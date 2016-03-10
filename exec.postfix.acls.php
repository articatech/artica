<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.postfix.regex.inc");

$q=new mysql();
$ipClass=new IP();


echo "Starting......: ".date("H:i:s")." Building rules....\n";

$sql="SELECT ID,pattern FROM miltergreylist_acls WHERE `method`='whitelist' AND `type`='addr'";
$results=$q->QUERY_SQL($sql,"artica_backup");
while ($ligne = mysql_fetch_assoc($results)) {
	$ipaddr=trim($ligne["pattern"]);
	if($ipaddr==null){continue;}
	if($ipaddr=="127.0.0.1/8"){$ipaddr="127.0.0.0/8";}
	if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
	$MAINARRAY[]="$ipaddr\tOK rule id {$ligne["ID"]}";
}


$sql="SELECT ID,pattern FROM miltergreylist_acls WHERE `method`='blacklist' AND `type`='addr'";
$results=$q->QUERY_SQL($sql,"artica_backup");
while ($ligne = mysql_fetch_assoc($results)) {
	$ipaddr=$ligne["pattern"];
	if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
	$MAINARRAY[]="$ipaddr\tREJECT Go Away! rule id {$ligne["ID"]}";
}


echo "Starting......: ".date("H:i:s")." /etc/postfix/acls.cdir.cf ".count($MAINARRAY)." items\n";
@file_put_contents("/etc/postfix/acls.cdir.cf", @implode("\n", $MAINARRAY)."\n");



$miltergreylist_acls2=array();
$sql="SELECT ID,pattern FROM miltergreylist_acls WHERE `method`='whitelist' AND `type`='domain'";
$results=$q->QUERY_SQL($sql,"artica_backup");
while ($ligne = mysql_fetch_assoc($results)) {
	$domain=$ligne["pattern"];
	if(preg_match("#regex:\s+#", $domain)){continue;}
	$miltergreylist_acls2[trim(strtolower($domain))]=$ligne["ID"];
}

if(count($miltergreylist_acls2)>0){
	while (list ($domain, $ID) = each ($miltergreylist_acls2) ){
		if(!is_numeric($ID)){$PostDomains[]="$domain\tOK";continue;}
		$PostDomains[]="$domain\tOK";
	}
}

$sql="SELECT ID,pattern FROM miltergreylist_acls WHERE `method`='blacklist' AND `type`='domain'";
$results=$q->QUERY_SQL($sql,"artica_backup");
while ($ligne = mysql_fetch_assoc($results)) {
	$domain=$ligne["pattern"];
	if(preg_match("#regex:\s+#", $domain)){continue;}
	$miltergreylist_acls[trim(strtolower($domain))]=$ligne["ID"];
}

if(count($miltergreylist_acls)>0){
	while (list ($domain, $ID) = each ($miltergreylist_acls) ){
		if(!is_numeric($ID)){$PostDomains[]="$domain\tREJECT Go Away! $ID";continue;}
		$PostDomains[]="$domain\tREJECT Go Away! ACL N.$ID";
	}
}



$unix=new unix();
$postmap=$unix->find_program("postmap");
echo "Starting......: ".date("H:i:s")." /etc/postfix/blacklist.domains.cf ".count($PostDomains)." items\n";
@file_put_contents("/etc/postfix/blacklist.domains.cf", @implode("\n", $PostDomains)."\n");
shell_exec("$postmap hash:/etc/postfix/blacklist.domains.cf");


$MAINARRAY=array();
$sql="SELECT ID,pattern FROM miltergreylist_acls WHERE `method`='blacklist' AND `type`='from'";
$results=$q->QUERY_SQL($sql,"artica_backup");
while ($ligne = mysql_fetch_assoc($results)) {
	$from=$ligne["pattern"];
	$from=str_replace("'", "", $from);
	$ID=$ligne["ID"];
	$postfix_regex_compile=new postfix_regex_compile($from);
	$MAINARRAY[]="/^From: ".$postfix_regex_compile->return_regex()."/ REJECT Go Away! From $ID";
} 
echo "Starting......: ".date("H:i:s")." /etc/postfix/blacklist.headers.cf ".count($MAINARRAY)." items\n";

@file_put_contents("/etc/postfix/blacklist.headers.cf", @implode("\n", $MAINARRAY)."\n");
