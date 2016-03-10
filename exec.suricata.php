<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["SERVICE_NAME"]="IDS service";
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){events("PID: $pid Already exists....");die();}

	
if($argv[1]=="--build"){suricata_config();die();}
if($argv[1]=="--classifications"){build_classification();die();}	
if($argv[1]=="--cd"){installapt();die();}
if($argv[1]=="--package"){make_package();die();}
if($argv[1]=="--path"){@unlink($GLOBALS["LOGFILE"]);installapt($argv[2]);die();}
if($argv[1]=="--install"){@unlink($GLOBALS["LOGFILE"]);installapt($argv[2]);die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--version"){$GLOBALS["OUTPUT"]=true;echo suricata_version();die();}
if($argv[1]=="--reconfigure-progress"){$GLOBALS["OUTPUT"]=true;echo reconfigure_progress();die();}
if($argv[1]=="--dashboard"){$GLOBALS["OUTPUT"]=true;echo suricata_dashboard();die();}
if($argv[1]=="--parse-rules"){$GLOBALS["OUTPUT"]=true;parse_rulesToPostGres();die();}
if($argv[1]=="--reload-progress"){$GLOBALS["OUTPUT"]=true;reload_progress();die();}
if($argv[1]=="--firewall"){$GLOBALS["OUTPUT"]=true;firewall($argv[2]);die();}



function build_progress($text,$pourc){
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/suricata.install.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_reconfigure($text,$pourc){
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/suricata.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function reload(){
	$unix=new unix();
	suricata_config();
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reloading service\n";}
	$pid=suricata_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress_reconfigure("{reloading} Suricata",20);
		suricata_config();
		build_progress_reconfigure("{reloading} Suricata",80);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Running since {$time}Mn...\n";}
		$unix->KILL_PROCESS($pid,12);
		$nohup=$unix->find_program("nohup");
		build_progress_reconfigure("{reloading} Suricata",90);
		shell_exec("$nohup /etc/init.d/suricata-tail restart >/dev/null 2>&1 &");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not running, start it\n";}
		start(true);
	}
	
	
	
}

function reload_progress(){
	
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	
	if($EnableSuricata==1){
		build_progress_reconfigure("{reloading} Suricata",15);
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --suricata");
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --barnyard2");
		reload();
		build_progress_reconfigure("{reloading} Suricata {done}",100);
		
	}else{
		build_progress_reconfigure("{stopping} Suricata",20);
		stop(true);
		build_progress_reconfigure("{stopping} barnyard",30);
		system("/etc/init.d/barnyard stop");
		build_progress_reconfigure("{stopping} {done}",100);
	}
	
}


function reconfigure_progress(){
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	
	if($EnableSuricata==1){
		build_progress_reconfigure("{restarting} Suricata",15);
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --suricata");
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --barnyard2");
		
		build_progress_reconfigure("{restarting} Suricata",20);
		echo "Restarting service....\n";
		restart(true);
		build_progress_reconfigure("{restarting} barnyard",30);
		system("/etc/init.d/barnyard restart");
		build_progress_reconfigure("{reconfigure} pulledpork",40);
		pulledpork_conf();
		build_progress_reconfigure("{reconfigure} pulledpork",45);
		disablesid();
		build_progress_reconfigure("{reconfigure} Dashboard",50);
		suricata_dashboard();
		pulledpork_run();
		if(!installapt()){
			build_progress_reconfigure("{reconfigure} {failed}",110);
			return;
		}
		
		build_progress_reconfigure("{reconfigure} {done}",100);
		
	}else{
		build_progress_reconfigure("{stopping} Suricata",20);
		stop(true);
		build_progress_reconfigure("{stopping} barnyard",30);
		system("/etc/init.d/barnyard stop");
		
		build_progress_reconfigure("{stopping} tail",40);
		system("/etc/init.d/suricata-tail stop");
		
		build_progress_reconfigure("{stopping} {done}",100);
	}
	
}

function installapt(){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){ @file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 1); }
	$GLOBALS["OUTPUT"]=true;
	@unlink($GLOBALS["LOGFILE"]);
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$git_proxy=null;
	$gem_proxy=null;
	$curl_proxy=null;
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	if($SQUIDEnable==1){
		$SquidMgrListenPort=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
		echo "* * * Force git to use http.proxy http://127.0.0.1:$SquidMgrListenPort * * *\n";
		
		$gem_proxy="--http-proxy 127.0.0.1:$SquidMgrListenPort";
		$curl_proxy="--proxy http://127.0.0.1:$SquidMgrListenPort";
		$git_proxy="--global http.proxy http://127.0.0.1:$SquidMgrListenPort";
	}
	
	
	$prc=20;
	echo "Please wait...\n";
	
//-------------------------------------------------------------------------------------------------
	$prc++;
	if(!is_file("/usr/lib/x86_64-linux-gnu/libyaml.so")){
		build_progress("{install_package} libyaml",$prc);
		build_progress_reconfigure("{install_package} libyaml",46);
		$unix->DEBIAN_INSTALL_PACKAGE("libyaml-dev");
		if(!is_file("/usr/lib/x86_64-linux-gnu/libyaml.so")){
			build_progress("{install_package} libyaml {failed_to_install}",110);
			return;
		}else{
			echo "libyaml..........: OK\n";
		}
	}

//-------------------------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------------------------
	$prc++;
	if(!is_file("/usr/lib/perl5/Crypt/SSLeay.pm")){
		build_progress("{install_package} libcrypt-ssleay-perl",$prc);
		build_progress_reconfigure("{install_package} libcrypt-ssleay-perl",46);
		$unix->DEBIAN_INSTALL_PACKAGE("libcrypt-ssleay-perl");
		if(!is_file("/usr/lib/perl5/Crypt/SSLeay.pm")){
			build_progress("{install_package} libcrypt-ssleay-perl {failed_to_install}",110);
			return;
		}else{
			echo "libcrypt-ssleay-perl OK\n";
		}
	}
	
	//-------------------------------------------------------------------------------------------------
	$prc++;
	if(!is_file("/usr/lib/x86_64-linux-gnu/libpng.so")){
		build_progress("{install_package} libpng12-dev",$prc);
		build_progress_reconfigure("{install_package} libpng12-dev",46);
		$unix->DEBIAN_INSTALL_PACKAGE("libpng12-dev");
		if(!is_file("/usr/lib/x86_64-linux-gnu/libpng.so")){
			build_progress("{install_package} libpng12-dev {failed_to_install}",110);
			return;
		}else{
			echo "libpng12-dev.....: OK\n";
		}
	}
	$prc++;
	if(!is_file("/usr/lib/x86_64-linux-gnu/libgd.so")){
		build_progress("{install_package} libgd2-xpm-dev",$prc);
		build_progress_reconfigure("{install_package} libgd2-xpm-dev",46);
		$unix->DEBIAN_INSTALL_PACKAGE("libgd2-xpm-dev");
		if(!is_file("/usr/lib/x86_64-linux-gnu/libgd.so")){
			build_progress("{install_package} libgd2-xpm-dev {failed_to_install}",110);
			return;
		}else{
			echo "libgd2-xpm-dev.: OK\n";
		}
	}	
	
	$prc++;
	if(!is_file("/usr/lib/x86_64-linux-gnu/libjansson.so.4")){
		build_progress("{install_package} libjansson4",$prc);
		build_progress_reconfigure("{install_package} libjansson4",47);
		$unix->DEBIAN_INSTALL_PACKAGE("libjansson4");
		if(!is_file("/usr/lib/x86_64-linux-gnu/libjansson.so.4")){
			build_progress("{install_package} libjansson {failed_to_install}",110);
			return;
		}else{
			echo "libjansson4......: OK\n";
		}
	}

	$prc++;
	if(!is_file("/usr/lib/x86_64-linux-gnu/libnss3.so")){
		build_progress("{install_package} libnss3",$prc);
		build_progress_reconfigure("{install_package} libnss3",47);
		$unix->DEBIAN_INSTALL_PACKAGE("libnss3");
		if(!is_file("/usr/lib/x86_64-linux-gnu/libnss3.so")){
			build_progress("{install_package} libnss3 {failed_to_install}",110);
			return;
		}else{
			echo "libnss3..........: OK\n";
		}
	}
	$prc++;
	if(!is_file("/usr/share/pyshared/yaml/__init__.py")){
		build_progress("{install_package} python-yaml",$prc);
		build_progress_reconfigure("{install_package} python-yaml",47);
		$unix->DEBIAN_INSTALL_PACKAGE("python-yaml");
		if(!is_file("/usr/share/pyshared/yaml/__init__.py")){
			build_progress("{install_package} python-yaml {failed_to_install}",110);
			return;
		}else{
			echo "python-yaml......: OK\n";
		}
	}
	$prc++;
	if(!is_file("/usr/share/pyshared/MySQLdb/__init__.py")){
		build_progress("{install_package} python-mysqldb",$prc);
		build_progress_reconfigure("{install_package} python-mysqldb",47);
		$unix->DEBIAN_INSTALL_PACKAGE("python-mysqldb");
		if(!is_file("/usr/share/pyshared/MySQLdb/__init__.py")){
			build_progress("{install_package} python-mysqldb {failed_to_install}",110);
			return;
		}else{
			echo "python-mysqldb...: OK\n";
		}
	}	
	$prc++;
	if(!is_file("/usr/share/pyshared/psycopg2/__init__.py")){
		build_progress("{install_package} python-psycopg2",$prc);
		build_progress_reconfigure("{install_package} python-psycopg2",47);
		$unix->DEBIAN_INSTALL_PACKAGE("python-psycopg2");
		if(!is_file("/usr/share/pyshared/psycopg2/__init__.py")){
			build_progress("{install_package} python-psycopg2 {failed_to_install}",110);
			return;
		}else{
			echo "python-psycopg2..: OK\n";
		}
	}	
	
	
	
	
	
$prc++;
	if(!is_file("/usr/sbin/oinkmaster")){
		build_progress_reconfigure("{install_package} oinkmaster",48);
		build_progress("{install_package} oinkmaster",$prc);	
		$unix->DEBIAN_INSTALL_PACKAGE("oinkmaster");
		if(!is_file("/usr/sbin/oinkmaster")){
			build_progress("{install_package} oinkmaster {failed_to_install}",110);
			return;
		}else{
			echo "oinkmaster.......: OK\n";
		}
	}
//-------------------------------------------------------------------------------------------------
return true;
	
}
function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping service\n";}
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Building configuration\n";}
	suricata_config();
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service\n";}
	start(true);
}

function start($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}

	$pid=suricata_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return;
	}

	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	if($EnableSuricata==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableSuricata )...\n";}
		return;
	}


	$masterbin=$unix->find_program("suricata");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return;
	}

	$ldconfig=$unix->find_program("ldconfig");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} running ldconfig..\n";}
	system($ldconfig);
	
	if(!is_file("/etc/suricata/suricata.yaml")){suricata_config();}
	@mkdir("/var/run/suricata",0755,true);
	@mkdir("/var/log/barnyard2",0755,true);
	@mkdir("/var/log/suricata",0755,true);
	@chmod("/usr/share/artica-postfix/bin/sidrule",0755);

	
	if(is_file("/var/log/suricata.log")){@unlink("/var/log/suricata.log");}
	
	$SuricataInterface=$sock->GET_INFO("SuricataInterface");
	if($SuricataInterface==null){$SuricataInterface="eth0";}
	
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/suricata/$fileZ";;
	
			if(preg_match("#unified2\.alert\.#", $fileZ)){
				if($unix->file_time_min($path)>10){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} remove $path\n";}
					@unlink($path);}
					continue;
			}
	
		}
	}
	$ethtool=$unix->find_program("ethtool");
	if(is_file($ethtool)){
		shell_exec("$ethtool -K $SuricataInterface gro off >/dev/null 2>&1");
		shell_exec("$ethtool -K $SuricataInterface lro off >/dev/null 2>&1");
	}
	
	
	$suricata_version=suricata_version();
	@mkdir("/var/run/suricata",0755,true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$suricata_version\n";}
	$cmd="$masterbin -c /etc/suricata/suricata.yaml --pidfile /var/run/suricata/suricata.pid --pfring -D";
	@unlink("/var/run/suricata/suricata.pid");
	
	if(!installapt()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed to check required packages\n";}
	}
	
	shell_exec($cmd);

	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=suricata_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}

	$pid=suricata_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
	}else{
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup /etc/init.d/suricata-tail restart >/dev/null 2>&1 &");
		if(is_file("/bin/suricata-fw.sh")){shell_exec("/bin/suricata-fw.sh");}
	}

}
function stop(){
	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("suricata");

	$pid=suricata_pid();
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return;

	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=suricata_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill($pid);
		sleep(1);
	}
	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	$pid=suricata_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}

	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=suricata_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
		@unlink("/var/run/suricata/suricata.pid");
		shell_exec("$php5 /usr/share/artica-postfix/exec.suricata-fw.php --delete >/dev/null 2>&1 &");
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function suricata_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("suricata");
	$pid=$unix->get_pid_from_file('/var/run/suricata/suricata.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}
function suricata_version(){
	$unix=new unix();
	if(isset($GLOBALS["suricata_version"])){return $GLOBALS["suricata_version"];}
	$squidbin=$unix->find_program("suricata");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Suricata\s+version\s+([0-9\.]+)#i", $val,$re)){
			$GLOBALS["suricata_version"]=trim($re[1]);
			return $GLOBALS["suricata_version"];
		}
	}
}


function disablesid(){
	
	
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("suricata_disablesid", "artica_backup")){
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`suricata_disablesid` (
			`ID` BIGINT UNSIGNED NOT NULL PRIMARY KEY ,
			`explain` VARCHAR(255) ,
				INDEX ( `explain`)
			)";		
		
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){echo $q->mysql_error."\n";}
		
	}
	
	if($q->COUNT_ROWS("suricata_disablesid", "artica_backup")==0){
		$sql="INSERT IGNORE INTO suricata_disablesid (ID,`explain`) VALUES 	
	('2200029','ICMPv6 unknown type'),('2200038','SURICATA UDP packet too small'),('2200070','SURICATA FRAG IPv4 Fragmentation overlap'),('2200072','SURICATA FRAG IPv6 Fragmentation overlap'),('2200073','SURICATA IPv4 invalid checksum'),
				('2200075','SURICATA UDPv4 invalid checksum'),
				('2200078','SURICATA UDPv6 invalid checksum'),
				('2200076','SURICATA ICMPv4 invalid checksum'),
				('2200079','SURICATA ICMPv6 invalid checksum'),('2200080','SURICATA IPv6 useless Fragment extension header'),('2240001','SURICATA DNS Unsollicited response'),('2240002','SURICATA DNS malformed request data'),('2240003','SURICATA DNS malformed response data'),('2221000','SURICATA HTTP unknown error'),('2221021','SURICATA HTTP response header invalid'),('2230002','SURICATA TLS invalid record type'),('2230003','SURICATA TLS invalid handshake message'),('2012811','ET DNS DNS Query to a .tk domain - Likely Hostile'),('2018438','ET DNS DNS Query for vpnoverdns - indicates DNS tunnelling'),('2014703','ET DNS Non-DNS or Non-Compliant DNS traffic on DNS port Reserved Bit Set - Likely Kazy'),('2014701','ET DNS Non-DNS or Non-Compliant DNS traffic on DNS port Opcode 6 or 7 set - Likely Kazy'),('2003068','ET SCAN Potential SSH Scan OUTBOUND'),('2013479','ET SCAN Behavioral Unusually fast Terminal Server Traffic, Potential Scan or Infection (Outbound)'),('2012086','ET SHELLCODE Possible Call with No Offset TCP Shellcode'),('2012088','ET SHELLCODE Possible Call with No Offset TCP Shellcode'),('2012252','ET SHELLCODE Common 0a0a0a0a Heap Spray String'),('2013319','ET SHELLCODE Unicode UTF-8 Heap Spray Attempt'),('2013222','ET SHELLCODE Excessive Use of HeapLib Objects Likely Malicious Heap Spray Attempt'),('2011507','ET WEB_CLIENT PDF With Embedded File'),('2010514','ET WEB_CLIENT Possible HTTP 401 XSS Attempt (External Source)'),('2010516','ET WEB_CLIENT Possible HTTP 403 XSS Attempt (External Source)'),('2010518','ET WEB_CLIENT Possible HTTP 404 XSS Attempt (External Source)'),('2010520','ET WEB_CLIENT Possible HTTP 405 XSS Attempt (External Source)'),('2010522','ET WEB_CLIENT Possible HTTP 406 XSS Attempt (External Source)'),('2010525','ET WEB_CLIENT Possible HTTP 500 XSS Attempt (External Source)'),('2010527','ET WEB_CLIENT Possible HTTP 503 XSS Attempt (External Source)'),('2012266','ET WEB_CLIENT Hex Obfuscation of unescape % Encoding'),('2012272','ET WEB_CLIENT Hex Obfuscation of eval % Encoding'),('2012398','ET WEB_CLIENT Hex Obfuscation of replace Javascript Function % Encoding'),('2101201','GPL WEB_SERVER 403 Forbidden'),('2101852','GPL WEB_SERVER robots.txt access'),('2016672','ET WEB_SERVER SQL Errors in HTTP 200 Response (error in your SQL syntax)')";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	$f=array();
	$results=$q->QUERY_SQL("SELECT * FROM suricata_disablesid ORDER BY ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="1:{$ligne["ID"]}";
		
	}
	
	echo "/etc/pulledpork/disablesid.conf done with ". count($f)." rules...\n";
	@file_put_contents("/etc/pulledpork/disablesid.conf", @implode("\n", $f));
}


function make_package(){
	
	
/* HOWTO
 * NDPI --------------------------------------------------------
 * apt-get install dh-autoreconf libpcap-dev libdaq-dev libyaml-dev libpng3 libnss3-dev libnet1-dev libjansson-dev
 * git clone https://github.com/betolj/ndpi-netfilter
 * cd ndpi-netfilter/
 * tar -xf nDPI.tar.gz
 * NDPI_PATH=/root/ndpi-netfilter/nDPI make
 * make modules_install
 * cp ipt/libxt_ndpi.so /lib/xtables/
 * --------------------------------------------------------
 * SURICATA 
 * git clone https://github.com/ironbee/libhtp
 * cd libhtp
 * ./configure --prefix=/usr
 * git clone git://phalanx.openinfosecfoundation.org/oisf.git
 * cd oisf/
 * ./autogen.sh
 * tar -xf suricata-2.0.9.tar.gz 
CFLAGS="-O0 -ggdb"  ./configure --enable-pfring --enable-geoip --with-libpfring-includes=/usr/local/pfring/include/ --with-libpfring-libraries=/usr/local/pfring/lib/ --with-libpcap-includes=/usr/local/pfring/include/ --with-libpcap-libraries=/usr/local/pfring/lib/ --with-libnss-libraries=/usr/lib --with-libnss-includes=/usr/include/nss/ --with-libjansson --with-libnspr-libraries=/usr/lib --with-libnspr-includes=/usr/include/nspr --prefix=/usr --sysconfdir=/etc --localstatedir=/var --enable-profiling --disable-gccmarch-native
 * make && make install && make install-full
 * --------------------------------------------------------
 * barnyard2
 * wget "http://prdownloads.sourceforge.net/libdnet/libdnet-1.11.tar.gz?download" -O libdnet-1.11.tar.gz
 * tar -xf libdnet-1.11.tar.gz
 * cd libdnet-1.11/
 * ./configure --prefix=/usr
 * make && make install
 * --------------------------------------------------------
 * git clone https://github.com/firnsy/barnyard2.git
 * ./autogen.sh 
 * ./configure --with-mysql-libraries=/usr/lib/x86_64-linux-gnu/
 * --------------------------------------------------------
wget "http://sourceforge.net/projects/bandwidthd/files/bandwidthd/bandwidthd%202.0.1/bandwidthd-2.0.1.tgz/download" -O bandwidthd-2.0.1.tgz
tar -xf bandwidthd-2.0.1.tgz

wget http://people.redhat.com/sgrubb/libcap-ng/libcap-ng-0.7.7.tar.gz
tar -xf libcap-ng-0.7.7.tar.gz 
cd libcap-ng-0.7.7/
./autogen.sh 
./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --disable-maintainer-mode --without-python3
make  && make install

wget https://ftp.mozilla.org/pub/nspr/releases/v4.9.6/src/nspr-4.9.6.tar.gz
tar -xf nspr-4.9.6.tar.gz 
cd nspr-4.9.6/
./configure --enable-64bit --prefix=/usr
make  && make install

 * 
 */
	//ldconfig
//modprobe pf_ring transparent_mode=0 min_num_slots=65534
	$f["/usr/lib/libhtp-0.5.18.so.1"]=true;
	$f["/usr/lib/libhtp-0.5.18.so.1.0.0"]=true;
	$f["/usr/lib/libhtp-0.2.so.1"]=true;
	$f["/usr/lib/libhtp-0.2.so.1.0.2"]=true;
	$f["/usr/lib/libhtp-0.5.18.so.1"]=true;
	$f["/usr/lib/libhtp-0.5.18.so.1.0.0"]=true; 
	$f["/usr/lib/libhtp.a"]=true;
	$f["/usr/lib/libhtp.la"]=true;
	$f["/usr/lib/libhtp.so"]=true;
	$f["/usr/bin/suricatasc"]=true;
	$f["/usr/bin/suricata"]=true;
	$f["/usr/lib/libdnet.1"]=true;  
	$f["/usr/lib/libdnet.1.0.1"]=true;  
	$f["/usr/lib/libdnet.a"]=true;  
	$f["/usr/lib/libdnet.la"]=true;
	$f["/usr/local/bin/barnyard2"]=true;
	$f["/usr/local/etc/barnyard2.conf"]=true;
	$f["/usr/sbin/pulledpork.pl"]=true;
	$f["/usr/sbin/snortsam"]=true;
	$f["/usr/local/lib/libpfring.a"]=true;
	$f["/usr/local/lib/libpfring.so"]=true;
	
	$f["/usr/bin/captest"]=true;
	$f["/usr/bin/filecap"]=true;
	$f["/usr/bin/netcap"]=true;
	$f["/usr/bin/pscap"]=true;
	$f["/usr/lib/libcap-ng.a"]=true;
	$f["/usr/lib/libcap-ng.la"]=true;
	$f["/usr/lib/libcap-ng.so"]=true;
	$f["/usr/lib/libcap-ng.so.0"]=true;
	$f["/usr/lib/libcap-ng.so.0.0.0"]=true;
	$f["/usr/lib/libplc4.a"]=true;  
	$f["/usr/lib/libplc4.so"]=true;


	$f["/lib/xtables/libxt_ndpi.so"]=true;
	$f["/lib/modules/3.2.0-4-amd64/extra/xt_ndpi.ko"]=true;
	$f["/lib/modules/3.2.0-4-amd64/kernel/net/pf_ring/pf_ring.ko"]=true;
	
	$version=suricata_version();
	$BASE="/root/suricata-$version-compiler";
	if(is_dir($BASE)){system("/bin/rm -rf $BASE");}
	mkdir("$BASE/etc/suricata",0755,true);
	mkdir("$BASE/usr/lib",0755,true);
	mkdir("$BASE/usr/sbin",0755,true);
	mkdir("$BASE/usr/bin",0755,true);
	mkdir("$BASE/usr/local/bin",0755,true);
	mkdir("$BASE/usr/local/lib",0755,true);
	mkdir("$BASE/usr/local/etc",0755,true);
	mkdir("$BASE/usr/bandwidthd",0755,true);
	mkdir("$BASE/lib/xtables",0755,true);
	mkdir("$BASE/etc/pulledpork",0755,true);
	mkdir("$BASE/lib/modules/3.2.0-4-amd64/extra",0755,true);
	mkdir("$BASE/lib/modules/3.2.0-4-amd64/kernel/net/pf_ring",0755,true);
	
	while (list ($num, $val) = each ($f)){
		echo "Copy $num {$BASE}".dirname($num)."/\n";
		shell_exec("/bin/cp -fd $num {$BASE}".dirname($num)."/");
	}
	echo "Copy Directory /etc/suricata\n";
	shell_exec("/bin/cp -rfd /etc/suricata/* {$BASE}/etc/suricata/");
	
	echo "Copy Directory /etc/pulledpork\n";
	shell_exec("/bin/cp -rfd /etc/pulledpork/* {$BASE}/etc/pulledpork/");
	
	echo "Copy Directory /usr/bandwidthd\n";
	shell_exec("/bin/cp -rfd /usr/bandwidthd/* {$BASE}/usr/bandwidthd/");
	system("cd $BASE");
	@chdir($BASE);
	$Architecture=Architecture();
	$DebianVersion=DebianVersion();
	
	$finalFileName="/root/suricata-debian{$DebianVersion}-$Architecture-$version.tar.gz";
	
	if(is_file($finalFileName)){@unlink($finalFileName);}
	echo "Compressing  $finalFileName\n";
	system("tar -czf $finalFileName *"); 
}
function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}
function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}






function suricata_config(){
	
	if(is_dir("/etc/suricata/suricata")){
		$unix=new unix();
		$cp=$unix->find_program("cp");
		$rm=$unix->find_program("rm");
		shell_exec("$cp -rf /etc/suricata/suricata/* /etc/suricata/");
		shell_exec("$rm -rf /etc/suricata/suricata");
		if($GLOBALS["OUTPUT"]){echo "Config........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Fixing suricata config done...\n";}
		
	}
	$sock=new sockets();
	$SuricataInterface=$sock->GET_INFO("SuricataInterface");
	if($SuricataInterface==null){$SuricataInterface="eth0";}
	$SurcataLogDNS=intval($sock->GET_INFO("SurcataLogDNS"));
	$SurcataLogSSH=intval($sock->GET_INFO("SurcataLogSSH"));
	$SurcataLogHTTP=intval($sock->GET_INFO("SurcataLogHTTP"));
	$SurcataLogTLS=intval($sock->GET_INFO("SurcataLogTLS"));
	$SurcataLogFiles=intval($sock->GET_INFO("SurcataLogFiles"));
	
	$net=new networkscanner();
	if(!is_array($net->networklist)){
	
		$net->networklist[]="192.168.0.0/16";
		$net->networklist[]="10.0.0.0/8";
		$net->networklist[]="172.16.0.0/12";
	}
	
	while (list ($num, $maks) = each ($net->networklist)){
		if($GLOBALS["OUTPUT"]){echo "Config........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}  Network: $maks\n";}
		$HOME_NET[]=$maks;
	}
	
	
	$f[]="%YAML 1.1";
	$f[]="---";
	$f[]="";
	$f[]="max-pending-packets: 2048";
	$f[]="runmode: workers";
	$f[]="autofp-scheduler: active-packets";
	$f[]="host-mode: sniffer-only";
	$f[]="pid-file: /var/run/suricata.pid";
	$f[]="#daemon-directory: \"/\"";
	$f[]="#default-packet-size: 1514";
	$f[]="default-log-dir: /var/log/suricata/";
	$f[]="unix-command:";
	$f[]="  enabled: no";
	$f[]="  #filename: custom.socket";
	$f[]="";
	$f[]="outputs:";
	$f[]="";
	$f[]="  - fast:";
	$f[]="      enabled: no";
	$f[]="      filename: fast.log";
	$f[]="      append: yes";
	$f[]="      filetype: regular";
	$f[]="";
	$f[]="  - eve-log:";
	$f[]="      enabled: yes";
	$f[]="      type: file";
	$f[]="      filename: eve.json";

	$f[]="      types:";
	$f[]="        - alert";
	
	if($SurcataLogHTTP==1){
		$f[]="        - http:";
		$f[]="            extended: yes";
	}

	if($SurcataLogDNS==1){
		$f[]="        - dns";
	}
	
	if($SurcataLogTLS==1){
	$f[]="        - tls:";
	$f[]="            extended: yes     # enable this for extended logging information";
	}
	
	if($SurcataLogFiles==1){
	$f[]="        - files:";
	$f[]="            force-magic: no   # force logging magic on all logged files";
	$f[]="            force-md5: no     # force logging of md5 checksums";
	}
	$f[]="        #- drop";
	
	if($SurcataLogSSH==1){
		$f[]="        - ssh";
	}
	$f[]="";
	$f[]="  # alert output for use with Barnyard2";
	$f[]="  - unified2-alert:";
	$f[]="      enabled: no";
	$f[]="      filename: unified2.alert";
	$f[]="      sensor-id: 0";
	$f[]="";
	$f[]="      xff:";
	$f[]="        enabled: no";
	$f[]="        mode: extra-data";
	$f[]="        header: X-Forwarded-For ";
	$f[]="";
	$f[]="  - http-log:";
	$f[]="      enabled: no";
	$f[]="      filename: http.log";
	$f[]="      append: yes";
	$f[]="      #extended: yes     # enable this for extended logging information";
	$f[]="      #custom: yes       # enabled the custom logging format (defined by customformat)";
	$f[]="      #customformat: \"%{%D-%H:%M:%S}t.%z %{X-Forwarded-For}i %H %m %h %u %s %B %a:%p -> %A:%P\"";
	$f[]="      #filetype: regular # 'regular', 'unix_stream' or 'unix_dgram'";
	$f[]="";
	$f[]="  # a line based log of TLS handshake parameters (no alerts)";
	$f[]="  - tls-log:";
	$f[]="      enabled: no  # Log TLS connections.";
	$f[]="      filename: tls.log # File to store TLS logs.";
	$f[]="      append: yes";
	$f[]="      #filetype: regular # 'regular', 'unix_stream' or 'unix_dgram'";
	$f[]="      #extended: yes # Log extended information like fingerprint";
	$f[]="      certs-log-dir: certs # directory to store the certificates files";
	$f[]="";
	$f[]="  # a line based log of DNS requests and/or replies (no alerts)";
	$f[]="  - dns-log:";
	$f[]="      enabled: no";
	$f[]="      filename: dns.log";
	$f[]="      append: yes";
	$f[]="      #filetype: regular # 'regular', 'unix_stream' or 'unix_dgram'";
	$f[]="";
	$f[]="  - pcap-info:";
	$f[]="      enabled: no";
	$f[]="";
	$f[]="  - pcap-log:";
	$f[]="      enabled:  no";
	$f[]="      filename: log.pcap";
	$f[]="      limit: 1000mb";
	$f[]="      max-files: 2000";
	$f[]="";
	$f[]="      mode: normal";
	$f[]="      use-stream-depth: no";
	$f[]="";

	$f[]="  - alert-debug:";
	$f[]="      enabled: no";
	$f[]="      filename: alert-debug.log";
	$f[]="      append: yes";
	$f[]="      filetype: regular";
	$f[]="";
	$f[]="  - alert-prelude:";
	$f[]="      enabled: no";
	$f[]="      profile: suricata";
	$f[]="      log-packet-content: no";
	$f[]="      log-packet-header: yes";
	$f[]="";
	$f[]="  - stats:";
	$f[]="      enabled: yes";
	$f[]="      filename: stats.log";
	$f[]="      interval: 10";
	$f[]="";
	$f[]="  # a line based alerts log similar to fast.log into syslog";
	$f[]="  - syslog:";
	$f[]="      enabled: no";
	$f[]="      identity: \"suricata\"";
	$f[]="      facility: local5";
	$f[]="";
	$f[]="  # a line based information for dropped packets in IPS mode";
	$f[]="  - drop:";
	$f[]="      enabled: no";
	$f[]="      filename: drop.log";
	$f[]="      append: yes";
	$f[]="      filetype: regular";
	$f[]="";
	$f[]="  - file-store:";
	$f[]="      enabled: no       # set to yes to enable";
	$f[]="      log-dir: files    # directory to store the files";
	$f[]="      force-magic: no   # force logging magic on all stored files";
	$f[]="      force-md5: no     # force logging of md5 checksums";
	$f[]="      #waldo: file.waldo # waldo file to store the file_id across runs";
	$f[]="";
	
	$SuricataTrackFiles_enabled="no";
	$SuricataTrackFiles=intval($sock->GET_INFO("SuricataTrackFiles"));
	if($SuricataTrackFiles==1){
		$SuricataTrackFiles_enabled="yes";
	}
	$f[]="  - file-log:";
	$f[]="      enabled: $SuricataTrackFiles_enabled";
	$f[]="      filename: files-json.log";
	$f[]="      append: yes";
	$f[]="      filetype: regular";
	$f[]="      force-magic: yes";
	$f[]="      force-md5: yes";
	$f[]="";
	$f[]="magic-file: /usr/share/file/magic";
	$f[]="";
	$f[]="nfq:";
	$f[]="";
	$f[]="nflog:";
	$f[]="  - group: 2";
	$f[]="    buffer-size: 18432";
	$f[]="  - group: default";
	$f[]="    qthreshold: 1";
	$f[]="    qtimeout: 100";
	$f[]="    max-size: 20000";
	$f[]="";
	
	$f[]="legacy:";
	$f[]="  uricontent: enabled";
	$f[]="";
	$f[]="detect-engine:";
	$f[]="  - profile: medium";
	$f[]="  - custom-values:";
	$f[]="      toclient-src-groups: 2";
	$f[]="      toclient-dst-groups: 2";
	$f[]="      toclient-sp-groups: 2";
	$f[]="      toclient-dp-groups: 3";
	$f[]="      toserver-src-groups: 2";
	$f[]="      toserver-dst-groups: 4";
	$f[]="      toserver-sp-groups: 2";
	$f[]="      toserver-dp-groups: 25";
	$f[]="  - sgh-mpm-context: auto";
	$f[]="  - inspection-recursion-limit: 3000";

	$f[]="";
	$f[]="threading:";
	$f[]="  set-cpu-affinity: yes";
	$f[]="";
	$f[]="  cpu-affinity:";
	$f[]="    - management-cpu-set:";
	$f[]="        cpu: [ \"all\" ]";
	$f[]="";
	$f[]="    - receive-cpu-set:";
	$f[]="        cpu: [ 0 ]  # include only these cpus in affinity settings";
	$f[]="";
	$f[]="    - decode-cpu-set:";
	$f[]="        cpu: [ 0, 1 ]";
	$f[]="        mode: \"balanced\"";
	$f[]="";
	$f[]="    - stream-cpu-set:";
	$f[]="        cpu: [ \"0-1\" ]";
	$f[]="";
	$f[]="    - detect-cpu-set:";
	$f[]="        cpu: [ \"all\" ]";
	$f[]="        mode: \"exclusive\"";
	$f[]="        prio:";
	$f[]="          low: [ 0 ]";
	$f[]="          medium: [ \"1-2\" ]";
	$f[]="          high: [ 3 ]";
	$f[]="          default: \"medium\"";
	$f[]="";
	$f[]="    - verdict-cpu-set:";
	$f[]="        cpu: [ 0 ]";
	$f[]="        prio:";
	$f[]="          default: \"high\"";
	$f[]="    - reject-cpu-set:";
	$f[]="        cpu: [ 0 ]";
	$f[]="        prio:";
	$f[]="          default: \"low\"";
	$f[]="    - output-cpu-set:";
	$f[]="        cpu: [ \"all\" ]";
	$f[]="        prio:";
	$f[]="           default: \"medium\"";
	$f[]="  #";
	$f[]="  detect-thread-ratio: 1.5";
	$f[]="";
	$f[]="# Cuda configuration.";
	$f[]="cuda:";
	$f[]="  mpm:";
	$f[]="    data-buffer-size-min-limit: 0";
	$f[]="    data-buffer-size-max-limit: 1500";
	$f[]="    cudabuffer-buffer-size: 500mb";
	$f[]="    gpu-transfer-size: 50mb";
	$f[]="    batching-timeout: 2000";
	$f[]="    device-id: 0";
	$f[]="    cuda-streams: 2";
	$f[]="";
	$f[]="mpm-algo: ac";
	$f[]="";
	$f[]="pattern-matcher:";
	$f[]="  - b2gc:";
	$f[]="      search-algo: B2gSearchBNDMq";
	$f[]="      hash-size: low";
	$f[]="      bf-size: medium";
	$f[]="  - b2gm:";
	$f[]="      search-algo: B2gSearchBNDMq";
	$f[]="      hash-size: low";
	$f[]="      bf-size: medium";
	$f[]="  - b2g:";
	$f[]="      search-algo: B2gSearchBNDMq";
	$f[]="      hash-size: low";
	$f[]="      bf-size: medium";
	$f[]="  - b3g:";
	$f[]="      search-algo: B3gSearchBNDMq";
	$f[]="      hash-size: low";
	$f[]="      bf-size: medium";
	$f[]="  - wumanber:";
	$f[]="      hash-size: low";
	$f[]="      bf-size: medium";
	$f[]="";
	$f[]="# Defrag settings:";
	$f[]="";
	$f[]="defrag:";
	$f[]="  memcap: 32mb";
	$f[]="  hash-size: 65536";
	$f[]="  trackers: 65535 # number of defragmented flows to follow";
	$f[]="  max-frags: 65535 # number of fragments to keep (higher than trackers)";
	$f[]="  prealloc: yes";
	$f[]="  timeout: 60";
	$f[]="";
	$f[]="";
	$f[]="flow:";
	$f[]="  memcap: 64mb";
	$f[]="  hash-size: 65536";
	$f[]="  prealloc: 10000";
	$f[]="  emergency-recovery: 30";
	$f[]="";
	$f[]="vlan:";
	$f[]="  use-for-tracking: true";
	$f[]="";
	$f[]="";
	$f[]="flow-timeouts:";
	$f[]="";
	$f[]="  default:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    closed: 0";
	$f[]="    emergency-new: 10";
	$f[]="    emergency-established: 100";
	$f[]="    emergency-closed: 0";
	$f[]="  tcp:";
	$f[]="    new: 60";
	$f[]="    established: 3600";
	$f[]="    closed: 120";
	$f[]="    emergency-new: 10";
	$f[]="    emergency-established: 300";
	$f[]="    emergency-closed: 20";
	$f[]="  udp:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    emergency-new: 10";
	$f[]="    emergency-established: 100";
	$f[]="  icmp:";
	$f[]="    new: 30";
	$f[]="    established: 300";
	$f[]="    emergency-new: 10";
	$f[]="    emergency-established: 100";
	$f[]="";
	$f[]="# Stream engine settings. Here the TCP stream tracking and reassembly";
	$f[]="# engine is configured.";
	$f[]="#";
	$f[]="stream:";
	$f[]="  memcap: 32mb";
	$f[]="  checksum-validation: no      # reject wrong csums";
	$f[]="  inline: auto                  # auto will use inline mode in IPS mode, yes or no set it statically";
	$f[]="  reassembly:";
	$f[]="    memcap: 128mb";
	$f[]="    depth: 1mb                  # reassemble 1mb into a stream";
	$f[]="    toserver-chunk-size: 2560";
	$f[]="    toclient-chunk-size: 2560";
	$f[]="    randomize-chunk-size: yes";
	$f[]="";
	$f[]="host:";
	$f[]="  hash-size: 4096";
	$f[]="  prealloc: 1000";
	$f[]="  memcap: 16777216";
	$f[]="";

	$f[]="logging:";
	$f[]="";
	$f[]="  default-log-level: notice";
	$f[]="  default-output-filter:";
	$f[]="";
	$f[]="  outputs:";
	$f[]="  - console:";
	$f[]="      enabled: yes";
	$f[]="  - file:";
	$f[]="      enabled: yes";
	$f[]="      filename: /var/log/suricata.log";
	$f[]="  - syslog:";
	$f[]="      enabled: no";
	$f[]="      facility: syslog";
	$f[]="      format: \"[%i] <%d> -- \"";
	$f[]="";
	$f[]="# Tilera mpipe configuration. for use on Tilera TILE-Gx.";
	$f[]="mpipe:";
	$f[]="";
	$f[]="  load-balance: dynamic";
	$f[]="  iqueue-packets: 2048";
	$f[]="  inputs:";
	$f[]="  - interface: xgbe2";
	$f[]="  - interface: xgbe3";
	$f[]="  - interface: xgbe4";
	$f[]="";
	$f[]="";
	$f[]="  # Relative weight of memory for packets of each mPipe buffer size.";
	$f[]="  stack:";
	$f[]="    size128: 0";
	$f[]="    size256: 9";
	$f[]="    size512: 0";
	$f[]="    size1024: 0";
	$f[]="    size1664: 7";
	$f[]="    size4096: 0";
	$f[]="    size10386: 0";
	$f[]="    size16384: 0";
	$f[]="";
	$f[]="# PF_RING configuration. for use with native PF_RING support";
	$f[]="# for more info see http://www.ntop.org/PF_RING.html";
	$clid=100;
	$c=0;
	$f[]="pfring:";
	
	$q=new mysql();
	
	$results = $q->QUERY_SQL("SELECT * FROM suricata_interfaces WHERE enable=1","artica_backup");
	if(!$q->ok){
		$f[]="# suricata_interfaces $q->mysql_error";
	}
	$f[]="# suricata_interfaces ".mysql_num_rows($results);
	while ($ligne = mysql_fetch_assoc($results)) {
		$clid=$clid-1;
		$Interface=$ligne["interface"];
		if(intval($ligne["threads"])==0){$ligne["threads"]=1;}
		$f[]="  - interface: $Interface";
		$f[]="    threads: {$ligne["threads"]}";
		$f[]="    cluster-id: $clid";
		$f[]="    cluster-type: cluster_flow";
		$f[]="";
		$c++;
	}
	if($c==0){
		$f[]="# no interface set, use the default used";
		$f[]="  - interface: $SuricataInterface";
		$f[]="    threads: 1";
		$f[]="    cluster-id: 99";
		$f[]="    cluster-type: cluster_flow";
		$f[]="";
	}

	
	$f[]="default-rule-path: /etc/suricata/rules";
	
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("suricata_rules_packages", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`suricata_rules_packages` (
		`rulefile` VARCHAR(128) NOT NULL PRIMARY KEY ,
		`category` VARCHAR(40) NOT NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 0,
		INDEX ( `category`),
		INDEX ( `enabled`)
		)";
		
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if($q->COUNT_ROWS("suricata_rules_packages", "artica_backup")==0){
		$sql="INSERT IGNORE INTO suricata_rules_packages (rulefile,enabled,category) VALUES 
				('botcc.rules',1,'DMZ'),('ciarmy.rules',0,'DMZ'),('compromised.rules','DMZ',0),
				('drop.rules',1,'DMZ'),
				('dshield.rules',1,'DMZ'),
				('emerging-activex.rules',1,'WEB'),
				('emerging-attack_response.rules',1,'ALL'),
				('emerging-chat.rules',0,'WEB'),
				('emerging-current_events.rules',0,'ALL'),
				('emerging-dns.rules',0,'DMZ'),
				('emerging-dos.rules',0,'DMZ'),
				('emerging-exploit.rules',0,'DMZ'),
				('emerging-ftp.rules',0,'DMZ'),
				('emerging-games.rules',0,'ALL'),
				('emerging-icmp_info.rules',0,'ALL'),
				('emerging-icmp.rules',0,'ALL'),
				('emerging-imap.rules',0,'DMZ'),
				('emerging-inappropriate.rules',0,'WEB'),
				('emerging-malware.rules',1,'WEB'),
				('emerging-mobile_malware.rules',0,'WEB'),
				('emerging-netbios.rules',0,'ALL'),
				('emerging-p2p.rules',0,'WEB'),
				('emerging-policy.rules',1,'WEB'),
				('emerging-pop3.rules',0,'DMZ'),
				('emerging-rpc.rules',0,'ALL'),
				('emerging-scada.rules',0,'ALL'),
				('emerging-scan.rules',1,'ALL'),
				('emerging-shellcode.rules',1,'ALL'),
				('emerging-smtp.rules',0,'DMZ'),
				('emerging-snmp.rules',0,'ALL'),
				('emerging-sql.rules',0,'ALL'),
				('emerging-telnet.rules',0,'ALL'),
				('emerging-tftp.rules',0,'ALL'),
				('emerging-trojan.rules',1,'ALL'),
				('emerging-user_agents.rules',0,'ALL'),
				('emerging-voip.rules',0,'ALL'),
				('emerging-web_client.rules',1,'HTTP'),
				('emerging-web_server.rules',0,'HTTP'),
				('emerging-web_specific_apps.rules',0,'HTTP'),
				('emerging-worm.rules',1,'ALL'),
				('tor.rules',0,'ALL'),
				('decoder-events.rules',0,'ALL'),
				('stream-events.rules',0,'ALL'),
				('http-events.rules',0,'HTTP'),
				('smtp-events.rules',0,'DMZ'),
				('dns-events.rules',0,'DMZ'),
				('tls-events.rules',0,'DMZ')";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	
	
	$f[]="rule-files:";
	$results = $q->QUERY_SQL("SELECT * FROM suricata_rules_packages WHERE enabled=1","artica_backup");
	if(!$q->ok){
		$f[]="# suricata_rules_packages $q->mysql_error";
		return;
	}
	$f[]="# suricata_rules_packages ".mysql_num_rows($results);
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["rulefile"]=="snort.rules"){continue;}
		$f[]=" - {$ligne["rulefile"]}";
	}

	$f[]="";
	$f[]="classification-file: /etc/suricata/classification.config";
	$f[]="reference-config-file: /etc/suricata/reference.config";
	$f[]="";
	$f[]="# Holds variables that would be used by the engine.";
	

	
	$f[]="vars:";
	$f[]="  address-groups:";
	$f[]="    HOME_NET: \"[".@implode(",", $HOME_NET)."]\"";
	$f[]="    EXTERNAL_NET: \"!\$HOME_NET\"";
	$f[]="    HTTP_SERVERS: \"\$HOME_NET\"";
	$f[]="    SMTP_SERVERS: \"\$HOME_NET\"";
	$f[]="    SQL_SERVERS: \"\$HOME_NET\"";
	$f[]="    DNS_SERVERS: \"\$HOME_NET\"";
	$f[]="    TELNET_SERVERS: \"\$HOME_NET\"";
	$f[]="    AIM_SERVERS: \"\$EXTERNAL_NET\"";
	$f[]="    DNP3_SERVER: \"\$HOME_NET\"";
	$f[]="    DNP3_CLIENT: \"\$HOME_NET\"";
	$f[]="    MODBUS_CLIENT: \"\$HOME_NET\"";
	$f[]="    MODBUS_SERVER: \"\$HOME_NET\"";
	$f[]="    ENIP_CLIENT: \"\$HOME_NET\"";
	$f[]="    ENIP_SERVER: \"\$HOME_NET\"";
	
	
	$f[]="";
	$f[]="  port-groups:";
	$HTTP_PORTS=array();
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		$q=new mysql_squid_builder();
		$sql="SELECT port FROM proxy_ports WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			$f[]="# $q->mysql_error";
		}
		while ($ligne = mysql_fetch_assoc($results)) {
		$port=intval($ligne["port"]);
		if($port==80){continue;}
		if($port>0){$HTTP_PORTS[]=$port;}
		}
	}else{
		$f[]="# SquidBin, no such file";
	}
	
	if(count($HTTP_PORTS)==0){
		$f[]="    HTTP_PORTS: \"80\"";
	}else{
		$HTTP_PORTS[]=80;
		$f[]="    HTTP_PORTS: \"[".@implode(",", $HTTP_PORTS)."]\"";
	}
	$f[]="    SHELLCODE_PORTS: \"!80\"";
	$f[]="    ORACLE_PORTS: 1521";
	$f[]="    SSH_PORTS: 22";
	$f[]="    DNP3_PORTS: 20000";
	$f[]="    FILE_DATA_PORTS: \"[110,143]\"";
	
	$f[]="";
	$f[]="# Set the order of alerts bassed on actions";
	$f[]="# The default order is pass, drop, reject, alert";
	$f[]="action-order:";
	$f[]="  - pass";
	$f[]="  - drop";
	$f[]="  - reject";
	$f[]="  - alert";
	$f[]="";
	$f[]="# IP Reputation";
	$f[]="#reputation-categories-file: /etc/suricata/iprep/categories.txt";
	$f[]="#default-reputation-path: /etc/suricata/iprep";
	$f[]="#reputation-files:";
	$f[]="# - reputation.list";
	$f[]="";
	$f[]="# Host specific policies for defragmentation and TCP stream";
	$f[]="# reassembly.  The host OS lookup is done using a radix tree, just";
	$f[]="# like a routing table so the most specific entry matches.";
	$f[]="host-os-policy:";
	$f[]="  # Make the default policy windows.";
	$f[]="  windows: [0.0.0.0/0]";
	$f[]="  bsd: []";
	$f[]="  bsd-right: []";
	$f[]="  old-linux: []";
	$f[]="  linux: [10.0.0.0/8, 192.168.1.100, \"8762:2352:6241:7245:E000:0000:0000:0000\"]";
	$f[]="  old-solaris: []";
	$f[]="  solaris: [\"::1\"]";
	$f[]="  hpux10: []";
	$f[]="  hpux11: []";
	$f[]="  irix: []";
	$f[]="  macos: []";
	$f[]="  vista: []";
	$f[]="  windows2k3: []";
	$f[]="";
	$f[]="";
	$f[]="# Limit for the maximum number of asn1 frames to decode (default 256)";
	$f[]="asn1-max-frames: 256";
	$f[]="";
	$f[]="engine-analysis:";
	$f[]="  rules-fast-pattern: yes";
	$f[]="  rules: yes";
	$f[]="";
	$f[]="#recursion and match limits for PCRE where supported";
	$f[]="pcre:";
	$f[]="  match-limit: 3500";
	$f[]="  match-limit-recursion: 1500";
	$f[]="";
	$f[]="threshold-file: /etc/suricata/threshold.config";
	$f[]="";
	$f[]="app-layer:";
	$f[]="  protocols:";
	$f[]="    tls:";
	$f[]="      enabled: yes";
	$f[]="      detection-ports:";
	$f[]="        dp: 443";
	$f[]="    dcerpc:";
	$f[]="      enabled: yes";
	$f[]="    ftp:";
	$f[]="      enabled: yes";
	$f[]="    ssh:";
	$f[]="      enabled: yes";
	$f[]="    smtp:";
	$f[]="      enabled: yes";
	$f[]="    imap:";
	$f[]="      enabled: detection-only";
	$f[]="    msn:";
	$f[]="      enabled: detection-only";
	$f[]="    smb:";
	$f[]="      enabled: yes";
	$f[]="      detection-ports:";
	$f[]="        dp: 139";
	$f[]="    dns:";
	$f[]="      #global-memcap: 16mb";
	$f[]="      #state-memcap: 512kb";
	$f[]="      #request-flood: 500";
	$f[]="";
	$f[]="      tcp:";
	$f[]="        enabled: yes";
	$f[]="        detection-ports:";
	$f[]="          dp: 53";
	$f[]="      udp:";
	$f[]="        enabled: yes";
	$f[]="        detection-ports:";
	$f[]="          dp: 53";
	$f[]="    http:";
	$f[]="      enabled: yes";
	$f[]="      # memcap: 64mb";
	$f[]="";

	$f[]="      libhtp:";
	$f[]="";
	$f[]="         default-config:";
	$f[]="           personality: IDS";
	$f[]="           request-body-limit: 3072";
	$f[]="           response-body-limit: 3072";
	$f[]="           request-body-minimal-inspect-size: 32kb";
	$f[]="           request-body-inspect-window: 4kb";
	$f[]="           response-body-minimal-inspect-size: 32kb";
	$f[]="           response-body-inspect-window: 4kb";
	$f[]="           #randomize-inspection-sizes: yes";
	$f[]="           #randomize-inspection-range: 10";
	$f[]="           double-decode-path: no";
	$f[]="           double-decode-query: no";
	$f[]="";
	$f[]="         server-config:";
	$f[]="";
	$f[]="profiling:";
	$f[]="  # 1000 received.";
	$f[]="  #sample-rate: 1000";
	$f[]="";
	$f[]="  # rule profiling";
	$f[]="  rules:";
	$f[]="    enabled: yes";
	$f[]="    filename: rule_perf.log";
	$f[]="    append: yes";
	$f[]="    sort: avgticks";
	$f[]="    limit: 100";
	$f[]="";
	$f[]="  keywords:";
	$f[]="    enabled: yes";
	$f[]="    filename: keyword_perf.log";
	$f[]="    append: yes";
	$f[]="";
	$f[]="  packets:";
	$f[]="    enabled: yes";
	$f[]="    filename: packet_stats.log";
	$f[]="    append: yes";
	$f[]="";
	$f[]="    csv:";
	$f[]="      enabled: no";
	$f[]="      filename: packet_stats.csv";
	$f[]="";
	$f[]="  # profiling of locking. Only available when Suricata was built with";
	$f[]="  # --enable-profiling-locks.";
	$f[]="  locks:";
	$f[]="    enabled: no";
	$f[]="    filename: lock_stats.log";
	$f[]="    append: yes";
	$f[]="";
	$f[]="";
	$f[]="coredump:";
	$f[]="  max-dump: unlimited";
	$f[]="";
	$f[]="napatech:";
	$f[]="    hba: -1";
	$f[]="    use-all-streams: yes";
	$f[]="    streams: [1, 2, 3]";
	$f[]="";
	$f[]="#include: include1.yaml";
	$f[]="#include: include2.yaml";
	echo "/etc/suricata/suricata.yaml done..\n";
	@file_put_contents("/etc/suricata/suricata.yaml", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} suricata.yaml done\n";}

	threshold();
	build_classification();
}




function pulledpork_run(){
	
	
	$cmd="/usr/sbin/pulledpork.pl -P -c /etc/pulledpork/pulledpork.conf";
	
}

function pulledpork_conf(){
	$sock=new sockets();
	$SnortRulesCode=$sock->GET_INFO("SnortRulesCode");
	
$f[]="# Be sure to read through the entire configuration file";
$f[]="# If you specify any of these items on the command line, it WILL take ";
$f[]="# precedence over any value that you specify in this file!";
$f[]="";
$f[]="#######";
$f[]="#######  The below section defines what your oinkcode is (required for ";
$f[]="#######  VRT rules), defines a temp path (must be writable) and also ";
$f[]="#######  defines what version of rules that you are getting (for your ";
$f[]="#######  snort version and subscription etc...)";
$f[]="####### ";
$f[]="";
if($SnortRulesCode<>null){
	$f[]="rule_url=https://www.snort.org/reg-rules/|snortrules-snapshot-2976.tar.gz|$SnortRulesCode";
	$f[]="rule_url=https://www.snort.org/reg-rules/|opensource.gz|$SnortRulesCode";
}
$f[]="";
$f[]="rule_url=https://snort.org/downloads/community/|community-rules.tar.gz|Community";
$f[]="rule_url=http://labs.snort.org/feeds/ip-filter.blf|IPBLACKLIST|open";
$f[]="rule_url=https://rules.emergingthreatspro.com/|emerging.rules.tar.gz|open";
$f[]="# ignore = dos,sensitive-data.preproc,p2p.so,netbios.rules";
$f[]="ignore=deleted.rules,experimental.rules,local.rules";
$f[]="# ignore=deleted,experimental,local,decoder,preprocessor,sensitive-data";
$f[]="temp_path=/tmp";
$f[]="";
$f[]="rule_path=/etc/suricata/rules/snort.rules";
$f[]="out_path=/etc/suricata/rules/";
$f[]="local_rules=/etc/suricata/rules/local.rules";
$f[]="sid_msg=/etc/suricata/rules/sid-msg.map";
$f[]="sid_msg_version=1";
$f[]="sid_changelog=/var/log/suricata/sid_changes.log";
$f[]="#sorule_path=/usr/local/lib/snort_dynamicrules/";
$f[]="# Path to the snort binary, we need this to generate the stub files";
$f[]="snort_path=/usr/bin/suricata";
$f[]="";
$f[]="# We need to know where your snort.conf file lives so that we can";
$f[]="# generate the stub files";
$f[]="#config_path=/usr/local/etc/snort/snort.conf";
$f[]="distro=Debian-6-0";
$f[]="";
$f[]="black_list=/etc/suricata/rules/default.blacklist";
$f[]="IPRVersion=/etc/suricata/rules/iplists";
$f[]="#snort_control=/usr/local/bin/snort_control";
$f[]="# backup=/usr/local/etc/snort,/usr/local/etc/pulledpork,/usr/local/lib/snort_dynamicrules/";
$f[]="# backup_file=/tmp/pp_backup";
$f[]="# docs=/path/to/base/www";
$f[]="# state_order=disable,drop,enable";
$f[]="# pid_path=/var/run/snort_eth0.pid";
$f[]="# snort_version=2.9.0.0";
$f[]="";
$f[]="enablesid=/etc/pulledpork/enablesid.conf";
$f[]="dropsid=/etc/pulledpork/dropsid.conf";
$f[]="disablesid=/etc/pulledpork/disablesid.conf";
$f[]="modifysid=/etc/pulledpork/modifysid.conf";
$f[]="";
$f[]="# ips_policy=security";
$f[]="version=0.7.0";
$f[]="";

@file_put_contents("/etc/pulledpork/pulledpork.conf", @implode("\n", $f));
	
}

function threshold(){
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("select signature FROM suricata_sig where enabled=0");
	
	while($ligne=@pg_fetch_assoc($results)){
		$sig=$ligne["signature"];
		$f[]="suppress gen_id 1, sig_id $sig";
		
	}
	
	@file_put_contents("/etc/suricata/threshold.config", @implode("\n", $f)."\n");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} threshold.config ". count($f)." disabled rules\n";}
}


function build_classification(){
	
	$q=new mysql();
	$f=explode("\n",@file_get_contents("/etc/suricata/rules/classification.config"));
	$postgres=new postgres_sql();
	$postgres->suricata_tables();
	
	$q=new mysql();
	$t=array();
	
	if($q->TABLE_EXISTS("suricata_classifications", "artica_backup")){
		$results=$q->QUERY_SQL("SELECT * FROM suricata_classifications");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$uduniq=$ligne["uduniq"];
			$shortname=pg_escape_string2($ligne["shortname"]);
			$description=pg_escape_string2($ligne["description"]);
			$priority=$ligne["priority"];
			$t[]="('$uduniq','$shortname','$description','$priority')";
			
		}
		
		$q->QUERY_SQL("DROP TABLE suricata_classifications","artica_backup");
	}
	
	
	
	
	
	while (list ($num, $val) = each ($f)){
		$val=trim($val);
		if(trim($val)==null){continue;}
		if(substr($val, 0,1)=="#"){continue;}
		if(!preg_match("#^config classification:\s+(.+?),(.+?),([0-9]+)#", $val,$re)){continue;}
		$uduniq=md5($re[2]);
		$shortname=mysql_real_escape_string($re[1]);
		$description=mysql_real_escape_string($re[2]);
		$priority=$re[3];
		
		$t[]="('$uduniq','$shortname','$description','$priority')";
	}
	
	if(count($t)>0){
		$sql="INSERT INTO suricata_classifications (uduniq,shortname,description,priority) VALUES ".@implode(",", $t)." ON CONFLICT DO NOTHING";
		$postgres->QUERY_SQL($sql);
		if(!$postgres->ok){
			echo $postgres->mysql_error."\n";
		}
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} classifications done\n";}
	
}

function suricata_dashboard(){
	$unix=new unix();
	$TimeFile="{$GLOBALS["BASEDIR"]}/suricata.dashboard";
	
	if(!$GLOBALS["FORCE"]){
		$TimeEx=$unix->file_time_min($TimeFile);
		if($TimeEx<15){return;}
	}
	
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("suricata_events")){return;}
	
	$results=$q->QUERY_SQL("SELECT SUM(xcount) as tcount, severity FROM suricata_events GROUP BY severity");
	if(!$q->ok){return;}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$severity=$ligne["severity"];
		$tcount=$ligne["tcount"];
		if($tcount==0){continue;}
		$ARRAY["SEVERITIES"][$severity]=$tcount;
		
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, serialize($ARRAY));
	@chmod($TimeFile, 0755);
	
}

function parse_rulesToPostGres(){
	if(!is_file("/etc/suricata/rules/sid-msg.map")){return;}
	$prefix="INSERT INTO suricata_sig (signature,description,enabled) VALUES ";
	$f=explode("\n",@file_get_contents("/etc/suricata/rules/sid-msg.map"));
	$I=array();
	while (list ($num, $val) = each ($f)){
		$tr=explode("||",$val);
		$sig=intval(trim($tr[0]));
		if($sig==0){
			echo "SIG  === 0 / $val\n";
			continue;}
		$explain=trim(pg_escape_string2($tr[1]));
		if($explain==null){continue;}
		if(strlen($explain)>128){$explain=substr($explain, 0,128);}
		$I[]="('$sig',E'$explain',1)";
		
		}
		
	if(count($I)==0){return;}
	
	$sql=$prefix.@implode(",", $I). " ON CONFLICT DO NOTHING";
	
	$postgres=new postgres_sql();
	$postgres->QUERY_SQL($sql);
	if(!$postgres->ok){echo $postgres->mysql_error."\n";}
}

function firewall($signature){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$proxyname=$unix->hostname_g();
	$suffixTables="-m comment --comment \"ArticaSuricata\"";
	$prefix="INSERT INTO suricata_firewall (zdate,uduniq,signature,src_ip,dst_port,proto,proxyname) VALUES ";
	
	$zdate=date("Y-m-d H:i:s");
	$iptables=$unix->find_program("iptables");
	
	
	
	
	
	
	$ARRAY=array();
	$q=new postgres_sql();
	$sql="SELECT * FROM suricata_events WHERE signature='$signature'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@pg_fetch_assoc($results)){
		$src_ip=$ligne["src_ip"];
		$zdate=$ligne["zdate"];
		$dst_ip=$ligne["dst_ip"];
		$dst_port=$ligne["dst_port"];
		$proto=$ligne["proto"];
		$proto=strtolower($proto);
		$uduniq=md5("$signature,$src_ip,$dst_port,$proto");
		$content="('$zdate','$uduniq','$signature','$src_ip','$dst_port','$proto','$proxyname')";
		$sql_line="$prefix $content ON CONFLICT DO NOTHING";
		$cmdline="$iptables -I INPUT -p $proto -m $proto -s $src_ip --dport $dst_port -j DROP $suffixTables >>/var/log/suricata/tail.debug 2>&1";
		$ARRAY[$uduniq]["SQL"]=$sql_line;
		$ARRAY[$uduniq]["FW"]=$cmdline;
	}
	
	if(count($ARRAY)==0){return;}
	
	
	while (list ($num, $main) = each ($ARRAY)){
		$sql=$main["SQL"];
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		$q->QUERY_SQL($sql);
		shell_exec($main["FW"]);
		if($GLOBALS["VERBOSE"]){echo $main["FW"]."\n";}
	}
	
}



