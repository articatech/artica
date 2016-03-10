<?php
class HTTrackInSquid{
	
	private $uri=null;
	private $szLine=null;
	private $DenyIfNotExists=0;
	private $WindowsUpdatePartitionExceed=false;
	
	public function HTTrackInSquid($uri,$szLine){
		$this->uri=$uri;
		$this->szLine=$szLine;
		$GLOBALS["DEBUG_OUTPUT"]=true;
		
		if(isset($GLOBALS["LOG_AR"]["IP"])){
			$this->szLine="Requested by {$GLOBALS["LOG_AR"]["IP"]}/{$GLOBALS["LOG_AR"]["userid"]}";
		}
		
		
		
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	}
	
	public function MakeRedirect(){
		return $this->isALocalSite();
	}
	

	
		
	
	private function LoadHTTrackDb(){
		
		if(!is_file("/etc/squid3/HTTrack.db")){return array();}
		if(isset($GLOBALS["LoadHTTrackDb"]["FFSIZE"])){
			$size=@filesize("/etc/squid3/HTTrack.db");
			if($size==$GLOBALS["LoadHTTrackDb"]["FFSIZE"]){return $GLOBALS["HTTrack"];}
		}
		$size=@filesize("/etc/squid3/HTTrack.db");
		$this->debug("Loading: /etc/squid3/HTTrack.db $size/{$GLOBALS["LoadHTTrackDb"]["FFSIZE"]}", __LINE__);
		
		$GLOBALS["HTTrack"]=unserialize(@file_get_contents("/etc/squid3/HTTrack.db"));
		$GLOBALS["LoadHTTrackDb"]["FFSIZE"]=$size;
		return $GLOBALS["HTTrack"];
		
	}
	
		
	private function isALocalSite(){
		$ipsrc=trim($GLOBALS["LOG_AR"]["IP"]);
		$BaseName=null;
		
		$HTTrack=$this->LoadHTTrackDb();
		$prefix_channel=$this->PREFIX_CHANNEL();
		
		$this->debug("Loading: Scanning ". count($HTTrack)." rules...",__LINE__);
		
		while (list($pattern,$alias)=each($HTTrack)){
			$this->debug("INFO: Check:[$ipsrc] \"$pattern\" for $this->uri", __LINE__);
			if(preg_match("#$pattern#", $this->uri)){
				
				$parse_url=parse_url($this->uri);
				if(isset($parse_url["path"])){
					$path=$parse_url["path"];
				}
				
				$fulluri="http://127.0.0.1/$alias{$path}";
				$LineTOSend="{$prefix_channel}OK rewrite-url=\"$fulluri\"";
				$this->debug("Output: $LineTOSend", __LINE__);
				print("$LineTOSend\n");
				return true;
			}
			
		}
	}
	
	private function file_ext($path){
		$path_info = pathinfo($path);
		return $path_info['extension'];
	
	}
	

	
	private function SendDeny(){
		if($this->WindowsUpdatePartitionExceed){return false;}
		$prefix_channel=$this->PREFIX_CHANNEL();
		$LineTOSend="{$prefix_channel}OK rewrite-url=\"$fulluri\"";
		$this->debug("Output: $LineTOSend", __LINE__);
		print("$LineTOSend\n");
		return true;
	}
	
	
	private function PREFIX_CHANNEL(){
		if(!is_numeric($GLOBALS["CHANNEL"])){return null;}
		if($GLOBALS["CHANNEL"]==0){return null;}
		return "{$GLOBALS["CHANNEL"]} ";
		
	}
	
	


private function debug($text,$line){
	if(!isset($GLOBALS["DEBUG_HTTRACK"])){$GLOBALS["DEBUG_HTTRACK"]=false;}
	if(!$GLOBALS["DEBUG_HTTRACK"]){return;}
	if(function_exists("events")){events("$text in $line");}
	$this->events($text, $line);
	
}
		
	private function events($text,$line){
		$date=@date("H:i:s");
		$logFile="/var/log/squid/HTTrack.debug";
		$time_end=tool_microtime_float();
		$tt = round($time_end - $GLOBALS["time_loop_start"],3);
		$size=@filesize($logFile);
		if($size>9000000){@unlink($logFile);}
		$line="$date:[Realtime/$line]:[{$GLOBALS["MYPID"]}]: $text";
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$line\n");
		@fclose($f);
	
	
	}
}