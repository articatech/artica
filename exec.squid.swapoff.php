<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");


start();




function start(){
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";

	
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){ return;}
	@file_put_contents($pidFile, getmypid());
	
	
	$time=$unix->file_time_min($pidtime);
	if($time<5){return;}
	@file_put_contents($pidtime,time());
	
	
	$free=$unix->find_program("free");
	$echo=$unix->find_program("echo");
	$sync=$unix->find_program("sync");
	$swapoff=$unix->find_program("swapoff");
	$swapon=$unix->find_program("swapon");

	exec("$free 2>&1",$results);
	$used=0;
	$total=0;
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$total=$re[1];
			$used=$re[2];
				
		}
	
	}
	if(!is_numeric($total)){return;}
	if($total==0){return;}
	if($used==0){return;}
	if($total==$used){return;}
	$tot1=$used/$total;
	$tot1=$tot1*100;
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total - $tot1\n";}
	
	
	
	$perc=round($tot1);
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total {$perc}%\n";}
	
	
	
	$t=time();
	$GLOBALS["ALL_SCORES"]++;
	shell_exec("$swapoff -a && $swapon -a");
	$usedTXT=FormatBytes($used);
	$report=$unix->ps_mem_report();
	$distance=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(0,"System swap exceed rule: {$perc}%","Used $usedTXT\nSystem cache was flushed took $distance\nThis means you did have enough memory for this computer.\n$report",__FILE__,__LINE__);
	
}