<?php
class UfdbWindowsCache{
	
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
		
		if(!$this->isAWindowsUpdate()){return false;}
		if(!isset($GLOBALS["WindowsUpdateCaching"])){
			$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
			if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
		}
		
		if(!isset($GLOBALS["WindowsUpdateDenyIfNotExists"])){
			$GLOBALS["WindowsUpdateDenyIfNotExists"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateDenyIfNotExists"));
			
		}
		$this->DenyIfNotExists=$GLOBALS["WindowsUpdateDenyIfNotExists"];
		if($this->GetLocalDir()){return true;}
		return false;
		
	}
	
	public function LoadWhiteList(){
		if(!is_file("/etc/squid3/windowsupdate.whitelist.db")){return array();}
		if(!isset($GLOBALS["WINDOWS_UPDATE_WHITELIST"]["FTIME"])){$GLOBALS["WINDOWS_UPDATE_WHITELIST"]["FTIME"]=0;}
		
		$t=filemtime("/etc/squid3/windowsupdate.whitelist.db");
		if($t==$GLOBALS["WINDOWS_UPDATE_WHITELIST"]["FTIME"]){return $GLOBALS["WINDOWS_UPDATE_WHITELIST"];}
		
		$f=explode("\n",@file_get_contents("/etc/squid3/windowsupdate.whitelist.db"));
		while (list($num,$val)=each($f)){
			$val=trim($val);
			if($val==null){continue;}
			$GLOBALS["WINDOWS_UPDATE_WHITELIST"][$val]=true;
		}
		$GLOBALS["WINDOWS_UPDATE_WHITELIST"]["FTIME"]=$t;
	}
	
		
	
	private function LoadBlacklist(){
		
		if(!is_file("/etc/squid3/WindowsUpdateBlacklists.db")){return array();}
		if(isset($GLOBALS["WindowsUpdateBlacklists"]["FFSIZE"])){
			$size=@filesize("/etc/squid3/WindowsUpdateBlacklists.db");
			if($size==$GLOBALS["WindowsUpdateBlacklists"]["FFSIZE"]){return $GLOBALS["WindowsUpdateBlacklists"];}
		}
		
		$f=explode("\n",@file_get_contents("/etc/squid3/WindowsUpdateBlacklists.db"));
		
		while (list($num,$val)=each($f)){
			$val=trim($val);
			if($val==null){continue;}
			$GLOBALS["WindowsUpdateBlacklists"][$val]=true;
			
		}
		
		$this->events("Add ".count($GLOBALS["WindowsUpdateBlacklists"])." blacklisted files", __LINE__);
		$size=@filesize("/etc/squid3/WindowsUpdateBlacklists.db");
		$GLOBALS["WindowsUpdateBlacklists"]["FFSIZE"]=$size;
		return $GLOBALS["WindowsUpdateBlacklists"];
		
	}
	
		
	private function isAWindowsUpdate(){
		$ipsrc=trim($GLOBALS["LOG_AR"]["IP"]);
		$BaseName=null;
		$NOTNESSCAB[]="disallowedcertstl";
		$NOTNESSCAB[]="pinrulesstl";
		$NOTNESSCAB[]="wsus3setup";
		$NOTNESSCAB[]="authrootstl";
		$NOTNESSCAB[]="muv4wuredir";
		$NOTNESSCAB[]="WuSetupHandler";
		$NOTNESSCAB[]="v6-win7sp1-wuredir";
		
		
//		if(preg_match("#\.dl\.[a-z]+\.[a-z]+\.microsoft\.com.*?\.appx#", $this->uri)){return true; }
		
		
		if(strpos($this->uri, ".windowsupdate.com")>0){
			$FileName=basename($this->uri);
			if(preg_match("#(".@implode("|", $NOTNESSCAB).")\.cab#", $this->uri)){
				$this->events("OK: SKIP:[$ipsrc] ".basename($this->uri), __LINE__);
				return false;
			}
			
			if(preg_match("#WUClient-SelfUpdate#", $this->uri)){
				$this->events("OK: SKIP:[$ipsrc] ".basename($this->uri), __LINE__);
				return false;
			}
			
			$parse_url=parse_url($this->uri);
			if(!isset($parse_url["path"])){
				$this->events("OK: SKIP:[$ipsrc] ".basename($this->uri)." (path not found)", __LINE__);
				return false;
			}
			$BaseName=basename($parse_url["path"]);
			
			if(preg_match("#cleanupwindowsdefendertasks#", $BaseName)){
				$this->events("OK: SKIP:[$ipsrc] $BaseName", __LINE__);
				return false;
			}
			
			if(preg_match("#^[0-9]+_[a-z0-9]+\.cab$#", $BaseName)){
				$this->events("OK: SKIP:[$ipsrc] $BaseName", __LINE__);
				return false;
			}
			
			$LoadWhiteList=$this->LoadWhiteList();
			
			if(isset($LoadWhiteList[$ipsrc])){
				$this->events("OK: SKIP:[$ipsrc] {$GLOBALS["LOG_AR"]["IP"]} white-listed", __LINE__);
				return false;
			}
						
			$Blacklists=$this->LoadBlacklist();
			if(isset($Blacklists[$BaseName])){
				$this->events("OK: SKIP:[$ipsrc] $BaseName (blacklisted)", __LINE__);
				return false;
				
			}
			
			
			
			$this->debug("$this->szLine OK");
			return true;
		}
		
	}
	
	private function file_ext($path){
		$path_info = pathinfo($path);
		return $path_info['extension'];
	
	}
	
	private function GetLocalDir(){
		$prefix_channel=null;
		$parse_url=parse_url($this->uri);
		$sitename=$parse_url["host"];
		if(preg_match("#(.+?):([0-9]+)#", $sitename,$re)){$sitename=$re[1];}
		$Path=$parse_url["path"];
		$familysite=$this->tool_get_familysite($sitename);
		$pathMd5=md5($Path);
		$filename=basename($Path);
		$this->WindowsUpdatePartitionExceed=false;
		if(is_file("/etc/squid3/WindowsUpdatePartitionExceed")){
			$this->WindowsUpdatePartitionExceed=true;
		}
		
		$allowed_ext["cab"]=true;
		$allowed_ext["psf"]=true;
		$allowed_ext["exe"]=true;
		
		
		
		$ext=$this->file_ext($filename);
		if(!isset($allowed_ext[$ext])){
			$this->events("OK: SKIP:[{$GLOBALS["LOG_AR"]["IP"]}] Extension *.$ext not allowed for $filename", __LINE__);
			return false;
		}
		
		$TwoChars=substr($pathMd5, 0,2);
		$fullpath="{$GLOBALS["WindowsUpdateCachingDir"]}/Cache/$TwoChars/$pathMd5/$filename";
		$fulluri="http://127.0.0.1/WindowsUpdateProxyCache/Cache/$TwoChars/$pathMd5/$filename";
		
		if(!is_file($fullpath)){
			$this->debug("$fullpath not exists", __LINE__);
			$this->SaveQueue($this->uri,$fullpath);
			if($this->DenyIfNotExists==1){return $this->SendDeny();}
			return false;
		}
		
		$size=@filesize($fullpath);
		if($size<5){
			@unlink($fullpath);
			$this->debug("$fullpath only 5 bytes lenght", __LINE__);
			$this->SaveQueue($this->uri,$fullpath);
			if($this->DenyIfNotExists==1){return $this->SendDeny();}
			return false;
		}

		
		$size=$size/1024;
		$size=round($size/1024,2);
		$this->events("OK: CACHED:[{$GLOBALS["LOG_AR"]["IP"]}] ".basename($this->uri)." {$size}MB", __LINE__);
		$prefix_channel=$this->PREFIX_CHANNEL();
		$LineTOSend="{$prefix_channel}OK rewrite-url=\"$fulluri\"";
		$this->debug("Output: $LineTOSend", __LINE__);
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
	
	private function SaveQueue($uri,$fullpath){
		if($this->WindowsUpdatePartitionExceed){return false; }
		$this->events("NO: QUEUE:[{$GLOBALS["LOG_AR"]["IP"]}] ".basename($uri), __LINE__);
		$logFile="{$GLOBALS["WindowsUpdateCachingDir"]}/Queue.log";
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$fullpath|||$uri\n");
		@fclose($f);
	}
	
		
private function tool_get_familysite($sitename){
	if(isset($GLOBALS["FAMILYSITES"][$sitename])){return $GLOBALS["FAMILYSITES"][$sitename];}
	$f=new familysite();
	$GLOBALS["FAMILYSITES"][$sitename]=$f->GetFamilySites($sitename);
	return $GLOBALS["FAMILYSITES"][$sitename];
	
}

private function debug($text,$line){
	if(!isset($GLOBALS["DEBUG_OUTPUT"])){$GLOBALS["DEBUG_OUTPUT"]=false;}
	if($GLOBALS["DEBUG_OUTPUT"]){
		$this->events($text, $line);
	}
}
		
	private function events($text,$line){
		$date=@date("H:i:s");
		$logFile="/var/log/squid/windowsupdate.debug";
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