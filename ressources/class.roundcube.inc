<?php
	include_once(dirname(__FILE__).'/class.artica.inc');
	include_once(dirname(__FILE__).'/class.mysql.inc');
	include_once(dirname(__FILE__).'/class.ini.inc');
	
	class roundcube{
		var $https_port=449;
		var $mysql_error=null;
		var $mysql_errornum=null;
		var $lighttp_min_proc;
		var $lighttp_max_proc;
		var $lighttp_max_load_per_proc;
		var $PHP_FCGI_CHILDREN;
		var $PHP_FCGI_MAX_REQUESTS;
		var $RoundCubeLightHTTPD;
		var $RoundCubeHTTPEngineEnabled;
		var $roundCubeArray=array();
		var $ini_flat;
		var $ssl_enabled=1;
		var $roundcube_plugins_line=null;
		var $roundcube_plugins_array=array();
		var $roundcubeWebsites=array();
		var $AsRoot=false;
		var $mysql_server;
		var $mysql_admin;
		var $mysql_password;
		var $mysql_port;
		var $SocketPath;
		var $MYSQL_CMDLINES=null;
		var $database="roundcubemail";
		var $dbidsn;
		var $SieveListenIp;
		var $root_path="/usr/share/roundcube";
		var $ok=true;
		private $ClassSQL;
		
		function roundcube($database=null){
			$artica=new artica_general();
			if(posix_getuid()==0){
				$this->AsRoot=true;
				include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			}
			if($database<>null){$this->database=$database;}
			
			$users=new usersMenus();
			$sock=new sockets();
			$this->ParseMysqlParams();
			$this->RoundCubeLightHTTPD=$artica->RoundCubeLightHTTPD;
			$this->RoundCubeHTTPEngineEnabled=$artica->RoundCubeHTTPEngineEnabled;
			
			if($this->RoundCubeLightHTTPD==null){$this->RoundCubeLightHTTPD=$this->Build_lighthttp();}
			if($this->RoundCubeHTTPEngineEnabled==null){$this->RoundCubeHTTPEngineEnabled=0;}
			$this->Parse_lighttpd();
			$ini=new Bs_IniHandler();
			
			if(trim($artica->RoundCubeArticaConfigurationFile)==null){$artica->RoundCubeArticaConfigurationFile=$sock->getfile("INFOS:RoundCubeArticaConfigurationFile");}
			$this->RoundCubeHTTPEngineEnabled=$artica->RoundCubeHTTPEngineEnabled;		
			$ini->loadString($artica->RoundCubeArticaConfigurationFile);
			$this->roundCubeArray=$ini->_params["CONF"];
			$this->ssl_enabled=$ini->_params["CONF"]["ssl_enabled"];
			$this->roundcubeWebsites=$ini->_params;			
			$this->BuilDefault();
			
			$this->roundcube_plugins_line=$users->roundcube_plugins;
			$this->roundcube_plugins_array=$this->BuildPlugins();
			
	}
	
	private function parseNewParams(){
		$sock=new sockets();
		$this->roundCubeArray["ldap_ok"]=intval($sock->GET_INFO("RoundCubeEnableLDAP"));
		$this->roundCubeArray["default_host"]=$sock->GET_INFO("RoundCubeDefaultHost");
		$this->roundCubeArray["user_link"]=$sock->GET_INFO("RoundCubeUserLink");
		$this->roundCubeArray["locale_string"]=$sock->GET_INFO("RoundeCubeLocalString");
		$this->roundCubeArray["product_name"]=$sock->GET_INFO("RoundCubeProductName");
		
		
		$this->roundCubeArray["debug_level"]=intval($sock->GET_INFO("RoundCubeDebugLevel"));
		
		
		
		$RoundCubeSkipDeleted=intval($sock->GET_INFO("RoundCubeSkipDeleted"));
		$RoundCubeFlagForDeletion=intval($sock->GET_INFO("RoundCubeFlagForDeletion"));
		
		
		if($RoundCubeSkipDeleted==1){$this->roundCubeArray["skip_deleted"]="TRUE";}else{$this->roundCubeArray["skip_deleted"]="FALSE"; }
		if($RoundCubeFlagForDeletion==1){$this->roundCubeArray["flag_for_deletion"]="TRUE";}else{$this->roundCubeArray["flag_for_deletion"]="FALSE"; }
		
		$RoundCubeEnableCaching=intval($sock->GET_INFO("RoundCubeEnableCaching"));
		if($RoundCubeEnableCaching==1){$this->roundCubeArray["enable_caching"]="TRUE";}else{$this->roundCubeArray["enable_caching"]="FALSE"; }
		
		$RoundCubeAutoCreateuser=intval($sock->GET_INFO("RoundCubeAutoCreateuser"));
		if($RoundCubeAutoCreateuser==1){$this->roundCubeArray["auto_create_user"]="TRUE";}else{$this->roundCubeArray["auto_create_user"]="FALSE";}
		
		
		$RoundCubeSievePort=$sock->GET_INFO("RoundCubeSievePort");
		if($RoundCubeSievePort==null){$RoundCubeSievePort=$this->SieveListenIp.":4190";}

		if(is_numeric($RoundCubeSievePort)){$this->roundCubeArray["sieve_port"]=$RoundCubeSievePort;}
		
		if(preg_match("#(.+?):([0-9]+)#", $RoundCubeSievePort,$re)){
			$this->SieveListenIp=$re[1];
			$this->roundCubeArray["sieve_port"]=$re[2];
		}

		
		if($this->roundCubeArray["product_name"]==null){$this->roundCubeArray["product_name"]="RoundCube Webmail for Artica";}
		if($this->roundCubeArray["user_link"]==null){$this->roundCubeArray["user_link"]=$this->BuildUserlink();}
		
		
	}
	
	private function ParseMysqlParams(){
		$sock=new sockets();
		$this->SocketPath="/var/run/mysqld/mysqld.sock";
		$def=array();
		$RoundCubeDedicateMySQLServer=intval($sock->GET_INFO("RoundCubeDedicateMySQLServer"));
		$MySQLSyslogType=$sock->GET_INFO("RoundCubeMySQLServiceType");
		if(!is_numeric($MySQLSyslogType)){
			if($RoundCubeDedicateMySQLServer==1){
				$MySQLSyslogType=3;
			}
		}
		
		if(!is_numeric($MySQLSyslogType)){
			$MySQLSyslogType=0;
		}
		
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} RoundCubeMySQLServiceType....: $MySQLSyslogType\n";}
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} RoundCubeDedicateMySQLServer.: $RoundCubeDedicateMySQLServer\n";}
		
		if($MySQLSyslogType>0){
		
			if($MySQLSyslogType==4){
				
				$this->mysql_admin=$sock->GET_INFO("RoundCubeRemoteMySQLServerAdmin");
				$this->mysql_password=$sock->GET_INFO("RoundCubeRemoteMySQLServerPassword");
				$this->mysql_server=$sock->GET_INFO("RoundCubeRemoteMySQLServer");
				$this->mysql_port=$sock->GET_INFO("RoundCubeRemoteMySQLServerPort");
				$this->SocketPath=null;
				$def["TryTCP"]=true;
				$auth=$this->mysql_admin;
				$def["mysql_server"]=$this->mysql_server;
				$def["mysql_port"]=$this->mysql_port;
				if($this->mysql_password<>null){$auth="$this->mysql_admin:$this->mysql_password";}
				$this->MYSQL_CMDLINES="--host=$this->mysql_server --port=$this->mysql_port -u $this->mysql_admin -p" .$sock->shellEscapeChars($this->mysql_password);
				$dsn="$auth@$q->mysql_server:$q->mysql_port/$this->database";
				$this->dbidsn=$dsn;
			}
			if($MySQLSyslogType==3){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$def["mysql_admin"]=$this->mysql_admin;
				$def["mysql_password"]=$this->mysql_password;
				$this->SocketPath="/var/run/mysqld/roundcube-db.sock";
				$def["TryTCP"]=false;
				$def["mysql_server"]="127.0.0.1";
				$def["SocketPath"]=$this->SocketPath;
				$this->MYSQL_CMDLINES="--protocol=socket --socket=$this->SocketPath -u root";
				$dsn="root@unix($this->SocketPath)/$this->database";
				$this->dbidsn=$dsn;
			}
		
		
		
			
		}else{
			
		$q=new mysql();
		if($q->mysql_server=="localhost"){$q->mysql_server="127.0.0.1";}
		if($q->mysql_server=="localhost.localdomain"){$q->mysql_server="127.0.0.1";}
	
					if($q->mysql_server=="127.0.0.1"){
						$token[]="--protocol=socket --socket=$q->SocketName";
						
					}else{
						$token[]="--host=$q->mysql_server --port=$q->mysql_port";
					}			
					
					
					if($q->mysql_admin<>null){$token[]="--user=$q->mysql_admin";}
					if($q->mysql_password<>null){$token[]="--password=".$sock->shellEscapeChars($this->mysql_password);}
					$token[]="--database=$this->database";
					$token[]="--silent";
					$this->MYSQL_CMDLINES=@implode(" ", $token);
		}

				
				
				
		
		
		$this->ClassSQL=new mysql($def);
		if(!$this->ClassSQL->DATABASE_EXISTS($this->database)){
				$this->ClassSQL->CREATE_DATABASE($this->database);
				if($this->AsRoot){
					$unix=new unix();
					$php5=$unix->LOCATE_PHP5_BIN();
					$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.roundcube.php");
				}
		}
		return;
		
		
	}
	
	public function DATABASE_EXISTS($database){
		return $this->ClassSQL->DATABASE_EXISTS($database);
		
	}
	
	public function TABLE_EXISTS($table){
		return $this->ClassSQL->TABLE_EXISTS($table, $this->database);
	}
	
	public function QUERY_SQL($sql){
		$this->ok=true;
		$results=$this->ClassSQL->QUERY_SQL($sql, $this->database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error; 
		$this->mysql_errornum=$this->ClassSQL->mysql_errornum;
		return $results;
	}
	
		
	function Parse_lighttpd(){
		$tbl=explode("\n",$this->RoundCubeLightHTTPD);
		$this->ssl_enabled=0;
		while (list ($num, $line) = each ($tbl)){
			if(preg_match('#min-procs.+?([0-9]+)#',$line,$re)){$this->lighttp_min_proc=$re[1];}
			if(preg_match('#server\.port\s+=\s+([0-9]+)#',$line,$re)){$this->https_port=$re[1];}
			if(preg_match('#max-procs.+?([0-9]+)#',$line,$re)){$this->lighttp_max_proc=$re[1];}
			if(preg_match('#max-load-per-proc.+?([0-9]+)#',$line,$re)){$this->lighttp_max_load_per_proc=$re[1];}
			if(preg_match('#PHP_FCGI_CHILDREN.+?([0-9]+)#',$line,$re)){$this->PHP_FCGI_CHILDREN=$re[1];}
			if(preg_match('#PHP_FCGI_MAX_REQUESTS.+?([0-9]+)#',$line,$re)){$this->PHP_FCGI_MAX_REQUESTS=$re[1];}
			if(preg_match('#ssl\.engine.+?enable#',$line,$re)){$this->ssl_enabled=1;}
		}
		
		$sock=new sockets();
		$this->https_port=$sock->GET_INFO("RoundCubeHTTPSPort");
		if(!is_numeric($this->https_port)){$this->https_port=449;}
		
	}
	
		
	function BuildPlugins(){
		$arr=explode(';',$this->roundcube_plugins_line);
		if(!is_array($arr)){return array();}
		while (list ($num, $line) = each ($arr)){
			if(trim($line)==null){continue;}
			$array[$line]="{{$line}}";
		}
		
		return $array;
	}
	
	function BuildConfig(){
		return $this->RoundCubeConfig();
	}

	function Save(){
		$sock=new sockets();
		$sock->SET_INFO("RoundCubeLightHTTPD",$this->RoundCubeLightHTTPD);
		$sock->SET_INFO("RoundCubeHTTPEngineEnabled",$this->RoundCubeHTTPEngineEnabled);
		$sock->SET_INFO('php5UploadMaxFileSize',$this->roundCubeArray["upload_max_filesize"]);	

		$mainconf=$this->Build_lighthttp();
		$sock->SaveConfigFile($mainconf,"RoundCubeLightHTTPD");
		$sock->SaveConfigFile($this->BuildConf(),"RoundCubeArticaConfigurationFile");
		$sock->SaveConfigFile($this->RoundCubeConfig(),"RoundCubeConfigurationFile");
		
		if(!$this->AsRoot){
			$sock->getFrameWork("cmd.php?roundcube-restart=yes");
		}
		
	}
	
	function BuildUserlink(){
		if($this->RoundCubeHTTPEngineEnabled==1){
			$_user=new usersMenus();
			return "https://$_user->fqdn:$this->https_port";
		}else {
			include_once(dirname(__FILE__).'/class.httpd.inc');
			$_user=new usersMenus();
			$h=new httpd();
			return "https://$_user->fqdn:$h->https_port/webmail";
		}
		
	}
	
	function BuilDefault(){
				$sock=new sockets();
				if($this->https_port==null){$this->https_port=443;}
				if($this->lighttp_min_proc==null){$this->lighttp_min_proc=1;}
				if(trim($this->lighttp_max_proc==null)){$this->lighttp_max_proc=2;}
				if($this->lighttp_max_load_per_proc==null){$this->lighttp_max_load_per_proc=2;}
				if($this->PHP_FCGI_CHILDREN==null){$this->PHP_FCGI_CHILDREN=4;}
				if($this->PHP_FCGI_MAX_REQUESTS==null){$this->PHP_FCGI_MAX_REQUESTS=100;}
				$users=new usersMenus();
				$this->parseNewParams();
				if($this->roundCubeArray["debug_level"]==null){$this->roundCubeArray["debug_level"]=1;}
				if($this->roundCubeArray["enable_caching"]==null){$this->roundCubeArray["enable_caching"]="TRUE";}
				if($this->roundCubeArray["auto_create_user"]==null){$this->roundCubeArray["auto_create_user"]="TRUE";}
				if($this->roundCubeArray["default_host"]==null){$this->roundCubeArray["default_host"]="127.0.0.1";}
				if($this->roundCubeArray["locale_string"]==null){$this->roundCubeArray["locale_string"]="us";}
				if($this->roundCubeArray["product_name"]==null){$this->roundCubeArray["product_name"]="RoundCube Webmail for Artica";}				
				if($this->roundCubeArray["user_link"]==null){$this->roundCubeArray["user_link"]=$this->BuildUserlink();}
				if($this->roundCubeArray["ldap_ok"]==null){$this->roundCubeArray["ldap_ok"]=0;}
				if($this->roundCubeArray["skip_deleted"]==null){$this->roundCubeArray["skip_deleted"]="FALSE";}
				if($this->roundCubeArray["flag_for_deletion"]==null){$this->roundCubeArray["flag_for_deletion"]="TRUE";}
				if($this->roundCubeArray["ssl_enabled"]==null){$this->roundCubeArray["ssl_enabled"]="1";}
				
				$this->roundCubeArray["upload_max_filesize"]=$sock->GET_INFO('php5UploadMaxFileSize');
				if($this->roundCubeArray["upload_max_filesize"]==null){$this->roundCubeArray["upload_max_filesize"]="2";}
				
				if(!isset($this->roundCubeArray["sieve_port"])){$this->roundCubeArray["sieve_port"]=4190;}
				if(!is_numeric($this->roundCubeArray["sieve_port"])){$this->roundCubeArray["sieve_port"]=4190;}
				$this->SieveListenIp=$sock->GET_INFO("SieveListenIp");
				if($this->SieveListenIp==null){$this->SieveListenIp="127.0.0.1";}
				
				
	}
	
	function  BuildConf(){
		$conf="[CONF]\n";
		
		while (list ($num, $line) = each ($this->roundCubeArray)){
			if(!is_array($line)){
				if(!is_array($line)){$conf=$conf. "$num=$line\n";}
			}
			
		}
		
		$conf=$conf. "ssl_enabled=$this->ssl_enabled\n";
		
		
		if(is_array($this->roundcubeWebsites)){
			while (list ($num, $line) = each ($this->roundcubeWebsites)){
				if($num=="CONF"){continue;}
					$conf=$conf. "[$num]\nservername={$line["servername"]}\n";
			
		}}		
		
		return $conf;
		
	}
		
		
	function Build_lighthttp(){
		if(!$this->AsRoot){return;}
		$this->BuilDefault();
		$users=new usersMenus();
		$sock=new sockets();
		$unix=new unix();
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		$LighttpdUseUnixSocket=$sock->GET_INFO('LighttpdUseUnixSocket');
		if(!is_numeric($LighttpdUseUnixSocket)){$LighttpdUseUnixSocket=0;}
		$lighttpdRcbePhpPort=$sock->GET_INFO('lighttpdRcbePhpPort');
		if(!is_numeric($lighttpdRcbePhpPort)){$lighttpdRcbePhpPort=1818;}
		$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
		if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
		if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
	
		$PHP_STANDARD_MODE=true;
		$f[]="#artica-postfix saved by artica lighttpd-roundcube.conf";
		$f[]="server.modules = (";
		$f[]="\t\"mod_alias\",";
		$f[]="\t\"mod_access\",";
		$f[]="\t\"mod_accesslog\",";
		$f[]="\t\"mod_compress\",";
		$f[]="\t\"mod_fastcgi\",";
		$f[]="\t\"mod_status\"";
		$f[]=")";
		$f[]="";
		$f[]="server.document-root        = \"$this->root_path\"";
		$f[]="server.username = \"www-data\"";
		$f[]="server.groupname = \"www-data\"";
		$f[]="server.errorlog             = \"/var/log/lighttpd/roundcube-error.log\"";
		$f[]="index-file.names            = ( \"index.php\")";
		$f[]="";
		$f[]="mimetype.assign             = (";
		$f[]="\t\".pdf\"          =>      \"application/pdf\",";
		$f[]="\t\".sig\"          =>      \"application/pgp-signature\",";
		$f[]="\t\".spl\"          =>      \"application/futuresplash\",";
		$f[]="\t\".class\"        =>      \"application/octet-stream\",";
		$f[]="\t\".ps\"           =>      \"application/postscript\",";
		$f[]="\t\".torrent\"      =>      \"application/x-bittorrent\",";
		$f[]="\t\".dvi\"          =>      \"application/x-dvi\",";
		$f[]="\t\".gz\"           =>      \"application/x-gzip\",";
		$f[]="\t\".pac\"          =>      \"application/x-ns-proxy-autoconfig\",";
		$f[]="\t\".swf\"          =>      \"application/x-shockwave-flash\",";
		$f[]="\t\".tar.gz\"       =>      \"application/x-tgz\",";
		$f[]="\t\".tgz\"          =>      \"application/x-tgz\",";
		$f[]="\t\".tar\"          =>      \"application/x-tar\",";
		$f[]="\t\".zip\"          =>      \"application/zip\",";
		$f[]="\t\".mp3\"          =>      \"audio/mpeg\",";
		$f[]="\t\".m3u\"          =>      \"audio/x-mpegurl\",";
		$f[]="\t\".wma\"          =>      \"audio/x-ms-wma\",";
		$f[]="\t\".wax\"          =>      \"audio/x-ms-wax\",";
		$f[]="\t\".ogg\"          =>      \"application/ogg\",";
		$f[]="\t\".wav\"          =>      \"audio/x-wav\",";
		$f[]="\t\".gif\"          =>      \"image/gif\",";
		$f[]="\t\".jar\"          =>      \"application/x-java-archive\",";
		$f[]="\t\".jpg\"          =>      \"image/jpeg\",";
		$f[]="\t\".jpeg\"         =>      \"image/jpeg\",";
		$f[]="\t\".png\"          =>      \"image/png\",";
		$f[]="\t\".xbm\"          =>      \"image/x-xbitmap\",";
		$f[]="\t\".xpm\"          =>      \"image/x-xpixmap\",";
		$f[]="\t\".xwd\"          =>      \"image/x-xwindowdump\",";
		$f[]="\t\".css\"          =>      \"text/css\",";
		$f[]="\t\".html\"         =>      \"text/html\",";
		$f[]="\t\".htm\"          =>      \"text/html\",";
		$f[]="\t\".js\"           =>      \"text/javascript\",";
		$f[]="\t\".asc\"          =>      \"text/plain\",";
		$f[]="\t\".c\"            =>      \"text/plain\",";
		$f[]="\t\".cpp\"          =>      \"text/plain\",";
		$f[]="\t\".log\"          =>      \"text/plain\",";
		$f[]="\t\".conf\"         =>      \"text/plain\",";
		$f[]="\t\".text\"         =>      \"text/plain\",";
		$f[]="\t\".txt\"          =>      \"text/plain\",";
		$f[]="\t\".dtd\"          =>      \"text/xml\",";
		$f[]="\t\".xml\"          =>      \"text/xml\",";
		$f[]="\t\".mpeg\"         =>      \"video/mpeg\",";
		$f[]="\t\".mpg\"          =>      \"video/mpeg\",";
		$f[]="\t\".mov\"          =>      \"video/quicktime\",";
		$f[]="\t\".qt\"           =>      \"video/quicktime\",";
		$f[]="\t\".avi\"          =>      \"video/x-msvideo\",";
		$f[]="\t\".asf\"          =>      \"video/x-ms-asf\",";
		$f[]="\t\".asx\"          =>      \"video/x-ms-asf\",";
		$f[]="\t\".wmv\"          =>      \"video/x-ms-wmv\",";
		$f[]="\t\".bz2\"          =>      \"application/x-bzip\",";
		$f[]="\t\".tbz\"          =>      \"application/x-bzip-compressed-tar\",";
		$f[]="\t\".tar.bz2\"      =>      \"application/x-bzip-compressed-tar\",";
		$f[]="\t\"\"              =>      \"application/octet-stream\",";
		$f[]=" )";
		$f[]="";
		$f[]="";
		$f[]="accesslog.filename          = \"/var/log/lighttpd/roundcube-access.log\"";
		$f[]="url.access-deny             = ( \"~\", \".inc\" )";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen port $this->https_port\n";}
		$f[]="static-file.exclude-extensions = ( \".php\", \".pl\", \".fcgi\" )";
		$f[]="server.port                 = $this->https_port";
		$f[]="#server.bind                = \"127.0.0.1\"";
		$f[]="#server.error-handler-404   = \"/error-handler.html\"";
		$f[]="#server.error-handler-404   = \"/error-handler.php\"";
		$f[]="server.pid-file             = \"/var/run/lighttpd/lighttpd-roundcube.pid\"";
		$f[]="server.max-fds 		     = 2048";
		$f[]="cgi.fix_pathinfo 			 = 1";
		$f[]="";
	
	
		if(is_file($phpfpm)){
				
			if($EnablePHPFPM==1){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Artica Web service PHP-FPM is enabled\n";}
				$PHP_STANDARD_MODE=false;
				$f[]='fastcgi.server = ( ".php" =>((';
				$f[]="\t\"socket\" => \"/var/run/php-fpm.sock\",";
			}
		}
	
	
	
		if ($PHP_STANDARD_MODE){
			$f[]="fastcgi.server = ( \".php\" =>((";
			$f[]="\t\"bin-path\" => \"$users->phpcgi\",";
			if($LighttpdUseUnixSocket==1){
				$f[]='\t"socket" => "/var/run/lighttpd/phprcbe.socket" + var.PID,';
			}else{
	
				$f[]="\t\"host\" => \"127.0.0.1\",\"port\" => $lighttpdRcbePhpPort,";
			}
		}
	
	
		
		$f[]="\t\t\"min-procs\" => 1,";
		$f[]="\t\t\"max-procs\" => $this->lighttp_max_proc,";
		$f[]="\t\t\"max-load-per-proc\" => $this->lighttp_max_load_per_proc,";
		$f[]="\t\t\"idle-timeout\" => 10,";
		$f[]="\t\t \"bin-environment\" => (";
		$f[]="\t\t\t\"PHP_FCGI_CHILDREN\" => \"$this->PHP_FCGI_CHILDREN\",";
		$f[]="\t\t\t\"PHP_FCGI_MAX_REQUESTS\" => \"$this->PHP_FCGI_MAX_REQUESTS\"";
		$f[]="\t\t),\n";
		$f[]="\t\t\"bin-copy-environment\" => (";
		$f[]="\t\t\t\"PATH\", \"SHELL\", \"USER\"";
		$f[]="\t\t),\n";
		$f[]="\t\t\"broken-scriptfilename\" => \"enable\"";
		$f[]="\t))\n";
		$f[]=")\n";
		if($this->ssl_enabled==1){$ssl="enable";}else{$ssl="disable";}
		$f[]="ssl.engine                 = \"$ssl\"\n";
		$f[]="ssl.pemfile                = \"/opt/artica/ssl/certs/lighttpd.pem\"";
		$f[]="status.status-url          = \"/server-status\"";
		$f[]="status.config-url          = \"/server-config\"";
		@mkdir("/var/run/lighttpd",0755,true);
		return @implode("\n", $f);
	
	
	}	

	
	function ParseMysqlInstall(){
		$users=new usersMenus();
		$path=$users->roundcube_mysql_sources;
		$datas=@file_get_contents($path);
		$tables=array();
		$tbl=explode("\n",$datas);
		while (list ($num, $line) = each ($tbl)){
		if(preg_match('#CREATE TABLE (.+?)\s+#',$line,$re)){
			$re[1]=str_replace('`','',$re[1]);
			$tables[$re[1]]=$re[1];
			}
		}
		
		
		$html="<table style='width:1OO%'>";
		
		while (list ($num, $line) = each ($tables)){
			$mysql=new mysql();
			if(!$this->ClassSQL->TABLE_EXISTS($num,$this->database)){
				$img="mailbox_hd_D32D2D.png";
			}else{$img="mailbox_hd.png";}
			
			$html=$html .  "<tr>
			<td width=1%><img src='img/$img'></td>
			<td width=99%><strong style='font-size:20px'>$line</strong>
			</tr>";
			
		}
		
		return $html."</table>";
	}
	
	function  RebuildMysql(){
		$mysql=new mysql();
		$users=new usersMenus();
		$path=$users->roundcube_mysql_sources;
		$this->ClassSQL->DELETE_DATABASE('roundcubemail');
		$this->ClassSQL->CREATE_DATABASE('roundcubemail');
		
		$this->ParseSQLCommands($path);
		if($this->AsRoot){
			$unix=new unix();
			$php5=$unix->LOCATE_PHP5_BIN();
			$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.roundcube.php");
		}
	}
	
	
	function ParseSQLCommands($file){
		$datas=@file_get_contents($file);
		if(preg_match_all("#CREATE(.+?);#is",$datas,$re)){
			while (list ($num, $line) = each ($re[0])){
				$sql=$line;
				$this->ClassSQL->QUERY_SQL($sql,"roundcubemail");
			}
		}
		
	}
	
	public function plugin_install($basedir,$pluginname){
		if(!is_dir("/usr/share/artica-postfix/bin/install/roundcube/$pluginname")){return;}
		if(!is_dir($basedir)){return;}
		if($this->AsRoot){echo "Starting......: ".date("H:i:s")." Roundcube installing $pluginname plugin\n";}
		@mkdir("$basedir/plugins/$pluginname",null,true);
		shell_exec("/bin/cp -rf /usr/share/artica-postfix/bin/install/roundcube/$pluginname/* $basedir/plugins/$pluginname/");
		
		
	}
	
	public function plugin_jqueryui($basedir){
		$f[]="<?php";
		$f[]="\$rcmail_config['jquery_ui_i18n'] = array('datepicker');";
		$f[]="?>";
		@file_put_contents("$basedir/plugins/jqueryui/config.inc.php",@implode("\n",$f));
	}
	
	public function plugin_password($basedir,$ou=null){
		if(!$this->AsRoot){return;}
		
		$unix=new unix();
		$pear=$unix->find_program("pear");
		if(strlen($pear)<5){return null;}
		if(!$unix->PEAR_INSTALL_CHECK("Net_LDAP2")){
			shell_exec("$pear install Net_LDAP2-2.0.10");
		}
		if(!$unix->PEAR_INSTALL_CHECK("Net_LDAP2")){return false;}
		if(!is_file("$this->root_path/plugins/password/password.php")){return false;}
		$ldap=new clladp();
		if($ou<>null){$dnprefix="ou=$ou,";}
		$conf[]="<?php";
		$conf[]="\$rcmail_config['password_driver'] = 'ldap';";
		$conf[]="\$rcmail_config['password_confirm_current'] = true;";
		$conf[]="\$rcmail_config['password_minimum_length'] = 0;";
		$conf[]="\$rcmail_config['password_require_nonalpha'] = false;";
		$conf[]="\$rcmail_config['password_ldap_host'] = '$ldap->ldap_host';";
		$conf[]="\$rcmail_config['password_ldap_port'] = '$ldap->ldap_port';";
		$conf[]="\$rcmail_config['password_ldap_starttls'] = false;";
		$conf[]="\$rcmail_config['password_ldap_version'] = '3';";
		$conf[]="\$rcmail_config['password_ldap_basedn'] = '$ldap->suffix';";
		$conf[]="\$rcmail_config['password_ldap_method'] = 'admin';";
		$conf[]="\$rcmail_config['password_ldap_adminDN'] = 'cn=$ldap->ldap_admin,$ldap->suffix';";
		$conf[]="\$rcmail_config['password_ldap_adminPW'] = '$ldap->ldap_password';";
		//$conf[]="\$rcmail_config['password_ldap_userDN_mask'] = 'uid=%login,ou=people,dc=exemple,dc=com';";
		$conf[]="\$rcmail_config['password_ldap_searchDN'] = 'cn=$ldap->ldap_admin,$ldap->suffix';";
		$conf[]="\$rcmail_config['password_ldap_searchPW'] = '$ldap->ldap_password';";
		$conf[]="\$rcmail_config['password_ldap_search_base'] = '{$dnprefix}dc=organizations,$ldap->suffix';";
		$conf[]="\$rcmail_config['password_ldap_search_filter'] = '(uid=%login)';";
		$conf[]="\$rcmail_config['password_ldap_encodage'] = 'clear';";
		$conf[]="\$rcmail_config['password_ldap_pwattr'] = 'userPassword';";
		$conf[]="\$rcmail_config['password_ldap_force_replace'] = true;";
		$conf[]="?>";
		if(file_put_contents("$this->root_path/plugins/password/config.inc.php",@implode("\n",$conf))){return true;}
		
	}
	
	
	function  RoundCubeConfig(){
		$sock=new sockets();
		$RoundCubeEnableSieve=$sock->GET_INFO("RoundCubeEnableSieve");
		$RoundCubeEnableCalendar=$sock->GET_INFO("RoundCubeEnableCalendar");
		$users=new usersMenus();
		$sql="SELECT enabled FROM roundcube_globaladdressbook WHERE hostname='MAIN_INSTANCE'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$RoundCubeEnableGlobalAddressBook=$ligne["enabled"];
		$RC_VERSION=$this->RC_VERSION();
		$RC_VERSION_EX=explode(".",$RC_VERSION);
		$MAJOR=intval($RC_VERSION[0]);
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} v{$RC_VERSION} Major $MAJOR [".__LINE__."]\n";}
		
		$plugins='';
			reset($this->roundCubeArray);
			while (list ($num, $line) = each ($this->roundCubeArray)){
					if(preg_match("#plugin\_(.+)#",$num,$re)){
						if($re[1]=="sieverules"){continue;}
						if($line==1){
							$arr[]="'{$re[1]}'";
							//$plugins=$plugins."\$rcmail_config['plugins']['{$re[1]}'] = TRUE;\n";
						}
					}else{
						
					}
				}
				
			reset($this->roundCubeArray);
				
		    if($RoundCubeEnableSieve==1){
		    	if($this->AsRoot){echo "Starting......: ".date("H:i:s")." Roundcube sieverules Enabled\n";}
		    	$arr[]="'sieverules'";
		    	$sock->getFrameWork("cmd.php?roundcube-install-sieverules=yes");
		    }
		    
			if($RoundCubeEnableCalendar==1){
				if($this->AsRoot){echo "Starting......: ".date("H:i:s")." Roundcube calendar Enabled\n";}
		    	$arr[]="'calendar'";
		    	$sock->getFrameWork("cmd.php?roundcube-install-calendar=yes");
		    }	

		    
		    writelogs("RoundCubeEnableGlobalAddressBook=$RoundCubeEnableGlobalAddressBook",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		    if($RoundCubeEnableGlobalAddressBook==1){
		    	if($this->AsRoot){echo "Starting......: ".date("H:i:s")." Roundcube Global Address Book Enabled\n";}
		   		$sock->getFrameWork("cmd.php?roundcube-install-globaladdressbook=yes");
		    	$arr[]="'globaladdressbook'";
		    }
		    
			if($users->roundcube_subscriptions_option){
				$this->roundCubeArray["plugin_subscriptions_option"]=1;
				$arr[]="'subscriptions_option'";
			}
			
			if($MAJOR<1){
				if($this->AsRoot){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking plugins for 0.x versions [".__LINE__."]\n";}
				if(!is_file("$this->root_path/plugins/msglistcols/msglistcols.php")){$this->plugin_install($this->root_path,"msglistcols");}
				if(!is_file("$this->root_path/plugins/sticky_notes/sticky_notes.php")){$this->plugin_install($this->root_path,"sticky_notes");}
				if(!is_file("$this->root_path/plugins/jqueryui/jqueryui.php")){$this->plugin_install($this->root_path,"jqueryui");}
				if(!is_file("$this->root_path/plugins/dkimstatus/dkimstatus.php")){$this->plugin_install($this->root_path,"dkimstatus");}
				}
			}
				
			if($MAJOR<1){	
				
				if(is_file("$this->root_path/plugins/msglistcols/msglistcols.php")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} msglistcols Enabled for 0.x versions  [".__LINE__."]\n";}
					$arr[]="'msglistcols'";
				}
			}
				
			if($MAJOR<1){
				if(is_file("$this->root_path/plugins/dkimstatus/dkimstatus.php")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} dkimstatus Enabled for 0.x versions [".__LINE__."]\n";}
					$arr[]="'dkimstatus'";
				}
			}
			
			if($MAJOR>0){
				if(is_file("$this->root_path/plugins/acl/acl.php")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ACL Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'acl'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/acl/acl.php no such file for 1.x versions [".__LINE__."]\n";}
				}
				if(is_file("$this->root_path/plugins/filesystem_attachments/filesystem_attachments.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} filesystem_attachments Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'filesystem_attachments'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/filesystem_attachments/filesystem_attachments.php no such file for 1.x versions [".__LINE__."]\n";}
				}
				
				if(is_file("$this->root_path/plugins/archive/archive.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} archive Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'filesystem_attachments'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/archive/archive.php no such file for 1.x versions [".__LINE__."]\n";}
				}

				if(is_file("$this->root_path/plugins/zipdownload/zipdownload.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} zipdownload Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'zipdownload'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/zipdownload/zipdownload.php no such file for 1.x versions [".__LINE__."]\n";}
				}	
				
				
				if(is_file("$this->root_path/plugins/subscriptions_option/subscriptions_option.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} subscriptions_option Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'subscriptions_option'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/subscriptions_option/subscriptions_option.php no such file for 1.x versions [".__LINE__."]\n";}
				}
				if(is_file("$this->root_path/plugins/vcard_attachments/vcard_attachments.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} vcard_attachments Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'vcard_attachments'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/vcard_attachments/vcard_attachments.php no such file for 1.x versions [".__LINE__."]\n";}
				}			
				if(is_file("$this->root_path/plugins/markasjunk/markasjunk.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} markasjunk Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'markasjunk'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/markasjunk/markasjunk.php no such file for 1.x versions [".__LINE__."]\n";}
				}				
				if(is_file("$this->root_path/plugins/show_additional_headers/show_additional_headers.php")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} show_additional_headers Enabled for 1.x versions [".__LINE__."]\n";}
					$arr[]="'markasjunk'";
				}else{
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $this->root_path/plugins/show_additional_headers/show_additional_headers.php no such file for 1.x versions [".__LINE__."]\n";}
				}				
				
				
			}
				
				if(is_file("$this->root_path/plugins/fail2ban/fail2ban.php")){
					echo "Starting......: ".date("H:i:s")." Roundcube fail2ban installed but skipped\n";
					//$arr[]="'fail2ban'";
				}				
				
				
					if(is_file("$this->root_path/plugins/jqueryui/jqueryui.php")){
						echo "Starting......: ".date("H:i:s")." Roundcube jqueryui Enabled\n";
						$this->plugin_jqueryui($this->root_path);
						$arr[]="'jqueryui'";
					}
				
					
				if(is_file("$this->root_path/plugins/sticky_notes/sticky_notes.php")){
					echo "Starting......: ".date("H:i:s")." Roundcube sticky_notes Enabled\n";
					$arr[]="'sticky_notes'";
				}					
				
				
				if($this->plugin_password($this->root_path,null)){
					echo "Starting......: ".date("H:i:s")." Roundcube password Enabled\n";
					$arr[]="'password'";
				}else{
					echo "Starting......: ".date("H:i:s")." Roundcube password failed\n";
				}
				
				if(is_dir("$this->root_path/plugins/managesieve")){
					$arr[]="'managesieve'";
				}
				
				
			
				
	
				if(is_array($arr)){$plugins="\$rcmail_config['plugins'] = array(".implode(',',$arr).");";}
				$version_bin=$users->roundcube_intversion;
				$SockPath="/var/run/mysqld/mysqld.sock";
				
				$conf[]="<?php";
				$conf[]="\$rcmail_config = array();";
				$conf[]="\$rcmail_config['debug_level'] = {$this->roundCubeArray["debug_level"]};";
				$conf[]="\$rcmail_config['log_driver'] = 'file';";
				$conf[]="\$rcmail_config['log_date_format'] = 'd-M-Y H:i:s O';";
				$conf[]="\$rcmail_config['syslog_id'] = 'roundcube';";
				$conf[]="\$rcmail_config['syslog_facility'] = LOG_USER;";
				$conf[]="\$rcmail_config['smtp_log'] = true;";
				$conf[]="\$rcmail_config['log_logins'] = false;";
				$conf[]="\$rcmail_config['sql_debug'] = false;";
				$conf[]="\$rcmail_config['imap_debug'] = false;";
				$conf[]="\$rcmail_config['ldap_debug'] = false;";
				$conf[]="\$rcmail_config['smtp_debug'] = false;";
				$conf[]="\$rcmail_config['default_host'] = '{$this->roundCubeArray["default_host"]}';";
				$conf[]="\$rcmail_config['default_port'] = 143;";
				$conf[]="\$rcmail_config['imap_auth_type'] = null;";
				$conf[]="\$rcmail_config['imap_delimiter'] = null;";
				$conf[]="\$rcmail_config['imap_ns_personal'] = null;";
				$conf[]="\$rcmail_config['imap_ns_other']    = null;";
				$conf[]="\$rcmail_config['imap_ns_shared']   = null;";
				$conf[]="\$rcmail_config['imap_force_caps'] = false;";
				$conf[]="\$rcmail_config['imap_force_lsub'] = false;";
				$conf[]="\$rcmail_config['imap_timeout'] = 0;";
				$conf[]="\$rcmail_config['imap_auth_cid'] = null;";
				$conf[]="\$rcmail_config['imap_auth_pw'] = null;";
				$conf[]="\$rcmail_config['smtp_server'] = '';";
				$conf[]="\$rcmail_config['smtp_port'] = 25;";
				$conf[]="\$rcmail_config['smtp_user'] = '';";
				$conf[]="\$rcmail_config['smtp_pass'] = '';";
				$conf[]="\$rcmail_config['smtp_auth_type'] = '';";
				$conf[]="\$rcmail_config['smtp_auth_cid'] = null;";
				$conf[]="\$rcmail_config['smtp_auth_pw'] = null;";
				$conf[]="\$rcmail_config['smtp_helo_host'] = '';";
				$conf[]="\$rcmail_config['smtp_timeout'] = 0;";
				$conf[]="\$rcmail_config['enable_installer'] = false;";
				$conf[]="\$rcmail_config['log_dir'] = 'logs/';";
				$conf[]="\$rcmail_config['temp_dir'] = 'temp/';";
				$conf[]="\$rcmail_config['enable_caching'] = {$this->roundCubeArray["enable_caching"]};";
				$conf[]="\$rcmail_config['message_cache_lifetime'] = '10d';";
				$conf[]="\$rcmail_config['force_https'] = false;";
				$conf[]="\$rcmail_config['use_https'] = false;";
				$conf[]="\$rcmail_config['login_autocomplete'] = 0;";
				$conf[]="\$rcmail_config['login_lc'] = false;";
				$conf[]="\$rcmail_config['auto_create_user'] = {$this->roundCubeArray["auto_create_user"]};";
				$conf[]="\$rcmail_config['skin_include_php'] = false;";
				$conf[]="\$rcmail_config['session_lifetime'] = 10;";
				$conf[]="\$rcmail_config['ip_check'] = false;";
				$conf[]="\$rcmail_config['double_auth'] = false;";
				$conf[]="\$rcmail_config['referer_check'] = false;";
				$conf[]="\$rcmail_config['des_key'] = 'rcmail-!24ByteDESkey*Str';";
				$conf[]="\$rcmail_config['username_domain'] = '';";
				$conf[]="\$rcmail_config['mail_domain'] = '';";
				$conf[]="\$rcmail_config['password_charset'] = 'ISO-8859-1';";
				$conf[]="\$rcmail_config['sendmail_delay'] = 0;";
				$conf[]="\$rcmail_config['max_recipients'] = 0; ";
				$conf[]="\$rcmail_config['max_group_members'] = 0; ";
				$conf[]="\$rcmail_config['useragent'] = 'Roundcube Webmail/'.RCMAIL_VERSION;";
				$conf[]="\$rcmail_config['product_name'] = '{$this->roundCubeArray["product_name"]}';";
				$conf[]="\$rcmail_config['include_host_config'] = false;";
				$conf[]="\$rcmail_config['generic_message_footer'] = '';";
				$conf[]="\$rcmail_config['generic_message_footer_html'] = '';";
				$conf[]="\$rcmail_config['http_received_header'] = false;";
				$conf[]="\$rcmail_config['http_received_header_encrypt'] = false;";
				$conf[]="\$rcmail_config['mail_header_delimiter'] = NULL;";
				$conf[]="\$rcmail_config['line_length'] = 72;";
				$conf[]="\$rcmail_config['send_format_flowed'] = true;";
				$conf[]="\$rcmail_config['session_domain'] = '';";
				$conf[]="\$rcmail_config['dont_override'] = array();";
				$conf[]="\$rcmail_config['identities_level'] = 0;";
				$conf[]="\$rcmail_config['mime_magic'] = '/usr/share/misc/magic';";
				$conf[]="\$rcmail_config['email_dns_check'] = false;";
				if($this->roundCubeArray["plugin_subscriptions_option"]==1){
									$conf[]="\$rcmail_config['use_subscriptions'] = true;";
								}
				$conf[]="\$rcmail_config['plugins'] = array();";
				$conf[]="$plugins";
				
				$conf[]="\$rcmail_config['message_sort_col'] = '';";
				$conf[]="\$rcmail_config['message_sort_order'] = 'DESC';";
				$conf[]="\$rcmail_config['list_cols'] = array('subject', 'status', 'from', 'date', 'size', 'flag', 'attachment');";
				if($version_bin>=40){
					$conf[]="\$rcmail_config['language'] = '{$this->roundCubeArray["locale_string"]}';";					
				}else{
					$conf[]="\$rcmail_config['locale_string'] = '{$this->roundCubeArray["locale_string"]}';";	
				}
				$conf[]="\$rcmail_config['date_short'] = 'D H:i';";
				$conf[]="\$rcmail_config['date_long'] = 'd.m.Y H:i';";
				$conf[]="\$rcmail_config['date_today'] = 'H:i';";
				$conf[]="\$rcmail_config['drafts_mbox'] = 'Drafts';";
				$conf[]="\$rcmail_config['junk_mbox'] = 'Junk';";
				$conf[]="\$rcmail_config['sent_mbox'] = 'Sent';";
				$conf[]="\$rcmail_config['trash_mbox'] = 'Trash';";
				$conf[]="\$rcmail_config['default_imap_folders'] = array('INBOX', 'Drafts', 'Sent', 'Junk', 'Trash');";
				$conf[]="\$rcmail_config['create_default_folders'] = false;";
				$conf[]="\$rcmail_config['protect_default_folders'] = true;";
				$conf[]="\$rcmail_config['quota_zero_as_unlimited'] = false;";
				$conf[]="\$rcmail_config['enable_spellcheck'] = true;";
				$conf[]="\$rcmail_config['spellcheck_engine'] = 'googie';";
				$conf[]="\$rcmail_config['spellcheck_uri'] = '';";
				$conf[]="\$rcmail_config['spellcheck_languages'] = NULL;";
				$conf[]="\$rcmail_config['max_pagesize'] = 200;";
				$conf[]="\$rcmail_config['min_keep_alive'] = 60;";
				$conf[]="\$rcmail_config['address_book_type'] = 'sql';";
				$conf[]="\$rcmail_config['ldap_public'] = array();";
				$conf[]=$this->enable_ldap();
				$conf[]="\$rcmail_config['autocomplete_addressbooks'] = array('sql');";
				$conf[]="\$rcmail_config['autocomplete_min_length'] = 1;";
				$conf[]="\$rcmail_config['default_charset'] = 'ISO-8859-1';";
				if($version_bin>=40){
					$conf[]="\$rcmail_config['skin'] = 'skins/default/';";
				}else{
					$conf[]="\$rcmail_config['skin_path'] = 'skins/default/';\n";
				}	
				$conf[]="\$rcmail_config['pagesize'] = 40;";
				$conf[]="\$rcmail_config['timezone'] = 'auto';";
				$conf[]="\$rcmail_config['dst_active'] = (bool)date('I');";
				$conf[]="\$rcmail_config['prefer_html'] = true;";
				$conf[]="\$rcmail_config['show_images'] = 0;";
				$conf[]="\$rcmail_config['htmleditor'] = 0;";
				$conf[]="\$rcmail_config['prettydate'] = true;";
				$conf[]="\$rcmail_config['draft_autosave'] = 300;";
				$conf[]="\$rcmail_config['preview_pane'] = false;";
				$conf[]="\$rcmail_config['preview_pane_mark_read'] = 0;";
				$conf[]="\$rcmail_config['focus_on_new_message'] = true;";
				$conf[]="\$rcmail_config['logout_purge'] = false;";
				$conf[]="\$rcmail_config['logout_expunge'] = false;";
				$conf[]="\$rcmail_config['inline_images'] = true;";
				$conf[]="\$rcmail_config['mime_param_folding'] = 1;";
				$conf[]="\$rcmail_config['skip_deleted'] = {$this->roundCubeArray["skip_deleted"]};";
				$conf[]="\$rcmail_config['read_when_deleted'] = true;";
				$conf[]="\$rcmail_config['flag_for_deletion'] = {$this->roundCubeArray["flag_for_deletion"]};";
				$conf[]="\$rcmail_config['keep_alive'] = 60;";
				$conf[]="\$rcmail_config['check_all_folders'] = false;";
				$conf[]="\$rcmail_config['display_next'] = false;";
				$conf[]="\$rcmail_config['autoexpand_threads'] = 0;";
				$conf[]="\$rcmail_config['top_posting'] = false;";
				$conf[]="\$rcmail_config['strip_existing_sig'] = true;";
				$conf[]="\$rcmail_config['show_sig'] = 1;";
				$conf[]="\$rcmail_config['sig_above'] = false;";
				$conf[]="\$rcmail_config['force_7bit'] = false;";
				$conf[]="\$rcmail_config['search_mods'] = null;  ";
				$conf[]="\$rcmail_config['delete_always'] = false;";
				$conf[]="\$rcmail_config['mdn_requests'] = 0;";
				$conf[]="\$rcmail_config['mdn_default'] = 0;";
				$conf[]="\$rcmail_config['dsn_default'] = 0;";
				$conf[]="\$rcmail_config['reply_same_folder'] = false;\n";	
				return @implode("\n", $conf);			
		}
		
		
	function managesieve(){
		$local=false;
		if(!is_dir("$this->root_path/plugins/managesieve")){return;}
		if($this->roundCubeArray["default_host"]=="127.0.0.1"){$local=true;}
		if($this->roundCubeArray["default_host"]=="localhost"){$local=true;}
		$managesieve_port=$round->roundCubeArray["sieve_port"];
		
		
		if($this->SieveListenIp=="127.0.0.1"){$local=true;}
		if($local){
			$f=file("/etc/services");
			while (list ($num, $ligne) = each ($f) ){
				if(preg_match("#sieve\s+([0-9]+)\/tcp#", $ligne,$re)){
					$managesieve_port=$re[1];
					break;
				}
			}
		}
		
		$conf[]="<?php";
		$conf[]="";
		$conf[]="// managesieve server port";
		$conf[]="\$rcmail_config['managesieve_port'] = $managesieve_port;";
		$conf[]="";
		$conf[]="// managesieve server address, default is localhost.";
		$conf[]="// Replacement variables supported in host name:";
		$conf[]="// %h - user's IMAP hostname";
		$conf[]="// %n - http hostname (\$_SERVER['SERVER_NAME'])";
		$conf[]="// %d - domain (http hostname without the first part)";
		$conf[]="// For example %n = mail.domain.tld, %d = domain.tld";
		$conf[]="\$rcmail_config['managesieve_host'] = '$this->SieveListenIp';";
		$conf[]="";
		$conf[]="// authentication method. Can be CRAM-MD5, DIGEST-MD5, PLAIN, LOGIN, EXTERNAL";
		$conf[]="// or none. Optional, defaults to best method supported by server.";
		$conf[]="\$rcmail_config['managesieve_auth_type'] = null;";
		$conf[]="";
		$conf[]="// Optional managesieve authentication identifier to be used as authorization proxy.";
		$conf[]="// Authenticate as a different user but act on behalf of the logged in user.";
		$conf[]="// Works with PLAIN and DIGEST-MD5 auth.";
		$conf[]="\$rcmail_config['managesieve_auth_cid'] = null;";
		$conf[]="";
		$conf[]="// Optional managesieve authentication password to be used for imap_auth_cid";
		$conf[]="\$rcmail_config['managesieve_auth_pw'] = null;";
		$conf[]="";
		$conf[]="// use or not TLS for managesieve server connection";
		$conf[]="// it's because I've problems with TLS and dovecot's managesieve plugin";
		$conf[]="// and it's not needed on localhost";
		$conf[]="\$rcmail_config['managesieve_usetls'] = false;";
		$conf[]="";
		$conf[]="// default contents of filters script (eg. default spam filter)";
		$conf[]="\$rcmail_config['managesieve_default'] = '/etc/dovecot/sieve/global';";
		$conf[]="";
		$conf[]="// The name of the script which will be used when there's no user script";
		$conf[]="\$rcmail_config['managesieve_script_name'] = 'managesieve';";
		$conf[]="";
		$conf[]="// Sieve RFC says that we should use UTF-8 endcoding for mailbox names,";
		$conf[]="// but some implementations does not covert UTF-8 to modified UTF-7.";
		$conf[]="// Defaults to UTF7-IMAP";
		$conf[]="\$rcmail_config['managesieve_mbox_encoding'] = 'UTF-8';";
		$conf[]="";
		$conf[]="// I need this because my dovecot (with listescape plugin) uses";
		$conf[]="// ':' delimiter, but creates folders with dot delimiter";
		$conf[]="\$rcmail_config['managesieve_replace_delimiter'] = '';";
		$conf[]="";
		$conf[]="// disabled sieve extensions (body, copy, date, editheader, encoded-character,";
		$conf[]="// envelope, environment, ereject, fileinto, ihave, imap4flags, index,";
		$conf[]="// mailbox, mboxmetadata, regex, reject, relational, servermetadata,";
		$conf[]="// spamtest, spamtestplus, subaddress, vacation, variables, virustest, etc.";
		$conf[]="// Note: not all extensions are implemented";
		$conf[]="\$rcmail_config['managesieve_disabled_extensions'] = array();";
		$conf[]="";
		$conf[]="// Enables debugging of conversation with sieve server. Logs it into <log_dir>/sieve";
		$conf[]="\$rcmail_config['managesieve_debug'] = false;";
		$conf[]="";
		$conf[]="// Enables features described in http://wiki.kolab.org/KEP:14";
		$conf[]="\$rcmail_config['managesieve_kolab_master'] = false;";
		$conf[]="";
		$conf[]="// Script name extension used for scripts including. Dovecot uses '.sieve',";
		$conf[]="// Cyrus uses '.siv'. Doesn't matter if you have managesieve_kolab_master disabled.";
		$conf[]="\$rcmail_config['managesieve_filename_extension'] = '.siv';";
		$conf[]="";
		$conf[]="// List of reserved script names (without extension).";
		$conf[]="// Scripts listed here will be not presented to the user.";
		$conf[]="\$rcmail_config['managesieve_filename_exceptions'] = array();";
		$conf[]="";
		$conf[]="?>";
		@file_put_contents("$this->root_path/plugins/managesieve/config.inc.php", @implode("\n", $conf));
		@chmod("$this->root_path/plugins/managesieve/config.inc.php",0755);		
		
		
	}
	
	
	function enable_ldap(){
		
		if($this->roundCubeArray["ldap_ok"]==1){
			$ldap=new clladp();
			$ous=$ldap->hash_get_ou(true);
			
			while (list ($ou, $line) = each ($ous)){
			
			$conf=$conf."\$rcmail_config['ldap_public']['$ou'] = array(\n";
			$conf=$conf."\t'name'          => '$ou',\n";
			$conf=$conf."\t'hosts'         => array('$ldap->ldap_host'),\n";
			$conf=$conf."\t'port'          => $ldap->ldap_port,\n";
			$conf=$conf."\t'base_dn'       => 'ou=$ou,dc=organizations,$ldap->suffix',\n";
			$conf=$conf."\t'bind_dn'       => 'cn=$ldap->ldap_admin,$ldap->suffix',\n";
			$conf=$conf."\t'bind_pass'     => '$ldap->ldap_password',\n";
			$conf=$conf."\t'ldap_version'  => 3,       // using LDAPv3\n"; 
			$conf=$conf."\t'search_fields' => array('mail', 'cn','uid','givenName','DisplayName'),  // fields to search in\n";
			$conf=$conf."\t'name_field'    => 'cn',    // this field represents the contact's name\n";
			$conf=$conf."\t'email_field'   => 'mail',  // this field represents the contact's e-mail\n";
			$conf=$conf."\t'surname_field' => 'sn',    // this field represents the contact's last name\n";
			$conf=$conf."\t'firstname_field' => 'gn',  // this field represents the contact's first name\n";
			$conf=$conf."\t'scope'         => 'sub',   // search mode: sub|base|list\n";
			$conf=$conf."\t'LDAP_Object_Classes' => array( 'person', 'inetOrgPerson', 'userAccount'),\n";
			$conf=$conf."\t'filter'        => 'givenName=*',      // used for basic listing (if not empty) and will be &'d with search queries. ex: (status=act)\n";
			$conf=$conf."\t'fuzzy_search'  => true);   // server allows wildcard search\n";
			
			
			
			}
		return $conf;	
		}
		
		
		
	}
	
	function RC_VERSION($root){
	
		if(isset($GLOBALS["RC_VERSION"])){$GLOBALS["RC_VERSION"];}
		$f=explode("\n",@file_get_contents("$this->root_path/program/include/iniset.php"));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if(!preg_match("#RCMAIL_VERSION.*?([0-9\.]+)#", $ligne,$re)){continue;}
			
			$GLOBALS["RC_VERSION"]= trim($re[1]);
			return $GLOBALS["RC_VERSION"];
		}
	
	}
	
	
	function db_inc_php($return=false){
		$filepath="/etc/roundcube/debian-db.php";
		$f[]="<?php";
		$f[]="\$rcmail_config = array();";
		$f[]="\$rcmail_config[\"db_dsnw\"] = \"$this->dbidsn\";";
		$f[]="\$rcmail_config[\"db_dsnr\"] = \"\";";
		$f[]="\$rcmail_config[\"db_max_length\"] = 512000;  // 500K";
		$f[]="\$rcmail_config[\"db_persistent\"] = FALSE;";
		$f[]="\$rcmail_config[\"db_table_users\"] = \"users\";";
		$f[]="\$rcmail_config[\"db_table_identities\"] = \"identities\";";
		$f[]="\$rcmail_config[\"db_table_contacts\"] = \"contacts\";";
		$f[]="\$rcmail_config[\"db_table_session\"] = \"session\";";
		$f[]="\$rcmail_config[\"db_table_cache\"] = \"cache\";";
		$f[]="\$rcmail_config[\"db_table_messages\"] = \"messages\";";
		$f[]="\$rcmail_config[\"db_sequence_users\"] = \"user_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_identities\"] = \"identity_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_contacts\"] = \"contact_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_cache\"] = \"cache_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_messages\"] = \"message_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_contacts\"] = \"contact_ids\";";
		$f[]="\$rcmail_config[\"db_sequence_contactgroups\"] = \"contactgroups_ids\";";
		$f[]="?>";
		@mkdir(dirname($filepath),0755,true);
		if(!$return){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: RoundCube Web `$filepath`\n";}
			@file_put_contents($filepath, @implode("\n", $f));
		}
		
		$f=array();
		$filepath="$this->root_path/config/db.inc.php";
		$f[]="<?php";
		$f[]="";
		$f[]="/*";
		$f[]=" +-----------------------------------------------------------------------+";
		$f[]=" | Configuration file for database access                                |";
		$f[]=" |                                                                       |";
		$f[]=" | This file is part of the Roundcube Webmail client                     |";
		$f[]=" | Copyright (C) 2005-2012, The Roundcube Dev Team                       |";
		$f[]=" |                                                                       |";
		$f[]=" | Licensed under the GNU General Public License version 3 or            |";
		$f[]=" | any later version with exceptions for skins & plugins.                |";
		$f[]=" | See the README file for a full license statement.                     |";
		$f[]=" |                                                                       |";
		$f[]=" +-----------------------------------------------------------------------+";
		$f[]="";
		$f[]="*/";
		$f[]="";
		$f[]="\$rcmail_config = array();";
		$f[]="";
		$f[]="// PEAR database DSN for read/write operations";
		$f[]="// format is db_provider://user:password@host/database ";
		$f[]="// For examples see http://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php";
		$f[]="// currently supported db_providers: mysql, mysqli, pgsql, sqlite, mssql or sqlsrv";
		$f[]="";
		$f[]="\$rcmail_config['db_dsnw'] = 'mysql://$this->dbidsn';";
		$f[]="\$rcmail_config['db_dsnr'] = '';";
		$f[]="";
		$f[]="// use persistent db-connections";
		$f[]="// beware this will not \"always\" work as expected";
		$f[]="// see: http://www.php.net/manual/en/features.persistent-connections.php";
		$f[]="\$rcmail_config['db_persistent'] = FALSE;";
		$f[]="";
		$f[]="// you can define specific table names used to store webmail data";
		$f[]="\$rcmail_config['db_table_users'] = 'users';";
		$f[]="\$rcmail_config['db_table_identities'] = 'identities';";
		$f[]="\$rcmail_config['db_table_contacts'] = 'contacts';";
		$f[]="\$rcmail_config['db_table_contactgroups'] = 'contactgroups';";
		$f[]="\$rcmail_config['db_table_contactgroupmembers'] = 'contactgroupmembers';";
		$f[]="\$rcmail_config['db_table_session'] = 'session';";
		$f[]="\$rcmail_config['db_table_cache'] = 'cache';";
		$f[]="\$rcmail_config['db_table_cache_index'] = 'cache_index';";
		$f[]="\$rcmail_config['db_table_cache_thread'] = 'cache_thread';";
		$f[]="\$rcmail_config['db_table_cache_messages'] = 'cache_messages';";
		$f[]="\$rcmail_config['db_table_dictionary'] = 'dictionary';";
		$f[]="\$rcmail_config['db_table_searches'] = 'searches';";
		$f[]="\$rcmail_config['db_table_system'] = 'system';";
		$f[]="";
		$f[]="// you can define specific sequence names used in PostgreSQL";
		$f[]="\$rcmail_config['db_sequence_users'] = 'user_ids';";
		$f[]="\$rcmail_config['db_sequence_identities'] = 'identity_ids';";
		$f[]="\$rcmail_config['db_sequence_contacts'] = 'contact_ids';";
		$f[]="\$rcmail_config['db_sequence_contactgroups'] = 'contactgroups_ids';";
		$f[]="\$rcmail_config['db_sequence_searches'] = 'search_ids';";
		$f[]="";
		$f[]="";
		$f[]="// end db config file";
		$f[]="?>";
		
		if($return){return @implode("\n", $f);}
		@mkdir(dirname($filepath),0755,true);
		@file_put_contents($filepath, @implode("\n", $f));
		
		$RC_VERSION=$this->RC_VERSION();
		$RC_VERSION_EX=explode(".",$RC_VERSION);
		$MAJOR=intval($RC_VERSION[0]);
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} v{$RC_VERSION} Major $MAJOR [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} `$filepath` [".__LINE__."]\n";}
		
		if(is_file("$this->root_path/plugins/sieverules/sieverules.php")){
			$users=new usersMenus();
			$sieverules_port=4190;
			if(is_numeric($users->SIEVE_PORT)){if($users->SIEVE_PORT>0){$sieverules_port=$users->SIEVE_PORT;}}
			echo "Starting......: ".date("H:i:s")." {$GLOBALS["SERVICE_NAME"]} (php) sieverules_port ($sieverules_port) [".__LINE__."]\n";
			$conf[]="\$rcmail_config['plugins'][] = 'sieverules';";
			$sieve[]="<?php";
			$sieve[]="\$rcmail_config[\"sieverules_host\"] = \"127.0.0.1\";";
			$sieve[]="\$rcmail_config[\"sieverules_port\"] = $sieverules_port;";
			$sieve[]="\$rcmail_config[\"sieverules_usetls\"] = FALSE;";
			$sieve[]="\$rcmail_config[\"sieverules_folder_delimiter\"] = null;";
			$sieve[]="\$rcmail_config[\"sieverules_folder_encoding\"] = null;";
			$sieve[]="\$rcmail_config[\"sieverules_include_imap_root\"] = null;";
			$sieve[]="\$rcmail_config[\"sieverules_ruleset_name\"] = \"roundcube\";";
			$sieve[]="\$rcmail_config[\"sieverules_multiple_actions\"] = TRUE;";
			$sieve[]="\$rcmail_config[\"sieverules_allowed_actions\"] = array(\"fileinto\" => TRUE,\"vacation\" => TRUE,\"reject\" => TRUE,\"redirect\" => TRUE,\"keep\" => TRUE,\"discard\" => TRUE,\"imapflags\" => TRUE,\"notify\" => TRUE,\"stop\" => TRUE);";
			$sieve[]="\$rcmail_config[\"sieverules_other_headers\"] = array(\"Reply-To\", \"List-Id\", \"MailingList\", \"Mailing-List\",\"X-ML-Name\", \"X-List\", \"X-List-Name\", \"X-Mailing-List\",\"Resent-From\",";
			$sieve[]="	\"Resent-To\", \"X-Mailer\", \"X-MailingList\",\"X-Spam-Status\", \"X-Priority\", \"Importance\", \"X-MSMail-Priority\",\"Precedence\", \"Return-Path\", \"Received\", \"Auto-Submitted\",\"X-Spam-Flag\", \"X-Spam-Tests\");";
			$sieve[]="\$rcmail_config[\"sieverules_predefined_rules\"] = array();";
			$sieve[]="\$rcmail_config[\"sieverules_adveditor\"] = 0;";
			$sieve[]="\$rcmail_config[\"sieverules_multiplerules\"] = FALSE;";
			$sieve[]="\$rcmail_config[\"sieverules_default_file\"] = \"/etc/dovecot/sieve/default\";";
			$sieve[]="\$rcmail_config[\"sieverules_auto_load_default\"] = FALSE;";
			$sieve[]="\$rcmail_config[\"sieverules_example_file\"] = \"/etc/dovecot/sieve/example\";";
			$sieve[]="\$rcmail_config[\"sieverules_force_vacto\"] = TRUE;";
			$sieve[]="\$rcmail_config[\"sieverules_use_elsif\"] = TRUE;";
			$sieve[]="?>";
			@file_put_contents("$this->root_path/plugins/sieverules/config.inc.php",@implode("\n",$sieve));
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: RoundCube Web `plugins/sieverules/config.inc.php`\n";}
			@chmod("$this->root_path/plugins/sieverules/config.inc.php",0755);
		}		
		
		
		
	}
	
		
		
	}

?>