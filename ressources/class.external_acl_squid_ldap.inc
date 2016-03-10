<?php


class external_acl_squid_ldap{
	public $Errors=array();
	private $SquidAddkerAlernates=array();
	
	function external_acl_squid_ldap(){
		
		
		
	}
	
	private function diff_time_min($time){
		$data1 = $time;
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
	}
	
	public function GetUserOU($member){
		if(isset($GLOBALS["GetUserOU"][$member])){return $GLOBALS["GetUserOU"][$member];}
		$infos=$this->ADLdap_userinfos($member);
		if(!isset($infos[0]["dn"])){return null;}
		$DNs=explode(",",$infos[0]["dn"]);
		while (list ($a, $part) = each ($DNs) ){
			$part=trim(strtolower($part));
			if(preg_match("#ou=(.+)#", $part,$re)){
				$GLOBALS["GetUserOU"][$member]=$re[1];
				return $GLOBALS["GetUserOU"][$member];
			}
		}
		
		$GLOBALS["GetUserOU"][$member]="users";
		return "users";
		
	}
	
	
	public function GetGroupsFromMember($member){
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]:[".__FUNCTION__."/".__LINE__."] Query Time:{$GLOBALS["QUERYTIME"]}");}
		$GLOBALS["TIMELOG"]++;
		
		
		if(isset($GLOBALS["QUERYTIME"])){
			$timeEx=$this->diff_time_min($GLOBALS["QUERYTIME"]);
			if($GLOBALS["DEBUG_GROUPS"] >0){  $this->WLOG("[GetGroupsFromMember]: QUERYTIME = {$timeEx}Mn/15mn");}
			if($timeEx>15){
				$this->WLOG("[PURGE]: ".count($GLOBALS["MEMBERS_GROUPS"])." item(s) are purged from Cache array after {$timeEx}mn");
				unset($GLOBALS["QUERYTIME"]);unset($GLOBALS["MEMBERS_GROUPS"]);unset($GLOBALS["GetUserOU"]);
				$GLOBALS["QUERYTIME"]=time();
			}
		}else{
			$GLOBALS["QUERYTIME"]=time();
		}
		
		if(isset($GLOBALS["MEMBERS_GROUPS"][$member])){
			if(is_array($GLOBALS["MEMBERS_GROUPS"][$member])){
				if(count($GLOBALS["MEMBERS_GROUPS"][$member])>0){
					if($GLOBALS["DEBUG_GROUPS"] >0){  $this->WLOG("[GetGroupsFromMember]: $member -> Return from memory");}
					return $GLOBALS["MEMBERS_GROUPS"][$member];
				}
			}
		}
		
		
		$this->BuildDefault();
		if($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]==1){
			if($GLOBALS["VERBOSE"]){  echo "[GetGroupsFromMember]: -> ADLdap_getgroups () RECURSIVE_GROUPS = 1\n";}
			$GLOBALS["MEMBERS_GROUPS"][$member]=$this->ADLdap_getgroups($member);
			if($GLOBALS["VERBOSE"]){echo "[ADLdap_getgroups] RETURN ".count($GLOBALS["MEMBERS_GROUPS"][$member])." members\n";}
			return $GLOBALS["MEMBERS_GROUPS"][$member];
		}
		
		
		$this->ConnectToLDAP();
		if(!isset($GLOBALS["QUERYTIME"])){$GLOBALS["QUERYTIME"]=time();}
		
		
	
		if(!$GLOBALS["BIND"]){
			$this->WLOG("[QUERY]: Error: BIND is broken -> reconnect");
			$this->ConnectToLDAP();
			if(!$GLOBALS["BIND"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
		}
	
		if(!$GLOBALS["CONNECTION"]){
			$this->WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect twice");
			$this->ConnectToLDAP();
		}
	
		if(!$GLOBALS["CONNECTION"]){
			$this->WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
			return false;
		}
	
		$array=array();
		@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
		$filter="(&(objectCategory=Person)(objectClass=user)(sAMAccountName=$member))";
	
	
	
		$GLOBALS["QUERIES_NUMBER"]++;
		$link_identifier=$GLOBALS["CONNECTION"];
		$base_dn=$GLOBALS["SUFFIX"];
		$attributes=array("memberOf","dn");
		$attrsonly=null;
		$sizelimit=null;
		$timelimit= $GLOBALS["LDAP_TIME_LIMIT"];
		if($GLOBALS["VERBOSE"]){$this->WLOG("[QUERY]::$filter -> $filter in $base_dn");}
	
	
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]:[".__FUNCTION__."/".__LINE__."] Query $filter -> $filter in $base_dn");}
		$sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);
		if (!$sr) {
			if(is_numeric(ldap_errno($GLOBALS["CONNECTION"]))){
				$error=ldap_errno($GLOBALS["CONNECTION"]);
				$errstr=@ldap_err2str($error);
					
				if($error==-1){
					if(!isset($GLOBALS["RETRY_AFTER_ERROR"])){
						$this->WLOG("[QUERY]: Error:`$error` ($errstr) re-connect and retry query...");
						$GLOBALS["RETRY_AFTER_ERROR"]=true;
						return GetGroupsFromMember($member);
					}else{
						$this->WLOG("[QUERY]: Error:`$error` ($errstr) Connection lost definitively");
						return false;
					}
	
				}
					
				$this->WLOG("[QUERY]: Error:`$error` ($errstr) suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
				return false;
			}else{
				$this->WLOG("[QUERY]: Error: unknown Error (ldap_errno not a numeric) suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
			}
		}
	
	
	
	
		$hash=ldap_get_entries($GLOBALS["CONNECTION"],$sr);
		if(!is_array($hash)){
			$this->WLOG("[QUERY]: Error: undefined, hash is not an array or did not find user...");
			return false;
		}

	
		unset($GLOBALS["RETRY_AFTER_ERROR"]);
		if(isset($hash[0]["memberof"])){
			for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
				if(preg_match("#^CN=(.+?),#i", $hash[0]["memberof"][$i],$re)){
					$re[1]=trim(strtolower($re[1]));
					if($GLOBALS["DEBUG"] == 1){  $this->WLOG("$member = \"{$re[1]}\""); }
					$array[$re[1]]=true;
				}
					
			}
		}
		if($GLOBALS["DEBUG"] == 1){  $this->WLOG("Return array of ".count($array)." items"); }
		
		$GLOBALS["MEMBERS_GROUPS"][$member]=$array;
		$this->WLOG("[CACHE]: $member -> ".count($GLOBALS["MEMBERS_GROUPS"][$member])." item(s) in cache array");
		if(count($GLOBALS["MEMBERS_GROUPS"][$member])<10){
			if(count($GLOBALS["MEMBERS_GROUPS"][$member])>0){
				$this->WLOG("[CACHE]: $member -> ".@implode(", ", $GLOBALS["MEMBERS_GROUPS"][$member]));
			}
		}
		return $array;
	
	}	
	
	
	
	
private function BuildDefault(){
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SERVER"])){
		$this->WLOG("KerbAuthInfos -> Try to find LDAP_SERVER");
		$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]=$this->BuildDefault_ldap_server();
		$this->WLOG("KerbAuthInfos -> {$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]}");
	}
	
	if(!isset($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"])){$GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]=0;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_PORT"])){$GLOBALS["KerbAuthInfos"]["LDAP_PORT"]=389;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"])){
		$SMB=$this->SAMBA_GetNetAdsInfos();
		$GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"]=$SMB["Bind Path"];
	}
	
	if(!isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_ADMIN"])){
		$array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/KerbAuthInfos")));
		$GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
		$GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
		$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];
	}

	
	
}	



private function LOCATE_NET_BIN_PATH(){
	$net=$this->internal_find_program("net");
	if(is_file($net)){return $net;}
	$net=$this->internal_find_program("net.samba3");
	if(is_file($net)){return $net;}
}

private function SAMBA_GetNetAdsInfos(){
	if(isset($GLOBALS["CACHE_NET"])){return $GLOBALS["CACHE_NET"];}
	
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
		if(count($array)>5){
			$GLOBALS["CACHE_NET"]=$array;
			return $array;
		}
	}

	$net=$this->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	$this->WLOG("$net ads info 2>&1 return ".count($array)." items");
	$GLOBALS["CACHE_NET"]=$array;
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	return $array;
}


private function internal_find_program($strProgram){
	if(isset($GLOBALS["internal_find_program"][$strProgram])){return $GLOBALS["internal_find_program"][$strProgram];}
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {
			$strProgrammpath = $strPath . "/" . $strProgram;
			if (is_executable($strProgrammpath)) {
				$GLOBALS["internal_find_program"][$strProgram]=$strProgrammpath;return $strProgrammpath;
				}
		}
	} else {
		$GLOBALS["internal_find_program"][$strProgram]=strpos($strProgram, '.exe');
		return $GLOBALS["internal_find_program"][$strProgram];
	}
}


private function BuildDefault_ldap_server(){
	if(isset($GLOBALS["KerbAuthInfos"]["ADNETIPADDR"])){return $GLOBALS["KerbAuthInfos"]["ADNETIPADDR"];}
	if(isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"])){return $GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
	$SMB=$this->SAMBA_GetNetAdsInfos();
	return $SMB["LDAP server"];
	$this->WLOG("[START]: BuildDefault_ldap_server did not find any LDAP server...");
}

private function WLOG($text=null){
	if(isset($GLOBALS["VERBOSE"])){echo "$text\n";}
	if(count($this->Errors)>500){$this->Errors=array();}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if (is_file("/var/log/squid/external-acl.log")) {
		$size=@filesize("/var/log/squid/external-acl.log");
		if($size>1000000){unlink("/var/log/squid/external-acl.log");
			
		}
	}
	$F = @fopen("/var/log/squid/external-acl.log", 'a');
	if(!isset($GLOBALS["PID"])){ if(function_exists("getmypid")){ $GLOBALS["PID"]=getmypid(); }else{$GLOBALS["PID"]=0;}}
	$FinalText="$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called";
	$this->Errors[]=$FinalText;
	@fwrite($F, $FinalText."\n");
	@fclose($F);
}

public function ADLdap_getgroups($username){
	$RETURN=array();

	
	
	$this->ADLdap_init();
	$options=$this->ADLdap_options();
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		return array();
	}
	
	try {
		$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
		catch (adLDAPException $e) {
			$this->WLOG($e);
			return array();
	}
	
	if(preg_match("#^(.+?)\/(.+)#", $username,$re)){
		$workgroup=trim(strtolower($re[1]));
		$username=$re[2];
		if(count($this->SquidAddkerAlernates)>0){
			$result=$this->_ADLdap_getgroups($username,$workgroup);
			if(count($result)>0){
				while (list ($num, $group) = each ($result) ){
					$RETURN[$group]=true;
				}
				return $RETURN;
			}
		}
		
	}
	
	$result=$this->_ADLdap_getgroups($username);
	
	if($GLOBALS["VERBOSE"]){echo @implode("\n", $GLOBALS["CLASS_ACTV"]);$GLOBALS["CLASS_ACTV"]=array();}
	
	if($GLOBALS["VERBOSE"]){echo "* * * * _ADLdap_getgroups($username) = ".count($result)." items * * * *\n";}
	
	
	if(count($result)>0){
		while (list ($num, $group) = each ($result) ){$RETURN[$group]=true;}
		return $RETURN;
	}
	
	if(count($RETURN)==0){
		if(count($this->SquidAddkerAlernates)>0){
			reset($this->SquidAddkerAlernates);
			while (list ($workgroup, $group) = each ($this->SquidAddkerAlernates) ){
				$result=$this->_ADLdap_getgroups($username,$workgroup);
				if($GLOBALS["VERBOSE"]){echo "* * * * * $username,$workgroup * * * *\n";}
				if($GLOBALS["VERBOSE"]){echo "* * * * * ". count($result)." items * * * *\n";}
				if($GLOBALS["VERBOSE"]){echo @implode("\n", $GLOBALS["CLASS_ACTV"]);$GLOBALS["CLASS_ACTV"]=array();}
				if(count($result)>0){
					while (list ($num, $group) = each ($result) ){
						$RETURN[$group]=true;
					}
				return $RETURN;}
			}
		}
	}
	
	
	return $RETURN;
}

private function _ADLdap_getgroups($username,$workgroup=null){
	if($workgroup==null){
		$options=$this->ADLdap_options();
		
	}else{
		$options=$this->ADLdap_options_workgroup($workgroup);
	}
	
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		return array();
	}
	
	try {
		$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
		catch (adLDAPException $e) {
			$this->WLOG($e);
			return array();
		}
		return $adldap->user()->groups($username);
	
}


public function ADLdap_userinfos($username){
	$RETURN=array();


	$this->ADLdap_init();
	$options=$this->ADLdap_options();
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		return array();
	}

	try {
		$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
		catch (adLDAPException $e) {
			$this->WLOG($e);
			return array();
		}
		$result=$adldap->user()->info($username);
		return $result;
}

private function ADLdap_init(){
	
	if($GLOBALS["VERBOSE"]){
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
	}
	
	$this->SquidAddkerAlernates=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidAddkerAlernates"));
	if(class_exists("adLDAP")){return;}
	$lib=dirname(__FILE__)."/externals/adLDAP/adLDAP.php";
	if(!is_file($lib)){return array();}
	if($GLOBALS["VERBOSE"]){echo "Include $lib\n";}
	include($lib);
}

private function ADLdap_options(){
	
	$this->BuildDefault();
	$array=$GLOBALS["KerbAuthInfos"];
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	$recursive=false;
	if($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]==1){
		if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: RECURSIVE_GROUPS = TRUE\n";}
		$recursive=true;
	}
	
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: SERVER = {$array["LDAP_SERVER"]}\n";}
	
	
	$options=array(
			'base_dn'=>$GLOBALS["SUFFIX"],
			'ad_username'=>$array["WINDOWS_SERVER_ADMIN"],
			'ad_password'=>$array["WINDOWS_SERVER_PASS"],
			'recursive_groups'=>$recursive,
			'domain_controllers'=>array($array["LDAP_SERVER"]),
			'account_suffix'=>"@{$array["WINDOWS_DNS_SUFFIX"]}");
	
	return $options;
	
}

private function ADLdap_options_workgroup($workgroup){
	$recursive=false;
	if($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]==1){
		if($GLOBALS["VERBOSE"]){echo "$workgroup: RECURSIVE_GROUPS = TRUE\n";}
		$recursive=true;
	}
	
	$array=$GLOBALS["KerbAuthInfos"];
	$Main=$this->SquidAddkerAlernates[$workgroup];
	
	if($Main["LDAP_DN"]==null){$Main["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
	if($Main["LDAP_PASSWORD"]==null){$Main["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];}
	
	if(strpos($Main["LDAP_DN"], "@")>0){
		$xplode=explode("@",$Main["LDAP_DN"]);
		$Main["LDAP_DN"]=$xplode[0];
		$array["WINDOWS_DNS_SUFFIX"]=$xplode[1];
	}
	
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options_workgroup:: SERVER = {$Main["LDAP_SERVER"]}\n";}
	$options=array(
			'base_dn'=>$Main["LDAP_SUFFIX"],
			'ad_username'=>$Main["LDAP_DN"],
			'ad_password'=>$Main["LDAP_PASSWORD"],
			'recursive_groups'=>$recursive,
			'domain_controllers'=>array($Main["LDAP_SERVER"]),
			'account_suffix'=>"@{$array["WINDOWS_DNS_SUFFIX"]}");
	
	return $options;
	
}

private function ObjectProperty($dn){
	
	$this->ConnectToLDAP();
	if(!isset($GLOBALS["QUERYTIME"])){$GLOBALS["QUERYTIME"]=time();}
	
	if(!$GLOBALS["BIND"]){
		$this->WLOG("[QUERY]: Error: BIND is broken -> reconnect");
		$this->ConnectToLDAP();
		if(!$GLOBALS["BIND"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
	}
	
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect twice");
		$this->ConnectToLDAP();
	}
	
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
		return array();
	}
	
	
	$GLOBALS["QUERIES_NUMBER"]++;
	$link_identifier=$GLOBALS["CONNECTION"];
	$base_dn=$GLOBALS["SUFFIX"];
	$attributes=array();
	$attrsonly=null;
	$sizelimit=null;
	$timelimit= $GLOBALS["LDAP_TIME_LIMIT"];
	
	
	
	
	$res=@ldap_read($link_identifier,$dn,"(objectClass=*)",array(),null,null,20);
	if(!$res){
		$this->WLOG("[QUERY]: Error: LDAP search number " . ldap_errno($link_identifier) . "\nAction:LDAP search\ndn:$dn\n");
		ldap_err2str(ldap_errno($link_identifier));
		return array();
	}

	$hash=@ldap_get_entries($link_identifier,$res);
	$Config=$hash[0];
	for($i=0;$i<$Config["objectclass"]["count"];$i++){
		$types[$Config["objectclass"][$i]]=true;
			
	}
	$ARRAY["SOURCE"]=$Config;
	$ARRAY["description"]=null;
	$ARRAY["mail"]=null;

	$cn=utf8_decode($Config["cn"][0]);
	$ARRAY["cn"]=$cn;


	if(isset($Config["description"])){$ARRAY["description"]=utf8_decode($Config["description"][0]);}
	if(isset($types["group"])){
		$ARRAY["TYPE"]="group";
		$ARRAY["MEMBERS"]=$Config["member"]["count"];
		$ARRAY["name"]=$Config["name"][0];
		return $ARRAY;
	}
	if(isset($types["foreignSecurityPrincipal"])){
		$ARRAY["TYPE"]="group";
		return null;
	}
	if(isset($types["user"])){
		$ARRAY["TYPE"]="user";
	}


	$ARRAY["name"]=$Config["name"][0];
	$ARRAY["uid"]=$Config["samaccountname"][0];
	$ARRAY["displayname"]=$Config["displayname"][0];
	if(isset($Config["userprincipalname"])){$ARRAY["userprincipalname"]=$Config["userprincipalname"][0];}
	if(isset($Config["mail"][0])){$ARRAY["mail"]=$Config["mail"][0];}
	return $ARRAY;
}


public function AdLDAP_MembersFromGroup($dn){
	$this->ADLdap_init();
	$options=$this->ADLdap_options();
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		return array();
	}
	
	try {
		$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
		catch (adLDAPException $e) {
			$this->WLOG($e);
			return array();
	}
	
	$props=$this->ObjectProperty($dn);
	$name=$props["name"];
	
	if($GLOBALS["VERBOSE"]){echo "$dn -> $name\n";}
	
	$array=$adldap->group()->members($name);
	if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup $dn -> ".count($array)."\n";}
	return $array;
	}	
	
	

	
	
	
private function ConnectToLDAP(){
	$this->BuildDefault();
	$array=$GLOBALS["KerbAuthInfos"];
	if(!is_array($array)){
		$this->WLOG("KerbAuthInfos not an array");
		return false;
	}	
	
	if(!isset($array["LDAP_SERVER"])){WLOG("LDAP_SERVER not set");return;}
	if(!isset($array["LDAP_SUFFIX"])){WLOG("LDAP_SUFFIX not set");return;}
	
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	$GLOBALS["CONNECTION"]=@ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
	//WLOG("[LDAP]: Connecting to LDAP server `{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}`");
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[LDAP]: Fatal: ldap_connect({$array["LDAP_SERVER"]},{$array["LDAP_PORT"]} )");
		@ldap_close();
		return false;
	}	
	
	//WLOG("[LDAP]: Connecting to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span> with suffix:&laquo;{$GLOBALS["SUFFIX"]}&raquo;");
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);	
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	
	if(preg_match("#^(.+?)\/(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	if(preg_match("#^(.+?)\\\\(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	
	$GLOBALS["BIND"]=@ldap_bind($GLOBALS["CONNECTION"], "{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}", $array["WINDOWS_SERVER_PASS"]);
	if(!$GLOBALS["BIND"]){
		
		if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."$extended_error";
		}
		
		switch (ldap_errno($GLOBALS["CONNECTION"])) {
			case 0x31:
				$error=$error . "Bad username or password. Please try again.";
				break;
			case 0x32:
				$error=$error . "Insufficient access rights.";
				break;
			case 81:
				$error=$error . "Unable to connect to the LDAP server
				{$array["LDAP_SERVER"]}please,verify if ldap daemon is running or the ldap server address";
				break;
			case -1:
					
				break;
			default:
				$error=$error . "Could not bind to the LDAP server." ."". @ldap_err2str($GLOBALS["CONNECTION"]);
		}
		$this->WLOG("[LDAP]:".__LINE__." Connecting to LDAP server {$array["LDAP_SERVER"]} failed $error");
		return false;
	}
	//WLOG("[LDAP]: Binding to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span>.");
	return true;
}


}