<?php
if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
if(!isset($GLOBALS["TITLENAME"])){$GLOBALS["TITLENAME"]="PostgresSQL";}

class postgres_sql{
	public $ok=false;
	private $connection;
	private $database="proxydb";
	private $host="'/var/run/ArticaStats'";
	public $hostname="/var/run/ArticaStats";
	public $portname=null;
	public $isRemote=false;
	
	public $mysql_error;
	function postgres_sql($nocheck=false){
		if(!$nocheck){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->postgres_sql\n";}
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->CREATE_DATABASE($this->database);
		}}
		$this->LoadConfig();
	}
	
	private function LoadConfig(){
	
		$this->host="'/var/run/ArticaStats'";

		if(!isset($GLOBALS["InfluxApiIP"])){
			$GLOBALS["InfluxApiIP"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxApiIP");
			if($GLOBALS["VERBOSE"]){echo "InfluxApiIP: {$GLOBALS["InfluxApiIP"]}\n";}
		}
		if($GLOBALS["InfluxApiIP"]==null){$GLOBALS["InfluxApiIP"]="127.0.0.1";}
	
		if(!isset($GLOBALS["InfluxUseRemote"])){
			$GLOBALS["InfluxUseRemote"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
			if($GLOBALS["InfluxUseRemote"]==1){$this->isRemote=true;}
		}
	
		if(!isset($GLOBALS["InfluxUseRemoteIpaddr"])){
			$GLOBALS["InfluxUseRemoteIpaddr"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemoteIpaddr"));
		}
	
		if(!isset($GLOBALS["InfluxUseRemotePort"])){
			$GLOBALS["InfluxUseRemotePort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemotePort"));
			if($GLOBALS["InfluxUseRemotePort"]==0){$GLOBALS["InfluxUseRemotePort"]=5432;}
		}
		if(!isset($GLOBALS["EnableInfluxDB"])){
			if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
			$GLOBALS["EnableInfluxDB"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB"));
		}
		
	
	
	
		if($GLOBALS["InfluxUseRemote"]==1){
			$GLOBALS["InfluxApiIP"]=$GLOBALS["InfluxUseRemoteIpaddr"];
			$port=intval($GLOBALS["InfluxUseRemotePort"]);
			if($port==8086){$port=5432;}
			if($port==0){$port=5432;}
			$this->host="'{$GLOBALS["InfluxApiIP"]}' port='{$port}'";
			$this->hostname="{$GLOBALS["InfluxApiIP"]}";
			$this->portname=$port;
			
		}
	
	
	
	}
	
	
	private function DATABASE_EXISTS($database){
		$database=trim($database);
		if(!$this->connect()){return false;}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Check if $database exists\n";}
		$sql="SELECT datname, datdba FROM pg_database ORDER BY datname";
		$results = @pg_query ($this->connection, $sql);
		
		
		
		while($ligne=@pg_fetch_assoc($results)){
			if(strtolower($database)==strtolower($ligne["datname"])){
				$_SESSION["POSTGRES_DATABASE_EXISTS"][$database]=true;
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $database exists\n";}
				@pg_close($this->connection);
				return true;
			}
		}
		@pg_close($this->connection);
	
		return false;
	}
	
	public function fieldSelectore(){
		$BASEDIR="/usr/share/artica-postfix/ressources/interface-cache";
		$date1=intval(@file_get_contents("$BASEDIR/DATE_START"));
		$date2=intval(@file_get_contents("$BASEDIR/DATE_END"));
		$mintime=strtotime("2008-01-01 00:00:00");
	
		if($date1<$mintime){
			$date1=filemtime("/etc/artica-postfix/artica-iso-first-reboot");
		}
	
		if($date2<strtotime(date("Y-m-d H:i:s")." -5 hours")){
			$date2=time();
	
		}
	
	
		$date_start=date("Y-m-d",$date1);
		$date_end=date("Y-m-d",$date2);
		return "mindate:$date_start;maxdate:$date_end";
	}
	
	
	public function TABLE_EXISTS($table){
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		if(!$this->connect()){return false;}
		$sql="SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
		$results = @pg_query ($this->connection, $sql);
	
		while($ligne=@pg_fetch_assoc($results)){
			if(strtolower($table)==strtolower($ligne["table_name"])){
				@pg_close($this->connection);
				return true;
			}
		}
		@pg_close($this->connection);
	
		return false;
	}	
	public function LIST_TABLES($database){
		if(!$this->connect()){return false;}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		$sql="SELECT table_name FROM information_schema.tables WHERE table_catalog='$database'";
		$results = @pg_query ($this->connection, $sql);
		
		while($ligne=@pg_fetch_assoc($results)){
			$array[strtolower($ligne["table_name"])]=true;
				
		}
		@pg_close($this->connection);
		
		return $array;
	}
	
	
	
	function TABLE_SIZE($table){
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		if(!$this->connect()){return false;}
		$sql="SELECT
		relname AS objectname,
		relkind AS objecttype,
		reltuples AS \"#entries\", relpages::bigint AS size
		FROM pg_class
		WHERE relpages >= 8 AND relname LIKE '{$table}%'
				ORDER BY relpages DESC;";
		
		$results = @pg_query ($this->connection, $sql);
		$size=0;
		while($ligne=@pg_fetch_assoc($results)){
			$sizeB=$ligne["size"]*8;
			$sizeB=$sizeB*1024;
			$size=$size+$sizeB;
		}
		@pg_close($this->connection);
		return $size;
	}
	
	
	private function connect($database=null){
		if(!function_exists("pg_connect")){
			$this->ok=false;
			$this->mysql_error="pg_connect(), no such function...";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pg_connect no such function\n";}
			return;
		}
		$this->LoadConfig();
		if($database==null){$database=$this->database;}
		
		$this->connection = @pg_connect("host=$this->host user='ArticaStats' dbname='$database' connect_timeout='5'");
		
		if(!$this->connection){
			$this->mysql_error="Could not Connect to database service";
			$this->events_failed(__FUNCTION__,__LINE__);
			$this->ok=false;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		}
		$this->ok=true;
		return true;
	}
	
	public function CREATE_DATABASE($database){
		

		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$su=$unix->find_program("su");
			system("$su -c \"/usr/local/ArticaStats/bin/createdb -O ArticaStats -h /var/run/ArticaStats $database\" ArticaStats");
			if($this->DATABASE_EXISTS($database)){return true;}
		}

		
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		if(!$this->connect()){return false;}
		$results = @pg_query ($this->connection, "CREATE DATABASE $database OWNER \"ArticaStats\"");
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__,__LINE__);
			return false;
		}
		$this->ok=true;
		@pg_close($this->connection);
		return true;
	}
	
	
	public function QUERY_SQL($sql){
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__,__LINE__);
			$this->events("QUERY_SQL: Error: $this->mysql_error",__FILE__,__LINE__);
			$this->events("QUERY_SQL: [".substr($sql, 0,255)."]",__FILE__,__LINE__);
				
			return false;
				
		}
		
		$this->ok=true;
		@pg_close($this->connection);
		return $results;
		
	}
	
	

	
	private function is_index_exists($tablename,$key){
		if(!$this->connect($this->database)){return false;}
		$sql="SELECT relname, indkey FROM pg_class, pg_index WHERE pg_class.oid = pg_index.indexrelid AND pg_class.oid IN ( SELECT indexrelid FROM pg_index, pg_class WHERE pg_class.relname='$tablename' AND pg_class.oid=pg_index.indrelid AND indisunique != 't' AND indisprimary != 't');";
		
		$results = @pg_query ($this->connection, $sql);
		
		while($ligne=@pg_fetch_assoc($results)){
			if(strtolower($ligne["relname"])==strtolower($key)){return true;}
		}
		
	} 
	
	public function create_index($table,$indexname,$fields=array()){
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		$keyname="{$table}_$indexname";
		if($this->is_index_exists($table,$keyname)){return;}
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE INDEX $keyname ON $table (".@implode(",", $fields).");";
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if(preg_match("#already exists#", $this->mysql_error)){ @pg_close($this->connection); return true; }
			$this->events_failed(__FUNCTION__,__LINE__);
			$this->events("create_index: $this->mysql_error ON $table,$indexname",__FILE__,__LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error ON $table,$indexname\n";}
			@pg_close($this->connection);
			return false;
				
		}
		@pg_close($this->connection);
		return true;
		
	}
	
	private function events($text,$file,$line){
		$date=date("Y-m-d H:i:s");
		$pid=getmypid();
		$file=basename($file);
		$maxSize=900000;
		if(function_exists("events")){events("$text");}
		$line="$date [$pid] $file: $text  - in line:$line\n";
		$logFile="/var/log/artica.postgres.log";
		
		
		$size=@filesize($logFile);
		if($size>$maxSize){
			@unlink($logFile);
			@touch($logFile);
			@chmod($logFile, 0777);
		}
		
		$h = @fopen($logFile, 'a');
		@fwrite($h,$line);
		@fclose($h);
		
		
	}
	
	public function FIELD_EXISTS($table,$fieldname){
		$fieldname=strtolower($fieldname);
		if(!$this->connect($this->database)){return false;}
		$sql="select column_name from information_schema.columns where table_name = '$table'";
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			@pg_close($this->connection);
			return false;
				
		}
		
		while($ligne=@pg_fetch_assoc($results)){
			$array[strtolower($ligne["column_name"])]=true;
		
		}
		@pg_close($this->connection);
		if(!isset($array[$fieldname])){
			$this->mysql_error="in $table,$fieldname does not exists";
			$this->events_failed(__FUNCTION__, __LINE__);
			return false;}
		return true;
		
		
	}
	
	public function ADD_FIELD($tablename,$COLUMN,$type){
		$sql="ALTER TABLE $tablename ADD COLUMN $COLUMN $type";
		$this->QUERY_SQL($sql);
	}
	
	
	public function SMTP_TABLES(){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SMTP_TABLES...\n";}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS maillog
		(zdate timestamp,
		zmd5 varchar(90) primary key,
		fromdomain varchar(128),
		relay_s varchar(128),
		relay_r varchar(128),
		todomain varchar(128),
		frommail varchar(256),
		tomail varchar(256),
		size bigint,
		smtp_code int
		)";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing maillog\n";}
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("maillog","idate",array("zdate"));
		$this->create_index("maillog","ikeyf",array("frommail","fromdomain"));
		$this->create_index("maillog","ikeyr",array("todomain","tomail"));
		
		
		$sql="CREATE TABLE IF NOT EXISTS maillogsrc (sourcefile varchar(90) primary key)";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing maillogsrc\n";}
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS smtpstats_day
		(zdate timestamp,
		zmd5 varchar(90) primary key,
		domain varchar(128),
		grey bigint,
		black bigint,
		cnx bigint,
		hosts bigint,
		ips bigint,
		infos text				
		)";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpstats_day\n";}
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
				
		}
		
		$this->create_index("smtpstats_day","domaini",array("domain"));
		$this->create_index("smtpstats_day","sizei",array("grey","black","cnx","hosts","ips"));
		$this->create_index("smtpstats_day","zdate",array("zdate"));
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpstats_day DONE\n";}
		
	
		$sql="CREATE TABLE IF NOT EXISTS smtpcdir_day
		(zdate timestamp,
		zmd5 varchar(90) primary key,
		cdir varchar(128),
		grey bigint,
		black bigint,
		cnx bigint,
		hosts bigint,
		domains bigint,
		infos text
		)";
		
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpcdir_day: SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("smtpcdir_day","cdiri",array("cdir"));
		$this->create_index("smtpcdir_day","sizei",array("grey","black","cnx","hosts","domains"));
		$this->create_index("smtpcdir_day","zdate",array("zdate"));
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpcdir_day DONE\n";}
		
		$sql="CREATE TABLE IF NOT EXISTS smtpsum_day(
		zmd5 VARCHAR(90) primary key,
		zdate timestamp,
		recipients BIGINT,
		rejected BIGINT,
		bounced BIGINT,
		deferred BIGINT,
		forwarded BIGINT,
		delivered  BIGINT,
		received BIGINT)";
			
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsum_day: SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("smtpsum_day","zdate",array("zdate"));
		$this->create_index("smtpsum_day","ikey",array("recipients","rejected","bounced","deferred","forwarded","delivered","received"));
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpsum_day DONE\n";}
				
		$sql="CREATE TABLE IF NOT EXISTS smtpgraph_day(
		zmd5 VARCHAR(90) primary key,
		zdate timestamp,
		range VARCHAR(40),
		received BIGINT,
		delivered BIGINT,
		deferred BIGINT,
		bounced BIGINT,
		rejected  BIGINT)";
			
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsum_day: SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("smtpgraph_day","zdate",array("zdate"));
		$this->create_index("smtpgraph_day","irange",array("range"));
		$this->create_index("smtpgraph_day","ikey",array("received","delivered","deferred","bounced","rejected"));
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpgraph_day DONE\n";}
		
		
		$sql="CREATE TABLE IF NOT EXISTS smtpdeliver_day(
		zmd5 VARCHAR(90) primary key,
		zdate timestamp,
		domain VARCHAR(128),
		size BIGINT,
		rqs BIGINT )";
			
		if(!$this->connect($this->database)){return false;}
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpdeliver_day: SQL ERROR: $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("smtpdeliver_day","zdate",array("zdate"));
		$this->create_index("smtpdeliver_day","idomain",array("domain"));
		$this->create_index("smtpdeliver_day","isize",array("size","rqs"));
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpdeliver_day DONE\n";}
				
		
	$sql="CREATE TABLE IF NOT EXISTS smtpsenders_day (
	zmd5 varchar(90) primary key,
	zdate timestamp, email varchar(128), rqs bigint)";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	$this->create_index("smtpsenders_day","zdate",array("zdate"));
	$this->create_index("smtpsenders_day","iemail",array("email"));
	$this->create_index("smtpsenders_day","isize",array("rqs"));
	
	
//-----------------------------------------------------------------------------------------------------------------------------------	
	$sql="CREATE TABLE IF NOT EXISTS smtprecipients_day (
	zmd5 varchar(90) primary key,
	zdate timestamp, email VARCHAR(128), rqs bigint)";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	$this->create_index("smtprecipients_day","zdate",array("zdate"));
	$this->create_index("smtprecipients_day","iemail",array("email"));
	$this->create_index("smtprecipients_day","isize",array("rqs"));

//-----------------------------------------------------------------------------------------------------------------------------------
	$sql="CREATE TABLE IF NOT EXISTS smtprefused (
	zdate timestamp,
	hostname VARCHAR(128), 
	mailfrom VARCHAR(128),
	mailto VARCHAR(128),
	ipaddr inet,
	reason VARCHAR(90)		
	)";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	$this->create_index("smtprefused","zdate",array("zdate"));
	$this->create_index("smtprefused","iemail",array("mailfrom","mailto"));
	$this->create_index("smtprefused","ipaddr",array("ipaddr"));
	$this->create_index("smtprefused","reason",array("reason"));
	
//-----------------------------------------------------------------------------------------------------------------------------------		
	$sql="CREATE TABLE IF NOT EXISTS backupmsg (
	 ID SERIAL NOT NULL PRIMARY KEY,
	 zdate timestamp,
	 final BIGINT,
	 msgmd5 varchar(90),
	 size BIGINT,
	 subject varchar(255),
	 mailfrom varchar(128),
	 mailto varchar(128),
	 domainfrom varchar(128),
	 domainto varchar(128)
	 )";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	
	$this->create_index("backupmsg","zdate",array("zdate"));
	$this->create_index("backupmsg","ifinal",array("final"));
	$this->create_index("backupmsg","ifrom",array("mailfrom","domainfrom"));
	$this->create_index("backupmsg","ito",array("mailto","domainto"));
	$this->create_index("backupmsg","isubject",array("subject"));
	
	$sql="CREATE TABLE IF NOT EXISTS backupdata (
	msgmd5 varchar(90) primary key,
	zdate timestamp, final bigint,contentid BIGINT)";	
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	
	$this->create_index("backupdata","zdate",array("zdate"));
	$this->create_index("backupdata","ifinal",array("final"));
	
//--------------------------------------------------------------------------------------------------------	
	$sql="CREATE TABLE IF NOT EXISTS quarmsg (
	 ID SERIAL NOT NULL PRIMARY KEY,
	 zdate timestamp,
	 final BIGINT,
	 msgmd5 varchar(90),
	 size BIGINT,
	 subject varchar(255),
	 mailfrom varchar(128),
	 mailto varchar(128),
	 domainfrom varchar(128),
	 domainto varchar(128)
	 )";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	
	$this->create_index("quarmsg","zdate",array("zdate"));
	$this->create_index("quarmsg","ifinal",array("final"));
	$this->create_index("quarmsg","ifrom",array("mailfrom","domainfrom"));
	$this->create_index("quarmsg","ito",array("mailto","domainto"));
	$this->create_index("quarmsg","isubject",array("subject"));
	
	$sql="CREATE TABLE IF NOT EXISTS quardata (
	msgmd5 varchar(90) primary key,
	zdate timestamp, final bigint,contentid BIGINT)";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	
	$this->create_index("quardata","zdate",array("zdate"));
	$this->create_index("quardata","ifinal",array("final"));
	
//--------------------------------------------------------------------------------------------------------	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} attachstats: Checking...\n";}
	$sql="CREATE TABLE IF NOT EXISTS attachstats (
		zmd5 varchar(90) primary key,	
		zdate TIMESTAMP NOT NULL,
		mailfrom VARCHAR(255) NOT NULL,
		domainfrom VARCHAR(255) NOT NULL,
		mailto VARCHAR(255) NOT NULL,
		domainto VARCHAR(255) NOT NULL,
		fname VARCHAR(128) NOT NULL,
		ext VARCHAR(10) NOT NULL,
		contenttype VARCHAR(60) NOT NULL,
		size BIGINT )";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} attachstats: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	if(!$this->connect($this->database)){return false;}
		
	$this->create_index("attachstats","izdate",array("zdate"));
	$this->create_index("attachstats","ifrom",array("mailfrom","domainfrom"));
	$this->create_index("attachstats","ito",array("mailto","domainto"));
	$this->create_index("attachstats","isize",array("size"));
	$this->create_index("attachstats","ifile",array("contenttype","ext","fname"));
//--------------------------------------------------------------------------------------------------------	
	
	$sql="CREATE TABLE IF NOT EXISTS smtpstats ( 
	zmd5 varchar(90) primary key,	
	zdate timestamp,mailfrom varchar(255),
	domainfrom varchar(255),mailto varchar(255),domainto varchar(255),
	subject varchar(255),
	size BIGINT,
	spamscore int,
	spamreport text,
	disclaimer int,
	backuped int,
	infected int,
	filtered int,
	whitelisted int,
	compressed int,
	stripped int )";
	
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, $sql);
	if(!$results){
		$this->ok=false;
		$this->mysql_error=@pg_last_error($this->connection);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpstats: SQL ERROR: $this->mysql_error\n";}
		@pg_close($this->connection);
		return false;
	
	}
	if(!$this->connect($this->database)){return false;}
	$results = @pg_query ($this->connection, "alter table smtpstats add column if not exists spamreport text;");
	
	$this->create_index("smtpstats","izdate",array("zdate"));
	$this->create_index("smtpstats","ifrom",array("mailfrom","domainfrom"));
	$this->create_index("smtpstats","ito",array("mailto","domainto"));
	$this->create_index("smtpstats","iscore",array("spamscore","disclaimer","backuped","infected","filtered","whitelisted","compressed","stripped"));
//--------------------------------------------------------------------------------------------------------	
	}
	
	
	public function CREATE_DHCPD_TABLES(){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
		if(!$this->connect($this->database)){return false;}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS dhcpd_hosts (
				mac macaddr,
				created timestamp,
				updated timestamp,
				ipaddr inet,
				hostname VARCHAR(128) )";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing dhcpd_hosts\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
				
		}
		
		$this->create_index("dhcpd_hosts","maci",array("mac","ipaddr","hostname"));
		$this->create_index("dhcpd_hosts","zdate",array("created","updated"));
		
	}
	
	
	public function CREATE_TABLES(){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
		if(!$this->connect($this->database)){return false;}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS access_log
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		SITENAME VARCHAR(128) NULL,
		FAMILYSITE VARCHAR(128) NULL,				
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";
		
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_log\n";} 
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
			
		}
		
		$this->create_index("access_log","categoryi",array("familysite","category"));
		$this->create_index("access_log","useridi",array("userid","mac","ipaddr"));
		$this->create_index("access_log","sizei",array("SIZE","RQS"));
		$this->create_index("access_log","proxynamei",array("PROXYNAME"));
		$this->create_index("access_log","zdate",array("zdate"));
		
//-----------------------------------------------------------------------------------------------------------------------------------

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
		if(!$this->connect($this->database)){return false;}
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS haproxy_log
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_log\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
				
		}
		
		$this->create_index("haproxy_log","servicenamei",array("servicename","backend"));
		$this->create_index("haproxy_log","tsi",array("ts"));
		$this->create_index("haproxy_log","sizei",array("size","rqs"));
		$this->create_index("haproxy_log","proxynamei",array("proxyname"));
		$this->create_index("haproxy_log","zdate",array("zdate"));		
//-----------------------------------------------------------------------------------------------------------------------------------		
		$sql="CREATE TABLE IF NOT EXISTS haproxy_month
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_month\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("haproxy_month","servicenamei",array("servicename","backend"));
		$this->create_index("haproxy_month","tsi",array("ts"));
		$this->create_index("haproxy_month","sizei",array("size","rqs"));
		$this->create_index("haproxy_month","proxynamei",array("proxyname"));
		$this->create_index("haproxy_month","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
		$sql="CREATE TABLE IF NOT EXISTS haproxy_year
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_year\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		
		$this->create_index("haproxy_year","servicenamei",array("servicename","backend"));
		$this->create_index("haproxy_year","tsi",array("ts"));
		$this->create_index("haproxy_year","sizei",array("size","rqs"));
		$this->create_index("haproxy_year","proxynamei",array("proxyname"));
		$this->create_index("haproxy_year","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
		$sql="CREATE TABLE IF NOT EXISTS not_categorized
		(zdate timestamp,
		familysite varchar(128) primary key,
		rqs BIGINT,
		size BIGINT )";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing not_categorized\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("not_categorized","sizei",array("size","rqs"));
		$this->create_index("haproxy_year","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
		$sql="CREATE TABLE IF NOT EXISTS access_month
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		FAMILYSITE VARCHAR(128) NULL,
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_month\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
				
		}
		
		
		$this->create_index("access_month","categoryi",array("familysite","category"));
		$this->create_index("access_month","useridi",array("userid","mac","ipaddr"));
		$this->create_index("access_month","sizei",array("SIZE","RQS"));
		$this->create_index("access_month","proxynamei",array("PROXYNAME"));
		$this->create_index("access_month","zdate",array("zdate"));		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS access_year
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		FAMILYSITE VARCHAR(128) NULL,
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_year\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		
		$this->create_index("access_year","categoryi",array("familysite","category"));
		$this->create_index("access_year","useridi",array("userid","mac","ipaddr"));
		$this->create_index("access_year","sizei",array("SIZE","RQS"));
		$this->create_index("access_year","proxynamei",array("PROXYNAME"));
		$this->create_index("access_year","zdate",array("zdate"));
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS main_size (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing main_size\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
				
		}
		$this->create_index("main_size","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("main_size","keyi",array("zDate","SIZE"));
		
		
		
		
		
		
		
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS CACHED (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing CACHED table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("CACHED","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("CACHED","keyi",array("zDate","SIZE"));		
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS NO_CACHED (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing NO_CACHED table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("NO_CACHED","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("NO_CACHED","keyi",array("zDate","SIZE"));	

		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS proxy_requests (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, RQS BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing proxy_requests table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("proxy_requests","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("proxy_requests","keyi",array("zDate","RQS"));	

		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS websites (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, FAMILYSITE VARCHAR(128), SIZE BIGINT,RQS BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing websites table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("websites","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("websites","keyi",array("zDate","FAMILYSITE","RQS","SIZE"));
		

		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS webfilter (zDate timestamp, 
				PROXYNAME VARCHAR(128) NOT NULL, 
				website VARCHAR(128), 
				category VARCHAR(64),
				rulename VARCHAR(128),
				public_ip inet,
				blocktype VARCHAR(128),
				why VARCHAR(128),
				hostname VARCHAR(128),
				client VARCHAR(128),
				rqs BIGINT)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing webfilter table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("webfilter","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("webfilter","keyi",array("zDate","website","RQS"));
		$this->create_index("webfilter","zuid",array("hostname","client"));
		$this->create_index("webfilter","rule",array("category","rulename","blocktype"));
		
		$prefix="(zDate,IPADDR,SITENAME,HTTP_CODE,RQS,SIZE,PROXYNAME)";
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS apache_size (zDate timestamp,
				PROXYNAME VARCHAR(128) NOT NULL,
				IPADDR inet,
				SITENAME VARCHAR(128),
				HTTP_CODE INT,
				RQS BIGINT,
				SIZE BIGINT
				)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("apache_size","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("apache_size","keyi",array("zDate","IPADDR","RQS","SIZE"));
		$this->create_index("apache_size","zuid",array("IPADDR"));
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS dnsperfs (zDate timestamp,
				PROXYNAME VARCHAR(128) NOT NULL,
				DNS VARCHAR(128) NOT NULL,
				PERCENT INT,
				RESPONSE BIGINT
				)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("dnsperfs","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("dnsperfs","dnsi",array("DNS","PERCENT"));
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS ethrxtx (
				zDate timestamp,
				proxyname VARCHAR(128) NOT NULL,
				tx BIGINT NOT NULL,
				rx BIGINT NOT NULL,
				eth VARCHAR(30) NOT NULL
				)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("ethrxtx","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("ethrxtx","keyi",array("TX","RX","ETH"));

		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS squidmem (
				zDate timestamp,
				proxyname VARCHAR(128) NOT NULL,
				zpercent smallint NOT NULL,
				memory BIGINT NOT NULL
				)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		$this->create_index("squidmem","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("squidmem","keyi",array("zpercent","memory","zDate"));	

		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS system (
				zdate timestamp,
				proxyname VARCHAR(128) NOT NULL,
				load_avg float NOT NULL,
				mem_stats BIGINT NOT NULL,
				cpu_stats float NOT null
				)";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("system","PROXYNAMEi",array("PROXYNAME"));
		$this->create_index("system","keyi",array("zdate","load_avg","mem_stats","cpu_stats"));
		

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Check tables success\n";}
		$this->CREATE_DHCPD_TABLES();
	}
	
	public function suricata_tables(){
		
		if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return;}
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS suricatajson( time_received VARCHAR(64), ipver VARCHAR(4),
		srcip VARCHAR(40), dstip VARCHAR(40), protocol INTEGER, sp INTEGER, dp INTEGER,
		http_uri TEXT, http_host TEXT, http_referer TEXT, filename TEXT, magic TEXT, state VARCHAR(32),
		md5 VARCHAR(32), stored VARCHAR(32), size BIGINT,proxyname VARCHAR(128)
		);";
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("suricatajson","PROXYNAMEi",array("proxyname"));
		$this->create_index("suricatajson","keyi",array("time_received","ipver","srcip","dstip","state"));
		
		if(!$this->connect($this->database)){return false;}
		
		$sql="CREATE TABLE IF NOT EXISTS suricata_events (
		zDate timestamp,
		src_ip inet,
		dst_ip inet,
		dst_port smallint NOT NULL,
		proto varchar(10) NOT NULL,
		severity smallint NOT NULL,
		signature BIGINT,
		proxyname VARCHAR(128),
		xcount BIGINT)";
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("suricata_events","PROXYNAMEi",array("proxyname"));
		$this->create_index("suricata_events","keyi",array("zDate","src_ip","dst_ip","severity","signature","xcount"));
		
		if(!$this->connect($this->database)){return false;}
		
		$sql="CREATE TABLE IF NOT EXISTS suricata_sig (
		signature BIGINT PRIMARY KEY,
		description varchar(128),
		enabled smallint NOT NULL DEFAULT 1,
		firewall smallint NOT NULL DEFAULT 0,
		notify smallint NOT NULL DEFAULT 0 )";
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		
		
		if(!$this->FIELD_EXISTS("suricata_sig","firewall")){
			$this->ADD_FIELD("suricata_sig","firewall","smallint NOT NULL DEFAULT 0");
			$this->ADD_FIELD("suricata_sig","notify","smallint NOT NULL DEFAULT 0");
			$this->create_index("suricata_sig2","enabled",array("firewall",'notify','enabled'));
			
		}else{
			$this->create_index("suricata_sig","enabled",array("enabled"));
		}
		

		
		
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS suricata_classifications (
                ID SERIAL NOT NULL PRIMARY KEY,
                uduniq varchar(50) NOT NULL UNIQUE,
                shortname varchar(50) NOT NULL,
                description VARCHAR(128) NOT NULL,
                priority smallint NOT NULL)";
		
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		$this->create_index("suricata_classifications","ikey",array("uduniq","shortname","priority"));
		
		
		if(!$this->connect($this->database)){return false;}
		$sql="CREATE TABLE IF NOT EXISTS suricata_firewall (
                ID SERIAL NOT NULL PRIMARY KEY,
                uduniq varchar(50) NOT NULL UNIQUE,
				zdate timestamp,
			   signature BIGINT,
               src_ip inet,
               dst_port smallint NOT NULL,
			   proto varchar(10) NOT NULL,
			   xauto smallint NOT NULL DEFAULT 0,
			   proxyname varchar(128)
              )";
		
		$results = @pg_query ($this->connection, $sql);
		if(!$results){
			$this->ok=false;
			$this->mysql_error=@pg_last_error($this->connection);
			$this->events_failed(__FUNCTION__, __LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
			@pg_close($this->connection);
			return false;
		
		}
		
		if(!$this->FIELD_EXISTS("suricata_firewall","xauto")){
			$this->ADD_FIELD("suricata_firewall","xauto","smallint NOT NULL DEFAULT 0");
			$this->create_index("suricata_firewall","xauto",array("xauto"));
		
		}
		
		$this->create_index("suricata_firewall","ikey",array("uduniq","signature","src_ip","proxyname","zdate","xauto"));
		
		
		

		
		

		return true;
	}
	
	private function events_failed($function,$line){
		$date=date("Y-m-d H:i:s");
		$filename=basename(__FILE__);
		$common="/var/log/postgres.errors.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		if(function_exists("getmypid")){$pid=getmypid();}
	
		$line="$date [$pid] $filename: [$function/$line] $this->mysql_error\n";
		
	
		$h = @fopen($common, 'a');
		@fwrite($h,$line);
		@fclose($h);
			
	}
	
}
function pg_escape_string2($line){
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}
?>