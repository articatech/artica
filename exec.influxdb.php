#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--clean"){clean();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--dump-db"){$GLOBALS["OUTPUT"]=true;dump_dbs();die();}
if($argv[1]=="--create-db"){$GLOBALS["OUTPUT"]=true;create_db();die();}
if($argv[1]=="--interface"){$GLOBALS["OUTPUT"]=true;InterfaceSize();$GLOBALS["DEBUG_INFLUX"]=true;die();}
if($argv[1]=="--InfluxDbSize"){$GLOBALS["OUTPUT"]=true;InfluxDbSize();$GLOBALS["DEBUG_INFLUX"]=true;die();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install($argv[2]);die();}

if($argv[1]=="--remove-db"){$GLOBALS["OUTPUT"]=true;remove_db();die();}
if($argv[1]=="--disable-db"){$GLOBALS["OUTPUT"]=true;disable_db();die();}
if($argv[1]=="--enable-db"){$GLOBALS["OUTPUT"]=true;enable_db();die();}
if($argv[1]=="--initd"){$GLOBALS["OUTPUT"]=true;initd();die();}



if($argv[1]=="--install-progress"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;install();die();}
if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;restart_progress();die();}
if($argv[1]=="--backup"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;backup_database();die();}
if($argv[1]=="--time"){timeZoneUTC();exit;}
if($argv[1]=="--config"){$GLOBALS["OUTPUT"]=true;Config();exit;}
if($argv[1]=="--restore"){restore_influx();exit;}
if($argv[1]=="--tests"){tests();exit;}
if($argv[1]=="--restore-server"){restore_influx_remote($argv[2]);exit;}
if($argv[1]=="--refresh-progress"){refresh_progress();exit;}
if($argv[1]=="--InfluxDBPassword"){InfluxDBPassword();exit;}
if($argv[1]=="--remote-progress"){remote_progress();exit;}
if($argv[1]=="--disconnect-progress"){disconnect_progress();exit;}



query_influx($argv[1]);


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	start(true);
	
}

function initd(){
	include_once(dirname(__FILE__)."/ressources/class.influxdb-service.inc");
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();

	influx_db_service();
	echo "Influx: [INFO] Writing /etc/init.d/artica-postgres with new config\n";
}


function dump_dbs(){
	$influx=new influx();
	$influx->ROOT_DUMP_ALL_DATABASES();
	
}
function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}

function install($filekey=0){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$filename=null;
	$MD5=null;
	$DebianVersion=DebianVersion();
	if($DebianVersion<7){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb Debian version incompatible!\n";}
		build_progress_idb("Incompatible system!",110);
		die();
	}
	
	if($filekey<>0){
		$sock=new sockets();
		$ArticaTechNetSquidRepo=unserialize(base64_decode($sock->GET_INFO("ArticaTechNetInfluxRepo")));
		$version=$ArticaTechNetSquidRepo[$filekey]["VERSION"];
		$filename=$ArticaTechNetSquidRepo[$filekey]["FILENAME"];
		$MD5=$ArticaTechNetSquidRepo[$filekey]["MD5"];
	}
	
	if($filename==null){
		$filename="influxdb-0.9.0.0.tar.gz";
	}
	
	
	$curl=new ccurl("http://mirror.articatech.com/download/InfluxDatabase/$filename");
	$tmpdir=$unix->TEMP_DIR();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress_idb("{downloading}",1);
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Downloading $filename\n";}
	if(!$curl->GetFile("$tmpdir/$filename")){
		
		build_progress_idb("$curl->error",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $curl->error\n";}
		
		while (list ($key, $value) = each ($curl->errors) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $value\n";}	
		}
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb unable to install....\n";}
		@unlink("$tmpdir/$filename");
		return;
	}
	
	if($MD5<>null){
		$DESTMD5=md5_file("$tmpdir/$filename");
		if($DESTMD5<>$MD5){
			echo "$DESTMD5<>$MD5\n";
			@unlink("$tmpdir/$filename");
			build_progress_idb("{install_failed} {corrupted_package}",110);
			return;
					
		}
		
	}
	
	build_progress_idb("{stopping_service}",95);
	stop(true);
	
	
	build_progress_idb("{extracting}",96);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, extracting....\n";}
	$tar=$unix->find_program("tar");
	shell_exec("$tar xvf $tmpdir/$filename -C /");
	
	if($GLOBALS["MIGRATION"]){
		
		$rm=$unix->find_program("rm");
		build_progress_idb("{delete_database}...",97);
		shell_exec("$rm -rf /home/artica/squid/InfluxDB");
		@mkdir("/home/artica/squid/InfluxDB",0755,true);
		shell_exec("$rm -rf /etc/artica-postfix/DIRSIZE_MB_CACHE/*");
		build_progress_idb("{build_status}...",98);
		InfluxDbSize();
	}
	
	
	if($GLOBALS["PROGRESS"]){
		build_progress_idb("{starting_service}",98);
		start(true);
	}
	
	if($GLOBALS["MIGRATION"]){
		build_progress_idb("{restarting_service}",98);
		stop(true);
		start(true);
	}
	
	build_progress_idb("{refresh_status}",98);
	shell_exec("$php /usr/share/artica-postfix/exec.squid.interface-size.php --force");
	system("/etc/init.d/squid-tail restart");
	
	build_progress_idb("{done}",100);
	
	
}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
			if($progress<95){
				build_progress_idb("{downloading}",$progress);
			}
			$GLOBALS["previousProgress"]=$progress;
			
	}
}

function restart_progress(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_rs("{stopping_service}",10);
	if(!stop(true)){return;}
	build_progress_rs("{reconfigure}",20);
	Config();
	sleep(3);
	build_progress_rs("{starting_service}",30);
	shell_exec("/etc/init.d/artica-postgres start");
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_rs("{failed_to_start_service} ",110);
		return;
	}
	build_progress_rs("{starting_service}",70);
	system("/etc/init.d/hypercache-tail restart");
	build_progress_rs("{starting_service}",80);
	system("/etc/init.d/ufdb-tail restart restart");
	build_progress_rs("{starting_service}",80);
	system("/etc/init.d/squid-tail restart restart");
	build_progress_rs("{starting_service} {success}",100);
}


function refresh_progress(){
	$unix=new unix();
	$sock=new sockets();
	build_progress_rs("{database_size}...",25);
	InfluxDbSize();
	
	build_progress_rs("{backup_size}...",25);
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	echo "Checking $InFluxBackupDatabaseDir\n";
	@mkdir($InFluxBackupDatabaseDir,0755,true);
	$size=$unix->DIRSIZE_BYTES_NOCACHE($InFluxBackupDatabaseDir);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/influxdb_snapshotsize", $size);
	
	
	
	
	$q=new postgres_sql();
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT MAX(zDate) as MAX, MIN(zDate) as MIN from access_log"));
	

	
	
	
	$date_start=strtotime($ligne["min"]);
	$date_end=strtotime($ligne["max"]);
	
	
	build_progress_rs("{last_date}...",50);
	echo "* * * END TO {$date_end} ". date("Y-m-d H:i:s",$date_end)."\n";
	
	
	build_progress_rs("{date_start}...",90);
	echo "* * * START FROM {$date_start} ". date("Y-m-d H:i:s",$date_start)."\n";
	
	sleep(5);
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/DATE_START",$date_start);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/DATE_END",$date_end);
	
	build_progress_rs("{done}...",100);
	
}


function restore_influx_remote($server){
	$unix=new unix();
	$sock=new sockets();
	build_progress_rs("{restore} $server",25);
	
	$rm=$unix->find_program("rm");
	
	
	
	
	$fp = @stream_socket_client("tcp://$server:8089",$errno, $errstr,3, STREAM_CLIENT_CONNECT);
	
	
	if(!$fp){
		echo "Error $errno $errstr\n";
		build_progress_rs("{restore} $server:8089 {failed}",110);
		return false;
	}
	
	if (!is_resource($fp)) {
		echo "Error $errno $errstr\n";
		build_progress_rs("{restore} $server:8089 {failed}",110);
		return false;
	
	}
	@socket_close($fp);
	@fclose($fp);
	build_progress_rs("{backup} $server:8089 {please_wait}",30);
	
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	
	$InFluxBackupDatabaseDir=$InFluxBackupDatabaseDir."/restore";
	if(is_dir($InFluxBackupDatabaseDir)){shell_exec("$rm -rf $InFluxBackupDatabaseDir");}
	@mkdir("$InFluxBackupDatabaseDir",0755,true);
	
	$cmd="/opt/influxdb/influxd backup -host {$server}:8089 $InFluxBackupDatabaseDir/snapshot.db";
	echo $cmd."\n";
	system("$cmd");
	if(!is_file("$InFluxBackupDatabaseDir/snapshot.db")){
		build_progress_rs("{backup} $server:8089 {failed}",110);
		return;
	}
	
	$size=@filesize("$InFluxBackupDatabaseDir/snapshot.db");
	$size=$size/1024;
	$size=$size/1024;
	$size=round($size,2);
	build_progress_rs("{restore} $server:8089 ($size MB) {please_wait}",50);
	$cmd="/opt/influxdb/influxd restore -config /etc/opt/influxdb/influxdb.conf $InFluxBackupDatabaseDir/snapshot.db";
	build_progress_rs("{restore}",50);
	system($cmd);
	build_progress_rs("{restore}",90);
	InfluxDbSize();
	if(is_dir($InFluxBackupDatabaseDir)){shell_exec("$rm -rf $InFluxBackupDatabaseDir");}
	build_progress_rs("{done}",100);
	
}


function restore_influx(){
	
	$PathToRestore=null;
	build_progress_rs("{restore}",25);
	$sock=new sockets();
	$dataX=unserialize($sock->GET_INFO("InfluxDBRestoreArray"));
	while (list ($path, $size) = each ($dataX)){
		$basename=basename($path);
		
		if($basename=="snapshot.db"){$PathToRestore=$path;break;}
		$dirname=dirname($path);
		
	}
	if(!is_file($PathToRestore)){
		build_progress_rs("{failed} snapshot.db not found",110);
		return;
	}
	$cmd="/opt/influxdb/influxd restore -config /etc/opt/influxdb/influxdb.conf $PathToRestore";
	build_progress_rs("{restore}",50);
	system($cmd);
	build_progress_rs("{restore}",90);
	InfluxDbSize();
	build_progress_rs("{done}",100);
}

function timeZoneUTC(){
	
	$UTC_TIME=strtotime(gmdate("Y-m-d H:i:s"));
	$CURRENTIME=time();
	if($CURRENTIME>$UTC_TIME){
		$diff=$CURRENTIME-$UTC_TIME;
		$sep="+";
	}
	if($UTC_TIME>$CURRENTIME){
		$diff=$UTC_TIME-$CURRENTIME;
		$sep="-";
	}
	
	echo "Current: $CURRENTIME UTC:$UTC_TIME Diff=$sep$diff seconds\n";
	
	echo "Strtotime: strtotime(".gmdate("Y-m-d H:i:s")." $sep$diff seconds)\n";
	echo date("Y-m-d H:i:s",strtotime(gmdate("Y-m-d H:i:s")." $sep$diff seconds"))."\n";
	
}


function ifInitScript(){
	$f=explode("\n",@file_get_contents("/etc/init.d/artica-postgres"));
	
	while (list ($filepath, $value) = each ($f) ){
		if(preg_match("#OPEN_FILE_LIMIT#", $value)){return true;}
		
	}
	
	
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/influxdb/influxd";
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	build_progress_rs("{starting_service}",30);
	if(!is_file($Masterbin)){
		Install();
		if(!is_file($Masterbin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb not installed\n";}
			build_progress_rs("{failed_to_start_service} ({not_installed})",110);
			return;
		}
		
	}

	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	
	$pid=PID_NUM();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	if($InfluxUseRemote==1){$EnableInfluxDB=0;}
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){$EnableInflux=0;}
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$EnableInflux=1;$SquidPerformance=0;$EnableIntelCeleron=0;}

	@mkdir("/home/artica/squid/InfluxDB",0755,true);

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($SquidPerformance>2){stop(true);
			build_progress_rs("{starting_service} {failed} ({disabled})",110);
			return;
		}
		if($EnableInfluxDB==0){
			stop(true);
			build_progress_rs("{starting_service} {failed} ({disabled})",110);
			return;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_rs("{already_running}",100);
		return true;
	}

	if($EnableInfluxDB==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} InfluxDB engine is disabled\n";}
		build_progress_rs("{starting_service} {failed} ({disabled})",110);
		return false;
	}	

	if($SquidPerformance>2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Perfomance is set to no statistics\n";}
		build_progress_rs("{starting_service} {failed} ({disabled})",110);
		return false;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$date=$unix->find_program("date");
	
	if(!is_dir("/var/log/influxdb")){@mkdir("/var/log/influxdb",0755,true);}
	$influxdb_version=influxdb_version();

	build_progress_rs("{starting_service} v{$influxdb_version}",35);
	
	Config($influxdb_version);
		
	
	build_progress_rs("{starting_service}",45);
	$unix->KILL_PROCESSES_BY_PORT(8086);
	
	if(!ifInitScript()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} patching init script\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --influx");
	}
	$cmd="/etc/init.d/artica-postgres start";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} v$influxdb_version service\n";}
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		build_progress_rs("{starting_service}",45+$i);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress_rs("{starting_service}",50);
		
		
		for($i=1;$i<5;$i++){
			build_progress_rs("{starting_service} {waiting_listen_port}",50+$i);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting 8086 port... $i/5\n";}
			sleep(1);
			$pid=PID_NUM();
			if(test_listen_port()){break;}
		}
		if(!test_listen_port()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
			build_progress_rs("{failed_to_start_service}",110);
			return false;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success v$influxdb_version PID $pid\n";}
		build_progress_rs("{starting_service} {success}",100);
		return true;
		
		
		
	}else{
		build_progress_rs("{failed_to_start_service}",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
	}


}

function test_listen_port(){
	if(!isset($GLOBALS["InfluxApiIP"])){$GLOBALS["InfluxApiIP"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxApiIP");}
	if($GLOBALS["InfluxApiIP"]==null){$GLOBALS["InfluxApiIP"]="127.0.0.1";}
	$fp = @stream_socket_client("tcp://{$GLOBALS["InfluxApiIP"]}:8086",
			$errno, $errstr,3, STREAM_CLIENT_CONNECT);
	if(!$fp){
		if(is_resource($fp)){@socket_close($fp);}
		return false;
	}
	if(is_resource($fp)){@socket_close($fp);}
	return true;
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		build_progress_rs("{stopping_service}",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	


	build_progress_rs("{stopping_service}",15);
	
	if(!ifInitScript()){shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --influx");}
	$cmd="/etc/init.d/artica-postgres stop";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	$pid=PID_NUM();
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_rs("{stopping_service}",15+$i);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if(test_listen_port()){
		for($i=0;$i<5;$i++){
			build_progress_rs("{stopping_service} {waiting_port_to_be_closed}",25);
			if(!test_listen_port()){break;}
			if(!$unix->process_exists($pid)){break;}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting socket $i/5...\n";}
			sleep(1);
		}
		
	}
	
	

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_rs("{stopping_service}",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
	}

	build_progress_rs("{stopping_service}",30);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		build_progress_rs("{stopping_service}",30+$i);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		build_progress_rs("{stopping_service} {failed}",110);
		squid_admin_mysql(0, "Failed to start Statistics Engine",__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
	return true;

}


function GetInfluxListenIP(){
	$unix=new unix();
	$sock=new sockets();
	$STATS_APPLIANCE=false;
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$STATS_APPLIANCE=true;}
	$InfluxListenInterface=$sock->GET_INFO("InfluxListenInterface");
	
	
	if($STATS_APPLIANCE){
		if($InfluxListenInterface==null){$InfluxListenInterface="ALL";}
	}
	if($InfluxListenInterface==null){$InfluxListenInterface="lo";}
	
	if($InfluxListenInterface=="lo"){
		$InfluxListenIP="127.0.0.1";
		$InfluxApiIP="127.0.0.1";
	}
	if($InfluxListenInterface=="ALL"){
		$InfluxListenIP="0.0.0.0";
		$InfluxApiIP="127.0.0.1";
	}	

	if($InfluxListenIP==null){
		$unix=new unix();
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$InfluxListenIP=$NETWORK_ALL_INTERFACES[$InfluxListenInterface]["IPADDR"];
		$InfluxApiIP=$InfluxListenIP;
		if($InfluxListenIP=="0.0.0.0"){$InfluxApiIP="127.0.0.1";}
		if($InfluxListenIP=="127.0.0.1"){$InfluxApiIP="127.0.0.1";}
	}
	
	if($STATS_APPLIANCE){
		if($InfluxListenIP=="127.0.0.1"){$InfluxListenIP="0.0.0.0";}
	}	
	$sock->SET_INFO("InfluxListenIP", $InfluxListenIP);
	return $InfluxListenIP;
	
}


function Config($influxdb_version=null){
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();	
$sock=new sockets();	
@mkdir("/etc/opt/influxdb",0755,true);
@mkdir("/home/artica/squid/InfluxDB/logs",0755,true);
@mkdir("/home/artica/squid/InfluxDB/raft",0755,true);
@mkdir("/home/artica/squid/InfluxDB/db",0755,true);

$InfluxListenIP=null;
$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
$InFluxBackupDatabaseMaxContainers=intval("InFluxBackupDatabaseMaxContainers");
if($InFluxBackupDatabaseMaxContainers==0){$InFluxBackupDatabaseMaxContainers=5;}
$InFluxBackupDatabaseInterval=intval("InFluxBackupDatabaseInterval");
if($InFluxBackupDatabaseInterval==0){$InFluxBackupDatabaseInterval=10080;}
if($InFluxBackupDatabaseInterval<1440){$InFluxBackupDatabaseInterval=1440;}	
$InfluxListenInterface=$sock->GET_INFO("InfluxListenInterface");

$STATS_APPLIANCE=false;

$Intervals[60]="45 * * * * *";
$Intervals[120]="45 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
$Intervals[240]="45 0,4,6,10,14,18,22 * * *";
$Intervals[1440]="10 1 * * *";
$Intervals[10080]="10 0 * * 6";

$CRON[]="MAILTO=\"\"";
$CRON[]="{$Intervals[$InFluxBackupDatabaseInterval]} root $php ".__FILE__." --backup >/dev/null 2>&1";
$CRON[]="";
file_put_contents("/etc/cron.d/InfluxBackup",@implode("\n", $CRON));
$CRON=array();
chmod("/etc/cron.d/InfluxBackup",0640);
chown("/etc/cron.d/InfluxBackup","root");
system("/etc/init.d/cron reload");
if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$STATS_APPLIANCE=true;}


if($STATS_APPLIANCE){
	if($InfluxListenInterface==null){$InfluxListenInterface="ALL";}
}
if($InfluxListenInterface==null){$InfluxListenInterface="lo";}

$influxdb_version=influxdb_version();
if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $influxdb_version,$re)){
	$MAJOR=$re[1];
	$MINOR=$re[2];
	$REV=$re[3];
}
$AS_092=false;
$AS_093=false;
if($MAJOR==0){
	if($MINOR==9){
		if($REV==1){
			$AS_092=true;
		}
		
		
		if($REV==2){
			$AS_092=true;
		}
		if($REV>=3){
			$AS_092=false;
			$AS_093=true;
		}
	}
}


$InfluxListenIP=GetInfluxListenIP();
$InfluxApiIP=$InfluxListenIP;
if($InfluxListenIP=="0.0.0.0"){$InfluxApiIP="127.0.0.1";}
if($InfluxListenIP=="127.0.0.1"){$InfluxApiIP="127.0.0.1";}
$sock->SET_INFO("InfluxApiIP", $InfluxApiIP);
$InfluxAdminDisabled=intval($sock->GET_INFO("InfluxAdminDisabled"));
$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
$unix=new unix();
@mkdir("/home/artica/squid/InfluxDB",0755,true);
@mkdir("/home/artica/squid/InfluxDB/db",0755,true);
@mkdir("/home/artica/squid/InfluxDB/raft",0755,true);
@mkdir("/home/artica/squid/InfluxDB/meta",0755,true);
@mkdir("/home/artica/squid/InfluxDB/hh",0755,true);
$InfluxListenHosname=$InfluxListenIP;
if($InfluxListenHosname=="0.0.0.0"){$InfluxListenHosname=$unix->hostname_g();}

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building settings\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building v$influxdb_version\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building is 0.9.2x: $AS_092\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building is 0.9.3x: $AS_093\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen interface $InfluxListenInterface/$InfluxListenIP/$InfluxApiIP\n";}




$f[]="# Created on ". date("Y-m-d H:i:s");
$f[]="# Welcome to the InfluxDB configuration file.";
$f[]="# Listen interface $InfluxListenInterface/$InfluxListenIP/$InfluxApiIP.";
$f[]="";
$f[]="# If hostname (on the OS) doesn't return a name that can be resolved by the other";
$f[]="# systems in the cluster, you'll have to set the hostname to an IP or something";
$f[]="# that can be resolved here.";
$f[]="hostname = \"$InfluxListenHosname\"";
$f[]="bind-address = \"$InfluxListenIP\"";
$f[]="";
$f[]="# The default cluster and API port";
$f[]="port = 8086";
$f[]="";
$f[]="# Once every 24 hours InfluxDB will report anonymous data to m.influxdb.com";
$f[]="# The data includes raft id (random 8 bytes), os, arch and version";
$f[]="# We don't track ip addresses of servers reporting. This is only used";
$f[]="# to track the number of instances running and the versions, which";
$f[]="# is very helpful for us.";
$f[]="# Change this option to true to disable reporting.";
$f[]="reporting-disabled = true";
$f[]="";
$f[]="# Controls settings for initial start-up. Once a node is successfully started,";
$f[]="# these settings are ignored.  If a node is started with the -join flag,";
$f[]="[initialization]";
$f[]="join-urls = \"\" # Comma-delimited URLs, in the form http://host:port, for joining another cluster.";
$f[]="";
$f[]="# Control authentication";
$f[]="# If not set authetication is DISABLED. Be sure to explicitly set this flag to";
$f[]="# true if you want authentication.";
$f[]="[authentication]";
$f[]="enabled = false";
$f[]="";
$f[]="";

$f[]="[meta]";
$f[]="  dir = \"/home/artica/squid/InfluxDB/meta\"";
$f[]="  hostname = \"$InfluxListenHosname\"";
$f[]="  bind-address = \":8089\"";
$f[]="  retention-autocreate = true";
$f[]="  election-timeout = \"1s\"";
$f[]="  heartbeat-timeout = \"1s\"";
$f[]="  leader-lease-timeout = \"500ms\"";
$f[]="  commit-timeout = \"50ms\"";
$f[]="";
$f[]="";

$f[]="[hinted-handoff]";
$f[]="  enabled = true";
$f[]="  dir = \"/home/artica/squid/InfluxDB/hh\"";
$f[]="  max-size = 1073741824";
$f[]="  max-age = \"168h0m0s\"";
$f[]="  retry-rate-limit = 0";
$f[]="  retry-interval = \"1s\"";
$f[]="";

$f[]="# Configure the admin server";
$f[]="[admin]";
//$f[]="auth-enabled=true";
if($InfluxAdminDisabled==0){
	$f[]="enabled = true";
	$f[]="port = $InfluxAdminPort";
}else{
	$f[]="enabled = false";
	$f[]="#port = $InfluxAdminPort";	
}
$f[]="";
$f[]="# Configure the HTTP API endpoint. All time-series data and queries uses this endpoint.";
$f[]="[api]";
$f[]="# ssl-port = 8087    # SSL support is enabled if you set a port and cert";
$f[]="# ssl-cert = \"/path/to/cert.pem\"";
$f[]="";
$f[]="# Configure the Graphite plugins.";
$f[]="[[graphite]] # 1 or more of these sections may be present.";
$f[]="enabled = false";
$f[]="# protocol = \"\" # Set to \"tcp\" or \"udp\"";
$f[]="# address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
$f[]="# port = 2003";
$f[]="# name-position = \"last\"";
$f[]="# name-separator = \"-\"";
$f[]="# database = \"\"  # store graphite data in this database";
$f[]="";
$f[]="# Configure the collectd input.";
$f[]="[collectd]";
$f[]="enabled = false";
$f[]="#address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
$f[]="#port = 25827";
$f[]="#database = \"collectd_database\"";
$f[]="#typesdb = \"types.db\"";
$f[]="";
$f[]="# Configure the OpenTSDB input.";
$f[]="[opentsdb]";
$f[]="enabled = false";
$f[]="#address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
$f[]="#port = 4242";
$f[]="#database = \"opentsdb_database\"";
$f[]="";
$f[]="# Configure UDP listener for series data.";
if($AS_093){
$f[]="[[udp]]";
}else{
	$f[]="[udp]";
}
$f[]="enabled = false";
$f[]="#bind-address = \"0.0.0.0\"";
$f[]="#port = 4444";
$f[]="";
$f[]="# Broker configuration. Brokers are nodes which participate in distributed";
$f[]="# consensus.";
$f[]="[broker]";
$f[]="enabled = true";
$f[]="# Where the Raft logs are stored. The user running InfluxDB will need read/write access.";
$f[]="dir  = \"/home/artica/squid/InfluxDB/raft\"";
$f[]="truncation-interval = \"10m\"";
$f[]="max-topic-size = 52428800";
$f[]="max-segment-size = 10485760";
$f[]="";
$f[]="# Raft configuration. Controls the distributed consensus system.";
$f[]="[raft]";
$f[]="apply-interval = \"10ms\"";
$f[]="election-timeout = \"5s\"";
$f[]="heartbeat-interval = \"100ms\"";
$f[]="reconnect-timeout = \"10ms\"";
$f[]="";
$f[]="# Data node configuration. Data nodes are where the time-series data, in the form of";
$f[]="# shards, is stored.";
$f[]="[data]";
$f[]="enabled = true";
$f[]="dir = \"/home/artica/squid/InfluxDB/db\"";

if($AS_093){
	$f[]="# The following WAL settings are for the b1 storage engine used in 0.9.2. They won't";
	$f[]="# apply to any new shards created after upgrading to a version > 0.9.3.";
	$f[]="max-wal-size = 104857600 # Maximum size the WAL can reach before a flush. Defaults to 100MB.";
	$f[]="wal-flush-interval = \"10m\" # Maximum time data can sit in WAL before a flush.";
	$f[]="wal-partition-flush-delay = \"2s\" # The delay time between each WAL partition being flushed.";
	$f[]="";
	if(!is_dir("/home/artica/squid/InfluxDB/wal")){@mkdir("/home/artica/squid/InfluxDB/wal",0755,true);}
	$f[]="# These are the WAL settings for the storage engine >= 0.9.3";
	$f[]="wal-dir = \"/home/artica/squid/InfluxDB/wal\"";
	$f[]="wal-enable-logging = true";
	$f[]="";
	$f[]="# When a series in the WAL in-memory cache reaches this size in bytes it is marked as ready to";
	$f[]="# flush to the index";
	$f[]="wal-ready-series-size = 4096";
	$f[]="";
	$f[]="# Flush and compact a partition once this ratio of series are over the ready size";
	$f[]="# wal-compaction-threshold = 0.6";
	$f[]="";
	$f[]="# Force a flush and compaction if any series in a partition gets above this size in bytes";
	$f[]="# wal-max-series-size = 2097152";
	$f[]="";
	$f[]="# Force a flush of all series and full compaction if there have been no writes in this";
	$f[]="# amount of time. This is useful for ensuring that shards that are cold for writes don't";
	$f[]="# keep a bunch of data cached in memory and in the WAL.";
	$f[]="# wal-flush-cold-interval = \"10m\"";
	$f[]="";
	$f[]="# Force a partition to flush its largest series if it reaches this approximate size in";
	$f[]="# bytes. Remember there are 5 partitions so you'll need at least 5x this amount of memory.";
	$f[]="# The more memory you have, the bigger this can be.";
	$f[]="wal-partition-size-threshold = 10485760";	
	
	
}


$f[]="";
$f[]="# Auto-create a retention policy when a database is created. Defaults to true.";
$f[]="retention-auto-create = true";
$f[]="";
$f[]="# Control whether retention policies are enforced and how long the system waits between";
$f[]="# enforcing those policies.";
$f[]="retention-check-enabled = true";
$f[]="retention-check-period = \"10m\"";
$f[]="";
$f[]="# Configuration for snapshot endpoint.";
$f[]="[snapshot]";
$f[]="enabled = true # Enabled by default if not set.";
$f[]="";
$f[]="[logging]";
$f[]="write-tracing = false # If true, enables detailed logging of the write system.";
$f[]="raft-tracing = false # If true, enables detailed logging of Raft consensus.";
$f[]="http-access = false # If true, logs each HTTP access to the system.";
$f[]="";
$f[]="# InfluxDB can store statistical and diagnostic information about itself. This is useful for";
$f[]="# monitoring purposes. This feature is disabled by default, but if enabled, these data can be";
$f[]="# queried like any other data.";
$f[]="[monitoring]";
$f[]="enabled = false";
$f[]="write-interval = \"1m\"          # Period between writing the data.\n";


system("$php /usr/share/artica-postfix/exec.initslapd.php --influx");

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving settings in 0.9x mode\n";}
build_progress_rs("{starting_service} {configuration_done}",40);
@mkdir("/etc/opt/influxdb",0755,true);
@mkdir("/var/log/influxdb",0755,true);
@file_put_contents("/etc/opt/influxdb/influxdb.conf", @implode("\n", $f));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building /etc/opt/influxdb/influxdb.conf done\n";}
		
}

function backup_database(){
	$unix=new unix();
	$q=new mysql();
	
	$sock=new sockets();
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){
		$InFluxBackupDatabaseDir="/home/artica/influx/backup";
	}
	$InFluxBackupDatabaseMaxContainers=intval("InFluxBackupDatabaseMaxContainers");
	if($InFluxBackupDatabaseMaxContainers==0){$InFluxBackupDatabaseMaxContainers=5;}
	$MaxContainers=5;
	@mkdir($InFluxBackupDatabaseDir,0755,true);
	
	
	$GetInfluxListenIP=GetInfluxListenIP();
	if($GetInfluxListenIP=="0.0.0.0"){$GetInfluxListenIP="127.0.0.1";}
	
	build_progress_idb("{backup} - $GetInfluxListenIP",5);
	
	$tar=$unix->find_program("tar");
	@chdir("/home/artica/squid/InfluxDB");
	system("cd /home/artica/squid/InfluxDB");
	$time=time();
	
	$rm=$unix->find_program("rm");
	build_progress_idb("{backup}",50);
	$cmd="/opt/influxdb/influxd backup -host {$GetInfluxListenIP}:8089 $InFluxBackupDatabaseDir/snapshot.db";
	echo $cmd."\n";
	system("$cmd");
	

	system("$rm -rf $InFluxBackupDatabaseDir/*.gz");
	$size=FormatBytes(@filesize("$InFluxBackupDatabaseDir/snapshot.db")/1024);
	squid_admin_mysql(2, "Snaphost BigData database ($size) done", null,__FILE__,__LINE__);
	
	$size=$unix->DIRSIZE_BYTES_NOCACHE($InFluxBackupDatabaseDir);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/influxdb_snapshotsize", $size);
	build_progress_idb("{done}",100);
	
	
}




function InterfaceSize(){
	
	$influx=new influx();
	$sql="select sum(SIZE) as size from MAIN_SIZE group by time(1m) where time > now() - 1h";
	$main=$influx->QUERY_SQL($sql);
	
	var_dump($main);
	
	
	foreach ($main as $row) {
		$time=$row->time;
		$min=date("i",$time);
		$size=$row->size/1024;
		$size=$size/1024;
		$xdata[]=$min;
		$ydata[]=$size;
	}	
	
}


function create_db(){
	$GLOBALS["DEBUG_INFLUX"]=true;
	$GLOBALS["VERBOSE"]=true;
	$influx=new influx();
	
}


function query_influx($sql){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
	$influx=new influx();
	
	$influx->ROOT_DUMP_ALL_DATABASES();
	
	
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		
		echo "TIME:  ".date("Y-m-d H:i:s",$row->time)."\n";
		echo "SIZE:  $row->size\n";
		var_dump($row, $row->time);
	}
	
	echo "today is ".strtotime(date("Y-m-d H:i:s"))."\n";
	
	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/influxdb.pid");
	$Masterbin="/opt/influxdb/influxd";
	return $unix->PIDOF($Masterbin);
}

function influxdb_version(){
	if(isset($GLOBALS["influxdb_version"])){return $GLOBALS["influxdb_version"];}
	exec("/opt/influxdb/influxd version 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#InfluxDB v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "VERSION: TRY 0.8?\n";}
	exec("/opt/influxdb/influxd -v 2>&1",$results2);
	while (list ($key, $value) = each ($results2) ){
		if(preg_match("#InfluxDB\s+v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION 0.8x: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	
}

function InfluxDbSize(){
	$dir="/home/ArticaStatsDB";
	$unix=new unix();
	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);
	
	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	echo "$dir: $size Partition $TOT\n";
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}
	
	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	
}

function clean(){
	$influx=new influx();
	$sock=new sockets();
	$tables["access_log"]=true;
	$InfluxAdminRetentionTime=intval($sock->GET_INFO("InfluxAdminRetentionTime"));
	if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){$InfluxAdminRetentionTime=7;}
	
	$time=strtotime("-$InfluxAdminRetentionTime days");
	$date=date("Y-m-d",$time);
	squid_admin_mysql(1, "[CLEANING] Remove statistics before $date", null,__FILE__,__LINE__);
	
	while (list ($tablename, $value) = each ($tables) ){
		$sql="DELETE FROM $tablename WHERE time < {$time}s";
		//$influx->QUERY_SQL($sql);
		
	}
	
	
}

function remove_db(){
	$unix=new unix();
	
	$rm=$unix->find_program("rm");
	build_progress_rdb(15,"Remove databases files...");
	shell_exec("$rm -rf /home/artica/squid/InfluxDB");
	@mkdir("/home/artica/squid/InfluxDB",0755,true);
	build_progress_rdb(20,"Stopping service");
	stop(true);
	build_progress_rdb(50,"Starting service");
	start(true);
	
	
	
	shell_exec("$rm -rf /etc/artica-postfix/DIRSIZE_MB_CACHE/*");
	InfluxDbSize();
	system("/etc/init.d/squid-tail restart");
	build_progress_rdb(100,"{done}");
	
	
}

function disable_db(){
	include_once(dirname(__FILE__)."/ressources/class.influxdb-service.inc");
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_rdb(15,"{disable_service}");
	$sock=new sockets();
	$sock->SET_INFO("EnableInfluxDB", 0);
	build_progress_rdb(20,"Stopping service");
	stop(true);
	system("/etc/init.d/squid-tail stop");
	influx_db_service();
	build_progress_rdb(50,"Refresh services");
	if(is_file($squidbin)){system("$php /usr/share/artica-postfix/exec.squid.php --build --force");}
	system("/etc/init.d/artica-status restart --force");
	build_progress_rdb(100,"{done}");
}
function enable_db(){
	include_once(dirname(__FILE__)."/ressources/class.influxdb-service.inc");
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_rdb(15,"{enable_service}");
	$sock=new sockets();
	$sock->SET_INFO("EnableInfluxDB", 1);
	influx_db_service();
	build_progress_rdb(20,"Starting service");
	start(true);
	if(is_file($squidbin)){system("$php /usr/share/artica-postfix/exec.squid.php --build --force");}
	system("/etc/init.d/squid-tail start");
	build_progress_rdb(50,"Refresh services");
	system("/etc/init.d/artica-status restart --force");
	build_progress_rdb(100,"{done}");
}

function build_progress_rdb($pourc,$text){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_rs($text,$pourc){
	$progress_file="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function tests(){
	$influx=new influx();
	
	$data=$influx->QUERY_SQL("SELECT SUM(SIZE) as SIZE FROM MAIN_SIZE WHERE time > 1434116669s GROUP BY time(10m)");
}

function InfluxDBPassword(){
	$sock=new sockets();
	$InfluxDBPassword=$sock->GET_INFO("InfluxDBPassword");
	
	$influx=new influx();
	$data=$influx->QUERY_SQL("SHOW USERS");
	
	foreach ($data as $row) {
		$ARRAY["USER"]=$row->user;
	}
	
	if($InfluxDBPassword<>null){
		if(isset($ARRAY["root"])){
			$influx->QUERY_SQL("SET PASSWORD FOR root = '$InfluxDBPassword'");
	
		}else{
			$influx->QUERY_SQL("CREATE USER root WITH PASSWORD '$InfluxDBPassword' WITH ALL PRIVILEGES");
		}
	
	}else{
		if(isset($ARRAY["root"])){
			$influx->QUERY_SQL("DROP USER root");
		}
		
		
	}

}

function disconnect_progress(){
	$sock=new sockets();
	$POST=unserialize($sock->GET_INFO("InfluxRemoteProgress"));
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$InfluxUseRemote=intval($POST["InfluxUseRemote"]);
	build_progress_rs("{disconnect}",20);
	build_progress_rs("{saving_settings}",60);
	$sock->SET_INFO("InfluxUseRemote", 0);
	$sock->SET_INFO("InfluxSyslogRemote", 0);
	$sock->SET_INFO("InfluxRemoteProgress", null);
	$sock->SET_INFO("EnableInfluxDB", 1);
	
	build_progress_rs("{reconfiguring_proxy_service}",70);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress_rs("{restarting_services} 1/6",74);
	system("/etc/init.d/rsyslog restart");
	build_progress_rs("{restarting_services} 2/6",75);
	system("/etc/init.d/squid-tail restart");
	build_progress_rs("{restarting_services} 3/6",80);
	system("/etc/init.d/ufdb-tail restart");
	build_progress_rs("{restarting_services} 4/6",85);
	system("/etc/init.d/artica-syslog");
	build_progress_rs("{restarting_services} 5/6",90);
	system("/etc/init.d/artica-status");
	build_progress_rs("{restarting_services} 6/6",95);
	system("/etc/init.d/artica-postgres restart");
	build_progress_rs("{done}",100);
	
	
}

function remote_progress(){
	$sock=new sockets();
	$POST=unserialize($sock->GET_INFO("InfluxRemoteProgress"));
	$unix=new unix();
	$InfluxUseRemote=intval($POST["InfluxUseRemote"]);
	$InfluxUseRemoteIpaddr=$POST["InfluxUseRemoteIpaddr"];
	$InfluxUseRemotePort=intval($POST["InfluxUseRemotePort"]);
	if($InfluxUseRemotePort==0){$InfluxUseRemotePort=8086;}
	$InfluxUseRemoteArticaPort=intval($POST["InfluxUseRemoteArticaPort"]);
	if($InfluxUseRemoteArticaPort==0){$InfluxUseRemoteArticaPort=9000;}
	$ArticaInfluxUsername=$POST["ArticaInfluxUsername"];
	$ArticaInfluxPassword=$POST["ArticaInfluxPassword"];
	$php=$unix->LOCATE_PHP5_BIN();
	
	$array["username"]=$ArticaInfluxUsername;
	$array["password"]=$ArticaInfluxPassword;
	$sock->SET_INFO("InfluxUseRemoteIpaddr", $InfluxUseRemoteIpaddr);
	$sock->SET_INFO("InfluxUseRemotePort", $InfluxUseRemotePort);
	
	
	echo "Use: $ArticaInfluxUsername as SuperAdmin/$ArticaInfluxPassword\n";
	build_progress_rs("{checking} {enabled}: $InfluxUseRemote",20);
	sleep(2);
	build_progress_rs("{checking} Syslog: {$POST["InfluxSyslogRemote"]}",22);
	sleep(2);
	
	$auth=urlencode(base64_encode(serialize($array)));
	
	if($InfluxUseRemote==1){
		build_progress_rs("{checking} {$InfluxUseRemoteIpaddr}:{$InfluxUseRemoteArticaPort}",30);
		
		if(!$unix->network_test_port($InfluxUseRemoteIpaddr,$InfluxUseRemoteArticaPort)){
			build_progress_rs("{checking} {$InfluxUseRemoteIpaddr}:{$InfluxUseRemoteArticaPort} {failed}",110);
			return;
			
		}
		


		build_progress_rs("{notify_statistics_server}",50);
		$myhostname=$unix->hostname_g();
		$curl=new ccurl("https://$InfluxUseRemoteIpaddr:$InfluxUseRemoteArticaPort/artica.meta.listener.php?influx-client=yes&hostname=$myhostname&auth=$auth",false);
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			echo $curl->error;
			build_progress_rs("{notify_statistics_server} {failed}",110);
			return;
		}
	
		
		if(strpos($curl->data, "<OK>OK</OK>")==0){
			if(preg_match("#<ERROR>(.+?)<\/#is", $curl->data,$re)){echo $re[1]."\n";}
			build_progress_rs("{protocol_error} {failed2}",110);
			return;
		}
		
		sleep(3);
		build_progress_rs("{checking} {$InfluxUseRemoteIpaddr}:$InfluxUseRemotePort",55);
		if(!$unix->network_test_port($InfluxUseRemoteIpaddr,$InfluxUseRemotePort)){
			build_progress_rs("{checking} {$InfluxUseRemoteIpaddr}:$InfluxUseRemotePort {failed}",110);
			return;
		}
		
		
		
		build_progress_rs("{saving_settings}",60);
		$sock->SET_INFO("InfluxUseRemote", 1);
		$sock->SET_INFO("InfluxSyslogRemote", $POST["InfluxSyslogRemote"]);
		

		
	}else{
		build_progress_rs("{saving_settings}",60);
		$sock->SET_INFO("InfluxUseRemote", 0);
	}
	
	
	build_progress_rs("{reconfiguring_proxy_service}",70);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	
	
	
	build_progress_rs("{restarting_services} 1/6",74);
	system("/etc/init.d/rsyslog restart");
	build_progress_rs("{restarting_services} 2/6",75);
	system("/etc/init.d/squid-tail restart");
	build_progress_rs("{restarting_services} 3/6",80);
	system("/etc/init.d/ufdb-tail restart");
	build_progress_rs("{restarting_services} 4/6",85);
	system("/etc/init.d/artica-syslog");
	build_progress_rs("{restarting_services} 5/6",90);
	system("/etc/init.d/artica-status");
	build_progress_rs("{restarting_services} 6/6",95);
	system("/etc/init.d/artica-postgres restart");
	build_progress_rs("{done}",100);

}
	


