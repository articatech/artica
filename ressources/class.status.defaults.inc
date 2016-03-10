<?php

function Build_default_values(){
	
	
	$KERNEL_VERSION=$GLOBALS["CLASS_UNIX"]->KERNEL_VERSION();
	$ipset=$GLOBALS["CLASS_UNIX"]->find_program("ipset");
	$cgrulesengd=$GLOBALS["CLASS_UNIX"]->find_program("cgrulesengd");
	$dialog=$GLOBALS["CLASS_UNIX"]->find_program("dialog");
	var_lib_php();
	buildCronStatistics();
	
	$fjih=trim(@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw==")));
	
	if($fjih=="TRUE"){@file_put_contents(base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ=="),"VFJVRQ==");}else{
		@file_put_contents(base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ=="),"RkFMU0U=");
	}
	@chmod(base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ=="),0755);
	
	
	if(!is_file($dialog)){
		$TimeEx=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/install.dialog.time");
		if($TimeEx>15){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.installv2.php --dialog >/dev/null 2>&1");
			shell_exec2($cmd);
		}
	}
	
	
	if(is_file("/etc/cron.d/php5")){@unlink("/etc/cron.d/php5");shell_exec2("/etc/init.d/cron reload");}
	if(is_file("/usr/local/bin/sarg")){@unlink("/usr/local/bin/sarg");}
	
	if(is_file($ipset)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/IPSetInstalled",1);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/IPSetInstalled",0);
	}
	
	if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/iptablesNDPI",1);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/iptablesNDPI",0);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMsftncsi")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableMsftncsi",1);
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-msftncsi","* * * *","exec.msftncsi.php");
		shell_exec2("/etc/init.d/cron reload");
	}
	
	
	
	
	if(!is_file($cgrulesengd)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/CGROUPS_INSTALLED", 0);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/CGROUPS_INSTALLED", 1);
	}
	
	if(!is_file("/usr/lib/apache2/modules/mod_cband.so")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ApacheModCbandInstalled", 0);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ApacheModCbandInstalled", 1);
	}
	
	if(is_file("/usr/local/ArticaStats/bin/postgres")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/StatsPostGres", 1);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/StatsPostGres", 0);
	}
		
	
	
	if(is_file("/etc/cron.d/access-failed-parser")){@unlink("/etc/cron.d/access-failed-parser");}
	if(is_file("/etc/cron.d/access-logs-parser")){@unlink("/etc/cron.d/access-logs-parser");}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSquidQuotasBandwidth")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableSquidQuotasBandwidth", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidQuotaBandwidthRefresh")){@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidQuotaBandwidthRefresh", 30);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMacAddressFilter")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableMacAddressFilter", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote")){@file_put_contents("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSuricata")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/cgroupsEnabled")){@file_put_contents("/etc/artica-postfix/settings/Daemons/cgroupsEnabled", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/cgroupsServiceEnabled")){@file_put_contents("/etc/artica-postfix/settings/Daemons/cgroupsServiceEnabled", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableClamavUnofficial")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableClamavUnofficial", 0);}
	
	$EnableClamavUnofficial=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableClamavUnofficial"));
	
	
	if($EnableClamavUnofficial==1){
		if(!is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			$CRON[]="MAILTO=\"\"";
			$CRON[]="45 * * * * root /usr/share/artica-postfix/bin/clamav-unofficial-sigs.sh -c /etc/clamav-unofficial-sigs.conf >/dev/null 2>&1";
			$CRON[]="";
			file_put_contents("/etc/cron.d/clamav-unofficial-sigs-cron",@implode("\n", $CRON));
			$CRON=array();
			chmod("/etc/cron.d/clamav-unofficial-sigs-cron",0640);
			chown("/etc/cron.d/clamav-unofficial-sigs-cron","root");
			shell_exec2("/etc/init.d/cron reload");
		}
	}
	
	if($EnableClamavUnofficial==0){
		if(is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			@unlink("/etc/cron.d/clamav-unofficial-sigs-cron");
			shell_exec2("/etc/init.d/cron reload");
		}
	}

}

function var_lib_php(){
	$unix=new unix();
	$df=$unix->find_program("df");
	exec("df /var/lib/php5 2>&1",$results);
	
	while (list ($cidr,$none) = each ($results)){
		if(preg_match("#^tmpfs\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+#", $none,$re)){
			$percent=$re[4];
			break;
		}
	}
	
	if($percent<92){return;}
	system_admin_mysql(1, "/var/lib/php5 partition size execeed 92% ({$percent}%) [action=clean]", @implode("\n", $results),__FILE__,__LINE__);
	$files=$unix->DirFiles("/var/lib/php5");
	
	while (list ($filename,$none) = each ($files)){
		$fullpath="/var/lib/php5/$filename";
		$time=$unix->file_time_min($fullpath);
		if($time>15){@unlink($fullpath);continue;}
	
	}
	
}

function buildCronStatistics(){
	$ENABLED=true;
	$SquidPerformance=0;
	$SquidStatsMonthQueue=0;
	$AsStatsAppliance=0;
	
	// if > 1 then no stats $SquidPerformance
	$InfluxSyslogRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote"));
	$InfluxUseRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));}
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
		$InfluxUseRemote=0;
		$SquidPerformance=0;
		$AsStatsAppliance=1;
	}
	
	if($AsStatsAppliance==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/interface-cache/suricata.dashboard");
		if($time_file>15){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.php --dashboard >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
	
	
	
	
	
	if($InfluxUseRemote==1){$ENABLED=false;}
	if($SquidPerformance>1){$ENABLED=false;}
	
	if($ENABLED){
		if(!is_file("/etc/cron.d/statistics-monthly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("statistics-monthly","15 1 * * *","exec.squid.stats.compress.php");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(!is_file("/etc/cron.d/statistics-yearly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("statistics-yearly","30 3 1 * *","exec.squid.stats.compress.php --compress-year");
			shell_exec2("/etc/init.d/cron reload");
		}		
		
		
		buildCronStatistics_run();
		buildCronStatistics_year_run();
	}
	
	if(!$ENABLED){
		if(is_file("/etc/cron.d/statistics-monthly")){
			@unlink("/etc/cron.d/statistics-monthly");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(is_file("/etc/cron.d/statistics-yearly")){
			@unlink("/etc/cron.d/statistics-yearly");
			shell_exec2("/etc/init.d/cron reload");
		}
		
	}
	
}
function buildCronStatistics_run(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue")){return;}
	$SquidStatsMonthQueue=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue"));
	if($SquidStatsMonthQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/SquidStatsMonthQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsMonthQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.stats.compress.php >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));
}
function buildCronStatistics_year_run(){
	
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.stats.compress.php --compress-year >/dev/null 2>&1 &";
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue")){return;}
	$SquidStatsYearQueue=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue"));
	if($SquidStatsYearQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/SquidStatsYearQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsYearQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	shell_exec2(trim($cmd));
}
?>