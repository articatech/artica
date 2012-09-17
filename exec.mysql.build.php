<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["MULTI"]=false;
$GLOBALS["NOMONIT"]=false;
$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--multi#",implode(" ",$argv))){$GLOBALS["MULTI"]=true;}
if(preg_match("#--withoutmonit#",implode(" ",$argv))){$GLOBALS["NOMONIT"]=true;}

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$unix=new unix();
$unix->events("Executing ".@implode(" ",$argv));

if($argv[1]=='--tmpfs'){mysql_tmpfs();die();}
if($argv[1]=='--clean-numericsqu'){CleanBadFiles();die();}
if($argv[1]=='--mysqldisp'){mysql_display($argv[2],$argv[3]);die();}
if($argv[1]=='--execute'){execute_sql($argv[2],$argv[3]);die();}
if($argv[1]=='--database-exists'){execute_database_exists($argv[2]);die();}
if($argv[1]=='--table-exists'){execute_table_exists($argv[2],$argv[3]);die();}
if($argv[1]=='--rownum'){execute_rownum($argv[2],$argv[3]);die();}
if($argv[1]=='--GetAsSQLText'){GetAsSQLText($argv[2]);die();}
if($argv[1]=='--backup'){Backup($argv[2]);die();}
if($argv[1]=='--checks'){checks();die();}
if($argv[1]=='--maintenance'){$unix->events("Executing Maintenance");maintenance();die();}

if($argv[1]=="--fixmysqldbug"){fixmysqldbug();die();}
if($argv[1]=="--multi-start"){multi_start($argv[2]);die();}
if($argv[1]=="--multi-stop"){multi_stop($argv[2]);die();}
if($argv[1]=="--multi-start-all"){multi_start_all();die();}
if($argv[1]=="--multi-status"){multi_status();die();}
if($argv[1]=='--dbstats'){databases_list_fill();die();}
if($argv[1]=='--multi-dbstats'){multi_databases_parse();die();}
if($argv[1]=='--mysqltuner'){mysqltuner();die();}
if($argv[1]=='--database-rescan'){databases_rescan($argv[2],$argv[3]);die();}
if($argv[1]=='--database-dump'){database_dump($argv[2],$argv[3]);die();}




// 


	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid.time";
	$oldpid=$unix->get_pid_from_file($pidfile);
		
	if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already process $oldpid exists",__FUNCTION__,__FILE__,__LINE__);die();}
	
if($argv[1]=='--tables'){checks();die();}
if($argv[1]=='--imapsync'){rebuild_imapsync();die();}
if($argv[1]=='--rebuild-zarafa'){rebuild_zarafa();die();}
if($argv[1]=='--squid-events-purge'){squid_events_purge();die();}
if($argv[1]=='--mysqlcheck'){mysqlcheck($argv[2],$argv[3],$argv[4]);die();}



if($GLOBALS["VERBOSE"]){echo "Starting......:MySQL no understandeable parameters, build the config by default...\n";}


$sock=new sockets();
$q=new mysqlserver();
$MysqlConfigLevel=$sock->GET_INFO("MysqlConfigLevel");
if(!is_numeric($MysqlConfigLevel)){$MysqlConfigLevel=0;}
$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
$users=new usersMenus();
if($users->ZARAFA_INSTALLED){if($EnableZarafaTuning==1){$MysqlConfigLevel=-1;}}

if($MysqlConfigLevel>0){
	if($MysqlConfigLevel==1){
		echo "Starting......:MySQL my.cnf........: SWITCH TO LOWER CONFIG.\n";
		$datas=$q->Mysql_low_config();
	}
	
	if($MysqlConfigLevel==2){
		echo "Starting......:MySQL my.cnf........: SWITCH TO VERY LOWER CONFIG.\n";
		$datas=$q->Mysql_verlow_config();
	}	
}


if($MysqlConfigLevel==0){
	$unix=new unix();
	$mem=$unix->TOTAL_MEMORY_MB();
	echo "\n";
	echo "Starting......: Mysql my.cnf........: Total memory {$mem}MB\n";
	
	if($mem<550){
		echo "Starting......:MySQL my.cnf........: SWITCH TO LOWER CONFIG.\n";
		$datas=$q->Mysql_low_config();
		if($mem<390){
			echo "Starting......:MySQL my.cnf........: SWITCH TO VERY LOWER CONFIG.\n";
			$datas=$q->Mysql_verlow_config();
		}
	}else{
		$datas=$q->BuildConf();
	}
}

if($MysqlConfigLevel==-1){
	echo "Starting......: Mysql my.cnf........: SWITCH TO PERSONALIZED CONFIG.\n";
	$datas=$q->BuildConf();
}

$mycnf=$argv[1];
if(!is_file($mycnf)){$mycnf=LOCATE_MY_CNF();}
if(!is_file($mycnf)){echo "Starting......: Mysql my.cnf........: unable to stat {$argv[1]}\n";die();}

@file_put_contents($mycnf,$datas);
echo "Starting......: Mysql Updating \"$mycnf\" success ". strlen($datas)." bytes\n";

function checks(){
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql();
	$q->BuildTables();	
	$execute=false;
	
$tableEngines = array("hardware"=>"InnoDB","accesslog"=>"InnoDB","bios"=>"InnoDB","memories"=>"InnoDB","slots"=>"InnoDB",
"registry"=>"InnoDB","monitors"=>"InnoDB","ports"=>"InnoDB","storages"=>"InnoDB","drives"=>"InnoDB","inputs"=>"InnoDB",
"modems"=>"InnoDB","networks"=>"InnoDB","printers"=>"InnoDB","sounds"=>"InnoDB","videos"=>"InnoDB","softwares"=>"InnoDB",
"accountinfo"=>"InnoDB","netmap"=>"InnoDB","devices"=>"InnoDB", "locks"=>"HEAP");	
	
	if(is_file("/usr/share/artica-postfix/bin/install/ocsbase_new.sql")){
		if(!$q->DATABASE_EXISTS("ocsweb")){$execute=true;}
		if(!$execute){
			while (list ($table, $ligne) = each ($tableEngines) ){
				if(!$q->TABLE_EXISTS($table,"ocsweb")){$execute=true;break;}
			}
		}
		
	}
	
	
	reset($tableEngines);
	if($execute){
		$unix=new unix();
		$q->CREATE_DATABASE("ocsweb");
		$mysql=$unix->find_program("mysql");
		$password=$q->mysql_password;
		if(strlen($password)>0){$password=" -p$password";}
		$cmd="$mysql -u $q->mysql_admin$password --batch -h $q->mysql_server -P $q->mysql_port -D ocsweb < /usr/share/artica-postfix/bin/install/ocsbase_new.sql";
		exec($cmd,$results);
		exec($cmd,$results);
		exec($cmd,$results);
		while (list ($table, $ligne) = each ($tableEngines) ){
			if(!$q->TABLE_EXISTS($table,"ocsweb")){$unix->send_email_events("Unable to create OCS table (missing $table) table" , "$cmd\nmysql results\n".@implode("\n", $results),"system");break;}
		}
	}
	
	
	
}

function multi_status(){
	
	
if(!is_file("/etc/mysql-multi.cnf")){die();}
if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: Overloaded system,die()","MAIN",__FILE__,__LINE__);die();}


	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$INSTANCES=array();
	
	while (list ($key, $line) = each ($ini->_params)){
		if(preg_match("#^mysqld([0-9]+)#", $key,$re)){
			$instance_id=$re[1];
			$INSTANCES[$instance_id]=true;
		}
	}	
	if(count($INSTANCES)==0){die();}
	$unix=new unix();
	
	$mysqlversion=$unix->GetVersionOf("mysql-ver");
	while (list ($instance_id, $line) = each ($INSTANCES)){
		$master_pid=multi_get_pid($instance_id);
		$l[]="[ARTICA_MYSQL:$instance_id]";
			$l[]="service_name=APP_MYSQL_ARTICA";
			$l[]="master_version=$mysqlversion";
			$l[]="service_cmd=mysql:$instance_id";
			$l[]="service_disabled=1";
			$l[]="watchdog_features=1";
			$l[]="family=system";
			 
			$status=$unix->PROCESS_STATUS($master_pid);
			if($GLOBALS["VERBOSE"]){echo "Mysqld status = $status\n";
			print_r($status);}
			
			 
			if(!$unix->process_exists($master_pid)){
				multi_start($instance_id);
				$l[]="running=0";
			}else{
				$l[]="running=1";
				$l[]=$unix->GetMemoriesOf($master_pid);
				$l[]="";
			}		
		
	}
	echo @implode("\n", $l);
	
}

function database_dump($database,$instanceid){
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){return;}
	$options="--add-drop-table --no-create-info --no-create-db --skip-comments";
	echo "Dump $database with instance $instanceid ($mysqldump)\n";
	
	if($instanceid>0){
		$q=new mysql_multi($instance_id);
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqldump --user=$q->mysql_admin$password --socket=$q->SocketPath $options --databases $database >/tmp/$database.sql 2>&1";
		
	}else{
		$q=new mysql();
		if($q->mysql_server=="127.0.0.1"){
			$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";
		}else{
			$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";
		}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqldump --user=$q->mysql_admin$password $servcmd $options --databases $database >/tmp/$database.sql 2>&1";
	}
	$results[]=$cmdline;
	exec($cmdline,$results);
	echo @implode("\n", $results);
	compress("/tmp/$database.sql","/usr/share/artica-postfix/ressources/logs/web/$database.gz");
	@unlink("/tmp/$database.sql");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/$database.gz", 0777);
	
	
}
function compress($source,$dest){
		if(!function_exists("gzopen")){
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			writelogs("FATAL!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}

function databases_rescan($instanceid=0,$database){
	if($instanceid>0){
		multi_databases_list_tables($instanceid,$database);
		return;
	}
	databases_list_tables($database);
}


function multi_databases_parse(){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$sql="SELECT ID FROM mysqlmulti WHERE enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		multi_databases_list_fill($ligne["ID"]);
		
	}	
	
	
}

function multi_databases_list_fill($instance_id){
	
	$prefix="INSERT IGNORE INTO mysqldbsmulti (instance_id,databasename,TableCount,dbsize) VALUES ";
	$q=new mysql_multi($instance_id);
	$q2=new mysql();
	$databases=$q->DATABASE_LIST_SIMPLE();
	if($GLOBALS["VERBOSE"]){echo "Found ". count($databases)." databases\n";}	
	while (list ($database, $ligne) = each ($databases) ){
		
		$rr=multi_databases_list_tables($instance_id,$database);
		$TableCount=$rr[0];
		$Size=$rr[1];
		if($GLOBALS["VERBOSE"]){echo "Found database `$database` $TableCount tables ($Size)\n";}
		$f[]="($instance_id,'$database','$TableCount','$Size')";
		
	}
	
	
	if(count($f)>0){
		$q2->QUERY_SQL("DELETE FROM mysqldbsmulti WHERE instance_id='$instance_id'","artica_backup");
		$q2->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}		
}
function multi_databases_list_tables($instance_id,$database){
	$sql="show TABLE STATUS";
	$q=new mysql_multi($instance_id);
	$prefix="INSERT IGNORE INTO mysqldbtablesmulti (instance_id,tablename,databasename,tablesize,tableRows) VALUES ";
	$dbsize=0;
	$count=0;
	$f=array();
	$results=$q->QUERY_SQL($sql,$database);
	$q2=new mysql();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$tablesize=$ligne['Data_length'] + $ligne['Index_length'];
			$dbsize += $tablesize; 
			$Rows=$ligne["Rows"];
			$count=$count+1;
			$tablename=$ligne["Name"];
			if($GLOBALS["VERBOSE"]){echo "Found table `$database/$tablename $tablesize $Rows rows`\n";}	
			$f[]="($instance_id,'$tablename','$database','$tablesize','$Rows')";
	}
	
	if(count($f)>0){
		$q2->QUERY_SQL("DELETE FROM mysqldbtablesmulti WHERE databasename='$database' AND instance_id='$instance_id'","artica_backup");
		$q2->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}
	
	
	return array($count,$dbsize);
	}




function databases_list_fill(){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$oldpid=$unix->get_pid_from_file($pidfile);
		
		if($unix->process_exists($oldpid,basename(__FILE__))){
			writelogs("Already process $oldpid exists",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$time=$unix->file_time_min($pidfileTime);
		if($time<20){
			if($GLOBALS["VERBOSE"]){echo "Minimal time = 20Mn (current is {$time}Mn)\n";}
			return;
		}
		@unlink($pidfileTime);
		@file_put_contents($pidfileTime, time());
		@file_put_contents($pidfile, getmypid());
	}
	
	if($GLOBALS["VERBOSE"]){echo "databases_list_fill() executed\n";}
	$prefix="INSERT IGNORE INTO mysqldbs (databasename,TableCount,dbsize) VALUES ";
	$q=new mysql();
	if(!$q->TABLE_EXISTS('mysqldbs','artica_backup')){
	if($GLOBALS["VERBOSE"]){echo "check_storage_table()\n";}	
		$q->check_storage_table();}	
	$databases=$q->DATABASE_LIST_SIMPLE();
	
	if($GLOBALS["VERBOSE"]){echo "Found ". count($databases)." databases\n";}
	
	$q->QUERY_SQL("DROP TABLE mysqldbtables","artica_backup");
	$q->BuildTables();
	while (list ($database, $ligne) = each ($databases) ){
		
		$rr=databases_list_tables($database);
		$TableCount=$rr[0];
		$Size=$rr[1];
		if($GLOBALS["VERBOSE"]){echo "Found database `$database` $TableCount tables ($Size)\n";}
		$f[]="('$database','$TableCount','$Size')";
		
	}
	
	
	if(count($f)>0){
		$q->QUERY_SQL("TRUNCATE TABLE mysqldbs","artica_backup");
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}	
	multi_databases_parse();
	
}

	
function databases_list_tables($database){
	$sql="show TABLE STATUS";
	$q=new mysql();
	$prefix="INSERT INTO mysqldbtables (zKey,tablename,databasename,tablesize,tableRows) VALUES ";
	$dbsize=0;
	$count=0;
	$f=array();
	$results=$q->QUERY_SQL($sql,$database);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$tablesize=$ligne['Data_length'] + $ligne['Index_length'];
			$dbsize += $tablesize; 
			$Rows=$ligne["Rows"];
			$count=$count+1;
			$tablename=$ligne["Name"];
			$zKey=md5("$tablename$database");
			if($GLOBALS["VERBOSE"]){echo "Found table `$database/$tablename $tablesize $Rows rows`\n";}	
			$f[]="('$zKey','$tablename','$database','$tablesize','$Rows')";
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("DELETE FROM mysqldbtables WHERE databasename='$database'","artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
		
	}
	
	echo "Filling DB for $database : ".count($f)." items..\n";
	return array($count,$dbsize);
	}


function multi_start_all(){
	$q=new mysqlserver();
	$GLOBALS["MULTI"]=true;
	$q->mysql_multi();
	$sql="SELECT ID  FROM `mysqlmulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {multi_start($ligne["ID"]);}
	
}

function multi_create_cache(){
	if(isset($GLOBALS["CACHECREATED"])){return;}
	$sql="SELECT ID,servername  FROM `mysqlmulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ARR[$ligne["ID"]]=$ligne["servername"];
	}
	
	@file_put_contents("/etc/artica-postfix/mysql_multi_names.cache", serialize($ARR));
	$GLOBALS["CACHECREATED"]=true;
}

function multi_get_pid($ID){
	$unix=new unix();
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){
		if($unix->process_exists($pid)){return $pid;}
	}
	
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"socket=/var/run/mysqld/mysqld$ID.sock\" 2>&1";
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}
	}
	return null;
}

function multi_stop($ID){
	if(!is_numeric($ID)){echo "Stopping......: Mysql instance no id specified\n";return;}
	$PID=multi_get_pid($ID);
	echo "Stopping......: Mysql instance id:$ID PID:$PID..\n";
	$unix=new unix();
	if(!$unix->process_exists($PID)){
		echo "Stopping......: Mysql instance id:$ID already stopped..\n";
		return;
	}
	$mysqld_multi=$unix->find_program("mysqld_multi");
	$kill=$unix->find_program("kill");
	$cmd="$mysqld_multi --defaults-file=/etc/mysql-multi.cnf start $ID 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){echo "Stopping......: Mysql instance id:$ID $ligne\n";}
	sleep(1);
	
	for($i=0;$i<10;$i++){
		$PID=multi_get_pid($ID);
		if(!$unix->process_exists($PID)){break;}
		if(is_numeric($PID)){
			$cmd="$kill -9 $PID";
			echo "Stopping......: Mysql instance id:$ID killing PID: $PID\n";
			shell_exec($cmd);
			sleep(1);
		}
	}
	$PID=multi_get_pid($ID);
	if(!$unix->process_exists($PID)){
		echo "Stopping......: Mysql instance id:$ID success..\n";
		return;
	}	
	echo "Stopping......: Mysql instance id:$ID failed..\n";
}

function multi_start($ID){
	$q=new mysqlserver();
	$GLOBALS["MULTI"]=true;
	$GLOBALS["SHOWLOGONLYFOR"]=$ID;
	multi_monit($ID);
	multi_create_cache();
	$q->mysql_multi();
	echo "Starting......: Mysql instance id:$ID..\n";
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	echo "Starting......: Mysql instance id:$ID PID:$pidfile..\n";
	$unix=new unix();
	if($unix->process_exists($unix->get_pid_from_file($pidfile))){echo "Starting......: Mysql instance id:$ID already running...\n";return;}
	$chmod=$unix->find_program("chmod");
	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$database_path=$ini->get("mysqld$ID","datadir");
	if(is_file("$database_path/error.log")){@unlink("$database_path/error.log");}
	echo "Starting......: Mysql instance id:$ID database=$database_path\n";
	
	$cmd="$chmod 755 $database_path";
	exec($cmd,$results);
	$mysqld_multi=$unix->find_program("mysqld_multi");
	$cmd="$mysqld_multi --defaults-file=/etc/mysql-multi.cnf start $ID --verbose --no-log 2>&1";
	if(is_file("$database_path/maria_log_control")){@unlink("$database_path/maria_log_control");}
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){echo "Starting......: Mysql instance id:$ID $ligne\n";}
	
	for($i=0;$i<4;$i++){
		sleep(1);
		if($unix->process_exists(multi_get_pid($ID))){sleep(1);break;}
	}
	
	if(!$unix->process_exists(multi_get_pid($ID))){
		echo "Starting......: Mysql instance id:$ID failed..\n";
	}else{
		$q=new mysql_multi($ID);
		$q->QUERY_SQL_NO_BASE("create user 'mysqld_multi'@'127.0.0.1' identified by 'mysqld_multi'");
		$q->QUERY_SQL_NO_BASE("create user 'mysqld_multi'@'localhost' identified by 'mysqld_multi'");
		$q->QUERY_SQL_NO_BASE("create user 'grant shutdown on *.* to mysqld_multi'");
		$q=new mysqlserver_multi($ID);
		$q->setssl();
		
	}
		if(is_file("$database_path/error.log")){
			echo "Starting......: Mysql instance id:$ID $database_path/error.log\n";
			$f=explode("\n",@file_get_contents("$database_path/error.log"));
			while (list ($index, $ligne) = each ($f) ){
				if(trim($ligne)==null){continue;}
				if(preg_match("#^[0-9]+\s+[0-9\:]+\s+(.+)#", $ligne,$re)){$ligne=$re[1];}
				echo "Starting......: $ligne\n";
			}
		}else{
			echo "Starting......: Mysql instance id:$ID $database_path/error.log no such file\n";
		}
}

function multi_monit($ID){
	if($GLOBALS["NOMONIT"]){return;}
	$unix=new unix();
	$monit=$unix->find_program("monit");
	$chmod=$unix->find_program("chmod");
	if(!is_file($monit)){return;}
	$q=new mysql_multi($ID);
	$reloadmonit=false;
	$monit_file="/etc/monit/conf.d/mysqlmulti$ID.monitrc";
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	
	if($q->watchdog==0){
		echo "Starting......: Mysql instance id:$ID monit is not enabled ($q->watchdog)\n";
		if(is_file($monit_file)){
			@unlink($monit_file);
			@unlink("/usr/sbin/mysqlmulti-start{$ID}");
			@unlink("/usr/sbin/mysqlmulti-stop{$ID}");
			$reloadmonit=true;}
	}
	
	if($q->watchdog==1){
		echo "Starting......: Mysql instance id:$ID monit is enabled\n";
		$reloadmonit=true;
		$f[]="check process mysqlmulti{$ID}";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"/usr/sbin/mysqlmulti-start{$ID}\"";
   		$f[]="stop program =  \"/usr/sbin/mysqlmulti-stop{$ID}\"";
   		if($q->watchdogMEM>0){
  			$f[]="if totalmem > $q->watchdogMEM MB for 5 cycles then alert";
   		}
   		if($q->watchdogCPU>0){
   			$f[]="if cpu > $q->watchdogCPU% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	   
	   @file_put_contents($monit_file, @implode("\n", $f));
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --multi-start $ID --withoutmonit";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/mysqlmulti-start{$ID}", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/mysqlmulti-start{$ID}");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --multi-stop $ID --withoutmonit";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/mysqlmulti-stop{$ID}", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/mysqlmulti-stop{$ID}");	   
	}
	
	if($reloadmonit){
		$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	}
	
}


function rebuild_imapsync(){
	$q=new mysql();
	writelogs("DELETE imapsync table...",__FUNCTION__,__FILE__,__LINE__);
	$sql="DROP TABLE `imapsync`";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql:: $q->mysql_error\n";}
	writelogs("Rebuild tables",__FUNCTION__,__FILE__,__LINE__);
	$q->BuildTables();
	}
	
function rebuild_zarafa(){
	$q=new mysql();
	$q->DELETE_DATABASE("zarafa");
	shell_exec("/etc/init.d/artica-postfix restart zarafa");
	}
	
function execute_sql($filename,$database){
	$q=new mysql();
	$q->QUERY_SQL(@file_get_contents($filename),$database);
	if(!$q->ok){echo "ERROR: $q->mysql_error";}
	
}
function execute_database_exists($database){
	$q=new mysql();
	if(!$q->DATABASE_EXISTS($database)){echo "FALSE\n";die();}
	echo "TRUE\n";
	
}
function execute_table_exists($database,$table){
	$q=new mysql();
	if(!$q->TABLE_EXISTS($table,$database)){echo "FALSE\n";die();}
	echo "TRUE\n";
	
}
function execute_create_database($database,$table){
	$q=new mysql();
	if(!$q->TABLE_EXISTS($table,$database)){echo "FALSE\n";die();}
	echo "TRUE\n";
	
}
function execute_rownum($database,$table){
	$q=new mysql();
	$table=trim($table);
	$sql="SELECT count(*) as tcount FROM $table";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,$database));
	if($ligne["tcount"]==null){echo "0\n";return;}
	echo "{$ligne["tcount"]}\n";
}
function GetAsSQLText($filename){
	$datas=@file_get_contents($filename);
	$datas=addslashes($datas);
	@file_put_contents($filename,$datas);
}

function squid_events_purge(){
	$q=new mysql();
	$t1=time();
	$sock=new sockets();
	$nice=EXEC_NICE();
	$squidMaxTableDays=$sock->GET_INFO("squidMaxTableDays");
	$squidMaxTableDaysBackup=$sock->GET_INFO("squidMaxTableDaysBackup");
	$squidMaxTableDaysBackupPath=$sock->GET_INFO("squidMaxTableDaysBackupPath");
	if($squidMaxTableDays==null){$squidMaxTableDays=730;}
	if($squidMaxTableDaysBackup==null){$squidMaxTableDaysBackup=1;}
	if($squidMaxTableDaysBackupPath==null){$squidMaxTableDaysBackupPath="/home/squid-mysql-bck";}
	

	$sql="SELECT COUNT( ID ) as tcount FROM `dansguardian_events` WHERE `zDate` < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$events_number=$ligne["tcount"];
	if($events_number==0){return;}
	if($events_number<0){return;}
	if(!is_numeric($events_number)){return;}
	
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$gzip_bin=$unix->find_program("gzip");
	$stat_bin=$unix->find_program("stat");
	
	if($squidMaxTableDaysBackup==1){
			
			if(!is_file($mysqldump)){
				send_email_events("PURGE: unable to stat mysqldump the backup cannot be performed",
				"task aborted, uncheck the backup feature if you want to purge without backup",
				"proxy");
				return;
			}
			
			if(strlen($squidMaxTableDaysBackupPath)==0){
				send_email_events("PURGE: backup path was not set",
				"task aborted, uncheck the backup feature if you want to purge without backup",
				"proxy");
				return;		
			}
			@mkdir($squidMaxTableDaysBackupPath,600,true);
			$targeted_path="$squidMaxTableDaysBackupPath/".date("Y-m-d").".".time().".sql";
			$dumpcmd="$nice$mysqldump -u $q->mysql_admin -p$q->mysql_password -h $q->mysql_server artica_events dansguardian_events";
			$dumpcmd=$dumpcmd." -w \"zDate < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )\" >$targeted_path";
			
			exec($dumpcmd,$results);
			$text_results=@implode("\n",$results);
			if(!is_file("$targeted_path")){
				send_email_events("PURGE: failed dump table",
				"task aborted,$targeted_path no such file\n$text_results\n uncheck the backup feature if you want to purge without backup\n$dumpcmd",
				"proxy");
				return;
			}
			
			if(is_file($gzip_bin)){
				$targeted_path_gz=$targeted_path.".gz";
				shell_exec("$nice$gzip_bin $targeted_path -c >$targeted_path_gz 2>&1");
				if(is_file($targeted_path_gz)){
					@unlink($targeted_path);
					$targeted_path=$targeted_path_gz;
				}
			}
			
			unset($results);
			exec("$stat_bin -c %s $targeted_path",$results);
			$filesize=trim(@implode("",$results));
			$filesize=$filesize/1024;
			$filesize=FormatBytes($filesize);
			$filesize=str_replace("&nbsp;"," ",$filesize);
	}
	
	$sql="DELETE FROM `dansguardian_events` WHERE `zDate` < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		send_email_events("PURGE: failed removing $events_number elements",
		"task aborted,unable to delete $events_number elements,\nError:$q->mysql_error\n$sql",
		"proxy");
		return;	
	}
			
	$t2=time();
	
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);
	
	if($squidMaxTableDaysBackup==1){
		$backuptext="\nRemoved elements are backuped on your specified folder:$squidMaxTableDaysBackupPath\nBackuped datas file:$targeted_path ($filesize)";
	}
	
	send_email_events("PURGE: success removing $events_number elements",
	"task successfully executed.\nExecution time:$distanceOfTimeInWords\nBackuped datas:$targeted_path",
	"proxy");	
	
	
}

function Backup($table){
	$q=new mysql();
	$q->BackupTable($table,"artica_backup");
	
}

function mysql_display($table,$database){
	if($database==null){$database="artica_backup";}
	$q=new mysql();
	$sql="SELECT * FROM $table LIMIT 0,1";
	$results=$q->QUERY_SQL($sql,$database);
	$len = mysql_num_fields($results);
	
	for ($i = 0; $i < $len; $i++) {
		$name = mysql_field_name($results, $i);
		$lines[]=$name;
		
		
		$fields[$name]=true;
			
	} 	
	echo @implode(" | ", $lines)."\n";
	
	
	$sql="SELECT * FROM $table";
	$results=$q->QUERY_SQL($sql,$database);	
	while ($ligne = mysql_fetch_assoc($results)) {
		reset($fields);
		unset($f);
		while (list ($a, $b) = each ($fields) ){
			$f[]=$ligne[$a];
			
		}
		echo @implode(" | ", $f)."\n";
		
	}
	
	
	
}

function mysqlcheck($db,$table,$instance_id){
	if($GLOBALS["VERBOSE"]){echo "START:: ".__FUNCTION__."\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($oldpid)){
		echo "Process already exists pid $oldpid\n";
		return;
	}
	
	if(!is_numeric($instance_id)){$instance_id=0;}
	
	$time1=time();
	$mysqlcheck=$unix->find_program("mysqlcheck"); 
	$q=new mysql();
	$cmd="$mysqlcheck -r $db $table -u $q->mysql_admin -p$q->mysql_password 2>&1";
	
	if($instance_id>0){
		$q=new mysql_multi($instance_id);
		$cmd="$mysqlcheck -r $db $table -u $q->mysql_admin -p$q->mysql_password --socket=\"$q->SocketPath\" 2>&1";
	}
	
	exec($cmd,$results);
	$time_duration=distanceOfTimeInWords($time1,time());	
	$unix->send_email_events("mysqlcheck results on instance $instance_id $db/$table","$time_duration\n".@implode("\n",$results),"system");
}


function maintenance(){
	$unix=new unix();
	$time=$unix->file_time_min("/etc/artica-postfix/mysql.optimize.time");
	$time1=time();
	$myisamchk=$unix->find_program("myisamchk");
	$mysqlcheck=$unix->find_program("mysqlcheck"); 
	
	if(!$GLOBALS["VERBOSE"]){
		if($time<1440){
		$unix->events("Maintenance on aborting {$time}Mn wait 1440Mn minimal");
		system_admin_events("Maintenance on aborting {$time}Mn wait 1440Mn minimal",__FUNCTION__,__FILE__,__LINE__,"mysql");
		
		return;
		}
	}
	
	

	@unlink("/etc/artica-postfix/mysql.optimize.time");
	@file_put_contents("/etc/artica-postfix/mysql.optimize.time","#");
	
	
	if(is_file($mysqlcheck)){
		exec("$mysqlcheck -A -1 2>&1",$mysqlcheck_array);
		$mysqlcheck_logs=$mysqlcheck_logs."\n".@implode("\n",$mysqlcheck_array);
		unset($mysqlcheck_array);
	}
	$q=new mysql();
	$DATAS=$q->DATABASE_LIST();
	if($GLOBALS["VERBOSE"]){echo "Maintenance on ". count($DATAS)." databases starting...\n";}
	while (list ($db, $ligne) = each ($DATAS) ){
		_repair_database($db);
	
	}
	
	

	$t2=time();
	$time_duration=distanceOfTimeInWords($time1,$t2);	
	system_admin_events("Maintenance on ". count($DATAS)." databases done tool:$time_duration\nMysql Check events:$mysqlcheck_logs",__FUNCTION__,__FILE__,__LINE__,"mysql");
}

function _repair_database($database){
	$q=new mysql();
	$sql="SHOW TABLES";
	$results=$q->QUERY_SQL($sql,"squidlogs");	
	$unix=new unix();
	$time1=time();
	$myisamchk=$unix->find_program("myisamchk");
	$mysqlcheck=$unix->find_program("mysqlcheck"); 	
	$mysqlcheck_logs=null;
	$q=new mysql();
	$sql="SHOW TABLES";
	$results=$q->QUERY_SQL($sql,$database);
	
	if(mysql_num_rows($results)==0){
		system_admin_events("Maintenance on database $database aborting, no table stored",__FUNCTION__,__FILE__,__LINE__,"mysql");	
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["Tables_in_$database"];
		$tt=time();
		if(is_file($mysqlcheck)){
			exec("$mysqlcheck -r $database $table 2>&1",$mysqlcheck_array);
			$mysqlcheck_logs=$mysqlcheck_logs."\n".@implode("\n",$mysqlcheck_array);
			unset($mysqlcheck_array);
		}		
			
		
		echo $table."\n";
		if(is_file($myisamchk)){
			shell_exec("$myisamchk -r --safe-recover --force /var/lib/mysql/$database/$table");
		}else{
			$q->REPAIR_TABLE($database,$table);
		}
		
		$q->QUERY_SQL("OPTIMIZE table $table","$database");
		$time_duration=distanceOfTimeInWords($tt,time());	
		$p[]="$database/$table $time_duration";
		
	}
	$t2=time();
	$time_duration=distanceOfTimeInWords($time1,$t2);	
	system_admin_events("Maintenance on database $database done: took $time_duration\nOperations has be proceed on \n".@implode("\n",$p)."\nmysqlchecks results:\n$mysqlcheck_logs",__FUNCTION__,__FILE__,__LINE__,"mysql");	
	
}



function fixmysqldbug(){
	if(!is_file("/usr/bin/mysqld_safe")){echo "fixmysqldbug:: /usr/bin/mysqld_safe no such file...\n";return;}
	$f=@explode("\n", @file_get_contents("/usr/bin/mysqld_safe"));
	$replace=false;
	while (list ($index, $ligne) = each ($f) ){
		if(strpos($ligne, "/usr//usr/bin//")>0){
			echo "Fix line $index\n";
			$f[$index]=str_replace("/usr//usr/bin//", "/usr/bin/", $ligne);
			$replace=true;
		}
		
		if(strpos($ligne, "/usr//usr/sbin/")>0){
			echo "Fix line $index\n";
			$f[$index]=str_replace("/usr//usr/sbin/", "/usr/sbin/", $ligne);
			$replace=true;
		}		
		
	}
	
	if($replace){
		@file_put_contents("/usr/bin/mysqld_safe", @implode("\n", $f));
	}
	
	
}
function LOCATE_MY_CNF(){
	 if(is_file('/etc/mysql/my.cnf')){return '/etc/mysql/my.cnf';}
  	 if(is_file('/etc/my.cnf')){return '/etc/my.cnf';}
 	return '/etc/mysql/my.cnf';
}

function GetMemmB(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#^Mem:\s+([0-9]+)\s+#", $ligne,$re)){
			$mem=$re[1];
			continue;
		}
	if(preg_match("#^Swap:\s+([0-9]+)\s+#", $ligne,$re)){
			$Swap=$re[1];
			break;
		}
		
	}
	return array($mem,$Swap);
}

function mysqltuner(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already running PID $pid\n";}
		}
	}	
	
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["VERBOSE"]){echo "Running for Instance 0\n";}
	$targetfile="/usr/share/artica-postfix/ressources/mysqltuner/instance-0.db";
	@mkdir("/usr/share/artica-postfix/ressources/mysqltuner",0755);
	
	$mem=GetMemmB();
	$Memory=$mem[0];
	$Swap=$mem[1];
	system_admin_events("Memory: {$Memory}M Swap: {$Swap}M",__FUNCTION__,__FILE__,__LINE__,"mysql");
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($targetfile);
		if($GLOBALS["VERBOSE"]){echo "$targetfile Time:{$time}Mn need 119\n";}
		if($time>119){@unlink($targetfile);}
	}else{
		@unlink($targetfile);
	}
	
	if(!is_file($targetfile)){
		$q=new mysql();
		$t=time();
		$resultsCMDLINES=array();
		$mysql_admin=$q->mysql_admin;
		$password=$q->mysql_password;
		if($mysql_admin==null){$mysql_admin="root";}
		if($password<>null){$password=" --pass \"$password\"";}
		$cmdline="/usr/share/artica-postfix/bin/mysqltuner.pl";
		$socket=" --socket \"/var/run/mysqld/mysqld.sock\"";
		$cmdline=$cmdline." --nocolor --user=$mysql_admin$password --forcemem $Memory ";
		$cmdline=$cmdline."--forceswap $Swap $socket 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
		$resultsCMDLINES[]=" >>  Generated on ". date("Y-m-d H:i:s")." %%REBUILD";
		exec($cmdline,$resultsCMDLINES);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		system_admin_events("Generating report for instance number `0` tool: $took...\n".@implode("\n", $resultsCMDLINES),__FUNCTION__,__FILE__,__LINE__,"mysql");
		@file_put_contents($targetfile, @implode("\n", $resultsCMDLINES));
		@chmod($targetfile, 0755);
	}else{
		@unlink($targetfile);
	}
	
	$sql="SELECT ID FROM mysqlmulti WHERE enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "Generating report for instance number `{$ligne["ID"]}`...\n";}
		$id=$ligne["ID"];
		$targetfile="/usr/share/artica-postfix/ressources/mysqltuner/instance-$id.db";
		
		
		if(!$GLOBALS["FORCE"]){
			
			$time=$unix->file_time_min($targetfile);
			if($GLOBALS["VERBOSE"]){echo "$targetfile Time:{$time}Mn need 119\n";}
			if($time>119){@unlink($targetfile);}
		}else{
			@unlink($targetfile);
		}
		
		
		if(!is_file($targetfile)){
			$resultsCMDLINES=array();
			$t=time();
			$q=new mysql_multi($id);
			$mysql_admin=$q->mysql_admin;
			$password=$q->mysql_password;
			if($mysql_admin==null){$mysql_admin="root";}
			if($password<>null){$password=" --pass \"$password\"";}
			$cmdline=null;
			$cmdline="/usr/share/artica-postfix/bin/mysqltuner.pl";
			$cmdline=$cmdline." --nocolor --user=$mysql_admin$password --forcemem $Memory ";
			$cmdline=$cmdline."--forceswap $Swap --socket $q->SocketPath 2>&1";
			if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
			$resultsCMDLINES[]=" >>  Generated on ". date("Y-m-d H:i:s")." %%REBUILD";
			exec($cmdline,$resultsCMDLINES);	
			system_admin_events("Generating report for instance number `$id` tool: $took...\n".@implode("\n", $resultsCMDLINES),__FUNCTION__,__FILE__,__LINE__,"mysql");
			@file_put_contents($targetfile, @implode("\n", $resultsCMDLINES));
			@chmod($targetfile, 0755);
		}else{
			if($GLOBALS["VERBOSE"]){echo "$targetfile exists... skip it\n";}
		}		
		
		
	}

CleanBadFiles();



}

function CleanBadFiles(){
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		$filebase=basename($filename);
		if(is_numeric($filebase)){@unlink($filename);}
		
	}
}

function mysql_tmpfs(){
	$sock=new sockets();
	$unix=new unix();
	$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
	if($MySQLTMPDIR=="/tmp"){$MySQLTMPDIR=null;}
	$MySQLTMPMEMSIZE=trim($sock->GET_INFO("MySQLTMPMEMSIZE"));
	if($MySQLTMPDIR==null){echo "Starting......: MySQL tmpdir not set...\n";return;}
	if(!is_numeric($MySQLTMPMEMSIZE)){$MySQLTMPMEMSIZE=0;}
	if($MySQLTMPMEMSIZE<1){echo "Starting......: MySQL tmpfs not set...\n";return;}
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	
	if(strlen($idbin)<3){echo "Starting......: MySQL tmpfs `id` no such binary\n";return;}
	if(strlen($mount)<3){echo "Starting......: MySQL tmpfs `mount` no such binary\n";return;}
	exec("$idbin mysql 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......:MySQL mysql no such user...\n";return;}
	$uid=$re[1];
	$gid=$re[2];
	echo "Starting......: MySQL tmpfs uid/gid =$uid:$gid\n";
	mysql_tmpfs_umount($uid);
	if(is_dir($MySQLTMPDIR)){shell_exec("$rm -rf $MySQLTMPDIR/* >/dev/null 2>&1");}
	@mkdir($MySQLTMPDIR,0755,true);
	$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$MySQLTMPMEMSIZE}M,nr_inodes=10k,mode=0700 tmpfs \"$MySQLTMPDIR\"";
	shell_exec($cmd);
	$mounted=mysql_tmpfs_ismounted($uid);
	if(strlen($mounted)>3){
		echo "Starting......: MySQL $MySQLTMPDIR(tmpfs) for {$MySQLTMPMEMSIZE}M success\n";	
		
	}else{
		echo "Starting......: MySQL tmpfs for {$MySQLTMPMEMSIZE}M failed, it will return back to disk\n";
	}
}
function mysql_tmpfs_umount($uid){
	
	$unix=new unix();
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");	
	exec("$idbin mysql 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......:MySQL mysql no such user...\n";return;}	
	$uid=$re[1];
	$gid=$re[2];
	
	if(!is_numeric($uid)){
		echo "Starting......: MySQL tmpfs uid is not a numeric, aborting umounting task\n";
		return;
	}	
	
	$mounted=mysql_tmpfs_ismounted($uid);
	$c=0;
	while (strlen($mounted)>3) {
		if(strlen($mounted)>3){
		echo "Starting......:MySQL umount($uid) $mounted\n";
		shell_exec("$umount -l \"$mounted\"");
		$c++;
		}
		if($c>20){break;}
		$mounted=mysql_tmpfs_ismounted($uid);		
	}	

}



function mysql_tmpfs_ismounted($uid){
	
	$f=file("/proc/mounts");
	while (list ($index, $ligne) = each ($f) ){
		if(!preg_match("#tmpfs\s+(.+?)\s+tmpfs.*?uid=$uid#", $ligne,$re)){continue;}
		return trim($re[1]);
	}
}
	


?>