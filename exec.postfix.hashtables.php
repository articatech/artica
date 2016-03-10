<?php
$GLOBALS["EnablePostfixMultiInstance"]=0;
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix.externaldbs.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--pourc=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POURC_START"]=$re[1];}



if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$sock=new sockets();
$unix=new unix();
$GLOBALS["EnablePostfixMultiInstance"]=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($GLOBALS["EnablePostfixMultiInstance"])){$GLOBALS["EnablePostfixMultiInstance"]=0;}
$GLOBALS["EnableBlockUsersTroughInternet"]=$sock->GET_INFO("EnableBlockUsersTroughInternet");
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["newaliases"]=$unix->find_program("newaliases");
$GLOBALS["postalias"]=$unix->find_program("postalias");
$GLOBALS["postfix"]=$unix->find_program("postfix");
$GLOBALS["newaliases"]=$unix->find_program("newaliases");
$GLOBALS["virtual_alias_maps"]=array();
$GLOBALS["alias_maps"]=array();
$GLOBALS["relay_domains"]=array();
$GLOBALS["bcc_maps"]=array();
$GLOBALS["transport_maps"]=array();
$GLOBALS["smtp_generic_maps"]=array();
$GLOBALS["PHP5_BIN"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["CLASS_UNIX"]=$unix;

if(!is_file($GLOBALS["postfix"])){die();}

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$time=$unix->PROCCESS_TIME_MIN($pid);
	echo "Starting......: ".date("H:i:s")." Already executed pid:$pid since {$time}Mn\n";
	$unix->send_email_events("Postfix user databases aborted (instance executed)", "Already instance pid $pid is executed", "postfix");
	die();
}

@file_put_contents($pidfile, getmypid());

$ldap=new clladp();
if($ldap->ldapFailed){
	WriteToSyslogMail("Fatal: connecting to ldap server $ldap->ldap_host",basename(__FILE__),true);
	echo "Starting......: ".date("H:i:s")." failed connecting to ldap server $ldap->ldap_host\n";
	$unix->send_email_events("Postfix user databases aborted (ldap failed)", "The process has been scheduled to start in few seconds.", "postfix"); 
	$unix->THREAD_COMMAND_SET(trim($unix->LOCATE_PHP5_BIN()." ".__FILE__. " {$argv[1]}"));
	die();
}

if($argv[1]=="--dump-db_extern"){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);DUMP_EXTERNALS_DBS();die();}
if($GLOBALS["EnablePostfixMultiInstance"]==1){if($argv[1]=="--aliases"){system(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.postfix-multi.php --aliases");die();}system(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.postfix-multi.php");die();}
if($argv[1]=="--postmaster"){postmaster();die();}	

$php=$unix->LOCATE_PHP5_BIN();
if($argv[1]=="--virtuals"){cmdline_virtuals();exit;}
if($argv[1]=="--mailbox-transport-maps"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php --mailbox-transport-maps");
}

if($argv[1]=="--mailman"){
	internal_pid($argv);
	cmdline_alias();
	perso_settings();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();	
}


if($argv[1]=="--relayhost"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php --relayhost");
	die();
}


if($argv[1]=="--bcc"){
	internal_pid($argv);
	recipient_bcc_maps();
	recipient_bcc_domain_maps();
	recipient_bcc_maps_build();
	sender_bcc_maps();
	sender_bcc_maps_build();
	perso_settings();
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();
}

if($argv[1]=="--recipient-canonical"){
	internal_pid($argv);
	
	recipient_canonical_maps();
	perso_settings();
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();	
}

if($argv[1]=="--restricted-relais"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php --restricted-relais");
	die();
}


if($argv[1]=="--transport"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php");
	die();
}


	
if($argv[1]=="--aliases"){
	internal_pid($argv);
	build_progress_aliases("Building aliases...",10);
	cmdline_alias();
	build_progress_aliases("Building perso_settings...",70);
	perso_settings();
	build_progress_aliases("{reloading}",80);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	build_progress_aliases("{done}",100);
	die();}
		
if($argv[1]=="--smtp-passwords"){
	internal_pid($argv);
	
	sender_canonical_maps();
	recipient_canonical_maps();
	smtp_generic_maps_build_global();
	smtp_generic_maps();
	sender_dependent_relayhost_maps();
	sender_dependent_default_transport_maps();
	smtp_sasl_password_maps_build();
	smtp_sasl_password_maps();
	perso_settings();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();}	
	
	

	
if($argv[1]=="--sender-dependent-relayhost"){	
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	build_progress_sender_routing("{building}: relayhost",10);
	relayhost();
	build_progress_sender_routing("{building}: sender routing table",20);
	sender_dependent_relayhost_maps();
	sender_dependent_default_transport_maps();
	build_progress_sender_routing("{building}: Patching service table",30);
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --ssl --progress-sender-dependent-relayhost");
	
	build_progress_sender_routing("{building}: SMTP authentication passwords",70);
	smtp_sasl_password_maps();
	build_progress_sender_routing("{building}: Personal settings",80);
	perso_settings();
	build_progress_sender_routing("{reloading}",90);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	system("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	build_progress_sender_routing("{done}",100);
	die();
}
	
if($argv[1]=="--smtp-generic-maps"){
	internal_pid($argv);
	build_progress_smtp_generic_maps("{buiding} {senders} Canonicals...",10);
	sender_canonical_maps();
	build_progress_smtp_generic_maps("{buiding} {recipients} Canonicals...",10);
	recipient_canonical_maps();
	build_progress_smtp_generic_maps("{buiding} SMTP Generic Maps...",20);
	smtp_generic_maps_build_global();
	build_progress_smtp_generic_maps("{configuring} SMTP Generic Maps...",30);
	smtp_generic_maps();
	build_progress_smtp_generic_maps("{building}: Personal settings",90);
	perso_settings();
	build_progress_smtp_generic_maps("{reloading}",95);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	build_progress_smtp_generic_maps("{done}",100);
	die();

}


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/postfix.reconfigure2.pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
	die();
}
@file_put_contents($pidfile, getmypid());


$start=50;

internal_pid($argv);

$functions=array(
		"LoadLDAPDBs","maillings_table","aliases_users","aliases","catch_all","build_aliases_maps","build_virtual_alias_maps",
		 "recipient_canonical_maps_build",
		"recipient_canonical_maps","sender_canonical_maps_build","sender_canonical_maps",
		"smtp_generic_maps_build_global","smtp_generic_maps","sender_dependent_relayhost_maps","sender_dependent_default_transport_maps","smtp_sasl_password_maps_build",
		"smtp_sasl_password_maps","recipient_bcc_maps","recipient_bcc_domain_maps","recipient_bcc_maps_build",
		"sender_bcc_maps","sender_bcc_maps_build","build_local_recipient_maps",
		
		"relayhost","postmaster","perso_settings"
		
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
			SEND_PROGRESS($start,"Action 2, {$start}% Please wait, executing $func() $i/$tot..");
			call_user_func($func);
		} catch (Exception $e) {
			SEND_PROGRESS($start,$func,"Error on $func ($e)");
		}			
	}

	
	
	$reste=100-$start;
	$reste++;
	SEND_PROGRESS($reste,"mydestination");
	$hashT=new main_hash_table();
	$hashT->mydestination();
	
	
	SEND_PROGRESS(100,"Reload postfix");
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");

function build_progress_smtp_generic_maps($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}		
	
function build_progress_sender_routing($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_sender_routing";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}	
function build_progress_aliases($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postfix.aliases.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}



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


function internal_pid($argv){
	
	$md5=md5(serialize($argv));
	
	unset($argv[0]);
	$cmsline=@implode(" ", $argv);
	
	$mef=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$md5.pid";
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,$mef)){
		build_progress_smtp_generic_maps("{failed} Process Already exist pid $pid",110);
		echo "Starting......: ".date("H:i:s")." Postfix : Process Already exist pid $pid line:".__LINE__."\n";
		system_admin_events("`$cmsline` task cannot be performed, a Process Already exist pid $pid", __FUNCTION__, __FILE__, __LINE__, "postfix");
		die();
	}	
	
	@file_put_contents($pidfile, getmypid());
	
}

function cmdline_virtuals(){
	build_aliases_maps();
	build_virtual_alias_maps();
}


function cmdline_alias(){
	build_progress_aliases("Building LoadLDAPDBs...",15);
	LoadLDAPDBs();
	build_progress_aliases("Building maillings_table...",20);
	maillings_table();
	build_progress_aliases("Building aliases_users...",25);
	aliases_users();
	build_progress_aliases("Building aliases...",30);
	aliases();
	build_progress_aliases("Building catch_all...",35);
	catch_all();
	build_progress_aliases("Building build_aliases_maps...",40);
	build_aliases_maps();
	build_progress_aliases("Building build_virtual_alias_maps...",45);
	build_virtual_alias_maps();
	build_progress_aliases("Building postmaster...",50);
	postmaster();
	build_progress_aliases("Building recipient_canonical_maps_build...",55);
	recipient_canonical_maps_build();
	build_progress_aliases("Building recipient_canonical_maps...",60);
	recipient_canonical_maps();	
	build_progress_aliases("Building aliases {done}...",65);
}



function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("/etc/postfix/main.cf");
}


function recipient_bcc_maps(){
	
$ldap=new clladp();
	$filter="(&(objectClass=UserArticaClass)(RecipientToAdd=*))";
	$attrs=array("RecipientToAdd","mail");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["mail"][0];
		$RecipientToAdd=$hash[$i]["recipienttoadd"][0];
		$GLOBALS["bcc_maps"][]="$mail\t$RecipientToAdd";
		
	}	
	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["bcc_maps"])." recipient(s) BCC\n"; 	
}
function sender_bcc_maps(){
	$ldap=new clladp();
	if(!isset($GLOBALS["sender_bcc_maps"])){$GLOBALS["sender_bcc_maps"]=array();}
	$filter="(&(objectClass=UserArticaClass)(SenderBccMaps=*))";
	$attrs=array("SenderBccMaps","mail");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["mail"][0];
		$senderbccmaps=$hash[$i]["senderbccmaps"][0];
		$GLOBALS["sender_bcc_maps"][]="$mail\t$senderbccmaps";
		
	}	
	
	if(isset($GLOBALS["sender_bcc_maps"])){
		echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["sender_bcc_maps"])." Sender(s) BCC\n";
	} 	
}
function sender_bcc_maps_build(){
	
	if(!isset($GLOBALS["sender_bcc_maps"])){$GLOBALS["sender_bcc_maps"]=array();}
	
	if(!count($GLOBALS["sender_bcc_maps"]==0)){
		shell_exec("{$GLOBALS["sender_bcc_maps"]} -e \"sender_bcc_maps = \" >/dev/null 2>&1");
		return null;
		}
	
		shell_exec("{$GLOBALS["postconf"]} -e \"sender_bcc_maps =hash:/etc/postfix/sender_bcc\" >/dev/null 2>&1");
		echo "Starting......: ".date("H:i:s")." Compiling Sender(s) BCC\n"; 
		@file_put_contents("/etc/postfix/sender_bcc",implode("\n",$GLOBALS["sender_bcc_maps"]));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/sender_bcc >/dev/null 2>&1");	
}


function recipient_bcc_domain_maps(){
	$sql="SELECT * FROM postfix_duplicate_maps";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["pattern"]==null){continue;}	
		
		$left="(.*)";
		$right='${1}';
		$leftNext="(.*)";
		$rightNext='${1}';
		$domain=$ligne["pattern"];
		$nextdomain=$ligne["nextdomain"];
		$nextdomain_transport=$ligne["nextdomain"];

		
		
		if(preg_match("#(.+?)@(.+)#",$ligne["pattern"],$re)){
			$nextHope_pattern=$ligne["pattern"];
			$domain=$re[2];
			$left=$re[1];
			$right=$re[1];
			$rightNext=$right;
			$left=str_replace(".","\.",$left);
			$right=str_replace(".","\.",$right);
			$leftNext=$left;
		}
		
		if(preg_match("#(.+?)@(.+)#",$ligne["nextdomain"],$re)){
			$right=$re[1];
			$nextdomain=$re[2];
			
		}		
		
		$md5=md5($domain);
		$domain_regex=str_replace(".","\.",$domain);
		$f[]="/^$left@$domain_regex$/   $right@$nextdomain";
		$t[]="$nextdomain_transport\tsmtp:[{$ligne["relay"]}]:{$ligne["port"]}";
		$c++;
	}
	echo "Starting......: ".date("H:i:s")." ".count($f)." duplicated destination(s)\n"; 
	$f[]="";
	@file_put_contents("/etc/postfix/copy.pcre",implode("\n",$f));
	@file_put_contents("/etc/postfix/copy.transport",implode("\n",$t));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/copy.transport >/dev/null 2>&1");	
}
function recipient_bcc_maps_build(){
if(!is_array($GLOBALS["bcc_maps"])){
		shell_exec("{$GLOBALS["postconf"]} -e \"recipient_bcc_maps = pcre:/etc/postfix/copy.pcre\" >/dev/null 2>&1");
		return null;
		}
	
		shell_exec("{$GLOBALS["postconf"]} -e \"recipient_bcc_maps =hash:/etc/postfix/recipient_bcc,pcre:/etc/postfix/copy.pcre\" >/dev/null 2>&1");
		echo "Starting......: ".date("H:i:s")." Compiling Recipient(s) BCC\n";
		@file_put_contents("/etc/postfix/recipient_bcc",implode("\n",$GLOBALS["bcc_maps"]));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/recipient_bcc >/dev/null 2>&1");	
}



function repair_addr($email){
	$old_email=$email;
	$email=trim(strtolower($email));
	$email=str_replace("\n", "", $email);
	$email=str_replace("\r", "", $email);
	if(strlen($email)<3){return null;}
	$email=str_replace(" ", "", $email);
	$email=str_replace(";", ".", $email);
	if(!preg_match("#^(.+?)@(.+)#", $email)){return null;}
	if(preg_match("#^(.+?)\s+(.+)#", $email,$re)){$email="{$re[1]}{$re[2]}";}
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: `$old_email` [$email]\n";}
	return $email;
}

function maillings_table(){
	if(isset($GLOBALS["maillings_table_exectuted"])){return;}
	$GLOBALS["maillings_table_exectuted"]=true;
	$sock=new sockets();
	$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
	if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}
	if($MailingListUseLdap==1){return;}
	$ldap=new clladp();
	$filter="(&(objectClass=MailingAliasesTable)(cn=*))";
	$attrs=array("cn","MailingListAddress","MailingListAddressGroup");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
	for($i=0;$i<$hash["count"];$i++){
		$cn=trim($hash[$i]["cn"][0]);
		$MailingListAddressGroup=0;
		if(isset($hash[$i]["mailinglistaddressgroup"])){
			$MailingListAddressGroup=$hash[$i]["mailinglistaddressgroup"][0];
		}
		for($t=0;$t<$hash[$i]["mailinglistaddress"]["count"];$t++){
			$mailinglistaddress_email=repair_addr($hash[$i]["mailinglistaddress"][$t]);
			if($mailinglistaddress_email==null){continue;}
			if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: maillings_table(): -> \"$mailinglistaddress_email\"\n";}
			$mailinglistaddress[$mailinglistaddress_email]=$mailinglistaddress_email;
		}
		
		if($MailingListAddressGroup==1){
			$uid=$ldap->uid_from_email($cn);
			$user=new user($uid);
			$array=$user->MailingGroupsLoadAliases();
			
			while (list ($num, $ligne) = each ($array) ){
				$ligne=repair_addr($ligne);
    			if(trim($ligne)==null){continue;} 
    			if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: $uid -> [$ligne]\n";}
    			$mailinglistaddress[$ligne]=$ligne;
    		}	
		}
		
		$final=array();
		if(is_array($mailinglistaddress)){
				while (list ($num, $ligne) = each ($mailinglistaddress) ){
					$num=repair_addr($num);
					if($num==null){continue;}
					$final[]=$num;
				}
				
				if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: maillings_table(): $cn = ". implode(",",$final)."\n";}
				if(count($final)>0){
					$cn=trim($cn);
					$cn=str_replace("\n", "",$cn);
					$cn=str_replace("\r", "",$cn);
					if($cn==null){continue;}
					$GLOBALS["virtual_alias_maps_emailing"][$cn]="$cn\t". implode(",",$final);
				}
			}	
			
		unset($final);
		unset($mailinglistaddress);
		$MailingListAddressGroup=0;
	}
	
	

	
	$filter="(&(objectClass=ArticaMailManRobots)(cn=*))";
	$attrs=array("cn","MailManAliasPath");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	$sock=new sockets();
	if($sock->GET_INFO("MailManEnabled")==1){$GLOBALS["MAILMAN"]=true;}else{
		$GLOBALS["MAILMAN"]=false;
		return;
	}
	
	if($hash["count"]>0){$GLOBALS["MAILMAN"]=true;}else{$GLOBALS["MAILMAN"]=false;}
	

}


function catch_all(){
	$ldap=new clladp();
	$filter="(&(objectClass=AdditionalPostfixMaps)(cn=*))";
	$attrs=array("cn","CatchAllPostfixAddr");
	$dn="cn=catch-all,cn=artica,$ldap->suffix";
	
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> open branch $dn $filter\n";}
	
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> found {$hash["count"]} entries\n";}
	for($i=0;$i<$hash["count"];$i++){
		$cn=$hash[$i]["cn"][0];
		for($t=0;$t<$hash[$i][strtolower("CatchAllPostfixAddr")]["count"];$t++){
			echo "Starting......: ".date("H:i:s")." catch-all {$hash[$i][strtolower("CatchAllPostfixAddr")][$t]} for $cn\n";
			if(substr($cn,0,1)<>"@"){$cn=trim("@$cn");}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> virtual_alias_maps=$cn\t{$hash[$i][strtolower("CatchAllPostfixAddr")][$t]}\n";}
			$GLOBALS["virtual_alias_maps"][$cn]="$cn\t{$hash[$i][strtolower("CatchAllPostfixAddr")][$t]}";
		}
	}
}


	




function LoadLDAPDBs(){
	if(isset($GLOBALS["LoadLDAPDBs_performed"])){return ;}
	$main=new maincf_multi("master","master");
	$databases_list=unserialize(base64_decode($main->GET_BIGDATA("ActiveDirectoryDBS")));	
	if(is_array($databases_list)){
		while (list ($dbindex, $array) = each ($databases_list) ){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]}; enabled={$array["enabled"]}\n";}
			if($array["enabled"]<>1){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} is not enabled, skipping\n";}
				continue;
			}
			$targeted_file=$main->buidLdapDB("master",$dbindex,$array);
			if(!is_file($targeted_file)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} \"$targeted_file\" no such file, skipping\n";}
				continue;
			}
			
			
//$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"]	

			if($array["resolv_domains"]==1){$domains=$main->buidLdapDBDomains($array);}
			
			$GLOBALS["LDAPDBS"][$array["database_type"]][]="ldap:$targeted_file";
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: GLOBALS[LDAPDBS][{$array["database_type"]}]=ldap:$targeted_file\n";}
		}	
	}
	$GLOBALS["LoadLDAPDBs_performed"]=true;
}





function aliases_users(){
	$ldap=new clladp();
	$users=new usersMenus();
	$main=new maincf_multi();
	if($GLOBALS["VERBOSE"]){echo "*** aliases_users() ***\n";}
	$filter="(&(objectClass=userAccount)(uid=*))";
	$attrs=array("uid","mail");
	$trap_uid="uid";
	$dn="dc=organizations,$ldap->suffix";
	
	if($ldap->EnableManageUsersTroughActiveDirectory){
		$ldapAD=new ldapAD();
		$filter="(&(objectClass=user)(samaccountname=*))";
		$attrs=array("samaccountname","mail");
		$trap_uid="samaccountname";
		$dn="$ldapAD->suffix";
		$hash=$ldapAD->Ldap_search($dn,$filter,$attrs);
	}else{
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	}

	for($i=0;$i<$hash["count"];$i++){
		$uid=trim($hash[$i][$trap_uid][0]);
		if(strpos($uid,"$")>0){continue;}
		if($uid==null){continue;}
		
		if(isset($hash[$i]["mail"])){
			for($t=0;$t<$hash[$i]["mail"]["count"];$t++){
				$mail=$hash[$i]["mail"][$t];
				$mail=repair_addr($mail);
				$mail=trim($mail);
				$mail=str_replace("\r", "", $mail);
				$mail=str_replace("\n", "", $mail);
				if($mail==null){continue;}
				
				if(!isset($GLOBALS["virtual_alias_maps_mem"][$mail])){
					if(!isset($GLOBALS["virtual_alias_maps_emailing"][$mail])){$GLOBALS["virtual_alias_maps_emailing"][$mail]=null;}
					if($GLOBALS["virtual_alias_maps_emailing"][$mail]==null){$GLOBALS["virtual_alias_maps"][$mail]="$mail\t$mail";}
				}
				
				$GLOBALS["virtual_alias_maps_mem"][$mail]=true;
				
				if(!isset($GLOBALS["alias_maps_mem"][$uid])){
					$uid=trim($uid);
					$uid=str_replace("\r", "", $uid);
					$uid=str_replace("\n", "", $uid);
					if($uid==null){continue;}
					if(!preg_match("#.+?@#",$uid)){$GLOBALS["alias_maps"][]="$uid:$mail";}
					$GLOBALS["alias_maps_mem"][$uid]=true;	
				}
				
				$GLOBALS["virtual_mailbox"]="$mail\t$uid";
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Skipping \"$uid\" no \"mail\" attribute... in ". basename(__FILE__)." Line: ".__LINE__."\n";}
		}
	}

	$filter="(&(objectClass=transportTable)(cn=*@*))";
	$attrs=array("cn");
	$dn="cn=PostfixRobots,cn=artica,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$cn=$hash[$i]["cn"][0];
		if(preg_match("#(.+?)@#",$cn,$re)){
			$map=$re[1];
			if(!$GLOBALS["alias_maps_mem"][$map]){
				$GLOBALS["alias_maps"][]="$map:$cn";
				$GLOBALS["alias_maps_mem"][$map]=true;
			}
		}
	}
	
	
	$GLOBALS["virtual_alias_maps"]=$main->mailman_virtual($GLOBALS["virtual_alias_maps"]);
	
	
	
	
	$sock=new sockets();
	$PostfixPostmaster=trim($sock->GET_INFO("PostfixPostmaster"));
	if($PostfixPostmaster==null){return;}
	
	$myhostname=trim($sock->GET_INFO("myhostname"));
	if($myhostname==null){$myhostname=$users->hostname;}
	preg_match("#(.+?)@#",$PostfixPostmaster,$re);
	$PostfixPostmaster_prefix=trim($re[1]);	
	
	$myhostname=trim($myhostname);
	$GLOBALS["virtual_alias_maps"]["$PostfixPostmaster_prefix@$myhostname"]="$PostfixPostmaster_prefix@$myhostname\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"][$PostfixPostmaster]="$PostfixPostmaster\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["root@$myhostname"]="root@$myhostname\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["postmaster"]="postmaster\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["MAILER-DAEMON"]="MAILER-DAEMON\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["root"]="root\t$PostfixPostmaster";
	
	
	
	
	/*$sql="SELECT `email` FROM postfix_relais_domains_users";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$GLOBALS["virtual_alias_maps"][$ligne["email"]]="{$ligne["email"]}\t{$ligne["email"]}";
	}
	
	* see trusted_smtp_domain
		DOMAIN_TRUSTED_NO_USERDB_TEXT
	*/		
	
	
	$GLOBALS["alias_maps"][]="postmaster:$PostfixPostmaster";
	$GLOBALS["alias_maps"][]="MAILER-DAEMON:$PostfixPostmaster";
	$GLOBALS["alias_maps"][]="root:$PostfixPostmaster";
	if($PostfixPostmaster_prefix<>null){
		if(!isset($GLOBALS["alias_maps_mem"][$PostfixPostmaster_prefix])){$GLOBALS["alias_maps"][]="$PostfixPostmaster_prefix:$PostfixPostmaster";}
	}
	
	
	
	
}


function build_local_recipient_maps(){
if(!is_array($GLOBALS["local_recipient_maps"])){
		shell_exec("{$GLOBALS["postconf"]} -e \"local_recipient_maps = \" >/dev/null 2>&1");
		echo "Starting......: ".date("H:i:s")." No recipients maps\n"; 
		return null;
		}	

echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["local_recipient_maps"])." local recipient(s)\n"; 
shell_exec("{$GLOBALS["postconf"]} -e \"local_recipient_maps =hash:/etc/postfix/local_recipients\" >/dev/null 2>&1");
file_put_contents("/etc/postfix/local_recipients",implode("\n",$GLOBALS["local_recipient_maps"]));
shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/local_recipients >/dev/null 2>&1");		
	
}

function mailling_ldap(){
	$ldap=new clladp();
	$conf[]="#Mailling list configuration to Open LDAP --------------------------------------------------------------------";
	$conf[]="server_host = $ldap->ldap_host";
	$conf[]="server_port = $ldap->ldap_port";
	$conf[]="bind = yes";
	$conf[]="bind_dn = cn=$ldap->ldap_admin,$ldap->suffix";
	$conf[]="bind_pw = $ldap->ldap_password";
	$conf[]="timeout = 10";
	$conf[]="search_base = dc=organizations,$ldap->suffix";
	$conf[]="query_filter = (&(objectclass=MailingAliasesTable)(cn=%s))";
	$conf[]="result_attribute = MailingListAddress";
	$conf[]="version =3";
	$conf[]= "#-------------------------------------------------------------------------------------------";
	@file_put_contents("/etc/postfix/mailinglist.ldap.cf", @implode("\n", $conf));
}

function build_virtual_alias_maps(){
	$main=new maincf_multi("master","master");
	$ldap=new clladp();
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> virtual_alias_maps=". count($GLOBALS["virtual_alias_maps"]) . " entries\n";}

	if(is_array($GLOBALS["virtual_alias_maps_emailing"])){	
			echo "Starting......: ".date("H:i:s")." Postfix [".__LINE__."] ". count($GLOBALS["virtual_alias_maps_emailing"])." distribution listes\n";	
			while (list ($num, $ligne) = each ($GLOBALS["virtual_alias_maps_emailing"]) ){
				$num=trim($num);
				$num=str_replace("\r", "", $num);
				$num=str_replace("\n", "", $num);
				if($GLOBALS["VERBOSE"]){echo "FINAL -> $num/\"$ligne\"\n";}
				if($ligne==null){continue;}
				$final[]=$ligne;
			}
		}	
//-----------------------------------------------------------------------------------
	if(is_array($GLOBALS["virtual_alias_maps"])){
			echo "Starting......: ".date("H:i:s")." Cleaning virtual aliase(s)\n"; 
			while (list ($num, $ligne) = each ($GLOBALS["virtual_alias_maps"]) ){
			$ligne=trim($ligne);
			$ligne=str_replace("\r", "", $ligne);
			$ligne=str_replace("\n", "", $ligne);
			if($ligne==null){continue;}
			if(preg_match("#x500:#",$ligne)){continue;}
			if(preg_match("#x400:#",$ligne)){continue;}
			$final[]=$ligne;
		}
	}
//-----------------------------------------------------------------------------------	
  	$dn="cn=artica_smtp_sync,cn=artica,$ldap->suffix";
  	$filter="(&(objectClass=InternalRecipients)(cn=*))";
  	if($ldap->ExistsDN($dn)){
	  	$attrs=array("cn");	
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);	
		if($hash["count"]>0){
			for($i=0;$i<$hash["count"];$i++){
				$email=$hash[$i]["cn"][0];
				$email=trim($email);
				$email=str_replace("\r", "", $email);
				$email=str_replace("\n", "", $email);
				if(trim($email)==null){continue;}
				$final[]="$email\t$email";
			} 	
		}
  	}
//-----------------------------------------------------------------------------------	
	
		
	if(isset($GLOBALS["LDAPDBS"]["virtual_alias_maps"])){
		if(!is_array($GLOBALS["LDAPDBS"]["virtual_alias_maps"])){
			$virtual_alias_maps_cf=$GLOBALS["LDAPDBS"]["virtual_alias_maps"];
		}
	}
	
		$sock=new sockets();
		$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
		if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}	
		if($MailingListUseLdap==1){
			$virtual_alias_maps_cf[]="ldap:/etc/postfix/mailinglist.ldap.cf";
			mailling_ldap();
		}
	
	
		
		$sql="SELECT * FROM postfix_aliases_domains";
		$q=new mysql();
		$pre='${1}';
		$li=array();
		$results=$q->QUERY_SQL($sql,"artica_backup");	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$ligne["alias"]=trim($ligne["alias"]);
			$ligne["alias"]=strtolower($ligne["alias"]);
			$aliases=str_replace(".","\.",$ligne["alias"]);
			$domain=$ligne["domain"];
			$li[]="/^(.*)@$aliases$/\t$pre@$domain";
			$final[]="{$ligne["alias"]}\tDOMAIN";
		}

		$main=new maincf_multi("master","master");
		$virtual_mailing_addr=$main->mailling_list_mysql("master");
		if(is_array($virtual_mailing_addr)){
			while (list ($num, $ligne) = each ($virtual_mailing_addr) ){
				$final[]=$ligne;
			}
		}
	
	
		echo "Starting......: ".date("H:i:s")." Postfix ". count($final)." virtual aliase(s)\n"; 	
		echo "Starting......: ".date("H:i:s")." Postfix ". count($li)." virtual domain(s) aliases\n"; 	
		$virtual_alias_maps_cf[]="hash:/etc/postfix/virtual";
		$virtual_alias_maps_cf[]="pcre:/etc/postfix/virtual.domains";
		
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> writing /etc/postfix/virtual\n";}			
		@file_put_contents("/etc/postfix/virtual",implode("\n",$final));
		@file_put_contents("/etc/postfix/virtual.domains",implode("\n",$li));
		
		echo "Starting......: ".date("H:i:s")." Postfix compiling virtual aliase database /etc/postfix/virtual\n"; 
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> {$GLOBALS["postmap"]} hash:/etc/postfix/virtual >/dev/null 2>&1\n";}	
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/virtual >/dev/null 2>&1");
	
		$dbmaps=new postfix_extern();
		$contz=$dbmaps->build_extern("master","virtual_alias_maps");
		if($contz<>null){$virtual_alias_maps_cf[]=$contz;}

	
	if(!is_array($virtual_alias_maps_cf)){
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> {$GLOBALS["postconf"]} -e \"virtual_alias_maps = \" >/dev/null 2>&1\n";}
		shell_exec("{$GLOBALS["postconf"]} -e \"virtual_alias_maps = \" >/dev/null 2>&1");
		echo "Starting......: ".date("H:i:s")." Postfix No virtual aliases\n";
		return;
	}else{
		echo "Starting......: ".date("H:i:s")." Postfix building virtual_alias_maps\n";
		shell_exec("{$GLOBALS["postconf"]} -e \"virtual_alias_maps = ". @implode(",",$virtual_alias_maps_cf).$main->mailman_aliases()."\" >/dev/null 2>&1");
	}		
	
}


function build_aliases_maps(){
	maillings_table();
	$alias_maps_cf=array();
	$alias_database_cf=array();
	$virtual_mailbox_maps_cf=array();
	$hash_mailman=null;
	$main=new maincf_multi();
	if(!isset($GLOBALS["alias_maps"])){$GLOBALS["alias_maps"]=array();}
	if(!is_array($GLOBALS["alias_maps"])){$GLOBALS["alias_maps"]=array();}
	
	if(count($GLOBALS["alias_maps"]==0)){aliases_users();}
	
	
	if(isset($GLOBALS["LDAPDBS"]["alias_maps"])){
		if(is_array($GLOBALS["LDAPDBS"]["alias_maps"])){
			if($GLOBALS["VERBOSE"]){"LDAP:: alias_maps = \"".@implode(",",$GLOBALS["LDAPDBS"]["alias_maps"])."\n";}
			$alias_maps_cf=$GLOBALS["LDAPDBS"]["alias_maps"];
		}else{
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: GLOBALS[LDAPDBS][alias_maps]=not an array\n";}
		}
	}
	
	if(isset($GLOBALS["LDAPDBS"]["alias_database"])){
		if(is_array($GLOBALS["LDAPDBS"]["alias_database"])){$alias_database_cf=$GLOBALS["LDAPDBS"]["alias_database"];}
	}

	if(isset($GLOBALS["LDAPDBS"]["virtual_mailbox_maps"])){
		if(is_array($GLOBALS["LDAPDBS"]["virtual_mailbox_maps"])){$virtual_mailbox_maps_cf=$GLOBALS["LDAPDBS"]["virtual_mailbox_maps"];}	
	}
	
	$contz=new postfix_extern();
	$contzdata=$contz->build_extern("master", "virtual_mailbox_maps");
	if($contzdata<>null){$virtual_mailbox_maps_cf[]=$contzdata;}
	
	$alias_maps_cf[]="hash:/etc/postfix/aliases";
	$alias_database_cf[]="hash:/etc/postfix/aliases";
	
	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["alias_maps"])." aliase(s)\n"; 
		
	
	
	
	@file_put_contents("/etc/postfix/aliases",implode("\n",$GLOBALS["alias_maps"]));	
	shell_exec("{$GLOBALS["postalias"]} -c /etc/postfix hash:/etc/postfix/aliases >/dev/null 2>&1");
	shell_exec("{$GLOBALS["newaliases"]}");		

	
	$extern=new postfix_extern();
	if($GLOBALS["VERBOSE"]){echo "*** Check external databases rules master/alias_maps ( line:".__LINE__.")";}
	$aliases_extern=$extern->build_extern("master","alias_maps");
	if($aliases_extern<>null){$alias_database_cf[]=$aliases_extern;}else{
		if($GLOBALS["VERBOSE"]){echo "*** Check external databases rules master/alias_maps -> Nothing to add ( line:".__LINE__.")";}
	}
	
	
	echo "Starting......: ".date("H:i:s")." Postfix building alias_maps\n";
	shell_exec("{$GLOBALS["postconf"]} -e \"alias_maps =". @implode(",",$alias_maps_cf)."\" >/dev/null 2>&1");
	
	
	echo "Starting......: ".date("H:i:s")." Postfix building alias_database\n";
	shell_exec("{$GLOBALS["postconf"]} -e \"alias_database =". @implode(",",$alias_database_cf)."\" >/dev/null 2>&1");
	
	if(count($virtual_mailbox_maps_cf)>0){	
		echo "Starting......: ".date("H:i:s")." Postfix building virtual_mailbox_maps\n";
		shell_exec("{$GLOBALS["postconf"]} -e \"virtual_mailbox_maps =". @implode(",",$virtual_mailbox_maps_cf)."\" >/dev/null 2>&1");
	}else{
		shell_exec("{$GLOBALS["postconf"]} -e \"virtual_mailbox_maps = \" >/dev/null 2>&1");
	}

	
}




function aliases(){
	$ldap=new clladp();
	if($ldap->EnableManageUsersTroughActiveDirectory){
		aliases_ad();
		return;
	}
	$filter="(&(objectClass=userAccount)(mailAlias=*))";
	$attrs=array("mail","mailAlias");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$mail=trim($hash[$i]["mail"][0]);
		
		for($t=0;$t<$hash[$i]["mailalias"]["count"];$t++){
			$hash[$i]["mailalias"][$t]=trim($hash[$i]["mailalias"][$t]);
			if($hash[$i]["mailalias"][$t]==null){continue;}
			$GLOBALS["virtual_alias_maps"]["{$hash[$i]["mailalias"][$t]}"]="{$hash[$i]["mailalias"][$t]}\t$mail";
		}
	}

}

function aliases_ad(){
	$ldap=new ldapAD();
	$filter="(&(objectClass=user)(userPrincipalName=*))";
	$attrs=array("userPrincipalName","mail");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=trim($hash[$i]["mail"][0]);
		$userPrincipalName=trim($hash[$i]["userprincipalname"][0]);
		$GLOBALS["virtual_alias_maps"][$userPrincipalName]="$userPrincipalName\t$mail";
		
	}
	
}











function recipient_canonical_maps_build(){
	$ldap=new clladp();
	$filter="(&(objectClass=RecipientCanonicalMaps)(cn=*))";
	$attrs=array("cn","MailAlternateAddress");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["cn"][0];
		$canonical=$hash[$i][strtolower("MailAlternateAddress")][0];
		$GLOBALS["recipient_canonical_maps"][]="$mail\t$canonical";
	}		
	
	$q=new mysql();
	$sql="SELECT * FROM smtp_generic_maps WHERE ou='POSTFIX_MAIN' AND recipient_canonical_maps=1 ORDER BY generic_from";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}
		$GLOBALS["recipient_canonical_maps"][]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}
	
}

function smtp_sasl_password_maps_build(){
	$ldap=new clladp();
	$smtp_sasl_password_maps=array();
	$main=new maincf_multi();
	$filter="(&(objectClass=PostfixSmtpSaslPaswordMaps)(cn=*))";
	$attrs=array("cn","SmtpSaslPasswordString");
	$dn="cn=smtp_sasl_password_maps,cn=artica,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["cn"][0];
		$value=trim($hash[$i][strtolower("SmtpSaslPasswordString")][0]);
		if($value==null){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." skip  $mail (no password)\n";}
			continue;
		}
		if($value==":"){continue;}
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." adding  $mail\n";}
		$smtp_sasl_password_maps[$mail]=$value;
	}

	$filter="(&(objectClass=SenderDependentSaslInfos)(cn=*))";
	$attrs=array("cn","SenderCanonicalRelayPassword");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["cn"][0];
		$value=trim($hash[$i][strtolower("SenderCanonicalRelayPassword")][0]);
		if($value==null){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." skip  $mail (no password)\n";}
			continue;
		}
		if($value==":"){continue;}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." adding  $mail\n";}
		
		$smtp_sasl_password_maps[$mail]=$value;
	}

	if(is_array($smtp_sasl_password_maps)){
		while (list ($mail, $value) = each ($smtp_sasl_password_maps) ){
			$GLOBALS["smtp_sasl_password_maps"][]="$mail\t$value";
		}
	}
	
	$q=new mysql();
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM relay_host WHERE enabledauth=1","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$relay_text=$main->RelayToPattern($ligne["relay"], $ligne["relay_port"], $ligne["lookups"]);
		$username=$ligne["username"];
		$password=$ligne["password"];
		$GLOBALS["smtp_sasl_password_maps"][]="$relay_text\t{$username}:$password";
	}
	
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("sender_dependent_relay_host","enabledauth","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `enabledauth` smallint(1) NULL,
		ADD INDEX ( `enabledauth` )";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$results=$q->QUERY_SQL("SELECT * FROM relay_host WHERE enabledauth=1","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$relay_text=$main->RelayToPattern($ligne["relay"], $ligne["relay_port"], $ligne["lookups"]);
		$username=$ligne["username"];
		$password=$ligne["password"];
		$GLOBALS["smtp_sasl_password_maps"][]="$relay_text\t{$username}:$password";
	}
	

	
	
	$results=$q->QUERY_SQL("SELECT * FROM sender_dependent_relay_host WHERE enabledauth=1","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["relay"]=="*"){continue;}
		$relay_text=$main->RelayToPattern($ligne["relay"], $ligne["relay_port"], $ligne["lookups"]);
		$username=$ligne["username"];
		$password=$ligne["password"];
		$GLOBALS["smtp_sasl_password_maps"][]="$relay_text\t{$username}:$password";
	}
	
	

}

function smtp_sasl_password_maps(){
	smtp_sasl_password_maps_build();
	if(!isset($GLOBALS["smtp_sasl_password_maps"])){$GLOBALS["smtp_sasl_password_maps"]=null;}
	if(!is_array($GLOBALS["smtp_sasl_password_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 smtp password rule(s)\n"; 
		shell_exec("{$GLOBALS["postconf"]} -X \"smtp_sasl_password_maps\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -e \"smtp_sasl_auth_enable =no\" >/dev/null 2>&1");
		
		return;
	}
	reset($GLOBALS["smtp_sasl_password_maps"]);
	while (list ($index, $value) = each ($GLOBALS["smtp_sasl_password_maps"]) ){$newarray[$value]=$value;}
	while (list ($index, $value) = each ($newarray) ){$newarray2[]=$value;}		

	echo "Starting......: ".date("H:i:s")." Postfix ". count($newarray2)." smtp password rule(s)\n"; 
	@file_put_contents("/etc/postfix/smtp_sasl_password",implode("\n",$newarray2));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/smtp_sasl_password >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"smtp_sasl_password_maps = hash:/etc/postfix/smtp_sasl_password\" >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"smtp_sasl_auth_enable = yes\" >/dev/null 2>&1");
}

function  sender_dependent_default_transport_maps_build(){
	$q=new mysql();
	$main=new maincf_multi();
	$sender_dependent_default_transport_maps=array();
	$q=new mysql();
	$sql="SELECT * FROM sender_dependent_relay_host WHERE enabled=1 
			AND `override_transport`=1 
			AND `override_relay`=1 
			AND `hostname`='master' ORDER by zOrders";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$relay=$ligne["relay"];
		$relay_port_text=null;
		$relay_port=$ligne["relay_port"];
		$lookups=$ligne["lookups"];
		$relay_text=$main->RelayToPattern($relay, $relay_port,$lookups);
		if($ligne["directmode"]==1){$relay_text="{$ligne["zmd5"]}:";}
		$domain=$ligne["domain"];
		
		$sender_dependent_default_transport_maps[$domain]=$relay_text;
	}
	
	
	
	
	
	
	if(is_array($sender_dependent_default_transport_maps)){
		while (list ($mail, $value) = each ($sender_dependent_default_transport_maps) ){
			if(strpos("   $mail", "@")==0){$mail="@$mail";}
			$mail=str_replace(".", "\.", $mail);
			$mail=str_replace("*", ".*", $mail);
			
			$GLOBALS["sender_dependent_default_transport_maps"][]="/$mail/\t$value";
		}
	}
}
function relayhost(){$main=new maincf_multi("master");$main->relayhost();return;}
function sender_dependent_relayhost_maps_build(){
	$ldap=new clladp();
	$main=new maincf_multi();
	$sender_dependent_relayhost_maps=array();
	$filter="(&(objectClass=SenderDependentRelayhostMaps)(cn=*))";
	$attrs=array("cn","SenderRelayHost");
	$dn="cn=Sender_Dependent_Relay_host_Maps,cn=artica,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["cn"][0];
		$value=trim($hash[$i][strtolower("SenderRelayHost")][0]);
		if($value==null){continue;}
		if($value==":"){continue;}
		$sender_dependent_relayhost_maps[$mail]=$value;
		//$GLOBALS["sender_dependent_relayhost_maps"][]="$mail\t$value";
	}
	
	$filter="(&(objectClass=userAccount)(mail=*))";
	$attrs=array("mail","AlternateSmtpRelay");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["mail"][0];
		if(!isset($hash[$i][strtolower("AlternateSmtpRelay")])){continue;}
		$value=trim($hash[$i][strtolower("AlternateSmtpRelay")][0]);
		if($value==null){continue;}
		if($value==":"){continue;}
		$sender_dependent_relayhost_maps[$mail]=$value;
		//$GLOBALS["sender_dependent_relayhost_maps"][]="$mail\t$value";
	}	
	
	$filter="(&(objectClass=SenderDependentSaslInfos)(cn=*))";
	$attrs=array("cn","SenderCanonicalRelayHost");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["cn"][0];
		$value=trim($hash[$i][strtolower("SenderCanonicalRelayHost")][0]);
		if($value==null){continue;}
		if($value==":"){continue;}
		$sender_dependent_relayhost_maps[$mail]=$value;
	}
	
	
	$arr=array("SmtpSaslPasswordString");
	$filter="(&(objectclass=PostfixSmtpSaslPaswordMaps)(cn=*))";
	$dn="cn=smtp_sasl_password_maps,cn=artica,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail="{$hash[$i]["cn"][0]}";
		$value=trim($hash[$i][strtolower("SmtpSaslPasswordString")][0]);
		if($value==null){continue;}
		if($value==":"){continue;}
		$sender_dependent_relayhost_maps[$mail]=$value;
	}
	
	$q=new mysql();

	$sql="SELECT * FROM sender_dependent_relay_host WHERE enabled=1
			AND `override_transport`=0
			AND `override_relay`=0
			AND `hostname`='master' ORDER by zOrders";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$relay=$ligne["relay"];
		$relay_port_text=null;
		$relay_port=$ligne["relay_port"];
		$lookups=$ligne["lookups"];
		$relay_text=$main->RelayToPattern($relay, $relay_port, $lookups);
		if($ligne["directmode"]==1){$relay_text="smtp:";}
		$domain=$ligne["domain"];
		$sender_dependent_relayhost_maps[$domain]=$relay_text;
	}	

	if(is_array($sender_dependent_relayhost_maps)){
		while (list ($mail, $value) = each ($sender_dependent_relayhost_maps) ){
			$mail=str_replace(".", "\.", $mail);
			$mail=str_replace("*", ".*", $mail);
			if(strpos($mail, "@")==0){$mail=".*@$mail";}
			$GLOBALS["sender_dependent_relayhost_maps"][]="/$mail/\t$value";
		}
	}

}



function sender_dependent_relayhost_maps(){
	sender_dependent_relayhost_maps_build();
	if(!is_array($GLOBALS["sender_dependent_relayhost_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender dependent relayhost rule(s)\n"; 
		shell_exec("{$GLOBALS["postconf"]} -X \"sender_dependent_relayhost_maps\" >/dev/null 2>&1");
		return;
	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["sender_dependent_relayhost_maps"])." sender dependent relayhost rule(s)\n"; 
	@file_put_contents("/etc/postfix/sender_dependent_relayhost",implode("\n",$GLOBALS["sender_dependent_relayhost_maps"])."\n");
	//shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/sender_dependent_relayhost >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"sender_dependent_relayhost_maps = regexp:/etc/postfix/sender_dependent_relayhost\" >/dev/null 2>&1");
}


function sender_dependent_default_transport_maps(){
	sender_dependent_default_transport_maps_build();
	
	
	if(!isset($GLOBALS["sender_dependent_default_transport_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender dependent default transport rule(s)\n";
		shell_exec("{$GLOBALS["postconf"]} -X \"sender_dependent_default_transport_maps\" >/dev/null 2>&1");
		@file_put_contents("/etc/postfix/sender_dependent_default_transport_maps","#");
		return;
		
	}
	
	if(!is_array($GLOBALS["sender_dependent_default_transport_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender dependent default transport rule(s)\n";
		shell_exec("{$GLOBALS["postconf"]} -X \"sender_dependent_default_transport_maps\" >/dev/null 2>&1");
		@file_put_contents("/etc/postfix/sender_dependent_default_transport_maps","#");
		return;
	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["sender_dependent_default_transport_maps"])." sender dependent default transport rule(s)\n";
	@file_put_contents("/etc/postfix/sender_dependent_default_transport_maps",implode("\n",$GLOBALS["sender_dependent_default_transport_maps"])."\n");
	shell_exec("{$GLOBALS["postconf"]} -e \"sender_dependent_default_transport_maps = regexp:/etc/postfix/sender_dependent_default_transport_maps\" >/dev/null 2>&1");

}



function sender_canonical_maps_build(){
	$ldap=new clladp();
	$filter="(&(objectClass=userAccount)(mail=*))";
	$attrs=array("mail","SenderCanonical");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["mail"][0];
		if(!isset($hash[$i][strtolower("SenderCanonical")])){continue;}
		$canonical=$hash[$i][strtolower("SenderCanonical")][0];
		if($canonical==null){continue;}
		$GLOBALS["sender_canonical_maps"][]="$mail\t$canonical";
		$GLOBALS["smtp_generic_maps"][]="$mail\t$canonical";
	}
	
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("smtp_generic_maps","sender_canonical_maps","artica_backup")){
		$sql="ALTER TABLE `smtp_generic_maps` ADD `sender_canonical_maps` smallint(1)  NOT NULL DEFAULT '0',ADD INDEX ( `sender_canonical_maps` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	$sql="SELECT * FROM smtp_generic_maps WHERE ou='POSTFIX_MAIN' AND sender_canonical_maps=1 ORDER BY generic_from";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}
		$GLOBALS["sender_canonical_maps"][]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}

	
	
			
}

function smtp_generic_maps_build_global(){
	$q=new mysql();
	$sql="SELECT * FROM smtp_generic_maps WHERE ou='POSTFIX_MAIN' ORDER BY generic_from";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}		
		$GLOBALS["smtp_generic_maps"][]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}
}


function sender_canonical_maps(){
	sender_canonical_maps_build();
	
	if(!isset($GLOBALS["sender_canonical_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender retranslation rule(s)\n";
		shell_exec("{$GLOBALS["postconf"]} -X \"sender_canonical_maps\" >/dev/null 2>&1");
		return;
	}
	
	
	if(!is_array($GLOBALS["sender_canonical_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender retranslation rule(s)\n"; 
		shell_exec("{$GLOBALS["postconf"]} -X \"sender_canonical_maps\" >/dev/null 2>&1");
		return;
	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["sender_canonical_maps"])." sender retranslation rule(s)\n"; 
	@file_put_contents("/etc/postfix/sender_canonical",implode("\n",$GLOBALS["sender_canonical_maps"]));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/sender_canonical >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"sender_canonical_maps = hash:/etc/postfix/sender_canonical\" >/dev/null 2>&1");
}

function smtp_generic_maps(){
	
	
	
	if(!is_array($GLOBALS["smtp_generic_maps"])){
		build_progress_smtp_generic_maps("{building}: smtp_generic_maps 0 items",70);
		echo "Starting......: ".date("H:i:s")." 0 SMTP generic retranslations rule(s)\n"; 
		shell_exec("{$GLOBALS["postconf"]} -X \"smtp_generic_maps\" >/dev/null 2>&1");
	}	
	
	build_progress_smtp_generic_maps(" ". count($GLOBALS["smtp_generic_maps"])." SMTP generic retranslations rule(s)",40);
	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["smtp_generic_maps"])." SMTP generic retranslations rule(s)\n"; 
	@file_put_contents("/etc/postfix/smtp_generic_maps",implode("\n",$GLOBALS["smtp_generic_maps"])."\n");
	build_progress_smtp_generic_maps("{compiling}",50);
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/smtp_generic_maps >/dev/null 2>&1");
	build_progress_smtp_generic_maps("{save}",60);
	shell_exec("{$GLOBALS["postconf"]} -e \"smtp_generic_maps = hash:/etc/postfix/smtp_generic_maps\" >/dev/null 2>&1");
}






function recipient_canonical_maps(){
	recipient_canonical_maps_build();
	$recipient_canonical_maps=array();
	$pst=new postfix_extern();
	$pstData=$pst->build_extern("master", "recipient_canonical_maps");
	if($pstData<>null){$recipient_canonical_maps[]=$pstData;}
	if(!isset($GLOBALS["recipient_canonical_maps"])){$GLOBALS["recipient_canonical_maps"]=array();}
	
	if(count($GLOBALS["recipient_canonical_maps"])>0){
		echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["recipient_canonical_maps"])." recipients retranslation rule(s)\n"; 
		$recipient_canonical_maps[]="hash:/etc/postfix/recipient_canonical";
		@file_put_contents("/etc/postfix/recipient_canonical",implode("\n",$GLOBALS["recipient_canonical_maps"]));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/recipient_canonical >/dev/null 2>&1");
	}
	
	
	if(count($recipient_canonical_maps)>0){
		echo "Starting......: ".date("H:i:s")." Postfix ". count($recipient_canonical_maps)." retranslation database(s)\n"; 
		shell_exec("{$GLOBALS["postconf"]} -e \"recipient_canonical_maps = ".@implode(", ", $recipient_canonical_maps)."\" >/dev/null 2>&1");
	}else{
		echo "Starting......: ".date("H:i:s")." Postfix 0 retranslation database\n";
		shell_exec("{$GLOBALS["postconf"]} -X \"recipient_canonical_maps\" >/dev/null 2>&1");
	}
}









function postmaster(){
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	if($hostname==null){$hostname=$users->hostname;}
	if($GLOBALS["DEBUG"]){echo "postmaster():: Hostname=$hostname\n";}
	$hosts=explode(".",$hostname);
	if(count($hosts)>0){$mydomain_default="\\\$myhostname";}else{$mydomain_default="localdomain";}
	
		
	$PostfixPostmaster=trim($sock->GET_INFO("PostfixPostmaster"));
	$PostfixPostmasterSender=trim($sock->GET_INFO("PostfixPostmasterSender"));
	if($PostfixPostmaster==null){
		$error_notice_recipient="postmaster";
		$delay_notice_recipient="postmaster";
		$empty_address_recipient="MAILER-DAEMON";
		$myorigin="\\\$myhostname";
	}else{
		$error_notice_recipient="$PostfixPostmaster";
		$delay_notice_recipient="$PostfixPostmaster";
		$empty_address_recipient="$PostfixPostmaster";
	}
	shell_exec("{$GLOBALS["postconf"]} -e \"error_notice_recipient =$error_notice_recipient\" >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"delay_notice_recipient =$delay_notice_recipient\" >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"empty_address_recipient =$empty_address_recipient\" >/dev/null 2>&1");
	
	$address_verify_sender="\\\$double_bounce_sender";
	$double_bounce_sender="double-bounce";
	$mydomain=$mydomain_default;
	
	if($PostfixPostmasterSender<>null){
		if(preg_match("#(.+?)@(.+)#",$PostfixPostmasterSender,$re)){
			$mydomain=$re[2];
			$myorigin="\$mydomain";
		}
		$address_verify_sender=$PostfixPostmasterSender;
		$double_bounce_sender=$PostfixPostmasterSender;
	
	}
	
	if($GLOBALS["DEBUG"]){echo "postmaster():: mydomain =$mydomain\n";}
	
	shell_exec("{$GLOBALS["postconf"]} -e \"address_verify_sender =$address_verify_sender\" >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"double_bounce_sender =$double_bounce_sender\" >/dev/null 2>&1");
	shell_exec("{$GLOBALS["postconf"]} -e \"mydomain =$mydomain\" >/dev/null 2>&1");	
}



function DUMP_EXTERNALS_DBS(){
		$dbmaps=new postfix_extern();		
		while (list ($type, $numeric) = each ($dbmaps->classTypes) ){
			echo "DUMP class master:: $type [$numeric]:\n";
			$contz=$dbmaps->build_extern("master",$type);
			echo "Result: `$contz`\n";
		}
}





?>
