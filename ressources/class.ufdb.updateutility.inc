<?php
class UfdbUpdateUtility{
	
	private $uri=null;
	private $szLine=null;
	private $DenyIfNotExists=0;
	private $WindowsUpdatePartitionExceed=false;
	
	public function UfdbWindowsCache($uri,$szLine){
		$this->uri=$uri;
		$this->szLine=$szLine;
		
		
		if(isset($GLOBALS["LOG_AR"]["IP"])){
			$this->szLine="Requested by {$GLOBALS["LOG_AR"]["IP"]}/{$GLOBALS["LOG_AR"]["userid"]}";
		}
		
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	}
	
	public function MakeRedirect(){
		if(!$this->isHooked()){return false;}
		$this->RedirectFinal();
		return true;
		
	}
	
	private function isHooked(){
		$ipsrc=trim($GLOBALS["LOG_AR"]["IP"]);
		$BaseName=null;
			
		if(strpos($this->uri, ".geo.kaspersky.com")>0){
			$this->debug("$this->szLine OK");
			return true;
		}
		
	}
	
	private function file_ext($path){
		$path_info = pathinfo($path);
		return $path_info['extension'];
	
	}
	
	private function RedirectFinal(){
		$prefix_channel=null;
		$parse_url=parse_url($this->uri);
		$sitename=$parse_url["host"];
		$Path=$parse_url["path"];
		$filename=basename($Path);
		$allowed_ext["cab"]=true;
		$allowed_ext["psf"]=true;
		$allowed_ext["exe"]=true;
		$ext=$this->file_ext($filename);
		
		$fulluri="http://127.0.0.1/UpdateUtilityStorePath/database/Updates/$Path";
		$prefix_channel=$this->PREFIX_CHANNEL();
		$LineTOSend="{$prefix_channel}OK rewrite-url=\"$fulluri\"";
		$this->events("REDIRECT: $LineTOSend", __LINE__);
		print("$LineTOSend\n");
		return true;
	}
	
	private function SendDeny(){
		if($this->WindowsUpdatePartitionExceed){return false;}
		$prefix_channel=$this->PREFIX_CHANNEL();
		$LineTOSend="{$prefix_channel}OK url=\"http://127.0.0.1/".time().".nothing\"";
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
		if(!isset($GLOBALS["DEBUG_OUTPUT"])){$GLOBALS["DEBUG_OUTPUT"]=false;}
		if($GLOBALS["DEBUG_OUTPUT"]){
			$this->events($text, $line);
		}
	}
		
	private function events($text,$line){
		$date=@date("H:i:s");
		$logFile="/var/log/squid/UpdateUtility.debug";
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