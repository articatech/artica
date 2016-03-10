<?php


class squid_checks{
	private $sourcefile=null;
	private $squidbin=null;
	
	
	function squid_checks($sourceconf=null){
		if($sourceconf==null){$sourceconf="/etc/squid3/squid.conf";}
		$this->sourcefile=$sourceconf;
		
		$unix=new unix();
		$this->squidbin=$this->find_program("squid3");
		if(!is_file($this->squidbin)){$this->squidbin=$this->find_program("squid");}
		
	}
	
	function ignoreline($ligne){
		$ligne=trim($ligne);
		if($ligne==null){return true;}
		if(strpos($ligne,"| WARNING:")>0){return true;}
		if(strpos($ligne,"url_regex -i")>0){return true;}
		if(preg_match("#Processing:\s+#", $ligne)){return true;}
		if(preg_match("#Warning: empty ACL#", $ligne)){return true;}
		if(preg_match("#searching predictable#", $ligne)){return true;}
		if(preg_match("#is a subnetwork of#", $ligne)){return true;}
		if(preg_match("#You should probably#", $ligne)){return true;}
		if(preg_match("#Startup:\s+#", $ligne)){return true;}
		
	}
	
	function isfatal($ligne){
		if(preg_match("#Segmentation fault#", $ligne)){
			echo "Starting......: ".date("H:i:s")." [SYS]: !!!! $ligne !!!!!\n";
			if(function_exists("build_progress")){build_progress("{operation_failed} Segmentation fault !!!",110);}
			return true;
		}
		
	}
	
	private function OutputLines($ligne){
		
		if(preg_match("#\/etc\/squid3\/(.+?):([0-9]+)#", $ligne,$ri)){
			$Buggedline=$ri[2];
			echo "Starting......: ".date("H:i:s")." [SYS]: Line number: $Buggedline on file {$ri[1]}\n";
			$tt=explode("\n",@file_get_contents("/etc/squid3/{$ri[1]}"));
			
			echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
			for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
				$lineNumber=$i+1;
				if(trim($tt[$i])==null){continue;}
					echo "Starting......: ".date("H:i:s")." [SYS]: [line:$lineNumber]: \"{$tt[$i]}\"\n";
				}
			echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
			return;
		}
		
		if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents($this->sourcefile));
				echo "Starting......: ".date("H:i:s")." [SYS]: *****************************\n";
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "Starting......: ".date("H:i:s")." [SYS]: [line:$lineNumber]: {$tt[$i]}\n";
				}
			
			
			return;
		}
		
		
	}
		
	
	
	function isBungled($ligne){
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$this->OutputLines($ligne);
			if(function_exists("build_progress")){build_progress("{operation_failed} Bungled !!!",110);}
			return true;}
		
	}
	
	function isFileMissing($ligne){
		if(!preg_match("#strtokFile:\s+(.+?)\s+not found#", $ligne,$re)){return false;}
		$filename=trim($re[1]);
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid missing $filename, create an empty one\n";
		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename ,"");
		@chown($filename, "squid");
		@chgrp($filename, "squid");
		
		
	}
	
	
	private function revert(){
		if(!is_file("/root/squid-good.tgz")){
			echo "Starting......: ".date("H:i:s")." [SYS]: !!! Missing backup configuration, could not revert\n";
			return false;
		}
		
		
	
		echo "Starting......: ".date("H:i:s")." [SYS]: !!! aborting configuration, keep the old one...\n";
		echo "Starting......: ".date("H:i:s")." [SYS]: Revert to old config...\n";
		shell_exec("/bin/tar -xf /root/squid-good.tgz -C /etc/squid3/");
		
	}
	
	private function backup(){
		
		@file_put_contents("/etc/artica-postfix/settings/Daemons/GlobalSquidConf",@file_get_contents("/etc/squid3/squid.conf"));
		
		if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}
		chdir("/etc/squid3");
		shell_exec("cd /etc/squid3");
		echo "Starting......: ".date("H:i:s")." [SYS]: Compressing and banckup configuration...\n";
		shell_exec("/bin/tar -czf /root/squid-good.tgz *");
		chdir("/root");
		shell_exec("cd /root");
	}
	
	
	function squid_parse(){
		exec("$this->squidbin -f $this->sourcefile -k parse 2>&1",$this->results);
		$results=$this->results;
		while (list ($index, $ligne) = each ($results) ){
			if($this->ignoreline($ligne)){continue;}
			if($this->isfatal($ligne)){return false;}
			if($this->isFileMissing($ligne)){return true;}
		
			if(preg_match("#ERROR: Failed#", $ligne)){
				if(function_exists("squid_admin_mysql")){squid_admin_mysql(0, "Configuration failed, revert to backuped config", $ligne,__FILE__,__LINE__);}
				$this->revert();
				return false;
			}
		
			if($this->isBungled($ligne)){
				if(function_exists("squid_admin_mysql")){squid_admin_mysql(0, "Configuration failed, revert to backuped config", $ligne,__FILE__,__LINE__);}
				$this->revert();
				return false;
				
			}
										
		}
		
		$this->backup();
		return true;
	
	}
	
	private function find_program($strProgram){
		if($strProgram=="rm"){
			if(!is_file("/bin/rm")){
				$Arch=$this->LINUX_ARCHITECTURE();
				if($Arch==64){
					@copy("/usr/share/artica-postfix/bin/rm","/bin/rm");
					$chattr=$this->find_program("chattr");
					shell_exec("$chattr -i /bin/rm");
				}
			}
		}
	
		global $addpaths;
		$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
				'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
		if (function_exists("is_executable")) {
			foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
		} else {
			return strpos($strProgram, '.exe');
		}
	}	
	
	
}