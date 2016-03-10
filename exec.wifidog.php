<?php
$GLOBALS["SCRIPT_SUFFIX"]="--script=".basename(__FILE__);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["NO_RESTART"]=false;
$GLOBALS["TITLENAME"]="WiFiDog service";
$GLOBALS["RECOVER"]=false;
$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.webauth.restart.progress";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--norestart#",implode(" ",$argv),$re)){$GLOBALS["NO_RESTART"]=true;}
if(preg_match("#--recover#",implode(" ",$argv),$re)){$GLOBALS["RECOVER"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;buildconfig();BuildSSLTables();die();}
if($argv[1]=="--testcnx"){$GLOBALS["OUTPUT"]=true;TESTCONNECTION();die();}
if($argv[1]=="--clean-all-sessions"){$GLOBALS["OUTPUT"]=true;CLEAN_ALL_SESSIONS();die();}
if($argv[1]=="--reconfigure-progress"){$GLOBALS["OUTPUT"]=true;RECONFIGURE_PROGRESS();die();}
if($argv[1]=="--wizard"){$GLOBALS["OUTPUT"]=true;WIZARD_PROGRESS();die();}
if($argv[1]=="--backup"){$GLOBALS["OUTPUT"]=true;BACKUP_PROGRESS();die();}
if($argv[1]=="--restore"){$GLOBALS["OUTPUT"]=true;BACKUP_RESTORE($argv[2]);die();}
if($argv[1]=="--emergency-on"){$GLOBALS["OUTPUT"]=true;EMERGENCY_ON();die();}
if($argv[1]=="--emergency-off"){$GLOBALS["OUTPUT"]=true;EMERGENCY_OFF();die();}



function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0777);

}
function build_progress_reconfigure($text,$pourc){
	$filename="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}
function build_progress_backup($text,$pourc){
	$filename="/usr/share/artica-postfix/ressources/logs/webauth.rules.bakckup.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}
function build_progress_restore($text,$pourc){
	$filename="/usr/share/artica-postfix/ressources/logs/webauth.rules.restore.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}

function BACKUP_RESTORE($filename){
	$unix=new unix();
	build_progress_restore("{restore} $filename",5);
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$filesource="$content_dir$filename";
	$filedest=$unix->FILE_TEMP();
	if(!is_file($filesource)){
		@unlink($filesource);
		echo "$filesource no such file\n";
		build_progress_backup("{restore} {failed}",110);
		return;
		
	}
	
	build_progress_restore("{restore} $filename {uncompress}",50);
	if(!$unix->uncompress($filesource, $filedest)){
		@unlink($filesource);
		@unlink($filedest);
		echo "$filesource unable to uncompress\n";
		build_progress_backup("{restore} {failed}",110);
		return;
	}
	@unlink($filesource);
	
	
	build_progress_restore("{restore} $filename {injecting}",90);
	$q=new mysql_squid_builder();
	$cmdline=$q->MYSQL_CMDLINES;
	$mysql=$unix->find_program("mysql");
	
	$cmdline="$mysql $cmdline --force squidlogs < $filedest";
	echo $cmdline."\n";
	system($cmdline);
	sleep(5);
	@unlink($filedest);
	build_progress_restore("{restore} {done}",100);
	
}

function BACKUP_PROGRESS(){
	
	build_progress_backup("{backup} {rules}",5);
	$tables[]="webauth_rules";
	$tables[]="webauth_settings";
	$tables[]="hotspot_members";
	
	$q=new mysql_squid_builder();
	$cmdline=$q->MYSQL_CMDLINES;
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$bzip2=$unix->find_program("bzip2");
	$bzip2_cmd="| $bzip2 ";
	
	
	$filename="/usr/share/artica-postfix/ressources/logs/web/hotspot.rules.backup.sql";
	$fileCompressName="/usr/share/artica-postfix/ressources/logs/web/hotspot.rules.backup.gz";
	if(is_file($filename)){@unlink($filename);}
	build_progress_backup("{backup} {tables}",50);
	$cmd="$mysqldump $cmdline --skip-add-drop-table --insert-ignore --single-transaction --skip-add-locks --skip-lock-tables squidlogs ".@implode(" ", $tables) ."> $filename 2>&1";
	echo $cmd."\n";
	system("$cmd");
	if(!is_file($filename)){
		build_progress_backup("{backup} {failed}",110);
		return;
	}
	
	$size=@filesize($filename);
	echo "Size: ".$size ." bytes ". FormatBytes($size/1024,true)."\n";
	
	sleep(3);
	
	build_progress_backup("{backup} {compress}",80);
	if(!$unix->compress($filename, $fileCompressName)){
		@unlink($filename);
		build_progress_backup("{backup} {compress} failed}",110);
		return;
		
	}
	
	@unlink($filename);
	
	$size=@filesize($fileCompressName);
	echo "Size: ( after compression)  ".$size ." bytes ". FormatBytes($size/1024,true)."\n";
	sleep(2);
	
	
	build_progress_backup("{backup} {done}",100);
	
	
	
}

function WIZARD_PROGRESS(){
	build_progress("{activate_the_service}",5);
	$unix=new unix();
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaHotSpot", 1);
	
	build_progress("{stopping_firewall}",6);
	$sock->SET_INFO("FireHolEnable", 0);
	shell_exec("/usr/local/sbin/firehol stop");
	
	
	$SquidHotSpotPort=intval($sock->GET_INFO("SquidHotSpotPort"));
	$ArticaHotSpotPort=intval($sock->GET_INFO("ArticaHotSpotPort"));
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	
	$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
	
	$SquidHotSpotSSLPort=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	$WifiDogDebugLevel=intval($sock->GET_INFO("WifiDogDebugLevel"));
	$ArticaHotSpotInterface2=$sock->GET_INFO("ArticaHotSpotInterface2");
	
	
	
	
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	$ArticaHotSpotEnableProxy=$sock->GET_INFO("ArticaHotSpotEnableProxy");
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	if(!is_numeric($ArticaHotSpotEnableProxy)){$ArticaHotSpotEnableProxy=1;}
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	
	
	$EnableKerbAuth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));
	
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($EnableKerbAuth==1){
		build_progress("{disconnect} Active Directory",8);
		shell_exec("$php /usr/share/artica-postfix/exec.kerbauth.php --disconnect");
	}
	
	
	
	if($ArticaHotSpotPort==0){
		$ArticaHotSpotPort=rand(38000, 64000);
		$sock->SET_INFO("ArticaHotSpotPort", $ArticaHotSpotPort);
	}
	
	if($ArticaSSLHotSpotPort==0){
		$ArticaSSLHotSpotPort=rand(38500, 64000);
		$sock->SET_INFO("ArticaSSLHotSpotPort", $ArticaSSLHotSpotPort);
	}
	
	if($SquidHotSpotPort==0){
		$SquidHotSpotPort=rand(40000, 64000);
		$sock->SET_INFO("SquidHotSpotPort", $SquidHotSpotPort);
	}
	
	if($SquidHotSpotSSLPort==0){
		$SquidHotSpotSSLPort=rand(40500, 64000);
		$sock->SET_INFO("SquidHotSpotSSLPort", $SquidHotSpotSSLPort);
	}

	build_progress("{reconfigure_proxy_service}",10);
	shell_exec("$php /usr/share/artica-postfix/exec/squid.php --build --force");
	restart(true,true);
	
	
	
	
}

function restart($nopid=false,$wizard=false) {
	$unix=new unix();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
			build_progress("{failed}",110);
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	if(!$wizard){build_progress("{stopping_service}",5);}else{
		build_progress("{stopping_service}",15);
	}
	stop(true);
	sleep(1);
	build_progress("{starting_service}",50);
	start(true);
	build_progress("{restarting_service} ( Web)",60);
	system("/etc/init.d/artica-hotspot restart");
	build_progress("{restarting_service} ( {done})",100);
	
	
}

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){start(true);return;}
	buildconfig();
	BuildSSLTables();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reloading PID $pid\n";}
	$unix->KILL_PROCESS($pid,1);
	
}


function wifidog_version(){
	if(isset($GLOBALS["wifidog_version"])){return $GLOBALS["wifidog_version"];}
	$unix=new unix();
	$Masterbin=$unix->find_program("wifidog");
	if(preg_match("#([0-9\.]+)#", exec("$Masterbin -v 2>&1"),$re)){$GLOBALS["wifidog_version"]=$re[1];return $re[1];}
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("wifidog");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$php5=$unix->LOCATE_PHP5_BIN();
	build_progress_reconfigure("Building template",50);
	system("$php5 /usr/share/artica-postfix/hotspot.php --templates >/dev/null 2>&1");
	
	
	if($EnableArticaHotSpot==0){
		build_progress_reconfigure("{starting_service} {disabled}",50);
		build_progress("{starting_service} {disabled}",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Service disabled ( see EnableArticaHotSpot )...\n";}
		return;
	}
	
	
	
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	if(!is_file("/usr/local/etc/wifidog-msg.html")){
		build_progress("{configuring}",54);
		shell_exec("$php5 /usr/share/artica-postfix/hostpot.php --templates");
	}

	if(!is_file("/var/log/artica-wifidog.log")){
		@touch("/var/log/artica-wifidog.log");
	}

	@chmod("/var/log/artica-wifidog.log",0777);

	build_progress("{reconfiguring}",55);
	build_progress_reconfigure("{reconfiguring}",42);
	buildconfig();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service version ". wifidog_version()."\n";}
	

	$WifiDogDebugLevel=intval($sock->GET_INFO("WifiDogDebugLevel"));
	$WifiDogDebugLevel_cmd=null;

	if($WifiDogDebugLevel>0){
		$WifiDogDebugLevel_cmd=" -d $WifiDogDebugLevel";
	}
	
	$iptables=$unix->find_program("iptables");
	build_progress_reconfigure("{starting_service}",43);
	build_progress("{starting_service}",85);
	$cmd="$Masterbin -s$WifiDogDebugLevel_cmd -c /etc/wifidog.conf -w /var/run/wifidog.sock >/dev/null 2>&1 &";
	shell_exec($cmd);

	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Success PID $pid\n";}
		BuildSSLTables();
		build_progress_reconfigure("{verify_web_engine}",44);
		build_progress("{verify_web_engine}",90);
		system("/etc/init.d/artica-hotspot start");
		
		build_progress_reconfigure("{starting_service} waiting iptables rules",55);
		
		for($i=0;$i<10;$i++){
			if(iptables_created()){break;}
			build_progress_reconfigure("{starting_service} waiting iptables rules $i/10",55);
			sleep(1);
		}
		
		Specifics_rules();
		Trusted_sites();
		build_progress_reconfigure("{starting_service} {success}",50);
		build_progress("{starting_service} {success}",100);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: $cmd\n";}
		build_progress_reconfigure("{starting_service} {failed}",110);
		build_progress("{starting_service} {failed}",110);
	}


}

function iptables_created(){
	$unix=new unix();
	$iptables=$unix->find_program("iptables-save");
	exec("$iptables 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#WiFiDog_(.*?)_Unknown#", $ligne)){return true;}
		
	}
	
	
}

function BuildSSLTables(){
	$sock=new sockets();
	$unix=new unix();
	$SquidHotSpotSSLPort=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	$ArticaSplashHotSpotPortSSL=intval($sock->GET_INFO("ArticaSplashHotSpotPortSSL"));
	if($ArticaSplashHotSpotPortSSL==0){$ArticaSplashHotSpotPortSSL=16443;}
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	$iptables_restore=$unix->find_program("iptables-restore");
	$iptables_save=$unix->find_program("iptables-save");
	$HotSpotDenySSL=intval($sock->GET_INFO("HotSpotDenySSL"));
	
	if($GLOBALS["RECOVER"]){
		if(is_file("/etc/wifidog.dump")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: restoring iptables\n";}
			shell_exec("$iptables_restore < /etc/wifidog.dump");
			return;
		}
	}
	
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Adding Know Client SSL port $SquidHotSpotSSLPort forward $ArticaHotSpotInterface..\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Adding Unknown Client SSL port $ArticaSplashHotSpotPortSSL forward..\n";}
	
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	if($ArticaHotSpotEnableMIT==1){
		system("$iptables -t nat -I WiFiDog_{$ArticaHotSpotInterface}_Internet -i $ArticaHotSpotInterface -m mark --mark 0x2 -p tcp --dport 443 -j REDIRECT --to-port $SquidHotSpotSSLPort");
		trusted_ssl_sites();
	}
	
	if($HotSpotDenySSL==0){
		system("$iptables  -t nat -I WiFiDog_{$ArticaHotSpotInterface}_Unknown -p tcp -m tcp --dport 443 -j REDIRECT --to-ports $ArticaSplashHotSpotPortSSL");
	}else{
		system("$iptables  -t nat -I WiFiDog_{$ArticaHotSpotInterface}_Unknown -p tcp -m tcp --dport 443 -j REJECT --reject-with icmp-port-unreachable");
	}
	
	
	
	
	
}

function Specifics_rules(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$sock=new sockets();
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_networks WHERE direction=1 ORDER BY zorder");
	$Count=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Checking $Count incoming rule(s)\n";}
	if($Count==0){return;}
	
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	
	$array[0]="Global";
	$array[1]="Known";
	$array[2]="Unknown";
	$iptables=$unix->find_program("iptables");
	
	$WifiGroup="WiFiDog_{$ArticaHotSpotInterface}_{$array[$type]}";
	
	$action["block"]="REJECT";
	$action["drop"]="DROP";
	$action["allow"]="ACCEPT";
	$MARKLOG="-m comment --comment \"WiFiDog_Artica\"";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$hotspoted=$ligne["hotspoted"];
		$proto=$ligne["proto"];
		$port=$ligne["port"];
		if($port==0){$port=null;}
		$pattern=$ligne["pattern"];
		$actionT=$ligne["action"];
		$s=array();
		$s[]=$action;
		$destination=$ligne["destination"];
		if($destination==null){$destination="0.0.0.0/0";}
		if($pattern==null){$pattern="0.0.0.0/0";}
		echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: {$action[$actionT]} From $destination to $pattern\n";
		shell_exec("$iptables -I FORWARD -i $ArticaHotSpotInterface -s $destination -d $pattern $MARKLOG -j {$action[$actionT]}");

	}
	
	

	

}


function Trusted_sites(){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$iptables=$unix->find_program("iptables");
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	$WifiGroup="WiFiDog_{$ArticaHotSpotInterface}_Internet";
	$WifiGroupUnknown="WiFiDog_{$ArticaHotSpotInterface}_Unknown";
	$prefix_iptables2="$iptables -t nat -I $WifiGroup -i $ArticaHotSpotInterface -p tcp ";
	$prefix_iptables3="$iptables -t nat -I $WifiGroupUnknown -i $ArticaHotSpotInterface -p tcp ";
	$suffix_iptables="-j RETURN";
	
	
	$sql="SELECT *  FROM `hotspot_whitelist`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}return;}
	$Total=mysql_num_rows($results);
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $Total whitelisted websites\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Trust {$ligne["ipaddr"]}\n";}
		$f["$prefix_iptables2 --dst {$ligne["ipaddr"]} $suffix_iptables"]=true;
		$f["$prefix_iptables3 --dst {$ligne["ipaddr"]} $suffix_iptables"]=true;
	}
	
	if(count($f)>0){
		while (list ($cmdline, $b) = each ($f) ){
			system($cmdline);
		}
	
	}
	
}


function trusted_ssl_sites(){
	$sock=new sockets();
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	$WifiGroup="WiFiDog_{$ArticaHotSpotInterface}_Internet";
	$WifiGroupUnknown="WiFiDog_{$ArticaHotSpotInterface}_Unknown";
	$HotSpotWhiteWhatsApp=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotWhiteWhatsApp"));
	
	
	
	
	$f=array();
	
	include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
	$q=new mysql_squid_builder();
	$sql="SELECT hotspot_sslwhitelists.objectid,
			webfilters_sqgroups.GroupName,
			webfilters_sqgroups.GroupType,
			hotspot_sslwhitelists.enabled 
			FROM webfilters_sqgroups,hotspot_sslwhitelists
			WHERE webfilters_sqgroups.ID=hotspot_sslwhitelists.objectid 
			AND hotspot_sslwhitelists.enabled=1";

	
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Checking SSL whitelists $Count rule(s)\n";}
	
	
	
	$prefix_iptables="$iptables -t nat -I $WifiGroup -i $ArticaHotSpotInterface -m mark --mark 0x2 -p tcp --dport 443";
	$prefix_iptables_full="$iptables -t nat -I $WifiGroup -i $ArticaHotSpotInterface -p tcp";
	
	
	$suffix_iptables="-j RETURN";
	
	
	if($HotSpotWhiteWhatsApp==1){
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->whatsapp_networks();
		if($GLOBALS["VERBOSE"]){echo "whatsapp_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["$prefix_iptables_full -m iprange --dst-range $b $suffix_iptables"]=true;
				continue;
			}
			$f["$prefix_iptables_full --dst $b $suffix_iptables"]=true;
				
		}
	}
	
	
	
	
	
	
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {	
		$GroupType=$ligne["GroupType"];
		
		if($GroupType=="teamviewer"){
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->teamviewer_networks();
			if($GLOBALS["VERBOSE"]){echo "teamviewer_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
					
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			continue;
		}
		
		if($GroupType=="whatsapp"){
			if($HotSpotWhiteWhatsApp==1){continue;}
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->whatsapp_networks();
			if($GLOBALS["VERBOSE"]){echo "whatsapp_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
					
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			continue;
			
		}		
		
		
		
		if($GroupType=="skype"){
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->skype_networks();
			if($GLOBALS["VERBOSE"]){echo "teamviewer_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
					
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			continue;
		}		
		
		if($GroupType=="google"){
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->google_networks();
			if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
					
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: google_networks::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			continue;
		}
		
		if($GroupType=="youtube"){
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->youtube_networks();
			if($GLOBALS["VERBOSE"]){echo "youtube_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
					
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: youtube_networks::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			continue;
		}		
		
		
		if($GroupType=="google_ssl"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->google_ssl();
			if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
					$f["$prefix_iptables -m iprange --dst-range $b $suffix_iptables"]=true;
					continue;
				}
				$f["$prefix_iptables --dst $b $suffix_iptables"]=true;
		
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: google_ssl::{$ligne["objectid"]} -> ".count($f)." item(s).\n";}
			return $f;
		}		
		
		if($GroupType=="dst"){$f=trusted_ssl_groups($ligne["objectid"],$f,$prefix_iptables,$suffix_iptables);}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Building ". count($f). " Trusted SSL sites\n";}

	
	if(count($f)>0){
		while (list ($cmdline, $b) = each ($f) ){
			system($cmdline);
		}
	
	}	
	
	
}

function trusted_ssl_groups($gpid,$f,$prefix_iptables,$suffix_iptables){
	$IpClass=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	
	
	$f=array();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";return $f;}
	
	if(mysql_num_rows($results)==0){return $f;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		if(preg_match("#[0-9\.]+-[0-9\.]+", $pattern)){
				$f["$prefix_iptables -m iprange --dst-range $pattern $suffix_iptables"]=true;
				continue;
			}
		
		$f["$prefix_iptables --dst $pattern $suffix_iptables"]=true;
		
		}
		
	return $f;

}
	
	
	







function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	$bin=$unix->find_program("wifidog");
	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service ($bin) already stopped...\n";}
		build_progress_reconfigure("{stopping_service}",20);
		build_progress("{stopping_service}",45);
		KillIptablesRules();
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$wdctl=$unix->find_program("wdctl");

	build_progress_reconfigure("{stopping_service} pid $pid",10);
	build_progress("{stopping_service} pid $pid",10);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service Shutdown pid $pid...\n";}
	
	build_progress_reconfigure("{stopping_service} backup rules",11);
	if($GLOBALS["RECOVER"]){
		$iptables_saves=$unix->find_program("iptables-save");
		shell_exec("$iptables_saves > /etc/wifidog.dump");
	}
	
	shell_exec("$wdctl -s /var/run/wifidog.sock stop >/dev/null 2>&1 &");
	
	
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	build_progress_reconfigure("{stopping_service} pid $pid",12);
	build_progress("{stopping_service} pid $pid",15);
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service success...\n";}
		KillIptablesRules();
		return;
	}
	
	build_progress_reconfigure("{stopping_service} pid $pid",13);
	build_progress("{stopping_service} pid $pid",20);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		build_progress_reconfigure("{stopping_service} {failed} $pid",20);
		build_progress("{stopping_service} {failed}",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: service failed...\n";}
		return;
	}

	build_progress_reconfigure("{stopping_service} {success} $pid",15);
	build_progress("{stopping_service} {success}",30);
	KillIptablesRules();
	build_progress_reconfigure("{stopping_service} {success} $pid",20);
	build_progress("{stopping_service} {success}",40);

}

function KillIptablesRules(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#WiFiDog_#";
	
	
	
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		//echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: $ligne\n"; 
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.wifidog.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.wifidog.conf");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Removing $d iptables rule(s) done...\n";
	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reset sessions...\n";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE hotspot_sessions");
	$q->check_hotspot_session();
}

function PID_NUM(){
	
	$unix=new unix();
	return $unix->PIDOF_PATTERN("wifidog.*?wifidog.conf");
}


function msg_html(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	@mkdir("/usr/local/etc",0755);
	shell_exec("$php /usr/share/artica-postfix/hostpot.php --templates");
	return;
	
	$f[]="<html>";
	$f[]="<head>";
	$f[]="<title>\$title</title>";
	$f[]="<meta HTTP-EQUIV='Pragma' CONTENT='no-cache'>";
	$f[]="";
	$f[]="<style>";
	$f[]="body {";
	$f[]="  margin: 10px 60px 0 60px; ";
	$f[]="  font-family : bitstream vera sans, sans-serif;";
	$f[]="  color: #46a43a;";
	$f[]="}";
	$f[]="";
	$f[]="a {";
	$f[]="  color: #46a43a;";
	$f[]="}";
	$f[]="";
	$f[]="a:active {";
	$f[]="  color: #46a43a;";
	$f[]="}";
	$f[]="";
	$f[]="a:link {";
	$f[]="  color: #46a43a;";
	$f[]="}";
	$f[]="";
	$f[]="a:visited {";
	$f[]="  color: #46a43a;";
	$f[]="}";
	$f[]="";
	$f[]="#header {";
	$f[]="  height: 30px;";
	$f[]="  background-color: #B4F663;";
	$f[]="  padding: 20px;";
	$f[]="  font-size: 20pt;";
	$f[]="  text-align: center;";
	$f[]="  border: 2px solid #46a43a;";
	$f[]="  border-bottom: 0;";
	$f[]="}";
	$f[]="";
	$f[]="#header h2 {";
	$f[]="  margin: 0pt;";
	$f[]="}";
	$f[]="";
	$f[]="#menu {";
	$f[]="  width: 200px;";
	$f[]="  float: right;";
	$f[]="  background-color: #B4F663;";
	$f[]="  border: 2px solid #46a43a;";
	$f[]="  font-size: 80%;";
	$f[]="  min-height: 300px;";
	$f[]="}";
	$f[]="";
	$f[]="#menu h2 {";
	$f[]="  margin: 0;";
	$f[]="  background-color: #46a43a;";
	$f[]="  text-align: center;";
	$f[]="  color: #B4F663;";
	$f[]="}";
	$f[]="";
	$f[]="#copyright {";
	$f[]="}";
	$f[]="";
	$f[]="#content {";
	$f[]="  padding: 20px;";
	$f[]="  border: 2px solid #46a43a;";
	$f[]="  min-height: 300px;";
	$f[]="}";
	$f[]="</style>";
	$f[]="";
	$f[]="</head>";
	$f[]="";
	$f[]="<body>";
	$f[]="";
	$f[]="<div id=\"header\">";
	$f[]="    <h2>\$title</h2>";
	$f[]="</div>";
	$f[]="";
	$f[]="<div id=\"menu\">";
	$f[]="";
	$f[]="";
	$f[]="    <h2>Info</h2>";
	$f[]="    <ul>";
	$f[]="    <li>Version: 20130917";
	$f[]="";
	$f[]="    <li>Node ID: \$nodeID";
	$f[]="    </ul>";
	$f[]="    <br>";
	$f[]="";
	$f[]="    <h2>Menu</h2>";
	$f[]="    <ul>";
	$f[]="    <li><a href='/wifidog/status'>HotSpot Status</a>";
	$f[]="    <li><a href='/wifidog/about'>About HotSpot</a>";
	$f[]="    </ul>";
	$f[]="</div>";
	$f[]="";
	$f[]="<div id=\"content\">";
	$f[]="<h2>\$message</h2>";
	$f[]="</div>";
	$f[]="";
	$f[]="<div id=\"copyright\">";
	$f[]="Copyright (C) 2004-". date("Y");
	$f[]="</div>";
	$f[]="";
	$f[]="";
	$f[]="</body>";
	$f[]="</html>";
	$f[]="";
	@file_put_contents("/usr/local/etc/wifidog-msg.html",@implode("\n", $f));
}

function buildconfig(){
	# $Id$";
	
	msg_html();
	
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$q->check_hotspot_tables();
	$php=$unix->LOCATE_PHP5_BIN();
	$SquidHotSpotPort=intval($sock->GET_INFO("SquidHotSpotPort"));
	$ArticaHotSpotPort=intval($sock->GET_INFO("ArticaHotSpotPort"));
	$ArticaSSLHotSpotPort=intval($sock->GET_INFO("ArticaSSLHotSpotPort"));
	$ArticaSplashHotSpotPort=intval($sock->GET_INFO("ArticaSplashHotSpotPort"));
	$SquidHotSpotSSLPort=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	$HospotNoSSL=intval($sock->GET_INFO("HospotNoSSL"));
	$HotSpotDenySSL=intval($sock->GET_INFO("HotSpotDenySSL"));
	$ArticaHotSpotEmergency=intval($sock->GET_INFO("ArticaHotSpotEmergency"));
	
	$ArticaSplashHotSpotPortSSL=intval($sock->GET_INFO("ArticaSplashHotSpotPortSSL"));
	
	$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
	
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	
	$ArticaHotSpotInterface2=$sock->GET_INFO("ArticaHotSpotInterface2");
	
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	$ArticaHotSpotEnableProxy=$sock->GET_INFO("ArticaHotSpotEnableProxy");
	
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	if(!is_numeric($ArticaHotSpotEnableProxy)){$ArticaHotSpotEnableProxy=1;}
	
	if($ArticaHotSpotInterface2==$ArticaHotSpotInterface){$ArticaHotSpotInterface2=null;}
	
	
	if($ArticaSplashHotSpotPort==0){$ArticaSplashHotSpotPort=16080;}
	if($ArticaSplashHotSpotPortSSL==0){$ArticaSplashHotSpotPortSSL=16443;}
	
	if($ArticaHotSpotPort==0){
		$ArticaHotSpotPort=rand(38000, 64000);
		$sock->SET_INFO("ArticaHotSpotPort", $ArticaHotSpotPort);
	}
	
	if($ArticaSSLHotSpotPort==0){
		$ArticaSSLHotSpotPort=rand(38500, 64000);
		$sock->SET_INFO("ArticaSSLHotSpotPort", $ArticaSSLHotSpotPort);
	}
	
	if($SquidHotSpotPort==0){
		$SquidHotSpotPort=rand(40000, 64000);
		$sock->SET_INFO("SquidHotSpotPort", $SquidHotSpotPort);
	}
	
	if($SquidHotSpotSSLPort==0){
		$SquidHotSpotSSLPort=rand(40500, 64000);
		$sock->SET_INFO("SquidHotSpotSSLPort", $SquidHotSpotSSLPort);
	}
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	

	
	$IPADDR=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
	$GatewayAddress=$IPADDR;
	
	$sock->SET_INFO("HotSpotGatewayAddr", $IPADDR);
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: HTTP service on {$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"]} `$IPADDR` port\n";}
	$HospotHTTPServerName=trim($sock->GET_INFO("HospotHTTPServerName"));
	
	
	$IPADDR2=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface2]["IPADDR"];
	
	
	$WifiDogDebugLevel=intval($sock->GET_INFO("WifiDogDebugLevel"));
	
	
	build_progress("{reconfiguring}",60);
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: HTTP service on $ArticaSplashHotSpotPort port\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: HTTPS service on $ArticaSplashHotSpotPortSSL port\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: HotSpot service on $ArticaHotSpotPort port\n";}
	if($ArticaHotSpotInterface2<>null){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Listen IN on $ArticaHotSpotInterface ( $IPADDR )\n";}
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Listen OUT on $ArticaHotSpotInterface2 ( $IPADDR2 )\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Listen on $ArticaHotSpotInterface ( $IPADDR )\n";}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Proxy Listen on $SquidHotSpotPort port\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Re-authenticate each $ArticaSplashHotSpotCacheAuth Minutes\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Debug Level:$WifiDogDebugLevel\n";}
	
	
	$Checking_squid=Checking_squid($SquidHotSpotPort);
	if(!$Checking_squid){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reconfiguring proxy...\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Restarting Proxy...\n";}
		shell_exec("/etc/init.d/squid restart --force {$GLOBALS["SCRIPT_SUFFIX"]}");
	}
	
	build_progress("{reconfiguring}",61);
	$Checking_squid=Checking_squid($SquidHotSpotPort);
	if(!$Checking_squid){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reconfiguring proxy on port $SquidHotSpotPort Failed!!!\n";}
		
	}
	
	build_progress("{reconfiguring}",62);
	if($ArticaHotSpotEnableMIT==1){
		$Checking_squid=Checking_squid($SquidHotSpotSSLPort);
		build_progress("{reconfiguring}",63);
		if(!$Checking_squid){
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reconfiguring proxy...\n";}
			shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Restarting Proxy...\n";}
			shell_exec("/etc/init.d/squid restart --force {$GLOBALS["SCRIPT_SUFFIX"]}");
		}
		
		$Checking_squid=Checking_squid($SquidHotSpotSSLPort);
		if(!$Checking_squid){
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Reconfiguring proxy on port $SquidHotSpotSSLPort Failed!!!\n";}
		}
	}
	
	$modprobe=$unix->find_program("modprobe");
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: probing iptables modules...\n";}
	$array=array();
	$array[]="ip_tables";
	$array[]="ip_conntrack";
	$array[]="ip_conntrack_ftp";
	$array[]="ip_conntrack_irc";
	$array[]="iptable_nat";
	$array[]="ip_nat_ftp";
	
	while (list ($num, $ligne) = each ($array) ){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: probing $ligne\n";}
		shell_exec("$modprobe $ligne");
	}
	
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Enable gateway..\n";}
	shell_exec("$echo 1 > /proc/sys/net/ipv4/ip_forward");
	shell_exec("$echo 1 > /proc/sys/net/ipv4/ip_dynaddr");
	
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec("$echo 1 > /proc/sys/net/ipv4/ip_forward");
	
	$comment=" -m comment --comment \"WiFiDog_NAT\"";
	
	if($ArticaHotSpotInterface2<>null){
		$iptables=$unix->find_program("iptables");
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Chain $ArticaHotSpotInterface and $ArticaHotSpotInterface2\n";}
		$EXTIF=$ArticaHotSpotInterface2;
		$INTIF=$ArticaHotSpotInterface;
		shell_exec("$iptables -A FORWARD -i $EXTIF -o $INTIF -m state --state ESTABLISHED,RELATED $comment -j ACCEPT"); 
		shell_exec("$iptables -A FORWARD -i $INTIF -o $EXTIF $comment -j ACCEPT"); 
		shell_exec("$iptables -t nat -A POSTROUTING -o $EXTIF $comment -j MASQUERADE"); 
	}
	
	$WifidogClientTimeout=intval($sock->GET_INFO("WifidogClientTimeout"));
	if($WifidogClientTimeout<5){$WifidogClientTimeout=30;}

	build_progress("{reconfiguring}",64);
	$f[]="# WiFiDog Configuration file";
	$f[]="# Saved by artica on ".date("Y-m-d H:i:s");
	$f[]="";
	$f[]="# Parameter: GatewayID";
	$f[]="# Default: default";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# Set this to the node ID on the auth server";
	$f[]="# This is used to give a customized login page to the clients and for";
	$f[]="# monitoring/statistics purpose. If you run multiple gateways on the same";
	$f[]="# machine each gateway needs to have a different gateway id.";
	$f[]="# If none is supplied, the mac address of the GatewayInterface interface will be used,";
	$f[]="# without the : separators";
	$f[]="";
	$f[]="# GatewayID default";
	$f[]="";
	$f[]="# Parameter: ExternalInterface";
	$f[]="# Default: NONE";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# Set this to the external interface (the one going out to the Inernet or your larger LAN).  ";
	$f[]="# Typically vlan1 for OpenWrt, and eth0 or ppp0 otherwise,";
	$f[]="# Normally autodetected";
	$f[]="";
	if($ArticaHotSpotInterface2<>null){
		$f[]="ExternalInterface $ArticaHotSpotInterface2";
	}else{
		$f[]="#ExternalInterface eth0 or ppp0 otherwise";
	}
	$f[]="";
	$f[]="GatewayInterface $ArticaHotSpotInterface";
	$f[]="GatewayAddress $GatewayAddress";
	$f[]="";
	$f[]="# Parameter: HtmlMessageFile";
	$f[]="# Default: wifidog-msg.html";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# This allows you to specify a custome HTML file which will be used for";
	$f[]="# system errors by the gateway. Any \$title, \$message and \$node variables";
	$f[]="# used inside the file will be replaced.";
	$f[]="#";
	$f[]="# HtmlMessageFile /opt/wifidog/etc/wifidog-.html";
	$f[]="";
	$f[]="# Parameter: AuthServer";
	$f[]="# Default: NONE";
	$f[]="# Mandatory, repeatable";
	$f[]="#";
	$f[]="# This allows you to configure your auth server(s).  Each one will be tried in order, untill one responds.";
	$f[]="# Set this to the hostname or IP of your auth server(s), the path where";
	$f[]="# WiFiDog-auth resides in and the port it listens on.";
	$f[]="#AuthServer {";
	$f[]="#	Hostname                 (Mandatory; Default: NONE)";
	$f[]="#	SSLAvailable             (Optional; Default: no; Possible values: yes, no)";
	$f[]="#	SSLPort                  (Optional; Default: 443)";
	$f[]="#	HTTPPort                 (Optional; Default: 80)";
	$f[]="#	Path                     (Optional; Default: /wifidog/ Note:  The path must be both prefixed and suffixed by /.  Use a single / for server root.)";
	$f[]="#   LoginScriptPathFragment  (Optional; Default: login/? Note:  This is the script the user will be sent to for login.)";
	$f[]="#   PortalScriptPathFragment (Optional; Default: portal/? Note:  This is the script the user will be sent to after a successfull login.)";
	$f[]="#   MsgScriptPathFragment    (Optional; Default: gw_message.php? Note:  This is the script the user will be sent to upon error to read a readable message.)";
	$f[]="#   PingScriptPathFragment    (Optional; Default: ping/? Note:  This is the script the user will be sent to upon error to read a readable message.)";
	$f[]="#   AuthScriptPathFragment    (Optional; Default: auth/? Note:  This is the script the user will be sent to upon error to read a readable message.)";
	$f[]="#}";
	$f[]="# HospotHTTPServerName = $HospotHTTPServerName";
	$f[]="AuthServer {";
	if($HospotHTTPServerName<>null){
		$f[]="    Hostname $HospotHTTPServerName";
		$unix->create_EtcHosts($HospotHTTPServerName, $IPADDR);
	}else{
		$f[]="    Hostname $IPADDR";
	}
	
	if($HotSpotDenySSL==1){$HospotNoSSL=1;}
	
	
	$f[]="    SSLPort $ArticaSplashHotSpotPortSSL";
	if($HospotNoSSL==0){
		$f[]="    SSLAvailable yes";
	}else{
		$f[]="    SSLAvailable no";
	}
	$f[]="    HTTPPort $ArticaSplashHotSpotPort";
	$f[]="    LoginScriptPathFragment hotspot.php?wifidog-login=yes&";
	$f[]="    PingScriptPathFragment hotspot.php?wifidog-ping=yes&";
	$f[]="    AuthScriptPathFragment hotspot.php?wifidog-auth=yes&";
	$f[]="    PortalScriptPathFragment hotspot.php?wifidog-portal=yes&";
	$f[]="    Path /";
	$f[]="}";
	$f[]="";
	$f[]="Daemon 1";
	$f[]="GatewayPort $ArticaHotSpotPort";
	if($ArticaHotSpotEnableProxy==1){
		$f[]="ProxyPort $SquidHotSpotPort";
	}
	$f[]="HTTPDName Artica HotSpot";
	$f[]="# HTTPDMaxConn 50";
	$f[]="";
	$f[]="# Parameter: HTTPDRealm";
	$f[]="# Default: WiFiDog";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# The name of the HTTP authentication realm. This only used when a user";
	$f[]="# tries to access a protected WiFiDog internal page. See HTTPUserName.";
	$f[]="# HTTPDRealm WiFiDog";
	$f[]="";
	$f[]="# Parameter: HTTPDUserName / HTTPDPassword";
	$f[]="# Default: unset";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# The gateway exposes some information such as the status page through its web";
	$f[]="# interface. This information can be protected with a username and password,";
	$f[]="# which can be set through the HTTPDUserName and HTTPDPassword parameters.";
	$f[]="# HTTPDUserName admin";
	$f[]="# HTTPDPassword secret";
	$f[]="";

	$f[]="CheckInterval 60";
	$f[]="ClientTimeout $WifidogClientTimeout";
	$f[]="";
	$f[]="# Parameter: TrustedMACList";
	$f[]="# Default: none";
	$f[]="# Optional";
	$f[]="#";
	$f[]="# Comma separated list of MAC addresses who are allowed to pass";
	$f[]="# through without authentication";
	
	$f[]="#TrustedMACList 00:15:5D:01:09:06,00:00:C0:1D:F0:0D";
	build_progress("{reconfiguring}",65);
	$trusted_macs=trusted_macs();
	if($trusted_macs<>null){
		$f[]="TrustedMACList $trusted_macs";
	}
	$f[]="";
	$f[]="# Parameter: FirewallRuleSet";
	$f[]="# Default: none";
	$f[]="# Mandatory";
	$f[]="#";
	$f[]="# Groups a number of FirewallRule statements together.";
	$f[]="";
	$f[]="# Parameter: FirewallRule";
	$f[]="# Default: none";
	$f[]="# ";
	$f[]="# Define one firewall rule in a rule set.";
	$f[]="";
	$f[]="# Rule Set: global";
	$f[]="# ";
	$f[]="# Used for rules to be applied to all other rulesets except locked.";
	$f[]="FirewallRuleSet global {";
	
	if($ArticaHotSpotEmergency==1){
		$f[]="\tFirewallRule allow udp to 0.0.0.0/0";
		$f[]="\tFirewallRule allow tcp to 0.0.0.0/0";
		
	}else{
		$f[]=firewall_rules(0);
	}
	$f[]="    # FirewallRule syntax:";
	$f[]="    # FirewallRule (block|drop|allow|log|ulog) [(tcp|udp|icmp) [port X]] [to IP/CIDR]";
	$f[]="";
	$f[]="    ## To block SMTP out, as it's a tech support nightmare, and a legal liability";
	$f[]="    #FirewallRule block tcp port 25";
	$f[]="    ";
	$f[]="    ## Use the following if you don't want clients to be able to access machines on ";
	$f[]="    ## the private LAN that gives internet access to wifidog.  Note that this is not";
	$f[]="    ## client isolation;  The laptops will still be able to talk to one another, as";
	$f[]="    ## well as to any machine bridged to the wifi of the router.";
	$f[]="    # FirewallRule block to 192.168.0.0/16";
	$f[]="    # FirewallRule block to 172.16.0.0/12";
	$f[]="    # FirewallRule block to 10.0.0.0/8";
	$f[]="    ";
	$f[]="    ## This is an example ruleset for the Teliphone service.";
	$f[]="    #FirewallRule allow udp to 69.90.89.192/27";
	$f[]="    #FirewallRule allow udp to 69.90.85.0/27";
	$f[]="    #FirewallRule allow tcp port 80 to 69.90.89.205";
	$f[]="";
	$f[]="    ## Use the following to log or ulog the traffic you want to allow or block.";
	$f[]="    # For OPENWRT: use of these feature requires modules ipt_LOG or ipt_ULOG present in dependencies";
	$f[]="    # iptables-mod-extra and iptables-mod-ulog (to adapt it to the linux distribution). ";
	$f[]="    # Note: the log or ulog rule must be passed before, the rule you want to match.";
	$f[]="    # for openwrt: use of these feature requires modules ipt_LOG or ipt_ULOG present in dependencies";
	$f[]="    # iptables-mod-extra and iptables-mod-ulog";
	$f[]="    # For example, you want to log (ulog works the same way) the traffic allowed on port 80 to the ip 69.90.89.205:";
	$f[]="    #FirewallRule log tcp port 80 to 69.90.89.205";
	$f[]="    #FirewallRule allow tcp port 80 to 69.90.89.205";
	$f[]="    # And you want to know, who matche your block rule:";
	$f[]="    #FirewallRule log to 0.0.0.0/0";
	$f[]="    #FirewallRule block to 0.0.0.0/0";
	$f[]="}";
	$f[]="";
	$f[]="# Rule Set: validating-users";
	$f[]="# Used for new users validating their account";
	$f[]="FirewallRuleSet validating-users {";
	if($ArticaHotSpotEmergency==1){
		$f[]="\tFirewallRule allow udp to 0.0.0.0/0";
		$f[]="\tFirewallRule allow tcp to 0.0.0.0/0";
	
	}else{
		$f[]=firewall_rules(1);
	}
	
	$f[]="FirewallRule allow tcp port 80 to 0.0.0.0/0";
	$f[]="FirewallRule allow tcp port 443 to 0.0.0.0/0";
	$f[]="}";
	$f[]="";
	$f[]="# Rule Set: known-users";
	$f[]="# Used for normal validated users.";
	$f[]="FirewallRuleSet known-users {";
	if($ArticaHotSpotEmergency==1){
		$f[]="\tFirewallRule allow udp to 0.0.0.0/0";
		$f[]="\tFirewallRule allow tcp to 0.0.0.0/0";
	
	}else{
		$f[]=firewall_rules(1);
	}
	$f[]="FirewallRule allow tcp port 80 to 0.0.0.0/0";
	$f[]="FirewallRule allow tcp port 443 to 0.0.0.0/0";
	$f[]="}";
	$f[]="";
	$f[]="# Rule Set: unknown-users";
	$f[]="#";
	$f[]="# Used for unvalidated users, this is the ruleset that gets redirected.";
	$f[]="#";
	$f[]="# XXX The redirect code adds the Default DROP clause.";
	$f[]="FirewallRuleSet unknown-users {";
	
	if($ArticaHotSpotEmergency==1){
		$f[]="\tFirewallRule allow udp to 0.0.0.0/0";
		$f[]="\tFirewallRule allow tcp to 0.0.0.0/0";
	}else{
	
		if($HotSpotDenySSL==1){$f[]="FirewallRule block tcp port 443 to 0.0.0.0/0";}
		if($HotSpotDenySSL==0){$f[]="FirewallRule allow tcp port 443 to 0.0.0.0/0";}
		$f[]="    FirewallRule allow udp port 53";
		$f[]="    FirewallRule allow tcp port 53";
		$f[]="    FirewallRule allow udp port 67";
		$f[]="    FirewallRule allow tcp port 67";	
		$f[]=firewall_rules(2);
	}
	$f[]="}";
	$f[]="";
	$f[]="# Rule Set: locked-users";
	$f[]="#";
	$f[]="# Not currently used";
	$f[]="FirewallRuleSet locked-users {";
	if($ArticaHotSpotEmergency==1){
		$f[]="\tFirewallRule allow udp to 0.0.0.0/0";
		$f[]="\tFirewallRule allow tcp to 0.0.0.0/0";
	}else{
	$f[]="\tFirewallRule block to 0.0.0.0/0";
	}
	$f[]="}";
	$f[]="";
	@file_put_contents("/etc/wifidog.conf", @implode("\n", $f));
	build_progress("{reconfiguring}",90);
	build_error_page();
}
function build_error_page(){
	$sock=new sockets();
	$HotSpotErrorRedirect=$sock->GET_INFO("HotSpotErrorRedirect");
	if($HotSpotErrorRedirect==null){$HotSpotErrorRedirect="http://www.msftncsi.com";}


	$f[]="<html>";
	$f[]="<head>";
	$f[]="<META http-equiv=\"refresh\" content=\"1; URL=$HotSpotErrorRedirect\">";
	$f[]="</head>";
	$f[]="<body style='font-size:40px;text-align:center;margin:80px'>";
	$f[]="Redirecting to $HotSpotErrorRedirect";
	$f[]="</body></html>";

	@file_put_contents(dirname(__FILE__)."/hotspot.html" ,@implode("", $f));
	@chmod(dirname(__FILE__)."/hotspot.html", 0755);



}


function trusted_macs(){
	$Ipclass=new IP();
	$q=new mysql_squid_builder();
	$f=array();
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_whitemacs WHERE enabled=1");
	$Count=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Checking $Count trusted MAC(s)\n";}
	if($Count==0){return null;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$MAC=$ligne["MAC"];
		if(!$Ipclass->IsvalidMAC($MAC)){continue;}
		$f[]=$MAC;
	}
	
	if(count($f)>0){return @implode(",", $f);}
}

function firewall_rules($type=0){
	if(isset($GLOBALS["FWRLS"][$type])){return $GLOBALS["FWRLS"][$type];}
	$HotSpotWhiteWhatsApp=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotWhiteWhatsApp"));
	$Ipclass=new IP();
	$q=new mysql_squid_builder();
	$f=array();
	$array[100]="garbage";
	$array[0]="global";
	$array[1]="known-users";
	$array[2]="unknown-users";
	
	
	if($type==1){
		if($HotSpotWhiteWhatsApp==1){
			$f[]="FirewallRule allow tcp port 5222";
			$f[]="FirewallRule allow tcp port 5223";
			$f[]="FirewallRule allow tcp port 5228";
			
		}
	}
	
	
	
	
	//--------------------------------------------------------------------------
	$sql="SELECT *  FROM `hotspot_whitelist`";
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="\tFirewallRule allow to {$ligne["ipaddr"]}";
	}
	//--------------------------------------------------------------------------
	
	
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_networks WHERE hotspoted=$type AND direction=0 ORDER BY zorder");
	$Count=mysql_num_rows($results);
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Checking \"{$array[$type]}\" $Count rule(s)\n";}
	if($Count==0){
		$f[]="# Type $type No rule set";
		if($type==1){ $f[]="FirewallRule allow to 0.0.0.0/0"; }
		return @implode("\n", $f);
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$hotspoted=$ligne["hotspoted"];
		$proto=$ligne["proto"];
		$port=$ligne["port"];
		if($port==0){$port=null;}
		$pattern=$ligne["pattern"];
		$action=$ligne["action"];
		
		$s=array();
		$s[]=$action;
		
		if($proto<>null){
			$s[]=$proto;
		}
		if($port<>null){
			$s[]="port $port";
		}	
		
		$f[]="# Type $type to $pattern/$proto port:$port action=$action ";
		
		if(!$Ipclass->isIPAddressOrRange($pattern)){
			$f[]="# ! $pattern isIPAddressOrRange -> false -> try to resolve";
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: resolving \"$pattern\"\n";}
			$pattern=gethostbyname($pattern); }
			
		if(!$Ipclass->isIPAddressOrRange($pattern)){
			$f[]="# ! $pattern isIPAddressOrRange -> false";
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: unable to resolve {$ligne["pattern"]}\n";}
			continue;
		}
		$s[]="to $pattern";
		$f[]="\tFirewallRule ".@implode(" ", $s);
		
	}
	if($type==1){
		if(count($f)==0){$f[]="\tFirewallRule allow to 0.0.0.0/0";}
	}
	if($type==0){
		if(count($f)==0){$f[]="\tFirewallRule drop to 0.0.0.0/0";}
	}

	
	
	
	
	$GLOBALS["FWRLS"][$type]=@implode("\n", $f);
	return $GLOBALS["FWRLS"][$type];
}

function Checking_squid($port){
	$f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(preg_match("#^(http_port|https_port).*?$port#", $ligne)){
			return true;}
	
	}
	
	return false;
}

function TESTCONNECTION($force=false){
	$sock=new sockets();
	$unix=new unix();
	if($GLOBALS["FORCE"]){$force=true;}
	
	if(system_is_overloaded()){
		hotspot_admin_mysql(1, "Aborting task: Overloaded system Memory:{$GLOBALS["SYSTEM_INTERNAL_MEMM"]}M, Load {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",null,__FILE__,__LINE__);
		die();
	}
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){echo "$TimeFile\n";}
	
	if(!$force){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	$TimeEx=$unix->file_time_min($TimeFile);
	if(!$force){
		if($TimeEx<5){die();}
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
	}
	
	
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$IPADDR=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
	$ArticaHotSpotPort=intval($sock->GET_INFO("ArticaHotSpotPort"));
	
	$uri="http://$IPADDR:$ArticaHotSpotPort/wifidog/status";
	
	@file_put_contents("/home/artica/hotspot/caches/WifidogUri",$uri);
	@file_put_contents("/home/artica/hotspot/caches/HTTPClientIP",$IPADDR);
	
	$curl=new ccurl($uri,true,$IPADDR,true);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=5;
	$curl->interface=$IPADDR;
	
	if(!$curl->get()){
		if(!$GLOBALS["NO_RESTART"]){
			hotspot_admin_mysql(0, "Checking HotSpot service failed [action=restart]",$curl->errors,__FILE__,__LINE__);
			$GLOBALS["RECOVER"]=true;
			
			stop(true);
			start(true);
		}
	}
	$f=explode("\n",$curl->data);
	while (list ($num, $line) = each ($f) ){
		$line=trim($line);
		if(preg_match("#IP:\s+([0-9\.]+)\s+MAC:\s+(.+)#", $line,$re)){
			$MAC=trim(strtolower($re[2]));
			$IP=trim($re[1]);
			continue;
		}
		
		if(preg_match("#Token:\s+(.+)#", $line,$re)){
			$ARRAY["SESSIONS"][$MAC]=trim($re[1]);
		}

		
		if($line==null){continue;}
		if(preg_match("#Uptime:\s+(.+)#", $line,$re)){
			$ARRAY["UPTIME"]=trim($re[1]);continue;
		}
		if(preg_match("#Internet Connectivity:\s+(.+)#", $line,$re)){
			$ARRAY["INTERNET"]=trim($re[1]);continue;
		}
		if(preg_match("#Clients served this session:\s+([0-9]+)#", $line,$re)){
			$ARRAY["CLIENTS"]=trim($re[1]);continue;
		}	
		
		if(preg_match("#([0-9]+)\s+clients connected#", $line,$re)){
			$ARRAY["CLIENTS"]=trim($re[1]);continue;
		}	

		if($GLOBALS["VERBOSE"]){echo "No Match $line\n";}
	}
	
	$ARRAY["TIME"]=time();
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	@mkdir("/usr/share/artica/postfix/ressources/logs/web",0755,true);
	@unlink("/usr/share/artica/postfix/ressources/logs/web/wifidog.status");
	file_put_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status", serialize($ARRAY));
	chmod("/usr/share/artica/postfix/ressources/logs/web/wifidog.status",0755);
	return $ARRAY;
	
}

function  CLEAN_ALL_SESSIONS(){
	$sock=new sockets();
	$unix=new unix();
	if($GLOBALS["FORCE"]){$force=true;}
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){echo "$TimeFile\n";}
	
	if(!$force){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$IPADDR=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
	$ArticaHotSpotPort=intval($sock->GET_INFO("ArticaHotSpotPort"));
	
	$uri="http://$IPADDR:$ArticaHotSpotPort/wifidog/";
	$ARRAY=TESTCONNECTION(true);
	
	while (list ($MAC, $token) = each ($ARRAY["SESSIONS"]) ){
		
		
	}
	
	
}
function apache_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/artica-apache/hotspot-apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f /etc/artica-postfix/hotspot-httpd.conf");
}

function EMERGENCY_OFF(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaHotSpotEmergency", 0);
	RECONFIGURE_PROGRESS(true);	
	
}

function EMERGENCY_ON(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaHotSpotEmergency", 1);
	RECONFIGURE_PROGRESS(true);
}

function RECONFIGURE_PROGRESS($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
			build_progress_reconfigure("{failed}",110);
			return;
		}
	}
	$sock=new sockets();
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	$ArticaHotSpotEnableProxy=$sock->GET_INFO("ArticaHotSpotEnableProxy");
	$ArticaHotSpotEmergency=intval($sock->GET_INFO("ArticaHotSpotEmergency"));
	
	
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	if(!is_numeric($ArticaHotSpotEnableProxy)){$ArticaHotSpotEnableProxy=1;}
	
	
	$proxyRestart=0;
	if($ArticaHotSpotEnableMIT==1){$proxyRestart=1;}
	if($ArticaHotSpotEnableProxy==1){$proxyRestart=1;}
	
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_reconfigure("{reconfigure_hostpot_service}",5);
	build_progress_reconfigure("{stopping_service} Hostpot",10);
	stop(true);
	build_progress_reconfigure("Building templates",20);
	sleep(2);
	shell_exec("$php /usr/share/artica-postfix/hostpot.php --templates");
	build_progress_reconfigure("{building_parameters}",30);
	buildconfig();
	sleep(2);
	
	build_progress_reconfigure("{starting_service} Hostpot",40);
	start(true);
	


	
	if($ArticaHotSpotEmergency==0){
		if($proxyRestart==1){
			build_progress_reconfigure("{reconfigure_proxy_service}",50);
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		}
		
		build_progress_reconfigure("{reconfiguring} {webserver}",60);
		system("$php /usr/share/artica-postfix/exec.hostpot-web.php --build");
		sleep(2);
		
		build_progress_reconfigure("{restarting} {webserver}",70);
		system("$php /usr/share/artica-postfix/exec.hostpot-web.php --restart --force");
		sleep(2);
	
	
		build_progress_reconfigure("{restarting} {dns_service}",71);
		system("/etc/init.d/dnsmasq restart");
		sleep(2);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_reconfigure("{starting_service} HotSpot {failed}",110);
		return;
	}
	build_progress_reconfigure("{starting_service} HotSpot {success}",95);
	$pid=apache_pid();
	if(!$unix->process_exists($pid)){
		build_progress_reconfigure("{starting_service} {webserver} {failed}",110);
		return;
	}	
	build_progress_reconfigure("{starting_service} {webserver} {success}",98);
	sleep(3);
	build_progress_reconfigure("{reconfigure_hostpot_service} {done}",100);
}



