<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/externals/adLDAP/adLDAP.php");
class ActiveDirectory{
	
	var $ldapbind;
	var $ldap_connection;
	var $ldap_admin="";
	var $ldap_dn_user="";
	var $ldap_password="";
	var $suffix="";
	var $ldap_host="";
	var $ldap_port=389;
	var $ldap_last_error="";
	var $ldapFailed=false;
	var $members=array();
	var $LDAP_RECURSIVE=0;
	var $ARRAY_MEMBERS=array();
	var $UseDynamicGroupsAcls=1;

	private $WINDOWS_SERVER_ADMIN=null;
	private $WINDOWS_DNS_SUFFIX=null;
	
	function ActiveDirectory($AdSourceID=0){
			if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
			if(!is_numeric($AdSourceID)){$AdSourceID=0;}
			if($AdSourceID==0){
				$this->LoadAdSettingsFromSquid();
			}
			
			
			
			$sock=new sockets();
			if(!is_numeric($this->LDAP_RECURSIVE)){$this->LDAP_RECURSIVE=0;}
			if($this->suffix==null){return;}

			
			$fp=@fsockopen($this->ldap_host, $this->ldap_port, $errno, $errstr, 2);
			if(!$fp){
				writelogs("[$AdSourceID]:ERROR unable to connect to ldap server ($this->suffix) in line ". __LINE__." $this->ldap_host, $this->ldap_port \"$errstr\"",
				__CLASS__."/" .__FUNCTION__,__FILE__);
				$this->ldap_last_error=$errstr;$this->ldapFailed=true;
				@fclose($fp);
				$GLOBALS["LDAP_AD_FAILED"]=true;
				return false;
			}		
			
			
			$this->ldap_connection=ldap_connect($this->ldap_host, $this->ldap_port );
			ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
			ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0); 		 
			$this->ldapbind=@ldap_bind($this->ldap_connection, $this->ldap_dn_user,$this->ldap_password);
	
				if(!$this->ldapbind){
						$called=null;
						if(function_exists("debug_backtrace")){$trace=debug_backtrace();$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
						$errornumber= ldap_errno($this->ldap_connection);
						$error= "ERROR number : $errornumber unable to connect with following\n &laquo;<strong>$this->ldap_dn_user</strong>&raquo;\n";
						writelogs("ERROR number : $errornumber unable to connect to ldap server ldap://{$this->ldap_host}:{$this->ldap_port} right username $this->ldap_dn_user $called"
						,__CLASS__."/" .__FUNCTION__,__FILE__);	
					switch (ldap_errno($this->ldap_connection)) {
						case 0x31:
							$error=$error . "\nBad username or password. Please try again.";
							break;
						case 0x32:
							$error=$error . "\nInsufficient access rights.";
							break;
						case 81:
							$error=$error . "\nUnable to connect to the LDAP server\n
							$this->ldap_host:$this->ldap_port\nplease,\nverify if ldap daemon is running\n or the ldap server address";
							break;						
						case -1:
							
							break;
						default:
							$error=$error . "\nCould not bind to the LDAP server." ."\n". ldap_err2str($this->ldap_connection);
	 				}					
						
						
					$this->ldap_last_error=$error;
					$this->ldapFailed=true;
					return false;
				}else{
	
					//writelogs("Connection success on ldap://{$this->ldap_host}:{$this->ldap_port} $called", __CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
				}
				$GLOBALS["LDAP_AD_FAILED"]=false;
				return true;
		}
		
	
	
	private function LoadAdSettingsFromSquid(){
		$sock=new sockets();
		$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
		if($EnableKerbAuth==0){
			if($GLOBALS["VERBOSE"]){writelogs("EnableKerbAuth is disabled", __CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);}
			return;
		}

		
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		if(  (!isset($array["LDAP_SUFFIX"]) ) OR (strlen(trim($array["LDAP_SUFFIX"]))<3) OR (strlen(trim($array["WINDOWS_DNS_SUFFIX"]))<3)  ){
			
			$INFOS=$this->NetAdsInfo();
			if($GLOBALS["VERBOSE"]){writelogs("Found LDAP_SUFFIX={$INFOS["SUFFIX"]}, server={$INFOS["SERVER_IP"]}", __CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);}
			$array["LDAP_SUFFIX"]=$INFOS["SUFFIX"];
			$array["LDAP_PORT"]=$INFOS["LDAP_PORT"];
			$array["LDAP_SERVER"]=$INFOS["SERVER_IP"];
			$array["FQDN"]=$INFOS["FQDN"];
			
			$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
		}
		
		
		//cn=$this->ldap_admin,cn=Users,
		
		if(!isset($array["RECURSIVE_GROUPS"])){$array["RECURSIVE_GROUPS"]=0;}
		if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
		if(!isset($array["LDAP_RECURSIVE"])){$array["LDAP_RECURSIVE"]=0;}
		if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
		
		if($array["LDAP_PASSWORD"]==null){
			$this->ldap_password=$array["WINDOWS_SERVER_PASS"];
		}else{
			$this->ldap_password=$array["LDAP_PASSWORD"];
		}
		$this->suffix=$array["LDAP_SUFFIX"];
		$this->ldap_host=$array["LDAP_SERVER"];
		$this->ldap_port=$array["LDAP_PORT"];
		$this->LDAP_RECURSIVE=$array["LDAP_RECURSIVE"];
		if($array["RECURSIVE_GROUPS"]==1){$this->LDAP_RECURSIVE=1;}
		
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $array["WINDOWS_DNS_SUFFIX"])){
			if($array["FQDN"]<>null){$FGH=explode(".",$array["FQDN"]);unset($FGH[0]);$array["WINDOWS_DNS_SUFFIX"]=@implode(".", $FGH);}
		}
		

		$this->WINDOWS_SERVER_ADMIN=$array["WINDOWS_SERVER_ADMIN"];
		$this->WINDOWS_DNS_SUFFIX=$array["WINDOWS_DNS_SUFFIX"];
		
		if($array["LDAP_DN"]==null){
			$this->ldap_dn_user="{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}";
		}else{
			
			if(!preg_match("#(.+?)@(.+)#", $array["LDAP_DN"])){
					if(preg_match("#^CN=(.+?),#i", $array["LDAP_DN"],$re)){
						$array["LDAP_DN"]="{$re[1]}@{$array["WINDOWS_DNS_SUFFIX"]}";
					}
				}
			$this->ldap_dn_user=$array["LDAP_DN"];
		
		}
				
			
			
	}
	
	
	
	
	public function DumpDN($dn){
		$res=@ldap_read($this->ldap_connection,$dn,"(objectClass=*)",array(),null,null,20);
		if(!$res){
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" . 
			ldap_err2str(ldap_errno($this->ldap_connection));
			return array();
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$res);
		$Config=$hash[0];		
		return $Config;
	}
	
	private function NetAdsInfo(){
		$sock=new sockets();
		$v=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
		while (list ($num, $ligne) = each ($v) ){
			if(preg_match("#LDAP server:\s+(.+)#i", $ligne,$re)){$array["SERVER_IP"]=trim($re[1]);continue;}
			if(preg_match("#Bind Path:\s+(.+)#i", $ligne,$re)){$array["SUFFIX"]=trim($re[1]);continue;}	
			if(preg_match("#LDAP port:\s+(.+)#i", $ligne,$re)){$array["LDAP_PORT"]=trim($re[1]);continue;}			
			if(preg_match("#Realm:\s+(.+)#i", $ligne,$re)){$array["WINDOWS_DNS_SUFFIX"]=trim(strtolower($re[1]));continue;}
			if(preg_match("#LDAP server name:\s+(.+)#i", $ligne,$re)){$array["FQDN"]=$re[1];}
			
			
		}
	
		return $array;
		
		
	}
	
	
	
	
	function Ldap_search($dn,$filter,$attrs,$maxRow=0){
		if(!$this->ldap_connection){
			writelogs("Could not search without proper ldap connection", __CLASS__ . "/" . __FUNCTION__,__FILE__);
			return ;
			}
		if(!is_array($attrs)){
			$sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
			}else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
		if ($sr) {
			return ldap_get_entries($this->ldap_connection,$sr);
		}else{
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
   			writelogs("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);return false;}
		return array();}

	function Ldap_list($dn,$filter,$attrs,$maxRow=0){
		if(!$this->ldap_connection){
			writelogs("Could not search without proper ldap connection", __CLASS__ . "/" . __FUNCTION__,__FILE__);
			return ;
			}
		if(!is_array($attrs)){
			$sr =@ldap_list($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
			}else{$sr =@ldap_list($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
		if ($sr) {
			return ldap_get_entries($this->ldap_connection,$sr);
		}else{
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
   			writelogs("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);return false;}
		return array();}

		
		function UserSearch_formated($ou=null,$tofind,$maxRow=null){	
			$uid=$_SESSION["uid"];
			if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
			$filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind)))";
			$attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
			if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,$this->suffix";}
			//writelogs("Ldap_search : $filter in $dn", __CLASS__ . "/" . __FUNCTION__,__FILE__);
			$hash=$this->find_users($ou,$tofind,$maxRow);
			
			for($i=0;$i<$hash["count"];$i++){
				$GroupName=utf8_decode($hash[$i]["samaccountname"][0]);
				writelogs("{$hash[$i]["samaccountname"][0]} = `$GroupName`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$dn=$hash[$i]["dn"];
				$description=utf8_decode($hash[$i]["displayname"][0]);
				$FINAL[$dn]=array($GroupName,$description,0);
			
			
			}			
			return $FINAL;
			
		}

		
	
	function UserSearch($ou=null,$tofind,$maxRow=null){
			$uid=$_SESSION["uid"];
			if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
			$filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind)))";
			$attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
			if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,$this->suffix";}
			//writelogs("Ldap_search : $filter in $dn", __CLASS__ . "/" . __FUNCTION__,__FILE__);
			$hash=$this->find_users($ou,$tofind,$maxRow);
			
			$hash2=array();	
			return array($hash,$hash2);

		}

		function _Get_dn_userid($userid){
			if($userid==null){return null;}
			$sr =@ldap_search($this->ldap_connection,$this->suffix,"(sAMAccountName=$userid)");
			if($sr){$result = ldap_get_entries($this->ldap_connection, $sr);return trim($result[0]["dn"]);}
			else{
				writelogs("Unable to find dn (sAMAccountName=$userid)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		function hash_groups($ou,$fieldlist=0){
		
		if($ou==null){
			writelogs("OU NULL !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$pos=strpos($ou,',');
		if($pos>0){$ou=substr($ou,0,$pos);}
		$ld =$this->ldap_connection;
		$bind =$this->ldapbind;
		$cols=array("cn","description",'sAMAccountName',"dn");
		$dn="ou=$ou,$this->suffix";
		if($ou=="Domain Controllers"){$dn="CN=Users,$this->suffix";}
		
		
		$sr = @ldap_search($ld,"$dn",'objectclass=group',$cols);
		if ($sr) {
			$hash=ldap_get_entries($ld,$sr);
			if(!is_array($hash)){return array();}
			while (list ($num, $ligne) = each ($hash) ){
				if($ligne["samaccountname"][0]<>null){
					switch ($fieldlist) {
						case 1:$res[$ligne["dn"]]=$ligne["samaccountname"][0];break;
						case 2;$res=$res . "{$ligne["dn"]};{$ligne["samaccountname"][0]}\n";break;
						default:$res[$ligne["cn"][0]]=array("gid"=>$ligne["dn"],"description"=>$ligne["description"][0]);break;
					}
				}
				
			}
		}else{
			writelogs("Unable to find groups in $dn",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		writelogs(count($res). " groups found for $ou ($dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $res;	
		}

		
	public function Hash_GetALLUsers(){
		$filter="(objectClass=user)";
		$attrs=array("samaccountname","mail","userprincipalname");
		$dn="$this->suffix";
		
	$sr =@ldap_search($this->ldap_connection,"$this->suffix",$filter,$attrs);
			if ($sr) {
				$hash=ldap_get_entries($this->ldap_connection,$sr);			
				for($i=0;$i<$hash["count"];$i++){
					$userid=$hash[$i]["samaccountname"][0];
					if(substr($userid,strlen($userid)-1,1)=='$'){continue;}
					$mail=$hash[$i]["mail"][0];
					$userprincipalname=$hash[$i]["userprincipalname"][0];
					if($mail==null){
						if(preg_match("#.+?@.+#",$userprincipalname)){$mail=$userprincipalname;}
					}
					$res[$userid]=$mail;
					}
				}else{
					return array();
				}
	
		return $res;	
		
	}	

	function CountDeDomainsOU($ou){
		$ldap=new clladp();
		return count($ldap->Hash_domains_table($ou));
	}	
	
	function CountDeGroups($ou){
		return count($this->hash_get_groups_from_ou_mysql($ou));
		
	}
	
	
	function search_users($pattern,$entriesNumber=50){
		
		
		
	}
	
	
	function search_groups($pattern,$entriesNumber=50){
		$this->ldap_last_error=null;
		if($pattern==null){$pattern="*";}
		if(strpos("  $pattern", '*')==0){$pattern=$pattern.'*';}
		if(!is_numeric($entriesNumber)){$entriesNumber=50;}
		$cols=array("cn","description",'sAMAccountName',"dn","member");
		$filter="(&(objectclass=group)(cn=$pattern))";
		//$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
		$sr = @ldap_search($this->ldap_connection,$this->suffix,$filter,$cols,null,$entriesNumber,15);
		if (!$sr) {
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" . 
			ldap_err2str(ldap_errno($this->ldap_connection));
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);		
		if(!is_array($hash)){return array();}
		
		for($i=0;$i<$hash["count"];$i++){
			$GroupName=utf8_decode($hash[$i]["cn"][0]);
			writelogs("{$hash[$i]["cn"][0]} = `$GroupName` Maxentries:$entriesNumber",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$dn=$hash[$i]["dn"];
			$description=utf8_decode($hash[$i]["description"][0]);
			$FINAL[$dn]=array($GroupName,$description,$hash[$i]["member"]["count"]);
			
			
		}
		
		
		return $FINAL;
	}
	
	
	function dump_users_from_group($dn){
		$this->ldap_last_error=null;
		$recursive=false;
		if($this->LDAP_RECURSIVE==1){$recursive=true;}
		
		if($GLOBALS["VERBOSE"]){echo "Dn = $dn<br>\n";}
		
		$props=$this->ObjectProperty($dn);
		$name=$props["name"];
		
		$options=array(
				'base_dn'=>$this->suffix,
				'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
				'ad_password'=>$this->ldap_password,
				'recursive_groups'=>$recursive,
				'domain_controllers'=>array($this->ldap_host),
				'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");
		
		
		try {
			$adldap = new adLDAP($options);
		}
		catch (adLDAPException $e) {
			if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
			return array();
		}
		
		try {
			$adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
			catch (adLDAPException $e) {
				if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
				return array();
			}
			$MEMBERS=$adldap->group()->members($name);
		
				
			if(count($MEMBERS)==0){return array();}
		
			while (list ($num, $uid) = each ($MEMBERS) ){
				$uid=trim(strtolower($uid));
				$array[$uid]=true;
			}
			
			return $array;
			
	}
	
	function search_users_from_groupName($name,$entriesNumber=5000){
		$this->ldap_last_error=null;
		$recursive=false;
		if($this->LDAP_RECURSIVE==1){$recursive=true;}
	
	
		$options=array(
				'base_dn'=>$this->suffix,
				'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
				'ad_password'=>$this->ldap_password,
				'recursive_groups'=>$recursive,
				'domain_controllers'=>array($this->ldap_host),
				'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");
	
	
		try {
			$adldap = new adLDAP($options);
		}
		catch (adLDAPException $e) {
			if($GLOBALS["VERBOSE"]){echo $e." $name:<br>\n";}
			return array();
		}
	
		try {
			$adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
			catch (adLDAPException $e) {
				if($GLOBALS["VERBOSE"]){echo $e." $name: <br>\n";}
				return array();
			}
			if($GLOBALS["VERBOSE"]){echo "$name: authenticate $this->ldap_host/$this->WINDOWS_SERVER_ADMIN<br>\n";}
			return $adldap->group()->members($name);
	
				
			
	
	}	
	
	function search_users_from_group($dn,$entriesNumber=1000){
		$this->ldap_last_error=null;
		$recursive=false;
		if($this->LDAP_RECURSIVE==1){$recursive=true;}
		
		if($GLOBALS["VERBOSE"]){echo "Dn = $dn<br>\n";}
		
		$props=$this->ObjectProperty($dn);
		$name=$props["name"];
		
		$options=array(
				'base_dn'=>$this->suffix,
				'ad_username'=>$this->WINDOWS_SERVER_ADMIN,
				'ad_password'=>$this->ldap_password,
				'recursive_groups'=>$recursive,
				'domain_controllers'=>array($this->ldap_host),
				'account_suffix'=>"@{$this->WINDOWS_DNS_SUFFIX}");
		
		
		try {
			$adldap = new adLDAP($options);
		}
		catch (adLDAPException $e) {
			if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
			return array();
		}
		
		try {
			$adldap->authenticate($this->WINDOWS_SERVER_ADMIN,$this->ldap_password);}
			catch (adLDAPException $e) {
				if($GLOBALS["VERBOSE"]){echo $e."<br>\n";}
				return array();
			}
			$MEMBERS=$adldap->group()->members($name);
		if($GLOBALS["VERBOSE"]){echo "$name: FOUND ".count($MEMBERS)."\n";}
			
		if(count($MEMBERS)==0){return array();}
		
		while (list ($num, $uid) = each ($MEMBERS) ){
			$info=$adldap->user()->info($uid);
			$dn=$info[0]["dn"];
			if($GLOBALS["VERBOSE"]){echo $uid."->$dn\n";}
			$props=$this->ObjectProperty($dn);
			if(!is_array($props)){continue;}
			$array[$dn]=$props;
		}
			
			
		
		return $array;

		
		
	}
	

	
	public function ObjectProperty($dn){
		$res=@ldap_read($this->ldap_connection,$dn,"(objectClass=*)",array(),null,null,20);
		if(!$res){
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" . 
			ldap_err2str(ldap_errno($this->ldap_connection));
			return array();
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$res);
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
		
		

function hash_get_ou($has_key=false){
		$res=$this->hash_get_ou_mysql($has_key);
		if(count($res)>0){return $res;}
		$ld =$this->ldap_connection;
		$bind =$this->ldapbind;
		$suffix="$this->suffix";
		$res=array();
		$arr=array("ou");
		$sr = @ldap_list($ld,$suffix,'(&(objectclass=organizationalUnit)(ou=*))',$arr);
		if ($sr) {
			$hash=ldap_get_entries($ld,$sr);
			if(!is_array($hash)){return array();}
			
			while (list ($num, $ligne) = each ($hash) ){
				$ou=trim($ligne["ou"][0]);
				if($ou==null){continue;}
				if($has_key){$res[$ou]=$ou;}else{$res[]=$ou;}
			}
		}
			
			
		
		return $res;
		
		}
		
	public function find_members($tofind){
		$hash=$this->find_users(null,$tofind);
		for($i=0;$i<$hash["count"];$i++){
			$this->members[]=$hash[$i]["samaccountname"][0];
			$this->ARRAY_MEMBERS[$hash[$i]["samaccountname"][0]]=$hash[$i]["samaccountname"][0];
		}
		
	}		
		
	public function find_users($ou,$tofind,$maxRow=null){
			$hash=$this->find_users_mysql($ou,$tofind);
			writelogs("search $tofind in ou:$ou Mysql return {$hash["count"]} elements ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if($hash["count"]>0){return $hash;}
			$tofind=str_replace("***", "*", $tofind);
			$tofind=str_replace("**", "*", $tofind);
			$cc=new external_ad_search();
			$dn="$cc->suffix";
			if($maxRow==null){$maxRow=150;}
			$filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
			$attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
			if(trim($ou)=="Domain Controllers"){$dn="CN=Users,$cc->suffix";}
			
			$hash=$cc->Ldap_search($dn,$filter,$attrs,$maxRow);	
			writelogs("search $tofind in `$dn` {$hash["count"]} element(s)...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(!is_array($hash)){return array();}
			return $hash;
			
		}
		
	private function find_users_mysql($ou,$tofind){
		if($tofind==null){$tofind="*";}
		$tofind=str_replace("*", "%", $tofind);
		if($ou<>null){$sql_ou=" AND ou='$ou'";}
		
		
		$sql="SELECT * FROM activedirectory_users WHERE ((samaccountname LIKE '$tofind' $sql_ou) 
		OR (displayName LIKE '$tofind' $sql_ou)
		OR (userprincipalname LIKE '$tofind' $sql_ou)
		OR (displayname LIKE '$tofind' $sql_ou))
		ORDER BY samaccountname
		LIMIT 0,150";
		
		if($tofind==null){
			if($ou<>null){
				$sql="SELECT * FROM activedirectory_users WHERE ou='$ou' LIMIT 0,150";
			}
		}
		
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");	
		if(!$q->ok){	writelogs("\n\n$sql\n\n$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$hash["count"]=mysql_num_rows($results);
		if(mysql_num_rows($results)==0){
			writelogs("0 elements for \n\n$sql\n\n",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		$c=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$hash[$c]["samaccountname"][0]=$ligne["samaccountname"];
			$hash[$c]["displayname"][0]=$ligne["displayname"];	
			$hash[$c]["telephonenumber"][0]=$ligne["telephonenumber"];
			$hash[$c]["mail"][0]=$ligne["mail"];
			$hash[$c]["givenname"][0]=$ligne["givenname"];	
			$hash[$c]["title"][0]=$ligne["title"];	
			$hash[$c]["sn"][0]=$ligne["sn"];			
			$hash[$c]["dn"]=$ligne["dn"];	
		
			
			$c++;
			
		}
		
		return $hash;
	}
		
	
	
	
public function hash_get_groups_from_ou_mysql($ou,$pattern,$return_array=false){
	$oupattern=str_replace("**", "*", $pattern);
	$oupattern=str_replace("*", "%", $oupattern);
	
	
	if($oupattern<>null){$sql_ou="AND activedirectory_groupsNames.groupname LIKE '$oupattern'";}
		$sql="SELECT activedirectory_groups.groupdn,
		activedirectory_groupsNames.groupname,
		activedirectory_groupsNames.description,
		activedirectory_groupsNames.UsersCount,
		activedirectory_users.ou
		FROM activedirectory_groups,activedirectory_users,activedirectory_groupsNames
		WHERE
		activedirectory_users.dn=activedirectory_groups.userdn AND activedirectory_groups.groupdn=activedirectory_groupsNames.dn
		AND activedirectory_users.ou='$ou'
		$sql_ou
		GROUP BY activedirectory_groups.groupdn,
		activedirectory_groupsNames.groupname,
		activedirectory_users.ou
		ORDER BY activedirectory_groupsNames.groupname LIMIT 0,50";
		
		
		
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("\n\n$sql\n\n$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if(mysql_num_rows($results)==0){writelogs("\n\n$sql\n\n",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}else{
			writelogs(mysql_num_rows($results)." rows",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$res[$ligne["groupdn"]]=$ligne["groupname"];
			if($return_array){
				$res[$ligne["groupdn"]]=array("groupname"=>$ligne["groupname"],"description"=>$ligne["description"],"UsersCount"=>$ligne["UsersCount"]);
			}else{
				$res[$ligne["groupdn"]]=$ligne["groupname"];
			}
		}

		return $res;	
}	

public function find_members_groups($dn,$pattern){
	$sql="SELECT activedirectory_users.samaccountname FROM activedirectory_users,activedirectory_groups
	WHERE activedirectory_users.dn=activedirectory_groups.userdn AND activedirectory_groups.groupdn='$dn'
	";
	
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){	writelogs("\n\n$sql\n\n$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("$dn -> " .mysql_num_rows($result),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$this->members[]=$ligne["samaccountname"];
			$this->ARRAY_MEMBERS[$ligne["samaccountname"]]=$ligne["samaccountname"];
		}	
	
	
	
}

public function COUNT_DE_USERS(){
	$q=new mysql();
	return $q->COUNT_ROWS("activedirectory_users", "artica_backup");
}


public function CountDeUSerOu($ou){
		$ou=utf8_encode($ou);
		$sql="SELECT COUNT(samaccountname) as tcount FROM activedirectory_users WHERE ou='$ou'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){writelogs("$sql -> $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("$sql -> {$ligne["tcount"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tcount"];
}
	
	
	

private function hash_get_ou_mysql($has_key=false){		
		$res=array();
		$sql="SELECT ou,dn FROM activedirectory_orgs WHERE enabled=1 ORDER BY ou";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ou=utf8_decode($ligne["ou"]);
			if($has_key){$res[$ou]=$ou;}else{$res[]=$ou;}
			
		}

		return $res;
}
		
	
	
}