<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--clean"){cleanother();exit;}

xstart();

function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/upgradev10.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function xstart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(!is_file("/opt/influxdb/influxd")){
		
		build_progress("{InstallingBigDataEngine}...",10);
		system("$php /usr/share/artica-postfix/exec.influxdb.php --install");
	}
	
	if(!is_file("/opt/influxdb/influxd")){
		$php=$unix->LOCATE_PHP5_BIN();
		build_progress("{InstallingBigDataEngine} {failed}...",10);
	}
	
	
	build_progress("Removing old code....",10);
	
$toDelete["squid.cache.perf.stats.php"]=true;
$toDelete["system.cpustats.php"]=true;
$toDelete["exec.logfile_daemon-parse.php"]=true;
$toDelete["exec.squid.stats.members.hours.php"]=true;
$toDelete["exec.squid.hourly.tables.php"]=true;
$toDelete["exec.squidstream.php"]=true;
$toDelete["exec.sarg.php"]=true;
$toDelete["exec.sarg-web.php"]=true;
$toDelete["exec.squid-users-rttsize.php"]=true;
$toDelete["exec.squid.stats.notcached-week.php"]=true;
$toDelete["squid.traffic.panel.php"]=true;
$toDelete["squid.rrt-yesterday.php"]=true;
$toDelete["exec.squid.whitelist.ntlm.php"]=true;
$toDelete["squid.rtt.php"]=true;
$toDelete["exec.squid.stats.central.php"]=true;
$toDelete["exec.squid.stats.php"]=true;
$toDelete["{ipto}"]=true;
$toDelete["exec.firewall.php"]=true;
$toDelete["stats.admin.events.php"]=true;
$toDelete["exec.squid.stats.totals.php"]=true;
$toDelete["exec.mysar.php"]=true;
$toDelete["exec.admin.status.postfix.flow.php"]=true;
$toDelete["exec.squid.stats.mime.parser.php"]=true;
$toDelete["exec.dansguardian.last.php"]=true;
$toDelete["exec.squid.stats.month.php"]=true;
$toDelete["exec.dansguardian.last.php"]=true;
$toDelete["exec.squid.stats.familyday.php"]=true;
$toDelete["exec.squid.stats.usersday.php"]=true;
$toDelete["exec.squid.stats.quotaday.php"]=true;
$toDelete["exec.squid.stats.protos.php"]=true;
$toDelete["exec.squid-searchwords.php"]=true;
$toDelete["exec.squid.words.parsers.php"]=true;
$toDelete["exec.squid.visited.sites.php"]=true;
$toDelete["exec.squid.reports-scheduled.php"]=true;
$toDelete["exec.squid.hourly.tables.php"]=true;
$toDelete["exec.squid.quotasbuild.php"]=true;
$toDelete["exec.squid.cmdline.finduser.php"]=true;
$toDelete["exec.squid.reports-scheduled.php"]=true;
$toDelete["exec.squid.stats.categorize-table.php"]=true;
$toDelete["exec.squid.stats.month.php"]=true;
$toDelete["exec.squid.stats.not-categorized.php"]=true;
$toDelete["exec.squid.stats.recategorize.missed.php"]=true;
$toDelete["exec.squid.stats.recategorize.php"]=true;
$toDelete["exec.squid.stats.uid-month.php"]=true;
$toDelete["exec.squid.stats.usersday.php"]=true;
$toDelete["exec.squid.logs.import.php"]=true;
$toDelete["exec.squid.stats.quotaday.php"]=true;
$toDelete["exec.squid.cache.optimize.php"]=true;
$toDelete["squid.statistics.visited.day.php"]=true;
$toDelete["squid.traffic.statistics.days.php"]=true;
$toDelete["squid.traffic.statistics.days.graphs.php"]=true;
$toDelete["squid.statistics.visited.day.php"]=true;
$toDelete["squid.stats.repair.day.php"]=true;
$toDelete["squid.stats.category.php"]=true;
$toDelete["squid.statistics.query.categories.php"]=true;
$toDelete["squid.statistics.querytable.php"]=true;
$toDelete["exec.squid.stats.proto.parser.php"]=true;
$toDelete["exec.squid.stats.central.php"]=true;
$toDelete["exec.squid.stats.protos.php"]=true;
$toDelete["exec.squid.stats.month.php"]=true;
$toDelete["exec.squid.stats.members.hours.php"]=true;
$toDelete["exec.squid.stats.recategorize.missed.php"]=true;
$toDelete["exec.squid.stats.global.categories.php"]=true;
$toDelete["exec.squid.stats.totals.php"]=true;
$toDelete["exec.squid.stats.year.php"]=true;
$toDelete["exec.squid-rrd.php"]=true;
$toDelete["exec.squid.stats.quota-week.parser.php"]=true;
$toDelete["exec.squid.stats.notcached-week.php"]=true;
$toDelete["exec.squid.stats.uid-month.php"]=true;
$toDelete["exec.squid.stats.usersday.php"]=true;
$toDelete["exec.squid.stats.familyday.php"]=true;
$toDelete["exec.squid.stats.days.websites.php"]=true;
$toDelete["exec.squid.stats.days.cached.php"]=true;
$toDelete["exec.squid.stats.blocked.week.php"]=true;
$toDelete["exec.squid.stats.repair.php"]=true;
$toDelete["exec.getent.php"]=true;
$toDelete["squid.stats.filetypes.php"]=true;
$toDelete["exec.netdiscover.php"]=true;
$toDelete["miniadm.MembersTrack.category.php"]=true;
$toDelete["miniadm.MembersTrack.cronozoom.php"]=true;
$toDelete["miniadm.MembersTrack.sitename.php"]=true;
$toDelete["miniadm.webstats.php"]=true;
$toDelete["sarg.events.php"]=true;
$toDelete["squid.blocked.statistics.days.php"]=true;
$toDelete["squid.blocked.statistics.php"]=true;
$toDelete["squid.blocked.statistics.week.php"]=true;
$toDelete["squid.members.statistics.php"]=true;
$toDelete["squid.statistics.central.php"]=true;
$toDelete["squid.graphs.php"]=true;
$toDelete["squid.client-plugins.php"]=true;
$toDelete["exec.cache.pages.php"]=true;

while (list ($filepath, $table) = each ($toDelete) ){
	$filepath="/usr/share/artica-postfix/$filepath";
	if(is_file($filepath)){
		echo "Remove $filepath\n";
		@unlink($filepath);}
}

$Files["/etc/cron.hourly/SquidHourlyTables.sh"]=true;
$Files["/bin/artica-firewall.sh"]=true;
$Files["/etc/init.d/tproxy"]=true;
$Files["/usr/share/artica-postfix/bin/netdiscover"]=true;
$Files["/usr/share/artica-postfix/bin/install/rrd/yorel"]=true;
$Files["/usr/share/artica-postfix/bin/install/rrd/yorel-create"]=true;
$Files["/usr/share/artica-postfix/bin/install/rrd/yorel_cron"]=true;  
$Files["/usr/share/artica-postfix/bin/install/rrd/yorel-upd"]=true;

while (list ($filepath, $table) = each ($Files) ){
	if(is_file($filepath)){
		echo "Removing $filepath\n";
		@unlink($filepath);}
	}
	
	
	build_progress("Removing old code....",15);

$artica_events_delete["dnsperfs"]=true;
$artica_events_delete["dnsperfs_week"]=true;
$artica_events_delete["sys_loadvg"]=true;
$artica_events_delete["sys_mem"]=true;
$artica_events_delete["sys_loadvg"]=true;
$artica_events_delete["squid_rqs_days"]=true;
$artica_events_delete["squid_cache_perfs"]=true;
$artica_events_delete["cpustats"]=true;
@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableStreamCache", 0);

build_progress("List old tables...",20);
cleanother();
$LIST_TABLES_HOURS=LIST_TABLES_HOURS();
$LIST_TABLES_RTTZ_WORKSHOURS=LIST_TABLES_RTTZ_WORKSHOURS();
$LIST_TABLES_CACHE_DAY=LIST_TABLES_CACHE_DAY();
$LIST_TABLES_WORKSHOURS=LIST_TABLES_WORKSHOURS();
$LIST_TABLES_SIZEHOURS=LIST_TABLES_SIZEHOURS();
$LIST_TABLES_dansguardian_events=LIST_TABLES_dansguardian_events();
$LIST_TABLES_RTTD=LIST_TABLES_RTTD();
$LIST_TABLES_week=LIST_TABLES_week();
$MQUOTASIZE=LIST_TABLES_gen("%_MQUOTASIZE");
$WQUOTASIZE=LIST_TABLES_gen("%_WQUOTASIZE");
$quotaday=LIST_TABLES_gen("quotaday_%");
$visited=LIST_TABLES_gen("%_visited");
$quotamonth=LIST_TABLES_gen("quotamonth_%");
$DAYS=LIST_TABLES_gen("%_day");
$CATFAM=LIST_TABLES_gen("%_catfam");
$WWWUSERS=LIST_TABLES_gen("www_%");
$GCACHES=LIST_TABLES_gen("%_gcache");
$_not_cached=LIST_TABLES_gen("%_not_cached");
$members=LIST_TABLES_gen("%_members");
$dcache=LIST_TABLES_gen("%_dcache");
$family=LIST_TABLES_gen("%_family");
$proto=LIST_TABLES_gen("%_proto");
$_cacheperfs=LIST_TABLES_gen("%_cacheperfs");
$UserSizeD=LIST_TABLES_gen("UserSizeD_%");
$blocked_days=LIST_TABLES_gen("%_blocked_days");
$squidmemory=LIST_TABLES_gen("squidmemory_%");
$squidmemoryM=LIST_TABLES_gen("squidmemoryM_%");
$blocked_week=LIST_TABLES_gen("%_blocked_week");
$hours1=LIST_TABLES_gen("hour_%");
$hours2=LIST_TABLES_gen("squidhour_%");
$hours3=LIST_TABLES_gen("sizehour_%");
$visited=LIST_TABLES_gen("%_visited");


$squidlogs["UserSizeRTT"]=true;
$squidlogs["visited_sites"]=true;
$squidlogs["MySQLStats"]=true;
while (list ($num, $table) = each ($LIST_TABLES_HOURS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_RTTZ_WORKSHOURS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_CACHE_DAY) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_WORKSHOURS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_SIZEHOURS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_dansguardian_events) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_RTTD) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($LIST_TABLES_week) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($MQUOTASIZE) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($WQUOTASIZE) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($quotaday) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($visited) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($quotamonth) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($DAYS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($CATFAM) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($WWWUSERS) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($GCACHES) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($_not_cached) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($members) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($family) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($proto) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($_cacheperfs) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($UserSizeD) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($blocked_days) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($squidmemory) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($squidmemoryM) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($blocked_week) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($hours1) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($hours2) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($hours3) ){$squidlogs[$num]=true;}
while (list ($num, $table) = each ($visited) ){$squidlogs[$num]=true;}




$q=new mysql_squid_builder();
while (list ($tablename, $none) = each ($squidlogs)){
	if(!$q->TABLE_EXISTS($tablename)){continue;}
	if($q->COUNT_ROWS($table)==0){$q->QUERY_SQL("DROP TABLE `$tablename`");continue;}
	build_progress("Backup table/remove $tablename",25);
	echo "Backup $tablename\n";	
	if(!backup_squidlogs($tablename)){continue;}
	$q->QUERY_SQL("DROP TABLE `$tablename`");
}


while (list ($filename, $none) = each ($toDelete)){
	
	
	if(is_file("/share/artica-postfix/$filename")){
		build_progress("Removing $filename",50);
		@unlink("/usr/share/artica-postfix/$filename");
	}
}


$q=new mysql();
while (list ($tablename, $none) = each ($artica_events_delete)){
	if(!$q->TABLE_EXISTS($tablename,"artica_events")){continue;}
	build_progress("Backup table $tablename",60);
	if($q->COUNT_ROWS($table,"artica_events")==0){$q->QUERY_SQL("DROP TABLE `$tablename`","artica_events");continue;}
	$q->QUERY_SQL("DROP TABLE `$tablename`","artica_events");
	
}

$users=new usersMenus();
if($users->POSTFIX_INSTALLED){
	$q=new mysql();
	if($q->DATABASE_EXISTS("postfixlog")){$q->DELETE_DATABASE("postfixlog"); }
	if($q->DATABASE_EXISTS("syslogstore")){$q->DELETE_DATABASE("syslogstore"); }
	
}


build_progress("{reconfigure_proxy_service}",70);
system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
build_progress("{restarting} BigData Database",80);
system("/etc/init.d/artica-postgres restart --force");
build_progress("{restarting} Watchdog",90);
system("/etc/init.d/artica-status restart --force");
build_progress("{restarting} Watchdog",95);
system("/etc/init.d/squid-tail restart");
build_progress("{restarting} Watchdog",98);
system("/etc/init.d/cache-tail restart");

@file_put_contents("/etc/artica-postfix/settings/Daemons/UpgradeTov10", 1);
build_progress("{done}",100);

}


function LIST_TABLES_HOURS(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["SQUID_LIST_TABLES_HOURS"])){return $GLOBALS["SQUID_LIST_TABLES_HOURS"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_hour'";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#[0-9]+_hour#", $ligne["c"])){
			$GLOBALS["SQUID_LIST_TABLES_HOURS"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;
}




function LIST_TABLES_RTTZ_WORKSHOURS(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["LIST_TABLES_RTTZ_WORKSHOURS"])){return $GLOBALS["LIST_TABLES_RTTZ_WORKSHOURS"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'RTTH_%'";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#RTTH_[0-9]+#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_RTTZ_WORKSHOURS"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;
}
function LIST_TABLES_gen($pattern){
	$q=new mysql_squid_builder();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '$pattern'";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^webfilter_#i", $ligne["c"])){continue;}
		$GLOBALS["LIST_TABLES_CACHE_DAY"][$ligne["c"]]=$ligne["c"];
		$array[$ligne["c"]]=$ligne["c"];

	}
	return $array;
}

function LIST_TABLES_week(){
	$q=new mysql_squid_builder();

	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_week'";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$GLOBALS["LIST_TABLES_CACHE_DAY"][$ligne["c"]]=$ligne["c"];
		$array[$ligne["c"]]=$ligne["c"];

	}
	return $array;
}
function LIST_TABLES_RTTD(){
	$q=new mysql_squid_builder();
	
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'RTTD_%'";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$GLOBALS["LIST_TABLES_CACHE_DAY"][$ligne["c"]]=$ligne["c"];
		$array[$ligne["c"]]=$ligne["c"];
		
	}
	return $array;
}

function LIST_TABLES_CACHE_DAY(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["LIST_TABLES_CACHE_DAY"])){return $GLOBALS["LIST_TABLES_CACHE_DAY"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_cacherated'";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#[0-9]+_cacherated#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_CACHE_DAY"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;
}
function LIST_TABLES_WORKSHOURS(){
	if(isset($GLOBALS["LIST_TABLES_WORKSHOURS"])){return $GLOBALS["LIST_TABLES_WORKSHOURS"];}
	$q=new mysql_squid_builder();
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%'";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#squidhour_[0-9]+#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_WORKSHOURS"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;
}
function LIST_TABLES_SIZEHOURS(){
	if(isset($GLOBALS["LIST_TABLES_SIZEHOURS"])){return $GLOBALS["LIST_TABLES_SIZEHOURS"];}
	$q=new mysql_squid_builder();
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'sizehour_%'";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#sizehour_[0-9]+#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_SIZEHOURS"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;
}
function LIST_TABLES_dansguardian_events(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["LIST_TABLES_dansguardian_events"])){return $GLOBALS["LIST_TABLES_dansguardian_events"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#dansguardian_events_[0-9]+#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_dansguardian_events"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}


function cleanother(){
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'youtubehours_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'searchwords_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'youtubeweek_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'youtube_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}	
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_dcache'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_gsize'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_dcache'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}
	$q->QUERY_SQL("DROP TABLE visited_sites_days","artica_events");
	$q->QUERY_SQL("DROP TABLE FamilyCondensed","artica_events");
	$q->QUERY_SQL("DROP TABLE webfilters_thumbnails","artica_events");
	

}

function backup_squidlogs($tablename){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$tar=$unix->find_program("tar");
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose --force $q->database ";
	$container="/home/artica/squid/backup-statistics/$tablename.sql";
	if(is_file($container)){return;}
	$cmdline="$mysqldump_prefix$tablename >$container";
	echo "$cmdline\n";
	if($GLOBALS["VERBOSE"]){echo "\n*******\n$cmdline\n*******\n";}
	exec($cmdline,$resultsZ);

	if(!$unix->Mysql_TestDump($resultsZ,$container)){
		stats_admin_events(0, "Fatal Error: day: Dump failed $tablename", "",__FILE__,__LINE__);
	}
	$size=@filesize($container);
	@mkdir("/home/artica/squid/backup-statistics",0755,true);
	chdir("/home/artica/squid/backup-statistics");


	$cmdline="$tar cfz $container.tar.gz $container 2>&1";
	$resultsZ=array();
	exec($cmdline,$resultsZ);
	if($GLOBALS["VERBOSE"]){while (list ($a, $b) = each ($resultsZ)){echo "Compress: `$b`\n";}}

	if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
		stats_admin_events(0, "Test container failed: $container.tar.gz", "",__FILE__,__LINE__);
		@unlink($container);
		@unlink("$container.tar.gz");
		return ;
	}

	$size=FormatBytes($size/1024);
	@unlink($container);
	return true;
}

