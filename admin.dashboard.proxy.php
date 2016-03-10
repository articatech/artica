<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');


if(isset($_GET["ldap"])){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_MEM"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	TestLDAPAD();
}
if(isset($_GET["proxy_dashboard_counters"])){echo proxy_dashboard_counters();exit;}
if(isset($_GET["button-refresh"])){button_refresh();exit;}
if(isset($_GET["sequence-proxy"])){echo proxy_status();exit;}
if(isset($_GET["sequence-server"])){echo server_status();exit;}
if(isset($_GET["sequence-firewall"])){echo  firewall_status();exit;}

if(isset($_GET["proxy-dashboard"])){proxy_dashboard();exit;}
if(isset($_GET["Dashboardjs"])){Dashboardjs();exit;}
if(isset($_GET["proxy-dahsboard-title"])){Dashboard_title();exit;}
if(isset($_GET["bx-slider-top-right"])){bxSliderTopRight();exit;}
if(isset($_GET["active-directory-dash-infos"])){active_directory_infos();exit;}

if(isset($_GET["graph1-js"])){proxy_graph_js();exit;}
if(isset($_GET["graph2-js"])){proxy_graph2_js();exit;}
if(isset($_GET["graph3-js"])){proxy_graph3_js();exit;}
if(isset($_GET["graph2-haproxy-js"])){haproxy_graph2_js();exit;}





Start();


function Start(){
$page=CurrentPageName();
$tpl=new templates();
$missing_javascript_function_refresh=$tpl->javascript_parse_text("{missing_javascript_function_refresh}");

echo "
<input type='hidden' id='DASHBOARD_SEQUENCE_SERVER' value='0'>
<div style='margin-top:-15px'>
<ul class=\"bxslider\"  id='MainSlider'>
  <li><div style='background-color:white;width:1500px;height:2500px;' id='proxy-dashboard'><script>LoadAjaxRound('proxy-dashboard','admin.dashboard.proxy.php?proxy-dashboard=yes')</script></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='proxy-services'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='proxy-store-caches'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='artica-main-updates'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-main-status'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-member-follower'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-webfiltering-main'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='artica-license-status'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='categories-service'></div></li>
  <li><div style='background-color:white;width:1500px;height:3000px;' id='windowsad-service'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-dashboard-proxy'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-statistics-options'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-squid-ports'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-squid-import-logs'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='dashboard-firewall'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='firewall-routers'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='firewall-nats'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-squid-ssl_wl'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-ntopng'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-caches-center'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-caches-level'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-caches-rock'></div></li>
  <li><div style='background-color:transparent;width:1500px;height:1800px;' id='main-filter-cicap'></div></li>
  <li><div style='background-color:white;width:1500px;height:1500px;' id='main-ufdb-frontend'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-ufdb-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-fw-nic-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-members'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-acls-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-acls-groups'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-acls-browsers'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-acls-bandwidth'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-acls-options'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-restricted-members'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-blocked-members'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-proxy-pac'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-proxy-pac-events'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-access-rotate'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-proxy-update-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-artica-update-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-ufdb-personal-categories'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-squid-watchdog-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='main-squid-tasks-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='firewall-services-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-hypercache-table'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-hypercache-mirror'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-params-timeouts'></div></li>
  <li><div style='background-color:white;width:1500px;height:2200px;' id='ufdb-web-page-errors'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='watchdog-squid-parameters'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='logs-cache-squid'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-debug-tools'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-ssl-encrypt'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-webf-quotas-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='ufdbguard-behavior-settings'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-global-blacklist'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-dns-settings'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-perf-mon'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-templates-errors'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='ufdbguard-rewrite-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='ufdbguard-terms-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-icap-center'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-transparent'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='hostspot-v3'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-parent-proxy'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='failover-manager'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-performances'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-old-status'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-eye-php'></div></li>
  <li><div style='background-color:white;width:1500px;height:1600px;' id='squid-openldap'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-hard-drives'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-sensors'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-clock'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-ldap'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-mysql'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-freeweb'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-wordpress'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-rdp-proxy'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-haproxy'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='nginx-reverse'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-snmp'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-sshd'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-optimize'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-PowerDNS'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-schedules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-events'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-syncthing'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=85 id='system-meta-server'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=86 id='system-vsftpd'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=87 id='system-certificates-center'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=88 id='system-old-parameters'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=89 id='system-clamd'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=90 id='network-nettrack'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='network-stunnels'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='network-vde'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='network-etchosts'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='network-vni'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='network-VLAN'></div></li>
  <li><div style='background-color:white;width:1500px;height:3800px;' idnum=96 id='network-hardware'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=97 id='network-arp-table'></div></li>
  
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=98 id='network-system-dns'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=99 id='network-routes'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=100 id='network-bridges'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=101 id='network-hamachi'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=102 id='network-dhcpd'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=103 id='network-dnsmasq'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=104 id='network-PowerDNS'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=105 id='network-OpenVPN'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=106 id='network-NETWORKS'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=107 id='network-BrowseComputers'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=108 id='network-OtherServices'></div></li>
  
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=109 id='members-dash-search'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=110 id='members-dash-freeradius'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=111 id='members-dash-mycomputers'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='members-dash-squidident'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='members-dash-macToUid'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='members-dash-nsswitch'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='squid-dash-booster'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' id='system-dash-backup'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=117 id='system-artica-settings'></div></li>
  
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=118 id='stats-members'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=119 id='stats-flow'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=120 id='stats-websites'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=121 id='stats-options'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=122 id='stats-requests'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=123 id='categories-service2'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=124 id='stats-caches'></div></li>
  <li><div style='background-color:white;width:1500px;height:10000px;' idnum=125 id='webfiltering-db-status'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=126 id='stats-categories'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=127 id='caches-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=128 id='ssl-rules'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=129 id='stats-webfiltering'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=130 id='stats-snicerts'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=131 id='vmware-tools-section'></div></li>
  <li><div style='background-color:white;width:1500px;height:6000px;' idnum=132 id='clamav-updates-section'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=133 id='squid-nas-storage'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=134 id='ufdb-groups'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=135 id='influxdbv8-issue'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=136 id='my-proxy-aliases'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=137 id='kav4proxy-dashboard'></div></li>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=138 id='unifi-controller'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=139 id='influx-update'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=140 id='cached-stats'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=141 id='artica-meta-main-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=142 id='ufdb-unlock-rules'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=143 id='ad-ldap-params'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=144 id='automount-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=145 id='itchart-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=146 id='artica-web-console'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=147 id='mgr_client_list'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=148 id='squid-snmp'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=149 id='system-smtp-nofifs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=150 id='stats-appliance-clients'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=151 id='nginx-web-config'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=152 id='nginx-lists-config'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=153 id='system-memory'></div>
  <li><div style='background-color:white;width:1500px;height:2270px;' idnum=154 id='squid-top-stats'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=155 id='squid-top-members'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=156 id='watchdogsmtpnotifs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=157 id='FollowXforwardedFor'></div>
  <li><div style='background-color:white;width:1500px;height:700px;'  idnum=158 id='squid-top-websites-list'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=159 id='messaging-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=160 id='postfix-networks-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=161 id='postfix-transport-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=162 id='postfix-backupemail-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=163 id='postfix-opendkim-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=164 id='postfix-queues-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=165 id='postfix-fetchmail-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=166 id='postfix-whitelist-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=167 id='postfix-auth-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=168 id='postfix-bodychecks-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=169 id='postfix-postfwd2-dashboard'></div>
  <li><div style='background-color:white;width:1500px;height:2100px;' idnum=170 id='system-graphs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=171 id='squid-paranoid'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=172 id='system-speedtests'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=173 id='system-dnsperfs'></div>
  
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=174 id='postfix-milter-greylist-main'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=175 id='postfix-milter-greylist-acls'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=176 id='postfix-milter-greylist-update'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=177 id='postfix-smtp-rfc'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=178 id='postfix-smtp-logs'></div>
 
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=179 id='pdns-dash-status'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=180 id='pdns-dash-logs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=181 id='postfix-postscreen'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=182 id='postfix-policyd'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=183 id='postfix-instant-iptables'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=184 id='postfix-zarafa-main'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=185 id='postfix-zarafa-mailboxes'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=186 id='system-organizations'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=187 id='postfix-zarafa-webmail'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=188 id='postfix-zarafa-zpush'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=189 id='postfix-current-stats'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=190 id='postfix-dnsbl'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=191 id='transmission-daemon'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=192 id='postfix-milter-regex'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=193 id='postfix-stats-domains'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=194 id='postfix-stats-cdir'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=195 id='squid-external-quotas'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=196 id='postfix-milter-spamass'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=197 id='postfix-RHSBL'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=198 id='squid-bandwidth-general'></div>
  <li><div style='background-color:white;width:1500px;height:10000px;' idnum=199 id='squid-calamaris'></div>
  <li><div style='background-color:white;width:1500px;height:2800px;' idnum=200 id='postfix-queues'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=201 id='vmware-client'></div>
  <li><div style='background-color:white;width:1500px;height:2470px;' idnum=202 id='squid-top-members2'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=203 id='squid-top-members-table'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=204 id='cyrus-main-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=205 id='postfix-domains-table'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=206 id='roundcube-section'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=207 id='squid-external-sessions'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=208 id='ecap-clamav'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=209 id='ecap-gzip'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=210 id='health-monit'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=211 id='health-perfs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=212 id='gateway-secure-page'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=213 id='kaspersky-update-utility'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=214 id='postfix-watchdog-events'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=215 id='postfix-search-events'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=216 id='ss5-main-interface'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=217 id='squid-syslog-interface'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=218 id='system-network-bridges'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=219 id='squid-windows-update'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=220 id='main-nginx-watchdog-table'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=221 id='system-nic-vde'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=222 id='squid-web-copy'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=223 id='apache-watchdoglogs'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=224 id='squid-smtp'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=225 id='quotas-bandwidth'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=226 id='postfix-postmaster'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=227 id='postfix-notifications'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=228 id='suricata-main'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=229 id='ipblocks-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=230 id='squid-ftp-template'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=231 id='squid-youtube-locker'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=232 id='firehol-rules'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=233 id='bandwidthd-stats'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=234 id='suricata-events'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=235 id='groups-search'></div>  
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=236 id='postfix-mysql-distributions'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=237 id='routing-rules'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=238 id='msftncsi-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=239 id='Suricata-Updates'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=240 id='Haproxy-Updates'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=241 id='StatsIDS'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=242 id='phpmyadmin-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=243 id='wordpress-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=244 id='proftpd-update'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=245 id='qos-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=246 id='not-categorized-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=247 id='squid-acls-delegate'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=248 id='messaging-dashboard-ou'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=249 id='messaging-domains-ou'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=250 id='messaging-stats-members'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=251 id='messaging-clamav-milter'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=252 id='messaging-security-update'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=253 id='privoxy-div'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=254 id='postfix-valvulad'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=255 id='postfix-ssl'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=256 id='postfix-whitelist'></div>
  <li><div style='background-color:white;width:1500px;height:1800px;' idnum=257 id='as-by-dom'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=258 id='postfix-smtp-rfc-domain'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=259 id='mimedefang-domain'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=260 id='mimedefang-backuplist'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=261 id='mimedefang-autowhite'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=262 id='mimedefang-extensions'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=263 id='smtp-refused-table'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=264 id='mimedefang-disclaimers'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=265 id='mimedefang-transactions'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=266 id='mimedefang-quarantine'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=267 id='stats-smtp-refused'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=268 id='stats-smtp-flow'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=269 id='mimedefang-autocompress'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=270 id='mimedefang-attachments'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=271 id='stats-smtp-attachs'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=272 id='spamassassin-analyze'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=273 id='spamassassin-urls-rules'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=274 id='spamassassin-escrap-rules'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=275 id='spamassassin-subjects-rules'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=276 id='mimedefang-antivirus-rules'></div>
  <li><div style='background-color:white;width:1500px;height:3000px;' idnum=277 id='sealion-agent'></div>
  
  
  
  <li><div style='background-color:white;width:1500px;height:1800px;' id='none'></div></li>
  
  
  
</ul>
</div>
		
		
<script>
var STATS_ZMD5='';
var DASHBOARD_MEMORY_NIC='';
var VAR_CURRENT_POS=0;
var SystemSchedulesAddon='';
var ApacheWatchdogFileName='';
var FIREHOLE_RULES_ETH='';

if(!IsFunctionExists('LoadAjaxRound')){
	alert('$missing_javascript_function_refresh');
}


  UnlockPage();
  var MainSlider=$('#MainSlider').bxSlider({
  pager:false,
  autoControls: false,
  adaptiveHeight:true,
  controls:false,
  onSliderLoad: function(){
  	
  },
  onSlideBefore: function(slideElement,oldIndex, newIndex){
  	
    if(newIndex==0){ LoadAjaxRound('proxy-dashboard','$page?proxy-dashboard=yes');}
    if(newIndex==1){ LoadAjaxRound('proxy-services','admin.dashboard.proxy.services.php');}
    if(newIndex==2){ LoadAjaxRound('proxy-store-caches','admin.dashboard.proxy.caches.php');}
    if(newIndex==3){ LoadAjaxRound('artica-main-updates','artica.update.php?main_artica_update=config');}
    if(newIndex==4){ LoadAjaxRound('system-main-status','admin.dashboard.system.php');}
    if(newIndex==5){ LoadAjaxRound('squid-member-follower','admin.dashboard.proxy.follower.php');}
    if(newIndex==6){ LoadAjaxRound('squid-webfiltering-main','admin.dashboard.ufdbguard.php');}
    if(newIndex==7){ LoadAjaxRound('artica-license-status','admin.dashboard.license.php');}
    if(newIndex==8){ LoadAjaxRound('categories-service','ufdbcat.php');}
    if(newIndex==9){ LoadAjaxRound('windowsad-service','squid.adker.php?tabs=yes');}
    if(newIndex==10){ LoadAjaxRound('main-dashboard-proxy','squid.dashboard.php');}
    if(newIndex==11){ LoadAjaxRound('main-statistics-options','squid.statistics.options.php');}
    if(newIndex==12){ LoadAjaxRound('main-squid-ports','squid.ports.php');}
    if(newIndex==13){ LoadAjaxRound('main-squid-import-logs','squid.statistics.import.php');}
    if(newIndex==14){ LoadAjaxRound('dashboard-firewall','dashboard.firewall.php');}
    if(newIndex==15){ LoadAjaxRound('firewall-routers','system.network.bridges.php?popup=yes');}
    if(newIndex==16){ LoadAjaxRound('firewall-nats','system.network.nat.php');}
    if(newIndex==17){ LoadAjaxRound('main-squid-ssl_wl','squid.sslbump.php?whitelist=yes');}
    if(newIndex==18){ LoadAjaxRound('main-ntopng','system.ntopng.php');}
    if(newIndex==19){ LoadAjaxRound('main-caches-center','squid.caches.center.php');}
    if(newIndex==20){ LoadAjaxRound('main-caches-level','squid.caches.level.php');}
    if(newIndex==21){ LoadAjaxRound('main-caches-rock','squid.rock.php');}
    if(newIndex==22){ LoadAjaxRound('main-filter-cicap','icap-webfilter.php');}
    if(newIndex==23){ LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');}
    if(newIndex==24){ LoadAjaxRound('main-ufdb-rules','dansguardian2.mainrules.php?main-rules=yes');}
    if(newIndex==25){ LoadAjaxRound('main-fw-nic-rules','firehol.nic.php?nic='+DASHBOARD_MEMORY_NIC);}
    if(newIndex==26){ LoadAjaxRound('main-proxy-members','squid.statistics.current.members.php');}
    if(newIndex==27){ LoadAjaxRound('main-proxy-acls-rules','squid.acls-rules.php');}
    if(newIndex==28){ LoadAjaxRound('main-proxy-acls-groups','squid.acls.groups.php?as-big=yes');}
    if(newIndex==29){ LoadAjaxRound('main-proxy-acls-browsers','squid.browsers-rules.php?popup=yes');}
    if(newIndex==30){ LoadAjaxRound('main-proxy-acls-bandwidth','squid.bandwith.php');}
    if(newIndex==31){ LoadAjaxRound('main-proxy-acls-options','squid.macros.php');}
    if(newIndex==32){ LoadAjaxRound('main-proxy-restricted-members','squid.restricted.members.php');}
    if(newIndex==33){ LoadAjaxRound('main-proxy-blocked-members','squid.blocked.members.php');}
    if(newIndex==34){ LoadAjaxRound('main-proxy-proxy-pac','squid.autoconfiguration.main.php?rules=yes');}
    if(newIndex==35){ LoadAjaxRound('main-proxy-proxy-pac-events','squid.autoconfiguration.main.php?events=yes');}
    if(newIndex==36){ LoadAjaxRound('main-proxy-access-rotate','squid.sourceslogs.php');}
    if(newIndex==37){ LoadAjaxRound('main-proxy-update-table','squid.update.php');}
    if(newIndex==38){ LoadAjaxRound('main-artica-update-table','artica.update.php?main_artica_update=yes');}
    if(newIndex==39){ LoadAjaxRound('main-ufdb-personal-categories','dansguardian2.databases.perso.php?categories=yes');}
    if(newIndex==40){ LoadAjaxRound('main-squid-watchdog-table','squid.watchdog-events.php?important-only=yes');}
    if(newIndex==41){ LoadAjaxRound('main-squid-tasks-table','squid.databases.schedules.php');}
    if(newIndex==42){ LoadAjaxRound('firewall-services-table','firehole.services.php');}
    if(newIndex==43){ LoadAjaxRound('squid-hypercache-table','squid.hypercache.php');}
    if(newIndex==44){ LoadAjaxRound('squid-hypercache-mirror','squid.artica-rules.mirror.php?mirror=yes');}
    if(newIndex==45){ LoadAjaxRound('squid-params-timeouts','squid.timeouts.php?popup=yes');}
    if(newIndex==46){ LoadAjaxRound('ufdb-web-page-errors','squidguardweb.php?tabs=yes');}
    if(newIndex==47){ LoadAjaxRound('watchdog-squid-parameters','squid.proxy.watchdog.php?tabs=yes');}
    if(newIndex==48){ LoadAjaxRound('logs-cache-squid','squid.cachelogs.php');}
    if(newIndex==49){ LoadAjaxRound('squid-debug-tools','squid.debug.php');}
    if(newIndex==50){ LoadAjaxRound('squid-ssl-encrypt','squid.ssl.encrypt.php');}
    if(newIndex==51){ LoadAjaxRound('squid-webf-quotas-rules','squid.artica-quotas.php');}
    if(newIndex==52){ LoadAjaxRound('ufdbguard-behavior-settings','ufdbguard.php?tabs=yes');}
    if(newIndex==53){ LoadAjaxRound('squid-global-blacklist','squid.global.wl.center.php');}
    if(newIndex==54){ LoadAjaxRound('squid-dns-settings','squid.popups.php?content=dns');}
    if(newIndex==55){ LoadAjaxRound('squid-perf-mon','squid.caches.status.php?tabs=yes');}
    if(newIndex==56){ LoadAjaxRound('squid-templates-errors','squid.templates.php?tabs=yes');}
    if(newIndex==57){ LoadAjaxRound('ufdbguard-rewrite-rules','ufdbguard.rewrite.php');}
    if(newIndex==58){ LoadAjaxRound('ufdbguard-terms-rules','squid.terms.groups.php');}
    if(newIndex==59){ LoadAjaxRound('squid-icap-center','icap-center.php');}
    if(newIndex==60){ LoadAjaxRound('squid-transparent','squid.transparent.php');}
    if(newIndex==61){ LoadAjaxRound('hostspot-v3','squid.webauth.php?tabs=yes');}
    if(newIndex==62){ LoadAjaxRound('squid-parent-proxy','squid.parent.proxy.php');}
    if(newIndex==63){ LoadAjaxRound('failover-manager','squid.failover.php?tabs=yes');}
    if(newIndex==64){ LoadAjaxRound('squid-performances','squid.global.performance.php');}
    if(newIndex==65){ LoadAjaxRound('squid-old-status','squid.main.quicklinks.php?function=section_status');}
    if(newIndex==66){ LoadAjaxRound('squid-eye-php','squid.eye.php');}
    if(newIndex==67){ LoadAjaxRound('squid-openldap','squid.openldap.php');}
    if(newIndex==68){ LoadAjaxRound('system-hard-drives','quicklinks.php?function=section_btrfs');}
    if(newIndex==69){ LoadAjaxRound('system-sensors','system.sensors.php');}
    if(newIndex==70){ LoadAjaxRound('system-clock','index.time.php?tabs=yes');}
    if(newIndex==71){ LoadAjaxRound('system-ldap','system.ldap.php?tabs=yes');}
    if(newIndex==72){ LoadAjaxRound('system-mysql','system.mysql.php?tabs=yes');}
    if(newIndex==73){ LoadAjaxRound('system-freeweb','freeweb.php?popup=yes&newinterface=yes');}
  	if(newIndex==74){ LoadAjaxRound('system-wordpress','wordpress.php');}
	if(newIndex==75){ LoadAjaxRound('system-rdp-proxy','squid.rdpproxy.php?tabs=yes');}
    if(newIndex==76){ LoadAjaxRound('system-haproxy','haproxy.php?tabs=yes');}
    if(newIndex==77){ LoadAjaxRound('nginx-reverse','nginx.main.php');}
    if(newIndex==78){ LoadAjaxRound('system-snmp','system.snmp.php');}
    if(newIndex==79){ LoadAjaxRound('system-sshd','sshd.php?in-front-ajax=yes&tabsize=22');}
    if(newIndex==80){ LoadAjaxRound('system-optimize','system.optimize.php');}
    if(newIndex==81){ LoadAjaxRound('system-PowerDNS','pdns.php?tabs=yes&expand=yes');}
    if(newIndex==82){ LoadAjaxRound('system-schedules','schedules.php'+SystemSchedulesAddon);}
    if(newIndex==83){ LoadAjaxRound('system-events','logrotate.php?tabs=yes');}
    if(newIndex==84){ LoadAjaxRound('system-syncthing','syncthing.php');}
    if(newIndex==85){ LoadAjaxRound('system-meta-server','artica-meta.php');}
    if(newIndex==86){ LoadAjaxRound('system-vsftpd','vsftpd.php');}
    if(newIndex==87){ LoadAjaxRound('system-certificates-center','certificates.center.php?tabs=yes');}
    if(newIndex==88){ LoadAjaxRound('system-old-parameters','quicklinks.php?function=section_computers_infos');}
    if(newIndex==89){ LoadAjaxRound('system-clamd','clamd.php?tabs=yes');}
    if(newIndex==90){ LoadAjaxRound('network-nettrack','system.network.conntrack.php');}
    if(newIndex==91){ LoadAjaxRound('network-stunnels','system.network.stunnel4.php');}
    if(newIndex==92){ LoadAjaxRound('network-vde','virtualswitch.php?tabs=yes');}
    if(newIndex==93){ LoadAjaxRound('network-etchosts','system-etc-hosts.php?table=yes?newinterface=yes');}
    if(newIndex==94){ LoadAjaxRound('network-vni','system.nic.virtuals.php');}
    if(newIndex==95){ LoadAjaxRound('network-VLAN','system.nic.vlan.php?newinterface=yes');}
    if(newIndex==96){ LoadAjaxRound('network-hardware','system.nic.infos.php?popup=yes&newinterface=yes');}
    if(newIndex==97){ LoadAjaxRound('network-arp-table','arptable.php');}
    if(newIndex==98){ LoadAjaxRound('network-system-dns','system.nic.config.php?main=DNSServers&newinterface=yes');}
    if(newIndex==99){ LoadAjaxRound('network-routes','system.routes.php');}
    if(newIndex==100){ LoadAjaxRound('network-bridges','system.network.bridges.php');}
    if(newIndex==101){ LoadAjaxRound('network-hamachi','hamachi.php?in-line=yes');}
    if(newIndex==102){ LoadAjaxRound('network-dhcpd','index.gateway.php?index_dhcp_popup=yes');}
    if(newIndex==103){ LoadAjaxRound('network-dnsmasq','dnsmasq.index.php?tabs=yes&newinterface=yes');}
    if(newIndex==104){ LoadAjaxRound('network-PowerDNS','pdns.php?tabs=yes');}
    if(newIndex==105){ LoadAjaxRound('network-OpenVPN','index.openvpn.php?infront=yes');}
    if(newIndex==106){ LoadAjaxRound('network-NETWORKS','computer-browse.php?networks-tabs=yes');}
    if(newIndex==107){ LoadAjaxRound('network-BrowseComputers','computer-browse.php?tabs=yes');}
    if(newIndex==108){ LoadAjaxRound('network-OtherServices','system.index.php?tab=network&newinterface=yes');}
    
    //if(newIndex==109){ LoadAjaxRound('members-dash-search','domains.manage.users.index.php?finduser-tab=yes');}
   	if(newIndex==109){ LoadAjaxRound('members-dash-search','system.members.search.php');}
    if(newIndex==110){ LoadAjaxRound('members-dash-freeradius','freeradius.users.php?t=0&tab=yes');}
    if(newIndex==111){ LoadAjaxRound('members-dash-mycomputers','computer-browse.php?tabs=yes');}
    if(newIndex==112){ LoadAjaxRound('members-dash-squidident','squid.identd.php');}
    if(newIndex==113){ LoadAjaxRound('members-dash-macToUid','squid.macToUid.php');}
    if(newIndex==114){ LoadAjaxRound('members-dash-nsswitch','system.nsswitch.php');}
    if(newIndex==115){ LoadAjaxRound('squid-dash-booster','squid.booster.php?tabs=yes');}
    if(newIndex==116){ LoadAjaxRound('system-dash-backup','artica.backup.php');}
	if(newIndex==117){ LoadAjaxRound('system-artica-settings','artica.settings.php?js-web-interface=yes');}
	if(newIndex==118){ LoadAjaxRound('stats-members','squid.statistics.members.php?zmd5='+STATS_ZMD5);}
	if(newIndex==119){ LoadAjaxRound('stats-flow','squid.statistics.flow.php?zmd5='+STATS_ZMD5);}
	if(newIndex==120){ LoadAjaxRound('stats-websites','squid.statistics.websites.php?zmd5='+STATS_ZMD5);}
	if(newIndex==121){ LoadAjaxRound('stats-options','squid.statistics.options.php');}
	if(newIndex==122){ LoadAjaxRound('stats-requests','squid.statistics.requests.php');}
	if(newIndex==123){ LoadAjaxRound('categories-service2','ufdbcat.php');}
	if(newIndex==124){ LoadAjaxRound('stats-caches','squid.statistics.caches.php');}
	if(newIndex==125){ LoadAjaxRound('webfiltering-db-status','ufdb.categories.status.php');}
	if(newIndex==126){ LoadAjaxRound('stats-categories','squid.statistics.categories.php?zmd5='+STATS_ZMD5);}
	if(newIndex==127){ LoadAjaxRound('caches-rules','squid.cached.sitesinfos.php?sites-list=yes');}
	if(newIndex==128){ LoadAjaxRound('ssl-rules','squid.ssl.rules.php');}
	if(newIndex==129){ LoadAjaxRound('stats-webfiltering','squid.statistics.webfiltering.php');}
	if(newIndex==130){ LoadAjaxRound('stats-snicerts','squid.statistics.snicerts.php');}
	if(newIndex==131){ LoadAjaxRound('vmware-tools-section','VMWareTools.php?popup=yes');}
	if(newIndex==132){ LoadAjaxRound('clamav-updates-section','clamav.updates.php');}
	if(newIndex==133){ LoadAjaxRound('squid-nas-storage','squid.nas.storage.php');}
	if(newIndex==134){ LoadAjaxRound('ufdb-groups','dansguardian2.group.all.php');}
	if(newIndex==135){ LoadAjaxRound('influxdbv8-issue','influxdb.migration.php');}
	if(newIndex==136){ LoadAjaxRound('my-proxy-aliases','proxy.aliases.php');}
	if(newIndex==137){ LoadAjaxRound('kav4proxy-dashboard','kav4proxy.php?inline=yes');}
	if(newIndex==138){ LoadAjaxRound('unifi-controller','unifi.php');}
	if(newIndex==139){ LoadAjaxRound('influx-update','influx.update.php');}
	if(newIndex==140){ LoadAjaxRound('cached-stats','squid.statistics.cached.php');}
	if(newIndex==141){ LoadAjaxRound('artica-meta-main-div','artica-meta.start.php');}     
	if(newIndex==142){ LoadAjaxRound('ufdb-unlock-rules','squidguardweb.rules.php?dashboard=yes');}
	if(newIndex==143){ LoadAjaxRound('ad-ldap-params','squid.adker.php?ldap-params=yes');}
	if(newIndex==144){ LoadAjaxRound('automount-div','autofs.php?tabs=yes');}
	if(newIndex==145){ LoadAjaxRound('itchart-div','itchart.index.php');}
	if(newIndex==146){ LoadAjaxRound('artica-web-console','artica.webconsole.php');}
	if(newIndex==147){ LoadAjaxRound('mgr_client_list','squid.mgr.clientlist.php');}
	if(newIndex==148){ LoadAjaxRound('squid-snmp','squid.snmp.php?popup=yes');}
	if(newIndex==149){ LoadAjaxRound('system-smtp-nofifs','artica.settings.php?ajax-notif-popup=yes');}
	if(newIndex==150){ LoadAjaxRound('stats-appliance-clients','influx.clients.php');}
	if(newIndex==151){ LoadAjaxRound('nginx-web-config','nginx.site.php?tabs=yes&servername='+STATS_ZMD5);}
	if(newIndex==152){ LoadAjaxRound('nginx-lists-config','nginx.www.php');}
	if(newIndex==153){ LoadAjaxRound('system-memory','system.memory.php?popup=yes');}
	if(newIndex==154){ LoadAjaxRound('squid-top-stats','squid.statistics.top.php');}
	if(newIndex==155){ LoadAjaxRound('squid-top-members','squid.statistics.current.members.mysql.php');}
	if(newIndex==156){ LoadAjaxRound('watchdogsmtpnotifs','squid.proxy.watchdog.php?smtp=yes');}
	if(newIndex==157){ LoadAjaxRound('FollowXforwardedFor','squid.FollowXforwardedFor.php');}
	if(newIndex==158){ LoadAjaxRound('squid-top-websites-list','squid.statistics.top.websites.php');}
	if(newIndex==159){ LoadAjaxRound('messaging-dashboard','admin.dashboard.postfix.php');}
	if(newIndex==160){ LoadAjaxRound('postfix-networks-dashboard','postfix.network.php?ajax-popup=yes&hostname=master');}
	if(newIndex==161){ LoadAjaxRound('postfix-transport-dashboard','postfix.transport.table.php?hostname=master');}
	if(newIndex==162){ LoadAjaxRound('postfix-backupemail-dashboard','mimedefang.backup.php');}
	if(newIndex==163){ LoadAjaxRound('postfix-opendkim-dashboard','opendkim.php?popup=yes&mail=master');}
	if(newIndex==164){ Loadjs('postfix.queue.monitoring.php?inline-js=yes&font-size=18');}
	if(newIndex==165){ LoadAjaxRound('postfix-fetchmail-dashboard','fetchmail.index.php?quicklinks=yes');}
	if(newIndex==166){ Loadjs('whitelists.admin.php?js=yes&js-in-line=yes&font-size=18');}
	if(newIndex==167){ LoadAjaxRound('postfix-auth-dashboard','postfix.index.php?popup-auth=yes&hostname=master&ou=master');}
	if(newIndex==168){ LoadAjaxRound('postfix-bodychecks-dashboard','postfix.headers-body-checks.php?tabs=yes&ou=master&hostname=master');}
	if(newIndex==169){ LoadAjaxRound('postfix-postfwd2-dashboard','postfwd2.php?tabs=yes&instance=master&newinterface=yes');}
	if(newIndex==170){ LoadAjaxRound('system-graphs','admin.dashboard.system.graphs.php');}
	if(newIndex==171){ LoadAjaxRound('squid-paranoid','dansguardian2.paranoid.php');}
	if(newIndex==172){ LoadAjaxRound('system-speedtests','admin.dashboard.speedtests.php');}
	if(newIndex==173){ LoadAjaxRound('system-dnsperfs','admin.dashboard.dnsperfs.php');}
	
	if(newIndex==174){ LoadAjaxRound('postfix-milter-greylist-main','milter.greylist.tabs.php');}
	if(newIndex==175){ LoadAjaxRound('postfix-milter-greylist-acls','milter.greylist.index.php?acllist=true&hostname=&expand=yes&ou=');}
	if(newIndex==176){ LoadAjaxRound('postfix-milter-greylist-update','milter.greylist.update.php');}
	if(newIndex==177){ LoadAjaxRound('postfix-smtp-rfc','postfix.smtpd_client_restrictions.php?popup=yes');}
	if(newIndex==178){ LoadAjaxRound('postfix-smtp-logs','postfix.events.new.php?popup=yes');}
	
	if(newIndex==179){ LoadAjaxRound('pdns-dash-status','pdns.php?status=yes');}
	if(newIndex==180){ LoadAjaxRound('pdns-dash-logs','pdns.php?syslog-table=yes');}
	if(newIndex==181){ LoadAjaxRound('postfix-postscreen','postscreen.php?popup=yes&hostname=master&ou=master');}
	if(newIndex==182){ LoadAjaxRound('postfix-policyd','policyd-weight.php?tabs=yes');}
	if(newIndex==183){ Loadjs('postfix.iptables.php?in-front-ajax=yes');}
	if(newIndex==184){ LoadAjaxRound('postfix-zarafa-main','zarafa.index.php?popup=yes&font-size=22');}
	if(newIndex==185){ LoadAjaxRound('postfix-zarafa-mailboxes','zarafa.index.php?popup-mailbox=yes&font-size=22');}
	if(newIndex==186){ LoadAjaxRound('system-organizations','domains.index.php?js-pop=yes');}
	if(newIndex==187){ LoadAjaxRound('postfix-zarafa-webmail','zarafa.webmail.php');}
	if(newIndex==188){ LoadAjaxRound('postfix-zarafa-zpush','zarafa.zpush.php');}
	if(newIndex==189){ LoadAjaxRound('postfix-current-stats','admin.dashboard.pflogsumm.php');}
	if(newIndex==190){ LoadAjaxRound('postfix-dnsbl','postfix.dnsbl.php');}
	if(newIndex==191){ LoadAjaxRound('transmission-daemon','transmission-daemon.php');}
	if(newIndex==192){ LoadAjaxRound('postfix-milter-regex','milter-regex.php');}
	if(newIndex==193){ LoadAjaxRound('postfix-stats-domains','postfix.statstics.domains.php');}
	if(newIndex==194){ LoadAjaxRound('postfix-stats-cdir','postfix.statstics.cdir.php');}
	if(newIndex==195){ LoadAjaxRound('squid-external-quotas','squid.quotas.objects.php');}
	if(newIndex==196){ LoadAjaxRound('postfix-milter-spamass','spamassassin.php');}
	if(newIndex==197){ LoadAjaxRound('postfix-RHSBL','postfix.rhsbl.php');}
	if(newIndex==198){ LoadAjaxRound('squid-bandwidth-general','squid.bandwww.php');}
	if(newIndex==199){ LoadAjaxRound('squid-calamaris','squid.calamaris.php');}
	if(newIndex==200){ LoadAjaxRound('postfix-queues','postfix.queue.monitoring.php?popup=yes');}
	if(newIndex==201){ LoadAjaxRound('vmware-client','VMWareTools.php');}
	if(newIndex==202){ LoadAjaxRound('squid-top-members2','squid.statistics.top.members.php');}
	if(newIndex==203){ LoadAjaxRound('squid-top-members-table','squid.statistics.top.members.table.php');}
	if(newIndex==204){ LoadAjaxRound('cyrus-main-div','cyrus.index.php?popup-index=yes');}
	if(newIndex==205){ LoadAjaxRound('postfix-domains-table','postfix.transport.table.php?organizations=yes&hostname=master');}
	if(newIndex==206){ LoadAjaxRound('roundcube-section','roundcube.index.php?ajax-pop=yes');}
	if(newIndex==207){ LoadAjaxRound('squid-external-sessions','squid.sessions.objects.php');}
	if(newIndex==208){ LoadAjaxRound('ecap-clamav','squid.ecap.clamav.php');}
	if(newIndex==209){ LoadAjaxRound('ecap-gzip','squid.ecap.gzip.php');}
	if(newIndex==210){ LoadAjaxRound('health-monit','system.monit.health.php');}
	if(newIndex==211){ LoadAjaxRound('health-perfs','system.monit.health.reports.php');}
	if(newIndex==212){ LoadAjaxRound('gateway-secure-page','system.gateway.secure.php');}
	if(newIndex==213){ LoadAjaxRound('kaspersky-update-utility','UpdateUtility.php');}
	if(newIndex==214){ LoadAjaxRound('postfix-watchdog-events','postfix.watchdog-events.php');}
	if(newIndex==215){ LoadAjaxRound('postfix-search-events','postfix.events.search.php');}
	if(newIndex==216){ LoadAjaxRound('ss5-main-interface','ss5.index.php');}
	if(newIndex==217){ LoadAjaxRound('squid-syslog-interface','squid.syslog.php');}
	if(newIndex==218){ LoadAjaxRound('system-network-bridges','system.network.bridges.interfaces.php');}
	if(newIndex==219){ LoadAjaxRound('squid-windows-update','squid.windowsupdate.php');}
	if(newIndex==220){ LoadAjaxRound('main-nginx-watchdog-table','nginx.watchdog-events.php?important-only=yes');}
    if(newIndex==221){ LoadAjaxRound('system-nic-vde','virtualswitch.php');}
    if(newIndex==222){ LoadAjaxRound('squid-web-copy','freewebs.HTTrack.php');}
    if(newIndex==223){ LoadAjaxRound('apache-watchdoglogs','apache.watchdog-events.php'+ApacheWatchdogFileName);}
	if(newIndex==224){ LoadAjaxRound('squid-smtp','squid.proxy.watchdog.php?smtp=yes');}
	if(newIndex==225){ LoadAjaxRound('quotas-bandwidth','squid.quotasband.php');}
	if(newIndex==226){ LoadAjaxRound('postfix-postmaster','postfix.postmaster.php');}
	if(newIndex==227){ LoadAjaxRound('postfix-notifications','postfix.notifs.php');}
	if(newIndex==228){ LoadAjaxRound('suricata-main','suricata.main.php');}
	if(newIndex==229){ LoadAjaxRound('ipblocks-div','system.ipblock.php');}
	if(newIndex==230){ LoadAjaxRound('squid-ftp-template','squid.ftp.template.php');}
	if(newIndex==231){ LoadAjaxRound('squid-youtube-locker','squid.youtube.locker.php');}
	if(newIndex==232){ LoadAjaxRound('firehol-rules','firehol.nic.rules.php?eth='+FIREHOLE_RULES_ETH);}
	if(newIndex==233){ LoadAjaxRound('bandwidthd-stats','bandwidthd.stats.php');}
	if(newIndex==234){ LoadAjaxRound('suricata-events','suricata.detected.php');}
	if(newIndex==235){ LoadAjaxRound('groups-search','system.groups.search.php');}
	if(newIndex==236){ LoadAjaxRound('postfix-mysql-distributions','postfix.routing.diffusion.php?hostname=master');}
	if(newIndex==237){ LoadAjaxRound('routing-rules','system.routing.rules.php');}
	if(newIndex==238){ LoadAjaxRound('msftncsi-div','system.msftncsi.php');}
	if(newIndex==239){ LoadAjaxRound('Suricata-Updates','suricata.update.php');}
	if(newIndex==240){ LoadAjaxRound('Haproxy-Updates','haproxy.update.php');}
	if(newIndex==241){ LoadAjaxRound('StatsIDS','suricata.stats.php');}
	if(newIndex==242){ LoadAjaxRound('phpmyadmin-div','system.mysql.phpmyadmin.php');}
	if(newIndex==243){ LoadAjaxRound('wordpress-div','wordpress.php');}
	if(newIndex==244){ LoadAjaxRound('proftpd-update','proftpd.update.php');}
	if(newIndex==245){ LoadAjaxRound('qos-div','system.qos.containers.php');}
	if(newIndex==246){ LoadAjaxRound('not-categorized-div','squid.statistics.not-categorized.php');}
	if(newIndex==247){ LoadAjaxRound('squid-acls-delegate','squid.acls.dynamics.php?gpid='+STATS_ZMD5);}
	if(newIndex==248){ LoadAjaxRound('messaging-dashboard-ou','admin.dashboard.postfix-byou.php?ou='+STATS_ZMD5);}
	if(newIndex==249){ LoadAjaxRound('messaging-domains-ou','domains.ou.domains.php?ou='+STATS_ZMD5);}
	if(newIndex==250){ LoadAjaxRound('messaging-stats-members','postfix.statistics.members.php?zmd5='+STATS_ZMD5);}
	if(newIndex==251){ LoadAjaxRound('messaging-clamav-milter','clamd.php?tabs=yes');}
	if(newIndex==252){ LoadAjaxRound('messaging-security-update','mailsecurity.update.php');}
	if(newIndex==253){ LoadAjaxRound('privoxy-div','privoxy.php');}
	if(newIndex==254){ LoadAjaxRound('postfix-valvulad','postfix.valvulad.php?tabs=yes');}
	if(newIndex==255){ LoadAjaxRound('postfix-ssl','postfix.ssl.php');}
	if(newIndex==256){ LoadAjaxRound('postfix-whitelist','fw.whitehosts.php');}
	if(newIndex==257){ LoadAjaxRound('as-by-dom','spamassassin.domains.php?domain='+STATS_ZMD5);}
	if(newIndex==258){ LoadAjaxRound('postfix-smtp-rfc-domain','postfix.domains.smtpd_client_restrictions.php?domain='+STATS_ZMD5);}
	if(newIndex==259){ LoadAjaxRound('mimedefang-domain','mimedefang.php?tabs=yes');}
	if(newIndex==260){ LoadAjaxRound('mimedefang-backuplist','mimedefang.backup.query.php');}
	if(newIndex==261){ LoadAjaxRound('mimedefang-autowhite','mimedefang.autowhite.query.php');}
	if(newIndex==262){ LoadAjaxRound('mimedefang-extensions','mimedefang.extensions.php');}
	if(newIndex==263){ LoadAjaxRound('smtp-refused-table','postfix.refused.table.php');}
	if(newIndex==264){ LoadAjaxRound('mimedefang-disclaimers','mimedefang.disclaimers.php');}
	if(newIndex==265){ LoadAjaxRound('mimedefang-transactions','mimedefang.transactions.php');}
	if(newIndex==266){ LoadAjaxRound('mimedefang-quarantine','mimedefang.quarantine.php');}
	if(newIndex==267){ LoadAjaxRound('stats-smtp-refused','postfix.stats-refused.php?zmd5='+STATS_ZMD5);}
	if(newIndex==268){ LoadAjaxRound('stats-smtp-flow','postfix.stats-flow.php?zmd5='+STATS_ZMD5);}
	if(newIndex==269){ LoadAjaxRound('mimedefang-autocompress','mimedefang.autocompress.php');}
	if(newIndex==270){ LoadAjaxRound('mimedefang-attachments','mimedefang.attachments.php');}
	if(newIndex==271){ LoadAjaxRound('stats-smtp-attachs','postfix.stats-attachs.php?zmd5='+STATS_ZMD5);}
	if(newIndex==272){ LoadAjaxRound('spamassassin-analyze','spamassassin.analyze.php');}
	if(newIndex==273){ LoadAjaxRound('spamassassin-urls-rules','spamassassin.urls.php');}
	if(newIndex==274){ LoadAjaxRound('spamassassin-escrap-rules','spamassassin.escrap.php');}
	if(newIndex==275){ LoadAjaxRound('spamassassin-subjects-rules','spamassassin.subjects.php');}
	if(newIndex==276){ LoadAjaxRound('mimedefang-antivirus-rules','mimedefang.antivirus.php');}
	if(newIndex==277){ LoadAjaxRound('sealion-agent','sealion.agent.php');}
	
	
	
	
	}
});	

function GoToIndex(){if(VAR_CURRENT_POS==0){return;}cleandivsDash();VAR_CURRENT_POS=0;MainSlider.goToSlide(0);}
function GoToServices(){if(VAR_CURRENT_POS==1){return;}cleandivsDash();VAR_CURRENT_POS=1; MainSlider.goToSlide(1);}  		
function GoToCaches(){VAR_CURRENT_POS=2;cleandivsDash();MainSlider.goToSlide(2);}
function GoToPerfs(){VAR_CURRENT_POS=3;cleandivsDash();MainSlider.goToSlide(4);}
function GoToArticaUpdate(){if(VAR_CURRENT_POS==3){return;}VAR_CURRENT_POS=3;cleandivsDash();MainSlider.goToSlide(3);}
function GoToSystem(){if(VAR_CURRENT_POS==4){return;}VAR_CURRENT_POS=4;cleandivsDash();MainSlider.goToSlide(4);}
function GoToFollower(){if(VAR_CURRENT_POS==5){return;}VAR_CURRENT_POS=5;cleandivsDash();MainSlider.goToSlide(5);}
function GoToUfdb(){if(VAR_CURRENT_POS==6){return;}VAR_CURRENT_POS=6;cleandivsDash();MainSlider.goToSlide(6);}
function GoToArticaLicense(){if(VAR_CURRENT_POS==7){return;}VAR_CURRENT_POS=7;cleandivsDash();MainSlider.goToSlide(7);}
function GoToCategoriesServiceA(){if(VAR_CURRENT_POS==8){return;}VAR_CURRENT_POS=8;cleandivsDash();MainSlider.goToSlide(8);}
function GotoAdConnection(){if(VAR_CURRENT_POS==9){return;}VAR_CURRENT_POS=9;cleandivsDash();MainSlider.goToSlide(9);}
function GoToActiveDirectory(){ GotoAdConnection();}
function LoadStatisticsOptions(){cleandivsDash();VAR_CURRENT_POS=11;MainSlider.goToSlide(11);}
function LoadMainDashProxy(){if(VAR_CURRENT_POS==10){return;}cleandivsDash();VAR_CURRENT_POS=10;MainSlider.goToSlide(10);}
function GotoSquidPorts(){cleandivsDash();VAR_CURRENT_POS=12;MainSlider.goToSlide(12);}
function GotoFirewall(){if(VAR_CURRENT_POS==14){return;}cleandivsDash();VAR_CURRENT_POS=14;MainSlider.goToSlide(14);}
function LoadStatisticsImport(){cleandivsDash();VAR_CURRENT_POS=13;MainSlider.goToSlide(13);}
function GotoRouters(){cleandivsDash();VAR_CURRENT_POS=15;MainSlider.goToSlide(15);}
function GotoNATRules(){cleandivsDash();VAR_CURRENT_POS=16;MainSlider.goToSlide(16);}
function GotoSquidSSLWL(){LoadDivDash(17);}

function GotoNTOPNG(){LoadDivDash(18);}  
function GoToCachesCenter(){LoadDivDash(19);}  
function GoToCachesLevel(){LoadDivDash(20);} 
function GoToRock(){LoadDivDash(21);} 
function GoToCICAP(){LoadDivDash(22);} 
function GoToUfdbguardMain(){LoadDivDash(23);} 
function GoToUfdbguardRules(){	LoadDivDash(24);}
function GoToNicFirewallConfiguration(nic){cleandivsDash();VAR_CURRENT_POS=25;DASHBOARD_MEMORY_NIC=nic; MainSlider.goToSlide(25);}
function GotoProxyCurrentMembers(){DASHBOARD_MEMORY_NIC=''; VAR_CURRENT_POS=26; cleandivsDash(); MainSlider.goToSlide(26);}
function GoToSquidAcls(){DASHBOARD_MEMORY_NIC=''; cleandivsDash(); VAR_CURRENT_POS=27; MainSlider.goToSlide(27);}
function GoToSquidAclsGroups(){DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=28;MainSlider.goToSlide(28);}
function GoToSquidAclsBrowsers(){DASHBOARD_MEMORY_NIC='';VAR_CURRENT_POS=29;cleandivsDash();	MainSlider.goToSlide(29);}
function GoToSquidAclsBandwidth(){DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=30;MainSlider.goToSlide(30);}
function GoToSquidAclsOptions(){LoadDivDash(31);;}
function GoToSquidRestrictedMembers(){DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=32;MainSlider.goToSlide(32);}
function GoToSquidBlockedMembers(){DASHBOARD_MEMORY_NIC='';cleandivsDash(); VAR_CURRENT_POS=33; MainSlider.goToSlide(33);}
function GoToProxyPac(){DASHBOARD_MEMORY_NIC=''; cleandivsDash(); VAR_CURRENT_POS=34; MainSlider.goToSlide(34);}
function ProxyPacEvents(){DASHBOARD_MEMORY_NIC='';cleandivsDash(); VAR_CURRENT_POS=35; MainSlider.goToSlide(35);}
function LoadSquidRotate(){DASHBOARD_MEMORY_NIC='';cleandivsDash(); VAR_CURRENT_POS=36; MainSlider.goToSlide(36);}
function LoadProxyUpdate(){DASHBOARD_MEMORY_NIC=''; cleandivsDash(); VAR_CURRENT_POS=37; MainSlider.goToSlide(37);}
function GotToArticaUpdate(){DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=38; MainSlider.goToSlide(38);}
function GotoYourcategories(){LoadDivDash(39);}

function GotoWatchdog(){cleandivsDash();VAR_CURRENT_POS=40;MainSlider.goToSlide(40);}
function GotoSquidTasks(){cleandivsDash();VAR_CURRENT_POS=41;MainSlider.goToSlide(41);}
function GotoFireholServices(){cleandivsDash();VAR_CURRENT_POS=42;MainSlider.goToSlide(42);}
function GoToHyperCache(){cleandivsDash();VAR_CURRENT_POS=43;MainSlider.goToSlide(43);}
function GoToHyperCacheMirror(){cleandivsDash();VAR_CURRENT_POS=44;MainSlider.goToSlide(44);}
function GotoSquidTimeOuts(){cleandivsDash();VAR_CURRENT_POS=45;MainSlider.goToSlide(45);}
function GotoUfdbErrorPage(){LoadDivDash(46);}
function GotoWatchdogParameters(){VAR_CURRENT_POS=47;cleandivsDash();MainSlider.goToSlide(47);}
function GotoLogsCacheSquid(){VAR_CURRENT_POS=48;cleandivsDash();MainSlider.goToSlide(48);}
function LoadProxyDebug(){VAR_CURRENT_POS=49;cleandivsDash();MainSlider.goToSlide(49);}
function GotoSSLEncrypt(){VAR_CURRENT_POS=50;cleandivsDash();MainSlider.goToSlide(50);}
function GotoSquidWebfilterQuotas(){VAR_CURRENT_POS=51;cleandivsDash();MainSlider.goToSlide(51);}
function GotoUfdbServiceBehavior(){VAR_CURRENT_POS=52;cleandivsDash();MainSlider.goToSlide(52);}
function GotoGlobalBLCenter(){VAR_CURRENT_POS=53;cleandivsDash();MainSlider.goToSlide(53);}
function GotoSquidDNSsettings(){VAR_CURRENT_POS=54;cleandivsDash();MainSlider.goToSlide(54);}
function GotoSquidPerfMonitor(){VAR_CURRENT_POS=55; cleandivsDash();MainSlider.goToSlide(55);}
function GotoSquidTemplatesErrors(){VAR_CURRENT_POS=56; cleandivsDash();MainSlider.goToSlide(56);}

function GotoUfdbRewriteRules(){VAR_CURRENT_POS=57;cleandivsDash();MainSlider.goToSlide(57);}
function GotoUfdbTermsGroups(){VAR_CURRENT_POS=58; cleandivsDash();MainSlider.goToSlide(58);}
function GotoICAPCenter(){LoadDivDash(59);}
function GotoFirewallLinks(){VAR_CURRENT_POS=60;cleandivsDash();MainSlider.goToSlide(60);}
function GotoHostpotv3(){VAR_CURRENT_POS=61;cleandivsDash();MainSlider.goToSlide(61);}
function GotoSquidParentProxy(){VAR_CURRENT_POS=62;cleandivsDash();MainSlider.goToSlide(62);}
function GotoFailover(){VAR_CURRENT_POS=63;cleandivsDash();MainSlider.goToSlide(63);}
function GotoSquidPerformances(){VAR_CURRENT_POS=64; cleandivsDash();MainSlider.goToSlide(64);}
function GotoSquidOldStatus(){VAR_CURRENT_POS=65; cleandivsDash();MainSlider.goToSlide(65);}
function GotoMainLogs(){if(VAR_CURRENT_POS==66){return;} cleandivsDash(); VAR_CURRENT_POS=66; MainSlider.goToSlide(66);}
function GotoOpenldap(){if(VAR_CURRENT_POS==67){return;} cleandivsDash(); VAR_CURRENT_POS=67; MainSlider.goToSlide(67);}
function GotoHarddrive(){if(VAR_CURRENT_POS==68){return;} cleandivsDash(); VAR_CURRENT_POS=68; MainSlider.goToSlide(68);}
function GotoSenSors(){if(VAR_CURRENT_POS==69){return;} cleandivsDash(); VAR_CURRENT_POS=69; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoClock(){if(VAR_CURRENT_POS==70){return;} cleandivsDash(); VAR_CURRENT_POS=70; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoOpenLDAP(){if(VAR_CURRENT_POS==71){return;} cleandivsDash(); VAR_CURRENT_POS=71; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotToMySQL(){if(VAR_CURRENT_POS==72){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=72; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotToFreeWeb(){if(VAR_CURRENT_POS==73){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=73; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotToWordpress(){if(VAR_CURRENT_POS==74){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=74; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotToRDPPROX(){if(VAR_CURRENT_POS==75){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=75; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotToHAPROXY(){if(VAR_CURRENT_POS==76){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=76; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoReverseProxy(){LoadDivDash(77);}
function GotoSNMPD(){if(VAR_CURRENT_POS==78){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=78; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoSSHD(){if(VAR_CURRENT_POS==79){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=79; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoOptimizeSystem(){if(VAR_CURRENT_POS==80){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=80; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoPowerDNS(){if(VAR_CURRENT_POS==81){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=81; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoSystemSchedules(ForceTask){if(ForceTask){SystemSchedulesAddon='?ForceTaskType='+ForceTask;}else{SystemSchedulesAddon='';}LoadDivDash(82);}
function GotoSystemEvents(){if(VAR_CURRENT_POS==83){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=83; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoSyncThing(){if(VAR_CURRENT_POS==84){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=84; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoArticaMeta(){if(VAR_CURRENT_POS==85){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=85; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoVSFTPD(){if(VAR_CURRENT_POS==86){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=86; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoCertificatesCenter(){if(VAR_CURRENT_POS==87){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=87; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoSystemGlobalParameters(){if(VAR_CURRENT_POS==88){return;} DASHBOARD_MEMORY_NIC='';cleandivsDash();VAR_CURRENT_POS=88; MainSlider.goToSlide(VAR_CURRENT_POS);}
function GotoClamdSection(){LoadDivDash(89);}
function GotoNetTrack(){LoadDivDash(90);}
function GotoStunnels(){LoadDivDash(91);}
function GotoVDE(){LoadDivDash(92);}
function GotoETCHOSTS(){LoadDivDash(93);}
function GotoVNI(){LoadDivDash(94);}
function GotoVLAN(){LoadDivDash(95);}
function GotoNetHard(){LoadDivDash(96);}
function GotoARTPTable(){LoadDivDash(97);}
function GotoSystemDNS(){LoadDivDash(98);}
function GotoNetworkRoutes(){LoadDivDash(99);}
function GotoNetworkBridges(){LoadDivDash(100);}
function GotoNetworkHamachi(){LoadDivDash(101);}
function GotoNetworkDHCPD(){LoadDivDash(102);}
function GotoNetworkDNSMASQ(){LoadDivDash(103);}
function GotoNetworkPowerDNS(){LoadDivDash(104);}
function GotoNetworkOpenVPN(){LoadDivDash(105);}
function GotoNetworkNETWORKS(){LoadDivDash(106);}
function GotoNetworkBrowseComputers(){LoadDivDash(107);}
function GotoNetworkOtherServices(){LoadDivDash(108);}
function GotoMembersSearch(){LoadDivDash(109);}
function GotoMembersRadius(){LoadDivDash(110);}
function GotoMemberMyComp(){LoadDivDash(111);}
function GotoSquidIdent(){LoadDivDash(112);}
function GotomacToUid(){LoadDivDash(113);}
function GotoNsswitch(){LoadDivDash(114);}
function GotoProxyBooster(){LoadDivDash(115);}
function GotoArticaBackup(){LoadDivDash(116);}
function GotoArticaSettings(){LoadDivDash(117);}
function GoToStatsMembers(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(118);}
function GoToStatsFlow(zmd5){if(zmd5){STATS_ZMD5=zmd5;} LoadDivDash(119);}
function GoToWebsitesStats(zmd5){if(zmd5){STATS_ZMD5=zmd5;} LoadDivDash(120);}
function GoToStatsOptions(){ LoadDivDash(121);}
function GoToStatsRequests(){LoadDivDash(122);}
function GoToCategoriesService(){LoadDivDash(123);}
function GoToStatsCache(){LoadDivDash(124);}
function GoToWebfilteringDBstatus(){LoadDivDash(125);}
function GoToStatisticsByCategories(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(126);}
function GotoSquidCachesRules(){LoadDivDash(127);}
function GotoSSLRules(){LoadDivDash(128);}
function GoToStatisticsByWebFiltering(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(129);}
function GoToSniCerts(){LoadDivDash(130);}
function GotoVMWareTools(){LoadDivDash(131);}
function GotoClamavUpdates(){LoadDivDash(132);}
function GotoSquidNasStorage(){LoadDivDash(133);}
function GotoUfdbGroups(){LoadDivDash(134);}
function GoToInfluxDBMigr8(){LoadDivDash(135);}
function GoToProxyAliases(){LoadDivDash(136);}
function GoToKav4Proxy(){LoadDivDash(137);}
function GotoUnifi(){LoadDivDash(138);}
function GotoInfluxUpdate(){LoadDivDash(139);}
function GoToCachedStatistics(){LoadDivDash(140);}
function GoToMeta(){LoadDivDash(141);}
function GotoUfdbUnlockPages(){LoadDivDash(142);}
function GotoActiveDirectoryLDAPParams(){LoadDivDash(143);}
function GotoAutomount(){LoadDivDash(144);}
function GotoItChart(){LoadDivDash(145);}
function GotoArticaWebConsole(){LoadDivDash(146);}
function GotoMgrClientList(){LoadDivDash(147);}
function GotoSquidSNMP(){LoadDivDash(148);}
function GotoSMTPNOTIFS(){LoadDivDash(149);}
function GotoStatsApplianceClients(){LoadDivDash(150);}
function GoToNginxLists(){LoadDivDash(152);}
function GoToNginxOption(zsite){if(zsite){STATS_ZMD5=zsite;}LoadDivDash(151);}
function GotoSystemMemory(){LoadDivDash(153);}
function GotoSquidTopStats(){LoadDivDash(154);}
function GotoProxyMysqlCurrentMembers(){LoadDivDash(155);}
function GotoWatchDogSMTPNotifs(){LoadDivDash(156);}
function GotoFollowXforwardedFor(){LoadDivDash(157);}
function GotoMysQLAllWebsites(){LoadDivDash(158);}
function GoToMessaging(){LoadDivDash(159);}
function GoToPostfixNetworks(){LoadDivDash(160);}
function GoToPostfixRouting(){LoadDivDash(161);}
function GoToBackupeMail(){LoadDivDash(162);}
function GoToOpenDKIM(){LoadDivDash(163);}
function GotoPostfixQueues(){LoadDivDash(164);}
function GotoFetchMail(){LoadDivDash(165);}
function GotoPostfixWhiteList(){LoadDivDash(166);}
function GotoPostfixAuth(){LoadDivDash(167);}
function GotoPostfixBodyChecks(){LoadDivDash(168);}
function GotoPostfixPostfwd2(){LoadDivDash(169);}
function GotoStatsSystem(){LoadDivDash(170);}
function GotoParanoidMode(){LoadDivDash(171);}
function GotoSpeedTests(){ LoadDivDash(172);}
function GotoDNSPerfs(){ LoadDivDash(173);}

function GotoMilterGreyListMain(){ LoadDivDash(174);}
function GotoMilterGreyListACLS(){ LoadDivDash(175);}
function GotoMilterGreyListUpdate(){ LoadDivDash(176);}
function GotoSMTPRFC(){ LoadDivDash(177);}
function GotoPostfixMainLogs(){ LoadDivDash(178);}
function GotoNetworkPowerDNSStatus(){LoadDivDash(179);}
function GotoNetworkPowerDNSLOGS(){LoadDivDash(180);}
function GotoPostScreen(){LoadDivDash(181);}
function GotoPolicyDaemon(){LoadDivDash(182);}
function GotoInstantIpTables(){LoadDivDash(183);}
function GoToZarafaMain(){LoadDivDash(184);}
function GoToZarafaMailboxes(){LoadDivDash(185);}
function GoToOrganizations(){LoadDivDash(186);}
function GoToZarafaWebMail(){LoadDivDash(187);}
function GoToZarafaZPush(){LoadDivDash(188);}
function GotoPflogsummDetails(){LoadDivDash(189);}
function GotoPostfixDNSBL(){LoadDivDash(190);}
function GoToTransmissionDaemon(){LoadDivDash(191);}
function GotoPostfixMilterRegex(){LoadDivDash(192);}
function GotoSMTPTableDomains(){LoadDivDash(193);}
function GotoSMTPTableCDIR(){LoadDivDash(194);}
function GotoProxyQuotasObjects(){LoadDivDash(195);}
function GotoMilterSpamass(){LoadDivDash(196);}
function GotoPostfixRHSBL(){LoadDivDash(197);}
function GotoSquidBandwG(){LoadDivDash(198);}
function GotoSquidCalamaris(){LoadDivDash(199);}
function PostfixQueueMonitoring(){LoadDivDash(200);}
function GotoVMWareClient(){LoadDivDash(201);}
function GotoProxyMysqlTOPMembers(){LoadDivDash(202);}
function GotoProxyMysqlTOPMembersTable(){LoadDivDash(203);}
function GotoCyrusManager(){LoadDivDash(204);}
function GoToPostfixDomains(){LoadDivDash(205);}
function GoToRoundCube(){LoadDivDash(206);}
function GoToSquidSessionsObjects(){LoadDivDash(207);}
function GoToeCapClamav(){LoadDivDash(208);}
function GoToeCapGzip(){LoadDivDash(209);}
function GotoSystemHealthMonit(){ LoadDivDash(210);}
function GotoDashBoardPerfQueue(){ LoadDivDash(211);}
function GotoGatewaySecure(){ LoadDivDash(212);}
function GoToUpdateUtility(){ LoadDivDash(213);}
function GoToPostfixWatchdog(){ LoadDivDash(214);}
function GoToPostfixSearchEvents(){ LoadDivDash(215);}
function GoToSS5(){ LoadDivDash(216);}
function GotoSquidSyslog(){ LoadDivDash(217);} 
function GotoBridges(){LoadDivDash(218);}
function GotoWindowsUpdate(){LoadDivDash(219);}
function GotoNginxImportEvents(){LoadDivDash(220);}
function GotoVdeSwichs(){LoadDivDash(221);}
function GotoWebCopy(){LoadDivDash(222);}
function GotoApacheWatchdog(filename){ApacheWatchdogFileName='';if(filename){ApacheWatchdogFileName='?filename='+filename;}LoadDivDash(223);}
function GotoSquidSMTP(){LoadDivDash(224);}
function GotoQuotasBand(){LoadDivDash(225);}
function GotoPostfixPostmaster(){LoadDivDash(226);}
function GotoPostfixNotifications(){LoadDivDash(227);}
function GotoSuricata(){LoadDivDash(228);}
function GotoIpBlocks(){LoadDivDash(229);}
function GotoSquidTemplateFTP(){LoadDivDash(230);}
function GotoYoutubeLocker(){LoadDivDash(231);}
function GotoFireholeRules(eth){FIREHOLE_RULES_ETH='';if(eth){FIREHOLE_RULES_ETH=eth;}LoadDivDash(232);}
function GotoBandwidthdStats(){LoadDivDash(233);}
function GotoSuricataEvents(){LoadDivDash(234);}   
function GotoGroupsSearch(){LoadDivDash(235);}   
function GotoPostfixDitribs(){LoadDivDash(236);}
function GotoRoutingRules(){LoadDivDash(237);}
function GotoMsftncsi(){LoadDivDash(238);}
function GotoSuricataUpdates(){LoadDivDash(239);}
function GotoHaProxyUpdates(){LoadDivDash(240);}
function GoToStatsIDS(zmd5){if(zmd5){STATS_ZMD5=zmd5;} LoadDivDash(241);}
function GotoPHPMyAdmin(){LoadDivDash(242);}
function GotoWordPress(){LoadDivDash(243);}
function GotoProftpdUpdate(){LoadDivDash(244);}
function GotoQOS(){LoadDivDash(245);}
function GotoNotCategorized(){LoadDivDash(246);}
function GotoAclsDeletegate(gid){if(gid){STATS_ZMD5=gid;} LoadDivDash(247);}	 
function GoToMessagingOU(ou){if(ou){STATS_ZMD5=ou;} LoadDivDash(248);}
function GoToDomainsOU(ou){if(ou){STATS_ZMD5=ou;} LoadDivDash(249);}	 
function GoToStatsSMTPMembers(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(250);}
function GotoMilterClamav(){LoadDivDash(251);}
function GotoMessagingSecurityUpdate(){LoadDivDash(252);}
function GotoPrivoxy(){LoadDivDash(253);}
function GotoValvuad(){LoadDivDash(254);}
function GotoPostfixSSL(){LoadDivDash(255);}
function GotoPostfixWhiteListG(){LoadDivDash(256);}
function GoToAntiSpamsDomain(domain){STATS_ZMD5=domain;LoadDivDash(257);}
function GoToRFCDomain(domain){STATS_ZMD5=domain;LoadDivDash(258);}
function GotoMimeDefang(){LoadDivDash(259);}
function GotoBackupMails(){LoadDivDash(260);}
function GotoAutoWhite(){LoadDivDash(261);}
function GotoMimeDefangExtensions(){LoadDivDash(262);}
function GotoSMTPRefused(){LoadDivDash(263);}
function GotoMimeDefangDisclaimers(){LoadDivDash(264);}
function GotoSMTPTransactions(){LoadDivDash(265);}
function GotoQuarantineMails(){LoadDivDash(266);}
function GoToStatsSMTPRefused(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(267);}
function GoToStatsSMTPFlow(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(268);}
function GotoMimeDefangAutocompress(){LoadDivDash(269);}
function GotoSMTPAttachments(){LoadDivDash(270);}
function GoToStatsAttachs(zmd5){if(zmd5){STATS_ZMD5=zmd5;}LoadDivDash(271);}
function GoToSpamAssassinAnalyze(){LoadDivDash(272);}
function GotoSpamAssRulesDomains(){LoadDivDash(273);}
function GotoSpamAssRulesEscrap(){LoadDivDash(274);}
function GotoSpamAssRulesSubjects(){LoadDivDash(275);}
function GotoMimeDefangAntivirus(){LoadDivDash(276);}
function GoToSealionAgent(){LoadDivDash(277);}

function LoadDivDash(Number){
	
	if(VAR_CURRENT_POS==Number){return;} 
	DASHBOARD_MEMORY_NIC='';
	cleandivsDash();
	VAR_CURRENT_POS=Number; 
	MainSlider.goToSlide(VAR_CURRENT_POS);
}


function RemoveDiv(id){
	if(!document.getElementById(id)){return;}
	document.getElementById(id).innerHTML='';
}


function cleandivsDash(){
	$('html, body').animate({ scrollTop: 0 }, 'fast');
	DASHBOARD_MEMORY_NIC='';
	RemoveDiv('sealion-agent');
	RemoveDiv('mimedefang-antivirus-rules');
	RemoveDiv('spamassassin-subjects-rules');
	RemoveDiv('spamassassin-escrap-rules');
	RemoveDiv('spamassassin-urls-rules');
	RemoveDiv('spamassassin-analyze');
	RemoveDiv('stats-smtp-attachs');
	RemoveDiv('mimedefang-attachments');
	RemoveDiv('mimedefang-autocompress');
	RemoveDiv('stats-smtp-flow');
	RemoveDiv('stats-smtp-refused');
	RemoveDiv('mimedefang-quarantine');
	RemoveDiv('mimedefang-transactions');
	RemoveDiv('mimedefang-disclaimers');
	RemoveDiv('smtp-refused-table');
	RemoveDiv('mimedefang-extensions');
	RemoveDiv('mimedefang-autowhite');
	RemoveDiv('mimedefang-backuplist');
	RemoveDiv('mimedefang-domain');
	RemoveDiv('postfix-whitelist');
	RemoveDiv('postfix-ssl');
	RemoveDiv('postfix-valvulad');
	RemoveDiv('privoxy-div');
	RemoveDiv('messaging-security-update');
	RemoveDiv('messaging-clamav-milter');
	RemoveDiv('messaging-stats-members');
	RemoveDiv('messaging-dashboard-ou');
	RemoveDiv('squid-acls-delegate');
	RemoveDiv('not-categorized-div');
	RemoveDiv('qos-div');
	RemoveDiv('proftpd-update');
	RemoveDiv('phpmyadmin-div');
	RemoveDiv('wordpress-div');
	RemoveDiv('StatsIDS');
	RemoveDiv('Suricata-Updates');
	RemoveDiv('Haproxy-Updates');
	RemoveDiv('msftncsi-div');
	RemoveDiv('routing-rules');
	RemoveDiv('postfix-mysql-distributions');
	RemoveDiv('groups-search');
	RemoveDiv('suricata-events');
	RemoveDiv('bandwidthd-stats');
	RemoveDiv('squid-youtube-locker');
	RemoveDiv('squid-ftp-template');
	RemoveDiv('ipblocks-div');
	RemoveDiv('suricata-main');
	RemoveDiv('postfix-notifications');
	RemoveDiv('postfix-postmaster');
	RemoveDiv('quotas-bandwidth');
	RemoveDiv('squid-smtp');
	RemoveDiv('apache-watchdoglogs');
	RemoveDiv('system-schedules');
	RemoveDiv('squid-web-copy');
	RemoveDiv('system-nic-vde');
	RemoveDiv('main-nginx-watchdog-table');
	RemoveDiv('squid-windows-update');
	RemoveDiv('system-network-bridges');
	RemoveDiv('squid-syslog-interface');
	RemoveDiv('ss5-main-interface');
	RemoveDiv('postfix-search-events');
	RemoveDiv('postfix-watchdog-events');
	RemoveDiv('kaspersky-update-utility');
	RemoveDiv('gateway-secure-page');
	RemoveDiv('health-perfs');
	RemoveDiv('health-monit');
	RemoveDiv('ecap-gzip');
	RemoveDiv('ecap-clamav');
	RemoveDiv('squid-external-sessions');
	RemoveDiv('roundcube-section');
	RemoveDiv('postfix-domains-table');
	RemoveDiv('cyrus-main-div');
	RemoveDiv('squid-top-members-table');
	RemoveDiv('squid-top-members2');
	RemoveDiv('vmware-client');
	RemoveDiv('squid-external-quotas');
	RemoveDiv('postfix-stats-cdir');
	RemoveDiv('postfix-stats-domains');
	RemoveDiv('transmission-daemon');
	RemoveDiv('postfix-dnsbl');
	RemoveDiv('postfix-current-stats');
	RemoveDiv('postfix-zarafa-zpush');
	RemoveDiv('postfix-zarafa-webmail');
	RemoveDiv('system-organizations');
	RemoveDiv('postfix-zarafa-mailboxes');
	RemoveDiv('postfix-zarafa-main');
	RemoveDiv('postfix-instant-iptables');
	RemoveDiv('postfix-policyd');
	RemoveDiv('postfix-postscreen');
	RemoveDiv('pdns-dash-logs');
	RemoveDiv('pdns-dash-status');
	RemoveDiv('postfix-smtp-rfc');
	RemoveDiv('postfix-milter-greylist-update');
	RemoveDiv('postfix-milter-greylist-acls');
	RemoveDiv('postfix-milter-greylist-main');
	RemoveDiv('system-dnsperfs');
	RemoveDiv('system-speedtests');
	RemoveDiv('squid-paranoid');
	RemoveDiv('system-graphs');
	RemoveDiv('postfix-postfwd2-dashboard');
	RemoveDiv('postfix-bodychecks-dashboard');
	RemoveDiv('squid-bandwidth-general');
	RemoveDiv('postfix-auth-dashboard');
	RemoveDiv('postfix-whitelist-dashboard');
	RemoveDiv('postfix-fetchmail-dashboard');
	RemoveDiv('postfix-queues-dashboard');
	RemoveDiv('postfix-opendkim-dashboard');
	RemoveDiv('postfix-backupemail-dashboard');
	RemoveDiv('postfix-transport-dashboard');
	RemoveDiv('postfix-networks-dashboard');
	RemoveDiv('messaging-dashboard');
	RemoveDiv('squid-top-websites-list');
	RemoveDiv('watchdogsmtpnotifs');
	RemoveDiv('squid-top-members');
	RemoveDiv('squid-top-stats');
	RemoveDiv('system-memory');
	RemoveDiv('nginx-reverse');
	RemoveDiv('nginx-web-config');
	RemoveDiv('nginx-lists-config');
	RemoveDiv('stats-appliance-clients');
	RemoveDiv('system-smtp-nofifs');
	RemoveDiv('squid-snmp');
	RemoveDiv('mgr_client_list');
	RemoveDiv('main-ufdb-personal-categories');
	RemoveDiv('artica-web-console');
	RemoveDiv('itchart-div');
	RemoveDiv('automount-div');
	RemoveDiv('ufdb-unlock-rules');
	RemoveDiv('artica-meta-main-div');
	RemoveDiv('cached-stats');
	RemoveDiv('influx-update');
	RemoveDiv('unifi-controller');
	RemoveDiv('my-proxy-aliases');
	RemoveDiv('ufdb-groups');
	RemoveDiv('squid-nas-storage');
	RemoveDiv('clamav-updates-section'); 
	RemoveDiv('vmware-tools-section');
	RemoveDiv('proxy-dashboard');
	RemoveDiv('webfiltering-db-status');
	RemoveDiv('ssl-rules');
	RemoveDiv('stats-categories'); 
	RemoveDiv('proxy-services'); 
	RemoveDiv('proxy-dashboard'); 
	RemoveDiv('squid-eye-php'); 
	RemoveDiv('squid-old-status'); 
	RemoveDiv('squid-performances'); 
	RemoveDiv('failover-manager'); 
	RemoveDiv('squid-parent-proxy'); 
	RemoveDiv('ufdbguard-rewrite-rules'); 
	RemoveDiv('squid-templates-errors'); 
	RemoveDiv('squid-perf-mon'); 
	RemoveDiv('squid-dns-settings');
	RemoveDiv('proxy-dashboard');
 	RemoveDiv('squid-member-follower');
 	RemoveDiv('squid-webfiltering-main');
 	RemoveDiv('main-squid-watchdog-table');
  	RemoveDiv('proxy-store-caches');
  	RemoveDiv('artica-main-updates');
  	RemoveDiv('system-main-status');
  	RemoveDiv('squid-member-follower');
  	RemoveDiv('squid-webfiltering-main');
  	RemoveDiv('artica-license-status');
  	RemoveDiv('categories-service');
  	RemoveDiv('windowsad-service');
  	RemoveDiv('main-dashboard-proxy');
  	RemoveDiv('main-statistics-options');
  	RemoveDiv('main-squid-ports');
  	RemoveDiv('main-squid-import-logs');
    RemoveDiv('firewall-routers');
  	RemoveDiv('firewall-nats');
  	RemoveDiv('main-squid-ssl_wl');
  	RemoveDiv('main-ntopng');
  	RemoveDiv('main-caches-center');
  	RemoveDiv('main-caches-level');
  RemoveDiv('main-caches-rock');
  RemoveDiv('main-filter-cicap');
  RemoveDiv('main-ufdb-frontend');
  RemoveDiv('main-ufdb-rules');
  RemoveDiv('main-fw-nic-rules');
  RemoveDiv('main-proxy-members');
  RemoveDiv('main-proxy-acls-rules');
  RemoveDiv('main-proxy-acls-groups');
  RemoveDiv('main-proxy-acls-browsers');
  RemoveDiv('main-proxy-acls-bandwidth');
  RemoveDiv('main-proxy-acls-options');
  RemoveDiv('main-proxy-restricted-members');
  RemoveDiv('main-proxy-blocked-members');
  RemoveDiv('main-proxy-proxy-pac');
  RemoveDiv('main-proxy-proxy-pac-events');
  RemoveDiv('main-proxy-access-rotate');
  RemoveDiv('main-proxy-update-table');
  RemoveDiv('main-artica-update-table');
  RemoveDiv('main-ufdb-personal-categories');
  RemoveDiv('main-squid-watchdog-table');
  RemoveDiv('main-squid-tasks-table');
  RemoveDiv('firewall-services-table');
  RemoveDiv('squid-hypercache-table');
  RemoveDiv('squid-hypercache-mirror');
  RemoveDiv('squid-params-timeouts');
  RemoveDiv('ufdb-web-page-errors');
  RemoveDiv('watchdog-squid-parameters');
  RemoveDiv('logs-cache-squid');
  RemoveDiv('squid-debug-tools');
  RemoveDiv('squid-ssl-encrypt');
  RemoveDiv('squid-webf-quotas-rules');	
  RemoveDiv('ufdbguard-behavior-settings');
  RemoveDiv('squid-global-blacklist');
  RemoveDiv('squid-icap-center');
  RemoveDiv('kav4proxy-dashboard');
  $('#GLOBAL_ACCESS_CENTER').remove();
  
}



</script>";

}

function Dashboardjs(){
	echo "MainSlider.goToSlide(0);";
}

function Dashboard_title(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$ahref_stats=null;

	$md5CacheF=md5("bxSliderTopRight{$_SESSION["uid"]}{$tpl->language}");
	$cachefile="/usr/share/artica-postfix/ressources/interface-cache/$md5CacheF";
	$js="LoadAjaxSilent('bx-slider-top-right','admin.dashboard.proxy.php?bx-slider-top-right=yes&jsafter={$_GET["jsafter"]}');";
	if(is_file($cachefile)){
		$content=@file_get_contents($cachefile);
		$js=null;
	}
	
	$html="
	<table style='width:100%'>
	<tr>
	<td style='color:#DCDCDC;text-align:right'><span id='bx-slider-top-right'>$content</span></td>
		
	</tr>
	</table>
	<script>
		document.getElementById('squid-member-follower').innerHTML='';
		$js
	</script>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function influxdb_tests(){
	$sock=new sockets();
	$users=new usersMenus();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$js="Loadjs('postgres.progress.php')";
	
	if($EnableInfluxDB==0){return null;}
	if($SquidPerformance>2){return;}
	
	$InfluxApiIP=$sock->GET_INFO("InfluxApiIP");
	$remote_port=5432;
	if($InfluxApiIP==null){$InfluxApiIP="127.0.0.1";}
	
	
	
	if($users->STATS_APPLIANCE){
		$InfluxApiIP="127.0.0.1";
		
	}
	
	if(!function_exists("pg_connect")){return;}
	
	
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$InfluxUseRemoteIpaddr=$sock->GET_INFO("InfluxUseRemoteIpaddr");
	$InfluxUseRemotePort=intval($sock->GET_INFO("InfluxUseRemotePort"));
	
	if($InfluxUseRemote==1){
		$InfluxApiIP=$InfluxUseRemoteIpaddr;
		$remote_port=$InfluxUseRemotePort;
		$js=null;
	}
	
	
	if($InfluxApiIP=="127.0.0.1"){$InfluxApiIP="/var/run/ArticaStats";}
	
	if($remote_port==8086){$remote_port=5432;}
	$connection = @pg_connect("host='$InfluxApiIP' port='$remote_port' dbname='proxydb' user='ArticaStats' connect_timeout='5'");
	
	if(!$connection){
		$errstr="Could not Connect to database service";
		$errstr = $errstr." see (".@pg_last_error($connection).")";
		@pg_close($connection);
		return proxy_status_warning("{bigdata_listen_port_issue} ($InfluxApiIP:$remote_port)",
				"{bigdata_listen_port_issue_explain}<hr>$InfluxApiIP:$remote_port $errstr", $js);
		
	}
	@pg_close($connection);
	
	
	
}

function bxSliderTopRight(){
	unset($_GET["_"]);
	$md5CacheF=md5("bxSliderTopRight{$_SESSION["uid"]}{$tpl->language}");
	$cachefile="/usr/share/artica-postfix/ressources/interface-cache/$md5CacheF";
	$sock=new sockets();
	$realsquidversion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
	$tpl=new templates();
	$users=new usersMenus();
	$f=array();
	$ProductName="Artica";
	$ahref_stats=null;
	$SHOW_WEBFILTERING=false;
	if($users->WEBSECURIZE){$ProductName="Web Securize";}
	if($users->LANWANSAT){$ProductName="LanWanSAT Proxy";}
	if($users->BAMSIGHT){$ProductName="BamSight";}
	
	$LoadProxyUpdate="LoadProxyUpdate();";
	$LOadUfdbUpdate="GoToWebfilteringDBstatus();";
	$underline="underline";
	
	if(!$users->AsSquidAdministrator){
		$LoadProxyUpdate="blur()";
		$LOadUfdbUpdate="blur()";
		$underline="none";
	}
	
	if($users->SQUID_INSTALLED){
		$SHOW_WEBFILTERING=true;
	}
	if($users->STATS_APPLIANCE){
		$SHOW_WEBFILTERING=true;
	}
	
	
	
	$UPTIME=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/UPTIME"));
	$GoToArticaLicense="GoToArticaLicense()";
	if(!$users->AsSystemAdministrator){$GoToArticaLicense="blur()";}
	
	if($users->AsSystemAdministrator){
		$ahref1="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:GotToArticaUpdate();\" style='text-decoration:underline'>";
	}
	$FRDB=array();
	
	if($SHOW_WEBFILTERING){
		
		
		$CurrentTLSEDbCloud=unserialize($sock->GET_INFO("CurrentTLSEDbCloud"));
		$CURRENT_TIME=0;
		while (list ($table,$MAIN) = each ($CurrentTLSEDbCloud) ){
			$xTIME=$MAIN["TIME"];
			if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
		}
		
		if($CURRENT_TIME>0){
			$FRDB[]=$tpl->_ENGINE_parse_body("{free_databases}: ").$tpl->time_to_date($CURRENT_TIME);
		}
		
		
		$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentArticaDbCloud"));
		$TIME=0;
		while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
			$xTIME=$MAIN["TIME"];
			if($xTIME>$TIME){$TIME=$xTIME;}
		}
		
		
		if($TIME>0){
			$FRDB[]="$ProductName: ".$tpl->time_to_date($TIME);
		}
		
	}
	
	if($UPTIME>0){
		$f[]="{uptime}:".distanceOfTimeInWords($UPTIME,time());
	}
	
	if($users->CORP_LICENSE){
		$Eval=null;
		$Ent="Entreprise Edition";
		$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
		if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}
		if($FINAL_TIME>0){
			$ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
			if($ExpiresSoon<31){
				$Eval="&nbsp;({trial_mode})";
			}
			if($ExpiresSoon<1){
				$Eval="&nbsp;({expired})";
			}
		}
		
		$f[]="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$GoToArticaLicense;\" style='text-decoration:$underline'>
		Entreprise Edition$Eval</a>";
	}else{
		$f[]="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$GoToArticaLicense;\" 
		style='text-decoration:$underline'>Community Edition</a>";
	}
	
	
	if(preg_match("#^([0-9\.]+)-#",$realsquidversion, $re)){
		$realsquidversion=$re[1];
	}
	
	$f[]="$ProductName {$ahref1}v.".@file_get_contents("VERSION")."</a>";
	

	
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
		if($SQUIDEnable==1){
			$f[]="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$LoadProxyUpdate\"
			style='text-decoration:$underline'>Proxy v$realsquidversion</a>";
		}
	}
	
	
	if(count($FRDB)>0){
		$f[]="{webfiltering_databases}: <a href=\"javascript:blur();\"
		OnClick=\"javascript:$LOadUfdbUpdate\"
		style='text-decoration:$underline'>".@implode("&nbsp;/&nbsp;", $FRDB)."</a>";
		
	}
	
	
	
	$html= $tpl->_ENGINE_parse_body(@implode("&nbsp;|&nbsp;", $f))."<script>".base64_decode($_GET["jsafter"])."</script>";
	@file_put_contents($cachefile, $html);
	echo $html;
}


function proxy_snmp(){
	$SNMP_WALK["PERC_CACHE"]=0;
	$SNMP_WALK["REQUESTS"]=0;
	$SNMP_WALK["CPU"]=0;
	if(!extension_loaded('snmp')){
		$SNMP_WALK["ERROR"]=true;		
		return $SNMP_WALK;
	}
	$sock=new sockets();
	$SquidSNMPPort=intval($sock->GET_INFO("SquidSNMPPort"));
	$SquidSNMPComunity=$sock->GET_INFO("SquidSNMPComunity");
	if($SquidSNMPPort==0){$SquidSNMPPort=3401;}
	if($SquidSNMPComunity==null){$SquidSNMPComunity="public";}
	
  if(!class_exists("SNMP")){
  	$SNMP_WALK["ERROR"]=false;
  	return $SNMP_WALK;
  }
  $session = new SNMP(SNMP::VERSION_1, "127.0.0.1:{$SquidSNMPPort}", $SquidSNMPComunity);
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $ifDescr = $session->walk(".1.3.6.1.4.1.3495.1", TRUE);
  $SNMP_WALK["PERC_CACHE"]=intval($ifDescr["3.2.2.1.10.5"]);
	$SNMP_WALK["REQUESTS"]=intval($ifDescr["3.2.1.1.0"]);
	$SNMP_WALK["CPU"]=intval($ifDescr["3.1.5.0"]);
	$SNMP_WALK["STORED_OBJECTS"]=intval($ifDescr["3.1.7.0"]);
	$SNMP_WALK["CLIENTS_NUMBER"]=intval($ifDescr["3.2.1.15.0"]);
	
	
	
	
	$SNMP_WALK["ERROR"]=false;
 	return $SNMP_WALK;
}

function proxy_dashboard_counters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	$baseHour="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/$HOUR";
	$baseDay="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY";
	$baseWeek="/home/squid/rttsize/$YEAR/$MONTH/$WEEK";
	$baseMonth="/home/squid/rttsize/$YEAR/$MONTH";
	
	$js_hour="Loadjs('squid.RTTSIZES.php?timeslot=hour');";
	$js_day="Loadjs('squid.RTTSIZES.php?timeslot=day');";
	$js_week="Loadjs('squid.RTTSIZES.php?timeslot=week');";
	$js_month="Loadjs('squid.RTTSIZES.php?timeslot=month');";
	$underline="underline";
	
	if(!$users->AllowViewStatistics){
		$js_hour="blur()";
		$js_day="blur();";
		$js_week="blur()";
		$js_month="blur();";
		$underline="none";
	}
	
	$html[]="<table style='width:1495px;margin-bottom:20px;margin-top:10px'><tr>";
	
	$TMPINT=intval(@file_get_contents("$baseHour/TOT"));
	if($TMPINT>0){
		$html[]="<td style='font-size:26px;text-align:center;width:20%' nowrap>{this_hour}: <a href=\"javascript:blur();\" 
				OnClick=\"javascript:$js_hour\"
				style='text-decoration:$underline'
				>".FormatBytes($TMPINT/1024)."</a></td>";
		$html[]="<td style='font-size:26px;text-align:center'>&nbsp;|&nbsp;</td>";
		
	}
	$TMPINT=intval(@file_get_contents("$baseDay/TOT"));
	if($TMPINT>0){
		$html[]="<td style='font-size:26px;text-align:center;width:20%' nowrap>{this_day}: <a href=\"javascript:blur();\" 
				OnClick=\"javascript:$js_day\"
				style='text-decoration:$underline'
				>".FormatBytes($TMPINT/1024)."</td>";
		$html[]="<td style='font-size:26px;text-align:center'>&nbsp;|&nbsp;</td>";
	
	}	
	$TMPINT=intval(@file_get_contents("$baseWeek/TOT"));
	if($TMPINT>0){
		$html[]="<td style='font-size:26px;text-align:center;width:20%' nowrap>{this_week}: <a href=\"javascript:blur();\" 
				OnClick=\"javascript:$js_week\"
				style='text-decoration:$underline'
				>".FormatBytes($TMPINT/1024)."</td>";
		$html[]="<td style='font-size:26px;text-align:center'>&nbsp;|&nbsp;</td>";
	
	}		
	$TMPINT=intval(@file_get_contents("$baseMonth/TOT"));
	if($TMPINT>0){
		$html[]="<td style='font-size:26px;text-align:center;width:20%' nowrap>{this_month}: <a href=\"javascript:blur();\" 
				OnClick=\"javascript:$js_month\"
				style='text-decoration:$underline'
				>".FormatBytes($TMPINT/1024)."</td>";
		$html[]="<td style='font-size:26px;text-align:center'>&nbsp;|&nbsp;</td>";
	
	}		
			
	$html[]="<td style='font-size:26px;text-align:center;width:150px'><center id='id-dashboard-button-refresh'></center></td>";		
	$html[]="</tr></table>";
	
	if(isset($_GET[__FUNCTION__])){
		$html[]="<script>LoadAjaxTiny('id-dashboard-button-refresh','$page?button-refresh=yes');</script>";
	}
	
	return $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}

function proxy_dashboard(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
	
	
	$jsload="Loadjs('$page?graph1-js=yes');";
	if(!$users->SQUID_INSTALLED){$SQUIDEnable=0;}
	if($users->POSTFIX_INSTALLED){$jsload="Loadjs('admin.dashboard.smtpgraphs.php');";}
	
	
	$jsload2="AnimateDivRound('graph1-dashboard');";
	$jsloadEnc=base64_encode($jsload);
	if(!is_file("{$GLOBALS["BASEDIR"]}/FLUX_HOUR")){$jsload=null;$jsload2=null;}
	$FLUX_HOUR=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/FLUX_HOUR"));
	if(count($FLUX_HOUR)<2){$jsload=null;}
	
	$content="&nbsp;";
	$md5CacheF=md5("bxSliderTopRight{$_SESSION["uid"]}{$tpl->language}");
	$cachefile="/usr/share/artica-postfix/ressources/interface-cache/$md5CacheF";
	$jsBxtop="LoadAjaxSilent('bxslider-top','$page?bx-slider-top-right=yes&jsafter=$jsloadEnc');";
	if(is_file($cachefile)){
		$content=@file_get_contents($cachefile);
		$jsBxtop=$jsload;
	}
	
	if($SQUIDEnable==1){
		if($SquidPerformance>1){
			$explain="{performance_level_nostats}";
			$error_stats=$tpl->_ENGINE_parse_body("
			<center style='margin:20px;font-size:18px'>
					<p class=text-info style=';font-size:18px'>". texttooltip("{artica_statistics_disabled}<br>$explain","{SQUID_LOCAL_STATS_DISABLED}","GotoSquidPerformances()")."
					</p>
					</center>");
		}
	
	
		if($EnableIntelCeleron==1){
			$explain="{CELERON_METHOD_EXPLAIN}";
			$error_stats=$tpl->_ENGINE_parse_body("
			<center style='margin:20px;font-size:18px'>
					<p class=text-info style=';font-size:18px'>". texttooltip("{artica_statistics_disabled}<br>$explain","{SQUID_LOCAL_STATS_DISABLED}","GotoOptimizeSystem()")."
					</p>
					</center>");
		}	
		
	
	}
	
	
	$html="
	<center>
			
	<table style='width:1545px'>
		<tr>
			<td style='width:410px;padding:15px;vertical-align:top'><div id='sequence-proxy'><center>".proxy_status()."</center></div></td>
			<td style='width:410px;padding:15px;vertical-align:top'><div id='sequence-server'><center>".server_status()."</center></div></td>
			<td style='width:410px;padding:15px;vertical-align:top'><div id='sequence-firewall'><center>".firewall_status()."</center></div></td>
		</tr>
		</table>
	</center>
	<center>
	<div id='bxslider-top' class='bx-slider-top'>$content</div>
	</center>
	<center id='proxy_dashboard_counters' style='height:57px'>
	". proxy_dashboard_counters()."
	</center>
	$error_stats
	<div id='graph1-dashboard' style='width:1500px;heigth:300px'></div>
	<div id='graph2-dashboard' style='width:1500px;heigth:300px'></div>
	<div id='graph3-dashboard' style='width:1500px;heigth:300px'></div>
	<script>
		LoadAjaxTiny('id-dashboard-button-refresh','$page?button-refresh=yes');
		$jsload2
		$jsBxtop
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function button_refresh(){
	$tpl=new templates();
	$page=CurrentPageName();
	echo $tpl->_ENGINE_parse_body(button("{refresh}","Loadjs('admin.dashboard.refresh.php')"))."
	<script>LoadAjaxSilent('sequence-firewall','$page?sequence-firewall=yes');</script>";		
			
	
}


function wifidog_status(){
	$tpl=new templates();
	$sock=new sockets();
	$errT=array();
	$js_hostpot="GotoHostpotv3()";
	$icon="wifidog-ok-128.png";
	$err=array();
	
	$ini=new Bs_IniHandler();
	$q=new mysql_squid_builder();
	$data=base64_decode($sock->getFrameWork("hotspot.php?services-status=yes"));
	$ARRAY=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
	$genrate=$q->time_to_date($ARRAY["TIME"],true) ;
	$uptime=$ARRAY["UPTIME"];
	$ArticaHotSpotEmergency=intval($sock->GET_INFO("ArticaHotSpotEmergency"));
	$ini->loadString($data);
	
	
	while (list ($key, $array) = each ($ini->_params) ){
	
	
	
		$service_name=$array["service_name"];
		$service_disabled=intval($array["service_disabled"]);
		if($service_disabled==0){continue;}
		$running=intval($array["running"]);
		$c++;
		if($running==0){
			$js=$js_hostpot;
			if($key=="SQUID"){$js="Loadjs('squid.start.progress.php');";}
				
			$icon="wifidog-critic-128.png";
			$err[]=proxy_status_warning("{{$service_name}} {stopped}", "{{$service_name}} {stopped}", "GoToServices()");
	
		}
	}
	
	if($ArticaHotSpotEmergency==1){
		$icon="wifidog-critic-128.png";
		$err[]=proxy_status_warning("{global_urgency_mode}", "{hotspot_in_emergency_mode_explain}", 
				"Loadjs('squid.webauth.maintenance.php')");
	}
	
	
	
	if(count($err)>0){
		$errT[]="<tr><td style='font-size:32px;color:#d32d2d;vertical-align:middle'>".count($err)." {issues}</td></tr>
		<tr><td colspan=2>&nbsp;</td></tr>
				";
	}
	
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	$hotSpotSession=$q->COUNT_ROWS("hotspot_sessions");
	
	
	

	
	
	$icon=imgtootltip($icon,"{dashboard_hotspot}",$js_hostpot);
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px'>
	$icon
	</td>
	<td style='width:99%'>
	<table style='width:100%'>
	<tr>
	<td style='font-size:30px'>HotSpot</td>
	</tr>
	<tr>
	<td style='font-size:30px;text-decoration:underline'
	OnClick=\"javascript:$js_hostpot\" $curs>$hotSpotSession {sessions}</td>
	
	".@implode("\n", $errT)."
	".@implode("\n", $err)."
	
	</tr>
	</table>
	</td>
	</tr>
	</table>
	";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);	
	
}

function VerifyRights_ou(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsOrgAdmin){return true;}
	if(!$usersmenus->AsSystemAdministrator){return true;}
	if($usersmenus->AsOrgPostfixAdministrator){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return true;}
}


function notadmin_status(){
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){$_SESSION["ACLS_PRIVILEGES"]=null;}
	$icon="member-128.png";
	$ct=new user($_SESSION["uid"]);
	$squid_acls=null;
	if($ct->AsActiveDirectoryMember){
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$DN=$ad->GetDNFromUserid($_SESSION["uid"]);
		$groupsArray=$ad->GroupsOfMember($DN);
		
		$MyGroups="
			<tr>
			<td style='font-size:30px;text-decoration:underline'
				OnClick=\"javascript:$js\" $curs>$ct->mail
			</td>
		</tr>
		<tr>
			<td style='font-size:18px;vertical-align:top'>{gidNumber} ".count($groupsArray)." {groups2}</td>
		</tr>
		<tr>
		<td style='font-size:18px;vertical-align:top'>{organization} <strong>$ct->ou</strong></td>
		</tr>
		";
		
	}else{
		$MEMBER_JS="javascript:blur();";
		$OU_js="javascript:blur();";
		$OU_decoration="none";
		$OU_curs=null;
		if($users->AllowChangeUserPassword){
			$MEMBER_JS=MEMBER_JS($_SESSION["uid"]);
		}
		
		if(VerifyRights_ou()){
			$OU_js="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$ct->ou');";
			$OU_decoration="underline";
			$OU_curs=$curs;
		}
		$MyGroups="
		<tr>
		<td style='font-size:16px;text-decoration:underline'
		OnClick=\"$MEMBER_JS\" $curs>{myaccount}: $ct->mail
		</td>
		</tr>
		<tr>
		<td style='font-size:18px;vertical-align:top'>{my_organization} <strong OnClick=\"$OU_js\" $curs style='text-decoration:$OU_decoration'>$ct->ou</strong></td>
		</tr>
		";

			
		
		
		
	}
		
	if($users->SQUID_INSTALLED){	
		if(!isset($_SESSION["ACLS_PRIVILEGES"])){
			include_once(dirname(__FILE__)."/ressources/class.squid.acls.privileges.inc");
			$f=new squid_acls_privileges();$_SESSION["ACLS_PRIVILEGES"]=$f->build();
		}
		$squid_acls=$_SESSION["ACLS_PRIVILEGES"];
		if($squid_acls<>null){$squid_acls="<tr><td>$squid_acls</td></tr>";}
	}
	

	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px'>
	<img src='img/$icon'>
	</td>
	<td style='width:99%'>
	<table style='width:100%'>
	<tr>
	<td style='font-size:30px;vertical-align:top'>$ct->DisplayName</td>
	</tr>
	$MyGroups
	$squid_acls

	</table>
	</td>
	</tr>
	</table>
	";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);	
	
}



function firewall_status(){
	$icon="firewall-128-grey.png";
	$sock=new sockets();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return notadmin_status();}
	$q=new mysql();
	
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$EnableSuricata=intval($sock->GET_INFO("EnableSuricata"));
	$SuricataInstalled=intval($sock->GET_INFO("SuricataInstalled"));
	$STATS_APPLIANCE=false;
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$STATS_APPLIANCE=true;}
	
	if($SuricataInstalled==0){$EnableSuricata=0;}
	
	if($EnableArticaHotSpot==1){return wifidog_status();}
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	
	$js="GotoFirewall()";
	
	$OK=true;
	$INSTALLED=true;
	if(intval($sock->getFrameWork("firehol.php?is-installed=yes"))==0){
		$js="Loadjs('system.firewall.php?FireHolInstall-js=yes');";
		$icon="firewall-128-grey-install.png";
		$text="{not_installed}";
		$OK=false;
		$INSTALLED=false;
	}else{
		$FireHolConfigured=intval($sock->GET_INFO("FireHolConfigured"));
		if($FireHolConfigured==0){
			$js="Loadjs('system.firewall.php?FireHolInstall-wizard-js=yes');";
			$icon="firewall-128-grey.png";
			$text="{not_configured}";
			$OK=false;
		}
		
		
	}
	if($OK){
		$FireHolEnable=intval($sock->GET_INFO("FireHolEnable"));
		if($FireHolEnable==0){
			$js="Loadjs('firehol.wizard.enable.progress.php');";
			$icon="firewall-128-disabled.png";
			$text="{firewall_is_disabled}";
			$OK=false;
			
		}
	}
	
	
	if(!$users->AsSystemAdministrator){$js="blur()";}
	$rules=null;
	if($OK){
		$icon="firewall-128.png";
		$q=new mysql();
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM iptables_main","artica_backup"));
		$items=$ligne2["tcount"];
		if($users->AsSystemAdministrator){
			$rules="
			<tr>
			<td style='font-size:30px;text-decoration:underline' 		
			OnClick=\"javascript:GotoFireholeRules()\" $curs><strong>$items</strong> {rules}</td>		
			</tr>";
		}
	}
	
	
	
	if($INSTALLED){
		$q=new mysql();
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(Interface) as tcount FROM `nics` WHERE `FireQOS`=1","artica_backup"));
		if($ligne2["tcount"]>0){
		$QOS_ROW="
		<tr>
		<td style='font-size:16px;text-decoration:underline;padding-top:20px'
		OnClick=\"javascript:GotoQOS()\" $curs>
			<table style='width:100%'>
			<tr>
				<td valign='middle' style='width:25px'><img src='img/24-green.png'></td>
				<td style='font-size:16px;text-decoration:underline'><strong>{Q.O.S}</strong> {enabled}</td>
			</tr>
			</table>
		</td>
		</tr>";
		}else{
			$QOS_ROW="
			<tr>
			<td style='font-size:16px;text-decoration:none;padding-top:20px'
			>
			<table style='width:100%'>
			<tr>
			<td valign='middle' style='width:25px;color:#898989'><img src='img/ok24-grey.png'></td>
			<td style='font-size:16px;text-decoration:none;color:#898989'><strong>{Q.O.S}</strong> {disabled}</td>
			</tr>
			</table>
			</td>
			</tr>";
		}
	}else{
		$QOS_ROW="
		<tr>
		<td style='font-size:16px;text-decoration:none;padding-top:20px'
		>
		<table style='width:100%'>
		<tr>
		<td valign='middle' style='width:25px;color:#898989'><img src='img/ok24-none.png'></td>
		<td style='font-size:16px;text-decoration:none;color:#898989'><strong>{Q.O.S}</strong> {not_installed}</td>
		</tr>
		</table>
		</td>
		</tr>";
	}
	
	
	$IDS_ROW=null;
	
	$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
	
	if($STATS_APPLIANCE){$EnableSuricata=1;}
	if($EnableSuricata==1){
		if(is_file("{$GLOBALS["BASEDIR"]}/suricata.dashboard")){
			$IDS_SEVERITIES=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/suricata.dashboard"));
			
			if(isset($IDS_SEVERITIES["SEVERITIES"][1])){
				IF($IDS_SEVERITIES["SEVERITIES"][1]>0){
				$IDS_ROW="
				<tr>
				<td style='font-size:16px;text-decoration:underline'
						OnClick=\"javascript:GotoSuricataEvents()\" $curs>
						<table style='width:100%'>
						<tr><td valign='middle' style='width:25px'><img src='img/warn-red-24.png'></td>
						<td style='font-size:16px;text-decoration:underline'><strong>{$IDS_SEVERITIES["SEVERITIES"][1]}</strong> IDS {detected_rules}</td>
						</tr>
						</table>
				</td>
				</tr>";
				
			}
			
			}
			
		}
		
	}
	
	
	
	
	$icon=imgtootltip($icon,"{administrate_your_firewall}",$js);
	$text_suricata="<span style='font-size:14px'>( {not_installed} )</span>";
	$js_suricata="GotoSuricataUpdates()";
	$icon_suricata="ok24-none.png";
	$color_suricata="#898989";
	
	if($SuricataInstalled==1){
		$icon_suricata="ok24.png";
		$js_suricata="GotoSuricata()";
		$SuricataVersion=$sock->GET_INFO("SuricataVersion");
		
		if($EnableSuricata==0){
			$icon_suricata="ok24-grey.png";
			$text_suricata="<span style='font-size:14px'>( {disabled} )</span>";
		}else{
			$text_suricata=null;
			$color_suricata="black";
		}
		if($SuricataVersion<>null){$SuricataVersion="{version} $SuricataVersion";}
		
		

		
		
	}
	
		$SURICATA="
		<tr>
		<td style='font-size:18px;text-decoration:underline;padding-top:20px;color:$color_suricata'
				OnClick=\"javascript:$js_suricata\" $curs><strong>{IDS}</strong>
					<table style='width:100%'>
						<tr>
						<td valign='middle' style='width:25px'><img src='img/$icon_suricata'></td>
						<td style='font-size:16px;text-decoration:underline'><strong style='color:$color_suricata'>$SuricataVersion $text_suricata</td>
						</tr>
					</table>
				</td>
				</tr>$IDS_ROW";
		
		
	
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:128px'>
			$icon
		</td>
	<td style='width:99%'>
		<table style='width:100%'>
		<tr>
			<td style='font-size:30px'>{firewall}</td>
		</tr>	
		<tr>
			<td style='font-size:30px;text-decoration:underline' 
			OnClick=\"javascript:$js\" $curs>$text</td>
		</tr>
		$rules
		$QOS_ROW
		$SURICATA
		</table>
	</td>
	</tr>
	</table>
	";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);	
	
}

function proxy_graph_js(){
	
	$sock=new sockets();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$HaProxyInstalled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HaProxyInstalled"));
	$EnableHaProxy=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHaProxy"));
	if($HaProxyInstalled==0){$EnableHaProxy=0;}
	if($SQUIDEnable==0){
		if($EnableNginx==1){return nginx_graph_js(); }
		if($EnableHaProxy==1){return haproxy_graph_js();exit;}
	}
	
	
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/FLUX_HOUR"));
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		header("content-type: application/x-javascript");
		die();
	}
	
	if(count($MAIN["xdata"])<2){
		header("content-type: application/x-javascript");
		die();
	}
	
	$tpl=new templates();
	
	$title="{downloaded_flow} (MB) ".DATE_START();
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
	if(is_file("{$GLOBALS["BASEDIR"]}/FLUX_RQS")){
		$page=CurrentPageName();
		echo "\nLoadjs('$page?graph2-js=yes');\n";
		
	}
	
	
}


function haproxy_graph_js(){
	
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/HAPROXY_DAY"));
	if(count($MAIN["xdata"])<2){
		header("content-type: application/x-javascript");
		die();
	}
	$tpl=new templates();
	
	$title="{downloaded_flow} (MB) {today}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	if(is_file("{$GLOBALS["BASEDIR"]}/HAPROXY_RQS_DAY")){
		$page=CurrentPageName();
		echo "\nLoadjs('$page?graph2-haproxy-js=yes');\n";
	
	}
	
}

function nginx_graph_js(){




	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/NGINX_FLUX_HOUR"));
	if(count($MAIN["xdata"])<2){
		header("content-type: application/x-javascript");
		die();
	}

	$tpl=new templates();

	$title="{downloaded_flow} (MB) ".NGINX_DATE_START();
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

/*	if(is_file("{$GLOBALS["BASEDIR"]}/FLUX_RQS")){
		$page=CurrentPageName();
		echo "\nLoadjs('$page?graph2-js=yes');\n";

	}
*/

}

function NGINX_DATE_START(){

	if(isset($_SESSION["NGINX_DATE_START"])){return $_SESSION["NGINX_DATE_START"];}


	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="dashboard_apache_sizes";
	


	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));


	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	$_SESSION["NGINX_DATE_START"]= $tpl->javascript_parse_text("{date_start} $time1, {last_date} $time2");
	return $_SESSION["NGINX_DATE_START"];
}

function DATE_START(){
	
	if(isset($_SESSION["SQUID_GRAPH_DATE_START"])){return $_SESSION["SQUID_GRAPH_DATE_START"];}
	
	
	$tpl=new templates();
	$q=new mysql_squid_builder();

	$table="dashboard_user_day";
	if($q->COUNT_ROWS($table)==0){
		$table="dashboard_blocked_day";
	}


	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));

	
	
	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if($ligne["xmin"]==null){return;}
	
	
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	$_SESSION["SQUID_GRAPH_DATE_START"]= $tpl->javascript_parse_text("{date_start} $time1, {last_date} $time2");
	return $_SESSION["SQUID_GRAPH_DATE_START"];
}


function proxy_graph2_js(){
	
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		header("content-type: application/x-javascript");
		die();
	}
	
	
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/FLUX_RQS"));
	$tpl=new templates();
	
	$title="{requests} ".DATE_START();
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();	
	if(is_file("{$GLOBALS["BASEDIR"]}/MEMBERS_GRAPH")){
		$page=CurrentPageName();
		echo "\nLoadjs('$page?graph3-js=yes');\n";
	
	}	
}

function haproxy_graph2_js(){
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/HAPROXY_RQS_DAY"));
	$tpl=new templates();
	
	$title="{requests}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="RQS";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
	
}

function proxy_graph3_js(){
	
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		header("content-type: application/x-javascript");
		die();
	}
	
	
	
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_GRAPH"));
	$tpl=new templates();

	$title="{members} ".DATE_START();
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph3-dashboard";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" Users";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{members}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
}


function server_status(){
	
	$PHP5_CURRENT_MEMORY=null;
	if(!$GLOBALS["VERBOSE"]){
	if(!isset($_GET["without-cache"])){
		unset($_GET["_"]);
		$md5CacheF=md5("server_status{$_SESSION["uid"]}{$tpl->language}".serialize($_GET));
		$cachefile="/usr/share/artica-postfix/ressources/interface-cache/$md5CacheF";
		if(file_time_sec_Web($cachefile)<10){return @file_get_contents($cachefile);}
	}
	}
	$BOGOMIPS=0;
	
	if(is_file("/usr/share/artica-postfix/ressources/interface-cache/processor_type")){
		$processor_type=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/processor_type"));
		$BOGOMIPS=intval($processor_type["BOGOMIPS"]);
	}
	
	$icon="server-128-ok.png";
	$os=new os_system();
	$sock=new sockets();
	$users=new usersMenus();
	$Warn=false;
	$HyperWarn=true;
	$tpl=new templates();
	$EnableMsftncsi=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMsftncsi"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
	$RootPasswordChanged=intval($sock->GET_INFO("RootPasswordChanged"));
	$influxdb_version=@file_get_contents("{$GLOBALS["BASEDIR"]}/influxdb_version");
	$influxdbversionBin=$influxdb_version;
	$RegisterCloudBadEmail=intval($sock->GET_INFO("RegisterCloudBadEmail"));
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$InfluxUseRemoteInfo=intval($sock->GET_INFO("InfluxUseRemoteInfo"));
	$SessionPathInMemory=intval($sock->GET_INFO("SessionPathInMemory"));
	$EnableBandwithCalculation=intval($sock->GET_INFO("EnableBandwithCalculation"));
	$DashBoardDNSPerfsStats=$sock->GET_INFO("DashBoardDNSPerfsStats");
	$BigDatav3Read=intval($sock->GET_INFO("BigDatav3Read"));
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	$ArticaTechNetInfluxRepo=unserialize(base64_decode($sock->GET_INFO("ArticaTechNetInfluxRepo")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	
	
	if($EnableArticaMetaClient==1){
		$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
		if($ArticaMetaHost==null){
			$err[]=proxy_status_warning("{incorrect_meta_server_address}", "{click_to_edit}",
					"GotoArticaMeta()");
			
		}
		
	}
	
	if($SessionPathInMemory>0){
		exec("/bin/df -h /var/lib/php5 2>&1",$results);

		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#tmpfs\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)%\s+#", $ligne,$re)){continue;}
			$PHP5_CURRENT_MEMORY=$re[4];
			$PHP5_CURRENT_MEMORY_SIZE=$re[1];
		}
	}
	
	

	
	if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
		$sock=new sockets();
		$CPU_NUMBER=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
	}else{
		$CPU_NUMBER=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
	}	
	
	if($CPU_NUMBER<2){
		if($EnableIntelCeleron==0){
			$warn[]=status_important_event("{performance_issue_cpu_number_text}", "{click_to_fix}", "GotoOptimizeSystem()");
		}
	}
	
	if($EnableMsftncsi==1){
		$msftncsiStatus=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/msftncsiStatus"));
		if($msftncsiStatus==3){
			$warn[]=status_important_event("{network_awareness} !!", "", "GotoWatchdog()");
		}
	}
	
	
	$results=array();
	exec("/usr/bin/pgrep -l -f \"philesight --db\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne)){
			$warn[]=status_important_event("{APP_PHILESIGHT_INDEXING}",null,"blur()");
			break;
		}
		
	}
	
	
	
	
	
	
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	$os->html_Memory_usage();
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
	
	$MAIN=$os->meta_array;
	$PHP5_CURRENT_MEMORY_COLOR="black";
	$LOAD_COLOR="black";
	$ORG_LOAD=$MAIN["LOAD"]["ORG_LOAD"];
	$CPU_NUMBER=$MAIN["LOAD"]["CPU_NUMBER"];
	$MAXOVER=$MAIN["LOAD"]["MAXOVER"];
	$MEM_USED_POURC=$MAIN["MEM"]["MEM_USED_POURC"];
	$MEM_USED_COLOR="black";
	$SWAP_POURC=$MAIN["SWAP_POURC"];
	$DISKY=array();
	$MAXOVER2=$CPU_NUMBER-1;
	if($MAXOVER2==0){$MAXOVER2=1.5;}
	$EnableIntelCeleronText=null;

	$CURVER=@file_get_contents("VERSION");
	$CURVER_KEY=str_replace(".", "", $CURVER);
	if($EnableIntelCeleron==1){
		$EnableIntelCeleronText="{intel_celeron_support}";
		$MAXOVER2=2;}

	
	$INFO_WORKING_TASK=INFO_WORKING_TASK();
	if(is_array($INFO_WORKING_TASK)){
		$INFOS=$INFO_WORKING_TASK;
	}
	
	
	if(!$users->STATS_APPLIANCE){
		if($InfluxUseRemote==0){
			if($EnableIntelCeleron==0){
				if($InfluxUseRemoteInfo==0){
					if($SquidPerformance<2){
						$INFOS[]=status_info_event("{suggest_remote_statistics_appliance}","{suggest_remote_statistics_appliance}<br>{click_here}", "Loadjs('influx.remote.suggest.php')");
					}
				}
			}
		}
	}

	
	
	if($ORG_LOAD>$MAXOVER2){
		$LOAD_COLOR="#F59C44";
		$Warn=true;
		$icon="server-128-warn.png";
		$microerror_text="{overloaded} $ORG_LOAD &raquo; $MAXOVER2";
	}
	$SWAPERR=false;
	if($ORG_LOAD>$MAXOVER){
		$HyperWarn=true;
		$LOAD_COLOR="#D32D2D";
		$icon="server-128-critic.png";
		$microerror_text="{overloaded} $ORG_LOAD &raquo; $MAXOVER";
	}	
	
	if($MEM_USED_POURC>79){
		$microerror_text="{memory_warning}";
		if(!$HyperWarn){$icon="server-128-warn.png";}
		$MEM_USED_COLOR="#F59C44";
		$jsOn="GotoSystemMemory()";
		if(!$users->AsArticaAdministrator){$jsOn="blur()";}
		
		$err[]="<tr><td style='font-size:18px;color:#d32d2d;vertical-align:middle'>
		<img src='img/warn-red-32.png' style='float:left;margin-right:10px'>
		<span style='text-decoration:underline' $curs OnClick=\"javascript:$jsOn\">{overloaded_memory}</span>
		</td></tr>";
		$SWAPERR=true;
	}
	
	if($MEM_USED_POURC>90){
		$microerror_text="{memory_alert}";
		$icon="server-128-critic.png";
		$MEM_USED_COLOR="#D32D2D";
		if(!$SWAPERR){
			$jsOn="GotoSystemMemory()";
			if(!$users->AsArticaAdministrator){$jsOn="blur()";}
			
			$icon="disks-128-warn.png";
			$err[]="<tr><td style='font-size:18px;color:#d32d2d;vertical-align:middle'>
			<img src='img/warn-red-32.png' style='float:left;margin-right:10px'>
			<span style='text-decoration:underline' $curs OnClick=\"javascript:$jsOn\">{overloaded_memory}</span>
			</td></tr>";
			$SWAPERR=true;
		}
		
	}	
	

	
	
	if($SWAP_POURC>20){
		
		if(!$HyperWarn){
			$microerror_text="{swap_warning}";
			$icon="server-128-warn.png";}
		$SWAP_COLOR="#F59C44";
	}
	
	if($PHP5_CURRENT_MEMORY>80){
		if(!$HyperWarn){
			$microerror_text="{swap_warning}";
			$icon="server-128-warn.png";}
		$PHP5_CURRENT_MEMORY_COLOR="#F59C44";
	}

	if($SWAP_POURC>30){
		if(!$HyperWarn){
			$microerror_text="{swap_warning}";
			$icon="server-128-warn.png";}
		$SWAP_COLOR="#D32D2D";
	}
	if($SWAP_POURC>50){
		$microerror_text="{swap_alert}";
		$icon="server-128-critic.png";
		$SWAP_COLOR="#D32D2D";
		$jsOn="GotoSystemMemory()";
		if(!$users->AsArticaAdministrator){$jsOn="blur()";}
		$err[]=proxy_status_warning("{high_swap_value}", "{high_swap_value}", $jsOn);

	}
	
	if($PHP5_CURRENT_MEMORY>95){
		if(!$HyperWarn){
			$microerror_text="{session_memory_warning}";
			$icon="server-128-warn.png";}
		$PHP5_CURRENT_MEMORY_COLOR="#D32D2D";
	}
	
	if($EnableIntelCeleron==0){
		$sock->getFrameWork("postgres.php?is-installed=yes");
		$PostgresInstalled=intval($sock->GET_INFO("PostgresInstalled"));
		
		if($PostgresInstalled==0){
			$jsOn="Loadjs('influxdb.install.progress.php');";
			if(!$users->AsArticaAdministrator){$jsOn="blur()";}
			$err[]=proxy_status_warning("{influx_not_installed}", "{click_to_install}", $jsOn);
			
		}else{
			$influxdb_tests=influxdb_tests();
			if($influxdb_tests<>null){$err[]=$influxdb_tests;}
		}
	}
	
	if(isset($LicenseInfos["FINAL_TIME"])){
		if(is_numeric($LicenseInfos["FINAL_TIME"])){
			$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);
			$ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
			$jsOn="GoToArticaLicense()";
			$distanceOfTimeInWords=distanceOfTimeInWords(time(),$FINAL_TIME);
			if(!$users->AsSystemAdministrator){$jsOn=null;}
			$corporate_licence_will_expire_explain=$tpl->_ENGINE_parse_body("{corporate_licence_will_expire_explain}");
			$corporate_licence_will_expire_explain=str_replace("%d", $ExpiresSoon, $corporate_licence_will_expire_explain);
			
			if(time()>$FINAL_TIME){
				$err[]=proxy_status_warning("{license2}:&nbsp;{license_expired} !!", "{license_expired_explain}", $jsOn);
				
			}
			
			if($FINAL_TIME>time()){
				if($ExpiresSoon<10){
					$err[]=proxy_status_warning("{corporate_licence_will_expire} ({$ExpiresSoon} {days})","$corporate_licence_will_expire_explain", $jsOn);
				}
				
				if($ExpiresSoon<31){
					$ExpiresSoon=$ExpiresSoon+1;
					$warn[]=status_important_event("{license2}:&nbsp;{trial_period} - {$ExpiresSoon} {days} -","{expiredate}:".$tpl->time_to_date($FINAL_TIME) ." ($distanceOfTimeInWords)", $jsOn);
				}
				
			}
			
			
		}
	}
	
	if(!$users->CGROUPS_INSTALLED){
		$jsOn="Loadjs('cgroups.install.progress.php');";
		if(!$users->AsArticaAdministrator){$jsOn="blur()";}
		$warn[]=status_important_event("{cgroups_not_installed}", "{click_to_install}", $jsOn);
		
	}
	
	if($RegisterCloudBadEmail==1){
		$warn[]=status_important_event("{incorrect_email_address}", "{incorrect_email_address_cloud}",
				 "GoToArticaLicense()");
		
	}
	
	if(!extension_loaded('pgsql')){
		$warn[]=status_important_event("{missing_postgres_library}", "{missing_postgres_library_explain}",
		"Loadjs('admin.pgsql.php')");
		
	}else{
		if(!function_exists("pg_connect")){
			$warn[]=status_important_event("{missing_postgres_library}", "{missing_postgres_library_explain}",
			"Loadjs('admin.pgsql.php')");
		}
	}
	
	
	if($BOGOMIPS>0){
		if($users->CGROUPS_INSTALLED){
			if($BOGOMIPS<3500){
				if($EnableIntelCeleron==0){
					$jsOn="Loadjs('system.optimize.celeron.wizard.php');";
					$err[]=status_important_event("{low_performance}", 
							"{low_performance_link_explain}", $jsOn);
				}
			}
		}
	}
	
	$q=new mysql();
	$perfs_queue=$q->COUNT_ROWS("perfs_queue", "artica_events");
	if($perfs_queue>0){
		$dashboard_perfs_queue=$tpl->_ENGINE_parse_body("{dashboard_perfs_queue}");
		$dashboard_perfs_queue=str_replace("%s", $perfs_queue, $dashboard_perfs_queue);
		$warn[]=status_important_event($dashboard_perfs_queue,
				"$dashboard_perfs_queue", "GotoDashBoardPerfQueue()");
	
	}
	
	$HostType=null;
	if($users->VMWARE_HOST){
		$HostType="VMWare Edition";
		$HostTypejs="GotoVMWareClient();";
		if(trim($sock->getFrameWork("services.php?vmtools_installed=yes"))<>"TRUE"){
			$jsOn="Loadjs('vmware.install.progress.php');";
			if(!$users->AsArticaAdministrator){$jsOn="blur()";}
			$warn[]=status_important_event("{APP_VMWARE_TOOLS_NOT_INSTALLED}", 
					"{click_to_install}", $jsOn);
			
			
		}
	}
	
if(is_array($MAIN["DISKS"])){
	while (list ($disk, $array) = each ($MAIN["DISKS"]) ){	
		$POURC=$array["POURC"];
		$LABEL=$array["LABEL"];
		if($LABEL==null){$LABEL=$disk;}
		if($POURC<80){continue;}
		$DISK_COLOR="#F59C44";
		$icon="server-128-warn.png";
		$microerror_text="{partition_warning}";
		$diskZ="	
			<tr>
			<td style='font-size:20px;color:$DISK_COLOR'>{$LABEL} {$POURC}% {used}</td>
			</tr>";
		
		if($POURC>95){
			$DISK_COLOR="#D32D2D";
			$icon="server-128-critic.png";
			$diskZ="	<tr>
			<td style='font-size:20px;color:$DISK_COLOR'>{$LABEL} {$POURC}% {used}</td>
			</tr>";
			
		}
		
		$DISKY[]=$diskZ;
		
	}
}
	

	
	if($RootPasswordChanged==0){
		$jsOn="Loadjs('system.root.pwd.php')";
		if(!$users->AsSystemAdministrator){$jsOn=null;}
		$err[]=status_important_event("{root_password_not_changed}",
		"{root_password_not_changed_text}",$jsOn);
	}
	
	
	
	
	
	if(count($err)>0){
		$errT[]="<tr><td style='font-size:32px;color:#d32d2d;vertical-align:middle'>".
		count($err)." {issues}</td></tr>
		<tr><td colspan=2>&nbsp;</td></tr>
		";
	}	
	
	$GotoSpeedTests="GotoSpeedTests()";
	$GotoBandwidthdStats="GotoBandwidthdStats()";
	$GoToSystem="GoToSystem()";
	$GotToArticaUpdate="GotToArticaUpdate()";
	$GotoOptimizeSystem="GotoOptimizeSystem()";
	$GotoDNSPerfs="GotoDNSPerfs()";
	$GotoSystemMemory="GotoSystemMemory()";
	$GotoStatsSystem="GotoStatsSystem()";
	$GotoStatsSystem_underline="underline";
	$HostTypejs_underline="underline";
	if(!$users->AsSystemAdministrator){
		$microerror_text=null;
		$HostTypejs_underline="none";
		$GotoStatsSystem_underline="none";
		$HostTypejs="blur();";
		$GoToSystem="blur()";
		$GotoStatsSystem="blur()";
		$GotoSystemMemory="blur()";
		$GotoDNSPerfs="blur()";
		$GotoOptimizeSystem="blur()";
		$GotToArticaUpdate="blur()";
		$GotoBandwidthdStats="blur()";
		$GotoSpeedTests="blur()";
	}
	
	
	if($EnableBandwithCalculation==1){
			$q=new mysql();
			$sql="SELECT * FROM speedtests ORDER BY zDate DESC LIMIT 0,1";
			$speedtests=null;
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$download=$ligne["download"];
			$upload=$ligne["upload"];
			$ISP=$ligne["ISP"];
			
			//Kbi/s upload 51.2 Kbi/s
			if($download>0){
				$speedtests="
				<tr>
				<td>&nbsp;</td>
				</tr>
				<tr>
				<td style='font-size:16px;'>
				<span style='color:black'><a href=\"javascript:blur();\"
				OnClick=\"javascript:$GotoSpeedTests;\" style='text-decoration:$GotoStatsSystem_underline'>
				{bandwidth}: <i style='font-size:16px'>{$download}Kbit/sec {download2}</i></td>
				</tr><tr>
				<td style='font-size:16px;'>
				<span style='color:black'><a href=\"javascript:blur();\"
				OnClick=\"javascript:$GotoSpeedTests;\" style='text-decoration:$GotoStatsSystem_underline'>
				{bandwidth}: <i style='font-size:16px'>{$upload}Kbit/sec {upload}</a> ($ISP)</span></i></td>
				</tr>";
				
			}
	}
	

	
	if($sock->Bandwidthd_enabled()==1){
		$q=new mysql_squid_builder();
		$bandwidthd_today=$q->COUNT_ROWS("bandwidthd_today");
		if($bandwidthd_today>0){
			$speedtests=$speedtests."
			<tr>
			<td style='font-size:18px;'>
			<table style='width:100%'>
			<tr>
			<td valign='middle' style='width:25px'><img src='img/graph-24.png'></td>
			<td valign='middle'>
			<span style='color:black'><a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoBandwidthdStats;\" style='text-decoration:$GotoStatsSystem_underline'>
			<span style='font-size:18px;font-weight:bold'>$bandwidthd_today</span> <span style='font-size:18px;'>{nodes}</span></td>
			</tr>
			</table>
			</td>
			</tr>";
		}
	}
	

	
	
	
if($users->AsSystemAdministrator){
	if($EnableArticaMetaClient==0){
		if($ArticaAutoUpateOfficial==1){
			$ArticaUpdateRepos=unserialize($sock->GET_INFO("ArticaUpdateRepos"));
			$key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
			$key_offical=update_find_latest($ArticaUpdateRepos);
			
			$OFFICIALS=$ArticaUpdateRepos["OFF"];
			$Lastest=$OFFICIALS[$key_offical]["VERSION"];
			$MAIN_URI=$OFFICIALS[$key_offical]["URL"];
			$MAIN_MD5=$OFFICIALS[$key_offical]["MD5"];
			$MAIN_FILENAME=$OFFICIALS[$key_offical]["FILENAME"];	
			
			
			
			
			if($key_offical>$CURVER_KEY){
				
				$err[]="<tr><td style='font-size:18px;color:#46a346;vertical-align:middle' nowrap>
				<img src='img/32-install-soft.png' style='float:left;margin-right:10px'>
				". texttooltip("{new_version}: $Lastest","{NEW_RELEASE_TEXT}","$GotToArticaUpdate")."
				</td></tr>";
				}
			}
	}
}	

			
	if($EnableIntelCeleron==1){
		$EnableIntelCeleron_explain="<tr><td style='font-size:16px;color:#000000;vertical-align:middle' nowrap>
				<i>". texttooltip("{CELERON_METHOD}","{CELERON_METHOD_EXPLAIN}",$GotoOptimizeSystem)."</i>
				</td></tr>";
	}
	
	$EnableDNSPerfs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs"));
	

	
	if($EnableDNSPerfs==1){
		if($EnableIntelCeleron==0){
			$DNS_COLOR="black;";
			if($DashBoardDNSPerfsStats<>null){
				$DashBoardDNSPerfsStats=round($DashBoardDNSPerfsStats,2);
				if($DashBoardDNSPerfsStats<30){
					$DNS_COLOR="#D32D2D";
				}
				
				$DashBoardDNSPerfsStats_text="
					<tr>
					<td style='font-size:20px;color:$DNS_COLOR'>". texttooltip("{dns_performance}","{dnsperf_explain}","$GotoDNSPerfs").": {$DashBoardDNSPerfsStats}%</td>
					</tr>";
						
				}
				
				
			}
	}
	

	
	
	
	if($microerror_text<>null){
		$microerror_text="<center style='margin-top:10px;font-weight:bold;font-size:14px'>$microerror_text</center>";
	}
	if($HostType<>null){$HostType="<center style='font-size:14px;margin-top:10px;'>
	<a href=\"javascript:blur();\" OnClick=\"javascript:$HostTypejs\" style='text-decoration:$HostTypejs_underline'>
		$HostType</a></center>";}
		
	$html[]="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px'  $curs OnClick=\"javascript:$GoToSystem\">
		<img src='img/$icon'>$HostType$microerror_text
	</td>
	<td>
	<table style='width:100%'>
	<tr>
		<td style='font-size:30px'  $curs OnClick=\"javascript:$GoToSystem\">{system}</td>
	</tr>	
	<tr>
	<td style='font-size:30px;color:$LOAD_COLOR;text-decoration:$GotoStatsSystem_underline' $curs OnClick=\"javascript:$GotoStatsSystem;\">{$ORG_LOAD} {load2}</td>
	</tr>
	$EnableIntelCeleron_explain
	<tr>
		<td style='font-size:20px;'>
		<span style='color:$MEM_USED_COLOR'><a href=\"javascript:blur();\"
		OnClick=\"javascript:$GotoSystemMemory;\" style='text-decoration:$GotoStatsSystem_underline'>		
		{$MEM_USED_POURC}% {memory}</a></span>&nbsp;|&nbsp;
		<span style='color:$SWAP_COLOR'>{$SWAP_POURC}% SWAP</span></td>
	</tr>
	";
		
		
		
	if($users->AsSystemAdministrator){	
		if($PHP5_CURRENT_MEMORY<>null){
			$html[]="<tr>
			<td style='font-size:18px;'>
			<span style='color:$PHP5_CURRENT_MEMORY_COLOR'><a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoSystemMemory;\" style='text-decoration:$GotoStatsSystem_underline'>
			{session_memory}: {$PHP5_CURRENT_MEMORY}%/$PHP5_CURRENT_MEMORY_SIZE</a></span></td>
			</tr>";	
			
		}		
	}
	
	
	if($users->AsSystemAdministrator){
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/monit.status.all")){
			
			$dataMonit=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/monit.status.all"));
			$dataMonitCount=count($dataMonit);
			$html[]="<tr>
			<td style='font-size:18px;'>
			<span style='color:black'><a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('monit.php');\" style='text-decoration:underline'>
			{monitored_processes}: $dataMonitCount</a></span></td>
			</tr>";
		}
	}
	
	if(!$users->AsSystemAdministrator){
		$INFOS=array();
		$errT=array();
		$err=array();
		$warn=array();
	}
		
	$html[]=$DashBoardDNSPerfsStats_text;
	
	if($speedtests<>null){$html[]=$speedtests;}	
	
	if(count($INFOS)>0){
		$html[]=@implode("", $INFOS);
		$html[]="<tr><td colspan=2>&nbsp;</td></tr>";
		
	}
		
	if(count($DISKY)>0){
		$html[]=@implode("", $DISKY);
	}
	
	$page=CurrentPageName();
	$seqfw="LoadAjaxRound('sequence-firewall','$page?sequence-firewall=yes');";
	if(isset($_GET["nofw"])){$seqfw=null;}
	
	$html[]="".@implode("", $errT)."
	".@implode("", $err).@implode("", $warn)."
			
	</table>
	</td>
	</tr>
	</table>
	<script>
	
	function LoadseQuenceProxy(){
		LoadAjaxSilent('sequence-proxy','$page?sequence-proxy=yes&nofw=yes&sequence=yes');
	
	}
	

	function LoadSequenceServer(){
			if( !document.getElementById('sequence-server')){return;}
			
			var DASHBOARD_SEQUENCE_SERVER=parseInt(document.getElementById('DASHBOARD_SEQUENCE_SERVER').value);
			if(DASHBOARD_SEQUENCE_SERVER<10){
				DASHBOARD_SEQUENCE_SERVER=DASHBOARD_SEQUENCE_SERVER+1;
				document.getElementById('DASHBOARD_SEQUENCE_SERVER').value=DASHBOARD_SEQUENCE_SERVER;
				setTimeout('LoadSequenceServer()',1000);
				return;
			}
			
			document.getElementById('DASHBOARD_SEQUENCE_SERVER').value=0;
			LoadAjaxSilent('sequence-server','$page?sequence-server=yes&nofw=yes&sequence=yes');
			setTimeout('LoadseQuenceProxy()',20000);
		}
	setTimeout('LoadSequenceServer()',5000);
	</script>		
	";
	$html=$tpl->_ENGINE_parse_body(@implode("", $html));
	@file_put_contents($cachefile, $html);
	return $html;
	
}
function update_find_latest_nightly($array){


	$MAIN=$array["NIGHT"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function update_find_latest($array){


	$MAIN=$array["OFF"];
	if(!is_array($MAIN)){return false;}
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function proxy_status_warning($text,$tooltip,$js){
	
	$toot=texttooltip($text,$tooltip,$js,null,0,"font-size:18px;color:#d32d2d;");
	
	return "<tr>
		<td style='font-size:18px;color:#d32d2d;vertical-align:middle'>
		<div style='width:99%;margin-top:10px;padding:10px;
			border:1px solid #d32d2d; -webkit-border-radius: 4px; 
			-moz-border-radius: 4px;border-radius: 4px;'>
			<table style='width:100%'>
				<tr>
					<td valign='middle;vertical-align:top'><img src='img/warning-32-red.png'></td>
					<td valign='middle' style='padding-left:10px;color:color:#d32d2d;'>$toot</td>
				</tr>
			</table>
			</div>
		</td>
		</tr>
	";
	
	
}

function status_important_event($text,$tooltip=null,$js){
	
	if($tooltip==null){$tooltip=$text;}
	$toot=texttooltip($text,$tooltip,$js,null,0,"font-size:16px;color:#f59c44;");
	
	return "
	<tr>
		<td>
			<div style='width:99%;margin-top:10px;padding:10px;
			border:1px solid #f59c44; -webkit-border-radius: 4px; 
			-moz-border-radius: 4px;border-radius: 4px;'>
			<table style='widh:100%'>
				<tr>
					<td style='vertical-align:top'><img src='img/warning-32-yellow.png'></td>
					<td style='font-size:16px;color:f59c44;vertical-align:middle;'>$toot</td>
				</tr>
			</table>
			</div>
		</td>
	</tr>";
	
	
}
function status_info_event($text,$tooltip=null,$js){

	if($tooltip==null){$tooltip=$text;}
	$toot=texttooltip($text,$tooltip,$js,null,0,"font-size:16px;color:#2975b8;");

	return "
	<tr>
	<td>
	<div style='width:99%;margin-top:10px;padding:10px;
	border:1px solid #2975b8; -webkit-border-radius: 4px;
	-moz-border-radius: 4px;border-radius: 4px;'>
	<table style='widh:100%'>
	<tr>
	<td style='vertical-align:top'><img src='img/32-infos.png'></td>
	<td style='font-size:16px;color:#2975b8;vertical-align:middle;'>$toot</td>
	</tr>
	</table>
	</div>
	</td>
	</tr>";


}

function ufdbguard_toulouse_cloud_version(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	
	$ArticaDbCloud=unserialize(base64_decode($sock->GET_INFO("TLSEDbCloud")));
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	if($TIME==0){return 0;}
	
	$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentTLSEDbCloud"));
	$CURRENT_TIME=0;
	while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
	}
	if($CURRENT_TIME==0){return 0;}

	if($CURRENT_TIME>=$TIME){return 1;}
	return $TIME;

}


function ufdbguard_artica_cloud_version(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	if($users->CORP_LICENSE){return 1;}
	$ArticaDbCloud=unserialize(base64_decode($sock->GET_INFO("ArticaDbCloud")));
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	if($TIME==0){return 0;}
	$CurrentArticaDbCloud=unserialize($sock->GET_INFO("CurrentArticaDbCloud"));
	$TIME=0;
	while (list ($table,$MAIN) = each ($ArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	
	$CURRENT_TIME=0;
	while (list ($table,$MAIN) = each ($CurrentArticaDbCloud) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$CURRENT_TIME){$CURRENT_TIME=$xTIME;}
	}
	if($CURRENT_TIME==0){return 0;}
	
	if($CURRENT_TIME>=$TIME){return 1;}
	return $TIME;
	
}

function meta_server_status(){
	include_once('ressources/class.mysql-meta.inc');
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$icon="disks-128-ok.png";
	$GotoMeta="GoToMeta()";
	if(!$users->AsArticaMetaAdmin){$GotoMeta="blur();";}
	$c=0;
	$q=new mysql_meta();

	$results=$q->QUERY_SQL("SELECT * FROM metahosts");
	if(!$q->ok){
		$err[]=proxy_status_warning("MySQL Error !!!",
				"$q->mysql_error",
				"blur();");
	}
	$ArticaMetaPooling=intval($sock->GET_INFO("ArticaMetaPooling"));
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	$ArticaLinkAutoconnect=intval($sock->GET_INFO("ArticaLinkAutoconnect"));
	$MetaUfdbArticaVer=intval($sock->GET_INFO("MetaUfdbArticaVer"));
	if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$load=$ligne["load"];
		$uuid=$ligne["uuid"];
		$PROXYEMERG=$ligne["PROXYEMERG"];
		$hostname=$ligne["hostname"];
		if(strpos($hostname, ".")>0){$xtr=explode(".",$hostname);$hostname=$xtr[0];}
		$CPU_NUMBER_MAX=$ligne["CPU_NUMBER"]+1.5;
		$updated=$ligne["updated"];
		$mem_perc=$ligne["mem_perc"];
		$WINDOWSAD=$ligne["WINDOWSAD"];
		$ADEMERG=intval($ligne["ADEMERG"]);
		if($load>$CPU_NUMBER_MAX){
			
			$err[]=proxy_status_warning("$hostname: {overloaded} $load",
			"$hostname",
			"Loadjs('artica-meta.cpustats.php?js=yes&uuid=$uuid');");
		}
		if($PROXYEMERG==1){
			$err[]=proxy_status_warning("$hostname: {proxy_in_emergency_mode}",
			"$hostname",
			"Loadjs('artica-meta.urgency.php?uuid=$uuid');");
		}
		
		if($WINDOWSAD==1){
			if($ADEMERG==1){
				$err[]=proxy_status_warning("$hostname: {activedirectory_emergency_mode}",
				"$hostname",
				"Loadjs('artica-meta.menus.php?activedirectory-emergency-disable-js=yes&uuid=$uuid&gpid=0');");
					
			}
		}
		
		if($mem_perc>80){
			$mem_total=FormatBytes($ligne["mem_total"]);
			$alerts[]=status_important_event("$hostname: {memory_exceed_80} &laquo;{$mem_perc}%/$mem_total&raquo;",
			"$hostname",
			"Loadjs('artica-meta.cpustats.php?js=yes&uuid=$uuid');");
	
		}
		
		$xtime=strtotime($updated);
		$diff=time_diff_min($xtime);
		$Difftext=distanceOfTimeInWords($xtime,time(),true);
		if($diff>$ArticaMetaPooling*4){
			$err[]=proxy_status_warning("$hostname: {did_not_talk_with_meta}",
			"{since} $Difftext",
			"Loadjs('artica-meta.cpustats.php?js=yes&uuid=$uuid');");
		}	
		
		$c++;
		
	}
	
	if(count($alerts)>0){
		$icon="disks-128-warn.png";
	}
	
	if(count($err)>0){
		$icon="disks-128-red.png";

	}
	
	$master_version=@file_get_contents("VERSION");
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px' >
	<img src='img/$icon'>
	</td>
	<td>
	<table style='width:100%'>
	<tr>
	<td style='font-size:30px'>
	". $tpl->_ENGINE_parse_body(texttooltip("Artica Meta","Artica Meta","$GotoMeta"))."
	<div style='width:100%;text-align:right'><span style='font-size:16px'>{version}:$master_version</div>
	</td>
	</tr>
	<tr>
	<td colspan=2>&nbsp;</td>
	</tR>
	<tr>
		<td style='font-size:26px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoMeta\"
			style='text-decoration:underline'>{servers}: ". FormatNumber($c)."</a>
		</td>
	</tr>
	<tr>
	".@implode("", $err)."
	".@implode("", $alerts)."
	</table>
</td>
</tr>
</table>
";
$html= $tpl->_ENGINE_parse_body($html);
return $html;
	
	
	
}

function time_diff_min($xtime){
	$data1 = $xtime;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}
function haproxy_server_status(){
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$icon="disks-128-ok.png";
	$GotoHaProxy="GotToHAPROXY()";
	if(!$users->AsSystemAdministrator){$GotoHaProxy="blur();";}
	$datas=base64_decode($sock->getFrameWork("haproxy.php?main-status=yes"));
	$ini->loadString($datas);	
	
	$master_version=$ini->_params["APP_HAPROXY"]["master_version"];
	$running=$ini->_params["APP_HAPROXY"]["running"];
	$uptime=$ini->_params["APP_HAPROXY"]["uptime"];
	$master_memory=$ini->_params["APP_HAPROXY"]["master_memory"];
	$processes_number=$ini->_params["APP_HAPROXY"]["processes_number"];
	
	$WATCHDOG_COUNT_EVENTS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/APACHE_WATCHDOG_COUNT_EVENTS"));
	
	if(intval($WATCHDOG_COUNT_EVENTS)>0){
		$important_events[]=status_important_event("$WATCHDOG_COUNT_EVENTS {important_events_48h}", null, "GotoNginxImportEvents()");
	
	}
	
	if($running==0){
		$icon="disks-128-red.png";
		$err=proxy_status_warning("{stopped}","{load_balancing} {stopped}","$GotoHaProxy");
		$uptime="{stopped}";
		$master_memory=0;
		$processes_number=0;
	}
	
	$icon=imgtootltip($icon,"position:right:{load_balancing}","$GotoHaProxy");
	
	if(count($important_events)>0){
		$important_events_text="<tr><td colspan=2>&nbsp;</td></tr>".@implode("\n", $important_events);
	}
	
	$table=unserialize(base64_decode($sock->getFrameWork("haproxy.php?global-stats=yes")));
	while (list ($num, $ligne) = each ($table) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#\##", $ligne)){continue;}
		$f=explode(",", $ligne);
		$pxname=$f[0];
		$svname=$f[1];
		if($f[32]<>2){continue;}
		$status=$f[17];
		$req_tot=$f[48];
		$bin=FormatBytes($f[8]/1024);
		$bout=FormatBytes($f[9]/1024);
		$color_haproxy="black";
		$arrow="arrow-right-16.png";
		if(!preg_match("#UP#", $status)){
			$arrow="arrow-right-16-grey.png";
			$color_haproxy="#898989";
		}
		
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<table style='width:100%'>
		<tr>
			<td style='width:18px'><img src='img/$arrow'></td>
			<td style='font-size:16px'>
				<a href=\"javascript:blur();\"
					OnClick=\"javascript:$GotoHaProxy\"
					style='text-decoration:underline;color:$color_haproxy'>&laquo;$svname&raquo; ($status): $bin/$bout</a>
			</td>
		</tr>
		</table>
		</td>
		</tr>
		";
		
		
		
		
	}
	
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px' >
	$icon
	</td>
	<td>
	<table style='width:100%'>
	<tr>
	<td style='font-size:30px'>
	". $tpl->_ENGINE_parse_body(texttooltip("{load_balancing}","{APP_HAPROXY}","$GotoHaProxy"))."
	<div style='width:100%;text-align:right'><span style='font-size:16px'>{version}:$master_version</div>
	</td>
	</tr>
	<tr>
	<td colspan=2>&nbsp;</td>
	</tR>
	<tr>
	<td style='font-size:18px'>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:$GotoHaProxy\"
	style='text-decoration:underline'>{memory}: ". FormatBytes($master_memory)."</a></td>
	</tr>
	<tr>
	<td style='font-size:18px'>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:$GotoHaProxy\"
	style='text-decoration:underline'>{processes}: ". FormatNumber($processes_number)."</a></td>
			</tr>
				
			<tr>
			<td style='font-size:18px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoHaProxy\"
			style='text-decoration:underline'>{uptime}: $uptime</a></td>
			</tr>
			".@implode("\n", $stats)."
	
			".@implode("", $errT)."
			".@implode("", $err)."
			$important_events_text
			</table>
			</td>
			</tr>
			</table>
			";
			$html= $tpl->_ENGINE_parse_body($html);
			return $html;
	
}

function nginx_server_status(){
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$icon="disks-128-ok.png";
	$GotoReverseProxy="GotoReverseProxy()";
	$datas=$sock->getFrameWork("cmd.php?apachesrc-ini-status=yes");
	
	$ini->loadString(base64_decode($datas));
	
	
	$master_version=$ini->_params["APP_NGINX"]["master_version"];
	$running=$ini->_params["APP_NGINX"]["running"];
	$uptime=$ini->_params["APP_NGINX"]["uptime"];
	$master_memory=$ini->_params["APP_NGINX"]["master_memory"];
	$processes_number=$ini->_params["APP_NGINX"]["processes_number"];
	
	$APACHEDSIZE=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/APACHEDSIZE"));
	$APACHEDRQS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/APACHEDRQS"));
	$REVERSE_COUNT=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/REVERSE_COUNT"));
	$WATCHDOG_COUNT_EVENTS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/APACHE_WATCHDOG_COUNT_EVENTS"));
	
	if(intval($WATCHDOG_COUNT_EVENTS)>0){
		$important_events[]=status_important_event("$WATCHDOG_COUNT_EVENTS {important_events_48h}", null, "GotoNginxImportEvents()");
	
	}
	
	
	if($APACHEDSIZE>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoApacheStats()\"
		style='text-decoration:underline'>{uploaded}: ". FormatBytes($APACHEDSIZE/1024)."</a></td>
		</tr>";
	
	
	}
	if($REVERSE_COUNT>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:$GotoReverseProxy\"
		style='text-decoration:underline'>{websites}: ". FormatNumber($REVERSE_COUNT)."</a></td>
		</tr>";
	
	
	}
	
	$err=array();
	$errT=array();
	$c=0;
	
	
	
	if($running==0){
		$icon="disks-128-red.png";
		$err=proxy_status_warning("{stopped}","Reverse Proxy {stopped}","$GotoReverseProxy");
		$uptime="{stopped}";
		$master_memory=0;
		$processes_number=0;
	}
	
	
	$icon=imgtootltip($icon,"position:right:{APP_NGINX}","$GotoReverseProxy");

	if(count($important_events)>0){
		$important_events_text="<tr><td colspan=2>&nbsp;</td></tr>".@implode("\n", $important_events);
	}
		
		
	$html="
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:128px' >
			$icon
			</td>
			<td>
			<table style='width:100%'>
			<tr>
			<td style='font-size:30px'>
			". $tpl->_ENGINE_parse_body(texttooltip("Reverse Proxy","{APP_NGINX}","$GotoReverseProxy"))."
		<div style='width:100%;text-align:right'><span style='font-size:16px'>{version}:$master_version</div>
			</td>
			</tr>
			<tr>
			<td colspan=2>&nbsp;</td>
			</tR>
			<tr>
			<td style='font-size:18px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoReverseProxy\"
			style='text-decoration:underline'>{memory}: ". FormatBytes($master_memory)."</a></td>
			</tr>	
			<tr>
			<td style='font-size:18px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoReverseProxy\"
			style='text-decoration:underline'>{processes}: ". FormatNumber($processes_number)."</a></td>
			</tr>				
			
			<tr>
			<td style='font-size:18px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GotoReverseProxy\"
			style='text-decoration:underline'>{uptime}: $uptime</a></td>
			</tr>
			".@implode("\n", $stats)."
	
			".@implode("", $errT)."
			".@implode("", $err)."
			$important_events_text
			</table>
		</td>
	</tr>
</table>
";
$html= $tpl->_ENGINE_parse_body($html);
return $html;	
	
	
}


function postfix_notadmin(){
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$icon="disks-128-ok.png";
	$GoToMessaging="blur()";
	
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangArchiver=intval($sock->GET_INFO("MimeDefangArchiver",true));
	$MimeDefangSpamAssassin=intval($sock->GET_INFO("MimeDefangSpamAssassin"));
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;$MimeDefangSpamAssassin=0;}
	if($MimeDefangEnabled==0){$MimeDefangArchiver=0;$MimeDefangSpamAssassin=0;}
	
	
	if(VerifyRights_ou()){
		$GoToMessaging="GoToMessagingOU('{$_SESSION["ou"]}')";
		
		if($MimeDefangArchiver==1){
			$features[]="
			<tr>
			<td style='font-size:18px'>{backupemail_behavior}: <span id='BACKUP_EMAIL_BEHAVIOR_DASHBOARD'></span></td>
			</tr>";
			$scripts[]="LoadAjaxSilent('BACKUP_EMAIL_BEHAVIOR_DASHBOARD','admin.dashboard.users.php?BACKUP_EMAIL_BEHAVIOR_DASHBOARD=yes');";
			
		}
		
		if($MimeDefangSpamAssassin==1){
			$features[]="
			<tr>
			<td style='font-size:18px'>{quarantine}: <span id='QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD'></span></td>
			</tr>";
			$scripts[]="LoadAjaxSilent('QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD','admin.dashboard.users.php?QUARANTINE_EMAIL_BEHAVIOR_DASHBOARD=yes');";
			
			
		}
		
	}
	
	
	
	$icon=imgtootltip($icon,"position:right:{messaging}","$GoToMessaging");
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px' >
	$icon
	</td>
	<td>
	<table style='width:100%'>
	<tr>
	<td style='font-size:30px'>
	". $tpl->_ENGINE_parse_body(texttooltip("{messaging} {$_SESSION["ou"]}","{messaging}","$GoToMessaging"))."
	
	</td>
	</tr>
	<tr>
	<td colspan=2>&nbsp;</td>
	</tR>
	
	".@implode("\n", $features)."
	
	$perc_cache
	$SUM_FAMILYSITES_TEXT
	".@implode("", $errT)."
	".@implode("", $err)."
	$important_events_text
	</table>
</td>
</tr>
</table>
<script>". @implode("\n", $scripts)."</script>";

$html= $tpl->_ENGINE_parse_body($html);
return $html;
	
}


function postfix_status(){
	if($GLOBALS['VERBOSE']){echo "<h1>postfix_status()</H1>\n";}
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$stats=array();
	$createamailbox=null;
	
	if($GLOBALS['VERBOSE']){echo "<span style='color:red'>AsPostfixAdministrator: $users->AsPostfixAdministrator</span>\n";}
	
	
	if(!$users->AsPostfixAdministrator){
		if($GLOBALS['VERBOSE']){echo "<h1>postfix_notadmin()</H1>\n";}
		return postfix_notadmin();
	}
	
	$version=$sock->getFrameWork("influx.php?version=yes");
	$GoToMessaging="GoToMessaging()";
	
	$stat=unserialize(base64_decode($sock->getFrameWork("cmd.php?postfix-stat=yes")));
	$queues=unserialize($sock->getFrameWork("cmd.php?postfixQueues=yes"));
	$total=base64_decode($sock->getFrameWork("cmd.php?postfix-multi-postqueue=MASTER"));
	$SMTP_SUM_DOMAINS=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_DOMAINS"));
	$SMTP_SUM_CDIR=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_CDIR"));
	$SMTP_REFUSED=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_REFUSED"));
	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	
	$SUM_BACK=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_BACK"));
	$SUM_BACKSIZE=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_BACKSIZE"));
	
	$SMTP_SUM_QUAR=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_QUAR"));
	$SMTP_SUM_QUARSIZE=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_QUARSIZE"));
	
	
	if($GLOBALS['VERBOSE']){echo "<h1>/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_BACKSIZE = $SUM_BACKSIZE</H1>\n";}
	$MimeDefangAutoWhiteList=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangAutoWhiteList"));
	$MimeDefangEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangEnabled"));
	$MimeDefangSpamAssassin=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangSpamAssassin"));
	if($MimeDefangEnabled==0){$MimeDefangAutoWhiteList=0;$MimeDefangSpamAssassin=0;}
	$SUM_TRANSCATS=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SUM_TRANSCATS"));
	$SUM_TRANSCATS_SIZE=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SUM_TRANSCATS_SIZE"));
	
	$SMTP_SUM_ATTACH=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_ATTACH"));
	$SMTP_SUM_ATTACHSIZE=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_SUM_ATTACHSIZE"));
	
	
	
	if($users->cyrus_imapd_installed){
		
		$createamailbox="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('create-user.php')\"
		style='text-decoration:underline'>
		<table style='width:100%'>
		<td width=32px><img src='img/mailbox-add-32.png'>
		<td style='font-size:18px;text-decoration:underline'>{new_mailbox}</td>
				</tr></table>
		</td>
		</tr>";
		
	}
	
	if($GLOBALS['VERBOSE']){echo "<h1>MimeDefangEnabled=$MimeDefangEnabled; SUM_TRANSCATS = $SUM_TRANSCATS</H1>\n";}
	if($MimeDefangEnabled==1){
		if($SUM_TRANSCATS>0){
			if($GLOBALS['VERBOSE']){echo "<h1> SUM_TRANSCATS - BUILD !</H1>\n";}
			$stats[]="
			<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoSMTPTransactions()\"
			style='text-decoration:underline'>{transactions}: ". FormatNumber($SUM_TRANSCATS)."&nbsp;<span  style='font-size:16px'>".FormatBytes($SUM_TRANSCATS_SIZE/1024)."</span></a></td>
			</tr>";
		}
		
		if($SMTP_SUM_ATTACH>0){
			$stats[]="
			<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoSMTPAttachments()\"
			style='text-decoration:underline'>{attachments}: ". FormatNumber($SMTP_SUM_ATTACH)."&nbsp;<span  style='font-size:16px'>".FormatBytes($SMTP_SUM_ATTACHSIZE/1024)."</span></a></td>
			</tr>";			
			
			
		}
		
		
		
	}
	
	
	if($GLOBALS['VERBOSE']){echo "<h1>SMTP_REFUSED = $SMTP_REFUSED</H1>\n";}
	if($SMTP_REFUSED>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoSMTPRefused()\"
		style='text-decoration:underline'>{refused}: ". FormatNumber($SMTP_REFUSED)."</a></td>
		</tr>";
	}
	
	
	
	if($GLOBALS['VERBOSE']){echo "<h1>SMTP_SUM_DOMAINS = $SMTP_SUM_DOMAINS</H1>\n";}
	if($SMTP_SUM_DOMAINS>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoSMTPTableDomains()\"
		style='text-decoration:underline'>{domains}: ". FormatNumber($SMTP_SUM_DOMAINS)."</a></td>
		</tr>";
	}
	if($GLOBALS['VERBOSE']){echo "<h1>SMTP_SUM_CDIR = $SMTP_SUM_CDIR</H1>\n";}
	if($SMTP_SUM_CDIR>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoSMTPTableCDIR()\"
		style='text-decoration:underline'>{networks}: ". FormatNumber($SMTP_SUM_CDIR)."</a></td>
		</tr>";
	
	
	}	
	
	if($GLOBALS['VERBOSE']){echo "<h1>SUM_BACK = $SUM_BACK</H1>\n";}
	if($SUM_BACK>0){
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoBackupMails()\"
		style='text-decoration:underline'>{backuped_mails}: ". FormatNumber($SUM_BACK)."&nbsp;(".FormatBytes($SUM_BACKSIZE/1024).")</a></td>
		</tr>";
		
	}
	

	if($MimeDefangSpamAssassin==1){
		if($SMTP_SUM_QUAR>0){
			$stats[]="
			<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoQuarantineMails()\"
			style='text-decoration:underline'>{quarantine}: ". FormatNumber($SMTP_SUM_QUAR)."&nbsp;<span  style='font-size:16px'>".FormatBytes($SMTP_SUM_QUARSIZE/1024)."</span></a></td>
			</tr>";
		}
	}	
	
	
	$q=new mysql();
	if($MimeDefangAutoWhiteList==1){
		$whitelisted=$q->COUNT_ROWS("autowhite", "artica_backup");
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoAutoWhite()\"
		style='text-decoration:underline'>{smtp_AutoWhiteList}: ". FormatNumber($whitelisted)." </a></td>
		</tr>";
		
	}
	
	
	if(is_file("{$GLOBALS["BASEDIR"]}/SMTP_TOTALS")){
		$SMTP_TOTALS=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_TOTALS"));
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoPflogsummDetails()\"
		style='text-decoration:underline'>{received}: ". FormatNumber($SMTP_TOTALS["received"])."</a></td>
		</tr>";
		
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoPflogsummDetails()\"
		style='text-decoration:underline'>{delivered}: ". FormatNumber($SMTP_TOTALS["delivered"])."</a></td>
		</tr>";
		
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoPflogsummDetails()\"
		style='text-decoration:underline'>{rejected}: ". FormatNumber($SMTP_TOTALS["rejected"])."</a></td>
		</tr>";
		
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoPflogsummDetails()\"
		style='text-decoration:underline'>{senders}: ". FormatNumber($SMTP_TOTALS["senders"])."</a></td>
		</tr>";
		
		$stats[]="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GotoPflogsummDetails()\"
		style='text-decoration:underline'>{recipients}: ". FormatNumber($SMTP_TOTALS["recipients"])."</a></td>
		</tr>";
		
		
	}
	
	
	
	
	$postfix_status=$stat[0];
	$postfix_version=$stat[1];
	$icon="disks-128-ok.png";
	$err=array();
	$errT=array();
	$c=0;
	
	if(is_file(dirname(__FILE__).'/logs/artica-backup-size.ini')){
		$ini=new Bs_IniHandler(dirname(__FILE__).'/logs/artica-backup-size.ini');
		if($ini->_params["artica_backup"]["original_messages"]==null){$ini->_params["artica_backup"]["original_messages"]=0;}
		if($ini->_params["artica_backup"]["attachments"]==null){$ini->_params["artica_backup"]["attachments"]=0;}
		$size=$ini->_params["artica_backup"]["original_messages"]+$ini->_params["artica_backup"]["attachments"];
		$size=FormatBytes($size);
		$link2=CellRollOver("Loadjs('postfix.backup.monitoring.php')");
		if(!isset($queues["backup"])){$queues["backup"]=0;}
		if(!isset($queues["quarantine"])){$queues["quarantine"]=0;}
	
	
		$artica_backup="<tr>
		<td align='right' $link2 style='$textFONTStyle'>{backup_size}:</a>&nbsp;</td>
		<td $link2 ><strong style='$textFONTStyle'>$size</td>
		</tr>";
	
		if($queues["quarantine"]>0){
			$link_quarantine_progress=CellRollOver("Loadjs('postfix.quarantine.progress.php')");
		}
	
	
	
	}
	
	if($users->cyrus_imapd_installed){
		include_once(dirname(__FILE__)."/ressources/class.cyrus.inc");
		$cyr=new cyrus();
		if(!$cyr->TestConnection()){
			$err[]=proxy_status_warning("{unable_to_connect_imap}",
					"{unable_to_connect_imap}",
					"Loadjs('cyrus.sync-services.progress.php')");
			$icon="disks-128-red.png";
		}
		
		
	}
	
	
	if($EnableStopPostfix==1){
		$icon="disks-128-red.png";
		$err[]=proxy_status_warning("{messaging_stopped}",
				"{messaging_stopped_explain}",
				"Loadjs('postfix.stop.php')");
		$icon="disks-128-red.png";
	}	
	
	
		$link_corrupt="Loadjs('postfix.corrupt.queue.php')";
		$link_incoming=CellRollOver("Loadjs('postfix.queue.monitoring.php?show-queue=incoming&count={$queues["incoming"]}');");
		$link_active=CellRollOver("Loadjs('postfix.queue.monitoring.php?show-queue=active&count={$queues["active"]}');");
		$link_deferred=CellRollOver("Loadjs('postfix.queue.monitoring.php?show-queue=deferred&count={$queues["deferred"]}');");
		
		$q=new mysql();
		$sql="SELECT SUM(`size`) as tsize,COUNT(msgid) as tcount FROM postqueue WHERE `instance`='master'";
		if(function_exists("mysql_fetch_array")){
			$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		}
		
		
		
		$postfix_admin_mysql=$q->COUNT_ROWS("postfix_admin_mysql", "artica_events");
		
		
		if($postfix_admin_mysql>0){
			$postfix_admin_mysql_text="		
		<td style='font-size:18px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:GoToPostfixWatchdog()\"
		style='text-decoration:underline'>{watchdog}: $postfix_admin_mysql {events}</a></td>
		</tr>
		<tr><td>&nbsp;</td></tr>";
			
		}
		
		$tot=$ligne["tcount"];
		$tot_size=$ligne["tsize"]/1024;
		$tot_size=FormatBytes($tot_size);		
		
		if($GLOBALS['VERBOSE']){echo "<h1>stats arrayof ". count($stats)." elements</H1>\n";}
		
		$icon=imgtootltip($icon,"position:right:{messaging}","$GoToMessaging");
$html="
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:128px' >
		$icon
		</td>
		<td>
		<table style='width:100%'>
		<tr>
		<td style='font-size:30px'>
		". $tpl->_ENGINE_parse_body(texttooltip("{APP_POSTFIX}","{messaging}","$GoToMessaging"))."
		<div style='width:100%;text-align:right'><span style='font-size:16px'>{version}:$postfix_version</div>
		</td>
		</tr>
		$createamailbox
		<tr>
			<td colspan=2>&nbsp;</td>
		</tR>
		
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:PostfixQueueMonitoring()\"
		style='text-decoration:underline'>{smtp_queues}: $tot_size</a></td>
		</tr>$postfix_admin_mysql_text
		".@implode("\n", $stats)."

		$perc_cache
		$SUM_FAMILYSITES_TEXT
		".@implode("", $errT)."
		".@implode("", $err)."
		$important_events_text
		</table>
		</td>
	</tr>
</table>
			";
	$html= $tpl->_ENGINE_parse_body($html);
	return $html;	
	
}


function influxdb_status(){
	
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$version=$sock->getFrameWork("postgres.php?version=yes");
	$version=$tpl->_ENGINE_parse_body("PostgreSQL $version");
	$GoToStatsOptions="GoToStatsOptions()";
	$NOT_CATEGORIZED=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/NOT_CATEGORIZED"));

	
	$icon="disks-128-ok.png";
	$err=array();
	$errT=array();
	$c=0;
	$SUM_FAMILYSITES=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/SUM_FAMILYSITES"));

	
	$SERVICES_STATUS=SERVICES_STATUS();
	if(!is_array($SERVICES_STATUS)){$CountDeServices=$SERVICES_STATUS;}else{
		$icon="disks-128-warn.png";
		$err=$SERVICES_STATUS;
	}
	
	if($SUM_FAMILYSITES>0){
		$SUM_FAMILYSITES=FormatNumber($SUM_FAMILYSITES);
		$SUM_FAMILYSITES_TEXT="
		<tr>
		<td style='font-size:20px'>{websites}:
		<a href=\"javascript:blur();\" OnClick=\"javascript:GotoMysQLAllWebsites();\"
		style='text-decoration:underline'>$SUM_FAMILYSITES</a></td>
		</tr>";
	}
	
	if($NOT_CATEGORIZED>0){
		$NOT_CATEGORIZED=FormatNumber($NOT_CATEGORIZED);
		$NOT_CATEGORIZED_ROW="<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"GotoNotCategorized()\"
		style='text-decoration:underline'>{websites}: $NOT_CATEGORIZED {not_categorized}</a></td>
		</tr>";
	
	}	
	
	
	$InfluxListenInterface=$sock->GET_INFO("InfluxListenInterface");
	if($InfluxListenInterface==null){$InfluxListenInterface="0.0.0.0";}
	$stats_appliance_count_clients=stats_appliance_count_clients();
	
	
	
	
	$icon=imgtootltip($icon,"position:right:{configure_your_database}","$GoToStatsOptions");
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:128px' >
		$icon
	</td>
		<td>
			<table style='width:100%'>
				<tr>
				<td style='font-size:30px'>
				". $tpl->_ENGINE_parse_body(texttooltip("{your_database}","{configure_your_database}","$GoToStatsOptions"))."
						<div style='width:100%;text-align:right'><span style='font-size:16px'>$version</div>
						</td>
				</tr>
				$CountDeServices
				
		<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$GoToStatsOptions\"
			style='text-decoration:underline'>{listen}: $InfluxListenInterface:5432</a></td>
		</tr>
					
		<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoStatsApplianceClients()\"
			style='text-decoration:underline'>{clients}: $stats_appliance_count_clients</a></td>
		</tr>
		$perc_cache
		$SUM_FAMILYSITES_TEXT
		$NOT_CATEGORIZED_ROW
		". TOP_GRAPHS()."					
				".@implode("", $errT)."
				".@implode("", $err)."
				$important_events_text
			</table>
		</td>
	</tr>
	</table>
	<script>
		LoadAjaxSilent('proxy_dashboard_counters','$page?proxy_dashboard_counters=yes');
	</script>
	";
		$html= $tpl->_ENGINE_parse_body($html);	
	return $html;
	
	
}


function TOP_GRAPHS(){
	$sock=new sockets();
	$users=new usersMenus();
	if(!$users->AllowViewStatistics){return;}
	if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>{$GLOBALS["BASEDIR"]}/TOP_BLOCKED<br>BLOCKED DATA:". @file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED")."</strong><br>";}
	if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>{$GLOBALS["BASEDIR"]}/TOP_USER<br>TOP_USER:". @file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED")."</strong><br>";}
	$TOP_WEBSITE=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_WEBSITE"));
	$TOP_USER=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_USER"));
	$TOP_BLOCKED=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED"));
	$COUNT_DE_MEMBERS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT"));
	$CalamarisSchedules=intval($sock->GET_INFO("CalamarisSchedules"));
	
	if($CalamarisSchedules>0){
		if(is_file("{$GLOBALS["BASEDIR"]}/CALAMARIS")){
			$tr[]="
			<tr>
			<td style='font-size:20px;font-weight:bold'>
				<table style='width:100%'>
				<tr>
					<td style='width:24px'><img src='img/statistics-24.png'></td>
					<td style='width:99%;font-size:20px;font-weight:bold''>
					". texttooltip("{traffic_report}","{traffic_report}",
									"GotoSquidCalamaris()")."</td>
				</tr>
				</table>
			</td>
			</tr>";
			
		}
	}
	

	
	
	
	$PROXY_REQUESTS_NUMBER=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/PROXY_REQUESTS_NUMBER"));
	$PROXY_REQUESTS_NUMBER_TEXT=FormatNumber($PROXY_REQUESTS_NUMBER);
	
	if($COUNT_DE_MEMBERS>0){
		// TABLE current_members
		$tr[]="
		<tr>
		<td style='font-size:20px'>
				". texttooltip("{members}: $COUNT_DE_MEMBERS","{dashboard_browse_members_explain}",
							"GotoProxyMysqlCurrentMembers()")."
		</td>
		</tr>";
	
	}
	
	if($PROXY_REQUESTS_NUMBER>0){
		$tr[]="
		<tr>
			<td style='font-size:20px'>{requests}:
			<a href=\"javascript:blur();\" OnClick=\"javascript:GotoProxyMysqlCurrentMembers();\"
			style='text-decoration:underline'>$PROXY_REQUESTS_NUMBER_TEXT</a>
		</td>
		</tr>";
	}	
	
	
	$tr[]="
	<tr>
	<tr>
	<td style='font-size:22px'>&nbsp;</td>
	</tr>			
	<tr>
	<tr>
	<td style='font-size:22px'>TOP:</td>
	</tr>";
	
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("dashboard_currentusers")){
		$sql="SELECT `USER`,`MAC`,`IPADDR`,`SIZE` FROM dashboard_currentusers ORDER BY SIZE DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$USER=null;
		if($ligne["USER"]=="none"){$ligne["USER"]=null;}
		if($ligne["USER"]<>null){$USER=$ligne["USER"];}
		if($USER==null){if($ligne["MAC"]<>null){$USER=$ligne["MAC"];}}
		if($USER==null){if($ligne["IPADDR"]<>null){$USER=$ligne["IPADDR"];}}
		$SIZE=$ligne["SIZE"]/1024;
		
		$tr[]="
		
		<tr>
		<td style='font-size:14px'>
		<a href=\"javascript:blur();\"
		OnClick=\"GotoProxyMysqlTOPMembers()\"
		style='text-decoration:underline'>{member}:  $USER (".FormatBytes($SIZE)." <i>{last_15_minutes}</i>)</a> </td>
		</tr>";
		
	}	
	
	
	
	if($TOP_WEBSITE[0]>0){
		$size=FormatBytes($TOP_WEBSITE[0]/1024);
		$tr[]="
		
		<tr>
		<td style='font-size:14px'>
		<a href=\"javascript:blur();\"
		OnClick=\"GotoSquidTopStats()\"
		style='text-decoration:underline'>{website}: {$TOP_WEBSITE[1]} ($size)</a> </td>
		</tr>";
			
	}
	
	if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>TOP BLOCKED: {$TOP_BLOCKED[0]}</strong><br>\n";}
	
	if($TOP_BLOCKED[0]>0){
		$rqs=FormatNumber($TOP_BLOCKED[0]);
		$tr[]="
	
		<tr>
		<td style='font-size:14px'>
		<a href=\"javascript:blur();\"
		OnClick=\"GotoSquidTopStats()\"
		style='text-decoration:underline'>{blocked}: {$TOP_BLOCKED[1]} ($rqs)</a> </td>
		</tr>";
			
	}	
	
	if($TOP_USER[0]>0){
		$size=FormatBytes($TOP_USER[0]/1024);
		$tr[]="
	
		<tr>
		<td style='font-size:14px'>
		<a href=\"javascript:blur();\"
		OnClick=\"GotoSquidTopStats()\"
		style='text-decoration:underline'>{member}: {$TOP_USER[1]} ($size)</a> </td>
		</tr>";
			
	}	
	if(count($tr)>1){
		return @implode("\n", $tr);
	}
	
	
}


function _ntmlauthenticators(){
	include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
	$cache_manager=new cache_manager();
	$datas=explode("\n",$cache_manager->makeQuery("ntlmauthenticator"));
	if(!$cache_manager->ok){return;}
	
	$CPU_NUMBER=0;
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			continue;
		}
	
		if(preg_match("#number active: ([0-9]+) of ([0-9]+)#",$ligne,$re)){
			$Active=intval($re[1]);
			$Max=intval($re[2]);
			$prc=round(($Active/$Max)*100);
			$ARRAY[$CPU_NUMBER]=$prc;
		}
	
	}
	
	return $ARRAY;

}


function proxy_status(){
	
	
	$ntmlauthenticators_array=array();
	$COUNT_DE_CACHES_TEXT=null;
	$webfilters_paranoid_text=null;
	$users=new usersMenus();
	if($users->STATS_APPLIANCE){   return influxdb_status(); }
	if($users->POSTFIX_INSTALLED){ return postfix_status(); }
	$sock=new sockets();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableHaProxy=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHaProxy"));
	
	if($SQUIDEnable==0){
		if($EnableArticaMetaServer==1){return meta_server_status();}
		if($EnableNginx==1){return nginx_server_status(); }
		if($EnableHaProxy==1){return haproxy_server_status(); }
	}
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	$HTTrackInSquid=intval($sock->GET_INFO("HTTrackInSquid"));
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	
	unset($_GET["_"]);
	$sock=new sockets();
	if(!isset($_GET["ForceCache"])){
		$md5CacheF=md5("proxy_status{$_SESSION["uid"]}{$tpl->language}".serialize($_GET));
		$cachefile="/usr/share/artica-postfix/ressources/interface-cache/$md5CacheF";
		if(file_time_sec_Web($cachefile)<5){return @file_get_contents($cachefile);}
	}
	
	if(isset($_GET["ForceCache"])){$sock->getFrameWork("cmd.php?Global-Applications-Status=yes");}
	
	
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$perc_cache=null;
	$active_resquests=null;
	$important_events=null;
	$CountDeServices=null;
	$icon="disks-128-ok.png";
	
	$rqs=null;

	$NOT_CATEGORIZED_ROW=null;
	$mgr_client_list=$q->COUNT_ROWS("mgr_client_list");
	$SNMP_WALK=proxy_snmp();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$MacToUidUrgency=intval($sock->GET_INFO("MacToUidUrgency"));
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
	$SquidUFDBUrgency=intval($sock->GET_INFO("SquidUFDBUrgency"));
	$IsPortsConverted=intval($sock->GET_INFO("IsPortsConverted"));
	$SquidCacheFullHideWarn=intval($sock->GET_INFO("SquidCacheFullHideWarn"));
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$BasicAuthenticatorEmergency=intval($sock->GET_INFO("BasicAuthenticatorEmergency"));
	$eCAPClamavEmergency=intval($sock->GET_INFO("eCAPClamavEmergency"));
	$ParanoidBlockerEmergency=intval($sock->GET_INFO("ParanoidBlockerEmergency"));
	$EnableeCapClamav=intval($sock->GET_INFO("EnableeCapClamav"));
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	$WebFiltering_blocked=null;
	$CACHES_AVG=round(@file_get_contents("{$GLOBALS["BASEDIR"]}/CACHES_AVG"),1);
	$COUNT_DE_BLOCKED=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_BLOCKED"));
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	
	$AsTransparent=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_TRANSPARENT"));
	$WATCHDOG_COUNT_EVENTS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/WATCHDOG_COUNT_EVENTS"));
	
	$COUNT_DE_CACHES=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES"));
	$SUM_FAMILYSITES=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/SUM_FAMILYSITES"));
	$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
	
	
	$TOP_WEBSITE=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/TOP_WEBSITE"));
	$NOT_CATEGORIZED=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/NOT_CATEGORIZED"));
	$CACHES_RATES=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/TOTAL_CACHED_ARRAY"));
	$TOTALS_NOT_CACHED=intval($CACHES_RATES["TOTALS_NOT_CACHED"]);
	$TOTALS_CACHED=intval($CACHES_RATES["TOTALS_CACHED"]);
	

	
	
	if($users->AsProxyMonitor){
		if($EnableKerbAuth==1){
				$ntmlauthenticators=_ntmlauthenticators();
				while (list ($cpu, $purc) = each ($ntmlauthenticators) ){
					$color="black";
					if($purc>95){$color="#d32d2d";}
					$ntmlauthenticators_array[]="
					<tr>
					<td style='font-size:18px'>
					<a href=\"javascript:blur();\"
					OnClick=\"javscript:Loadjs('squid.ntmlauthenticators.php?cpu=$cpu')\"
					style='text-decoration:underline;color:$color'>{ntlm_processes}: CPU#$cpu {$purc}%</a></td>
					</tr>";
					
				}
				
		}
	}
	
	
	

	$SERVICES_STATUS=SERVICES_STATUS();
	if(!is_array($SERVICES_STATUS)){$CountDeServices=$SERVICES_STATUS;}else{
		$icon="disks-128-warn.png";
		$err=$SERVICES_STATUS;
	}
	
	
	$scriptEnd="LoadAjaxTiny('active-directory-dash-infos','$page?active-directory-dash-infos=yes');";
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	$realsquidversion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidRealVersion");

	
	$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){
		$err[]=proxy_status_warning("MySQL error",$q->mysql_error_html(),"blur()");
			
	}
	$COUNTDePorts=$ligne["tcount"];
	$js_icon_stats=null;
	$icon_stats="<div style='float:left;margin-right:10px;margin-top:5px'><img src='img/statistics-24-grey.png'></div>";
	
	$GotoNotCategorized="GotoNotCategorized()";
	$ActiveRequests_js="Loadjs('squid.active.requests.php')";
	$GotoNotCategorized_underline="underline";
	$ActiveRequests_underline="underline";
	$GotoMysQLAllWebsites_underline="underline";
	$GoToCachedStatistics="GoToCachedStatistics()";
	$GotoMysQLAllWebsites="GotoMysQLAllWebsites()";
	
	$LoadProxyUpdate="LoadProxyUpdate();";
	$LOadUfdbUpdate="GoToWebfilteringDBstatus();";
	
	

	
	if(!$users->AllowViewStatistics){
		$GotoNotCategorized="blur();";
		$ActiveRequests_js="blur()";
		$GotoNotCategorized_underline="none";
		$ActiveRequests_underline="none";
		$GoToCachedStatistics="blur()";
		$GotoMysQLAllWebsites="blur()";
		$GotoMysQLAllWebsites_underline="none";
	}
	
	if(!$users->AsSquidAdministrator){
		$LoadProxyUpdate="blur()";
		$LOadUfdbUpdate="blur()";
	}
	
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance<2){
		
		$prec=round(@file_get_contents("{$GLOBALS["BASEDIR"]}/CACHED_AVG"),1);
		$PROXY_REQUESTS_NUMBER=@file_get_contents("{$GLOBALS["BASEDIR"]}/PROXY_REQUESTS_NUMBER");
		$PROXY_REQUESTS_NUMBER=FormatNumber($PROXY_REQUESTS_NUMBER);
		if($COUNT_DE_CACHES>0){
			$COUNT_DE_CACHES_KB=$COUNT_DE_CACHES*1024;
			$COUNT_DE_CACHES_TEXT=FormatBytes($COUNT_DE_CACHES_KB);
		}
		
		
		
		
		
		$js_icon_stats="OnMouseOver=\"this.style.cursor='pointer';\"
		OnMouseOut=\"this.style.cursor='auto'\"
		OnClick=\"javascript:$GoToCachedStatistics;\"";
		
		
		if(is_file("{$GLOBALS["BASEDIR"]}/CACHED_ROW_DAY")){
			$icon_stats="<div style='float:left;margin-right:10px;margin-top:5px'>
					<img src='img/statistics-24.png'></div>";
		}
		
		

		
		
		if($SUM_FAMILYSITES>0){
			$SUM_FAMILYSITES=FormatNumber($SUM_FAMILYSITES);
			$SUM_FAMILYSITES_TEXT="
			<tr>
			<td style='font-size:20px'>{websites}:
			<a href=\"javascript:blur();\" OnClick=\"javascript:$GotoMysQLAllWebsites;\"
			style='text-decoration:$GotoMysQLAllWebsites_underline'>$SUM_FAMILYSITES</a></td>
			</tr>";
				
			
		}
		
		
	}
	
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$ActiveRequestsIpaddr=count($ActiveRequestsR["IPS"]);
	$ActiveRequestsMembers=count($ActiveRequestsR["USERS"]);
	

	
	
	if($NOT_CATEGORIZED>0){
		$NOT_CATEGORIZED=FormatNumber($NOT_CATEGORIZED);
		$NOT_CATEGORIZED_ROW="<tr>
			<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"$GotoNotCategorized\"
		style='text-decoration:$GotoNotCategorized_underline'>{websites}: $NOT_CATEGORIZED {not_categorized}</a></td>
		</tr>";
		
	}
	
	
		$TITLE_REQUESTS="
		<tr>
			<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"$ActiveRequests_js\"
		style='text-decoration:$ActiveRequests_underline'>$ActiveRequestsNumber {active_requests}</a></td>
		</tr>";
	
	
	
	
	
	if($COUNTDePorts==0){
		$err[]=proxy_status_warning("{no_listening_port_defined}",
		"{no_listening_port_proxydefined_explain}",
		"GotoSquidPorts()");
		
	}
	
	
	if($SquidDebugAcls==1){
		$err[]=proxy_status_warning("{debug_acls}",
				"{debug_acls_explain}",
				"Loadjs('squid.acls.options.php')");
	}
	
	if($SNMP_WALK["ERROR"]){
		$err[]=proxy_status_warning("SNMP:{need_to_restart_webconsole}",
		"{click_to_install}",
		"Loadjs('php-snmp.progress.php'");
	}
	
	
	
	
	preg_match("#^([0-9]+)\.([0-9]+)#", $realsquidversion,$re);
	$MAJOR=intval($re[1]);
	$MINOR=intval($re[2]);
	$INCOMPATIBLE=true;
	$REV=0;
	$BUILD=0;
	if($MAJOR>2){if($MINOR>4){$INCOMPATIBLE=false;}}
	if($MAJOR==0){$INCOMPATIBLE=false;}
	if($MAJOR==4){$INCOMPATIBLE=false;}
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $realsquidversion,$re)){
		$REV=intval($re[3]);
	}
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)-([0-9]+)-r([0-9]+)#", $realsquidversion,$re)){
		$BUILD=intval($re[4].$re[5]);
	}
	
	if($SQUIDEnable==1){
			if($INCOMPATIBLE){
				$incompatible_proxy_version=$tpl->_ENGINE_parse_body("{incompatible_proxy_version}");
				$incompatible_proxy_version=str_replace("%s", $realsquidversion, $incompatible_proxy_version);
				$err[]=proxy_status_warning($incompatible_proxy_version,$incompatible_proxy_version,$LoadProxyUpdate);
				
			}	
		
			
		$ArticaTechNetSquidRepo=unserialize(base64_decode($sock->GET_INFO("ArticaTechNetSquidRepo")));
		$NEWVER=null;
		while (list ($key, $array) = each ($ArticaTechNetSquidRepo) ){
			$AVVERSION=$array["VERSION"];
			$XREV=0;
			$XBUILD=0;
			preg_match("#^([0-9]+)\.([0-9]+)#", $AVVERSION,$re);
			$XMAJOR=intval($re[1]);
			$XMINOR=intval($re[2]);
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $AVVERSION,$re)){
				$XREV=intval($re[3]);
			}
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)-([0-9]+)-r([0-9]+)#", $AVVERSION,$re)){
				$XBUILD=intval($re[4].$re[5]);
			}
			
			
			$KEY=intval("$XMAJOR$XMINOR$XREV$XBUILD");
			
			
			
			if($MAJOR>$XMAJOR){continue;}
			
			if($GLOBALS["VERBOSE"]){echo "<strong> squidver check $XMAJOR/$XMINOR/$XREV/$XBUILD - $MAJOR/$MINOR/$REV/$BUILD</strong>\n<br>";}
	
			
			if($XMAJOR>$MAJOR){if($GLOBALS["VERBOSE"]){echo "<strong> squidver check $XMAJOR>$MAJOR</strong>\n<br>";}$NEWVER=$AVVERSION;break;}
			if($XMAJOR==$MAJOR){if($XMINOR>$MINOR){$NEWVER=$AVVERSION;break;}}
			if($XMAJOR==$MAJOR){if($XMINOR==$MINOR){if($XREV>$REV){$NEWVER=$AVVERSION;break;}}}
			if($XMAJOR==$MAJOR){if($XMINOR==$MINOR){if($XREV==$REV){if($XBUILD>$BUILD){$NEWVER=$AVVERSION;break;}}}}
		
			
		}
		
		if($users->AsSquidAdministrator){
			if($NEWVER<>null){
				$INFOS[]=status_info_event("{SQUID_NEWVERSION} $NEWVER","{SQUID_NEWVERSION_TEXT}",$LoadProxyUpdate);
				
			}
		}
	
	}
	
	if($SquidUrgency==1){
		$jsOn="Loadjs('squid.urgency.php?justbutton=yes')";
		if(!$users->AsSquidAdministrator){$jsOn="blur()";}
		$err[]=proxy_status_warning("{proxy_in_emergency_mode}","{proxy_in_emergency_mode_explain}",$jsOn);		
		$icon="disks-128-red.png";
		//proxy_in_emergency_mode
		//proxy_in_emergency_mode_explain
	}
	if($SquidSSLUrgency==1){
		$jsOn="Loadjs('squid.urgency.php?ssl=yes');";
		if(!$users->AsSquidAdministrator){$jsOn="blur()";}
		$icon="disks-128-red.png";
		$err[]=proxy_status_warning("{proxy_in_ssl_emergency_mode}","{proxy_in_ssl_emergency_mode_explain}",$jsOn);
	}
	if($MacToUidUrgency==1){
		$jsOn="Loadjs('squid.urgency.php?justbutton=yes');";
		if(!$users->AsSquidAdministrator){$jsOn="blur()";}
		$icon="disks-128-red.png";
		$err[]=proxy_status_warning("{proxy_in_MacToUid_emergency_mode}","{proxy_in_MacToUid_emergency_mode_explain}",$jsOn);
	}	
	
	

	if($SQUIDEnable==1){
		
			if($SquidUFDBUrgency==1){
				$jsOn="Loadjs('squid.urgency.php?ufdb=yes');";
				if(!$users->AsSquidAdministrator){$jsOn="blur()";}
				$icon="disks-128-red.png";
				
				$err[]=proxy_status_warning("{proxy_in_webfiltering_emergency_mode}",
						"{proxy_in_webfiltering_emergency_mode_explain}",$jsOn);
	
			}
			
			if($EnableUfdbGuard==1){
				if($users->CORP_LICENSE){
			
				$ufdbguard_artica_cloud_version=ufdbguard_artica_cloud_version();
				if($ufdbguard_artica_cloud_version==0){
					$jsOn="Loadjs('dansguardian2.articadb-progress.php')";
					if(!$users->AsSquidAdministrator){$jsOn="blur()";}
					$important_events[]=status_important_event("{update_webfiltering_artica_databases}",
							"{update_webfiltering_artica_databases_not_updated}",$jsOn);
					
					
				}
				if($ufdbguard_artica_cloud_version>1){
					$jsOn="Loadjs('dansguardian2.articadb-progress.php')";
					if(!$users->AsSquidAdministrator){$jsOn="blur()";}
					$important_events[]=status_important_event("{webfiltering_artica_databases_available}",
					"{webfiltering_artica_databases_available_explain}",$jsOn);
				
				
				}
			}
			
			$ufdbguard_toulouse_cloud_version=ufdbguard_toulouse_cloud_version();
			if($ufdbguard_toulouse_cloud_version==0){
				$jsOn="Loadjs('dansguardian2.articadb-progress.php')";
				if(!$users->AsSquidAdministrator){$jsOn="blur()";}
				$important_events[]=status_important_event("{update_webfiltering_toulouse_databases}",
						"{update_webfiltering_toulouse_databases_not_updated}",$jsOn);
			}
			
			if($ufdbguard_toulouse_cloud_version>1){
				$jsOn="Loadjs('dansguardian2.articadb-progress.php')";
				if(!$users->AsSquidAdministrator){$jsOn="blur()";}
				$important_events[]=status_important_event("{webfiltering_toulouse_databases_available}",
						"{webfiltering_artica_databases_available_explain}",$jsOn);
					
					
			}
			
			
			
		}
	}
	if($SQUIDEnable==1){
		if($LogsWarninStop==1){
			$jsOn="Loadjs('system.log.emergency.php');";
			if(!$users->AsSquidAdministrator){$jsOn="blur()";}
			$help=help_icon("{squid_logs_urgency}");
			$text=texttooltip("{squid_logs_urgency_section}","{squid_logs_urgency}",$jsOn);
			$icon="disks-128-red.png";
			$err[]=proxy_status_warning("{squid_logs_urgency_section}","{squid_logs_urgency}",$jsOn);
		}
	}
	
	
	if($SQUIDEnable==1){
		if($IsPortsConverted==0){
			$jsOn="Loadjs('squid.compile.progress.php');";
			if(!$users->AsSquidAdministrator){$jsOn="blur()";}
			$icon="disks-128-warn.png";
			$err[]=proxy_status_warning("{IsPortsConverted_requested}",
					"{squid_IsPortsConverted_explain}",$jsOn);
			
			
		}else{
			if($AsTransparent>0){
				$FireHolConfigured=intval($sock->GET_INFO("FireHolConfigured"));
				if($FireHolConfigured==0){
					$icon="disks-128-warn.png";
					$err[]=proxy_status_warning("{transparent_mode_issue}",
							"{squid_transparent_no_firewall}",$jsOn);
				}
				
				
			}
			
			
		}
	}
	
	$GoToCategoriesServiceA="GoToCategoriesServiceA()";
	$GotoAdConnection="GotoAdConnection()";
	$LoadMainDashProxy="LoadMainDashProxy()";
	$GoToServices="GoToServices()";
	$GoToUfdb="GoToUfdb()";
	$GoToCaches="GoToCaches()";
	$GoToCaches_underline="underline";
	
	
	if(!$users->AsDansGuardianAdministrator){$GoToCategoriesServiceA="blur()";$GoToUfdb="blur()";}
	if(!$users->AsSquidAdministrator){
			$GotoAdConnection="blur()";
			$LoadMainDashProxy="blur()";
			$GoToServices="blur()";
			$GoToCaches="blur()";
			$GoToCaches_underline="none";
	}
	if($SQUIDEnable==1){
		$catz=new mysql_catz();
		if($catz->UfdbCatEnabled==1){
			$categories=$catz->ufdbcat("google.com");
			
			if(!$catz->ok){
				$icon="disks-128-warn.png";
				
				$err[]=proxy_status_warning("{APP_UFDBCAT}: {connection_error}",
				$catz->mysql_error,$GoToCategoriesServiceA);
			}
			
		}
	}
	
	if($SQUIDEnable==1){	
	   if($sock->SQUID_IS_EXTERNAL_LDAP()){
	   		$tests=CHECK_SQUID_EXTERNAL_LDAP();
	   		if($tests<>null){
	   			$err[]=proxy_status_warning("$tests","$tests","GotoOpenldap()");
	   		}
	   }
	}
	
if($BasicAuthenticatorEmergency==1){
	$jsOn="Loadjs('squid.urgency.php?justbutton=yes');";
	if(!$users->AsSquidAdministrator){$jsOn="blur()";}
	$icon="disks-128-warn.png";
		
	$err[]=proxy_status_warning("{authentication_emergency_mode}",
			"{authentication_emergency_mode_explain}",$jsOn);

	
}
	
if($SQUIDEnable==1){	
	if($EnableKerbAuth==1){
		
		if($ActiveDirectoryEmergency==1){
			$jsOn="Loadjs('squid.urgency.php?activedirectory=yes');";
			if(!$users->AsSquidAdministrator){$jsOn="blur()";}
			$icon="disks-128-warn.png";
			
			$err[]=proxy_status_warning("{activedirectory_emergency_mode}",
			"{activedirectory_emergency_mode_explain}",$jsOn);
		}
		
		if(!$users->CORP_LICENSE){
			$Days=86400*30;
			$DayToLeft=30;
			if(is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){
				include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
				$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
				$seconds_diff = $EndTime - time();
				$DayToLeft=floor($seconds_diff/3600/24);
			}
			$MAIN_ERROR=$tpl->_ENGINE_parse_body("{warn_no_license_activedirectory_30days}");
			$MAIN_ERROR=str_replace("%s", $DayToLeft, $MAIN_ERROR);
			$important_events[]=status_important_event($MAIN_ERROR,$MAIN_ERROR,$jsOn);
		}
		
	
		if($ActiveDirectoryEmergency==0){
			$IsConnected=IsKerconnected();
			if($IsConnected<>"TRUE"){
				$err[]=proxy_status_warning("{proxy_is_not_configured_ad}",null,$GotoAdConnection);
		
			}
		}
		
		$TestLDAPAD=TestLDAPAD();
		if($TestLDAPAD<>null){$err[]=$TestLDAPAD;}
		
		
	
		$AdminAsSeenNTLMPerfs=intval($sock->GET_INFO("AdminAsSeenNTLMPerfs"));
		if($AdminAsSeenNTLMPerfs==0){
			$err[]=proxy_status_warning("{NTLM_PERFORMANCES_NOT_DEFINED}",null,$GotoAdConnection);
	
		}
	
	}	
}

if($EnableeCapClamav==1){
	if($eCAPClamavEmergency==1){
		$jsOn="Loadjs('squid.urgency.php?eCAPClamavEmergency=yes');";
		if(!$users->AsSquidAdministrator){$jsOn="blur()";}
		$icon="disks-128-warn.png";
		$err[]=proxy_status_warning("{eCAPClamav_emergency_mode}",
				"{activedirectory_emergency_mode_explain}",$jsOn);
		
	}
}

if($ParanoidBlockerEmergency==1){
	$jsOn="Loadjs('squid.urgency.php?ParanoidBlockerEmergency=yes');";
	if(!$users->AsSquidAdministrator){$jsOn="blur()";}
	$icon="disks-128-warn.png";
	$err[]=proxy_status_warning("{paranoid_emergency_mode}",
			"{paranoid_emergency_mode_explain}",$jsOn);
	
}

if($SquidCacheFullHideWarn==0){
	if($COUNT_DE_CACHES>0){
		if($CACHES_AVG>85){
			$err[]=proxy_status_warning("{caches_are_full}",
					"{caches_are_full_explain}",$GoToCaches);
		}
		
	}
}
	
	
	if($COUNT_DE_CACHES>0){
		$COUNT_DE_MEMBERS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT"));
		if($COUNT_DE_MEMBERS>15){
			if($COUNT_DE_CACHES<20000){
				$undersized_proxy_caches_explain=$tpl->_ENGINE_parse_body("{undersized_proxy_caches_explain}");
				$COUNT_DE_CACHES_KB=$COUNT_DE_CACHES*1024;
				$COUNT_DE_CACHES_TEXT=FormatBytes($COUNT_DE_CACHES_KB);
				$undersized_proxy_caches_explain=str_replace("%S", $COUNT_DE_CACHES_TEXT, $undersized_proxy_caches_explain);
				$undersized_proxy_caches_explain=str_replace("%U", $COUNT_DE_MEMBERS, $undersized_proxy_caches_explain);
				if($SquidCacheLevel>0){
					$err[]=proxy_status_warning("{undersized_proxy_caches}",$undersized_proxy_caches_explain,$GoToCaches);
				}
			}
			
		}
	}
	
	
	if(!$users->AsSquidAdministrator){$err=array();}
	
	
	if(count($err)>0){
		$errT[]="<tr><td style='font-size:32px;color:#d32d2d;vertical-align:middle'>".count($err)." {issues}</td></tr>
		<tr><td colspan=2>&nbsp;</td></tr>	
				";
	}	
	
	
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$ActiveRequestsIpaddr=count($ActiveRequestsR["IPS"]);
	$ActiveRequestsMembers=count($ActiveRequestsR["USERS"]);
	
	$GotoParanoidMode="GotoParanoidMode()";
	$GotoParanoidMode_underline="underline";
	$GoToUfdb_underline="underline";
	
	if(!$users->AsDansGuardianAdministrator){
		$GotoParanoidMode="blur()";
		$GoToUfdb="blur()";
		$GotoParanoidMode_underline="none";
		$GoToUfdb_underline="none";
	}
	
	
	
	$UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
	if($UfdbEnableParanoidMode==1){
		$q=new mysql_squid_builder();
		$webfilters_paranoid=$q->COUNT_ROWS("webfilters_paranoid");
		if($webfilters_paranoid>0){
			$webfilters_paranoid_text="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:$GotoParanoidMode\"
		style='text-decoration:$GotoParanoidMode_underline'>{paranoid_mode}: ".FormatNumber($webfilters_paranoid)." {rules}</a></td>
		</tr>";
			
		}
			
			
	}
	
	
	

	
	if($EnableUfdbGuard==1){
		
		if($COUNT_DE_BLOCKED>0){
		$WebFiltering_blocked="
		<tr>
		<td style='font-size:20px'>
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:$GoToUfdb\"
		style='text-decoration:$GoToUfdb_underline'>{blocked_events}: ".FormatNumber($COUNT_DE_BLOCKED)."</a></td>
		</tr>";
		}
		
		
		
	}
	
	if(intval($ini->_params["SQUID"]["service_disabled"])==1){
		if($ini->_params["SQUID"]["running"]==0){
			$icon="disks-128-red.png";
		}
	}
	

	
	$mgr_client_list_TR="
		<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoMgrClientList()\"
			style='text-decoration:underline'>{active_clients}: ".FormatNumber($SNMP_WALK["CLIENTS_NUMBER"])."</a></td>
		</tr>";
	
	
	$active_resquests_js="Loadjs('squid.active.requests.php')";
	$active_resquests_underline="underline";
	
	if(!$users->AsSquidAdministrator){
		$active_resquests_js="blur()";
		$active_resquests_underline="none";
	}
	
	
	if($ActiveRequestsNumber>1){
		
		$active_resquests="
		<tr>
			<td style='font-size:20px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:$active_resquests_js\"
			style='text-decoration:underline'>{active_requests}: $ActiveRequestsNumber</a></td>
		</tr>";
		
		
	}

	

	if(intval($WATCHDOG_COUNT_EVENTS)>0){
		$important_events[]=status_important_event("$WATCHDOG_COUNT_EVENTS {important_events_48h}", null, "GotoWatchdog()");

	}
	
	$CACHES_AVG_COLOR="black";
	if($CACHES_AVG>85){
		if($SquidCacheFullHideWarn==0){$CACHES_AVG_COLOR="#d32d2d";}
	
	}
	
	
	if(count($important_events)>0){
	$important_events_text="<tr><td colspan=2>&nbsp;</td></tr>".@implode("\n", $important_events);
	}
	
	if($SQUIDEnable==0){
		$icon="disks-128-ok-grey.png";
	
	}
	
	if(count($INFOS)>0){
		$INFOS[]="<tr><td><br></td></tr>";
	}
	
	if($SquidCacheLevel==0){
		$SNMP_WALK["PERC_CACHE"]=0;
		$SNMP_WALK["STORED_OBJECTS"]=0;
	}
	
	
	
	$prec=intval($SNMP_WALK["PERC_CACHE"]);
	if($prec>0){
		$perc_cache="
		<tr>
			<td style='font-size:18px;vertical-align:middle'>{$prec}% {cache} ({currently})</td>
		</tr>";
	}
	

	$REQUESTS=intval($SNMP_WALK["REQUESTS"]);
	if($REQUESTS>0){
		$current_req="<tr>
		<td style='font-size:18px;vertical-align:middle'>". FormatNumber($REQUESTS)." {requests}</td>
		</tr>";
		
	}
	if($SNMP_WALK["CPU"]>0){
		$current_cpu_use="<tr>
		<td style='font-size:18px;vertical-align:middle'>{$SNMP_WALK["CPU"]}% {cpu_use}</td>
		</tr>";
	
	}
	
	$windowsUpdate=null;
	if($WindowsUpdateCaching==1){
		$cacheFile="/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state";
		$ARRAY=unserialize(@file_get_contents($cacheFile));
		if(intval($ARRAY["SIZEKB"])>4){
			$windowsUpdate="
			<tr>
			<td style='font-size:18px;vertical-align:middle'> 
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoWindowsUpdate()\"
			style='text-decoration:underline'>Windows Update:</a> ".FormatBytes($ARRAY["SIZEKB"])."</td>
			</tr>";
			
		}
		
	}
	
	$HTTrack=null;
	if($HTTrackInSquid==1){
		$HTTRackSize=$sock->GET_INFO("HTTRackSize");
		if($HTTRackSize>0){
			$HTTrack="
			<tr>
			<td style='font-size:18px;vertical-align:middle'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:GotoWebCopy()\"
			style='text-decoration:underline'>WebCopy:</a> ".FormatBytes($HTTRackSize/1024)."</td>
			</tr>";
			
		}
		
	}
	
	
	
	
	
	
	if($SNMP_WALK["STORED_OBJECTS"]>0){
		$current_stored_objects="<tr>
		<td style='font-size:18px;vertical-align:middle'>".FormatNumber($SNMP_WALK["STORED_OBJECTS"])." {stored_objects}</td>
		</tr>";
	
	}
	
	if($SquidCacheLevel>0){
		if($COUNT_DE_CACHES_TEXT<>null){
			$INFO_STORAGE_CACHE="
			<tr>
			<td style='font-size:20px'>
					<a href=\"javascript:blur();\"
					OnClick=\"$GoToCaches\"
					style='text-decoration:$GoToCaches_underline;color:$CACHES_AVG_COLOR'>
						{storage}: {$CACHES_AVG}%&nbsp;/&nbsp;{$COUNT_DE_CACHES_TEXT}</a>
					</td>
				</tr>";
		}
	}
	
	$CACHED_DETAILS_TOTAL=null;
	$NOT_CACHED_DETAILS_TOTAL=null;
	
	if($TOTALS_CACHED>0){
		$CACHED_DETAILS_TOTAL="
		<tr>
			<td style='font-size:18px'>
					<a href=\"javascript:blur();\"
					OnClick=\"$GoToCaches\"
					style='text-decoration:$GoToCaches_underline;color:black'>
						{cached}: ".FormatBytes($TOTALS_CACHED/1024)."</a>
			</td>
		</tr>
	";
	}
	
	
	if($TOTALS_NOT_CACHED>0){
		
		$NOT_CACHED_DETAILS_TOTAL="
		<tr>
			<td style='font-size:18px'>
					<a href=\"javascript:blur();\"
					OnClick=\"$GoToCaches\"
					style='text-decoration:$GoToCaches_underline;color:black'>
						{not_cached}: ".FormatBytes($TOTALS_NOT_CACHED/1024)."</a>
					</td>
				</tr>
		";
		
		
	}
	
	if(!$users->AsSquidAdministrator){
		$important_events_text=null;
		$errT=array();
		$windowsUpdate=null;
		$mgr_client_list_TR=null;
	}
	
	$icon=imgtootltip($icon,"position:right:{configure_your_proxy}","$LoadMainDashProxy");
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:128px' >
			$icon
			<div id='active-directory-dash-infos'>".active_directory_infos()."</div>	
		</td>
		<td>
			<table style='width:100%'>
			<tr>
				<td style='font-size:30px'>
				". texttooltip("{your_proxy}","{configure_your_proxy}","$LoadMainDashProxy")."
				&nbsp;<span style='font-size:22px'>[&nbsp;<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.infos.php');\"
				style='text-decoration:underline'>Infos.</a>&nbsp;]</span>		
						
				</td>
			</tr>
			<!-- perc_cache -->
			$perc_cache
			<!-- TOTALS_CACHED:$TOTALS_CACHED -->
			$CACHED_DETAILS_TOTAL
			<!-- TOTALS NOT CACHED:$TOTALS_NOT_CACHED -->
			$NOT_CACHED_DETAILS_TOTAL
			<!-- windowsUpdate -->
			$windowsUpdate
			<!-- HTTrack -->
			$HTTrack
			<!-- current_req -->
			$current_req
			<!-- SUM_FAMILYSITES_TEXT -->
			$SUM_FAMILYSITES_TEXT
		
			$active_resquests
			$mgr_client_list_TR
			$rqs
				".@implode("", $ntmlauthenticators_array)."
			
			
		<!-- WebFiltering_blocked -->
		$WebFiltering_blocked
		<!-- webfilters_paranoid -->
		$webfilters_paranoid_text	
		<!-- INFO_STORAGE_CACHE -->
		$INFO_STORAGE_CACHE
		
		
		
	<!-- current_stored_objects -->
			$current_stored_objects
	<!-- TITLE_REQUESTS -->
			$TITLE_REQUESTS
	<!-- TOP_GRAPHS -->
			". TOP_GRAPHS()."
			$CountDeServices
			".@implode("", $INFOS)."
			".@implode("", $errT)."
			".@implode("", $err)."
			$important_events_text
			
			</table>
		</td>
	</tr>
	</table>
	<script>
		LoadAjaxSilent('proxy_dashboard_counters','$page?proxy_dashboard_counters=yes');
	</script>
	";
	$html= $tpl->_ENGINE_parse_body($html);
	

	if(!is_dir("/usr/share/artica-postfix/ressources/interface-cache")){
			@mkdir("/usr/share/artica-postfix/ressources/interface-cache");
	}
	@file_put_contents($cachefile,$html);
	return $html;
	
			
}


function CHECK_SQUID_EXTERNAL_LDAP(){
	
	$sock=new sockets();
	
	$EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidExternalAuth")));
	$ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$CONNECTION=@ldap_connect($ldap_server,$ldap_port);
	
	if(!$CONNECTION){
		return "{failed_connect_ldap} $ldap_server:$ldap_port";
		
	}
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
	
	$userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$BIND=@ldap_bind($CONNECTION, $userdn, $ldap_password);
	
	if(!$BIND){
		$error=@ldap_err2str(@ldap_errno($CONNECTION));
		if (@ldap_get_option($CONNECTION, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error." $extended_error";}
		@ldap_close($CONNECTION);
		return $error;
	}
	
	@ldap_close($CONNECTION);
	
}


function TestLDAPAD_bind($server,$port,$user,$password){
	$ldap_connection=@ldap_connect($server,$port);
	
	if(!$ldap_connection){
		$error="[CONNECT] ldap://$server:$port";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>$error</strong><br>\n";}
		}
		@ldap_close($ldap_connection);
		return $error;
	
	}
	
	@ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($ldap_connection, LDAP_OPT_NETWORK_TIMEOUT, 1);
	
	
	$bind=ldap_bind($ldap_connection, "$user", $password);
	
	if(!$bind){
		$error="[BIND] ldap://$server:$port";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>$error</strong><br>\n";}
		}
		@ldap_close($ldap_connection);
		return $error;
	
	}
	
	if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>ldap://$server:$port [OK]</strong><br>\n";}
	return null;
	
}



function TestLDAPAD(){
	$sock=new sockets();
	$error=null;
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	
	if(!isset($array["LDAP_SERVER"])){
		if(isset($array["ADNETIPADDR"])){
			$array["LDAP_SERVER"]=$array["ADNETIPADDR"];
			
		}
		
		if(!isset($array["LDAP_SERVER"])){
			if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])){
				$array["LDAP_SERVER"]=$array["WINDOWS_SERVER_NETBIOSNAME"].".".$array["WINDOWS_DNS_SUFFIX"];
			}
		}
		
	}
	
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($GLOBALS["VERBOSE"]){echo "{$array["LDAP_SERVER"]} Port: {$array["LDAP_PORT"]}<br>\n";}
	$GotoAdConnection="GotoActiveDirectoryLDAPParams()";
	
	
	if(preg_match("#^(.+?)\/(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	if(preg_match("#^(.+?)\\\\(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	$LDAP_DN=$array["LDAP_DN"];
	$LDAP_PASSWORD=$array["LDAP_PASSWORD"];
	if($LDAP_DN==null){
		$LDAP_DN="{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}";
		$LDAP_PASSWORD=$array["WINDOWS_SERVER_PASS"];
	}
	
	if($GLOBALS["VERBOSE"]){echo "<p>TestLDAPAD_bind ->{$array["LDAP_SERVER"]}</p>\n";}
	$TestLDAPAD_bind=TestLDAPAD_bind($array["LDAP_SERVER"],$array["LDAP_PORT"],$LDAP_DN,$LDAP_PASSWORD);
	if($TestLDAPAD_bind<>null){return proxy_status_warning("{error_ad_ldap}",$TestLDAPAD_bind,$GotoAdConnection);}
	
	$SquidAddkerAlernates=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidAddkerAlernates"));
	if($GLOBALS["VERBOSE"]){echo "SquidAddkerAlernates: ".count($SquidAddkerAlernates)."<br>\n";}
	if(count($SquidAddkerAlernates)>0){
		
		while (list ($workgroup, $MainConfig) = each ($SquidAddkerAlernates)){
			$LDAP_DN2=$LDAP_DN;
			$LDAP_PASSWORD2=$LDAP_PASSWORD;
			if(isset($MainConfig["LDAP_SERVER"])){$LDAP_SERVER=$MainConfig["LDAP_SERVER"];}
			if(isset($MainConfig["LDAP_PORT"])){$LDAP_PORT=intval($MainConfig["LDAP_PORT"]);}
			if($LDAP_PORT==0){$LDAP_PORT=389;}
		
			if(isset($MainConfig["LDAP_DN"])){
				if($MainConfig["LDAP_DN"]<>null){$LDAP_DN2=$MainConfig["LDAP_DN"];}
			}
			
			if(isset($MainConfig["LDAP_PASSWORD"])){
				if($MainConfig["LDAP_PASSWORD"]<>null){$LDAP_PASSWORD2=$MainConfig["LDAP_PASSWORD"];}
					
			}
			if($GLOBALS["VERBOSE"]){echo "{$LDAP_SERVER} Port: {$LDAP_PORT} $LDAP_DN/$LDAP_PASSWORD<br>\n";}
			$TestLDAPAD_bind=TestLDAPAD_bind($LDAP_SERVER,$LDAP_PORT,$LDAP_DN,$LDAP_PASSWORD);
			if($TestLDAPAD_bind<>null){return proxy_status_warning("{error_ad_ldap}",$TestLDAPAD_bind,$GotoAdConnection);}
		}
		
	}
	
	
}

function IsKerconnected(){
$sock=new sockets();	
	$IsConnected=$sock->getFrameWork("squid.php?IsKerconnected=yes");
	if($IsConnected<>"TRUE"){return false;}
	return true;
}


function active_directory_infos(){
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){return;}
	$sock=new sockets();
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"
	OnClick=\"javascript:GoToActiveDirectory();\"		
	";
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==0){return null;}
	return "<center $curs style='margin-top:10px'><img src='img/windows-64-on.png'></center>";
	
	
	
}

function recheck_service($key){
	$sock=new sockets();
	if($key=="APP_INFLUXDB"){
		$sock->getFrameWork("influx.php?service-status=yes");
		$ini=new Bs_IniHandler();
		$ini->loadFile("/usr/share/artica-postfix/ressources/logs/APP_INFLUXDB.status");
		if($ini->_params[$key]["running"]==1){return true;}
		return false;
	}
	
}

function stats_appliance_count_clients(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT count(*) AS tcount FROM influxIPClients WHERE isServ=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	return intval($ligne["tcount"]);
	
}


function SERVICES_STATUS(){
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return;}
	

	
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	
	$err=array();
	$errT=array();
	$c=0;
	
	while (list ($key, $array) = each ($ini->_params) ){
		$service_name=$array["service_name"];
		$service_disabled=intval($array["service_disabled"]);
		if($service_disabled==0){continue;}
		$running=intval($array["running"]);
		$c++;
		if($running==0){
			if($key=="APP_INFLUXDB"){if(recheck_service("APP_INFLUXDB")){continue;}}
		
			
			
			$service_cmd=$array["service_cmd"];
			if($service_cmd<>null){
				$js="Loadjs('system.services.cmd.php?APPNAME={$array["service_name"]}&action=start&cmd=$service_cmd&appcode=$key')";
			}
			if($key=="SQUID"){$js="Loadjs('squid.start.progress.php');";}
			if($key=="APP_INFLUXDB"){$js="Loadjs('infludb.start.progress.php');";}
				
			
			
			$icon="disks-128-warn.png";
			$err[]=proxy_status_warning("{{$service_name}} {stopped}", "{{$service_name}} {stopped}", $js);
	
		}
	}
	
	if($c>0){
		$GoToServices="GoToServices()";
		$GoToServices_underline="underline";

		$CountDeServices="<tr>
		<td style='font-size:20px'><a href=\"javascript:blur();\"
		OnClick=\"javascript:$GoToServices;\" style='text-decoration:$GoToServices_underline'>{services}: $c</a></td>
		</tr>";
		
		return $CountDeServices;
	}
	
	
	
	return $err;
	
}

function INFO_WORKING_TASK(){
	$pgrep="/usr/bin/pgrep";
	$INFOS=null;
	$cmd="$pgrep -l -f bin/seeker 2>&1";
	if($GLOBALS["VERBOSE"]){echo "<H3>$cmd</H3>\n";}
	exec("$pgrep -l -f bin/seeker 2>&1",$results);
	while (list ($table,$MAIN) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "<H3>$MAIN</H3>\n";}
		if(preg_match("#pgrep#", $MAIN)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $MAIN)){continue;}
		$INFOS[]=status_info_event("{disk_benchmark_executed}","{during_this_task_load}", null);
		break;
		
		
	}
	
	if(is_array($INFOS)){return $INFOS;}
	
}





function proxy_services(){
	
	
	echo "<h1>Services</H1>";
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}