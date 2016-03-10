<?php
include_once(dirname(__FILE__)."/class.mysql.inc");
include_once(dirname(__FILE__)."/class.groups.inc");

class compile_dansguardian{
	var $squid_port=0;
	var $listen_port=0;
	var $ipgroups=array();
	
	function compile_dansguardian(){
		$ini=new Bs_IniHandler();
		$ini->loadFile("/etc/artica-postfix/settings/Daemons/ArticaSquidParameters");
		$this->squid_port=$ini->_params["NETWORK"]["ALT_PORT"];
		$this->listen_port=$ini->_params["NETWORK"]["LISTEN_PORT"];
		$sock=new sockets();
		$this->SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
		if(!is_numeric($this->SquidDatabasesUtlseEnable)){$this->SquidDatabasesUtlseEnable=1;}			
		
		
	}
	
	function build(){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian ->compile_default()\n";}
		$this->compile_default();
		
		
		
	}
	
	private function loadgroupsrules(){
		$GLOBALS["DGRULES"][0]=1;
		$q=new mysql_squid_builder();
		$sql="SELECT ID FROM webfilter_rules WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." DansGuardian Fatal error $q->mysql_error LINE: ". __LINE__."\n";}
		$c=1;;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$c++;
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." rule {$ligne["ID"]} = $c\n";}
			$GLOBALS["DGRULES"][$ligne["ID"]]=$c;
		}
		
	}
	
	private function filtergroupslist_assoc($ruleid,$groupid){
		$f=array();
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilter_group WHERE enabled=1 AND ID=$groupid";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian filtergroupslist_assoc(): $sql ruleid:$ruleid, groupid: $groupid\n";}
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["ID"]==null){return;}
		$DGRULE=$GLOBALS["DGRULES"][$ruleid];
		$groupname=$ligne["groupname"];
		$f[]="# rule id:$ruleid -> $DGRULE, groupid = $groupid Name:$groupname";
		
		if($ligne["localldap"]==1){
			$gp=new groups($ligne["gpid"]);
				while (list ($num, $member) = each ($gp->members)){
					if(trim($member)==null){continue;}
					if(strpos($member, "$")>0){continue;}
					$f[]="$member=filter$DGRULE";
				}
		}
		
		if($ligne["localldap"]==2){
			$f[]=$this->members_from_activedirectory($ligne["ID"],$DGRULE);
		}		
		
		$sql="SELECT * FROM webfilter_members WHERE enabled=1 AND groupid=$groupid";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian filtergroupslist_assoc():gpid:$groupid ". mysql_num_rows($results)." items\n";}
		
		while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne2["pattern"])==null){continue;}
			if($GLOBALS["VERBOSE"]){writelogs("$groupname Type:{$ligne2["membertype"]} {$ligne2["pattern"]} = filter$DGRULE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if($ligne2["membertype"]==0){$this->ipgroups[]="{$ligne2["pattern"]} = filter$DGRULE";}
			if($ligne2["membertype"]==2){$this->ipgroups[]="{$ligne2["pattern"]} = filter$DGRULE";}
			if($ligne2["membertype"]==1){$f[]="{$ligne2["pattern"]}=filter$DGRULE";}		
		}
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian ruleid:$ruleid, groupid: $groupid -> ". count($f) ." items\n";}
		return @implode("\n", $f);
		
	}
	
	
	private function filtergroupslist(){
		$f=array();
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilter_assoc_groups";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$webfilter_id=$ligne["webfilter_id"];
			$group_id=$ligne["group_id"];
			$f[]=$this->filtergroupslist_assoc($webfilter_id,$group_id);
		}
		
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian filtergroupslist -> ". count($f) ." items\n";}
		@file_put_contents("/etc/dansguardian/lists/filtergroupslist", @implode("\n", $f));
		
	}
	
	
	private function members_from_activedirectory($ID,$DGRULE){
		$sql="SELECT dn FROM webfilter_group WHERE ID=$ID";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$dn=base64_decode($ligne["dn"]);
		$sql="SELECT activedirectory_users.samaccountname FROM activedirectory_users,activedirectory_groups
		WHERE activedirectory_groups.userdn=activedirectory_users.dn AND activedirectory_groups.groupdn='$dn'";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian :$sql\n";}
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian :$DGRULE] Active Directory `{$ligne["samaccountname"]}`\n";}
				$f[]="{$ligne["samaccountname"]}=filter$DGRULE";
		}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." DansGuardian Active Directory group $ID return ". count($f) ." items\n";}
		return @implode("\n", $f)."\n";
		
	}
	
	
	
	private function dansguardianFrules(){
		if(!isset($GLOBALS["DGRULES"])){$this->loadgroupsrules();}
		$rules=$GLOBALS["DGRULES"];
		
		$q=new mysql_squid_builder();
		while (list ($ruleID, $DGRULE) = each ($rules)){
			echo "Starting......: ".date("H:i:s")." DansGuardian building rule `$ruleID`\n";
			$sql="SELECT * FROM webfilter_rules WHERE ID=$ruleID";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			if($ruleID==0){
				$sock=new sockets();
				$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
				$ligne["groupname"]="default";
			}
			
				if(!isset($ligne["blockdownloads"])){$ligne["blockdownloads"]=0;}
				if(!isset($ligne["deepurlanalysis"])){$ligne["deepurlanalysis"]=0;}
				if(!isset($ligne["sslcertcheck"])){$ligne["sslcertcheck"]=0;}
				if(!isset($ligne["TemplateError"])){$ligne["TemplateError"]=null;}
				if(!isset($ligne["groupmode"])){$ligne["groupmode"]=1;}
				if(!isset($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
				if(!isset($ligne["searchtermlimit"])){$ligne["searchtermlimit"]=0;}
				if(!isset($ligne["bypass"])){$ligne["bypass"]=0;}
				if(!isset($ligne["BypassSecretKey"])){$ligne["BypassSecretKey"]='';}
				if(!isset($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}			
			
			
			$ligne["groupname"]=str_replace("'", "`", $ligne["groupname"]);
			if($ligne["blockdownloads"]==0){$ligne["blockdownloads"]="off";}else{$ligne["blockdownloads"]="on";}
			if($ligne["deepurlanalysis"]==0){$ligne["deepurlanalysis"]="off";}else{$ligne["deepurlanalysis"]="on";}
			if($ligne["sslcertcheck"]==0){$ligne["sslcertcheck"]="off";}else{$ligne["sslcertcheck"]="on";}
			if(!is_numeric($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=50;}
			if(!is_numeric($ligne["embeddedurlweight"])){$ligne["embeddedurlweight"]=0;}
			
			
			$bannedsitelistname="bannedsitelist{$ruleID}";
			$weightedphraselist="weightedphraselist{$ruleID}";
			$exceptionsitelistname="exceptionsitelist{$ruleID}";
			$bannedextensionlist="bannedextensionlist{$ruleID}";
			$exceptionphraselist="exceptionphraselist{$ruleID}";
			$bannedregexpurllist="bannedregexpurllist{$ruleID}";
			$exceptionregexpurllist="exceptionregexpurllist{$ruleID}";
			$TemplateError=trim($ligne["TemplateError"]);
			if($TemplateError==null){$TemplateError=@file_get_contents(dirname(__FILE__)."/databases/dansguard-template.html");}
			$TemplateErrorHeader=@file_get_contents(dirname(__FILE__)."/databases/dansguard-template-header.html");
			$TemplateErrorFinal="$TemplateErrorHeader$TemplateError\n</body>\n</html>";
			@mkdir("/usr/share/dansguardian/languages/ukenglish",755,true);
			@file_put_contents("/usr/share/dansguardian/languages/ukenglish/template-error-$DGRULE.html", $TemplateErrorFinal);
			
			$f=array();
			$f[]="groupmode = {$ligne["groupmode"]}";
			$f[]="groupname = '{$ligne["groupname"]}'";
			$f[]="bannedphraselist = '/etc/dansguardian/lists/bannedphraselist'";
			$f[]="weightedphraselist = '/etc/dansguardian/lists/$weightedphraselist'";
			$f[]="exceptionphraselist = '/etc/dansguardian/lists/$exceptionphraselist'";
			$f[]="bannedsitelist = '/etc/dansguardian/lists/$bannedsitelistname'";
			$f[]="greysitelist = '/etc/dansguardian/lists/greysitelist'";
			$f[]="exceptionsitelist = '/etc/dansguardian/lists/$exceptionsitelistname'";
			$f[]="bannedurllist = '/etc/dansguardian/lists/bannedurllist'";
			$f[]="greyurllist = '/etc/dansguardian/lists/greyurllist'";
			$f[]="exceptionurllist = '/etc/dansguardian/lists/exceptionurllist'";
			$f[]="exceptionregexpurllist = '/etc/dansguardian/lists/$exceptionregexpurllist'";
			$f[]="bannedregexpurllist = '/etc/dansguardian/lists/$bannedregexpurllist'";
			$f[]="picsfile = '/etc/dansguardian/lists/pics'";
			$f[]="contentregexplist = '/etc/dansguardian/lists/contentregexplist'";
			$f[]="urlregexplist = '/etc/dansguardian/lists/urlregexplist'";
			$f[]="blockdownloads = {$ligne["blockdownloads"]}";
			$f[]="exceptionextensionlist = '/etc/dansguardian/lists/exceptionextensionlist'";
			$f[]="exceptionmimetypelist = '/etc/dansguardian/lists/exceptionmimetypelist'";
			$f[]="bannedextensionlist = '/etc/dansguardian/lists/$bannedextensionlist'";
			$f[]="bannedmimetypelist = '/etc/dansguardian/lists/bannedmimetypelist'";
			$f[]="exceptionfilesitelist = '/etc/dansguardian/lists/exceptionfilesitelist'";
			$f[]="exceptionfileurllist = '/etc/dansguardian/lists/exceptionfileurllist'";
			$f[]="#logsitelist = '/etc/dansguardian/lists/logsitelist'";
			$f[]="#logurllist = '/etc/dansguardian/lists/logurllist'";
			$f[]="#logregexpurllist = '/etc/dansguardian/lists/logregexpurllist'";
			$f[]="headerregexplist = '/etc/dansguardian/lists/headerregexplist'";
			$f[]="bannedregexpheaderlist = '/etc/dansguardian/lists/bannedregexpheaderlist'";
			$f[]="#weightedphrasemode = 0";
			$f[]="naughtynesslimit = {$ligne["naughtynesslimit"]}";
			$f[]="#searchengineregexplist = '/etc/dansguardian/lists/searchengineregexplist'";
			$f[]="#searchtermlimit = {$ligne["searchtermlimit"]}";
			$f[]="#bannedsearchtermlist = '/etc/dansguardian/lists/bannedsearchtermlist'";
			$f[]="#weightedsearchtermlist = '/etc/dansguardian/lists/weightedsearchtermlist'";
			$f[]="#exceptionsearchtermlist = '/etc/dansguardian/lists/exceptionsearchtermlist'";
			$f[]="categorydisplaythreshold = 0";
			$f[]="embeddedurlweight = {$ligne["embeddedurlweight"]}";
			$f[]="enablepics = off";
			$f[]="bypass = {$ligne["bypass"]}";
			$f[]="bypasskey = {$ligne["BypassSecretKey"]}";
			$f[]="infectionbypass = 0";
			$f[]="infectionbypasskey = ''";
			$f[]="infectionbypasserrorsonly = on";
			$f[]="disablecontentscan = off";
			$f[]="deepurlanalysis = {$ligne["deepurlanalysis"]}";
			$f[]="reportinglevel = 3";
			$f[]="#accessdeniedaddress = 'http://YOURSERVER.YOURDOMAIN/cgi-bin/dansguardian.pl'";
			$f[]="htmltemplate = 'template-error-$DGRULE.html'";
			$f[]="sslcertcheck = {$ligne["sslcertcheck"]}";
			$f[]="sslmitm = off";
			@file_put_contents("/etc/dansguardian/dansguardianf$DGRULE.conf", @implode("\n", $f));
			$this->bannedsitelist($ruleID);
			$this->exceptionsitelist($ruleID);
			$this->bannedextensionlist($ruleID);
			$this->weightedphraselist($ruleID);
			$this->exceptionphraselist($ruleID);
			$this->bannedregexpurllist($ruleID);
			$this->exceptionregexpurllist($ruleID);
			$this->WriteToDisk_weightedphraselist();
						
		}
	}
	
	private function WriteToDisk_weightedphraselist(){
		$basedir="/etc/dansguardian/lists/phraselists";
		$sql="SELECT *  FROM phraselists_weigthed WHERE enabled=1";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strpos($ligne["pattern"], ">")==0){continue;}
			$f["{$ligne["language"]}-{$ligne["category"]}"][]="{$ligne["pattern"]},<{$ligne["score"]}>";
			
		}
		
		while (list ($category, $array) = each ($f)){
			if(!is_dir("$basedir/$category")){@mkdir("$basedir/$category");}
			@file_put_contents("$basedir/$category/weighted", @implode("\n", $array));
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian writing ". count($f)." items phraseslist\n"; 
		
		
	}
	
	
	private function bannedsitelist_time($ruleID,$whitelist=0){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_dtimes_rules WHERE ruleid='$ruleID' and enabled=1";
		$results = $q->QUERY_SQL($sql);
		$FINAL=array();
		if($whitelist==0){$suffix="banned";}else{$suffix="exception";}
		if(mysql_num_rows($results)==0){return;}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne['TimeName']=utf8_encode($ligne['TimeName']);
			$TimeSpace=unserialize($ligne["TimeCode"]);
			$f=array();
			while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	if(!is_numeric($num)){continue;}if($val<>1){continue;}$f[]=$num;}
		
			$file=array();
			$days=@implode("", $f);
			$file[]="time: {$TimeSpace["BEGINH"]} {$TimeSpace["BEGINM"]} {$TimeSpace["ENDH"]} {$TimeSpace["ENDM"]} $days";
			
			
			
			$results2 =$q->QUERY_SQL("SELECT * FROM webfilters_dtimes_blks WHERE webfilter_id='{$ligne["ID"]}' and modeblk=$whitelist");
			if(mysql_num_rows($results2)==0){echo "Starting......: ".date("H:i:s")." DansGuardian building time: rule {$ligne["ID"]} of main rule $ruleID have no $suffix(s)\n";continue;}
						
			while ($ligne2 = mysql_fetch_assoc($results2)) {
				$category=$ligne2["category"];
				$categorypath="/var/lib/squidguard/".$q->category_transform_name($category)."/domains";
				$categorypathTLSE="/var/lib/ftpunivtlse1fr/".$q->category_transform_name_toulouse($category)."/domains";
				if(!is_file($categorypath)){continue;}
				$file[]=".Include<$categorypath>";
				if($this->SquidDatabasesUtlseEnable==1){if(is_file($categorypathTLSE)){$file[]=".Include<$categorypathTLSE>";}}			
			}
			
			$file[]="";
			@file_put_contents("/etc/dansguardian/lists/{$suffix}listTime{$ruleID}-{$ligne["ID"]}", @implode("\n", $file));
			$FINAL[]=".Include</etc/dansguardian/lists/{$suffix}listTime{$ruleID}-{$ligne["ID"]}>";
		
		}
		
		return @implode("\n", $FINAL);
		
	}
	
	
	private function bannedsitelist($ruleID){
		$bannedsitelistname="bannedsitelist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		$f=array();
		
		
		$time=$this->bannedsitelist_time($ruleID);
		if($time<>null){$f[]=$time;}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=0 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$categorypath="/var/lib/squidguard/".$q->category_transform_name($category)."/domains";
			$categorypathTLSE="/var/lib/ftpunivtlse1fr/".$q->category_transform_name_toulouse($category)."/domains";
			if(!is_file($categorypath)){
				echo "Starting......: ".date("H:i:s")." DansGuardian building $category $categorypath no such file -> Order it\n";
				$order="COMPILEDB:$category";
				$md5=md5($order);
				$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");
				if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");}}
			}else{
				$f[]=".Include<$categorypath>";
			}
			
			if($this->SquidDatabasesUtlseEnable==1){
				if(is_file($categorypathTLSE)){
					$f[]=".Include<$categorypathTLSE>";
				}
			}
		}
		
		if(!isset($GLOBALS["webfilters_blkwhlts_black"])){
			$l=array();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=3 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){$www=trim($ligne["pattern"]);if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}$l[]="$www";}	
			if(count($l)>0){@mkdir("/etc/dansguardian/lists/blacklists/blacklisted",755,true);@file_put_contents("/etc/dansguardian/lists/blacklists/blacklisted/domains", @implode("\n", $l));$GLOBALS["webfilters_blkwhlts_black"]=true;}else{$GLOBALS["webfilters_blkwhlts_black"]=false;}
			}
			
		if($GLOBALS["webfilters_blkwhlts_black"]){$f[]=".Include</etc/dansguardian/lists/blacklists/blacklisted/domains>";}		
		
		
		echo "Starting......: ".date("H:i:s")." DansGuardian building bannedsitelist `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$bannedsitelistname", @implode("\n", $f));
	}
	
	private function bannedregexpurllist($ruleID){
		if(!is_dir("/etc/dansguardian/lists/bannedregexpurllist")){@unlink("/etc/dansguardian/lists/bannedregexpurllist");@mkdir("/etc/dansguardian/lists/bannedregexpurllist",755,true);}
		$q=new mysql_squid_builder();
		$bannedsitelistname="bannedregexpurllist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists/bannedregexpurllist")){@mkdir("/etc/dansguardian/lists/bannedregexpurllist",755,true);}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=5 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$f[]=$this->bannedregexpurllist_compile($category);
			
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian building bannedregexpurllist `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$bannedsitelistname", @implode("\n", $f));
	}
	
	private function exceptionregexpurllist($ruleID){
		$q=new mysql_squid_builder();
		$bannedsitelistname="exceptionregexpurllist{$ruleID}";
		if(is_dir("/etc/dansguardian/lists/bannedregexpurllist")){shell_exec("rm -rf /etc/dansguardian/lists/bannedregexpurllist");}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=6 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$f[]=$this->bannedregexpurllist_compile($category);
			
			
		}
		
		echo "Starting......: ".date("H:i:s")." DansGuardian building exceptionregexpurllist `$ruleID` ". count($f)." items\n";
		$f[]='';
		@file_put_contents("/etc/dansguardian/lists/$bannedsitelistname", @implode("\n", $f));		
	}

	private function bannedregexpurllist_compile($category){
		$q=new mysql_squid_builder();
		if(isset($GLOBALS["bannedregexpurllist_compile"][$category])){return @implode("\n",$GLOBALS["bannedregexpurllist_compile"][$category]);}
		$GLOBALS["bannedregexpurllist_compile"][$category]=array();
		$sql="SELECT pattern FROM regex_urls WHERE enabled=1 AND category='$category'";
		$results=$q->QUERY_SQL($sql);
		
		if(!$q->ok){echo $q->mysql_error."\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS["bannedregexpurllist_compile"][$category][]=$ligne["pattern"];
			
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian building exceptionregexpurllist `$category` ". count($GLOBALS["bannedregexpurllist_compile"][$category])." items\n";
		return @implode("\n",$GLOBALS["bannedregexpurllist_compile"][$category]);
		
	}
	
	
	
	
	private function weightedphraselist($ruleID){
		$weightedphraselist="weightedphraselist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=3 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$categorypath="/etc/dansguardian/lists/phraselists/$category/weighted";
			if(!is_file($categorypath)){continue;}
			$f[]=".Include<$categorypath>";
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian building weightedphraselist (block) `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$weightedphraselist", @implode("\n", $f));
	}
	
	private function exceptionphraselist($ruleID){
		$exceptionphraselist="exceptionphraselist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=4 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$categorypath="/etc/dansguardian/lists/phraselists/$category/weighted";
			if(!is_file($categorypath)){continue;}
			
			$f[]=".Include<$categorypath>";
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian building exceptionphraselist `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$exceptionphraselist", @implode("\n", $f));
	}	

	
	
	private function bannedextensionlist($ruleID){
		$bannedsitelistname="bannedextensionlist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		$sql="SELECT ext,description FROM webfilter_bannedexts WHERE enabled=1 AND ruleid=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ext=$ligne["ext"];
			$description=$ligne["description"];
			$f[]=".$ext\t#$description";
		}
		echo "Starting......: ".date("H:i:s")." DansGuardian building bannedextensionlist `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$bannedsitelistname", @implode("\n", $f));
	}	
	
	private function exceptionsitelist($ruleID){
		$exceptionsitelistname="exceptionsitelist{$ruleID}";
		if(!is_dir("/etc/dansguardian/lists")){@mkdir("/etc/dansguardian/lists",755,true);}
		$sql="SELECT webfilter_id,category FROM webfilter_blks WHERE modeblk=1 AND webfilter_id=$ruleID"; 
		$q=new mysql_squid_builder();
		$f=array();
		$f[]=$this->bannedsitelist_time($ruleID,1);
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$categorypath="/var/lib/squidguard/".$q->category_transform_name($category)."/domains";
			$categorypathTLSE="/var/lib/ftpunivtlse1fr/".$q->category_transform_name_toulouse($category)."/domains";
			if(!is_file($categorypath)){
				echo "Starting......: ".date("H:i:s")." DansGuardian building $category $categorypath no such file -> Order it\n";
				$order="COMPILEDB:$category";
				$md5=md5($order);
				$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");
				if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");}}
			}else{
				$f[]=".Include<$categorypath>";
			}
			
			if($this->SquidDatabasesUtlseEnable==1){
				if(is_file($categorypathTLSE)){
					$f[]=".Include<$categorypathTLSE>";
				}
			}					
		}
		if(!isset($GLOBALS["webfilters_blkwhlts_white"])){
			$l=array();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=2 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){$www=trim($ligne["pattern"]);if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}$l[]="$www";}	
			if(count($l)>0){@mkdir("/etc/dansguardian/lists/blacklists/whitelisted",755,true);@file_put_contents("/etc/dansguardian/lists/blacklists/whitelisted/domains", @implode("\n", $l));$GLOBALS["webfilters_blkwhlts_white"]=true;}else{$GLOBALS["webfilters_blkwhlts_white"]=false;}
			}
			
		if(isset($GLOBALS["webfilters_blkwhlts"])){$f[]=".Include</etc/dansguardian/lists/blacklists/whitelisted/domains>";}
		
		
		echo "Starting......: ".date("H:i:s")." DansGuardian building exceptionsitelist `$ruleID` ". count($f)." items\n";
		@file_put_contents("/etc/dansguardian/lists/$exceptionsitelistname", @implode("\n", $f));
	}	
	
	
	private function DansguardianVersion(){
		$unix=new unix();
		$dansguardian=$unix->find_program("dansguardian");
		if(!is_file($dansguardian)){return "0.0.0.0";}
		exec("$dansguardian -v 2>&1",$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#DansGuardian\s+([0-9\.]+)#", $val,$re)){return $re[1];}
			
		}
		return "0.0.0.0";
		
	}
	
	
	private function compile_default(){
			$sock=new sockets();
			$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
			if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
			$GLOBALS["ipgroups"]=array();
			$version=explode(".",$this->DansguardianVersion());
			echo "Starting......: ".date("H:i:s")." DansGuardian v{$version[0]}.{$version[1]}\n";
			
			
			
		
			$this->loadgroupsrules();
			$filterNumber=count($GLOBALS["DGRULES"]);
			if($filterNumber==0){$filterNumber=1;}
			$f[]="reportinglevel = 3";
			$f[]="languagedir = '/usr/share/dansguardian/languages'";
			$f[]="language = 'ukenglish'";
			$f[]="loglevel = 2";
			$f[]="logexceptionhits = 2";
			$f[]="logfileformat = 1";
			$f[]="maxlogitemlength = 0";
			$f[]="anonymizelogs = off";
			$f[]="logsyslog = on";
			$f[]="loglocation = '/var/log/dansguardian/access.log'";
			$f[]="statlocation = '/var/log/dansguardian/stats'";
			$f[]="filterip =";
			if($version[0]>1){if($version[1]>11){
				$f[]="filterports = $this->listen_port";
			}}
			
			if($version[0]>1){if($version[1]<12){
				$f[]="filterport = $this->listen_port";
			}}
			$f[]="proxyip = 127.0.0.1";
			$f[]="proxyport = $this->squid_port";
			$f[]="originalip = on";
			$f[]="accessdeniedaddress = 'http://YOURSERVER.YOURDOMAIN/cgi-bin/dansguardian.pl'";
			$f[]="nonstandarddelimiter = on";
			$f[]="usecustombannedimage = on";
			$f[]="custombannedimagefile = '/usr/share/dansguardian/transparent1x1.gif'";
			$f[]="usecustombannedflash = on";
			$f[]="custombannedflashfile = '/usr/share/dansguardian/blockedflash.swf'";
			$f[]="filtergroups = $filterNumber";
			$f[]="filtergroupslist = '/etc/dansguardian/lists/filtergroupslist'";
			$f[]="bannediplist = '/etc/dansguardian/lists/bannediplist'";
			$f[]="exceptioniplist = '/etc/dansguardian/lists/exceptioniplist'";
			$f[]="perroomblockingdirectory = '/etc/dansguardian/lists/bannedrooms/'";
			$f[]="showweightedfound = on";
			$f[]="weightedphrasemode = 2";
			$f[]="urlcachenumber = 1000";
			$f[]="urlcacheage = 900";
			$f[]="scancleancache = on";
			$f[]="phrasefiltermode = 2";
			$f[]="preservecase = 0";
			$f[]="hexdecodecontent = off";
			$f[]="forcequicksearch = off";
			$f[]="reverseaddresslookups = off";
			$f[]="reverseclientiplookups = off";
			$f[]="logclienthostnames = off";
			$f[]="createlistcachefiles = on";
			$f[]="prefercachedlists = off";
			$f[]="maxuploadsize = -1";
			$f[]="maxcontentfiltersize = 256";
			$f[]="maxcontentramcachescansize = 2000";
			$f[]="maxcontentfilecachescansize = 20000";
			$f[]="filecachedir = '/tmp'";
			$f[]="deletedownloadedtempfiles = on";
			$f[]="initialtrickledelay = 20";
			$f[]="trickledelay = 10";
			$f[]="downloadmanager = '/etc/dansguardian/downloadmanagers/fancy.conf'";
			$f[]="downloadmanager = '/etc/dansguardian/downloadmanagers/default.conf'";
			$f[]="#contentscanner = '/etc/dansguardian/contentscanners/icapscan.conf'";
			$f[]="contentscannertimeout = 60";
			$f[]="contentscanexceptions = off";
			$f[]="recheckreplacedurls = off";
			$f[]="forwardedfor = on";
			$f[]="usexforwardedfor = on";
			$f[]="logconnectionhandlingerrors = on";
			$f[]="logchildprocesshandling = off";
			$f[]="maxchildren = 120";
			$f[]="minchildren = 8";
			$f[]="minsparechildren = 4";
			$f[]="preforkchildren = 6";
			$f[]="maxsparechildren = 32";
			$f[]="maxagechildren = 500";
			$f[]="maxips = 0";
			$f[]="ipcfilename = '/tmp/.dguardianipc'";
			$f[]="urlipcfilename = '/tmp/.dguardianurlipc'";
			$f[]="ipipcfilename = '/tmp/.dguardianipipc'";
			$f[]="pidfilename = '/var/run/dansguardian/dansguardian.pid'";
			$f[]="nodaemon = off";
			$f[]="nologger = off";
			$f[]="logadblocks = off";
			$f[]="loguseragent = off";
			$f[]="daemonuser = 'squid'";
			$f[]="daemongroup = 'squid'";
			$f[]="softrestart = off";
			$f[]="#sslcertificatepath = '/etc/ssl/certs/'";
			$f[]="#cacertificatepath = '/home/stephen/dginstall/ca.pem'";
			$f[]="#caprivatekeypath = '/home/stephen/dginstall/ca.key'";
			$f[]="#certprivatekeypath = '/home/stephen/dginstall/cert.key'";
			$f[]="#generatedcertpath = '/home/stephen/dginstall/generatedcerts/'";
			$f[]="#generatedlinkpath = '/home/stephen/dginstall/generatedlinks/'";
			$f[]="";
			$f[]="authplugin = '/etc/dansguardian/authplugins/proxy-basic.conf'";
			$f[]="authplugin = '/etc/dansguardian/authplugins/proxy-digest.conf'";
			//$f[]="authplugin = '/etc/dansguardian/authplugins/ident.conf'";
			@mkdir("/etc/dansguardian/authplugins",755,true);
			@mkdir("/etc/dansguardian/lists/authplugins",755,true);
			$this->dansguardianFrules();
			$this->filtergroupslist();
			echo "Starting......: ".date("H:i:s")." DansGuardian ip based : ". count($this->ipgroups)." items\n";
			if(count($this->ipgroups)>0){
				$f[]="authplugin = '/etc/dansguardian/authplugins/ip.conf'";
			}
						
			
			
			@file_put_contents("/etc/dansguardian/authplugins/ip.conf", "\nplugname = 'ip'\nipgroups = '/etc/dansguardian/lists/authplugins/ipgroups'\n");
			
					
			if($EnableKerbAuth==1){
				//$f[]="authplugin = '/etc/dansguardian/authplugins/proxy-ntlm.conf'";
				@file_put_contents("/etc/dansguardian/authplugins/proxy-ntlm.conf", "\nplugname = 'proxy-ntlm'\n");
			}
			
			
			if(!is_dir("/etc/dansguardian")){@mkdir("/etc/dansguardian",755,true);}
			
			$f[]="";
			
			@file_put_contents("/etc/dansguardian/dansguardian.conf", @implode("\n", $f));
			
			@file_put_contents("/etc/dansguardian/lists/authplugins/ipgroups",@implode("\n",$this->ipgroups)."\n");
			$this->bannediplist();
			$this->exceptioniplist();
		
	}
	
	private function bannediplist(){
			$f=array();
			$q=new mysql_squid_builder();
			$sql="SELECT pattern as tcount FROM webfilters_blkwhlts WHERE blockType=0 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($ligne["PatternType"]==1){continue;}
				if($ligne["PatternType"]==0){$f[]=trim($ligne["pattern"]);}
			}

			@file_put_contents("/etc/dansguardian/lists/bannediplist",@implode("\n", $f));
		
		
	}
	
	private function exceptioniplist(){
			$f=array();
			$q=new mysql_squid_builder();
			$sql="SELECT pattern as tcount FROM webfilters_blkwhlts WHERE blockType=1 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($ligne["PatternType"]==1){continue;}
				if($ligne["PatternType"]==0){$f[]=trim($ligne["pattern"]);}
			}

			@file_put_contents("/etc/dansguardian/lists/exceptioniplist",@implode("\n", $f));		
		
	}
	
	
	
}
