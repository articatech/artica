<?php
class external_acls_squid{
	private $ACL_ARP_ENABLED=false;
	private $ASROOT=false;
	private $SquidClientParams=array();
	private $SQUID_VERSION=null;
	private $IS_31=false;
	private $IS_32=false;
	private $IS_40=false;
	private $IS_35=false;
	private $SquidUrgency=0;
	private $EnableKerbAuth=0;
	private $LDAP_AUTH=0;
	private $LDAP_EXTERNAL_AUTH=0;
	private $SQUID_VERSIONS_LOGS=array();
	private $CORP_LICENSE=false;
	private $SquidUsersNumber=0;
	private $ActiveDirectoryEmergency=0;
	private $UfdbEnableParanoidMode=0;
	private $MacToUidUrgency=0;
	
	public function external_acls_squid(){
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		if(posix_getuid()==0){$this->ASROOT=true;}
		$this->SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
		$this->SQUID_VERSION=$this->root_squid_version_nocache();
		$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
		$this->EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
		$this->UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
		$this->ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
		$this->MacToUidUrgency=intval($sock->GET_INFO("MacToUidUrgency"));
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		if(preg_match("#^3\.[0|1]\.#",$this->SQUID_VERSION)){$this->IS_31=true;}
		if(preg_match("#^3\.[2|3|4|5|6|7|8]\.#",$this->SQUID_VERSION)){$this->IS_32=true;}
		if(preg_match("#^3\.[5|6|7|8]\.#",$this->SQUID_VERSION)){$this->IS_35=true;}
		if(preg_match("#^4\.[0|1|2|3|4|5]\.#",$this->SQUID_VERSION)){$this->IS_32=true;$this->IS_40=true;}
		$ini=new Bs_IniHandler();
		$ini->loadString($ArticaSquidParameters);
		$this->LDAP_AUTH=intval($sock->GET_INFO("SquidStandardLDAPAuth"));
		$this->SquidUsersNumber=intval($sock->GET_INFO("SquidUsersNumber"));
		$this->LDAP_EXTERNAL_AUTH=intval($ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"]);
		$this->CORP_LICENSE=$users->CORP_LICENSE;
		$this->defaultsvalues();
	}
	
	
	
	public function Build(){
		$conf[]="# Squid version $this->SQUID_VERSION";
		$conf[]="# Saved on ".date("Y-m-d H:i:s")." version 1.0";
		$conf[]="# Auth enabled: ".$this->is_auth();
		if($this->SquidUrgency==1){$conf[]="# SquidUrgency == 1";}
		if($this->LDAP_AUTH==1){$conf[]="# LDAP_AUTH == 1";}
		if($this->LDAP_EXTERNAL_AUTH==1){$conf[]="# LDAP_EXTERNAL_AUTH == 1";}
		if($this->EnableKerbAuth==1){$conf[]="# EnableKerbAuth == 1";}
		
		$conf[]=$this->external_acl_helper_categorize();
		$conf[]=$this->external_acl_paranoid();
		$conf[]=$this->external_acl_helper_categories();
		$conf[]=$this->external_acl_helper_sizequota();
		$conf[]=$this->external_acl_helper_restrict_access();
		$conf[]=$this->external_acl_helper_MacToUid();
		$conf[]=$this->external_acl_helper_time_quota_acl();
		$conf[]=$this->external_acl_helper_nudity_scanner();
		$conf[]=$this->external_acl_dynamic_helper();
		$conf[]=$this->external_acl_helper_Unlock();
		$conf[]=$this->external_acl_ext_time_quota();
		$conf[]=$this->external_acl_ext_session();
		//$conf[]=$this->external_acl_filewatcher();
		@file_put_contents("/etc/squid3/external_acls.conf", @implode("\n", $conf)."\n");
		
	}
	
	private function external_acl_helper_time_quota_acl(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$acl=new squid_acls_quotas_time();
		return $acl->build()."\n";
	}
	
	private function external_acl_helper_Unlock(){
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		if($sock->EnableUfdbGuardArtica()==1){return null;}
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
		$ACL_ARP_ENABLED=$this->ACL_ARP_ENABLED;
		if($SquidPerformance>2){return;}
		if(!$this->ASROOT){return;}
		$UfdbGuardHTTPAllowUnblock=intval($sock->GET_INFO("UfdbGuardHTTPAllowUnblock"));
		$SquidGuardWebUseExternalUri=intval($sock->GET_INFO("SquidGuardWebUseExternalUri"));
		
		if($SquidGuardWebUseExternalUri==1){
			$f[]="#-----------------------------------------------------------------";
			$f[]="# external_ufdbunblock Use an external URL";
			$f[]="#-----------------------------------------------------------------";
			return @implode("\n", $f);
		}
		
		
		if($UfdbGuardHTTPAllowUnblock==0){
			$q=new mysql_squid_builder();
			if($q->COUNT_ROWS("ufdb_page_rules")>0){$UfdbGuardHTTPAllowUnblock=1;}
		}
	
		if($UfdbGuardHTTPAllowUnblock==0){return;}
	
		$array["CACHE_TIME"]=60;
	
		$UnbblockMaxTime=intval($sock->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
		if($UnbblockMaxTime<5){$UnbblockMaxTime=30;}
		$UnbblockMaxTime=$UnbblockMaxTime-1;
		$UnbblockMaxTime=$UnbblockMaxTime*60;
	
		$L[]="%SRC";
		if($ACL_ARP_ENABLED){$L[]="%SRCEUI48";}else{$L[]="%%";}
		$L[]="%>ha{X-Forwarded-For}";
		$L[]="%DST";
		if($this->IS_32){$ipv4=" ipv4";}
	
		$ForceConf["ttl"]=$UnbblockMaxTime;
		$external_acl_childrens=$this->external_acl_childrens(0,true,$ForceConf);
		$f[]="# EnableTransparent27 = $EnableTransparent27\n";
		$f[]="#-----------------------------------------------------------------";
		$f[]="external_acl_type ArtAutoUnblock  $external_acl_childrens ".@implode(" ", $L)." /usr/share/artica-postfix/external_ufdbunblock.php\n";
		$f[]="acl UfdbAutoblock external ArtAutoUnblock";
		$f[]="#-----------------------------------------------------------------";
		$f[]="url_rewrite_access deny UfdbAutoblock";
		@chmod("/usr/share/artica-postfix/external_ufdbunblock.php", 0755);
		return @implode("\n", $f);
	
	
	}
	
	private function external_acl_helper_sizequota(){
		$GLOBALS["ArtcExtrnQuotaSizeScanner"]=false;
		if(!$this->CORP_LICENSE){return "# No license set\n";}
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'quota_size'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: quota_size $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: quota_size {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return;}
	
		$LOGON="%%";
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		$L[]="%EXT_LOG";
		$L[]="%ACL";
		if($this->is_auth()){$L[]="%LOGIN";}else{$L[]="%%";}
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		$L[]="%DST";
		$CMDILES=@implode(" ", $L);
	
		$GLOBALS["ArtcExtrnQuotaSizeScanner"]=true;
	
		$external_acl_childrens=$this->external_acl_childrens(0);
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'quota_size'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$rulename=$ligne["aclname"];
			$GLOBALS["LoadingExtrnQuotaSize"][$ligne["ID"]]=true;
			$count=$this->external_acl_helper_sizequota_items($ligne["ID"]);
			$f[]="#Group id {$ligne["ID"]} $count item(s)";
			
		}
		
		$f[]="external_acl_type ArtQuotaSize  $external_acl_childrens $CMDILES /usr/share/artica-postfix/external_acl_size_quota.php\n";
		@chmod("/usr/share/artica-postfix/external_acl_size_quota.php", 0755);
		@chown("/usr/share/artica-postfix/external_acl_size_quota.php", "squid");
		shell_exec("/etc/init.d/squid-tail restart &");
		return @implode("\n", $f);
	
	}
	
	private function external_acl_helper_sizequota_items($gpid){
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$rules[]=$ligne["pattern"];
	
		}
	
		@file_put_contents("/etc/squid3/acls/size_gpid{$gpid}.acl", serialize($rules));
		@chmod("/etc/squid3/acls/size_gpid{$gpid}.acl", 0755);
		@chown("/etc/squid3/acls/size_gpid{$gpid}.acl", "squid");
		return count($rules);
	
	}
	
	
	private function external_acl_ext_time_quota(){
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		if(!is_file("/lib/squid3/ext_time_quota_acl")){return null;}
		$q=new mysql_squid_builder();
		
		if($q->COUNT_ROWS("quota_objects")==0){return ;}
		$sql="SELECT *  FROM `quota_objects`";
		$results = $q->QUERY_SQL($sql);
		
		$indentifierZ["LOGIN"]="%un";
		$indentifierZ["SRC"]="%SRC";
		$indentifierZ["SRCEUI48"]="%SRCEUI48";
		$indentifierZ["EXT_TAG"]="%EXT_TAG";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ttl=$ligne["ttl"];
			$ForceConf["ttl"]=$ttl;
			$ForceConf["children-startup"]=1;
			$ID=$ligne["ID"];
			$external_acl_childrens=$this->external_acl_childrens(0,true,$ForceConf);
			$objectname=$ligne["objectname"];
			$sleep=intval($ligne["sleep"]);
			if($sleep==0){$sleep=60;}
			$cmd=array();
			$f[]="# Time Quota id $ID {$objectname}";
			$cmd[]="external_acl_type ExTimeQuota{$ligne["ID"]}";
			$cmd[]="$external_acl_childrens";
			$cmd[]="{$indentifierZ[$ligne["identifier"]]}";
			$cmd[]="/lib/squid3/ext_time_quota_acl";
			$cmd[]="-b /etc/squid3/acls/time_{$ID}.db";
			$cmd[]="-p $sleep";
			$cmd[]="-l /var/log/squid/ext_time_quota_acl.log";
			$cmd[]="/etc/squid3/acls/time_{$ID}.txt";
			$f[]=@implode(" ", $cmd);
			$f[]="";
			if(!is_file("/etc/squid3/acls/time_{$ID}.txt")){@touch("/etc/squid3/acls/time_{$ID}.txt");}
			
		}
		
		return @implode("\n", $f)."\n";
		
	}
	private function external_acl_ext_session(){
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		if(!is_file("/lib/squid3/ext_session_acl")){return null;}
		$q=new mysql_squid_builder();
	
		if($q->COUNT_ROWS("sessions_objects")==0){return ;}
		$sql="SELECT *  FROM `sessions_objects`";
		$results = $q->QUERY_SQL($sql);
	
		$indentifierZ["LOGIN"]="%un";
		$indentifierZ["SRC"]="%SRC";
		$indentifierZ["SRCEUI48"]="%SRCEUI48";
		$indentifierZ["EXT_TAG"]="%EXT_TAG";
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ttl=$ligne["ttl"];
			$ForceConf["ttl"]=3;
			$ForceConf["children-startup"]=1;
			$ID=$ligne["ID"];
			$external_acl_childrens=$this->external_acl_childrens(0,true,$ForceConf);
			$objectname=$ligne["objectname"];
			$sleep=intval($ligne["sleep"]);
			if($sleep==0){$sleep=60;}
			$cmd=array();
			$f[]="# Time Quota id $ID {$objectname}";
			$cmd[]="external_acl_type SessionTrack{$ligne["ID"]}";
			$cmd[]="concurrency=100 $external_acl_childrens";
			$cmd[]="{$indentifierZ[$ligne["identifier"]]}";
			$cmd[]="/lib/squid3/ext_session_acl";
			$cmd[]="-a -T $ttl -b /etc/squid3/acls/sessions_{$ID}";
			@mkdir("/etc/squid3/acls/sessions_{$ID}",0755,true);
			@chown("/etc/squid3/acls/sessions_{$ID}","squid");
			@chgrp("/etc/squid3/acls/sessions_{$ID}", "squid");
			$f[]=@implode(" ", $cmd);
			//$f[]="acl SessionTrack{$ligne["ID"]}_LOGIN external SessionTrack{$ligne["ID"]} LOGIN";
			//$f[]="acl SessionTrack{$ligne["ID"]}_ACTIVE external SessionTrack{$ligne["ID"]}";
			//$f[]="acl SessionTrack{$ligne["ID"]}_LOGOUT external SessionTrack{$ligne["ID"]} LOGOUT";
			
				
		}
	
		return @implode("\n", $f)."\n";
	
	}	
	
	private function external_acl_dynamic_helper(){
	
		$q=new mysql_squid_builder();
		$logprepend="[ACLS]";
		if($GLOBALS["VERBOSE"]){$logprepend="[DYNAACLS]";}
		$sock=new sockets();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'dynamic_acls'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
	
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
		if(!$q->ok){
			$f[]="# Mysql Error ".__FUNCTION__." in line ".__LINE__;
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: MySQL Error: $q->mysql_error\n";}
		}
	
		if($ligne["TCOUNT"]==0){
			$f[]="# Rules number = 0 ".__FUNCTION__." in line ".__LINE__;
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: Dynamic ACLS ({$ligne["TCOUNT"]} item)\n";}
			return @implode("\n", $f);
		}
		$ACLS_OPTIONS=unserialize(base64_decode($sock->GET_INFO("AclsOptions")));
	
	
	
		if(!isset($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
		if(!is_numeric($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
	
	
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: Dynamic ACLS ({$ligne["TCOUNT"]} item(s))\n";}
	
		
		$L[]=$this->LOGIN();
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}else{$L[]="%%";}
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		$L[]="%DST";
		
	
	
		$ForceConf["ttl"]=$ACLS_OPTIONS["DYN_TTL"];
		$ForceConf["negative_ttl"]=$ACLS_OPTIONS["DYN_TTL"];
		$ForceConf["children-startup"]=$ACLS_OPTIONS["CHILDREN_STARTUP"];
		$ForceConf["children-idle"]=$ACLS_OPTIONS["CHILDREN_IDLE"];
	
		$external_acl_childrens=$this->external_acl_childrens(0,true,$ForceConf);
		$f[]="external_acl_type DynamicAcls $external_acl_childrens ".@implode(" ", $L)." /usr/share/artica-postfix/external_acl_dynamic.php";
		return @implode("\n", $f)."\n";
	}	
	
	private function LOGIN(){
		$LOGIN="%LOGIN";
		if(!$this->is_auth()){$LOGIN=null;}
		if($LOGIN==null){$LOGIN="%%";}
		return $LOGIN;
	}
	
	private function SRCEUI48(){
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		return $SRCEUI48;
	}
	
	private function external_acl_paranoid(){
		if($this->SquidUrgency==1){return null;}
		if($this->UfdbEnableParanoidMode==0){return "\n# Paranoid mode disabled\n"; }
		$sock=new sockets();
		if($sock->EnableUfdbGuard()==0){return "\n# Web filtering disabled\n"; }
		$ParanoidBlockerEmergency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ParanoidBlockerEmergency"));
		if($ParanoidBlockerEmergency==1){return "\n# Paranoid Emergency\n"; }
		
		$ipv4=null;
	
		$ForceConf["concurrency"]=10;
		$external_acl_childrens=$this->external_acl_childrens(0,false,$ForceConf);
		$SRCEUI48=$this->SRCEUI48();
		
		$L[]="%EXT_USER";
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		$L[]="%DST";
		

		$helper="/usr/share/artica-postfix/external_acl_paranoid.php";
		$f[]="external_acl_type ParanoidBlocker {$external_acl_childrens} ".@implode(" ", $L)." $helper";
		$f[]="acl IsBlockParanoid external ParanoidBlocker";
		$f[]="acl IsMgRPort myportname MyManagerPort";
		$f[]="http_access deny !IsMgRPort IsBlockParanoid all";
		$f[]="deny_info ERR_PARANOID IsBlockParanoid";
		@chmod($helper,0755);
		return @implode("\n", $f);
	
	
	}

	
	private function external_acl_filewatcher(){
		if($this->SquidUrgency==1){return null;}
		$ipv4=null;
		
		$ForceConf["ttl"]=0;
		$ForceConf["negative_ttl"]=0;
		
		$external_acl_childrens=$this->external_acl_childrens(0,false,$ForceConf);
		$SRCEUI48=$this->SRCEUI48();
		$LOGIN=$this->LOGIN();
		$L[]=$LOGIN;
		$L[]="%EXT_USER";
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		$L[]="%URI";
		$L[]="%<h{Content-Type} %<h{Content-Disposition} %<h{Content-Length}";
		if($this->IS_32){$ipv4=" ipv4";}
		


		$helper="/usr/share/artica-postfix/external_acl_response.php";
		$f[]="external_acl_type FileWatcherExt {$external_acl_childrens}{$ipv4} ".@implode(" ", $L)." $helper";
		$f[]="acl FileWatcher external FileWatcherExt";
		
		@chmod($helper,0755);
		
		return @implode("\n", $f);
		
		
	}
	
	
	private function external_acl_helper_hotspot(){
		if($this->SquidUrgency==1){return "# external_acl_helper_hotspot() -> Emergency mode !! \n";;}
		if($this->MacToUidUrgency==1){return "# external_acl_helper_hotspot() -> MacToUidUrgency mode !! \n";;}
		
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){return "# external_acl_helper_hotspot() -> ACL_ARP_ENABLED disabled !! \n";;}
		$external_acl_childrens=$this->external_acl_childrens();
		$helper="/usr/share/artica-postfix/external_acl_hotspot.php";
		$f[]="external_acl_type MacToUid  $external_acl_childrens $SRCEUI48 $helper";
		$f[]="acl MacToUid_acl external MacToUid\n";
		$f[]="http_access deny !MacToUid_acl";
		return @implode("\n", $f);
		@chmod($helper,0755);
		
		
	}
	
	
	private function external_acl_helper_MacToUid(){
		@unlink("/etc/squid3/MacToUidEnabled");
		if($this->SquidUrgency==1){return "# external_acl_helper_MacToUid() -> Emergency mode !! \n";;}
		if($this->MacToUidUrgency==1){return "# external_acl_helper_MacToUid() -> MacToUidUrgency mode !! \n";;}
		$sock=new sockets();
		$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
		if($EnableArticaHotSpot==0){
			$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
			if($SquidPerformance>2){return;}
		}
	
		$q=new mysql();
		
		if($EnableArticaHotSpot==1){
			return $this->external_acl_helper_hotspot();
			
		}
	
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$helper="/usr/share/artica-postfix/external_acl_usersMacs.php";
		@chmod($helper,0755);
	
		if(!is_file("/etc/squid3/usersMacs.db")){return "# /etc/squid3/usersMacs.db no such file\n";}
		
	
	
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
	
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
	
	
		$external_acl_childrens=$this->external_acl_childrens();
		$f[]="external_acl_type MacToUid  $external_acl_childrens ".@implode(" ", $L)." $helper --mactouid";
		$f[]="acl MacToUid_acl external MacToUid\n";
		$f[]="http_access deny !MacToUid_acl";
		@touch("/etc/squid3/MacToUidEnabled");
		return @implode("\n", $f);
	
	}	
	
	
	private function external_acl_helper_nudity_scanner(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$NoAuthPorts=null;
		$SquidNuditScanParams=unserialize(base64_decode($sock->GET_INFO("SquidNudityScanParams")));
		if(!isset($SquidNuditScanParams["ProcessesNumber"])){$SquidNuditScanParams["ProcessesNumber"]=30;}
		$ProcessesNumber=$SquidNuditScanParams["ProcessesNumber"];
	
		$array["CACHE_TIME"]=360;
		if($this->is_auth()){$L[]="%LOGIN";}
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'NudityScan'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [NUDE]: NudityScanner $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [NUDE]: NudityScanner {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return;}
		if($this->is_auth()){$L[]="%LOGIN";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		$GLOBALS["ArtcExtrnNudeScanner"]=true;
	
		$external_acl_childrens=$this->external_acl_childrens();
	
		$f[]="external_acl_type ArtcExtrnNudeScanner $external_acl_childrens ".@implode(" ", $L)." %URI /usr/share/artica-postfix/picporn.php\n";
		return @implode("\n", $f);
	
	
	}	
	private function NoAuthPorts(){
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT(*) as TCOUNT FROM proxy_ports WHERE enabled=1 and NoAuth=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(intval($ligne["TCOUNT"])==0){return null;}
		return "!NoAuthPort ";
	}
	
	private function external_acl_helper_restrict_access(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		if(!$this->ACL_ARP_ENABLED){return null;}
		if($this->IS_31){return null;}
		$sock=new sockets();
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		if($SquidPerformance>2){return null;}
	
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM computers_time WHERE `enabled`=1";
		$array["CACHE_TIME"]=300;
		$results = $q->QUERY_SQL($sql);
		$helper="/usr/share/artica-postfix/external_acl_restrict_access.php";
		@chmod($helper,0755);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM computers_time WHERE `enabled`=1"));
		if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
		if($ligne["tcount"]==0){return;}
		$L[]="%SRC";
		$L[]="%SRCEUI48";
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		$external_acl_childrens=$this->external_acl_childrens();
		$f[]="external_acl_type ArticaRestrictAccess  $external_acl_childrens ".@implode(" ", $L)." $helper";
		$f[]="acl ArticaRestrictAccess external ArticaRestrictAccess\n";
		$GLOBALS["HTTP_ACCESS"]["ArticaRestrictAccess"]="http_access deny ArticaRestrictAccess";
		$UfdbGuardSquidGuardIPWeb=$this->UfdbGuardSquidGuardIPWeb();
		$f[]="# Web filtering error page: \"$UfdbGuardSquidGuardIPWeb\"";
		if($UfdbGuardSquidGuardIPWeb<>null){
			$f[]="deny_info 307:$UfdbGuardSquidGuardIPWeb?clientaddr=%i&clientname=&clientuser=%i&clientgroup=default&targetgroup=restricted_time&url=%u ArticaRestrictAccess";
				
		}
	
	
	
		if(class_exists("unix")){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			echo "Starting......: ".date("H:i:s")." [MAC]: Restrict Access, building database...\n";
			if($GLOBALS["VERBOSE"]){
				system("$php /usr/share/artica-postfix/exec.squid.computer.access.php --nocheck");
			}else{
				shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.computer.access.php --nocheck >/dev/null 2>&1 &");
			}
		}
	
		return @implode("\n", $f)."\n";
	}	

	public function external_acl_helper_categorize(){
		$ScriptFileName="/usr/share/artica-postfix/external_acl_categorize.php";
		if($this->SquidUrgency==1){return "# external_acl_helper_categorize:: SquidUrgency is enabled, aborting\n";}
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=3600;
		$CategoriesHelperChildrenMax=10;
		
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'tag_categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categories $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categorize {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return "# external_acl_helper_categorize:: first query return no item\n";}
		
		$ForceConf["ttl"]=3600;
		$ForceConf["negative_ttl"]=0;
		$ForceConf["children-startup"]=5;
		$ForceConf["children-idle"]=1;
		$ForceConf["concurrency"]=5;
		
		$external_acl_childrens=$this->external_acl_childrens($CategoriesHelperChildrenMax,false,$ForceConf);
		
		$f[]="external_acl_type CategorizeEngine $external_acl_childrens %DST $ScriptFileName\n";
		$f[]="acl EtiquetteCategory external CategorizeEngine";
		$f[]="http_access deny !EtiquetteCategory";
		
		@chmod($ScriptFileName, 0755);
		@chown($ScriptFileName, "squid");
		return @implode("\n", $f);
		
		
		
	}
	
	public function external_acl_helper_categories(){
		$ScriptFileName="/usr/share/artica-postfix/external_acl_categories.php";
		$GLOBALS["ArtcExtrnCategoryScanner"]=false;
		$sock=new sockets();
		if(!$this->CORP_LICENSE){return "# No license set\n";}
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
		$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
		$CATEGORY_UNSET=false;
		if($SquidPerformance>0){$CATEGORY_UNSET=true;}
		if($EnableLocalUfdbCatService==1){$CATEGORY_UNSET=false;}
		if($RemoteUfdbCat==1){$CATEGORY_UNSET=false;}
		
		
		if($CATEGORY_UNSET){
			
				return "# external_acl_helper_categories:: No enabled for performance reason\n";;
			
		}
		if($this->SquidUrgency==1){return "# external_acl_helper_categories:: SquidUrgency is enabled, aborting\n";}
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categories $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categories {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){
			return "# external_acl_helper_categories:: first query return no item\n";
		}
	
		$LOGON="%%";
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		$L[]="%EXT_LOG";
		$L[]="%ACL";
		if($this->is_auth()){$L[]="%LOGIN";}else{$L[]="%%";}
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		$L[]="%DST";
		$CMDILES=@implode(" ", $L);
	
		
		$CategoriesHelperConcurrency=intval($sock->GET_INFO("CategoriesHelperConcurrency"));
		$CategoriesHelperChildrenMax=intval($sock->GET_INFO("CategoriesHelperChildrenMax"));
		$CategoriesHelperHidden=intval($sock->GET_INFO("CategoriesHelperHidden"));
		$CategoriesHelperStartup=intval($sock->GET_INFO("CategoriesHelperStartup"));
		$CategoriesHelperPostitiveTTL=intval($sock->GET_INFO("CategoriesHelperPostitiveTTL"));
		$CategoriesHelperNegativeTTL=intval($sock->GET_INFO("CategoriesHelperNetgativeTTL"));
		
		
		if($CategoriesHelperNegativeTTL==0){$CategoriesHelperNegativeTTL=360;}
		if($CategoriesHelperPostitiveTTL==0){$CategoriesHelperPostitiveTTL=360;}
		if($CategoriesHelperChildrenMax==0){$CategoriesHelperChildrenMax=5;}
		if($CategoriesHelperStartup==0){$CategoriesHelperStartup=2;}
		if($CategoriesHelperHidden==0){$CategoriesHelperHidden=1;}
		
		
		$ForceConf["ttl"]=$CategoriesHelperPostitiveTTL;
		$ForceConf["negative_ttl"]=$CategoriesHelperNegativeTTL;
		$ForceConf["children-startup"]=$CategoriesHelperStartup;
		$ForceConf["children-idle"]=$CategoriesHelperHidden;
		$ForceConf["concurrency"]=$CategoriesHelperConcurrency;
		
		$external_acl_childrens=$this->external_acl_childrens($CategoriesHelperChildrenMax,false,$ForceConf);
		
		
		
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
		
		if(mysql_num_rows($results)==0){
			return "# external_acl_helper_categories:: Rows number == 0 \n";
			
		}
		$GLOBALS["ArtcExtrnCategoryScanner"]=true;
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$rulename=$ligne["aclname"];
			$GLOBALS["LoadingArtcatz"][$ligne["ID"]]=true;
			$this->external_acl_helper_categories_items($ligne["ID"],$rulename);
			
		}
		
		$f[]="external_acl_type ArtCategories $external_acl_childrens $CMDILES $ScriptFileName\n";
	
		@chmod($ScriptFileName, 0755);
		@chown($ScriptFileName, "squid");
		return @implode("\n", $f);
	
	}
	
	private function external_acl_helper_categories_items($gpid,$rulename){
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$tablename=$q->cat_totablename($ligne["pattern"]);
			$HASH[$tablename]="{$ligne["pattern"]}";
		}
	
		@file_put_contents("/etc/squid3/acls/catz_gpid{$gpid}.acl", serialize($HASH));
	}
	
	private function external_acl_childrens($MacProc=0,$NoNegCache=false,$ForceConf=array()){
	
		$array["CACHE_TIME"]=360;
		$external_acl_children=intval($this->external_acl_children());
		$MacProc=$external_acl_children;
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		$external_acl_concurrency=0;
		$negative_ttl=$array["CACHE_TIME"];
		$ttl=$array["CACHE_TIME"];
	
	
	
		if(isset($ForceConf["ttl"])){$ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["negative_ttl"])){$negative_ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["children-startup"])){$external_acl_startup=$ForceConf["children-startup"];}
		if(isset($ForceConf["children-idle"])){$external_acl_idle=$ForceConf["children-idle"];}
		if(isset($ForceConf["concurrency"])){$external_acl_concurrency=$ForceConf["concurrency"];}
		if($external_acl_startup==0){$external_acl_startup=5;}
		if($external_acl_idle<1){$external_acl_idle=1;}
		if($MacProc<$external_acl_startup){$MacProc=$external_acl_startup;}
		if($MacProc<5){$MacProc=5;}
	
		$f[]="ttl=$ttl";
		if(!$NoNegCache){
			$f[]="negative_ttl=$negative_ttl";
		}else{
			$f[]="negative_ttl=0";
		}
		if($this->IS_32){
			$f[]="children-startup={$external_acl_startup}";
			$f[]="children-idle={$external_acl_idle}";
			$f[]="children-max=$MacProc";
			if($external_acl_concurrency>0){
				$f[]="concurrency=$external_acl_concurrency";
			}
			$f[]="ipv4";
		}else{
				
			if($external_acl_startup>1){
				$f[]="children=$MacProc";
			}
		}
	
		return @implode(" ", $f);
	
	}
	

	private function UfdbGuardSquidGuardIPWeb(){
		if(isset($GLOBALS["SquidGuardIPWebMEM"])){return $GLOBALS["SquidGuardIPWebMEM"];}
		$sock=new sockets();
		if($sock->EnableUfdbGuard()==0){
			$GLOBALS["SquidGuardIPWebMEM"]=null;
			return null;
		}
		include_once(dirname(__FILE__)."/class.compile.ufdbguard.inc");
		$conf=new compile_ufdbguard();
		$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
		if(is_array($SquidGuardIPWeb)){
			$GLOBALS["SquidGuardIPWebMEM"]=$SquidGuardIPWeb["SquidGuardIPWeb"];
		}
	
		return $GLOBALS["SquidGuardIPWebMEM"];
	
	}
	
	private function root_squid_version_nocache(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
	
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
	
		$this->SQUID_VERSIONS_LOGS[]="# Binary $squidbin";
	
	
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				$this->SQUID_VERSIONS_LOGS[]="# Version {$re[1]}";
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				if($re[1]=="3.3."){$re[1]="3.3.0";}
				if($re[1]=="3.4."){$re[1]="3.4.0";}
				if($re[1]=="3.5."){$re[1]="3.5.0";}
	
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
				}
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
	
	}
	
	public function is_auth(){
		if($this->SquidUrgency==1){return false;}
		if(isset($GLOBALS["squid_is_auth2"])){return $GLOBALS["squid_is_auth2"];}
			
		$GLOBALS["squid_is_auth2"]=$this->_is_auth();
		return $GLOBALS["squid_is_auth2"];
	}
	
	
	private function _is_auth(){
		if($this->SquidUrgency==1){return false;}
		if($this->LDAP_AUTH==1){return true;}
		if($this->LDAP_EXTERNAL_AUTH==1){return true;}
		if($this->EnableKerbAuth==1){return true;}
		$sock=new sockets();
		$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		if($EnableArticaHotSpot==1){return true;}
		$acl=new squid_acls();
		if($acl->IsObjectAuth()){return true;}
		return false;
	}	
	
	private function external_acl_children(){
		if(isset($GLOBALS["external_acl_children"])){return $GLOBALS["external_acl_children"];}
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		if($this->SquidUsersNumber==0){
			$GLOBALS["external_acl_children"]=$external_acl_children;
			return $GLOBALS["external_acl_children"];
		}
	
		
		if($this->SquidUsersNumber>49){$multiplicator=8;}
		if($this->SquidUsersNumber>499){$multiplicator=15;}
		if($this->SquidUsersNumber>1000){$multiplicator=20;}
		if($this->SquidUsersNumber>1500){$multiplicator=30;}

		$GLOBALS["external_acl_children"]=$multiplicator;
		return $multiplicator;
	
	
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
	
	
	
	}
	
	private function defaultsvalues(){
		if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!isset($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!isset($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}
		if($this->SquidClientParams["external_acl_startup"]<1){$this->SquidClientParams["external_acl_startup"]=2;}
		
		if($this->ActiveDirectoryEmergency==1){$this->EnableKerbAuth=0;}
	}
	
	
}