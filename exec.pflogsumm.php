<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/smtp-cache";
@mkdir($GLOBALS["BASEDIR"],0755,true);
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
if($argv[1]=="--parse"){ParseReport($argv[2]);exit;}
if($argv[1]=="--stats"){stats_total();exit;}


run();


function run(){
	
	$TimeFile="/etc/artica-postfix/pids/". basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/". basename(__FILE__).".pid";
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	
	$pid=$unix->get_pid_from_file($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS["VERBOSE"]){
			$time=$unix->file_time_min($TimeFile);
			if($time<14){
				echo "Current {$time}Mn, require at least 14mn\n";
				return;
			}
		}}
		
		
	$tmpfileSource=$unix->FILE_TEMP();	
	$year=date("Y");
	@copy("/var/log/mail.log", $tmpfileSource);
	
	
	
	$binary="/usr/share/artica-postfix/bin/pflogsumm.pl";	
	@chmod("$binary",0755);
	system("$binary -d today $tmpfileSource >$tmpfile");
	@unlink($tmpfileSource);
	ParseReport($tmpfile);
	@unlink($tmpfile);
	stats_total(true);
	
	
}



function ParseReport($filepath){
	
	
	$f=explode("\n",@file_get_contents($filepath));
	
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		
		if(preg_match("#Grand Totals#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#([0-9]+)\s+received#", $value,$re)){
			$MAIN["received"]=$re[1];
			continue;
		}
		if(preg_match("#([0-9]+)\s+delivered#", $value,$re)){
			$MAIN["delivered"]=$re[1];
			continue;
		}
		if(preg_match("#([0-9]+)\s+forwarded#", $value,$re)){
			$MAIN["forwarded"]=$re[1];
			continue;
		}		
		if(preg_match("#([0-9]+)\s+deferred#", $value,$re)){
			$MAIN["deferred"]=$re[1];
			continue;
		}		
		if(preg_match("#([0-9]+)\s+bounced#", $value,$re)){
			$MAIN["bounced"]=$re[1];
			continue;
		}
		if(preg_match("#([0-9]+)\s+rejected#", $value,$re)){
			$MAIN["rejected"]=$re[1];
			continue;
		}		
		if(preg_match("#([0-9]+)\s+senders#", $value,$re)){
			$MAIN["senders"]=$re[1];
			continue;
		}		
		if(preg_match("#([0-9]+)\s+recipients#", $value,$re)){
			$MAIN["recipients"]=$re[1];
			continue;
		}
		

		if(preg_match("#Per-Hour Traffic Summary#", $value)){break;}
		
		
	}
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_TOTALS", serialize($MAIN));
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Per-Hour Traffic Summary#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		
		if(preg_match("#([0-9]+)-([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $value,$re)){
			
			$MAIN["RECEIVED"]["X"][]="{$re[1]}-{$re[2]}";
			$MAIN["RECEIVED"]["Y"][]="{$re[3]}";
			
			$MAIN["DELIVERED"]["X"][]="{$re[1]}-{$re[2]}";
			$MAIN["DELIVERED"]["Y"][]="{$re[4]}";
			
			$MAIN["DEFERRED"]["X"][]="{$re[1]}-{$re[2]}";
			$MAIN["DEFERRED"]["Y"][]="{$re[5]}";			
			
			$MAIN["BOUNCED"]["X"][]="{$re[1]}-{$re[2]}";
			$MAIN["BOUNCED"]["Y"][]="{$re[6]}";
			
			$MAIN["REJECTED"]["X"][]="{$re[1]}-{$re[2]}";
			$MAIN["REJECTED"]["Y"][]="{$re[7]}";
			continue;
		}else{
			echo "$value\n";
		}
		
		if(preg_match("#Host\/Domain Summary#", $value)){break;}
		
	}
		
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS", serialize($MAIN));
	
	
	reset($f);
	$MAIN=array();
	$GrandTotals=false;
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
			echo "('$domain','$msg','$size')\n";
			$TR[]="('$domain','$msg','$size')";
			
		
	}
	
	
	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_smtpdeliver` (
	`DOMAIN` VARCHAR(128), `SIZE` BIGINT UNSIGNED, `RQS` BIGINT UNSIGNED, 
	KEY `DOMAIN` (`DOMAIN`), KEY `SIZE` (`SIZE`), KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$q->QUERY_SQL("TRUNCATE TABLE dashboard_smtpdeliver","artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO dashboard_smtpdeliver (DOMAIN,RQS,SIZE) VALUES ".@implode(",", $TR),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Senders by message count#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Recipients by message count#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
		$email=mysql_escape_string2(trim(strtolower($re[2])));
		$msg=$re[1];
		if($email=="from=<>"){$email="Postmaster";}
		echo "('$email','$msg')\n";
		$TR[]="('$email','$msg')";
			
	
	}
		
	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_smtpsenders` (
	`email` VARCHAR(128), `RQS` BIGINT UNSIGNED,
	KEY `email` (`email`), KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$q->QUERY_SQL("TRUNCATE TABLE dashboard_smtpsenders","artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO dashboard_smtpsenders (email,RQS) VALUES ".
			@implode(",", $TR),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";}	
	}
	
	
	
	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#Recipients by message count#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		if(preg_match("#Senders by message size#", $value)){break;}
		if(!preg_match("#([0-9]+)\s+(.+)#", $value,$re)){continue;}
		$email=mysql_escape_string2(trim(strtolower($re[2])));
		$msg=$re[1];
		if($email=="from=<>"){$email="Postmaster";}
		echo "('$email','$msg')\n";
		$TR[]="('$email','$msg')";
			
	
	}
	
	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_smtprecipients` (
	`email` VARCHAR(128), `RQS` BIGINT UNSIGNED,
	KEY `email` (`email`), KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
	if(!$q->ok){echo $q->mysql_error."\n";}
	
		$q->QUERY_SQL("TRUNCATE TABLE dashboard_smtprecipients","artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	if(count($TR)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO dashboard_smtprecipients (email,RQS) VALUES ".
				@implode(",", $TR),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	
	reset($f);
	$TR=array();
	$MAIN=array();
	$GrandTotals=false;
	$ARRR=array();
	while (list ($key, $value) = each ($f) ){
		if(preg_match("#^Warnings$#", trim($value))){break;}
		if(preg_match("#message reject detail#", $value)){$GrandTotals=true;}
		if($GrandTotals==false){continue;}
		
		
		if(!preg_match("#(.+?)\(total:\s+([0-9]+)\)#", $value,$re)){continue;}
		$reject=$re[1];
		$msg=$re[2];
		if(preg_match("#^(.+?):#", $reject,$re)){$reject=$re[1];}
		if(preg_match("#blocked using\s+(.+)\s+#", $reject,$re)){$reject=$re[1];}
		
		
		$reject=trim(strtolower($reject));
		$reject=mysql_escape_string2($reject);
		if(!isset($ARRR[$reject])){
			$ARRR[$reject]=$msg;
			continue;
		}
		$ARRR[$reject]=$ARRR[$reject]+$msg;
	}
	while (list ($reject, $msg) = each ($ARRR) ){		
		echo "('$reject','$msg')\n";
		$TR[]="('$reject','$msg')";
}

$q=new mysql();
$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_smtprejects` (
	`rule` VARCHAR(128), `RQS` BIGINT UNSIGNED,
	KEY `rule` (`rule`), KEY `RQS` (`RQS`) ) ENGINE=MYISAM;","artica_events" );
if(!$q->ok){echo $q->mysql_error."\n";}

$q->QUERY_SQL("TRUNCATE TABLE dashboard_smtprejects","artica_events");
if(!$q->ok){echo $q->mysql_error."\n";}

if(count($TR)>0){
	$q->QUERY_SQL("INSERT IGNORE INTO dashboard_smtprejects (rule,RQS) VALUES ".
		@implode(",", $TR),"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
}

function stats_total($nopid=false){
	
	$TimeFile="/etc/artica-postfix/pids/exec.pflogsumm.php.stats_total.time";
	$pidfile="/etc/artica-postfix/pids/exec.pflogsumm.php.stats_total.pid";
	$unix=new unix();
	
	
	if(!$nopid){
		if(system_is_overloaded(basename(__FILE__))){
			die();
		}
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
			if(!$GLOBALS["FORCE"]){
				if($timepid<14){return;}
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
			}
		}
	
		@file_put_contents($pidfile, getmypid());
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["VERBOSE"]){
				$time=$unix->file_time_min($TimeFile);
				if($time<5){
					echo "Current {$time}Mn, require at least 5mn\n";
					return;
				}
			}
		}
	}
	
	
	
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	
	$sql="SELECT COUNT(*) as tcount FROM (SELECT SUM(grey) as grey, SUM(black) AS black, SUM(cnx) as cnx,cdir FROM smtpcdir_day GROUP BY cdir) as t;";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_CDIR=$ligne["tcount"];
	
	
	$sql="SELECT COUNT(*) as tcount FROM smtprefused";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SMTP_REFUSED=$ligne["tcount"];
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["BASEDIR"]}/SMTP_REFUSED ->$SMTP_REFUSED\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_REFUSED", $SMTP_REFUSED);
	
	
	$sql="SELECT COUNT(*) as tcount FROM (SELECT SUM(grey) as grey, SUM(black) AS black, SUM(cnx) as cnx,domain FROM smtpstats_day GROUP BY domain) as t;";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_DOMAINS=$ligne["tcount"];
		
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["BASEDIR"]}/SMTP_SUM_CDIR ->$SUM_CDIR\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_CDIR", $SUM_CDIR);
	
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["BASEDIR"]}/SMTP_SUM_DOMAINS ->$SUM_DOMAINS\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_DOMAINS", $SUM_DOMAINS);
	
	
	
	$sql="SELECT COUNT(*) as tcount,SUM(size) as size FROM smtpstats";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_TRANSCATS=$ligne["tcount"];
	$SUM_SIZE=$ligne["size"];
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["BASEDIR"]}/SUM_TRANSCATS ->$SUM_TRANSCATS\n";}
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["BASEDIR"]}/SUM_TRANSCATS_SIZE ->$SUM_SIZE\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SUM_TRANSCATS", $SUM_TRANSCATS);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SUM_TRANSCATS_SIZE", $SUM_SIZE);
	
	$sql="SELECT COUNT(*) as tcount FROM smtprefused";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_REFUSED=$ligne["tcount"];
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_REFUSED", $SUM_REFUSED);
	
	
	$sql="SELECT COUNT(*) as tcount, SUM(size) as size FROM backupmsg";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_BACK=$ligne["tcount"];
	$SUM_BACKSIZE=$ligne["size"];
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_BACK", $SUM_BACK);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_BACKSIZE", $SUM_BACKSIZE);
	
	$sql="SELECT COUNT(*) as tcount, SUM(size) as size FROM quarmsg";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_BACK=$ligne["tcount"];
	$SUM_BACKSIZE=$ligne["size"];
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_QUAR", $SUM_BACK);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_QUARSIZE", $SUM_BACKSIZE);
	
	$sql="SELECT COUNT(*) as tcount, SUM(size) as size FROM attachstats";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_BACK=$ligne["tcount"];
	$SUM_BACKSIZE=$ligne["size"];
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_ATTACH", $SUM_BACK);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/SMTP_SUM_ATTACHSIZE", $SUM_BACKSIZE);
	
	
	
	
}


