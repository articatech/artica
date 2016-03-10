<?php
$mem=round(((memory_get_usage()/1024)/1000),2);events("START WITH {$mem}MB ","MAIN",__LINE__);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.auth.tail.inc");
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB before class.users.menus.inc","MAIN",__LINE__);
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/framework/class.syslogger.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.shorewall.inc");
include_once(dirname(__FILE__)."/ressources/class.c-icap.monitor.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.monitor.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.logs.inc");
$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after class.settings.inc","MAIN",__LINE__);
if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}


if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(!Build_pid_func(__FILE__,"MAIN")){
	events(basename(__FILE__)." Already executed.. aborting the process");
	die();
}



$pid=getmypid();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/xapian",0755,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/infected-queue",0755,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/snort-queue",0755,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/pdns-hack-queue",0755,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/dansguardian-stats4",0755,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/haproxy-rtm",0755,true);
@mkdir("/etc/artica-postfix/croned.1",0755,true);
if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/dhcpd")){echo "Starting......: ".date("H:i:s")." sysloger Creating dhcpd queue\n";@mkdir("{$GLOBALS["ARTICALOGDIR"]}/dhcpd",0755,true);}
events("running $pid ");
file_put_contents($pidfile,$pid);
$sock=new sockets();
$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
$users=new settings_inc();
$unix=new unix();
$GLOBALS["EnableOpenLDAP"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));

$GLOBALS["SQUID_INSTALLED"]=false;
$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
$GLOBALS["RSYNC_RECEIVE"]=array();
$GLOBALS["LOCATE_PHP5_BIN"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["PS_BIN"]=$unix->find_program("ps");
$GLOBALS["SID"]="";
$squidbin=$unix->find_program("squid3");
if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
$GLOBALS["SQUIDBIN"]=$squidbin;
$GLOBALS["EnableKerbAuth"]=$EnableKerbAuth;
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["sysctl"]=$unix->find_program("sysctl");
$GLOBALS["CHMOD_BIN"]=$unix->find_program("chmod");
$GLOBALS["CHOWN_BIN"]=$unix->find_program("chown");
$GLOBALS["SMARTCTL_BIN"]=$unix->find_program("smartctl");
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["REBOOT_BIN"]=$unix->find_program("reboot");
$GLOBALS["SYNC_BIN"]=$unix->find_program("sync");
$GLOBALS["DF_BIN"]=$unix->find_program("df");
$GLOBALS["COUNT-LINES"]=0;
$GLOBALS["COUNT-LINES-TIME"]=0;
$GLOBALS["ufdbguardd_path"]=$unix->find_program("ufdbguardd");
$GLOBALS["PGREP_BIN"]=$unix->find_program("pgrep");
$GLOBALS["SHUTDOWN_BIN"]=$unix->find_program("shutdown");
$GLOBALS["UCARP_MASTER"]=null;
if(is_file($GLOBALS["SQUIDBIN"])){ $GLOBALS["SQUID_INSTALLED"]=true; }
if(is_file("/usr/share/ucarp/Master")){ $GLOBALS["UCARP_MASTER"]=@file_get_contents("/usr/share/ucarp/Master"); }
					
$GLOBALS["ROUNDCUBE_HACK"]=0;
$GLOBALS["PDNS_HACK"]=$sock->GET_INFO("EnablePDNSHack");
$GLOBALS["PDNS_HACK_MAX"]=$sock->GET_INFO("PDNSHackMaxEvents");
if(!is_numeric($GLOBALS["PDNS_HACK_MAX"])){$GLOBALS["PDNS_HACK_MAX"]=3;}
if(!is_numeric($GLOBALS["PDNS_HACK"])){$GLOBALS["PDNS_HACK"]=1;}
$GLOBALS["PDNS_HACK_DB"]=array();
@mkdir("/home/artica/haproxy-postgres/realtime-events",0755,true);

$unix=new unix();
$GLOBALS["NODRYREBOOT"]=$sock->GET_INFO("NoDryReboot");
$GLOBALS["NOOUTOFMEMORYREBOOT"]=$sock->GET_INFO("NoOutOfMemoryReboot");
if(!is_numeric($GLOBALS["NOOUTOFMEMORYREBOOT"])){$GLOBALS["NOOUTOFMEMORYREBOOT"]=0;}
$GLOBALS["CLASS_SOCKET"]=$sock;
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["CLEANCMD"]="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.clean.logs.php --urgency >/dev/null 2>&1 &";
$GLOBALS["CLASS_C_ICAP_MONITOR"]=new c_icap_monitor();

$sock=null;
$unix=null;

$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB before forking","MAIN",__LINE__);

$_GET["server"]=$users->hostname;
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
$buffer .= fgets($pipe, 4096);
try{ Parseline($buffer);}catch (Exception $e) {events("fatal error:".  $e->getMessage());}

$buffer=null;
}



fclose($pipe);
events("Shutdown...");
die();


function Parseline($buffer){
	$buffer=trim($buffer);
	
	$GLOBALS["COUNT-LINES"]++;
	if($GLOBALS["COUNT-LINES"]>5000){
		$GLOBALS["TOTAL-LINES"]=$GLOBALS["TOTAL-LINES"]+$GLOBALS["COUNT-LINES"];
		$distanceInSeconds = round(abs(time() - $GLOBALS["COUNT-LINES-TIME"]));
		$distanceInMinutes = round($distanceInSeconds / 60);
		events("{$GLOBALS["TOTAL-LINES"]} Parsed...");
		if($distanceInMinutes>2){
			events("{$GLOBALS["TOTAL-LINES"]} Check size...");
			$GLOBALS["COUNT-LINES-TIME"]=time();
			shell_exec($GLOBALS["CLEANCMD"]);
			$GLOBALS["COUNT-LINES"]=0;
		}
		
	}
	
	if(strpos($buffer, "FIREHOL:")>0){FIREHOL_PARSER($buffer);return;}
	if(strpos($buffer,'):  operation="file_perm" pid=')>0){return;}


	$dust=new syslogger();
	if(strpos($buffer,"]: [DEBUG]")>0){return;}
	if(strpos($buffer,"class.process.inc[")>0){return;}
	if(strpos($buffer,"caches_infos()")>0){return;}
	if(strpos($buffer,"MemorySync(){")>0){return;}
	if(strpos($buffer,"ufdbguard-tail[")>0){return;}
	if($dust->MailDustbin($buffer)){return;}
//kernel dustbin
	if(strpos($buffer,"ext4_dx_add_entry: Directory index full")>0){return true;}
	if(strpos($buffer,"] ll header:")>0){return true;}
	if(strpos($buffer,"exec.squid.watchdog.php")>0){return true;}
	if(strpos($buffer,"using local addresses only for domain")>0){return true;}
	
	//squid dustbin
	if(strpos($buffer,"Load average increasing, re-enabling all cpus for irq balancing")>0){return true;}
	if(strpos($buffer,"artica-watchdog[")>0){return true;}
	if(strpos($buffer,"exec.syslog-engine.php")>0){return true;}
	if(strpos($buffer,"exec.postfix-logger.php")>0){return true;}
	if(strpos($buffer,"]: WARNING: ")>0){return true;}
	if(strpos($buffer," epmd running")>0){return true;}
	if(strpos($buffer,"#]: Startup: Initializing")>0){return true;}	
	if(strpos($buffer,"]: Reconfiguring Squid Cache")>0){return true;}	
	if(strpos($buffer,"]: Closing HTTP port")>0){return true;}	
	if(strpos($buffer,"]: Processing Configuration File:")>0){return true;}	
	if(strpos($buffer,"]: Startup: Initialized")>0){return true;}	
	if(strpos($buffer,"]: Warning: empty ACL")>0){return true;}	
	if(strpos($buffer,"]: Accepting HTTP Socket connections")>0){return true;}	
	if(strpos($buffer," RELEASE ")>0){return true;}	
	if(strpos($buffer," SWAPOUT ")>0){return true;}	
	if(strpos($buffer,"RELEASE -1 FFFFFFFF")>0){return true;}	
//Postfix dustbin


	if(preg_match("#Do you need to run.+?sa-update#",$buffer)){amavis_sa_update($buffer);return;}
	if(strpos($buffer," fcrontab[")>0){return true;}	
	if(strpos($buffer,"exec.mailarchive.php")>0){return true;}	
	if(strpos($buffer,"Orphan Comm::Connection: local=")>0){return true;}	
	if(strpos($buffer,"class.mysql.squid.builder.php")>0){return true;}
	if(strpos($buffer,"Orphans since last started")>0){return true;}
	if(strpos($buffer,"general, No Profile configured! Allowing")>0){return true;}
	if(strpos($buffer,"general, KHSE: no threat detected in")>0){return true;}
	if(preg_match("#exec.dstat.top.php#",$buffer)){return true;}
	if(preg_match("#artica-filter#",$buffer)){return true;}
	if(preg_match("#postfix\/#",$buffer)){return true;}
	if(preg_match("#CRON\[#",$buffer)){return true;}
	if(preg_match("#: CACHEMGR:#",$buffer)){return true;}
	if(preg_match("#exec\.postfix-logger\.php:#",$buffer)){return true;}
	if(preg_match("#artica-install\[#",$buffer)){return true;}
	
	// monit dustbin
	if(preg_match("#monitor action done#",$buffer)){return true;}
	if(preg_match("#monitor service.+?on user request#",$buffer)){return true;}
	if(preg_match("#CRON\[.+?\(root\).+CMD#",$buffer)){return true;}
	if(preg_match("#winbindd\[.+?winbindd_listen_fde_handler#",$buffer)){return true;}
	if(strpos($buffer,"Other action already in progress -- please try again later")>0){return true;}
	if(strpos($buffer,"class.cronldap.inc")>0){return true;}
	if(strpos($buffer,"Awakened by User defined")>0){return true;}
	if(strpos($buffer,": Checking summary")>0){return true;}
	
	//Zarafa dustbin
	if(strpos($buffer,": End of session (logoff)")>0){return true;}
	if(strpos($buffer," receives session ")>0){return true;}
	if(strpos($buffer,": Disconnecting client")>0){return true;}
	if(strpos($buffer,"  thread exiting")>0){return true;}
	if(strpos($buffer,": Accepted connection from")>0){return true;}
	if(strpos($buffer,": Not authorized for command: CAPA")>0){return true;}
	if(strpos($buffer,": Starting worker process for")>0){return true;}
	
	// **************** peut être utilisé ???
	if(strpos($buffer,"User supplied password using program zarafa-gateway")>0){return true;}
	if(strpos($buffer,"authenticated through User supplied password using program")>0){return true;}
	if(strpos($buffer,"authenticated through Pipe socket using program")>0){return true;}
	if(strpos($buffer,"conntrack-tools[")>0){return true;}
	if(strpos($buffer,"]: (root) CMD (")>0){return true;}
	if(strpos($buffer,"]: MemoryInstances")>0){return true;}
	if(strpos($buffer,"]: launch_all_status(")>0){return true;}
	if(strpos($buffer,"]: PROCESS IN MEMORY")>0){return true;}
	if(strpos($buffer,">/dev/null 2>&1 &")>0){return true;}
	if(strpos($buffer,"executed...end")>0){return true;}
	if(strpos($buffer,"requests per minute")>0){return true;}
	if(strpos($buffer,"Ask all status to MONIT")>0){return true;}
	if(strpos($buffer,"exec.status.php[")>0){return true;}
	
	if(preg_match("#slapd.+?conn=[0-9]+\s+fd=.+?closed#",$buffer)){return true;}
	if(strpos($buffer,"msmtp: ")>0){return true;}
	if(strpos($buffer,"*system*awstats")>0){return true;}
	if(strpos($buffer,"extra modules loaded after daemonizing/chrooting")>0){return;}
	if(strpos($buffer,"/etc/cron.d/awstats")>0){return;}
	if(strpos($buffer,"emailrelay:")>0){return;}
	if(strpos($buffer,"pptpd-logwtmp.so loaded")>0){return;}
	if(strpos($buffer,"Reinitializing monit daemon")>0){return;}
	if(strpos($buffer,"Monit reloaded")>0){return;}
	if(strpos($buffer,"Tarticaldap.logon")>0){return;}
	if(strpos($buffer,"pulseaudio[")>0){return;}
	if(strpos($buffer,"exec: /usr/bin/php5")>0){return;}
	if(strpos($buffer,"Found decoder for ")>0){return;}
	if(strpos($buffer,"Internal decoder for ")>0){return;}
	if(strpos($buffer,"Loaded Icons")>0){return;}
	if(strpos($buffer,"CP ConfReq")>0){return;}
	if(strpos($buffer,"CP ConfAck")>0){return;}
	if(strpos($buffer,"CP EchoReq")>0){return;}
	if(strpos($buffer,"/usr/sbin/cron")>0){return;}
	if(strpos($buffer,"no IPv6 routers present")>0){return;}
	if(strpos($buffer,"AM.PDP-SOCK")>0){return;}
	if(strpos($buffer,"disconnect from unknown")>0){return;}

//amavis - Mail Dutdsbin


	//LDAP Dustbin
	if(strpos($buffer,"SEARCH RESULT tag=")>0){return;}
	if(strpos($buffer,'SRCH base="cn=')>0){return;}
	if(strpos($buffer,'ACCEPT from IP=')>0){return;}
	if(strpos($buffer,'closed (connection lost)')>0){return;}

	//automount dustbin
	if(strpos($buffer,"handle_packet: type")>0){return;}
	if(strpos($buffer,"dev_ioctl_send_fail: token")>0){return;}
	if(strpos($buffer,"lookup_mount: lookup(ldap)")>0){return;}
	if(strpos($buffer,"handle_packet_missing_indirect: token")>0){return;}
	if(strpos($buffer,"getuser_func: called with context")>0){return;}
	if(strpos($buffer,"attempting to mount entry /automounts")>0){return;}
	if(strpos($buffer,"lookup_one: lookup(ldap)")>0){return;}
	if(strpos($buffer,"do_bind: lookup(ldap):")>0){return;}
	if(strpos($buffer,"sun_mount: parse")>0){return;}
	if(strpos($buffer,"]: failed to mount /")>0){return;}
	if(strpos($buffer,"]: do_mount:")>0){return;}
	if(strpos($buffer,"]: parse_mount: parse")>0){return;}
	if(strpos($buffer,"mount_mount: mount(generic):")>0){return;}
	if(strpos($buffer,">> Error connecting to")>0){return;}
	if(strpos($buffer,">> Refer to the mount")>0){return;}
	if(strpos($buffer,"getpass_func: context (nil)")>0){return;}

	//ROOT Dustbin
	if(strpos($buffer,"(root) CMD")>0){return;}
	if(strpos($buffer,"RELOAD (/etc/cron")>0){return;}
	//Cyrus DUSTBIN


	//pdns dustbin
	if(strpos($buffer,"question for '")>0){return;}
	if(strpos($buffer,"answer to question '")>0){return;}
	if(strpos($buffer,"failed (res=3)")>0){return;}
	if(preg_match("#pdns_recursor\[[0-9]+\]: \[[0-9]+\]\s+#", $buffer)){return;}
	
	//roundcube dustbin
	if(strpos($buffer,"IMAP Error: Empty password")>0){return;}


	//monit dustbin
	if(strpos($buffer,"Monit has not changed")>0){return;}
	if(strpos($buffer,": synchronized to ")>0){return;}
	if(strpos($buffer,"monit HTTP server stopped")>0){return;}
	if(strpos($buffer,"Shutting down monit HTTP server")>0){return;}
	if(strpos($buffer,"Starting monit HTTP server at")>0){return;}
	if(strpos($buffer,"Reinitializing monit - Control")>0){return;}
	//squid dustbin:

	if(strpos($buffer,"Unlinkd pipe opened on FD")>0){return;}
	if(strpos($buffer,"Beginning Validation Procedure")>0){return;}

	//EMAILRELAY DUSTBIN
	if(strpos($buffer,"emailrelay: info: failing file")>0){return;}
	if(strpos($buffer,"emailrelay: info: no more messages to send")>0){return; }
	if(strpos($buffer,"emailrelay: warning: cannot do tls")>0){return; }
	if(strpos($buffer,"]: monit daemon at")>0){return;}
	if(strpos($buffer,"artica-ldap[")>0){return;}
	if(strpos($buffer,"want to change spamassassin settings but not installed")>0){return;}
 
	//SAMBA DUSTBIN
	if(strpos($buffer,"smb_register_idmap")>0){return;}
	if(strpos($buffer,"could not find idmap alloc module ad")>0){return;}
	if(strpos($buffer,"Idmap module nss already registered")>0){return;}
	if(strpos($buffer,"'winbindd' process PID changed to")>0){return;}
	if(strpos($buffer,"idmap_alloc module tdb already registered")>0){return;}
	if(strpos($buffer,"ad_idmap_cached_connection_internal")>0){return;}
	if(strpos($buffer,"idmap_ad_unixids_to_sids")>0){return;}
	if(strpos($buffer,"libads/kerberos.c:")>0){return;}
	if(strpos($buffer,"initialize_winbindd_cache")>0){return;}
	if(strpos($buffer,"winbindd/winbindd_group.c")>0){return;}
	if(strpos($buffer,"winbindd/winbindd_util.c")>0){return;}
	if(strpos($buffer,"smb_register_idmap_alloc")>0){return;}
	if(strpos($buffer,"Idmap module passdb already registered")>0){return;}
	if(strpos($buffer,"Cleaning up brl and lock database after unclean shutdown")>0){return;}
	if(strpos($buffer,"winbindd_sig_term_handler")>0){return;}
	if(strpos($buffer,"wins_registration_timeout")>0){return;}
	if(strpos($buffer,":   netbios connect:")>0){return;}
	if(strpos($buffer,"cleanup_timeout_fn")>0){return;}
	if(strpos($buffer,"struct wbint_Gid2Sid")>0){return;}
	if(strpos($buffer,":   doing parameter")>0){return;}
	if(strpos($buffer,"param/loadparm.c")>0){return;}
	if(strpos($buffer,":   wins_registration_timeout:")>0){return;}
	if(strpos($buffer,"src: struct server_id")>0){return;}
	if(strpos($buffer,"dest: struct server_id")>0){return;}
	if(strpos($buffer,"messages: struct messaging_rec")>0){return;}
	if(strpos($buffer,"ndr/ndr.c")>0){return;}
	if(strpos($buffer,"smbd/reply.c")>0){return;}
	if(strpos($buffer,"lib/smbldap.c")>0){return;}
	if(strpos($buffer,"srvsvc_NetShare")>0){return;}
	if(strpos($buffer,"]:   Global parameter")>0){return;}
	if(strpos($buffer,"STYPE_IPC_HIDDEN")>0){return;}
	if(strpos($buffer,"STYPE_DISKTREE")>0){return;}
	if(strpos($buffer,": NTLMSSP_")>0){return;}
	if(strpos($buffer,"MSG_SMB_UNLOCK")>0){return;}
	if(strpos($buffer,":           messages: ARRAY(")>0){return;}
	if(strpos($buffer,"struct messaging_array")>0){return;}
	if(strpos($buffer,":                   msg_version              :")>0){return;}
	if(strpos($buffer,":           num_messages             :")>0){return;}
	if(strpos($buffer,":                   sid                      :")>0){return;}
	if(strpos($buffer,":               sid                      :")>0){return;}
	if(strpos($buffer,":                       id                       :")>0){return;}
	if(strpos($buffer,":               dom_name                 :")>0){return;}
	if(strpos($buffer,":                   msg_version              :")>0){return;}
	if(strpos($buffer,":                   buf                      :")>0){return;}
	if(strpos($buffer,":               result                   :")>0){return;}
	if(strpos($buffer,":               gid                      :")>0){return;}
	if(strpos($buffer,"server_unc")>0){return;}
	if(strpos($buffer,"union ntlmssp_AvValue")>0){return;}
	if(strpos($buffer,"MsvAvNbDomainName")>0){return;}
	if(strpos($buffer,"NegotiateFlags")>0){return;}
	if(strpos($buffer,"AvDnsComputerName")>0){return;}
	if(strpos($buffer,"Version: struct VERSION")>0){return;}
	if(strpos($buffer,"array: ARRAY(")>0){return;}
	if(strpos($buffer,"info_ctr")>0){return;}
	if(strpos($buffer,"init_sam_from_ldap: Entry found")>0){return;}
	//Snort dustbin
	
	//pdns_recursor[23651]: stats: 600 questions, 665 cache entries, 29 negative entries, 0% cache hits"
	// check_ntlm_password:  Authentication for user [root] -> [root] FAILED with error NT_STATUS_WRONG_PASSWORD
	if(strpos($buffer,"]: last message repeated")>0){return;}


	//pdns dustbin
	if(strpos($buffer,"Looking for CNAME")>0){return;}
	if(strpos($buffer,"No CNAME cache hit of")>0){return;}
	if(strpos($buffer,"Found cache hit")>0){return;}
	if(strpos($buffer,": Resolved '")>0){return;}
	if(strpos($buffer,": Trying IP")>0){return;}
	if(strpos($buffer,".: Got 1 answers")>0){return;}
	if(strpos($buffer,": accept answer")>0){return;}
	if(strpos($buffer,": determining status")>0){return;}
	if(strpos($buffer,": got negative caching")>0){return;}
	if(strpos($buffer,": No cache hit for")>0){return;}
	if(strpos($buffer,": Checking if we have NS")>0){return;}
	if(strpos($buffer,": no valid/useful NS")>0){return;}
	if(strpos($buffer,": NS (with ip, or non-glue)")>0){return;}
	if(strpos($buffer,": We have NS in cache")>0){return;}
	if(strpos($buffer,".: Nameservers:")>0){return;}
	if(strpos($buffer,": Trying to resolve NS")>0){return;}
	if(strpos($buffer,".: got NS record")>0){return;}
	if(strpos($buffer,".: status=")>0){return;}
	if(strpos($buffer,".: Starting additional")>0){return;}
	if(strpos($buffer,".: Done with additional")>0){return;}
	if(strpos($buffer,".: Found cache CNAME hit")>0){return;}
	if(strpos($buffer,".: answer is in")>0){return;}
	if(strpos($buffer,"is negatively cached via")>0){return;}
	if(strpos($buffer,".: within bailiwick")>0){return;}
	if(strpos($buffer,"]: Query: '")>0){return;}
	if(strpos($buffer,"bdb_equality_candidates:")>0){return;}
	if(strpos($buffer,"Cache consultations done")>0){return;}
	if(strpos($buffer,".: Entire record")>0){return;}
	if(strpos($buffer,"got upwards/level NS record")>0){return;}
	if(strpos($buffer,"), rcode=0, in")>0){return;}
	if(strpos($buffer,"]    ns1.")>0){return;}
	if(strpos($buffer,"error resolving, possible error: Connection refused")>0){return;}
	if(strpos($buffer,"Failed to resolve via any of the")>0){return;}
	if(strpos($buffer,"failed (res=-1)")>0){return;}
	if(strpos($buffer,"question answered from packet cache from")>0){return;}
	if(strpos($buffer,": timeout resolving")>0){return;}
	if(strpos($buffer,": query throttled")>0){return;}
	if(strpos($buffer,"]: Invalid query packet")>0){return;}
	if(strpos($buffer,'BIND dn="cn=')>0){return;}
	if(strpos($buffer,'RESULT tag=')>0){return;}
	if(strpos($buffer,'SRCH base="')>0){return;}
	if(strpos($buffer,'SRCH attr=')>0){return;}
	if(strpos($buffer,'MOD attr=')>0){return;}
	if(strpos($buffer,'MOD dn=')>0){return;}
	if(strpos($buffer,' UNBIND')>0){return;}
	if(strpos($buffer,": connection_input: conn=")>0){return;}
	if(strpos($buffer,"attr=dNSTTL aRecord nSRecord cNAMERecord")>0){return;}
	if(strpos($buffer,": monit HTTP server started")>0){return;}
	if(strpos($buffer,"Awakened by the")>0){return;}
	

	
if(strpos($buffer, "]: HASTATS:::")){
	haproxy_parseline($buffer);return;
	
}
	
if(preg_match("#ACTION REQUIRED RESTART MYSQL-SQUID#", $buffer)){
	$file="/etc/artica-postfix/croned.1/ACTION.REQUIRED.RESTART.MYSQL.SQUID";
	if(IfFileTime($file,1)){
		squid_admin_mysql(1, "MySQL database issue [action=restart]","$buffer",__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db restart");
	}
	
	
}
	
// ******************************************************************************************************************************************	
if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is going\s+([A-Z]+)\s+#", $buffer,$re)){squid_admin_mysql(1, "Load-balancing {$re[1]} going on state {$re[2]} [action=notify]",$buffer,__FILE__,__LINE__);return;}
if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is\s+(DOWN|UP).*?reason: (.+?), check duration#", $buffer,$re)){squid_admin_mysql(0, "Load-balancing {$re[1]} is on state {$re[2]} ! reason:{$re[3]} [action=notify]",$buffer,__FILE__,__LINE__);return;}
if(preg_match("#haproxy\[.*?Server\s+(.+?)\s+is\s+([A-Z\/]+)\s+#", $buffer,$re)){squid_admin_mysql(1, "Load-balancing {$re[1]} going on state {$re[2]} [action=notify]",$buffer,__FILE__,__LINE__);return;}	
	
//Clamd	
// ******************************************************************************************************************************************
if(preg_match("#clamd.*?Can.*?create (new file|temporary directory) ERROR#", $buffer)){
		$ClamavTemporaryDirectory=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ClamavTemporaryDirectory"));
		if($ClamavTemporaryDirectory==null){$ClamavTemporaryDirectory="/home/clamav";}
		@mkdir($ClamavTemporaryDirectory,0755,true);
		@chmod($ClamavTemporaryDirectory, 0755);
		@chmod(dirname($ClamavTemporaryDirectory), 0755);
		@chown($ClamavTemporaryDirectory, "clamav");
		@chgrp($ClamavTemporaryDirectory, "clamav");
		$file="/etc/artica-postfix/croned.1/clamd.tempdir.error";
		if(IfFileTime($file,5)){
			squid_admin_mysql(1, "File operation error on $ClamavTemporaryDirectory [action=check permissions]",
			"The directory $ClamavTemporaryDirectory was created and owned by user/group clamav:clamav",__FILE__,__LINE__);
		}
		return;
}
// ******************************************************************************************************************************************
	


if(preg_match("#APP_SQUID.*?total mem amount of\s+(.+?)kB matches resource limit.*?total mem amount.*?(.+?)kB#", $buffer,$re)){
	$Current=$re[1];
	$max=$re[2];
	squid_admin_mysql(0, "Proxy service memory exceed {$max}kB !! ({$Current}Kb)", $buffer,__FILE__,__LINE__);
	return;
}


// SS5 
if(preg_match("#ss5:.*SS5 Version [0-9\.]+ - Release [0-9]+ starting#",$buffer)){
	squid_admin_mysql(2, "Socks Proxy started", $buffer,__FILE__,__LINE__);
	return;
}
	
//nginx
if(preg_match("#nginx:.*?notice.+?gracefully shutting down#", $buffer)){
	squid_admin_mysql(1, "Reverse Proxy service gracefully shutting down", $buffer,__FILE__,__LINE__);
	return;
}
if(preg_match("#nginx:.+?start worker processes#", $buffer)){
	squid_admin_mysql(2, "Reverse Proxy service starting", $buffer,__FILE__,__LINE__);
	return;
}

if(preg_match("#nginx:.*?:\s+reconfiguring#", $buffer)){
	squid_admin_mysql(1, "Reverse Proxy service was reconfigured", $buffer,__FILE__,__LINE__);
	return;
}
	
	
if(preg_match("#wifidog.*?Failed to open HTML message file#", $buffer)){
	squid_admin_mysql(1, "Creating HTML message for the Hotspot", null,__FILE__,__LINE__);
	shell_exec2("{$GLOBALS["LOCATE_PHP5_BIN"]} {$GLOBALS["BASE_ROOT"]}/hostpot.php --templates >/dev/null 2>&1");
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} {$GLOBALS["BASE_ROOT"]}/exec.wifidog.php --restart");
	return;
}
	
if(preg_match("#wifidog\[.*?Removing Firewall rules#",$buffer,$re)){
	squid_admin_mysql(1, "HotSpot is stopped", null,__FILE__,__LINE__);
	return;
}
	
	
//Crash kernel


if(preg_match("#smartd.*?Try 'smartctl(.*?)'\s+to turn on SMART features#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/smartd.smartctl".md5($re[1]);
	if(IfFileTime($file,5)){
		system("{$GLOBALS["SMARTCTL_BIN"]} {$re[1]}");
		if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(2, "Running {$GLOBALS["SMARTCTL_BIN"]} {$re[1]}",__FILE__,__LINE__);}
	}
	return;
}


if(preg_match("#nf_queue: full at [0-9]+ entries,\s+dropping packets#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/nf_queue.full";
	if(IfFileTime($file,5)){
		system_admin_events("Fatal! nf_queue is full\n$buffer\nYou should consider increase your hardware memory and CPU\nor disable Network application detection", __FUNCTION__, __FILE__, __LINE__, "system");
		if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Fatal! nf_queue is full", "$buffer\nYou should consider increase your hardware memory and CPU\nor disable Network application detection",__FILE__,__LINE__);}
	}
	return;
}


if(preg_match("#glibc detected.*?\/(.+?):\s+(.+?):#",$buffer,$re)){
	system_admin_events("Fatal! Crash {$re[1]} {$re[2]} [action=Run Sync]\n$buffer", __FUNCTION__, __FILE__, __LINE__, "system");
	if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Fatal! Crash {$re[1]} {$re[2]} [action=Run Sync]", $buffer,__FILE__,__LINE__);}
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["SYNC_BIN"]} >/dev/null 2>&1 &");
	return;
}

if(preg_match("#kernel:\[.*?general protection fault:\s+[0-9]+\s+\[\#([0-9]+)\]\s+SMP#", $buffer,$re)){
	if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Kernel crash !! general protection fault on SMP processor", $buffer,__FILE__,__LINE__);}
	system_admin_events("Kernel crash !! general protection fault on SMP Processor\n$buffer", __FUNCTION__, __FILE__, __LINE__, "system");
	return;
}
	
	
if(preg_match("#kernel:.*?squid\[.*?segfault at.*?error.*?in squid#",$buffer)){
	squid_admin_mysql(0, "Fatal, proxy service was crashed !!!","Here it is the report\n$buffer\nService is automatically started\n",__FILE__,__LINE__);
	shell_exec(trim("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.watchdog.php --start --crashed --cache-logs >/dev/null 2>&1 &"));
	return;
}

if(preg_match("#class\.sockets\.inc.*?Fatal ERROR 500#",$buffer)){
	shell_exec(trim("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.framework.php --restart >/dev/null 2>&1 &"));
	return;
}
		
if(strpos($buffer,"rdpproxy:")>0){
	if(!isset($GLOBALS["CLASS_RDPPROXY_MONITOR"])){ $GLOBALS["CLASS_RDPPROXY_MONITOR"]=new rdpproxy_monitor(); }
	$GLOBALS["CLASS_RDPPROXY_MONITOR"]->parse($buffer);
	return;
	
}
	
	

	if(strpos($buffer,"C-ICAP")>0){
		if($GLOBALS["CLASS_C_ICAP_MONITOR"]->parse($buffer)){return;}
	}
	
	
	
//UCARP

	if(preg_match("#ucarp\[.*?Switching to state:\s+BACKUP#",$buffer)){
		if(!is_file("/usr/share/ucarp/Master")){
			foreach (glob("/usr/share/ucarp/vip-*-down.sh") as $filename) {
				$tt[]=$filename;
				shell_exec("{$GLOBALS["nohup"]} $filename >/dev/null 2>&1 &");
			}
			squid_admin_mysql(0, "FailOver: Slave switch to backup mode", "Executed\n".@implode("\n", $tt),__FILE__,__LINE__);
		}else{
			squid_admin_mysql(0, "FailOver: Master shutdown connections transfered to slave", "\n",__FILE__,__LINE__);
		}
		return;
	}
	
	if(preg_match("#ucarp\[.*?Switching to state:\s+MASTER#",$buffer)){
		if(!is_file("/usr/share/ucarp/Master")){
			squid_admin_mysql(0, "FailOver: Slave switch to Master mode and accept connections", "\n",__FILE__,__LINE__);
		}else{
			squid_admin_mysql(0, "FailOver: Master return back and accept connections", "\n",__FILE__,__LINE__);
		}
		return;
	}	
	
// SHOREWALL

if(preg_match("#Shorewall:(.+?)2(.+?):(.+?):IN=(.*?)\s+OUT=(.*?)\s+MAC=(.*?)\s+SRC=(.*?)\s+DST=(.*?)\s+.*?PROTO=(.*?)\s+.*?DPT=([0-9]+)#", $buffer,$re)){
	$ZONE_FROM=$re[1];
	$ZONE_TO=$re[2];
	$ACTION=$re[2];
	$NIC_IN=$re[4];
	$NIC_OUT=$re[5];
	$MAC_SRC=strtolower($re[6]);
	$IP_SRC=$re[7];
	$IP_DST=$re[8];
	$PROTO=$re[9];
	$PORT=$re[10];
	$DATE=date("Y-m-d H:i:s");
	$currentHour=date("YmdH");
	if(!isset($GLOBALS["MYSQL_SHOREWALL"])){$GLOBALS["MYSQL_SHOREWALL"]=new mysql_shorewall();}
	if(!isset($GLOBALS["MYSQL_SHOREWALL_T"][date("YmdH")])){$GLOBALS["MYSQL_SHOREWALL"]->BuildHourTable();}
	$sql="INSERT IGNORE INTO `FWH_$currentHour` (`ZDATE`,`ZONE_FROM`,`ZONE_TO`,`ACTION`,`NIC_IN`,`NIC_OUT`,`MAC_SRC`,`IP_SRC`,`IP_DST`,`PROTO`,`PORT`) VALUES
	('$DATE','$ZONE_FROM','$ZONE_TO','$ACTION','$NIC_IN','$NIC_OUT','$MAC_SRC','$IP_SRC','$IP_DST','$PROTO','$PORT')";
	$GLOBALS["MYSQL_SHOREWALL"]->QUERY_SQL($sql);
	if(count($GLOBALS["MYSQL_SHOREWALL_T"])>10){unset($GLOBALS["MYSQL_SHOREWALL_T"]);}
	return;
	
}

if(preg_match("#kernel:.*?:\s+(.+?):\s+link down#", $buffer,$re)){
	system_admin_events("{$re[1]}: Network Interface Down\n$buffer", __FUNCTION__, __FILE__, __LINE__, "network");
	squid_admin_mysql(0, "{$re[1]}: Network Interface Down", $buffer,__FILE__,__LINE__);
	return;
}




	
if(preg_match("#kernel:.*?\]\s+ADDRCONF.*?:\s+(.+?):\s+link is not ready#", $buffer,$re)){
	system_admin_events("{$re[1]}: Network Interface not ready\n$buffer", __FUNCTION__, __FILE__, __LINE__, "network");
	squid_admin_mysql(0, "{$re[1]}: Network Interface not ready [action=nothing]", $buffer,__FILE__,__LINE__);
	return;
}

if(preg_match("#kernel:.*?\]\s+ADDRCONF.*?:\s+(.+?):\s+link becomes ready#", $buffer,$re)){
	system_admin_events("{$re[1]}: Network Interface becomes ready\n$buffer", __FUNCTION__, __FILE__, __LINE__, "network");
	squid_admin_mysql(2, "{$re[1]}: Network Interface becomes ready", $buffer,__FILE__,__LINE__);
	return;
}

if(preg_match("#kernel:.*?:\s+(.+?):\s+link up#", $buffer,$re)){
	system_admin_events("{$re[1]}: Network Interface Up\n$buffer", __FUNCTION__, __FILE__, __LINE__, "network");
	squid_admin_mysql(2, "{$re[1]}: Network Interface Up", $buffer,__FILE__,__LINE__);
	return;
}

if(preg_match("#FATAL ERROR: unable to open remote file .*?framework\.sock#", $buffer,$re)){
	$file="/etc/artica-postfix/croned.1/lighttpd.framework.sock.error";
	if(IfFileTime($file,1)){
		system_admin_events("Framework issue, restarting framework service\n$buffer", __FUNCTION__, __FILE__, __LINE__, "artica");
		$cmd="{$GLOBALS["nohup"]} /etc/init.d/artica-framework restart >/dev/null 2>&1 &";
		shell_exec($cmd);
	}
	return;
}

if(preg_match("#lighttpd\[.*?connect failed: No such file or directory on unix:\/var\/run\/php-fpm\.sock#", $buffer,$re)){
	$file="/etc/artica-postfix/croned.1/lighttpd.phpfpm.sock.error";
	if(IfFileTime($file,1)){
		system_admin_events("PHP-FPM issue, starting PHP-FPM service\n$buffer", __FUNCTION__, __FILE__, __LINE__, "artica");
		$cmd="{$GLOBALS["nohup"]} /etc/init.d/php5-fpm start >/dev/null 2>&1 &";
		shell_exec($cmd);
	}
	return;
}

	

	
	
// LIGTTPD
if(preg_match("#lighttpd.*?connections\.c.*?SSL.*?error.*?Broken pipe#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/lighttpd.connections.Broken.pipe";
	if(IfFileTime($file,2)){ shell_exec("{$GLOBALS["nohup"]} /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &"); }
	return;
}
	
	

	if(dhcpd($buffer)){return;}
	if(preg_match("#squid.*?\[[0-9]+\]:#",$buffer)){squid_parser($buffer);return;}
	if(preg_match("#\(squid-.*?\):#",$buffer)){squid_parser($buffer);return;}
	if(preg_match("#nss_wins.*?\[[0-9]+\]:#",$buffer)){nss_parser($buffer);return;}
	if(preg_match("#haproxy.*?\[[0-9]+\]:#",$buffer)){haproxy_parser($buffer);return;}
	if(preg_match("#kernel.*?\[#",$buffer)){Kernel_parser($buffer);return;}
	
	
	
	
	
	if(preg_match("#coova-chilli.+?net\.c.*?Cannot assign requested address.*?ioctl.*?SIOCSIFFLAGS#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/coova-chilli.SIOCSIFFLAGS";
		if(IfFileTime($file,5)){
			events("HotSpot Failed to bin address, disable hotSpot system!");
			system_admin_events("HotSpot Failed to bin address, disable hotSpot system!", __FUNCTION__, __FILE__, __LINE__, "system");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableChilli", 0);
			ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
			$cmd="{$GLOBALS["nohup"]} /etc/init.d/artica-ifup start --script=".basename(__FILE__)."/".__FUNCTION__." >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="{$GLOBALS["nohup"]} /etc/init.d/chilli stop >/dev/null 2>&1 &";
			shell_exec($cmd);
			WriteFileCache($file);
			return;
		}
		events("$buffer = > TIMEOUT ... ");
		return;
	}
		
	
	if(preg_match("#'apache' total mem amount of ([0-9]+)([a-zA-Z])+\s+matches resource limit#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/apache.matches.resource.limit";
		if(IfFileTime($file,5)){
			$unit=strtolower($re[2]);
			if($unit=="kb"){$size=$re[1];$size=round($size/1024,2);
			$cmd="{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart apachesrc >/dev/null 2>&1 &";
			events("{$size}M $buffer = $cmd ... ");
			shell_exec($cmd);
			WriteFileCache($file);
			return;
		}
		events("$buffer = > TIMEOUT ... ");
		return;
	}
	
	}
		
	if(preg_match("#connect failed: No such file or directory on unix:\/var\/run\/php-fpm\.sock#",$buffer)){
		$file="/etc/artica-postfix/croned.1/lighttpd.php-fpm.sock.No.such.file.directory.0";
		if(IfFileTime($file,1)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.initslapd.php --phppfm-fix >/dev/null 2>&1 &";
			events("$buffer = $cmd ... ");
			shell_exec($cmd);
			WriteFileCache($file);
			return;
		}
		events("$buffer = > TIMEOUT ... ");
		return;
	}
	
	if(preg_match("#lighttpd.*?mod_fastcgi.*?connect failed:\s+No such file or directory on unix:\/var\/run\/php-fpm\.sock#",$buffer)){
		$file="/etc/artica-postfix/croned.1/lighttpd.php-fpm.sock.No.such.file.directory";
		if(IfFileTime($file,1)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.initslapd.php --phppfm-fix >/dev/null 2>&1 &";
			events("$buffer = $cmd ... ");
			shell_exec($cmd);
			WriteFileCache($file);
			return;
		}
		events("$buffer = > TIMEOUT ... ");
		return;
	}
	
	if(preg_match("#haarp.*?munmap_chunk.*?invalid pointer#",$buffer)){
		$GLOBALS["HAARP_FATAL"]++;
		if(haarp_remove()){return;}
		$file="/etc/artica-postfix/croned.1/haarp.invalid.pointer";
		events("invalid pointer haarp:".__LINE__);
		squid_admin_mysql(1,"Haarp issue: {$GLOBALS["HAARP_FATAL"]}/5 invalid pointer","Proxy service have issues with haarp,\n$buffer\n the service will be restarted",__FILE__,__LINE__);
		if(IfFileTime($file,3)){
			squid_admin_mysql(0,"Warning, Haarp issues.\nProxy service have issues with haarp,\n$buffer\n the service will be restarted");
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/haarp start >/dev/null 2>&1 &");
			WriteFileCache($file);
		}
		return;
		
	}
	
	if(preg_match("#kernel:\s+\[.*?haarp.*?general protection.*?libmysqlclient\.#",$buffer)){
		$GLOBALS["HAARP_FATAL"]++;
		if(haarp_remove()){return;}
		$file="/etc/artica-postfix/croned.1/haarp.general.protection";
		events("general protection haarp:".__LINE__);
		squid_admin_mysql(1,"Haarp issue: {$GLOBALS["HAARP_FATAL"]}/5 general protection libmysqlclient","Proxy service have issues with haarp,\n$buffer\n the service will be restarted",__FILE__,__LINE__);
		if(IfFileTime($file,1)){
			squid_admin_mysql(0,"Warning, Haarp issues.\nProxy service have issues with haarp,\n$buffer\n the service will be restarted");
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/haarp start >/dev/null 2>&1 &");
			WriteFileCache($file);
		}
		return;
		
	}
	
	if(preg_match("#monit\[.+?(.+?).+?start:#", $buffer)){
		squid_admin_mysql(1, "Monitor daemon detected: {{$re[1]}} is down and watchdog started it [action=notify]", $buffer,__FILE__,__LINE__);
		return;
	}
	
	
	
	
	if(preg_match("#monit\[.+?system statistic error.+?cannot get real memory buffers amount#", $buffer)){
		$file="/etc/artica-postfix/croned.1/squid.Failed.to.make.swap.directory";
		if(IfFileTime($file,10)){
			email_events("Watchdog failed, cannot get real memory buffers amount","monit claim \"$buffer\" Artica will install the latest monit version....",'system');
			shell_exec("{$GLOBALS["nohup"]} /usr/share/artica-postfix/bin/artica-make APP_MONIT >/dev/null 2>&1 &");
			WriteFileCache($file);
		}
		return;
	}
	
	
	if(preg_match("#squid\.monitrc:.*?syntax error#",$buffer)){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1 &";
		events("$buffer Monit = $cmd ... ");
		shell_exec($cmd);
		return;		
		
	}
	
	
	
	if(preg_match("#artica-cron\[.+?: Could not add job : serial queue is full#",$buffer)){
		$cmd="{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &";
		events("$buffer fcron CMD = $cmd ... ");
		shell_exec($cmd);
		return;		
		
	}
	
	if(preg_match("#cron\[.+?Fork error : could not exec.+?Cannot allocate memory#",$buffer)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			exec("{$GLOBALS["PS_BIN"]} aux 2>&1",$resultsa);
			email_events("Memory full: System will be rebooted after running after $uptime","System claim \"$buffer\" the operating system will be rebooted ($reboot).",'proxy');
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted\n".@implode("\n", $resultsa),__FILE__,__LINE__);}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;		
		}
	}
	
	
	if(preg_match("#monit: Error reading pid from file '(.+?)\/ufdbguardd.pid'#",$buffer,$re)){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.squidguard.php --ufdbguard-start >/dev/null 2>&1 &";
		events("$buffer Monit CMD = $cmd ... ");
		shell_exec($cmd);
		return;
	}
	
	
	if(preg_match("#Cannot open.*?\/var\/log\/squid\/store\.log.*?No space left on device#is",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/varlogfull";
		if(IfFileTime($file,5)){
			$results[]="\n\n--------------   SPACE AVAILABLE   -------------\n\n";
			exec("{$GLOBALS["DF_BIN"]} -h 2>&1",$results);
			$results[]="\n\n--------------   INODES AVAILABLE   -------------\n\n";
			exec("{$GLOBALS["DF_BIN"]} -i 2>&1",$results);
			squid_admin_mysql(0, "Fatal: no space left on log partition", "A specific procedure as been executed to make more free space.\nHere it is the current status\n".@implode("\n", $results),__FILE__,__LINE__);
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.varlog-urgency.php --squid >/dev/null 2>&1 &";
			shell_exec($cmd);
			WriteFileCache($file);
		}
		
	}
	
	

	if(preg_match("#\(squid-.+?Failed to make swap directory\s+(.+?):\s+\(13\)\s+Permission denied#i",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/squid.Failed.to.make.swap.directory";
		if(IfFileTime($file,10)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.squid.php --reconfigure-squid >/dev/null 2>&1 &";
			events("--> Repair squid dir '{$re[1]}'... $cmd");
			@mkdir($re[1],0755,true);@chmod($re[1],0755);@chown($re[1], "squid");@chgrp($re[1], "squid");	
			shell_exec($cmd);
			WriteFileCache($file);
		}
		return;
	}

	if(strpos($buffer, "DETECTED IN")>0){
		if(preg_match("#KHSE: THREAT\s+(.+?)\s+DETECTED IN\s+(.+)#",$buffer,$re)){
			$user="unknown";
			$local_ip="unknown";
			$rulename="Antivirus KSE";
			$category="KSE_THREAT";
			$public_ip="unknown";
			$virus=$re[1];
			$uri=$re[2];
			if(preg_match("#(|http|https|ftp|ftps)://(.+)#",$uri,$re)){$www=$re[2];}
			if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}	
			if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
			$date=time();
			$table=date('Ymd')."_blocked";	
			$md5=md5("$date,$local_ip,$rulename,$category,$www,$public_ip");
			$sql="('$local_ip','$www','$category','$rulename','$public_ip','THREAT $virus DETECTED','Security issue','unknown')";
			if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-queue")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-queue",0755,true);}
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-queue/$md5.sql",$sql);
			eventsAuth("[KHSE]: blocked THREAT $virus DETECTED IN $uri");
			return;
			}
	}
	

	

	
	
	

	$auth=new auth_tail();
	if($auth->ParseLog($buffer)){return;}
	$auth=null;
	
	// ---------------------- DANSGUARDIAN ---------------------------------
	if(strpos($buffer, "dansguardian[")>0){
		if(preg_match("#dansguardian\[.+?:\s+[0-9\.]+\s+[0-9:]+\s+(.+?)\s+([0-9\.]+)\s+(.+?)\s+\*([A-Z]+)\*\s+(.+?):\s+(.+?)\s+([A-Z]+)\s+[0-9]+\s+[0-9]+\s+(.+?)\s+([0-9]+)#", $buffer,$re)){
			$array["userid"]=trim($re[1]);
			$array["ipaddr"]=$re[2];
			$array["uri"]=$re[3];
			$array["EVENT"]=$re[4];
			$array["WHY"]=trim($re[5]);
			$array["EXPLAIN"]=$re[6];
			$array["BLOCKTYPE"]=$re[8];
			$array["RULEID"]=$re[9];
			$array["TIME"]=date('Y-m-d H:i:s');
			eventsAuth("[Dansguardian]: blocked {$array["uri"]} {$array["BLOCKTYPE"]} {$array["RULEID"]}");
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/dansguardian-stats4/".md5(serialize($array)), serialize($array));
		}
		return;
	}

// Samba/Winbind **********************************************************************************************************************************************

	if(preg_match("#winbindd\[.+?Connection to LDAP server failed for the\s+[0-9]+\s+try#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/samba.". md5("winbindd\[.+?Connection to LDAP server failed for the\s+[0-9]+\s+try").".error";
		system_admin_events("winbindd connection to LDAP failed, update password...", __FUNCTION__, __FILE__, __LINE__, "samba");
		if(IfFileTime($file,5)){
			system_admin_events("winbindd connection to LDAP failed, update password...", __FUNCTION__, __FILE__, __LINE__, "samba");
			shell_exec("{$GLOBALS["nohup"]} /usr/share/artica-postfix/exec.samba.php --smbpasswd >/dev/null 2>&1 &");
		}
			
		return true;
		
	}
	

	if(preg_match("#net:\s+ads_keytab_add_entry: unable to determine machine account's dns name in AD#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/samba.". md5("net:\s+ads_keytab_add_entry: unable to determine machine account's dns name in AD").".error";
		if(IfFileTime($file,10)){
			email_events("Active Directory: Unable to determine machine account's dns name in AD","System claims:\n$buffer\nThere is link problem with your Active Directory",'system');
			WriteFileCache($file);
		}
		return true;
	}
	
	if(preg_match("#winbindd\[.*?Could not fetch our SID - did we join#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/ntlm.samba.could.not.fetch.our.SID.join.error";
		if(IfFileTime($file,3)){
			squid_admin_mysql(0, "NTLM: not joinded", $buffer,__FILE__,__LINE__);
			$cmd="{$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.kerbauth.php --join";
			events("Active Directory: NTLM:: not joinded -> $cmd");
			shell_exec("{$GLOBALS["nohup"]} $cmd >/dev/null 2>&1 &");
			WriteFileCache($file);
		}else{
			events("Active Directory: NTLM: not joinded -> WAIT");
		}
		
		return;
	}
	
	
	
	if(preg_match("#\(ntlm_auth\): could not obtain winbind domain name\!#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/ntlm.samba.could.not.obtain.winbind.domain.name.error";
		if(IfFileTime($file,3)){
			squid_admin_mysql(0, "NTLM: could not obtain winbind domain name", $buffer,__FILE__,__LINE__);
			$cmd="{$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.kerbauth.php --join";
			events("Active Directory: NTLM:: could not obtain winbind domain name -> $cmd");
			shell_exec("{$GLOBALS["nohup"]} $cmd >/dev/null 2>&1 &");
			WriteFileCache($file);
		}else{
			events("Active Directory: NTLM:: could not obtain winbind domain name -> WAIT");
		}
		
		return;
	}
	
	if(preg_match("#smbd\[.+?:.+?PANIC\s+\(pid.+?:\s+internal error#", $buffer,$re)){
		email_events("Samba: SMBD daemon has crashed","Samba claims:\n$buffer\nArtica cannot do something, please try to re-install samba...",'samba');
		return;
	}
	
	
	if(preg_match("#kerberos_kinit_password\s+(.+?)\s+failed:\s+Preauthentication failed#i", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/samba.". md5("kerberos_kinit_password+Preauthentication failed").".error";
		if(IfFileTime($file,2)){
			squid_admin_mysql(0, "NTLM: Preauthentication failed", $buffer,__FILE__,__LINE__);
			$cmd="{$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.kerbauth.php --ping --force";
			email_events("Active Directory: Preauthentication failed","System claims:\n$buffer\nThere is link problem with your Active Directory\nArtica will try to relink the system by executing $cmd --verbose\nbut you should try to investigate if this server is able to resolve the Active Directory server",'system');
			events("Active Directory: Preauthentication failed -> $cmd");
			shell_exec("{$GLOBALS["nohup"]} $cmd >/dev/null 2>&1 &");
			$cmd="{$GLOBALS["LOCATE_PHP5_BIN"]} ". dirname(__FILE__)."/exec.kerbauth.php --join";
			events("Active Directory: Preauthentication failed -> $cmd");
			shell_exec("{$GLOBALS["nohup"]} $cmd >/dev/null 2>&1 &");
			@unlink($file);	
			WriteFileCache($file);
		}
		return true;
	}	
	
// **********************************************************************************************************************************************
	
	
	if(preg_match("#dnsmasq.+? failed to read\s+(.+?):\s+Permission denied#",$buffer,$re)){
		if(!isset($GLOBALS["aa-complain"])){$GLOBALS["aa-complain"]=$GLOBALS["CLASS_UNIX"]->find_program("aa-complain");}
		if(!isset($GLOBALS["dnsmasq_bin"])){$GLOBALS["dnsmasq_bin"]=$GLOBALS["CLASS_UNIX"]->find_program("dnsmasq");}
		$targetedfile=$re[1];
		$file="/etc/artica-postfix/croned.1/dnsmasq.". md5($targetedfile).".Permission.denied";
		events("dnsmasq $targetedfile -> Permission denied");
		if(IfFileTime($file,10)){
				events("dnsmasq $targetedfile -> chmod 755");
				if(is_file($GLOBALS["aa-complain"])){
					events("dnsmasq {$GLOBALS["aa-complain"]}  -> {$GLOBALS["dnsmasq_bin"]}");
					shell_exec("{$GLOBALS["aa-complain"]} {$GLOBALS["dnsmasq_bin"]}");
				}
				email_events("dnsmasq: Permission denied on $targetedfile","dnmasq claims:\n$buffer\nArtica will change permission of this file to 0755 in order to fix this issue and put it into aa-complain mode",'system');
				shell_exec("/bin/chmod 755 \"$targetedfile\"");
				shell_exec(trim("{$GLOBALS["nohup"]} /etc/init.d/dnsmasq restart >/dev/null 2>&1 &"));
				@unlink($file);	
				WriteFileCache($file);
			}
			return;
		}
		
	
	if(preg_match("#pam_ldap: error trying to bind \(Invalid credentials\)#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/pam_ldap.Invalid.credentials";
		if(IfFileTime($file,10)){
				email_events("pam_ldap: system unable to contact the LDAP server","system claims:\n$buffer\nArtica will reconfigure nss-ldap system\nSome systems request rebooting\nto be sure, reboot your server",'system');
				shell_exec(trim("{$GLOBALS["nohup"]} /usr/share/artica-postfix/bin/artica-install --nsswitch >/dev/null 2>&1 &"));
				@unlink($file);	
				WriteFileCache($file);
			}
			return;
		}	



	if(preg_match("#net:\s+failed to bind to server.+?Error: Invalid credentials#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/net.Invalid.credentials";
		if(IfFileTime($file,10)){
				email_events("Samba/net: system unable to contact the LDAP server","Samba/net claims:\n$buffer\nArtica will reconfigure samba system\n",'system');
				shell_exec(trim("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.samba.php --build >/dev/null 2>&1 &"));
				@unlink($file);	
				WriteFileCache($file);
			}
			return;
		}	


	
	if(preg_match("#pdns.+?:\s+\[LdapBackend\] Unable to search LDAP directory: Starting LDAP search: Can't contact LDAP server#",$buffer,$re)){
		events("--> PDNS LDAP FAILED");
		$file="/etc/artica-postfix/croned.1/pdns.Can.t.contact.LDAP.server";
		if(IfFileTime($file,10)){
			email_events("PowerDNS: DNS server is unable to contact the LDAP server","PDNS claims:\n$buffer\nArtica will restart PowerDNS service",'system');
			shell_exec(trim("{$GLOBALS["nohup"]} /etc/init.d/pdns restart >/dev/null 2>&1 &"));
			@unlink($file);	
			WriteFileCache($file);
		}
		return;
	}	
	
	
	if(preg_match("#pdns_recursor\[.*?Failed to update \. records, RCODE=([0-9]+)#",$buffer,$re)){
		events("--> Failed to update \. records, RCODE={$re[1]}");
		$file="/etc/artica-postfix/croned.1/pdns.failed.to.update.record.{$re[1]}";
		if(IfFileTime($file,2)){
			shell_exec(trim("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.initslapd.php --pdns-recursor >/dev/null 2>&1"));
			shell_exec(trim("{$GLOBALS["nohup"]} /etc/init.d/pdns-recursor restart >/dev/null 2>&1 &"));
		}
		
		return;
	}
	
	
	if(preg_match("#pdns(?:\[\d{1,5}\])?: Not authoritative for '.*',.*sending servfail to\s+(.+?)\s+\(recursion was desired\)#",$buffer,$re)){
		events("--> PDNS Hack {$re[2]}");
		if($GLOBALS["PDNS_HACK"]==1){
			$GLOBALS["PDNS_HACK_DB"][$re[2]]=$GLOBALS["PDNS_HACK_DB"][$re[2]]+1;
			if($GLOBALS["PDNS_HACK_DB"][$re[2]]>$GLOBALS["PDNS_HACK_MAX"]){
				events("--> PDNS Hack {$re[2]} will be banned");
				@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/pdns-hack-queue/".time(), $re[2]);
				unset($GLOBALS["PDNS_HACK_DB"][$re[2]]);
			}
		}
		return;
	}	
	
	
	if(preg_match("#auditd\[.+?Unable to set audit pid, exiting#", $buffer)){
		$file="/etc/artica-postfix/croned.1/Unable.to.set.audit.pid";
		if(IfFileTime($file,10)){
			email_events("Auditd: cannot start","auditd claims:\n$buffer\nIt seems that Auditd cannot start, if you run this computer on an OpenVZ VPS server, be sure that your Administrator has enabled audtid capability
			Take a look here http://bugzilla.openvz.org/show_bug.cgi?id=1157
			\nthis notification is not a good information.\nthe Auditd feature is now disabled\n",'system');
			@unlink($file);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableAuditd", "0");
			shell_exec(trim("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop auditd >/dev/null 2>&1 &"));
			WriteFileCache($file);
		return;	
		}	
	}
	
	
	if(preg_match("#snort\[[0-9]+\]:\s+\[.+?\]\s+(.+?)\s+\[Classification: (.+?)\]\s+\[Priority:\s+([0-9]+)\]:\/s+\{(.+?)\}\s+(.+?):([0-9]+)\s+->\s+(.+?):([0-9]+)#",$buffer,$re)){
		$md5=md5($buffer);
		$filename="{$GLOBALS["ARTICALOGDIR"]}/snort-queue/".time().".$md5.snort";
		@file_put_contents($filename,serialize($re));
		return;
	}
	
	
	if(preg_match("#snort\[.+?:\s+Can.+?t acquire.+?cooked-mode frame doesn.+?t have room for sll header#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/snort.cant.bind";
		if(IfFileTime($file,10)){
			email_events("SNORT: Fatal error: could not acquire the network","snort claims:\n$buffer\nIt seems that snort is unable to hook your Interface Card, perhaps your server running in a Xen environnement or any virtual system\nthis notification is not a good information.\nYou should remove the IDS feature from Artica or remove SNORT package\nYour system cannot support IDS system.\nsee http://seclists.org/snort/2011/q2/52\nhttp://support.citrix.com/article/CTX116204",'system');
			@unlink($file);
			WriteFileCache($file);
		return;	
		}	
	}
	

	if(preg_match("#.+?roundcube-(.+?): FAILED login for (.+?) from ([0-9\.]+)#",$buffer,$re)){
		Roundcubehack($re[1],$re[2],$re[3]);
		return;
	}
	
	if(preg_match("#net:\s+failed to bind to server ldap.+?localhost#",$buffer)){
		$GLOBALS["EnableOpenLDAP"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
		if($GLOBALS["EnableOpenLDAP"]==0){return;}
		events("--> exec.samba.php --fix-etc-hosts");
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.samba.php --fix-etc-hosts >/dev/null 2>&1 &");
		$file="/etc/artica-postfix/croned.1/net-ldap-bind";
		if(IfFileTime($file,5)){
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
			WriteFileCache($file);
			return;	
		}	
	}
	
	
	if(preg_match("#(winbindd|smbd)\[.+?failed to bind to server.+?Invalid credentials#",$buffer)){
		events("SAMBA: Invalid credentials");
		
		$file="/etc/artica-postfix/croned.1/samba-ldap-credentials";
		if(IfFileTime($file,5)){
			if(is_file("/var/lib/samba/winbindd_idmap.tdb")){@unlink("/var/lib/samba/winbindd_idmap.tdb");}
			if(is_file("/var/lib/samba/group_mapping.ldb")){@unlink("/var/lib/samba/group_mapping.ldb");}
			email_events("Samba: could not connect to ldap Invalid credentials","samba claims:\n$buffer\nArtica will try to reconfigure password and restart Samba",'system');
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.samba.php --fix-etc-hosts >/dev/null 2>&1 &");
			@unlink($file);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart samba >/dev/null 2>&1 &");
			WriteFileCache($file);
			}	
		return;
	}
	
// -------------------------------------------------------------------------------------------------------------------------------------------------	
	if(preg_match("#failed due to\s+\[winbind client not authorized to use winbindd_pam_auth_crap\.\s+Ensure permissions on.+?are set correctly#",$buffer)){
		events("SQUID: winbindd_pam_auth_crap --> exec.kerbauth.php --winbindfix");
		$file="/etc/artica-postfix/croned.1/winbindd_pam_auth_crap";
		if(IfFileTime($file,5)){
			squid_admin_mysql(0, "NTLM: client not authorized to use winbindd_pam_auth_crap", $buffer,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.kerbauth.php --winbindfix");
			@unlink($file);
			WriteFileCache($file);
		}
		return;
	}
	
// -------------------------------------------------------------------------------------------------------------------------------------------------	
	if(preg_match("#smbd\[.+?:\s+smbd_open_once_socket: open_socket_in: Address already in use#",$buffer)){
		events("SMBD: smbd_open_once_socket: open_socket_in: Address already in use");
		$file="/etc/artica-postfix/croned.1/smbd_open_once_socket.open_socket_in.Address.already.in.use";
		if(IfFileTime($file,10)){
			email_events("Samba: try to bind ipv6 and ipv4, fixed","samba claims:\n$buffer
			Artica will do \"sysctl net.ipv6.bindv6only=1\" to fix this issue (see https://bugzilla.redhat.com/show_bug.cgi?id=726936)",'system');
			shell_exec("{$GLOBALS["sysctl"]} net.ipv6.bindv6only=1");
			@unlink($file);
			WriteFileCache($file);
		}
		
		return;
	}
// -------------------------------------------------------------------------------------------------------------------------------------------------	
	if(preg_match("#winbindd.+?Could not receive trustdoms#",$buffer)){
		events("WINBIND: Could not receive trustdoms");
		
		$file="/etc/artica-postfix/croned.1/Could.not.receive.trustdoms";
		if(IfFileTime($file,5)){
			events("WINBIND: Could not receive trustdoms -> restart Winbind");
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("restart winbindd", basename(__FILE__));}
			email_events("Samba: Could not receive trustdoms","samba claims:\n$buffer\nArtica will try to restart winbindd service",'system');
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/winbind restart >/dev/null 2>&1 &");
			@unlink($file);
			}
			WriteFileCache($file);
		return;	
	}			
		
	
	
	if(preg_match("#winbindd\[.+?ADS uninitialized: No logon servers#",$buffer)){
		$file="/etc/artica-postfix/croned.1/winbindd-No-logon-servers";
		events("WINBINDD: ADS uninitialized: No logon servers");
		if($GLOBALS["EnableKerbAuth"]==1){
			if(IfFileTime($file,3)){
				squid_admin_mysql(0, "NTLM: No logon servers", $buffer,__FILE__,__LINE__);
				events("WINBINDD: EnableKerbAuth:: exec.kerbauth.php --build (do nothing new patch 2012-05-04)");
				
				//shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.kerbauth.php --build &");
				WriteFileCache($file);
			}
			return;
		}

	}		
		
		

	
	if(preg_match("#lessfs\[.+?send_backlog : failed to connect to the slave#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/lessfs.1";
		if(IfFileTime($file,5)){
			email_events("lessFS: Replication deduplication to connect to the slave ","lessFS claims:\n$buffer\nPlease check communications with the slave",'system');
			WriteFileCache($file);
		return;	
		}	
	}
	
	if(preg_match("#lessfs\[.+?send_backlog : invalid message size#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/lessfs.2";
		if(IfFileTime($file,5)){
			email_events("lessFS: Replication deduplication failed to replicate ","lessFS claims:\n$buffer\nPlease check communications with the slave",'system');
			WriteFileCache($file);
		return;	
		}	
	}
	
	if(preg_match("#lessfs\[.+?replication_worker : replication is disabled, disconnect#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/lessfs.2";
		if(IfFileTime($file,5)){
			email_events("lessFS: Replication deduplication failed: Slave is disabled ","lessFS claims:\n$buffer\nPlease check communications with the slave",'system');
			WriteFileCache($file);
		return;	
		}	
	}	
	
	if(preg_match("#lessfs\[.+?Could not recover database : (.+?)#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/lessfs.3";
		if(IfFileTime($file,5)){
			email_events("lessFS: database {$re[1]} corrupted !!","lessFS claims:\n$buffer\nArtica will try to repair it...",'system');
			shell_exec("lessfsck -o -f -t -c /etc/lessfs.cfg &");
		}
		
	}
	
	if(preg_match("#automount\[.+?mount.+?unknown filesystem type.+?ext4#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/automount.unknown.filesystem.type.ext4";
		if(IfFileTime($file,15)){
			email_events("automount: Failed to mount EXT4 !","automount claims:\n$buffer\nYou should upgrade your system in order to obtain the last kernel that enables ext4",'system');
			WriteFileCache($file);
		}
		return;
	}	
	
	if(preg_match("#automount\[.+?mount.+?failed to mount\s+(.+?)\s+on\s+(.+)$#",$buffer,$re)){
		$mount_dir=$re[1];
		$mount_dest=$re[2];
		$md5=md5("$mount_dir$mount_dest");
		$file="/etc/artica-postfix/croned.1/automount.$md5";
		if(IfFileTime($file,15)){
			email_events("automount: Failed to mount $mount_dir ","automount claims:\n$buffer\nCheck your connexions settings on automount section",'system');
			WriteFileCache($file);
		}
		return;
	}	
	
	

	if(preg_match("#modprobe: WARNING: Error inserting\s+(.+?)\s+\(.+?\):\s+No such device#",$buffer,$re)){
		email_events("kernel: missing {$re[1]} module","modprobe claims:\n$buffer\nTry to find the right package that store {$re[2]} file",'VPN');
		return;
	}
	
	
	if(preg_match("#pptp_callmgr.+?Could not open control connection to\s+([0-9\.]+)#",$buffer,$re)){
		vpn_msql_events("VPN connexion failed to {$re[1]}, unable to create connection tunnel",$buffer,"{$re[1]}");
		email_events("VPN connexion failed to {$re[1]}, unable to create connection tunnel ","$buffer",'VPN');
		return;
	}
	
	
	if(preg_match("#pppd\[.+?Can.+?t open options file.+?ppp\/peers\/(.+?):\s+No such file or directory#",$buffer,$re)){
		email_events("VPN connexion failed for {$re[1]} connection,No such file","pptp clients claims $buffer\artica will try to rebuild connections","VPN");
		vpn_msql_events("VPN (PPTPD) failed for {$re[1]} connection,No such file",$buffer,"{vpn_server}");
		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.pptpd.php --clients &");
		return;
	}
	
	 if(preg_match("#pppd\[.+?peer refused to authenticate: terminating link#",$buffer,$re)){
		vpn_msql_events("VPN (PPTPD) authentification failed from remote host",$buffer,"{vpn_server}");
		return;
	}
	
	 if(preg_match("#pppd\[.+?peer refused to authenticate#",$buffer,$re)){
		vpn_msql_events("VPN (PPTPD) failed peer refused to authenticate",$buffer,"{vpn_server}");
		return;
	}	
	
	if(preg_match("#pppd\[.+?MS-CHAP authentication failed: E=691 Authentication failure#",$buffer,$re)){
		vpn_msql_events("VPN (CLIENT) failed server refused to authenticate (Authentication failure)",$buffer,"{vpn_server}");
		return;
	}	
	
	if(preg_match("#pppd\[.+?MPPE required but not available#",$buffer,$re)){
		vpn_msql_events("VPN (PPTPD) authentification failed MPPE required",$buffer,"{vpn_server}");
		return;
	}
	
	
	
	if(preg_match("#pptpd\[.+?CTRL: Client\s+(.+?)\s+control connection finished#",$buffer,$re)){
		vpn_msql_events("VPN (PPTPD) connection closed for {$re[1]}",$buffer,"{vpn_server}");
		return;
	}
	
	if(preg_match("#pppd\[.+?pptpd-logwtmp\.so ip-up ppp[0-9]+\s+(.+?)\s+([0-9\.]+)#",$buffer,$re)){
		vpn_msql_events("VPN (PPTPD) connection open for {$re[1]} ({$re[2]})","$buffer",'{vpn_server}');
		return;
	}
	
	if(preg_match("#slapd\[(.+?)\]:.+?OpenLDAP: slapd\s+([0-9\.]+)#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/openldap-started";
		events("OpenLDAP service version {$re[2]} successfully started PID {$re[1]}","$buffer",'system');
		return;
	}
	


if(preg_match("#monit\[.+?Sendmail error:\s+(.+)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/monit-sendmail-failed-". md5($re[1]);
	if(IfFileTime($file,10)){
		events("MONIT -> SENDMAIL FAILED");
		//email_events("Monit is unable to send notifications","Monit claim \"$buffer\"\ntry to analyze why postfix send this error:\n{$re[1]}",'system');
		WriteFileCache($file);
		return;	
	}
}


if(strpos($buffer,"pam_ldap: ldap_simple_bind Can't contact LDAP server")>0){
	if($GLOBALS["EnableOpenLDAP"]==0){return;}
	$file="/etc/artica-postfix/croned.1/ldap-failed";
	if(IfFileTime($file,10)){
		events("pam_ldap -> LDAP FAILED");
		email_events("LDAP server is unavailable","System claim \"$buffer\" artica will try to restart LDAP server ",'system');
		WriteFileCache($file);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
		return;	
	}	
}

if(preg_match("#net:\s+failed to bind to server.+?Error:\s+Can.?t\s+contact LDAP server#",$buffer)){
	$file="/etc/artica-postfix/croned.1/ldap-failed";
	if($GLOBALS["EnableOpenLDAP"]==0){return;}
	if(IfFileTime($file,10)){
		events("NET -> LDAP FAILED");
		email_events("LDAP server is unavailable","System claim \"$buffer\" artica will try to restart LDAP server ",'system');
		WriteFileCache($file);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
		return;	
	}	
}

if(preg_match("#winbindd\[.+?failed to bind to server\s+(.+?)\s+with dn.+?Error: Can.+?contact LDAP server#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/ldap-failed";
	if(IfFileTime($file,10)){
		if($GLOBALS["EnableOpenLDAP"]==0){return;}
		events("winbindd -> LDAP FAILED");
		email_events("LDAP server is unavailable","Samba claim \"$buffer\" artica will try to restart LDAP server ",'system');
		WriteFileCache($file);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
		return;	
	}
}


if(preg_match("#smbd\[.+?User\s+(.+?)with invalid SID\s+(.+?)\s+in passdb#",$buffer,$re)){
	events("SAMBA Invalid SID for {$re[1]}");
	$md5=md5("{$re[1]}{$re[2]}");
	$file="/etc/artica-postfix/croned.1/samba.invalid.sid.$md5";
	if(IfFileTime($file)){
		$unix=new unix();
		$localsid=$unix->GET_LOCAL_SID();
		$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.synchronize.php";
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		email_events("Samba invalid SID for {$re[1]}","Samba claim \"$buffer\"\nUser:{$re[1]} with sid {$re[2]} has this server has the SID $localsid\nArtica will re-sync accounts",'system');
		WriteFileCache($file);
	}
	return true;
}

if(preg_match("#smbd\[.+?sid\s+(.+?)\s+does not belong to our domain#",$buffer,$re)){
	events("SAMBA Invalid global SID for {$re[1]}");
	$md5=md5("{$re[1]}");
	$file="/etc/artica-postfix/croned.1/samba.invalid.sid.$md5";
	if(IfFileTime($file)){
		$unix=new unix();
		$localsid=$unix->GET_LOCAL_SID();
		email_events("Samba global invalid SID for {$re[1]}","Samba claim \"$buffer\"\n{$re[1]} has this server has the real SID $localsid\nTry to rebuild the configuration trough artica web Interface",'system');
		WriteFileCache($file);
	}
	return true;
}


if(preg_match("#NetBIOS name\s+(.+?)\s+is too long. Truncating to (.+?)#",$buffer,$re)){
	events("SAMBA NetBIOS name {$re[1]} is too long");
	$file="/etc/artica-postfix/croned.1/NetBIOSNameTooLong";
	if(IfFileTime($file)){
		email_events("Samba NetBIOS name {$re[1]} is too long","Samba claim \"$buffer\" \nYou should change your server hostname",'system');
		WriteFileCache($file);
	}
	return true;
}	
	
	



if(preg_match('#net:\s+WARNING:\s+Ignoring invalid value.+?Bad Pasword#',$buffer,$re)){
	events("SAMBA unknown parameter Bad Pasword");
	$file="/etc/artica-postfix/croned.1/SambaBadPasword";
	if(IfFileTime($file)){
		email_events("Samba unknown parameter \"Bad Pasword\"","Samba claim \"$buffer\" Artica will reconfigure samba",'system');
		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --reconfigure &");
		WriteFileCache($file);
	}
	return true;
}

if(preg_match('#smbd\[.+Ignoring unknown parameter\s+"hide_unwriteable_files"#',$buffer,$re)){
	events("SAMBA unknown parameter hide_unwriteable_files");
	$file="/etc/artica-postfix/croned.1/hide_unwriteable_files";
	if(IfFileTime($file)){
		email_events("Samba unknown parameter hide_unwriteable_files","Samba claim \"$buffer\" Artica will correct the configuration file",'system');
		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --fix-HideUnwriteableFiles &");
		WriteFileCache($file);
	}
	return true;
}

if(preg_match('#load_usershare_shares: directory\s+(.+?)\s+is not owned by root or does not have the sticky bit#',$buffer,$re)){
	events("SAMBA load_usershare_shares {$re[1]}");
	$file="/etc/artica-postfix/croned.1/load_usershare_shares";
	if(IfFileTime($file)){
		email_events("Samba load_usershare_shares permissions issues","Samba claim \"$buffer\" Artica will correct the filesystem directory",'system');
		shell_exec("chmod 1775 $re[1]/ &");
		shell_exec("chmod chmod +t $re[1]/ &");
		WriteFileCache($file);
	}
	return true;	
}

if(preg_match("#amavis\[.+?:\s+\(.+?\)TROUBLE\s+in child_init_hook:#",$buffer,$re)){
	events("AMAVIS TROUBLE in child_init_hook");
	$file="/etc/artica-postfix/croned.1/amavis.".md5("AMAVIS:TROUBLE in child_init_hook");
	if(IfFileTime($file)){
		email_events("Amavis child error","Amavis claim \"$buffer\" the amavis daemon will be restarted",'postfix');
		shell_exec('/etc/init.d/amavis restart &');
		WriteFileCache($file);
	}
	return true;
}

if(preg_match("#amavis\[.+?:\s+\(.+?\)_DIE:\s+Suicide in child_init_hook#",$buffer,$re)){
	events("AMAVIS TROUBLE in child_init_hook");
	$file="/etc/artica-postfix/croned.1/amavis.".md5("AMAVIS:TROUBLE in child_init_hook");
	if(IfFileTime($file)){
		email_events("Amavis child error","Amavis claim \"$buffer\" the amavis daemon will be restarted",'postfix');
		shell_exec('/etc/init.d/amavis restart &');
		WriteFileCache($file);
	}
	return true;
}


if(preg_match("#smbd_audit:\s+(.+?)\|(.+?)\|(.+?)\|(.+?)\|(.+?)\|(.+?)\|(.+?)\|(.+?)$#",$buffer,$re)){
	events("{$re[5]}/{$re[8]} in xapian queue");
	WriteXapian("{$re[5]}/{$re[8]}"); 
	return true;
}




if(preg_match("#dansguardian.+?:\s+Error connecting to proxy#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/squid.tostart.error";
		if(IfFileTime($file,2)){
			events("Squid not available...! Artica will start squid");
			email_events("Proxy error","DansGuardian claim \"$buffer\", Artica will start squid ",'system');
			$GLOBALS["CLASS_UNIX"]->RECONFIGURE_SQUID();
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix start dansguardian');
			WriteFileCache($file);
			return;
		}else{
			events("Proxy error, but take action after 10mn");
			return;
		}		
}


if(preg_match("#zarafa-server.+?INNODB engine is disabled#",$buffer)){
	$file="/etc/artica-postfix/croned.1/zarafa.INNODB.engine";
	if(IfFileTime($file,2)){
			events("Zarafa innodb errr");
			WriteFileCache($file);
			return;
		}else{
			events("Zarafa innodb err, but take action after 10mn");
			return;
		}			
}


if(preg_match("#zarafa-spooler\[.+?Unable to open admin session.*?Error ([0-9a-zA-Z]+)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/zarafa.Unable.to.open.admin.session";
	events("Unable to open admin session `{$re[1]}` line:".__LINE__);
	
	if(IfFileTime($file,3)){
		$restartZarafa=false;
		if(preg_match("#0x80040115#",$buffer)){
			events("{$re[1]}: Restart required...");
			$restartZarafa=true;$restartaction="\nServer will be restarted...\n";
		}else{
			events("{$re[1]}: Restart NOT required...");
		}
		email_events("zarafa Spooler service error connecting to zarafa server ({$re[1]})","Zarafa claim \"$buffer\"$restartaction ",'system');
		WriteFileCache($file);
		if($restartZarafa){
			events("\"{$GLOBALS["nohup"]} /etc/init.d/zarafa-server restart >/dev/null 2>&1 &\" line:".__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/zarafa-server restart >/dev/null 2>&1 &");
		}
	}
	return;
}


if(preg_match("#(.+?)\[.+?segfault at.+?error.+?in.+?\[#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/segfault.{$re[1]}";
	if(IfFileTime($file,10)){
		events("{$re[1]}: segfault");
		email_events("{$re[1]}: segfault","Kernel claim \"$buffer\" ",'system');
		WriteFileCache($file);
		return;	
	}
}

if(preg_match("#kernel:.+?Out of memory:\s+kill\s+process\s+#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kernel.Out.of.memory";
	if(!is_numeric($GLOBALS["NOOUTOFMEMORYREBOOT"])){$GLOBALS["NOOUTOFMEMORYREBOOT"]=0;}
	if(IfFileTime($file,1)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			events("Out of memory -> REBOOT !!!");
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			email_events("Out of memory: reboot action performed Uptime:$uptime","Kernel claim \"$buffer\" the server will be rebooted",'system');
			WriteFileCache($file);
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted",__FILE__,__LINE__);}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;	
		}else{
			email_events("Out of memory: your system hang !","Kernel claim \"$buffer\" I suggest rebooting the system",'system');
			WriteFileCache($file);
		}
	}
}


if(preg_match("#kernel:\s+\[.+?Out of memory\s+\(oom_kill_allocating_task#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kernel.Out.of.memory";
	if(!is_numeric($GLOBALS["NOOUTOFMEMORYREBOOT"])){$GLOBALS["NOOUTOFMEMORYREBOOT"]=0;}
	if(IfFileTime($file,1)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			events("Out of memory -> REBOOT !!!");
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			email_events("Out of memory: reboot action performed uptime:$uptime","Kernel claim \"$buffer\" the server will be rebooted",'system');
			WriteFileCache($file);
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted",__FILE__,__LINE__);}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;	
		}else{
			email_events("Out of memory: your system hang !","Kernel claim \"$buffer\" I suggest rebooting the system",'system');
			WriteFileCache($file);
		}
	}
return;}

if(preg_match("#Modules linked in: xt_ndpi#",$buffer,$re)){
	email_events("BUG: Modules linked in: xt_ndpi (layer 7 protocol detection )!!!","Kernel claim \"$buffer\" you need to remove any Firewall rules based on applications",'system');
	if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "BUG: Modules linked in: xt_ndpi (layer 7 protocol detection )!!!", "Kernel claim \"$buffer\" you need to remove any Firewall rules based on applications",__FILE__,__LINE__);}
	return;	
}

if(preg_match("#BUG: scheduling while atomic: swapper#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kernel.scheduling.while.atomic.swapper";
	if(!is_numeric($GLOBALS["NOOUTOFMEMORYREBOOT"])){$GLOBALS["NOOUTOFMEMORYREBOOT"]=0;}
	if(IfFileTime($file,1)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			events("BUG: scheduling while atomic: swapper -> REBOOT !!!");
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			email_events("BUG: scheduling while atomic: reboot action performed uptime:$uptime","Kernel claim \"$buffer\" the server will be rebooted",'system');
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "BUG: scheduling while atomic: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted",__FILE__,__LINE__);}
			WriteFileCache($file);
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;
		}
	}
	return;
}


if(preg_match("#kernel:.+?ata.+?status:\s+{\s+DRDY#",$buffer,$re)){
	if($GLOBALS["NODRYREBOOT"]==1){
		events("Hard Disk problem: -> reboot banned");
		return ;
	}
	$file="/etc/artica-postfix/croned.1/kernel.DRDY";
	if(IfFileTime($file,5)){
		
		events("DRDY -> REBOOT !!!");
		exec("/bin/dmesg 2>&1",$results);
		$array["buffer"]=$buffer;
		$array["dmsg"]=$results;
		@mkdir("/etc/artica-postfix/reboot",644,true);
		@file_put_contents("/etc/artica-postfix/reboot/".time(),serialize($array));
		email_events("Hard Disk issue: reboot action performed","Kernel claim \"$buffer\" the server will be rebooted\n".@implode("\n",$results),'system');
		if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted",__FILE__,__LINE__);}
		UcarpDown();
		shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
		return;
	}
}




if(preg_match("#winbindd\[.+?resolve_name: unknown name switch type lmhost#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/winbindd.lmhost.failed";
	if(IfFileTime($file,10)){
		events("winbindd -> lmhost failed");
		WriteFileCache($file);
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.samba.php --fix-lmhost");
		return;	
	}	
}

if(preg_match("#nmbd\[.+?become_logon_server_success: Samba is now a logon server for workgroup (.+?)\s+on subnet\s+([A-Z0-9\._-]+)#",$buffer,$re)){
	email_events("Samba (file sharing) started domain {$re[1]}/{$re[2]}","Samba notice: \"$buffer\"",'system');
	return;	
}




if(preg_match("#zarafa-server.+?Unable to connect to database.+?MySQL server on.+?([0-9\.]+)#",$buffer)){
	$file="/etc/artica-postfix/croned.1/zarafa.MYSQL.CONNECT";
	if(IfFileTime($file,2)){
			events("Zarafa Mysql Error errr");
			email_events("MailBox server unable connect to database","Zarafa server  claim \"$buffer\" ",'mailbox');
			WriteFileCache($file);
			return;
		}else{
			events("MailBox server unable connect to database but take action after 10mn");
			return;
		}			
}

if(preg_match("#winbindd:\s+Exceeding\s+[0-9]+\s+client\s+connections.+?no idle connection found#",$buffer)){
	$file="/etc/artica-postfix/croned.1/Winbindd.connect.error";
	if(IfFileTime($file,2)){
			events("winbindd Error connections");
			email_events("Winbindd exceeding connections","Samba server  claim \"$buffer\" \nArtica will restart samba",'system');
			shell_exec('/etc/init.d/artica-postfix restart samba &');
			WriteFileCache($file);
			return;
		}else{
			events("Winbindd exceeding connections take action after 10mn");
			return;
		}			
}




// -------------------------------------------------------------------- MONIT


if(preg_match("#'(.+?)'\s+total mem amount of\s+([0-9]+).+?matches resource limit#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/mem.globalmem.monit";
	if(IfFileTime($file,10)){
			   $processname=$re[1];if(preg_match("#mysqlmulti([0-9]+)#", $processname,$ri)){$tt=unserialize(@file_get_contents("/etc/artica-postfix/mysql_multi_names.cache"));$instancenem=$tt[$ri[1]];$re[1]="Mysql Instance {$ri[1]} ($instancenem)";}
				events("{$re[1]} limit memory exceed");
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ".dirname(__FILE__)."/exec.watchdog.php --mem >/dev/null 2>&1 &");
				system_admin_events("{$re[1]}: memory limit","Monitor claim \"$buffer\"\n".@implode("\n", $psarr),__FUNCTION__,__FILE__,__LINE__,"watchdog");
				WriteFileCache($file);
				return;
			}else{
				events("{$re[1]} limit memory exceed, but take action after 10mn");
				return;
			}			
	}
	if(preg_match("#monit\[.+?'(.+?)'\s+trying to restart#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/restart.{$re[1]}.monit";
		if(IfFileTime($file,5)){
					events("{$re[1]} was restarted");
					$processname=$re[1];if(preg_match("#mysqlmulti([0-9]+)#", $processname,$ri)){$tt=unserialize(@file_get_contents("/etc/artica-postfix/mysql_multi_names.cache"));$instancenem=$tt[$ri[1]];$re[1]="Mysql Instance {$ri[1]} ($instancenem)";}
					WriteFileCache($file);
					return;
				}else{
					events("{$re[1]}: stopped, try to restart, but take action after 10mn");
					return;
				}			
		}
	
	if(preg_match("#monit\[.+?mem usage of\s+([0-9\.]+)%\s+matches resource limit#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/mem.usage.monit";
		if(IfFileTime($file,15)){
					events("{$re[1]}% limit memory exceed");
					shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ".dirname(__FILE__)."/exec.watchdog.php --mem >/dev/null 2>&1 &");
					system_admin_events("{$re[1]}% memory limit\nMonitor claim \"$buffer\"\n",__FUNCTION__,__FILE__,__LINE__,"watchdog");
					WriteFileCache($file);
					return;
				}else{
					events("{$re[1]}% limit memory exceed, but take action after 15mn");
					return;
				}			
		}
	
	
if(preg_match("#monit\[.+?'(.+?)'\s+process is not running#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/restart.{$re[1]}.monit";
	if(IfFileTime($file,5)){
				events("{$re[1]} was stopped");
				$processname=$re[1];if(preg_match("#mysqlmulti([0-9]+)#", $processname,$ri)){
					$tt=unserialize(@file_get_contents("/etc/artica-postfix/mysql_multi_names.cache"));
					$instancenem=$tt[$ri[1]];$re[1]="Mysql Instance {$ri[1]} ($instancenem)";
				}
				
				WriteFileCache($file);
				return;
			}else{
				events("{$re[1]}: stopped, but take action after 10mn");
				return;
			}			
	}
	
	
if(preg_match("#pdns\[.+?:\s+binding UDP socket to.+?Address already in use#",$buffer,$re)){
$file="/etc/artica-postfix/croned.1/restart.pdns.bind.error";
	if(IfFileTime($file,5)){
				events("PowerDNS: Unable to bind UDP socket");
				email_events("PowerDNS: Unable to bind UDP socket","Artica will restart PowerDNS",'system');
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart pdns');
				WriteFileCache($file);
				return;
			}else{
				events("PowerDNS: Unable to bind UDP socket: but take action after 10mn");
				return;
			}			
	}	
	
	
//pdns_recursor[5011]: Failed to update . records, RCODE=2
if(preg_match("#pdns_recursor\[.+?:\s+Failed to update \. records, RCODE=2#",$buffer,$re)){
$file="/etc/artica-postfix/croned.1/restart.pdns.RCODE2.error";
	if(IfFileTime($file,5)){
				events("PowerDNS: Unable to query Public DNS");
				//email_events("PowerDNS: Unable to query Public DNS","PowerDNS claim: $buffer,It seems that your Public DNS are not available or network is down",'system');
				WriteFileCache($file);
				return;
			}else{
				events("PowerDNS: Unable to query Public DNS: but take action after 10mn");
				return;
			}			
	}		
	

	
if(preg_match("#cpu system usage of ([0-9\.]+)% matches#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cpu.system.monit";
	if(IfFileTime($file,15)){
				events("cpu exceed");
				system_admin_events("CPU warning {$re[1]}%\nMonitor claim \"$buffer\"",__FUNCTION__,__FILE__,__LINE__,"watchdog");
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ".dirname(__FILE__)."/exec.watchdog.php --cpu >/dev/null 2>&1 &");
				WriteFileCache($file);
				return;
			}else{
				events("cpu exceed, but take action after 10mn");
				return;
			}			
	}
	
if(preg_match("#monit.+?loadavg.+?of\s+([0-9\.]+)\s+matches resource limit#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/load.system.monit";
		if(IfFileTime($file,15)){
					events("Load exceed");
					system_admin_events("Load warning {$re[1]}\nMonitor claim \"$buffer\"",__FUNCTION__,__FILE__,__LINE__,"watchdog");
					shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} ".dirname(__FILE__)."/exec.watchdog.php --loadavg >/dev/null 2>&1 &");
					WriteFileCache($file);
					return;
				}else{
					events("Load exceed, but take action after 15mn");
					return;
				}			
	}	
	
	

if(preg_match("#monit.+?'(.+)'\s+start:#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/monit.start.{$re[1]}";
	if(IfFileTime($file,5)){
				events("{$re[1]} start");
				WriteFileCache($file);
				return;
			}else{
				events("{$re[1]} start, but take action after 10mn");
				return;
			}			
	}		

if(preg_match("#monit\[.+?:\s+'(.+?)'\s+process is running with pid\s+([0-9]+)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/monit.run.{$re[1]}";
	if(IfFileTime($file,5)){
				events("{$re[1]} running");
				WriteFileCache($file);
				return;
			}else{
				events("{$re[1]} running, but take action after 10mn");
				return;
			}			
	}		
	
if(preg_match("#nmbd.+?:\s+Cannot sync browser lists#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/samba.CannotSyncBrowserLists.error";
		if(IfFileTime($file)){
			events("Samba cannot sync browser list, remove /var/lib/samba/wins.dat");
			@unlink("/var/lib/samba/wins.dat");
			WriteFileCache($file);
		}else{
			events("Samba error:$buffer, but take action after 10mn");
			return;
		}		
}

// ********************************************** CLAMAV **********************************************

if(preg_match("#freshclam.+?:\s+Database updated \(([0-9]+)\s+signatures\) from .+?#",$buffer,$re)){
			email_events("ClamAV Database Updated {$re[1]} signatures","$buffer",'update');
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} " .basename(__FILE__)."/exec.clamavsig.php >/dev/null 2>&1 &");
			return;
		}
		
if(preg_match("#freshclam\[.+?:\s+Database updated\s+\(#",$buffer,$re)){shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} " .basename(__FILE__)."/exec.clamavsig.php >/dev/null 2>&1 &");}
		
		
		
if(preg_match("#freshclam.+?Can.+?t\s+connect to port\s+([0-9]+)\s+of\s+host\s+(.+?)\s+#",$buffer,$re)){
	$host=$re[2].":".$re[1];
	$file="/etc/artica-postfix/croned.1/freshclam.error.".md5($host);
	if(IfFileTime($file)){
			email_events("Unable to update ClamAV Databases from $host","freshclam claim $buffer\nCheck is this server hav access to Internet\nCheck your proxy configuration",'update');
			WriteFileCache($file);
			return;
		}else{
			events("KAV4PROXY error:$buffer, but take action after 10mn");
			return;
		}		
	}		
		


	

if(preg_match("#KASERROR.+?NOLOGID.+?Can.+?find user mailflt3#",$buffer)){
	$file="/etc/artica-postfix/croned.1/KASERROR.NOLOGID.mailflt3";
		if(IfFileTime($file)){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --mailflt3');
			WriteFileCache($file);
			return;
		}else{
			events("KASERROR error:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#lmtp.+?status=deferred.+?lmtp\]:.+?(No such file or directory|Too many levels of symbolic links)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.lmtp.failed";
		if(IfFileTime($file)){
			email_events("cyrus-imapd socket error","Postfix claim \"$buffer\", Artica will restart cyrus",'system');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-checkconfig');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.main.cf.php --imap-sockets");
			cyrus_socket_error($buffer,$re[1]."lmtp");
			WriteFileCache($file);
			return;
		}else{
			events("CYRUS error:$buffer, but take action after 10mn");
			return;
		}		
}


if(preg_match("#rsyncd\[.+?:\s+recv.+?\[(.+?)\].+?([0-9]+)$#",$buffer,$re)){
	$file=md5($buffer);
	@mkdir('{$GLOBALS["ARTICALOGDIR"]}/rsync',null,true);
	$f["IP"]=$re[1];
	$f["DATE"]=date('Y-m-d H:00:00');
	$f["SIZE"]=$re[2];
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rsync/$file",serialize($f));
}

if(preg_match("#kavmilter.+?Can.+?t load keys: No active key#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmilter.key.failed";
		if(IfFileTime($file)){
			email_events("Kaspersky Antivirus Mail license error","KavMilter claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("Kaspersky Antivirus Mail license error:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#kavmd.+?Can.+?t load keys:.+?#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmd.key.failed";
		if(IfFileTime($file)){
			email_events("Kaspersky Antivirus Mail license error","Kaspersky Antivirus Mail claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("Kaspersky Antivirus Mail license error:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#kavmd.+?ERROR Engine problem#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmd.engine.failed";
		if(IfFileTime($file)){
			email_events("Kaspersky Antivirus Mail Engine error","Kaspersky Antivirus Mail claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("Kaspersky Antivirus Mail Engine error:$buffer, but take action after 10mn");
			return;
		}		
}



if(preg_match("#kavmilter.+?WARNING.+?Your AV signatures are older than#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmilter.upd.failed";
		if(IfFileTime($file)){
			email_events("Kaspersky Antivirus Mail AV signatures are older","KavMilter claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("Kaspersky Antivirus update license error:$buffer, but take action after 10mn");
			return;
		}		
}
if(preg_match("#dansguardian.+?Error compiling regexp#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/dansguardian.compiling.regexp";
		if(IfFileTime($file)){
			email_events("Dansguardian failed to start","Dansguardian claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("Dansguardian failed to start:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#kavmilter.+?Invalid value specified for SendmailPath#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmilter.SendmailPath.Invalid";
		if(IfFileTime($file)){
			events("Check SendmailPath for kavmilter");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.kavmilter.php --SendmailPath");
			WriteFileCache($file);
			return;
		}else{
			events("Check SendmailPath for kavmilter:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#KAVMilter Error.+?Group.+?Default.+?has error#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/kavmilter.Default.error";
		if(IfFileTime($file)){
			events("Check Group default for kavmilter");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.kavmilter.php --default-group");
			WriteFileCache($file);
			return;
		}else{
			events("Check Group default for kavmilter:$buffer, but take action after 10mn");
			return;
		}		
}

if(preg_match("#kavmilter.+?Message INFECTED from (.+?)\(remote:\[(.+?)\).+?with\s+(.+?)$#",$buffer,$re)){
	events("KAVMILTER INFECTION <{$re[1]}> {$re[2]}");
	infected_queue("kavmilter",trim($re[1]),trim($re[2]),trim($re[3]));
	return;
}


if(preg_match("#pdns\[.+?\[LdapBackend.+?Ldap connection to server failed#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/pdns.ldap.error";
	if(IfFileTime($file)){
			events("PDNS LDAP FAILED");
			email_events("PowerDNS ldap connection failed","PowerDNS claim \"$buffer\"",'system');
			WriteFileCache($file);
			return;
		}else{
			events("PDNS FAILED:$buffer, but take action after 10mn");
			return;
		}		
}





if(preg_match("#master.+?cannot find executable for service.+?sieve#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.sieve.error";
		if(IfFileTime($file)){
			events("Check sieve path");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");
			WriteFileCache($file);
			return;
		}else{
			events("Check sieve path error :$buffer, but take action after 10mn");
			return;
		}		
}


if(preg_match("#smbd\[.+?write_data: write failure in writing to client 0.0.0.0. Error Connection reset by peer#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/samba.Error.Connection.reset.by.peer.error";
		if(IfFileTime($file)){
			events("Check sieve Error Connection reset by peer");
			$text[]="Your MS Windows computers should not have access to the server cause network generic errors";
			$text[]="- Check these parameters:"; 
			$text[]="- Check if Apparmor or SeLinux are disabled on the server.";
			$text[]="- Check your hard drives by this command-line: hdparm -tT /dev/sda(0-9)";
			$text[]="- Check that 137|138|139|445 ports is open from workstation to this server";
			$text[]="- Check network switch or hub connection between this server and your workstations.";
			$text[]="- Try to add this registry key [HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\Disk]\n\t\"TimeOutValue\"=dword:0000003c";
			email_events("Samba network error","Samba claim \"$buffer\"\n" .implode("\n",$text) ,'system');
			WriteFileCache($file);
			return;
		}else{
			events("Check sieve Error Connection reset by peer :$buffer, but take action after 10mn");
			return;
		}		
}


$mem=round(((memory_get_usage()/1024)/1000),2);	
events_not_filtered("Not Filtered:\"$buffer\" (line ".__LINE__.") memory: {$mem}MB");		
}




function IfFileTime($file,$min=10){
	$time=file_time_min($file);
	events("$file = {$time}Mn Max:$min");
	if($time>$min){return WriteFileCache($file);return true;}
	return false;
}

function WriteFileCache($file){
	@unlink($file);
	@file_put_contents($file,"#");	
}


function Roundcubehack($instance,$account,$ip){
	
	if($ip=="127.0.0.1"){return;}
	
	$enable=$GLOBALS["CLASS_SOCKET"]->GET_INFO("RoundCubeHackEnabled");
	if($enable==null){$enable=1;}
	if($enable==0){return;}
	
	
	
	$maxCount=$GLOBALS["CLASS_SOCKET"]->GET_INFO("RoundCubeHackMaxAttempts");
	
	$maxCountTimeMin=$GLOBALS["CLASS_SOCKET"]->GET_INFO("RoundCubeHackMaxAttemptsTimeMin");
	$attempts=unserialize(base64_decode($GLOBALS["CLASS_SOCKET"]->GET_INFO("RoundCubeHackAttempts")));
	
	if($maxCount==null){$maxCount=6;}
	if($maxCountTimeMin==null){$maxCountTimeMin=10;}
	
	$attempts_first_time=$attempts[$instance][$ip]["TIME"];
	$attempts_count=$attempts[$instance][$ip]["COUNT"];
	if($attempts_first_time==null){$attempts_first_time=time();}
	$minutes=calc_time_min($attempts_first_time);
	
	if($attempts_count==null){$attempts_count=0;}
	$attempts_count++;
	
	events("ROUNDCUBE HACK:: instance \"$instance\" $ip ($account) $attempts_count attempts/$maxCount in {$minutes}mn [ arraof: attempts[$instance][$ip] ]");
	$attempts[$instance][$ip]["TIME"]=$attempts_first_time;
	$attempts[$instance][$ip]["COUNT"]=$attempts_count;
	
	if($attempts_count>=$maxCount){
		if($minutes<=$maxCountTimeMin){
			events("ROUNDCUBE HACK:: block $ip");
			$unix=new unix();
			
			$GLOBALS["CLASS_UNIX"]->send_email_events("RoundCube Hack: $ip from instance $instance is banned","Acount:<b>$account</b>");
			$RoundCubeHackConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKET"]->GET_INFO("RoundCubeHackConfig")));
			if(!isset($RoundCubeHackConfig[$instance][$ip])){
				$RoundCubeHackConfig[$instance][$ip]=true;
			}
			unset($attempts[$instance][$ip]);
			$GLOBALS["CLASS_SOCKET"]->SaveConfigFile(base64_encode(serialize($RoundCubeHackConfig)),"RoundCubeHackConfig");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.roundcube.php --hacks");
		}else{
			unset($attempts[$instance][$ip]);
		}
	}
	
	$GLOBALS["CLASS_SOCKET"]->SaveConfigFile(base64_encode(serialize($attempts)),"RoundCubeHackAttempts");
	
}




function events($text){
		$filename=basename(__FILE__);
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
		}
		
function WriteXapian($path){
	$md=md5($path);
	$f="{$GLOBALS["ARTICALOGDIR"]}/xapian/$md.queue";
	if(is_file($f)){return null;}
	@file_put_contents($f,$path);
	
}
function email_events($subject,$text,$context){
	events("DETECTED: $subject: $text -> $context");
	$GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}
	
	function vpn_msql_events($subject,$text,$IPPARAM){
	$subject=addslashes($subject);
	$text=addslashes($text);
	$time=time();
	$sql="INSERT INTO vpn_events (`stime`,`subject`,`text`,`IPPARAM`)
	VALUES('$time','$subject','$text','$IPPARAM')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){events($q->mysql_error ." $sql",__FUNCTION__,__FILE__,__LINE__);}
}

function dhcpd($buffer){
	
	if(strpos($buffer,"dhcpd: ")==0){return false;}
	if(strpos($buffer,"the README")>0){return true;}
	if(strpos($buffer,"requesting help")>0){return true;}
	if(strpos($buffer,"isc.org")>0){return true;}
	if(strpos($buffer,"All rights reserved")>0){return true;}
	if(strpos($buffer,"circumstances send")>0){return true;}
	if(strpos($buffer,"bug reports")>0){return true;}
	if(strpos($buffer,"Copyright")>0){return true;}
	if(strpos($buffer,"dhcpd: DHCPREQUEST")>0){return true;}
	if(strpos($buffer,"dhcpd: DHCPOFFER")>0){return true;}
	if(strpos($buffer,"dhcpd: execute_statement")>0){return true;}
	if(strpos($buffer,"dhcpd: execute: bad arg")>0){return true;}
	
	if(preg_match("#dhcpd:\s+(.+)#",$buffer,$re)){
		$day=date('Y-m-d H:i:s');
		$text=addslashes($re[1]);
		$sqlcontent="('$day','$text')";
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/dhcpd/".md5($sqlcontent),$sqlcontent);
		return true;
	}
	
	
	if(preg_match("#dhcpd.*?DHCPDECLINE\s+of\s+(.+?)\s+from\s+(.+?)\s+.*?abandoned#", $re)){
		if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
		$md5=md5("{$re[1]}{$re[2]}");
		$file="/etc/artica-postfix/croned.1/dhcpd-$md5-abandoned";
		if(IfFileTime($file,10)){
			email_events("DHCPD: cannot assign ip address {$re[1]} for {$re[2]} computer!","DHCPD claim\n$buffer\nPlease check your configuration",'dhcpd');
		}
		return true;
	}
	
	
	
	
	
	if(preg_match("#dhcpd: Multiple interfaces match the same shared network:\s+(.+)$#",$buffer,$re)){
		if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
		email_events("DHCPD:{$re[1]}: check your configuration interfaces match the same shared network","DHCPD claim\n$buffer\nPlease check your configuration",'dhcpd');
		return true;
	}
	
	if(preg_match("#dhcpd:\s+No subnet declaration for\s+(.+?)\s+\((.+?)\)#",$buffer,$re)){
		if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
		email_events("DHCPD: bad configuration:: No subnet declaration for {$re[1]}/{$re[2]}",
		"DHCPD claim\n$buffer\nPlease check your configuration.\nYou must add a subnet that handle {$re[2]}",'dhcpd');
		return true;
	}

	
	if(preg_match("#dhcpd:\s+receive_packet failed on (.+?): Network is down#",$buffer,$re)){
			if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
			$file="/etc/artica-postfix/croned.1/dhcpd-{$re[1]}-failed";
			events("DHCPD: {$re[1]} is down");
			if(IfFileTime($file,3)){
				email_events("DHCPD: {$re[1]} is down",
				"DHCPD claim\n$buffer\nArtica will restart DHCP service",'dhcpd');
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart dhcp");
			}
		return true;
	}	

	if(preg_match("#dhcpd:\s+Not configured to listen on any interfaces#",$buffer)){
		if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
		$file="/etc/artica-postfix/croned.1/dhcpfd-interfaces-failed";
		if(IfFileTime($file,10)){
			events("DHCPD -> FAILED");
			email_events("DHCPD corrupted settings","DHCPD claim \"$buffer\"\nArtica will try to repair the configuration",'system');
			shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.dhcpd.php &");
			WriteFileCache($file);
			return true;	
		}	
	}

if(preg_match("#dhcpd: DHCPREQUEST for (.+?)\s+from\s+(.+?)\s+\((.+?)\)\s+via#",$buffer,$re)){
	if($GLOBALS["CLASS_SOCKET"]->GET_INFO('EnableDHCPServer')==0){return true;}
	$md=md5("{$re[1]}{$re[2]}");
	if(!isset($GLOBALS["DHCPREQUEST"]["$md"])){
		$GLOBALS["DHCPREQUEST"]["$md"]=true;
		events("DHCPD: IP:{$re[1]} MAC:({$re[2]}) computer name={$re[3]}-> exec.dhcpd-leases.php");
		if(preg_match("#([0-9\.]+)\s+\(#", $re[1],$ri)){$re[1]=$ri[1];}
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.dhcpd-leases.php");
	}
	return true;
}	

return false;
	
}

function squid_parser($buffer){
	if(strpos($buffer,"Initializing IP Cache...")>0){return;}
	if(strpos($buffer,"DNS Socket created")>0){return;}
	if(strpos($buffer,"Target number of buckets")>0){return;}
	
	
	if(preg_match("#squid.*?httpAccept: FD [0-9]+: accept failure:.*?22.*?Invalid argument#",$buffer)){
		$file="/etc/artica-postfix/croned.1/squid.httpAccept.FR.22";
		if(IfFileTime($file,5)){
			UcarpDown();
			squid_admin_mysql(0,"File descriptor error 22 [action=emergency]",$buffer,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			WriteFileCache($file);
		}else{events("File descriptor error 22 (but timed out)");}
		return;
		
	}
	
	
	if(preg_match("#squid.*?swap directories, Check cache.*?squid -z#",$buffer)){
		$file="/etc/artica-postfix/croned.1/squid-caches-failed-1";
		if(IfFileTime($file,5)){
			UcarpDown();
			squid_admin_mysql(2,"Proxy Must reconfigure squid caches [action=build caches]",$buffer,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.php --caches >/dev/null 2>&1 &");
			WriteFileCache($file);
		}else{events("Squid Must reconfigure squid caches (but timed out)");}
		return;			
	}
	
	
	if(preg_match("#squid.*?Write failure -- check your disk space#",$buffer)){
		$file="/etc/artica-postfix/croned.1/check-disk-space";
		if(IfFileTime($file,5)){
			squid_admin_mysql(0, "Write failure check your disk space [action=reload]",$buffer,__FILE__,__LINE__);
			$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
			shell_exec("{$GLOBALS["nohup"]} $cmd >/dev/null 2>&1 &");
			WriteFileCache($file);
		}
		return;
		
	}
	
	
	if(preg_match("#squid\[.+?:\s+(.+?):\s+\(2\)\s+No such file or directory#i",$buffer,$re)){
		events("--> Repair squid dir '{$re[1]}'...");
		@mkdir($re[1],0755,true);@chmod($re[1],0755);@chown($re[1], "squid");@chgrp($re[1], "squid");
		if(strlen($GLOBALS["SQUIDBIN"])>3){shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["SQUIDBIN"]} -z >/dev/null 2>&1 &");}
		return;		
		
	}
	
	if(preg_match("#ipcCreate: fork:.+?Cannot allocate memory#", $buffer)){
		$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
		email_events("Memory full: System will be rebooted after $uptime uptime","SQUID claim \"$buffer\" the operating system will be rebooted ($reboot).",'proxy');
		if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: System will be rebooted after running after $uptime", "System claim \"$buffer\" the operating system will be rebooted",__FILE__,__LINE__);}
		UcarpDown();
		shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
	}
	

if(preg_match("#httpAccept:\s+FD\s+[0-9]+:\s+accept failure:\s+\([0-9]+\)\s+Invalid argument#",$buffer)){
	$file="/etc/artica-postfix/croned.1/squid_accept_failure";	
	if(IfFileTime($file)){
			UcarpDown();
			squid_admin_mysql(0, "File descriptor error ! [action=emergency]",$buffer,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			WriteFileCache($file);
			return;
		}else{
			return;
		}	
}
	
if(preg_match("#squid.*?Failed to verify one of the swap directories, Check cache.log.+?squid -z#",$buffer)){
		events("Squid Must reconfigure squid caches");
		$file="/etc/artica-postfix/croned.1/squid-caches-failed";
		if(IfFileTime($file,5)){
			squid_admin_mysql(1, "failed to load (error swap directories)","Proxy claim \"$buffer\"\nArtica will try to repair caches",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.php --caches >/dev/null 2>&1 &");
			WriteFileCache($file);
		}else{events("Squid Must reconfigure squid caches (but timed out)");}
		return;	
	}	
	

	if(preg_match("#squid\[([0-9]+)\]:\s+Starting Squid Cache version\s+([0-9\.]+)\s+#",$buffer)){
			squid_admin_mysql(2, "Proxy start PID {$re[1]} version {$re[2]}","Proxy claim \"$buffer\"\n",__FILE__,__LINE__);
			email_events("Squid started pid {$re[1]} version {$re[2]}","Squid has been started \"$buffer\"\n",'proxy');
			return;
	}

	if(preg_match("#Your cache is running out of filedescriptors#",$buffer)){
			UcarpDown();
			squid_admin_mysql(2, "Proxy service is running out of file descriptors [action=reload]","Proxy claim \"$buffer\"\n",__FILE__,__LINE__);
			shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squid.php --reload-squid &");
			return;
		}	
	
	
	
if(preg_match("#squid\[.+?comm_old_accept:\s+FD\s+[0-9]+:.+?Invalid argument#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/comm_old_accept.FD15";
	if(IfFileTime($file)){
			squid_admin_mysql(0, "File descriptor error ! [action=emergency]",$buffer,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			WriteFileCache($file);
			return;
		}else{
			events("comm_old_accept FD15 SQUID");
			return;
		}	
}
if(preg_match("#httpAccept: FD [0-9]+: accept failure: \([0-9]+\) Invalid argument#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/comm_old_accept.FD15";
	if(IfFileTime($file)){
			squid_admin_mysql(0, "File descriptor error ! [action=emergency]",$buffer,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			WriteFileCache($file);
			return;
		}else{
			events("FD 83: accept failure SQUID");
			return;
		}	
}

if(preg_match("#create_local_private_krb5_conf_for_domain.*?smb_mkstemp failed.*?for file\s+(.+?)\s+#", $buffer,$re)){
	$file="/etc/artica-postfix/croned.1/create_local_private_krb5_conf_for_domain.smb_mkstemp";
	if(IfFileTime($file)){
			$directory=basename($re[1]);
			squid_admin_mysql(1,"Squid File System error (permission denied) [action=chmod]","SQUID claim \"$buffer\" The $directory directory access will be granted",'proxy');
			shell_exec("{$GLOBALS["CHMOD_BIN"]} 1777 $directory");
			
			WriteFileCache($file);
			return;
		}else{
			events("create_local_private_krb5_conf_for_domain : smb_mkstemp dir:$directory (timeout)");
			return;
		}		
}

if(preg_match("#NetfilterInterception.+?failed on FD.+?No such file or directory#",$buffer)){
			events("Squid NetfilterInterception failed");	
			$file="/etc/artica-postfix/croned.1/NetfilterInterception.FD15";
			if(IfFileTime($file)){
				email_events("SQUID: NetfilterInterception failed","Squid claim \"$buffer\"\nArtica will reload squid",'proxy');
				shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squid.php --reload-squid &");
				WriteFileCache($file);
			}
			return;
		}	


if(preg_match("#squid.+?:\s+essential ICAP service is down after an options fetch failure:\s+icap:\/\/:1344\/av\/respmod#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/squid.icap1.error";
		if(IfFileTime($file)){
			squid_admin_mysql(1,"Kasperky for Proxy is DOWN [action=start-kav]","Proxy claim \"$buffer\"",'proxy');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix start kav4proxy');
			WriteFileCache($file);
		}else{
			events("KAV4PROXY error:$buffer, but take action after 10mn");
			
		}
	return;			
}

if(preg_match("#squid\[([0-9]+)\]:\s+storeLateRelease:\s+#",$buffer,$re)){
	email_events("Proxy: Squid was successfull loaded PID {$re[1]}","$buffer",'proxy');
	return;
}

if(preg_match("#squid\[.+?Squid Parent: child process ([0-9]+) exited with status ([0-9]+)#",$buffer,$re)){
	email_events("Proxy: Squid child process PID {$re[1]} was been terminated (code {$re[2]})","Squid claim \"$buffer\"",'proxy');
	return;
}

if(preg_match("#squid\[.+?:idnsSendQuery.+?Invalid argument#",$buffer,$re)){
	email_events("Proxy: DNS configuration error","Squid claim \"$buffer\"\nIt seems that you have a DNS misconfiguration under Proxy settings",'proxy');
	return;
}

if(preg_match("#squid\[.+?:\s+(.+?):\s+\(13\)\s+Permission denied#",$buffer,$re)){
	$file_error=trim($re[1]);
	$file="/etc/artica-postfix/croned.1/squid.". md5($file_error).".error";
	events("SQUID:: Permissions error on $file_error");
		if(IfFileTime($file)){
			email_events("Squid File $file_error error","SQUID claim \"$buffer\" permissions of $file_error will be changed to squid:squid ",'proxy');
			$dirfile=dirname($file_error);
			if(is_dir($dirfile)){
				$cmd="/bin/chown squid:squid $dirfile";
				events("$cmd");
				shell_exec("$cmd &");
				
				$cmd="/bin/chown -R squid:squid $dirfile";
				events("$cmd");
				shell_exec("$cmd &");
			}
			WriteFileCache($file);	
		}
		
		return;
}


events_not_filtered("SQUID:: Not Filtered:\"$buffer\"");
		
}


function nss_parser($buffer){
	if(preg_match('#nss_wins.+?failed to bind to server\s+(.+?)\s+with\s+dn="(.+?)"\s+Error:\s+Invalid credentials#',$buffer,$re)){	
		$file="/etc/artica-postfix/croned.1/nss_parser.Invalidcredentials.error";
		events("nss_wins:: Invalid credentials");
		if(IfFileTime($file)){
			email_events("System error NSS cannot bind to {$re[1]}: Invalid credentials","NSS Wins claim \"$buffer\"",'system');
			}
			WriteFileCache($file);	
			return;	
		}	
		
	
	events_not_filtered("nss_wins:: Not Filtered:\"$buffer\"");
	
}

function haproxy_parser($buffer){
	
	if(preg_match('#haproxy\[.*?:\s+(.*?):[0-9]+\s+.*?\]\s+(.+?)\/(.+?)\s+([0-9]+)\/([0-9]+)\/([0-9]+)\/([0-9]+)\/([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+([a-zA-Z\-]+)\s+.*?"(.*?)"#', $buffer,$re)){
		$ARRAY["TIME"]=time();
		$ARRAY["SOURCE"]=$re[1];
		$ARRAY["SERVICE"]=$re[2];
		$ARRAY["BACKEND"]=$re[3];
		$Tq=intval($re[4]);
		$Tw=intval($re[5]);
		$Tc=intval($re[6]);
		$Tr=intval($re[7]);
		$Tt=intval($re[8]);
		$Td = $Tt - ($Tq + $Tw + $Tc + $Tr);
		$ARRAY["TD"]=$Td;
		$ARRAY["HTTP_CODE"]=$re[9];
		$ARRAY["BYTES"]=$re[10];
		$ARRAY["STATUSLB"]=$re[13];
		$uri=$re[14];
		if(preg_match("#.*?\s+(.+?)\s+#", $uri,$ri)){$uri=$ri[1];}
		$ARRAY["URI"]=$uri;
		$line=serialize($ARRAY);
		$md5=md5($line);
		$GLOBALS["haproxy_parser_size"]=$GLOBALS["haproxy_parser_size"]+$ARRAY["BYTES"];
		$GLOBALS["haproxy_parser_COUNT"]++;
		if($GLOBALS["haproxy_parser_COUNT"]>500){events_not_filtered("haproxy:: 500 connections:\"{$GLOBALS["haproxy_parser_size"]} bytes transfered\"");$GLOBALS["haproxy_parser_COUNT"]=0;}
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/haproxy-rtm/$md5.log",$line);
		return true;
	}
	
}



function amavis_sa_update($buffer){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &";
	events("$cmd amavis_sa_update()");
	$file="/etc/artica-postfix/pids/".__FUNCTION__.".error.time";
	if(file_time_min($file)<15){events("-> detected $buffer, need to wait 15mn");return null;}	
	@unlink($file);
	@file_put_contents($file,"#");	
	shell_exec(trim($cmd));
	events("$cmd");
	return;			
	
}


function events_not_filtered($text){
		$common="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
		$size=@filesize($common);
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$h = @fopen($common, 'a');
		$sline="[$pid] $text";
		$line="$date [$pid] $text\n";
		@fwrite($h,$line);
		
		@fclose($h);	
	
}
function eventsAuth($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
		
function haarp_remove(){
	if($GLOBALS["HAARP_FATAL"]<5){
		squid_admin_mysql(0,"Haarp Fatal: {$GLOBALS["HAARP_FATAL"]}/5 waiting 5 times..",
		"after 5 times, the service will be disabled\n"
				,__FILE__,__LINE__);
		return false;}
	
		$file="/etc/artica-postfix/croned.1/haarp.haarp_remove";
	if($GLOBALS["HAARP_FATAL"]<8){
		if(IfFileTime($file,5)){return;}
	}
	
	squid_admin_mysql(0,"Haarp Fatal: Too many errors on this service, disable it",
	"Too many errors as been detected on StreamCache system.\nArtica will disable this service in order to continue production\n"
	,__FILE__,__LINE__);
	$GLOBALS["CLASS_SOCKET"]->SET_INFO("EnableHaarp", "0");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
	$GLOBALS["HAARP_FATAL"]=0;
	WriteFileCache($file);
}

function Kernel_parser($buffer){
	//   KERNEL //
	
	if(preg_match("#kernel:\s+\[([0-9]+)\..*?\]\s+.*?invoked oom-killer#",$buffer,$re)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			exec("{$GLOBALS["PS_BIN"]} aux 2>&1",$resultsa);
			email_events("Memory full: System will be rebooted after running after $uptime",
			"System claim \"$buffer\" the operating system will be rebooted.\n".@implode("\n", $resultsa),'system');
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full: [".__LINE__."] System will be rebooted after running after $uptime", 
			"System claim \"$buffer\" the operating system will be rebooted\n".@implode("\n", $resultsa),__FILE__,__LINE__);}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;
		}
			
	}
	
	if(preg_match("#kernel.*?Out of memory: kill process#",$buffer,$re)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			exec("{$GLOBALS["PS_BIN"]} aux 2>&1",$resultsa);
			email_events("Memory full: System will be rebooted after running after $uptime",
			"System claim \"$buffer\" the operating system will be rebooted.\n".@implode("\n", $resultsa),'system');
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Memory full:[".__LINE__."] System will be rebooted after running after $uptime", 
			"System claim \"$buffer\" the operating system will be rebooted\n".@implode("\n", $resultsa),__FILE__,__LINE__);}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;
		}
	}
	
	if(preg_match("#kernel.*?invoked oom-killer#",$buffer,$re)){
		if($GLOBALS["NOOUTOFMEMORYREBOOT"]<>1){
			$uptime=$GLOBALS["CLASS_UNIX"]->uptime();
			exec("{$GLOBALS["PS_BIN"]} aux 2>&1",$resultsa);
			email_events("Memory full: System will be rebooted after running after $uptime",
			"System claim \"$buffer\" the operating system will be rebooted.\n".@implode("\n", $resultsa),'system');
				if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, 
				"Memory full: [".__LINE__."] System will be rebooted after running after $uptime", 
				"System claim \"$buffer\" the operating system will be rebooted\n".@implode("\n", $resultsa),__FILE__,__LINE__);
				}
			UcarpDown();
			shell_exec("{$GLOBALS["SHUTDOWN_BIN"]} -rF now");
			return;
		}
	}	
}



function UcarpDown(){
	if(!$GLOBALS["CORP_LICENSE"]){return;}
	if($GLOBALS["UCARP_MASTER"]==null){return;}
	$downfile="/usr/share/ucarp/vip-{$GLOBALS["UCARP_MASTER"]}-down.sh";
	if(!is_file($downfile)){return;}
	ToSyslog("Shutdown VIP {$GLOBALS["UCARP_MASTER"]}");
	shell_exec("{$GLOBALS["nohup"]} $downfile >/dev/null 2>&1 &");
}

function FIREHOL_PARSER($buffer){
	if(!preg_match("#kernel:.*?FIREHOL:(.+)#",$buffer,$re)){return true;}
	$lines=explode(" ",$re[1]);
	
	
	while (list ($num, $line) = each ($lines)){
		$line=trim($line);
		if(!preg_match("#(.+?)=(.*)#", $line,$re)){continue;}
			$re[1]=trim($re[1]);
			$re[2]=$re[2];
			events_not_filtered("'{$re[1]}'->'{$re[2]}'");
			
	}	
}


function ToSyslog($text){
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog("syslog-watch", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}
?>