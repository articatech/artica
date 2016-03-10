<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["default-js"])){default_js();exit;}
	if(isset($_GET["default-rule"])){default_popup();exit;}
	if(isset($_POST["default-rule-save"])){default_save();exit;}
	
	if(isset($_POST["item-enable"])){items_enable();exit;}
	if(isset($_POST["item-move"])){items_move();exit;}
	if(isset($_POST["item"])){items_save();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["item-js"])){items_js();exit;}
	if(isset($_GET["items-search"])){items_search();exit;}
	if(isset($_GET["item-popup"])){items_popup();exit;}
	if(isset($_POST["item-delete"])){items_delete();exit;}
	
	if(isset($_POST["main-subrule-delete"])){main_subrule_delete();exit;}
	if(isset($_POST["main-subrule-move"])){main_subrule_move();exit;}	
	
	if(isset($_POST["main-rule-delete"])){main_rule_delete();exit;}
	if(isset($_POST["main-rule-move"])){main_rule_move();exit;}
	if(isset($_POST["main-rule-enable"])){main_rule_enable();exit;}
	
	if(isset($_GET["main-section"])){main_rule();exit;}
	if(isset($_GET["main-search"])){main_search();exit;}
	if(isset($_GET["main"])){main_rule();exit();}
	if(isset($_GET["main-js"])){main_js();exit;}
	if(isset($_GET["main-tabs"])){main_tabs();exit;}
	if(isset($_GET["main-rules-tabs"])){main_section_tabs();exit;}
	
	
	if(isset($_GET["options"])){options_section();exit;}
	if(isset($_GET["options-search"])){options_search();exit;}
	if(isset($_POST["options-enable"])){options_enable();exit;}
	
	
	if(isset($_GET["main-popup"])){main_popup();exit;}
	if(isset($_POST["main-ID"])){main_save();exit;}
	if(isset($_GET["parameters"])){global_parameters();exit;}
	if(isset($_POST["DisableAnyCache"])){global_parameters_save();exit;}
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["main-subrules"])){rules();exit;}
	if(isset($_GET["rules-search"])){rules_search();exit;}
	if(isset($_GET["rule-js"])){rules_js();exit;}
	if(isset($_GET["subrules-tabs"])){subrules_tabs();exit;}
	if(isset($_POST["rule-enable"])){subrules_enable();exit;}
	if(isset($_POST["subrule-id"])){subrules_save();exit;}
	if(isset($_GET["rules-tabs"])){rules_tabs();exit;}
	if(isset($_GET["rules-popup"])){rules_popup();exit;}
	
	
	if(isset($_POST["rule-delete"])){members_delete();exit;}
	
	
	if(isset($_POST["serviceport"])){accounts_save();exit;}
	if(isset($_POST["username"])){members_save();exit;}
	if(isset($_GET["accounts-search"])){accounts_search();exit;}
	if(isset($_GET["members-accounts"])){accounts();exit;}
	if(isset($_GET["account-js"])){accounts_js();exit;}
	if(isset($_GET["accounts-js"])){accounts_js();exit;}
	if(isset($_GET["accounts-popup"])){accounts_popup();exit;}
	if(isset($_POST["accounts-delete"])){accounts_delete();exit;}
	
js();
// main_cache_rules

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$sock->SET_INFO("SquidAsSeenCache",1);
	header("content-type: application/x-javascript");
	$APP_RDPPROXY=$tpl->javascript_parse_text("{cache_rules}");
	echo "
	$('#main_cache_rules_main_tabs').remove();
	YahooWin('1030','$page?main-tabs=yes&addtbanme=$t','$APP_RDPPROXY',true);";
}
// ---------------------------------------------------------------------------------------------------------------------
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["rules"]='{parameters}';
	
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_cachesrules_tabs");

}
// ---------------------------------------------------------------------------------------------------------------------
function rules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$mainid=$_GET["mainid"];
	header("content-type: application/x-javascript");
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='$mainid'"));
	$mainname=utf8_encode($ligne["rulename"])."&nbsp;&raquo;&nbsp;";
	
	
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("$mainname{new_rule}");
	}else{
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM cache_rules WHERE ID='$ID'"));
		$TITLE=$mainname.utf8_encode($ligne["rulename"]);
	}
	
	echo "YahooWin3('850','$page?subrules-tabs=yes&ID=$ID&t=$t&tt=$tt&mainid={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}','$TITLE',true);";	
	
}
// ---------------------------------------------------------------------------------------------------------------------
function items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=$_GET["ttt"];
	$mainid2=$_GET["mainid2"];
	$mainid=$_GET["mainid"];
	header("content-type: application/x-javascript");
	$subtitle=null;
	if($ID==0){
		$subtitle=$tpl->javascript_parse_text("{new_item}");
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='$mainid2'"));
	$mainname=utf8_encode($ligne["rulename"])."&nbsp;&raquo;&nbsp;";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename,GroupType FROM cache_rules WHERE ID='$mainid'"));
	$mainname=$mainname.utf8_encode($ligne["rulename"]);
	$GroupType=$tpl->javascript_parse_text($q->CACHES_RULES_TYPES[$ligne["GroupType"]]);
	$title="$mainname&nbsp;&raquo;&nbsp;&raquo;&nbsp;$GroupType&nbsp;$subtitle";
	echo "YahooWin4('650','$page?item-popup=yes&ID=$ID&t=$t&tt=$tt&ttt=$ttt&mainid={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}','$title',true);";

}
// ---------------------------------------------------------------------------------------------------------------------

function default_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$TITLE=$tpl->javascript_parse_text("{default_rule2}");
	echo "YahooWin2('900','$page?default-rule=yes&t=$t&tt={$_GET["tt"]}&SourceT={$_GET["SourceT"]}','$TITLE',true);";
}


function main_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_rules WHERE ID='$ID'"));
		$TITLE=utf8_encode($ligne["rulename"]);
	}
	echo "YahooWin2('900','$page?main-rules-tabs=yes&ID={$_GET["ID"]}&t=$t&tt={$_GET["tt"]}&SourceT={$_GET["SourceT"]}','$TITLE',true);";	
}
// ---------------------------------------------------------------------------------------------------------------------
function global_parameters(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$CacheReplacementPolicy=$sock->GET_INFO("CacheReplacementPolicy");
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
	$SquidDebugCacheProc=$sock->GET_INFO("SquidDebugCacheProc");
	$ForceWindowsUpdateCaching=$sock->GET_INFO("ForceWindowsUpdateCaching");
	$ProxyDedicateMicrosoftRules=$sock->GET_INFO("ProxyDedicateMicrosoftRules");
	$DisableSquidSMP=intval($sock->GET_INFO("DisableSquidSMP"));
	if($DisableSquidSMP==0){$EnableSQUIDSMP=1;}else{$EnableSQUIDSMP=0;}
	$SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
	if(!is_numeric($SquidSimpleConfig)){$SquidSimpleConfig=1;}
	
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	
	$SquidReloadIntoIMS=$sock->GET_INFO("SquidReloadIntoIMS");
	
	
	$license_error=null;
	
	
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=4320;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_min=43200;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=40;}
	
	
	if(!is_numeric($SquidDebugCacheProc)){$SquidDebugCacheProc=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($ForceWindowsUpdateCaching)){$ForceWindowsUpdateCaching=0;}
	if(!is_numeric($ProxyDedicateMicrosoftRules)){$ProxyDedicateMicrosoftRules=0;}
	if(!is_numeric($SquidReloadIntoIMS)){$SquidReloadIntoIMS=1;}
	
	
	
	
	
	
	
	
	$squid=new squidbee();
	$t=time();
	$array["lru"]="{cache_lru}";
	$array["heap_GDSF"]="{heap_GDSF}";
	$array["heap_LFUDA"]="{heap_LFUDA}";
	$array["heap_LRU"]="{heap_LRU}";
	$read_ahead_gap=$squid->global_conf_array["read_ahead_gap"];
	$cache_mem=$squid->global_conf_array["cache_mem"];
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	
	$minimum_object_size=$squid->global_conf_array["minimum_object_size"];
	if(preg_match("#([0-9]+)\s+#", $read_ahead_gap,$re)){$read_ahead_gap=$re[1];}
	
	if(preg_match("#([0-9]+)\s+#", $minimum_object_size,$re)){$minimum_object_size=$re[1];}
	
	
	
	if(preg_match("#([0-9]+)#",$squid->global_conf_array["maximum_object_size"],$re)){
		$maximum_object_size=$re[1];
		if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$unit=$re[1];}
		if($unit=="KB"){$maximum_object_size_in_memory=round($maximum_object_size_in_memory/1024);}
	}
	
	
	$level=Paragraphe_switch_img('{DisableAnyCache} ({hard_drive})',"{DisableAnyCache_explain2}","DisableAnyCache-$t",
			$DisableAnyCache,null,850);
	
	if($SquidCacheLevel==0){
		$level=Paragraphe_switch_disable('{DisableAnyCache}',"{DisableAnyCache_explain2}","DisableAnyCache-$t",
		$DisableAnyCache,null,850);
	}
	
	$DisableSquidSMPF=Paragraphe_switch_img("{EnableSQUIDSMP}","{EnableSQUIDSMP_explain}",
			"EnableSQUIDSMP-$t",$EnableSQUIDSMP,null,850);
	
	$CORP_LICENSE=1;
	if(!$users->CORP_LICENSE){
		$CORP_LICENSE=0;
		$license_error="<hr><i style='font-size:18px;color:#d32d2d;font-weight:bold'>".$tpl->_ENGINE_parse_body("{license_error}")."</i><hr>";
		$DisableSquidSMPF=Paragraphe_switch_disable('{EnableSQUIDSMP}',"$license_error{EnableSQUIDSMP_explain}","EnableSQUIDSMP-$t",
		$EnableSQUIDSMP,null,850);
		
	}	

	

	
	$ForceWindowsUpdateCaching=Paragraphe_switch_img("{ForceWindowsUpdateCaching}", "{ForceWindowsUpdateCaching_explain}",
			"ForceWindowsUpdateCaching-$t",$ForceWindowsUpdateCaching,
			null,850);
	
	$ProxyDedicateMicrosoftRules=Paragraphe_switch_img("{ProxyDedicateMicrosoftRules}", "{ProxyDedicateMicrosoftRules_explain}",
			"ProxyDedicateMicrosoftRules-$t",$ProxyDedicateMicrosoftRules,
			null,850);
	$ForceWindowsUpdateCaching=null;
	$html="
	<div id='animate-$t'></div>
	<div style='margin:10px;padding:10px;width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=3 style='margin-bottom:15px;vertical-align:top'>$DisableSquidSMPF</td>
	</tr>	
	<tr>
	<td colspan=3 style='margin-bottom:15px;vertical-align:top'>$level</td>
	</tr>
	<tr>
	<td colspan=3 style='margin-bottom:15px;vertical-align:top'>$ProxyDedicateMicrosoftRules</td>
	</tr>
	<tr><td colspan=3 style='text-align:right'><hr>". button("{apply}","Save$t()",36)."</td><tr>
	<tr><td colspan=3 style='padding-bottom:50px;vertical-align:top'><p>&nbsp;</p></td></tr>
	<td colspan=3 style='margin-bottom:15px;vertical-align:top;font-size:42px'>{advanced_options}</td>
	</tr>	
			
	<tr>
		<td class=legend style='font-size:22px' width=99%>{central_memory}:</td>
		<td style='font-size:22px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:22px;width:110px")."&nbsp;MB</td>
		<td style='font-size:16px' width=1%>". help_icon("{cache_mem_text}")."</td>
	</tr>			
<tr><td colspan=3><hr></td></tr>			
	<tr>
	<td class=legend style='font-size:22px'>{cache_replacement_policy}:</td>
	<td>". Field_array_Hash($array, "CacheReplacementPolicy-$t",$CacheReplacementPolicy,null,null,0,"font-size:22px")."</td>
	<td width=1%>" . help_icon('{cache_replacement_policy_explain}',true)."</td>
	</tr>
			
	<tr>
		<td align='right' class=legend nowrap style='font-size:22px'>{cache_swap_low}:</strong></td>
		<td style='font-size:22px'>" . Field_text("cache_swap_low-$t",$squid->global_conf_array["cache_swap_low"],'width:110px;font-size:22px')."&nbsp;%</td>
		<td>" . help_icon('{cache_swap_low_text}',false,'squid.index.php')."</td>
	</tr>
	<tr>
		<td align='right' class=legend nowrap style='font-size:22px'>{cache_swap_high}:</strong></td>
		<td style='font-size:22px'>" . Field_text("cache_swap_high-$t",$squid->global_conf_array["cache_swap_high"],'width:110px;font-size:22px')."&nbsp;%</td>
		<td>" . help_icon('{cache_swap_high_text}',false,'squid.index.php')."</td>
	</tr>			
			
	<tr><td colspan=3><hr></td></tr>
				
			
		
	<tr>
		<td class=legend style='font-size:22px'>{read_ahead_gap}:</td>
		<td style='font-size:18px'>". Field_text("read_ahead_gap-$t",$read_ahead_gap,"font-size:22px;width:110px")."&nbsp;MB</td>
		<td style='font-size:18px' width=1%>". help_icon("{read_ahead_gap_text}")."</td>
	</tr>						
	<tr>
	<td style='font-size:22px' class=legend>{maximum_object_size}:</td>
	<td align='left' style='font-size:22px'>" . Field_text("maximum_object_size-$t",$maximum_object_size,'width:110px;font-size:22px')."&nbsp;MB</td>
	<td width=1%>" . help_icon('{maximum_object_size_text}',true)."</td>
	</tr>
			
<tr>
		<td align='right' class=legend nowrap style='font-size:22px'>{minimum_object_size}:</strong></td>
		<td align='left' style='font-size:18px'>" . Field_text("minimum_object_size-$t",$minimum_object_size,'width:110px;font-size:22px')."&nbsp;KB</td>
		<td>" . help_icon('{minimum_object_size_text}',false,'squid.index.php')."</td>
</tr>
<tr>			

	<tr>
	<td style='font-size:22px' class=legend>{debug_cache_processing}:</td>
	<td align='left' style='font-size:22px'>" . Field_checkbox_design("SquidDebugCacheProc-$t",1,$SquidDebugCacheProc)."</td>
	<td width=1%></td>
	</tr>	
	<tr><td colspan=3 style='text-align:right'><hr>". button("{apply}","Save$t()",36)."</td>
	</tr>
	
	
	
	</table>
	</div>
		
	<script>
	
	var xSSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			Loadjs('squid.compile.progress.php');
		}		

		function Save$t(){
			var SquidDebugCacheProc=0;
			var CORP_LICENSE=$CORP_LICENSE;
			var XHR = new XHRConnection();
			XHR.appendData('DisableAnyCache',document.getElementById('DisableAnyCache-$t').value);
			
			XHR.appendData('ProxyDedicateMicrosoftRules',document.getElementById('ProxyDedicateMicrosoftRules-$t').value);
			
			if(document.getElementById('SquidDebugCacheProc-$t').checked){SquidDebugCacheProc=1;}

			
			if(CORP_LICENSE==1){
				XHR.appendData('EnableSQUIDSMP',document.getElementById('EnableSQUIDSMP-$t').value);
			}else{
				XHR.appendData('EnableSQUIDSMP',0);
			}
			
			
			XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
			XHR.appendData('minimum_object_size',document.getElementById('minimum_object_size-$t').value);
			XHR.appendData('cache_swap_high',document.getElementById('cache_swap_high-$t').value);
			XHR.appendData('cache_swap_low',document.getElementById('cache_swap_low-$t').value);
			
			XHR.appendData('read_ahead_gap',document.getElementById('read_ahead_gap-$t').value);
			XHR.appendData('CacheReplacementPolicy',document.getElementById('CacheReplacementPolicy-$t').value);
			XHR.appendData('maximum_object_size',document.getElementById('maximum_object_size-$t').value);
			
			XHR.appendData('SquidDebugCacheProc',SquidDebugCacheProc);
			XHR.sendAndLoad('$page', 'POST',xSSave$t);
		}
		
		function CheckDisableAnyCache$t(){
			var DisableAnyCache=$DisableAnyCache;
			var SquidSimpleConfig=$SquidSimpleConfig;
			
			document.getElementById('SquidDebugCacheProc-$t').disabled=true;
			document.getElementById('CacheReplacementPolicy-$t').disabled=true;
			document.getElementById('maximum_object_size-$t').disabled=true;
			
			document.getElementById('read_ahead_gap-$t').disabled=true;
			document.getElementById('CacheReplacementPolicy-$t').disabled=true;
			
			if(SquidSimpleConfig==0){
				document.getElementById('read_ahead_gap-$t').disabled=false;
				document.getElementById('CacheReplacementPolicy-$t').disabled=false;		
			
			}
			
			if(DisableAnyCache==0){
				document.getElementById('SquidDebugCacheProc-$t').disabled=false;
				if(SquidSimpleConfig==0){document.getElementById('CacheReplacementPolicy-$t').disabled=false;}
				document.getElementById('maximum_object_size-$t').disabled=false;			
			}
			
			
		}
		
		CheckDisableAnyCache$t();	
	
	</script>";
	
			echo $tpl->_ENGINE_parse_body($html);
}
// ---------------------------------------------------------------------------------------------------------------------

function global_parameters_save(){
	$sock=new sockets();
	$users=new usersMenus();
	
	
	
	$sock->SET_INFO("CacheReplacementPolicy", $_POST["CacheReplacementPolicy"]);
	$sock->SET_INFO("SquidDebugCacheProc", $_POST["SquidDebugCacheProc"]);
	$sock->SET_INFO("DisableAnyCache", $_POST["DisableAnyCache"]);
	if(isset($_POST["ForceWindowsUpdateCaching"])){$sock->SET_INFO("ForceWindowsUpdateCaching", $_POST["ForceWindowsUpdateCaching"]);}
	$sock->SET_INFO("ProxyDedicateMicrosoftRules", $_POST["ProxyDedicateMicrosoftRules"]);
	
	
	if(isset($_POST["EnableSQUIDSMP"])){
		if($_POST["EnableSQUIDSMP"]==1){
			$sock->SET_INFO("DisableSquidSMP", 0);
		}else{
			$sock->SET_INFO("DisableSquidSMP", 1);
		}
	}
	
	
	
	$squid=new squidbee();
	
	if(is_numeric($_POST["cache_mem"])){
		$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	}
	
	
	if(is_numeric($_POST["read_ahead_gap"])){
		$squid->global_conf_array["read_ahead_gap"]=trim($_POST["read_ahead_gap"])." MB";
	}
	
	if(is_numeric($_POST["cache_swap_low"])){
		$squid->global_conf_array["cache_swap_low"]=trim($_POST["cache_swap_low"]);
	}	
	if(is_numeric($_POST["cache_swap_high"])){
		$squid->global_conf_array["cache_swap_high"]=trim($_POST["cache_swap_high"]);
	}	
	if(is_numeric($_POST["minimum_object_size"])){
		$squid->global_conf_array["minimum_object_size"]=trim($_POST["minimum_object_size"]) ." KB";
	}	
	
	
	$squid->global_conf_array["maximum_object_size"]=$_POST["maximum_object_size"]." MB";
	
	$squid->SaveToLdap(true);
	
}
// ---------------------------------------------------------------------------------------------------------------------
function rules_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==0){
		$array["rule-popup"]='{new_rule}';
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_rules WHERE ID='$ID'"));
		$array["main-popup"]=utf8_encode($ligne["rulename"]);
		$array["subrules"]='{subrules}';
		
		
	}
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID&tt={$_GET["tt"]}&SourceT={$_GET["SourceT"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_cache_rules_tabs");	
	
}
// ---------------------------------------------------------------------------------------------------------------------
function subrules_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$mainid=$_GET["mainid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='$mainid'"));
	$mainname=utf8_encode($ligne["rulename"]);
	
	$t=$_GET["t"];
	if($ID==0){
		$array["rules-popup"]="$mainname&nbsp;&raquo;&nbsp;{new_rule}";
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM cache_rules WHERE ID='$ID'"));
		$mainnameR=utf8_encode($ligne["rulename"]);
		$array["rules-popup"]="$mainname&nbsp;&raquo;&nbsp;$mainnameR";
		$array["items"]="$mainnameR&nbsp;&raquo;&nbsp;{items}";
	}
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&SourceT={$_GET["SourceT"]}&ID=$ID&tt={$_GET["tt"]}&mainid={$_GET["mainid"]}\" 
		style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_subrules_tabs");
}


function main_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	$UseSimplifiedCachePattern=$sock->GET_INFO("UseSimplifiedCachePattern");
	if(!is_numeric($UseSimplifiedCachePattern)){$UseSimplifiedCachePattern=1;}
	
	$realsquidversion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	
	$array["caches-level"]='{cache_level}';
	$array["caches-status"]='{caches_status}';
	
	if($DisableAnyCache==0){
		if(preg_match("#^3\.(4|5|6|7|8)\.#", $realsquidversion)){
			$array["rock"]='{rock_store}';
				
		}
	
	}
	
	$array["caches-center"]='{caches_center}';
	
	
	$array["artica-cache"]="{enforce_rules}";
	
	
	
	
	
	
	if($UseSimplifiedCachePattern==0){
		if($SquidCacheLevel>2){
			//$array["dyn-section"]="{dynamic_enforce_rules}";
		}
		
	}
	
	if($SquidCacheLevel>1){
		//$array["main-section"]="{cache_rules}";
	}
	

	
	$array["parameters"]="{global_parameters}";
	


	

	if($DisableAnyCache==0){
		$q=new mysql_blackbox();
		if($q->TABLE_EXISTS("cacheitems_localhost")){
			$ct=$q->COUNT_ROWS("cacheitems_localhost");
			if($ct>0){
				$array["cached_items"]="$ct {cached_items}";
	
			}
		}
	}

	if(count($array)>=4){
		$fontsize=18;
	}
	
	if(count($array)>=8){
		$fontsize=14;
	}
	while (list ($num, $ligne) = each ($array) ){
		
		
		if($num=="artica-cache"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.php\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="rock"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.rock.php\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="caches-level"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.level.php\">
					<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="caches-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.status.php\">
					 <span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"squid.caches32.php?uuid=$squid->uuid\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		if($num=="caches-params"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"squid.caches.php?parameters=yes\">
						<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="caches-center"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.center.php\" ><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="cached_items"){
			$html[]= $tpl->_ENGINE_parse_body("<li ><a href=\"squid.cached.itemps.php?hostid=localhost\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="dyn-section"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.cache.dynamic.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;			
		}
						
		$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"$page?$num=yes&t=$t&ID=$ID&tt={$_GET["tt"]}&SourceT={$_GET["SourceT"]}\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_cache_rules_main_tabs")."<script>LeftDesign('caches-center-white-256-opac20.png');</script>";
	
}
// ---------------------------------------------------------------------------------------------------------------------
function main_section_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	if($ID==0){
		$array["main-popup"]='{new_rule}';
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_rules WHERE ID='$ID'"));
		$array["main-popup"]=utf8_encode($ligne["rulename"]);
		$array["main-subrules"]='{subrules}';
		$array["options"]='{options}';
	}
	
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&SourceT={$_GET["SourceT"]}&t=$t&ID=$ID&mainid=$ID&tt={$_GET["tt"]}\" 
		style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_section_tabs_$ID");	
	
	
}

// ---------------------------------------------------------------------------------------------------------------------

function main_rule_delete(){
	$ID=$_POST["main-rule-delete"];
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT * FROM cache_rules WHERE ruleid=$ID");
	while ($ligne = mysql_fetch_assoc($results)) {
		$RULE_ID=$ligne["ID"];
		$q->QUERY_SQL("DELETE FROM cache_rules_items WHERE ruleid='$ID'");
		$q->QUERY_SQL("DELETE FROM cache_rules WHERE ID='$ID'");
	}
	

	$q->QUERY_SQL("DELETE FROM main_cache_rules WHERE ID='$ID'");
	
	
}

function main_subrule_delete(){
	$q=new mysql_squid_builder();
	$ID=$_POST["main-subrule-delete"];
	$q->QUERY_SQL("DELETE FROM cache_rules_items WHERE ruleid='$ID'");
	$q->QUERY_SQL("DELETE FROM cache_rules WHERE ID='$ID'");
}

// ---------------------------------------------------------------------------------------------------------------------
function main_subrule_move(){
	$ID=$_POST["main-subrule-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM cache_rules WHERE ID='$ID'"));
	$LastOrder=$ligne["zorder"];
	
	
	$ruleid=$ligne["ruleid"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
	}
	
	
	
	if(isset($_POST["main-subrule-destination-zorder"])){
		if(is_numeric($_POST["main-subrule-destination-zorder"])){
			$NewOrder=$_POST["main-subrule-destination-zorder"];
		}
	}
	
	

	$q->QUERY_SQL("UPDATE cache_rules SET zorder='$NewOrder' WHERE ID='$ID'");
	

	$q->QUERY_SQL("UPDATE cache_rules SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND ID!='$ID' AND ruleid=$ruleid");
	if(!$q->ok){echo $q->mysql_error;}
	
	$sql="SELECT *  FROM cache_rules WHERE ruleid=$ruleid ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
	
		$q->QUERY_SQL("UPDATE cache_rules SET zorder='$c' WHERE ID='$ID'");
		$c++;
	
	}
	
}
// ---------------------------------------------------------------------------------------------------------------------
function items_enable(){
	$ID=$_POST["item-enable"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM cache_rules_items WHERE ID='$ID'"));
	
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE cache_rules_items SET enabled='$enabled' WHERE ID='$ID'");
}

// ---------------------------------------------------------------------------------------------------------------------
function items_move(){
	$ID=$_POST["item-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM cache_rules_items WHERE ID='$ID'"));
	$LastOrder=$ligne["zorder"];
	
	
	$ruleid=$ligne["ruleid"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
	}
	
	
	
	if(isset($_POST["item-destination-zorder"])){
		if(is_numeric($_POST["item-destination-zorder"])){
			$NewOrder=$_POST["item-destination-zorder"];
		}
	}
	
	

	$q->QUERY_SQL("UPDATE cache_rules_items SET zorder='$NewOrder' WHERE ID='$ID'");
	

	$q->QUERY_SQL("UPDATE cache_rules_items SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND ID!='$ID' AND ruleid=$ruleid");
	if(!$q->ok){echo $q->mysql_error;}
	
	$sql="SELECT *  FROM cache_rules_items WHERE ruleid=$ruleid ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
	
		$q->QUERY_SQL("UPDATE cache_rules_items SET zorder='$c' WHERE ID='$ID'");
		$c++;
	
	}
	
}
// ---------------------------------------------------------------------------------------------------------------------

function main_rule_enable(){
	$ID=$_POST["main-rule-enable"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM main_cache_rules WHERE ID='$ID'"));
	
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE main_cache_rules SET enabled='$enabled' WHERE ID='$ID'");	
}

function options_enable(){
	$ID=$_POST["options-enable"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM cache_rules_options WHERE ID='$ID'"));
	
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE cache_rules_options SET enabled='$enabled' WHERE ID='$ID'");	
}

function main_rule_move(){
	$ID=$_POST["main-rule-move"];
	$direction=$_POST["direction"];
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_rules WHERE ID='$ID'"));
	$LastOrder=$ligne["zorder"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
	}
	
	
	
	if(isset($_POST["main-rule-destination-zorder"])){
		if(is_numeric($_POST["main-rule-destination-zorder"])){
			$NewOrder=$_POST["main-rule-destination-zorder"];
		}
	}
	
	$q->QUERY_SQL("UPDATE main_cache_rules SET zorder='$NewOrder' WHERE ID='$ID'");
	$q->QUERY_SQL("UPDATE main_cache_rules SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND ID <>'$ID'");
	
	$sql="SELECT *  FROM main_cache_rules ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("UPDATE main_cache_rules SET zorder='$c' WHERE ID='$ID'");
		$c++;
	
	}	
	
}


function main_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$btname="{add}";
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_rules WHERE ID='$ID'"));
	
	}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["zorder"])){$ligne["zorder"]=1;}
	
	$html="<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
	<td class=legend style='font-size:22px'>{rulename}:</td>
		<td>". Field_text("rulename-$tt",$ligne["rulename"],"font-size:22px;width:99%",null,null,null,false,"SaveCheck$tt(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$tt",1,$ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{order}:</td>
		<td>". Field_text("zorder-$tt",$ligne["zorder"],"font-size:22px;width:90px",null,null,null,false,"SaveCheck$tt(event)")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("$btname", "Save$tt()",36)."</td>
	</tr>
	</table>
</div>
<script>
var xSave$tt= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	$('#flexRT$t').flexReload();
	if(ID==0){ YahooWin2Hide();}
	
}
function Save$tt(){
	var XHR = new XHRConnection();
	enabled=0;
	if(document.getElementById('enabled-$tt').checked){enabled=1;}
	XHR.appendData('main-ID','$ID');
	XHR.appendData('zorder',document.getElementById('zorder-$tt').value);
	XHR.appendData('enabled',enabled);
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$tt').value));
	AnimateDiv('test-$t');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);

}

function SaveCheck$tt(e){
	if(!checkEnter(e)){return;}
	Save$tt();
}

</script>
";	
echo $tpl->_ENGINE_parse_body($html);	
}
function main_save(){
	$ID=$_POST["main-ID"];
	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
	}
		
	if($ID>0){
		$sql="UPDATE main_cache_rules SET
		`rulename` ='{$_POST["rulename"]}',
		`enabled` ='{$_POST["enabled"]}',
		`zorder` ='{$_POST["zorder"]}' WHERE ID={$_POST["main-ID"]}";
	
	}else{
		
		$sql="INSERT INTO main_cache_rules (`rulename`,	`enabled`,`zorder`)
		VALUES ('{$_POST["rulename"]}','{$_POST["enabled"]}','{$_POST["zorder"]}')";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
	
}

function subrules_enable(){
	$ID=$_POST["rule-enable"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM cache_rules WHERE ID='$ID'"));
	
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE cache_rules SET enabled='$enabled' WHERE ID='$ID'");	
	
}

function subrules_save(){
	$ID=$_POST["subrule-id"];
	$ruleid=$_POST["ruleid"];
	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
	}	
	
	if($ruleid==0){echo "No main rule !\n";return;}

	if($ID>0){
		$sql="UPDATE cache_rules SET
		`rulename` ='{$_POST["rulename"]}',
		`min` ='{$_POST["min"]}',
		`max` ='{$_POST["max"]}',
		`perc` ='{$_POST["perc"]}'
		WHERE ID=$ID";
	
	}else{
	
	$sql="INSERT INTO cache_rules (`rulename`,	`enabled`,`zorder`,`GroupType`,`min`,`max`,`perc`,`ruleid`)
	VALUES ('{$_POST["rulename"]}','1','1','{$_POST["GroupType"]}','{$_POST["min"]}','{$_POST["max"]}','{$_POST["perc"]}','$ruleid')";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}	
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$net=new networking();
	if(!$users->RDPPROXY_INSTALLED){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{NOT_INSTALLED}")."</p>";
	}
	$t=time();

	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	$RDPProxyListen=$sock->GET_INFO("RDPProxyListen");
	$RDPProxyPort=$sock->GET_INFO("RDPProxyPort");
	$RDPDisableGroups=$sock->GET_INFO("RDPDisableGroups");
	if($RDPProxyListen==null){$RDPProxyListen="0.0.0.0";}
	
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if(!is_numeric($RDPProxyPort)){$RDPProxyPort=3389;}
	if(!is_numeric($RDPDisableGroups)){$RDPDisableGroups=1;}
	$ips=$net->ALL_IPS_GET_ARRAY();
	unset($ips["127.0.0.1"]);
	$ips["0.0.0.0"]="{all}";

	$html="<div class='explain' style='font-size:16px'>
	{APP_RDPPROXY_EXPLAIN}
	</div>
	<div id='test-$t'></div>
	<div style='width:98%' class=form>
		<table>
			<tr>
				<td class='legend' style='font-size:16px !important;vertical-align:top'>{activate_RDP_service}:</td>
				<td>". Field_checkbox("EnableRDPProxy", 1,$EnableRDPProxy)."</td>
				<td width=1%>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px !important;vertical-align:top''>{listen_ip}:</td>
				<td>". Field_array_Hash($ips, "RDPProxyListen",$RDPProxyListen,null,null,0,"font-size:16px")."</td>
				<td width=1%></td>
			</tr>		
			<tr>
				<td class=legend style='font-size:16px !important;vertical-align:top''>{listen_port}:</td>
				<td>". Field_text("RDPProxyPort", $RDPProxyPort,"font-size:16px;width:90px")."</td>
				<td width=1%></td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px !important;vertical-align:top''>{disable_groups}:</td>
				<td>". Field_checkbox("RDPDisableGroups", 1,$RDPDisableGroups)."</td>
				<td width=1%></td>
			</tr>						

			<tr>
				<td colspan=3  align='right'><hr>". button("{apply}", "Save$t()","22px")."</td>
			</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	document.getElementById('test-$t').innerHTML='';
	RefreshTab('squid_main_svc');
	RefreshTab('main_rdpproxy_tabs');
}
function Save$t(){
	var XHR = new XHRConnection();
	EnableRDPProxy=0;
	RDPDisableGroups=0;
	if(document.getElementById('EnableRDPProxy').checked){EnableRDPProxy=1;}
	if(document.getElementById('RDPDisableGroups').checked){RDPDisableGroups=1;}
	XHR.appendData('EnableRDPProxy',EnableRDPProxy);
	XHR.appendData('RDPDisableGroups',RDPDisableGroups);
	XHR.appendData('RDPProxyPort',document.getElementById('RDPProxyPort').value);
	XHR.appendData('RDPProxyListen',document.getElementById('RDPProxyListen').value);
	AnimateDiv('test-$t');
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}

function Check$t(){
	document.getElementById('RDPDisableGroups').disabled=true;
}
Check$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function items(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$zorder=$tpl->_ENGINE_parse_body("{order}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$nastype=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$type=$tpl->javascript_parse_text("{type}");
	$add=$tpl->javascript_parse_text("{new_item}");
	$items=$tpl->javascript_parse_text("{items}");
	
	$tablewidht=883;
	
	if(!is_numeric($_GET["ID"])){echo "<p class=text-error>No ID</p>";return;}
	if(!is_numeric($_GET["mainid"])){echo "<p class=text-error>No mainID</p>";return;}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='{$_GET["mainid"]}'"));
	$mainname=utf8_encode($ligne["rulename"]);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename,GroupType FROM cache_rules WHERE ID='{$_GET["ID"]}'"));
	$mainnameR=utf8_encode($ligne["rulename"]);
	$GroupType=$tpl->javascript_parse_text($q->CACHES_RULES_TYPES[$ligne["GroupType"]]);
	$title="$mainname&nbsp;&raquo;&nbsp;$mainnameR&nbsp;&raquo;&nbsp;$items&nbsp;&raquo;&nbsp;$GroupType";
	
	
	$t=time();
	
	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : NewRule$t},
	],	";
	
	
	
	echo "<table class='$t' style='display: none' id='flexRT$t' style='width:99%;text-align:left'></table>
	<script>
	var MEMM$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?items-search=yes&t={$_GET["t"]}&tt={$_GET["tt"]}&ttt=$t&mainid={$_GET["ID"]}&SourceT={$_GET["SourceT"]}',
	dataType: 'json',
	colModel : [
	{display: '$zorder', name : 'zorder', width : 40, sortable : false, align: 'center'},
	{display: '$items', name : 'items', width : 537, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'enabled', width : 31, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$rules', name : 'rulename'},
	
	
	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	});
	});
	
	function RefreshTable$t(){
		$('#flexRT$t').flexReload();
		$('#flexRT{$_GET["t"]}').flexReload();
		$('#flexRT{$_GET["tt"]}').flexReload();
		$('#flexRT{$_GET["SourceT"]}').flexReload();
	
	}
	
	var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$t();
	}
	
	var x_ConnectionDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	RefreshTable$t();
	}
	
	function NewRule$t(){
	Loadjs('$page?item-js=yes&ID=0&t={$_GET["t"]}&tt={$_GET["t"]}&ttt=$t&mainid={$_GET["ID"]}&mainid2={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}');
	}
	
function MoveItem$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('item-move', mkey);
	XHR.appendData('ruleid', '{$_GET["ID"]}');
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
}
	
function MoveRuleDestinationAsk$t(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('item-move', mkey);
	XHR.appendData('ruleid', '{$_GET["ID"]}');
	XHR.appendData('item-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
	}
	
	function EnableDisable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('item-enable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
	}
	
	function ItemDelete$t(ID){
		MEMM$t=ID;
		if(confirm('$delete ?')){
			var XHR = new XHRConnection();
			XHR.appendData('item-delete',ID);
			XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
		}
	}
	</script>
	";	
}

function rules(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$zorder=$tpl->_ENGINE_parse_body("{order}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$nastype=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$type=$tpl->javascript_parse_text("{type}");
	$add=$tpl->javascript_parse_text("{new_rule}");
	$items=$tpl->javascript_parse_text("{items}");
	
	
	
	
	$tablewidht=883;
	
	if(!is_numeric($_GET["mainid"])){
		echo "<p class=text-error>No main ID</p>";
		return;
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='{$_GET["mainid"]}'"));
	$title=$ligne["rulename"];
	$t=time();

	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : NewSubRule$t},
	],	";



	echo "<table class='$t' style='display: none' id='flexRT$t' style='width:99%;text-align:left'></table>
<script>
var MEMM$t='';
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?rules-search=yes&t=$t&tt={$_GET["t"]}&mainid={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}',
	dataType: 'json',
	colModel : [
	{display: '$zorder', name : 'zorder', width : 40, sortable : false, align: 'center'},
	{display: '$rules', name : 'rulename', width : 365, sortable : false, align: 'left'},
	{display: '$type', name : 'GroupType', width : 113, sortable : true, align: 'left'},
	{display: '$items', name : 'items', width : 90, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'enabled', width : 31, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$rules', name : 'rulename'},


	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '$rules $title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	});
});

function RefreshTable$t(){
	$('#flexRT$t').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["SourceT"]}').flexReload();
	
}

var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$t();
}

var x_ConnectionDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	RefreshTable$t();
}

function NewSubRule$t(){
	Loadjs('$page?rule-js=yes&ID=0&t=$t&mainid={$_GET["ID"]}&mainid2={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}');
}

function MoveSubRuleLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('main-subrule-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);	
}

function MoveRuleDestinationAsk$t(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('main-subrule-move', mkey);
	XHR.appendData('main-subrule-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
}

function SubRuleEnable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

function RuleDelete$t(ID){
	MEMM$t=ID;
	if(confirm('$delete ?')){
		var XHR = new XHRConnection();
		XHR.appendData('main-subrule-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
	}
}
</script>
";
}
function rules_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="cache_rules";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=" ruleid='{$_GET["mainid"]}'";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	

	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	if(mysql_num_rows($results)==0){json_error_show("no rule",1);}
	
	$no_license="<span style='color:#d32d2d'>".$tpl->javascript_parse_text("{no_license}")."</span>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$md5=md5(serialize($ligne));
		$color="black";
		$delete=imgsimple("delete-24.png",null,"RuleDelete$t('{$ligne['ID']}')");
		$up=imgsimple("arrow-up-32.png",null,"MoveSubRuleLinks$t('{$ligne["ID"]}','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveSubRuleLinks$t('{$ligne["ID"]}','down')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"SubRuleEnable$t('{$ligne["ID"]}','$md5')");
		$lic=null;
		
		
		$type=$tpl->_ENGINE_parse_body($q->CACHES_RULES_TYPES[$ligne["GroupType"]]);
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ruleid) as tcount FROM cache_rules_items WHERE ruleid={$ligne["ID"]}"));
		if(!$q->ok){$items=$q->mysql_error;}else{
			$items=$ligne2["tcount"];
		}
		
		if(!$users->CORP_LICENSE){$color="#C5C5C5"; $lic=$no_license;}

		
		$uri="$MyPage?rule-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}&mainid={$ligne["ruleid"]}&tt=$tt&SourceT={$_GET["SourceT"]}";
		if($ligne["enabled"]==0){$color="#C5C5C5";}
		$href="<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('$uri');\"
						style=\"font-size:16px;text-decoration:underline;color:$color\">";
		
		
		$href_move="<a href=\"javascript:blur();\"
						OnClick=\"javascript:MoveRuleDestinationAsk$t({$ligne["ID"]},{$ligne['zorder']});\"
						style=\"font-size:16px;text-decoration:underline;color:$color\">";

		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(						
						"$href_move{$ligne['zorder']}</a>",
						"$href{$ligne['rulename']}$lic</a>",
						"$href$type</a>","$href$items</a>",
						$enable,$up,$down,$delete 
				)
		);
	
	}
	
	
	echo json_encode($data);
}

function items_popup(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$btname="{add}";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename,GroupType FROM cache_rules WHERE ID='{$_GET["mainid"]}'"));
	$mainname=utf8_encode($ligne["rulename"]);
	$GroupType=$tpl->javascript_parse_text($q->CACHES_RULES_TYPES[$ligne["GroupType"]]);
	
	$explain="{cache_rules_type_explain_{$ligne["GroupType"]}}";
	
	$title="$GroupType";
	$html="<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{rule}:</td>
		<td style='font-size:26px'>$mainname</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:26px'>{type}:</td>
		<td style='font-size:26px'>$GroupType [{$ligne["GroupType"]}]</td>
		<td width=1%>&nbsp;</td>
	</tr>
	
	<tr><td colspan=3><div class=explain style='font-size:26px'>$explain</div></td></tr>
	
	<td class=legend style='font-size:26px'>{item}:</td>
		<td>". Field_text("items-$t",$ligne["item"],"font-size:26px;width:99%",null,null,null,false,"SaveCheck$t(event)")."</td>
		<td width=1%>&nbsp;</td>
	</tr>		
	<tr>
		<td colspan=3 align=right><hr>".button("$btname","Save$t()",32)."</td>
	</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	$('#flexRT{$_GET["ttt"]}').flexReload();
	$('#flexRT{$_GET["SourceT"]}').flexReload();
	
	YahooWin4Hide();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ruleid', '{$_GET["mainid"]}');
	XHR.appendData('item', encodeURIComponent(document.getElementById('items-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
	if(checkEnter(e)){Save$t();}
	}
</script>
	";
	
		echo $tpl->_ENGINE_parse_body($html);	
}
function items_save(){

	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($ligne));
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM cache_rules WHERE ID='{$_POST["ruleid"]}'"));
	$GroupType=$ligne["GroupType"];
	if($GroupType==2){
		
		if(strpos($_POST["item"], ",")>0){
			
			$prefix="INSERT IGNORE INTO cache_rules_items (`ruleid`,`item`,`zorder`,`enabled`,`zMD5`) VALUES ";
			$tr=explode(",",$_POST["item"]);
			while (list ($num, $ligne) = each ($tr) ){
				if(substr($ligne,0,1)=="."){$ligne=substr($ligne, 1,strlen($ligne));}
				$zMD5=md5("{$_POST["ruleid"]}$ligne");
				$f[]="('{$_POST["ruleid"]}','$ligne','1','1','$zMD5')";
				
			}
			$sql=$prefix.@implode(",", $f);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;}
			return;
		}
		
		if(substr($_POST["item"],0,1)=="."){$_POST["item"]=substr($_POST["item"], 1,strlen($_POST["item"]));}
		
		
	}
	$zMD5=md5("{$_POST["ruleid"]}{$_POST["item"]}");
	$sql="INSERT IGNORE INTO cache_rules_items (`ruleid`,`item`,`zorder`,`enabled`,`zMD5`) VALUES ('{$_POST["ruleid"]}','{$_POST["item"]}','1','1','$zMD5')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function items_delete(){
	$ID=$_POST["item-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM cache_rules_items WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}

function default_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$btname="{apply}";
	$q=new mysql_squid_builder();
	$sock=new sockets();
	
	$default=$tpl->_ENGINE_parse_body("{default_rule2}");
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=4320;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_max=43200;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=40;}	
	
	
	for($i=0;$i<101;$i++){
		$precents[$i]="{$i}%";
	}
	
	
	$html="<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{rulename}:</td>
		<td  style='font-size:16px'>{default_rule2}</td>
		<td width=1%>&nbsp;</td>
	</tr>
		<tr>
			<td class=legend style='font-size:16px'>{type}:</td>
			<td  style='font-size:16px'>{all}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{minimal_time}:</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"min-$t",$refresh_pattern_def_min,"style:font-size:16px")."</td>
						<td width=1%>". help_icon("{caches_rules_min}")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{max_time}:</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"max-$t",$refresh_pattern_def_max,"style:font-size:16px")."</td>
			<td width=1%>". help_icon("{caches_rules_max}")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{percent}:</td>
			<td>". Field_array_Hash($precents,"perc-$t",$refresh_pattern_def_perc,"style:font-size:16px",null,null,null,false,"SaveCheck$t(event)")."</td>
			<td width=1%>". help_icon("{caches_rules_percent}")."</td>
		</tr>
			
		<tr>
			<td colspan=3 align=right><hr>".button("$btname","Save$t()",18)."</td>
		</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	YahooWin3Hide();
}
	
function Save$t(){
	var XHR = new XHRConnection();
		XHR.appendData('default-rule-save', 'yes');
		XHR.appendData('min', encodeURIComponent(document.getElementById('min-$t').value));
		XHR.appendData('max', encodeURIComponent(document.getElementById('max-$t').value));
		XHR.appendData('perc', encodeURIComponent(document.getElementById('perc-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
</script>
	";
	
		echo $tpl->_ENGINE_parse_body($html);	
}

function default_save(){
	$sock=new sockets();
	$sock->SET_INFO("min");
	$sock->SET_INFO("max");
	$sock->SET_INFO("perc");
}

function rules_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$btname="{add}";
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	
	if(!is_numeric($_GET["mainid"])){echo "<p class=text-error>No main ID</p>";return;}
	
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM cache_rules WHERE ID='$ID'"));
		
	}
	
	
	//http://www.packtpub.com/article/squid-proxy-server-fine-tuning-achieve-better-performance
	
/*
 * 
 * Cache rules can be used to achieve higher HIT ratios by keeping the recently expired objects 
 * fresh for a short period of time, or by overriding some of the HTTP headers sent by the web servers.[br]
 * The advantage of using a cache rule is that we can alter the lifetime of the cached objects, 
 * while with the cache directive we can only control whether a request should be cached or not.
 * 
 */	
	
	for($i=0;$i<101;$i++){
		$precents[$i]="{$i}%";
	}
	
	if(!is_numeric($ligne["min"])){$ligne["min"]=1440;}
	if(!is_numeric($ligne["max"])){$ligne["max"]=10080;}
	if(!is_numeric($ligne["perc"])){$ligne["perc"]=20;}

$html="<div id='anim-$t'></div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{rulename}:</td>
			<td>". Field_text("rulename-$t",$ligne["rulename"],"font-size:22px;width:99%",null,null,null,false,"SaveCheck$t(event)")."</td>
			<td width=1%>&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{type}:</td>
			<td>". Field_array_Hash($q->CACHES_RULES_TYPES,"type-$t",$ligne["GroupType"],"style:font-size:22px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{minimal_time}:</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"min-$t",$ligne["min"],"style:font-size:22px")."</td>
			<td width=1%>". help_icon("{caches_rules_min}")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{max_time}:</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"max-$t",$ligne["max"],"style:font-size:22px")."</td>
			<td width=1%>". help_icon("{caches_rules_max}")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{percent}:</td>
			<td>". Field_array_Hash($precents,"perc-$t",$ligne["perc"],"style:font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
			<td width=1%>". help_icon("{caches_rules_percent}")."</td>
		</tr>					
					
		<tr>
			<td colspan=3 align=right><hr>".button("$btname","Save$t()",32)."</td>
		</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var ID='$ID';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	if(ID==0){YahooWin3Hide();}
	if(ID>0){RefreshTab('main_subrules_tabs');}
	
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('subrule-id', '{$_GET["ID"]}');
	XHR.appendData('ruleid', '{$_GET["mainid"]}');	
	XHR.appendData('rulename', encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('GroupType', encodeURIComponent(document.getElementById('type-$t').value));
	XHR.appendData('min', encodeURIComponent(document.getElementById('min-$t').value));
	XHR.appendData('max', encodeURIComponent(document.getElementById('max-$t').value));
	XHR.appendData('perc', encodeURIComponent(document.getElementById('perc-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
	if(checkEnter(e)){Save$t();}
}

function Check$t(){
	var ID='$ID';
	if(ID==0){return;}
	document.getElementById('type-$t').disabled=true;
}
Check$t();
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}
function main_rule(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$service=$tpl->javascript_parse_text("{connection}");
	$add=$tpl->javascript_parse_text("{new_rule}");
	$servicetype=$tpl->_ENGINE_parse_body("{type}");
	
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->javascript_parse_text("{options}");
	$restart=$tpl->javascript_parse_text("{restart}");
	$cache_rules=$tpl->javascript_parse_text("{cache_rules}");
	
	$tablewidht=883;
	$t=time();
	$tt=time();
	
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		$q=new mysql_squid_builder();
		if($q->COUNT_ROWS("main_cache_rules")>0){
			$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
			$onlycorpdefaultrule=$tpl->_ENGINE_parse_body("{onlycorpdefaultrule}");
			$onlycorpavailable="<p class=text-error>$onlycorpavailable<br>$onlycorpdefaultrule</p>";
		}
		
	}
	
	

	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : NewMainRule$tt},{separator: true},
	{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
	{name: '$restart', bclass: 'Reload', onpress : SquidRestartNow$t},	
	],	";

	echo "$onlycorpavailable<table class='$tt' style='display: none' id='flexRT$tt' style='width:99%;text-align:left'></table>
	<script>
	var MEMM$tt='';
function start$tt(){
		$('#flexRT$tt').flexigrid({
		url: '$page?main-search=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'zorder', width : 55, sortable : false, align: 'center'},
		{display: '$rulename', name : 'rulename', width : 269, sortable : false, align: 'left'},
		{display: '$explain', name : 'username', width : 551, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enabled', width : 45, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width : 45, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 45, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'del', width : 45, sortable : false, align: 'center'},
		
		],
		$buttons
		searchitems : [
		{display: '$rulename', name : 'rulename'},
		],
		sortname: 'zorder',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:22px>$cache_rules</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true
	});
}

function RefreshTable$tt(){
	$('#flexRT$tt').flexReload();
}
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}
	
	function SquidRestartNow$t(){
		Loadjs('squid.restart.php?onlySquid=yes&ApplyConfToo=yes');
	}

var x_Refresh$tt=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$tt();
}

function NewMainRule$tt(){
	Loadjs('$page?main-js=yes&t=$t&ID=0&SourceT={$_GET["SourceT"]}');
}
function MoveObjectLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$tt);	
}

function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-move', mkey);
	XHR.appendData('main-rule-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$tt);
}

function RuleDelete$tt(ID){
	MEMM$tt=ID;
	if(confirm('$delete ?')){
		var XHR = new XHRConnection();
		XHR.appendData('main-rule-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_Refresh$tt);
	}
}
function MainRuleEnable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-enable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

setTimeout('start$tt()',800);
</script>
		";
}




function main_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="main_cache_rules";
	$page=1;
	$data = array();
	$data['rows'] = array();
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}


	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];

	}else{
		$total =$q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}



	$data['page'] = $page;
	$data['total'] = $total+1;
	$default=$tpl->_ENGINE_parse_body("{default_rule2}");
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=4320;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_max=43200;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=40;}
	
	$refresh_pattern_def_min=$q->CACHE_AGES[$refresh_pattern_def_min];
	$refresh_pattern_def_max_text=$q->CACHE_AGES[$refresh_pattern_def_max];
	
	$no_license="<div style='color:#d32d2d;font-size:18px;'>".$tpl->javascript_parse_text("* * {no_license} * *")."</div>";
	
	$href="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$MyPage?default-js=yes&t=$t&SourceT={$_GET["t"]}');\"
	style=\"font-size:18px;text-decoration:underline;color:black\">";
	
	
	$explain=$tpl->_ENGINE_parse_body("{if_no_rule_matches}<br>{cache_objects_during} $refresh_pattern_def_min
	 {for_a_maximal_time_of} $refresh_pattern_def_max_text <br>{with_a_ratio_of} {$refresh_pattern_def_perc}%<br>
	{for_requests_to} {all_websites}");
	

	$data['rows'][] = array(
			'id' => "none",
			'cell' => array(
					"<span style=\"font-size:18px;\">*</span>",
					"$href$default</a>",
					"<span style='font-size:16px'>$explain</span>",
					null,null,null,null
			)
	);


	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$md5=md5(serialize($ligne));
		$color="black";
		$lic=null;
		$delete=imgsimple("delete-24.png",null,"RuleDelete$t('{$ligne['ID']}')");
		$up=imgsimple("arrow-up-32.png",null,"MoveObjectLinks$t('{$ligne["ID"]}','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveObjectLinks$t('{$ligne["ID"]}','down')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"MainRuleEnable$t('{$ligne["ID"]}','$md5')");
		
		if($ligne["enabled"]==0){$color="#C5C5C5";}

		$href="<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('$MyPage?main-js=yes&ID={$ligne["ID"]}&t=$t&SourceT={$_GET["t"]}');\"
						style=\"font-size:18px;text-decoration:underline;color:$color\">";
		
		
		$href_move="<a href=\"javascript:blur();\"
						OnClick=\"javascript:MoveRuleDestinationAsk$t({$ligne["ID"]},{$ligne['zorder']});\"
						style=\"font-size:18px;text-decoration:underline;color:$color\">";
		
		$explain=explainThisRule($ligne["ID"],$tpl,$q);
		if(!$users->CORP_LICENSE){$lic=$no_license;}

		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(						
						"$href_move{$ligne['zorder']}</a>",
						"$href{$ligne['rulename']}</a>",
						"$lic<span style='font-size:16px'>$explain</span>",
						$enable,$up,$down,$delete 
				)
		);
	}
	echo json_encode($data);
}


function explainThisRule($ID,$tpl,$q){
	if($GLOBALS["VERBOSE"]){echo "<hr>\nexplainThisRule - $ID\n<hr>\n";}

	$sql="SELECT *  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 ORDER BY zorder";
	if($GLOBALS["VERBOSE"]){echo "<li style='font-size:16px'>$sql</li>\n";}
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return $q->mysql_error;}
	$ct=mysql_num_rows($results);
	
	if($GLOBALS["VERBOSE"]){echo "<li style='font-size:16px>Rules:$ct</li>\n";}
	if($ct==0){return $tpl->_ENGINE_parse_body("{no_rule}  [$ID] Line:".__LINE__);}
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$and=null;
	$EXTENSIONS=extensions_rules($ID,$tpl,$q);
	if(count($EXTENSIONS)>0){
		while (list ($a, $b) = each ($EXTENSIONS) ){$exts[]=$b;}
		$and="<br>{and} {files} ".@implode(",", $exts);
	}
	
	$MAIN=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		
		if($GLOBALS["VERBOSE"]){echo "<li style='font-size:16px'>{$ligne["ID"]}</li>\n";}
		
		if(isset($EXTENSIONS[$ligne["ID"]])){continue;}
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(item) as tcount FROM cache_rules_items WHERE enabled=1 AND ruleid={$ligne["ID"]}"));
		if(!$q->ok){$MAIN[]=$q->mysql_error;continue;}
		$items=$ligne2["tcount"];
		
		
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(item) as tcount FROM cache_rules_items 
				WHERE enabled=1 AND ruleid={$ligne["ID"]} AND `GroupType`=2"));
		
		
		if($items==0){$MAIN[]="$rule {$ligne["rulename"]} ".$tpl->_ENGINE_parse_body("{no_item}");continue;}
		
		$age=$q->CACHE_AGES[$ligne["min"]];
		$maxage=$q->CACHE_AGES[$ligne["max"]];
		$type=$q->CACHES_RULES_TYPES[$ligne["GroupType"]];
		
		$MAIN[]=$tpl->_ENGINE_parse_body("{cache_objects_during} $age<br>{for_a_maximal_time_of} $maxage<br>{with_a_ratio_of} {$ligne["perc"]}%<br>
		{for_requests_to} $type ($items {items})$and");

		
		
	}
	if(count($MAIN)==0){return $tpl->_ENGINE_parse_body("{no_rule} [$ID] Line:".__LINE__);}
	return @implode("<br>{or} ", $MAIN);
	
}

function extensions_rules($ID,$tpl,$q){
	
	$sql="SELECT *  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 AND `GroupType`=1  ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	$ct=mysql_num_rows($results);
	if($ct==0){return array();}
	
	$ARRAY=array();
	$sql="SELECT ID,rulename  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 AND `GroupType`=2 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ARRAY[$ligne["ID"]]=$ligne["rulename"];
	}
	return $ARRAY;
}

function items_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$ttt=$_GET["ttt"];
	$search='%';
	$table="cache_rules_items";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=" ruleid={$_GET["mainid"]}";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}
	
	$sql="SELECT *  FROM `$table` WHERE $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	if(mysql_num_rows($results)==0){json_error_show("no rule",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$md5=md5(serialize($ligne));
		$color="black";
		$delete=imgsimple("delete-24.png",null,"ItemDelete$ttt('{$ligne['ID']}')");
		$up=imgsimple("arrow-up-32.png",null,"MoveItem$ttt('{$ligne["ID"]}','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveItem$ttt('{$ligne["ID"]}','down')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"EnableDisable$ttt('{$ligne["ID"]}','$md5')");
	
		
		if($ligne["enabled"]==0){$color="#C5C5C5";}
	
	
		
		$uri="$MyPage?rule-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}&mainid={$ligne["ruleid"]}&tt=$tt&SourceT={$_GET["SourceT"]}";
	
		$href="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$uri');\"
		style=\"font-size:18px;text-decoration:underline;color:$color\">";
	
	
		$href_move="<a href=\"javascript:blur();\"
		OnClick=\"javascript:MoveItemAsk$t({$ligne["ID"]},{$ligne['zorder']});\"
		style=\"font-size:18px;text-decoration:underline;color:$color\">";
	
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"$href_move{$ligne['zorder']}</a>",
						"$href{$ligne['item']}</a>",
						$enable,$up,$down,$delete
				)
		);
	
	}
	
	
	echo json_encode($data);
	}

function options_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$option=$tpl->javascript_parse_text("{option}");
	$options=$tpl->javascript_parse_text("{options}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$service=$tpl->javascript_parse_text("{connection}");
	$add=$tpl->javascript_parse_text("{new_rule}");
	$servicetype=$tpl->_ENGINE_parse_body("{type}");
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM main_cache_rules WHERE ID='{$_GET["mainid"]}'"));
	$title=$ligne["rulename"];
	
	$tablewidht=883;
	$t=time();
	$tt=time();
	
	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : NewMainRule$tt},
	],	";
	$buttons=null;
	echo "<table class='$tt' style='display: none' id='flexRT$tt' style='width:99%;text-align:left'></table>
	<script>
	var MEMM$tt='';
	function start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?options-search=yes&t=$t&mainid={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$option', name : 'option', width : 192, sortable : false, align: 'left'},
	{display: '$explain', name : 'explain', width : 567, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'enabled', width : 31, sortable : true, align: 'center'},

	
	],
	$buttons
	searchitems : [
	{display: '$option', name : 'rulename'},
	],
	sortname: 'option',
	sortorder: 'asc',
	usepager: true,
	title: '$title&nbsp;&raquo;&nbsp;$options',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	});
	}
	
	function RefreshTable$tt(){
	$('#flexRT$tt').flexReload();
	}
	
	
	var x_Refresh$tt=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$tt();
	}
	
	function NewMainRule$tt(){
	Loadjs('$page?main-js=yes&t=$t&ID=0&SourceT={$_GET["SourceT"]}');
	}
	function MoveObjectLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-move', mkey);
			XHR.appendData('direction', direction);
			XHR.sendAndLoad('$page', 'POST',RefreshTable$tt);
	}
	
			function MoveRuleDestinationAsk$tt(mkey,def){
			var zorder=prompt('Order',def);
			if(!zorder){return;}
			var XHR = new XHRConnection();
			XHR.appendData('main-rule-move', mkey);
			XHR.appendData('main-rule-destination-zorder', zorder);
			XHR.sendAndLoad('$page', 'POST',RefreshTable$tt);
	}
	
			function RuleDelete$tt(ID){
					MEMM$tt=ID;
					if(confirm('$delete ?')){
					var XHR = new XHRConnection();
					XHR.appendData('main-rule-delete',ID);
					XHR.sendAndLoad('$page', 'POST',x_Refresh$tt);
	}
	}
function OptionEnable$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('options-enable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}
	
			setTimeout('start$tt()',800);
			</script>
			";
	}	
	//cache_rules_options options_section
	
function options_search(){
	
	$f["override-expire"]=true;
	$f["override-lastmod"]=true;
	$f["reload-into-ims"]=true;
	$f["ignore-reload"]=true;
	$f["ignore-no-store"]=true;
	$f["ignore-must-revalidate"]=true;
	$f["ignore-private"]=true;
	$f["ignore-auth"]=true;
	$f["refresh-ims"]=true;
	$f["store-stale"]=true;
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="cache_rules_options";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=" ruleid='{$_GET["mainid"]}'";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}
	$total = $ligne["TCOUNT"];
	if($total==0){
		$prefix="INSERT IGNORE INTO cache_rules_options (`ruleid`,`zMD5`,`option`,`enabled`) VALUES ";
		while (list ($a, $b) = each ($f) ){
			$zMD5=md5("$a{$_GET["mainid"]}");
			$QR[]="('{$_GET["mainid"]}','$zMD5','$a','0')";
		}
		
		$q->QUERY_SQL("$prefix".@implode(",", $QR));
		if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}
	}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	if(mysql_num_rows($results)==0){json_error_show("no rule",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$md5=md5(serialize($ligne));
		$color="black";
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"OptionEnable$t('{$ligne["ID"]}','$md5')");
	
	
		$explain=$tpl->_ENGINE_parse_body("{{$ligne['option']}}");
		
		$explain=wordwrap($explain,110,"<br>",true);
		
		if($ligne["enabled"]==0){$color="#C5C5C5";}
	
	
		
	
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:18px;color:$color'>{$ligne['option']}</span>",
						"<span style='font-size:13px;color:$color'>$explain</span>",$enable
						
				)
		);
	
	}
	
	
	echo json_encode($data);	
	
}
	
	
	
?>