<?php

class system_tasks{
	var $tasks_array=array();
	var $tasks_explain_array=array();
	var $tasks_processes=array();
	var $task_disabled=array();
	public function system_tasks(){
		$this->fill_task_array();
		$q=new mysql();
		$tasknum=$q->COUNT_ROWS("system_schedules","artica_backup");
		if($q->COUNT_ROWS("system_schedules","artica_backup")==0){$this->CheckDefaultSchedules();}else{
			
		}
		
		
	}
	
	
	public function PatternToHuman($pattern,$nobr=false){
		if(!preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $pattern,$re)){return "{corrupted}...";}
		$min=$re[1];
		$hour=$re[2];
		$DayOfMonth=$re[3];
		$MonthOfYear=$re[4];
		$DayOfWeek=$re[5];
		
		$MM=array();
		$HH=array();
		if($min=="*"){$mins="{all_minutes}";$MM[]="{all_minutes}";}
		if($hour=="*"){$hours="{all_hours}";$HH[]="{all_hours}";}
		if($DayOfWeek=="*"){$days="{all_days}";$DD[]="{all_days}";}else{$days=$DayOfWeek;}
		
		$array_day_of_week=array(
				"0"=>"{Sunday}",
				"1"=>"{Monday}",
				"2"=>"{Tuesday}",
				"3"=>"{Wednesday}",
				"4"=>"{Thursday}",
				"5"=>"{Friday}",
				"6"=>"{Saturday}");		
		
		if(count($HH)==0){
			if(strpos($hour, ",")){$HH=explode(",",$hour);}else{$HH[]=$hour;}
		}
		
		if(count($MM)==0){
			if(strpos($min, ",")){$MM=explode(",",$min);}else{$MM[]=$min;}
		}
		if(count($DD)==0){
			if(strpos($DayOfWeek, ",")){$DD=explode(",",$DayOfWeek);}else{$DD[]=$DayOfWeek;}
		}		
		

		$f=array();
		while (list ($index, $DAY_X) = each ($DD) ){
			//echo "Day: $DAY_X\n";
			if(isset($array_day_of_week[$DAY_X])){
				$f[]="{$array_day_of_week[$DAY_X]}";
			}else{
				$f[]="$DAY_X, ";
			}
			reset($HH);
			while (list ($index, $hour_x) = each ($HH) ){
				if(is_numeric($hour_x)){if(strlen($hour_x)==1){$hour_x="0$hour_x";}}
				//echo "Hour: $hour_x\n";
				reset($MM);
				while (list ($index, $min_x) = each ($MM) ){
					if(is_numeric($min_x)){if(strlen($min_x)==1){$min_x="0$min_x";}}
					//echo "$hour_x:$min_x\n";
					$t[]="$hour_x:$min_x";
				}
			}
			
		}
		if(!$nobr){$br="<br>";}
		
		$final=@implode(", ",$f)."$br {at} ".@implode(", ", $t);
	//	echo $final."\n";
		return $final;
	}
	
	
	private function fill_task_array(){
			$this->tasks_array[0]="{select}";
			$this->tasks_array[1]="{reboot_system}";
			$this->tasks_array[2]="{check_system_updates}";
			$this->tasks_array[3]="{check_acls}";
			$this->tasks_array[4]="{build_cache_users}";
			$this->tasks_array[5]="{check_mysql_performances}";
			$this->tasks_array[6]="{build_available_softwares}";
			$this->tasks_array[7]="{build_install_status}";
			$this->tasks_array[8]="{task_logrotate}";
			$this->tasks_array[9]="{task_remote_agent}";
			$this->tasks_array[10]="{task_ipbandstatus}";
			$this->tasks_array[11]="{task_ipbandresolv}";
			$this->tasks_array[12]="{task_mysqlclean}";
			$this->tasks_array[13]="{compile_squid_repo}";
			$this->tasks_array[15]="{mysql_tables_maintenance}";
			$this->tasks_array[16]="{mysql_defrag_all}";
			$this->tasks_array[17]="{system_swap_reboot}";
			$this->tasks_array[18]="{clean_task_logs}";
			$this->tasks_array[19]="{check_all_services_status}";
			$this->tasks_array[20]="{pkg_upgrade_interface}";
			$this->tasks_array[21]="{parse_freewebs_scansize}";
			$this->tasks_array[22]="{syncronize_dns_entries}";
			$this->tasks_array[23]="{WebCopy_task}";
			
			$this->tasks_array[24]="{xapian_mysql_dirs}";
			$this->tasks_array[25]="{xapian_shared_folder}";
			$this->tasks_array[26]="{xapian_homes_dir}";
			
			$this->tasks_array[27]="{geoip_update_databases}";
			$this->tasks_array[28]="{restart_webconsole_service}";
			$this->tasks_array[29]="{replic_roudcubes_freewebs}";
			$this->tasks_array[30]="{arpscan}";
			$this->tasks_array[31]="{backup_zarafa_db}";
			$this->tasks_array[32]="{synchronize_mailboxes}";
			$this->tasks_array[33]="{system_load_task}";
			$this->tasks_array[34]="{whitelist_zarafa_contacts}";
			$this->tasks_array[35]="{recompile_postfix_parameters}";
			$this->tasks_array[36]="{restart_mysql_server}";
			$this->tasks_array[37]="{scan_mailattachments_queue_explain}";
			$this->tasks_array[38]="{artica_install_watchdog}";
			$this->tasks_array[39]="{all_status_cache}";
			$this->tasks_array[40]="{setup_center_install}";
			$this->tasks_array[41]="{webinterface_check}";
			$this->tasks_array[42]="{hdparmT}";
			$this->tasks_array[43]="{my_public_ip}";
			$this->tasks_array[44]="{clean_files}";
			$this->tasks_array[45]="{auth_logs}";
			
			
			$this->tasks_array[48]="{postfix_stats_days}";
			$this->tasks_array[49]="{postfix_stats_month}";
			$this->tasks_array[50]="{refresh_kerberos_ticket}";
			$this->tasks_array[51]="{vps_server_watchdog}";
			$this->tasks_array[52]="{postfixlogger_scanner}";
			$this->tasks_array[53]="{snort_scanner}";
			$this->tasks_array[54]="{Sanesecurity_signatures}";
			$this->tasks_array[55]="{myisamchk_task}";
			$this->tasks_array[56]="{syslog_engine}";
			$this->tasks_array[57]="{ps_mem}";
			$this->tasks_array[58]="{tests_speed}";
			$this->tasks_array[59]="{backup_mysql_databases}";
			$this->tasks_array[60]="{mailsync_mailboxes_rules}";
			$this->tasks_array[61]="{clean_mysql_events}";
			$this->tasks_array[62]="{salearnschedule}";
			$this->tasks_array[63]="{kaspersky_updateutility}";
			$this->tasks_array[64]="{milter_greylist_tables}";
			$this->tasks_array[65]="{purge_mysql_logrotate}";
			$this->tasks_array[66]="{restart_artica_web_services}";
			$this->tasks_array[67]="{drop_caches}";
			$this->tasks_array[68]="{update_debian_repository}";
			$this->tasks_array[69]="{APP_CYRUS_BACKUP}";
			$this->tasks_array[70]="{TEXT_TO_CSV}";
			$this->tasks_array[71]="{clean_zarafa_search_indexes}";
			$this->tasks_array[72]="{dns_performance}";
			$this->tasks_array[73]="{dashboard_stats}";
			$this->tasks_array[74]="{mysql_repair}";
			$this->tasks_array[75]="{backup_artica_settings}";
			$this->tasks_array[76]="{blackwhite_automation}";
			$this->tasks_array[77]="{backup_wordpress_websites}";
			$this->tasks_array[78]="{create_a_snapshot}";
			$this->tasks_array[79]="{clean_logs}";
			$this->tasks_array[80]="{send_snapshot_to_meta}";
			
			
			
			$this->tasks_explain_array[1]="{reboot_system_explain}";
			$this->tasks_explain_array[2]="{check_system_updates_explain}";
			$this->tasks_explain_array[3]="{check_acls_explain}";
			$this->tasks_explain_array[4]="{build_cache_users_explain}";
			$this->tasks_explain_array[5]="{check_mysql_performances_explain}";
			$this->tasks_explain_array[6]="{build_available_softwares_explain}";
			$this->tasks_explain_array[7]="{build_installation_status_explain}";
			$this->tasks_explain_array[8]="{task_logrotate_explain}";
			$this->tasks_explain_array[9]="{task_remote_agent_explain}";
			$this->tasks_explain_array[10]="{task_ipbandstatus_explain}";
			$this->tasks_explain_array[11]="{task_ipbandresolv_explain}";
			$this->tasks_explain_array[12]="{task_mysqlclean_explain}";
			$this->tasks_explain_array[13]="{compile_squid_repo_explain}";
			$this->tasks_explain_array[15]="{mysql_tables_maintenance_explain}";
			$this->tasks_explain_array[16]="{mysql_defrag_all_explain}";
			$this->tasks_explain_array[17]="{system_swap_reboot_explain}";
			$this->tasks_explain_array[18]="{clean_logs_task_explain}";
			$this->tasks_explain_array[19]="{check_all_services_status_explain}";
			$this->tasks_explain_array[20]="{pkg_upgrade_interface_explain}";
			$this->tasks_explain_array[21]="{parse_freewebs_scansize_explain}";
			$this->tasks_explain_array[22]="{syncronize_dns_entries_explain}";
			$this->tasks_explain_array[23]="{WebCopy_task_explain}";
			
			$this->tasks_explain_array[24]="{xapian_mysql_dirs_explain}";
			$this->tasks_explain_array[25]="{xapian_shared_folder_explain}";
			$this->tasks_explain_array[26]="{xapian_homes_dir_explain}";
			
			$this->tasks_explain_array[27]="{geoip_update_databases_explain}";
			$this->tasks_explain_array[28]="{restart_webconsole_service_explain}";
			$this->tasks_explain_array[29]="{replic_roudcubes_freewebs_explain}";
			$this->tasks_explain_array[30]="{arpscan_explain}";
			$this->tasks_explain_array[31]="{backup_zarafa_db_explain}";
			$this->tasks_explain_array[32]="{synchronize_mailboxes_explain}";
			$this->tasks_explain_array[33]="{system_load_task_explain}";
			$this->tasks_explain_array[34]="{whitelist_zarafa_contacts_explain}";
			$this->tasks_explain_array[35]="{recompile_postfix_parameters_explain}";
			$this->tasks_explain_array[36]="{restart_mysql_server_explain}";
			$this->tasks_explain_array[37]="{scan_mailattachments_queue}";
			$this->tasks_explain_array[38]="{artica_install_watchdog_explain}";
			$this->tasks_explain_array[39]="{all_status_cache_explain}";
			$this->tasks_explain_array[40]="{setup_center_install_explain}";
			$this->tasks_explain_array[41]="{webinterface_check_explain}";
			$this->tasks_explain_array[42]="{hdparm_explain}";
			$this->tasks_explain_array[43]="{public_ip_explain}";
			$this->tasks_explain_array[44]="{clean_files_explain}";
			$this->tasks_explain_array[45]="{auth_logs_explain}";
			$this->tasks_explain_array[48]="{postfix_stats_days_explain}";
			$this->tasks_explain_array[49]="{postfix_stats_month_explain}";
			$this->tasks_explain_array[50]="{refresh_kerberos_ticket_explain}";
			$this->tasks_explain_array[51]="{vps_server_watchdog_explain}";
			$this->tasks_explain_array[53]="{snort_scanner_explain}";
			$this->tasks_explain_array[54]="{Sanesecurity_signatures_explain}";
			$this->tasks_explain_array[55]="{myisamchk_task_explain}";
			$this->tasks_explain_array[56]="{syslog_engine_explain}";
			$this->tasks_explain_array[57]="{ps_mem_explain}";
			$this->tasks_explain_array[58]="{tests_speed_explain}";
			$this->tasks_explain_array[59]="{backup_mysql_databases_explain}";
			$this->tasks_explain_array[60]="{mailsync_mailboxes_rules_explain}";
			$this->tasks_explain_array[61]="{clean_mysql_events_explain}";
			$this->tasks_explain_array[62]="{salearnschedule_text}";
			$this->tasks_explain_array[63]="{kaspersky_updateutility_text}";
			$this->tasks_explain_array[64]="{milter_greylist_tables_text}";
			$this->tasks_explain_array[65]="{purge_mysql_logrotate_explain}";
			$this->tasks_explain_array[66]="{restart_artica_web_services_explain}";
			$this->tasks_explain_array[67]="{drop_caches_explain}";
			$this->tasks_explain_array[68]="{update_debian_repository_explain}";
			$this->tasks_explain_array[69]="{APP_CYRUS_BACKUP_EXPLAIN}";
			$this->tasks_explain_array[70]="{TEXT_MEMBERS_TO_CSV}";
			$this->tasks_explain_array[71]="{clean_zarafa_search_indexes_explain}";
			
			$this->tasks_explain_array[73]="{dashboard_stats_explain}";
			$this->tasks_explain_array[74]="{mysql_repair_task_explain}";
			$this->tasks_explain_array[75]="{backup_artica_settings_explain}";
			$this->tasks_explain_array[76]="{postfix_automation_about}";
			$this->tasks_explain_array[77]="{backup_wordpress_websites}";
			$this->tasks_explain_array[78]="{create_a_snapshot_explain}";
			$this->tasks_explain_array[79]="{clean_logs_explain}";
			$this->tasks_explain_array[80]="{send_snapshot_to_meta_explain}";
			
			

			$this->tasks_processes[1]="exec.status.php --reboot";
			$this->tasks_processes[2]="exec.apt-get.php --update";
			$this->tasks_processes[3]="exec.acls.php --acls";
			$this->tasks_processes[4]="exec.getent.php --force";
			$this->tasks_processes[5]="exec.mysql.build.php --mysqltuner --force";
			$this->tasks_processes[6]="exec.setup-center.php";
			$this->tasks_processes[7]="exec.setup-center.php --install-status";
			$this->tasks_processes[8]="exec.logrotate.php --run";
			$this->tasks_processes[9]="exec.netagent.php";
			$this->tasks_processes[10]="exec.ipband.php --status";
			$this->tasks_processes[11]="exec.ipband.php --parseresolv";
			$this->tasks_processes[12]="exec.mysql.clean.php";
			$this->tasks_processes[13]="compile-squid32.php --repos";
			
			$this->tasks_processes[15]="exec.mysql.build.php --maintenance";
			$this->tasks_processes[16]="mysqldefrag.php";
			$this->tasks_processes[17]="exec.swapboot.php";
			$this->tasks_processes[18]="exec.clean.logs.php --clean-logs";
			$this->tasks_processes[19]="exec.status.php --all";
			$this->tasks_processes[20]="exec.apt-get.php --pkg-upgrade";
			$this->tasks_processes[21]="exec.freeweb.php --ScanSize";
			$this->tasks_processes[22]="exec.pdns.php --replic-artica";
			$this->tasks_processes[23]="exec.httptrack.php";
			
			$this->tasks_processes[24]="exec.xapian.index.php --mysql-dirs";
			$this->tasks_processes[25]="exec.xapian.index.php --shared";
			$this->tasks_processes[26]="exec.xapian.index.php --homes";
			
			
			$this->tasks_processes[27]="exec.geoip.update.php";
			$this->tasks_processes[28]="exec.lighttpd.nets.php --restart";
			$this->tasks_processes[29]="exec.freeweb.rdcube-replic.php --all";
			$this->tasks_processes[30]="exec.arpscan.php";
			$this->tasks_processes[31]="exec.zarafa-backup.php --exec";
			$this->tasks_processes[32]="exec.mailbox.migration.php --schedules";
			$this->tasks_processes[33]="exec.syslog-engine.php --loadavg";
			$this->tasks_processes[34]="exec.mapiContacts.php";
			$this->tasks_processes[35]="exec.postfix.recompile.task.php";
			$this->tasks_processes[36]="exec.mysql-restart.php";
			$this->tasks_processes[37]="exec.mimedefang.mysql.php";
			$this->tasks_processes[38]="bin:artica-install -watchdog daemon";
			$this->tasks_processes[39]="exec.status.php --all";
			$this->tasks_processes[40]="exec.setup-center.php --install";
			$this->tasks_processes[41]="exec.webinterface-checker.php";
			$this->tasks_processes[42]="exec.hdparm.php";
			$this->tasks_processes[43]="exec.my-rbl.check.php --myip";
			$this->tasks_processes[44]="exec.cleanfiles.php";
			$this->tasks_processes[45]="exec.syslog-engine.php --auth-logs";
			
			$this->tasks_processes[48]="exec.postfix-stats.php --days"; //5mn
			$this->tasks_processes[49]="exec.postfix-stats.php --month"; //5mn
			$this->tasks_processes[50]="exec.kerbauth.php --refresh-ticket"; //5mn
			$this->tasks_processes[51]="exec.vservers.php --watchdog";
			
			$this->tasks_processes[53]="exec.syslog-engine.php --snort";
			//ClamaV
			$this->tasks_processes[54]="exec.scamp.php";
			$this->tasks_processes[55]="exec.mysql.build.php --myisamchk";
			$this->tasks_processes[56]="exec.syslog-engine.php";
			$this->tasks_processes[57]="exec.syslog-engine.php --psmem";
			$this->tasks_processes[58]="exec.testspeed.php";
			$this->tasks_processes[59]="exec.mysql.backup.php";
			$this->tasks_processes[60]="exec.mailsync.php --alltasks";
			$this->tasks_processes[61]="exec.clean.logs.php --cleandb";
			$this->tasks_processes[62]="exec.zarafa.learn.php";
			$this->tasks_processes[63]="exec.keepup2date.php --UpdateUtility";
			$this->tasks_processes[64]="exec.postfix.miltergrey.stats.php";
			$this->tasks_processes[65]="exec.logrotate.php --clean";
			$this->tasks_processes[66]="exec.initslapd.php --restart-www";
			$this->tasks_processes[67]="exec.drop-caches.php";
			$this->tasks_processes[68]="exec.debian.mirror.php --schedule";
			$this->tasks_processes[69]="exec.cyrus.backup.php --schedule";
			$this->tasks_processes[70]="exec.TextToLdap.php --schedule";
			$this->tasks_processes[71]="exec.zarafa-search.php --clean";
			
			$this->tasks_processes[73]="exec.loadavg.php --dns";
			$this->tasks_processes[74]="exec.mysql.optimize.php --repair";
			$this->tasks_processes[75]="exec.backup.artica.php";
			$this->tasks_processes[76]="exec.imap.blacklists.php";
			$this->tasks_processes[77]="exec.wordpress-backup.php --exec";
			$this->tasks_processes[78]="exec.backup.artica.php --snapshot";
			$this->tasks_processes[79]="exec.clean.varlog.php";
			$this->tasks_processes[80]="exec.backup.artica.php --snapshot --force --meta-ping";
			
			$this->DisableTasks();
	
	}
	
private function DisableTasks(){
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql();
	$EnableSnort=$sock->GET_INFO("EnableSnort");
	$LXCEnabled=$sock->GET_INFO("LXCEnabled");
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}	
	if(!is_numeric($EnableSnort)){$EnableSnort=0;}
	if(!is_numeric($LXCEnabled)){$LXCEnabled=0;}
	if(!$users->SNORT_INSTALLED){$EnableSnort=0;}
	if(!$users->LXC_INSTALLED){$LXCEnabled=0;}
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){$ActAsASyslogClient=1;}
	if($SambaEnabled==0){$users->SAMBA_INSTALLED=false;}
	
	
	if(!$users->ZARAFA_INSTALLED){
		$this->task_disabled[31]=true;
	}
	
	$MEMORY=$users->MEM_TOTAL_INSTALLEE;
	
	//setup-center
	$this->task_disabled[7]=true;
	$this->task_disabled[40]=true;
	$this->task_disabled[4]=true;
	$this->task_disabled[38]=true;
	$this->task_disabled[46]=true;
	$this->task_disabled[47]=true;
	$this->task_disabled[14]=true;
	$this->task_disabled[52]=true;
	
	
	if($EnableIntelCeleron==1){
		$this->task_disabled[51]=true;
		$this->task_disabled[57]=true;
	}
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	if($SquidPerformance>2){
		$this->task_disabled[4]=true;
		$this->task_disabled[45]=true;
	}
	
	if($MEMORY<624288){
		$this->task_disabled[14]=true;
		$this->task_disabled[24]=true;
		$this->task_disabled[25]=true;
		$this->task_disabled[26]=true;
		$this->task_disabled[39]=true;
		$this->task_disabled[68]=true;
		$this->task_disabled[69]=true;
		$this->task_disabled[70]=true;
		$this->task_disabled[71]=true;
		$this->task_disabled[72]=true;
	}
	
	if($ActAsASyslogServer==1){
		$EnableSnort=1;
		$users->POSTFIX_INSTALLED=true;
	}
	
	if($ActAsASyslogClient==1){
		$EnableSnort=0;
		$users->POSTFIX_INSTALLED=false;
	}
	
	if($EnableSnort==0){$this->task_disabled[53]=true;}
	
	if($EnableRemoteStatisticsAppliance==0){
		$this->task_disabled[9]=true;
	}
	
	
	if($q->COUNT_ROWS("ipban","artica_backup")==0){$users->IPBAN_INSTALLED=false;}
	
	if($LXCEnabled==0){
		$this->task_disabled[51]=true;
	}
	
	if(!$users->POSTFIX_INSTALLED){
		$this->task_disabled[52]=true;
		$this->task_disabled[46]=true;
		$this->task_disabled[48]=true;
		$this->task_disabled[49]=true;
		$this->task_disabled[37]=true;
		$this->task_disabled[35]=true;
		$this->task_disabled[60]=true;
		$this->task_disabled[64]=true;
		$this->task_disabled[47]=true;
		$this->task_disabled[48]=true;
		$this->task_disabled[52]=true;
	}
	
	if(!$users->ZARAFA_INSTALLED){
		$this->task_disabled[31]=true;
		$this->task_disabled[34]=true;
		$this->task_disabled[62]=true;
	}
	
	
	if(!$users->IPBAN_INSTALLED){
		$this->task_disabled[10]=true;
		$this->task_disabled[11]=true;
	}
	
	if(!$users->SNORT_INSTALLED){
		$this->task_disabled[53]=true;
	}
	
	if(!$users->MILTERGREYLIST_INSTALLED){
		$this->task_disabled[64]=true;
	}
	
	if(!$users->RSYNC_INSTALLED){
		$this->task_disabled[68]=true;
	}
	
	
}
	
	
public function CheckDefaultSchedules(){
		$sock=new sockets();
		$q=new mysql();
		if($GLOBALS["VERBOSE"]){echo "CheckDefaultSchedules OK\n";}
		if(!$q->TABLE_EXISTS('system_schedules',"artica_backup")){	
			$q->BuildTables();

			if(!$q->TABLE_EXISTS('system_schedules',"artica_backup")){	
				writelogs("Fatal!!! system_schedules table did not exists",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}	
		
		$update=false;
		$array[31]=array("TimeText"=>"05 00 * * *","TimeDescription"=>"Backup Zarafa server each 00h05");
		$array[12]=array("TimeText"=>"30 23 * * *","TimeDescription"=>"Clean tables each day at 23h30");
		$array[55]=array("TimeText"=>"15 5 * * *","TimeDescription"=>"myisamchk each day at 05h15");
		$array[5]=array("TimeText"=>"30 7 * * *","TimeDescription"=>"MySQLTuner each day at 07h30");
		$array[4]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
		$array[6]=array("TimeText"=>"0 4,8,12,16,20 * * *","TimeDescription"=>"Each 4 hours");
		$array[2]=array("TimeText"=>"30 0,5,10,15,20 * * *","TimeDescription"=>"Each 5 Hours");
		$array[8]=array("TimeText"=>"5 0 * * *","TimeDescription"=>"Each day at 00:05");
		$array[9]=array("TimeText"=>"10,20,30,40,50 * * * *","TimeDescription"=>"Each 10mn");
		$array[10]=array("TimeText"=>"0,15,30,45 * * * *","TimeDescription"=>"Each 15mn");
		$array[11]=array("TimeText"=>"0,30 * * * *","TimeDescription"=>"Each 30mn");
	
		$array[15]=array("TimeText"=>"30 16 * * 0","TimeDescription"=>"Defrag MySQL databases each Sunday at 16h30");
		$array[18]=array("TimeText"=>"0 23 * * *","TimeDescription"=>"Clean temp logs each day at 23h00");
		
		$array[21]=array("TimeText"=>"30 21 * * *","TimeDescription"=>"Calculate www disk usage each day at 21h30");
		$array[27]=array("TimeText"=>"15 23 2,4,6,8,10,12,14,16,18,20,22,24,26,28,30 * *","TimeDescription"=>"Update GeoIP each 48H at 23h15");
		
		$array[30]=array("TimeText"=>"30 4,8,12,16,20 * * *","TimeDescription"=>"Perform ArpScan Each 4h and 30 mins");
		$array[33]=array("TimeText"=>"0,10,20,30,40,50 * * * *","TimeDescription"=>"Each 10mn");
		$array[38]=array("TimeText"=>"5,10,15,20,25,30,35,40,45,50,55,59 * * * *","TimeDescription"=>"Each 5mn");
		$array[39]=array("TimeText"=>"3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *","TimeDescription"=>"Each 3mn");
		$array[40]=array("TimeText"=>"* * * * *","TimeDescription"=>"Each 1mn");
		$array[41]=array("TimeText"=>"0,15,30 * * * *","TimeDescription"=>"Each 15mn");
		$array[42]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
		$array[43]=array("TimeText"=>"30 * * * *","TimeDescription"=>"Each hour + 30mn");
		$array[44]=array("TimeText"=>"35 * * * *","TimeDescription"=>"Each hour + 35mn");
		$array[45]=array("TimeText"=>"3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *","TimeDescription"=>"Each 3mn");
		$array[46]=array("TimeText"=>"10 0,2,4,6,8,10,12,14,16,18,20,22 * * *","TimeDescription"=>"Each 2H +10mn");
		$array[47]=array("TimeText"=>"5,10,15,20,25,30,35,40,45,50,55,59 * * * *","TimeDescription"=>"parse queue Each 5mn");
		
		
		$array[52]=array("TimeText"=>"2,7,12,17,22,27,32,37,42,47,52,57 * * * *","TimeDescription"=>"parse queue Each 5mn");
		$array[53]=array("TimeText"=>"* * * * *","TimeDescription"=>"Scan snort results Each 1mn");
		$array[56]=array("TimeText"=>"5,20,35 * * * *","TimeDescription"=>"Each 15mn");
		$array[57]=array("TimeText"=>"7,22,37 * * * *","TimeDescription"=>"Each 15mn");
		$array[58]=array("TimeText"=>"12 * * * *","TimeDescription"=>"Bandwidth Checker Each hour +12mn");
		$array[61]=array("TimeText"=>"30 8 * * *","TimeDescription"=>"Clean tasks events each day at 08h30");
		
		$array[65]=array("TimeText"=>"30 18 * * 0","TimeDescription"=>"Purge logs storage MySQL database each Sunday at 18h30");
		$array[66]=array("TimeText"=>"14 0,3,6,9,12,15,18,21 * * *","TimeDescription"=>"Restart Artica Web consoles each 3h");
		
		$array[72]=array("TimeText"=>"0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *","TimeDescription"=>"DNS statistics each 2mn");
		
		$array[74]=array("TimeText"=>"30 3,6,12,18,22 * * *","TimeDescription"=>"Repair MySQL tables - non productive hours");
		
		
		$c=0;
		while (list ($TaskType, $content) = each ($array) ){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,TimeText FROM system_schedules WHERE TaskType=$TaskType LIMIT 0,1","artica_backup"));
			$ID=intval($ligne["ID"]);
			if($GLOBALS["VERBOSE"]){echo "TaskType $TaskType = $ID \n";}
			
			if($ID>0){
				if($GLOBALS["VERBOSE"]){echo "TaskType $TaskType = $ID OK\n";}
				continue;
			}
			if($ligne["TimeText"]<>null){continue;}
			
			$sql="INSERT IGNORE INTO system_schedules (TimeDescription,TimeText,TaskType,enabled) VALUES('{$content["TimeDescription"]}','{$content["TimeText"]}','$TaskType',1)";							
			if($GLOBALS["VERBOSE"]){echo "$sql\n";}
			
			writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
				
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "TaskType $TaskType = $ID $q->mysql_error\n";}
				writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
				$sock->TOP_NOTIFY("$q->mysql_error :".__FUNCTION__." File:".__FILE__);return;}
				$c++;
				$update=true;
			}
			
			if($update){
				$sock=new sockets();
				$sock->getFrameWork("services.php?build-schedules=yes");
				$sock->TOP_NOTIFY("{some_system_default_schedules_has_been_added}: $c {tasks}","info");
			}
			
		
	}
		
	
	
	
}