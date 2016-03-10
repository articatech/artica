<?php
if(isset($_GET["verbose"])){ini_set_verbosedx();}else{	ini_set('display_errors', 0);ini_set('error_reporting', 0);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
session_save_path('/home/squid/error_page_sessions');
session_cache_expire(10);

if(isset($_POST["smtp-send-email"])){parseTemplate_smtp_post();exit;}
if(isset($_POST["unlock-www"])){parseTemplate_unlock_save();exit;}
if(isset($_POST["unlock-ticket"])){parseTemplate_ticket_save();exit;}

if(isset($_GET["unlock"])){parseTemplate_unlock();exit;}
if(isset($_GET["ticket"])){parseTemplate_ticket();exit;}
if(isset($_GET["release-ticket"])){parseTemplate_release_ticket();exit;}



if(isset($_GET["SquidGuardWebAllowUnblockSinglePass"])){parseTemplate_SinglePassWord();die();}
if(isset($_GET["smtp-send-js"])){parseTemplate_sendemail_js();exit;}
if(isset($_REQUEST["send-smtp-notif"])){parseTemplate_sendemail_perform();exit;}
if(isset($_POST["USERNAME"])){parseTemplate_LocalDB_receive();die();}
if(isset($_GET["SquidGuardWebUseLocalDatabase"])){parseTemplate_LocalDB();die();}
parseTemplate();

function parseTemplate(){
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	include_once(dirname(__FILE__)."/ressources/class.ufdb.microsoft.inc");
	$CATEGORY_SOURCE=null;
	$proto="http";
	$url=$_GET["url"];
	$cacheid=null;
	$HTTP_X_FORWARDED_FOR=null;
	$HTTP_X_REAL_IP=null;
	if(isset($_GET["category"])){$CATEGORY_SOURCE=$_GET["category"];}
	$AS_SSL=false;
	$DisableSquidGuardHTTPCache=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableSquidGuardHTTPCache"));

	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": DisableSquidGuardHTTPCache: $DisableSquidGuardHTTPCache</div>\n";}



	$HTTP_REFERER=null;
	if(isset($_GET["targetgroup"])){
		$TARGET_GROUP_SOURCE=$_GET["targetgroup"];
		if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE=$TARGET_GROUP_SOURCE;}
	}
	$clientgroup=$_GET["clientgroup"];
	$QUERY_STRING=$_SERVER["QUERY_STRING"];
	if(isset($_SERVER["HTTP_REFERER"])){$HTTP_REFERER=$_SERVER["HTTP_REFERER"];}
	$HTTP_REFERER_HOST=hostfrom_url($HTTP_REFERER);
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];}

	$URL_HOST=hostfrom_url($url);
	if(isset($_GET["rule-id"])){$ID=$_GET["rule-id"];}
	if(isset($_GET["fatalerror"])){$ID=0;$cacheid="fatalerror";}
	if(isset($_GET["loading-database"])){$ID=0;$cacheid="loading-database";}
	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";$AS_SSL=true;}}
	$time=date("Ymdh");

	if($AS_SSL){
		if(!isset($_GET["SquidGuardIPWeb"])){
			$requested_uri="https://".$_SERVER["SERVER_NAME"]."/".$_SERVER["REQUEST_URI"];
			$arrayURI=parse_url($requested_uri);
			$requested_hostname=$arrayURI["host"];
		}
	}


	if(preg_match("#&url=(.*?)(&|$)#", $QUERY_STRING,$re)){
		$requested_uri=parseTemplate_string_to_url($re[1]);
		$arrayURI=parse_url($requested_uri);
		$requested_hostname=$arrayURI["host"];
	}

	$GLOBALS["BLOCK_KEY_CACHE"]=md5("$HTTP_X_FORWARDED_FOR$HTTP_X_REAL_IP$time$proto$proto$TARGET_GROUP_SOURCE$clientgroup$requested_hostname$HTTP_REFERER_HOST$URL_HOST$ID$cacheid");
	if($GLOBALS["VERBOSE"]){$DisableSquidGuardHTTPCache=1;}

	if($DisableSquidGuardHTTPCache==0){
		if(is_file("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}")){
			if(parseTemplate_file_time_min("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}")<10){
				echo @file_get_contents("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}");
				return;
			}
		}
	}


	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": TARGET_GROUP_SOURCE $TARGET_GROUP_SOURCE / $requested_hostname</div>\n";}
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": CATEGORY_SOURCE $CATEGORY_SOURCE / $requested_hostname</div>\n";}


	if($TARGET_GROUP_SOURCE=="none"){
		$TARGET_GROUP_SOURCE="{ufdb_none}";
		$EnableSquidGuardSearchCategoryNone=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidGuardSearchCategoryNone"));

		if($CATEGORY_SOURCE==null){
			$EnableSquidGuardSearchCategoryNone=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidGuardSearchCategoryNone"));
			if($EnableSquidGuardSearchCategoryNone==1){
				include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
				$catz=new mysql_catz();
				$CATEGORY_SOURCE=$catz->GET_CATEGORIES($requested_hostname);
				if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE="{unknown}";}
			}
		}
	}

	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": TARGET_GROUP_SOURCE $TARGET_GROUP_SOURCE / $requested_hostname</div>\n";}
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": CATEGORY_SOURCE $CATEGORY_SOURCE / $requested_hostname</div>\n";}





	session_start();
	$HTTP_REFERER=null;
	$template_default_file=dirname(__FILE__)."/ressources/databases/dansguard-template.html";

	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$users=new usersMenus();
	//$q=new mysql_squid_builder();
	$UfdbGuardRedirectCategories=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
	$SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$SquidGuardWebUseLocalDatabase=$sock->GET_INFO("SquidGuardWebUseLocalDatabase");
	$SquidGuardWebBlankReferer=intval($sock->GET_INFO("SquidGuardWebBlankReferer"));

	if(!is_numeric($SquidGuardWebFollowExtensions)){$SquidGuardWebFollowExtensions=1;}
	if(!is_numeric($SquidGuardWebUseLocalDatabase)){$SquidGuardWebUseLocalDatabase=0;}




	if($SquidGuardWebBlankReferer==1){
		if($URL_HOST<>$HTTP_REFERER_HOST){
			$data="<html><head></head><body></body></html>";
			header("Content-Length: ".strlen($data));
			header("Content-Type: text/html");
			echo $data;
			die();
		}
	}

	$GLOBALS["JS_NO_CACHE"]=true;
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";

	if($SquidGuardWebFollowExtensions==1){
		if(parseTemplate_extension($_GET["url"])){return;}
	}

	if(parseTemplateForcejs($_GET["url"])){
		parseTemplateLogs("JS detected : For {$_GET["url"]}",__FUNCTION__,__FILE__,__LINE__);
		header("content-type: application/x-javascript");
		echo "// blocked by url filtering\n";
		return true;
		return;
	}

	$defaultjs="alert('Disabled')";
	$ADD_JS_PACK=false;




	if($SquidGuardWebUseLocalDatabase==1){
		$clientaddr=base64_encode($_GET["clientaddr"]);
		$defaultjs="s_PopUp('{$GLOBALS["JS_HEAD_PREPREND"]}/". basename(__FILE__)."?SquidGuardWebUseLocalDatabase=1&url=".base64_encode("{$_GET["url"]}")."&clientaddr=$clientaddr',640,350)";
		$ADD_JS_PACK=true;
	}

	if($users->CORP_LICENSE){$LICENSE=1;$FOOTER=null;}
	if(!$users->CORP_LICENSE){$LICENSE=0;}
	parseTemplateLogs("{$_GET["clientaddr"]}: Category=`$CATEGORY_SOURCE` targetgroup=`{$_GET["targetgroup"]}` LICENSE:$LICENSE",__FUNCTION__,__FILE__,__LINE__);
	$CATEGORY_KEY=null;
	$_GET["targetgroup"]=parseTemplate_categoryname($TARGET_GROUP_SOURCE,$LICENSE);
	$_GET["clientgroup"]=parseTemplate_categoryname($_GET["clientgroup"],$LICENSE);
	$_GET["category"]=parseTemplate_categoryname($CATEGORY_SOURCE,$LICENSE);
	$CATEGORY_KEY=parseTemplate_categoryname($CATEGORY_SOURCE,$LICENSE,1);
	if($CATEGORY_KEY==null){
		$CATEGORY_KEY=parseTemplate_categoryname($TARGET_GROUP_SOURCE,$LICENSE,1);
	}



	$_CATEGORIES_K=$_GET["category"];




	$_RULE_K=$_GET["clientgroup"];
	if($_CATEGORIES_K==null){$_CATEGORIES_K=$_GET["targetgroup"];}




	if($_RULE_K==null){$_RULE_K="{web_filtering}";}
	$REASONGIVEN="{web_filtering} $_CATEGORIES_K";

	if($_CATEGORIES_K=="restricted_time"){$REASONGIVEN="{restricted_access}";}

	parseTemplateLogs("{$REASONGIVEN}: _CATEGORIES_K=`$_CATEGORIES_K` _RULE_K=$_RULE_K` LICENSE:$LICENSE",__FUNCTION__,__FILE__,__LINE__);
	$IpToUid=null;
	//$IpToUid=$q->IpToUid($_GET["clientaddr"]);
	if($IpToUid<>null){$IpToUid="&nbsp;($IpToUid)";}

	if($LICENSE==1){
		if($CATEGORY_KEY<>null){
			$RedirectCategory=$UfdbGuardRedirectCategories[$CATEGORY_KEY];
				
			if($RedirectCategory["enable"]==1){
				if($RedirectCategory["blank_page"]==1){
					parseTemplateLogs("[$CATEGORY_KEY]: blank_page : For {$_GET["url"]}",__FUNCTION__,__FILE__,__LINE__);
					header("HTTP/1.1 200 OK");
					die();
					return;
				}
				if(trim($RedirectCategory["template_data"])<>null){
					header('Content-Type: text/html; charset=iso-8859-1');
					$TemplateErrorFinal=$RedirectCategory["template_data"];
					return;
				}
			}
		}
	}

	$EnableSquidFilterWhiteListing=$sock->GET_INFO("EnableSquidFilterWhiteListing");


	if($LICENSE==1){
		if(is_numeric($ID)){
			if($ID==0){
				$ligne["groupname"]="Default";
			}else{
				$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
				$q=new mysql_squid_builder();
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
				$ruleName=$ligne["groupname"];
			}
				
				
				
		}else{
			writelogs("ID: not a numeric",__FUNCTION__,__FILE__,__LINE__);
		}
	}



	if(isset($_GET["fatalerror"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$REASONGIVEN="{webfiltering_issue}";
		$_CATEGORIES_K="{system_Webfiltering_error}";
		$_RULE_K="{service_error}";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];
	}

	if(isset($_GET["loading-database"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$REASONGIVEN="{Webfiltering_maintenance}";
		$_CATEGORIES_K="{please_wait_reloading_databases}";
		$_RULE_K="{waiting_service}....";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];

	}

	if(!isset($_SESSION["IPRES"][$_GET["clientaddr"]])){$_SESSION["IPRES"][$_GET["clientaddr"]]=gethostbyaddr($_GET["clientaddr"]);}
	if(isset($_GET["source"])){$_GET["clientaddr"]=$_GET["source"];}
	if(isset($_GET["user"])){$_GET["clientname"]=$_GET["user"];}
	if(isset($_GET["virus"])){$_GET["targetgroup"]=$_GET["virus"];$ruleName=null;}
	if($_GET["clientuser"]<>null){$_GET["clientname"]=$_GET["clientuser"];}
	$ruleName=parseTemplate_categoryname($ruleName,$LICENSE);

	$ARRAY["URL"]=$_GET["url"];
	$ARRAY["IPADDR"]=$_GET["clientaddr"];
	$ARRAY["REASONGIVEN"]=$REASONGIVEN;
	$ARRAY["CATEGORY_KEY"]=$CATEGORY_KEY;
	$ARRAY["RULE_ID"]=$ID;

	$ARRAY["CATEGORY"]=$_CATEGORIES_K;
	$ARRAY["RULE"]=$_RULE_K;
	if($ruleName<>null){
		$ARRAY["RULE"]=$ruleName;
	}
	$ARRAY["targetgroup"]=$_GET["targetgroup"];
	$ARRAY["IpToUid"]=$IpToUid;
	$ARRAY["clientname"]=$_GET["clientname"];
	$ARRAY["HOST"]=$_SESSION["IPRES"][$_GET["clientaddr"]];


	$GLOBALS["BLOCK_KEY_CACHE"];
	$Content=parseTemplate_build_main($ARRAY);
	@file_put_contents("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}", $Content);
	echo $Content;

}


function parseadmin($emailTemplate,$subj){

	$CacheManager=CacheManager();
	$subject=rawurlencode("Web Filtering complain [$subj]");
	$emailTemplate=rawurlencode($emailTemplate);
	return "<a href=\"mailto:$CacheManager?subject=$subject&body=$emailTemplate\">$CacheManager</a>";

}
function parseTemplate_file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}

	$data1 = filemtime($path);
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}
function parseTemplate_events($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/artica-webpage-error.log";
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');

	@fwrite($f, "$date:$text  - $line\n");
	@fclose($f);
}

function parseTemplate_string_to_url($url){
	$url=str_replace("%3A", ":", $url);
	$url=str_replace("%2F", "/", $url);
	$url=str_replace("%3D","=",$url);
	$url=str_replace("%3F","?",$url);
	$url=str_replace("%20"," ",$url);
	$url=str_replace("%25",'%',$url);
	$url=str_replace("%40","@",$url);
	return $url;
}

function parseTemplate_smtp_post(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_POST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$serialize_array=$_POST["serialize"];



	$_RULE_K=$ARRAY["RULE"];
	$IPADDR=$ARRAY["IPADDR"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$URL=$ARRAY["URL"];
	$HOST=$ARRAY["HOST"];

	$members[]=$IPADDR;
	if($HOST<>null){$members[]=$HOST; }
	if(trim($IpToUid)<>null){$members[]=$IpToUid;}
	if(count($members)>0){while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
	$members=array();
	while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}}
	$membersTX=@implode(", ", $members);


	$email=$_POST["email"];

	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$error=parseTemplate_sendemail_perform($email,$ARRAY);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];

	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);


	$f[]=parseTemplate_headers($notify_your_administrator,null,$SquidGuardIPWeb);
	$f[]="    <h2>{notify_your_administrator}</h2>";
	if($error<>null){$f[]="    <h2>$error</h2>";}
	$f[]="<div id=\"info\">";
	$f[]="";
	$f[]="<form id='send-email-form' action=\"$SquidGuardIPWeb\" method=\"post\">
	<input type='hidden' name='smtp-send-email' value='yes'>
	<input type='hidden' name='email' value='$email'>
	<input type='hidden' name='serialize' value='$serialize_array'>";
	$f[]="<table width='100%;'>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>
	<p style='margin-top:50px'>&nbsp;</p>";





	if($email==null){
		$f[]="<table width='100%;'>";


		$f[]="
	<tr>
		<td class=\"info_title\">{email}:</td>
		<td class=\"info_content\">".Field_text("email",$_REQUEST["email"],"$fontfamily;width:80%;font-size:35px;padding:5px"
				,null,null,null,false,"CheckTheForm(event)")."</td>
	</tr>
	";
		$f[]=" <tr><td colspan=2 align='right'><p style='margin-top:50px'>&nbsp;</p></td></tr>";
		$f[]=" <tr><td colspan=2 align='right'><hr>". button("{submit}","document.forms['send-email-form'].submit();")."</td></tr>
	</table>";
	}
	$f[]="
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['send-email-form'].submit();
		}

	</script>
	";


	$f[]="</div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));




}
function parseTemplate_sendemail_perform($smtp_sender=null,$ARRAY,$ticket=false,$SquidGuardIPWeb=null){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");


	if(!$ticket){
		if($smtp_sender==null){
			$tpl=new templates();
			return $tpl->_ENGINE_parse_body("{give_your_email_address}");
		}
	}
	$main_array=base64_encode(serialize($ARRAY));
	$tpl=new templates();
	$HOST=$ARRAY["HOST"];
	$clientname=$ARRAY["clientname"];
	if($clientname<>null){$clientname="/$clientname";}
	$Subject="Web filter request from an user: {$HOST}$clientname";
	$zDate=date('Y-m-d H:i:s');
	$q=new mysql_squid_builder();
	$URL=$ARRAY["URL"];
	$REASONGIVEN=$ARRAY["REASONGIVEN"];


	unset($ARRAY["SquidGuardIPWeb"]);
	unset($ARRAY["URL"]);
	unset($ARRAY["REASONGIVEN"]);


	while (list ($a, $b) = each ($ARRAY) ){
		$body[]="$a\t:$b";


	}

	$text=mysql_escape_string2(@implode($body, "\r\n"));
	$Subject=mysql_escape_string2($Subject);
	$URL=mysql_escape_string2($URL);
	$REASONGIVEN=mysql_escape_string2($REASONGIVEN);
	$md5=md5(serialize($ARRAY)."$Subject $smtp_sender");
	$ticket_val=0;
	if($ticket){$ticket_val=1;}

	$tablename="ufdb_smtp";
	if($q->COUNT_ROWS("ufdb_smtp")==0){
		$q->QUERY_SQL("DROP TABLE ufdb_smtp");
	}
	$sql="CREATE TABLE IF NOT EXISTS `ufdb_smtp` (
	`zmd5` varchar(90) NOT NULL,
	`zDate` datetime NOT NULL,
	`Subject` varchar(255) NOT NULL,
	`content` varchar(255) NOT NULL,
	`main_array` TEXT,
	`URL` varchar(255) NOT NULL,
	`REASONGIVEN` varchar(255) NOT NULL,
	`sender` varchar(128) NOT NULL,
	`retrytime` smallint(1) NOT NULL,
	`ticket` smallint(1) NOT NULL,
	`SquidGuardIPWeb` varchar(255),
	PRIMARY KEY (`zmd5`),
	KEY `zDate` (`zDate`),
	KEY `Subject` (`Subject`),
	KEY `sender` (`sender`),
	KEY `ticket` (`ticket`),
	KEY `retrytime` (`retrytime`)

	) ENGINE=MYISAM;";

	$q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;return;}

		if(!$q->FIELD_EXISTS("ufdb_smtp", "SquidGuardIPWeb")){
			$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `SquidGuardIPWeb` varchar(255)");
		}
		if(!$q->FIELD_EXISTS("ufdb_smtp", "ticket")){
			$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `ticket` smallint(1) NOT NULL");
		}
		if(!$q->FIELD_EXISTS("ufdb_smtp", "main_array")){
			$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `main_array` TEXT");
		}




		$q->QUERY_SQL("INSERT IGNORE INTO ufdb_smtp (`zmd5`,`zDate`,`Subject`,`content`,`sender`,`URL`,
				`REASONGIVEN`,`retrytime`,`SquidGuardIPWeb`,`ticket`,`main_array`) VALUES
				('$md5',NOW(),'$Subject','$text','$smtp_sender','$URL','$REASONGIVEN','0','$SquidGuardIPWeb','$ticket_val','$main_array')");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			return $q->mysql_error_html();
			return false;
		}


		$sock=new sockets();
		$sock->getFrameWork("squidguardweb.php?smtp-notifs=yes");

		$tpl=new templates();
		return $tpl->_ENGINE_parse_body("{your_query_was_sent_to_administrator}");

		return true;


}

function parseTemplate_build_main($ARRAY){
	$sock=new sockets();
	$page=CurrentPageName();
	if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=null;}
	if($GLOBALS["ARTICA_VERSION"]==null){$GLOBALS["ARTICA_VERSION"]=trim(@file_get_contents(dirname(__FILE__)."/VERSION"));}

	$version=$GLOBALS["ARTICA_VERSION"];
	$FOOTER=null;
	$users=new usersMenus();
	$HOST=$ARRAY["HOST"];
	$URL=$ARRAY["URL"];
	$IPADDR=$ARRAY["IPADDR"];
	$REASONGIVEN=$ARRAY["REASONGIVEN"];
	$_CATEGORIES_K=$ARRAY["CATEGORY"];
	$_RULE_K=$ARRAY["RULE"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$SquidGuardIPWeb=base64_decode($_GET["SquidGuardIPWeb"]);
	$client_username=$ARRAY["clientname"];
	$hostname=$sock->GET_INFO("myhostname");

	$ARRAY["Proxy Server"]=$hostname;
	$sock->BuildTemplatesConfig($ARRAY);
	$EnableSquidGuardMicrosoftTPL=intval($sock->GET_INFO("EnableSquidGuardMicrosoftTPL"));
	$SquidHTTPTemplateSmiley=intval($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]);

	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: {$GLOBALS["UfdbGuardHTTP"]["NoVersion"]}</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":SquidHTTPTemplateSmileyEnable: {$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"]} / {$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]}</li>";}
	if($GLOBALS["VERBOSE"]){echo "</div>";}


	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"])){
		$SquidHTTPTemplateSmileyEnable=1;
	}else{
		$SquidHTTPTemplateSmileyEnable=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"];
	}

	$BackgroundColorBLKBT=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"];

	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}

	if($IPADDR==null){
		$IPADDR=parseTemplate_GET_REMOTE_ADDR();
	}

	if($HOST==null){
		$HOST=$_SERVER["HTTP_HOST"];
	}

	if($URL==null){
		$proto="http";
		if(isset($_SERVER["HTTPS"])){
			if($_SERVER["HTTPS"]=="on"){
				$proto="https";
			}
		}
		$URL="$proto://$HOST{$_SERVER["REQUEST_URI"]}";

	}

	if($SquidGuardIPWeb==null){
		$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
		$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
		$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
		if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
		if(!preg_match("#\/\/(.+?):$SquidGuardApachePort#", $SquidGuardIPWeb)){
			if($SquidGuardServerName<>null){
				$SquidGuardIPWeb="http://$SquidGuardServerName:$SquidGuardApachePort";
			}
		}

	}

	if(strpos($SquidGuardIPWeb, $page)==0){
		if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb = $SquidGuardIPWeb require $page</H1>";}
		$SquidGuardIPWeb="$SquidGuardIPWeb/$page";
	}


	if($GLOBALS["VERBOSE"]){echo "<H1>$SquidGuardIPWeb</H1>";}

	$UfdbGuardHTTPUnbblockMaxTime=intval($sock->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
	$UfdbGuardHTTPDisableHostname=intval($sock->GET_INFO("UfdbGuardHTTPDisableHostname"));
	$UfdbGuardHTTPUnbblockText2=$sock->GET_INFO("UfdbGuardHTTPUnbblockText2");
	$UfdbGuardHTTPEnablePostmaster=$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"];
	$UfdbGuardHTTPNoVersion=$GLOBALS["UfdbGuardHTTP"]["NoVersion"];
	$UfdbGuardHTTPAllowUnblock=$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"];

	if($UfdbGuardHTTPEnablePostmaster==1){
		$emailTemplate="URL:{$_GET["url"]}\nIP:{$_GET["clientaddr"]}\nREASON:$REASONGIVEN\nCategory:$_CATEGORIES_K\nrule:$_RULE_K";
		$Postmaster=parseadmin($emailTemplate,$URL);
	}

	$UfdbGuardHTTPAllowSMTP=intval($sock->GET_INFO("UfdbGuardHTTPAllowSMTP"));
	if($UfdbGuardHTTPAllowSMTP==1){
		$UfdbGuardHTTPEnablePostmaster=1;
		$Postmaster=parseTemplate_smtp_button($ARRAY,$SquidGuardIPWeb);
	}

	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$UFDBGUARD_TITLE_1=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"];
	$UFDBGUARD_PARA1=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"];
	$UFDBGUARD_PARA2=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"];
	$UFDBGUARD_TITLE_2=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"];
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TICKET_LINK"];
	$UfdbGuardHTTPDisableHostname=$GLOBALS["UfdbGuardHTTP"]["UfdbGuardHTTPDisableHostname"];

	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>UfdbGuardHTTPDisableHostname: $UfdbGuardHTTPDisableHostname</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>UfdbGuardHTTPNoVersion: $UfdbGuardHTTPNoVersion</li>";}

	if($GLOBALS["VERBOSE"]){echo "</div>";}



	$f[]=parseTemplate_headers("$UFDBGUARD_TITLE_1 - $_CATEGORIES_K",null,$SquidGuardIPWeb);
	$f2[]=microsoft_ufdb_template("$UFDBGUARD_TITLE_1",null,$SquidGuardIPWeb);

	$f2[]="<p style='font-size:25px'>$REASONGIVEN</p>";


	if($SquidHTTPTemplateSmileyEnable==1){
		$f[]="    <h1 class=bad></h1>";
	}
	if(trim(strtolower($UFDBGUARD_TITLE_1))<>"none"){
		$f[]="    <h2>$UFDBGUARD_TITLE_1</h2>    ";
	}
	$f[]="    <h2>$REASONGIVEN</h2>    ";

	if(trim(strtolower($UFDBGUARD_PARA1))<>"none"){
		$f[]="    <p>$UFDBGUARD_PARA1</p>";
		$f2[]="    <p>$UFDBGUARD_PARA1</p>";
	}
	if(trim(strtolower($UFDBGUARD_TITLE_2))<>"none"){
		$f[]="    <h3>$UFDBGUARD_TITLE_2</h3>";
		$f2[]="    <p style='font-size:25px'>$UFDBGUARD_TITLE_2</p>";
	}
	if(trim(strtolower($UFDBGUARD_PARA2))<>"none"){
		$f[]="    <p>$UFDBGUARD_PARA2</p>    ";
		$f2[]="    <p>$UFDBGUARD_PARA2</p>";
	}
	$f[]="    ";
	$f[]="    <div id=\"info\">";
	$f[]="    <table width='100%'>";

	if($client_username<>null){
		$members[]=$client_username;

	}


	$members[]=$IPADDR;
	if($HOST<>null){
		$members[]=$HOST;
	}

	if(trim($IpToUid)<>null){
		$members[]=$IpToUid;
	}

	if(count($members)>0){
		while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
		$members=array();
		while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}

	}

	$membersTX=@implode(", ", $members);
	$f2[]="<UL class=\"tasks\" id=\"cantDisplayTasks\">";
	if($UfdbGuardHTTPDisableHostname==0){
		$hostname=$sock->GET_INFO("myhostname");
		if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
		$f[]="        <tr><td class=\"info_title\">{proxy_server}:</td><td class=\"info_content\">$hostname</td></tr>";
		$f2[]="<li><strong>{proxy_server}</strong>: $hostname</li>";
	}

	if($GLOBALS["VERBOSE"]){echo "<span style='font-size:16px'>UfdbGuardHTTPEnablePostmaster:$UfdbGuardHTTPEnablePostmaster</span><br>\n";}

	if($UfdbGuardHTTPEnablePostmaster==1){
		$f[]="        <tr><td class=\"info_title\">{administrator}:</td><td class=\"info_content\">$Postmaster</td></tr>";
		$f2[]="<li><strong>{administrator}</strong>: $Postmaster</li>";
	}
	if($UfdbGuardHTTPNoVersion==0){
		$f2[]="<li><strong>{application}</strong>: Version $version</li>";
		$f[]="        <tr><td class=\"info_title\">{application}:</td><td class=\"info_content\">Version $version</td></tr>";
	}


	if($targetgroup=="restricted_time"){$targetgroup="{restricted_access}";}
	$f2[]="<li><strong>{member}</strong>: $membersTX</li>";
	$f2[]="<li><strong>{policy}</strong>: $_RULE_K, $targetgroup</li>";
	$f2[]="<li><strong>{requested_uri}</strong>: $URL</li>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>";

	$NOUNBLOCK=false;
	if(isset($_GET["fatalerror"])){$NOUNBLOCK=true;}
	if(isset($_GET["loading-database"])){$NOUNBLOCK=true;}
	$AllowTicket=0;

	$q=new mysql_squid_builder();
	$CountOfufdb_page_rules=$q->COUNT_ROWS("ufdb_page_rules");
	parseTemplate_debug("ufdb_page_rules: $CountOfufdb_page_rules", __LINE__);


	if($CountOfufdb_page_rules>0){
		include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
		$unlock=new parse_template_ufdb();

		if($GLOBALS["VERBOSE"]){echo "<hr style='border-color:#35CA61'>\n";}
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}
		$UfdbGuardHTTPAllowUnblock=$unlock->parseTemplate_unlock_privs($ARRAY,"allow=1",$UfdbGuardHTTPAllowUnblock);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>allow: UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}
		$UfdbGuardHTTPAllowUnblock=$unlock->parseTemplate_unlock_privs($ARRAY,"deny=1",$UfdbGuardHTTPAllowUnblock);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>Deny: UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}

		$AllowTicket=$unlock->parseTemplate_unlock_privs($ARRAY,"ticket=1",0);
		if($AllowTicket==1){$UfdbGuardHTTPAllowUnblock=0;}
	}

	$f2[]="</ul>";

	if($UfdbGuardHTTPAllowUnblock==1){

		if(!$NOUNBLOCK){
			$URL_ENCODED=urlencode($URL);
			$IPADDR_ENCODE=urlencode($IPADDR);
			$page=CurrentPageName();
			$SquidGuardIPWeb_enc=urlencode($SquidGuardIPWeb);
			$unlock_web_site_text="{unlock_web_site}";
			if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}
				
			if(isset($GLOBALS["RULE_MAX_TIME"])){$ARRAY["RULE_MAX_TIME"]=$GLOBALS["RULE_MAX_TIME"];}
				
			$ARRAY_SERIALIZED=urlencode(base64_encode(serialize($ARRAY)));
			$unlock_text="<p>{$GLOBALS["UfdbGuardHTTP"]["UnbblockText1"]}</p>
			<div style='text-align:right;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};padding-top:5px'>
			<a href='$SquidGuardIPWeb?unlock=yes&url=$URL_ENCODED&ipaddr=$IPADDR_ENCODE&SquidGuardIPWeb=$SquidGuardIPWeb_enc&clientname={$ARRAY["clientame"]}&serialize=$ARRAY_SERIALIZED' class=important>
			$unlock_web_site_text</a></div>";
				
			$f[]=$unlock_text;
			$f2[]=$unlock_text;
		}
	}

	if($AllowTicket==1){
		$URL_ENCODED=urlencode($URL);
		$IPADDR_ENCODE=urlencode($IPADDR);
		$page=CurrentPageName();
		$SquidGuardIPWeb_enc=urlencode($SquidGuardIPWeb);
		$ticket_web_site_text="{submit_a_ticket}";
		if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}
		$ARRAY_SERIALIZED=urlencode(base64_encode(serialize($ARRAY)));
		$unlock_text="<p>{$GLOBALS["UfdbGuardHTTP"]["TICKET_TEXT"]}</p>
		<div style='text-align:right;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};padding-top:5px'>
		<a href='$SquidGuardIPWeb?ticket=yes&url=$URL_ENCODED&ipaddr=$IPADDR_ENCODE&SquidGuardIPWeb=$SquidGuardIPWeb_enc&clientname={$ARRAY["clientame"]}&serialize=$ARRAY_SERIALIZED' class=important>
		$ticket_web_site_text</a></div>";
		$f[]=$unlock_text;
		$f2[]=$unlock_text;
	}

	$f2[]="$FOOTER</DIV>";
	$f2[]="</DIV>";
	$f2[]="</BODY>";
	$f2[]="</HTML>";

	if(!isset($_SESSION["UFDB_PAGE_LANG"])){
		if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/ressources/class.langages.inc");}
		$langAutodetect=new articaLang();
		$_SESSION["UFDB_PAGE_LANG"]=$langAutodetect->get_languages();

	}


	$tpl=new templates();

	$tpl->language=$_SESSION["UFDB_PAGE_LANG"];

	if($EnableSquidGuardMicrosoftTPL==1){
		return $tpl->_ENGINE_parse_body(@implode("\n", $f2));

	}



	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	while (list ($num, $ligne) = each ($ARRAY) ){
		$f[]="    $num = $ligne";
	}

	$f[]=" Language : $tpl->language";
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

	return $tpl->_ENGINE_parse_body(@implode("\n", $f));
}
function parseTemplate_debug($text,$line){
	if(!$GLOBALS["VERBOSE"]){return;}
	echo "<p style='color:yellow'>$text ( in line $line )</p>\n";
}

function wifidog_build_uri(){
	reset($_REQUEST);
	while (list ($num, $ligne) = each ($_REQUEST) ){
		if($num=="unlock-www"){continue;}
		if($num=="unlock"){continue;}

		$URIZ[]="$num=".urlencode($ligne);
		$inputz[]="<input type='hidden' id='$num' name='$num' value='$ligne'>";

	}

	return array(@implode("&", $URIZ),@implode("\n", $inputz));

}

function parseTemplate_unlock_checkcred(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
	include_once(dirname(__FILE__)."/ressources/settings.inc");
	$sock=new sockets();
	
	
	$UfdbGuardHTTPAllowNoCreds=intval($_POST["UfdbGuardHTTPAllowNoCreds"]);
	
	
	
	if($UfdbGuardHTTPAllowNoCreds==1){return true;}
	if(isset($_POST["nocreds"])){if(intval($_POST["nocreds"])==1){return true;} }
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	
	
	if($username==null){return false;}
	if($password==null){return false;}

	if($sock->SQUID_IS_EXTERNAL_LDAP()){
		$ldap_extern=new ldap_extern();
		if($ldap_extern->checkcredentials($username, $password)){return true;}


	}




	if(trim(strtolower($username))==trim(strtolower($_GLOBAL["ldap_admin"]))){
		if($password==trim($_GLOBAL["ldap_password"])){return true;}
	}

	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($username,$password)){
			return true;
		}
	}



	$q=new mysql();
	$sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$username' AND `attribute`='Cleartext-Password' LIMIT 0,1";

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	if(!$q->ok){writelogs("$username:: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}

	if($ligne["id"]>0){
		if($ligne["value"]==$password){return true; }
	}


	$u=new user($username);
	if(trim($u->uidNumber)<>null){
		if(trim($password)==trim($u->password)){return true; }
	}


	return false;
}
function parseTemplate_ticket_save(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");

	$sock=new sockets();
	$q=new mysql_squid_builder();
	$tpl=new templates();

	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$finalhost=$_POST["finalhost"];
	$IPADDR=$_REQUEST["ipaddr"];
	$user=$_REQUEST["username"];
	$SquidGuardIPWeb=$_REQUEST["SquidGuardIPWeb"];
	$familysite=$q->GetFamilySites($finalhost);

	$_RULE_K=$ARRAY["RULE"];
	$IPADDR=$ARRAY["IPADDR"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$URL=$ARRAY["URL"];
	$HOST=$ARRAY["HOST"];

	$members[]=$IPADDR;
	if($HOST<>null){$members[]=$HOST; }
	if(trim($IpToUid)<>null){$members[]=$IpToUid;}
	if(count($members)>0){while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
	$members=array();
	while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}}
	$membersTX=@implode(", ", $members);

	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");

	$ticket_web_site_text="{submit_a_ticket}";
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$TICKET_TEXT_SUCCESS=$GLOBALS["UfdbGuardHTTP"]["TICKET_TEXT_SUCCESS"];
	if($TICKET_TEXT_SUCCESS==null){$TICKET_TEXT_SUCCESS="{ufdb_ticket_text_success}";}

	if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}

	$cssform="  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
  background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  background: rgb(255,255,255); /* Old browsers */
  background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(47%,rgba(246,246,246,1)), color-stop(100%,rgba(237,237,237,1))); /* Chrome,Safari4+ */
  background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Chrome10+,Safari5.1+ */
  background: -o-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Opera 11.10+ */
  background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* IE10+ */
  background: linear-gradient(to bottom, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* W3C */
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 ); /* IE6-9 */

  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);";


	$MAX=intval($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]);
	$url=$_REQUEST["url"];
	$md5=md5($finalhost.$IPADDR.$user);
	$q->QUERY_SQL("INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) VALUES ('$md5','$IPADDR','$familysite','$user')");
	$function=__FUNCTION__;
	$file=basename(__FILE__);
	$line=__LINE__;
	$subject="Unlock website ticket $finalhost/$familysite from $user/$IPADDR";







	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
			(NOW(),'','$subject','$function','$file','$line','1','{$_SERVER["SERVER_NAME"]}')","artica_events");
	if(!$q->ok){
		$redirect=null;
		$MAIN_BODY="<center style='margin:20px;padding:20px;$cssform;color:black;width:80%'>
		<H1>Oups!</H1><hr>".$q->mysql_error_html()."</center>";

	}
	$error=parseTemplate_sendemail_perform(null,$ARRAY,true,$SquidGuardIPWeb);
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];

	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);


	$f[]=parseTemplate_headers($ticket_web_site_text,null,$SquidGuardIPWeb);
	$f[]="    <h2>{notify_your_administrator}</h2>";
	if($error<>null){$f[]="    <h2>$error</h2>";}
	$f[]="<h3>$TICKET_TEXT_SUCCESS</h3>";
	$f[]="<div id=\"info\" style='margin-top:20px'>";

	$f[]="<form id='send-email-form' action=\"$SquidGuardIPWeb\" method=\"post\">";
	$f[]="<table width='100%;'>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>
	<p style='margin-top:50px'>&nbsp;</p>";
	$f[]="
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['send-email-form'].submit();
		}

	</script>
	";


	$f[]="</div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));




}

function parseTemplate_unlock_save($noauth=false,$ARRAYCMD=array(),$noredirect=false){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$cssform="  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
  background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);";

	if(!$noauth){
		if(!parseTemplate_unlock_checkcred()){
			parseTemplate_unlock("{wrong_password_or_username}");
			die();
		}
	}

	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock=new sockets();
	$sock->BuildTemplatesConfig($ARRAY);
	$q=new mysql_squid_builder();
	$finalhost=$_POST["finalhost"];
	$IPADDR=$_REQUEST["ipaddr"];
	$user=$_REQUEST["username"];
	$url=$_REQUEST["url"];
	$SquidGuardIPWeb=$_REQUEST["SquidGuardIPWeb"];

	if(count($ARRAYCMD)>3){
		$IPADDR=$ARRAY["IPADDR"];
		$user=$ARRAY["clientname"];
		$url=$ARRAY["URL"];
		$H=parse_url($url);
		$finalhost=$H["host"];

	}


	$MAX=intval($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]);



	if(isset($ARRAY["RULE_MAX_TIME"])){
		if(intval($ARRAY["RULE_MAX_TIME"])>0){
			$MAX=$ARRAY["RULE_MAX_TIME"];
		}
	}

	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ufdbunlock` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`uid` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR( 90 ) NULL,
			`www` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			KEY `MAC` (`MAC`),
			KEY `logintime` (`logintime`),
			KEY `finaltime` (`finaltime`),
			KEY `uid` (`uid`),
			KEY `www` (`www`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";

	$q->QUERY_SQL($sql);
	if(!$q->ok){parseTemplate_unlock($q->mysql_error);return;}


	if($MAX==0){$MAX=60;}



	$familysite=$q->GetFamilySites($finalhost);
	include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
	$unlock=new parse_template_ufdb();
	$addTocategory=$unlock->parseTemplate_unlock_privs($ARRAY,"addTocat=1",null);

	if(!isset($ARRAY["RULE_MAX_TIME"])){
		if(isset($GLOBALS["RULE_MAX_TIME"])){
			if(intval($GLOBALS["RULE_MAX_TIME"])>0){
				$MAX=$GLOBALS["RULE_MAX_TIME"];
			}
		}
	}

	$md5=md5($finalhost.$IPADDR.$user);
	$time=time();
	$EnOfLife = strtotime("+{$MAX} minutes", $time);

	$NextLogs=$EnOfLife-$time;
	writelogs("$finalhost $IPADDR $user Alowed for {$MAX} minutes, EndofLife=$EnOfLife in {$NextLogs} seconds",__FUNCTION__,__FILE__,__LINE__);

	$q->QUERY_SQL("INSERT IGNORE INTO `ufdbunlock` (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`)
			VALUES('$md5','$time','$EnOfLife','$user','$familysite','$IPADDR')");
	if(!$q->ok){
		writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		parseTemplate_unlock($q->mysql_error);
		return;
	}




	if($addTocategory<>null){
		writelogs("Saving $familysite  into $addTocategory",__FUNCTION__,__FILE__,__LINE__);
		$q->ADD_CATEGORYZED_WEBSITE($familysite, $addTocategory);
	}


	$q->QUERY_SQL("INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid)
			VALUES ('$md5','$IPADDR','$familysite','$user')");
	$function=__FUNCTION__;
	$file=basename(__FILE__);
	$line=__LINE__;
	$subject="Unlocked website $finalhost/$familysite from $user/$IPADDR";
	$redirect="<META http-equiv=\"refresh\" content=\"10; URL=$url?ufdbtime=".time()."\">";

	$redirecting_text=$tpl->javascript_parse_text("{redirecting}");

	$redirect_text="{please_wait_redirecting_to}<br>$url<br><{for} $MAX {minutes}";

	if($noredirect==true){
		$redirect=null;
		$redirect_text="{unlock}<br>$url<br><{for} $MAX {minutes}";
		$redirecting_text=$tpl->javascript_parse_text("{done}");
	}

	$MAIN_BODY="<center>
	<div id='maincountdown' style='width:100%'>
	<center style='margin:20px;padding:20px;$cssform;color:black;width:80%' >
	<input type='hidden' id='countdownvalue' value='10'>
	<span id='countdown' style='font-size:70px'></span>
	</center>
	</div>
	<p style='font-size:22px'>
	<center style='margin:50px;$cssform;color:black;width:80%'>
	$redirect_text
	<center style='margin:20px;font-size:70px' id='wait_verybig_mini_red'>
	<img src='img/wait_verybig_mini_red.gif'>
	</center>
	</center>
	</p>
	</center>
	<script>



	setInterval(function () {
	var countdown = document.getElementById('countdownvalue').value
	countdown=countdown-1;
	if(countdown==0){
	document.getElementById('countdownvalue').value=0;
	document.getElementById('wait_verybig_mini_red').innerHTML='$redirecting_text';
	document.getElementById('maincountdown').innerHTML='';

	return;
}
document.getElementById('countdownvalue').value=countdown;
document.getElementById('countdown').innerHTML=countdown

}, 1000);
</script>";


	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
			(NOW(),'','$subject','$function','$file','$line','1','{$_SERVER["SERVER_NAME"]}')","artica_events");
	if(!$q->ok){
	$redirect=null;
	$MAIN_BODY="<center style='margin:20px;padding:20px;$cssform;color:black;width:80%'>
	<H1>Oups!</H1><hr>".$q->mysql_error_html()."</center>";

	}



	if($redirect<>null){
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "<H1 style='color:white'>squid.php?reconfigure-unlock=yes</H1>";}
		$sock->getFrameWork("squid.php?reconfigure-unlock=yes");
	}
	if($noredirect){
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "<H1 style='color:white'>squid.php?reconfigure-unlock=yes</H1>";}
		$sock->getFrameWork("squid.php?reconfigure-unlock=yes");
	}

	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}

	$f[]=parseTemplate_headers($unlock_web_site_text,$redirect);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$f[]=$MAIN_BODY;
	$f[]=$FOOTER;
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));

}
function parseTemplate_unlock($error=null){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$SquidGuardIPWeb=null;
	$url=$_REQUEST["url"];
	$IPADDR=$_REQUEST["ipaddr"];
	if(isset($_GET["SquidGuardIPWeb"])){$SquidGuardIPWeb=$_GET["SquidGuardIPWeb"];}
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb=CurrentPageName();}
	if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb=$SquidGuardIPWeb</H1>";}
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));

	$q=new mysql_squid_builder();
	$parse_url=parse_url($url);
	$host=$parse_url["host"];
	if(preg_match("#(.+?):[0-9]+#", $host,$re)){$host=$re[1];}
	$FinalHost=$q->GetFamilySites($host);

	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$MAX=$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"];
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[240]="4 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[2880]="2 {days}";
	$TEXT_TIME=$Timez[$MAX];
	$UnbblockText2=$GLOBALS["UfdbGuardHTTP"]["UnbblockText2"];
	$page=CurrentPageName();
	$UnbblockText2=str_replace("%WEBSITE%", $url, $UnbblockText2);
	$UnbblockText2=str_replace("%TIME%", $TEXT_TIME, $UnbblockText2);
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);

	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];



	$client_username=$ARRAY["clientname"];
	if($client_username<>null){$_REQUEST["clientname"]=$client_username;}

	if($q->COUNT_ROWS("ufdb_page_rules")>0){
		include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
		$unlock=new parse_template_ufdb();
		$noauth=$unlock->parseTemplate_unlock_privs($ARRAY,$pattern="noauth=1",0,true);
		$UfdbGuardHTTPAllowNoCreds=$noauth;
	}

	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}

	if($noauth==1){
		$f[]=parseTemplate_headers("$unlock_web_site_text",null,$SquidGuardIPWeb);
		$f[]=$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
		<input type='hidden' id='unlock-www' name='unlock-www' value='yes'>
		<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
		<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
		<input type='hidden' id='SquidGuardIPWeb' name='SquidGuardIPWeb' value='$SquidGuardIPWeb'>
		<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
		<input type='hidden' id='url' name='url' value='$url'>";
		$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
		$f[]="<input type='hidden' id='nocreds' name='nocreds' value='1'>";
		$f[]="<script>	";
		$f[]="function CheckTheForm(){	";
		$f[]="document.forms['unlockform'].submit();";
		$f[]="}	";
		$f[]="CheckTheForm();";
		$f[]="</script>	";
		$f[]="</body>";
		$f[]="</html>";
		echo @implode("\n", $f);
		return;
	}
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}

	$f[]=parseTemplate_headers($unlock_web_site_text,null,$SquidGuardIPWeb);
	$f[]="    <h2>$unlock_web_site_text $FinalHost {for} $IPADDR {$_REQUEST["clientname"]}</h2>";
	if($error<>null){
		$f[]="    <h2>$error</h2>";
	}
	$f[]="<div id=\"info\">";
	$f[]="<p style='margin-bottom:30px'>$UnbblockText2</p>";
	$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
	<input type='hidden' id='unlock-www' name='unlock-www' value='yes'>
	<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
	<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
	<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
	<input type='hidden' id='url' name='url' value='$url'>";
	$f[]="<input type='hidden' id='UfdbGuardHTTPAllowNoCreds' name='username' value='{$UfdbGuardHTTPAllowNoCreds}'>";
	$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";

	if($UfdbGuardHTTPAllowNoCreds==1){
		$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
		$f[]="<input type='hidden' id='password' name='password' value='{$_REQUEST["password"]}'>";

	}


	$f[]="<table width='100%;'>";
	if($UfdbGuardHTTPAllowNoCreds==0){
		$f[]=" <tr>
				<td class=\"info_title\">{username}:</td>
				<td class=\"info_content\">".Field_text("username",$_REQUEST["username"],"$fontfamily;width:80%;font-size:35px;padding:5px")."</td>
			</tr>
			<tr>
				<td class=\"info_title\">{password}:</td>
				<td class=\"info_content\">".Field_password(
						"nolock:password",$_REQUEST["password"],"$fontfamily;width:80%;font-size:35px;padding:5px",
						null,null,null,false,"CheckTheForm(event)")."</td>
			</tr>";
	}
	$f[]=" <tr><td colspan=2 align='right'><hr>". button("{submit}","document.forms['unlockform'].submit();")."</td></tr>
	</table>
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['unlockform'].submit();
		}
		
	</script>
	";


	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}
function parseTemplate_release_ticket(){

	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));

	parseTemplate_unlock_save(true,$ARRAY,true);

}

function parseTemplate_headers($title,$addhead=null,$SquidGuardIPWeb=null){
	$sock=new sockets();

	if(!isset($GLOBALS["UfdbGuardHTTP"])){$sock->BuildTemplatesConfig();}

	if($SquidGuardIPWeb<>null){
		$SquidGuardIPWeb=str_replace("/".basename(__FILE__), "", $SquidGuardIPWeb);

	}

	$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"];
	if(isset($_REQUEST["unlock"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["unlock-www"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["smtp-send-email"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"])){$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]=2639;}


	$SquidHTTPTemplateSmiley=intval($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]);

	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"])){
		$SquidHTTPTemplateSmileyEnable=1;
	}else{
		$SquidHTTPTemplateSmileyEnable=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"];
	}

	$BackgroundColorBLKBT=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"];

	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}





	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	$f[]="<head>";
	$f[]=$addhead;
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-1.8.3.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-ui-1.8.22.custom.min.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery.blockUI.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/mouse.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/default.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/XHRConnection.js\"></script>";
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
	$f[]="            document.body.style.background = \"$Background\";";
	$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
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
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\{$SquidHTTPTemplateSmiley}\";}";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
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
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }    ";
	$f[]="p {";
	$f[]="        font-size: 12pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
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
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }
		
		
	.Button2014-lg {
	border-radius: 6px 6px 6px 6px;
	-moz-border-radius: 6px 6px 6px 6px;
	-khtml-border-radius: 6px 6px 6px 6px;
	-webkit-border-radius: 6px 6px 6px 6px;
	font-size: 18px;
	line-height: 1.33;
	padding: 10px 16px;
}
.Button2014-success {
background-color: $BackgroundColorBLKBT;
border-color: #000000;
color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};
}
.Button2014 {
-moz-user-select: none;
border: 1px solid transparent;
border-radius: 4px 4px 4px 4px;
cursor: pointer;
display: inline-block;
font-size: 22px;
font-weight: normal;
line-height: 1.42857;
margin-bottom: 0;
padding: 6px 22px;
text-align: center;
vertical-align: middle;
white-space: nowrap;
font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};
}";
$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";



	if($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]==1){
		$SquidHTTPTemplateLogoPositionH=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionH"];
		$SquidHTTPTemplateLogoPositionL=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionL"];
		$picturemode=$GLOBALS["UfdbGuardHTTP"]["picturemode"];
				if($picturemode==null){$picturemode="absolute";}
				$widthDiv="100%";
				$heightDiv=null;
				$align=null;

	list($width, $height, $type, $attr) = getimagesize(dirname(__FILE__)."/{$GLOBALS["UfdbGuardHTTP"]["picture_path"]}");
		
			$heightDiv="height:{$height}px;";
			$background="background-position:{$SquidHTTPTemplateLogoPositionL}% {$SquidHTTPTemplateLogoPositionH}%;";

			if($picturemode=="absolute"){
			$widthDiv="{$width}px";
			$background=null;
			}

			if($GLOBALS["UfdbGuardHTTP"]["picturealign"]<>null){
			$align="text-align:{$GLOBALS["UfdbGuardHTTP"]["picturealign"]};";
			}
		$f[]="<div style='position:{$picturemode};{$align}width:{$widthDiv};$heightDiv
		background-image:url(\"$SquidGuardIPWeb/{$GLOBALS["UfdbGuardHTTP"]["picture_path"]}\");
		background-repeat:no-repeat;$background
		left:{$SquidHTTPTemplateLogoPositionL}%;
		top:{$SquidHTTPTemplateLogoPositionH}%;
		'
		>&nbsp;</div>
		";

	}


	$f[]="<div id=\"wrapper\">";
	return @implode("\n", $f);
}

function parseTemplate_smtp_button($ARRAY,$SquidGuardIPWeb){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$client_username=$ARRAY["clientname"];
	$ARRAY["SquidGuardIPWeb"]=$SquidGuardIPWeb;
	$serialize_array=base64_encode(serialize($ARRAY));
	$sock=new sockets();
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));

	$client_username=$ARRAY["clientname"];
	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$email=null;
	$t=time();
	if($client_username<>null){

		$sock=new sockets();
		$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
		if($EnableKerbAuth==1){

			$ad=new external_acl_squid_ldap();
			$array=$ad->ADLdap_userinfos($client_username);
			$email=$array[0]["mail"][0];

		}else{

			$users=new user($client_username);
			if(count($users->email_addresses)>0){
				$email=$users->email_addresses[0];
			}

		}
	}



	return "
	<form method='post' action='$SquidGuardIPWeb' id='post-send-email'>
	<input type='hidden' name='smtp-send-email' value='yes'>
	<input type='hidden' name='email' value='$email'>
	<input type='hidden' name='serialize' value='$serialize_array'>
	</form>
	<a href=\"javascript:blur();\" OnClick=\"javascript:document.forms['post-send-email'].submit();\">{$SquidGuardWebSMTP["smtp_recipient"]}</a>";


}


function parseTemplate_extension_gif($filename){
	$fsize = filesize("img/1x1.gif");
	header("Content-Type: image/gif");
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile( "img/1x1.gif" );
}

function parseTemplate_LocalDB_receive(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");

	$user=new user($_POST["USERNAME"]);



	if($_POST["password"]<>md5($user->password)){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}: {wrong_password}");
		die();
	}

	$privs=new privileges($user->uid);
	$privileges_array=$privs->privs;
	if($privileges_array["AllowDansGuardianBanned"]<>"yes"){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}: {ERROR_NO_PRIVS}");
		return;
	}


	//AllowDansGuardianBanned

	$Whitehost=$_POST["Whitehost"];
	$CLIENT=$_POST["CLIENT"];
	$MEMBER=$_POST["USERNAME"];
	$md5=md5("$CLIENT$Whitehost$MEMBER");
	$sql="INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid)
	VALUES ('$md5','$CLIENT','$Whitehost','$MEMBER')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL($sql);}
	}
	if(!$q->ok){echo $q->mysql_error;return;}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{success_restart_query_in_few_seconds}");

	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");


}


function parseTemplate_SinglePassWord(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$proto="http";
	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";}}
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";
	$GLOBALS["JS_NO_CACHE"]=true;

	$t=time();
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$ask_password=$tpl->javascript_parse_text("{password}:");
	$url=base64_decode($_GET["url"]);
	$clientaddr=base64_decode($_GET["clientaddr"]);
	$array=parse_url($url);
	$Whitehost=strtolower($array["host"]);
	$pp=new pagebuilder();
	$head=$pp->jsArtica()."\n\n".$pp->headcss();
	if(preg_match("#^www.(.+)#", $Whitehost,$re)){$Whitehost=$re[1];}

	$yahoo=$pp->YahooBody();

	$t=time();
	$unlock=$tpl->_ENGINE_parse_body("{unlock}");
	$title="$unlock &laquo;$Whitehost&raquo;";

	$html="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html lang=\"en\">
	<head>
	<meta charset=\"utf-8\">
	<title>$ask_password</title>
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
	<meta name=\"description\" content=\"\">
	<meta name=\"author\" content=\"\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap.css\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap-responsive.css\">

	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
	<script type='text/javascript' language='javascript' src='{$GLOBALS["JS_HEAD_PREPREND"]}/js/jquery.uilock.min.js'></script>
	<script type='text/javascript' language='javascript' src='{$GLOBALS["JS_HEAD_PREPREND"]}/js/jquery.blockUI.js'></script>
	<style type=\"text/css\">
	body {
	padding-top: 40px;
	padding-bottom: 40px;
	background-color: #f5f5f5;
}

.form-signin {
max-width: 300px;
padding: 19px 29px 29px;
margin: 0 auto 20px;
background-color: #fff;
border: 1px solid #e5e5e5;
-webkit-border-radius: 5px;
-moz-border-radius: 5px;
border-radius: 5px;
-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.form-signin .form-signin-heading,
.form-signin .checkbox {
margin-bottom: 10px;
}
.form-signin input[type=\"text\"],
.form-signin input[type=\"password\"] {
font-size: 16px;
height: auto;
margin-bottom: 15px;
padding: 7px 9px;
}
</style>
<!--[if IE]>
<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/bootstrap/css/ie-only.css\" />
<![endif]-->
</head>
<body>
<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/ajax-loader-eu.gif\">


<div class=\"form-signin\">
<div id='div-$t'></div>
		<h2 class=\"form-signin-heading\">$title</h2>
		<input type=\"password\" class=\"input-block-level\" placeholder=\"Password\" id=\"PASS-$t\">
		<button class=\"btn btn-large btn-primary\" type=\"button\" id=\"signin\">$unlock</button>
		</div>

<script type=\"text/javascript\">
$('#signin').on('click', function (e) {
	//if(!checkEnter(e)){return;}
	SendPass$t();
});


$('.input-block-level').keypress(function (e) {
	if (e.which == 13) {
		SendPass$t();
	}
});


var xSendPass$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	document.getElementById('div-$t').innerHTML='';
}

function SendPass$t(){
	var password=MD5(document.getElementById('PASS-$t').value);
	var XHR = new XHRConnection();
	XHR.appendData('password',password);
	XHR.appendData('CLIENT','$clientaddr');
	XHR.appendData('Whitehost','$Whitehost');
	AnimateDiv('div-$t');
	XHR.sendAndLoad('{$GLOBALS["JS_HEAD_PREPREND"]}/$page', 'POST',xSendPass$t);
}

</script>

				</body>
				</html>";
				echo $html;return;

}


function parseTemplate_LocalDB(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$GLOBALS["JS_NO_CACHE"]=true;
	$proto="http";

	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";}}
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";
	$t=time();
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$page=CurrentPageName();
	$tpl=new templates();
	$ask_password=$tpl->javascript_parse_text("{password}:");
	$url=base64_decode($_GET["url"]);
	$clientaddr=base64_decode($_GET["clientaddr"]);
	$array=parse_url($url);
	$Whitehost=strtolower($array["host"]);
	$pp=new pagebuilder();
	$head=$pp->jsArtica()."\n\n".$pp->headcss();
	if(preg_match("#^www.(.+)#", $Whitehost,$re)){$Whitehost=$re[1];}

	$yahoo=$pp->YahooBody();

	$t=time();
	$html="
	<html>
	<head>
	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
	<title>$ask_password</title>
	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=EmulateIE7\" />
	<link href='/css/styles_main.css'    rel=\"styleSheet\"  type='text/css' />
	<link href='/css/styles_header.css'  rel=\"styleSheet\"  type='text/css' />
	<link href='/css/styles_middle.css'  rel=\"styleSheet\"  type='text/css' />
	<link href='/css/styles_tables.css'  rel=\"styleSheet\"  type='text/css' />
	<link href=\"/css/styles_rounded.css\" rel=\"stylesheet\"  type=\"text/css\" />
	$head
	</head>
	<body style='background: url(\"/css/images/pattern.png\") repeat scroll 0pt 0pt rgb(38, 56, 73); padding: 0px;padding-top: 15px; margin: 0px; border: 0px solid black; width: 100%; cursor: default; -moz-user-select: inherit;'>
	$yahoo
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{client}:</td>
	<td style='font-size:16px'><strong>$clientaddr</strong></td>
	</tr>
	<tr>
	<td class=legend style='font-size:16px'>{website}:</td>
	<td style='font-size:16px'><strong>$Whitehost</strong></td>
	</tr>
	<tr>
	<td class=legend style='font-size:16px'>{username}:</td>
	<td style='font-size:16px'>". Field_text("USERNAME-$t",null,"font-size:16px;font-weight:bolder",null,null,null,false,"SendPassCheck(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td style='font-size:16px'>". Field_password("#nolock:PASS-$t",null,"font-size:16px",null,null,null,false,"SendPassCheck(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{submit}","SendPass$t()",18)."</td>
				</tr>
				</table>

				<script>
				var X_SendPass= function (obj) {
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);}
				document.getElementById('div-$t').innerHTML='';
}

				function SendPassCheck(e){
				if(checkEnter(e)){SendPass$t();}
}

				function SendPass$t(){
				var password=MD5(document.getElementById('PASS-$t').value);
				var XHR = new XHRConnection();
				XHR.appendData('password',password);
				XHR.appendData('USERNAME',document.getElementById('USERNAME-$t').value);
				XHR.appendData('CLIENT','$clientaddr');
				XHR.appendData('Whitehost','$Whitehost');
				AnimateDiv('div-$t');
				XHR.sendAndLoad('$page', 'POST',X_SendPass);
}
				MessagesTophideAllMessages();
				</script>
				</body>
				</html>";

	echo $tpl->_ENGINE_parse_body($html);


}
 
function parseTemplateLogs($text=null,$function,$file,$line){
	if(!$GLOBALS["WRITELOGS"]){return;}
	$time=date('m-d H:i:s');

	if($GLOBALS["VERBOSE"]){echo "[$time]:$function:$text in line $line<br>\n";}
	$logFile=dirname(__FILE__)."/ressources/logs/squid-template.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
	}
	 
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "[$time]:$function:$text in line $line\n");
	@fclose($f);
}

function parseTemplateForcejs($uri){
	if(preg_match("#ad\.doubleclick\.net\/adj\/#", $uri)){return true;}

}


function parseTemplate_sendemail_js(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$your_query_was_sent_to_administrator= $tpl->javascript_parse_text("{your_query_was_sent_to_administrator}",0);

	$ARRAY=unserialize(base64_decode($_GET["serialize"]));
	$client_username=$ARRAY["clientname"];
	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$email=null;
	$t=time();
	if($client_username<>null){

		$sock=new sockets();
		$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
		if($EnableKerbAuth==1){
				
			$ad=new external_acl_squid_ldap();
			$array=$ad->ADLdap_userinfos($client_username);
			$email=$array[0]["mail"][0];
				
		}else{
				
			$users=new user($client_username);
			if(count($users->email_addresses)>0){
				$email=$users->email_addresses[0];
			}
				
		}
	}

	if($email<>null){
		echo "
		// $client_username
		var xSMTPNotifValues$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}

	function SMTPNotifValues$t(){

	var jqxhr = $.post( '$SquidGuardIPWeb',{'send-smtp-notif':'$email','MAIN_ARRAY':'{$_GET["serialize"]}'}, function(result) {
	alert( '$your_query_was_sent_to_administrator' );
	})
	.done(function(result) {
	alert('$your_query_was_sent_to_administrator' );
	})
	.fail(function() {
	alert( '$your_query_was_sent_to_administrator' );
	})
	.always(function() {
	//alert( 'unknown' );
	});


	//	var XHR = new XHRConnection();
	//	XHR.setLockOff();
	//	XHR.appendData('send-smtp-notif','$email');
	//	XHR.appendData('MAIN_ARRAY','{$_GET["serialize"]}');
	//	XHR.sendAndLoad('$SquidGuardIPWeb', 'POST',xSMTPNotifValues$t);
	}
	SMTPNotifValues$t();";
	return;

	}

		$tpl=new templates();
		$title=$tpl->javascript_parse_text("{give_your_email_address}");
	echo " //$client_username
	var xSMTPNotifValues$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}

function SMTPNotifValues$t(){
var email=prompt('$title');
if(!email){return;}

var jqxhr = $.post( '$SquidGuardIPWeb',{'send-smtp-notif':email,'MAIN_ARRAY':'{$_GET["serialize"]}'}, function(result) {
alert( '$your_query_was_sent_to_administrator' );
})
.done(function(result) {
		alert('$your_query_was_sent_to_administrator' );
})
.fail(function(xhr, textStatus, errorThrown){
		alert('$your_query_was_sent_to_administrator' +xhr.responseText);
		 
})
.always(function() {
//none
});


//var XHR = new XHRConnection();
//XHR.appendData('send-smtp-notif',email);
//XHR.appendData('MAIN_ARRAY','{$_GET["serialize"]}');
//XHR.sendAndLoad('$SquidGuardIPWeb', 'POST',xSMTPNotifValues$t);
}
SMTPNotifValues$t();";
return;

}

function parseTemplate_GET_REMOTE_ADDR(){
	if(isset($_SERVER["REMOTE_ADDR"])){
		$IPADDR=$_SERVER["REMOTE_ADDR"];
		if($GLOBALS["VERBOSE"]){echo "REMOTE_ADDR = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){
		$IPADDR=$_SERVER["HTTP_X_REAL_IP"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_REAL_IP = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_FORWARDED_FOR = $IPADDR<br>\n";}
	}
	$GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
	if($GLOBALS["VERBOSE"]){echo "HTTP_USER_AGENT = {$GLOBALS["HTTP_USER_AGENT"]}<br>\n";}

	if($GLOBALS["VERBOSE"]){
		while (list ($num, $Linz) = each ($_SERVER) ){
			if(is_array($Linz)){
				while (list ($a, $b) = each ($Linz) ){
					echo "<li style='font-size:10px'>\$_SERVER[\"$num\"][\"$a\"]=\"$b\"</li>\n";
				}
				continue;
			}
			echo "<li style='font-size:10px'>\$_SERVER[\"$num\"]=\"$Linz\"</li>\n";
		}

	}


	$GLOBALS["IPADDR"]=$IPADDR;
	return $IPADDR;
}

function parseTemplate_ticket($error=null){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$SquidGuardIPWeb=null;
	$url=$_REQUEST["url"];
	$IPADDR=$_REQUEST["ipaddr"];
	if(isset($_GET["SquidGuardIPWeb"])){$SquidGuardIPWeb=$_GET["SquidGuardIPWeb"];}
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb=CurrentPageName();}
	if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb=$SquidGuardIPWeb</H1>";}
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));

	$q=new mysql_squid_builder();
	$parse_url=parse_url($url);
	$host=$parse_url["host"];
	if(preg_match("#(.+?):[0-9]+#", $host,$re)){$host=$re[1];}
	$FinalHost=$q->GetFamilySites($host);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];

	$ticket_web_site_text="{submit_a_ticket}";
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}

	$f[]=parseTemplate_headers("$UFDBGUARD_TICKET_LINK",null,$SquidGuardIPWeb);
	$f[]=$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
	<input type='hidden' id='unlock-ticket' name='unlock-ticket' value='yes'>
	<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
	<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
	<input type='hidden' id='SquidGuardIPWeb' name='SquidGuardIPWeb' value='$SquidGuardIPWeb'>
	<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
	<input type='hidden' id='url' name='url' value='$url'>";
	$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
	$f[]="<script>	";
	$f[]="function CheckTheForm(){	";
	$f[]="document.forms['unlockform'].submit();";
	$f[]="}	";
	$f[]="CheckTheForm();";
	$f[]="</script>	";
	$f[]="</body>";
	$f[]="</html>";
	echo @implode("\n", $f);


}
function parseTemplate_categoryname($category=null,$license=0,$nosuffix=0){

	$CATEGORY_PLUS_TXT=null;
	parseTemplateLogs("parseTemplate_categoryname($category,$license)",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	$SquidGuardApacheShowGroupNameTXT=null;



	if($license==1){
		$SquidGuardApacheShowGroupName=$sock->GET_INFO("SquidGuardApacheShowGroupName");
		if(!is_numeric($SquidGuardApacheShowGroupName)){$SquidGuardApacheShowGroupName=0;}
		if($SquidGuardApacheShowGroupName==1){
			$SquidGuardApacheShowGroupNameTXT=$sock->GET_INFO("SquidGuardApacheShowGroupNameTXT");
			if($SquidGuardApacheShowGroupNameTXT==null){
				$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
					
				if($LicenseInfos["COMPANY"]==null){
					$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
					$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
			}
			$SquidGuardApacheShowGroupNameTXT=$LicenseInfos["COMPANY"];

		}
	}


	$category=strtolower(trim($category));

	include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");


	if(preg_match("#^art(.+)#", $category,$re)){
		parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
		$category=CategoryCodeToCatName($category);
		$CATEGORY_PLUS_TXT="Artica Database";
		$users=new usersMenus();
		if($users->WEBSECURIZE){$CATEGORY_PLUS_TXT="Web Securize Database";}
		if($users->LANWANSAT){$CATEGORY_PLUS_TXT="LanWanSAT Database";}
		if($users->BAMSIGHT){$CATEGORY_PLUS_TXT="BamSight Database";}
			
			
	}

	if(preg_match("#^tls(.+)#", $category,$re)){
		parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
		$category=CategoryCodeToCatName($category);
		$CATEGORY_PLUS_TXT="Toulouse University Database";
	}
	parseTemplateLogs("Parsing: `$category` - $CATEGORY_PLUS_TXT nosuffix=$nosuffix",__FUNCTION__,__FILE__,__LINE__);
	if($nosuffix==1){return $category;}

	if($SquidGuardApacheShowGroupNameTXT<>null){$CATEGORY_PLUS_TXT=$SquidGuardApacheShowGroupNameTXT;}
	if($CATEGORY_PLUS_TXT<>null){
		return $category." (".$CATEGORY_PLUS_TXT.")";
	}
	return $category;
}
function  parseTemplate_extension($uri){

	$js_forced["revsci.net"]=true;
	$js_forced["omtrdc.net"]=true;

	$array=parse_url($uri);
	$hostname=$array["host"];

	$fam=new squid_familysite();
	$hostname=$fam->GetFamilySites($hostname);

	if(count($array)==0){return false;}
	if(!isset($array["path"])){return false;}
	$path_parts = pathinfo($array["path"]);
	$ext=$path_parts['extension'];
	if(preg_match("#(.+?)\?#", $ext,$re)){$ext=$re[1];}
	if($ext=="php"){return false;}
	if($ext=="html"){return false;}
	$basename=$path_parts['basename'];
	$filename=$path_parts['basename'];

	if(preg_match("#\/pixel\?#", $uri)){
		parseTemplate_extension_gif();
		return true;
	}



	if(isset($js_forced[$hostname])){$ext="js";}



	if($filename==null){$filename="1x1.$ext";}
	$ctype=null;
	switch ($ext) {

		case "gif": parseTemplate_extension_gif($filename);return true;
		case "png": $ctype="image/png"; break;
		case "jpeg": $ctype="image/jpg";break;
		case "jpg": $ctype="image/jpg";;break;
		case "js": $ctype="application/x-javascript";;break;
		case "css": $ctype="text/css";;break;
	}

	//aspx



	if($ext=="js"){
		header("content-type: application/x-javascript");echo "// blocked by url filtering\n";
		return true;
	}
	if($ext=="css"){
		header("content-type: text/css");echo "\n";
		echo "/**\n";
		echo "* blocked by url filtering\n";
		echo "* \n";
		echo "*/\n";
		return true;
	}
	if($ext=="ico"){

		$fsize = filesize("ressources/templates/Squid/favicon.ico");
		header("content-type: image/vnd.microsoft.icon");
		header("Content-Length: ".$fsize);
		ob_clean();
		flush();
		readfile( $fsize );
		return true;
	}



	if($ctype<>null){
		if(!is_file("img/$filename")){$filename=null;}
		if($filename==null){$filename="1x1.$ext";}
		$fsize = filesize("img/$filename");
		header("Content-Type: $ctype");
		header("Content-Length: ".$fsize);
		ob_clean();
		flush();
		readfile( $fsize );
		return true;
	}

	writelogs("$uri: $ext ($filename) Unkown",__FUNCTION__,__FILE__,__LINE__);



}
function hostfrom_url($url){
	$URL_ARRAY=parse_url($url);
	if(!isset($URL_ARRAY["host"])){return null;}
	$src_hostname=$URL_ARRAY["host"];
	if(preg_match("#^www.(.+)#", $src_hostname,$re)){$src_hostname=$re[1];}
	if(preg_match("#^(.+?):[0-9]+#", $src_hostname,$re)){$src_hostname=$re[1];}
	return $src_hostname;
}
function CacheManager_default(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));

	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
	$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
	return $LicenseInfos["EMAIL"];
}

function CacheManager(){
	$sock=new sockets();
	$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
	if($cache_mgr_user<>null){return $cache_mgr_user;}
	return CacheManager_default();
}

function ini_set_verbosedx(){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
}