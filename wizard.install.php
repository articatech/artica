<?php
if(isset($_GET["verbose"])){
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.browser.detection.inc');
include_once('ressources/class.resolv.conf.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.squid.inc');

if(isset($_GET["explain-performance"])){setup_features_explain();exit;}
if(isset($_GET["setup-1"])){setup_1();exit;}
if(isset($_GET["setup-2"])){setup_2();exit;}
if(isset($_GET["setup-3"])){setup_3();exit;}
if(isset($_GET["setup-4"])){setup_4();exit;}
if(isset($_GET["setup-5"])){setup_5();exit;}
if(isset($_GET["setup-nic"])){setup_nic();exit;}
if(isset($_POST["savedsettings"])){save();exit;}
if(isset($_GET["settings-dns"])){dns_save();exit;}
if(isset($_GET["settings-ou"])){ou_save();exit;}
if(isset($_GET["settings-final"])){final_show();exit;}
if(isset($_GET["setup-proxy-type"])){setup_proxy_type();exit;}
if(isset($_GET["setup-active-directory"])){setup_active_directory();exit;}
if(isset($_GET["setup-features"])){setup_features();exit;}
if(isset($_POST["EnableKerbAuth"])){setup_active_directory_save();exit;}
if(isset($_POST["SquidPerformance"])){setup_features_save();exit;}

if(isset($_GET["automation"])){automation_js();exit;}
if(isset($_GET["automation-js"])){automation_js();exit;}
if(isset($_GET["automation-popup"])){automation_popup();exit;}
if(isset($_POST["AutomationScript"])){SaveAutomation();exit;}
if(isset($_GET["setup-ufdbguard"])){setup_ufdbguard();exit;}
if(isset($_POST["EnableUfdbGuard"])){EnableUfdbGuard();exit;}
if(isset($_GET["progressbar-js"])){progressbar_js();exit;}
if(isset($_GET["ShowProgress-js"])){progressbar_js();exit;}
if(isset($_GET["video-proxy"])){video_proxy();exit;}
js();


function automation_js(){
	$sock=new sockets();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	if($WizardSavedSettingsSend==1){die("Already posted..");}
	if($WizardSavedSettings["company_name"]<>null){die("Already posted..");}
	
	
	header("content-type: application/x-javascript");
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();
	
	$title=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
	
	echo "LoadAjax('content','$page?automation-popup=yes','$title')";
	
}

function automation_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$importsquid=null;
$html="";
$apply=$tpl->_ENGINE_parse_body("{apply}");

if($users->SQUID_INSTALLED){
	$importsquid=button("{import_squid_conf}","Loadjs('import.squid.zip.php')",22)."&nbsp;&nbsp;";
	
}

$nic=new system_nic("eth0");
$IPADDR=$nic->IPADDR;
$NETMASK=$nic->NETMASK;
$GATEWAY=$nic->GATEWAY;
$BROADCAST=$nic->BROADCAST;
$metric=$nic->metric;
if(!is_numeric($metric)){$metric=1;}

$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
$q=new mysql_squid_builder();
$domainname=$q->GetFamilySites($hostname);
$CMP=explode(".",$domainname);
$CompanyName=strtoupper($CMP[0]);

$arrayNameServers=GetNamesServers();
$tt=explode(".",$hostname);
$tt1=$tt[0];
unset($tt[0]);
$tt2=@implode(".", $tt);




	
$f[]="########################################";
$f[]="#";
$f[]="#       Automation script sample       #";
$f[]="#";
$f[]="########################################";
$f[]="# Copy this content and paste modified data";
$f[]="# After apply, the server will change all parameters";
$f[]="# You will have access to the interface using";
$f[]="# Manager as account and secret as password";
$f[]="";
$f[]="########################################";
$f[]="######             Network        ######";
$f[]="########################################";
$f[]="";
$f[]="# First main Network interface";
$f[]="# \"KEEPNET\" should modify current network [0] or keep the current network settings [1]";
$f[]="KEEPNET=0";
$f[]="VPS_COMPATIBLE=0";
$f[]="NIC=eth0";
$f[]="IPADDR=$IPADDR";
$f[]="NETMASK=$NETMASK";
$f[]="GATEWAY=$GATEWAY";
$f[]="BROADCAST=$BROADCAST";
$f[]="metric=$metric";
$f[]="DNS1=".$arrayNameServers[0];
$f[]="DNS2=".$arrayNameServers[1];
$f[]="";
$f[]="########################################";
$f[]="######          SNMP Service      ######";
$f[]="########################################";
$f[]="";
$f[]="# Activate the SNMP service 0/1";
$f[]="EnableSNMPD=0";
$f[]="# Public community";
$f[]="SNMPDCommunity=public";
$f[]="# Allowed network";
$f[]="SNMPDNetwork=default";

$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
if($ArticaMetaPort==0){$ArticaMetaPort=9000;}

$f[]="########################################";
$f[]="######          Meta Server      ######";
$f[]="########################################";
$f[]="";
$f[]="# Use an Meta Server Global Management console 0/1";
$f[]="EnableArticaMetaClient=0";
$f[]="# Meta Server server user name";
$f[]="#ArticaMetaUsername=Manager";
$f[]="# Meta Server server password";
$f[]="#ArticaMetaPassword=secret";
$f[]="# Meta Server server address";
$f[]="#ArticaMetaHost=1.2.3.4";
$f[]="# Meta Server server port";
$f[]="#ArticaMetaPort=9000";


$f[]="";
$f[]="########################################";
$f[]="######      System parameters     ######   ";
$f[]="########################################";
$f[]="# Hostname of the server 15 characters MAX";
$f[]="netbiosname=$tt1";
$f[]="";
$f[]="# Domain of the server ( use the same of your Active directory server if you plan to use AD)";
$f[]="domain=$tt2";
$f[]="";
$f[]="# Time zone: Europe/Moscow, Europe/Paris, Europe/Rome, US/Central ...";
$f[]="# see http://en.wikipedia.org/wiki/List_of_tz_database_time_zones for the complete list";
$f[]="timezones=US/Central";
$f[]="# OpenLDAP server threads";
$f[]="SlapdThreads=2";
$f[]="# Kernel Swapiness define after which percentage of physical memory use the kernel will use the swap file";
$f[]="swappiness=90";
$f[]="# Change the system root password ( used for SSH or console mode )";
$f[]="#RootPassword=artica";
$f[]="";



$f[]="";
$f[]="########################################";
$f[]="######   Registration parameters  ######";
$f[]="########################################";
$f[]="";
$f[]="";
$f[]="# company name will be the title of your web interface";
$f[]="# you should not set corrupted data such as toto mdlcsmck or something else";
$f[]="# You will not be able to change it after";
$f[]="company_name=$CompanyName";
$f[]="city=Paris";
$f[]="# LDAP Organization ( if not connected to the Active Directory)";
$f[]="organization=$CompanyName";
$f[]="country=France";
$f[]="smtp_domainname=$domainname";
$f[]="mail=support@$domainname";
$f[]="telephone=00.00.00.00.00";
$f[]="employees=55";
$f[]="#The Gold key License number provided by our sales team;";
$f[]="#GoldKey=";
$f[]="";
$f[]="########################################";
$f[]="######    Services/Proxy section  ######";
$f[]="########################################";
$f[]="";

$f[]="# Standard Proxy Listen port ( non transparent)";
$f[]="proxy_listen_port=3128";
$f[]="";
$f[]="# Enable/Disable the transparent mode 0 = no, 1 = yes";
$f[]="# Standard Proxy Transparent Listen port";
$f[]="EnableTransparent=0";
$f[]="# Transparent port";
$f[]="TransparentPort=0";
$f[]="";
$f[]="# Activate FreeRadius";
$f[]="EnableFreeRadius=0";
$f[]="";
$f[]="# Activate DHCP service.";
$f[]="EnableDHCPServer=0";
$f[]="";

$f[]="# Activate Web Filtering.";
$f[]="EnableWebFiltering=1";
$f[]="";
$f[]="# Activate NTLM Proxy";
$f[]="EnableCNTLM=0";

$f[]="";
$f[]="# Artica Features level";
$f[]="# SquidPerformance=0 Enable all features including Categories services,statistics and relatime monitor";
$f[]="# SquidPerformance=1 Disable Categories services, enable Artica statistics and Artica realtime monitor";
$f[]="# SquidPerformance=2 Disable Categories services, Disable Artica statistics and Artica realtime monitor";

$f[]="SquidPerformance=1";
$f[]="";

$f[]="# NTLM Proxy Listen port";
$f[]="CnTLMPORT=3155";
$f[]="# Proxy shared physical memory (MB)";
$f[]="cache_mem=256";
$f[]="# Proxy FQDN DNS cache size (items)";
$f[]="fqdncache_size=51200";
$f[]="# Proxy IP DNS cache size (items)";
$f[]="ipcache_size=51200";
$f[]="# Proxy DNS cache low (%)";
$f[]="ipcache_low=90";
$f[]="# Proxy DNS cache High (%)";
$f[]="ipcache_low=95";
$f[]="";
$f[]="";
$f[]="# Watchdog";
$f[]="";

$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
$f[]="# Ping the gateway in order to see if network is available ?";
$f[]="ENABLE_PING_GATEWAY={$MonitConfig["ENABLE_PING_GATEWAY"]}";
$f[]="# Ip address of the gateway, if not set, then automatically found it";
$f[]="PING_GATEWAY={$MonitConfig["PING_GATEWAY"]}";
$f[]="# Max rotation, after x failed, stop to evaluate the ping process";
$f[]="MAX_PING_GATEWAY={$MonitConfig["MAX_PING_GATEWAY"]}";
$f[]="# If ping failed, reconfigure the network ?";
$f[]="PING_FAILED_RELOAD_NET={$MonitConfig["PING_FAILED_RELOAD_NET"]}";
$f[]="# If ping failed, reboot the server ?";
$f[]="PING_FAILED_REBOOT={$MonitConfig["PING_FAILED_REBOOT"]}";
$f[]="# If ping failed, report network status ?";
$f[]="PING_FAILED_REPORT={$MonitConfig["PING_FAILED_REPORT"]}";
$f[]="# If ping failed, switch to failover backup server ?";
$f[]="PING_FAILED_FAILOVER={$MonitConfig["PING_FAILED_FAILOVER"]}";

$f[]="";
$f[]="";
$f[]="# Specifics DNS servers for the proxy. Separate them with a comma";
$f[]="ProxyDNS=".@implode(",",$arrayNameServers);
$f[]="";
$f[]="# Blacklist categories in default rule.";
$f[]="# Separate them with a comma";
$f[]="# possible values are:";
$f[]="#porn,sex/lingerie,mixed_adult,sexual_education,abortion,dating,tattooing,agressive,violence,terrorism,";
$f[]="#automobile/bikes,automobile/boats,automobile/cars,automobile/planes,automobile/carpool,bicycle,publicite,";
$f[]="#cleaning,dangerous_material,downloads,chat,passwords,drugs,dynamic,financial,stockexchange,";
$f[]="#finance/banking,finance/insurance,finance/moneylending,finance/realestate,finance/other,";
$f[]="#forums,socialnet,jobsearch,jobtraining,learning,humanitarian,associations,gamble,hacking,warez,";
$f[]="#hobby/cooking,hobby/fishing,hobby/arts,hobby/other,isp,webmail,liste_bu,mobile-phone,marketingware,";
$f[]="#webradio,audio-video,youtube,webtv,music,movies,blog,news,press,society,books,manga,dictionaries,phishing,";
$f[]="#redirector,proxy,paytosurf,reaffected,tricheur,webphone,weapons,";
$f[]="#games,hobby/pets,animals,horses,filehosting,photo,pictureslib,imagehosting,religion,sect,";
$f[]="#genealogy,recreation/wellness,recreation/travel,recreation/nightout,governments,";
$f[]="#recreation/schools,housing/doityourself,housing/builders,housing/accessories,houseads,smallads,";
$f[]="#electricalapps,justice,police,converters,meetings,getmarried,tobacco,recreation/sports,recreation/humor,";
$f[]="#children,teens,shopping,gifts,luxury,cosmetics,clothing,electronichouse,models,celebrity,womanbrand,";
$f[]="#politic,industry,science/chemistry,sciences,astrology,science/astronomy,science/weather,nature,green,";
$f[]="#browsersplugins,webplugins,maps,webapps,science/computing,remote-control,hospitals,medical,health,";
$f[]="#handicap,sslsites,updatesites,internal,searchengines,translators,spyware,malware,tracker,";
$f[]="#transport,culture,wine,alcohol,literature,mailing,suspicious";
$f[]="";
$f[]="Blacklists=porn,mixed_adult,dating,violence,spyware,malware,tracker,publicite,mailing,suspicious";
$f[]="";
$f[]="";
$f[]="# Caches center.";
$f[]="# Allows you to auto-create caches";
$f[]="# Caches will be prepared but will not created until the license is not accepted by Artica";
$f[]="# If you have a gold key, it is fully supported";
$f[]="# caches type should be tmpfs,rock,aufs,diskd";
$f[]="# define: cache_name,cpu,directory,type,cache_size (MB),cache_dir_level1,cache_dir_level2";
$f[]="# Cache Memory Example: ";
$f[]="# caches=Mem1,1,/home/mem,tmpfs,500,128,256 Will create a cache memory with 500MB";
$f[]="# Cache disk Example: ";
$f[]="# caches=disk1,2,/home/squid/cache,tmpfs,5000,128,256 Wil create a cache disk for CPU2 with 5GB";
$f[]="";
$f[]="# Activate ARP Daemon";
$f[]="EnableArpDaemon=0";
$f[]="#";
$f[]="# Activate FreeWebs Web servers management";
$f[]="EnableFreeWeb=0";
$f[]="# If 1 then Artica Proxy Statistics are disabled, if 0 Artica Proxy Statistics are enabled";
$f[]="DisableArticaProxyStatistics=0";
$f[]="# Activate SARG statistics generation";
$f[]="EnableSargGenerator=0";
$f[]="# Activate Hostnames logging in Proxy statistics";
$f[]="EnableProxyLogHostnames=1";

$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
$username=$TuningParameters["username"];
$password=$TuningParameters["password"];
$mysqlserver=$TuningParameters["mysqlserver"];
$RemotePort=$TuningParameters["RemotePort"];
if($username==null){$username="root";}

$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
$WizardStatsApplianceDisconnected=intval($sock->GET_INFO("WizardStatsApplianceDisconnected"));

$f[]="";
$f[]="########################################";
$f[]="######  Statistics appliance ###### ";
$f[]="########################################";
$f[]="# Use Statistics appliance in disconnected mode (0/1) - suggest to 1";
$f[]="#WizardStatsApplianceDisconnected=0";
$f[]="# Artica stats appliance remote address";
$f[]="#WizardStatsAppliance_server=";
$f[]="# Artica stats appliance remote SSL port";
$f[]="#WizardStatsAppliance_port=9000";
$f[]="# SuperAdmin credentials to communicate";
$f[]="#WizardStatsAppliance_username=Manager";
$f[]="#WizardStatsAppliance_password=secret";
$f[]="";
$f[]="########################################";
$f[]="######  Squid.conf acls importation ###### ";
$f[]="########################################";
$f[]="";
$f[]="# copy/paste your old Squid.conf inside <SQUIDCONF></SQUIDCONF> paragraph";
$f[]="<SQUIDCONF>";
$f[]="#something here...";
$f[]="</SQUIDCONF>";
$f[]="";


$f[]="########################################";
$f[]="######      LOGS ROTATION       ######";
$f[]="########################################";


$f[]="# Activate System events rotation storage";

$f[]="#Run/install a MySQL dedicated service on this computer 1 = Enabled, 0= disabled";
$f[]="EnableSyslogDB=0";
$f[]="#Where to put rotate files ?";
$f[]="# 1 = MySQL Local service"; 
$f[]="# 2 = Remote MySQL service";
$f[]="# 3 = Remote NAS Storage";
$f[]="# 4 = On the local disk";
$f[]="MySQLSyslogType=4";
$f[]="#";
$f[]="# Where to put Syslog database path ( if EnableSyslogDB=1 )";
$f[]="MySQLSyslogWorkDir=/home/syslogsdb";
$f[]="MySQLSyslogUsername=$username";
$f[]="MySQLSyslogPassword=$password";
$f[]="MySQLSyslogServer=$mysqlserver";
$f[]="MySQLSyslogServerPort=$RemotePort";
$f[]="# NAS Storage system parameters( if MySQLSyslogType=4 )";

$f[]="# NAS IP address";
$f[]="#BackupSquidLogsNASIpaddr=$BackupSquidLogsNASIpaddr";
$f[]="# NAS Shared Folder";
$f[]="#BackupSquidLogsNASFolder=$BackupSquidLogsNASFolder";
$f[]="# NAS username and password ( empty if no credential)";
$f[]="#BackupSquidLogsNASUser=$BackupSquidLogsNASUser";
$f[]="#BackupSquidLogsNASPassword=$BackupSquidLogsNASPassword";
$f[]="";
$f[]="########################################";
$f[]="######       Web Interface       ######";
$f[]="########################################";
$f[]="# Manager name and password:";
$f[]="# This is the Account of the global Manager interface ( default: Username Manager, password=secret)";
$f[]="#ManagerAccount=Manager";
$f[]="#ManagerPassword=secret";
$f[]="# Disable insert special characters in passwords (0/1)";
$f[]="#DisableSpecialCharacters=1";
$f[]="";
$f[]="# EndUser Web Access Web servername";
$f[]="adminwebserver=admin.company.tld";
$f[]="";
$f[]="# EndUser Web Access Web servername 2";
$f[]="second_webadmin=$IPADDR";
$f[]="# Full Administrator";
$f[]="administrator=admin";
$f[]="administratorpass=password";
$f[]="# Statistics Administrator";
$f[]="statsadministrator=admin";
$f[]="statsadministratorpass=password";
$f[]="";
$f[]="########################################";
$f[]="######  Active Directory settings ###### ";
$f[]="########################################";
$f[]="";
$f[]="";
$f[]="# Enable/Disable Active Directory connection.";
$f[]="EnableKerbAuth=0";
$f[]="# Enable Active Directory connection.";
$f[]="# Active Directory DNS suffix.";
$f[]="WINDOWS_DNS_SUFFIX=$domainname";
$f[]="";
$f[]="# Active Directory server netbios name";
$f[]="WINDOWS_SERVER_NETBIOSNAME=dc";
$f[]="# Active Directory workgroup name";
$f[]="ADNETBIOSDOMAIN=$CompanyName";
$f[]="# Ip address of the Active Directory server";
$f[]="ADNETIPADDR=192.168.1.10";
$f[]="# If ip address is set, you can force system to use the AD as first DNS";
$f[]="UseADAsNameServer=1";
$f[]="# Use the Active Directory as Time server ? 0/1";
$f[]="NtpdateAD=0";
$f[]="# Use this Internal Interface to communicate with the Active Directory";
$f[]="#SambaBindInterface=10.10.10.1";
$f[]="# Active Directory server version ( WIN_2003 or WIN_2008AES for both 2008/2012)";
$f[]="WINDOWS_SERVER_TYPE=WIN_2003";
$f[]="";
$f[]="COMPUTER_BRANCH=CN=Computers";
$f[]="WINDOWS_SERVER_ADMIN=administrator";
$f[]="WINDOWS_SERVER_PASS=adminpassword\n";	
$f[]="";


$t=time();
$text=@implode("\n", $f);

$button=button($apply, "Save$t()",22);

$html="
<div style='font-size:22px;margin:15px' class=explain>{automation_script_explain}</div>
<center id='$t' style='margin:10px'></center>
<center>
<div style='text-align:center;width:100%;background-color:white;margin-bottom:10px;padding:5px;'>$importsquid$button<br></div>
<textarea
style='width:95%;height:400px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
id='content-$t'>$text</textarea>
<div style='text-align:center;width:100%;background-color:white;margin-top:10px'>$button</div>
</center>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
	Loadjs('wizard.automationscript.progress.php');
	//alert('The Automation Script was correctly executed on your server...\\nWe suggest to reboot your server after 2/3 minutes');
	//document.location.href='logon.php';

}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('AutomationScript', encodeURIComponent(document.getElementById('content-$t').value));
	document.getElementById('$t').innerHTML=\"<img src='img/wait_verybig_old.gif' style='margin:30px'>\";
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";

echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveAutomation(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"\n");
	$sock=new sockets();
	
	$_POST["AutomationScript"]=url_decode_special_tool($_POST["AutomationScript"]);
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf", $_POST["AutomationScript"]);
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf")){
		$sock->getFrameWork("services.php?chown-medir=yes");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf", $_POST["AutomationScript"]);
	}
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf")){
		echo "/usr/share/artica-postfix/ressources/logs/web premission denied!\n";
		return;
	}
	
	
	

}



function js(){
	header("content-type: application/x-javascript");
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;	
	$ProductName=null;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	if($users->WEBSECURIZE){
		$ProductName="Web Securize";
	}
	if($users->LANWANSAT){
		$ProductName="LanWanSat Proxy";
	}
	
	$title=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
	if($ProductName<>null){$title=str_ireplace("artica", $ProductName, $title);}
	
	echo "
		$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/blurps.css' type='text/css' media='screen' />\"));
		$(\"head\").append($(\"<link rel='stylesheet' href='ressources/templates/default/styles_forms.css' type='text/css' media='screen' />\"));
	
	YahooSetupControlModalFixedNoclose(850,'$page?setup-1=yes','$title')";
	
}

function video_proxy(){
	$videoStarted="
		
			<center  >
			<center style='font-size:18px;;margin-bottom:20px'>Video - Artica Proxy Started Guide</center>
			<iframe style='margin:5px;background-color:black;
				border:3px solid #A0A0A0;padding:5px;margin;5px;border-radius:5px 5px 5px 5px;-moz-border-radius:5px;
				-webkit-border-radius:5px;'width='560' height='315'
			src='//www.youtube.com/embed/7ZUqX8_5NGk?list=UUYbS4gGDNP62LsEuDWOMN1Q'
				frameborder='0' allowfullscreen></iframe></center>";
	echo $videoStarted;
	
	
}

function setup_nic(){
	$DISABLED=false;
	$t=$_GET["t"];
	$nic=new system_nic($_GET["NIC"]);
	$IPADDR=$nic->IPADDR;
	$NETMASK=$nic->NETMASK;
	$GATEWAY=$nic->GATEWAY;
	$BROADCAST=$nic->BROADCAST;
	$metric=$nic->metric;
	if(!is_numeric($metric)){$metric=1;}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:25px' nowrap>{tcp_address}:</td>
	<td>" . field_ipv4("IPADDR",$IPADDR,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:25px'>{netmask}:</td>
	<td>" . field_ipv4("NETMASK",$NETMASK,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
	</tr>
		
	<tr>
	<td class=legend style='font-size:25px'>{gateway}:</td>
	<td>" . field_ipv4("GATEWAY",$GATEWAY,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:25px'>{metric}:</td>
	<td>" . field_text("metric-$t",$metric,'padding:3px;font-size:25px;width:90px',null,null,null,false,null,$DISABLED)."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:25px'>{broadcast}:</td>
	<td>" . field_ipv4("BROADCAST",$BROADCAST,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
	</tr>
	</TABLE>");
	
}

function setup_1(){
	$users=new usersMenus();
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$WizardSavedSettings=$sock->GET_INFO("WizardSavedSettings");
	if($users->SQUID_INSTALLED){
		if(is_file("ressources/templates/Squid/welcome-$DetectedLanguage.txt")){
			$WELCOME_WIZARD_2=@file_get_contents("ressources/templates/Squid/welcome-$DetectedLanguage.txt");
		}
		
		$videolink=imgtootltip("youtube-play-64.png","{WELCOME_ON_ARTICA_PROJECT_PROXY_VIDEO}",
				"YahooWin('600','$page?video-proxy=yes','video')");
		

		
		
		$videoStarted="<div style='font-size:14px;padding:15px'>
		<table style='width:100%'>
		<tr>
		<td style='width:64px;vertical-align:top'>
		$videolink</td>
		<td style='vertical-align:top'>
		<div style='font-size:14px;padding:15px'>{WELCOME_ON_ARTICA_PROJECT_PROXY_VIDEO}</div>
		</td>
		</tr>
		</table>
		</div>";
		
	}
	

	if($users->WEBSECURIZE){
		$_SESSION["change-artica-name"]="WebSecurize";
		$WELCOME_WIZARD_2=str_ireplace("artica", $_SESSION["change-artica-name"], $WELCOME_WIZARD_2);
	}
	
	if($users->LANWANSAT){
		$_SESSION["change-artica-name"]="LanWanSat Proxy";
		$WELCOME_WIZARD_2=str_ireplace("artica", $_SESSION["change-artica-name"], $WELCOME_WIZARD_2);
	}
	
	if($users->BAMSIGHT){
		$_SESSION["change-artica-name"]="BamSight";
		$WELCOME_WIZARD_2=str_ireplace("artica", $_SESSION["change-artica-name"], $WELCOME_WIZARD_2);		
	}
	
	$WELCOME_ON_ARTICA_PROJECT=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
	$WELCOME_ON_ARTICA_PROJECT_WARNIN_REBOOT=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT_WARNIN_REBOOT}");
	$WELCOME_WIZARD_ARC1=$tpl->_ENGINE_parse_body("{WELCOME_WIZARD_ARC1}");
	
	$html="
	<input type='hidden' id='savedsettings' value=''>
	<div id='setup-content'>
	<div style='margin:10px;width:95%' class=form>
	<div style='font-size:37px;font-weight:bolder;padding:15px'>$WELCOME_ON_ARTICA_PROJECT</div>
	<div style='font-size:14px;padding:15px'>$WELCOME_ON_ARTICA_PROJECT_WARNIN_REBOOT</div>

	
    $videoStarted
    <div style='text-align:right'><hr>". button("{next}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$WizardSavedSettings')","36px")."</div>
	<div style='margin:18px;font-size:14px'>{WELCOME_WIZARD_ARC1}$WELCOME_WIZARD_2</div>
	<div style='text-align:right'><hr>". button("{next}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$WizardSavedSettings')","36px")."</div>
				
	<center style='margin:10px;width:95%'><img src='img/bg_user.jpg'></center>
	</div>
	</div>
	<script>
		$(\".ui-dialog-titlebar-close\").remove();
		document.getElementById('content').style.width='1000px';
		document.getElementById('content').style.height='1500px';

		
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function setup_ufdbguard(){
	include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$EnableWebFiltering=$savedsettings["EnableWebFiltering"];
	$savedsettings_encoded=urlencode($_GET["savedsettings"]);
	$ss="porn,mixed_adult,dating,violence,spyware,malware,tracker,publicite,mailing,suspicious";
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'>
		<div style='font-size:22px;margin-bottom:10px;'>{web_filtering}</div>
	</tr>
	<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'>
	". Paragraphe_switch_img("{activate_webfiltering}", "{activate_webfiltering_text}","EnableWebFiltering-$t",
			$EnableWebFiltering,null,500)."</td>
	</td>
	</tr>
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
	</tr>	
	<tr>
		<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","18px")."</td>
		<td>&nbsp;</td>
		<td align='right'>". button("{next}","SaveUfdbGuardSettings()","18px")."</td>
	</tr>		
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'>
		<div style='font-size:22px;margin-bottom:10px;'>{web_filtering_explain_choose}</div>
	</tr>	
	";
	
	$s=explode(",",$ss);
	while (list ($a, $b) = each ($s) ){
		$DEF[$b]=true;
	}
	
	$dansG = new dansguardian_rules();
	$ARRAY=$dansG->array_blacksites;
	while (list ($cat, $explain) = each ($ARRAY) ){
		$explain=$tpl->_ENGINE_parse_body("{$explain}");
		$tt[]=$cat;
		$vla=0;
		if(isset($DEF[$cat])){$vla=1;}
		$html=$html."
		<tr>
			<td style='border-top:1px solid #CCCCCC'>". Field_checkbox("cat_{$cat}", 1,$vla)."</td>
			<td style='font-size:16px;font-weight:bold;border-top:1px solid #CCCCCC'>$cat</td>
			<td style='font-size:14px;font-weight:normal;border-top:1px solid #CCCCCC'>$explain</td>
		</tr>		
				
		";
		$js[]="if(document.getElementById('cat_{$cat}').checked){ XHR.appendData('cat_{$cat}',1); }else{XHR.appendData('cat_{$cat}',0);}";
	}
	
	$html=$html."
	<tr>
		<td colspan=3 style='padding-top:15px;padding-left:10px;'><hr></td>
	</tr>	
	<tr>
		<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","18px")."</td>
		<td>&nbsp;</td>
		<td align='right'>". button("{next}","SaveUfdbGuardSettings()","18px")."</td>
	</tr>
	</table>
	</div>	
<script>
var xSaveUfdbGuardSettings= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');
}


		
function SaveUfdbGuardSettings(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableUfdbGuard',document.getElementById('EnableWebFiltering-$t').value);
	".@implode("\n", $js)."
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xSaveUfdbGuardSettings);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	

}

function EnableUfdbGuard(){
	$sock=new sockets();
	$sock->SET_INFO("EnableUfdbGuard", $_POST["EnableUfdbGuard"]);
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	$q=new mysql_squid_builder();
	while (list ($key, $val) = each ($_POST) ){
		if(!preg_match("#^cat_(.+)#", $key,$re)){continue;}
		if($val==0){
			$sql="DELETE FROM webfilter_blks WHERE webfilter_id=0 AND category='$key' AND modeblk=0";
			$q->QUERY_SQL($sql);
			continue;			
		}
		
		$sql="INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','$key','0')";
		$q->QUERY_SQL($sql);
	}
	
	$sql="DELETE FROM webfilter_blks WHERE webfilter_id=0 AND category='liste_bu' AND modeblk=1";
	$q->QUERY_SQL($sql);
	$sql="INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','liste_bu','1')";
	$q->QUERY_SQL($sql);
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");
}


function setup_features(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$savedsettings_encoded=urlencode($_GET["savedsettings"]);	
	$t=time();
	
	$sock->SET_INFO("AsSeenPerformanceFeature",1);
	$AsReverseProxyAppliance=intval($savedsettings["AsReverseProxyAppliance"]);
	$SquidPerformance=1;
	
	$t=time();
	

	$text_info_style="
margin: 0 0 20px;
padding: 5px 0 5px 15px;
color: black;
margin:5px;
margin-top:15px;
padding:3px;
text-align:left;
font-weight:normal;
font-size: 22px;
margin-bottom: 20px;
padding: 8px 35px 8px 14px;
text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
border:2px solid #73AD65;
border-radius:5px 5px 5px 5px;
-moz-border-radius:5px;
-webkit-border-radius:5px;
background-color: white;
";	

	$AsCategoriesAppliance=intval($savedsettings["AsCategoriesAppliance"]);
	$AsHapProxyAppliance=intval($savedsettings["AsHapProxyAppliance"]);
	
	
	$AsMetaServer=intval($savedsettings["AsMetaServer"]);
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	
	if($AsCategoriesAppliance==1){
		echo "<script>LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}
	
	if($AsHapProxyAppliance==1){
		echo "<script>LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}
	
	if($AsMetaServer==1){
		echo "<script>LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}	
	
	if($AsReverseProxyAppliance==1){
	echo "<script>LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');</script>";
	return;
	}	
	
	
	$array[0]="{full_features}";
	$array[1]="{no_categories}";
	$array[2]="{no_statistics}";	
	$array[3]="{minimal_features}";
	
	
	$ARRAYF[null]="{no_web_filtering}";
	$ARRAYF[0]="{block_unproductive_websites}";
	$ARRAYF[1]="{block_sexual_websites}";
	$ARRAYF[2]="{block_susp_websites}";
	$ARRAYF[3]="{block_multi_websites}";
	
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:50px;margin-bottom:30px;'>{global_performance}</div>
	<div style='$text_info_style' >{artica_squid_performance_text}</div>
	<p>&nbsp;</p>	
	<div class=explain style='font-size:20px;margin-bottom:30px' id='explain-$t'></div>	

	<div style='$text_info_style' >
	<table style='width:100%'>
	<tr>
		<td style='font-size:28px'>{performance_level}:</td>
		<td>". Field_array_Hash($array, "SquidPerformance-$t",$SquidPerformance,"Choose$t()",null,0,"font-size:28px")."</td>
	</tr>
	<tr>
		<td style='font-size:28px'>{webfiltering}:</td>
		<td>". Field_array_Hash($ARRAYF, "WizardWebFilteringLevel-$t",$WizardWebFilteringLevel,null,null,0,"font-size:28px")."</td>
	</tr>				
	</table>
	</div>
				

	<table style='width:100%;margin-top:50px'>
		<tr>
			<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","30px")."</td>
			<td>&nbsp;</td>
			<td  align='right'>". button("{next}","Save$t()","30px")."</td>
		</tr>
	</table>
		
</div>		
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		LoadAjax('setup-content','$page?setup-3=yes&savedsettings=$savedsettings_encoded');
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidPerformance',document.getElementById('SquidPerformance-$t').value);
		XHR.appendData('WizardWebFilteringLevel',document.getElementById('WizardWebFilteringLevel-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	
		function Choose$t(){
			var choosed=document.getElementById('SquidPerformance-$t').value;
			LoadAjax('explain-$t','$page?explain-performance='+choosed);
		}

		
		document.getElementById('content').style.height='890px';
		Choose$t();	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
		
	
}

function setup_features_save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidPerformance", $_POST["SquidPerformance"]);
	$sock->SET_INFO("WizardWebFilteringLevel", $_POST["WizardWebFilteringLevel"]);
	
	
}

function setup_features_explain(){
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{SquidPerformance_{$_GET["explain-performance"]}}");
}


function setup_proxy_type(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$savedsettings_encoded=urlencode($_GET["savedsettings"]);	
	$users=new usersMenus();
	
	$text_info_style="
margin: 0 0 20px;
padding: 5px 0 5px 15px;
color: black;
margin:5px;
margin-top:15px;			
padding:3px;
text-align:left;
font-weight:normal;
font-size: 16px;
margin-bottom: 20px;
padding: 8px 35px 8px 14px;
text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
border:2px solid #73AD65;
border-radius:5px 5px 5px 5px;
-moz-border-radius:5px;
-webkit-border-radius:5px;
background-color: white;
";
	
	$button_size=28;
	
	$explain_http_proxy_connected=$tpl->_ENGINE_parse_body("{explain_http_proxy_connected}");
	$explain_http_proxy_connected=str_ireplace("active directory", "<strong>Active Directory/Open LDAP</strong>", $explain_http_proxy_connected);

	$html[]="
	<div style='font-size:50px;margin-bottom:30px'>{select_main_service}</div><center>";
	

	
	
	$html[]="<center style='margin:30px;width:85%;padding:20px' class=form >".button("{webproxy_service} {or} {transparent_mode}", "ConnectedProxy()",$button_size)."
			<div style='$text_info_style'>
				$explain_http_proxy_connected
			</div>
			
			</center>";
	

	
	
	$html[]="<center style='margin:30px;width:85%;padding:20px' class=form >".button("{categories_appliance}", "CategoriesAppliance()",$button_size)."
			<div style='$text_info_style'>
				{explain_categories_appliance}		
			</div>
			</center>";
	
	
	$html[]="<center style='margin:30px;width:85%;padding:20px' class=form >".button("{LOAD_BALACING_SERVICE}", "LoadBalanceProxy()",$button_size)."
	<div style='$text_info_style'>
	{LOAD_BALACING_SERVICE_EXPLAIN}
	</div>
	</center>";
	
	
	if($users->NGINX_INSTALLED){
		$html[]="
		<center style='margin:30px;width:85%;padding:20px' class=form >".button("{reverse_proxy_service}", "ReverseProxy()",$button_size)."
			<div style='$text_info_style'>
			{explain_reverse_proxy}
			</div>
			</center>
	
			";
	}

	$html[]="
		<center style='margin:30px;width:85%;padding:20px' class=form >".button("{artica_meta_server}", "MetaServer()",$button_size)."
			<div style='$text_info_style'>
			{artica_meta_server_explain}
			</div>
			</center>
	
			";	
	$html[]="
			<table style='width:100%;margin-top:50px'>
			<tr>
			<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","30px")."</td>
			<td>&nbsp;</td>
			<td  align='right'>&nbsp;</td>
			</tr>
			</table>		
	</center>
	<script>
var xchoose= function (obj) {
	UnlockPage();
	var results=obj.responseText;

	LoadAjax('setup-content','$page?setup-active-directory=yes&savedsettings='+results);
}
		
function ReverseProxy(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
	XHR.appendData('AsHapProxyAppliance','0');
	XHR.appendData('AsReverseProxyAppliance','1');
	XHR.appendData('AsCategoriesAppliance','0');
	XHR.appendData('AsTransparentProxy','0');
	XHR.appendData('AsMetaServer','0');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);
}

function LoadBalanceProxy(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
	XHR.appendData('AsHapProxyAppliance','1');
	XHR.appendData('AsReverseProxyAppliance','0');
	XHR.appendData('AsCategoriesAppliance','0');
	XHR.appendData('AsTransparentProxy','0');
	XHR.appendData('AsMetaServer','0');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);
}
		
function CategoriesAppliance(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
		XHR.appendData('AsHapProxyAppliance','0');
	XHR.appendData('AsCategoriesAppliance','1');
	XHR.appendData('AsReverseProxyAppliance','0');
	XHR.appendData('AsTransparentProxy','0');
	XHR.appendData('AsMetaServer','0');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);
}
		
function ConnectedProxy(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
	XHR.appendData('AsHapProxyAppliance','0');
	XHR.appendData('AsReverseProxyAppliance','0');
	XHR.appendData('AsCategoriesAppliance','0');
	XHR.appendData('AsTransparentProxy','0');
	XHR.appendData('AsMetaServer','0');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);		
}
		
function TransparentProxy(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
	XHR.appendData('AsHapProxyAppliance','0');
	XHR.appendData('AsCategoriesAppliance','0');
	XHR.appendData('AsReverseProxyAppliance','0');
	XHR.appendData('AsTransparentProxy','1');
	XHR.appendData('AsMetaServer','0');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);		
}
function MetaServer(){
	var XHR = new XHRConnection();
	XHR.appendData('CHOOSE',1);
	XHR.appendData('AsHapProxyAppliance','0');
	XHR.appendData('AsCategoriesAppliance','0');
	XHR.appendData('AsReverseProxyAppliance','0');
	XHR.appendData('AsTransparentProxy','0');
	XHR.appendData('AsMetaServer','1');
	XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xchoose);		
}		
		
		
	document.getElementById('content').style.height='1425px';
	</script>
			
	";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}


function setup_active_directory(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$savedsettings_encoded=urlencode($_GET["savedsettings"]);
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$setupWebFILTER=0;
	if($users->APP_UFDBGUARD_INSTALLED){
		$setupWebFILTER=1;
	}
	
	$AsHapProxyAppliance=intval($savedsettings["AsHapProxyAppliance"]);
	$AsCategoriesAppliance=intval($savedsettings["AsCategoriesAppliance"]);
	$transparent_proxy=intval($savedsettings["AsTransparentProxy"]);
	$AsReverseProxyAppliance=intval($savedsettings["AsReverseProxyAppliance"]);
	$AsMetaServer=intval($savedsettings["AsMetaServer"]);
	
	
	if($AsCategoriesAppliance==1){
		AsCategoriesAppliance();
		return;
	}
	if($AsHapProxyAppliance==1){
		echo "<script>LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}	
	
	if($AsMetaServer==1){
		echo "<script>LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');</script>";
		return;		
	}
	
	if($transparent_proxy==1){
		echo "<script>LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}
	
	if($AsReverseProxyAppliance==1){
		echo "<script>LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');</script>";
		return;
	}	
	
	
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$severtype["WIN_2003"]="Windows 2003";
	$severtype["WIN_2008AES"]="Windows 2008 with AES";
	$users=new usersMenus();

	
	
	$hostname_domain=$savedsettings["domain"];
	if(!isset($array["WINDOWS_DNS_SUFFIX"])){
		$array["WINDOWS_DNS_SUFFIX"]=$hostname_domain;
	}
	
	
	$hostname_domain_TR=explode(".",$hostname_domain);
	if(!isset($array["ADNETBIOSDOMAIN"])){
		$array["ADNETBIOSDOMAIN"]=strtoupper($hostname_domain_TR[0]);
	}
	
	if(!isset($array["COMPUTER_BRANCH"])){
		$array["COMPUTER_BRANCH"]="CN=Computers";
	}
	
	$html="
<div style='width:98%;visibility:hidden' class=form>
<table style='width:100%'>
<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'>
	<div style='font-size:50px;margin-bottom:30px;'>{join_active_directory}</div>
</tr>	
	<tr>
		<td class=legend style='font-size:25px' nowrap>{EnableWindowsAuthentication}:</td>
		<td>". Field_checkbox("EnableKerbAuth",1,"$EnableKerbAuth","EnableKerbAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>			
<tr> 
		<td class=legend style='font-size:25px'>{WINDOWS_DNS_SUFFIX}:</td>
		<td>". Field_text("WINDOWS_DNS_SUFFIX",$array["WINDOWS_DNS_SUFFIX"],"font-size:25px;padding:3px;width:290px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{WINDOWS_SERVER_NETBIOSNAME}:</td>
		<td>". Field_text("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"],"font-size:25px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:25px'>{ADNETBIOSDOMAIN}:</td>
		<td>". Field_text("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"],"font-size:25px;padding:3px;width:290px")."</td>
		<td>". help_icon("{howto_ADNETBIOSDOMAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{ADNETIPADDR}:</td>
		<td>". field_ipv4("ADNETIPADDR",$array["ADNETIPADDR"],"font-size:25px")."</td>
		<td>". help_icon("{howto_ADNETIPADDR}")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:25px'>{WINDOWS_SERVER_TYPE}:</td>
		<td>". Field_array_Hash($severtype,"WINDOWS_SERVER_TYPE",$array["WINDOWS_SERVER_TYPE"],
				"style:font-size:25px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{COMPUTERS_BRANCH}:</td>
		<td>". Field_text("COMPUTER_BRANCH",$array["COMPUTER_BRANCH"],"font-size:25px;padding:3px;width:290px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:25px'>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$array["WINDOWS_SERVER_ADMIN"],"font-size:25px;padding:3px;width:290px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:25px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$array["WINDOWS_SERVER_PASS"],"font-size:25px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>			
	
	<tr>
		<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","30px")."</td>
		<td>&nbsp;</td>
		<td  align='right'>". button("{next}","JoinActiveDirectory()","30px")."</td>
	</tr>
	</table>
	</div>	
	<script>
		var xJoinActiveDirectory= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			var setupWebFILTER=$setupWebFILTER;
			LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');
			
		}
			
		
		function EnableKerbAuthCheck(){
			document.getElementById('WINDOWS_SERVER_PASS').disabled=true;
			document.getElementById('WINDOWS_SERVER_ADMIN').disabled=true;
			document.getElementById('WINDOWS_DNS_SUFFIX').disabled=true;
			document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=true;
			document.getElementById('ADNETBIOSDOMAIN').disabled=true;
			document.getElementById('ADNETIPADDR').disabled=true;
			document.getElementById('COMPUTER_BRANCH').disabled=true;
			document.getElementById('WINDOWS_SERVER_TYPE').disabled=true;
			
			
			if(document.getElementById('EnableKerbAuth').checked){
				document.getElementById('WINDOWS_SERVER_PASS').disabled=false;
				document.getElementById('WINDOWS_SERVER_ADMIN').disabled=false;
				document.getElementById('WINDOWS_DNS_SUFFIX').disabled=false;
				document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=false;
				document.getElementById('ADNETBIOSDOMAIN').disabled=false;
				document.getElementById('ADNETIPADDR').disabled=false;
				document.getElementById('COMPUTER_BRANCH').disabled=false;
				document.getElementById('WINDOWS_SERVER_TYPE').disabled=false;		
			
			}
		
		}

		
		function JoinActiveDirectory(){
			EnableKerbAuth=0;
			if(document.getElementById('EnableKerbAuth').checked){EnableKerbAuth=1;}
			var XHR = new XHRConnection();
			XHR.appendData('EnableKerbAuth',EnableKerbAuth);
			XHR.appendData('WINDOWS_SERVER_PASS',encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value));
			XHR.appendData('WINDOWS_SERVER_ADMIN',document.getElementById('WINDOWS_SERVER_ADMIN').value);
			
			XHR.appendData('WINDOWS_DNS_SUFFIX',document.getElementById('WINDOWS_DNS_SUFFIX').value);
			XHR.appendData('WINDOWS_SERVER_NETBIOSNAME',document.getElementById('WINDOWS_SERVER_NETBIOSNAME').value);
			XHR.appendData('ADNETBIOSDOMAIN',document.getElementById('ADNETBIOSDOMAIN').value);
			XHR.appendData('ADNETIPADDR',document.getElementById('ADNETIPADDR').value);
			XHR.appendData('WINDOWS_SERVER_TYPE',document.getElementById('WINDOWS_SERVER_TYPE').value);
			XHR.appendData('COMPUTER_BRANCH',encodeURIComponent(document.getElementById('COMPUTER_BRANCH').value));
			
			LockPage();
			XHR.sendAndLoad('$page', 'POST',xJoinActiveDirectory);
			
		}
		EnableKerbAuthCheck();
		document.getElementById('content').style.height='685px';
		JoinActiveDirectory();
	</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function AsCategoriesAppliance(){
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$savedsettings_encoded=urlencode($_GET["savedsettings"]);
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td colspan=3 style='padding-top:15px;padding-left:10px;'>
	<div style='font-size:50px;margin-bottom:30px;'>{act_has_categories}</div>
</tr>
<tr>
	<td colspan=2>
				<div style='font-size:22px'>{act_has_categories_explain}</div>
				<center style='margin:30px;width:98%'><img src='img/Categories-server.png'></center>		
	</td>
	<tr>
		<td align='left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings=$savedsettings_encoded')","30px")."</td>
		<td>&nbsp;</td>
		<td  align='right'>". button("{next}","LoadAjax('setup-content','$page?setup-features=yes&savedsettings=$savedsettings_encoded');","30px")."</td>
	</tr>			
</tr>
</table>
</div>		
<script>
	document.getElementById('content').style.height='880px';
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function setup_active_directory_save(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	$_POST["COMPUTER_BRANCH"]=url_decode_special_tool($_POST["COMPUTER_BRANCH"]);
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	unset($_POST["savedsettings"]);
	while (list ($num, $ligne) = each ($_POST) ){
		$array[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
}

function setup_2(){
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>[".__LINE__."] setup_2()</span><br>\n";}
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>[".__LINE__."] articaLang()</span><br>\n";}
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$setup_proxy=0;
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>[".__LINE__."] OK</span><br>\n";}
	
	$netbiosname_field=$tpl->javascript_parse_text("{netbiosname}");
	$domain_field=$tpl->javascript_parse_text("{domain}");
	
	if(count($savedsettings)<3){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>[".__LINE__."] network.php?fqdn=yes</span><br>\n";}
			$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));	
			if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}	
			$arrayNameServers=GetNamesServers();
		
		if(strpos($hostname, '.')>0){
				$Thostname=explode(".", $hostname);
				$netbiosname=$Thostname[0];
				unset($Thostname[0]);
				$domainname=@implode(".", $Thostname);
			}else{
				$netbiosname=$hostname;
			}
			
			if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $netbiosname)){$netbiosname=null;}	
	
	
	}else{
		$netbiosname=$savedsettings["netbiosname"];
		$domainname=$savedsettings["domain"];
		$arrayNameServers[0]=$savedsettings["DNS1"];
		$arrayNameServers[1]=$savedsettings["DNS2"];
	}
	
	if($netbiosname==null){
		$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
		if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}
		if(strpos($hostname, '.')>0){
			$Thostname=explode(".", $hostname);
			$netbiosname=$Thostname[0];
			unset($Thostname[0]);
			$domainname=@implode(".", $Thostname);
		}else{
			$netbiosname=$hostname;
		}
	}
	
	if($arrayNameServers[0]==null){
		$arrayNameServers=GetNamesServers();
	}
	
	$SetupAD=0;
	if($users->SQUID_INSTALLED){
		if($users->SAMBA_INSTALLED){
			$SetupAD=1;
		}
	}
	
	if($users->SQUID_INSTALLED){
		$setup_proxy=1;
		if($users->SQUID_REVERSE_APPLIANCE){
			$setup_proxy=0;
			$proxy="<input type='hidden' id='proxy_listen_port' value='80' name='proxy_listen_port'>";
		}
	}
	

	
	
	
	
	$dhcpd=null;$SERVICES_TITLE=null;
	
	//FIRST_WIZARD_NIC2 -> fini -> demande de reboot
	$t=time();
	
	$IPADDR=$savedsettings["IPADDR"];
	$NETMASK=$savedsettings["NETMASK"];
	$GATEWAY=$savedsettings["GATEWAY"];
	$metric=$savedsettings["metric"];
	$BROADCAST=$savedsettings["BROADCAST"];
	$KEEPNET=$savedsettings["KEEPNET"];
	$NIC=$savedsettings["NIC"];
	$VPS_COMPATIBLE=$savedsettings["VPS_COMPATIBLE"];
	$nic=new system_nic("eth0");
	if($IPADDR==null){$IPADDR=$nic->IPADDR;}
	if($NETMASK==null){$NETMASK=$nic->NETMASK;}
	if($GATEWAY==null){$GATEWAY=$nic->GATEWAY;}
	if($BROADCAST==null){$BROADCAST=$nic->BROADCAST;}
	if($metric==null){$metric=$nic->metric;}
	if(!is_numeric($metric)){$metric=100;}
	if($metric<2){$metric=100;}
	$DISABLED=false;
	if(trim($arrayNameServers[1])==null){$arrayNameServers[1]="8.8.8.8";}
	if(!is_numeric($KEEPNET)){$KEEPNET=0;}
	
	$NICS=new networking();
	$Local_interfaces=$NICS->Local_interfaces(true);
	
	if(count($Local_interfaces)==0){
		$error=FATAL_ERROR_SHOW_128("{unable_to_retreive_network_information_refresh}");
	}
	
	
	
	$timezone=timezonearray();
	for($i=0;$i<count($timezone);$i++){
		$arrayTime[$timezone[$i]]=$timezone[$i];
	}
	
	
	$timezone_def=getLocalTimezone();
	
	$FORM="$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=2 style='font-size:50px;'><div style='margin-bottom:35px'>{serveretdom}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px;vertical-align:top' nowrap>{timezone}:</td>
		<td valign='top'>".Field_array_Hash($arrayTime,"timezones",$timezone_def,null,null,"style:font-size:25px;padding:3px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:25px' nowrap>{netbiosname}:</td>
		<td>". Field_text("hostname_netbios",$netbiosname,"font-size:25px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:25px' nowrap>{DomainOfThisserver}:</td>
		<td>". Field_text("hostname_domain",$domainname,"font-size:25px;width:220px",null,null,null,false,"ChangeQuickHostnameCheck(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 style='font-size:50px;padding-top:50px'>{network}</td>
	</tr>				
	<tr>
		<td colspan=2 style='font-size:18px;font-weight:bolder'><div style='margin-bottom:35px'>{network_settings_will_be_applied_after_reboot}</div></td>
	</tr>
		<tr>
			<td class=legend style='font-size:25px' nowrap>{keep_current_settings}:</td>
			<td>" . Field_checkbox("KEEPNET",1,$KEEPNET,'KeepNetCheck()')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:25px' nowrap>{VPS_COMPATIBLE}:</td>
			<td>" . Field_checkbox("VPS_COMPATIBLE",1,$VPS_COMPATIBLE,'')."</td>
		</tr>
					
		<tr>
			<td class=legend style='font-size:25px' nowrap>{network_interface}:</td>
			<td>" . Field_array_Hash($Local_interfaces,"NIC","$NIC",
					"ChangeWizardNetInterfaces()",null,0,"padding:3px;font-size:25px")."</td>
		</tr>
	</table>
	<br>
	<span id='INTERFACES_OBJECTS'>	
	<table style='width:100%'>							
		<tr>
			<td class=legend style='font-size:25px' nowrap>{tcp_address}:</td>
			<td>" . field_ipv4("IPADDR",$IPADDR,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:25px'>{netmask}:</td>
			<td>" . field_ipv4("NETMASK",$NETMASK,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
			
		<tr>
			<td class=legend style='font-size:25px'>{gateway}:</td>
			<td>" . field_ipv4("GATEWAY",$GATEWAY,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:25px'>{metric}:</td>
			<td>" . field_text("metric-$t",$metric,'padding:3px;font-size:25px;width:90px',null,null,null,false,null,$DISABLED)."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:25px'>{broadcast}:</td>
			<td>" . field_ipv4("BROADCAST",$BROADCAST,'padding:3px;font-size:25px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		</TABLE>
	</SPAN>
	<br>
	<table style='width:100%'>	
	<tr>
		<td colspan=2 style='font-size:50px;'><div style='margin-bottom:35px'>DNS</div></td>
	</tr>					
	<tr>
		<td class=legend style='font-size:25px' nowrap>{primary_dns}:</td>
		<td>". field_ipv4("DNS1", $arrayNameServers[0],"padding:3px;font-size:25px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px' nowrap>{secondary_dns}:</td>
		<td>". field_ipv4("DNS2", $arrayNameServers[1],"padding:3px;font-size:25px")."</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:16px;font-weight:bolder'>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:25px;font-weight:bolder'><div style='text-align:right'><hr>". button("{next}","ChangeQuickHostname()","30px")."</div></td>
	</tr>
	</table>
	</div>
	
	<script>
		var X_ChangeQuickHostname= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			var setup_proxy=$setup_proxy;
			if(setup_proxy==1){
				LoadAjax('setup-content','$page?setup-proxy-type=yes&savedsettings='+results)
				return;
			}			
			
			var SetupAD=$SetupAD;
			if(SetupAD==1){
				LoadAjax('setup-content','$page?setup-active-directory=yes&savedsettings='+results)
				return;
			}
				LoadAjax('setup-content','$page?setup-3=yes&savedsettings='+results)
			}
			
		function ChangeQuickHostnameCheck(e){
			if(checkEnter(e)){ChangeQuickHostname();}
		}
		
		function ChangeWizardNetInterfaces(){
			var nic=document.getElementById('NIC').value;
			LoadAjax('INTERFACES_OBJECTS','$page?setup-nic=yes&t=$t&NIC='+nic)
		
		}
		
		function KeepNetCheck(){
			document.getElementById('NIC').disabled=false;
			document.getElementById('hostname_netbios').disabled=false;
			document.getElementById('hostname_domain').disabled=false;
			document.getElementById('IPADDR').disabled=false;
			document.getElementById('NETMASK').disabled=false;
			document.getElementById('GATEWAY').disabled=false;
			document.getElementById('BROADCAST').disabled=false;
			document.getElementById('metric-$t').disabled=false;	
			document.getElementById('DNS1').disabled=false;
			document.getElementById('DNS2').disabled=false;
			document.getElementById('VPS_COMPATIBLE').disabled=false;
			
			
			if(document.getElementById('KEEPNET').checked){
				document.getElementById('NIC').disabled=true;
				document.getElementById('VPS_COMPATIBLE').disabled=true;
				document.getElementById('IPADDR').disabled=true;
				document.getElementById('NETMASK').disabled=true;
				document.getElementById('GATEWAY').disabled=true;
				document.getElementById('BROADCAST').disabled=true;
				document.getElementById('metric-$t').disabled=true;	
				document.getElementById('DNS1').disabled=true;
				document.getElementById('DNS2').disabled=true;
				document.getElementById('hostname_netbios').disabled=true;
				document.getElementById('hostname_domain').disabled=true;				
			
			}
		
		}

		
		function ChangeQuickHostname(){
			KEEPNET=0;
			VPS_COMPATIBLE=0;
			if(document.getElementById('KEEPNET').checked){KEEPNET=1;}
			if(document.getElementById('VPS_COMPATIBLE').checked){VPS_COMPATIBLE=1;}
			var XHR = new XHRConnection();
			var netbios=document.getElementById('hostname_netbios').value;
			var dom=document.getElementById('hostname_domain').value;
			if(KEEPNET==0){
				if(netbios.length==0){alert('$netbiosname_field (Null!)');return;}
				if(dom.length==0){alert('$domain_field (Null!)');return;}
				if(dom=='localhost.localdomain'){alert('localhost.localdomain wrong domain...');return;}
			}
			
			
			
			if(document.getElementById('proxy_listen_port')){
				XHR.appendData('proxy_listen_port',document.getElementById('proxy_listen_port').value);
			}
			if(document.getElementById('EnablePDNS')){
				var EnablePDNS=0;
				if(document.getElementById('EnablePDNS').checked){EnablePDNS=1;}
				XHR.appendData('EnablePDNS',EnablePDNS);
			}

			if(document.getElementById('EnableFreeRadius')){
				var EnableFreeRadius=0;
				if(document.getElementById('EnableFreeRadius').checked){EnableFreeRadius=1;}
				XHR.appendData('EnableFreeRadius',EnableFreeRadius);
			}

			if(document.getElementById('EnableDHCPServer')){
				XHR.appendData('EnableDHCPServer',document.getElementById('EnableDHCPServer').value);
			}
			


			if(document.getElementById('EnableWebFiltering')){
				XHR.appendData('EnableWebFiltering',document.getElementById('EnableWebFiltering').value);
			}

			if(document.getElementById('AsCategoriesAppliance')){
				XHR.appendData('AsCategoriesAppliance',document.getElementById('AsCategoriesAppliance').value);
			}			
			

			if(document.getElementById('timezones')){
				XHR.appendData('timezones',document.getElementById('timezones').value);
			}
			
			
			 
			XHR.appendData('KEEPNET',KEEPNET);
			
			if(KEEPNET==0){ 
				XHR.appendData('VPS_COMPATIBLE',VPS_COMPATIBLE);
				XHR.appendData('NIC',document.getElementById('NIC').value);
				XHR.appendData('IPADDR',document.getElementById('IPADDR').value);
				XHR.appendData('NETMASK',document.getElementById('NETMASK').value);  
				XHR.appendData('GATEWAY',document.getElementById('GATEWAY').value);
				XHR.appendData('BROADCAST',document.getElementById('BROADCAST').value);
				XHR.appendData('metric',document.getElementById('metric-$t').value);          
				XHR.appendData('DNS1',document.getElementById('DNS1').value);
				XHR.appendData('DNS2',document.getElementById('DNS2').value);
				XHR.appendData('netbiosname',netbios);
				XHR.appendData('domain',dom);
			}
			
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			AnimateDiv('setup-content');
			LockPage();
			XHR.sendAndLoad('$page', 'POST',X_ChangeQuickHostname);
			
		}
		KeepNetCheck();
		document.getElementById('content').style.height='1250px';
	</script>
	
	";
	
	$html="
	<div style='font-size:35px;font-weight:bolder;margin-bottom:10px'>{squid_net_simple}</div>
	$FORM
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function setup_3(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	$sock=new sockets();
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if($EnableUfdbGuard<>$savedsettings["EnableWebFiltering"]){$savedsettings["EnableWebFiltering"]=$EnableUfdbGuard;}
	
	$please_fill_all_form_values=$tpl->javascript_parse_text("{please_fill_all_form_values}");
	$organization=$savedsettings["organization"];
	$employees=$savedsettings["employees"];
	$company_name=$savedsettings["company_name"];
	$country=$savedsettings["country"];
	$city=$savedsettings["city"];
	$mail=$savedsettings["mail"];
	$telephone=$savedsettings["telephone"];
	$UseServerV=$savedsettings["UseServer"];
	$smtp_domainname=$savedsettings["smtp_domainname"];
	$company_www=$savedsettings["company_www"];
	$GoldKey=$savedsettings["GoldKey"];
	$KEEPNET=$savedsettings["KEEPNET"];
	if(!is_numeric($KEEPNET)){$KEEPNET=0;}
	$t=time();
	$MBX=false;
	$Postfix=false;
	$Samba=false;
	$squid_installed=false;
	$nginx_installed=false;
	$UseServer[null]="{select}";
	if($users->POSTFIX_INSTALLED){$Postfix=true;}
	if($users->ZARAFA_INSTALLED){$MBX=true;}
	if($users->cyrus_imapd_installed){$MBX=true;}
	if($MBX){
		$UseServer["ASMAIL"]="{mail_server}";
	}
	
	if($users->SAMBA_INSTALLED){$Samba=true;}
	if($users->SQUID_INSTALLED){$squid_installed=true;}
	if($users->NGINX_INSTALLED){$nginx_installed=true;}
	
	if($Postfix){$UseServer["ASRELAY"]="{relay_server}";}
	if($Samba){$UseServer["ASFILE"]="{file_server}";}
	if($squid_installed){$UseServer["ASPROXY"]="{proxy_server}";}
	if($nginx_installed){$UseServer["ASREVERSEPROXY"]="{reverse_proxy_server}";}
	$UseServer["AS_WORDPRESS"]="Wordpress appliance";
	
	
	
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];	
	$page=CurrentPageName();
	$tpl=new templates();
	if($KEEPNET==0){
		$resolv=new resolv_conf();
		$resolv->MainArray["DNS1"]=$arrayNameServers[0];
		$resolv->MainArray["DNS2"]=$arrayNameServers[1];
		$resolv->save();
	}

	if($KEEPNET==0){
		if($_POST["IPADDR"]<>null){
			$nics=new system_nic("eth0");
			$nics->eth="ethO";
			$nics->IPADDR=$arrayNameServers["IPADDR"];
			$nics->NETMASK=$arrayNameServers["NETMASK"];
			$nics->GATEWAY=$arrayNameServers["GATEWAY"];
			$nics->BROADCAST=$arrayNameServers["BROADCAST"];
			$nics->DNS1=$arrayNameServers[0];
			$nics->DNS2=$arrayNameServers[1];
			$nics->dhcp=0;
			$nics->metric=$savedsettings["metric"];
			$nics->enabled=1;
			$nics->NoReboot=true;
			$nics->SaveNic();
		}
	}
	
	
	
	$UseServerF=Field_array_Hash($UseServer, "UseServer",$UseServerV,"style:font-size:25px");
	$UseServerFF="	
	</tr>
		<td class=legend style='font-size:25px'>{you_using_this_server_for}:</td>
		<td>$UseServerF</td>
	</tr>";
	
	if($users->WORDPRESS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Wordpress Appliance'>";
	}	
	
	if($users->SMTP_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='SMTP Relay Appliance'>";
	}
	if($users->KASPERSKY_WEB_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Kaspersky Web Appliance'>";
	}	
	if($users->LOAD_BALANCE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Load balance Appliance'>";
	}		
	if($users->ZARAFA_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Zarafa Appliance'>";
	}		
	if($users->SAMBA_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='File Sharing Appliance'>";
	}		
	if($users->WEBSTATS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Web statistics Appliance'>";
	}

	if($users->STATS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Statistics Appliance'>";
	}	
	
	if($users->KASPERSKY_SMTP_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Kaspersky SMTP Appliance'>";
	}	
	if($users->APACHE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Apache Appliance'>";
	}	
	if($users->SQUID_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Proxy Appliance'>";
	}		
	if($users->HAPRROXY_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Load balance Appliance'>";
	}		
	if($users->FULL_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Artica Full Appliance'>";
	}
	
	if($users->MYCOSI_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='MyCOSI Appliance'>";
	}
	
	if($users->CYRUS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='IMAP-POP3 OpenSource Appliance'>";
	}

	if($users->PROXYTINY_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Tiny Proxy Appliance'>";
	}	

	if($users->SQUID_REVERSE_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Reverse Proxy Appliance' >";
	}	
	
	if($users->GATEWAY_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Gateway Appliance' >";
	}	
	
	if($users->WORDPRESS_APPLIANCE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Wordpress Appliance' >";
	}	
	if($users->WEBSECURIZE){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='WebSecurize Appliance' >";
	}

	if($users->BAMSIGHT){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='BamSight' >";
	}
	
	if($users->LANWANSAT){
		$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='LANWANSAT Appliance' >";
	}
	
	//toujours à la fin...
	if($UseServerFF==null){
		if($users->FROM_SETUP){
			$UseServerFF="<input type='hidden' id='UseServer' name='UseServer' value='Installed from Setup'>";
		}
	}
	
	$noticeregisterform="<div style='font-size:11px;text-align:right'>{noticeregisterform}</div>";
	if($users->WEBSECURIZE){$noticeregisterform=null;}
	if($users->LANWANSAT){$noticeregisterform=null;}
	if($users->BAMSIGHT){$noticeregisterform=null;}
	
	$company_name_txtjs=$tpl->javascript_parse_text("{company_name}");
	$FORM="
	<div style='width:98%' class=form>
	<table style='width:100%' id='$t'>
	
	<tr>
		<td colspan=2 style='font-size:50px;font-weight:bolder'>{YourRealCompany}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{gold_license}:</td>
		<td>". Field_text("GoldKey",$GoldKey,"font-size:25px;width:440px")."</td>
	</tr>
		<tr><td colspan=2 align='right'><i>{gold_license_explain}</i></td></tr>	
	<tr>
		<td class=legend style='font-size:25px'>{company_name}:</td>
		<td>". Field_text("company_name",$company_name,"font-size:25px;width:440px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{company_website}:</td>
		<td>". Field_text("company_www",$company_www,"font-size:25px;width:440px")."</td>
	</tr>				
	</tr>
		<td class=legend style='font-size:25px'>{country}:</td>
		<td>". Field_text("country",$country,"font-size:25px;width:440px")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:25px'>{city}:</td>
		<td>". Field_text("city",$city,"font-size:25px;width:440px")."</td>
	</tr>
					
	</tr>
		<td class=legend style='font-size:25px'>{your_email_address}:</td>
		<td>". Field_text("mail",$mail,"font-size:25px;width:440px")."</td>
	</tr>	
	</tr>
		<td class=legend style='font-size:25px'>{phone_title}:</td>
		<td>". Field_text("telephone",$telephone,"font-size:25px;width:440px")."</td>
	</tr>
	</tr>
		<td class=legend style='font-size:25px'>{nb_employees}</td>
		<td>". Field_text("employees",$employees,"font-size:25px;width:440px")."</td>
	</tr>

	$UseServerFF
	
	<tr>
		<td colspan=2 style='font-size:25px;font-weight:bolder'>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=2 style='font-size:50px;font-weight:bolder'>{virtual_company}</div></td>
	</tr>	
	</tr>
		<td class=legend style='font-size:25px'>{organization}:</td>
		<td>". Field_text("organization",$organization,"font-size:25px;width:440px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:25px'>{domain}:</td>
		<td>". Field_text("smtp_domainname",$smtp_domainname,"font-size:25px;width:440px",null,null,null,false,"CheckMyForm$t(event)")."</td>
	</tr>	
		<tr>
		<td colspan=2 style='font-size:25px;font-weight:bolder'>&nbsp;</td>
	</tr>
	<tr>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:left'>". button("{back}","LoadAjax('setup-content','$page?setup-2=yes&savedsettings={$_GET["savedsettings"]}')","30px")."</div></td>
		<td style='font-size:16px;font-weight:bolder'><div style='text-align:right'>". button("{next}","ChangeCompanySettings()","30px")."</div></td>
	</tr>
	</table>
	$noticeregisterform
	
	<script>
		var X_ChangeCompanySettings= function (obj) {
			UnlockPage();
			var results=obj.responseText;
			var KEEPNET=$KEEPNET;
			if(KEEPNET==0){
				LoadAjax('setup-content','$page?setup-4=yes&savedsettings='+results);
			}else{
				LoadAjax('setup-content','$page?setup-5=yes&savedsettings='+results);
			}
		
			}
		
			
		function CheckMyForm$t(e){
			if(!checkEnter(e)){return;}
		}
		
		function ChangeCompanySettings(){
			var XHR = new XHRConnection();
			var testval=document.getElementById('company_name').value;
			if(testval.length==0){alert('$please_fill_all_form_values: $company_name_txtjs');return;}
			var testval=document.getElementById('country').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('city').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}						
			var testval=document.getElementById('mail').value;
			if(testval.length==0){alert('$please_fill_all_form_values - mail');return;}
			var testval=document.getElementById('employees').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('organization').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}
			var testval=document.getElementById('smtp_domainname').value;
			if(testval.length==0){alert('$please_fill_all_form_values');return;}			
			
			XHR.appendData('company_name',encodeURIComponent(document.getElementById('company_name').value));
			XHR.appendData('city',encodeURIComponent(document.getElementById('city').value));
			XHR.appendData('organization',encodeURIComponent(document.getElementById('organization').value));
			XHR.appendData('country',encodeURIComponent(document.getElementById('country').value));
			XHR.appendData('smtp_domainname',document.getElementById('smtp_domainname').value);
			XHR.appendData('organization',document.getElementById('organization').value);
			XHR.appendData('mail',document.getElementById('mail').value);
			XHR.appendData('telephone',document.getElementById('telephone').value);
			XHR.appendData('employees',document.getElementById('employees').value);
			XHR.appendData('company_www',document.getElementById('company_www').value);
			XHR.appendData('EnableWebFiltering','{$savedsettings["EnableWebFiltering"]}');
			XHR.appendData('GoldKey',document.getElementById('GoldKey').value);
			
			
			
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			LockPage();
			XHR.sendAndLoad('$page', 'POST',X_ChangeCompanySettings);
			
		}
	document.getElementById('content').style.height='930px';
	</script>
	
	";	
	
	
	$html="
	
	<div style='font-size:32px;font-weight:bolder;margin-bottom:50px'>{ContactAndOrganization}</div>$FORM
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_POST["savedsettings"]));
	
	unset($_POST["savedsettings"]);
	
	if(isset($_POST["company_name"])){$_POST["company_name"]=url_decode_special_tool($_POST["company_name"]);}
	if(isset($_POST["city"])){$_POST["city"]=url_decode_special_tool($_POST["city"]);}
	if(isset($_POST["organization"])){$_POST["organization"]=url_decode_special_tool($_POST["organization"]);}
	if(isset($_POST["country"])){$_POST["country"]=url_decode_special_tool($_POST["country"]);}

	if(isset($_POST["ArticaMetaHost"])){$sock->SET_INFO("ArticaMetaHost",$_POST["ArticaMetaHost"]);}
	if(isset($_POST["EnableArticaMetaClient"])){$sock->SET_INFO("EnableArticaMetaClient",$_POST["EnableArticaMetaClient"]);}
	if(isset($_POST["ArticaMetaPort"])){$sock->SET_INFO("ArticaMetaPort",$_POST["ArticaMetaPort"]);}
	if(isset($_POST["ArticaMetaUsername"])){$sock->SET_INFO("ArticaMetaUsername",$_POST["ArticaMetaUsername"]);}
	if(isset($_POST["ArticaMetaPassword"])){$sock->SET_INFO("ArticaMetaPassword",url_decode_special_tool($_POST["ArticaMetaPassword"]));}
	if(isset($_POST["ArticaMetaServerPassword"])){$sock->SET_INFO("ArticaMetaServerPassword",url_decode_special_tool($_POST["ArticaMetaServerPassword"]));}
	if(isset($_POST["ArticaMetaServerUsername"])){$sock->SET_INFO("ArticaMetaServerUsername",$_POST["ArticaMetaServerUsername"]);}
	
	
	if(isset($_POST["statsadministratorpass"])){$_POST["statsadministratorpass"]=url_decode_special_tool($_POST["statsadministratorpass"]);}
	if(isset($_POST["administrator"])){$_POST["administrator"]=url_decode_special_tool($_POST["administrator"]);}
	if(isset($_POST["administratorpass"])){$_POST["administratorpass"]=url_decode_special_tool($_POST["administratorpass"]);}
	
	
	while (list ($key, $value) = each ($_POST) ){
		$value=str_replace("___.___.___.___", "", $value);
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $value)){
			$pr=explode(".",$value);
			while (list ($index, $number) = each ($pr) ){$pr[$index]=intval($number);}
			$value=@implode(".", $pr);
		}
		
		$savedsettings[$key]=$value;
	}
	$GLOBALS["TIMEZONES"]=$_POST["timezones"];
	$_SESSION["TIMEZONES"]=$_POST["timezones"];
	if(isset($_POST["timezones"])){$sock->SET_INFO("timezones",$_POST["timezones"]);}
	$timezoneenc=urlencode(base64_encode(trim($_POST["timezone"])));
	$data=$sock->getFrameWork("system.php?zoneinfo-set=$timezoneenc");
	
	$savedsettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	$Encoded=base64_encode(serialize($savedsettings));
	$sock->SET_INFO("WizardSavedSettings", $Encoded);
	echo $Encoded;
	
	
}

function setup_4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	$CPU=$users->CPU_NUMBER;
	$memory=intval($sock->getFrameWork("services.php?total-memory=yes"));
	if($memory==0){$memory=intval($sock->getFrameWork("services.php?total-memory=yes"));}
	if($memory==0){$memory=round($users->MEM_TOTAL_INSTALLEE/1024);}
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	
	
	$ArticaMetaUsername=$sock->GET_INFO("ArticaMetaUsername");
	$ArticaMetaPassword=$sock->GET_INFO("ArticaMetaPassword");
	$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
	$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
	$ArticaMetaServerUsername=$sock->GET_INFO("ArticaMetaServerUsername");
	$ArticaMetaServerPassword=$sock->GET_INFO("ArticaMetaServerPassword");
	if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
	
	
	$WIZMEM=false;
	$wizard_warn_memory=$tpl->_ENGINE_parse_body("{wizard_warn_memory}");
	if($users->PROXYTINY_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}
	if($users->SAMBA_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}	
	if($users->SMTP_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->LOAD_BALANCE_APPLIANCE){
		if($memory<750){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "750M", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->LOAD_BALANCE_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}

	if($users->APACHE_APPLIANCE){
		if($memory<1000){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
			$WIZMEM=true;
		}
	}	
	
	
	if(!$WIZMEM){
		if( ($memory<2450) OR ($CPU<2)){
			$wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
			$wizard_warn_memory=str_replace("%F", "2.5G", $wizard_warn_memory);
			$WIZMEM=true;
			
			
			$error_style="color: #b94a48;margin:5px;
	padding:3px;
	border:1px solid #E40501;
	border-radius:5px 5px 5px 5px;
	 -moz-border-radius:5px;
	-webkit-border-radius:5px;
    background-color: #F7E5D9;
 	font-weight:bold;
    font-size: 16px;
    margin-bottom: 20px;
    padding: 8px 35px 8px 14px;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);";
			
			$warn_memory="<div style='$error_style'>$wizard_warn_memory</div>
			
			";
			
			if($users->SQUID_INSTALLED){
				$sock->SET_INFO("EnableUfdbGuard", "0");
				$savedsettings["EnableWebFiltering"]=0;
			}
			
			$sock->SET_INFO("EnableArpDaemon", 0);
			$sock->SET_INFO("EnablePHPFPM",0);
			$sock->SET_INFO("EnableFreeWeb",0);
			$sock->SET_INFO("SlapdThreads", 2);
			$sock->SET_INFO("EnableVnStat", 0);
			
			
			$Encoded=base64_encode(serialize($savedsettings));
			
			$sock->SET_INFO("WizardSavedSettings", $Encoded);
			$sock->getFrameWork("services.php?restart-arpd=yes");
			$f[]="[MYSQL]";
			$f[]="default-character-set=";
			$f[]="bind-address=";
			$f[]="key_buffer=";
			$f[]="tmp_table_size=64";
			$f[]="max_allowed_packet=100";
			$f[]="sort_buffer_size=1";
			$f[]="key_buffer_size=32";
			$f[]="innodb_log_file_size=";
			$f[]="net_buffer_length=";
			$f[]="join_buffer_size=";
			$f[]="thread_cache_size=";
			$f[]="query_cache_limit=";
			$f[]="max_heap_table_size=";
			$f[]="sort_buffer=";
			$f[]="innodb_lock_wait_timeout=";
			$f[]="open_files_limit=";
			$f[]="skip_external_locking=yes";
			$f[]="skip_name_resolve=no";
			$f[]="table_cache=512";
			$f[]="table_open_cache=256";
			$f[]="read_buffer_size=0.5";
			$f[]="read_rnd_buffer_size=1";
			$f[]="myisam_sort_buffer_size=64";
			$f[]="query_cache_size=4";
			$f[]="thread_stack=0.192";
			$f[]="max_tmp_table_size=0";
			$f[]="innodb_buffer_pool_size=42";
			$f[]="innodb_additional_mem_pool_size=3";
			$f[]="innodb_log_buffer_size=3";
			$f[]="max_connections=55";
			$f[]="instance-id=";
			$sock->SaveConfigFile(@implode("\n", $f), "MysqlParameters");
			$sock->getFrameWork("cmd.php?restart-mysql=yes");
			$f=array();			
		
			
			
			
		}
		

	
	}
	
	if($savedsettings["company_www"]<>null){
		if(preg_match("#^http#", $savedsettings["company_www"])){
			$parse_url=parse_url( $savedsettings["company_www"]);
			$domainname=$parse_url["host"];
		}else{
			$domainname=$savedsettings["company_www"];
		}
		
	}
	
	if(preg_match("#^www\.(.+)#", $domainname,$re)){$domainname=$re[1];}
	
	
	if($savedsettings["adminwebserver"]==null){
		$domainname=$savedsettings["domain"];
		$savedsettings["adminwebserver"]="admin.$domainname";
	}
	
	if($savedsettings["second_webadmin"]==null){
		$savedsettings["second_webadmin"]="manager.$domainname";
	}
	
	if($users->SQUID_INSTALLED){
		$sock->getFrameWork("cmd.php?sysctl-setvalue=5&key=".base64_encode("vm.swappiness"));
	}
	$t=time();
	if($savedsettings["administrator"]==null){$savedsettings["administrator"]="Manager";}
	if($savedsettings["administratorpass"]==null){$savedsettings["administratorpass"]="secret";}
	
	$superAdminForm="
	<div style='font-size:50px;font-weight:bolder;margin-bottom:10px;margin-top:25px'>{artica_manager}:</div>
	<div style='font-size:18px' class=explain>{miniadm_wizard_admin_explain}</div>
	<p>&nbsp;</p>
	<table style='width:100%'>	
				<tr>
					<td class=legend style='font-size:25px'>{username}:</td>
					<td style='font-size:14px'>". Field_text("administrator",$savedsettings["administrator"],"font-size:25px;width:340px")."</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:25px'>{password}:</td>
					<td style='font-size:14px'>". Field_password("administratorpass",$savedsettings["administratorpass"],"font-size:25px;width:340px")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:25px'>{confirm}:</td>
					<td style='font-size:14px'>". Field_password("administratorpass2",$savedsettings["administratorpass"],"font-size:25px;width:340px")."</td>
				</tr>								
		</table>
	";
	
	
	$miniadm_form="<div style='width:98%' class=form>
	<div style='font-size:50px;font-weight:bolder;margin-bottom:10px;margin-top:25px'>End-Users WebAccess</div>
		<div style='font-size:18px' class=explain>{miniadm_wizard_explain}<p>
		{miniadm_wizard_explain2}</p></div>
		<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:25px'>{webserver}:</td>
					<td style='font-size:25px'>http://". Field_text("adminwebserver",$savedsettings["adminwebserver"],"font-size:25px;width:450px")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:25px'>{second_webadmin}:</td>
					<td style='font-size:25px'>http://". Field_text("second_webadmin",$savedsettings["second_webadmin"],"font-size:25px;width:450px")."</td>
				</tr>							
							
							
				<tr>
					<td colspan=2><div style='font-size:50px;margin-bottom:30px;margin-top:30px'>{statistics_administrator}:</div>
				</tr>
					<td class=legend style='font-size:25px'>{username}:</td>
					<td style='font-size:14px'>". Field_text("statsadministrator",$savedsettings["statsadministrator"],"font-size:25px;width:280px")."</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:25px'>{password}:</td>
					<td style='font-size:14px'>". Field_password("statsadministratorpass",$savedsettings["statsadministratorpass"],"font-size:25px;width:170px")."</td>
				</tr>	
						
		</table>
	</div>";
	$miniadm_form=null;
	
	$meta_client_form="	<div style='font-size:50px;font-weight:bolder;margin-bottom:10px'>{artica_meta} - Central Management</div>
		". Paragraphe_switch_img("{enable_artica_meta}", "{artica_meta_client_howto}",
				"EnableArticaMetaClient-$t",$EnableArticaMetaClient,null,850)."
		
	
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:25px'>{hostname}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaHost-$t",$ArticaMetaHost,"font-size:25px;width:240px")."</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:25px'>{port}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaPort-$t",$ArticaMetaPort,"font-size:25px;width:110px")."</td>
		</tr>				
			
		<tr>
			<td class=legend style='font-size:25px'>{username}:</td>
			<td style='font-size:18px'>". Field_text("username-$t",$ArticaMetaUsername,"font-size:25px;width:240px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:25px'>{password}:</td>
			<td style='font-size:18px'>". Field_password("password-$t",$ArticaMetaPassword,"font-size:25px;width:240px")."</td>
		</tr>
		</table>
	</div>";
	
	
	$size_form="1205px";
	
	$meta_server_form="
			<div style='font-size:50px;font-weight:bolder;margin-bottom:10px'>{artica_meta}</div>
			<div style='font-size:18px;margin-bottom:20px'>{artica_meta_howto}</div>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:25px'>{username}:</td>
			<td style='font-size:25px'>". Field_text("ArticaMetaServerUsername-$t",$ArticaMetaServerUsername,"font-size:25px;width:240px")."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:25px'>{password}:</td>
			<td style='font-size:25px'>". Field_password("ArticaMetaServerPassword-$t",$ArticaMetaServerPassword,"font-size:25px;width:240px")."</td>		
		</tr>				
	</table>		
			
			
			
	";
	
	
	
	$password_mismatch=$tpl->javascript_parse_text("{artica_manager}: {password_mismatch}");
	$AsCategoriesAppliance=intval($savedsettings["AsCategoriesAppliance"]);
	$AsReverseProxyAppliance=intval($savedsettings["AsReverseProxyAppliance"]);
	$AsMetaServer=intval($savedsettings["AsMetaServer"]);
	$AsHapProxyAppliance=intval($savedsettings["AsHapProxyAppliance"]);
	if($AsHapProxyAppliance==1){$miniadm_form=null;$size_form="1205px";}
	if($AsCategoriesAppliance==1){$miniadm_form=null;$size_form="1205px";}
	if($AsReverseProxyAppliance==1){$miniadm_form=null;$size_form="1205px";}
	if($AsMetaServer==1){$miniadm_form=null;$meta_client_form=null;$size_form="1205px";}
	if($AsMetaServer==0){$meta_server_form=null;}
	
	
	
	
	$html="
	$warn_memory
	<div style='width:98%' class=form>
		$superAdminForm
	</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	$meta_client_form
	<p>&nbsp;</p>
	$meta_server_form
	<p>&nbsp;</p>

	<table style='width:100%'>
				<tr><td colspan=2><p>&nbsp;</p></td></tr>
				<tr>
					<td style='font-size:14px;font-weight:bolder'><div style='text-align:left'>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","30px")."<div></td>
					<td style='font-size:14px;font-weight:bolder'><div style='text-align:right'>". button("{build_parameters}","ChangeWebAccess()","30px")."</div></td>
				</tr>	
	</table>
	<script>
		var XChangeWebAccess= function (obj) {
			var results=obj.responseText;
			LoadAjax('setup-content','$page?setup-5=yes&savedsettings='+results);
		
			}
		
		
		function ChangeWebAccess(){
			var XHR = new XHRConnection();
			XHR.appendData('savedsettings','{$_GET["savedsettings"]}');
			if(document.getElementById('EnableArticaMetaClient-$t')){
				XHR.appendData('EnableArticaMetaClient',document.getElementById('EnableArticaMetaClient-$t').value);
				XHR.appendData('ArticaMetaHost',document.getElementById('ArticaMetaHost-$t').value);
				XHR.appendData('ArticaMetaPort',document.getElementById('ArticaMetaPort-$t').value);
				XHR.appendData('ArticaMetaUsername',document.getElementById('username-$t').value);
				XHR.appendData('ArticaMetaPassword',encodeURIComponent(document.getElementById('password-$t').value));
			}
			
			if(document.getElementById('ArticaMetaServerUsername-$t')){
				XHR.appendData('ArticaMetaServerUsername',document.getElementById('ArticaMetaServerUsername-$t').value);
				XHR.appendData('ArticaMetaServerPassword',encodeURIComponent(document.getElementById('ArticaMetaServerPassword-$t').value));
			}
			
			if(document.getElementById('administrator')){
				XHR.appendData('administrator',encodeURIComponent(document.getElementById('administrator').value));
				if(document.getElementById('administratorpass').value!=document.getElementById('administratorpass2').value){
					alert('$password_mismatch');
					return;
				}
				
				XHR.appendData('administratorpass',encodeURIComponent(document.getElementById('administratorpass').value));
			}
			
		
			if(document.getElementById('adminwebserver')){
				XHR.appendData('adminwebserver',document.getElementById('adminwebserver').value);
				XHR.appendData('second_webadmin',document.getElementById('second_webadmin').value);
				XHR.appendData('statsadministrator',document.getElementById('statsadministrator').value);
				var statsadministratorpass=encodeURIComponent(document.getElementById('statsadministratorpass').value);
				XHR.appendData('statsadministratorpass',statsadministratorpass);
			}
			XHR.sendAndLoad('$page', 'POST',XChangeWebAccess);
			
		}
	document.getElementById('content').style.height='$size_form';
	</script>
			
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

	
function setup_5(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$users=new usersMenus();
	
	if(!isset($_GET["bypass"])){
		if(!check_email_address($savedsettings["mail"])){
			$warn_email_invalid_wizard=$tpl->_ENGINE_parse_body("{warn_email_invalid_wizard}");
			$warn_email_invalid_wizard=str_replace("%s", $savedsettings["mail"], $warn_email_invalid_wizard);
			$html="
			<table style='width:99%' class=form>
			<tr>
				<td valign='top'><img src='img/error-64.png'></td>
				<td style='padding-left:15px'><strong style='font-size:18px'>$warn_email_invalid_wizard</strong>
				<center>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","22px")."</center>
			</td>
			</tr>
			<tr>
			</table>
			<div style='text-align:right;margin-top:20px;font-size:22px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:LoadAjax('setup-content','$page?setup-5=yes&bypass=yes&savedsettings={$_GET["savedsettings"]}');\"
			style='font-size:22px;text-decoration:underline'>{i_understand_continue}...</a>
			</div>						
						
						";
			echo $tpl->_ENGINE_parse_body($html);
			return;
			
		}
	}
	
	$html="
	<div id='settings-final'>
		<table style='width:99%' class=form>
		<tr>
			<td style='padding-left:15px'><strong style='font-size:18px'>{build_parameters}</strong>
			
		</td>
		</tr>
		</table>
	
	<div id='settings-dns'></div>
	<div id='settings-ou'></div>
	</div>
	<center style='margin-top:20px'>". button("{back}","LoadAjax('setup-content','$page?setup-3=yes&savedsettings={$_GET["savedsettings"]}')","22px")."</center>
	<script>
		LoadAjax('settings-dns','$page?settings-dns=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
	
	
}

function dns_save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	
	$KEEPNET=$savedsettings["KEEPNET"];
	$netbiosname=$savedsettings["netbiosname"];
	$domainname=$savedsettings["domain"];
	$arrayNameServers[0]=$savedsettings["DNS1"];
	$arrayNameServers[1]=$savedsettings["DNS2"];
	$page=CurrentPageName();
	$tpl=new templates();	
	if($KEEPNET==0){
		if($savedsettings["DNS1"]==null){
				
			$html="
					<table style='width:99%' class=form>
					<tr>
						<td valign='top'><img src='img/danger64.png'></td>
						<td style='padding-left:15px'><strong style='font-size:18px'>{saving_network_failed}:<br>$netbiosname.$domainname<br>DNS1:{$arrayNameServers[0]}<br>DNS2{$arrayNameServers[1]}</strong>
						
					</td>
					</tr>
					</table>
				";
				echo $tpl->_ENGINE_parse_body($html);	
				return;	
		}
	}	


	
	
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{saving_network_done}:<br>$netbiosname.$domainname<br>{$arrayNameServers[0]}<br>{$arrayNameServers[1]}</strong>
			
		</td>
		</tr>
		</table>
	
	
	<script>
		LoadAjax('settings-ou','$page?settings-ou=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function ou_save(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$page=CurrentPageName();
	$tpl=new templates();	
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));


	
	
$html="
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'><strong style='font-size:18px'>{organization}:{$savedsettings["organization"]}/{$savedsettings["smtp_domainname"]} {success}</strong>
			
		</td>
		</tr>
		</table>
	
	
	<script>
		LoadAjax('settings-final','$page?settings-final=yes&savedsettings={$_GET["savedsettings"]}');
	</script>
	";
	$sock=new sockets();
	$sock->getFrameWork("system.php?wizard-execute=yes");
	echo $tpl->_ENGINE_parse_body($html);	
	 
	
}
function final_show(){
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	include_once(dirname(__FILE__)."/ressources/class.langages.inc");
	$langAutodetect=new articaLang();
	$DetectedLanguage=$langAutodetect->get_languages();
	$GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;		
	$page=CurrentPageName();
	$tpl=new templates();	
	$ldap=new clladp();
	$savedsettings=unserialize(base64_decode($_GET["savedsettings"]));
	$settings_final_show=$tpl->_ENGINE_parse_body("{settings_final_show}");
	$settings_final_show=str_replace("%a", "<strong style='color:#C91111'>{$savedsettings["administrator"]}</strong>", $settings_final_show);
	$settings_final_show=str_replace("%p", "<strong  style='color:#C91111'>{$savedsettings["administratorpass"]}</strong>", $settings_final_show);
	
	$webinterf=array();
	$webinterf[]="<hr>";
	if($savedsettings["adminwebserver"]<>null){
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["adminwebserver"]}</div>";
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> https://{$savedsettings["IPADDR"]}:9000/miniadm.logon.php</div>";
	}
	if($savedsettings["second_webadmin"]<>null){
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["second_webadmin"]}</div>";
		$webinterf[]="<div style='font-size:18px'><strong>WebAdmin Access:</strong> http://{$savedsettings["second_webadmin"]}/miniadm.logon.php</div>";
	}		
		
	$webinterf[]="<div style='font-size:18px'><strong>{artica_manager}:</strong>{$savedsettings["administrator"]}</div>";
	if($savedsettings["adminwebserver"]<>null){	
		if($savedsettings["statsadministrator"]<>null){
			$webinterf[]="<div style='font-size:18px'><strong>WebAccess {username} ({statistics}):</strong>{$savedsettings["statsadministrator"]}</div>";
		}		
	}
$t=time();	
$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
$html="
		

	<center id='title$t' style='font-size:22px;font-weight:bold;margin-bottom:15px'>$pleasewait</center>
	<center style='margin-bottom:20px;margin-top:10px'>
		<div id='Status$t' style='height:50px;'></div>
	</center>


		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/ok64.png'></td>
			<td style='padding-left:15px'>
				<div style='font-size:18px'>$settings_final_show</strong>
				".@implode("\n", $webinterf)."
				
		</td>
		</tr>
		</table>
<script>						
	$('#Status$t').progressbar({ value: 2 });	
	document.getElementById('content').style.height='424px';
	Loadjs('$page?progressbar-js=yes&t=$t');
</script>

";
//<center style='margin:10px'>". button("{close}","YahooSetupControlHide();document.location.href='logon.php'","22px")."

	$sock=new sockets();
	$sock->getFrameWork("system.php?create-new-uuid=yes");
	$sock->getFrameWork("system.php?wizard-execute=yes");
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function progressbar_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	$ARRAY=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/wizard.progress"));
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}");
	if(!is_array($ARRAY)){
	echo "
	function Start$tt(){
		Loadjs('$page?progressbar-js=yes&t=$t');
	}
	document.getElementById('title$t').innerHTML='$please_wait';
	setTimeout('Start$tt()',2000);
	";
	return;
	
	}
	
	$text=$tpl->javascript_parse_text($ARRAY["TEXT"]);
	$prc=$ARRAY["POURC"];
	
	if($prc>99){
		echo "
		document.getElementById('title$t').innerHTML='$text&nbsp;';
		$('#Status$t').progressbar({ value: $prc });
		YahooSetupControlHide();
		document.location.href='logon.php'
		";
		return;
		}
	
	
	
echo "
function Start$tt(){
		Loadjs('$page?ShowProgress-js=yes&t=$t');
}
	
if(document.getElementById('title$t')){
	document.getElementById('title$t').innerHTML='$text&nbsp;$please_wait';
	$('#Status$t').progressbar({ value: $prc });
	setTimeout('Start$tt()',2000);
}
";	
	
}

function checkDNSEmail($email) {
  if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$email)){
    list($username,$domain)=split('@',$email);
    if(!checkdnsrr($domain,'MX')) {
      return false;
    }
    return true;
  }
  return false;
  
}



function check_email_address($email) {
	$email=trim(strtolower($email));
	$banned["test@test.fr"]=true;
	$banned["tests@test.com"]=true;
	$banned["tests@test.fr"]=true;
	$banned["lol@lol.com"]=true;
	
	
	if($banned[$email]){return false;}
	
	$t=explode("@", $email);
	$lastpart=$t[1];
	$firstpart=$t[0];
	$falseDomains["toto"]=true;
	$falseDomains["tata"]=true;
	$falseDomains["coucocu"]=true;
	$falseDomains["coucou"]=true;
	$falseDomains["titi"]=true;
	$falseDomains["ici"]=true;
	$falseDomains["domains"]=true;
	$falseDomains["default"]=true;
	$falseDomains["myaddress"]=true;
	$falseDomains["mydomain"]=true;
	$falseDomains["demo"]=true;
	$falseDomains["test@"]=true;
	$falseDomains["tests@"]=true;
	$falseDomains["tests\."]=true;
	$falseDomains["test\."]=true;
	$falseDomains["contact@"]=true;
	$falseDomains["nn\.mm@"]=true;
	$falseDomains["nnn\.mmm@"]=true;
	$falseDomains["postmaster@"]=true;
	$falseDomains["root"]=true;
	$falseDomains["pippo"]=true;
	$falseDomains["lol.com"]=true;
	$falseDomains["asfsdf"]=true;
	
	
	while (list ($index, $lines) = each ($falseDomains) ){
		if(preg_match("#$index#i", $lastpart)){return false;}
		if(preg_match("#$index#i", $firstpart)){return false;}
		
	}

	return true;
	
	
  // First, we check that there's one @ symbol, 
  // and that the lengths are right.
  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
    // Email invalid because wrong number of characters 
    // in one section or wrong number of @ symbols.
    return false;
  }
  // Split it into sections to make life easier
  $email_array = explode("@", $email);
  $local_array = explode(".", $email_array[0]);
  for ($i = 0; $i < sizeof($local_array); $i++) {
    if
(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
$local_array[$i])) {
      return false;
    }
  }
  // Check if domain is IP. If not, 
  // it should be valid domain name
  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
    $domain_array = explode(".", $email_array[1]);
    if (sizeof($domain_array) < 2) {
        return false; // Not enough parts to domain
    }
    for ($i = 0; $i < sizeof($domain_array); $i++) {
      if
(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",
$domain_array[$i])) {
        return false;
      }
    }
  }
  return true;
}



function GetNamesServers(){
	
	$resolv_conf=explode("\n",@file_get_contents("/etc/resolv.conf"));
	while (list ($index, $lines) = each ($resolv_conf) ){
		if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
		if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
			$g=trim($re[1]);
			if($g=="127.0.0.1"){continue;}
			$arrayNameServers[]=$g;
		}
	}
		
	if(count($arrayNameServers)==0){
		$resolv_conf=file("/etc/resolvconf/resolv.conf.d/original");
		while (list ($index, $lines) = each ($resolv_conf) ){
			if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
			if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
				$g=trim($re[1]);
				if($g=="127.0.0.1"){continue;}
				$arrayNameServers[]=$g;
			}
		}
	
	}	
	return $arrayNameServers;
}


function timezonearray(){

	$timezone[]="Africa/Abidjan";                 //,0x000000 },
	$timezone[]="Africa/Accra";                   //,0x000055 },
	$timezone[]="Africa/Addis_Ababa";             //,0x0000FD },
	$timezone[]="Africa/Algiers";                 //,0x000153 },
	$timezone[]="Africa/Asmara";                  //,0x00027E },
	$timezone[]="Africa/Asmera";                  //,0x0002D4 },
	$timezone[]="Africa/Bamako";                  //,0x00032A },
	$timezone[]="Africa/Bangui";                  //,0x000395 },
	$timezone[]="Africa/Banjul";                  //,0x0003EA },
	$timezone[]="Africa/Bissau";                  //,0x000461 },
	$timezone[]="Africa/Blantyre";                //,0x0004C7 },
	$timezone[]="Africa/Brazzaville";             //,0x00051C },
	$timezone[]="Africa/Bujumbura";               //,0x000571 },
	$timezone[]="Africa/Cairo";                   //,0x0005B5 },
	$timezone[]="Africa/Casablanca";              //,0x00097C },
	$timezone[]="Africa/Ceuta";                   //,0x000A58 },
	$timezone[]="Africa/Conakry";                 //,0x000D5F },
	$timezone[]="Africa/Dakar";                   //,0x000DCA },
	$timezone[]="Africa/Dar_es_Salaam";           //,0x000E30 },
	$timezone[]="Africa/Djibouti";                //,0x000E9D },
	$timezone[]="Africa/Douala";                  //,0x000EF2 },
	$timezone[]="Africa/El_Aaiun";                //,0x000F47 },
	$timezone[]="Africa/Freetown";                //,0x000FAD },
	$timezone[]="Africa/Gaborone";                //,0x0010BC },
	$timezone[]="Africa/Harare";                  //,0x001117 },
	$timezone[]="Africa/Johannesburg";            //,0x00116C },
	$timezone[]="Africa/Kampala";                 //,0x0011DA },
	$timezone[]="Africa/Khartoum";                //,0x001259 },
	$timezone[]="Africa/Kigali";                  //,0x00136C },
	$timezone[]="Africa/Kinshasa";                //,0x0013C1 },
	$timezone[]="Africa/Lagos";                   //,0x00141C },
	$timezone[]="Africa/Libreville";              //,0x001471 },
	$timezone[]="Africa/Lome";                    //,0x0014C6 },
	$timezone[]="Africa/Luanda";                  //,0x00150A },
	$timezone[]="Africa/Lubumbashi";              //,0x00155F },
	$timezone[]="Africa/Lusaka";                  //,0x0015BA },
	$timezone[]="Africa/Malabo";                  //,0x00160F },
	$timezone[]="Africa/Maputo";                  //,0x001675 },
	$timezone[]="Africa/Maseru";                  //,0x0016CA },
	$timezone[]="Africa/Mbabane";                 //,0x001732 },
	$timezone[]="Africa/Mogadishu";               //,0x001788 },
	$timezone[]="Africa/Monrovia";                //,0x0017E3 },
	$timezone[]="Africa/Nairobi";                 //,0x001849 },
	$timezone[]="Africa/Ndjamena";                //,0x0018C8 },
	$timezone[]="Africa/Niamey";                  //,0x001934 },
	$timezone[]="Africa/Nouakchott";              //,0x0019A7 },
	$timezone[]="Africa/Ouagadougou";             //,0x001A12 },
	$timezone[]="Africa/Porto-Novo";              //,0x001A67 },
	$timezone[]="Africa/Sao_Tome";                //,0x001ACD },
	$timezone[]="Africa/Timbuktu";                //,0x001B22 },
	$timezone[]="Africa/Tripoli";                 //,0x001B8D },
	$timezone[]="Africa/Tunis";                   //,0x001C87 },
	$timezone[]="Africa/Windhoek";                //,0x001EB1 },
	$timezone[]="America/Adak";                   //,0x0020F8 },
	$timezone[]="America/Anchorage";              //,0x00246E },
	$timezone[]="America/Anguilla";               //,0x0027E2 },
	$timezone[]="America/Antigua";                //,0x002837 },
	$timezone[]="America/Araguaina";              //,0x00289D },
	$timezone[]="America/Argentina/Buenos_Aires"; //,0x0029F8 },
	$timezone[]="America/Argentina/Catamarca";    //,0x002BA6 },
	$timezone[]="America/Argentina/ComodRivadavia";  //,0x002D67 },
	$timezone[]="America/Argentina/Cordoba";      //,0x002F0D },
	$timezone[]="America/Argentina/Jujuy";        //,0x0030E2 },
	$timezone[]="America/Argentina/La_Rioja";     //,0x003296 },
	$timezone[]="America/Argentina/Mendoza";      //,0x00344E },
	$timezone[]="America/Argentina/Rio_Gallegos"; //,0x00360E },
	$timezone[]="America/Argentina/Salta";        //,0x0037C3 },
	$timezone[]="America/Argentina/San_Juan";     //,0x00396F },
	$timezone[]="America/Argentina/San_Luis";     //,0x003B27 },
	$timezone[]="America/Argentina/Tucuman";      //,0x003E05 },
	$timezone[]="America/Argentina/Ushuaia";      //,0x003FC1 },
	$timezone[]="America/Aruba";                  //,0x00417C },
	$timezone[]="America/Asuncion";               //,0x0041E2 },
	$timezone[]="America/Atikokan";               //,0x0044C7 },
	$timezone[]="America/Atka";                   //,0x00459D },
	$timezone[]="America/Bahia";                  //,0x004903 },
	$timezone[]="America/Barbados";               //,0x004A8C },
	$timezone[]="America/Belem";                  //,0x004B26 },
	$timezone[]="America/Belize";                 //,0x004C21 },
	$timezone[]="America/Blanc-Sablon";           //,0x004D9D },
	$timezone[]="America/Boa_Vista";              //,0x004E51 },
	$timezone[]="America/Bogota";                 //,0x004F5A },
	$timezone[]="America/Boise";                  //,0x004FC6 },
	$timezone[]="America/Buenos_Aires";           //,0x00535D },
	$timezone[]="America/Cambridge_Bay";          //,0x0054F6 },
	$timezone[]="America/Campo_Grande";           //,0x00581E },
	$timezone[]="America/Cancun";                 //,0x005B0D },
	$timezone[]="America/Caracas";                //,0x005D4F },
	$timezone[]="America/Catamarca";              //,0x005DB6 },
	$timezone[]="America/Cayenne";                //,0x005F5C },
	$timezone[]="America/Cayman";                 //,0x005FBE },
	$timezone[]="America/Chicago";                //,0x006013 },
	$timezone[]="America/Chihuahua";              //,0x00652A },
	$timezone[]="America/Coral_Harbour";          //,0x006779 },
	$timezone[]="America/Cordoba";                //,0x00680B },
	$timezone[]="America/Costa_Rica";             //,0x0069B1 },
	$timezone[]="America/Cuiaba";                 //,0x006A3B },
	$timezone[]="America/Curacao";                //,0x006D19 },
	$timezone[]="America/Danmarkshavn";           //,0x006D7F },
	$timezone[]="America/Dawson";                 //,0x006EC3 },
	$timezone[]="America/Dawson_Creek";           //,0x0071E0 },
	$timezone[]="America/Denver";                 //,0x0073BA },
	$timezone[]="America/Detroit";                //,0x007740 },
	$timezone[]="America/Dominica";               //,0x007A9F },
	$timezone[]="America/Edmonton";               //,0x007AF4 },
	$timezone[]="America/Eirunepe";               //,0x007EAC },
	$timezone[]="America/El_Salvador";            //,0x007FBF },
	$timezone[]="America/Ensenada";               //,0x008034 },
	$timezone[]="America/Fort_Wayne";             //,0x0084DB },
	$timezone[]="America/Fortaleza";              //,0x00839D },
	$timezone[]="America/Glace_Bay";              //,0x008745 },
	$timezone[]="America/Godthab";                //,0x008ABC },
	$timezone[]="America/Goose_Bay";              //,0x008D80 },
	$timezone[]="America/Grand_Turk";             //,0x00923D },
	$timezone[]="America/Grenada";                //,0x0094EC },
	$timezone[]="America/Guadeloupe";             //,0x009541 },
	$timezone[]="America/Guatemala";              //,0x009596 },
	$timezone[]="America/Guayaquil";              //,0x00961F },
	$timezone[]="America/Guyana";                 //,0x00967C },
	$timezone[]="America/Halifax";                //,0x0096FD },
	$timezone[]="America/Havana";                 //,0x009C13 },
	$timezone[]="America/Hermosillo";             //,0x009F86 },
	$timezone[]="America/Indiana/Indianapolis";   //,0x00A064 },
	$timezone[]="America/Indiana/Knox";           //,0x00A2F5 },
	$timezone[]="America/Indiana/Marengo";        //,0x00A68C },
	$timezone[]="America/Indiana/Petersburg";     //,0x00A932 },
	$timezone[]="America/Indiana/Tell_City";      //,0x00AE7F },
	$timezone[]="America/Indiana/Vevay";          //,0x00B118 },
	$timezone[]="America/Indiana/Vincennes";      //,0x00B353 },
	$timezone[]="America/Indiana/Winamac";        //,0x00B607 },
	$timezone[]="America/Indianapolis";           //,0x00AC15 },
	$timezone[]="America/Inuvik";                 //,0x00B8C0 },
	$timezone[]="America/Iqaluit";                //,0x00BBB7 },
	$timezone[]="America/Jamaica";                //,0x00BED9 },
	$timezone[]="America/Jujuy";                  //,0x00BF9E },
	$timezone[]="America/Juneau";                 //,0x00C148 },
	$timezone[]="America/Kentucky/Louisville";    //,0x00C4C6 },
	$timezone[]="America/Kentucky/Monticello";    //,0x00C8E4 },
	$timezone[]="America/Knox_IN";                //,0x00CC69 },
	$timezone[]="America/La_Paz";                 //,0x00CFDA },
	$timezone[]="America/Lima";                   //,0x00D041 },
	$timezone[]="America/Los_Angeles";            //,0x00D0E9 },
	$timezone[]="America/Louisville";             //,0x00D4FA },
	$timezone[]="America/Maceio";                 //,0x00D8EF },
	$timezone[]="America/Managua";                //,0x00DA29 },
	$timezone[]="America/Manaus";                 //,0x00DADC },
	$timezone[]="America/Marigot";                //,0x00DBDE },
	$timezone[]="America/Martinique";             //,0x00DC33 },
	$timezone[]="America/Mazatlan";               //,0x00DC9F },
	$timezone[]="America/Mendoza";                //,0x00DF0C },
	$timezone[]="America/Menominee";              //,0x00E0C0 },
	$timezone[]="America/Merida";                 //,0x00E441 },
	$timezone[]="America/Mexico_City";            //,0x00E67C },
	$timezone[]="America/Miquelon";               //,0x00E8F7 },
	$timezone[]="America/Moncton";                //,0x00EB69 },
	$timezone[]="America/Monterrey";              //,0x00F000 },
	$timezone[]="America/Montevideo";             //,0x00F247 },
	$timezone[]="America/Montreal";               //,0x00F559 },
	$timezone[]="America/Montserrat";             //,0x00FA6F },
	$timezone[]="America/Nassau";                 //,0x00FAC4 },
	$timezone[]="America/New_York";               //,0x00FE09 },
	$timezone[]="America/Nipigon";                //,0x010314 },
	$timezone[]="America/Nome";                   //,0x010665 },
	$timezone[]="America/Noronha";                //,0x0109E3 },
	$timezone[]="America/North_Dakota/Center";    //,0x010B13 },
	$timezone[]="America/North_Dakota/New_Salem"; //,0x010EA7 },
	$timezone[]="America/Panama";                 //,0x011250 },
	$timezone[]="America/Pangnirtung";            //,0x0112A5 },
	$timezone[]="America/Paramaribo";             //,0x0115DB },
	$timezone[]="America/Phoenix";                //,0x01166D },
	$timezone[]="America/Port-au-Prince";         //,0x01171B },
	$timezone[]="America/Port_of_Spain";          //,0x011936 },
	$timezone[]="America/Porto_Acre";             //,0x011837 },
	$timezone[]="America/Porto_Velho";            //,0x01198B },
	$timezone[]="America/Puerto_Rico";            //,0x011A81 },
	$timezone[]="America/Rainy_River";            //,0x011AEC },
	$timezone[]="America/Rankin_Inlet";           //,0x011E24 },
	$timezone[]="America/Recife";                 //,0x01210A },
	$timezone[]="America/Regina";                 //,0x012234 },
	$timezone[]="America/Resolute";               //,0x0123F2 },
	$timezone[]="America/Rio_Branco";             //,0x0126EB },
	$timezone[]="America/Rosario";                //,0x0127EE },
	$timezone[]="America/Santarem";               //,0x012994 },
	$timezone[]="America/Santiago";               //,0x012A99 },
	$timezone[]="America/Santo_Domingo";          //,0x012E42 },
	$timezone[]="America/Sao_Paulo";              //,0x012F08 },
	$timezone[]="America/Scoresbysund";           //,0x013217 },
	$timezone[]="America/Shiprock";               //,0x013505 },
	$timezone[]="America/St_Barthelemy";          //,0x013894 },
	$timezone[]="America/St_Johns";               //,0x0138E9 },
	$timezone[]="America/St_Kitts";               //,0x013E3C },
	$timezone[]="America/St_Lucia";               //,0x013E91 },
	$timezone[]="America/St_Thomas";              //,0x013EE6 },
	$timezone[]="America/St_Vincent";             //,0x013F3B },
	$timezone[]="America/Swift_Current";          //,0x013F90 },
	$timezone[]="America/Tegucigalpa";            //,0x0140B1 },
	$timezone[]="America/Thule";                  //,0x014130 },
	$timezone[]="America/Thunder_Bay";            //,0x014377 },
	$timezone[]="America/Tijuana";                //,0x0146C0 },
	$timezone[]="America/Toronto";                //,0x014A35 },
	$timezone[]="America/Tortola";                //,0x014F4C },
	$timezone[]="America/Vancouver";              //,0x014FA1 },
	$timezone[]="America/Virgin";                 //,0x0153DE },
	$timezone[]="America/Whitehorse";             //,0x015433 },
	$timezone[]="America/Winnipeg";               //,0x015750 },
	$timezone[]="America/Yakutat";                //,0x015B90 },
	$timezone[]="America/Yellowknife";            //,0x015EFB },
	$timezone[]="Antarctica/Casey";               //,0x01620B },
	$timezone[]="Antarctica/Davis";               //,0x016291 },
	$timezone[]="Antarctica/DumontDUrville";      //,0x01631B },
	$timezone[]="Antarctica/Mawson";              //,0x0163AD },
	$timezone[]="Antarctica/McMurdo";             //,0x016429 },
	$timezone[]="Antarctica/Palmer";              //,0x01672B },
	$timezone[]="Antarctica/Rothera";             //,0x016A47 },
	$timezone[]="Antarctica/South_Pole";          //,0x016ABD },
	$timezone[]="Antarctica/Syowa";               //,0x016DC5 },
	$timezone[]="Antarctica/Vostok";              //,0x016E33 },
	$timezone[]="Arctic/Longyearbyen";            //,0x016EA8 },
	$timezone[]="Asia/Aden";                      //,0x0171DA },
	$timezone[]="Asia/Almaty";                    //,0x01722F },
	$timezone[]="Asia/Amman";                     //,0x0173AE },
	$timezone[]="Asia/Anadyr";                    //,0x01766E },
	$timezone[]="Asia/Aqtau";                     //,0x01795C },
	$timezone[]="Asia/Aqtobe";                    //,0x017B5B },
	$timezone[]="Asia/Ashgabat";                  //,0x017D13 },
	$timezone[]="Asia/Ashkhabad";                 //,0x017E30 },
	$timezone[]="Asia/Baghdad";                   //,0x017F4D },
	$timezone[]="Asia/Bahrain";                   //,0x0180C2 },
	$timezone[]="Asia/Baku";                      //,0x018128 },
	$timezone[]="Asia/Bangkok";                   //,0x018410 },
	$timezone[]="Asia/Beirut";                    //,0x018465 },
	$timezone[]="Asia/Bishkek";                   //,0x018772 },
	$timezone[]="Asia/Brunei";                    //,0x01891E },
	$timezone[]="Asia/Calcutta";                  //,0x018980 },
	$timezone[]="Asia/Choibalsan";                //,0x0189F9 },
	$timezone[]="Asia/Chongqing";                 //,0x018B72 },
	$timezone[]="Asia/Chungking";                 //,0x018C61 },
	$timezone[]="Asia/Colombo";                   //,0x018D10 },
	$timezone[]="Asia/Dacca";                     //,0x018DAC },
	$timezone[]="Asia/Damascus";                  //,0x018E4D },
	$timezone[]="Asia/Dhaka";                     //,0x01919D },
	$timezone[]="Asia/Dili";                      //,0x01923E },
	$timezone[]="Asia/Dubai";                     //,0x0192C7 },
	$timezone[]="Asia/Dushanbe";                  //,0x01931C },
	$timezone[]="Asia/Gaza";                      //,0x01941F },
	$timezone[]="Asia/Harbin";                    //,0x019768 },
	$timezone[]="Asia/Ho_Chi_Minh";               //,0x01984F },
	$timezone[]="Asia/Hong_Kong";                 //,0x0198C7 },
	$timezone[]="Asia/Hovd";                      //,0x019A93 },
	$timezone[]="Asia/Irkutsk";                   //,0x019C0B },
	$timezone[]="Asia/Istanbul";                  //,0x019EF2 },
	$timezone[]="Asia/Jakarta";                   //,0x01A2DF },
	$timezone[]="Asia/Jayapura";                  //,0x01A389 },
	$timezone[]="Asia/Jerusalem";                 //,0x01A40D },
	$timezone[]="Asia/Kabul";                     //,0x01A73C },
	$timezone[]="Asia/Kamchatka";                 //,0x01A78D },
	$timezone[]="Asia/Karachi";                   //,0x01AA72 },
	$timezone[]="Asia/Kashgar";                   //,0x01AC3F },
	$timezone[]="Asia/Kathmandu";                 //,0x01AD10 },
	$timezone[]="Asia/Katmandu";                  //,0x01AD76 },
	$timezone[]="Asia/Kolkata";                   //,0x01ADDC },
	$timezone[]="Asia/Krasnoyarsk";               //,0x01AE55 },
	$timezone[]="Asia/Kuala_Lumpur";              //,0x01B13E },
	$timezone[]="Asia/Kuching";                   //,0x01B1FB },
	$timezone[]="Asia/Kuwait";                    //,0x01B2E9 },
	$timezone[]="Asia/Macao";                     //,0x01B33E },
	$timezone[]="Asia/Macau";                     //,0x01B479 },
	$timezone[]="Asia/Magadan";                   //,0x01B5B4 },
	$timezone[]="Asia/Makassar";                  //,0x01B897 },
	$timezone[]="Asia/Manila";                    //,0x01B950 },
	$timezone[]="Asia/Muscat";                    //,0x01B9D5 },
	$timezone[]="Asia/Nicosia";                   //,0x01BA2A },
	$timezone[]="Asia/Novokuznetsk";              //,0x01BD12 },
	$timezone[]="Asia/Novosibirsk";               //,0x01C015 },
	$timezone[]="Asia/Omsk";                      //,0x01C309 },
	$timezone[]="Asia/Oral";                      //,0x01C5F1 },
	$timezone[]="Asia/Phnom_Penh";                //,0x01C7C1 },
	$timezone[]="Asia/Pontianak";                 //,0x01C839 },
	$timezone[]="Asia/Pyongyang";                 //,0x01C8FA },
	$timezone[]="Asia/Qatar";                     //,0x01C967 },
	$timezone[]="Asia/Qyzylorda";                 //,0x01C9CD },
	$timezone[]="Asia/Rangoon";                   //,0x01CBA3 },
	$timezone[]="Asia/Riyadh";                    //,0x01CC1B },
	$timezone[]="Asia/Saigon";                    //,0x01CC70 },
	$timezone[]="Asia/Sakhalin";                  //,0x01CCE8 },
	$timezone[]="Asia/Samarkand";                 //,0x01CFE8 },
	$timezone[]="Asia/Seoul";                     //,0x01D11E },
	$timezone[]="Asia/Shanghai";                  //,0x01D1C2 },
	$timezone[]="Asia/Singapore";                 //,0x01D2A2 },
	$timezone[]="Asia/Taipei";                    //,0x01D359 },
	$timezone[]="Asia/Tashkent";                  //,0x01D471 },
	$timezone[]="Asia/Tbilisi";                   //,0x01D5A2 },
	$timezone[]="Asia/Tehran";                    //,0x01D75C },
	$timezone[]="Asia/Tel_Aviv";                  //,0x01D9CA },
	$timezone[]="Asia/Thimbu";                    //,0x01DCF9 },
	$timezone[]="Asia/Thimphu";                   //,0x01DD5F },
	$timezone[]="Asia/Tokyo";                     //,0x01DDC5 },
	$timezone[]="Asia/Ujung_Pandang";             //,0x01DE4E },
	$timezone[]="Asia/Ulaanbaatar";               //,0x01DECA },
	$timezone[]="Asia/Ulan_Bator";                //,0x01E025 },
	$timezone[]="Asia/Urumqi";                    //,0x01E172 },
	$timezone[]="Asia/Vientiane";                 //,0x01E239 },
	$timezone[]="Asia/Vladivostok";               //,0x01E2B1 },
	$timezone[]="Asia/Yakutsk";                   //,0x01E59E },
	$timezone[]="Asia/Yekaterinburg";             //,0x01E884 },
	$timezone[]="Asia/Yerevan";                   //,0x01EB90 },
	$timezone[]="Atlantic/Azores";                //,0x01EE94 },
	$timezone[]="Atlantic/Bermuda";               //,0x01F397 },
	$timezone[]="Atlantic/Canary";                //,0x01F678 },
	$timezone[]="Atlantic/Cape_Verde";            //,0x01F94E },
	$timezone[]="Atlantic/Faeroe";                //,0x01F9C7 },
	$timezone[]="Atlantic/Faroe";                 //,0x01FC6B },
	$timezone[]="Atlantic/Jan_Mayen";             //,0x01FF0F },
	$timezone[]="Atlantic/Madeira";               //,0x020241 },
	$timezone[]="Atlantic/Reykjavik";             //,0x02074A },
	$timezone[]="Atlantic/South_Georgia";         //,0x020903 },
	$timezone[]="Atlantic/St_Helena";             //,0x020C1B },
	$timezone[]="Atlantic/Stanley";               //,0x020947 },
	$timezone[]="Australia/ACT";                  //,0x020C70 },
	$timezone[]="Australia/Adelaide";             //,0x020F8D },
	$timezone[]="Australia/Brisbane";             //,0x0212B9 },
	$timezone[]="Australia/Broken_Hill";          //,0x021380 },
	$timezone[]="Australia/Canberra";             //,0x0216BE },
	$timezone[]="Australia/Currie";               //,0x0219DB },
	$timezone[]="Australia/Darwin";               //,0x021D0E },
	$timezone[]="Australia/Eucla";                //,0x021D94 },
	$timezone[]="Australia/Hobart";               //,0x021E69 },
	$timezone[]="Australia/LHI";                  //,0x0221C7 },
	$timezone[]="Australia/Lindeman";             //,0x022462 },
	$timezone[]="Australia/Lord_Howe";            //,0x022543 },
	$timezone[]="Australia/Melbourne";            //,0x0227EE },
	$timezone[]="Australia/North";                //,0x022B13 },
	$timezone[]="Australia/NSW";                  //,0x022B87 },
	$timezone[]="Australia/Perth";                //,0x022EA4 },
	$timezone[]="Australia/Queensland";           //,0x022F7C },
	$timezone[]="Australia/South";                //,0x023028 },
	$timezone[]="Australia/Sydney";               //,0x023345 },
	$timezone[]="Australia/Tasmania";             //,0x023682 },
	$timezone[]="Australia/Victoria";             //,0x0239C7 },
	$timezone[]="Australia/West";                 //,0x023CE4 },
	$timezone[]="Australia/Yancowinna";           //,0x023D9A },
	$timezone[]="Brazil/Acre";                    //,0x0240BC },
	$timezone[]="Brazil/DeNoronha";               //,0x0241BB },
	$timezone[]="Brazil/East";                    //,0x0242DB },
	$timezone[]="Brazil/West";                    //,0x0245B8 },
	$timezone[]="Canada/Atlantic";                //,0x0246B0 },
	$timezone[]="Canada/Central";                 //,0x024B98 },
	$timezone[]="Canada/East-Saskatchewan";       //,0x0254A2 },
	$timezone[]="Canada/Eastern";                 //,0x024FB2 },
	$timezone[]="Canada/Mountain";                //,0x02562B },
	$timezone[]="Canada/Newfoundland";            //,0x0259A1 },
	$timezone[]="Canada/Pacific";                 //,0x025ECC },
	$timezone[]="Canada/Saskatchewan";            //,0x0262E5 },
	$timezone[]="Canada/Yukon";                   //,0x02646E },
	$timezone[]="CET";                            //,0x026771 },
	$timezone[]="Chile/Continental";              //,0x026A7A },
	$timezone[]="Chile/EasterIsland";             //,0x026E15 },
	$timezone[]="CST6CDT";                        //,0x027157 },
	$timezone[]="Cuba";                           //,0x0274A8 },
	$timezone[]="EET";                            //,0x02781B },
	$timezone[]="Egypt";                          //,0x027ACE },
	$timezone[]="Eire";                           //,0x027E95 },
	$timezone[]="EST";                            //,0x0283A6 },
	$timezone[]="EST5EDT";                        //,0x0283EA },
	$timezone[]="Etc/GMT";                        //,0x02873B },
	$timezone[]="Etc/GMT+0";                      //,0x028807 },
	$timezone[]="Etc/GMT+1";                      //,0x028891 },
	$timezone[]="Etc/GMT+10";                     //,0x02891E },
	$timezone[]="Etc/GMT+11";                     //,0x0289AC },
	$timezone[]="Etc/GMT+12";                     //,0x028A3A },
	$timezone[]="Etc/GMT+2";                      //,0x028B55 },
	$timezone[]="Etc/GMT+3";                      //,0x028BE1 },
	$timezone[]="Etc/GMT+4";                      //,0x028C6D },
	$timezone[]="Etc/GMT+5";                      //,0x028CF9 },
	$timezone[]="Etc/GMT+6";                      //,0x028D85 },
	$timezone[]="Etc/GMT+7";                      //,0x028E11 },
	$timezone[]="Etc/GMT+8";                      //,0x028E9D },
	$timezone[]="Etc/GMT+9";                      //,0x028F29 },
	$timezone[]="Etc/GMT-0";                      //,0x0287C3 },
	$timezone[]="Etc/GMT-1";                      //,0x02884B },
	$timezone[]="Etc/GMT-10";                     //,0x0288D7 },
	$timezone[]="Etc/GMT-11";                     //,0x028965 },
	$timezone[]="Etc/GMT-12";                     //,0x0289F3 },
	$timezone[]="Etc/GMT-13";                     //,0x028A81 },
	$timezone[]="Etc/GMT-14";                     //,0x028AC8 },
	$timezone[]="Etc/GMT-2";                      //,0x028B0F },
	$timezone[]="Etc/GMT-3";                      //,0x028B9B },
	$timezone[]="Etc/GMT-4";                      //,0x028C27 },
	$timezone[]="Etc/GMT-5";                      //,0x028CB3 },
	$timezone[]="Etc/GMT-6";                      //,0x028D3F },
	$timezone[]="Etc/GMT-7";                      //,0x028DCB },
	$timezone[]="Etc/GMT-8";                      //,0x028E57 },
	$timezone[]="Etc/GMT-9";                      //,0x028EE3 },
	$timezone[]="Etc/GMT0";                       //,0x02877F },
	$timezone[]="Etc/Greenwich";                  //,0x028F6F },
	$timezone[]="Etc/UCT";                        //,0x028FB3 },
	$timezone[]="Etc/Universal";                  //,0x028FF7 },
	$timezone[]="Etc/UTC";                        //,0x02903B },
	$timezone[]="Etc/Zulu";                       //,0x02907F },
	$timezone[]="Europe/Amsterdam";               //,0x0290C3 },
	$timezone[]="Europe/Andorra";                 //,0x029501 },
	$timezone[]="Europe/Athens";                  //,0x02977D },
	$timezone[]="Europe/Belfast";                 //,0x029AC0 },
	$timezone[]="Europe/Belgrade";                //,0x029FF7 },
	$timezone[]="Europe/Berlin";                  //,0x02A2C0 },
	$timezone[]="Europe/Bratislava";              //,0x02A616 },
	$timezone[]="Europe/Brussels";                //,0x02A948 },
	$timezone[]="Europe/Bucharest";               //,0x02AD7F },
	$timezone[]="Europe/Budapest";                //,0x02B0A9 },
	$timezone[]="Europe/Chisinau";                //,0x02B41C },
	$timezone[]="Europe/Copenhagen";              //,0x02B7AA },
	$timezone[]="Europe/Dublin";                  //,0x02BAB4 },
	$timezone[]="Europe/Gibraltar";               //,0x02BFC5 },
	$timezone[]="Europe/Guernsey";                //,0x02C41C },
	$timezone[]="Europe/Helsinki";                //,0x02C953 },
	$timezone[]="Europe/Isle_of_Man";             //,0x02CC09 },
	$timezone[]="Europe/Istanbul";                //,0x02D140 },
	$timezone[]="Europe/Jersey";                  //,0x02D52D },
	$timezone[]="Europe/Kaliningrad";             //,0x02DA64 },
	$timezone[]="Europe/Kiev";                    //,0x02DDC7 },
	$timezone[]="Europe/Lisbon";                  //,0x02E0DE },
	$timezone[]="Europe/Ljubljana";               //,0x02E5E2 },
	$timezone[]="Europe/London";                  //,0x02E8AB },
	$timezone[]="Europe/Luxembourg";              //,0x02EDE2 },
	$timezone[]="Europe/Madrid";                  //,0x02F238 },
	$timezone[]="Europe/Malta";                   //,0x02F5FE },
	$timezone[]="Europe/Mariehamn";               //,0x02F9B7 },
	$timezone[]="Europe/Minsk";                   //,0x02FC6D },
	$timezone[]="Europe/Monaco";                  //,0x02FF78 },
	$timezone[]="Europe/Moscow";                  //,0x0303B3 },
	$timezone[]="Europe/Nicosia";                 //,0x030705 },
	$timezone[]="Europe/Oslo";                    //,0x0309ED },
	$timezone[]="Europe/Paris";                   //,0x030D1F },
	$timezone[]="Europe/Podgorica";               //,0x031165 },
	$timezone[]="Europe/Prague";                  //,0x03142E },
	$timezone[]="Europe/Riga";                    //,0x031760 },
	$timezone[]="Europe/Rome";                    //,0x031AA5 },
	$timezone[]="Europe/Samara";                  //,0x031E68 },
	$timezone[]="Europe/San_Marino";              //,0x032194 },
	$timezone[]="Europe/Sarajevo";                //,0x032557 },
	$timezone[]="Europe/Simferopol";              //,0x032820 },
	$timezone[]="Europe/Skopje";                  //,0x032B4B },
	$timezone[]="Europe/Sofia";                   //,0x032E14 },
	$timezone[]="Europe/Stockholm";               //,0x03311C },
	$timezone[]="Europe/Tallinn";                 //,0x0333CB },
	$timezone[]="Europe/Tirane";                  //,0x033705 },
	$timezone[]="Europe/Tiraspol";                //,0x033A0B },
	$timezone[]="Europe/Uzhgorod";                //,0x033D99 },
	$timezone[]="Europe/Vaduz";                   //,0x0340B0 },
	$timezone[]="Europe/Vatican";                 //,0x034343 },
	$timezone[]="Europe/Vienna";                  //,0x034706 },
	$timezone[]="Europe/Vilnius";                 //,0x034A33 },
	$timezone[]="Europe/Volgograd";               //,0x034D72 },
	$timezone[]="Europe/Warsaw";                  //,0x03507B },
	$timezone[]="Europe/Zagreb";                  //,0x03545C },
	$timezone[]="Europe/Zaporozhye";              //,0x035725 },
	$timezone[]="Europe/Zurich";                  //,0x035A66 },
	$timezone[]="Factory";                        //,0x035D15 },
	$timezone[]="GB";                             //,0x035D86 },
	$timezone[]="GB-Eire";                        //,0x0362BD },
	$timezone[]="GMT";                            //,0x0367F4 },
	$timezone[]="GMT+0";                          //,0x0368C0 },
	$timezone[]="GMT-0";                          //,0x03687C },
	$timezone[]="GMT0";                           //,0x036838 },
	$timezone[]="Greenwich";                      //,0x036904 },
	$timezone[]="Hongkong";                       //,0x036948 },
	$timezone[]="HST";                            //,0x036B14 },
	$timezone[]="Iceland";                        //,0x036B58 },
	$timezone[]="Indian/Antananarivo";            //,0x036D11 },
	$timezone[]="Indian/Chagos";                  //,0x036D85 },
	$timezone[]="Indian/Christmas";               //,0x036DE7 },
	$timezone[]="Indian/Cocos";                   //,0x036E2B },
	$timezone[]="Indian/Comoro";                  //,0x036E6F },
	$timezone[]="Indian/Kerguelen";               //,0x036EC4 },
	$timezone[]="Indian/Mahe";                    //,0x036F19 },
	$timezone[]="Indian/Maldives";                //,0x036F6E },
	$timezone[]="Indian/Mauritius";               //,0x036FC3 },
	$timezone[]="Indian/Mayotte";                 //,0x037039 },
	$timezone[]="Indian/Reunion";                 //,0x03708E },
	$timezone[]="Iran";                           //,0x0370E3 },
	$timezone[]="Israel";                         //,0x037351 },
	$timezone[]="Jamaica";                        //,0x037680 },
	$timezone[]="Japan";                          //,0x037745 },
	$timezone[]="Kwajalein";                      //,0x0377CE },
	$timezone[]="Libya";                          //,0x037831 },
	$timezone[]="MET";                            //,0x03792B },
	$timezone[]="Mexico/BajaNorte";               //,0x037C34 },
	$timezone[]="Mexico/BajaSur";                 //,0x037F9D },
	$timezone[]="Mexico/General";                 //,0x0381E2 },
	$timezone[]="MST";                            //,0x038440 },
	$timezone[]="MST7MDT";                        //,0x038484 },
	$timezone[]="Navajo";                         //,0x0387D5 },
	$timezone[]="NZ";                             //,0x038B4E },
	$timezone[]="NZ-CHAT";                        //,0x038ECC },
	$timezone[]="Pacific/Apia";                   //,0x0391B4 },
	$timezone[]="Pacific/Auckland";               //,0x039232 },
	$timezone[]="Pacific/Chatham";                //,0x0395BE },
	$timezone[]="Pacific/Easter";                 //,0x0398B5 },
	$timezone[]="Pacific/Efate";                  //,0x039C13 },
	$timezone[]="Pacific/Enderbury";              //,0x039CD9 },
	$timezone[]="Pacific/Fakaofo";                //,0x039D47 },
	$timezone[]="Pacific/Fiji";                   //,0x039D8B },
	$timezone[]="Pacific/Funafuti";               //,0x039E01 },
	$timezone[]="Pacific/Galapagos";              //,0x039E45 },
	$timezone[]="Pacific/Gambier";                //,0x039EBD },
	$timezone[]="Pacific/Guadalcanal";            //,0x039F22 },
	$timezone[]="Pacific/Guam";                   //,0x039F77 },
	$timezone[]="Pacific/Honolulu";               //,0x039FCD },
	$timezone[]="Pacific/Johnston";               //,0x03A061 },
	$timezone[]="Pacific/Kiritimati";             //,0x03A0B3 },
	$timezone[]="Pacific/Kosrae";                 //,0x03A11E },
	$timezone[]="Pacific/Kwajalein";              //,0x03A17B },
	$timezone[]="Pacific/Majuro";                 //,0x03A1E7 },
	$timezone[]="Pacific/Marquesas";              //,0x03A246 },
	$timezone[]="Pacific/Midway";                 //,0x03A2AD },
	$timezone[]="Pacific/Nauru";                  //,0x03A337 },
	$timezone[]="Pacific/Niue";                   //,0x03A3AF },
	$timezone[]="Pacific/Norfolk";                //,0x03A40D },
	$timezone[]="Pacific/Noumea";                 //,0x03A462 },
	$timezone[]="Pacific/Pago_Pago";              //,0x03A4F2 },
	$timezone[]="Pacific/Palau";                  //,0x03A57B },
	$timezone[]="Pacific/Pitcairn";               //,0x03A5BF },
	$timezone[]="Pacific/Ponape";                 //,0x03A614 },
	$timezone[]="Pacific/Port_Moresby";           //,0x03A669 },
	$timezone[]="Pacific/Rarotonga";              //,0x03A6AD },
	$timezone[]="Pacific/Saipan";                 //,0x03A789 },
	$timezone[]="Pacific/Samoa";                  //,0x03A7EC },
	$timezone[]="Pacific/Tahiti";                 //,0x03A875 },
	$timezone[]="Pacific/Tarawa";                 //,0x03A8DA },
	$timezone[]="Pacific/Tongatapu";              //,0x03A92E },
	$timezone[]="Pacific/Truk";                   //,0x03A9BA },
	$timezone[]="Pacific/Wake";                   //,0x03AA13 },
	$timezone[]="Pacific/Wallis";                 //,0x03AA63 },
	$timezone[]="Pacific/Yap";                    //,0x03AAA7 },
	$timezone[]="Poland";                         //,0x03AAEC },
	$timezone[]="Portugal";                       //,0x03AECD },
	$timezone[]="PRC";                            //,0x03B3C9 },
	$timezone[]="PST8PDT";                        //,0x03B47A },
	$timezone[]="ROC";                            //,0x03B7CB },
	$timezone[]="ROK";                            //,0x03B8E3 },
	$timezone[]="Singapore";                      //,0x03B987 },
	$timezone[]="Turkey";                         //,0x03BA3E },
	$timezone[]="UCT";                            //,0x03BE2B },
	$timezone[]="Universal";                      //,0x03BE6F },
	$timezone[]="US/Alaska";                      //,0x03BEB3 },
	$timezone[]="US/Aleutian";                    //,0x03C21C },
	$timezone[]="US/Arizona";                     //,0x03C582 },
	$timezone[]="US/Central";                     //,0x03C610 },
	$timezone[]="US/East-Indiana";                //,0x03D01A },
	$timezone[]="US/Eastern";                     //,0x03CB1B },
	$timezone[]="US/Hawaii";                      //,0x03D284 },
	$timezone[]="US/Indiana-Starke";              //,0x03D312 },
	$timezone[]="US/Michigan";                    //,0x03D683 },
	$timezone[]="US/Mountain";                    //,0x03D9BA },
	$timezone[]="US/Pacific";                     //,0x03DD33 },
	$timezone[]="US/Pacific-New";                 //,0x03E138 },
	$timezone[]="US/Samoa";                       //,0x03E53D },
	$timezone[]="UTC";                            //,0x03E5C6 },
	$timezone[]="W-SU";                           //,0x03E8BD },
	$timezone[]="WET";                            //,0x03E60A },
	$timezone[]="Zulu";                           //,0x03EBF8 },
	return $timezone;


}