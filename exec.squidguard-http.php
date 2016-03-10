<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Webfiltering splash";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.lighttpd.certificate.php');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}



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


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("lighttpd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

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

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	$EnableSquidGuardHTTPToArtica=intval($sock->GET_INFO("EnableSquidGuardHTTPToArtica"));
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($GLOBALS["VERBOSE"]){echo "* * * EnableUfdbGuard = $EnableUfdbGuard * * *\n";}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
	
	if(!is_numeric($EnableUfdbGuard)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: disabled $EnableUfdbGuard=0\n";}
		if($GLOBALS["VERBOSE"]){echo "* * * EnableUfdbGuard NOT A NUMERIC => 0 * * *\n";}
		$EnableUfdbGuard=0;
	}
	
	
	if($EnableUfdbGuard==0){
		$EnableSquidGuardHTTPService=0;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: disabled because EnableUfdbGuard=$EnableUfdbGuard\n";}
	
	}
	if($SQUIDEnable==0){$SQUIDEnable=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableSquidGuardHTTPService=1;}
	if($SquidPerformance>2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: disabled because EnableSquidGuardHTTPService=$EnableSquidGuardHTTPService\n";}
		$EnableSquidGuardHTTPService=0;
	}
	if($EnableSquidGuardHTTPToArtica==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: disabled because EnableSquidGuardHTTPService=$EnableSquidGuardHTTPService\n";}
		$EnableSquidGuardHTTPService=0;
	}

	if($EnableSquidGuardHTTPService==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableUfdbGuard = $EnableUfdbGuard\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableWebProxyStatsAppliance = $EnableWebProxyStatsAppliance\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQUIDEnable = $SQUIDEnable\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableSquidGuardHTTPToArtica = $EnableSquidGuardHTTPToArtica\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SquidPerformance = $SquidPerformance\n";}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled\n";}
		
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	
	
	$TMPFILE="/var/log/lighttpd/squidguard-lighttpd.start";
	
	$CMDS[]="$nohup";
	$CMDS[]=$unix->find_program("lighttpd");
	$CMDS[]="-f /etc/artica-postfix/squidguard-lighttpd.conf";
	$CMDS[]="> $TMPFILE 2>&1 &";
	$cmd=@implode(" ", $CMDS);
	shell_exec($cmd);

	for($i=1;$i<6;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		$f=explode("\n",@file_get_contents($TMPFILE));
		while (list ($index, $line) = each ($f) ){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [DEBUG]:1] $line\n";}}
		$f=explode("\n",@file_get_contents("/var/log/lighttpd/squidguard-lighttpd-error.log"));
		while (list ($index, $line) = each ($f) ){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [DEBUG]:2] $line\n";}}
	}


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
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
}

function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$lighttpd_bin=$unix->find_program("lighttpd");
	return $unix->PIDOF_PATTERN("$lighttpd_bin -f /etc/artica-postfix/squidguard-lighttpd.conf");
}

function PID_PATH(){
	return "/var/run/lighttpd/squidguard-lighttpd.pid";
}




function build(){
	$unix=new unix();
	@mkdir("/var/run/lighttpd",0755,true);
	@mkdir("/var/log/lighttpd",0755,true);
	$username=$unix->LIGHTTPD_USER();
	$sock=new sockets();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$chown=$unix->find_program("chown");
	$perlbin=$unix->find_program("perl");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$PHP_STANDARD_MODE=true;	
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	$SquidGuardWebSSLCertificate=$sock->GET_INFO("SquidGuardWebSSLCertificate");
	@mkdir("/home/squid/error_page_sessions",0755, true);
	@mkdir("/home/squid/error_page_cache",0755, true);
	
	if($username==null){
		$username="www-data";
		$unix->CreateUnixUser($username,$username,"lighttpd username");
	}
	
	if(preg_match("#^(.+?):(.+)#", $username,$re)){$username=$re[1];$username=$re[1];}
	
	$SquidGuardStorageDir=$sock->GET_INFO("SquidGuardStorageDir");
	
	@unlink("/var/log/lighttpd/squidguard-lighttpd-error.log");
	@unlink("/var/log/lighttpd/squidguard-lighttpd.log");
	
	
	if(!is_file("/var/log/lighttpd/squidguard-lighttpd.log")){@file_put_contents("/var/log/lighttpd/squidguard-lighttpd.log", "#");}
	if(!is_file("/var/log/lighttpd/squidguard-lighttpd-error.log")){@file_put_contents("/var/log/artica-postfix/lighttpd-error.log", "#");}
	
	$unix->chown_func($username,$username, "/var/log/lighttpd/squidguard-lighttpd.log");
	$unix->chown_func($username,$username, "/var/log/lighttpd/squidguard-lighttpd-error.log");
	$unix->chown_func($username,$username, "/home/squid/error_page_sessions");
	$unix->chown_func($username,$username, "/home/squid/error_page_cache");
	$unix->chown_func($username,$username, "/usr/share/artica-postfix/bin/install/squid/adzap/zaps/*");
	@chmod("/var/log/lighttpd/squidguard-lighttpd-error.log",0777);
	@chmod("/var/log/lighttpd/squidguard-lighttpd.log",0777);
	
	if($SquidGuardStorageDir==null){$SquidGuardStorageDir="/home/artica/cache";}
	@mkdir($SquidGuardStorageDir,0755,true);
	$unix->chown_func($username,$username,$SquidGuardStorageDir);
	
	$LighttpdUseUnixSocket=$sock->GET_INFO('LighttpdUseUnixSocket');
	if(!is_numeric($LighttpdUseUnixSocket)){$LighttpdUseUnixSocket=0;}
	
	$lighttpdPhpPort=$sock->GET_INFO('lighttpdPhpPort');
	if(!is_numeric($lighttpdPhpPort)){$lighttpdPhpPort=1808;}

	$SquidGuardEvasiveMaxConnsPerIP=$sock->GET_INFO("SquidGuardEvasiveMaxConnsPerIP");
	if(!is_numeric($SquidGuardEvasiveMaxConnsPerIP)){$SquidGuardEvasiveMaxConnsPerIP=5;}
	
	
	$LighttpdArticaMaxProcs=$sock->GET_INFO('LighttpdArticaMaxProcs');
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=0;}
	
	$LighttpdArticaMaxChildren=$sock->GET_INFO('LighttpdArticaMaxChildren');
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=0;}
	
	
	$LighttpdRunAsminimal=$sock->GET_INFO('LighttpdRunAsminimal');
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO('PHP_FCGI_MAX_REQUESTS');
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	
	
	$EnablePHPFPM=$sock->GET_INFO('EnablePHPFPM');
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}

	$PHP_STANDARD_MODE=true;
	$phpcgi_path=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Run as: $username\n";}
	
	
	$PHP_FCGI_CHILDREN=1;
	$max_procs=2;
	
	
	if($LighttpdArticaMaxProcs>0){$max_procs=$LighttpdArticaMaxProcs;}
	if($LighttpdArticaMaxChildren>0){$HP_FCGI_CHILDREN=$LighttpdArticaMaxChildren;}
	if($LighttpdRunAsminimal==1){$max_procs=2;$PHP_FCGI_CHILDREN=2;}	
	

	
$f[]="#artica-postfix saved by artica lighttpd.conf";
$f[]="";
$f[]="server.modules = (";
$f[]="        \"mod_alias\",";
$f[]="        \"mod_access\",";
$f[]="        \"mod_accesslog\",";
$f[]="        \"mod_compress\",";
$f[]="        \"mod_fastcgi\",";
$f[]="        \"mod_cgi\",";
$f[]="		  \"mod_evasive\",";
$f[]="	       \"mod_status\"";
$f[]=")";
$f[]="";
$f[]="server.document-root        = \"/usr/share/artica-postfix\"";
$f[]="server.username = \"$username\"";
$f[]="server.groupname = \"$username\"";
$f[]="server.errorlog             = \"/var/log/lighttpd/squidguard-lighttpd-error.log\"";
$f[]="index-file.names            = ( \"ufdbguardd.php\")";

$f[]="";
$f[]="mimetype.assign             = (";
$f[]="  \".pdf\"          =>      \"application/pdf\",";
$f[]="  \".sig\"          =>      \"application/pgp-signature\",";
$f[]="  \".spl\"          =>      \"application/futuresplash\",";
$f[]="  \".class\"        =>      \"application/octet-stream\",";
$f[]="  \".ps\"           =>      \"application/postscript\",";
$f[]="  \".torrent\"      =>      \"application/x-bittorrent\",";
$f[]="  \".dvi\"          =>      \"application/x-dvi\",";
$f[]="  \".gz\"           =>      \"application/x-gzip\",";
$f[]="  \".pac\"          =>      \"application/x-ns-proxy-autoconfig\",";
$f[]="  \".swf\"          =>      \"application/x-shockwave-flash\",";
$f[]="  \".tar.gz\"       =>      \"application/x-tgz\",";
$f[]="  \".tgz\"          =>      \"application/x-tgz\",";
$f[]="  \".tar\"          =>      \"application/x-tar\",";
$f[]="  \".zip\"          =>      \"application/zip\",";
$f[]="  \".mp3\"          =>      \"audio/mpeg\",";
$f[]="  \".m3u\"          =>      \"audio/x-mpegurl\",";
$f[]="  \".wma\"          =>      \"audio/x-ms-wma\",";
$f[]="  \".wax\"          =>      \"audio/x-ms-wax\",";
$f[]="  \".ogg\"          =>      \"application/ogg\",";
$f[]="  \".wav\"          =>      \"audio/x-wav\",";
$f[]="  \".gif\"          =>      \"image/gif\",";
$f[]="  \".jar\"          =>      \"application/x-java-archive\",";
$f[]="  \".jpg\"          =>      \"image/jpeg\",";
$f[]="  \".jpeg\"         =>      \"image/jpeg\",";
$f[]="  \".png\"          =>      \"image/png\",";
$f[]="  \".xbm\"          =>      \"image/x-xbitmap\",";
$f[]="  \".xpm\"          =>      \"image/x-xpixmap\",";
$f[]="  \".xwd\"          =>      \"image/x-xwindowdump\",";
$f[]="  \".css\"          =>      \"text/css\",";
$f[]="  \".html\"         =>      \"text/html\",";
$f[]="  \".htm\"          =>      \"text/html\",";
$f[]="  \".js\"           =>      \"text/javascript\",";
$f[]="  \".asc\"          =>      \"text/plain\",";
$f[]="  \".c\"            =>      \"text/plain\",";
$f[]="  \".cpp\"          =>      \"text/plain\",";
$f[]="  \".log\"          =>      \"text/plain\",";
$f[]="  \".conf\"         =>      \"text/plain\",";
$f[]="  \".text\"         =>      \"text/plain\",";
$f[]="  \".txt\"          =>      \"text/plain\",";
$f[]="  \".dtd\"          =>      \"text/xml\",";
$f[]="  \".xml\"          =>      \"text/xml\",";
$f[]="  \".mpeg\"         =>      \"video/mpeg\",";
$f[]="  \".mpg\"          =>      \"video/mpeg\",";
$f[]="  \".mov\"          =>      \"video/quicktime\",";
$f[]="  \".qt\"           =>      \"video/quicktime\",";
$f[]="  \".avi\"          =>      \"video/x-msvideo\",";
$f[]="  \".asf\"          =>      \"video/x-ms-asf\",";
$f[]="  \".asx\"          =>      \"video/x-ms-asf\",";
$f[]="  \".wmv\"          =>      \"video/x-ms-wmv\",";
$f[]="  \".bz2\"          =>      \"application/x-bzip\",";
$f[]="  \".tbz\"          =>      \"application/x-bzip-compressed-tar\",";
$f[]="  \".tar.bz2\"      =>      \"application/x-bzip-compressed-tar\",";
$f[]="  \"\"              =>      \"application/octet-stream\",";
$f[]=" )";
$f[]="";
$f[]="";
$f[]="accesslog.filename          = \"/var/log/lighttpd/squidguard-lighttpd.log\"";
$f[]="url.access-deny             = ( \"~\", \".inc\",\".log\",\".ini\",\"ressources\",\"computers\",\"user-backup\",\"logon.php\",\"index.php\")";
$f[]="";
$f[]="static-file.exclude-extensions = ( \".php\", \".pl\", \".fcgi\" )";
$f[]="server.port                 = $SquidGuardApachePort";
$f[]="#server.bind                = \"127.0.0.1\"";
$f[]="server.error-handler-404   = \"/exec.squidguard.php\"";
$f[]="#server.error-handler-404   = \"/error-handler.php\"";
$f[]="server.pid-file             = \"/var/run/lighttpd/squidguard-lighttpd.pid\"";
$f[]="server.max-fds 		   = 2048";
$f[]="evasive.max-conns-per-ip = $SquidGuardEvasiveMaxConnsPerIP";
//$f[]="server.network-backend      = \"write\"";
$f[]="server.network-backend = \"linux-sendfile\"";
$f[]="server.follow-symlink = \"enable\"";
$f[]="server.max-keep-alive-requests = 0";
$f[]="server.max-keep-alive-idle = 1";
$f[]="server.max-read-idle = 15";
$f[]="server.max-write-idle = 15";
$f[]="server.event-handler = \"linux-sysepoll\"";

$f[]="";
$f[]='';
$f[]="\$SERVER[\"socket\"]== \":$SquidGuardApacheSSLPort\" {";
$f[]="\tssl.engine                 = \"enable\"";
$cert=new lighttpd_certificate($SquidGuardWebSSLCertificate);
$f[]=$cert->build();

$f[]="ssl.cipher-list=\"ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM:+SSLv3\"";
$f[]="}";	
if(!is_file("/opt/artica/ssl/certs/lighttpd.pem")){
	@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
	shell_exec("/usr/share/artica-postfix/bin/artica-install -lighttpd-cert");
	
}
// 

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen on: $SquidGuardApachePort\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen on: $SquidGuardApacheSSLPort SSL\n";}

$phpfpm=$unix->find_program('php5-fpm')  ;
if(!is_file($phpfpm)){$phpfpm=$unix->find_program('php-fpm');}


	if(is_file($phpfpm)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PHP-FPM is installed\n";}
		if($EnablePHPFPM==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PHP-FPM is enabled\n";}
			$PHP_STANDARD_MODE=false;
			$f[]='fastcgi.server = ( ".php" =>((';
			$f[]='         "socket" => "/var/run/php-fpm.sock",';
		}
	}
	
	
	
	if ($PHP_STANDARD_MODE){
		$f[]='fastcgi.server = ( ".php" =>((';
		$f[]='         "bin-path" => "/usr/bin/php-cgi",';
	if($LighttpdUseUnixSocket==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Fast-cgi server unix socket mode\n";}
			$f[]='         "socket" => "/var/run/lighttpd/php.socket" + var.PID,';
	}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Fast-cgi server socket 127.0.0.1:$lighttpdPhpPort\n";}
			$f[]='         "host" => "127.0.0.1","port" =>'.$lighttpdPhpPort.',';
		}
	}
	
	$f[]='         "max-procs" => '.$max_procs.',';
	$f[]='         "idle-timeout" => 10,';
	$f[]='         "bin-environment" => (';
	$f[]='             "PHP_FCGI_CHILDREN" => "'.$PHP_FCGI_CHILDREN.'",';
	$f[]='             "PHP_FCGI_MAX_REQUESTS" => "'.$PHP_FCGI_MAX_REQUESTS.'"';
	$f[]='          ),';
	$f[]='          "bin-copy-environment" => (';
	$f[]='            "PATH", "SHELL", "USER"';
	$f[]='           ),';
	$f[]='          "broken-scriptfilename" => "enable"';
	$f[]='        ))';
	$f[]=')';
	
	$f[]="alias.url += ( \"/css/\" => \"/usr/share/artica-postfix/css/\" )";
	$f[]="alias.url += ( \"/img/\" => \"/usr/share/artica-postfix/img/\" )";
	$f[]="alias.url += ( \"/js/\" => \"/usr/share/artica-postfix/js/\" )";
	$f[]="alias.url += ( \"/zaps/\" => \"/usr/share/artica-postfix/bin/install/squid/adzap/zaps/\" )";
	$f[]="";
	$f[]="cgi.assign= (";
	$f[]="	\".pl\"  => \"/usr/bin/perl\",";
	$f[]="	\".php\" => \"/usr/bin/php-cgi\",";
	$f[]="	\".py\"  => \"/usr/bin/python\",";
	$f[]="	\".cgi\"  => \"/usr/bin/perl\",";
	$f[]=")";


	@file_put_contents("/etc/artica-postfix/squidguard-lighttpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} squidguard-lighttpd.conf done.\n";}
	
}