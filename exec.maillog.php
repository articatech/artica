<?php
$GLOBALS["DEBUG_MEM"]=true;
$GLOBALS["DEBUG_MEM_FILE"]="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug";
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
events("Memory: START AT ".round(((memory_get_usage()/1024)/1000),2) ." line:".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.ini.inc line:".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.os.system.inc line:".__LINE__);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes frame.class.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/framework/class.unix.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.unix.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/framework/class.settings.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.settings.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.sockets.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.postfix.maillog.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.postfix.maillog.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.amavis.maillog.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.amavis.maillog.inc line: ".__LINE__);
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after includes class.amavis.maillog.inc line: ".__LINE__);
//Jun 29 14:53:54 ns214639 postfix-outbond-167-33.ultranavy.info/smtpd[23815]: warning: SASL authentication failure: cannot connect to saslauthd server: No such file or directory 
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS");
$set=new settings_inc();
$GLOBALS["CLASS_SETTINGS"]=$set;
events("Memory: FINISH ".round(((memory_get_usage()/1024)/1000),2) ." after includes line: ".__LINE__);

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=='--amavis-port'){postfix_is_amavis_port($argv[2]);die();}

$unix=new unix();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid)){writelogs("Already running pid $pid, Aborting");die();}
$pid=getmypid();
events("running $pid ");
file_put_contents($pidfile,$pid);
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after unix() declaration line: ".__LINE__);
$sock=new sockets();
$GLOBALS["CLASS_SOCKETS"]=$sock;
$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after sockets() declaration line: ".__LINE__);
$users=new settings_inc();
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after usersMenus() declaration line: ".__LINE__);
$_GET["server"]=$users->hostname;
$GLOBALS["MYHOSTNAME"]=$users->hostname;
$_GET["IMAP_HACK"]=array();
$GLOBALS["ZARAFA_INSTALLED"]=$users->ZARAFA_INSTALLED;
$GLOBALS["AMAVIS_INSTALLED"]=$users->AMAVIS_INSTALLED;
$GLOBALS["CLASS_POSTFIX_SQL"]=new mysql_postfix_builder();
$GLOBALS["POP_HACK"]=array();
$GLOBALS["SMTP_HACK"]=array();
$GLOBALS["PHP5_BIN"]=LOCATE_PHP5_BIN2();
$GLOBALS["LN_BIN"]=$unix->find_program("ln");
$GLOBALS["POSTFIX_BIN"]=$unix->find_program("postfix");
$GLOBALS["iptables"]=$unix->find_program("iptables");
$GLOBALS["EnablePostfixAutoBlock"]=trim($sock->GET_INFO("EnablePostfixAutoBlock"));
if(!is_numeric($GLOBALS["EnablePostfixAutoBlock"])){$GLOBALS["EnablePostfixAutoBlock"]=1;}
$GLOBALS["PostfixNotifyMessagesRestrictions"]=$sock->GET_INFO("PostfixNotifyMessagesRestrictions");
$GLOBALS["GlobalIptablesEnabled"]=$GlobalIptablesEnabled;
$GLOBALS["PopHackEnabled"]=$sock->GET_INFO("PopHackEnabled");
$GLOBALS["PopHackCount"]=$sock->GET_INFO("PopHackCount");
$GLOBALS["DisableMailBoxesHack"]=$sock->GET_INFO("DisableMailBoxesHack");
$GLOBALS["EnableArticaSMTPStatistics"]=$sock->GET_INFO("EnableArticaSMTPStatistics");
$GLOBALS["ActAsASyslogSMTPClient"]=$sock->GET_INFO("ActAsASyslogSMTPClient");
$GLOBALS["EnableStopPostfix"]=$sock->GET_INFO("EnableStopPostfix");
$GLOBALS["EnableAmavisDaemon"]=$sock->GET_INFO("EnableAmavisDaemon");
if(!is_numeric($GLOBALS["EnableStopPostfix"])){$GLOBALS["EnableStopPostfix"]=0;}
if(!is_numeric($GLOBALS["EnableArticaSMTPStatistics"])){$GLOBALS["EnableArticaSMTPStatistics"]=1;}
if(!is_numeric($GLOBALS["ActAsASyslogSMTPClient"])){$GLOBALS["ActAsASyslogSMTPClient"]=0;}
if(!is_numeric($GLOBALS["EnableAmavisDaemon"])){$GLOBALS["EnableAmavisDaemon"]=0;}
if(!is_numeric($GLOBALS["DisableMailBoxesHack"])){$GLOBALS["DisableMailBoxesHack"]=0;}
if($GLOBALS["PopHackEnabled"]==null){$GLOBALS["PopHackEnabled"]=1;}
if($GLOBALS["PopHackCount"]==null){$GLOBALS["PopHackCount"]=10;}
$GLOBALS["MYPATH"]=dirname(__FILE__);
$GLOBALS["SIEVEC_PATH"]=$unix->LOCATE_SIEVEC();
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]=15;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]=5;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]=5;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]=10;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]=2;
$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]=10;
smtp_hack_reconfigure();
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["postfix_bin_path"]=$unix->find_program("postfix");
$GLOBALS["postconf_bin_path"]=$unix->find_program("postconf");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["GROUPADD"]=$unix->find_program("groupadd");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["fuser"]=$unix->find_program("fuser");
$GLOBALS["kill"]=$unix->find_program("kill");
$GLOBALS["NOHUP_PATH"]=$unix->find_program("nohup");
$GLOBALS["NETSTAT_PATH"]=$unix->find_program("netstat");
$GLOBALS["TOUCH_PATH"]=$unix->find_program("touch");
$GLOBALS["POSTMAP_PATH"]=$unix->find_program("postmap");
$GLOBALS["maillog_tools"]=new maillog_tools();
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections",0755,true);
@mkdir("/etc/artica-postfix/cron.1",0755,true);
@mkdir("/etc/artica-postfix/cron.2",0755,true);
$users=null;
$sock=null;
$unix=null;
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." after all declarations ".__LINE__);
@mkdir("/home/artica/postfix/realtime-events");
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
ini_set("error_log", "{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug");
$postgres=new postgres_sql();
$postgres->SMTP_TABLES();


$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer=fgets($pipe, 4096);
	Parseline($buffer);
	$buffer=null;
}

fclose($pipe);
events("Shutdown...");
die();
function Parseline($buffer){
if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){return;}	
$buffer=trim($buffer);
if($buffer==null){return null;}
if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}

if(preg_match("#qmgr\[.*?:\s+([0-9A-Z]+): removed#", $buffer,$re)){$GLOBALS["maillog_tools"]->event_messageid_removed($re[1]);return;}
if(is_file("{$GLOBALS["ARTICALOGDIR"]}/smtp-hack-reconfigure")){smtp_hack_reconfigure();}
if(strpos($buffer,'config file "/etc/mail/greylist.conf"')>0){return;} 
if(strpos($buffer,"]: fatal: Usage:postmulti")>0){return;} 
if(strpos($buffer,"warning: non-SMTP command from unknown")>0){return;} 
if(strpos($buffer,"Do you need to run 'sa-update'?")>0){amavis_sa_update($buffer);return;}
if(strpos($buffer,"Passed CLEAN {AcceptedOpenRelay}")>0){return;} 
if(strpos($buffer,"Passed BAD-HEADER-1 {RelayedInternal}")>0){return;} 
if(strpos($buffer,"Valid PID file (")>0){return;} 
if(strpos($buffer,"]: SA dbg:")>0){return;} 
if(strpos($buffer,") SA dbg:")>0){return;} 
if(strpos($buffer,"enabling PIX workarounds: disable_esmtp delay_dotcrlf")>0){return;} 
if(strpos($buffer,"]: child: exiting: idle for")>0){return;} 
if(strpos($buffer,"]: master: child")>0){return;} 
if(strpos($buffer,") 2822.From: <")>0){return;} 
if(strpos($buffer,") Connecting to LDAP server")>0){return;} 
if(strpos($buffer,") connect_to_ldap: connected")>0){return;} 
if(strpos($buffer,") connect_to_ldap: bind")>0){return;} 
if(strpos($buffer,") Passed CLEAN, AM.PDP-SOCK [")>0){return;} 
if(strpos($buffer,"mode select: signing")>0){return;} 
if(strpos($buffer,"Starting worker process for POP3 request")>0){return;} 
if(strpos($buffer,": Accepted connection from")>0){return;} 
if(strpos($buffer,"]: Not authorized for command:")>0){return;} 
if(strpos($buffer,"milter-greylist: GeoIP failed to lookup ip")>0){return;} 
if(strpos($buffer,": Number of messages in the queue")>0){return;} 
if(strpos($buffer,") inspect_dsn: is a DSN")>0){return;}
if(strpos($buffer,": decided action=DUNNO NULL")>0){return;} 
if(strpos($buffer,"Mail::SpamAssassin::Plugin::Check")>0){return;} 
if(strpos($buffer,"vnStat daemon")>0){return;} 
if(strpos($buffer,"aliases.db: duplicate entry")>0){return;} 
if(strpos($buffer,"DKIM-Signature\" header added")>0){return;}
if(strpos($buffer,"DKIM verification successful")>0){return;}
if(strpos($buffer,": decided action=PREPEND X-policyd-weight: using cached result;")>0){return;} 
if(strpos($buffer," mode select: verifying")>0){return;} 
if(strpos($buffer,"Message canceled by rule")>0){return;}
if(strpos($buffer,"no signing table match for")>0){return;}
if(strpos($buffer,"Connection closed because of timeout")>0){return;}
//if(strpos($buffer,") SPAM-TAG, <")>0){return;} 
if(strpos($buffer,") mail checking ended: version_server=")>0){return;} 
if(strpos($buffer,") check_header:")>0){return;} 
if(strpos($buffer,") dkim: FAILED Author")>0){return;} 
if(strpos($buffer,") dkim: VALID Sender signature")>0){return;} 
if(strpos($buffer,") collect banned table")>0){return;} 
if(strpos($buffer,") p.path")>0){return;}  
if(strpos($buffer,") ask_av Using (ClamAV-clamd): CONTSCAN")>0){return;} 
if(strpos($buffer,") ClamAV-clamd: Connecting to socket")>0){return;} 
if(strpos($buffer,") ClamAV-clamd: Sending CONTSCAN")>0){return;}  
if(strpos($buffer,") inspect_dsn:")>0){return;} 
if(strpos($buffer,"IO::Socket::INET")>0){return;} 
if(strpos($buffer,") smtp resp to greeting:")>0){return;} 
if(strpos($buffer,") smtp cmd> EHLO")>0){return;}  
if(strpos($buffer,") smtp resp to EHLO:")>0){return;} 
if(strpos($buffer,") smtp resp to RCPT (")>0){return;} 
if(strpos($buffer,"greylist: mi_stop=1")>0){return;} 
if(strpos($buffer,"smfi_main() returned 0")>0){return;} 
if(strpos($buffer,"Final database dump")>0){return;}
if(strpos($buffer,"refreshing the Postfix")>0){return;}
if(strpos($buffer,"class.auth.tail.inc")>0){return;}
if(strpos($buffer,"authenticated, bypassing greylisting")>0){return;}
if(strpos($buffer,"NEW message_id")>0){return;}
if(strpos($buffer,"Passed CLEAN {")>0){return;}
if(strpos($buffer,") Blocked SPAM {")>0){return;}
if(strpos($buffer,") Blocked SPAMMY {")>0){return;}
if(strpos($buffer,"does not resolve to address")>0){return;}
if(strpos($buffer,"skipped, still being delivered")>0){return;}
if(strpos($buffer,"(0,lock|fold_fix)")>0){return;}
if(strpos($buffer,"Insecure dependency in open while running with -T")>0){return;}
// ************************ DKIM DUTSBIN
if(strpos($buffer,"no signing domain match for")>0){return;}
if(strpos($buffer,"no signing subdomain match for")>0){return;}
if(strpos($buffer,"no signing keylist match for")>0){return;}
if(strpos($buffer,": no signature data")>0){return;}
if(strpos($buffer," not internal")>0){return;}
if(strpos($buffer," not authenticated")>0){return;}

// ************************ ZARAFA DUTSBIN
if(strpos($buffer,"]: Still waiting for 1 threads to exit")>0){return;}
if(preg_match("#zarafa-dagent\[.*?Delivered message to#",$buffer)){return;}
if(strpos($buffer,": Disconnecting client.")>0){return;}
if(strpos($buffer,"thread exiting")>0){return;}
if(strpos($buffer,"Started to create store")>0){return;}

//if(strpos($buffer,") p00")>0){return;}  
//if(strpos($buffer,") TIMING [total")>0){return;} 
//if(strpos($buffer,") TIMING-SA total")>0){return;}   
if(strpos($buffer,"mailarchiver[")>0){return;}
if(strpos($buffer,") policy protocol:")>0){return;} 
if(strpos($buffer,"]: policy protocol:")>0){return;} 
if(strpos($buffer,") run_av (ClamAV-clamd)")>0){return;}
if(strpos($buffer,"Net::Server: Process Backgrounded")>0){return;}
if(strpos($buffer,"Net::Server:")>0){return;}
if(strpos($buffer,": No ext program for")>0){return;}
if(strpos($buffer,": SA info: zoom: able to use")>0){return;}
if(strpos($buffer,": warm restart on HUP [")>0){return;}
if(strpos($buffer,": starting. (warm)")>0){return;}
if(strpos($buffer,"user=postfix, EUID:")>0){return;}
if(strpos($buffer,"No \$altermime,")>0){return;}
if(strpos($buffer,"starting. /usr/local/sbin/amavisd")>0){return;}
if(strpos($buffer,"initializing Mail::SpamAssassin")>0){return;}
if(strpos($buffer,"Net::Server: Binding to UNIX socket file")>0){return;}
if(strpos($buffer,"SpamControl: init_pre_chroot on SpamAssassin done")>0){return;}
if(strpos($buffer,"Starting worker for LMTP request")>0){return;}
if(strpos($buffer,"LMTP thread exiting")>0){return;}
if(strpos($buffer,") truncating a message passed to SA at")>0){return;}
if(strpos($buffer,"loaded policy bank")>0){return;}
if(strpos($buffer,"process_request: fileno sock")>0){return;}
if(strpos($buffer,"AM.PDP  /var/amavis/")>0){return;}
if(strpos($buffer,"KASWARNING [NOLOGID]: mfhelo: HELO already set")>0){return;}
if(strpos($buffer,"Passed CLEAN {AcceptedInbound}")>0){return;}
if(strpos($buffer,"Blocked MTA-BLOCKED {TempFailedOutbound}")>0){return;}
if(strpos($buffer,") body hash: ")>0){return;}
//if(strpos($buffer,") spam_scan: score=")>0){return;}
if(strpos($buffer,") Cached virus check expired")>0){return;}
if(strpos($buffer,") blocking contents category is")>0){return;}
if(strpos($buffer,") do_notify_and_quar: ccat=")>0){return;}
if(strpos($buffer,") inspect_dsn: not a bounce")>0){return;}
if(strpos($buffer,") local delivery:")>0){return;} 
if(strpos($buffer,") DSN: NOTIFICATION: ")>0){return;}
if(strpos($buffer,") SEND via PIPE:")>0){return;}
if(strpos($buffer,"Discarding because filter instructed us to")>0){return;}
if(strpos($buffer,") Checking for banned types and")>0){return;}
if(strpos($buffer,"skipping mailbox user")>0){return;}
if(strpos($buffer,"artica-plugin:")>0){return;} 
if(strpos($buffer,"success delivered trough 192.168.1.228:33559")>0){return;}
if(strpos($buffer,"skiplist: checkpointed /var/lib/cyrus/user")>0){return;}
if(strpos($buffer,"starttls: TLSv1 with cipher AES256-SHA (256/256 bits new)")>0){return;}
if(strpos($buffer,"lost connection after CONNECT from unknown")>0){return null;}
if(strpos($buffer,"lost connection after DATA from unknown")>0){return null;}
if(strpos($buffer,"lost connection after RCPT")>0){return null;}
if(strpos($buffer,"created decompress buffer of")>0){return null;}
if(strpos($buffer,"created compress buffer of")>0){return null;}
if(strpos($buffer,"SQUAT returned")>0){return null;}
if(strpos($buffer,": lmtp connection preauth")>0){return null;}
if(strpos($buffer,"indexing mailbox user")>0){return null;}
if(strpos($buffer,"mystore: starting txn")>0){return null;}
if(strpos($buffer,"duplicate_mark:")>0){return null;}
if(strpos($buffer,"mystore: committing txn")>0){return null;}
if(strpos($buffer,"cyrus/tls_prune")>0){return null;}
if(strpos($buffer,"milter-greylist: reloading config file")>0){return null;}
if(strpos($buffer,"milter-greylist: reloaded config file")>0){return null;}
if(strpos($buffer,"skiplist: recovered")>0){return null;}
if(strpos($buffer,"milter-reject NOQUEUE < 451 4.7.1 Greylisting in action, please come back in")>0){return null;}
if(strpos($buffer,"extra modules loaded after daemonizing/chrooting")>0){return null;}
if(strpos($buffer,"exec: /usr/bin/php5")>0){return;}
if(strpos($buffer,"rec_get: type N")>0){return;}
if(strpos($buffer,"Found decoder for ")>0){return;}
if(strpos($buffer,"Internal decoder for ")>0){return;}
if(strpos($buffer,"indexing mailboxes")>0){return;}
if(strpos($buffer,"decided action=DUNNO multirecipient-mail - already accepted by previous query")>0){return;}
if(strpos($buffer,"decided action=PREPEND X-policyd-weight: passed - too many local DNS-errors")>0){return;}
if(strpos($buffer,"DSN: FILTER 554 Spam, spam level")>0){return;}
if(strpos($buffer,"emailrelay: info: no more messages to send")>0){return;}
if(strpos($buffer,"spamd: connection from ip6-localhost")>0){return;}
if(strpos($buffer,"spamd: processing message")>0){return;}
if(strpos($buffer,"spamd: clean message")>0){return;}
if(strpos($buffer,"spamd: result:")>0){return;}
if(strpos($buffer,"prefork: child states: I")>0){return;}
if(strpos($buffer,"autowhitelisted for another")>0){return;}
//if(strpos($buffer,"spamd: identified spam")>0){return;}
if(strpos($buffer,"spamd: handled cleanup of child pid")>0){return;}
if(strpos($buffer,"open_on_specific_fd")>0){return;}
if(strpos($buffer,"rundown_child on")>0){return;}
if(strpos($buffer,"switch_to_my_time")>0){return;}
if(strpos($buffer,"%, total idle")>0){return;}
if(strpos($buffer,"exec.mailarchive.php[")>0){return;}
if(strpos($buffer,"do_notify_and_quarantine: spam level exceeds")>0){return;}
if(strpos($buffer,", DEAR_SOMETHING=")>0){return;}
if(strpos($buffer,", DIGEST_MULTIPLE=")>0){return;}
if(strpos($buffer,", BAD_ENC_HEADER=")>0){return;}
if(strpos($buffer,"dkim: VALID")>0){return;}
if(strpos($buffer,"SA info: pyzor:")>0){return;}
if(strpos($buffer,"DSN: sender is credible")>0){return;}
if(strpos($buffer,"mail_via_pipe")>0){return;}
if(strpos($buffer,") ...continue")>0){return;}
if(strpos($buffer,"Cached spam check expired")>0){return;}
if(strpos($buffer,") cached")>0){return;}
if(strpos($buffer,"extra modules loaded:")>0){return;}
if(strpos($buffer,"from MTA(smtp:[127.0.0.1]:10025): 250 2.0.0 Ok")>0){return;}
if(strpos($buffer,"Use of uninitialized value")>0){return;}
if(strpos($buffer,"DecodeShortURLs")>0){return;}
if(strpos($buffer,"FWD via SMTP: <")>0){return;}
if(strpos($buffer,"DKIM-Signature header added")>0){return;}
if(strpos($buffer,"Passed CLEAN, MYNETS LOCAL")>0){return;}
if(strpos($buffer,") Passed CLEAN, [")>0){return;}
if(strpos($buffer,") Passed BAD-HEADER, [")>0){return;}
if(strpos($buffer,") Checking: ")>0){return;}
if(strpos($buffer,") WARN: MIME::Parser error: unexpected end of header")>0){return;}
if(strpos($buffer,") Open relay? Nonlocal recips but not originating")>0){return;}
if(strpos($buffer,": not authenticated")>0){return;}
if(strpos($buffer,": dk_eom() returned status")>0){return;}
if(strpos($buffer,"ASN1_D2I_READ_BIO:not enough data")>0){return;}
if(strpos($buffer,"SpamControl: init_pre_fork on SpamAssassin done")>0){return;}
if(strpos($buffer,": Selected group:")>0){return;}
if(strpos($buffer,"Message entity scanning: message CLEAN")>0){return;}
if(strpos($buffer,"New connection on thread")>0){return;}
//if(strpos($buffer,"AM.PDP-SOCK/MYNETS")>0){return;}
if(strpos($buffer,": disconnect from")>0){return;} 
if(strpos($buffer,"sfupdates: KASINFO")>0){return;} 
if(strpos($buffer,": lost connection after CONNECT")>0){return;} 
if(strpos($buffer,"enabling PIX workarounds: disable_esmtp delay_dotcrlf")>0){return;} 
if(strpos($buffer,"Message Aborted!")>0){return;} 
if(strpos($buffer,"WHITELISTED [")>0){return;}
if(strpos($buffer,"COMMAND PIPELINING from")>0){return;}
if(strpos($buffer,"COMMAND COUNT LIMIT from [")>0){return;}
if(strpos($buffer,"]: warning: psc_cache_update:")>0){return;}
if(strpos($buffer,"]: PREGREET")>0){return;}
if(strpos($buffer,": PASS OLD [")>0){return;}
if(strpos($buffer,"]: DNSBL rank")>0){return;}
if(strpos($buffer,"]: HANGUP after")>0){return;}
if(strpos($buffer,": DISCONNECT [")>0){return;}
if(strpos($buffer,"KASNOTICE")>0){return;}
if(strpos($buffer,"KASINFO")>0){return;}
if(strpos($buffer,"]: PASS NEW [")>0){return;}
if(strpos($buffer,"]: COMMAND TIME LIMIT from")>0){return;}
if(strpos($buffer,"Client host triggers FILTER")>0){return;}
if(strpos($buffer,"Starting worker process for IMAP request")>0){return;}
if(strpos($buffer,"IMAP thread exiting")>0){return;}
if(strpos($buffer,"]: seen_db: user ")>0){return;}
if(strpos($buffer,"Client disconnected")>0){return;}
if(strpos($buffer,"starting the Postfix mail system")>0){return;}
if(strpos($buffer,"Postfix mail system is already running")>0){return;}
if(strpos($buffer,": Perl version")>0){return;}
if(strpos($buffer,": No decoder for")>0){return;}
if(strpos($buffer,"Using primary internal av scanner")>0){return;}
if(strpos($buffer,"starting.  /usr/local/sbin/amavisd")>0){return;}
if(strpos($buffer,") smtp resp to data-dot (")>0){return;}
if(strpos($buffer,") TIMING-SA total")>0){return;}
if(strpos($buffer,") sending SMTP response:")>0){return;}
if(strpos($buffer,") TIMING [total")>0){return;}
if(strpos($buffer,") Amavis::")>0){return;}
if(strpos($buffer,"] run_as_subprocess: child done")>0){return;}
if(strpos($buffer,"]: vstream_buf_get_ready:")>0){return;}
if(strpos($buffer,"]: > 127.0.0.1[")>0){return;}
if(strpos($buffer,"]: Using secondary internal")>0){return;}
if(strpos($buffer,"]: rec_get:")>0){return;}
if(strpos($buffer,") p004 1")>0){return;}
if(strpos($buffer,") p001 1")>0){return;}
if(strpos($buffer,") p002 1")>0){return;}
if(strpos($buffer,") p003 1")>0){return;}
if(strpos($buffer,") SPAM-TAG,")>0){return;}
if(strpos($buffer,"]: send attr")>0){return;} 
if(strpos($buffer,") (!)FWD from <")>0){return;} 
if(strpos($buffer,") bounce rescued by:")>0){return;}
if(strpos($buffer,") smtp session: setting")>0){return;}
if(strpos($buffer,") smtp cmd> MAIL FROM:")>0){return;}
if(strpos($buffer,") smtp cmd> RCPT TO:")>0){return;}
if(strpos($buffer,") smtp connection cache")>0){return;}
if(strpos($buffer,") spam_scan: score=")>0){return;}
if(strpos($buffer,") smtp session reuse,")>0){return;}
if(strpos($buffer,") smtp cmd> NOOP")>0){return;}
if(strpos($buffer,") smtp resp to NOOP")>0){return;}
if(strpos($buffer,") smtp cmd> DATA")>0){return;}
if(strpos($buffer,") smtp resp to MAIL")>0){return;}
if(strpos($buffer,") smtp resp to DATA:")>0){return;}
if(strpos($buffer,") smtp cmd> QUIT")>0){return;}
if(strpos($buffer,") smtp session most")>0){return;}
if(strpos($buffer,") smtp resp to RCPT")>0){return;}
if(strpos($buffer,") inspect_dsn:")>0){return;}
if(strpos($buffer,"IO::Socket::INET")>0){return;}
if(strpos($buffer,") smtp resp to greeting")>0){return;}
if(strpos($buffer,") smtp cmd> EHLO")>0){return;}
if(strpos($buffer,") smtp resp to EHLO:")>0){return;}
if(strpos($buffer,") smtp resp to RCPT (")>0){return;}
if(strpos($buffer,"exiting on SIGTERM/SIGINT")>0){return;}
if(strpos($buffer,": ready for work")>0){return;}
if(strpos($buffer,": process started")>0){return;}
if(strpos($buffer,"]: entered child_init_hook")>0){return;}
if(strpos($buffer,"]: SpamControl: init_child on SpamAssassin done")>0){return;}
if(preg_match("#kavmilter\[.+?\[tid.+?New message from:#",$buffer,$re)){return null;}
if(preg_match("#assp\[.+?LDAP Results#",$buffer,$re)){return null;}
if(preg_match("#amavis\[[0-9]+\]:\s+\([0-9\-]+\) FWD from <#",$buffer,$re)){return null;}
if(preg_match("#smtpd\[.+?\]: disconnect from#",$buffer,$re)){return null;}
if(preg_match("#smtpd\[.+?\]: timeout after END-OF-MESSAGE#",$buffer,$re)){return null;}
if(preg_match("#smtpd\[.+?\]:.+?enabling PIX workarounds#",$buffer,$re)){return null;}
if(preg_match("#milter-greylist:.+?skipping greylist#",$buffer,$re)){return null;}
if(preg_match("#milter-greylist:\s+\(.+?greylisted entry timed out#",$buffer,$re)){return null;}
if(preg_match("#postfix\/smtpd\[.+?\]:\s+lost connection after#",$buffer,$re)){return null;}
if(preg_match("#assp.+?\[MessageOK\]#",$buffer,$re)){return null;}
if(preg_match("#assp.+?\[NoProcessing\]#",$buffer,$re)){return null;}
if(preg_match("#passed trough amavis and event is saved#",$buffer,$re)){return null;}
if(preg_match("#assp.+?AdminUpdate#",$buffer,$re)){return null;}
if(preg_match("#last message repeated.+?times#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/master.+?about to exec#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/.+?open: user#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/lmtpunix.+?accepted connection#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/lmtpunix.+?Delivered:#",$buffer,$re)){return null;}
if(preg_match("#cyrus\/master.+?process.+?exited#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?mystore: starting txn#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?duplicate_mark#",$buffer,$re)){return null;}
if(preg_match("#lmtpunix.+?mystore: committing txn#",$buffer,$re)){return null;}
if(preg_match("#ctl_cyrusdb.+?archiving#",$buffer,$re)){return null;}
if(preg_match("#assp.+?LDAP - found.+?in LDAPlist;#",$buffer,$re)){return null;}
if(preg_match("#anvil.+?statistics: max#",$buffer,$re)){return null;}
if(preg_match("#smfi_getsymval failed for#",$buffer)){return null;}
if(preg_match("#cyrus\/imap\[.+?Expunged\s+[0-9]+\s+message.+?from#",$buffer)){return null;}
if(preg_match("#cyrus\/imap\[.+?seen_db:\s+#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?SSL_accept\(#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?starttls:#",$buffer)){return null;}
if(preg_match("#cyrus\/[pop3|imap]\[.+?:\s+inflate#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.*?fetching\s+user_.+? entry for '#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+accepted connection$#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+deflate\(#",$buffer)){return null;}
if(preg_match("#cyrus\/.+?\[.+?:\s+\=>\s+compressed to#",$buffer)){return null;}
if(preg_match("#filter-module\[.+?:\s+KASINFO#",$buffer)){return null;}
if(preg_match("#exec\.mailbackup\.php#",$buffer)){return null;}
if(preg_match("#kavmilter\[.+?\]:\s+Loading#",$buffer)){return null;}
if(preg_match("#DBERROR: init.+?on berkeley#",$buffer)){return null;}
if(preg_match("#FATAL: lmtpd: unable to init duplicate delivery database#",$buffer)){return null;}
if(preg_match("#skiplist: checkpointed.+?annotations\.db#",$buffer)){return null;}
if(preg_match("#duplicate_prune#",$buffer)){return null;}
if(preg_match("#cyrus\/cyr_expire\[[0-9]+#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.+?SSL_accept#",$buffer)){return null;}
if(preg_match("#cyrus\/pop3.+?SSL_accept#",$buffer)){return null;}
if(preg_match("#cyrus\/imap.+?:\s+executed#",$buffer)){return null;}
if(preg_match("#cyrus\/ctl_cyrusdb.+?recovering cyrus databases#",$buffer)){return null;}
if(preg_match("#cyrus.+?executed#",$buffer)){return null;}
if(preg_match("#postfix\/.+?refreshing the Postfix mail system#",$buffer)){return null;}
if(preg_match("#master.+?reload -- version#",$buffer)){return null;}
if(preg_match("#SQUAT failed#",$buffer)){return null;}
if(preg_match("#lmtpunix.+?sieve\s+runtime\s+error\s+for#",$buffer)){return null;}
if(preg_match("#imapd:Loading hard-coded DH parameters#",$buffer)){return null;}
if(preg_match("#ctl_cyrusdb.+?checkpointing cyrus databases#",$buffer)){return null;}
if(preg_match("#idle for too long, closing connection#",$buffer)){return null;}
if(preg_match("#amavis\[.+?Found#",$buffer)){return null;}
if(preg_match("#amavis\[.+?Module\s+#",$buffer)){return null;}
if(preg_match("#amavis\[.+?\s+loaded$#",trim($buffer))){return null;}

if(preg_match("#amavis\[.+?\s+Internal decoder#",trim($buffer))){return null;}
if(preg_match("#amavis\[.+?\s+Creating db#",trim($buffer))){return null;}
if(preg_match("#smtpd\[.+? warning:.+?address not listed for hostname#",$buffer)){return null;}
if(preg_match("#zarafa-dagent\[.+?Delivered message to#",$buffer)){return null;}
if(preg_match("#postfix\/policyd-weight\[.+?SPAM#",$buffer)){return null;}
if(preg_match("#postfix\/policyd-weight\[.+?decided action=550#",$buffer)){return null;}
if(preg_match("#cyrus\/lmtp\[.+?Delivered#",$buffer)){return null;}
if(preg_match("#ESMTP::.+?\/var\/amavis\/tmp\/amavis#",$buffer)){return null;}
if(preg_match("#zarafa-dagent.+?Client disconnected#",$buffer)){return null;}
if(preg_match("#zarafa-dagent.+?Failed to resolve recipient#",$buffer)){return null;}

// MIMEDFANG
if(strpos($buffer,"stderr: netset: cannot include")>0){return;}
if(strpos($buffer,"MySQL: from=<")>0){return;}

if(strpos($buffer, "MGREYSTATS")>0){$md5=md5($buffer);@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS/$md5", $buffer);return;}

if(stripos($buffer,"opendkim")>0){
	include_once(dirname(__FILE__).'/ressources/class.opendkim.maillog.inc');
	if(parse_opendkim($buffer)){return;}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: (discard|Quarantine): RCPT from\s+(.*?):.*?Message infected \[(.*?)\];.*?\[(.*?)\].*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[2];
	$ipaddr=$re[4];
	$reason="Infected:{$re[3]}";
	$mailfrom=$re[5];
	$mailto=$re[6];
	$helo=$re[2];
	if($hostname=="unknown"){$hostname=gethostbyaddr($ipaddr);}
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: MXCommand: connect: Connection refused: Is multiplexor running#",$buffer,$re)){
	$file="/etc/artica-postfix/pids/NOQUEUE.MXCommand.Connection.refused.multiplexor.running".__LINE__.".err";
	$timefile=file_time_min($file);
	if($timefile>0){
		events("Connection refused: Is multiplexor running ?? --> restart [OK] {$timefile}Mn");
		postfix_admin_mysql(1, "Policies service: (multiplexor running ?) Connection refused [action=restart]", $buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/mimedefang restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#Slave [0-9]+ stderr: bayes: cannot open bayes databases (.*?)\/bayes_.*?: lock failed: Interrupted system call#",$buffer,$re)){
	postfix_admin_mysql(1, "Spamassassin: bayes issue (lock failed) [action=notify]", $buffer,__FILE__,__LINE__);
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#milter-reject: END-OF-MESSAGE from\s+(.*?)\[(.+?)\]: 4.3.0 virus found (.*?); from=<(.*?)>\s+to=<(.+?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Virus {$re[3]}";
	$mailfrom=$re[4];
	$mailto=$re[5];
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;	
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from (.*?):\s+554.*?Client host \[(.*?)\] blocked using Spamassassin.*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Antispam denied";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=gethostbyaddr($ipaddr);}
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: Quarantine: RCPT from (.*?):\s+554.*?Client host \[(.*?)\] blocked using Spamassassin.*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Quarantine";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=gethostbyaddr($ipaddr);}
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: milter-reject: RCPT from (.*?)\[(.*?)\]: 451.*?Greylisting in action.*?; from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$reason="Greylisted";
	$mailfrom=$re[3];
	$mailto=$re[4];
	$helo=$re[5];
	if($hostname=="unknown"){$hostname=$helo;}
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#mimedefang.*?Could not connect to clamd daemon#",$buffer,$re)){
	events("Antivirus issue while checking mail [action=restart clamd]");
	$file="/etc/artica-postfix/pids/mimedefang.Could.not.connect.to.clamd.daemon";
	$timefile=file_time_min($file);
	if($timefile>0){
		postfix_admin_mysql(0, "Antivirus issue while checking mail [action=restart clamd]", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect to Milter service unix:.*?mimedefang\.sock: No such file or directory#",$buffer,$re)){
	events("mimedefang.sock: No such file or directory --> restart ?");
	$file="/etc/artica-postfix/pids/Milter.service.mimedefang.".__LINE__.".sock";
	$timefile=file_time_min($file);
	if($timefile>0){
		events("mimedefang.sock: No such file or directory --> restart [OK] {$timefile}Mn");
		postfix_admin_mysql(1, "mimedefang.sock: No such file or directory [action=restart]", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/mimedefang restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect to Milter service unix:.*?milter-greylist\.sock: Connection refused#",$buffer,$re)){
	events("milter-greylist.sock: Connection refused --> restart ?");
	$file="/etc/artica-postfix/pids/Milter.service.miltergreylist.".__LINE__.".sock";
	$timefile=file_time_min($file);
	if($timefile>0){
		events("milter-greylist.sock: --> restart [OK] {$timefile}Mn");
		postfix_admin_mysql(1, "milter-greylist.sock: Connection refused [action=restart]", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/milter-greylist restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from\s+(.*?)\[(.*?)\]:\s+554.*?blocked using\s+(.*?); Client host blocked using\s+(.*?),.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$Service=$re[3];
	$Service2=$re[4];
	$mailfrom=$re[5];
	$mailto=$re[6];
	$helo=$re[7];
	if($hostname=="unknown"){$hostname=$helo;}
	if(strlen($Service2)>3){$Service=$Service2;}
	$reason="Rbl:$Service";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450 4.7.1 Client host rejected: cannot find your reverse hostname.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$ipaddr=$re[1];
	$mailfrom=$re[2];
	$mailto=$re[3];
	$hostname=$re[4];
	$reason="Reverse not found";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject:\s+RCPT from unknown\[([0-9\.]+)\].*?Client host rejected: cannot find your hostname.*?from=<(.*?)>\s+to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[1];
	$mailfrom=$re[2];
	$mailto=$re[3];
	$reason="Hostname not found";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject:\s+RCPT from (.*?)\[([0-9\.]+)\].*?Client host rejected: Go Away.+?from=<(.*?)>\s+to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Blacklisted";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450.*?<(.*?)>: Sender address rejected: Domain not found; from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[5];
	$ipaddr=$re[1];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Unknown sender domain";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;	
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#NOQUEUE: reject: RCPT from (.*?)\[(.*?)\]: 450.*?Sender address rejected: Domain not found; from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname=$re[1];
	$ipaddr=$re[2];
	$mailfrom=$re[3];
	$mailto=$re[4];
	$reason="Unknown sender domain";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;	
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.*?)\]: 450.*?Client host rejected: cannot find your reverse hostname,.*?from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
	$date=date("Y-m-d H:i:s");
	$postgres=new postgres_sql();
	$hostname="Unknown";
	$ipaddr=$re[1];
	$mailfrom=$re[2];
	$mailto=$re[3];
	$reason="Unknown reverse hostname";
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;	
}


if(preg_match("#reject#",$buffer)){
	events("NOT TRAPPED \"$buffer\"");
	
}


if(preg_match("#unknown group name:\s+postdrop#i", $buffer,$re)){
	shell_exec("{$GLOBALS["GROUPADD"]} postdrop >/dev/null 2>&1");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: SASL authentication problem: unable to open Berkeley db \/etc\/sasldb2: Permission denied#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/SASL.authentication.problem.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/smtpd_sasl_path", "smtpd");
		shell_exec("{$GLOBALS["postconf_bin_path"]} -e \"smtpd_sasl_path=smtpd\"");
		shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/postfix reload >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}

if(preg_match("#smtpd.*?warning: No server certs available. TLS won't be enabled#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/postfix.No.server.certs.available.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){
		
		
	}
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#fatal: scan_dir_push: open directory .*?: Permission denied#", $buffer,$re)){
	shell_exec("{$GLOBALS["POSTFIX_BIN"]} set-permissions");
	shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/postfix restart >/dev/null 2>&1 &");
	return;
}
// ---------------------------------------------------------------------------------------------------------------

if(preg_match("#warning: SASL authentication problem: unable to open Berkeley db\s+(.+?):\s+Permission denied#", $buffer,$re)){
	$GLOBALS["CLASS_UNIX"]->chown_func("postfix","postfix", "{$re[1]}");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#hash.*? open database\s+(.*?)\.db: No such file or directory#", $buffer,$re)){
	if(!is_file($GLOBALS["postconf_bin_path"])){return;}
	events("Missing hash database {$re[1]} -> build it");
	@file_put_contents($re[1], "\n");
	shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postconf_bin_path"]} hash:{$re[1]} >/dev/null 2>&1 &");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#cyrus.*?DBERROR: opening (.*?)\.seen: cyrusdb error#", $buffer,$re)){
	events("cyrus, corrupted seen file {$re[1]}.seen");
	@unlink("{$re[1]}.seen");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#connect to.*?\[(.*?)lmtp\]:\s+Permission denied#", $buffer)){
	events("{$re[1]}/lmtp, permission denied, apply postfix:postfix");
	$GLOBALS["CLASS_UNIX"]->chown_func("postfix","postfix", "{$re[1]}/lmtp");
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#warning: connect \#[0-9]+\s+to subsystem private\/cyrus: No such file or directory#", $buffer)){
	events("Cyrus unconfigured, reconfigure it...");
	$file="/etc/artica-postfix/pids/cyrus-subsystem.".__LINE__.".time";
	$timefile=file_time_min($file);
	if($timefile>3){shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --imap-sockets >/dev/null 2>&1 &");}
		@unlink($file);
		@file_put_contents($file, time());
	
	return;
}
// ---------------------------------------------------------------------------------------------------------------


if(preg_match("#postfix-script\[.+?: the Postfix mail system is not running#", $buffer)){
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.time";
		$timefile=file_time_min($file);
		if($timefile>1){
			shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		} 
		return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#master.*?fatal: bind (.+?)\s+port\s+([0-9]+):\s+Address already in use#", $buffer,$re)){
	
	$port=$re[2];
	events("Port conflict on $port");
	exec("{$GLOBALS["fuser"]} $port/tcp 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#:\s+([0-9]+)#", $ligne,$re)){
			$tokill=$re[1];
			events("Killing PID $tokill");
			shell_exec_maillog("{$GLOBALS["kill"]} -9 $tokill");
		}
	}
	
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.".__LINE__.".time";
		$timefile=file_time_min($file);
		if($timefile>1){
			shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		} 
		return;
}
// ---------------------------------------------------------------------------------------------------------------
if(strpos($buffer,"fatal: mail system startup failed")>0){
	$sock=new sockets();
	if($GLOBALS["EnableStopPostfix"]==0){
		$file="/etc/artica-postfix/pids/postfix-script.start.".__LINE__.".time";
		$timefile=file_time_min($file);
		if($timefile>1){shell_exec_maillog("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");}
			@unlink($file);
			@file_put_contents($file, time());
		}
	return;	
}
// ---------------------------------------------------------------------------------------------------------------

if(strpos($buffer," amavis[")>0){
	$p=new amavis_maillog_buffer($buffer);
	if($p->parse()){$p=null;return;}
}

$p=new postfix_maillog_buffer($buffer);if($p->parse()){$p=null;return;}

if(strpos($buffer," zarafa-")>0){
	if(!class_exists("zarafa_maillog_buffer")){include_once(dirname(__FILE__)."/ressources/class.zarafa.maillog.inc");}
	$p=new zarafa_maillog_buffer($buffer);
	if($p->parse()){$p=null;return;}
}


if($GLOBALS["CLASS_SETTINGS"]->cyrus_imapd_installed){
	if(!class_exists("cyrus_maillog")){include_once(dirname(__FILE__)."/ressources/class.cyrus.maillog.inc");}
	$p=new cyrus_maillog($buffer);if($p->ParseBuffer()){$p=null;return;}
}


if(preg_match("#createuser\[.+?User store\s+'(.+?)'\s+createdi#",$buffer,$re)){
	$this->email_events("Zarafa server new store created for {$re[1]}",$buffer,"mailbox");
	return;
}

// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#clamav-milter.*?No clamd server appears to be available#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/clamav-milter.".md5("No clamd server appears to be available");
	$timefile=file_time_min($file);
	if($timefile>5){
		postfix_admin_mysql(0, "Milter Antivirus issue! [action=update signatures]", $buffer,__FILE__,__LINE__);
		$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.freshclam.php --execute >/dev/null 2>&1 &";
		@unlink($file);@file_put_contents($file,"#");
		events("$cmd");
		shell_exec_maillog($cmd);
	}
	return;
}
// ---------------------------------------------------------------------------------------------------------------
if(preg_match("#milter-greylist:.+?bind failed: Address already in use#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/milter-greylist.".md5("cannot start MX sync, bind failed: Address already in use");
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("milter-greylist: double service issue",
		"milter-greylist\n$buffer\nArtica will restart milter-greylist service","smtp");
		@unlink($file);@file_put_contents($file,"#");
		$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/milter-greylist restart >/dev/null 2>&1 &";
		events("$cmd");
		shell_exec_maillog($cmd);
		
	}
	return;
}


if(strpos($buffer,"inet_interfaces: no local interface found")>0){
	$file="/etc/artica-postfix/croned.1/postfix.error.inet_interfaces";
	events("inet_interfaces issues $buffer");	
	$timefile=file_time_min($file);
	if($timefile>10){
		email_events("{$re[1]}: misconfiguration on inet_interfaces",
		"Postfix claim \n$buffer\n\nIf this event is resended\nplease Check Artica Technology support service.","postfix");
		@unlink($file);@file_put_contents($file,"#");
		$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces >/dev/null 2>&1 &");
		events("$cmd");
		shell_exec_maillog($cmd);
		}
	return;	
}

if(preg_match("#mail_queue_enter.*?create file maildrop\/.*?Permission denied#", $buffer,$re)){
	chgrp("/var/spool/postfix/public", "postdrop");
	chgrp("/var/spool/postfix/maildrop", "maildrop");
	shell_exec("{$GLOBALS["CHMOD"]} 1730 /var/spool/postfix/maildrop");
	shell_exec("{$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start");
	return;
}
	
if(preg_match("#(.+?)\/smtpd\[.+?fatal:\s+config variable inet_interfaces#", $buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.error.inet_interfaces";
	events("inet_interfaces issues' '{$re[1]}'");
	$timefile=file_time_min($file);
	if($timefile>10){
		email_events("{$re[1]}: misconfiguration on inet_interfaces",
		"Postfix claim \n$buffer\n\nIf this event is resended\nplease Check Artica Technology support service.","postfix");
		@unlink($file);@file_put_contents($file,"#");
		if($re[1]=="postfix"){
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces >/dev/null 2>&1 &");
			events("$cmd");
			shell_exec_maillog($cmd);
		}else{
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php >/dev/null 2>&1 &");
			events("$cmd");
			shell_exec_maillog($cmd);
		}
	}
	return;			
}

	if(preg_match("#\]:\s+bayes: cannot open bayes databases\s+(.+?)\/bayes_.+?R\/.+?: tie failed.+?Permission denied#", $buffer,$re)){
		events("cannot open bayes databases , Permission denied' '{$re[1]}/bayes_*'");
		shell_exec_maillog("/bin/chown postfix:postfix {$re[1]}/bayes*");
		return;
	}


	if(preg_match("#\]:\s+bayes: cannot open bayes databases\s+(.+?)\/bayes_.+?R\/O: tie failed#", $buffer,$re)){
		events("cannot open bayes databases , unlink '{$re[1]}/bayes_seen' '{$re[1]}/bayes_toks'");
		if(is_file("{$re[1]}/bayes_seen")){@unlink("{$re[1]}/bayes_seen");}
		if(is_file("{$re[1]}/bayes_toks")){@unlink("{$re[1]}/bayes_toks");}
		return;
	}
	
	if(preg_match("#problem talking to server\s+127\.0\.0\.1:10040: Connection refused#",$buffer,$re)){
		events("Postfix: Postfwd2 issue... -> Connection refused");
		$file="/etc/artica-postfix/croned.1/postfix.postfwd2.Connection.refused";
		$timefile=file_time_min($file);
		if($timefile>5){
			email_events("Postfix: postfwd2 plugin is not available",
			"Postfix claim \n$buffer\nArtica will try to start postfwd2.","postfix");
			shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfwd2.php --start >/dev/null 2>&1"));
			@unlink($file);@file_put_contents($file,"#");
		}else{events("Postfix: Postfwd2 issue... -> Connection refused: {$timefile}Mn/5Mn");}
		return;
	}	
	if(preg_match("#problem talking to server\s+127\.0\.0\.1:7777: Connection refused#",$buffer,$re)){
		events("Postfix: policyd Daemon issue... -> Connection refused");
		$file="/etc/artica-postfix/croned.1/postfix.policyd.Connection.refused";
		$timefile=file_time_min($file);
		if($timefile>5){
			email_events("Postfix: policyd plugin is not available",
			"Postfix claim \n$buffer\nArtica will try to start policyd Daemon.","postfix");
			shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfwd2.php --start >/dev/null 2>&1"));
			@unlink($file);@file_put_contents($file,"#");
		}else{events("Postfix: Postfwd2 issue... -> Connection refused: {$timefile}Mn/5Mn");}
		return;
	}


	
	
	if(preg_match("#postfix-(.+?)\/smtpd\[[0-9]+\]:\s+warning:\s+connect to Milter service unix:(.+?):\s+Connection refused#", $buffer,$re)){
		
		events("Postfix: {$re[2]} socket issue Connection refused... (line ".__LINE__.")");
		$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.". md5($re[2]).".sock.No.such.file.or.directory";
		$timefile=file_time_min($file);
		if($timefile>5){
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /bin/chown postfix:postfix {$re[2]} >/dev/null 2>&1 &");
			events("Postfix:{$re[1]}: $cmd");
			shell_exec_maillog($cmd);
		}
		return;
	}

	if(preg_match("#smtpd\[.+?warning:\s+connect to Milter service unix:\/var\/spool\/postfix\/var\/run\/amavisd-milter\/amavisd-milter\.sock: No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/postfix.amavisd-milter.sock.No.such.file.or.directory";
		$timefile=file_time_min($file);
		events("Postfix: Amavisd socket issue... (line ".__LINE__.")");
		if($timefile>5){
			
			if(!is_file("/usr/local/sbin/amavisd-milter")){
				email_events("Postfix: amavisd-milter is not installed !, change the postfix method",
				"postfix claim \n$buffer\nit seems that amavisd-milter is not installed\nArtica will re-install amavisd-milter or just\nChange amavis hooking to after-queue in order to use amavis main daemon.","postfix");
				@unlink($file);@file_put_contents($file,"#");
				$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-make APP_AMAVISD_MILTER >/dev/null 2>&1 &");
				shell_exec_maillog($cmd);
				return;
			}
			
			$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/amavis start >/dev/null 2>&1 &");
			shell_exec_maillog($cmd);
			return;			
			
		}
		return;
	}



	if(preg_match("#\[.+?:\s+connect to 127\.0\.0\.1\[127\.0\.0\.1\]:2003:\s+Connection refused#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/postfix.port.2003.Connection.refused";
		$timefile=file_time_min($file);
		if($timefile>5){
				email_events("Postfix: Connect to zarafa LMTP port Connection refused zarafa-lmtp will be restarted",
				"postfix claim \n$buffer\nArtica will try to restart zarafa-lmtp daemon.","postfix");
				shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix restart zarafa-lmtp >/dev/null 2>&1 &"));
				@unlink($file);@file_put_contents($file,"#");
			}else{events("Postfix: Connect to zarafa LMTP port Connection refused: {$timefile}Mn/5Mn");}
		return;			
		}



if(preg_match("#smtp\[.+?:\s+connect to 127\.0\.0\.1\[127\.0\.0\.1\]:([0-9]+):\s+Connection refused#", $buffer,$re)){
	if(postfix_is_amavis_port($re[1])){
		$file="/etc/artica-postfix/croned.1/postfix.port.{$re[1]}.Connection.refused";
		$timefile=file_time_min($file);
		if($timefile>5){
			email_events("Postfix: Connect to amavis port {$re[1]} Connection refused Amavis will be restarted",
			"postfix claim \n$buffer\nArtica will try to restart amavis daemon.","postfix");
			shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/amavis restart --by-exec-maillog >/dev/null 2>&1 &"));
			@unlink($file);@file_put_contents($file,"#");
		}else{events("Postfix: Connect to amavis port {$re[1]} Connection refused: {$timefile}Mn/5Mn");}
		return;			
		
	}
}
	



if(preg_match("#cyrus\/.+?\[[0-9]+]#",$buffer)){
	include_once(dirname(__FILE__)."/ressources/class.cyrus.maillog.inc");
	$cyrus=new cyrus_maillog();
	if($cyrus->ParseBuffer($buffer)){return;}
	}
	
if(preg_match("#master\[.+?fatal: bind 127.0.0.1 port 33559: Address already in use#", $buffer,$re)){
	events("Postfix: bind 127.0.0.1 port 33559: Address already in use -> startit");
	shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
	return;
}	


if(preg_match("#postqueue.+?warning: Mail system is down#", $buffer,$re)){
	$sock=new sockets();
	$EnableStopPostfix=$sock->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==0){
		events("Postfix: Mail system is down:  -> startit");
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
	}
	
	return;
}	
	
if(preg_match("#postscreen.+?warning: database\s+(.+?):\s+could not delete entry for#", $buffer,$re)){
	events("Postscreen: Cache database failed");
	if(is_file($re[1])){
		@unlink($re[1]);
		email_events("Postfix: postscreen_cache_map problem",
		"postfix claim \n$buffer\nArtica have deleted {$re[1]} file to fix this issue.","postfix");
	}
}


if(preg_match("#fatal: dict_open: unsupported dictionary type: pcre:  Is the postfix-pcre package installed#i",$buffer,$re)){
	events("Postfix: pcre missing");
	$file="/etc/artica-postfix/croned.1/postfix.pcre.missing";
	$timefile=file_time_min($file);
	if($timefile>20){
		email_events("Postfix: pcre missing",
		"postfix claim \n$buffer\nArtica will try to upgrade postfix.","postfix");
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-make APP_POSTFIX >/dev/null 2>&1 &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix: pcre missing: {$timefile}Mn/20Mn");}
	return;			
}

if(preg_match("#zarafa-server.+?The recommended upgrade procedure is to use the zarafa7-upgrade commandline tool#",$buffer,$re)){
	
	$cmd=trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa-migrate.php --upgrade-7 >/dev/null 2>&1 &");
	events("zarafa-server, need to upgrade... -> $cmd");
	shell_exec_maillog($cmd);
}


if(preg_match("#zarafa-gateway.+?POP3, POP3S, IMAP and IMAPS are all four disabled#",$buffer,$re)){
	events("Zarafa-gateway No services enabled...???");
	$file="/etc/artica-postfix/croned.1/zarafa-gateway.no.services";
	$timefile=file_time_min($file);
	if($timefile>10){
		email_events("Zarafa mail server: No mailbox protocol ?",
		"Zarafa claim \n$buffer\nYou have disabled all mailboxes protocols.\nMeans that zarafa-gateway is not necessary ???\nAre you sure ??","mailbox");
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix: Zarafa-gateway No services enabled...: {$timefile}Mn/10Mn");}
	return;			
}


if(preg_match("#kavmilter\[.+?Cannot read template file:\s+(.+?)$#",$buffer,$re)){
	events("kavmilter: {$re[1]} missing");
	$md=md5($re[1]);
	$file="/etc/artica-postfix/croned.1/kavmilter.template.$md";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Kaspersky Milter: error template ".basename($re[1]),
		"kavmilter claim \n$buffer\nArtica will try to repair.","postfix");
		shell_exec_maillog("/bin/touch {$re[1]}");
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.kavmilter.php --templates >/dev/null 2>&1 &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{events("kavmilter: {$re[1]} missing: {$timefile}Mn/5Mn");}
	return;		
}



if(preg_match("#kavmilter\[.+?Can't load keys: No active key. Only skip actions allowed#",$buffer,$re)){
	events("kavmilter: key missing");
	$md=md5($re[1]);
	$file="/etc/artica-postfix/croned.1/kavmilter.no-active-key.error";
	$timefile=file_time_min($file);
	if($timefile>10){
		email_events("Kaspersky Milter: no license !!",
		"kavmilter claim \n$buffer\nPlease disable kavmilter plugin or perform a license key activation","postfix");
		@unlink($file);@file_put_contents($file,"#");
	}else{events("kavmilter: kavmilter: key missing: {$timefile}Mn/5Mn");}
	return;		
}






if(preg_match("#warning:.+?then you may have to chmod a\+r\s+(.+?)$#",$buffer,$re)){
	events("chmod a+r {$re[1]}");
	shell_exec_maillog("/bin/chmod a+r {$re[1]}");
	return;
}

if(preg_match("#imaps\[.+?Fatal error: tls_start_servertls.+?failed#",$buffer,$re)){
	events("Cyrus-imap : IMAP SSL FAILED");
	$file="/etc/artica-postfix/croned.1/imaps.error.tls_start_servertls";
	$timefile=file_time_min($file);
	if($timefile>5){
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.cyrus.php --imaps-failed >/dev/null 2>&1 &"));
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Cyrus-imap wait:{$timefile}Mn/5Mn");}
	return;		
}

if(preg_match("#fatal: file.+?main\.cf: parameter setgid_group: unknown group name:\s+(.+)#",$buffer,$re)){
	events("Postfix : group name {$re[1]} problem");
	$file="/etc/artica-postfix/croned.1/postfix.group.{$re[1]}.error";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: group {$re[1]} is not available",
		"Postfix claim \n$buffer\nArtica will try create this group.","postfix");
		$unix=new unix();
		$groupadd=$unix->find_program("groupadd");
		shell_exec_maillog("$groupadd {$re[1]}&");
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix: Postfix: group {$re[1]} is not available: {$timefile}Mn/5Mn");}
	return;		
}


if(preg_match("#fatal: parameter inet_interfaces: no local interface found for ([0-9\.]+)#i",$buffer,$re)){
	events("Postfix : NIC {$re[1]} problem");
	$file="/etc/artica-postfix/croned.1/postfix.interface.{$re[1]}.error";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: Interface {$re[1]} is not available",
		"Postfix claim \n$buffer\nArtica will try to restore TCP/IP interfaces.","postfix");
		@unlink("/etc/artica-postfix/MEM_INTERFACES");
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1 &"));
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix: Interface {$re[1]} is not available: {$timefile}Mn/5Mn");}
	return;		
}


if(preg_match("#qmgr\[.+?fatal: incorrect version of Berkeley DB: compiled against.+?run-time linked against#i",$buffer,$re)){
	events("Postfix : incorrect version of Berkeley DB");
	$file="/etc/artica-postfix/croned.1/qmgr.error.Berkeley";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: incorrect version of Berkeley DB",
		"Postfix claim \n$buffer\nArtica will upgrade/re-install your postfix version.","postfix");
		@unlink($file);
		shell_exec_maillog(trim("{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-make APP_POSTFIX 2>&1 &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix : incorrect version of Berkeley DB wait:{$timefile}Mn/5Mn");}
	return;		
}
if(preg_match('#smtpd\[.+? warning: unknown smtpd restriction: "(.+?)"#',$buffer,$re)){
	events("Postfix : incorrect parameters on smtpd restriction");
	$file="/etc/artica-postfix/croned.1/smtpd.error.restriction." .md5($re[1]);
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: incorrect parameters on smtpd restriction",
		"Postfix claim \n$buffer\nArtica will try to fix the problem.\nif this error is sended again, please contact Artica Support team.","postfix");
		@unlink($file);
		shell_exec_maillog(trim("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sender-restrictions &"));
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Postfix : incorrect parameters on smtpd restriction wait:{$timefile}Mn/5Mn");}
	return;		
}
if(preg_match('#spamc\[.+?connect to spamd on (.+?)\s+failed,.+?Connection refused#',$buffer,$re)){
	events("Spamassassin : {$re[1]} Connection refused");
	$file="/etc/artica-postfix/croned.1/spamc.error.cnx.refused." .md5($re[1]);
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Spamassassin: Connection refused on {$re[1]}",
		"Spamassassin claim \n$buffer\nYou should have less issues and better performances using Amavisd-new instead Spamassassin only","postfix");
		@unlink($file);
		@unlink($file);@file_put_contents($file,"#");
	}else{events("Spamassassin : {$re[1]} Connection refused wait:{$timefile}Mn/5Mn");}
	return;		
}





if(preg_match("#smtpd\[.+?warning: connect to 127.0.0.1:54423: Connection refused#",$buffer,$re)){
	events("restart Artica-policy");
	shell_exec_maillog("/etc/init.d/artica-postfix restart artica-policy &");
	return;
}



if(preg_match("#nss_wins\[.+?connect from (.+?)\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection($re[1],$re[2]);
	return;
}

if(preg_match("#nss_wins\[.+?warning: (.+?):\s+address not listed for hostname\s+(.+?)$#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"ADDR_NOT_LISTED1");
	return;
}

if(preg_match("#postscreen\[.+?CONNECT from \[(.+?)\]#i",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection(null,$re[1]);
	return;
}

if(preg_match("#smtpd\[.*?connect from\s+(.*?)\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection($re[1],$re[2]);
	return;
}

if(preg_match("#dnsblog\[.+?addr\s+(.+?)\s+listed by domain#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"RBL");
	return;
}

if(preg_match("#nss_wins\[.+?warning: (.+?):\s+hostname\s+(.+?)\s+verification failed: Name or service not known#",$buffer,$re)){
	//"verification failed: Name or service not known"
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"VERIFY_FAILED1");
	return;
}

if(preg_match("#nss_wins\[.+?timeout after DATA.+?from\s+(.+?)\[(.+?)\]#",$buffer,$re)){
	//"verification failed: Name or service not known"
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[2],"TIMEOUT");
	return;
}

if(strpos($buffer,"connect to Milter service inet:127.0.0.1:1052: Connection refused")>0){
	events("KavMilter stopped !");
	$md5=md5("connect to Milter service inet:127.0.0.1:1052: Connection refused");
	$file="/etc/artica-postfix/croned.1/postfix.milter.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: Kaspersky Antivirus For Postfix daemon is not available",
		"Postfix claim \n$buffer\nArtica will restart it's daemon.","postfix");
		@unlink($file);
		shell_exec_maillog("/etc/init.d/kavmilterd restart &");
		file_put_contents($file,"#");
		
	}else{
		events("connect to Milter service inet:127.0.0.1:1052: Connection refused :{$timefile}Mn/5Mn to wait");
	}
	return;	
}

if(preg_match("#problem talking to server .+?:10040: Connection timed out#",$buffer)){
	events("postfwd2 problem Connection timed out !");
	$md5=md5("problem talking to server .+?:10040: Connection timed out");
	$file="/etc/artica-postfix/croned.1/postfix.postfwd2.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: postfwd2 Postfix daemon is not available",
		"Postfix claim \n$buffer\nArtica will restart it's daemon.","postfix");
		@unlink($file);
		shell_exec_maillog($GLOBALS["PHP5_BIN"]." /usr/share/artica-postfix/exec.postfwd2.php --restart &");
		file_put_contents($file,"#");
		
	}else{
		events("connect to talking to server .+?:10040 :{$timefile}Mn/5Mn to wait");
	}
	return;		
}

if(preg_match("#postfix.+?fatal: non-null host address bits in.+?([0-9\.\/]+)\", perhaps you should use \"(.+?)\"\s+instead#",$buffer,$re)){
	events("NetWork & Nics, need to change from {$re[1]} to {$re[2]}");
	$md5=md5("{$re[1]}{$re[2]}");
	$file="/etc/artica-postfix/croned.1/postfix.network.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: Bad network parameter you have set {$re[1]} you need to set {$re[2]} instead !",
		"Postfix claim \n$buffer\n","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("Bad network parameter you have set {$re[1]} you need to set {$re[2]} instead :{$timefile}Mn");
	}
	return;	
}

if(preg_match("#postfix\/master\[.+?fatal:\s+open lock file\s+(.+?): unable to set exclusive lock: Resource temporarily unavailable#",$buffer,$re)){
	events("postfix: {$re[1]}, unable to set exclusive lock");
	$re[1]=trim($re[1]);
	$md5=md5("postfix: {$re[1]} unable to set exclusive lock");
	$file="/etc/artica-postfix/croned.1/postfix.error.$md5";
	$timefile=file_time_min($file);
	if($timefile>5){
		exec("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --repair-locks",$results);
		email_events("Postfix: {$re[1]} unable to set exclusive lock",
		"Postfix claim \n$buffer\nArtica tried to repair it\n".@implode("\n", $results),"postfix");
		if(is_file($re[1])){@unlink($re[1]);}
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("postfix: {$re[1]} unable to set exclusive lock instead wait:{$timefile}Mn");
	}
	return;	
}
// ##########################  emailrelay 


if(preg_match("#emailrelay:\s+error:\s+polling:\s+cannot stat\(\)\s+file:\s+(.+)#",$buffer,$re)){
	events("emailrelay: ".basename($re[1])." corrupted file");
	shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.emailrelay.php --corrupted \"{$re[1]}\" &");
	return;
}

if(preg_match("#emailrelay\[(.+?)\].+?emailrelay: error:\s+(.+)#",$buffer,$re)){
	if(strpos("$buffer","cannot stat")>0){return;}
	events("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}");
	email_events("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}","emailrelay claim \n$buffer\nCheck your configuration file","emailrelay");
	return;
}
if(preg_match("#emailrelay\[(.+?)\].+?emailrelay: warning:\s+(.+)#",$buffer,$re)){
	if(strpos("$buffer","cannot stat")>0){return;}
	events("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}");
	email_events("emailrelay PID {$re[1]} Error:Mass Mailing {$re[2]}","emailrelay claim \n$buffer\nCheck your configuration file","emailrelay");
	return;
} 

// ##########################

if(strpos($buffer,"warning: to change inet_interfaces, stop and start Postfix")>0){
	events("inet_interfaces: restarting postfix");
	shell_exec_maillog("{$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start &");
	return;
}

if(preg_match("#(.+?)\/smtpd.+?fatal: bad string length.+? inet_interfaces =#",$buffer,$re)){
	
	if($re[1]=="postfix"){
		$instance="master";
		$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces";
	}else{
		if(preg_match("#postfix-(.+)#",$re[1],$ri)){
			$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure {$ri[1]}";
			$instance=$ri[1];
		}
	}
	events("$instance:inet_interfaces is null ?? in postfix configuration file, try to repair");
	$file="/etc/artica-postfix/croned.1/postfix.$instance.inet_interfaces.null";
	$timefile=file_time_min($file);
	if($timefile>5){
		events("$cmd");
		email_events("$instance: inet_interfaces missing data parameter","Postfix claim \n$buffer\nArtica will change value to \"all\"","postfix");
		shell_exec_maillog("$cmd &");	
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("$instance: inet_interfaces is null ?? but require 5mn to wait current:{$timefile}Mn");
	}
	return;	
}

if(preg_match("#bounce\[.+?fatal: bad string length 0 < 1: myorigin#",$buffer,$re)){
	events("myorigin is null ?? in postfix configuration file, try to repair");
	$file="/etc/artica-postfix/croned.1/postfix.myorigin.null";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: myorigin missing data parameter","Postfix claim \n$buffer\nArtica will change value","postfix");
		shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --networks &");	
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("myorigin is null ?? but require 5mn to wait current:{$timefile}Mn");
	}
	return;	
}

if(preg_match("#local\[.+?warning: dict_ldap_connect: Unable to bind to server (.+?)\s+#",$buffer,$re)){
	events("{$re[1]} unavailable");
	$file="/etc/artica-postfix/croned.1/postfix.ldap.failed";
	$timefile=file_time_min($file);
	if($timefile>5){
		email_events("Postfix: LDAP server {$re[1]} unavailable","Postfix claim \n$buffer\nplease check the LDAP server database","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("$re[1]} unavailable but require 5mn to wait current:{$timefile}Mn");
	}
	return;	
}


if(preg_match("#postqueue\[.+?fatal: bad string length 0.+?:\s+(.+?)\s+#",$buffer,$re)){
	events("{$re[1]} is null ?? in postfix configuration file");
	$file="/etc/artica-postfix/croned.1/postfix.postdrop.permissions";
	if(file_time_min($file)>5){
		email_events("Postfix: {$re[1]} missing data parameter","Postfix claim \n$buffer\nContact your support team in order to fix this issue.","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;
}




if(preg_match("#zarafa-server\[.+?Server shutdown complete.#",$buffer,$re)){
	events("Zarafa stopped");
	email_events("Zarafa: Zarafa was successfully stopped","$buffer","mailbox");
	return;		
}

if(preg_match("#zarafa-server\[.+?Startup succeeded on pid#",$buffer,$re)){
	events("Zarafa started");
	email_events("Zarafa: Zarafa was successfully started","$buffer","mailbox");
	return;		
}

if(preg_match("#zarafa-server\[.+?SQL Failed: Can't connect to MySQL server on '(.+?)'#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/zarafa.mysql.error";
	events("Zarafa mysql server {$re[1]} error connect to MySQL");
	if(file_time_min($file)>5){
		email_events("Zarafa: Zarafa Can't connect to MySQL server {$re[1]}","Zarafa claims, $buffer\nArtica will try to fix it\nYou will recieve an other notification","mailbox");
		shell_exec_maillog($GLOBALS["PHP5_BIN"]." /usr/share/artica-postfix/exec.status.php --zarafa-watchdog &");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}	

if(preg_match("#zarafa-server\[.+?Unable to find company id for object\s+(.+?)$#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/zarafa.{$re[1]}.error";
	if(file_time_min($file)>5){
		events("{$re[1]}: user is not stored in artica Database");
		shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa.build.stores.php --emergency \"{$re[1]}\" &");
		email_events("Zarafa: Zarafa was successfully started","Zarafa claims, $buffer\nArtica will try to fix it","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;		
}





if(preg_match("#postfix\/master\[.+?fatal: bind 0\.0\.0\.0 port 25: Address already in use#",$buffer,$re)){
	email_events("Postfix will be restarted","Postfix claims, $buffer","postfix");
	shell_exec_maillog("/etc/init.d/postfix restart-single &");
	return;
}

if(preg_match("#zarafa-(.+?)\[.+?Starting zarafa-.+?, pid\s+([0-9]+)#",$buffer,$re)){
	email_events("Zarafa: {$re[1]} successfully started pid {$re[2]}",$buffer,"system");
	return;
}

if(preg_match("#zarafa-dagent\[.+?Failed to resolve recipient (.+?)$#",$buffer,$re)){
	$re[1]=trim($re[1]);
	$file="/etc/artica-postfix/croned.1/zarafa.{$re[1]}.error";
	if(file_time_min($file)>10){
		$zarafa_admin=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-admin");
		exec("$zarafa_admin -l 2>&1",$results);
		email_events("Zarafa: {$re[1]} no such user","Zarafa failed to find {{$re[1]}}\n$buffer\nHere it is the results of already registered users:\n".@implode("\n",$results),"mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}

if(preg_match("#zarafa-dagent\[.+?Unable to login for user (.+?), error code: ([0-9a-zA-Z]+)#",$buffer,$re)){
	$re[1]=trim($re[1]);
	$file="/etc/artica-postfix/croned.1/zarafa.{$re[1]}.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa.build.stores.php --orphans");
			$textadd="Please check if this user exists in the LDAP database, artica will check orphans users and stores in background mode";
			email_events("Zarafa: {$re[1]} user failed to login","Zarafa failed to login {{$re[1]}}\n$buffer\nHere it is the results of already registered users:\n".@implode("\n",$results),"\n$textadd","mailbox");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}




if(preg_match("#zarafa-server\[.+?Unable to start server on port 236: Address already in use#",$buffer,$re)){
	events("Zarafa-server error port 236 failed");
	$file="/etc/artica-postfix/croned.1/zarafa.236.error";
	if(file_time_min($file)>10){
		email_events("Zarafa: unable to start port already open","Zarafa claim \n$buffer\nArtica will try to restart it","mailbox");
		shell_exec_maillog("/etc/init.d/artica-postfix restart zarafa-server &");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
	
}
if(preg_match("#zarafa-gateway\[.+?Unable to listen on port 110#",$buffer,$re)){
	events("Zarafa-server error port 110 failed");
	$file="/etc/artica-postfix/croned.1/zarafa.110.error";
	if(file_time_min($file)>10){
		email_events("Zarafa: unable to start port 110 already open","Zarafa claim \n$buffer\nArtica will try to restart it","mailbox");
		shell_exec_maillog("/etc/init.d/artica-postfix restart zarafa-server &");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
	
}

if(preg_match("#zarafa-licensed\[.+?License is for(.+?)users#",$buffer,$re)){
	events("Zarafa license={$re[1]}");
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ZarafaLicenseInfos",$re[1]);
}

 


if(preg_match("#postfix\/postdrop\[.+?warning: mail_queue_enter: create file maildrop\/.+?:\s+Permission denied#",$buffer,$re)){
	events("Permission denied on maildrop queue");
	$file="/etc/artica-postfix/croned.1/postfix.postdrop.permissions";
	if(file_time_min($file)>10){
		email_events("Postfix: Permissions problems on postdrop queue","Postfix claim \n$buffer\nArtica will try to fix it","postfix");
		shell_exec_maillog("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --postdrop-perms &");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;
}

if(preg_match("#smtp\[.+?host\s+(.+?)\[.+?said:\s+421\s+4\.2\.1\s+MSG=.+?\(DNS:NR\)#",$buffer,$re)){
	events("mail Refused from {$re[1]}");
	$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.refused";
	if(file_time_min($file)>10){
		email_events("Postfix: your messages has been refused from {$re[1]}","Postfix claim \n$buffer\nCheck your smtp configuration in order to be compliance for {$re[1]}","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}


if(preg_match("#smtpd\[.+?NOQUEUE: reject:\s+RCPT from\s+(.+?)\[(.+?)\]:.+?<(.+?)>:\s+Recipient address rejected: Mail appeared to be SPAM or forged.+?from=<(.+?)>#",$buffer,$re)){
		events("mail Refused from {$re[1]} for {$re[4]}");
		$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.refused";
		$GLOBALS["maillog_tools"]->event_message_reject_hostname("Forged",$re[4],$re[3],$re[2],$re[1]);
		if(file_time_min($file)>10){
			email_events("Postfix: your messages has been refused from {$re[1]} ({$re[2]}) it seems your Forged your messages","Postfix claim \n$buffer\nCheck your smtp configuration in order to be compliance for {$re[1]}","postfix");
			@unlink($file);
			file_put_contents($file,"#");
		}
		
		return;
}

if(preg_match('#ClamAV-clamd.*?FAILED.*?output="(.*?):.*?Permission denied#',$buffer,$re)){
	$filename=$re[1];
	$dirname=dirname($filename);
	@chmod($dirname, 0777);
	return;
}

if(preg_match("#\[.+?NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Mail appeared to be SPAM or forged.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Forged",$re[2],$re[3],null,$re[1]);
	return;
}


if(preg_match("#postscreen\[.+?NOQUEUE: reject: RCPT from\s+\[(.+?)\].+?Service currently unavailable;\s+from=<(.*?)>,\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("PostScreen",$re[2],$re[3],null,$re[1]);
	return;
}

if(preg_match("#\[.+?:\s+NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Sender address rejected: blacklisted sender;\s+from=<(.*)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("blacklisted",$re[2],$re[3],$re[1]);
	return;
}
if(preg_match("#\]: NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Banned destination domain.+?from=<(.*?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Banned domain",$re[2],$re[3],$re[1]);
	return;
}


if(preg_match("#smtpd\[.+?NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Recipient address rejected: Your MTA is listed in too many DNSBLs.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("DNSBL",$re[1],$re[3],$re[4]);
	return;	
}


if(preg_match("#smtpd\[.*?warning: connect to 127\.0\.0\.1:7777: Connection refused#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.connexion-refused.".__LINE__.".error";
	events("Postfix connexion refused from iredMail");
	if(file_time_min($file)>10){
		$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/iredmail restart >/dev/null 2>&1 &";
		shell_exec_maillog(trim($cmd));
		email_events("Postfix: Unable to connect to iRedMail","Postfix claim\n$buffer\nArtica will restart iredMail service","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}




if(preg_match("#postfix\/smtp.+?connect to\s+(.+?)\[(.+?)\]:([0-9]+):\s+Connection refused#",$buffer,$re)){
	$md5=md5($re[1]);
	$file="/etc/artica-postfix/croned.1/postfix.connexion-refused.$md5.error";
	events("Postfix connexion refused from {$re[1]}");
	if(file_time_min($file)>10){
		email_events("Postfix: Unable to connect to {$re[1]} on port {$re[3]}","Postfix claim\n$buffer\nPlease check if {$re[2]} is available","postfix");
		@unlink($file);
		file_put_contents($file,"#");		
	}
	return;	
	
}


if(preg_match("#NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Relay access denied;\s+from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.relay.access.denied";
	if(file_time_min($file)>30){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Postfix Relay access denied", "Artica will recompile Postfix in case of bad settings", "postfix");
		shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time);
	}	
	
	events("Relay access denied :{$re[1]} from {$re[2]} to {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Relay access denied",$re[2],$re[3],$re[1]);
	return;
}

if(preg_match("#cleanup\[.+?:\s+(.+?):\s+reject: body.+?\s+from.+?\[(.+?)\];\s+from=<(.*?)>\s+to=<(.+?)>.+?Message Body rejected#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"Banned words",$re[1],$re[2],$buffer);
	return;
}

if(preg_match("#postscreen.+?NOQUEUE: reject: RCPT from \[(.+?)\].+?Service unavailable;.+?blocked using.+?; from=<(.+?)>, to=<(.+?)>#",$buffer,$re)){
	events("PostScreen RBL :{$re[1]} from {$re[2]} to {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("PostScreen RBL",$re[2],$re[3],$re[1]);
	return;
}


if(strpos($buffer,"warning: cannot get certificate from file /etc/ssl/certs/postfix/ca.crt")>0){
	$file="/etc/artica-postfix/croned.1/postfix.certificate.error";
	events("Postfix certificate problems");
	if(file_time_min($file)>10){
		email_events("Postfix: SSL certificate error","Postfix claim\n$buffer\nArtica try to rebuild the certificate.","postfix");
		shell_exec_maillog("/usr/share/artica-postfix/bin/artica-install --change-postfix-certificate &");
		@unlink($file);
		file_put_contents($file,"#");			
	}
	return;
}

if(preg_match("#NOQUEUE: reject: CONNECT from.+?\[(.+?)\].+?: Client host rejected: Server configuration error;#",$buffer,$re)){
	events("postfix fatal error {$re[1]} rejected");
	$file="/etc/artica-postfix/croned.1/postfix.Server.configuration.error";
	if(file_time_min($file)>10){
		email_events("Postfix: Server configuration error mails from {$re[1]} has been rejected","Postfix claim\n$buffer\nPlease check your configuration.","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;		
}

if(preg_match("#postfix.+?NOQUEUE: reject: RCPT from.+?\[(.+?)\]: 554.+?: Relay access denied; from=<> to=<(.+?)>#",$buffer,$re)){
	events("Access denied :{$re[1]} from unknown to {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Access denied","unknown",$re[2],$re[1]);
	return;
}


if(preg_match("#NOQUEUE: reject: RCPT from.+?\[(.+?)\]:.+?Client host rejected: Access denied;\s+from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	events("Access denied :{$re[1]} from {$re[2]} to {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Access denied",$re[2],$re[3],$re[1]);
	return;
}

if(preg_match("#postfix.+?:\s+(.+):\s+milter-discard: END-OF-MESSAGE\s+from.+?\[(.+?)\]:\s+milter triggers DISCARD action;\s+from=<(.*?)>\s+to=<(.+?)>\s+#",$buffer,$re)){
	events("Rejected :{$re[1]} from {$re[2]} to {$re[2]}");
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[3],$re[4],$buffer,$re[2]);
	return;
}

if(preg_match("#smtpd\[.+?NOQUEUE: reject: MAIL from.+?\[(.+?)\]:.+?Sender address rejected: Domain not found;\s+from=<(.+?)>#",$buffer,$re)){
	events("Domain not found :{$re[1]} from {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Domain not found",$re[2],null,$re[1]);
	return;
}
if(preg_match("#smtpd\[.+?NOQUEUE: reject: MAIL from.+?\[(.+?)\]:.+?Sender address rejected: Access denied;\s+from=<(.+?)>#",$buffer,$re)){
	events("Access denied :{$re[1]} from {$re[2]}");
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Access denied",$re[2],null,$re[1]);
	return;
}

//SMTP HACK ######################################################################################################
if(preg_match("#postfix.+?timeout after.+?from.+?\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"Timeout");
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TIMEOUT"]=$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TIMEOUT"]+1;
		events("Postfix Hack: timeout from {$re[1]} {$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TIMEOUT"]} attempts/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]}");
		if($GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TIMEOUT"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"SMTPHACK_TIMEOUT");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}	
	}
	return null;
}
if(preg_match("#postfix.+?: too many errors after.+?from.+?\[(.+?)\]#",$buffer,$re)){
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TOO_MANY_ERRORS"]=$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TOO_MANY_ERRORS"]+1;
		events("Postfix Hack: too many errors from {$re[1]} {$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TOO_MANY_ERRORS"]} attempts/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]}");
		if($GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_TOO_MANY_ERRORS"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"SMTPHACK_TOO_MANY_ERRORS");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}	
	}
	return null;
}






if(preg_match("#postfix.+?: warning: (.+?): hostname.+?verification failed: Temporary failure in name resolution#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"verification failed");
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_RESOLUTION_FAILURE"]=$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_RESOLUTION_FAILURE"]+1;
		events("Postfix Hack: Temporary failure in name resolution from {$re[1]} {$GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_RESOLUTION_FAILURE"]} attempts/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]}");
		if($GLOBALS["SMTP_HACK"][$re[1]]["SMTPHACK_RESOLUTION_FAILURE"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"SMTPHACK_RESOLUTION_FAILURE");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}	
	}
	return null;
}


if(preg_match("#smtpd\[.+?:\s+reject:\s+CONNECT from\s+(.+?)\[([0-9\.]+)\]:\s+554.+?Service unavailable;.+?blocked#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[2],"RBL");	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[2]]["RBL"]+2;
		events("Postfix Hack: {$re[1]} RBL !! {$re[2]}={$GLOBALS["SMTP_HACK"][$re[2]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[2]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[2],$GLOBALS["SMTP_HACK"][$re[2]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[2]]);	
		}	
	}
	return null;
}


if(preg_match("#smtpd\[.+?warning:\s+(.+?):\s+hostname\s+(.+?)\s+verification failed: Name or service not known#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[2],$re[1],"Name or service not known");
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["NAME_SERVICE_NOT_KNOWN"]=$GLOBALS["SMTP_HACK"][$re[1]]["NAME_SERVICE_NOT_KNOWN"]+1;
		events("Postfix Hack: {$re[1]} Name or service not known {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["NAME_SERVICE_NOT_KNOWN"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["NAME_SERVICE_NOT_KNOWN"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"NAME_SERVICE_NOT_KNOWN");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);
		}
	}
	return;
}

if(preg_match('#warning.+?\[([0-9\.]+)\]:\s+SASL LOGIN authentication failed: authentication failure#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[1],"Login failed");
	
	$ipaddr=$re[1];
	if(!isset($GLOBALS["SMTP_HACK"][$ipaddr]["SASL_LOGIN"])){$GLOBALS["SMTP_HACK"][$ipaddr]["SASL_LOGIN"]=0;}
	$Count=intval($GLOBALS["SMTP_HACK"][$ipaddr]["SASL_LOGIN"]);
	
	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]>0){
		$Count++;
	 	events("Postfix Hack:bad SASL login $Count retries/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]} max attempts");
		if($Count>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]){
			events("Postfix Hack:smtp_hack_perform -> $ipaddr SASL_LOGIN");
			smtp_hack_perform($ipaddr,$GLOBALS["SMTP_HACK"][$ipaddr],"SASL_LOGIN");
			unset($GLOBALS["SMTP_HACK"][$ipaddr]);
			return;	
		}
	}
	$GLOBALS["SMTP_HACK"][$ipaddr]["SASL_LOGIN"]=$Count;
	return null;
}

if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?Service unavailable.+?blocked using.+?from=<(.+?)> to=<(.+?)> proto#",$buffer,$re)){
	
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]+1;
		events("Postfix Hack: {$re[1]} RBL !! from=<{$re[2]}> to=<{$re[3]}> {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}
if(preg_match("#smtpd.+?reject: RCPT from.+?\[(.+?)\]:\s+550.+?:.+Recipient address rejected:.+?because of previous errors.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]+1;
		events("Postfix Hack: {$re[1]} RBL !! from=<{$re[2]}> to=<{$re[3]}> {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}


if(preg_match("#smtpd.+?reject: RCPT from.+?\[(.+?)\]:\s+554.+?:.+Sender address rejected:.+?FORGED MAIL.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("FORGED",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]+1;
		events("Postfix Hack: {$re[1]} RBL !! from=<{$re[2]}> to=<{$re[3]}> {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}

if(preg_match("#:\s+NOQUEUE: reject: RCPT from.+?\[(.+?)\]:\s+550.+?:\s+Recipient address rejected: Mail appears to be SPAM or forged.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]+1;
		events("Postfix Hack: {$re[1]} RBL !! from=<{$re[2]}> to=<{$re[3]}> {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}





if(preg_match("#smtpd.+?reject: RCPT from unknown\[(.+?)\]:\s+550.+?:.+Recipient address rejected:.+?DNSBLs.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("RBL",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]=$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]+1;
		events("Postfix Hack: {$re[1]} RBL !! from=<{$re[2]}> to=<{$re[3]}> {$re[1]}={$GLOBALS["SMTP_HACK"][$re[1]]["RBL"]} attempts");
		if($GLOBALS["SMTP_HACK"][$re[1]]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"RBL");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}


if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?<(.+?)>:\s+Recipient address rejected: User unknown in local recipient table;\s+from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("User unknown",$re[2],$re[3],$re[1]);
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["USER_UNKNOWN"]=$GLOBALS["SMTP_HACK"][$re[1]]["USER_UNKNOWN"]+1;
		events("Postfix Hack: : {$re[1]} User unknown from=<{$re[2]}> to=<{$re[3]}> {$GLOBALS["SMTP_HACK"][$re[1]]["USER_UNKNOWN"]} attempts/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]}");
		if($GLOBALS["SMTP_HACK"][$re[1]]["USER_UNKNOWN"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"USER_UNKNOWN");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}

if(preg_match("#smtpd\[.+?warning: Illegal address syntax from.+?\[(.+?)\] in MAIL#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error(null,$re[1],"Illegal address");
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]>0){
		$GLOBALS["SMTP_HACK"][$re[1]]["BLOCKED_SPAM"]=$GLOBALS["SMTP_HACK"][$re[1]]["BLOCKED_SPAM"]+1;
		events("Postfix Hack: {$re[1]} Illegal address syntax {$GLOBALS["SMTP_HACK"][$re[1]]["BLOCKED_SPAM"]} attempts/{$GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]}");
		if($GLOBALS["SMTP_HACK"][$re[1]]["BLOCKED_SPAM"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]){
			smtp_hack_perform($re[1],$GLOBALS["SMTP_HACK"][$re[1]],"BLOCKED_SPAM");
			unset($GLOBALS["SMTP_HACK"][$re[1]]);	
		}
	}	
	return null;
}


if(preg_match("#postfix\/lmtp\[.+?:\s+(.+?):\s+to=<(.+)>,\s+relay=([0-9\.]+)\[.+?:[0-9]+,.+?status=deferred.+?430 Authentication required#",$buffer,$re)){
	events("postfix LMTP error to {$re[2]}");
	$file="/etc/artica-postfix/croned.1/postfix.lmtp.auth.failed";
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Mailbox Authentication required",$re[3],$re[2]);
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Postfix: LMTP Error","Postfix\n$buffer\nArtica will reconfigure LMTP settings","postfix");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} {$GLOBALS["MYPATH"]}/exec.postfix.maincf.php --mailbox-transport");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;	
	
}

if(preg_match("#postfix\/lmtp\[.+?:\s+connect to ([0-9\.]+)\[.+?:[0-9]+:\s+Connection refused#",$buffer)){
	events("postfix LMTP error");
	$file="/etc/artica-postfix/croned.1/postfix.lmtp.cnx.refused";
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"LMTP Error","127.0.0.1",$re[2]);
	if(file_time_min($file)>5){
		
		if($GLOBALS["ZARAFA_INSTALLED"]){
			email_events("Postfix: Zarafa LMTP Error","Postfix\n$buffer\nArtica will trying to start Zarafa","postfix");
			$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix start zarafa >/dev/null 2>&1 &";
			shell_exec_maillog(trim($cmd));
			@unlink($file);
			file_put_contents($file,"#");
			return;	
		}
		
		email_events("Postfix: LMTP Error","Postfix\n$buffer\nArtica will reconfigure LMTP settings","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} {$GLOBALS["MYPATH"]}/exec.postfix.maincf.php --mailbox-transport");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;		
}
if(preg_match("#postfix\/.+?:\s+warning:\s+problem talking to server\s+[0-9\.]+:12525:\s+Connection refused#",$buffer)){
	events("postfix policyd-weight error");
	$file="/etc/artica-postfix/croned.1/postfix.policyd-weight.conect.failed";
	
	if(file_time_min($file)>10){
		email_events("Postfix: Policyd-weight server connection problem","Postfix\n$buffer\nArtica will reconfigure restart policyd-weight service","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/policyd-weight start");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;		
}

if(preg_match("#KASERROR.+?keepup2date\s+failed.+?no valid license info found#",$buffer,$re)){
	events("Kas3, license error, uninstall kas3");
	$file="/etc/artica-postfix/croned.1/kas3.license.error";
	if(file_time_min($file)>5){
		email_events("Kaspersky Antispam: license error","Kaspersky Updater claim\n$buffer\nArtica will uninstall Kaspersky Anti-spam","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --kas3-remove");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}


if(preg_match("#postfix\/postfix-script\[.+?\]: fatal: the Postfix mail system is not running#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} start");
	}
	return;
}


if(preg_match("#zarafa-server\[.+?: SQL Failed: Table.+?zarafa\.(.+?)'\s+doesn.+?exist#",$buffer,$re)){
	events("Zarafa, missing table {$re[1]}");
	zarafa_rebuild_db($re[1],$buffer);
	return;
}

if(preg_match("#zarafa-server\[.+?INNODB engine is not support.+?Please enable the INNODB engine#",$buffer,$re)){
	events("Zarafa, INNODB not enabled, restart mysql {$re[1]}");
	$file="/etc/artica-postfix/croned.1/zarafa.INNODB.error";
	if(file_time_min($file)>5){
		email_events("Zarafa server: innodb is not enabled","Zarafa-server claim\n$buffer\nArtica will restart mysql","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}




if(preg_match("#zarafa-server\[.+?:\s+Cannot instantiate user plugin: ldap_bind_s: Invalid credentials#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/zarafa.ldap_bind_s.error";
	events("zarafa-server -> ldap_bind_s: Invalid credentials");
	if(file_time_min($file)>5){
		email_events("Zarafa server cannot connect to ldap server","Zarafa-server claim\n$buffer\nArtica will restart and reconfigure zarafa","mailbox");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart zarafa");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	
	return;
}

if(preg_match("#smtp\[.+? fatal: specify a password table via the.+?smtp_sasl_password_maps.+?configuration parameter#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.smtp_sasl_password_maps.error";
	events("postfix -> smtp_sasl_password_maps");
	if(file_time_min($file)>5){
		email_events("Postfix configuration problem","Postfix claim\n$buffer\nArtica will disable SMTP Sasl feature","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --disable-smtp-sasl");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#amavis\[.+?TROUBLE.+?in child_init_hook: BDB can't connect db env.+?No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.BDB.error";
	events("amavis BDB ERROR");
	if(file_time_min($file)>5){
		email_events("AMAVIS BDB Error","amavis claim\n$buffer\nArtica will restart amavis service","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}
if(preg_match("#amavis\[.*?\]:.*?DIE.*?BDB\s+can't connect db.*?\/var(.+?): No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.BDB.error";
	events("amavis BDB ERROR");
	if(file_time_min($file)>5){
		email_events("AMAVIS BDB Error","amavis claim\n$buffer\nArtica will restart amavis service","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;
}



if(preg_match("#amavis\[.+?custom checks error:\s+Insecure dependency in connect while running with -T switch at .+?/IO/Socket\.pm line 114#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.Compress-Raw-Zlib.error";
	events("amavis Compress-Raw-Zlib error -> check Compress-Raw-Zlib version");
	if(file_time_min($file)>5){
		email_events("AMAVIS dependency Error","amavis claim\n$buffer\nArtica will try to check depencies, especially \Compress-Raw-Zlib\"","postfix");
		//THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}


if(preg_match("#amavis\[.+?connect_to_ldap: bind failed: LDAP_INVALID_CREDENTIALS#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.LDAP.error";
	events("amavis LDAP ERROR");
	if(file_time_min($file)>5){
		email_events("AMAVIS LDAP connexion Error","amavis claim\n$buffer\nArtica will restart amavis service to reconfigure it","postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#Decoding of p[0-9]+\s+\(.+?data, at least.+?failed, leaving it unpacked: Compress::Raw::Zlib version\s+(.+?)\s+required.+?this is only version\s+(.+?)\s+#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/amavis.Compress.Raw.Zlib.error";
	events("amavis Compress::Raw::Zlib need to be upgraded");
	if(file_time_min($file)>20){
		
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("AMAVIS Compress::Raw::Zlib need to be upgraded from {$re[1]} to {$re[2]}","amavis claim\n$buffer\nArtica will install a newest Compress::Raw::Zlib version","postfix");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-make APP_COMPRESS_ROW_ZLIB");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;	
}

if(preg_match("#smtp\[.+?:\s+fatal: valid hostname or network address required in server description:(.+?)#",$buffer,$re)){
	mail_events("{$re[1]} Bad configuration parameters","Postfix claim\n$buffer\nPlease come back to the interface and check your configuration!","postfix");
	return;
}


if(preg_match("#.+?postfix-.+?\/master\[.+?:\s+fatal:\s+bind\s+[0-9\.]+\s+port\s+25:\s+Address already in use#",$buffer,$re)){
	events("Address already in use -> restart postfix");
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		email_events("Postfix will be restarted","Line: ". __LINE__."\nPostfix claims, $buffer","postfix");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/postfix restart-single");
	}
	return null;	
}

if(preg_match("#postfix\/.+?warning:\s+(.+?)\s+and\s+(.+?)\s+differ#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/cp -pf {$re[2]} {$re[1]}");}
	return ;
}

if(preg_match("#smtpd\[.+?warning:\s+connect to Milter service unix:(.+?):\s+Permission denied#",$buffer,$re)){
	events("chown postfix:postfix {$re[1]}");
	shell_exec_maillog("/bin/chown postfix:postfix {$re[1]} &");
	return;
}

if(preg_match("#spamd\[[0-9]+.+?Can.+?locate\s+Mail\/SpamAssassin\/CompiledRegexps\/body_[0-9]+\.pm#",$buffer,$re)){
	SpamAssassin_error_saupdate($buffer);
	return null;
}

if(preg_match("#zarafa-monitor.+?:\s+Unable to get store entry id for company\s+(.+?), error code#",$buffer,$re)){
	zarafa_store_error($buffer);
	return null;
}



if(preg_match("#postfix\/lmtp.+?:\s+(.+?):\s+to=<(.+?)>.+?lmtp.+?deferred.+?451.+?Mailbox has an invalid format#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Mailbox corrupted",null,$re[2]);
	mailbox_corrupted($buffer,$re[2]);
	return null;
	}
	

	
if(preg_match("#postfix\/lmtp.+?(.+?):\s+to=<(.+?)>.+?lmtp.+?status=deferred.+?452.+?Over quota#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Over quota",null,$re[2]);
	mailbox_overquota($buffer,$re[2]);
	return null;
	}	

if(preg_match("#postfix\/.+?:(.+?):\s+milter-reject: END-OF-MESSAGE\s+.+?Error in processing.+?ALL VIRUS SCANNERS FAILED;.+?from=<(.+?)>\s+to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"antivirus failed",$re[1],$re[2],$buffer);
	clamav_error_restart($buffer);
	return null;	
	}

if(preg_match("#postfix\/.+?:(.+?):\s+to=<(.+?)>,.+?\[(.+?)\].+?status=deferred.+?virus_scan FAILED#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"antivirus failed",$re[3],$re[2]);
	return null;
	}
	
if(preg_match("#smtp\[[0-9]+\]:\s+(.+?):\s+to=<(.+?)>,\s+relay=127\.0\.0.+:[0-9]+,.+?deferred.+?451.+?during fwd-connect\s+\(Negative greeting#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Internal timed-out","127.0.0.1",$re[2]);
	$file="/etc/artica-postfix/croned.1/timedout-amavis";
	events("fwd-connect ERROR");
	if(file_time_min($file)>5){
		events("fwd-connect ERROR -> restarting Postfix");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} stop");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["postfix_bin_path"]} start");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return;		
}
	
	
if(preg_match("#master\[.+?:\s+fatal:\s+binds\+(.+?)\s+port\s+(.+?).+?Address already in use#",$buffer,$re)){
	postfix_bind_error($re[1],$re[2],$buffer);
	return null;
}


if(preg_match("#kavmilter\[.+?:\s+KAVMilter Error\(13\):\s+Active key expired.+?Exiting#",$buffer,$re)){
	kavmilter_expired($buffer);
	return null;
}


if(preg_match("#postfix.+?\[.+?fatal: open\s+\/etc\/postfix-(.+?)\/main\.cf:\s+No such file or directory#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.no-such-file";
	events("{$re[1]} -> bad main.cf ".dirname($re[1]));
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Postfix missing main.cf for {$re[1]} instance","Postfix claim\n$buffer\nArtica will reconfigure this instance","postfix");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure {$re[1]}");
		}
	@unlink($file);
	file_put_contents($file,"#");
	}
	return null;		
}

if(preg_match("#postmulti.+?fatal:.+?Failed to obtain all required /etc/postfix-(.+?)\/main\.cf parameters#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.no-maincf-params";
	events("{$re[1]} -> bad main.cf ".dirname($re[1]));
	if(file_time_min($file)>5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Postfix missing main.cf for {$re[1]} instance","Postfix claim\n$buffer\nArtica will reconfigure this instance","postfix");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure {$re[1]}");
		}
	@unlink($file);
	file_put_contents($file,"#");
	}
	return null;		
}
if(preg_match("#postfix-(.+?)\/postqueue\[.+?warning: Mail system is down#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/instance-{$re[1]}.down";
	$ftime=file_time_min($file);
	events("{$re[1]} -> system down ({$ftime}mn)");
	if($ftime>=5){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-start {$re[1]}";
			email_events("Postfix {$re[1]} instance stopped","Postfix claim\n$buffer\nArtica will start this instance","postfix");
			events("$cmd");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		}
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;		
}

if(preg_match("#postfix-(.+?)\/master\[.+?daemon started#",$buffer,$re)){
	events("{$re[1]} -> system start");
	email_events("Postfix {$re[1]} instance started","Postfix notify\n$buffer\n","postfix");
	return null;		
}


if(preg_match("#postfix\[.+?fatal: parameter inet_interfaces: no local interface found for ([0-9\.]+)#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/inet_interfaces-{$re[1]}.down";
	$ftime=file_time_min($file);
	events("{$re[1]} -> interface down ({$ftime}mn)");
	if($ftime>=5){
		email_events("Postfix interface {$re[1]} down","Postfix claim\n$buffer\n
		Check your configuration settings in order to see
		why \"{$re[1]}\" is not loaded","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match("#postmulti-script\[.+?warning: (.+?): please verify contents and remove by hand#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/". md5("{$re[1]}").".delete";
	$ftime=file_time_min($file);
	events("{$re[1]} -> delete");
	if($ftime>=5){
		if(is_dir($re[1])){
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/rm -rf {$re[1]} &");
			}
			@unlink($file);
			file_put_contents($file,"#");
		}
	}
	return null;
}



if(preg_match("#.+?\/(.+?)\[.+?:\s+fatal:\s+open\s+(.+?):\s+No such file or directory#",$buffer,$re)){
	postfix_nosuch_fileor_directory($re[1],$re[2],$buffer);
	return null;
}
if(preg_match("#.+?\/(.+?)\[.+?:\s+fatal:\s+open\s+(.+?)\.db:\s+Bad file descriptor#",$buffer,$re)){
	postfix_baddb($re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#postfix\/qmgr.+?:\s+(.+?):\s+from=<(.*?)>,\s+status=expired, returned to sender#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_finish($re[1],null,"expired","expired",$re[2],$buffer);
	return null;
}


if(preg_match("#postfix postmulti\[[0-9+]\]: fatal: No matching instances#",$buffer,$re)){
	multi_instances_reconfigure($buffer);
	return null;
}

if(preg_match('#NOQUEUE: reject: MAIL from.+?452 4.3.1 Insufficient system storage#',$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.storage.error";
	if(file_time_min($file)>10){
		email_events("Postfix Insufficient storage disk space!!! ","Postfix claim: $buffer\n Please check your hard disk space !" ,"system");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match("#starting amavisd-milter.+?on socket#",$buffer)){
	email_events("Amavisd New has been successfully started",$buffer,"system"); 
	return;
}


if(preg_match("#kavmilter\[.+?\]:\s+Could not open pid file#",$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.kavmilter.pid.error";
		if(file_time_min($file)>10){
			events("Kaspersky Milter PID error");
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				email_events("Kaspersky Milter PID error","kvmilter claim $buffer\nArtica will try to restart it","postfix");
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart kavmilter');
			}
			@unlink($file);
		}else{
			events("Kaspersky Milter PID error, but take action after 10mn");
		}	
	file_put_contents($file,"#");	
	return null;
	
}	


// HACK POP3
if(preg_match("#cyrus\/pop3\[.+?badlogin.+?.+?\[(.+?)\]\s+APOP.+?<(.+?)>.+?SASL.+?: user not found: could not find password#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
	}
if(preg_match("#cyrus\/pop3\[.+?:\s+badlogin:\s+.+?\[(.+?)\]\s+plaintext\s+(.+?)\s+SASL.+?authentication failure:#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
}

if(preg_match("#zarafa-gateway\[.+?: Failed to login from\s+(.+?)\s+with invalid username\s+\"(.+?)\"\s+or wrong password#",$buffer,$re)){
	hackPOP($re[1],$re[2],$buffer);
	return;
}


if(preg_match("#postfix\/.+?warning: TLS library problem.+?system library:fopen:No such file or directory.+?\('(.+?)',#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.tls.{$re[1]}.error";
		if(file_time_min($file)>5){
			events("TLS {$re[1]} No such file");
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				email_events("Postfix error TLS on {$re[1]} (no such file)","Postfix claim $buffer\nArtica will try to repair it by rebuilding certificate","postfix");
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --change-postfix-certificate');
			}
			@unlink($file);
		}else{
			events("TLS {$re[1]} No such file failure, but take action after 5mn");
		}	
	return null;
}


if(preg_match("#smtpd.+?:\s+warning: SASL authentication failure: no secret in database#",$buffer)){
	$file="/etc/artica-postfix/croned.1/postfix.sasl.secret.error";
		if(file_time_min($file)>10){
			events("SASL authentication failure");
			if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
				email_events("Postfix error SASL","Postfix claim $buffer\nArtica will try to repair it","postfix");
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --postfix-sasldb2');
			}
			@unlink($file);
		}else{
			events("SASL authentication failure, but take action after 10mn");
		}	
	return null;
	
}

if(preg_match("#smtp.+?connect to 127\.0\.0\.1\[127\.0\.0\.1\]:10024: Connection refused#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->AmavisConfigErrorInPostfix($buffer);
	return null;
}


if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+to=<(.+?)>.+?status=deferred\s+\(SASL authentication failed.+?\[(.+?)\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"authentication failed",$re[3],$re[2]);
	smtp_sasl_failed($re[3],$re[3],$buffer);
}


if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+to=<(.+?)>.+?status=bounced.+?.+?\[(.+?)\]\s+said:\s+554.+?http:\/\/#",$buffer,$re)){
	ImBlackListed($re[3],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[3],$re[2]);
	return null;
}

if(preg_match("#postfix\/(cleanup|bounce|smtp|smtpd|flush|trivial-rewrite)\[.+?warning: database\s+(.+?)\.db\s+is older than source file\s+(.+)#",$buffer,$re)){
	postfix_compile_db($re[3],$buffer);
	return null;
}
if(preg_match("#postfix\/(cleanup|bounce|smtp|smtpd|flush|trivial-rewrite)\[.+?fatal: open database\s+(.+?)\.db:\s+No such file or directory#",$buffer,$re)){
	postfix_compile_missing_db($re[2],$buffer);
	return null;
}

if(preg_match("#postfix\/smtp\[.+?:\s+(.+?):\s+host.+?\[(.+?)\]\s+said:\s+[0-9]+\s+invalid sender domain#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->Postfix_Addconnection_error($re[1],$re[2],"invalid sender domain");
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"invalid sender domain",$re[2],null);
	return null;
}

if(preg_match("#warning: connect to Milter service unix:(.+?)clamav-milter.ctl: Connection refused#",$buffer,$re)){
	MilterClamavError($buffer,"$re[1]/clamav-milter.ctl");
	return null;
}



if(preg_match("#warning: connect to Milter service unix:(.+?)greylist.sock: No such file or directory#",$buffer,$re)){
	miltergreylist_error($buffer,"{$re[1]}/greylist.sock");
	return null;
}

if(preg_match("#postfix\/smtpd\[.+?warning: connect to Milter service unix:(.+?)milter-greylist.sock: No such file or directory#",$buffer,$re)){
	miltergreylist_error($buffer,"{$re[1]}/milter-greylist.sock");
	return null;
}

if(preg_match("#warning: connect to Milter service unix:/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.sock: Connection refused#",$buffer)){
		AmavisConfigErrorInPostfix($buffer);
		return null;
}

if(preg_match("#qmgr.+?transport amavis: Connection refused#",$buffer)){
	AmavisConfigErrorInPostfixRestart($buffer);
	return null;
}



if(preg_match('#milter-greylist: greylist: Unable to bind to port (.+?): Permission denied#',$buffer,$re)){
	miltergreylist_error($buffer,$re[1]);
}

if(preg_match('#]:\s+(.+?): to=<(.+?)>.+?socket/lmtp\].+?status=deferred.+?lost connection with.+?end of data#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_finish($re[1],$re[2],"deferred","mailbox service error",null,$buffer);
	return null;
}




if(preg_match('#badlogin: \[(.+?)\] plaintext\s+(.+?)\s+SASL\(-13\): authentication failure: checkpass failed#',$buffer,$re)){
	if($GLOBALS["DisableMailBoxesHack"]==1){return;}
	if($GLOBALS["GlobalIptablesEnabled"]<>1){return;}
	$date=date('Y-m-d H');
	$_GET["IMAP_HACK"][$re[1]][$date]=$_GET["IMAP_HACK"][$re[1]][$date]+1;
	events("cyrus Hack:bad login {$re[1]}:{$_GET["IMAP_HACK"][$re[1]][$date]} retries");
	if($_GET["IMAP_HACK"][$re[1]][$date]>15){
		email_events("Cyrus HACKING !!!!","Build iptables rule \"iptables -I INPUT -s {$re[1]} -j DROP\" for {$re[1]}!\nlaster error: $buffer","mailbox");
		shell_exec_maillog("iptables -I INPUT -s {$re[1]} -j DROP");
		events("IMAP Hack: -> iptables -I INPUT -s {$re[1]} -j DROP");
		unset($_GET["IMAP_HACK"][$re[1]]);
	}
	
	return null;
}



if(preg_match('#badlogin: \[(.+?)\] plaintext\s+(.+?)\s+SASL\(-1\): generic failure: checkpass failed#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.checkpass.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Cyrus auth error","Artica will restart messaging service\n\"$buffer\"","mailbox");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
		}
		@unlink($file);
	}
	return null;
}
if(preg_match('#cyrus\/lmtpunix.+?DBERROR:\s+opening.+?\.db:\s+Cannot allocate memory#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.dberror.restart.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Cyrus DBERROR error","Artica will restart messaging service\n\"$buffer\"","mailbox");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
		}
		@unlink($file);
	}
	return null;
}
if(preg_match('#cyrus\/imap.+?DBERROR.+?Open database handle:\s+(.+?)tls_sessions\.db#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.dberror.tls_sessions.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			email_events("Cyrus DBERROR error","Artica will delete {$re[1]}tls_sessions.db file\n\"$buffer\"","mailbox");
			@unlink("{$re[1]}tls_sessions.db");
		}
		@unlink($file);
	}
	return null;
}


if(preg_match('#cyrus\/notify.+?DBERROR db[0-9]: PANIC: fatal region error detected; run recovery#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-recoverdb');
			email_events("Cyrus database error !!",$buffer,"mailbox");
		}
		events("DBERROR detected, take action");
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("(fatal region error detected; run recovery) DBERROR detected, but take action after 10mn");
	}
	return null;	
}


if(preg_match("#cyrus.+?DBERROR\s+db[0-9]+:\s+DB_AUTO_COMMIT may not be specified in non-transactional environment#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-ctl-cyrusdb');
			email_events("Cyrus database error !!",$buffer,"mailbox");
		}
		events("DBERROR detected, take action");
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("(DB_AUTO_COMMIT may not be specified in non-transactional) DBERROR detected, but take action after 10mn");
	}
	return null;
}

if(preg_match("#tlsmgr.+?fatal: open database .+?Stale NFS file handle#",$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/tlsmgr.Stale.NFS.file.handle";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on Postfix (tls manager)\n$buffer\nTo fix this issue, you need to reboot the computer\n";
			$buffer=$buffer."In order to release locked file\nIf reboot trough Artica did not working, run this commandline :\nshutdown -rF now";
			email_events("Stale NFS file handle !!",$buffer,"postfix");
			events("Stale NFS file handle");
			@unlink($file);
		}
		file_put_contents($file,"#");
	}else{
		events("tlsmgr:Stale NFS file handle, but take action after 10mn");
	}
	return null;
}






if(preg_match("#cyrus.+?:\s+DBERROR:\s+opening.+?mailboxes.db:\s+cyrusdb error#",$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db.error";
	if(file_time_min($file)>10){
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$buffer="Artica has detected a fatal error on cyrus\n$buffer\nArtica will try to repair it but it should not working\n";
			$buffer=$buffer."Perhaps you need to contact your support to correctly recover cyrus databases\n";
			$buffer=$buffer."Notice,read this topic : http://www.gradstein.info/software/how-to-recover-from-cyrus-when-you-have-some-db-errors/\n";
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --cyrus-recoverdb');
			email_events("Cyrus database error !!",$buffer,"mailbox");
		}
		@unlink($file);
		file_put_contents($file,"#");
	}else{
		events("DBERROR detected, but take action after 10mn");
	}
	return null;	
}
if(preg_match("#IMAP Login from\s+(.*?)\s+for user\s+(.+)#",$buffer,$re)){
	$service="imap";
	$server=trim($re[2]);
	$server_ip=null;
	$user=trim($re[4]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
}


if(preg_match('#cyrus\/(.+?)\[.+?login:(.+?)\[(.+?)\]\s+(.+?)\s+.+?User#',$buffer,$re)){
	$service=trim($re[1]);
	$server=trim($re[2]);
	$server_ip=trim($re[3]);
	$user=trim($re[4]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
	return null;
}

if(preg_match("#zarafa-gateway\[.+?:\s+IMAP Login from\s+(.+)\s+for user\s+(.+?)\s+#",$buffer,$re)){
	$service="IMAP";
	$server=trim($re[1]);
	$server_ip=trim($re[1]);
	$user=trim($re[2]);
	cyrus_imap_conx($service,$server,$server_ip,$user);
	return null;
}




if(preg_match('#cyrus\/ctl_mboxlist.+?DBERROR: reading.+?, assuming the worst#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.db1.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected a fatal error on cyrus\n$buffer\n\n";
		email_events("Cyrus database error !!",$buffer,"mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}
if(preg_match('#cyrus\/sync_client.+?Can not connect to server#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.cluster.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected that the cyrus cluster replica is not available on cyrus\n$buffer\n\n";
		email_events("Cyrus replica not available",$buffer,"mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}

if(preg_match('#cyrus\/sync_client.+?connect.+?failed: No route to host#',$buffer)){
	$file="/etc/artica-postfix/croned.1/cyrus.cluster.error";
	if(file_time_min($file)>10){
		$buffer="Artica has detected that the cyrus cluster replica is not available on cyrus\n$buffer\n\n";
		email_events("Cyrus replica not available",$buffer,"mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}

if(preg_match('#warning: dict_ldap_connect: Unable to bind to server ldap#',$buffer)){
	$file="/etc/artica-postfix/croned.1/ldap.error";
	if(file_time_min($file)>10){
		email_events("Postfix is unable to connect to ldap server ",$buffer,"system");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}





if(preg_match('#service pop3 pid.+?in BUSY state and serving connection#',$buffer)){
	$file="/etc/artica-postfix/croned.1/pop3-busy.error";
	if(file_time_min($file)>10){
		email_events("Pop3 service is overloaded","pop3 report:\n$buffer\nPlease,increase pop3 childs connections in artica Interface","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match('#milter inet:[0-9\.]+:1052.+?Connection timed out#',$buffer)){
	$file="/etc/artica-postfix/croned.1/KAV-TIMEOUT.error";
	if(file_time_min($file)>10){
		email_events("Postfix service Cannot connect to Kaspersky Antivirus milter",
		"it report:\n$buffer\nPlease,disable Kaspersky service or contact your support",
		"postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}

if(preg_match('#milter unix:/var/run/milter-greylist/milter-greylist.sock.+?Connection timed out#',$buffer)){
	$file="/etc/artica-postfix/croned.1/miltergreylist-TIMEOUT.error";
	if(file_time_min($file)>10){
		email_events("milter-greylist error",
		"it report:\n$buffer\nPlease,investigate what plugin cannot send to milter-greylist events",
		"postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match('#SASL authentication failure: cannot connect to saslauthd server#',$buffer)){
	$file="/etc/artica-postfix/croned.1/saslauthd.error";
	if(file_time_min($file)>10){
		email_events("saslauthd failed to run","it report:\n$buffer\nThis error is fatal, nobody can be logged on the system.","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;
}


if(preg_match("#smtp.+?warning:\s+(.+?)\[(.+?)\]:\s+SASL DIGEST-MD5 authentication failed#",$buffer,$re)){
	$router_name=$re[1];
	$ip=$re[2];
	smtp_sasl_failed($router_name,$ip,$buffer);
	return null;
}



if(preg_match('#warning: connect to Milter service unix:/var/run/kas-milter.socket: Permission denied#',$buffer)){
	$file="/etc/artica-postfix/croned.1/kas-perms.error";
	if(file_time_min($file)>10){
		email_events("Kaspersky Anti-spam socket error","it report:\n$buffer\nArtica will restart kas service...","postfix");
		@unlink($file);
		file_put_contents($file,"#");
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart kas3');
		}
		
	}
	return null;
}


if(preg_match('#smtpd.+?warning: problem talking to server (.+?):\s+Connection refused#',$buffer,$re)){
	$pb=md5($re[1]);
	
	$file="/etc/artica-postfix/croned.1/postfix-talking.$pb.error";
	$time=file_time_min($file);
	if($time>10){
		events("Postfix routing error {$re[1]}");
		email_events("Postfix routing error {$re[1]}","it report:\n$buffer\nPlease take a look of your routing table","postfix");
		@unlink($file);
		file_put_contents($file,"#");
	}
	events("Postfix routing error {$re[1]} (SKIP) $time/10mn");
	return null;
	
}



if(preg_match("#sync_client.+?connect\((.+?)\) failed: Connection refused#",$buffer,$re)){
$file="/etc/artica-postfix/croned.1/".md5($buffer);
	if(file_time_min($file)>10){
		email_events("Cyrus replica {$re[1]} cluster failed","it report:\n$buffer\n
		please check your support, mails will not be delivered until replica is down !","mailbox");
		@unlink($file);
		file_put_contents($file,"#");
	}
	return null;	
}


if(preg_match("#could not connect to amavisd socket /var/spool/postfix/var/run/amavisd-new/amavisd-new.sock: No such file or directory#",$buffer)){
	amavis_socket_error($buffer);
	return null;
	}
	
if(preg_match("#could not connect to amavisd socket.+?Connection timed out#",$buffer)){
	amavis_socket_error($buffer);
	return null;	
}

if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?Sender address rejected: Domain not found; from=<(.+?)> to=<(.+?)> proto#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Domain not found",$re[2],$re[3],$re[1]);
	events("{$re[1]} Domain not found from=<{$re[2]}> to=<{$re[3]}>");
	return null;
	}
	
if(preg_match("#NOQUEUE: reject:.+?from.+?\[([0-9\.]+)\]:.+?Client host rejected: cannot find your hostname.+?from=<(.+?)> to=<(.+?)> proto#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("hostname not found",$re[2],$re[3],$re[1]);
	return null;
}

if(preg_match("#smtpd.+?NOQUEUE:.+?from.+?\[(.+?)\].+?Client host rejected.+?reverse hostname.+?from=<(.+?)>.+?to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("hostname not found",$re[2],$re[3],$re[1]);
	return null;
}

if(preg_match("#smtpd.+?NOQUEUE: reject.+?from.+?\[(.+?)\].+?Helo command rejected:.+?from=<(.+?)> to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Helo command rejected",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#smtpd.+?NOQUEUE: reject.+?from.+?\[(.+?)\].+?4.3.5 Server configuration problem.+?from=<(.+?)> to=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Server configuration problem",$re[2],$re[3],$re[1]);
	return null;
}




if(preg_match("#postfix.+?\[.+?reject: header.+?from.+?\[([0-9\.]+)\];\s+from=<(.*?)>\s+to=<(.+?)>.+? too many rec.+?pients#",$buffer,$re)){
	events("too many recipients from {$re[2]} to {$re[3]}");
	if($GLOBALS["PostfixNotifyMessagesRestrictions"]==1){
		events("-> notification...");
		$GLOBALS["CLASS_UNIX"]->send_email_events("Blocked message too many recipients from {$re[2]}","Postfix claims $buffer","postfix");
	}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("too many recepients",$re[2],$re[3],$re[1]);
	return null;
}



if(preg_match("#cyrus.+?badlogin:\s+(.+?)\s+\[(.+?)\]\s+.+?\s+(.+?)\s+(.+)#",$buffer,$re)){
	$router=$re[1];
	$ip=$re[2];
	$user=$re[3];
	$error=$re[4];
	cyrus_bad_login($router,$ip,$user,$error);
	return null;
}



if(preg_match("#IOERROR.+?fstating sieve script\s+(.+?):\s+No such file or directory#",$buffer,$re)){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/touch \"".trim($re[1])."\"");
	}
	return null;
}



if(preg_match("#smtp.+?\].+?([A-Z0-9]+):\s+to=<(.+?)>.+?status=deferred.+?\((.+?)command#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"deferred",$re[2],$re[3]);
	return null;
}



if(preg_match("#smtp.+?:\s+(.+?):\s+to=<(.+?)>,\s+relay=none,.+?status=deferred \(connect to .+?\[(.+?)\].+?Connection refused#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Connection refused",$re[2],$re[3]);
	return null;
}



if(preg_match("#smtp.+?\].+?([A-Z0-9]+):.+?SASL authentication failed#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Authentication failed");
	return null;
}
if(preg_match("#smtp.+?\].+?([A-Z0-9]+):.+?refused to talk to me.+?554 RBL rejection#",$buffer,$re)){
	ImBlackListed($re[2],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted");
	return null;
}


if(preg_match("#smtp\[.+?:\s+(.+?):\s+to=<(.+?)>,\s+relay=.+?\[(.+?)\].+?status=deferred.+?refused to talk to me#",$buffer,$re)){
	ImBlackListed($re[3],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[3],$re[2]);
	return null;
}

if(preg_match("#postfix\/bounce\[.+?:\s+(.+?):\s+sender non-delivery notification#",$buffer,$re)){
	events("{$re[1]} non-delivery");
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"non-delivery",null,null);
	return null;
	}	


if(preg_match("#smtp\[.+?\]:\s+(.+?):\s+to=<(.+?)>, relay=(.+?)\[.+?status=bounced\s+\(.+?loops back to myself#",$buffer,$re)){
	if(!is_dir("/etc/artica-postfix/croned.1")){@mkdir("/etc/artica-postfix/croned.1",0755,true);}
	
	$file="/etc/artica-postfix/croned.1/postfix.loops.back.to.myself";
	if(file_time_min($file)>10){
		shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time);
	}
	
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"loops back to myself",$re[3],$re[2]);
	
	
	
	
	return null;
}

if(preg_match("#smtp\[.+?:\s+(.+?): host.+?\[(.+?)\] said.+?<(.+?)>:.+?Greylisting in action#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Greylisted",$re[2],$re[3]);		
	return null;	
}



if(preg_match("#smtp\[.+?:\s+(.+?):\s+host.+?\[(.+?)\]\s+refused to talk to me:#",$buffer,$re)){
	ImBlackListed($re[2],$buffer);
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Your are blacklisted",$re[2]);
	return null;
}

if(preg_match("#\/cleanup.*?:\s+([A-Z0-9]+):\s+redirect:.*?from\s+(.+?)\[([0-9\.]+)\];\s+from=<(.*?)>\s+to=<(.*?)>#", $buffer,$re)){
	$GLOBALS["maillog_tools"]->event_messageid_rejected($re[1],"Redirect",$re[2],$re[5],$re[4],$re[3]);
	return null;
}



if(preg_match('#milter-greylist:.+?:.+?addr.+?from <(.+?)> to <(.+?)> delayed for#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),
			"Greylisting",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match('#milter-greylist:.+?addr.+?\[(.+?)\] from <> to <(.+?)> delayed#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"Greylisting","unknown",$re[2],$buffer);
	return null;
}

if(preg_match('#milter-greylist: \(unknown id\): addr.+?\[(.+?)\] from\s+=(.+?)> to <(.+?)>\s+delayed#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].time()),"Greylisting",$re[2],$re[3],$buffer,$re[1]);
	return null;
}

if(preg_match("#assp.+?<(.+?)>\s+to:\s+(.+?)\s+recipient delayed#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"Greylisting",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#assp.+?MessageScoring.+?<(.+?)>\s+to:\s+(.+?)\s+\[spam found\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"SPAM",$re[1],$re[2],$buffer);
	return null;
}
if(preg_match("#assp.+?MalformedAddress.+?<(.+?)>\s+to:\s+(.+?)\s+\malformed address:'\|(.+?)'#",$buffer,$re)){
	eventsRTM("malformed address: $buffer");
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"malformed address (ASSP)",$re[1],$re[2],$buffer);
	return null;
}

if(preg_match("#assp.+?\[Extreme\]\s+(.+?)\s+<(.+?)>\s+to:\s+(.+?)\s+\[spam found\]#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"SPAM",$re[2],$re[3],$buffer,$re[1]);
	return null;	
}


if(preg_match("#assp.+?<(.*?)>\s+to:\s+(.+?)\s+bounce delayed#",$buffer,$re)){
	if($re[1]==null){$re[1]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_rejected(md5($re[1].$re[2].date('Y-m d H is')),"bounce delayed",$re[1],$re[2],$buffer);
}

if(preg_match("#assp.+?\[DNSBL\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("DNSBL",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#assp.+?\[URIBL\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("URIBL",$re[2],$re[3],$re[1]);
	return null;
}


if(preg_match("#assp.+?\[SpoofedSender\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+.+?No Spoofing Allowed#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("SPOOFED",$re[2],$re[3],$re[1]);
	return null;
}
if(preg_match("#assp.+?\[InvalidHELO\]\s+(.+?)\s+<(.*?)>\s+to:\s+(.+?)\s+#",$buffer,$re)){
	if($re[2]==null){$re[2]="Unknown";}
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("BAD HELO",$re[2],$re[3],$re[1]);
	return null;
}


if(preg_match("#NOQUEUE: reject: RCPT from.+?<(.+?)>: Recipient address rejected: User unknown in relay recipient table;.+?to=<(.+?)> proto=SMTP#",
$buffer,$re)){
	$id=md5($re[1].$re[2].date('Y-m d H is'));
	$GLOBALS["maillog_tools"]->event_finish($id,$re[2],"reject","User unknown",$re[1]);
	return null;
	
}




if(preg_match("#postfix\/lmtp.+?:\s+(.+?):\s+to=<(.+?)>.+?said:\s+550-Mailbox unknown#",$buffer,$re)){
	$id=$re[1];
	$to=$re[2];
	$GLOBALS["maillog_tools"]->event_message_milter_reject($id,"Mailbox unknown",null,$re[2],$buffer);
	mailbox_unknown($buffer,$to);
	return null;
}


if(preg_match('#: (.+?): reject: RCPT.+?Relay access denied; from=<(.+?)> to=<(.+?)> proto=SMTP#',$buffer,$re)){
	$file="/etc/artica-postfix/croned.1/postfix.relay.access.denied";
	if(file_time_min($file)>30){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Postfix Relay access denied", "Artica will recompile Postfix in case of bad settings", "postfix");
		shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time);
	}	
	
	if($re[1]=="NOQUEUE"){$re[1]=md5($re[3].$re[2].date('Y-m d H is'));}
	$GLOBALS["maillog_tools"]->event_finish($re[1],$re[3],"reject","Relay access denied",$re[2],$buffer);
	return null;
}

if(preg_match('#postfix.+?cleanup.+?:\s+(.+?):\s+milter-reject: END-OF-MESSAGE.+4.6.0 Content scanner malfunction; from=<(.+?)> to=<(.+?)> proto=SMTP#',
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_Content_scanner_malfunction($re[1],$re[2],$re[3]);
	return null;
}
if(preg_match("#postfix.+?cleanup.+?:\s+(.+?):\s+milter-discard.+?END-OF-MESSAGE.+?DISCARD.+?from=<(.+?)> to=<(.+?)> proto=SMTP#",
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[2],$re[3],$buffer);
	return null;
}

if(preg_match("#cleanup\[.+?:\s+(.+?):\s+milter-discard: END-OF-MESSAGE from.+?\[(.+?)\]:\s+milter triggers DISCARD action;\s+from=<(.+?)>\s+to=<(.+?)>#",
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_DISCARD($re[1],$re[3],$re[4],$buffer,$re[2]);
	return null;
}
	
if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+client=(.+)#",$buffer,$re)){
	$date=date('Y-m-d H:i:s');
	$GLOBALS["maillog_tools"]->event_newmail($re[4]);
	return null;
}



if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+message-id=<(.*?)>#",$buffer,$re)){
	events("NEW message_id {$re[4]} {$re[5]}");
	$GLOBALS["maillog_tools"]->event_newmail($re[4],$re[5]);
	return null;	
}
if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+from=<(.*?)>, size=([0-9]+)#",$buffer,$re)){
	events("NEW MAIL {$re[4]} <{$re[5]}> ({$re[6]} bytes)");
	$GLOBALS["maillog_tools"]->event_message_from($re[4],$re[5],$re[6]);
	return null;
}

if(preg_match("#NOQUEUE: milter-reject: RCPT from.+?: 451 4.7.1 Greylisting in action, please come back in .+?; from=<(.+?)> to=<(.+?)> proto=SMTP#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_reject_hostname("Greylisting",$re[1],$re[2]);
	return null;
}

if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+milter-reject:.+?:(.+?)\s+from=<(.+?)>#",$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[4],$re[5],$re[6],null,$buffer);
	return null;
}




if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+to=<(.+?)>,\s+orig_to=<.+?>,\s+relay=(.+?),\s+delay=.+?,\s+delays=.+?,\s+dsn=.+?,\s+status=([a-zA-Z]+)#"
,$buffer,$re)){
	if(preg_match('#\s+status=.+?\s+\((.+?)\)#',$buffer,$ri)){
		$bounce_error=$ri[1];
	}
   events("Finish {$re[4]} <{$re[5]}> ({$re[7]})");
   $GLOBALS["maillog_tools"]->event_finish($re[4],$re[5],$re[7],$bounce_error,null,$buffer);   
   return null;
	
}
if(preg_match("#^([A-ZA-z]+)\s+([0-9]+)\s+([0-9\:]+).+?:\s+([A-Z0-9]+):\s+to=<(.+?)>,\s+relay=(.+?),\s+delay=.+?,\s+delays=.+?,\s+dsn=.+?,\s+status=([a-zA-Z]+)#"
,$buffer,$re)){
	if(preg_match('#\s+status=.+?\s+\((.+?)\)#',$buffer,$ri)){
		$bounce_error=$ri[1];
	}
   $GLOBALS["maillog_tools"]->event_finish($re[4],$re[5],$re[7],$bounce_error,null,$buffer);   
   return null;	
}

	
//-------------------------------------------------------------- ERRORS

if(preg_match('#amavisd-milter.+?could not read from amavisd socket.+?\.sock:Connection timed out#',$buffer,$re)){
	amavis_socket_error($buffer);
	return null;
}

if(preg_match('#warning: milter unix.+?amavisd-milter.sock:.+SMFIC_MAIL reply packet header: Broken pipe#',$buffer,$re)){
	amavis_error_restart($buffer);
	return null;
}
if(preg_match('#sfupdates.+?KASERROR.+?keepup2date\s+failed.+?code.+?critical error#',$buffer,$re)){
	kas_error_update($buffer);
	return null;
}


if(preg_match('#lmtp.+?:\s+(.+?): to=<(.+?)>,.+?status=deferred.+?connect to .+?\[(.+?)\].+?No such file or directory#',
$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"deferred",null,$re[1]);
	cyrus_socket_error($buffer,"$re[3]");
	return null;
}

if(preg_match('#lmtp.+?:(.+?):\s+to=<(.+?)>.+?said: 550-Mailbox unknown#',$buffer,$re)){
	$GLOBALS["maillog_tools"]->event_message_milter_reject($re[1],"Mailbox unknown",null,$re[2]);
	mailbox_unknown($buffer,$re[2]);
	return null;
}

events_not_filtered("Not Filtered:\"$buffer\"");	
}




function events($text){
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
		$filename=basename(__FILE__);
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		error_log("[{$GLOBALS["MYPID"]}]: $filename $text");
}
		

function eventsRTM($text){
		$pid=getmypid();
		$date=date('H:i:s');
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.sql.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid] $text\n");
		@fclose($f);	
		}
	
function cyrus_imap_conx($service,$hostname,$ip,$user){
	$time=time();
	
	events("$service-connection: $hostname - > $ip");
	$fam=new familysite();
	if($hostname==null){$hostname=$fam->GetComputerName($ip);}
	$curdate=date("YmdH");
	$tablename="{$curdate}_hcnx";
	$zDate=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_POSTFIX_SQL"]->postfix_buildhour_connections();
	$domain=$fam->GetFamilySites($hostname);
	$zmd5=md5("$time$hostname$ip");
	$tablename="{$curdate}_hmbx";

	$sql="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`mbx_service`,`hostname`,`ipaddr`,`uid`,`imap_server`,`domain`)
	VALUES('$zmd5','$zDate','$service','$hostname','$ip','$user','{$GLOBALS["MYHOSTNAME"]}','$domain')";
	$GLOBALS["CLASS_POSTFIX_SQL"]->QUERY_SQL($sql);
}


function CyrusSocketErrot(){
	
	
}

function _MonthToInteger($month){
  $zText=$month;	
  $zText=str_replace('JAN', '01',$zText);
  $zText=str_replace('FEB', '02',$zText);
  $zText=str_replace('MAR', '03',$zText);
  $zText=str_replace('APR', '04',$zText);
  $zText=str_replace('MAY', '05',$zText);
  $zText=str_replace('JUN', '06',$zText);
  $zText=str_replace('JUL', '07',$zText);
  $zText=str_replace('AUG', '08',$zText);
  $zText=str_replace('SEP', '09',$zText);
  $zText=str_replace('OCT', '10',$zText);
  $zText=str_replace('NOV', '11',$zText);
  $zText=str_replace('DEC', '12',$zText);
  return $zText;	
}
function email_events($subject,$text,$context){
	$GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}
	
function interface_events($product,$line){
	$ini=new Bs_IniHandler();
	if(is_file("/usr/share/artica-postfix/ressources/logs/interface.events")){
		$ini->loadFile("/usr/share/artica-postfix/ressources/logs/interface.events");
	}
	$ini->set($product,'error',$line);
	$ini->saveFile("/usr/share/artica-postfix/ressources/logs/interface.events");
	@chmod("/usr/share/artica-postfix/ressources/logs/interface.events",0755);
	
}



function amavis_socket_error($line){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	events("AMAVIS SOCKET ERROR ! ($line)");
	$ftime=file_time_min($file);
	if($ftime<15){
		events("Unable to process new operation for amavis...waiting 15mn (current {$ftime}mn)");
		return null;
	}
	$unix=new unix();
	$stat=$unix->find_program("stat");
	exec("$stat /var/spool/postfix/var/run/amavisd-new/amavisd-new.sock 2>&1",$STATr);
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart-milter");
		email_events("Warning Amavis socket is not available",$line." (Postfix claim that amavis socket is not available, 
	Artica will restart amavis \"milter\" service)
	Here it is the stat results:
	------------------------------------------
	file requested :/var/spool/postfix/var/run/amavisd-new/amavisd-new.sock
	".@implode("\n",$STATr)
	,"postfix");
	}
	@unlink($file);
	@mkdir("/etc/artica-postfix/cron.1");
	@unlink($file);@file_put_contents($file,"#");	
}

function mailbox_unknown($line,$to){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.'.'.md5($to);
	if(file_time_min($file)<15){return null;}
	email_events("Warning unknown mailbox $to","Postfix claim: $to mailbox is not available you should create an alias or mailbox $line","mailbox");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	
}



 
function amavis_error_restart($buffer){
	events("amavis_error_restart:: $buffer");
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){
		events("amavis_error_restart:: wait 15mn");
		return null;
	}	
	email_events('Warning Amavis error',"Amavis claim that $buffer, Artica will restart amavis",'postfix');
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/amavis restart");
	}
	@unlink($file);
	file_put_contents($file,"#");	
	}
	
	function clamav_error_restart($buffer){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){
		email_events('Warning Clamad error',"Postfix claim that $buffer, Artica will restart clamav",'postfix');
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart clamd");
	}
	@unlink($file);
	file_put_contents($file,"#");	
	}	
	
function kas_error_update($buffer){
	events("kas_error_update:: $buffer");
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){return;}
	email_events('Kaspersky Anti-spam report failure when updating it`s database',"for your information: $buffer",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart kas3");
	@unlink($file);
	file_put_contents($file,"#");	
	}

function cyrus_generic_error($buffer,$subject){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	events("Cyrus error !! $buffer (cache=$file)");
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	email_events("cyrus-imapd error: $subject","$buffer, Artica will restart cyrus",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/cyrus-imapd restart");
	@unlink($file);
	file_put_contents($file,"#");
	
}

function cyrus_socket_error($buffer,$socket){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	email_events("cyrus-imapd socket error: $socket","Postfix claim \"$buffer\", Artica will restart cyrus",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/cyrus-imapd restart');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");
}






function SpamAssassin_error_saupdate($buffer){
$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$timeFile=file_time_min($file);
	if($timeFile<15){
		events("*** $buffer ****");
		events("Spamassassin no operations, blocked by timefile $timeFile Mn!!!");
		return null;}	
	events("Spamassassin error time:$timeFile Mn!!!");
	email_events("SpamAssassin error Regex","SpamAssassin claim \"$buffer\", Artica will run /usr/bin/sa-update to fix it",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-update --spamassassin --force");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	if(!is_file($file)){
		events("error writing time file:$file");
	}	
}

function miltergreylist_error($buffer,$socket){
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	email_events("Milter Greylist error: $socket","System claim \"$buffer\", Artica will restart milter-greylist",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/milter-greylist restart');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");
}



function MilterClamavError($buffer,$socket){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	email_events("Milter-clamav socket error: $socket","Postfix claim \"$buffer\", 
	Artica will grant postfix to this socket\but you can use amavis instead that will handle clamav antivirus scanner too",'postfix');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/chmod -R 775 ". dirname($socket));
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/bin/chown -R postfix:postfix ". dirname($socket));
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postqueue -f");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	
}
function AmavisConfigErrorInPostfixRestart($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){return null;}	
	email_events("Amavis network error: $socket","Postfix claim \"$buffer\", Artica will restart postfix",'postfix');
	
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/postfix restart-single");
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
}
function ImBlackListed($server,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($server);
	if(file_time_min($file)<15){return null;}	
	email_events("Your are blacklisted from $server","Postfix claim \"$buffer\", try to investigate why or contact our technical support",'postfix');
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
}


function postfix_compile_db($hash_file,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	events("DB Problem -> $hash_file");
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($hash_file);
	if(file_time_min($file)<5){return null;}
	
	if(!is_file($hash_file)){
		@file_put_contents($hash_file,"#");
	}
	$cmd=$unix->find_program("postmap"). " hash:$hash_file 2>&1";
	exec($cmd,$results);
	email_events("Postfix Database problem","Postfix claim \"$buffer\", Artica has recompiled ".basename($hash_file)."\n".@implode("\n",$results),'postfix');
	events("DB Problem -> $hash_file -> $cmd");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($unix->find_program("postfix"). " reload");		
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
	
}

function postfix_compile_missing_db($hash_file,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	events("DB Problem -> $hash_file");
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5($hash_file);
	if(file_time_min($file)<5){return null;}
	
	if(!is_file($hash_file)){
		@file_put_contents($hash_file,"#");
	}
	
	email_events("Postfix Database problem","Postfix claim \"$buffer\", Artica will create blanck file and recompile ".basename($hash_file),'postfix');
	$cmd=$unix->find_program("postmap"). " hash:$hash_file";
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	events("DB Problem -> $hash_file -> $cmd");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($unix->find_program("postfix"). " reload");		
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");		
	
}

function cyrus_bad_login($router,$ip,$user,$error){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5("$router,$ip,$user,$error");
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	email_events("User $user cannot login to mailbox","cyrus claim \"$error\" for $user (router:$router, ip:$ip),
	 please,send the right password to $user",'mailbox');
	@unlink($file);@file_put_contents($file,"#");		
}

function smtp_sasl_failed($router,$ip,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".".md5("$router,$ip");
	events("SMTP authentication failed from $router ($ip)"); 
	if(file_time_min($file)<15){return null;}
	@unlink($file);
	email_events("SMTP authentication failed from $router","Postfix claim \"$buffer\" for ip address $ip",'postfix');
	@unlink($file);@file_put_contents($file,"#");		
}

function kavmilter_expired($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".expired";
	if(file_time_min($file)<15){return null;}
	@unlink($file);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/kavmilterEnable","0");
	$cmd=LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.postfix.maincf.php --reconfigure";
	events("$cmd");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-postfix stop kavmilter");
	email_events("Kaspersky For Mail server, license expired","Postfix claim \"$buffer\" Artica will disable Kaspersky and restart postfix",'postfix');
	@unlink($file);@file_put_contents($file,"#");
	}

function hackPOP($ip,$logon,$buffer){
	if($GLOBALS["DisableMailBoxesHack"]==1){return;}
	if($GLOBALS["PopHackEnabled"]==0){return;}
	if($GLOBALS["GlobalIptablesEnabled"]<>1){return;}
	$file="/etc/artica-postfix/croned.1/postfix.hackPop3.error";
	if($ip=="127.0.0.1"){return;}
	$GLOBALS["POP_HACK"][$ip]=intval($GLOBALS["POP_HACK"][$ip])+1;
	$count=intval($GLOBALS["POP_HACK"][$ip]);
	events("POP HACK {$ip} email={$logon} $count/{$GLOBALS["PopHackCount"]} failed");

	if(file_time_min($file)>10){
			email_events("POPHACK {$ip}/{$logon} $count/{$GLOBALS["PopHackCount"]} failed",
			"Mailbox server claim $buffer\nAfter ( $count/{$GLOBALS["PopHackCount"]}) {$GLOBALS["PopHackCount"]} times failed, 
			a firewall rule will added","mailbox");
			@unlink($file);
		}else{
			events("User not found for mailbox {$ip}/{$logon} $count/{$GLOBALS["PopHackCount"]} failed");
		}	
	
	if($count>=$GLOBALS["PopHackCount"]){
		shell_exec_maillog("iptables -I INPUT -s {$ip} -j DROP");
		events("POP HACK RULE CREATED {$ip} $count/{$GLOBALS["PopHackCount"]} failed");
		email_events("HACK pop3 from {$ip}","A firewall rule has been created and this IP:{$ip} is now denied ","mailbox");
		unset($GLOBALS["POP_HACK"][$ip]);
	}
	file_put_contents($file,"#");	
}


function zarafa_store_error($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".store.error";
	if(file_time_min($file)<3600){return null;}
	@unlink($file);
	$cmd=LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.zarafa.build.stores.php";
	events("$cmd");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	email_events("Zarafa mailbox server store error","Zarafa claim \"$buffer\" Artica will try to reactivate stores and accounts",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
}

function postfix_nosuch_fileor_directory($service,$targetedfile,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($targetedfile).".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	
	$targetedfile=trim($targetedfile);
	if($targetedfile==null){return;}
	if(preg_match("#(.+?)\.db$#",$targetedfile,$re)){
		$unix=new unix();
		$postmap=$unix->find_program("postmap");
		$cmd="/bin/touch {$re[1]}";
		events(__FUNCTION__. " <$cmd>");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		$cmd="$postmap hash:{$re[1]}";
		events(__FUNCTION__. " <$cmd>");
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		email_events("missing database ". basename($targetedfile),"Service postfix/$service claim \"$buffer\" Artica will create a blank $targetedfile",'smtp');
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
		@unlink($file);@file_put_contents($file,"#");	
		return;		
	 }
	

	
	$cmd="/bin/touch $targetedfile";
	events("$cmd");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
	email_events("missing ". basename($targetedfile),"Service postfix/$service claim \"$buffer\" Artica will create a blank $targetedfile",'smtp');
	@unlink($file);@file_put_contents($file,"#");		
}
function postfix_baddb($service,$targetedfile,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($targetedfile).".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	$targetedfile=trim($targetedfile);
	if($targetedfile==null){return;}	
	$unix=new unix();
	$postmap=$unix->find_program("postmap");
	$cmd="$postmap hash:$targetedfile";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
	email_events("corrupted database ". basename($file),"Service postfix/$service claim \"$buffer\" Artica will rebuild $targetedfile.db",'smtp');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("postfix reload");
	@unlink($file);@file_put_contents($file,"#");	
	return;			
}

function multi_instances_reconfigure($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.".postfix.file";
	if(file_time_min($file)<15){return null;}	
	@unlink($file);
	$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);	
	email_events("multi-instances not correctly set","Service postfix claim \"$buffer\" Artica will rebuild multi-instances settings",'smtp');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function postfix_bind_error($ip,$port,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5("$ip:$port");
	if(file_time_min($file)<15){
		events("Postfix bind error, time-out");
		return null;
	}	
	@unlink($file);
	$cmd="{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --restart-all";
	events(__FUNCTION__. " <$cmd>");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);	
	email_events("Unable to bind $ip:$port","Service postfix claim \"$buffer\" Artica will restart all daemons to fix it",'smtp');
	@unlink($file);@file_put_contents($file,"#");	
	return;	
}



function mailbox_corrupted($buffer,$mail){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($mail);
	if(file_time_min($file)<15){
		events("mailbox_corrupted <$mail>, time-out");
		return null;
	}	
	@unlink($file);
	email_events("Corrupted mailbox $mail","Service postfix claim \"$buffer\" try to repair the mailbox or to use the command line
	turned out to be corrupted quota files:
	find ~cyrus -type f | grep quota\nremove the quota files for the affected mailbox(es)\nrun
	reconstruct -r -f user/mailboxoftheuser\n\n
	if you cannot perform this operation, you can open a ticket on artica technology company http://www.artica-technology.com' ",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function mailbox_overquota($buffer,$mail){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__.md5($mail);
	if(file_time_min($file)<15){
		events("mailbox_overquota <$mail>, time-out");
		return null;
	}	
	@unlink($file);
	email_events("mailbox $mail Over Quota","Service postfix claim \"$buffer\" try to increase quota for $mail' ",'mailbox');
	@unlink($file);@file_put_contents($file,"#");	
	return;		
}

function zarafa_rebuild_db($table,$buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	if(file_time_min($file)<15){
		events("Zarafa missing table <$table>, time-out");
		return null;
	}	
	@unlink($file);
	email_events("Zarafa missing Mysql table $table","Service Zarafa claim \"$buffer\" artica will destroy the zarafa database in order to let the Zarafa service create a new one' ",'mailbox');
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} ".dirname(__FILE__)."/exec.mysql.build.php --rebuild-zarafa");
	@unlink($file);@file_put_contents($file,"#");	
	return;		
	
}


function smtp_hack_reconfigure(){
	
	if(is_file("{$GLOBALS["ARTICALOGDIR"]}/smtp-hack-reconfigure")){
		@unlink("{$GLOBALS["ARTICALOGDIR"]}/smtp-hack-reconfigure");
	}
	
	$sock=new sockets();
	$GLOBALS["SMTP_HACK_CONFIG_RATE"]=unserialize(base64_decode($sock->GET_INFO("PostfixAutoBlockParameters")));
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["NAME_SERVICE_NOT_KNOWN"]=10;}
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SASL_LOGIN"]=15;}
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]=5;}	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["USER_UNKNOWN"]=10;}	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["BLOCKED_SPAM"]=5;}	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["ADDRESS_NOT_LISTED"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["ADDRESS_NOT_LISTED"]=2;}	
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TIMEOUT"]=10;}
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_RESOLUTION_FAILURE"]=2;}
	if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]==null){$GLOBALS["SMTP_HACK_CONFIG_RATE"]["SMTPHACK_TOO_MANY_ERRORS"]=10;}
	
	

while (list ($num, $ligne) = each ($GLOBALS["SMTP_HACK_CONFIG_RATE"]) ){
	$info="Starting......: ".date("H:i:s")." artica-postfix realtime logs SMTP HACK: $num=$ligne";
	events($info);
	echo $info."\n";
}
	
	
}


function smtp_hack_perform($servername,$array,$matches){
	if($servername=="127.0.0.1"){return;}
	if($GLOBALS["EnablePostfixAutoBlock"]==0){return;}
	$NAME_SERVICE_NOT_KNOWN=$array["NAME_SERVICE_NOT_KNOWN"];
	$SASL_LOGIN=$array["SASL_LOGIN"];
	$USER_UNKNOWN=$array["USER_UNKNOWN"];
	$RBL=$array["RBL"];
	$BLOCKED_SPAM=$array["BLOCKED_SPAM"];
	$ADDRESS_NOT_LISTED=$array["ADDRESS_NOT_LISTED"];
	
	if($NAME_SERVICE_NOT_KNOWN==null){$NAME_SERVICE_NOT_KNOWN=0;}
	if($SASL_LOGIN==null){$SASL_LOGIN=0;}
	if($USER_UNKNOWN==null){$USER_UNKNOWN=0;}
	if($RBL==null){$RBL=0;}
	if($BLOCKED_SPAM==null){$BLOCKED_SPAM=0;}
	if($ADDRESS_NOT_LISTED==null){$ADDRESS_NOT_LISTED=0;}
	
	//$EnablePostfixAutoBlock=$sock->GET_INFO("EnablePostfixAutoBlock");
	
	$text="
	Rule matched: $matches
	--------------------------------------------------------
	NAME_SERVICE_NOT_KNOWN attempts:\t$NAME_SERVICE_NOT_KNOWN
	SASL_LOGIN attempts:\t$SASL_LOGIN
	RBL attempts:\t$RBL
	USER_UNKNOWN attempts:\t$USER_UNKNOWN
	ADDRESS_NOT_LISTED attempts:\t$ADDRESS_NOT_LISTED
	BLOCKED_SPAM attempts:\t$BLOCKED_SPAM";
	
	$md=array(
		"IP"=>$servername,
		"MATCHES"=>$matches,
		"EVENTS"=>$text,
		"DATE"=>date("Y-m-d H:i:s")
	);
	
	$serialize=serialize($md);
	$md5=md5($serialize);
	@mkdir("{$GLOBALS["ARTICALOGDIR"]}/smtp-hack",0666,true);
	
	$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["iptables"]} -A INPUT -s $servername -p tcp --destination-port 25 -j DROP -m comment --comment \"ArticaInstantPostfix\" >/dev/null 2>&1";
	events($cmd);
	shell_exec($cmd);
	
	$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["iptables"]} -A INPUT -s $servername -p tcp --destination-port 465 -j DROP -m comment --comment \"ArticaInstantPostfix\"";
	events($cmd);
	shell_exec($cmd);	

	
	$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["iptables"]} -A INPUT -s $servername -p tcp --destination-port 587 -j DROP -m comment --comment \"ArticaInstantPostfix\"";
	events($cmd);
	shell_exec($cmd);	
	
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/smtp-hack/$md5.hack",$serialize);
	
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} ".dirname(__FILE__)."/exec.postfix.iptables.php --compile");
	
	
	
	events("SMTP Hack: $servername matches $matches $text");
	if(!$GLOBALS["SMTP_HACKS_NOTIFIED"][$servername]){
		$GLOBALS["SMTP_HACKS_NOTIFIED"][$servername]=true;
		email_events("[SMTP HACK]: $servername match rules",$text,'postfix');
	}
}
function events_not_filtered($text){
	error_log("Not filtered: $text");
	
}




function amavisd_milter_bin_path(){
	
	$path=$GLOBALS["CLASS_UNIX"]->find_program('amavisd-milter');
	if(is_file($path)){return $path;}
	$path=$GLOBALS["CLASS_UNIX"]->find_program('amavis-milter');
	if(is_file($path)){return $path;}	
}


function postfix_is_amavis_port($portToCheck){
	if(!isset($GLOBALS["AMAVIS_INSTALLED"])){$users=new settings_inc();$GLOBALS["AMAVIS_INSTALLED"]=$users->AMAVIS_INSTALLED;}
	
	
	if(!$GLOBALS["AMAVIS_INSTALLED"]){if($GLOBALS["VERBOSE"]){echo "AMAVIS_INSTALLED -> FALSE\n";return false;}}
	events("Postfix: bind 127.0.0.1 port $portToCheck: -> check Amavis");
	$f=explode("\n",@file_get_contents("/usr/local/etc/amavisd.conf"));
	while (list ($num, $line) = each ($f) ){
			if(preg_match("#inet_socket_port.+?\[(.+?)\]#", $line,$re)){
				$inet_socket_port=$re[1];
				if(strpos($inet_socket_port, ",")){
					$socketstmp=explode(",",$inet_socket_port);while (list ($a, $b) = each ($socketstmp) ){$socket[$b]=true;}
				}else{
					$socket[$inet_socket_port]=true;
				}
			}
		}

	if(!isset($socket)){
		events("Postfix: unable to detect sockets port");
		return false;
	}
	
	if(!isset($socket[$portToCheck])){events("Postfix: $portToCheck no such array...");
		return false;
	}
	
	if($socket[$portToCheck]){
		events("Postfix: $portToCheck is an amavis port");
		return true;
	}
	
	return false;
}

function amavis_sa_update($buffer){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){return;}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &";
	events("$cmd amavis_sa_update()");
	$file="/etc/artica-postfix/pids/".__FUNCTION__.".error.time";
	if(file_time_min($file)<15){events("-> detected $buffer, need to wait 15mn");return null;}	
	@unlink($file);
	@unlink($file);@file_put_contents($file,"#");	
	shell_exec_maillog(trim($cmd));
	events("$cmd");
	return;			
	
}

function shell_exec_maillog($cmd){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){
		events("`$cmd` will not be executed ActAsSMTPGatewayStatistics is enabled" );
		return;
	}
	$unix=new unix();
	$timeExec="/etc/artica-postfix/pids/shell_exec_maillog.".md5($cmd).".time";
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_sec($timeExec);
	if($time<10){
		events("EXEC: cannot execute `$cmd` before 10s of interval" );
		return;
	}
	
	shell_exec($cmd);
	events("EXEC:`$cmd`" );
	@unlink($timeExec);
	@file_put_contents($timeExec, time());
}

 
?>
