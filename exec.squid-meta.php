<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["NOCHECK"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--nochek#",implode(" ",$argv))){$GLOBALS["NOCHECK"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


xrun();


function xrun(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__);
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Starting......: ".date("H:i:s")." [META]: Already executed $pid\n";
		return;
	}
	
	
	
	
	if($unix->SQUID_ENABLED()==0){
		echo "Starting......: ".date("H:i:s")." [META]: Squid Not installed or disabled\n";
		return;
	}
	
	$myuuid=$unix->GetUniqueID();
	echo "Starting......: ".date("H:i:s")." [META]: My UUID = $myuuid\n";
	
	$md5_org=md5_file("/etc/squid3/acls_center_meta.conf");
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT gpid FROM metagroups_link WHERE uuid='$myuuid'","metaclient");
	
	$acls=new squid_acls();
	$acls->Build_Acls(false,true);
	
	if(count($acls->acls_array)==0){
		@file_put_contents("/etc/squid3/acls_center_meta.conf", "\n");
		@chown("/etc/squid3/acls_center_meta.conf", "squid");
		@chgrp("/etc/squid3/acls_center_meta.conf", "squid");
		$md5_new=md5_file("/etc/squid3/acls_center_meta.conf");
		if($md5_new<>$md5_org){
			$squidbin=$unix->LOCATE_SQUID_BIN();
			squid_admin_mysql(1, "Reload proxy service for Meta acls", null,__FILE__,__LINE__);
			shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
		}
		return;
	}
	
	$all_acls=@implode("\n", $acls->acls_array);
	$php=$unix->LOCATE_PHP5_BIN();
	
	$ACLS=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		echo "$myuuid is a member of group id {$ligne["gpid"]}\n";
		$ACLS=buildacls_fromgroup($ligne["gpid"],$ACLS);
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT * FROM meta_webfilters_acls WHERE metauuid='$myuuid'","metaclient");
	
	$aclsGroups=new squid_acls_groups();
	$aclsGroups->AsMeta=true;
	while ($ligne = mysql_fetch_assoc($results)) {
		$aclname=$ligne["aclname"];
		$httpaccess=$ligne["httpaccess"];
		$httpaccess_data=$ligne["httpaccess_data"];
		$reverse=false;
		$ID=$ligne["ID"];
		$valueToAdd=null;
		if($httpaccess=="deny_access_except"){$reverse=true;}
		echo "Starting......: ".date("H:i:s")." [META]: aclname[$ID]: $aclname/$httpaccess\n";
		
		if(isset($GLOBALS["ACLRULEXEC"][$ID])){
			echo "Starting......: ".date("H:i:s")." [META]: aclname[$ID]: Already executed, skip\n";
			continue;
		}
		
		$Groups=$aclsGroups->buildacls_bytype_items($ID,$reverse);
		
		if(count($Groups)==0){
			echo "Starting......: ".date("H:i:s")." [META]: aclname[$ID]: no group, skip...\n";
			continue;
		}
		
		$GLOBALS["ACLRULEXEC"][$ID]=true;
		$firstToken=getFirstToken($httpaccess,$httpaccess_data,$ID);
		$ACLS[]="$firstToken {$valueToAdd}".@implode(" ", $Groups);
		
	}
	
	@file_put_contents("/etc/squid3/acls_center_meta.conf", "$all_acls\n".@implode("\n", $ACLS)."\n");
	@chown("/etc/squid3/acls_center_meta.conf", "squid");
	@chgrp("/etc/squid3/acls_center_meta.conf", "squid");
	
	if(count($ACLS)==0){
		$md5_new=md5_file("/etc/squid3/acls_center_meta.conf");
		if($md5_new<>$md5_org){
			$squidbin=$unix->LOCATE_SQUID_BIN();
			squid_admin_mysql(1, "Reload proxy service for Meta acls", null,__FILE__,__LINE__);
			shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
		}
		return;
	}
	
	
	
	if(!$GLOBALS["NOCHECK"]){
		if(!isInSquidConf()){
			squid_admin_mysql(1, "Reconfigure proxy service for Meta acls", null,__FILE__,__LINE__);
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force --for-meta");
			return;
		}
	}
	
	$md5_new=md5_file("/etc/squid3/acls_center_meta.conf");
	if($md5_new<>$md5_org){
		$squidbin=$unix->LOCATE_SQUID_BIN();
		squid_admin_mysql(1, "Reload proxy service for Meta acls", null,__FILE__,__LINE__);
		shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	}
}

function isInSquidConf(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($www, $line) = each ($f) ){
		if(!preg_match("#acls_center_meta\.conf#", $line)){continue;}
		echo "Starting......: ".date("H:i:s")." [META]: Include acls_center_meta.conf OK\n";
		return true;
	}
	
	return false;
}

function buildacls_fromgroup($gpid,$ACLS){
	$q=new mysql();
	$sql="SELECT * FROM meta_webfilters_acls WHERE metagroup=$gpid AND enabled=1 ORDER BY xORDER";
	$results=$q->QUERY_SQL($sql,"metaclient");
	$mysql_num_rows=mysql_num_rows($results);
	if($mysql_num_rows==0){
		echo "Group ID:$gpid -> no acls...\n";
		return;
	}
	
	$IpClass=new IP();
	$aclsGroups=new squid_acls_groups();
	$aclsGroups->AsMeta=true;
	$unix=new unix();
	while ($ligne = mysql_fetch_assoc($results)) {
		$aclname=$ligne["aclname"];
		$httpaccess=$ligne["httpaccess"];
		$httpaccess_data=$ligne["httpaccess_data"];
		$reverse=false;
		$ID=$ligne["ID"];
		$valueToAdd=null;
		if($httpaccess=="deny_access_except"){$reverse=true;}
		echo "aclname[$ID]: $aclname/$httpaccess\n";
		
		if(isset($GLOBALS["ACLRULEXEC"][$ID])){
			echo "aclname[$ID]: Already executed, skip\n";
			continue;
		}
		
		$Groups=$aclsGroups->buildacls_bytype_items($ID,$reverse);
		
		if(count($Groups)==0){
			echo "aclname[$ID]: no group, skip...\n";
			continue;
		}
		
		$GLOBALS["ACLRULEXEC"][$ID]=true;
		$firstToken=getFirstToken($httpaccess,$httpaccess_data,$ID);
		$ACLS[]="$firstToken {$valueToAdd}".@implode(" ", $Groups);
			
		
		
	}
	
	return $ACLS;
	
}

function getFirstToken($httpaccess,$httpaccess_data,$ID){
	$IpClass=new IP();
	$unix=new unix();
	$valueToAdd=null;
	if($httpaccess=="deny_access_except"){$reverse=true;$firstToken="http_access deny";}
	if($httpaccess=="access_allow"){$firstToken="http_access allow";}
	if($httpaccess=="access_deny"){$firstToken="http_access deny";}
	if($httpaccess=="cache_deny"){$firstToken="cache deny";}
	if($httpaccess=="http_reply_access_deny"){$firstToken="http_reply_access deny";}
	if($httpaccess=="http_reply_access_allow"){$firstToken="http_reply_access allow";}
	if($httpaccess=="url_rewrite_access_deny"){$firstToken="url_rewrite_access deny";}
	if($httpaccess=="url_rewrite_access_allow"){$firstToken="url_rewrite_access allow";}
	if($httpaccess=="tcp_outgoing_address"){$firstToken="tcp_outgoing_address";}
	if($httpaccess=="request_header_add"){$firstToken="request_header_add";}
	if($httpaccess=="log_access"){$firstToken="access_log";}
	if($httpaccess=="deny_log"){$firstToken="access_log none";}
	
	if($httpaccess=="tcp_outgoing_tos"){
		$valueToAdd=$httpaccess_data;
		if($valueToAdd==null){continue;}
		$valueToAdd=$valueToAdd." ";
	}
		
	if($httpaccess=="reply_body_max_size"){
		$valueToAdd=intval($httpaccess_data);
		if($valueToAdd==0){continue;}
		$valueToAdd=$valueToAdd." MB ";
	}
	
	if($httpaccess=="tcp_outgoing_address"){
		$valueToAdd=$httpaccess_data;
		if($valueToAdd==null){continue;}
		if($IpClass->isValid($valueToAdd)){continue;}
		$LOCALSIPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(preg_match("#[0-9\.]+#", $valueToAdd)){
			$valueToAdd=trim($valueToAdd);
			if(!isset($LOCALSIPS[$valueToAdd])){
				$GLOBALS["tcp_outgoing_address_errors"][]="Error tcp_outgoing_address $valueToAdd NO SUCH ADDRESS";
				if($GLOBALS["VERBOSE"]){echo "tcp_outgoing_address \"$valueToAdd\" PORT:{$aclport} NO SUCH ADDRESS !!!\n";}
				continue;
			}
		}
			
		$valueToAdd=$valueToAdd." ";
	}
	
	if($httpaccess=="request_header_add"){
		$httpaccess_data=unserialize(base64_decode($httpaccess_data));
		$request_header_add_name=$httpaccess_data["header_name"];
		$request_header_add_value=$httpaccess_data["header_value"];
		if(trim($request_header_add_name)==null){continue;}
		if(trim($request_header_add_value)==null){continue;}
		$valueToAdd="$request_header_add_name \"$request_header_add_value\" ";
	
	}
		
	if($httpaccess=="log_access"){
		$valueToAdd="stdio:/var/log/squid/access_acl_$ID.csv csv_acls ";
	}
	
	
	
	return "$firstToken {$valueToAdd}";
	
}