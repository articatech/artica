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
$GLOBALS["PROGRESS_FILE"]=null;
$GLOBALS["POURC_START"]=0;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--pourc=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POURC_START"]=$re[1];}
if(preg_match("#--progress-file=(.+?)\s+#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS_FILE"]=$re[1];}



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


if($argv[1]=="--relayhost"){
	internal_pid($argv);
	relayhost();
	perso_settings();
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();
}
if($argv[1]=="--restricted-relais"){
	restrict_relay_domains();
	die();
}
if($argv[1]=="--mailbox-transport-maps"){
	mailbox_transport_maps();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
	die();
}


start();

function build_progress($text,$pourc){
	if($GLOBALS["POURC_START"]>0){if($pourc<$GLOBALS["POURC_START"]){$pourc=$GLOBALS["POURC_START"];}}
	if($GLOBALS["POURC_START"]>0){if($pourc>95){$pourc=95;}}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress";
	if($GLOBALS["PROGRESS_FILE"]<>null){$cachefile=$GLOBALS["PROGRESS_FILE"];}
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}




function start(){
	build_progress("Loading LDAP config",15);
	LoadLDAPDBs();
	build_progress("Loading Transport data",20);
	transport_maps_search();
	build_progress("Loading relais_domains_search",25);
	relais_domains_search();
	build_progress("Building build_transport_maps",30);
	build_transport_maps();
	build_progress("Building Transport database",35);
	build_relay_domains();
	build_progress("Building restrict_relay_domains",40);
	restrict_relay_domains();
	build_progress("Building build_cyrus_lmtp_auth",50);
	build_cyrus_lmtp_auth();
	build_progress("Building relay_recipient_maps_build",55);
	relay_recipient_maps_build();
	$hashT=new main_hash_table();
	$hashT->mydestination();
	build_progress("Building mailbox_transport_maps",60);
	mailbox_transport_maps();
	build_progress("Building relayhost",70);
	relayhost();
	build_progress("Building perso_settings",80);
	perso_settings();
	if($GLOBALS["POURC_START"]==0){
		build_progress("{reloading_smtp_service}",90);
		shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");
		build_progress("{done}",100);
	}
	
}
function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("/etc/postfix/main.cf");
}
function mailbox_transport_maps(){
	$f=array();
	$DestinationFile="/etc/postfix/mailbox_transport_maps";
	$sql="SELECT * FROM postfix_transport_mailbox WHERE hostname='master'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$xType=$ligne["xType"];
		$pattern="lmtp:{$ligne["lmtp_address"]}";
		if($xType==1){
			$hash=$ldap->hash_users_ou($ligne["uid"]);
			while (list ($uid, $none) = each ($hash) ){if(trim($uid)==null){continue;}$f[]="$uid\t$pattern";}
			continue;
		}
		$f[]="{$ligne["uid"]}\t$pattern";
	}

	@file_put_contents($DestinationFile,@implode("\n",$f));
	shell_exec("{$GLOBALS["postmap"]} hash:$DestinationFile >/dev/null 2>&1");
	if(count($f)>0){
		shell_exec("{$GLOBALS["postconf"]} -e \"mailbox_transport_maps = hash:$DestinationFile\" >/dev/null 2>&1");
	}else{
		shell_exec("{$GLOBALS["postconf"]} -X \"mailbox_transport_maps\" >/dev/null 2>&1");
	}

}

function relayhost(){

	$main=new maincf_multi("master");
	$main->relayhost();
	return;


}
function relay_recipient_maps_by_transport(){
	$unix=new unix();
	$f=array();
	$sql="SELECT recipient FROM postfix_transport_recipients WHERE enabled=1 AND hostname='master'";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){$unix->send_email_events("Fatal:$this->myhostname $q->mysql_error", "function:".__FUNCTION__."\nFile:".__FILE__."\nLIne:".__LINE__, "postfix");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$email=$ligne["recipient"];
		$email=trim($email);
		if($email==null){continue;}
		if(!preg_match("#^.*?@.*#", $email)){continue;}
		$f[]="$email\tOK";
	}
	if(count($f)>0){
		@file_put_contents("/etc/postfix/relay_recipient_maps_transport", @implode("\n", $f));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/relay_recipient_maps_transport >/dev/null 2>&1");
		return "hash:/etc/postfix/relay_recipient_maps_transport";
	}
}

function relay_recipient_maps_build(){
	shell_exec("{$GLOBALS["postconf"]} -X \"relay_recipient_maps\" >/dev/null 2>&1");
}

function build_cyrus_lmtp_auth(){
	$users=new usersMenus();
	$disable=false;
	if($users->ZABBIX_INSTALLED){$disable=true;}else{
		if(!$users->cyrus_imapd_installed){$disable=true;}
	}

	if($disable){
		shell_exec("{$GLOBALS["postconf"]} -e \"lmtp_sasl_auth_enable =no\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -X \"lmtp_sasl_password_maps\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -X \"lmtp_sasl_security_options\" >/dev/null 2>&1");
		return;
	}


	$sock=new sockets();
	$page=CurrentPageName();
	$CyrusEnableLMTPUnix=$sock->GET_INFO("CyrusEnableLMTPUnix");
	if($CyrusEnableLMTPUnix==1){
		shell_exec("{$GLOBALS["postconf"]} -e \"lmtp_sasl_auth_enable =no\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -X \"lmtp_sasl_password_maps\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -X \"lmtp_sasl_security_options\" >/dev/null 2>&1");
	}else{
		$ldap=new clladp();
		$CyrusLMTPListen=trim($sock->GET_INFO("CyrusLMTPListen"));
		$cyruspass=$ldap->CyrusPassword();
		if($CyrusLMTPListen==null){return;}
		@file_put_contents("/etc/postfix/lmtpauth","$CyrusLMTPListen\tcyrus:$cyruspass");
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/lmtpauth >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -e \"lmtp_sasl_auth_enable =yes\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -e \"lmtp_sasl_password_maps = hash:/etc/postfix/lmtpauth\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -e \"lmtp_sasl_mechanism_filter = plain, login\" >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -X \"lmtp_sasl_security_options\" >/dev/null 2>&1");
	}

}

function restrict_relay_domains(){
	@file_put_contents("/etc/postfix/relay_domains_restricted","\n");
	$ldap=new clladp();
	$q=new mysql();
	$f=array();
	$relaysdomains=$ldap->hash_get_relay_domains();
	$main=new maincf_multi("master","master");
	$relay_domains_restricted=$main->relay_domains_restricted();
	echo "Starting......: ".date("H:i:s")." Postfix ".count($relay_domains_restricted)." restricted defined domains\n";

	if(count($relaysdomains)>0){
		while (list ($domain, $ligne) = each ($relaysdomains) ){
			if(preg_match("#^@(.+)#",$domain,$re)){$domain=$re[1];}
			if(!isset($relay_domains_restricted[$domain])){continue;}
			$f[]="$domain\tartica_restrict_relay_domains";
			echo "Starting......: ".date("H:i:s")." Postfix `$domain` will be restricted\n";
		}
	}
	echo "Starting......: ".date("H:i:s")." Postfix ". count($f)." restricted relayed domains\n";
	@file_put_contents("/etc/postfix/relay_domains_restricted",implode("\n",$f));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/relay_domains_restricted >/dev/null 2>&1");

}

function relais_domains_search(){
	$sock=new sockets();
	$PostfixLocalDomainToRemote=$sock->GET_INFO("PostfixLocalDomainToRemote");
	if(!is_numeric($PostfixLocalDomainToRemote)){$PostfixLocalDomainToRemote=0;}

	$ldap=new clladp();
	$filter="(&(objectClass=PostFixRelayDomains)(cn=*))";
	$attrs=array("cn");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$GLOBALS["relay_domains"][]=$hash[$i]["cn"][0]."\tOK";

	}

	if($PostfixLocalDomainToRemote==1){
		$filter="(&(objectClass=organizationalUnit)(associatedDomain=*))";
		$attrs=array("associatedDomain");
		$dn="$ldap->suffix";
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);

		for($i=0;$i<$hash["count"];$i++){
			for($t=0;$t<$hash[$i]["associateddomain"]["count"];$t++){
				$GLOBALS["relay_domains"][]=$hash[$i][strtolower("associatedDomain")][$t]."\tOK";
			}
		}
	}




	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["relay_domains"])." relay domain(s)\n";
}
function build_relay_domains(){
	if(!is_array($GLOBALS["relay_domains"])){
		shell_exec("{$GLOBALS["postconf"]} -e \"relay_domains = \" >/dev/null 2>&1");
		return null;
	}

	shell_exec("{$GLOBALS["postconf"]} -e \"relay_domains =hash:/etc/postfix/relay_domains\" >/dev/null 2>&1");
	@file_put_contents("/etc/postfix/relay_domains",implode("\n",$GLOBALS["relay_domains"]));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/relay_domains >/dev/null 2>&1");

}

function build_transport_maps(){
	if(!isset($GLOBALS["transport_maps_AT"])){$GLOBALS["transport_maps_AT"]=array();}
	$main=new maincf_multi("master","master");
	$main->bann_destination_domains();
	$users=new usersMenus();
	$CountDeMailMan=0;
	if(!is_file("/etc/postfix/transport.throttle")){@file_put_contents("/etc/postfix/transport.throttle"," ");}

	if(!is_array($GLOBALS["transport_maps"])){
		shell_exec("{$GLOBALS["postconf"]} -e \"transport_maps = hash:/etc/postfix/transport.throttle\" >/dev/null 2>&1");
	}

	if($users->MAILMAN_INSTALLED){
		$GLOBALS["transport_maps"]=$main->mailman_transport($GLOBALS["transport_maps"]);
	}


	while (list ($num, $ligne) = each ($GLOBALS["transport_maps"]) ){
		if($ligne==null){continue;}
		$array[]="$num\t$ligne";

	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($array)." routings rules\n";

	if(count($GLOBALS["transport_maps_AT"])>0){
		while (list ($num, $ligne) = each ($GLOBALS["transport_maps_AT"]) ){
			if($ligne==null){continue;}
			$array[]="$num\t$ligne";

		}}

		if($users->MAILMAN_INSTALLED){shell_exec("{$GLOBALS["postconf"]} -e \"mailman_destination_recipient_limit = 1\" >/dev/null 2>&1");}
		@file_put_contents("/etc/postfix/transport",implode("\n",$array));
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/transport >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix/transport.throttle >/dev/null 2>&1");
		shell_exec("{$GLOBALS["postconf"]} -e \"transport_maps = hash:/etc/postfix/transport.throttle, hash:/etc/postfix/transport, hash:/etc/postfix/transport.banned,hash:/etc/postfix/copy.transport\" >/dev/null 2>&1");

}

function transport_maps_search(){
	$ldap=new clladp();
	$unix=new unix();
	$sock=new sockets();
	$PostfixLocalDomainToRemote=$sock->GET_INFO("PostfixLocalDomainToRemote");
	if(!is_numeric($PostfixLocalDomainToRemote)){$PostfixLocalDomainToRemote=0;}
	$PostfixLocalDomainToRemoteAddr=$sock->GET_INFO("PostfixLocalDomainToRemoteAddr");
	if(!isset($GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"])){$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"]=array();}

	//----------------------------------------------------------------------------------------------------------
	$filter="(&(objectClass=transportTable)(cn=*))";
	$attrs=array("cn","transport");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$domain=$hash[$i]["cn"][0];
		$transport=$hash[$i]["transport"][0];

		if(substr($domain,0,1)=="@"){$domain=substr($domain,1,strlen($domain));}
		if(!$GLOBALS["transport_mem"]["$domain"]){$GLOBALS["transport_maps"]["$domain"]="$transport";}

		if(strpos("  $domain","@")==0){$domain="@$domain";}
		if(!$GLOBALS["transport_mem"]["$domain"]){$GLOBALS["transport_maps_AT"]["$domain"]="$transport";}
		$GLOBALS["transport_mem"]["$domain"]=true;
	}

	//----------------------------------------------------------------------------------------------------------
	if($PostfixLocalDomainToRemote==1){
		$filter="(&(objectClass=organizationalUnit)(associatedDomain=*))";
		$attrs=array("associatedDomain");
		$dn="$ldap->suffix";
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		$transport="smtp:$PostfixLocalDomainToRemoteAddr";
		for($i=0;$i<$hash["count"];$i++){
			for($t=0;$t<$hash[$i]["associateddomain"]["count"];$t++){
				$domain=$hash[$i][strtolower("associatedDomain")][$t];

				if(substr($domain,0,1)=="@"){$domain=substr($domain,1,strlen($domain));}
				if(!$GLOBALS["transport_mem"]["$domain"]){$GLOBALS["transport_maps"]["$domain"]="$transport";}

				if(strpos("  $domain","@")==0){$domain="@$domain";}
				if(!$GLOBALS["transport_mem"]["$domain"]){$GLOBALS["transport_maps_AT"]["$domain"]="$transport";}
				$GLOBALS["transport_mem"]["$domain"]=true;
			}
		}
	}
	//----------------------------------------------------------------------------------------------------------
	$t=0;
	if(count($GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"])>0){
		while (list ($domain, $targeted_ip) = each ($GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"]) ){
			$transport="relay[$targeted_ip]:25";
			if(!$GLOBALS["transport_mem"]["@$domain"]){
				$t++;
				$GLOBALS["transport_maps"]["$domain"]="$transport";
				$GLOBALS["transport_maps_AT"]["$domain"]="$transport";
			}
			$GLOBALS["transport_mem"]["@$domain"]=true;
		}
	}
	echo "Starting......: ".date("H:i:s")." Postfix $t routed domains from external sources\n";

	$dn="cn=artica_smtp_sync,cn=artica,$ldap->suffix";
	$filter="(&(objectClass=InternalRecipients)(cn=*))";
	$attrs=array("cn","ArticaSMTPSenderTable");
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$email=$hash[$i]["cn"][0];
		$transport=$hash[$i][strtolower("ArticaSMTPSenderTable")][0];
		$uid=$ldap->uid_from_email($email);
		if($uid<>null){continue;}
		if(!$GLOBALS["transport_mem"]["$email"]){
			$GLOBALS["transport_maps"]["$email"]="$transport";
		}
		$GLOBALS["transport_mem"]["$email"]=true;
	}


	$sql="SELECT *  FROM postfix_transport_recipients WHERE hostname='master' AND enabled=1";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){$unix->send_email_events("Fatal: $q->mysql_error", "function:".__FUNCTION__."\nFile:".__FILE__."\nLIne:".__LINE__, "postfix");}
	while ($ligne = mysql_fetch_assoc($results)) {
		$email=$ligne["recipient"];
		$transport=$ligne["transport"];
		if(isset($GLOBALS["transport_mem"]["$email"])){continue;}
		$GLOBALS["transport_maps"]["$email"]="$transport";
		$GLOBALS["transport_mem"]["$email"]=true;

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