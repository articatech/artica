<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
class external_ad_search{
	private $ldap_connection;
	public $ou;
	public $error;
	public $suffix=null;
	private $connectionEncoded=null;
	public $IsError=false;
	private $UserConnected=null;
	private $debug=false;
	public $ldap_error;
	private $ldap_password;
	private $SquidAddkerAlernates=array();
	function external_ad_search($connectionEncoded=null){
		if(!isset($GLOBALS["AS_ROOT"])){
			if(posix_geteuid()>0){$GLOBALS["AS_ROOT"]=false;}else{$GLOBALS["AS_ROOT"]=true;}
		}
		$this->SquidAddkerAlernates=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidAddkerAlernates"));
		if($connectionEncoded<>null){$this->connectionEncoded=$connectionEncoded;}
		$this->ADLdap_init();
		$this->BuildDefault();
		
	}
	
	private function ADLdap_init(){
	
		if($GLOBALS["VERBOSE"]){
			ini_set('display_errors', 1);
			ini_set('html_errors',0);
			ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
		}
	
		if(class_exists("adLDAP")){return;}
		$lib=dirname(__FILE__)."/externals/adLDAP/adLDAP.php";
		if(!is_file($lib)){return array();}
		if($GLOBALS["VERBOSE"]){echo "Include $lib\n";}
		include($lib);
	}
	
	private function ADLdap_options(){
	
		$array=$this->KerbAuthInfos;
		$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
		$recursive=false;
		if(!isset($array["RECURSIVE_GROUPS"])){$array["RECURSIVE_GROUPS"]=0;}
		if($array["RECURSIVE_GROUPS"]==1){
			if($GLOBALS["VERBOSE"]){echo "RECURSIVE_GROUPS = TRUE\n";}
			$recursive=true;
		}
	
		$options=array(
				'base_dn'=>$GLOBALS["SUFFIX"],
				'ad_username'=>$array["WINDOWS_SERVER_ADMIN"],
				'ad_password'=>$array["WINDOWS_SERVER_PASS"],
				'recursive_groups'=>$recursive,
				'domain_controllers'=>array($array["LDAP_SERVER"]),
				'account_suffix'=>"@{$array["WINDOWS_DNS_SUFFIX"]}");
	
		return $options;
	
	}
	
	
	function CheckUserAuth($username,$password){
		$password=trim($password);
		
		
		
		if($this->KerbAuthInfos["LDAP_SERVER"]==null){
			$this->Debuglogs("Failed to connect to DC Could not obtain `LDAP_SERVER`",__FUNCTION__,__LINE__);
			$this->error="Failed to connect to DC Could not obtain `LDAP_SERVER`";
			writelogs("$this->error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$array=$this->KerbAuthInfos;
		

		

		
	
		$this->ldap_connection=@ldap_connect($this->KerbAuthInfos["LDAP_SERVER"],$this->KerbAuthInfos["LDAP_PORT"]);
		if(!$this->ldap_connection){
			$this->Debuglogs("Failed to connect to DC {$this->KerbAuthInfos["LDAP_SERVER"]}",__FUNCTION__,__LINE__);
			$this->error="Failed to connect to DC {$this->KerbAuthInfos["LDAP_SERVER"]}";
			$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$this->error";
			writelogs("$this->error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			@ldap_close();
			return false;
		}
		
		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
		if(!preg_match("#.*?@.+?#", $username)){
			$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}:$password";
			$bind=ldap_bind($this->ldap_connection, 
				"$username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}",
		 		$password);
		}else{
			$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$username:$password";
			writelogs("ldap_bind(...,$username,$password)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			
			$bind=ldap_bind($this->ldap_connection,
				"$username",
				$password);
		}
		if(!$bind){
			$error=ldap_err2str(ldap_errno($this->ldap_connection));
			$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":$error";
			$this->Debuglogs("Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} - $username `$error`",__FUNCTION__,__LINE__);
			$this->error="Failed to login to DC {$this->KerbAuthInfos["LDAP_SERVER"]} - $username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]} `$error`";
			writelogs($this->error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		return true;
		
		
		}
	

	
	function BuildDefault_ldap_server(){
		if(isset($this->KerbAuthInfos["ADNETIPADDR"])){return $this->KerbAuthInfos["ADNETIPADDR"];}
		if(isset($this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"])){$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
		$SMB=$this->SAMBA_GetNetAdsInfos();
		return $SMB["LDAP server"];
		
	}
	
	function UsersNumber(){
		
		
	}
	
	private function timeminSince($fromtime){
		if(!is_numeric($fromtime)){return 1000000;}
		$data2 = time();
		$difference = ($data2 - $fromtime);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		
	}
	
	public function NumUsers(){
		$NET_RPC_INFOS=$this->NET_RPC_INFOS();
		if($GLOBALS["VERBOSE"]){print_r($NET_RPC_INFOS);}
		return trim($NET_RPC_INFOS["Num users"]);
		
	}
	
	function NET_RPC_INFOS(){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$sock=new sockets();
		if(isset($GLOBALS["NET_RPC_INFOS_MEM"])){
			if(!is_array($GLOBALS["NET_RPC_INFOS_MEM"])){
				unset($GLOBALS["NET_RPC_INFOS_MEM"]);}else{
				if(count($GLOBALS["NET_RPC_INFOS_MEM"])==1){unset($GLOBALS["NET_RPC_INFOS_MEM"]);}
			}
		}
		
		if(!isset($GLOBALS["NET_RPC_INFOS_MEM"])){
			$array=unserialize(base64_decode($sock->GET_INFO("NET_RPC_INFOS")));
		}else{
			$array=$GLOBALS["NET_RPC_INFOS_MEM"];
		}
		
		if($GLOBALS["VERBOSE"]){echo "NET_RPC_INFOS count: ".count($array)."\n";}
		if(is_array($array)){
			if(count($array)>1){
				$time=$this->timeminSince($array["TIME"]);
				if($time<240){return $array;}
			}
		}
		$unix=new unix();
		$net=$unix->find_program("net");
		$array["USER"]=$this->BuildAdUser();
		$array["PASSWD"]=$unix->shellEscapeChars($this->KerbAuthInfos["WINDOWS_SERVER_PASS"]);
	
		if(!is_file($net)){return array();}
		$cmd="$net rpc info -U {$array["USER"]}%{$array["PASSWD"]} 2>&1";
		if($GLOBALS["VERBOSE"]){echo "NET_RPC_INFOS $cmd\n";}
		exec($cmd,$results);
		while (list ($key, $line) = each ($results)){
			if(!preg_match("#(.+?):(.+)#", $line,$re)){continue;}
			$ARRAY[trim($re[1])]=trim($re[2]);
			
		}
		
		
		$ARRAY["TIME"]=time();
		$GLOBALS["NET_RPC_INFOS_MEM"]=$ARRAY;
		
		$sock->SaveConfigFile(base64_encode(serialize($ARRAY)), "NET_RPC_INFOS");
		return $ARRAY;
		
	}	
	
	function PaseEncodedConnection(){
		$array=unserialize(base64_decode($this->connectionEncoded));
		
		if($GLOBALS["VERBOSE"]){
			while (list ($key, $line) = each ($array)){
				echo "$key = $line<br>\n";
				
			}
			
		}
		$this->debug=$array["DEBUG"];
		$this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
		$this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
		$this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["LDAP_PASSWORD"];
		$this->KerbAuthInfos["LDAP_DN"]=$array["LDAP_DN"];
		if(isset($array["LDAP_FILTER"])){
			if(preg_match("#^\?(.*)#", $array["LDAP_FILTER"],$re)){
				if(strpos($re[1], "?")>0){$re[1]=substr($re[1], 0,strpos($re[1], "?"));}
				$array["LDAP_FILTER"]="({$re[1]}=%s)";
				$this->KerbAuthInfos["LDAP_FILTER"]=$array["LDAP_FILTER"];
				$this->KerbAuthInfos["LDAP_ATTR"]=$re[1];
			}else{
				if($GLOBALS["VERBOSE"]){echo "{$array["LDAP_FILTER"]} #^\?(.+?)[\?$]#  no match\n";}
			}
			
		}
		
		
		if(isset($array["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=$array["LDAP_PORT"];}
	}
	
	
	private function BuildDefault(){
		
		if($this->connectionEncoded<>null){
			if($this->PaseEncodedConnection()){return;}
		}
		
		
		
		
		$this->LoadSambaInformations();
		if($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]==null){return;}
		if(!isset($this->KerbAuthInfos["LDAP_SERVER"])){$this->KerbAuthInfos["LDAP_SERVER"]=$this->BuildDefault_ldap_server();}
		if(!isset($this->KerbAuthInfos["LDAP_PORT"])){$this->KerbAuthInfos["LDAP_PORT"]=389;}
		if(!isset($this->KerbAuthInfos["LDAP_SUFFIX"])){
			writelogs("LDAP_SUFFIX not set ->SAMBA_GetNetAdsInfos() ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$SMB=$this->SAMBA_GetNetAdsInfos();
			$this->KerbAuthInfos["LDAP_SUFFIX"]=$SMB["Bind Path"];
		}
		if($this->KerbAuthInfos["ADNETIPADDR"]==null){
			$SMB=$this->SAMBA_GetNetAdsInfos();
			$this->KerbAuthInfos["ADNETIPADDR"]=$SMB["KDC server"];			
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS('adgroupsprivs','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`adgroupsprivs` (`DN` VARCHAR( 255 ) NOT NULL PRIMARY KEY,`content` TEXT NOT NULL) ENGINE=MYISAM;";
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		if(!isset($this->KerbAuthInfos["KerbAuthInfos"]["RECURSIVE_GROUPS"])){$this->KerbAuthInfos["KerbAuthInfos"]["RECURSIVE_GROUPS"]=0;}
		$this->suffix=$this->KerbAuthInfos["LDAP_SUFFIX"];
	
	}
	
	function Ldap_search($dn,$filter,$attrs,$maxRow=0){
		if(!$this->connect()){return array();}

		if(!is_array($attrs)){
			$sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
		}else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
		if (!$sr) {
			$this->ldap_last_error='Error LDAP search number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP search\ndn:$dn\n$filter\n" . ldap_err2str(ldap_errno($this->ldap_connection));
			writelogs("ERROR Ldap_search : $filter in $dn" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);
			return array();
		}
		
		return ldap_get_entries($this->ldap_connection,$sr);
		
	}	
	
	
	public function GroupsOfMember($dn){
		$res=array();
		$KeySession=md5(__CLASS__.__FUNCTION__.$dn);
		if(!$GLOBALS["AS_ROOT"]){
			if(isset($_SESSION[$KeySession])){
				return $_SESSION[$KeySession];
			}
		}
		if(!$this->connect()){return null;}
		
		$UserAR=$this->LoadMember($dn);
		$dn=$UserAR["distinguishedName"][0];
		$sAMAccountName=$UserAR["sAMAccountName"][0];
		if($GLOBALS["VERBOSE"]){echo "[AD] GroupsOfMember() $dn -> $sAMAccountName<br>\n";}
		
		if($sAMAccountName==null){return array();}
		$searchFilter="(&(objectClass=group)(member=$dn))";
		$filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search {$this->KerbAuthInfos["LDAP_SUFFIX"]} / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		
		
		while (list ($num, $ligne) = each ($hash) ){
			if(!isset($ligne["samaccountname"][0])){continue;}
			if($ligne["samaccountname"][0]==null){continue;}
			$MembersCount=0;
			$dn=$ligne["dn"];
			$GroupName=$ligne["samaccountname"][0];
			if($GLOBALS["VERBOSE"]){echo "[AD] GroupsOfMember() $dn -> $GroupName<br>\n";}
			
			$res[$dn]=$GroupName;
		}
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[$KeySession]=$res;}
		return $res;		
	}
	
	
	public function LoadMember($uid){
		$KeySession=md5(__CLASS__.__FUNCTION__.$uid);
		if(!$GLOBALS["AS_ROOT"]){if(isset($_SESSION[$KeySession])){
			$GLOBALS[$KeySession]=$_SESSION[$KeySession];
			}
		}
		
		
		if(isset($GLOBALS[$KeySession])){
			if(isset($GLOBALS[$KeySession]["ARTICA_OU"])){
				$this->ou=$GLOBALS[$KeySession]["ARTICA_OU"];
				return $GLOBALS[$KeySession];
			}
		}
		if(!$this->connect()){return null;}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$uid))";
		$filter=array();
		$DN=$this->KerbAuthInfos["LDAP_SUFFIX"];
		if(strpos($uid, ",")>0){
			$DN=$uid;
			$searchFilter="(&(objectClass=user)(sAMAccountName=*))";
		}
		
		$sr =@ldap_search($this->ldap_connection,$DN,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search {$this->KerbAuthInfos["LDAP_SUFFIX"]} / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$entry_id = ldap_first_entry($this->ldap_connection,$sr);
		if(!$entry_id){return null;}
		$attrs = ldap_get_attributes($this->ldap_connection, $entry_id);
		$DN=$attrs["distinguishedName"][0];
		$EXPLODED=explode(",",$DN);
		$prefix=$EXPLODED[0].",";
		$DN_PREFIXED=str_replace($prefix, "", $DN);
		
		
		if(strtolower(trim($DN_PREFIXED))==strtolower(trim("CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}"))){
			$this->ou=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
			$attrs["ARTICA_OU"]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];
		}else{
			$EXPLODED=explode(",",$DN);
			while (list ($a, $b) = each ($EXPLODED) ){if(preg_match("#OU=(.+)#i", $b,$re)){$attrs["ARTICA_OU"]=$re[1];$this->ou=$re[1];}}
		}
		
		
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[$KeySession]=$attrs;}
		$GLOBALS[$KeySession]=$attrs;
		return $attrs;
	}
	
	private function mem_getusage(){
		$time=0;
		$unit="KB";
		$mem=round(memory_get_usage(true)/1024);
		if($mem>1024){$mem=round($mem/1000,2);$unit="MB";}
		 
		$xtime=microtime(true);
		if(!isset($GLOBALS["VERBOSE_MICROTIME"])){
			$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
		}else{
			$time = $xtime - $GLOBALS["VERBOSE_MICROTIME"];
			$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
			$time=round($time,2);
		}
		return $time."s ".$mem.$unit;
	}	
	
	
	public function DNDUMP($dn){
		if(!$this->connect()){return null;}
		$sr=@ldap_read($this->ldap_connection, $dn, "(objectClass=*)",array());
		if (!$sr) {
			if($GLOBALS["VERBOSE"]){echo "BAD $dn SR!<br>\n";}
			writelogs("Bad search $dn ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$ARR=ldap_get_entries($this->ldap_connection,$sr);
		return $ARR[0];
	}
	
	
	private function MemberInfoByDN($dn,$recursive=false){
		if(!$this->connect()){return null;}
		$searchFilter="(objectClass=*)";
		
		$filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","objectClass","member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$AsGroup=false;
		
		
		
		
		for($i=0;$i<$hash[0]["objectclass"]["count"];$i++){
			$class=$hash[0]["objectclass"][$i];
			if($class=="group"){$AsGroup=true;break;}
		}
		
		
		
		
		if($AsGroup){
			if(!$recursive){return null;}
			$MembersCount=$hash[0]["member"]["count"];
			if($GLOBALS["VERBOSE"]){echo "MemberInfoByDN MembersCount -> $MembersCount\n";}
			for($i=0;$i<$MembersCount;$i++){
				if($GLOBALS["VERBOSE"]){echo "MemberInfoByDN $i = {$hash[0]["member"][$i]}\n";}
				$member=$this->MemberInfoByDN($hash[0]["member"][$i]);
				if(is_array($member)){
					while (list ($a, $b) = each ($member) ){
						if(trim($b)==null){continue;}
						$f[$b]=$b;
					}
				}else{
					$f[$member]=$member;
				}
			
			return $f;
			}
		}
		
		
		if(!isset($hash[0]["samaccountname"][0])){
			writelogs("samaccountname no such attribute",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return null;}
		return $hash[0]["samaccountname"][0];
	}
	
	
	Public function MembersFromGroupName($GroupName){
		
		$this->ADLdap_init();
		$options=$this->ADLdap_options();
		try {
			$adldap = new adLDAP($options);
		}
		catch (adLDAPException $e) {
			if($GLOBALS["VERBOSE"]){echo "$e\n";}
			return array();
		}
		
		try {
			$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
			catch (adLDAPException $e) {
				if($GLOBALS["VERBOSE"]){echo "$e\n";}
				return array();
			}
		
			$array=$adldap->group()->members($GroupName);
			if($GLOBALS["VERBOSE"]){echo "MembersFromGroupName $GroupName -> ".count($array)."\n";}
			return $array;		
		
		
	}
	
	
	public function HashUsersFromGroupDN($dn,$recursive=false,$ReturnHash=false){
		$ORGDN=$dn;
		$Tosend=array();
		if(isset($GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"])){return $GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"];}
		if(!$this->connect()){writelogs("Error: connection failed",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return null;}
		$f=array();
		$searchFilter="(objectClass=*)";
		
		$filter=array("member","memberOf");
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {writelogs("Bad search $dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){writelogs("Not an array...$dn / $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		
		if(!isset($hash[0]["member"]["count"])){$hash[0]["member"]["count"]=0;}
		
		$MembersCount=$hash[0]["member"]["count"];
		writelogs("MembersCount:$MembersCount in `$dn`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		
		if($GLOBALS["VERBOSE"]){echo "MembersCount:$MembersCount in `$dn` line ". __LINE__."\n";}
		for($i=0;$i<$MembersCount;$i++){
			$MemberName=$this->MemberInfoByDN($hash[0]["member"][$i]);
			if($GLOBALS["VERBOSE"]){echo "{$hash[0]["member"][$i]} => $MemberName line ". __LINE__."\n";}
			if($MemberName==null){continue;}
			if(is_array($MemberName)){
				while (list ($a, $b) = each ($MemberName) ){
					if(trim($b)==null){continue;}
					$f[$b]=$b;
				}
			}else{
				$f[$MemberName]=$MemberName;
			}
			
			
		}
		
		
		
		if(isset($hash[0]["memberof"]["count"])){
			for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
				$dn1=$hash[0]["memberof"][0];
				$ff=$this->HashUsersFromGroupDN($dn1);
				if(count($ff)>0){
					while (list ($a, $b) = each ($ff) ){
						if(trim($b)==null){continue;}
						$f[$b]=$b;
					}
				}
				
			}
		}
		
		if($ReturnHash){
			$GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"]=$f;
			return $f;}
		
		
		while (list ($a, $b) = each ($f) ){
			if(trim($b)==null){continue;}
			$Tosend[]=$b;
		}
		
		
		$GLOBALS["HashUsersFromGroupDN($ORGDN)$ReturnHash"]=$Tosend;
		return $Tosend;		
		
	}
	
	private function _DNinfos_local($dn){	
		$f=array();
		$func=__FUNCTION__;
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
			return array();
		}
		$searchFilter="(objectClass=*)";
		$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$f);
		if(!$sr){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn -> $searchFilter failed</strong><br>\n";}
			return array();
		}
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn -> ". count($hash)."</strong><br>\n";}
		return $hash;
		
	}
	
	public function DNinfos($dn){
		$f=array();
		$func=__FUNCTION__;
		if($GLOBALS["VERBOSE"]){echo "<hr>\n";}
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$dn</strong><br>\n";}
		$hash=$this->_DNinfos_local($dn);
		
		if(count($hash)>1){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#46a346'>OK Active Directory LOCAL</strong><br>\n";}
			return $hash;
		}
		
		
		$searchFilter="(objectClass=*)";
		if(count($this->SquidAddkerAlernates)>0){
			reset($this->SquidAddkerAlernates);
			while (list ($workgroup, $MainArray) = each ($this->SquidAddkerAlernates) ){
			if(!$this->connect($MainArray)){
				if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} $workgroup/Connection failed</strong><br>\n";}
				continue;
			}
			$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$f);
			$hash=@ldap_get_entries($this->ldap_connection,$sr);
			if(count($hash)>1){
				if($GLOBALS["VERBOSE"]){echo "<strong style='color:#46a346'>OK Active Directory $workgroup</strong><br>\n";}
				return $hash;}
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} $workgroup/{$MainArray["LDAP_SERVER"]} no such DN $dn</strong><br>\n";}
			}
		}
			
		
		
		return array();
	}
	
	public function SearchOuSimple($dn=null){
		$func=__CLASS__."/".__FUNCTION__."::";
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
			return array();
		}
		if($this->KerbAuthInfos["LDAP_SUFFIX"]==null){ $this->LoadSambaInformations(); }
		
		if($dn==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
		$searchFilter="(|(objectClass=organizationalUnit)(objectClass=container))";
		
		$filter=array();
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} DN &laquo;$dn&raquo; -$searchFilter</strong><br>\n";}
		$sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}`$error` ($errstr)</strong><br>\n";}
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$hash["count"]} items</strong><br>\n";}
		
		$FINAL=array();
		if(!is_array($hash)){return array();}
		for($i=0;$i<$hash["count"];$i++){
			$tempar=$hash[$i];
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$tempar["dn"]}</strong><br>\n";}
			$dn=$tempar["dn"];
			$objectClass=array();
			for($z=0;$z<$tempar["objectclass"]["count"];$z++){
				$FINAL[$dn]["objectClass"][$tempar["objectclass"][$z]]=true;
				
			}
			
			if(isset($tempar["ou"])){
				$FINAL[$dn]["NAME"]=$tempar["ou"][0];
				continue;
			}
			
			if(isset($tempar["name"])){
				$FINAL[$dn]["NAME"]=$tempar["name"][0];
			}
			
		}
		return $FINAL;
	}	
	
	public function searchGroupSimple($dn=null,$search=null){
		$func=__CLASS__."/".__FUNCTION__."::";
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}Connection failed</strong><br>\n";}
			return array();
		}
		if($dn==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
		$searchFilter="(objectClass=group)";
		if($search<>null){
			$searchFilter="(objectClass=group)";
		}
		$filter=array();
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}$dn -$searchFilter</strong><br>\n";}
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func}`$error` ($errstr)</strong><br>\n";}
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$hash["count"]} items</strong><br>\n";}
		$FINAL=array();
		if(!is_array($hash)){return array();}
		for($i=0;$i<$hash["count"];$i++){
			$tempar=$hash[$i];
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>{$func} {$tempar["dn"]}</strong><br>\n";}
			
			$dn=$tempar["dn"];
			if(isset($tempar["samaccountname"])){
				$FINAL[$dn]=$tempar["samaccountname"][0];
			}
		}
		return $FINAL;
	}
	
	
	public function SearchOu($ou){
		if(!$this->connect()){return false;}
		if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){return true;}
		$searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return false;
		}	
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return false;}	
		return true;
	}
	
	public function CountDeUSerOu($ou,$dn=null){
		
		if($dn<>null){
			if(!$GLOBALS["AS_ROOT"]){
				$q=new mysql();
				$sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `dn`='$dn' AND CountDeUsers>0";
				writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				return $ligne["CountDeUsers"];
			}
			
		}
		
		if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeUsers"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeUsers"];}
		if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
		if(!$GLOBALS["AS_ROOT"]){
			$q=new mysql();
			
			if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
				$ou=utf8_encode($ou);
				$sql="SELECT CountDeUsers FROM activedirectory_ou WHERE `name`='$ou'";
				writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				if($q->ok){
					$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeUsers"];
					return $ligne["CountDeUsers"];
				}else{
					writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
			}else{
				writelogs("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
			}
			
		}
		
		
		
		if(strpos($ou, ",")==0){
			$dn=$this->GetOuDN($ou);
		}else{
			$dn=$ou;
		}
		
		
		if($dn==null){return 0;}
		$pattern="*";
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn");
		
		
		
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return 0;}
		$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$hash["count"];
		return $hash["count"];
				
		
	}
	
	public function find_computers($tofind,$maxentries=0){
		if(!$this->connect()){writelogs("Unable to connect",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		$tofind=str_replace("***", "*", $tofind);
		$tofind=str_replace("**", "*", $tofind);
		$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
		if($dn==null){return array();}
		$searchFilter="(&(objectClass=computer)(|(cn=$tofind)(serviceprincipalname=$tofind)(distinguishedname=$tofind)(sAMAccountName=$tofind)(name=$tofind) (dnshostname=$tofind)))";
		$attrs=array("cn","operatingsystem","sAMAccountName","name","serviceprincipalname");
		writelogs("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxentries>0){
			$sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
		}else{
			$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
		}
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Error:Unknown suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		
		
		writelogs("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		return $hash;
	}
	
	public function GetDNFromUserid($userid){
		$array=$this->find_DN($userid);
		return $array[0]["dn"];
		
	}
	
	public function find_DN($tofind,$maxentries=0){
		
		$KeySession=md5(__CLASS__.__FUNCTION__.$tofind.$maxentries);
		if(!$GLOBALS["AS_ROOT"]){
			if(isset($_SESSION[$KeySession])){
				return $_SESSION[$KeySession];
			}
		}
		
				
		if(!$this->connect()){writelogs("Unable to connect",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		$tofind=str_replace("***", "*", $tofind);
		$tofind=str_replace("**", "*", $tofind);
		$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];
		if($dn==null){return array();}
	
	
		$searchFilter="(&(objectClass=person)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
		$attrs=array("dn","distinguishedname","sAMAccountName","uid");
		
	
		writelogs("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxentries>0){
			$sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
		}else{
			$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
		}
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Error:Unknown suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
	
	
		writelogs("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[$KeySession]=$hash;}
		return $hash;
	
			
	}	
	
	public function find_users($ou,$tofind,$maxentries=0){
		if(!$this->connect()){writelogs("Unable to connect",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return array();}
		$tofind=str_replace("***", "*", $tofind);
		$tofind=str_replace("**", "*", $tofind);
		if($ou==null){$dn=$this->KerbAuthInfos["LDAP_SUFFIX"];}
		if($dn==null){$dn=$this->GetOuDN($ou);}
		if(strpos($ou, ",")>0){$dn=$ou;}
		if($dn==null){return array();}
		
		
		$searchFilter="(&(objectClass=person)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sAMAccountName=$tofind) (givenname=$tofind)))";
		$attrs=array("name","sAMAccountName","mail","userPrincipalName","givenName","telephoneNumber","title","sn");
		$attrs=array("displayName","sAMAccountName","mail","givenName","telephoneNumber","title","sn","userPrincipalName","distinguishedName");
		
		writelogs("search $tofind in $dn max:$maxentries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxentries>0){
			$sr =@ldap_search($this->ldap_connection, $dn, $searchFilter, $attrs ,null, $maxentries,20);
		}else{
			$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$attrs,null, null, 20);
		}
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Error:Unknown suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not found $tofind in $dn max:$maxentries = 0 items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		
	
		writelogs("found ". count($hash)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		return $hash;
		
			
	}
	
	function LoadGroupDataByDN($dn){
		$dn=trim($dn);
		
		
		$q=new mysql();
		$sql="SELECT `content` FROM adgroupsprivs WHERE `DN`='$dn'";
		writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ArticaGroupPrivileges=$ligne["content"];
		
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ArticaGroupPrivileges -> `". strlen($ArticaGroupPrivileges)." bytes`<br>\n";}
		
		if(!$this->connect()){
			$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
			return $hash;
		}
		$filter=array();
		$searchFilter="(&(objectClass=group)(sAMAccountName=*))";
		writelogs("$searchFilter in $dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
			return $hash;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		$hash["ArticaGroupPrivileges"]=$ArticaGroupPrivileges;
		if(!$GLOBALS["AS_ROOT"]){$_SESSION[__CLASS__][__FUNCTION__][$dn]=$hash;}
		return $hash;
	}
	
	function SaveGroupPrivileges($content,$dn){
		$q=new mysql();
		$sql="SELECT `DN` FROM adgroupsprivs WHERE `DN`='$dn'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$content=addslashes($content);
		$sql="UPDATE adgroupsprivs SET content='$content' WHERE DN='$dn'";
		if($ligne["DN"]==null){
			
			$sql="INSERT INTO adgroupsprivs (DN,content) VALUES ('$dn','$content')";
		}
		writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	function build_sql_activedirectory_ou(){
		$hash=$this->hash_get_ou(false,array(),true);
		if(!$hash){return;}
		$q=new mysql();
		
		
		$q->QUERY_SQL("TRUNCATE TABLE activedirectory_ou","artica_backup");
		$q->QUERY_SQL("DROP TABLE activedirectory_groupsNames","artica_backup");
		$q->check_storage_table(true);
		while (list ($dn, $name) = each ($hash) ){
			if(trim($dn)==null){continue;}
			
			$CountDeUsers=$this->CountDeUSerOu($dn);
			$CountDeGroup=$this->CountDeGroups($dn,true);
			if($GLOBALS["VERBOSE"]){echo "Organization: $name $CountDeUsers members, $CountDeGroup groups\n";}
			
			$name=addslashes(utf8_encode($name));
			$dn=addslashes(utf8_encode($dn));
			$f[]="('$dn','$name','$CountDeUsers','$CountDeGroup')";
		}
		
		if(count($f)>0){
			if($GLOBALS["VERBOSE"]){echo count($f)." organization(s) found...\n";}
			$q->QUERY_SQL("INSERT IGNORE activedirectory_ou (`dn`,`name`,`CountDeUsers`,`CountDeGroups`) VALUES ".@implode(",", $f),"artica_backup");
			if($GLOBALS["VERBOSE"]){if(!$q->ok){echo "Fatal !!! $q->mysql_error\n";}}
		}
		
	}
	

	function hash_groups($ou,$fieldlist=0,$maxitems=0,$tofind=null){
		if(!$this->connect()){
			writelogs("Unable to connect",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		if(strpos($ou,",")>0){$dn=$ou;}else{
			$dn=$this->GetOuDN($ou);
		}
		if($dn==null){
			writelogs("$ou $tofind, dn=null ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();}
		$res=array();
		if($tofind==null){$tofind="*";}
		writelogs("Search Groups in $dn",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$filter=array("cn","description",'sAMAccountName',"dn","member","memberOf");
		$searchFilter="(&(objectClass=group)(sAMAccountName=$tofind))";
		writelogs("$searchFilter in $dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:$dn $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			writelogs("Unknown error...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			writelogs("Not an array ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();}
		
		$NOTCOUNT[strtolower("Domain Computers")]=true;
		$NOTCOUNT[strtolower("Domain Controllers")]=true;
		$NOTCOUNT[strtolower("Domain Users")]=true;
		$NOTCOUNT[strtolower("Domain Guests")]=true;
		$NOTCOUNT[strtolower("RAS and IAS Servers")]=true;

		
		writelogs(count($hash)." items ($searchFilter)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		while (list ($num, $ligne) = each ($hash) ){
			if(!isset($ligne["samaccountname"][0])){continue;}
			if($ligne["samaccountname"][0]==null){continue;}
			$MembersCount=0;
			$GroupName=$ligne["samaccountname"][0];
			if(isset($ligne["member"]["count"])){$MembersCount=$ligne["member"]["count"];}
			if($MembersCount==0){
			if(isset($NOTCOUNT[strtolower($GroupName)])){$MembersCount="-";}}
				
			
				
				
			switch ($fieldlist) {
				case 1:$res[$ligne["dn"]]=$ligne["samaccountname"][0];break;
				case 2;$res=$res . "{$ligne["dn"]};{$ligne["samaccountname"][0]}\n";break;
				default:$res[$ligne["cn"][0]]=array(
					"groupname"=>$ligne["samaccountname"][0],
					"gid"=>urlencode($ligne["dn"]),
					"description"=>$ligne["description"][0],
					"UsersCount"=>$this->CountDeMemberGroupDN($ligne["dn"])
					);
				break;
			}
			
				
		
		}
		
		return $res;
		
	}
	
	public function CountDeUsersByGroupName($groupname){
		if(!$this->connect()){return 0;}
		$filter=array("distinguishedName");
		$searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		$DN=$hash[0]["distinguishedname"][0];
		return $this->CountDeMemberGroupDN($DN);
	}
	
	public function CountDeUsersByGroupDN($dn){
		return $this->CountDeMemberGroupDN($dn);
	}
	
	
	private function CountDeMemberGroupDN($dn){
		if(isset($GLOBALS["CountDeMemberGroupDN($dn)"])){return $GLOBALS["CountDeMemberGroupDN($dn)"];}
		$hash=$this->HashUsersFromGroupDN($dn);
		if($GLOBALS["VERBOSE"]){echo "`$dn` -> ".count($hash)." members line ".__LINE__."\n";}
		$GLOBALS["CountDeMemberGroupDN($dn)"]=count($hash);
		return $GLOBALS["CountDeMemberGroupDN($dn)"];
		
	}
	
	
	private function GetOuDN($ou){
		if(!$this->connect()){return null;}
		if(strtolower($ou)==strtolower($this->KerbAuthInfos["ADNETBIOSDOMAIN"])){
			return "CN=Users,{$this->KerbAuthInfos["LDAP_SUFFIX"]}";
		}
		$searchFilter="(&(objectClass=organizationalUnit)(name=$ou))";
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return false;
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return false;}
		if(isset($hash[0]["dn"])){return $hash[0]["dn"];	}	
		if(isset($hash["dn"])){return $hash["dn"];}	
	}
	
	
	public function hash_get_ou($has_key=false,$array,$FullHash=false){
		
		if(!$GLOBALS["AS_ROOT"]){
			$q=new mysql();
			if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
				$sql="SELECT * FROM activedirectory_ou ORDER BY name";
				$results=$q->QUERY_SQL($sql,"artica_backup");
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$ou=$ligne["name"];
					$GLOBALS["hash_get_ou"][$ou]["CountDeGroups"]=$ligne["CountDeGroups"];
					$GLOBALS["hash_get_ou"][$ou]["CountDeUsers"]=$ligne["CountDeUsers"];
					if($has_key){$array[$ou]=$ou;}else{$array[]=$ou;}
					
				}
				return $array;
				
		}
		}
		
		
		
		if(!$this->connect()){return $array;}
		if($has_key){$array[$this->KerbAuthInfos["ADNETBIOSDOMAIN"]]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}else{$array[]=$this->KerbAuthInfos["ADNETBIOSDOMAIN"];}
		
		
		$searchFilter="(&(objectClass=organizationalUnit)(name=*))";
		$filter=array("name","dn");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}
		writelogs("{$hash["count"]} organization(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		
		
		
		for($i=0;$i<$hash["count"];$i++){
			
			$dn=$hash[$i]["dn"];
			$ou=$hash[$i]["name"][0];
			if(strtolower($ou)==strtolower("Domain Controllers")){
				$dn="CN=Users,".$this->KerbAuthInfos["LDAP_SUFFIX"];
				$ff=explode(",",$this->KerbAuthInfos["LDAP_SUFFIX"]);
				if(preg_match("#(.+?)=(.+)#", $ff[0],$re)){$ou=$re[2];}
			}
			
			
			
			if($FullHash){
				$array[$dn]=$ou;
				continue;
			}
			
			
			if($has_key){$array[$ou]=$ou;}else{$array[]=$ou;}
				
		}
		
		return $array;		
		
		
	}
	
	public function CountDeGroups($ou,$mysql=false){
		if(isset($GLOBALS["hash_get_ou"][$ou]["CountDeGroups"])){return $GLOBALS["hash_get_ou"][$ou]["CountDeGroups"];}
		
			if(isset($GLOBALS[__FUNCTION__.__CLASS__.$ou])){return $GLOBALS[__FUNCTION__.__CLASS__.$ou];}
			if(!$GLOBALS["AS_ROOT"]){
				$q=new mysql();
				if($q->COUNT_ROWS("activedirectory_ou", "artica_backup")>0){
					$ou=utf8_encode($ou);
					$sql="SELECT CountDeGroups FROM activedirectory_ou WHERE `name`='$ou'";
					writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
					if($q->ok){
						$GLOBALS[__FUNCTION__.__CLASS__.$ou]=$ligne["CountDeGroups"];
						return $ligne["CountDeGroups"];
					}else{
						writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}
				}else{
					writelogs("No rows in activedirectory_ou for $ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
				}
		}
		
		
		
		if(strpos($ou, ",")==0){$dn=$this->GetOuDN($ou);}else{$dn=$ou;}
		
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "!!! *** Error:connect failed in line ".__LINE__."\n";}
			return 0;
		}
		$pattern="*";
		$searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn","description");
		
		$sr =@ldap_search($this->ldap_connection,$dn,$searchFilter,$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				if($GLOBALS["VERBOSE"]){echo "!!! *** Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter\n";}				
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return 0;
		}

		
		$dn=addslashes($dn);
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			if($GLOBALS["VERBOSE"]){echo "!!! *** Error: Hash is not an array in line ".__LINE__."\n";}
			return 0;
		}
		
		
		$f=array();
		
		if($mysql){
			for($i=0;$i<$hash["count"];$i++){
				$dnG=addslashes($hash[$i]["dn"]);
				
				$samaccountname=addslashes($hash[$i]["samaccountname"][0]);
				if(!isset($hash[$i]["description"][0])){$hash[$i]["description"][0]=null;}
				
				$description=addslashes($hash[$i]["description"][0]);
				$CountDeUsers=$this->CountDeMemberGroupDN($hash[$i]["dn"]);
				$md5=md5($dn);
				if($GLOBALS["VERBOSE"]){echo "('$dnG','$samaccountname','$CountDeUsers','$description','$dn')\n";}
				$f[]="('$dnG','$samaccountname','$CountDeUsers','$description','$dn')";
				if($GLOBALS["VERBOSE"]){echo "$samaccountname $CountDeUsers $dnG\n";}
			}
			
			if(count($f)>0){
				$q=new mysql();
				$sql="INSERT IGNORE INTO activedirectory_groupsNames 
						(`dn`,`groupname`,`UsersCount`,`description`,`oudn`) VALUES ".
						@implode(",", $f);
				
				$q->QUERY_SQL($sql,"artica_backup");
		
				if(!$q->ok){echo "!!! $q->mysql_error\n";}
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Mysql = FALSE line ".__LINE__."\n";}
		}
		
		
		
		return $hash["count"];
	}
	
	
	private function LoadSambaInformations(){
			$sock=new sockets();
			$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
			$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
			if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
			if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
			
			if($EnableKerbAuth==1){
				if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>GET_INFO(KerbAuthInfos)</span>";}
				$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
				if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
				if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_DN"]=null;}
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];
				$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["WINDOWS_SERVER_NETBIOSNAME"];
				$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["ADNETBIOSDOMAIN"];
				$this->KerbAuthInfos["ADNETIPADDR"]=$array["ADNETIPADDR"];
				$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
				$this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
				$this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
				$this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
				$this->KerbAuthInfos["LDAP_DN"]=$array["LDAP_DN"];
				if(isset($array["LDAP_PASSWORD"])){
					$this->KerbAuthInfos["LDAP_PASSWORD"]=$array["LDAP_PASSWORD"];
				}

				
			}
			if($EnableSambaActiveDirectory==1){
				$Save=false;
				$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
				$this->KerbAuthInfos["WINDOWS_SERVER_NETBIOSNAME"]=$array["ADSERVER"];
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$array["ADDOMAIN"];
				$this->KerbAuthInfos["ADNETBIOSDOMAIN"]=$array["WORKGROUP"];
				$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$array["ADADMIN"];
				$this->KerbAuthInfos["WINDOWS_SERVER_PASS"]=$array["PASSWORD"];
				$this->KerbAuthInfos["ADNETIPADDR"]=$array["ADSERVER_IP"];
				
				
				if(!isset($array["LDAP_SUFFIX"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["Bind Path"]<>null){$array["LDAP_SUFFIX"]=$SMB["Bind Path"];$Save=true;}}
				if(!isset($array["LDAP_SERVER"])){$SMB=$this->SAMBA_GetNetAdsInfos();if($SMB["LDAP server"]<>null){$array["LDAP_SERVER"]=$SMB["LDAP server"];$Save=true;}}
				if($Save){$sock->SaveConfigFile(base64_encode(serialize($array)), "SambaAdInfos");}
				$this->KerbAuthInfos["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
				$this->KerbAuthInfos["LDAP_SERVER"]=$array["LDAP_SERVER"];
			}
			
			if($this->KerbAuthInfos["LDAP_SUFFIX"]==null){
				$SMB=$this->SAMBA_GetNetAdsInfos();
				
			}
			
			
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){
				$SMB=$this->SAMBA_GetNetAdsInfos();
				$array["FQDN"]=$SMB["LDAP server name"];
				$this->KerbAuthInfos["FQDN"]=$array["FQDN"];
				if($array["FQDN"]<>null){$FGH=explode(".",$array["FQDN"]);unset($FGH[0]);
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=@implode(".", $FGH);}
			}

			if($this->KerbAuthInfos["ADNETIPADDR"]<>null){
				$ipaddrZ=explode(".",$this->KerbAuthInfos["ADNETIPADDR"]);
				while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
				$this->KerbAuthInfos["ADNETIPADDR"]=@implode(".", $ipaddrZ);
			}
				
		}
		
	public function SearchGroups($pattern,$dn,$maxitems=0){
		if($maxitems==0){$maxitems=null;}
		if(!$this->connect()){return array();}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
		
		if($GLOBALS["VERBOSE"]){echo "searchFilter: $searchFilter<br>\n";}
		
		$filter=array();
		$sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				if($GLOBALS["VERBOSE"]){echo "$this->error<br>\n";}
				return array();
			}
		}
		
		return @ldap_get_entries($this->ldap_connection,$sr);
		
	}
	
	public function SearchUsers($pattern,$dn,$maxitems=0){
		if($maxitems==0){$maxitems=null;}
		if(!$this->connect()){return array();}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array();
		$sr =@ldap_list($this->ldap_connection,$dn,"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				return array();
			}
		}
	
		return @ldap_get_entries($this->ldap_connection,$sr);
	
	}	

	public function flexRTGroups($pattern,$maxitems=0,$objectClass="group"){
		if(!$this->connect()){return false;}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=$objectClass)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn");
		$Attribute="samaccountname";
		
		if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
			$searchFilter="(&(objectClass=$objectClass)(cn=$pattern))";
			$filter=array("cn","dn");			
			$Attribute="cn";
		}
		//posixGroup
		
		
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				return array();
			}
		}
		
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			$error=ldap_errno($this->ldap_connection);
			if($error>1){
				$errstr=@ldap_err2str($error);
				$this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			}
			return array();
		}
		
		if($hash["count"]==0){
			if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
				return $this->flexRTGroups($pattern,$maxitems=0,$objectClass="posixGroup");
					
			}
		}
		
		
		writelogs("{$hash["count"]} in {$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	
		for($i=0;$i<$hash["count"];$i++){
			$groupid=$hash[$i][$Attribute][0];
			if(is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"][0];}
			if(!is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"];}
			
			
			if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
			$array[$dn]="$groupid";
			
		}	
		
		return $array;
		
	}
	
	public function flexRTUsers($pattern,$maxitems=0){
		
		if(!$this->connect()){return false;}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array("samaccountname","dn");
		
		
		if(isset($this->KerbAuthInfos["LDAP_FILTER"])){
			$searchFilter=str_replace("%s", $pattern, $this->KerbAuthInfos["LDAP_FILTER"]);
			$filter=array($this->KerbAuthInfos["LDAP_ATTR"],"dn");
		}
		
		
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->error="flexRTGroups:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				return array();
			}
		}
		
		$hash=@ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){
			$error=ldap_errno($this->ldap_connection);
			if($error>1){
				$errstr=@ldap_err2str($error);
				$this->error="flexRTUsers:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			}
			return array();
		}
		
		writelogs("{$hash["count"]} in {$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			$groupid=$hash[$i][strtolower($this->KerbAuthInfos["LDAP_ATTR"])][0];
			if(is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"][0];}
			if(!is_array($hash[$i]["dn"])){$dn=$hash[$i]["dn"];}
				
				
			if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
			$array[$dn]="$groupid";
				
		}
		
		return $array;
				
		
	}
	
	

	public function searchGroup($pattern,$array,$maxitems=0){
		if(!$this->connect()){
			$this->IsError=true;
			return $array;
		}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=group)(sAMAccountName=$pattern))";
		$filter=array("samaccountname");
		
		
		
		
		if($maxitems==0){$maxitems=null;}
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				$this->IsError=true;
				$this->error="$errstr User:`$this->UserConnected` $searchFilter";
				writelogs("Error:`$error` ($errstr) suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}	

		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}
		writelogs("{$hash["count"]} Group(s) found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			
			$groupid=$hash[$i]["samaccountname"][0];
			$dn=$hash[$i]["dn"];
			if(substr($groupid,strlen($groupid)-1,1)=='$'){continue;}
			$array["@$groupid"]="@$groupid:$dn";
			
		}
		
		return $array;		
		
	}
	
	public function GetGroupDN($groupname){
		$maxitems=null;
		if(!$this->connect()){return null;}
		$searchFilter="(&(objectClass=group)(sAMAccountName=$groupname))";
		$filter=array("samaccountname","dn");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				$this->error="`$error` ($errstr) $this->UserConnected $searchFilter";
				writelogs("Error:`$error` ($errstr) $this->UserConnected $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return null;
		}	
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return null;}
		return $hash[0]["dn"];
		
	}
	
	
		
	public function searchUser($pattern,$array){
		if(!$this->connect()){return $array;}
		if($pattern==null){$pattern="*";}
		$searchFilter="(&(objectClass=user)(sAMAccountName=$pattern))";
		$filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
		$sr =@ldap_search($this->ldap_connection,$this->KerbAuthInfos["LDAP_SUFFIX"],"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);$errstr=@ldap_err2str($error);
				writelogs("$errstr) $this->UserConnected $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			return $array;
		}

		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $array;}	
		writelogs("{$hash["count"]} members found suffix:{$this->KerbAuthInfos["LDAP_SUFFIX"]} {$this->KerbAuthInfos["LDAP_SERVER"]}:389 $searchFilter",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			$userid=$hash[$i]["samaccountname"][0];
			if(substr($userid,strlen($userid)-1,1)=='$'){continue;}
			$mail=$hash[$i]["mail"][0];
			$userprincipalname=$hash[$i]["userprincipalname"][0];
			$displayname=$hash[$i]["displayname"][0];
			if($displayname==null){$displayname=$userid;}
			$array[$userid]=$displayname;
		}
		
		return $array;
		
	}
	
	
	function GroupsSearch($ou=null,$tofind,$max=0){
		return $this->flexRTGroups($tofind,$max);
	}
	
	
	function UserSearch($ou=null,$tofind,$max=0){
		$uid=$_SESSION["uid"];
		if($tofind==null){$tofind='*';}else{$tofind="$tofind*";}
		$filter="(&(objectClass=user)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(samaccountname=$tofind) (givenname=$tofind)))";
		$attrs=array("displayName","samaccountname","mail","givenname","telephoneNumber","mobile","title","sn","mozillaSecondEmail","employeeNumber");
		if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,$this->suffix";}
		//writelogs("Ldap_search : $filter in $dn", __CLASS__ . "/" . __FUNCTION__,__FILE__);
		$hash=$this->find_users($ou,$tofind,$max);
			
		$hash2=array();
		return array($hash,$hash2);
	
	}
	
	function SaveUserPhoto($content,$DN){
		if(!$this->connect()){return false;}
		$upd=array();
		$upd["thumbnailPhoto"]=$content;
		//$r=ldap_mod_add($this->ldap_connection,$DN,$upd);
		$r=@ldap_modify($this->ldap_connection,$DN,$upd);
		
		if(!$r){
		
			$ldap_errno=ldap_errno($this->ldap_connection);
			$ldap_err2str=ldap_err2str($ldap_errno);
			$this->ldap_error="$ldap_err2str lenght() = ".strlen($content);
			return false;
		}
		
		
		return true;
	}
	

	private function SAMBA_GetNetAdsInfos(){
		$sock=new sockets();
		
		if(isset($GLOBALS["CACHE_NETADS"])){
			if(trim($GLOBALS["CACHE_NETADS"]["KDC server"])<>null){
				return $GLOBALS["CACHE_NETADS"];
			}
		}
		
		if(isset($_SESSION["CACHE_NETADS"])){
				if(trim($_SESSION["CACHE_NETADS"]["KDC server"])<>null){
					return $_SESSION["CACHE_NETADS"];
			}
		}
		$GLOBALS["CACHE_NETADS"]=unserialize(base64_decode($sock->getFrameWork("samba.php?GetNetAdsInfos=yes")));
		$_SESSION["CACHE_NETADS"]=$GLOBALS["CACHE_NETADS"];
		return $GLOBALS["CACHE_NETADS"];
	}

	private function connect($MainConfig=array()){
		
		if($this->ldap_connection){@ldap_close($this->ldap_connection);}
		$LDAP_SERVER=$this->KerbAuthInfos["LDAP_SERVER"];
		$LDAP_PORT=intval($this->KerbAuthInfos["LDAP_PORT"]);
		
		if(isset($MainConfig["LDAP_SERVER"])){
			$LDAP_SERVER=$MainConfig["LDAP_SERVER"];
		}
		if(isset($MainConfig["LDAP_PORT"])){
			$LDAP_PORT=intval($MainConfig["LDAP_PORT"]);
		}
		if($LDAP_PORT==0){$LDAP_PORT=389;}
		
		if($LDAP_SERVER==null){
			$this->error="connect:: Failed to connect to DC Could not obtain `LDAP_SERVER` ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			writelogs("Failed to connect to DC Could not obtain `LDAP_SERVER`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}

		
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>ldap_connect($LDAP_SERVER,$LDAP_PORT)</strong><br>\n";}
		$ldap_connection=@ldap_connect($LDAP_SERVER,$LDAP_PORT);
		if(!$ldap_connection){
			$this->error="flexRTGroups:Failed to connect to DC {$LDAP_SERVER} in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			writelogs("connect:: Failed to connect to DC {$LDAP_SERVER}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>Failed to connect to DC {$LDAP_SERVER}:{$LDAP_PORT}</strong><br>\n";}
			@ldap_close($ldap_connection);
			return false;
		}
		
		ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
		

		$Username=$this->BuildAdUser();
		$Password=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
		
		if(isset($MainConfig["LDAP_DN"])){
			if($MainConfig["LDAP_DN"]<>null){$Username=$MainConfig["LDAP_DN"];}
		}
		
		if(isset($MainConfig["LDAP_PASSWORD"])){
			if($MainConfig["LDAP_PASSWORD"]<>null){$Password=$MainConfig["LDAP_PASSWORD"];}
			
		}
		
		
		if(trim($Username)==null){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>Failed to connect to DC {$LDAP_SERVER}:{$LDAP_PORT} without any username set</strong><br>\n";}
			$this->error="connect:: Failed to connect to DC without any username set".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			return false;
		}
		$this->UserConnected=$Username;
		
		
		if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>ldap_bind({$LDAP_SERVER}:{$LDAP_PORT},$Username,$Password)</strong><br>\n";}
		$bind=ldap_bind($ldap_connection, $Username, $Password);
		
		if(!$bind){
			@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
			$error=ldap_errno($ldap_connection);
			$errstr=@ldap_err2str($error);
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>Error: $error Failed to login as $Username to DC {$LDAP_SERVER}:{$LDAP_PORT} &laquo;$errstr - $extended_error&raquo;</strong><br>\n";}
			$this->error="$error Failed to login as $Username to DC {$LDAP_SERVER} `$errstr - $extended_error` in ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			writelogs("Er.$error Failed to login as $Username:$Password to DC {$LDAP_SERVER}:{$LDAP_PORT} `$errstr - $extended_error`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		
		$this->ldap_connection=$ldap_connection;
		
		
		return true;
		
		
	}
	
	public function BuildAdUser(){
		if(!isset($this->KerbAuthInfos["LDAP_DN"])){$this->KerbAuthInfos["LDAP_DN"]=null;}
		$this->KerbAuthInfos["LDAP_DN"]=trim($this->KerbAuthInfos["LDAP_DN"]);
	
		if(trim($this->KerbAuthInfos["LDAP_DN"])==null){
			writelogs("Err LDAP_DN:{$this->KerbAuthInfos["LDAP_DN"]} == NULL`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$error_plus="LDAP_DN = null";
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){
				$SMB=$this->SAMBA_GetNetAdsInfos();
				$array["FQDN"]=$SMB["LDAP server name"];
				$this->KerbAuthInfos["FQDN"]=$array["FQDN"];
				if($array["FQDN"]<>null){$FGH=explode(".",$array["FQDN"]);unset($FGH[0]);
				$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=@implode(".", $FGH);}
			}
				
			if(($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]<>null) && ($this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]<>null)){
				$Username="{$this->KerbAuthInfos["WINDOWS_SERVER_ADMIN"]}@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}";
			}
			$this->ldap_password=$this->KerbAuthInfos["WINDOWS_SERVER_PASS"];
		}else{
			$Username=$this->KerbAuthInfos["LDAP_DN"];
			$this->ldap_password=$this->KerbAuthInfos["LDAP_PASSWORD"];
			if(!preg_match("#(.+?)@#", $Username)){$Username="$Username@{$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]}";}
		}
		
		Return $Username;
		
	}
	
	private function Debuglogs($text=null,$function=null,$line=null){
		if(!$this->debug){return;}
		if($text==null){return;}
		$linetext=null;
	
		if($function==null){
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
			if(is_array($trace)){
				$filename=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}
		}
	
		$linetext=$text;
		if($function<>null){$linetext="$function/$line $linetext";}else{
			if($line<>null){
				$linetext="$line $linetext";
			}
		}
	
	
		if(function_exists("syslog")){
			$LOG_SEV=LOG_INFO;
			openlog("authenticator", LOG_PID , LOG_SYSLOG);
			syslog($LOG_SEV, $linetext);
			closelog();
	
		}
	}
	
}