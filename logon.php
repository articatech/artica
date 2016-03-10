<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(!function_exists("posix_getuid")){echo "<H1>posix_getuid() no such function, please check your PHP installation<br>Or reboot this system</H1>";return;}
	if(posix_getuid()==0){
		$array=posix_getpwuid();
		echo "<center style='margin:50px'><H1>posix_getuid() return 0 has user.<br>It seems that this server run has {$array["name"]} ({$array["uid"]}),<br>operation aborted<br>It should be a library issue, try to reboot this system</H1></center>";
		return;
	}	
	
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(!function_exists("session_start")){echo "<div style='margin:200px;padding:10px;border:2px solid red'><center><H1><error>session module is not properly loaded<BR>please restart artica-postfix web daemon using <br> <code>/etc/init.d/artica-postfix restart apache</code></error><div style='color:#d32d2d;font-size:13px'>Unable to stat session_start function</div></H1></div>";exit;}
	
	if(function_exists("session_start")){session_start();}
	
	$GLOBALS["DEBUG_INCLUDES"]=false;
	$GLOBALS["EXECUTED_AS_ROOT"]=false;
	unset($_SESSION["LANG_FILES"]);
	if(isset($_POST["php5-ldap-restart"])){restart_phpldap();exit;}
	if(isset($_POST["Changelang"])){applyLang();exit;}
	
	if(!isset($_POST["VIA_API"])){
		if(isset($_SESSION["uid"])){
			include_once('ressources/class.templates.inc');
			include_once('ressources/class.ldap.inc');
			include_once('ressources/class.user.inc');
			include_once('ressources/class.langages.inc');
			include_once('ressources/class.sockets.inc');
			include_once('ressources/class.mysql.inc');
			include_once('ressources/class.privileges.inc');
			include_once('ressources/class.browser.detection.inc');
			$users=new usersMenus();
			if($users->AsAnAdministratorGeneric){
				header("location:admin.index.php");exit;
			}
			error_log("[{$_SESSION["uid"]}]::Redirect to users.index.php in ".__FUNCTION__." file " .basename(__FILE__)." line ".__LINE__);
			header("location:admin.index.php");exit;
		}
	}

	
if(count($_GET)>0){
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.langages.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.privileges.inc');
	include_once('ressources/class.browser.detection.inc');
}

if(isset($_GET["autologmeta"])){autologmeta();exit;}
if(isset($_GET["logon-form-build"])){logonForm(true);exit;}
if(isset($_GET["popup-lang"])){TEMPLATE_LANG_POPUP();exit;}
if(isset($_GET["TEMPLATE_LANG_LINK"])){TEMPLATE_LANG_LINK();exit;}
if(isset($_GET["script"])){
	if($_GET["script"]=="autoaccount"){autoaccount_js();exit;}
}
if(isset($_GET["first_name"])){autoaccount_submit();exit;}
if(isset($_GET["createaccountForm"])){autoaccount_form();exit;}
if(isset($_GET["submitedform"])){autoaccount_form2();exit;}
if(isset($_GET["lang"])){lang();exit;}
if(isset($_POST["artica_username"])){logon();exit;}
if(isset($_GET["login-form"])){pagelogon();exit;}


if(!is_file("ressources/settings.inc")){echo "
<div style='margin:200px;padding:10px;border:2px solid red'><center><H1>
<error>Artica-postfix daemon is not running... <BR>please start artica-postfix daemon</error>
<div style='color:#d32d2d;font-size:13px'>Unable to stat ressources/settings.inc</div>
</H1></div>";exit;}


if(isset($_GET["ldap_connect"])){ldap_connect_error();exit;}
if(isset($_GET["ldap_connect_popup"])){ldap_connect_popup();exit;}

if(isset($_GET["reject-browser"])){reject_browser();exit;}
if(isset($_GET["session"])){session_settings();exit;}
if(isset($_GET["start"])){start_js();exit;}
if(isset($_GET["login-form"])){pagelogon();exit;}

echo buildPage();



function start_js(){
	$page=CurrentPageName();	
	$sock=new sockets();
	$logon_parameters=unserialize(base64_decode($sock->GET_INFO("LogonPageSettings")));
	if($logon_parameters["LANGUAGE_SELECTOR_REMOVE"]==null){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]="0";}	
	if(!is_numeric($logon_parameters["LANGUAGE_SELECTOR_REMOVE"])){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]=0;}
	if($logon_parameters["DEFAULT_LANGUAGE"]==null){$logon_parameters["DEFAULT_LANGUAGE"]="en";}	
	$ldap=new clladp();
	$CRYPT=0;
	if($ldap->IsKerbAuth()){
		$CRYPT=1;
		
	}

$html="
var MEM_USERNAME='';
var MEM_PASSWORD='';
var x_FillLogonForm=function(obj){
     var tempvalue=obj.responseText;
	 document.getElementById('loginform').innerHTML=tempvalue;
	
	}	
	
var x_SendLogonStart=function(obj){
	 if(document.getElementById('anim')){document.getElementById('anim').innerHTML='';}
	 if(document.getElementById('YouCanAnimateIt')){document.getElementById('YouCanAnimateIt').innerHTML='';}
	 UnlockPage();
     var tempvalue=obj.responseText;
	 var re = new RegExp(/location:(.+)/);
	 m=re.exec(tempvalue);
	  if(m){
		var url=m[1]; 
		 document.location.href=url;
		 return ;
      }	 
	 alert(tempvalue);
	}		

	function FillLogonForm(){
		if(!document.getElementById('loginform')){return;}
		var LANGUAGE_SELECTOR_REMOVE={$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]};
		if(LANGUAGE_SELECTOR_REMOVE==1){
			Set_Cookie('artica-language', '{$logon_parameters["DEFAULT_LANGUAGE"]}', '3600', '/', '', '');
		}
		var mem_logon_user='{$_COOKIE["mem-logon-user"]}';
		
		AnimateDiv('loginform');
		MEM_USERNAME=escape(MEM_USERNAME);
		LoadAjax('loginform','$page?login-form=yes&MEM_USERNAME='+MEM_USERNAME+'&LANGUAGE_SELECTOR_REMOVE={$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]}&DEFAULT_LANGUAGE={$logon_parameters["DEFAULT_LANGUAGE"]}');
	}
	
	function SendLogon(e){
		if(checkEnter(e)){SendLogonStart();}
	}
	
	function SendLogonStart(){
		if(document.getElementById('YouCanAnimateIt')){
			document.getElementById('YouCanAnimateIt').innerHTML='<img src=\"/img/preloader.gif\">';
		}
		
		var XHR = new XHRConnection();
		var CRYPT=$CRYPT;
		var LANGUAGE_SELECTOR_REMOVE={$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]};
		var password=document.getElementById('artica_password').value;
		if(document.getElementById('template')){Set_Cookie('artica-template', document.getElementById('template').value, '3600', '/', '', '');}
		if(document.getElementById('change-artica-name')){Set_Cookie('change-artica-name', document.getElementById('change-artica-name').value, '3600', '/', '', '');}
		if(!document.getElementById('artica_username')){alert('missing tag `artica_username`');}
		if(!document.getElementById('artica_password')){alert('missing tag `artica_password`');}
		
		XHR.appendData('artica_username',document.getElementById('artica_username').value);
		XHR.appendData('artica_password',MD5(password));
		if(CRYPT==1){
			XHR.appendData('artica_password_crypted',login_crypt(password));
			
		}
		Set_Cookie('mem-logon-user', document.getElementById('artica_username').value, '3600', '/', '', '');
		if(LANGUAGE_SELECTOR_REMOVE==1){
			if(document.getElementById('lang')){XHR.appendData('lang',document.getElementById('lang').value);}
			Set_Cookie('artica-language', '{$logon_parameters["DEFAULT_LANGUAGE"]}', '3600', '/', '', '');
		}else{
			if(document.getElementById('lang')){XHR.appendData('lang','{$logon_parameters["DEFAULT_LANGUAGE"]}');}
		}
		if(document.getElementById('anim')){AnimateDiv('anim');}
		LockPage();
		XHR.sendAndLoad('$page', 'POST',x_SendLogonStart);
		
	}
	


var X_ChangeDefaultLanguage= function (obj) {
	var results=obj.responseText;
	FillLogonForm(); 
	}
		
function ChangeDefaultLanguage(){
	var lang=document.getElementById('lang').value;
	Set_Cookie('artica-language', lang, '3600', '/', '', '');
	var XHR = new XHRConnection();
	XHR.appendData('Changelang',lang);
	MEM_USERNAME=document.getElementById('artica_username').value;
	XHR.sendAndLoad('logon.php', 'POST',X_ChangeDefaultLanguage);	
}

FillLogonForm();";	
		
echo $html;
	
}

function pagelogon(){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/logon.html";
	if(is_file($cachefile)){
		include_once('ressources/class.templates.inc');
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(@file_get_contents($cachefile));
		return;
	}
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.langages.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.privileges.inc');
	include_once('ressources/class.browser.detection.inc');	
	$sock=new sockets();
	$user=new usersMenus();
	

	

	
	if($user->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){$user->SQUID_INSTALLED=false;}
	}

error_log("[{$_SESSION["uid"]}]::logon form ". __FILE__. " line ". __LINE__);
$fixed_template=$sock->GET_INFO('ArticaFixedTemplate');
error_log("[{$_SESSION["uid"]}]::init fixed template=$fixed_template in ". __FILE__. " line ". __LINE__);
if(trim($fixed_template)<>null){$_COOKIE["artica-template"]=$fixed_template;}
$imglogon="img/logon2.png";
$SambaEnabled=$sock->GET_INFO("SambaEnabled");
if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
if($SambaEnabled==0){$user->SAMBA_INSTALLED=false;}

if($user->GATEWAY_APPLIANCE){$imglogon="img/artica-nas.png";}

if(!$user->POSTFIX_INSTALLED){
	if($user->SAMBA_INSTALLED){
		$imglogon="img/artica-nas.png";
	}
}
if(!$user->POSTFIX_INSTALLED){
	if($user->SQUID_INSTALLED){
		$imglogon="img/logon-squid.png";
	}
}
if($user->POSTFIX_INSTALLED){if(!$user->SQUID_INSTALLED){$imglogon="img/logon-postfix.png";}}


$persologon=$sock->GET_INFO("ArticaLogonImage");
if(trim($persologon)<>null){
	$imglogon=$persologon;
	$imglogon=str_replace("%TEMPLATE%","ressources/templates/{$_COOKIE["artica-template"]}",$imglogon);
}
	
if($user->KASPERSKY_SMTP_APPLIANCE){$imglogon="img/logon-k.png";}
//if($user->KASPERSKY_WEB_APPLIANCE){$imglogon="img/logon-squidk.png";}
if($user->KASPERSKY_WEB_APPLIANCE){$imglogon="img/logo-artica-kav.png";}
if($user->ZARAFA_APPLIANCE){$imglogon="img/logon-zarafa.png";}
if($user->OPENVPN_APPLIANCE){$imglogon="img/logo-openvpn.png";}
if($user->APACHE_APPLIANCE){$imglogon="img/artica-apache.png";}
if($user->MYCOSI_APPLIANCE){$imglogon="img/my-cosi-3d.png";}
if($user->GATEWAY_APPLIANCE){$imglogon="img/artica-nas.png";}



$page=CurrentPageName();
$center_bg=null;

$newacc="<div style='float:left;margin-left:-180px;width:179px;margin-top:-50px'>
<table style='width:100%'>
<tr " . CellRollOver("Loadjs('$page?script=autoaccount')","{create_your_user_account}").">
<td width=1%>
	<img src='img/member-64-add.png'>
</td>
<td nowrap valign='top'><H3>{register}</H3>
<p class=caption>{create_your_user_account}</p>
</td>
</tr>
</table>
</div>
<script>
	ChangeHTMLTitle();
</script>
	

";

error_log("[{$_SESSION["uid"]}]::-> buildFrontEnd  ". __FILE__. " line ". __LINE__);
$sock->getFrameWork("cmd.php?buildFrontEnd=yes");

error_log("[{$_SESSION["uid"]}]::-> CheckAutousers  ". __FILE__. " line ". __LINE__);
if(!CheckAutousers()){$newacc=null;}

	if($user->KASPERSKY_WEB_APPLIANCE){
		//$GLOBALS["CHANGE_TEMPLATE"]="squid.kav.html";
		//$imglogon=null;
		//$addedlogo="<div style='float:left;margin-left:-190px;width:537px;height:474px;background-image:url(/img/bg_kavweb-appliance.jpg);background-position:left top;'></div>";
		//
	}

if($imglogon<>null){$imglogon="background-image:url($imglogon);";}
//$logonForm=logonForm();

	$sock=new sockets();
	$logon_parameters=unserialize(base64_decode($sock->GET_INFO("LogonPageSettings")));
	if($logon_parameters["LANGUAGE_SELECTOR_REMOVE"]==null){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]="0";}	
	if(!is_numeric($logon_parameters["LANGUAGE_SELECTOR_REMOVE"])){$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]=0;}
	if($logon_parameters["DEFAULT_LANGUAGE"]==null){$logon_parameters["DEFAULT_LANGUAGE"]="en";}


$html="
<script>
function SaveSession(){
	var LANGUAGE_SELECTOR_REMOVE={$logon_parameters["LANGUAGE_SELECTOR_REMOVE"]};
	var template=document.getElementById('template').value;
	if(LANGUAGE_SELECTOR_REMOVE==0){
		var lang=document.getElementById('lang').value;
	}else{
		var lang='{$logon_parameters["DEFAULT_LANGUAGE"]}';
	}
	Set_Cookie('artica-template', template, '3600', '/', '', '');
	Set_Cookie('artica-language', lang, '3600', '/', '', '');
	var XHR = new XHRConnection();
	XHR.appendData('lang',lang);
	XHR.sendAndLoad('$page', 'GET');		
	location.reload();
}

function LoadModal(){
	$('#loginform').modal({onOpen: function (dialog) {
		dialog.overlay.fadeIn('slow', function () {
			dialog.container.slideDown('slow', function () {
				dialog.data.fadeIn('slow');
			});
		});
	}});
}

LoadAjax('logon-form','$page?logon-form-build=yes');
</script>
$newacc
<center>
<span id='logon-form'></span>

<div style='width:667px;height:395px;
	background-position:center top;
	$imglogon
	background-repeat:no-repeat;border:1px solid #FFFFFF'>
</div>
</center>

<script>ChangeHTMLTitle();Loadjs('js/mouse.js')</script>
";

$tpl=new templates();
@file_put_contents($cachefile, $html);
$html=$tpl->_ENGINE_parse_body($html);

echo $html;
}

function logonForm($ouptut=false){
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	$_SESSION["DisableSSHControl"]=trim($sock->GET_INFO("DisableSSHControl"));
	$AllowInternetUsersCreateOrg=$sock->GET_INFO("AllowInternetUsersCreateOrg");
	$AddInArticaLogonFrontPage=$sock->GET_INFO("AddInArticaLogonFrontPage");
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$FileCookyLang=$sock->GET_INFO($FileCookyKey);
	$template=null;
	
	$html=new htmltools_inc();
	$lang=$html->LanguageArray();
	$MEM_LANG=$_COOKIE["artica-language"];
	if($MEM_LANG==null){$MEM_LANG=$FileCookyLang;}
	
	
	if($_GET["MEM_USERNAME"]==null){
		if($_COOKIE["mem-logon-user"]<>null){
			$_GET["MEM_USERNAME"]=$_COOKIE["mem-logon-user"];
		}
	}
	
	
	if($MEM_LANG==null){
		$languageClass=new articaLang();
		$defaultlanguage=$languageClass->get_languages();
		if($defaultlanguage=="pt"){$defaultlanguage="po";}
		if($lang[$defaultlanguage]==null){$defaultlanguage="en";}
		setcookie("artica-language", $defaultlanguage, time()+172800);
		$sock->SET_INFO($FileCookyKey, $defaultlanguage);
	}else{$defaultlanguage=$MEM_LANG;}
	
	
	
	
	ksort($lang);
	$field_lang=Field_array_Hash($lang,'lang',$defaultlanguage,"ChangeDefaultLanguage()");

	
	$language_selector="
		<tr>
			<td align='right' class=legend style='font-size:13px'><strong>{language}:</strong></td>
			<td>$field_lang</td>
		</tr>";

		
	if($_GET["LANGUAGE_SELECTOR_REMOVE"]==1){
		$language_selector="<tr>
			<td colspan=2 style='margin:-1px;padding:-1px>
			<input type='hidden' name='lang' id='lang' value='{$_GET["DEFAULT_LANGUAGE"]}'></td>
			</tr>
		";
			
		$_SESSION["detected_lang"]=$_GET["DEFAULT_LANGUAGE"];
		unset($_SESSION["translation"]);
		setcookie("artica-language", $_GET["DEFAULT_LANGUAGE"], time()+172800);			
		$sock->SET_INFO($FileCookyKey, $_GET["DEFAULT_LANGUAGE"]);	
	}
		

	$contour_color="#005447";
	
	
	if($users->KASPERSKY_WEB_APPLIANCE){
		$template="<input type='hidden' id='template' value='Kav4Proxy'>";
		
	}
	
	if($users->MYCOSI_APPLIANCE){
		$contour_color="#FFB683";
		$template="<input type='hidden' id='template' value='myCosi'>";
		$changename="<input type='hidden' id='change-artica-name' value='MyCosi'>";
	
	}
	
	if($users->WEBSECURIZE){
		$template="<input type='hidden' id='template' value='Websecurize'>";
		$changename="<input type='hidden' id='change-artica-name' value='Web Securize'>";
	}
	
	if($users->BAMSIGHT){
		$template="<input type='hidden' id='template' value='BamSight'>";
		$changename="<input type='hidden' id='change-artica-name' value='BamSight'>";
	}	
	
	if($users->LANWANSAT){
		$template="<input type='hidden' id='template' value='LanWanSAT'>";
		$changename="<input type='hidden' id='change-artica-name' value='LanWanSAT Proxy'>";
	}
	
	
if($AllowInternetUsersCreateOrg==1){
	if($AddInArticaLogonFrontPage==1){
		$addon="
		<div>
		<a href=\"miniadm.register.php\" style='font-size:11px'>{register}</a>&nbsp;|&nbsp;<a href=\"miniadm.php\" style='font-size:11px'>{organization_administrator}</a>
		</div>";
		
	}
}

if(!function_exists('ldap_connect')){
	$ldap_error= "
	<div style='border:3px solid red;font-size:16px;color:#d32d2d;padding:5px;width:220px;position:absolute;left:0;top:0;background-color:white'>
		<center>Error ldap_connect() try to restart the web service<br><br>or check if php5-ldap is installed
		<br>And restart web server</center>
		<center>
		<form name='FF1' method='POST' action='$page'>
		<input type='hidden' name='php5-ldap-restart' value='1'>
		<input type='submit' value='&nbsp;&nbsp;&nbsp;Restart The web service&nbsp;&nbsp;&nbsp;'>
		</form>
		</center>
	</div>
	";

}

if(!function_exists("posix_getuid")){
	$ldap_error=$ldap_error. "
	<div style='border:3px solid red;font-size:16px;color:#d32d2d;padding:5px;width:220px;position:absolute;left:250px;top:0;background-color:white'>
	Artica-postfix need <strong>posix &laquo;posix_getuid()&raquo;</strong>  function <BR>... please try to install php-posix
	</div>";
	
}

if(!function_exists('mysql_connect')){
	$ldap_error=$ldap_error. "
	<div style='border:3px solid red;font-size:16px;color:#d32d2d;padding:5px;width:220px;position:absolute;left:500px;top:0;background-color:white'>
	Artica-postfix need  <strong> &laquo;mysql_connect()&raquo;</strong>  function <BR>... please try to install php-mysql
	</div>";	
	
}
	$browser=browser_detection();
	
	if($browser=="ie"){
		$ldap_error=$ldap_error. "
		<div style='border:3px solid red;font-size:16px;color:#d32d2d;padding:5px;width:220px;position:absolute;left:200px;top:50;background-color:white'>
		{NOIEPLEASE_TEXT}<br>
		<p style='font-size:12px;text-align:left'>
		<i>{error_no_ie_text}</i></p></div><br>";
	}

	
$html="
<!-- LINE ".__LINE__." -->			
<div id='loginform'>
<center>
<div style='color:#d32d2d;font-size:13px;font-weight:bold;width:70%;
font-family:Helvetica,Tahoma,Verdana,sans-serif'>{$_GET["ERROR"]}</div>
</center>
<H1 style='text-align:left'>{logon}</h1>
<form name='logon_{$_SERVER["SERVER_NAME"]}' method='POST'>
				<table style='width:50%;border:3px solid $contour_color;margin:5px;padding:5px;'>
				<tr>
					<td align='right' class=legend style='font-size:13px'><strong>{username}:</strong></td>
					<td><input type='text' id='artica_username' name='artica_username' value='{$_GET["MEM_USERNAME"]}' 
					style='border:1px solid black;width:100%;font-size:13px'
					OnkeyPress=\"javascript:SendLogon(event)\"></td>
				</tr>
				<tr>
					<td align='right' class=legend style='font-size:13px'><strong>{password}:</strong></td>
					<td><input type='password'  id='artica_password' name='artica_password' 
					value='{$_GET["MEM_PASSWORD"]}' style='border:1px solid black;width:100%;font-size:13px'
					OnkeyPress=\"javascript:SendLogon(event)\"></td>
				</tr>
				$language_selector
				<tr>
					<td colspan=2 align='right' ><br>
					<input type='button' value='{logon}&nbsp;&raquo;' OnClick=\"javascript:SendLogonStart();\" OnkeyPress=\"javascript:SendLogon(event)\">
					</td>
				</tr>
				<tr><td colspan=2 align='right'>$addon</td>
				<tr>
				<td colspan=2 align='right' style='padding-top:10px'><span id='anim'></span></td>
				</tr>
				</table>	
	
</div>$template$changename
$ldap_error";
if($ouptut){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	return;
}
return $html;
}


function ErrorConnection($ldapClass){


$port=$ldapClass->ldap_port;
$password=$ldapClass->ldap_password;
$admin=$ldapClass->ldap_admin;
$suffix=$ldapClass->suffix;
$ldap_host=$ldapClass->ldap_host;

$dn="cn=admin,$suffix";

writelogs("testing connection dn $dn",__FUNCTION__,__FILE__);

$ldap_connection=ldap_connect($ldap_host, $port );
if(!$ldap_connection){ 
	$error="Please check LDAP connection, it seems that Artica is not allowed to connect to &laquo;$ldap_host:$port&raquo;<br>";}
writelogs("Error after connecting:$error '$ldap_connection'",__FUNCTION__,__FILE__);	
	
if($error==null){
		writelogs("settings ldap options LDAP_OPT_PROTOCOL_VERSION",__FUNCTION__,__FILE__);
		ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
		writelogs("settings ldap options LDAP_OPT_REFERRALS",__FUNCTION__,__FILE__);
		ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0); 		 
		$ldapbind=@ldap_bind($ldap_connection, $dn, $password);
		writelogs("Bind success",__FUNCTION__,__FILE__);	
		
		if(!$ldapbind){
			$errornumber=ldap_errno($ldap_connection);
			$error_text=ldap_err2str($ldap_connection);
			
			switch ($errornumber) {
					case 0x31:
						$error=$error . "<li>Bad username or password. Please try again.</li>";
						break;
					case 0x32:
						$error=$error . "<li>Insufficient access rights.</li>";
						break;
					case 81:
						$error=$error . "<li>Unable to connect to the LDAP server\n $ldap_host:$port, <br>please,verify if ldap daemon is running or the ldap server address";
						break;						
					case -1:
						$error=$error . "<li>$error_text, <br>it seems that Artica could not connect to the server</li>";
						break;
					default:
						$error=$error . "<li>Could not bind to the LDAP server $error_text</li>";
 				}			

		$error="Error number $errornumber,$error_text<br>$error";}
} 				
	
	$html="
		
		
	


			<center>
				<form>
				<div style='width:667px;height:395px;background-image:url(img/nologon.jpg);background-repeat:no-repeat;border:1px solid #FFFFFF'>
					<div style='float:right;margin-right:35px;margin-top:0px;background-color:#FFFFFF;border:1px solid black;padding:5px;font-size:11px;font-weight:bold;color:#d32d2d;text-align:left'>
						LDAP error with the following informations : <br>
						<ul>
						<li>$ldap_host:$port</li>
						<li>$dn</li>
						</ul>
						$error
						<br>
						
						
					</div>
				</form>
				</div>
			</center>";

$tpl=new template_users('Artica-postfix {error}',$html,1,0,0,0);
echo $tpl->web_page;	
	
	
}


function LDAP_FORM(){
	
	$ldap=new clladp();
	$ldap_admin=$ldap->ldap_admin;
	$ldap_host=$ldap->ldap_host;
	$suffix=$ldap->suffix;

	
	
}

function applyLang(){
	session_start();
	include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
	$sock=new sockets();
	$_SESSION["detected_lang"]=$_POST["Changelang"];
	//echo "Change lang to {$_POST["Changelang"]}";
	unset($_SESSION["translation"]);
	setcookie("artica-language", $_POST["Changelang"], time()+172800);
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$sock->SET_INFO($FileCookyKey, $_POST["Changelang"]);
	
}


function autologmeta(){
	
	$md5=$_GET["autologmeta"];
	include("ressources/settings.inc");
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.langages.inc');
	$sock=new sockets();
	$tpl=new templates();
	$_POST["artica_password"]=url_decode_special($_POST["artica_password"]);
	writelogs("Testing logon....{$_POST["artica_username"]}",__FUNCTION__,__FILE__,__LINE__);
	//writelogs("Testing logon.... password:{$_POST["artica_password"]}",__FUNCTION__,__FILE__,__LINE__);
	$_COOKIE["artica-language"]=$_POST["lang"];
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$sock->SET_INFO($FileCookyKey, $_POST["Changelang"]);
	if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
	if($_GLOBAL["ldap_admin"]==null){
		$sock->getFrameWork("services.php?process1-tenir=yes?MyCURLTIMEOUT=120");
		include("ressources/settings.inc");
	}
	
	if($_GLOBAL["ldap_admin"]==null){
		if($VIA_API){echo "FALSE";return;}
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{ldap_username_corrupt_text}");
		return null;
	}
	
	$md5Check=md5($_GLOBAL["ldap_admin"].$_GLOBAL["ldap_password"]);
	if($md5<>$md5Check){die("Wrong credentials");}
	
	$users=new usersMenus();
	artica_mysql_events("Success to logon on the Web console from {$_SERVER["REMOTE_HOST"]}/META as SuperAdmin",@implode("\n",$notice),"security","security");
	//session_start();
	$_SESSION["uid"]='-100';
	$_SESSION["groupid"]='-100';
	$_SESSION["passwd"]=$_GLOBAL["ldap_password"];
	$_SESSION["MINIADM"]=false;
	setcookie("MINIADM", "No", time()+1000);
	$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
	setcookie("artica-language", $_POST["lang"], time()+172800);
	$_SESSION["detected_lang"]=$_POST["lang"];
	$_SESSION["CORP"]=$users->CORP_LICENSE;
	$_SESSION["privileges"]["ArticaGroupPrivileges"]='
	[AllowAddGroup]="yes"
	[AllowAddUsers]="yes"
	[AllowChangeKav]="yes"
	[AllowChangeKas]="yes"
	[AllowChangeUserPassword]="yes"
	[AllowEditAliases]="yes"
	[AllowEditAsWbl]="yes"
	[AsSystemAdministrator]="yes"
	[AsPostfixAdministrator]="yes"
	[AsArticaAdministrator]="yes"
	[AsArticaMetaAdmin]="yes"';
	

	$tpl=new templates();
	$sock->getFrameWork("squid.php?clean-catz-cache=yes");
	writelogs("OK it is a global admin -> location:admin.index.php",_FUNCTION__,__FILE__,__LINE__);
	header("location:admin.index.php");
	exit;
	
	
}


function logon(){
	include("ressources/settings.inc");
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');	
	include_once('ressources/class.langages.inc');
	$sock=new sockets();
	$FixedLanguage=$sock->GET_INFO("FixedLanguage");
	$tpl=new templates();
	$_POST["artica_password"]=url_decode_special($_POST["artica_password"]);
	writelogs("Testing logon....{$_POST["artica_username"]}",__FUNCTION__,__FILE__,__LINE__);
	//writelogs("Testing logon.... password:{$_POST["artica_password"]}",__FUNCTION__,__FILE__,__LINE__);	
	$_COOKIE["artica-language"]=$_POST["lang"];
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$sock->SET_INFO($FileCookyKey, $_POST["Changelang"]);
	if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
	$VIA_API=false;
	if(isset($_POST["VIA_API"])){$VIA_API=true;}
	
	if($_SESSION["uid"]<>null){
		if(!$VIA_API){
			echo $tpl->javascript_parse_text("{wrong_password_or_username}");
			return;
		}
		
	}
	
	$socks=new sockets();
	
	
	while (list ($index, $value) = each ($_SERVER) ){
		$notice[]="$index:$value";
	}
	
	if($_GLOBAL["ldap_admin"]==null){
		$sock->getFrameWork("services.php?process1-tenir=yes?MyCURLTIMEOUT=120");
		include("ressources/settings.inc");
	}
	
	if($_GLOBAL["ldap_admin"]==null){
		if($VIA_API){echo "FALSE";return;}
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{ldap_username_corrupt_text}");
		return null;
	}
	
	if(preg_match("#^cn=(.+)#", $_GLOBAL["ldap_admin"],$re)){$_GLOBAL["ldap_admin"]=$re[1];}
	
	if(trim($_POST["artica_password"])==null){
		echo $tpl->javascript_parse_text("{wrong_password_or_username}");
		return;
	}
	
	
	$md5submitted=$_POST["artica_password"];
	if($VIA_API){$md5submitted=md5($_POST["artica_password"]);}
	$md5Manager=md5(trim($_GLOBAL["ldap_password"]));
	if(trim($GLOBALS["FixedLanguage"])<>null){$_POST["lang"]=$GLOBALS["FixedLanguage"];}
	
	
	$trimed_artica_username=trim(strtolower($_POST["artica_username"]));
	$trimed_ldap_admin=trim(strtolower($_GLOBAL["ldap_admin"]));
	writelogs("Manager -> $trimed_artica_username ?=== $trimed_ldap_admin",_FUNCTION__,__FILE__,__LINE__);
	

	if($trimed_artica_username==$trimed_ldap_admin){
		writelogs("Manager: `YES`",__FUNCTION__,__FILE__,__LINE__);
		if($md5Manager<>$md5submitted){
			$tpl=new templates();
			//writelogs("Testing logon.... password:{$_POST["artica_password"]}!==\"{$_GLOBAL["ldap_password"]}\"",__FUNCTION__,__FILE__,__LINE__);	
			artica_mysql_events("Failed to logon on the Artica Web console from {$_SERVER["REMOTE_HOST"]}",@implode("\n",$notice),"security","security");
			if($VIA_API){echo "FALSE";return;}
			echo $tpl->javascript_parse_text("{wrong_password_or_username}");
			return null;
		}else{
			
			$users=new usersMenus();
			artica_mysql_events("Success to logon on the Artica Web console from {$_SERVER["REMOTE_HOST"]} as SuperAdmin",@implode("\n",$notice),"security","security");
			//session_start();
			$_SESSION["uid"]='-100';
			$_SESSION["groupid"]='-100';
			$_SESSION["passwd"]=$_GLOBAL["ldap_password"];
			$_SESSION["MINIADM"]=false;
			setcookie("MINIADM", "No", time()+1000);
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			setcookie("artica-language", $_POST["lang"], time()+172800);
			$_SESSION["detected_lang"]=$_POST["lang"];
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"
			[AsArticaMetaAdmin]="yes"
			';
			
			
		if($VIA_API){
			writelogs("VIA API = TRUE -> BUILD SESSION",_FUNCTION__,__FILE__,__LINE__);
			BuildSession($_SESSION["uid"]);
			echo "TRUE";
			return;
		}
		$tpl=new templates();
		$sock->getFrameWork("squid.php?clean-catz-cache=yes");
		writelogs("OK it is a global admin -> location:admin.index.php",_FUNCTION__,__FILE__,__LINE__);
		echo("location:admin.index.php");
		exit;
		}
	}
	
	
	
	$ldap=new clladp();
	
	if($ldap->IsKerbAuth()){
		$userPassword=$_POST["artica_password"];
		
		if(isset($_POST["artica_password_crypted"])){
			$userPassword=$_POST["artica_password_crypted"];
			include_once(dirname(__FILE__)."/ressources/class.cryptform.inc");
			$userPassword=logon_decrypt($userPassword);
		}

		writelogs("*** TEST Active Directory user {$_POST["artica_username"]} ****",__FUNCTION__,__FILE__,__LINE__);
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($_POST["artica_username"],$userPassword)){
			writelogs("*** TEST Active Directory user {$_POST["artica_username"]} success ****",__FUNCTION__,__FILE__,__LINE__);
			$_SESSION["MINIADM"]=false;
			setcookie("MINIADM", "No", time()+1000);
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			setcookie("artica-language", $_POST["lang"], time()+172800);
			$_SESSION["detected_lang"]=$_POST["lang"];
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$users=new usersMenus();
			$privs=new privileges($_POST["artica_username"]);
			$privileges_array=$privs->privs;
			DumpPrivileges($_POST["artica_username"],$privileges_array);
			$users->_TranslateRights($privileges_array,true);
			
			setcookie("mem-logon-user", $_POST["artica_username"], time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["uid"]=$_POST["artica_username"];
			
			BuildSession($_POST["artica_username"]);
			$sock->getFrameWork("squid.php?clean-catz-cache=yes");
			$_SESSION["MINIADM"]=false;
			setcookie("MINIADM", "No", time()+1000);
			if($VIA_API){BuildSession($_SESSION["uid"]);echo "TRUE";return;}
			echo("location:admin.index.php");
			return;
		}else{
			if(is_array($GLOBALS["CLASS_ACTV"])){
				while (list ($key, $line) = each ($GLOBALS["CLASS_ACTV"])){
					writelogs("*** Active Directory $line",__FUNCTION__,__FILE__,__LINE__);
				}
			}
			
		}
	}
	
	
	
	if(Radius_admins($_POST["artica_username"],$md5submitted)){
		writelogs('*** TEST RADIUS USER ****',__FUNCTION__,__FILE__,__LINE__);
		$tpl=new templates();
		$sock->getFrameWork("squid.php?clean-catz-cache=yes");
		writelogs("OK it is a global admin -> location:admin.index.php",_FUNCTION__,__FILE__,__LINE__);
		if($VIA_API){BuildSession($_SESSION["uid"]);echo "TRUE";return;}
		echo("location:admin.index.php");
		exit;
	}
	
	
	writelogs('This is not Global admin, so test user...',__FUNCTION__,__FILE__,__LINE__);
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP")){$EnableOpenLDAP=1;}
	
	if($EnableOpenLDAP==0){
		echo $tpl->javascript_parse_text("{wrong_password_or_username}");
		return null;
	}
		
		
	$u=new user($_POST["artica_username"]);
	$userPassword=$u->password;
	if(trim($u->uidNumber)==null){
		if($VIA_API){echo "FALSE";return;}
		writelogs('Unable to get user infos abort',__FUNCTION__,__FILE__);
		echo $tpl->javascript_parse_text("{wrong_password_or_username}");
		return null;
	}
	
	
	$tpl=new templates();
	
	if( trim($_POST["artica_password"])==md5(trim($userPassword))){
			BuildSession($u->uid);
			$ldap=new clladp();
			$users=new usersMenus();
			$privs=new privileges($u->uid);
			$privileges_array=$privs->privs;
			if(trim($FixedLanguage)<>null){$_SESSION["detected_lang"]=$FixedLanguage;}
			
			
			$users->_TranslateRights($privileges_array,true);
			if(!$users->IfIsAnuser(true)){
				if($VIA_API){echo "TRUE";return;}
				artica_mysql_events("Success to logon on the Artica Web console from {$_SERVER["REMOTE_HOST"]} as User",@implode("\n",$notice),"security","security");
				writelogs("[{$_POST["artica_username"]}]: This is not an user =>admin.index.php",__FUNCTION__,__FILE__);
				$sock->getFrameWork("squid.php?clean-catz-cache=yes");
				$_SESSION["MINIADM"]=false;
				setcookie("MINIADM", "No", time()+1000);
				if($VIA_API){BuildSession($_SESSION["uid"]);echo "TRUE";return;}
				echo "location:admin.index.php";
				return null;
			}
			
			
			if($VIA_API){BuildSession($_SESSION["uid"]);echo "TRUE";return;}
			
			
			$array["USERNAME"]=$_POST["artica_username"];
			$array["PASSWORD"]=md5($_POST["artica_username"]);
			$credentials=base64_encode(serialize($array));
			artica_mysql_events("Success to logon on the management console as user from {$_SERVER["REMOTE_HOST"]} (bad password)",@implode("\n",$notice),"security","security");
			echo "location:admin.index.php";
			return null;
		exit;
	
	}else{	
		if($VIA_API){echo "FALSE";return;}
		writelogs("[{$_POST["artica_username"]}]: The password typed  is not the same in ldap database...",__FUNCTION__,__FILE__);
		echo $tpl->javascript_parse_text("{wrong_password_or_username}");
		return null;
	}
	

	
}

function DumpPrivileges($username,$array){
	while (list ($key, $val) = each ($array) ){
		if($val=="yes"){
			$f[]="[$key]=\"$val\"";
		}
		writelogs("[$username]: $key = $val",__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=@implode("\n", $f);
	
}


function Radius_admins($username,$MD5password){
	
	$q=new mysql();
	$sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$username' AND `attribute`='Cleartext-Password' LIMIT 0,1";
	writelogs("$username:: Is a RADIUS users \"$sql\"",__FUNCTION__,__FILE__,__LINE__);
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	if(!$q->ok){return false;}
	
	if($ligne["id"]==0){return false;}
	
	writelogs("$username:: $MD5password <> ".md5($ligne["value"]),__FUNCTION__,__FILE__,__LINE__);
	$checkRadiusPass=false;
	
	if(md5($ligne["value"])==$MD5password){
		writelogs("$username:: RADIUS Password true for no MD5",__FUNCTION__,__FILE__,__LINE__);
		$checkRadiusPass=true;
	}
	if(md5($ligne["value"])==$MD5password){
		writelogs("$username:: RADIUS Password true for yes MD5",__FUNCTION__,__FILE__,__LINE__);
		$checkRadiusPass=true;
	}
	
	if(!$checkRadiusPass){return false;}
		
		
	writelogs("$username:: Authenticated as a RADIUS users id={$ligne["id"]}",__FUNCTION__,__FILE__,__LINE__);
	
	$_SESSION["uid"]=$username;
	$_SESSION["RADIUS_ID"]=$ligne["id"];
	$privs=new privileges($username,null,$ligne["id"]);
	
	$privileges_array=$privs->privs;
	if(count($privileges_array)==0){
		unset($_SESSION["RADIUS_ID"]);
		unset($_SESSION["uid"]);
		return false;
	}
	
	$_SESSION["CORP"]=$users->CORP_LICENSE;
	$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
	setcookie("mem-logon-user", $username, time()+172800);
	$_SESSION["privileges_array"]=$privs->privs;
	while (list ($key, $val) = each ($_SESSION["privileges_array"]) ){if(!isset($_SESSION[$key])){$_SESSION[$key]=$val;}}
	reset($_SESSION["privileges_array"]);
	BuildSession($username);
	return true;
	
}


function session_settings(){
	$lang=DirFolders('ressources/language');
	$list=DirFolders('ressources/templates');
	unset($list["default"]);
	$list[null]="{default}";
	$lang[null]="{default}";
	$field=Field_array_Hash($list,'template',$_COOKIE["artica-template"]);
	$field_lang=Field_array_Hash($lang,'lang',$_COOKIE["artica-language"]);
	
	$html="<h1>{session_settings}</H1>
	<H3>{template}</H3>
	<p class=caption>{template_text}</p>
	<table style='width:100%'>
	<tr>
	<td class='legend'>{template}:</td>
	<td>$field</td>
	</tr>
	<tr>
	<td class='legend'>{language}:</td>
	<td>$field_lang</td>
	</tr>	
	<td colspan=2 align='right'><input type='button' OnClick=\"javascript:SaveSession();\" value='{apply}&nbsp;&raquo;'></td>
	</tr>
	</table>
	
	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function autoaccount_js(){
	$page=CurrentPageName();
	$html="
	
		load();
	
	function load(){
	YahooWin(650,'$page?createaccountForm=yes','','');	
	}
	
	";
	echo $html;
	
}

function autoaccount_form(){
	$page=CurrentPageName();
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();
	
	
	
	
	$field_domains=Field_array_Hash($domains,"domain");
	
	$html="<h1>{register}</h1>
	<div id='fromadd'>
	<form name='FFMCOMPRESSS'>
	<table style='width:100%'>
	<tr>
	<td valign='top'><img src='img/bg_lego.png'></td>
	<td valign='top'>
		<H3>{create_your_user_account}</H3>
	<table class=form style='width:99%'>
	<tr>
		<td class=legend>{firstname}:</td>
		<td>" . Field_text('first_name',null,'width:160px')."</td>
	</tr>
	<tr>
		<td class=legend>{lastname}:</td>
		<td>" . Field_text('last_name',null,'width:160px')."</td>
	</tr>	
		<td class=legend>{login}:</td>
		<td>" . Field_text('login',null,'width:100px')."@$field_domains</td>
	</tr>
	</tr>	
		<td class=legend>{password}:</td>
		<td>" . Field_password('password',null,'width:100px')."</td>
	</tr>	
	<tr><td colspan=2><hr></td></tr>
	<tr><td colspan=2 align='right'>
		<input type='button' OnClick=\"javascript:ParseForm('FFMCOMPRESSS','$page',true,false,false,'fromadd','$page?submitedform=yes');\"
		 value='{add}&nbsp;&raquo;'>
	</td>
	</tR>
	</table>
	</form>
	</div>
	
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function autoaccount_submit(){
	if(!CheckAutousers()){exit;}
	$login=$_GET["login"];
	$email="$login@{$_GET["domain"]}";
	$tpl=new templates();
	$ldap=new clladp();
	$uid=$ldap->uid_from_email($email);
	if($uid<>null){
		echo $tpl->_ENGINE_parse_body('{account_already_exists}');
		exit;
	}
	
	$ou=$ldap->ou_by_smtp_domain($_GET["domain"]);
	$user=new user();
	$user->DisplayName=$_GET["first_name"]." ".$_GET["last_name"];
	$user->sn=$_GET["first_name"];
	$user->cn=$_GET["last_name"];
	$user->mail=$email;
	$user->domainname=$_GET["domain"];
	$user->password=$_GET["password"];
	$user->ou=$ou;
	$user->uid=$login;
	if($user->add_user()){
		echo $tpl->_ENGINE_parse_body("{success}:$email\n");
	}else{
		echo $user->ldap_error;
	}
	
	
}

function CheckAutousers(){
include_once("auto-account.php");
include_once("ressources/class.tcpip.inc");	
$account=new AutoUsers();
$chckip=false;
if($account->AutoCreateAccountEnabled==0){
	writelogs("auto-account is disabled",__FUNCTION__,__FILE__);
	return false;
	}
	$ip=new IP();
	$list=$account->AutoCreateAccountIPArray;
	if(is_array($list)){
		while (list ($num, $val) = each ($list) ){
			if($ip->isInRange($_SERVER['REMOTE_ADDR'],trim($val))){
				writelogs("{$_SERVER['REMOTE_ADDR']} against $val=TRUE",__FUNCTION__,__FILE__);
				return true;
				break;
			}
		}	
	}else{
		writelogs("{$_SERVER['REMOTE_ADDR']} IP List is null",__FUNCTION__,__FILE__);
		return false;
	}

return false;	
}
function autoaccount_form2(){
	$tpl=new templates();
	$html="<H3 style='color:#d32d2d'>{you_can_close_this_form}</h3>";
	echo $tpl->_ENGINE_parse_body($html);
}
function restart_phpldap(){
	
	if(!isset($_GET["fromClass"])){
		if(function_exists('ldap_connect')){
			echo "<html><head>
			<META HTTP-EQUIV='REFRESH' CONTENT='10'>
			</head>
			<body><center style='margin:100px'<H1>ldap_connect is ok, no need to restart web server</h1></center></body>
			</html>
			";
			exit;
		}
	}
	
	if(!function_exists('ldap_connect')){
		include_once('ressources/class.sockets.inc');
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?restart-apache-no-timeout");
		echo "<html><head>
		<META HTTP-EQUIV='REFRESH' CONTENT='10'>
		</head>
		<body><center style='margin:100px'<H1>Waiting...10 seconds</h1></center></body>
		</html>
		
		";		
		
	}
}
function lang(){
	$sock=new sockets();
	$sock->SET_INFO("session_language",$_GET["lang"]);
}


function buildPage(){
	if($GLOBALS["VERBOSE"]){echo "<H1>buildPage() function line ".__LINE__."</H1>";}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.langages.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.privileges.inc');
	include_once('ressources/class.browser.detection.inc');	
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	unset($_SESSION);
	unset($_COOKIE["artica-template"]);
	$sslcert=null;
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	if($GLOBALS["VERBOSE"]){echo "<H1>articaLang() function line ".__LINE__."</H1>";}
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	if($GLOBALS["VERBOSE"]){echo "<H1>DetectedLanguage = $DetectedLanguage line ".__LINE__."</H1>";}
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;	
	$TEMPLATE_INDEX="logon.html";
	if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
	
	$ie=browser_detection();
	
	if($ie=="ie"){
		FATAL_ERROR_SHOW_128_NO_IE();
		
		return;
	}
	
	
	$logo="logo.gif";
	$logo_bg="bg_header.gif";
	$bg_color="#005447";
	$ProductName="Artica";
	$link_company_name="http://www.articatech.com";
	$OEM_CompanyName="Artica Tech";
	$template=null;
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$SSlBumpAllowLogon=intval($sock->GET_INFO("SSlBumpAllowLogon"));
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	$LOCK_TEMPLATE=false;
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	$AsMetaServer=intval($sock->GET_INFO("AsMetaServer"));
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){if($EnableNginx==1){$SquidActHasReverse=1;} }
	$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	if($hostname==null){$hostname=$users->hostname;}
	$WordPressInstalled=intval($sock->GET_INFO("WordPressInstalled"));
	if($GLOBALS["VERBOSE"]){echo "new templates() line:".__LINE__."<br>\n";}
	
	
	
	
	if($users->KASPERSKY_WEB_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;color:black'>".__LINE__.": KASPERSKY_WEB_APPLIANCE DETECTED</div>\n";}
		$template="Kav4Proxy";
		$logo="logo-kav.gif";
	}
	if($users->ZARAFA_APPLIANCE){$template="zarafa";$logo="logo-kav.gif";}	
	if($users->MYCOSI_APPLIANCE){$logo_bg="bg_header_kavweb.gif";$logo="logo-mycosi.gif";$bg_color="#FFB683";$ProductName="MyCosi";$template="myCosi";}
	if($users->APACHE_APPLIANCE){$template="Apache";$users->SAMBA_APPLIANCE=false;$logo="logo-kav.gif";}
	
	
	if($users->WEBSECURIZE){
			$LOCK_TEMPLATE=true;
			$users->SQUID_APPLIANCE=false;
			$template="Websecurize";
			$users->SAMBA_APPLIANCE=false;
			$logo="logo-kav.gif";
			$ProductName="Web Securize";
			$link_company_name="http://www.lemnia.com";
			$OEM_CompanyName="LEMNIA SAS";
	}
	if($users->LANWANSAT){
		$LOCK_TEMPLATE=true;
		$users->SQUID_APPLIANCE=false;
		$template="LanWanSAT";
		$users->SAMBA_APPLIANCE=false;
		$logo="logo-kav.gif";
		$ProductName="LanWanSAT Proxy";
		$link_company_name="http://lanwansan.synology.me/wordpress/";
		$OEM_CompanyName="LanWanSan";
		
	}
	
	if($users->BAMSIGHT){
		$LOCK_TEMPLATE=true;
		$users->SQUID_APPLIANCE=false;
		$template="BamSight";
		$users->SAMBA_APPLIANCE=false;
		$logo="logo-kav.gif";
		$ProductName="BamSight";
		$link_company_name="http://www.networksparis.com/";
		$OEM_CompanyName="NetworksParis";
	}
	
	
	$LinkColor="white";
	if(is_file("/usr/share/artica-postfix/ressources/templates/$template/logon-link-color.conf")){
		$LinkColor=trim(@file_get_contents("/usr/share/artica-postfix/ressources/templates/$template/logon-link-color.conf"));
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "<H1>template=$template line ".__LINE__."</H1>";}
	
	if($users->SQUID_APPLIANCE){
		if(!$users->KASPERSKY_WEB_APPLIANCE){
			if($GLOBALS["VERBOSE"]){echo "<div style='background-color:$LinkColor;color:black'>".__LINE__.": SQUID_APPLIANCE DETECTED</div>\n";}
			$template="Squid";
		}
	}
	
	if(!$LOCK_TEMPLATE){
		if($users->LOAD_BALANCE_APPLIANCE){$template="LoadBalance";}
		if($users->HAPRROXY_APPLIANCE){$template="LoadBalance";}
		if($users->WEBSTATS_APPLIANCE){$template="WebStats";}
		if($users->STATS_APPLIANCE){$template="WebStats";}
		if($users->GATEWAY_APPLIANCE){$template="Gateway";$TEMPLATE_INDEX="logon.html";}
		if($users->WORDPRESS_APPLIANCE){$template="Wordpress";}
		if($AsCategoriesAppliance==1){$template="UfdbCat";}
		if($AsMetaServer==1){$template="MetaServer";}
		
	}
	
	
	


	if($template==null){
		if($users->SQUID_INSTALLED){
			if(!$users->POSTFIX_INSTALLED){
				if(!$users->SAMBA_INSTALLED){
					$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
					if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
					if($SQUIDEnable==1){$template="Squid";}
					
				}
			}
		}
	}
	
	if($template==null){
		if($users->POSTFIX_INSTALLED){
			if($users->cyrus_imapd_installed){$template="Postfix";$TEMPLATE_INDEX="logon.html";}
			if($users->ZARAFA_INSTALLED){$template="zarafa";$TEMPLATE_INDEX="logon.html";}
		}
	}
		
	
	if($template==null){
		if($users->POSTFIX_INSTALLED){
			if(!$users->SQUID_INSTALLED){
				if(!$users->SAMBA_INSTALLED){
					$template="Postfix";
					$TEMPLATE_INDEX="logon.html";
				}
			}
		}
	}
	
	if(trim($template)==null){if($users->SAMBA_APPLIANCE){$template="Samba";}}
	if(trim($template)==null){if($WordPressInstalled==1){$template="Wordpress";}}
	if(trim($template)==null){if($users->SQUID_INSTALLED){$template="Squid";}}
	if(trim($template)==null){if($users->SAMBA_INSTALLED){$template="Samba";}}
	if(trim($template)==null){if($users->APACHE_INSTALLED){$template="Apache";}}
	
	if($template=="Squid"){
		if($SquidActHasReverse==1){$TEMPLATE_INDEX="logonr.html";}
		if($AsSquidLoadBalancer==1){$TEMPLATE_INDEX="logonb.html";}
		if($MikrotikTransparent==1){$TEMPLATE_INDEX="logon_mikrotik.html";}
		
	}
	if($template<>null){$_SESSION["FORCED_TEMPLATE"]=$template;}
	
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$q=new mysql_squid_builder();
		$tpl2=new templates();
		$sql="SELECT COUNT( * ) AS tcount FROM personal_categories WHERE PublicMode=1";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$ligneCatz=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligneCatz["tcount"]>0){
			$categoriesPublic="<span style='color:$LinkColor'>&nbsp;|&nbsp;</span>
			<a href='public.categories.personnal.php' style='color:$LinkColor'>".$tpl2->_ENGINE_parse_body("{categories}")."</a>&nbsp;";
		}
	}
	

	
	$js[]="<!-- template=$template line ".__LINE__." -->";
	$tpl2=new templates();
	if($template<>null){
	
		$jquery=null;
		$_SESSION["FORCED_TEMPLATE"]=$template;
		
		include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
		if($GLOBALS["VERBOSE"]){echo "new pagebuilder() line ".__LINE__."</H1>";}
		$p=new pagebuilder();
		$p->SetTemplateMemory($template);
		if(is_file("ressources/templates/$template/$TEMPLATE_INDEX"));
		$tpl=@file_get_contents("ressources/templates/$template/$TEMPLATE_INDEX");
		
		if($GLOBALS["VERBOSE"]){echo "<H1>ressources/templates/$template/$TEMPLATE_INDEX (".strlen($tpl).") length line ".__LINE__."</H1>";}
		
		foreach (glob("ressources/templates/$template/css/*.css") as $filename) {
			//$datas=@file_get_contents("$filename");
			//$datas=str_replace("\n", " ", $datas);
			$css[]="<link href=\"/$filename\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" >";;
		}	
		$log[]="<!-- TEMPLATE_INDEX:$TEMPLATE_INDEX -->";
		
		if($GLOBALS["VERBOSE"]){echo "Scanning ressources/templates/$template/js/*.js".__LINE__."<br>\n";}
		foreach (glob("ressources/templates/$template/js/*.js") as $filename) {
			$filename=basename($filename);
			
			if(preg_match("#^jquery-([0-9\.]+)\.min\.js#", $filename)){
				$log[]="<!-- jquery = $filename -->";
				$jquery=$filename;
				continue;}
			$js[]="<script type=\"text/javascript\" src=\"/ressources/templates/$template/js/$filename\"></script>";
			$log[]="<!-- $filename -->";
		}
		
		
		$lang2Link="<a href=\"javascript:blur();\" OnClick=\"javascript:PopupLogonLang()\" style='color:$LinkColor !important'>{language}</a>";
		if(trim($GLOBALS["FixedLanguage"])<>null){$lang2Link=null;}
		
		
		if($jquery<>null){$jquery="<script type=\"text/javascript\" src=\"/ressources/templates/$template/js/$jquery\"></script>";}
		$jslogon="<script type=\"text/javascript\" src=\"logon.php?start=yes\"></script>";
		if($ProductName<>null){$ProductName="<input type='hidden' id='change-artica-name' value='$ProductName'>";}
		
		$jsArtica=$p->jsArtica($template);
		$js[]="<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.reject.js\"></script>";
		$css[]="<link href=\"/css/jquery.reject.css\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" >";
		
		$sock=new sockets();
		$TITLE_RESSOURCE="ressources/templates/$template/TITLE";
		$favicon=$p->favicon($template);
		if($GLOBALS["VERBOSE"]){echo "replace tokens line:".__LINE__."<br>\n";}
		if(is_file($TITLE_RESSOURCE)){$title=@file_get_contents($TITLE_RESSOURCE);$title=str_replace("%server", $users->hostname, $title);}else{$title=$users->hostname;}
		
		
		$FPM=null;
		if(method_exists("templates","parsePHPModules")){
			$modules=$tpl2->parsePHPModules();
			$PHPVERSION=null;
			if(isset($modules["Core"]["PHP Version"])){
				if(preg_match("#([0-9\.]+)#", $modules["Core"]["PHP Version"],$re)){
					$PHPVERSION="&nbsp;|&nbsp;PHP v.{$re[1]}";
				}
			}
			
			if(isset($modules["cgi-fcgi"]["php-fpm"])){
				$FPM=" (PHP-FPM)";
			}
		}
		
		$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
		$UnlockCompanyName=$sock->GET_INFO("UnlockCompanyName");
		if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
		
		
		$roundcube_uri=roundcube_uri($users);
		if($roundcube_uri<>null){
			$miniadm="<span style='color:$LinkColor !important'>&nbsp;|&nbsp;</span><a href=\"$roundcube_uri\" style='color:$LinkColor;text-decoration:underline'>WebMail<a>&nbsp;";
		}
		
		
		
		if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
		$company_name=$WizardSavedSettings["company_name"];		
		if($UnlockCompanyName<>null){$company_name=$UnlockCompanyName;}
		
		
		if($company_name<>null){
				$company_name="<center style='margin:5px;font-size:16px;padding:5px;
				border-top:1px solid $LinkColor;border-bottom:1px solid $LinkColor'>-&nbsp;$company_name&nbsp;-</center>";
		}
		
		$company_name="<center style='margin:5px;font-size:12px;padding:5px;'>-&nbsp;$hostname&nbsp;-</center>$company_name";
		
		
		$WEBSEVERV=null;
		
		if(isset($_SERVER["SERVER_SOFTWARE"])){
			if(preg_match("#Apache\/([0-9\.]+)#", $_SERVER["SERVER_SOFTWARE"],$re)){
				$WEBSEVERV="&nbsp;|&nbsp;Apache v{$re[1]}";
			}
			
			if(preg_match("#lighttpd\/([0-9\.]+)#", $_SERVER["SERVER_SOFTWARE"],$re)){
				$WEBSEVERV="&nbsp;|&nbsp;LigHttpd v{$re[1]}";
			}			
			
		}
		
		if($users->SQUID_INSTALLED){
			if($SQUIDEnable==1){
				if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;color:black'>".__LINE__.": SQUID INSTALLED</div>\n";}
				$userslogs="<span style='color:$LinkColor'>&nbsp;|&nbsp;</span><a href='squid.access-sql.php' style='color:$LinkColor'>Proxy requests</a>&nbsp;";
				
				$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
				$EnableMaintenanceHotSpot=intval($sock->GET_INFO("EnableMaintenanceHotSpot"));
				
				
				if($EnableArticaHotSpot==1){
					if($EnableMaintenanceHotSpot==1){
						$userslogs="<span style='color:$LinkColor'>&nbsp;|&nbsp;</span><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.webauth.maintenance.php',true);\" style='color:$LinkColor'>HotSpot</a>&nbsp;$userslogs";
						
					}
					
				}
				
				
				$EnableSquidUrgencyPublic=$sock->GET_INFO("EnableSquidUrgencyPublic");
				if(!is_numeric($EnableSquidUrgencyPublic)){$EnableSquidUrgencyPublic=0;}
				if($EnableSquidUrgencyPublic==1){
					$urgency_mode=$tpl2->_ENGINE_parse_body("{urgency_mode}");
					$userslogs="<span style='color:$LinkColor'>&nbsp;|&nbsp;</span><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php',true);\" style='color:$LinkColor'>$urgency_mode</a>&nbsp;$userslogs";
				}
	
				if($SSlBumpAllowLogon==1){
					if(is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
						$certificate=$tpl2->_ENGINE_parse_body("{certificate}");
						$sslcert="<span style='color:$LinkColor'>&nbsp;|&nbsp;</span>
								<a href='ressources/squid/certificate.der' style='color:$LinkColor'>
						$certificate</a>&nbsp;
								";
					}
					
				}
			}
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;color:$LinkColor'>".__LINE__.": SQUID !!NOT!! INSTALLED</div>\n";}
		}
		
		
		if($users->KAV4PROXY_INSTALLED){
			$WEBSEVERV="&nbsp;|&nbsp;Kav4Proxy v{$users->KAV4PROXY_VERSION}";
			
		}


		
		$ARTICAVER=@file_get_contents("VERSION").$WEBSEVERV.$PHPVERSION.$FPM;
		

		
		$tpl=str_replace("{COPYRIGHT}","$lang2Link$miniadm$userslogs$sslcert$categoriesPublic<br>{$company_name}Copyright 2003 - ". date('Y')."&nbsp;<a href=\"$link_company_name\" style='color:$LinkColor'>$OEM_CompanyName</a>",$tpl);
		$tpl=str_replace("{copy-right}","$lang2Link$miniadm$userslogs$sslcert$categoriesPublic<br>{$company_name}Copyright 2003 - ". date('Y')."&nbsp;<a href=\"$link_company_name\" style='color:$LinkColor'>$OEM_CompanyName</a>",$tpl);
		$tpl=str_replace("{TEMPLATE_HEAD}","<!-- HEAD TITLE: $TITLE_RESSOURCE -->\n$favicon\n$jquery\n$jsArtica\n". @implode("\n", $js)."\n$jslogon\n".@implode("\n", $css)."\n".@implode("\n", $log), $tpl);
		$tpl=str_replace("{ARTICA_VERSION}",$ARTICAVER,$tpl);
		
		
		if($SQUIDEnable==1){
			$tpl=str_replace("{SQUID_VERSION}",$users->SQUID_VERSION,$tpl);
		}else{
			$tpl=str_replace("{SQUID_VERSION}",null,$tpl);
		}
		$tpl=str_replace("{POSTFIX_VERSION}",$users->POSTFIX_VERSION,$tpl);
		$tpl=str_replace("{SAMBA_VERSION}",$users->SAMBA_VERSION,$tpl);
		$tpl=str_replace("{CROSSROADS_VERSION}",$users->CROSSROADS_VERSION,$tpl);
		$tpl=str_replace("{APACHE_VERSION}",$users->APACHE_VERSION,$tpl);
		

		
		
		
		if(!function_exists("ldap_connect")){
			$tpl=str_replace("{LOGON_BUTTON}","<span id='YouCanAnimateIt'></span><script>Loadjs('$page?ldap_connect=yes');</script>",$tpl);
			
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "p->YahooBody() line:".__LINE__."<br>\n";}
		$tpl=str_replace("{TEMPLATE_BODY_YAHOO}",$p->YahooBody(),$tpl);
		if(trim($GLOBALS["FixedLanguage"])==null){
			$tpl=str_replace("{TEMPLATE_LANG_LINK}","<span id='llang-select'></span><script>LoadAjaxTiny('llang-select','$page?TEMPLATE_LANG_LINK=yes')</script>",$tpl);
		}else{
			$tpl=str_replace("{TEMPLATE_LANG_LINK}",null,$tpl);
		}
		
		
		
		$tpl=str_replace("{artica_username}",$_GET["MEM_USERNAME"],$tpl);
		$reject_browser_automation=null;
		if(isset($_GET["automation"])){$reject_browser_automation="&automation=yes";}
		
		
		$tpl=str_replace("{LOGON_BUTTON}","<span id='YouCanAnimateIt'></span>
			<script>Loadjs('$page?reject-browser=yes$reject_browser_automation');</script><input type='hidden' id='template' value='$template'>$ProductName".button("{login}", "SendLogonStart()","18px"),$tpl);
		$tpl=str_replace("{TEMPLATE_TITLE_HEAD}",$title,$tpl);
		
			
		
		if(strpos($tpl,"{ZARAFA_VERSION")>0){
			$sock=new sockets();
			$tpl=str_replace("{ZARAFA_VERSION}",$sock->getFrameWork("zarafa.php?getversion=yes"),$tpl);
			
		}

		if(trim($GLOBALS["FixedLanguage"])==null){$tpl2->language=$DetectedLanguage;}
		if($GLOBALS["VERBOSE"]){echo "Langage $tpl2->language line:".__LINE__."<br>\n";}
		
		$tpl=str_replace("User name",$tpl2->_ENGINE_parse_body("{username2}"),$tpl);
		$tpl=str_replace("Password",$tpl2->_ENGINE_parse_body("{password}"),$tpl);
	
		
		if($GLOBALS["VERBOSE"]){echo "Success return form ". strlen($tpl)." bytes lenght<br>\n";}
		if($GLOBALS["VERBOSE"]){echo "Success return complete page line ".__LINE__."<br>\n";}
		return $tpl2->_ENGINE_parse_body($tpl)."<script>//LockPage();</script>";
		
	}
	
	
	
$html="<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
	<title></title>
	<meta name='keywords' content=''>
	<meta name='description' content=\"\">
	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=EmulateIE7\" />
	<link href='css/styles_main.css'    rel=\"styleSheet\"  type='text/css' />

	<link href='css/styles_header.css'  rel=\"styleSheet\"  type='text/css' />
	<link href='css/styles_middle.css'  rel=\"styleSheet\"  type='text/css' />
	<link href='css/styles_tables.css'  rel=\"styleSheet\"  type='text/css' />
	<link href=\"css/styles_rounded.css\" rel=\"stylesheet\"  type=\"text/css\" />
	<!--[if lt IE 7]>
	<link rel='stylesheet' type='text/css' href='css/styles_ie.css' />
	<![endif]-->
	<!--[if IE 7]>
	<link rel='stylesheet' type='text/css' href='css/styles_ie7.css' />
	<![endif]-->
		<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"ressources/templates/default/contact.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" rel=\"styleSheet\"  href=\"ressources/templates/default/menus_top.css\" />
		<link href=\"css/calendar.css\" rel=\"stylesheet\" type=\"text/css\">
		<link href=\"js/jqueryFileTree.css\" rel=\"stylesheet\" type=\"text/css\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/uploadify.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/artica-theme/jquery-ui-1.7.2.custom.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/jquery.jgrowl.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/jquery.cluetip.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/jquery.treeview.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"css/thickbox.css\" media=\"screen\"/>
		<div id='PopUpInfos' style='position:absolute'></div>
		<div id='find' style='position:absolute'></div>
		<script type=\"text/javascript\" language=\"javascript\" src=\"XHRConnection.js\"></script>
		<script type=\"text/javascript\" language=\"JavaScript\" src=\"mouse.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"default.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/cookies.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery-1.6.1.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jqueryFileTree.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.easing.1.3.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery-ui-1.8.custom.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/thickbox-compressed.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.simplemodal-1.3.3.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.jgrowl_minimized.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.cluetip.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.blockUI.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"js/jquery.treeview.min.js\"></script>
		<script type='text/javascript' language='javascript' src='/js/jquery.uilock.min.js'></script>
		<script type='text/javascript' language='javascript' src='/js/jquery.blockUI.js'></script>  
		<!-- js Artica  -->


</head>
<body>
<!-- LINE ".__LINE__." -->		
<center>
<div style=\"width:900px;background-image:url(/css/images/$logo_bg);background-repeat:repeat-x;background-position:center top;margin:0px;padding:0px;\">
	<table style=\"width:100%;margin:0px;padding:0px;border:0px;\">
		<tr>
		    <td valign=\"top\" style='padding:0px;margin:0px;border:0px;padding-top:24px'>
			<div style=\"height:72px\">
				<table style=\"padding:0px;margin:0px;border:0px;margin-left:-6px;\">
				<tr>
			   		<td style='padding:0px;border:0px;' valign=\"top\" align=\"left\">
						
							<table style=\"margin:0px;border:0px;padding:0px;\">
							<tr>
			 				<td style=\"margin:0px;padding:0px;background-color:$bg_color\" width=\"160px\">

								<img src='/css/images/$logo' style=\"margin:0px;padding:0px;\">
							</td>
							<td style=\"margin:0px;padding:0px;\" valign=\"middle\">
								<div style=\"margin-top:-7px;padding-left:5px\"> </div>
							</td>
							<td style=\"margin:0px;padding:0px;border:0px solid black\" valign=\"middle\" align='right' width=50%>
								
							</td>
							</tr>

							</table>
						
					  </td>
				</tr>
				<tr>
				  <td style='height:25px'>
					<div id='menus_2'><ul></ul></div id='menus_2'>
				 </td>
				</tr>
				</table>

		</div>
		     </td>
		  
			
		 
	    	</tr>
		<tr>
		<td valign=\"top\" colspan=2 style=\"margin:0px;padding:0px;padding-top:4px;background-color:white;\">	
<div id='middle'>
	<div id='content' style='background-color:white;'>
		<table style='width:100%'>
			<tr>

				<td valign='top' style='padding:0px;margin:0px;width:150px'>
					
				</td>
				<td valign='top' style='padding-left:3px'>
					<div id='template_users_menus'></div>
					<div id='BodyContentTabs'></div>
						<div id='BodyContent'>
							<h1 id='template_title'></h1>
							<!-- content -->
							

<script>
function SaveSession(){
	var template=document.getElementById('template').value;
	var lang=document.getElementById('lang').value;
	Set_Cookie('artica-template', template, '3600', '/', '', '');
	Set_Cookie('artica-language', lang, '3600', '/', '', '');
	var XHR = new XHRConnection();
	XHR.appendData('lang',lang);
	XHR.sendAndLoad('logon.php', 'GET');		
	location.reload();
}

function LoadModal(){
$('#loginform').modal({onOpen: function (dialog) {
	dialog.overlay.fadeIn('slow', function () {
		dialog.container.slideDown('slow', function () {
			dialog.data.fadeIn('slow');
		});
	});
}});



}


</script>


<center>
	<div id='loginform'></div>
</center>
<!-- content end -->
						</div>

				</td>

				<td valign='top'></td>
			</tr>	
	</table>	

	<div class='clearleft'></div>
	<div class='clearright'></div>
	</div id='content'>

</div id='middle'>
</td>
</tr>
<tr>
<td valign='top' align=left colspan=2 >
<div style='background-color:#736e6c;font-size:13px;color:$LinkColor;height:25px;padding:0px;margin:0px;padding-top:5px;
width:900px;text-align:center;margin-left:-5px;margin-bottom:-3px'>

<strong>$ProductName Copyright 2006-". date('Y')."</strong>
</div>
</td>
</tr>
</table>
</div>
</center>
<script>
document.getElementById('loginform').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
Loadjs('logon.php?start=yes',true);</script>
		<div id=\"SetupControl\" style='width:0;height:0'></div>
		<div id=\"dialogS\" style='width:0;height:0'></div> 
		<div id=\"dialogT\" style='width:0;height:0'></div> 
		<div id=\"dialog0\" style='width:0;height:0'></div> 
		<div id=\"dialog1\" style='width:0;height:0'></div>
		<div id=\"dialog2\" style='width:0;height:0'></div> 
		<div id=\"dialog3\" style='width:0;height:0'></div>
		<div id=\"dialog4\" style='width:0;height:0'></div>
		<div id=\"dialog5\" style='width:0;height:0'></div>
		<div id=\"dialog6\" style='width:0;height:0'></div>
		<div id=\"YahooUser\" style='width:0;height:0'></div>
		<div id=\"logsWatcher\" style='width:0;height:0'></div>
		<div id=\"WinORG\" style='width:0;height:0'></div>
		<div id=\"WinORG2\" style='width:0;height:0'></div>
		<div id=\"RTMMail\" style='width:0;height:0'></div>
		<div id=\"Browse\" style='width:0;height:0'></div>
		<div id=\"SearchUser\" style='width:0;height:0'></div>
</body>
</html>";	

return $html;

}

function TEMPLATE_LANG_LINK(){
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$FileCookyLang=$sock->GET_INFO($FileCookyKey);
	$template=null;
	$page=CurrentPageName();
	$html=new htmltools_inc();
	$lang=$html->LanguageArray();
	$MEM_LANG=$_COOKIE["artica-language"];
	if($MEM_LANG==null){$MEM_LANG=$FileCookyLang;}
	if($MEM_LANG==null){$MEM_LANG="en";}
	$title=$tpl->_ENGINE_parse_body("{select_your_language}");
	
	echo "<dl class=\"jgd-dropdown\" id=\"jgd_dd_langs_select\">
			<dt><a href=\"#\" OnClick=\"javascript:PopupLogonLang()\">{$lang["$MEM_LANG"]}<span class=\"value\">$MEM_LANG</span></a></dt>
		</dl>
		
	<script>
		function PopupLogonLang(){
			YahooWin('300','$page?popup-lang=yes','$title');
		}
	
	</script>
	";


}

function TEMPLATE_LANG_POPUP(){
	$page=CurrentPageName();
	$tpl=new templates();
$html="	
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>&nbsp;</th>
	
	</tr>
</thead>
<tbody class='tbody'>";		
	$t=time();
	$htmlT=new htmltools_inc();
	$lang=$htmlT->LanguageArray();
	while (list($num,$val)=each($lang)){	
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:18px'><a href=\"javascript:blur();\" OnClick=\"javascript:SelectMyLanguage('$num')\" style='font-weight:bold'>$val</a></td>
		</tr>
		
		";
	}
	
	$html=$html."</tbody></table>
	<script>
	var X_SelectMyLanguage= function (obj) {
		var results=obj.responseText;
	
	}
	
	
		function SelectMyLanguage(lang){
			Set_Cookie('artica-language', lang, '3600', '/', '', '');
			var XHR = new XHRConnection();
			XHR.appendData('Changelang',lang);
			if(document.getElementById('artica_password')){
				MEM_PASSWORD=document.getElementById('artica_password').value;
			}
			XHR.sendAndLoad('logon.php', 'POST');		
			setTimeout('ReloadThisPage()',800);	
			
		
		}
		
		function ReloadThisPage(){
			var MEM_USERNAME='';
			if(document.getElementById('artica_username')){
				MEM_USERNAME=document.getElementById('artica_username').value;
			}
			window.location.href='logon.php?MEM_USERNAME='+MEM_USERNAME+'&t=$t';
		}
	</script>
	
	";	
	echo $tpl->_ENGINE_parse_body($html);
	#F2FAFD
}

function ldap_connect_error(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{GENERIC_LDAP_ERROR}");
	header("content-type: application/x-javascript");
	echo "YahooSetupControlModalFixedNoclose('650','$page?ldap_connect_popup=yes','$title');";
	
}

function ldap_connect_popup(){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_ldap_connect_function}"))."
	<script>$(\".ui-dialog-titlebar-close\").hide();</script>";
	
}

function reject_browser(){
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$tpl=new templates();
	$sock=new sockets();
	$header=$tpl->javascript_parse_text("{browser_not_supported}");
	$paragraph1=$tpl->javascript_parse_text("{browser_not_supported1}");
	$paragraph2=$tpl->javascript_parse_text("{browser_not_supported2}");
	$WizardNetLeaveUnconfigured=$sock->GET_INFO("WizardNetLeaveUnconfigured");
	if(!is_numeric($WizardNetLeaveUnconfigured)){$WizardNetLeaveUnconfigured==0;}
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	$q=new mysql();
	$countDeNIC=$q->COUNT_ROWS("nics", "artica_backup");
	if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
	$company_name=$WizardSavedSettings["company_name"];
	$UnlockCompanyName=$sock->GET_INFO("UnlockCompanyName");
	if($UnlockCompanyName<>null){$company_name=$UnlockCompanyName;}
	
	
	
	writelogs("NICS = $countDeNIC WizardSavedSettingsSend=$WizardSavedSettingsSend count:".count($WizardSavedSettings),__FUNCTION__,__FILE__,__LINE__);
	
	if($company_name==null){
				$link="wizard.install.php?setup-1=yes";
				if(isset($_GET["automation"])){
					$link="wizard.install.php?automation=yes";
				}
		
		
				$wizard="
						$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/blurps.css' type='text/css' media='screen' />\"));
						$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/styles_forms.css' type='text/css' media='screen' />\"));
						$('.footer').remove();
						document.getElementById('content').style.width='900px';
						document.getElementById('content').style.height='690px';
						document.getElementById('content').style.marginLeft='-150px';
						document.getElementById('content').style.backgroundColor='white';
						document.getElementById('content').style.backgroundImage='none';
						document.getElementById('content').style.padding='10px';
						document.getElementById('content').style.borderRadius = '5px';
						document.getElementById('content').style.MozBorderRadius = '5px';				
						LoadAjax('content','$link');";
			}
		
	
	
	if($wizard==null){
		if($_COOKIE["mem-logon-user"]<>null){
			$fillUsername="
			var mem_logon_user='{$_COOKIE["mem-logon-user"]}';
			if(document.getElementById('artica_username')){
				document.getElementById('artica_username').value=mem_logon_user;
				if(document.getElementById('artica_password')){
					document.getElementById('artica_password').focus();
				}
			}
			";
		}
		
	}
	
	
echo "
function StartBrowserLoc(){
	$(document).ready(function(){
	    $.reject({  
	        reject: {msie5: true, msie6: true,msie7:true,msie8:true},
	        header: '$header',  
	        paragraph1: '$paragraph1', // Paragraph 1  
	        paragraph2: '$paragraph2', // Paragraph 2  
	        close: false,
	        imagePath: './img/',
	        overlayOpacity: 1,
	        display: ['firefox','chrome','opera','safari']
	    	});
		});
	}
$wizard
StartBrowserLoc();
$fillUsername
";
}
function roundcube_uri($users){
	
	if(!$users->roundcube_installed){return null;}
	$sock=new sockets();
	$RoundCubeHTTPEngineEnabled=intval($sock->GET_INFO("RoundCubeHTTPEngineEnabled"));
	if($RoundCubeHTTPEngineEnabled==0){return null;}
	$RoundCubeUserLink=$sock->GET_INFO("RoundCubeUserLink");
	if($RoundCubeUserLink<>null){return $RoundCubeUserLink;}
	
	
	$RoundCubeHTTPSPort=intval($sock->GET_INFO("RoundCubeHTTPSPort"));
	$RoundCubeHTTPPort=intval($sock->GET_INFO("RoundCubeHTTPPort"));
	if($RoundCubeHTTPSPort==0){$RoundCubeHTTPSPort=449;}
	if($RoundCubeHTTPPort==0){$RoundCubeHTTPPort=8888;}
	$RoundCubeUseSSL=intval($sock->GET_INFO("RoundCubeUseSSL"));
	
	
	$RDCUBE_proto="http";
	$RDCUBE_port=$RoundCubeHTTPPort;
	
	if($RoundCubeUseSSL==1){
		$RDCUBE_proto="https";
		$RDCUBE_port=$RoundCubeHTTPSPort;
	}
		
	$uri="$RDCUBE_proto://{$_SERVER["SERVER_NAME"]}:$RDCUBE_port";
	$sock->SET_INFO("RoundCubeUserLink",$uri);
	return $uri;
	
}

function FATAL_ERROR_SHOW_128_NO_IE(){
	$tpl=new templates();
	
	$NOIEPLEASE_TEXT=$tpl->_ENGINE_parse_body("{NOIEPLEASE_TEXT}");
	$ie_not_really_compatible=$tpl->_ENGINE_parse_body("{ie_not_really_compatible}");
	$incompatible_browser=$tpl->_ENGINE_parse_body("{incompatible_browser}");
	$sorry_ie=$tpl->_ENGINE_parse_body("{sorry_ie}");
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	$f[]="<head>";
	$f[]="";
	$f[]="<title>$incompatible_browser</title>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function blur(){ }";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"#8c1919\";";
	$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            #FFFFFF; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      Arial; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: Arial;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\2639\";}";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: Arial; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: Arial; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]=".important{";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: Arial; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }    ";
	$f[]="p {";
	$f[]="        font-size: 12pt; ";
	$f[]="        font-family: Arial; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }    ";
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: #FFFFFF; ";
	$f[]="        font-family: Arial; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: #FFFFFF; ";
	$f[]="    }";
	$f[]="			";
	$f[]="			";
	$f[]=".Button2014-lg {";
	$f[]="	border-radius: 6px 6px 6px 6px;";
	$f[]="	-moz-border-radius: 6px 6px 6px 6px;";
	$f[]="	-khtml-border-radius: 6px 6px 6px 6px;";
	$f[]="	-webkit-border-radius: 6px 6px 6px 6px;";
	$f[]="	font-size: 18px;";
	$f[]="	line-height: 1.33;";
	$f[]="	padding: 10px 16px;";
	$f[]="}";
	$f[]=".Button2014-success {";
	$f[]="	background-color: #625FFD;";
	$f[]="	border-color: #000000;";
	$f[]="	color: #FFFFFF;";
	$f[]="}";
	$f[]=".Button2014 {";
	$f[]="	-moz-user-select: none;";
	$f[]="	border: 1px solid transparent;";
	$f[]="	border-radius: 4px 4px 4px 4px;";
	$f[]="	cursor: pointer;";
	$f[]="	display: inline-block;";
	$f[]="	font-size: 22px;";
	$f[]="	font-weight: normal;";
	$f[]="	line-height: 1.42857;";
	$f[]="	margin-bottom: 0;";
	$f[]="	padding: 6px 22px;";
	$f[]="	text-align: center;";
	$f[]="	vertical-align: middle;";
	$f[]="	white-space: nowrap;";
	$f[]="	font-family: Arial;";
	$f[]="}";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <h2>$incompatible_browser</h2>    ";
	$f[]="    <p>$sorry_ie</p>";
	$f[]="    <h3>Microsoft Internet Explorer</h3>";
	
	$f[]="    ";
	$f[]="    <div id=\"info\"><p>&nbsp;</p>";
	$f[]="    <p>$NOIEPLEASE_TEXT<p>&nbsp;</p><p>$ie_not_really_compatible</p>";
	
	$f[]="<p></p>";

	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	echo @implode("\n", $f);
	
}


?>