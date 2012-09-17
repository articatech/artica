<?php

class system_tasks{
	var $tasks_array=array();
	var $tasks_explain_array=array();
	var $tasks_processes=array();
	public function system_tasks(){
		$this->fill_task_array();
		$q=new mysql();
		$tasknum=$q->COUNT_ROWS("system_schedules","artica_backup");
		writelogs("Tasks: ".$tasknum,__FUNCTION__,__FILE__,__LINE__);
		if($q->COUNT_ROWS("system_schedules","artica_backup")==0){$this->CheckDefaultSchedules();}else{
			
		}
		
		
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
			$this->tasks_array[14]="{scan_filesystem_size}";
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
			$this->tasks_array[46]="{awstats_postfix}";
			$this->tasks_array[47]="{awstats_postfix_parser}";
			$this->tasks_array[48]="{postfix_stats_days}";
			
			
			
			
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
			$this->tasks_explain_array[14]="{scan_filesystem_size_explain}";
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
			$this->tasks_explain_array[46]="{awstats_postfix_explain}";
			$this->tasks_explain_array[47]="{awstats_postfix_parser_explain}";
			$this->tasks_explain_array[48]="{postfix_stats_days_explain}";
			
			
			
			

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
			$this->tasks_processes[14]="exec.philesight.php --run";  
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
			$this->tasks_processes[46]="exec.awstats.php --postfix"; //120
			$this->tasks_processes[47]="exec.postfix.parse.awstats.php"; //5mn
			$this->tasks_processes[48]="exec.postfix-stats.php --days"; //5mn
			
	
	}
	
	
   
	
	
public function CheckDefaultSchedules(){
		$sock=new sockets();
		$q=new mysql();
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!$q->TABLE_EXISTS('system_schedules',"artica_backup")){	
			$q->BuildTables();

			if(!$q->TABLE_EXISTS('system_schedules',"artica_backup")){	
				writelogs("Fatal!!! $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}	
		
		$update=false;
		$array[12]=array("TimeText"=>"30 23 * * *","TimeDescription"=>"Clean tables each day at 23h30");
		$array[4]=array("TimeText"=>"30 7 * * *","TimeDescription"=>"MySQLTuner each day at 07h30");
		$array[5]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
		$array[6]=array("TimeText"=>"0 4,8,12,16,20 * * *","TimeDescription"=>"Each 4 hours");
		$array[2]=array("TimeText"=>"30 0,5,10,15,20 * * *","TimeDescription"=>"Each 5 Hours");
		$array[8]=array("TimeText"=>"5 0 * * *","TimeDescription"=>"Each day at 00:05");
		if($EnableRemoteStatisticsAppliance==1){$array[9]=array("TimeText"=>"10,20,30,40,50 * * * *","TimeDescription"=>"Each 10mn");}
		$array[10]=array("TimeText"=>"0,15,30,45 * * * *","TimeDescription"=>"Each 15mn");
		$array[11]=array("TimeText"=>"0,30 * * * *","TimeDescription"=>"Each 30mn");
		$array[14]=array("TimeText"=>"0 4 * * 0,2,5","TimeDescription"=>"Scan disks each 2 days");
		$array[15]=array("TimeText"=>"30 16 * * 0","TimeDescription"=>"Defrag MySQL databases each Sunday at 16h30");
		$array[18]=array("TimeText"=>"0 23 * * *","TimeDescription"=>"Clean temp logs each day at 23h00");
		$array[20]=array("TimeText"=>"45 23 * * *","TimeDescription"=>"Try to upgrade selected packages each day at 23h45");
		$array[21]=array("TimeText"=>"30 21 * * *","TimeDescription"=>"Calculate www disk usage each day at 21h30");
		$array[27]=array("TimeText"=>"15 23 2,4,6,8,10,12,14,16,18,20,22,24,26,28,30 * *","TimeDescription"=>"Update GeoIP each 48H at 23h15");
		$array[20]=array("TimeText"=>"45 22 * * *","TimeDescription"=>"Restart Web console service each day at 22h45");
		$array[30]=array("TimeText"=>"30 4,8,12,16,20 * * *","TimeDescription"=>"Perform ArpScan Each 4h and 30 mins");
		$array[33]=array("TimeText"=>"0,10,20,30,40,50 * * * *","TimeDescription"=>"Each 10mn");
		$array[38]=array("TimeText"=>"5,10,15,20,25,30,35,40,45,50,55,59 * * * *","TimeDescription"=>"Each 5mn");
		$array[39]=array("TimeText"=>"3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *","TimeDescription"=>"Each 3mn");
		$array[40]=array("TimeText"=>"* * * * *","TimeDescription"=>"Each 1mn");
		$array[41]=array("TimeText"=>"0,15,30 * * * *","TimeDescription"=>"Each 15mn");
		$array[42]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
		$array[43]=array("TimeText"=>"30 * * * *","TimeDescription"=>"Each hour + 30mn");
		$array[44]=array("TimeText"=>"35 * * * *","TimeDescription"=>"Each hour + 35mn");
		$array[45]=array("TimeText"=>"* * * * *","TimeDescription"=>"Each 1mn");
		$array[46]=array("TimeText"=>"10 0,2,4,6,8,10,12,14,16,18,20,22 * * *","TimeDescription"=>"Each 2H +10mn");
		$array[47]=array("TimeText"=>"5,10,15,20,25,30,35,40,45,50,55,59 * * * *","TimeDescription"=>"parse queue Each 5mn");
		$array[48]=array("TimeText"=>"45 0,5,10,15,20 * * *","TimeDescription"=>"Days statistics each 5 Hours +45mn");
		
		
		
			
		$c=0;
		while (list ($TaskType, $content) = each ($array) ){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,TimeText FROM system_schedules WHERE TaskType=$TaskType LIMIT 0,1","artica_backup"));
			$ID=$ligne["ID"];
			if(is_numeric($ID)){if($ID>0){continue;}}
			if($ligne["TimeText"]<>null){continue;}
			
			$sql="INSERT IGNORE INTO system_schedules (TimeDescription,TimeText,TaskType,enabled) VALUES('{$content["TimeDescription"]}','{$content["TimeText"]}','$TaskType',1)";							
			writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
				
				if(!$q->ok){
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