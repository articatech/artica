<?php

class disk_monitor{
	
	var $HardDisksWatchDog=array();
	
	function disk_monitor(){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$this->HardDisksWatchDog=unserialize(@file_get_contents('/etc/artica-postfix/settings/Daemons/HardDisksWatchDog'));
	}
	
	
	public function Scan(){
		if(!is_array($this->HardDisksWatchDog)){return;}
		if(count($this->HardDisksWatchDog)==0){return;}
		while (list ($dev, $ligne) = each ($this->HardDisksWatchDog) ){
			
			$this->Analyze($dev);
		}
		
	}
	
	private function Analyze($dev){
		
		
		
		exec("/bin/df -h $dev/* 2>&1",$results);
		while (list ($dev, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#(.+?)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9]+)%\s+(.+)#", $ligne,$re)){continue;}
			$partition=$re[1];
			$size=$re[2].$re[3];
			$util=$re[4].$re[5];
			$Dispo=$re[6].$re[7];
			$perc=$re[8];
			$mounted=$re[9];
			
			if($perc<80){continue;}
			
			
			$md5=md5("$dev$partition$perc");
			$TimeFile="/etc/artica-postfix/pids/disk_monitor.$md5";
			$TimeStamp=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
			if($TimeStamp>120){continue;}
			
			if($perc>80){
				if($perc<95){
					$text="The disk $dev with the $partition ($mounted) exceed 80% ({$perc}%)\nPartition size: $size, used {$util}, free {$Dispo}";
					system_admin_events("Warning, partition $partition exceed 80%",$text,__FUNCTION__,__FILE__,__LINE__);
					$GLOBALS["CLASS_UNIX"]->send_email_events("Warning, partition $partition exceed 80%",$text);
					
					@unlink($TimeFile);
					@file_put_contents($TimeFile, time());
					continue;
				}
				
				if($perc>94){
					$text="The disk $dev with the $partition ($mounted) exceed 95% ({$perc}%)\nPartition size: $size, used {$util}, free {$Dispo}";
					system_admin_events("FATAL!!!, partition $partition exceed 95%",$text,__FUNCTION__,__FILE__,__LINE__);
					$GLOBALS["CLASS_UNIX"]->send_email_events("Warning, partition $partition exceed 80%",$text);
					@unlink($TimeFile);
					@file_put_contents($TimeFile, time());
					continue;
				}
				
			}
			
			
		}
		
		
	}
	
	
}