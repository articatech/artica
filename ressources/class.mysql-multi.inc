<?php
include_once(dirname(__FILE__).'/class.mysql.inc');


class mysql_multi{
	var $mysqlInstanceName;
	var $instance_id=0;
	var $CurrentPageName;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_connection;
	var $ok=true;
	var $mysql_error;
	var $SocketPath;
	var $last_id=0;
	var $PidPath;
	var $HomeDir;
	var $listen_port;
	var $listen_addr;
	var $mysql_port=0;
	var $watchdog=0;
	var $watchdogCPU=0;
	var $watchdogMEM=0;
	var $MyServer=null;
	var $mysql_server=null;
	var $MyServerCMDLINE=null;
	
	function mysql_multi($instance_id){
		if(is_numeric($instance_id)){$this->instance_id=$instance_id;$this->LoadParams();}
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	  	$this->CurrentPageName=$phpPage;
	 }
	 
	private function LoadParams(){
		if(!function_exists('mysql_connect')){$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);return false;}
		if($this->instance_id==0){return false;}
		$q=new mysql();
		$this->mysql_password=null;
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,listen_port,watchdog,listen_addr,params FROM mysqlmulti WHERE ID='$this->instance_id'","artica_backup"));
		$params=unserialize(base64_decode($ligne["params"]));
		if(!isset($params["mysql_admin"])){$params["mysql_admin"]="root";}
		if(!isset($params["mysql_password"])){$params["mysql_password"]="";}
		if($params["mysql_password"]=="secret"){$params["mysql_password"]=null;}
		$this->mysql_admin=$params["mysql_admin"];
		$this->mysql_password=$params["mysql_password"];
		$this->mysql_admin=trim($this->mysql_admin);
		$this->mysql_password=trim($this->mysql_password);	
		
		$this->SocketPath="/var/run/mysqld/mysqld$this->instance_id.sock";
		$this->PidPath="/var/run/mysqld/mysqld$this->instance_id.pid";
		$this->listen_port=$ligne["listen_port"];
		$this->mysql_port=$ligne["listen_port"];
		$this->listen_addr=$ligne["listen_addr"];
		$this->watchdog=$ligne["watchdog"];
		$this->MyServer=$ligne["servername"];
		$this->mysql_server=$ligne["servername"];
		$this->watchdogCPU=$params["MONIT"]["watchdogCPU"];
		$this->watchdogMEM=$params["MONIT"]["watchdogMEM"];
		$this->mysqlInstanceName=$ligne["servername"];
		$this->HomeDir=$ligne["Dir"];
		$this->MyServerCMDLINE=trim($ligne["servername"]);
		$this->MyServerCMDLINE=str_replace(" ", "-", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace("[", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace("]", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace("(", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace(")", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace("$", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=str_replace("'", "", $this->MyServerCMDLINE);
		$this->MyServerCMDLINE=strtolower($this->MyServerCMDLINE);
	}
	
	function SET_VARIABLES($key,$value){
		if(!$this->BD_CONNECT()){return false;}
		$results=@mysql_query("SET GLOBAL $key = $value",$this->mysql_connection);
		$errnum=mysql_error($this->mysql_connection);
    	$des=mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		}	
	
	function QUERY_SQL_NO_BASE($sql){
		if(!$this->BD_CONNECT()){return false;}
		$mysql_result = @mysql_query($sql,$this->mysql_connection);

		if(mysql_error($this->mysql_connection)){
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
	    	if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	    	$this->writeLogs("Instance: $this->instance_id::Error Number ($errnum) ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    	$this->writeLogs("Instance: $this->instance_id::$sql",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    	
	    	$this->mysql_error="Instance: $this->instance_id::QUERY_SQL_NO_BASE:: Error ($des) line:".__LINE__;
	    	$this->ok=false;
	    	return null;			
		}
		
		return $mysql_result;
 		
	}
	
	function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$bd=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
		
		if(!$bd){$errnum=mysql_error();$des=mysql_error();$this->mysql_error=$des;return null;}
		$results=@mysql_query($sql,$bd);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}	
	
	FUNCTION GLOBAL_STATUS(){
		if(!$this->BD_CONNECT()){return array();}
		$sql="SHOW GLOBAL STATUS";	
		$results = $this->QUERY_SQL_NO_BASE($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$array[$ligne["Variable_name"]]=$ligne["Value"];
			
		}
		return $array; 
	}
	
	function DATABASE_EXISTS($database){
		if(isset($_SESSION["{$this->instance_id}MYSQL_DATABASE_EXISTS"][$database])){
			if($_SESSION["{$this->instance_id}MYSQL_DATABASE_EXISTS"][$database]==true){return true;}
		}
		$sql="SHOW DATABASES";
		$bd=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
		$results=@mysql_query($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION["{$this->instance_id}MYSQL_DATABASE_EXISTS"][$database]=true;
				return true;
			}
		}
		
		return false;
		
	}	
	
	function DATABASE_LIST(){
		$sql="SHOW DATABASES";
		$bd=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
		if(!$bd){$errnum=mysql_error();$des=mysql_error();$this->mysql_error=$des;
		$this->writeLogs("Instance: $this->instance_id::Error Number ($errnum) ($des) ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );return array();}
		$results=@mysql_query($sql);
		$errnum=mysql_errno($bd);
    	$des=mysql_error();
    	$this->mysql_error=$des;
		
    	$this->writeLogs("Instance: $this->instance_id($this->SocketPath)::$sql ($errnum/$des)" . mysql_num_rows($results) ." rows",__CLASS__.'/'.__FUNCTION__,__LINE__ );
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=$this->DATABASE_INFOS($Database);
			}
			return $array;
		}
		
	function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		$bd=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
		if(!$bd){$errnum=mysql_error();$des=mysql_error();$this->mysql_error=$des;
		$this->writeLogs("Instance: $this->instance_id::Error Number ($errnum) ($des) ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__ );return array();}
		
		$results=@mysql_query($sql);
		$errnum=mysql_error();
    	$des=mysql_error();
    	$this->mysql_error=$des;
		$this->writeLogs("Instance: $this->instance_id($this->SocketPath)::$sql ($errnum/$des)" . mysql_num_rows($results) ." rows",__CLASS__.'/'.__FUNCTION__,__LINE__ );
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
		}		

	private function DATABASE_INFOS($database){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length']; 
			$count=$count+1;}
		return array($count,ParseBytes($dbsize));
	}

	private function TABLES_STATUS_CORRUPTED($database){
		$ARRAY=array();
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$Name=$ligne["Name"];
			$Comment=$ligne["Comment"];
			$ARRAY[$Name]=$Comment;
		
			}
		return $ARRAY;
	}	

	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		if(!$this->BD_CONNECT()){return false;}
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=mysql_error($this->mysql_connection);
    	$des=mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;

		}

	function PRIVILEGES($user,$password,$database){
		$server_mysql_password=$this->mysql_password;
		$OrginalPassword=$this->mysql_password;
		$server_database=$database;
		$mysql_password=$password;
		$sql="SELECT User FROM user WHERE User='$user'";
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));	
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$server_database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $server_database\"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}
	
		if($userfound==null){
			$this->mysql_password=$OrginalPassword;
			$sql="CREATE USER '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){
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
		$this->mysql_password=$OrginalPassword;
		$sql="CREATE USER '$user'@'localhost' IDENTIFIED BY '$mysql_password';";
		$this->EXECUTE_SQL($sql);	
		if(!$this->ok){
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
				if(!$this->ok){
					echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
					return false;
				}			
			}
			
		}
	
	$server_database2=str_replace("_","\_",$server_database);
	$this->mysql_password=$OrginalPassword;
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'localhost' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->mysql_password=$OrginalPassword;
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	$this->mysql_password=$OrginalPassword;
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->mysql_password=$OrginalPassword;
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	$this->mysql_password=$OrginalPassword;
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->mysql_password=$OrginalPassword;
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
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
	
	writelogs("success set privileges on $server_database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
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
		
		

	private function BD_CONNECT(){
		@mysql_close();
		@mysql_close($this->mysql_connection);
		$this->ok=true;
		if(!function_exists('mysql_connect')){$this->mysql_error="mysql_connect() function does not exists";$this->ok=false;return false;}
		if(($this->mysql_admin==null) && ($this->mysql_password==null)){$this->mysql_admin="root";}
		
		
		if(trim($this->mysql_password)==null){
			$INFOS="-- mysql_connect no password";
			$bdA=@mysql_connect(":$this->SocketPath",$this->mysql_admin,"");
		}else{
			$bdA=@mysql_connect(":$this->SocketPath",$this->mysql_admin,$this->mysql_password);
		}
		
		
		
		
		if(!$bdA){
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("BD_CONNECT:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__LINE__);}
				}
							
    		$des=mysql_error($bdA);
    		if($des==null){$des=mysql_error();}
    		$this->mysql_error="Instance: $this->instance_id:$des";
    		$errnum=mysql_errno($bdA);
			if($GLOBALS["VERBOSE"]){echo "mysql_connect failed ($errnum) \"$des\"\n";}
    		$this->writeLogs("Instance: $this->instance_id::$this->SocketPath::Error Number ($errnum) ($des) $this->mysql_admin,$this->mysql_password$INFOS",__CLASS__.'/'.__FUNCTION__,__LINE__ );
    		$sock=new sockets();
    		$sock->getFrameWork("services.php?restart-mysql-emergency=yes");
			return false;
		}		
			
		$this->mysql_connection=$bdA;		
		return true;
	}	
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
		
		@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno($this->mysql_connection);
				$des=mysql_error($this->mysql_connection);
				$this->mysql_error="Error Number ($errnum) ($des)";
				writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;				
		}
		
		@mysql_close($this->mysql_connection);		
		return true;
	}
	
	function TABLE_EXISTS($table,$database,$nocache=false){
		if(!$nocache){
			if(!isset($GLOBALS["{$this->instance_id}__MYSQL_TABLE_EXISTS"])){$GLOBALS["{$this->instance_id}__MYSQL_TABLE_EXISTS"]=array();}
			if(isset($GLOBALS["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table])){if($GLOBALS["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table]==true){return true;}}
			if(isset($_SESSION["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table])){if($_SESSION["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table]==true){return true;}}
		}
		
		if(!$this->DATABASE_EXISTS($database)){
			$this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->CREATE_DATABASE($database);
			if(!$this->DATABASE_EXISTS($database)){$this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;}
		}
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql,$database);
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){
				$GLOBALS["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table]=true;
				$_SESSION["{$this->instance_id}__MYSQL_TABLE_EXISTS"][$database][$table]=true;
				return true;
			}
		}
		
		if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called=" called by `". basename($trace[1]["file"])."` {$trace[1]["function"]}() line: {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}		
		
		$this->writeLogs("testing \"$table\" failed in $database $called ",__CLASS__.'/'.__FUNCTION__,__FILE__);
		return false;
		
	}	
	
	FUNCTION COUNT_ROWS($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}	
	
	
	FUNCTION CREATE_DATABASE($database,$nocache=false){
		
		if(!$nocache){
		if($this->DATABASE_EXISTS($database)){
			writelogs("CREATE_DATABASE $database Already exists aborting",__CLASS__,__FILE__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}}
		
		if(!$this->BD_CONNECT()){return false;}
    	$results=@mysql_query("CREATE DATABASE `$database`",$this->mysql_connection);
		if(mysql_error()){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}

		$this->ok=true;
		return true;
		
	}	
	
function QUERY_SQL($sql,$database=null){
		$database=trim($database);
		if(!isset($MYSQLpassword)){$MYSQLpassword=null;}
		if(!$this->BD_CONNECT()){
			$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error Failed to connect to MySQL server, aborting operation",__CLASS__.'/'.__FUNCTION__,__LINE__);
			return false;}
		
		
		if($database<>null){
			$ok=@mysql_select_db($database);
	    	if (!$ok){
	    		$errnum=@mysql_errno($this->mysql_connection);
	    		$des=@mysql_error();
	    		$this->mysql_error=$des;
	    		$time=date('h:i:s');
	    		
	    		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	    		$this->writeLogs("QUERY_SQL:: DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:$MYSQLpassword ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
	    		$this->mysql_error="QUERY_SQL:: mysql_select_db:: Error  ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin:" .strlen($MYSQLpassword)." password length line:".__LINE__;
	    		$this->ok=false;
	    		return null;
    		}
		}	
		$results=mysql_query($sql,$this->mysql_connection);
		if(!$results){
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error();
			if($des<>null){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}}
		}
	
		if(mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=mysql_errno($this->mysql_connection);
				$des=mysql_error();
				if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error $des $called line ".__LINE__;
				if($errnum==1062){$this->ok=true;@mysql_close($bd);return true;}	
				if($errnum==1548){
					$sock=new sockets();
					$sock->getFrameWork("mysql.php?mysql-upgrade=yes&instance-id=$this->instance_id");
					$this->mysql_error=$this->mysql_error."\nArtica as performed a mysql_upgrade, please retry later\n";
				}

				
				$this->ok=false;
				@mysql_close($this->mysql_connection);
				return null;
			}	
			
		$this->last_id=@mysql_insert_id();	
		$this->ok=true;
		$result_return=$results;
		@mysql_close($bd);
		return $result_return;
	}	

	private function writelogs($text=null,$function=null,$line=0){
		
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql-multi.debug";
		@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		if(!is_numeric($line)){$line=0;}
		writelogs("$text (L.$line)","$function",__FILE__,$line);
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
	 
	 
}