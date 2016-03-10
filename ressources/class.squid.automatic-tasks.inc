<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
include_once(dirname(__FILE__)."/class.os.system.inc");

class squid_auto_tasks{
	
	
	function squid_auto_tasks(){
		$this->LoadClasses();
		$this->execute();
	}
	
	
	private function ifStatisticsMustBeExecuted(){
		$users=new usersMenus();
		$sock=new sockets();
		$update=true;
		$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		$EnableRemoteStatisticsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance"));
		$CategoriesRepositoryEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRepositoryEnable"));
		if($SquidPerformance>2){return false;}
		if($EnableRemoteStatisticsAppliance==1){return false;}
		if($EnableWebProxyStatsAppliance==1){return true;}
		if($CategoriesRepositoryEnable==1){return true;}
		
		return true;
	}
	
	private function squid_rtt_events($text){
		if(trim($text)==null){return;}
	
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
	
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
		@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
		@fclose($f);
		@chmod($logFile,0755);
		@chown($logFile, "squid");
	
	}	
	
	
	private function execute(){
		$DisableStats=0;
		$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
		$prefixcmdBIN=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}"." ";
		$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
		if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}
		$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
		$DisableArticaProxyStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaProxyStatistics");
		$EnableImportOldSquid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableImportOldSquid");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		$UnlockWebStats=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
		$ArticaDBPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDBPath");
		$EnableSargGenerator=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSargGenerator");
		$CacheManagement2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheManagement2");
		if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
		if(!is_numeric($CacheManagement2)){$CacheManagement2=1;}
		$CACHE_DYN_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDynamicCaches")));
		if(!is_numeric($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
		if($CACHE_DYN_PARAMS["INTERVAL"]<60){$CACHE_DYN_PARAMS["INTERVAL"]=60;}
		$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
		$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
		$EnableKerbAuth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));
		
		//Paramètres de rotation des logs.
		$SquidLogRotateFreq=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidLogRotateFreq"));
		$SquidRotateOnlySchedule=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidRotateOnlySchedule"));
		if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
		
		
		
		
		$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		
		if(!is_numeric($CACHE_DYN_PARAMS["ENABLED"])){if($SquidCacheLevel>2){$CACHE_DYN_PARAMS["ENABLED"]=1; }}
		if(!is_numeric($CACHE_DYN_PARAMS["MAX_WWW"])){$CACHE_DYN_PARAMS["MAX_WWW"]=100;}
		if(!is_numeric($CACHE_DYN_PARAMS["LEVEL"])){$CACHE_DYN_PARAMS["LEVEL"]=5;}
		if(!is_numeric($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
		if(!is_numeric($CACHE_DYN_PARAMS["MAX_TTL"])){$CACHE_DYN_PARAMS["MAX_TTL"]=15;}
		if(!is_numeric($CACHE_DYN_PARAMS["ENABLED"])){$CACHE_DYN_PARAMS["ENABLED"]=0;}
		if($SquidCacheLevel<3){$CACHE_DYN_PARAMS["ENABLED"]=0;}
		
		
		
		$EnableArticaHotSpot=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaHotSpot"));
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
		if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
		
		if($SQUIDEnable==0){return;}
		if($EnableRemoteStatisticsAppliance==1){$DisableStats=1;}
		if($DisableArticaProxyStatistics==1){$DisableStats=1;}
		if($SquidPerformance>2){$DisableStats=1;}
		
		if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
		$ifStatisticsMustBeExecuted=$this->ifStatisticsMustBeExecuted();
		
		
	if($EnableProxyCompressor==1){		
	//**********************************************************************************************************************
	// ZipProxy statistiques globales
			$TimeFile="/etc/artica-postfix/pids/exec.zipproxy.php.zipproxy_global.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($TimeFile);
			if($CacheSchedules>10){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.zipproxy.php --global >/dev/null 2>&1 &");
				$this->squid_rtt_events("Running $cmd");
				shell_exec($cmd);
			}	
	//**********************************************************************************************************************
	// ZipProxy injection des access.log
			$TimeFile="/etc/artica-postfix/pids/exec.zipproxy.php.zipproxy_access.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($TimeFile);
			if($CacheSchedules>60){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.zipproxy.php --access >/dev/null 2>&1 &");
				$this->squid_rtt_events("Running $cmd");
				shell_exec($cmd);
			}
			
	}
		
//**********************************************************************************************************************		
		//Regénères les états de l'interface ( toutes les 10mn)	
		$TimeFile="/etc/artica-postfix/pids/SQUID_ALL_STATUS.time";
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
		_statussquid("exec.squid.watchdog.php --all-status {$CacheSchedules}Mn/10");
		if($CacheSchedules>10){
			@unlink($TimeFile);
			@file_put_contents($TimeFile, time());
			$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php --all-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}

//**********************************************************************************************************************		
//rotation des évènements squid.
		if($SquidRotateOnlySchedule==0){		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
			_statussquid("exec.squid.php --rotate {$CacheSchedules}Mn/{$SquidLogRotateFreq}mn");
			
			if($CacheSchedules>$SquidLogRotateFreq){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --rotate >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		}
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.rotate.php.build.time");
		if($time>60){
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.rotate.php >/dev/null 2>&1 &"));
		}
		
//**********************************************************************************************************************
		$this->events("Execute: DisableStats:`$DisableStats`",_CLASS_."/".__FUNCTION__,__LINE__);
		if($DisableStats==0){	
			$this->statistics_tasks();		
		 }
		
		if(is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager")){
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/kav4proxy-licensemanager-i");
			if($CacheSchedules>60){
				if(system_is_overloaded()){return;}
				@unlink("/etc/artica-postfix/kav4proxy-licensemanager-i");
				$cmd=trim($prefixcmdBIN."/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -i >/etc/artica-postfix/kav4proxy-licensemanager-i 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
			
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.kav4proxy.php.license_infos.time");
			if($CacheSchedules>240){
				if(system_is_overloaded()){return;}
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.kav4proxy.php --license >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
			$KasperskyAskQuote=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskyAskQuote");
			if(!is_numeric($KasperskyAskQuote)){$KasperskyAskQuote=0;}
			
			if($KasperskyAskQuote==1){
				$KasperskyAskQuoteResults=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskyAskQuoteResults"));
				if($KasperskyAskQuoteResults<>"KEY_OK"){
					$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/KasperskyAskQuoteResults");
					if($CacheSchedules>5){
						$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.web-community-filter.php --register-kaspersky >/dev/null 2>&1 &");
						$this->shell_exec2($cmd);
					}
				}
			}
				
				
			
		}
		
		

		
		

		
		
		if($EnableArticaHotSpot==1){
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.artica.hotspot.php.remove_mysql_sessions.time");
			if($CacheSchedules>10){
				if(system_is_overloaded()){return;}
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.artica.hotspot.php --remove-mysql-sessions >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
		}
		
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.cache_center_status.time");
		_statussquid("exec.squid.watchdog.php --caches-center-status {$CacheSchedules}Mn/5mn");
		
		if($CacheSchedules>5){
			$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php --caches-center-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		

		

		
		$system_is_overloaded=system_is_overloaded();
		if($system_is_overloaded){
			_statussquid("Overloaded system");
		}
		
		if(!$system_is_overloaded){
		
		
		
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time");
			if($CacheSchedules>2){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.dansguardian.injector.php >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
				@unlink("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time");
				@file_put_contents("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time", time());
			}
			
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.c-icap.php.purge.time");
			if($CacheSchedules>20){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.c-icap.php --purge --schedule >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		
		
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
			if($CacheSchedules>30){
				@unlink("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squidguard.php --artica-db-status >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		
		
		
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/squid.compilation.params");
			if($CacheSchedules>20){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --compilation-params >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		
			
		
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.build_schedules_tests.time");
			if($CacheSchedules>10){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --build-schedules-test >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
		
		
		
			if($EnableRemoteStatisticsAppliance==1){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.netagent.php --timeout >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		
		
			if(is_file("$ArticaDBPath/VERSION")){
					$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/web/categories-db.size.db");
					if($CacheSchedules>20){
					$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.catz-db.php --databasesize >/dev/null 2>&1 &");
					if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
					$this->shell_exec2($cmd);
				}
			}
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
			if($CacheSchedules>1440){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --build-schedules >/dev/null 2>&1 &");
				@unlink("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
				@file_put_contents("/etc/artica-postfix/CACHES_SQUID_SCHEDULE", time());
				$this->shell_exec2($cmd);
			}
				
		
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php --counters >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php --peer-status >/dev/null 2>&1 &"));
		
		
			
		
		
		
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
			if($time>15){
				$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --cache-infos >/dev/null 2>&1 &"));
			}
		
		
		
			if(is_file("{$GLOBALS["BASE_ROOT"]}/exec.kerbauth.php")){
				if($EnableKerbAuth==1){
					$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.kerbauth.php.1");
					if($time>30){
						$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.kerbauth.php --klist >/dev/null 2>&1 &"));
						$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.kerbauth.php --winbinddpriv >/dev/null 2>&1 &"));
						@unlink("/etc/artica-postfix/pids/exec.kerbauth.php.1");
						@touch("/etc/artica-postfix/pids/exec.kerbauth.php.1");
					}
				}
			}
		}
		
		
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidLogs.time");
		if($time>1440){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.clean.logs.php --squidLogs >/dev/null 2>&1 &"));
		}		
		
		
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time");
		if($time>15){
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.clean.logs.php --squid >/dev/null 2>&1 &"));
		}
		
		
		
		
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			mysql_admin_mysql(1, "SQUID_TEMPLATE_DONE: No such file, launch build template action...", null,__FILE__,__LINE__);
			$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.php --tpl-save >/dev/null 2>&1 &"));
		}
		
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/web/squiddb.size.db");
	_statussquid("exec.squid-db.php --databasesize {$time}Mn");
	if($time>20){
		if(system_is_overloaded()){return;}
		$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid-db.php --databasesize >/dev/null 2>&1 &"));
	}
		
	
		
		
	}
	
	
private function statistics_tasks(){
		
		$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
		$prefixcmdBIN=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}"." ";
		$ifStatisticsMustBeExecuted=$this->ifStatisticsMustBeExecuted();
		
		$this->events("Execute: statistics_tasks() ifStatisticsMustBeExecuted:$ifStatisticsMustBeExecuted",_CLASS_."/".__FUNCTION__,__LINE__);
		
		$CategoriesDatabasesByCron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/CategoriesDatabasesByCron"));
		
		
		// Scan les videos Youtube toutes les heures.
		if($ifStatisticsMustBeExecuted){
			$TimeFile="/etc/artica-postfix/pids/YoutubeByHour.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
			if($CacheSchedules>65){
				$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.stats.youtube.days.php --onlyhours >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
				if(!is_file($TimeFile)){ @file_put_contents($TimeFile, time()); }
			}
		}

// ********************************************************************************************************************		
// Répare le calendrier des jours.
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.totals.php.TOTALS_REPAIR.time");
	if($CacheSchedules>240){
		$cmd=trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.stats.totals.php --repair-time >/dev/null 2>&1 &");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$this->shell_exec2($cmd);
	}		
// ********************************************************************************************************************		

if($ifStatisticsMustBeExecuted){
	// ********************************************************************************************************************
				
		
}


		
		
		if($CategoriesDatabasesByCron==0){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/ArticaDbCloud");
			if($time>60){
				$this->shell_exec2(trim($prefixcmd."{$GLOBALS["BASE_ROOT"]}/exec.squid.blacklists.php >/dev/null 2>&1 &"));
			}
		}
			
		$this->events("FINISH: statistics_tasks() *** *** ***",_CLASS_."/".__FUNCTION__,__LINE__);
	}
	
	
	private function shell_exec2($cmdline){
		if(function_exists("shell_exec2")){shell_exec2($cmdline);return;}
		_statussquid($cmdline);
		$text_cmdline=$cmdline;
		if(preg_match("#\/artica-postfix\/(.+?)$#", $cmdline,$re)){
			$text_cmdline=$re[1];
		}
		
		$this->events("Execute: $text_cmdline",_CLASS_."/".__FUNCTION__,__LINE__);
		shell_exec($cmdline);
	
	}
	
	private function LoadClasses(){
		
		
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once("{$GLOBALS["BASE_ROOT"]}/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		
		if(!isset($GLOBALS["SQUIDBIN"])){$GLOBALS["SQUIDBIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();}
		
		if(!isset($GLOBALS["CLASS_SOCKETS"])){
			include_once("{$GLOBALS["BASE_ROOT"]}/ressources/class.sockets.inc");
			$GLOBALS["CLASS_SOCKETS"]=new sockets();
		}
		
		_statussquid("LoadClasses() done");
		
	}

	private function events($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		$function=__CLASS__."/".$function;
		$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-stats-executor.log",false,$function,$line,$filename);
	}	
	
}