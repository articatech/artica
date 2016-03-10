<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.tcpip.inc");

class mysql_catz{
	var $ClassSQL;
	private $called='';
	private $mysql_connection;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="catz";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	var $affected_rows=0;
	var $SocketPath=null;
	var $TryTCP=true;	
	var $arrayConfig=array();
	var $TimeExec=0;
	var $evArray=array();
	var $CacheDatabase="/usr/share/squid3/categories_caches.db";
	public $UfdbCatEnabled=0;
	public $FinalUsedServer;
	public $LocalUfdbCatEnabled=0;

	
	
	
	
	function mysql_catz($nomysql=false){
		
			
		$this->LoadParams();
		if($GLOBALS["EnableLocalUfdbCatService"]==1){
			$this->UfdbCatEnabled=1;
			$this->LocalUfdbCatEnabled=1;
			if($this->isRemoteSockets()){
				$this->LocalUfdbCatEnabled=0;
				$GLOBALS["RemoteUfdbCat"]=1;
				$GLOBALS["ufdbCatInterface"]=$this->GET_INFO("ufdbCatInterface");
				$GLOBALS["ufdbCatPort"]=intval($this->GET_INFO("ufdbCatPort"));
				return;
			}
			
			$GLOBALS["SquidPerformance"]=0;
			$GLOBALS["RemoteUfdbCat"]=0;
			return;
		}
		
		if($GLOBALS["AsCategoriesAppliance"]==1){
			$this->UfdbCatEnabled=1;
			$this->LocalUfdbCatEnabled=1;
			$GLOBALS["RemoteUfdbCat"]=1;
			$GLOBALS["ufdbCatInterface"]="127.0.0.1";
			$GLOBALS["ufdbCatPort"]=intval($this->GET_INFO("ufdbCatPort"));
			$GLOBALS["SquidPerformance"]=0;
			
		}
		
		
		if(!isset($GLOBALS["RemoteUfdbCat"])){$GLOBALS["RemoteUfdbCat"]=intval($this->GET_INFO("RemoteUfdbCat"));}
			
		if($GLOBALS["RemoteUfdbCat"]==1){
			$this->UfdbCatEnabled=1;
			$this->LocalUfdbCatEnabled=0;
			if(!isset($GLOBALS["ufdbCatPort"])){$GLOBALS["ufdbCatPort"]=intval($this->GET_INFO("ufdbCatPort"));}
			if(!isset($GLOBALS["ufdbCatInterface"])){$GLOBALS["ufdbCatInterface"]=$this->GET_INFO("ufdbCatInterface");}
		}
		
		if(!isset($GLOBALS["ufdbCatInterface"])){$GLOBALS["ufdbCatInterface"]=null;}
		if($GLOBALS["ufdbCatInterface"]==null){$GLOBALS["RemoteUfdbCat"]=0;}
		

	}
	
	private function GET_INFO($key){
		if(!is_file("/etc/artica-postfix/settings/Daemons/$key")){return null;}
		return trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
		
	}
	
	private function LoadParams(){
		
		if(!isset($GLOBALS["EnableLocalUfdbCatService"])){$GLOBALS["EnableLocalUfdbCatService"]=intval($this->GET_INFO("EnableLocalUfdbCatService"));}
		if(!isset($GLOBALS["AsCategoriesAppliance"])){$GLOBALS["AsCategoriesAppliance"]=intval($this->GET_INFO("AsCategoriesAppliance")); }
		if(!isset($GLOBALS["UfdbgclientSockTimeOut"])){$GLOBALS["UfdbgclientSockTimeOut"]=intval($this->GET_INFO("UfdbgclientSockTimeOut"));}
		if($GLOBALS["UfdbgclientSockTimeOut"]==0){$GLOBALS["UfdbgclientSockTimeOut"]=2;}
		if(!isset($GLOBALS["SquidPerformance"])){$GLOBALS["SquidPerformance"]=intval($this->GET_INFO("SquidPerformance"));}
		
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
			$this->LocalUfdbCatEnabled=1;
			$this->UfdbCatEnabled=1;
			return;
			
		}
		
		
		if($GLOBALS["AsCategoriesAppliance"]==1){
			$this->LocalUfdbCatEnabled=1;
			$this->UfdbCatEnabled=1;
			return;
		}
		
		if($GLOBALS["SquidPerformance"]==0){
				$this->UfdbCatEnabled=1;
				$this->LocalUfdbCatEnabled=1;
				return;
		}

		
		
		
	}
	
	
	function isRemoteSockets(){
		$AsCategoriesAppliance=intval($this->GET_INFO("AsCategoriesAppliance"));
		$EnableLocalUfdbCatService=intval($this->GET_INFO("EnableLocalUfdbCatService"));
		if($AsCategoriesAppliance==1){return true;}
	
		if($EnableLocalUfdbCatService==1){
			$ufdbCatInterface=$this->GET_INFO("ufdbCatInterface");
			if($ufdbCatInterface<>null){return true;}
		}
		return false;
	
	}
	
	public function TestingConnection(){return true;}
	
		
	
	
	
	function category_transform_name($category){
			if($category=="teans"){$category="teens";}
			if($category=="forum"){$category="forums";}
			if($category=="gambling"){$category="gamble";}
			if($category=="radio"){$category="webradio";}
			$category=str_replace('/',"_",$category);
			$category=str_replace('-',"_",$category);
			return $category;	
	}
	
	private function ToSyslog($text){
		$LOG_SEV=LOG_INFO;
		if(function_exists("openlog")){openlog("artica-categorize", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}

	private function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	private function ufdbcat_remote($sitename,$forcecache=false){
		$time_start = $this->microtime_float();
		$this->FinalUsedServer="tcp:{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]}";
		$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		$ret = @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $GLOBALS["UfdbgclientSockTimeOut"], 'usec' => 0));
		$ret = socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $GLOBALS["UfdbgclientSockTimeOut"], 'usec' => 0));
		$ret = @socket_get_option($socket, SOL_SOCKET, SO_RCVTIMEO);
		
		
		if($ret === false){
			$error=socket_strerror(socket_last_error());
			$this->ToSyslog("Error category socket tcp://{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} $error");
			return null;
		
		}
		
		if(!@socket_connect($socket, $GLOBALS["ufdbCatInterface"], $GLOBALS["ufdbCatPort"])){
			$socketerror=socket_strerror(socket_last_error($socket));
			$this->ToSyslog("Error category socket tcp://{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} $socketerror");
			if(is_resource($socket)){@socket_close($socket);}
			return null;
		}
				
		$ipClass=new IP();
		if($ipClass->isValid($sitename)){
			$sitename=ip2long($sitename).".addr";
		}

		$datatosend="http://$sitename 192.168.1.158/- - GET myip=192.168.1.238 myport=3128\n";
		@socket_write($socket, $datatosend, strlen($datatosend));
		
		$resp = @socket_read($socket, 1024);
		if(!$resp){
			$socketerror=socket_strerror(socket_last_error($socket));
			$this->ToSyslog("Error category socket tcp://{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} $socketerror");
			if(is_resource($socket)){@socket_close($socket);}
			return null;
		}
		
		$time_end = $this->microtime_float();
		$this->TimeExec = round($time_end - $time_start,3);
		if(is_resource($socket)){@socket_close($socket);}
		if($GLOBALS["VERBOSE"]){echo " **** > **** $resp\n";}
		if(preg_match("#\/none\/(.+?)$#", trim($resp),$re)){
			$re[1]=urldecode($re[1]);
			if(preg_match("#(.+?)\s+#", $re[1],$rz)){$re[1]=$rz[1];}
			if(preg_match("#^category_(.+)#", $re[1])){$re[1]=$this->tablename_tocat($re[1]);}
				
			$this->SetMemoryCache($sitename, $re[1]);
			$this->ok=true;
			return $re[1];
		}
		
		
		if($forcecache){$this->SetMemoryCache($sitename, null);}
		$this->ok=true;
		
	}
	
	public function ufdbcat($sitename,$forcecache=false){
		if(!isset($GLOBALS["UFDBCAT_SOCKET_PATH"])){$GLOBALS["UFDBCAT_SOCKET_PATH"]=null;}
		
		
		if($GLOBALS["UFDBCAT_SOCKET_PATH"]==null){
			if($this->is_socket("/var/run/ufdbcat-03978")){
				$GLOBALS["UFDBCAT_SOCKET_PATH"]="/var/run/ufdbcat-03978";
				$GLOBALS["RemoteUfdbCat"]=0;
				$GLOBALS["AsCategoriesAppliance"]=0;
				$GLOBALS["EnableLocalUfdbCatService"]=1;
			}
		}else{
			$GLOBALS["RemoteUfdbCat"]=0;
			
		}
		
		
		
		if($GLOBALS["RemoteUfdbCat"]==1){ 
			if($GLOBALS["VERBOSE"]){echo "this->ufdbcat_remote($sitename,$forcecache=false);<br>\n";}
			return $this->ufdbcat_remote($sitename,$forcecache=false);}
		
		if($GLOBALS["VERBOSE"]){echo "ufdbcat($sitename) RemoteUfdbCat 			   = 0<br>\n";}
		if($GLOBALS["VERBOSE"]){echo "ufdbcat($sitename) EnableLocalUfdbCatService = {$GLOBALS["EnableLocalUfdbCatService"]}<br>\n";}
		if($GLOBALS["VERBOSE"]){echo "ufdbcat($sitename) AsCategoriesAppliance     = {$GLOBALS["AsCategoriesAppliance"]}<br>\n";}
		
		
		
		
		
		if($GLOBALS["AsCategoriesAppliance"]==1){
			return $this->ufdbcat_remote($sitename,$forcecache=false);
		}
		
		
		if($GLOBALS["EnableLocalUfdbCatService"]==0){
			if($GLOBALS["SquidPerformance"]>0){
					$GLOBALS["CATZ"][$sitename]="NULL";
					return null;
			}
		}
		
		
			
		
		if($GLOBALS["UFDBCAT_SOCKET_PATH"]==null){
			if($this->is_socket("/var/run/ufdbcat-03978")){
				$GLOBALS["UFDBCAT_SOCKET_PATH"]="/var/run/ufdbcat-03978";
			}else{
				if($GLOBALS["VERBOSE"]){echo "/var/run/ufdbcat-03978 No such socket<br>\n";}
			}
				
		}
		
		if($GLOBALS["UFDBCAT_SOCKET_PATH"]==null){
			if($this->is_socket("/var/run/ufdbcat-03977")){
				$GLOBALS["UFDBCAT_SOCKET_PATH"]="/var/run/ufdbcat-03977";
			}else{
				if($GLOBALS["VERBOSE"]){echo "/var/run/ufdbcat-03977 No such socket<br>\n";}
			}
		}		
		
		
		if($GLOBALS["UFDBCAT_SOCKET_PATH"]==null){
			$this->mysql_error="Error No unix socket found";
			$this->ok=false;
			return false;
		}
		
		$this->FinalUsedServer="unix:{$GLOBALS["UFDBCAT_SOCKET_PATH"]}";
		$time_start = $this->microtime_float();
		if($GLOBALS["VERBOSE"]){echo " ->  stream_socket_client unix://{$GLOBALS["UFDBCAT_SOCKET_PATH"]}<br>\n";}
		$sock = @stream_socket_client("unix://{$GLOBALS["UFDBCAT_SOCKET_PATH"]}", $errno, $errst,$GLOBALS["UfdbgclientSockTimeOut"]);
		if(!$sock){
			if($GLOBALS["VERBOSE"]){echo "<li><strong> unix://{$GLOBALS["UFDBCAT_SOCKET_PATH"]} socket error</strong></li>\n";}
			$this->mysql_error="Error UnixSocket N.$errno $errst";
			$GLOBALS["ufdbcat"][date("i")]=false;
			if($GLOBALS["VERBOSE"]){echo "*************\n**************\n{$GLOBALS["UFDBCAT_SOCKET_PATH"]} $errno $errst\n*************\n**************\n";}
			$this->ToSyslog("Error unix://{$GLOBALS["UFDBCAT_SOCKET_PATH"]} $errno $errst");
			$this->ok=false;
			return false;
		}
		$GLOBALS["ufdbcat"][date("i")]=true;
		
		$sitename_ask=$sitename;
		$ipClass=new IP();
		if($ipClass->isValid($sitename_ask)){
			$sitename_ask=ip2long($sitename_ask).".addr";
		}
		
		
		if($GLOBALS["VERBOSE"]){echo " -> http://$sitename 192.168.1.158/- - GET myip=192.168.1.238 myport=3128<br>\n";}
		fwrite($sock, "http://$sitename 192.168.1.158/- - GET myip=192.168.1.238 myport=3128\n");
		$resp = trim(fread($sock, 4096));
		fclose($sock);
		
		$time_end = $this->microtime_float();
		$this->TimeExec = round($time_end - $time_start,3);
		$this->ok=true;
		
		if($GLOBALS["VERBOSE"]){echo " **** > **** $resp ". strlen($resp)." characters<br>\n";}
		if(strlen($resp)==0){
			if($forcecache){$this->SetMemoryCache($sitename, null);}
			return null;
		}
		
		if(preg_match("#\/none\/(.+?)$#", $resp,$re)){
			$category=urldecode($re[1]);

			if(preg_match("#^category_(.+)#", $category)){$category=$this->tablename_tocat($category);}
			$this->SetMemoryCache($sitename, $category);
			return $category;
		}
		
		
		if($forcecache){$this->SetMemoryCache($sitename, null);}
		return true;
		
	}
	
	private function is_socket($socketpath){
		$stat=$this->alt_stat($socketpath);
		$type=$stat["filetype"]["type"];
		if($type=="socket"){return true;}
		return false;
	}
	
	private function alt_stat($file=null) {
		if($file==null){return;}
		$ss=@stat($file);
		if(!$ss) return false; //Couldnt stat file
	
		$ts=array(
		  0140000=>'ssocket',
		  0120000=>'llink',
		  0100000=>'-file',
		  0060000=>'bblock',
		  0040000=>'ddir',
		  0020000=>'cchar',
		  0010000=>'pfifo'
		);
	
		$p=$ss['mode'];
		$t=decoct($ss['mode'] & 0170000); // File Encoding Bit
	
		$str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
		$str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
		$str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
		$str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
		$str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
		$str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
		$str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
	
		$s=array(
			 'perms'=>array(
			 		'umask'=>sprintf("%04o",@umask()),
			 		'human'=>$str,
			 		'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
			 		'octal2'=>sprintf("0%o", 0777 & $p),
			 		'decimal'=>sprintf("%04o", $p),
			 		'fileperms'=>@fileperms($file),
			 		'mode1'=>$p,
			 		'mode2'=>$ss['mode']),
					
			 'owner'=>array(
			 		'fileowner'=>$ss['uid'],
			 		'filegroup'=>$ss['gid'],
			 		'owner'=>
			 		(function_exists('posix_getpwuid'))?
			 		@posix_getpwuid($ss['uid']):'',
			 		'group'=>
			 		(function_exists('posix_getgrgid'))?
			 		@posix_getgrgid($ss['gid']):''
			 ),
					
			 'file'=>array(
			 		'filename'=>$file,
			 		'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
			 		'dirname'=>@dirname($file),
			 		'basename'=>@basename($file)
			 ),
	
			 'filetype'=>array(
			 		'type'=>substr($ts[octdec($t)],1),
			 		'type_octal'=>sprintf("%07o", octdec($t)),
			 		'is_file'=>@is_file($file),
			 		'is_dir'=>@is_dir($file),
			 		'is_link'=>@is_link($file),
			 		'is_readable'=> @is_readable($file),
			 		'is_writable'=> @is_writable($file)
			 ),
					
			 'device'=>array(
			 		'device'=>$ss['dev'], //Device
			 		'device_number'=>$ss['rdev'], //Device number, if device.
			 		'inode'=>$ss['ino'], //File serial number
			 		'link_count'=>$ss['nlink'], //link count
			 		'link_to'=>($s['type']=='link') ? @readlink($file) : ''
			 ),
					
			 'size'=>array(
			 		'size'=>$ss['size'], //Size of file, in bytes.
			 		'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
			 		'block_size'=> $ss['blksize'] //Optimal block size for I/O.
			 ),
					
			 'time'=>array(
			 		'mtime'=>$ss['mtime'], //Time of last modification
			 		'atime'=>$ss['atime'], //Time of last access.
			 		'ctime'=>$ss['ctime'], //Time of last status change
			 		'accessed'=>@date('Y M D H:i:s',$ss['atime']),
			 		'modified'=>@date('Y M D H:i:s',$ss['mtime']),
			 		'created'=>@date('Y M D H:i:s',$ss['ctime'])
			 ),
		);
	
		return $s;
	}
	
	private function tablename_tocat($tablename){
		if(isset($GLOBALS["tablename_tocat"][$tablename])){return $GLOBALS["tablename_tocat"][$tablename];}
		$trans=$this->TransArray();
		if(!isset($trans[$tablename])){return $tablename;}
			
	}
	
	
	private function setevents($line){
		$this->WLOGS($line);
		if($GLOBALS["VERBOSE"]){echo "$line<br>\n";}
		if(count($this->evArray)>500){$this->evArray=array();}
		$this->evArray[]=$line;
		
	}
	
	private function GET_LOCAL_DB($sitename){
		if(!is_file($this->CacheDatabase)){return;}
		$db_con = @dba_open($this->CacheDatabase, "r","db4");
		if(!@dba_exists($sitename,$db_con)){
			@dba_close($db_con);
			return;
		}
		
		$catz=dba_fetch($sitename,$db_con);
		@dba_close($db_con);
		return $catz;
	}
	
	private function berekley_db_create($db_path){
		if(is_file($db_path)){return true;}
		$db_desttmp = @dba_open($db_path, "c","db4");
		@chmod($db_path,0777);
		@dba_close($db_desttmp);
		if(!is_file($db_path)){return false;}
		return true;
	
	}
	
	private function SET_LOCAL_DB($sitename,$category){
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(trim($category)==null){return;}
		$key=md5("$sitename$category");
		if(isset($GLOBALS["SET_LOCAL_DB"][$key])){return;}
		if(!$this->berekley_db_create($this->CacheDatabase)){return;}
		$db_con = @dba_open($this->CacheDatabase, "w","db4");
		$GLOBALS["SET_LOCAL_DB"][$key]=true;
		if(!$db_con){@dba_close($db_con);return;}
		@dba_replace($sitename,$category,$db_con);
		@dba_close($db_con);
	}

	
	public function GET_CATEGORIES($sitename){
		
		$this->evArray=array();
		if($GLOBALS["RemoteUfdbCat"]==0){
			if($GLOBALS["SquidPerformance"]>1){
				$this->setevents("RemoteUfdbCat = 0 AND  SquidPerformance={$GLOBALS["SquidPerformance"]} return null...");
				return null;
			}
		}
		
		$keyMem=date("Ymdh");
		$orgsitename=$sitename;
		$cat=$this->GetMemoryCache($orgsitename);
		if($cat<>null){
			if($GLOBALS["VERBOSE"]){echo "->GetMemoryCache($orgsitename) ->  $cat<br>\n";}
			if($cat=="NULL"){$cat=null;}
			return $cat;
		}
		
		
		
		
		
		$orgsite=$sitename;
		$cat=$this->ufdbcat($sitename);
		$this->setevents("GET_CATEGORIES ufdbcat: $orgsitename = $cat");
		
		$this->SetMemoryCache($orgsitename, $cat);
		if($cat<>null){return $cat;}
	}
	
	
	private function WLOGS($event){
		if(!isset($GLOBALS["DEBUG"])){return;}
		if(!$GLOBALS["DEBUG"]){return;}
		if(!function_exists("WLOG")){return;}
		WLOG($event);
	}
	
	public function GetMemoryCache($sitename,$categorize=false){
		$fill=false;
		if(!isset($GLOBALS["CATZ"])){$GLOBALS["CATZ"]=array();}
		if(count($GLOBALS["CATZ"])>65000){unset($GLOBALS["CATZ"]);}
		if(isset($GLOBALS["CATZ"][$sitename])){
			if(strlen($GLOBALS["CATZ"][$sitename])>3){
				return $GLOBALS["CATZ"][$sitename];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "GetMemoryCache() ->->ufdbcat($sitename,true)<br>\n";}
		$catz=$this->ufdbcat($sitename,true);
		if($catz==null){$catz="NULL";}
		
		
		$this->setevents("GET_CATEGORIES:: ufdbcat($sitename,true) return $catz");
		$GLOBALS["CATZ"][$sitename]=$catz;
		return $catz;
	}
	
	public function SetMemoryCache($sitename,$category=null){
		if($category==null){$category="NULL";}
		$GLOBALS["CATZ"][$sitename]=$category;
		
		
	}
	
	
	
	
	public function LIST_TABLES_CATEGORIES($nocache=false){
		if(!$nocache){
			
			if(isset($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])){
				if(strlen($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])>2){
					return unserialize($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]);
				}
			}
		}
		
		$f=$this->TransArray();
		while (list ($table, $none) = each ($f) ){	
			if($this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` exists...\n";}
				$array[$table]=$table;
			}else{
				
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` no such table...\n";}
			}
		}
		
		$array=array();
		
		foreach (glob("/home/artica/categories_databases/*.db") as $filename) {
			$file=basename($filename);
			$file=str_replace(".db", "", $file);
			$array[$file]=$file;
		
		}

		
		$GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]=serialize($array);
		return $array;
		
	}	
	
	function COUNT_DATABASES(){
	
		$c=0;
		$array=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		while (list ($table, $items) = each ($array) ){
			$items=intval($items);
			if($items==0){continue;}
			$c++;
		}
	
		return $c;
	}	
	
	function COUNT_CATEGORIES(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$c=0;
		$array=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		if(!is_array($array)){return 0;}
		unset($array["TIME"]);
		while (list ($table, $items) = each ($array) ){
			$items=intval($items);
			$c=$c+$items;
		}
		$GLOBALS[__CLASS__.__FUNCTION__]=$c;
		return $c;
	}
	
	FUNCTION DELETE_TABLE($table){

	}		
	
	private function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
		$distanceInSeconds = round(abs($toTime - $fromTime));
		$distanceInMinutes = round($distanceInSeconds / 60);
	
		if ( $distanceInMinutes <= 1 ) {
			if ( !$showLessThanAMinute ) {
				return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
			} else {
				if ( $distanceInSeconds < 5 ) {
					return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 10 ) {
					return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 20 ) {
					return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
				}
				if ( $distanceInSeconds < 40 ) {
					return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 60 ) {
					return '{lessthanaminute}';
				}
	
				return '1 minute';
			}
		}
		if ( $distanceInMinutes < 45 ) {
			return $distanceInMinutes . ' {minutes}';
		}
		if ( $distanceInMinutes < 90 ) {
			return '{abouttime} 1 {hour}';
		}
		if ( $distanceInMinutes < 1440 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
		}
		if ( $distanceInMinutes < 2880 ) {
			return '1 {day}';
		}
		if ( $distanceInMinutes < 43200 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
		}
		if ( $distanceInMinutes < 86400 ) {
			return '{abouttime} 1 {month}';
		}
		if ( $distanceInMinutes < 525600 ) {
			return round(floatval($distanceInMinutes) / 43200) . ' {months}';
		}
		if ( $distanceInMinutes < 1051199 ) {
			return '{abouttime} 1 {year}';
		}
	
		return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
		 
	}	

function TransArray(){
		
			$trans["category_society"]="society";
			$trans["category_association"]="associations";
			$trans["category_publicite"]="publicite";
			$trans["category_phishtank"]="phishtank";
			$trans["category_shopping"]="shopping";
			$trans["category_abortion"]="abortion";
			$trans["category_agressive"]="agressive";
			$trans["category_alcohol"]="alcohol";
			$trans["category_animals"]="animals";
			$trans["category_associations"]="associations";
			$trans["category_astrology"]="astrology";
			$trans["category_audio_video"]="audio-video";
			$trans["category_youtube"]="youtube";
			$trans["category_automobile_bikes"]="automobile/bikes";
			$trans["category_automobile_boats"]="automobile/boats";
			$trans["category_automobile_carpool"]="automobile/carpool";
			$trans["category_automobile_cars"]="automobile/cars";
			$trans["category_automobile_planes"]="automobile/planes";
			$trans["category_bicycle"]="bicycle";
			$trans["category_blog"]="blog";
			$trans["category_books"]="books";
			$trans["category_browsersplugins"]="browsersplugins";
			$trans["category_celebrity"]="celebrity";
			$trans["category_chat"]="chat";
			$trans["category_children"]="children";
			$trans["category_cleaning"]="cleaning";
			$trans["category_clothing"]="clothing";
			$trans["category_converters"]="converters";
			$trans["category_cosmetics"]="cosmetics";
			$trans["category_culture"]="culture";
			$trans["category_dangerous_material"]="dangerous_material";
			$trans["category_dating"]="dating";
			$trans["category_dictionaries"]="dictionaries";
			$trans["category_downloads"]="downloads";
			$trans["category_drugs"]="drugs";
			$trans["category_dynamic"]="dynamic";
			$trans["category_electricalapps"]="electricalapps";
			$trans["category_electronichouse"]="electronichouse";
			$trans["category_filehosting"]="filehosting";
			$trans["category_finance_banking"]="finance/banking";
			$trans["category_finance_insurance"]="finance/insurance";
			$trans["category_finance_moneylending"]="finance/moneylending";
			$trans["category_finance_other"]="finance/other";
			$trans["category_finance_realestate"]="finance/realestate";
			$trans["category_financial"]="financial";
			$trans["category_forums"]="forums";
			$trans["category_gamble"]="gamble";
			$trans["category_games"]="games";
			$trans["category_genealogy"]="genealogy";
			$trans["category_gifts"]="gifts";
			$trans["category_governements"]="governements";
			$trans["category_governments"]="governments";
			$trans["category_green"]="green";
			$trans["category_hacking"]="hacking";
			$trans["category_handicap"]="handicap";
			$trans["category_health"]="health";
			$trans["category_hobby_arts"]="hobby/arts";
			$trans["category_hobby_cooking"]="hobby/cooking";
			$trans["category_hobby_other"]="hobby/other";
			$trans["category_hobby_pets"]="hobby/pets";
			$trans["category_paytosurf"]="paytosurf";
			$trans["category_terrorism"]="terrorism";
			$trans["category_hobby_fishing"]="hobby/fishing";
			$trans["category_hospitals"]="hospitals";
			$trans["category_houseads"]="houseads";
			$trans["category_housing_accessories"]="housing/accessories";
			$trans["category_housing_doityourself"]="housing/doityourself";
			$trans["category_housing_builders"]="housing/builders";
			$trans["category_humanitarian"]="humanitarian";
			$trans["category_imagehosting"]="imagehosting";
			$trans["category_industry"]="industry";
			$trans["category_internal"]="internal";
			$trans["category_isp"]="isp";
			$trans["category_smalladds"]="smalladds";
			$trans["category_jobsearch"]="jobsearch";
			$trans["category_jobtraining"]="jobtraining";
			$trans["category_justice"]="justice";
			$trans["category_learning"]="learning";
			$trans["category_liste_bu"]="liste_bu";
			$trans["category_luxury"]="luxury";
			$trans["category_mailing"]="mailing";
			$trans["category_malware"]="malware";
			$trans["category_manga"]="manga";
			$trans["category_maps"]="maps";
			$trans["category_marketingware"]="marketingware";
			$trans["category_medical"]="medical";
			$trans["category_mixed_adult"]="mixed_adult";
			$trans["category_mobile_phone"]="mobile-phone";
			$trans["category_models"]="models";
			$trans["category_movies"]="movies";
			$trans["category_music"]="music";
			$trans["category_nature"]="nature";
			$trans["category_news"]="news";
			
			$trans["category_passwords"]="passwords";
			$trans["category_phishing"]="phishing";
			$trans["category_photo"]="photo";
			$trans["category_pictureslib"]="pictureslib";
			$trans["category_politic"]="politic";
			$trans["category_porn"]="porn";
			$trans["category_press"]="news";
			$trans["category_proxy"]="proxy";
			$trans["category_reaffected"]="reaffected";
			$trans["category_recreation_humor"]="recreation/humor";
			$trans["category_recreation_nightout"]="recreation/nightout";
			$trans["category_recreation_schools"]="recreation/schools";
			$trans["category_recreation_sports"]="recreation/sports";
			$array["category_getmarried"]="getmarried";
			$array["category_police"]="police";
			$trans["category_recreation_travel"]="recreation/travel";
			$trans["category_recreation_wellness"]="recreation/wellness";
			$trans["category_redirector"]="redirector";
			$trans["category_religion"]="religion";
			$trans["category_remote_control"]="remote-control";
			
			$trans["category_sciences"]="sciences";
			$trans["category_science_astronomy"]="science/astronomy";
			$trans["category_science_computing"]="science/computing";
			$trans["category_science_weather"]="science/weather";
			$trans["category_science_chemistry"]="science/chemistry";
			$trans["category_searchengines"]="searchengines";
			$trans["category_sect"]="sect";
			$trans["category_sexual_education"]="sexual_education";
			$trans["category_sex_lingerie"]="sex/lingerie";
			$trans["category_smallads"]="smallads";
			
			$trans["category_socialnet"]="socialnet";
			$trans["category_spyware"]="spyware";
			$trans["category_sslsites"]="sslsites";
			$trans["category_stockexchange"]="stockexchange";
			$trans["category_strict_redirector"]="strict_redirector";
			$trans["category_strong_redirector"]="strong_redirector";
			$trans["category_suspicious"]="suspicious";
			$trans["category_teens"]="teens";
			$trans["category_tobacco"]="tobacco";
			$trans["category_tracker"]="tracker";
			$trans["category_translators"]="translators";
			$trans["category_transport"]="transport";
			$trans["category_tricheur"]="tricheur";
			$trans["category_updatesites"]="updatesites";
			$trans["category_violence"]="violence";
			$trans["category_warez"]="warez";
			$trans["category_weapons"]="weapons";
			$trans["category_webapps"]="webapps";
			$trans["category_webmail"]="webmail";
			$trans["category_webphone"]="webphone";
			$trans["category_webplugins"]="webplugins";
			$trans["category_webradio"]="webradio";
			$trans["category_webtv"]="webtv";
			$trans["category_wine"]="wine";
			$trans["category_womanbrand"]="womanbrand";	
			$trans["category_horses"]="horses";	
			$trans["category_meetings"]="meetings";	
			$trans["category_tattooing"]="tattooing";	
			$trans["category_advertising"]="publicite";	
			$trans["category_getmarried"]="getmarried";	
			$trans["category_literature"]="literature";
			$trans["category_police"]="police";
			$trans["category_search"]="searchengines";
			
			return $trans;		
		
	}	
	
	

	
	
	function CreateCategoryTable($category,$fulltablename=null){
	return true;
		
	}	
	
}


	

	
	

