<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;include_once("ressources/logs.inc");}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.kav4proxy.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["callback-enabled"])){callback_enabled_js();exit;}
	if(isset($_GET["frontend-status"])){frontend_status_js();exit;}
	if(isset($_GET["frontend-params"])){frontend_params_js();exit;}
	if(isset($_GET["frontend-groups"])){frontend_groups_js();exit;}
	if(isset($_GET["frontend-stats"])){frontend_stats_js();exit;}
	if(isset($_GET["frontend-tasks"])){frontend_tasks_js();exit;}
	if(isset($_GET["frontend-blacklists"])){frontend_blacklists_js();exit;}
	if(isset($_GET["popup-big"])){tabs();exit;}
	if(isset($_GET["service-cmds"])){kav_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){kav_cmds_popup();exit;}
	if(isset($_GET["service-cmds-logs"])){kav_cmds_logs();exit;}
	if(isset($_GET["frontend-excludemime"])){frontend_excludemime_js();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["ExcludeMimeType"])){ExcludeMimeType();exit;}
	if(isset($_GET["MimeTypeList"])){ExcludeMimeType_list();exit;}
	if(isset($_GET["MimeTypeToAdd"])){ExcludeMimeType_add();exit;}
	if(isset($_GET["KavProxyDeleteLine"])){KavProxyDeleteLine();exit;}
	if(isset($_GET["icapserver_engine_options"])){icapserver_engine_options();exit;}
	if(isset($_GET["MaxChildren"])){icapserver_engine_options_save();exit;}
	if(isset($_GET["inline"])){js();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["kav4proxy-status"])){kav4proxy_status();exit;}
	if(isset($_GET["Kav4EULA"])){Kav4EULA();exit;}
	if(isset($_POST["AcceptEULA"])){Kav4EULASave();exit;}
	if(isset($_POST["kavicapserverEnabled"])){kavicapserverEnabledSave();exit;}
js();


function frontend_status_js(){$page=CurrentPageName();echo "$('#BodyContent').load('$page?status=yes');";}
function frontend_params_js(){$page=CurrentPageName();echo "$('#BodyContent').load('$page?icapserver_engine_options=yes');";}
function frontend_groups_js(){$page=CurrentPageName();echo "$('#BodyContent').load('Kav4Proxy.Groups.php');";}
function frontend_stats_js(){$page=CurrentPageName();echo "$('#BodyContent').load('Kav4Proxy.statistics.php');";}
function frontend_tasks_js(){$page=CurrentPageName();echo "$('#BodyContent').load('Kav4Proxy.Tasks.php');";}
function frontend_excludemime_js(){$page=CurrentPageName();echo "$('#BodyContent').load('$page?ExcludeMimeType=yes');";}
function frontend_blacklists_js(){$page=CurrentPageName();echo "$('#BodyContent').load('squid.blacklist.php');";}

function kav_cmds_js(){
	$page=CurrentPageName();
	$cmd=$_GET["service-cmds"];
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','Service::$cmd');";
	echo $html;	
}

function callback_enabled_js(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($kavicapserverEnabled==0){
		echo "Loadjs('kav4Proxy.disable.progress.php');";
		return;
	}
	echo "Loadjs('kav4Proxy.enable.progress.php');";
}


function kav4proxy_status(){
	$ini=new Bs_IniHandler();
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$kav=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,0);
	$Keep=DAEMON_STATUS_ROUND("KAV4PROXY_KEEPUP2DATE",$ini,null,0);
	
	
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('services.php?Update-Utility-status=yes')));
	$UpdateUtility=DAEMON_STATUS_ROUND("APP_UPDATEUTILITYRUN",$ini,null).
	

	
	$Kav4ProxyLicenseRead=$sock->GET_INFO("Kav4ProxyLicenseRead");
	if(!is_numeric($Kav4ProxyLicenseRead)){$Kav4ProxyLicenseRead=0;}
	
	$pattern_date=base64_decode($sock->getFrameWork("kav4proxy.php?pattern-date=yes"));
	$pattern_date_org=$pattern_date;
	if($pattern_date==null){
		$pattern_date="<strong style='font-size:18px;color:#C61010'>{av_pattern_database_obsolete_or_missing}</strong>";}
	else{
		$day=substr($pattern_date, 0,2);
		$month=substr($pattern_date, 2,2);
		$year=substr($pattern_date, 4,4);
		$re=explode(";",$pattern_date_org);
		$time=$re[1];
		$H=substr($time, 0,2);
		$M=substr($time, 2,2);
		$pattern_date="$year/$month/$day $H:$M:00";	
	}
	
	
	$iconupdate="arrow-right-24.png";
	$updatejs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:UpdateKav4Proxy$t();\"
	style='font-size:18px;text-decoration:underline'>";
	
	if($ini->_params["KAV4PROXY_KEEPUP2DATE"]["running"]==1){
		$iconupdate="preloader.gif";
		$updatejs="<span style='font-size:18px;'>";
		$pattern_date="{downloading}";
	}	
	$expire=$tpl->_ENGINE_parse_body("{expire}");
	$q=new mysql();
	$sql="SELECT *  FROM kav4proxy_license ORDER BY expiredate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(trim($ligne["serial"])<>null){
		$expiredate_color="black";
		$expiredate=$ligne["expiredate"];
		$expiredate=strtotime($expiredate);
		
		if($expiredate-time()<0){$expiredate_color="#B60000";}
		if($tpl->language=="fr"){
			$expiredate=date("Y l F d",$expiredate);
		}else{
			$expiredate=date("{l} d {F} Y",$expiredate);
		}
		$license_text="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('Kav4Proxy.License-infos.php');\"
			style='font-size:18px;font-weight:bold;color:$expiredate_color;text-decoration:underline'>$expire:$expiredate</strong>";		
	}else{
		$sock->getFrameWork("squid.php?kav4proxy-license-generate=yes");
		$licenseerror=base64_decode($sock->getFrameWork("squid.php?kav4proxy-license-error=yes"));
		if($licenseerror<>null){
			$license_text="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('Kav4Proxy.License.php');\"
			style='font-size:18px;font-weight:bold;color:#C61010;text-decoration:underline'>$licenseerror</strong>";
		}
	}
	
	
	
	$sql="SELECT * FROM kav4proxy_av_stats ORDER BY zDate DESC LIMIT 0,1";
	$q=new mysql();
	$ligne_query=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$fields[]="total_requests";
	$fields[]="infected_requests";
	$fields[]="protected_requests";
	$fields[]="error_requests";
	$fields[]="requests_per_min";
	$fields[]="processed_traffic";
	$fields[]="clean_traffic";
	$fields[]="infected_traffic";
	$fields[]="traffic_per_min";
	$fields[]="engine_errors";
	$fields[]="total_connections";
	$fields[]="total_processes";
	$fields[]="idle_processes";

	$link["infected_requests"]=true;
	$link["error_requests"]=true;
	$link["protected_requests"]=true;
	$link["error_requests"]=true;
	
	
	if($ligne_query["zDate"]<>null){
		
		while (list ($num, $ligne) = each ($fields) ){
			
			$jsa=null;
			$jsb=null;
			
	
			
			
			
		$status[]="
		<tr>
			<td class=legend style='font-size:18px' nowrap>{kav4_$ligne}:</td>
			<td style='font-size:18px'>$jsa{$ligne_query[$ligne]}$jsb</td>
			<td width=1%>". help_icon("{kav4_{$ligne}_text}")."</td>
		</tr>";	 	 	 	 	 	 	 	 	 	 	 	
		}
		
	}
	
	if(is_array($status)){$status_text=@implode("\n", $status);}
	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($kavicapserverEnabled==1){
		$q=new mysql_squid_builder();
		$ligneSQL=mysql_fetch_array($q->QUERY_SQL("SELECT `enabled` FROM c_icap_services WHERE ID=6"));
		if($ligneSQL["enabled"]==0){
			$CICAP_LOCAL_WARNING="<center>".Paragraphe("warning-panneau-64.png", "{local_proxy_service_not_linked}",
					"{local_proxy_service_not_linked_explain}",
					"javascript:Loadjs('kav4Proxy.enable.progress.php')",null,350)."</center>";
		}
	
	}
	$DEFAULT_SIZE=133602007;
	
	
	
	$html="$CICAP_LOCAL_WARNING$kav$Keep$UpdateUtility

	

	<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","Kav4ProxyStatus()")."</div>
	<br>
	<table class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:18px' nowrap>{pattern_date}:</td>
			<td style='font-size:18px' colspan=2>$pattern_date</td>
		</tr>
		
		
		
		
		<tr>
			<td width=1% align='right'><img src='img/$iconupdate'></td>
			<td nowrap>$updatejs{TASK_UPDATE_ANTIVIRUS}</a></span></td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{license2}:</td>
			<td style='font-size:18px' colspan=2>$license_text</td>
		</tr>	
		<tr>
			<td width=1% align='right'><img src='img/arrow-right-16.png'></td>
			<td style='font-size:18px' colspan=2><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('Kav4Proxy.install.php')\"
			style='font-size:18px;font-weight:bold;color:black;text-decoration:underline'>{uninstall}</td>
		</tr>							
		$status_text
	</tbody>
	</table>
	<script>
		function Kav4EULA(){
			var Kav4ProxyLicenseRead=$Kav4ProxyLicenseRead;
			if(Kav4ProxyLicenseRead==0){
				YahooWin(680,'$page?Kav4EULA=yes','License...',true,'top');
			}
		
		}
		
	var x_UpdateKav4Proxy$t= function (obj) {
	      var results=obj.responseText;
	      alert(results);
	}	

	function UpdateKav4Proxy$t(){
			var XHR = new XHRConnection();
			XHR.appendData('update-kav4proxy','yes');
			XHR.sendAndLoad('Kav4Proxy.Tasks.php', 'POST',x_UpdateKav4Proxy$t);	
	}		
		
		
	Kav4EULA();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function kav_cmds_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock->getFrameWork("services.php?kav4proxy-service-cmds={$_GET["service-cmds-peform"]}");
	$t=time();
	

	$html="<div style='width:100%;height:350px;min-height:350px;overflow:auto' id='service-$t'>
	
		<center style='margin:50px'><img src='img/loadingAnimation.gif'>
		<div style='font-size:28px'>{$_GET["service-cmds-peform"]}</div>
		</center></div>
	<script>
		function kav_cmds_popup_refresh(){
			LoadAjax('service-$t','$page?service-cmds-logs=yes');
		
		}
	
		setTimeout('kav_cmds_popup_refresh()',10000);
	</script>
	";
	
	echo $html;
}

function kav_cmds_logs(){
	$tpl=new templates();
	$datas=service_logs_to_table("ressources/logs/web/kav4proxy.services.txt");
$html="
<div>
$datas
</div>
<center style='margin:5px'><img src='img/loadingAnimation.gif'></center>
<script>
	if(YahooWin4Open()){
		setTimeout('kav_cmds_popup_refresh()',10000);
		Kav4ProxyStatus();
	}
</script>

";		
	
echo $tpl->_ENGINE_parse_body($html);
}



function status(){
		$tpl=new templates();
		$page=CurrentPageName();
		$sock=new sockets();
		
		
		$INSTALLED=trim($sock->getFrameWork("squid.php?kaspersky-is-installed=yes"));
		if($INSTALLED<>"TRUE"){
			echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{not_installed}"));
			return;
		}
		
		
		$t=time();
		$users=new usersMenus();
		$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
		
		
		$form=$tpl->_ENGINE_parse_body(
		Paragraphe_switch_img("{ACTIVATEANTIVIRUSSERVICE}", "{ACTIVATEANTIVIRUSWSERVICETEXT}",
		"kavicapserverEnabled-$t",$kavicapserverEnabled,null,890));
		
		
		$KAV4PROXY_VERSION=$users->KAV4PROXY_VERSION;
		if(preg_match("#([0-9])\.([0-9]).([0-9]+)#",  $KAV4PROXY_VERSION,$re)){
			$rev=$re[3];
		}
		$MainV="Kaspersky For Proxy Server version $KAV4PROXY_VERSION (revision $rev)";
		
	$html="
	<div style='font-size:30px;text-align:right;margin-bottom:10px'>$MainV</div>
	<table style='width:100%'>
	<tbody>
		<tr>
			<td width=600px valign='top'><div id='kav4proxy-status'></div></td>
			<td width=1% valign='top'>
			<div id='$t-div'>
				<div style='width:98%' class=form>
					$form
					<div style='width:100%;text-align:right'>". button("{apply}","kavicapserverEnabledSave$t()",26)."</div>
				</div>
			
				<center style='margin-top:20px'><img src=img/kaspersky-logo-250.png></center>
				<div class=explain style='font-size:18px'>{kav4proxy_about}</div></td>
			</div>
		</tr>
	</tbody>
	</table>
	
	<script>
		function Kav4ProxyStatus(){
			LoadAjax('kav4proxy-status','$page?kav4proxy-status=yes');
		
		}
		
		
	var X_kavicapserverEnabledSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		Loadjs('$page?callback-enabled=yes');
		}		
		
	function kavicapserverEnabledSave$t(){
		var enabled=document.getElementById('kavicapserverEnabled-$t').value;
		var XHR = new XHRConnection();
		XHR.appendData('kavicapserverEnabled',enabled);
		XHR.sendAndLoad('$page', 'POST',X_kavicapserverEnabledSave$t);     		
	}
		
		
		
	Kav4ProxyStatus();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function kavicapserverEnabledSave(){
	
	$sock=new sockets();
	$sock->SET_INFO("kavicapserverEnabled", $_POST["kavicapserverEnabled"]);
	
	
}



function tabs(){
		$font_size=$_GET["font-size"];
		if($font_size==null){$font_size="16px";}
		$tpl=new templates();
		$page=CurrentPageName();
		$users=new usersMenus();
		$sock=new sockets();
		if($sock->getFrameWork("kav4proxy.php?is-installed=yes")=="NO"){
		
			echo "<div id='KasperskyPromo022014'></div>
			<script>
					LoadAjax('KasperskyPromo022014','artica.pubs.php?KasperskyPromo022014=yes');
			</script>	
			";
			return;
			
		
		}
		
		
		$sock=new sockets();
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}		
		
		$array["status"]='{status}';
		$array["events"]='{events}';
		$array["icapserver_engine_options"]='{icapserver_1}';
		
		
		
		$array["ExcludeMimeType"]='{exclude}:{ExcludeMimeType}';
		//$array["groups"]='{groups}';
		
		if($users->UPDATE_UTILITYV2_INSTALLED){
			$array["updateutility"]='UpdateUtility';
		}else{
			$array["KeepUp2date"]='{update_tasks}';
			
		}	

		

		
		if($SQUIDEnable==0){	
			$array["blacklist_databases"]='{blacklist_databases}';
		}
		
		
		$font_size="22px";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="blacklist_databases"){
			$tab[]="<li><a href=\"squid.blacklist.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="KeepUp2date"){
			$tab[]="<li><a href=\"squid.databases.schedules.php?TaskType=12\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		if($num=="tasks"){
			$tab[]="<li><a href=\"Kav4Proxy.Tasks.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="groups"){
			$tab[]="<li><a href=\"Kav4Proxy.Groups.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}	
		
		if($num=="updateutility"){
			$tab[]="<li><a href=\"UpdateUtility.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}		
		

		if($num=="statistics"){
			$tab[]="<li><a href=\"squid.blocked.statistics.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}

	if($num=="events"){
			$tab[]="<li><a href=\"Kav4Proxy.events.php\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}			
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			
		}
	
	
	
	echo $tpl->_ENGINE_parse_body(build_artica_tabs($tab, "main_kav4proxy_config",1490));
	

}




function js(){
	$Kav4Proxyload="Kav4Proxyload()";
	
if(isset($_GET["js-popup"])){$Kav4Proxyload="LoadBigSettings()";}
if(isset($_GET["inline"])){
	$Kav4Proxyload="Kav4ProxyloadInLIne('{$_GET["font-size"]}')";
	$prefix="<div id='Kav4Proxy-div'>
	</div>
	<script>
	
	";
	$suffix="</script>";
}	
$page=CurrentPageName();
$tpl=new templates();
$icapserver_1=$tpl->_ENGINE_parse_body("{icapserver_1}","kav4proxy.index.php");
$title=$tpl->_ENGINE_parse_body("{web_proxy}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{APP_KAV4PROXY}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{parameters}");
$title2=$title."&nbsp;&nbsp;&raquo;&raquo;&nbsp;".$tpl->_ENGINE_parse_body("{exclude}:{ExcludeMimeType}");

$html="
	$prefix
	
	function LoadBigSettings(){
		YahooWin('930','$page?popup-big=yes','$title');
	
	}
	
	function Kav4Proxyload(){
		YahooWin('550','$page?popup=yes','$title');
	}	
	
	function ExcludeMimeTypePopUp(){
		YahooWin2('600','$page?ExcludeMimeType=yes','$title2');
	}

	function icapserver_engine_options(){
		YahooWin2('350','$page?icapserver_engine_options=yes','$icapserver_1');
	}

	function ExcludeMimeTypeAddEnter(e){
		if(!checkEnter(e)){return;}
		ExcludeMimeTypeAdd();
	}

      



function ExcludeMimeTypeRefreshList(){
	LoadAjax('ExcludeMimeTypediv','$page?MimeTypeList=yes');
}


function Kav4ProxyloadInLIne(fontsize){
	LoadAjax('Kav4Proxy-div','$page?tabs=yes&font-size='+fontsize);
}

	$Kav4Proxyload;
	$suffix";
	
echo $html;	
	
}

function popup(){
	
	$html="
	<table style='width=100%'>
	<tr>
		<td valign='top'>". Paragraphe("good-files-64.png","{exclude}:{ExcludeMimeType}","{ExcludeMimeType_text}","javascript:ExcludeMimeTypePopUp()")."</td>
		<td valign='top'>". Paragraphe("kav4proxy-settings-64.png","{icapserver_1}","{kav4proxyprocess_explain}","javascript:icapserver_engine_options()")."</td>
	</tr>
	</table>
	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,"kav4proxy.index.php");	
	
	
}

function ExcludeMimeType(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	
	$INSTALLED=trim($sock->getFrameWork("squid.php?kaspersky-is-installed=yes"));
	if($INSTALLED<>"TRUE"){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{not_installed}"));
		return;
	}
	
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$groups=$tpl->_ENGINE_parse_body("{groups}:{ComputerMacAddress}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$mimetype=$tpl->_ENGINE_parse_body("{ExcludeMimeType}");
	$add_mime=$tpl->javascript_parse_text("{add}: {ExcludeMimeType}");
	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var md$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?MimeTypeList=yes',
	dataType: 'json',
	colModel : [
		{display: '$mimetype', name : 'data', width :758, sortable : true, align: 'left'},
		{display: '$delete', name : 'country', width : 70, sortable : false, align: 'center'},
		

		],
		
buttons : [
		{name: '$mimetype', bclass: 'add', onpress : NewMimeType},
		],			
	
	searchitems : [
		{display: '$mimetype', name : 'data'},
		],
	sortname: 'data',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


	var x_ExcludeMimeTypeAdd=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
    	$('#flexRT$t').flexReload();	
	}

function NewMimeType(){
	var mime=prompt('$add_mime');
	if(!mime){return;}
	var XHR = new XHRConnection();
	XHR.appendData('MimeTypeToAdd',mime);
	XHR.sendAndLoad('$page', 'GET',x_ExcludeMimeTypeAdd);	
	
	
}

var x_KavProxyDeleteExcludeMimeType=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
    $('#row'+md$t).remove();	
}

function KavProxyDeleteExcludeMimeType(id,md){
	md$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('KavProxyDeleteLine',id);
	XHR.sendAndLoad('$page', 'GET',x_KavProxyDeleteExcludeMimeType);
}
</script>
	
	
	";
	
	echo $html;
}

function ExcludeMimeType_add(){
	$kav=new Kav4Proxy();
	$kav->SET("icapserver.filter","ExcludeMimeType",$_GET["MimeTypeToAdd"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");
	
}
function KavProxyDeleteLine(){
	$kav=new Kav4Proxy();
	$sql="DELETE FROM `artica_backup`.`kav4Proxy` WHERE `kav4Proxy`.`ID` ={$_GET["KavProxyDeleteLine"]}";
	
	$kav->q->QUERY_SQL($sql,"artica_backup");
	if(!$kav->q->ok){
		echo $sql."\n".$kav->q->mysql_error;
		return;
	}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");
	//--reload-kav4proxy
}

if(isset($_GET["KavProxyDeleteLine"])){KavProxyDeleteLine();exit;}




function ExcludeMimeType_list(){
	
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$database="artica_backup";		
	$q=new mysql();	
	$t=time();
	$fontsize=13;
	$table="(SELECT ID,data FROM kav4Proxy WHERE `key`='icapserver.filter' AND `value`='ExcludeMimeType') as t";
	$search='%';
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$FORCE_FILTER=null;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$QUERY=string_to_flexquery($_POST["query"]);

	if($QUERY<>null){
		$search=$QUERY;
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $search $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER ";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $QUERY $FORCE_FILTER $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	

	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"$q->mysql_error", "",""));echo json_encode($data);return;}	
	if(mysql_num_rows($results)==0){array('id' => $ligne[time()],'cell' => array(null,"", "",""));echo json_encode($data);return;}
	
	$data['total'] = mysql_num_rows($results);
	$style="style='font-size:16px'";
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=md5($ligne["ID"]);
		$enabled=0;
 		$sql="SELECT ID FROM webfilters_sqitems WHERE `pattern`='{$_GET["MAC"]}' AND enabled=1 AND gpid='{$ligne["ID"]}'";
 		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne2>0){$enabled=1;}
		$delete=imgsimple("delete-32.png","{delete}","KavProxyDeleteExcludeMimeType({$ligne["ID"]},'$id')");
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>{$ligne["data"]}</span>",
			"<span $style>$delete</span>",

			)
			);		
		
		
	}

echo json_encode($data);	
		
}

function icapserver_engine_options(){
$page=CurrentPageName();

$sock=new sockets();
$tpl=new templates();
$INSTALLED=trim($sock->getFrameWork("squid.php?kaspersky-is-installed=yes"));
if($INSTALLED<>"TRUE"){
	echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{not_installed}"));
	return;
}

$kav4=new Kav4Proxy();

include_once(dirname(__FILE__)."/ressources/system.network.inc");
$ip=new networking();
$ips=$ip->ALL_IPS_GET_ARRAY();
$ips["0.0.0.0"]="{all}";
$sock=new sockets();
$Kav4ProxyTMPFS=$sock->GET_INFO("Kav4ProxyTMPFS");
if(!is_numeric($Kav4ProxyTMPFS)){$Kav4ProxyTMPFS=0;}
$Kav4ProxyTMPFSMB=$sock->GET_INFO("Kav4ProxyTMPFSMB");
if(!is_numeric($Kav4ProxyTMPFSMB)){$Kav4ProxyTMPFSMB=512;}

if(preg_match("#(.+?):[0-9]+#", $kav4->main_array["ListenAddress"],$re)){$kav4->main_array["ListenAddress"]=$re[1];}
	$license=Paragraphe("64-kav-license.png", "{license_info}", "{license_info_text}","javascript:Loadjs('Kav4Proxy.License-infos.php')");
	$update_kaspersky=Paragraphe('kaspersky-update-64.png','{TASK_UPDATE_ANTIVIRUS}','{APP_KAV4PROXY}<br>{UPDATE_ANTIVIRUS_TEXT}',
	"javascript:UpdateKav4Proxy()");
	
	$templates=Paragraphe('squid-templates-64.png','{squid_templates_error}','{APP_KAV4PROXY_TEMPLATES}',"javascript:Loadjs('kav4proxy.templates.php')");
	
	$KAV4PROXY_VERSION=$kav4->VERSION;
	if(preg_match("#([0-9])\.([0-9]).([0-9]+)#",  $KAV4PROXY_VERSION,$re)){$rev=$re[3];}
	
	$Rev88Opts["MaxNestingLevel"]=8;
	$Rev88Opts["DetectVirware"]=1;
	$Rev88Opts["DetectTrojware"]=1;
	$Rev88Opts["DetectMalware"]=1;
	$Rev88Opts["DetectAdware"]=1;
	$Rev88Opts["DetectPornware"]=1;
	$Rev88Opts["DetectRiskware"]=1;
	$Rev88Opts["DetectXfiles"]=0;
	$Rev88Opts["DetectSoftware"]=0;
	$Rev88TRJS=array();
	$Rev88TR=array();
	$TypesFields["MaxNestingLevel"]="F";
	
	if($rev>87){
		while (list ($fieldname, $default) = each ($Rev88Opts) ){
			if(!isset($kav4->main_array[$fieldname])){$kav4->main_array[$fieldname]=$default;}
			if(!isset($TypesFields[$fieldname])){
				$Rev88TR[]="<tr>
					<td align='right' style='font-size:18px' class=legend>{{$fieldname}}:</strong></td>
					<td>". Field_checkbox("$fieldname", 1,$kav4->main_array[$fieldname])."</td>
					<td>&nbsp;</td>
				</tr>";
				
				$Rev88TRJS[]="if(document.getElementById('$fieldname').checked){XHR.appendData('$fieldname',1);}else{XHR.appendData('$fieldname',0);}";
			}else{
				if($TypesFields[$fieldname]=="F"){
					
					$Rev88TR[]="<tr>
					<td align='right' style='font-size:18px' class=legend>{{$fieldname}}:</strong></td>
					<td align='left'>" . Field_text($fieldname,$kav4->main_array[$fieldname],'width:110px;font-size:18px')."</td>
					<td>&nbsp;</td>
					</tr>";	

					$Rev88TRJS[]="XHR.appendData('$fieldname',document.getElementById('$fieldname').value);";
				}
			}
			
		}
		
		
	}
	
	



$html=" 
<div id='icapserver_engine_options'>
<center>
<table style='width:100%'>
<tr>
<td width=1% valign='top'>
$license
$update_kaspersky
$templates
</td>
<td width=99% valign='top'>
			<div style='width:98%' class=form>
				<table style='width:100%'>
				<tbody>
				<tr>
					<td align='right' style='font-size:18px' class=legend>{ListenAddress}:</strong></td>
					<td align='left' style='font-size:18px'>" . Field_array_Hash($ips, 'ListenAddress',$kav4->main_array["ListenAddress"],'style:font-size:18px')."&nbsp;:1344</td>
					<td align='left'>&nbsp;</td>
				</tr>				
				<tr>
					<td align='right' style='font-size:18px' class=legend>{MaxChildren}:</strong></td>
					<td align='left'>" . Field_text('MaxChildren',$kav4->main_array["MaxChildren"],'width:110px;font-size:18px')."</td>
					<td align='left'>" . help_icon('{MaxChildren_text}',false,'milter.index.php') . "</td>
				</tr>
				<tr>
				<td align='right' style='font-size:18px' class=legend>{IdleChildren}:</strong></td>
				<td align='left'>" . Field_text('IdleChildren',$kav4->main_array["IdleChildren"],'width:110px;font-size:18px')."</td>
				<td align='left'>" . help_icon('{IdleChildren_text}',false,'milter.index.php') . "</td>
				</tr>
				<tr>
				<td align='right' style='font-size:18px' class=legend>{MaxReqsPerChild}:</strong></td>
				<td align='left'>" . Field_text('MaxReqsPerChild',$kav4->main_array["MaxReqsPerChild"],'width:110px;font-size:18px')."</td>
				<td align='left'>" . help_icon('{MaxReqsPerChild_text}',false,'milter.index.php') . "</td>
				</tr>	
				<tr>
				<td align='right' style='font-size:18px' class=legend>{MaxEnginesPerChild}:</strong></td>
				<td align='left'>" . Field_text('MaxEnginesPerChild',$kav4->main_array["MaxEnginesPerChild"],'width:110px;font-size:18px')."</td>
				<td align='left'>" . help_icon('{MaxEnginesPerChild_text}',false,'milter.index.php') . "</td>
				<tr>
				<tr>
				<td align='right' style='font-size:18px' class=legend>{PreviewSize}:</strong></td>
				<td align='left'>" . Field_text('PreviewSize',$kav4->main_array["PreviewSize"],'width:110px;font-size:18px')."</td>
				<td align='left'>" . help_icon('{PreviewSize_text}',false,'milter.index.php') . "</td>
				<tr>
				<tr>
				<td align='right' style='font-size:18px' class=legend>{MaxReqLength}:</strong></td>
				<td align='left' style='font-size:18px'>" . Field_text('MaxReqLength',$kav4->main_array["MaxReqLength"],'width:110px;font-size:18px')."&nbsp;Bytes</td>
				<td align='left'>" . help_icon('{MaxReqLength_text}',false,'milter.index.php') . "</td>
				<tr>
						".@implode("\n", $Rev88TR)."
				<tr>
					<td colspan=3 style='font-size:22px;font-weight:bold;padding-top:15px;padding-bottom:15px'>{memory_scanning}</td>
				</tr>
				<tr>
					<td align='right' style='font-size:18px' class=legend>{enable_memory_scanning}:</strong></td>
					<td>". Field_checkbox("Kav4ProxyTMPFS", 1,$Kav4ProxyTMPFS,"Kav4ProxyTMPFSMBCheck()")."</td>
					<td>" . help_icon('{Kav4ProxyTMPFS_explain}') . "</td>
				</tr>
				<tr>
				<td align='right' style='font-size:18px' class=legend>{memory_size}:</strong></td>
				<td align='left' style='font-size:18px'>" . Field_text('Kav4ProxyTMPFSMB',$Kav4ProxyTMPFSMB,'width:110px;font-size:18px')."&nbsp;MB</td>
				<td align='left'>" . help_icon('{Kav4ProxyTMPFS_explain}') . "</td>
				<tr>				

				
				
					<td colspan=3 align='right'>
						<hr>
						". button("{apply}","icapserver_engine_options_save()","26")."</td>
				</tr>
				</tbody>
				</table>
			</div>
</td>
</tr>
</tbody>
</table>
				</center>
			</div>
		<script>
var x_icapserver_engine_options_save= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
    	YahooWin2Hide();
    	if(document.getElementById('main_kav4proxy_config')){RefreshTab('main_kav4proxy_config');}
	}

	function Kav4ProxyTMPFSMBCheck(){
		document.getElementById('Kav4ProxyTMPFSMB').disabled=true;
		if(document.getElementById('Kav4ProxyTMPFS').checked){
			document.getElementById('Kav4ProxyTMPFSMB').disabled=false;
		}
	
	}


function icapserver_engine_options_save(){
		var XHR = new XHRConnection();
		XHR.appendData('MaxChildren',document.getElementById('MaxChildren').value);
		XHR.appendData('IdleChildren',document.getElementById('IdleChildren').value);
		XHR.appendData('MaxReqsPerChild',document.getElementById('MaxReqsPerChild').value);
		XHR.appendData('PreviewSize',document.getElementById('PreviewSize').value);
		XHR.appendData('MaxReqLength',document.getElementById('MaxReqLength').value);
		XHR.appendData('MaxEnginesPerChild',document.getElementById('MaxEnginesPerChild').value);
		XHR.appendData('ListenAddress',document.getElementById('ListenAddress').value);
		XHR.appendData('Kav4ProxyTMPFSMB',document.getElementById('Kav4ProxyTMPFSMB').value);
		if(document.getElementById('Kav4ProxyTMPFS').checked){XHR.appendData('Kav4ProxyTMPFS',1);}else{XHR.appendData('Kav4ProxyTMPFS',0);}
		".@implode("\n", $Rev88TRJS)."
		AnimateDiv('icapserver_engine_options');
		XHR.sendAndLoad('$page', 'GET',x_icapserver_engine_options_save);
}

	var x_UpdateKav4Proxy= function (obj) {
	      var results=obj.responseText;
	      alert(results);
	      LoadAjax('kav4proxy-status','$page?kav4proxy-status=yes');
	}	

	function UpdateKav4Proxy(){
			var XHR = new XHRConnection();
			XHR.appendData('update-kav4proxy','yes');
			XHR.sendAndLoad('Kav4Proxy.Tasks.php', 'POST',x_UpdateKav4Proxy);	
	}

Kav4ProxyTMPFSMBCheck();
</script>

			
			
			";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}
function icapserver_engine_options_save(){
		$kav=new Kav4Proxy();
		$sock=new sockets();
		$sock->SET_INFO("Kav4ProxyTMPFS", $_GET["Kav4ProxyTMPFS"]);
		$sock->SET_INFO("Kav4ProxyTMPFSMB", $_GET["Kav4ProxyTMPFSMB"]);
		
		$Rev88Opts["MaxNestingLevel"]=8;
		$Rev88Opts["DetectVirware"]=1;
		$Rev88Opts["DetectTrojware"]=1;
		$Rev88Opts["DetectMalware"]=1;
		$Rev88Opts["DetectAdware"]=1;
		$Rev88Opts["DetectPornware"]=1;
		$Rev88Opts["DetectRiskware"]=1;
		$Rev88Opts["DetectXfiles"]=0;
		$Rev88Opts["DetectSoftware"]=0;
		
		while (list ($fieldname, $default) = each ($Rev88Opts) ){
			if(isset($_GET[$fieldname])){
				$kav->MOD("icapserver.engine.options",$fieldname,$_GET[$fieldname]);
			}
		}
		
		
		$kav->MOD("icapserver.filter","MaxReqLength",$_GET["MaxReqLength"]);		
		$kav->MOD("icapserver.protocol","PreviewSize",$_GET["PreviewSize"]);
		$kav->MOD("icapserver.process","MaxChildren",$_GET["MaxChildren"]);
		$kav->MOD("icapserver.process","IdleChildren",$_GET["IdleChildren"]);
		$kav->MOD("icapserver.process","MaxReqsPerChild",$_GET["MaxReqsPerChild"]);
		$kav->MOD("icapserver.process","MaxEnginesPerChild",$_GET["MaxEnginesPerChild"]);
		$kav->MOD("icapserver.network","ListenAddress","{$_GET["ListenAddress"]}:1344");
		
		

		
		
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");		
}

function Kav4EULA(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$language=$tpl->language;
	$dataf="ressources/databases/kav4license-$language-license.txt";
	if(!is_file($dataf)){$dataf="ressources/databases/kav4license-en-license.txt";}
	
	$html="<center style='margin:20px' id='kavlogoforanimate'><imf src='img/kaspersky-logo-250.png'></center>
	
	<div style='width:100%;text-align:right'>". button("{i_accept}","AcceptEULA()")."</div>
	<textarea style='width:100%;height:450px;overflow:auto;border:0px;font-size:13px'>".@file_get_contents($dataf)."</textarea>
	
	
	<script>
	var x_AcceptEULA= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;};
    	YahooWinHide();
	}		
	
	
	function AcceptEULA(){
		var XHR = new XHRConnection();
		XHR.appendData('AcceptEULA','1');
		AnimateDiv('kavlogoforanimate');
		XHR.sendAndLoad('$page', 'POST',x_AcceptEULA);
	}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function Kav4EULASave(){
	$sock=new sockets();
	$sock->SET_INFO("Kav4ProxyLicenseRead", 1);
}



?>