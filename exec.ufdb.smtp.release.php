<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbgard-msmtp.inc');


xstart();





function xstart(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	
	$pid=@file_get_contents($pidfile);
	
	
	if($GLOBALS["VERBOSE"]){
		echo "$pidtime\n";
	}
	
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
			die();
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$timefile=$unix->file_time_min($pidtime);
	if($GLOBALS["VERBOSE"]){echo "Timelock:$pidtime $timefile Mn\n";}
	
	if(!$GLOBALS["FORCE"]){
		if($timefile<5){
			if($GLOBALS["VERBOSE"]){echo "{$timefile}mn require 5mn\n";}
			return;
		}
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	if(!is_file("/home/ufdb/smtp-events/ACCESS_LOG")){
		if($GLOBALS["VERBOSE"]){echo "/home/ufdb/smtp-events/ACCESS_LOG no such file\n";}
	}
	
	$array=explode("\n",@file_get_contents("/home/ufdb/smtp-events/ACCESS_LOG"));
	@unlink("/home/ufdb/smtp-events/ACCESS_LOG");
	
	
	$body=array();
	$mmstp=new ufdb_msmtp();
	$Subject=count($array)." Web filtering blocked event(s)";
	
	$body[]="Return-Path: <$mmstp->smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $mmstp->smtp_sender";
	$body[]="Subject: $Subject";
	$body[]="To: $mmstp->recipient";
	$body[]="";
	$body[]="";
	
	
	
	$body[]=@implode("\r\n", $array);
	$body[]="";
	$body[]="";
	$finalbody=@implode("\r\n", $body);
	
	if(!$mmstp->Send($finalbody)){
		squid_admin_mysql(0, "Unable to send notification $Subject to $mmstp->recipient",
		"$Subject to $mmstp->recipient\n------------------\n".@implode("\n", $array)."
		The following error encountered\n".$mmstp->logs."\n",__FILE__,__LINE__
		);
		
	}

	
}




