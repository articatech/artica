<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="AdsBlocker daemon";
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
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');

// Usage: /etc/init.d/clamav-daemon {start|stop|restart|force-reload|reload-log|reload-database|status}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--reconfigure-squid"){$GLOBALS["OUTPUT"]=true;InSquid(true);die();}
if($argv[1]=="--template"){$GLOBALS["OUTPUT"]=true;template(true);die();}




function restart() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress_restart("{failed}",110);
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	
	build_progress_restart("{stopping_service}",15);
	if(!stop(true)){
		build_progress_restart("{failed}",110);
		return;
	}
	
	if($PrivoxyEnabled==0){
		$size=@filesize("/etc/squid3/privoxy.conf");
		if($size>1){
			echo "Remove link with main proxy...\n";
			build_progress_restart("{reconfiguring}",20);
			@unlink("/etc/squid3/privoxy.conf");
			@touch("/etc/squid3/privoxy.conf");
			$squidbin=$unix->LOCATE_SQUID_BIN();
			shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
		}
		
		build_progress_restart("{disabled}",90);
		sleep(2);
		build_progress_restart("{success}",100);
		return;
	}
	
	
	build_progress_restart("{reconfiguring}",30);
	if(!build()){
		return;
	}
	sleep(1);
	build_progress_restart("{starting_service}",80);
	if(!start(true)){
		build_progress_restart("{failed}",110);
		return;
	}
	
	build_progress_restart("{success}",100);
	

}
function build_progress_restart($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_squidr($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.squid.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_template($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.template.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function reload($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("privoxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
	
	
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	if($PrivoxyEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see ClamavMilterEnabled)\n";}
		return false;
	}
	
	
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("privoxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, service not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	
	
	

	if($PrivoxyEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see PrivoxyEnabled)\n";}
		return false;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	build_progress_restart("{starting_service}",31);
	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add clamd Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	@unlink("/var/log/privoxy/start.log");
	$privoxy_version=privoxy_version();
	$cmd="$nohup $Masterbin --pidfile /var/run/privoxy.pid --user squid /etc/privoxy/privoxy.conf >/var/log/privoxy/start.log 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service version $privoxy_version\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		build_progress_restart("{starting_service}",35);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

		
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		build_progress_restart("{starting_service} {failed}",40);
		echo " ******\n$cmd\n ******\n";
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	return true;


}

function privoxy_version($bin){
	$unix=new unix();
	if(isset($GLOBALS["privoxy_version"])){return $GLOBALS["privoxy_version"];}
	$bin=$unix->find_program("privoxy");
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^Privoxy version\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["privoxy_version"]=$re[1];
	}

	return $GLOBALS["privoxy_version"];

}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/privoxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("privoxy");
	return $unix->PIDOF($Masterbin);

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
		return true;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$chmod=$unix->find_program("chmod");



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
		return true;
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
		return false;
	}
return true;
}

function build(){
	$sock=new sockets();
	$unix=new unix();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$PrivoxyPort=intval($sock->GET_INFO("PrivoxyPort"));
	if($PrivoxyPort==0){
		$PrivoxyPort=rand(15000,5000);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyPort", $PrivoxyPort);
	}
	$visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
	$visible_hostname=str_replace("..", ".", $visible_hostname);
	if($visible_hostname==null){$visible_hostname=$unix->hostname_g();}
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} listen 127.0.0.1:$PrivoxyPort\n";}
	
	@mkdir("/etc/privoxy",0755,true);
	@mkdir("/var/log/privoxy",0755,true);
	@mkdir("/home/privoxy",0755,true);
	
	@chown("/var/log/privoxy", "squid");
	@chgrp("/var/log/privoxy", "squid");
	@chgrp("/etc/privoxy", "squid");
	
	@chown("/home/privoxy", "squid");
	@chgrp("/home/privoxy", "squid");
	@chgrp("/etc/privoxy", "squid");
	
	$f[]="user-manual /usr/local/share/doc/privoxy/user-manual/";
	$f[]="#trust-info-url  http://www.example.com/why_we_block.html";
	$f[]="#trust-info-url  http://www.example.com/what_we_allow.html";
	$f[]="#admin-address privoxy-admin@example.com";
	$f[]="#proxy-info-url http://www.example.com/proxy-service.html";
	$f[]="confdir /etc/privoxy";
	$f[]="templdir /home/privoxy";
	$f[]="#temporary-directory .";
	$f[]="logdir /var/log/privoxy";
	$f[]="actionsfile match-all.action";
	$f[]="actionsfile default.action";
	
	Artica_pattern();
	
	$actionsfile[]="malwaredomains_full.script.action";
	$actionsfile[]="fanboy-social.script.action";
	$actionsfile[]="easyprivacy.script.action";
	$actionsfile[]="easylist.script.action";
	$actionsfile[]="easylistdutch.script.action";
	$actionsfile[]="easylistdutch+easylist.script.action";
	$actionsfile[]="liste_fr.script.action";
	$actionsfile[]="easylistchina.script.action";
	$actionsfile[]="easylistitaly.script.action";
	$actionsfile[]="artica.action";
	
	$filterfile[]="malwaredomains_full.script.filter";
	$filterfile[]="fanboy-social.script.filter";
	$filterfile[]="easyprivacy.script.filter";
	$filterfile[]="easylist.script.filter";
	$filterfile[]="easylistdutch.script.filter";
	$filterfile[]="easylistdutch+easylist.script.filter";
	$filterfile[]="liste_fr.script.filter";
	$filterfile[]="easylistchina.script.filter";
	$filterfile[]="easylistitaly.script.filter";
	
	
	
	
	while (list ($num, $filename) = each ($actionsfile)){
		if(!is_file("/etc/privoxy/$filename")){continue;}
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $filename\n";}
		$f[]="actionsfile $filename";
	}
	
	
	$f[]="actionsfile user.action";
	
	
	
	$f[]="filterfile default.filter";
	
	while (list ($num, $filename) = each ($filterfile)){
		if(!is_file("/etc/privoxy/$filename")){continue;}
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $filename\n";}
		$f[]="filterfile $filename";
	}
	
	
	$f[]="filterfile user.filter";
	
	
	
	$f[]="logfile privoxy.log";
	$f[]="#trustfile trust";
	$f[]="#debug     1 # Log the destination for each request Privoxy let through. See also debug 1024.";
	$f[]="#debug  1024 # Actions that are applied to all sites and maybe overruled later on.";
	$f[]="#debug  4096 # Startup banner and warnings";
	$f[]="#debug  8192 # Non-fatal errors";
	$f[]="debug 1024";
	$f[]="single-threaded 0";
	$f[]="hostname $visible_hostname";
	$f[]="listen-address  127.0.0.1:$PrivoxyPort";
	$f[]="toggle  1";
	$f[]="enable-remote-toggle  1";
	$f[]="enable-remote-http-toggle  1";
	$f[]="enable-edit-actions 1";
	$f[]="enforce-blocks 1";
	$f[]="buffer-limit 4096";
	$f[]="enable-proxy-authentication-forwarding 1";
	$f[]="forwarded-connect-retries  0";
	$f[]="accept-intercepted-requests 1";
	$f[]="allow-cgi-request-crunching 0";
	$f[]="split-large-forms 0";
	$f[]="keep-alive-timeout 300";
	$f[]="tolerate-pipelining 1";
	$f[]="#default-server-timeout 60";
	$f[]="#connection-sharing 1";
	$f[]="socket-timeout 600";
	$f[]="max-client-connections 512";
	$f[]="#handle-as-empty-doc-returns-ok 1";
	$f[]="#enable-compression 1";
	$f[]="#compression-level 9";
	$f[]="#activity-animation   1";
	$f[]="#log-messages   1";
	$f[]="#log-buffer-size 1";
	$f[]="#log-max-lines 200";
	$f[]="#log-highlight-messages 1";
	$f[]="#log-font-name Comic Sans MS";
	$f[]="#log-font-size 8";
	$f[]="#show-on-task-bar 0";
	$f[]="#close-button-minimizes 1";
	$f[]="#hide-console";
	$f[]="";
		
	
	
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.default.filter")){
		echo "Missing default.filter file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.default.action")){
		echo "Missing default.action file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action")){
		echo "Missing user.action file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}	
	
	if(!is_file("/etc/privoxy/default.filter")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/default.filter\n";}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.default.filter","/etc/privoxy/default.filter");
	}
	if(!is_file("/etc/privoxy/default.action")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/default.action\n";}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.default.action","/etc/privoxy/default.action");
	}
	
	if(!is_file("/etc/privoxy/user.action")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/user.action\n";}
		
		if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action")){
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} fatal privoxy.user.action no such file!!!!\n";}
		}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action","/etc/privoxy/user.action");
	}
	
	if(!is_file("/etc/privoxy/default.filter")){
		echo "Missing /etc/privoxy/default.filter file\n";
		echo "Please Restart....\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	
	
	@chmod("/usr/share/artica-postfix/bin/privoxy-blocklist.sh", 0755);
	@chown("/etc/privoxy/default.filter", "squid");
	@chgrp("/etc/privoxy/default.filter", "squid");
	
	@chown("/etc/privoxy/default.action", "squid");
	@chgrp("/etc/privoxy/default.action", "squid");
	
	@chown("/etc/privoxy/user.action", "squid");
	@chgrp("/etc/privoxy/user.action", "squid");
	
	
	if(!is_file("/etc/privoxy/user.filter")){
		@touch("/etc/privoxy/user.filter");
		@chown("/etc/privoxy/user.filter", "squid");
		@chgrp("/etc/privoxy/user.filter", "squid");
	}
	
	
	$easy[]="URLS=(";
	$easy[]="\"https://easylist-downloads.adblockplus.org/malwaredomains_full.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/fanboy-social.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easyprivacy.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easylist.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easylistdutch.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easylistdutch+easylist.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/liste_fr.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easylistchina.txt\"";
	$easy[]="\"https://easylist-downloads.adblockplus.org/easylistitaly.txt\"";
	$easy[]=")";
	$easy[]="";
	$easy[]="# config for privoxy initscript providing PRIVOXY_CONF, PRIVOXY_USER and PRIVOXY_GROUP";
	$easy[]="INIT_CONF=\"/etc/conf.d/privoxy\"";
	$easy[]="";
	$easy[]="# !! if the config above doesn't exist set these variables here !!";
	$easy[]="# !! These values will be overwritten by INIT_CONF !!";
	$easy[]="PRIVOXY_USER=\"squid\"";
	$easy[]="PRIVOXY_GROUP=\"squid\"";
	$easy[]="PRIVOXY_CONF=\"/etc/privoxy/privoxy.conf\"";
	$easy[]="";
	$easy[]="# name for lock file (default: script name)";
	$easy[]="TMPNAME=\"$(basename \${0})\"";
	$easy[]="# directory for temporary files";
	$easy[]="TMPDIR=\"/tmp/\${TMPNAME}\"";
	$easy[]="";
	$easy[]="# Debug-level";
	$easy[]="#   -1 = quiet";
	$easy[]="#    0 = normal";
	$easy[]="#    1 = verbose";
	$easy[]="#    2 = more verbose (debugging)";
	$easy[]="#    3 = incredibly loud (function debugging)";
	$easy[]="DBG=0";
	@file_put_contents("/etc/privoxy/blocklists.conf", @implode("\n", $easy));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/privoxy/blocklists.conf done\n";}
	
	
$actions="\n{ \
+change-x-forwarded-for{block} \
+client-header-tagger{css-requests} \
+client-header-tagger{image-requests} \
+hide-from-header{block} \
+set-image-blocker{pattern} \
}
/ # Match all URLs\n
";
	@file_put_contents("/etc/privoxy/match-all.action", $actions);
	@chown("/etc/privoxy/match-all.action", "squid");
	@chgrp("/etc/privoxy/match-all.action", "squid");
	
	@mkdir("/etc/privoxy",0755,true);
	@file_put_contents("/etc/privoxy/privoxy.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/privoxy/privoxy.conf done\n";}
	
	

	
	
	InSquid();
	return true;
}

function template(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress_template("{building} {error_page}",15);
	
	shell_exec("$php /usr/share/artica-postfix/exec.squid.templates.php --single ERR_ADS_BLOCK");
	$templateDestination="/usr/share/squid-langpack/templates/ERR_ADS_BLOCK";
	@unlink("/home/privoxy/blocked");
	@mkdir("/home/privoxy",0755,true);
	build_progress_template("{copy} {error_page}",50);
	@copy($templateDestination, "/home/privoxy/blocked");
	
	$content=@file_get_contents($templateDestination);
	$content_no_such_domain=str_replace("@block-reason@","The domain name <b>@host@</b> could not be resolved",$content);
	$content_connect_failed=str_replace("@block-reason@","Connection to <b>@host@</b> (@host-ip@) could not be established",$content);
	
	
	
	@file_put_contents("/home/privoxy/no-such-domain",$content_no_such_domain);
	@file_put_contents("/home/privoxy/connect-failed",$content_connect_failed);
	@copy($templateDestination, "/home/privoxy/no-server-data");
	build_progress_template("{error_page} {done}",100);
}


function InSquid($reconfigure_squid=false){
	$unix=new unix();
	$sock=new sockets();
	$ipClass=new IP();
	$q=new mysql_squid_builder();
	
	$acls=new squid_acls();
	$acls->clean_dstdomains();
	
	build_progress_squidr("{checking} {whitelist}",30);
	
	$sql="CREATE TABLE IF NOT EXISTS `privoxy_whitelist` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	
	$results=$q->QUERY_SQL("SELECT * FROM privoxy_whitelist");
	
	$ACLS=array();
	$ACLS["IPS"]=array();
	$ACLS["DOMS"]=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$items=trim(strtolower($ligne["items"]));
		if($ipClass->isIPAddressOrRange($items)){
			$ACLS["IPS"][$items]=$items;
			
		}
		$ACLS["DOMS"][$items]=$items;
		
		
	}
	
	$ipacls=array();
	$ACLS["DOMS"]["apple.com"]="apple.com";
	$ACLS["DOMS"]["windowsupdate.com"]="windowsupdate.com";
	$ACLS["DOMS"]["googleapis.com"]="googleapis.com";
	$ACLS["DOMS"]["mozilla.net"]="mozilla.net";
	$ACLS["DOMS"]["teamviewer.com"]="teamviewer.com";
	$ACLS["DOMS"]["microsoft.com"]="microsoft.com";
	$ACLS["DOMS"]["artica.fr"]="artica.fr";
	
	if(count($ACLS["IPS"])>0){
		while (list ($num, $line) = each ($ACLS["IPS"])){$ipacls[]=$line;}
	}
	
	if(count($ACLS["DOMS"])>0){
		while (list ($num, $line) = each ($ACLS["DOMS"])){$domacls[]=$line;}
	}
	
	if(count($domacls)>0){
		$domacls=$acls->clean_dstdomains($domacls);
	}
	
	
	$PrivoxyPort=intval($sock->GET_INFO("PrivoxyPort"));
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	$privoxyInSquid=false;
	while (list ($num, $line) = each ($f)){
		if(preg_match("#include.*?privoxy\.conf#", $line)){
			$privoxyInSquid=true;
			break;
		}
	}
	
	$InSquid[]="acl AntiAdsPost method POST";
	if(count($domacls)>0){
		@file_put_contents("/etc/squid3/AntiAdsDenyWeb.acl", @implode("\n", $domacls));
		$InSquid[]="acl AntiAdsDenyWeb dstdomain \"/etc/squid3/AntiAdsDenyWeb.acl\"";
	}
	if(count($ipacls)>0){
		@file_put_contents("/etc/squid3/AntiAdsDenyIP.acl", @implode("\n", $ipacls));
		$InSquid[]="acl AntiAdsDenyIP dst \"/etc/squid3/AntiAdsDenyIP.acl\"";
	}
	$InSquid[]="cache_peer 127.0.0.1 parent $PrivoxyPort 7 no-query no-digest no-netdb-exchange name=AntiAds";
	$InSquid[]="always_direct allow FTP";
	
	if(count($ipacls)>0){
		$InSquid[]="cache_peer_access AntiAds deny AntiAdsDenyIP";
	}
	
	if(count($domacls)>0){
		$InSquid[]="cache_peer_access AntiAds deny AntiAdsDenyWeb";
	}
	
	$InSquid[]="cache_peer_access AntiAds deny AntiAdsPost";
	$InSquid[]="cache_peer_access AntiAds allow all";
	
	@file_put_contents("/etc/squid3/privoxy.conf", @implode("\n", $InSquid));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/squid3/privoxy.conf done\n";}
	
	build_progress_squidr("{reconfiguring}",50);
	
	if($privoxyInSquid==false){
		$php=$unix->LOCATE_PHP5_BIN();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reconfiguring Squid-cache\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if($reconfigure_squid){
		build_progress_squidr("{reloading}",90);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	}
	
	build_progress_squidr("{done}",100);
	
}


function Artica_pattern(){
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	
	$q=new mysql();
	$f[]="{ -block{Artica} -filter{Artica} }";
	$f[]=".articatech.net";
	
	$f[]=".artica.fr";
	$f[]=".privoxy.org";
	$f[]=".wdrmaus.de";
	$f[]=".die-maus.de";
	$f[]=".hirnwindungen.de";
	$f[]=".mathe-spass.de";
	$f[]=".learnetix.de";
	$f[]=".lerntux.de";
	$f[]=".wikipedia.org";
	$f[]=".wikimedia.org";
	$f[]=".fragfinn.de";
	$f[]=".geolino.de";
	$f[]=".geo.de";
	$f[]=".blinde-kuh.de";
	$f[]=".br-online.de";
	$f[]=".derkleinekoenig.de";
	$f[]=".kika.de";
	$f[]=".kindersache.de";
	$f[]=".kindernetz.de";
	$f[]=".seitenstark.de";
	$f[]=".rbb-online.de";
	$f[]=".kidsweb.de";
	$f[]=".bmu-kids.de";
	$f[]=".br-online.de";
	$f[]=".helles-koepfchen.de";
	$f[]=".kidsville.de";
	$f[]=".legakids.net";
	$f[]=".lilipuz.de";
	$f[]=".milkmoon.de";
	$f[]=".pixelkids.de";
	$f[]=".pomki.de";
	$f[]=".labbe.de";
	$f[]=".hamsterkiste.de";
	$f[]=".physikfuerkids.de";
	$f[]=".sowieso.de";
	$f[]=".hanisauland.de";
	$f[]=".rossipotti.de";
	$f[]=".wasistwas.de";
	$f[]=".wolf-kinderclub.de";
	$f[]=".kidnetting.de";
	$f[]=".radio108komma8.de";
	$f[]=".klasse-wasser.de";
	$f[]=".oekolandbau.de";
	$f[]=".news4kids.de";
	$f[]=".primolo.de";
	$f[]=".starke-pfoten.de";
	$f[]=".internet-abc.de";
	$f[]=".notenmax.de";
	$f[]=".lucylehmann.de";
	$f[]=".kidkit.de";
	$f[]=".junge-klassik.de";
	$f[]=".medizin-fuer-kids.de";
	$f[]=".global-gang.de";
	$f[]=".klickerkids.de";
	$f[]=".kinderrathaus.de";
	$f[]=".bayerische.staatsoper.de";
	$f[]=".zum.de";
	$f[]=".mechant-loup.schule.de";
	$f[]=".prinzessin-knoepfchen.de";
	$f[]=".1000-maerchen.de";
	$f[]=".creativecommons.org";
	$f[]=".toggo.de";
	$f[]=".toggolino.de";
	
	$results=$q->QUERY_SQL("SELECT * FROM urlrewriteaccessdeny","artica_backup");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$items=$ligne["items"];
		if(substr($items, 0,1)=="^"){$items=substr($items,1,strlen($items));}
		if(substr($items, 0,1)<>"."){$items=".$items";}
		$f[]=$items;
	}
	
	
	
	if($EnableArticaMetaClient==0){
		include_once(dirname(__FILE__).'/ressources/class.mysql-meta.inc');
		$q=new mysql_meta();
	
		$results=$q->QUERY_SQL("SELECT * FROM squid_whitelists ORDER BY `pattern`");
		while ($ligne = mysql_fetch_assoc($results)) {
			$items=$ligne["pattern"];
			if(substr($items, 0,1)=="^"){$items=substr($items,1,strlen($items));}
			if(substr($items, 0,1)<>"."){$items=".$items";}
			$f[]=$items;
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ". count($f)." Whitelisted item(s)\n";}
	
	
	$f[]="{ +block{Artica} }";
	$f[]="/piwik/piwik\.php\?action_name=";
	$f[]=".f1g\.fr/media/ext";
	@file_put_contents("/etc/privoxy/artica.action", @implode("\n", $f));
}
