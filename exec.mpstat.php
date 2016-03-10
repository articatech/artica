<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) .'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");


start();

function start(){

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
if($GLOBALS["VERBOSE"]){echo "TimeFile:$pidTime\n";}
$unix=new unix();
if($unix->file_time_min($pidTime)<1){die();}


if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);die();}

@file_put_contents($pidfile, getmypid());

if(system_is_overloaded(basename(__FILE__))){
	if($GLOBALS["VERBOSE"]){echo "die, overloaded\n";}
	die();
}


@unlink($pidTime);
@file_put_contents($pidTime, time());

if($argv[1]=='email'){BuildWarning('100','0');exit;}


	$timef=file_get_time_min("/etc/artica-postfix/croned.2/".md5(__FILE__));
	if($timef<5){events("die, 5mn minimal current {$timef}mn");die();}
	@unlink("/etc/artica-postfix/croned.2/".md5(__FILE__));
	@file_put_contents("/etc/artica-postfix/croned.2/".md5(__FILE__),date('Y-m-d H:i:s'));


	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini->loadString($sock->GET_INFO("SmtpNotificationConfig"));
	
	if(!isset($ini->_params["SMTP"]["SystemCPUAlarm"])){die();}
	if($ini->_params["SMTP"]["SystemCPUAlarm"]==0){ die(); }
	
	
	if(!is_numeric($ini->_params["SMTP"]["enabled"])){$ini->_params["SMTP"]["enabled"]=1;}
	if(!is_numeric($ini->_params["SMTP"]["SystemCPUAlarm"])){$ini->_params["SMTP"]["SystemCPUAlarm"]=0;}
	
	if($ini->_params["SMTP"]["SystemCPUAlarm"]==null){$ini->_params["SMTP"]["SystemCPUAlarm"]=0;}
	if($ini->_params["SMTP"]["SystemCPUAlarmPourc"]==null){$ini->_params["SMTP"]["SystemCPUAlarmPourc"]=95;}
	if($ini->_params["SMTP"]["SystemCPUAlarmMin"]==null){$ini->_params["SMTP"]["SystemCPUAlarmMin"]=5;}
	if($ini->_params["SMTP"]["enabled"]==0){events("$page SMTP notification is not enabled");die();}
	
	$filestatus="/etc/artica-postfix/mpstat.status";
	
	$timestamp=mktime(date("H"),date("i"),0,date('m'),date('Y'));
	$timestamp_string=date("H").",".date("i").",".date('j');

	if(!isset($GLOBALS["ISVALS"])){
		$GLOBALS["ISVALS"]=trim(exec('/usr/share/artica-postfix/bin/cpu-alarm.pl'));
	}
	$cpu=intval($GLOBALS["ISVALS"]);
	if(!is_file($filestatus)){file_put_contents($filestatus,"$timestamp_string;$cpu\n");events("$page CPU: $cpu%");die();}
	
	$cpu_total=0;
	$count=0;
	$file_datas=explode("\n",file_get_contents($filestatus));
	events("$filestatus=". count($file_datas)." lines number");
	$old_timestamp=0;


	while (list ($num, $ligne) = each ($file_datas) ){
		if(trim($ligne==null)){continue;}
		usleep(300000);
		if(preg_match('#^([0-9,]+);(.+)#',$ligne,$re)){
			$newfileARRAY[]=$ligne;
			$count=$count+1;
			$t=explode(",",$re[1]);
			if($old_timestamp==0){$old_timestamp=mktime($t[0],$t[1],0,date('m'),date('Y'));events("old_timestamp=$old_timestamp line $num");}
				$cpu_total=$cpu_total+intval(trim($re[2]));
			}else{
				events("$page unable to preg_match $ligne");
			}
	}
	
	$cpu_total=$cpu_total+$cpu;
	$cpuaverage=floor($cpu_total/($count+1));
	$difference = ($timestamp - $old_timestamp);
	$difference=str_replace("-",'',$difference);
	$difference=intval($difference);	 
	$filetime=floor($difference/60);
	$newfileARRAY[]="$timestamp_string;$cpu";


	events("$page CPU average: $cpuaverage% last cpu in ".$filetime." minute(s) \"$difference\" [must reach {$ini->_params["SMTP"]["SystemCPUAlarmMin"]}mn] cache file=$count line(s): current: $cpu%");

	if($filetime<$ini->_params["SMTP"]["SystemCPUAlarmMin"]){file_put_contents($filestatus,implode("\n",$newfileARRAY));die();}
	
	if($cpuaverage>=$ini->_params["SMTP"]["SystemCPUAlarmPourc"]){
		if(system_is_overloaded()){
			events("$page Build warning CPU overload $cpu% and overloaded {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}/{$GLOBALS["SYSTEM_MAX_LOAD"]}");
			BuildWarning($cpuaverage,$filetime);
		}
	}
	
	events("$page Clean cache...");			
	unset($newfileARRAY);
	$newfileARRAY[]="$timestamp_string;$cpu";
	file_put_contents($filestatus,implode("\n",$newfileARRAY));


}

function BuildWarning($cpu,$time){
	
	$load = sys_getloadavg();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	
	$ldtext[]="**** Current system load ****";
	$ldtext[]="Load 1mn.: ".$load[0];
	$ldtext[]="Load 5mn.: ".$load[1];
	$ldtext[]="Load 15mn: ".$load[2];
	$ldtext[]="*****************************";
	
	$subject="CPU overload ($cpu%) and overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}/{$GLOBALS["SYSTEM_MAX_LOAD"]})";
	shell_exec("/bin/ps -w axo ppid,pcpu,pmem,time,args --sort -pcpu,-pmem|/usr/bin/head --lines=20 >/tmp.top.txt 2>&1");
	$top=file_get_contents("/tmp.top.txt");
	@unlink("/tmp.top.txt");
	$top=SafeProcesses()."\n".$top;
	$text="Server report that your $hostname server has reach $cpu% CPU average consumption in $time minute(s)\n".@implode("\n", $ldtext)."\nYou will find below a processes report:\n---------------------------------------------\n$top\nGenerated by ". basename(__FILE__)." (". __FUNCTION__." on line ". __LINE__.") at ". date("H:i:s")."";
	send_email_events($subject,$text,'system');
}



function SafeProcesses(){
	$users=new usersMenus();
	if($users->CPU_NUMBER>1){return;}
	$sock=new sockets();
	$DisableSafeProcesses=$sock->GET_INFO("DisableSafeProcesses");
	if(!is_numeric($DisableSafeProcesses)){$DisableSafeProcesses=0;}
	if($DisableSafeProcesses==1){return null;}
	$q=new mysql();
	$unix=new unix();
	$restartStatus=false;
	$arraySTOP=array();
	$kill=$unix->find_program("kill");
	if($q->COUNT_ROWS("freeweb", "artica_backup")==0){
		$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
		$PureFtpdEnabled=$sock->GET_INFO("PureFtpdEnabled");
		if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=1;}
		if(!is_numeric($PureFtpdEnabled)){$PureFtpdEnabled=1;}
		if($EnableFreeWeb==1){
			$sock->SET_INFO("EnableFreeWeb", 0);
			$restartStatus=true;
			$arraySTOP[]="FreeWebs is now disabled (you did not have any websites set)";
			shell_exec("/etc/init.d/artica-postfix stop apachesrc");
			
		}
		
		if($PureFtpdEnabled==1){
			$restartStatus=true;
			$sock->SET_INFO("PureFtpdEnabled",0);
			$arraySTOP[]="PureTFPD is now disabled (you did not have any websites set)";
			shell_exec("/etc/init.d/artica-postfix stop ftp");
		}
		
	}
	
	$vnstatd=$unix->find_program("vnstatd");
	if(is_file($vnstatd)){
		$EnableVnStat=$sock->GET_INFO("EnableVnStat");
		if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}
		if($EnableVnStat==1){
			$arraySTOP[]="vnStat Daemon (Network Card interfaces) is now disabled";
			$sock->SET_INFO("EnableVnStat", 0);
			$restartStatus=true;
			shell_exec("/etc/init.d/artica-postfix stop vnstat");
		}
	}
	
	$FreshClam=$unix->find_program("freshclam");
	if(is_file($FreshClam)){
		$EnableFreshClam=$sock->GET_INFO("EnableFreshClam");
		if(!is_numeric($EnableFreshClam)){$EnableFreshClam=1;}
		if($EnableFreshClam==1){
			$arraySTOP[]="FreshClam Clamav Daemon updater is now disabled";
			$sock->SET_INFO("EnableFreshClam", 0);
			$restartStatus=true;
			shell_exec("/etc/init.d/artica-postfix stop freshclam");
		}
	}		
	
	if($restartStatus){shell_exec("/etc/init.d/artica-status reload");}
	
	$preload=$unix->find_program("preload");
	if(is_file($preload)){
		$pid=$unix->PIDOF($preload);
		if($pid>5){$arraySTOP[]=$preload. ":[$pid]";shell_exec("kill -9 $pid");}
	}
	
	$named=$unix->find_program("named");
	if(is_file($named)){
		$pid=$unix->PIDOF($named);
		if($pid>5){$arraySTOP[]=$named. ":[$pid]";shell_exec("kill -9 $pid");}
	}	
	
	$firefox="/usr/lib/iceweasel/firefox-bin";
	if(is_file($firefox)){
		$pid=$unix->PIDOF($firefox);
		if($pid>5){$arraySTOP[]=$firefox. ":[$pid]";shell_exec("kill -9 $pid");}
	}		

	$xfce4="/usr/bin/xfce4-session";
	if(is_file($xfce4)){
		$pid=$unix->PIDOF($xfce4);
		if($pid>5){$arraySTOP[]=$xfce4. ":[$pid]";shell_exec("kill -9 $pid");}
	}
	
	$xfdesktop="/usr/bin/xfdesktop";	
	if(is_file($xfdesktop)){
		$pid=$unix->PIDOF($xfdesktop);
		if($pid>5){$arraySTOP[]=$xfdesktop. ":[$pid]";shell_exec("kill -9 $pid");}
	}	
	
	$intro="Artica has detected that you have only $users->CPU_NUMBER CPU, so the Safe Process has been automatically enabled.\n";
	if(count($arraySTOP)>0){return "$intro\nSafe Process: the following services/processes has been killed or removed\nin order to safe performances:\n" .@implode("\n", $arraySTOP)."\nIf you want to disable the SafeProcess Features do the following command :\n# echo 1 >/etc/artica-postfix/settings/Daemons/DisableSafeProcesses\n---------------------------------------------\n";}
	
	
	
}





function events($text){
		if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		include_once(dirname(__FILE__)."/framework/class.unix.inc");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/artica-status.debug";
		$f=new debuglogs();
		$f->debuglogs($text);
		}
?>