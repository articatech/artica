<?php
if(!isset($GLOBALS["HOTSPOT_DEBUG"])){$GLOBALS["HOTSPOT_DEBUG"]=false;}
class mysql_hotspot{
	public $ok=false;
	public $mysql_error;
	
	public function mysql_hotspot(){
			$GLOBALS["MYSQL_SOCKET"]=null;
			$GLOBALS["MYSQL_PASSWORD"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
			if($GLOBALS["MYSQL_PASSWORD"]=="!nil"){$GLOBALS["MYSQL_PASSWORD"]=null;}
			$GLOBALS["MYSQL_PASSWORD"]=stripslashes($GLOBALS["MYSQL_PASSWORD"]);
			$GLOBALS["MYSQL_USERNAME"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
			$GLOBALS["MYSQL_SERVER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
			$GLOBALS["MYSQL_PORT"]=intval(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
			if($GLOBALS["MYSQL_PORT"]==0){$GLOBALS["MYSQL_PORT"]=3306;}
			if($GLOBALS["MYSQL_SERVER"]==null){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
			$GLOBALS["MYSQL_USERNAME"]=str_replace("\r", "", $GLOBALS["MYSQL_USERNAME"]);
			$GLOBALS["MYSQL_USERNAME"]=trim($GLOBALS["MYSQL_USERNAME"]);
			$GLOBALS["MYSQL_PASSWORD"]=str_replace("\r", "", $GLOBALS["MYSQL_PASSWORD"]);
			$GLOBALS["MYSQL_PASSWORD"]=trim($GLOBALS["MYSQL_PASSWORD"]);
		
			if($GLOBALS["MYSQL_USERNAME"]==null){$GLOBALS["MYSQL_USERNAME"]="root";}
			if($GLOBALS["MYSQL_SERVER"]=="localhost"){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
			if($GLOBALS["MYSQL_SERVER"]=="127.0.0.1"){$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/squid-db.sock";}
	}
	
	private function mysql_connect(){
	
		if($GLOBALS["MYSQL_SOCKET"]<>null){
			$bd=@mysql_connect(":{$GLOBALS["MYSQL_SOCKET"]}",$GLOBALS["MYSQL_USERNAME"],$GLOBALS["MYSQL_PASSWORD"]);
		}else{
			$bd=@mysql_connect("{$GLOBALS["MYSQL_SERVER"]}:{$GLOBALS["MYSQL_PORT"]}","{$GLOBALS["MYSQL_USERNAME"]}","{$GLOBALS["MYSQL_PASSWORD"]}");
		}
	
		if($bd){
			$this->ok=true;
			return $bd;
		}
		$des=@mysql_error();
		$errnum=@mysql_errno();
		$this->ok=false;
		$this->mysql_error="failed (N:$errnum) \"$des\"";
		if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
		if(function_exists("events")){events(0,"Fatal, MySQL error $des",$this->mysql_error);}
		if($GLOBALS["MYSQL_SOCKET"]=="/var/run/mysqld/squid-db.sock"){
			$this->ToSyslog("- ACTION REQUIRED RESTART MYSQL-SQUID - $des");
		}
		return false;
		
	}
	
	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\r", " ", $text);
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	public function QUERY_SQL($sql){
		$mysql_connection=$this->mysql_connect();
		if(!$mysql_connection){$this->ok=false;return false;}
	
		if(function_exists("wifidog_logs")){if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$sql",__FUNCTION__,__LINE__);}}
		
		$ok=@mysql_select_db("squidlogs",$mysql_connection);
		if(!$ok){
			$errnum=@mysql_errno($mysql_connection);
			$des=@mysql_error($mysql_connection);
			@mysql_close($mysql_connection);
			
			$this->mysql_error="mysql_select_db() failed (N:$errnum) \"$des\"";
			if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
			$this->ok=false;
			return false;
		}
	
		$mysql_unbuffered_query_log=null;
		if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
			$mysql_unbuffered_query_log="mysql_unbuffered_query";
			$results=@mysql_unbuffered_query($sql,$mysql_connection);
				
		}else{
			$mysql_unbuffered_query_log="mysql_query";
			$results=@mysql_query($sql,$mysql_connection);
		}
	
		if(!$results){
			$errnum=@mysql_errno($mysql_connection);
			$des=@mysql_error($mysql_connection);
			@mysql_close($mysql_connection);
			$this->ok=false;
			$this->mysql_error="$mysql_unbuffered_query_log() failed (N:$errnum) \"$des\"";
			if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
			return false;
		}
		@mysql_close($mysql_connection);
		$this->ok=true;
		return $results;
	
	
	}
	
	public function COUNT_ROWS($table,$database=null){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
	}	
	
	public function FIELD_EXISTS($table,$field){
		$field=trim($field);
		$sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
	
		if(trim($ligne["Field"])<>null){
			
			return true;
		}
		
		return false;
	
	}
	
	public function TABLE_EXISTS($table){
		$keyCache=md5(__CLASS__.__FUNCTION__.$table.__LINE__);
		if(isset($GLOBALS[$keyCache])){return $GLOBALS[$keyCache];}
	
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
	
	
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql);
		$result=false;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS[$keyCache][$ligne["Tables_in_squidlogs"]]=true;
			if(strtolower($table)==strtolower($ligne["Tables_in_squidlogs"])){
				$GLOBALS[$keyCache]=true;
				return true;
			}
		}
		$GLOBALS[$keyCache]=false;
		return $result;
	
	}

	public	function check_tables(){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_sessions` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`maxtime` INT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`incoming` INT UNSIGNED ,
			`outgoing` INT UNSIGNED ,
			`nextcheck` BIGINT UNSIGNED,
			`ruleid` BIGINT UNSIGNED,
			`autocreate` smallint(1) NOT NULL DEFAULT 0,
			`username` VARCHAR( 128 ) NOT NULL ,
			`MAC` VARCHAR( 90 ) NOT NULL,
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			INDEX ( `logintime` , `maxtime` , `username` ,`finaltime`),
			KEY `MAC` (`MAC`),
			KEY `incoming` (`incoming`),
			KEY `ruleid` (`ruleid`),
			KEY `nextcheck` (`nextcheck`),
			KEY `autocreate` (`autocreate`),
			KEY `uid` (`uid`),
			KEY `hostname` (`hostname`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";
		$this->QUERY_SQL($sql);
		if(!$this->ok){if(function_exists("wifidog_logs")){wifidog_logs( "CREATE TABLE hotspot_sessions Failed $this->mysql_error");}}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "nextcheck")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `nextcheck` BIGINT UNSIGNED ,ADD INDEX ( `nextcheck` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "finaltime")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `finaltime` BIGINT UNSIGNED ,ADD INDEX ( `finaltime` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "ipaddr")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ipaddr` VARCHAR( 128 ) ,ADD INDEX ( `ipaddr` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "incoming")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `incoming` BIGINT UNSIGNED ,ADD INDEX ( `incoming` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "outgoing")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `outgoing` BIGINT UNSIGNED  ,ADD INDEX ( `outgoing` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "autocreate")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `autocreate` smallint(1) NOT NULL DEFAULT 1  ,ADD INDEX ( `autocreate` )");}
		if(!$this->FIELD_EXISTS("hotspot_sessions", "ruleid")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ruleid` BIGINT UNSIGNED,ADD INDEX ( `ruleid` )");}
	
	}
	
}