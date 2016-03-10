<?php
if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.squid.acls.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["delete"])){Delete();exit;}
	if(isset($_POST["nocache_single"])){add_nocache_single();exit();}
	
	if(isset($_GET["add-rangeoffsetlimit-js"])){add_rangeoffsetlimit_js();exit;}
	if(isset($_GET["add-rangeoffsetlimit-popup"])){add_rangeoffsetlimit_popup();exit;}
	if(isset($_POST["rangeoffsetlimit"])){add_rangeoffsetlimit_save();exit;}
	if(isset($_POST["rangeoffsetlimit_single"])){add_rangeoffsetlimit_single();exit;}
	
	if(isset($_POST["nonntlm_single"])){add_nontlm_single();exit;}
	if(isset($_POST["ntlmwhite"])){add_nontlm_save();exit;}
	
	
	
	if(isset($_GET["add-www-js"])){add_www_js();exit;}
	if(isset($_GET["add-nocache-js"])){add_nocache_js();exit;}
	if(isset($_GET["add-ntlm-popup"])){add_ntlm_popup();exit;}
	
	
	
	if(isset($_GET["add-nocache-popup"])){add_nocache_popup();exit;}
	if(isset($_GET["add-white-popup"])){add_white_popup();exit;}
	if(isset($_GET["add-white-popup2"])){add_white_popup2();exit;}
	
	if(isset($_GET["add-white-tab"])){add_white_tab();exit;}
	
	
	if(isset($_GET["add-ntlm-js"])){add_ntlm_js();exit;}
	
	if(isset($_GET["add-black-js"])){add_black_js();exit;}
	if(isset($_GET["add-black-popup"])){add_black_popup();exit;}
	if(isset($_POST["blacklist"])){add_black_save();exit;}
	if(isset($_POST["blacklist-single"])){add_black_single_save();exit;}
	
	
	if(isset($_POST["whitelist"])){add_white_save();exit;}
	if(isset($_POST["nocache"])){add_nocache_save();exit;}
	if(isset($_POST["whitelist-single"])){add_white_single();exit;}
	
	
	js();

	
function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{whitelist}::{APP_UFDBGUARD}");
	echo "YahooWin4('560','$page?popup=yes&t=$t','$title')";
	
}

function add_black_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{blacklist}');
	echo "YahooWin5('790','$page?add-black-popup=yes','$title')";
	return;
}
function add_ntlm_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{authentication_whitelist}');
	echo "YahooWin5('790','$page?add-ntlm-popup=yes','$title')";
	return;
}
function add_rangeoffsetlimit_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{partial_content_list}');
	echo "YahooWin5('790','$page?add-rangeoffsetlimit-popup=yes','$title')";
	return;	
}

function add_black_popup(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	
	$t=time();
	$sql="SELECT items  FROM deny_websites ORDER BY items";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}
	
	$html="
	<div style='font-size:22px'>{blacklist}</div>
	<div class=explain style='font-size:18px'>{squid_ask_domain}<br><strong style='color:#d32d2d'>{warning_deny_for_all_users}</strong></div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right;margin-top:20px;font-size:28px'>
			<hr>
			". button("{error_page}","Loadjs('squid.templates.skin.php?Zoom-js=ERR_BLACKLISTED_SITE&lang=$SquidHTTPTemplateLanguage');",28)."&nbsp;|&nbsp;".
			button("{compile2}","Save2$t()",28).
			"&nbsp;|&nbsp;". button("{apply}","Save$t()",28)."</div>

<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	
}
var xSave2$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('squid.compile.whiteblack.progress.php?ask=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('blacklist',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('blacklist',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);	
}

</script>			
";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function add_black_save(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$q=new mysql_squid_builder();
	$f=array();
	$f=explode("\n",$_POST["blacklist"]);
	
	while (list ($index, $line) = each ($f) ){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		$line=mysql_escape_string2($line);
		$md5=md5($line);
		$n[]="('$line')";
	
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `deny_websites`","artica_backup");
	if(count($n)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `deny_websites` (`items`) VALUES ".@implode(",", $n),"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}	
	
}

function add_rangeoffsetlimit_save(){
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `rangeoffsetlimit` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
	
	$f=array();
	$f=explode("\n",$_POST["rangeoffsetlimit"]);
	
	while (list ($index, $line) = each ($f) ){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		$line=mysql_escape_string2($line);
		$md5=md5($line);
		$n[]="('$line')";
	
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `rangeoffsetlimit`","artica_backup");
	if(count($n)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `rangeoffsetlimit` (`items`) VALUES ".@implode(",", $n),"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	
}


function add_nontlm_save(){
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	
	$sql="CREATE TABLE IF NOT EXISTS `deny_ntlm_domains` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	$q=new mysql_squid_builder();
	$f=array();
	$f=explode("\n",$_POST["ntlmwhite"]);
	
	while (list ($index, $line) = each ($f) ){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		$line=mysql_escape_string2($line);
		$md5=md5($line);
		$n[]="('$line')";
	
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `deny_ntlm_domains`","artica_backup");
	if(count($n)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `deny_ntlm_domains` (`items`) VALUES ".@implode(",", $n),"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
}


function add_nocache_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{deny_from_cache}');
	
	echo "YahooWin5('790','$page?add-nocache-popup=yes','$title')";
	return;
}

function add_nocache_popup(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$sql="CREATE TABLE IF NOT EXISTS `denycache_websites` ( `items` VARCHAR( 255 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	

	$t=time();
	$sql="SELECT items  FROM denycache_websites ORDER BY items";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}
	$sql="SELECT items  FROM deny_cache_domains ORDER BY items";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}
	
	
	
	

	$html="
	<div style='font-size:22px'>{deny_from_cache}</div>
	<div class=explain style='font-size:18px'>{notcaching_websites}<br>{squid_ask_domain}<br></div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right;margin-top:20px;font-size:28px'>
			<hr>
			".
			button("{compile2}","Save2$t()",28)."&nbsp;|&nbsp;". button("{save}","Save$t()",28)."</div>

			<script>
			var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}

}
var xSave2$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('squid.global.wl.center.progress.php');;
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nocache',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nocache',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}






function add_www_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text('{whitelist}');
	//echo "YahooWin5('790','$page?add-white-popup=yes','$title')";
	echo "YahooWin5('790','$page?add-white-tab=yes','$title')";
	return;
}

function add_white_tab(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["add-white-popup"]="{whitelist}";
	$array["add-white-popup2"]="{import}";
	$array["analyze"]="{analyze}";
	
	
	
	$fontsize=18;
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		if($num=="add-white-popup"){
			$tab[]="<li><a href=\"squid.global.whitelist.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		if($num=="analyze"){
			$tab[]="<li><a href=\"squid.analyze.page.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "add_white_tabs")."";
	
		
	
}

function add_rangeoffsetlimit_popup(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `rangeoffsetlimit` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
	
	$t=time();
	$sql="SELECT items  FROM rangeoffsetlimit ORDER BY items";
	
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}
	
	$html="
	<div style='font-size:22px'>{partial_content_list}</div>
	<div class=explain style='font-size:18px'>{enforce_partial_content_explain}<br>{squid_ask_domain}<br></div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right;margin-top:20px;font-size:28px'>
			<hr>
			".
				button("{compile2}","Save2$t()",28)."&nbsp;|&nbsp;". button("{apply}","Save$t()",28)."</div>
	
				<script>
				var xSave$t=function(obj){
				var tempvalue=obj.responseText;
				if(tempvalue.length>3){alert(tempvalue);return;}
	
	}
	var xSave2$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('squid.global.wl.center.progress.php');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rangeoffsetlimit',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ntlmwhite',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
	}
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function add_ntlm_popup(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sock=new sockets();

	
	$sql="CREATE TABLE IF NOT EXISTS `deny_ntlm_domains` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);

	$t=time();
	$sql="SELECT items  FROM deny_ntlm_domains ORDER BY items";
	
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}

	$html="
	<div style='font-size:22px'>{authentication_whitelist}</div>
	<div class=explain style='font-size:18px'>{squid_ask_domain}<br></div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right;margin-top:20px;font-size:28px'>
			<hr>
			".
			button("{compile2}","Save2$t()",28)."&nbsp;|&nbsp;". button("{apply}","Save$t()",28)."</div>

			<script>
			var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}

}
var xSave2$t=function(obj){
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
Loadjs('squid.compile.whiteblack.progress.php');
}

function Save$t(){
var XHR = new XHRConnection();
XHR.appendData('ntlmwhite',document.getElementById('form$t').value);
XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function Save2$t(){
var XHR = new XHRConnection();
XHR.appendData('ntlmwhite',document.getElementById('form$t').value);
XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}

function add_white_popup(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$page=CurrentPageName();
	$t=time();
	echo "<div id='$t'></div><script>LoadAjax('$t','squid.global.whitelist.php');</script>";
}


function add_white_popup2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();


	$t=time();
	$sql="SELECT items  FROM urlrewriteaccessdeny ORDER BY items";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$tr[]=$ligne["items"];
	}

	$html="
	<div style='font-size:22px'>{whitelist}</div>
	<div class=explain style='font-size:18px'>{squid_ask_domain}<br></div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:350px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='form$t'>".@implode("\n", $tr)."</textarea>
	<div style='text-align:right;margin-top:20px;font-size:28px'>
			<hr>
			". button("{save}","Save$t()",28)."</div>

			<script>
			var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}

}
var xSave2$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('whitelist',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('whitelist',document.getElementById('form$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);


}

function add_white_save(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q1=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
		
	$tr=explode("\n",$_POST["whitelist"]);
	
	$sql="CREATE TABLE IF NOT EXISTS `urlrewriteaccessdeny` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	$q->QUERY_SQL("TRUNCATE TABLE urlrewriteaccessdeny","artica_backup");
		
	while (list ($none,$www ) = each ($tr) ){
		$www=$acl->dstdomain_parse($www);
		if($www==null){continue;}
		$q->QUERY_SQL("INSERT IGNORE INTO urlrewriteaccessdeny (items) VALUES ('{$www}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
		
}

function add_white_single(){
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q1=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	
	$sql="CREATE TABLE IF NOT EXISTS `urlrewriteaccessdeny` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	
	$www=$_POST["whitelist-single"];
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO urlrewriteaccessdeny (items) VALUES ('{$www}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
		
	
}

function add_black_single_save(){
	$acl=new squid_acls();
	$q=new mysql_squid_builder();
	$www=$_POST["blacklist-single"];
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO `deny_websites` (`items`) VALUES ('$www')","artica_backup");
	
}


function add_nocache_single(){
	
	
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	
	$sql="CREATE TABLE IF NOT EXISTS `deny_cache_domains` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	$www=$_POST["nocache_single"];
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO deny_cache_domains (items) VALUES ('{$www}')");
	if(!$q->ok){echo $q->mysql_error;return;}


}

function add_rangeoffsetlimit_single(){
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `rangeoffsetlimit` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
	$www=$_POST["rangeoffsetlimit_single"];
	if($www==null){echo "NULL Domain!\n";return;}
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO rangeoffsetlimit (items) VALUES ('{$www}')");
	if(!$q->ok){echo $q->mysql_error;return;}	
}


function add_nontlm_single(){
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	
	$sql="CREATE TABLE IF NOT EXISTS `deny_ntlm_domains` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	$www=$_POST["nonntlm_single"];
	if($www==null){echo "NULL Domain!\n";return;}
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO deny_ntlm_domains (items) VALUES ('{$www}')");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function add_nocache_save(){
	$table="denycache_websites";
	$q=new mysql_squid_builder();
	$q1=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();
	
	$sql="CREATE TABLE IF NOT EXISTS `deny_cache_domains` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	$tr=explode("\n",$_POST["nocache"]);
	$q->QUERY_SQL("TRUNCATE TABLE deny_cache_domains","artica_backup");
	
	while (list ($none,$www ) = each ($tr) ){
		$www=trim(strtolower($www));
		if($www==null){continue;}
		
		if(!$IP->isIPAddressOrRange($www)){
			if(substr($www,0, 1)<>"^"){$www=$acl->dstdomain_parse($www);}
		}
		
		$q->QUERY_SQL("INSERT IGNORE INTO deny_cache_domains (items) VALUES ('{$www}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}	
	
}
	



function popup(){
	$tt=$_GET["tt"];
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new=$tpl->_ENGINE_parse_body("{new}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}")." {blocked_requests}");
	$unblock=$tpl->javascript_parse_text("{unblock}");
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	$title=$tpl->javascript_parse_text("{whitelist}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$t=time();
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$webservers', name : 'items', width : 461, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
		
		],
buttons : [
	{name: '$new', bclass: 'add', onpress : NewWebServer$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},

		],			
	searchitems : [
		{display: '$webservers', name : 'items'},
		
		],			
		
	sortname: 'items',
	sortorder: 'asc',
	usepager: true,
	useRp: true,
	title: '<span style=\"font-size:14px\">$title</span>',
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});   
});

	var x_Delete$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      $('#row'+mem$t).remove();
	}	
	
	var x_reload$t=function(obj){
		var tempvalue=obj.responseText;
	     if(tempvalue.length>3){alert(tempvalue);return;}
		 $('#flexRT$t').flexReload();
		 $('#flexRT$tt').flexReload();
	}

function Delete$t(domain,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete',domain);
	XHR.sendAndLoad('$page', 'POST',x_Delete$t);
}

function Apply$t(){
	Loadjs('squid.compile.progress.php?onlywhitelist=yes');
}

function NewWebServer$t(){
	Loadjs('$page?add-www-js=yes&t=$t&tt=$tt',true);
}

</script>
	
	
	";
echo $html;	

}
function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="urlrewriteaccessdeny";
	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table",'artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`urlrewriteaccessdeny` ( `items` VARCHAR( 255 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,'artica_backup');
	}

	if(!$q->TABLE_EXISTS("$table",'artica_backup')){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_backup')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();

	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
	if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
	$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
	$id=md5(serialize($ligne));
	
	
	
		
	$delete=imgsimple("delete-24.png",null,"Delete$t('{$ligne["items"]}','$id')");
	
	
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:16px;'>{$ligne["items"]}</span>",
			
			$delete
			)
		);
	}
	
	
echo json_encode($data);		


}

function Delete(){
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM urlrewriteaccessdeny WHERE items='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-whitelist=yes");
}
?>


