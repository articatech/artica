<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if($argv[1]=="--crashed"){check_crashed();exit;}
if($argv[1]=="--crashed-squid"){check_crashed_squid();exit;}
if($argv[1]=="--crashed-squid-framework"){check_squiddb_queue_crashed();exit;}
if($argv[1]=="--crashed-table"){check_crashed_table($argv[2],$argv[3]);exit;}


function check_crashed_squid(){
	$FILE_LOG="/opt/squidsql/error.log";
	$DB_PATH="/opt/squidsql/data";
	
	// /etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time";

	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}


	$unix=new unix();


	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();

	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		if($pidTime>120){
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			die();
		}
		return;
	}


	@file_put_contents($pidfile, getmypid());
	$Time=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($Time<120){return;}
	}

	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(!preg_match("#^[0-9]+_#", $ligne["c"])){continue;}
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}");
	
	}

	
	
	
	if(!is_file($FILE_LOG)){return;}

	$myisamchk=$unix->find_program("myisamchk");
	$echo=$unix->find_program("echo");
	if(!is_file($myisamchk)){return;}
	$f=explode("\n",@file_get_contents($FILE_LOG));


	$GLOBALS["CRASHED"]=array();
	$PATHS=array();
	while (list ($num, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		
		
		if(preg_match("#Aborted connection [0-9]+#", $line)){continue;}
		if(preg_match("#Got an error from thread_id#", $line)){continue;}
		if(preg_match("#MySQL thread id [0-9]+#", $line)){continue;}
		if(preg_match("#ERROR.*?Table.*?\/(.+?)\/(.*?)'\s+is marked as crashed#", $line,$re)){
			$md5=md5("{$re[1]}{$re[2]}");
			if(!isset($arrayCheckNot[$md5])){
				mysql_admin_mysql(1, "{$re[1]}/{$re[2]} Incorrect key file for table [action=repair]", @implode("\n", $xxx));
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$arrayCheckNot[$md5]=true;
			}
			
			continue;
				
		}

		if(preg_match("#Incorrect key file for table './(.+?)\/(.+?)\.MYI'; try to repair it#", $line,$re)){
			$md5=md5("{$re[1]}{$re[2]}");
			if(!isset($arrayCheckNot[$md5])){
				mysql_admin_mysql(1, "{$re[1]}/{$re[2]} Incorrect key file for table [action=repair]", @implode("\n", $xxx));
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$arrayCheckNot[$md5]=true;
			}
			continue;
		}
		
		if(preg_match("#Got error 127 when reading table './(.+?)\/(.+?)'#", $line,$re)){
			$md5=md5("{$re[1]}{$re[2]}");
			if(!isset($arrayCheckNot[$md5])){
				mysql_admin_mysql(1, "{$re[1]}/{$re[2]} Got error 127 when reading table [action=repair]", @implode("\n", $xxx));
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$arrayCheckNot[$md5]=true;
			}
			continue;			
		}

		if($GLOBALS["VERBOSE"]){echo $line." no match\n";}




	}


	if(count($GLOBALS["CRASHED"])==0){return;}

	while (list ($database, $tables) = each ($GLOBALS["CRASHED"])){
		while (list ($a, $table) = each ($tables)){
			$path="$DB_PATH/$database/$table.MYI";
			if(is_file("$DB_PATH/$database/$table.TMD")){
				@copy("$DB_PATH/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
				@unlink("$DB_PATH/$database/$table.TMD");
			}
			
			$arrayCheck[$table]=true;
			
			$PATHS[$path]=true;
		}

	}

	while (list ($filepath, $none) = each ($PATHS)){

		$t=time();
		
		$cmd="$myisamchk -f -r $filepath 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results=array();
		exec("$cmd",$results);
		$Took=$unix->distanceOfTimeInWords($t,time());
		mysql_admin_mysql(1,basename($filepath)." repair report, took $Took", @implode("\n", $results));
		system_admin_events(basename($filepath)." repair report, took $Took",@implode("\n", $results),__FILE__,__LINE__,"mysql");
	}
	
	$mysqlcheck=$unix->find_program("mysqlcheck");
	$q=new mysql_squid_builder();
	while (list ($tablename, $none) = each ($arrayCheck)){
		shell_exec("$mysqlcheck --auto-repair $q->MYSQL_CMDLINES squidlogs $tablename");
		
		
	}
	shell_exec("$echo \"\" >$FILE_LOG");


}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/mysql.repair.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function check_crashed_table($table,$database){
	
	$unix=new unix();
	$q=new mysql();
	$mysqlcheck=$unix->find_program("mysqlcheck");
	$myisamchk=$unix->find_program("myisamchk");
	
	build_progress("$table/$database {checking}",10);
	$cmd="$mysqlcheck --auto-repair $q->MYSQL_CMDLINES $database $table";
	echo $cmd."\n";
	system($cmd);
	
	$path="/var/lib/mysql/$database/$table.MYI";
	
	if(!is_file($path)){
		echo "$path no such file\n";
		build_progress("$table/$database {failed}",110);
		return;
		
	}
	
	if(is_file("/var/lib/mysql/$database/$table.TMD")){
		@copy("/var/lib/mysql/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
		@unlink("/var/lib/mysql/$database/$table.TMD");
	}
	
	build_progress("$table/$database {repair}",50);
	$cmd="$myisamchk -f -r $path";
	echo $cmd."\n";
	system($cmd);
	build_progress("$table/$database {done}",100);
	
}

function check_squiddb_queue_crashed(){
	$LOCK_PATH="/etc/artica-postfix/squiddb_crashed.LOCK";
	if(!is_file("/etc/artica-postfix/squiddb_crashed")){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$q=new mysql_squid_builder();
	
	if(is_file($LOCK_PATH)){return;}
	
	@file_put_contents($LOCK_PATH, time());
	$data=unserialize(@file_get_contents("/etc/artica-postfix/squiddb_crashed"));
	if(count($data)==0){
			@unlink("/etc/artica-postfix/squiddb_crashed");
			@unlink($LOCK_PATH);
			return;
	}
	$LOOP_SQL=$data;
	$mysqlcheck=$unix->find_program("mysqlcheck");
	$myisamchk=$unix->find_program("myisamchk");
	
	$baseDir="/opt/squidsql/data";
	while (list ($keyenc, $line) = each ($LOOP_SQL)){
		$ARRAY=unserialize(base64_decode($keyenc));
		$database=$ARRAY["DB"];
		$table=$ARRAY["TABLE"];
		unset($data[$keyenc]);
		@file_put_contents("/etc/artica-postfix/squiddb_crashed", serialize($data));
		exec("$mysqlcheck --auto-repair $q->MYSQL_CMDLINES $database $table 2>&1",$results);
		
		$path="$baseDir/$database/$table.MYI";
		if(is_file("$baseDir/$database/$table.TMD")){
			@copy("$baseDir/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
			@unlink("$baseDir/$database/$table.TMD");
		}
		
		$cmd="$myisamchk -f -r $path 2>&1";
		exec($cmd,$results);
		squid_admin_mysql(1, "MySQL Repair task report on $database/$table", @implode("\n", $results),__FILE__,__LINE__);
	}
	
	@unlink($LOCK_PATH);
	
}

function check_crashed(){

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed.time";
	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
	
	
	$unix=new unix();
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		events("Already process PID: $pid running since $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "mysql");
		if($pidTime>120){
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			die();
		}
		return;
	}
	
	
	$q=new mysql();
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE 'TaskSq%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
		
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'artica_events' AND table_name LIKE 'Taskev%'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE {$ligne["c"]}","artica_events");
	
	}	
	
	
	
	
	@file_put_contents($pidfile, getmypid());
	$Time=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($Time<240){return;}
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$myisamchk=$unix->find_program("myisamchk");
	$echo=$unix->find_program("echo");
	if(!is_file($myisamchk)){return;}
	$f=explode("\n",@file_get_contents("/var/lib/mysql/mysqld.err"));
	
	
	$GLOBALS["CRASHED"]=array();
	$PATHS=array();
	while (list ($num, $line) = each ($f)){
		if(preg_match("#Aborted connection [0-9]+#", $line)){continue;}
		if(preg_match("#Got an error from thread_id#", $line)){continue;}
		if(preg_match("#MySQL thread id [0-9]+#", $line)){continue;}
		
		if(preg_match("#ERROR.*?Table.*?\/(.+?)\/(.*?)'\s+is marked as crashed#", $line,$re)){
			if(!isset($KEYF[$re[1]][$re[2]])){
				$KEYF[$re[1]][$re[2]]=true;
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			}
			continue;
			
		}
		
		if(preg_match("#Got error 127 when reading table './(.+?)\/(.+?)'#", $line,$re)){
			if(!isset($KEYF[$re[1]][$re[2]])){
				$KEYF[$re[1]][$re[2]]=true;
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			}
			continue;
		}
			
		if(preg_match("#Incorrect key file for table './(.+?)\/(.+?)\.MYI'; try to repair it#", $line,$re)){
			if(!isset($KEYF[$re[1]][$re[2]])){
				$KEYF[$re[1]][$re[2]]=true;
				$GLOBALS["CRASHED_LOGS"][]="Detected: $line";
				$GLOBALS["CRASHED"][$re[1]][]=$re[2];
			}
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo $line." no match\n";}
	
		
		
		
	}
	
	
	if(count($GLOBALS["CRASHED"])==0){return;}
	
	$mysqlcheck=$unix->find_program("mysqlcheck");
	$q=new mysql();
	
	while (list ($database, $tables) = each ($GLOBALS["CRASHED"])){
		
		while (list ($a, $table) = each ($tables)){
			$results=array();
			exec("$mysqlcheck --auto-repair $q->MYSQL_CMDLINES $database $table 2>&1",$results);
			squid_admin_mysql(1,"MySQL check on $database/$table report",@implode("\n", $results),__FILE__,__LINE__);
			$path="/var/lib/mysql/$database/$table.MYI";
			if(is_file("/var/lib/mysql/$database/$table.TMD")){
				@copy("/var/lib/mysql/$database/$table.TMD", "/var/lib/mysql/$database/$table.".time().".TMD");
				@unlink("/var/lib/mysql/$database/$table.TMD");
			}
			$PATHS[$path]=true;
		}
		
	}
	
	while (list ($filepath, $none) = each ($PATHS)){
		$results=array();
		$t=time();
		$cmd="$myisamchk -f -r $filepath 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results=array();
		exec("$cmd",$results);
		$Took=$unix->distanceOfTimeInWords($t,time());
		
		system_admin_events(basename($filepath)." repair report, took $Took",
		@implode("\n", $GLOBALS["CRASHED_LOGS"])."\n".
		@implode("\n", $results),__FILE__,__LINE__,"mysql");
		
		squid_admin_mysql(1,basename($filepath)." repair report, took $Took",
		@implode("\n", $GLOBALS["CRASHED_LOGS"])."\n".
		@implode("\n", $results),__FILE__,__LINE__,"mysql");
		
	}
	
	shell_exec("$echo \"\" >/var/lib/mysql/mysqld.err");


}