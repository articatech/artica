<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["RSQUID"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.bandwith.inc');
if($argv[1]=="--emergency"){emergency_remove();exit;}
if($argv[1]=="--remove"){remove_rules();exit;}
if($argv[1]=="--frame"){$GLOBALS["RSQUID"]=1;}


Paranoid();

function build_progress_paranoid($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.paranoid.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);


}

function emergency_remove(){
	build_progress_paranoid("{stamp_emergency_to_off}",25);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ParanoidBlockerEmergency", 0);
	Paranoid(true);
}

function Paranoid($nopid=false){
	
	$unix=new unix();
	if(!$nopid){
		$mypid=getmypid();
		if(isset($argv[1])){$argv=$argv[1];}
		$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?$argv");
		if(count($pids)>1){
			while (list ($num, $ligne) = each ($pids) ){
				$cmdline=@file_get_contents("/proc/$num/cmdline");
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Already process PID $num $cmdline exists..\n";
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Running ".@file_get_contents("/proc/$num/cmdline")."\n";
			}
			build_progress_paranoid("{already_process_exists_try_later}",110);
			die();
		}
	}
	
	
	$ParanoidBlockerEmergency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ParanoidBlockerEmergency"));
	$UfdbEnableParanoidMode=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbEnableParanoidMode"));
	
	if($ParanoidBlockerEmergency==1){
		if(isInSquid()){
			build_progress_paranoid("{reconfigure}",70);
			$php=$unix->LOCATE_PHP5_BIN();
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		}
		build_progress_paranoid("{emergency}!!!",110);
		@unlink("/etc/squid3/paranoid.db");
		return;
	}
	
	if($UfdbEnableParanoidMode==0){
		
		@unlink("/etc/squid3/paranoid.db");
		if(isInSquid()){
			build_progress_paranoid("{reconfigure}",70);
			$php=$unix->LOCATE_PHP5_BIN();
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		}
		build_progress_paranoid("{disabled}!!!",110);
		return;	
	}

	$sock=new sockets();
	if($sock->EnableUfdbGuard()==0){
		build_progress_paranoid("{webfiltering} {disabled}!!!",110);
		@unlink("/etc/squid3/paranoid.db");
		return;
	}
	build_progress_paranoid("{webfiltering} {enabled} OK",25);
	$ipClass= new IP();
	$SquidFam=new squid_familysite();
	$q=new mysql_squid_builder();
	$ARRAY=array();
	$results=$q->QUERY_SQL("SELECT pattern, object FROM webfilters_paranoid");

	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["pattern"]=trim(strtolower($ligne["pattern"]));
		if($ligne["pattern"]==null){continue;}
		build_progress_paranoid("{$ligne["pattern"]}",50);
		$ARRAY[$ligne["object"]][$ligne["pattern"]]=true;
			
	}
	$src=array();
	$dstdomain=array();


	if(isset($ARRAY["src"])){
		while (list ($pattern, $xtrace) = each ($ARRAY["src"]) ){
			if(!$ipClass->isValid($pattern)){continue;}
			$MAIN["IPSRC"][$pattern]=true;

		}
			
	}
	if(isset($ARRAY["dstdomain"])){
		while (list ($pattern, $xtrace) = each ($ARRAY["dstdomain"]) ){
			$MAIN["DOMS"][$pattern]=true;
		}
	}
	
	
	
	if(isset($ARRAY["dstdomainsrc"])){
		while (list ($pattern, $xtrace) = each ($ARRAY["dstdomainsrc"]) ){
			$fr=explode("/",$pattern);
			if(!$ipClass->isValid($fr[0])){continue;}
			if($fr[1]==null){continue;}
			$fr[1]=$SquidFam->GetFamilySites($fr[1]);
			$MAIN["IPDOM"][trim($fr[0])][trim(strtolower($fr[1]))]=true;
		}
		
	} 
	
	if(!isInSquid()){
		build_progress_paranoid("{reconfigure}",70);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if(!isInSquid()){
		build_progress_paranoid("{failed}",110);
		return;
	}
	build_progress_paranoid("{enabled} OK",80);
	
	if($GLOBALS["RSQUID"]){
		$squidbin=$unix->LOCATE_SQUID_BIN();
		shell_exec("$squidbin -k reconfigure");
	}
	
	@file_put_contents("/etc/squid3/paranoid.db", serialize($MAIN));
	build_progress_paranoid("{done}",100);
}

function isInSquid(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/external_acls.conf"));
	while (list ($pattern, $line) = each ($f) ){
		if(preg_match("#external_acl_paranoid\.php#", $line)){return true;}
		
	}
}

function remove_rules(){
	
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/paranoid.remove.time";
	if($unix->file_time_min($TimeFile)<60){return;}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$sock=new sockets();
	$UfdbEnableParanoidBlockR=intval($sock->GET_INFO("UfdbEnableParanoidBlockR"));
	if($UfdbEnableParanoidBlockR==0){$UfdbEnableParanoidBlockR=24;}
	
	$q=new mysql_squid_builder();
	
	$ROWS=$q->COUNT_ROWS("webfilters_paranoid");
	
	$sql="DELETE FROM webfilters_paranoid WHERE zDate<DATE_SUB(NOW(),INTERVAL $UfdbEnableParanoidBlockR HOUR)";
	
	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(0, "MySQL error", $q->mysql_error);}
	$ROWS2=$q->COUNT_ROWS("webfilters_paranoid");
	if($ROWS-$ROWS2>0){
		Paranoid(true);
	}
	
	
}