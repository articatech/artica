<?php
if(!defined("alse")){define("alse",null);}
class settings_inc {

	public $squid_kerb_auth_path=null;
	public $TreePage=array("domains.edit.group.php"=>true,"samba.index.php"=>true);
	public $ArchStruct=0;
	public $menus;
	public $main_left_menus=array();
	public $menusright;
	public $toplinks;
	public $priv;
	public $MAIN_CONSOLE_PORT=9000;
	public $EnablePerUserRemoteAddressBook=0;
	public $dhcp_installed=false;
	public $AllowChangeDomains=false;
	public $AsSystemAdministrator=false;
	public $AsOrgStorageAdministrator=false;
	public $AllowXapianDownload=false;
	public $AllowAddGroup=false;
	public $AllowChangeKav=false;
	public $AllowChangeKas=false;
	public $AllowEditAsWbl=false;
	public $AllowEditOuSecurity=false;	
	public $AsArticaAdministrator=false;
	public $AsSambaAdministrator=false;
	public $AsMailManAdministrator=false;
	public $AsPostfixAdministrator=false;
	public $AsSquidAdministrator=false;
	public $AsMailBoxAdministrator=false;
	public $AsDansGuardianGroupRule=false;
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
	public $NETATALK_INSTALLED=false;
	public $AsAnAdministratorGeneric=false;
	public $AllowAddUsers=false;
	public $AllowViewStatistics=false;
	public $AllowChangeAntiSpamSettings=false;
	public $AsOrgAdmin=false;
	public $AsInventoryAdmin=false;
	public $AseMailCampaignsAdmin=false;
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
	public $disks_size=null;
	public $cpumhz=null;
	public $preload_installed=false;
	public $smbmount_installed=false;
	public $collectd_installed=false;
	public $EnableVirtualDomainsInMailBoxes=false;
	public $SIMPLE_GROUPEWARE_INSTALLED=false;
	public $APP_ATOPENMAIL_INSTALLED=false;
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
	public $POLICYD_WEIGHT_PORT;
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
	public $OPENVPN_APPLIANCE=false;
	public $SIEVE_PORT=0;
	public $TOMCAT_INSTALLED=false;
	public $TOMCAT_VERSION="";
	public $APP_KHSE_INSTALLED=false;
	public $MEMCACHED_INSTALLED=false;
	public $HAPROXY_INSTALLED=false;
	public $APP_TOMCAT6_INSTALLED=false;
	public $URLSNARF_INSTALLED=false;
	public $APP_ZARAFADB_INSTALLED=false;
	public $FREERADIUS_INSTALLED=false;
	public $SQUID_REVERSE_APPLIANCE=false;
	/* This licence must be used Only with Artica-for postfix */
	public $ChartLicence="D1XFWGMEW9L.HSK5T4Q79KLYCK07EK";
	/*you cannot use it without any authorizations of maani.us.
	this licence is Copyright 2004-2007, maani.us
	For more information, see
	http://www.maani.us/xml_charts/index.php?menu=License
	*/
	
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
	public $WIFIDOG_INSTALLED=false;
	
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
	public $SQUID_BIN_PATH="";
	public $VMWARE_HOST=false;
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
	public $NGINX_INSTALLED=false;
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
	public $LOSETUP_INSTALLED=false;
	public $SNORT_INSTALLED=false;
	public $APP_EYEOS_INSTALLED=false;
	public $EYEOS_VERSION;
	public $APACHE_MOD_SECURITY=false;
	public $APACHE_MOD_EVASIVE=false;
	public $Z_PUSH_INSTALLED=false;
	public $APACHE_PROXY_MODE=false;
	public $APP_GREENSQL_INSTALLED=false;
	public $IPTABLES_ACCOUNTING_EXISTS=false;
	public $POWER_DNS_MYSQL=false;
	public $POWERADMIN_INSTALLED=false;
	public $APACHE_MOD_CACHE=false;
	public $APP_AMANDA_INSTALLED=false;
	public $NSCD_INSTALLED=false;
	public $DRUPAL7_INSTALLED=false;
	public $TOMCAT_JAVA="";
	public $TOMCAT_DIR="";
	public $APACHE_MOD_TOMCAT=false;
	public $OPENEMM_INSTALLED=false;
	public $OPENEMM_VERSION="";
	public $MSKTUTIL_INSTALLED=false;
	public $OPENEMM_SENDMAIL_VERSION="";
	public $PIWIK_INSTALLED=false;
	public $APACHE_MOD_INFO=false;
	public $APACHE_MOD_STATUS=false;
	public $APACHE_APPLIANCE=false;
	public $APACHE_MOD_SUEXEC=false;
	public $APACHE_MOD_FCGID=false;	
	public $APACHE_MOD_LOGSSQL=false;
	public $LOAD_BALANCE_APPLIANCE=false;
	public $ARPD_INSTALLED=false;
	public $YAFFAS_INSTALLED=false;
	public $SQUID_APPLIANCE=false;
	public $PROXYTINY_APPLIANCE=false;
	public $MPM_ITK_MODULE=false;
	public $APACHE_MOD_BW=false;
	public $QUOTA_INSTALLED=false;
	public $MYSQLD_MULTI_INSTALLED=false;
	public $MONIT_INSTALLED=false;
	public $IPBAN_INSTALLED=false;
	public $EJABBERD_INSTALLED=false;
	public $PYMSNT_INSTALLED=false;
	public $SSHFS_INSTALLED=false;
	public $APP_ARKEIA_INSTALLED=false;
	public $SAMBA_CLUSTER_SUPPORT=false;
	public $KLMS_INSTALLED=false;
	public $ZARAFA_SEARCH_INSTALLED=false;
	public $LIGHT_INSTALL=false;
	public $CORP_LICENSE=false;
	public $WEBSTATS_APPLIANCE=false;
	public $ARTICADB_INSTALLED=false;
	public $SURICATA_INSTALLED=false;
	public $ZARAFA_INDEXER_INSTALLED=false;
	public $APP_TOMCAT6_VERSION=null;
	public $APP_JAVA_JDK_INSTALLED=false;
	public $WSGATE_INSTALLED=false;
	public $ETTERCAP_INSTALLED=false;
	public $APACHE_MOD_PYTHON=false;
	public $APP_PYAUTHENNTLM=false;
	public $APP_SQUIDDB_INSTALLED=false;
	public $APP_CHILLI_INSTALLED=false;
	public $ISCSID_INSTALLED=false;
	public $RDPPROXY_INSTALLED=false;
	public $SYNCTHING_INSTALLED=false;
	public $SENSORS_INSTALLED=false;
	public $SS5_INSTALLED=false;
	public $HYPERCACHE_STOREID=false;
	public $XEN_HOST=false;
	public $HYPERV_HOST=false;
	
	private function CheckIndexDefaults($_GLOBAL){
		return $_GLOBAL;
		foreach($this as $key => $value) {
			if(!isset($_GLOBAL[$key])){$_GLOBAL[$key]=$value;}
		}
		
		return $_GLOBAL;
		
		
	}
	
	
	function settings_inc(){
			$_GLOBAL=array();
			if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
			
			if($GLOBALS["VERBOSE"]){
					$trace=debug_backtrace();
					if(isset($trace[1])){
						$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			
						echo "Instanciate userMenus() $called\n";
					}
				}		
		
			
			
			$settings="/usr/share/artica-postfix/ressources/settings.inc";
			
			if(file_exists($settings)){
					@include($settings);
					$this->GLOBAL_CONFIG=$_GLOBAL;
					$GLOBALS["SETTINGS_FILES"]=$_GLOBAL;
					if(!is_array($this->GLOBAL_CONFIG)){echo "Settings unable stat";return false;}
					}else{
					die(__CLASS__."/".__FUNCTION__."::Unable to stat $settings\n");
			}

			if(isset($_GLOBAL["squid_kerb_auth_path"])){$this->squid_kerb_auth_path=$_GLOBAL["squid_kerb_auth_path"];}
			if(isset($_GLOBAL["NSCD_INSTALLED"])){$this->NSCD_INSTALLED=$_GLOBAL["NSCD_INSTALLED"];}
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
			
			if(isset($_GLOBAL["NGINX_INSTALLED"])){$this->NGINX_INSTALLED=$_GLOBAL["NGINX_INSTALLED"];;}
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
			if(isset($_GLOBAL["KAV4PROXY_INSTALLED"])){$this->KAV4PROXY_INSTALLED=$_GLOBAL["KAV4PROXY_INSTALLED"];;}
			if(isset($_GLOBAL["SQUID_PID"])){$this->SQUID_PID=$_GLOBAL["SQUID_PID"];;}
			if(isset($_GLOBAL["SQUID_NTLM_AUTH"])){$this->SQUID_NTLM_AUTH=$_GLOBAL["SQUID_NTLM_AUTH"];;}
			if(isset($_GLOBAL["SQUID_BIN_VERSION"])){$this->SQUID_BIN_VERSION=$_GLOBAL["SQUID_BIN_VERSION"];;}
			if(isset($_GLOBAL["KAV4PROXY_MEMORY"])){$this->KAV4PROXY_MEMORY=$_GLOBAL["KAV4PROXY_MEMORY"];;}
			if(isset($_GLOBAL["KAV4PROXY_PATTERN"])){$this->KAV4PROXY_PATTERN=$_GLOBAL["KAV4PROXY_PATTERN"];;}
			if(isset($_GLOBAL["KAV4PROXY_VERSION"])){$this->KAV4PROXY_VERSION=$_GLOBAL["KAV4PROXY_VERSION"];;}
			if(isset($_GLOBAL["SQUID_CACHMGR"])){$this->SQUID_CACHMGR=$_GLOBAL["SQUID_CACHMGR"];;}
			if(isset($_GLOBAL["APACHE_DIR_SITES_ENABLED"])){$this->APACHE_DIR_SITES_ENABLED=$_GLOBAL["APACHE_DIR_SITES_ENABLED"];;}
			if(isset($_GLOBAL["APACHE_MODULES_PATH"])){$this->APACHE_MODULES_PATH=$_GLOBAL["APACHE_MODULES_PATH"];;}
			if(isset($_GLOBAL["LOAD_BALANCE_APPLIANCE"])){$this->APACHE_RUN_USER=$_GLOBAL["LOAD_BALANCE_APPLIANCE"];;}
			if(isset($_GLOBAL["APACHE_RUN_USER"])){$this->APACHE_RUN_USER=$_GLOBAL["APACHE_RUN_USER"];;}
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];;}
			if(isset($_GLOBAL["DAVFS_INSTALLED"])){$this->DAVFS_INSTALLED=$_GLOBAL["DAVFS_INSTALLED"];;}
			if(isset($_GLOBAL["APACHE_MOD_QOS"])){$this->APACHE_MOD_QOS=$_GLOBAL["APACHE_MOD_QOS"];;}
			if(isset($_GLOBAL["APACHE_MOD_CACHE"])){$this->APACHE_MOD_CACHE=$_GLOBAL["APACHE_MOD_CACHE"];}
			if(isset($_GLOBAL["TOMCAT_INSTALLED"])){$this->TOMCAT_INSTALLED=$_GLOBAL["TOMCAT_INSTALLED"];}
			if(isset($_GLOBAL["TOMCAT_VERSION"])){$this->APACHE_MOD_CACHE=$_GLOBAL["TOMCAT_VERSION"];}
			if(isset($_GLOBAL["ARPD_INSTALLED"])){$this->ARPD_INSTALLED=$_GLOBAL["ARPD_INSTALLED"];}
			if(isset($_GLOBAL["NETATALK_INSTALLED"])){$this->NETATALK_INSTALLED=$_GLOBAL["NETATALK_INSTALLED"];}
			if(isset($_GLOBAL["SQUID_APPLIANCE"])){$this->SQUID_APPLIANCE=$_GLOBAL["SQUID_APPLIANCE"];}
			if(isset($_GLOBAL["PROXYTINY_APPLIANCE"])){$this->PROXYTINY_APPLIANCE=$_GLOBAL["PROXYTINY_APPLIANCE"];}
			if(isset($_GLOBAL["WSGATE_INSTALLED"])){$this->WSGATE_INSTALLED=$_GLOBAL["WSGATE_INSTALLED"];}
			if(isset($_GLOBAL["URLSNARF_INSTALLED"])){$this->URLSNARF_INSTALLED=$_GLOBAL["URLSNARF_INSTALLED"];}
			if(isset($_GLOBAL["ETTERCAP_INSTALLED"])){$this->ETTERCAP_INSTALLED=$_GLOBAL["ETTERCAP_INSTALLED"];}
			if(isset($_GLOBAL["APP_ZARAFADB_INSTALLED"])){$this->APP_ZARAFADB_INSTALLED=$_GLOBAL["APP_ZARAFADB_INSTALLED"];}
			if(isset($_GLOBAL["APP_CHILLI_INSTALLED"])){$this->APP_CHILLI_INSTALLED=$_GLOBAL["APP_CHILLI_INSTALLED"];}
			if(isset($_GLOBAL["RDPPROXY_INSTALLED"])){$this->RDPPROXY_INSTALLED=$_GLOBAL["RDPPROXY_INSTALLED"];}
			if(isset($_GLOBAL["WIFIDOG_INSTALLED"])){$this->WIFIDOG_INSTALLED=$_GLOBAL["WIFIDOG_INSTALLED"];}
			
			
			if(isset($_GLOBAL["MPM_ITK_MODULE"])){$this->MPM_ITK_MODULE=$_GLOBAL["MPM_ITK_MODULE"];}
			if(isset($_GLOBAL["APACHE_MOD_BW"])){$this->APACHE_MOD_BW=$_GLOBAL["APACHE_MOD_BW"];}
			if(isset($_GLOBAL["MYSQLD_MULTI_INSTALLED"])){$this->MYSQLD_MULTI_INSTALLED=$_GLOBAL["MYSQLD_MULTI_INSTALLED"];}
			if(isset($_GLOBAL["MEMCACHED_INSTALLED"])){$this->MEMCACHED_INSTALLED=$_GLOBAL["MEMCACHED_INSTALLED"];}
			if(isset($_GLOBAL["WEBSTATS_APPLIANCE"])){$this->WEBSTATS_APPLIANCE=$_GLOBAL["WEBSTATS_APPLIANCE"];}
			$this->hostname=$_GLOBAL["fixed_hostname"];
			$this->fqdn=$_GLOBAL["fqdn_hostname"];
			if(isset($_GLOBAL["DANSGUARDIAN_INSTALLED"])){$this->DANSGUARDIAN_INSTALLED=$_GLOBAL["DANSGUARDIAN_INSTALLED"];}
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
			$this->lighttpd_installed=$_GLOBAL["lighttpd_installed"];
			if($this->ChartLicence==null){$this->ChartLicence='D1XFWGMEW9L.HSK5T4Q79KLYCK07EK';}
			$this->SAMBA_INSTALLED=$_GLOBAL["SAMBA_INSTALLED"];
			$this->LOCAL_SID=$_GLOBAL["LOCAL_SID"];
			$this->BadMysqlPassword=$_GLOBAL["BadMysqlPassword"];
			if($this->BadMysqlPassword==null){$this->BadMysqlPassword=0;}
			if(isset($_GLOBAL["POSTFIX_LDAP_COMPLIANCE"])){$this->POSTFIX_LDAP_COMPLIANCE=$_GLOBAL["POSTFIX_LDAP_COMPLIANCE"];}
			if(isset($_GLOBAL["MAILFROMD_SOCKET"])){$this->MAILFROMD_SOCKET=$_GLOBAL["MAILFROMD_SOCKET"];}
			if(isset($_GLOBAL["CLAMAV_INSTALLED"])){$this->CLAMAV_INSTALLED=$_GLOBAL["CLAMAV_INSTALLED"];}
			if(isset($_GLOBAL["CLAMSCAN_INSTALLED"])){$this->CLAMSCAN_INSTALLED=$_GLOBAL["CLAMSCAN_INSTALLED"];}
			
			if(isset($_GLOBAL["CLAMAV_MILTER_SOCKET"])){$this->CLAMAV_MILTER_SOCKET=$_GLOBAL["CLAMAV_MILTER_SOCKET"];}
			if(isset($_GLOBAL["CLAMAV_MILTER_INSTALLED"])){$this->CLAMAV_MILTER_INSTALLED=$_GLOBAL["CLAMAV_MILTER_INSTALLED"];}
			if(isset($_GLOBAL["SPAMASS_MILTER_INSTALLED"])){$this->SPAMASS_MILTER_INSTALLED=$_GLOBAL["SPAMASS_MILTER_INSTALLED"];}
			if(isset($_GLOBAL["SPAMASS_MILTER_SOCKET"])){$this->SPAMASS_MILTER_SOCKET=$_GLOBAL["SPAMASS_MILTER_SOCKET"];}
			if(isset($_GLOBAL["roundcube_folder"])){$this->roundcube_folder=$_GLOBAL["roundcube_folder"];}
			$this->phpcgi=$_GLOBAL["phpcgi"];
			if(isset($_GLOBAL["razor_config"])){$this->razor_config=$_GLOBAL["razor_config"];}
			if(isset($_GLOBAL["razor_installed"])){$this->razor_installed=$_GLOBAL["razor_installed"];}
			if(isset($_GLOBAL["pyzor_installed"])){$this->pyzor_installed=$_GLOBAL["pyzor_installed"];}
			if(isset($_GLOBAL["spfmilter_installed"])){$this->spfmilter_installed=$_GLOBAL["spfmilter_installed"];}
			if(isset($_GLOBAL["netbiosname"])){$this->netbiosname=$_GLOBAL["netbiosname"];}
			if(isset($_GLOBAL["MIMEDEFANG_INSTALLED"])){$this->MIMEDEFANG_INSTALLED=$_GLOBAL["MIMEDEFANG_INSTALLED"];}
			if(isset($_GLOBAL["MIMEDEFANG_SOCKET"])){$this->MIMEDEFANG_SOCKET=$_GLOBAL["MIMEDEFANG_SOCKET"];}
			if(isset($_GLOBAL["GRAPHDEFANG_INSTALLED"])){$this->GRAPHDEFANG_INSTALLED=$_GLOBAL["GRAPHDEFANG_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_INSTALLED"])){$this->MHONARC_INSTALLED=$_GLOBAL["MHONARC_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_VERSION"])){$this->MHONARC_VERSION=$_GLOBAL["MHONARC_VERSION"];}
			if(isset($_GLOBAL["spamassassin_installed"])){$this->spamassassin_installed=$_GLOBAL["spamassassin_installed"];}
			if(isset($_GLOBAL["spamassassin_version"])){$this->spamassassin_version=$_GLOBAL["spamassassin_version"];}
			if(isset($_GLOBAL["bogofilter_installed"])){$this->BOGOFILTER_INSTALLED=$_GLOBAL["bogofilter_installed"];}
			if(isset($_GLOBAL["SQUID_VERSION"])){$this->SQUID_VERSION=$_GLOBAL["SQUID_VERSION"];}
			if(isset($_GLOBAL["SQUID_LDAP_AUTH"])){$this->SQUID_LDAP_AUTH=$_GLOBAL["SQUID_LDAP_AUTH"];}
			$this->stunnel4_installed=$_GLOBAL["stunnel4_installed"];
			if(isset($_GLOBAL["DKIMFILTER_INSTALLED"])){$this->DKIMFILTER_INSTALLED=$_GLOBAL["DKIMFILTER_INSTALLED"];}
			if(isset($_GLOBAL["DKIMFILTER_SOCKET"])){$this->DKIMFILTER_SOCKET=$_GLOBAL["DKIMFILTER_SOCKET"];}
			$this->POSTFIX_VERSION=$_GLOBAL["POSTFIX_VERSION"];
			$this->blkid_installed=$_GLOBAL["blkid_installed"];
			if(isset($_GLOBAL["usb_list"])){$this->usb_list=$_GLOBAL["usb_list"];}
			$this->nmap_installed=$_GLOBAL["nmap_installed"];
			if(isset($_GLOBAL["roundcube_mysql_sources"])){$this->roundcube_mysql_sources=$_GLOBAL["roundcube_mysql_sources"];}
			if(isset($_GLOBAL["roundcube_web_folder"])){$this->roundcube_web_folder=$_GLOBAL["roundcube_web_folder"];}
			if(isset($_GLOBAL["fetchmail_configured"])){$this->fetchmail_configured=$_GLOBAL["fetchmail_configured"];}
			if(isset($_GLOBAL["KAV4SAMBA_INSTALLED"])){$this->KAV4SAMBA_INSTALLED=$_GLOBAL["KAV4SAMBA_INSTALLED"];}
			if(isset($_GLOBAL["KAV4SAMBA_VFS"])){$this->KAV4SAMBA_VFS=$_GLOBAL["KAV4SAMBA_VFS"];}
			if(isset($_GLOBAL["SAMBA_MYSQL_AUDIT"])){$this->SAMBA_MYSQL_AUDIT=$_GLOBAL["SAMBA_MYSQL_AUDIT"];}
			if(isset($_GLOBAL["awstats_www_path"])){$this->awstats_www_path=$_GLOBAL["awstats_www_path"];}
			if(isset($_GLOBAL["SQUID_ICAP_ENABLED"])){$this->SQUID_ICAP_ENABLED=$_GLOBAL["SQUID_ICAP_ENABLED"];}
			if(isset($_GLOBAL["LIGHTTPD_LDAP_AUTH"])){$this->LIGHTTPD_LDAP_AUTH=$_GLOBAL["LIGHTTPD_LDAP_AUTH"];}
			if(isset($_GLOBAL["YAFFAS_INSTALLED"])){$this->YAFFAS_INSTALLED=$_GLOBAL["YAFFAS_INSTALLED"];}
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];}
			if(isset($_GLOBAL["MONIT_INSTALLED"])){$this->MONIT_INSTALLED=$_GLOBAL["MONIT_INSTALLED"];}
			if(isset($_GLOBAL["IPBAN_INSTALLED"])){$this->IPBAN_INSTALLED=$_GLOBAL["IPBAN_INSTALLED"];}
			if(isset($_GLOBAL["EJABBERD_INSTALLED"])){$this->EJABBERD_INSTALLED=$_GLOBAL["EJABBERD_INSTALLED"];;}
			if(isset($_GLOBAL["PYMSNT_INSTALLED"])){$this->PYMSNT_INSTALLED=$_GLOBAL["PYMSNT_INSTALLED"];;}
			if(isset($_GLOBAL["HAPROXY_INSTALLED"])){$this->HAPROXY_INSTALLED=$_GLOBAL["HAPROXY_INSTALLED"];;}
			if(isset($_GLOBAL["SSHFS_INSTALLED"])){$this->SSHFS_INSTALLED=$_GLOBAL["SSHFS_INSTALLED"];;}
			if(isset($_GLOBAL["APP_ARKEIA_INSTALLED"])){$this->APP_ARKEIA_INSTALLED=$_GLOBAL["APP_ARKEIA_INSTALLED"];;}
			if(isset($_GLOBAL["ZARAFA_SEARCH_INSTALLED"])){$this->ZARAFA_SEARCH_INSTALLED=$_GLOBAL["ZARAFA_SEARCH_INSTALLED"];;}
			if(isset($_GLOBAL["ZARAFA_INDEXER_INSTALLED"])){$this->ZARAFA_INDEXER_INSTALLED=$_GLOBAL["ZARAFA_INDEXER_INSTALLED"];;}
			if(isset($_GLOBAL["FREERADIUS_INSTALLED"])){$this->FREERADIUS_INSTALLED=$_GLOBAL["FREERADIUS_INSTALLED"];;}
			if(isset($_GLOBAL["SQUID_REVERSE_APPLIANCE"])){$this->SQUID_REVERSE_APPLIANCE=$_GLOBAL["SQUID_REVERSE_APPLIANCE"];;}
			if(isset($_GLOBAL["ISCSID_INSTALLED"])){$this->ISCSID_INSTALLED=$_GLOBAL["ISCSID_INSTALLED"];;}
			if(isset($_GLOBAL["SYNCTHING_INSTALLED"])){$this->SYNCTHING_INSTALLED=$_GLOBAL["SYNCTHING_INSTALLED"];;}
			if(isset($_GLOBAL["SENSORS_INSTALLED"])){$this->SENSORS_INSTALLED=$_GLOBAL["SENSORS_INSTALLED"];;}
			if(isset($_GLOBAL["SS5_INSTALLED"])){$this->SS5_INSTALLED=$_GLOBAL["SS5_INSTALLED"];}
			if(isset($_GLOBAL["HYPERCACHE_STOREID"])){$this->HYPERCACHE_STOREID=$_GLOBAL["HYPERCACHE_STOREID"];}
			if(isset($_GLOBAL["XEN_HOST"])){$this->XEN_HOST=$_GLOBAL["XEN_HOST"];}
			if(isset($_GLOBAL["HYPERV_HOST"])){$this->HYPERV_HOST=$_GLOBAL["HYPERV_HOST"];}
			
			
			
			
			if(isset($_GLOBAL["LIGHT_INSTALL"])){$this->LIGHT_INSTALL=$_GLOBAL["LIGHT_INSTALL"];}
			// Are you sure to do that !!!, breaking the license turn you to a pirate...
			if(isset($_GLOBAL["CORP_LICENSE"])){$this->CORP_LICENSE=$_GLOBAL["CORP_LICENSE"];}
					
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
			$this->OPENLDAP_DYNLIST=$_GLOBAL["OPENLDAP_DYNLIST"];
			$this->SLPAD_LOCKED=$_GLOBAL["SLPAD_LOCKED"];
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
			if(isset($_GLOBAL["IOSTAT_ARRAY"])){
				if(is_array($_GLOBAL["IOSTAT_ARRAY"])){
					$this->IOSTAT_ARRAY=$_GLOBAL["IOSTAT_ARRAY"];
				}
			}
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
			$this->autofs_installed=$_GLOBAL["autofs_installed"];
			$this->APACHE_PORT=$_GLOBAL["APACHE_PORT"];
			$this->NFS_SERVER_INSTALLED=$_GLOBAL["NFS_SERVER_INTSALLED"];
			$this->LVM_INSTALLED=$_GLOBAL["LVM_INSTALLED"];
			$this->MYSQL_NDB_MGMD_INSTALLED=$_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"];
			
			$this->winexe_installed=$_GLOBAL["winexe_installed"];
			$this->OCSI_INSTALLED=$_GLOBAL["OCSI_INSTALLED"];
			if(isset($_GLOBAL["ASSP_INSTALLED"])){$this->ASSP_INSTALLED=$_GLOBAL["ASSP_INSTALLED"];}
			$this->MEM_HIGER_1G=$_GLOBAL["MEM_HIGER_1G"];
			$this->LMB_LUNDIMATIN_INSTALLED=$_GLOBAL["LMB_LUNDIMATIN_INSTALLED"];
			$this->APACHE_MODE_VHOSTS_LDAP=$_GLOBAL["APACHE_MODE_VHOSTS_LDAP"];
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
			$this->hostname=str_replace('.(none)','',$this->hostname);
			$this->fqdn=str_replace('.(none)','',$this->fqdn);
			$this->BACKUPPC_INSTALLED=$_GLOBAL["BACKUPPC_INSTALLED"];
			$this->BACKUPPC_APACHE=$_GLOBAL["BACKUPPC_APACHE"];
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
			if(isset($_GLOBAL["SAMBA_CLUSTER_SUPPORT"])){$this->SAMBA_CLUSTER_SUPPORT=$_GLOBAL["SAMBA_CLUSTER_SUPPORT"];}
			if(isset($_GLOBAL["KLMS_INSTALLED"])){$this->KLMS_INSTALLED=$_GLOBAL["KLMS_INSTALLED"];}
			if(isset($_GLOBAL["SURICATA_INSTALLED"])){$this->SURICATA_INSTALLED=$_GLOBAL["SURICATA_INSTALLED"];}
			if(isset($_GLOBAL["APP_JAVA_JDK_INSTALLED"])){$this->APP_JAVA_JDK_INSTALLED=$_GLOBAL["APP_JAVA_JDK_INSTALLED"];}
			if(isset($_GLOBAL["APACHE_MOD_PYTHON"])){$this->APACHE_MOD_PYTHON=$_GLOBAL["APACHE_MOD_PYTHON"];}
			if(isset($_GLOBAL["APP_PYAUTHENNTLM"])){$this->APP_PYAUTHENNTLM=$_GLOBAL["APP_PYAUTHENNTLM"];}
			
			
			
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
			$this->SIEVE_PORT=$_GLOBAL["SIEVE_PORT"];
			$this->APACHE_PROXY_MODE=$_GLOBAL["APACHE_PROXY_MODE"];
			$this->APP_GREENSQL_INSTALLED=$_GLOBAL["APP_GREENSQL_INSTALLED"];
			$this->IPTABLES_ACCOUNTING_EXISTS=$_GLOBAL["IPTABLES_ACCOUNTING_EXISTS"];
			$this->POWER_DNS_MYSQL=$_GLOBAL["POWER_DNS_MYSQL"];
			$this->POWERADMIN_INSTALLED=$_GLOBAL["POWERADMIN_INSTALLED"];
			$this->OPENEMM_INSTALLED=$_GLOBAL["OPENEMM_INSTALLED"];
			$this->APACHE_MOD_LOGSSQL=$_GLOBAL["APACHE_MOD_LOGSSQL"];
			
			if(isset($_GLOBAL["ISCSI_CLIENT_INSTALLED"])){$this->ISCSI_CLIENT_INSTALLED=$_GLOBAL["ISCSI_CLIENT_INSTALLED"];}
			if($this->LinuxDistriCode=="DEBIAN"){$this->AS_DEBIAN_FAMILY=true;}
			if($this->LinuxDistriCode=="UBUNTU"){$this->AS_DEBIAN_FAMILY=true;}
			$this->APACHE_MOD_SECURITY=$_GLOBAL["APACHE_MOD_SECURITY"];
			$this->APACHE_MOD_EVASIVE=$_GLOBAL["APACHE_MOD_EVASIVE"];
			$this->OPENVPN_APPLIANCE=$_GLOBAL["OPENVPN_APPLIANCE"];
			if(isset($_GLOBAL["Z_PUSH_INSTALLED"])){$this->Z_PUSH_INSTALLED=$_GLOBAL["Z_PUSH_INSTALLED"];}
			if(isset($_GLOBAL["APP_AMANDA_INSTALLED"])){$this->APP_AMANDA_INSTALLED=$_GLOBAL["APP_AMANDA_INSTALLED"];}
			if(isset($_GLOBAL["DRUPAL7_INSTALLED"])){$this->DRUPAL7_INSTALLED=$_GLOBAL["DRUPAL7_INSTALLED"];}
			if(isset($_GLOBAL["ARTICADB_INSTALLED"])){$this->ARTICADB_INSTALLED=$_GLOBAL["ARTICADB_INSTALLED"];}
			if(isset($_GLOBAL["APP_TOMCAT6_INSTALLED"])){$this->APP_TOMCAT6_INSTALLED=$_GLOBAL["APP_TOMCAT6_INSTALLED"];}
			if(isset($_GLOBAL["APP_TOMCAT6_VERSION"])){$this->APP_TOMCAT6_VERSION=$_GLOBAL["APP_TOMCAT6_VERSION"];}
			if(isset($_GLOBAL["APP_SQUIDDB_INSTALLED"])){$this->APP_SQUIDDB_INSTALLED=$_GLOBAL["APP_SQUIDDB_INSTALLED"];}
			
			
			$this->TOMCAT_JAVA=$_GLOBAL["TOMCAT_JAVA"];
			$this->TOMCAT_DIR=$_GLOBAL["TOMCAT_DIR"];
			$this->APACHE_MOD_TOMCAT=$_GLOBAL["APACHE_MOD_TOMCAT"];
			$this->OPENEMM_VERSION=$_GLOBAL["OPENEMM_VERSION"];
			$this->MSKTUTIL_INSTALLED=$_GLOBAL["MSKTUTIL_INSTALLED"];
			$this->OPENEMM_SENDMAIL_VERSION=$_GLOBAL["OPENEMM_SENDMAIL_VERSION"];
			$this->PIWIK_INSTALLED=$_GLOBAL["PIWIK_INSTALLED"];
			$this->APACHE_MOD_INFO=$_GLOBAL["APACHE_MOD_INFO"];
			$this->APACHE_MOD_STATUS=$_GLOBAL["APACHE_MOD_STATUS"];
			$this->APACHE_APPLIANCE=$_GLOBAL["APACHE_APPLIANCE"];
			$this->APACHE_MOD_SUEXEC=$_GLOBAL["APACHE_MOD_SUEXEC"];
			$this->APACHE_MOD_FCGID=$_GLOBAL["APACHE_MOD_FCGID"];		
			
			$this->APP_KHSE_INSTALLED=$_GLOBAL["APP_KHSE_INSTALLED"];
			
			if(isset($_GLOBAL["deduplication_installed"])){$this->deduplication_installed=$_GLOBAL["deduplication_installed"];}
			$this->EnableManageUsersTroughActiveDirectory=$_GLOBAL["EnableManageUsersTroughActiveDirectory"];
			$this->ActiveDirectoryCredentials=$_GLOBAL["ActiveDirectoryCredentials"];
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}
			if(!is_object($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$ArticaMetaEnabled=trim($sock->GET_INFO("ArticaMetaEnabled"));
			if($ArticaMetaEnabled==null){$sock->SET_INFO("ArticaMetaEnabled","0");}
			if($ArticaMetaEnabled==1){$this->ARTICA_META_ENABLED=true;}
			if($this->PROXYTINY_APPLIANCE){$this->SQUID_APPLIANCE=true;}
			
			
			if(intval($sock->GET_INFO("HaProxyInstalled"))==1){$this->HAPROXY_INSTALLED=true;}else{$this->HAPROXY_INSTALLED=false;}
			
			
			$pear=explode(";",$_GLOBAL["PEAR_PACKAGES"]);
			if(is_array($pear)){
				while (list ($num, $pp) = each ($pear) ){
					if(trim($pp)==null){continue;}
					$this->PEAR_PACKAGES[$pp]=true;
				}
			}
			
			
			$this->CPU_NUMBER=$_GLOBAL["CPU_NUMBER"];
			$this->LOAD_AVERAGE=$_GLOBAL["LOAD_AVERAGE"];
			
			if($this->OPENVPN_APPLIANCE){
				$this->SQUID_INSTALLED=false;
				$this->SAMBA_INSTALLED=false;
				$this->POSTFIX_INSTALLED=false;
				$this->cyrus_imapd_installed=false;
				$this->ZARAFA_INSTALLED=false;
				$this->BACKUPPC_INSTALLED=false;
				$this->nmap_installed=false;
			}			
			
			if($this->KASPERSKY_WEB_APPLIANCE){
				$this->POSTFIX_INSTALLED=false;
				$this->APP_AUDITD_INSTALLED=false;
				$this->BACKUPPC_INSTALLED=false;
				$this->KAV4PROXY_INSTALLED=true;
				
			}
			if(is_file(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="))){
				$data=trim(@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw==")));
				if($data=="TRUE"){$this->CORP_LICENSE=true;}
			}
			
		}
		
	private function postfixmutli(){
		if(!$this->POSTMULTI){return false;}
		$vers=explode(".",$this->POSTFIX_VERSION);
		if($vers[0]>=2){if($vers[1]>=5){return true;}}
	}	

	public function EnablePDNS(){
		
		$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");
		if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
		if(!$this->POWER_DNS_INSTALLED){return 0;}
		if($this->EnableDNSMASQ()==1){return 0;}
	
	
		if($this->APP_CHILLI_INSTALLED){
			$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
			if(!is_numeric($EnableChilli)){$EnableChilli=0;}
			if($EnableChilli==1){return 0;}
		}
	
		return $EnablePDNS;
	}
	public function EnableDNSMASQ(){
		
		if(!$this->dnsmasq_installed){return 0;}
		$EnableDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSMASQ");
		if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
		return $EnableDNSMASQ;
	}	
	
}

?>