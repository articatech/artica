<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsHotSpotManager){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
	
	if(isset($_POST["EnableMaintenanceHotSpot2"])){EnableMaintenanceHotSpot2();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["cas"])){cas_auth();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["options"])){options();exit;}
	if(isset($_POST["EnableArticaHotSpot"])){SaveEnable();exit;}
	if(isset($_POST["ArticaHotSpotPort"])){EnableArticaHotSpot();exit;}
	if(isset($_POST["EnableArticaHotSpotCAS"])){EnableArticaHotSpotCAS();exit;}
	if(isset($_GET["terme-of-use"])){echo terme_of_use();exit;}
	if(isset($_POST["USETERMSTEXT"])){xSaveOptions();exit;}
	if(isset($_POST["USELDAP"])){xSaveOptions();exit;}
	if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
	if(isset($_GET["radius"])){radius_config();exit;}
	if(isset($_POST["RAD_SERVER"])){xSaveOptions();exit;}
	if(isset($_GET["hostspot-status"])){services_status();exit;}
	if(isset($_GET["download-debug"])){download_debug();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$YahooWin=2;
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	$title=$tpl->_ENGINE_parse_body("{HotSpot} V3");
	$html="
	var YahooWinx=$YahooWin;
	if(YahooWinx==2){
		YahooWin2Hide();
		YahooWin6Hide();
	}	
	YahooWin$YahooWin('950','$page?tabs=yes$YahooWinUri','$title')";
	echo $html;
}

function status(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$HotSpotArticaDebug=intval($sock->GET_INFO("HotSpotArticaDebug"));
	$HotSpotWhiteWhatsApp=intval($sock->GET_INFO("HotSpotWhiteWhatsApp"));
	$EnableMaintenanceHotSpot=intval($sock->GET_INFO("EnableMaintenanceHotSpot"));
	$ArticaHotSpotEmergency=intval($sock->GET_INFO("ArticaHotSpotEmergency"));
	$error=null;
	if($ArticaHotSpotEmergency==1){
		$emergency_bt="<center style='margin:30px'>". button("{disable_emergency_mode}","Loadjs('squid.hostspot.emergency.disable.progress.php')",40)."</center>";
		$error=$emergency_bt.FATAL_ERROR_SHOW_128("{hotspot_in_emergency_mode_explain}");
	}
	
	if($EnableArticaHotSpot==0){
		$html="<div id='$t' class=explain style='font-size:20px;margin-bottom:30px'>{captive_portal_explain}</div>
		<center style='margin:50px'>
		".button("{activate_hostpot}", "Loadjs('squid.webauth.wizard.php');",40).
		"</center>";
		
		
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top'>
		<div style='width:480px'>
		<div style='width:98%' class=form id='hostspot-status'></div>
		</div>
		</td>
		<td valign='top' style='padding-left:15px'>		
			<div style='font-size:30px;margin-bottom:20px'>{captive_portal} ( revision 4.0 )</div>
			<div style='width:98%' class=form>
			$error
			".Paragraphe_switch_img("{activate_hostpot}","{activate_hostpot_explain}",
					"EnableArticaHotSpot",$EnableArticaHotSpot,null,"650")."
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:22px'>{whitelist}: WhatsApp</td>
				<td>". Field_checkbox_design("HotSpotWhiteWhatsApp", 1,$HotSpotWhiteWhatsApp)."</td>
			</tr>							
							
			<tr>
				<td class=legend style='font-size:22px'>{debug}:</td>
				<td>". Field_checkbox_design("HotSpotArticaDebug", 1,$HotSpotArticaDebug)."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><a href=\"javascript:blur();\"
						OnClick=\"javascript:YahooWin2('650','$page?download-debug=yes','Debug file');\"
						style='font-size:22px;text-decoration:underline'>hotspot.debug.gz</a>
				</td>
			</tr>
			</table>				
							
				<div style='text-align:right;margin:15px;font-size:22px'>". button("{reconfigure}", "Loadjs('squid.hostspot.reconfigure.php')",28)."&nbsp;|&nbsp;". button("{apply}", "Save$t()",28)."</div>
			</div>
			<p>&nbsp;</p>
			<div style='width:98%' class=form>
			".Paragraphe_switch_img("{activate_maintenance_page}","{EnableMaintenanceHotSpot}",
					"EnableMaintenanceHotSpot",$EnableMaintenanceHotSpot,null,"650")."
			
			<div style='text-align:right;margin:15px;font-size:22px'>". button("{apply}", "Save2$t()",28)."</div>
			
			</td>
	</tr>
	</table>
	<script>
	var xsave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.hostspot.reconfigure.php');
		
	}
	var xsave2$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('squid_hotspot');
		
	}

function Save$t(){
		var XHR = new XHRConnection();
		var HotSpotWhiteWhatsApp=0;
		var HotSpotArticaDebug=0;
		
		if(document.getElementById('HotSpotWhiteWhatsApp').checked){HotSpotWhiteWhatsApp=1;}
		if(document.getElementById('HotSpotArticaDebug').checked){HotSpotArticaDebug=1;}
		
		XHR.appendData('EnableArticaHotSpot',document.getElementById('EnableArticaHotSpot').value);
		XHR.appendData('HotSpotArticaDebug',HotSpotArticaDebug);
		XHR.appendData('EnableMaintenanceHotSpot',document.getElementById('EnableMaintenanceHotSpot').value);
		XHR.appendData('HotSpotWhiteWhatsApp',HotSpotWhiteWhatsApp);
		XHR.sendAndLoad('$page', 'POST',xsave$t);
	}
	
function Save2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableMaintenanceHotSpot2',document.getElementById('EnableMaintenanceHotSpot').value);
	XHR.sendAndLoad('$page', 'POST',xsave2$t);
}

LoadAjax('hostspot-status','$page?hostspot-status=yes');	
</script>						
						
						
";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}


function options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	
	
	if($HotSpotConfig["USETERMSLABEL"]==null){$HotSpotConfig["USETERMSLABEL"]="I agree to terms";}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$HospotHTTPServerName=trim($sock->GET_INFO("HospotHTTPServerName"));
	
	
	
	
	$Timez[5]="5 {minutes}";
	$Timez[15]="15 {minutes}";
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
	$Timez[43200]="1 {month}";
	$Timez[129600]="3 {months}";
	$Timez[259200]="6 {months}";
	$Timez[388800]="9 {months}";
	$Timez[518400]="1 {year}";
	
	
	$WifidogClientTimeout=intval($sock->GET_INFO("WifidogClientTimeout"));
	if($WifidogClientTimeout<5){$WifidogClientTimeout=30;}
	
	
	$lockAd=0;
	if($EnableKerbAuth==0){
		$lockAd=1;
		$exp[]="<li style='font-weight:bold'>{not_connected_to_active_directory}</li>";
	}
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$lockAd=1;
		$exp[]="<li style='font-weight:bold'>{license_inactive}</li>";		
	}
	
	if($lockAd==1){
		$explainNotAd="<div class=explain>{ad_database_is_disabled_because}".@implode("<br>-", $exp)."</div>";
	}
	
	$html="
	<div id='$t-animate'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=3 style='font-size:18px'>{authentication}:<hr></td>
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{use_ldap_database}:</td>
		<td>". Field_checkbox_design("USELDAP", 1,$HotSpotConfig["USELDAP"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{use_dedicated_database}:</td>
		<td>". Field_checkbox_design("USEMYSQL", 1,$HotSpotConfig["USEMYSQL"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{use_active_directory}:</td>
		<td>". Field_checkbox_design("USEAD-$t", 1,$HotSpotConfig["USEAD"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin3('600','$page?radius=yes','{use_radius}');\"
		style='font-size:18px;text-decoration:underline'>{use_radius}</a>:</td>
		<td>". Field_checkbox_design("USERAD-$t", 1,$HotSpotConfig["USERAD"])."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin3('600','$page?terme-of-use=yes','{use_terme_of_use}');\"
		style='font-size:18px;text-decoration:underline'>{use_terme_of_use}</a>:</td>
		<td>". Field_checkbox_design("USETERMS", 1,$HotSpotConfig["USETERMS"])."</td>
		<td>&nbsp;</td>
	</tr>
			
				
				
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{label}</a>:</td>
		<td style='font-size:18px'>". Field_text("USETERMSLABEL",$HotSpotConfig["USETERMSLABEL"],
		"font-size:18px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{verif_auth_each}:</td>
		<td style='font-size:18px'>". Field_text("CACHE_TIME",$HotSpotConfig["CACHE_TIME"],"font-size:18px;width:90px")."&nbsp;{seconds}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;text-transform:capitalize'>{re_authenticate_each} ({default}):</td>
		<td style='font-size:18px'>". Field_array_Hash($Timez,"CACHE_AUTH",$HotSpotConfig["CACHE_AUTH"],null,null,0,"font-size:18px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveHostportConfig()","18px")."</td>
	</tr>
	</table>
	$explainNotAd
	<script>
		function LockAd$t(){
			var lock=$lockAd;
			if(lock==1){
				document.getElementById('USEAD-$t').disabled=true;
				document.getElementById('USEAD-$t').checked=false;
			}
		}
		
	var x_SaveHostportConfig$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.compile.progress.php?ask=yes');
		document.getElementById('$t-animate').innerHTML='';
		
		
	}		
		
		
		function SaveHostportConfig(){
			var lock=$lockAd;
			var USELDAP=0;
			var USEMYSQL=0;
			var USETERMS=0;
			var USERAD=0;
			var USEAD=0;
			var XHR = new XHRConnection();
			if(lock==0){
				if(document.getElementById('USEAD-$t').checked){
					USEAD=1;
				}
			}
			
			if(document.getElementById('USELDAP').checked){USELDAP=1;}
			if(document.getElementById('USEMYSQL').checked){USEMYSQL=1;}
			if(document.getElementById('USETERMS').checked){USETERMS=1;}
			if(document.getElementById('USERAD-$t').checked){USERAD=1;}
			XHR.appendData('USEAD',USEAD);
			XHR.appendData('USERAD',USERAD);
			XHR.appendData('USELDAP',USELDAP);
			XHR.appendData('USEMYSQL',USEMYSQL);
			XHR.appendData('USETERMS',USETERMS);
			XHR.appendData('USETERMSLABEL',document.getElementById('USETERMSLABEL').value);
			XHR.appendData('CACHE_TIME',document.getElementById('CACHE_TIME').value);
			XHR.appendData('CACHE_AUTH',document.getElementById('CACHE_AUTH').value);
			AnimateDiv('$t-animate');
			XHR.sendAndLoad('$page', 'POST',x_SaveHostportConfig$t);	
		}
		
	LockAd$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function xSaveOptions(){
	
	if(isset($_POST["RAD_PASSWORD"])){$_POST["RAD_PASSWORD"]=url_decode_special_tool($_POST["RAD_PASSWORD"]);}
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
	while (list ($num, $ligne) = each ($_POST) ){	
		$HotSpotConfig[$num]=$ligne;
		
	}
	
	$NewHotSpotConfig=base64_encode(serialize($HotSpotConfig));
	$sock->SaveConfigFile($NewHotSpotConfig, "HotSpotConfig");
	$sock->getFrameWork("squid.php?build-smooth=yes");
}

function cas_auth(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$squid=new squidbee();
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=$sock->GET_INFO("ArticaHotSpotCASPort");
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	if($ArticaHotSpotCASHost==null){$ArticaHotSpotCASHost="yourcasserver.domain.tld";}
	$t=time();
	$html="
	
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
	<td colspan=2>". Paragraphe_switch_img("{CAS_AUTH_LABEL}","{cas_HotSpot_text}",
		"EnableArticaHotSpotCAS",$EnableArticaHotSpotCAS,null,"450")."
	</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:18px'>{hostname}:</td>
			<td>". Field_text("ArticaHotSpotCASHost",$ArticaHotSpotCASHost,"font-size:18px;width:290px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{listen_port} (SSL):</td>
			<td>". Field_text("ArticaHotSpotCASPort",$ArticaHotSpotCASPort,"font-size:18px;width:90px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{CAS_CONTEXT}:</td>
			<td>". Field_text("ArticaHotSpotCASContext",$ArticaHotSpotCASContext,"font-size:18px;width:150px")."</td>
		</tr>
		<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHotSpot$t()","18px")."</td>
	</tr>
	</table>
</div>
<script>
	var x_SaveHotSpot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	
	
function SaveHotSpot$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaHotSpotCAS',document.getElementById('EnableArticaHotSpotCAS').value);
	XHR.appendData('ArticaHotSpotCASHost',document.getElementById('ArticaHotSpotCASHost').value);
	XHR.appendData('ArticaHotSpotCASPort',document.getElementById('ArticaHotSpotCASPort').value);
	XHR.appendData('ArticaHotSpotCASContext',document.getElementById('ArticaHotSpotCASContext').value);
	XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
}

document.getElementById('ArticaHotSpotCASPort').disabled=true;
</script>
";
echo $tpl->_ENGINE_parse_body($html);
	
		
}

function EnableArticaHotSpotCAS(){
	$sock=new sockets();
	
	$sock->SET_INFO("ArticaHotSpotCASHost", $_POST["ArticaHotSpotCASHost"]);
	$sock->SET_INFO("ArticaHotSpotCASPort", $_POST["ArticaHotSpotCASPort"]);
	$sock->SET_INFO("ArticaHotSpotCASContext", $_POST["ArticaHotSpotCASContext"]);
	
	$ipCalss=new IP();
	if(!$ipCalss->isValid($_POST["ArticaHotSpotCASHost"])){
		$ipaddr=gethostbyname($_POST["ArticaHotSpotCASHost"]);
		if(!$ipCalss->isValid($ipaddr)){
			echo "Unable to resolve {$_POST["ArticaHotSpotCASHost"]}\n";
			return;
		}
	}
	
	$sock->SET_INFO("EnableArticaHotSpotCAS", $_POST["EnableArticaHotSpotCAS"]);
	$sock->getFrameWork("hotspot.php?restart-firewall=yes");
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	
	$WIFIDOG_INSTALLED=false;
	
	if(is_file("/usr/local/bin/wifidog")){$WIFIDOG_INSTALLED=true;}
	if(is_file("/usr/bin/wifidog")){$WIFIDOG_INSTALLED=true;}
	if(is_file("/usr/sbin/wifidog")){$WIFIDOG_INSTALLED=true;}
	if($users->WIFIDOG_INSTALLED){$WIFIDOG_INSTALLED=true;}
	
	if(!$users->CONNTRACK_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{conntrackd_not_installed}");
		return;
	}
	
	
	if(!$WIFIDOG_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{ERROR_SERVICE_NOT_INSTALLED} <hr><center>".button("{manual_update}", "Loadjs('update.upload.php')",32)."</center>");
		return;
	}
	$sock=new sockets();
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$array["status"]='{status}';
	if($EnableArticaHotSpot==1){
		$array["sessions"]='{sessions}';
		$array["members"]='{local_members}';
		$array["rules"]='{rules}';
		$array["hotspot_whitelist"]='{trusted_websites}';
		$array["popup"]='{service_parameters}';
		$array["hotspot"]='{networks}';
		$array["events"]='{events}';
		
	}
	
	
	
	
	
	
	//$array["options"]='{options}';
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	
	
	$fontsize=20;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="members"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.members.php?members=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"webauth.rules.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		
		if($num=="sessions"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.members.php?sessions=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="members-ad"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.activedirectory.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		
		if($num=="self_register"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.smtp.php?tabs=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}	
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.events.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="hotspot_whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.www.whitelist.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
				
		if($num=="tweaks"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.tweaks.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		
		
		if($num=="hotspot"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.webauth.hotspots.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t$YahooWinUri\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "squid_hotspot",1490)."<script>LeftDesign('wifi-white-256-opac20.png');</script>";
	
	
	
}
function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_explain}");
	$t=$_GET["t"];
	$html="
		
var x_AddNewFreeWeb$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('squid_hotspot');
}

function AddNewFreeWeb$t(){
var servername=prompt('$addfree');
if(!servername){return;}
var XHR = new XHRConnection();
XHR.appendData('ADD_DNS_ENTRY','');
XHR.appendData('ForceInstanceZarafaID','');
XHR.appendData('ForwardTo','');
XHR.appendData('Forwarder','0');
XHR.appendData('SAVE_FREEWEB_MAIN','yes');
XHR.appendData('ServerIP','');
XHR.appendData('UseDefaultPort','0');
XHR.appendData('UseReverseProxy','0');
XHR.appendData('gpid','');
XHR.appendData('lvm_vg','');
XHR.appendData('servername',servername);
XHR.appendData('sslcertificate','');
XHR.appendData('uid','');
XHR.appendData('useSSL','0');
XHR.appendData('force-groupware','SPLASHSQUID');
AnimateDiv('$t-animate');
XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);
}


AddNewFreeWeb$t();

";
echo $html;

}

function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$squid=new squidbee();
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$SquidHotSpotPort=intval($sock->GET_INFO("SquidHotSpotPort"));
	$ArticaHotSpotPort=intval($sock->GET_INFO("ArticaHotSpotPort"));
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	
	$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
	
	$SquidHotSpotSSLPort=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	$WifiDogDebugLevel=intval($sock->GET_INFO("WifiDogDebugLevel"));
	$ArticaHotSpotInterface2=$sock->GET_INFO("ArticaHotSpotInterface2");
	
	$WifidogClientTimeout=intval($sock->GET_INFO("WifidogClientTimeout"));
	if($WifidogClientTimeout<5){$WifidogClientTimeout=30;}
	$HospotHTTPServerName=trim($sock->GET_INFO("HospotHTTPServerName"));
	$HotSpotDenySSL=intval($sock->GET_INFO("HotSpotDenySSL"));
	
	
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	
	if(!$users->CONNTRACK_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{conntrackd_not_installed}");
	}
	$WifiDogDebugLevelZ[null]="{none}";
	for($i=0;$i<11;$i++){
		$WifiDogDebugLevelZ[$i]=$i;
	}
	

	
	
	
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
	
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	$ArticaHotSpotEnableProxy=$sock->GET_INFO("ArticaHotSpotEnableProxy");
	
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	if(!is_numeric($ArticaHotSpotEnableProxy)){$ArticaHotSpotEnableProxy=1;}
	
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	
	$Params=unserialize($sock->GET_INFO("HotSpotEvasive"));
	$ApacheEvasiveInstalled=intval($sock->GET_INFO("ApacheEvasiveInstalled"));
	
	if(!is_numeric($Params["DOSEnable"])){$Params["DOSEnable"]=1;}
	if(!is_numeric($Params["DOSHashTableSize"])){$Params["DOSHashTableSize"]=1024;}
	if(!is_numeric($Params["DOSPageCount"])){$Params["DOSPageCount"]=3;}
	if(!is_numeric($Params["DOSSiteCount"])){$Params["DOSSiteCount"]=20;}
	if(!is_numeric($Params["DOSPageInterval"])){$Params["DOSPageInterval"]=1;}
	if(!is_numeric($Params["DOSSiteInterval"])){$Params["DOSSiteInterval"]=10;}
	if(!is_numeric($Params["DOSBlockingPeriod"])){$Params["DOSBlockingPeriod"]=5;}
	$HotSpotErrorRedirect=$sock->GET_INFO("HotSpotErrorRedirect");
	
	
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="HotSpot system";}
	
	if($ArticaHotSpotPort==0){
		$ArticaHotSpotPort=rand(38000, 64000);
		$sock->SET_INFO("ArticaHotSpotPort", $ArticaHotSpotPort);
	}
	
	if($ArticaSSLHotSpotPort==0){
		$ArticaSSLHotSpotPort=rand(38500, 64000);
		$sock->SET_INFO("ArticaSSLHotSpotPort", $ArticaSSLHotSpotPort);
	}
	
	if($SquidHotSpotPort==0){
		$SquidHotSpotPort=rand(40000, 64000);
		$sock->SET_INFO("SquidHotSpotPort", $SquidHotSpotPort);
	}
	
	if($SquidHotSpotSSLPort==0){
		$SquidHotSpotSSLPort=rand(40500, 64000);
		$sock->SET_INFO("SquidHotSpotSSLPort", $SquidHotSpotSSLPort);
	}
	
	
	$tcp=new networking();
	$interfacesZ=$tcp->Local_interfaces();
	while (list ($interface, $line) = each ($interfacesZ) ){
		$p=new system_nic($interface);
		if($p->IsBridged($interface)){continue;}
		$interfaces[$interface]=$p->NICNAME;
		
	}
	
	
	$HotSpotUseSSL=1;
	$HospotNoSSL=intval($sock->GET_INFO("HospotNoSSL"));
	
	if($HospotNoSSL==1){$HotSpotUseSSL=0;}
	if($HotSpotDenySSL==1){$HotSpotUseSSL=0;}
	
	
	$interfaces[null]="{none}";
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	if($HotSpotErrorRedirect==null){$HotSpotErrorRedirect="http://www.bing.com";}
	
	$t=time();
	$html="
	
	
	<div id='$t' class=explain style='font-size:20px'>{HotSpot_text}<br>{HotSpot_infra_text}</div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	
	<tr>
		<td  style='font-size:42px' colspan=2>{sessions}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{session_time}","{wifidog_disconnect_time}").":</td>
		<td>". Field_array_Hash($Timez,"WifidogClientTimeout", $WifidogClientTimeout,"style:font-size:22px")."</td>
	</tr>		
	
	<tr>
		<td  style='font-size:42px' colspan>{service2}:</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{listen_port}:</td>
		<td>". Field_text("ArticaHotSpotPort",$ArticaHotSpotPort,"font-size:22px;width:110px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{interface} (IN):</td>
		<td>". Field_array_Hash($interfaces,"ArticaHotSpotInterface", $ArticaHotSpotInterface,"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{interface} (OUT):</td>
		<td>". Field_array_Hash($interfaces,"ArticaHotSpotInterface2", $ArticaHotSpotInterface2,"style:font-size:22px")."</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:22px'>{log_level}:</td>
		<td>". Field_array_Hash($WifiDogDebugLevelZ,"WifiDogDebugLevel", $WifiDogDebugLevel,"style:font-size:22px")."</td>
	</tr>				
	<tr>
		<td  style='font-size:42px' colspan=2>{webserver}:</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{HospotHTTPServerName}","{HospotHTTPServerName_explain}").":</td>
		<td>". Field_text("HospotHTTPServerName",$HospotHTTPServerName,"font-size:22px;width:300px")."</td>
	</tr>

	<tr><td  style='font-size:42px' colspan=2>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{FreeWebsEnableModEvasive}","{mod_evasive_explain}").":</td>
		<td style='font-size:22px'>". Field_checkbox_design("DOSEnable",1,$Params["DOSEnable"],"EvasiveDisabled()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{DOSHashTableSize}","{DOSHashTableSize_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSHashTableSize",$Params["DOSHashTableSize"],"font-size:22px;width:110px")."&nbsp;MB</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{threshold}","{DOSPageCount_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSPageCount",$Params["DOSPageCount"],"font-size:22px;width:110px")."</td>
	</tr>

	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{total_threshold}","{DOSSiteCount_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSSiteCount",$Params["DOSSiteCount"],"font-size:22px;width:110px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{page_interval}","{DOSPageInterval_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSPageInterval",$Params["DOSPageInterval"],"font-size:22px;width:110px")."&nbsp;{seconds}</td>
	</tr>	
	


	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{site_interval}","{DOSSiteInterval_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSSiteInterval",$Params["DOSSiteInterval"],"font-size:22px;width:110px")."&nbsp;{seconds}</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{Blocking_period}","{DOSBlockingPeriod_explain}").":</td>
		<td style='font-size:22px'>". Field_text("DOSBlockingPeriod",$Params["DOSBlockingPeriod"],"font-size:22px;width:110px")."&nbsp;{seconds}</td>
		
	</tr>
	<tr><td  style='font-size:42px' colspan=2>&nbsp;</td></tr>	
	<tr>
		<td class=legend style='font-size:22px'>{listen_authentication_http_port}:</td>
		<td>". Field_text("ArticaSplashHotSpotPort",$ArticaSplashHotSpotPort,"font-size:22px;width:110px")."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{listen_authentication_https_port}:</td>
		<td>". Field_text("ArticaSplashHotSpotPortSSL",$ArticaSplashHotSpotPortSSL,"font-size:22px;width:110px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{http_error_redirect}","{wifidog_http_error_redirect_explain}").":</td>
		<td>". Field_text("HotSpotErrorRedirect",$HotSpotErrorRedirect,"font-size:22px;width:400px")."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{ssl_redirection}","{wifidog_use_ssl_explain}").":</td>
		<td>". Field_checkbox_design("HotSpotUseSSL",1,$HotSpotUseSSL)."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{deny_ssl}","{wifidog_deny_ssl_explain}").":</td>
		<td>". Field_checkbox_design("HotSpotDenySSL",1,$HotSpotDenySSL)."</td>
	</tr>				
				
	<tr>
		<td class=legend nowrap style='font-size:22px;'>{certificate}:</td>
		<td >". Field_array_Hash($sslcertificates, "ArticaSplashHotSpotCertificate",
				$ArticaSplashHotSpotCertificate,null,null,0,"font-size:22px")."</td>
	</tr>
<tr><td style='font-size:16px;text-align:right' colspan=2> ".texttooltip("{see} {certificates_center}",
			"position:right:{certificate_center_explain}","GotoCertificatesCenter()")."</td>
</tr>
						
	<tr>
		<td  style='font-size:42px' colspan=2>Proxy:</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{transparent_ssl}","{wifidog_transparent_ssl_explain}").":</td>
		<td>". Field_checkbox_design("ArticaHotSpotEnableMIT",1,$ArticaHotSpotEnableMIT)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>.".texttooltip("{transparent_http}","{wifidog_transparent_http_explain}").":</td>
		<td>". Field_checkbox_design("ArticaHotSpotEnableProxy",1,$ArticaHotSpotEnableProxy)."</td>
	</tr>									
	<tr>
		<td class=legend style='font-size:22px'>{listen_proxy_http_port}:</td>
		<td>". Field_text("SquidHotSpotPort",$SquidHotSpotPort,"font-size:22px;width:110px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{listen_proxy_https_port}:</td>
		<td>". Field_text("SquidHotSpotSSLPort",$SquidHotSpotSSLPort,"font-size:22px;width:110px")."</td>
	</tr>					

						
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHotSpot()","42px")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveHotSpot= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		Loadjs('squid.webauth.restart.php');

	}


function SaveHotSpot(){
		var XHR = new XHRConnection();
		
		XHR.appendData('ArticaHotSpotPort',document.getElementById('ArticaHotSpotPort').value);
		XHR.appendData('SquidHotSpotPort',document.getElementById('SquidHotSpotPort').value);
		
		XHR.appendData('ArticaSplashHotSpotPort',document.getElementById('ArticaSplashHotSpotPort').value);
		XHR.appendData('ArticaSplashHotSpotPortSSL',document.getElementById('ArticaSplashHotSpotPortSSL').value);
		XHR.appendData('ArticaHotSpotInterface',document.getElementById('ArticaHotSpotInterface').value);
		XHR.appendData('ArticaHotSpotInterface2',document.getElementById('ArticaHotSpotInterface2').value);
		XHR.appendData('HospotHTTPServerName',document.getElementById('HospotHTTPServerName').value);
		
		XHR.appendData('ArticaSplashHotSpotCertificate',document.getElementById('ArticaSplashHotSpotCertificate').value);
		
		XHR.appendData('WifiDogDebugLevel',document.getElementById('WifiDogDebugLevel').value);
		XHR.appendData('WifidogClientTimeout',document.getElementById('WifidogClientTimeout').value);
		
		
		if(document.getElementById('DOSEnable').checked){XHR.appendData('DOSEnable',1);}else{XHR.appendData('DOSEnable',0);}
		XHR.appendData('DOSHashTableSize',document.getElementById('DOSHashTableSize').value);
		XHR.appendData('DOSPageCount',document.getElementById('DOSPageCount').value);
		XHR.appendData('DOSSiteCount',document.getElementById('DOSSiteCount').value);
		XHR.appendData('DOSPageInterval',document.getElementById('DOSPageInterval').value);
		XHR.appendData('DOSSiteInterval',document.getElementById('DOSSiteInterval').value);
		XHR.appendData('DOSBlockingPeriod',document.getElementById('DOSBlockingPeriod').value);
		
		
		if(document.getElementById('ArticaHotSpotEnableMIT').checked){
			XHR.appendData('ArticaHotSpotEnableMIT',1);
		}else{
			XHR.appendData('ArticaHotSpotEnableMIT',0);
		}
		if(document.getElementById('HotSpotUseSSL').checked){
			XHR.appendData('HospotNoSSL',0);
		}else{
			XHR.appendData('HospotNoSSL',1);
		}
		if(document.getElementById('HotSpotDenySSL').checked){
			XHR.appendData('HotSpotDenySSL',1);
		}else{
			XHR.appendData('HotSpotDenySSL',0);
		}
		
		
		
		
		if(document.getElementById('ArticaHotSpotEnableProxy').checked){
			XHR.appendData('ArticaHotSpotEnableProxy',1);
		}else{
			XHR.appendData('ArticaHotSpotEnableProxy',0);
		}	
	
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot);
	}	

	
	
	function EvasiveDisabled(){
		document.getElementById('DOSHashTableSize').disabled=true;
		document.getElementById('DOSPageCount').disabled=true;
		document.getElementById('DOSSiteCount').disabled=true;
		document.getElementById('DOSPageInterval').disabled=true;
		document.getElementById('DOSSiteInterval').disabled=true;
		document.getElementById('DOSBlockingPeriod').disabled=true;
		
		var ApacheEvasiveInstalled=$ApacheEvasiveInstalled;
		if(ApacheEvasiveInstalled==0){
			document.getElementById('DOSEnable').checked=false;
			document.getElementById('DOSEnable').disabled=true;
			return;
		}
	
		if(document.getElementById('DOSEnable').checked){	
			document.getElementById('DOSHashTableSize').disabled=false;
			document.getElementById('DOSPageCount').disabled=false;
			document.getElementById('DOSSiteCount').disabled=false;
			document.getElementById('DOSPageInterval').disabled=false;
			document.getElementById('DOSSiteInterval').disabled=false;
			document.getElementById('DOSBlockingPeriod').disabled=false;	
			}		
	}	
	
EvasiveDisabled();	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableMaintenanceHotSpot2(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMaintenanceHotSpot", $_POST["EnableMaintenanceHotSpot2"]);
	
}

function SaveEnable(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaHotSpot", $_POST["EnableArticaHotSpot"]);
	$sock->SET_INFO("HotSpotArticaDebug", $_POST["HotSpotArticaDebug"]);
	$sock->SET_INFO("HotSpotWhiteWhatsApp", $_POST["HotSpotWhiteWhatsApp"]);
	$sock->SET_INFO("EnableMaintenanceHotSpot", $_POST["EnableMaintenanceHotSpot"]);
	 
	$sock->getFrameWork("hotspot.php?restart-wifidog=yes");
	$sock->getFrameWork("hotspot.php?restart-web=yes");
	if($_POST["EnableArticaHotSpot"]==0){
		$sock->getFrameWork("hotspot.php?stop-wifidog=yes");
		$sock->getFrameWork("hotspot.php?stop-web=yes");
	}
	sleep(3);
	
}


function EnableArticaHotSpot(){
	$sock=new sockets();
	$tpl=new templates();
	
	if( ($_POST["ArticaHotSpotPort"]==443) OR ($_POST["ArticaHotSpotPort"]==80) ){		
		echo "Cannot use 80/443 port\n";
		return;
	}
	
	if( ($_POST["ArticaSSLHotSpotPort"]==443) OR ($_POST["ArticaSSLHotSpotPort"]==80) ){
		echo "Cannot use 80/443 port\n";
		return;
	}

	if( ($_POST["ArticaSplashHotSpotPortSSL"]==443) OR ($_POST["ArticaSplashHotSpotPortSSL"]==80) ){
		echo "Cannot use 80/443 port\n";
		return;
	}	
	
	if( ($_POST["ArticaSplashHotSpotPort"]==443) OR ($_POST["ArticaSplashHotSpotPort"]==80) ){
		echo "Cannot use 80/443 port\n";
		return;
	}	
	
	if($_GET["HospotHTTPServerName"]<>null){
		$hostname=$sock->GET_INFO("myhostname");
		if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
		if(trim(strtolower($hostname))==trim(strtolower($_GET["HospotHTTPServerName"]))){
			$error=$tpl->javascript_parse_text("{ErrorVirtualhostnameHostname}",1);
			$error=str_replace("%a", $_GET["HospotHTTPServerName"],$error);
			echo $error;
			$_GET["HospotHTTPServerName"]=null;
		}
	}
	
	
	$sock->SET_INFO("HospotNoSSL", $_POST["HospotNoSSL"]);
	
	$sock->SET_INFO("SquidHotSpotPort", $_POST["SquidHotSpotPort"]);
	
	$sock->SET_INFO("ArticaHotSpotPort", $_POST["ArticaHotSpotPort"]);
	$sock->SET_INFO("ArticaSSLHotSpotPort", $_POST["ArticaSSLHotSpotPort"]);
	$sock->SET_INFO("ArticaHotSpotInterface", $_POST["ArticaHotSpotInterface"]);
	$sock->SET_INFO("ArticaSplashHotSpotPort", $_POST["ArticaSplashHotSpotPort"]);
	$sock->SET_INFO("ArticaSplashHotSpotPortSSL", $_POST["ArticaSplashHotSpotPortSSL"]);
	$sock->SET_INFO("ArticaSplashHotSpotTitle", $_POST["ArticaSplashHotSpotTitle"]);
	$sock->SET_INFO("ArticaSplashHotSpotCertificate", $_POST["ArticaSplashHotSpotCertificate"]);
	
	$sock->SET_INFO("WifiDogDebugLevel", $_POST["WifiDogDebugLevel"]);
	$sock->SET_INFO("ArticaHotSpotEnableMIT", $_POST["ArticaHotSpotEnableMIT"]);
	$sock->SET_INFO("ArticaHotSpotEnableProxy", $_POST["ArticaHotSpotEnableProxy"]);
	$sock->SET_INFO("ArticaHotSpotInterface2", $_POST["ArticaHotSpotInterface2"]);
	$sock->SET_INFO("ArticaHotSpotNowPassword", $_POST["ArticaHotSpotNowPassword"]);
	$sock->SET_INFO("WifidogClientTimeout", $_POST["WifidogClientTimeout"]);
	$sock->SET_INFO("HospotHTTPServerName", $_POST["HospotHTTPServerName"]);
	$sock->SET_INFO("HotSpotDenySSL", $_POST["HotSpotDenySSL"]);
	
	$sock->SaveConfigFile(serialize($_POST), "HotSpotEvasive");
	
	
}



function SaveOptions(){
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
}


function SaveConfig(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSplashScreen", $_POST["EnableSplashScreen"]);
	$sock->SET_INFO("SplashScreenURI", $_POST["SplashScreenURI"]);
	$sock->SET_INFO("EnableSplashScreenAsObject", $_POST["EnableSplashScreenAsObject"]);
	
	if(isset($_POST["PdnsHotSpot"])){
		$sock->SET_INFO("PdnsHotSpot", $_POST["PdnsHotSpot"]);
		if($_POST["PdnsHotSpot"]==1){$sock->SET_INFO("EnablePDNS", 1);}
		$sock->getFrameWork("cmd.php?pdns-restart=yes");
	}
	
	$sock->getFrameWork("squid.php?build-smooth=yes");
}
function radius_config(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$array=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
	if(!is_numeric($array["RAD_PORT"])){$array["RAD_PORT"]=1812;}
	
	
	
	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:18px'>{radius_server}:</td>
	<td>". Field_text("RAD_SERVER-$tt",$array["RAD_SERVER"],"font-size:18px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{listen_port}:</td>
		<td>". Field_text("RAD_PORT-$tt",$array["RAD_PORT"],"font-size:18px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>". Field_password("RAD_PASSWORD-$tt",$array["RAD_PASSWORD"],"font-size:18px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$tt()","18px")."</td>
					</tr>
					</table>
					<script>
					var x_Save$tt= function (obj) {
					var results=obj.responseText;
					if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
					document.getElementById('$tt').innerHTML='';
					YahooWin3Hide();
	}
	
					function Save$tt(){
					var XHR = new XHRConnection();
					XHR.appendData('RAD_SERVER', document.getElementById('RAD_SERVER-$tt').value);
					XHR.appendData('RAD_PORT', document.getElementById('RAD_PORT-$tt').value);
					XHR.appendData('RAD_PASSWORD', encodeURIComponent(document.getElementById('RAD_PASSWORD-$tt').value));
					AnimateDiv('$tt');
					XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
	</script>";
	
		echo $tpl->_ENGINE_parse_body($html);
}

function services_status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$q=new mysql_squid_builder();
	$data=base64_decode($sock->getFrameWork("hotspot.php?services-status=yes"));
	$ARRAY=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
	$genrate=$q->time_to_date($ARRAY["TIME"],true) ;
	$uptime=$ARRAY["UPTIME"];
	
	$ini->loadString($data);
	$f[]="<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{uptime}:</td>
		<td><strong style='font-size:16px' nowrap>$uptime</strong>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px' nowrap>{sessions}:</td>
		<td><strong style='font-size:16px'>{$ARRAY["CLIENTS"]}</strong>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{generated_on}:</td>
		<td><strong style='font-size:16px' nowrap>$genrate</strong>
	</tr>		
	</table><hr>		
	";
	$f[]=DAEMON_STATUS_ROUND("HOTSPOT_WWW", $ini);
	$f[]=DAEMON_STATUS_ROUND("HOTSPOT_SERVICE", $ini);
	$f[]="<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('hostspot-status','$page?hostspot-status=yes');")."</div>";
	$f[]="<center style='margin:10px'>".button("{maintenance}","Loadjs('squid.webauth.maintenance.php');",18)."</center>";
	
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $f));
	
}

function download_debug(){
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?compress-debug=yes");
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/hotspot.debug.gz")){
		$size=@filesize("/usr/share/artica-postfix/ressources/logs/web/hotspot.debug.gz");
		$size=FormatBytes($size/1024);
		echo "
			<center style='margin:60px'>
				<a href=\"ressources/logs/web/hotspot.debug.gz\">
				<img src='img/download-64.png'>
				<center style='margin-top:15px;font-size:22px;color:black;font-weigth:bold'>hotspot.debug.gz ($size)</center>
				</a>
			</center>	
				
				
			";
		
		return;
	}
	
	echo FATAL_ERROR_SHOW_128("{compress} {failed}");
	
}


