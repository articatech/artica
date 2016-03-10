<?php
if(!isset($GLOBALS["DEBUG_PRIVS"])){$GLOBALS["DEBUG_PRIVS"]=false;}
class privileges{
	var $privs=array();
	var $uid=null;
	var $content=null;
	var $ldap;
	var $webpage=null;
	var $privilegesuid=null;
	private $radiusid=0;
	
	function privileges($uid=null,$privilegesuid=null,$radiusid=0){
		
		if($GLOBALS["DEBUG_PRIVS"]){echo "<br><span style='color:red;font-size:14px'>privileges($uid)</span><br>\n";}
		$this->privilegesuid=$privilegesuid;
		$this->uid=$uid;
		$this->radiusid=$radiusid;
		if(isset($_SESSION["RADIUS_ID"])){
			if($GLOBALS["VERBOSE"]){echo "Radius identifier={$_SESSION["RADIUS_ID"]}<br>\n";}
			if($_SESSION["RADIUS_ID"]>0){
				$this->radiusid=$_SESSION["RADIUS_ID"];
			}
		}
		if(function_exists("CurrentPageName")){$this->webpage=CurrentPageName();}
		
		
		$trace=debug_backtrace();
		if(isset($trace[1])){
				$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			
		}
		
		//if($GLOBALS["VERBOSE"]){$this->SearchPrivileges();}
		if(!isset($_SESSION["privileges_array"])){
			if($GLOBALS["DEBUG_PRIVS"]){echo "<br><span style='color:red;font-size:14px'>privileges_array NOT SET</span><br>\n";}
			if($this->webpage=="admin.index.php"){writelogs("$this->webpage: --> this->SearchPrivileges $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->SearchPrivileges();
		}
		if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=array();}
		
		if(count($_SESSION["privileges_array"])==0){
			$this->ldap=new clladp();
			if($this->webpage=="admin.index.php"){writelogs("$this->webpage: privileges_array COUNT = 0 this->SearchPrivileges() $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if($GLOBALS["DEBUG_PRIVS"]){echo "<br><span style='color:red;font-size:14px'>privileges_array COUNT = 0 ->SearchPrivileges()</span><br>\n";}
			$this->SearchPrivileges();
			if($GLOBALS["DEBUG_PRIVS"]){$this->DebugPrivs();}
		}
		
		if(isset($_SESSION[__CLASS__][$uid]["CONTENT"])){$this->content=$_SESSION[__CLASS__][$uid]["CONTENT"];}
		
		if(isset($_SESSION["privileges_array"])){
			if($GLOBALS["DEBUG_PRIVS"]){
				echo "<br><span style='color:red;font-size:14px'>privileges_array -> session " .__CLASS__.'/'.__FUNCTION__."/".basename(__FILE__)."/".__LINE__."</span><br>\n";
				$this->DebugPrivs();
			
			}
		}	
			
			$this->privs=$_SESSION["privileges_array"];
		
		return;
	}
	private function DebugPrivs(){
		if(!isset($_SESSION[__CLASS__][$this->uid]["PRIVS"])){return;}
		$array=$_SESSION[__CLASS__][$this->uid]["PRIVS"];
		
		while (list ($num, $ligne) = each ($array) ){
			if($ligne=="yes"){
				echo "<li style='color:red;font-size:14px'>[PRIVS]: $num = <strong>[$ligne]</strong></span></li>\n";
				
			}else{
				echo "<li style='color:red;font-size:14px'>[PRIVS]: $num = <code>[$ligne]</code></span></li>\n";
			}
		}
		
	}
	
	
	public function PolicyPassword($password,$replaceArray=null){
		$policiespwd=unserialize(base64_decode($this->privs["PasswdPolicy"]));
		$tpl=new templates();
		if(!is_array($replaceArray)){
			writelogs("replaceArray is not an array....",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		
		if(is_array($replaceArray)){$policiespwd=$replaceArray;}
		
		
		if(!is_array($policiespwd)){
			writelogs("policiespwd is not an array....",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return true;	
		}
		if($policiespwd["passwd_length"]>0){
			if(strlen($password)<$policiespwd["passwd_length"]-1){
				echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{minimum_length}: {$policiespwd["passwd_length"]}",1);
				return false;
			}
		}
		
		
		if($policiespwd["UpperCase"]==1){
			if(!preg_match("#[A-Z]+#",$password)){
				echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_upper_case_letter}",1);
				return false;	
			}
		}
		
		if($policiespwd["LowerCase"]==1){
			if(!preg_match("#[a-z]+#",$password)){
				echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_lower_case_letter}",1);
				return false;	
			}
		}

		if($policiespwd["DigitCase"]==1){
			if(!preg_match("#[0-9]+#",$password)){
				echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_digit_letter}",1);
				return false;	
			}
		}

		if($policiespwd["SpecialCase"]==1){
			$spec=utf8_decode("$ % ! * # ( ) [ ] § ; :  , / ~ & < > . - _ ²");
			if(!preg_match("#[\$\%\!\*\#\(\)\[\]\,\?\§\;\:\/\~\&\<\>\.\_\-\²]+#",$password)){
				echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_special_letter}
$spec",1);
				return false;	
			}
		}

		return true;
		
		
	}
	
	public function SearchPrivileges(){
		if(trim($this->uid)==null){
			if($GLOBALS["VERBOSE"]){echo "SearchPrivileges -> no uid, return<br>\n";}
			return;
		}

		$_SESSION["privileges_array"]=array();
		if($this->radiusid>0){
			$q=new mysql();
			$results=$q->QUERY_SQL("SELECT groupname FROM radusergroup WHERE username='$this->uid'","artica_backup");
			while ($ligne = mysql_fetch_assoc($results)) {
				$groupname=$ligne["groupname"];
				if($groupname=="administrators"){
					$this->privs["AsSystemAdministrator"]="yes";
					$this->privs["AsPostfixAdministrator"]="yes";
					$this->privs["AsArticaAdministrator"]="yes";
					$this->privs["AsSquidAdministrator"]="yes";
					$this->privs["AsSambaAdministrator"]="yes";
					$this->privs["AsVirtualBoxManager"]="yes";
					$this->privs["AsDansGuardianAdministrator"]="yes";
					$this->privs["AsWebStatisticsAdministrator"]="yes";
					$this->privs["AsSquidPersonalCategories"]="yes";
					$this->privs["AsArticaMetaAdmin"]="yes";
					
				}
				if($groupname=="ProxyAdms"){$this->privs["AsSquidAdministrator"]="yes";$this->privs["AsSquidPersonalCategories"]="yes";}
				if($groupname=="ProxySecurity"){$this->privs["AsDansGuardianAdministrator"]="yes";$this->privs["AsSquidPersonalCategories"]="yes";}				
				if($groupname=="WebStatsAdm"){$this->privs["AsWebStatisticsAdministrator"]="yes";$this->privs["AsSquidPersonalCategories"]="yes";}				
				
				
			}
			$_SESSION[__CLASS__][$this->uid]["PRIVS"]=$this->privs;
			$_SESSION["privileges_array"]=$this->privs;
			return;
		}
		
		
		if($this->uid==-100){
			if($this->webpage=="admin.index.php"){writelogs("$this->webpage: -->privileges_Manager -> uid=`$this->uid`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$this->privileges_Manager();
			$_SESSION["privileges_array"]=$this->privs;
			$_SESSION[__CLASS__][$this->uid]["PRIVS"]=$this->privs;
			return;
		}
		
		
		if($this->uid<>-100){
			if($this->webpage=="admin.index.php"){writelogs("$this->webpage: -->SearchPrivileges -> uid=`$this->uid`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$this->ldap=new clladp();
			$this->privileges_groups();	
			$this->ParsePrivilegesOU();
			$this->GetContent();
			$this->SinglePrivileges();
			$_SESSION["privileges_array"]=$this->privs;
			$_SESSION[__CLASS__][$this->uid]["PRIVS"]=$this->privs;
			$_SESSION[__CLASS__][$this->uid]["CONTENT"]=$this->content;
			return;
		}	
		
		if($this->webpage=="admin.index.php"){writelogs("$this->webpage: -->SearchPrivileges -> uid=`$this->uid` no privs !!",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	
	public function EmptyCache(){
		unset($_SESSION[__CLASS__][$this->uid]);
		unset($_SESSION["privileges_array"]);
		unset($_SESSION[__CLASS__][$this->uid]);
		
	}
	
	private function privileges_groups_AD(){
		if(isset($GLOBALS["privileges_groups_AD$this->uid"])){
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."]  privileges_groups_AD$this->uid is alreay set<br>\n";}
			return;}
		include_once(dirname(__FILE__))."/class.external.ad.inc";
		$gp=new external_ad_search();
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ->LoadMember($this->uid)<br>\n";}
		$attrs=$gp->LoadMember($this->uid);
		if(!isset($attrs["distinguishedName"][0])){
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] distinguishedName = NULL<br>\n";}
			return false;
		}
		$dn=$attrs["distinguishedName"][0];
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ->GroupsOfMember($dn)<br>\n";}
		$Groups=$gp->GroupsOfMember($dn);
		if(!is_array($Groups)){
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] GroupsOfMember = Not an array<br>\n";}
			return false;
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ->GroupsOfMember(".count($Groups).") items<br>\n";}
		
		while (list ($dnGroup, $GroupName) = each ($Groups) ){
			$hash=$gp->LoadGroupDataByDN($dnGroup);
			if(strlen($hash["ArticaGroupPrivileges"])>10){
				if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ArticaGroupPrivileges:". strlen($hash["ArticaGroupPrivileges"])." bytes for $GroupName -> `$dnGroup`<br>\n";}
				$this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
			}else{
				if(preg_match("#CN=Domain Admins,CN=Users,#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}
				if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}
				if(preg_match("#CN=Administrators,CN=Builtin#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}
				
			}
		}
		$GLOBALS["privileges_groups_AD$this->uid"]=true;
		return true;
	}
	
	
	private function default_all_rights(){
		$f[]="[AsArticaAdministrator]=\"yes\"";
		$f[]="[AsSystemAdministrator]=\"yes\"";
		$f[]="[AsPostfixAdministrator]=\"yes\"";
		$f[]="[AsSquidAdministrator]=\"yes\"";
		$f[]="[AsDansGuardianAdministrator]=\"yes\"";
		$f[]="[AsSystemWebMaster]=\"yes\"";
		$f[]="[AsArticaMetaAdmin]=\"yes\"";
		$f[]="[AsSquidPersonalCategories]=\"yes\"";
		
		
		
		return @implode("\n", $f)."\n";
	}
	
	private function privileges_Manager(){
		$this->ParsePrivileges($this->default_all_rights());
		
		
	}
	
	
	private function privileges_groups(){
			if(isset($GLOBALS["privileges_groups_executed$this->uid"])){return;}
			$GLOBALS["privileges_groups_executed"]=true;
			
			$ldap=new clladp();
			if($ldap->IsKerbAuth()){
				if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] IsKerbAuth() -> `TRUE` -> privileges_groups_AD()<br>\n";}
				if($this->privileges_groups_AD()){return;}
			}else{
				if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] IsKerbAuth() -> `FALSE`<br>\n";}
			}
			
			
			
			$filters=array("gidNumber","ArticaGroupPrivileges","cn");
			$dr =@ldap_search($ldap->ldap_connection,$ldap->suffix,"(&(objectClass=posixGroup)(memberUid=$this->uid))",$filters);	
			if(!$dr){
				$error=@ldap_err2str(ldap_errno($ldap->ldap_connection));
				writelogs("Fatal Error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			
			$result = @ldap_get_entries($ldap->ldap_connection, $dr);
			if($result){
				for($i=0;$i<$result["count"];$i++){
					$gidnumber=$result[$i]["gidnumber"][0];
					if($gidnumber==544){
						include_once(dirname(__FILE__)."/class.translate.rights.inc");
						$pp=new TranslateRights();
						$pt=$pp->GetPrivsArray();while (list ($num, $ligne) = each ($pt) ){$this->privs[$num]="yes";}
						continue;
					}						
							
					
					
					if($GLOBALS["DEBUG_PRIVS"]){writelogs("[$this->uid] is a part of {$result[$i]["cn"][0]} ($gidnumber)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					
					if(isset($result[$i]["articagroupprivileges"][0])){
						$this->ParsePrivileges($result[$i]["articagroupprivileges"][0]);
					}
				}
			}else{
				$error=@ldap_err2str(@ldap_errno($ldap->ldap_connection));
				if($error<>"Success"){writelogs("Fatal Error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
				
			
	}

	
	private function ParsePrivileges($content=null){
		
		if(preg_match_all('#\[([a-zA-Z]+)\]="(.+?)"#is',$content,$reg)){
				while (list ($num, $ligne) = each ($reg[1]) ){
						if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] PRIV:$ligne={$reg[2][$num]}<br>\n";}
						if($this->webpage=="logon.php"){writelogs("PRIV:$ligne={$reg[2][$num]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
						if($ligne=="ForceLanguageUsers"){$this->privs[$ligne]=$ligne;}
						if($ligne=="VirtualSambaServer"){$this->privs["VIRTUALS_SERVERS"][$reg[2][$num]]=true;}
						if($ligne=="PostfixInstance"){$this->privs["POSTFIX_SERVERS"][$reg[2][$num]]=true;}
						$this->privs[$ligne]=$reg[2][$num];
					}
			}
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

	private function ParsePrivilegesOU(){
			$ldap=new clladp();
			$userdn=$ldap->_Get_dn_userid($this->uid);
			if(!preg_match("#ou=users,ou=(.+?),dc#",$userdn,$re)){return ;}
			$ou=$re[1];
			$hash=$ldap->OUDatas($ou);
			if($this->webpage=="logon.php"){writelogs("[$this->uid] parsing $ou organization privileges ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if(isset($hash["ArticaGroupPrivileges"])){
				$privs=$this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
			}
		}

	private function GetContent(){
		while (list ($num, $ligne) = each ($this->privs) ){
			$f[]="[$num]=\"$ligne\"";
		}
		
		$this->content=@implode("\n",$f);
		
	}
	
	private function SinglePrivileges(){
		if($GLOBALS["AS_ROOT"]){return null;}
		if($this->uid==-100){return;}
		if($this->uid==null){return;}
		if($this->privilegesuid==null){
			if($GLOBALS["VERBOSE"]){echo "[PRIVS]:[$this->uid] parsing  user privileges\n";}
			$ArticaGroupPrivileges=$this->UserPrivs();
		}else{
			$ArticaGroupPrivileges=$this->privilegesuid;
		}
		
		$this->ParsePrivileges($ArticaGroupPrivileges);
	}
	
	
	private function UserPrivs(){
		if($GLOBALS["AS_ROOT"]){return null;}
		$ldap=new clladp();
		if($ldap->IsKerbAuth()){return null;}
		$searchdn="dc=organizations,$ldap->suffix";
		$sr =@ldap_search($ldap->ldap_connection,$searchdn,"(uid=$this->uid)");
		if(!$sr){
			writelogs("Unable to find : $this->uid stamp DoesNotExists has TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		
		$entry_id = ldap_first_entry($ldap->ldap_connection,$sr);
		if(!$entry_id){
			writelogs( "INFOS: bad value $entry_id: ($entry_id)  find: (uid=$this->uid) -> aborting function search engine doesn`t found the pattern",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		$attrs = ldap_get_attributes($ldap->ldap_connection, $entry_id);
		if(!isset($attrs["ArticaGroupPrivileges"][0])){$attrs["ArticaGroupPrivileges"][0]=null;}
		if($GLOBALS["VERBOSE"]){echo "[PRIVS]:[$this->uid] {$attrs["ArticaGroupPrivileges"][0]}\n";}	
		if(isset($attrs["ArticaGroupPrivileges"][0])){	
			return $attrs["ArticaGroupPrivileges"][0];
		}
	}
	
	
	
}