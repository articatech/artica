<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf_filtering.inc');
include_once(dirname(__FILE__).'/ressources/class.policyd-weight.inc');
include_once(dirname(__FILE__).'/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__).'/ressources/class.postfix.certificate.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
$GLOBALS["RELOAD"]=false;
$GLOBALS["URGENCY"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["PROGRESS_SENDER_DEPENDENT"]=false;
$_GET["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/interface-postfix.log";

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--urgency#",implode(" ",$argv))){$GLOBALS["URGENCY"]=true;}
if(preg_match("#--progress-sender-dependent-relayhosty#",implode(" ",$argv))){$GLOBALS["PROGRESS_SENDER_DEPENDENT"]=true;}

if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
$unix=new unix();

$pidfile="/etc/artica-postfix/".basename(__FILE__)." ". md5(implode("",$argv)).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: ".date("H:i:s")." Postfix configurator already executed PID ". @file_get_contents($pidfile)."\n";die();}
$pid=getmypid();
echo "Starting......: ".date("H:i:s")." Postfix configurator running $pid\n";
file_put_contents($pidfile,$pid);
if($argv[1]=='--wlscreen'){wlscreen();die();}





$users=new usersMenus();
$GLOBALS["CLASS_USERS_MENUS"]=$users;
if(!$users->POSTFIX_INSTALLED){echo("Postfix is not installed\n");die();}


if(!$unix->IS_OPENLDAP_RUNNING()){echo "Starting......: ".date("H:i:s")." Postfix openldap is not running, start it\n";system("/etc/init.d/artica-postfix start ldap");}
if(!$unix->IS_OPENLDAP_RUNNING()){echo "Starting......: ".date("H:i:s")." Postfix openldap is not running, aborting\n";die();}

$ldap=new clladp();
if($ldap->ldapFailed){echo "Starting......: ".date("H:i:s")." Postfix openldap error, aborting\n";die();	}

if(!$ldap->ExistsDN("dc=organizations,$ldap->suffix")){$ldap->BuildOrganizationBranch();}
if(!$ldap->ExistsDN("dc=organizations,$ldap->suffix")){
	echo "Starting......: ".date("H:i:s")." dc=organizations,$ldap->suffix Failed to create Branch\n";
	echo "Starting......: ".date("H:i:s")." dc=organizations,$ldap->suffix no such branch...\n";
	echo "Starting......: ".date("H:i:s")." Postfix openldap is not ready, aborting\n";die();}
echo "Starting......: ".date("H:i:s")." Postfix openldap server success\n";

$q=new mysql();
if(!$q->test_mysql_connection()){echo "Starting......: ".date("H:i:s")." Postfix mysql is not ready aborting...\n";die();}
echo "Starting......: ".date("H:i:s")." Postfix mysql server success\n";

if($argv[1]=='--notifs-templates-force'){postfix_templates();die();}


$GLOBALS["EnablePostfixMultiInstance"]=$sock->GET_INFO("EnablePostfixMultiInstance");
$GLOBALS["EnableBlockUsersTroughInternet"]=$sock->GET_INFO("EnableBlockUsersTroughInternet");
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["postfix"]=$unix->find_program("postfix");
echo "Starting......: ".date("H:i:s")." Postfix bin postfix....: {$GLOBALS["postfix"]}\n";
echo "Starting......: ".date("H:i:s")." Postfix bin postmap....: {$GLOBALS["postmap"]}\n";
echo "Starting......: ".date("H:i:s")." Postfix bin postconf...: {$GLOBALS["postconf"]}\n";



if($argv[1]=='--loadbalance'){haproxy_compliance();ReloadPostfix(true);die();}
if($argv[1]=='--ScanLibexec'){ScanLibexec();die();}
if($argv[1]=='--smtpd-recipient-restrictions'){smtpd_recipient_restrictions();ReloadPostfixSimple(true);die();}



if($argv[1]=='--smtpd-client-restrictions'){
	smtpd_client_restrictions_progress("{starting}",5);
	
	smtpd_client_restrictions_progress("{building_rules}",15);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.acls.php");
	smtpd_client_restrictions_progress("Headers rules",15);
	headers_check(1,1);
	
	if(smtpd_client_restrictions()){
		smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",50);
		smtpd_recipient_restrictions();
		smtpd_client_restrictions_progress("{reloading}",95);
		ReloadPostfix(true);
		smtpd_client_restrictions_progress("{done}",100);
	}
	die();
}



if($argv[1]=='--networks'){build_mynetworks();die();}
if($argv[1]=='--headers-check'){headers_check();die();}
if($argv[1]=='--headers-checks'){headers_check();die();}
if($argv[1]=='--assp'){ASSP_LOCALDOMAINS();die();}
if($argv[1]=='--artica-filter'){MasterCFBuilder(true);die();}
if($argv[1]=='--ldap-branch'){BuildDefaultBranchs();die();}
if($argv[1]=='--ssl'){SMTP_SASL_PROGRESS(true);die();}
if($argv[1]=='--ssl-on'){MasterCFBuilder(true);die();}
if($argv[1]=='--ssl-off'){MasterCFBuilder(true);die();}
if($argv[1]=='--imap-sockets'){imap_sockets();MailBoxTransport();ReloadPostfix(true);die();}
if($argv[1]=='--policyd-reconfigure'){policyd_weight_reconfigure();die();}
if($argv[1]=='--restricted'){RestrictedForInternet(true);die();}
if($argv[1]=='--banner'){smtp_banner(true);die();}


if($argv[1]=='--myhostname'){ CleanMyHostname();ReloadPostfix(true);}
if($argv[1]=='--others-values'){OthersValues_start();}
if($argv[1]=='--mime-header-checks'){mime_header_checks_progress();}
if($argv[1]=='--interfaces'){inet_interfaces();MailBoxTransport();exec("{$GLOBALS["postfix"]} stop");exec("{$GLOBALS["postfix"]} start");ReloadPostfix(true);die();}
if($argv[1]=='--mailbox-transport'){MailBoxTransport();ReloadPostfix(true);die();}
if($argv[1]=='--disable-smtp-sasl'){disable_smtp_sasl();ReloadPostfix(true);die();}
if($argv[1]=='--perso-settings'){perso_settings();HashTables();die();}
if($argv[1]=='--luser-relay'){luser_relay();die();}
if($argv[1]=='--smtp-sender-restrictions'){smtp_cmdline_restrictions();ReloadPostfix(true);die();}
if($argv[1]=='--postdrop-perms'){fix_postdrop_perms();exit;}
if($argv[1]=='--smtpd-restrictions'){smtp_cmdline_restrictions();die();}
if($argv[1]=='--repair-locks'){repair_locks();exit;}
if($argv[1]=='--smtp-sasl'){SMTP_SASL_PROGRESS();exit;}
if($argv[1]=='--memory'){memory();exit;}
if($argv[1]=='--postscreen'){postscreen($argv[2]);ReloadPostfix(true);exit;}
if($argv[1]=='--freeze'){ReloadPostfix(true);exit;}
if($argv[1]=='--body-checks'){BodyChecks();ReloadPostfix(true);exit;}
if($argv[1]=='--amavis-internal'){amavis_internal();ReloadPostfix(true);exit;}
if($argv[1]=='--notifs-templates'){postfix_templates();ReloadPostfix(true);exit;}
if($argv[1]=='--restricted-domains'){restrict_relay_domains();exit;}
if($argv[1]=='--debug-peer-list'){debug_peer_list();ReloadPostfix(true);die();}
if($argv[1]=='--badnettr'){badnettr($argv[2],$argv[3],$argv[4]);ReloadPostfix(true);die();}
if($argv[1]=='--milters'){smtpd_milters();RestartPostix();die();}
if($argv[1]=='--cleanup'){CleanUpMainCf();die();}
if($argv[1]=='--restrictions'){smtpd_recipient_restrictions();ReloadPostfix(true);die();}
if($argv[1]=='--milters-progress'){milters();}


function SEND_PROGRESS($POURC,$text,$error=null){
	$cache="/usr/share/artica-postfix/ressources/logs/web/POSTFIX_COMPILES";
	if($error<>null){echo "Fatal !!!! $error\n";}
	echo "{$POURC}% $text\n";
	
	$array=unserialize(@file_get_contents($cache));
	$array["POURC"]=$POURC;
	$array["TEXT"]=$text;
	if($error<>null){$array["ERROR"][]=$error;}
	@mkdir(dirname($cache),0755,true);
	@file_put_contents($cache, serialize($array));
	@chmod($cache, 0777);
	
}
function build_progress_mime_header($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/HEADER_CHECK";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_othervalues($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postfix.othervalues.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function build_mynetworks(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_othervalues("{starting} {networks}...",15);
	mynetworks();
	build_progress_othervalues("{starting} Mailbox transport...",20);
	MailBoxTransport();
	build_progress_othervalues("{starting} {domains} & {transport}...",25);
	system("$php /usr/share/artica-postfix/exec.postfix.transport.php --pourc=30 --progress-file={$GLOBALS["CACHEFILE"]} --");
	build_progress_othervalues("{reloading}",98);
	ReloadPostfix(true);
	build_progress_othervalues("{done}",100);
	
}



function mime_header_checks_progress(){
	
	build_progress_mime_header("{starting} Mime Header",15);
	mime_header_checks();
	build_progress_mime_header("{starting} Headers",50);
	headers_check();
	build_progress_mime_header("{starting} Body check",60);
	BodyChecks();
	build_progress_mime_header("{reloading}",90);
	ReloadPostfix(true);
	build_progress_mime_header("{done}",100);
	die();
	
	
}

function OthersValues_start(){
	build_progress_othervalues("{starting} OthersValues()...",15);
	OthersValues();
	build_progress_othervalues("Clean my Hostname",80);
	CleanMyHostname();
	build_progress_othervalues("{reloading}",90);
	ReloadPostfix(true);
	build_progress_othervalues("{done}",100);
	die();
}


function milters(){
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	milters_progress("{starting} {filtering_modules}",15);
	milters_progress("{starting} {smtpd_client_restrictions}",20);
	
	smtpd_client_restrictions();
	
	milters_progress("{checking} Anti-Spam",25);
	amavis_internal();
	shell_exec("$php /usr/share/artica-postfix/exec.spamassassin.php");
	milters_progress("{checking} {milters_plugins}",30);
	smtpd_milters();
	milters_progress("{checking} MASTER CF",40);
	MasterCFBuilder(true);
	
	$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
	$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));
	$MilterGreyListEnabled=intval($sock->GET_INFO("MilterGreyListEnabled"));
	echo "SpamAssassin Milter: $SpamAssMilterEnabled\n";
	echo "Regex Milter.......: $EnableMilterRegex\n";
	echo "Greylist Milter....: $MilterGreyListEnabled\n";
	
	if($SpamAssMilterEnabled==1){
		$php=$unix->LOCATE_PHP5_BIN();
		milters_progress("{checking} {APP_SPAMASS_MILTER}",41);
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --milter-spamass");
		milters_progress("{starting} {APP_SPAMASS_MILTER}",42);
		system("/etc/init.d/spamass-milter restart");
		milters_progress("{starting} {APP_SPAMASS_MILTER}",43);
		system("/etc/init.d/spamassassin restart");
		
	}
	
	if($EnableMilterRegex==1){
		$php=$unix->LOCATE_PHP5_BIN();
		milters_progress("{checking} {milter_regex}",45);
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --milter-regex");
		milters_progress("{starting} {milter_regex}",46);
		system("/etc/init.d/milter-regex restart");
	}
	
	if($MilterGreyListEnabled==1){
		$php=$unix->LOCATE_PHP5_BIN();
		milters_progress("{restarting} GreyList",47);
		system("/etc/init.d/milter-greylist restart");
	}
	milters_progress("{reloading}",90);
	ReloadPostfix(true);
	milters_progress("{done}",100);
	
}

if($argv[1]=='--reconfigure'){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/postfix.reconfigure2.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: reconfigure2: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		die();
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".reconfigure.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		die();
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	if($GLOBALS["EnablePostfixMultiInstance"]==1){
		shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --from-main-reconfigure");
	}
	
	$t1=time();
	$main=new main_cf();
	SEND_PROGRESS(2,"Writing mainc.cf...");
	$main->save_conf_to_server(1);
	SEND_PROGRESS(4,"Writing mainc.cf done...");
	if(!is_file("/etc/postfix/hash_files/header_checks.cf")){@file_put_contents("/etc/postfix/hash_files/header_checks.cf","#");}
	
	SEND_PROGRESS(5,"Building all settings...");
	_DefaultSettings();
	HashTables();
	$unix->send_email_events("Postfix: postfix compilation done. Took :".$unix->distanceOfTimeInWords($t1,time()), "No content yet...\nShould be an added feature :=)", "postfix");
	SEND_PROGRESS(100,"Configuration done");
	die();
}




_DefaultSettings();



function smtp_cmdline_restrictions(){
		
		
		
	    $sock=new sockets();
	    $disable_vrfy_command=$sock->GET_INFO("disable_vrfy_command");
	    if(!is_numeric($disable_vrfy_command)){$disable_vrfy_command=0;}
	    if($disable_vrfy_command==1){postconf("disable_vrfy_command","yes");}else{postconf("disable_vrfy_command","no");}
	
	
		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_recipient_restrictions() function\n ***\n";}
		smtpd_recipient_restrictions();
		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_client_restrictions() function\n ***\n";}
		smtpd_client_restrictions();
		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_sender_restrictions() function\n ***\n";}
		smtpd_sender_restrictions();
		
		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_data_restrictions() function\n ***\n";}
		smtpd_data_restrictions();
		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_end_of_data_restrictions() function\n ***\n";}
		smtpd_end_of_data_restrictions();
		if($GLOBALS["RELOAD"]){
			if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> ReloadPostfix() function\n ***\n";}
			ReloadPostfix(true);
			
		}	
		HashTables();
	
}

function smtpd_data_restrictions(){
	include_once(dirname(__FILE__)."/ressources/class.smtp_data_restrictions.inc");
	$smtpd_data_restrictions=new smtpd_data_restrictions("master");
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix -> smtpd_data_restrictions->compile() function\n";}
	$smtpd_data_restrictions->compile();
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix -> compiled \"$smtpd_data_restrictions->restriction_final\"\n";}
	if($smtpd_data_restrictions->restriction_final<>null){
		postconf("smtpd_data_restrictions",$smtpd_data_restrictions->restriction_final);
	}
}

function HashTables($start=0){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	shell_exec("$php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --pourc=$start");
}

function _DefaultSettings(){
if($GLOBALS["EnablePostfixMultiInstance"]==1){shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --from-main-null");return;}

	shell_exec("{$GLOBALS["postconf"]} -e 'debug_peer_level = 2' >/dev/null 2>&1");

	$start=5;
	$functions=array("CleanUpMainCf","debug_peer_list",
		"cleanMultiplesInstances","SetTLS","inet_interfaces","imap_sockets","MailBoxTransport","mynetworks",
		"headers_check","mime_header_checks","smtpd_recipient_restrictions","smtpd_client_restrictions_clean",
		"smtpd_client_restrictions","smtpd_sasl_exceptions_networks","sender_bcc_maps","CleanMyHostname","OthersValues","luser_relay",
		"smtpd_sender_restrictions"	,"smtpd_end_of_data_restrictions","perso_settings","remove_virtual_mailbox_base","postscreen",
		"smtp_sasl_security_options","smtp_sasl_auth_enable","SetSALS","BodyChecks","postfix_templates","haproxy_compliance","smtpd_milters",
		"MasterCFBuilder","ReloadPostfix"
			
			
	);
	
	$tot=count($functions);
	$i=0;
	while (list ($num, $func) = each ($functions) ){
		$i++;
		$start++;
		if(!function_exists($func)){
			SEND_PROGRESS($start,$func,"Error $func no such function...");
			continue;
		}
			
			
		try {
			SEND_PROGRESS($start,"Action 1, {$start}% Please wait, executing $func() $i/$tot..");
			call_user_func($func);
		} catch (Exception $e) {
			SEND_PROGRESS($start,$func,"Error on $func ($e)");
		}			
	}
	
	
	
	
	
	if($GLOBALS["URGENCY"]){
		$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php >/dev/null 2>&1 &");
	}else{
		HashTables($start);
	}
	
}



if($argv[1]=='--write-maincf'){
	$unix=new unix();
	if($GLOBALS["EnablePostfixMultiInstance"]==1){shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --from-main-write-maincf");return;}
	echo "Starting......: ".date("H:i:s")." Postfix Postfix Multi Instance disabled, single instance mode\n";
	$main=new main_cf();
	$main->save_conf_to_server(1);
	file_put_contents('/etc/postfix/main.cf',$main->main_cf_datas);
	echo "Starting......: ".date("H:i:s")." Postfix Building main.cf ". strlen($main->main_cf_datas). "line ". __LINE__." bytes done\n";
	if(!is_file("/etc/postfix/hash_files/header_checks.cf")){@file_put_contents("/etc/postfix/hash_files/header_checks.cf","#");}
	_DefaultSettings();
	perso_settings();
	if($argv[2]=='no-restart'){appliSecu();die();}
	echo "Starting......: ".date("H:i:s")." restarting postfix\n";
	$unix->send_email_events("Postfix will be restarted","Line: ". __LINE__."\nIn order to apply new configuration file","postfix");
	shell_exec("/etc/init.d/postfix restart-single");
	HashTables();
	die();
}

if($argv[1]=='--maincf'){
	if($GLOBALS["EnablePostfixMultiInstance"]==1){shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --from-main-maincf");return;}	
	$main=new main_cf();
	$main->save_conf_to_server(1);
	file_put_contents('/etc/postfix/main.cf',$main->main_cf_datas);
	_DefaultSettings();
	perso_settings();
	if($GLOBALS["DEBUG"]){echo @file_get_contents("/etc/postfix/main.cf");}
	HashTables();
	die();
}





function ASSP_LOCALDOMAINS(){
	if($GLOBALS["EnablePostfixMultiInstance"]==1){return null;}
	if(!is_dir("/usr/share/assp/files")){return null;}
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();
	while (list ($num, $ligne) = each ($domains) ){
		$conf=$conf."$ligne\n";
	}
	echo "Starting......: ".date("H:i:s")." ASSP ". count($domains)." local domains\n"; 
	@file_put_contents("/usr/share/assp/files/localdomains.txt",$conf);
	HashTables();
	
}

function SetSASLMech(){
	$sock=new sockets();
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$echo=$unix->find_program("echo");
	$saslpasswd2=$unix->find_program("saslpasswd2");
	$EnableMechLogin=$sock->GET_INFO("EnableMechLogin");
	$EnableMechPlain=$sock->GET_INFO("EnableMechPlain");
	$EnableMechDigestMD5=intval($sock->GET_INFO("EnableMechDigestMD5"));
	$EnableMechCramMD5=intval($sock->GET_INFO("EnableMechCramMD5"));
	
	if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
	if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}
	$mech_list=array();
	if($EnableMechPlain==1){$mech_list[]="PLAIN";}
	if($EnableMechLogin==1){$mech_list[]="LOGIN";}
	if($EnableMechDigestMD5==1){$mech_list[]="DIGEST-MD5";}
	if($EnableMechCramMD5==1){$mech_list[]="CRAM-MD5";}

	if(count($mech_list==0)){
		$mech_list[]="PLAIN";
		$mech_list[]="LOGIN";
	}
	$mech_list_text=@implode(" ", $mech_list);
	
	echo "Starting......: ".date("H:i:s")." authentication mechanisms $mech_list_text\n";
	
	
	$f[]="pwcheck_method: saslauthd";
	$f[]="mech_list: $mech_list_text";
	$f[]="log_level: 5";
	echo "Starting......: ".date("H:i:s")." Creating /etc/postfix/sasl/smtpd.conf\n";
	
	@mkdir("/etc/postfix/sasl",0755,true);
	@file_put_contents("/etc/postfix/sasl/smtpd.conf", @implode("\n", $f));
	if(!is_file("/usr/lib/sasl2/smtpd.conf")){
		system("$ln -s /etc/postfix/sasl/smtpd.conf  /usr/lib/sasl2/smtpd.conf");
		
	}
	if(!is_file("$saslpasswd2")){
		echo "Starting......: ".date("H:i:s")." saslpasswd2 doesn''t exists!!!\n";
		return;
	}
	
	if(!is_dir("/var/spool/postfix/etc")){
		echo "Starting......: ".date("H:i:s")." Creating /var/spool/postfix/etc\n";
		@mkdir("/var/spool/postfix/etc",0755,true);
	}
	
	if(!is_file("/var/spool/postfix/etc/sasldb2")){
		echo "Starting......: ".date("H:i:s")." Creating /var/spool/postfix/etc/sasldb2 doesn't exists, create it\n";
		system("$echo cyrus|$saslpasswd2 -c cyrus");
	}
	
	if(is_file("/etc/sasldb2")){
		@file_put_contents("/var/spool/postfix/etc/sasldb2", @file_get_contents("/etc/sasldb2"));
		
	}

	$unix->chown_func("root","root","/var/spool/postfix/etc/sasldb2");
	@chmod("/var/spool/postfix/etc/sasldb2", 0755);

	
}

function SetSSL(){
	$sock=new sockets();
	$unix=new unix();
	$main=new main_cf();
	
	if($main->main_array["smtpd_tls_session_cache_timeout"]==null){$main->main_array["smtpd_tls_session_cache_timeout"]='3600s';}
	$PostfixEnableMasterCfSSL=intval($sock->GET_INFO("PostfixEnableMasterCfSSL"));
	$smtpd_tls_security_level=$sock->GET_INFO("smtpd_tls_security_level");
	$cert=new postfix_certificate($PostFixMasterCertificate);
	echo "Starting......: ".date("H:i:s")." Certificate $PostFixMasterCertificate\n";
	if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}
	
	$cert->build();
	$unix->chown_func("postfix","postfix","/etc/ssl/certs/postfix/*");
	
	if($PostfixEnableMasterCfSSL==1){
		postconf("smtpd_tls_security_level" ,$smtpd_tls_security_level);
		postconf("smtpd_tls_session_cache_timeout" ,$main->main_array["smtpd_tls_session_cache_timeout"]);
		postconf("smtpd_tls_session_cache_database" ,"btree:/var/lib/postfix/smtpd_tls_cache");
		postconf("smtpd_use_tls" ,"yes");
		
	}else{	
		postconf("smtpd_use_tls","no");
		postconf("smtpd_tls_security_level" ,"none");
		postconf("smtpd_tls_key_file",null);
		postconf("smtpd_tls_cert_file",null);
		postconf("smtpd_tls_CAfile",null);
	}
	
	
}


function SetSALS(){
	$unix=new unix();
	$sock=new sockets();
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();
	$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$PostFixSmtpSaslEnable=$sock->GET_INFO("PostFixSmtpSaslEnable");
	$PostFixMasterCertificate=$sock->GET_INFO("PostFixMasterCertificate");
	$PostfixEnableMasterCfSSL=intval($sock->GET_INFO("PostfixEnableMasterCfSSL"));
	$smtpd_tls_auth_only=intval($sock->GET_INFO("smtpd_tls_auth_only"));
	$smtpd_sasl_path=$sock->GET_INFO("smtpd_sasl_path");
	SetSSL();
	
	if($PostFixSmtpSaslEnable==1){
		@mkdir("/var/lib/postfix",0755,true);
		chown("/var/lib/postfix","postfix");
		chgrp("/var/lib/postfix", "postfix");
		echo "Starting......: ".date("H:i:s")." SASL authentication is enabled\n";
		
		if($PostfixEnableMasterCfSSL==1){
			if($smtpd_tls_auth_only==1){
				postconf("smtpd_tls_auth_only" ,"yes");
			}else{
				postconf("smtpd_tls_auth_only" ,"no");
			}
			
		}

		if($smtpd_sasl_path==null){$smtpd_sasl_path="smtpd";}
		$cmd["smtpd_sasl_auth_enable"]="yes";
		$cmd["smtpd_sasl_path"]="smtpd";
		$cmd["smtpd_sasl_authenticated_header"]="yes";
		$cmd["smtpd_delay_reject"]="yes";
		$cmd["cyrus_sasl_config_path"]="/etc/postfix/sasl";
		echo "Starting......: ".date("H:i:s")." SASL authentication running ". count($cmd)." commands\n";
		
		
		while (list ($num, $ligne) = each ($cmd) ){
			postconf($num,$ligne);
			
		}
		
	}else{
		echo "Starting......: ".date("H:i:s")." SASL authentication is disabled\n";
		postconf("smtpd_sasl_auth_enable","no");
		postconf("smtpd_sasl_authenticated_header","no");
		postconf("smtpd_tls_auth_only" ,"no");
		

	}
	

	

}



function BodyChecks(){
	
	
	
	$main=new maincf_multi("master","master");
	$datas=$main->body_checks();
	if($datas<>null){
		if(preg_match("#(.+?)=(.+)#", $datas,$re)){$datas=$re[2];}
		postconf("body_checks",$datas);
		
		
		
	}else{
		postconf("body_checks",null);
	}
	
}

function smtp_sasl_security_options(){
	$f=array();
	$main=new maincf_multi("master","master");
	$datas=unserialize($main->GET_BIGDATA("smtp_sasl_security_options"));
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if($datas["noanonymous"]==1){$f[]="noanonymous";}
	if($datas["noplaintext"]==1){$f[]="noplaintext";}
	if($datas["nodictionary"]==1){$f[]="nodictionary";}
	if($datas["mutual_auth"]==1){$f[]="mutual_auth";}
	if(count($f)==0){$f[]="noanonymous";}
	postconf("smtp_sasl_security_options",@implode(", ",$f));
	postconf("smtp_sasl_tls_security_options",@implode(", ",$f));
	postconf("smtpd_delay_reject","yes");	

	$EnableMechSMTPCramMD5=$sock->GET_INFO("EnableMechSMTPCramMD5");
	$EnableMechSMTPDigestMD5=$sock->GET_INFO("EnableMechSMTPDigestMD5");
	$EnableMechSMTPLogin=$sock->GET_INFO("EnableMechSMTPLogin");
	$EnableMechSMTPPlain=$sock->GET_INFO("EnableMechSMTPPlain");
	if(!is_numeric($EnableMechSMTPCramMD5)){$EnableMechSMTPCramMD5=1;}
	if(!is_numeric($EnableMechSMTPDigestMD5)){$EnableMechSMTPDigestMD5=1;}
	if(!is_numeric($EnableMechSMTPLogin)){$EnableMechSMTPLogin=1;}
	if(!is_numeric($EnableMechSMTPPlain)){$EnableMechSMTPPlain=1;}	
	
	if($EnableMechSMTPLogin==1){$d[]="login";}
	if($EnableMechSMTPPlain==1){$d[]="plain";}
	if($EnableMechSMTPDigestMD5==1){$d[]="digest-md5";}
	if($EnableMechSMTPCramMD5==1){$d[]="cram-md5";}
	$EnableMechSMTPText=$sock->GET_INFO("EnableMechSMTPText");
	if($EnableMechSMTPText==null){$d[]="!gssapi, !external, static:all";}else{$d[]=$EnableMechSMTPText;}	
	postconf("smtp_sasl_mechanism_filter",@implode(", ",$d));
	 
	
}




function SetTLS(){
	
	$main=new maincf_multi("master","master");
	
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$smtpd_tls_security_level=trim($sock->GET_INFO('smtpd_tls_security_level'));
	if($smtpd_tls_security_level<>null){
		shell_exec("{$GLOBALS["postconf"]} -e \"smtpd_tls_security_level = $smtpd_tls_security_level\" >/dev/null 2>&1");
	}
	
	if($sock->GET_INFO('smtp_sender_dependent_authentication')==1){
		postconf("smtp_sender_dependent_authentication","yes");
		postconf("smtp_sasl_auth_enable","yes");
	
	}
	
	$broken_sasl_auth_clients=$main->GET("broken_sasl_auth_clients");
	$smtpd_tls_auth_only=$main->GET("smtpd_tls_auth_only");
	$smtpd_sasl_authenticated_header=$main->GET("smtpd_sasl_authenticated_header");
	$smtpd_tls_received_header=$main->GET("smtpd_tls_received_header");
	$smtpd_tls_security_level=$main->GET("smtpd_tls_security_level");
	$smtpd_sasl_security_options=$main->GET("smtpd_sasl_security_options");
	
	if(!is_numeric($broken_sasl_auth_clients)){$broken_sasl_auth_clients=1;}
	if(!is_numeric($smtpd_sasl_authenticated_header)){$smtpd_sasl_authenticated_header=1;}
	if(!is_numeric($smtpd_tls_auth_only)){$smtpd_tls_auth_only=0;}
	if(!is_numeric($smtpd_tls_received_header)){$smtpd_tls_received_header=1;}
	if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}
	if($smtpd_sasl_security_options==null){$smtpd_sasl_security_options="noanonymous";}
	
	
	
	postconf("broken_sasl_auth_clients",$main->YesNo($broken_sasl_auth_clients));
	postconf("smtpd_sasl_local_domain",$main->GET("smtpd_sasl_local_domain"));
	postconf("smtpd_sasl_authenticated_header",$main->YesNo($smtpd_sasl_authenticated_header));
	postconf("smtpd_tls_security_level",$smtpd_tls_security_level);
	postconf("smtpd_tls_auth_only",$main->YesNo($smtpd_tls_auth_only));
	postconf("smtpd_tls_received_header",$main->YesNo($smtpd_tls_received_header));
	postconf("smtpd_sasl_security_options",$smtpd_sasl_security_options);
}

function mynetworks(){
	
	if($GLOBALS["EnablePostfixMultiInstance"]==1){
		echo "Starting......: ".date("H:i:s")." Building mynetworks multiple-instances, enabled\n";
		postconf("mynetworks","127.0.0.0/8");
		shell_exec(LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.exec.postfix-multi.php --reload-all");
		return;
	}
	
	$sock=new sockets();
	$MynetworksInISPMode=$sock->GET_INFO("MynetworksInISPMode");
	if(!is_numeric($MynetworksInISPMode)){$MynetworksInISPMode=0;}	
	$dbfile="/etc/artica-postfix/settings/Daemons/PostfixBadNettr";
	$ArrayBadNets=unserialize(base64_decode(@file_get_contents($dbfile)));
	
	
	if($MynetworksInISPMode==1){
		echo "Starting......: ".date("H:i:s")." Building mynetworks ISP Mode enabled\n";
		postconf("mynetworks","127.0.0.0/24, 127.0.0.0/8, 127.0.0.1");
		return;	
	}
	
	$ldap=new clladp();
	$nets=$ldap->load_mynetworks();
	if(!is_array($nets)){
		if($GLOBALS["DEBUG"]){echo "No networks sets\n";}
		postconf("mynetworks","127.0.0.0/8");
		return;
	}
	$nets[]="127.0.0.0/8";

	while (list ($num, $network) = each ($nets) ){$cleaned[$network]=$network;}
	unset($nets);
	while (list ($network, $network2) = each ($cleaned) ){
		$network=trim($network);
		if(isset($ArrayBadNets[$network])){	
			if($ArrayBadNets[$network]==0){continue;}
			if($ArrayBadNets[$network]<>null){$nets[]=$ArrayBadNets[$network];continue;}
		}
		$nets[]=$network;
	}
	
	
	
	$inline=@implode(", ",$nets);
	$inline=str_replace(',,',',',$inline);
	$config_net=@implode("\n",$nets);
	echo "Starting......: ".date("H:i:s")." Postfix Building mynetworks ". count($nets)." Networks ($inline)\n";
	@file_put_contents("/etc/artica-postfix/mynetworks",$config_net);
	postconf("mynetworks",$inline);
}


function badnettr($instance,$badentry,$goodentry){
	
	$dbfile="/etc/artica-postfix/settings/Daemons/PostfixBadNettr";
	$array=unserialize(base64_decode(@file_get_contents($dbfile)));
	$array[trim($badentry)]=trim($goodentry);
	@file_put_contents($dbfile, base64_encode(serialize($array)));	
	
	if($instance=="master"){mynetworks();return;}
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 ".dirname(__FILE__)."/exec.postfix-multi.php --instance-reconfigure $instance >/dev/null 2>&1 &");
	die();
}

function remove_virtual_mailbox_base(){
	$f=@explode("\n",@file_get_contents("/etc/postfix/main.cf"));
	$found=false;
	while (list ($num, $line) = each ($f) ){
		if(preg_match("#virtual_mailbox_base#",$line)){
			echo "Starting......: ".date("H:i:s")." Postfix remove virtual_mailbox_base entry\n";
			unset($f[$line]);
			$found=true;
		}
		
	}
	if($found){@file_put_contents("/etc/postfix/main.cf",@implode("\n",$f));}
	
}

function headers_check($noreload=0,$nowhiteblack=0){
	$unix=new unix();
	$headersFiles=array();
	$main=new maincf_multi("master","master");
	echo "Starting......: ".date("H:i:s")." Loading header_checks()\n";
	$headers=$main->header_checks();
	$headers=str_replace("header_checks =","",$headers); 
	
	if(is_file("/etc/postfix/blacklist.headers.cf")){
		$headersFiles[]="regexp:/etc/postfix/blacklist.headers.cf";
	}
	
	if($headers<>null){
		$headersFiles[]=$headers;
	}
	

		
	if(count($headersFiles)>0){
		postconf("header_checks",@implode(", ", $headersFiles));
	}else{
		postconf("header_checks",null);
	}
	
	
	if($nowhiteblack==0){
		$nohup=$unix->find_program("nohup");
		echo "Starting......: ".date("H:i:s")." Running exec.white-black-central.php\n";
		system("$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.white-black-central.php >/dev/null 2>&1 &" );
	}
	if($noreload==0){
		echo "Starting......: ".date("H:i:s")." Reloading Postfix...\n";
		ReloadPostfix(true);
	}
}

function buildtables_background(){
	$unix=new unix();	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	system("$php5 /usr/share/artica-postfix/exec.postfix.hashtables.php");
}

function RestartPostix(){
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	if(is_file($postfix)){shell_exec("$postfix stop >/dev/null 2>&1");}
	if(is_file($postfix)){shell_exec("$postfix start >/dev/null 2>&1");}
}

function ReloadPostfixSimple(){
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	if(is_file($postfix)){shell_exec("$postfix reload >/dev/null 2>&1");return;}
}

function ReloadPostfix($nohastables=false){
	$ldap=new clladp();
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$myOrigin=null;
	$dom=array();
	
	
	$myOrigin=$unix->hostname_g();
	
	
	if($myOrigin==null){
		$domains=$ldap->Hash_domains_table();
		if(count($domains)>0){while (list ($num, $ligne) = each ($domains) ){$dom[]=$num;}$myOrigin=$dom[0];}
	}
	
	
	if($myOrigin==null){$myOrigin="localhost.localdomain";}
	$postfix=$unix->find_program("postfix");
	$daemon_directory=$unix->LOCATE_POSTFIX_DAEMON_DIRECTORY();
	echo "Starting......: ".date("H:i:s")." Postfix daemon directory \"$daemon_directory\"\n";
	postconf("daemon_directory",$daemon_directory);
	
	
	if($myOrigin==null){$myOrigin="localhost.localdomain";}
	
	if(!$nohastables){
		echo "Starting......: ".date("H:i:s")." Postfix launch datases compilation...\n";
		buildtables_background();
	}
	
	postconf("myorigin","$myOrigin");
	postconf("smtpd_delay_reject","yes");
	$main=new maincf_multi("master","master");
	$freeze_delivery_queue=$main->GET("freeze_delivery_queue");
	if($freeze_delivery_queue==1){
		postconf("master_service_disable","qmgr.fifo");
		postconf("in_flow_delay","0");
	}else{
		postconf("master_service_disable","");
		$in_flow_delay=$main->GET("in_flow_delay");
		if($in_flow_delay==null){$in_flow_delay="1s";}
		postconf("in_flow_delay",$in_flow_delay);		
	}
	
	
	
	postconf_strip_key();
	
	echo "Starting......: ".date("H:i:s")." Postfix Apply securities issues\n"; 
	appliSecu();
	echo "Starting......: ".date("H:i:s")." Postfix Reloading ASSP\n"; 
	system("/usr/share/artica-postfix/bin/artica-install --reload-assp");
	echo "Starting......: ".date("H:i:s")." Postfix reloading postfix master with \"$postfix\"\n";
	ScanLibexec();
	CleanUpMainCf();
	if(is_file($postfix)){shell_exec("$postfix reload >/dev/null 2>&1");return;}
	
	
	
}

function appliSecu(){
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	echo "Starting......: ".date("H:i:s")." Postfix verify permissions...\n"; 
	if(is_file("/var/lib/postfix/smtpd_tls_session_cache.db")){shell_exec("/bin/chown postfix:postfix /var/lib/postfix/smtpd_tls_session_cache.db");}
	if(is_file("/var/lib/postfix/master.lock")){@chown("/var/lib/postfix/master.lock","postfix");}
	if(is_dir("/var/spool/postfix/pid")){@chown("/var/spool/postfix/pid", "root");}
	if(is_file("/usr/sbin/postqueue")){
		@chgrp("/usr/sbin/postqueue", "postdrop");
		@chmod("/usr/sbin/postqueue",0755);
		shell_exec("$chmod g+s /usr/sbin/postqueue");
 		
	}
	if(is_file("/usr/sbin/postdrop")){
		@chgrp("/usr/sbin/postdrop", "postdrop");
		@chmod("/usr/sbin/postdrop",0755);
		shell_exec("$chmod g+s /usr/sbin/postdrop");
	}
	if(is_dir("/var/spool/postfix/public")){@chgrp("/var/spool/postfix/public", "postdrop");}
	if(is_dir("/var/spool/postfix/maildrop")){@chgrp("/var/spool/postfix/maildrop", "postdrop");}
	echo "Starting......: ".date("H:i:s")." Postfix verify permissions done\n";
	
	
	
}


function cleanMultiplesInstances(){
	foreach (glob("/etc/postfix-*",GLOB_ONLYDIR ) as $dirname) {
	    echo "Starting......: ".date("H:i:s")." Postfix removing old instance ". basename($dirname)."\n";
	    shell_exec("/bin/rm -rf $dirname");
	}
	postconf("multi_instance_directories",null);
	
}


	
	
function BuildDefaultBranchs(){
	
	$main=new main_cf();
	$main->BuildDefaultWhiteListRobots();
	
	$sender=new sender_dependent_relayhost_maps();
	
	if($GLOBALS["RELOAD"]){
		$unix=new unix();
		$postfix=$unix->find_program("postfix");
		shell_exec("$postfix stop && $postfix start");
	}
}



function imap_sockets(){
	return;
	
}

function policyd_weight_reconfigure(){
	$pol=new policydweight();
	$conf=$pol->buildConf();
	@file_put_contents("/etc/artica-postfix/settings/Daemons/PolicydWeightConfig",$conf);
	echo "Starting......: ".date("H:i:s")." policyd-weight building first config done\n";
}

function mime_header_checks(){
	$f=array();
	$main=new maincf_multi("master","master");
	$enable_attachment_blocking_postfix=$main->GET("enable_attachment_blocking_postfix");
	if(!is_numeric($enable_attachment_blocking_postfix)){$enable_attachment_blocking_postfix=0;}
	$extmime=$main->mime_header_checks();
	$extmime=trim(str_replace("mime_header_checks =","",$extmime)); 	
	
	if($enable_attachment_blocking_postfix==1){
		$sql=new mysql();
		$sql="SELECT * FROM smtp_attachments_blocking WHERE ou='_Global' ORDER BY IncludeByName";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$q=new mysql();
		writelogs("-> Qyery",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("Error mysql $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return null;}
			
		writelogs("-> loop",__FUNCTION__,__FILE__,__LINE__);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["IncludeByName"]==null){continue;}
			$f[]=$ligne["IncludeByName"];
			
		}

	}else{
		echo "Starting......: ".date("H:i:s")." Blocking extensions trough postfix is disabled\n";
	}
	
	
	if(count($f)==0){
		echo "Starting......: ".date("H:i:s")." No extensions blocked\n";
		if($extmime<>null){postconf("mime_header_checks",$extmime);}
		postconf("mime_header_checks",null);
		return;
	}
	
	$strings=implode("|",$f);
	echo "Starting......: ".date("H:i:s")." ". count($f)." extensions blocked\n";
	$pattern[]="/^\s*Content-(Disposition|Type).*name\s*=\s*\"?(.+\.($strings))\"?\s*$/\tREJECT file attachment types is not allowed. File \"$2\" has the unacceptable extension \"$3\"";
	$pattern[]="";
	@file_put_contents("/etc/postfix/mime_header_checks",implode("\n",$pattern));
	if($extmime<>null){$extmime=",$extmime";}
	postconf("mime_header_checks","regexp:/etc/postfix/mime_header_checks$extmime");
	echo "Starting......: ".date("H:i:s")." mime_header_checks() done\n";
}

function smtp_sasl_auth_enable(){
	$ldap=new clladp();
	if($ldap->ldapFailed){
		echo "Starting......: ".date("H:i:s")." SMTP SALS connection to ldap failed\n";
		return;
	}

	$suffix="dc=organizations,$ldap->suffix";
	$filter="(&(objectclass=SenderDependentSaslInfos)(SenderCanonicalRelayPassword=*))";
	$res=array();
	$search = @ldap_search($ldap->ldap_connection,$suffix,"$filter",array());
	$count=0;		
	if ($search) {
			$hash=ldap_get_entries($ldap->ldap_connection,$search);	
			$count=$hash["count"];
		}
	
	echo "Starting......: ".date("H:i:s")." SMTP SALS $count account(s)\n"; 	
	if($count>0){
		postconf("smtp_sasl_auth_enable","yes");
		postconf("smtp_sender_dependent_authentication","yes");
		
		
	}else{
		postconf("smtp_sender_dependent_authentication","no");
		
	}

}

function smtpd_client_restrictions_clean(){
	$f=@explode("\n",@file_get_contents("/etc/postfix/main.cf"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#smtpd_client_restrictions_#",$ligne)){continue;}
		if(preg_match("#smtpd_helo_restrictions_#",$ligne)){continue;}
		if(preg_match("#check_client_access ldap_#",$ligne)){continue;}
		$ligne=str_replace("check_client_access ldap:smtpd_client_restrictions_check_client_access","",$ligne);
		$ligne=str_replace("main.cf=\'my_domain\'=","",$ligne);
		
		$newarray[]=$ligne;
		
	}
	@file_put_contents("/etc/postfix/main.cf",@implode("\n",$newarray));
	
}

function smtpd_client_restrictions_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";

	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function milters_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/smtpd_milters";

	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function smtpd_client_restrictions(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	
	exec("{$GLOBALS["postconf"]} -h smtpd_client_restrictions",$datas);
	$tbl=explode(",",implode(" ",$datas));
	
	
	echo "Old values = $datas\n";
	
	
	$EnablePostfixAntispamPack=$sock->GET_INFO("EnablePostfixAntispamPack");
	$EnableArticaPolicyFilter=$sock->GET_INFO("EnableArticaPolicyFilter");
	$EnableArticaPolicyFilter=0;
	$EnableAmavisInMasterCF=$sock->GET_INFO('EnableAmavisInMasterCF');
	$EnableAmavisDaemon=$sock->GET_INFO('EnableAmavisDaemon');		
	$amavis_internal=null;
	$newHash=array();
	smtpd_client_restrictions_progress("{cleaning_data}",10);
	
	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
		$ligne=trim($ligne);
		if(trim($ligne)==null){continue;}
		if($ligne=="Array"){continue;}
		$newHash[$ligne]=$ligne;
		}
	}

	$hashToDelete[]="check_client_access hash:/etc/postfix/check_client_access";
	$hashToDelete[]="check_client_access \"hash:/etc/postfix/postfix_allowed_connections\"";
	$hashToDelete[]="check_client_access hash:/etc/postfix/postfix_allowed_connections";
	$hashToDelete[]="check_client_access pcre:/etc/postfix/fqrdns.pcre";
	$hashToDelete[]="check_reverse_client_hostname_access pcre:/etc/postfix/fqrdns.pcre";
	
	$hashToDelete[]="reject_unknown_reverse_client_hostname";
	$hashToDelete[]="reject_unknown_client_hostname";
	$hashToDelete[]="reject_non_fqdn_hostname";
	$hashToDelete[]="reject_unknown_sender_domain";
	$hashToDelete[]="reject_non_fqdn_sender";
	$hashToDelete[]="reject_unauth_pipelining";
	$hashToDelete[]="reject_invalid_hostname";
	$hashToDelete[]="reject_unknown_client_hostname";
	$hashToDelete[]="reject_unknown_reverse_client_hostname";
	$hashToDelete[]="reject_invalid_hostname";
	$hashToDelete[]="reject_rbl_client zen.spamhaus.org";
	$hashToDelete[]="reject_rbl_client sbl.spamhaus.org";
	$hashToDelete[]="reject_rbl_client cbl.abuseat.org";
	$hashToDelete[]="reject_unauth_pipelining";
	$hashToDelete[]="reject_unauth_pipelining";
	$hashToDelete[]="reject_rbl_client=zen.spamhaus.org";
	$hashToDelete[]="reject_rbl_client=sbl.spamhaus.org";
	$hashToDelete[]="reject_rbl_client=sbl.spamhaus.org";
	$hashToDelete[]="permit_sasl_authenticated";
	$hashToDelete[]="check_client_access hash:/etc/postfix/amavis_internal";
	$hashToDelete[]="check_client_access cidr:/etc/postfix/acls.cdir.cf";
	$hashToDelete[]="check_client_access hash:/etc/postfix/blacklist.domains.cf";
	$hashToDelete[]="check_recipient_access hash:/etc/postfix/check_recipient_access_ou";
	
	while (list ($num, $ligne) = each ($hashToDelete) ){
		if(isset($newHash[$ligne])){unset($newHash[$ligne]);}
	}

	if(is_file("/etc/postfix/acls.cdir.cf")){
		$newHash["check_client_access cidr:/etc/postfix/acls.cdir.cf"]="check_client_access cidr:/etc/postfix/acls.cdir.cf";
	}
	
	if(is_file("/etc/postfix/blacklist.domains.cf.db")){
		$newHash["check_client_access hash:/etc/postfix/blacklist.domains.cf"]="check_client_access hash:/etc/postfix/blacklist.domains.cf";
	}
	
	if($GLOBALS["VERBOSE"]){
		echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: origin:".@implode(",",$newHash)."\n";
	}
	
	$main=new maincf_multi("master","master");
	$check_client_access=$main->check_client_access();
	
	if(strpos($check_client_access, ",")>0){
		$check_client_accessEX=explode(",",$check_client_access);
		$check_client_access=null;
		while (list ($num, $ligne) = each ($check_client_accessEX) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$newHash[$ligne]=$ligne;
		}
	}
	
	if($check_client_access<>null){
		$newHash[$check_client_access]=$check_client_access;
	}
	$smtpd_client_restrictions=array();
	
		if(count($newHash)>0){	
			while (list ($num, $ligne) = each ($newHash) ){
				echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: Checks \"$ligne\"\n";
				if(preg_match("#(hash|cidr):(.+)$#",$ligne,$re)){
					$path=trim($re[2]);
					if(!is_file($path)){
						echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: bungled \"$ligne\"\n"; 
						continue;
					}
					$smtpd_client_restrictions[]=$ligne;
					continue;
				}
				
				if(preg_match("#reject_rbl_client=(.+?)$#",$ligne,$re)){
					$rbl=trim($re[1]);
						echo "Starting......: ".date("H:i:s")." reject_rbl_client: bungled \"$ligne\" fix it\n"; 
						$num="reject_rbl_client $rbl";
						continue;
					}
					
			$smtpd_client_restrictions[]=$ligne;
			
			}			
			
	}
	
	
	$smtpd_client_restrictions[]="check_recipient_access hash:/etc/postfix/check_recipient_access_ou";
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/reject_unknown_client_hostname")){
	@file_put_contents("/etc/artica-postfix/settings/Daemons/reject_unknown_client_hostname", 1);	
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/reject_unknown_reverse_client_hostname")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/reject_unknown_reverse_client_hostname", 1);
	}

	$reject_unknown_client_hostname=$sock->GET_INFO('reject_unknown_client_hostname');
	$reject_unknown_reverse_client_hostname=$sock->GET_INFO('reject_unknown_reverse_client_hostname');
	
	$reject_invalid_hostname=$sock->GET_INFO('reject_invalid_hostname');
	if($reject_unknown_client_hostname==1){$smtpd_client_restrictions[]="reject_unknown_client_hostname";}
	if($reject_unknown_reverse_client_hostname==1){$smtpd_client_restrictions[]="reject_unknown_reverse_client_hostname";}
	if($reject_invalid_hostname==1){$smtpd_client_restrictions[]="reject_invalid_hostname";}
	
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: reject_invalid_hostname...............: $reject_invalid_hostname\n";
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: reject_unknown_reverse_client_hostname: $reject_unknown_reverse_client_hostname\n";
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: reject_unknown_client_hostname........: $reject_unknown_client_hostname\n";
	
	smtpd_client_restrictions_progress("{construct_settings}",15);

	$main_dnsbl=$main->main_dnsbl();
	$main_rhsbl=$main->main_rhsbl();
	
	if($EnablePostfixAntispamPack==1){
		echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions:Anti-spam Pack is enabled\n";
		if(!is_file("/etc/postfix/postfix_allowed_connections")){@file_put_contents("/etc/postfix/postfix_allowed_connections","#");}
		$smtpd_client_restrictions[]="check_client_access \"hash:/etc/postfix/postfix_allowed_connections\"";
		$smtpd_client_restrictions[]="reject_non_fqdn_hostname";
		$smtpd_client_restrictions[]="reject_invalid_hostname";
		$main_dnsbl["zen.spamhaus.org"]=true;
		$main_dnsbl["sbl.spamhaus.org"]=true;
		$main_dnsbl["cbl.abuseat.org"]=true;
			
	}	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableGenericrDNSClients")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableGenericrDNSClients", 1);
	}
	
	$EnableGenericrDNSClients=$sock->GET_INFO("EnableGenericrDNSClients");
	if($EnableGenericrDNSClients==1){
		$users=new usersMenus();
		if(!$users->POSTFIX_PCRE_COMPLIANCE){$EnableGenericrDNSClients=0;}
	}
	
	if($EnableGenericrDNSClients==1){
		echo "Starting......: ".date("H:i:s")." Reject Public ISP reverse DNS patterns enabled\n";
		$smtpd_client_restrictions[]="check_reverse_client_hostname_access pcre:/etc/postfix/fqrdns.pcre";
		shell_exec("/bin/cp /usr/share/artica-postfix/bin/install/postfix/fqrdns.pcre /etc/postfix/fqrdns.pcre");
	}else{
		echo "Starting......: ".date("H:i:s")." Reject Public ISP reverse DNS patterns disabled\n";
	}
	
	
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions:". count($main_dnsbl)." DNSBL Services\n";
	if(count($main_dnsbl)>0){
		while (list ($num, $ligne) = each ($main_dnsbl) ){
			$smtpd_client_restrictions[]="reject_rbl_client $num";
		}
	}
	if(count($main_rhsbl)>0){
		while (list ($num, $ligne) = each ($main_dnsbl) ){
			$smtpd_client_restrictions[]="reject_rhsbl_client $num";
		}
		
	}
	
	

	
	smtpd_client_restrictions_progress("{construct_settings}",20);


	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: ". count($smtpd_client_restrictions)." rule(s)\n";
	
	
	if($EnableAmavisInMasterCF==1){
		if($EnableAmavisDaemon==1){
			$count=amavis_internal();
			if($count>0){
				echo "Starting......: ".date("H:i:s")." $count addresses bypassing amavisd new\n";
				$amavis_internal="check_client_access hash:/etc/postfix/amavis_internal,";
			}
		}
	}	
	smtpd_client_restrictions_progress("{construct_settings}",25);
	if(is_array($smtpd_client_restrictions)){
		
		
		//CLEAN engine ---------------------------------------------------------------------------------------
		while (list ($num, $ligne) = each ($smtpd_client_restrictions) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			echo "Starting......: ".date("H:i:s")." Clean \"$ligne\"\n";
			$array_cleaned[trim($ligne)]=trim($ligne);
		}
		
		
		
		if(isset($array_cleaned["permit_mynetworks"])){unset($array_cleaned["permit_mynetworks"]);};
		if(isset($array_cleaned["permit_sasl_authenticated"])){unset($array_cleaned["permit_sasl_authenticated"]);}
		
		
		unset($smtpd_client_restrictions);
		$smtpd_client_restrictions=array();
		
		
		smtpd_client_restrictions_progress("{construct_settings}",25);		
		
		if(is_array($array_cleaned)){
			while (list ($num, $ligne) = each ($array_cleaned) ){
				echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions : $ligne\n";
				$smtpd_client_restrictions[]=trim($ligne);}
		}
	   //CLEAN engine ---------------------------------------------------------------------------------------
	}else{
		echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: Not an array\n";
	}	
	
	$newval=null;
	
	
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: arrayof (".count($smtpd_client_restrictions).")\n";
	if(count($smtpd_client_restrictions)>1){
			$newval=implode(",",$smtpd_client_restrictions);
			$newval="{$amavis_internal}permit_mynetworks,permit_sasl_authenticated,reject_unauth_pipelining,$newval";
	}else{
		
		if($amavis_internal<>null){
			echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: adding amavis internal\n";
			$newval="check_client_access hash:/etc/postfix/amavis_internal";
		}
	}
	
	
	smtpd_client_restrictions_progress("{construct_settings}",30);
	echo "Starting......: ".date("H:i:s")." smtpd_client_restrictions: $newval\n";
	
	smtpd_client_restrictions_progress("{apply_settings}",80);
	
	postconf("smtpd_client_restrictions",$newval);
	
	return true;
	
	
}

function restrict_relay_domains(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	system("$php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --restricted-relais");
		
	
}



function smtpd_recipient_restrictions(){
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$newHash=array();
	include_once(dirname(__FILE__)."/ressources/class.postfix.check_recipient_access.inc");
	$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
	$EnablePostfixAntispamPack=$sock->GET_INFO("EnablePostfixAntispamPack");
	$EnableArticaPolicyFilter=$sock->GET_INFO("EnableArticaPolicyFilter");
	$EnablePolicydWeight=intval($sock->GET_INFO('EnablePolicydWeight'));
	$EnableArticaPolicyFilter=0;
	if($GLOBALS["DEBUG"]){echo "EnableCluebringer=$EnableCluebringer\n";}
	$EnableAmavisInMasterCF=$sock->GET_INFO('EnableAmavisInMasterCF');
	$EnableAmavisDaemon=$sock->GET_INFO('EnableAmavisDaemon');	
	$TrustMyNetwork=$sock->GET_INFO("TrustMyNetwork");
	$ValvuladEnabled=intval($sock->GET_INFO("ValvuladEnabled"));
	
	$POLICYD_WEIGHT_PORT=12525;
	
	
	$main=new maincf_multi("master");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	exec("{$GLOBALS["postconf"]} -h smtpd_recipient_restrictions",$datas);
	$tbl=explode(",",implode(" ",$datas));
	$permit_mynetworks_remove=false;
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",51);

	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		if(preg_match("#_rhsbl_#", $ligne)){continue;}
		$newHash[trim($ligne)]=trim($ligne);
		}
	}
	
	
	
	unset($newHash["permit_dnswl_client list.dnswl.org"]);
	unset($newHash["check_client_access hash:/etc/postfix/amavis_internal"]);
	unset($newHash["check_recipient_access hash:/etc/postfix/relay_domains_restricted"]);
	unset($newHash["permit"]);
	unset($newHash["check_sender_access hash:/etc/postfix/disallow_my_domain"]);
	unset($newHash["check_sender_access hash:/etc/postfix/unrestricted_senders"]);
	unset($newHash["check_recipient_access hash:/etc/postfix/amavis_bypass_rcpt"]);
	unset($newHash["reject_unauth_destination"]);
	unset($newHash["permit_mynetworks"]);
	unset($newHash["check_client_access pcre:/etc/postfix/fqrdns.pcre"]);
	unset($newHash["check_policy_service inet:127.0.0.1:54423"]);
	unset($newHash["check_policy_service inet:127.0.0.1:13331"]);
	unset($newHash["check_policy_service inet:127.0.0.1:7777"]);
	unset($newHash["check_policy_service inet:127.0.0.1:3579"]);
	unset($newHash["check_client_access hash:/etc/postfix/wbl_connections"]);
	unset($newHash["check_recipient_access hash:/etc/postfix/wbl_connections"]);
	unset($newHash["check_client_access cidr:/etc/postfix/check_client_access.cidr"]);
	unset($newHash["check_client_access hash:/etc/postfix/check_client_access"]);
	unset($newHash["check_policy_service inet:127.0.0.1:$POLICYD_WEIGHT_PORT"]);
	
	
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",52);
	
	if(is_array($newHash)){	
		while (list ($num, $ligne) = each ($newHash) ){
			
			
			
		if(preg_match("#hash:(.+)$#",$ligne,$re)){
				$path=trim($re[1]);
				if(!is_file($path)){
					echo "Starting......: ".date("H:i:s")." smtpd_recipient_restrictions: bungled \"$ligne\"\n"; 
					continue;
				}
			}
			$smtpd_recipient_restrictions[]=$num;
		}
	}
	


	

	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",53);					
	postconf("smtpd_restriction_classes","artica_restrict_relay_domains");
	postconf("artica_restrict_relay_domains","reject_unverified_recipient");
	$MynetworksInISPMode=$sock->GET_INFO("MynetworksInISPMode");
	if(!is_numeric($MynetworksInISPMode)){$MynetworksInISPMode=0;}		
	if($TrustMyNetwork==0 && $MynetworksInISPMode==1){$TrustMyNetwork=1;}
	

	
	if($TrustMyNetwork==1){$smtpd_recipient_restrictions[]="permit_mynetworks";}else{
		echo "Starting......: ".date("H:i:s")." **** TrustMyNetwork is disabled, outgoing messages should be not allowed... **** \n";
		
	}
	$smtpd_recipient_restrictions[]="permit_mynetworks";
	$smtpd_recipient_restrictions[]="permit_sasl_authenticated";
	
	
	echo "Starting......: ".date("H:i:s")." Postfix class check_recipient_access_ou()...\n";
	smtpd_client_restrictions_progress("{organizations}",54);
	$check_recipient_access_ou=new check_recipient_access_ou();
	$check_recipient_access_ou->build();
	
	$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix/check_recipient_access_ou";
	$smtpd_recipient_restrictions[]="check_client_access cidr:/etc/postfix/check_client_access.cidr";
	$smtpd_recipient_restrictions[]="check_client_access hash:/etc/postfix/check_client_access";
	$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix/relay_domains_restricted";
	$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix/amavis_bypass_rcpt";
	$smtpd_recipient_restrictions[]="permit_auth_destination";
	
	if($ValvuladEnabled==1){
		$smtpd_recipient_restrictions[]="check_policy_service inet:127.0.0.1:3579";
		
	}
	

	if($EnablePolicydWeight==1){
		$smtpd_recipient_restrictions[]="check_client_access hash:/etc/postfix/wbl_connections";
		$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix/wbl_connections";
		$smtpd_recipient_restrictions[]="check_policy_service inet:127.0.0.1:$POLICYD_WEIGHT_PORT";
	}
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",54);
	$smtpd_recipient_restrictions[]="permit_dnswl_client list.dnswl.org";
	
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",55);
	amavis_bypass_byrecipients();
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",56);
	restrict_relay_domains();
	
	
	postconf("auth_relay",null);
	
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",57);

		
		
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$reject_forged_mails=$sock->GET_INFO("reject_forged_mails");
	if($reject_forged_mails==1){
		if(smtpd_recipient_restrictions_reject_forged_mails()){
			echo "Starting......: ".date("H:i:s")." Reject Forged mails enabled\n"; 	
			$smtpd_recipient_restrictions[]="check_sender_access hash:/etc/postfix/disallow_my_domain";
		}
	}else{
		echo "Starting......: ".date("H:i:s")." Reject Forged mails disabled\n"; 			
	}
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",58);
	$main_rhsbl=$main->main_rhsbl();
	
	
	if(count($main_rhsbl)>0){
		while (list ($domain, $ID) = each ($main_rhsbl) ){
			if(trim($domain)==null){continue;}
			$smtpd_recipient_restrictions[]="reject_rhsbl_client $domain";
			$smtpd_recipient_restrictions[]="reject_rhsbl_sender $domain";
		}
	
	}
	
	
	

	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",59);
	$smtpd_recipient_restrictions[]="reject_unauth_destination";
	$smtpd_recipient_restrictions[]="permit";


	if($GLOBALS["EnableBlockUsersTroughInternet"]==1){
		echo "Starting......: ".date("H:i:s")." Restricted users are enabled\n"; 	
		if(RestrictedForInternet()){
 			postconf("auth_relay","check_recipient_access hash:/etc/postfix/local_domains, reject");
			 array_unshift($smtpd_recipient_restrictions,"check_sender_access hash:/etc/postfix/unrestricted_senders");
			__ADD_smtpd_restriction_classes("auth_relay");
		}else{__REMOVE_smtpd_restriction_classes("auth_relay");}
	}
	else{__REMOVE_smtpd_restriction_classes("auth_relay");}	
	
	
	if(is_file("/opt/iRedAPD/iredapd.py")){
		//array_unshift($smtpd_recipient_restrictions,"check_policy_service inet:127.0.0.1:7777");
	}
	
	
	//CLEAN engine ---------------------------------------------------------------------------------------
	while (list ($num, $ligne) = each ($smtpd_recipient_restrictions) ){
		$smtpd_recipient_restrictions_cleaned[trim($ligne)]=trim($ligne);
	}
	
	
	
	unset($smtpd_recipient_restrictions);
	while (list ($num, $ligne) = each ($smtpd_recipient_restrictions_cleaned) ){
		echo "Starting......: ".date("H:i:s")." smtpd_recipient_restrictions Final: ".trim($ligne)."\n";
		$smtpd_recipient_restrictions[]=trim($ligne);
	}

   //CLEAN engine ---------------------------------------------------------------------------------------
	
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",59);
	if(is_array($smtpd_recipient_restrictions)){$newval=implode(",",$smtpd_recipient_restrictions);}
	if($GLOBALS["DEBUG"]){echo "smtpd_recipient_restrictions = $newval\n";}
	postconf("smtpd_recipient_restrictions",$newval);
	smtpd_client_restrictions_progress("{smtpd_recipient_restrictions}",60);
	
	}
	
function amavis_bypass_byrecipients(){
	$f=array();
	$count=0;
	$users=new usersMenus();
	$q=new mysql();
	$unix=new unix();
	$sock=new sockets();
	$EnableAmavisDaemon=$sock->GET_INFO('EnableAmavisDaemon');
	$EnableAmavisInMasterCF=$sock->GET_INFO('EnableAmavisInMasterCF');
	if(!$users->AMAVIS_INSTALLED){$EnableAmavisDaemon=0;}
	if($EnableAmavisDaemon==1){
		if($EnableAmavisInMasterCF==1){
			$sql="SELECT * FROM amavis_bypass_rcpt ORDER BY `pattern`";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";return 0;}	
			$count=0;
			$f=array();
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$ligne["pattern"]=trim($ligne["pattern"]);
				$ip=trim($ligne["pattern"]);
				if($ip==null){continue;}
				if(is_array($ip)){continue;}
				$count++;
				$f[]="{$ligne["pattern"]}\tFILTER smtp:[127.0.0.1]:10025";
			}
		}
	}
	$postmap=$unix->find_program("postmap");
	echo "Starting......: ".date("H:i:s")." ". count($f) ." bypass recipient(s) for amavisd new\n"; 	
	
	$f[]="";
	@file_put_contents("/etc/postfix/amavis_bypass_rcpt",@implode("\n",$f));
	shell_exec("$postmap hash:/etc/postfix/amavis_bypass_rcpt");
	return $count;
	}	
	
function amavis_internal(){
	$users=new usersMenus();
	$q=new mysql();
	$unix=new unix();
	$sock=new sockets();
	$EnableAmavisDaemon=$sock->GET_INFO('EnableAmavisDaemon');
	$EnableAmavisInMasterCF=$sock->GET_INFO('EnableAmavisInMasterCF');
	if(!$users->AMAVIS_INSTALLED){$EnableAmavisDaemon=0;}
	if($EnableAmavisDaemon==1){
		if($EnableAmavisInMasterCF==1){
			$sql="SELECT * FROM amavisd_bypass ORDER BY ip_addr";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";return 0;}	
			$count=0;
			$f=array();
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$ligne["ip_addr"]=trim($ligne["ip_addr"]);
				$ip=trim($ligne["ip_addr"]);
				if($ip==null){continue;}
				if(is_array($ip)){continue;}
				$count++;
				$f[]="{$ligne["ip_addr"]}\tFILTER smtp:[127.0.0.1]:10025";
			}
		}
	}
	
	$postmap=$unix->find_program("postmap");
	$f[]="";
	@file_put_contents("/etc/postfix/amavis_internal",@implode("\n",$f));
	shell_exec("$postmap hash:/etc/postfix/amavis_internal");
	return $count;
}	




	
function __ADD_smtpd_restriction_classes($classname){
exec("{$GLOBALS["postconf"]} -h smtpd_restriction_classes",$datas);
	$tbl=explode(",",implode(" ",$datas));
	

	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		$newHash[$ligne]=$ligne;
		}
	}
	
	unset($newHash[$classname]);
	
	if(is_array($newHash)){	
		while (list ($num, $ligne) = each ($newHash) ){	
			$smtpd_restriction_classes[]=$num;
		}
	}
	
	$smtpd_restriction_classes[]=$classname;
	if(is_array($smtpd_restriction_classes)){$newval=implode(",",$smtpd_restriction_classes);}
	
	postconf("smtpd_restriction_classes",$newval);
		
	
}

function __REMOVE_smtpd_restriction_classes($classname){
	exec("{$GLOBALS["postconf"]} -h smtpd_restriction_classes",$datas);
	$tbl=explode(",",implode(" ",$datas));
	$newHash=array();

	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		$newHash[$ligne]=$ligne;
		}
	}
	
	unset($newHash[$classname]);
	
	if(is_array($newHash)){	
		while (list ($num, $ligne) = each ($newHash) ){	
			$smtpd_restriction_classes[]=$num;
		}
	}
	
	if(is_array($smtpd_restriction_classes)){$newval=implode(",",$smtpd_restriction_classes);}
	postconf("smtpd_restriction_classes",$newval);
}
	
	
function smtpd_recipient_restrictions_reject_forged_mails(){
	$ldap=new clladp();
	$unix=new unix();
	$postmap=$unix->find_program("postmap");
	$hash=$ldap->hash_get_all_domains();
	if(!is_array($hash)){return false;}
	while (list ($domain, $ligne) = each ($hash) ){
		$f[]="$domain\t 554 $domain FORGED MAIL"; 
		
	}
	
	if(!is_array($f)){return false;}
	@file_put_contents("/etc/postfix/disallow_my_domain",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." compiling domains against forged messages\n";
	shell_exec("$postmap hash:/etc/postfix/disallow_my_domain");
	return true;
}

function RestrictedForInternet($reload=false){
	$main=new main_cf();
	$unix=new unix();
	$GLOBALS["postmap"]=$unix->find_program("postmap");
	$restricted_users=$users=$main->check_sender_access();
	if(!$reload){echo "Starting......: ".date("H:i:s")." Restricted users ($restricted_users)\n";}
	if($restricted_users>0){
		@copy("/etc/artica-postfix/settings/Daemons/unrestricted_senders","/etc/postfix/unrestricted_senders");
		@copy("/etc/artica-postfix/settings/Daemons/unrestricted_senders_domains","/etc/postfix/local_domains");
		echo "Starting......: ".date("H:i:s")." Compiling unrestricted users ($restricted_users)\n";
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/unrestricted_senders");
		echo "Starting......: ".date("H:i:s")." Compiling local domains\n";
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/local_domains");
		if($reload){shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");}
		return true;
		}
	return false;
	
}

function CleanMyHostname(){
	exec("{$GLOBALS["postconf"]} -h myhostname",$results);
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$myhostname=trim(implode("",$results));
	$myhostname=str_replace("header_checks =","",$myhostname);
	exec("{$GLOBALS["postconf"]} -h relayhost",$results);
	
	if(is_array($results)){
		$relayhost=trim(@implode("",$results));
	}
	
	if($myhostname=="Array.local"){
		if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
		$myhostname=$users->hostname;
	}
	
	if($relayhost<>null){
		if($myhostname==$relayhost){
			$myhostname="$myhostname.local";
		}
	}
	
	//fix bug with extension.
	
	$myhostname=str_replace(".local.local.",".local",$myhostname);
	$myhostname=str_replace(".locallocal.locallocal.",".",$myhostname);
	$myhostname=str_replace(".locallocal",".local",$myhostname);
	$myhostname=str_replace(".local.local",".local",$myhostname);
	
	$myhostname2=trim($sock->GET_INFO("myhostname"));
	if(strlen($myhostname2)>0){
		$myhostname=$myhostname2;
	}
	

	echo "Starting......: ".date("H:i:s")." Hostname=$myhostname\n";
	
	postconf("myhostname",$myhostname);
	
}

function smtpd_sasl_exceptions_networks(){
	$nets=array();
	$main=new maincf_multi("master");
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$smtpd_sasl_exceptions_networks_list=unserialize(base64_decode($sock->GET_INFO("smtpd_sasl_exceptions_networks")));
	$smtpd_sasl_exceptions_mynet=$sock->GET_INFO("smtpd_sasl_exceptions_mynet");
	$TrustMyNetwork=$main->GET("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}	
	if($smtpd_sasl_exceptions_mynet==1){$nets[]="\\\$mynetworks";}
	
	if(is_array($smtpd_sasl_exceptions_networks_list)){
		while (list ($num, $val) = each ($smtpd_sasl_exceptions_networks_list) ){
			if($val==null){continue;}
			$nets[]=$val;
		}
	}
	
	
	if(count($nets)>0){
		$final_nets=implode(",",$nets);
		echo "Starting......: ".date("H:i:s")." SASL exceptions enabled\n";
		postconf("smtpd_sasl_exceptions_networks",$final_nets);
		
	}else{
		echo "Starting......: ".date("H:i:s")." SASL exceptions disabled\n";
		postconf("smtpd_sasl_exceptions_networks",null);
		
	}
}

function sender_bcc_maps(){
if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$sender_bcc_maps_path=$sock->GET_INFO("sender_bcc_maps_path");
	if(is_file($sender_bcc_maps_path)){
		echo "Starting......: ".date("H:i:s")." Sender BCC \"$sender_bcc_maps_path\"\n";
		postconf("sender_bcc_maps","hash:$sender_bcc_maps_path");
		shell_exec("{$GLOBALS["postmap"]} hash:$sender_bcc_maps_path");
	}
	
}

function smtp_banner(){
	$mainmulti=new maincf_multi("master","master");
	$smtpd_banner=$mainmulti->GET('smtpd_banner');
	echo 	"$smtpd_banner\n";
}

function OthersValues(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return;}	
	$main=new main_cf();
	$mainmulti=new maincf_multi("master","master");
	$main->FillDefaults();	
	echo "Starting......: ".date("H:i:s")." Fix others settings\n";
	
	$message_size_limit=$sock->GET_INFO("message_size_limit");
	if(!is_numeric($message_size_limit)){
		$message_size_limit=0;
		
	}
	$main->main_array["message_size_limit"]=$sock->GET_INFO("message_size_limit");
	
	
	$minimal_backoff_time=$mainmulti->GET("minimal_backoff_time");
	$maximal_backoff_time=$mainmulti->GET("maximal_backoff_time");
	$bounce_queue_lifetime=$mainmulti->GET("bounce_queue_lifetime");
	$maximal_queue_lifetime=$mainmulti->GET("maximal_queue_lifetime");
	
	$smtp_connection_cache_on_demand=$mainmulti->GET("smtp_connection_cache_on_demand");
	$smtp_connection_cache_time_limit=$mainmulti->GET("smtp_connection_cache_time_limit");
	$smtp_connection_reuse_time_limit=$mainmulti->GET("smtp_connection_reuse_time_limit");
	$connection_cache_ttl_limit=$mainmulti->GET("connection_cache_ttl_limit");
	$connection_cache_status_update_time=$mainmulti->GET("connection_cache_status_update_time");
	$smtp_connection_cache_destinations=unserialize(base64_decode($mainmulti->GET_BIGDATA("smtp_connection_cache_destinations")));	
	
	$address_verify_map=$mainmulti->GET("address_verify_map");
	$address_verify_negative_cache=$mainmulti->GET("address_verify_negative_cache");
	$address_verify_poll_count=$mainmulti->GET("address_verify_poll_count");
	$address_verify_poll_delay=$mainmulti->GET("address_verify_poll_delay");
	$address_verify_sender=$mainmulti->GET("address_verify_sender");
	$address_verify_negative_expire_time=$mainmulti->GET("address_verify_negative_expire_time");
	$address_verify_negative_refresh_time=$mainmulti->GET("address_verify_negative_refresh_time");
	$address_verify_positive_expire_time=$mainmulti->GET("address_verify_positive_expire_time");
	$address_verify_positive_refresh_time=$mainmulti->GET("address_verify_positive_refresh_time");
	if($address_verify_map==null){$address_verify_map="btree:/var/lib/postfix/verify";}
	
	$smtpd_error_sleep_time=$mainmulti->GET("smtpd_error_sleep_time");
	$smtpd_soft_error_limit=$mainmulti->GET("smtpd_soft_error_limit");
	$smtpd_hard_error_limit=$mainmulti->GET("smtpd_hard_error_limit");
	$smtpd_client_connection_count_limit=$mainmulti->GET("smtpd_client_connection_count_limit");
	$smtpd_client_connection_rate_limit=$mainmulti->GET("smtpd_client_connection_rate_limit");
	$smtpd_client_message_rate_limit=$mainmulti->GET("smtpd_client_message_rate_limit");
	$smtpd_client_recipient_rate_limit=$mainmulti->GET("smtpd_client_recipient_rate_limit");
	$smtpd_client_new_tls_session_rate_limit=$mainmulti->GET("smtpd_client_new_tls_session_rate_limit");
	$smtpd_client_event_limit_exceptions=$mainmulti->GET("smtpd_client_event_limit_exceptions");
	$in_flow_delay=$mainmulti->GET("in_flow_delay");
	$smtp_connect_timeout=$mainmulti->GET("smtp_connect_timeout");
	$smtp_helo_timeout=$mainmulti->GET("smtp_helo_timeout");
	$initial_destination_concurrency=$mainmulti->GET("initial_destination_concurrency");
	$default_destination_concurrency_limit=$mainmulti->GET("default_destination_concurrency_limit");
	$local_destination_concurrency_limit=$mainmulti->GET("local_destination_concurrency_limit");
	$smtp_destination_concurrency_limit=$mainmulti->GET("smtp_destination_concurrency_limit");
	$default_destination_recipient_limit=$mainmulti->GET("default_destination_recipient_limit");
	$smtpd_recipient_limit=$mainmulti->GET("smtpd_recipient_limit");
	$queue_run_delay=$mainmulti->GET("queue_run_delay");  
	$minimal_backoff_time =$mainmulti->GET("minimal_backoff_time");
	$maximal_backoff_time =$mainmulti->GET("maximal_backoff_time");
	$maximal_queue_lifetime=$mainmulti->GET("maximal_queue_lifetime"); 
	$bounce_queue_lifetime =$mainmulti->GET("bounce_queue_lifetime");
	$qmgr_message_recipient_limit =$mainmulti->GET("qmgr_message_recipient_limit");
	$default_process_limit=$mainmulti->GET("default_process_limit");	
	$smtp_fallback_relay=$mainmulti->GET("smtp_fallback_relay");
	$smtpd_reject_unlisted_recipient=$mainmulti->GET("smtpd_reject_unlisted_recipient");
	$smtpd_reject_unlisted_sender=$mainmulti->GET("smtpd_reject_unlisted_sender");

	$ignore_mx_lookup_error=$mainmulti->GET("ignore_mx_lookup_error");
	$disable_dns_lookups=$mainmulti->GET("disable_dns_lookups");
	$smtpd_banner=$mainmulti->GET('smtpd_banner');
	$enable_original_recipient=$mainmulti->GET("enable_original_recipient");
	$undisclosed_recipients_header=$mainmulti->GET("undisclosed_recipients_header");
	$smtpd_discard_ehlo_keywords=$mainmulti->GET("smtpd_discard_ehlo_keywords");
	
	
	$detect_8bit_encoding_header=$mainmulti->GET("detect_8bit_encoding_header");
	$disable_mime_input_processing=$mainmulti->GET("disable_mime_input_processing");
	$disable_mime_output_conversion=$mainmulti->GET("disable_mime_output_conversion");
	
	
	if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
	if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
	if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
	
	
	if(!is_numeric($ignore_mx_lookup_error)){$ignore_mx_lookup_error=0;}
	if(!is_numeric($disable_dns_lookups)){$disable_dns_lookups=0;}
	if(!is_numeric($smtpd_reject_unlisted_recipient)){$smtpd_reject_unlisted_recipient=1;}
	if(!is_numeric($smtpd_reject_unlisted_sender)){$smtpd_reject_unlisted_sender=0;}
	
		
	


	
	
	if(!is_numeric($smtp_connection_cache_on_demand)){$smtp_connection_cache_on_demand=1;}
	if($smtp_connection_cache_time_limit==null){$smtp_connection_cache_time_limit="2s";}
	if($smtp_connection_reuse_time_limit==null){$smtp_connection_reuse_time_limit="300s";}
	if($connection_cache_ttl_limit==null){$connection_cache_ttl_limit="2s";}
	if($connection_cache_status_update_time==null){$connection_cache_status_update_time="600s";}	
	if($smtp_connection_cache_on_demand==1){$smtp_connection_cache_on_demand="yes";}else{$smtp_connection_cache_on_demand="no";}
	
	if(count($smtp_connection_cache_destinations)>0){
		while (list ($host, $none) = each ($smtp_connection_cache_destinations) ){$smtp_connection_cache_destinationsR[]=$host;}
		$smtp_connection_cache_destinationsF=@implode(",", $smtp_connection_cache_destinationsR);
	}
	

	if(!is_numeric($address_verify_negative_cache)){$address_verify_negative_cache=1;}
	if(!is_numeric($address_verify_poll_count)){$address_verify_poll_count=3;}
	if($address_verify_poll_delay==null){$address_verify_poll_delay="3s";}
	if($address_verify_sender==null){$address_verify_sender="double-bounce";}
	if($address_verify_negative_expire_time==null){$address_verify_negative_expire_time="3d";}
	if($address_verify_negative_refresh_time==null){$address_verify_negative_refresh_time="3h";}
	if($address_verify_positive_expire_time==null){$address_verify_positive_expire_time="31d";}
	if($address_verify_positive_refresh_time==null){$address_verify_positive_refresh_time="7d";}
	if($smtpd_error_sleep_time==null){$smtpd_error_sleep_time="1s";}
	if(!is_numeric($smtpd_soft_error_limit)){$smtpd_soft_error_limit=10;}
	if(!is_numeric($smtpd_hard_error_limit)){$smtpd_hard_error_limit=20;}
	if(!is_numeric($smtpd_client_connection_count_limit)){$smtpd_client_connection_count_limit=50;}
	if(!is_numeric($smtpd_client_connection_rate_limit)){$smtpd_client_connection_rate_limit=0;}
	if(!is_numeric($smtpd_client_message_rate_limit)){$smtpd_client_message_rate_limit=0;}
	if(!is_numeric($smtpd_client_recipient_rate_limit)){$smtpd_client_recipient_rate_limit=0;}
	if(!is_numeric($smtpd_client_new_tls_session_rate_limit)){$smtpd_client_new_tls_session_rate_limit=0;}
	if(!is_numeric($initial_destination_concurrency)){$initial_destination_concurrency=5;}
	if(!is_numeric($default_destination_concurrency_limit)){$default_destination_concurrency_limit=20;}
	if(!is_numeric($smtp_destination_concurrency_limit)){$smtp_destination_concurrency_limit=20;}
	if(!is_numeric($local_destination_concurrency_limit)){$local_destination_concurrency_limit=2;}
	if(!is_numeric($default_destination_recipient_limit)){$default_destination_recipient_limit=50;}
	if(!is_numeric($smtpd_recipient_limit)){$smtpd_recipient_limit=1000;}
	if(!is_numeric($default_process_limit)){$default_process_limit=100;}
	if(!is_numeric($qmgr_message_recipient_limit)){$qmgr_message_recipient_limit=20000;}
	if($smtpd_client_event_limit_exceptions==null){$smtpd_client_event_limit_exceptions="\$mynetworks";}
	if($in_flow_delay==null){$in_flow_delay="1s";}
	if($smtp_connect_timeout==null){$smtp_connect_timeout="30s";}
	if($smtp_helo_timeout==null){$smtp_helo_timeout="300s";}
	if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
	if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}
	if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
	if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
	if($queue_run_delay==null){$queue_run_delay="300s";}	
	if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}
	
	
	
	$detect_8bit_encoding_header=$mainmulti->YesNo($detect_8bit_encoding_header);
	$disable_mime_input_processing=$mainmulti->YesNo($disable_mime_input_processing);
	$disable_mime_output_conversion=$mainmulti->YesNo($disable_mime_output_conversion);
	$smtpd_reject_unlisted_sender=$mainmulti->YesNo($smtpd_reject_unlisted_sender);
	$smtpd_reject_unlisted_recipient=$mainmulti->YesNo($smtpd_reject_unlisted_recipient);
	$ignore_mx_lookup_error=$mainmulti->YesNo($ignore_mx_lookup_error);
	$disable_dns_lookups=$mainmulti->YesNo($disable_dns_lookups);
	
	
	if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
	if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
	$enable_original_recipient=$mainmulti->YesNo($enable_original_recipient);
	

	
	
	
	$mime_nesting_limit=$mainmulti->GET("mime_nesting_limit");
	if(!is_numeric($mime_nesting_limit)){
		$mime_nesting_limit=$sock->GET_INFO("mime_nesting_limit");
	}
	
	if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}
	
	$main->main_array["default_destination_recipient_limit"]=$sock->GET_INFO("default_destination_recipient_limit");
	$main->main_array["smtpd_recipient_limit"]=$sock->GET_INFO("smtpd_recipient_limit");
	
	$main->main_array["header_address_token_limit"]=$sock->GET_INFO("header_address_token_limit");
	$main->main_array["virtual_mailbox_limit"]=$sock->GET_INFO("virtual_mailbox_limit");
	
	if($main->main_array["message_size_limit"]==null){$main->main_array["message_size_limit"]=102400000;}
	if($main->main_array["virtual_mailbox_limit"]==null){$main->main_array["virtual_mailbox_limit"]=102400000;}
	if($main->main_array["default_destination_recipient_limit"]==null){$main->main_array["default_destination_recipient_limit"]=50;}
	if($main->main_array["smtpd_recipient_limit"]==null){$main->main_array["smtpd_recipient_limit"]=1000;}
	if($main->main_array["header_address_token_limit"]==null){$main->main_array["header_address_token_limit"]=10240;}
	
	echo "Starting......: ".date("H:i:s")." message_size_limit={$main->main_array["message_size_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." default_destination_recipient_limit={$main->main_array["default_destination_recipient_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." smtpd_recipient_limit={$main->main_array["smtpd_recipient_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." *** MIME PROCESSING ***\n";
	echo "Starting......: ".date("H:i:s")." mime_nesting_limit=$mime_nesting_limit\n";
	echo "Starting......: ".date("H:i:s")." detect_8bit_encoding_header=$detect_8bit_encoding_header\n";
	echo "Starting......: ".date("H:i:s")." disable_mime_input_processing=$disable_mime_input_processing\n";
	echo "Starting......: ".date("H:i:s")." disable_mime_output_conversion=$disable_mime_output_conversion\n";
	
	
	
	echo "Starting......: ".date("H:i:s")." header_address_token_limit={$main->main_array["header_address_token_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." minimal_backoff_time=$minimal_backoff_time\n";
	echo "Starting......: ".date("H:i:s")." maximal_backoff_time=$maximal_backoff_time\n";
	echo "Starting......: ".date("H:i:s")." maximal_queue_lifetime=$maximal_queue_lifetime\n";
	echo "Starting......: ".date("H:i:s")." bounce_queue_lifetime=$bounce_queue_lifetime\n";
	echo "Starting......: ".date("H:i:s")." ignore_mx_lookup_error=$ignore_mx_lookup_error\n";
	echo "Starting......: ".date("H:i:s")." disable_dns_lookups=$disable_dns_lookups\n";
	echo "Starting......: ".date("H:i:s")." smtpd_banner=$smtpd_banner\n";
	
	
	
	
	if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
	if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
	if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
	if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}

	$postfix_ver=$mainmulti->postfix_version();
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $postfix_ver,$re)){$MAJOR=$re[1];$MINOR=$re[2];}
	if($MAJOR>1){
		if($MINOR>9){
			postconf("smtpd_relay_restrictions","permit_mynetworks, permit_sasl_authenticated, defer_unauth_destination");
		}
	}

	build_progress_mime_header("{configuring}",50);
	$address_verify_negative_cache=$mainmulti->YesNo($address_verify_negative_cache);
	echo "Starting......: ".date("H:i:s")." Apply all settings..\n";
	postconf("smtpd_reject_unlisted_sender","$smtpd_reject_unlisted_sender");
	postconf("smtpd_reject_unlisted_recipient","$smtpd_reject_unlisted_recipient");
	postconf("address_verify_map","$address_verify_map");
	postconf("address_verify_negative_cache","$address_verify_negative_cache");
	postconf("address_verify_poll_count","$address_verify_poll_count");
	postconf("address_verify_poll_delay","$address_verify_poll_delay");
	postconf("address_verify_sender","$address_verify_sender");
	postconf("address_verify_negative_expire_time","$address_verify_negative_expire_time");
	postconf("address_verify_negative_refresh_time","$address_verify_negative_refresh_time");
	postconf("address_verify_positive_expire_time","$address_verify_positive_expire_time");
	postconf("address_verify_positive_refresh_time","$address_verify_positive_refresh_time");	
	postconf("message_size_limit","$message_size_limit");
	postconf("virtual_mailbox_limit","$message_size_limit");
	postconf("mailbox_size_limit","$message_size_limit");
	postconf("default_destination_recipient_limit","{$main->main_array["default_destination_recipient_limit"]}");
	postconf("smtpd_recipient_limit","{$main->main_array["smtpd_recipient_limit"]}");
	
	postconf("mime_nesting_limit","$mime_nesting_limit");
	postconf("detect_8bit_encoding_header","$detect_8bit_encoding_header");
	postconf("disable_mime_input_processing","$disable_mime_input_processing");
	postconf("disable_mime_output_conversion","$disable_mime_output_conversion");
		
	postconf("minimal_backoff_time","$minimal_backoff_time");
	postconf("maximal_backoff_time","$maximal_backoff_time");
	postconf("maximal_queue_lifetime","$maximal_queue_lifetime");
	postconf("bounce_queue_lifetime","$bounce_queue_lifetime");
	postconf("smtp_connection_cache_on_demand","$smtp_connection_cache_on_demand");
	postconf("smtp_connection_cache_time_limit","$smtp_connection_cache_time_limit");
	postconf("smtp_connection_reuse_time_limit","$smtp_connection_reuse_time_limit");
	postconf("connection_cache_ttl_limit","$connection_cache_ttl_limit");
	postconf("connection_cache_status_update_time","$connection_cache_status_update_time");	
	postconf("smtp_connection_cache_destinations","$smtp_connection_cache_destinationsF");
	postconf("smtpd_error_sleep_time",$smtpd_error_sleep_time);
	postconf("smtpd_soft_error_limit",$smtpd_soft_error_limit);
	postconf("smtpd_hard_error_limit",$smtpd_hard_error_limit);
	postconf("smtpd_client_connection_count_limit",$smtpd_client_connection_count_limit);
	postconf("smtpd_client_connection_rate_limit",$smtpd_client_connection_rate_limit);
	postconf("smtpd_client_message_rate_limit",$smtpd_client_message_rate_limit);
	postconf("smtpd_client_recipient_rate_limit",$smtpd_client_recipient_rate_limit);
	postconf("smtpd_client_new_tls_session_rate_limit",$smtpd_client_new_tls_session_rate_limit);
	postconf("initial_destination_concurrency",$initial_destination_concurrency);
	postconf("default_destination_concurrency_limit",$default_destination_concurrency_limit);
	postconf("smtp_destination_concurrency_limit",$smtp_destination_concurrency_limit);
	postconf("local_destination_concurrency_limit",$local_destination_concurrency_limit);
	postconf("default_destination_recipient_limit",$default_destination_recipient_limit);
	postconf("smtpd_recipient_limit",$smtpd_recipient_limit);
	postconf("default_process_limit",$default_process_limit);
	postconf("qmgr_message_recipient_limit",$qmgr_message_recipient_limit);
	postconf("smtpd_client_event_limit_exceptions",$smtpd_client_event_limit_exceptions);
	postconf("in_flow_delay",$in_flow_delay);
	postconf("smtp_connect_timeout",$smtp_connect_timeout);
	postconf("smtp_helo_timeout",$smtp_helo_timeout);
	postconf("bounce_queue_lifetime",$bounce_queue_lifetime);
	postconf("maximal_queue_lifetime",$maximal_queue_lifetime);
	postconf("maximal_backoff_time",$maximal_backoff_time);
	postconf("minimal_backoff_time",$minimal_backoff_time);
	postconf("queue_run_delay",$queue_run_delay);	
	postconf("smtp_fallback_relay",$smtp_fallback_relay);
	postconf("ignore_mx_lookup_error",$ignore_mx_lookup_error);
	postconf("disable_dns_lookups",$disable_dns_lookups);
	postconf("smtpd_banner",$smtpd_banner);
	postconf("undisclosed_recipients_header","$undisclosed_recipients_header");
	postconf("enable_original_recipient","$enable_original_recipient");
	postconf("smtpd_discard_ehlo_keywords","$smtpd_discard_ehlo_keywords");
	

	build_progress_mime_header("{configuring} {done}",60);
	
	
	if(!isset($GLOBALS["POSTFIX_HEADERS_CHECK_BUILDED"])){headers_check(1);}
	
	
	$HashMainCf=unserialize(base64_decode($sock->GET_INFO("HashMainCf")));
	if(is_array($HashMainCf)){
		while (list ($key, $val) = each ($HashMainCf) ){
			system("{$GLOBALS["postconf"]} -e \"$key = $val\" >/dev/null 2>&1");
		}
	}
	
	$hashT=new main_hash_table();
	echo "Starting......: ".date("H:i:s")." Apply mydestination\n";
	build_progress_mime_header("My Destination...",65);
	$hashT->mydestination();	
	echo "Starting......: ".date("H:i:s")." Apply perso_settings\n";
	build_progress_mime_header("Perso settings...",70);
	perso_settings();
	build_progress_mime_header("Perso settings...",75);
}

function LoadIpAddresses($nic){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#inet adr:([0-9\.]+)#", $line,$re)){
			$array[trim($re[1])]=trim($re[1]);
		}
	}
	
	return $array;
}

function inet_interfaces(){
	$newarray=array();
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return;}
	$table=explode("\n",$sock->GET_INFO("PostfixBinInterfaces"));	
	$unix=new unix();
	
	$interfacesexists=$unix->NETWORK_ALL_INTERFACES();
	while (list ($num, $myarray) = each ($interfacesexists) ){
		$INTERFACE[$myarray["IPADDR"]]=$myarray["IPADDR"];
	}
	
	while (list ($num, $val) = each ($table) ){
		$val=trim($val);
		if($val==null){continue;}
		if($val=="all"){
			echo "Starting......: ".date("H:i:s")." Postfix skip $val\n";
			continue;
		}
		if(isset($already[$val])){continue;}
		echo "Starting......: ".date("H:i:s")." Postfix checking interface : `$val`\n";
		if($val=="127.0.0.1"){
			$newarray[]=$val;
			$already[$val]=true;
			continue;
		}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $val)){
			if(!isset($INTERFACE[$val])){
				echo "Starting......: ".date("H:i:s")." Postfix $val interface not found\n";
				continue;
			}
		}
		
		$already[$val]=true;
		if(preg_match("#[a-zA-Z]+[0-9]+#", $val)){
			$ipsaddrs=LoadIpAddresses($val);
			while (list ($a, $b) = each ($ipsaddrs) ){
				echo "Starting......: ".date("H:i:s")." Postfix found interface '$b'\n";
				$newarray[]=$b;
			}
		continue;
		}
		
		if($val=="all"){continue;}
		
		echo "Starting......: ".date("H:i:s")." Postfix add $val interface in settings\n";
		$newarray[]=$val;
	}
	
	if(count($newarray)>0){
		while (list ($a, $b) = each ($newarray) ){$testinets[$b]=$b;}
		$users=new usersMenus();
		if(($users->roundcube_installed) OR ($users->ZARAFA_INSTALLED)){
			if(!isset($testinets["127.0.0.1"])){
				echo "Starting......: ".date("H:i:s")." Postfix Listen interface Roundcube or Zarafa installed, force to listen 127.0.0.1\n";
				$newarray[]="127.0.0.1";
			}
		}		
	}
	

	if(count($newarray)>0){
		$finale=implode(",",$newarray);
		$finale=str_replace(',,',',',$finale);
	}else{
		$unix=new unix();
		$INT=$unix->NETWORK_ALL_INTERFACES(true);
		$INT["127.0.0.1"]="127.0.0.1";
		while (list ($a, $b) = each ($INT) ){$INTS[]=$a;}
		$finale=@implode(",", $INTS);
	}
	
	echo "Starting......: ".date("H:i:s")." Postfix Listen interface(s) \"$finale\"\n";
	
	
	postconf("inet_interfaces",$finale);
	
	postconf("inet_protocols","ipv4");
	postconf("smtp_bind_address6","");
	
	
	 
	
	$smtp_bind_address6=$sock->GET_INFO("smtp_bind_address6");
	$PostfixEnableIpv6=$sock->GET_INFO("PostfixEnableIpv6");
	if($PostfixEnableIpv6==null){$PostfixEnableIpv6=0;}
	if($PostfixEnableIpv6=1){
		if(trim($smtp_bind_address6)<>null){
			echo "Starting......: ".date("H:i:s")." Postfix Listen ipv6 \"$smtp_bind_address6\"\n";
			postconf("inet_protocols","all");
			postconf("smtp_bind_address6",$smtp_bind_address6);
		}
	}
	
	
	
}

function MailBoxTransport(){
	$main=new maincf_multi("master","master");
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	
	echo "Starting......: ".date("H:i:s")." Postfix get mailbox transport\n";
	$mailbox_transport=trim($main->GET("mailbox_transport"));
	echo "Starting......: ".date("H:i:s")." Postfix get mailbox transport = \"$mailbox_transport\"\n";
	
	if($mailbox_transport<>null){
		postconf("mailbox_transport",$mailbox_transport);
		postconf("zarafa_destination_recipient_limit",1);
		return;	
	}
	
	

	$default=$main->getMailBoxTransport();
	
	if($default==null){
		postconf_X("mailbox_transport");
		postconf_X("virtual_transport");
		return;
	}
	
	postconf("zarafa_destination_recipient_limit",1);
	echo "Starting......: ".date("H:i:s")." Postfix mailbox_transport=`$default`\n";
	postconf("mailbox_transport",$default);
	postconf("virtual_transport","\$mailbox_transport");
	postconf("local_transport","local");
	postconf("lmtp_sasl_auth_enable","no");
	postconf("lmtp_sasl_password_maps","");
	postconf("lmtp_sasl_mechanism_filter","plain, login");
	postconf("lmtp_sasl_security_options",null);
	
	if(!$users->ZARAFA_INSTALLED){
		if(!$users->cyrus_imapd_installed){
			echo "Starting......: ".date("H:i:s")." Postfix None of Zarafa or cyrus imap installed on this server\n";
			return null;
		}
	}

	
	if(preg_match("#lmtp:(.+?):([0-9]+)#",$default,$re)){
		echo "Starting......: ".date("H:i:s")." Postfix \"LMTP\" is enabled ($default)\n";
		$ldap=new clladp();
		$CyrusLMTPListen=$re[1].":".$re[2];
		$cyruspass=$ldap->CyrusPassword();
		@file_put_contents("/etc/postfix/lmtpauth","$CyrusLMTPListen\tcyrus:$cyruspass");
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/lmtpauth");
		postconf("lmtp_sasl_auth_enable","yes");
		postconf("lmtp_sasl_password_maps","hash:/etc/postfix/lmtpauth");
		postconf("lmtp_sasl_mechanism_filter","plain, login");
		postconf("lmtp_sasl_security_options","noanonymous");
		}
	}
	
	
	
function disable_lmtp_sasl(){
	echo "Starting......: ".date("H:i:s")." Postfix LMTP is disabled\n";
	postconf("lmtp_sasl_auth_enable","no");
	
			
}
	
function disable_smtp_sasl(){
	postconf("smtp_sasl_password_maps","");
	postconf("smtp_sasl_auth_enable","no");
	
}

function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("/etc/postfix/main.cf");
	if($GLOBALS["RELOAD"]){exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");}
	
}

function luser_relay(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$luser_relay=trim($sock->GET_INFO("luser_relay"));
	if($luser_relay==null){
		echo "Starting......: ".date("H:i:s")." Postfix no Unknown user recipient set\n";
		system("{$GLOBALS["postconf"]} -e \"luser_relay = \" >/dev/null 2>&1");
		return;
	}
	echo "Starting......: ".date("H:i:s")." Postfix Unknown user set to $luser_relay\n";
	postconf("luser_relay",$luser_relay);
	postconf("local_recipient_maps",null);
	if($GLOBALS["RELOAD"]){shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");}
	
}
function smtpd_sender_restrictions(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$main=new maincf_multi("master","master");
	$smtpd_sender_restrictions_black=$main->Blacklist_generic();
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/reject_unknown_sender_domain")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/reject_unknown_sender_domain", 1);
	}
	
	$RestrictToInternalDomains=$sock->GET_INFO("RestrictToInternalDomains");
	$EnablePostfixInternalDomainsCheck=$sock->GET_INFO("EnablePostfixInternalDomainsCheck");
	$reject_non_fqdn_sender=$sock->GET_INFO('reject_non_fqdn_sender');	
	$reject_unknown_sender_domain=$sock->GET_INFO('reject_unknown_sender_domain');
	$enforce_helo_restrictions=intval($sock->GET_INFO("enforce_helo_restrictions"));
	
	$smtpd_sender_restrictions[]="permit_mynetworks";
	$smtpd_sender_restrictions[]="permit_sasl_authenticated";
	$smtpd_sender_restrictions[]="check_client_access cidr:/etc/postfix/check_client_access.cidr";
	$smtpd_sender_restrictions[]="check_client_access hash:/etc/postfix/check_client_access";
	
	if($EnablePostfixInternalDomainsCheck==1){
			$smtpd_sender_restrictions[]="reject_unknown_sender_domain";
			$reject_unknown_sender_domain=0;
	
	}
	
	
	
	if($RestrictToInternalDomains==1){
		BuildAllWhitelistedServer();
		BuildAllMyDomains();
		$smtpd_sender_restrictions[]="check_client_access hash:/etc/postfix/all_whitelisted_servers";
		$smtpd_sender_restrictions[]="check_sender_access hash:/etc/postfix/all_internal_domains";
		if($reject_unknown_sender_domain==1){$smtpd_sender_restrictions[]="reject_unknown_sender_domain";}
		if($reject_non_fqdn_sender==1){$smtpd_sender_restrictions[]="reject_non_fqdn_sender";}
		if($smtpd_sender_restrictions_black<>null){$smtpd_sender_restrictions[]=$smtpd_sender_restrictions_black;}
		$smtpd_sender_restrictions[]="reject";
	}else{
		if($reject_unknown_sender_domain==1){$smtpd_sender_restrictions[]="reject_unknown_sender_domain";}
		if($reject_non_fqdn_sender==1){$smtpd_sender_restrictions[]="reject_non_fqdn_sender";}
		if($smtpd_sender_restrictions_black<>null){$smtpd_sender_restrictions[]=$smtpd_sender_restrictions_black;}
	}
	
	
	postconf("smtpd_helo_restrictions",null);
	
	
	if($enforce_helo_restrictions==1){
		$enforce_helo_restrictions_final="permit_mynetworks,permit_sasl_authenticated, check_client_access hash:/etc/postfix/check_client_access, check_client_access cidr:/etc/postfix/check_client_access.cidr,reject_invalid_helo_hostname,reject_unknown_helo_hostname";
		postconf("smtpd_helo_required","yes");
		postconf("smtpd_helo_restrictions",$enforce_helo_restrictions_final);
	}else{
		postconf("smtpd_helo_required","no");
		postconf("smtpd_helo_restrictions","permit_mynetworks,permit_sasl_authenticated, check_client_access hash:/etc/postfix/check_client_access, check_client_access cidr:/etc/postfix/check_client_access.cidr, permit");
	}
	
	
	if(!isset($smtpd_sender_restrictions)){postconf("smtpd_sender_restrictions");return;}
	if(!is_array($smtpd_sender_restrictions)){postconf("smtpd_sender_restrictions");return;}
	
	$final=@implode(",",$smtpd_sender_restrictions);
	postconf("smtpd_sender_restrictions",$final);
	
	
	
	
}

function smtpd_end_of_data_restrictions(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	$EnableArticaPolicyFilter=$sock->GET_INFO("EnableArticaPolicyFilter");
	$EnableArticaPolicyFilter=0;
	$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
	
	$main=new maincf_multi("master");
	$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));
	$ENABLE_POSTFWD2=$array_filters["APP_POSTFWD2"];
	if(!is_numeric($ENABLE_POSTFWD2)){$ENABLE_POSTFWD2=0;}
	
	if($ENABLE_POSTFWD2==1){
		echo "Starting......: ".date("H:i:s")." Postfix Postfwd2 is enabled\n";
		$smtpd_end_of_data_restrictions[]="check_policy_service inet:127.0.0.1:10040";
	}
	
	
	
	if($users->CLUEBRINGER_INSTALLED){
		if($EnableCluebringer==1){
			echo "Starting......: ".date("H:i:s")." Postfix ClueBringer is enabled\n";
			$smtpd_end_of_data_restrictions[]="check_policy_service inet:127.0.0.1:13331";
		}
	}
	
	
	if($EnableArticaPolicyFilter==1){
		$smtpd_end_of_data_restrictions[]="check_policy_service inet:127.0.0.1:54423";
		
	}
	if(isset($smtpd_end_of_data_restrictions)){	
		if(!is_array($smtpd_end_of_data_restrictions)){
			system("{$GLOBALS["postconf"]} -X \"smtpd_end_of_data_restrictions\" >/dev/null 2>&1");
			return;
		}
	}
	$final=@implode(",",$smtpd_end_of_data_restrictions);
	postconf("smtpd_end_of_data_restrictions",$final);
	
}

function BuildAllMyDomains(){
	$ldap=new clladp();
	$hash=$ldap->AllDomains();
	while (list ($num, $ligne) = each ($hash) ){	
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$doms[]="$ligne\tOK";
	}
	
	@file_put_contents("/etc/postfix/all_internal_domains",@implode("\n",$doms));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/all_internal_domains");
	
	
}
function BuildAllWhitelistedServer(){
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$f[]="{$ligne["ipaddr"]}\tOK";
		$f[]="{$ligne["hostname"]}\tOK";
		
		
	}		
	
	@file_put_contents("/etc/postfix/all_whitelisted_servers",@implode("\n",$f));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/all_whitelisted_servers");

}

function fix_postdrop_perms(){
	$unix=new unix();
	$postfix_bin=$unix->find_program("postfix");
	$chgrp_bin=$unix->find_program("chgrp");
	$killall_bin=$unix->find_program("killall");
	shell_exec("$postfix_bin stop 2>&1");
	shell_exec("$killall_bin -9 postdrop 2>&1");
	shell_exec("$chgrp_bin -R postdrop /var/spool/postfix/public 2>&1");
	shell_exec("$chgrp_bin -R postdrop /var/spool/postfix/maildrop/ 2>&1");
	shell_exec("$postfix_bin check 2>&1");
	shell_exec("$postfix_bin start 2>&1");
	
	
}

function postscreen($hostname=null){
	
	if($GLOBALS["EnablePostfixMultiInstance"]==1){
		echo "Starting......: ".date("H:i:s")." PostScreen multiple instances, running for -> $hostname\n";
		shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --postscreen $hostname");
	}	
	$permit_mynetworks=null;
	$user=new usersMenus();
	if(!$user->POSTSCREEN_INSTALLED){echo "Starting......: ".date("H:i:s")." PostScreen is not installed, you should upgrade to 2.8 postfix version\n";return;}
	$main=new maincf_multi("master","master");
	$EnablePostScreen=$main->GET("EnablePostScreen");
	$sock=new sockets();
	$TrustMyNetwork=$sock->GET_INFO("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	
	if($EnablePostScreen<>1){echo "Starting......: ".date("H:i:s")." PostScreen is not enabled\n";return;}
	echo "Starting......: ".date("H:i:s")." PostScreen configuring....\n";
	if(!is_file("/etc/postfix/postscreen_access.cidr")){@file_put_contents("/etc/postfix/postscreen_access.cidr","#");}
	if(!is_file("/etc/postfix/postscreen_access.hosts")){@file_put_contents("/etc/postfix/postscreen_access.hosts"," ");}
	if($TrustMyNetwork==1){$permit_mynetworks="permit_mynetworks,";}
	
	postconf("postscreen_access_list","{$permit_mynetworks}cidr:/etc/postfix/postscreen_access.cidr");
	
	
	$postscreen_bare_newline_action=$main->GET("postscreen_bare_newline_action");
	$postscreen_bare_newline_enable=$main->GET("postscreen_bare_newline_enable");
	
	$postscreen_bare_newline_ttl=$main->GET("postscreen_bare_newline_ttl");
	$postscreen_cache_cleanup_interval=$main->GET("postscreen_cache_cleanup_interval");
	$postscreen_cache_retention_time=$main->GET("postscreen_cache_retention_time");
	$postscreen_client_connection_count_limit=$main->GET("postscreen_client_connection_count_limit");
	$postscreen_pipelining_enable=$main->GET("postscreen_pipelining_enable");
	$postscreen_pipelining_action=$main->GET("postscreen_pipelining_action");
	$postscreen_pipelining_ttl=$main->GET("postscreen_pipelining_ttl");
	$postscreen_post_queue_limit=$main->GET("postscreen_post_queue_limit");
	$postscreen_pre_queue_limit=$main->GET("postscreen_pre_queue_limit");
	$postscreen_non_smtp_command_enable=$main->GET("postscreen_non_smtp_command_enable");
	$postscreen_non_smtp_command_action=$main->GET("postscreen_non_smtp_command_action");
	$postscreen_non_smtp_command_ttl=$main->GET("postscreen_non_smtp_command_ttl");
	$postscreen_forbidden_commands=$main->GET("postscreen_forbidden_command");
	$postscreen_dnsbl_action=$main->GET("postscreen_dnsbl_action");
	$postscreen_dnsbl_ttl=$main->GET("postscreen_dnsbl_ttl");
	$postscreen_dnsbl_threshold=$main->GET("postscreen_dnsbl_threshold");	
	
	
	if($postscreen_bare_newline_action==null){$postscreen_bare_newline_action="ignore";}
	if(!is_numeric($postscreen_bare_newline_enable)){$postscreen_bare_newline_enable="0";}
	if($postscreen_bare_newline_ttl==null){$postscreen_bare_newline_ttl="30d";}
	if($postscreen_cache_cleanup_interval==null){$postscreen_cache_cleanup_interval="12h";}
	if($postscreen_cache_retention_time==null){$postscreen_cache_retention_time="7d";}
	if($postscreen_client_connection_count_limit==null){$postscreen_client_connection_count_limit="50";}
	if($postscreen_pipelining_enable==null){$postscreen_pipelining_enable="0";}
	if($postscreen_pipelining_action==null){$postscreen_pipelining_action="ignore";}
	if($postscreen_pipelining_ttl==null){$postscreen_pipelining_ttl="30d";}			
	if($postscreen_post_queue_limit==null){$postscreen_post_queue_limit="100";}
	if($postscreen_pre_queue_limit==null){$postscreen_pre_queue_limit="100";}
	
	if($postscreen_non_smtp_command_enable==null){$postscreen_non_smtp_command_enable="0";}
	if($postscreen_non_smtp_command_action==null){$postscreen_non_smtp_command_action="drop";}
	if($postscreen_non_smtp_command_ttl==null){$postscreen_non_smtp_command_ttl="30d";}
	if($postscreen_forbidden_commands==null){$postscreen_forbidden_commands="CONNECT, GET, POST";}
	if($postscreen_dnsbl_action==null){$postscreen_dnsbl_action="ignore";}
	if($postscreen_dnsbl_action==null){$postscreen_dnsbl_action="ignore";}
	if($postscreen_dnsbl_ttl==null){$postscreen_dnsbl_ttl="1h";}
	if($postscreen_dnsbl_threshold==null){$postscreen_dnsbl_threshold="1";}
	
	if($postscreen_bare_newline_enable==1){$postscreen_bare_newline_enable="yes";}else{$postscreen_bare_newline_enable="no";}
	if($postscreen_pipelining_enable==1){$postscreen_pipelining_enable="yes";}else{$postscreen_pipelining_enable="no";}
	if($postscreen_non_smtp_command_enable==1){$postscreen_non_smtp_command_enable="yes";}else{$postscreen_non_smtp_command_enable="no";}
	
	
	postconf("postscreen_bare_newline_action",$postscreen_bare_newline_action);
	postconf("postscreen_bare_newline_enable",$postscreen_bare_newline_enable);
	postconf("postscreen_bare_newline_ttl",$postscreen_bare_newline_ttl);
	postconf("postscreen_cache_cleanup_interval",$postscreen_cache_cleanup_interval);
	postconf("postscreen_cache_retention_time",$postscreen_cache_retention_time);
	postconf("postscreen_client_connection_count_limit",$postscreen_client_connection_count_limit);
	postconf("postscreen_client_connection_count_limit",$postscreen_client_connection_count_limit);
	postconf("postscreen_pipelining_enable",$postscreen_pipelining_enable);
	postconf("postscreen_pipelining_action",$postscreen_pipelining_action);
	postconf("postscreen_pipelining_ttl",$postscreen_pipelining_ttl);
	postconf("postscreen_post_queue_limit",$postscreen_post_queue_limit);
	postconf("postscreen_pre_queue_limit",$postscreen_pre_queue_limit);
	postconf("postscreen_non_smtp_command_enable",$postscreen_non_smtp_command_enable);
	postconf("postscreen_non_smtp_command_action",$postscreen_non_smtp_command_action);
	postconf("postscreen_non_smtp_command_ttl",$postscreen_non_smtp_command_ttl);
	postconf("postscreen_forbidden_command",$postscreen_forbidden_commands);
	postconf("postscreen_dnsbl_action",$postscreen_dnsbl_action);
	postconf("postscreen_dnsbl_ttl",$postscreen_dnsbl_ttl);
	postconf("postscreen_dnsbl_threshold",$postscreen_dnsbl_threshold);
	postconf("postscreen_cache_map","btree:/var/lib/postfix/postscreen_master_cache");
	
	
	
	
	$dnsbl_array=unserialize(base64_decode($main->GET_BIGDATA("postscreen_dnsbl_sites")));
	if(is_array($dnsbl_array)){
		while (list ($site, $threshold) = each ($dnsbl_array) ){if($site==null){continue;}$dnsbl_array_compiled[]="$site*$threshold";}
	}
		
	$final_dnsbl=null;
	if(is_array($dnsbl_array_compiled)){$final_dnsbl=@implode(",",$dnsbl_array_compiled);}
	postconf("postscreen_dnsbl_sites",$final_dnsbl);
	
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	$nets=array();
	$hostsname=array();
	$ldap=new clladp();
	$ipClass=new IP();	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$ligne["ipaddr"]=trim($ligne["ipaddr"]);
		$ligne["hostname"]=trim($ligne["hostname"]);
			
		if($ligne["hostname"]==null){continue;}
		if($ligne["ipaddr"]==null){continue;}
			
		if(!$ipClass->isIPAddress($ligne["hostname"])){
			$hostsname[]="{$ligne["hostname"]}\tOK";
		}else{
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne["hostname"])){
				$nets[]="{$ligne["hostname"]}\tdunno";
			}
		}
		
		if(!$ipClass->isIPAddress($ligne["ipaddr"])){
			$hostsname[]="{$ligne["ipaddr"]}\tOK";
		}else{
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne["ipaddr"])){
				$nets[]="{$ligne["ipaddr"]}\tdunno";
			}
		}		
		
	}		
	

		
		
	

	$networks=$ldap->load_mynetworks();	
	if(is_array($networks)){
		while (list ($num, $ligne) = each ($networks) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!$ipClass->isIPAddress($ligne)){
				$hostsname[]="$ligne\tOK";
			}else{
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne)){
					$nets[]="$ligne\tdunno";
				}
			}
		}
	}
	
	$postfix_global_whitelist_to_mx=$main->postfix_global_whitelist_to_mx();
	if(count($postfix_global_whitelist_to_mx)>0){
		while (list ($num, $ligne) = each ($postfix_global_whitelist_to_mx) ){
			$nets[]="$ligne\tdunno";
		}
		
	}
	
	@unlink("/etc/postfix/postscreen_access.hosts");
	@unlink("/etc/postfix/postscreen_access.cidr");
	
	if(count($hostsname)>0){
		@file_put_contents("/etc/postfix/postscreen_access.hosts",@implode("\n",$hostsname));
		$postscreen_access=",hash:/etc/postfix/postscreen_access.hosts";
	}
	if(!is_file("/etc/postfix/postscreen_access.hosts")){@file_put_contents("/etc/postfix/postscreen_access.hosts", "\n");}
	
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/postscreen_access.hosts >/dev/null 2>&1");
	
	if(count($nets)>0){@file_put_contents("/etc/postfix/postscreen_access.cidr",@implode("\n",$nets));}
	postconf("postscreen_access_list","permit_mynetworks,cidr:/etc/postfix/postscreen_access.cidr$postscreen_access");
	
	MasterCFBuilder();
	}
	
function MasterCF_DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid){	
	$main=new maincf_multi("master","master");
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	$caches=$array[$uuid]["smtp-instance-cache-destinations"];
	if(count($caches)==0){return null;}
	while (list ($domain, $none) = each ($caches) ){if(trim($domain)<>null){$f[]="$domain\tOK";}}
	@file_put_contents("/etc/postfix/{$uuid}_CONNECTION_CACHE_DESTINATIONS", implode("\n", $f));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/{$uuid}_CONNECTION_CACHE_DESTINATIONS >/dev/null 2>&1");
	return "smtp_connection_cache_destinations=hash:/etc/postfix/{$uuid}_CONNECTION_CACHE_DESTINATIONS";
}
	
function MasterCF_DOMAINS_THROTTLE(){
	$main=new maincf_multi("master","master");
	$array=unserialize(base64_decode($main->GET_BIGDATA("domain_throttle_daemons_list")));	
	
	$f=explode("\n",@file_get_contents("/etc/postfix/main.cf"));
	if(!is_array($f)){$f=array();}
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#^[0-9]+_destination#",$line)){continue;}
		if(preg_match("#^[0-9]+_delivery_#",$line)){continue;}
		if(preg_match("#^[0-9]+_initial_#",$line)){continue;}
		$new[]=$line;
	}
	if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: Cleaning main.cf done..\n";}
	@file_put_contents("/etc/postfix/main.cf",@implode("\n",$new));
	unset($new);
	
	
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: Not An Array line ". __LINE__."\n";}
		return null;
	}
	
	while (list ($uuid, $conf) = each ($array) ){
		if($conf["ENABLED"]<>1){continue;}
		if(count($conf["DOMAINS"])==0){continue;}
		$maps=array();
		if($conf["transport_destination_concurrency_failed_cohort_limit"]==null){$conf["transport_destination_concurrency_failed_cohort_limit"]=1;}
		if($conf["transport_delivery_slot_loan"]==null){$conf["transport_delivery_slot_loan"]=3;}
		if($conf["transport_delivery_slot_discount"]==null){$conf["transport_delivery_slot_discount"]=50;}
		if($conf["transport_delivery_slot_cost"]==null){$conf["transport_delivery_slot_cost"]=5;}
		if($conf["transport_extra_recipient_limit"]==null){$conf["transport_extra_recipient_limit"]=1000;}
		if($conf["transport_initial_destination_concurrency"]==null){$conf["transport_initial_destination_concurrency"]=5;}
		if($conf["transport_destination_recipient_limit"]==null){$conf["transport_destination_recipient_limit"]=50;}		
		if($conf["transport_destination_concurrency_limit"]==null){$conf["transport_destination_concurrency_limit"]=20;}
		if($conf["transport_destination_rate_delay"]==null){$conf["transport_destination_rate_delay"]="0s";}
		if(!is_numeric($conf["default_process_limit"])){$conf["default_process_limit"]=100;}
		$moinso["{$uuid}_destination_concurrency_failed_cohort_limit"]="{$conf["transport_destination_concurrency_failed_cohort_limit"]}";
		$moinso["{$uuid}_delivery_slot_loan"]="{$conf["transport_delivery_slot_loan"]}";
		$moinso["{$uuid}_delivery_slot_discount"]="{$conf["transport_delivery_slot_discount"]}";
		$moinso["{$uuid}_delivery_slot_cost"]="{$conf["transport_delivery_slot_cost"]}";
		$moinso["{$uuid}_initial_destination_concurrency"]="{$conf["transport_initial_destination_concurrency"]}";
		$moinso["{$uuid}_destination_recipient_limit"]="{$conf["transport_destination_recipient_limit"]}";
		$moinso["{$uuid}_destination_concurrency_limit"]="{$conf["transport_destination_concurrency_limit"]}";
		$moinso["{$uuid}_destination_rate_delay"]="{$conf["transport_destination_rate_delay"]}";
		
		
		$moinsoMasterText=null;
		if(is_numeric($conf["smtp_connection_cache_on_demand"])){
			if($conf["smtp_connection_cache_on_demand"]==0){
				$moinsoMaster[]="smtp_connection_cache_on_demand=no";
			}else{
				$moinsoMaster[]="smtp_connection_cache_on_demand=yes";
				$moinsoMaster[]="smtp_connection_cache_time_limit={$conf["smtp_connection_cache_time_limit"]}";
				$moinsoMaster[]="smtp_connection_reuse_time_limit={$conf["smtp_connection_reuse_time_limit"]}";
				$cache_destinations=MasterCF_DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid);
				if($cache_destinations<>null){$moinsoMaster[]=$cache_destinations;}
			}
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"{$conf["smtp_connection_cache_on_demand"]}\" is not a numeric\n";}
		}
		
		if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"". count($moinsoMaster)." value(s)\n";}
		if(count($moinsoMaster)>0){$moinsoMasterText=" -o ".@implode(" -o ", $moinsoMaster);}		
		
		
		$instances[]="\n# THROTTLE {$conf["INSTANCE_NAME"]}\n$uuid\tunix\t-\t-\tn\t-\t{$conf["default_process_limit"]}\tsmtp$moinsoMasterText";
		while (list ($domain, $null) = each ($conf["DOMAINS"]) ){$maps[$domain]="$uuid:";}
		while (list ($a, $b) = each ($maps) ){$maps_final[]="$a\t$b";}
	}
	
	if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: ". count($moinso)." main.cf command lines\n";}
	if(is_array($moinso)){
		while (list ($key, $val) = each ($moinso) ){
			postconf($key,$val);
		}
	}
	
	if(!is_array($instances)){return null;}
	@file_put_contents("/etc/postfix/transport.throttle",@implode("\n",$maps_final)."\n");
	return @implode("\n",$instances)."\n";
	
	
}

function debug_peer_list(){
	$main=new maincf_multi("master");
	$datas=unserialize(base64_decode($main->GET_BIGDATA("debug_peer_list")));
	
	if(count($datas)==0){
		postconf("debug_peer_level",2);
		postconf("debug_peer_list",null);
		return;
	}
	while (list ($index, $file) = each ($datas)){
			if(trim($index)==null){continue;}
			$f[]=$index;
		}
		
		if(count($f)>0){
			postconf("debug_peer_level",3);
			postconf("debug_peer_list",@implode(",", $f));
			
		}	
	
	
}

function haproxy_compliance(){
	$main=new maincf_multi("master");
	$EnablePostfixHaProxy=$main->GET("EnablePostfixHaProxy");
	if(!is_numeric($EnablePostfixHaProxy)){$EnablePostfixHaProxy=0;}	
	
	$users=new usersMenus();
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $users->POSTFIX_VERSION,$re)){
		$major=intval($re[1]);
		$minor=intval($re[2]);
		$binver="{$major}{$minor}";
		if($EnablePostfixHaProxy==1){
			if($binver<210){echo "Starting......: ".date("H:i:s")." HaProxy compliance: require 2.10 minimal.\n";return;}
		}
		
	}
	
	if($EnablePostfixHaProxy==0){
		echo "Starting......: ".date("H:i:s")." HaProxy compliance: disabled\n";
		postconf("postscreen_upstream_proxy_protocol",null);
		postconf("smtpd_upstream_proxy_protocol",null);
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." HaProxy compliance: enabled\n";
	$EnablePostScreen=$main->GET("EnablePostScreen");
	if(!is_numeric($EnablePostScreen)){$EnablePostScreen=0;}	
	if(!$users->POSTSCREEN_INSTALLED){$EnablePostScreen=0;}
	
	if($EnablePostScreen==1){
		echo "Starting......: ".date("H:i:s")." HaProxy compliance: enabled + PostScreen\n";
		postconf("postscreen_upstream_proxy_protocol","haproxy");
		postconf("smtpd_upstream_proxy_protocol",null);
	}else{
		echo "Starting......: ".date("H:i:s")." HaProxy compliance: enabled + SMTPD\n";
		postconf("postscreen_upstream_proxy_protocol",null);
		postconf("smtpd_upstream_proxy_protocol","haproxy");
	}

}


function ScanLibexec(){
	if(!is_dir("/usr/lib/postfix")){return;}
	if(!is_dir("/usr/libexec/postfix")){return;}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	
	$files=$unix->DirFiles("/usr/libexec/postfix");
	while (list ($filename, $MFARRY) = each ($files) ){
		if(!is_link("/usr/lib/postfix/$filename")){
			if(!is_link("/usr/libexec/postfix/$filename")){
				@unlink("/usr/lib/postfix/$filename");
				echo "Starting......: ".date("H:i:s")." linking $filename\n";
				shell_exec("$ln -sf /usr/libexec/postfix/$filename /usr/lib/postfix/$filename");
			}
		}
		
	}
	
	
	
	
}

function build_progress_sender_routing($text,$pourc){
	if(!$GLOBALS["PROGRESS_SENDER_DEPENDENT"]){return;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_sender_routing";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}




function MasterCFBuilder($restart_service=false){
	$smtp_ssl=null;
	if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	if(!is_object($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();$sock=$GLOBALS["CLASS_SOCKET"];}else{$sock=$GLOBALS["CLASS_SOCKET"];}
	$EnableArticaSMTPFilter=$sock->GET_INFO("EnableArticaSMTPFilter");
	$EnableArticaSMTPFilter=0;
	$EnableAmavisInMasterCF=intval($sock->GET_INFO('EnableAmavisInMasterCF'));
	$EnableAmavisDaemon=intval($sock->GET_INFO('EnableAmavisDaemon'));
	$PostfixEnableMasterCfSSL=$sock->GET_INFO("PostfixEnableMasterCfSSL");
	$ArticaFilterMaxProc=$sock->GET_INFO("ArticaFilterMaxProc");
	$PostfixEnableSubmission=$sock->GET_INFO("PostfixEnableSubmission");
	$EnableASSP=$sock->GET_INFO('EnableASSP');
	$PostfixBindInterfacePort=$sock->GET_INFO("PostfixBindInterfacePort");
	$TrustMyNetwork=$sock->GET_INFO("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	
	$user=new usersMenus();
	$main=new maincf_multi("master","master");
	$EnablePostScreen=$main->GET("EnablePostScreen");
	$postscreen_line=null;
	$tlsproxy=null;
	$dnsblog=null;
	$re_cleanup_infos=null;
	$smtp_submission=null;
	$pre_cleanup_addons=null;
	$master=new master_cf(1,"master");
	
	$ver210=false;
	$users=new usersMenus();
	echo "Starting......: ".date("H:i:s")." Postfix master version: $users->POSTFIX_VERSION\n";
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $users->POSTFIX_VERSION,$re)){
		$major=intval($re[1]);
		$minor=intval($re[2]);
		$binver=intval("{$major}{$minor}");
		if($binver >= 210){
			echo "Starting......: ".date("H:i:s")." Postfix master version: 2.10 [$binver] OK\n";
			$ver210=true;}
	}	
	
	
	$MASTER_CF_DEFINED=$master->GetArray();
	
	if($EnablePostScreen==null){$EnablePostScreen=0;}	
	if(!$user->POSTSCREEN_INSTALLED){$EnablePostScreen=0;}
	
	if($EnablePostScreen==1){$PostfixEnableSubmission=1;}
	
	
	$ADD_PRECLEANUP=false;
	$TLSSET=false;
	
	if($GLOBALS["EnablePostfixMultiInstance"]==1){
		$EnableAmavisDaemon=0;
		$PostfixEnableMasterCfSSL=0;
	}
	
	if(!is_numeric($PostfixBindInterfacePort)){	$PostfixBindInterfacePort=25;}
	if($EnableAmavisDaemon==0){$EnableAmavisInMasterCF=0;}
	if(!is_numeric($PostfixEnableSubmission)){$PostfixEnableSubmission=0;}
	if(!is_numeric($EnableASSP)){$EnableASSP=0;}
	
	
	shell_exec("{$GLOBALS["postconf"]} -X \"content_filter\" >/dev/null 2>&1");
	
	
		
	build_progress_sender_routing("{building} Master.cf",35);

	
	if($EnableAmavisInMasterCF==1){
		build_progress_sender_routing("{building} Amavis hooks",40);
		$MasterCFAmavisInstancesCount=intval($sock->GET_INFO("MasterCFAmavisInstancesCount"));
		if($MasterCFAmavisInstancesCount==0){$MasterCFAmavisInstancesCount="-";}
		if($MasterCFAmavisInstancesCount<0){$MasterCFAmavisInstancesCount="-";}
		$ADD_PRECLEANUP=true;
		echo "Starting......: ".date("H:i:s")." Amavis is enabled using post-queue mode\n";
		
		shell_exec("{$GLOBALS["postconf"]} -e \"content_filter = amavis:[127.0.0.1]:10024\" >/dev/null 2>&1");
	
		
		
		
		echo "Starting......: ".date("H:i:s")." Amavis max process: $MasterCFAmavisInstancesCount\n";	
		
		if(isset($MASTER_CF_DEFINED["amavis"])){unset($MASTER_CF_DEFINED["amavis"]);}
		
		$amavis[]="amavis\tunix\t-\t-\t-\t-\t$MasterCFAmavisInstancesCount\tsmtp";
		$amavis[]=" -o smtp_data_done_timeout=1200";
		$amavis[]=" -o smtp_send_xforward_command=yes";
		$amavis[]=" -o disable_dns_lookups=yes";
		$amavis[]=" -o smtp_generic_maps=";
		$amavis[]=" -o smtpd_sasl_auth_enable=no"; 
		$amavis[]=" -o smtpd_use_tls=no";
		$amavis[]=" -o max_use=20";				
		$amavis[]="";
		$amavis[]="";
		
		if(isset($MASTER_CF_DEFINED["127.0.0.1:10025"])){unset($MASTER_CF_DEFINED["127.0.0.1:10025"]);}
		$amavis[]="127.0.0.1:10025\tinet\tn\t-\tn\t-\t-\tsmtpd";
		$amavis[]=" -o local_recipient_maps=";
		$amavis[]=" -o relay_recipient_maps=";
		$amavis[]=" -o smtpd_restriction_classes=";
		$amavis[]=" -o smtpd_client_restrictions=";
		$amavis[]=" -o smtpd_helo_restrictions=";
		$amavis[]=" -o smtpd_sender_restrictions=";
		$artica[]=" -o smtpd_end_of_data_restrictions=";
		$amavis[]=" -o smtp_generic_maps=";
		$amavis[]=" -o smtpd_recipient_restrictions=permit_mynetworks,reject";
		$amavis[]=" -o mynetworks=127.0.0.0/8";
		$amavis[]=" -o mynetworks_style=host";
		$amavis[]=" -o strict_rfc821_envelopes=yes";
		$amavis[]=" -o smtpd_error_sleep_time=0";
		$amavis[]=" -o smtpd_soft_error_limit=1001";
		$amavis[]=" -o smtpd_hard_error_limit=1000";
		$amavis[]=" -o receive_override_options=no_header_body_checks";	
		$amavis[]="	-o smtpd_sasl_auth_enable=no"; 
		$amavis[]=" -o smtpd_milters=";
		if($ver210){
		$amavis[]="	-o smtpd_upstream_proxy_protocol=";
		}
		$amavis[]="	-o smtpd_use_tls=no";
		$master_amavis=@implode("\n",$amavis);

	}	
	
	if($ADD_PRECLEANUP){
		echo "Starting......: ".date("H:i:s")." Enable pre-cleanup service...\n";
		$pre_cleanup_addons=" -o smtp_generic_maps= -o canonical_maps= -o sender_canonical_maps= -o recipient_canonical_maps= -o masquerade_domains= -o recipient_bcc_maps= -o sender_bcc_maps=";
		$re_cleanup_infos  =" -o cleanup_service_name=pre-cleanup";
	}	
	$permit_mynetworks=null;
	
	if($PostfixEnableMasterCfSSL==1){
		if($TrustMyNetwork==1){$permit_mynetworks="permit_mynetworks,";}
		echo "Starting......: ".date("H:i:s")." Enabling SSL (465 port)\n";
		SetTLS();
		$TLSSET=true;
		if(isset($MASTER_CF_DEFINED["smtps"])){unset($MASTER_CF_DEFINED["smtps"]);}
		$SSL_INSTANCE[]="smtps\tinet\tn\t-\tn\t-\t-\tsmtpd";
		if($re_cleanup_infos<>null){$SSL_INSTANCE[]=$re_cleanup_infos;}
		$SSL_INSTANCE[]=" -o smtpd_tls_wrappermode=yes";
		$SSL_INSTANCE[]=" -o smtpd_delay_reject=yes";
		//$SSL_INSTANCE[]=" -o smtpd_client_restrictions={$permit_mynetworks}permit_sasl_authenticated,reject\n";
		//$SSL_INSTANCE[]=" -o smtpd_sender_restrictions=permit_sasl_authenticated,reject";
		//$SSL_INSTANCE[]=" -o smtpd_helo_restrictions=permit_sasl_authenticated,reject";
		//$SSL_INSTANCE[]=" -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject";		
		$smtp_ssl=@implode("\n",$SSL_INSTANCE);
	}else{
		echo "Starting......: ".date("H:i:s")." SSL (465 port) Disabled\n";
	}

	if($PostfixEnableSubmission==1){
		echo "Starting......: ".date("H:i:s")." Enabling submission (587 port)\n";
		if(isset($MASTER_CF_DEFINED["submission"])){unset($MASTER_CF_DEFINED["submission"]);}
		if(!$TLSSET){SetTLS();}
		$TLSSET=true;
		$SUBMISSION_INSTANCE[]="submission\tinet\tn\t-\tn\t-\t-\tsmtpd";
		if($re_cleanup_infos<>null){$SUBMISSION_INSTANCE[]=$re_cleanup_infos;}
		$SUBMISSION_INSTANCE[]=" -o smtpd_etrn_restrictions=reject";
		$SUBMISSION_INSTANCE[]=" -o smtpd_enforce_tls=yes";
		$SUBMISSION_INSTANCE[]=" -o smtpd_sasl_auth_enable=yes";
		$SUBMISSION_INSTANCE[]=" -o smtpd_delay_reject=yes";
		$SUBMISSION_INSTANCE[]=" -o smtpd_client_restrictions=permit_sasl_authenticated,reject";
		$SUBMISSION_INSTANCE[]=" -o smtpd_sender_restrictions=permit_sasl_authenticated,reject";
		$SUBMISSION_INSTANCE[]=" -o smtpd_helo_restrictions=permit_sasl_authenticated,reject";
		$SUBMISSION_INSTANCE[]=" -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject";
		$SUBMISSION_INSTANCE[]=" -o smtp_generic_maps=";
		$SUBMISSION_INSTANCE[]=" -o sender_canonical_maps=";
		$smtp_submission=@implode("\n",$SUBMISSION_INSTANCE);
		
	}else{
		echo "Starting......: ".date("H:i:s")." submission (587 port) Disabled\n";
	}
	
	if($PostfixBindInterfacePort==25){
		$postfix_listen_port="smtp";
		$postscreen_listen_port="smtp";		
	}else{
		$postfix_listen_port=$PostfixBindInterfacePort;
		$postscreen_listen_port=$PostfixBindInterfacePort;		
	}
	
	
	echo "Starting......: ".date("H:i:s")." Postfix intended to listen SMTP Port $postfix_listen_port\n";
	$smtp_in_proto="inet";
	$smtp_private="n";
	
	
	
	if($EnableASSP==1){
		echo "Starting......: ".date("H:i:s")." ASSP is enabled change postfix listen port to 127.0.0.1:26\n";
		$postfix_listen_port="127.0.0.1:6000";
		$postscreen_listen_port="127.0.0.1:6000";
	}
	
	
	if($EnablePostScreen==1){
		if(isset($MASTER_CF_DEFINED["tlsproxy"])){unset($MASTER_CF_DEFINED["tlsproxy"]);}
		if(isset($MASTER_CF_DEFINED["dnsblog"])){unset($MASTER_CF_DEFINED["dnsblog"]);}
		echo "Starting......: ".date("H:i:s")." PostScreen is enabled, users should use 587 port to send mails internally\n"; 
		$smtp_in_proto="pass";
		$smtp_private="-";
		if($postfix_listen_port=="smtp"){$postfix_listen_port="smtpd";}
		$postscreen_line="$postscreen_listen_port\tinet\tn\t-\tn\t-\t1\tpostscreen -o soft_bounce=yes";
		$tlsproxy="tlsproxy\tunix\t-\t-\tn\t-\t0\ttlsproxy";
		$dnsblog="dnsblog\tunix\t-\t-\tn\t-\t0\tdnsblog";
		}else{
			echo "Starting......: ".date("H:i:s")." PostScreen is disabled\n";
		}
	
if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." run MasterCF_DOMAINS_THROTTLE()\n";}	
build_progress_sender_routing("{building} DOMAINS_THROTTLE",45);
$smtp_throttle=MasterCF_DOMAINS_THROTTLE();

// http://www.ijs.si/software/amavisd/README.postfix.html	
$conf[]="#";
$conf[]="# Postfix master process configuration file.  For details on the format";
$conf[]="# of the file, see the master(5) manual page (command: \"man 5 master\").";
$conf[]="#";
$conf[]="# ==========================================================================";
$conf[]="# service type  private unpriv  chroot  wakeup  maxproc command + args";
$conf[]="#               (yes)   (yes)   (yes)   (never) (100)";
$conf[]="# ==========================================================================";
if(isset($MASTER_CF_DEFINED[$postfix_listen_port])){unset($MASTER_CF_DEFINED[$postfix_listen_port]);}
if($postscreen_line<>null){$conf[]=$postscreen_line;}
if($tlsproxy<>null){$conf[]=$tlsproxy;}
if($dnsblog<>null){$conf[]=$dnsblog;}
$conf[]="$postfix_listen_port\t$smtp_in_proto\t$smtp_private\t-\tn\t-\t-\tsmtpd$re_cleanup_infos";
if($smtp_ssl<>null){$conf[]=$smtp_ssl;}
if($smtp_submission<>null){$conf[]=$smtp_submission;}
if($smtp_throttle<>null){$conf[]=$smtp_throttle;}
if(isset($MASTER_CF_DEFINED["pickup"])){unset($MASTER_CF_DEFINED["pickup"]);}
if(isset($MASTER_CF_DEFINED["cleanup"])){unset($MASTER_CF_DEFINED["cleanup"]);}
if(isset($MASTER_CF_DEFINED["mailman"])){unset($MASTER_CF_DEFINED["mailman"]);}
if(count($MASTER_CF_DEFINED)==0){
	$conf[]="pickup\tfifo\tn\t-\tn\t60\t1\tpickup$re_cleanup_infos";
	$conf[]="cleanup\tunix\tn\t-\tn\t-\t0\tcleanup";
	$conf[]="pre-cleanup\tunix\tn\t-\tn\t-\t0\tcleanup$pre_cleanup_addons";
	$conf[]="qmgr\tfifo\tn\t-\tn\t300\t1\tqmgr";
	$conf[]="tlsmgr\tunix\t-\t-\tn\t1000?\t1\ttlsmgr";
	$conf[]="rewrite\tunix\t-\t-\tn\t-\t-\ttrivial-rewrite";
	$conf[]="bounce\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="defer\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="trace\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="verify\tunix\t-\t-\tn\t-\t1\tverify";
	$conf[]="flush\tunix\tn\t-\tn\t1000?\t0\tflush";
	$conf[]="proxymap\tunix\t-\t-\tn\t-\t-\tproxymap";
	$conf[]="proxywrite\tunix\t-\t-\tn\t-\t1\tproxymap";
	$conf[]="smtp\tunix\t-\t-\tn\t-\t-\tsmtp";
	
	$conf[]="relay\tunix\t-\t-\tn\t-\t-\tsmtp -o fallback_relay=";
	$conf[]="showq\tunix\tn\t-\tn\t-\t-\tshowq";
	$conf[]="error\tunix\t-\t-\tn\t-\t-\terror";
	$conf[]="discard\tunix\t-\t-\tn\t-\t-\tdiscard";
	$conf[]="local\tunix\t-\tn\tn\t-\t-\tlocal";
	$conf[]="virtual\tunix\t-\tn\tn\t-\t-\tvirtual";
	$conf[]="lmtp\tunix\t-\t-\tn\t-\t-\tlmtp";
	$conf[]="anvil\tunix\t-\t-\tn\t-\t1\tanvil";
	$conf[]="scache\tunix\t-\t-\tn\t-\t1\tscache";
	$conf[]="scan\tunix\t-\t-\tn\t\t-\t10\tsm -v";
	$conf[]="maildrop\tunix\t-\tn\tn\t-\t-\tpipe ";
	$conf[]="retry\tunix\t-\t-\tn\t-\t-\terror ";
	$conf[]="uucp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fqhu user=uucp argv=uux -r -n -z -a\$sender - \$nexthop!rmail (\$recipient)";
	$conf[]="ifmail\tunix\t-\tn\tn\t-\t-\tpipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r \$nexthop (\$recipient)";
	$conf[]="bsmtp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t\$nexthop -f\$sender \$recipient";
}else{
	if(!isset($MASTER_CF_DEFINED["pickup"])){ $conf[]="pickup\tfifo\tn\t-\tn\t60\t1\tpickup$re_cleanup_infos"; }
	if(!isset($MASTER_CF_DEFINED["cleanup"])){ $conf[]="cleanup\tunix\tn\t-\tn\t-\t0\tcleanup"; }
	if(!isset($MASTER_CF_DEFINED["pre-cleanup"])){ $conf[]="pre-cleanup\tunix\tn\t-\tn\t-\t0\tcleanup$pre_cleanup_addons"; }
	if(!isset($MASTER_CF_DEFINED["qmgr"])){ $conf[]="qmgr\tfifo\tn\t-\tn\t300\t1\tqmgr"; }
	if(!isset($MASTER_CF_DEFINED["rewrite"])){ $conf[]="rewrite\tunix\t-\t-\tn\t-\t-\ttrivial-rewrite"; }
	if(!isset($MASTER_CF_DEFINED["bounce"])){ $conf[]="bounce\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["defer"])){ $conf[]="defer\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["trace"])){ $conf[]="trace\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["verify"])){ $conf[]="verify\tunix\t-\t-\tn\t-\t1\tverify";}
	if(!isset($MASTER_CF_DEFINED["flush"])){ $conf[]="flush\tunix\tn\t-\tn\t1000?\t0\tflush"; } 
	if(!isset($MASTER_CF_DEFINED["proxymap"])){ $conf[]="proxymap\tunix\t-\t-\tn\t-\t-\tproxymap"; }
	if(!isset($MASTER_CF_DEFINED["proxywrite"])){ $conf[]="proxywrite\tunix\t-\t-\tn\t-\t1\tproxymap";}
	if(!isset($MASTER_CF_DEFINED["smtp"])){ $conf[]="smtp\tunix\t-\t-\tn\t-\t-\tsmtp"; }
	
	if(!isset($MASTER_CF_DEFINED["relay"])){$conf[]="relay\tunix\t-\t-\tn\t-\t-\tsmtp -o fallback_relay=";;}
	if(!isset($MASTER_CF_DEFINED["showq"])){$conf[]="showq\tunix\tn\t-\tn\t-\t-\tshowq";;}
	if(!isset($MASTER_CF_DEFINED["error"])){$conf[]="error\tunix\t-\t-\tn\t-\t-\terror";;}
	if(!isset($MASTER_CF_DEFINED["discard"])){$conf[]="discard\tunix\t-\t-\tn\t-\t-\tdiscard";;}
	if(!isset($MASTER_CF_DEFINED["local"])){$conf[]="local\tunix\t-\tn\tn\t-\t-\tlocal";;}
	if(!isset($MASTER_CF_DEFINED["virtual"])){$conf[]="virtual\tunix\t-\tn\tn\t-\t-\tvirtual";;}
	if(!isset($MASTER_CF_DEFINED["lmtp"])){$conf[]="lmtp\tunix\t-\t-\tn\t-\t-\tlmtp";;}
	if(!isset($MASTER_CF_DEFINED["anvil"])){$conf[]="anvil\tunix\t-\t-\tn\t-\t1\tanvil";;}
	if(!isset($MASTER_CF_DEFINED["scache"])){$conf[]="scache\tunix\t-\t-\tn\t-\t1\tscache";;}
	if(!isset($MASTER_CF_DEFINED["scan"])){$conf[]="scan\tunix\t-\t-\tn\t\t-\t10\tsm -v";;}
	if(!isset($MASTER_CF_DEFINED["maildrop"])){$conf[]="maildrop\tunix\t-\tn\tn\t-\t-\tpipe ";;}
	if(!isset($MASTER_CF_DEFINED["retry"])){$conf[]="retry\tunix\t-\t-\tn\t-\t-\terror ";;}
	if(!isset($MASTER_CF_DEFINED["uucp"])){$conf[]="uucp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fqhu user=uucp argv=uux -r -n -z -a\$sender - \$nexthop!rmail (\$recipient)";;}
	if(!isset($MASTER_CF_DEFINED["ifmail"])){$conf[]="ifmail\tunix\t-\tn\tn\t-\t-\tpipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r \$nexthop (\$recipient)";;}
	if(!isset($MASTER_CF_DEFINED["bsmtp"])){$conf[]="bsmtp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t\$nexthop -f\$sender \$recipient";;}
}


while (list ($service, $MFARRY) = each ($MASTER_CF_DEFINED) ){
	$MFARRY["MAXPROC"]=intval($MFARRY["MAXPROC"]);
	$conf[]="$service\t{$MFARRY["TYPE"]}\t{$MFARRY["PRIVATE"]}\t{$MFARRY["UNIPRIV"]}\t{$MFARRY["CHROOT"]}\t{$MFARRY["WAKEUP"]}\t{$MFARRY["MAXPROC"]}\t{$MFARRY["COMMAND"]}";
	echo "Starting......: ".date("H:i:s")." master.cf adding $service ({$MFARRY["TYPE"]})\n";
	
}

$conf[]="mailman\tunix\t-\tn\tn\t-\t-\tpipe flags=FR user=mail:mail argv=/etc/mailman/postfix-to-mailman.py \${nexthop} \${mailbox}";
$conf[]="artica-whitelist\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --white";
$conf[]="artica-blacklist\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --black";
$conf[]="artica-reportwbl\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --report";
$conf[]="artica-reportquar\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --quarantines";
$conf[]="artica-spam\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --spam";
$conf[]="zarafa\tunix\t-\tn\tn\t-\t-\tpipe	user=mail argv=/usr/bin/zarafa-dagent \${user}";


$unix=new unix();
$cyrdeliver=$unix->find_program("cyrdeliver");
if(is_file($cyrdeliver)){
	echo "Starting......: ".date("H:i:s")." master.cf adding cyrus\n";
	$conf[]="cyrus\tunix\t-\tn\tn\t-\t-\tpipe\tflags=R user=cyrus argv=/usr/sbin/cyrdeliver -e -m \${extension} \${user}";	
}else{
	$conf[]="# cyrdeliver no such binary."; 
}

$conf[]="";
$conf[]="";
$conf[]=$master_amavis;
$conf[]="";
$conf[]="127.0.0.1:33559\tinet\tn\t-\tn\t-\t-\tsmtpd";
$conf[]="    -o notify_classes=protocol,resource,software";
$conf[]="    -o header_checks=";
$conf[]="    -o content_filter=";
$conf[]="    -o smtpd_restriction_classes=";
$conf[]="    -o smtpd_delay_reject=no";
$conf[]="    -o smtpd_client_restrictions=permit_mynetworks,reject";
$conf[]="    -o smtpd_helo_restrictions=";
$conf[]="    -o smtpd_sender_restrictions=";
$conf[]="    -o smtpd_recipient_restrictions=permit_mynetworks,reject";
$conf[]="    -o smtpd_data_restrictions=reject_unauth_pipelining";
$conf[]="    -o smtpd_end_of_data_restrictions=";
$conf[]="    -o mynetworks=127.0.0.0/8";
$conf[]="    -o strict_rfc821_envelopes=yes";
$conf[]="    -o smtpd_error_sleep_time=0";
$conf[]="    -o smtpd_soft_error_limit=1001";
$conf[]="    -o smtpd_hard_error_limit=1000";
$conf[]="    -o smtpd_client_connection_count_limit=0";
$conf[]="    -o smtpd_client_connection_rate_limit=0";
$conf[]="    -o receive_override_options=no_header_body_checks,no_unknown_recipient_checks";
$conf[]="    -o smtp_send_xforward_command=yes";
$conf[]="    -o disable_dns_lookups=yes";
$conf[]="    -o local_header_rewrite_clients=";
$conf[]="    -o smtp_generic_maps=";
$conf[]="    -o sender_canonical_maps=";
$conf[]="    -o smtpd_milters=";
$conf[]="    -o smtpd_sasl_auth_enable=no";
$conf[]="    -o smtpd_use_tls=no";
if($ver210){
$conf[]="	 -o smtpd_upstream_proxy_protocol=";
}	

$q=new mysql();
$sql="SELECT * FROM sender_dependent_relay_host WHERE enabled=1 
				AND `override_transport`=1 
				AND `hostname`='master' ORDER by zOrders";

$results = $q->QUERY_SQL($sql,"artica_backup");

echo "Starting......: ".date("H:i:s")." master.cf sender_dependent_relay_host ".mysql_num_rows($results)." item(s)\n";

build_progress_sender_routing("{building} master.cf sender_dependent_relay_host",50);

$main=new maincf_multi();
while ($ligne = mysql_fetch_assoc($results)) {
	$domain=$ligne["domain"];
	$md5=$ligne["zmd5"];
	$relay=$ligne["relay"];
	$relay_port_text=null;
	$relay_port=$ligne["relay_port"];
	$lookups=$ligne["lookups"];
	$relay_text=$main->RelayToPattern($relay,$relay_port,$lookups);
	$conf[]="";
	
	$conf[]="$md5\tunix\t-\t-\tn\t-\t-\tsmtp";
	
	if($ligne["smtp_bind_address"]<>null){
		$conf[]="    -o smtp_bind_address={$ligne["smtp_bind_address"]}";
	}
	if($ligne["smtp_helo_name"]<>null){
		$conf[]="    -o smtp_helo_name={$ligne["smtp_helo_name"]}";
	}
	if($ligne["syslog_name"]<>null){
		$ligne["syslog_name"]=str_replace(" ", "-", $ligne["syslog_name"]);
		$conf[]="    -o syslog_name={$ligne["syslog_name"]}";
	}	
	
	
	if($ligne["directmode"]==0){
		if($ligne["relay"]<>null){
			$conf[]="    -o relayhost=$relay_text";
			if($ligne["enabledauth"]==0){
				$conf[]="    -o smtp_sasl_password_maps=hash:/etc/postfix/smtp_sasl_password";
				$conf[]="    -o smtp_sasl_auth_enable=yes";
			}			
		}
	}else{
		$conf[]="    -o relayhost=";
		$conf[]="    -o smtp_host_lookup=dns";
	}
	

	
	
	
//	04 	-o syslog_name=postfix-customer1

}



$conf[]="";	
$conf[]="";
build_progress_sender_routing("{building} master.cf {done}",55);
@file_put_contents("/etc/postfix/master.cf",@implode("\n",$conf));
echo "Starting......: ".date("H:i:s")." master.cf done\n";
if($GLOBALS["RELOAD"]){shell_exec("/usr/sbin/postfix reload >/dev/null 2>&1");}	

if($restart_service){
	build_progress_sender_routing("{restarting_service}",60);
	shell_exec("{$GLOBALS["postfix"]} stop");
	shell_exec("{$GLOBALS["postfix"]} start");
}



}
function build_progress_postfix_templates($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_postfix_templates";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function postfix_templates(){
	$mainTPL=new bounces_templates();
	$main=new maincf_multi("master");
	$mainTemplates=new bounces_templates();
	$conf=null;
	
	
	build_progress_postfix_templates("{building}",10);
	
	$double_bounce_sender=$main->GET("double_bounce_sender");
	$address_verify_sender=$main->GET("address_verify_sender");
	$twobounce_notice_recipient=$main->GET("2bounce_notice_recipient");
	$error_notice_recipient=$main->GET("error_notice_recipient");
	$delay_notice_recipient=$main->GET("delay_notice_recipient");
	$empty_address_recipient=$main->GET("empty_address_recipient");
	
	$sock=new sockets();
	$PostfixPostmaster=$sock->GET_INFO("PostfixPostmaster");
	if(trim($PostfixPostmaster)==null){$PostfixPostmaster="postmaster";}
	
	if($double_bounce_sender==null){$double_bounce_sender="double-bounce";};
	if($address_verify_sender==null){$address_verify_sender="\$double_bounce_sender";}
	if($twobounce_notice_recipient==null){$twobounce_notice_recipient="postmaster";}
	if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
	if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
	if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}	
	if(is_array($mainTemplates->templates_array)){
		while (list ($template, $nothing) = each ($mainTemplates->templates_array) ){
			
			build_progress_postfix_templates("{{$template}}",50);
			
			$array=unserialize(base64_decode($main->GET_BIGDATA($template)));
			if(!is_array($array)){$array=$mainTemplates->templates_array[$template];}
				$tp=explode("\n",$array["Body"]);
				$Body=null;
				while (list ($a, $line) = each ($tp) ){if(trim($line)==null){continue;}$Body=$Body.$line."\n";}
				$conf=$conf ."\n$template = <<EOF\n";
				$conf=$conf ."Charset: {$array["Charset"]}\n";
				$conf=$conf ."From:  {$array["From"]}\n";
				$conf=$conf ."Subject: {$array["Subject"]}\n";
				$conf=$conf ."\n";
				$conf=$conf ."$Body";
				$conf=$conf ."\n\n";
				$conf=$conf ."EOF\n";
				
			}
	}


	@file_put_contents("/etc/postfix/bounce.template.cf",$conf);
	
	$notify_class=unserialize(base64_decode($main->GET_BIGDATA("notify_class")));
	if($notify_class["notify_class_software"]==1){$not[]="software";}
	if($notify_class["notify_class_resource"]==1){$not[]="resource";}
	if($notify_class["notify_class_policy"]==1){$not[]="policy";}
	if($notify_class["notify_class_delay"]==1){$not[]="delay";}
	if($notify_class["notify_class_2bounce"]==1){$not[]="2bounce";}
	if($notify_class["notify_class_bounce"]==1){$not[]="bounce";}
	if($notify_class["notify_class_protocol"]==1){$not[]="protocol";}
	
	
	build_progress_postfix_templates("{apply_config}",90);
	
	postconf("notify_class",@implode(",",$not));
	postconf("double_bounce_sender","$double_bounce_sender");
	postconf("address_verify_sender","$address_verify_sender");	
	postconf("2bounce_notice_recipient",$twobounce_notice_recipient);	
	postconf("error_notice_recipient",$error_notice_recipient);	
	postconf("delay_notice_recipient",$delay_notice_recipient);
	postconf("empty_address_recipient",$empty_address_recipient);
	postconf("bounce_template_file","/etc/postfix/bounce.template.cf");				

	build_progress_postfix_templates("{done}",100);
	
	}


function memory(){
	$unix=new unix();
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){$cmd_verbose=" --verbose";}
	$PostFixEnableQueueInMemory=$sock->GET_INFO("PostFixEnableQueueInMemory");
	$PostFixQueueInMemory=$sock->GET_INFO("PostFixQueueInMemory");
	$directory="/var/spool/postfix";
	if($PostFixEnableQueueInMemory==1){
		echo "Starting......: ".date("H:i:s")." Postfix Queue in memory is enabled for {$PostFixQueueInMemory}M\n";
		echo "Starting......: ".date("H:i:s")." Postfix executing exec.postfix-multi.php\n";
		shell_exec(LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.postfix-multi.php --instance-memory master $PostFixQueueInMemory$cmd_verbose");
		return;
	}else{
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			shell_exec(LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.postfix-multi.php --instance-memory-kill master$cmd_verbose");
			return;
		}
		echo "Starting......: ".date("H:i:s")." Postfix Queue in memory is not enabled\n"; 
	}	
	
}

function repair_locks(){
	$Myfile=basename(__FILE__);
	$timeFile="/etc/artica-postfix/pids/$Myfile.".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/$Myfile.".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	
	if($unix->process_exists($pid,$Myfile)){writelogs("Die, already process $pid running ",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$time=$unix->file_time_min($timeFile);
	if($time<5){writelogs("Die, No more than 5mn ",__FUNCTION__,__FILE__,__LINE__);return;}
	@unlink($timeFile);
	@mkdir(dirname($timeFile),0755,true);
	@file_put_contents($timeFile, time());
	@file_put_contents($pidFile, getmypid());
	
	echo "Starting......: ".date("H:i:s")." Stopping postfix\n";
	shell_exec("{$GLOBALS["postfix"]} stop");
	$daemon_directory=$unix->POSTCONF_GET("daemon_directory");
	$queue_directory=$unix->POSTCONF_GET("queue_directory");
	echo "Starting......: ".date("H:i:s")." Daemon directory: $daemon_directory\n";
	echo "Starting......: ".date("H:i:s")." Queue directory.: $queue_directory\n";
	$pid=$unix->PIDOF("$daemon_directory/master",true);
	echo "Starting......: ".date("H:i:s")." Process \"$daemon_directory/master\" PID:\"$pid\"\n";
	
	for($i=0;$i<10;$i++){
		if(is_numeric($pid)){
			if($pid>5){
				echo "Starting......: ".date("H:i:s")." Killing bad pid $pid\n";
				$unix->KILL_PROCESS($pid,9);
				sleep(1);
				
			}
		}else{
			echo "Starting......: ".date("H:i:s")." No $daemon_directory/master ghost process\n";
			break;
		}
		$pid=$unix->PIDOF("$daemon_directory/master");
		
		echo "Starting......: ".date("H:i:s")." Process \"$daemon_directory/master\" PID:\"$pid\"\n";
	}
	
	if(file_exists("$daemon_directory/master.lock")){
		echo "Starting......: ".date("H:i:s")." Delete $daemon_directory/master.lock\n";
		@unlink("$daemon_directory/master.lock");
	
	}
	if(file_exists("$queue_directory/pid/master.pid")){
		echo "Starting......: ".date("H:i:s")." Delete $queue_directory/pid/master.pid\n";
		@unlink("$queue_directory/pid/master.pid");
	}
	
	if(file_exists("$queue_directory/pid/inet.127.0.0.1:33559")){
		echo "Starting......: ".date("H:i:s")." $queue_directory/pid/inet.127.0.0.1:33559\n";
		@unlink("$queue_directory/pid/inet.127.0.0.1:33559");
	}
	
	
	echo "Starting......: ".date("H:i:s")." Starting postfix\n";
	exec("{$GLOBALS["postfix"]} start -v 2>&1",$results);
	while (list ($template, $nothing) = each ($results) ){echo "Starting......: ".date("H:i:s")." Starting postfix $nothing\n";}
}

function postconf($key,$value=null){
	if($value==null){
		shell_exec("{$GLOBALS["postconf"]} -X \"$key\" >/dev/null 2>&1");
		return;
	}
	$value=str_ireplace('$', '\$', $value);
	echo "Starting......: ".date("H:i:s");
	echo " Set {$GLOBALS["postconf"]} key $key = '$value'\n";
	shell_exec("{$GLOBALS["postconf"]} -e \"$key = $value\" >/dev/null 2>&1");
	
}
function postconf_X($key){
	shell_exec("{$GLOBALS["postconf"]} -X \"$key\" >/dev/null 2>&1");

}
function postconf_strip_key(){
	$t=array();
	$f=file("/etc/postfix/main.cf");
	while (list ($index, $line) = each ($f) ){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(trim($line)==null){
			echo "Starting......: ".date("H:i:s")." Starting postfix cleaning line $index (unused line)\n";
			continue;
		}
		
		if(preg_match("#alias_maps.*?=#", $line)){
			if(!preg_match("#virtual_alias_maps.*?=#", $line)){
			$line=str_replace("alias_maps", "\nalias_maps", $line);}
		}
		
		if(preg_match("#^(.*?)=(.*)#", $line,$re)){$value=trim($re[2]);if($value==null){
			echo "Starting......: ".date("H:i:s")." Starting postfix cleaning {$re[1]} (unused value `$line`)\n";
			continue;}}
			
		$t[]=$line;
	}
	@file_put_contents("/etc/postfix/main.cf", @implode("\n", $t)."\n");
	
}

function smtpd_milters(){
	if($GLOBALS["EnablePostfixMultiInstance"]==1){
		echo "Starting......: ".date("H:i:s")." Postfix EnablePostfixMultiInstance is enabled...\n";
		shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix-multi.php --from-main-reconfigure");return;}	
	
	$main=new main_cf();
	echo "Starting......: ".date("H:i:s")." Postfix building milters...\n";
	$milter_array=$main->BuildMilters(true);
	while (list ($key, $value) = each ($milter_array) ){
		echo "Starting......: ".date("H:i:s")." Postfix setting key `$key`...\n";
		postconf($key,$value);
	}
}

function wlscreen(){
	echo "wlscreen()\n";
	$f=new maincf_multi();
	$f->postfix_global_whitelist_to_mx();
	
	
}
function CleanUpMainCf(){
	
	$DBS["mydestination"]=true;
	$DBS["copy.transport"]=true;
	$DBS["sender_dependent_relayhost"]=true;
	$DBS["sender_canonical"]=true;
	$DBS["sender_bcc"]=true;
	$DBS["recipient_bcc"]=true;
	$DBS["smtp_generic_maps"]=true;
	$DBS["relay_domains"]=true;
	$DBS["transport"]=true;
	$DBS["transport.banned"]=true;
	

	if(!is_file("/etc/postfix/header_checks")){@file_put_contents("/etc/postfix/header_checks", "\n");}
	
	while (list ($filename, $none) = each ($DBS) ){
		if(!is_file("/etc/postfix/$filename")){@file_put_contents("/etc/postfix/$filename", "\n");}
		
		if(!is_file("/etc/postfix/$filename.db")){
			echo "Starting......: ".date("H:i:s")." Postfix compiling $filename database\n";
			shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/$filename >/dev/null 2>&1");
		}
		
	}
	
	$f=explode("\n",@file_get_contents("/etc/postfix/main.cf"));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#^\##", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning mark line $line\n";
			continue;
		}
		
		if(preg_match("#PATH=\/bin#s", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning bad parameters $line\n";
			continue;
		}
		
		if(preg_match("#ddd\s+.*?daemon#is", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning bad parameters $line\n";
			continue;
		}
		
		
		if(preg_match("#^(.+?)=(.*)#", $line,$re)){
			if(trim($re[2])==null){
				echo "Starting......: ".date("H:i:s")." Postfix cleaning unused parameter `{$re[1]}`\n";
				continue; 
			}
		}
		
		
		$r[]=$line;
		
	}
	
	@file_put_contents("/etc/postfix/main.cf", @implode("\n", $r));
	echo "Starting......: ".date("H:i:s")." Postfix cleaning /etc/postfix/main.cf done\n";
	echo "Starting......: ".date("H:i:s")." Postfix Please wait...set permissions..\n";
	shell_exec("{$GLOBALS["postfix"]} set-permissions >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." Postfix set permissions done..\n";
	
	
}

function SMTP_SASL_PROGRESS(){
	SMTP_SASL_PROGRESS_LOG("Check structure",10);
	SetSASLMech();
	SMTP_SASL_PROGRESS_LOG("Enable SASL",20);
	SetSALS();
	SMTP_SASL_PROGRESS_LOG("Enable TLS",30);
	SetTLS();
	SMTP_SASL_PROGRESS_LOG("Smtpd Recipient Restrictions",40);
	smtpd_recipient_restrictions();
	SMTP_SASL_PROGRESS_LOG("SMTP SASL Security Options",50);
	smtp_sasl_security_options();
	SMTP_SASL_PROGRESS_LOG("SMTP SASL whitelisted networks",55);
	smtpd_sasl_exceptions_networks();
	SMTP_SASL_PROGRESS_LOG("Build Master.cf",60);
	MasterCFBuilder();
	SMTP_SASL_PROGRESS_LOG("Checks transport table",70);
	MailBoxTransport();
	SMTP_SASL_PROGRESS_LOG("{reloading} SMTP MTA",80);
	ReloadPostfix(true);
	SMTP_SASL_PROGRESS_LOG("{reloading} SaslAuthd",90);
	system("/etc/init.d/saslauthd restart");
	
	SMTP_SASL_PROGRESS_LOG("{done}",100);
	
}
function SMTP_SASL_PROGRESS_LOG($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/SMTP_SASL_PROGRESS";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

?>