<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");




run();
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.statistics.not-categorized.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function run(){
	
	$TimeFile="/etc/artica-postfix/pids/exec.squid.stats.categorize.php.time";
	$MaxTime="/etc/artica-postfix/pids/exec.squid.stats.categorize.php.maxtime";
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.categorize.php.pid";
	$unix=new unix();
	$skiptime=false;
	

	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){
				build_progress("Already executed pid $pid",110);
				return;
			}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
		
		
	}
	
	
	if($GLOBALS["PROGRESS"]){$skiptime=true;}
	if($GLOBALS["FORCE"]){$skiptime=true;}
	
	@file_put_contents($pidfile, getmypid());
	if(!$skiptime){
		$time=$unix->file_time_min($TimeFile);
		if($time<240){echo "Current {$time}Mn, require at least 240mn\n";return;}
		
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
	}
	
	@unlink($MaxTime);
	@file_put_contents($MaxTime, time());
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$catz=new mysql_catz();
		if($catz->UfdbCatEnabled==0){
			build_progress("Categories Engine is disabled",110);
			squid_admin_mysql(1, "Categories Engine is disabled, skip parsing non-categorized websites.", null,__FILE__,__LINE__);
			return ;
		}
		
		
		build_progress("Updating databases",10);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.blacklists.php --bycron");
		
		
		build_progress("Construct not categorized webistes...",15);
		$q=new postgres_sql();
		$q->CREATE_TABLES();
		$sql="SELECT sum(size) as size,sum(rqs) as rqs, familysite from access_log WHERE category='' AND zdate>'$now' GROUP BY familysite ORDER BY size DESC LIMIT 5000";
		
		$q=new postgres_sql();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		
		$DEST_DAY=array();
		$q->QUERY_SQL("truncate table not_categorized");
		while($ligne=@pg_fetch_assoc($results)){
			$familysite=$ligne["familysite"];
			$size=$ligne["size"];
			$rqs=$ligne["rqs"];
			echo "$familysite $size ($rqs)\n";
			$q->QUERY_SQL("INSERT INTO not_categorized (zdate,familysite,size,rqs) VALUES (NOW(),'$familysite','$size','$rqs')");
		}
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/NOT_CATEGORIZED_TIME", time());
	if(system_is_overloaded()){
		build_progress("Overloaded, aborting",110);
		return;
	}
	$sql="SELECT familysite from not_categorized ORDER BY size DESC LIMIT 5000";
	$c=0;
	$q=new postgres_sql();
	build_progress("Query the system...",20);
	$results=$q->QUERY_SQL($sql);	
	
	$sum=pg_num_rows($results);
	
	$c=0;
	
	while($ligne=@pg_fetch_assoc($results)){
		$c++;
		
		$perc=$c/$sum;
		$perc=round($perc*100);
		$perc=$perc+20;
		if($perc>95){$perc=95;}
		$familysite=$ligne["familysite"];
		build_progress("Analyze $familysite $c/$sum",$perc);
		$category=$catz->GET_CATEGORIES($familysite);
		echo "$familysite = $category\n";
		if($category<>null){
			build_progress("Analyze $familysite = $category $c/$sum",$perc);
			echo "UPDATE access_log = $category\n";
			$q->QUERY_SQL("UPDATE access_log SET category='$category' WHERE familysite='$familysite' AND category=''");
			echo "UPDATE access_month = $category\n";
			$q->QUERY_SQL("UPDATE access_month SET category='$category' WHERE familysite='$familysite' AND category=''");
			echo "UPDATE access_year = $category\n";
			$q->QUERY_SQL("UPDATE access_year SET category='$category' WHERE familysite='$familysite' AND category=''");
			echo "DELETE not_categorized FOR $familysite\n";
			$q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$familysite'");
			
		}
		
		$timexec=$unix->file_time_min($MaxTime);
		if($timexec>230){
			build_progress("Expired time, aborting $c/$sum",110);
			return;
		}
		
		if(system_is_overloaded()){
			build_progress("Overloaded, aborting $c/$sum",110);
			return;}
		
	}
	build_progress("{success}",100);


	
	
	
}