<?php
include_once(dirname(__FILE__) . '/class.users.menus.inc');
include_once(dirname(__FILE__) . "/class.sockets.inc");
include_once(dirname(__FILE__) . "/class.ccurl.inc");
include_once(dirname(__FILE__) . "/class.os.system.tools.inc");
include_once(dirname(__FILE__) . "/class.ocs.inc");
include_once(dirname(__FILE__) . "/class.artica-meta.orders.inc");
include_once(dirname(__FILE__) . "/class.artica-meta.squidconf.inc");
include_once(dirname(__FILE__) . "/class.artica-meta.categories-client.inc");
class artica_meta{
	public $GLOBAL_ARRAY=array();
	public $RegisterDatas=array();
	public $ArticaMetaHostname;
	public $uuid;
	public $MAIN_URI;
	public $LOG_URI;
	
	private $ArticaMetaPort;
	private $ArticaMetaUsername;
	private $ArticaMetaPassword;
	private $ArticaMetaUseLocalProxy=0;
	private $ArticaMetaTimeOut=300;
	private $class_socket;
	private $debug=false;
	private $nochecksquid=true;
	public $ping_error=null;
	

	
	function artica_meta(){
		$this->debug=$GLOBALS["VERBOSE"];
		$this->class_socket=new sockets();
		$sock=new sockets();
		$ArticaLinkAutoconnect=intval($sock->GET_INFO('ArticaLinkAutoconnect'));
		$unix=new unix();
		$users=new usersMenus();
		$ArticaMetaRegisterDatas=base64_decode($this->class_socket->GET_INFO("ArticaMetaRegisterDatas"));
		$trace=debug_backtrace();
		$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		
		$this->ArticaMetaUsername=$sock->GET_INFO("ArticaMetaUsername");
		$this->ArticaMetaPassword=$sock->GET_INFO("ArticaMetaPassword");
		$this->ArticaMetaHostname=$sock->GET_INFO("ArticaMetaHost");
		$this->ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
		$this->ArticaMetaTimeOut=intval($sock->GET_INFO("ArticaMetaTimeOut"));
		if($this->ArticaMetaTimeOut==0){$this->ArticaMetaTimeOut=300;}
		$this->ArticaMetaUseLocalProxy=intval($sock->GET_INFO("ArticaMetaUseLocalProxy"));
		
		if($this->ArticaMetaPort==0){$this->ArticaMetaPort=9000;}
		$this->uuid=$unix->GetUniqueID();
		if($this->debug){$this->events("SYSTEMID: $this->uuid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		
		
		if($this->uuid==null){
			if(function_exists("client_progress")){client_progress("UUID is null",25);}
			if(function_exists("client_progress")){client_progress("Run wizard first",110);}
			if(function_exists("client_progress")){client_progress("*** **** FAILED **** ****",110);}
			
			if($this->debug){$this->events("SYSTEMID is null!!!$called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			return;
		}		
		
		
		
		$os=new os_system();
		$hostname=$unix->hostname_g();
		
		
		$post["uuid"]=$this->uuid;
		$post["hostname"]=$hostname;
		
		$post["username"]=$this->ArticaMetaUsername;
		$post["password"]=$this->ArticaMetaPassword;
		$post["system_adm"]=null;
		$post["system_password"]=null;
		
		if($ArticaLinkAutoconnect==1){
			$ldap=new clladp();
			$post["system_adm"]=$ldap->ldap_admin;
			$post["system_password"]=$ldap->ldap_password;
		}
		
		$post["version"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
		$post["memory"]=$os->realMemory();
		$array_load=sys_getloadavg();
		$post["load"]=$array_load[0];
		$post["CPU"]=$unix->CPU_NUMBER();
		
		
		$post["ALL_DISKS_STATUS"]=serialize($unix->ALL_DISKS_STATUS());
		
		if($users->POSTFIX_INSTALLED){
			$post["POSTFIX"]=1;
			$post["squidver"]=$unix->POSTFIX_VERSION();
		}
		
		if($users->SQUID_INSTALLED){
			$post["SQUID"]=1;
			$post["squidver"]=$unix->squid_version();
			$post["PROXYEMERG"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
			$post["UFDBARTICA"]=intval(@file_get_contents("/etc/artica-postfix/ufdbartica.txt"));
			$post["WINDOWSAD"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));
			$post["ADEMERG"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ActiveDirectoryEmergency"));
			$post["UFDB_ENABLED"]=$sock->EnableUfdbGuard_local();
			@unlink("/etc/artica-postfix/settings/Daemons/UfdbMetaClientVersion");
			@copy("/etc/artica-postfix/ufdbartica.txt", "/etc/artica-postfix/settings/Daemons/UfdbMetaClientVersion");
			@chmod("/etc/artica-postfix/settings/Daemons/UfdbMetaClientVersion", 0755);
		}
		
		
		$IDENT=urlencode(base64_encode(serialize($post)));
		
		
		
		if($GLOBALS["VERBOSE"]){echo "hostname:$hostname\n";}
		if($GLOBALS["VERBOSE"]){$post["VERBOSE"]=1;}
		$this->MAIN_URI="https://$this->ArticaMetaHostname:$this->ArticaMetaPort/artica.meta.listener.php?ident=$IDENT";
	}
	
	public function buildCurl($uri,$post=array()){
		
		if(substr($uri, 0,1)=="/"){
			$URI="$this->ArticaMetaHostname:$this->ArticaMetaPort/$uri";
			$URI=str_replace("//", "/", $URI);
			$URI="https://$URI";
			$this->events("Return $URI", __FUNCTION__,__FILE__,__LINE__);
			$curl=new ccurl($URI,false,null,$this->nochecksquid);
			return $curl;
		}
		
		if($this->ArticaMetaUseLocalProxy==1){$nochecksquid=false;}
		$URI=$this->MAIN_URI."&$uri";
		
		if(function_exists("client_progress")){client_progress("Final: $URI",25);}
		$this->LOG_URI=$URI;
		$curl=new ccurl($URI,false,null,$this->nochecksquid);
		if($GLOBALS["VERBOSE"]){echo "Building URI:\n$URI\n";}
		$curl->Timeout=$this->ArticaMetaTimeOut;
		if(count($post)==0){$curl->NoHTTP_POST=true;}
		$this->events("Return $URI", __FUNCTION__,__FILE__,__LINE__);
		return $curl;
	}
	
	
	public function SendArticaEventsToMeta(){
			if(isset($GLOBALS["PING"]["SendArticaEventsToMeta"])){return;}
			$GLOBALS["PING"]["SendArticaEventsToMeta"]=true;
			$BaseWorkDir="/home/artica-meta/events-notify-master";
			$unix=new unix();
			if(is_dir($BaseWorkDir)){
				if ($handle = opendir($BaseWorkDir)) {
					while (false !== ($filename = readdir($handle))) {
						if($filename=="."){continue;}
						if($filename==".."){continue;}
						$targetFile="$BaseWorkDir/$filename";
						@unlink($targetFile);
						}
					}
			}
		
		$sourcefile="/home/artica-meta/META_CLIENT_EVENTS.log";
		if(!is_file($sourcefile)){return;}	
		$filecompress="/home/artica-meta/META_CLIENT_EVENTS.gz";
		
		if(is_file($filecompress)){
			if(!$this->SendFile("$filecompress","META_CLIENT_EVENTS")){
				if($unix->file_time_min($filecompress)>240){@unlink($filecompress);}
				return;
			}
			return;
		}
		
		
		if(!$unix->compress($sourcefile, $filecompress)){
			@unlink($filecompress);
			return;
		}
		@unlink($sourcefile);
		if($this->SendFile("$filecompress","META_CLIENT_EVENTS")){@unlink($filecompress);}
		
			
	}
		
		
	public function SendHotSpotAccounts(){
		
		if(isset($GLOBALS["PING"]["SendHotSpotAccounts"])){return;}
		$GLOBALS["PING"]["SendHotSpotAccounts"]=true;
		$q=new mysql_squid_builder();
		$unix=new unix();
		if($q->COUNT_ROWS("hotspot_members_meta")==0){return;}
		$sql="SELECT * FROM hotspot_members_meta";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$ARRAY[$ligne["uid"]]=$ligne["creationtime"];
		}
		
		$DirTemp=$unix->TEMP_DIR();
		$FILENAME="$DirTemp/HOTSPOT_SESSSIONS";
		@file_put_contents($FILENAME, serialize($ARRAY));
		$filecompress=$FILENAME.".gz";
		if(!$unix->compress($FILENAME, $filecompress)){
			@unlink($FILENAME);
			@unlink($filecompress);
			return;
		}
		@unlink($FILENAME);
		if(!$this->SendFile("$filecompress","HOTSPOT_SESSSIONS")){
			@unlink($filecompress);
			return;
		}
		
		$q->QUERY_SQL("TRUNCATE TABLE hotspot_members_meta");
	}	
	
	
	public function SendArticaConfig(){
		if(isset($GLOBALS["PING"]["SendArticaConfig"])){return;}
		$GLOBALS["PING"]["SendArticaConfig"]=true;
		$unix=new unix();
		$sourceDir="/etc/artica-postfix/settings";
		if (!$handle = opendir("$sourceDir/Daemons")) {echo "Failed open $sourceDir/Daemons\n";return;}
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			
			$SourceFile="$sourceDir/Daemons/$filename";
			if(is_dir($SourceFile)){continue;}
			$data=trim(@file_get_contents($SourceFile));
			$BIG_ARRAY[$filename]=$data;
		
			
		}
		
		$filename=$unix->TEMP_DIR()."/ARTICA_DAEMONS";
		@file_put_contents($filename, serialize($BIG_ARRAY));
		$filecompress=$filename.".gz";
		if(!$unix->compress($filename, $filecompress)){
			@unlink($filename);
			@unlink($filecompress);
			return;
		}
		@unlink($filename);
		$this->SendFile("$filecompress","ARTICA_DAEMONS");
		@unlink($filecompress);
		$this->SEND_GLOBAL_CONFIG();
	}
	
	public function SendTempStats(){
		$dir="/usr/share/artica-postfix/ressources/interface-cache";
		$unix=new unix();
		$files=$unix->DirFiles($dir);
		while (list ($filename,$none) = each ($files)){
			$fullpath="$dir/$filename";
			$MAIN["filename"]=@file_get_contents($fullpath);
		}
		
		$filetemp=$unix->FILE_TEMP();
		$filecompress=$unix->FILE_TEMP().".gz";
		
		@file_put_contents($filetemp, serialize($MAIN));
		if(!$unix->compress($filetemp, $filecompress)){
			@unlink($filecompress);
			@unlink($filetemp);
			return;
		}
		
		$this->SendFile("$filecompress","INTERFACE_CACHE");
		@unlink($filecompress);
		@unlink($filetemp);
	}
	
	
	public function SendSquidPerfs(){
		if(isset($GLOBALS["PING"]["SendSquidPerfs"])){return;}
		$GLOBALS["PING"]["SendSquidPerfs"]=true;
		if(!is_file("/usr/share/artica-postfix/ressources/logs/web/TOTAL_CACHED")){return;}
		$unix=new unix();
		$filecompress=$unix->FILE_TEMP().".gz";
		if(!$unix->compress("/usr/share/artica-postfix/ressources/logs/web/TOTAL_CACHED", $filecompress)){
			@unlink($filecompress);
		}
		$this->SendFile("$filecompress","SQUID_PERFS");
		@unlink($filecompress);
		
	}
	
	
	public function SendSysAlerts(){
		if(isset($GLOBALS["PING"]["SendSysAlerts"])){return;}
		$GLOBALS["PING"]["SendSysAlerts"]=true;
		$unix=new unix();
	
		if(!is_file("/home/artica/metaclient/upload/SYS_ALERTS.gz")){
			$this->LAST_SYS_ALERTS();
		}
	
		if(!is_file("/home/artica/metaclient/upload/SYS_ALERTS.gz")){return;}
		if($this->SendFile("/home/artica/metaclient/upload/SYS_ALERTS.gz","SYS_ALERTS")){
			@unlink("/home/artica/metaclient/upload/SYS_ALERTS.gz");
		}
	}	
	
	
	public function SendPS(){
		if(isset($GLOBALS["PING"]["SENDPS"])){return;}
		$GLOBALS["PING"]["SENDPS"]=true;
		$unix=new unix();
		$ps=$unix->find_program("ps");
		$tmp_file=$unix->FILE_TEMP();
		$tmpdir=$unix->TEMP_DIR();
		system("$ps auxww >$tmp_file 2>&1");
		$tmp_fileC=$tmpdir."/PSAUX.gz";
		$unix->compress($tmp_file, $tmp_fileC);
		@unlink($tmp_file);
		$this->SendFile($tmp_fileC,"PSAUX");
		@unlink($tmp_fileC);
	}
	
	public function SendPhileSight(){
		if(isset($GLOBALS["PING"]["SendPhileSight"])){return;}
		$GLOBALS["PING"]["SendPhileSight"]=true;
		
		if(!is_file("/home/artica/metaclient/upload/philesight.tgz")){
			$this->events("/home/artica/metaclient/upload/philesight.tgz no such file", __FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		if(!$this->SendFile("/home/artica/metaclient/upload/philesight.tgz","PHILESIGHT")){
			$this->events("/home/artica/metaclient/upload/philesight.tgz unable to upload", __FUNCTION__,__FILE__,__LINE__);
			return;
		}
		@unlink("/home/artica/metaclient/upload/philesight.tgz");
	}
	
	
	
	
	public function SendFile($file_path,$command=null){
		$curl=new ccurl($this->MAIN_URI,false,null,$this->nochecksquid);
		$curl->x_www_form_urlencoded=false;
		$curl->Timeout=$this->ArticaMetaTimeOut;
		$ARRAY=array();
		if($command<>null){
			$ARRAY["PUSH_FILE_CMD"]=$command;
		}
		
		
		$this->events("POST FILE $file_path CMD=$command Timeout:{$this->ArticaMetaTimeOut}Mn", __FUNCTION__,__FILE__,__LINE__);
		if(!$curl->postFile(basename($file_path),$file_path,$ARRAY )){
			meta_admin_mysql(1, "Unable to post ".basename($file_path), $curl->error);
			return false;
		}
		return true;
	}
	
	public function SendSyslog($file_path,$command=null){
		$curl=new ccurl($this->MAIN_URI,false,null,$this->nochecksquid);
		$curl->x_www_form_urlencoded=false;
		$ARRAY=array();
		
			$ARRAY["PUSH_FILE_CMD"]="SYSLOG";
		
	
		if(!$curl->postFile(basename($file_path),$file_path,$ARRAY )){
			meta_admin_mysql(1, "Unable to post ".basename($file_path), $curl->error);
			return false;
		}
		
		if(!preg_match("#<ARTICAMETA>OK</ARTICAMETA>#is",$curl->data)){
			meta_admin_mysql(1, "Communication failed with Meta server", $curl->error);
			return false;
		}
		
		return true;
		
	}
	
	public function send_personal_categories(){
		
		$unix=new unix();
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM personal_categories");
		$MAIN=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			$category=$ligne["category"];
			$tablename=$q->cat_totablename($category);
			$results2=$q->QUERY_SQL("SELECT pattern FROM $tablename WHERE `enabled`=1");
			if(mysql_num_rows($results2)==0){continue;}
			while ($ligne2 = mysql_fetch_assoc($results2)) {
				$pattern=$ligne2["pattern"];
				$zmd5=md5("$category{$ligne2["pattern"]}");
				$sqlline="('$category','$pattern','$zmd5')";
				$MAIN[$category][]=$sqlline;
				
			}
		}
		if(count($MAIN)==0){return;}
		$tempfile=$unix->FILE_TEMP();
		$tempdir=$unix->TEMP_DIR();
		@file_put_contents($tempfile, serialize($MAIN));
		if(!$unix->compress($tempfile, "$tempdir/PROXY_CATEGORIES.gz")){
			@unlink($tempfile);
			return;
		}
		@unlink($tempfile);
		$this->SendFile("$tempdir/PROXY_CATEGORIES.gz","PROXY_CATEGORIES");
		@unlink("$tempdir/PROXY_CATEGORIES.gz");
	}

	
	private function SEND_GLOBAL_CONFIG(){
		$users=new usersMenus();
		$unix=new unix();
		$tempfile=$unix->FILE_TEMP();
		
		
		if($users->SQUID_INSTALLED){
			$q=new mysql_squid_builder();
			$q->dump_table_to_file("proxy_ports", "$tempfile","uuid","VARCHAR(90)",$unix->GetUniqueID());
			$unix->compress($tempfile, dirname($tempfile)."/PROXY_PORTS.gz");
			$this->SendFile(dirname($tempfile)."/PROXY_PORTS.gz","PROXY_PORTS");
			@unlink($tempfile);
		}
		
		
		$tempfile=$unix->FILE_TEMP();
		$q=new mysql();
		$q2=new class_mysql_dump("artica_backup", $q->mysql_connection,$tempfile);
		$q2->set_OnlyTableName("nics");
		$q2->add_fields_values("uuid",$this->uuid,"VARCHAR(90)");
		$q2->doDump();
		$unix->compress($tempfile, dirname($tempfile)."/TABLE_NICS.gz");
		$this->SendFile(dirname($tempfile)."/TABLE_NICS.gz","TABLE_NICS");
		@unlink($tempfile);
		
	}
	
	
	
	private function LAST_SYS_ALERTS(){
		$unix=new unix();
		if(isset($GLOBALS["LAST_SYS_ALERTS_PERFORMED"])){return;}
		$GLOBALS["LAST_SYS_ALERTS_PERFORMED"]=true;
		$gz_file="/home/artica/metaclient/upload/SYS_ALERTS.gz";
		if(is_file($gz_file)){return;}
		@mkdir("/home/artica/metaclient/upload",0755,true);
		
		
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM sys_alerts WHERE sended=0 LIMIT 0,5000","artica_events");
		if(!$q->ok){
			$this->events($q->mysql_error, __FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		$f=array();
		
		$linesNumber=mysql_num_rows($results);
		$this->events("$linesNumber event(s) to push...", __FUNCTION__,__FILE__,__LINE__);
		if($linesNumber==0){return; }
		
		$prefix="INSERT IGNORE INTO sys_alerts (`zmd5`,`zDate`,`sended`,`load`,`uuid`,`content`) VALUES";
		
		if($GLOBALS["VERBOSE"]){echo "**********\sys_alerts - > ". mysql_num_rows($results)."\n**********\n";}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["content"]=mysql_escape_string2($ligne["content"]);
			$ligne["subject"]=mysql_escape_string2($ligne["subject"]);
			$zmd5=$ligne["zmd5"];
			$uuid=$ligne["uuid"];
			$time=$ligne["zDate"];
			$load=$ligne["load"];
			$content=mysql_escape_string2($ligne["content"]);
			$f[]="('$zmd5','$time','1','$load','$uuid','$content')";
				
		}
		
		if(count($f)>0){
			@file_put_contents("/home/artica/metaclient/upload/SYS_ALERTS.sql", $prefix." ".@implode(",", $f));
			if(!is_file("/home/artica/metaclient/upload/SYS_ALERTS.sql")){return;}
			$unix->compress("/home/artica/metaclient/upload/SYS_ALERTS.sql", $gz_file);
			if(!is_file("/home/artica/metaclient/upload/SYS_ALERTS.gz")){return;}
				
			$q->QUERY_SQL("UPDATE sys_alerts SET sended=1 WHERE sended=0 LIMIT 5000","artica_events");
			if(!$q->ok){echo $q->mysql_error."\n\n";}
		}
		
	}
	
	
	private function LAST_META_EVENTS(){
		$unix=new unix();
		if(isset($GLOBALS["LAST_META_EVENTS_PERFORMED"])){return;}
		$GLOBALS["LAST_META_EVENTS_PERFORMED"]=true;
		if(is_file("/home/artica/metaclient/upload/META_EVENTS.gz")){return;}
		@mkdir("/home/artica/metaclient/upload",0755,true);
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM meta_admin_mysql WHERE sended=0 LIMIT 0,5000","artica_events");
		if(!$q->ok){
			$this->events($q->mysql_error, __FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		$f=array();
		
		
		$linesNumber=mysql_num_rows($results);
		$this->events("$linesNumber event(s) to push...", __FUNCTION__,__FILE__,__LINE__);
		if($linesNumber==0){return; }
		
		$prefix="INSERT IGNORE INTO `meta_admin_mysql` (`zmd5`,`uuid`,`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES";
		
		if($GLOBALS["VERBOSE"]){echo "**********\nmeta_admin_mysql - > ". mysql_num_rows($results)."\n**********\n";}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["content"]=mysql_escape_string2($ligne["content"]);
			$ligne["subject"]=mysql_escape_string2($ligne["subject"]);
			$zmd5=$ligne["zmd5"];
			$uuid=$ligne["uuid"];
			$f[]="('$zmd5','$uuid','{$ligne["zDate"]}','{$ligne["content"]}','{$ligne["subject"]}','{$ligne["function"]}','{$ligne["filename"]}','{$ligne["line"]}','{$ligne["severity"]}','{$ligne["hostname"]}')";
			
		}
		
		if(count($f)>0){
			@file_put_contents("/home/artica/metaclient/upload/META_EVENTS.sql", $prefix." ".@implode(",", $f));
			if(!is_file("/home/artica/metaclient/upload/META_EVENTS.sql")){return;}
			$unix->compress("/home/artica/metaclient/upload/META_EVENTS.sql", "/home/artica/metaclient/upload/META_EVENTS.gz");
			if(!is_file("/home/artica/metaclient/upload/META_EVENTS.gz")){return;}
			
			$q->QUERY_SQL("UPDATE meta_admin_mysql SET sended=1 WHERE sended=0 LIMIT 5000","artica_events");
			return $prefix." ".@implode(",", $f);
			
			if(!$q->ok){echo $q->mysql_error."\n\n";}
		}
		
	}
	
	private function get_meta_infos($uri){
		if($GLOBALS["OUTPUT"]){$uri=$uri."&output=yes";}
		if(function_exists("client_progress")){client_progress("Query: $uri",25);}
		$curl=$this->buildCurl($uri);
		
		if(!$curl->get()){
			if(function_exists("client_progress")){client_progress("{$uri} {failed}",30);}
			if($GLOBALS["OUTPUT"]){echo "$uri failed $curl->error\n";}else{
				meta_admin_mysql(1, "$uri failed", $curl->error);
			}
			return false;
		}
		
		if(!preg_match("#<ARTICA_META>(.+?)</ARTICA_META>#is", $curl->data,$re)){
			$this->events("************ <$curl->data> ************", __FUNCTION__,__FILE__,__LINE__);
			if(function_exists("client_progress")){client_progress("Communication failed {failed}",30);}
			if($GLOBALS["OUTPUT"]){
				echo "************ <$curl->data> ************\nNo match <ARTICA_META>\n";}else{
				meta_admin_mysql(1, "$uri No match <ARTICA_META>", $curl->error);
			}
			return false;
		}
		
		$data=trim($re[1]);
		if(function_exists("client_progress")){client_progress("Communication {success}",30);}
		return $data;
		
	}
	
	
	private function get_meta_file($uri,$targetfilename,$noerror=false){
		$curl=$this->buildCurl($uri);
		$this->events("Query: $curl->uri", __FUNCTION__,__FILE__,__LINE__);
		if(!$curl->GetFile($targetfilename)){
			meta_admin_mysql(1, "$uri failed with error  $curl->error", @implode("\n",$curl->errors));
			while (list ($num, $val) = each ($curl->errors)){$this->events("$val", __FUNCTION__,__FILE__,__LINE__);}
			return false;
		}
		
		$size=@filesize($targetfilename);
		if($size==0){
			$this->events("$curl->error $targetfilename ({0}Bytes)", __FUNCTION__,__FILE__,__LINE__);
			if(!$noerror){meta_admin_mysql(1, "$uri failed 0 bytes $curl->error", @implode("\n",$curl->errors));}
			@unlink($targetfilename);
			return false;
		}
		$this->events("Downloaded file $targetfilename ({$size}Bytes)", __FUNCTION__,__FILE__,__LINE__);
		return true;
	
	}	
	
	
	public function retreive_dump_sql($expectedmd5=null){
		$unix=new unix();
		if($expectedmd5==null){return false;}
		$targetfilename=$unix->FILE_TEMP().".gz";
		$tmp_path=$unix->TEMP_DIR();
		if(!$this->get_meta_file("/meta-updates/dump.sql.gz",$targetfilename)){
			$this->events("/meta-updates/dump.sql.gz, failed to download", __FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		$this->events("/meta-updates/dump.sql.gz, success to download", __FUNCTION__,__FILE__,__LINE__);
		
		if(!$unix->uncompress($targetfilename, "$tmp_path/dump.sql")){
			$this->events("/meta-updates/dump.sql.gz, failed to uncompress", __FUNCTION__,__FILE__,__LINE__);
			@unlink($targetfilename);
			return false;
		}
		
		$md5_file=md5_file($targetfilename);
		$this->events("/meta-updates/dump.sql.gz, MD5 = $md5_file / expected=$expectedmd5", __FUNCTION__,__FILE__,__LINE__);
		@unlink($targetfilename);
		if($expectedmd5<>$md5_file){
			$this->events("/meta-updates/dump.sql.gz, MD5 not matches.", __FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		$q=new mysql();
		$q->CREATE_DATABASE("metaclient");
		$pattern_cmdline=$q->MYSQL_CMDLINES;
		$mysql=$unix->find_program("mysql");
		
		
		$cmdline="$mysql $pattern_cmdline metaclient < \"$tmp_path/dump.sql\"";
		$this->events($cmdline, __FUNCTION__,__FILE__,__LINE__);
		system($cmdline);
		@unlink("$tmp_path/dump.sql");
		
		
		return true;
		
	}
	
	
	public function ping($NocheckOrder=FALSE){
		
		if(function_exists("client_progress")){client_progress("Communicate...",25);}
		$data=$this->get_meta_infos("ping=yes");
		if(!$data){
			if(function_exists("client_progress")){client_progress("No data return from master server...",25);}
			$this->events("No data returned from master server", __FUNCTION__,__FILE__,__LINE__);
			$this->ping_error="No data returned from master server";
			return;
		}
		
		
		
		$array=unserialize(base64_decode($data));
		$sock=new sockets();
		
		if(isset($array["ArticaMetaPooling"])){
			if(function_exists("client_progress")){client_progress("ArticaMetaPooling:{$array["ArticaMetaPooling"]}",30);}
			if($GLOBALS["VERBOSE"]){echo "Server sends ArticaMetaPooling:{$array["ArticaMetaPooling"]}\n";}
			$sock->SET_INFO("ArticaMetaPooling", $array["ArticaMetaPooling"]);
		}else{
			$this->events("Failed...to match ArticaMetaPooling", __FUNCTION__,__FILE__,__LINE__);
		}
		
		if(isset($array["ArticaMetaKillProcess"])){
			if(function_exists("client_progress")){client_progress("ArticaMetaKillProcess:{$array["ArticaMetaKillProcess"]}",30);}
			if($GLOBALS["VERBOSE"]){echo "Server sends ArticaMetaKillProcess:{$array["ArticaMetaKillProcess"]}\n";}
			$sock->SET_INFO("ArticaMetaKillProcess", $array["ArticaMetaKillProcess"]);
		}
		if(isset($array["ArticaLinkAutoconnect"])){
			if(function_exists("client_progress")){client_progress("ArticaLinkAutoconnect:{$array["ArticaLinkAutoconnect"]}",30);}
			if($GLOBALS["VERBOSE"]){echo "Server sends ArticaLinkAutoconnect:{$array["ArticaLinkAutoconnect"]}\n";}
			$sock->SET_INFO("ArticaLinkAutoconnect", $array["ArticaLinkAutoconnect"]);
		}
		
		if(isset($array["ArticaMetaDumpSQLMD5"])){
			if(function_exists("client_progress")){client_progress("ArticaMetaDumpSQLMD5:{$array["ArticaMetaDumpSQLMD5"]}",31);}
			if($GLOBALS["VERBOSE"]){echo "Server sends ArticaMetaDumpSQLMD5:{$array["ArticaMetaDumpSQLMD5"]}\n";}
			$sock->SET_INFO("ArticaMetaDumpSQLMD5", $array["ArticaMetaDumpSQLMD5"]);
		}else{
			
		}
		
		$this->events("Order NocheckOrder=$NocheckOrder", __FUNCTION__,__FILE__,__LINE__);
		if(!$NocheckOrder){
			if(isset($array["ORDERS"])){
				$this->events("ORDERS is an array...", __FUNCTION__,__FILE__,__LINE__);
				$ORDERS=unserialize(base64_decode($array["ORDERS"]));
				if(count($ORDERS)>0){
					$this->events("Checking ".count($ORDERS), __FUNCTION__,__FILE__,__LINE__);
					$this->parse_orders($ORDERS);}
			}else{
				$this->events("No order defined \$array[\"ORDERS\"] not found...", __FUNCTION__,__FILE__,__LINE__);
			}
			
			if(isset($array["POLICIES"])){
				if(count($array["POLICIES"])>0){
					$this->events("Checking ".count($array["POLICIES"])." policies", __FUNCTION__,__FILE__,__LINE__);
					while (list ($md5_policy, $val) = each ($array["POLICIES"])){
						$this->APPLY_POLICY($md5_policy);
					}
				}
				
			}
			
			if(isset($array["SYSTEM_SCHEDULES"])){$this->APPLY_SCHEDULES($array["SYSTEM_SCHEDULES"]);}else{$this->events("No schedules defined...", __FUNCTION__,__FILE__,__LINE__); }
			if(isset($array["SYSTEM_CLONE"])){$this->APPLY_CLONE($array["SYSTEM_CLONE"]);}else{$this->events("No Cloning defined...", __FUNCTION__,__FILE__,__LINE__);}
			
		
			
			
		}
		
		if(function_exists("client_progress")){client_progress("Send global status",40);}
		$this->events("->global_status()", __FUNCTION__,__FILE__,__LINE__);
		$this->global_status();
		if(function_exists("client_progress")){client_progress("Send Processes",45);}
		$this->SendPS();
		if(function_exists("client_progress")){client_progress("Send Directories",50);}
		$this->events("SendPhileSight()", __FUNCTION__,__FILE__,__LINE__);
		$this->SendPhileSight();
		if(function_exists("client_progress")){client_progress("Send system alerts",55);}
		$this->SendSysAlerts();
		if(function_exists("client_progress")){client_progress("Send local settings",60);}
		$this->SendArticaConfig();
		if(function_exists("client_progress")){client_progress("Send Proxy performances",60);}
		$this->SendSquidPerfs();
		if(function_exists("client_progress")){client_progress("Send Events",60);}
		$this->SendArticaEventsToMeta();
		if(function_exists("client_progress")){client_progress("Send HotSpot Members",60);}
		$this->SendHotSpotAccounts();
		if(function_exists("client_progress")){client_progress("Send Personal Categories",60);}
		$this->send_personal_categories();
		if(function_exists("client_progress")){client_progress("Get Personal Categories",60);}
		$this->ACTION_GET_CATEGORIES();
		
		
		return true;
	}
	
	private function parse_orders($ORDERS){
		$meta_order=new meta_orders();
		while (list ($orderid, $ARRAY) = each ($ORDERS) ){
			$this->events("Checking order id: $orderid, action={$ARRAY["SUBJECT"]} CONTENT={$ARRAY["CONTENT"]}", __FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Checking order id: $orderid, action={$ARRAY["SUBJECT"]} CONTENT={$ARRAY["CONTENT"]}\n";}
			
			switch ($ARRAY["SUBJECT"]) {
				case "UPDATE_ARTICA":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_UPDATE_ARTICA($orderid,$CONTENT);
					break;
						
				case "PROXY_PARAMS":
					$this->ACTION_PROXY_PARAMS($orderid);
					break;
				
				case "CHANGE_HOSTNAME":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_CHANGE_HOSTNAME($orderid,$CONTENT);
					break;
					
				case "ROOT_PASSWORD":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_ROOT_PASSWORD($orderid,$CONTENT);
					break;
								
				case "MANAGER_CREDS":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_MANAGER_CREDS($orderid,$CONTENT);
					break;
					
				case "POLICY":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_POLICY($orderid,$CONTENT);
					break;
				
				case "REBOOT":
					$this->ACTION_REBOOT($orderid);
					break;
				case "SERVICE_CMD":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_SERVICE_CMD($orderid,$CONTENT);
					break;
					
				case "UPDATE_HOST":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_UPDATE_HOST($orderid,$CONTENT);
					break;
					
				case "INSTALL_SOFTWARE":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_INSTALL_SOFT($orderid,$CONTENT);
					break;
					
				case "RESTART_PROXY":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_RESTART_PROXY($orderid,$CONTENT);
					break;
					
				case "PROXY_DISABLE_URGENCY":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_PROXY_DISABLE_URGENCY($orderid,$CONTENT);
					
				case "PROXY_ENABLE_URGENCY":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_PROXY_ENABLE_URGENCY($orderid,$CONTENT);					
					
				case "CLEAN_PROXY_CACHES":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->CLEAN_PROXY_CACHES($orderid,$CONTENT);
					break;
					
				case "REINDEX_PROXY_CACHES":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->REINDEX_PROXY_CACHES($orderid,$CONTENT);
					break;	

				case "RELOAD_PROXY":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_RELOAD_PROXY($orderid,$CONTENT);
					break;

				case "PHILESIGHT":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_PHILESIGHT($orderid,$CONTENT);
					break;

					
				case "SNAPSHOT":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_SNAPSHOT($orderid,$CONTENT);
					break;
					
				case "SNAPSHOT_RESTORE":
						$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
						$this->ACTION_SNAPSHOT_RESTORE($orderid,$CONTENT);
					break;
					
					
				case "SQUID_TRANSPARENT_WBL":
						$this->events("PROCESSING /SQUID_TRANSPARENT_WBL/", __FUNCTION__,__FILE__,__LINE__);
						$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
						$meta_order->SQUID_TRANSPARENT_WBL($orderid,$CONTENT);
					break;
					
					
				case "RECONNECT_AD":
					$CONTENT=unserialize(base64_decode($ARRAY["CONTENT"]));
					$this->ACTION_RECONNECT_AD($orderid,$CONTENT);
					break;	

					
				case "PROXY_APPLY_PARAMS":
					$meta_order->ACTION_PROXY_APPLY_PARAMS($orderid,unserialize(base64_decode($ARRAY["CONTENT"])));
					break;
					
				case "URGENCY_NOAD":
					$meta_order->ACTION_DISABLE_AD_EMERGENCY($orderid,unserialize(base64_decode($ARRAY["CONTENT"])));
					break;
				case "URGENCY_AD":
					$meta_order->ACTION_ENABLE_AD_EMERGENCY($orderid,unserialize(base64_decode($ARRAY["CONTENT"])));
					break;					
					
				
				default:
					;
				break;
			}
			
		}
		
		
		
	}
	
	private function global_status(){
		if(isset($GLOBALS["global_status_executed"])){return;}
		$GLOBALS["global_status_executed"]=true;
		$unix=new unix();
		$temp_dir=$unix->TEMP_DIR()."/artica-meta-client-upload";
		@mkdir($temp_dir,0755,true);
		@copy("/usr/share/artica-postfix/ressources/logs/global.status.ini","$temp_dir/global.status.ini");
		@copy("/usr/share/artica-postfix/ressources/logs/web/cpustats.db","$temp_dir/cpustats.db");
		@copy("/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG.db","$temp_dir/INTERFACE_LOAD_AVG.db");
		@copy("/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG2.db","$temp_dir/INTERFACE_LOAD_AVG2.db");
		@chdir($temp_dir);
		$this->DUMP_SQUID_WATCHDOG($temp_dir);
		$this->DUMP_SYSTEM_COMPUTERS($temp_dir);
		$tar=$unix->find_program("tar");
		shell_exec("$tar -czf $temp_dir/status.tgz *");
		$this->SendFile("$temp_dir/status.tgz");
		if(is_dir($temp_dir)){
			$rm=$unix->find_program("rm");
			shell_exec("$rm -rf $temp_dir");
		}
		@chdir("/root");
	}
	
	
	
	public function removeorder($orderid,$NoPing=false){
		$curl=$this->buildCurl("removeorder=$orderid");
		if(!$curl->get()){
			$this->events("Failed with error $curl->error", __FUNCTION__,__LINE__);
			return false;
		}
		
		
		if(!preg_match("#<ARTICA_META>SUCCESS#is", $curl->data)){
			$this->events("FATAL pattern `SUCCESS` was not found ..", __FUNCTION__,__LINE__);
			$this->events("************* START DUMP ****************");
			$this->events("$curl->data");
			$this->events("************* END ****************");
			return false;
		}
		
		$this->events("Remove order id $orderid success", __FUNCTION__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo $curl->data."\n";}
		return true;
		
	}
	
	private function ACTION_PROXY_PARAMS($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_PROXY_PARAMS"][$orderid])){return;}
		$GLOBALS["ACTION_PROXY_PARAMS"][$orderid]=true;
		$uri="/meta-updates/proxy/squid_whitelists.db";
		
		$curl=$this->buildCurl($uri);
		$filetemp=$unix->FILE_TEMP().".db";
		if($GLOBALS["VERBOSE"]){echo "Downloading $uri -> $filetemp\n";}
		if(!$curl->GetFile($filetemp)){
			meta_admin_mysql(0, "Unable to download $uri $curl->error", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
		}
		$q=new mysql_meta();
		$q->CheckTables();
		$q->QUERY_SQL("TRUNCATE TABLE `squid_whitelists`");
		$data=base64_decode(@file_get_contents($filetemp));
		@unlink($filetemp);
		if(strlen($data)>10){
			$q->QUERY_SQL($data); 
			if(!$q->ok){meta_admin_mysql(0, "Unable to replicate global whitelist $q->mysql_error - $data", null,__FILE__,__LINE__);}
		
		}
		
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		meta_admin_mysql(2, "Replicate global whitelist ".$q->COUNT_ROWS("squid_whitelists")." items", null,__FILE__,__LINE__);
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-whitelists >/dev/null 2>&1 &");
		shell_exec($cmd);
		$this->removeorder($orderid);
	}
	
	private function ACTION_UPDATE_HOST($orderid,$array){
		if(isset($GLOBALS["ACTION_UPDATE_HOST"][$orderid])){return;}
		$GLOBALS["ACTION_UPDATE_HOST"][$orderid]=true;
		include_once(dirname(__FILE__)."/class.computers.inc");
		$cp=new computers();
		$uid=$cp->ComputerIDFromMAC($array["MAC"]);
		$cp=new computers($uid);
		$cp->ComputerOS=$array["OSNAME"];
		$cp->ComputerIP=$array["IPADDR"];
		$cp->ComputerRealName=$array["hostname"];
		$cp->Add();
		
		
		if($array["username"]<>null){
			$q=new mysql_squid_builder();
			$q->ADD_UID($array["IPADDR"],$array["MAC"],$array["username"]);
		}
		meta_admin_mysql(2, "Update computer {$array["hostname"]}/{$array["IPADDR"]}/{$array["MAC"]}", null,__FILE__,__LINE__);
		$this->removeorder($orderid);
		
	}
	
	function ACTION_SNAPSHOT_RESTORE_EVENTS($text,$function,$file,$line){
		$unix=new unix();
		$unix->events($text,"/var/log/artica-backup.log",false,$function,$file,$line);
	
	
	}
	
	private function ACTION_SNAPSHOT_RESTORE($orderid,$array){
		if(isset($GLOBALS["ACTION_SNAPSHOT_RESTORE"][$orderid])){return;}
		$GLOBALS["ACTION_SNAPSHOT_RESTORE"][$orderid]=true;
		$unix=new unix();
		
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_SNAPSHOT_RESTORE ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		if(!isset($array["ZMD5"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_SNAPSHOT_RESTORE ( ZMD5 expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$results=$this->ACTION_SNAPSHOT_RETREIVE($array["ZMD5"]);
		$this->removeorder($orderid);
		return $results;
	}
	
	
	private function ACTION_SNAPSHOT_RETREIVE($zMD5=null,$target_filename=null){
		if($zMD5==null){return;}
		$cmdadd=null;
		$unix=new unix();
		
		$nohup=$unix->find_program("nohup");
		$php5=$unix->LOCATE_PHP5_BIN();
		
		
		$filename=$unix->FILE_TEMP().".tar.gz";
		if($target_filename<>null){
			$cmdadd=" --not-remove";
			$filename=$target_filename;
		}
		@mkdir(dirname($filename),0755,true);
		$this->ACTION_SNAPSHOT_RESTORE_EVENTS("Downloading snapshot {$zMD5}", __FUNCTION__,__FILE__,__LINE__);
		$this->events("Downloading snapshot {$zMD5}", __FUNCTION__,__FILE__,__LINE__);
		if(!$this->get_meta_file("snapshot={$zMD5}",$filename)){
			meta_admin_mysql(0, "Failed to download snapshot {$zMD5} to $filename", null,__FILE__,__LINE__);
			return false;
		}
		$this->events("{$zMD5} downloaded - success..", __FUNCTION__,__FILE__,__LINE__);
		$this->ACTION_SNAPSHOT_RESTORE_EVENTS("{$zMD5} downloaded - success..", __FUNCTION__,__FILE__,__LINE__);
		$this->events("Execute restoring snapshot ID {$zMD5}", __FUNCTION__,__FILE__,__LINE__);
		$this->ACTION_SNAPSHOT_RESTORE_EVENTS("Execute restoring snapshot ID {$zMD5}", __FUNCTION__,__FILE__,__LINE__);
		meta_admin_mysql(2, "Execute restoring snapshot ID {$zMD5}", null,__FILE__,__LINE__);
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-file $filename --meta-ping{$cmdadd} >/dev/null 2>&1 &");
		$this->ACTION_SNAPSHOT_RESTORE_EVENTS($cmd, __FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		return true;
	}
	
	
	private function ACTION_SERVICE_CMD($orderid,$array){
		if(isset($GLOBALS["ACTION_SERVICE_CMD"][$orderid])){return;}
		$GLOBALS["ACTION_SERVICE_CMD"][$orderid]=true;
		$unix=new unix();
		
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_SERVICE_CMD ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		while (list ($key, $value) = each ($array) ){
			$fz[]="$key = $value";
		}
		
		$logstxt=@implode("; ", $fz);
		$action=$array["action"];
		$cmdline=$array["cmdline"];
		if($action==null){
			meta_admin_mysql(0, "Unable to understand order ACTION_SERVICE_CMD ( action expected $logstxt)", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		if($cmdline==null){
			meta_admin_mysql(0, "Unable to understand order ACTION_SERVICE_CMD ( cmdline expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}		
		
		$nohup=$unix->find_program("nohup");
		
		if(strpos(" $cmdline", "/")>0){
			shell_exec("$nohup $cmdline $action >/dev/null 2>&1 &");
			meta_admin_mysql(2, "$cmdline with action $action performed", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
		}
		shell_exec("$nohup /etc/init.d/artica-postfix $action $cmdline >/dev/null 2>&1 &");
		meta_admin_mysql(2, "artica-postfix $action $cmdline  performed", null,__FILE__,__LINE__);
		$this->removeorder($orderid);
		return;
		
	}
	
	private function ACTION_CHANGE_HOSTNAME($orderid,$array){
		if(isset($GLOBALS["ACTION_CHANGE_HOSTNAME"][$orderid])){return;}
		$GLOBALS["ACTION_CHANGE_HOSTNAME"][$orderid]=true;
		$unix=new unix();
		
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_CHANGE_HOSTNAME ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		if(!isset($array["VALUE"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_CHANGE_HOSTNAME ( no hostname )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$unix=new unix();
		$unix->CHANGE_HOSTNAME($array["VALUE"]);
		meta_admin_mysql(2, "Success change hostname to {$array["VALUE"]}", null,__FILE__,__LINE__);
		$this->removeorder($orderid);
		return true;
		
	}
	
	private function ACTION_POLICY($orderid,$array){
		if(isset($GLOBALS["ACTION_POLICY"][$orderid])){return;}
		$GLOBALS["ACTION_POLICY"][$orderid]=true;
		$unix=new unix();
		$this->events("Checking ACTION_POLICY:$orderid", "ACTION_POLICY",__FILE__,__LINE__);
		
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_POLICY ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		if(!isset($array["VALUE"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_POLICY ( no value )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$md5_policy=$array["VALUE"];
		$this->events("Retreive Policy $md5_policy", __FUNCTION__,__FILE__,__LINE__);
		
		if(!$this->APPLY_POLICY($md5_policy)){
			meta_admin_mysql(0, "Failed to apply policy $md5_policy", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
			
		}
		
		
		
		$this->removeorder($orderid);
		
		
		//this->)
		
	}
	
	
	private function APPLY_CLONE($md5file=null){
		if(isset($GLOBALS["APPLY_CLONE"][$md5file])){return;}
		if($md5file==null){return;}
		$md5file_local=null;
		$filename="/home/artica-meta/cloning/snapshot.tar.gz";
		if(!is_file($filename)){
			$this->events("$filename no such file...", __FUNCTION__,__LINE__);
			@file_put_contents("/etc/artica-postfix/meta-clone.md5", null);
		}
		if(is_file("/etc/artica-postfix/meta-clone.md5")){
			$md5file_local=@file_get_contents("/etc/artica-postfix/meta-clone.md5");
		}
		$this->events("REMOTE MD5 = $md5file", __FUNCTION__,__LINE__);
		$this->events("LOCAL  MD5 = $md5file_local", __FUNCTION__,__LINE__);
		if($md5file==$md5file_local){return;}
		if(is_file($filename)){@unlink($filename);}
		if(!$this->ACTION_SNAPSHOT_RETREIVE($md5file,$filename)){
			@unlink($filename);
			$this->events(" - - FAILED TO RESTORE SNAPSHOT - -", __FUNCTION__,__LINE__);
			return false;
		}
		$newMD5=md5_file($filename);
		$this->events("meta-clone.md5 = $filename = $newMD5", __FUNCTION__,__LINE__);
		@file_put_contents("/etc/artica-postfix/meta-clone.md5", $newMD5);
		
	}
	
	
	private function APPLY_SCHEDULES($md5file=null){
		if(isset($GLOBALS["APPLY_SCHEDULES"][$md5file])){return;}
		
		$GLOBALS["APPLY_SCHEDULES"][$md5file]=true;
		$filename="/etc/artica-postfix/meta-schedules.gz";
		$md5file_local=null;
		
		if($md5file==null){
			if(is_file($filename)){
				$q=new mysql();
				$q->QUERY_SQL("TRUNCATE TABLE system_meta_schedules","artica_backup");
				@unlink("/etc/artica-postfix/meta-schedules.gz");
				
			}
			return;
		}
		$this->events("Remote MD5 = $md5file", __FUNCTION__,__LINE__);
		if(is_file($filename)){$md5file_local=md5_file($filename); }
		$q=new mysql();
		
		if($md5file==$md5file_local){
			if($q->COUNT_ROWS("system_meta_schedules", "artica_backup")>0){
			return;
			}
		}
		
		
		if(!$this->get_meta_file("system-schedules=$this->uuid",$filename)){
			meta_admin_mysql(0, "Failed to download schedules.gz", null,__FILE__,__LINE__);
			return false;
		}
		
		$this->events("$filename: Size:".@filesize($filename), __FUNCTION__,__LINE__);
		
		$unix=new unix();
		$tmpfile=$unix->FILE_TEMP();
		if(!$unix->uncompress($filename, $tmpfile)){
			@unlink($tmpfile);
			@unlink($filename);
			$this->events("$filename: Uncompress failed {$GLOBALS["UNCOMPRESSLOGS"]}", __FUNCTION__,__LINE__);
			return;
		}
		
		$array=unserialize(@file_get_contents($tmpfile));
		@unlink($tmpfile);
		if(!is_array($array)){@unlink($filename);}
		
		

		$q->QUERY_SQL("TRUNCATE TABLE system_meta_schedules","artica_backup");
		if(isset($array["REMOVE"])){return;}
		
		
		while (list ($md5, $line) = each ($array) ){
			$TimeText=$line["TimeText"];
			$TimeDescription=mysql_escape_string2($line["TimeDescription"]);
			$TaskType=$line["TaskType"];
			$f[]="('$TimeText','$TimeDescription','$TaskType')";
		}
		
		if(count($f)>0){

			$q->QUERY_SQL("INSERT IGNORE INTO `system_meta_schedules` (`TimeText`,`TimeDescription` ,`TaskType`) VALUES" .@implode(",", $f),"artica_backup");
			if(!$q->ok){
				$this->events("$q->mysql_error", __FUNCTION__,__LINE__);
				@unlink($filename);
				return;
			}
		}
		
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.schedules.php");
		
	}
	
	private function APPLY_POLICY($md5_policy){
		$unix=new unix();
		if(isset($GLOBALS["APPLY_POLICY"][$md5_policy])){return;}
		$GLOBALS["APPLY_POLICY"][$md5_policy]=true;
		$data=$this->get_meta_infos("policy=$md5_policy");
		if(!$data){
			$this->events("Retreive Policy failed", __FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		$ARRAY=(unserialize(base64_decode($data)));
		
		if(!is_array($ARRAY)){
			meta_admin_mysql(0, "Unable to understand order ACTION_POLICY ( Policy array expected )", null,__FILE__,__LINE__);
			return false;
		}
		
		$policy_name=$ARRAY["policy_name"];
		$policy_type=$ARRAY["policy_type"];
		$policy_content=$ARRAY["policy_content"];
		$this->events("Policy $policy_name Type:$policy_type ".count($policy_content)." parameters", __FUNCTION__,__FILE__,__LINE__);
		
		$sock=new sockets();
		while (list ($key, $value) = each ($policy_content) ){
			$this->events("Apply policy token $key", __FUNCTION__,__FILE__,__LINE__);
			if(strlen($value)>50){
				$sock->SaveConfigFile($value, $key);
				continue;
			}
			$sock->SET_INFO($key, $value);
		}
		meta_admin_mysql(2, "Policy success $policy_name Type:$policy_type ".count($policy_content)." parameters", null,__FILE__,__LINE__);
		
		$data=$this->get_meta_infos("policy-remove=$md5_policy");
		
		if(!$data){
			meta_admin_mysql(1, "No back return after sending policy removal command", null,__FILE__,__LINE__);
			return true;
		}
		
		
		
		if($policy_type==1){
			shell_exec("/etc/init.d/apache2 restart >/dev/null 2>&1");
			shell_exec("/etc/init.d/nginx restart >/dev/null 2>&1");
		}
		
		shell_exec("/etc/init.d/artica-status restart --force >/dev/null 2>&1");
		
		
		
		return true;
		
		
	}
	
	
	private function ACTION_MANAGER_CREDS($orderid,$array){
		if(isset($GLOBALS["ACTION_MANAGER_CREDS"][$orderid])){return;}
		$GLOBALS["ACTION_MANAGER_CREDS"][$orderid]=true;
		$unix=new unix();
	
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_MANAGER_CREDS ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		if(!isset($array["VALUE"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_MANAGER_CREDS ( no value )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$array=unserialize(base64_decode($array["VALUE"]));
		if($array["USER"]==null){
			meta_admin_mysql(0, "Unable to understand order ACTION_MANAGER_CREDS ( no User )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		if($array["PASS"]==null){
			meta_admin_mysql(0, "Unable to understand order ACTION_MANAGER_CREDS ( no Password )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}	

		$BASCONF="/etc/artica-postfix/ldap_settings";
		$php=$unix->LOCATE_PHP5_BIN();
		file_put_contents("$BASCONF/admin", $array["USER"]);
		file_put_contents("$BASCONF/password", $array["PASS"]);
		system("/usr/share/artica-postfix/bin/artica-install --slapdconf");
		system('/usr/share/artica-postfix/bin/process1 --checkout --force --verbose '. time());
		
		$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
		if($EnableOpenLDAP==1){
			shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__));
		}
		system("$php /usr/share/artica-postfix/exec.change.password.php");
		meta_admin_mysql(2, "Success change Manager credentials to {$array["USER"]} to password value of ".strlen($array["PASS"])." characters", null,__FILE__,__LINE__);
		$this->removeorder($orderid);
		return true;
		
	}
	
	
	private function ACTION_ROOT_PASSWORD($orderid,$array){
		if(isset($GLOBALS["ACTION_ROOT_PASSWORD"][$orderid])){return;}
		$GLOBALS["ACTION_ROOT_PASSWORD"][$orderid]=true;
		$unix=new unix();
		
		if(!is_array($array)){
			meta_admin_mysql(0, "Unable to understand order ACTION_ROOT_PASSWORD ( array expected )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		if(!isset($array["VALUE"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_ROOT_PASSWORD ( no password )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$unix=new unix();
		$unix->ChangeRootPassword($array["VALUE"]);
		meta_admin_mysql(2, "Success change root password to a value of ".strlen($array["VALUE"])." characters", null,__FILE__,__LINE__);
		$this->removeorder($orderid);
		return true;
		
	}
	
	
	function ACTION_REBOOT($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_REBOOT"][$orderid])){return;}
		$GLOBALS["ACTION_REBOOT"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$sock=new sockets();
		$DisableForceFCK=intval($sock->GET_INFO("DisableForceFCK"));
		@unlink("/forcefsck");if($DisableForceFCK==0){@touch("/forcefsck"); }
		
		meta_admin_mysql(2, "Success ask system to reboot...", null,__FILE__,__LINE__);
		$shutdown=$unix->find_program("shutdown");
		shell_exec("$shutdown -rF now");
		
	}
	
	private function ACTION_RESTART_PROXY($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_RESTART_PROXY"][$orderid])){return;}
		$GLOBALS["ACTION_RESTART_PROXY"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$this->SEND_TASK_PERCENT(50);
		meta_admin_mysql(2, "Success launching restarting proxy service", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup /etc/init.d/squid restart --force >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
	}
	private function ACTION_PROXY_DISABLE_URGENCY($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_PROXY_DISABLE_URGENCY"][$orderid])){return;}
		$GLOBALS["ACTION_PROXY_DISABLE_URGENCY"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$this->SEND_TASK_PERCENT(50);
		meta_admin_mysql(2, "Success launching disable Proxy emergency service", null,__FILE__,__LINE__);
		squid_admin_mysql(1, "Emergency disabled by Meta Server", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.urgency.remove.php --meta >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
		
	}
	private function ACTION_PROXY_ENABLE_URGENCY($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_PROXY_DISABLE_URGENCY"][$orderid])){return;}
		$GLOBALS["ACTION_PROXY_DISABLE_URGENCY"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$this->SEND_TASK_PERCENT(50);
		meta_admin_mysql(2, "Success launching enable Proxy emergency service", null,__FILE__,__LINE__);
		squid_admin_mysql(0, "Emergency enabled by Meta Server", null,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
		$nohup=$unix->find_program("nohup");
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
	
	}
	

	
	private function ACTION_RELOAD_PROXY($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_RELOAD_PROXY"][$orderid])){return;}
		$GLOBALS["ACTION_RELOAD_PROXY"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$this->SEND_TASK_PERCENT(50);
		meta_admin_mysql(2, "Success launching reload proxy service", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup /etc/init.d/squid reload --force --script=".basename(__FILE__)." >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
	}
	
	private function ACTION_PHILESIGHT($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_PHILESIGHT"][$orderid])){
			$this->events("$orderid already executed", __FUNCTION__,__LINE__);
			return;
		}
		$GLOBALS["ACTION_PHILESIGHT"][$orderid]=true;
		
		if(!$this->removeorder($orderid)){
			$this->events("$orderid unable to remove...", __FUNCTION__,__LINE__);
			return;
		}
		
		meta_admin_mysql(2, "Success launching Scanning directories", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		$php=$unix->LOCATE_PHP5_BIN();
		$this->events("exec.philesight.php --directories --force...", __FUNCTION__,__LINE__);
		$this->shell_exec("$nohup $php /usr/share/artica-postfix/exec.philesight.php --directories --force --meta-ping >/dev/null 2>&1 &");		
		
	}
	
	private function RECONNECT_AD($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_PHILESIGHT"][$orderid])){
			$this->events("$orderid already executed", __FUNCTION__,__LINE__);
			return;
		}
		$GLOBALS["ACTION_PHILESIGHT"][$orderid]=true;
		
		if(!$this->removeorder($orderid)){
			$this->events("$orderid unable to remove...", __FUNCTION__,__LINE__);
			return;
		}
		
		meta_admin_mysql(2, "Success launching Active Directory re-connection task", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		$php=$unix->LOCATE_PHP5_BIN();
		$this->events("exec.kerbauth.php --build --force --meta-ping...", __FUNCTION__,__LINE__);
		$this->shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --build --force --meta-ping >/dev/null 2>&1 &");
	}
	
	
	
	
	function ACTION_SNAPSHOT($orderid){
		$unix=new unix();
		if(isset($GLOBALS["ACTION_SNAPSHOT"][$orderid])){
			$this->events("$orderid already executed", __FUNCTION__,__LINE__);
			return;
		}		
		$GLOBALS["ACTION_SNAPSHOT"][$orderid]=true;
		
		if(!$this->removeorder($orderid)){
			$this->events("$orderid unable to remove...", __FUNCTION__,__LINE__);
			return;
		}
		$php=$unix->LOCATE_PHP5_BIN();
		$this->events("exec.backup.artica.php --snapshot...", __FUNCTION__,__LINE__);
		$this->SEND_TASK_PERCENT(50);
		$this->shell_exec("$php /usr/share/artica-postfix/exec.backup.artica.php --snapshot --force --meta-ping >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
	}
	
	
	
	private function shell_exec($cmdline){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
					$line=$trace[1]["line"];
			}
				
		}
		
		$this->events($cmdline, $function,$line);
		system($cmdline);
		
		
	}
	
	private function CLEAN_PROXY_CACHES($orderid){
		$unix=new unix();
		if(isset($GLOBALS["CLEAN_PROXY_CACHES"][$orderid])){return;}
		$GLOBALS["CLEAN_PROXY_CACHES"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		$this->SEND_TASK_PERCENT(50);
		meta_admin_mysql(2, "Success launching cleaning caches task..", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --empty >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
	}
	
	private function REINDEX_PROXY_CACHES($orderid){
		$unix=new unix();
		if(isset($GLOBALS["REINDEX_PROXY_CACHES"][$orderid])){return;}
		$GLOBALS["REINDEX_PROXY_CACHES"][$orderid]=true;
		if(!$this->removeorder($orderid)){return;}
		
		meta_admin_mysql(2, "Success launching re-index caches task..", null,__FILE__,__LINE__);
		$nohup=$unix->find_program("nohup");
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --reindex >/dev/null 2>&1 &");
		}
		
		
		private function ACTION_GET_CATEGORIES(){
			$uuid=$this->uuid;
			$this->events("[CATEGORIES]: Checking $uuid/PERSONAL_CATEGORIES.gz", __FUNCTION__);
			$curl=$this->buildCurl("/meta-updates/$uuid/PERSONAL_CATEGORIES.gz");
			$unix=new unix();
			$targetfilename=$unix->FILE_TEMP();
			
			if(!$this->get_meta_file("/meta-updates/$uuid/PERSONAL_CATEGORIES.gz", $targetfilename)){
				@unlink($targetfilename);
				return;
			}
			$this->events("[CATEGORIES]: Success downloading PERSONAL_CATEGORIES", __FUNCTION__,__FILE__,__LINE__);
			
			$artica_meta_client_categories=new artica_meta_client_categories($targetfilename);
			
			
			
			
			//get_personal_categories
			
		}
		
		
private function ACTION_INSTALL_SOFT_INCREMENTS($tgzfile){		
		$FINAL_FILENAME=basename($tgzfile);
		$this->events("Checking $tgzfile", __FUNCTION__);
		$unix=new unix();
		if(strpos($tgzfile, ".tgz")>0){
			$Mainpath=str_replace(".tgz", "", $tgzfile);
			$MAIN_EXT="tgz";
		}
		if(strpos($tgzfile, ".tar.gz")>0){
			$Mainpath=str_replace(".tar.gz", "", $tgzfile);
			$MAIN_EXT="tar.gz";
		}
		
		$DirTemp=basename($Mainpath);
		$path=$Mainpath."/metaindex.txt";
		
		
		$curl=$this->buildCurl($path);
		$filetemp=$unix->FILE_TEMP().".txt";
		meta_admin_mysql(2, "Downloading $path", null,__FILE__,__LINE__);
		
		
		if(!$curl->GetFile($filetemp)){
			meta_admin_mysql(0, "Unable to download $path $curl->error",  @implode("\n", $curl->errors),__FILE__,__LINE__);
			return false;
		}
		
		$ARRAY=unserialize(@file_get_contents($filetemp));
		if(!is_array($ARRAY)){
			meta_admin_mysql(0, "Corrupted metaindex ( not an array )", null,__FILE__,__LINE__);
			return false;
		}
		
		if(count($ARRAY)<5){
			meta_admin_mysql(0, "Corrupted metaindex ( only ".count($ARRAY)." items )", null,__FILE__,__LINE__);
			return false;
		}
		$tempdir="/home/artica-meta/download/TEMP/$DirTemp";
		@mkdir($tempdir,0755,true);
		$count=count($ARRAY);
		$c=0;
		while (list ($targetFile, $md5file) = each ($ARRAY) ){
			$BaseName=basename($targetFile);
			$HTTP_LINK="$Mainpath/$BaseName";
			$LOCAL_FILE="$tempdir/$BaseName";
			$this->events("Checking $LOCAL_FILE", __FUNCTION__);
			if(is_file($LOCAL_FILE)){
				$md5Local=md5_file($LOCAL_FILE);
				if($md5Local==$md5file){ continue; }
				$this->events("$LOCAL_FILE corrupted...", __FUNCTION__);
				@unlink($LOCAL_FILE);
			}
			$c++;
			$this->events("$c/$count Downloading $HTTP_LINK", __FUNCTION__);
			$curl=$this->buildCurl($HTTP_LINK);
			if(!$curl->GetFile($LOCAL_FILE)){
				$this->SEND_TASK_PERCENT(0);
				meta_admin_mysql(0, "Unable to download $HTTP_LINK $curl->error", @implode("\n", $curl->errors),__FILE__,__LINE__);
				return true;
			}
			$md5Local=md5_file($LOCAL_FILE);
			if($md5Local==$md5file){
				$this->events("$HTTP_LINK success...", __FUNCTION__);
				$percent=$c/$count;
				$percent=round($percent*100);
				$this->SEND_TASK_PERCENT($percent);
				continue;
			}
				
			meta_admin_mysql(0, "Corrupted download $HTTP_LINK ( md5 differ )", @implode("\n", $curl->errors),__FILE__,__LINE__);
			$this->SEND_TASK_PERCENT(0);
			return true;
				
		}
		
		
		$this->SEND_TASK_PERCENT(0);
		$rm=$unix->find_program("rm");
		$cat=$unix->find_program("cat");
		$php=$unix->LOCATE_PHP5_BIN();
		$tar=$unix->find_program("tar");
		$filetemp=dirname(__FILE__)."/conf/upload/$FINAL_FILENAME";
		system("$cat $tempdir/*.$MAIN_EXT.* >$filetemp");
		shell_exec("$rm -rf $tempdir");
		$size=filesize("$filetemp");
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		
		$php=$unix->LOCATE_PHP5_BIN();
		$this->SEND_TASK_PERCENT(99);
		$this->events("exec.installv2.php --install $FINAL_FILENAME", __FUNCTION__,__FILE__,__LINE__);
		system("$php /usr/share/artica-postfix/exec.installv2.php --install $FINAL_FILENAME >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
		return true;
		}		
		
		
		
	
	private function ACTION_INSTALL_SOFT($orderid,$array=array()){
		if(isset($GLOBALS["ACTION_INSTALL_SOFT"][$orderid])){return;}
		$GLOBALS["ACTION_INSTALL_SOFT"][$orderid]=true;
		$this->events("Checking order id: $orderid, action={$array["FILENAME"]} {$array["SIZE"]}Bytes", __FUNCTION__,__FILE__,__LINE__);
		$unix=new unix();
		if(!isset($array["FILENAME"])){
			meta_admin_mysql(0, "Unable to understand order ACTION_INSTALL_SOFT ( no filename )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		$this->SEND_TASK_PERCENT(10);
		
		$uri="/meta-updates/softwares/{$array["FILENAME"]}";
		
		if($this->ACTION_INSTALL_SOFT_INCREMENTS($uri)){
			$this->removeorder($orderid);
			return true;
		}
		
		
		$uri="/meta-updates/softwares/{$array["FILENAME"]}";
		$this->events("$uri", __FUNCTION__,__FILE__,__LINE__);
		$curl=$this->buildCurl($uri);
		$filetemp=dirname(__FILE__)."/conf/upload/{$array["FILENAME"]}";
		if(!$curl->GetFile($filetemp)){
			$this->SEND_TASK_PERCENT(0);
			$this->events("Unable to download $uri $curl->error", __FUNCTION__,__FILE__,__LINE__);
			meta_admin_mysql(0, "Unable to download $uri $curl->error", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
		}
		$this->SEND_TASK_PERCENT(50);
		$php=$unix->LOCATE_PHP5_BIN();
		if(!$unix->TARGZ_TEST_CONTAINER($filetemp)){
			@unlink($filetemp);
			$this->SEND_TASK_PERCENT(0);
			$this->events("Unable to uncompress $filetemp", __FUNCTION__,__FILE__,__LINE__);
			meta_admin_mysql(0, "Unable to uncompress $filetemp", @implode("\n", $GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]),__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
		}
		$php=$unix->LOCATE_PHP5_BIN();
		$this->SEND_TASK_PERCENT(99);
		$this->events("exec.installv2.php --install {$array["FILENAME"]}", __FUNCTION__,__FILE__,__LINE__);
		system("$php /usr/share/artica-postfix/exec.installv2.php --install {$array["FILENAME"]} >/dev/null 2>&1 &");
		$this->SEND_TASK_PERCENT(0);
		$this->removeorder($orderid);
		
	}
	
	public function SEND_TASK_PERCENT($num){
		
		$this->events("Notify task {$num}%", "SEND_TASK_PERCENT", __FUNCTION__,__LINE__);
		
		$curl=$this->buildCurl("SEND_TASK_PERCENT=$num");
		if(!$curl->get()){
			$this->events("Failed with error $curl->error", __FUNCTION__,__LINE__);
			return false;
		}
		
	}
	
	private function ACTION_UPDATE_ARTICA_INCREMENTS($tgzfile){
		$this->events("Checking $tgzfile", __FUNCTION__);
		$unix=new unix();
		$Mainpath=str_replace(".tgz", "", $tgzfile);
		$DirTemp=basename($Mainpath);
		$path=$Mainpath."/metaindex.txt";
		
		
		$curl=$this->buildCurl($path);
		$filetemp=$unix->FILE_TEMP().".txt";
		meta_admin_mysql(2, "Downloading $path", null,__FILE__,__LINE__);
		
		
		if(!$curl->GetFile($filetemp)){
			meta_admin_mysql(0, "Unable to download $path $curl->error",  @implode("\n", $curl->errors),__FILE__,__LINE__);
			return false;
		}
		
		$ARRAY=unserialize(@file_get_contents($filetemp));
		if(!is_array($ARRAY)){
			meta_admin_mysql(0, "Corrupted metaindex ( not an array )", null,__FILE__,__LINE__);
			return false;
		}
		
		if(count($ARRAY)<5){
			meta_admin_mysql(0, "Corrupted metaindex ( only ".count($ARRAY)." items )", null,__FILE__,__LINE__);
			return false;
		}
		$tempdir="/home/artica-meta/download/TEMP/$DirTemp";
		@mkdir($tempdir,0755,true);
		$count=count($ARRAY);
		$c=0;
		while (list ($targetFile, $md5file) = each ($ARRAY) ){
			$BaseName=basename($targetFile);
			$HTTP_LINK="$Mainpath/$BaseName";
			$LOCAL_FILE="$tempdir/$BaseName";
			$this->events("Checking $LOCAL_FILE", __FUNCTION__);
			if(is_file($LOCAL_FILE)){
				$md5Local=md5_file($LOCAL_FILE);
				if($md5Local==$md5file){ continue; }
				$this->events("$LOCAL_FILE corrupted...", __FUNCTION__);
				@unlink($LOCAL_FILE);
			}
			$c++;
			$this->events("$c/$count Downloading $HTTP_LINK", __FUNCTION__);
			$curl=$this->buildCurl($HTTP_LINK);
			if(!$curl->GetFile($LOCAL_FILE)){
				$this->SEND_TASK_PERCENT(0);
				meta_admin_mysql(0, "Unable to download $HTTP_LINK $curl->error", @implode("\n", $curl->errors),__FILE__,__LINE__);
				return true;
			}
			$md5Local=md5_file($LOCAL_FILE);
			if($md5Local==$md5file){ 
				$this->events("$HTTP_LINK success...", __FUNCTION__);
				$percent=$c/$count;
				$percent=round($percent*100);
				$this->SEND_TASK_PERCENT($percent);
				continue; 
			}
			
			meta_admin_mysql(0, "Corrupted download $HTTP_LINK ( md5 differ )", @implode("\n", $curl->errors),__FILE__,__LINE__);
			$this->SEND_TASK_PERCENT(0);
			return true;
			
		}
		
		
		$this->SEND_TASK_PERCENT(0);
		$rm=$unix->find_program("rm");
		$cat=$unix->find_program("cat");
		$php=$unix->LOCATE_PHP5_BIN();
		$tar=$unix->find_program("tar");
		system("$cat $tempdir/*.tgz.* >$tempdir/artica.tgz");
		$size=filesize("$tempdir/artica.tgz");
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		meta_admin_mysql(2, "Extracting $tempdir/artica.tgz ( $size MB )", null,__FILE__,__LINE__);
		shell_exec("$tar xpf $tempdir/artica.tgz -C /usr/share/");
		meta_admin_mysql(2, "Removing $tempdir", null,__FILE__,__LINE__);
		shell_exec("$rm -rf $tempdir");
		
		shell_exec("$php /usr/share/artica-postfix/exec.nightly.php --restart-services");
		$VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
		meta_admin_mysql(2, "Success updating Artica to version $VERSION", null,__FILE__,__LINE__);
		artica_update_event(2,"Success updating - META CLIENT - Artica version $VERSION",null,  __FILE__, __LINE__);
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
		return true;
	}
	
	
	
	private function ACTION_UPDATE_ARTICA($orderid,$array=array()){
		if(isset($GLOBALS["ACTION_UPDATE_ARTICA"][$orderid])){return;}
		$GLOBALS["ACTION_UPDATE_ARTICA"][$orderid]=true;
		
		$unix=new unix();
		if(!isset($array["FILENAME"])){
			meta_admin_mysql(0, "Unable to understand order UPDATE_ARTICA ( no filename )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		if(!isset($array["FILETYPE"])){
			meta_admin_mysql(0, "Unable to understand order UPDATE_ARTICA ( no filetype )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}		
		
		if(!preg_match("#^artica-([0-9\.]+)\.tgz$#", $array["FILENAME"],$re)){
			meta_admin_mysql(0, "Unable to understand order UPDATE_ARTICA ( wrong package )", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;	
		}
		
		$version=trim($re[1]);
		$myVersion=trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
		if($myVersion==$version){
			meta_admin_mysql(1, "Cannot update with the same package $version", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return false;
		}
		
		
		$FILENAME=$array["FILENAME"];
		$FILETYPE=$array["FILETYPE"];
		$uri="/meta-updates/nightlys/$FILENAME";
		if($FILETYPE=="RELEASES"){$uri="/meta-updates/releases/$FILENAME";}
		
		if($this->ACTION_UPDATE_ARTICA_INCREMENTS($uri)){
			$this->removeorder($orderid);
			return true;
		}
		
		
		$curl=$this->buildCurl($uri);
		$filetemp=$unix->FILE_TEMP().".tgz";
		meta_admin_mysql(2, "Downloading $uri", null,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "Downloading $uri -> $filetemp\n";}
		if(!$curl->GetFile($filetemp)){
			meta_admin_mysql(0, "Unable to download $uri $curl->error", null,__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
		}
		
		$tar=$unix->find_program("tar");
		$php=$unix->LOCATE_PHP5_BIN();
		if(!$unix->TARGZ_TEST_CONTAINER($filetemp)){
			@unlink($filetemp);
			meta_admin_mysql(0, "Unable to uncompress $filetemp", @implode("\n", $GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]),__FILE__,__LINE__);
			$this->removeorder($orderid);
			return;
			
			
		}
		meta_admin_mysql(2, "Extracting $filetemp", null,__FILE__,__LINE__);
		shell_exec("$tar xf $filetemp -C /usr/share/");
		@unlink($filetemp);
		shell_exec("$php /usr/share/artica-postfix/exec.nightly.php --restart-services");
		$VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
		meta_admin_mysql(2, "Success updating Artica version $VERSION", null,__FILE__,__LINE__);
		artica_update_event(2,"Success updating - META CLIENT - Artica version $VERSION",null,  __FILE__, __LINE__);
		$this->removeorder($orderid);
		
	}
	
	
	
	function Save(){
		if($GLOBALS["VERBOSE"]){echo "\nsave array of ". count($this->RegisterDatas)." rows\n";}
		$this->class_socket->SaveConfigFile(base64_encode(serialize($this->RegisterDatas)),"ArticaMetaRegisterDatas");
		
	}
	
	function DetectError($body){
		if(preg_match("#<b>Fatal error<\/b>:\s+(.+?)<br \/>#is",$body,$re)){return $re[1];}
		if(preg_match("#<ERROR>(.+?)<\/ERROR>#is",$body,$re)){return $re[1];}
		if(preg_match("#<title>404 Not Found<\/title>#is",$body,$re)){return "404 Not Found";}
		if(preg_match("#<title>400 Bad Request<\/title>#is",$body,$re)){return "400 Bad Request";}
		
	}
	
	private function DUMP_SQUID_WATCHDOG($temp_dir){
		$uuid=$this->uuid;
		$q=new mysql();
		$f=array();
		if(!$q->TABLE_EXISTS("squid_admin_mysql", "artica_events")){return;}
		
		
		if(!$q->FIELD_EXISTS("squid_admin_mysql", "sended", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
		}
		
		$results=$q->QUERY_SQL("SELECT * FROM squid_admin_mysql WHERE sended=0","artica_events");
		if(!$q->ok){
			meta_admin_mysql(0, "Error while exporting squid_admin_mysql",$q->mysql_error,__FILE__,__LINE__);
			return;
		}
		$prefix="INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`,`uuid`) VALUES ";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$content=mysql_escape_string2($ligne["content"]);
			$subject=mysql_escape_string2($ligne["subject"]);
			$f[]="('{$ligne["zDate"]}','$content','$subject','{$ligne["function"]}','{$ligne["filename"]}','{$ligne["line"]}','{$ligne["severity"]}','{$ligne["hostname"]}','$uuid')";
			
		}
		if(count($f)>0){
			@file_put_contents("$temp_dir/squid_admin_mysql.db", $prefix.@implode(",", $f));
			$q->QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE sended=0","artica_events");
		}
		
		
	}
	
	public function DUMP_SYSTEM_COMPUTERS($temp_dir){
		$ocs=new ocs();
		$q=new mysql();
		$ARRAY=array();
		$q2=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT `networks`.`IPADDRESS`,`networks`.`MACADDR`,`hardware`.`NAME`,`hardware`.`OSNAME` 
				FROM `networks`,`hardware` WHERE networks.HARDWARE_ID=hardware.ID AND networks.meta=0","ocsweb");
		if(!$q->ok){return;}
		if(mysql_num_rows($results)==0){return;}
		while ($ligne = mysql_fetch_assoc($results)) {
			
			$uid=$q2->UID_FROM_MAC($ligne["MACADDR"]);
			if($uid==null){$uid=$q2->UID_FROM_IP($ligne["IPADDRESS"]);}
			$mac=$ligne["MACADDR"];
			
			$ARRAY[$mac]["IPADDR"]=$ligne["IPADDRESS"];
			$ARRAY[$mac]["IPINT"]=ip2long($ligne["IPADDRESS"]);
			$ARRAY[$mac]["hostname"]=$ligne["NAME"];
			$ARRAY[$mac]["username"]=$uid;
			$ARRAY[$mac]["OSNAME"]=$ligne["OSNAME"];
			
			
		}
		
		if(count($ARRAY)>0){
			@file_put_contents("$temp_dir/network_hosts.db", serialize($ARRAY));
			$q->QUERY_SQL("UPDATE networks SET meta=1 WHERE meta=0","ocsweb");
		}
		
		
	}
	
	
	
	public function events($text,$function,$file=null,$line=0){
		if($file==null){$file=basename(__FILE__);}else{$file=basename($file);}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-meta-agent.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$text="[$file][$pid] $date $function:: $text (L.$line)\n";
		if($GLOBALS["OUTPUT"]){echo $text;}
		if($GLOBALS["VERBOSE"]){echo $text;}
		@fwrite($f, $text);
		@fclose($f);	
		
		$logFile="/var/log/artica-meta.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, $text);
		@fclose($f);
	}	
	
	
}