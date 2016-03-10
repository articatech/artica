<?php
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["DEBUG_SQL"]=true;
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$BASEDIR="/usr/share/artica-postfix";

include_once($BASEDIR . '/ressources/class.users.menus.inc');
include_once($BASEDIR . '/ressources/class.sockets.inc');
include_once($BASEDIR . '/framework/class.unix.inc');
include_once($BASEDIR. '/framework/frame.class.inc');
include_once($BASEDIR. '/ressources/class.iptables-chains.inc');
include_once($BASEDIR . '/ressources/class.mysql.haproxy.builder.php');
include_once($BASEDIR . "/ressources/class.mysql.squid.builder.php");
include_once($BASEDIR. "/ressources/class.mysql.builder.inc");
include_once($BASEDIR . "/ressources/class.mysql.syslogs.inc");

xstart();

function xstart(){
	
	$timeFile="/etc/artica-postfix/pids/exec.system.last.php.xstart.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.system.last.php.xstart.php.pid";
	$unix=new unix();
	$RARRAY=array();
if(!$GLOBALS["VERBOSE"]){
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return;}	
	
	$timefile=$unix->file_time_min($timeFile);
	if($timefile<60){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());

}
	
	$last=$unix->find_program("last");
	$results=array();
	exec("$last reboot 2>&1",$results);
	if(count($results)==0){return;}
	sort($results);
	$linze=array();
	$lasttime=0;
	$DIFF=0;
	while (list ($num, $line) = each ($results) ){
		if(!preg_match("#reboot\s+(.+?)\s+([0-9]+).*?\s+(.+?)-\s+([0-9:]+)\s+#", $line,$re)){continue;}
	
		$time=strtotime($re[3]);
		$RARRAY[$time]=$line;
	}
	
	krsort($RARRAY);
	
	while (list ($time, $line) = each ($RARRAY) ){
		if(!preg_match("#reboot\s+(.+?)\s+([0-9]+).*?\s+(.+?)-\s+([0-9:]+)\s+#", $line,$re)){continue;}	
		if($lasttime>0){$DIFF=$lasttime;}
		$action=$re[1];
		$lasttime=$time;
		$DATE1=date("Y-m-d H:i:s",$time);
		$md5=md5("$action$time$DATE1");
		
		$cmds[]="DELETE FROM last_boot WHERE zmd5='$md5'";
		$yline="('$md5','$action','$DATE1','$time','$DIFF')";
		if($GLOBALS["VERBOSE"]){echo "$yline\n";}
		$linze[]=$yline;
	}
	
	
	if(count($linze)==0){return;}
	$q=new mysql();
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `last_boot` (
				`zmd5` VARCHAR( 90 ),
				`zDate` TIMESTAMP NOT NULL ,
				`subject` VARCHAR( 255 ),
				`ztime` INT UNSIGNED,
				`ztime2` INT UNSIGNED,
				 PRIMARY KEY (`zmd5`),
 				  KEY `zDate` (`zDate`),
				  KEY `subject` (`subject`)
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}
	
	while (list ($num, $sql) = each ($cmds) ){
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$q->QUERY_SQL($sql,"artica_events");
	
	}
	
	
	$sql="INSERT IGNORE INTO last_boot (zmd5,subject,zDate,ztime,ztime2) VALUES ".@implode(",", $linze);
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}

}
