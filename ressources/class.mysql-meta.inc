<?php
if(!isset($GLOBALS["DEBUG_SQL"])){$GLOBALS["DEBUG_SQL"]=false;}


class mysql_meta{
	private $mysql_admin="root";
	private $SocketName=null;
	public $last_id=0;
	public $ok=false;
	public $policy_type=array();
	public $mysql_error=null;
	private $mysql_connection;
	private $database="articameta";
	private $mysql_server=":/var/run/mysqld/mysqld.sock";
	private $mysql_port=3306;
	private $mysql_password=null;
	
	public function mysql_meta(){
		
		$this->FillSettings();
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		
		$this->policy_type[1]="{web_service}";
		$this->policy_type[2]="{proxy_service}";
		$this->policy_type[3]="{update}";
		$this->policy_type[4]="{notifications} (Meta)";
	}
	
	
	public FUNCTION CREATE_DATABASE($database){
		
		if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
	
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		$results=@mysql_query("CREATE DATABASE `$database`",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
		$this->ok=true;
		return true;
	}
	private FUNCTION DELETE_DATABASE($database){
		if(!$this->DATABASE_EXISTS($database)){return true;}
		if($GLOBALS["VERBOSE"]){echo " -> ->DELETE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
	
		if(!$this->BD_CONNECT()){
			writelogs("DELETE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		$results=@mysql_query("DROP DATABASE `$database`",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="DELETE DATABASE $database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
		$this->ok=true;
		return true;
	}
	
	public function SQL_EDIT_FROM_POST($key,$table){
		while (list ($num, $val) = each ($_POST)){
			if($num==$key){continue;}
			$f[]="`$num`='".mysql_escape_string2($val)."'";
		}
		return "UPDATE `$table` SET ".@implode(",", $f)." WHERE `$key`='{$_POST[$key]}'";
		
	}
	
	private function FillSettings(){
		if(!isset($GLOBALS["posix_getuid"])){
			$GLOBALS["posix_getuid"]=1000;
			if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}
		}
	
			
		if(!isset($GLOBALS["AS_ROOT"])){if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}}
			
		$this->mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		if($this->mysql_password=="!nil"){$this->mysql_password=null;}
		$this->mysql_password=stripslashes($this->mysql_password);
		$this->mysql_admin=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$this->mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		$this->mysql_port=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
		if($this->mysql_port==null){$this->mysql_port=3306;}
		if($this->mysql_server==null){$this->mysql_server="localhost";}
		$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
		$this->mysql_admin=trim($this->mysql_admin);
		$this->mysql_password=str_replace("\r", "", $this->mysql_password);
		$this->mysql_password=trim($this->mysql_password);
		if(!isset($GLOBALS["TIMEZONES"])){$GLOBALS["TIMEZONES"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/timezones"));}
	
		if(function_exists("date_default_timezone_set")){
			if($GLOBALS["TIMEZONES"]<>null){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}
		}
	
	
	
	
		if(($this->mysql_server=="!nil") OR ($this->mysql_admin=='!nil') OR ($this->mysql_password=='!nil')){
			$this->mysql_server="127.0.0.1";
			$this->mysql_password=null;
			$this->mysql_admin="root";
	
		}
	
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		if($this->mysql_server==null){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="locahost"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		if(!is_numeric($this->mysql_port)){$this->mysql_port=3306;}
		if($this->mysql_server=="127.0.0.1"){
			$this->mysql_server=":/var/run/mysqld/mysqld.sock";
			$this->SocketName="/var/run/mysqld/mysqld.sock";
		}
	
	}
	
	public function SQL_ADD_FROM_POST($key,$table){
		while (list ($num, $val) = each ($_POST)){
			if($num==$key){continue;}
			$fields[]="`$num`";
			$data[]="'".mysql_escape_string2($val)."'";
			
		}
		return "INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(',', $data).")";
		
	
	}	
	
	public function delete_policy($ID){
		$this->QUERY_SQL("DELETE FROM `policies` WHERE ID='$ID'");
	}
	
	public function GET($key,$policy_id){
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT `policy_value` FROM `policies_content` 
				WHERE policy_id='$policy_id' AND `policy_key`='$key'"));
		return trim($ligne["policy_value"]);
		
	}
	
	public function GET_CONFIG($key,$uuid){
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT `fileData` FROM `localconfig` WHERE `filekey`='$key' AND `uuid`='$uuid'"));
		return trim($ligne["fileData"]);
	
	}
	
	public function SET($key,$value,$policy_id){
		
		$this->QUERY_SQL("DELETE FROM policies_content WHERE policy_id='$policy_id' AND `policy_key`='$key'");	
		$value=mysql_escape_string2($value);
		$sql="INSERT IGNORE INTO `policies_content` (policy_value,policy_key,policy_id)
		VALUES('$value','$key','$policy_id')";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		return true;
	}
	
	
	public function BD_CONNECT($noretry=false,$called=null){
		
		if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		
		if($this->SocketName<>null){
			$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,$this->mysql_password);
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
		}
		
			if($bd){
				$this->mysql_connection=$bd;
				return true;
			}
	
	
			if($GLOBALS["VERBOSE"]){echo "mysql_connect $this->SocketName -> error<br>\n";}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->ToSyslog($this->BD_CONNECT_ERROR);
			return false;
		}
		
		public function QUERY_SQL($sql,$database=null,$called=null,$unbuffered=false){
			$database=trim($database);
			if($database=="artica_backup"){$database=$this->database;}
			if($database=="artica_events"){$database=$this->database;}
			if($database=="ocsweb"){$database=$this->database;}
			if($database=="postfixlog"){$database=$this->database;}
			if($database=="powerdns"){$database=$this->database;}
			if($database=="zarafa"){$database=$this->database;}
			if($database=="syslogstore"){$database=$this->database;}
			if($database=="squidlogs"){$database=$this->database;}
			if($database==null){$database=$this->database;}
			$this->last_id=0;
			$this->ok=true;
			$this->sql=$sql;
			$CLASS=__CLASS__;
			$FUNCTION=__FUNCTION__;
			$FILENAME=basename(__FILE__);
			$LOGPRF="$FILENAME::$CLASS/$FUNCTION";
			
			
			if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
			@mysql_close($this->mysql_connection);
			if(!$this->BD_CONNECT(false,$called)){
				if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
				if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
				$this->ok=false;
				$this->mysql_error=$this->BD_CONNECT_ERROR ." Error, unable to connect to MySQL server";
				$this->ToSyslog($this->mysql_error);
				return false;
			}		
			if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
				$TableDropped=$re[1];
				if(function_exists("system_admin_events")){
					$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
				}
			}
			$ok=@mysql_select_db($database,$this->mysql_connection);
			
			if (!$ok){
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				if(!is_numeric($errnum)){
					if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__."  [FAILED] error $errnum $des -> RESTART !!\n";};
					@mysql_close($this->mysql_connection);
					$this->mysql_connection=false;
					$this->BD_CONNECT(false,$called);
					$ok=@mysql_select_db($this->database,$this->mysql_connection);
					if (!$ok){
						if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] -> SECOND TIME !!\n";};
						$this->ok=false;
						$this->mysql_error=$this->BD_CONNECT_ERROR ." mysql_select_db/$this->database failed $errnum $des";
						return false;
					}
				}
			}
			
			if (!$ok){
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				if($GLOBALS["VERBOSE"]){echo "mysql -u $this->mysql_admin -p$this->mysql_password -h $this->mysql_server -P $this->mysql_port -A $this->database\n";}
				$this->mysql_errornum=$errnum;
				$this->mysql_error=$des;
				$time=date('h:i:s');
				$this->writeLogs("$LOGPRF Line:".__LINE__.":mysql_select_db DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
				$this->mysql_error="$LOGPRF Line:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				$this->ok=false;
				$this->ToSyslog($this->mysql_error);
				$this->ToSyslog($sql);
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				return null;
			}
			
			$mysql_unbuffered_query_log=null;
			if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
				$mysql_unbuffered_query_log="mysql_unbuffered_query";
				if($GLOBALS["DEBUG_SQL"]){echo "mysql_unbuffered_query()\n";}
				$results=@mysql_unbuffered_query($sql,$this->mysql_connection);
					
			}else{
				if($unbuffered){
					$mysql_unbuffered_query_log="mysql_unbuffered_query";
					if($GLOBALS["DEBUG_SQL"]){echo "mysql_unbuffered_query()\n";}
					$results=@mysql_unbuffered_query($sql,$this->mysql_connection);
				}else{
					$mysql_unbuffered_query_log="mysql_query";
					if($GLOBALS["DEBUG_SQL"]){echo "mysql_query()\n";}
					$results=@mysql_query($sql,$this->mysql_connection);
					$this->last_id=@mysql_insert_id($this->mysql_connection);
				}
			}			
			
			if(!$results){
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
					
				if(preg_match('#Duplicate entry#',$des)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
					$this->ok=true;
					@mysql_close($this->mysql_connection);
					$this->mysql_connection=false;
					return true;
				}
				$this->mysql_errornum=$errnum;
				$this->mysql_error="QUERY_SQL:".__LINE__.": $mysql_unbuffered_query_log:: $called Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				$this->ToSyslog($this->mysql_error);
				$sql=str_replace("\n", " ", $sql);
				$sql=str_replace("\t", " ", $sql);
				$sql=str_replace("  ", " ", $sql);
				$this->ToSyslog($sql);
				if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log/".__LINE__." [FAILED] N.$errnum DESC:$des $called\n";}
				if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log".__LINE__." [FAILED] $sql\n";}
				@mysql_free_result($this->mysql_connection);
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				$this->ok=false;
				return null;
			
			}
			
			
			
			if($GLOBALS["DEBUG_SQL"]){echo "SUCCESS\n";}
			$this->ok=true;
			if($this->last_id==0){
				$this->last_id=@mysql_insert_id($this->mysql_connection);
			}
			$result_return=$results;
			@mysql_free_result($this->mysql_connection);
			@mysql_close($this->mysql_connection);
			$this->mysql_connection=false;
			return $result_return;

		}
		
		public function COUNT_ROWS($table,$database=null){
			$table=str_replace("`", "", $table);
			$table=str_replace("'", "", $table);
			$table=str_replace("\"", "", $table);
			if(!function_exists("mysql_connect")){return 0;}
			$sql="show TABLE STATUS WHERE Name='$table'";
			$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
			if($ligne["Rows"]==null){$ligne["Rows"]=0;}
			return $ligne["Rows"];
		}
		public function TABLE_SIZE($table,$database=null){
			$database=trim($database);
			if($database==null){$database=$this->database;}
			if(!function_exists("mysql_connect")){return 0;}
			$sql="show TABLE STATUS WHERE Name='$table'";
			$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
			if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
			if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
			return $ligne["Index_length"]+$ligne["Data_length"];
		}

		public function EXECUTE_SQL($sql){
			if(!$this->BD_CONNECT()){return false;}
		
			$results=@mysql_query($sql,$this->mysql_connection);
			if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno($this->mysql_connection);
				$des=mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des) <hr>$sql";
				writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->ok=false;
				return false;
			}
		
			$this->ok=true;
			return $results;
		}		
		
		
			
		private function ToSyslog($text,$error=false){
				$text=str_replace("\n", " ", $text);
				$text=str_replace("\r", " ", $text);
			
			
				if(function_exists("debug_backtrace")){
					$trace=@debug_backtrace();
					if(isset($trace[1])){
						$function="{$trace[1]["function"]}()";
						$line="{$trace[1]["line"]}";
					}
				}
			
				$text="{$function}[$line]:$text";
				if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
				if(function_exists("openlog")){openlog("mysql-meta", LOG_PID , LOG_SYSLOG);}
				if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
				if(function_exists("closelog")){closelog();}
			}
			
		private function writelogs($text=null,$function=null,$line=0){
				$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.meta.debug";
				@mkdir(dirname($file_source));
				if(!is_numeric($line)){$line=0;}
				if(function_exists("writelogs")){
					writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
				}
				if(!$GLOBALS["VERBOSE"]){return;}
				$logFile=$file_source;
				if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
				if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
				$f = @fopen($logFile, 'a');
				$date=date("Y-m-d H:i:s");
				@fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
				@fclose($f);
			}

			public function TABLE_EXISTS($table,$database=null){
				$keyCache=__FUNCTION__;
				$database=trim($database);
				if($database=="artica_backup"){$database=$this->database;}
				if($database=="artica_events"){$database=$this->database;}
				if($database=="ocsweb"){$database=$this->database;}
				if($database=="postfixlog"){$database=$this->database;}
				if($database=="powerdns"){$database=$this->database;}
				if($database=="zarafa"){$database=$this->database;}
				if($database=="syslogstore"){$database=$this->database;}
				if($database=="squidlogs"){$database=$this->database;}
				if($database==null){$database=$this->database;}
				if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("TABLE_EXISTS:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
			
				$table=str_replace("`", "", $table);
				$table=str_replace("'", "", $table);
				$table=str_replace("\"", "", $table);
			
			
				if(!$this->DATABASE_EXISTS($database)){
					$this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
					if(!$this->CREATE_DATABASE($database)){
						$this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						return false;
					}
				}
			
				$sql="SHOW TABLES";
				$results=$this->QUERY_SQL($sql,$database,$called);
				$result=false;
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$GLOBALS[$keyCache][$database][$ligne["Tables_in_$database"]]=true;
					if(!$GLOBALS["AS_ROOT"]){$_SESSION[$keyCache][$database][$ligne["Tables_in_$database"]]=true;}
					if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){$result=true;}
				}
			
				return $result;
			
			}
			public function DATABASE_EXISTS($database){
				$database=trim($database);
				if($database=="artica_backup"){$database=$this->database;}
				if($database=="artica_events"){$database=$this->database;}
				if($database=="ocsweb"){$database=$this->database;}
				if($database=="postfixlog"){$database=$this->database;}
				if($database=="powerdns"){$database=$this->database;}
				if($database=="zarafa"){$database=$this->database;}
				if($database=="syslogstore"){$database=$this->database;}
				if($database=="squidlogs"){$database=$this->database;}
				if($database==null){$database=$this->database;}
			
				$sql="SHOW DATABASES";
				$this->BD_CONNECT();
				$results=@mysql_query($sql,$this->mysql_connection);
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					if(strtolower($database)==strtolower($ligne["Database"])){
						$_SESSION["MYSQL_DATABASE_EXISTS"][$database]=true;
						return true;
					}
				}
			
				return false;
			}
			
			public function network_hosts_count($uuid){
				$sql="SELECT COUNT(MAC) as tcount FROM networks_hosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["tcount"];
			}
			
			public function philesight_count($uuid){
				$sql="SELECT COUNT(directory) as tcount FROM philesight WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["tcount"];
				
			}
			
			public function uuid_to_host($uuid){
				if($uuid==null){return;}
				$sql="SELECT hostname FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["hostname"];
			}
			
			public function uuid_to_public_ip($uuid){
				$sql="SELECT public_ip FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["public_ip"];
				
			}
			
			public function group_count($ID){
				$sql="SELECT COUNT(zmd5) as tcount FROM metagroups_link WHERE gpid='$ID'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return intval($ligne["tcount"]);
			}
			
			public function IsAD($uuid){
				$sql="SELECT WINDOWSAD FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return intval($ligne["WINDOWSAD"]);
				
			}
			
			
			public function group_is_ad_inside($gpid){
				
				
				$sql="SELECT `metahosts`.`WINDOWSAD` 
					FROM `metagroups_link`,metahosts WHERE 
					`metagroups_link`.uuid=metahosts.uuid
					AND metagroups_link.gpid=$gpid AND `metahosts`.`WINDOWSAD`=1";
				$results = $this->QUERY_SQL($sql);
				$Count=mysql_num_rows($results);
				if($GLOBALS["VERBOSE"]){echo $sql."<hr>group_is_ad_inside RETURN $Count ROWS<br>\n";}
				
				if(!$this->ok){echo "<strong style='color:#d32d2d;font-size:22px'>MySQL Error!<br>$this->mysql_error</strong>";}
				
				
				if($Count>0){
					if($GLOBALS["VERBOSE"]){echo $sql."<hr>group_is_ad_inside RETURN TRUE<br>\n";}
					return true;
				}
				
			}
			
			public function gpid_to_name($ID){
				$sql="SELECT `groupname` FROM metagroups WHERE ID=$ID";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return utf8_encode($ligne["groupname"]);
			}
			
			public function uuid_to_tag($uuid){
				$sql="SELECT hostag FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["hostag"];				
				
			}
			
			public function isProxy($uuid){
				$sql="SELECT PROXY FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				if(intval($ligne["PROXY"])==1){return true;}
				return false;
			}
			
			public function ArticaVersion($uuid){
				$sql="SELECT version FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["version"];
			}
			public function LicenseInfos($uuid){
				$LicenseInfos["CORP_LICENSE"]=false;
				$DATA=$this->GET_CONFIG("LicenseInfos",$uuid);
				$LicenseInfos["ExpiresSoon"]=10000;
				$LicenseInfos=unserialize(base64_decode($DATA));
				$WizardSavedSettings=unserialize(base64_decode($this->GET_CONFIG("WizardSavedSettings",$uuid)));
				if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
				if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
				if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
				if(!isset($LicenseInfos["LICENCE_REQUEST"])){$LicenseInfos["LICENCE_REQUEST"]=null;}
				if($LicenseInfos["license_status"]=="{license_active}"){$LicenseInfos["CORP_LICENSE"]=true;}
				if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}
				if($FINAL_TIME>0){
					$LicenseInfos["ExpiresSoon"]=intval(time_between_day_Web($FINAL_TIME));
					if($LicenseInfos["ExpiresSoon"]==0){$LicenseInfos["ExpiresSoon"]=10000;}
					if($LicenseInfos["ExpiresSoon"]==1){$LicenseInfos["ExpiresSoon"]=10000;}
					//trial_mode
				}
				return $LicenseInfos;
				
			}
			
			
			public function isProxyGroup($gpid){
					$table="(SELECT metahosts.PROXY,
				metahosts.uuid,metagroups_link.zmd5 
				FROM metahosts,metagroups_link WHERE
				metagroups_link.uuid=metahosts.uuid
				AND metagroups_link.gpid=$gpid) as t";
				$sql="SELECT COUNT(PROXY) as PROXY FROM $table";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				if(intval($ligne["PROXY"])>0){return true;}
				return false;
				
			}
			
			public function isProxyAll(){
				$sql="SELECT COUNT(PROXY) PROXY FROM metahosts WHERE PROXY=1";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return intval($ligne["PROXY"]);
			
			}
			
			public function CloneSource($uuid){
				$sql="SELECT cloneFrom FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return trim($ligne["cloneFrom"]);
			}			
			
			public function isExists($uuid){
				$sql="SELECT uuid FROM metahosts WHERE uuid='$uuid'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				if(strlen($ligne["uuid"])>10){return true;}
				return false;
			}		
			
			public function mac_to_host($mac){
				$sql="SELECT hostname FROM networks_hosts WHERE MAC='$mac'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				return $ligne["hostname"];
				
			}
			
			public function isOrder($uuid,$subject="UPDATE_ARTICA"){
				$sql="SELECT orderid FROM metaorders WHERE uuid='$uuid' AND ordersubject='$subject'";
				$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
				if(strlen($ligne["orderid"])>5){return true;}
				return false;
				
			}
			
			public function RemoveOrder($uuid,$orderid){
				$this->meta_admin_mysql(2, "Ask to remove order $orderid for $uuid", null,__FILE__,__LINE__);
				$this->QUERY_SQL("DELETE FROM metaorders WHERE `uuid`='$uuid' AND `orderid`='$orderid'");
				if(!$this->ok){echo $q->mysql_error;return;}
				$sock=new sockets();
				$sock->getFrameWork("artica.php?meta-admin-orders=yes");
				return true;
			}
			
			
			public function DeleteUuid($uuid){
				
				$this->QUERY_SQL("DELETE FROM metaorders WHERE uuid='$uuid'");
				if(!$this->ok){
					echo "FAILED - metaorders - $uuid\n";
					echo $this->mysql_error;
					return false;}
				$this->QUERY_SQL("DELETE FROM squid_admin_mysql WHERE uuid='$uuid'");
				if(!$this->ok){
					echo "FAILED - squid_admin_mysql - $uuid\n";
					echo $this->mysql_error;
					return false;}
				$this->QUERY_SQL("DELETE FROM global_status WHERE uuid='$uuid'");
				if(!$this->ok){
					echo "FAILED - global_status - $uuid\n";
					echo $this->mysql_error;
					return false;}
					
				if(!$this->DELETE_DATABASE("squid_{$uuid}")){
					echo "FAILED - DELETE_DATABASE - squid_{$uuid}\n";
					echo $this->mysql_error;
					return false;
				}
					
				if($this->TABLE_EXISTS("dansguardian_community_nocat")){
					$this->QUERY_SQL("DELETE FROM dansguardian_community_nocat WHERE uuid='$uuid'");
					if(!$this->ok){
						echo "FAILED - dansguardian_community_nocat - $uuid\n";
						echo $this->mysql_error;
						return false;
					}
				}
				if($this->TABLE_EXISTS("snapshots")){
					$this->QUERY_SQL("DELETE FROM snapshots WHERE uuid='$uuid'");
					if(!$this->ok){
						echo "FAILED - snapshots - $uuid\n";
						echo $this->mysql_error;
						return false;
					}
				}
				if($this->TABLE_EXISTS("localconfig")){
					$this->QUERY_SQL("DELETE FROM localconfig WHERE uuid='$uuid'");
					if(!$this->ok){
						echo "FAILED - snapshots - $uuid\n";
						echo $this->mysql_error;
						return false;
					}
				}

				if($this->TABLE_EXISTS("meta_admin_hosts")){
					$this->QUERY_SQL("DELETE FROM `meta_admin_hosts` WHERE `uuid`='$uuid'");
				}
				if($this->TABLE_EXISTS("nics")){
					$this->QUERY_SQL("DELETE FROM `nics` WHERE `uuid`='$uuid'");
				}				
				
				$this->ok=true;
				$this->QUERY_SQL("DELETE FROM `metahosts` WHERE `uuid`='$uuid'");
				if(!$this->ok){
					echo "FAILED - metahosts - $uuid\n-------\n$this->mysql_error\n------\n";
					return false;
				}
				
				$this->QUERY_SQL("DELETE FROM `squid_perfs_gb` WHERE `uuid`='$uuid'");
				
				
				
				$this->QUERY_SQL("DELETE FROM `metagroups_link` WHERE `uuid`='$uuid'");
				if(!$this->ok){
					echo "FAILED - metagroups_link - $uuid\n-------\n$this->mysql_error\n------\n";
					return false;
				}
				
				if($this->TABLE_EXISTS("{$uuid}_WEEK_RTTD")){
					$this->QUERY_SQL("DROP  TABLE `{$uuid}_WEEK_RTTD`");
				}
				if($this->TABLE_EXISTS("{$uuid}_WEEK_RTTH")){
					$this->QUERY_SQL("DROP  TABLE `{$uuid}_WEEK_RTTH`");
				}				
				
				
				$q=new mysql();
				if($q->TABLE_EXISTS("meta_admin_mysql","artica_events")){
					$q->QUERY_SQL("DELETE FROM `meta_admin_mysql` WHERE `uuid`='$uuid'","artica_events");
				}

			
				
				
				
				
				return true;
				
			}
			
			public function CreateOrder_group($gpid,$subject, $array=array()){
				if($gpid==0){return;}
				$sql="SELECT uuid FROM metagroups_link WHERE gpid=$gpid";
				$results = $this->QUERY_SQL($sql);
				if(!$this->ok){echo $this->mysql_error."\n$sql\n";return;}
				
				while ($ligne = mysql_fetch_assoc($results)) {
					$uuid=$ligne["uuid"];
					if(!$this->CreateOrder($uuid, $subject,$array,true)){
						echo "Failed Computer $uuid\n";
						return false;}
					
				}
				
				$sock=new sockets();
				$sock->getFrameWork("artica.php?meta-admin-orders=yes");
				return true;
			}
			
			public function CreateOrder($uuid,$subject, $array=array(),$noSendorders=false){
				
				
				
				$orderid=md5("$uuid$subject".serialize($array));
				$ordercontent=base64_encode(serialize($array));
				
				$sql="INSERT IGNORE INTO metaorders ( `uuid`,`orderid`,`zDate`,`ordersubject`,`ordercontent` )
						VALUES ('$uuid','$orderid',NOW(),'$subject','$ordercontent')";
				$this->QUERY_SQL($sql);
				
				
				if(!$this->ok){
					$this->meta_admin_mysql(0, "FATAL MySQL error on order $subject for $uuid", $this->mysql_error,__FILE__,__LINE__);
					return false;
				}
				$this->meta_admin_mysql(2, "Send Order `$subject` for $uuid", $this->mysql_error,__FILE__,__LINE__);
				if(!$noSendorders){
					$sock=new sockets();
					$sock->getFrameWork("artica.php?meta-admin-orders=yes");
				}
				
				return true;
			}
			
			
			public function create_table_meta_uuid_admin_mysql(){
			
				$sql="CREATE TABLE IF NOT EXISTS `meta_admin_hosts` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`zmd5` VARCHAR( 90 ),
				`uuid` VARCHAR( 90 ),
				`zDate` TIMESTAMP NOT NULL ,
				`content` MEDIUMTEXT NOT NULL ,
				`hostname` VARCHAR( 255 ),
				`subject` VARCHAR( 255 ) NOT NULL ,
				`function` VARCHAR( 60 ) NOT NULL ,
				`filename` VARCHAR( 50 ) NOT NULL ,
				`line` INT( 10 ) NOT NULL ,
				`severity` smallint( 1 ) NOT NULL ,
				`sended` smallint( 1 ) NOT NULL DEFAULT 0,
				`TASKID` BIGINT UNSIGNED ,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY (`zmd5`),
				  KEY `zDate` (`zDate`),
				  KEY `uuid` (`uuid`),
				  KEY `subject` (`subject`),
				  KEY `hostname` (`hostname`),
				  KEY `function` (`function`),
				  KEY `filename` (`filename`),
				  KEY `severity` (`severity`)
				) ENGINE=MYISAM;";
			
				$q=new mysql();
				$q->QUERY_SQL($sql,$this->database);
			}

			public function create_table_meta_stats_size_hours($tablename){
				$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
				`zmd5` VARCHAR( 90 ),
				`uuid` VARCHAR( 90 ),
				`zDate` TIMESTAMP NOT NULL ,
				`filename` VARCHAR(256) NOT NULL,
				`size` INT UNSIGNED,
				 PRIMARY KEY (`zmd5`),
				 KEY `uuid` (`uuid`),
				 KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){
					if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n$sql\n";}
					return false;}
				return true;
			}
			public function create_table_meta_stats_size_day($tablename){
				$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
						`zmd5` VARCHAR( 90 ),
						`uuid` VARCHAR( 90 ),
						`zDate` TIMESTAMP NOT NULL ,
						`size` INT UNSIGNED,
						`hits` INT UNSIGNED,
						PRIMARY KEY (`zmd5`),
						KEY `uuid` (`uuid`),
						KEY `zDate` (`zDate`)
					) 
				ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n$sql\n";}return false;}
				return true;
				}			
			
			public function LIST_TABLES_STATS_HOURLY(){
				if(isset($GLOBALS["LIST_TABLES_STATS_HOURLY"])){return $GLOBALS["LIST_TABLES_STATS_HOURLY"];}
				$array=array();
				$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'metastats_size_%'";
				$results=$this->QUERY_SQL($sql);
				if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
				if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
			
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					if(preg_match("#metastats_size_[0-9]+#", $ligne["c"])){
						$GLOBALS["LIST_TABLES_STATS_HOURLY"][$ligne["c"]]=$ligne["c"];
						$array[$ligne["c"]]=$ligne["c"];
					}
				}
				return $array;
			
			}			
			
			
			public function create_table_meta_admin_mysql(){
				
				$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`meta_admin_mysql` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`zmd5` VARCHAR( 90 ),
				`uuid` VARCHAR( 90 ),
				`zDate` TIMESTAMP NOT NULL ,
				`content` MEDIUMTEXT NOT NULL ,
				`hostname` VARCHAR( 255 ),
				`subject` VARCHAR( 255 ) NOT NULL ,
				`function` VARCHAR( 60 ) NOT NULL ,
				`filename` VARCHAR( 50 ) NOT NULL ,
				`line` INT( 10 ) NOT NULL ,
				`severity` smallint( 1 ) NOT NULL ,
				`sended` smallint( 1 ) NOT NULL DEFAULT 0,
				`TASKID` BIGINT UNSIGNED ,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY (`zmd5`),
				  KEY `zDate` (`zDate`),
				  KEY `uuid` (`uuid`),
				  KEY `subject` (`subject`),
				  KEY `hostname` (`hostname`),
				  KEY `function` (`function`),
				  KEY `filename` (`filename`),
				  KEY `severity` (`severity`)
				) ENGINE=MYISAM;";

				$q=new mysql();
				$q->QUERY_SQL($sql,"artica_events");
				
				
				
			}
			
			private function MyUUid(){
				if(isset($GLOBALS["UUID"])){return $GLOBALS["UUID"];}
				if($GLOBALS["AS_ROOT"]){
					$unix=new unix();
					$GLOBALS["UUID"]=$unix->GetUniqueID();
					return $GLOBALS["UUID"];
				}
				if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc"); }
				$sock=new sockets();
				$GLOBALS["UUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
				return $GLOBALS["UUID"];
			}
			
			public function meta_admin_mysql($severity, $subject, $text,$file=null,$line=0){
				if(!is_numeric($line)){$line=0;}
				// 0 -> RED, 1 -> WARN, 2 -> INFO

				if(function_exists("debug_backtrace")){
					$trace=debug_backtrace();
					if(isset($trace[1])){
						if($file==null){
							$file=basename($trace[1]["file"]);
						}
						$function=$trace[1]["function"];
						if($line==0){
							$line=$trace[1]["line"];
						}
					}
						
				}
				$q=new mysql();
				if(!$q->test_mysql_connection()){return;}
				$file=basename(__FILE__);
				$text=mysql_escape_string2($text);
				$subject=mysql_escape_string2($subject);
				
				if($q->TABLE_EXISTS("meta_admin_mysql","artica_events")){
					if(!$q->FIELD_EXISTS("meta_admin_mysql", "zmd5", "artica_events")){
						$q->QUERY_SQL("DROP TABLE `meta_admin_mysql`","artica_events");
						
					}
				}
				
				$this->create_table_meta_admin_mysql();
				$uuid=$this->MyUUid();

				
				if(!$q->ok){echo $q->mysql_error."\n";return;}
				$zdate=date("Y-m-d H:i:s");
				$zm5=md5("$zdate$subject$function$file$line$uuid");
				
		
				$q->QUERY_SQL("INSERT IGNORE INTO `meta_admin_mysql`
					(`zmd5`,`uuid`,`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
					('$zm5','$uuid','$zdate','$text','$subject','$function','$file','$line','$severity','master')","artica_events");

			
			}
			
			public function FIELD_EXISTS($table,$field,$database=null){
				if($database==null){$database=$this->database;}
				$field=trim($field);
				if(isset($GLOBALS["__FIELD_EXISTS"])){
					if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
						if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
							if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]==true){return true;}
						}
					}
				}
				$sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
				$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
			
				if(trim($ligne["Field"])<>null){
					$GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
					return true;
				}else{
					
					return false;
				}
			
			}	

			public function BuildTables(){ $this->CheckTables();}
			
			public function CheckTables(){
				
				$sql="CREATE TABLE IF NOT EXISTS `squid_whitelists` (
				`pattern` varchar(90) NOT NULL,
				`zMD5` CHAR(32) NOT NULL,
				PRIMARY KEY (`zMD5`),
				KEY `pattern` (`pattern`)
				
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				
				$sql="CREATE TABLE IF NOT EXISTS `metaorders` (
				`uuid` varchar(90) NOT NULL,
				`orderid` varchar(90) NOT NULL,
				`zDate` DATETIME NOT NULL,
				`ordersubject` varchar(90) NOT NULL,
				`ordercontent` TEXT null,
				PRIMARY KEY (`orderid`),
				KEY `uuid` (`uuid`),
				KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				
				$sql="CREATE TABLE IF NOT EXISTS `policies` (
				`ID` INT(10) NOT NULL AUTO_INCREMENT,
				`policy_type` SMALLINT(1) NOT NULL,
				`policy_name` varchar(90) NOT NULL,
				`enabled` SMALLINT(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (`ID`),
					KEY `policy_type` (`policy_type`),
					KEY `policy_name` (`policy_name`),
					KEY `enabled` (`enabled`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				
				$sql="CREATE TABLE IF NOT EXISTS `INTERFACE_CACHE` (
				`uuid` VARCHAR(90) NOT NULL,
				`filekey` VARCHAR(90) NOT NULL,
				`filecontent` longblob,
				 KEY `filekey` (`filekey`),
				 KEY `uuid` (`uuid`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				

				$sql="CREATE TABLE IF NOT EXISTS `policies_content` (
				`policy_id` INT(10) NOT NULL,
				`policy_key` VARCHAR(90) NOT NULL,
				`policy_value` TEXT NOT NULL,
					KEY `policy_id` (`policy_id`),
					KEY `policy_key` (`policy_key`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}

				
				$sql="CREATE TABLE IF NOT EXISTS `policies_storage` (
				`zmd5`  VARCHAR(90) NOT NULL PRIMARY KEY,
				`uuid` VARCHAR(90) NOT NULL,
				`policy_content` longblob NOT NULL,
				KEY `uuid` (`uuid`)
					
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				
				$sql="CREATE TABLE IF NOT EXISTS `webfiltering_categories` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`category_description` VARCHAR( 255 ) NOT NULL ,
				`PublicMode` smallint(1) NOT NULL,
				PRIMARY KEY (`category`),
				INDEX ( `category_description`) ,
				KEY `PublicMode` (`PublicMode`) )  ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				$sql="CREATE TABLE IF NOT EXISTS `webfiltering_categories_items` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				`enabled` smallint(1) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`) ,
				KEY `enabled` (`enabled`) )  ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}

				$sql="CREATE TABLE IF NOT EXISTS `webfiltering_categories_link` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`gpid` INT(100) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `gpid`) ,
				KEY `category` (`category`) )  ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
								
				
				
				
				
				
				$sql="CREATE TABLE IF NOT EXISTS `webfiltering_categories_urls` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				`enabled` smallint(1) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`) ,
				KEY `enabled` (`enabled`) )  ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
								
				
				$sql="CREATE TABLE IF NOT EXISTS `metagroups` (
				`ID` INT(10) NOT NULL AUTO_INCREMENT,
				`groupname` varchar(90) NOT NULL,
				`CountHosts` smallint(3) NOT NULL DEFAULT 0,
				PRIMARY KEY (`ID`),
				KEY `groupname` (`groupname`),
				KEY `CountHosts` (`CountHosts`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				$sql="CREATE TABLE IF NOT EXISTS `metagroups_link` (
				`ID` INT(10) NOT NULL AUTO_INCREMENT,
				`zmd5` varchar(90) NOT NULL,
				`gpid` INT(10) NOT NULL,
				`uuid` varchar(90) NOT NULL,
				PRIMARY KEY (`ID`),
				UNIQUE KEY `zmd5` (`zmd5`),
				KEY `gpid` (`gpid`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				
				$sql="CREATE TABLE IF NOT EXISTS `metapolicies_link` (
				`ID` INT(10) NOT NULL AUTO_INCREMENT,
				`zmd5` varchar(90) NOT NULL,
				`gpid` INT(10) NOT NULL,
				`policy-id` INT(10) NOT NULL,
				PRIMARY KEY (`ID`),
				UNIQUE KEY `zmd5` (`zmd5`),
				KEY `policy-id` (`policy-id`)
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				$sql="CREATE TABLE IF NOT EXISTS `proxy_ports_wbl` (
					`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`groupid` INT NOT NULL,
					`destport` INT NOT NULL,
					`pattern` VARCHAR(128) NOT NULL,
					`include`  smallint(1) NOT NULL DEFAULT '0' ,
					KEY `pattern` (`pattern`),
					KEY `destport` (`destport`),
					KEY `include` (`include`),
					KEY `groupid` (`groupid`)
					)  ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){echo "<H2>$this->mysql_error</H2>";if(isset($GLOBALS["CLIENT_META_IP"])){echo $sql."\n$this->mysql_error\n";}}
				
				
				$sql="CREATE TABLE IF NOT EXISTS `metahosts` (
				`uuid` varchar(90) NOT NULL,
				`hostname` CHAR(128) NOT NULL,
				`public_ip` CHAR(60) NOT NULL,
				`updated` DATETIME NOT NULL,
				`version` CHAR(40) NOT NULL,
				`blacklisted` SMALLINT(1) NOT NULL DEFAULT 0,
				`PROXY` SMALLINT(1) NOT NULL DEFAULT 0,
				`UFDB_ENABLED` SMALLINT(1) NOT NULL DEFAULT 0,
				`PROXYEMERG` SMALLINT(1) NOT NULL DEFAULT 0,
				`WINDOWSAD` SMALLINT(1) NOT NULL DEFAULT 0,
				`ADEMERG` SMALLINT(1) NOT NULL DEFAULT 0,
				`UFDBARTICA` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`BANDWIDTH` BIGINT UNSIGNED NOT NULL DEFAULT 0,
				`squidver` CHAR(40) NULL,
				`CPU_NUMBER` SMALLINT(2) NOT NULL DEFAULT 0,
				`TaskPercent` SMALLINT(2) NOT NULL DEFAULT 0,
				`load` DOUBLE( 10, 2 ) NOT NULL ,
				`mem_perc` SMALLINT(2) NOT NULL DEFAULT 0,
				`mem_used` INT(100) NOT NULL DEFAULT 0,
				`mem_total` INT(100) NOT NULL DEFAULT 0,
				`hostag` VARCHAR(128) NULL,
				`cloneFrom` varchar(90) NOT NULL,
				`system_adm` varchar(90),
				`system_password` varchar(90),
				`disks` TEXT null,
				PRIMARY KEY (`uuid`),
				KEY `hostname` (`hostname`),
				KEY `public_ip` (`public_ip`),
				KEY `updated` (`updated`),
				KEY `version` (`version`),
				KEY `mem_perc` (`mem_perc`),
				KEY `mem_used` (`mem_used`),
				KEY `mem_total` (`mem_total`),
				KEY `BANDWIDTH` (`BANDWIDTH`),
				KEY `CPU_NUMBER` (`CPU_NUMBER`),
				KEY `cloneFrom` (`cloneFrom`),
				KEY `load` (`load`),
				KEY `PROXY` (`PROXY`),	
				KEY `hostag` (`hostag`),			
				KEY `squidver` (`squidver`),														
				KEY `blacklisted` (`blacklisted`)
				
				) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);				
				if(!$this->ok){
					if(isset($GLOBALS["CLIENT_META_IP"])){
						echo $sql."\n$this->mysql_error\n";
					}
				}
				
				if(!$this->FIELD_EXISTS("metahosts", "system_adm")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `system_adm` VARCHAR(90)");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				if(!$this->FIELD_EXISTS("metahosts", "system_password")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `system_password` VARCHAR(90)");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				
				
				if(!$this->FIELD_EXISTS("metahosts", "TaskPercent")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `TaskPercent` smallint(2),ADD INDEX ( `TaskPercent` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}	

				if(!$this->FIELD_EXISTS("metahosts", "cloneFrom")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `cloneFrom` VARCHAR(90),ADD INDEX ( `cloneFrom` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}	

				
				if(!$this->FIELD_EXISTS("metahosts", "PROXYEMERG")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `PROXYEMERG` smallint(1),ADD INDEX ( `PROXYEMERG` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				if(!$this->FIELD_EXISTS("metahosts", "WINDOWSAD")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `WINDOWSAD` smallint(1),ADD INDEX ( `WINDOWSAD` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}

				if(!$this->FIELD_EXISTS("metahosts", "ADEMERG")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `ADEMERG` smallint(1),ADD INDEX ( `ADEMERG` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				

				if(!$this->FIELD_EXISTS("metahosts", "UFDBARTICA")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `UFDBARTICA` BIGINT UNSIGNED NOT NULL DEFAULT 0");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				
				if(!$this->FIELD_EXISTS("metahosts", "UFDB_ENABLED")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `UFDB_ENABLED` smallint(1),ADD INDEX ( `UFDB_ENABLED` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				
				if(!$this->FIELD_EXISTS("metahosts", "PROXY")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `PROXY` smallint(1),ADD INDEX ( `PROXY` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				if(!$this->FIELD_EXISTS("metahosts", "BANDWIDTH")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `BANDWIDTH` BIGINT UNSIGNED NOT NULL DEFAULT 0,ADD INDEX ( `BANDWIDTH` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				
				
				if(!$this->FIELD_EXISTS("metahosts", "hostag")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `hostag` VARCHAR(128) NULL,ADD INDEX ( `hostag` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				
				
				
				if(!$this->FIELD_EXISTS("metahosts", "Squidver")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `squidver` varchar(40),ADD INDEX ( `squidver` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				if(!$this->FIELD_EXISTS("metahosts", "POSTFIX")){
					$this->QUERY_SQL("ALTER TABLE `metahosts` ADD `POSTFIX` smallint(1),ADD INDEX ( `POSTFIX` )");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}				

				
				$sql="CREATE TABLE IF NOT EXISTS `squid_admin_mysql` (
					`ID` int(11) NOT NULL AUTO_INCREMENT,
					`zDate` TIMESTAMP NOT NULL ,
					`content` MEDIUMTEXT NOT NULL ,
					`hostname` VARCHAR( 255 ),
					`subject` VARCHAR( 255 ) NOT NULL ,
					`function` VARCHAR( 60 ) NOT NULL ,
					`filename` VARCHAR( 50 ) NOT NULL ,
					`line` INT( 10 ) NOT NULL ,
					`severity` smallint( 1 ) NOT NULL ,
					`uuid` varchar(90) NOT NULL,
					`TASKID` BIGINT UNSIGNED ,
					PRIMARY KEY (`ID`),
					KEY `zDate` (`zDate`),
					KEY `subject` (`subject`),
					KEY `hostname` (`hostname`),
					KEY `function` (`function`),
					KEY `filename` (`filename`),
					KEY `uuid` (`uuid`),
					KEY `severity` (`severity`)
					) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				
				
				
				$sql="CREATE TABLE IF NOT EXISTS `nics` (
				`Interface` varchar(40) NOT NULL,
				`NICNAME` varchar(90) DEFAULT 'My Interface' NOT NULL,
				`AUTO` varchar(10) NOT NULL,
				`BRIDGE_PORTS` varchar(10) NOT NULL,
				`BRIDGE_STP` varchar(10) NOT NULL,
				`BRIDGE_MAXWAIT` int(2) NOT NULL,
				`DNS_SEARCH` varchar(128) NOT NULL,
				`VLAN_ROW_DEVICE` varchar(40) NOT NULL,
				`MTU` int(2) NOT NULL,
				`IPADDR` varchar(128) NOT NULL,
				`NETMASK` varchar(55) NOT NULL,
				`BOOTPROTO` varchar(10) NOT NULL,
				`GATEWAY` varchar(55) NOT NULL,
				`BROADCAST` varchar(55) NOT NULL,
				`DNS1` varchar(55) NOT NULL,
				`DNS2` varchar(55) NOT NULL,
				`dhcp` smallint(1) NOT NULL,
				`metric` smallint(5) NOT NULL,
				`ipv6mask` int(2) NOT NULL,
				`enabled` smallint(1) NOT NULL,
				`defaultroute` smallint(1) NOT NULL,
				`routes` longtext NOT NULL,
				`NETWORK` varchar(55) NOT NULL,
				`IsBridge` smallint(1) NOT NULL,
				`Bridged` smallint(1) NOT NULL,
				`isFW` smallint(1) NOT NULL,
				`isFWLogBlocked` smallint(1) NOT NULL,
				`ipv6` smallint(1) NOT NULL,
				`BridgedTo` varchar(50) NOT NULL,
				`isFWAcceptNet` smallint(1) NOT NULL,
				`ucarp-vid` int(1) DEFAULT '3' NOT NULL,
				`ucarp-vip` varchar(60),
				`ucarp-advskew` smallint(1),
				`ucarp-advbase` smallint(3),
				`ucarp-enable` smallint(1),
				`ucarp-master` smallint(1),
				`QOS` smallint(1) NOT NULL,
				`QOSMAX` smallint(10) DEFAULT '100' NOT NULL,
				`ShoreWallServices` text,
				`netzone` varchar(10),
				`SourceBasedRouting` smallint(1),
				`isFWAcceptArtica` smallint(1),
				`firewall_policy` varchar(10),
				`firewall_behavior` smallint(1) NOT NULL,
				`firewall_masquerade` smallint(1) NOT NULL,
				`ipv6addr` varchar(50) NOT NULL,
				`ipv6gw` varchar(50) NOT NULL,
				  KEY `Interface`  (`Interface`),
				  KEY `BridgedTo` (`BridgedTo`),
				  KEY `defaultroute` (`defaultroute`),
				  KEY `QOS` (`QOS`),
				  KEY `QOSMAX` (`QOSMAX`),
				  KEY `netzone` (`netzone`),
				  KEY `SourceBasedRouting` (`SourceBasedRouting`),
				  KEY `isFWAcceptArtica` (`isFWAcceptArtica`),
				  KEY `firewall_policy` (`firewall_policy`),
				  KEY `firewall_behavior` (`firewall_behavior`),
				  KEY `firewall_behavior_2` (`firewall_behavior`)
				) ENGINE=MYISAM;
				";
				$this->QUERY_SQL($sql,$this->database);
				
				$sql="CREATE TABLE IF NOT EXISTS `squid_perfs_gb` (
				`uuid` varchar(90) NOT NULL,
				`client_http_hits` FLOAT,
				`client_http_requests` FLOAT,
				`client_http_kbytes_out` FLOAT,
				`TOTALS_NOT_CACHED` INT UNSIGNED,
				`TOTALS_CACHED` INT UNSIGNED,
				`TOTALS_CACHED_AVG` FLOAT,
				PRIMARY KEY (`uuid`)) ENGINE=MYISAM;";
				
				$this->QUERY_SQL($sql,$this->database);
				
				
				$sql="CREATE TABLE IF NOT EXISTS `dansguardian_community_nocat` (
					zmd5 VARCHAR(90) NOT NULL,
					uuid VARCHAR(90) NOT NULL,
					sitename VARCHAR( 255 ) NOT NULL,
					HitsNumber BIGINT(100) NOT NULL,
					familysite VARCHAR( 255 ) NOT NULL,
					sended smallint(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (`zmd5`),
					KEY `sitename` (`sitename`)
					) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				
				
				$sql="CREATE TABLE IF NOT EXISTS `networks_hosts` (
					MAC VARCHAR(90) NOT NULL,
					IPADDR VARCHAR(90) NOT NULL,
					IPINT INT(10) NOT NULL,
					uuid VARCHAR( 90 ) NOT NULL,
					hostname VARCHAR(90) NOT NULL,
					username VARCHAR(255) NOT NULL,
					OSNAME VARCHAR(255) NOT NULL,
					PRIMARY KEY (`MAC`),
					KEY `IPINT` (`IPINT`),
					KEY `uuid` (`uuid`),
					KEY `hostname` (`hostname`),
					KEY `OSNAME` (`OSNAME`),
					KEY `username` (`username`)
					) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);	

				
				
				if(!$this->FIELD_EXISTS("networks_hosts", "OSNAME")){
					$this->QUERY_SQL("ALTER TABLE `networks_hosts` ADD `OSNAME` varchar(255)");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				$sql="CREATE TABLE IF NOT EXISTS `psaux` (
					uuid VARCHAR( 90 ) NOT NULL,
					user VARCHAR( 40 ) NOT NULL,
					pid INT( 10 ) NOT NULL,
					CPU FLOAT NOT NULL,
					MEM FLOAT NOT NULL,
					VSZ INT(100) NOT NULL,
					RSS INT(100) NOT NULL,
					pTIME VARCHAR( 40 ) NOT NULL,
					pcmd VARCHAR( 255 ) NOT NULL,
					KEY `user` (`user`),
					KEY `CPU` (`CPU`),
					KEY `uuid` (`uuid`),
					KEY `MEM` (`MEM`),
					KEY `VSZ` (`VSZ`),
					KEY `RSS` (`RSS`)
					) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);				
				
			$sql="CREATE TABLE IF NOT EXISTS `system_schedules` (
				`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`uuid` VARCHAR(128) NOT NULL,
				`TimeText` VARCHAR( 128 ) NOT NULL ,
				`TimeDescription` VARCHAR( 128 ) NOT NULL ,
				`TaskType` SMALLINT( 1 ) NOT NULL ,
				`enabled` SMALLINT( 1 ) NOT NULL ,
				 KEY `uuid` (`uuid`),
				 INDEX ( `TaskType` , `TimeDescription`,`enabled`)
				)";
			
			
			
				
			$this->QUERY_SQL($sql);
			
			

				
				
				
				$sql="CREATE TABLE IF NOT EXISTS `global_status` (
					`uuid` varchar(90) NOT NULL,
					`MAIN` VARCHAR( 90 ) NOT NULL ,
					`service_name` VARCHAR( 90 ) NOT NULL ,
					`service_cmd` VARCHAR( 128 ) NULL ,
					`master_version` VARCHAR( 40 ) NULL ,
					`service_disabled` smallint(1) NOT NULL ,
					`watchdog_features` smallint(1) NOT NULL ,
					`binpath` VARCHAR( 128 ) NULL ,
					`explain` VARCHAR( 128 ) NULL ,
					`running` smallint(1) NOT NULL ,
					`installed` smallint(1) NOT NULL ,
					`master_pid` smallint(5) NOT NULL ,
					`master_memory` INT(20) NOT NULL ,
					`master_cached_memory` INT(20) NOT NULL ,
					`processes_number` smallint(2) NOT NULL ,
					`uptime` VARCHAR( 128 ) NULL ,
					KEY `service_name` (`service_name`),
					KEY `service_disabled` (`service_disabled`),
					KEY `running` (`running`),
					KEY `installed` (`installed`),
					KEY `master_memory` (`master_memory`),
					KEY `uuid` (`uuid`),
					KEY `master_cached_memory` (`master_cached_memory`)
					) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->FIELD_EXISTS("global_status", "master_version")){
					$this->QUERY_SQL("ALTER TABLE `global_status` ADD `master_version` varchar(40)");
					if(!$this->ok){echo $this->mysql_error."\n";}
				}
				
				
			}
			
			
}
?>