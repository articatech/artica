<?php
if (!defined('CURLOPTTYPE_OBJECTPOINT')){define('CURLOPTTYPE_OBJECTPOINT', 10000);}
if (!defined('CURLOPT_NOPROXY')){define('CURLOPT_NOPROXY', CURLOPTTYPE_OBJECTPOINT + 177);}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["INFLUX"])){$GLOBALS["INFLUX"]=array();}
if(!isset($GLOBALS["DEBUG_INFLUX_VERBOSE"])){$GLOBALS["DEBUG_INFLUX_VERBOSE"]=false;}
$GLOBALS["AS_ROOT"]=false;
if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}
if(!isset($GLOBALS["DEBUG_INFLUX"])){$GLOBALS["DEBUG_INFLUX"]=false;}
include_once(dirname(__FILE__)."/externals/InfluxPHP/BaseHTTP.php");
include_once(dirname(__FILE__)."/externals/InfluxPHP/DB.php");
if(is_file(dirname(__FILE__)."/externals/InfluxPHP/Cursor.php")){include_once(dirname(__FILE__)."/externals/InfluxPHP/Cursor.php");}
include_once(dirname(__FILE__)."/externals/InfluxPHP/Client.php");
include_once(dirname(__FILE__)."/externals/InfluxPHP/ResultsetBuilder.php");
include_once(dirname(__FILE__)."/externals/InfluxPHP/ResultSeriesObject.php");
include_once(dirname(__FILE__)."/externals/InfluxPHP/MultipleResultSeriesObject.php");
include_once(dirname(__FILE__)."/class.sockets.inc");
include_once(dirname(__FILE__)."/class.postgres.inc");


class influx{
	public $curl_error;
	private $client;
	public $database;
	public $systemid=null;
	private $host;
	private $port=8086;
	public $db;
	public $ok=false;
	public $error=null;
	public $xurl=null;
	public $debug=false;
	private $line_insert;
	public $writeToFile=null;
	
	public function influx(){
		$this->LoadConfig();
		if($GLOBALS["VERBOSE"]){echo "DATABASE: proxydb\n";}
		//$this->BD_CONNECT();
		//$this->database="proxydb";
		//$this->CREATE_DATABASE();
		
		
	}
	
	public function insert_ufdb($line){
		
		if(!is_dir("/home/ufdb/relatime-events")){@mkdir("/home/ufdb/relatime-events",0755,true);}
		$file="/home/ufdb/relatime-events/ACCESS_LOG";
		
		
		$h = @fopen($file, 'a');
		@fwrite($h,$line."\n");
		@fclose($h);
		
		
		/*
		 * 	$array=array();
		$array["tags"]["uid"]=$user;
		$array["fields"]["TIME"]=$time;
		$array["fields"]["RQS"]=1;
		$array["tags"]["category"]=$category;
		$array["tags"]["rulename"]=$rulename;
		$array["tags"]["public_ip"]=$public_ip;
		$array["tags"]["blocktype"]="blocked domain";
		$array["tags"]["why"]="blocked domain";
		$array["tags"]["hostname"]=$Clienthostname;
		$array["tags"]["website"]=$www;
		$array["tags"]["client"]=$local_ip;
		 */
		
	}
	
	public function insert_ufdb_notif($line){
	
		if(!is_dir("/home/ufdb/smtp-events")){@mkdir("/home/ufdb/smtp-events",0755,true);}
		$file="/home/ufdb/smtp-events/ACCESS_LOG";
		$h = @fopen($file, 'a');
		@fwrite($h,$line."\n");
		@fclose($h);
		
	
	}	
	
	
	private function LoadConfig(){
		
		if(!isset($GLOBALS["SYSTEMID"])){$GLOBALS["SYSTEMID"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID");}
		$this->systemid=$GLOBALS["SYSTEMID"];
		
		if(!isset($GLOBALS["InfluxApiIP"])){
			$GLOBALS["InfluxApiIP"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxApiIP");
			if($GLOBALS["VERBOSE"]){echo "InfluxApiIP: {$GLOBALS["InfluxApiIP"]}\n";}
		}
		if($GLOBALS["InfluxApiIP"]==null){$GLOBALS["InfluxApiIP"]="127.0.0.1";}
		
		if(!isset($GLOBALS["InfluxUseRemote"])){
			$GLOBALS["InfluxUseRemote"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
		}
		
		if(!isset($GLOBALS["InfluxUseRemoteIpaddr"])){
			$GLOBALS["InfluxUseRemoteIpaddr"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemoteIpaddr"));
		}
		
		if(!isset($GLOBALS["InfluxUseRemotePort"])){
			$GLOBALS["InfluxUseRemotePort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemotePort"));
			if($GLOBALS["InfluxUseRemotePort"]==0){$GLOBALS["InfluxUseRemotePort"]=8086;}
		}
		if(!isset($GLOBALS["EnableInfluxDB"])){
			if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
			$GLOBALS["EnableInfluxDB"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB"));
		}
		
		
		
		
		if($GLOBALS["InfluxUseRemote"]==1){
			$GLOBALS["InfluxApiIP"]=$GLOBALS["InfluxUseRemoteIpaddr"];
			$this->port=$GLOBALS["InfluxUseRemotePort"];
		}
		
	
		
	}
	
	
	private function BD_CONNECT($noretry=false,$called=null){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		$this->host=$GLOBALS["InfluxApiIP"];
		$this->client = new \crodas\InfluxPHP\Client($this->host,$this->port,"root","root");
	}
	
	public function ROOT_DUMP_ALL_DATABASES(){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		return $this->LIST_DATABASES();
	}
	
	public function LIST_TABLES($database=null){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		if($database==null){$database=$this->database;}
		$db = $this->client->getDatabase($database);
		$main=$db->query("SHOW MEASUREMENTS");
		

		if(is_null($main)){return;}
		
		 foreach ($main as $row) {
		 	$name=trim($row->name);
		 	if($name==null){continue;}
		 	$array[$name]=$name;
		 	
		 	
		 }
		return $array;
	}
	
	private function LIST_DATABASES(){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		if(isset($GLOBALS["INFLUX"]["DATABASES"])){return $GLOBALS["INFLUX"]["DATABASES"];}
		$DATAS=$this->client->getDatabases();
		if(!is_array($DATAS)){return;}
		foreach ($this->client->getDatabases() as $db) {
			$GLOBALS["INFLUX"]["DATABASES"][$db->getName()]=true;
		}
		
		return $GLOBALS["INFLUX"]["DATABASES"];
		
	}
	
	private function CREATE_DATABASE(){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		$dbs=$this->LIST_DATABASES();
		if(isset($dbs[$this->database])){return true;}
		
	if($GLOBALS["VERBOSE"]){echo "Database: $this->database\n";}
		$this->db = $this->client->createDatabase($this->database);
		if(!$this->db){
			$this->events("Unable to create database $this->database", __FUNCTION__, __LINE__);
		}
		$GLOBALS["INFLUX"]["DATABASES"][$this->database]=true;
	}
	
	public function CREATE_NEW_DATABASE($databasename){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		$this->db = $this->client->createDatabase($databasename);
		if(!$this->db){
			$this->events("Unable to create database $this->database", __FUNCTION__, __LINE__);
			return false;
		}
		$GLOBALS["INFLUX"]["DATABASES"][$this->database]=true;
		return true;
	}
	
	

	
	public function DELETE_DATABASE($databasename){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		$this->client->deleteDatabase($databasename);
		
	}
	
	public function QUERY_SQL_API($sql){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		if(strpos($sql, strlen($sql)-1,1)<>";"){$sql=$sql.";";}
		$GLOBALS["TRACE_INFLUX"]=$sql;
		if($GLOBALS["DEBUG_INFLUX_VERBOSE"]){echo "$this->database -> $sql\n";}
		$db = $this->client->getDatabase($this->database);
		return $db->query("$sql");
		
		
	}
	
	public function DistanceHour($FirstTime,$SecondTime){
		$data1 = $FirstTime;
		$data2 = $SecondTime;
		$difference = ($data2 - $data1);
		$resultsMinute=intval($difference/60);
		if($resultsMinute<0){$resultsMinute=1;}
		$resultsHour=round($resultsMinute/60);
		return $resultsHour;
	}
	
	
	
	public function QUERY_SQL($sql){
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		if(strpos($sql, strlen($sql)-1,1)<>";"){$sql=$sql.";";}
		
		$trace=debug_backtrace();
		$this->events_migrate("QUERY $sql: ", __FUNCTION__, __LINE__);
		while (list ($index, $traces) = each ($trace) ){
			$file=basename($traces["file"]);
			$function=$traces["function"];
			$line=$traces["line"];
			$this->events_migrate("FROM[{$index}] $file: function: $function line:$line", $function, $line);
		}
		
		$data=$this->GetReadCurl($sql);
		
		$json=@json_decode($data);
		$c=0;
		$d=0;
		if(!isset($json->results[0]->series)){return false;}
		$TAGS=array();
		foreach ($json->results[0]->series as $resultElem) {
			if(isset($resultElem->tags)){
				$TAGS=array();
				reset($resultElem->columns);
				while (list ($key, $value) = each ($resultElem->tags) ){
					//echo "\$TAGS[$key]=$value;\n";
					$value=str_replace("'", "", $value);
					$value=str_replace("\"", "", $value);
					$TAGS[$key]=$value;
				}
			}
	
			
			$d=0;
			while (list ($key, $value) = each ($resultElem->values) ){
				reset($resultElem->columns);
				while (list ($index, $name) = each ($resultElem->columns) ){
					if(!isset($value[$index])){$value[$index]=null;}
					//echo "\$MAIN[$c][$name]=\$value[$index]={$value[$index]}\n";
					$MAIN[$c][$name]=$value[$index];
					
				}
				
				if(count($TAGS)>0){
					reset($TAGS);
					while (list ($index, $name) = each ($TAGS) ){
						//echo "\$MAIN[$c][$index]=$name\n";
						$MAIN[$c][$index]=$name;
					}
				}
				

				
				
				$c++;
				$d++;
			}
			
					
		}
		
		
		ini_set('memory_limit','1000M');
		return json_decode(json_encode(($MAIN)));
		
	
	}
	
	public function prepare($table,$ARRAY){
		$time=null;
		$precision=null;
		if(isset($ARRAY["precision"])){
			$precision=$ARRAY["precision"];
			unset($ARRAY["precision"]);
		}
		
		
		
		
		if(isset($ARRAY["time"])){
			$time=" ".$ARRAY["time"];
			unset($ARRAY["time"]);
		}
		
		if(isset($ARRAY["fields"]["time"])){
			$time=" ".$ARRAY["fields"]["time"];
			unset($ARRAY["fields"]["time"]);
		}
		
		if(count($ARRAY["tags"])>0){
			while (list ($key, $value) = each ($ARRAY["tags"]) ){
				if(trim($value)==null){continue;}
				$value=str_replace(" ", "\ ", $value);
				$value=str_replace(",", "\,", $value);
				$TAGS[]="$key=$value";
			}
		}
		
		if(count($ARRAY["fields"])>0){
			while (list ($key, $value) = each ($ARRAY["fields"]) ){
				$FIELDS[]="$key=". intval($value);
			}
				
		}
		
		
		
		$line="$table,".@implode(",", $TAGS)." ".@implode(",", $FIELDS).$time;
		return $line;	
		
		
	}
	
	public function bulk_inject($ARRAY){
		
		
		$this->events_migrate("bulk_inject()",__FILE__,__LINE__);
		$trace=debug_backtrace();
			while (list ($index, $traces) = each ($trace) ){
			$file=basename($traces["file"]);
			$function=$traces["function"];
			$line=$traces["line"];
			$this->events_migrate("FROM[{$index}] $file: function: $function line:$line", $function, $line);
		}
		
		
		
		$ch=$this->GetWriteCurl("s");
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		curl_setopt_array($ch, array(
		CURLOPT_POST =>  1,
		CURLOPT_POSTFIELDS => @implode("\n", $ARRAY)
		));
		
		return $this->execWriteCurl($ch);
		
		
	}
	
	public function files_inject($path){
		
		
		$this->events_migrate("files_inject($path)",__FILE__,__LINE__);
		$trace=debug_backtrace();
		while (list ($index, $traces) = each ($trace) ){
			$file=basename($traces["file"]);
			$function=$traces["function"];
			$line=$traces["line"];
			$this->events_migrate("QUERY FROM: ", $function, $line);
		}
		
	
		$ch=$this->GetWriteCurl("s");
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		curl_setopt_array($ch, array(
		CURLOPT_POST =>  1,
		CURLOPT_POSTFIELDS => @file_get_contents($path)
		));
	
		return $this->execWriteCurl($ch);
	
	
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
	
	
	
	public function insert($table,$ARRAY){
		
		
		$this->events_migrate("insert($table)",__FILE__,__LINE__);
		$trace=debug_backtrace();
			while (list ($index, $traces) = each ($trace) ){
			$file=basename($traces["file"]);
			$function=$traces["function"];
			$line=$traces["line"];
			$this->events_migrate("FROM[{$index}] $file: function: $function line:$line", $function, $line);
		}
		
		
		
		$time=null;
		$precision=null;
		if(isset($ARRAY["precision"])){
			$precision=$ARRAY["precision"];
			unset($ARRAY["precision"]);
		}
		
		
		
		
		if(isset($ARRAY["time"])){
			$time=" ".$ARRAY["time"];
			unset($ARRAY["time"]);
		}
		
		if(isset($ARRAY["fields"]["time"])){
			$time=" ".$ARRAY["fields"]["time"];
			unset($ARRAY["fields"]["time"]);
		}		
		
		if(count($ARRAY["tags"])>0){
			while (list ($key, $value) = each ($ARRAY["tags"]) ){
				$value=trim($value);
				if(strpos($value, " ")>0){$value=str_replace(" ", "\ ", $value);}
				if(strpos($value, ",")>0){$value=str_replace(",", "\,", $value);}
				$TAGS[]="$key=$value";
			}
		}
		
		if(count($ARRAY["fields"])>0){
			while (list ($key, $value) = each ($ARRAY["fields"]) ){
				$FIELDS[]="$key=". intval($value);
			}
			
		}
		

		
		$line="$table,".@implode(",", $TAGS)." ".@implode(",", $FIELDS).$time;
		//$this->events($line, __FUNCTION__, __LINE__);
		if($GLOBALS["VERBOSE"]){echo "$line\n";}
		$this->line_insert=$line;
		
		if($this->writeToFile<>null){
			$this->write_to_file($line);
			return true;
			
		}
		
		//$this->Error("$line",__FUNCTION__,__LINE__);
		if($GLOBALS["EnableInfluxDB"]==0){return;}
		$ch=$this->GetWriteCurl($precision);
		curl_setopt_array($ch, array(
		CURLOPT_POST =>  1,
		CURLOPT_POSTFIELDS => $line
		));
		
		return $this->execCurl($ch);
		
	}
	
	private function write_to_file($line){
		
		$h = fopen($this->writeToFile, 'a');
		fwrite($h,$line."\n");
		fclose($h);
		
	}
	
	
	protected function execWriteCurl($ch, $json = false){
		$response = curl_exec($ch);
		$status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$Infos= curl_getinfo($ch);
		if(curl_errno($ch)){
			$this->curl_error=curl_error($ch);
			$this->Error("Error:Curl error: $this->curl_error",__FUNCTION__,__LINE__);}
		curl_close($ch);
	
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:{$status[0]}\n";}
	
		if ($status[0] != 2) {
			$trace=debug_backtrace();
			$this->Error("**********************************************************************",__FUNCTION__,__LINE__);
				
			$CountDeTrace=count($trace);
				
			$this->Error($this->xurl,__FUNCTION__,__LINE__);
			$this->Error("Error:$response - $status - Traces:$CountDeTrace",__FUNCTION__,__LINE__);
			if($this->line_insert<>null){$this->Error($this->line_insert,__FUNCTION__,__LINE__);}
				
			while (list ($MDKey, $array) = each ($Infos) ){
				if(is_array($array)){
					while (list ($a, $b) = each ($array) ){
						$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);
					}
					continue;
				}
				$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);
					
			}
	
			$GLOBALS['LAST_ERROR_INFLUX']="$response - $status";
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
	
			if(isset($GLOBALS["TRACE_INFLUX"])){
				if($GLOBALS["TRACE_INFLUX"]<>null){
					$this->Error("Trace {$GLOBALS["TRACE_INFLUX"]}",__FUNCTION__,__LINE__);
					$GLOBALS["TRACE_INFLUX"]=null;
				}
			}
				
	
			for($i=0;$i<$CountDeTrace;$i++){
				$file=basename($trace[$i]["file"]);
				$function=$trace[$i]["function"];
				$line=$trace[$i]["line"];
				$this->Error("Trace $i/$CountDeTrace: $file $function $line",__FUNCTION__,__LINE__);
	
			}
			return false;
		}
		$GLOBALS["TRACE_INFLUX"]=null;
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
		return true;
	}
	
	
	protected function execCurl($ch, $json = false){
		$response = curl_exec($ch);
		$status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$Infos= curl_getinfo($ch);
		if(curl_errno($ch)){$this->Error("Error:Curl error: ". curl_error($ch),__FUNCTION__,__LINE__);}
		curl_close($ch);
	
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:{$status[0]}\n";}
	
		if ($status[0] != 2) {
			$trace=debug_backtrace();
			$this->Error("**********************************************************************",__FUNCTION__,__LINE__);
			
			$CountDeTrace=count($trace);
			
			$this->Error($this->xurl,__FUNCTION__,__LINE__);
			$this->curl_error="$response - $status";
			$this->Error("Error:$response - $status - Traces:$CountDeTrace",__FUNCTION__,__LINE__);
			if($this->line_insert<>null){$this->Error($this->line_insert,__FUNCTION__,__LINE__);}
			
			while (list ($MDKey, $array) = each ($Infos) ){
				if(is_array($array)){
					while (list ($a, $b) = each ($array) ){
						$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);
					}
					continue;
				}
				$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);
					
			}
			 
			$GLOBALS['LAST_ERROR_INFLUX']="$response - $status";
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
			 
			if(isset($GLOBALS["TRACE_INFLUX"])){
				if($GLOBALS["TRACE_INFLUX"]<>null){
					$this->Error("Trace {$GLOBALS["TRACE_INFLUX"]}",__FUNCTION__,__LINE__);
					$GLOBALS["TRACE_INFLUX"]=null;
				}
			}
			
			 
			for($i=0;$i<$CountDeTrace;$i++){
				$file=basename($trace[$i]["file"]);
				$function=$trace[$i]["function"];
				$line=$trace[$i]["line"];
				$this->Error("Trace $i/$CountDeTrace: $file $function $line",__FUNCTION__,__LINE__);
	
			}
			return json_decode($response, true);
		}
		$GLOBALS["TRACE_INFLUX"]=null;
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
		return $json ? json_decode($response, true) : $response;
	}	
	
	private function GetWriteCurl($precision=null){
		if($precision==null){$precision="u";}
		if($GLOBALS["VERBOSE"]){echo "getCurl({$this->host}:{$this->port})\n";}
		$url  = "http://{$this->host}:{$this->port}/write?db={$this->database}&precision=$precision";
		if($GLOBALS["VERBOSE"]){echo "getCurl exec: $url\n";}
		$ch   = curl_init($url);
		$this->xurl=$url;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOPROXY,"127.0.0.1,localhost");
		curl_setopt($ch, CURLOPT_PROXY,null);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		return $ch;
	}
	private function GetReadCurl($sql,$json=false){
		if($GLOBALS["VERBOSE"]){echo "getCurl({$this->host}:{$this->port})\n";}
		$sql=urlencode($sql);
		$url  = "http://{$this->host}:{$this->port}/query?db={$this->database}&q=$sql";
		$ch   = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOPROXY,"127.0.0.1,localhost");
		curl_setopt($ch, CURLOPT_PROXY,null);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		$response = curl_exec($ch);
		$status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$Infos= curl_getinfo($ch);
		if(curl_errno($ch)){$this->Error("Error:Curl error: ". curl_error($ch),__FUNCTION__,__LINE__);}
		curl_close($ch);
		
		
		
		if ($status[0] != 2) {
			$trace=debug_backtrace();
			$CountDeTrace=count($trace);
			$this->Error("**********************************************************************",__FUNCTION__,__LINE__);
			$this->Error($this->xurl,__FUNCTION__,__LINE__);
			$this->Error("Error:$response - $status - Traces:$CountDeTrace",__FUNCTION__,__LINE__);
				
			while (list ($MDKey, $array) = each ($Infos) ){
				if(is_array($array)){while (list ($a, $b) = each ($array) ){$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);}continue;}
				$this->Error("Error:$MDKey = $array",__FUNCTION__,__LINE__);
			}
		
			$GLOBALS['LAST_ERROR_INFLUX']="$response - $status";
		
			
			for($i=0;$i<$CountDeTrace;$i++){
				$file=basename($trace[$i]["file"]);
				$function=$trace[$i]["function"];
				$line=$trace[$i]["line"];
				$this->Error("Trace $i/$CountDeTrace: $file $function $line",__FUNCTION__,__LINE__);
		
			}
			return json_decode($response, true);
		}
		
		
		return $json ? json_decode($response, true) : $response;
		
	}	
	
	private function events_migrate($text,$function,$line){
		$date=date("Y-m-d H:i:s");
		$filename=basename(__FILE__);
		$common="/var/log/influx.migrate.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		if(function_exists("getmypid")){$pid=getmypid();}
	
		$line="$date [$pid] $filename: [$function/$line] $text\n";
		if($GLOBALS["VERBOSE"]){echo $line;return;}
	
		$h = @fopen($common, 'a');
		@fwrite($h,$line);
		@fclose($h);
			
	}
	
	
	
	private function events($text,$function,$line){
		$date=date("Y-m-d H:i:s");
		$filename=basename(__FILE__);
		$common="/var/log/influx.client.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		if(function_exists("getmypid")){$pid=getmypid();}
		
		$line="$date [$pid] $filename: [$function/$line] $text\n";
		if($GLOBALS["VERBOSE"]){echo $line;return;}
		
		$h = @fopen($common, 'a');
		@fwrite($h,$line);
		@fclose($h);
		 
	}	
	
	private function Error($text,$function,$line){
		if(isset($GLOBALS["VERBOSE"])){
			if($GLOBALS["VERBOSE"]){$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;}
		}
		if(!isset($GLOBALS["DEBUG_INFLUX_VERBOSE"])){$GLOBALS["DEBUG_INFLUX_VERBOSE"]=false;}
		if($GLOBALS["DEBUG_INFLUX"]){
			if(function_exists("events")){events("$function/$line $text");}
		}
	
	
		$REBUILD=false;
		$filename=basename(__FILE__);
		$date=date("Y-m-d H:i:s");
		if(function_exists("getmypid")){$pid=getmypid();}
		$line="$date [$pid] $filename: [$function/$line] $text\n";
		if($GLOBALS["DEBUG_INFLUX_VERBOSE"]){echo $line;}
		$common="/var/log/influx.client.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);$REBUILD=true;}
		$h = @fopen($common, 'a');
		@fwrite($h,$line);
		@fclose($h);
		if($REBUILD){@chmod($common,0777);}
	
	}	
	
}