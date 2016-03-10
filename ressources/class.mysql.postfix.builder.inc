<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_SESSION["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.familysites.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
class mysql_postfix_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="postfixlog";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $SocketName=null;
	var $EnableRemoteStatisticsAppliance=0;
	
	
	function mysql_postfix_builder(){
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;	
		$this->SocketName=$this->ClassSQL->SocketName;		
		if(!$this->TestingConnection()){$this->MysqlFailed=true;}
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		
				
		
	}
	
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];
	}
	
	
	public function DUMP_TABLE($tablename){
		$sock=new sockets();
		$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
		if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
		$directory="$BackupMaxDaysDir/$this->database";
		$target_file="$directory/$tablename.sql";
		@mkdir($directory,0755,true);
		$unix=new unix();
		$bzip2=$unix->find_program("bzip2");
		if(is_file($bzip2)){
			$bzip2_cmd="| $bzip2 ";
			$target_file=$target_file.".tar.bz2";
		}
		
		
		$mysqldump=$unix->find_program("mysqldump");
		if($this->mysql_server=="127.0.0.1"){
			$socket=" -S $this->SocketName";
		}else{
			$socket=" -P $this->mysql_port -h $this->mysql_server";
		}
		if($this->mysql_password<>null){
			$password=" -p$this->mysql_password";
		}
		if(!is_file($mysqldump)){return false;}
		
		$cmd="$mysqldump --single-transaction --skip-add-locks --skip-lock-tables -u $this->mysql_admin $password $socket $this->database $tablename $bzip2_cmd > $target_file 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		exec($cmd,$results);
		
		while (list ($num_line, $evenement) = each ($results)){
			
			if(preg_match("#Error\s+([0-9]+)#",$evenement)){
				if($GLOBALS["VERBOSE"]){echo "Error $evenement\n";}
				return;}
		}
		
		return true;
	}
	
	public function TestingConnection(){
		$this->ok=true;
		$this->ClassSQL->ok=true;
		$a=$this->ClassSQL->TestingConnection();
		$this->mysql_error=$this->ClassSQL->mysql_error;
		return $a;
	}

	public function COUNT_ROWS($table,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}

	public function TABLE_SIZE($table,$database=null){
			if($database<>$this->database){$database=$this->database;}
			return $this->ClassSQL->TABLE_SIZE($table,$database);		
		}
	
	public function TABLE_EXISTS($table,$database=null){
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		if(!$a){if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("TABLE_EXISTS:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		return $a;
		
	}
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	
	private function FIELD_EXISTS($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}

	public function QUERY_SQL($sql,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		if(!$this->ok){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$filename=basename($trace[1]["file"]);
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
				}
			}
			$this->events("Error generated by $filename function $function() in line $line: \"$this->mysql_error\"");
		}
			
			
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}

	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'dansguardian_events_%'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tsum"];
		
	}	
	
	public function CheckTables(){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		$this->TableDays();
	}
	
	public function BuildIndexTable(){
		
		
		
	}
	
	public function BuildHourTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `zhour` tinyint(2) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `mailsize` BIGINT UNSIGNED NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `ztime` (`ztime`,`zhour`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}
	
	public function BuildDaySentTable($tablename){
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`hour` tinyint(2) NOT NULL,
		`messageid` varchar(60) NOT NULL,
		`mailto` varchar(255) NOT NULL,
		`domainto` varchar(128) NOT NULL,
		`hostname` varchar(128) NOT NULL,
		`ipaddr` varchar(128) NOT NULL,
		`mailtime` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `messageid` (`messageid`),
		KEY `mailto` (`mailto`),
		KEY `domainto` (`domainto`),
		KEY `hostname` (`hostname`),
		KEY `ipaddr` (`ipaddr`)
		) ENGINE=MYISAM;";
	
		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
	
	}	
	
	public function BuildDayFromTable($tablename){
	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`hour` tinyint(2) NOT NULL,
		`messageid` varchar(60) NOT NULL,
		`mailfrom` varchar(255) NOT NULL,
		`domainfrom` varchar(128) NOT NULL,
		`mailsize` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `messageid` (`messageid`),
		KEY `mailfrom` (`mailfrom`),
		KEY `domainfrom` (`domainfrom`),
		KEY `mailsize` (`mailsize`)
		) ENGINE=MYISAM;";
	
		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
	
		}	
		
		public function BuildDayConnectTable($tablename){
		
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			`zmd5` varchar(90) NOT NULL,
			`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`hour` tinyint(2) NOT NULL,
			`messageid` varchar(60) NOT NULL,
			`hostname` varchar(255) NOT NULL,
			`domain` varchar(128) NOT NULL,
			`ipaddr` VARCHAR(90) NOT NULL,
			PRIMARY KEY (`zmd5`),
			KEY `zDate` (`zDate`),
			KEY `messageid` (`messageid`),
			KEY `hostname` (`hostname`),
			KEY `domain` (`domain`),
			KEY `ipaddr` (`ipaddr`)
			) ENGINE=MYISAM;";
		
			if(!$this->QUERY_SQL($sql)){return false;}
			return true;
		
			}		
		

	
	public function milter_BuildHourTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `zhour` tinyint(2) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `failed` varchar(15) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `ztime` (`ztime`,`zhour`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `failed` (`failed`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}

public function postfix_buildday_connections($curdate=null){
	if(!is_numeric($curdate)){
		$curdate=date("Ymd");
	}
	
	$tablename="{$curdate}_dcnx";
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	`zmd5` varchar(90) NOT NULL,
	`Hour` smallint(2) NOT NULL,
	`cnx` BIGINT(100) NOT NULL,
	`hostname` varchar(255) NOT NULL,
	`domain` varchar(255) NOT NULL,
	`ipaddr` varchar(90) NOT NULL,
	PRIMARY KEY (`zmd5`),
	KEY `Hour` (`Hour`),
	KEY `cnx` (`cnx`),
	KEY `hostname` (`hostname`),
	KEY `domain` (`domain`),
	KEY `ipaddr` (`ipaddr`)
	) ENGINE=MYISAM;";
	if(!$this->QUERY_SQL($sql)){
		$this->events($q->mysql_error);
		return false;}
	return true;
	
}
public function postfix_buildday_failed_connections($curdate=null){
	if(!is_numeric($curdate)){
		$curdate=date("Ymd");
	}

	$tablename="{$curdate}_dfcnx";
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	`zmd5` varchar(90) NOT NULL,
	`Hour` smallint(2) NOT NULL,
	`cnx` BIGINT(100) NOT NULL,
	`hostname` varchar(255) NOT NULL,
	`domain` varchar(255) NOT NULL,
	`ipaddr` varchar(90) NOT NULL,
	`WHY` varchar(90) NOT NULL,
	PRIMARY KEY (`zmd5`),
	KEY `Hour` (`Hour`),
	KEY `cnx` (`cnx`),
	KEY `hostname` (`hostname`),
	KEY `domain` (`domain`),
	KEY `WHY` (`WHY`),
	KEY `ipaddr` (`ipaddr`)
	) ENGINE=MYISAM;";
	
	$this->QUERY_SQL($sql);
	if(!$this->ok){
		$this->events($q->mysql_error);
		if(function_exists("events")){events("postfix_buildday_failed_connections:: $this->mysql_error");}
		return false;
	}
		return true;

}
public function postfix_buildhour_failed_connections($curdate=null){
	if($curdate==null){$curdate=date("YmdH");}
	
	$tablename="{$curdate}_hfcnx";
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	`zmd5` varchar(90) NOT NULL,
	`zDate` datetime NOT NULL,
	`hostname` varchar(255) NOT NULL,
	`domain` varchar(255) NOT NULL,
	`ipaddr` varchar(90) NOT NULL,
	`WHY` varchar(90) NOT NULL,
	PRIMARY KEY (`zmd5`),
	KEY `zDate` (`zDate`),
	KEY `hostname` (`hostname`),
	KEY `domain` (`domain`),
	KEY `WHY` (`WHY`),
	KEY `ipaddr` (`ipaddr`)
	) ENGINE=MYISAM;";


	$this->QUERY_SQL($sql);
	if(!$this->ok){
		if($GLOBALS["VERBOSE"]){echo $this->mysql_error."\n".$sql."\n\n";}
		if(function_exists("events")){events("postfix_buildhour_failed_connections:: $this->mysql_error");}
		$this->events($this->mysql_error);
		return false;
	}
	
	return true;
}
	
public function postfix_buildhour_connections($curdate=null){
	if($curdate==null){$curdate=date("YmdH");}
	if(isset($GLOBALS["postfix_buildhour_connections"][$curdate])){return;}
	$tablename="{$curdate}_hcnx";
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` datetime NOT NULL,
		`hostname` varchar(255) NOT NULL,
		`domain` varchar(255) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `hostname` (`hostname`),
		KEY `domain` (`domain`),
		KEY `ipaddr` (`ipaddr`)
		) ENGINE=MEMORY;";

	
	$this->QUERY_SQL($sql);
	if(!$this->ok){
		if($GLOBALS["VERBOSE"]){echo $this->mysql_error."\n".$sql."\n\n";}
		if(function_exists("events")){events("postfix_buildhour_failed_connections:: $this->mysql_error");}
		$this->events($this->mysql_error);
		return false;
	}
	$GLOBALS["postfix_buildhour_connections"][$curdate]=true;
	
	$tablename="{$curdate}_hmbx";
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `mbx_service` varchar(30) NOT NULL,
			  `hostname` varchar(90) NOT NULL,
			  `ipaddr` varchar(20) NOT NULL,
			  `uid` varchar(50) NOT NULL,
			  `imap_server` varchar(255) NOT NULL,
			  `domain` varchar(255) NOT NULL,
			 PRIMARY KEY (`zmd5`),
			  KEY `zDate` (`zDate`),
			  KEY `mbx_service`(`mbx_service`),
			  KEY `hostname` (`hostname`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `uid` (`uid`),
			  KEY `domain` (`domain`),
			  KEY `imap_server` (`imap_server`)
			)";
	$this->QUERY_SQL($sql);
	if(!$this->ok){
		if($GLOBALS["VERBOSE"]){echo $this->mysql_error."\n".$sql."\n\n";}
		if(function_exists("events")){events("postfix_buildhour_failed_connections:: $this->mysql_error");}
		$this->events($this->mysql_error);
		return false;
	}
	
	return true;	
	
	
}	

private function events($text){
	
	
	$logFile="/var/log/mysql.postfix.log";
	if(!isset($GLOBALS["CLASS_UNIX"])){
		include_once("/usr/share/artica-postfix/framework/class.unix.inc");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
				$filename=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
		}
	}
	
	$GLOBALS["CLASS_UNIX"]->events("$filename/$function $text [$line]",$logFile);
}





	
	public function milter_BuildDayTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`zhour` tinyint(2) NOT NULL,
		`mailfrom` varchar(255) NOT NULL,
		`instancename` varchar(255) NOT NULL,
		`mailto` varchar(255) NOT NULL,
		`domainfrom` varchar(128) NOT NULL,
		`domainto` varchar(128) NOT NULL,
		`senderhost` varchar(128) NOT NULL,
		`failed` varchar(15) NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `hits` (`hits`),
		KEY `zhour` (`zhour`),
		KEY `mailfrom` (`mailfrom`),
		KEY `mailto` (`mailto`),
		KEY `domainfrom` (`domainfrom`),
		KEY `domainto` (`domainto`),
		KEY `senderhost` (`senderhost`),
		KEY `instancename` (`instancename`),
		KEY `failed` (`failed`)
		)";
	
		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
	
	}	
	public function milter_BuildMonthTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`zday` date NOT NULL,
		`mailfrom` varchar(255) NOT NULL,
		`instancename` varchar(255) NOT NULL,
		`mailto` varchar(255) NOT NULL,
		`domainfrom` varchar(128) NOT NULL,
		`domainto` varchar(128) NOT NULL,
		`senderhost` varchar(128) NOT NULL,
		`failed` varchar(15) NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `hits` (`hits`),
		KEY `zday` (`zday`),
		KEY `mailfrom` (`mailfrom`),
		KEY `mailto` (`mailto`),
		KEY `domainfrom` (`domainfrom`),
		KEY `domainto` (`domainto`),
		KEY `senderhost` (`senderhost`),
		KEY `instancename` (`instancename`),
		KEY `failed` (`failed`)
		)";
	
		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
	
		}	
	
	public function BuildDayTable($tablename){
		
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `zhour` tinyint(2) NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zhour` (`zhour`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}	
	
	public function BuildMonthTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `zday` tinyint(2) NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zday` (`zday`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){
			if($GLOBALS["VERBOSE"]){echo "Failed to create $tablename\n";}
			return false;}
		return true;
		
	}	
	
	private function TableDays(){
		$sql="CREATE TABLE IF NOT EXISTS `TableDays` (
			`zDays` DATE NOT NULL ,
			`size` BIGINT UNSIGNED ,
			`DayBuilded` SMALLINT( 1 ) NOT NULL DEFAULT '0',
			`MonthBuilded` SMALLINT( 1 ) NOT NULL DEFAULT '0',
			`events` BIGINT UNSIGNED ,
			PRIMARY KEY ( `zDays` ) ,
			INDEX ( `size` , `DayBuilded` ),
			KEY `MonthBuilded` (`MonthBuilded`)
			)"; 
			if(!$this->QUERY_SQL($sql)){
			writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			
			if(!$this->FIELD_EXISTS("TableDays", "MonthBuilded")){$this->QUERY_SQL("ALTER TABLE `TableDays` ADD `MonthBuilded` smallint( 1 ) NOT NULL,ADD INDEX (`MonthBuilded`)");}
			
		}
		
		
	public function LIST_HOUR_TABLES(){}
	
	
	public function LIST_POSTFIX_CNX_HOUR_TABLES(){
		if(isset($GLOBALS["LIST_POSTFIX_CNX_HOUR_TABLES"])){return $GLOBALS["LIST_POSTFIX_CNX_HOUR_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE '%_hcnx'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#([0-9]+)_hcnx#", $ligne["c"],$re)){
				if($re[1]==$thishour){continue;}
				$number=$re[1];
				$year=substr($number, 0,4);
				$month=substr($number, 4,2);
				$day=substr($number, 6,2);
				$hour=substr($number, 8,2);
				$GLOBALS["LIST_POSTFIX_CNX_HOUR_TABLES"][$ligne["c"]]="$year-$month-$day $hour:00:00";
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $GLOBALS["LIST_POSTFIX_CNX_HOUR_TABLES"];
	
	
	}	
	
	public function LIST_POSTFIX_CNX_FAILED_HOUR_TABLES(){
		if(isset($GLOBALS["LIST_POSTFIX_CNX_FAILED_HOUR_TABLES"])){return $GLOBALS["LIST_POSTFIX_CNX_FAILED_HOUR_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' 
		AND table_name LIKE '%_hfcnx'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#([0-9]+)_hfcnx#", $ligne["c"],$re)){
				if($re[1]==$thishour){continue;}
				$number=$re[1];
				$year=substr($number, 0,4);
				$month=substr($number, 4,2);
				$day=substr($number, 6,2);
				$hour=substr($number, 8,2);
				$GLOBALS["LIST_POSTFIX_CNX_FAILED_HOUR_TABLES"][$ligne["c"]]="$year-$month-$day $hour:00:00";
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $GLOBALS["LIST_POSTFIX_CNX_FAILED_HOUR_TABLES"];
	
	
	}	
	
	
	public function LIST_MILTERGREYLIST_HOUR_TABLES(){
		if(isset($GLOBALS["LIST_MILTERGREYLIST_HOUR_TABLES"])){return $GLOBALS["LIST_MILTERGREYLIST_HOUR_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'mgreyh_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#mgreyh_([0-9]+)#", $ligne["c"],$re)){
				if($re[1]==$thishour){continue;}
				$number=$re[1];
				$year=substr($number, 0,4);
				$month=substr($number, 4,2);
				$day=substr($number, 6,2);
				$hour=substr($number, 8,2);
				$GLOBALS["LIST_MILTERGREYLIST_HOUR_TABLES"][$ligne["c"]]=strtotime("$year-$month-$day $hour:00:00");
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $GLOBALS["LIST_MILTERGREYLIST_HOUR_TABLES"];
	
	
	}	
	
	public function LIST_MILTERGREYLIST_DAY_TABLES(){
		if(isset($GLOBALS["LIST_MILTERGREYLIST_DAY_TABLES"])){return $GLOBALS["LIST_MILTERGREYLIST_DAY_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'mgreyd_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#mgreyd_([0-9]+)#", $ligne["c"],$re)){
				if($re[1]==$thishour){continue;}
				$number=$re[1];
				$year=substr($number, 0,4);
				$month=substr($number, 4,2);
				$day=substr($number, 6,2);
				$GLOBALS["LIST_MILTERGREYLIST_DAY_TABLES"][$ligne["c"]]=strtotime("$year-$month-$day 00:00:00");
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $GLOBALS["LIST_MILTERGREYLIST_DAY_TABLES"];
	
	
	}	
	
}