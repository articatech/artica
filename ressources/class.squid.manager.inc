<?php


class cache_manager{
	private $fp;
	public $errno;
	public $errstr;
	public $ok=false;
	public $TimeOutsec=3;
	public $errstr_plus=null;
	public $URL_SENDED;
	function cache_manager(){
		
		
	}
	
	
	private function connect(){
		$this->fp = false;
		$this->ok=true;
		$this->errstr_plus=null;
		$AP=$this->squid_get_internal_addrport();
		if($GLOBALS["VERBOSE"]){echo "Connect {$AP[0]}:{$AP[1]}\n";}
		
		
		$this->fp = @stream_socket_client("tcp://{$AP[0]}:{$AP[1]}",$this->errno, $this->errstr,$this->TimeOutsec, STREAM_CLIENT_CONNECT);
		
		
		if(!$this->fp){
			$this->ok=false;
			return false;
		}
		
		if (!is_resource($this->fp)) {
			$this->ok=false;
			return false;
		
		}
		
		if($GLOBALS["VERBOSE"]){echo "success to connect {$AP[0]}:{$AP[1]}\n";}
		return true;
	}
	
	private function GET_INFO($key){
		$value=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
		return $value;
	
	}
	private function squid_get_alternate_port(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
			if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
				
			if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
		}
	
	}
	
	
	public function makeQuery($query,$rawZ=false){
		if(!$this->fp){if(!$this->connect()){return null;}}
		$this->errno=0;
		$this->URL_SENDED="cache_object://localhost/$query";
		$out = "GET cache_object://localhost/$query HTTP/1.0\r\n\r\n";
		if($GLOBALS["VERBOSE"]){echo "Send $out\n";}
	
		stream_set_timeout($this->fp,$this->TimeOutsec);
		fwrite($this->fp, $out);
		$info = stream_get_meta_data($this->fp);
		
		if ($info['timed_out']) {
			$this->errno=1000;
			$this->errstr="Timed Out";
			@socket_close($this->fp);
			@fclose($this->fp);
			return false;
		}
		
		stream_set_blocking($this->fp, TRUE);
		stream_set_timeout($this->fp,$this->TimeOutsec);
		
		 while ((!feof($this->fp)) && (!$info['timed_out'])) { 
		 	$lineZ=trim(fgets($this->fp, 2048));
		 	if($GLOBALS["VERBOSE"]){echo "--> \"$lineZ\"\n";}
		 	if(preg_match("#X-Squid-Error:\s+ERR_ICAP_FAILURE#", $lineZ)){
		 		$this->errno=3;
		 		$this->errstr="ERR_ICAP_FAILURE";
		 		$this->ok=false;
		 		@socket_close($this->fp);
		 		@fclose($this->fp);
		 		return false;
		 	}
		 	if(preg_match("#ACCESS_DENIED#", $lineZ)){
		 		$this->errno=4;
		 		$this->errstr="Access denied";
		 		$this->ok=false;
		 		@socket_close($this->fp);
		 		@fclose($this->fp);
		 		return false;
		 	}
		 	
		 	if(preg_match("#INVALID_URL#", $lineZ)){
		 		$this->errno=5;
		 		$this->errstr="Invalid url";
		 		$this->ok=false;
		 		@socket_close($this->fp);
		 		@fclose($this->fp);
		 		return false;
		 	}
		 	
		 	if(preg_match("#ERR_PARANOID#", $lineZ)){
		 		$this->errno=6;
		 		$this->errstr="Paranoid mode";
		 		$this->ok=false;
		 		@socket_close($this->fp);
		 		@fclose($this->fp);
		 		return false;
		 	}
		 	
		 	
		 	
		 	if(preg_match("#X-Squid-Error:\s+ERR_([A-Z\_]+)#", $lineZ,$re)){
		 		if($re[1]=="CACHE_ACCESS_DENIED"){
		 			$this->errno=4;
		 			$this->errstr="Access denied";
		 			$this->ok=false;
		 			@socket_close($this->fp);
		 			@fclose($this->fp);
		 			return false;
		 		}
		 		
		 		$this->errstr_plus="ERR_".$re[1];
		 		
		 	}
		 	
			$raw[]=$lineZ;
			$info = stream_get_meta_data($this->fp);
		}
		if ($info['timed_out']) {
			$this->errno=1000;
			$this->errstr="Timed Out";
			$this->ok=false;
			@socket_close($this->fp);
			@fclose($this->fp);
			return false;
		}
		
		
		@socket_close($this->fp);
		@fclose($this->fp);
		
		if(!preg_match("#HTTP.*?200\s+OK#i", $raw[0])){
			if($GLOBALS["VERBOSE"]){echo "Failed \"{$raw[0]}\"\n";}
			$this->errno=1;
			$this->errstr="Cannot get data. Server answered: $raw[0] {$this->errstr_plus}";
			$this->ok=false;
			return false;
		}
		$this->ok=true;
		if($GLOBALS["VERBOSE"]){echo "Success ".count($raw)." line(s)\n";}
		if($rawZ){return $raw;}else{return @implode("\n", $raw);}
		
	}
	
	
	public function active_requests(){
		$f=$this->makeQuery("active_requests",true);
		if(!$this->ok){return;}
		while (list ($mkey, $ligne) = each ($f) ){
		
			if(preg_match("#Connection:\s+(.+)#", $ligne,$re)){
				$CON=$re[1];
				if($CON=="close"){continue;}
				$H["CON"][$re[1]]=true;
				continue;
			}
		
			if(preg_match("#remote:\s+([0-9\.]+):#", $ligne,$re)){
				$H[$CON]["IPS"][$re[1]]=true;
				$H["connections"][$CON]["IPS"]=$re[1];
				$H["IPS"][$re[1]]=true;
				$H["ALL"][$re[1]]=true;
				continue;
			}
		
			if(preg_match("#username\s+(.+)#", $ligne,$re)){
				$H[$CON]["USERS"][$re[1]]=true;
				$H["connections"][$CON]["USERS"]=$re[1];
				$H["USERS"][$re[1]]=true;
				$H["ALL"][$re[1]]=true;
				continue;
			}
			
			if(preg_match("#uri\s+(.+)#",$ligne,$matches)){
				$H["connections"][$CON]["uri"]=$matches[1];
				$H[$CON]["uri"]=$matches[1];
			}
			
			if(preg_match('/out.offset \d+, out.size (\d+)/',$ligne,$matches)){
				$H["connections"][$CON]["bytes"]=$matches[1];
				$H[$CON]["bytes"]=$matches[1];
			}
			if(preg_match('/start \d+\.\d+ \((\d+).\d+ seconds ago\)/',$ligne,$matches)){
				$H["connections"][$CON]["seconds"]=$matches[1];
				$H[$CON]["seconds"]=$matches[1];
			}
		
		}
		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc", serialize($H));
		@chmod("/usr/share/artica-postfix/ressources/logs/active_requests.inc", 0755);
		return $H;
		
	}

	
	
	
	
	
	private function squid_get_internal_addrport(){
		$SquidMgrListenPort=trim($this->GET_INFO("SquidMgrListenPort"));
		if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
			$SquidBinIpaddr=$this->GET_INFO("SquidBinIpaddr");
			if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$this->squid_get_alternate_port();
	
			if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
				$SquidBinIpaddr=$re[1];
				if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
				$http_port=$re[2];
			}
	
		}else{
			$SquidBinIpaddr="127.0.0.1";
			$http_port=$SquidMgrListenPort;
		}
	
		return array($SquidBinIpaddr,$http_port);
	}	
	
	
}