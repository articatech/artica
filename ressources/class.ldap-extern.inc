<?php


class ldap_extern{
	private $ldap_server;
	private $ldap_port;
	private $userdn;
	private $ldap_password;
	private $ldap_suffix;
	private $ldap_filter_users;
	private $ldap_filter_group;
	private $ldap_user_attribute;
	private $ldap_group_attribute;
	private $ldap_filter_search_group;
	public $ldap_filter_group_attribute;
	private $ldap_connection;
	public $ldap_error;
	private $ldapbind;
	public	$ok=false;
	function ldap_extern(){
		$this->LoadSettings();
		
	}
	
	
	
	function LoadSettings(){
		$EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidExternalAuth")));
		$this->ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
		$this->ldap_port=intval($EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]);
		$this->userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
		$this->ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
		$this->ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
		$this->ldap_filter_users=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
		$this->ldap_filter_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
		$this->ldap_user_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
		$this->ldap_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
		$this->ldap_filter_search_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
		$this->ldap_filter_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
		
	}
	
	private function connect(){
		$this->ok=false;
		if(!$this->socket_connect()){
			if($GLOBALS["VERBOSE"]){echo "socket_connect report failed<br>\n";}
			return false;}
		$this->ldap_error=null;
		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
		$this->ldapbind=@ldap_bind($this->ldap_connection, $this->userdn, $this->ldap_password);
		
		if($this->ldapbind){
			if($GLOBALS["VERBOSE"]){echo "ldap_bind success<br>\n";}
			$this->ok=true;
			return true;
		}
		
		$ldap_errno=ldap_errno($this->ldap_connection);
		$ldap_err2str=ldap_err2str($ldap_errno);
		if($GLOBALS["VERBOSE"]){echo "ldap_bind FAILED $ldap_err2str<br>\n";}
		@ldap_close($this->ldap_connection);
		$this->ldap_error="Error $ldap_errno $ldap_err2str";
		$this->ok=false;
		return false;
	}
	
	
	private function socket_connect(){
		$this->ldap_connection=@ldap_connect($this->ldap_server, $this->ldap_port ) ;
		if($this->ldap_connection){
			if($GLOBALS["VERBOSE"]){echo "socket_connect success<br>\n";}
			$this->ok=true;
			return true;
		}
		$ldap_errno=ldap_errno($this->ldap_connection);
		$ldap_err2str=ldap_err2str($ldap_errno);
		@ldap_close($this->ldap_connection);
		$this->ldap_error="$this->ldap_server:$this->ldap_port socket failed err:$ldap_errno $ldap_err2str";
		return false;
		
	}
	

	
	
	
	public function SearchGroups($pattern,$maxitems=0){
		if($maxitems==0){$maxitems=null;}
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "Connection failed<br>\n";}
			$this->ok=false;
			return array();
		}
		if($pattern==null){$pattern="*";}
		
		$searchFilter=$this->ldap_filter_search_group;
		$searchFilter=str_replace("%s", $pattern, $searchFilter);
		$filter=array($this->ldap_group_attribute);
		
		if($GLOBALS["VERBOSE"]){echo "$searchFilter/$this->ldap_suffix<br>\n";}
		
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			}
			$this->ok=false;
			@ldap_close($this->ldap_connection);
			return array();
		}
		
		$array_ldap=@ldap_get_entries($this->ldap_connection,$sr);
		$count=$array_ldap["count"];
		if($GLOBALS["VERBOSE"]){echo "$searchFilter -> $count<br>\n";}
		
		for($i=0;$i<$count;$i++){
			$attr=$array_ldap[$i][$this->ldap_group_attribute][0];
			$dn=$array_ldap[$i]["dn"];
			$MAIN[$dn]=$attr;
		}
		return $MAIN;
	}
	
	public function CountDeUsersByGroupDN($dn){
		return count($this->HashUsersFromGroupDN($dn));
		
	}
	
	
	public function DNInfos($dn){
		if(!$this->connect()){return false;}
		$sr =@ldap_read($this->ldap_connection,$dn,"objectClass=*",array(),null, null, 10);
		if(!$sr){
			@ldap_close($this->ldap_connection);
			return false;
		}
		
		
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		@ldap_close($this->ldap_connection);
		return $hash;
	}
	
	
	public function UserIdentity($uid){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$searchFilter=$this->ldap_filter_users;
		$searchFilter=str_replace("%s", $uid, $searchFilter);
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
		
			}
			$this->ok=false;
			@ldap_close($this->ldap_connection);
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if($hash["count"]==0){return array();}
		return $hash;
		
	}
	
	private function GETDN_FROM_UID($uid){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$hash=$this->UserIdentity($uid);
		return $hash[0]["dn"];
	}
	
	private function SearchUserByuid($uid){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$searchFilter=$this->ldap_filter_group;
		$searchFilter=str_replace("%u", "$uid", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
		$filter=array("dn");
		$this->debug("Search $searchFilter in $this->ldap_suffix",__LINE__);
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
		
			}
			$this->ok=false;
			@ldap_close($this->ldap_connection);
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		return $hash;
	}
	
	
	private function GetGroupsByMember($uid){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$searchFilter=$this->ldap_filter_group;
		$searchFilter=str_replace("%u", "$uid", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
		$filter=array("dn");
		$this->debug("Search $searchFilter in $this->ldap_suffix",__LINE__);
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
		
			}
			$this->ok=false;
			@ldap_close($this->ldap_connection);
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		return $hash;
		
	}
	
	
	public function GetGroupsFromuser($uid){
		if(isset($GLOBALS[__CLASS__.".".__FUNCTION__][$uid])){return $GLOBALS[__CLASS__.".".__FUNCTION__][$uid];}
		
		
		$this->debug("GetGroupsByMember($uid)={$hash["count"]}", __LINE__);
		if($hash["count"]==0){
			$hash=$this->UserIdentity($uid);
			$DN=$hash[0]["dn"];
			$hash=$this->GetGroupsByMember($DN);
			$this->debug("GetGroupsByMember($DN)={$hash["count"]}", __LINE__);
			
			
		}
		if($hash["count"]==0){
			$this->debug("Return 0 items", __LINE__);
			$GLOBALS[__CLASS__.".".__FUNCTION__][$uid]=array();
			return array();
		}
		
		for($i=0;$i<$hash["count"];$i++){
			$GroupDN=$hash[$i]["dn"];
			$ARRAY[$GroupDN]=true;
		}
		$GLOBALS[__CLASS__.".".__FUNCTION__][$uid]=$ARRAY;
		return $ARRAY;
		
	}
	
	private function debug($text,$line){
		if(!$GLOBALS["VERBOSE"]){return;}
		echo "<p style='color:yellow'>parse_template_ufdb: $text ( in line $line )</p>\n";
	}
	
	public function HashUsersFromGroupDN($dn){
		$ORGDN=$dn;
		$Tosend=array();
		if(isset($GLOBALS["HashUsersFromGroupDN($dn)"])){return $GLOBALS["HashUsersFromGroupDN($dn)"];}
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "Connection failed<br>\n";}
			$this->ok=false;
			return array();
		}
		$f=array();
		$searchFilter=$this->ldap_filter_group;
		$searchFilter=str_replace("%u", "*", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
	
		$filter=array($this->ldap_filter_group_attribute);
		$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				
			}
			$this->ok=false;
			@ldap_close($this->ldap_connection);
			return array();
		}
		
		if($GLOBALS["VERBOSE"]){echo "$searchFilter<br>\n";}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!isset($hash[0][$this->ldap_filter_group_attribute]["count"])){$hash[0][$this->ldap_filter_group_attribute]["count"]=0;}
		$MembersCount=$hash[0][$this->ldap_filter_group_attribute]["count"];
		
	
	
		if($GLOBALS["VERBOSE"]){echo "MembersCount:$MembersCount in `$dn` line ". __LINE__."\n";}
		for($i=0;$i<$MembersCount;$i++){
			$MemberName=$hash[0][$this->ldap_filter_group_attribute][$i];
			if(strpos($MemberName, ",")>0){
				$MemberName=$this->MemberInfoByDN($MemberName);
			}
			
			$MemberName=trim(strtolower($MemberName));
			if($MemberName==null){continue;}
			if($GLOBALS["DEBUG_GROUPS"] >0){ if(function_exists("WLOG")){WLOG("Found \"$MemberName\""); }}
			$f[$MemberName]=$MemberName;
		}
		$GLOBALS["HashUsersFromGroupDN($dn)"]=$f;
		return $f;
	
	}
	
	private function MemberInfoByDN($dn){
		if(!$this->connect()){return null;}
		$searchFilter="(objectClass=*)";
	
		$filter=array($this->ldap_user_attribute);
		$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if($GLOBALS["VERBOSE"]){echo "Bad search $dn / $searchFilter\n";}
			return null;
			
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $dn;}
		if(isset($hash[0][$this->ldap_user_attribute][0])){return $hash[0][$this->ldap_user_attribute][0];}
	}
	
	public function checkcredentials($username,$password){
		if(!$this->connect()){
			
			if($GLOBALS["VERBOSE"]){echo "Connect failed\n";}
			return false;}
		$ldap_filter_users=$this->ldap_filter_users;
		$ldap_filter_users=str_replace("%s", $username, $ldap_filter_users);
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,$ldap_filter_users,array());
		
		if(!$sr){
			if($GLOBALS["VERBOSE"]){echo "$ldap_filter_users Search failed in $this->suffix\n";}
			@ldap_close($this->ldap_connection);
			return false;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if($hash["count"]==0){
			@ldap_close($this->ldap_connection);
			return false;
			
			
		}
		@ldap_close($this->ldap_connection);
		$userpassword=$hash[0]["userpassword"][0];
		return $this->check_password($password,$userpassword);
		
		
		
	}
	
	private function check_password($password, $hash){
		if($GLOBALS["VERBOSE"]){echo "Check '$hash'\n";}
		$encrypted_password=null;
 		if ($hash == '') {
 			return FALSE;
 		}
 
 		if ($hash{0} != '{'){
 			if ($password == $hash){return TRUE;}
 			return FALSE;
		}
 
 		if (substr($hash,0,7) == '{crypt}'){
 			if (crypt($password, substr($hash,7)) == substr($hash,7)){return TRUE;}
			return FALSE;
 		}
 		
 		
 		
 		if(substr($hash,0,5) == '{MD5}'){
 			if($GLOBALS["VERBOSE"]){echo "Check MD5\n";}
 			$encrypted_password = '{MD5}' . base64_encode(md5( $password,TRUE));
 		}
 		
 		
 	
 		
 		if($encrypted_password==null){
 			if(substr($hash,0,6) == '{SHA1}'){
 				if($GLOBALS["VERBOSE"]){echo "Check SHA1\n";}
 				$encrypted_password = '{SHA1}' . base64_encode(sha1( $password, TRUE ));
 			}
 		}
 		
 		if($encrypted_password==null){
	 		if(substr($hash,0,6) == '{SSHA}'){
	 			$salt = substr(base64_decode(substr($hash,6)),20);
	 			$encrypted_password = '{SSHA}' . base64_encode(sha1( $password.$salt, TRUE ). $salt);
			}
 		}
 		
 		
 		if($encrypted_password==null){
 			if($GLOBALS["VERBOSE"]){echo "Unsupported password hash format";}
 			return FALSE;
 		}
 		
 		if($GLOBALS["VERBOSE"]){echo "Checks against $encrypted_password for \"$hash\"\n";}
 		if ($hash == $encrypted_password){return TRUE;}
 		
 		return FALSE;
 
	}
	
	
	
		
			
	
}