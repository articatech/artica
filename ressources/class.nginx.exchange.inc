<?php

class nginx_exchange{
	private $servername=null;
	private $listen_port=0;
	private $zOrder=0;
	private $MAJOR;
	private $MINOR;
	private $REV;
	private $PATCH;
	private $DenyConf=0;
	private $ConfBuilded=null;
	
	function nginx_exchange($servername){
		$this->nginx_version();
		$this->servername=$servername;
		$this->LoadSettings();
		
	}
	
	private function nginx_version(){
		$unix=new unix();
		$version=$unix->nginx_version();
		
		if(!preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$version,$re)){return;}
		$this->MAJOR=intval($re[1]);
		$this->MINOR=intval($re[2]);
		$this->REV=intval($re[3]);
		$this->PATCH=intval($re[4]);
	}
	
	private function proxy_request_buffering($value){
		if($this->MAJOR<1){return;}
		if($this->MAJOR<7){return;}
		if($this->REV<11){return;}
		return "\tproxy_request_buffering $value;";
	}
	
	
	
	
	
	function server_name(){
		$s=array();
		$IPClass=new IP();
		if(!$IPClass->isIPAddress($this->servername)){
			$s[]=$this->servername;
		}
		$sql="SELECT *  FROM `nginx_aliases` WHERE servername='$this->servername'";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql,"artica_events");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$alias=trim($ligne["alias"]);
			if($alias==null){continue;}
			$s[]=$alias;
		}
		
		if(count($s)==0){$s[]=$this->servername;;}
				
		return @implode(" ", $s);
	}
	
	function LoadSettings(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		$this->listen_port=intval($ligne["port"]);
		$this->zOrder=intval($ligne["zOrder"]);
		if($this->listen_port==0){$this->listen_port=443;}
		if($this->listen_port==80){$this->listen_port=443;}
		if(trim($ligne["ipaddr"])<>null){
			$this->listen_port="{$ligne["ipaddr"]}:$this->listen_port";
		}
		$this->DenyConf=intval($ligne["DenyConf"]);
		$this->ConfBuilded=$ligne["ConfBuilded"];
	}
	
	function get_certificate(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cache_peer_id FROM reverse_www WHERE servername='$this->servername'"));
		$cache_peer_id=intval($ligne["cache_peer_id"]);
		if($cache_peer_id==0){return null;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT certificate FROM reverse_sources WHERE ID='$cache_peer_id'"));
		return $ligne["certificate"];
		
	}
	
	function GetDestination(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cache_peer_id FROM reverse_www WHERE servername='$this->servername'"));
		$cache_peer_id=intval($ligne["cache_peer_id"]);
		if(!$q->ok){echo $q->mysql_error."\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $this->servername, Peer ID:$cache_peer_id\n";}
		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port FROM reverse_sources WHERE ID='$cache_peer_id'"));
		$ipaddr=trim($ligne["ipaddr"]);
		$port=intval($ligne["port"]);
		if($port==0){$port=443;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $this->servername, Peer ID:$cache_peer_id / $ipaddr:$port\n";}
		if($ipaddr==null){$ipaddr="127.0.0.1";}
		$array[0]="https://$ipaddr:$port";
		$array[1]="$ipaddr";
		$array[2]=gethostbyaddr($ipaddr);
		return $array;
	}
	
	
	function substitutions($destinationZ){
		$nginx_tools=new nginx_tools();
		$IsSubstitutions=$nginx_tools->IsSubstitutions();
		if(!$IsSubstitutions){return null;}
		$f[]="\tsubs_filter ". str_replace(".", "\.", $destinationZ[1])." $this->servername gir;";
		$f[]="\tsubs_filter ". str_replace(".", "\.", $destinationZ[2])." $this->servername gir;";
		return @implode("\n", $f);
	}
	
	
	function limitecp(){
		$f=array();
		$sql="SELECT * FROM nginx_exchecp WHERE `hostname`='{$this->servername}'";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]="\tallow {$ligne["ipsrc"]};";
		}
		if(count($f)>0){$f[]="\tdeny all;";}
		if(count($f)>0){return @implode("\n", $f);}
	}
	
	function buildConfig(){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $this->servername, buildConfig();\n";}
		$file_settings_path="/etc/nginx/sites-enabled/$this->zOrder-freewebs-ssl-$this->servername.443.conf";
		
		if($this->DenyConf==1){
			@file_put_contents($file_settings_path, $this->ConfBuilded."\n");
			return;
			
		}
		
		$server_name=$this->server_name();
		$certificate=$this->get_certificate();
		$destinationZ=$this->GetDestination();
		$substitutions=$this->substitutions($destinationZ);
		$destination=$destinationZ[0];
		$nginx_tools=new nginx_tools($this->servername);
		$AntiExploits=$nginx_tools->AntiExploits();
		
		
		$ErrorLogLevel="crit";
		$f[]="# nginx version $this->MAJOR,$this->MINOR,$this->REV,$this->PATCH";
		
		$f[]="server {";
		$f[]="\tserver_name $server_name";
		$f[]="\tserver_name_in_redirect off;";
		$f[]="\tlisten 80;";
		$f[]="\tlocation / {";
		$f[]="\t\treturn 301 \"https://$this->servername:$this->listen_port/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername:$this->listen_port%2fowa%2f\";";
		$f[]="\t}";
		$f[]="}";
		$f[]="";
		
		
		$f[]="server {";
		$f[]="\tserver_name $server_name";
		$f[]="\tserver_name_in_redirect off;";
		$f[]="\tlisten $this->listen_port ssl;";
		$f[]="\tclient_max_body_size 2G;";
		$f[]="\tproxy_set_header	X-Forwarded-For	\$proxy_add_x_forwarded_for;";
		$nginx_certificate=new nginx_certificate($certificate);
		$f[]=$nginx_certificate->GetConf();
		$f[]="\tssl_protocols TLSv1 TLSv1.1 TLSv1.2;";	
		$f[]="\taccess_log  /var/log/apache2/common-access.log awc_log;";
		$f[]="\terror_log  /var/log/apache2/nginx.error.log $ErrorLogLevel;";
		$f[]="";
		
		$enforceFolders[]="autodiscover";
		$enforceFolders[]="ecp";
		$enforceFolders[]="ews";
		$enforceFolders[]="exchange";
		$enforceFolders[]="exchweb";
		$enforceFolders[]="Microsoft-Server-ActiveSync";
		$enforceFolders[]="mapi";
		$enforceFolders[]="oab";
		$enforceFolders[]="owa";
		$enforceFolders[]="public";
		$enforceFolders[]="rpc";
		$enforceFolders[]="rpcwithcert";
		
		$enforceFoldersPattern=@implode("|", $enforceFolders);
		$hostname_regex=str_replace(".", "\.",$this->servername);
		
		$f[]="if (\$request_uri !~ \"\/($enforceFoldersPattern)\/\"){";
			$f[]="\treturn 301 \"https://$this->servername:$this->listen_port/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername:$this->listen_port%2fowa%2f\";";
		$f[]="}";
		
		$f[]="";
		
		
		$f[]="location = / {";
		$f[]="\treturn 301 \"https://$this->servername:$this->listen_port/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername:$this->listen_port%2fowa%2f\";";
		$f[]="}";
		$f[]="location = /favicon.ico {";
		$f[]="\tempty_gif;";
		$f[]="\taccess_log off;";
		$f[]="}";
		$f[]="location / {";
		if($AntiExploits<>null){$f[]=$AntiExploits;}
		$f[]=$substitutions;
		$f[]="\tadd_header Front-End-Https on;";
		$f[]="\tproxy_pass $destination;";
		$f[]="\tproxy_http_version 1.1;";
		$f[]="\tproxy_set_header Connection \"\";";
		$f[]="}";
		
		$f[]="";
		$f[]="location /ecp {";
		$f[]=$substitutions;
		if($AntiExploits<>null){$f[]=$AntiExploits;}
		$f[]=$this->limitecp();
		$f[]="\tadd_header Front-End-Https on;";
		$f[]="\tproxy_pass $destination;";
		$f[]="\tproxy_http_version 1.1;";
		$f[]="\tproxy_set_header Connection \"\";";
		$f[]="}";
		
		while (list ($index, $subdir) = each ($enforceFolders) ){
			if($subdir=="ecp"){continue;}
			if($subdir=="rpc"){continue;}
			
			$f[]="location /$subdir {";
			$f[]=$substitutions;
			if($AntiExploits<>null){$f[]=$AntiExploits;}
			$f[]="\tadd_header Front-End-Https on;";
			$f[]="\tproxy_pass $destination;";
			$f[]="\tproxy_http_version 1.1;";
			$f[]="\tproxy_set_header Connection \"\";";
			$f[]="}";
			
		}
		
	
		$f[]="";
		$f[]="location /rpc/rpcproxy.dll {";
		if($AntiExploits<>null){$f[]=$AntiExploits;}
		$f[]="\tproxy_pass $destination;";
		$f[]="\tproxy_buffering off;";
		$f[]="\tproxy_http_version 1.1;";
		$f[]=$this->proxy_request_buffering("off");
		$f[]="\tproxy_set_header Connection \"Keep-Alive\";";
		$f[]="}";
		$f[]="";
		$f[]="}";
		
		$data=@implode("\n", $f);
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www", "ConfBuilded")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `ConfBuilded` TEXT");if(!$q->ok){echo $q->mysql_error_html();}}
		$q->QUERY_SQL("UPDATE reverse_www SET `ConfBuilded`='".mysql_escape_string2($data)."' WHERE `servername`='$this->servername'");
		
		
		@file_put_contents($file_settings_path, $data."\n");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $file_settings_path\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Microsoft Exchange server configuration done\n";}
		
	}
	
	
	
	
}