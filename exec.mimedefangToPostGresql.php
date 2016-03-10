<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["EXECUTED_AS_ROOT"]=true;
if($GLOBALS["VERBOSE"]){$GLOBALS["OUTPUT"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include(dirname(__FILE__).'/ressources/class.amavis.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).  "/ressources/smtp/smtp.php");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

xrun();


function xrun(){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$TimeFile="/etc/artica-postfix/pids/exec.mimedefangToPostGresql.php.xrun.time";
		$me=basename(__FILE__);
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,$me)){
			events("Already executed.. $pid aborting the process",__LINE__);
			if($GLOBALS["VERBOSE"]){echo " --> Already executed.. $pid aborting the process\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
		
		$TimeExec=$unix->file_time_min($TimeFile);
		if($TimeExec<3){
			events("{$TimeExec}mn, require 3mn",__LINE__);
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	

	
	$q=new mysql();
	$Countrows=$q->COUNT_ROWS("mimedefang_stats", "artica_backup");
	events("mimedefang_stats = $Countrows",__LINE__);
	if($Countrows==0){
		$q2=new postgres_sql();
		$q2->SMTP_TABLES();
		return AttachStats();}
	
	$q2=new postgres_sql();
	$q2->SMTP_TABLES();
	
	$results=$q->QUERY_SQL("SELECT * FROM mimedefang_stats","artica_backup");
	

	$prefix="INSERT INTO smtpstats (zmd5,zdate,mailfrom,domainfrom,mailto,domainto,subject,size,spamscore,spamreport,disclaimer,backuped,infected,filtered,whitelisted,compressed,stripped) VALUES ";
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$mailfrom=$ligne["mailfrom"];
		$mailfrom=str_replace("<", "", $mailfrom);
		$mailfrom=str_replace(">", "", $mailfrom);
		$zdate=$ligne["zdate"];
		$mailto=$ligne["mailto"];
		$mailto=str_replace("<", "", $mailto);
		$mailto=str_replace(">", "", $mailto);
		
		
		$domainfrom=$ligne["domainfrom"];
		$domainto=$ligne["domainto"];
		$subject=trim($ligne["subject"]);
		
		$subject=str_replace("'", "`", $subject);
		$subject=str_replace("\"", "`", $subject);
		$subject=utf8_encode($subject);
		
		
		$size=intval($ligne["size"]);
		$spamscore=intval($ligne["spamscore"]);
		$disclaimer=intval($ligne["disclaimer"]);
		$backuped=intval($ligne["backuped"]);
		$infected=intval($ligne["infected"]);
		$filtered=intval($ligne["filtered"]);
		$whitelisted=intval($ligne["whitelisted"]);
		$compressed=intval($ligne["compressed"]);
		$stripped=intval($ligne["stripped"]);
		$spamreport=$ligne["spamreport"];
		$zmd5=md5(serialize($ligne));
		$zdateFinale=$zdate;
		$f[]="('$zmd5','$zdate','$mailfrom','$domainfrom','$mailto','$domainto','$subject','$size','$spamscore','$spamreport','$disclaimer','$backuped','$infected','$filtered','$whitelisted','$compressed','$stripped')";
		if(count($f)>500){
			events("mimedefang_stats = Inserting ".count($f),__LINE__);
			$q2->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
			if(!$q2->ok){
				events("$q2->mysql_error",__LINE__);
				echo $q2->mysql_error."\n".$prefix.@implode(",", $f)." ON CONFLICT DO NOTHING\n";
				AttachStats();
				return;
			}
			$f=array();
		}
	}
	
	
	if(count($f)>0){
		events("mimedefang_stats = Inserting ".count($f),__LINE__);
		$q2->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
		if(!$q2->ok){events("$q2->mysql_error",__LINE__);AttachStats();return;}
	}	
	
	events("DELETE FROM mimedefang_stats WHERE zdate<='$zdateFinale'",__LINE__);
	$q->QUERY_SQL("DELETE FROM mimedefang_stats WHERE zdate<='$zdateFinale'","artica_backup");
	if(!$q->ok){
		events("$q->mysql_error",__LINE__);
		echo $q->mysql_error."\n";
	}
	AttachStats();
	
}

function AttachStats(){
	$q=new mysql();
	$Countrows=$q->COUNT_ROWS("mimedefang_parts", "artica_backup");
	events("mimedefang_parts = COUNT_ROWS -> $Countrows",__LINE__);
	if($Countrows==0){return;}
	
	$q2=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT * FROM mimedefang_parts","artica_backup");
	
	
	$prefix="INSERT INTO attachstats (zmd5,zdate,mailfrom,domainfrom,mailto,domainto,fname,ext,contenttype,size) VALUES ";
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$mailfrom=$ligne["mailfrom"];
		$mailfrom=str_replace("<", "", $mailfrom);
		$mailfrom=str_replace(">", "", $mailfrom);
		$zdate=$ligne["zdate"];
		$mailto=$ligne["mailto"];
		$mailto=str_replace("<", "", $mailto);
		$mailto=str_replace(">", "", $mailto);
	
	
		$domainfrom=$ligne["domainfrom"];
		$domainto=$ligne["domainto"];
		$fname=trim($ligne["fname"]);
		$fname=str_replace("'", "`", $fname);
		$fname=str_replace("\"", "`", $fname);
		$fname=utf8_encode($fname);
		$size=intval($ligne["size"]);
		$ext=strtolower($ligne["ext"]);
		$contenttype=$ligne["contenttype"];
		$zmd5=md5(serialize($ligne));
		$zdateFinale=$zdate;
	
		$f[]="('$zmd5','$zdate','$mailfrom','$domainfrom','$mailto','$domainto','$fname','$ext','$contenttype','$size')";
		if(count($f)>500){
			events("mimedefang_parts = Inserting ".count($f),__LINE__);
			$q2->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
			if(!$q2->ok){
				events("$q2->mysql_error",__LINE__);
				echo $q2->mysql_error."\n".$prefix.@implode(",", $f)." ON CONFLICT DO NOTHING\n";
				return;
			}
			$f=array();
		}
	}
	
	
	if(count($f)>0){
		events("mimedefang_parts = Inserting ".count($f),__LINE__);
		$q2->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
		if(!$q2->ok){events("$q2->mysql_error",__LINE__);return;}
	}
	
	events("DELETE FROM mimedefang_parts WHERE zdate<='$zdateFinale'",__LINE__);
	$q->QUERY_SQL("DELETE FROM mimedefang_parts WHERE zdate<='$zdateFinale'","artica_backup");
	if(!$q->ok){
		events("$q->mysql_error",__LINE__);
		echo $q->mysql_error."\n";
	}
	
	
	
}

function events($text,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/mimedefang-postgres.log",false,"xrun",$line);
	
}

