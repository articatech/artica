<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["ProxyPacCacheTime"])){settings_save();exit;}
if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["settings-popup"])){settings_popup();exit;}
if(isset($_POST["EmptyCache"])){EmptyCache();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["help"])){help();exit;}

if(isset($_GET["events"])){events();exit;}
if(isset($_GET["events-search"])){events_search();exit;}
if(isset($_GET["events-script"])){events_script_js();exit;}
if(isset($_GET["events-script-popup"])){events_script_popup();exit;}
if(isset($_GET["events-script-tester-js"])){events_script_tester_js();exit;}
if(isset($_GET["events-script-tester-popup"])){events_script_tester_popup();exit;}
if(isset($_POST["TESTER-URL"])){events_script_tester_perform();exit;}


if(isset($_POST["rebuild-tables"])){rebuild_tables();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["rules-search"])){rules_search();exit;}
if(isset($_GET["rule-js"])){rules_js();exit;}
if(isset($_GET["rules-tabs"])){rules_tabs();exit;}
if(isset($_POST["rule-enable"])){rules_enable();exit;}
if(isset($_POST["rule-delete"])){rules_delete();exit;}


if(isset($_GET["rules-sources"])){rules_sources();exit;}
if(isset($_GET["rules-sources-search"])){rules_sources_search();exit;}
if(isset($_GET["rules-options"])){rules_options();exit;}
if(isset($_POST["isResolvable"])){rules_options_save();exit;}

if(isset($_POST["rules-sources-link"])){rules_sources_link();exit;}
if(isset($_POST["rules-sources-unlink"])){rules_sources_unlink();exit;}
if(isset($_POST["rules-sources-negation"])){rules_sources_negation();exit;}
if(isset($_GET["rules-sources-order-js"])){rules_sources_link_order_js();exit;}
if(isset($_POST["rules-sources-order"])){rules_sources_link_order();exit;}
if(isset($_GET["rules-whitelisted"])){rules_whitelisted();exit;}
if(isset($_GET["rules-whitelisted-search"])){rules_whitelisted_search();exit;}
if(isset($_POST["rules-whitelisted-link"])){rules_whitelisted_link();exit;}
if(isset($_POST["rules-whitelisted-unlink"])){rules_whitelisted_unlink();exit;}
if(isset($_POST["rules-whitelisted-negation"])){rules_whitelisted_negation();exit;}


if(isset($_GET["rules-proxies"])){rules_proxies();exit;}
if(isset($_GET["rules-proxies-search"])){rules_proxies_search();exit;}
if(isset($_GET["rules-proxies-js"])){rules_proxies_js();exit;}
if(isset($_GET["rules-proxies-popup"])){rules_proxies_popup();exit;}
if(isset($_POST["rules-proxies-hostname"])){rules_proxies_add();exit;}
if(isset($_POST["rules-proxies-move"])){rules_proxies_move();exit;}
if(isset($_POST["rules-proxy-unlink"])){rules_proxies_unlink();exit;}

if(isset($_GET["rules-destination"])){rules_destination_section();exit;}
if(isset($_GET["rules-destination-table"])){rules_destination();exit;}
if(isset($_GET["rules-destination-search"])){rules_destination_search();exit;}
if(isset($_GET["rules-destination-js"])){rules_destination_js();exit;}
if(isset($_GET["rules-destination-popup"])){rules_destination_popup();exit;}
if(isset($_GET["rules-destination-form2"])){rules_destination_form2();exit;}
if(isset($_POST["rules-destination-add"])){rules_destination_create();exit;}
if(isset($_POST["rules-destination-edit"])){rules_destination_edit();exit;}
if(isset($_POST["rules-destination-move"])){rules_destination_move();exit;}
if(isset($_POST["rules-destination-delete"])){rules_destination_delete();exit;}

if(isset($_POST["new-rule"])){rules_create();exit;}
if(isset($_POST["rules-move"])){rules_move();exit;}
if(isset($_POST["rulename"])){rewrite_rule_settings_save();exit;}
if(isset($_GET["debug"])){debug_page();exit;}
if(isset($_GET["debug-search"])){debug_search();exit;}
if(isset($_GET["enable-disable-debug-js"])){debug_disable_js();exit;}
if(isset($_POST["ProxyPacDynamicDebug"])){ProxyPacDynamicDebug();exit;}
if(isset($_POST["empty-debug"])){debug_empty();exit;}
if(isset($_GET["download-debug"])){debug_download();exit;}

popup();




function tabs(){
	popup();
}


function help(){
	
	$tr[]=Paragraphe("youtube-play-64.png", "Video",
			"Proxy PAC: Dynamic Proxy PAC feature (Howto)","http://youtu.be/6H1XMZIK-S8",null,250);
		
	$tr[]=Paragraphe("youtube-play-64.png", "Video",
			"Web Proxy Auto-Discovery Protocol (WPAD) ",
			"http://www.youtube.com/watch?v=iLxZZNFomdg&list=PL6GqpiBEyv4q1GqpV5QbdYWbQdyxlWKGW&index=5",null,250);
	

	
	echo "<center style='width:80%'>".CompileTr3($tr)."</center>";	
	
}

function rules_sources_link_order_js(){
	header("content-type: application/x-javascript");
	//'$MyPage?rules-sources-order-js=yes&key=$mkey&order=0&t={$_GET["t"]}&tt={$_GET["tt"]}
	$tpl=new templates();
	$page=CurrentPageName();
	$key=$_GET["key"];
	$order=$_GET["order"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$aclid=$_GET["aclid"];
	$time=time();
	
$html="
var xSave$time= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();	
	$('#table-items-$tt').flexReload();
	$('#table-items-$t').flexReload();		
}

function SaveR$time(){
	var XHR = new XHRConnection();
	XHR.appendData('rules-sources-order', 'yes');
	XHR.appendData('key', '$key');
	XHR.appendData('aclid', '$aclid');
	XHR.appendData('order', '$order');
	XHR.sendAndLoad('$page', 'POST',xSave$time);
}

SaveR$time();";

echo $html;
	
}

function EmptyCache(){
	$CACHE_DIR=dirname(__FILE__)."/ressources/logs/proxy.pacs";
	$list = @glob("$CACHE_DIR/*");
	$size=0;
	$c=0;
	$gsize=0;
	$err=0;
	while (list ($index, $filename) = each ($list)){
		$size=@filesize($filename);
		@unlink($filename);
		if(!is_file($filename)){
			$gsize=$gsize+$size;
			$c++;
			continue;
		}
		
		$err++;
	}
	$gsize=FormatBytes($gsize/1024);
	$gsize=str_replace("&nbsp;", " ", $gsize);
	echo "Deleted: $c file(s) ($gsize)\nDelete Error: $err file(s)\n";
	
}

function rules_sources_link_order(){
	$key=$_POST["key"];
	$direction=$_POST["order"];
	$aclid=$_POST["aclid"];	
	
	$q=new mysql_squid_builder();
	$sql="SELECT zorder FROM wpad_sources_link WHERE zmd5='$key'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$OlOrder=$ligne["zorder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE wpad_sources_link SET zorder='$OlOrder' WHERE zorder='$NewOrder' AND aclid='$aclid'";
	//echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	$sql="UPDATE wpad_sources_link SET zorder='$NewOrder' WHERE zmd5='$key'";
	$q->QUERY_SQL($sql);
	//echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM wpad_sources_link WHERE aclid='$aclid' ORDER BY zorder");
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$c++;
		$sql="UPDATE wpad_sources_link SET `zorder`='$c' WHERE zmd5='$zmd5'";
		$q->QUERY_SQL($sql);
		//echo "LOOP::".$sql."\n";
		if(!$q->ok){echo $q->mysql_error;}
		
	
	}
	
	
}

function debug_disable_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ProxyPacDynamicDebug=$sock->GET_INFO("ProxyPacDynamicDebug");
	if(!is_numeric($ProxyPacDynamicDebug)){$ProxyPacDynamicDebug=0;}
	$ProxyPacDynamicDebugA=0;
	$ProxyPacDynamicDebugAsk="{disable} {debug} ?";
	$tt=time();
	if($ProxyPacDynamicDebug==0){
		$ProxyPacDynamicDebugA=1;
		$ProxyPacDynamicDebugAsk="{enable} {debug} ?";
	}
	
	$ask=$tpl->javascript_parse_text($ProxyPacDynamicDebugAsk);

$html="
var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
}

	
function NewRule$tt(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('ProxyPacDynamicDebug', '$ProxyPacDynamicDebugA');
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
NewRule$tt();";
	echo $html;
}

function ProxyPacDynamicDebug(){
	$sock=new sockets();
	$sock->SET_INFO("ProxyPacDynamicDebug", $_POST["ProxyPacDynamicDebug"]);
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}
function debug_empty() {
	$sock=new sockets();
	$sock->getFrameWork("squid.php?proxy-pac-empty-debug=yes");
	
}

function settings_popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$SessionCache=intval($sock->GET_INFO("ProxyPacCacheTime"));
	$ProxyPacLockScript=intval($sock->GET_INFO("ProxyPacLockScript"));
	$ProxyPacLockScriptContent=$sock->GET_INFO("ProxyPacLockScriptContent");
	if($SessionCache==0){$SessionCache=10;}
	$DenyDnsResolve=intval($sock->GET_INFO("DenyDnsResolve"));
	$t=time();
	$html="
	<div style='font-size:26px;margin-bottom:26px'>{settings}</div>		
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px;vertical-align:middle' >{cache_time}:</td>
		<td style='font-size:18px;vertical-align:middle'>". Field_text("ProxyPacCacheTime",$SessionCache,"font-size:18px;width:120px;explain={ProxyPacCacheTime_explain};")."&nbsp;{minutes}</td>			
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;vertical-align:middle' >". texttooltip("{do_not_resolv_ipaddr_wpad}","{do_not_resolv_ipaddr_wpad_ex}").":</td>
		<td style='font-size:18px;vertical-align:middle'>". 
		Field_checkbox_design("DenyDnsResolve",1,$DenyDnsResolve)."</td>			
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:18px;vertical-align:middle' >{lock_script_with_this_script}:</td>
		<td style='font-size:18px;vertical-align:middle'>". 
		Field_checkbox_design("ProxyPacLockScript",1,$ProxyPacLockScript,"ProxyPacLockScriptCheck()")."</td>			
	</tr>					

				
<tr><td colspan=2 ><textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>$ProxyPacLockScriptContent</textarea>
		</center>
	</td>
	</tr>				
				
	<tr><td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td></tr>	
	</table>		
	</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ProxyPacCacheTime', document.getElementById('ProxyPacCacheTime').value);
	var pp=encodeURIComponent(document.getElementById('text$t').value);
	XHR.appendData('ProxyPacLockScriptContent',pp);
	if(document.getElementById('ProxyPacLockScript').checked){
		XHR.appendData('ProxyPacLockScript',1);
	}else{
		XHR.appendData('ProxyPacLockScript',0);
	}
	
	if(document.getElementById('DenyDnsResolve').checked){
		XHR.appendData('DenyDnsResolve',1);
	}else{
		XHR.appendData('DenyDnsResolve',0);
	}	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function ProxyPacLockScriptCheck(){
	document.getElementById('text$t').disabled=true;
	if(document.getElementById('ProxyPacLockScript').checked){
		document.getElementById('text$t').disabled=false;
	}
}
ProxyPacLockScriptCheck();
</script>			
			
";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function settings_save(){
	$sock=new sockets();
	$_POST["ProxyPacLockScriptContent"]=url_decode_special_tool($_POST["ProxyPacLockScriptContent"]);
	$sock->SET_INFO("ProxyPacCacheTime", $_POST["ProxyPacCacheTime"]);
	$sock->SET_INFO("ProxyPacLockScript", $_POST["ProxyPacLockScript"]);
	$sock->SET_INFO("DenyDnsResolve", $_POST["DenyDnsResolve"]);
	
	$sock->SaveConfigFile($_POST["ProxyPacLockScriptContent"], "ProxyPacLockScriptContent");
}

function events_script_js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zDate FROM wpad_events WHERE zmd5='$zmd5'"));
	$title=$ligne["zDate"];
	
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(653,'$page?events-script-popup=yes&zmd5=$zmd5&t=$t','$title',true)";
}
function events_script_tester_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zDate FROM wpad_events WHERE zmd5='$zmd5'"));
	$title=$ligne["zDate"];
	$test_this_script=$tpl->javascript_parse_text("{test_this_script}");
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(550,'$page?events-script-tester-popup=yes&zmd5=$zmd5&t=$t','$test_this_script:$title',true)";	
	
}

function events_script_popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$zmd5=$_GET["zmd5"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT script FROM wpad_events WHERE zmd5='$zmd5'"));
	if($users->PACTESTER_INSTALLED){
		$PACTESTER="<center style='margin-top:10px'>".button("{test_this_script}","Loadjs('$page?events-script-tester-js=yes&zmd5=$zmd5',true)")."</center>";
	}
	$datas=base64_decode($ligne["script"]);
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>$datas</textarea>$PACTESTER";
}

function events_script_tester_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ruleid=$_GET["ruleid"];
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=time();
	$ligne=array();
	$button="{test}";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_destination_rules WHERE zmd5='$zmd5'"));
	
	if(!isset($_SESSION["TESTER-URL"])){$_SESSION["TESTER-URL"]="http://www.google.com";}
	if(!isset($_SESSION["TESTER-IPADDR"])){$_SESSION["TESTER-IPADDR"]=$_SERVER["REMOTE_ADDR"];}
	
	$html="
	
	<span id=form2-$tt></span>
	<div id='$t'>
	<center class=form style='width:95%'>
	
	<table style='width:100%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:18px'>{url}:</td>
		<td>". Field_text("TESTER-URL",$_SESSION["TESTER-URL"],"font-size:18px;width:350px",null,null,null,false,"Save$ttt(event)")."</td>
			</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}:</td>
		<td>". Field_text("TESTER-IPADDR",$_SESSION["TESTER-IPADDR"],"font-size:18px;width:220PX",null,null,null,false,"Save$ttt(event)")."</td>
	</tr>
	<tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"SaveR$ttt()",22)."</td>
	</tr>
	</tbody>
	</table>
	</center>
	</div>
<script>
var xSave$ttt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
}
	
function Save$ttt(e){
	if(checkEnter(e)){SaveR$ttt();}
}
function SaveR$ttt(){
	var XHR = new XHRConnection();
	XHR.appendData('TESTER-URL', encodeURIComponent(document.getElementById('TESTER-URL').value));
	XHR.appendData('TESTER-IPADDR', encodeURIComponent(document.getElementById('TESTER-IPADDR').value));
	XHR.appendData('zmd5', encodeURIComponent('$zmd5'));
	XHR.sendAndLoad('$page', 'POST',xSave$ttt);
}
</script>
		";	
echo $tpl->_ENGINE_parse_body($html);
	
}
function events_script_tester_perform(){
	$q=new mysql_squid_builder();
	$filepath=dirname(__FILE__)."/ressources/logs/web/proxy.pac";
	$_SESSION["TESTER-URL"]=url_decode_special_tool($_POST["TESTER-URL"]);
	$_SESSION["TESTER-IPADDR"]=url_decode_special_tool($_POST["TESTER-IPADDR"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT script FROM wpad_events WHERE zmd5='{$_POST["zmd5"]}'"));
	$script=base64_decode($ligne["script"]);
	@file_put_contents($filepath, $script);
	$arrayURI=parse_url($_SESSION["TESTER-URL"]);
	$hostname=$arrayURI["host"];
	
	exec("/usr/bin/pactester -p $filepath -u \"{$_SESSION["TESTER-URL"]}\" -h \"$hostname\" -c \"{$_SESSION["TESTER-IPADDR"]}\" -e 2>&1",$results);
	echo @implode("\n", $results);
}

function settings_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{settings}");
	echo "YahooWin2(890,'$page?settings-popup=yes','$title',true)";
}

function rules_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ruleid)){$ruleid=0;}
	
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM wpad_rules WHERE ID='$ruleid'"));
		$title=$ligne["rulename"];
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(653,'$page?rules-tabs=yes&ID=$ruleid&t=$t','$title',true)";
}

function rules_proxies_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	if(!is_numeric($ruleid)){die();}
	
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM wpad_rules WHERE ID='$ruleid'"));
		$title=$ligne["rulename"];
	}
	
	$title=$tpl->javascript_parse_text($title."::{new_proxy}");
	echo "YahooWin3(550,'$page?rules-proxies-popup=yes&ID=$ruleid&t=$t&tt=$tt','$title',true)";	
	
}

function rules_destination_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$zmd5=$_GET["zmd5"];
	
	
	
	if($ruleid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM wpad_rules WHERE ID='$ruleid'"));
		$title=$ligne["rulename"];
	}
	
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename,xtype FROM wpad_destination_rules WHERE zmd5='$zmd5'"));
		$rulename=$ligne["rulename"]."&nbsp;&raquo;&nbsp;".$ligne["xtype"];
	}	
	
	$title=$tpl->javascript_parse_text($title."::$rulename");
	echo "YahooWin3(550,'$page?rules-destination-popup=yes&ruleid=$ruleid&t=$t&tt=$tt&zmd5=$zmd5','$title',true)";	
}

function rules_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["rules-sources"]='{sources}';
	$array["rules-whitelisted"]='{whitelist}';
	$array["rules-destination"]='{subrules}';
	$array["rules-proxies"]='{proxy_servers}';
	$array["rules-options"]='{options}';
	
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:17px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_autoconfiguration_rules_tabs");
	
}
function rules_destination_section(){
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?rules-destination-table=yes&t={$_GET["t"]}&tt={$_GET["tt"]}&ID={$_GET["ID"]}',true);
	</script>
	";
	
	echo $html;
}

function rules_destination(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("wpad_destination_rules", "rulename")){$q->CheckTables(null,true);}
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$tt},
	],";
	
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
		url: '$page?rules-destination-search=yes&t=$t&tt=$tt&ruleid={$_GET["ID"]}',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'zorder', width :31, sortable : true, align: 'center'},
		{display: '$rulename', name : 'rulename', width :395, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'up', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$items', name : 'value'},
	{display: 'Proxy', name : 'proxyserver'},
	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}
	
var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();	
}

	
function NewRule$tt(){
	var rulename=encodeURIComponent(prompt('$rulename'));
	if(rulename=='null'){return;}
	if(!rulename){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-add', rulename);
	XHR.appendData('ruleid', '{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
function RuleDestinationDelete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('rules-destination-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}
	
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
	
	
function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
}
var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
}
function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
}
	
function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
	
function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('rules-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
Start$tt();
	
</script>
";
echo $html;
	
}
function rules_destination_delete(){
	$md5=$_POST["rules-destination-delete"];
	$sql="DELETE FROM wpad_destination_rules WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function rules_destination_create(){
	$q=new mysql_squid_builder();
	$rulename=url_decode_special_tool($_POST["rules-destination-add"]);
	$ruleid=$_POST["ruleid"];
	$zmd5=md5("$ruleid$rulename");
	$rulename=mysql_escape_string2($rulename);
	
	if(!$q->FIELD_EXISTS("wpad_destination_rules", "rulename")){
		$q->QUERY_SQL("ALTER TABLE `wpad_destination_rules` ADD `rulename` VARCHAR(255) NOT NULL, ADD INDEX (`rulename`)");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	
	$q->QUERY_SQL("INSERT INTO wpad_destination_rules (`rulename`,`zmd5`,`aclid`,`enabled`) VALUES ('$rulename','$zmd5','$ruleid',1)");
	if(!$q->ok){echo $q->mysql_error;}
}

function explainArule($ID,$color="black"){
	
	$acls=new squid_acls_groups();
	$q=new mysql_squid_builder();
	$h=array();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_rules WHERE ID='$ID'"));
	$dntlhstname=$ligne["dntlhstname"];
	$isResolvable=$ligne["isResolvable"];
	$FinishbyDirect=$ligne["FinishbyDirect"];
	
	$sql="SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.* 
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID 
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";
	
	if($GLOBALS['VERBOSE']){echo "\n<HR>$sql</hr>\n";}
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return "</a><br><span style='color:#C52B06;font-size:12px;text-decoration:none'>$q->mysql_error<br>\n$sql</span>";}
	if(mysql_num_rows($results)==0){return "</a><br><span style='color:#C52B06;font-size:12px;text-decoration:none'>{no_source_defined} for #$ID</span>";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$not=null;
		$GroupName=utf8_encode($ligne["GroupName"]);
		$negation=$ligne["negation"];
		if($negation==1){$not="{not} ";}
		$link="Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-org=table-items-{$_GET["t"]}',true);";
		$GroupType=$q->acl_GroupType[$ligne["GroupType"]];
		$f[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$link\" style='font-size:18px;color:$color;text-decoration:underline'>$not{$GroupName} ($GroupType)</a>";
		
	}
	
	
	$sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID ORDER BY zorder";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$g[]= "</a><br><span style='color:#C52B06;font-size:18px;color:$color;text-decoration:none'>$q->mysql_error<br>\n$sql</span>";}
	
	if(mysql_num_rows($results)>0){
		while ($ligne = mysql_fetch_assoc($results)) {
			$g[]="{$ligne["proxyserver"]}:{$ligne["proxyport"]}";
		}
		
		if($FinishbyDirect==1){$g[]="{direct_connection}";}
		
	}else{
		$g[]="{direct_connection} #$ID";
		
	}
	
	
	$sql="SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
	wpad_white_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_white_link,webfilters_sqgroups
	WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
	AND wpad_white_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_white_link.zorder";
	
	if($GLOBALS['VERBOSE']){echo "\n<HR>$sql</hr>\n";}
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return "</a><br><span style='color:#C52B06;font-size:18px;text-decoration:none'>$q->mysql_error<br>\n$sql</span>";}
	
	$hi=array();
	if($dntlhstname==1){
		$hi[]=" {dnot_proxy_localnames}";
	}
	if($isResolvable==1){
		$hi[]=" {isResolvable}";
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$not=null;
		$GroupName=utf8_encode($ligne["GroupName"]);
		$negation=$ligne["negation"];
		if($negation==1){$not="{not} ";}
		$link="Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-org=table-items-{$_GET["t"]}',true);";
		$GroupType=$q->acl_GroupType[$ligne["GroupType"]];
		$h[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$link\" 
		style='font-size:18px;text-decoration:underline;color:$color'>$not{$GroupName} ($GroupType)</a>";
	
	}	
	
	
	if(count($h)==0){$h[]="{none}";}
	
	
	return "</a><br><span style='color:$color;font-size:18px'>{if_a_computer_matches} ".@implode("&nbsp;{and}&nbsp;", $f).
	"<br>{then_set_proxy_parameters} ".@implode("&nbsp;{or}&nbsp;", $g).
	"<br>". @implode("&nbsp;{or}&nbsp;", $hi)." {and_do_not_use_proxy_for} ".@implode("&nbsp;{or}&nbsp;", $h)."</span>";
	
}


function rules_create(){
	$rulnename=mysql_escape_string2(url_decode_special_tool($_POST["new-rule"]));
	
	
	
	$sql="INSERT IGNORE INTO `wpad_rules` (`rulename`,`enabled`,`zorder`) VALUES ('$rulnename',1,0)";
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("wpad_rules", "zorder")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
	}
	if(!$q->FIELD_EXISTS("wpad_sources_link", "zorder")){
		$q->QUERY_SQL("ALTER TABLE `wpad_sources_link` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
	}	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rules_options(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("wpad_rules", "FinishbyDirect")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `FinishbyDirect`  smallint( 1 ) DEFAULT '0',ADD INDEX (`FinishbyDirect`)");
	}
	
	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=time();
	$ligne=array();
	$button="{apply}";
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_rules WHERE ID='$ID'"));
	$html="
	<div id='$t'>
	<center class=form style='width:95%'>
	<table style='width:100%' >
	<tbody>
	<tr>
	<td class=legend style='font-size:18px'>". texttooltip("{dnot_proxy_localnames}","{dnot_proxy_localnames_explain}").":</td>
	<td>". Field_checkbox_design("dntlhstname-$ttt", 1,$ligne["dntlhstname"])."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:18px'>". texttooltip("{dnot_proxy_lisResolvable}","{dnot_proxy_lisResolvable_explain}").":</td>
	<td>". Field_checkbox_design("isResolvable-$ttt", 1,$ligne["isResolvable"])."</td>
	</tr>			
	<tr>
	<td class=legend style='font-size:18px'>". texttooltip("{return_direct_mode}","{wpad_return_direct_mode}").":</td>
	<td>". Field_checkbox_design("FinishbyDirect-$ttt", 1,$ligne["FinishbyDirect"])."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"SaveR$ttt()",22)."</td>
	</tr>
	</tbody>
	</table>
	</center>
	</div>
<script>
var xSave$ttt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID=$ID;
	$('#table-items-$tt').flexReload();
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
}
	

function SaveR$ttt(){
	var XHR = new XHRConnection();
	var dntlhstname=0;
	var isResolvable=0;
	var FinishbyDirect=0;
	if( document.getElementById('dntlhstname-$ttt').checked){dntlhstname=1;}
	if( document.getElementById('isResolvable-$ttt').checked){isResolvable=1;}
	if( document.getElementById('FinishbyDirect-$ttt').checked){FinishbyDirect=1;}
	XHR.appendData('dntlhstname', dntlhstname);
	XHR.appendData('isResolvable', isResolvable);
	XHR.appendData('FinishbyDirect', FinishbyDirect);
	XHR.appendData('ID', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$ttt);
		
	}
</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function rules_options_save(){
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("wpad_rules", "dntlhstname")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `dntlhstname`  smallint( 1 ) DEFAULT '0'");
	}
	if(!$q->FIELD_EXISTS("wpad_rules", "isResolvable")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `isResolvable`  smallint( 1 )  DEFAULT '0'");
	}
	
	
	
	$sql="UPDATE wpad_rules SET
			dntlhstname='{$_POST["dntlhstname"]}',
			isResolvable='{$_POST["isResolvable"]}',
			FinishbyDirect='{$_POST["FinishbyDirect"]}'
		WHERE ID='{$_POST["ID"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

/*			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`xtype` VARCHAR(40) NOT NULL, 
			`value` TEXT NOT NULL, 
			`destinations` TEXT NOT NULL, 
			`proxyserver` VARCHAR(128) NOT NULL ,
			`proxyport` INT UNSIGNED ,
			`enabled` smallint(1) NOT NULL,
			`zorder` smallint(2) NOT NULL ,
			 KEY `xtype` (`xtype`),
			 KEY `enabled` (`enabled`),
			INDEX ( `aclid` ,`zorder`, `proxyserver`,`proxyport`)
*/

function rules_destination_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ruleid=$_GET["ruleid"];
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=time();
	$ligne=array();
	$button="{apply}";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_destination_rules WHERE zmd5='$zmd5'"));
	
	
	
	$html="
	
	<span id=form2-$tt></span>
	<div id='$t'>
	<center class=form style='width:95%'>
	
	<table style='width:100%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:18px'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$ttt", 1,$ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{order}:</td>
		<td>". Field_text("zorder-$ttt",$ligne["zorder"],"font-size:18px;width:60px",null,null,null,false,"Save$ttt(event)")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{rulename}:</td>
		<td>". Field_text("rulename-$ttt",$ligne["rulename"],"font-size:18px;width:99%",null,null,null,false,"Save$ttt(event)")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{type}:</td>
		<td>". Field_array_Hash($q->PROXY_PAC_TYPES, "xtype-$ttt",$ligne["xtype"],"ChangeXtype$ttt()",null,0,
			"font-size:18px")."</td>
	</tr>
	<tr>
		<td colspan=2>
			<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:98%;
			height:250px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important' id='value-$ttt'>{$ligne["value"]}</textarea>
		</td>
	</tr>
	<tr>
		<td colspan=2><div class=explain style='font-size:16px'>{wpad_destination_rules_proxy_explain}</span>
	</tr>
	<tr>
		<td colspan=2>
			<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:98%;
			height:250px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important' id='rules-destination-$ttt'>{$ligne["destinations"]}</textarea>
		</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button($button,"SaveR$ttt()",22)."</td>
	</tr>
	</tbody>
	</table>
	</center>
	</div>
<script>
var xSave$ttt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#table-items-$tt').flexReload();
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	
}
	
function Save$ttt(e){
	if(checkEnter(e)){SaveR$ttt();}
}
	
function SaveR$ttt(){
	var XHR = new XHRConnection();
	XHR.appendData('enabled', encodeURIComponent(document.getElementById('enabled-$ttt').value));
	XHR.appendData('zorder', encodeURIComponent(document.getElementById('zorder-$ttt').value));
	XHR.appendData('rulename', encodeURIComponent(document.getElementById('rulename-$ttt').value));
	XHR.appendData('xtype', encodeURIComponent(document.getElementById('xtype-$ttt').value));
	XHR.appendData('value', encodeURIComponent(document.getElementById('value-$ttt').value));
	XHR.appendData('rules-destination-edit',encodeURIComponent(document.getElementById('rules-destination-$ttt').value));
	XHR.appendData('zmd5', encodeURIComponent('$zmd5'));
	XHR.sendAndLoad('$page', 'POST',xSave$ttt);
}
	
function ChangeXtype$ttt(){
	var type=document.getElementById('xtype-$ttt').value;
	LoadAjax('form2-$tt','$page?rules-destination-form2=yes&ruleid=$ruleid&t=$ttt&type='+type,true);
}
	
ChangeXtype$ttt();
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function rules_destination_edit(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
		
	}
	
	if($_POST["xtype"]==null){echo "Type = NULL\n";return;}
	
	/*			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
	 `aclid` BIGINT UNSIGNED ,
	`xtype` VARCHAR(40) NOT NULL,
	`value` TEXT NOT NULL,
	`destinations` TEXT NOT NULL,
	`proxyserver` VARCHAR(128) NOT NULL ,
	`proxyport` INT UNSIGNED ,
	`enabled` smallint(1) NOT NULL,
	`zorder` smallint(2) NOT NULL ,
	KEY `xtype` (`xtype`),
	KEY `enabled` (`enabled`),
	INDEX ( `aclid` ,`zorder`, `proxyserver`,`proxyport`)
	*/

	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE wpad_destination_rules 
			SET rulename='{$_POST["rulename"]}',
			xtype='{$_POST["xtype"]}',
			enabled='{$_POST["enabled"]}',
			zorder='{$_POST["zorder"]}',
			destinations='{$_POST["rules-destination-edit"]}',
			`value`='{$_POST["value"]}' WHERE zmd5='{$_POST["zmd5"]}'");
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function rules_destination_form2(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$type=$_GET["type"];
	echo "<div class=explain style='font-size:16px'>
			<span style='font-weight:bold'>".$tpl->_ENGINE_parse_body($q->PROXY_PAC_TYPES[$type])."</span><br>
			".$tpl->_ENGINE_parse_body($q->PROXY_PAC_TYPES_EXPLAIN[$type])."</div>";
	
	
	
}

function rules_proxies_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=time();
	$ligne=array();
	$button="{add}";
	
	
	$html="
	<div id='$t'>
	<center class=form style='width:95%'>
	<table style='width:100%' >
		<tbody>
			<tr>
				<td class=legend style='font-size:18px'>{hostname}:</td>
				<td>". Field_text("hostname-$ttt",null,"font-size:18px;width:99%",null,null,null,false,"Save$ttt(event)")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:18px'>{listen_port}:</td>
				<td>". Field_text("port-$ttt",null,"font-size:18px;width:90px",null,null,null,false,"Save$ttt(event)")."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><hr>". button($button,"SaveR$ttt()",22)."</td>
			</tr>
		</tbody>
	</table>
	</center>
	</div>
	<script>
		var xSave$ttt= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			var ID=$ID;
			$('#table-items-$tt').flexReload();
			$('#table-$t').flexReload();
			$('#flexRT$t').flexReload();
			$('#flexRT$tt').flexReload();
			ExecuteByClassName('SearchFunction');
			YahooWin3Hide();
			
		}	

		function Save$ttt(e){
			if(checkEnter(e)){SaveR$ttt();}
		}
		
		function SaveR$ttt(){
			var XHR = new XHRConnection();
		    XHR.appendData('rules-proxies-hostname', document.getElementById('hostname-$ttt').value);
		    XHR.appendData('port', document.getElementById('port-$ttt').value);
		    XHR.appendData('ID', '$ID');
		    XHR.sendAndLoad('$page', 'POST',xSave$ttt);  
			
		}
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function rules_proxies_add(){
	$ID=$_POST["ID"];
	if(!is_numeric($ID)){echo "No ID?\n";}
	if($ID==0){echo "No ID -> 0 ?\n";}
	$hostname=$_POST["rules-proxies-hostname"];
	$port=$_POST["port"];
	if(!is_numeric($port)){$port="3128";}
	if($hostname==null){$hostname="1.2.3.4";}
	
	$zmd5=md5("$ID$hostname$port");
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT INTO wpad_destination (zmd5,aclid,proxyserver,proxyport,zorder) 
			VALUES ('$zmd5','$ID','$hostname','$port',0)");
	if(!$q->ok){echo $q->mysql_error;}	
}



function rules_proxies_unlink(){
	$zmd5=$_POST["rules-proxy-unlink"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM wpad_destination where `zmd5`='$zmd5'");
	if(!$q->ok){echo $q->mysql_error;}
}

function rules_proxies_move(){
	
	$zmd5=$_POST["rules-proxies-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_destination WHERE zmd5='$zmd5'"));
	$aclid=$ligne["aclid"];
	$LastOrder=$ligne["zorder"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
	}
	
	if(isset($_POST["rules-proxies-zorder"])){
		if(is_numeric($_POST["rules-proxies-zorder"])){
			$NewOrder=$_POST["rules-proxies-zorder"];
		}
	}
		
	$q->QUERY_SQL("UPDATE wpad_destination SET zorder='$NewOrder' WHERE zmd5='$zmd5'");
	$q->QUERY_SQL("UPDATE wpad_destination SET zorder='$LastOrder' zorder='$NewOrder' AND aclid='$aclid' AND zmd5<>'$zmd5'");
		
	$sql="SELECT *  FROM wpad_destination WHERE aclid='$aclid' ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE wpad_destination SET zorder='$c' WHERE zmd5='$zmd5'");
		$c++;
		
	}	
}

function rules_destination_move(){
	$zmd5=$_POST["rules-destination-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_destination_rules WHERE zmd5='$zmd5'"));
	$aclid=$ligne["aclid"];
	$LastOrder=$ligne["zorder"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
	}
	
	
	
	if(isset($_POST["rules-destination-zorder"])){
		if(is_numeric($_POST["rules-destination-zorder"])){
			$NewOrder=$_POST["rules-destination-zorder"];
		}
	}
	
	$q->QUERY_SQL("UPDATE wpad_destination_rules SET zorder='$NewOrder' WHERE zmd5='$zmd5'");
	$q->QUERY_SQL("UPDATE wpad_destination_rules SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND aclid='$aclid' AND zmd5<>'$zmd5'");
	
	$sql="SELECT *  FROM wpad_destination_rules WHERE aclid='$aclid' ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE wpad_destination_rules SET zorder='$c' WHERE zmd5='$zmd5'");
		$c++;
	
	}	
	
}

function rules_move(){
	$ID=$_POST["rules-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_rules WHERE ID='$ID'"));
	$LastOrder=$ligne["zorder"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
		$LastOrder=$ligne["zorder"]+1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
		$LastOrder=$ligne["zorder"]-1;
	}
	
	if(isset($_POST["rules-zorder"])){
		if(is_numeric($_POST["rules-zorder"])){
			$NewOrder=$_POST["rules-zorder"];
			$LastOrder=$ligne["zorder"]+1;
		}
	}
	if($NewOrder<0){$NewOrder=0;}
	if($LastOrder<0){$LastOrder=0;}
	
	$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$NewOrder' WHERE ID='$ID'");
	$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND ID<>$ID");
	
	$sql="SELECT *  FROM wpad_rules ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["ID"];
		$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$c' WHERE ID='$zmd5'");
		$c++;
	
	}	
	
}

function rules_sources_unlink(){
	$zmd5=$_POST["rules-sources-unlink"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM wpad_sources_link where `zmd5`='$zmd5'");
	if(!$q->ok){echo $q->mysql_error;}
}
function rules_whitelisted_unlink(){
	$zmd5=$_POST["rules-whitelisted-unlink"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM wpad_white_link where `zmd5`='$zmd5'");
	if(!$q->ok){echo $q->mysql_error;}
}
function rules_sources_negation(){
	$zmd5=$_POST["rules-sources-negation"];
	$sql="UPDATE wpad_sources_link SET negation={$_POST["value"]} WHERE zmd5='$zmd5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}
function rules_whitelisted_negation(){
	$zmd5=$_POST["rules-whitelisted-negation"];
	$sql="UPDATE wpad_white_link SET negation={$_POST["value"]} WHERE zmd5='$zmd5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rules_sources_link(){
	$ID=$_POST["rules-sources-link"];
	$gpid=$_POST["gpid"];
	$zmd5=md5("$ID$gpid");
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT INTO wpad_sources_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$ID','0','$gpid',1)");
	if(!$q->ok){echo $q->mysql_error;}
}
function rules_whitelisted_link(){
	$ID=$_POST["rules-whitelisted-link"];
	$gpid=$_POST["gpid"];
	$zmd5=md5("$ID$gpid");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT INTO wpad_white_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$ID','0','$gpid',1)");
	if(!$q->ok){echo $q->mysql_error;}
}

function rules_proxies(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$ports=$tpl->_ENGINE_parse_body("{listen_port}");
	$new_item=$tpl->_ENGINE_parse_body("{new_proxy}");
	$remove=$tpl->_ENGINE_parse_body("{remove_this_proxy_ask}");
	$t=$_GET["t"];
	$tt=time();
	$html="
<table class='table-items-$tt' style='display: none' id='table-items-$tt' style='width:99%'></table>
	<script>
	var DeleteAclKey$tt=0;
	function LoadTable$tt(){
	$('#table-items-$tt').flexigrid({
	url: '$page?rules-proxies-search=yes&ID=$ID&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'zorder', width : 31, sortable : true, align: 'center'},
	{display: '$servername', name : 'proxyserver', width : 284, sortable : true, align: 'left'},
	{display: '$ports', name : 'proxyport', width : 100, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : LinkAclItem$tt},
	
	],
	searchitems : [
	{display: '$servername', name : 'proxyserver'},
	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
	}
	function LinkAclItem$tt() {
	Loadjs('$page?rules-proxies-js=yes&ID=$ID&t=$t&tt=$tt');
	
	}
	
	
	var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-items-$tt').flexReload();
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	
	function LinkAclRuleGpid$tt(gpid){
	var XHR = new XHRConnection();
	XHR.appendData('rules-whitelisted-link', '$ID');
	XHR.appendData('gpid', gpid);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	var x_DeleteObjectLinks$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#row'+DeleteAclKey$tt).remove();
	$('#table-$t').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	
	
	function DeleteObjectLinks$tt(mkey){
	if(!confirm('$remove')){return;}
	DeleteAclKey$tt=mkey;
	var XHR = new XHRConnection();
	XHR.appendData('rules-proxy-unlink', mkey);
	XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks$tt);
	}
	
	function MoveObjectLinks$tt(mkey,direction){
		var XHR = new XHRConnection();
		XHR.appendData('rules-proxies-move', mkey);
		XHR.appendData('direction', direction);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);	
	
	}
	
	function MoveObjectLinksAsk$tt(mkey,def){
		var zorder=prompt('Order',def);
		if(!zorder){return;}
		var XHR = new XHRConnection();
		XHR.appendData('rules-proxies-move', mkey);
		XHR.appendData('rules-proxies-zorder', zorder);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);	
	
	}
	

	setTimeout('LoadTable$tt()',600);
	</script>
	
	";
	
	echo $html;
}	
	


function rules_whitelisted(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$t=$_GET["t"];
	$tt=time();
	$html="
	<table class='table-items-$tt' style='display: none' id='table-items-$tt' style='width:99%'></table>
	<script>
	var DeleteAclKey$tt=0;
	function LoadTable$tt(){
	$('#table-items-$tt').flexigrid({
	url: '$page?rules-whitelisted-search=yes&ID=$ID&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '$objects', name : 'gpid', width : 415, sortable : true, align: 'left'},
	{display: '$reverse', name : 'negation', width : 31, sortable : false, align: 'center'},
	{display: '$items', name : 'items', width : 69, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : LinkAclItem$tt},
	
	],
	searchitems : [
	{display: '$items', name : 'GroupName'},
	],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
	}
	function LinkAclItem$tt() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid$tt&table-acls-t=$tt&wpad=yes');
	
	}
	
	
var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-items-$tt').flexReload();
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
function LinkAclRuleGpid$tt(gpid){
	var XHR = new XHRConnection();
	XHR.appendData('rules-whitelisted-link', '$ID');
	XHR.appendData('gpid', gpid);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
	
var x_DeleteObjectLinks$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#row'+DeleteAclKey$tt).remove();
	$('#table-$t').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
	
function DeleteObjectLinks$tt(mkey){
	DeleteAclKey$tt=mkey;
	var XHR = new XHRConnection();
	XHR.appendData('rules-whitelisted-unlink', mkey);
	XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks$tt);
}
	
function ChangeNegation$tt(mkey){
	var value=0;
	var XHR = new XHRConnection();
	if(document.getElementById('negation-'+mkey).checked){value=1;}
	XHR.appendData('rules-whitelisted-negation', mkey);
	XHR.appendData('value', value);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
setTimeout('LoadTable$tt()',600);
</script>
	
	";
	
	echo $html;
}

function rules_sources(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$order=$tpl->_ENGINE_parse_body("{order}");
	$t=$_GET["t"];
	$tt=time();
	$html="
	
	<table class='table-items-$tt' style='display: none' id='table-items-$tt' style='width:99%'></table>
<script>
var DeleteAclKey$tt=0;
function LoadTable$tt(){
$('#table-items-$tt').flexigrid({
	url: '$page?rules-sources-search=yes&ID=$ID&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
		{display: '$order', name : 'zorder', width : 31, sortable : true, align: 'center'},
		{display: '$objects', name : 'gpid', width : 274, sortable : true, align: 'left'},
		{display: '$reverse', name : 'negation', width : 31, sortable : false, align: 'center'},
		{display: '$items', name : 'items', width : 69, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'up', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : LinkAclItem$tt},
	
		],	
	searchitems : [
		{display: '$items', name : 'GroupName'},
		],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
}
function LinkAclItem$tt() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid$tt&table-acls-t=$tt&wpad=yes');
	
}	


	var x_LinkAclRuleGpid$tt= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-items-$tt').flexReload();
		$('#table-$t').flexReload();
		$('#flexRT$t').flexReload();
		$('#flexRT$tt').flexReload();
		ExecuteByClassName('SearchFunction');
	}	

function LinkAclRuleGpid$tt(gpid){
		var XHR = new XHRConnection();
		XHR.appendData('rules-sources-link', '$ID');
		XHR.appendData('gpid', gpid);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);  		
	}
	
	var x_DeleteObjectLinks$tt= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#row'+DeleteAclKey$tt).remove();
		$('#table-$t').flexReload();
		ExecuteByClassName('SearchFunction');
	}	
	
	
	function DeleteObjectLinks$tt(mkey){
		DeleteAclKey$tt=mkey;
		var XHR = new XHRConnection();
		XHR.appendData('rules-sources-unlink', mkey);
		XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks$tt);
		  		
	}
	
	function ChangeNegation$tt(mkey){
		var value=0;
		var XHR = new XHRConnection();
		if(document.getElementById('negation-'+mkey).checked){value=1;}
		XHR.appendData('rules-sources-negation', mkey);
		XHR.appendData('value', value);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}

	
	
setTimeout('LoadTable$tt()',600);
</script>
	
	";
	
	echo $html;
	
}
function popup(){
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("wpad_rules")==0){
		
		$html="<div style='width:95%' class=form>
				<center style='margin:20px'>". button("{autoconfiguration_wizard}","Loadjs('squid.autocofiguration.wizard.php')",40)."
				</center>
				";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	$page=CurrentPageName();
	$array["rules"]='{rules}';
	$array["events"]='{events}';
	$array["debug"]='{debug}';
	$array["help"]='{online_help}';
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_autoconfiguration_tabs")."<script>LeftDesign('autoconf-256-opac20.png');</script>";
	
	
}
function events(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$t},
	{name: '$rebuild_tables', bclass: 'Delz', onpress : RebuildTables$t},
	],";
	
	$title=$tpl->javascript_parse_text("{autoconfiguration}: {events}");
	$buttons=null;
	$zDate=$tpl->javascript_parse_text("{zDate}");
	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$browser=$tpl->javascript_parse_text("{browser}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?events-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'n', width :31, sortable : false, align: 'center'},
	{display: '$zDate', name : 'zDate', width :140, sortable : true, align: 'left'},
	{display: '$rulename', name : 'ruleid', width : 144, sortable : true, align: 'left'},
	{display: '$hostname', name : 'hostname', width :110, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width :109, sortable : true, align: 'left'},
	{display: '$browser', name : 'browser', width : 260, sortable : true, align: 'left'},
	
	],
	$buttons
		searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$browser', name : 'browser'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	});
	});
	
	</script>
	
	";
	echo $html;
}	



function rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("wpad_rules")==0){
	
		$html="<div style='width:95%' class=form>
				<center style='margin:20px'>". button("{autoconfiguration_wizard}","Loadjs('squid.autocofiguration.wizard.php')",40)."
				</center>
				";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	$t=time();
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$empty_cache=$tpl->javascript_parse_text("{empy_cache}");
	$all_rules_lost=$tpl->javascript_parse_text("{all_rules_lost}!");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$rebuild_tables</strong>', bclass: 'Delz', onpress : RebuildTables$t},
	{name: '<strong style=font-size:18px>$parameters</strong>', bclass: 'Settings', onpress : Settings$t},
	{name: '<strong style=font-size:18px>$empty_cache</strong>', bclass: 'Delz', onpress : Empty$t},
	],";		
		
	$title=$tpl->javascript_parse_text("{autoconfiguration}");
	
	
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rules-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zorder', width :31, sortable : true, align: 'center'},
		{display: '<strong style=font-size:18px>$rulename</strong>', name : 'rulename', width : 1064, sortable : false, align: 'left'},	
		{display: '&nbsp;', name : 'enable', width :45, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width :45, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 45, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 45, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function Settings$t(){
	Loadjs('$page?settings-js=yes');
}

	var xNewRule$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#flexRT$t').flexReload();
	}
	
	function RebuildTables$t(){
		if(!confirm('$rebuild_tables ?\\n$all_rules_lost')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('rebuild-tables', 'yes');
		XHR.sendAndLoad('$page', 'POST',xNewRule$t);		
	}
	
	function NewRule$t(){
		var rulename=encodeURIComponent(prompt('$rulename'));
		if(rulename=='null'){return;}
		if(!rulename){return;}
		var XHR = new XHRConnection();
		XHR.appendData('new-rule', rulename);
		XHR.sendAndLoad('$page', 'POST',xNewRule$t);
	
	}
	
	function RuleDelete$t(ID){
		if(confirm('$delete')){
			var XHR = new XHRConnection();
		    XHR.appendData('rule-delete', ID);
		    XHR.sendAndLoad('$page', 'POST',xNewRule$t); 		
		}
	}
	
	var xRuleEnable$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#flexRT$t').flexReload();
	}
	
	
	function Empty$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EmptyCache', 'yes');
		XHR.sendAndLoad('$page', 'POST',xNewRule$t); 		
	
	}
	
	
	function RuleEnable$t(ID,md5){
		var XHR = new XHRConnection();
		XHR.appendData('rule-enable', ID);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',xRuleEnable$t); 	
	
	}
var x_LinkAclRuleGpid$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}	

	function FlexReloadRulesRewrite(){
		$('#flexRT$t').flexReload();
	}

	function MoveObjectLinks$t(mkey,direction){
		var XHR = new XHRConnection();
		XHR.appendData('rules-move', mkey);
		XHR.appendData('direction', direction);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$t);	
	
	}
	
	function MoveObjectLinksAsk$t(mkey,def){
		var zorder=prompt('Order',def);
		if(!zorder){return;}
		var XHR = new XHRConnection();
		XHR.appendData('rules-move', mkey);
		XHR.appendData('rules-zorder', zorder);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$t);	
	
	}

</script>

";	
	echo $html;
	
}


function debug_page(){
$page=CurrentPageName();
$tpl=new templates();
$t=time();
$line=$tpl->javascript_parse_text("{line}");
$verbose=$tpl->_ENGINE_parse_body("{verbose}");
$new_rule=$tpl->_ENGINE_parse_body("{settings}");
$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
$rebuild_tables=$tpl->javascript_parse_text("{empty}");
$download=$tpl->javascript_parse_text("{download2}");



$buttons="
buttons : [
	{name: '$new_rule', bclass: 'Settings', onpress : Settings$t},
	{name: '$download', bclass: 'Down', onpress : download$t},
	{name: '$rebuild_tables', bclass: 'Delz', onpress : empty$t},
	],";
	
	
	
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?debug-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$line', name : 'line', width :850, sortable : true, align: 'left'},
	],
	$buttons
	searchitems : [
	{display: '$line', name : 'line'},
	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '$verbose',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [500]
	
	});
});
	
var xNewRule$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
}
	
function empty$t(){
	if(!confirm('$rebuild_tables ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-debug', 'yes');
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);
}
	
function Settings$t(){
	Loadjs('$page?enable-disable-debug-js=yes&t=$t');
}
	
function download$t(){
	 s_PopUp(\"$page?download-debug=true\",0,0);
}
	
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
		$('#flexRT$t').flexReload();
	}
	
	
function RuleEnable$t(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$t);
}
var x_LinkAclRuleGpid$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
}
	
function MoveObjectLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$t);
}
	
function MoveObjectLinksAsk$t(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-move', mkey);
	XHR.appendData('rules-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$t);
}
</script>
";
echo $html;	
	
	
}

function debug_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	
	
	$searchstring=urlencode(string_to_flexregex());
	$datas=explode("\n",base64_decode($sock->getFrameWork("squid.php?proxy-pac-debug=yes&searchstring=$searchstring")));
	$pageStart = 1;
	
	
	
	if(count($datas)==0){json_error_show("no data");}
	
	krsort($datas);
	$data = array();
	$data['page'] = 1;
	$data['total'] = 1;
	$data['rows'] = array();
	
	
	
	while (list ($num, $ligne) = each ($datas) ){

	
	
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array("$ligne")
		);
	}
	echo json_encode($data);
}

function new_rule(){
	$q=new mysql_squid_builder();
	$q->CheckTables(null,true);
	$rulename=mysql_escape_string2(url_decode_special_tool($_POST["new-rule"]));
	$q->QUERY_SQL("INSERT INTO wpad_rules (rulename,enabled) VALUES ('$rulename','1')");
	if(!$q->ok){echo $q->mysql_error;}
}
function rules_enable(){
	$q=new mysql_squid_builder();
	$q->CheckTables(null,true);
	$q->QUERY_SQL("UPDATE wpad_rules SET `enabled`='{$_POST["enable"]}' WHERE ID='{$_POST["rule-enable"]}'");
	if(!$q->ok){echo $q->mysql_error;}	
}
function rules_delete(){
	$q=new mysql_squid_builder();
	$ID=$_POST["rule-delete"];
	$q->QUERY_SQL("DELETE FROM `wpad_rules` WHERE ID='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_sources_link` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_white_link` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_destination` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_events` WHERE aclid='$ID'");
	
}

function events_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="wpad_events";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("wpad_events: no event");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no event");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$color="black";

	
	
	$zDate=$ligne["zDate"];
	$ruleid=$ligne["ruleid"];
	$ipaddr=$ligne["ipaddr"];
	$browser=$ligne["browser"];
	$hostname=$ligne["hostname"];
	$script=null;
	if($ruleid==0){$ruleid=$no_rule;$color="#D10909";}
	if($ruleid>0){$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM wpad_rules WHERE ID='$ruleid'"));
	$ruleid=utf8_encode($ligne2["rulename"]);
	$script=imgsimple("script-24.png",null,"Loadjs('$MyPage?events-script=yes&zmd5={$ligne["zmd5"]}',true)");
	
	}
	
	$data['rows'][] = array(
	'id' => $ligne['ID'],
		'cell' => array(
					"<span style='font-size:18px;font-weight:normal;color:$color'>$script</span>",
					"<span style='font-size:18px;font-weight:normal;color:$color'>{$ligne["zDate"]}</span>",
					"<span style='font-size:18px;font-weight:normal;color:$color'>$ruleid</span>",
					"<span style='font-size:18px;font-weight:normal;color:$color'>{$ligne["hostname"]}</span>",
					"<span style='font-size:18px;font-weight:normal;color:$color'>{$ligne["ipaddr"]}</span>",
					"<span style='font-size:18px;font-weight:normal;color:$color'>{$ligne["browser"]}</span>",)
	);
	}
	
echo json_encode($data);
}

function rules_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	
	$t=$_GET["t"];
	$search='%';
	$table="wpad_rules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){
		json_error_show("wpad_rules: no rule");
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}	
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	
	$ProxyPacLockScript=intval($sock->GET_INFO("ProxyPacLockScript"));
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
		$delete=imgtootltip("delete-32.png","{delete} Rule:{$ligne["rulename"]}","RuleDelete$t('{$ligne["ID"]}')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"RuleEnable$t('{$ligne["ID"]}','$md5')");	
		$js="Loadjs('$MyPage?rule-js=yes&ID={$ligne["ID"]}&t=$t');";
		
		$up=imgsimple("arrow-up-32.png",null,"MoveObjectLinks$t('{$ligne["ID"]}','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveObjectLinks$t('{$ligne["ID"]}','down')");
		if($ligne["enabled"]==0){$color="#A8A5A5";}
		if($ProxyPacLockScript==1){$color="#A8A5A5";}
		$explainArule=$tpl->_ENGINE_parse_body(explainArule($ligne["ID"],$color));
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
				"<span style='font-size:18px;font-weight:bold;color:$color'><a href=\"javascript:blur();\"
				OnClick=\"javascript:MoveObjectLinksAsk$t('{$ligne['ID']}','{$ligne["zorder"]}')\"
				style='font-size:18px;font-weight:bold;text-decoration:underline'
				>[{$ligne["zorder"]}]</a></span>",
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" 
			style='font-size:22px;color:$color;text-decoration:underline'>".utf8_encode($ligne["rulename"])."</span>$explainArule",
			$enable,$up,$down,$delete )
		);
	}
	
	
echo json_encode($data);		

}

function rules_sources_search(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$ID=$_GET["ID"];
	$t0=$_GET["t"];
	$t=$_GET["tt"];
	$search='%';
	$table="(SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zorder as zorder,wpad_sources_link.zmd5 as mkey,
	webfilters_sqgroups.* FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID AND wpad_sources_link.aclid=$ID) as t";
	
	$page=1;
	
	if($q->COUNT_ROWS("wpad_sources_link")==0){json_error_show("No datas");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$acl=new squid_acls_groups();
	
		while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["mkey"];
		$arrayF=$acl->FlexArray($ligne['ID']);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks$t('$mkey')");
		$negation=Field_checkbox("negation-$mkey", 1,$ligne["negation"],"ChangeNegation$t('$mkey')");
		
		
		
		
		$up=imgsimple("arrow-up-18.png","","Loadjs('$MyPage?rules-sources-order-js=yes&aclid=$ID&key=$mkey&order=0&t={$_GET["t"]}&tt={$_GET["tt"]}')");
		$down=imgsimple("arrow-down-18.png","","Loadjs('$MyPage?rules-sources-order-js=yes&aclid=$ID&key=$mkey&order=1&t={$_GET["t"]}&tt={$_GET["tt"]}')");
		
		$data['rows'][] = array(
			'id' => "$mkey",
			'cell' => array(
			"<span style='font-size:14px;font-weight:bold'>".$ligne["zorder"]."</span>",
			$arrayF["ROW"],
			$negation,"<span style='font-size:14px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",$up,$down,
			$delete)
		);
		}
	echo json_encode($data);
}
function rules_whitelisted_search(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$ID=$_GET["ID"];
	$t0=$_GET["t"];
	$t=$_GET["tt"];
	$linked_table="wpad_white_link";
	
	$search='%';
	$table="(SELECT 
		$linked_table.gpid,
		$linked_table.negation,
		$linked_table.zmd5 as mkey,
		webfilters_sqgroups.* FROM $linked_table,webfilters_sqgroups
		WHERE $linked_table.gpid=webfilters_sqgroups.ID 
		AND $linked_table.aclid=$ID) as t";

	$page=1;

	if($q->COUNT_ROWS($linked_table)==0){json_error_show("No datas");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$acl=new squid_acls_groups();

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["mkey"];
		$arrayF=$acl->FlexArray($ligne['ID']);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks$t('$mkey')");
		$negation=Field_checkbox("negation-$mkey", 1,$ligne["negation"],"ChangeNegation$t('$mkey')");
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array($arrayF["ROW"],
						$negation,"<span style='font-size:14px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",
						$delete)
		);
	}
	echo json_encode($data);
}

function rules_destination_search(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$aclid=$_GET["ruleid"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$FORCE="aclid=$aclid";
	
	$search='%';
	$table="wpad_destination_rules";
	
	$page=1;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("No datas {for} #$aclid",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM $table WHERE $FORCE $searchstring $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no row {for} #$aclid - $FORCE",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["zmd5"];
		$delete=imgsimple("delete-32.png",null,"RuleDestinationDelete$tt('$mkey')");
		$color="black";
		$up=imgsimple("arrow-up-32.png",null,"MoveRuleDestination$tt('$mkey','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveRuleDestination$tt('$mkey','down')");
		
	
		$proxy="{$ligne["proxyserver"]}:{$ligne["proxyport"]}";
		if($proxy<3){$proxy="{no_proxy}";}
		
		
		$value=$ligne["value"];
		$rulename=utf8_encode($ligne["rulename"]);
		$style="style='font-size:16px;font-weight:bold;color:$color'";
		$span="<span >";
		$js="javascript:Loadjs('$MyPage?rules-destination-js=yes&ruleid=$aclid&t=$t&tt=$tt&zmd5=$mkey',true)";
		$Explain=rules_destination_explain($ligne);
		
	
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<a href=\"javascript:blur();\"
						OnClick=\"javascript:MoveRuleDestinationAsk$tt('$mkey','{$ligne["zorder"]}')\"
						$style><span style='text-decoration:underline'>
						[{$ligne["zorder"]}]</span></a></span>",
						"<span $style><a href=\"javascript:blur();\" OnClick=\"$js\" $style>
							<span style='text-decoration:underline'>$rulename</span></a></span>
							<div style='font-size:12px'>$Explain</div>",
						"<span style='font-size:16px;font-weight:bold'>$up</span>",
						"<span style='font-size:16px;font-weight:bold'>$down</span>",
						$delete)
		);
	}
	echo json_encode($data);
}
	
function rules_destination_explain($SQLligne){
	
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtype=$SQLligne["xtype"];
	if(trim($xtype)==null){return;}
	
	if(isset($q->PROXY_PAC_TYPES[$xtype])){
		$xtype=$tpl->_ENGINE_parse_body($q->PROXY_PAC_TYPES[$xtype]);
	}else{
		$xtype="<span style='color:#d32d2d'>!! $xtype</span>";
	}
	
	$destinations=array();
	$values=array();
	
	$value=trim($SQLligne["value"]);
	if($value<>null){
	if(strpos($value, "\n")==0){$values[]=$value;}
	$explode=explode("\n", $value);
	while (list ($num, $ligne) = each ($explode) ){$ligne=trim($ligne);if($ligne==null){continue;}$values[]=$ligne;}
	}
	
	
	$value=trim($SQLligne["destinations"]);
	if($value==null){return;}
	if(strpos($value, "\n")==0){$destinations[]=$value;}
	$explode=explode("\n", $value);
	while (list ($num, $ligne) = each ($explode) ){$ligne=trim($ligne);if($ligne==null){continue;}$destinations[]=$ligne;}
	

	if(count($destinations)==0){$destinations[]="DIRECT";}
	return "&laquo;$xtype&raquo; ".@implode(",", $values)."<br><i>&raquo;&raquo;&nbsp;".@implode(",", $destinations)."</i>";
	
}

function rules_proxies_search(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$ID=$_GET["ID"];
	$t0=$_GET["t"];
	$t=$_GET["tt"];
	$FORCE="aclid=$ID";

	$search='%';
	$table="wpad_destination";

	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No datas");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE $FORCE $searchstring $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["zmd5"];
		$delete=imgsimple("delete-32.png",null,"DeleteObjectLinks$t('$mkey')");
		
		$up=imgsimple("arrow-up-32.png",null,"MoveObjectLinks$t('$mkey','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveObjectLinks$t('$mkey','down')");
		
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<span style='font-size:16px;font-weight:bold'><a href=\"javascript:blur();\" 
							OnClick=\"javascript:MoveObjectLinksAsk$t('$mkey','{$ligne["zorder"]}')\"
							style='font-size:16px;font-weight:bold;text-decoration:underline'
							>[{$ligne["zorder"]}]</a></span>",
						"<span style='font-size:16px;font-weight:bold'>{$ligne["proxyserver"]}</span>",
						"<span style='font-size:16px;font-weight:bold'>{$ligne["proxyport"]}</span>",
						"<span style='font-size:16px;font-weight:bold'>$up</span>",
						"<span style='font-size:16px;font-weight:bold'>$down</span>",
						
						
						$delete)
		);
	}
	echo json_encode($data);
}

function rebuild_tables(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `wpad_rules`");
	$q->QUERY_SQL("DROP TABLE `wpad_sources_link`");
	$q->QUERY_SQL("DROP TABLE `wpad_white_link`");
	$q->QUERY_SQL("DROP TABLE `wpad_destination`");
	$q->CheckTables(null,true);
	
}

function debug_download(){
	
	$sock=new sockets();
	$path="/usr/share/artica-postfix/ressources/logs/web/proxy.pack.debug.gz";
	$sock->getFrameWork("squid.php?proxy-pac-debug-compress=yes");
	$file=basename($path);
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($path)));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	
	
	
}
