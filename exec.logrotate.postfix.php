<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--connect"){connect_from($argv[2]);exit;}
if($argv[1]=="--compress-clean"){compressAndClean();exit;}
if($argv[1]=="--pfl"){pflogsumm($argv[2]);exit;}
if($argv[1]=="--migrate"){$GLOBALS["OUTPUT"]=true;smtprecipients_day_migrate_to_postgres();exit;}
if($argv[1]=="--convert"){maillogconvert($argv[2]);exit;}
if($argv[1]=="--convert-parse"){maillogconvertparse($argv[2]);exit;}
if($argv[1]=="--convert-all"){$GLOBALS["OUTPUT"]=true;maillogconvertall();exit;}



$targetfile="/home/postfix/logrotate/".date("Y-m-d").".log";
$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(isset($argv[1])){
	if(!is_file($argv[1])){
		echo "Unable to understand {$argv[1]}\n";die();
	}
	$targetfile=$argv[1];
}

@mkdir("/home/postfix/logrotate",0755,true);



$q=new mysql();
$hier=$q->HIER();
$targetcompressed="/home/postfix/logrotate/$hier.gz";
$unix=new unix();


if(is_file($targetfile)){
	if(!connect_from($targetfile)){
		postfix_admin_mysql(0, "FATAL! $targetfile connect_from() failed", null,__FILE__,__LINE__);
		return;
	}
	
	
	
	if(!pflogsumm($targetfile)){
		postfix_admin_mysql(0, "FATAL! $targetfile pflogsumm() failed", null,__FILE__,__LINE__);
		return;
	}
	if(!$unix->compress($targetfile, $targetcompressed)){
		@unlink($targetcompressed);
		return;
	}
	
	maillogconvert($targetcompressed);
	@unlink($targetfile);
	
	
}



if(is_file($targetcompressed)){
	echo "$targetcompressed exists, abort\n";
	die();
}



if(!@copy("/var/log/mail.log", $targetfile)){
	postfix_admin_mysql(0, "FATAL! unable to rotate mail.log", null,__FILE__,__LINE__);
	die();
}

$echo=$unix->find_program("echo");
shell_exec("$echo \"\" >/var/log/mail.log");
shell_exec("/etc/init.d/rsyslog restart");
$php=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
shell_exec("$php $nohup ".__FILE__." >/dev/null 2>&1 &");


function smtpstats_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpstats_day", "artica_events")){
		echo "smtpstats_day no such table\n";
		return;
	}
	
	
	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();
	
	$sql="SELECT * FROM smtpstats_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpstats_day ". mysql_num_rows($results)." rows...\n";
	
	$prefix="INSERT INTO smtpstats_day (zdate,zmd5,domain,grey,black,cnx,hosts,ips,infos) ";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$infos=$ligne["INFOS"];
		$infos=mysql_escape_string2($infos);
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["domain"]}','{$ligne["GREY"]}','{$ligne["BLACK"]}','{$ligne["CNX"]}','{$ligne["HOSTS"]}','{$ligne["IPS"]}','$infos')";
		
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){
				echo $q2->mysql_error."\n";
				return;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){return;}
		$f=array();
	}	
	
	echo "smtpstats_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpstats_day","artica_events");
	
	
	
}
function smtpcdir_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpcdir_day", "artica_events")){
		echo "smtpcdir_day no such table\n";
		return;
	}


	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtpcdir_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpcdir_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtpcdir_day (zdate,zmd5,cdir,domains,grey,black,cnx,hosts,infos) ";

	// `zmd5`,`zDate`,`CDIR`,`GREY`,`BLACK`,`CNX`,`DOMAINS`,`INFOS`
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$infos=$ligne["INFOS"];
		$infos=mysql_escape_string2($infos);
		$ligne["HOSTS"]=intval($ligne["HOSTS"]);
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["CDIR"]}','{$ligne["DOMAINS"]}','{$ligne["GREY"]}','{$ligne["BLACK"]}','{$ligne["CNX"]}','{$ligne["HOSTS"]}','$infos')";

		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){
				echo $q2->mysql_error."\n";
				return;}
				$f=array();
		}

	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){return;}
		$f=array();
	}

	echo "smtpcdir_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpcdir_day","artica_events");



}

function maillogconvertall(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!$GLOBALS["FORCE"]){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeMin=$unix->PROCCESS_TIME_MIN($pid);
			postfix_admin_mysql("Already executed PID $pid since $timeMin Minutes",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			if($timeMin>240){
				postfix_admin_mysql("Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
			}else{
				die();
			}
		}
		
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($timefile);
		if($time<30){postfix_admin_mysql("No less than 30mn (current {$time}Mn)",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM maillogsrc");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$CountOF=pg_num_rows($results);
	echo "$CountOF Scanned files\n";
	
	while ($ligne = pg_fetch_array($results)) {
		$MAIN_DEST=$ligne["sourcefile"];
		echo "Already scanned: $MAIN_DEST\n";
		$SCANNED[$MAIN_DEST]=true;
	}
	
	
	$f=$unix->DirFiles("/home/postfix/logrotate","\.gz$");
	while (list ($basename, $ARRAY) = each ($f) ){
		if(isset($SCANNED[$basename])){
			echo "SKIP -> $basename\n";
			continue;
		}
		echo "Scanning ->maillogconvert(/home/postfix/logrotate/$basename)\n";
		maillogconvert("/home/postfix/logrotate/$basename");
		if(system_is_overloaded(basename(__FILE__))){
			echo "Overloaded system\n";
			postfix_admin_mysql(1, "Overloaded system, aborting task", null,__FILE__,__LINE__);
			return;
		}
	}
	
	shell_exec("/usr/local/ArticaStats/bin/vacuumdb -h /var/run/ArticaStats --dbname=proxydb --username=ArticaStats");
	
	
}


function maillogconvert($filename){
	$basename=basename($filename);
	$time=filemtime($filename);
	$maillogconvert_path="/var/log/maillogconvert/$time.convert";
	$unix=new unix();
	$year=date("Y");
	$compress=false;
	if(preg_match("#\.gz$#", $basename)){
		if(preg_match("#^([0-9]+)-([0-9]+)-([0-9]+)\.#", $basename,$re)){
			$year=$re[1];
			$zdate="{$re[1]}-{$re[2]}-{$re[3]}";
		}
		$compress=true;
	}
	
	
	
	$q=new postgres_sql();
	$binary="/usr/share/artica-postfix/bin/maillogconvert.pl";
	@chmod("$binary",0755);
	@mkdir("/var/log/maillogconvert");
	
	if($compress){
		$uncompressed_filename=$unix->FILE_TEMP();
		if(!$unix->uncompress($filename, $uncompressed_filename)){return false;}
		if(is_file($maillogconvert_path)){@unlink($maillogconvert_path);}
		echo "$binary standard $year $uncompressed_filename >$maillogconvert_path\n";
		system("$binary standard $year $uncompressed_filename >$maillogconvert_path");
		@unlink($uncompressed_filename);
		
		$maillogconvert_path_basename=basename($maillogconvert_path);
		if(maillogconvertparse($maillogconvert_path_basename,$zdate)){
			$q->QUERY_SQL("INSERT INTO maillogsrc (sourcefile) VALUES ('$basename')");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}else{
			echo "maillogconvert:: maillogconvertparse-> RETURN FALSE\n";
		}
		
		
		return;
	}
	
	if(is_file($maillogconvert_path)){@unlink($maillogconvert_path);}
	system("$binary standard $year $filename >$maillogconvert_path");
	$maillogconvert_path_basename=basename($maillogconvert_path);
	if(maillogconvertparse($maillogconvert_path_basename)){
		$q=new postgres_sql();
		echo "INSERT INTO maillogsrc sourcefile VALUES '$maillogconvert_path_basename'\n";
		$q->QUERY_SQL("INSERT INTO maillogsrc (sourcefile) VALUES ('$basename')");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
}

function maillogconvertparse($filename,$zdateFile=null){
	
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	$maillogconvert_path="/var/log/maillogconvert/$filename";
	echo "maillogconvertparse: $maillogconvert_path OPEN\n";
	
	if(!is_file($maillogconvert_path)){
		echo "$maillogconvert_path no such file\n";
		return;
	}
	
	$fp = @fopen($maillogconvert_path, "r");
	if(!$fp){echo "$maillogconvert_path FOPEN FAILED\n";return false;}
	
	
	$prefix="INSERT INTO maillog (zmd5,zdate,fromdomain,todomain,relay_s,relay_r,frommail,tomail,size,smtp_code) ";
	$c=0;
	$f=array();
	while(!feof($fp)){
		$line = trim(fgets($fp));
		$line=str_replace("'", "", $line);
		$zmd5=md5($line);
		$TT=explode(" ",trim($line));
		
		$FROMDOMAIN=null;
		$TODOMAIN=null;
		//print "$year-$month-$day $time $from $to $relay_s $relay_r SMTP $extinfo $code $size $subject ";
		if(count($TT)<9){continue;}
		$date=$TT[0];
		$time=$TT[1];
		$xtime=strtotime("$date $time");
		if(date("Y",$xtime)<2014){if($zdateFile<>null){$xtime=strtotime("$zdateFile $time");}}
		if(date("Y",$xtime)<2014){continue;}
		
		$zdate=date("Y-m-d H:i:s",$xtime);
		
		$FROM=strtolower(trim($TT[2]));
		$TO=strtolower($TT[3]);
		if($TO=="-"){$TO="unknown";}
		
		if(strpos($FROM, "@")>0){
			$FROMT=explode("@",$FROM);
			$FROMDOMAIN=$FROMT[1];
		}
		
		if(strpos($TO, "@")>0){
			$TOT=explode("@",$TO);
			$TODOMAIN=$TOT[1];
		}
		
		if($FROMDOMAIN==null){$FROMDOMAIN="localhost";}
		if($TODOMAIN==null){$TODOMAIN="localhost";}
		
		$relay_source=$TT[4];
		$relay_recipient=$TT[5];
		$extinfo=$TT[7];
		$smtp_code=$TT[8];
		$size=$TT[9];
		if($smtp_code=="-"){$smtp_code=0;}
		if($size=="-"){$size=0;}
		
		if(!is_numeric($size)){$size=0;}
		if(!is_numeric($smtp_code)){$smtp_code=0;}
		if($extinfo=="-"){$extinfo=null;}
		if($FROM=="<>"){$FROM="postmaster";}
		$c++;
		$f[]="('$zmd5','$zdate','$FROMDOMAIN','$TODOMAIN','$relay_source','$relay_recipient','$FROM','$TO','$size','$smtp_code')";
		
		if(count($f)>800){ 
			echo "INSERTING $c\n";
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				echo $q->mysql_error."\n";
				fclose($fp);
				return false;
			}
			$f=array();
		}
		
		
	}
	
	fclose($fp);
	if($c==0){
		echo "$maillogconvert_path: FALSE $c items\n";
		@unlink($maillogconvert_path);
		return false;
	}
	
	if(count($f)>0){
		echo "FINAL: $c\n";
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			return false;
		}
		$f=array();
	}	
	
	@unlink($maillogconvert_path);
	echo "$maillogconvert_path: TRUE $c items\n";
	return true;
	
}



function smtpsum_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpsum_day", "artica_events")){
		echo "smtpsum_day no such table\n";
		return;
	}

	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtpsum_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpsum_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtpsum_day (zdate,zmd5,recipients,rejected,bounced,deferred,forwarded,delivered,received) ";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["recipients"]}','{$ligne["rejected"]}','{$ligne["bounced"]}','{$ligne["deferred"]}','{$ligne["forwarded"]}' ,'{$ligne["delivered"]}','{$ligne["received"]}')";
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){echo $q2->mysql_error."\n";return;}
			$f=array();
		}
	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){return;}
		$f=array();
	}
	
	echo "smtpsum_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpsum_day","artica_events");
	
}
function smtpgraph_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpgraph_day", "artica_events")){
		echo "smtpgraph_day no such table\n";
		return;
	}

	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtpgraph_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpgraph_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtpgraph_day (zDate,zmd5,range,received,delivered,deferred,bounced,rejected)";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["range"]}','{$ligne["RECEIVED"]}','{$ligne["DELIVERED"]}','{$ligne["DEFERRED"]}','{$ligne["BOUNCED"]}','{$ligne["REJECTED"]}')";
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){echo $q2->mysql_error."\n";return;}
			$f=array();
		}
	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){echo $q2->mysql_error."\n";return;}
		$f=array();
	}

	echo "smtpgraph_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpgraph_day","artica_events");

}
function smtpdeliver_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpdeliver_day", "artica_events")){
		echo "smtpdeliver_day no such table\n";
		return;
	}

	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtpdeliver_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpdeliver_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtpdeliver_day (zDate,zmd5,domain,rqs,size)";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["DOMAIN"]}','{$ligne["RQS"]}','{$ligne["SIZE"]}')";
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){echo $q2->mysql_error."\n";return;}
			$f=array();
		}
	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){echo $q2->mysql_error."\n";return;}
		$f=array();
	}

	echo "smtpdeliver_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpdeliver_day","artica_events");

}
function smtpsenders_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpsenders_day", "artica_events")){
		echo "smtpsenders_day no such table\n";
		return;
	}

	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtpsenders_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtpsenders_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtpsenders_day (zdate,zmd5,email,rqs)";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["email"]}','{$ligne["RQS"]}')";
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){echo $q2->mysql_error."\n";return;}
			$f=array();
		}
	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){echo $q2->mysql_error."\n";return;}
		$f=array();
	}

	echo "smtpsenders_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtpsenders_day","artica_events");

}
function smtprecipients_day_migrate_to_postgres(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtprecipients_day", "artica_events")){
		echo "smtprecipients_day no such table\n";
		return;
	}

	$q=new mysql();
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();

	$sql="SELECT * FROM smtprecipients_day";
	$results=$q->QUERY_SQL($sql,"artica_events");
	echo "smtprecipients_day ". mysql_num_rows($results)." rows...\n";

	$prefix="INSERT INTO smtprecipients_day (zdate,zmd5,email,rqs)";

	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["email"]=str_replace("'", "", $ligne["email"]);
		$f[]="('{$ligne["zDate"]}','{$ligne["zmd5"]}','{$ligne["email"]}','{$ligne["RQS"]}')";
		if(count($f)>500){
			$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){echo $q2->mysql_error."\n";return;}
			$f=array();
		}
	}

	if(count($f)>0){
		$sql="$prefix VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){echo $q2->mysql_error."\n";return;}
		$f=array();
	}

	echo "smtprecipients_day -> DROP\n";
	$q->QUERY_SQL("DROP TABLE smtprecipients_day","artica_events");

}
function connect_from($logpath){
	$unix=new unix();
	smtpstats_day_migrate_to_postgres();
	smtpcdir_day_migrate_to_postgres();
	smtpsum_day_migrate_to_postgres();
	smtpgraph_day_migrate_to_postgres();
	smtpdeliver_day_migrate_to_postgres();
	smtpsenders_day_migrate_to_postgres();
	smtprecipients_day_migrate_to_postgres();
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	$grep=$unix->find_program("grep");
	$tmpfile=$unix->FILE_TEMP();	
	shell_exec("$grep -e \"smtpd.*: connect from\" $logpath >$tmpfile");
	
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	$t=array();
	
	$fam=new familysite();
	
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+connect from\s+(.+?)\[([0-9\.]+)\]#", $line,$re)){continue;}
		$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
		$ipaddr=$re[5];
		$day=date("Y-m-d",$date);
		$NETZ=explode(".",$ipaddr);
		$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
		
		$hostname=$re[4];
		$familysite=$fam->GetFamilySites($hostname);
		
		if(!isset($MAINNETS[$day][$network]["CNX"])){
			$MAINNETS[$day][$network]["CNX"]=1;
		}else{
			$MAINNETS[$day][$network]["CNX"]=$MAINNETS[$day][$network]["CNX"]+1;
		}
		
		if(!isset($MAINNETS[$day][$network]["FAM"][$familysite])){
			$MAINNETS[$day][$network]["FAM"][$familysite]=1;
		}else{
			$MAINNETS[$day][$network]["FAM"][$familysite]=$MAINNETS[$day][$network]["FAM"][$familysite]+1;
		}
	
		
		
		if(!isset($MAIN[$day][$familysite]["IPS"][$ipaddr])){
			$MAIN[$day][$familysite]["IPS"][$ipaddr]=1;
		}else{
			$MAIN[$day][$familysite]["IPS"][$ipaddr]=$MAIN[$day][$familysite]["IPS"][$ipaddr]+1;
		}
		
		if(!isset($MAIN[$day][$familysite]["COUNT"])){
			$MAIN[$day][$familysite]["COUNT"]=1;
		}else{
			$MAIN[$day][$familysite]["COUNT"]=$MAIN[$day][$familysite]["COUNT"]+1;
		}
		
		if(!isset($MAIN[$day][$familysite]["HOSTS"][$hostname])){
			$MAIN[$day][$familysite]["HOSTS"][$hostname]=1;
		}else{
			$MAIN[$day][$familysite]["HOSTS"][$hostname]=$MAIN[$day][$familysite]["HOSTS"][$hostname]+1;
		}
		
		//echo date("Y-m-d")." $hostname $ipaddr\n";
	}
	
	@fclose($fp);
	@unlink($tmpfile);
	
	shell_exec("$grep -e \"NOQUEUE: milter-reject: RCPT from\" $logpath >$tmpfile");
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+NOQUEUE: milter-reject: RCPT from\s+(.*?)\[([0-9\.]+)\]:\s+([0-9]+)\s+#", $line,$re)){
			echo "NO MATCH $line\n";
			continue;}
		$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
		$hostname=$re[4];
		$ipaddr=$re[5];
		$CODE=$re[6];
		$day=date("Y-m-d",$date);
		$familysite=$fam->GetFamilySites($hostname);
		
		$NETZ=explode(".",$ipaddr);
		$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
		if(!isset($MAINNETS[$day][$network]["FAM"][$familysite])){
			$MAINNETS[$day][$network]["FAM"][$familysite]=1;
		}else{
			$MAINNETS[$day][$network]["FAM"][$familysite]=$MAINNETS[$day][$network]["FAM"][$familysite]+1;
		}
		
		
		if($CODE==451){
			
			if(!isset($MAINNETS[$day][$network]["GREY"])){
				$MAINNETS[$day][$network]["GREY"]=1;
			}else{
				$MAINNETS[$day][$network]["GREY"]=$MAINNETS[$day][$network]["GREY"]+1;
			}
			
			if(!isset($MAIN[$day][$familysite]["GREY"])){
				$MAIN[$day][$familysite]["GREY"]=1;
			}else{
				$MAIN[$day][$familysite]["GREY"]=$MAIN[$day][$familysite]["GREY"]+1;
			}
		}
		if($CODE==551){
			if(!isset($MAIN[$day][$familysite]["BLACK"])){
				$MAIN[$day][$familysite]["BLACK"]=1;
			}else{
				$MAIN[$day][$familysite]["BLACK"]=$MAIN[$day][$familysite]["BLACK"]+1;
			}
			
			if(!isset($MAINNETS[$day][$network]["BLACK"])){
				$MAINNETS[$day][$network]["BLACK"]=1;
			}else{
				$MAINNETS[$day][$network]["BLACK"]=$MAINNETS[$day][$network]["BLACK"]+1;
			}
			
		}		
		
	}
	
	@fclose($fp);
	@unlink($tmpfile);
	
	shell_exec("$grep -e \"NOQUEUE: reject: RCPT from\" $logpath >$tmpfile");
	$fp = @fopen($tmpfile, "r");
	if(!$fp){return false;}
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=trim($line);
		

		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+NOQUEUE: reject: RCPT from\s+(.*?)\[([0-9\.]+)\]:\s+([0-9]+)\s+#", $line,$re)){
			echo "NO MATCH $line\n";
			continue;}
			$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
			$hostname=$re[4];
			$ipaddr=$re[5];
			$CODE=$re[6];
			$day=date("Y-m-d",$date);
			$familysite=$fam->GetFamilySites($hostname);
			$NETZ=explode(".",$ipaddr);
			$network="{$NETZ[0]}.{$NETZ[1]}.{$NETZ[2]}.0/24";
		
			if(($CODE==551) OR ($CODE==554)){
				if(!isset($MAIN[$day][$familysite]["BLACK"])){
					$MAIN[$day][$familysite]["BLACK"]=1;
				}else{
					$MAIN[$day][$familysite]["BLACK"]=$MAIN[$day][$familysite]["BLACK"]+1;
				}
				
				if(!isset($MAINNETS[$day][$network]["BLACK"])){
					$MAINNETS[$day][$network]["BLACK"]=1;
				}else{
					$MAINNETS[$day][$network]["BLACK"]=$MAINNETS[$day][$network]["BLACK"]+1;
				}
				
			}
		
	}
	@fclose($fp);
	@unlink($tmpfile);
	
	
	
	
	$prefix="INSERT INTO smtpstats_day (zmd5,zdate,domain,grey,black,cnx,hosts,ips,infos) VALUES ";
	$q=new postgres_sql();
	
	while (list ($zDate, $ARRAY) = each ($MAIN) ){
		while (list ($domain, $INFOS) = each ($ARRAY) ){
			$GREY=0;
			if(!isset($INFOS["BLACK"])){$INFOS["BLACK"]=0;}
			if(!isset($INFOS["GREY"])){$INFOS["GREY"]=0;}
			$HOSTS=count($INFOS["HOSTS"]);
			$IPS=count($INFOS["IPS"]);
			$BLACK=intval($INFOS["BLACK"]);
			$CNX=intval($INFOS["COUNT"]);
			$INFO["IPS"]=$INFOS["IPS"];
			$INFO["HOSTS"]=$INFOS["HOSTS"];
			$infotext=mysql_escape_string2(serialize($INFO));
			
			if($GLOBALS["VERBOSE"]){echo "$zDate: $domain hosts:$HOSTS ips:$IPS blacklisted:$BLACK greylisted:$GREY cnx:$CNX $infotext\n";}
			$md5=md5("$zDate$domain$HOSTS$IPS$BLACK$GREY$CNX$infotext");
			
			$f[]="('$md5','$zDate','$domain','$GREY','$BLACK','$CNX','$HOSTS','$IPS','$infotext')";
			if(count($f)>500){
				$q->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
				if(!$q->ok){echo $q->mysql_error."\n";return;}
				$f=array();
			}
			
		}
		
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
	}
	

	$prefix="INSERT INTO smtpcdir_day (zmd5,zdate,cdir,grey,black,cnx,domains,infos) VALUES ";
	
	
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	
	while (list ($zDate, $ARRAY) = each ($MAINNETS) ){
		while (list ($CDIR, $INFOS) = each ($ARRAY) ){
			if(!isset($INFOS["BLACK"])){$INFOS["BLACK"]=0;}
			if(!isset($INFOS["GREY"])){$INFOS["GREY"]=0;}
			$CNX=intval($INFOS["CNX"]);
			$GREY=intval($INFOS["GREY"]);
			$BLACK=intval($INFOS["BLACK"]);
			$DOMAINS=intval($INFOS["FAM"]);
			$infotext=mysql_escape_string2(serialize($INFOS["FAM"]));
			echo "$zDate $CDIR cnx:$CNX greylisted:$GREY blacklisted:$BLACK domains:$DOMAINS\n";
			$md5=md5("$zDate$CDIR$DOMAINS$BLACK$GREY$CNX$infotext");
			$f[]="('$md5','$zDate','$CDIR','$GREY','$BLACK','$CNX','$DOMAINS','$infotext')";
			
			if(count($f)>500){
				$q->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
				if(!$q->ok){echo $q->mysql_error."\n";return;}
				$f=array();
			}
		}
		
		
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
	}
	
	return true;
	
	//print_r($MAINNETS);
	
}

function pflogsumm($filename){
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$binary="/usr/share/artica-postfix/bin/pflogsumm.pl";
	@chmod("$binary",0755);
	echo "$binary $filename >$tmpfile\n";
	system("$binary $filename >$tmpfile");
	if(ParseReport($tmpfile)){
		@unlink($tmpfile);
		return true;
	}
}
	
function ParseReport($filepath){
	$unix=new unix();
		$t1=time();
		$f=explode("\n",@file_get_contents($filepath));
		$q=new mysql();
		$HIER=$q->HIER();
		$q=new postgres_sql();
	
		$GrandTotals=false;
		while (list ($key, $value) = each ($f) ){
	
			if(preg_match("#Grand Totals#", $value)){$GrandTotals=true;}
			if($GrandTotals==false){continue;}
			if(preg_match("#([0-9]+)\s+received#", $value,$re)){
				$received=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+delivered#", $value,$re)){
				$delivered=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+forwarded#", $value,$re)){
				$forwarded=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+deferred#", $value,$re)){
				$deferred=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+bounced#", $value,$re)){
				$bounced=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+rejected#", $value,$re)){
				$rejected=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+senders#", $value,$re)){
				$senders=$re[1];
				continue;
			}
			if(preg_match("#([0-9]+)\s+recipients#", $value,$re)){
				$recipients=$re[1];
				continue;
			}
	
	
			if(preg_match("#Per-Hour Traffic Summary#", $value)){break;}
	
	
		}


	

	
	

	$q=new postgres_sql();
	$md5=md5("$HIER$received$delivered$forwarded$deferred$bounced$rejected$senders$recipients");
	
	$sql="INSERT INTO smtpsum_day (zmd5,zDate,recipients,rejected,bounced,deferred,forwarded,delivered,received)
		VALUES ('$md5','$HIER','$recipients','$rejected','$bounced','$deferred','$forwarded','$delivered','$received') ON CONFLICT DO NOTHING";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return false;}
	
	
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Per-Hour Traffic Summary#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
	
		if(preg_match("#([0-9]+)-([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $value,$re)){
				
			$range="{$re[1]}-{$re[2]}";
			$RECEIVED="{$re[3]}";
			$DELIVERED="{$re[4]}";
			$DEFERRED="{$re[5]}";
			$BOUNCED="{$re[6]}";
			$REJECTED=$re[7];
			
			$md5=md5("$HIER$value");
			
			$sql="INSERT INTO smtpgraph_day (zmd5,zDate,range,received,delivered,deferred,bounced,rejected)
			VALUES('$md5','$HIER','$range','$RECEIVED','$DELIVERED','$DEFERRED','$BOUNCED','$REJECTED') ON CONFLICT DO NOTHING";
			$q->QUERY_SQL($sql);
			if(!$q->ok){return false;}
			continue;
		}
	
	
		if(preg_match("#Host\/Domain Summary#", $value)){break;}
	
	}

	
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
	$q=new postgres_sql();
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Host\/Domain Summary: Message Delivery#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Host\/Domain Summary: Messages Received#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+([0-9km]+)\s+[0-9\.]+\s+[0-9\.]+\s+[a-z]\s+[0-9\.]+\s+[a-z]\s+(.+)#", $value,$re)){continue;}
		$size=0;
		$msg=$re[1];
		if(preg_match("#([0-9]+)k#", $re[2],$kr)){
			$size=$kr[1]*1024;
		}
		if(preg_match("#([0-9]+)m#", $re[2],$kr)){
			$size=$kr[1]*1024;
			$size=$size*1024;
		}
			
		if($size==0){$size=$re[2];}
		$domain=trim($re[3]);
		$md5=md5("$HIER$domain$msg$size");
		echo "('$domain','$msg','$size')\n";
		$TR[]="('$md5','$HIER','$domain','$msg','$size')";
		if(count($TR)>500){
			$q->QUERY_SQL("INSERT INTO smtpdeliver_day (zmd5,zDate,domain,rqs,size) VALUES ".@implode(",", $TR)." ON CONFLICT DO NOTHING");
			$TR=array();
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
	
	}
	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT INTO smtpdeliver_day (zmd5,zDate,domain,rqs,size) VALUES ".@implode(",", $TR)." ON CONFLICT DO NOTHING");
		$TR=array();
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	

	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	$q=new postgres_sql();
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Senders by message count#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Recipients by message count#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
		$email=mysql_escape_string2(trim(strtolower($re[2])));
		$msg=$re[1];
		if($email=="from=<>"){$email="Postmaster";}
		$md5=md5("$HIER$email$msg");
		echo "('$md5','$HIER','$email','$msg')\n";
		$TR[]="('$md5','$HIER','$email','$msg')";
			
		if(count($TR)>500){
			$q->QUERY_SQL("INSERT INTO smtpsenders_day (zmd5,zDate,email,rqs) VALUES ".
			@implode(",", $TR)." ON CONFLICT DO NOTHING");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
	
	}
	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT INTO smtpsenders_day (zmd5,zdate,email,rqs) VALUES ".
		@implode(",", $TR)." ON CONFLICT DO NOTHING");
	}
	
	

	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	$q=new postgres_sql();
	while (list ($key, $value) = each ($f) ){
	if(preg_match("#Recipients by message count#", $value)){$GrandTotals=true;}
	if($GrandTotals==false){continue;}
	
	if(preg_match("#Senders by message size#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
			$email=mysql_escape_string2(trim(strtolower($re[2])));
			$msg=$re[1];
			if($email=="from=<>"){$email="Postmaster";}
			$md5=md5("$HIER$email$msg");
			echo "('$md5','$HIER','$email','$msg')\n";
			$TR[]="('$md5','$HIER','$email','$msg')";
				
			if(count($TR)>500){
				$q->QUERY_SQL("INSERT INTO smtprecipients_day (zmd5,zdate,email,rqs) VALUES ".
				@implode(",", $TR)." ON CONFLICT DO NOTHING");
				if(!$q->ok){echo $q->mysql_error."\n";}
			}
	
	}	
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT INTO smtprecipients_day (zmd5,zdate,email,rqs) VALUES ".
				@implode(",", $TR)." ON CONFLICT DO NOTHING");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	$took=$unix->distanceOfTimeInWords($t1,time());
	$filepathBase=basename($filepathBase);
	postfix_admin_mysql(2, "Success calculating statistics on $filepathBase took:$took", null,__FILE__,__LINE__);
	return true;
}


function compressAndClean(){
	@unlink("/etc/artica-postfix/POSTFIX_COMPRESS_CLEAN.time");
	@file_put_contents("/etc/artica-postfix/POSTFIX_COMPRESS_CLEAN.time", time());
	$unix=new unix();
	$q=new mysql();
	$hier=$q->HIER();
	$targetSourceFile="$hier.log";
	
	if(system_is_overloaded(basename(__FILE__))){
		postfix_admin_mysql(0, "Overloaded system, aborting rotation compressing", null,__FILE__,__LINE__);
		return;
	}

	$BaseWorkDir="/home/postfix/logrotate";
	$targetcompressed="/home/postfix/logrotate/$hier.gz";
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetfile="$BaseWorkDir/$filename";
		if(strpos($filename, ".gz")>0){continue;}
		if($filename==$targetSourceFile){
			echo "Hier: $targetSourceFile was not compressed!\n";
			if(is_file($targetfile)){
				if(!connect_from($targetfile)){
					postfix_admin_mysql(0, "FATAL! $targetfile connect_from() failed", null,__FILE__,__LINE__);
					return;
				}
				if(!pflogsumm($targetfile)){
					postfix_admin_mysql(0, "FATAL! $targetfile pflogsumm() failed", null,__FILE__,__LINE__);
					return;
				}
				if(!$unix->compress($targetfile, $targetcompressed)){
					@unlink($targetcompressed);
					continue;
				}
				@unlink($targetfile);
			
			
			}
			continue;
		}
		
		$ToCompressPath="$BaseWorkDir/$filename";
		$ToCompressPath=str_replace(".log", ".gz", $ToCompressPath);
		echo "Compressing $targetfile -> $ToCompressPath\n";
		if(!$unix->compress($targetfile, $ToCompressPath)){
			echo "Compressing $targetfile -> $ToCompressPath - FAILED -\n";
			@unlink($ToCompressPath);
			continue;
		}else{
			postfix_admin_mysql(2, "Success compressing $targetfile", null,__FILE__,__LINE__);
			@unlink($targetfile);
		}
		
		
	}
		
	
}


?>





