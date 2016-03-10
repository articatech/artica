<?php
if(isset($_SESSION["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!isset($GLOBALS["FULL_DEBUG"])){$GLOBALS["FULL_DEBUG"]=false;}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.simple.image.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.highcharts.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
ini_set("mysql.connect_timeout",60);

class mysql_squid_reports{

	public $ok=false;
	public $mysql_error;
	public $UseMysql=true;
	public $database="squidlogs";
	public $mysql_server="127.0.0.1";
	public $mysql_admin;
	public $mysql_password;
	public $mysql_port;
	public $MysqlFailed=false;
	public $mysql_connection;
	public $EnableRemoteStatisticsAppliance=0;
	public $EnableRemoteSyslogStatsAppliance=0;
	private $squidEnableRemoteStatistics=0;
	private $sql;
	public $DisableArticaProxyStatistics=0;
	public $EnableSquidRemoteMySQL=0;
	public $ProxyUseArticaDB=0;
	public $UseStandardMysql=false;
	public $EnableSargGenerator=0;
	public $last_id;

	public $SquidActHasReverse=0;
	public $SocketName="/var/run/mysqld/mysqld.sock";
	public $SocketPath="";
	private $NOCHDB=array("mysql"=>true);
	public $MYSQL_CMDLINES=null;
	public $MYSQL_DATA_DIR="/var/lib/mysql";
	public $mysql_affected_rows;
	public $MySQLConnectionType=0;
	private $BD_CONNECT_ERROR;
	private $PDO_DSN;
	
	function mysql_squid_reports($report_id){
		if(function_exists("getLocalTimezone")){
			@date_default_timezone_set(getLocalTimezone());
		}
		
		$this->database="squidreport_$report_id";

		if(!isset($GLOBALS["DEBUG_SQL"])){$GLOBALS["DEBUG_SQL"]=false;}
	
		
		
		$this->PrepareMySQLClass();
		
		if(!$this->DATABASE_EXISTS($this->database)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: Patching tables -> $this->database...\n";}
			$this->CREATE_DATABASE($this->database);
			$this->CheckTables();
		}
		

		
		if(!$this->TestingConnection()){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: TestingConnection -> FAILED Stamp MySQL to FAILED\n";}
			$this->MysqlFailed=true;
		}
		
	}
	
	public function time_to_date($xtime,$time=false){
		if(!class_exists("templates")){return;}
		$tpl=new templates();
		$dateT=date("{l} {F} d",$xtime);
		if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
		return $tpl->_ENGINE_parse_body($dateT);
	
	}
	
	public function mysql_error_html(){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}		
		return "<p class=text-error>$this->mysql_error<br>$this->sql<br><i>$called</i></p>";
		
	}
	
	function QUERY_CSV($query, $filename) {
		
		@mysql_close($this->mysql_connection);
		if(!$this->BD_CONNECT(false)){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$this->database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->BD_CONNECT_ERROR ." Error, unable to connect to MySQL server";
			$this->ToSyslog($this->mysql_error);
			return false;
		}
		
		
		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if(!is_numeric($errnum)){
				if($GLOBALS["VERBOSE"]){echo "mysql_select_db/$this->database/".__LINE__."  [FAILED] error $errnum $des -> RESTART !!\n";};
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				$this->BD_CONNECT(false,$called);
				$ok=@mysql_select_db($this->database,$this->mysql_connection);
				if (!$ok){
					if($GLOBALS["VERBOSE"]){echo "mysql_select_db/$this->database/".__LINE__." [FAILED] -> SECOND TIME !!\n";};
					$this->ok=false;
					return false;
				}
			}
		}
		
		
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if($GLOBALS["VERBOSE"]){echo "mysql_select_db/$this->database/".__LINE__." [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			if($GLOBALS["VERBOSE"]){echo "mysql -u $this->mysql_admin -p$this->mysql_password -h $this->mysql_server -P $this->mysql_port -A $this->database\n";}
			$this->mysql_errornum=$errnum;
			$this->mysql_error=$des;
			$time=date('h:i:s');
			$this->writeLogs("Line:".__LINE__.":mysql_select_db DB:\"$this->database)\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->mysql_error="Line:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
			$this->ok=false;
			$this->ToSyslog($this->mysql_error);
			$this->ToSyslog($query);
			@mysql_close($this->mysql_connection);
			$this->mysql_connection=false;
			return null;
		}		
		
		 
		$fp = fopen($filename, 'w');
		$results = @mysql_query($query, $this->mysql_connection);
		 
		
		$row = @mysql_fetch_assoc($results);
		if($row) {
			fputcsv($fp, array_keys($row));
			mysql_data_seek($results, 0);
		}
		
		 
		while($row = mysql_fetch_assoc($results)) {
			fputcsv($fp, $row);
		}
		 
		@fclose($fp);
		@mysql_close($this->mysql_connection);
	}	
	
	
	
	public function GRANT_PRIVS($hostname,$username,$password){
		$this->BD_CONNECT();
		$ok=@mysql_select_db("mysql",$this->mysql_connection);
		if (!$ok){
				if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_errornum=$errnum;
				$this->mysql_error="Error Number ($errnum) ($des)";
				$this->ok=false;
				return false;
			}
			
			
		$sql="SELECT User FROM user WHERE Host='$hostname' AND User='$username'";
		$ligne=@mysql_fetch_array(@mysql_query($sql,$this->mysql_connection));
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
	
	private function PrepareMySQLClassMemory(){
		if(!isset($GLOBALS["PrepareMySQLClassMemory"])){
			if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
			if(!class_exists("usersMenus")){include_once(dirname(__FILE__)."/class.users.menus.inc");}
			$sock=new sockets();
			
			$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]=$sock->GET_INFO("ChangeMysqlDir");
			if($GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]==null){$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]="/var/lib/mysql";}
			$GLOBALS["PrepareMySQLClassMemory"]["squidEnableRemoteStatistics"]=$sock->GET_INFO("squidEnableRemoteStatistics");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteStatisticsAppliance"]=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"]=$sock->GET_INFO("EnableSquidRemoteMySQL");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteSyslogStatsAppliance"]=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"]=$sock->GET_INFO("squidRemostatisticsServer");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"]=$sock->GET_INFO("squidRemostatisticsPort");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"]=$sock->GET_INFO("squidRemostatisticsUser");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"]=$sock->GET_INFO("squidRemostatisticsPassword");
			$GLOBALS["PrepareMySQLClassMemory"]["SquidActHasReverse"]=$sock->GET_INFO("SquidActHasReverse");
			$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"]=$sock->GET_INFO("ProxyUseArticaDB");
			$GLOBALS["PrepareMySQLClassMemory"]["DisableArticaProxyStatistics"]=$sock->GET_INFO("DisableArticaProxyStatistics");
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::ProxyUseArticaDB = {$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"]}\n";}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::EnableSquidRemoteMySQL = {$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"]}\n";}
			$GLOBALS["PrepareMySQLClassMemory"]["DisableLocalStatisticsTasks"]=$sock->GET_INFO("DisableLocalStatisticsTasks");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableSargGenerator"]=$sock->GET_INFO("EnableSargGenerator");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableKerbAuth"]=$sock->GET_INFO("EnableKerbAuth");
			$GLOBALS["PrepareMySQLClassMemory"]["UseDynamicGroupsAcls"]=$sock->GET_INFO("UseDynamicGroupsAcls");
			
			
		}
		
		$this->squidEnableRemoteStatistics=$GLOBALS["PrepareMySQLClassMemory"]["squidEnableRemoteStatistics"];
		$this->EnableRemoteStatisticsAppliance=$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteStatisticsAppliance"];
		$this->EnableSquidRemoteMySQL=$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"];
		$this->EnableRemoteSyslogStatsAppliance=$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteSyslogStatsAppliance"];
		$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
		$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
		$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
		$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
		$this->SquidActHasReverse=$GLOBALS["PrepareMySQLClassMemory"]["SquidActHasReverse"];
		$this->ProxyUseArticaDB=$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"];
		$this->DisableLocalStatisticsTasks=$GLOBALS["PrepareMySQLClassMemory"]["DisableLocalStatisticsTasks"];
		$this->DisableArticaProxyStatistics=$GLOBALS["PrepareMySQLClassMemory"]["DisableArticaProxyStatistics"];
		$this->EnableSargGenerator=$GLOBALS["PrepareMySQLClassMemory"]["EnableSargGenerator"];
		$this->EnableKerbAuth=$GLOBALS["PrepareMySQLClassMemory"]["EnableKerbAuth"];
		$this->UseDynamicGroupsAcls=$GLOBALS["PrepareMySQLClassMemory"]["UseDynamicGroupsAcls"];
		
		if(!is_numeric($this->EnableSquidRemoteMySQL)){$this->EnableSquidRemoteMySQL=0;}
		if(!is_numeric($this->squidEnableRemoteStatistics)){$this->squidEnableRemoteStatistics=0;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->EnableRemoteSyslogStatsAppliance)){$this->EnableRemoteSyslogStatsAppliance=0;}
		if(!is_numeric($this->ProxyUseArticaDB)){$this->ProxyUseArticaDB=0;}
		if(!is_numeric($this->DisableLocalStatisticsTasks)){$this->DisableLocalStatisticsTasks=0;}
		if($this->EnableRemoteStatisticsAppliance==1){$this->squidEnableRemoteStatistics=0;}
		if(!is_numeric($this->DisableArticaProxyStatistics)){$this->DisableArticaProxyStatistics=0;}
		if(!is_numeric($this->EnableSargGenerator)){$this->EnableSargGenerator=0;}
		if(!is_numeric($this->SquidActHasReverse)){$this->SquidActHasReverse=0;}
		
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=0;}
		
		if($GLOBALS["DEBUG"]){
			echo "squidEnableRemoteStatistics=$this->squidEnableRemoteStatistics\n";
			echo "EnableRemoteStatisticsAppliance=$this->EnableRemoteStatisticsAppliance\n";
			echo "EnableSquidRemoteMySQL=$this->EnableSquidRemoteMySQL\n";
		}
		
	}
	
	
	
	private function PrepareMySQLClass(){
		
		$this->PrepareMySQLClassMemory();
		$this->MYSQL_DATA_DIR=$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"];
		if($this->MYSQL_DATA_DIR==null){$this->MYSQL_DATA_DIR="/var/lib/mysql";}
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		if(isset($_SESSION["MYSQL_PARAMETERS"])){
		unset($_SESSION["MYSQL_PARAMETERS"]);
		}

		
		
		
		if($this->EnableSquidRemoteMySQL==1){
			
			$pass=null;
			$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
			$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
			$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
			$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
			$def["mysql_admin"]=$squidRemostatisticsUser;
			$def["mysql_password"]=$squidRemostatisticsPassword;
			$def["mysql_port"]=$squidRemostatisticsPort;
			$def["mysql_server"]=$squidRemostatisticsServer;
			$def["MySQLConnectionType"]=2;
			$def["SocketPath"]=null;
			$def["TryTCP"]=true;
			$this->mysql_admin=$squidRemostatisticsUser;
			$this->mysql_password=$squidRemostatisticsPassword;
			$this->mysql_port=$squidRemostatisticsPort;
			$this->mysql_server=$squidRemostatisticsServer;
			$this->MySQLConnectionType=2;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			$this->UseStandardMysql=false;
			
			
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__.":: squidRemostatisticsUser =$squidRemostatisticsUser\n";}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__.":: squidRemostatisticsPassword =$squidRemostatisticsPassword\n";}
			
			if(strlen($squidRemostatisticsPassword)>1){
				$mysql_password=$this->shellEscapeChars($squidRemostatisticsPassword);
				$pass=" -p$mysql_password";
			}
			$this->MYSQL_CMDLINES="--protocol=tcp --host=$squidRemostatisticsServer --port=$squidRemostatisticsPort -u $squidRemostatisticsUser$pass";
			
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$def["mysql_admin"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$def["mysql_password"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$def["SocketPath"];
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=$def["TryTCP"];
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;			
			
			return;			
		}
		
		
		if($this->squidEnableRemoteStatistics==1){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>squidEnableRemoteStatistics = 1</strong><br>\n";}
			$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
			$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
			$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
			$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
			
			$def["mysql_admin"]=$squidRemostatisticsUser;
			$def["mysql_password"]=$squidRemostatisticsPassword;
			$def["mysql_port"]=$squidRemostatisticsPort;
			$def["mysql_server"]=$squidRemostatisticsServer;
			$def["SocketPath"]=null;
			$def["TryTCP"]=true;
			$this->MySQLConnectionType=2;
			$this->mysql_admin=$squidRemostatisticsUser;
			$this->mysql_password=$squidRemostatisticsPassword;
			$this->mysql_port=$squidRemostatisticsPort;
			$this->mysql_server=$squidRemostatisticsServer;
			$this->mysql_admin=$this->mysql_admin;
			$this->mysql_password=$this->mysql_password;
			$this->mysql_port=$this->mysql_port;
			$this->mysql_server=$this->mysql_server;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			
			if(strlen($squidRemostatisticsPassword)>1){
				$mysql_password=$this->shellEscapeChars($squidRemostatisticsPassword);
				$pass=" -p$mysql_password";
			}
			$this->MYSQL_CMDLINES="--protocol=tcp --host=$squidRemostatisticsServer --port=$squidRemostatisticsPort -u $squidRemostatisticsUser$pass";
			
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$squidRemostatisticsUser;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$squidRemostatisticsPassword;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$squidRemostatisticsPort;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$squidRemostatisticsServer;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=null;
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=true;
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
			$this->UseStandardMysql=false;
			return;
		}
		
		if($this->ProxyUseArticaDB==1){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>ProxyUseArticaDB = 1</strong><br>\n";}
			$this->MYSQL_DATA_DIR="/opt/squidsql/data";
			$def["SocketPath"]="/var/run/mysqld/squid-db.sock";
			$this->SocketPath="/var/run/mysqld/squid-db.sock";
			$def["mysql_admin"]="root";
			$def["mysql_password"]=null;	
			$def["TryTCP"]=false;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_server="127.0.0.1";	
			$this->MYSQL_CMDLINES="--protocol=socket --socket=/var/run/mysqld/squid-db.sock -u root";
			$this->MySQLConnectionType=1;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$def["mysql_admin"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$def["mysql_password"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$def["SocketPath"];
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=$def["TryTCP"];
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
			$this->PDO_DSN="mysql:unix_socket=$this->SocketName;dbname=$this->database";
			$this->UseStandardMysql=false;
			return;
		}
		
		
		if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>Use Standard MysSQL instance</strong><br>\n";}
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
		
		$pass=null;
		if(strlen($this->mysql_password)>1){
			$mysql_password=$this->shellEscapeChars($this->mysql_password);
			$pass=" -p$mysql_password";
		}
		
		if($this->mysql_server=="localhost.localdomain"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		if(preg_match("#localhost#i", $this->mysql_server)){$this->mysql_server="127.0.0.1";}
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		
		if($this->mysql_server=="127.0.0.1"){
			$this->MySQLConnectionType=1;
			$this->PDO_DSN="unix_socket=/var/run/mysqld/mysqld.sock;dbname=$this->database";
			$this->MYSQL_CMDLINES="--protocol=socket --socket=/var/run/mysqld/mysqld.sock -u {$this->mysql_admin}$pass";
		}else{
			$this->MySQLConnectionType=2;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			$this->MYSQL_CMDLINES="--protocol=tcp --host={$this->mysql_server} --port={$this->mysql_port} -u {$this->mysql_admin}$pass";
		}
		
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$this->mysql_admin;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$this->mysql_password;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
		$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$this->SocketPath;
		$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
		$this->UseStandardMysql=true;
	}
	
	public function MEMORY_TABLES_DUMP(){
		if($this->EnableSquidRemoteMySQL==1){return;}
		if($this->squidEnableRemoteStatistics==1){return;}
		if($this->mysql_server<>"127.0.0.1"){return;}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$workdir="/home/artica/MySQLStartStop";
		$array=$this->MEMORY_TABLES_LIST();
		$CountDeArray=count($array);
		if($CountDeArray==0){echo "Stopping MySQL...............: no memory table to dump\n";return;}
		$unix=new unix();
		
		echo "Stopping MySQL...............: $CountDeArray memory tables to dump\n";
		$mysqldump=$unix->find_program("mysqldump");
		$MYSQL_CMDLINES=$this->MYSQL_CMDLINES;
		@mkdir($workdir,0755,true);
		$workfile="$workdir/restore.sql";
		
		$F[]="$mysqldump";
		$F[]="--skip-add-drop-table";
		$F[]="--delayed-insert";
		$F[]="--insert-ignore";
		$F[]=$MYSQL_CMDLINES;
		
		$cmdline=@implode(" ", $F)." $this->database ". @implode(" ", $array)." >$workfile";
		if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
		shell_exec($cmdline);
		
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
		return $path;
	}	
	
	

	
	
	
	
	
	FUNCTION DELETE_TABLE($table){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
		if(!$this->ok){return false;}
		$this->QUERY_SQL("FLUSH TABLES",$this->database);
		return true;
	}		
	
	
	public function TestingConnection($called=null){
			return $this->BD_CONNECT();
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
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];	
	}
	
	FUNCTION TABLE_STATUS($table,$database=null){
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		if(!function_exists('mysql_connect')){
		$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);return false;}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$database));		
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
	private function DATABASE_EXISTS($database){
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
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
	
	
	function PRIVILEGES($user,$password){
		
		$sql="SELECT User FROM user WHERE User='$user'";
	
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$this->database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $this->database \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		
		if($userfound==null){
			$sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
		}
		
		
		$sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){
			echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
			return false;
		}
		
		$sql="GRANT USAGE ON `$this->database`. *  TO '$user'@'*' IDENTIFIED BY '$password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}


		$sql="GRANT ALL PRIVILEGES ON `$this->database` . * TO '$user'@'*' WITH GRANT OPTION ;";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
	}
	
	
	
	
	public function DATABASE_INFOS(){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$this->database);
		$dbsize=0;$count=0;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
			$count=$count+1;}
			return array($count,ParseBytes($dbsize));
		
	}
	
	
	public function TABLE_GET_COLUMNS($table) {
		$result =$this->QUERY_SQL("SHOW COLUMNS FROM `$table`");
		$fieldnames=array();
		if (@mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_assoc($result)) {
				$fieldnames[] = $row['Field'];
			}
		}
	
		return $fieldnames;
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
			$this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
			$this->writelogs("$sql",__FUNCTION__,__LINE__);
			return false;
		}
		
	}

	
	
	public function BD_CONNECT($noretry=false,$called=null){
			if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
			if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
			if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
			if($this->MySQLConnectionType==1){
				if(!$this->is_socket($this->SocketName)){
					$this->mysql_error="$this->SocketName no such socket";
					$this->ToSyslog("$this->SocketName no such socket");
					$GLOBALS["THIS_TestingConnection"]=false;
					return false;
				}
				
				if($this->SocketName=="/var/run/mysqld/mysqld.sock"){
					$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,$this->mysql_password);
				}else{
					$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,null);
				}
				
				
		
				if($bd){
					$this->mysql_connection=$bd;
					$GLOBALS["THIS_TestingConnection"]=true;
					return true;
				}
				
				
				if($GLOBALS["VERBOSE"]){echo "mysql_connect $this->SocketName -> error<br>\n";}
				$des=@mysql_error(); $errnum=@mysql_errno();
				$this->BD_CONNECT_ERROR=__LINE__.":MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
				$this->ToSyslog($this->BD_CONNECT_ERROR);
				$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
		}
	
		if($this->MySQLConnectionType==2){
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($bd){$this->mysql_connection=$bd;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->BD_CONNECT_ERROR=__LINE__.":MySQL Server:$this->mysql_server: MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
			$this->ToSyslog($this->BD_CONNECT_ERROR);
			$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
			$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
			$GLOBALS["THIS_TestingConnection"]=false;
			return false;
		}
		
		if($this->mysql_server=="127.0.0.1"){
			if(!$this->is_socket($this->SocketName)){
				$this->mysql_error="$this->SocketName no such socket";
				$this->ToSyslog("$this->SocketName no such socket");
				$GLOBALS["THIS_TestingConnection"]=false;
				$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
				return false;
			}
			
			$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,$this->mysql_password);
			$FinalLog="$this->SocketName@$this->mysql_admin";
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			$FinalLog="$this->mysql_admin@$this->mysql_server:$this->mysql_port";
		}
		if($bd){$this->mysql_connection=$bd;$GLOBALS["THIS_TestingConnection"]=true;return true;}
		$des=@mysql_error(); $errnum=@mysql_errno();
		$this->BD_CONNECT_ERROR=__LINE__.":$FinalLog = Err:$errnum $des $called";
		$this->ToSyslog($this->BD_CONNECT_ERROR);
		$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
		$GLOBALS["THIS_TestingConnection"]=false;
		$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
		return false;
		
	}
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	private function THIS_TestingConnection($noretry=false,$called=null){return $this->BD_CONNECT();}	
	
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
		$des=@mysql_error($this->mysql_connection);
		$this->mysql_error=$des;
	
	}

	public function TABLES_STATUS_CORRUPTED(){
		$sql="show TABLE STATUS";
		$ARRAY=array();
		if(!$this->BD_CONNECT()){return false;}
		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		
		if (!$ok){
			if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_errornum=$errnum;
			$this->mysql_error="Error Number ($errnum) ($des)";
			$this->ok=false;
			return false;
		}
		
		$results=@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			if($GLOBALS["VERBOSE"]){echo "mysql_query -> ERROR\n";}
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;
			return false;
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "mysql_query -> ". mysql_num_rows($results)." items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			
			$Name=$ligne["Name"];
			$Comment=$ligne["Comment"];
			if(trim($Comment)==null){continue;}
			$ARRAY[$Name]=$Comment;
	
		}
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		
		return $ARRAY;
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
	
	
	public function DATABASE_LIST(){
		if(!$this->BD_CONNECT()){return false;}
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
	
	private function writelogs($text=null,$function=null,$line=0){
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.squid.debug";
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
	
	public function QUERY_PDO($sql){
		$this->sql=$sql;
		$pdo_opt = array ( PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
		
		try {
	    $dbh = new PDO($this->PDO_DSN, $this->mysql_admin, $this->mysql_password,$pdo_opt);
		} catch (PDOException $e) {
			$this->ok=false;
			$this->mysql_error="Connection failed $this->PDO_DSN ". $e->getMessage();
			if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
	    	return false;
		}
		
		
		
		
		$stmt = $dbh->prepare($sql);
		
		
		
		if (!$stmt->execute()){
			$this->ok=false;
			$this->mysql_error=implode(":",$stmt->errorInfo());
			if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
			return false;
		}
		
		
		return $stmt;
		
	}
	
	
	public function QUERY_SQL($sql,$called=null,$unbuffered=false){
		$database=$this->database;
		$this->last_id=0;
		$this->sql=$sql;
		$CLASS=__CLASS__;
		$FUNCTION=__FUNCTION__;
		$FILENAME=basename(__FILE__);
		$LOGPRF="$FILENAME::$CLASS/$FUNCTION";
		$this->ok=false;
		
		$sql=trim($sql);
		
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		if($GLOBALS["DEBUG_SQL"]){echo "this->BD_CONNECT\n";}
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
	
	
		if($GLOBALS["DEBUG_SQL"]){echo "mysql_select_db()\n";}
		if($GLOBALS['VERBOSE']){$ok=mysql_select_db($database,$this->mysql_connection);}else{
		$ok=@mysql_select_db($database,$this->mysql_connection);
		}
		
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
		if(function_exists("openlog")){openlog("mysql-squid", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	public function FIELD_TYPE($table,$field){
		$database=$this->database;
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
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}

		if(isset($_SESSION["MYSQL_INDEX_EXISTS"])){if($_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]==true){return true;}}
		$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	public FUNCTION CREATE_DATABASE($database){
		if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
		if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
		
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		
		
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

	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		
		if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." $sql<br>\n";}
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." SUM: {$ligne["tsum"]}<br>\n";}
		return $ligne["tsum"];
		
	}

	
	
	private function replace_accents($s) {
			$s = htmlentities($s);$s = preg_replace ('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|ring);/', '$1', $s);$s=str_replace("&Ntilde;","N",$s);$s=str_replace("&ntilde;","n",$s);$s=str_replace("&Oacute;","O",$s);$s=str_replace("&oacute;","O",$s);$s=str_replace("&Ograve;","O",$s);$s=str_replace("&ograve;","o",$s);$s=str_replace("&Ocirc;","O",$s);$s=str_replace("&ocirc;","o",$s);$s=str_replace("&Ouml;","O",$s);$s=str_replace("&ouml;","o",$s);$s=str_replace("&Otilde;","O",$s);$s=str_replace("&otilde;","o",$s);$s=str_replace("&Oslash;","O",$s);$s=str_replace("&oslash;","o",$s);$s=str_replace("&szlig;","b",$s);$s=str_replace("&Thorn;","T",$s);$s=str_replace("&thorn;","t",$s);$s=str_replace("&Uacute;","U",$s);$s=str_replace("&uacute;","u",$s);$s=str_replace("&Ugrave;","U",$s);$s=str_replace("&ugrave;","u",$s);$s=str_replace("&Ucirc;","U",$s);$s=str_replace("&ucirc;","u",$s);$s=str_replace("&Uuml;","U",$s);$s=str_replace("&uuml;","u",$s);$s=str_replace("&Yacute;","Y",$s);$s=str_replace("&yacute;","y",$s);$s=str_replace("&yuml;","y",$s);$s=str_replace("&Icirc;","I",$s);$s=str_replace("&icirc;","i",$s);$s = html_entity_decode($s);return $s;
	}	
	

public function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$this->BD_CONNECT();
		$this->ok=true;
		$results=mysql_query($sql,$this->mysql_connection);
		if(!$results){$this->ok=false;}
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			
			
			return $array;
		}
		
		public function SHOW_STATUS(){
			$sql="SHOW STATUS;";
			$this->BD_CONNECT();
		$this->ok=true;
		$results=mysql_query($sql,$this->mysql_connection);
		if(!$results){$this->ok=false;}
			$errnum=@mysql_error($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			$this->mysql_error=$des;
		
		
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$Variable_name=$ligne["Variable_name"];
				$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
}








private function TimeDiffMinutes($oldtime){
	
	$data1 = $oldtime;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}



private  function file_time_min($path){
	if(!is_dir($path)){
		if(!is_file($path)){return 100000;}
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}
	
	
public function CheckTables($table=null,$force=false){
		$sql="CREATE TABLE IF NOT EXISTS `dayz` (
			`zMD5` CHAR(32) NOT NULL,
			`sitename` varchar(128) NOT NULL,
			`client` varchar(50) NOT NULL,
			`hostname` varchar(120) NOT NULL,
			`MAC` varchar(20) NOT NULL,
			`uid` varchar(90) NOT NULL,
			`zDate` DATE NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			`hits` BIGINT UNSIGNED NOT NULL,
			`category` varchar(50) NOT NULL,
			PRIMARY KEY (`zMD5`),
			KEY `client` (`client`),
			KEY `hostname` (`hostname`),
			KEY `uid` (`uid`),
			KEY `MAC` (`MAC`),
			KEY `zDate` (`zDate`),
			KEY `category` (`category`),
			KEY `size` (`size`),
			KEY `hits` (`hits`),
			KEY `sitename` (`sitename`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				echo $this->mysql_error."LINE [".__LINE__."]\n";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			
			$sql="CREATE TABLE IF NOT EXISTS `size_dayz` (
			`zMD5` CHAR(32) NOT NULL,
			`sitename` varchar(128) NOT NULL,
			`zDate` DATE NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			PRIMARY KEY (`zMD5`),
			KEY `zDate` (`zDate`),
			KEY `size` (`size`),
			KEY `sitename` (`sitename`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				echo $this->mysql_error."LINE [".__LINE__."]\n";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
							
			$sql="CREATE TABLE IF NOT EXISTS `users_dayz` (
			`zMD5` CHAR(32) NOT NULL,
			`client` varchar(50) NOT NULL,
			`hostname` varchar(120) NOT NULL,
			`MAC` varchar(20) NOT NULL,
			`uid` varchar(90) NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			PRIMARY KEY (`zMD5`),
			KEY `client` (`client`),
			KEY `hostname` (`hostname`),
			KEY `uid` (`uid`),
			KEY `MAC` (`MAC`),
			KEY `size` (`size`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				echo $this->mysql_error."LINE [".__LINE__."]\n";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
						
			$sql="CREATE TABLE IF NOT EXISTS `nocatz` (
			`familysite` varchar(128) NOT NULL PRIMARY KEY ) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				echo $this->mysql_error."LINE [".__LINE__."]\n";
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			
			
			
	return true;
	
	
		
	}

	
	
	

public function isCrashedRootRepair($table){
	$unix=new unix();
	if(!preg_match("#is marked as crashed and should be repaired#i", $this->mysql_error)){return false;}
	$myisamchk=$unix->find_program("myisamchk");
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	$table_path="$WORKDIR/data/squidlogs/$table.MYI";
	shell_exec("myisamchk -r $table_path");
	return true;

}


	public function getDaysInWeek ($weekNumber, $year) {
	  if(isset($GLOBALS["getDaysInWeek$weekNumber$year"])){return $GLOBALS["getDaysInWeek$weekNumber$year"];}
	  $sql="SELECT zDate,WeekDay FROM tables_day WHERE WeekNum=$weekNumber AND YEAR(zDate)='$year'";
	  $q=new mysql_squid_builder();
	  $results=$q->QUERY_SQL($sql);	
	  	if(preg_match("#Unknown column#i", $q->mysql_error)){
	  		if(!$q->FIELD_EXISTS("tables_day", "WeekDay")){
	  			$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `WeekDay` SMALLINT( 2 ) NOT NULL,ADD INDEX ( `WeekDay`)");
	  			$sock=new sockets();
	  			$sock->GET_INFO("squid.php?weekdaynum=yes");
	  		}
	  		
	  		if(!$q->FIELD_EXISTS("tables_day", "WeekNum")){
	  			$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `WeekNum` SMALLINT( 2 ) NOT NULL,ADD INDEX ( `WeekNum`)");
	  			$sock=new sockets();
	  			$sock->GET_INFO("squid.php?weekdaynum=yes");	  			
	  		}	  		
	  		$results=$q->QUERY_SQL($sql);	
	  	}
		
	  
	  
	  if(!$q->ok){echo $q->mysql_error;return;}
	  while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	  	$time=strtotime($ligne["zDate"]." 00:00:00");
	  	$dayTimes[$ligne["WeekDay"]]=$time;
	  }
	  $GLOBALS["getDaysInWeek$weekNumber$year"]=$dayTimes;
	  return $dayTimes;
	}


	
}

	


