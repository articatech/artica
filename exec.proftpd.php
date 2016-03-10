<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["TITLENAME"]="ProFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--chowndirs"){$GLOBALS["OUTPUT"]=true;CreateChownDirs();die();}




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
	build_progress("{stopping_service}",10);
	stop(true);
	build_progress("{reconfiguring}",40);
	build();
	sleep(1);
	build_progress("{starting_service}",45);
	if(!start(true)){
		build_progress("{starting_service} {failed}",110);
		return;
	}
	
	system("/etc/init.d/monit restart");
	
	build_progress("{starting_service} {success}",100);
}

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo $text."\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/proftpd.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("proftpd");

	if(!is_file($Masterbin)){
		build_progress("{starting_service} not installed",45);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, proftpd not installed\n";}
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
		return true;
	}
	$EnableProFTPD=intval($sock->GET_INFO("EnableProFTPD"));
	
	

	

	if($EnableProFTPD==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableProFTPD)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	
	$f[]="$Masterbin --config /etc/proftpd/proftpd.conf";
	if(!is_file("/etc/proftpd/proftpd.conf")){build();}
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build_progress("{starting_service}",50);
	shell_exec($cmd);
	
	
	
	$pr=80;
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pr=$pr+2;
		$pid=PID_NUM();
		build_progress("{starting_service} $i/5",$pr);
		if($unix->process_exists($pid)){break;}
	}

	build_progress("{starting_service}",90);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
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
	build_progress("{stopping_service}",20);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		build_progress("{stopping_service} $i/5",25);
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}
	build_progress("{stopping_service}",30);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		build_progress("{stopping_service} $i/5",35);
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/proftpd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("proftpd");
	return $unix->PIDOF($Masterbin);
	
}

function build(){
	
	$unix=new unix();
	$sock=new sockets();
	$apache=$unix->APACHE_SRC_ACCOUNT();
	$apachegrp=$unix->APACHE_SRC_GROUP();
	$VSFTPDPort=intval($sock->GET_INFO("VSFTPDPort"));
	if($VSFTPDPort==0){$VSFTPDPort=21;}
	$VsFTPDPassive=$sock->GET_INFO("VsFTPDPassive");
	$VsFTPDFileOpenMode=$sock->GET_INFO("VsFTPDFileOpenMode");
	$VsFTPDLocalUmask=$sock->GET_INFO("VsFTPDLocalUmask");
	$ProFTPDRootLogin=intval($sock->GET_INFO("ProFTPDRootLogin"));
	
	if(!is_numeric($VsFTPDPassive)){$VsFTPDPassive=1;}
	if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
	if($VsFTPDLocalUmask==null){$VsFTPDLocalUmask="077";}
	if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
	$VsFTPDLocalMaxRate=intval($sock->GET_INFO("VsFTPDLocalMaxRate"));
	
	$f[]="ServerName		\"FTP server $unix->hostname_g()\"";
	$f[]="ServerType		standalone";
	$f[]="DefaultServer		on";
	$f[]="Port				$VSFTPDPort";
	$f[]="UseIPv6			off";
	$f[]="Umask				$VsFTPDFileOpenMode $VsFTPDLocalUmask";
	$f[]="PidFile			/var/run/proftpd.pid";
	$f[]="MaxInstances		30";
	$f[]="User				$apache";
	$f[]="Group				$apachegrp";
	if($ProFTPDRootLogin==1){
		$f[]="RootLogin	on";
	}else{
		$f[]="RootLogin	off";
	}
	$f[]="RequireValidShell	off";
	$f[]="DefaultRoot 		~";
	$f[]="AllowOverwrite	on";
	$f[]="IdentLookups     	off";
	$f[]="UseReverseDNS    	off";
	$f[]="LogFormat         default \"%h %l %u %t \\\"%r\\\" %s %b\"";
	$f[]="LogFormat			auth    \"%v [%P] %h %t \\\"%r\\\" %s\"";
	$f[]="LogFormat			write   \"%h %l %u %t \\\"%r\\\" %s %b\"";
	$f[]="SystemLog 		/var/log/proftpd.log";
	$f[]="TransferLog 		/var/log/xferlog";
	$f[]="LoadModule 		mod_quotatab.c";
	$f[]="LoadModule 		mod_quotatab_sql.c";
	$f[]="";
	$f[]="LoadModule 		mod_sql.c";
	$f[]="LoadModule 		mod_sql_mysql.c";
	$f[]="";
	$f[]="LoadModule 		mod_ldap.c";
	$f[]="";
	$f[]="AuthOrder			AuthOrder mod_sql.c mod_ldap.c";
	$f[]="";
	if($VsFTPDPassive==1){
		$pasv_min_port=intval($sock->GET_INFO("VsFTPDPassiveMinPort"));
		$pasv_max_port=intval($sock->GET_INFO("VsFTPDPassiveMaxPort"));
		if($pasv_min_port==0){$pasv_min_port=40000;}
		if($pasv_max_port==0){$pasv_max_port=40200;}
		$f[]="PassivePorts $pasv_min_port $pasv_max_port";
		$VsFTPDPassiveAddr=$sock->GET_INFO("VsFTPDPassiveAddr");
		if($VsFTPDPassiveAddr<>null){
			$f[]="MasqueradeAddress $VsFTPDPassiveAddr";
		}
	}
	if($VsFTPDLocalMaxRate>0){
		
		if(strpos($VsFTPDLocalMaxRate, ".")==0){$VsFTPDLocalMaxRate="{$VsFTPDLocalMaxRate}.0";}
		$f[]="TransferRate RETR $VsFTPDLocalMaxRate";
		$f[]="TransferRate STOR $VsFTPDLocalMaxRate";
	}
	
	$f[]="";
	$f[]="# Bar use of SITE CHMOD by default";
	$f[]="<Limit SITE_CHMOD>";
	$f[]="  DenyAll";
	$f[]="</Limit>";
	$f[]="";
	$f[]="# A basic anonymous configuration, no upload directories.  If you do not";
	$f[]="# want anonymous users, simply delete this entire <Anonymous> section.";
	$f[]="<Anonymous ~ftp>";
	$f[]="  User				ftp";
	$f[]="  Group				ftp";
	$f[]="";
	$f[]="  # We want clients to be able to login with \"anonymous\" as well as \"ftp\"";
	$f[]="  UserAlias			anonymous ftp";
	$f[]="";
	$f[]="  # Limit the maximum number of anonymous logins";
	$f[]="  MaxClients			10";
	$f[]="";
	$f[]="  # We want 'welcome.msg' displayed at login, and '.message' displayed";
	$f[]="  # in each newly chdired directory.";
	$f[]="  DisplayLogin			welcome.msg";
	$f[]="  DisplayChdir			.message";
	$f[]="";
	$f[]="  # Limit WRITE everywhere in the anonymous chroot";
	$f[]="  <Limit WRITE>";
	$f[]="    DenyAll";
	$f[]="  </Limit>";
	$f[]="</Anonymous>";
	$f[]="";
	
	
	
	$ldap=new clladp();
	$f[]="<IfModule mod_ldap.c>";
	$f[]="\tLDAPBindDN		cn=$ldap->ldap_admin,$ldap->suffix $ldap->ldap_password";
	$f[]="\tLDAPServer		\"$ldap->ldap_host:$ldap->ldap_port\"";
	$f[]="\tLDAPUseTLS		off";
	$f[]="\tLDAPUsers		$ldap->suffix (uid=%u)";
	$f[]="\tLDAPGroups		$ldap->suffix";
	$f[]="\tLDAPAuthBinds	on";
	$f[]="#\tLDAPLog 		/var/log/proftpd.ldap.log";
	$f[]="</IfModule>";
	
	$f[]="PersistentPasswd    off";
	$f[]="AuthPAM             off";
	
	
	$q=new mysql();
	if($q->mysql_server==null){$q->mysql_server="127.0.0.1";}
	if($q->mysql_server=="localhost"){$q->mysql_server="127.0.0.1";}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL server:$q->mysql_server\n";} 
	$f[]="<IfModule mod_sql.c>";
	$f[]="\tSQLAuthTypes Plaintext";
	$f[]="\tSQLBackend            mysql";
	$f[]="\tSQLConnectInfo artica_backup@$q->mysql_server  $q->mysql_admin \"$q->mysql_password\"";
	$f[]="\tSQLUserInfo ftpuser userid passwd uid gid homedir shell";
	//$f[]="\tSQLUserWhereClause \"LoginAllowed = 'true'\"";
	$f[]="\tSQLGroupInfo ftpgroup groupname gid members";
	$f[]="\tCreateHome off";
	$f[]="\tSQLLog PASS updatecount";
	$f[]="\tSQLNamedQuery updatecount UPDATE \"count=count+1, accessed=now() WHERE userid='%u'\" ftpuser";
	$f[]="\tSQLLog STOR,RETR modified";
	$f[]="\tSQLNamedQuery modified UPDATE \"modified=now() WHERE userid='%u'\" ftpuser";
	$f[]="\tQuotaEngine off";
	$f[]="\tQuotaDirectoryTally off";
	$f[]="\tQuotaDisplayUnits Mb";
	$f[]="\tQuotaShowQuotas on";
	$f[]="\tSQLMinUserUID 0";
	$f[]="\tSQLMinUserGID 0";
	$f[]="\tSQLNamedQuery get-quota-limit SELECT \"name, quota_type, par_session, limit_type, bytes_up_limit, bytes_down_limit, bytes_transfer_limit, files_up_limit, files_down_limit, files_transfer_limit FROM ftpquotalimits WHERE name = '%{0}' AND quota_type = '%{1}'\"";
	$f[]="\tSQLNamedQuery get-quota-tally SELECT \"name, quota_type, bytes_up_total, bytes_down_total, bytes_transfer_total, files_up_total, files_down_total, files_transfer_total FROM ftpquotatotal WHERE name = '%{0}' AND quota_type = '%{1}'\"";
	$f[]="\tSQLNamedQuery update-quota-tally UPDATE \"bytes_up_total = bytes_up_total + %{0}, bytes_down_total = bytes_down_total + %{1}, bytes_transfer_total = bytes_transfer_total + %{2}, files_up_total = files_up_total + %{3}, files_down_total = files_down_total + %{4}, files_transfer_total = files_transfer_total + %{5} WHERE name = '%{6}' AND quota_type = '%{7}'\" ftpquotatotal";
	$f[]="\tSQLNamedQuery insert-quota-tally INSERT \"%{0}, %{1}, %{2}, %{3}, %{4}, %{5}, %{6}, %{7}\" ftpquotatotal";
	$f[]="\tQuotaLimitTable sql:/get-quota-limit";
	$f[]="\tQuotaTallyTable sql:/get-quota-tally/update-quota-tally/insert-quota-tally";
	$f[]="#\tSQLLogFile /var/log/proftpd.mysql.log";
	$f[]="</IfModule>";
	$f[]="";
	
	@mkdir("/etc/proftpd",0755,true);
	@unlink("/etc/proftpd/proftpd.conf");
	@file_put_contents("/etc/proftpd/proftpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/proftpd/proftpd.conf done\n";}
}

function CreateChownDirs(){
	$q=new mysql();
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$sql="SELECT * FROM ftpuser";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$homedir=$ligne["homedir"];
		$zuid="{$ligne["uid"]}:{$ligne["gid"]}";
		@mkdir($homedir,0755,true);
		system("$chown $zuid $homedir");
	}
	
	
	

}

?>