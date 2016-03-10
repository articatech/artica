<?php
$GLOBALS["NO_COMPILE_POSTFIX"]=true;
$GLOBALS["DEBUG SOCKET"]=false;
include_once(dirname(__FILE__).'/ressources/class.qos.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid)){$ptime=$unix->PROCESS_TTL($pid);die();}


if($argv[1]=='--org'){import($argv[2],$argv[3]);}



function import($ou,$path){
	$usersM=new usersMenus();
	$unix=new unix();
	if(!is_file($path)){
		echo "$path, no such file\n";
		die();
	}
	
	
	$ldap=new clladp();
	$oudn="ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($oudn)){$ldap->AddOrganization($ou);}
	
	$tmpfile=$unix->FILE_TEMP();
	uncompress($path,$tmpfile);
	$datas=unserialize(base64_decode(@file_get_contents($tmpfile)));
	if(!is_array($datas)){
		if($GLOBALS["VERBOSE"]){echo "Unable to import $ou $path, no such array\n";return;}
		$unix->send_email_events(basename(__FILE__)."::Unable to import $ou $path, no such array", null, "import");
		die();
	}
	
	$usersArray=$datas["USERS"];
	$groupsArray=$datas["GROUPS"];
	unset($datas);
	echo "Creating groups in `$ou`";
	
	
	while (list ($num, $hash) = each ($groupsArray)){
		$ORG_GID=$hash["main_array"]["gid"];
		$gp=new groups();
		$gpid=$gp->GroupIDFromName($ou,$hash["groupName"]);
		
		
		if(!is_numeric($gpid)){
			$gp->groupName=$hash["groupName"]; 
			$gp->add_new_group($hash["groupName"],$ou);
			$gpid=$gp->GroupIDFromName($ou,$hash["groupName"]);
			
		}
		$gp=new groups($gpid);
		$members=$hash["members"];
		while (list ($a, $b) = each ($members)){
			echo "Insert $b user to {$hash["groupName"]}/$gpid\n";
			$gp->AddUsertoThisGroup($b);
		}
		
		$gp->saveDescription($hash["main_array"]["description"]);
		$gp->ArticaGroupPrivileges=$hash["main_array"]["ArticaGroupPrivileges"];
		$gp->Privileges_array=$hash["Privileges_array"];
		$gp->SavePrivileges();
		$GROUPSORGS[$ORG_GID]=$gpid;
		
	}
	
	
	while (list ($num, $hash) = each ($usersArray)){
		$array_groups=$hash["array_groups"];
		unset($hash["dn"]);
		unset($hash["UserExists"]);
		unset($hash["ou"]);
		unset($hash["local_sid"]);
		unset($hash["objectClass_array"]);
		unset($hash["group_id"]);
		unset($hash["sambaPrimaryGroupSID"]);
		unset($hash["accountGroup"]);
		unset($hash["uidNumber"]);
		
		unset($hash["sambaSID"]);
		unset($hash["sambaPrimaryGroupGID"]);
		unset($hash["gidNumber_array"]);
		$samba_groups=$hash["samba_groups"];
		unset($hash["ldapClass"]);
    	unset($hash["attributs_array"]);
    	unset($hash["samba_groups"]);
		
		$users=new user($hash["uid"]);
		$users->ou=$ou;
		$users->group_id=$GROUPSORGS[$array_groups[0]];
		while (list ($a, $orgd) = each ($hash)){
			$users->a=$orgd;
			
		}
		
		
		$users->add_user();
		if($usersM->SAMBA_INSTALLED){
			if(!$hash["NotASambaUser"]){
				$users->Samba_edit_user();
			}
		}
	}

	

	
	
}

function uncompress($srcName, $dstName) {
	$sfp = gzopen($srcName, "rb");
	$fp = fopen($dstName, "w");
	while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	gzclose($sfp);
	fclose($fp);
} 
