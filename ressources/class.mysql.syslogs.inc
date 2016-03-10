<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.mount.inc');
include_once(dirname(__FILE__).'/class.mysql.squid.builder.php');

class mysql_storelogs{
	private $ClassSQL;
	private $LogRotatePath=null;
	public $MySQLSyslogType=1;
	private $mysql_errornum=0;
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	public $database="syslogs";
	private $mysql_connection=false;
	public $SocketName="";
	public $ok=false;
	public $mysql_error;
	public $last_id=0;
	public $dbidsn="";
	public $mysql_port=3306;
	private $NOCHDB=array("mysql"=>true);
	public $start_error=null;
	private $MySQLLogsTypeTextAR=array("{not_set}","{server}","{client}");
	public $MYSQLTypeText;
	public $EnableSyslogDB=0;
	private $filename=null;
	private $EnableSargGenerator=0;
	private $mySar=0;
	private $mySarBin=null;
	private $category=null;
	private $EnableMySQLSyslogWizard;
	private $BackupSquidLogsUseNas=0;
	private $LogRotateMysql=0;
	private $EnableArticaMetaClient=0;
	
	function mysql_storelogs(){
		$this->OutputDebugVerbose("Loading...");
		$this->PrepareMySQLClass();
		$sock=new sockets();
		if($GLOBALS["AS_ROOT"]){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
			$GLOBALS["CLASS_SOCKETS"]=$sock;
		}
		
		if(!function_exists("rotate_events")){include_once("/usr/share/artica-postfix/framework/class.unix.inc"); }
		$this->EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
		if(!is_numeric($this->EnableSargGenerator)){$this->EnableSargGenerator=0;}
		if(isset($GLOBALS["CLASS_UNIX"])){
			$sarg=$GLOBALS["CLASS_UNIX"]->find_program("sarg");
			if(strlen($sarg)<3){$this->EnableSargGenerator=0;}
			$this->mySarBin=$GLOBALS["CLASS_UNIX"]->find_program("mysar");
			if(strlen($this->mySarBin)>3){$this->mySar=1;}
			
		}
		
		$this->EnableMySQLSyslogWizard=intval($sock->GET_INFO("EnableMySQLSyslogWizard"));
		$this->EnableSyslogDB=intval($sock->GET_INFO("EnableSyslogDB"));
		$this->BackupSquidLogsUseNas=intval($sock->GET_INFO("BackupSquidLogsUseNas"));
		$this->LogRotateMysql=intval($sock->GET_INFO("LogRotateMysql"));
		$this->EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
		
		if($this->BackupSquidLogsUseNas==1){$this->MySQLSyslogType=3; $this->LogRotateMysql=0;}
		if($this->EnableArticaMetaClient==1){
			$this->LogRotateMysql=0;
			$this->MySQLSyslogType=4;
			$this->EnableSyslogDB=0;
		}
		
		
		
		if($this->EnableSyslogDB==0){return;}
		$this->OutputDebugVerbose("DATABASE_EXISTS($this->database) ?");
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->OutputDebugVerbose("CREATE_DATABASE($this->database) ?");
			$this->CREATE_DATABASE($this->database);
			$this->OutputDebugVerbose("CheckTables() ?");
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		if(isset($_SESSION["MYSQL_PARAMETERS"])){unset($_SESSION["MYSQL_PARAMETERS"]);}
		$this->MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
		$this->EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
		if(!is_numeric($this->EnableSyslogDB)){$this->EnableSyslogDB=0;}
		if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
		if($this->MySQLSyslogType==0){$this->MySQLSyslogType=4;}
		$this->LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($this->LogRotatePath==null){$this->LogRotatePath="/home/logrotate";}
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
		if(isset($this->MySQLLogsTypeTextAR[$this->MySQLSyslogType])){
			$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
		}
		
		$this->OutputDebugVerbose("MySQLSyslogType=$this->MySQLSyslogType");
		if($this->MySQLSyslogType==1){
			if($this->EnableSyslogDB==0){
				$this->MySQLSyslogType=4;
			}
		}
	
		if($this->MySQLSyslogType==1){
			if($this->EnableSyslogDB==1){
				if(isset($TuningParameters["ListenPort"])){ $ListenPort=$TuningParameters["ListenPort"]; }
				$def["SocketPath"]="/var/run/syslogdb.sock";
				if(!is_file("/var/run/syslogdb.sock")){return;}
				$this->SocketName=$def["SocketPath"];
				$def["mysql_admin"]="root";
				$def["mysql_password"]=null;
				$def["TryTCP"]=false;
				$def["mysql_port"]=$ListenPort;
				$this->SocketName=$def["SocketPath"];
				$this->mysql_admin=$def["mysql_admin"];
				$this->mysql_password=$def["mysql_password"];
				$this->mysql_port=$def["mysql_port"];
				$this->mysql_server="127.0.0.1";
				$this->ClassSQL=new mysql($def);
				$this->MYSQL_CMDLINES="--protocol=socket --socket={$def["SocketPath"]} -u root";
				$this->dbidsn="['DBI:mysql:database=$this->database;mysql_socket=$this->SocketName', '$this->mysql_admin', '$this->mysql_password']";
			}
		}
		
		
		if($this->MySQLSyslogType==2){
			$this->EnableSyslogDB=1;
			$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
			$username=$TuningParameters["username"];
			$password=$TuningParameters["password"];
			$mysqlserver=$TuningParameters["mysqlserver"];
			$RemotePort=$TuningParameters["RemotePort"];			
			$def["SocketPath"]=null;
			$def["mysql_admin"]=$username;
			$def["mysql_password"]=$password;
			$def["TryTCP"]=true;
			$def["mysql_port"]=$RemotePort;
			$def["mysql_server"]=$mysqlserver;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_port=$def["mysql_port"];
			$this->mysql_server=$mysqlserver;
			$this->ClassSQL=new mysql($def);
			$pwd=null;
			if($password<>null){ $pwd=" -p$password";}
			$this->MYSQL_CMDLINES="--protocol=tcp --user=$username$pwd --host=$mysqlserver --port=$RemotePort";
			$this->dbidsn="['DBI:mysql:database=$this->database;host=$mysqlserver;port=$RemotePort', '$username', '$password']";
		}
		
		if($this->MySQLSyslogType>=3){ return; }
		
		return $this->ClassSQL;
	}
	
	private function DATABASE_EXISTS($database){
		if($this->MySQLSyslogType>=3){ return; }
		if($database<>$this->database){$database=$this->database;}
		if(!method_exists($this->ClassSQL, "DATABASE_EXISTS")) { 
			return $this->THIS_DATABASE_EXISTS($this->database);
		}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	private FUNCTION CREATE_DATABASE($database){
		if($this->MySQLSyslogType>=3){ return; }
		if($database<>$this->database){$database=$this->database;}
		if(!method_exists($this->ClassSQL, "CREATE_DATABASE")) { 
			$this->OutputDebugVerbose("this->CREATE_DATABASE($this->database)");
			return $this->THIS_CREATE_DATABASE($this->database);
		}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	

	FUNCTION TABLE_SIZE($table){
		if($this->MySQLSyslogType>=3){ return; }
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];
	
	}
	
	
	public function GRANT_PRIVS($hostname,$username,$password){
		if($this->MySQLSyslogType>=3){ return; }
		$sql="SELECT User FROM user WHERE Host='$hostname' AND User='$username'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,"mysql"));
		if(trim($ligne["User"])==null){
			if(!$this->EXECUTE_SQL("CREATE USER '$username'@'$hostname' IDENTIFIED BY '$password';")){
				return false;
			}
				
				
			if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){
				return false;
			}
			$this->EXECUTE_SQL("FLUSH PRIVILEGES;");
			return true;
		}
	
		if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){return false;}
		$this->EXECUTE_SQL("FLUSH PRIVILEGES;");
		return true;
	}
	
	
	private function THIS_CREATE_DATABASE($database){
		if($this->MySQLSyslogType>=3){ return; }
		$this->OutputDebugVerbose("$database");
		$this->mysql_password=trim($this->mysql_password);
		
		
			if(!$this->BD_CONNECT()){
				writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
				return false;
			}
		
		
			if($GLOBALS["VERBOSE"]){echo " -> ->DATABASE_EXISTS($database)<br>\n";}
				if($this->DATABASE_EXISTS($database)){
					writelogs("CREATE_DATABASE $database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
					$this->ok=true;
					return true;
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
	
	
	private function THIS_DATABASE_EXISTS($database,$nocache=false){
		if($this->MySQLSyslogType>=3){ return; }
		$key=__CLASS__.__FUNCTION__;
		if(!$nocache){
			if(isset($_SESSION[$key][$database])){
				if($_SESSION[$key][$database]==true){return true;}
			}
		}
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION[$key][$database]=true;
				return true;
			}
		}
	
		return false;
	
	}

	public function THIS_BD_CONNECT($noretry=false,$called=null){
		if($this->MySQLSyslogType>=3){ return; }
		if($GLOBALS["VERBOSE"]){echo "THIS_BD_CONNECT()<br>\n";}
		
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection()<br>\n";}
		if($this->THIS_TestingConnection(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection() -> TRUE<br>\n";}
			return true;
		}
		return false;
	}
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	private function THIS_TestingConnection($noretry=false,$called=null){
		if($this->MySQLSyslogType>=3){ return; }
		if($this->mysql_connection){return true;}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		if($this->MySQLSyslogType==1){
			if($GLOBALS["VERBOSE"]){echo "mysql_connect -> /var/run/syslogdb.sock<br>\n";}
			
			if(!$this->is_socket("/var/run/syslogdb.sock")){
				$this->mysql_error="/var/run/syslogdb.sock no such socket";
				$this->OutputDebugVerbose("/var/run/syslogdb.sock !!! false");
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
			}
			
			$bd=@mysql_connect(":/var/run/syslogdb.sock","root",null);
			
			if($bd){
				$this->mysql_connection=$bd;
				$GLOBALS["THIS_TestingConnection"]=true;
				return true;
			}
			if($GLOBALS["VERBOSE"]){echo "mysql_connect /var/run/syslogdb.sock -> error<br>\n";}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->writelogs("mysql_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			$this->mysql_error="/var/run/syslogdb.sock Error:$errnum \"$des\"";
			return false;
		}
		
		if($this->MySQLSyslogType==2){
			
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($bd){$this->mysql_connection=$bd;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->writelogs("mysql_connect(\":/var/run/syslogdb.sock\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
			
	}
	
	public function BD_CONNECT(){
		if($this->MySQLSyslogType>=3){ return true; }
		$q=$this->PrepareMySQLClass();
		$this->ok=true;
		if($this->EnableSyslogDB==0){return true;}
		if(!method_exists($this->ClassSQL, "BD_CONNECT")) {
			$results=$this->THIS_BD_CONNECT();
			if($results){
				$GLOBALS[__CLASS__.__FUNCTION__]=true;
				return true;
			}
			$GLOBALS[__CLASS__.__FUNCTION__]=false;
			$this->ok=false;
			$this->writelogs("Fatal connection failed",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}else{
			$results=$this->ClassSQL->BD_CONNECT();
			if($results){
				$this->mysql_connection=$this->ClassSQL->mysql_connection;
				$GLOBALS[__CLASS__.__FUNCTION__]=true;
				return true;
			}
			$this->mysql_error=$this->ClassSQL->mysql_error;
			$GLOBALS[__CLASS__.__FUNCTION__]=false;
			return false;
		}
		
		$GLOBALS[__CLASS__.__FUNCTION__]=false;
		return false;
	
	}
	
	private function OutputDebugVerbose($text){
	if(!$GLOBALS["VERBOSE"]){return;}
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$filename=basename($trace[1]["file"]);
	$function=$trace[1]["function"];
	$line=$trace[1]["line"];
	echo "$filename:: $function $text in line $line<br>\n";
}
	
	private function THIS_COUNT_ROWS($table,$database){
		if($this->MySQLSyslogType>=3){ return true; }
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}

	public function COUNT_ROWS($table,$database=null){
		if($this->MySQLSyslogType>=3){ return true; }
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
	
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if(!method_exists($this->ClassSQL, "COUNT_ROWS")) {
			return $this->THIS_COUNT_ROWS($table,$database);
		}
		
		
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
		
	
	public function QUERY_SQL($sql,$database=null){
		if($this->MySQLSyslogType>=3){ return false; }
		if(!$this->BD_CONNECT()){return false;}
		if($this->EnableSyslogDB==0){return false;}
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if($database==null){$database=$this->database;}
		
		if(!method_exists($this->ClassSQL, "QUERY_SQL")) {
			if($GLOBALS["VERBOSE"]){echo "this->THIS_QUERY_SQL(....\n";}
			return $this->THIS_QUERY_SQL($sql,$database);
		}
		
		if($GLOBALS["VERBOSE"]){echo "this->ClassSQL->QUERY_SQL\n";}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
	
		@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error<hr>$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
	
		return true;
	}	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		if($this->MySQLSyslogType>=3){ return true; }
		$database=$this->database;
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}	
	
	public function SHOW_STATUS(){
		if($this->MySQLSyslogType>=3){ return true; }
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
	
	public function COUNT_ALL_TABLES(){
		if($this->MySQLSyslogType>=3){ return true; }
		if(!$this->BD_CONNECT()){return false;}
		$sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = '$this->database'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		return array($ligne["tcount"],$ligne["x"]);
	}	

	public function checkTables(){
		if($this->MySQLSyslogType>=3){ return true; }
		if(!$this->BD_CONNECT()){return false;}
		$sql="CREATE TABLE IF NOT EXISTS files_store (
  			ID  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  		   `filecontent` longblob NOT NULL
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS access_store (
  			ID  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  		   `filecontent` longblob NOT NULL
			) ENGINE=MYISAM;";	

		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS files_info (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`filename` varchar(128) NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`storeid` int unsigned NOT NULL,
				`taskid` int(10) NOT NULL,
				`filesize` BIGINT UNSIGNED NOT NULL,
				`filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				 KEY `filename` (`filename`),
				 KEY `hostname` (`hostname`),
				 KEY `taskid` (`taskid`,`filesize`),
				 KEY `filetime` (`filetime`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS accesslogs (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`filename` varchar(128) NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`storeid` int unsigned NOT NULL,
				`taskid` int(10) NOT NULL,
				`filesize` BIGINT UNSIGNED NOT NULL,
				`filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				 KEY `filename` (`filename`),
				 KEY `hostname` (`hostname`),
				 KEY `taskid` (`taskid`,`filesize`),
				 KEY `filetime` (`filetime`)
				) ENGINE=MYISAM;";
		
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS evnts (
				`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
				`zDate` datetime NOT NULL,
				`hostname` varchar(128) NOT NULL,
				`content` TEXT NOT NULL,
				`subject` varchar(255) NOT NULL,
				 KEY `zDate` (`zDate`),
				 KEY `hostname` (`hostname`)
				
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		if(!$this->ok){$this->start_error=$this->start_error."<br>".$this->mysql_error;}
		}		
	
	public function TABLE_EXISTS($table,$database=null){
		if($this->MySQLSyslogType>=3){ return true; }
		$this->BD_CONNECT();
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL("SHOW TABLES");
		if(!$this->ok){
			if($GLOBALS["VERBOSE"]){echo "SHOW TABLES -> $q->mysql_error\n";}
		}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$this->database"])){return true;}
		}
		
		return false;
	}

	public function DelteItem($ID){
		if($this->MySQLSyslogType>=3){ return true; }
		$sql="DELETE FROM files_store WHERE ID='$ID'";
		$this->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql *********\n";}
		if(!$this->ok){return false;}
		$sql="DELETE FROM files_info WHERE storeid='$ID'";
		$this->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql *********\n";}
		if(!$this->ok){return false;}
		return true;		
	}
	
	public function DelteAccessItem($ID){
		if($this->MySQLSyslogType>=3){ return true; }
		$sql="DELETE FROM access_store WHERE ID='$ID'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		$sql="DELETE FROM accesslogs WHERE storeid='$ID'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		return true;
	}	
	
	public function ExtractFile($filepath,$storeid){
		if($this->MySQLSyslogType>=3){ return true; }
		$sql="SELECT filecontent INTO DUMPFILE '$filepath' FROM files_store WHERE ID = '$storeid'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){
			if($GLOBALS["VERBOSE"]){echo " ******** $this->mysql_error *********\n";}
			return false;}
		return true;
	}
	
	public function ExtractAccessFile($filepath,$storeid){
		if($this->MySQLSyslogType>=3){ return true; }
		$sql="SELECT filecontent INTO DUMPFILE '$filepath' FROM access_store WHERE ID = '$storeid'";
		$this->QUERY_SQL($sql);
		if(!$this->ok){return false;}
		return true;
	}	
	
	private function ConvertGZToBzip($filesource){
		$t=time();
		$fromTime=time();
		$fileDest=str_replace(".gz", ".bz2", $filesource);
		$unix=new unix();
		$gunzip=$unix->find_program("gunzip");
		$bzip2=$unix->find_program("bzip2");
		$cmd="$gunzip --to-stdout \"$filesource\" | $bzip2 > \"$fileDest\"";
		shell_exec($cmd);
		if(!is_file($fileDest)){return null;}
		return $fileDest;
	}

	
	
	
	public function ROTATE_ACCESS_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$this->category="all";
		$this->filename=$filename;
		if(strpos($filename, "access.")>0){$this->category="proxy";}
		$taskid=0;
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		$size=@filesize($filename);
		$this->events("InjectFile_accesslog($filename) - $size Bytes");
		if(!$this->InjectFile_accesslog($filename, $sourceDate)){
			$this->events("InjectFile_accesslog($this->filename) RETURN FALSE, do not delete the file...");
			return false;
		}
		@unlink($this->filename);
		return true;
	}
	
	private function TestCompressed($filename=null,$destile=null){
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		$COMPRESSEDS["bz"]=true;
		
		if($filename<>null){
			if(!is_file($filename)){
				$this->events("$filename No such file");
				return false;
			}
			$this->filename=$filename;
		}else{
			if($this->filename<>null){
				$filename=$this->filename;
			}
		}
		
		if(!is_file($filename)){
			$this->events("$filename No such file");
			return false;
		}
		
		$size=@filesize($filename);
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		
		if(isset($COMPRESSEDS[$extension])){
			$this->events("$filename ( $size bytes ) Already compressed return $filename");
			return $filename;
		}
		
		$size=@filesize($filename);
		if(!isset($COMPRESSEDS[$extension])){
			$unix=new unix();
			if($destile==null){$destile=$filename.".gz";}
			$this->events("Compress $filename ( $size Bytes ) to $destile");
			if(!$unix->compress($filename, $destile)){
				$this->events("Compress $filename ( $size Bytes ) FAILED");
				return false;
			}
			$this->events("Removing $filename");
			$size=@filesize($filename);
			$this->events("New compressed file $destile ( $size bytes )");
			@unlink($filename);
			return $destile;
		}
	
		return $filename;
		
	}
	

	
	private function MySarExec($filename){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$q=new mysql_squid_builder();
		$NICE=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
		
		$q->PRIVILEGES("mysar",$GLOBALS["CLASS_UNIX"]->GetUniqueID());
		$f[]="username=mysar";
		$f[]="password=".$GLOBALS["CLASS_UNIX"]->GetUniqueID();
		$f[]="database=squidlogs";
		
		if($q->mysql_port<>3306){$q->EnableSquidRemoteMySQL=0;}
		if($q->EnableSquidRemoteMySQL==1){	
			echo "Build config Use remote MySQL server = $q->mysql_admin@$q->mysql_server::$q->mysql_port / $q->mysql_password\n";
			$f[]="server={$q->mysql_server}:$q->mysql_port";
		}else{
			echo "Build config Use Local MySQL server = $q->SocketName\n";
			$f[]="server=127.0.0.1";
			
		}
		
		$f[]="pidfile=/var/run/mysar.pid";
		@file_put_contents("/etc/mysar.conf", @implode("\n", $f));
		@mkdir("/usr/etc");
		@file_put_contents("/usr/etc/mysar.conf", @implode("\n", $f));
		
		
		
		$cmdline="$NICE $this->mySarBin --logfile $filename --stats --offline 2>&1";
		$results=array();
		$t=time();
		
		exec($cmdline,$results);
		$took=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($t,time());
		rotate_events("MySar executed on $filename took:$took",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $results);}
		
	}
	
	public function SargTask($filename){
		$basename=basename($filename);
		if($basename=="."){return;}
		if($basename==".."){return;}
		if(is_dir($filename)){return;}
		
		if(isset($GLOBALS["SARG_TASK"][$filename])){return $GLOBALS["SARG_TASK"][$filename];}
		if(!isset($GLOBALS["CLASS_UNIX"])){return;}
		
		if(!is_file("$filename")){
			$this->events("$filename no such file");
			return false;
		}
		
		$size=@filesize($filename);
		$this->events("$filename ( $size bytes )");
		
		if($size==0){return true;}
		
		
		
		if(preg_match("#ziproxy#", $basename)){
			$this->events("$basename is not an proxy access events",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$GLOBALS["SARG_TASK"][$filename]=true;
			return true;
		}
		
		if(!preg_match("#^access\.log#", $basename)){
			$this->events("$basename is not an proxy access events",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$GLOBALS["SARG_TASK"][$filename]=true;
			return true;
		}
		



		
		
		$md5=md5_file($filename);
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename FROM `sarg_performed` WHERE `md5file`='$md5'"));
		if($ligne["filename"]<>null){
			$this->events("$basename, already runned Sarg on $md5");
			rotate_events("$basename, already runned Sarg on $md5",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$GLOBALS["SARG_TASK"][$filename]=true;
			return true;
		}
		
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		
		if($extension=="bz2"){
			$this->events("Uncompress $filename ($size bytes) -> $filename.log");
			$bzip2=$GLOBALS["CLASS_UNIX"]->find_program("bzip2");
			rotate_events("Uncompress $filename",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			shell_exec("$bzip2 -dkc $filename > $filename.log");
			$filename="$filename.log";
		}
		
		if($extension=="gz"){
			$this->events("Uncompress $filename ($size bytes) -> $filename.log");
			rotate_events("Uncompress $filename",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$unix=new unix();
			if(!$unix->uncompress($filename, "$filename.log")){
				$this->events("Uncompress $filename -> Failed `{$GLOBALS["UNCOMPRESSLOGS"]}`");
				$GLOBALS["UNCOMPRESSLOGS"]=null;
			}
			$filename="$filename.log";
		}

		
		if(!is_file($filename)){
			$this->events("$filename.log no such file");
			$GLOBALS["SARG_TASK"][$filename]=false;
			return false;
		}
		if($this->mySar==1){
			$this->events("Running MySAR on $filename",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			if($GLOBALS["VERBOSE"]){echo "->MySAR\n";}
			$this->MySarExec($filename);
		}
		
		if($this->EnableSargGenerator==1){
			$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
			
			
		}
		$zDate=date("Y-m-d H:i:s");
		$sql="INSERT IGNORE INTO `sarg_performed` (`md5file`,`filename`,`zDate`) VALUES ('$md5','$basename','$zDate')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$GLOBALS["SARG_TASK"][$filename]=false;return false;}
		$GLOBALS["SARG_TASK"][$filename]=true;
		return true;
		
		
	}
	
	public function agregate_access_logs($filepath){
		if($filepath=="."){return false;}
		if($filepath==".."){return false;}
		if(is_dir($filepath)){return false;}
		$sock=new sockets();
		$this->events("Aggregate $filepath",__FUNCTION__,__LINE__);
		
		if(!is_file($filepath)){
			$this->events("$filepath no such file !",__FUNCTION__,__LINE__);
			$GLOBALS["agregate_access_logs"][$filepath]=false;
			return false;
		}
		if(isset($GLOBALS["agregate_access_logs"][$filepath])){
			return false;
		}
		$basename=basename($filepath);
		if(preg_match("#^access\.merged\.#",$basename)){return;}
		$LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
		if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
		$LogRotatePathWork="$LogRotatePath/merged";
		$BigMerged="$LogRotatePathWork/access.merged.log";

	
		
		$MERGED_FILES=unserialize(@file_get_contents("/etc/artica-postfix/accesslogs_merged.db"));
		

		
		@mkdir($LogRotatePathWork,0755,true);
		$md5file=md5_file($filepath);
		if(isset($MERGED_FILES[$md5file])){return;}
		$MERGED_FILES[$md5file]=true;
		$sep=">>";
		if(!is_file($BigMerged)){$sep=">";}
		if(is_file($BigMerged)){
			$size=@filesize($BigMerged);
			$size=$size/1024; // KB
			$size=$size/1024; //MB
			if($size>$SquidRotateMergeMaxFile){
				$this->events("$BigMerged will be rotated ( $size MB)",__FUNCTION__,__LINE__);
				@copy($BigMerged, "$LogRotatePath/work/access.merged.".time().".log");
				@unlink($BigMerged);
				$sep=">";
			}
		}
		
		$COMPRESSEDS["gz"]=true;
		$COMPRESSEDS["bz2"]=true;
		$COMPRESSEDS["bz"]=true;
		$size=@filesize($filepath);
		$extension = pathinfo($filepath, PATHINFO_EXTENSION);
		if(isset($COMPRESSEDS[$extension])){
			$this->events("$filepath already compressed, just save it",__FUNCTION__,__LINE__);
			$destfile="$LogRotatePathWork/".time().".$extension";
			@copy($filepath, $destfile);
			@file_put_contents("/etc/artica-postfix/accesslogs_merged.db", serialize($MERGED_FILES));
			return;
		}
		
		
		
		$unix=new unix();
		$cat=$unix->find_program("cat");
		
		shell_exec("$cat $filepath $sep $BigMerged");
		$size=@filesize($BigMerged);
		$size=$size/1024; // KB
		$size=$size/1024; //MB
		$this->events("$filepath has been merged to $BigMerged ( $size MB)",__FUNCTION__,__LINE__);
		@file_put_contents("/etc/artica-postfix/accesslogs_merged.db", serialize($MERGED_FILES));
		return;
		
	}
	
	public function FILE_SOURCE_TO_WORK($filepath){
		if($filepath=="."){return false;}
		if($filepath==".."){return false;}
		if(is_dir($filepath)){return false;}
		if(!is_file($filepath)){
			$this->events("$filepath no such file !");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		if(isset($GLOBALS["FILE_SOURCE_TO_WORK"][$filepath])){
			$this->events("$filepath aloready done, return  {$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]}");
			return $GLOBALS["FILE_SOURCE_TO_WORK"][$filepath];}
		
		$this->events("->TestCompressed($filepath) ");
		$filepath=$this->TestCompressed($filepath);
		if(!$filepath){
			$this->events("TestCompressed Return false");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		$sock=new sockets();
		$LogRotatePath=$sock->GET_INFO("LogRotatePath");
		if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
		if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
		$LogRotatePathWork="$LogRotatePath/work";
		@mkdir($LogRotatePathWork,0755,true);
		
		
		if(!is_dir($LogRotatePathWork)){
			$this->events("$LogRotatePathWork no such directory or permission denied !");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			return false;
		}
		$DirectorySource=dirname($filepath);
		
		$this->events("Source directory = $DirectorySource destination directory is `$LogRotatePathWork`");
		
		if($DirectorySource==$LogRotatePathWork){
			$this->events("Source directory is the same of destination `$LogRotatePathWork`");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$filepath;
			return $filepath;
		}
		
		if(is_link($DirectorySource)){
			$DirectorySource=@readlink($DirectorySource);
			$this->events("Source directory symbolic link = $DirectorySource");
			$this->events("Source directory = $DirectorySource destination directory is `$LogRotatePathWork`");
		}
		
		
		if($DirectorySource==$LogRotatePathWork){
			$this->events("Source directory is the same of destination `$LogRotatePathWork`");
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$filepath;
			return $filepath;
		}		

		
		$size=@filesize($filepath);
		$Basename=basename($filepath);
		
		$this->events("$filepath filename is `$Basename` size is ( $size bytes )");
		$DestFile="$LogRotatePathWork/$Basename";
		$this->events("Destination = `$DestFile`");
		
		
		if(is_file($DestFile)){
			$md5Dest=md5_file($DestFile);
			$md5Srource=md5_file($filepath);
			if($md5Dest==$md5Srource){
				$this->events("Destination Already exists, and is the same");
				@unlink($filepath);
				return $DestFile;
			}
			$this->events("Destination Already exists, create a new file");
			$ext = pathinfo($Basename, PATHINFO_EXTENSION);
			$basenameFF=str_replace(".$ext", "", $Basename);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestFile="$LogRotatePathWork/$basenameFF";
			$this->events("Desintation = `$DestFile`");
		}
		
		
		if(!@copy($filepath, $DestFile)){
			$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=false;
			$this->events("Copy $filepath -> $DestFile FAILED !!!");
			return false;
		}
		$sizeDest=@filesize($DestFile);
		$this->events("Copy $filepath -> $DestFile ( $sizeDest bytes ) SUCCESS");
		$GLOBALS["FILE_SOURCE_TO_WORK"][$filepath]=$DestFile;
		$this->events("Removing source $filepath");
		@unlink($filepath);
		return $DestFile;
		
		
	}
	
	
	
	public function ROTATE_TOMYSQL($filename,$sourceDate=null){
		$sock=new sockets();
		$unix=new unix();
		$taskid=0;
		$BackupSquidLogsUseNas=intval($sock->GET_INFO("BackupSquidLogsUseNas"));
		$this->filename=$filename;
		
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($this->filename));}
		
		
		$filename=$this->FILE_SOURCE_TO_WORK($filename);
		if(!$filename){return false;}
		$this->filename=$filename;
		
		
		if($GLOBALS["VERBOSE"]){echo "EnableSyslogDB: $this->EnableSyslogDB - BackupSquidLogsUseNas: $BackupSquidLogsUseNas\n";}
		
		if($this->MySQLSyslogType==4){
			if(!$this->InjectFileToDir($filename)){
				return false;
			}
			return true;
		}
		
		if($BackupSquidLogsUseNas==1){
			if(!$this->InjectFileToNas($filename, $sourceDate)){return false;}
			return true;
		}
		
		
		if($this->EnableSyslogDB==1){
			if($this->MySQLSyslogType>=3){return;}
			if(!$this->InjectFile($filename, $sourceDate)){return false;}
			@unlink($filename);
			return true;
		}
		
		
		$filesize=$unix->file_size($filename);
		$basenameFF=basename($basenameFF);
		$ToMySQL=false;
	
		if($this->LogRotateMysql==0){
			if(!$this->InjectFileToDir($filename)){
				return false;
			}
		}

		return false;
	}

	
	private function TransformName($filepath){
		$unix=new unix();
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));
		
		$LogRotatePathWork="$this->LogRotatePath/work";	
		@mkdir("$this->LogRotatePath/work",0755);
		if(!is_dir($LogRotatePathWork)){@mkdir($LogRotatePathWork,0777);}
		@chmod($LogRotatePathWork, 0777);
		$basenameFF=null;
		$DestinationFile="$LogRotatePathWork/$basename";
		if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
		if(is_file($DestinationFile)){
			$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
			$basenameFF=basename($DestinationFile);
			$basenameFF=str_replace(".$ext", "", $basenameFF);
			$basenameFF=$basenameFF.".".time().".$ext";
			$DestinationFile=str_replace(basename($DestinationFile), $basenameFF, $DestinationFile);
		}
		
		return $DestinationFile;
	}
	
	private function InjectFileToDir($filepath){
		$sock=new sockets();
		$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
		if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
		$dirname=dirname($filepath);
		if($dirname==$BackupMaxDaysDir){return true;}
		$basename=basename($filepath);
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		$destfile="$BackupMaxDaysDir/$basename";
		if(is_file($destfile)){
			$basename=str_replace(".$ext", time().".$ext", $basename);
			$destfile="$BackupMaxDaysDir/$basename";
		}
		if(!@copy($filepath, $destfile)){
			rotate_events("Failed to backup $filepath to $destfile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@unlink($destfile);
			return false;
		}
		rotate_events("Success backup $destfile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		@unlink($filepath);
		return true;
		
		
	}
	
	private function InjectFileToNas($filepath){
		if(is_dir($filepath)){
			$this->events("$filepath is a directory, skip it",__FUNCTION__,__LINE__);
			return;}
		
		$BaseName=basename($filepath);
		if($BaseName=="."){return;}
		if($BaseName==".."){return;}
		$size=@filesize($filepath);
		
		
		
		if($size==0){
			$this->events("$filepath 0 bytes, skip and return true",__FUNCTION__,__LINE__);
			rotate_events("$filepath 0 bytes, skip and return true",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return true;
		}
		
		$sock=new sockets();
		$users=new usersMenus();
		$unix=new unix();
		$myHostname=$unix->hostname_g();
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		
		if($BackupSquidLogsNASIpaddr==null){
			$this->events("Backup via NAS is disabled, skip",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}
		
		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			rotate_events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			
			if($BackupSquidLogsNASRetry==0){return;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				rotate_events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				return;
			}
				
		}
		
		$DirSuffix=null;
		
		if(preg_match("#ziproxy#", $BaseName)){
			$DirSuffix="/proxy_access";
		}
		if(preg_match("#access\.log#", $BaseName)){
			$DirSuffix="/proxy_access";
		}
		if(preg_match("#cache\.log#", $BaseName)){
			$DirSuffix="/proxy_service";
		}
		
		if(preg_match("#(syslog|daemon|user|)\.log#", $BaseName)){
			$DirSuffix="/system";
		}		
		$this->events("Hostname=$myHostname Suffix = $DirSuffix $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder",__FUNCTION__,__LINE__);
		$BackupMaxDaysDir="$mountPoint/artica-backup-syslog/{$myHostname}$DirSuffix";
		@mkdir("$BackupMaxDaysDir",0755,true);
		
		if(!is_dir($BackupMaxDaysDir)){
			$this->events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			rotate_events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			squid_admin_notifs("SYSLOG: FATAL $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__);
			system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$mount->umount($mountPoint);
			return false;
		}
		
		
		$t=time();
		@file_put_contents("$BackupMaxDaysDir/$t", time());
		if(!is_file("$BackupMaxDaysDir/$t")){
			$this->events("Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed",__FUNCTION__,__LINE__);
			rotate_events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			squid_admin_notifs("SYSLOG: FATAL $BackupMaxDaysDir permission denied",__FUNCTION__,__FILE__,__LINE__);
			system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$mount->umount($mountPoint);
			return false;
		}
		
		
		@unlink("$BackupMaxDaysDir/$t");
		
		$fileSizeSource=@filesize($filepath);
		
		$targetFile="$BackupMaxDaysDir/".basename($filepath);
		if(!copy($filepath, $targetFile)){
			$this->events("Fatal Failed to copy $filepath -> $targetFile");
			rotate_events("Failed to copy $filepath {$fileSizeSource}Bytes -> $targetFile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@unlink($targetFile);
			$mount->umount($mountPoint);
			echo "Failed to copy $filepath -> $targetFile\n";
			return;
		}
		
		$filesizeDest=@filesize($targetFile);
		
		
		if($fileSizeSource<>$filesizeDest){
			$this->events("Success to backup $targetFile to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__LINE__);
			$this->events("Size differ, source = {$fileSizeSource}Bytes destination = {$filesizeDest}Bytes");
			@unlink($targetFile);
			$mount->umount($mountPoint);
			return false;
		}
		$filesizeDest=$filesizeDest/1024;
		
		$this->events("Success to backup $targetFile ({$filesizeDest}KB)to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__LINE__);
		rotate_events("Success to backup $targetFile ({$filesizeDest}KB)to $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/artica-backup-syslog/$users->hostname",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		$this->events("Removing $filepath",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$mount->umount($mountPoint);
		return true;
		
		
	}
	
	
	
	public function InjectFile($filepath,$sourceDate=null){
		$unix=new unix();
		$sock=new sockets();
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}else{
				if(isset($trace[1])){
					$file=basename($trace[0]["file"]);
					$function=$trace[0]["function"];
					$line=$trace[0]["line"];
				}
			}
				
			$called="called by $file $function() in line $line";
				
		}
		
		if(is_dir($filepath)){return;}
		$BaseName=basename($filepath);
		if($BaseName=="."){return;}
		if($BaseName==".."){return;}
		if(is_link($filepath)){$filepath=@readlink($filepath);}
		
		if(preg_match("#^access#", $BaseName)){
			if(!$this->SargTask($filepath)){
				return false;
			}
			
		}
		
		$filepath=$this->FILE_SOURCE_TO_WORK($filepath);
		if(!$filepath){return false;}
		
		$FILE_SOURCE_TO_DELETE=$filepath;
		$SourceFile=$filepath;
		$DestinationFile=$filepath;
		$basenameFF=basename($filepath);
		$taskid=0;
		
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		$BackupSquidLogsUseNas=intval($sock->GET_INFO("BackupSquidLogsUseNas"));
		
	
		if($this->MySQLSyslogType>=3){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("MySQLSyslogType:3, Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => N.A.S");
			return true;
		}
		
		if($BackupSquidLogsUseNas==1){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("BackupSquidLogsUseNas=1, Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}	
			$this->events("Success to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
			return true;
		}
		
		
		if(preg_match("#^access\.log#", $basename)){
			if(!$this->InjectFile_accesslog($filepath, $sourceDate)){return false;}
			$this->events("Return true for $filepath",__FUNCTION__,__LINE__);
			return true;
		}
	
		
		$this->events("Info: Next destination \"$DestinationFile\"",__FUNCTION__,__LINE__);
	
		$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
		$filesize=$unix->file_size($DestinationFile);		
		
		$fcontent=null;
		
		
		if($GLOBALS["VERBOSE"]){echo "Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)\n";}
		$this->events("Injecting $basenameFF Task:$taskid ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)");
		
		$fcontent=mysql_escape_string2(@file_get_contents($DestinationFile));
		$sql = "INSERT INTO `files_store` (`filecontent`) VALUES( '$fcontent' )";
		$this->events("Info:INSERTING INTO files_store $this->mysql_server:$this->mysql_port ($this->SocketName)");
		
		$this->QUERY_SQL($sql);
		$fcontent=null;
		
		if(!$this->ok){
			@unlink($DestinationFile);
			$this->events("$q->mysql_error, $sql");
			return false;
		}	

		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($DestinationFile);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO files_info (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
		
		$this->QUERY_SQL($sql);
		if(!$this->ok){
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error, $sql\n";}
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM files_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
		
	}
	
	public function InjectFile_accesslog($filepath,$sourceDate=null){
		$basename=basename($filepath);
		if($basename=="."){return;}
		if($basename==".."){return;}
		
		
		$taskid=0;
		$unix=new unix();

		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}else{
				if(isset($trace[1])){
					$file=basename($trace[0]["file"]);
					$function=$trace[0]["function"];
					$line=$trace[0]["line"];
				}
			}
			$called="called by $file $function() in line $line";
		}
		
		$this->events("Info: $called");
		if(!is_file($filepath)){
			$this->events("$filepath no such file");
			return false;
		}
		
		$this->agregate_access_logs($filepath);
			
	
		
		
		$size=@filesize($filepath);
		if(!$this->SargTask($filepath)){
			$this->events("Info: `$filepath` ( $size bytes ) S.A.R.G process return false:$called",__FUNCTION__,__LINE__);
			return false;
		}
		
		
		
		$this->events("FILE_SOURCE_TO_WORK($filepath) ( $size bytes )");
		$filepath=$this->FILE_SOURCE_TO_WORK($filepath);
		if(!$filepath){return false;}
		$FILE_SOURCE_TO_DELETE=$filepath;
		$hostname=$unix->hostname_g();
		$basename=basename($filepath);
		$basenameFF=$basename;
		
		
		if($sourceDate==null){$sourceDate=date('Y-m-d H:i:s',filemtime($filepath));}
		$this->events("Info: want to backup $filepath ($sourceDate)",__FUNCTION__,__LINE__);
		
		if($this->MySQLSyslogType==3){
			if(!$this->InjectFileToNas($filepath)){
				$this->events("MySQLSyslogType:3 Failed to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => N.A.S",__FUNCTION__,__LINE__);
			return true;
		}
		
		if($this->MySQLSyslogType==4){
			if(!$this->InjectFileToDir($filepath)){
				$this->events("MySQLSyslogType:4, Failed to copy $filepath => LOCAL ( InjectFileToDir return false )",__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			$this->events("Success to copy $filepath => LOCAL",__FUNCTION__,__LINE__);
			return true;
		}
	
		if(!$this->TABLE_EXISTS("access_store")){$this->checkTables();}
	
	
		$filesize=@filesize($filepath);
		$this->events("InjectFile_accesslog:: Injecting $basename Task:$taskid  ($filesize bytes) $sourceDate (MySQLSyslogType:$this->MySQLSyslogType)",__FUNCTION__,__FILE__,__LINE__,$this->category,$GLOBALS["SCHEDULE_ID"]);
		
	
		if($this->MySQLSyslogType==1){
			$unix=new unix();
			if(!$unix->is_socket("/var/run/syslogdb.sock")){
				$this->events("InjectFile_accesslog: /var/run/syslogdb.sock no such socket...");
				return false;
			}
			
		}
		
		
		$fcontent=mysql_escape_string2(@file_get_contents($filepath));
		$sql = "INSERT INTO `access_store` (`filecontent`) VALUES( '$fcontent' )";
		$this->QUERY_SQL($sql);
		$fcontent=null;
	
		if(!$this->ok){
			@unlink("$filepath");
			$this->events("$q->mysql_error, $sql");
			@unlink($filepath);
			return false;
		}
	
		$ID=$this->last_id;
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){
			$this->events("$basenameFF: BAD ID = $ID");
			return false;
		}
		$this->events("$basenameFF: New ID = $ID");
		@unlink($filepath);
		$zmd5=md5("$hostname$basenameFF$taskid$filesize$sourceDate");
		$sql="INSERT INTO accesslogs (zmd5,filename,hostname,storeid,taskid,filesize,filetime)
		VALUES ('$zmd5','$basenameFF','$hostname','$ID','$taskid','$filesize','$sourceDate')";
	
		$this->QUERY_SQL($sql);
		if(!$this->ok){
				
			$this->events("$q->mysql_error, $sql");
			$this->QUERY_SQL("DELETE FROM access_store WHERE ID=$ID");
			return false;
		}
		$this->events("Success backuping $basenameFF $filesize bytes",__FUNCTION__,__LINE__,"logrotate");
		$this->events("removing \"$FILE_SOURCE_TO_DELETE\"");
		@unlink($FILE_SOURCE_TO_DELETE);
		return true;
	
	}	
	
	public function events($text,$function=null,$line=0){
		$pid=@getmypid();
		if($GLOBALS["PROGRESS"]){echo "$text [$function/$line]\n";}

		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				if($function==null){$function=$trace[1]["function"];}
				if($line==0){$line=$trace[1]["line"];}
			}
			
			if(isset($trace[0])){
				$file=basename($trace[0]["file"]);
				if($function==null){$function=$trace[0]["function"];}
				if($line==0){$line=$trace[0]["line"];}
			}
		}
		
		if(!isset($GLOBALS["CLASS_UNIX"])){
			if(class_exists("unix")){
				$GLOBALS["CLASS_UNIX"]=new unix();
			}
		}

		if(isset($GLOBALS["CLASS_UNIX"])){
			if(class_exists("unix")){
				$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/artica-postfix/logrotate.debug",false,__CLASS__."/$function",$line,$file);
			}
		}
		
	}
		
	function writelogs($text=null,$function=null,$line=0){
			$class=__CLASS__;
			$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
			@mkdir('/usr/share/artica-postfix/ressources/logs/web');
			if(!is_numeric($line)){$line=0;}
			if(function_exists("writelogs")){writelogs("$text (L.$line)","$class/$function",__FILE__,$line);}
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

		
	private function THIS_QUERY_SQL($sql,$database=null,$called=null){
		$database=trim($database);
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}

		if(!$this->BD_CONNECT(false,$called)){
				if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
				if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
				$this->ok=false;
				$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
				return false;
			}
		
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
		
		
		if($database<>null){
			$ok=@mysql_select_db($database,$this->mysql_connection);
				if (!$ok){
					$errnum=@mysql_errno($this->mysql_connection);
					$des=@mysql_error($this->mysql_connection);
					if($GLOBALS["VERBOSE"]){echo "mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
					$this->mysql_errornum=$errnum;
					$this->mysql_error=$des;
					$time=date('h:i:s');
				    $this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
				    $this->mysql_error="QUERY_SQL:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				    $this->ok=false;@mysql_close($this->mysql_connection);
				    return null;
				}
			}
			
			ini_set("mysql.connect_timeout",20);
			if($GLOBALS["VERBOSE"]){echo "mysql_query(.... Timeout:". ini_get("mysql.connect_timeout")."\n";}
			$results=mysql_query($sql,$this->mysql_connection);
			if(!$results){
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				
				if(preg_match('#Duplicate entry#',$des)){
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				
					$this->ok=true;
					@mysql_close($this->mysql_connection);
					return true;
				}				
				
				
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				@mysql_free_result($this->mysql_connection);
				
			}
		
		$this->ok=true;
		$this->last_id=@mysql_insert_id($this->mysql_connection);	
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		$this->mysql_connection=false;
		return $result_return;
		

		}
		
		
}
