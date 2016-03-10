<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(isset($_GET["off"])){off();exit;}
if(isset($_GET["squidcklinks-host-infos"])){squidcklinks_host_infos();exit;}
if(function_exists($_GET["function"])){call_user_func($_GET["function"]);exit;}
if(isset($_GET["off"])){off();exit;}
$page=CurrentPageName();
$tpl=new templates();
$account=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("user-48.png", "myaccount",null, "QuickLinkSystems('section_myaccount')"));
$webfiltering=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-filtering-48.png", "WEB_FILTERING","softwares_mangement_text", "QuickLinkSystems('section_webfiltering_dansguardian')"));
$squidStats=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "QuickLinkSystems('section_statistics')"));
$tr[]=$account;
$tr[]=$webfiltering;
$tr[]=$squidStats;
$tr[]=$squid;
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-parameters.png", "proxy_main_settings","proxy_main_settings", "QuickLinkSystems('section_browser_config')"));
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-logs.png", "realtime_events_squid","realtime_events_squid", "QuickLinkSystems('section_realtime_events')"));



$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-site-48.png", "main_interface","main_interface_back_interface_text", "QuickLinksHideUsrProxy()"));
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("logoff-48.png", "logoff","logoff", "QuickLinksHideUsrLogoff()"));




while (list ($key, $line) = each ($tr) ){if($line==null){continue;}$tr2[]=$line;}



$count=1;


while (list ($key, $line) = each ($tr2) ){
	if($line==null){continue;}
	$f[]="<li id='kwick1'>$line</li>";
	$count++;
	
}

while (list ($key, $line) = each ($GLOBALS["QUICKLINKS-ITEMS"]) ){
	
	$jsitems[]="\tif(document.getElementById('$line')){document.getElementById('$line').className='QuickLinkTable';}";
}



	
	$html="
            <div id='QuickLinksTop' class=mainHeaderContent>
                <ul class='kwicks'>
					".@implode("\n", $f)."
                    
                </ul>
            </div>
	
	<div id='quicklinks-samba' style='width:900px'></div>
	<div id='BodyContent' style='width:900px'></div>
	
	
	<script>
		function LoadQuickTaskBar(){
			$(document).ready(function() {
				$('#QuickLinksTop .kwicks').kwicks({max: 205,spacing:  5});
			});
		}
		
		function QuickLinksSamba(){
			Set_Cookie('QuickLinkCacheIndex', 'quicklinks.fileshare.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.fileshare.php');
		}
		
		function QuickLinksProxy(){
			Set_Cookie('QuickLinkCacheIndex', 'quicklinks.proxy.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.proxy.php');		
		
		}
		
		function QuickLinkPostfix(){
			Loadjs('quicklinks.postfix.php?js=yes');		
		}
		
		function QuickLinkPostfixMulti(){
			Loadjs('quicklinks.postfix.multiple.php?js=yes');
		
		}
		
		function QuickLinksNetwork(){
			LoadAjax('BodyContent','quicklinks.network.php?newinterface=yes');			
		}
		
		function QuickLinkCyrus(){
				LoadAjax('BodyContent','quicklinks.cyrus.php');
		}
		
		function QuickLinkSystems(sfunction){
			Set_Cookie('QuickLinkCacheIndex', '$page?function='+sfunction, '3600', '/', '', '');
			LoadAjax('BodyContent','$page?function='+sfunction);
		}
		
		function QuickLinksHideUsrLogoff(){
			 document.location.href='squid.users.logoff.php';
		}
		
		
		function QuickLinkMemory(){
			QuickLinkSystems('section_start');
			return;
			var memorized=Get_Cookie('QuickLinkCacheIndex');
			if(memorized=='section_postfix'){memorized='section_start';}
			if(!memorized){
				QuickLinkSystems('section_start');
				return;
			}
			
			if(memorized.length>0){
				if(memorized=='quicklinks.network.php'){QuickLinkSystems('section_start');return;}
				LoadAjax('BodyContent',memorized);
			}else{
				QuickLinkSystems('section_computers_infos');

			}
		
		}
		
		function QuickLinkShow(id){
			".@implode("\n", $jsitems)."
			if(document.getElementById(id)){document.getElementById(id).className='QuickLinkOverTable';}
			}		
		
		LoadQuickTaskBar();
		QuickLinkMemory();
		RemoveSearchEnginePerform();
	</script>
	";
	
	
	


$tpl=new templates();
$html= $tpl->_ENGINE_parse_body($html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	


function section_myaccount(){
	
	$html="
	
	<script>
		LoadAjax('BodyContent','squid.users.account.php');	
		QuickLinkShow('quicklinks-myaccount');
	</script>";
	

	
	echo $html;	
	
}

function section_realtime_events(){
	$html="
	
	<script>
		LoadAjax('BodyContent','squid.users.rtmm.php');	
		QuickLinkShow('quicklinks-realtime_events_squid');
	</script>";
	

	
	echo $html;	
}


function section_start(){
	
		error_log("[{$_SESSION["uid"]}]::OK ".__FUNCTION__."() in " . basename(__FILE__). " line ".__LINE__);
	
	$html="
	
	<script>
		LoadAjax('BodyContent','squid.users.homepage.php');	
		//QuickLinkShow('quicklinks-manage_your_server');
	</script>";
	

	
	echo $html;	
	
	
	
}


function section_security(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$users=new usersMenus();
		$superuser=Paragraphe("superuser-64.png","{account}","{accounts_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes');");
	
	$RootPasswordChangedTXT=Paragraphe('cop-lock-64.png',
		"{root_password_not_changed}",
		"{root_password_not_changed_text}",
		"javascript:Loadjs('system.root.pwd.php')",
		"{root_password_not_changed_text}");	

	if($users->SAMBA_INSTALLED){
		
	$RootPasswordSamba=Paragraphe('members-priv-64.png',
		"{domain_admin}",
		"{domain_admin_text}",
		"javascript:Loadjs('samba.index.php?script=yes&behavior-admin=yes')",
		"{domain_admin_text}");		
		
	}else{
		$RootPasswordSamba=Paragraphe('members-priv-64-grey.png',
		"{domain_admin}",
		"{domain_admin_text}",
		"",
		"{domain_admin_text}");	
		
	}
		
		
	$tr[]=$superuser;
	$tr[]=$RootPasswordChangedTXT;
	$tr[]=$RootPasswordSamba;
	$tr[]=kaspersky();
	$tr[]=statkaspersky();
	$tr[]=clamav();
	$tr[]=icon_troubleshoot();
	$tr[]=certificate();
	$tr[]=icon_externalports();
	$tr[]=incremental_backup();
$tables=CompileTr2($tr);

$t=time();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'><div id='$t'></div></td>
	<td valign='top'>$tables</td>
</tr>
</table>

<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));
	
	
}

function section_crossroads(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('crossroads.index.php?newinterface=yes');QuickLinkShow('quicklinks-load_balancing');</script>";}
function section_freeweb(){	echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('freeweb.php?in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-section_freeweb');</script>";}
function section_postfwd2(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes');QuickLinkShow('quicklinks-APP_POSTFWD2');</script>";}
function section_cyrus(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes');</script>";}
function section_dnsmasq(){echo "<script>Loadjs('dnsmasq.index.php?newinterface=yes');QuickLinkShow('quicklinks-APP_DNSMASQ');;</script>";}
function section_kav4proxy(){echo "<div id='QuicklinksKav4proxy'></div><script>LoadAjax('QuicklinksKav4proxy','kav4proxy.php?inline=yes');QuickLinkShow('quicklinks-APP_CYRUS');</script>";}
function section_webfiltering_dansguardian(){echo "<script>LoadAjax('BodyContent','squid.users.categories.php');QuickLinkShow('quicklinks-WEB_FILTERING');</script>";}
function section_zarafa(){echo "<div id='zarafa-inline-config'></div><script>Loadjs('zarafa.web.php?in-line=yes');QuickLinkShow('quicklinks-APP_ZARAFA');</script>";}
function section_mgreylist(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('milter.greylist.index.php?js=yes&in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-APP_MILTERGREYLIST');</script>";}
function section_squid_tasks(){echo "<script>LoadAjax('BodyContent','squid.statistics.tasks.php');QuickLinkShow('quicklinks-tasks');</script>";}
function section_fetchmail(){echo "<script>LoadAjax('BodyContent','fetchmail.index.php?quicklinks=yes');QuickLinkShow('quicklinks-APP_FETCHMAIL');</script>";}
function section_browser_config(){echo "<script>LoadAjax('BodyContent','squid.users.browser.php');QuickLinkShow('quicklinks-proxy_main_settings');</script>";}









function section_softwares(){
	$page=CurrentPageName();
	$tr[]=icon_update_clamav();
	$tr[]=icon_update_spamassassin_blacklist();
	$tr[]=icon_update_artica();
	$tr[]=applis();
	$tr[]=apt();
	
$tables=CompileTr2($tr);
	

$t=time();

$html="
<table style='width:100%'>
<tr>
	<td valign='top'><span id='$t'></span></td>
	<td valign='top'>$tables</td>
</tr>
</table>
<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function section_myhost_config(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$frontend_settings=Paragraphe("64-settings.png",'{index_page_settings}','{index_page_settings_text}',"javascript:Loadjs('artica.performances.php?cron-js=yes');","{internal_hard_drives_text}");
	$artica_settings=Paragraphe('folder-interface-64.png',"{advanced_options}","{advanced_artica_options_text}","javascript:Loadjs('artica.settings.php?js=yes&ByPopup=yes');","{advanced_artica_options_text}");
	$proxy=Paragraphe("proxy-64.png","{http_proxy}","{http_proxy_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');");
	
	$web_interface_settings=Paragraphe("folder-performances-64.png","{web_interface_settings}","{web_interface_settings_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-webinterface=yes');");
		
	$tr[]=$frontend_settings;
	$tr[]=$web_interface_settings;
	$tr[]=$proxy;

	
	$tr[]=$artica_settings;
	
	

	

$links=CompileTr2($tr);
$t=time();

$html="
<table style='width:100%'>
<tr>
	<td valign='top'><div id='$t'></div></td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));		
	
}

function section_computers_infos(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	
		$array["section_computers_infos_OS"]='{server}';
		$array["section_security"]='{security}';
		
		
		if($q->COUNT_ROWS("repquota", "artica_events")){
			$array["section_quotas"]='{quotas}';
			
		}
		
		
		if($users->OCS_LNX_AGENT_INSTALLED){
			$array["ocsagent"]="{APP_OCSI_LNX_CLIENT}";
		}
		
		$array["section_computers_infos_events"]='{artica_events}';
		$array["syslog"]='{syslog}';
		$array["openports"]='{opened_ports}';
		
		
		
		
	while (list ($num, $ligne) = each ($array) ){
		if($num=="section_computers_infos_events"){
			$tab[]="<li><a href=\"artica.events.php?popup=yes&full-size=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="section_quotas"){
			$tab[]="<li><a href=\"repquotas.php\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="ocsagent"){
			$tab[]="<li><a href=\"ocs.agent.php?inline=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="syslog"){
			$tab[]="<li><a href=\"syslog.php?popup=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}			
		
		if($num=="openports"){
			$tab[]="<li><a href=\"lsof.ports.php?popup=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}			
		$tab[]="<li><a href=\"$page?function=$num\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_computer_infos_quicklinks' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_computer_infos_quicklinks').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}


function section_computers_infos_OS(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$syslog=Paragraphe("syslog-64.png","{system_log}","{system_log_text}","javascript:Loadjs('syslog.engine.php?windows=yes');");
	$dmesg=Paragraphe("syslog-64.png","{kernel_infos}","{kernel_infos_text}","javascript:Loadjs('syslog.dmesg.php?windows=yes');");
	$articacron=Paragraphe("folder-tasks-64.png","{internal_scheduler}","{internal_scheduler_text}","javascript:Loadjs('artica.internal.cron.php');");
	
	
	$tr[]=sysinfos();
	$tr[]=icon_system();
	$tr[]=icon_memory();
	$tr[]=icon_harddrive();
	$tr[]=icon_terminal();
	$tr[]=$syslog;
	$tr[]=$dmesg;
	$tr[]=$articacron;
	$tr[]=scancomputers();
	


$links=CompileTr2($tr);
$t=time();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'><span id='$t'></span></td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
	QuickLinkShow('quicklinks-system_information');
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));
	
}

function section_computer_header(){
if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
$hour=date('h');
$key_cache="CACHEINFOS_STATUSSEVERREDNDER$hour";
$page=CurrentPageName();
//if(isset($_SESSION[$key_cache])){return $_SESSION[$key_cache];}
unset($_SESSION["DISTRI"]);

include_once('ressources/class.os.system.inc');
include_once("ressources/class.os.system.tools.inc");
$sock=new sockets();
$datas=unserialize($sock->getFrameWork("services.php?dmicode=yes"));
$img="img/server-256.png";
$foundChassis=false;
if(is_array($datas)){
	$proc_type=$datas["PROC_TYPE"];
	$MANUFACTURER =$datas["MANUFACTURER"];
	$PRODUCT=$datas["PRODUCT"];
	$CHASSIS=$datas["CHASSIS"];
	$md5Chassis=md5("{$datas["MANUFACTURER"]}{$datas["CHASSIS"]}{$datas["PRODUCT"]}");
	if(is_file("img/vendors/$md5Chassis.jpg")){$img="img/vendors/$md5Chassis.jpg";$foundChassis=true;}
	if(is_file("img/vendors/$md5Chassis.jpeg")){$img="img/vendors/$md5Chassis.jpeg";$foundChassis=true;}
	if(is_file("img/vendors/$md5Chassis.png")){$img="img/vendors/$md5Chassis.png";$foundChassis=true;}
	
}

if(!$foundChassis){
	$chassis_serial="<tr>
					<td valign='top' style='font-size:12px' class=legend>{serial}:</td>
					<td valign='top' style='font-size:12px'><strong>$md5Chassis</td>
				</tr>";
}

if(!isset($_SESSION["DISTRI"])){
	$sys=new systeminfos();
	writelogs('Loading datas system for session',__FUNCTION__,__FILE__);
	$distri=$sys->ditribution_name;
	$kernel=$sys->kernel_version;
	$LIBC=$sys->libc_version;
	$users=new usersMenus();
	$os=new os_system();
	$arraycpu=$os->cpu_info();
	$cpuspeed=round(($arraycpu["cpuspeed"]/1000*100)/100,2); 
	$host=$users->hostname;
	$publicip=@file_get_contents("ressources/logs/web/myIP.conf");
$distri_logo="img/serv-mail-linux.png";
if(is_file("img/$users->LinuxDistriCode.png")){$distri_logo="img/$users->LinuxDistriCode.png";}
if(is_file("img/$users->LinuxDistriCode.gif")){$distri_logo="img/$users->LinuxDistriCode.gif";}

if(preg_match("#Broken pipevmware#i", $MANUFACTURER)){$MANUFACTURER="VMWare";}
if(preg_match("#Broken pipevmware#i", $PRODUCT)){$PRODUCT="VMWare";}
if(preg_match("#Broken pipevmware#i", $CHASSIS)){$CHASSIS="VMWare";}



	$distri="
	<center>
	
	
	<table style='width:99%;color:black;' class=form>
		<tr>
			<td colspan=2 align=center><img src='$img'></td>
		</tr>
				<tr>
					<td valign='top' style='font-size:12px' class=legend>{server}:</td>
					<td valign='top' style='font-size:12px'><strong id='squidcklinks-host-infos'></strong><strong>$MANUFACTURER, $PRODUCT, $CHASSIS</td>
				</tr>
				<tr>
					<td valign='top' style='font-size:12px' class=legend>{public_ip}:</td>
					<td valign='top' style='font-size:12px'><strong>$publicip</strong></td>
				</tr>				
				<tr>
					<td valign='top' style='font-size:12px' class=legend>{processors}:</td>
					<td valign='top' style='font-size:12px'><strong>{$arraycpu["cpus"]} cpu(s):{$cpuspeed}GHz<br>$proc_type</strong></td>
				</tr>				
				<tr>
					<td valign='top' style='font-size:12px' class=legend>Artica:</td>
					<td valign='top' style='font-size:12px'><strong>$users->ARTICA_VERSION</strong></td>
				</tr>							
					<td valign='top' style='font-size:12px'><img src='$distri_logo'></td>
					<td valign='top' style='font-size:12px'><strong>$distri<br>kernel $kernel
					<br>libc $LIBC</strong>
					</td>
				</tr>
				$chassis_serial
			</table>
</center>
<script>
	LoadAjaxTiny('squidcklinks-host-infos','$page?squidcklinks-host-infos=yes');
</script>
	


";
	$tpl=new templates();
	$distri=$tpl->_ENGINE_parse_body($distri);
	$_SESSION["DISTRI"]=$distri;}else{$distri=$_SESSION["DISTRI"];}
	$html="$distri";
	$_SESSION[$key_cache]=$html;
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;
}

function squidcklinks_host_infos(){
	
	$tpl=new templates();
	$sock=new sockets();
	
	$host=$sock->getFrameWork("cmd.php?full-hostname=yes");
	$host=texttooltip($host,"{apply}","Loadjs('system.nic.config.php?change-hostname-js=yes')",null,0,"font-size:14px;text-decoration:underline;font-weight:bold");
	echo $tpl->_ENGINE_parse_body($host)."<script>ChangeHTMLTitlePerform()</script>";
}


function off(){
	
$html="<script>LoadAjax('middle','squid.users.quicklinks.php');</script>";
	
	

echo $html;
	
}

function LocalParagraphe($title,$text,$js,$img){
	
		$js=str_replace("javascript:","",$js);
		$id=md5($js);
		$img_id="{$id}_img";
		Paragraphe($img, $title, $text,$js);
	$html="
	<table style='width:198px;'>
	<tr>
	<td width=1% valign='top'>" . imgtootltip($img,"{{$text}}","$js",null,$img_id)."</td>
	<td><strong style='font-size:12px'>{{$title}}</strong><div style='font-size:11px'>{{$text}}</div></td>
	</tr>
	</table>";
	

return "<div style=\"width:200px;margin:2px\" 
	OnMouseOver=\"javascript:ParagrapheWhiteToYellow('$id',0);this.style.cursor='pointer';\" 
	OnMouseOut=\"javascript:ParagrapheWhiteToYellow('$id',1);this.style.cursor='auto'\" OnClick=\"javascript:$js\">
  <b id='{$id}_1' class=\"RLightWhite\">
  <b id='{$id}_2' class=\"RLightWhite1\"><b></b></b>
  <b id='{$id}_3' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_4' class=\"RLightWhite3\"></b>
  <b id='{$id}_5' class=\"RLightWhite4\"></b>
  <b id='{$id}_6' class=\"RLightWhite5\"></b></b>

  <div id='{$id}_0' class=\"RLightWhitefg\" style='padding:2px;'>
   $html
  </div>

  <b id='{$id}_7' class=\"RLightWhite\">
  <b id='{$id}_8' class=\"RLightWhite5\"></b>
  <b id='{$id}_9' class=\"RLightWhite4\"></b>
  <b id='{$id}_10' class=\"RLightWhite3\"></b>
  <b id='{$id}_11' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_12' class=\"RLightWhite1\"><b></b></b></b>
</div>
";		
		
	
}


function main_kaspersky_action(){
	include_once('ressources/class.kas-filter.inc');
	$kas=new kas_single();
	$html="
	<table style='width:100%'>
	<tr>
		<td align='right'><strong>{ACTION_SPAM_MODE}:</strong></td>
		<td>" . Field_array_Hash($kas->ACTION_SPAM_MODE_FIELD,'ACTION_SPAM_MODE',$kas->ACTION_SPAM_MODE)."</td>
	</tr>
	<tr>
		<td align='right'><strong>{ACTION_SPAM_SUBJECT}:</strong></td>
		<td>" . Field_text('ACTION_SPAM_SUBJECT_PREFIX',$kas->ACTION_SPAM_SUBJECT_PREFIX,'width:100%')."</td>
	<td>
	<tr><td colspan=2><hr></td></tr>
	
	
	
	<tr>
		<td align='right'><strong>{ACTION_PROBABLE_MODE}:</td>
		<td>" . Field_array_Hash($kas->ACTION_SPAM_MODE_FIELD,'ACTION_PROBABLE_MODE',$kas->ACTION_PROBABLE_MODE)."</td>
	</tr>	
	<tr>
		<td align='right'><strong>{ACTION_PROBABLE_MODE_SUBJECT}:</td>
		<td>" . Field_text('ACTION_PROBABLE_SUBJECT_PREFIX',$kas->ACTION_PROBABLE_SUBJECT_PREFIX,'width:100%')."</td>
	</tr>		
	<tr><td colspan=2 align='right'><input type='button' OnClick=\"javascript:kavStep4();\" value='{build}&nbsp;&raquo;'></td></tr>
	
	
	</table>";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'kas.group.rules.php');	

	
}


function main_kaspersky(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$users->LoadModulesEnabled();
	$page=CurrentPageName();

	$html="
	<p style='font-size:12px;font-weight:bold'>{welcome_kaspersky}</p>
	<table style='width:100%'>
	";
	
	if($users->kas_installed){
		$html=$html . "<tr>
	<td width=1%>
		" . Field_numeric_checkbox_img('enable_kasper',$users->KasxFilterEnabled,'{enable_disable}').
	"</td>
	<td>{enable_kaspersky_antispam}</td>
	</tr>";
	
	
	}
	
	if($users->KAV_MILTER_INSTALLED){
		$html=$html . "<tr>
	<td width=1%>
		" . Field_numeric_checkbox_img('enable_kav',$users->KAVMILTER_ENABLED,'{enable_disable}').
	"</td>
	<td>{enable_kaspersky_antivirus}</td>
	</tr>";
	
	
	}	
	
	$html=$html . "
	<tr><td colspan=2 align='right'><input type='button' OnClick=\"javascript:kavStep2();\" value='{next}&nbsp;&raquo;'></td></tr>
	
	</table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function main_kaspersky_level(){
	
		include_once('ressources/class.kas-filter.inc');
		$kas=new kas_single();
		$OPT_SPAM_RATE_LIMIT_TABLE=array(4=>"{maximum}",3=>"{high}",2=>"{normal}",1=>"{minimum}");
		$OPT_SPAM_RATE_LIMIT=Field_array_Hash($OPT_SPAM_RATE_LIMIT_TABLE,'OPT_SPAM_RATE_LIMIT',$kas->main_array["OPT_SPAM_RATE_LIMIT"]);
		
		$html="
		<table style='width:100%'>
		<tr>
			<td align='right' nowrap valign='top'><strong>{OPT_SPAM_RATE_LIMIT}:</strong></td>
			<td valign='top'>$OPT_SPAM_RATE_LIMIT</td>
			<td valign='top'>{OPT_SPAM_RATE_LIMIT_TEXT}</td>
		<tr><td colspan=3 align='right'><input type='button' OnClick=\"javascript:kavStep3();\" value='{next}&nbsp;&raquo;'></td></tr>
		</tr>
		</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'kas.group.rules.php');		
	
	
}

function kaspersky(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!$users->POSTFIX_INSTALLED){return false;}
	$users->LoadModulesEnabled();
	$page=CurrentPageName();
	if($users->kas_installed OR $users->KAV_MILTER_INSTALLED){
		$img="bigkav-64.png";
		$js="Loadjs('configure.server.php?script=enable_kasper')";
		return Paragraphe($img,"{enable_kaspersky}","{enable_kaspersky_text}","javascript:$js");
		return LocalParagraphe("enable_kaspersky","enable_kaspersky_text","Loadjs('configure.server.php?script=enable_kasper')","bigkav24.png");
	}
	
}
function icon_update_clamav(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->CLAMAV_INSTALLED){return null;}
	if(!$users->KASPERSKY_WEB_APPLIANCE){return null;}
	if(!$users->KASPERSKY_SMTP_APPLIANCE){return null;}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('clamav.update.php');";
	$img="clamav-update-48.png";
	return Paragraphe($img,"{UPDATE_CLAMAV}","{UPDATE_CLAMAV_EXPLAIN}","javascript:$js");
	return LocalParagraphe("UPDATE_CLAMAV","UPDATE_CLAMAV_EXPLAIN",$js,$img);				
}	
function icon_troubleshoot(){
	$GLOBALS["ICON_FAMILY"]="REPAIR";
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){return null;}
	$js="Loadjs('index.troubleshoot.php');";
	$img="64-troubleshoot-index.png";
	return Paragraphe($img,"{troubleshoot}","{troubleshoot_explain}","javascript:$js");
	return LocalParagraphe("troubleshoot","troubleshoot_explain",$js,$img);				
		
}
function icon_externalports(){
	$GLOBALS["ICON_FAMILY"]="SECURITY";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsArticaAdministrator){return null;}
	if($users->KASPERSKY_WEB_APPLIANCE){return null;}
	
	$js="Loadjs('external-ports.php')";
	$img="64-bind.png";
	return Paragraphe($img,"{EXTERNAL_PORTS}","{EXTERNAL_PORTS_TEXT}","javascript:$js");
	return LocalParagraphe("EXTERNAL_PORTS","EXTERNAL_PORTS_TEXT",$js,$img);	
	}	
function postmaster(){
	$GLOBALS["ICON_FAMILY"]="SMTP";
$sock=new sockets();
if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return null;}	
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$users->POSTFIX_INSTALLED){return false;}	
return LocalParagraphe("postmaster","postmaster_text","Loadjs('postfix.postmaster.php')","folder-useraliases2-48.png");	
}

function Firstwizard(){
	return LocalParagraphe("first_settings","first_settings","Loadjs('configure.server.php?script=wizard')","folder-update-48.png");	
}

function wizard_kaspersky_appliance_smtp(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->KASPERSKY_SMTP_APPLIANCE){return null;}
	return LocalParagraphe("wizard_kaspersky_smtp_appliance","wizard_kaspersky_smtp_appliance_text_wizard","Loadjs('wizard.kaspersky.appliance.php')","kaspersky-wizard-48.png");
}


function clamav(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	$page=CurrentPageName();
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if($users->KASPERSKY_WEB_APPLIANCE){return null;}
	if($users->KASPERSKY_SMTP_APPLIANCE){return null;}
	$img="clamav-64.png";
	$js="Loadjs('clamav.index.php');";
	return Paragraphe($img,"{clamav_av}","{clamav_av_text}","javascript:$js");
	return LocalParagraphe("clamav_av","clamav_av_text","Loadjs('clamav.index.php');","clamav-48.png");
	}



function nic_settings(){
	$GLOBALS["ICON_FAMILY"]="NETWORK";
	$page=CurrentPageName();
	$js="Loadjs('system.nic.config.php?js=yes')";
	$img="net-card-64.png";
	return Paragraphe($img,"{nic_settings}","{nic_settings_text}","javascript:$js");
	return LocalParagraphe("nic_settings","nic_settings_text",$js,$img);
	}
	
function wizard_backup(){
	$GLOBALS["ICON_FAMILY"]="BACKUP";
$page=CurrentPageName();
	$js="Loadjs('wizard.backup-all.php')";
	$img="48-dar-index.png";
	return LocalParagraphe("manage_backups","manage_backups_text",$js,"48-dar-index.png");
	

	
}

function scancomputers(){
	$GLOBALS["ICON_FAMILY"]="NETWORK";
	$js="Loadjs('computer-browse.php')";
	$img="64-win-nic-browse.png";
	return Paragraphe($img,"{browse_computers}","{browse_computers_text}","javascript:$js");
	return LocalParagraphe("browse_computers","browse_computers_text",$js,$img);
	}
	


		
	
function postfix_events(){
	$GLOBALS["ICON_FAMILY"]="SMTP";
	$js="Loadjs('postfix-realtime-events.php')";
	$img="folder-logs-643.png";
	return Paragraphe($img,"{postfix_realtime_events}","{postfix_realtime_events_text}","javascript:$js");
	return LocalParagraphe("postfix_realtime_events","postfix_realtime_events_text",$js,$img);
	}

function dmidecode(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	$js="Loadjs('dmidecode.php')";
	$img="system-64.org.png";
	return Paragraphe($img,"{dmidecode}","{dmidecode_text}","javascript:$js");
	return LocalParagraphe("dmidecode","dmidecode_text",$js,$img);
	}		
function icon_update_artica(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsArticaAdministrator){return null;}
	$GLOBALS["ICON_FAMILY"]="UPDATE";
	$js="Loadjs('artica.update.php?js=yes')";
	$img="folder-64-artica-update.png";
	$tpl=new templates();
	return Paragraphe($img,"{artica_autoupdate}","{artica_autoupdate_text}","javascript:$js");
	return $tpl->_ENGINE_parse_body(LocalParagraphe("artica_autoupdate","artica_autoupdate_text",$js,$img),'system.index.php');	
	}
	
function icon_update_spamassassin_blacklist(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->spamassassin_installed){return null;}
	if(!$users->AsPostfixAdministrator){return null;}
	$js="Loadjs('sa-blacklist.php')";
	$img="64-spam.png";
	$tpl=new templates();
	return Paragraphe($img,"{APP_SA_BLACKLIST}","{APP_SA_BLACKLIST_AUTOUPDATE}","javascript:$js");
	return $tpl->_ENGINE_parse_body(LocalParagraphe("APP_SA_BLACKLIST","APP_SA_BLACKLIST_AUTOUPDATE",$js,$img),'system.index.php');	
	}	
	
function statkaspersky(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	$js="YahooWin(580,'kaspersky.index.php','Kaspersky');";
	$img="bigkav-64.png";		
	return Paragraphe($img,"{Kaspersky}","{kaspersky_av_text}","javascript:$js");
	return LocalParagraphe("Kaspersky","kaspersky_av_text","YahooWin(580,'kaspersky.index.php','Kaspersky');","bigkav-48.png");
}
function sysinfos(){
	
}
function certificate(){
	$GLOBALS["ICON_FAMILY"]="SECURITY";
	$js="Loadjs('postfix.tls.php?js-certificate=yes')";
	$img="certificate-download-64.png";	
	return Paragraphe($img,"{ssl_certificate}","{ssl_certificate_text}","javascript:$js");
	return LocalParagraphe("ssl_certificate","ssl_certificate_text","Loadjs('postfix.tls.php?js-certificate=yes')","folder-lock-48.png");

}
function apt(){
	$sock=new sockets();
	$EnableSystemUpdates=$sock->GET_INFO("EnableSystemUpdates");
	if(!is_numeric($EnableSystemUpdates)){$EnableSystemUpdates=0;}
	if($EnableSystemUpdates==0){return;}
	$GLOBALS["ICON_FAMILY"]="UPDATE";
	$js="Loadjs('artica.repositories.php')";
	$img="DEBIAN_mirror-64.png";	
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsDebianSystem){return null;}
	return Paragraphe($img,"{repository_manager}","{repository_manager_text}","javascript:$js");
	return LocalParagraphe("repository_manager","repository_manager_text","Loadjs('artica.repositories.php')","folder-lock-48.png");
}
function incremental_backup(){
	$GLOBALS["ICON_FAMILY"]="BACKUP";
	$js="Loadjs('wizard.backup-all.php')";
	$img="64-dar-index.png";
	return Paragraphe($img,"{manage_backups}","{manage_backups}","javascript:$js");
	return LocalParagraphe("manage_backups","manage_backups",$js,"48-dar-index.png");
}
function atica_perf(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	$img="perfs-64.png";	
	$js="Loadjs('artica.performances.php')";
	
	return Paragraphe($img,"{artica_performances}","{artica_performances_text}","javascript:$js");
	
}
function applis(){
	$GLOBALS["ICON_FAMILY"]="SOFTWARES";
	$js="Loadjs('setup.index.php?js=yes')";
	$img="bg-applis-64.png";
	return Paragraphe($img,"{install_applis}","{install_applis_text}","javascript:$js");
		
		}
function icon_system(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('admin.index.services.status.php?js=yes')";
	$img="rouage-64.png";
	return Paragraphe($img,"{manage_services}","{manage_services_text}","javascript:$js");
}

function icon_terminal(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('system.terminal.php')";
	$img="terminal-64.png";
	return Paragraphe($img,"{commandline}","{commandline_text}","javascript:$js");	
	
}
	


function icon_memory(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="GotoSystemMemory()";
	$img="bg_memory-64.png";
	return Paragraphe($img,"{system_memory}","{system_memory_text}","javascript:$js");
	
	}
function icon_harddrive(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('system.internal.disks.php')";
	$img="64-hd.png";
	return Paragraphe($img,"{internal_hard_drives}","{internal_hard_drives_text}","javascript:$js");
		
	}	
function icon_adduser(){
	$GLOBALS["ICON_FAMILY"]="USER";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$sock=new sockets();
	if(!$users->AllowAddUsers){return null;}
	if($users->ARTICA_META_ENABLED){
		if($sock->GET_INFO("AllowArticaMetaAddUsers")<>1){return null;}
	}
	$js="Loadjs('create-user.php');";
	$img="identity-add-64.png";
	return Paragraphe($img,"{add_user}","{add user explain}","javascript:$js");
			
}		