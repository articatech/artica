<?php
if(!isset($GLOBALS["VERBOSE++"])){$GLOBALS["VERBOSE++"]=false;}
if(!isset($GLOBALS["FULL_DEBUG"])){$GLOBALS["FULL_DEBUG"]=false;}
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
include_once(dirname(__FILE__)."/class.highcharts.inc");
$squidBuilder=dirname(__FILE__)."/class.mysql.squid.builder.php";
if(function_exists("debug_mem")){debug_mem();}
if(is_file($squidBuilder)){include_once($squidBuilder);}
if(function_exists("debug_mem")){debug_mem();}
function QUERY_SQL($sql,$logfile=null,$notLog=0,$Database='artica_filter'){$s=new mysql();return $s->QUERY_SQL($sql,$Database);}
		
class mysql{
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $mysql_error;
	var $mysql_errornum=0;
	var $hostname;
	var $ok=false;
	var $CurrentPageName;
	var $mysql_connection;
	var $last_id;
	var $UseMysql=true;
	var $affected_rows=0;
	var $TestIngConnectionPerformed=false;
	var $SocketPath=null;
	var $SocketName="/var/run/mysqld/mysqld.sock";
	public $MYSQL_CMDLINES=null;
	var $TryTCP=true;
	var $mysql_affected_rows=0;
	
	
	function mysql($DefSettings=null){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		if($GLOBALS["FULL_DEBUG"]){
			$GLOBALS["DEBUG_PROCESS"]=true;
			$GLOBALS["VERBOSE++"]=true;
		}
		
		
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	  	$this->CurrentPageName=$phpPage;
	  	if(is_array($DefSettings)){
	  		while (list ($num, $line) = each ($DefSettings)){
	  			$this->$num=$line;
	  		}
	  	}else{
	  		$this->FillSettings();
	  	}
	  
		if(!$this->TestIngConnectionPerformed){$this->TestingConnection();}
			
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
		
		$ttA[]="-u $this->mysql_admin";
		
		if($this->mysql_password<>null){
			$ttA[]="-p".$this->shellEscapeChars($this->mysql_password);
		}
		
		if($this->mysql_server=="127.0.0.1"){
			$ttA[]="-S $this->SocketName";
		}else{
			$ttA[]="--protocol=tcp --host=$this->mysql_server --port=$this->mysql_port";
		}
		
		$this->MYSQL_CMDLINES=@implode(" ", $ttA);
	

	}
	
	private function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		$path=str_replace("|","\|",$path);
		return $path;
	}
	
	
	function IS_ERROR_DUMP_CMDLINE($results=array()){
		while (list ($index, $line) = each ($results)){
			if(preg_match("#error:\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if(preg_match("#ERROR\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if($GLOBALS["VERBOSE"]){echo "\"$line\" Unknown....line\n";}
		}

		return false;
		
	}
	
	
	public function TABLES_STATUS_CORRUPTED($database){
		$ARRAY=array();
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$Name=$ligne["Name"];
			$Comment=$ligne["Comment"];
			if(trim($Comment)==null){continue;}
			$ARRAY[$Name]=$Comment;
	
		}
		return $ARRAY;
	}
	
	
	private function DebugCredentials($line){
		
		writelogs("mysql_password: `$this->mysql_password`",__CLASS__."/".__FUNCTION__,__FILE__,$line);
		writelogs("mysql_admin: `$this->mysql_admin`",__CLASS__."/".__FUNCTION__,__FILE__,$line);
		writelogs("mysql_server: `$this->mysql_server`",__CLASS__."/".__FUNCTION__,__FILE__,$line);
		writelogs("mysql_port: `$this->mysql_port`",__CLASS__."/".__FUNCTION__,__FILE__,$line);
		writelogs("SocketPath: `$this->SocketPath`",__CLASS__."/".__FUNCTION__,__FILE__,$line);

		
	}
	
	
	function TestingConnection($noretry=false,$called=null){
		if(isset($GLOBALS["MYSQL1_TEST_CONNECTION_FAILED"])){return false;}
		$class=__CLASS__;
		$funcclass=__FUNCTION__."/".__CLASS__." in line:";
		$this->TestIngConnectionPerformed=true;
		$this->mysql_server=trim($this->mysql_server);
		$bd=false;
		ini_set("mysql.default_password", null);
		$SockPath="/var/run/mysqld/mysqld.sock";
		if($this->SocketPath<>null){
				$SockPath=$this->SocketPath;
				$this->mysql_server=="localhost";
		}
		
		
		
		
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called [ file=". basename($trace[1]["file"])." function={$trace[1]["function"]}() line={$trace[1]["line"]}";
				}
			}
		}
		
		
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			$this->mysql_error="Error, mysql_connect function does not exists, please reboot";
			return false;
		}
		
		if($this->mysql_server==null){$this->mysql_server="localhost";}
		if($this->mysql_server=="localhost.localdomain"){$this->mysql_server="localhost";}
		if($this->mysql_server=="127.0.0.1"){$this->mysql_server="localhost";}
		
		if(($this->mysql_server=="localhost")){$this->TryTCP=false;}
		if(!$this->TryTCP){if($this->mysql_server<>"localhost"){$this->TryTCP=true;}}
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		
		if($this->TryTCP){
			
			$this->SocketPath=null;
			$SockPath=null;
			if(!is_numeric($this->mysql_port)){$this->mysql_port=3306;}
			if($GLOBALS["VERBOSE"]){echo "$funcclass ". __LINE__." TryTCP = $this->mysql_server:$this->mysql_port\n";}
			if($this->mysql_port<5){$this->mysql_port=3306;}
			if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." @mysql_connect(\"$this->mysql_server:$this->mysql_port\",$this->mysql_admin,$this->mysql_password)<br>\n";}
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." @mysql_connect DONE<br>\n";}
			if($bd){
				$this->mysql_connection=$bd;
				return true;}
			$des=@mysql_error(); 
			$errnum=@mysql_errno();
			$this->writelogs("mysql_connect($this->mysql_server:$this->mysql_port,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			$this->mysql_error="$this->mysql_server:$this->mysql_port failed (N:$errnum) \"$des\" $called";
			if($errnum==1045){$this->DebugCredentials(__LINE__);}
		}
		
		
		if($SockPath<>null){
			ini_set("mysql.default_password", null);
			if(!$this->is_socket($SockPath)){
				if($GLOBALS["VERBOSE"]){echo "$funcclass ". __LINE__." $SockPath no such socket or permission denied\n";}
				$this->mysql_error="$SockPath no such socket or permission denied";
				return false;
			}
			
			if($SockPath=="/var/run/mysqld/mysqld.sock" && $this->mysql_server=="localhost" && $this->mysql_port==3306){
				if(is_file("/etc/artica-postfix/settings/Mysql/database_password")){
					$this->mysql_password=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password");
				}
			}
			
			$bd=@mysql_connect(":$SockPath",$this->mysql_admin,$this->mysql_password);
			if($bd){
				$this->mysql_connection=$bd;
				$this->ok=true;
				return true;
			}
			$des=@mysql_error(); $errnum=@mysql_errno();
			if($errnum==1045){$this->DebugCredentials();}
			if($GLOBALS["VERBOSE"]){echo "$funcclass ". __LINE__." FAILED, failed (N:$errnum) \"$des\" \n";}
			$this->mysql_error="$SockPath failed (N:$errnum) \"$des\" $called";
			$this->writelogs("mysql_connect(\":$SockPath\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
		}

		
		if(!$bd){
			
			if($errnum==1045){
				if(!$noretry){
					$this->mysql_admin="root";
					$this->mysql_password==null;
					ini_set("mysql.default_password", null);
					@mysql_close();
					return $this->TestingConnection(true);
				}
			}
			
			
    		
    		$this->mysql_error=$des;
    		$this->mysql_errornum=$errnum;
    		$GLOBALS["MYSQL1_TEST_CONNECTION_FAILED"]=true;
			return false;
		}
		
		return true;
		
	}
	
function writelogs($text=null,$function=null,$line=0){
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
		@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){writelogs("$text (L.$line)","$function",__FILE__,$line);}
		if(!$GLOBALS["VERBOSE"]){return;}
	    $logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
			if (is_file($logFile)) { 
		   			$size=filesize($logFile);
				    	if($size>1000000){unlink($logFile);}
		   		}
		
				$f = @fopen($logFile, 'a');
				$date=date("Y-m-d H:i:s");
				@fwrite($f, "$date:[$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
				@fclose($f);
			
		}

	FUNCTION TABLE_STATUS($table,$database){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$database));		
	}
	
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql,"artica_backup"));
		return $ligne["tdate"];
	}
	
	
	function DATABASE_STATUS($database){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		$results=$this->QUERY_SQL("SHOW TABLE STATUS",$database);
		$html="<table style='width:100%;margin:5px;border:1px solid #CCCCCC'>
		<tr>
				<td colspan=4 align='left' style='background-color:#CCCCCC'><strong style='font-size:14px'>$database</strong></td>
			</tr>";
		while ($row = mysql_fetch_array($results)) { 
 			$dbsize += $row['Data_length'] + $row['Index_length']; 
 			$html=$html."<tr>
 			<td width=1%><img src='img/fw_bold.gif'></td>
 			<td><strong>{$row["Name"]}</strong></td>
 			<td><strong>{$row["Rows"]}</strong></td>
 			<td><strong>" . FormatBytes($row["Data_length"]/1024)."</td>
 			</tr>";
			
			} 
			
			$html=$html . "
			<tr>
				<td colspan=4 align='right' style='background-color:#CCCCCC'><strong style='font-size:13px'>".FormatBytes($dbsize/1024)."</strong></td>
			</tr>
			
			</table>";
		return $html;
	}
	
	function REPAIR_TABLES($database){
		$this->BD_CONNECT();
		

  	 	$mysql_result = mysql_query("SHOW TABLES;",$this->mysql_connection);
 		while ($ligne = mysql_fetch_row($mysql_result)){
 			$table_name=$ligne[0];
 			echo "<div><code>Repair $database/$table_name</code></div>";
 			$this->REPAIR_TABLE($database,$table_name);
     	}
	}
	
	public function ReadBinaryFile($filepath){
		$filesize=filesize($filepath);
		$fileHandle = fopen($filepath, "r");
		$fileContent = fread($fileHandle, $filesize);
		$fileContent = addslashes($fileContent);
		return $fileContent;
		}
		
		
		
	public function test_mysql_connection(){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		
		return $this->BD_CONNECT();
	}
	
	public function EXECUTE_SQL($sql){
		@mysql_close();
		$this->BD_CONNECT();
		@mysql_unbuffered_query($sql,$this->mysql_connection);
		$errnum=@mysql_errno($this->mysql_connection);
		if($errnum<>0){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error query to with username $this->mysql_admin Number ($errnum) ($des)";
				writelogs("Error query to with username $this->mysql_admin Number `$errnum`, $this->mysql_error pass:".strlen($this->mysql_password)." length",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;				
		}
		
		@mysql_close($this->mysql_connection);		
		return true;
	}
	
	
	public function SET_NAMES_UTF8(){
		$this->BD_CONNECT();
		@mysql_query("SET NAMES UTF8",$this->mysql_connection);
		
		
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;
		}
		
		return true;
		
	}
	
	
	function MakeConnection($database){
		
		$users=new usersMenus();
				if(!$this->UseMysql){
					$q=new lib_sqlite();
					$a=$q->QUERY_SQL($sql,$database);
					$this->mysql_error=$q->sql_error;
					$this->ok=$q->ok;
					return $a;
				}
		
		
		$database=trim($database);
		$this->ok=true;
		if(!function_exists('mysql_connect')){
			$this->mysql_error="mysql_connect() function does not exists";
			$this->ok=false;
			return null;
		}
		
		$this->BD_CONNECT();
		
		if($database<>null){
			$ok=@mysql_select_db($database,$this->mysql_connection);
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error($this->mysql_connection);
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
				$page=$this->CurrentPageName;
				if($page=='obm.export.php' OR $page=="exec.obm.synchro.php"){
					echo "Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:". strlen($this->mysql_password)." password length\n";
					echo "Database selected=$database\n";
				}
	    		
	    		$this->writeLogs("MakeConnection:: DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:$this->mysql_password",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="MakeConnection:: mysql_installed={$_GLOBAL["mysql_installed"]}; [UseMysql:$this->UseMysql]: Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:". strlen($this->mysql_password)." password lenght";
	    		
	    		$this->ok=false;
	    		return false;
    		}
		}

		return true;
		
		
	}
	
	private function SendErrorOutput($text,$die=false){
		if($GLOBALS["AS_ROOT"]){return;}
		$currentpage=CurrentPageName();
		$NOOUT["logon.php"]=true;
		$NOOUT["admin.index.php"]=true;
		$NOOUT["admin.top.menus.php"]=true;
		$NOOUT["quicklinks.php"]=true;
		$NOOUT["admin.tabs.php"]=true;
		$NOOUT["admin.index.loadvg.php"]=true;
		$NOOUT["admin.index.status-infos.php"]=true;
		$NOOUT["system.internal.disks.php"]=true;
		$NOOUT["system.mysql.php"]=true;
		$NOOUT["admin.left.php"]=true;
		$NOOUT["Inotify.php"]=true;
		$NOOUT["SambaBrowse.php"]=true;
		if(isset($NOOUT[$currentpage])){return;}
		echo $text;
		if($die){die();}
		
	}
	
	public function BD_CONNECT($noretry=false,$called=null){
		if(isset($GLOBALS["MYSQL1_TEST_CONNECTION_FAILED"])){return false;}
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}		
		if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." TestingConnection()<br>\n";}
		if($this->TestingConnection(false,$called)){return true;}
		return false;
	}
	
	function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\t", " ", $text);
		$text=str_replace("  ", " ", $text);
		$text=str_replace("  ", " ", $text);
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("class.mysql.inc", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	
	
	function QUERY_SQL($sql,$database=null,$called=null){
		if(isset($GLOBALS["MYSQL1_TEST_CONNECTION_FAILED"])){return false;}
		$showMysqlQuery=null;
		$database=trim($database);
		$databasesKnown["artica_backup"]=true;
		$databasesKnown["artica_events"]=true;
		$databasesKnown["squidlogs"]=true;
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				
				if(isset($trace[1])){
					if(!isset($trace[1]["file"])){$trace[1]["file"]="NONE";}
					if(!isset($trace[1]["line"])){$trace[1]["line"]="NONE";}
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
				
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(!$this->UseMysql){
			if($GLOBALS["VERBOSE"]){echo "MySQL engine is not used -> lib_sqlite\n";}
			$q=new lib_sqlite();
			$a=$q->QUERY_SQL($sql,$database);
			$this->mysql_error=$q->sql_error;
			$this->ok=$q->ok;
			return $a;
		}
		
		if(!$this->BD_CONNECT(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL $this->mysql_admin@$this->mysql_server:$this->mysql_port \n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed called:$called",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
			return false;
		}
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("BD_CONNECT OK<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
		

		if($database<>null){
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_select_db  > $database<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$ok=@mysql_select_db($database,$this->mysql_connection);
			
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error($this->mysql_connection);
	    		if($GLOBALS["VERBOSE"]){
	    				echo "mysql_select_db [FAILED] \"$database\"  N.$errnum DESC:$des mysql/QUERY_SQL<br>\n";
	    				echo "mysql_select_db [FAILED] \"$database\"  config:$this->mysql_server:$this->mysql_port<br>\n";
	    				echo "mysql_select_db [FAILED] \"$database\"  $called<br>\n";
	    		}
	    		$this->mysql_errornum=$errnum;
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
	    		
	    			$page=$this->CurrentPageName;
						if($page=='obm.export.php' OR $page=="exec.obm.synchro.php"){
							echo "Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:****\n";
							echo "Database selected=$database\n";
						}
						
	    		if(preg_match("#Access denied for user#", $des)){
					unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
					unset($GLOBALS["MYSQL_PARAMETERS"]);
					unset($_SESSION["MYSQL_PARAMETERS"]);
					$des=" please retry again...\n".$des;
				}						
	    		
	    		$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="QUERY_SQL:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
	    		
	    		$this->ok=false;
	    		@mysql_close($this->mysql_connection);
	    		return null;
    		}
    		
    		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_select_db \"$database\" OK<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}	
		
		
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_query($sql)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$results=mysql_query($sql,$this->mysql_connection);
		
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_query() DONE...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		//if(preg_match("#Got error 134 from storage engine#i", $q->mysql_error)){
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			
			
			
			
			if(preg_match("#Got error 134 from storage engine#i", $des)){
				if(preg_match("\s+from\s+(.*?)\s+#i", $sql,$re)){
					$table=$re[1];
					if($this->TABLE_EXISTS($table, $database)){
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" try to repair table \"$table\"",__CLASS__.'/'.__FUNCTION__,__LINE__);
						mysql_query("REPAIR TABLE $table QUICK",$this->mysql_connection);
						$results=mysql_query($sql,$this->mysql_connection);
					}
				}
			}
			if($errnum==1548){
				$sock=new sockets();
				$sock->getFrameWork("mysql.php?mysql-upgrade=yes&instance-id=$this->instance_id");
				$this->mysql_error=$this->mysql_error."\nArtica as performed a mysql_upgrade, please retry later\n";
			}
			
			
			
		}
		
		
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			@mysql_free_result($this->mysql_connection);
			if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			if($des<>null){
				if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong><br><i>$called</i><br><code>$sql</code>";}}
			}
		}
	
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				@mysql_free_result($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}}
				if($GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."\n";}}
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error $this->mysql_server:$this->mysql_port ($this->SocketPath) $des $called ". basename(__FILE__)." on line ".__LINE__;
				if($database=="catz"){$this->ok=false;return false;}
				
				if(preg_match("#Incorrect key file for table '.\/(.+?)/(.+?)\.MYI'#",$des,$re)){		
					$this->mysql_error="Error $errnum $des\n$sql";
					if($database=="artica_events"){
						if(!isset($GLOBALS["DROPPED_TABLE"])){
							$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> remove it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
							$this->QUERY_SQL("DROP TABLE `$re[1]`",$database);
							$GLOBALS["DROPPED_TABLE"]=true;
							return false;
						}
					}
					
					
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					
				}
				
				if(preg_match("#Incorrect key file for table '(.+)'#",$des,$re)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->ok=false;
					$this->mysql_error="Error $errnum $des\n$sql";
					@mysql_close($this->mysql_connection);
					return false;
				}
				
				if(preg_match("#Can't find file: '(.+?)'#",$des,$re)){
					if(!$GLOBALS["AS_ROOT"]){
						
						if(isset($databasesKnown[$database])){
							$js="Loadjs('system.mysql.table.crashed.php?database=$database&table={$re[1]}')";
							$href="<a href=\"javascript:blur();\"
							OnClick=\"javascript:$js\" style='text-decoration:underline'>";
						}
						
					}
					
					if($GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){
						
						echo "Line:".__LINE__."::DB:$database Can't find file \"{$re[1]}\" is crashed REPAIR_TABLE_NOFILE_FOUND($database,$re[1]) -> Err.$errnum\n";
						
					}}
					
					$this->ok=false;
					$this->mysql_error="Error $errnum $des\n$sql";
					$this->ToSyslog("Error $errnum $des $sql");
					@mysql_close($this->mysql_connection);
					return false;
				}
				
				if(preg_match("#File '\./(.+?)\/(.+?)\.MYD' not found.*?Errcode: 13#i",$des,$re)){
					
					$database=$re[1];
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} file not found  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
					$this->ok=false;
					$this->mysql_error="Error $errnum $des\n$sql";
					$this->ToSyslog("Error $errnum $des $sql");
					@mysql_close($this->mysql_connection);
					return false;
				}
				
				if(preg_match("#File '\.\/(.+?)\/(.+?)\.MYD' not found#",$des,$re)){
					$database=$re[1];				
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} file not found  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
					$this->ok=false;
					$this->mysql_error="Error $errnum $des\n$sql";
					@mysql_close($this->mysql_connection);
					return false;					
				}
				
				if(preg_match("#Error writing file '\.\/(.+?)/(.+?)'#",$des,$re)){
					$database=$re[1];
					if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} Error writing file  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
					$this->ok=false;
					@mysql_close($this->mysql_connection);
					return false;
				}
				
				
							
				if(preg_match("#Table '(.+?)' is marked as crashed and should be repaired#",$des,$re)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->ok=false;
					$this->mysql_error="Error $errnum $des $sql Line:".__LINE__;
					@mysql_close($this->mysql_connection);
					return false;
				}
				
				if(preg_match("#Table '\.\/(.+?)\/(.+?)'\s+is marked as crashed and last#",$des,$re)){
					if(!$GLOBALS["AS_ROOT"]){
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} is crashed $errnum -> i will try to repair it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}else{
						if($this->myisamchk($database,$re[2])){
							$this->ok=true;$this->mysql_error=null;return $this->QUERY_SQL($sql,$database);
						}
					}
				}
				
				if(preg_match("#Access denied for user#", $des)){
					unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
					unset($GLOBALS["MYSQL_PARAMETERS"]);
					unset($_SESSION["MYSQL_PARAMETERS"]);
						
					$this->mysql_error=$this->mysql_error." please retry...";
					$this->ok=false;
					@mysql_close($this->mysql_connection);
					return;
				}
				
				if(preg_match("#Table\s+'\.\/(.+?)\/(.+?)'\s+is marked as crashed#",$des,$re)){
					echo "<p class=text-error>Line:".__LINE__."::". __CLASS__."::DB:$database Error table {$re[2]} is crashed $errnum : i will try to repair it (as root={$GLOBALS["AS_ROOT"]})\n<br>$called </p>\n";
					if(!$GLOBALS["AS_ROOT"]){
						$this->REPAIR_TABLE($re[1],$re[2]);
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it, $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}else{
						$this->myisamchk($database,$re[2]);
						die();	
					}
				}
				
				if(preg_match('#Duplicate entry#',$des)){
					if($GLOBALS["VERBOSE++"]){
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
					}
					$this->ok=true;
					@mysql_free_result($this->mysql_connection);
					@mysql_close($this->mysql_connection);
					return true;
				}

				if(preg_match("#Incorrect file format '(.+?)'#",$des,$re)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$table=trim($re[1]);
					if($table<>null){
						$this->ok=false;
						$this->mysql_error="Incorrect file format for table $table";
						$this->REPAIR_TABLE($database,$table);
						$this->mysql_error="Error $errnum $des\n$sql";
						return false;
						@mysql_close($this->mysql_connection);
					}
					@mysql_close($this->mysql_connection);
					return null;
				}
				

				if(preg_match('#/(.+?)/(.+?)\'\s+.+?crashed.+?(repaired|repair)#',$des,$re)){
					if(!preg_match('#REPAIR TABLE#',$sql)){
						$this->writeLogs("Try to repair {$re[2]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->mysql_error=$des." Artica answer Try to repair {$re[2]}";
						$this->REPAIR_TABLE($re[1],$re[2]);
						$this->ok=false;
						$this->mysql_error="Error $errnum $des\n$sql";
						@mysql_close($this->mysql_connection);
						return null;
					}
				}
				
				if(preg_match("#Table.+?quarantine.+?doesn't exist#",$des)){
						if(!$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]){
							$this->writeLogs("Try to build quarantine table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
							$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]=true;
							$this->Check_quarantine_table();
							$this->ok=true;
							@mysql_close($this->mysql_connection);
							return null;
						}
					
				}

				if(preg_match("#Table.+?storage.+?doesn't exist#",$des)){
							if(!$GLOBALS["CHECKMYSQLTABLE"]["storage"]){
								$GLOBALS["CHECKMYSQLTABLE"]["storage"]=true;
								$this->writeLogs("Try to build storage table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
								$this->check_storage_table();
								$this->ok=true;
								$this->mysql_error="Error $errnum $des\n$sql";
								@mysql_close($this->mysql_connection);
								return null;
							}
					
				}		

				if(preg_match("#Column count doesn.*?match value count#i", $des)){$showMysqlQuery="\n$sql";}	
				if($called==null){
					if(function_exists("debug_backtrace")){
						$trace=@debug_backtrace();
						if(isset($trace[1])){
							$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
						}
					}
				}
				
				$this->ToSyslog("Error $des call:$called \"$sql\"");
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $des call:$called \"$sql\" line:".__LINE__." of class.mysql.inc",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
	
				$this->affected_rows=@mysql_affected_rows($this->mysql_connection);
				
				if($errnum==1062){
					$this->ok=true;
					@mysql_free_result($this->mysql_connection);
					@mysql_close($this->mysql_connection);
					return true;
				}				
				
				$this->ok=false;
				@mysql_free_result($this->mysql_connection);
				@mysql_close($this->mysql_connection);
				return null;
				
				
				
		}	
		
	$this->last_id=@mysql_insert_id($this->mysql_connection);	
	$this->mysql_affected_rows=@mysql_affected_rows($this->mysql_connection);
	$this->ok=true;
	$result_return=$results;
	//@mysql_free_result($result); 
	@mysql_free_result($this->mysql_connection);
	@mysql_close($this->mysql_connection);
	return $result_return;
	}
	
	
	public function mysql_error_html(){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
		return "<p class=text-error>$this->mysql_error<br>$this->sql<br><i>$called</i></p>";
	
	}	
	
	function trace_to_string($array){
		while (list ($num, $ligne) = each ($array) ){
			try {
				if(is_object($ligne)){continue;}
				if(strpos("  $ligne", '/')>0){$ligne=basename($ligne);}
				if(is_array($ligne)){$r=@implode(" ", $ligne);unset($ligne);$ligne=$r;}
			} catch (Exception $e) {
			}
			
			$f[]="$num: $ligne";
		}
		return @implode("\n",$f);
	}
	
	function _mysql_close(){
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
	}
	
	
function myisamchk($db,$table){
	
	$keytask=md5(__FUNCTION__.__CLASS__.$db.$table);
	
	if(isset($GLOBALS[$keytask])){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::Already done";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: Already done...\n";}return;}
		
	$GLOBALS[$keytask]=true;		
		
		
	if($db==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::DB is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: db $db is null, aborting\n";}
		return;
	}
	
	if($table==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::table is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: table is null, aborting\n";}
		return;
	}
		
	$GLOBALS[md5(__FUNCTION__.__CLASS__.$db.$table)]=true;

	
	$sock=new sockets();
	$this->mysql_error=$this->mysql_error.base64_decode($sock->getFrameWork("cmd.php?myisamchk=yes&database=$db&table=$table"));
	
}	
	
	
function BackupTable($tablename,$database,$addons=array(),$zmd5=false,$endquery=null){
		 if(!$this->MakeConnection($database)){return false;}
		 $this->ok=true;
		 $query = "SELECT * FROM $tablename $endquery";
         $resData = mysql_query($query,$this->mysql_connection);
         $addons_num=count($addons);
         
         
		  
         if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("Exporting table $tablename Error Number ($errnum) ($des)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				writelogs("Exporting table $query (".mysql_num_rows($resData).")",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->ok=false;
				return false;
				
         }
         
         if (mysql_num_rows($resData) == 0){
         	writelogs("Exporting table $tablename no rows, aborting ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
         	return null;
         }
         
         writelogs("Exporting table $query (".mysql_num_rows($resData).")",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
         $num_fields = mysql_num_fields($resData);
          for($j=0; $j < $num_fields; $j++){
                  	$sFieldnames .= "`".mysql_field_name($resData, $j)."`,";
                  }
                  
                if($addons_num>0){
                	while (list ($key, $val) = each ($addons) ){$sFieldnames .= "`$key`,";}
                }
                
                if($zmd5){$sFieldnames .= "`zmd5`,";}
                  
                $sFieldnames = "(".substr($sFieldnames,0,-1).")";
               
            $sInsert = "INSERT INTO `$tablename` $sFieldnames VALUES\n";
            
            while($rowdata = @mysql_fetch_assoc($resData)){
            		$fields=array();
            		$fmd5=null;
 				while (list ($key, $val) = each ($rowdata)){
            			$fields[]="'".addslashes($val)."'";
            			if(strlen($val)<255){$fmd5=$fmd5.$val;}
            		}
            		
             if($addons_num>0){
             		reset($addons);
                	while (list ($key, $val) = each ($addons) ){
                			if(strlen($val)<255){$fmd5=$fmd5.$val;}
                			$fields[]="'".addslashes($val)."'";
                		}
                	
                }   

                if($zmd5){$fields[]="'".md5($fmd5)."'";}
            	$lines[]="(".@implode(",",$fields).")";
				}
				
			$export = "$sInsert".@implode(",\n",$lines)."\n";
				
            
            
            return $export;
                 
   //fclose($fp);
}	


	function REPAIR_TABLE_FILE_NOT_FOUND($database,$table){
		if($database=="catz"){return;}
		if($table=="icons_db"){
			$this->QUERY_SQL("DROP TABLE icons_db",$database);
			return;
		}
		
		
		$file="/var/lib/mysql/$database/$table.MYD";
		if($GLOBALS["AS_ROOT"]){return $this->REPAIR_TABLE_NOFILE_FOUND_root($database,"$table.MYD");}
		$sock=new sockets();
		$result=$sock->getFrameWork("mysql.php?mysql-fnfound=".base64_encode($file)."&db=$database&table=$table");
		if(!$GLOBALS["AS_ROOT"]){echo "<H2>Results:$result</H2><br>\n";}
		$this->writeLogs($result,__CLASS__.'/'.__FUNCTION__,__LINE__);
	}
	
	private function REPAIR_TABLE_NOFILE_FOUND_root($database,$filename){
		
		$this->writeLogs("REPAIR_TABLE_NOFILE_FOUND_root($database,$filename)",__CLASS__.'/'.__FUNCTION__,__LINE__);
		$tblf=basename($filename);
		if(!preg_match("#(.*?)\.[A-Za-z]+$#", $tblf,$re)){
			$this->writeLogs("Unable to preg_match $tblf ",__CLASS__.'/'.__FUNCTION__,__LINE__);
			return;
		}
		$table=$re[1];
		$this->writeLogs("$filename ($database/$table)",__CLASS__.'/'.__FUNCTION__,__LINE__);
		$unix=new unix();
		$datas=$unix->MYSQL_REPAIR_TABLE_FILE_NOT_FOUND($filename, $database, $table);
		if($GLOBALS["VERBOSE"]){echo $datas;}
		if(!is_file($filename)){
			$bb=basename($filename);
			$bc=explode(".",$filename);
			$table=$bc[0];
			if($database=="squidlogs"){
				$this->QUERY_SQL("DROP TABLE $table",$database);
				die();
			}
		}
	}
	
	function REPAIR_TABLE_NOFILE_FOUND($database,$filename){
		if($database=="catz"){return;}
		$table=$filename;
		$tblf=$filename;
		if(preg_match("#(.*?)\.[A-Za-z]+$#", $tblf,$re)){$table=$re[1];}
		if(preg_match("#Taskev[0-9]+#", $table)){
			$this->QUERY_SQL("DROP TABLE $table",$database);
			return;
		}
		
		
		$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$table);
	}

	
	function REPAIR_TABLE($database,$table){
		if(isset($GLOBALS["REPAIR_TABLE_{$database}_$table"])){return;} // Block the loop...
		$GLOBALS["REPAIR_TABLE_{$database}_$table"]=true;
		if(!$GLOBALS["AS_ROOT"]){echo "<li>REPAIR_TABLE: DB::$database, Table:$table</li>";}
		
		$sql="REPAIR TABLE $table use_frm";
		$sqlerror[]="REPAIR TABLE $table use_frm<br>\n";
		$this->BD_CONNECT();
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		
		
		$sql="REPAIR TABLE $table EXTENDED";
		$sqlerror[]="REPAIR TABLE $table EXTENDED";
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		
		$sqlerror[]="TABLE_STATUS...";
		$ligne=$this->TABLE_STATUS($table,$database);
		$failed=false;
		$sqlerror[]="TABLE_STATUS:Comment=`{$ligne["Comment"]}`..";
		$this->writeLogs("REPAIR_TABLE: TABLE_STATUS -> `{$ligne["Comment"]}`",__CLASS__.'/'.__FUNCTION__,__LINE__);
		if(preg_match("#is marked as crashed#",$ligne["Comment"])){$failed=true;}
		if(preg_match("#Incorrect file format#",$ligne["Comment"])){$failed=true;}
		
		if($failed){return;}
		$sqlerror[]="Should success...in line ". __LINE__." ,class:".__CLASS__;
		$sqlerror[]="Optimize table...";
		$sql="OPTIMIZE TABLE $table";
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		$this->mysql_error=@implode("<br>\n", $sqlerror);
		$GLOBALS["REPAIR_MYSQL_TABLE"]=$sqlerror;
		
	}
	
	private function REPAIR_TABLE_FAILED($database,$table){
		
		

		$this->BuildTables();
			if(function_exists("send_email_events")){
				$subject="Table $table in database $database definitively crashed (repaired)";
				$text[]="The Mysql table $table was crashed and the file $table.MYD was not stored on the disk";
				$text[]="In this case, datas was not recoverable.";
				$text[]="Artica has deleted the table and rebuild it.";
				$text[]="But no datas can be retrieved.";
				$text[]="If you want to recover datas, use your backup.";
				send_email_events($subject,@implode("\n",$text),"system");
			}
			if(function_exists("system_admin_events")){
				system_admin_events($subject."\n". @implode("\n",$text)."\n".@implode("\n", $GLOBALS["REPAIR_MYSQL_TABLE"]), __FUNCTION__, __FILE__, __LINE__, "mysql-error");
			}
			
		}
	
	function SIMPLE_GROUPWARE_ENABLED($userid){
		$database="SimpleGroupware";
		$sql="SELECT id,activated FROM simple_sys_users WHERE username='$userid' LIMIT 0,1";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if(!is_numeric($ligne["id"])){return false;}
		if($ligne["activated"]=="0"){return false;}
		return true;
		}
		
		
	function SET_SIMPLE_GROUPWARE_ACTIVE($userid,$active){
		$database="SimpleGroupware";
		$sql="SELECT id,activated FROM simple_sys_users WHERE username='$userid' LIMIT 0,1";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if(!is_numeric($ligne["id"])){
			$this->SIMPLE_GROUPWARE_USER_ADD($userid,$active);
			return true;}
			
		$this->SIMPLE_GROUPWARE_USER_EDIT($ligne["id"],$active);
		
	}
	
	function COUNT_DB_FROM_USER($user){
		$sql="SELECT COUNT(`Db`) as tcount FROM `db` WHERE `User`='$user'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,"mysql"));
		return $ligne["tcount"];
	}
	
	function DELETE_USER_INMYSQL($user){
		$this->BD_CONNECT();
		if($user==$this->mysql_admin){return;}
		if(!$this->mysql_connection){return;}
	    $results=@mysql_query("DROP USER '$user'@'localhost';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
		$results=@mysql_query("DROP USER '$user'@'127.0.0.1';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}

		$results=@mysql_query("DROP USER '$user'@'%';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
	}
	
	function SIMPLE_GROUPWARE_USER_EDIT($sql_id,$active){
		$database="SimpleGroupware";
		$sql="UPDATE simple_sys_users SET activated='$active' WHERE id='$sql_id'";
		$this->QUERY_SQL($sql,$database);
		
	}
	
	function SIMPLE_GROUPWARE_USER_ADD($userid,$active){
		$user=new user($userid);
		$md=md5($user->password);
		$database="SimpleGroupware";
		$sql="INSERT INTO `simple_sys_users` (
		`id`, 
		`username`, 
		`password`, 
		`email`, 
		`firstname`, 
		`lastname`, 
		`neverexp`, 
		`activated`)
		VALUES(
		'$user->uidNumber',
		'$userid',
		'$md',
		'$user->mail',
		'$user->givenName',
		'$user->sn',
		'1',
		'$active')";
		$this->QUERY_SQL($sql,$database);
		
	}
	
	function APACHE_TABLE_NAME($servername){
		$table_name="access_".str_replace(".", "_", $servername);
		return $table_name;
	}	
	
	
	function TABLE_EXISTS($table,$database,$nocache=false){
		$keyCache=__FUNCTION__;
		
		if(function_exists("debug_backtrace")){
			try {
				$trace=@debug_backtrace();
				if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			} catch (Exception $e) {$this->writeLogs("TABLE_EXISTS:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!$nocache){
			if(!isset($GLOBALS[$keyCache])){$GLOBALS[$keyCache]=array();}
			if(isset($GLOBALS[$keyCache][$database][$table])){if($GLOBALS[$keyCache][$database][$table]==true){return true;}}
			if(isset($_SESSION[$keyCache][$database][$table])){if($_SESSION[$keyCache][$database][$table]==true){return true;}}
		}
		
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
	
	
	function DATABASE_EXISTS($database,$nocache=false){
		if(!$nocache){
			if(isset($_SESSION["MYSQL_DATABASE_EXISTS"][$database])){
			if($_SESSION["MYSQL_DATABASE_EXISTS"][$database]==true){return true;}
			}
		}
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
	
	public function LIST_TABLES_CURL_HOUR(){
		if(isset($GLOBALS["LIST_TABLES_CURL_HOUR"])){return $GLOBALS["LIST_TABLES_CURL_HOUR"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE '%_curl'";
		$results=$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_curl$#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_CURL_HOUR"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$this->TIME_FROM_HOUR_GENERIC_TABLE($ligne["c"]);
			}
		}
		return $array;
	
	}
	
	public function LIST_TABLES_CURL_DAY(){
		if(isset($GLOBALS["LIST_TABLES_CURL_DAY"])){return $GLOBALS["LIST_TABLES_CURL_DAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE '%_dcurl'";
		$results=$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_dcurl$#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_CURL_DAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$this->TIME_FROM_HOUR_GENERIC_TABLE($ligne["c"]);
			}
		}
		return $array;
	
	}	
	
	private function TIME_FROM_HOUR_GENERIC_TABLE($tablename){
		preg_match("#([0-9]+)_[a-z]+$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		$xtime="$Cyear-$CMonth-$CDay $CHour:00:00";
	//	if($GLOBALS["VERBOSE"]){echo "$tablename -> $xtime\n";}
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}
	
	
	function PRIVILEGES_DELETE($database){
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed  \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}	
		return true;
	}
	
	
	function PRIVILEGES($user,$password,$database){
		$server_mysql_password=$this->mysql_password;
		$OrginalPassword=$this->mysql_password;
		$server_database=$database;
		$mysql_password=$password;
		$mysql_password_length=strlen($mysql_password);
		$sql="SELECT User FROM user WHERE User='$user'";
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));	
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$server_database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $server_database\"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}
	
		if($GLOBALS["VERBOSE"]){echo "PRIVILEGES:: $user FOUND = $userfound\n";}
		
		if($userfound==null){
			$sql="CREATE USER '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){
					echo "CREATE USER $user Failed with '$user'@'localhost' and password length $mysql_password_length: `$this->mysql_error`\n";
					$this->mysql_admin="root";
					$this->mysql_password=null;
					$this->EXECUTE_SQL($sql);
					$this->ok=true;
					if(!$this->ok){
						echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
						$this->mysql_admin="root";
						$this->mysql_password=$server_mysql_password;
						$this->ok=true;
						$this->EXECUTE_SQL($sql);
						if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
					}
					
			}
		}
	
	if($userfound==null){
		$sql="CREATE USER '$user'@'localhost' IDENTIFIED BY '$mysql_password';";
		$this->EXECUTE_SQL($sql);	
		if(!$this->ok){
			echo "CREATE USER $user Failed with '$user'@'localhost' and password length $mysql_password_length: `$this->mysql_error`\n";
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){
					echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
					return false;
				}			
			}
			
		}
		
	}
	
	if($userfound<>null){
		$sql="SET PASSWORD FOR '$user'@'localhost' = PASSWORD('$mysql_password');";
		if(!$this->EXECUTE_SQL($sql)){
			echo "UPDATE USER $user Failed with '$user'@'localhost' and password length $mysql_password_length `$this->mysql_error`\n";
			
		}
		
	}
	
	
	
	$server_database2=str_replace("_","\_",$server_database);
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'localhost' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}	
	
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	writelogs("success set privileges on $server_database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	$sql="FLUSH PRIVILEGES";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->ok=true;
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			
			if(!$this->EXECUTE_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->EXECUTE_SQL($sql,"mysql")){
					echo "$sql\n\n$q->mysql_error\n";
					return;
				}
			}			
		}			
	
		
	$this->mysql_password=$OrginalPassword;	
	return $this->check_write_privs($user,$password,$database);		
		
}

	private function check_write_privs($user,$password,$database){
		$OrginalPassword=$this->mysql_password;
		$write["Insert_priv"]=true;
		$write["Update_priv"]=true;
		$write["Delete_priv"]=true;
		$write["Create_priv"]=true;
		$write["Drop_priv"]=true;
		$write["Index_priv"]=true;
		$write["Alter_priv"]=true;
		$write["Create_tmp_table_priv"]=true;
		$write["Create_view_priv"]=true;
		$write["Show_view_priv"]=true;			
		$USERS=array();
		$sql="SELECT `User` FROM `db` WHERE `Db`='$database'";
		$results=$this->QUERY_SQL($sql,"mysql");
		if(@mysql_num_rows($results)>0){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$USERS[$ligne["User"]]=true;
			}
			
		}
		
		if(isset($USERS[$user])){return true;}
		while (list ($num, $none) = each ($write) ){
			$fileds[]="`$num`";
			$dd[]="'Y'";
		}
	
	
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','127.0.0.1',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','localhost',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}

	$sql="FLUSH PRIVILEGES";	
	$this->mysql_password=$OrginalPassword;
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$this->EXECUTE_SQL($sql,"mysql")){
		$this->mysql_admin="root";
		$this->mysql_password=$OrginalPassword;
		$this->ok=true;
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->ok=true;	
			if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}			
		}

		return true;
	}

	public function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}
		
		public function SHOW_STATUS(){
			$sql="SHOW STATUS;";
			$this->BD_CONNECT();
			$results=@mysql_query($sql,$this->mysql_connection);
			$errnum=@mysql_error($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			$this->mysql_error=$des;
		
		
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$Variable_name=$ligne["Variable_name"];
				$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}		
		
	function SET_VARIABLES($key,$value){
		$this->BD_CONNECT();
		$results=@mysql_query("SET GLOBAL $key = $value",$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		}	

		
	function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
		}		

		
	function DATABASE_LIST(){
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=$this->DATABASE_INFOS($Database);
			}
			return $array;
		}
		
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;

		}		
		
		
	function TABLES_LIST($database){
		$sql="SHOW TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize= $ligne['Data_length'] + $ligne['Index_length']; 
			$dbsize_text=ParseBytes($dbsize);
			$Name=$ligne["Name"];
			$Rows=$ligne["Rows"];
			$Max_data_length=$ligne["Max_data_length"];
			$array[$Name]=array($dbsize,$dbsize_text,$Rows,$Max_data_length);
			
		}
		
		return $array;
		
	}
	
	public function COUNT_TABLES($database){
		if(isset($GLOBALS["COUNT_TABLES$database"])){return count($GLOBALS["COUNT_TABLES$database"]);}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$database'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
			$GLOBALS["COUNT_TABLES$database"][$ligne["c"]]=$ligne["c"];
			
		}
		
		return count($array);
	}	
	
	
	private function DATABASE_INFOS($database){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		$count=0;
		$dbsize=0;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length']; 
			$count=$count+1;}
		return array($count,ParseBytes($dbsize));
	}
	
	
function mysql_real_escape_string2($query){
   $len=strlen($query);
    $escapeCount=0;
    $targetString='';
    for($offset=0;$offset<$len;$offset++) {
        switch($c=$query{$offset}) {
            case "'":
            // Escapes this quote only if its not preceded by an unescaped backslash
                    if($escapeCount % 2 == 0) $targetString.="\\";
                    $escapeCount=0;
                    $targetString.=$c;
                    break;
            case '"':
            // Escapes this quote only if its not preceded by an unescaped backslash
                    if($escapeCount % 2 == 0) $targetString.="\\";
                    $escapeCount=0;
                    $targetString.=$c;
                    break;
            case '\\':
                    $escapeCount++;
                    $targetString.=$c;
                    break;
            default:
                    $escapeCount=0;
                    $targetString.=$c;
        }
    }
    return $targetString;
} 
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	
	
	FUNCTION CREATE_DATABASE($database,$nocache=false){
		if(isset($GLOBALS["MYSQL1_TEST_CONNECTION_FAILED"])){return;}
		if(isset($GLOBALS["CREATE_DATABASE_$database"])){return;}
		
		$GLOBALS["CREATE_DATABASE_$database"]=true;
		if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
		if(!$nocache){
		if(!$this->BD_CONNECT()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: CREATE_DATABASE Connection failed\n";}
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		
		if($GLOBALS["VERBOSE"]){echo " -> ->DATABASE_EXISTS($database)<br>\n";}
		if($this->DATABASE_EXISTS($database)){
			writelogs("CREATE_DATABASE $database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}}
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
	
	FUNCTION CHECK_PERFORMANCES(){
		if(!function_exists("mysql_connect")){return 0;}
		$this->BD_CONNECT();
		$results=@mysql_query("SELECT benchmark(100000000,1+2);",$this->mysql_connection);
	}
	
	
	FUNCTION DELETE_DATABASE($database){
		if(!function_exists("mysql_connect")){$this->ok=false;return 0;}
		$this->ok=true;
		$this->BD_CONNECT();
		
		
		
		$results=@mysql_query("DROP DATABASE `$database`",$this->mysql_connection);	
		
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$this->ok=false;
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error Number ($errnum) ($des)$called";
				return false;

		}
		$this->ok=true;
		return true;
		
	}
	
	FUNCTION DELETE_TABLE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$database);
		$this->QUERY_SQL("FLUSH TABLES",$database);
		unset($GLOBALS["__MYSQL_TABLE_EXISTS"]);
		unset($_SESSION["__MYSQL_TABLE_EXISTS"]);
	}	
	
	
	FUNCTION COUNT_ROWS($table,$database){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}
		
	FUNCTION TABLE_SIZE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];		
		
	}
	
	
	public function LIST_TABLES_EVENTS_SYSTEM(){
		if(isset($GLOBALS["LIST_TABLES_EVENTS"])){return $GLOBALS["LIST_TABLES_EVENTS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE 'Taskev%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_EVENTS"]=$array;
		return $array;
		
	}	
	
	public function LIST_TABLES_ARTICA_BACKUP(){
		if(isset($GLOBALS["LIST_TABLES_ARTICA_BACKUP"])){return $GLOBALS["LIST_TABLES_ARTICA_BACKUP"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_backup'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_ARTICA_BACKUP"]=$array;
		return $array;
	
	}
	
	public function LIST_TABLES_POWERDNS(){
		if(isset($GLOBALS["LIST_TABLES_POWERDNS"])){return $GLOBALS["LIST_TABLES_POWERDNS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'powerdns'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_POWERDNS"]=$array;
		return $array;
	
	}	
	
	
	
	public function LIST_TABLES_ARTICA_SQUIDLOGS(){
		if(isset($GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS"])){return $GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS"]=$array;
		return $array;
	
	}	
	
	public function LIST_TABLES_ARTICA_OCSWEB(){
		if(isset($GLOBALS["LIST_TABLES_ARTICA_OCSWEB"])){return $GLOBALS["LIST_TABLES_ARTICA_OCSWEB"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'ocsweb'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_ARTICA_OCSWEB"]=$array;
		return $array;
	
	}	
	
	public function LIST_TABLES_EVENTS_SQUID(){
		if(isset($GLOBALS["LIST_TABLES_EVENTS_SQUID"])){return $GLOBALS["LIST_TABLES_EVENTS_SQUID"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE 'TaskSq%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_EVENTS_SQUID"]=$array;
		return $array;
		
	}		
	FUNCTION DATABASE_SIZE_BYTES($database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="SHOW table STATUS";
		$dbsize=0;
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
		}
		
		return $dbsize;
	
	}		
		
	FUNCTION DATABASE_SIZE($database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="SHOW table STATUS";
		$dbsize=0;
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
		}
		$dbsize=round(($dbsize/1024),2);
		if($dbsize>1000){
			$dbsize=round($dbsize/1000,1) . " Mb";
		}else{
			$dbsize=$dbsize . " kb";
		}
		return $dbsize;
		
	}
	
	
	
	FUNCTION INDEX_EXISTS($table,$index,$database){
		
		if(isset($_SESSION["MYSQL_INDEX_EXISTS"])){
			if(isset($_SESSION["MYSQL_INDEX_EXISTS"][$database])){
				if($_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]==true){
					return true;
				}
			}
		}
		$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	
	FUNCTION FIELD_EXISTS($table,$field,$database){
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
			$this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
			$this->writelogs("$sql",__FUNCTION__,__LINE__);
			return false;
		}
		
		
	}
	
	
	
	

	
	
	function FIELD_TYPE($table,$field,$database){
		if(isset($GLOBALS["__FIELD_TYPE"])){
			if(isset($GLOBALS["__FIELD_TYPE"][$database][$table][$field])){
				if($GLOBALS["__FIELD_TYPE"][$database][$table][$field]<>null){return $GLOBALS["__FIELD_TYPE"][$database][$table][$field];}
			}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		$GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
		return strtolower($ligne["Type"]);
	}
	
	
	FUNCTION CREATE_INDEX($table,$index,$database){
		$sql="ALTER TABLE `$table` ADD INDEX ( `$index` )";
		$this->QUERY_SQL($sql,$database);
	}
	
	
	private function Check_mldonkey_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_mldonkey_table();
	}
	
function check_vps_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_vps_tables();	
	}

	function check_networks_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_networks_tables();	
	}
	
	function check_postfwd2_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->check_postfwd2_table();
		if(!$q->ok){$this->ok=false;$this->mysql_error=$q->mysql_error;}
		}	
	
	function Check_iptables_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_iptables_table();
	}
	
	
	function Check_smtp_logs_table(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->Check_smtp_logs_table();	
	}

	public function CheckTablesAwstats(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTablesAwstats();
	}
	
	
	public function CheckTablesPostfilter(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTablesPostfilter();
	}
	public function checkTablesSamba(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkTablesSamba();
	}	
	
	
	
	public function checkRoundCubeTables($database){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkRoundCubeTables($database);	
	}
	
	
	
	private function checkAgentTables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->checkAgentTables();		
	}
	
	private function CheckTableWebsites(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableWebsites();			
	}
	
	
	private function CheckTableVirus(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableVirus();			
		
	}
	
	public function check_emailing_tables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_emailing_tables();		
	}
	
	
	public function CheckTableEmailingContacts($tablename,$merged=array()){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableEmailingContacts($tablename,$merged);
		
	}
	
	
	public function CheckTableThinClient(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTableThinClient();
	}
	
	private function CheckTableIMAP(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableIMAP();	
	}

	private function CheckTableEvents(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		$q->CheckTableEvents();	
	}
	
	private function CheckOutOfOfficeTable(){
			include_once(dirname(__FILE__).'/class.mysql.builder.inc');
			$q=new mysql_builder();
			return $q->CheckOutOfOfficeTable();	
	}
	
	public function CheckTables_gluster(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->CheckTables_gluster();	
	}
	
	
	
	function BuildTables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->BuildTables();		
	}
	
	function BuildNetworkTables(){
		include_once(dirname(__FILE__).'/class.mysql.builder.inc');
		$q=new mysql_builder();
		return $q->check_networks_tables();		
		
	}
	
	public function check_storage_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->check_storage_table(true);
	}
	
	
	function Check_reports_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->Check_reports_table();
	}
	
	
	function Check_quarantine_table(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->Check_quarantine_table();
	}
	
	
	function check_mysql_dbtables(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->check_mysql_dbtables();		
		
	}
	
	
	private	function CheckTables_repositories(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_repositories();	
	}
	
	public function CheckTables_openvpn(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_openvpn();	

	}
	
	
	private function  CheckTables_qos(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_qos();
	}
	
	
	private function CheckTables_dar(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_dar();
	}
	
	public function CheckTable_dansguardian(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTable_dansguardian();
	}
	
	public function CheckTablesSquid(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTable_dansguardian();
	}
	
	private function squid_events_sites_day_patch(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->squid_events_sites_day_patch();
	}
	
	private function squid_events_sites_day($check=true){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->squid_events_sites_day($check);		
	} 
	function CheckTables_rsync(){
		include_once(dirname(__FILE__)."/class.mysql.builder.inc");
		$q=new mysql_builder();
		return $q->CheckTables_rsync();		
	}
}

function mysql_escape_string2($line){
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function mysql_escape_line_query($ligne,$removeQuotes=false){
	while (list ($key, $val) = each ($ligne)){
		if($removeQuotes){$ligne[$key]=str_replace("'", "", $ligne[$key]);}
		$ligne[$key]=mysql_escape_string2($ligne[$key]);
	}
	reset($ligne);
	return $ligne;
}

?>