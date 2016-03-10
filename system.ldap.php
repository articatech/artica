<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-net"])){popup_net();exit;}
	if(isset($_GET["popup-search"])){popup_net_search();exit;}
	if(isset($_GET["openldap-interface-js"])){popup_net_add_js();exit;}
	if(isset($_GET["delete-js"])){popup_net_del_js();exit;}
	if(isset($_POST["LdapListenIPAddr-del"])){popup_net_del();exit;}
	
	
	if(isset($_GET["openldap-interface"])){popup_net_add();exit;}
	if(isset($_POST["LdapListenIPAddr"])){popup_net_save();exit;}
	
	
	if(isset($_GET["open-ldap-status"])){openldap_status();exit;}
	if(isset($_GET["open-ldap-parameters"])){LDAP_CONFIG();exit;}
	if(isset($_POST["LdapAllowAnonymous"])){LDAP_CONFIG_SAVE();exit;}
	
	
	
	
js();

function js(){
	$page=CurrentPageName();
echo "
		document.getElementById('BodyContent').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		$('#BodyContent').load('$page?tabs=yes&tabsize={$_GET["tabsize"]}');"	;
}
function popup_net_add_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$add_interface=$tpl->javascript_parse_text("{add_interface}");
	header("content-type: application/x-javascript");
	$html="YahooWin('650','$page?openldap-interface=yes','$add_interface')";
				
 echo $html;
}

function popup_net_del_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$add_interface=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]} ?");
	$t=time();
	echo "var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	YahooWinHide();
	$('#OPENLDAP_LISTEN_ADDR').flexReload();
}		
	
function Save$t(){
	if(!confirm('$add_interface')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('LdapListenIPAddr-del','{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
	Save$t();";
	
}

function popup_net_del(){
	$sock=new sockets();
	$SavedNets=explode("\n",$sock->GET_INFO('LdapListenIPAddr'));
	$ipc=new IP();
	
	
	while (list($num,$ip)=each($SavedNets)){
		if(!$ipc->isValid($ip)){continue;}
		$NEW[$ip]=$ip;
	}
	
	$SavedNets=array();
	unset($NEW[$_POST["LdapListenIPAddr-del"]]);
	while (list($num,$ip)=each($NEW)){
		
		$SavedNets[]=$ip;
	}
	
	
	$sock->SaveConfigFile(@implode("\n",$SavedNets),"LdapListenIPAddr");	
	
}

function Add_DB_save(){
	$q=new mysql();
	if((is_numeric($_POST["instance-id"]) && $_POST["instance-id"]>0)){$q=new mysql_multi($_POST["instance-id"]);}
	$sql="CREATE DATABASE `{$_POST["Add-DB-save"]}`";		
	if(!$q->EXECUTE_SQL($sql)){echo "{$_POST["Add-DB-save"]}\n\n$q->mysql_error";return;}
}


function popup_net(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=$_GET["t"];
	$t=time();
	

	$listen_addresses=$tpl->_ENGINE_parse_body("{listen_addresses}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$add_interface=$tpl->_ENGINE_parse_body("{add_interface}");
	$html="
	<table class='OPENLDAP_LISTEN_ADDR' style='display: none' id='OPENLDAP_LISTEN_ADDR' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#OPENLDAP_LISTEN_ADDR').flexigrid({
	url: '$page?popup-search=yes',
	dataType: 'json',
	colModel : [
	
		{display: '$listen_addresses', name : 'TaskType', width : 475, sortable : false, align: 'left'},
		{display: 'del', name : 'TaskType', width : 75, sortable : false, align: 'center'},
	],
buttons : [
	{name: '$add_interface', bclass: 'add', onpress : add_interface$t},
	{name: '$apply', bclass: 'Apply', onpress : Apply$t},
	],	
	searchitems : [
		{display: '$listen_addresses', name : 'listen_addresses'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$listen_addresses</span>',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
});		

var x_SelectDBEN= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#mysql-users-$tt').flexReload();
		
	}		
	
	
	function Apply$t(){
		Loadjs('system.ldap.progress.php');
	 }	
	
	function SelectDBEN(database,md){
			var value=0;
			if(document.getElementById(md).checked){value=1;}
			var XHR = new XHRConnection();
			XHR.appendData('selectDB-save','yes');
			XHR.appendData('database',database);
			XHR.appendData('user','{$_GET["user"]}');
			XHR.appendData('host','{$_GET["host"]}');
			XHR.appendData('enable',value);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.sendAndLoad('$page', 'POST',x_SelectDBEN);
	}
	
	function add_interface$t(){
		Loadjs('$page?openldap-interface-js=yes')
	}
	
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function popup_net_add(){
	$t=time();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$net=new networking();
	$nets=$net->ALL_IPS_GET_ARRAY();
	$SavedNets=explode("\n",$sock->GET_INFO('LdapListenIPAddr'));
	unset($nets["127.0.0.1"]);
	if(is_array($SavedNets)){
		while (list($num,$ip)=each($SavedNets)){
			unset($nets[$ip]);
		}
	
	}	
	
	$html="
	<center>". Field_array_Hash($nets, "LdapListenIPAddr-$t",null,"style:font-size:42px")."</center>
	<div style='text-align:right;margin-top:30px'>".button("{add}","Save$t()",42)."</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	YahooWinHide();
	$('#OPENLDAP_LISTEN_ADDR').flexReload();
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LdapListenIPAddr',document.getElementById('LdapListenIPAddr-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>			
";		
	
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function popup_net_save(){
	$sock=new sockets();
	$SavedNets=explode("\n",$sock->GET_INFO('LdapListenIPAddr'));
	$ipc=new IP();
	$SavedNets[]=$_POST["LdapListenIPAddr"];
	
	while (list($num,$ip)=each($SavedNets)){
		if(!$ipc->isValid($ip)){continue;}
		$NEW[]=$ip;
	}
	
	$sock->SaveConfigFile(@implode("\n",$NEW),"LdapListenIPAddr");
	
}


function popup_net_search(){
	$sock=new sockets();
	$MyPage=CurrentPageName();
	$net=new networking();
	$nets=$net->ALL_IPS_GET_ARRAY();
	$SavedNets=explode("\n",$sock->GET_INFO('LdapListenIPAddr'));
	$search=string_to_flexregex();
	$SavedNets[]="127.0.0.1";
	
	$data = array();
	$data['page'] = 1;$data['total'] = 0;$data['rows'] = array();	
		
	
	
	$c=0;
	while (list ($line,$key) = each ($SavedNets) ){
		$key=trim($key);
		if($key==null){continue;}
		if($search<>null){if(!preg_match("#$search#i", $key)){continue;}}
		$keyEnc=urlencode($key);
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=$keyEnc')");
		$c++;
		if($key=="127.0.0.1"){$delete=null;}
		$data['rows'][] = array(
		'id' => $keyEnc,
		'cell' => array("<span style='font-size:28px'>$key</span>",$delete)
		);		
		
		}
	$data['total'] =$c;
	echo json_encode($data);	
	
}




	
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["popup"]='{local_service}';
	$array["popup-net"]='{listen_addresses}';
	$array["ldap_proxy"]='{ldap_proxy}';
	$array["client"]='{client_parameters}';
	
	//$array["backup"]='{backup}';
	
	$tabsize="style='font-size:26px'";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="client"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"artica.settings.php?js-account-interface=yes\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="ldap_proxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"openldap.proxy.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;			
			
		}
		if($num=="mysql-multi"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mysql.multi.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;			
			
		}		
		
		if($num=="ssl"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.ssl.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="globals"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.globals.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.events.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="watchdog"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mysql.watchdog-events.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}	
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $tabsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_ldap")."<script>LeftDesign('database-256-opac20.png');</script>";
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$html="<div style='width:98%' class=form>
						<div style='font-size:32px;margin-bottom:20px'>{openldap_parameters}</div>
					<div class='text-info'>{openldap_parameters_text}</div>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'>	
			<div id='open-ldap-status'></div>
		</td>
		<td style='vertical-align:top;width:99%'>

					<div id='open-ldap-parameters' style='width:98%' class=form></div>
		</td>
	</tr>
	</table>
	</div>
	<script>
		LoadAjax('open-ldap-status','$page?open-ldap-status=yes');
		LoadAjax('open-ldap-parameters','$page?open-ldap-parameters=yes');
	</script>
	";

	echo $tpl->_ENGINE_parse_body($html);
}


function LDAP_CONFIG_SAVE(){
	$sock=new sockets();
	$tpl=new templates();
	if(isset($_POST["LdapAllowAnonymous"])){$sock->SET_INFO("LdapAllowAnonymous",$_POST["LdapAllowAnonymous"]);}
	if(isset($_POST["EnableRemoteAddressBook"])){$sock->SET_INFO("EnableRemoteAddressBook",$_POST["EnableRemoteAddressBook"]);}
	if(isset($_POST["LockLdapConfig"])){$sock->SET_INFO("LockLdapConfig",$_POST["LockLdapConfig"]);}
	if(isset($_POST["RestartLDAPEach"])){$sock->SET_INFO("RestartLDAPEach",$_POST["RestartLDAPEach"]);}
	if(isset($_POST["EnableOpenLDAP"])){$sock->SET_INFO("EnableOpenLDAP",$_POST["EnableOpenLDAP"]);}
	if(isset($_POST["RestartOpenLDAP"])){$sock->SET_INFO("RestartOpenLDAP",$_POST["RestartOpenLDAP"]);}
	
	
	
	
	if(isset($_POST["cachesize"])){
		if($_POST["set_cachesize"]>3999){
			echo $tpl->_ENGINE_parse_body('{error_max_value_is}: 3999M');
			exit;
		}
		
		if($_POST["cachesize"]<1000){
			echo $tpl->_ENGINE_parse_body(replace_accents(html_entity_decode('{error_min_value_is}: 1000 {entries}')));
			exit;
		}
		
		$sock->SET_INFO("LdapDBCachesize",$_GET["cachesize"]);
		$_POST["set_cachesize"]=($_POST["set_cachesize"]*1000)*1024;
		$sock->SET_INFO("LdapDBSetCachesize",$_POST["set_cachesize"]);
	}

	
	
}

function LDAP_SAVE(){
	if($this->SquidPerformance>2){return;}
	if($this->EnableIntelCeleron==1){return;}


	if(!isset($GLOBALS["cmd.php?ldap-restart=yes"])){
		$sock->getFrameWork("cmd.php?ldap-restart=yes");
		$GLOBALS["cmd.php?ldap-restart=yes"]=true;
	}

}

function LDAP_CONFIG(){
	$sock=new sockets();
	$ldap=new clladp();
	$page=CurrentPageName();
	$t=time();
	$nets[null]="{loopback}";
	$nets["all"]="{all}";
	$return=Paragraphe32("troubleshoot","troubleshoot_explain","Loadjs('index.troubleshoot.php');","48-troubleshoots.png",180);
	$superuser=Paragraphe32("account","accounts_text","Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes')","superuser-48.png",180);
	$EnableOpenLDAP=intval($sock->GET_INFO("EnableOpenLDAP"));
	$RestartOpenLDAP=intval($sock->GET_INFO("RestartOpenLDAP"));
	$loglevel_hash=array(
			0=>"{none}",
			256=>"{basic}",
			512=>"stats log entries sent",
			1024=>"Communications",
			16384=>"{synchronization}",
			2048=>"{debug}",
	);

	$CACHE_AGES[0]="{never}";
	$CACHE_AGES[30]="30 {minutes}";
	$CACHE_AGES[60]="1 {hour}";
	$CACHE_AGES[120]="2 {hours}";
	$CACHE_AGES[360]="6 {hours}";
	$CACHE_AGES[720]="12 {hours}";
	$CACHE_AGES[1440]="1 {day}";
	$CACHE_AGES[2880]="2 {days}";
	$CACHE_AGES[4320]="3 {days}";
	$CACHE_AGES[10080]="1 {week}";
	$CACHE_AGES[20160]="2 {weeks}";
	$CACHE_AGES[43200]="1 {month}";

	$RestartLDAPEach=$sock->GET_INFO("RestartLDAPEach");
	if(!is_numeric($RestartLDAPEach)){$RestartLDAPEach=4320;}

	$LockLdapConfig=$sock->GET_INFO("LockLdapConfig");
	$OpenLDAPLogLevel=$sock->GET_INFO("OpenLDAPLogLevel");
	if(!is_numeric($OpenLDAPLogLevel)){$OpenLDAPLogLevel=256;}
	if(!is_numeric($LockLdapConfig)){$LockLdapConfig=0;}
	$loglevel_field=Field_array_Hash($loglevel_hash,"OpenLDAPLogLevel",$OpenLDAPLogLevel,"style:font-size:22px;padding:3px");
	$restart_field=Field_array_Hash($CACHE_AGES,"RestartLDAPEach",$RestartLDAPEach,"style:font-size:22px;padding:3px");
	
	
	
	$EnableOpenLDAP=Paragraphe_switch_img("{EnableOpenLDAP}", "{EnableOpenLDAP_explain}","EnableOpenLDAP",$EnableOpenLDAP,null,980);
	$RestartOpenLDAP=Paragraphe_switch_img("{restart_openldap_periodically}", "{restart_openldap_periodically_explain}","RestartOpenLDAP",$RestartOpenLDAP,null,980);
	
	$LockLdapConfig=Paragraphe_switch_img("{LockLdapConfig}", "{LockLdapConfig_explain}","LockLdapConfig",$LockLdapConfig,null,980);
	$allowanonymouslogin=Paragraphe_switch_img("{allowanonymouslogin}", "{allowanonymouslogin_explain}","LdapAllowAnonymous",$sock->GET_INFO('LdapAllowAnonymous'),null,980);
	$EnableRemoteAddressBook=Paragraphe_switch_img("{remote_addressbook_text}", "{remote_addressbook_text_explain}","EnableRemoteAddressBook",$sock->GET_INFO('EnableRemoteAddressBook'),null,980);
	
	$LdapdbSize=$sock->getFrameWork('cmd.php?LdapdbSize=yes');
	if(preg_match('#(.+?)\s+#',$LdapdbSize,$re)){
		$LdapdbSize=$re[1];
	}
	
	$LdapDBSetCachesize=$sock->GET_INFO("LdapDBSetCachesize");
	$LdapDBSCachesize=$sock->GET_INFO("LdapDBCachesize");
	if($LdapDBSetCachesize==null){$LdapDBSetCachesize=5120000;}
	if($LdapDBSCachesize==null){$LdapDBSCachesize=1000;}
	$LdapDBSetCachesizeMo=($LdapDBSetCachesize/1024)/1000;
	
	
	$form_network="
	

		<div style='font-size:22px;margin-bottom:10px;text-align:right'>{ldap_suffix}: <a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('openldap.changesuffix.php');\"
		style='font-size:22px;text-decoration:underline;font-weight:bold'>$ldap->suffix</a></div>
		<div id='ParseFormLDAPNET'>
		<table style='width:99%'>
		<tr>
			<td colspan=2>$EnableOpenLDAP</td>
		</tr>	
		<tr>
			<td colspan=2>$RestartOpenLDAP</td>
		</tr>
		
		
		<tr>
			<td colspan=2>$LockLdapConfig</td>
		</tr>
		<tr>
			<td colspan=2>$allowanonymouslogin</td>
		</tr>
		<tr>
			<td colspan=2>$EnableRemoteAddressBook</td>
		</tr>							
		<tr>
			<td class=legend nowrap style='font-size:22px'>{log_level}:</td>
			<td><strong style='font-size:11px' nowrap>$loglevel_field</td>
		</tr>
		<tr>
			<td class=legend nowrap style='font-size:22px'>{restart_service_each}:</td>
			<td><strong style='font-size:22px' nowrap>$restart_field</td>
		</tr>
		
		
	<tr>
			<td colspan=2><div style='font-size:32px;margin-top:30px'>{ldap_configure_bdbd}</div>
	</tr>
	<tr>
			<td class=legend style='font-size:22px'>{set_cachesize}:</td>
			<td style='font-size:22px'>". Field_text('set_cachesize',$LdapDBSetCachesizeMo,'width:90px;font-size:22px')."M&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ldap_cache_size}:</td>
		<td style='font-size:22px'>". Field_text('cachesize',$LdapDBSCachesize,'width:90px;font-size:22px')."&nbsp;{entries}</td>
	</tr>			
		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","26")."
	</td>
	</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		alert(results);
		return;
	}
	Loadjs('system.ldap.progress.php');
	RefreshTab('main_config_ldap');
	
}

function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('RestartOpenLDAP',document.getElementById('RestartOpenLDAP').value);
	XHR.appendData('EnableOpenLDAP',document.getElementById('EnableOpenLDAP').value);
	XHR.appendData('EnableRemoteAddressBook',document.getElementById('EnableRemoteAddressBook').value);
	XHR.appendData('LdapAllowAnonymous',document.getElementById('LdapAllowAnonymous').value);
	XHR.appendData('LockLdapConfig',document.getElementById('LockLdapConfig').value);
	XHR.appendData('OpenLDAPLogLevel',document.getElementById('OpenLDAPLogLevel').value);
	XHR.appendData('RestartLDAPEach',document.getElementById('RestartLDAPEach').value);
	XHR.appendData('set_cachesize',document.getElementById('set_cachesize').value);
	XHR.appendData('cachesize',document.getElementById('cachesize').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($form_network);

}

function LDAP_CONFIG_JS(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==false){echo "alert('no privileges');";exit;}
	$tpl=new templates();
	$ldap_title=$tpl->_ENGINE_parse_body('{APP_LDAP}');
	$page=CurrentPageName();
	$html="
	function LDAPInterFace(){
	YahooWin3(910,'$page?js-ldap-popup=yes','$ldap_title');
}


var x_ParseFormLDAP= function (obj) {
var results=obj.responseText;
if(results.length>0){alert(results);}
RefreshTab('main_config_ldap_adv');
}

function ParseFormLDAP(){
var LdapAllowAnonymous;
var EnableRemoteAddressBook;
if(document.getElementById('LockLdapConfig').checked){LockLdapConfig=1;}else{LockLdapConfig=0;}
if(document.getElementById('LdapAllowAnonymous').checked){LdapAllowAnonymous=1;}else{LdapAllowAnonymous=0;}
if(document.getElementById('EnableRemoteAddressBook').checked){EnableRemoteAddressBook=1;}else{EnableRemoteAddressBook=0;}
var XHR = new XHRConnection();
XHR.appendData('set_cachesize',document.getElementById('set_cachesize').value);
XHR.appendData('cachesize',document.getElementById('cachesize').value);
XHR.appendData('LdapListenIPAddr',document.getElementById('LdapListenIPAddr').value);
XHR.appendData('LdapAllowAnonymous',LdapAllowAnonymous);
XHR.appendData('EnableRemoteAddressBook',EnableRemoteAddressBook);


XHR.sendAndLoad('$page', 'GET',x_ParseFormLDAP);

}

function ParseFormLDAPDB(){
var XHR = new XHRConnection();
XHR.appendData('set_cachesize',document.getElementById('set_cachesize').value);
XHR.appendData('cachesize',document.getElementById('cachesize').value);
AnimateDiv('ParseFormLDAPNET');
XHR.sendAndLoad('$page', 'GET',x_ParseFormLDAP);
}






LDAPInterFace();
";

echo $html;

}



function openldap_status(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=$sock->getFrameWork("services.php?openldap-status=yes");
	$ini->loadString(base64_decode($datas));
	$status=DAEMON_STATUS_ROUND("LDAP",$ini,null,0)."<center style='margin:30px'>".button("{restart_openldap_service}","Loadjs('system.ldap.progress.php')",16)."</center>";
	echo $tpl->_ENGINE_parse_body($status);	
	
}

?>