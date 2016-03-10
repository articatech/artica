<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.influx.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_POST["InfluxUseRemote"])){InfluxUseRemote();exit;}
	if(isset($_GET["service-status"])){service_status();exit;}
	if(isset($_GET["parameters"])){page();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_POST["InfluxAdminRetentionTime"])){InfluxAdminEnabled_save();exit;}
	if(isset($_POST["ActAsASyslogServerCheck"])){ActAsASyslogServerCheck();exit;}
	
tabs();


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();

	$array["parameters"]="{parameters}";
	$array["clients"]="{clients}";
	//$array["events"]="{events}";
	$array["artica-events"]="{events}: Artica";
	

	$fontsize=22;

	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$tab[]="<li><a href=\"influx.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="artica-events"){
			$tab[]="<li><a href=\"influx.artica-events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		if($num=="clients"){
			$tab[]="<li><a href=\"influx.clients.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="update"){
			$tab[]="<li><a href=\"influx.update.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		

		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}



	$t=time();
	//

	echo build_artica_tabs($tab, "influxdb_main_table",1490)."<script>LeftDesign('management-console-256.png');</script>";



}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$version=$sock->getFrameWork("postgres.php?version=yes");
	$title=$tpl->_ENGINE_parse_body("PostgreSQL $version");
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	
	$update="<center style='margin-top:10px'>". button("{update}","GotoInfluxUpdate()",28,447)."</center>";
	$update=null;
	
	if($EnableInfluxDB==1){
		$others="		<center style='margin-top:10px'>". button("{databases}","Loadjs('influxdb.databases.php')",28,447)."</center>
		$update
		<center style='margin-top:10px'>". button("{restart_service}","Loadjs('postgres.progress.php')",28,447)."</center>
		<center style='margin-top:10px'>". button("{backup_now}","Loadjs('postgres.backup.progress.php')",28,447)."</center>
		<center style='margin-top:10px'>". button("{restore}","Loadjs('postgres.restore.php')",28,447)."</center>";
		$bdis=button("{disable_service}","Loadjs('influxdb.disable.progress.php')",28,447);
	}else{
		$bdis=button("{enable_service}","Loadjs('influxdb.enable.progress.php')",28,447);
		$others=null;
	}
	
	$remove="<center style='margin-top:10px'>". button("{REMOVE_DATABASE}",
			"Loadjs('postgres.remove.progress.php')",28,447)."</center>";
	
	
	echo $tpl->_ENGINE_parse_body("
			
	<table style='widht:100%'>
	<tr>
		<td width=550px valign='top'>
			<div id='influx-db-status' style='margin-bottom:10px'></div>
			<div id='influx-db-size'></div>
		$others
		$remove
		<center style='margin-top:10px'>$bdis</center>
		
			
		
		
		
		</td>
		<td valign='top' width=950px><div style='font-size:42px;margin-bottom:20px'>$title</div>
		<div id='settings$t'></div>
		</td>
	</tr>
	</table>
	
	<script>
		Loadjs('$page?graph1=yes');
		LoadAjaxRound('settings$t','$page?settings=yes');
	</script>		
			
	");
}


function settings(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$influx=new influx();
	$t=time();
	$ERROR_PERF=null;
	$InfluxAdminDisabled=intval($sock->GET_INFO("InfluxAdminDisabled"));
	$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
	if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	$InfluxAdminEnabled=1;
	if($InfluxAdminDisabled==1){$InfluxAdminEnabled=0;}
	$InfluxAdminRetentionTime=intval($sock->GET_INFO("InfluxAdminRetentionTime"));
	$UserAgentsStatistics=intval($sock->GET_INFO("UserAgentsStatistics"));
	$ResolvIPStatistics=intval($sock->GET_INFO("ResolvIPStatistics"));
	$EnableQuotasStatistics=intval($sock->GET_INFO("EnableQuotasStatistics"));
	$QuotasStatisticsInterval=intval($sock->GET_INFO("QuotasStatisticsInterval"));
	$InfluxListenInterface=$sock->GET_INFO("InfluxListenInterface");
	if($InfluxListenInterface==null){$InfluxListenInterface="lo";}
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$MySQLStatisticsRetentionDays=intval($sock->GET_INFO("MySQLStatisticsRetentionDays"));
	if($MySQLStatisticsRetentionDays==0){$MySQLStatisticsRetentionDays=5;}
	$PostGresBackupMaxContainers=intval($sock->GET_INFO("PostGresBackupMaxContainers"));
	if($PostGresBackupMaxContainers==0){$PostGresBackupMaxContainers=3;}
	$CalamarisSchedules=intval($sock->GET_INFO("CalamarisSchedules"));
	
	$sock->getFrameWork("postgres.php?PostGresSQLDatabaseDirectory=yes");
	$PostGresSQLDatabaseDirectory=$sock->GET_INFO("PostGresSQLDatabaseDirectory");
	
	
	
	
	$bt_disconnect=null;
	$STATS_APPLIANCE=0;
	$sys=new networking();
	$influxstop=null;
	$Local_interfaces=$sys->Local_interfaces();
	if(!$users->STATS_APPLIANCE){
		$Local_interfaces["lo"]="loopback";
	}else{
		$STATS_APPLIANCE=1;
	}
	$Local_interfaces["ALL"]="{all}";
	
	if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
	$users=new usersMenus();
	
	$InfluxAdminRetention[7]="7 {days}";
	$InfluxAdminRetention[15]="15 {days}";
	$InfluxAdminRetention[30]="1 {month}";
	$InfluxAdminRetention[90]="3 {months}";
	$InfluxAdminRetention[180]="6 {months}";
	$InfluxAdminRetention[365]="1 {year}";
	$InfluxAdminRetention[730]="2 {years}";
	$InfluxAdminRetention[1095]="3 {years}";
	
	
	$MySQLStatisticsRetention[1]="1 {day}";
	$MySQLStatisticsRetention[2]="2 {days}";
	$MySQLStatisticsRetention[3]="3 {days}";
	$MySQLStatisticsRetention[4]="4 {days}";
	$MySQLStatisticsRetention[5]="5 {days}";
	$MySQLStatisticsRetention[6]="6 {days}";
	$MySQLStatisticsRetention[7]="7 {days}";
	$MySQLStatisticsRetention[8]="8 {days}";
	$MySQLStatisticsRetention[9]="9 {days}";
	$MySQLStatisticsRetention[10]="10 {days}";
	
	
	
	
	$QuotasStatisticsIntervalA[5]="5 {minutes}";
	$QuotasStatisticsIntervalA[10]="10 {minutes}";
	$QuotasStatisticsIntervalA[15]="15 {minutes}";
	$QuotasStatisticsIntervalA[30]="30 {minutes}";
	
	
	
	
	
	$CalamarisSchedulesZ[0]="{disabled}";
	$CalamarisSchedulesZ[1]="{each} 1 {hour}";
	$CalamarisSchedulesZ[2]="{each} 2 {hours}";
	$CalamarisSchedulesZ[3]="{each} 4 {hours}";
	$CalamarisSchedulesZ[4]="{each} 6 {hours}";
	
	
	if($QuotasStatisticsInterval==0){$QuotasStatisticsInterval=15;}
	
	
	$CORP_LICENSE=1;
	$explain_retention="&nbsp;";
	
	$field_ret=Field_array_Hash($InfluxAdminRetention,
				"InfluxAdminRetentionTime","$InfluxAdminRetentionTime","blur()",null,0,
				"font-size:22px");
	
	if(!$users->CORP_LICENSE){
		
		$InfluxAdminRetentionTime=5;$CORP_LICENSE=0;
		$field_ret=Field_hidden("InfluxAdminRetentionTime", 365)."5 {days}<div><i style='font-size:16px'>{retention_time_limited_license}</i></div>";
	
	}
	
	if($EnableInfluxDB==0){
		
		$influxstop="<div class=explain style='font-size:18px'>{influxdb_is_disabled}</div>";
		
		
	}
	
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$InFluxBackupDatabaseMaxContainers=intval("InFluxBackupDatabaseMaxContainers");
	if($InFluxBackupDatabaseMaxContainers==0){$InFluxBackupDatabaseMaxContainers=5;}
	$InFluxBackupDatabaseInterval=intval("InFluxBackupDatabaseInterval");
	if($InFluxBackupDatabaseInterval==0){$InFluxBackupDatabaseInterval=10080;}
	if($InFluxBackupDatabaseInterval<1440){$InFluxBackupDatabaseInterval=1440;}	
	$influxdb_snapshotsize=@file_get_contents("{$GLOBALS["BASEDIR"]}/influxdb_snapshotsize");
	$InfluxDBAllowBrowse=intval($sock->GET_INFO("InfluxDBAllowBrowse"));
	
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$InfluxUseRemoteIpaddr=$sock->GET_INFO("InfluxUseRemoteIpaddr");
	$InfluxRemoteDB=$sock->GET_INFO("InfluxRemoteDB");
	$InfluxUseRemotePort=intval($sock->GET_INFO("InfluxUseRemotePort"));
	$InfluxUseRemoteArticaPort=intval($sock->GET_INFO("InfluxUseRemoteArticaPort"));
	if($InfluxRemoteDB==null){$InfluxRemoteDB=$influx->db;}
	if($InfluxUseRemotePort==0){$InfluxUseRemotePort=5432;}
	if($InfluxUseRemotePort==8086){$InfluxUseRemotePort=5432;}
	if($InfluxUseRemoteArticaPort==0){$InfluxUseRemoteArticaPort=9000;}
	$InfluxDBPassword=$sock->GET_INFO("InfluxDBPassword");
	$ArticaInfluxUsername=$sock->GET_INFO("ArticaInfluxUsername");
	if($ArticaInfluxUsername==null){$ArticaInfluxUsername="Manager";}
	$InfluxSyslogRemote=intval($sock->GET_INFO("InfluxSyslogRemote"));
	$NoCompressStatisticsByHour=intval($sock->GET_INFO("NoCompressStatisticsByHour"));
	$ArticaInfluxPassword=$sock->GET_INFO("ArticaInfluxPassword");
	$InfluxRestartMem=intval($sock->GET_INFO("InfluxRestartMem"));
	$ActAsASyslogServer=intval($sock->GET_INFO("ActAsASyslogServer"));
	
	$PostgreSQLSharedBuffer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLSharedBuffer"));
	if($PostgreSQLSharedBuffer==0){$PostgreSQLSharedBuffer=32;}
	
	$PostgreSQLEffectiveCacheSize=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLEffectiveCacheSize"));
	if($PostgreSQLEffectiveCacheSize==0){$PostgreSQLEffectiveCacheSize=256;}
	
	$PostgreSQLWorkMem=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLWorkMem"));
	if($PostgreSQLWorkMem==0){$PostgreSQLWorkMem=4;}
	
	if($SquidPerformance>2){
		
		$ERROR_PERF="<p class=text-error style='font-size:16px'>{INFLUX_DISABLED_PROXY_PERFORMANCE}</p>
		<div style='margin-top:10px;text-align:right'>". button("{performance}", "GotoSquidPerformances()",16)."</div>";
		
	}
	

	
	if($InfluxUseRemote==1){
		if($InfluxUseRemoteIpaddr<>null){
			$bt_disconnect="
			<tr>
			<td class=legend style='font-size:22px;text-align:right' colspan=2><p>&nbsp;</p></td>
			</tr>
			<tr>
			<td class=legend style='font-size:22px;text-align:right' colspan=2>". button("{disconnect}","Loadjs('influxdb.disconnect.progress.php');",22)."</td>
			</tr>";
		}
		
	}
	
	
	$Intervals[1440]="1 {day}";
	$Intervals[2880]="1 {days}";
	$Intervals[7200]="5 {days}";
	$Intervals[10080]="1 {week}";
	$Intervals[20160]="2 {weeks}";
	
	for($i=0;$i<100;$i++){
		$PostGresBackupMaxContainersHASH[$i]=$i;
	}
	
	$password="	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>		
		<td style='font-size:22px;font-weight:bold'>".Field_password("InfluxDBPassword",$InfluxDBPassword,"font-size:22px;width:310px")."</td>
		<td style='font-size:22px;text-decoration:underline'>{username}: &laquo;root&raquo;</td>
	</tr>";
	
	
	$date_start=$tpl->time_to_date(intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_START")),true);
	$date_end=$tpl->time_to_date(intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_END")),true);
	
	$html="
	$ERROR_PERF		
	<div style='width:98%;margin-top:20px' class=form>
	$influxstop
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{date_start}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>$date_start</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{last_date}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>$date_end</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{refresh}","Loadjs('influxdb.refresh.progress.php')")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{ResolvIPStatistics}","{ResolvIPStatistics_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("ResolvIPStatistics", 1,$ResolvIPStatistics,"")."</td>
	</tr>	
		<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{MySQLStatisticsRetentionDays}","{MySQLStatisticsRetentionDays_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($MySQLStatisticsRetention,"MySQLStatisticsRetentionDays","$MySQLStatisticsRetentionDays","blur()",null,0,"font-size:22px")."</td>
	</tr>	
	
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{calamaris_statistics}","{calamaris_statistics_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($CalamarisSchedulesZ,"CalamarisSchedules","$CalamarisSchedules","blur()",null,0,"font-size:22px")."</td>
		
	</tr>					
	<tr>
		<td class=legend style='font-size:22px' nowrap>{useragents_statistics}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("UserAgentsStatistics", 1,$UserAgentsStatistics,"")."</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{quota_statistics}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("EnableQuotasStatistics", 1,$EnableQuotasStatistics,"EnableQuotasStatisticsCheck()")."</td>
		
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{interval}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($QuotasStatisticsIntervalA,"QuotasStatisticsInterval","$QuotasStatisticsInterval","blur()",null,0,"font-size:22px")."</td>
	</tr>	
	</table>
	<div id='influx-local-service-id'>
	<table style='width:100%'>
	<tr style='height:80px;'>
		<td colspan=3 style='font-size:30px'>{service_parameters}:</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{listen_interface}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($Local_interfaces,
				"InfluxListenInterface","$InfluxListenInterface","blur()",null,0,"font-size:22px")."</td>
		<td style='font-size:22px;font-weight:bold'>$InfluxListenInterface:5432</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>". texttooltip("{enable_syslog_server}","{enable_syslog_server_text}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("ActAsASyslogServer", 1,$ActAsASyslogServer,"ActAsASyslogServerCheck()")."</td>
	</tr>	

<tr>
		<td class=legend style='font-size:22px' nowrap>". texttooltip("{database_storage_path}","{database_storage_path}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>$PostGresSQLDatabaseDirectory&nbsp;".button("{edit}", "Loadjs('postgres.directory.php')",16)."</td>
	</tr>		
				
	
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>". texttooltip("{restart_if_memory_exceed}","{influx_restart_if_memory_exceed}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_text("InfluxRestartMem",$InfluxRestartMem,"font-size:22px;width:180px")."&nbsp;MB</td>
	</tr>		
		<tr>
			<td class=legend style='font-size:22px' nowrap>".texttooltip("{shared_buffer}","{PostgreSQLSharedBuffer}").":</td>		
			<td style='font-size:22px;font-weight:bold'>".Field_text("PostgreSQLSharedBuffer", $PostgreSQLSharedBuffer,"font-size:22px;width:110px")."&nbsp;MB</td>
		</tr>								
		<tr>
			<td class=legend style='font-size:22px' nowrap>".texttooltip("{effective_cache_size}","{PostgreSQLEffectiveCacheSize}").":</td>		
			<td style='font-size:22px;font-weight:bold'>".Field_text("PostgreSQLEffectiveCacheSize", $PostgreSQLEffectiveCacheSize,"font-size:22px;width:110px")."&nbsp;MB</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px' nowrap>".texttooltip("{work_mem}","{PostgreSQLWorkMem}").":</td>		
			<td style='font-size:22px;font-weight:bold'>".Field_text("PostgreSQLWorkMem", $PostgreSQLWorkMem,"font-size:22px;width:110px")."&nbsp;MB</td>
		</tr>		
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{retention_time}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>$field_ret$explain_retention</td>
	</tr>
	
	
	
	<tr style='height:80px;'>
		<td colspan=3 align='right' style='padding-top:30px'><hr>". button("{apply}","Save$t()",40)."</td>
	</tr>	
	</table>
	</div>
				
				
				
				
	<div id='influx-remote-service-id'>
		<table style='width:100%'>
		<tr style='height:80px;'>
			<td colspan=3 style='font-size:30px'>{remote_server}:</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px' nowrap>{use_statistics_appliance}:</td>		
			<td style='font-size:22px;font-weight:bold'>".Field_checkbox_design("InfluxUseRemote", 1,$InfluxUseRemote,"InfluxUseRemoteCheck()")."</td>
		</tr>	
		</table>
		<div id='influx-remote-service-options'>
			<table style='width:100%'>
$bt_disconnect					
		<tr>
			<td class=legend style='font-size:22px' nowrap>".texttooltip("{send_syslog_logs}","{send_syslog_explain}").":</td>		
			<td style='font-size:22px;font-weight:bold'>".Field_checkbox_design("InfluxSyslogRemote", 1,$InfluxSyslogRemote)."</td>
		</tr>						
			<tr>
				<td class=legend style='font-size:22px' nowrap>{remote_server_address}:</td>		
				<td style='font-size:22px;font-weight:bold'>".Field_text("InfluxUseRemoteIpaddr",
						$InfluxUseRemoteIpaddr,"font-size:22px;width:291px")."</td>
				
			</tr>
									

								
			<tr>
				<td class=legend style='font-size:22px'>{remote_port}:</td>		
				<td style='font-size:22px;font-weight:bold'>".Field_text("InfluxUseRemotePort",$InfluxUseRemotePort,"font-size:22px;width:120px")."</td>
			</tr>
				<tr>
					<td class=legend style='font-size:22px'>{artica_username}:</td>
					<td style='font-size:18px'>". Field_text("ArticaInfluxUsername",$ArticaInfluxUsername,"font-size:22px;width:240px")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{artica_password}:</td>
					<td style='font-size:18px'>". Field_password("ArticaInfluxPassword",$ArticaInfluxPassword,"font-size:22px;width:240px")."</td>
				</tr>				
			<tr>
				<td class=legend style='font-size:22px'>{remote_artica_port}:</td>		
				<td style='font-size:22px;font-weight:bold'>".Field_text("InfluxUseRemoteArticaPort",$InfluxUseRemoteArticaPort,"font-size:22px;width:120px")."</td>
			</tr>				
			<tr style='height:80px;'>
				<td colspan=2 align='right' style='padding-top:30px'>
							<hr>". button("{apply}","SaveRemote$t()",40)."</td>
			</tr>
						
			</table>
		</div>
	</div>			
				
	<div id='influx-backup-service-id'>
	<table style='width:100%'>
	<tr style='height:80px;'>
		<td colspan=3 style='font-size:26px'>{backup}:</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{backup_each}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($Intervals,
					"InFluxBackupDatabaseInterval","$InFluxBackupDatabaseInterval","blur()",null,0,"font-size:22px")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{ExecBackupMaxContainers}:</td>		
		<td style='font-size:22px;font-weight:bold'>".Field_array_Hash($PostGresBackupMaxContainersHASH,
		"PostGresBackupMaxContainers","$PostGresBackupMaxContainers","blur()",null,0,"font-size:22px")."</td>
		<td>". button("{backup_now}","Loadjs('postgres.backup.progress.php')",18)."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px' nowrap>{backup_directory}:</td>		
		<td style='font-size:22px;font-weight:bold'>".Field_text("InFluxBackupDatabaseDir",
				$InFluxBackupDatabaseDir,"font-size:22px;width:291px")."</td>
		<td>". button_browse("InFluxBackupDatabaseDir")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap colspan=2 align='right'>
				<a href=\"/backup-influx/\" style='text-decoration:underline'>{backup_directory} ". FormatBytes($influxdb_snapshotsize/1024)."</a>
		</td>
		<td>&nbsp;</td>
	</tr>	

						
						
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{allow_browse_directory}","{allow_browse_directory_web_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("InfluxDBAllowBrowse", 1,$InfluxDBAllowBrowse,"")."</td>
				
	</tr>			
				
				
	<tr>
		<td colspan=3 align='right' style='font-size:18px'>
			<a href=\"javascript:blur();\" OnClick=\"javascript:GotoSquidNasStorage()\" 
				style='text-decoration:underline;font-size:18px'>{also_see_backup_to_nas}</a>
		</td>
	</tr>
	<tr style='height:80px;'>
		<td colspan=3 align='right' style='padding-top:30px'><hr>". button("{apply}","Save$t()",40)."</td>
	</tr>			
	</table>
	</div>
	</div>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	Loadjs('postgres.progress.php');
}	
var xSaveRemote$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	Loadjs('influxdb.remote.progress.php');
}

function SaveRemote$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('InfluxUseRemote').checked){XHR.appendData('InfluxUseRemote', 1);}else{XHR.appendData('InfluxUseRemote', 0);}
	if(document.getElementById('InfluxSyslogRemote').checked){XHR.appendData('InfluxSyslogRemote', 1);}else{XHR.appendData('InfluxSyslogRemote', 0);}
	XHR.appendData('InfluxUseRemoteIpaddr', document.getElementById('InfluxUseRemoteIpaddr').value);
	XHR.appendData('InfluxUseRemotePort', document.getElementById('InfluxUseRemotePort').value);
	XHR.appendData('InfluxUseRemoteArticaPort', document.getElementById('InfluxUseRemoteArticaPort').value);
	XHR.appendData('ArticaInfluxPassword', encodeURIComponent(document.getElementById('ArticaInfluxPassword').value));
	XHR.appendData('ArticaInfluxUsername', document.getElementById('ArticaInfluxUsername').value);
	XHR.appendData('InfluxAdminEnabled',0);
	XHR.sendAndLoad('$page', 'POST',xSaveRemote$t);  

}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('NoCompressStatisticsByHour', 0);
	if(document.getElementById('ResolvIPStatistics').checked){XHR.appendData('ResolvIPStatistics', 1);	}else{XHR.appendData('ResolvIPStatistics', 0);}
	if(document.getElementById('EnableQuotasStatistics').checked){XHR.appendData('EnableQuotasStatistics', 1);	}else{XHR.appendData('EnableQuotasStatistics', 0);}
	if(document.getElementById('UserAgentsStatistics').checked){XHR.appendData('UserAgentsStatistics', 1);	}else{XHR.appendData('UserAgentsStatistics', 0);}
	if(document.getElementById('InfluxDBAllowBrowse').checked){XHR.appendData('InfluxDBAllowBrowse', 1);	}else{XHR.appendData('InfluxDBAllowBrowse', 0);}
	if(document.getElementById('ActAsASyslogServer').checked){XHR.appendData('ActAsASyslogServer', 1);}else{XHR.appendData('ActAsASyslogServer', 0);}
	
	
	if(document.getElementById('CalamarisSchedules')){
		XHR.appendData('CalamarisSchedules', document.getElementById('CalamarisSchedules').value);
	}
	
	XHR.appendData('PostGresBackupMaxContainers', document.getElementById('PostGresBackupMaxContainers').value);
	XHR.appendData('MySQLStatisticsRetentionDays', document.getElementById('MySQLStatisticsRetentionDays').value);
	XHR.appendData('InfluxAdminPort', '8083');
	XHR.appendData('InfluxRestartMem', document.getElementById('InfluxRestartMem').value);
	XHR.appendData('InfluxAdminRetentionTime', document.getElementById('InfluxAdminRetentionTime').value);		
	XHR.appendData('QuotasStatisticsInterval', document.getElementById('QuotasStatisticsInterval').value);
	XHR.appendData('InfluxListenInterface', document.getElementById('InfluxListenInterface').value);
	XHR.appendData('InFluxBackupDatabaseInterval', document.getElementById('InFluxBackupDatabaseInterval').value);
	XHR.appendData('InFluxBackupDatabaseDir', document.getElementById('InFluxBackupDatabaseDir').value);
	
	XHR.appendData('PostgreSQLWorkMem', document.getElementById('PostgreSQLWorkMem').value);
	XHR.appendData('PostgreSQLSharedBuffer', document.getElementById('PostgreSQLSharedBuffer').value);
	XHR.appendData('PostgreSQLEffectiveCacheSize', document.getElementById('PostgreSQLEffectiveCacheSize').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}

function EnableQuotasStatisticsCheck(){
	document.getElementById('QuotasStatisticsInterval').disabled=true;
	if(document.getElementById('EnableQuotasStatistics').checked){
		document.getElementById('QuotasStatisticsInterval').disabled=false;
	}
}

function InfluxUseRemoteCheck(){
	if(document.getElementById('InfluxUseRemote').checked){
		document.getElementById('influx-local-service-id').style.display='none';
		document.getElementById('influx-backup-service-id').style.display='none';
		document.getElementById('influx-remote-service-options').style.display='';
		
	}else{
		document.getElementById('influx-local-service-id').style.display='';
		document.getElementById('influx-backup-service-id').style.display='';
		document.getElementById('influx-remote-service-options').style.display='none';
	
	}
	
	var STATS_APPLIANCE=$STATS_APPLIANCE;
	if(STATS_APPLIANCE==1){
		document.getElementById('influx-remote-service-id').style.display='none';
	}
}

function ActAsASyslogServerCheck(){
	var XHR = new XHRConnection();
	XHR.appendData('ActAsASyslogServerCheck', 0);
	

	if(document.getElementById('ActAsASyslogServer').checked){
		XHR.appendData('ActAsASyslogServer', 1);
	}else{
		XHR.appendData('ActAsASyslogServer', 0);
	
	}
	
	XHR.sendAndLoad('$page', 'POST');  		
}


function checkt$t(){
	var CORP_LICENSE=$CORP_LICENSE;
	document.getElementById('InfluxAdminRetentionTime').disabled=true;
	if(CORP_LICENSE==1){document.getElementById('InfluxAdminRetentionTime').disabled=false;}
}

EnableQuotasStatisticsCheck();
checkt$t();
InfluxUseRemoteCheck();
LoadAjaxRound('influx-db-status','$page?service-status=yes');				
</script>
				
				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function ActAsASyslogServerCheck(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer", $_POST["ActAsASyslogServer"]);
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
	
}

function InfluxUseRemote(){
	
	$_POST["ArticaInfluxPassword"]=url_decode_special_tool($_POST["ArticaInfluxPassword"]);
	
	if($_POST["InfluxUseRemotePort"]==8086){
		echo "Port: 8086 not allowed\n";
		return;
	}
	
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($_POST), "InfluxRemoteProgress");
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
}




function service_status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$sock->getFrameWork("influx.php?service-status=yes");
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/APP_INFLUXDB.status");
	$status=DAEMON_STATUS_ROUND("APP_POSTGRES", $ini);
	$html="$status<div style='text-align:right;height:40px;'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('influxdb_main_table');","right")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}



function InfluxAdminEnabled_save(){
	$sock=new sockets();
	
	
	while (list ($num, $val) = each ($_POST)){
		$sock->SET_INFO($num, $val);
		
	}
	$sock->getFrameWork("squid.php?access-tail-restart=yes");
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");

}

function graph1(){
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state";
	$tpl=new templates();
	$ARRAY=unserialize(@file_get_contents($cacheFile));
	$ARRAY["PART"]=$ARRAY["PART"]/1024;
	
	$PART=intval($ARRAY["PART"])-intval($ARRAY["SIZEKB"]);
	
	$MAIN["Partition " .FormatBytes($ARRAY["PART"])]=$PART;
	$MAIN["DB ".FormatBytes($ARRAY["SIZEKB"])]=$ARRAY["SIZEKB"];
	
	$PieData=$MAIN;
	$highcharts=new highcharts();
	$highcharts->container="influx-db-size";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{database_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{database_size} ".FormatBytes($ARRAY["SIZEKB"]) ." (MB)");
	echo $highcharts->BuildChart();
}
