<?php

class SocketsAsRoot{
	public $Runned=false;
	private $uri=null;
	
	
	
	
	public function SocketsAsRoot($uri){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$this->uri=$uri;
		$this->SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
		$this->EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		
		
	}
	
	public function SocketsAsRoot_perform(){
		$uri=$this->uri;
		$this->Runned=false;
		
		$unix=new unix();
		
// *************************************************************************************************************************		
		
		if($uri=="cmd.php?ldap-restart=yes"){
			$this->Runned=true;
			if($this->SquidPerformance>2){return;}
			if($this->EnableIntelCeleron==1){return;}
			
			$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
			if($EnableOpenLDAP==1){
				shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__)." &");
			}
			$this->Runned=true;return;
		}
		
// *************************************************************************************************************************
		if($uri=="system.php?debian_version=yes"){
			if(!is_file("/etc/debian_version")){return;}
			$ver=trim(@file_get_contents("/etc/debian_version"));
			preg_match("#^([0-9]+)\.#",$ver,$re);
			if(preg_match("#squeeze\/sid#",$ver)){return 6;}
			$Major=$re[1];
			if(!is_numeric($Major)){return;}
			$this->Runned=true;
			return $Major;
			
		}
		
		
		
		if($uri=="squid.php?IsIcapClient=yes"){
			if($unix->SQUID_ICAP_ENABLED()){ return "TRUE"; }
			$this->Runned=true;
			return;
		}
		
		
		if($uri=="postfix.php?islocked=yes"){
			$this->Runned=true;
			if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){ return base64_encode("TRUE"); }
			return;
		}
// *************************************************************************************************************************		
		if($uri=="cmd.php?status-forced=yes"){
			$this->Runned=true;
			$unix->THREAD_COMMAND_SET('/etc/init.d/artica-status reload');
			return;
		}
// *************************************************************************************************************************		
		if($uri=="squid.php?isInjectrunning=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			exec("pgrep -l -f \"exec.squid.blacklists.php --v2\" 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){	
				if(preg_match("#pgrep#", $ligne)){continue;}
				if(preg_match("#^([0-9]+).*?blacklists\.php#", $ligne,$re)){
					return $unix->PROCCESS_TIME_MIN($re[1]);
					return;
				}
			}
			
			return;
		}
// *************************************************************************************************************************		
		if($uri=="services.php?ARTICA-MAKE=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			$master_pid=0;
			exec("$pgrep -l -f \"bin/artica-make\"",$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#pgrep#", $line)){continue;}
				if(preg_match("#^([0-9]+)\s+sh\s+#", $line,$re)){continue;}
				if(preg_match("#^([0-9]+)\s+.+?artica-make\s+([A-Z\_0-9]+)#", $line,$re)){
					$pid=$re[1];
					$time=$unix->PROCESS_TTL_TEXT($pid);
					$SOFT=$re[2];
					$array[$SOFT]=$time;
				}
			}
			return base64_encode(serialize($array));
		}
//*************************************************************************************************************************		
		if($uri=="squid.php?schedule-import-exec=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			exec("$pgrep -l -f \"exec.squid.blacklists.php --inject\" 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){
				if(!preg_match("#^([0-9]+).+?blacklists\.php#", $ligne,$re)){continue;}
				if(preg_match("#pgrep -l#", $ligne)){continue;}
				$time=$unix->PROCCESS_TIME_MIN($re[1]);
				$array["RUNNING"]=true;
				$array["TIME"]=$time;
				return base64_encode(serialize($array));
			}			
			
		}
// *************************************************************************************************************************		
		if($uri=="status.php?cpu-check-nx=yes"){
			$this->Runned=true;
			$check=$unix->find_program("check-bios-nx");
			if(strlen($check)<5){return;}
			exec("$check --verbose 2>&1",$results);
			return base64_encode(@implode("\n",$results));
		}
// *************************************************************************************************************************		
		if($uri=="services.php?is-dpkg-running=yes"){
			$this->Runned=true;
			$pgrep=$unix->find_program("pgrep");
			$master_pid=0;
			$cmdline="$pgrep -l -f \"/dpkg\" 2>&1";
			exec("$cmdline",$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#pgrep#", $line)){continue;}
				if(preg_match("#^([0-9]+)\s+#", $line,$re)){
					$array[$re[1]]=true;
				}
			}
			return base64_encode(serialize($array));
		}
				
// *************************************************************************************************************************		
		if($uri=="cmd.php?MalwarePatrolDatabasesCount=yes"){
			$datas=explode("\n",@file_get_contents("/etc/squid3/malwares.acl"));
			$count=0;
			while (list ($num, $line) = each ($datas) ){ if(trim($line)==null){continue;} if(substr($line,0,1)=="#"){continue;} $count=$count+1; }
			$this->Runned=true;
			return $count;
		}
// *************************************************************************************************************************
		if($uri=="cmd.php?postfixQueues=yes"){
			include_once("/usr/share/artica-postfix/framework/class.postfix.inc");
			$p=new postfix_system();$datas=serialize($p->getQueuesNumber());
			$this->Runned=true;
			return serialize($p->getQueuesNumber());
		}
// *************************************************************************************************************************
		if($uri=="services.php?SessionPathInMemoryInfos=yes"){
			$array=$unix->SessionPathInMemoryInfos();
			$this->Runned=true;
			return base64_encode(serialize($array));
		}
// *************************************************************************************************************************
		if($uri=="services.php?CPU-NUMBER=yes"){
			$CPUNUM=$unix->CPU_NUMBER();
			$this->Runned=true;
			return $CPUNUM;
		}
// *************************************************************************************************************************		
		if($uri=="cmd.php?system-debian-kernel=yes"){
			exec("/usr/bin/php /usr/share/artica-postfix/exec.apt-cache.kernel.php --detect");
			$this->Runned=true;
			return;
		}	
// *************************************************************************************************************************
		if($uri=="cmd.php?squid-ini-status=yes"){
			exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --all-squid --nowachdog 2>&1",$results);
			$this->Runned=true;
			return base64_encode(@implode("\n", $results));
		}		
// *************************************************************************************************************************				
		if($uri=="services.php?GetMyHostId=yes"){
			$this->Runned=true;
			return $unix->GetMyHostId();
		}	
// *************************************************************************************************************************
		if($uri=="squid.php?full-version=yes"){
			$squidbin=$unix->find_program("squid");
			if($squidbin==null){$squidbin=$unix->find_program("squid3");}
			exec("$squidbin -v 2>&1",$results);
			while (list ($num, $val) = each ($results)){
				if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
					$this->Runned=true;
					return trim($re[1]);}
			}
			$this->Runned=true;
			return;
				
		}

		if($uri=="system.php?restart-ldap=yes"){
			shell_exec("/etc/init.d/artica-postfix restart ldap >/dev/null 2>&1");
			die();
		}
		
		if($uri=="cmd.php?ifconfig-all-ips=yes"){
			if(!isset($GLOBALS["ifconfig_all_ips"])){
			$GLOBALS["ifconfig_all_ips"]=base64_encode(serialize($unix->ifconfig_all_ips()));
			}
			$this->Runned=true;
			return $GLOBALS["ifconfig_all_ips"];
		}
		
		
	}
	
}