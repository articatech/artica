<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.mimedefang.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}
	if(isset($_GET["compile-rules-js"])){compile_rules_js();exit;}
	if(isset($_GET["compile-rules-perform"])){	compile_rules_perform();exit;}
	if(isset($_POST["MimeDefangEnabled"])){enable_mimedefang();exit;}
	
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?tabs=yes&in-front-ajax=yes');";
}
	
function compile_rules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$mailman=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{compile_rules}");
	$html="YahooWinBrowse('750','$page?compile-rules-perform=yes','$mailman::$cmd');";
	echo $html;		
	
}

function compile_rules_perform(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("mimedefang.php?reload-tenir=yes"));
	echo "
	<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$datas</textarea>
<script>
	RefreshTab('main_config_mimedefang');
</script>
		
	";
	
}
	
function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("mimedefang.php?service-cmds={$_GET["service-cmds-peform"]}&MyCURLTIMEOUT=120")));
	
		$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
			$html=$html."
			<tr class=$classtr>
			<td width=99%><code style='font-size:12px'>$val</code></td>
			</tr>
			";
	
	
}

$html=$html."
</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_mimedefang');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}	
	
function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();


	$MimeDefangVersion=$sock->GET_INFO("MimeDefangVersion");
	$enable_amavisdeamon_ask=$tpl->javascript_parse_text("{enable_mimedefang_ask}");		
	$disable_amavisdeamon_ask=$tpl->javascript_parse_text("{disable_mimedefang_ask}");	
	$MimeDefangEnabled=trim($sock->GET_INFO("MimeDefangEnabled",true));	
	$MimeDefangArchiver=intval($sock->GET_INFO("MimeDefangArchiver",true));
	$MimeDefangClamav=intval($sock->GET_INFO("MimeDefangClamav"));
	$MimeDefangDisclaimer=intval($sock->GET_INFO("MimeDefangDisclaimer"));
	$MimeDefangSpamAssassin=intval($sock->GET_INFO("MimeDefangSpamAssassin"));
	$MimeDefangAutoWhiteList=intval($sock->GET_INFO("MimeDefangAutoWhiteList"));
	$MimeDefangFilterExtensions=intval($sock->GET_INFO("MimeDefangFilterExtensions"));
	$MimeDefangAutoCompress=intval($sock->GET_INFO("MimeDefangAutoCompress"));
	
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}
	
	$EnableMimeDefang=Paragraphe_switch_img("{enable_disable_this_service}", "{MIMEDEFANG_DEF}<br>{APP_VALVUAD_TEXT}","MimeDefangEnabled",$MimeDefangEnabled,null,990);
	$TOTAL_MEMORY_MB=$sock->getFrameWork("system.php?TOTAL_MEMORY_MB=yes");
	if($TOTAL_MEMORY_MB<1500){
		$p=FATAL_ERROR_SHOW_128("{NO_ENOUGH_MEMORY_FOR_THIS_SECTION}<br><strong style='font-size:18px'>{require}:1500MB</strong>",false,true);
		$MimeDefangEnabled=0;
		$EnableDaemonP=null;
		
		$EnableMimeDefang=Paragraphe_switch_disable("{enable_disable_this_service}", "{MIMEDEFANG_DEF}","MimeDefangEnabled",$MimeDefangEnabled,null,990);
	}

	if($MimeDefangEnabled==0){
		$backupemail_behavior=Paragraphe_switch_disable("{backupemail_behavior}", "{enable_APP_MAILARCHIVER_text}<br>{mimedefang_is_currently_disabled_text}","MimeDefangArchiver",$MimeDefangArchiver,null,990);
		$clamav_behavior=Paragraphe_switch_disable("{enable_antivirus}", "{ACTIVATE_ANTIVIRUS_SERVICE_TEXT}","MimeDefangClamav",$MimeDefangClamav,null,990);
		$disclaimer=Paragraphe_switch_disable("{enable_disclaimer}", "{disclaimer_text}","MimeDefangDisclaimer",$MimeDefangDisclaimer,null,990);
		$enableSpamassassin=Paragraphe_switch_disable("{enable_spamasssin}", "{enable_spamasssin_text}","MimeDefangSpamAssassin",$MimeDefangSpamAssassin,null,990);
		$AutoWhiteList=Paragraphe_switch_disable("{smtp_AutoWhiteList}", "{smtp_AutoWhiteList_text}","MimeDefangAutoWhiteList",$MimeDefangAutoWhiteList,null,990);
		$extensions=Paragraphe_switch_disable("{title_mime}", "{mimedefang_attachments_text}","MimeDefangFilterExtensions",$MimeDefangFilterExtensions,null,990);
		$autcompress=Paragraphe_switch_disable("{automated_compression}", "{auto-compress_text}","MimeDefangAutoCompress",$MimeDefangAutoCompress,null,990);
		
		
	}else{
		
		$clamav_behavior=Paragraphe_switch_img("{enable_antivirus}", "{ACTIVATE_ANTIVIRUS_SERVICE_TEXT}","MimeDefangClamav",$MimeDefangClamav,null,990);
		$enableSpamassassin=Paragraphe_switch_img("{enable_spamasssin}", "{enable_spamasssin_text}","MimeDefangSpamAssassin",$MimeDefangSpamAssassin,null,990);
		$backupemail_behavior=Paragraphe_switch_img("{backupemail_behavior}", "{enable_APP_MAILARCHIVER_text}","MimeDefangArchiver",$MimeDefangArchiver,null,990);
		$disclaimer=Paragraphe_switch_img("{enable_disclaimer}", "{disclaimer_text}<br>{disclaimer_explain}","MimeDefangDisclaimer",$MimeDefangDisclaimer,null,990);
		$AutoWhiteList=Paragraphe_switch_img("{smtp_AutoWhiteList}", "{smtp_AutoWhiteList_text}","MimeDefangAutoWhiteList",$MimeDefangAutoWhiteList,null,990);
		$extensions=Paragraphe_switch_img("{title_mime}", "{mimedefang_attachments_text}","MimeDefangFilterExtensions",$MimeDefangFilterExtensions,null,990);
		$autcompress=Paragraphe_switch_img("{automated_compression}", "{auto-compress_text}","MimeDefangAutoCompress",$MimeDefangAutoCompress,null,990);
	}
	
	
	
	
	$tr[]=$EnableDaemonP;
	//$tr[]=Paragraphe32("service_options", "service_options_text", "Loadjs('mimedefang.service.php')", "32-parameters.png",500);
	
	//$tr[]=Paragraphe32("online_help", "online_help", "s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');", "help_bg32.png",500);
	
	
	
	
	$table=CompileTr2($tr);
		
	
	
	$html="<table style='width:100%'>
	<tr>
		<td width=1% valign='top'>
			<div id='status-$t'></div>
		</td>
		<td valign='top' style='padding-left:15px'>
			<div style='font-size:40px;margin:bottom:40px;text-align:right'>{APP_MIMEDEFANG} v$MimeDefangVersion <span style='font-size:18px'>(". texttooltip("{reload_service}","{reload_service_text}","MimeDefangCompileRules()").")</span></div>
			$p
			<div style='width:98%' class=form>
			$EnableMimeDefang
			</div>
			<div style='width:98%' class=form>
			$enableSpamassassin
			$AutoWhiteList
			$clamav_behavior
			$extensions
			$backupemail_behavior
			$disclaimer
			$autcompress
			
			<div style='margin:20px;text-align:right'>". button("{apply}", "Save$t()",40)."</div>
			</div>
			
			<div id='explain-$t'>$table</div>
		</td>
	</tr>
	</table>
	<script>
	
	function MimeDefangCompileRules(){
		Loadjs('mimedefang.compile.php');
	}
	
	var x_Enablemimedefang= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		MimeDefangCompileRules();
	}		
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('MimeDefangEnabled',document.getElementById('MimeDefangEnabled').value);
		XHR.appendData('MimeDefangClamav',document.getElementById('MimeDefangClamav').value);
		XHR.appendData('MimeDefangArchiver',document.getElementById('MimeDefangArchiver').value);
		XHR.appendData('MimeDefangDisclaimer',document.getElementById('MimeDefangDisclaimer').value);
		XHR.appendData('MimeDefangSpamAssassin',document.getElementById('MimeDefangSpamAssassin').value);
		XHR.appendData('MimeDefangAutoWhiteList',document.getElementById('MimeDefangAutoWhiteList').value);
		XHR.appendData('MimeDefangFilterExtensions',document.getElementById('MimeDefangFilterExtensions').value);
		XHR.appendData('MimeDefangAutoCompress',document.getElementById('MimeDefangAutoCompress').value);
		
		XHR.sendAndLoad('$page', 'POST',x_Enablemimedefang);
	}
	

		LoadAjax('status-$t','$page?status=yes&t=$t');
		
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function status(){
	$t=$_GET["t"];
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('mimedefang.php?status=yes')));
	$APP_MIMEDEFANG=DAEMON_STATUS_ROUND("APP_MIMEDEFANG",$ini,null);
	$APP_MIMEDEFANGX=DAEMON_STATUS_ROUND("APP_MIMEDEFANGX",$ini,null);
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}	
	$tpl=new templates();
	
	
	if($Param["MX_TMPFS"]>5){
		$array=unserialize(base64_decode($sock->getFrameWork("mimedefang.php?getramtmpfs=yes")));
		if(!is_numeric($array["PURC"])){$array["PURC"]=0;}
		if(!isset($array["SIZE"])){$array["SIZE"]="0M";}
		$tmpfs[]="
		<tr>
			<td colspan=2 style='font-size:16px' align='left'>tmpfs:</td>
		</tr>
			<tr>
				<td valing='middle'>".pourcentage($array["PURC"])."</td>
				<td style='font-size:14px'>{$array["PURC"]}%/{$array["SIZE"]}</td>
			</tr>

			";
	}
	
	
	$q=new mysql_mimedefang_builder();
	$attachments_storage=$q->COUNT_ROWS("storage");
	
	if($attachments_storage>0){
		$sql="SELECT SUM(filesize) as tcount FROM `storage`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$size=FormatBytes($ligne["tcount"]/1024);
	
	$tmpfs[]="
	<tr>
		<td colspan=2>
		<hr>
	<table>
		<tr>
			<td style='font-size:16px' align='left'>{attachments_storage}:</td>
		</tr>
			<tr>
				<td style='font-size:16px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('mimedefang.filehosting.table.php');\"
				style='font-size:16px;text-decoration:underline'>$attachments_storage {items} ($size)</td>
			</tr>
		</table>
		</td>
	</tr>
	

			";	
		
		
	}
	
	if(count($tmpfs)>0){
		$tmpfs_builded="<table style='width:30%;margin-top:15px' class=form>".@implode("\n", $tmpfs)."</table>";
	}
	
	$html="<table style='width:99%' class=form>
	<tr>
	<td>$APP_MIMEDEFANG$APP_MIMEDEFANGX
	</td>
	</tr>
	</table>
	<center>
		$tmpfs_builded
	</center>
	<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('status-$t','$page?status=yes&t=$t');")."</div>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);		
		
	}
	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$q=new mysql();
	$q->BuildTables();
	
	
	$array["popup"]='{status}';
	$array["service_options"]='{service_options}';
	//$array["autocompress"]='{automated_compression}';
	//$array["filehosting"]='{mimedefang_filehosting}';
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.events.new.php?mimedefang-filter=yes&noform=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
	if($num=="disclaimers"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.disclaimers.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
	if($num=="autocompress"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.autocompress.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}

	if($num=="filehosting"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.filehosting.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="service_options"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.service.php\"><span style='font-size:26px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&section=$num&$ajaxpop\"><span style='font-size:26px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "main_config_mimedefang",1490);
		

	
}
function enable_mimedefang(){
	$sock=new sockets();
	
	while (list ($key, $val) = each ($_POST) ){
		$sock->SET_INFO("$key", $val);
		
	}
	
}

?>	