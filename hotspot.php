<?php
session_save_path('/home/artica/hotspot/sessions');
$GLOBALS["CACHEDIR"]="/home/artica/hotspot/caches";
$GLOBALS["HOTSPOT_DEBUG"]=false;
ini_set('session.gc_probability', 1);
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/artica-wifidog.log");
if(isset($_GET["css"])){css();exit;}

//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$GLOBALS["HOTSPOT_DEBUG"]=false;
$HotSpotArticaDebug=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotArticaDebug"));
if($HotSpotArticaDebug==1){$GLOBALS["HOTSPOT_DEBUG"]=true;}


wifidog_load_classes();



if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	
}

if($argv[1]=="--templates"){wifidog_templates();die();}
if($GLOBALS["HOTSPOT_DEBUG"]){while (list ($num, $ligne) = each ($_REQUEST) ){$URIZ[]="$num=$ligne";}wifidog_logs("Receive ".@implode(";", $URIZ));}

if(isset($_GET["gentokens"])){$GLOBALS["VERBOSE"]=true;print_r(generateToken_retrieve());die();}
if(isset($_POST["wifidog-terms"])){wifidog_login();exit;}
if(isset($_POST["confirm-password"])){wifidog_password_perform();exit;}
if(isset($_POST["register-recover"])){wifidog_recover_perform();exit;}
if(isset($_POST["register-member"])){wifidog_register_perform();exit;}
if(isset($_POST["register-sms"])){wifidog_register_sms_perform();exit;}
if(isset($_GET["wifidog-login"])){wifidog_login();exit;}
if(isset($_GET["wifidog-ping"])){wifidog_pong();exit;}
if(isset($_GET["wifidog-portal"])){wifidog_portal();exit;}
if(isset($_GET["wifidog-auth"])){wifidog_auth();exit;}
if(isset($_GET["wifidog-register"])){wifidog_register();exit;}
if(isset($_GET["wifidog-recover"])){wifidog_recover();exit;}
if(isset($_GET["wifidog-password"])){wifidog_password();exit;}
if(isset($_GET["wifidog-confirm"])){wifidog_confirm();exit;}
if(isset($_GET["wifidog-css"])){wifidog_css();exit;}

if(isset($_POST["username"])){wifidog_authenticate();exit;}


if(isset($_GET["css"])){css();exit;}

if(isset($_GET["endusers"])){endusers_load();exit;}
if(isset($_GET["jsload"])){js_load();exit;}
if(isset($_GET["imgload"])){imgload();exit;}

session_start();
if(isset($_POST["register-password"])){register_save();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tweaks"])){tweaks();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["popup"])){popup();exit;}

none_page();

function wifidog_pong(){
	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		while (list ($num, $ligne) = each ($_REQUEST) ){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$num -> $ligne",__FUNCTION__,__LINE__);}
		}
	}
	
	
	$q=new mysql_hotspot();
	$MAIN=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
	if(count($MAIN["SESSIONS"])>0){
		$MySQLSessions=$q->COUNT_ROWS("hotspot_sessions");
		wifidog_logs("wifidog_pong:: ".count($MAIN["SESSIONS"]) ."/$MySQLSessions exists");
		if($MySQLSessions==0){
			wifidog_logs("wifidog_pong:: Clean all sessions");
			$sock=new sockets();
			$sock->getFrameWork("hotspot.php?clean-all-sessions=yes");
		}
			
		
	}
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("OK -> PONG dear service, i'm alive...",__FUNCTION__,__LINE__);}
	echo "Pong\r\n";
}


//hotspot.php?wifidog-auth=yes&stage=login&ip=192.168.1.18&mac=00:15:5d:01:09:07&token=289a95d50c49c9ce202e4ee349389703&incoming=0&outgoing=0&gw_id=000C291B3AC4
// http://wiki.gergosnet.com/index.php/Installation%2Bclient%2Bwifidog%2Bsur%2BDebian
// iptables -t nat -I WiFiDog_eth0_WIFI2Internet -i eth0 -m mark --mark 0x2 -p tcp --dport 443 -j REDIRECT --to-port 63924
//https://192.168.1.204:9000/portal/?gw_id=000C291B3AC4



function none_page(){
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];	
	$page=CurrentPageName();
	$tpl=new templates();
	$proto="http";
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Rule: {$_SESSION["WIFIDOG_RULES"]} LOST_LANDING_PAGE=$LOST_LANDING_PAGE",__FUNCTION__,__LINE__);}
	
	
	$text_form="
	<div class=title2>$wifidog_templates->ArticaSplashHotSpotRedirectText:</div>
	<p>$LOST_LANDING_PAGE</p>
	<form>		
	<center>
			<div style='font-size:32px'><center>$LOST_LANDING_PAGE</center></div>
			<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'>
	</center>
	</form>
	
	
	
	";
	if(!preg_match("#^http#", $LOST_LANDING_PAGE)){$LOST_LANDING_PAGE="http://$LOST_LANDING_PAGE";}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"1; URL=$LOST_LANDING_PAGE\">");
	
	
	
	
}


function wifidog_auth(){
	$token=$_GET["token"];
	$ARP=$_GET["mac"];
	$mac=$_GET["mac"];
	$ip=$_GET["ip"];
	$GLOBALS["CONNECTED_IP"]=$ip;
	$GLOBALS["CONNECTED_MAC"]=$mac;
	$stage=$_GET["stage"];
	$token=$_GET["token"];
	$incoming=$_GET["incoming"];
	$outgoing=$_GET["outgoing"];
	$q=new mysql_hotspot();
	///hotspot.php?wifidog-auth=yes&stage=counters&ip=192.168.1.19&mac=00:15:5d:01:09:06&token=token&incoming=7161&outgoing=10255&gw_id=000C29D5571C
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth:: Receiving stage \"$stage\"",__FUNCTION__,__LINE__);}
	
	if($stage=="logout"){
		$q=new mysql_hotspot();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
		$username=$ligne["username"];
		events(1,"$username [LOGOFF] for $mac",null);
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `MAC`='$mac'");
	}
	
	
	if($stage=="counters"){
		$incoming=$incoming/1024; //KB
		$outgoing=$outgoing/1024; // KB
		$incomingMB=round($incoming/1024); //MB
		//mac=00:0c:29:9b:e0:bf
		
		if(wifidog_auth_auto_created($token)){
			wifidog_logs("[TRUE] $token/$ip -> Session is Failed");
			return;
		}
		
		
		if(wifidog_is_end_of_session($token)){
			wifidog_logs("[FALSE] $token/$ip END OF SESSION  Send [0] ERROR CODE");
			events(1,"COUNTER: MAC: $mac, Token $token Session: [End-Of-Life] (L:".__LINE__.")",null);
			echo "Auth: 0\n";
			echo "Messages: No session saved\n";
			return;
		}		
		
		
		if(wifidog_is_end_of_life($token,$mac)){
			wifidog_logs("[FALSE] $token/$ip END OF LIFE  Send [0] ERROR CODE");
			events(1,"COUNTER: MAC: $mac, Token $token Account: [End-Of-Life] (L:".__LINE__.")",null);
			echo "Auth: 0\n";
			echo "Messages: No session saved\n";
			return;
		}
		
		if($incomingMB>0){
			if(wifidog_is_session_exceed_size($token,$incoming)){
				events(1,"Session exceed size:$incoming [End-Of-Life] (L:".__LINE__.")",null);
				wifidog_logs("[FALSE] $token/$ip exceed session size");
				echo "Auth: 0\n";
				echo "Messages: No session saved\n";
				return;
				
			}
		}
		
		wifidog_logs("[TRUE] $token/$ip Update session values");
		$q->QUERY_SQL("UPDATE hotspot_sessions SET `incoming`='$incoming',`outgoing`='$outgoing',`ipaddr`='$ip' WHERE `md5`='$token'");
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
	
	
	
	if($ligne["logintime"]==0){
		events(1,"An error occurred during the validation process logintime = 0  No session saved [AUTH_ERROR] (L:".__LINE__.")",null);
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth:: * * * logintime = 0  No session saved * * *");}
		echo "Auth: -1\n";
		echo "Messages: No session saved\n";
		return;
	}
/*	0 - AUTH_DENIED - User firewall users are deleted and the user removed.
	6 - AUTH_VALIDATION_FAILED - User email validation timeout has occured and user/firewall is deleted
	1 - AUTH_ALLOWED - User was valid, add firewall rules if not present
	5 - AUTH_VALIDATION - Permit user access to email to get validation email under default rules
	-1 - AUTH_ERROR - An error occurred during the validation process
*/	
	
	
	echo "Auth: 1\n";
	echo "Messages: OK\n";

}

function wifidog_auth_auto_created($token){
	$q=new mysql_hotspot();
	$autocreate=0;
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$token'"));
	$username=$ligne["username"];
	$nextcheck=$ligne["nextcheck"];
	if($ligne["autocreate"]==0){return false;}
	$StillRest=$nextcheck-time();
	$StillRestMin=$StillRest/60;
	wifidog_logs("Session NextCheck in $nextcheck in {$StillRest}s {$StillRestMin}mn");
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_members WHERE `uid`='$username'"));
	if($ligne["uid"]==null){
		wifidog_logs("$username: no existent in database [FAILED] !!!");
		echo "Auth: 0\n";
		echo "Messages: AUTH_DENIED\n";
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		return true;
		
	}
	
	if($ligne["autocreate"]==1){if($ligne["autocreate_confirmed"]==0){ $autocreate=1; } }
	
	if($autocreate==1){
		if($nextcheck>time()){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$username: $nextcheck > ". time()." autocreate=1 and autocreate_confirmed=0 -> Was auto-created by email form, still waiting the confirmation");}
			return false;
		}
	}
	
	
	
	if($ligne["autocreate_confirmed"]==1){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("autocreate_confirmed = 1, keep the session live...",__FUNCTION__,__LINE__);}
		return false;
	}
	
	
	if($autocreate==1){
		wifidog_logs("\"$username\": Remove the account, not confirmed by the link");
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		$q->QUERY_SQL("DELETE FROM hotspot_members WHERE `uid`='$username'");
		echo "Auth: 0\n";
		echo "Messages: AUTH_DENIED\n";
		return true;
	}
	
	$time=time();
	

	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("\"$username\", Token:$token, for $NextCheck {$reste}Mn,checks hotspot_members");}
	
	
	if($time>$nextcheck){
		echo "Auth: -1\n";
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth_auto_created:: \"$username\", Token:$token Messages: AUTH_VALIDATION_FAILED - Timed out $time>$NextCheck");}
		echo "Messages: AUTH_VALIDATION_FAILED\n";
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		return true;
	}
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Messages: AUTH_VALIDATION_FAILED - still wait validation");}
	echo "Auth: 1\n";
	echo "Messages: AUTH_VALIDATION\n";
	return true;
	
}

function wifidog_templates(){
	@file_put_contents("/usr/local/etc/wifidog-msg.html",BuildFullPage(null,"<h2>\$title</h2><p>\$message</p>"));
	
}



function  wifidog_terms($ARP){
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];	
	$page=CurrentPageName();
	$tpl=new templates();
	$cachefile="{$GLOBALS["CACHEDIR"]}/$ARP.terms.html";
	
	if(is_file($cachefile)){echo @file_get_contents("$cachefile");return;}
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	$t=time();
	$accept=$tpl->_ENGINE_parse_body("{accept}");
	$wifidog_templates->TERMS_CONDITIONS=str_replace("\\n", "\n", $wifidog_templates->TERMS_CONDITIONS);
	$wifidog_templates->TERMS_CONDITIONS=str_replace("\\\"", "\"", $wifidog_templates->TERMS_CONDITIONS);
	
	
	$f[]="<p>".$wifidog_templates->char($wifidog_templates->TERMS_EXPLAIN)."</p>";
	$f[]="<form id='wifidogform$t' action=\"$page\" method=\"post\">";
	$f[]="<textarea readonly='yes' style='width:97%;height:450px'>$wifidog_templates->TERMS_CONDITIONS</textarea>";
	$f[]="";
	$f[]="<input type='hidden' name='wifidog-terms' value='yes'>";
	$f[]="$HiddenFields";
	
	
	
	
	$f[]="<p class=ButtonCell style='text-align:right'><a data-loading-text=\"Chargement...\"
	style=\"text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
	onclick=\"javascript:document.forms['wifidogform$t'].submit();\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$accept&nbsp;&raquo;</a></p>";
	
	$f[]="</form></div>";
	$text_form=@implode("\n", $f);
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("->BuildFullPage(".strlen($text_form).")",__FUNCTION__,__LINE__);}
	$html=BuildFullPage($text_form);
	@file_put_contents($cachefile, $html);
}


function  wifidog_login($error=null){
	header('Content-Type: text/html; charset=iso-8859-1');
	$REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
	
	session_start();
	$ipClass=new IP();
	
	$USE_TERMS_ACCEPTED=false;
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$cachefile="{$GLOBALS["CACHEDIR"]}/$ARP.".md5($error.serialize($_REQUEST)).".".__FUNCTION__.".html";
	
	if(isset($_POST["wifidog-terms"])){$_SESSION["USE_TERMS_ACCEPTED"]=true;$USE_TERMS_ACCEPTED=true;}
	if(isset($_SESSION["USE_TERMS_ACCEPTED"])){$USE_TERMS_ACCEPTED=true;}
	if(isset($_GET["clear"])){unset($_SESSION["WIFIDOG_RULES"]);}
	
	if(!$ipClass->IsvalidMAC($ARP)){
		$CacheError1="{$GLOBALS["CACHEDIR"]}/hostspot_network_incompatible.html";
		if(!is_file($CacheError1)){
			$tpl=new templates();
			$text_form=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{hostspot_network_incompatible}"));
			@file_put_contents($CacheError1, $text_form);
		}else{
			$text_form=@file_get_contents($CacheError1);
		}
	}
	
	
	if(!isset($_REQUEST["token"])){$_REQUEST["token"]=generateToken($ARP);}
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
		
	$wifidog_templates=new wifidog_rules();
	$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		wifidog_logs("Rule number {$_SESSION["WIFIDOG_RULES"]}",__FUNCTION__,__LINE__);
	}
	
	
	$wifidog_rule=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$ArticaHotSpotNowPassword=intval($wifidog_rule->GET_INFO("ArticaHotSpotNowPassword"));
	$ENABLED_REDIRECT_LOGIN=intval($wifidog_rule->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	$ENABLED_SMTP=intval($wifidog_rule->GET_INFO("ENABLED_SMTP"));
	$ENABLED_AUTO_LOGIN=intval($wifidog_rule->GET_INFO("ENABLED_AUTO_LOGIN"));
	$USE_TERMS=intval($wifidog_rule->GET_INFO("USE_TERMS"));
	$ALLOW_RECOVER_PASS=intval($wifidog_rule->GET_INFO("ALLOW_RECOVER_PASS"));
	$DO_NOT_AUTENTICATE=intval($wifidog_rule->GET_INFO("DO_NOT_AUTENTICATE"));
	$SMS_REGISTER=intval($wifidog_rule->GET_INFO("SMS_REGISTER"));
	$BOUNCE_AUTH=intval($wifidog_rule->GET_INFO("BOUNCE_AUTH"));
	
	
	
	if($BOUNCE_AUTH==1){
		if($ARP<>null){
			$CacheARP="{$GLOBALS["CACHEDIR"]}/$ARP";
			$ligne["uid"]=null;
			if(is_file($CacheARP)){if(file_time_min_Web($CacheARP)>15){@unlink($CacheARP);}}
			
			if(!is_file($CacheARP)){
				$q=new mysql_hotspot();
				$sql="SELECT * FROM hotspot_members WHERE MAC='$ARP'";
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
				$uid=$ligne["uid"];
			}else{
				$ligne["uid"]=@file_get_contents($CacheARP);
			}
			
			if($ligne["uid"]<>null){
				$array["LOGIN"]=$ligne["uid"];
				$array["IPADDR"]=null;
				$array["MAC"]=$ARP;
				$array["ARP"]=$ARP;
				$array["token"]=$_REQUEST["token"];
				$array["ruleid"]=$_SESSION["WIFIDOG_RULES"];
				if(UnLock($array)){wifidog_redirect_after_login();return;}
			}
		}
	}
	
	
	if($USE_TERMS==1){
		if(!$USE_TERMS_ACCEPTED){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("->wifidog_terms()",__FUNCTION__,__LINE__);}
			return wifidog_terms($ARP);
		}
	}
	
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	
	if($SMS_REGISTER==1){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("->wifidog_register_sms()",__FUNCTION__,__LINE__);}
		wifidog_register_sms();
		return;
	}
	
	if($ENABLED_SMTP==1){
		if($ENABLED_REDIRECT_LOGIN==1){
			wifidog_register();
			return;
		}
	}
	
	if(is_file($cachefile)){
		echo @file_get_contents($cachefile);
		return;
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$username=$wifidog_templates->LabelUsername;
	$password=$wifidog_templates->LabelPassword;
	$lost_password_text=$wifidog_templates->LostPasswordLink;
	
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	
	
	$t=time();
	unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
	$url_encoded=urlencode($url);
	if($DO_NOT_AUTENTICATE==1){$ArticaHotSpotNowPassword=1;}
	
	
	
	

	
	$f[]="<p>$wifidog_templates->WelcomeMessage</p>";
	$f[]="<div id='content'>";
	$f[]="";
	$f[]="<form id='wifidogform' action=\"$page\" method=\"post\">";
	$f[]="<input type=\"hidden\" name=\"ruleid\" id=\"ruleid\" value='{$_SESSION["WIFIDOG_RULES"]}'>";
	$f[]="$HiddenFields";
	
	if($wifidog_templates->FORM_HEAD<>null){
		$f[]="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	$f[]="<table style='width:100%'>";
	$f[]="<tr>";
	
	$f[]="<td class=legend>$username:</td>";
	$f[]="<td>
	<input type=\"text\" 
		name=\"username\" 
		id=\"username\"
		value=\"{$_REQUEST["username"]}\" 
		onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
		onblur=\"this.removeAttribute('class');\" 
		OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="</td>";
	$f[]="</tr>";
	
	
	if($ArticaHotSpotNowPassword==0){
	
	$f[]="<tr>";
	$f[]="<td class=legend>$password:</td>";
	$f[]="<td><input type=\"password\" name=\"password\" 
				value=\"{$_REQUEST["password"]}\"
				id=\"password\" onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
				onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="</td>";
	$f[]="</tr>";
	}else{
		$f[]="<input type=\"hidden\" name=\"password\" id=\"password\" value=''>";
	}
	

	
	
	$f[]="<tr><td colspan=2>&nbsp;</td></tr>";
	$f[]="<tr><td colspan=2 align='right' class=ButtonCell>";
	
	if($ENABLED_AUTO_LOGIN==1){
		$f[]="<a data-loading-text=\"Chargement...\"
		style=\"text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
		href=\"$page?wifidog-register=yes&$uriext\">&laquo;&nbsp;$wifidog_templates->RegisterTitle&nbsp;&raquo;</a>";
	}
	
	
	
	$f[]="<a data-loading-text=\"Chargement...\" 
			style=\"text-transform:capitalize\" 
			class=\"Button2014 Button2014-success Button2014-lg\" 
			id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\" 
			onclick=\"javascript:document.forms['wifidogform'].submit();\" 
			href=\"javascript:Blurz()\">&laquo;&nbsp;".$wifidog_templates->char($wifidog_templates->ConnectionButton)."&nbsp;&raquo;</a>";
	
	$f[]="</td>
	</tr>";
	if($ENABLED_SMTP==1){
		if($ALLOW_RECOVER_PASS==1){
			if($ArticaHotSpotNowPassword==0){
				$f[]="<tr><td class=legend colspan=2>";
				$f[]="<a href=\"$page?wifidog-recover=yes&email={$_REQUEST["username"]}&$uriext\">$lost_password_text</a></div>";
				$f[]="</td></tr>";
			}
		}
	}
	
	$f[]="</table>";
	$f[]="			</form>	";
	
	
	$f[]="</div>
	<script>
		function SendLogon$t(e){
			if(!checkEnter(e)){return;}
			document.forms['wifidogform'].submit();
		}
	</script>
	\n";
	
	
	$text_form=@implode("\n", $f);
	

	
	$page=BuildFullPage($text_form,$error);
	@file_put_contents($cachefile, $page);
	echo $page;
}




function checkcreds_AD($ruleid=0){
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	$account_suffix=null;
	$q=new mysql_hotspot();
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_activedirectory WHERE enabled=1 AND ruleid='$ruleid'");
	if(mysql_num_rows($results)==0){return false;}
	
	if(strpos($username, "/")>0){
		$FTR=explode("/",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	if(strpos($username, "\\")>0){
		$FTR=explode("\\",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	if(strpos($username, "@")>0){
		$FTR=explode("@",$username);
		$account_suffix=$FTR[1];
		$username=$FTR[0];
	}
	
	
	
	$username_login = strtoupper($username);
	
	if($account_suffix==null){
		$GLOBALS["AD_ERROR"]="{error_ad_count_suffix}";
		return false;
	}
	
	if($account_suffix<>null){
		$username_login="$username_login@$account_suffix";
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=$ligne["zmd5"];
		$groups=trim($ligne["groups"]);
		$hostname=$ligne["hostname"];

		wifidog_logs("$username_login -> $hostname:389");
	
		if(!checkcreds_AD_ToServer($hostname,$username,$account_suffix,$password)){
			wifidog_logs("$username_login -> $hostname:389 -> failed");
			continue;
		}
		
		if(!checkcreds_ADGroups_ToServer($groups,$hostname,$username,$password,$account_suffix)){
			continue;
		}
		
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("checkcreds_AD {$ligne["hostname"]} return true... in line:".__LINE__);}
		if(checkcreds_AD_ToMemberAD("$username_login",$password,0,$md5)){
			return true;
		}
		
		
		
	}
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs(" ****************** END FUNCTION FAILED ****************** in line:".__LINE__);}
	return false;
}

function checkcreds_AD_ToServer($hostname,$username,$account_suffix,$password){
	$options=array(
	
			'ad_username'=>$username,
			'ad_password'=>$password,
			'recursive_groups'=>true,
			'domain_controllers'=>array($hostname),
			'account_suffix'=>"@{$account_suffix}"
	);
	
	
	
	$adldap = new adLDAP($options);
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname:389, account_suffix = $account_suffix username = $username password=\"$password\"");}
	$adldap->setDomainControllers(array($hostname));
	if(!$adldap->authenticate("$username", $password)){
		if($GLOBALS["HOTSPOT_DEBUG"]){
			wifidog_logs_array($GLOBALS["CLASS_ACTV"]);
			wifidog_logs("$hostname: checkcreds_AD_ToServer Return false... in line:".__LINE__);
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname ****************** FAILED ******************");}
			return false;
		}
	
	}
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname ****************** SUCCESS ******************");}
	return true;
	
}

function checkcreds_ADGroups_ToServer($groups,$hostname,$username,$password,$account_suffix){
	if(strlen($groups)==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_ADGroups_ToServer no defined group in line:".__LINE__);}
		return true;
	}
	$YGroups=array();
	$zGroups=explode("\n",$groups);
	while (list ($num, $ligne) = each ($zGroups) ){
		$ligne=trim(strtolower($ligne));
		if($ligne==null){continue;}
		$YGroups[$ligne]=$ligne;
		wifidog_logs("$hostname: checkcreds_ADGroups_ToServer checks group $ligne in line:".__LINE__);
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_ADGroups_ToServer ".count($YGroups)." in line:".__LINE__);}
	
	if(count($YGroups)==0){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_ADGroups_ToServer no group defined, return true in line:".__LINE__);}
		return true;
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_ADGroups_ToServer checks $username groups in line:".__LINE__);}
	
	$account_suffixZ=explode(".",$account_suffix);
	while (list ($num, $a) = each ($account_suffixZ) ){
		$zsuffix[]="DC=$a";
	}
	
	$suffix=@implode(",", $zsuffix);
	
	$options=array(
			'base_dn'=>$suffix,
			'ad_username'=>$username,
			'ad_password'=>$password,
			'recursive_groups'=>true,
			'domain_controllers'=>array($hostname),
			'account_suffix'=>"@{$account_suffix}"
	);
	
	$adldap = new adLDAP($options);
	$adldap->authenticate("$username", $password);
	wifidog_logs("$hostname: Get groups...".__LINE__);
	$result=$adldap->user()->groups($username);
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs_array($GLOBALS["CLASS_ACTV"]);}
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_ADGroups_ToServer $username in:".count($result)." groups in line:".__LINE__);}
	
	while (list ($num, $group) = each ($result) ){
		$group=trim(strtolower($group));
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_AD checks $group group in line:".__LINE__);}
		if(isset($YGroups[$group])){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$hostname: checkcreds_AD checks $group is OK in line:".__LINE__);}
			return true;
		}
	}
	
	return false;
	
	
}

function checkcreds_AD_ToMemberAD($uid,$password,$ttl,$md5){
	$q=new mysql_hotspot();
	
	
	$sql="SELECT uid,ruleid,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$creationtime=time();
	$password=md5($password);
	
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$ArticaSplashHotSpotEndTime=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	$ENABLED_META_LOGIN=intval($sock->GET_INFO("ENABLED_META_LOGIN"));
	wifidog_logs("Ruleid: {$_SESSION["WIFIDOG_RULES"]} Endtime: $ArticaSplashHotSpotEndTime");
	
	if($ENABLED_META_LOGIN==1){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members_meta` ( `uid` VARCHAR( 128 ) NOT NULL , `creationtime` INT UNSIGNED NOT NULL, PRIMARY KEY ( `uid` ) , INDEX ( `creationtime`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		$sql="INSERT IGNORE INTO `hotspot_members_meta` (uid,creationtime) VALUES ('$uid','".time()."')";
		$q->QUERY_SQL($sql);
	}
	
	
	
	
	if($ligne["uid"]==null){
		$uid=strtolower($uid);
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Create new member $uid,$password,$ttl,$md5 in line:".__LINE__);}
		$sql="INSERT IGNORE INTO hotspot_members (uid,username,ruleid,ttl,sessiontime,password,enabled,creationtime,activedirectory,activedirectorycnx) VALUES
		('$uid','$uid','{$_SESSION["WIFIDOG_RULES"]}','$ArticaSplashHotSpotEndTime','','$password',1,'$creationtime',1,'$md5')";
		$q->QUERY_SQL($sql);
		

		if(!$q->ok){
			wifidog_logs("$q->mysql_error");
			return false;
		}
		return true;
	}
	
	$uid=strtolower($uid);
	$sql="UPDATE hotspot_members SET `uid`='$uid',
	`ruleid`='{$_SESSION["WIFIDOG_RULES"]}',
	`password`='$password',
	`activedirectory`=1,
	`activedirectorycnx`='$md5'
	WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
	return true;
	
	
}


function checkcreds_ldap(){
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	$DEBUG[]="username: $username";

	
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	
	$DEBUG[]="_POST[password]: $password";
	
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	
	$DEBUG[]="userPassword: $userPassword";
	
	$userPasswordMD=md5(trim($userPassword));
	$passwordMD=md5($password);
	if(trim($u->uidNumber)==null){return false;}
		
	
	
	if($passwordMD<>$userPasswordMD){ 
		$DEBUG[]="userPasswordMD: $userPasswordMD";
		$DEBUG[]="passwordMD: $passwordMD";
		$GLOBALS["ERROR"]=$tpl->javascript_parse_text("{bad_password}\n".@implode("\n", $DEBUG),1);
		return false;
	}
		
		return true;
}

function checkcreds_mysql($array,$noauthent=false){
	$q=new mysql_hotspot();
	
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];
	$MAC=$array["MAC"];
	$username=$_POST["username"];
	$HOST=$array["HOST"];
	$CACHE_AUTH=$GLOBALS["CACHE_AUTH"];
	$IPADDR=$array["IPADDR"];
	$md5key=md5(strtolower("$username$IPADDR$MAC$HOST"));
	

	$tpl=new templates();
	$time=time();
	$password=trim($_POST["password"]);
	
	$sql="SELECT * FROM hotspot_members WHERE uid='$username' AND activedirectory=0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if($ligne["uid"]<>null){
		$GLOBALS["CACHE_AUTH"]=$ligne["sessiontime"];
		$GLOBALS["MAX_TIME"]=$ligne["ttl"];
		
	}
	if($noauthent){return;}
	
	if($ligne["enabled"]==0){
		$GLOBALS["ERROR"]=$tpl->javascript_parse_text("{access_to_internet_disabled} ({disabled})");
		return false;
	}
	
	if($ligne["uid"]==null){
		$GLOBALS["LOGS"][]=__FUNCTION__.":: uid is null";
		return false;
	}

	if($ligne["password"]<>$password){
		if($ligne["password"]<>md5($password)){
			$GLOBALS["LOGS"][]=__FUNCTION__.":: $username `password` mismatch expected `{$ligne["password"]}`";
			
			return false;
		}
	}
	
	
	return true;
}


function wifidog_redirect_uri(){
	
	if(isset($_SESSION["HOTSPOT_REDIRECT_URL"])){$url=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	
	if($url==null){
		if(!isset($_SESSION["WIFIDOG_RULES"])){$wifidog_templates=new wifidog_rules(); $_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid; }
		$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
		$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
		$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
		$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
		if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
		if($LANDING_PAGE<>null){return $LANDING_PAGE;}
		return $LOST_LANDING_PAGE;
	}
	
	
	return $url;
}




function wifidog_portal(){
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_portal()");}
	session_start();
	$tpl=new templates();
	$url=wifidog_redirect_uri();
	
	$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
	$idbt=md5(time());
	$ssl_button=null;
	$explain2=null;
	$parse=parse_url($url);
	$hostname=$parse["host"];
	
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
	$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	
	
	$wifidog_build_uri=wifidog_build_uri();
	
	
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$wifidog_templates = new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	if(!preg_match("#http#", $url)){$url="http://$url";}
	$continue_button="<a data-loading-text=\"Chargement...\" 
					style=\"text-transform:capitalize\" 
					class=\"Button2014 Button2014-success Button2014-lg\" 
					id=\"$idbt\" 
					href=\"$url\">&laquo;&nbsp;$continue_to_internet&nbsp;&raquo;</a>";
	
	

	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		while (list ($num, $ligne) = each ($_SESSION) ){
			if(preg_match("#HOTSPOT_#", $num)){
			wifidog_logs("wifidog_portal:: SESSION OF $num = $ligne".__LINE__);
			}
		}
		
	}
	wifidog_logs("LOST_LANDING_PAGE = $LOST_LANDING_PAGE",__FUNCTION__,__LINE__);
	wifidog_logs("LANDING_PAGE      = $LANDING_PAGE",__FUNCTION__,__LINE__);
	wifidog_logs("Rule ID	        = {$_SESSION["WIFIDOG_RULES"]}",__FUNCTION__,__LINE__);
	
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_REGISTER"])){
		$tpl=new templates();
		
		$REGISTER_MAX_TIME=$sock->GET_INFO("REGISTER_MAX_TIME");
		
		$text_form=$wifidog_templates->CONFIRM_MESSAGE;
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		
		
		$html="<form>
		$text_form
		$explain2
		<div style='width:100%;text-align:right'>
		<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</form>";
		
		echo BuildFullPage($html,null);
		return;
		
		
	}
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_RECOVER"])){
		$tpl=new templates();
		$ArticaHotSpotSMTP=SMTP_SETTINGS();
		$REGISTER_MAX_TIME=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
		$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
		$text_form=$ArticaHotSpotSMTP["RECOVER_MESSAGE"];
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		$parse=parse_url($url);
		$hostname=$parse["host"];
		
		$html="<form>
			$text_form
			$explain2
			<div style='width:100%;text-align:right'>
				<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</div>
		</form>";
		
		echo BuildFullPage($html,null);
		return;
		
	}
	
		
	wifidog_logs("wifidog_portal:: buiding redirect to $url in line:".__LINE__);
	$tpl=new templates();
	$sock=new sockets();
	$text_redirecting=$sock->GET_INFO("ArticaSplashHotSpotRedirectText");
	if($text_redirecting==null){$text_redirecting=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}:");}
		
	
	$parse=parse_url($url);
	$host=$parse["host"];
	
	$text_form="
	<form>
	<center>
		<div style='font-size:18px'><center>$text_redirecting<br>$host</center></div>
		<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'>
	</center>
	</form>";
		
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Redirect Client {$_SESSION["HOTSPOT_REDIRECT_MAC"]} to $url");}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"3; URL=$url\">");
		
	
}

function wifidog_authenticate(){
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_authenticate()");}
	$tpl=new templates();
	
	session_start();
	if(!isset($_SESSION["HOTSPOT_REDIRECT_URL"])){$_SESSION["HOTSPOT_REDIRECT_URL"]=$_REQUEST["url"];}
	if(!isset($_SESSION["HOTSPOT_REDIRECT_MAC"])){$_SESSION["HOTSPOT_REDIRECT_MAC"]=$_REQUEST["mac"];}
	
	if(!checkcreds()){
		wifidog_login(
		"<span style='color:#CE0000;font-size:18px'>".
		$tpl->_ENGINE_parse_body("{failed2}: &laquo;{$GLOBALS["ERROR"]}&raquo;"))."</span>";
		return;
	}
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	wifidog_logs("Redirect Token: $token to $gateway_addr:$gw_port");
	header("Location: $redirecturi");
	
	
}

function wifidog_logs_array($array){
	while (list ($num, $ligne) = each ($array) ){wifidog_logs($ligne);}
}

function checkcreds(){
	
	
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$ruleid=$_REQUEST["ruleid"];
	if($ruleid==0){if(isset($_SESSION["WIFIDOG_RULES"])){$ruleid=$_SESSION["WIFIDOG_RULES"];}}
	$MAC=$ARP;
	
	if($EnableIntelCeleron==1){$EnableOpenLDAP=0;}
	
	
	$sock=new wifidog_settings($ruleid);
	$USE_MYSQL=intval($sock->GET_INFO("USE_MYSQL"));
	$USE_ACTIVEDIRECTORY=intval($sock->GET_INFO("USE_ACTIVEDIRECTORY"));
	$DO_NOT_AUTENTICATE=intval($sock->GET_INFO("DO_NOT_AUTENTICATE"));
	wifidog_logs("Verify credentials for $ARP/{$_POST["username"]} Active Directory:$USE_ACTIVEDIRECTORY; Token:$token ruleid:$ruleid",__FUNCTION__,__LINE__);
	
	
	$LOGIN=$_POST["username"];
	$IPADDR=null;
	
	$HOST=gethostbyaddr($IPADDR);
	$URI=$url;
	
	$array["LOGIN"]=$LOGIN;
	$array["IPADDR"]=null;
	$array["MAC"]=$MAC;
	$array["ARP"]=$MAC;
	$array["HOST"]=$HOST;
	$array["token"]=$token;
	$array["ruleid"]=$ruleid;
	if($DO_NOT_AUTENTICATE==1){
		return UnLock($array,true);
	}
	$q=new mysql_hotspot();
	
	$sql="SELECT uid,creationtime,ttl,enabled FROM hotspot_members WHERE uid='$LOGIN'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(trim($ligne["uid"])<>null){
		$enabled=intval($ligne["enabled"]);
		if($enabled==0){
			events(1,"Login failed for $LOGIN/$IPADDR, account locked");
			$GLOBALS["ERROR"]="{your_account_is_disabled}";
			return false;
		}
	}
	
	
	if($USE_MYSQL==0){
		if($USE_ACTIVEDIRECTORY==0){$USE_MYSQL=1;}
	}
	
	
	if($USE_MYSQL==0){
		$q->QUERY_SQL("DELETE FROM hotspot_members WHERE uid='$LOGIN'");
		
	}else{
		
		if(trim($ligne["uid"])<>null){
			if($ligne["enabled"]==0){
				$Created=$q->time_to_date($ligne["creationtime"],true);
				wifidog_logs("checkcreds:: $LOGIN is disabled $Created");
				$GLOBALS["ERROR"]="<strong>$LOGIN</strong> {your_account_is_disabled}<br>{created}:$Created";
				return false;
			}
			$ttl=$ligne["ttl"];
			if($ligne["creationtime"]>0){
				if($ligne["ttl"]>0){
					$EnOfLife = strtotime("+{$ttl} minutes", $ligne["creationtime"]);
					if(time()>$EnOfLife){
						wifidog_logs("checkcreds:: $LOGIN expired - End of Life");
						$GLOBALS["ERROR"]="{accesstime_to_internet_expired}";
						return false;
					}
				}
					
			}
		}
	
	}
	
	
	$auth=false;
	
	
	if($USE_ACTIVEDIRECTORY==1){
		if(checkcreds_AD($ruleid)){
			return UnLock($array);
		}
	}
	
	
	if($EnableOpenLDAP==1){
		if(checkcreds_ldap()){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("checkcreds_ldap return true... in line:".__LINE__);}
			return UnLock($array);
		}
	}
	
	if($USE_MYSQL==1){
		if(checkcreds_mysql($array)){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("checkcreds_mysql return true... in line:".__LINE__);}
			return UnLock($array);
		}
	}
	
	events(1,"Login failed for $LOGIN/$IPADDR","MAC:$MAC\nHost:$HOST\n".@implode("\n", $GLOBALS["LOGS"]));
	$GLOBALS["ERROR"]="{wrong_unername_or_password}";
	return false;
}

function events($severity,$subject,$content){
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			}
		}
			
	wifidog_logs($subject);
	$zdate=date("Y-m-d H:i:s");
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	$subject=mysql_escape_string2($subject);
	$content=mysql_escape_string2($content);
	$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
}

function UnLock($array,$CreateAccount=false){
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$IPADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];	
	$token=$array["token"];
	$HOST=$array["HOST"];
	$ruleid=intval($array["ruleid"]);
	$MAC=$ARP;
	$username=$uid;
	$finaltime=0;
	if($ruleid==0){
		if(isset($_SESSION["WIFIDOG_RULES"])){
			$ruleid=$_SESSION["WIFIDOG_RULES"];
		}
	}
	
	
	if($ruleid==0){
		$wifidog_rules=new wifidog_rules();
		$ruleid=intval($wifidog_rules->ruleid);
	}
	
	wifidog_logs("$username: Ruleid: $ruleid");
	$sock=new wifidog_settings($ruleid);
	$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
	$ArticaSplashHotSpotEndTime=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	$ArticaSplashHotSpotRemoveAccount=intval($sock->GET_INFO("ArticaSplashHotSpotRemoveAccount"));
	$REGISTER_MAX_TIME=$sock->GET_INFO("REGISTER_MAX_TIME");
	$MACWHITE=intval($sock->GET_INFO("MACWHITE"));
	
	
	wifidog_logs("$username: Token: $token, MAC=$MAC",__FUNCTION__,__LINE__);
	
	if($token==null){
		wifidog_logs("Generate a new token from $MAC Token is null");
		$token=generateToken($MAC);
		wifidog_logs("generateToken() -> '$token'");
		if($token==null){
			$token=md5(uniqid(rand(), 1));
			wifidog_logs("Generate a rand token $token from $MAC Token is null");
		}
		$_REQUEST["token"]=$token;
	}
	
	
	$autocreate=0;
	$time=time();
	$md5key=$token;
	
	
	$q=new mysql_hotspot();
	if($CreateAccount){
		$creationtime=time();
		$sql="INSERT IGNORE INTO hotspot_members
		(uid,username,token,ruleid,ttl,sessiontime,password,enabled,creationtime,autocreate,autocreate_confirmed,autocreate_maxttl,sessionkey,MAC) VALUES
		('$username','$username','$token','$ruleid','$ArticaSplashHotSpotEndTime','','',1,'$creationtime',0,'0',0,'$md5key','$MAC')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			wifidog_logs("$q->mysql_error",__FUNCTION__,__LINE__);
			events(2,"[$MAC]: $username FATAL $q->mysql_error",__FILE__,__LINE__);
			$GLOBALS["UnLockError"]="$q->mysql_error";
			return false;
		}
	}
	
	
	$sql="SELECT creationtime,uid,ttl,enabled,autocreate,autocreate_confirmed FROM hotspot_members WHERE uid='$username'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	
	if($ligne["autocreate"]==1){if($ligne["autocreate_confirmed"]==0){ $autocreate=1; } }
	$ttl=$ligne["ttl"];
	
	
	if($ttl==0){
		if($ArticaSplashHotSpotEndTime>0){
			$finaltime = strtotime("+{$ArticaSplashHotSpotEndTime} minutes", $ligne["creationtime"]);
			$q->QUERY_SQL("UPDATE hotspot_members SET ttl='$ArticaSplashHotSpotEndTime' WHERE uid='$username'");
		}
	}
	
	
	
	
	wifidog_logs("$username: Create session  $md5key ( autocreate = $autocreate ) Rule:$ruleid; $MACWHITE");
	wifidog_logs("$username: for $LOGIN MAC:$MAC with a Max time To Live of {$ArticaSplashHotSpotEndTime}Mn in line:".__LINE__);
	
	$NextCheck = strtotime("+525600 minutes", $time);
	$logintime=time();
	if($ArticaSplashHotSpotCacheAuth>0){ $NextCheck = strtotime("+{$ArticaSplashHotSpotCacheAuth} minutes", $time); }
	
	if($autocreate==1){
		$NextCheck = strtotime("+{$REGISTER_MAX_TIME} minutes", $time);
		wifidog_logs("$username: Create session with token $md5key and force to return back in {$REGISTER_MAX_TIME}Mn (".date("H:i:s",$NextCheck).")");
	}
	

	
	$datelogs=date("Y-m-d H:i:s",$NextCheck);
	$finaltimeDate=date("Y-m-d H:i:s",$finaltime);
	
	$MAC=trim(strtolower($MAC));
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth/UnLock: Remove sessions for $token,$IPADDR,$MAC,$uid");}
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$md5key'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE ipaddr='$IPADDR'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE MAC='$MAC'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE uid='$uid'");
	
	if($finaltime>0){
		if($finaltime>time()){
			wifidog_logs("wifidog_auth/UnLock: $IPADDR,$MAC,$uid [ACCOUNT EXPIRED]");
			$GLOBALS["UnLockError"]="ACCOUNT EXPIRED";
			return false;
		}
	}
	
	$sql="INSERT IGNORE INTO hotspot_sessions 
	(`md5`,logintime, maxtime,finaltime,nextcheck,username,uid,MAC,hostname,ipaddr,autocreate,ruleid) 
	VALUES('$md5key',$logintime,$ArticaSplashHotSpotCacheAuth,$finaltime,$NextCheck,'$username','$uid','$MAC','$HOST','$IPADDR','$autocreate','$ruleid')";
	
	events(2,"[$MAC]:[$IPADDR] $username creating a new session $md5key",__FUNCTION__,__LINE__);
	
	
	if(!$q->TABLE_EXISTS("hotspot_sessions")){$q->check_tables();}
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
	if(preg_match("#Unknown column.*?ruleid#i",$q->mysql_error)){
		wifidog_logs("Unknown column ruleid -> fix it... ".__LINE__);
		$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ruleid` BIGINT UNSIGNED,ADD INDEX ( `ruleid` )");
		$q->QUERY_SQL($sql);
	}
		
	}
	
	
	if(!$q->ok){
		if(!$q->ok){events(1,"MySQL Error $q->mysql_error",__FUNCTION__,__LINE__);return true;}
		$GLOBALS["UnLockError"]=$q->mysql_error;
		
		wifidog_logs("$q->mysql_error Line:".__LINE__);
		return false;
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `md5` FROM hotspot_sessions WHERE `md5`='$md5key'"));
	if(trim($ligne["md5"])==null){
		$GLOBALS["UnLockError"]="$token is not saved";
		wifidog_logs("MySQL Failed, token: $token is not saved Line:".__LINE__);
		return false;
	}
	
	if($MACWHITE==1){
		$username=mysql_escape_string2($username);
		$sql="INSERT IGNORE INTO hotspot_whitemacs (`MAC`,`enabled`,`username`) 
		VALUES ('$MAC','1','$username');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			$GLOBALS["UnLockError"]=$q->mysql_error;
			events(0,"Fatal, $q->mysql_error",__FILE__,__LINE__);
			wifidog_logs("$q->mysql_error Line:".__LINE__);
			return false;
		}
		events(2,"[$MAC]: $username added to trusted MAC list success",__FILE__,__LINE__);
		WHITELIST_MAC($MAC);
		return true;
	}
	
	
	if($finaltime>0){
		events(2,"[$MAC]: $username Create a new session Finish at $finaltime ($finaltimeDate)",__FILE__,__LINE__);
	}
	wifidog_logs("[$MAC]: $username Create session $token for $LOGIN MAC:$MAC Max time:$NextCheck",__FUNCTION__,__LINE__);
	
	
	return true;
}

function WHITELIST_MAC($MAC){
	
	$MACENC=urlencode($MAC);
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?trust-mac=$MACENC");
	
}

function CAS_SERVICE(){
	
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$redirecturi=urlencode(trim($_SESSION["HOTSPOT"]["redirecturi"]));
	$MAC=trim(strtolower($_SESSION["HOTSPOT"]["ARP"]));
	return urlencode("$ArticaHotSpotInterface?popup=yes&MAC=$MAC&redirecturi=$redirecturi");
}





function CAS_URI(){
	
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	
	
	
	
	$service=CAS_SERVICE();
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$URL_REDIRECT="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/login?service=$service";
	return $URL_REDIRECT;
	
}



function CAS_VALIDATE(){
	$TGT=$_GET["ticket"];
	if($TGT==null){
		$GLOBALS["CASLOGS"]="<br><strong>No ticket returned</strong><br>";
		return null;}
	$sock=new sockets();
	$token=$_GET["popup"];
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$REMOTE_ADDR=$_SESSION["HOTSPOT"]["REMOTE_ADDR"];
	$SERVER_NAME=$_SESSION["HOTSPOT"]["SERVER_NAME"];
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	$ARP=$_SESSION["HOTSPOT"]["ARP"];
	

	
	$cas_service=CAS_SERVICE();
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	

	$uri_check="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/serviceValidate?ticket=$TGT&service=$cas_service";
	
	$curl = curl_init("$uri_check");
	
	if($GLOBALS["VERBOSE"]){echo "<li>$uri_check</li>";}
	
	$t=time();
	curl_setopt($curl,  CURLOPT_HEADER, true);
	curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,  CURLOPT_FAILONERROR, true);
	curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl,  CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($curl,  CURLOPT_POST, 0);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	
	$data = curl_exec($curl);
	$length=strlen($data);
	$CURLINFO_HTTP_CODE=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	
	if($GLOBALS["VERBOSE"]) {
		echo "<strong>CURLINFO_HTTP_CODE:$CURLINFO_HTTP_CODE</strong><br>";
		print_r(curl_getinfo($curl));
	}
	
	curl_close($curl);
	$distanceInSeconds = round(abs(time() - $t));
	if($GLOBALS["VERBOSE"]) {echo "<strong>data: ". strlen($data)."</strong><hr>DATA: $data\n\n</hr>";}
	
	
	
	if($CURLINFO_HTTP_CODE<>200){
		$GLOBALS["CASLOGS"]="<br><strong>Unknown error: HTTP Error: $CURLINFO_HTTP_CODE</strong>";
		return null;}
	

	if($GLOBALS["VERBOSE"]){print_r($_SESSION["HOTSPOT"]);}
	
	if($GLOBALS["VERBOSE"]) {echo "<strong>".htmlentities($data)."</strong>";}
	
	if(preg_match("#INVALID_SERVICE#is", $data)){
		$GLOBALS["CASLOGS"]="<br><strong>Invalid service</strong>";
		events(1,"CAS session failed ticket $TGT INVALID_SERVICE","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi\service: $cas_service");
		return null;
	}
	
	if(preg_match("#<cas:user>(.+?)</cas:user>#is", $data,$re)){
		events(2,"CAS session success ticket $TGT with user {$re[1]}","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi");
		if($GLOBALS["VERBOSE"]){echo "<li>TGT:$TGT - $re[1]</li>";}
		$GLOBALS["CASLOGS"]="<br><strong>$TGT Cas user: {$re[1]}<br>";
		return $re[1];
	}	
	
	$GLOBALS["CASLOGS"]="<br><strong>Unknown error: REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP</strong>";
	$GLOBALS["CASLOGS"]=$data;
	if($GLOBALS["VERBOSE"]){echo "<li>failed</li>";}
	
}

function js_load(){
	$js=$_GET["jsload"];
	$file=basename($js);
	header('Content-type: application/x-javascript');
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($js);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($js);
		
	
}

function css(){
	$css=$_GET["css"];
	$file=basename($css);
	header('Content-type: text/css');
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($css);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($css);
	
}

function endusers_load(){
	$path="ressources/templates/endusers/".$_GET["endusers"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);	
}

function imgload(){
	$path="img/".$_GET["imgload"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	
}

function wifidog_is_session_exceed_size($token,$incoming){
	$incomingMB=round($incoming/1024,2);
	$q=new mysql_hotspot();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ruleid,username,MAC FROM hotspot_sessions WHERE `md5`='$token'"));
	$ruleid=$ligne["ruleid"];
	$username=$ligne["username"];
	$MAcAddress=$ligne["MAC"];
	
	$sock=new wifidog_settings($ruleid);
	$LIMIT_BY_SIZE=intval($sock->GET_INFO("LIMIT_BY_SIZE"));
	wifidog_logs("$token: Ruleid=$ruleid; LIMIT_BY_SIZE={$LIMIT_BY_SIZE}MB");
	if($LIMIT_BY_SIZE==0){return false;}
	if($incomingMB<$LIMIT_BY_SIZE){return false;}
	events(1,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username exceed {$incomingMB}MB exceed {$LIMIT_BY_SIZE}MB, remove session");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
	
	return true;
}


function wifidog_is_end_of_life($token,$mac){
	$q=new mysql_hotspot();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username,MAC FROM hotspot_sessions WHERE `md5`='$token'"));
	if(!$q->ok){events(1,"MySQL Error $q->mysql_error",__FUNCTION__,__LINE__);return true;}
	
	$username=$ligne["username"];
	$MAcAddress=$ligne["MAC"];
	
	if($MAcAddress==null){
		events(1,"[$mac]:[{$GLOBALS["CONNECTED_IP"]}] $token not found Searching session by MAC ($mac)",__FUNCTION__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT md5,username,MAC FROM hotspot_sessions WHERE MAC='$mac'"));
		
		if(!$q->ok){events(1,"MySQL Error $q->mysql_error",__FUNCTION__,__LINE__);return true;}
		$username=$ligne["username"];
		$MAcAddress=$ligne["MAC"];
		$token=$ligne["md5"];
		if($token<>null){
			events(1,"[{$GLOBALS["CONNECTED_MAC"]}]:[{$GLOBALS["CONNECTED_IP"]}] token is $token username=$username",__FUNCTION__,__LINE__);
		}
	}
	
	if($MAcAddress==null){
		events(1,"[$mac]:[{$GLOBALS["CONNECTED_IP"]}] $token not found Searching session by IP ({$GLOBALS["CONNECTED_IP"]})",__FUNCTION__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT md5,username,MAC FROM hotspot_sessions WHERE ipaddr='{$GLOBALS["CONNECTED_IP"]}'"));
		if(!$q->ok){events(1,"MySQL Error $q->mysql_error",__FUNCTION__,__LINE__);return true;}
		$username=$ligne["username"];
		$MAcAddress=$ligne["MAC"];
		$token=$ligne["md5"];
		if($token<>null){
			events(1,"[{$GLOBALS["CONNECTED_MAC"]}]:[{$GLOBALS["CONNECTED_IP"]}] token is $token username=$username",__FUNCTION__,__LINE__);
		}
	}
		
	
	if($MAcAddress==null){
		events(1,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]: $username End-Of-Life session $token (non-existent user) action=remove session");
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		return true;
		
	}
	
	
	
	$finaltime=0;
	$sql="SELECT creationtime,autocreate,autocreate_confirmed,ruleid,ttl FROM hotspot_members WHERE uid='$username'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){events(1,"MySQL Error $q->mysql_error",__FUNCTION__,__LINE__);return true;}
	
	$autocreate=intval($ligne["autocreate"]);
	$autocreate_confirmed=intval($ligne["autocreate_confirmed"]);
	$creationtime=intval($ligne["creationtime"]);
	$ttl=$ligne["ttl"];
	$ruleid=$ligne["ruleid"];
	$sock=new wifidog_settings($ruleid);
	
	if($autocreate==1){
		$REGISTER_MAX_TIME=$sock->GET_INFO("REGISTER_MAX_TIME");
		if($autocreate_confirmed==0){
			$finaltime = strtotime("+{$REGISTER_MAX_TIME} minutes", $creationtime);
			wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}] $username Waiting the user to confirm {$REGISTER_MAX_TIME}Mn at ".date("Y-m-d H:i:s",$finaltime),__FUNCTION__,__LINE__);
			if($finaltime>time()){return false;}
			$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
			$q->QUERY_SQL("DELETE FROM hotspot_members WHERE uid='$username'");
			events(0,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username Rule:$ruleid End-Of-Life Account (not confirmed) account/session");
			
			return true;
		}else{
			wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username Auto-created session, confirmed [OK]",__FUNCTION__,__LINE__);
		}
		
	}
	
	
	
	
	if($creationtime==0){
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		$q->QUERY_SQL("DELETE FROM hotspot_members WHERE uid='$username'");
		events(0,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username End-Of-Life $username (non-existent user) action=remove session");
		
		return true;
	}
	
	

	
	wifidog_logs("$username: Creation time: $creationtime ".date("Y-m-d H:i:s")." Ruleid:$ruleid, ttl=$ttl",__FUNCTION__,__LINE__);
	$ArticaSplashHotSpotEndTime=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	$ArticaSplashHotSpotRemoveAccount=intval($sock->GET_INFO("ArticaSplashHotSpotRemoveAccount"));
		
	if($ArticaSplashHotSpotEndTime==0){
		wifidog_logs("$username: Unlimited time for lock account",__FUNCTION__,__LINE__);
		if($ArticaSplashHotSpotRemoveAccount==0){
			wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username Unlimited time for remove account",__FUNCTION__,__LINE__);
			return false;
		}
	}
	
	
	
	if($ArticaSplashHotSpotRemoveAccount>0){
		$finaltime = strtotime("+{$ArticaSplashHotSpotRemoveAccount} minutes", $creationtime);
		wifidog_logs("$username: REMOVE the account when reach ".date("Y-m-d H:i:s",$finaltime),__FUNCTION__,__LINE__);
		if($finaltime<time()){
			wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username Account expired, - remove - it",__FUNCTION__,__LINE__);
			$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
			$q->QUERY_SQL("DELETE FROM hotspot_members WHERE uid='$username'");
			events(1,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username $finaltime is less than ". time()." End-Of-Life Account expired action=remove account");
			return true;
		}
	}
	
		
	
	if($ArticaSplashHotSpotEndTime>0){
		$finaltime = strtotime("+{$ArticaSplashHotSpotEndTime} minutes", $creationtime);
		wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username LOCK the account when reach ".date("Y-m-d H:i:s",$finaltime),__FUNCTION__,__LINE__);
		
		if($finaltime<time()){
			wifidog_logs("[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username Account expired, - disable - it",__FUNCTION__,__LINE__);
			$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
			$q->QUERY_SQL("UPDATE hotspot_members SET enabled=0 WHERE uid='$username'");
			events(1,"[$MAcAddress]:[{$GLOBALS["CONNECTED_IP"]}]$username End-Of-Life $finaltime is less than ". time()." Account is expired action=disable account");
			return true;
		}
		
	}


	
	return false;
	
	
}

function wifidog_is_end_of_session($token){
	$q=new mysql_hotspot();
	$sql="SELECT username,MAC,ruleid,nextcheck FROM hotspot_sessions WHERE `md5`='$token'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if(!$q->ok){events(0,"FATAL: $q->mysql_error",__FUNCTION__,__LINE__);
		wifidog_logs("FATAL: $q->mysql_error");
		wifidog_logs("FATAL: $sql");
	}
	$username=$ligne["username"];
	$nextcheck=$ligne["nextcheck"];
	$ruleid=$ligne["ruleid"];
	$MAC=$ligne["MAC"];
	
	if($MAC==null){
		events(1,"[{$GLOBALS["CONNECTED_MAC"]}]:[{$GLOBALS["CONNECTED_IP"]}] $token not found Searching session by MAC",__FUNCTION__,__LINE__);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username,MAC,ruleid,nextcheck FROM hotspot_sessions WHERE MAC='{$GLOBALS["CONNECTED_MAC"]}'"));
		$username=$ligne["username"];
		$MAC=$ligne["MAC"];
		$token=$ligne["md5"];
		$nextcheck=$ligne["nextcheck"];
		$ruleid=$ligne["ruleid"];
		if(!$q->ok){events(0,"FATAL: $q->mysql_error",__FUNCTION__,__LINE__);wifidog_logs("FATAL: $q->mysql_error");wifidog_logs("FATAL: $sql");}
		if($token<>null){
			events(1,"[{$GLOBALS["CONNECTED_MAC"]}]:[{$GLOBALS["CONNECTED_IP"]}] token is $token username=$username",__FUNCTION__,__LINE__);
		}
	}
	
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$username: NextCheck in $nextcheck (".date("Y-m-d H:i:s",$nextcheck).") from MySQL ruleid=$ruleid",__FUNCTION__,__LINE__);}
	
	
	$sock=new wifidog_settings($ruleid);
	$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
	wifidog_logs("$username: Ruleid: $ruleid Re-authenticate each {$ArticaSplashHotSpotCacheAuth}Mn Nextcheck:$nextcheck (".date("Y-m-d H:i:s",$nextcheck).") Current time:".time(),__FUNCTION__,__LINE__);
	
		
	if($ArticaSplashHotSpotCacheAuth==0){
		if(time()>$nextcheck){
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("OK -> ".time()." > $nextcheck And ArticaSplashHotSpotCacheAuth = 0 Token=[ Re-authenticate each ( default) ], add 30 minutes",__FUNCTION__,__LINE__);}
			$nextcheck = strtotime("+30 minutes", time());
			$nextcheck_text=date("Y-m-d H:i:s",$nextcheck);
			$q->QUERY_SQL("UPDATE hotspot_sessions SET `nextcheck`='$nextcheck' WHERE `md5`='$token'");
			if(!$q->ok){events(0,"FATAL: $q->mysql_error",__FUNCTION__,__LINE__);wifidog_logs("FATAL: $q->mysql_error");}
			events(2,"[$MAC]: $username update session $token to 30mn more ({$nextcheck}s = $nextcheck_text)");
			wifidog_logs("$username: Ruleid: $ruleid Next check in {$nextcheck}s ($nextcheck_text)",__FUNCTION__,__LINE__);
			return false;
		}
	}
	
	if($nextcheck==0){
		wifidog_logs("$username: Next Check disabled, return false for EndOfSession",__FUNCTION__,__LINE__);
		return false;
	}
	
	
	
	if(time()>$nextcheck){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("NOTICE -> ".time()." > $nextcheck Destroy session",__FUNCTION__,__LINE__);}
		$nextcheck_text=date("Y-m-d H:i:s",$nextcheck);
		events(0,"[$MAC]:$username Rule Number: {$ruleid}: Destroy session Curtime > $nextcheck=$nextcheck_text (Re-authenticate each {$ArticaSplashHotSpotCacheAuth}Mn)");
		wifidog_logs("Rule Number {$ruleid} - $username: Destroy session Curtime > $nextcheck ($nextcheck_text)",__FUNCTION__,__LINE__);
		$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$token'");
		if(!$q->ok){events(0,"FATAL: $q->mysql_error",__FUNCTION__,__LINE__);wifidog_logs("FATAL: $q->mysql_error");}
		return true;
		
	}
	
}





function wifidog_recover($error=null){
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_recover($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	$cancel=$tpl->_ENGINE_parse_body("{cancel}");
	session_start();
	unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}

	$html="
	<div class=title2>$wifidog_templates->LostPasswordLink</div>
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='register-recover' name='register-recover' value='yes'>
	$HiddenFields
	<table style='width:100%'>
	<tr>
		<td class=legend>$email:</td>
		<td><input style='width:80%' type=\"text\" placeholder=\"$email\" id=\"email\" name=\"email\" value='{$_REQUEST["email"]}'></td>
	</tr>
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr><td colspan=2 align='right' class=ButtonCell>
	
	
		<a data-loading-text=\"Chargement...\"
		style=\"text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		href=\"$page?wifidog-login&$uriext\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>
		&nbsp;&nbsp;
		<a data-loading-text=\"Chargement...\"
		style=\"text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
		href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
		</td>
	</tr>
	</table>
	
		</form>
		</div>
		<script>
		$('.input-block-level').keypress(function (e) {
	
		if (e.which == 13) {
		document.forms['register-$t'].submit();
		document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}
function wifidog_load_classes(){
	$dirname=dirname(__FILE__)."/";
	include_once($dirname.'ressources/class.templates.inc');
	include_once($dirname.'ressources/class.ldap.inc');
	include_once($dirname.'ressources/class.tcpip.inc');
	include_once($dirname.'ressources/class.system.nics.inc');
	include_once($dirname.'ressources/class.wifidog.rules.inc');
	include_once($dirname.'ressources/externals/adLDAP/adLDAP.php');
	include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');
	include_once(dirname(__FILE__).'/ressources/class.webauth-msmtp.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.rules.inc');
}

function wifidog_css(){
	header("Content-type: text/css");
	$cachefile="{$GLOBALS["CACHEDIR"]}/{$_GET["wifidog-css"]}.".__FUNCTION__.".css";
	if(is_file($cachefile)){echo @file_get_contents($cachefile);return;}
	wifidog_load_classes();
	$wifidog_templates=new wifidog_templates($_GET["wifidog-css"]);
	$data= $wifidog_templates->css();
	@file_put_contents($cachefile, $data);
	echo $data;
}

function wifidog_confirm($error=null){
	$sessionkey=null;
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_confirm($error)",__FUNCTION__,__LINE__);}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	if($GLOBALS["CONNECTED_IP"]==null){
		if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
		if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
		if(isset($_GET["forceip"])){$IPADDR=$_GET["forceip"];}
		if($GLOBALS["CONNECTED_IP"]==null){$GLOBALS["CONNECTED_IP"]=$IPADDR;}
	}
	
	
	
	$sessionkey=$_REQUEST["wifidog-confirm"];
	wifidog_logs("wifidog_confirm:: Starting Confirm with key `$sessionkey`");
	
	
	if($sessionkey==null){
		if(isset($_REQUEST["sessionkey"])){$sessionkey=$_REQUEST["sessionkey"];}
	}else{
		$_REQUEST["sessionkey"]=$sessionkey;
	}
	$q=new mysql_hotspot();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid,ruleid,autocreate,MAC,token FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	
	if(!$q->ok){
		wifidog_logs("FATAL: $q->mysql_error");
		events(0,"[{$GLOBALS["CONNECTED_IP"]}]: Error while confirm $q->mysql_error",null);
		echo BuildFullPage(null,$q->mysql_error);
		return;
	}
	
	wifidog_logs("Starting key `$sessionkey` from \"hotspot_members\" is member `{$ligne["uid"]}`");
	
	
	if($ligne["uid"]==null){
		
		$url=null;
		// A voir , ipaddr sinon...
		
		
		events(0,"[{$GLOBALS["CONNECTED_IP"]}]: $sessionkey: User did not exists, cancel confirmation",null);
		if(isset($_SESSION["HOTSPOT_REDIRECT_URL"])){$url=$_SESSION["HOTSPOT_REDIRECT_URL"];}
		
		if($url==null){
			$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
			$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
			if($LANDING_PAGE<>null){$url=$LANDING_PAGE;}
		}
		if($url==null){$url="http://articatech.net";}
		
		$this_account_didnot_exists=$tpl->_ENGINE_parse_body("{this_account_didnot_exists}");
		$text_form="
		<form>
			<center>
				<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'>
			</center>
		</form>";
		
		echo BuildFullPage($text_form,"$error","<META http-equiv=\"refresh\" content=\"3; URL=$url\">");
				return;
		
		
		echo BuildFullPage(null,$this_account_didnot_exists);
		return;
	}
	
	
	$email=mysql_escape_string2($ligne["uid"]);
	if($_REQUEST["url"]==null){$_REQUEST["url"]=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	if(preg_match("#msftncsi\.com#", $_REQUEST["url"])){$_REQUEST["url"]=null;}
	
	if($LANDING_PAGE<>null){
		if(!preg_match("#^http:#", $LANDING_PAGE)){$LANDING_PAGE="http://$LANDING_PAGE";}
		$parse=parse_url($LANDING_PAGE);
		$host=$parse["host"];
		$url=$LANDING_PAGE;
	}
	
	
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	
	$url=wifidog_redirect_uri();
	$parse=parse_url($url);
	$host=$parse["host"];
	$autocreate=intval($ligne["autocreate"]);
	$token=$ligne["token"];
	$MAC=$ligne["MAC"];
	$wifidog_templates=new wifidog_templates($ligne["ruleid"]);
	$ArticaSplashHotSpotRedirectText=$wifidog_templates->ArticaSplashHotSpotRedirectText;
	$ruleid=$ligne["ruleid"];
	$sock=new wifidog_settings($ligne["ruleid"]);
	$REGISTER_MAX_TIME=intval($sock->GET_INFO("REGISTER_MAX_TIME"));
	$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
	
	

	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		wifidog_logs("{$ligne["uid"]}: Ruleid..........: {$ligne["ruleid"]}",__FUNCTION__,__LINE__);
		wifidog_logs("{$ligne["uid"]}: Requested url...: {$_REQUEST["url"]}",__FUNCTION__,__LINE__);
		wifidog_logs("{$ligne["uid"]}: Redirect url....: {$url}",__FUNCTION__,__LINE__);
		wifidog_logs("{$ligne["uid"]}: ENABLED_REDIRECT_LOGIN: {$ENABLED_REDIRECT_LOGIN}",__FUNCTION__,__LINE__);
		wifidog_logs("{$ligne["uid"]}: token/sessionkey: {$token}",__FUNCTION__,__LINE__);
		wifidog_logs("{$ligne["uid"]}: Re-Authenticate.: {$ArticaSplashHotSpotCacheAuth}mn",__FUNCTION__,__LINE__);
	}
	
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `md5` as `sessionkey` FROM hotspot_sessions WHERE `md5`='$token'"));
	if(!$q->ok){
		events(0,"FATAL: $q->mysql_error",null);
		wifidog_logs("FATAL: $q->mysql_error");
	}
	
	$sessionkey=$ligne2["sessionkey"];
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("[$MAC]: {$ligne["uid"]}: sessionkey = `$sessionkey` from Token:$token",__FUNCTION__,__LINE__);}
	
	if($sessionkey==null){
		if($autocreate==1){
			if($ENABLED_REDIRECT_LOGIN==1){
				events(2,"[$MAC]:[{$GLOBALS["CONNECTED_IP"]}]: {$ligne["uid"]}: Auto-created by email, remove user by $sessionkey in database",null);
				wifidog_logs("{$ligne["uid"]}: Auto-created by email, remove user by $sessionkey in database");
				$q->QUERY_SQL("DELETE FROM hotspot_members WHERE `sessionkey`='$sessionkey'");
				
			}
		}
		
		
		events(0,"[$MAC]: {$ligne["uid"]}: Session expired",null);
		
		$error=$tpl->_ENGINE_parse_body("{session_expired}");
		$text_form="
		<p>$ArticaSplashHotSpotRedirectText: <a href=\"$url\">$host</a></p>
		<form><center><img src='hotspot.php?imgload=wait_verybig_mini_red.gif'></center></form>";
		
		echo BuildFullPage($text_form,"$error","<META http-equiv=\"refresh\" content=\"0; URL=$url\">");
		return;
	}

	
	

	
	wifidog_logs("Next authentication will be in {$ArticaSplashHotSpotCacheAuth}mn");
	
	if($ArticaSplashHotSpotCacheAuth>0){
		$NextCheck = strtotime("+{$ArticaSplashHotSpotCacheAuth} minutes");
		$NextCheck_results=$NextCheck-time();
		$NextCheck_results_minutes=$NextCheck_results/60;
		wifidog_logs("[$MAC]: $email: session key: `$sessionkey`  Re-Authenticate at: {$NextCheck}s calculate {$NextCheck_results}s/{$NextCheck_results_minutes}Mn from  ".time());
		events(2,"[$MAC]:[{$GLOBALS["CONNECTED_IP"]}]: $email: session key: `$sessionkey` Rule:{$ruleid} Re-Authenticate at: {$NextCheck}s",null);
		$q->QUERY_SQL("UPDATE hotspot_sessions SET nextcheck='$NextCheck' WHERE `md5`='$sessionkey'");
		if(!$q->ok){
			events(0,"[$MAC]: $email: $q->mysql_error");
			wifidog_logs("MySQL error $q->mysql_error");
		}
	}
	
	$sql="UPDATE hotspot_members SET 
		autocreate_confirmed=1, 
		autocreate=1, 
		autocreate_maxttl='$REGISTER_MAX_TIME', 
		creationtime='".time()."', sessionkey='$sessionkey' WHERE uid='$email'";

	$q->QUERY_SQL($sql);
	

	if(!$q->ok){
		events(0,"[$MAC]:[{$GLOBALS["CONNECTED_IP"]}]: {$email}: Unable to confirm Rule:{$ruleid} MySQL Error $q->mysql_error",null);
		wifidog_logs("$q->mysql_error");
		echo BuildFullPage(null,$q->mysql_error);die();
	}

	
	

	events(2,"[$MAC]: {$email}:[{$GLOBALS["CONNECTED_IP"]}]: [Confirmed]: Rule:{$ruleid} Success update session $sessionkey",null);
	
	
	
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$ttl=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	$ENABLED_META_LOGIN=intval($sock->GET_INFO("ENABLED_META_LOGIN"));
	$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
	if($LANDING_PAGE<>null){$url=$LANDING_PAGE;}
	
	
	if(!preg_match("#^http:#", $url)){$url="http://$url";}
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("{$ligne["uid"]}: LANDING_PAGE.: {$LANDING_PAGE}");}
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("{$ligne["uid"]}: Redirect to $url host:$host");}
	
	if($ENABLED_META_LOGIN==1){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members_meta` ( `uid` VARCHAR( 128 ) NOT NULL , `creationtime` INT UNSIGNED NOT NULL, PRIMARY KEY ( `uid` ) , INDEX ( `creationtime`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		$sql="INSERT IGNORE INTO `hotspot_members_meta` (uid,creationtime) VALUES ('$email','".time()."')";
		$q->QUERY_SQL($sql);
	}
	
	$text_form="
	
	<form>
	<p>$wifidog_templates->REGISTER_MESSAGE_SUCCESS</p>
	<p>$ArticaSplashHotSpotRedirectText: <a href=\"$url\">$host</a></p>			
	<center>
		<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'>
	</center>
	
	</form>";
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Redirect Client {$_SESSION["HOTSPOT_REDIRECT_MAC"]} to $url",__FUNCTION__,__LINE__);}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
	
}


function wifidog_password($error=null){
	$sessionkey=null;
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_password($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sessionkey=$_REQUEST["wifidog-password"];
	if($sessionkey==null){
		if(isset($_REQUEST["sessionkey"])){$sessionkey=$_REQUEST["sessionkey"];}
	}else{
		$_REQUEST["sessionkey"]=$sessionkey;
	}
	$q=new mysql_hotspot();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("No existent account",__FUNCTION__,__LINE__);}
		echo BuildFullPage(null,"{this_account_didnot_exists}");
		return;
	}
	
	if($_REQUEST["url"]==null){$_REQUEST["url"]=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	
	
	
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{change_password}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$RECOVER_MESSAGE_P1=$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"];
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	$html="
	
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='confirm-password' name='confirm-password' value='yes'>
	$HiddenFields
	<div style='font-size:26px;font-weight:bold;margin-bottom:15px'>$register</div>
	<div style='font-size:18px'>$RECOVER_MESSAGE_P1</div>
	<label style='font-size:$fontsize;margin-top:20px' for=\"email-$t\" class=legend>$email: {$ligne["uid"]}</label>
	<label style='font-size:$fontsize;margin-top:20px' for=\"password\" class=legend>$password:</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	placeholder=\"$password\" id=\"password\" name=\"password\" value='{$_REQUEST["password"]}'>
	 
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"password2-$t\" class=legend>$password ($confirm):</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	placeholder=\"$password ($confirm)\" name=\"password2\"
	id=\"password2\" value='{$_REQUEST["password2"]}'>
	
	
	<div style='margin-top:20px;text-align:right'>
	
	<a data-loading-text=\"Chargement...\"
	style=\"font-size:$btsize;text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</div>
	
	</form>
	</div>
	<script>
	$('.input-block-level').keypress(function (e) {
	
	if (e.which == 13) {
	document.forms['register-$t'].submit();
	document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}

function wifidog_password_perform(){
	$tpl=new templates();
	$q=new mysql_hotspot();
	$sessionkey=$_REQUEST["sessionkey"];
	
	if($sessionkey==null){
		return wifidog_password("Missing field sessionkey");
		
	}
	
	$url=$_REQUEST["url"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		echo BuildFullPage(null,"<center>{this_account_didnot_exists}<hr><span style='font-size:12px'>$sessionkey</span></center>","<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
		return;
	}
	
	
	$password2=trim($_POST["password2"]);
	$password=trim($_POST["password"]);
	if($password2<>$password){return wifidog_password("{password_mismatch}");}
	$password=md5($password);
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=1,
		autocreate=1,
		password='$password'
		WHERE sessionkey='$sessionkey'";

	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		return wifidog_password($q->mysql_error_html());
	}
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	
	$text_form="
	<div style='width:98%' class=form>
	<center>
	<div style='font-size:$fontsize'><center>{updated_password_successfully}<br>$url</center></div>
	<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'></center></div>";
	
	$text_form=$tpl->_ENGINE_parse_body($text_form);
	if(!preg_match("#^http#", $url)){$url="http://$url";}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
	
}


function wifidog_register_sms($error=null){
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_register_sms($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	if($_POST["remove-sms"]==1){
		$_SESSION["MEM_MOBILE"]=null;
		$_REQUEST["mobile"]=null;
		$_POST["remove-sms"]=0;
	}
	
	$mobile=$tpl->_ENGINE_parse_body("{mobile}");
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	
	if(!isset($_GET["url"])){$_GET["url"]=$sock->GET_INFO("LOST_LANDING_PAGE");}
	$styleF="font-size:{$wifidog_templates->SMS_FONT_SIZE}px !important";
	$SMS_BUTTON=$wifidog_templates->char($wifidog_templates->SMS_BUTTON);
	$redirecturi=$_GET["url"];
	
	if(isset($_SESSION["MEM_MOBILE"])){$_REQUEST["mobile"]=$_SESSION["MEM_MOBILE"];}
	if(!isset($_REQUEST["mobile"])){$_REQUEST["mobile"]=null;}
	
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	$html[]="
	<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</div>
	<p>".$wifidog_templates->char($wifidog_templates->SMS_INTRO)."</p>
	<div style='width:98%' class=form id='form-$t'>
		<form name='registersms-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
		$form_head
		<input type='hidden' id='register-sms' name='register-sms' value='yes'>
		<input type='hidden' id='remove-sms' name='remove-sms' value='0'>
		$HiddenFields
	<table style='width:100%'>
		<tr>
			
			";
	if($_REQUEST["mobile"]<>null){
		
		$SMS_BUTTON=$tpl->_ENGINE_parse_body("{submit}");
		$html[]="
		<td style='$styleF'><center style='$wifidog_templates->LegendsStyle;text-align:center !important'>$mobile</center></td>
		</tr>
		<tr style='height:90px'>
			<td style='$styleF'>
				<input type='hidden' id='mobile' name='mobile' value='{$_REQUEST["mobile"]}'>
				<center style='$wifidog_templates->LegendsStyle;text-align:center !important'>
					<a href=\"javascript:blur();\" OnClick=\"javascript:RetryMobile()\"
					style='$wifidog_templates->LegendsStyle;text-align:center !important;text-decoration:underline !important'>
					{$_REQUEST["mobile"]}
					</a>
			</td>
		</tr>
		<tr>
			<td>
				<center style='$wifidog_templates->LegendsStyle;$styleF;text-align:center !important'>".$wifidog_templates->char($wifidog_templates->SMS_FIELD)."</center>
			</td>
		</tr>
		<tr style='height:90px'>
			<td>
			<center style='$styleF'>
				<input style='width:80%;$styleF;text-align:center' type=\"text\" id=\"SMS_CODE\" name=\"SMS_CODE\" value=''>
			</center>
			</td>
		</tr>
		";
	}else{
		$html[]="
		<tr>
			<td style='$styleF'><center style='$wifidog_templates->LegendsStyle;text-align:center !important'>$mobile</center></td>	
		</tr>
		<tr style='height:90px'>
			<td><center style='$styleF'>
					<input style='width:80%;$styleF;text-align:center !important'  type=\"text\" placeholder=\"$mobile\" id=\"mobile\" name=\"mobile\" value='{$_REQUEST["mobile"]}'>
				</center>
			</td>
		</tr>
		";
		
		
		
	}

$html[]="<tr><td colspan=2>&nbsp;</td></tr>";
$html[]="<tr>";



$html[]="
<td colspan=2 class=ButtonCell><center>
	<a data-loading-text=\"Chargement...\"
	style=\"text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"javascript:document.forms['registersms-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$SMS_BUTTON&nbsp;&raquo;</a>
	</center>
</td>
</tr>
</table>

</form>
</div>
<script>
$('.input-block-level').keypress(function (e) {

if (e.which == 13) {
document.forms['registersms-$t'].submit();
document.getElementById('registersms-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
}

});

function RetryMobile(){
	document.getElementById('mobile').value='';
	document.getElementById('remove-sms').value='1';
	document.forms['registersms-$t'].submit();
	document.getElementById('registersms-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
}

</script>



";
if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("End wifidog_register_sms($error)");}
echo BuildFullPage(@implode("", $html),$error);



}


function wifidog_register($error=null){
	
	$ARP=$_REQUEST["mac"];
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_register($error)");}
	$t=time();
	
	
	
	$cachefile="{$GLOBALS["CACHEDIR"]}/$ARP.".md5($error).".".__FUNCTION__.".html";
	if(is_file($cachefile)){echo @file_get_contents($cachefile);return;}
	
	$page=CurrentPageName();
	session_start();
	$FieldsCache="{$GLOBALS["CACHEDIR"]}/FieldsTranslate.db";
	if(!is_file($FieldsCache)){
		$tpl=new templates();
		$email=$tpl->_ENGINE_parse_body("{email}");
		$password=$tpl->_ENGINE_parse_body("{password}");
		$submit=$tpl->_ENGINE_parse_body("{submit}");
	
		$confirm=$tpl->_ENGINE_parse_body("{confirm}");
		$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
		$cancel=$tpl->_ENGINE_parse_body("{cancel}");
		
		$ARRAY_TRANS["email"]=$email;
		$ARRAY_TRANS["password"]=$password;
		$ARRAY_TRANS["submit"]=$submit;
		$ARRAY_TRANS["confirm"]=$confirm;
		$ARRAY_TRANS["password_mismatch"]=$password_mismatch;
		$ARRAY_TRANS["cancel"]=$cancel;
		@file_put_contents($FieldsCache, serialize($ARRAY_TRANS));
	}else{
		$ARRAY_TRANS=unserialize(@file_get_contents($FieldsCache));
		$email=$ARRAY_TRANS["email"];
		$password=$ARRAY_TRANS["password"];
		$submit=$ARRAY_TRANS["submit"];
		$confirm=$ARRAY_TRANS["confirm"];
		$password_mismatch=$ARRAY_TRANS["password_mismatch"];
		$cancel=$ARRAY_TRANS["cancel"];
	}
	
	
	
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$ArticaHotSpotNowPassword=intval($sock->GET_INFO("ArticaHotSpotNowPassword"));
	
	
	
	
	
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ENABLED_SMTP=intval($sock->GET_INFO("ENABLED_SMTP"));
	if($ENABLED_SMTP==0){$email=$tpl->_ENGINE_parse_body("{account}");}
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	
	
	$CancelButton="<div style='margin-top:20px;text-align:right'>
	<a data-loading-text=\"Chargement...\" 
	style=\"text-transform:capitalize\" 
	class=\"Button2014 Button2014-success Button2014-lg\" 
	id=\"".time()."\" 
	href=\"$page?wifidog-login&$uriext\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>&nbsp;&nbsp;";
	
	
	if($ENABLED_SMTP==1){
		if($ENABLED_REDIRECT_LOGIN==1){
			$CancelButton=null;
		}
	}
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	
	$html[]="
	<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</div>
	<p>".$wifidog_templates->char($wifidog_templates->REGISTER_MESSAGE_EXPLAIN)."</p>
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='register-member' name='register-member' value='yes'>
	$HiddenFields
	<table style='width:100%'>
	<tr>
	<td class=legend>$email:</td>
	<td><input style='width:80%' type=\"text\" placeholder=\"$email\" id=\"email\" name=\"email\" value='{$_REQUEST["email"]}'></td>
	</tr>
	";

	if($ArticaHotSpotNowPassword==1){
		$html[]="<input type=\"hidden\" name=\"password2\" value=''><input type=\"hidden\" name=\"password\" id='password' value=''>";
	}else{
		
		$html[]="
		<tr>
			<td class=legend>$password:</td>
			<td><input style='width:80%' type=\"password\" placeholder=\"$password\" id=\"password\" name=\"password\" value='{$_REQUEST["password"]}'></td>
		</tr>
		<tr>
			<td class=legend>$password ($confirm):</td>
			<td><input style='width:80%' type=\"password\" 
		placeholder=\"$password ($confirm)\" name=\"password2\" 
		id=\"password2\" value='{$_REQUEST["password2"]}'></td>
		</tr>		
		";
	}
	
	
	if($wifidog_templates->REGISTER_GENERIC_PASSWORD==1){

		$html[]="
		<tr>
		<td class=legend>".$wifidog_templates->char($wifidog_templates->REGISTER_GENERIC_LABEL).":</td>
		<td><input style='width:80%' type=\"text\" id=\"passphrase\" name=\"passphrase\" value=''></td>
		</tr>";		
		
	}

	$html[]="<tr><td colspan=2>&nbsp;</td></tr>";
	$html[]="<td colspan=2 align='right' class=ButtonCell>";
	
	
	
	$html[]="$CancelButton
	
	<a data-loading-text=\"Chargement...\" 
								style=\"text-transform:capitalize\" 
								class=\"Button2014 Button2014-success Button2014-lg\" 
								id=\"".time()."\" 
								onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\" 
								href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</td>
	</tr>
	</table>

</form>
</div>	
<script>
 $('.input-block-level').keypress(function (e) {
	
	 if (e.which == 13) {
		document.forms['register-$t'].submit();
		document.getElementById('form-$t').innerHTML='<center><img src='/hotspot.php?imgload=wait_verybig_mini_red.gif'></center>';
	 }

});

</script>
	
	
	
	";
	$text=BuildFullPage(@implode("", $html),$error);
	@file_put_contents($cachefile, $text);
	echo $text;
}

function SMTP_SETTINGS(){
	if(isset($GLOBALS["SMTP_SETTINGS"])){return $GLOBALS["SMTP_SETTINGS"];}
	$sock=new sockets();
	if(!isset($GLOBALS["CACHE_AUTH"])){
		$GLOBALS["CACHE_AUTH"]=$sock->GET_INFO("ArticaSplashHotSpotCacheAuth");
	}
	if(!isset($GLOBALS["MAX_TIME"])){
		$GLOBALS["MAX_TIME"]=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	}
	
	
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	$ArticaHotSpotSMTP=$sock->FillSMTPNotifsDefaults($ArticaHotSpotSMTP);
	$ArticaHotSpotSMTP["ArticaSplashHotSpotEndTime"]=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	
	
	

	while (list ($num, $ligne) = each ($ArticaHotSpotSMTP) ){
		if(!$users->CORP_LICENSE){if(preg_match("#^SKIN_#i", trim($num))){$ArticaHotSpotSMTP[$num]=null;continue;}}
			$ArticaHotSpotSMTP[$num]=utf8_decode($ligne);
	
	}
	
	
	if(!isset($ArticaHotSpotSMTP["ENABLED_REDIRECT_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_REDIRECT_LOGIN"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["ENABLED_REDIRECT_LOGIN"])){$ArticaHotSpotSMTP["ENABLED_REDIRECT_LOGIN"]=0;}
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	
	
	
	
	
	if(!isset($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	if(!is_numeric($ArticaHotSpotSMTP["SSL_PORTAL"])){$ArticaHotSpotSMTP["SSL_PORTAL"]=0;}
	
	
	if($ArticaHotSpotSMTP["REGISTER_MESSAGE"]==null){$ArticaHotSpotSMTP["REGISTER_MESSAGE"]="Hi, in order to activate your account on the HotSpot system,\nclick on the link below";}
	if($ArticaHotSpotSMTP["RECOVER_MESSAGE"]==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE"]="Hi, in order to recover your password on the HotSpot system,\nclick on the link below";}
	
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_CONFIRM"]="Success<br>\nA message as been sent to you.<br>\nPlease check your WebMail system in order to recover your password<br>\nYour can surf on internet for %s minutes";}
	if(trim($ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"])==null){$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"]="Fill out the form below to change your password";}
	
	
	if($ArticaHotSpotSMTP["DEFAULT_URL"]==null){$ArticaHotSpotSMTP["DEFAULT_URL"]="http://www.articatech.net";}
	if(trim($ArticaHotSpotSMTP["TERMS_EXPLAIN"])==null){$ArticaHotSpotSMTP["TERMS_EXPLAIN"]="To signup for a new account you are required to read our \"TERMS and CONDITIONS\".<br>Once you have read these terms and conditions please click \"ACCEPT\" acknowledging you understand and accept these terms and conditions.";}
	if(trim($ArticaHotSpotSMTP["TERMS_CONDITIONS"])==null){$ArticaHotSpotSMTP["TERMS_CONDITIONS"]=@file_get_contents("ressources/databases/wifi-terms.txt");}
	
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_FONT_SIZE"]="22px";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"]="32px";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]==null)){$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]="Calibri, Candara, Segoe, Segoe UI, Optima, Arial, sans-serif";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_FONT_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_LINK_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]="263849";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"]="5CB85C";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]="398439";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"]="FFFFFF";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"]="47A447";}
	
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"]="485px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"]="221px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"]="10pt";}
	if(trim($ArticaHotSpotSMTP["SKIN_TEXT_LOGON"])==null){$ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"]=null;}
	
	
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"]="logo-hotspot.png";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"]="50";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"]="0";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"]="401";}
	
	
	
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]="15px";}
	
	
	
	$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]=str_replace("Segoe UI","\"Segoe UI\"",$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]);
	

	
	reset($ArticaHotSpotSMTP);
	$GLOBALS["SMTP_SETTINGS"]=$ArticaHotSpotSMTP;
	return $ArticaHotSpotSMTP;
}

function wifidog_recover_perform(){
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_hotspot();
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_recover_perform()");}
	$email=trim(strtolower($_POST["email"]));
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {return wifidog_recover("{error_email_invalid}");}
	$tr=explode("@",$email);
	$domain=$tr[1];
	if (!checkdnsrr($domain, 'MX')) {return wifidog_recover("&laquo;$domain&raquo;<br>{error_domain_email_invalid}");}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid,sessionkey,ruleid FROM hotspot_members WHERE `uid`='$email'"));
	if($ligne["uid"]==null){return wifidog_register("{this_account_didnot_exists}<hr>"); }
	$sessionkey=$ligne["sessionkey"];
	
	
	if($sessionkey==null){$sessionkey=md5(time().$email);}
	
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=0,
		autocreate=1,
		autocreate_maxttl='{$ArticaHotSpotSMTP["REGISTER_MAX_TIME"]}',
		creationtime='".time()."',
		sessionkey='$sessionkey'
		WHERE uid='$email'";

	$q->QUERY_SQL($sql);
	
	
	
	
	if(!$q->ok){
		wifidog_logs("$q->mysql_error");
		return wifidog_recover($q->mysql_error);
	}
	
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-password=$sessionkey";
	
	$smtp_sender=$ArticaHotSpotSMTP["smtp_sender"];
	
	$smtp_senderTR=explode("@",$smtp_sender);
	$instance=$smtp_senderTR[1];

	$body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="Subject: {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]}";
	$body[]="To: $email";
	$body[]="";
	$body[]="";
	$body[]=$ArticaHotSpotSMTP["RECOVER_MESSAGE"];
	$body[]=$URL_REDIRECT;
	$body[]="";
	$body[]="";
	$finalbody=@implode("\r\n", $body);
	
	
	$webauth_msmtp=new webauth_msmtp($smtp_sender, $finalbody,$email);
	if(!$webauth_msmtp->Send()){
		$smtp=new smtp();
		if($ArticaHotSpotSMTP["smtp_auth_user"]<>null){
			$params["auth"]=true;
			$params["user"]=$ArticaHotSpotSMTP["smtp_auth_user"];
			$params["pass"]=$ArticaHotSpotSMTP["smtp_auth_passwd"];
		}
		$params["host"]=$ArticaHotSpotSMTP["smtp_server_name"];
		$params["port"]=$ArticaHotSpotSMTP["smtp_server_port"];
		
		
		if(!$smtp->connect($params)){
			return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
		
		}
		
		
		if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$email,"body"=>$finalbody,"headers"=>null))){
			$smtp->quit();
			return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
		}
		
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth/wifidog_recover_perform From: $smtp_sender to $email {$ArticaHotSpotSMTP["REGISTER_SUBJECT"]} success");}
		$smtp->quit();
	}
	
	
	$array["LOGIN"]=$email;
	$array["ARP"]=$_REQUEST["mac"];
	$array["token"]=$_REQUEST["token"];
	$array["redirecturi"]=$_REQUEST["url"];
	$array["REMOTE_ADDR"]=$_REQUEST["ip"];
	$array["REGISTER"]=true;
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth/wifidog_recover_perform Unlock with token={$array["token"]}");}
	if(!UnLock($array)){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_recover_perform(): failed_to_create_session");}
		return wifidog_register("{error} {failed_to_create_session}");
	
	}
	
	
	session_start();
	$_SESSION["HOTSPOT_AUTO_RECOVER"]=true;
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$_REQUEST["url"];
	wifidog_redirect_after_login();
	
	
}


function wifidog_redirect_after_login(){

	$_SESSION["HOTSPOT_REDIRECT_URL"]=$_REQUEST["url"];
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	
	if($token==null){
		$token=generateToken($ARP);
	}
	
	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	wifidog_logs("wifidog_redirect_after_login:: Redirect Token: $token to $gateway_addr:$gw_port");
	header("Location: $redirecturi");
	
}




function wifidog_register_sms_perform(){
	session_start();
	$page=CurrentPageName();
	$tpl=new templates();
	$autocreate_confirmed=0;
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_register_sms_perform()");}
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$mobile=$_POST["mobile"];
	$mobile=str_replace(" ", "", $mobile);
	$mobile=str_replace(".", "", $mobile);
	$mobile=str_replace(";", "", $mobile);
	$mobile=str_replace("-", "", $mobile);
	
	$ArticaSplashHotSpotEndTime=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	$autocreate_maxttl=$ArticaSplashHotSpotEndTime;
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$MAC=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	$ruleid=$_REQUEST["ruleid"];
	if($ruleid==0){if(isset($_SESSION["WIFIDOG_RULES"])){$ruleid=$_SESSION["WIFIDOG_RULES"];}}
	
	
	
	$LOGIN=$_POST["mobile"];
	$IPADDR=null;
	
	$HOST=gethostbyaddr($IPADDR);
	$URI=$url;
	
	$Credsarray["LOGIN"]=$LOGIN;
	$Credsarray["IPADDR"]=null;
	$Credsarray["MAC"]=$MAC;
	$Credsarray["ARP"]=$MAC;
	$Credsarray["HOST"]=$HOST;
	$Credsarray["token"]=$token;
	$Credsarray["ruleid"]=$ruleid;
	
	
	
	if($mobile==null){
		return wifidog_register_sms("{please_give_your_phone_number}");
		
	}
	$q=new mysql_hotspot();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid,autocreate,password,sessionkey FROM hotspot_members WHERE `uid`='$mobile'"));
	$VerifPassord=$ligne["password"];
	if($ligne["uid"]==null){
		$CODE_NUMBER=substr(number_format(time() * mt_rand(),0,'',''),0,4);
		$password=md5($CODE_NUMBER);
		$creationtime=time();
		$sessionkey=md5($password.$creationtime.$mobile);
		$_SESSION["MEM_MOBILE"]=$mobile;
		
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Create new member $mobile,$CODE_NUMBER,TTL:$ArticaSplashHotSpotEndTime in line:".__LINE__);}
		$sql="INSERT IGNORE INTO hotspot_members
		(uid,username,token,ruleid,ttl,sessiontime,password,enabled,creationtime,autocreate,autocreate_confirmed,autocreate_maxttl,sessionkey,MAC) VALUES
		('$mobile','$mobile','$token','{$_SESSION["WIFIDOG_RULES"]}','$ArticaSplashHotSpotEndTime','','$password',1,'$creationtime',0,'$autocreate_confirmed',$autocreate_maxttl,'$sessionkey','$MAC')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events(0,"SMS: Register failed mobile: $mobile (MySQL error)","$q->mysql_error");
			return wifidog_register_sms($q->mysql_error);
		}
		events(2,"SMS: Register success mobile: $mobile",null);
		
		$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%MOBILE%", $mobile, $wifidog_templates->SMS_SMTP_SUBJECT);
		$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%CODE%", $CODE_NUMBER, $wifidog_templates->SMS_SMTP_SUBJECT);
		$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%TIME%", time(), $wifidog_templates->SMS_SMTP_SUBJECT);
		
		$wifidog_templates->SMS_SMTP_BODY=str_replace("%MOBILE%", $mobile, $wifidog_templates->SMS_SMTP_BODY);
		$wifidog_templates->SMS_SMTP_BODY=str_replace("%CODE%", $CODE_NUMBER, $wifidog_templates->SMS_SMTP_BODY);
		$wifidog_templates->SMS_SMTP_BODY=str_replace("%TIME%", time(), $wifidog_templates->SMS_SMTP_BODY);
		$wifidog_templates->SMS_SMTP_BODY=str_replace("\n","\r\n",$wifidog_templates->SMS_SMTP_BODY);
		
		
		
		$smtp_sender=$sock->GET_INFO("sms_smtp_sender");
		$smtp_senderTR=explode("@",$smtp_sender);
		$instance=$smtp_senderTR[1];
		$sms_smtp_recipient=$sock->GET_INFO("sms_smtp_recipient");
		
		
		$random_hash = md5(date('r', time()));
		$boundary="$random_hash/$instance";
		$body[]="Return-Path: <$smtp_sender>";
		$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
		$body[]="From: $smtp_sender";
		$body[]="Subject: {$wifidog_templates->SMS_SMTP_SUBJECT}";
		$body[]="To: $sms_smtp_recipient";
		$body[]="Auto-Submitted: auto-replied";
		$body[]="MIME-Version: 1.0";
		$body[]="Content-Type: multipart/mixed;";
		$body[]="	boundary=\"$boundary\"";
		$body[]="Content-Transfer-Encoding: 8bit";
		$body[]="Message-Id: <$random_hash@$instance>";
		$body[]="--$boundary";
		$body[]="Content-Description: Notification";
		$body[]="Content-Type: text/plain; charset=us-ascii";
		$body[]="";
		$body[]=$wifidog_templates->SMS_SMTP_BODY;
		$body[]="";
		$body[]="";
		$body[]="--$boundary";
		$finalbody=@implode("\r\n", $body);
		
		include_once(dirname(__FILE__)."/ressources/class.webauth-sms-msmtp.inc");
		$webauth_msmtp=new webauth_sms_msmtp($finalbody,$_SESSION["WIFIDOG_RULES"]);
		if(!$webauth_msmtp->Send()){
			events(0,"SMS: Method(1) Send eMail failed from $smtp_sender to $sms_smtp_recipient","$smtp->error_number\n$smtp->error_text");
			$smtp=new smtp();
			if($sock->GET_INFO("sms_smtp_auth_user")<>null){
				$params["auth"]=true;
				$params["user"]=$sock->GET_INFO("sms_smtp_auth_user");
				$params["pass"]=$sock->GET_INFO("sms_smtp_auth_passwd");
			}
			$params["host"]=$sock->GET_INFO("sms_smtp_server_name");
			$params["port"]=$sock->GET_INFO("sms_smtp_server_port");
		
		
			if(!$smtp->connect($params)){
				$q->QUERY_SQL("DELETE FROM hotspot_members WHERE `sessionkey`='$sessionkey'");
				events(0,"SMS: Method(2) Send eMail failed from $smtp_sender to $sms_smtp_recipient","$smtp->error_number\n$smtp->error_text");
				return wifidog_register_sms("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
			}
				
			
			if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$sms_smtp_recipient,"body"=>$finalbody,"headers"=>null))){
				$smtp->quit();
				events(0,"SMS: Method(3) Send eMail failed from $smtp_sender to $sms_smtp_recipient","$smtp->error_number\n$smtp->error_text");
				return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
			}
		
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("From: $smtp_sender to $sms_smtp_recipient {$wifidog_templates->REGISTER_SUBJECT} success");}
			$smtp->quit();
		}
		events(2,"SMS: Send Code: $CODE_NUMBER Success from $smtp_sender to $sms_smtp_recipient","$smtp->error_number\n$smtp->error_text");
		return wifidog_register_sms($wifidog_templates->char($wifidog_templates->SMS_CONFIRM_MESSAGE));
		
		
		
	}
	
	
	if($_POST["SMS_CODE"]==null){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("No Code submited!",__FUNCTION__,__LINE__);}
		events(1,"SMS: mobile: $mobile no SMS code returned",null);
		return wifidog_register_sms($wifidog_templates->SMS_CODE_ERROR);
	}
	
	
	
	
	if(preg_match("#([0-9]+)#", $_POST["SMS_CODE"],$re)){$_POST["SMS_CODE"]=$re[1];}
	$VerifPassord2=md5(trim($_POST["SMS_CODE"]));
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Saved Code: $VerifPassord, returned code {$_POST["SMS_CODE"]}/$VerifPassord2",__FUNCTION__,__LINE__);}
	
	
	if($VerifPassord<>$VerifPassord2){return wifidog_register_sms($wifidog_templates->SMS_CODE_ERROR);}
		
	$ENABLED_META_LOGIN=intval($sock->GET_INFO("ENABLED_META_LOGIN"));
	wifidog_logs("Ruleid: {$_SESSION["WIFIDOG_RULES"]} Endtime: $ArticaSplashHotSpotEndTime");
	
	if($ENABLED_META_LOGIN==1){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members_meta` ( `uid` VARCHAR( 128 ) NOT NULL , `creationtime` INT UNSIGNED NOT NULL, PRIMARY KEY ( `uid` ) , INDEX ( `creationtime`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		$sql="INSERT IGNORE INTO `hotspot_members_meta` (uid,creationtime) VALUES ('$mobile','".time()."')";
		$q->QUERY_SQL($sql);
	}	
	
	if(UnLock($Credsarray,false)){
		events(2,"SMS: mobile: $mobile success to register establishing a new session",null);
		wifidog_redirect_after_login();
		return;
		
	}else{
		events(1,"SMS: mobile: $mobile failed to register with code {$_POST["SMS_CODE"]} ",$GLOBALS["UnLockError"]);
		wifidog_register_sms($GLOBALS["UnLockError"]);
		return;
	}
	
	
}


function wifidog_register_perform(){
	session_start();
	$page=CurrentPageName();
	$tpl=new templates();
	$autocreate_confirmed=0;
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_register_perform()");}
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$ENABLED_SMTP=intval($sock->GET_INFO("ENABLED_SMTP"));
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	
	$email=trim(strtolower($_POST["email"]));
	$q=new mysql_hotspot();
	$password2=trim($_POST["password2"]);
	$password=trim($_POST["password"]);
	
	
	$passphrase=trim(strtolower($_POST["passphrase"]));
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$email: Ask a passphrase: $wifidog_templates->REGISTER_GENERIC_PASSWORD");}
	
	if($wifidog_templates->REGISTER_GENERIC_PASSWORD==1){
		if($passphrase<>trim(strtolower($wifidog_templates->REGISTER_GENERIC_PASSTXT))){
			return wifidog_register($wifidog_templates->char($wifidog_templates->REGISTER_GENERIC_PASSERR));
		}
	}
	
	
	if($password2<>$password){return wifidog_register("{password_mismatch}");}
	
	
	
	if($ENABLED_SMTP==1){
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {return wifidog_register("{error_email_invalid}");}
		$tr=explode("@",$email);
		$domain=$tr[1];
		if (!checkdnsrr($domain, 'MX')) {return wifidog_register("&laquo;$domain&raquo;<br>{error_domain_email_invalid}");}
	}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid,autocreate FROM hotspot_members WHERE `uid`='$email'"));
	
	
	if($ENABLED_REDIRECT_LOGIN==1){
		if(intval($ligne["autocreate"])==1){
			if($ligne["uid"]<>null){
				$q->QUERY_SQL("DELETE FROM hotspot_members WHERE `uid`='$email'");
				$ligne["uid"]=null;
			}
		}
	}

	
	
	if($ligne["uid"]<>null){ 
		if($ENABLED_SMTP==1){$link="<br><strong><a href=\"$page?wifidog-recover=yes&$uriext\">{lost_password}</a></strong>";}
		return wifidog_register($tpl->_ENGINE_parse_body("{this_account_already_exists}<br>$link")); 
	}
	
	
	wifidog_logs("Ruleid: {$_SESSION["WIFIDOG_RULES"]}");
	
	
	$MAC=$_REQUEST["mac"];
	
	$REGISTER_MAX_TIME=intval($sock->GET_INFO("REGISTER_MAX_TIME"));
	$ArticaSplashHotSpotEndTime=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	
	if($REGISTER_MAX_TIME==0){$REGISTER_MAX_TIME=5;}
	wifidog_logs("Ruleid: REGISTER_MAX_TIME:{$REGISTER_MAX_TIME}");
	wifidog_logs("Ruleid: ArticaSplashHotSpotEndTime:{$ArticaSplashHotSpotEndTime}");
	
	$password=md5($password);
	$creationtime=time();
	$autocreate_maxttl=$ArticaSplashHotSpotEndTime;
	$sessionkey=md5($password.$creationtime.$email);
	$gateway_addr=$_REQUEST["gw_address"];
	$gw_port=$_REQUEST["gw_port"];
	$gw_id=$_REQUEST["gw_id"];
	$ARP=$_REQUEST["mac"];
	$url=$_REQUEST["url"];
	$token=$_REQUEST["token"];
	
	if($token==null){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("no token retrive",__FUNCTION__,__LINE__);}
		$token=generateToken($ARP);
		if($token==null){
			$token=md5(uniqid(rand(), 1));
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Generate a temporary token $token",__FUNCTION__,__LINE__);}
		}
	}
	
	if($ENABLED_SMTP==0){$autocreate_confirmed=1;}
	
	
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Create new member $email,$password,TTL:$ArticaSplashHotSpotEndTime",__FUNCTION__,__LINE__);}
	$sql="INSERT IGNORE INTO hotspot_members 
	(uid,username,token,ruleid,ttl,sessiontime,password,enabled,creationtime,autocreate,autocreate_confirmed,autocreate_maxttl,sessionkey,MAC) VALUES
	('$email','$email','$token','{$_SESSION["WIFIDOG_RULES"]}','$ArticaSplashHotSpotEndTime','','$password',1,'$creationtime',1,'$autocreate_confirmed',$autocreate_maxttl,'$sessionkey','$MAC')";
	$q->QUERY_SQL($sql);
	
	wifidog_logs("[$ARP]:[{$GLOBALS["CONNECTED_IP"]}]: $email: Create New member with a token $token",__FUNCTION__,__LINE__);
	

		
	if(!$q->ok){
		events(0,"MySQL error: $q->mysql_error (Line: ".__LINE__.")");
		wifidog_logs("$q->mysql_error");
		wifidog_logs("$sql");
		return wifidog_register($q->mysql_error);
	}	
	
	if($ENABLED_SMTP==0){
			$array["SERVER_NAME"]=$_SERVER["SERVER_NAME"];
			$array["redirecturi"]=$url;
			$array["LOGIN"]=$email;
			$array["redirecturi"]=$_REQUEST["url"];
			$array["REMOTE_ADDR"]=$_REQUEST["ip"];
			$array["token"]=$token;
			$array["HOST"]=$_REQUEST["ip"];
			$array["ruleid"]=$_SESSION["WIFIDOG_RULES"];
			UnLock($array);
			wifidog_logs("wifidog_auth/".__FUNCTION__.":: SESSION(HOTSPOT_REDIRECT_URL) = $url");
			$_SESSION["HOTSPOT_AUTO_REGISTER"]=true;
			$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
			$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
			wifidog_logs("wifidog_auth/".__FUNCTION__.":: Redirect Token: $token to $redirecturi");
			header("Location: $redirecturi");
			return;
	}
	
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-confirm=$sessionkey";

	$smtp_sender=$sock->GET_INFO("smtp_sender");

	
	include_once(dirname(__FILE__)."/ressources/externals/mime/mime.inc");
	$message = new Mail_mime("\r\n");
	
	
	$text = "<p style=font-size:18px>$wifidog_templates->REGISTER_MESSAGE</p>
	<p>	
	<hr>
		<center>
			<a href=\"$URL_REDIRECT\" style='font-size:22px;text-decoration:underline'>$URL_REDIRECT</a>
		</center>
	</p>";
	
	$message->setFrom($smtp_sender);
	$message->addTo($email);
	$message->setSubject($wifidog_templates->REGISTER_SUBJECT);
	$message->setTXTBody(strip_tags($text)); 
	$message->setHTMLBody($text);
	$finalbody = $message->getMessage();
	
	
	$webauth_msmtp=new webauth_msmtp($smtp_sender, $finalbody,$email);
	if(!$webauth_msmtp->Send()){
		events(0,"SMTP Error[method 1]: $webauth_msmtp->logs",$webauth_msmtp->logs);
		
		$content[]="Method 1:\n$webauth_msmtp->logs\n";
		
		
		$smtp=new smtp();
		if($sock->GET_INFO("smtp_auth_user")<>null){
			$params["auth"]=true;
			$params["user"]=$sock->GET_INFO("smtp_auth_user");
			$params["pass"]=$sock->GET_INFO("smtp_auth_passwd");
		}
		$params["host"]=$sock->GET_INFO("smtp_server_name");
		$params["port"]=$sock->GET_INFO("smtp_server_port");
		
		
		if(!$smtp->connect($params)){
			$content[]="Method2 Error:$smtp->error_number $smtp->error_text";
			events(0,"SMTP Error[method 2 - Connect]: {$params["host"]} $smtp->error_number $smtp->error_text",@implode("\n", $content));
			return wifidog_register("$email: {error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
			
		}
			
		
		if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$email,"body"=>$finalbody,"headers"=>null))){
			events(0,"SMTP Error[method 2 - Send]: $smtp->error_number $smtp->error_text",null);
			$smtp->quit();
			return wifidog_register("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text");
		}
		
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("From: $smtp_sender to $email {$wifidog_templates->REGISTER_SUBJECT} success");}
		$smtp->quit();	
	
	
	}
	
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth/".__FUNCTION__.":: Token: $token -> UnLock(..");}
	$array["LOGIN"]=$email;
	$array["ARP"]=$_REQUEST["mac"];
	$array["token"]=$token;
	$array["redirecturi"]=$_REQUEST["url"];
	$array["REMOTE_ADDR"]=$_REQUEST["ip"];
	$array["REGISTER"]=true;
	
	
	if(!UnLock($array)){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("wifidog_auth/".__FUNCTION__."::failed_to_create_session");}
		return wifidog_register("{error} {failed_to_create_session}");
		
	}

	
	wifidog_logs("wifidog_auth/".__FUNCTION__.":: SESSION(HOTSPOT_REDIRECT_URL) = $url");
	$_SESSION["HOTSPOT_AUTO_REGISTER"]=true;
	$_SESSION["HOTSPOT_REDIRECT_URL"]=$url;
	

	$redirecturi="http://$gateway_addr:$gw_port/wifidog/auth?token=$token";
	wifidog_logs("wifidog_auth/".__FUNCTION__.":: Redirect Token: $token to $redirecturi");
	header("Location: $redirecturi");
	
	
}


function BuildFullPage($content,$error=null,$headerAdd=null){
	$prefix=null;
	$tpl=new templates();
	$sock=new sockets();
	
	if($error<>null){
		$content="<p class=text-error>".$tpl->_ENGINE_parse_body($error)."</p>$content";
	}

	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"],$headerAdd);
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("End BuildFullPage ".strlen($content)." bytes");}
	return $wifidog_templates->build($content);

}




?>