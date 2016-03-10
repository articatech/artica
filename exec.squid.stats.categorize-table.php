<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;
		//$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);		
}
if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}


if($GLOBALS["VERBOSE"]){"******* echo Loading... *******\n";}

include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



if($GLOBALS["VERBOSE"]){"echo Parsing arguments...\n";}

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();
$GLOBALS["Q"]=new mysql_squid_builder();

if($argv[1]=="--table"){_xprocess_table($argv[2]);exit;}
if($argv[1]=="--all"){process_all_tables();exit;}
if($argv[1]=="--xtime"){process_xtable($argv[2]);exit;}
if($argv[1]=="--repair-tables"){repair_tables();exit;}
if($argv[1]=="--last-days"){last_days();exit;}



function process_xtable($xtime){
	
	$table=date("Ymd",$xtime)."_hour";
	events_tail("process_xtable:: Processing $table");
	_xprocess_table($table);
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.totals.php --xtime $xtime >/dev/null 2>&1 &");
	
}

function process_all_tables(){
	
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
		
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
				return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<720){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <>720...\n";}
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	@file_put_contents($timefile, time());
	
	
	$tables=$GLOBALS["Q"]->LIST_TABLES_HOURS();
	$c=0;
	while (list ($tablename, $ligne) = each ($tables)){
		_xprocess_table($tablename);
		$xtime=$GLOBALS["Q"]->TIME_FROM_DAY_TABLE($tablename);
		shell_exec("$php /usr/share/artica-postfix/exec.squid.stats.totals.php --xtime $xtime >/dev/null 2>&1");
		if(!$GLOBALS["VERBOSE"]){
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
		}
		
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".dirname(__FILE__)."/exec.squid.stats.not-categorized.php");
	
}

function _xprocess_table($tablename,$nopid=false){
	
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){echo "Loading done...\n";}
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pid=@file_get_contents($pidfile);
		if(!$GLOBALS["FORCE"]){
			if($pid<100){$pid=null;}
			$unix=new unix();
			if($unix->process_exists($pid,basename(__FILE__))){
				events_tail("$tablename:: Already executed pid $pid");
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
				return;
			}
		
			$timeexec=$unix->file_time_min($timefile);
			if($timeexec<120){
				categorize_tables_events("$tablename:: {$timeexec}mn, need 120mn...","pid: ".getmypid(),$tablename);
				events_tail("$tablename:: {$timeexec}mn, need 120mn...");
				return;
			}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}	
	}
	
	
	
	$sql="SELECT COUNT(`sitename`) as tcount FROM $tablename WHERE LENGTH(`category`)=0";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	$max=$ligne["tcount"];
	
	
	$filePath="/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename";
	$ARRAY=unserialize(@file_get_contents($filePath));
	
	if($max>10000){
		$LIMIT_MAX=3000;
		$LIMIT_MIN=0;
		if(isset($ARRAY["CURRENT"])){
			if($ARRAY["CURRENT"]<$max-3000){
				$LIMIT_MIN=$ARRAY["CURRENT"];
			}
		}
		$LIMIT_SQL=" LIMIT $LIMIT_MIN,$LIMIT_MAX";
	}
	
	$sql="SELECT `sitename`,`familysite` FROM $tablename WHERE LENGTH(`category`)=0 ORDER BY familysite $LIMIT_SQL";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		events_tail("$tablename:: MySQL error","{$GLOBALS["Q"]->mysql_error}");
		categorize_tables_events("MySQL error","{$GLOBALS["Q"]->mysql_error}<br>$sql",$tablename);
		return;
	}
	
	$catz=0;
	
	if(mysql_num_rows($results)>0){categorize_tables_events("$tablename: Processing $max ($LIMIT_SQL) not categorized websites","pid: ".getmypid(),$tablename);}
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "O, nothing, return...\n";}
		@unlink($filePath);
		return;
	}
	
	$c=0;$d=0;
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=$ligne["sitename"];
		$sitenameToScan=$sitename;
		$familysite=$ligne["familysite"];
		
		if($sitename==null){
			if($GLOBALS["VERBOSE"]){echo "Null value for $sitename,$familysite aborting\n";}
			$GLOBALS["Q"]->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
			continue;
		}
		
		if($sitename=='.'){if($GLOBALS["VERBOSE"]){echo "'.' value for $sitename,$familysite aborting\n";}
			$GLOBALS["Q"]->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
			continue;
		}
			
		if(strpos($sitename, ',')>0){
			$sitename=str_replace(",", "", $sitename);
			$sitenameToScan=$sitename;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET `sitename`='$sitename' WHERE `sitename`='{$ligne["sitename"]}'");
		}
			
		if(is_numeric($sitename)){
			if($GLOBALS["VERBOSE"]){echo "Numeric value for $sitename,$familysite aborting\n";}
			$GLOBALS["Q"]->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
			continue;
		}
			
			
		if(strpos($sitename, ".")==0){
			if($GLOBALS["VERBOSE"]){echo "Seems to be a local domain for $sitename,$familysite aborting\n";}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET `category`='internal' WHERE `sitename`='{$ligne["sitename"]}'");
			continue;
		}
		
		if(!isset($GLOBALS[$tablename][$sitename])){
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitenameToScan)){
				$sitename=gethostbyaddr($sitename);
				if($GLOBALS["VERBOSE"]){echo "IP:$sitenameToScan -> `$sitename`\n";}
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){
					$GLOBALS[$tablename][$sitename]="ipaddr";
					$GLOBALS["Q"]->categorize($sitename, "ipaddr");
					$category="ipaddr";
				}
			}
		}
		
		if(!isset($GLOBALS[$tablename][$sitename])){
			$GLOBALS[$tablename][$sitename]=mysql_escape_string2($GLOBALS["Q"]->GET_CATEGORIES($sitename));
		}
		
		$c++;

		$category=$GLOBALS[$tablename][$sitename];
		
		writelogs_squid("$sitename -> `$category`");
		if(!$GLOBALS["VERBOSE"]){
			if(SquidStatisticsTasksOverTime()){
				stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__);
				return;
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "$sitename -> `$category`\n";}
		$c++;
		$d++;
		if($category==null){continue;}
		
	
		if(!isset($UPDATED[$sitenameToScan])){
			$GLOBALS["Q"]->categorize_temp($sitenameToScan,$category);
			$sqltmp="UPDATE `$tablename` SET `category`='$category' WHERE `sitename`='$sitenameToScan'";
			$GLOBALS["Q"]->QUERY_SQL($sqltmp);
			if(!$GLOBALS["Q"]->ok){
				categorize_tables_events("MySQL error (after $d rows)","{$GLOBALS["Q"]->mysql_error}<br>$sqltmp",$tablename);
				return;
			}
			$UPDATED[$sitenameToScan]=true;
		}
		
		
		
		if($c>500){
			WriteStatus($d,$max,$tablename);
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			$c=0;
		}
		
	}
		
		
	
	if($catz>0){
		$took=$unix->distanceOfTimeInWords($t,time());
		events_tail("$catz/$d/$max websites categorized for $tablename, took: $took");
		stats_admin_events(2,"$catz/$d websites categorized took: $took",$tablename,null,__FILE__,__LINE__);
	}
	
	
	
	
}
function events_tail($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}
	if($GLOBALS["VERBOSE"]){echo "$sourcefunction:: $text (in line $sourceline)\n";}
	writelogs_squid($text,$sourcefunction,__FILE__,$sourceline,"stats",true);
	
}
function WriteStatus($d,$max,$tablename,$ORDER){
	$mypid=getmypid();
	$ARRAY["PID"]=$mypid;
	$ARRAY["CURRENT"]=$d;
	$ARRAY["MAX"]=$max;
	$ARRAY["ORDER"]=$ORDER;
	@mkdir("/usr/share/artica-postfix/ressources/logs/categorize-tables",0777,true);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename", serialize($ARRAY));
	@chmod("/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename",0777);
}
function repair_tables(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		events_tail("Already executed pid $pid");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	$files=$unix->DirFiles("/usr/share/artica-postfix/ressources/logs/categorize-tables");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	while (list ($none, $tablename) = each ($files)){
			$filePath="/usr/share/artica-postfix/ressources/logs/categorize-tables/$tablename";
			if(!is_file($filePath)){
				@unlink($filePath);
				continue;
			}

			$ARRAY=unserialize(@file_get_contents($filePath));
			if(!is_array($ARRAY)){@unlink($filePath);continue;}
			$PID=$ARRAY["PID"];
			$CUR=$ARRAY["CURRENT"];
			$MAX=$ARRAY["MAX"];
			
			if($CUR==$MAX){@unlink($filePath);continue;}
			if($unix->process_exists($PID)){continue;}
			categorize_tables_events("Ask to schedule table Current:$CUR/$MAX",null,$tablename,1);
			$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --table $tablename");
			
	}

	
	
}

function last_days(){
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "last_days Loading done...\nTimeFile:$timefile\n";}
	$unix=new unix();

	if(!$GLOBALS["VERBOSE"]){
		if(SquidStatisticsTasksOverTime()){ 
			stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); 
			return; 
		}
	}
	
	
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}

		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
		
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<7200){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <> 7200...\n";}
			return;
		}
	}
	
	
	
	$q=new mysql_squid_builder();
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@file_put_contents($timefile, time());
	

	
	$current_table=date("Ymd")."_hour";
	$t=time();
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `suffix` FROM tables_day 
			WHERE DAY(zDate)<DAY(NOW()) AND YEAR(zDate) = YEAR(NOW()) AND MONTH(zDate) = MONTH(NOW()) AND zDate>DATE_SUB(NOW(),INTERVAL 7 DAY)";
	
	$results=$q->QUERY_SQL($sql);
	$num=mysql_num_rows($results);
	if($num==0){return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$current_table=$ligne["suffix"]."_hour";
		if(!$q->TABLE_EXISTS($current_table)){
			if($GLOBALS["VERBOSE"]){echo "$current_table no such table\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "Processing $current_table\n";}
		
		if(!$GLOBALS["VERBOSE"]){
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
		}
		_xprocess_table($current_table,true);
		
		$f[]=$current_table;
		
	}
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	stats_admin_events(2,"Processing categorization of $num tables $took",@implode("\n", $f),__FILE__,__LINE__);
	
}





?>