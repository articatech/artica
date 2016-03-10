<?php
if(!defined("alse")){define("alse", "alse");}

$GLOBALS["AS_ROOT"]=false;
if(!isset($GLOBALS["KAV4PROXY_NOSESSION"])){$GLOBALS["KAV4PROXY_NOSESSION"]=false;}
if(!isset($GLOBALS["DEBUG_MEM"])){$GLOBALS["DEBUG_MEM"]=false;}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__) .'/logs.inc');
if(function_exists("debug_mem")){debug_mem();}

if(function_exists("posix_getuid")){
	if(posix_getuid()<>0){
		if(!isset($GLOBALS["SESSION_ID"])){
				session_start();
				$GLOBALS["SESSION_ID"]=session_id();}
				if(function_exists("DefineSessions")){DefineSessions();}
				if(function_exists("CheckSessions")){CheckSessions();}
				if(!$GLOBALS["KAV4PROXY_NOSESSION"]){if(strlen($GLOBALS["SESSION_ID"])<2){$GLOBALS["SESSION_ID"]=session_id();} }
				
	}else{
		$GLOBALS["AS_ROOT"]=true;
	}
}

if(function_exists("debug_mem")){debug_mem();}


class usersMenus {
	public $AS_KIMSUFFI=false;
	public $TreePage=array("domains.edit.group.php"=>true,"samba.index.php"=>true);
	public $squid_kerb_auth_path=null;
	public $ArchStruct=0;
	public $menus;
	public $main_left_menus=array();
	public $menusright;
	public $WANPROXY=FALSE;
	public $toplinks;
	public $priv;
	public $MAIN_CONSOLE_PORT=9000;
	public $EnablePerUserRemoteAddressBook=0;
	public $dhcp_installed=false;
	public $AllowChangeDomains=false;
	public $AsSystemAdministrator=false;
	public $AsArticaMetaAdmin=false;
	public $AsDansGuardianAdministrator=false;
	public $AsSquidPersonalCategories=false;
	public $AsWebFilterRepository=false;	
	public $AsOrgStorageAdministrator=false;
	public $AllowXapianDownload=false;
	public $AllowAddGroup=false;
	public $AllowUserMaillog=false;
	public $AllowChangeKav=false;
	public $AllowChangeKas=false;
	public $AllowEditAsWbl=false;
	public $AllowEditOuSecurity=false;	
	public $RestrictNabToGroups=false;
	public $AsArticaAdministrator=false;
	public $AsSambaAdministrator=false;
	public $AsMailManAdministrator=false;
	public $AsPostfixAdministrator=false;
	public $AsSquidAdministrator=false;
	public $AsMailBoxAdministrator=false;
	public $AsDansGuardianGroupRule=false;
	public $AsWebStatisticsAdministrator=false;
	public $AsWebMaster=false;
	public $AsSystemWebMaster=false;
	public $AsDnsAdministrator=false;
	public $AsQuarantineAdministrator=false;
	public $AllowFetchMails=false;
	public $AllowSenderCanonical=false;
	public $AllowChangeUserPassword=false;
	public $AllowDansGuardianBanned=false;
	public $AllowChangeUserKas=false;
	public $AllowEditAliases=false;
	public $AllowEmailing=false;
	public $AllowOpenVPN=false;
	public $AsAnAdministratorGeneric=false;
	public $CGROUPS_INSTALLED=false;
	public $AllowAddUsers=false;
	public $AllowViewStatistics=false;
	public $AllowChangeAntiSpamSettings=false;
	public $AsOrgAdmin=false;
	public $AsProxyMonitor=false;
	public $AsInventoryAdmin=false;
	public $AseMailCampaignsAdmin=false;
	public $NETATALK_INSTALLED=false;
	public $SAMBA_APPLIANCE=false;
	public $CROSSROADS_VERSION="";
	public $MPM_ITK_MODULE=false;
	public $APACHE_MOD_BW=false;
	public $uid=null;
	public $groupid=-900;
	public $IptablesEnabled;
	public $KAVMILTER_ENABLED;
	public $lighttpd_installed;
	public $spamassassin_installed=false;
	public $spamassassin_version=0;
	public $fetchmail_configured=false;
	public $LOCAL_SID;
	public $EnableFetchmail=0;
	public $EnableFDMFetch=0;
	public $P3ScanEnabled=0;
	public $RoundCubeHTTPEngineEnabled=0;
	public $roundcube_intversion=0;
	public $roundcube_plugins=null;
	public $template_users_menus_h;
	public $template_user_addr;
	public $PasswdPolicy=array();
	public $disks_size=null;
	public $cpumhz=null;
	public $preload_installed=false;
	public $smbmount_installed=false;
	public $collectd_installed=false;
	public $EnableVirtualDomainsInMailBoxes=false;
	public $SIMPLE_GROUPEWARE_INSTALLED=false;
	public $MEMCACHED_INSTALLED=false;
	public $APP_ATOPENMAIL_INSTALLED=false;
	public $HAARP_INSTALLED=false;
	public $CNTLM_INSTALLED=false;
	public $CLAMD_DATABASE_PATH="";
	public $APP_ATOPENMAIL_VERSION="";
	public $COLLECTD_RRD=array();
	public $LoadModulesEnabledParsed=false;
	public $dar_installed=false;
	public $GNARWL_INSTALLED=false;
	public $AllowChangeMailBoxRules=false;
	public $cyrus_ldap_admin_password="";
	public $CLAMD_CONF_PATH="";
	public $PFLOGSUMM_INSTALLED=false;
	public $maillog_path;
	public $spamassassin_ipcountry=false;
	public $CUPS_INSTALLED=false;
	public $OBM2_INSTALLED=false;
	public $SARG_INSTALLED=false;
	public $OPENGOO_INSTALLED=false;
	public $APACHE_GROUPWARE_INSTALLED=false;
	public $POSTFIX_QUEUE=array();
	public $LinuxDistriCode=null;
	public $LinuxDistriFullName=null;
	public $APACHE_INSTALLED=false;
	public $cyrus_sync_installed=false;
	public $AllowManageOwnComputers=false;
	public $POLICYD_WEIGHT_PORT=12525;
	public $CRYPTSETUP_INSTALLED=false;
	public $ZABBIX_INSTALLED=true;
	public $HAMACHI_INSTALLED=true;
	public $phpldapadmin_installed=false;
	public $phpmyadmin_installed=false;
	public $HOSTAPD_INSTALLED=false;
	public $HOSTAPD_BINVER=0;
	public $POSTMULTI=false;
	public $MLDONKEY_INSTALLED=false;
	public $KAV4PROXY_LICENSE_ERROR=false;
	public $KAV4PROXY_LICENSE_ERROR_TEXT="";
	public $VIRTUALBOX_HOST=false;
	public $APP_VBOXADDINTION_INSTALLED=false;
	public $MHONARC_VERSION="";
	public $FETCHMAIL_STATUS="";
	public $kavmilter_installed=false;
	public $APACHE_MOD_AUTHNZ_LDAP=false;
	public $OverWriteRestrictedDomains=false;
	public $AsHotSpotManager=false;
	public $AsOrgDNSAdmin=false;
	public $ASDCHPAdmin=false;
	public $PACTESTER_INSTALLED=false;
	public $CONNTRACK_INSTALLED=false;
	public $APP_SQUID27_INSTALLED=false;
	public $APP_SQUIDSTREAM_INSTALLED=false;
	public $VSFTPD_INSTALLED=false;
	public $PHPFPM_INSTALLED=false;
	public $WIFIDOG_INSTALLED=false;
	/* This licence must be used Only with Artica-for postfix */
	public $ChartLicence="D1XFWGMEW9L.HSK5T4Q79KLYCK07EK";
	/*you cannot use it without any authorizations of maani.us.
	this licence is Copyright 2004-2007, maani.us
	For more information, see
	http://www.maani.us/xml_charts/index.php?menu=License
	*/
	public $STATS_APPLIANCE=null;
	public $WEBSTATS_APPLIANCE=null;
	public $ChangeAutoInterface=null;
	public $KASPERSKY_WEB_APPLIANCE=false;
	public $mysql_password='';
	public $mysql_admin='root';
	public $mysql_server='127.0.0.1';
	public $ARTICA_FILTER_QUEUE_PATH;
	public $ARTICA_FILTER_MAXSUBQUEUE;
	public $POSTFIX_MAILDROP_PATH;
	public $GLOBAL_CONFIG;
	public $AutomaticConfig=false;
	public $fetchmail_installed=false;
	public $cyrus_imapd_installed=false;
	public $cyrus_lmtp_path='/var/run/cyrus/socket/lmtp';
	public $kas_installed=false;
	public $aveserver_installed;
	public $rrdtool_installed;
	public $procmail_installed=false;	
	public $dnsmasq_installed=false;
	public $mysql_installed=false;
	public $mailgraph_installed=false;
	public $mailgraph_virus_database;
	public $mailgraph_postfix_database;
	public $queuegraph_installed=false;
	public $yorel_installed=false;
	public $LOCATE_AUTHLOG_PATH="";
	public $FRESHCLAM_INSTALLED=false;
	public $QUOTA_INSTALLED=false;
	public $roundcube_installed=false;
	public $awstats_installed=false;
	public $mysql_enabled=true;
	public $aveserver_pattern_date="00000000;0000";
	public $POSTIFX_STATUS="-1;0.0.0;";
	public $KAS_STATUS="-1;0.0.0;";
	public $CYRUS_STATUS="-1;0.0.0;";
	public $KAV_STATUS="-1;0.0.0;";
	public $FETHMAIL_STATUS="-1;0.0.0;";
	public $ARTICA_ROOT_PATH;
	public $ARTICA_VERSION;
	public $ARTICA_FILTER_STATUS=false;
	public $ARTICA_POLICY_STATUS=false;
	public $EMAILRELAY_STATUS=false;
	public $EMAILRELAY_INSTALLED=false;
	public $BOGOFILTER_INSTALLED=false;
	public $KAV_MILTER_INSTALLED=false;
	public $MAILMAN_INSTALLED=false;
	public $POSTFIX_INSTALLED=false;
	public $POSTFIX_VERSION="0.0.0";
	public $crossroads_installed=true;
	public $fetchmail_daemon_logs="";
	public $KAVMILTER_PID;
	public $hotwayd_installed;
	public $SQUID_INSTALLED=false;
	public $NGINX_INSTALLED=false;
	public $SQUID_BIN_PATH="";
	public $VMWARE_HOST=false;
	public $HAPROXY_INSTALLED=false;
	public $KAV4PROXY_INSTALLED=false;	
	public $KAV4PROXY_PID;
	public $KAV4PROXY_MEMORY=0;
	public $KAV4PROXY_PATTERN="";
	public $KAV4PROXY_VERSION="0.0.0";
	public $DANSGUARDIAN_INSTALLED=false;
	public $AMAVIS_INSTALLED=false;
	public $AMAVISD_VERSION=null;
	public $DANSGUARDIAN_VERSION=null;
	public $PUREFTP_INSTALLED=false;
	public $PUREFTP_USERS=false;
	public $MILTERGREYLIST_INSTALLED=false;
	public $MAILFROMD_INSTALLED=false;
	public $SQUID_PID;	
	public $fqdn;
	public $hostname;
	public $mysql_port;
	public $SMTP_LEVEL;
	public $ArticaFilterEnabled;
	public $AmavisFilterEnabled;
	public $MilterGreyListEnabled;
	public $EnableMilterSpyDaemon=0;
	public $SQLGreyEnabled;
	public $KasxFilterEnabled;
	public $OBM_INSTALLED;
	public $OBMEnabled;
	public $NTPD_INSTALLED;
	public $NTPDEnabled;
	public $MailFromdEnabled;
	public $IPTABLES_INSTALLED;
	public $ApacheArticaEnabled;
	public $SAMBA_INSTALLED;
	public $WINBINDD_INSTALLED;
	public $SAMBA_VERSION;
	public $GeoIPDat_Path;
	public $BadMysqlPassword=0;
	public $POSTFIX_LDAP_COMPLIANCE=false;
	public $MAILFROMD_SOCKET="";
	public $CLAMAV_INSTALLED=false;
	public $CLAMD_INSTALLED=false;
	public $CLAMSCAN_INSTALLED=false;
	
	public $ClamavMilterEnabled=0;
	public $CLAMAV_MILTER_SOCKET;
	public $CLAMAV_MILTER_INSTALLED=false;
	public $SpamAssMilterEnabled=0;
	public $SPAMASS_MILTER_INSTALLED=false;
	public $SPAMASS_MILTER_SOCKET="";
	public $roundcube_folder="";
	public $phpcgi;
	public $razor_installed=false;
	public $pyzor_installed=false;
	public $razor_config="";
	public $spfmilterEnabled=0;
	public $spfmilter_installed=false;
	public $MIMEDEFANG_INSTALLED=false;
	public $APP_CHILLI_INSTALLED=false;
	public $RDPPROXY_INSTALLED=false;
	public $netbiosname;
	public $MIMEDEFANG_SOCKET="";
	public $MimeDefangEnabled;
	public $GRAPHDEFANG_INSTALLED=false;
	public $MHONARC_INSTALLED=alse;
	public $SQUID_VERSION;
	public $SQUID_LDAP_AUTH;
	public $SQUID_NTLM_AUTH;
	public $SQUID_BIN_VERSION=0;
	public $stunnel4_installed=false;
	public $DkimFilterEnabled=0;
	public $DKIMFILTER_INSTALLED=false;
	public $DKIMFILTER_SOCKET="";
	public $blkid_installed=false;
	public $nmap_installed=false;
	public $usb_list=array();
	public $roundcube_mysql_sources;
	public $roundcube_web_folder;
	public $KAV4SAMBA_INSTALLED=false;
	public $KAV4SAMBA_VFS="";
	public $SAMBA_MYSQL_AUDIT=false;
	public $awstats_www_path="";
	public $SQUID_ICAP_ENABLED=false;
	public $LIGHTTPD_LDAP_AUTH=false;
	public $BIND9_INSTALLED=false;
	public $bindrrd_installed=false;
	public $fdm_installed=false;
	public $fdm_cache=false;
	public $p3scan_installed=false;
	public $syslogng_installed=false;
	public $AsDebianSystem=false;
	public $sTunnel4enabled=0;
	public $MailArchiverEnabled=0;
	public $roundcube_version="0.0.0";
	public $EnableMilterBogom=0;
	public $openldap_installed=false;
	public $EnableMysqlFeatures=1;
	public $MILTER_SPY_INSTALLED=false;
	public $EnableCollectdDaemon=0;
	public $EnableAmavisDaemon=0;
	public $imapsync_installed=false;
	public $mailsync_installed=false;
	public $ISOQLOG_INSTALLED=false;
	public $DOTCLEAR_INSTALLED=false;
	public $JCHECKMAIL_INSTALLED=false;
	public $zip_installed=false;
	public $JCheckMailEnabled=0;
	public $JCHECKMAIL_SOCKET;
	public $EnableAmavisBackup=0;
	public $OPENLDAP_DYNLIST=false;
	public $EnableRemoteAddressBook=0;
	public $SLPAD_LOCKED=false;
	public $ALTERMIME_INSTALLED=false;
	public $POMMO_INSTALLED=false;
	public $C_ICAP_INSTALLED=false;
	public $C_ICAP_DNSBL=false;
	public $MILTERGREYLIST_SOCKET="";
	public $OPENVPN_INSTALLED=false;
	public $spamassassin_conf_path="/etc/spamassassin/local.cf";
	public $EnableNonEncryptedLdapSession=1;
	public $SMARTMONTOOLS_INSTALLED=false;
	public $XAPIAN_PHP_INSTALLED=false;
	public $INSTANSEARCH_ENABLE=false;
	public $JOOMLA_INSTALLED=false;
	public $DSTAT_INSTALLED=false;
	public $SUGARCRM_INSTALLED=false;
	public $GNUPLOT_PNG=false;
	public $IOSTAT_INSTALLED=false;
	public $IOSTAT_ARRAY=array();
	public $PHP_BIN_PATH=null;	
	public $EXEC_NICE=null;
	public $spamassassin_bin_path=null;
	public $cyr_partition_default=null;
	public $cyr_config_directory=null;
	public $smbclient_installed=false;
	public $cyrus_murder_installed=false;
	public $FETCHMAIL_VERSION=null;
	public $cyrus_sync_client_path=null;
	public $ctl_mboxlist=null;
	public $CURL_PATH=null;
	public $NMAP_PATH=null;
	public $msmtp_installed=false;
	public $nmap_version=null;
	public $autofs_installed=false;
	public $APACHE_PORT=null;
	public $NFS_SERVER_INSTALLED=false;
	public $LVM_INSTALLED=false;
	public $MYSQL_NDB_MGMD_INSTALLED=false;
	public $CPU_NUMBER=0;
	public $LOAD_AVERAGE=0;
	public $winexe_installed=false;
	public $OCSI_INSTALLED=false;
	public $ASSP_INSTALLED=false;
	public $MEM_HIGER_1G=true;
	public $LMB_LUNDIMATIN_INSTALLED=false;
	public $APACHE_MODE_VHOSTS_LDAP=false;
	public $TCP_ARRAY=array();
	public $POWER_DNS_INSTALLED=false;
	public $mysqld_version=0;
	public $GLUSTER_INSTALLED=false;
	public $SLAPD_CONF_PATH;
	public $cyrus_squatter_exists=false;
	public $cyrus_ipurge_exists=false;
	public $SCANNED_ONLY_INSTALLED=false;
	public $GROUPOFFICE_INSTALLED=false;
	public $POSTFIX_MULTI=false;
	public $AsOrgPostfixAdministrator=false;
	public $AsMessagingOrg=false;
	public $ASSP_VERSION=0;
	public $MEM_TOTAL_INSTALLEE=0;
	public $KASPERSKY_SMTP_APPLIANCE=false;
	public $POSTFILTER_INSTALLED=false;
	public $VMWARE_TOOLS_INSTALLED=false;
	public $SQUID_NTLM_ENABLED=false;
	public $ZARAFA_INSTALLED=false;
	public $SQUIDGUARD_INSTALLED=false;
	public $SQUIDGUARD_BINVER=0;
	public $SQUIDGUARD_BIN_PATH="";
	public $RSYNC_INSTALLED=false;
	public $ZARAFA_APPLIANCE=false;
	public $mysqld_datadir;
	public $DRUPAL_INSTALLED=false;
	public $MAIL_DKIM_VERSION=null;
	public $HDPARM_INSTALLED=false;
	public $APACHE_MODE_WEBDAV=false;
	public $BACKUPPC_INSTALLED=false;
	public $KAV4FS_INSTALLED=false;
	public $OCS_LNX_AGENT_INSTALLED=false;
	public $POSTFIX_PCRE_COMPLIANCE=false;
	public $AsJoomlaWebMaster=false;
	public $APP_AUDITD_INSTALLED=false;
	public $DKFILTER_INSTALLED=false;
	public $OPENDKIM_INSTALLED=false;
	public $DB_RECOVER_INSTALLED=false;
	public $APP_UFDBGUARD_INSTALLED=false;
	public $ufdbgclient_path=null;
	public $roundcube_subscriptions_option=false;
	public $ADZAPPER_INSTALLED=false;
	public $KINIT_INSTALLED=false;
	public $DKIMPROXY_INSTALLED=false;
	public $MILTER_DKIM_INSTALLED=false;
	public $DROPBOX_INSTALLED=false;
	public $VIRTUALBOX_INSTALLED=false;
	public $AsVirtualBoxManager=false;
	public $TFTPD_INSTALLED=false;
	public $offlineimap_installed=false;
	public $THINSTATION_INSTALLED=false;
	public $OPENSSH_VER=0;
	public $cyrus_syncserver_installed=false;
	public $APP_SQUIDCLAMAV_INSTALLED=false;
	public $squidclamav_path="";
	public $PEAR_PACKAGES=array();
	public $RACOON_INSTALLED=false;
	public $IPSEC_INSTALLED=false;
	public $PPTPD_INSTALLED=false;
	public $PPTP_INSTALLED=false;
	public $SQUID_ARP_ACL_ENABLED=false;
	public $APT_MIRROR_INSTALLED=false;
	public $CLUEBRINGER_INSTALLED=false;
	public $qos_tools_installed=false;
	public $tc_version=0;
	public $LDAP_SYNCPROV=false;
	public $ARTICA_META_ENABLED=false;
	public $PIWIGO_INSTALLED=false;
	public $APP_SABNZBDPLUS_INSTALLED=false;
	public $POSTSCREEN_INSTALLED=false;
	public $KASPERSKY_UPDATE_UTILITY_INSTALLED=false;
	public $deduplication_installed=false;
	public $APP_VNSTAT_INSTALLED=false;
	public $IPSET_INSTALLED=false;
	public $EnableManageUsersTroughActiveDirectory=false;
	public $ActiveDirectoryCredentials;
	public $ASPELL_INSTALLED=false;
	public $MUNIN_CLIENT_INSTALLED=false;
	public $CURLFTPFS_INSTALLED=false;
	public $CIFS_INSTALLED=false;
	public $GREYHOLE_INSTALLED=false;
	public $SQUID_CACHMGR=false;
	public $APACHE_DIR_SITES_ENABLED='';
	public $APACHE_RUN_USER='';
	public $APACHE_MODULES_PATH='';
	public $DAVFS_INSTALLED=false;
	public $APACHE_MOD_QOS=false;
	public $ISCSI_INSTALLED=false;
	public $ISCSI_CLIENT_INSTALLED=false;
	public $ldapClass;
	public $LXC_INSTALLED=false;
	public $AS_VPS_CLIENT=false;
	public $AS_DEBIAN_FAMILY=false;
	public $VLAN_INSTALLED=false;
	
	public $LOSETUP_INSTALLED;
	public $SNORT_INSTALLED=false;
	public $APP_EYEOS_INSTALLED=false;
	public $EYEOS_VERSION;
	public $APACHE_MOD_SECURITY=false;
	public $APACHE_MOD_EVASIVE=false;
	public $OPENVPN_APPLIANCE=false;
	public $SIEVE_PORT=0;
	public $Z_PUSH_INSTALLED=false;
	public $APACHE_PROXY_MODE=false;
	public $APP_GREENSQL_INSTALLED=false;
	public $IPTABLES_ACCOUNTING_EXISTS=false;
	public $POWER_DNS_MYSQL=false;
	public $POWERADMIN_INSTALLED=false;
	public $APACHE_MOD_CACHE=false;
	public $APP_AMANDA_INSTALLED=false;
	public $SYNCTHING_INSTALLED=false;
	public $NicRoleArray=array();
	public $PDNSSEC_INSTALLED=false;
	public $NSCD_INSTALLED=false;
	public $DRUPAL7_INSTALLED=false;
	public $TOMCAT_VERSION=false;
	public $TOMCAT_INSTALLED=false;
	public $TOMCAT_JAVA="";
	public $TOMCAT_DIR="";
	public $APACHE_MOD_TOMCAT=false;
	public $OPENEMM_INSTALLED=false;
	public $MSKTUTIL_INSTALLED=false;
	public $OPENEMM_SENDMAIL_INSTALLED=false;
	public $OPENEMM_SENDMAIL_VERSION=false;
	public $PIWIK_INSTALLED=false;
	public $JOOMLA17_INSTALLED=false;
	public $APACHE_MOD_GEOIP=false;
	public $FROM_ISO=false;
	public $APACHE_MOD_PAGESPEED=false;
	public $WORDPRESS_INSTALLED=false;
	public $APACHE_MOD_INFO=false;
	public $APACHE_MOD_STATUS=false;
	public $APACHE_APPLIANCE=false;
	public $APACHE_MOD_SUEXEC=false;
	public $APACHE_MOD_FCGID=false;
	public $APACHE_MOD_LOGSSQL=false;
	public $MYCOSI_APPLIANCE=false;
	public $LOAD_BALANCE_APPLIANCE=false;
	public $ARPD_INSTALLED=false;
	public $YAFFAS_INSTALLED=false;
	public $SQUID_APPLIANCE=false;
	public $PROXYTINY_APPLIANCE=false;
	public $APP_KHSE_INSTALLED=false;
	public $MYSQLD_MULTI_INSTALLED=false;
	public $MONIT_INSTALLED=false;
	public $CONCRETE5_INSTALLED=false;
	public $ECAPAV_INSTALLED=false;
	public $UPDATE_UTILITYV2_INSTALLED=false;
	public $IPBAN_INSTALLED=false;
	public $SQUID_PURGE_INSTALLED=false;
	public $squid_ext_session_acl=null;
	public $ZARAFA_WEBAPP_INSTALLED=false;
	public $ZARAFA_WEBACCESS_MOBILE_INSTALLED=false;
	public $EJABBERD_INSTALLED=false;
	public $PYMSNT_INSTALLED=false;
	public $PYICQT_INSTALLED=false;
	public $SSHFS_INSTALLED=false;
	public $BTRFS_INSTALLED=false;
	public $APP_ARKEIA_INSTALLED=false;
	public $SAMBA_CLUSTER_SUPPORT=false;
	public $HAPRROXY_APPLIANCE=false;
	public $SMTP_APPLIANCE=false;
	public $FULL_APPLIANCE=false;
	public $FROM_SETUP=false;
	public $KLMS_WEB_INSTALLED=false;
	public $KLMS_INSTALLED=false;
	public $HTTRACK_INSTALLED=false;
	public $INNODB_FILE_PER_TABLE_INSTALL=false;
	public $LDAP_BACKMETA=false;
	public $LDAP_BACKLDAP=false;
	public $LDAP_RWM=false;
	public $LDAP_BACKRELAY=false;
	public $CYRUS_APPLIANCE=false;
	public $VPS_OPENVZ=false;
	public $ZARAFA_SEARCH_INSTALLED=false;
	public $OWNCLOUD_INSTALLED=false;
	public $LIGHT_INSTALL=false;
	public $CORP_LICENSE=false;
	public $AsOwnMailBoxBackup=false;
	public $ARTICADB_INSTALLED=false;
	public $SURICATA_INSTALLED=false;
	public $ZARAFA_INDEXER_INSTALLED=false;
	public $APP_TOMCAT6_INSTALLED=false;
	public $APP_JAVA_JDK_INSTALLED=false;
	public $WSGATE_INSTALLED=false;
	public $URLSNARF_INSTALLED=false;
	public $ETTERCAP_INSTALLED=false;	
	public $PERL_AUTHNTLM=false;
	public $APACHE_MOD_PYTHON=false;
	public $APP_PYAUTHENNTLM=false;	
	public $APP_ZARAFADB_INSTALLED=false;
	public $FREERADIUS_INSTALLED=false;
	public $APP_SQUIDDB_INSTALLED=false;
	public $APACHE_MOD_SUPHP=false;
	public $APP_FILEZ_WEB=false;
	public $NTPDATE=false;
	public $APP_FTP_PROXY=false;
	public $UCARP_INSTALLED=false;
	public $SQUID_REVERSE_APPLIANCE=false;
	public $ISCSID_INSTALLED=false;
	public $VDESWITCH_INSTALLED=false;
	public $GATEWAY_APPLIANCE=false;
	public $SHOREWALL_INSTALLED=false;
	public $APP_EBTABLES_INSTALLED=false;
	public $APACHE_MODE_BW=false;
	public $WORDPRESS_APPLIANCE=false;
	public $l7FILTER_INSTALLED=false;
	public $WEBSECURIZE=false;
	public $BAMSIGHT=false;
	public $LANWANSAT=false;
	public $SENSORS_INSTALLED=false;
	public $SS5_INSTALLED=false;
	public $HYPERCACHE_STOREID=false;
	public $FIREHOL=false;
	public $XEN_HOST=false;
	public $HYPERV_HOST=false;
	public $INFLUXDB=false;
	private function CheckIndexDefaults($_GLOBAL){
		return $_GLOBAL;
		foreach($this as $key => $value) {
			if(!isset($_GLOBAL[$key])){$_GLOBAL[$key]=$value;}
		}
		
		return $_GLOBAL;
	}
	

	
	
	function usersMenus($priv=null,$nobuild=0,$hostname=null){
			$noldap=false;
			$mypage=$this->CurrentPageName();
			if($mypage=="exec.initslapd.php"){$noldap=true;}
		
			if(!$noldap){
				if(!class_exists("clladp")){include_once(dirname(__FILE__)."/class.ldap.inc");}
				$this->ldapClass=new clladp();
			}
			
			$_GLOBAL=array();
			if($GLOBALS["VERBOSE"]){
					$trace=debug_backtrace();
					
					if(isset($trace[1]["file"])){
						$filename=basename($trace[1]["file"]);
					}
					if(isset($trace[1]["function"])){
						$function=" - function {$trace[1]["function"]}()";
					}
					if(isset($trace[1]["line"])){
						$function=$function." line {$trace[1]["line"]}";
					}
					
					
					if(isset($trace[1])){
						$called=" called by $filename $function";			
						//echo "Instanciate userMenus() $called\n";
					}
				}		
		
			if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
			
			include_once(dirname(__FILE__)."/class.sockets.inc");
			$settings=dirname(__FILE__).'/settings.inc';
			@include($settings);
			$this->GLOBAL_CONFIG=$_GLOBAL;
			
			
			
			if(!is_array($this->GLOBAL_CONFIG)){
				if(!$GLOBALS["AS_ROOT"]){echo "<p class=text-error>";}
				echo "GLOBAL_CONFIG Not an array for settings or unable stat $settings in ".basename(__FILE__)." function:".__FUNCTION__." line:".__LINE__."\n";
				if(!$GLOBALS["AS_ROOT"]){echo "</p>";}
				return false;
			}
			
			$this->cyrus_ldap_admin_password=$_GLOBAL["cyrus_ldap_admin_password"];
			if(isset($_GLOBAL["AutomaticConfig"])){$this->AutomaticConfig=$_GLOBAL["AutomaticConfig"];}
			$this->MAIN_CONSOLE_PORT=$_GLOBAL["MAIN_CONSOLE_PORT"];
			$this->imapsync_installed=$_GLOBAL["imapsync_installed"];
			$this->fetchmail_installed=$_GLOBAL["fetchmail_installed"];
			$this->cyrus_imapd_installed=$_GLOBAL["cyrus_imapd_installed"];
			$this->LOCATE_AUTHLOG_PATH=$_GLOBAL["LOCATE_AUTHLOG_PATH"];
			if(isset($_GLOBAL["cyrus_lmtp_path"])){$this->cyrus_lmtp_path=$_GLOBAL["cyrus_lmtp_path"];}
			if(isset($_GLOBAL["cyr_partition_default"])){$this->cyr_partition_default=$_GLOBAL["cyr_partition_default"];}
			if(isset($_GLOBAL["cyr_config_directory"])){$this->cyr_config_directory=$_GLOBAL["cyr_config_directory"];}
			$this->kas_installed=$_GLOBAL["kas_installed"];
			$this->aveserver_installed=$_GLOBAL["aveserver_installed"];
			$this->rrdtool_installed=$_GLOBAL["rrdtool_installed"];
			$this->procmail_installed=$_GLOBAL["procmail_installed"];
			$this->mysql_installed=$_GLOBAL["mysql_installed"];
			$this->mailgraph_installed=$_GLOBAL["mailgraph_installed"];
			$this->mailgraph_virus_database=$_GLOBAL["mailgraph_virus_database"];
			$this->mailgraph_postfix_database=$_GLOBAL["mailgraph_postfix_database"];
			$this->queuegraph_installed=$_GLOBAL["queuegraph_installed"];
			$this->yorel_installed=$_GLOBAL["yorel_installed"];
			$this->roundcube_installed=$_GLOBAL["roundcube_installed"];
			$this->ChangeAutoInterface=$_GLOBAL["ChangeAutoInterface"];
			$this->aveserver_pattern_date=$_GLOBAL["aveserver_pattern_date"];
			if(isset($_GLOBAL["POSTFIX_STATUS"])){$this->POSTIFX_STATUS=$_GLOBAL["POSTFIX_STATUS"];}
			if(isset($_GLOBAL["KAS_STATUS"])){$this->KAS_STATUS=$_GLOBAL["KAS_STATUS"];}
			if(isset($_GLOBAL["CYRUS_STATUS"])){$this->CYRUS_STATUS=$_GLOBAL["CYRUS_STATUS"];}
			if(isset($_GLOBAL["KAV_STATUS"])){$this->KAV_STATUS=$_GLOBAL["KAV_STATUS"];}
			if(isset($_GLOBAL["FETCHMAIL_STATUS"])){$this->FETCHMAIL_STATUS=$_GLOBAL["FETCHMAIL_STATUS"];}
			if(isset($_GLOBAL["awstats_installed"])){$this->awstats_installed=$_GLOBAL["awstats_installed"];;}
			if(isset($_GLOBAL["BOGOFILTER_INSTALLED"])){$this->BOGOFILTER_INSTALLED=$_GLOBAL["BOGOFILTER_INSTALLED"];;}
			if(isset($_GLOBAL["MAILMAN_INSTALLED"])){$this->MAILMAN_INSTALLED=$_GLOBAL["MAILMAN_INSTALLED"];;}
			if(isset($_GLOBAL["kavmilter_installed"])){$this->KAV_MILTER_INSTALLED=$_GLOBAL["kavmilter_installed"];;}
			if(isset($_GLOBAL["fetchmail_daemon_logs"])){$this->fetchmail_daemon_logs=$_GLOBAL["fetchmail_daemon_logs"];;}
			if(isset($_GLOBAL["ARTICA_ROOT_PATH"])){$this->ARTICA_ROOT_PATH=$_GLOBAL["ARTICA_ROOT_PATH"];;}
			if(isset($_GLOBAL["ARTICA_FILTER_STATUS"])){$this->ARTICA_FILTER_STATUS=$_GLOBAL["ARTICA_FILTER_STATUS"];;}
			if(isset($_GLOBAL["ARTICA_POLICY_STATUS"])){$this->ARTICA_POLICY_STATUS=$_GLOBAL["ARTICA_POLICY_STATUS"];;}
			if(isset($_GLOBAL["ARTICA_VERSION"])){$this->ARTICA_VERSION=$_GLOBAL["ARTICA_VERSION"];;}
			if(isset($_GLOBAL["hotwayd_installed"])){$this->hotwayd_installed=$_GLOBAL["hotwayd_installed"];;}
			if(isset($_GLOBAL["POSTFIX_INSTALLED"])){$this->POSTFIX_INSTALLED=$_GLOBAL["POSTFIX_INSTALLED"];;}
			if(isset($_GLOBAL["EMAILRELAY_STATUS"])){$this->EMAILRELAY_STATUS=$_GLOBAL["EMAILRELAY_STATUS"];;}
			if(isset($_GLOBAL["EMAILRELAY_INSTALLED"])){$this->EMAILRELAY_INSTALLED=$_GLOBAL["EMAILRELAY_INSTALLED"];;}
			if(isset($_GLOBAL["KAVMILTER_PID"])){$this->KAVMILTER_PID=$_GLOBAL["KAVMILTER_PID"];;}
			if(isset($_GLOBAL["SQUID_INSTALLED"])){$this->SQUID_INSTALLED=$_GLOBAL["SQUID_INSTALLED"];;}
			if(isset($_GLOBAL["NGINX_INSTALLED"])){$this->NGINX_INSTALLED=$_GLOBAL["NGINX_INSTALLED"];;}
			if(isset($_GLOBAL["KAV4PROXY_INSTALLED"])){$this->KAV4PROXY_INSTALLED=$_GLOBAL["KAV4PROXY_INSTALLED"];;}
			
			if(is_file("/etc/artica-postfix/settings/Daemons/Kav4ProxyInstalled")){
				if(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/Kav4ProxyInstalled"))==1){
					$this->KAV4PROXY_INSTALLED=true;
				}
			}
			
			
			if(isset($_GLOBAL["SQUID_PID"])){$this->SQUID_PID=$_GLOBAL["SQUID_PID"];;}
			if(isset($_GLOBAL["SQUID_NTLM_AUTH"])){$this->SQUID_NTLM_AUTH=$_GLOBAL["SQUID_NTLM_AUTH"];;}
			if(isset($_GLOBAL["SQUID_BIN_VERSION"])){$this->SQUID_BIN_VERSION=$_GLOBAL["SQUID_BIN_VERSION"];;}
			if(isset($_GLOBAL["KAV4PROXY_MEMORY"])){$this->KAV4PROXY_MEMORY=$_GLOBAL["KAV4PROXY_MEMORY"];;}
			if(isset($_GLOBAL["KAV4PROXY_PATTERN"])){$this->KAV4PROXY_PATTERN=$_GLOBAL["KAV4PROXY_PATTERN"];;}
			if(isset($_GLOBAL["KAV4PROXY_VERSION"])){$this->KAV4PROXY_VERSION=$_GLOBAL["KAV4PROXY_VERSION"];;}
			if(isset($_GLOBAL["SQUID_CACHMGR"])){$this->SQUID_CACHMGR=$_GLOBAL["SQUID_CACHMGR"];;}
			if(isset($_GLOBAL["APACHE_DIR_SITES_ENABLED"])){$this->APACHE_DIR_SITES_ENABLED=$_GLOBAL["APACHE_DIR_SITES_ENABLED"];;}
			
			
			if(isset($_GLOBAL["APACHE_MODULES_PATH"])){$this->APACHE_MODULES_PATH=$_GLOBAL["APACHE_MODULES_PATH"];;}
			if(is_file("/etc/artica-postfix/settings/Daemons/ApacheModulesPath")){$this->APACHE_MODULES_PATH=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApacheModulesPath"));}
			
			if(isset($_GLOBAL["MYCOSI_APPLIANCE"])){$this->MYCOSI_APPLIANCE=$_GLOBAL["MYCOSI_APPLIANCE"];;}
			if(isset($_GLOBAL["ARPD_INSTALLED"])){$this->ARPD_INSTALLED=$_GLOBAL["ARPD_INSTALLED"];}
			if(isset($_GLOBAL["AS_KIMSUFFI"])){$this->AS_KIMSUFFI=$_GLOBAL["AS_KIMSUFFI"];}
			if(isset($_GLOBAL["CROSSROADS_VERSION"])){$this->CROSSROADS_VERSION=$_GLOBAL["CROSSROADS_VERSION"];}
			if(isset($_GLOBAL["APACHE_MOD_BW"])){$this->APACHE_MOD_BW=$_GLOBAL["APACHE_MOD_BW"];}
			if(isset($_GLOBAL["MYSQLD_MULTI_INSTALLED"])){$this->MYSQLD_MULTI_INSTALLED=$_GLOBAL["MYSQLD_MULTI_INSTALLED"];}
			if(isset($_GLOBAL["ECAPAV_INSTALLED"])){$this->ECAPAV_INSTALLED=$_GLOBAL["ECAPAV_INSTALLED"];}
			if(isset($_GLOBAL["FRESHCLAM_INSTALLED"])){$this->FRESHCLAM_INSTALLED=$_GLOBAL["FRESHCLAM_INSTALLED"];}
			if(isset($_GLOBAL["UPDATE_UTILITYV2_INSTALLED"])){$this->UPDATE_UTILITYV2_INSTALLED=$_GLOBAL["UPDATE_UTILITYV2_INSTALLED"];}
			if(isset($_GLOBAL["WEBSTATS_APPLIANCE"])){$this->WEBSTATS_APPLIANCE=$_GLOBAL["WEBSTATS_APPLIANCE"];;}
			if(isset($_GLOBAL["APACHE_RUN_USER"])){$this->APACHE_RUN_USER=$_GLOBAL["APACHE_RUN_USER"];;}
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];;}
			if(isset($_GLOBAL["DAVFS_INSTALLED"])){$this->DAVFS_INSTALLED=$_GLOBAL["DAVFS_INSTALLED"];;}
			if(isset($_GLOBAL["APACHE_MOD_QOS"])){$this->APACHE_MOD_QOS=$_GLOBAL["APACHE_MOD_QOS"];;}
			if(isset($_GLOBAL["SQUID_PURGE_INSTALLED"])){$this->SQUID_PURGE_INSTALLED=$_GLOBAL["SQUID_PURGE_INSTALLED"];;}
			if(isset($_GLOBAL["squid_ext_session_acl"])){$this->squid_ext_session_acl=$_GLOBAL["squid_ext_session_acl"];;}
			if(isset($_GLOBAL["ZARAFA_WEBAPP_INSTALLED"])){$this->ZARAFA_WEBAPP_INSTALLED=$_GLOBAL["ZARAFA_WEBAPP_INSTALLED"];;}
			if(isset($_GLOBAL["ZARAFA_WEBACCESS_MOBILE_INSTALLED"])){$this->ZARAFA_WEBACCESS_MOBILE_INSTALLED=$_GLOBAL["ZARAFA_WEBACCESS_MOBILE_INSTALLED"];;}
			if(isset($_GLOBAL["EJABBERD_INSTALLED"])){$this->EJABBERD_INSTALLED=$_GLOBAL["EJABBERD_INSTALLED"];;}
			if(isset($_GLOBAL["PYMSNT_INSTALLED"])){$this->PYMSNT_INSTALLED=$_GLOBAL["PYMSNT_INSTALLED"];;}
			if(isset($_GLOBAL["PYICQT_INSTALLED"])){$this->PYICQT_INSTALLED=$_GLOBAL["PYICQT_INSTALLED"];;}
			if(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HaProxyInstalled"))==1){$this->HAPROXY_INSTALLED=true;}else{$this->HAPROXY_INSTALLED=false;}
			if(isset($_GLOBAL["SSHFS_INSTALLED"])){$this->SSHFS_INSTALLED=$_GLOBAL["SSHFS_INSTALLED"];;}
			if(isset($_GLOBAL["BTRFS_INSTALLED"])){$this->BTRFS_INSTALLED=$_GLOBAL["BTRFS_INSTALLED"];;}
			if(isset($_GLOBAL["APP_ARKEIA_INSTALLED"])){$this->APP_ARKEIA_INSTALLED=$_GLOBAL["APP_ARKEIA_INSTALLED"];;}
			if(isset($_GLOBAL["SAMBA_CLUSTER_SUPPORT"])){$this->SAMBA_CLUSTER_SUPPORT=$_GLOBAL["SAMBA_CLUSTER_SUPPORT"];}
			if(isset($_GLOBAL["HAPRROXY_APPLIANCE"])){$this->HAPRROXY_APPLIANCE=$_GLOBAL["HAPRROXY_APPLIANCE"];}
			if(isset($_GLOBAL["FULL_APPLIANCE"])){$this->FULL_APPLIANCE=$_GLOBAL["FULL_APPLIANCE"];}
			if(isset($_GLOBAL["FROM_SETUP"])){$this->FROM_SETUP=$_GLOBAL["FROM_SETUP"];}
			if(isset($_GLOBAL["KLMS_WEB_INSTALLED"])){$this->KLMS_WEB_INSTALLED=$_GLOBAL["KLMS_WEB_INSTALLED"];}
			if(isset($_GLOBAL["KLMS_INSTALLED"])){$this->KLMS_INSTALLED=$_GLOBAL["KLMS_INSTALLED"];}
			if(isset($_GLOBAL["HTTRACK_INSTALLED"])){$this->HTTRACK_INSTALLED=$_GLOBAL["HTTRACK_INSTALLED"];}
			if(isset($_GLOBAL["INNODB_FILE_PER_TABLE_INSTALL"])){$this->INNODB_FILE_PER_TABLE_INSTALL=$_GLOBAL["INNODB_FILE_PER_TABLE_INSTALL"];}
			$this->OPENVPN_APPLIANCE=$_GLOBAL["OPENVPN_APPLIANCE"];
			if(isset($_GLOBAL["CYRUS_APPLIANCE"])){$this->CYRUS_APPLIANCE=$_GLOBAL["CYRUS_APPLIANCE"];}
			if(isset($_GLOBAL["VPS_OPENVZ"])){$this->VPS_OPENVZ=$_GLOBAL["VPS_OPENVZ"];}
			if(isset($_GLOBAL["ZARAFA_SEARCH_INSTALLED"])){$this->ZARAFA_SEARCH_INSTALLED=$_GLOBAL["ZARAFA_SEARCH_INSTALLED"];}
			if(isset($_GLOBAL["ZARAFA_INDEXER_INSTALLED"])){$this->ZARAFA_INDEXER_INSTALLED=$_GLOBAL["ZARAFA_INDEXER_INSTALLED"];}
			if(isset($_GLOBAL["APP_TOMCAT6_INSTALLED"])){$this->APP_TOMCAT6_INSTALLED=$_GLOBAL["APP_TOMCAT6_INSTALLED"];}
			if(isset($_GLOBAL["OWNCLOUD_INSTALLED"])){$this->OWNCLOUD_INSTALLED=$_GLOBAL["OWNCLOUD_INSTALLED"];}
			if(isset($_GLOBAL["LIGHT_INSTALL"])){$this->LIGHT_INSTALL=$_GLOBAL["LIGHT_INSTALL"];}
			if(isset($_GLOBAL["SURICATA_INSTALLED"])){$this->SURICATA_INSTALLED=$_GLOBAL["SURICATA_INSTALLED"];}
			if(isset($_GLOBAL["WSGATE_INSTALLED"])){$this->WSGATE_INSTALLED=$_GLOBAL["WSGATE_INSTALLED"];}
			if(isset($_GLOBAL["URLSNARF_INSTALLED"])){$this->URLSNARF_INSTALLED=$_GLOBAL["URLSNARF_INSTALLED"];}
			if(isset($_GLOBAL["ETTERCAP_INSTALLED"])){$this->ETTERCAP_INSTALLED=$_GLOBAL["ETTERCAP_INSTALLED"];}
			if(isset($_GLOBAL["PERL_AUTHNTLM"])){$this->PERL_AUTHNTLM=$_GLOBAL["PERL_AUTHNTLM"];}
			
			if(isset($_GLOBAL["APACHE_MOD_PYTHON"])){$this->APACHE_MOD_PYTHON=$_GLOBAL["APACHE_MOD_PYTHON"];}
			if(isset($_GLOBAL["APP_PYAUTHENNTLM"])){$this->APP_PYAUTHENNTLM=$_GLOBAL["APP_PYAUTHENNTLM"];}
			if(isset($_GLOBAL["APP_ZARAFADB_INSTALLED"])){$this->APP_ZARAFADB_INSTALLED=$_GLOBAL["APP_ZARAFADB_INSTALLED"];}
			if(isset($_GLOBAL["APP_SQUIDDB_INSTALLED"])){$this->APP_SQUIDDB_INSTALLED=$_GLOBAL["APP_SQUIDDB_INSTALLED"];}
			if(isset($_GLOBAL["NTPDATE"])){$this->NTPDATE=$_GLOBAL["NTPDATE"];}
			if(isset($_GLOBAL["APP_FILEZ_WEB"])){$this->APP_FILEZ_WEB=$_GLOBAL["APP_FILEZ_WEB"];}
			if(isset($_GLOBAL["APACHE_MOD_SUPHP"])){$this->APACHE_MOD_SUPHP=$_GLOBAL["APACHE_MOD_SUPHP"];}
			if(isset($_GLOBAL["APP_FTP_PROXY"])){$this->APP_FTP_PROXY=$_GLOBAL["APP_FTP_PROXY"];}
			if(isset($_GLOBAL["PHPFPM_INSTALLED"])){$this->PHPFPM_INSTALLED=$_GLOBAL["PHPFPM_INSTALLED"];}
			if(isset($_GLOBAL["SQUID_REVERSE_APPLIANCE"])){$this->SQUID_REVERSE_APPLIANCE=$_GLOBAL["SQUID_REVERSE_APPLIANCE"];}
			if(isset($_GLOBAL["CNTLM_INSTALLED"])){$this->CNTLM_INSTALLED=$_GLOBAL["CNTLM_INSTALLED"];}
			if(isset($_GLOBAL["ISCSID_INSTALLED"])){$this->ISCSID_INSTALLED=$_GLOBAL["ISCSID_INSTALLED"];}
			if(isset($_GLOBAL["VDESWITCH_INSTALLED"])){$this->VDESWITCH_INSTALLED=$_GLOBAL["VDESWITCH_INSTALLED"];}
			if(isset($_GLOBAL["APP_EBTABLES_INSTALLED"])){$this->APP_EBTABLES_INSTALLED=$_GLOBAL["APP_EBTABLES_INSTALLED"];}
			if(isset($_GLOBAL["APP_SQUID27_INSTALLED"])){$this->APP_SQUID27_INSTALLED=$_GLOBAL["APP_SQUID27_INSTALLED"];}
			if(isset($_GLOBAL["APP_SQUIDSTREAM_INSTALLED"])){$this->APP_SQUIDSTREAM_INSTALLED=$_GLOBAL["APP_SQUIDSTREAM_INSTALLED"];}
			if(isset($_GLOBAL["APACHE_MODE_BW"])){$this->APACHE_MODE_BW=$_GLOBAL["APACHE_MODE_BW"];}
			if(isset($_GLOBAL["VSFTPD_INSTALLED"])){$this->VSFTPD_INSTALLED=$_GLOBAL["VSFTPD_INSTALLED"];}
			if(isset($_GLOBAL["WORDPRESS_APPLIANCE"])){$this->WORDPRESS_APPLIANCE=$_GLOBAL["WORDPRESS_APPLIANCE"];}
			if(isset($_GLOBAL["WIFIDOG_INSTALLED"])){$this->WIFIDOG_INSTALLED=$_GLOBAL["WIFIDOG_INSTALLED"];}
			if(isset($_GLOBAL["SYNCTHING_INSTALLED"])){$this->SYNCTHING_INSTALLED=$_GLOBAL["SYNCTHING_INSTALLED"];}
			if(isset($_GLOBAL["SENSORS_INSTALLED"])){$this->SENSORS_INSTALLED=$_GLOBAL["SENSORS_INSTALLED"];}
			if(isset($_GLOBAL["SS5_INSTALLED"])){$this->SS5_INSTALLED=$_GLOBAL["SS5_INSTALLED"];}
			if(isset($_GLOBAL["HYPERCACHE_STOREID"])){$this->HYPERCACHE_STOREID=$_GLOBAL["HYPERCACHE_STOREID"];}
			if(isset($_GLOBAL["INFLUXDB"])){$this->INFLUXDB=$_GLOBAL["INFLUXDB"];}
			if(isset($_GLOBAL["STATS_APPLIANCE"])){$this->STATS_APPLIANCE=$_GLOBAL["STATS_APPLIANCE"];}
			if(isset($_GLOBAL["WANPROXY"])){$this->WANPROXY=$_GLOBAL["WANPROXY"];}
			
			
			
			
			//Are you sure to do that !!!, breaking the license turn you to a pirate...
			if(isset($_GLOBAL["CORP_LICENSE"])){$this->CORP_LICENSE=$_GLOBAL["CORP_LICENSE"];}
	
			$this->hostname=$_GLOBAL["fixed_hostname"];
			$this->fqdn=$_GLOBAL["fqdn_hostname"];
			//if(isset($_GLOBAL["ARTICADB_INSTALLED"])){$this->ARTICADB_INSTALLED=$_GLOBAL["ARTICADB_INSTALLED"];;}
			if(isset($_GLOBAL["DANSGUARDIAN_INSTALLED"])){$this->DANSGUARDIAN_INSTALLED=$_GLOBAL["DANSGUARDIAN_INSTALLED"];;}
			if(isset($_GLOBAL["KAV4PROXY_PID"])){$this->KAV4PROXY_PID=$_GLOBAL["KAV4PROXY_PID"];;}
			if(isset($_GLOBAL["dnsmasq_installed"])){$this->dnsmasq_installed=$_GLOBAL["dnsmasq_installed"];;}
			if(isset($_GLOBAL["PUREFTP_INSTALLED"])){$this->PUREFTP_INSTALLED=$_GLOBAL["PUREFTP_INSTALLED"];;}
			if(isset($_SESSION["uid"])){$this->uid=$_SESSION["uid"];}
			if(isset($_SESSION["groupid"])){$this->groupid=$_SESSION["groupid"];}
			if(isset($_GLOBAL["crossroads_installed"])){$this->crossroads_installed=$_GLOBAL["crossroads_installed"];}
			if(isset($_GLOBAL["AMAVIS_INSTALLED"])){$this->AMAVIS_INSTALLED=$_GLOBAL["AMAVIS_INSTALLED"];}
			if(isset($_GLOBAL["mysql_port"])){$this->mysql_port=$_GLOBAL["mysql_port"];}
			if(isset($_GLOBAL["MILTERGREYLIST_INSTALLED"])){$this->MILTERGREYLIST_INSTALLED=$_GLOBAL["MILTERGREYLIST_INSTALLED"];}
			$this->mysql_password=$_GLOBAL["mysql_password"];
			$this->mysql_admin=$_GLOBAL["mysql_admin"];	
			$this->mysql_server=$_GLOBAL["mysql_server"];
			$this->mysql_enabled=$_GLOBAL["mysql_enabled"];
			$this->OBM_INSTALLED=$_GLOBAL["OBM_INSTALLED"];
			if(isset($_GLOBAL["NTPD_INSTALLED"])){$this->NTPD_INSTALLED=$_GLOBAL["NTPD_INSTALLED"];}
			if(isset($_GLOBAL["MAILFROMD_INSTALLED"])){$this->MAILFROMD_INSTALLED=$_GLOBAL["MAILFROMD_INSTALLED"];	}	
			if(isset($_GLOBAL["ARTICA_FILTER_QUEUE_PATH"])){$this->ARTICA_FILTER_QUEUE_PATH=$_GLOBAL["ARTICA_FILTER_QUEUE_PATH"];}
			if(isset($_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"])){$this->ARTICA_FILTER_MAXSUBQUEUE=$_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"];}
			$this->IPTABLES_INSTALLED=$_GLOBAL["IPTABLES_INSTALLED"];
			if(isset($_GLOBAL["GeoIPDat_Path"])){$this->GeoIPDat_Path=$_GLOBAL["GeoIPDat_Path"];}
			if(isset($_GLOBAL["lighttpd_installed"])){$this->lighttpd_installed=$_GLOBAL["lighttpd_installed"];}
			if($this->ChartLicence==null){$this->ChartLicence='D1XFWGMEW9L.HSK5T4Q79KLYCK07EK';}
			$this->SAMBA_INSTALLED=$_GLOBAL["SAMBA_INSTALLED"];
			$this->LOCAL_SID=$_GLOBAL["LOCAL_SID"];
			$this->BadMysqlPassword=$_GLOBAL["BadMysqlPassword"];
			if($this->BadMysqlPassword==null){$this->BadMysqlPassword=0;}
			if(isset($_GLOBAL["POSTFIX_LDAP_COMPLIANCE"])){$this->POSTFIX_LDAP_COMPLIANCE=$_GLOBAL["POSTFIX_LDAP_COMPLIANCE"];}
			if(isset($_GLOBAL["MAILFROMD_SOCKET"])){$this->MAILFROMD_SOCKET=$_GLOBAL["MAILFROMD_SOCKET"];}
			if(isset($_GLOBAL["CLAMAV_INSTALLED"])){$this->CLAMAV_INSTALLED=$_GLOBAL["CLAMAV_INSTALLED"];}
			if(isset($_GLOBAL["CLAMSCAN_INSTALLED"])){$this->CLAMSCAN_INSTALLED=$_GLOBAL["CLAMSCAN_INSTALLED"];}
			
			
			
			
			
			if(is_file("/etc/artica-postfix/settings/Daemons/ClamAVMilterInstalled")){
				if(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ClamAVMilterInstalled"))==1){
					$this->CLAMAV_MILTER_INSTALLED=true;
				}
			}
			
			if(is_file("/etc/artica-postfix/settings/Daemons/MimeDefangInstalled")){
				if(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangInstalled"))==1){
					$this->MIMEDEFANG_INSTALLED=true;
				}
			}
			
			if(!$this->MIMEDEFANG_INSTALLED){
				if(is_file("/usr/local/bin/mimedefang")){
					$this->MIMEDEFANG_INSTALLED=true;
				}
			}
			
			
			if(isset($_GLOBAL["SPAMASS_MILTER_INSTALLED"])){$this->SPAMASS_MILTER_INSTALLED=$_GLOBAL["SPAMASS_MILTER_INSTALLED"];}
			if(isset($_GLOBAL["SPAMASS_MILTER_SOCKET"])){$this->SPAMASS_MILTER_SOCKET=$_GLOBAL["SPAMASS_MILTER_SOCKET"];}
			if(isset($_GLOBAL["roundcube_folder"])){$this->roundcube_folder=$_GLOBAL["roundcube_folder"];}
			$this->phpcgi=$_GLOBAL["phpcgi"];
			if(isset($_GLOBAL["razor_config"])){$this->razor_config=$_GLOBAL["razor_config"];}
			if(isset($_GLOBAL["razor_installed"])){$this->razor_installed=$_GLOBAL["razor_installed"];}
			if(isset($_GLOBAL["pyzor_installed"])){$this->pyzor_installed=$_GLOBAL["pyzor_installed"];}
			if(isset($_GLOBAL["spfmilter_installed"])){$this->spfmilter_installed=$_GLOBAL["spfmilter_installed"];}
			if(isset($_GLOBAL["netbiosname"])){$this->netbiosname=$_GLOBAL["netbiosname"];}
			
			
			if(isset($_GLOBAL["GRAPHDEFANG_INSTALLED"])){$this->GRAPHDEFANG_INSTALLED=$_GLOBAL["GRAPHDEFANG_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_INSTALLED"])){$this->MHONARC_INSTALLED=$_GLOBAL["MHONARC_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_VERSION"])){$this->MHONARC_VERSION=$_GLOBAL["MHONARC_VERSION"];}
			if(isset($_GLOBAL["spamassassin_installed"])){$this->spamassassin_installed=$_GLOBAL["spamassassin_installed"];}
			if(isset($_GLOBAL["spamassassin_version"])){$this->spamassassin_version=$_GLOBAL["spamassassin_version"];}
			if(isset($_GLOBAL["bogofilter_installed"])){$this->BOGOFILTER_INSTALLED=$_GLOBAL["bogofilter_installed"];}
			if(isset($_GLOBAL["SQUID_VERSION"])){$this->SQUID_VERSION=$_GLOBAL["SQUID_VERSION"];}
			if(isset($_GLOBAL["SQUID_LDAP_AUTH"])){$this->SQUID_LDAP_AUTH=$_GLOBAL["SQUID_LDAP_AUTH"];}
			if(isset($_GLOBAL["stunnel4_installed"])){$this->stunnel4_installed=$_GLOBAL["stunnel4_installed"];}
			if(isset($_GLOBAL["DKIMFILTER_INSTALLED"])){$this->DKIMFILTER_INSTALLED=$_GLOBAL["DKIMFILTER_INSTALLED"];}
			if(isset($_GLOBAL["DKIMFILTER_SOCKET"])){$this->DKIMFILTER_SOCKET=$_GLOBAL["DKIMFILTER_SOCKET"];}
			if(isset($_GLOBAL["POSTFIX_VERSION"])){$this->POSTFIX_VERSION=$_GLOBAL["POSTFIX_VERSION"];}
			if(isset($_GLOBAL["blkid_installed"])){$this->blkid_installed=$_GLOBAL["blkid_installed"];}
			if(isset($_GLOBAL["usb_list"])){$this->usb_list=$_GLOBAL["usb_list"];}
			if(isset($_GLOBAL["nmap_installed"])){$this->nmap_installed=$_GLOBAL["nmap_installed"];}
			if(isset($_GLOBAL["roundcube_mysql_sources"])){$this->roundcube_mysql_sources=$_GLOBAL["roundcube_mysql_sources"];}
			if(isset($_GLOBAL["roundcube_web_folder"])){$this->roundcube_web_folder=$_GLOBAL["roundcube_web_folder"];}
			if(isset($_GLOBAL["fetchmail_configured"])){$this->fetchmail_configured=$_GLOBAL["fetchmail_configured"];}
			if(isset($_GLOBAL["KAV4SAMBA_INSTALLED"])){$this->KAV4SAMBA_INSTALLED=$_GLOBAL["KAV4SAMBA_INSTALLED"];}
			if(isset($_GLOBAL["KAV4SAMBA_VFS"])){$this->KAV4SAMBA_VFS=$_GLOBAL["KAV4SAMBA_VFS"];}
			if(isset($_GLOBAL["SAMBA_MYSQL_AUDIT"])){$this->SAMBA_MYSQL_AUDIT=$_GLOBAL["SAMBA_MYSQL_AUDIT"];}
			if(isset($_GLOBAL["awstats_www_path"])){$this->awstats_www_path=$_GLOBAL["awstats_www_path"];}
			
			if(isset($_GLOBAL["LIGHTTPD_LDAP_AUTH"])){$this->LIGHTTPD_LDAP_AUTH=$_GLOBAL["LIGHTTPD_LDAP_AUTH"];}
			if(isset($_GLOBAL["SIEVE_PORT"])){$this->SIEVE_PORT=$_GLOBAL["SIEVE_PORT"];}
			if(isset($_GLOBAL["Z_PUSH_INSTALLED"])){$this->Z_PUSH_INSTALLED=$_GLOBAL["Z_PUSH_INSTALLED"];}
			if(isset($_GLOBAL["APACHE_MOD_CACHE"])){$this->APACHE_MOD_CACHE=$_GLOBAL["APACHE_MOD_CACHE"];}
			if(isset($_GLOBAL["NSCD_INSTALLED"])){$this->NSCD_INSTALLED=$_GLOBAL["NSCD_INSTALLED"];}
			if(isset($_GLOBAL["DRUPAL7_INSTALLED"])){$this->DRUPAL7_INSTALLED=$_GLOBAL["DRUPAL7_INSTALLED"];}
			if(isset($_GLOBAL["OPENEMM_SENDMAIL_INSTALLED"])){$this->OPENEMM_SENDMAIL_INSTALLED=$_GLOBAL["OPENEMM_SENDMAIL_INSTALLED"];}
			if(isset($_GLOBAL["OPENEMM_SENDMAIL_VERSION"])){$this->OPENEMM_SENDMAIL_VERSION=$_GLOBAL["OPENEMM_SENDMAIL_VERSION"];}
			if(isset($_GLOBAL["YAFFAS_INSTALLED"])){$this->YAFFAS_INSTALLED=$_GLOBAL["YAFFAS_INSTALLED"];}
			if(isset($_GLOBAL["SQUID_APPLIANCE"])){$this->SQUID_APPLIANCE=$_GLOBAL["SQUID_APPLIANCE"];}
			if(isset($_GLOBAL["PROXYTINY_APPLIANCE"])){$this->PROXYTINY_APPLIANCE=$_GLOBAL["PROXYTINY_APPLIANCE"];}
			if(isset($_GLOBAL["NETATALK_INSTALLED"])){$this->NETATALK_INSTALLED=$_GLOBAL["NETATALK_INSTALLED"];}
			if(isset($_GLOBAL["SAMBA_APPLIANCE"])){$this->SAMBA_APPLIANCE=$_GLOBAL["SAMBA_APPLIANCE"];}
			if(isset($_GLOBAL["UCARP_INSTALLED"])){$this->UCARP_INSTALLED=$_GLOBAL["UCARP_INSTALLED"];}
			if(isset($_GLOBAL["APP_KHSE_INSTALLED"])){$this->APP_KHSE_INSTALLED=$_GLOBAL["APP_KHSE_INSTALLED"];}
			if(isset($_GLOBAL["MPM_ITK_MODULE"])){$this->MPM_ITK_MODULE=$_GLOBAL["MPM_ITK_MODULE"];}
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];}
			if(isset($_GLOBAL["MONIT_INSTALLED"])){$this->MONIT_INSTALLED=$_GLOBAL["MONIT_INSTALLED"];}
			if(isset($_GLOBAL["CONCRETE5_INSTALLED"])){$this->CONCRETE5_INSTALLED=$_GLOBAL["CONCRETE5_INSTALLED"];}
			if(isset($_GLOBAL["IPBAN_INSTALLED"])){$this->IPBAN_INSTALLED=$_GLOBAL["IPBAN_INSTALLED"];}
			if(isset($_GLOBAL["SMTP_APPLIANCE"])){$this->SMTP_APPLIANCE=$_GLOBAL["SMTP_APPLIANCE"];}
			if(isset($_GLOBAL["LDAP_BACKMETA"])){$this->LDAP_BACKMETA=$_GLOBAL["LDAP_BACKMETA"];}
			if(isset($_GLOBAL["LDAP_BACKLDAP"])){$this->LDAP_BACKLDAP=$_GLOBAL["LDAP_BACKLDAP"];}
			if(isset($_GLOBAL["LDAP_BACKRELAY"])){$this->LDAP_BACKLDAP=$_GLOBAL["LDAP_BACKRELAY"];}
			if(isset($_GLOBAL["LDAP_RWM"])){$this->LDAP_RWM=$_GLOBAL["LDAP_RWM"];}
			if(isset($_GLOBAL["FROM_ISO"])){$this->FROM_ISO=$_GLOBAL["FROM_ISO"];}
			if(isset($_GLOBAL["APP_JAVA_JDK_INSTALLED"])){$this->APP_JAVA_JDK_INSTALLED=$_GLOBAL["APP_JAVA_JDK_INSTALLED"];}
			if(isset($_GLOBAL["APP_CHILLI_INSTALLED"])){$this->APP_CHILLI_INSTALLED=$_GLOBAL["APP_CHILLI_INSTALLED"];}
			if(isset($_GLOBAL["HAARP_INSTALLED"])){$this->HAARP_INSTALLED=$_GLOBAL["HAARP_INSTALLED"];}
			if(isset($_GLOBAL["RDPPROXY_INSTALLED"])){$this->RDPPROXY_INSTALLED=$_GLOBAL["RDPPROXY_INSTALLED"];}
			if(isset($_GLOBAL["PACTESTER_INSTALLED"])){$this->PACTESTER_INSTALLED=$_GLOBAL["PACTESTER_INSTALLED"];}
			if(isset($_GLOBAL["GATEWAY_APPLIANCE"])){$this->GATEWAY_APPLIANCE=$_GLOBAL["GATEWAY_APPLIANCE"];}
			if(isset($_GLOBAL["SHOREWALL_INSTALLED"])){$this->SHOREWALL_INSTALLED=$_GLOBAL["SHOREWALL_INSTALLED"];}
			if(isset($_GLOBAL["CONNTRACK_INSTALLED"])){$this->CONNTRACK_INSTALLED=$_GLOBAL["CONNTRACK_INSTALLED"];}
			if(isset($_GLOBAL["l7FILTER_INSTALLED"])){$this->l7FILTER_INSTALLED=$_GLOBAL["l7FILTER_INSTALLED"];}
			if(isset($_GLOBAL["WEBSECURIZE"])){$this->WEBSECURIZE=$_GLOBAL["WEBSECURIZE"];}
			if(isset($_GLOBAL["BAMSIGHT"])){$this->BAMSIGHT=$_GLOBAL["BAMSIGHT"];}
			
			if(isset($_GLOBAL["LANWANSAT"])){$this->LANWANSAT=$_GLOBAL["LANWANSAT"];}
			if(isset($_GLOBAL["FIREHOL"])){$this->FIREHOL=$_GLOBAL["FIREHOL"];}
			if(isset($_GLOBAL["XEN_HOST"])){$this->XEN_HOST=$_GLOBAL["XEN_HOST"];}
			if(isset($_GLOBAL["HYPERV_HOST"])){$this->HYPERV_HOST=$_GLOBAL["HYPERV_HOST"];}
			

			
			$this->bindrrd_installed=$_GLOBAL["bindrrd_installed"];
			$this->fdm_installed=$_GLOBAL["fdm_installed"];
			if(isset($_GLOBAL["fdm_cache"])){$this->fdm_cache=$_GLOBAL["fdm_cache"];}
			$this->p3scan_installed=$_GLOBAL["p3scan_installed"];
			$this->syslogng_installed=$_GLOBAL["syslogng_installed"];
			$this->AsDebianSystem=$_GLOBAL["AsDebianSystem"];
			if(trim($this->hostname)==null){$this->hostname=$this->fqdn;}
			$this->cpumhz=$_GLOBAL["cpumhz"];
			$this->preload_installed=$_GLOBAL["preload_installed"];
			$this->POSTFIX_MAILDROP_PATH=$_GLOBAL["POSTFIX_MAILDROP_PATH"];
			$this->smbmount_installed=$_GLOBAL["smbmount_installed"];
			if(isset($_GLOBAL["PUREFTP_USERS"])){$this->PUREFTP_USERS=$_GLOBAL["PUREFTP_USERS"];}
			if(isset($_GLOBAL["roundcube_version"])){$this->roundcube_version=$_GLOBAL["roundcube_version"];}
			$this->openldap_installed=$_GLOBAL["openldap_installed"];
			$this->collectd_installed=$_GLOBAL["collectd_installed"];
			if(isset($_GLOBAL["COLLECTD_RRD"])){$this->COLLECTD_RRD=$_GLOBAL["COLLECTD_RRD"];}
			$this->EnableVirtualDomainsInMailBoxes=$_GLOBAL["EnableVirtualDomainsInMailBoxes"];
			$this->SIMPLE_GROUPEWARE_INSTALLED=$_GLOBAL["SIMPLE_GROUPEWARE_INSTALLED"];
			$this->APP_ATOPENMAIL_INSTALLED=$_GLOBAL["APP_ATOPENMAIL_INSTALLED"];
			$this->APP_ATOPENMAIL_VERSION=$_GLOBAL["APP_ATOPENMAIL_VERSION"];
			$this->MILTER_SPY_INSTALLED=$_GLOBAL["MILTER_SPY_INSTALLED"];
			$this->mailsync_installed=$_GLOBAL["mailsync_installed"];
			$this->ISOQLOG_INSTALLED=$_GLOBAL["ISOQLOG_INSTALLED"];
			$this->DOTCLEAR_INSTALLED=$_GLOBAL["DOTCLEAR_INSTALLED"];
			if(isset($_GLOBAL["JCHECKMAIL_INSTALLED"])){$this->JCHECKMAIL_INSTALLED=$_GLOBAL["JCHECKMAIL_INSTALLED"];}
			if(isset($_GLOBAL["JCHECKMAIL_SOCKET"])){$this->JCHECKMAIL_SOCKET=$_GLOBAL["JCHECKMAIL_SOCKET"];}
			$this->zip_installed=$_GLOBAL["zip_installed"];
			$this->BIND9_INSTALLED=$_GLOBAL["BIND9_INSTALLED"];
			if(isset($_GLOBAL["OPENLDAP_DYNLIST"])){$this->OPENLDAP_DYNLIST=$_GLOBAL["OPENLDAP_DYNLIST"];}
			if(isset($_GLOBAL["SLPAD_LOCKED"])){$this->SLPAD_LOCKED=$_GLOBAL["SLPAD_LOCKED"];}
			$this->dhcp_installed=$_GLOBAL["dhcp_installed"];
			if(isset($_GLOBAL["ALTERMIME_INSTALLED"])){$this->ALTERMIME_INSTALLED=$_GLOBAL["ALTERMIME_INSTALLED"];}
			if(isset($_GLOBAL["POMMO_INSTALLED"])){$this->POMMO_INSTALLED=$_GLOBAL["POMMO_INSTALLED"];}
			$this->dar_installed=$_GLOBAL["dar_installed"];
			if(isset($_GLOBAL["SAMBA_VERSION"])){$this->SAMBA_VERSION=$_GLOBAL["SAMBA_VERSION"];}
			if(isset($_GLOBAL["C_ICAP_INSTALLED"])){$this->C_ICAP_INSTALLED=$_GLOBAL["C_ICAP_INSTALLED"];}
			if(isset($_GLOBAL["C_ICAP_DNSBL"])){$this->C_ICAP_DNSBL=$_GLOBAL["C_ICAP_DNSBL"];}
			if(isset($_GLOBAL["MILTERGREYLIST_SOCKET"])){$this->MILTERGREYLIST_SOCKET=$_GLOBAL["MILTERGREYLIST_SOCKET"];}
			$this->OPENVPN_INSTALLED=$_GLOBAL["OPENVPN_INSTALLED"];
			if(isset($_GLOBAL["GNARWL_INSTALLED"])){$this->GNARWL_INSTALLED=$_GLOBAL["GNARWL_INSTALLED"];}
			if(isset($_GLOBAL["CLAMD_CONF_PATH"])){$this->CLAMD_CONF_PATH=$_GLOBAL["CLAMD_CONF_PATH"];}
			if(isset($_GLOBAL["CLAMD_DATABASE_PATH"])){$this->CLAMD_DATABASE_PATH=$_GLOBAL["CLAMD_DATABASE_PATH"];}
			if(isset($_GLOBAL["PFLOGSUMM_INSTALLED"])){$this->PFLOGSUMM_INSTALLED=$_GLOBAL["PFLOGSUMM_INSTALLED"];}
			if(isset($_GLOBAL["maillog_path"])){$this->maillog_path=$_GLOBAL["maillog_path"];}
			if(isset($_GLOBAL["spamassassin_ipcountry"])){$this->spamassassin_ipcountry=$_GLOBAL["spamassassin_ipcountry"];}
			if(isset($_GLOBAL["spamassassin_conf_path"])){$this->spamassassin_conf_path=$_GLOBAL["spamassassin_conf_path"];}
			if(isset($_GLOBAL["CUPS_INSTALLED"])){$this->CUPS_INSTALLED=$_GLOBAL["CUPS_INSTALLED"];}
			$this->OBM2_INSTALLED=$_GLOBAL["OBM2_INSTALLED"];
			$this->SMARTMONTOOLS_INSTALLED=$_GLOBAL["SMARTMONTOOLS_INSTALLED"];
			if(isset($_GLOBAL["SARG_INSTALLED"])){$this->SARG_INSTALLED=$_GLOBAL["SARG_INSTALLED"];}
			$this->XAPIAN_PHP_INSTALLED=$_GLOBAL["XAPIAN_PHP_INSTALLED"];
			$this->OPENGOO_INSTALLED=$_GLOBAL["OPENGOO_INSTALLED"];
			$this->JOOMLA_INSTALLED=$_GLOBAL["JOOMLA_INSTALLED"];
			if(isset($_GLOBAL["APACHE_GROUPWARE_INSTALLED"])){$this->APACHE_GROUPWARE_INSTALLED=$_GLOBAL["APACHE_GROUPWARE_INSTALLED"];}
			$this->DSTAT_INSTALLED=$_GLOBAL["DSTAT_INSTALLED"];
			$this->SUGARCRM_INSTALLED=$_GLOBAL["SUGARCRM_INSTALLED"];
			if(isset($_GLOBAL["GNUPLOT_PNG"])){$this->GNUPLOT_PNG=$_GLOBAL["GNUPLOT_PNG"];}
			$this->IOSTAT_INSTALLED=$_GLOBAL["IOSTAT_INSTALLED"];
			if(isset($_GLOBAL["IOSTAT_ARRAY"])){if(is_array($_GLOBAL["IOSTAT_ARRAY"])){$this->IOSTAT_ARRAY=$_GLOBAL["IOSTAT_ARRAY"];}}
			if(isset($_GLOBAL["POSTFIX_QUEUE"])){$this->POSTFIX_QUEUE=$_GLOBAL["POSTFIX_QUEUE"];}
			$this->PHP_BIN_PATH=$_GLOBAL["PHP_BIN_PATH"];
			$this->EXEC_NICE=$_GLOBAL["EXEC_NICE"];
			if(isset($_GLOBAL["spamassassin_bin_path"])){$this->spamassassin_bin_path=$_GLOBAL["spamassassin_bin_path"];}
			$this->LinuxDistriCode=$_GLOBAL["LinuxDistriCode"];
			$this->LinuxDistriFullName=$_GLOBAL["LinuxDistriFullName"];
			$this->APACHE_INSTALLED=$_GLOBAL["APACHE_INSTALLED"];
			if(isset($_GLOBAL["roundcube_intversion"])){$this->roundcube_intversion=$_GLOBAL["roundcube_intversion"];}
			if(isset($_GLOBAL["roundcube_plugins"])){$this->roundcube_plugins=$_GLOBAL["roundcube_plugins"];}
			if(isset($_GLOBAL["smbclient_installed"])){$this->smbclient_installed=$_GLOBAL["smbclient_installed"];}
			if(isset($_GLOBAL["AMAVISD_VERSION"])){$this->AMAVISD_VERSION=$_GLOBAL["AMAVISD_VERSION"];}
			if(isset($_GLOBAL["cyrus_murder_installed"])){$this->cyrus_murder_installed=$_GLOBAL["cyrus_murder_installed"];}
			if(isset($_GLOBAL["cyrus_sync_installed"])){$this->cyrus_sync_installed=$_GLOBAL["cyrus_sync_installed"];}
			if(isset($_GLOBAL["FETCHMAIL_VERSION"])){$this->FETCHMAIL_VERSION=$_GLOBAL["FETCHMAIL_VERSION"];}
			if(isset($_GLOBAL["cyrus_sync_client_path"])){$this->cyrus_sync_client_path=$_GLOBAL["cyrus_sync_client_path"];}
			if(isset($_GLOBAL["ctl_mboxlist"])){$this->ctl_mboxlist=$_GLOBAL["ctl_mboxlist"];}
			if(isset($_GLOBAL["CURL_PATH"])){$this->CURL_PATH=$_GLOBAL["CURL_PATH"];}
			if(isset($_GLOBAL["DANSGUARDIAN_VERSION"])){$this->DANSGUARDIAN_VERSION=$_GLOBAL["DANSGUARDIAN_VERSION"];}
			if(isset($_GLOBAL["nmap_path"])){$this->NMAP_PATH=$_GLOBAL["nmap_path"];}
			if(isset($_GLOBAL["msmtp_installed"])){$this->msmtp_installed=$_GLOBAL["msmtp_installed"];}
			if(isset($_GLOBAL["nmap_version"])){$this->nmap_version=$_GLOBAL["nmap_version"];}
			if(isset($_GLOBAL["LOAD_BALANCE_APPLIANCE"])){$this->LOAD_BALANCE_APPLIANCE=$_GLOBAL["LOAD_BALANCE_APPLIANCE"];}
			
			
			
			if(isset($_GLOBAL["autofs_installed"])){$this->autofs_installed=$_GLOBAL["autofs_installed"];}
			if(isset($_GLOBAL["APACHE_PORT"])){$this->APACHE_PORT=$_GLOBAL["APACHE_PORT"];}
			$this->NFS_SERVER_INSTALLED=$_GLOBAL["NFS_SERVER_INTSALLED"];
			$this->LVM_INSTALLED=$_GLOBAL["LVM_INSTALLED"];
			$this->MYSQL_NDB_MGMD_INSTALLED=$_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"];
			if(isset($_GLOBAL["POLICYD_WEIGHT_PORT"])){$this->POLICYD_WEIGHT_PORT=$_GLOBAL["POLICYD_WEIGHT_PORT"];}
			$this->winexe_installed=$_GLOBAL["winexe_installed"];
			$this->OCSI_INSTALLED=$_GLOBAL["OCSI_INSTALLED"];
			if(isset($_GLOBAL["ASSP_INSTALLED"])){$this->ASSP_INSTALLED=$_GLOBAL["ASSP_INSTALLED"];}
			$this->MEM_HIGER_1G=$_GLOBAL["MEM_HIGER_1G"];
			$this->LMB_LUNDIMATIN_INSTALLED=$_GLOBAL["LMB_LUNDIMATIN_INSTALLED"];
			if(isset($_GLOBAL["APACHE_MODE_VHOSTS_LDAP"])){$this->APACHE_MODE_VHOSTS_LDAP=$_GLOBAL["APACHE_MODE_VHOSTS_LDAP"];}
			$this->TCP_ARRAY=$_GLOBAL["TCP_ARRAY"];
			$this->POWER_DNS_INSTALLED=$_GLOBAL["POWER_DNS_INSTALLED"];
			if(isset($_GLOBAL["CLAMD_INSTALLED"])){$this->CLAMD_INSTALLED=$_GLOBAL["CLAMD_INSTALLED"];}
			$this->mysqld_version=$_GLOBAL["mysqld_version"];
			$this->GLUSTER_INSTALLED=$_GLOBAL["GLUSTER_INSTALLED"];
			$this->SLAPD_CONF_PATH=$_GLOBAL["SLAPD_CONF_PATH"];
			if(isset($_GLOBAL["cyrus_squatter_exists"])){$this->cyrus_squatter_exists=$_GLOBAL["cyrus_squatter_exists"];}
			if(isset($_GLOBAL["SCANNED_ONLY_INSTALLED"])){$this->SCANNED_ONLY_INSTALLED=$_GLOBAL["SCANNED_ONLY_INSTALLED"];}
			$this->CRYPTSETUP_INSTALLED=$_GLOBAL["CRYPTSETUP_INSTALLED"];
			$this->ZABBIX_INSTALLED=$_GLOBAL["ZABBIX_INSTALLED"];
			$this->GROUPOFFICE_INSTALLED=$_GLOBAL["GROUPOFFICE_INSTALLED"];
			$this->cyrus_ipurge_exists=$_GLOBAL["cyrus_ipurge_exists"];
			$this->POSTFIX_MULTI=$this->postfixmutli();
			if(isset($_GLOBAL["ASSP_VERSION"])){$this->ASSP_VERSION=$_GLOBAL["ASSP_VERSION"];}
			if(isset($_GLOBAL["HAMACHI_INSTALLED"])){$this->HAMACHI_INSTALLED=$_GLOBAL["HAMACHI_INSTALLED"];}
			if(isset($_GLOBAL["MEM_TOTAL_INSTALLEE"])){$this->MEM_TOTAL_INSTALLEE=$_GLOBAL["MEM_TOTAL_INSTALLEE"];} // 716800
			if(isset($_GLOBAL["KASPERSKY_SMTP_APPLIANCE"])){$this->KASPERSKY_SMTP_APPLIANCE=$_GLOBAL["KASPERSKY_SMTP_APPLIANCE"];}
			if(isset($_GLOBAL["POSTFILTER_INSTALLED"])){$this->POSTFILTER_INSTALLED=$_GLOBAL["POSTFILTER_INSTALLED"];}
			if(isset($_GLOBAL["VMWARE_HOST"])){$this->VMWARE_HOST=$_GLOBAL["VMWARE_HOST"];}
			if(isset($_GLOBAL["VMWARE_TOOLS_INSTALLED"])){$this->VMWARE_TOOLS_INSTALLED=$_GLOBAL["VMWARE_TOOLS_INSTALLED"];}
			if(isset($_GLOBAL["phpldapadmin_installed"])){$this->phpldapadmin_installed=$_GLOBAL["phpldapadmin_installed"];}
			if(isset($_GLOBAL["phpmyadmin_installed"])){$this->phpmyadmin_installed=$_GLOBAL["phpmyadmin_installed"];}			
			if(isset($_GLOBAL["SQUID_NTLM_ENABLED"])){$this->SQUID_NTLM_ENABLED=$_GLOBAL["SQUID_NTLM_ENABLED"];}
			if(isset($_GLOBAL["WINBINDD_INSTALLED"])){$this->WINBINDD_INSTALLED=$_GLOBAL["WINBINDD_INSTALLED"];}
			if(isset($_GLOBAL["ZARAFA_INSTALLED"])){$this->ZARAFA_INSTALLED=$_GLOBAL["ZARAFA_INSTALLED"];}
			if(isset($_GLOBAL["SQUIDGUARD_INSTALLED"])){$this->SQUIDGUARD_INSTALLED=$_GLOBAL["SQUIDGUARD_INSTALLED"];}
			if(isset($_GLOBAL["SQUIDGUARD_BINVER"])){$this->SQUIDGUARD_BINVER=$_GLOBAL["SQUIDGUARD_BINVER"];}
			if(isset($_GLOBAL["SQUIDGUARD_BIN_PATH"])){$this->SQUIDGUARD_BIN_PATH=$_GLOBAL["SQUIDGUARD_BIN_PATH"];}
			if(isset($_GLOBAL["SQUID_BIN_PATH"])){$this->SQUID_BIN_PATH=$_GLOBAL["SQUID_BIN_PATH"];}
			if(isset($_GLOBAL["HOSTAPD_INSTALLED"])){$this->HOSTAPD_INSTALLED=$_GLOBAL["HOSTAPD_INSTALLED"];}
			if(isset($_GLOBAL["MEMCACHED_INSTALLED"])){$this->MEMCACHED_INSTALLED=$_GLOBAL["MEMCACHED_INSTALLED"];}
			if(isset($_GLOBAL["FREERADIUS_INSTALLED"])){$this->FREERADIUS_INSTALLED=$_GLOBAL["FREERADIUS_INSTALLED"];}
			if(isset($_GLOBAL["SHOREWALL_INSTALLED"])){$this->SHOREWALL_INSTALLED=$_GLOBAL["SHOREWALL_INSTALLED"];}
			
			
			
			
			$this->WPA_SUPPLIANT_INSTALLED=$_GLOBAL["WPA_SUPPLIANT_INSTALLED"];
			if(isset($_GLOBAL["HOSTAPD_BINVER"])){$this->HOSTAPD_BINVER=$_GLOBAL["HOSTAPD_BINVER"];}
			$this->KASPERSKY_WEB_APPLIANCE=$_GLOBAL["KASPERSKY_WEB_APPLIANCE"];
			$this->ArchStruct=$_GLOBAL["ArchStruct"];
			$this->RSYNC_INSTALLED=$_GLOBAL["RSYNC_INSTALLED"];
			$this->ZARAFA_APPLIANCE=$_GLOBAL["ZARAFA_APPLIANCE"];
			$this->mysqld_datadir=$_GLOBAL["mysqld_datadir"];
			$this->DRUPAL_INSTALLED=$_GLOBAL["DRUPAL_INSTALLED"];
			if(isset($_GLOBAL["MAIL_DKIM_VERSION"])){$this->MAIL_DKIM_VERSION=trim($_GLOBAL["MAIL_DKIM_VERSION"]);}
			$this->HDPARM_INSTALLED=trim($_GLOBAL["HDPARM_INSTALLED"]);
			if(isset($_GLOBAL["POSTMULTI"])){$this->POSTMULTI=$_GLOBAL["POSTMULTI"];}
			$this->MLDONKEY_INSTALLED=$_GLOBAL["MLDONKEY_INSTALLED"];
			$this->APACHE_MODE_WEBDAV=$_GLOBAL["APACHE_MODE_WEBDAV"];
			//error detected ?
			$this->hostname=str_replace('.(none)','',$this->hostname);
			$this->fqdn=str_replace('.(none)','',$this->fqdn);
			$this->BACKUPPC_INSTALLED=$_GLOBAL["BACKUPPC_INSTALLED"];
			if(isset($_GLOBAL["BACKUPPC_APACHE"])){$this->BACKUPPC_APACHE=$_GLOBAL["BACKUPPC_APACHE"];}
			$this->KAV4FS_INSTALLED=$_GLOBAL["KAV4FS_INSTALLED"];
			if(isset($_GLOBAL["KAV4PROXY_LICENSE_ERROR"])){$this->KAV4PROXY_LICENSE_ERROR=$_GLOBAL["KAV4PROXY_LICENSE_ERROR"];}
			if(isset($_GLOBAL["KAV4PROXY_LICENSE_ERROR_TEXT"])){$this->KAV4PROXY_LICENSE_ERROR_TEXT=$_GLOBAL["KAV4PROXY_LICENSE_ERROR_TEXT"];}
			$this->OCS_LNX_AGENT_INSTALLED=$_GLOBAL["OCS_LNX_AGENT_INSTALLED"];
			if(isset($_GLOBAL["POSTFIX_PCRE_COMPLIANCE"])){$this->POSTFIX_PCRE_COMPLIANCE=$_GLOBAL["POSTFIX_PCRE_COMPLIANCE"];};
			$this->APP_AUDITD_INSTALLED=$_GLOBAL["APP_AUDITD_INSTALLED"];
			if(isset($_GLOBAL["DKFILTER_INSTALLED"])){$this->DKFILTER_INSTALLED=$_GLOBAL["DKFILTER_INSTALLED"];}
			if(isset($_GLOBAL["OPENDKIM_INSTALLED"])){$this->OPENDKIM_INSTALLED=$_GLOBAL["OPENDKIM_INSTALLED"];}
			$this->DB_RECOVER_INSTALLED=$_GLOBAL["DB_RECOVER_INSTALLED"];
			if(isset($_GLOBAL["APP_UFDBGUARD_INSTALLED"])){$this->APP_UFDBGUARD_INSTALLED=$_GLOBAL["APP_UFDBGUARD_INSTALLED"];}
			if(isset($_GLOBAL["ufdbgclient_path"])){$this->ufdbgclient_path=$_GLOBAL["ufdbgclient_path"];}
			if(isset($_GLOBAL["roundcube_subscriptions_option"])){$this->roundcube_subscriptions_option=$_GLOBAL["roundcube_subscriptions_option"];}
			if(isset($_GLOBAL["ADZAPPER_INSTALLED"])){$this->ADZAPPER_INSTALLED=$_GLOBAL["ADZAPPER_INSTALLED"];}
			if(isset($_GLOBAL["KINIT_INSTALLED"])){$this->KINIT_INSTALLED=$_GLOBAL["KINIT_INSTALLED"];}
			if(isset($_GLOBAL["DKIMPROXY_INSTALLED"])){$this->DKIMPROXY_INSTALLED=$_GLOBAL["DKIMPROXY_INSTALLED"];}
			if(isset($_GLOBAL["MILTER_DKIM_INSTALLED"])){$this->MILTER_DKIM_INSTALLED=$_GLOBAL["MILTER_DKIM_INSTALLED"];}
			if(isset($_GLOBAL["DROPBOX_INSTALLED"])){$this->DROPBOX_INSTALLED=$_GLOBAL["DROPBOX_INSTALLED"];}
			if(isset($_GLOBAL["VIRTUALBOX_INSTALLED"])){$this->VIRTUALBOX_INSTALLED=$_GLOBAL["VIRTUALBOX_INSTALLED"];}
			if(isset($_GLOBAL["TFTPD_INSTALLED"])){$this->TFTPD_INSTALLED=$_GLOBAL["TFTPD_INSTALLED"];}
			if(isset($_GLOBAL["offlineimap_installed"])){$this->offlineimap_installed=$_GLOBAL["offlineimap_installed"];}
			if(isset($_GLOBAL["THINSTATION_INSTALLED"])){$this->THINSTATION_INSTALLED=$_GLOBAL["THINSTATION_INSTALLED"];}
			$this->OPENSSH_VER=$_GLOBAL["OPENSSH_VER"];
			if(isset($_GLOBAL["cyrus_syncserver_installed"])){$this->cyrus_syncserver_installed=$_GLOBAL["cyrus_syncserver_installed"];}
			if(isset($_GLOBAL["APP_SQUIDCLAMAV_INSTALLED"])){$this->APP_SQUIDCLAMAV_INSTALLED=$_GLOBAL["APP_SQUIDCLAMAV_INSTALLED"];}
			if(isset($_GLOBAL["squidclamav_path"])){$this->squidclamav_path=$_GLOBAL["squidclamav_path"];}
			$this->RACOON_INSTALLED=$_GLOBAL["RACOON_INSTALLED"];
			$this->IPSEC_INSTALLED=$_GLOBAL["IPSEC_INSTALLED"];
			$this->PPTPD_INSTALLED=$_GLOBAL["PPTPD_INSTALLED"];
			$this->PPTP_INSTALLED=$_GLOBAL["PPTP_INSTALLED"];
			if(isset($_GLOBAL["SQUID_ARP_ACL_ENABLED"])){$this->SQUID_ARP_ACL_ENABLED=$_GLOBAL["SQUID_ARP_ACL_ENABLED"];}
			if(isset($_GLOBAL["GREYHOLE_INSTALLED"])){$this->GREYHOLE_INSTALLED=$_GLOBAL["GREYHOLE_INSTALLED"];}
			$this->APT_MIRROR_INSTALLED=$_GLOBAL["APT_MIRROR_INSTALLED"];
			if(isset($_GLOBAL["CLUEBRINGER_INSTALLED"])){$this->CLUEBRINGER_INSTALLED=$_GLOBAL["CLUEBRINGER_INSTALLED"];}
			$this->qos_tools_installed=$_GLOBAL["qos_tools_installed"];
			$this->tc_version=$_GLOBAL["tc_version"];
			$this->LDAP_SYNCPROV=$_GLOBAL["LDAP_SYNCPROV"];
			$this->PIWIGO_INSTALLED=$_GLOBAL["PIWIGO_INSTALLED"];
			$this->APP_VBOXADDINTION_INSTALLED=$_GLOBAL["APP_VBOXADDINTION_INSTALLED"];
			$this->VIRTUALBOX_HOST=$_GLOBAL["VIRTUALBOX_HOST"];
			$this->APP_SABNZBDPLUS_INSTALLED=$_GLOBAL["APP_SABNZBDPLUS_INSTALLED"];
			if(isset($_GLOBAL["POSTSCREEN_INSTALLED"])){$this->POSTSCREEN_INSTALLED=$_GLOBAL["POSTSCREEN_INSTALLED"];}
			$this->IPSET_INSTALLED=$_GLOBAL["IPSET_INSTALLED"];
			$this->KASPERSKY_UPDATE_UTILITY_INSTALLED=$_GLOBAL["KASPERSKY_UPDATE_UTILITY_INSTALLED"];
			$this->APP_VNSTAT_INSTALLED=$_GLOBAL["APP_VNSTAT_INSTALLED"];
			$this->ASPELL_INSTALLED=$_GLOBAL["ASPELL_INSTALLED"];
			$this->MUNIN_CLIENT_INSTALLED=$_GLOBAL["MUNIN_CLIENT_INSTALLED"];
			$this->APACHE_MOD_AUTHNZ_LDAP=$_GLOBAL["APACHE_MOD_AUTHNZ_LDAP"];
			$this->CURLFTPFS_INSTALLED=$_GLOBAL["CURLFTPFS_INSTALLED"];
			$this->CIFS_INSTALLED=$_GLOBAL["CIFS_INSTALLED"];
			$this->ISCSI_INSTALLED=$_GLOBAL["ISCSI_INSTALLED"];
			$this->LXC_INSTALLED=$_GLOBAL["LXC_INSTALLED"];
			$this->AS_VPS_CLIENT=$_GLOBAL["AS_VPS_CLIENT"];
			$this->VLAN_INSTALLED=$_GLOBAL["VLAN_INSTALLED"];
			$this->LOSETUP_INSTALLED=$_GLOBAL["LOSETUP_INSTALLED"];
			$this->SNORT_INSTALLED=$_GLOBAL["SNORT_INSTALLED"];
			$this->APP_EYEOS_INSTALLED=$_GLOBAL["APP_EYEOS_INSTALLED"];
			$this->EYEOS_VERSION=$_GLOBAL["EYEOS_VERSION"];
			$this->APACHE_MOD_SECURITY=$_GLOBAL["APACHE_MOD_SECURITY"];
			$this->APACHE_MOD_EVASIVE=$_GLOBAL["APACHE_MOD_EVASIVE"];
			$this->APACHE_PROXY_MODE=$_GLOBAL["APACHE_PROXY_MODE"];
			$this->APP_GREENSQL_INSTALLED=$_GLOBAL["APP_GREENSQL_INSTALLED"];
			$this->IPTABLES_ACCOUNTING_EXISTS=$_GLOBAL["IPTABLES_ACCOUNTING_EXISTS"];
			$this->POWER_DNS_MYSQL=$_GLOBAL["POWER_DNS_MYSQL"];
			$this->POWERADMIN_INSTALLED=$_GLOBAL["POWERADMIN_INSTALLED"];
			$this->APP_AMANDA_INSTALLED=$_GLOBAL["APP_AMANDA_INSTALLED"];
			$this->PDNSSEC_INSTALLED=$_GLOBAL["PDNSSEC_INSTALLED"];
			$this->TOMCAT_VERSION=$_GLOBAL["TOMCAT_VERSION"];
			$this->TOMCAT_INSTALLED=$_GLOBAL["TOMCAT_INSTALLED"];
			$this->TOMCAT_JAVA=$_GLOBAL["TOMCAT_JAVA"];
			$this->TOMCAT_DIR=$_GLOBAL["TOMCAT_DIR"];	
			$this->APACHE_MOD_TOMCAT=$_GLOBAL["APACHE_MOD_TOMCAT"];
			$this->OPENEMM_INSTALLED=$_GLOBAL["OPENEMM_INSTALLED"];
			$this->MSKTUTIL_INSTALLED=$_GLOBAL["MSKTUTIL_INSTALLED"];
			$this->PIWIK_INSTALLED=$_GLOBAL["PIWIK_INSTALLED"];
			$this->JOOMLA17_INSTALLED=$_GLOBAL["JOOMLA17_INSTALLED"];
			$this->APACHE_MOD_GEOIP=$_GLOBAL["APACHE_MOD_GEOIP"];
			$this->APACHE_MOD_PAGESPEED=$_GLOBAL["APACHE_MOD_PAGESPEED"];
			$this->WORDPRESS_INSTALLED=$_GLOBAL["WORDPRESS_INSTALLED"];
			$this->APACHE_MOD_INFO=$_GLOBAL["APACHE_MOD_INFO"];
			$this->APACHE_MOD_STATUS=$_GLOBAL["APACHE_MOD_STATUS"];
			$this->APACHE_APPLIANCE=$_GLOBAL["APACHE_APPLIANCE"];
			$this->APACHE_MOD_SUEXEC=$_GLOBAL["APACHE_MOD_SUEXEC"];
			$this->APACHE_MOD_FCGID=$_GLOBAL["APACHE_MOD_FCGID"];
			
			$CGROUPS_INSTALLED=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/CGROUPS_INSTALLED"));
			if($CGROUPS_INSTALLED==1){
				$this->CGROUPS_INSTALLED=true;
			}else{
				$this->CGROUPS_INSTALLED=false;
			}
			$this->APACHE_MOD_LOGSSQL=$_GLOBAL["APACHE_MOD_LOGSSQL"];
			if(isset($_GLOBAL["squid_kerb_auth_path"])){$this->squid_kerb_auth_path=$_GLOBAL["squid_kerb_auth_path"];}
			
			
			
			if(isset($_GLOBAL["ISCSI_CLIENT_INSTALLED"])){$this->ISCSI_CLIENT_INSTALLED=$_GLOBAL["ISCSI_CLIENT_INSTALLED"];}
			if($this->LinuxDistriCode=="DEBIAN"){$this->AS_DEBIAN_FAMILY=true;}
			if($this->LinuxDistriCode=="UBUNTU"){$this->AS_DEBIAN_FAMILY=true;}
			
			if(isset($_GLOBAL["deduplication_installed"])){$this->deduplication_installed=$_GLOBAL["deduplication_installed"];}
			$this->EnableManageUsersTroughActiveDirectory=$_GLOBAL["EnableManageUsersTroughActiveDirectory"];
			$this->ActiveDirectoryCredentials=$_GLOBAL["ActiveDirectoryCredentials"];
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}
			if(!is_object($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$ArticaMetaEnabled=trim($sock->GET_INFO("ArticaMetaEnabled"));
			if($ArticaMetaEnabled==null){$sock->SET_INFO("ArticaMetaEnabled","0");}
			if($ArticaMetaEnabled==1){$this->ARTICA_META_ENABLED=true;}
			
			if($this->SQUID_INSTALLED){
				if(!isset($GLOBALS["SQUID_ICAP_ENABLED"])){
					$GLOBALS["SQUID_ICAP_ENABLED"]=false;
					$IsIcapClient=@file_get_contents("/etc/artica-postfix/settings/Daemons/IsIcapClient");
					if($IsIcapClient==null){
						$IsIcapClient=trim($sock->getFrameWork("squid.php?IsIcapClient=yes"));
						if($IsIcapClient==null){$IsIcapClient="FALSE";}
						@file_put_contents("/etc/artica-postfix/settings/Daemons/IsIcapClient", $IsIcapClient);
					}
					if($IsIcapClient=='TRUE'){$GLOBALS["SQUID_ICAP_ENABLED"]=true; }
				}
			}
			
			if(isset($GLOBALS["SQUID_ICAP_ENABLED"])){$this->SQUID_ICAP_ENABLED=$GLOBALS["SQUID_ICAP_ENABLED"]; }
			
			if(is_file("/home/artica/categories_databases/CATZ_ARRAY")){ $this->ARTICADB_INSTALLED=true;}
			

			$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
			$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
			$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
			
						
			if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
			if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
			if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}

			if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}			
			if($EnableRemoteStatisticsAppliance==1){
				$EnableMacAddressFilterCentral=$sock->GET_INFO("EnableMacAddressFilterCentral");
				if(!is_numeric($EnableMacAddressFilterCentral)){$EnableMacAddressFilterCentral=1;}
				$EnableMacAddressFilter=$EnableMacAddressFilterCentral;
			}
			
			if($this->SQUID_ARP_ACL_ENABLED){if($EnableMacAddressFilter==0){$this->SQUID_ARP_ACL_ENABLED=false;}}
			
			$pear=explode(";",$_GLOBAL["PEAR_PACKAGES"]);
			if(is_array($pear)){
				while (list ($num, $pp) = each ($pear) ){
					if(trim($pp)==null){continue;}
					$this->PEAR_PACKAGES[$pp]=true;
				}
			}
			
			if(!$this->APACHE_MOD_PYTHON){$this->APP_PYAUTHENNTLM=false;}
			
			$this->CPU_NUMBER=$_GLOBAL["CPU_NUMBER"];
			$this->LOAD_AVERAGE=$_GLOBAL["LOAD_AVERAGE"];
			
			if($this->OPENVPN_APPLIANCE){
				$this->SQUID_INSTALLED=false;
				$this->SAMBA_INSTALLED=false;
				$this->POSTFIX_INSTALLED=false;
				$this->cyrus_imapd_installed=false;
				$this->ZARAFA_INSTALLED=false;
				$this->APP_AUDITD_INSTALLED=false;
				$this->BACKUPPC_INSTALLED=false;
				$this->nmap_installed=false;
			}
			
			if($this->PROXYTINY_APPLIANCE){
				$this->SQUID_APPLIANCE=true;
			}
			
			if($this->KASPERSKY_WEB_APPLIANCE){
				$this->KAV4PROXY_INSTALLED=true;
				$this->POSTFIX_INSTALLED=false;
				$this->APP_AUDITD_INSTALLED=false;
				$this->BACKUPPC_INSTALLED=false;
				
			}
			$this->SetTimeZone();
			$page=$this->CurrentPageName();
			$this->SessionExpired();
			$this->aghjkyrtij();
			if($GLOBALS["posix_getuid"]==0){if($this->roundcube_installed){$this->roundcube_detect_version();}}
			
			//Parse privileges
			if(!$GLOBALS["AS_ROOT"]){
				if($GLOBALS["VERBOSE"]){
					echo "<span style='color:red'>->_ParsePrivieleges()" .__CLASS__.'/'.__FUNCTION__."/".basename(__FILE__)."/".__LINE__."</span><br>\n";
				}
				$priv=$this->_ParsePrivieleges();
				$this->priv=$this->_TranslateRights($priv);			
			}else{
				$this->AsSystemAdministrator=true;
				$this->AsInventoryAdmin=true;
				$this->AsAnAdministratorGeneric=true;
				$this->AsPostfixAdministrator=true;
				$this->AsSambaAdministrator=true;
				$this->AsWebMaster=true;
				$this->AsSquidAdministrator=true;
				$this->AsArticaMetaAdmin=true;
				$this->AsDansGuardianAdministrator=true;
				$this->AsSquidPersonalCategories=true;
				$this->AsArticaAdministrator=true;
				$this->AsDebianSystem=true;
				$this->AsArticaAdministrator=true;
				if($this->WEBSECURIZE){$GLOBALS["CHANGE_TEMPLATE"]="Websecurize";};
				if($this->BAMSIGHT){$GLOBALS["CHANGE_TEMPLATE"]="BamSight";};
				if($this->LANWANSAT){$GLOBALS["CHANGE_TEMPLATE"]="LanWanSAT";};
			}
			
			if(isset($_SESSION["uid"])){
				if($_SESSION["uid"]==-100){
					$this->AsSquidPersonalCategories=true;
				}
			}
			
			
			if($this->SHOREWALL_INSTALLED){
				$this->NicRoleArray["NAT"]="{shorewall_masq}";
			}
			
			if($this->dnsmasq_installed){
				$this->NicRoleArray["DHCP"]="{APP_DHCP}";
			
			}
			
			
			
			
			
			
			
		
	}
	
	

	
	private function postfixmutli(){
	if(!$this->POSTMULTI){return false;}
	$vers=explode(".",$this->POSTFIX_VERSION);
	if($vers[0]>=2){if($vers[1]>=5){return true;}}
	}
	
	function SessionExpired(){}
	
	
	private function SetTimeZone(){
		if(!function_exists("getLocalTimezone")){return;}
		if(isset($GLOBALS["TIMEZONES"])){
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}
		
		if(isset($_SESSION["TIMEZONES"])){
			@date_default_timezone_set($_SESSION["TIMEZONES"]);
			$GLOBALS["TIMEZONES"]=$_SESSION["TIMEZONES"];
			return;
			
		}
		
		if(isset($GLOBALS["GET_TIME_ZONE"])){
			$GLOBALS["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			$_SESSION["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}
		
		$sock=new sockets();
		$timezones=$sock->GET_INFO("timezones");
		if($timezones==null){$timezones="Europe/Dublin";}
		$GLOBALS["TIMEZONES"]=$timezones;
		$_SESSION["TIMEZONES"]=$timezones;
		@date_default_timezone_set($GLOBALS["TIMEZONES"]);
	}
	

	private function CurrentPageName(){$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);return $phpPage;}  		
	

	function LoadModulesEnabled(){
		$debugThis=false;
		if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
		if($GLOBALS["DEBUG_TEMPLATE"]){$debugThis=true;}
		$CurrentPageName=CurrentPageName();
		if($GLOBALS["posix_getuid"]<>0){
			if($GLOBALS["VERBOSE"]){
				$CurrentPageName="logon.php";
			}
		}
		if($this->LoadModulesEnabledParsed){return ;}
		$ldap=$this->ldapClass;
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
		if(!class_exists("sqlgrey")){include(dirname(__FILE__).'/class.sqlgrey.inc');}
		if(!class_exists("milter_greylist")){include(dirname(__FILE__) . '/class.milter.greylist.inc');}
		include_once(dirname(__FILE__).'/class.artica.inc');
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:includes done..; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
		
		if($debugThis){error_log("[{$_SESSION["uid"]}]::$CurrentPageName: includes done ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$artica=new artica_general($this->ldapClass);
		if($debugThis){error_log("[{$_SESSION["uid"]}]::$CurrentPageName: artica class ok ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		
		if($debugThis){writelogs($CurrentPageName.": sqlgrey($this->hostname,true)".__FUNCTION__.'/'.__LINE__);}		
		$sqlg=new sqlgrey($this->hostname,true);
		if($debugThis){writelogs($CurrentPageName.": milter_greylist(true)".__FUNCTION__.'/'.__LINE__);}
		$milter=new milter_greylist(true);
		if($debugThis){writelogs($CurrentPageName.": ".__FUNCTION__.'/'.__LINE__);}
		
		
		if($sock->GET_INFO("kavmilterEnable")==1){$this->KAVMILTER_ENABLED=1;}else{$this->KAVMILTER_ENABLED=0;}
		if($sock->GET_INFO("InstantSearchEnable")==1){$this->INSTANSEARCH_ENABLE=true;}
		$this->MilterGreyListEnabled=$sock->GET_INFO("MilterGreyListEnabled");
		//$this->SMTP_LEVEL=$hash["ArticaMailAddonsLevel"];
		//$this->ArticaFilterEnabled=$hash["ArticaFilterEnabled"];
		$this->AmavisFilterEnabled=$sock->GET_INFO('EnableAmavisDaemon');
		$this->KasxFilterEnabled=$artica->KasxFilterEnabled;
		//$this->NTPDEnabled=$hash["NTPDEnabled"];
		//$this->OBMEnabled=$hash["OBMEnabled"];
		$this->SQLGreyEnabled=$sqlg->SqlGreyEnabled;
		//$this->MailFromdEnabled=$hash["MailFromdEnabled"];
		//$this->IptablesEnabled=$hash["IptablesEnabled"];
		$this->ApacheArticaEnabled=$artica->ApacheArticaEnabled;
		if($this->ApacheArticaEnabled==null){$this->ApacheArticaEnabled=0;}
		$this->ClamavMilterEnabled=$artica->ClamavMilterEnabled;
		$this->SpamAssMilterEnabled=$artica->SpamAssMilterEnabled;
		$this->spfmilterEnabled=$artica->spfmilterEnabled;
		$this->MimeDefangEnabled=$artica->MimeDefangEnabled;
		$this->DkimFilterEnabled=$artica->DkimFilterEnabled;
		$this->EnableFetchmail=$artica->EnableFetchmail;
		$this->EnableFDMFetch=$artica->EnableFDMFetch;
		$this->P3ScanEnabled=$artica->P3ScanEnabled;
		$this->RoundCubeHTTPEngineEnabled=$artica->RoundCubeHTTPEngineEnabled;
		$this->sTunnel4enabled=$artica->sTunnel4enabled;
		$this->MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
		$this->EnableMilterBogom=$artica->EnableMilterBogom;
		$this->EnableMysqlFeatures=$artica->EnableMysqlFeatures;
		$this->EnableMilterSpyDaemon=$artica->EnableMilterSpyDaemon;
		$this->EnableCollectdDaemon=$artica->EnableCollectdDaemon;
		$this->EnableAmavisDaemon=$artica->EnableAmavisDaemon;
		$this->JCheckMailEnabled=$artica->JCheckMailEnabled;
		$this->EnableAmavisBackup=$artica->EnableAmavisBackup;
		$this->KAVMILTER_ENABLED=$artica->kavmilterEnable;
		$this->EnableRemoteAddressBook=$artica->EnableRemoteAddressBook;
		$this->EnablePerUserRemoteAddressBook=$artica->EnablePerUserRemoteAddressBook;
		$this->EnableNonEncryptedLdapSession=$artica->EnableNonEncryptedLdapSession;
		//writelogs("KasxFilterEnabled={$hash["KasxFilterEnabled"]},ApacheArticaEnabled=$this->ApacheArticaEnabled ",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(!$this->AMAVIS_INSTALLED){$this->EnableAmavisDaemon==0;}
		
		if($this->SMTP_LEVEL==null){$this->SMTP_LEVEL=0;}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}: DONE; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
		$this->LoadModulesEnabledParsed=true;
		}
		
function ORGANISTATION_FROM_USER(){
	if(!function_exists("ldap_connect")){return array();}
	if($this->ldapClass->ldapFailed){return null;}
	$hash=$this->ldapClass->Hash_Get_ou_from_users($_SESSION["uid"],1);
	if(is_array($hash)){return $hash[0];}
	}

public function EnablePDNS(){
	$sock=new sockets();
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	if(!$this->POWER_DNS_INSTALLED){return 0;}
	if($this->EnableDNSMASQ()==1){return 0;}
	
	
	if($this->APP_CHILLI_INSTALLED){
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if($EnableChilli==1){return 0;}
	}
	
	return $EnablePDNS;
}
public function EnableDNSMASQ(){
	$sock=new sockets();
	if(!$this->dnsmasq_installed){return 0;}
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	return $EnableDNSMASQ;
}

private function roundcube_detect_version(){
	$webfolder=$this->roundcube_folder;
	$index_php="$this->roundcube_folder/index.php";
	if(!isset($_GET["__ROUNDCUBE_VER"])){$_GET["__ROUNDCUBE_VER"]=null;}
		if($_GET["__ROUNDCUBE_VER"]==null){
			if(!is_file($index_php)){return null;}
			$pattern="#Version\s+([0-9\.a-zA-Z\-]+)#";
			$datas=explode("\n",@file_get_contents($index_php));
			if(!is_array($datas)){return null;}
			while (list ($num, $line) = each ($datas)){
				if(preg_match($pattern,$line,$re)){
					$this->roundcube_version=$re[1];
					$_GET["__ROUNDCUBE_VER"]=$re[1];
					break;
				}
			}
		}
		
	if(!isset($_GET["__ROUNDCUBE_INTVER"])){
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)$#",$this->roundcube_version,$re)){
			if(strlen($re[3])==1){$re[3]="0{$re[3]}";}
			$this->roundcube_intversion=$re[1].$re[2].$re[3];
			$_GET["__ROUNDCUBE_INTVER"]=$this->roundcube_intversion;
			return null;
		}
		
		if(preg_match("#^([0-9]+)\.([0-9]+)$#",$this->roundcube_version,$re)){
			$this->roundcube_intversion=$re[1].$re[2].'0';
			$_GET["__ROUNDCUBE_INTVER"]=$this->roundcube_intversion;
			return null;
		}	
		
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)-#",$this->roundcube_version,$re)){
			
			$this->roundcube_intversion=$re[2].$re[3]."0";
			$_GET["__ROUNDCUBE_INTVER"]=$this->roundcube_intversion;
			return null;
		}	
	}else{
		$this->roundcube_intversion=$this->roundcube_version=$_GET["__ROUNDCUBE_INTVER"];
	}

}


public function IsSquidReverse(){
	if(isset($GLOBALS["SQUID_IsSquidReverse"])){return $GLOBALS["SQUID_IsSquidReverse"];}
	if(!$this->SQUID_INSTALLED){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
	if($this->SQUID_REVERSE_APPLIANCE){$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
	$sock=new sockets();
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
	if($SquidActHasReverse==1){$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
}

private function build_user_left_menus(){
	
	$array=array();
	if($this->BuildLeftMenus_can_manage_org()){
			if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
			$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
			$AllowArticaMetaAddUsers=$sock->GET_INFO("AllowArticaMetaAddUsers");
			$OU=trim($_GET["ou"]);
			if($OU==null){
				include_once(dirname(__FILE__).'/class.user.inc');
				$us=new user($_SESSION["uid"]);
				$OU=$us->ou;
			}

			if($OU<>null){
				if(strlen($OU)>13){$OU_TEXT=substr($OU,0,10).'...';}else{$OU_TEXT=$OU;}
				
				$OU_encoded=base64_encode($OU);
				$array[$OU_TEXT]["MAIN"]="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$OU_encoded&encoded=yes');";
				$array[$OU_TEXT]["SUB"]["{main_parameters}"]="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
				
				if($this->AsOrgPostfixAdministrator){
					if($this->POSTFIX_INSTALLED){
						if($EnablePostfixMultiInstance==1){
							$array[$OU_TEXT]["SUB"]["{messaging_servers}"]="javascript:Loadjs('domains.postfix.multi.php?ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
						}
					}
				}
				
				
				$array[$OU_TEXT]["SUB"]["{domains}"]="javascript:Loadjs('domains.edit.domains.php?js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
				if(!$this->ARTICA_META_ENABLED){
						$array[$OU_TEXT]["SUB"]["{add_user}"]="javascript:Loadjs('domains.add.user.php?ou=$OU_encoded&encoded=yes');";
				}else{
					if($AllowArticaMetaAddUsers==1){
						$array[$OU_TEXT]["SUB"]["{add_user}"]="javascript:Loadjs('domains.add.user.php?ou=$OU_encoded&encoded=yes');";
					}
				}
				
				
				
				
				$array[$OU_TEXT]["SUB"]["{members}"]="javascript:Loadjs('domains.manage.org.index.php?finduser-js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
				$array[$OU_TEXT]["SUB"]["{groups}"]="javascript:Loadjs('domains.edit.group.php?ou=$OU_encoded&js=yes&encoded=yes&in-front-ajax=yes');";
				$array[$OU_TEXT]["SUB"]["{search}"]="javascript:Loadjs('domains.find.user.php?ou=$OU_encoded&encoded=yes');";
			
			}	
		}
			
			
		$menus=$this->BuildArticaMenus($array,$sub_array_pages);
		$this->toplinks=$menus;
		return $menus;			
	
	
	
}


   function BuildLeftMenus_can_manage_org(){
   	if($this->AsOrgAdmin){return true;}
   	if($this->AllowAddUsers){return true;}
   	if($this->AsMessagingOrg){return true;}
   	if($this->AsOrgPostfixAdministrator){return true;}
   	if($this->AseMailCampaignsAdmin){return true;}
   	return false;
   }

	
	
	function BuildLeftMenus(){
		$debugThis=false;
		if($GLOBALS["DEBUG_TEMPLATE"]){$debugThis=true;}
		$array=array();
		$sub_array_pages=array();
		$sock=new sockets();
		$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		$EnableInterfaceMailCampaigns=$sock->GET_INFO("EnableInterfaceMailCampaigns");
		$InstantIpTablesInLeftMenu=$sock->GET_INFO("InstantIpTablesInLeftMenu");
		$FreeWebLeftMenu=$sock->GET_INFO("FreeWebLeftMenu");
		$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
		$EnableArticaSMTPStatistics=$sock->GET_INFO("EnableArticaSMTPStatistics");
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		$EnablePDNS=$sock->GET_INFO("EnablePDNS");
		$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
		$EnableLXCINLeftMenus=$sock->GET_INFO("EnableLXCINLeftMenus");
		if(!is_numeric($EnableArticaSMTPStatistics)){$EnableArticaSMTPStatistics=1;}
		$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
		$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");	
		$EnableGroupWareScreen=$sock->GET_INFO("EnableGroupWareScreen");
		if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
		if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
		if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
		if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=1;}
		if(!is_numeric($EnableLXCINLeftMenus)){$EnableLXCINLeftMenus=1;}
		if(!is_numeric($EnableGroupWareScreen)){$EnableGroupWareScreen=1;}


		$ASDEBIAN=false;
		if($this->LinuxDistriCode=="DEBIAN"){$ASDEBIAN=true;}
		if($this->LinuxDistriCode=="UBUNTU"){$ASDEBIAN=true;}	
			
	if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}: menus->BuildLeftMenus() function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
		
		if($InstantIpTablesInLeftMenu==null){$InstantIpTablesInLeftMenu=1;}
		if(!is_numeric($FreeWebLeftMenu)){$FreeWebLeftMenu=1;}		
		
		if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		if($_SESSION["UsersInterfaceDatas"]<>null){
			if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} UsersInterfaceDatas<>null".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
			return null;
		}
		if(is_array($_SESSION["MainLeftMenus"])){
			$this->main_left_menus=$_SESSION["MainLeftMenus"];
			if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} MainLeftMenus<>null, return".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
			return $_SESSION["MainLeftSubPagesHTML"];
		}
		
		if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->LoadModulesEnabled ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$this->LoadModulesEnabled();
		if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->LoadModulesEnabled done".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->BuildBackupFeature()".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$this->BuildBackupFeature();
		if(isset($GLOBALS["CURRENT_PAGE"])){$page=$GLOBALS["CURRENT_PAGE"];}else{$page=CurrentPageName();}

		if($page=='imap.index.php'){return null;}
		if(!isset($_SESSION["uid"])){return null;}
		if(!isset($_GET["ou"])){$_GET["ou"]=null;}
	
		if($_GET["ou"]==null){
			if($this->AsArticaAdministrator){
				if($_COOKIE["MyFavouriteOrganization"]==null){
					if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->this->ldapClass->hash_get_ou(false)".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
					$hash=$this->ldapClass->hash_get_ou(false);
					if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->OU={$hash[0]}".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
					$_GET["ou"]=$hash[0];
				}else{
					$_GET["ou"]=$_COOKIE["MyFavouriteOrganization"];
				}
			}else{
				if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ->this->ORGANISTATION_FROM_USER()".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
				$_GET["ou"]=$this->ORGANISTATION_FROM_USER();
			}
			
		}
		if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ORG={$_GET["ou"]} done".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		
		$SMTP_LEVEL=$this->SMTP_LEVEL;
		
		if(!$this->AsAnAdministratorGeneric){
			if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} this->build_user_left_menus()".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
			return $this->build_user_left_menus();
		}		

		if($_SESSION["uid"]==-100){$asSuperAdmin=true;}
		
		if($this->AsArticaAdministrator==true){
				$sub_array_pages["ORG"]["domains.index.php"]='MAIN';
			}
		
		if($this->AsPostfixAdministrator==true){
			if($this->POSTFIX_INSTALLED==true){
				$sub_array_pages["POSTFIX"]["postfix.index.php"]='MAIN';
			}
		}
		
		if($this->AsSambaAdministrator==true){
			if(!$this->KASPERSKY_SMTP_APPLIANCE){
				if($this->SAMBA_INSTALLED){
					$sub_array_pages["{fileshare}"]["fileshares.index.php"]='MAIN';
							
					}
			}
		}		
		
	

			

		
		$array["{index page}"]["MAIN"]="javascript:document.location.href='users.index.php'";
		$array["{index page}"]["SUB"]["{master_panel}"]="javascript:ConfigureYourserver();";
		$array["{index page}"]["SUB"]["{language}"]="javascript:Loadjs('chg.language.php');";
		if($this->AsSystemAdministrator){
			$array["{index page}"]["SUB"]["{backup}"]="javascript:Loadjs('backup.tasks.php?in-front-ajax=yes');";

			if($this->APP_AMANDA_INSTALLED){
				$array["{index page}"]["SUB"]["{APP_AMANDA}"]="javascript:Loadjs('amanda.php');";
			}
			
			
			if(!$this->KASPERSKY_WEB_APPLIANCE){
				if($this->BACKUPPC_INSTALLED){
					$array["{index page}"]["SUB"]["{APP_BACKUPPC}"]="javascript:Loadjs('backup-pc.index.php?in-front-ajax=yes');";
				}
			if($this->OCSI_INSTALLED){
				$array["{index page}"]["SUB"]["{APP_OCSI}"]="javascript:Loadjs('ocs.ng.php?in-front-ajax=yes');";
				}
			}
			
			
		}

		
			
		
			$OU=trim($_GET["ou"]);
			if($OU==null){
				include_once(dirname(__FILE__).'/class.user.inc');
				$us=new user($_SESSION["uid"]);
				$OU=$us->ou;
			}
			if($this->AllowAddUsers){writelogs("AllowAddUsers=TRUE && ou=$OU",__FUNCTION__,__FILE__);}else{writelogs("AllowAddUsers=FALSE && ou=$OU",__FUNCTION__,__FILE__);}

			if($this->AllowViewStatistics==true){
				
				$sub_array_pages["STAT"]['statistics.index.php']='MAIN';
				if($this->mailgraph_installed==true){
					$sub_array_pages["STAT"]['statistics.mailgraph.php']='{emails_flow}';
				}
				if($this->queuegraph_installed==true){
					$sub_array_pages["STAT"]['statistics.queuegraph.php']='{queue_flow}';
				}
				if($this->yorel_installed==true){
					$sub_array_pages["STAT"]['statistics.systems.yorel.php']='{system_perfomances}';
					$sub_array_pages["STAT"]['statistics.systems-storage.yorel.php']='{system_perfomances_datas}';
				}
				
				
				
			}

			
		if($this->AsArticaAdministrator==true){
			$array["{organizations}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.index.php?js=yes')";
			$array["{organizations}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.index.php?js=yes')";
			
			if($GLOBALS["VERBOSE"]){echo "<li>Starting this->ldapClass->hash_get_ou(true)</li>";}
			$ous=$this->ldapClass->hash_get_ou(true);
			if(is_array($ous)){
				while (list ($num, $ligne) = each ($ous) ){
					$OU_encoded=base64_encode($OU);
					$array["{organizations}"]["SUB"][$num]="javascript:AnimateDiv('BodyContent');Loadjs('domains.manage.org.index.php?js=yes&ou=$num&in-front-ajax=yes');";
				}
			}
			if(!$this->ARTICA_META_ENABLED){
				$array["{organizations}"]["SUB"]["{add}"]="javascript:loadjs('organization.js.php?add-ou=yes');;";
			}else{
				if($sock->GET_INFO("AllowArticaMetaAddUsers")==1){
					$array["{organizations}"]["SUB"]["{add}"]="javascript:loadjs('organization.js.php?add-ou=yes');;";
				}
			}
			
		
		
		}
		
		
		if($this->BuildLeftMenus_can_manage_org()){
			if($OU<>null){
				if(strlen($OU)>13){$OU_TEXT=substr($OU,0,10).'...';}else{$OU_TEXT=$OU;}
				$OU_encoded=base64_encode($OU);
				$array[$OU_TEXT]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.manage.org.index.php?js=yes&ou=$OU_encoded&encoded=yes');";
				$array[$OU_TEXT]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.manage.org.index.php?js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
				
				if($this->AsOrgPostfixAdministrator){
					if($this->POSTFIX_INSTALLED){
						if($EnablePostfixMultiInstance==1){
							$array[$OU_TEXT]["SUB"]["{smtp_relays}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.postfix.multi.php?ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
						}
					}
				}
								
				$array[$OU_TEXT]["SUB"]["{domains}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.edit.domains.php?js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";

				if($EnableInterfaceMailCampaigns==1){
					if($this->EMAILRELAY_INSTALLED){
					if($this->AseMailCampaignsAdmin){
					$array[$OU_TEXT]["SUB"]["{email_campaigns}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.emailings.php?popup-js=yes&ou=$OU_encoded');";
				}}}				
				
				
				if(!$this->EnableManageUsersTroughActiveDirectory){
					if(!$this->ARTICA_META_ENABLED){
							$array[$OU_TEXT]["SUB"]["{add_user}"]="javascript:Loadjs('domains.add.user.php?ou=$OU_encoded&encoded=yes');";
					}else{
						if($sock->GET_INFO("AllowArticaMetaAddUsers")==1){
							$array[$OU_TEXT]["SUB"]["{add_user}"]="javascript:Loadjs('domains.add.user.php?ou=$OU_encoded&encoded=yes');";
						}
					}
				}
				
				
				$array[$OU_TEXT]["SUB"]["{members}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.manage.org.index.php?finduser-js=yes&ou=$OU_encoded&encoded=yes&in-front-ajax=yes');";
				$array[$OU_TEXT]["SUB"]["{groups}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.edit.group.php?ou=$OU_encoded&js=yes&encoded=yes&in-front-ajax=yes');";
				$array[$OU_TEXT]["SUB"]["{search}"]="Loadjs('domains.find.user.php?ou=$OU_encoded&encoded=yes');";
				if(!$this->EnableManageUsersTroughActiveDirectory){
					if($EnableGroupWareScreen==1){
						$array[$OU_TEXT]["SUB"]["{groupwares}"]="javascript:AnimateDiv('BodyContent');Loadjs('domains.manage.org.index.php?ou=$OU_encoded&encoded=yes&groupwares-in-front-ajax=yes');";
					}
				}
				

			}
		}
		
		if($this->AsArticaAdministrator==true){
			$array["system"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('system.index.php?js=yes')";
			$array["system"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('system.index.php?js=yes')";
			$array["system"]["SUB"]["{artica_settings}"]="javascript:AnimateDiv('BodyContent');Loadjs('artica.settings.php?js=yes');";
			if(!$this->AS_VPS_CLIENT){
				$array["system"]["SUB"]["{left_menu_internal_hard_drives}"]="javascript:AnimateDiv('BodyContent');Loadjs('system.internal.disks.php?in-front-ajax=yes')";
			}
			
			if($this->autofs_installed){
				$array["system"]["SUB"]["{automount}"]="javascript:AnimateDiv('BodyContent');Loadjs('autofs.php')";
			}
			
			if($this->UPDATE_UTILITYV2_INSTALLED){
				$array["system"]["SUB"]["UpdateUtility"]="javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','UpdateUtility.php');";
				
			}
			
			$array["system"]["SUB"]["{left_menu_net_settings}"]="javascript:AnimateDiv('BodyContent');Loadjs('system.nic.config.php?js=yes&in-front-ajax=yes')";
			$array["system"]["SUB"]["{mysql_settings}"]="javascript:AnimateDiv('BodyContent');Loadjs('system.mysql.php')";
			
			
			
			if($sock->GET_INFO("WifiCardOk")==1){$array["system"]["SUB"]["WIFI"]="javascript:AnimateDiv('BodyContent');Loadjs('system.wifi.php')";}
			if($this->dhcp_installed){
					$array["system"]["SUB"]["{APP_DHCP}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.gateway.php?index_dhcp=yes&in-front-ajax=yes')";
			}
			
			if($sock->GET_INFO("QOSInLeftMenu")==1){
				$array["system"]["SUB"]["{Q.O.S}"]="javascript:AnimateDiv('BodyContent');Loadjs('qos.php?in-front-ajax=yes')";
				
			}
			

			
			if($this->PPTPD_INSTALLED){
				$array["system"]["SUB"]["VPN PPTP"]="javascript:AnimateDiv('BodyContent');Loadjs('pptpd.php?infront=yes')";
			}
			
			if($this->PPTP_INSTALLED){
				$array["system"]["SUB"]["VPN PPTP"]="javascript:AnimateDiv('BodyContent');Loadjs('pptpd.php?infront=yes')";
			}	

			
			if($this->IPSEC_INSTALLED){
				//$array["system"]["SUB"]["VPN IPSEC"]="javascript:AnimateDiv('BodyContent');Loadjs('ipsec.php?infront=yes')";

			}
			
			if($this->FREERADIUS_INSTALLED){
				$array["system"]["SUB"]["{APP_FREERADIUS}"]="javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','freeradius.php');";
			}
			
			if($this->AsDnsAdministrator){
				if($this->POWER_DNS_INSTALLED){$array["system"]["SUB"]["{APP_PDNS}"]="javascript:AnimateDiv('BodyContent');Loadjs('pdns.php?in-front-ajax=yes')";}
				if($this->dnsmasq_installed){$array["system"]["SUB"]["DNSMasq"]="javascript:AnimateDiv('BodyContent');Loadjs('dnsmasq.index.php')";}
				
			}
			
			if($this->APACHE_INSTALLED){
					if($FreeWebLeftMenu==1){
						$array["system"]["SUB"]["FreeWebs"]="javascript:AnimateDiv('BodyContent');Loadjs('freeweb.php?in-front-ajax=yes')";
					}
				}
			
			
			if($this->crossroads_installed){
					$array["system"]["SUB"]["{load_balancing}"]="javascript:AnimateDiv('BodyContent');Loadjs('crossroads.index.php')";
				}

			if($this->IPTABLES_ACCOUNTING_EXISTS){
				$array["system"]["SUB"]["{APP_IPTACCOUNT}"]="javascript:AnimateDiv('BodyContent');Loadjs('iptaccount.php')";
			}

			if($this->APT_MIRROR_INSTALLED){
				if($sock->GET_INFO("EnableAptMirror")==1){
					$array["system"]["SUB"]["{REPOSITORY_DEB_MIRROR}"]="javascript:AnimateDiv('BodyContent');Loadjs('apt-mirror.php?in-front-ajax=yes')";
				}
			}
			
			
			$array["system"]["SUB"]["{APP_OPENSSH}"]="javascript:Loadjs('sshd.php?in-front-ajax=yes')";
			
			if($this->OPENEMM_INSTALLED){$array["system"]["SUB"]["{APP_OPENEMM}"]="javascript:AnimateDiv('BodyContent');Loadjs('openemm.php')";}			
			if($this->APP_SABNZBDPLUS_INSTALLED){$array["system"]["SUB"]["{APP_SABNZBDPLUS}"]="javascript:AnimateDiv('BodyContent');Loadjs('sabnzbdplus.php?in-front-ajax=yes')";}
			
			if($this->OPENVPN_INSTALLED){
				$array["{APP_OPENVPN}"]["MAIN"]="javascript:Loadjs('index.openvpn.php?infront=yes')";
				$array["{APP_OPENVPN}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.openvpn.php?infront=yes');";
				$array["{APP_OPENVPN}"]["SUB"]["{server}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.openvpn.php?server-settings-js=yes')";
				$array["{APP_OPENVPN}"]["SUB"]["{client}"]="javascript:AnimateDiv('BodyContent');Loadjs('openvpn.servers-connect.php?inline-js=yes')";
				
				$array["{APP_OPENVPN}"]["SUB"]["{sessions}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.openvpn.php?session-js=yes')";
				$array["{APP_OPENVPN}"]["SUB"]["{REMOTE_SITES_VPN}"]="javascript:AnimateDiv('BodyContent');Loadjs('openvpn.remotesites.php?js-inline=yes')";
				$array["{APP_OPENVPN}"]["SUB"]["{events}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.openvpn.php?events-js=yes')";
				
				
			}			
			
			
		}
		
		
		if($this->AsVirtualBoxManager){
			if($this->VIRTUALBOX_INSTALLED){
				$array["{virtual_machines}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('virtualbox.php')";
				$array["{virtual_machines}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('virtualbox.php');";
				$array["{virtual_machines}"]["SUB"]["{virtualbox_manager}"]="javascript:s_PopUp('virtualbox/index.html','1024','768')";
			}
			
			if($this->THINSTATION_INSTALLED){
				$array["{virtual_machines}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('virtualbox.php')";
				$array["{virtual_machines}"]["SUB"]["{thinclients}"]="javascript:AnimateDiv('BodyContent');Loadjs('virtualbox.php?by-thinclients=yes');";
			}
			
			
			if($this->LXC_INSTALLED){
				if($EnableLXCINLeftMenus==1){
					$array["{virtual_machines}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('virtualbox.php')";
					$array["{virtual_machines}"]["SUB"]["{VPS_SERVERS}"]="javascript:AnimateDiv('BodyContent');Loadjs('lxc.index.php')";
				}
			}
			
		}
		
		
		if($this->AsSambaAdministrator){
			if($this->APP_AUDITD_INSTALLED){
				$EnableAuditd=$sock->GET_INFO("EnableAuditd");
				if($EnableAuditd==null){$EnableAuditd=1;}
				if($EnableAuditd==1){
					$array["system"]["SUB"]["{APP_AUDITD}"]="javascript:AnimateDiv('BodyContent');Loadjs('auditd.php?in-front-ajax=yes')";
				}
			}
		}
			

			
	if(!$this->OPENVPN_APPLIANCE){
			if($this->AsSambaAdministrator){
				if($this->SAMBA_INSTALLED){
					$array["{fileshare}"]["MAIN"]="javascript:Loadjs('fileshares.index.php?js=yes')";
					$array["{fileshare}"]["SUB"]["{main_parameters}"]="javascript:Loadjs('fileshares.index.php?js=yes');";
					$array["{fileshare}"]["SUB"]["{wins_server}"]="javascript:AnimateDiv('BodyContent');Loadjs('samba.wins.php');";
					$array["{fileshare}"]["SUB"]["{shared_folders}"]="javascript:AnimateDiv('BodyContent');Loadjs('samba.index.php?script=yes&js-shared_folders=yes');";
					$array["{fileshare}"]["SUB"]["{virtual_servers}"]="javascript:AnimateDiv('BodyContent');Loadjs('samba.virtual-servers.php?js-inline=yes');";
					$array["{fileshare}"]["SUB"]["SSLBridge"]="javascript:s_PopUpFull('sslbridge/index.php',900,900,'SSLBridge For Artica')";
					$array["{fileshare}"]["SUB"]["{explorer}"]="javascript:Loadjs('tree.php');";
					$array["{fileshare}"]["SUB"]["{SHARE_FOLDER}"]="javascript:Loadjs('browse-disk.php?with-samba=yes');";
					if($this->CUPS_INSTALLED){
						$array["{fileshare}"]["SUB"]["{printers}"]="javascript:AnimateDiv('BodyContent');Loadjs('cups.index.php?in-front-ajax=yes');";
					}
					$array["{fileshare}"]["SUB"]["{computers}"]="javascript:AnimateDiv('BodyContent');Loadjs('computer-browse.php?in-front-ajax=yes');";
					}
			}
			
			
			if($this->AsSystemAdministrator){
				if($this->PUREFTP_INSTALLED){
					$array["{fileshare}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes')";
					$array["{fileshare}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes');";
				}
			}
				
			if($this->AsSystemAdministrator){
				if($this->RSYNC_INSTALLED){
					$array["{fileshare}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes')";
					$array["{fileshare}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes');";
					$array["{fileshare}"]["SUB"]["{explorer}"]="javascript:Loadjs('tree.php');";
				}	
			}
			
			
			if($this->AsSystemAdministrator){
				if($this->MLDONKEY_INSTALLED){
					$array["{fileshare}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes')";
					$array["{fileshare}"]["SUB"]["{APP_MLDONKEY}"]="javascript:AnimateDiv('BodyContent');Loadjs('mldonkey.php?in-front-ajax=yes');";
					$array["{fileshare}"]["SUB"]["{explorer}"]="javascript:Loadjs('tree.php');";
				}	
			}
	
			if($this->AsSystemAdministrator){
				if($this->GLUSTER_INSTALLED){
					$array["{fileshare}"]["MAIN"]="javascript:AnimateDiv('BodyContent');Loadjs('fileshares.index.php?js=yes')";
					$array["{fileshare}"]["SUB"]["{APP_CLUSTER}"]="javascript:AnimateDiv('BodyContent');Loadjs('gluster.samba.php?in-front-ajax=yes');";
					$array["{fileshare}"]["SUB"]["{explorer}"]="javascript:Loadjs('tree.php');";
				}	
			}
	}
		
		
		
	
		
		
		if($this->SQUID_INSTALLED==true){
			if($this->AsSquidAdministrator==true){
			$sock=new sockets();
			$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
			$EnableOldSquidMenus=trim($sock->GET_INFO("EnableOldSquidMenus"));
			if(!is_numeric($EnableOldSquidMenus)){$EnableOldSquidMenus=0;}
			
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($SQUIDEnable==1){
				$array["{web_proxy}"]["MAIN"]="javascript:AnimateDiv('BodyContent');SquidMainQuickLinks()";
				$array["{web_proxy}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');SquidMainQuickLinks()";
				
				if($EnableOldSquidMenus==1){
					$array["{web_proxy}"]["MAIN"]="javascript:Loadjs('squid.newbee.php?yes')";
					$array["{web_proxy}"]["SUB"]["New menu"]="javascript:AnimateDiv('BodyContent');SquidMainQuickLinks()";
					$array["{web_proxy}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('squid.newbee.php?yes')";
					$array["{web_proxy}"]["SUB"]["main beta"]="javascript:AnimateDiv('BodyContent');SquidMainQuickLinks()";
					
					
					$array["{web_proxy}"]["SUB"]["{add_websites}"]="javascript:Loadjs('squid.visited.php?add-www=yes')";
					$array["{web_proxy}"]["SUB"]["{visited_websites}"]="javascript:Loadjs('squid.visited.php')";
					
					
					
					if($SquidActHasReverse<>1){if($this->DANSGUARDIAN_INSTALLED){$array["{web_proxy}"]["SUB"]["{DANSGUARDIAN_RULES}"]="javascript:AnimateDiv('BodyContent');Loadjs('dansguardian.index.php?js=yes')";}}
					if($SquidActHasReverse<>1){if($this->SQUIDGUARD_INSTALLED){$array["{web_proxy}"]["SUB"]["{DANSGUARDIAN_RULES}"]="javascript:AnimateDiv('BodyContent');Loadjs('dansguardian.index.php?js=yes')";}}
					if($SquidActHasReverse<>1){if($this->APP_UFDBGUARD_INSTALLED){$array["{web_proxy}"]["SUB"]["{DANSGUARDIAN_RULES}"]="javascript:AnimateDiv('BodyContent');Loadjs('dansguardian.index.php?js=yes')";}}					
					if($SquidActHasReverse<>1){if($this->C_ICAP_INSTALLED){$array["{web_proxy}"]["SUB"]["{DANSGUARDIAN_RULES}"]="javascript:AnimateDiv('BodyContent');Loadjs('dansguardian.index.php?js=yes')";}}
		
					
					
					$array["{web_proxy}"]["SUB"]["{bandwith_limitation}"]="javascript:AnimateDiv('BodyContent');Loadjs('squid.bandwith.php?js=yes')";
					$cachemgr_left_menu=$sock->GET_INFO("cachemgr_left_menu");
					
					if($cachemgr_left_menu==1){
						$sql="SELECT servername,useSSL FROM freeweb WHERE `groupware`='cachemgr' LIMIT 0,1";
						$q=new mysql();
						$prefix="http";
						$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
						if($ligne["useSSL"]==1){$FreeWebListenPort=$FreeWebListenSSLPort;$prefix="https";}
						$array["{web_proxy}"]["SUB"]["{cachemgr}"]="javascript:s_PopUpFull('$prefix://{$ligne["servername"]}:$FreeWebListenPort',800,800,'Squid Cache Manager')";
						
					}
					$array["{web_proxy}"]["SUB"]["{statistics}"]="javascript:s_PopUpFull('squid.logon.php',900,900,'Proxy statistics')";
					$array["{web_proxy}"]["SUB"]["{performance}"]="javascript:AnimateDiv('BodyContent');Loadjs('squid.cached.sitesinfos.php?js=yes');";
					if($this->SARG_INSTALLED){$array["{web_proxy}"]["SUB"]["SARG stats."]="javascript:s_PopUpFull('squid/index.html',900,900,'SARG')";}
					}	
				}else{
					if($this->KAV4PROXY_INSTALLED){$array=$this->KAV4_PROXY_MENUS($array);}
				}
			}
		}else{
			
			if(($this->KAV4PROXY_INSTALLED) OR ($this->KASPERSKY_WEB_APPLIANCE)){
				if($this->AsSquidAdministrator==true){$array=$this->KAV4_PROXY_MENUS($array);}
			}
			
			
		}
		
		if($this->POSTFIX_INSTALLED){
			if($this->AsPostfixAdministrator==true){
				$array["{messaging}"]["MAIN"]="javascript:Loadjs('postfix.index.php')";
				$array["{messaging}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.index.php')";
				
				if($EnablePostfixMultiInstance==1){
					$array["{messaging}"]["SUB"]["{multiple_instances}"]="javascript:Loadjs('postfix.multiples.php')";
				}
				
				$array["{messaging}"]["SUB"]["{queue_management}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.queue.monitoring.php?inline-js=yes')";
				
				
				
				$array["{messaging}"]["SUB"]["{security}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.security.php')";
				$array["{messaging}"]["SUB"]["{APP_POSTFWD2}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master')";
				
				
				
				$array["{messaging}"]["SUB"]["{postfix_finder}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.finder.php?js=yes')";
				$array["{messaging}"]["SUB"]["{search}"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.search.php')";
				if($InstantIpTablesInLeftMenu==1){
					$array["{messaging}"]["SUB"]["Instant Iptables"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.iptables.php?in-front-ajax=yes')";
				}
				
				if($this->CLUEBRINGER_INSTALLED){
					if($EnableCluebringer==1){
						$array["{messaging}"]["SUB"]["Policyd Adm."]="javascript:s_PopUp('cluebringer/index.php','800','800')";
					}
				}
				
				if($this->OPENEMM_INSTALLED){
					$array["{messaging}"]["SUB"]["{APP_OPENEMM}"]="javascript:AnimateDiv('BodyContent');Loadjs('openemm.php')";
				}
				
				$array=$this->MAILBOXES_MENUS($array);
				
				
				
				
				  
			  if($this->fetchmail_installed){
			  	if($EnablePostfixMultiInstance<>1){
			  		$array["{messaging}"]["SUB"]["fetchmail"]="javascript:Loadjs('fetchmail.index.php?ajax=yes&in-front-ajax=yes')";
			  	}
			  }
				
				if($this->AMAVIS_INSTALLED){
					if($sock->GET_INFO("EnableAmavisDaemon")==1){
						$array["{messaging}"]["SUB"]["{APP_AMAVIS}"]="javascript:AnimateDiv('BodyContent');Loadjs('amavis.index.php?ajax=yes&in-front-ajax=yes')";
					}
				}
				
				if($this->AsPostfixAdministrator){	
					$array["{messaging}"]["SUB"]["{white list}"]="javascript:AnimateDiv('BodyContent');Loadjs('whitelists.admin.php?js=yes&js-in-line=yes')";
				}
			
				
				if($this->MILTERGREYLIST_INSTALLED){
					$array["{messaging}"]["SUB"]["{APP_MILTERGREYLIST}"]="javascript:Loadjs('milter.greylist.index.php?js=yes&in-front-ajax=yes')";
				}
				
				
				
				
			}
		
		}
		
							 

//		if($this->AsPostfixAdministrator==true && $this->dnsmasq_installed==true && $this->BIND9_INSTALLED==false){$array["dnsmasq"]="dnsmasq.index.php";}
		if($this->POSTFIX_INSTALLED){if($this->AsPostfixAdministrator==true && $this->mailgraph_installed==true){$array["Mailman"]="mailman.index.php";}}
		if($this->AllowViewStatistics==true){
			$array["{statistics}"]["MAIN"]="javascript:Loadjs('statistics.index.php')";
			$array["{statistics}"]["SUB"]["{master_panel}"]="javascript:AnimateDiv('BodyContent');Loadjs('statistics.index.php')";
			
			if($this->POSTFIX_INSTALLED){
				if($this->AsPostfixAdministrator){
					if($EnableArticaSMTPStatistics==1){
						$array["{statistics}"]["SUB"]["SMTP (con.)"]="javascript:AnimateDiv('BodyContent');Loadjs('postfix.stats.cnx.php')";
					}
					
				}
			}
			
			if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");}
			$q=new mysql_squid_builder();
			$events=$q->EVENTS_SUM();
			writelogs("SQUID EVENTS: $events",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if($q->EVENTS_SUM()>0){
				$array["{statistics}"]["SUB"]["{web_proxy}"]="javascript:AnimateDiv('BodyContent');SquidQuickLinks()";
				if($this->SARG_INSTALLED){
						$array["{statistics}"]["SUB"]["{web_proxy}:SARG"]="javascript:s_PopUpFull('squid/index.html',900,900,'SARG')";
					}
			}
			
			if($this->APP_VNSTAT_INSTALLED){
				$array["{statistics}"]["SUB"]["{network_stats}"]="javascript:AnimateDiv('BodyContent');Loadjs('statistics.vnstat.php')";
			}
		
		}	
		
		
// ------------------------ EVENTS	------------------------	

		if($this->AsAnAdministratorGeneric){
			$array["{events}"]["MAIN"]="";
			
			if($this->AsSystemAdministrator){
				$array["{events}"]["SUB"]["{syslog}"]="javascript:AnimateDiv('BodyContent');Loadjs('syslog.php')";
				$array["{events}"]["SUB"]["{APP_OPENSSH}"]="javascript:AnimateDiv('BodyContent');Loadjs('sshd.php?events-js=yes')";
				if($this->SNORT_INSTALLED){$array["{events}"]["SUB"]["I.D.S"]="javascript:AnimateDiv('BodyContent');Loadjs('system.nic.snort.php?events-js=yes')";}
				if($this->OPENVPN_INSTALLED){$array["{events}"]["SUB"]["{APP_OPENVPN}"]="javascript:AnimateDiv('BodyContent');Loadjs('index.openvpn.php?events-js=yes')";}
				$array["{events}"]["SUB"]["{artica_events}"]="javascript:AnimateDiv('BodyContent');Loadjs('artica.events.php?in-front-ajax=yes')";
				$array["{events}"]["SUB"]["MySQL"]="javascript:AnimateDiv('BodyContent');Loadjs('system.mysql.events.php?in-front-ajax=yes')";
				
				
			}
			
			if($this->POSTFIX_INSTALLED){
				$array["{events}"]["SUB"]["{messaging}"]="javascript:s_PopUp('postfix.events.php?pop=true',450,400)";
			}
			if($this->fetchmail_installed){
				$array["{events}"]["SUB"]["fetchmail"]="javascript:AnimateDiv('BodyContent');Loadjs('fetchmail.events.php');";
			}
			
			if($this->SAMBA_INSTALLED){
			if($this->AsSambaAdministrator){
				$array["{events}"]["SUB"]["samba watch"]="javascript:AnimateDiv('BodyContent');Loadjs('samba.watch.php?ajax=yes&in-line=yes');";
				$array["{events}"]["SUB"]["samba"]="javascript:AnimateDiv('BodyContent');Loadjs('samba.events.php?ajax=yes&in-line=yes');";
				
				}
			}
			
		}
		
	if($this->AsSambaAdministrator){
			if($this->APP_AUDITD_INSTALLED){
				$EnableAuditd=$sock->GET_INFO("EnableAuditd");
				if($EnableAuditd==null){$EnableAuditd=1;}
				if($EnableAuditd==1){
					$array["{events}"]["SUB"]["{APP_AUDITD}"]="javascript:AnimateDiv('BodyContent');Loadjs('auditd.php?in-front-ajax=yes&js-folder=yes')";
				}
			}
		}		
		
		if($this->KASPERSKY_SMTP_APPLIANCE){
			unset($array["{fileshare}"]);
			unset($array["system"]["SUB"]["{APP_DHCP}"]);
		
		}
		
		if($this->KASPERSKY_WEB_APPLIANCE){
			unset($array["{virtual_machines}"]);
			unset($array["{fileshare}"]);
			unset($array["{messaging}"]);
			unset($array["{APP_OPENVPN}"]);
			unset($array["{events}"]["SUB"]["{APP_OPENVPN}"]);
			unset($array["system"]["SUB"]["FreeWebs"]);
			unset($array["system"]["SUB"]["{load_balancing}"]);
			unset($array["system"]["SUB"]["VPN IPSEC"]);
			unset($array["system"]["SUB"]["VPN PPTP"]);
			unset($array["system"]["SUB"]["{APP_DHCP}"]);
			unset($array["system"]["SUB"]["{APP_PDNS}"]);
			unset($array["{statistics}"]["SUB"]["SMTP (con.)"]);
			unset($array["{events}"]["SUB"]["{APP_AUDITD}"]);
			unset($array["system"]["SUB"]["{APP_AUDITD}"]);
		}			
		
		$menus=$this->BuildArticaMenus($array,$sub_array_pages);
		$this->toplinks=$menus;
		return $menus;

		$this->toplinks="<div id='TpLink'>
		<table style='width:100%'>";
		
		$html=$html."\n\t<li class='top'><a href='#'> </a></li>\n";
		if(is_array($array)){
		while (list ($num, $ligne) = each ($array) ){
			
			if($page==$ligne){$class="id='selected'";}else{if($ligne=="#"){$class="id=subtitle";}else{$class=null;}}
				if(strpos($ligne,'ascript')>0){
					$html=$html . "\t\t<li $class><a href='#'>$num&nbsp;&raquo;</a></li>\n";
				}else{
					$html=$html . "\t\t<li $class><a href='$ligne'>$num&nbsp;&raquo;</a></li>\n";
				}
				
			
				
				$toplinks=$this->BuildLefetMenusSubmenu($sub_array_pages,$ligne,false);
				if($toplinks<>null){
						$this->toplinks=$this->toplinks.$toplinks;
						$count_toplinks=$count_toplinks+1;
				}
				
				$BuildLefetMenusSubmenu=$this->BuildLefetMenusSubmenu($sub_array_pages,$ligne);
				
				$html=$html . $BuildLefetMenusSubmenu;
			}
		}
		$html=$html . "<li class='bottom'><a href='#'> </a></li>";
		$this->toplinks=$this->toplinks. "</table></div>";
		if($count_toplinks==0){$this->toplinks=null;}
		$_SESSION["MainLeftMenus"]=$this->main_left_menus;

		return $html;
		
	}
	
	private function KAV4_PROXY_MENUS($array){
		$array["{APP_KAV4PROXY}"]["MAIN"]="javascript:Loadjs('squid.newbee.php?yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{status}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-status=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{tasks}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-tasks=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{main_parameters}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-params=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{groups}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-groups=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{statistics}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-stats=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{ExcludeMimeType}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-excludemime=yes')";
		$array["{APP_KAV4PROXY}"]["SUB"]["{blacklist_databases}"]="javascript:AnimateDiv('BodyContent');Loadjs('kav4proxy.php?frontend-blacklists=yes')";		
		return $array;
	}
	
	
private function MAILBOXES_MENUS($array=array()){
	
  if($this->ZARAFA_APPLIANCE){
			  	$array["{messaging}"]["SUB"]["{APP_ZARAFA}"]="javascript:AnimateDiv('BodyContent');Loadjs('zarafa.index.php')";
			  	return $array;
			  }
			  
   if($this->ZARAFA_INSTALLED){		  
			$array["{messaging}"]["SUB"]["{mailboxes}"]="javascript:AnimateDiv('BodyContent');Loadjs('zarafa.index.php')";
			return $array;
   	
   }
	
   if($this->cyrus_imapd_installed){
		$array["{messaging}"]["SUB"]["{mailboxes}"]="javascript:AnimateDiv('BodyContent');Loadjs('cyrus.index.php?in-front-ajax=yes')";
		if($this->roundcube_installed){
			$array["{messaging}"]["SUB"]["{APP_WEBMAIL}"]="javascript:AnimateDiv('BodyContent');Loadjs('roundcube.index.php?script=yes&in-front-ajax=yes')";
		}
	}
	return $array;		
}
	
	function BuildArticaMenus($master_array,$subarray){
		$tpl=new templates();
		$html[]="
		<div>
		<ul id=\"navigation\" style='width:170px'>
		
		
		";
		if(is_array($master_array)){
		while (list ($num, $ligne) = each ($master_array) ){
			if(is_array($ligne)){
				$html[]="<li><a class=\"head\" href=\"{$ligne["MAIN"]}\">". $tpl->_ENGINE_parse_body($num)."</a>";
				if(is_array($ligne["SUB"])){
					$html[]="<ul>";
					while (list ($submenu_title, $js) = each ($ligne["SUB"]) ){
						$html[]="<li><a href=\"$js\">". $tpl->_ENGINE_parse_body($submenu_title)."</a></li>";
					}
					$html[]="</ul>";
				}
				$html[]="</li>";
				continue;
			}
			$html[]="<li><a class=\"head\" href=\"$ligne\">$num</a></li>";
			//$html[]="<ul><li> <a href=\"$ligne\">$num</a></li></ul>";
			
		}}
		
		$html[]="</ul></div>
		<script>
		jQuery().ready(function(){	
			jQuery('#navigation').accordion({
			active: false,
			header: '.head',
			navigation: true,
			event: 'click',
			fillSpace: true,
			clearStyle: true,
			animated: 'bounceslide'});
		});
		</script>";
		return implode("\n",$html);
		
	 //animated: 'bounceslide'
	 // animated: 'easeslide'  
	}
	
	
	function BuildLefetMenusSubmenu($array,$page,$normal=true){
		
		$key=$this->BuildLefetMenusFoundSubmenu($array);
		if($array[$key][$page]==null){return null;}
		$page=CurrentPageName();
		if($key<>null){
			while (list ($num, $ligne) = each ($array[$key] )){
				if(strpos($num,'?')>0){$link=substr($num,0,strpos($num,'?'));}else{$link=$num;}
				
				
				if($page==$link){$class="id='selected'";}else{if($num=="#"){$class="id=subtitle";}else{$class=null;}}
				if($ligne<>"MAIN"){
					if($normal==true){$html=$html . "\t\t<li $class><a href='$num' style='font-size:10px'>$ligne&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></li>\n";}
					else {$html=$html . "\t\t<tr><td width=1%><img src='img/arrow.gif'></td><td class='ten'><a href='$num' >$ligne</a></td></tr>\n";}
				}
			}
			}
		return $html;}
		
		
		function BuildBackupFeature(){
			if(isset($_SESSION["backup_feature"])){return null;}
			$this->LoadModulesEnabled();
			writelogs("backup_feature:: MailArchiverEnabled=$this->MailArchiverEnabled",__FUNCTION__,__FILE__);
			if($this->MailArchiverEnabled==1){
						$array["users.backup.php"]="{backup}";
						$_SESSION["backup_feature"]=true;
					
				}else{					
				
				writelogs("backup_feature:: MIMEDEFANG_INSTALLED=$this->MIMEDEFANG_INSTALLED",__FUNCTION__,__FILE__);
				if($this->MIMEDEFANG_INSTALLED){
					include_once(dirname(__FILE__).'/class.mimedefang.inc');
					$mime=new mimedefang();
					if($mime->ScriptConf_array["BUILD"]["BACKUP_ENABLED"]==1){
						include_once(dirname(__FILE__).'/class.backup.emails.inc');
						$back=new backup_email($_SESSION["ou"]);
						if($back->BackupEnabled==1){
							$array["users.backup.php"]="{backup}";
							$_SESSION["backup_feature"]=true;
							}
						}
					}	
				}
				writelogs("backup_feature:: AMAVIS_INSTALLED=$this->AMAVIS_INSTALLED",__FUNCTION__,__FILE__);
				writelogs("backup_feature:: EnableAmavisDaemon=$this->EnableAmavisDaemon",__FUNCTION__,__FILE__);
				writelogs("backup_feature:: EnableAmavisBackup=$this->EnableAmavisBackup",__FUNCTION__,__FILE__);
				if($this->AMAVIS_INSTALLED){
					if($this->EnableAmavisDaemon==1){
						if($this->EnableAmavisBackup==1){
							$array["users.backup.php"]="{backup}";
							$_SESSION["backup_feature"]=true;
						}
					}
				}
				
			
			
			
		}
		
		
		function BuildUsersTabs(){
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:BuildUsersTabs() start; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);} 
			if(!isset($_SESSION["uid"])){
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:SESSION['uid'] is not set,aborting; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
				return false;
			}
			if($_SESSION['UsersInterfaceDatas']<>null){
				if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:SESSION['UsersInterfaceDatas']<>null aborting; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
				return false;
			}
			
			if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:this->LoadModulesEnabled(); function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
			$this->LoadModulesEnabled();
			if($this->IfIsAnuser()==false){return false;}
			include_once(dirname(__FILE__).'/class.user.inc');
			
			if($_SESSION["TEMPLATE_USER_ADDR"]==null){
					$user=new user($_SESSION["uid"]);
					$_SESSION["TEMPLATE_USER_ADDR"]="<strong>$user->givenName $user->sn</strong><br />
					$user->postalAddress $user->street $user->postalCode<br />
					$user->town<br />
					Phone: $user->telephoneNumber<br />
					Mobile: $user->mobile<br />
					$user->mail";
			}
			
			$this->template_user_addr=$_SESSION["TEMPLATE_USER_ADDR"];
				
			if($_SESSION["ou"]==null){
				$user=new user($_SESSION["uid"]);
				$_SESSION["ou"]=$user->ou;
			}
			
			
		if($this->cyrus_imapd_installed==true){
				if($this->roundcube_installed==true){
					include_once(dirname(__FILE__).'/class.roundcube.inc');
					$r=new roundcube();
					$array[$r->roundCubeArray["user_link"]]='{webmail}';	
				}
			}	
						

			
			
			if(!isset($_SESSION["quarantine_feature"])){
				if($this->MIMEDEFANG_INSTALLED){
					include_once(dirname(__FILE__).'/class.mimedefang.inc');
					$mime=new mimedefang();
					if($mime->ScriptConf_array["BUILD"]["QUARANTINE_SPAM"]==1){
							$array["users.quarantine.php"]="{quarantine}";
							$_SESSION["quarantine_feature"]=true;
						}
					}
			}
						
//----------------------------------------- OBM -------------------------------------------------------			
			if($this->OBM_INSTALLED){
					if($this->OBMEnabled){
					include_once(dirname(__FILE__).'/class.obm.inc');
							$obm=new obm();
							if($obm->external_url==null){$obm->external_url=$_SERVER['SERVER_NAME'];}
							$array["https://$obm->external_url:$obm->apache_listen"]="{APP_OBM}";
						}else{
							writelogs("OBM is not enabled, skip",__CLASS__.'/'.__FUNCTION__,__FILE__);
						}
					}else{
						writelogs("OBM is not installed, skip",__CLASS__.'/'.__FUNCTION__,__FILE__);
					}
	
//-----------------------------------------//-----------------------------------------//-----------------					
			
			if($_SESSION["backup_feature"]){$array["users.backup.php"]="{backup}";}
			if($_SESSION["quarantine_feature"]){$array["users.quarantine.php"]="{quarantine}";}


			
			/*if($this->kas_installed==true){
			if($this->AllowChangeAntiSpamSettings==true){
				$array["kas.user.rules.php"]="Anti-spam";
			}}*/
			
			if($this->AllowFetchMails){
				if($this->fetchmail_installed){
					$array["javascript:Loadjs('wizard.fetchmail.newbee.php?script=yes&uid={$_SESSION["uid"]}')"]="{fetchmail}";
				}
				
			}
			
			if($this->cyrus_imapd_installed){
				if($this->AllowChangeMailBoxRules){
					$password=$_SESSION["passwd"];
					$password=md5($password);
					$array["/smartsieve/login.php?uid={$_SESSION["uid"]}&_session=$password"]="{smartsieve}";
					}
			}
			
			if(is_array($array)){
			while (list ($num, $ligne) = each ($array)){
				$page=CurrentPageName();
				if($page==$num){
					$li=$li . "<a href='$num'><span style='color:yellow'>$ligne</span></a>\n";
					$this->template_users_menus_h=$this->template_users_menus_h."<a href=\"$num\">$ligne</a>&nbsp;|&nbsp;";
				}else{
					$li=$li ."<a href='$num'><span>$ligne</span></a>\n";
					$this->template_users_menus_h=$this->template_users_menus_h."<a href=\"$num\">$ligne</a>&nbsp;|&nbsp;";
				}
				
			}}
			
			
			return $li;
			
		}
		
	
	
	function BuildLefetMenusFoundSubmenu($array){
		$page=CurrentPageName();
		while (list ($num, $ligne) = each ($array) ){
			while (list ($key, $val) = each ($ligne) ){	
				if(strpos($key,'?')>0){$key=substr($key,0,strpos($key,'?'));}
				if($key==$page){
					return $num;
				}
			}
		}
	}
	
	function BuildLefetMenusFoundSubmenubyPage($array,$page){
		while (list ($num, $ligne) = each ($array) ){
			if(is_array($ligne)){
				while (list ($key, $val) = each ($ligne) ){	
					if(strpos($key,'?')>0){$key=substr($key,0,strpos($key,'?'));}
					if($key==$page){
						return $num;
						}
					}
			}
		}
	}


	
	function _TranslateRights($priv,$log=false){
		if(isset($GLOBALS["_TranslateRights"])){
			reset($GLOBALS["_TranslateRights"]);
			while (list ($num, $ligne) = each ($GLOBALS["_TranslateRights"]) ){$this->$num=$ligne;}
			return $GLOBALS["_TranslateRights"];
		}
		
		
		if(!class_exists("TranslateRights")){
			include_once(dirname(__FILE__)."/class.translate.rights.inc");
		}
		
		$cr=new TranslateRights($priv,$this->uid);
		$cr->ParseRights();
		$TTR=$cr->FINAL_PRIVS;
		if(!is_array($TTR)){return;}
		$GLOBALS["_TranslateRights"]=$TTR;
		while (list ($num, $ligne) = each ($GLOBALS["_TranslateRights"]) ){
			if($GLOBALS['VERBOSE']){echo "this->$num=$ligne\n";}
			$this->$num=$ligne;}
		return $GLOBALS["_TranslateRights"];
		
	}
	private function aghjkyrtij(){
		$objzrtg=base64_decode("Q09SUF9MSUNFTlNF");
		$fguih=base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ==");
		if(!is_file($fguih)){return; }
		$bndfvs=trim(@file_get_contents($fguih));
		if(base64_decode($bndfvs)=="TRUE"){$this->$objzrtg=true;}
	}
	
	function IfIsAnuser($log=false){
			
			if($this->AsMessagingOrg){return false;}
			if($this->AseMailCampaignsAdmin){return false;}
			
			
			if($this->AsSystemAdministrator){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsSystemAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
		
			if($this->AsArticaMetaAdmin){
				return false;
			}
				
			if($this->AsVirtualBoxManager){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsVirtualBoxManager=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}				
				
				
			if($this->AsMailBoxAdministrator){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsMailBoxAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsPostfixAdministrator){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsPostfixAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
				
			if($this->AsSquidAdministrator){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsSquidAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			
			if($this->AllowChangeKav){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AllowChangeKav=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AllowChangeKas){
				if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AllowChangeKas=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AllowAddUsers){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowAddUsers=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsOrgAdmin){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsOrgAdmin=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AllowViewStatistics){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowViewStatistics=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AllowEditOuSecurity){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowEditOuSecurity=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsSambaAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsSambaAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsDnsAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsDnsAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsQuarantineAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsQuarantineAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
			if($this->AsOrgStorageAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsOrgStorageAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				return false;}
				
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  None privileges found",__CLASS__.'/'.__FUNCTION__,__FILE__);}	
			return true;
			}
	
	
	function _ParsePrivieleges($content=null){
		$trace=debug_backtrace();
		if($GLOBALS["DEBUG_PRIVS"]){echo "<span style='color:red;font-size:26px'>_ParsePrivieleges</span>
				<textarea style='width:100%;height:240px'>$content</textarea>";
		}
		
		if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		$posix_getuid=$GLOBALS["posix_getuid"];
		$priv=array();
		include_once(dirname(__FILE__)."/class.privileges.inc");
		
		if($GLOBALS["VERBOSE"]){
			echo "privileges({$_SESSION["uid"]})" .__CLASS__.'/'.__FUNCTION__."/".basename(__FILE__)."/".__LINE__."<br>\n";
		}
		
		if($GLOBALS["DEBUG_PRIVS"]){writelogs("class.privileges.inc ->privileges({$_SESSION["uid"]}) $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$privs=new privileges($_SESSION["uid"]);
		if($GLOBALS["DEBUG_PRIVS"]){writelogs("return privs",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		return $privs->privs;
	}

	function _BuildPrivileges($hash){
		if(!is_array($hash)){return "DEFAULT";}
		while (list ($num, $ligne) = each ($hash) ){
			$conf=$conf ."[$num]=\"$ligne\"\n";
			
		}
		
		return $conf;
		
	}
	
	
	function isTreeReadable($priv=null){
		if(posix_getuid()==0){$this->uid=-100;}
		if($this->uid==-100){return true;}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
		if($priv["AsArticaMetaAdmin"]=="yes"){return true;}
		
		
		if($priv["AllowChangeKas"]=="yes"){return true;}
		if($priv["AllowChangeKav"]=="yes"){return true;}
		if($priv["AllowAddUsers"]=="yes"){return true;}
		if($priv["AllowAddGroup"]=="yes"){return true;}
		return false;
		}
		
	function IsGlobalAdmin($priv=null,$log=false){
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["posix_getuid"]==0){$this->uid=-100;}
		if($this->uid==-100){
			$this->AsArticaAdministrator=true;
			$this->AsQuarantineAdministrator=true;
			$this->AsVirtualBoxManager=true;
			$this->AsArticaMetaAdmin=true;
			if($log){writelogs("It is a global administrator",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			return true;
			}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSquidAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
		if($priv["AsArticaMetaAdmin"]=="yes"){return true;}
		
		
		return false;
		}
		
	function IsSuperAdmin(){
		if($this->AsArticaAdministrator){return true;}
		if($this->AsPostfixAdministrator){return true;}
		if($this->AsSquidAdministrator){return true;}
		if($this->AsSambaAdministrator){return true;}
		if($this->AsSquidAdministrator){return true;}
		if($this->AsDansGuardianAdministrator){return true;}
		if($this->AsMailBoxAdministrator){return true;}
		return false;
	}
	
		
function array_monitorix($type="day"){
	include_once(dirname(__FILE__)."/class.page.builder.inc");
	$pges=new pagebuilder();			
	return $pges->monitorix_array($type);
	}

}


function url_decode_special($path){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	return url_decode_special_tool($path);
}

function GetRights_aliases() {
	$usersprivs = new usersMenus();
	if ($usersprivs->AsAnAdministratorGeneric) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsAnAdministratorGeneric}: {no}";
	if ($usersprivs->AllowEditOuSecurity) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AllowEditOuSecurity}: {no}";
	if ($usersprivs->AsMessagingOrg) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsMessagingOrg}: {no}";
	if ($usersprivs->AllowEditAliases) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AllowEditAliases}: {no}";
	if($usersprivs->AsInventoryAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsInventoryAdmin}: {no}";
	if($usersprivs->AsOrgDNSAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsOrgDNSAdmin}: {no}";
	if($usersprivs->ASDCHPAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{ASDCHPAdmin}: {no}";
	return 0;
}

?>