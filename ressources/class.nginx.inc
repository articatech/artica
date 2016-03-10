<?php
	include_once(dirname(__FILE__).'/class.squid.inc');
	include_once(dirname(__FILE__).'/class.nginx-sources.inc');
	include_once(dirname(__FILE__).'/class.nginx-wordpress.inc');
	include_once(dirname(__FILE__).'/class.nginx.certificate.inc');
	include_once(dirname(__FILE__).'/class.nginx.exchange.inc');
	include_once(dirname(__FILE__).'/class.nginx.tools.inc');
	include_once(dirname(__FILE__).'/class.tcpip.inc');
	
class nginx{
	private $servername=null;
	private $ssl=0;
	private $squidR;
	private $sslcertificate=null;
	private $proxy_to_host=null;
	private $poolid=0;
	private $cache_peer_id=0;
	private $proxy_to_port=null;
	private $set_proxy_destination_logs=array();
	private $proxy_suffix=null;
	private $proxy_scheme=null;
	private $HostPortLNK=null;
	private $forceddomain=null;
	private $proxy_cache_valid=4320;
	private $NginxProxyStorePath=null;
	private $RedirectQueries=null;
	private $host_port=0;
	private $ssl_client_certificate=0;
	private $storeid=0;
	private $host_ipaddr=null;
	private $proxy_disabled=0;
	private $DocumentRoot=null;
	private $IndexFile=null;
	private $IsAFreeWeb=false;
	private $APACHE_USER;
	private $APACHE_SRC_GROUP;
	private $CompileParams=array();
	private $ServersAliases=array();
	private $BuildFramework=false;
	private $servername_pattern=null;
	private $SargDir=null;
	private $OWA=false;
	private $EnableHSTS=0;
	private $RestrictIFrames=0;
	private $XSSBrowser=0;
	private $EnableCSP=0;
	private $ClassFreeWeb=null;
	private $default_server=0;
	private $ssl_protocols=null;
	private $ssl_ciphers=null;
	private $LimitCnx=0;
	private $LimitReqs=0;
	private $DenyConf=0;
	private $x_forwarded_for=1;
	private $x_forwarded_for_string=null;
	private $proxy_pass_request_headers=null;
	private $ngx_headers_more_module=false;
	private $zOrder=0;
	public $NoErrorPages=false;
	public $proxy_read_timeout=300;
	public $proxy_send_timeout=300;
	public $proxy_buffering=1;
	public $proxy_cache_min_uses=1;
	public $proxy_buffer_size=8;
	public $proxy_max_temp_file_size=1024;
	public $proxy_temp_file_write_size="64k";
	public $proxy_buffers="16 32k";
	public $file_settings_path;
	public $BackupBefore=false;
	public $owa=0;
	private $EnableArticaDynamicErrors=0;
	private $mixed_ssl=0;
	public $WORDPRESS=false;
	
	function nginx($servername=null){
		$this->unix=new unix();
		if(preg_match("#([0-9\.]+):([0-9]+)#", $servername,$re)){$servername=$re[1];$this->host_port=$re[2];}
		
		if(is_numeric($servername)){
			$this->host_port=$servername;
			if($GLOBALS["VERBOSE"]){echo "[DEBUG]: '$servername'-> host_port:'$servername'\n";}
			
		}else{
			if($servername<>null){$this->servername=$servername;}
			
		}
		$this->squidR=new squidbee();
		$sock=new sockets();
		
		$this->ClassFreeWeb=new freeweb($this->servername);
		$this->IndexFile=$this->ClassFreeWeb->DirectoryIndex();
		$this->NginxProxyStorePath=$sock->GET_INFO("NginxProxyStorePath");
		$this->EnableArticaDynamicErrors=intval($sock->GET_INFO("EnableArticaDynamicErrors"));
		if($this->NginxProxyStorePath==null){$this->NginxProxyStorePath="/home/nginx";}
		$this->APACHE_USER=$this->unix->APACHE_SRC_ACCOUNT();
		$this->APACHE_SRC_GROUP=$this->unix->APACHE_SRC_GROUP();
		if($this->APACHE_USER==null){$this->APACHE_USER="www-data";}
		if($this->APACHE_SRC_GROUP==null){$this->APACHE_SRC_GROUP="www-data";}
		if(class_exists("unix")){$this->LoadParams();}
		$this->LoadParams();
	}
	
	
	public function set_ssl($value=null){
		if(!is_numeric($value)){$value=1;}
		$this->ssl=$value;
	}
	
	public function set_mixed_ssl($value=null){
		if(!is_numeric($value)){$value=1;}
		$this->mixed_ssl=$value;
	}
	
	public function set_ssl_certificate($sslcertificate=null){
		if($sslcertificate==null){
			if($this->sslcertificate<>null){return;}
		}
		$this->sslcertificate=$sslcertificate;
	}
	

	public function Delete_website($servername,$noaction=false){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM reverse_www WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM reverse_privs WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM nginx_aliases WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM nginx_replace_folder WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
		
		if(!$noaction){
			$sock=new sockets();
			$sock->getFrameWork("nginx.php?clean-websites=yes");	
		}	
		
	}
	
	private function LoadParams(){
		$key="$this->servername";
		if(isset($GLOBALS[__CLASS__][__FUNCTION__][$key])){return;}
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Loading\n";}
		$unix=new unix();
		$this->CompileParams=$unix->NGINX_COMPILE_PARAMS();
		
		if(isset($this->CompileParams["ngx_headers_more"])){$this->ngx_headers_more_module=true;}
		
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
		$this->build_file_path();
		if($this->servername<>null){
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
			$this->proxy_read_timeout=$ligne["proxy_read_timeout"];
			$this->proxy_send_timeout=$ligne["proxy_send_timeout"];
			$this->proxy_cache_valid=$ligne["proxy_cache_valid"];
			$this->proxy_buffering=$ligne["proxy_buffering"];
			$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
			$this->proxy_buffer_size=$ligne["proxy_buffer_size"];
			$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
			$this->proxy_buffers=$ligne["proxy_buffers"];
			$this->default_server=$ligne["default_server"];
			$this->EnableHSTS=$ligne["EnableHSTS"];
			$this->RestrictIFrames=$ligne["RestrictIFrames"];
			$this->XSSBrowser=$ligne["XSSBrowser"];
			$this->EnableCSP=$ligne["EnableCSP"];
			$this->ssl_protocols=$ligne["ssl_protocols"];
			$this->ssl_ciphers=$ligne["ssl_ciphers"];
			$this->LimitCnx=$ligne["LimitCnx"];
			$this->LimitReqs=$ligne["LimitReqs"];
			$this->proxy_pass_request_headers=$ligne["proxy_pass_request_headers"];
			$this->zOrder=$ligne["zOrder"];
			$this->DenyConf=$ligne["DenyConf"];
			$this->ssl_client_certificate=intval($ligne["ssl_client_certificate"]);
			$this->servername_pattern=$ligne["servername_pattern"];
			if($ligne["certificate"]<>null){$this->set_ssl_certificate($ligne["certificate"]);}
			$this->proxy_max_temp_file_size=intval($ligne["proxy_max_temp_file_size"]);
			$this->proxy_temp_file_write_size=intval($ligne["proxy_temp_file_write_size"]);
			$this->owa=$ligne["owa"];
			
			
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [OPT] proxy_buffering...........: \"{$ligne["proxy_buffering"]}\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [OPT] x_forwarded_for...........: \"{$ligne["x_forwarded_for"]}\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [OPT] x_forwarded_for_string....: \"{$ligne["x_forwarded_for_string"]}\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [OPT] proxy_pass_request_headers: \"{$ligne["proxy_pass_request_headers"]}\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [MOD] ngx_headers_more_module...: \"$this->ngx_headers_more_module\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [MOD] Order.....................: \"$this->zOrder\"\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $key [MOD] DenyConf..................: \"$this->DenyConf\"\n";}
			
			
			
			$this->x_forwarded_for=$ligne["x_forwarded_for"];
			$this->x_forwarded_for_string=trim($ligne["x_forwarded_for_string"]);
			if($this->ssl_protocols==null){$this->ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";}
			if($this->ssl_ciphers==null){$this->ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}
			if(!is_numeric($this->proxy_cache_valid)){$this->proxy_cache_valid=4320;}
			if(!is_numeric($this->x_forwarded_for)){$this->x_forwarded_for=1;}
			if(!is_numeric($this->proxy_pass_request_headers)){$this->proxy_pass_request_headers=1;}
			if($this->ssl_protocols==null){$this->ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";}
			if($this->ssl_ciphers==null){$this->ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}
				
			if(intval($ligne["cache_peer_id"])>0){
				$this->cache_peer_id=$ligne["cache_peer_id"];
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT certificate FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
				if(trim($ligne["certificate"]<>null)){
					$this->set_ssl_certificate($ligne["certificate"]);
				}
			}
			
			
				
			
			
		$GLOBALS[__CLASS__][__FUNCTION__][$key]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." default_server = {$ligne["default_server"]}\n";}
			
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, no servername aborting LoadParams()\n";}
		}
		
		if($this->IsMiscModule()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername MISC module OK\n";}
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername MISC module Failed\n";}
		}
		
	}
	
	public function set_proxy_port($port=null){
		if(!is_numeric($port)){return;}
		if($port==0){return;}
		if($port<0){return;}
		$this->proxy_to_port=$port;
	}	
	
	public function set_RedirectQueries($url=null){
		$this->RedirectQueries=$url;
	}
	
	public function set_forceddomain($domainname=null){
		$this->forceddomain=$domainname;
	}
	public function set_storeid($id=null){
		if(!is_numeric($id)){return;}
		if($id==0){return;}
		$this->storeid=$id;
	}

	public function set_proxy_disabled(){
		$this->proxy_disabled=1;
	}
	
	public function set_DocumentRoot($directory){
		$this->DocumentRoot=$directory;
	}	
	
	public function set_index_file($indexfile){
		$this->IndexFile=$indexfile;
	}
	
	public function SargDir(){
		$sock=new sockets();
		$SargOutputDir=$sock->GET_INFO("SargOutputDir");
		if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
		$this->SargDir=$SargOutputDir;
	}
	
	public function set_freeweb(){
		$this->IsAFreeWeb=true;
	}
	
	public function set_framework(){
		$this->BuildFramework=true;
	}	
	
	public function set_cache_peer_id($ID=0){
		if(!is_numeric($ID)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : False\n";}
			return;}
		if($ID>0){
			$this->cache_peer_id=$ID;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : True\n";}
			return;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : False\n";}
	}
	
	public function set_listen_port($port=0){
		if(!is_numeric($port)){return;}
		if($port>0){$this->host_port=$port;}
	}
	
	public function set_poolid($poolid=0){
		if(!is_numeric($poolid)){return;}
		if($poolid>0){$this->poolid=$poolid;}
	}
	
	public function set_servers_aliases($array){
		if(!is_array($array)){return;}
		if(count($array)==0){return;}
		while (list ($index, $value) = each ($array) ){
			if(!is_numeric($index)){$newarray[$index]=true;continue;}
			if(!preg_match("#.*?\..*?$#", $value)){continue;}
			$newarray[$value]=true;
			
		}
		while (list ($hostname, $value) = each ($newarray) ){
			$this->ServersAliases[]=$hostname;
		}
		
	}
	
	public function set_listen_ip($ipaddr){
		$net =new IP();
		if(!$net->isValid($ipaddr)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,$ipaddr not a valid ip address\n";}
			return;
		}
		
		$this->host_ipaddr=$ipaddr;
		
	}
	
	public function set_owa($value){
		if($value==1){
			$this->OWA=true;
			$this->set_ssl();
		}
		
	}
	
	private function isFreeWebSSL(){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT useSSL FROM freeweb WHERE servername='$this->servername'","artica_backup"));
		if($ligne["useSSL"]==1){return true;}
		return false;
	}
	
	public function FillHostnameAsIP(){
		if(isset($GLOBALS["FillHostnameAsIP"])){
			$this->servername=$GLOBALS["FillHostnameAsIP"];
			return $GLOBALS["FillHostnameAsIP"];}
		$ifconfig_all_ips=$this->unix->ifconfig_all_ips();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ".count($ifconfig_all_ips)." network addresses\n";}
		while (list ($a, $b) = each ($ifconfig_all_ips) ){$ips[]=$a;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, host:{$ips[0]}\n";}
		$GLOBALS["FillHostnameAsIP"]=$ips[0];
		$this->servername=$ips[0];		
		return $GLOBALS["FillHostnameAsIP"];
	}
	
	public function IsSubstitutions(){
		if(isset($GLOBALS["IsSubstitutions"])){return $GLOBALS["IsSubstitutions"];}
		$GLOBALS["IsSubstitutions"]=false;
		$ARRAY=$this->CompileParams;
		if(count($ARRAY)<2){
			$unix=new unix();
			$this->CompileParams=$unix->NGINX_COMPILE_PARAMS();
			$ARRAY=$this->CompileParams;
		}
		
		
		if(isset($ARRAY["MODULES"]["ngx_http_substitutions_filter_module-1.0"])){
			$GLOBALS["IsSubstitutions"]=true;
			return $GLOBALS["IsSubstitutions"];
		}
		
		
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			
			if(preg_match("#http_substitutions_filter#", $a)){
				$GLOBALS["IsSubstitutions"]=true;
				return $GLOBALS["IsSubstitutions"];
			}
		
		}
		
	}
	
	private function IsLdapModule(){
		if(isset($GLOBALS["IsLdapModule"])){return $GLOBALS["IsLdapModule"];}
		$GLOBALS["IsLdapModule"]=false;
		$ARRAY=$this->CompileParams;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#http_auth_ldap_module#", $a)){
				$GLOBALS["IsLdapModule"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsLdapModule"];
	}
	private function IsMiscModule(){
		if(isset($GLOBALS["IsMiscModule"])){return $GLOBALS["IsMiscModule"];}
		$ARRAY=$this->CompileParams;
		$GLOBALS["IsMiscModule"]=false;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#misc-nginx-module#", $a)){
				$GLOBALS["IsMiscModule"]=true;
				break;
			}
		
		}
		return $GLOBALS["IsMiscModule"];
	}
	
	
	private function IsAutheRequestsModule(){
		if(isset($GLOBALS["IsAutheRequestsModule"])){return $GLOBALS["IsAutheRequestsModule"];}
		$GLOBALS["IsAutheRequestsModule"]=false;
		$ARRAY=$this->CompileParams;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#auth-request-nginx-module#", $a)){
				$GLOBALS["IsAutheRequestsModule"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsAutheRequestsModule"];
	}	
	
	public function webdav_containers(){
		return null;
		if(isset($GLOBALS["webdav_containers"])){return $GLOBALS["webdav_containers"];}
		$unix=new unix();
		$NGINX_COMPILE_PARAMS=$unix->NGINX_COMPILE_PARAMS();
		
		if(!isset($NGINX_COMPILE_PARAMS["ARGS"]["WITH-HTTP_DAV_MODULE"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav not implemented.\n";}
			$GLOBALS["webdav_containers"]="# WebDav not implemented\n";return null;
			return;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav implemented.\n";}
		$freeweb=new freeweb();
		$ls=$unix->find_program("ls");
		$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0 AND webdav=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ".mysql_num_rows($results)." WebDav items...\n";}
		$apacheusername=$unix->APACHE_SRC_ACCOUNT();
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$ID=$ligne["container_id"];
				$MountedPath="/media/artica_containers/membersdisks/disk$ID";
				$RootDir="/media/artica_containers/membersdisks";
				shell_exec("$ls $MountedPath/*");
				if(!is_dir("$MountedPath/WebShared")){@mkdir("$MountedPath/WebShared",0755,true);}
				@chown("$MountedPath/WebShared", $apacheusername);
				@mkdir("/etc/htpass",0755,true);
				echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav `$MountedPath`\n";
				$f[]="\tlocation ~ /disk$ID/(.*?)$ {";
				$f[]="\t\tautoindex on;";
				$f[]="\t\talias\t$MountedPath/WebShared/$1;";
				//$f[]="\t\troot $MountedPath/WebShared$1;";
				$f[]="\t\tdav_methods PUT DELETE MKCOL COPY MOVE;";
				$f[]="\t\tcreate_full_put_path  on;";
				$f[]="\t\tclient_body_temp_path           $MountedPath/WebShared/.tmp;";
				$f[]="\t\tdav_access                      user:rw  group:r  all:r;";
				$f[]="\t\tauth_basic \"{$ligne["container_name"]}\";";
				$f[]="\t\tauth_basic_user_file /etc/htpass/disk$ID;";
				$f[]="\t}";
			}
		
			$GLOBALS["webdav_containers"]=@implode("\n",$f);
			return $GLOBALS["webdav_containers"];
	}
	
	
	private function build_file_path(){
		if($this->servername==null){$this->servername=$this->FillHostnameAsIP();}
		$suffix=$this->servername;
		$suffix=str_replace("unix:", "unix-", $suffix);
		$suffix=str_replace("/", "", $suffix);
		
		if($this->IsAFreeWeb){
			if($this->ssl==1){
				$this->file_settings_path="/etc/nginx/local-sites/freewebs-ssl-$suffix.$this->host_port.conf";
			}else{
				$this->file_settings_path="/etc/nginx/local-sites/freewebs-$suffix.$this->host_port.conf";
			}
			return;
		}
		if(!is_numeric($this->zOrder)){$this->zOrder=0;}
		if(strlen($this->zOrder)==1){$this->zOrder="0{$this->zOrder}";}
		
		
		
		
		if($this->proxy_disabled==1){
			
			if(is_file("/etc/nginx/sites-enabled/$this->zOrder-freewebs-$suffix.$this->host_port.conf")){
				@unlink("/etc/nginx/sites-enabled/$this->zOrder-freewebs-$suffix.$this->host_port.conf");
			}
			if(is_file("/etc/nginx/sites-enabled/$this->zOrder-freewebs-ssl-$suffix.$this->host_port.conf")){
				@unlink("/etc/nginx/sites-enabled/$this->zOrder-freewebs-ssl-$suffix.$this->host_port.conf");
			}
			
			if($this->ssl==1){
				$suffix="freewebs-ssl-$suffix.$this->host_port.conf";
				$this->file_settings_path="/etc/nginx/local-sites/freewebs-ssl-$suffix.$this->host_port.conf";
			}else{
				$suffix="freewebs-$suffix.$this->host_port.conf";
				$this->file_settings_path="/etc/nginx/local-sites/freewebs-$suffix.$this->host_port.conf";
			}
			return;
			
		}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, No a FreeWebs [".__LINE__."]\n";}
		if($this->ssl==1){
			$this->file_settings_path="/etc/nginx/sites-enabled/$this->zOrder-freewebs-ssl-$suffix.$this->host_port.conf";
		}else{
			$this->file_settings_path="/etc/nginx/sites-enabled/$this->zOrder-freewebs-$suffix.$this->host_port.conf";
		}
		
		
	}
	
	
	private function FreeWebIsSSL(){
		$free=new freeweb($this->servername);
		if($free->useSSL==1){
			$this->set_ssl_certificate($free->sslcertificate);
			return true;
		}
	}
	
	private function isOWA(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT owa FROM reverse_www WHERE servername='$this->servername'","artica_backup"));
		if($ligne["owa"]==1){return true;}
		return false;
		
	}

	
	public function build_proxy(){
		
		if($this->isOWA()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Microsoft Exchange server configuration\n";}
			$nginx_exchange=new nginx_exchange($this->servername);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, build MS Exchange configuration...\n";}
			$nginx_exchange->buildConfig();
			return true;
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Microsoft Exchange server FALSE\n";}
		}
		
		
		@chmod("/etc/nginx/sites-enabled",0755);
		$this->LoadParams();
		if($this->sslcertificate<>null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, using certificate $this->sslcertificate, turn to ssl\n";}
			$this->ssl=1;
		}
		
		$unix=new unix();
		if($this->servername==null){$this->servername=$this->FillHostnameAsIP();}
		
		if(isset($GLOBALS["NGINX_SERVERS_NAME"][$this->servername])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername already set\n";}
			return true;
		}
		
		$suffix=$this->servername;
		$suffix=str_replace("unix:", "unix-", $suffix);
		$suffix=str_replace("/", "", $suffix);
		
		
		if($this->DenyConf==0){
			if(!$GLOBALS["REPLIC_CONF"]){
				$data=$this->http_proxy();
				if($GLOBALS["VERBOSE"]){echo "\n$data\n";}
			}else{
				$q=new mysql_squid_builder();
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ConfBuilded FROM reverse_www WHERE servername='$this->servername'"));
				$data=$ligne["ConfBuilded"];
			}
		}
		
		if($this->DenyConf==1){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ConfBuilded FROM reverse_www WHERE servername='$this->servername'"));
			$data=$ligne["ConfBuilded"];
		}
		
		@mkdir("/etc/nginx/sites-enabled-backuped",0755,true);
		$this->build_file_path();
		
		
		
		
		if($this->BackupBefore){
			if(is_file($this->file_settings_path)){
				$file=basename($this->file_settings_path);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup old file before\n";}
				@copy("/etc/nginx/nginx.conf","/etc/nginx/nginx.bak");
				@copy($this->file_settings_path, "/etc/nginx/sites-enabled-backuped/$file");
			}
		}

		if(trim($this->file_settings_path)==null){$this->build_file_path();}
		
		if(preg_match("#^[0-9\-]+(.+?)$#", basename($this->file_settings_path),$re)){
			$pattern=$re[1];
			$pattern=str_replace(".", "\.", $pattern);
			
		}
		
		foreach (glob(dirname($this->file_settings_path)."/*") as $filename) {
			if(preg_match("#$pattern#", basename($filename))){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, remove old file $filename\n";}
				@unlink($filename);
			}
			
		}
		
		
		@file_put_contents($this->file_settings_path,$data);
		
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www", "ConfBuilded")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `ConfBuilded` TEXT");if(!$q->ok){echo $q->mysql_error_html();}}
		$q->QUERY_SQL("UPDATE reverse_www SET `ConfBuilded`='".mysql_escape_string2($data)."' WHERE `servername`='$this->servername'");
		
		
		$size=filesize($this->file_settings_path);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,\"$this->file_settings_path\" done.($size bytes)\n";}
		
		if($this->BackupBefore){
			if(!$this->TestTheWholeConfig()){
				
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, removing this website..\n";}
				$unix=new unix();
				$php5=$unix->LOCATE_PHP5_BIN();
				system("$php5 /usr/share/artica-postfix/exec.nginx.single.php --remove \"$this->servername\" --no-reload --output --no-buildmain");
				return false;
			}
		}
		
		return true;
		
	}
	
	public function TestTheWholeConfig(){
		$unix=new unix();
		$nginxbin=$unix->find_program("nginx");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, testing configuration with $nginxbin...\n";}
		$unix=new unix();
		$GLOBALS["NGINX_FATAL_ERROR"]=null;
		exec("$nginxbin -c /etc/nginx/nginx.conf -t 2>&1",$testsR);
		$tests=false;
		while (list ($index, $line) = each ($testsR)){
			if(preg_match("#\[warn\]\s+(.+)#", $line,$re)){$line=$re[1];}
			if(preg_match("#nginx:\s+(.+)#", $line,$re)){$line=$re[1];}
			
			if(preg_match("#invalid URL prefix in\s+(.*?):([0-9]+)#",$line,$re)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
				$filename=$re[1];
				$filenameBase=basename($filename);
				$lineError=$re[2];
				$GLOBALS["NGINX_FATAL_ERROR"]=$lineError;
				$GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, copy $filenameBase to /root\n";}
				@unlink("/root/$filenameBase");
				@copy($filename,"/root/$filenameBase");
				continue;
			}
			
			
			if(preg_match("#invalid number of arguments in \"(.+?)\" directive in (.+?):([0-9]+)#", $line,$re)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
				$arguments=$re[1];
				$filename=$re[2];
				$filenameBase=basename($filename);
				$lineError=$re[3];
				$GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: !!!! ERROR $arguments LINE $lineError !!!!\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, copy $filenameBase to /root\n";}
				@unlink("/root/$filenameBase");
				@copy($filename,"/root/$filenameBase");
				continue;
			}

			if(preg_match('#open.*?\"(.+)\.log"\s+failed.*?No such file or directory#', $line,$re)){
				$filename=$re[1].".log";
				$dirname=dirname($filename);
				@mkdir($dirname,0755,true);
				$APACHE=$unix->APACHE_SRC_ACCOUNT();
				$APACHEG=$unix->APACHE_SRC_GROUP();
				@chown($dirname,$APACHE);
				@chgrp($dirname, $APACHEG);
				return $this->TestTheWholeConfig();
			}
			
			if(preg_match("#SSL: error:#", $line)){
				$GLOBALS["NGINX_FATAL_ERROR"]="SSL Certificate ERROR!";
				$GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
				if($GLOBALS["OUTPUT"]){echo "***************************************\n";}
				if($GLOBALS["OUTPUT"]){echo "$line\n";}
				if($GLOBALS["OUTPUT"]){echo "***************************************\n";}
				return false;
			}
			
			
			
			if(preg_match("#a duplicate default server for (.+?)\s+in\s+(.+?):#", $line,$re)){
				$filename=$re[2];
				$filenameBase=basename($filename);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, duplicate default server {$re[1]} remove $filenameBase\n";}
				@unlink($filename);
				return $this->TestTheWholeConfig();
			}
			
			if(preg_match("#invalid URL prefix in\s+(.+?):#", $line,$re)){
				$filename=$re[1];
				$filenameBase=basename($filename);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, invalid URL prefix remove $filenameBase\n";}
				@unlink($filename);
				return $this->TestTheWholeConfig();
			}
			

		
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
			if(preg_match("#configuration file.*?test is successful#", $line)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, testing configuration Success...\n";}
				$tests=true;
				break;
			}
		
		}
		
		if(!$tests){$GLOBALS["NGINX_FATAL_ERROR"]="Error in config file";}
		
		return $tests;
	}
	
	private function keys_zone_peer($cache_peer_id){
		if(!is_numeric($cache_peer_id)){return null;}
		if($cache_peer_id<1){return null;}
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cacheid FROM reverse_sources WHERE ID='$cache_peer_id'"));
		$cacheid=intval($ligne["cacheid"]);
		if($cacheid==0){return null;}
		return $this->keys_zone($cacheid);
	}
	
	private function keys_zone($storeid=null){
		if(!is_numeric($storeid)){return null;}
		if($storeid==0){return null;}
		
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT keys_zone FROM nginx_caches WHERE ID='$storeid'"));
		if($ligne["keys_zone"]<>null){
			$f[]="\t\tadd_header X-Cache-Status \$upstream_cache_status;";
			$f[]="\t\tproxy_cache {$ligne["keys_zone"]};";
			return @implode("\n", $f);
		}
	}
	
	private function proxy_buffers_check($buffers){
		
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Buffers   : $this->servername:$this->host_port: ";
		if(!preg_match("#([0-9\.]+)\s+([0-9\.]+)#", $buffers,$re)){
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: 128 16k\n";}
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size...: 64k\n";}
			
			if(intval($this->proxy_temp_file_write_size)==0){$this->proxy_temp_file_write_size=64;}
			if($this->proxy_temp_file_write_size<$this->proxy_buffer_size){$this->proxy_temp_file_write_size=$this->proxy_buffer_size;}
				
			
			
			
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_temp_file_write_size: {$this->proxy_temp_file_write_size}k\n";}
			$f[]="\t\tproxy_buffers      128 16k;";
			$f[]="\t\tproxy_busy_buffers_size 64k;";
			$f[]="\t\tproxy_temp_file_write_size {$this->proxy_temp_file_write_size}k;";
			return @implode("\n", $f);
		}
		$f[]="\t\tproxy_buffers      {$re[1]} {$re[2]}k;";
		$buffers=($re[1]-1)*$re[2];
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size {$buffers}k;\n";}
		$f[]="\t\tproxy_busy_buffers_size {$buffers}k;";
		$f[]="\t\tproxy_temp_file_write_size {$this->proxy_temp_file_write_size}k;";
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: {$re[1]} {$re[2]}\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size...: {$buffers}k\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_temp_file_write_size: {$this->proxy_temp_file_write_size}k\n";}
		
		return @implode("\n",$f);
		
	}
	
	private function proxy_parameters($cache_peer_id=0){
		$rev=new nginx_sources($cache_peer_id);
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: proxy_pass: $this->servername:$this->host_port: ";
		$f[]="# proxy_parameters [".__LINE__."]";
		$f[]="\t\tproxy_read_timeout    {$rev->proxy_read_timeout}s;";
		$f[]="\t\tproxy_send_timeout	{$rev->proxy_send_timeout}s;";
		
		
		if($cache_peer_id>0){
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cacheid FROM reverse_sources WHERE ID='$cache_peer_id'"));
			$cacheid=intval($ligne["cacheid"]);
			if($cacheid>0){
				$this->proxy_buffering=1;
			}
		}
		
		
		
		if($this->proxy_buffering==1){
			$f[]="\t\tproxy_buffering       on;";
			$f[]="\t\tproxy_cache_min_uses $this->proxy_cache_min_uses;";
			
			
			if($this->proxy_max_temp_file_size>0){
				$f[]="\t\tproxy_max_temp_file_size {$this->proxy_max_temp_file_size}m;";
			}
			

		
			
			
			if($this->proxy_buffer_size>0){
				$f[]="\t\tproxy_buffer_size       {$this->proxy_buffer_size}k;";
			}else{
				$f[]="#\t\tproxy_buffer_size == 0";
			}
			
			$f[]=$this->proxy_buffers_check($this->proxy_buffers);
		}else{
			$f[]="#\t\tproxy_buffering  == 0";
		}
		
		
		
		return @implode("\n", $f)."\n";
	}
	
	private function strip_slashes($directory){
		if(preg_match("#^\/(.+?)\/$#",$directory,$re)){return $re[1];}
		if(preg_match("#^\/(.+?)$#",$directory,$re)){return $re[1];}
		if(preg_match("#^(.+?)\/$#",$directory,$re)){return $re[1];}
		return $directory;
		
	}
	
	private function proxy_pass_pattern($pattern){
		
		if(!preg_match("#^http(.*?):#",$pattern)){
			$pattern="http://$pattern";
		}
		
		$urlP=parse_url($pattern);
		return "{$urlP["scheme"]}://{$urlP["host"]}";
		
		
		
		
	}
	
	
	
	private function proxy_pass($cache_peer_id=null,$asregex=false,$folderid=0,$directory=null){
		$q=new mysql_squid_builder();
		$suffix=null;
		$http_prefix=null;
		$port=0;
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: proxy_pass: $this->servername:$this->host_port: Proxy:[$folderid]";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT start_directory FROM reverse_www WHERE servername='$this->servername'"));
		$start_directory=$ligne["start_directory"];
		if($start_directory<>null){$start_directory="$start_directory/";}
		
		$remote_path=$ligne["start_directory"];
		if($remote_path<>null){$start_directory="$remote_path/";}
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix start_directory `$start_directory`\n";}
		
		$f[]="# proxy_pass() Line:".__LINE__." start_directory=$start_directory";
		$f[]="# proxy_pass() Line:".__LINE__." cache_peer_id=$cache_peer_id; folderid=$folderid";
		
		
		if($folderid>0){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_dirs WHERE folderid='$folderid'"));
			$hostweb=$ligne["hostweb"];
			$f[]="# proxy_pass() Line:".__LINE__." folderid=$folderid hostweb=`$hostweb`,Local={$ligne["local"]}";
			
			if($hostweb<>null){
				if($GLOBALS["OUTPUT"]){echo "hostweb = `$hostweb`\n";}
				if($ligne["local"]==0){
					if(substr($hostweb, 0,1)=="/"){$suffix=$hostweb;$hostweb=null;}
					if($hostweb<>null){
						$hostweb=$this->proxy_pass_pattern($hostweb);
						$f[]="\t\tproxy_pass $hostweb;";	
						if($ligne["ForceRedirect"]==1){$f[]="\t\tproxy_redirect  $hostweb/   /;";}
					}
					
				}
				
				return @implode("\n", $f);
			}
		}
		
		$f[]="# proxy_pass() cache_peer_id=$cache_peer_id; folderid=0 [".__LINE__."]";
		
		if(!is_numeric($cache_peer_id)){
			$f[]="#\t\tproxy_pass() Line:".__LINE__." cache_peer_id is not numeric, aborting [".__LINE__."]";
			$f[]="\t\tproxy_pass http://127.0.0.1:82;";
			return @implode("\n", $f);
		}
		
		if($cache_peer_id==0){
			$f[]="#\t\tproxy_pass() Line:".__LINE__." cache_peer_id = 0 aborting [".__LINE__."]";
			$f[]="\t\tproxy_pass http://127.0.0.1:82;";
			return @implode("\n", $f);
		}		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
		$ipaddr=trim($ligne["ipaddr"]);
		$start_directory=trim($ligne["remote_path"]);
		$ssl=$ligne["ssl"];
		$port=$ligne["port"];
		$this->events("reverse_sources/$cache_peer_id ipaddr=$ipaddr port=$port / remote_path = $start_directory",__FUNCTION__,__LINE__);
		$forceddomain=$ligne["forceddomain"];
		$proxy_cookie_domain=intval($ligne["proxy_cookie_domain"]);
		
		if(strpos(" $ipaddr", "://")){
			$array=parse_url($ipaddr);
			if(isset($array["port"])){$port=$array["port"]; }
			if(isset($array["scheme"])){$http_prefix=$array["scheme"]; }
			if($start_directory<>null){
				if(isset($array["path"])){$directory=$array["path"]; }
			}
				
		}
		

		
		$f[]="# SSL:{$ligne["ssl"]} Port:{$ligne["port"]} http prefix:{$http_prefix} [".__LINE__."]";
		if(!is_numeric($port)){
			if($ligne["ssl"]==0){
				$this->events("reverse_sources/$cache_peer_id $port is not numeric -> 80",__FUNCTION__,__LINE__);
				$port=80;
			}else{
				$this->events("reverse_sources/$cache_peer_id $port is not numeric -> 443",__FUNCTION__,__LINE__);
				$port=443;
			}
		}
		
		
		
		
		$port_suffix=":$port";
		$this->events("reverse_sources/$cache_peer_id http_prefix = '$http_prefix'",__FUNCTION__,__LINE__);
		$this->events("reverse_sources/$cache_peer_id port_suffix = '$port_suffix'",__FUNCTION__,__LINE__);
		if($http_prefix==null){
			$http_prefix="https";
			if($port==80){$http_prefix="http";$port_suffix=null;}
			if($ssl==0){
				$http_prefix="http";
				if($port==80){$port_suffix=null;}
			}
			
			if($port==443){
				if($ssl==0){
					$f[]="# !! Port:{$ligne["port"]} I Assume that protcol is SSL but you should check configuration and define the SSL [".__LINE__."]";
				}
				$http_prefix="https";$port_suffix=null;
			}
		}
		
		
		if($directory==null){
			if($start_directory<>null){$directory=$start_directory;}
		}
		
		
		if($ipaddr==null){return null;}
		$slash="/";
		$directoryStriped=null;
		
		if($directory<>null){
			$directoryStriped=$this->strip_slashes($directory)."/";
			if($GLOBALS["OUTPUT"]){echo "$logprefix $directory = \"$directoryStriped\"\n";}
		}
		if($asregex){$slash=null;}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix Pass to = `{$http_prefix}://$ipaddr$port_suffix$suffix`\n";}
		
		if($this->IsSubstitutions()){
			if($directory<>null){
				if(strpos(" $directory", "~")==0){
					$f[]="\t\tsubs_filter ". str_replace(".", "\.", $ipaddr)." $directory gir;";
				}
			}else{
				$f[]="\t\tsubs_filter ". str_replace(".", "\.", $ipaddr)." $this->servername gir;";
				if($forceddomain<>null){
					$f[]="\t\tsubs_filter ". str_replace(".", "\.", $forceddomain)." $this->servername gir;";
				}
			}
		}else{
			$f[]="# --- IsSubstitutions ".count($this->CompileParams["MODULES"])." modules FALSE [".__LINE__."]";
			
		}
		
		
		$proxy_pass_separator="/";
		$this->events("Suffix=$suffix",__FUNCTION__,__LINE__);
		$this->events("start_directory=$start_directory",__FUNCTION__,__LINE__);
		$this->events("start_directory Start='".substr($start_directory, 0,1)."'",__FUNCTION__,__LINE__);
		$this->events("start_directory End='".substr($start_directory, strlen($start_directory)-1,1)."'",__FUNCTION__,__LINE__);
		
		if($suffix<>null){
			if(substr($suffix, strlen($suffix)-1,1)=="/"){$proxy_pass_separator=null;}
		}
		
		if($start_directory<>null){
			if(substr($start_directory, 0,1)=="/"){$proxy_pass_separator=null;}
			if(substr($start_directory, strlen($start_directory)-1,1)<>"/"){$start_directory=$start_directory."/";}
		}
		
		
		$proxy_pass_suffix=$suffix.$proxy_pass_separator.$start_directory;
		$proxy_pass_suffix=str_replace("//", "/", $proxy_pass_suffix);
		$this->events("proxy_pass_suffix=$proxy_pass_suffix",__FUNCTION__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "$logprefix Suffix = `$proxy_pass_suffix`\n";}
		
		$f[]="# --- forceddomain        = $forceddomain [".__LINE__."]";
		$f[]="# --- start_directory     = $start_directory [".__LINE__."]";
		$f[]="# --- proxy_cookie_domain = $proxy_cookie_domain [".__LINE__."]";
		$f[]="# --- ForceRedirect 		= {$ligne["ForceRedirect"]} [".__LINE__."]";
		$f[]="# --- Suffix			 	= {$suffix} [".__LINE__."]";
		$f[]="# --- proxy_pass_suffix 	= {$proxy_pass_suffix} [".__LINE__."]";
		$f[]="# --- proxy_pass_separator= {$proxy_pass_separator} [".__LINE__."]";
		
		$this->events("proxy_pass=[$http_prefix]://[$ipaddr][$port_suffix][$proxy_pass_suffix]",__FUNCTION__,__LINE__);
		$f[]="\t\tproxy_pass {$http_prefix}://$ipaddr$port_suffix$proxy_pass_suffix;";
		
		if($forceddomain<>null){
			if($proxy_cookie_domain==1){
				$f[]="\t\tproxy_cookie_domain $forceddomain \$host;";
			}
		}
		
		if($ligne["ForceRedirect"]==1){
			$ForceRedirect=$ipaddr;
			if($forceddomain<>null){$ForceRedirect=$forceddomain;}
			$proxy_redirect="$ForceRedirect$port_suffix$proxy_pass_suffix/   /;";
			$proxy_redirect=str_replace("//", "/", $proxy_redirect);
			$f[]="\t\tproxy_redirect {$http_prefix}://$proxy_redirect";
		}
		
		return @implode("\n", $f);
		
	}
	
	private function forcedomain($cache_peer_id=null){
		if(!is_numeric($cache_peer_id)){return null;}
		if($cache_peer_id==0){return null;}
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT forceddomain FROM reverse_sources WHERE ID='$cache_peer_id'"));
		return trim($ligne["forceddomain"]);
	}
	
	function events($text,$function=null,$line=0){
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		$common="/var/log/nginx.builder.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$h = @fopen($common, 'a');
		$sline="[$pid] $text";
		$line="$date [$pid] ($function/$line) $text\n";
		@fwrite($h,$line);
		@fclose($h);
	}
	
	private function ParseHostHeader($HostHeader){
		if(strpos(" $HostHeader", "/")>0){
			if(substr($HostHeader, 0,1)=="/"){return null;}
			
			if(strpos(" $HostHeader", "http")>0){
				$urlEXt=parse_url($HostHeader);
				return $urlEXt["host"];
			}
		}

		if(substr($HostHeader, 0,1)=="."){$HostHeader=substr($HostHeader,1,strlen($HostHeader));}
		return $HostHeader;
		
	}
	
	private function IsForceRedirect(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ForceRedirect FROM reverse_sources WHERE ID='$this->cache_peer_id'"));
		if($ligne["ForceRedirect"]==0){return false;}
		return true;
		
	}
	
	private function is_proxy_cookie_domain(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT proxy_cookie_domain FROM reverse_sources WHERE ID='$this->cache_peer_id'"));
		$proxy_cookie_domain=trim($ligne["proxy_cookie_domain"]);
		if(!is_numeric($proxy_cookie_domain)){return true;}
		if($proxy_cookie_domain==1){return true;}
		return false;		
	}
	
	private function isDebug(){
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www", "debug")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `debug` smallint(1) NOT NULL DEFAULT 0");return false;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `debug` FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["debug"]==1){return true;}
		return false;
	}
	
	
	private function AUTHENTICATOR_DIRECTORY($authenticator_id,$directoryname){
		if(!is_numeric($authenticator_id)){return;}
		if($authenticator_id==0){return;}
		$q=new mysql_squid_builder();
		$directoryname=$this->strip_slashes($directoryname);
		$sock=new sockets();
		$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
		if($NginxAuthPort==null){$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";}
		
		if(preg_match("#^unix#", $NginxAuthPort)){
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort:/authenticator.php";
		}else{
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort/authenticator.php";
		}
		
		$groupes=$this->AUTHENTICATOR_GROUPS($authenticator_id);
		$f[]="\n\t\tlocation = /$directoryname/ARTICAAUTHENTICATOR$authenticator_id {";
		$f[]="\t\t\tproxy_pass {$array["REMOTE_FRAMEWORK"]}?uri=\$request_uri$groupes&servername=\$server_name;";
		$f[]="\t\t}\n";
		return @implode("\n", $f);
	}
	
	
	private function AUTHENTICATOR_PROXY(){
		$q=new mysql_squid_builder();
		$sock=new sockets();
		
		
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!$this->IsAutheRequestsModule()){$array["USE_AUTHENTICATOR"]=0;}
		
		if(!is_numeric($array["USE_AUTHENTICATOR"])){$array["USE_AUTHENTICATOR"]=0;}
		if(!is_numeric($array["AUTHENTICATOR_RULEID"])){$array["AUTHENTICATOR_RULEID"]=0;}
		if($array["AUTHENTICATOR_RULEID"]==0){$array["USE_AUTHENTICATOR"]=0;}
		

		if($array["USE_AUTHENTICATOR"]==0){return null;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM authenticator_rules WHERE ID='{$array["AUTHENTICATOR_RULEID"]}'"));
		if($ligne["enabled"]==0){$array["USE_AUTHENTICATOR"]=0;}
		
		
		
		if($array["USE_AUTHENTICATOR"]==0){return null;}
		$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
		if($NginxAuthPort==null){
			$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";
			$sock->SET_INFO("NginxAuthPort",$NginxAuthPort);
		}
		
		
		
		if(preg_match("#^unix#", $NginxAuthPort)){
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort:/authenticator.php";
		}else{
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort/authenticator.php";
		}
		
		$array["LDAP_BANNER"]=urlencode(base64_encode($array["LDAP_BANNER"]));
		
		$groupes=$this->AUTHENTICATOR_GROUPS($array["AUTHENTICATOR_RULEID"]);

		$f[]="\tlocation = /ARTICAAUTHENTICATOR {";
		$f[]=$this->headers_x_forwarded_for(true);
		$f[]="\t\tproxy_pass_request_headers on;\n";
		$f[]="\t\tproxy_pass {$array["REMOTE_FRAMEWORK"]}?uri=\$request_uri$groupes&servername=\$server_name&banner={$array["LDAP_BANNER"]};";
		$f[]="\t}";
		return @implode("\n", $f);
				
		
	}
	
	
	private function AUTHENTICATOR_GROUPS($ruleid){
		$q=new mysql_squid_builder();
		
		$sql="SELECT cachetime FROM authenticator_rules WHERE ID='$ruleid'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		$cachetime=$ligne["cachetime"];
		$sesskey=time();
		
		$sql="SELECT
		authenticator_sourceslnk.ID,
		authenticator_sourceslnk.zorder,
		authenticator_sourceslnk.groupid,
		authenticator_groups.groupname,
		authenticator_groups.group_type,
		authenticator_groups.enabled
		FROM authenticator_sourceslnk,authenticator_groups
		WHERE authenticator_sourceslnk.ruleid='$ruleid'
		AND authenticator_sourceslnk.groupid=authenticator_groups.ID
		AND authenticator_groups.enabled=1
		ORDER BY zorder";
		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo "\n   ***** \nStarting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername FATAL $q->mysql_error\n   ***** \n";
		}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticator rule $ruleid ". mysql_num_rows($results)." groups\n";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticator {$ligne["groupid"]} = {$ligne["group_type"]}\n";
			$GPS[$ligne["ID"]]=$ligne["group_type"];
		}
		
		$sql="SELECT
		authenticator_authlnk.ID,
		authenticator_authlnk.zorder,
		authenticator_auth.groupname,
		authenticator_auth.group_type,
		authenticator_authlnk.groupid
		FROM authenticator_authlnk,authenticator_auth
		WHERE authenticator_authlnk.ruleid='$ruleid' 
		AND authenticator_authlnk.groupid=authenticator_auth.ID";
		
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$DES[$ligne["ID"]]=$ligne["group_type"];
		}
		
		$gps_encoded=base64_encode(serialize($GPS));
		$des_encoded=base64_encode(serialize($DES));
		return "&ruleid=$ruleid&sesskey=$sesskey&gps=$gps_encoded&des=$des_encoded&cachetime=$cachetime";
	}
	
	
	private function AUTHENTICATOR_BASE(){
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!$this->IsAutheRequestsModule()){$array["USE_AUTHENTICATOR"]=0;}
		
		if(!is_numeric($array["USE_AUTHENTICATOR"])){$array["USE_AUTHENTICATOR"]=0;}
		if(!is_numeric($array["USE_REMOTE_FRAMEWORK"])){$array["USE_REMOTE_FRAMEWORK"]=0;}
		if(!isset($array["REMOTE_FRAMEWORK"])){$array["REMOTE_FRAMEWORK"]="https://articaserver:9000/authenticator.php";}
		
		
		if($array["USE_AUTHENTICATOR"]==1){
			$f[]="\t\tauth_request /ARTICAAUTHENTICATOR;";
			$f[]="\t\tauth_request_set \$username \$upstream_http_x_username;";
			$f[]="\t\tadd_header X-Set-Username \$username;";
			return @implode("\n", $f);
		}
	}
	
	private function LDAP_server_auth(){
		return null;
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		
		
		if(!$this->IsLdapModule()){$array["ENABLE_LDAP_AUTH"]=0;}
			
				
		
		if(!isset($array["ENABLE_LDAP_AUTH"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP Authentication not set.\n";}
			return null;
		}
		
		if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
		if($array["LDAP_PORT"]<>389){$ldapport=":{$array["LDAP_PORT"]}";}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP {$array["LDAP_SERVER"]}$ldapport\n";}
		$f[]="\tauth_ldap_url ldap://{$array["LDAP_SERVER"]}$ldapport/{$array["LDAP_SUFFIX"]}?{$array["LDAP_USER_ATTR"]}?sub?(objectClass={$array["LDAP_OBJCLASS_ATTR"]});";
		if($array["LDAP_DN"]<>null){
			$array["LDAP_PASSWORD"]=str_replace("$", "\$", $array["LDAP_PASSWORD"]);
			$f[]="\tauth_ldap_binddn {$array["LDAP_DN"]};";
			$f[]="\tauth_ldap_binddn_passwd {$array["LDAP_PASSWORD"]};";
		}
		
		if($array["LDAP_GROUP_ATTR_ISDN"]==1){
			$f[]="\tauth_ldap_group_attribute_is_dn on;";
		}else{
			$f[]="\tauth_ldap_group_attribute_is_dn off;";
		}
		$f[]="\tauth_ldap_group_attribute {$array["LDAP_GROUP_ATTR"]};";
		return @implode("\n", $f);
		
	}
	
	private function LDAP_require(){
		return null;
		if(!$this->IsLdapModule()){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP Authentication not compiled.\n";}return null;}
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!isset($array["ENABLE_LDAP_AUTH"])){return null;}
		if(!is_numeric($array["LDAP_REQUIRE_VALID"])){$array["LDAP_REQUIRE_VALID"]=1;}
		if($array["LDAP_BANNER"]==null){$array["LDAP_BANNER"]="Please login";}
		if($array["LDAP_REQUIRE_VALID"]==1){
			$f[]="\t\tauth_ldap \"{$array["LDAP_BANNER"]}\";";
			$f[]="\t\tauth_ldap_require valid_user;";
			$f[]="\t\tauth_ldap_satisfy any;";
			return @implode("\n", $f);
		}
		
	}
	
	
	
	private function error_pages(){
		if($this->OWA){
		$f[]="\terror_page 500 502 503 504 /exch500.html;";
		$f[]="\tlocation = /exch500.html {";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default;";
		$f[]="\t}";
		$f[]="\tlocation = /exchange2010.png	{";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default/img;";
		$f[]="\t}\n";
		
		}
		
		return @implode("\n", $f);
	}
	
	private function error_pages_default(){
		if($this->EnableArticaDynamicErrors==0){return null;}
		$squid_reverse=new squid_reverse();
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		$f[]="# ArticaErrors === {$ligne["ArticaErrors"]}";
		while (list ($key, $value) = each ($squid_reverse->errors_page) ){
			$f[]="\terror_page $value = @backendErrors{$value};";
			$f[]="\t\tlocation @backendErrors{$value} {";
			$f[]="\t\tinternal;";
			$f[]="\t\tset \$a \$request_uri;";
			if($this->IsMiscModule()){
				$f[]="\t\tset_encode_base64 \$a;";
			}
			$f[]="\t\tproxy_pass  http://unix:/var/run/nginx-authenticator.sock:/authenticator.php?error-page={$value}&servername=$this->servername&localport=$this->host_port&ipaddr=\$remote_addr&uencode=\$a;";
			$f[]="\t}";
		}
		$f[]="";
		return @implode("\n", $f);
	}
	
	private function header_csp(){
		
		if(!isset($GLOBALS["ALL_WEBSITES"])){
			$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1";
			$q=new mysql_squid_builder();
			$results=$q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$ligne["servername"]=trim($ligne["servername"]);
				$GLOBALS["ALL_WEBSITES"]["https://{$ligne["servername"]}"]=true;
				$GLOBALS["ALL_WEBSITES"]["http://{$ligne["servername"]}"]=true;
			}
			$q=new mysql();
			$sql="SELECT * FROM freeweb WHERE `enabled`=1";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while ($ligne = mysql_fetch_assoc($results)) {
				$ligne["servername"]=trim($ligne["servername"]);
				$GLOBALS["ALL_WEBSITES"]["https://{$ligne["servername"]}"]=true;
				$GLOBALS["ALL_WEBSITES"]["http://{$ligne["servername"]}"]=true;
			}
			
			$GLOBALS["ALL_WEBSITES"]["https://fonts.googleapis.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://ssl.google-analytics.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://connect.facebook.net"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://s-static.ak.facebook.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://themes.googleusercontent.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://www.facebook.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://s-static.ak.facebook.com"]=true; 
			$GLOBALS["ALL_WEBSITES"]["https://tautt.zendesk.com"]=true;
		}
		reset($GLOBALS["ALL_WEBSITES"]);
		while (list ($site, $value) = each ($GLOBALS["ALL_WEBSITES"]) ){
			$t[]=$site;
		}
		
		$f[]=@implode($t," ")."; img-src 'self' ; style-src 'self' 'unsafe-inline' ".@implode($t," ")." ; object-src 'none'\";";
		
		
		return "add_header Content-Security-Policy \"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' $websites; img-src 'self' $websites; style-src 'self' 'unsafe-inline' $websites; font-src 'self' $websites; frame-src $websites; object-src 'none'\";";
		
		
	}
	
	private function limit_bandwitdth(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT limit_rate,limit_rate_after FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		
		if($ligne["limit_rate"]==0){return;}
		$f[]="\t\tlimit_rate_after {$ligne["limit_rate_after"]}mb;";
		$limit_rate=$ligne["limit_rate"]*1024;
		$f[]="\t\tlimit_rate {$limit_rate}k;";
		return @implode("\n", $f);
		
	}
	
	
	private function error_pages_perso(){
		if($this->EnableArticaDynamicErrors==0){return null;}
		$squid_reverse=new squid_reverse();
		$q=new mysql_squid_builder();
		$f[]="\t\tproxy_intercept_errors on;";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		
		
		while (list ($key, $value) = each ($squid_reverse->errors_page) ){
			$params="?error-page=$value&error-ID={$ligne[$value]}&servername=$this->servername&localport=$this->host_port&uri=\$request_uri";
			$f[]="\t\terror_page $value /nginx.error.php$params;";
		}
		$f[]="";
		return @implode("\n", $f);
	}
	
	
	private function error_pages_perso_framework(){
		if($this->EnableArticaDynamicErrors==0){return null;}
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		$f[]="\tlocation /nginx.error.php {";
		$f[]=$this->headers_x_forwarded_for(true);
		$f[]="\t\tproxy_pass_request_headers on;\n";
		$f[]="\t\tproxy_no_cache 1;";
		$f[]="\t\tproxy_cache_bypass 1;";
		$f[]="\tproxy_pass http://unix:/var/run/nginx-authenticator.sock:/authenticator.php;";
		//$f[]=$this->php5_params("/usr/share/artica-postfix");
		$f[]="\t}\n";
		return @implode("\n", $f);		
		
	}
	
	

	
	
	private function translate_destdir($directory){
		$asregex=false;
		$directory=trim(stripslashes($directory));
		$Asfile=false;
		if(preg_match("#\.[a-z0-9]+$#", $directory)){
			$Asfile=true;
			
		}
		
		
		
		$firstPat=substr($directory, 0,1);
		$lastPat=substr($directory, strlen($directory)-1,1);
		if($firstPat<>"~"){
			if($firstPat<>'/'){$directory="/$directory";}
			if(!$Asfile){
				if($lastPat<>'/'){$directory="$directory/";}
			}
		}else{
			$asregex=true;
		}
		
		return array($directory,$asregex,$Asfile);
		
	}
	
	public function groupware_zarafa_Frontend(){
		if(!$this->spawn_fcgi()){return null;}
		$free=new freeweb($this->servername);
		$rootdir=$free->WORKING_DIRECTORY;
		$php_values=$free->php_values(true);
		$port=80;
		if($this->ssl==1){$port=443;}
		$f[]="server {";
		$f[]="        server_name $this->servername;";
		$f[]="        listen $port;";
		$f[]="        access_log  /var/log/apache2/common-access.log;";
		$f[]="        error_log   /var/log/apache2/error.log;";
		$f[]="        index index.php;";
		$f[]="        root $rootdir/;";
		if($this->ssl==1){
			$this->LoadParams();
			$f[]=" SSL ---------------------------------- [".__LINE__."]";
			$f[]="\tssl on;";
			$nginx_certificate=new nginx_certificate($this->sslcertificate,$this->ssl_client_certificate);
			$f[]=$nginx_certificate->GetConf();
			$f[]="\tssl_session_timeout  5m;";
			$f[]="\tssl_protocols  $this->ssl_protocols;";
			$f[]="\tssl_ciphers '$this->ssl_ciphers';";
			$f[]="\tssl_prefer_server_ciphers   on;";
		}
		

		$f[]="        location / {";
		$f[]="                try_files \$uri \$uri/ /index.php;";
		$f[]="        }";
		$f[]="";
		$f[]="location ~ \.php$ {";
		$f[]="                try_files \$uri =404;";
		$f[]="                fastcgi_buffers 8 16k;";
		$f[]="                fastcgi_buffer_size 32k;";
		$f[]="                fastcgi_read_timeout 300;";
		$f[]="                fastcgi_connect_timeout 300;";
		$f[]="                fastcgi_send_timeout 300;";
		$f[]="                fastcgi_index  index.php;";
		$f[]="                fastcgi_param   SCRIPT_FILENAME         \$document_root\$fastcgi_script_name;";
		$f[]="                fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;";
		$f[]="                fastcgi_param   PATH_INFO               \$fastcgi_path_info;";
		$f[]="                fastcgi_param   REQUEST_URI             \$request_uri;";
		$f[]="                fastcgi_param   DOCUMENT_URI            \$document_uri;";
		$f[]="                fastcgi_param   DOCUMENT_ROOT           \$document_root;";
		$f[]="                fastcgi_param   SERVER_PROTOCOL         \$server_protocol;";
		$f[]="                fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
		$f[]="                fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;";
		$f[]="                fastcgi_param   REMOTE_ADDR             \$remote_addr;";
		$f[]="                fastcgi_param   REMOTE_PORT             \$remote_port;";
		$f[]="                fastcgi_param   SERVER_ADDR             \$server_addr;";
		$f[]="                fastcgi_param   SERVER_PORT             \$server_port;";
		$f[]="                fastcgi_param   SERVER_NAME             \$server_name;";
		$f[]="                fastcgi_param   HTTPS                   \$server_https;";
		$f[]="				  fastcgi_pass 	   unix:/var/run/php-fpm-zarafa.sock;";
		$f[]="				  include fastcgi_params;";
		$f[]="        }";
		$f[]="}";
		
		$filename="/etc/nginx/sites-enabled/freewebs-ssl-$this->servername.$port.conf";
		@file_put_contents($filename, @implode("\n", $f));
		
	}	
	
	private function http_locations_authenticator($ligne){
		$pageid=$ligne["webpageid"];
		$directoryZ=$this->translate_destdir($ligne["directory"]);
		$directory=$directoryZ[0];
		$asregex=$directoryZ[1];
		$AsFile=$directoryZ[2];
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Page ID: $pageid";
		if($GLOBALS["OUTPUT"]){echo "$logprefix [{$ligne["directory"]}]\n";}
		$proxy_cache=$this->keys_zone($ligne["storeid"]);
		$dirpat=$this->strip_slashes($directory);
		
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		$f[]="location $directory_name {";
		
		if($ligne["folderid"]>0){
			$f[]=$this->replace_folder_rules($ligne["folderid"]);
		}
		$replace_rules=$this->replace_rules_build($ligne["replaceid"]);
		if($replace_rules<>null){
			$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
			$f[]=$replace_rules;
		}
		$f[]="\t\tproxy_pass http://unix:/var/run/nginx-authenticator.sock:/authenticator.php?pageid=$pageid;";
		$f[]="}";
		$f[]="";
		@mkdir("/home/reverse_pages_content",0777);
		@chmod("/home/reverse_pages_content",0777);
		return @implode("\n", $f);
		
	}
	
private function http_location_cache_params($ligne){

	$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port LOCATION CACHE:";
	if(!is_numeric($ligne["NoCache"])){$ligne["NoCache"]=0;}
	if(!is_numeric($ligne["proxy_cache_min_uses"])){$ligne["proxy_cache_min_uses"]=1;}
	if(!is_numeric($ligne["proxy_cache_valid"])){$ligne["proxy_cache_valid"]=4320;}
	if(!preg_match("#([0-9]+)\s+([0-9]+)([a-z]+)#", $ligne["proxy_buffers"])){$ligne["proxy_buffers"]="8 8k";}
	if(!is_numeric($ligne["proxy_buffering"])){$ligne["proxy_buffering"]=1;}
	if(!is_numeric($ligne["proxy_buffer_size"])){$ligne["proxy_buffer_size"]=8;}
	if(!is_numeric($rev->proxy_read_timeout)){$rev->proxy_read_timeout=300;}
	if(!is_numeric($rev->proxy_send_timeout)){$rev->proxy_send_timeout=300;}
	
	$f[]="\t\tproxy_cache_use_stale  error timeout invalid_header updating http_500 http_502 http_503 http_504;";
	
	
	$f[]="\t\tproxy_read_timeout    {$rev->proxy_read_timeout}s;";
	$f[]="\t\tproxy_send_timeout	{$rev->proxy_send_timeout}s;";
	
	if($ligne["NoCache"]==1){
		$f[]="\t\tproxy_cache_bypass 1;";
		return @implode("\n", $f);
	}
	
	
	if($ligne["proxy_buffering"]==1){
		$f[]="\t\tproxy_buffering on;";
	}else{
		$f[]="\t\tproxy_buffering off;";
	}
	if($ligne["proxy_buffering"]==1){
		if($ligne["proxy_buffer_size"]>0){
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: [{$ligne["proxy_buffers"]}]\n";}
			$f[]=$this->proxy_buffers_check($ligne["proxy_buffers"]);
		}
	}
	if($ligne["proxy_cache_valid"]>0){
		$f[]="\t\tproxy_cache_valid 200 302 {$ligne["proxy_cache_valid"]}m;";
		$f[]="\t\texpires {$ligne["proxy_cache_valid"]}m;";
	}
	//$f[]="\t\tproxy_cache_revalidate  on;";
	return @implode("\n", $f);
}
	
	
	private function http_locations_proxy($ligne){
		$directoryZ=$this->translate_destdir($ligne["directory"]);
		$directory=$directoryZ[0];
		$asregex=$directoryZ[1];
		$AsFile=$directoryZ[2];
		
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Proxy: $directory";
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix [{$ligne["directory"]}]\n";}
			
		$HostHeader=null;
		if($ligne["hostweb"]<>null){$HostHeader=$this->ParseHostHeader($ligne["hostweb"]);}
		
		
		
		$proxy_cache=$this->keys_zone($ligne["storeid"]);
		if(!is_numeric($ligne["limit_rate"])){$ligne["limit_rate"]=0;}
		if($GLOBALS["OUTPUT"]){echo "$logprefix storeid=[{$ligne["storeid"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix cache_peer_id=[{$ligne["cache_peer_id"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix folderid=[{$ligne["folderid"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix authenticator=[{$ligne["authenticator"]}] [".__LINE__."]\n";}
		
		
		
		$proxy_pass=$this->proxy_pass($ligne["cache_peer_id"],$asregex,$ligne["folderid"],$ligne["directory"]);
		
		$forcedomain=$this->forcedomain($ligne["cache_peer_id"]);
		if($forcedomain<>null){$HostHeader=$forcedomain;}
		$dirpat=$this->strip_slashes($directory);
		
		
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		
		
		$f[]="location $directory_name {";
		
		$f[]="# Bandwitdth Limitation: {$ligne["limit_rate"]}Mb/s after {$ligne["limit_rate_after"]}MB ".__CLASS__."/".__LINE__;
		if($ligne["limit_rate"]>0){
			$f[]="limit_rate_after {$ligne["limit_rate_after"]}mb;";
			$limit_rate=$ligne["limit_rate"]*1024;
			$f[]="limit_rate {$limit_rate}k;";
		}
		
		
		
		
		//$f[]="\t\tproxy_redirect off;";
		if(!$asregex){
		if(!$AsFile){
				$f[]="\t\trewrite ^$directory(.*) /$1 break;";
			}else{
				$f[]="\t\trewrite ^$directory /$1 break;";
			}
		}
		if($ligne["folderid"]>0){
			$f[]=$this->replace_folder_rules($ligne["folderid"]);
		}
		$AUTHENTICATOR_DIRECTORY=$this->AUTHENTICATOR_DIRECTORY($ligne["authenticator"],$ligne["directory"]);
		
		
		$replace_rules=$this->replace_rules_build($ligne["replaceid"]);
		if($replace_rules<>null){
			$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
			$f[]=$replace_rules;
		}
		
		
		if($HostHeader<>null){
			$f[]="\t\tproxy_set_header Referer http://$HostHeader;";
			$f[]="\t\tproxy_set_header Host $HostHeader;";
		}
		$f[]=$this->headers_x_forwarded_for();
		$f[]="\t\tproxy_hide_header \"Vary\";";
		$f[]="\t\tadd_header \"Vary\" \"Accept-Encoding\";";
		$f[]="\t\tproxy_set_header X-Forwarded-Ssl on;";
		if($AUTHENTICATOR_DIRECTORY<>null){
		$f[]="\t\tproxy_intercept_errors on;";
		$f[]="\t\terror_page 403 /nginx.error.php?results-page=yes&ruleid={$ligne["authenticator"]}&uri=\$request_uri;";
		$f[]="\t\tauth_request /$dirpat/ARTICAAUTHENTICATOR{$ligne["authenticator"]};";
		$f[]="\t\tauth_request_set \$username \$upstream_http_x_username;";
		}
		
		$f[]=$this->http_location_cache_params($ligne);
		$f[]="\t\tproxy_pass_header Date;";
		$f[]="\t\tproxy_pass_header Server;";
		
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		if($proxy_pass<>null){$f[]=$proxy_pass;}
		if($AUTHENTICATOR_DIRECTORY<>null){
			$f[]=$AUTHENTICATOR_DIRECTORY;
		}
		
		$f[]="}";
		$f[]="";		
		return @implode("\n", $f);
		
	}
	private function http_locations_local($ligne){
		$directory=trim(stripslashes($ligne["directory"]));
		$destination=$ligne["localdirectory"];
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		if(preg_match("#.+?\.[a-z]+$#", $destination)){$destination=dirname($destination);}
		if(!is_numeric($ligne["limit_rate"])){$ligne["limit_rate"]=0;}
		
		
		
		$f[]="\tlocation $directory_name {";
		$f[]="\t\troot\t$destination;";
		$f[]="# Bandwitdth Limitation: {$ligne["limit_rate"]}Mb/s after {$ligne["limit_rate_after"]}MB ".__CLASS__."/".__LINE__;
		if($ligne["limit_rate"]>0){
			$f[]="\t\tlimit_rate_after {$ligne["limit_rate_after"]}mb;";
			$limit_rate=$ligne["limit_rate"]*1024;
			$f[]="\t\tlimit_rate {$limit_rate}k;";
		}		
		
		
		$f[]="\t}";
		
		$f[]="";
		return @implode("\n", $f);
	}
	
	

	
	private function http_locations($servername){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername, checking LOCATIONS...\n";}
		$f=array();
		if($this->SargDir<>null){
			
			$f[]="\tlocation ~ /sarg.css$ {";
			$f[]="\t\talias\t$this->SargDir/sarg.css;";
			$f[]="\t}";
			$f[]="";
			$f[]="\tlocation ~ /pattern.png$ {";
			$f[]="\t\talias\t$this->SargDir/pattern.png;";
			$f[]="\t}";
			$f[]="\tlocation ~ /logo.gif$ {";
			$f[]="\t\talias\t$this->SargDir/logo.gif;";
			$f[]="\t}";	
			$f[]="\tlocation ~ /sarg/index.html$ {";
			$f[]="\t\talias\t$this->SargDir/index.html;";
			$f[]="\t}";	
			$f[]="\tlocation ~ /ui-bg_highlight.png$ {";
			$f[]="\t\talias\t/usr/share/artica-postfix/css/images/ui-bg_highlight.png;";
			$f[]="\t}";	
			$f[]="";			
			$f[]="\tlocation ~ /sarg/(.*?)$ {";
			$f[]="\t\tautoindex on;";
			$f[]="\t\talias\t$this->SargDir/$1;";
			$f[]="\t}";
			$f[]="";
		}
		
		
		$f[]=$this->reverse_dirs();
		$f[]=$this->webdav_containers();
		return @implode("\n", $f);
		
		
	}
	
	
	private function reverse_dirs(){
		$q=new mysql_squid_builder();
		
		$sql="SELECT * FROM reverse_dirs WHERE servername='$this->servername' AND enabled=1 ORDER BY directory";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$asregex=false;
			$directory=trim($ligne["directory"]);
				
			if(isset($ALREADY[$directory])){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $directory already set\n";}continue;}
			$ALREADY[$directory]=true;
			if($directory==null){continue;}
				
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: [$directory] Is local ={$ligne["local"]}\n";}
			$f[]=$this->reverse_dirs_pattern($ligne);
		}

		return @implode("\n", $f);
	}
	private function reverse_dirs_pattern($ligne){
		if($ligne["webpageid"]>0){
			return $this->http_locations_authenticator($ligne);
		}
		if($ligne["local"]==1){
			return $this->http_locations_local($ligne);
		}
		return $this->http_locations_proxy($ligne);
	}
	
	

	
	
	private function http_proxy(){
		$unix=new unix();
		$nginx_tools=new nginx_tools($this->servername);
		$ServersAliases=null;
		$host_port_ssl=null;
		$default_server=null;
		$server_name=null;
		$ErrorLogLevel="warn";
		if(trim($this->servername)==null){$this->FillHostnameAsIP();}
		$IsSSL=false;
		if($this->ssl==1){$IsSSL=true;}
		if($this->IsAFreeWeb){
			$IsSSL=$this->isFreeWebSSL();
			if(!$IsSSL){$this->set_proxy_port(82);}else{$this->set_proxy_port(447);}
		}
		
		if($this->default_server==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." IS A DEFAULT SERVER..\n";}
			$default_server=" default_server";
		}
		
		
		$HostHeader="\$host";
		if(trim($this->forceddomain<>null)){$HostHeader="$this->forceddomain";}
		if($this->host_port==0){$this->host_port=80;}
		$HostIPADDR=$this->MyHostnameListen();
		
		$AsProxy=true;
		
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: proxy_disabled='$this->proxy_disabled'\n";}
		
		if($this->proxy_disabled==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: It is not a reverse Proxy\n";}
			$AsProxy=false;
		}		
		
		if($IsSSL){if($this->host_port<>80){$host_port_ssl=" ssl";}}
		
		$LISTEN_FINAL="{$HostIPADDR}$this->host_port{$host_port_ssl}{$default_server}";
		if($this->BuildFramework){
			$LISTEN_FINAL="unix:/usr/share/artica-postfix/ressources/web/framework.sock";
			$IsSSL=false;
		}
		
		$f[]="server {";
		$f[]="# $this->servername/$this->servername_pattern IsSSL:$IsSSL Listen:$LISTEN_FINAL [".__LINE__."]";
		
//*********************************** Directive Server Name ********************** EN PREMIER		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." listen {$HostIPADDR}$this->host_port{$default_server}\n";}
		if(!$this->BuildFramework){$f[]="\tserver_name ".$this->CheckServerName($this->servername);}
		$f[]="\tserver_name_in_redirect off;";

//*********************************************************************************************************		
		
		
		$f[]="\tlisten $LISTEN_FINAL;";
		
		if($this->mixed_ssl==1){
			$IsSSL=false;
			$f[]="\tlisten {$HostIPADDR}443 ssl{$default_server};";
			$f[]=$this->ssl_directives();
			
		}
		
		
		if($IsSSL){
			if($this->host_port==80){
				$f[]="\tlisten {$HostIPADDR}443 ssl{$default_server};";
				$f[]=$this->ssl_directives();
			}else{
				$f[]=$this->ssl_directives();
			}
		}
		
		
		if($this->isDebug()){$ErrorLogLevel="debug";}

		$LDAP_server_auth=$this->LDAP_server_auth();
		if($LDAP_server_auth<>null){$f[]=$LDAP_server_auth;}
		$Logsuffix=$this->servername;
		$Logsuffix=str_replace("unix:", "unix-", $Logsuffix);
		$Logsuffix=str_replace("/", "", $Logsuffix);
		
		// LOGS
		$f[]="# Access logs [".__LINE__."]";
		$access_log="\taccess_log  /var/log/apache2/common-access.log awc_log;";
		
		
		@mkdir("/var/log/apache2/$Logsuffix",0755,true);
		$unix->chown_func($this->APACHE_USER,$this->APACHE_SRC_GROUP,"/var/log/apache2/$Logsuffix");
		if(preg_match("#^unix:#", $this->servername)){
			$access_log="\taccess_log /dev/null";
			$ErrorLogLevel="crit";
		}

		$f[]=$access_log;
		$f[]="\terror_log  /var/log/apache2/nginx.error.log $ErrorLogLevel;";
		
		
		if($this->OWA){
			$f[]="rewrite ^/$ http://$server_name_text$this->HostPortLNK/owa permanent;";
		}
		
		if($this->RedirectQueries<>null){
			$f[]="return 301 $this->RedirectQueries\$request_uri;";
			$f[]="}";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: ".count($f)." parameters\n";}
			return @implode("\n", $f);
				
		}
		$f[]=$this->general_security();
		$f[]=$this->error_pages_default();
		
		$f[]=$nginx_tools->AntiExploits();
		
		if($this->DocumentRoot<>null){
			$f[]="\troot   $this->DocumentRoot;";
			if($this->IndexFile<>null){ $f[]="\tindex $this->IndexFile;";}

			$f[]="\tlocation ~* ^.+.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt)$ {";
			//$f[]="\t\taccess_log        off;";
			$f[]="\t\texpires           max;";
			$f[]="\t}";
		}

		
		
		
		if(!$AsProxy){
			$f[]=$this->ROOT_WEBSERVER();
			
		}else{
			$f[]=$this->ROOT_PROXY();
		}
		if(!$this->NoErrorPages){
			$f[]=$this->error_pages();
			$f[]=$this->error_pages_perso_framework();
		}		

		$f[]="}";		

		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port:".count($f)." parameters\n";}
		return @implode("\n", $f);
		
	}
	
	private function CheckServerName($servername){
		if($this->BuildFramework){return null;}
		$nginx_tools=new nginx_tools($this->servername);
		if($this->servername_pattern<>null){$nginx_tools->ForceServerName=$this->servername_pattern;}
		$nginx_tools->ServersAliases=$this->ServersAliases;
		return $nginx_tools->server_name();
	}
	
	
	private function ssl_directives(){
		$GLOBALS["SSL_RETURNED"]=true;
		$nginx_certificate=new nginx_certificate($this->sslcertificate,$this->ssl_client_certificate);
		if($this->ssl_protocols==null){$this->ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";}
		if($this->ssl_ciphers==null){$this->ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}
		
		$f[]="# SSL ---------------------------------- [".__LINE__."]";
		$f[]="# cache_peer_id = $this->cache_peer_id";
		$f[]="# sslcertificate = $this->sslcertificate";
		$f[]="# ssl_client_certificate = $this->ssl_client_certificate [".__LINE__."]";
		
		
		$f[]=$nginx_certificate->GetConf();
		
		
		$f[]="\tssl_session_cache shared:SSL:50m;";
		$f[]="\tssl_session_timeout  5m;";
		$f[]="\tssl_protocols  $this->ssl_protocols;";
		$f[]="\tssl_ciphers '$this->ssl_ciphers';";
		$f[]="\tssl_prefer_server_ciphers   on;";
		return @implode("\n",$f);
	}
	
	private function MyHostnameListen(){
		$HostIPADDR=null;
		if(preg_match("#^unix:#", $this->servername)){
			$this->host_port=$this->servername;
			return null;
		}
		if(!isset($GLOBALS["ALL_IPS_GET_ARRAY"])){$IP=new networking();$GLOBALS["ALL_IPS_GET_ARRAY"]=$IP->ALL_IPS_GET_ARRAY();}
		
		
		if($this->host_ipaddr<>null){
			if(isset($GLOBALS["ALL_IPS_GET_ARRAY"][$this->host_ipaddr])){
				$HostIPADDR=$this->host_ipaddr.":";
			}
		}
		
		
		
		
		if($this->host_port>0){$HostPort=$this->host_port;}
		$this->HostPortLNK=$HostPort;
		if($this->HostPortLNK==443){$HostIPADDR=null;$this->HostPortLNK=null;}else{$this->HostPortLNK=":$this->HostPortLNK";}		
		if($this->HostPortLNK==80){$HostIPADDR=null;$this->HostPortLNK=null;}else{$this->HostPortLNK=":$this->HostPortLNK";}
		if(preg_match("#:([0-9]+)$#", $this->HostPortLNK,$re)){$this->HostPortLNK=":".$re[1];}
		
		return $HostIPADDR;
	}
	
	

	
	private function general_security(){
		$servername=$this->servername;
		$ZoneName=str_replace(".", "", $servername);
		$ZoneName=str_replace("-", "", $servername);
		$ZoneName=str_replace("_", "", $servername);
		$f=array();
		if($this->LimitCnx>0){$f[]="\tlimit_conn LimitCnx $this->LimitCnx;";}
		if($this->EnableHSTS==1){$f[]="\tadd_header Strict-Transport-Security \"max-age=31536000\";";}
		if($this->RestrictIFrames==1){$f[]="\tadd_header X-Frame-Options SAMEORIGIN;";}
		if($this->XSSBrowser==1){$f[]="\tadd_header X-XSS-Protection \"1; mode=block\";";}
		if($this->EnableCSP==1){$f[]=$this->header_csp();}
		
		if($this->LimitReqs>0){
			$f[]="\tlimit_req   zone=$ZoneName  burst=2  nodelay;";
		}
		if(count($f)>0){return @implode("\n", $f);}
		
	}
	
	
	private function WORDPRESS_CONFIG(){
		
		
		$f[]="\tset \$cache_uri \$request_uri;";
		
		$f[]="# POST requests and urls with a query string should always go to PHP ( http://codex.wordpress.org/Nginx )";
		$f[]="\tif (\$request_method = POST) {";
		$f[]="\tset \$cache_uri 'null cache';";
		$f[]="\t}";
		
		 
		$f[]="\tif (\$query_string != \"\") {";
		$f[]="\tset \$cache_uri 'null cache';";
		$f[]="\t}";
		
		
		
		
		
		$f[]="\tlocation / {";
		$f[]="\t\ttry_files \$uri \$uri/ /index.php?q=\$uri&\$args;";
		$f[]="\t}";
		
		$f[]="# Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac).";
		$f[]="# Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)";
		$f[]="\tlocation ~ /\. {";
		$f[]="\t\tdeny all;";
		$f[]="\t}";
		
		$f[]="";
		$f[]="\trewrite /wp-admin$ \$scheme://\$host\$uri/ permanent;";
		
		$f[]="";
		$f[]="# Directives to send expires headers and turn off 404 error logging.";
		$f[]="\tlocation ~* ^.+\.(ogg|ogv|svg|svgz|eot|otf|woff|mp4|ttf|rss|atom|jpg|jpeg|gif|png|ico|zip|tgz|gz|rar|bz2|doc|xls|exe|ppt|tar|mid|midi|wav|bmp|rtf)$ {";
		$f[]="\t\taccess_log off; log_not_found off; expires max;";
		$f[]="}";
		
		$f[]="# Don't cache uris containing the following segments";
		$f[]="\tif (\$request_uri ~* \"(/wp-admin/|/xmlrpc.php|/wp-(app|cron|login|register|mail).php|wp-.*.php|/feed/|index.php|wp-comments-popup.php|wp-links-opml.php|wp-locations.php|sitemap(_index)?.xml|[a-z0-9_-]+-sitemap([0-9]+)?.xml)\") {";
		$f[]="\t\tset \$cache_uri 'null cache';";
		$f[]="\t}   ";
		$f[]="";
		$f[]="# Don't use the cache for logged in users or recent commenters";
		$f[]="\tif (\$http_cookie ~* \"comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_logged_in\") {";
		$f[]="\t\tset \$cache_uri 'null cache';";
		$f[]="\t}";	

		
		$f[]="\tlocation = /favicon.ico {";
		$f[]="\t\tlog_not_found off;";
		$f[]="\t\taccess_log off;";
		$f[]="\t}";
		
		$f[]="\tlocation = /robots.txt {";
		$f[]="\t\tallow all;";
		$f[]="\t\tlog_not_found off;";
		$f[]="\t\taccess_log off;";
		$f[]="\t}";
		
		$f[]="\tlocation ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {";
		$f[]="\t\texpires max;";
		$f[]="\t\tlog_not_found off;";
		$f[]="\t}";
		return @implode("\n", $f);	
		
	}
	
	
	private function ROOT_WEBSERVER(){
		if($this->ssl==1){
			$f[]=$this->ROOT_WEBSERVER_SSL();
		}
		
		if($this->WORDPRESS){
			$f[]=$this->WORDPRESS_CONFIG();
		}
		
		$f[]=$this->php_fpm();
		$f[]=$this->http_locations($this->servername);
		return @implode("\n", $f);
	}
	
	
	private function QuoteStringSpaces($string){
		if(strpos($string," ")==0){return $string;}
		$string=str_replace("'", "\'", $string);
		return "'$string'";
	}
	
	private function replace_rules_single_domain(){
		if(!$this->IsSubstitutions()){return null;}
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("nginx_replace_www", "zorder")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `zorder` INT( 10 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `zorder` )");
			if(!$q->ok){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
				return null;
			}
		}
		
		if(!$q->FIELD_EXISTS("nginx_replace_www", "AsRegex")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `AsRegex` smallint( 1 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `AsRegex` )");
			if(!$q->ok){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
				return null;
			}
		}		
		
		
		$f=array();
		$sql="SELECT * FROM nginx_replace_www WHERE servername='$this->servername' ORDER BY zorder";
		$results=$q->QUERY_SQL($sql);
		
		
		
		if(!$q->ok){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
			return null;
		}
		
		if(@mysql_num_rows($results)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: no main replace rule\n";}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			$AsRegex=$ligne["AsRegex"];
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			
			
			$ligne["stringtosearch"]=$this->QuoteStringSpaces($ligne["stringtosearch"]);
				
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=$this->QuoteStringSpaces($ligne["replaceby"]);
			if(strpos(" {$ligne["tokens"]}", "r")==0){$ligne["tokens"]=$ligne["tokens"]."r";}
				

			$f[]="\t\tsubs_filter {$ligne["stringtosearch"]}  {$ligne["replaceby"]} {$ligne["tokens"]};";
			
		}
		
		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT replaceid FROM reverse_www WHERE servername='$this->servername'"));
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if(!is_numeric($ligne["replaceid"])){$ligne["replaceid"]=0;}
		if($ligne["replaceid"]>0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: no replace rule ({$ligne["replaceid"]})\n";}
			if($GLOBALS["VERBOSE"]){echo "SELECT replaceid FROM reverse_www WHERE servername='$this->servername'\n";}
			$a=$this->replace_rules_build($ligne["replaceid"]);
			if($a<>null){$f[]=$a;}
		}
		
		if(count($f)>0){
			return @implode("\n", $f);
		}
		 
		
	}
	
	private function PatternToRegex($pattern){
		
		if(isset($GLOBALS["PatternToRegex"][$pattern])){return $GLOBALS["PatternToRegex"][$pattern];}
		$pattern=trim($pattern);
		$patternSrc=$pattern;
		$pattern=str_replace("\\", "\\\\", $pattern);
		$pattern=str_replace(".", "\.", $pattern);

		$pattern=str_replace("/", "\/", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("+", "\+", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("=", "\=", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("|", "\|", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
		$pattern=str_replace("^", "\^", $pattern);
		
		$pattern=str_replace("\n", "\s+", $pattern);
		$pattern=str_replace("\t", "\s+", $pattern);
		$pattern=str_replace("  ", "\s+", $pattern);
		$pattern=str_replace("\s+\s+", "\s+", $pattern);
		$pattern=str_replace("\s+\s+", "\s+", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
	
		$GLOBALS["PatternToRegex"][$pattern]=$pattern;
		return $pattern;
	}
	
	private function replace_rules_build($id){
		if(!is_numeric($id)){return;}
		if($id==0){return;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM nginx_replace WHERE groupid='$id'");
		$t=array();
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Group id:$id ". mysql_num_rows($results)." rules\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$AsRegex=$ligne["AsRegex"];
			
		if(trim($ligne["stringtosearch"])==null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace rule id:{$ligne["ID"]} no search pattern\n";}
			return;}
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			$ligne["stringtosearch"]=str_replace("\"", '\"', $ligne["stringtosearch"]);
			
			
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=str_replace("\"", '\"', $ligne["replaceby"]);
			if($GLOBALS["VERBOSE"]){echo "\"{$ligne["stringtosearch"]}\"\n";}
			$t[]="\t\tsubs_filter \"{$ligne["stringtosearch"]}\" \"{$ligne["replaceby"]}\" gir;";
		}
		
		if(count($t)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Group id:$id no rule\n";}
			return null;}
		return @implode("\n", $t);		
	}
	
	private function replace_folder_rules($folderid){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Folder ID:$folderid [".__LINE__."]\n";}
		if(!is_numeric($folderid)){return;}
		if($folderid==0){return;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM nginx_replace_folder WHERE folderid='$folderid' ORDER BY zorder");
		$t=array();
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Folder id:$folderid ". mysql_num_rows($results)." rules\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$AsRegex=$ligne["AsRegex"];
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			$ligne["stringtosearch"]=str_replace("\"", '\"', $ligne["stringtosearch"]);
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=str_replace("\"", '\"', $ligne["replaceby"]);
			if($GLOBALS["VERBOSE"]){echo "\"{$ligne["stringtosearch"]}\"\n";}
			$ligne["tokens"]=str_replace("r", "", $ligne["tokens"]);
			$f[]="\t\tsubs_filter {$ligne["stringtosearch"]}  {$ligne["replaceby"]} {$ligne["tokens"]}r;";
		}
		
		
		return @implode("\n",$f);
		
	}
	
	
	
	private function ChekPool($ReverseProxyPattern){
		$q=new mysql_squid_builder();
		$http="http";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='$this->poolid'"));
		$upstream_name=$ligne["poolname"];
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Load-balancing: [$this->poolid] \"$upstream_name\"\n";}
		
		if(!isset($GLOBALS["upstream_available"][$upstream_name])){return $ReverseProxyPattern;}
		if($this->OWA){$http="https";}
		return "$http://$upstream_name";
		
	}
	
	private function proxy_cache_bypass(){
		$f[]="# cache_peer_id = $this->cache_peer_id; [".__LINE__."]";
		if($this->cache_peer_id>0){
				$q=new mysql_squid_builder();
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cacheid FROM reverse_sources WHERE ID='$this->cache_peer_id'"));
				$cacheid=intval($ligne["cacheid"]);
				if($cacheid>0){
				$this->proxy_buffering=1;
			}
			
		}
		
		if($this->proxy_buffering==0){
			$f[]="# Proxy buffering = 0; Force no caching [".__LINE__."]";
			$f[]="\t\tproxy_no_cache 1;";
			$f[]="\t\tproxy_cache_bypass 1;";
			return @implode("\n",$f);
		}
		
		$f[]="\t\tproxy_cache_use_stale  error timeout invalid_header updating http_500 http_502 http_503 http_504;";
		$f[]="\t\tproxy_cache_valid 200 302 {$this->proxy_cache_valid}m;";
		$f[]="\t\texpires {$this->proxy_cache_valid}m;";
		$f[]="\t\tproxy_cache_bypass \$cookie_nocache \$arg_nocache\$arg_comment ;";
		$f[]="\t\tproxy_no_cache \$http_pragma \$http_authorization;";
		return @implode("\n",$f);
	}
	
	private function headers_x_forwarded_for($force=false){
		$this->LoadParams();
		if($force){
			$f[]="\t\tproxy_set_header\tX-Forwarded-For\t\$proxy_add_x_forwarded_for;";
			$f[]="\t\tproxy_set_header\tX-Real-IP\t\$remote_addr;";
			return @implode("\n", $f);
		}
		if($this->x_forwarded_for==0){
			return "#\t\tx_forwarded_for set to 0 [".__LINE__."]";
			
		}
		if(trim($this->x_forwarded_for_string)==null){
			$f[]="\t\tproxy_set_header\tX-Forwarded-For\t\$proxy_add_x_forwarded_for;";
			$f[]="\t\tproxy_set_header\tX-Real-IP\t\$remote_addr;";
			return @implode("\n", $f);
		}
		$f[]="#\t\tFake x_forwarded_for [".__LINE__."]";
		$f[]="\t\tproxy_set_header\tX-Forwarded-For\t$this->x_forwarded_for_string;";
		$f[]="\t\tproxy_set_header\tX-Real-IP\t$this->x_forwarded_for_string;";
		return @implode("\n", $f);
	}	
	
	private function ROOT_WEBSERVER_SSL(){
		$sslconf=null;
		if($this->ssl==0){return "# SSL disabled [".__LINE__."]";}
		if($this->ssl_protocols==null){$this->ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";}
		if($this->ssl_ciphers==null){$this->ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}
		
		$f[]="# SSL ---------------------------------- [".__LINE__."]";
		$f[]="# ssl_client_certificate=$this->ssl_client_certificate [".__LINE__."]";
		$f[]="\tproxy_set_header X-Forwarded-Ssl on;";
		$f[]="\tssl on;";
		
		if(!isset($GLOBALS["SSL_RETURNED"])){
			$f[]="\tssl_session_timeout  5m;";
			$nginx_certificate=new nginx_certificate($this->sslcertificate,$this->ssl_client_certificate);
			$f[]=$nginx_certificate->GetConf();
			$f[]="\tssl_ciphers '$this->ssl_ciphers';";
			$f[]="\tssl_prefer_server_ciphers   on;";
			$f[]="\tssl_protocols  $this->ssl_protocols;";
		}

		
		
		
	
		return @implode("\n", $f);
	}
	
	
	
	private function ROOT_PROXY(){
		$IpClass=new IP();
		$HostHeader="\$host";
		$is_proxy_cookie_domain=false;
		$this->forceddomain=trim($this->forceddomain);
		if(!intval($this->proxy_to_port)==0){$this->proxy_to_port=80;}
		
		$f[]="# IsAFreeWeb....: $this->IsAFreeWeb [".__LINE__."]";
		$f[]="# Store ID......: $this->storeid [".__LINE__."]";
		$f[]="# Cache Peer id.: $this->cache_peer_id [".__LINE__."]";
		
		$PortTXT=null;
		if($this->forceddomain<>null){$HostHeader="$this->forceddomain";}
		if($this->default_server==1){
			if($this->forceddomain==null){
				$HostHeader="\$host";
				$this->forceddomain=$HostHeader;
			}
		}
		$proxy_to_host=$this->proxy_to_host;
		$proxy_ssl_session_reuse=null;
		$http_prefix=null;
		if(preg_match("#^(http|ftp|https|ftps|smtp|smtps):\/\/(.+)#", $proxy_to_host,$re)){
			$proxy_to_host=$re[2];
			$http_prefix=$re[1];
		}
		
		if($http_prefix==null){
			$http_prefix="http";
			if($this->proxy_to_port==80){$http_prefix="http";$this->proxy_to_port=null;}
			if($this->proxy_to_port==443){$http_prefix="https";$this->proxy_to_port=null;}
			if($this->proxy_scheme<>null){$http_prefix=$this->proxy_scheme;}
		}
		if($this->IsAFreeWeb){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername is a freeweb\n";}
			$this->proxy_to_host="127.0.0.1";
			$proxy_to_host=$this->proxy_to_host;
			$this->proxy_to_port=82;
			if($this->isFreeWebSSL()){
				$http_prefix="http";
				$proxy_ssl_session_reuse="off";
			}
		}
		
		if(preg_match("#([0-9\.]+):([0-9]+)#",$proxy_to_host,$re)){
			$proxy_to_host=$re[1];
			if(!$this->IsAFreeWeb){$this->proxy_to_port=$re[2]; }
		}
		
		
		if(!$IpClass->isValid($proxy_to_host)){
			$tcp=gethostbyname($proxy_to_host);
			if(!$IpClass->isValid($tcp)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $proxy_to_host -> 127.0.0.1 [".__LINE__."]\n";}
				$proxy_to_host="127.0.0.1";
				if($this->proxy_to_port==0){$this->proxy_to_port=82;}
				if($this->proxy_to_port==80){$this->proxy_to_port=82;}
				if($this->proxy_to_port==443){$this->proxy_to_port=447;}
			}
		}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port Proxy to: '$proxy_to_host' port:$this->proxy_to_port, suffix '$this->proxy_suffix'\n";} 
		$f[]="# proxy_to_port: $this->proxy_to_port [".__LINE__."]";
		if(is_numeric($this->proxy_to_port)){
			$PortTXT=":$this->proxy_to_port";
		}
		
		
		$this->proxy_suffix=str_replace("//", "/", $this->proxy_suffix);
		$f[]="# ReverseProxyPattern: $http_prefix $proxy_to_host $PortTXT $this->proxy_suffix [".__LINE__."]";
		$ReverseProxyPattern="{$http_prefix}://$proxy_to_host$PortTXT$this->proxy_suffix";
		
		
		
		if($this->poolid>0){
			$ReverseProxyPattern=$this->ChekPool($ReverseProxyPattern);
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port source id: $this->cache_peer_id\n";}
		
		
		if(substr($ReverseProxyPattern, strlen($ReverseProxyPattern)-1,1)=="/"){
			$ReverseProxyPattern=substr($ReverseProxyPattern, 0,strlen($ReverseProxyPattern)-1);
		}
		
		
		
		
		$f[]="\tproxy_cache_key \$host\$request_uri\$cookie_user;";
		$f[]="";
		if($this->OWA){	$f[]="\tproxy_hide_header \"Vary\";";}
		if($this->OWA){	$f[]="\tadd_header \"Vary\" \"Accept-Encoding\";";}
		if($this->OWA){	$f[]="\tproxy_redirect off;";}
		if($this->OWA){	$f[]="\tproxy_buffering off;";}
		if($this->OWA){	$f[]="\tproxy_read_timeout 3600s;";}
		if($this->OWA){	$f[]="\tproxy_pass_header Date;";}
		if($this->OWA){	$f[]="\tproxy_pass_header Server;";}
		if($this->OWA){	$f[]="\tproxy_set_header Connection \"\";";}
		if($this->OWA){	$f[]="\tproxy_set_header Accept-Encoding \"\";";}
		if($this->OWA){	$f[]="\tproxy_set_header X-Forwarded-Proto off;";}
		if($this->OWA){	$f[]="\tadd_header Front-End-Https on;";}
		if($this->OWA){	$f[]="\tproxy_http_version 1.1;";}
		
		$f[]="\t\tproxy_set_header Referer \$http_referer;";
		$f[]="\tproxy_set_header Host $HostHeader;";
		if($this->ngx_headers_more_module){
			$f[]="\tmore_set_headers    \"Server: $HostHeader\";";
		}else{
			$f[]="#\tmore_set_headers Not installed";
		}
		$f[]=$this->headers_x_forwarded_for();

		if(!$this->OWA){$f[]=$this->proxy_cache_bypass();}	
		if($proxy_ssl_session_reuse<>null){
			$f[]="\tproxy_ssl_session_reuse\t$proxy_ssl_session_reuse;";
		}

		

		
		
		$f[]="\tlocation /nginx_status {";
		$f[]="\t\tstub_status on;";
		$f[]="\t\taccess_log   off;";
		$f[]="\t\tallow 127.0.0.1;";
		$f[]="\t\tdeny all;";
		$f[]="\t\t}";
		
		
		
		if($this->OWA){
			//$f[]="\tproxy_set_header		Destination \$http_destination;";
			//$f[]="\tproxy_http_version 1.1;";
			$f[]="\tproxy_set_header Accept-Encoding \"\";";
			$f[]="\tlocation ~* ^/Autodiscover { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/exchange { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/ecp { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Rpc { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/owa {";
			$f[]="\t\tproxy_pass $ReverseProxyPattern;"; 
			$f[]="\t}";
			$f[]="\tlocation ~* ^/exchweb  { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/public   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Public   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/OAB   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/mailarchiver { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Microsoft-Server-ActiveSync { proxy_pass $ReverseProxyPattern; }";	
			$f[]="location ~*/$ {	return 301 https://$this->servername$this->HostPortLNK/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername$this->HostPortLNK%2fowa%2f; }";
			$f[]=$this->http_locations($this->servername);
			return @implode("\n", $f);
		}
		
		
		if($this->WORDPRESS){
			$nginx_wordpress=new nginx_wordpress($this->servername);
			$nginx_wordpress->ReverseProxyPattern=$ReverseProxyPattern;
			$f[]=$nginx_wordpress->Getconfig();		
			$f[]="\tproxy_pass_header Set-Cookie;";
			$f[]="# Catch the wordpress cookies.";
			$f[]="# Must be set to blank first for when they don't exist.";
			$f[]="\tset \$wordpress_auth \"\";";
			$f[]="\tif (\$http_cookie ~* \"wordpress_logged_in_*\") {";
			$f[]="\t\tset \$wordpress_auth wordpress_logged_in_$1;";
			$f[]="\t}";
			$f[]="";
		}
		
		$subs_filter_types=false;
		$f[]=$this->http_locations($this->servername);
		
		
// Racine du site Web ------------------------------------------------------------------------------	
		
		$f[]="\tlocation / {";
		$f[]=$this->limit_bandwitdth();
		$f[]=$this->error_pages_perso();
		$AUTHENTICATOR_BASE=$this->AUTHENTICATOR_BASE();
		if($AUTHENTICATOR_BASE<>null){$f[]=$AUTHENTICATOR_BASE;}
		$LDAP_require=$this->LDAP_require();
		if($LDAP_require<>null){$f[]=$LDAP_require;}
		
		$subsfilter_proxy_to_host=$proxy_to_host;
		
		if($this->forceddomain<>null){
				$f[]="# -- Forced domain = \"$this->forceddomain\" / is_proxy_cookie_domain = $is_proxy_cookie_domain [".__LINE__."]";
				$subsfilter_proxy_to_host=$this->forceddomain;
				if($is_proxy_cookie_domain){
					$f[]="\t\tproxy_cookie_domain $this->forceddomain \$host;";
				}
			
			
			
			if($this->IsSubstitutions()){
				$ToReplacewww=$this->servername;	
				if($this->default_server==1){$ToReplacewww="\$host";}
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				
				$subs_filter_types=true;
				$f[]="# -- IsSubstitutions $subsfilter_proxy_to_host -> $ToReplacewww [".__LINE__."]";
				
				
				$f[]="\t\tsubs_filter http:\/\/".$this->PatternToRegex($subsfilter_proxy_to_host)." \$scheme://$ToReplacewww gir;";
				$f[]="\t\tsubs_filter https:\/\/".$this->PatternToRegex($subsfilter_proxy_to_host)." \$scheme://$ToReplacewww gir;";
				$f[]="\t\tsubs_filter ".$this->PatternToRegex($subsfilter_proxy_to_host)." $ToReplacewww gir;";
				if(preg_match("#^www\.(.+)#", $this->proxy_to_host,$re)){
					$f[]="\t\tsubs_filter ".$this->PatternToRegex($re[1])." $ToReplacewww gir;";
				}
			}else{
				$f[]="# --- IsSubstitutions ".count($this->CompileParams["MODULES"])." modules RETURN FALSE [".__LINE__."]";
				
			}
		}else{
			$f[]="# -- Forced domain is not set, do not replace content in web pages [".__LINE__."]";
			$this->forceddomain="\$host";
		}
		
		
		
		$f[]=$this->proxy_cache_bypass();
		if($this->WORDPRESS){
			$f[]="\t\tproxy_cache_bypass \$wordpress_auth; # Do not cache the response.";
			$f[]="\t\tproxy_no_cache \$wordpress_auth; # Do not serve response from cache.";
		}
		
		$f[]="\t\tproxy_set_header Referer \$scheme://$this->forceddomain;";
		$f[]="\t\tproxy_set_header Host $this->forceddomain;";
		if($this->ngx_headers_more_module){
			$f[]="\t\tmore_set_headers    \"Server: $this->forceddomain\";";
		}else{
			$f[]="#\t\tmore_set_headers Not installed [".__LINE__."]";
		}
		$f[]=$this->headers_x_forwarded_for();
		//$f[]="\t\tproxy_cache_revalidate  on; 1.5.7";

		
		$replace_rules=$this->replace_rules_single_domain();
		
		
		if($replace_rules<>null){
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				$f[]=$replace_rules;
		}
		
		
// Creation des caches physiques	
		if($this->storeid>0){	
			$proxy_cache=$this->keys_zone($this->storeid);
		}else{
			$f[]="# Check store id from cache Peer id:$this->cache_peer_id [".__LINE__."]";
			$proxy_cache=$this->keys_zone_peer($this->cache_peer_id);
		}
		
		
		
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		$f[]=$this->proxy_parameters($this->cache_peer_id);
		
		if($this->proxy_pass_request_headers==1){
			$f[]="\t\tproxy_pass_request_headers on;\n";
		}else{
			$f[]="\t\tproxy_pass_request_headers off;\n";
		}
		
		if($this->cache_peer_id>0){
			$is_proxy_cookie_domain=$this->is_proxy_cookie_domain();
			$f[]="#\t\t proxy_pass($this->cache_peer_id)";
			$f[]=$this->proxy_pass($this->cache_peer_id);
			
		}		
		
		
		
		$f[]="\t}";
		
		$AUTHENTICATOR_PROXY=$this->AUTHENTICATOR_PROXY();
		if($AUTHENTICATOR_PROXY<>null){$f[]=$AUTHENTICATOR_PROXY;}
		$f[]="";
		
		return @implode("\n", $f);
		
	}
	
	private function spawn_fcgi_parameters(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
	}
	
	private function spawn_fcgi_debian_fix(){
		$unix=new unix();
		$aptget=$unix->find_program("apt-get");
		if(!is_file($aptget)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Operating system not supported\n";}
			return;
		}
		$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes install spawn-fcgi";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, installing spawn-fcgi...\n";}
		shell_exec($cmd);
		
	}
	
	
	private function spawn_fcgi(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		if(!is_file($spawn_fcgi)){
			$this->spawn_fcgi_debian_fix();
		}
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		if(!is_file($spawn_fcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, FATAL no spawn-php can be enabled !\n";}
			return false;
		}
		$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
		
		if(!is_file($phpcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, FATAL no php-cgi can be enabled !\n";}
			return false;			
		}
		
		
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		
		if(!$unix->process_exists($pid)){
			shell_exec("/etc/init.d/php5-fcgi start");
			sleep(1);
		}
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, PHP cgi spawned pid:$pid\n";}
			return true;
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, PHP cgi spawned failed\n";}
			
		}
		
		return true;
		
	}
	
	public function PHP_ENGINE($defaultfile=null){
		if($defaultfile==null){$defaultfile="index.php";}
		$this->IndexFile=$defaultfile;
		return $this->php_fpm();
	}
	
	
	private function spawn_fcgi_config($root=null){
		if(!$this->spawn_fcgi()){return null;}
		
		$this->fastcgi_params();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}	
		if(strpos($index, " ")>0){
			$indexR=explode(" ", $index);
			$index=$indexR[0];
		}
		
		$f[]="\tlocation ~ [^/]\.php(/|\$) {";
		$f[]=$this->php_perso_tokens();
		if($root<>null){$f[]="\t\troot\t$root;";}
		$f[]="\t\tindex\t$index;";
		$f[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
		
		$f[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
		$f[]="\t\t\treturn 404;";
		$f[]="\t\t}";
		$f[]=" ";
		$f[]="\t\tfastcgi_pass unix:/var/run/php-fcgi.sock;";
		$f[]="\t\tfastcgi_index $index;";
		$f[]="\t\tinclude fastcgi_params;";
		$f[]="\t}";
		
		return @implode("\n", $f);		

		
		
	}
	function php_perso_tokens(){
		$q=new mysql();
		$array=array();
		$sql="SELECT *  FROM freeweb_php WHERE servername='$this->servername'";
		$results=$q->QUERY_SQL($sql,'artica_backup');
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["key"]]=$ligne["value"];
		}
		while (list ($key, $val) = each ($array) ){
			$f[]="set \$php_value \"$key $val\";";
		}
		
		$line=@implode("\n", $f);
		return $line;
		
	
	
	}
	private function php5_params($root){
		$unix=new unix();
		$sock= new sockets();
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		$EnableArticaApachePHPFPM=intval($sock->GET_INFO("EnableArticaApachePHPFPM"));
		$EnablePHPFPMFreeWeb=intval($sock->GET_INFO("EnablePHPFPMFreeWeb"));
		if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
		if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$EnablePHPFPMFreeWeb=1;}
		
		
		if($EnableArticaApachePHPFPM==1){
				$socket="/var/run/php-fpm.sock";
		}
		
		if($EnablePHPFPMFreeWeb==1){
			$socket="/var/run/php-fpm-apache2.sock";
		}
		
		
		if($EnablePHPFPM==1){
			return "\t\troot $root;\n\t\tfastcgi_pass unix:$socket;\n\t\tinclude fastcgi_params;\n";
		}
		
		return "\t\troot $root;\n\t\tfastcgi_pass unix:/var/run/php-fcgi.sock;\n\t\tinclude fastcgi_params;\n";
	}
	

	
	public function php_fpm($Forceindex=null,$root=null,$Disable_phpfpm=0){
		$unix=new unix();
		$sock=new sockets();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}
		if($Forceindex<>null){$index=$Forceindex;}
		
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		
		if($Disable_phpfpm==1){
			$results=$this->spawn_fcgi_config($root);
			if($results<>null){return $results;}
		}
		
		if($EnablePHPFPM==0){return $this->spawn_fcgi_config();}
		$this->fastcgi_params();
		$EnableArticaApachePHPFPM=intval($sock->GET_INFO("EnableArticaApachePHPFPM"));
		$EnablePHPFPMFreeWeb=intval($sock->GET_INFO("EnablePHPFPMFreeWeb"));
		if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
		if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$EnablePHPFPMFreeWeb=1;}
		
		
		if($EnableArticaApachePHPFPM==1){$socket="/var/run/php-fpm.sock"; }
		if($EnablePHPFPMFreeWeb==1){$socket="/var/run/php-fpm-apache2.sock"; }
		if($this->BuildFramework){$socket="/var/run/php-fpm-framework.sock";}
		
		if(strpos($index, " ")>0){
			$indexR=explode(" ", $index);
			$index=$indexR[0];
		}
		
			$f[]="\tlocation ~ [^/]\.php(/|\$) {";
			$f[]="\t\tindex\t$index;";
			if($root<>null){$f[]="\t\troot\t$root;";}
			$f[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
			$f[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
			$f[]="\t\t\treturn 404;";
			$f[]="\t\t}";
			$f[]=" ";
			$f[]="\t\tfastcgi_buffers 8 16k;";
			$f[]="\t\tfastcgi_buffer_size 32k;";
			$f[]="\t\tfastcgi_read_timeout 300;";
			$f[]="\t\tfastcgi_connect_timeout 300;";
			$f[]="\t\tfastcgi_send_timeout 300;";
			$f[]="\t\tfastcgi_pass unix:$socket;";
			$f[]="\t\tfastcgi_index $index;";
			$f[]="\t\tinclude fastcgi_params;";
			$f[]="\t}";	

		return @implode("\n", $f);
		
	}
	
	private function fastcgi_params(){
		$f[]="fastcgi_param   QUERY_STRING            \$query_string;";
		$f[]="fastcgi_param   REQUEST_METHOD          \$request_method;";
		$f[]="fastcgi_param   CONTENT_TYPE            \$content_type;";
		$f[]="fastcgi_param   CONTENT_LENGTH          \$content_length;";
		$f[]=" ";
		$f[]="fastcgi_param   SCRIPT_FILENAME         \$document_root\$fastcgi_script_name;";
		$f[]="fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;";
		$f[]="fastcgi_param   PATH_INFO               \$fastcgi_path_info;";
		$f[]="fastcgi_param   REQUEST_URI             \$request_uri;";
		$f[]="fastcgi_param   DOCUMENT_URI            \$document_uri;";
		$f[]="fastcgi_param   DOCUMENT_ROOT           \$document_root;";
		$f[]="fastcgi_param   SERVER_PROTOCOL         \$server_protocol;";
		$f[]=" ";
		$f[]="fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
		$f[]="fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;";
		$f[]=" ";
		$f[]="fastcgi_param   REMOTE_ADDR             \$remote_addr;";
		$f[]="fastcgi_param   REMOTE_PORT             \$remote_port;";
		$f[]="fastcgi_param   SERVER_ADDR             \$server_addr;";
		$f[]="fastcgi_param   SERVER_PORT             \$server_port;";
		$f[]="fastcgi_param   SERVER_NAME             \$server_name;";
		$f[]=" ";
		$f[]="fastcgi_param   HTTPS                   \$server_https;";
		$f[]=" ";
		$f[]="# PHP only, required if PHP was built with --enable-force-cgi-redirect";
		$f[]="fastcgi_param   REDIRECT_STATUS         200;";
		@file_put_contents("/etc/nginx/fastcgi_params", @implode("\n", $f));
		$f=array();
		$f[]="fastcgi_split_path_info ^(.+\.php)(.*)$;";
		$f[]="fastcgi_param PATH_TRANSLATED \$document_root\$fastcgi_path_info;";
		@file_put_contents("/etc/nginx/fastcgi_params2", @implode("\n", $f));		
	}
	
	
	
}

class nginx_upstream{
	public $upstream_available=array();
	
	public function build(){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_pools ORDER BY poolname";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Load-balancing [{$ligne["poolname"]}]\n";}
			$upstream_name=$ligne["poolname"];
			$keepaliveTXT=null;
			$poolid=$ligne["ID"];
			if(!is_numeric($ligne["keepalive"])){$ligne["keepalive"]=0;}
			$data=$this->upstreams_server($poolid,$ligne["hashtype"]);
			if($data==null){continue;}
			$this->upstream_available[$upstream_name]=true;
			$GLOBALS["upstream_available"][$upstream_name]=true;
			if($ligne["keepalive"]>0){$keepaliveTXT=" keepalive {$ligne["keepalive"]}";}
			$f[]="upstream \"$upstream_name\" {";
			$f[]=$data;
			if($ligne["hashtype"]<>null){$f[]="{$ligne["hashtype"]}";}
			if($keepaliveTXT<>null){$f[]="$keepaliveTXT";}
			$f[]="}";
			
			
		}
		
		if(count($f)>0){return @implode("\n", $f);}
		
	}
	
	private function buildsource($sourceid){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$sourceid'"));
		if($ligne["enabled"]==0){return;}
		
		$port=$ligne["port"];
		$servername=$ligne["servername"];
		$ipaddr=$ligne["ipaddr"];
		$ssl=$ligne["ssl"];
		if(!is_numeric($port)){$port=0;}
		if($port==0){if($ssl==1){$port=443;}}
		
		if($port>0){return "$ipaddr:$port";}
		return $ipaddr;
		
	}
	
	private function upstreams_server($poolid,$hashtype){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_pools_list WHERE poolid=$poolid ORDER BY zorder DESC";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}

		$Weight=0;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$sourceid=$ligne["sourceid"];
			$lineServer=$this->buildsource($sourceid);
			if($lineServer==null){continue;}
			$Weight++;
			$max_fails=$ligne["max_fails"];
			$fail_timeout=$ligne["fail_timeout"];
			$max_failsTXT=null;
			$fail_timeoutTXT=null;
			$WeightTXT=null;
			if(($hashtype==null) OR ($hashtype=="least_conn")){
				$WeightTXT=" weight=$Weight";
				if($max_fails>0){$max_failsTXT=" max_fails=$max_fails";$WeightTXT=null;}
				if($fail_timeout>0){$fail_timeoutTXT=" fail_timeout={$fail_timeout}s";$WeightTXT=null;}
				$f[]="\tserver $lineServer$WeightTXT$max_failsTXT$fail_timeoutTXT;";
			}
			
			if($hashtype=="ip_hash"){
				$f[]="\tserver $lineServer {$ligne["backuptype"]};";
			}
			
		}

		if(count($f)>0){
			return @implode("\n", $f);
		}
	
	}
	


}
