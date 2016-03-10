<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include(dirname(__FILE__).'/ressources/class.amavis.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	
	if($argv[1]=="--tmpfs-create"){tmpfsDirCreate();exit(0);}
	if($argv[1]=="--tmpfs-destroy"){mimedefang_tmpfs_umount();exit(0);}
	if($argv[1]=="--init"){initScriptDebian();die();}
	if($argv[1]=="--compile-progress"){compile_progress();}
	

start();	
	
	
function start(){
	initScriptDebian();	
	etc_default();
	ParseMimeDefangFilter();	
}

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/mimedefang.progress",0755);
	sleep(1);

}

function compile_progress(){
	
	
	$unix=new unix();
	build_progress(10,"{build_init_script}");
	initScriptDebian();
	etc_default();
	build_progress(15,"{configure}");
	ParseMimeDefangFilter();
	
	build_progress(50,"{restarting_artica_status}");
	system("/etc/init.d/artica-status restart --force");
	
	build_progress(50,"{stopping_service}");
	system("/etc/init.d/mimedefang stop");
	build_progress(80,"{starting_service}");
	system("/etc/init.d/mimedefang start");
	build_progress(90,"{reconfigure_mta}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
	build_progress(100,"{done}");
}


function ParseMimeDefangFilter(){
	$q=new mysql();
	$sock=new sockets();
	if(!$q->TestingConnection()){echo "Starting mimedefang: testing connection to MySQL failed, aborting configuration task...\n";return;}
	$ldap=new clladp();
	$tr=array();
	$localdomains=$ldap->hash_get_all_domains();
	$disclaimers_rules=disclaimers_rules();
	$autocompress_rules=autocompress_rules();
	$filehosting_rules=filehosting_rules();
	if($autocompress_rules<>null){$EnableAutocompress=1;}else{$EnableAutocompress=0;}
	if($filehosting_rules<>null){$EnableFileHosting=1;}else{$EnableFileHosting=0;}
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));
	if(!is_numeric($Param["DEBUG"])){$Param["DEBUG"]=0;}
	
	$MySQL_server=$q->mysql_server;
	$MySQL_user=$q->mysql_admin;
	$MySQL_password=$q->mysql_password;
	$MySQL_port=intval($q->mysql_port);
	$main=new main_cf();
	@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangPostFixNetwork",@implode("\n",$main->array_mynetworks));
	
	if($MySQL_server==null){$MySQL_server="127.0.0.1";}
	if($MySQL_server=="localhost"){$MySQL_server="127.0.0.1";}
	if($MySQL_server=="127.0.0.1"){$MySQL_server=null;}
	
	if($MySQL_server==null){
		$DSN="DBI:mysql:database=artica_backup;mysql_socket=/var/run/mysqld/mysqld.sock";
	}else{
		$DSN="DBI:mysql:database=artica_backup;host=$MySQL_server;port=$MySQL_port";
	}
	$sock->SET_INFO("MySQLPerlDSN", $DSN);
	$sock->SET_INFO("MySQLPerlUsername", $MySQL_user);
	$sock->SET_INFO("MySQLPerlPassword", $MySQL_password);
	
	$TMPDIR="/var/spool/MIMEDefang";
	if(!is_dir($TMPDIR)){@mkdir($TMPDIR,0755,true);}
	if(!is_dir("$TMPDIR/BACKUP")){@mkdir("$TMPDIR/BACKUP",0755,true);}
	@chown($TMPDIR, "postfix");
	@chgrp($TMPDIR, "postfix");
	@chown("$TMPDIR/BACKUP", "postfix");
	@chgrp("$TMPDIR/BACKUP", "postfix");
	
	$QuarantineDir="/var/spool/MD-Quarantine";
	if(!is_dir($QuarantineDir)){@mkdir($QuarantineDir,0755,true);}
	@chown($QuarantineDir, "postfix");
	@chgrp($QuarantineDir, "postfix");
	
	
	$TMPDIR_CAPTURED="/var/spool/MIMEDefang_replaced";
	if(!is_dir($TMPDIR_CAPTURED)){@mkdir($TMPDIR_CAPTURED,0755,true);}
	@chown($TMPDIR_CAPTURED, "postfix");
	@chgrp($TMPDIR_CAPTURED, "postfix");	
	
	@copy("/usr/share/artica-postfix/bin/install/mimedefang/mimedefang-filter.pl", "/etc/mail/mimedefang-filter");
	while (list ($dim, $line) = each ($localdomains) ){$tr[]="'$dim'=>1";}
	if(count($tr)>0){$hashlocaldomains=@implode(",", $tr);}
	
	$MimeDefangFileHostingSubjectPrepend=$sock->GET_INFO("MimeDefangFileHostingSubjectPrepend");
	$MimeDefangFileHostingSubjectPrepend=str_replace('"', '\"', $MimeDefangFileHostingSubjectPrepend);
	
	$MimeDefangFileHostingLink=$sock->GET_INFO("MimeDefangFileHostingLink");
	$MimeDefangFileHostingText=$sock->GET_INFO("MimeDefangFileHostingText");
	$MimeDefangFileHostingExternMySQL=$sock->GET_INFO("MimeDefangFileHostingExternMySQL");
	$MimeDefangFileHostingMySQLsrv=$sock->GET_INFO("MimeDefangFileHostingMySQLsrv");
	$MimeDefangFileHostingMySQLusr=$sock->GET_INFO("MimeDefangFileHostingMySQLusr");
	$MimeDefangFileHostingMySQLPass=$sock->GET_INFO("MimeDefangFileHostingMySQLPass");
	$MimeDefangFileMaxDaysStore=$sock->GET_INFO("MimeDefangFileMaxDaysStore");
	if(!is_numeric($MimeDefangFileMaxDaysStore)){$MimeDefangFileMaxDaysStore=5;}
	
	
	$MimeDefangFileHostingText=stripslashes($MimeDefangFileHostingText);
	
	if($MimeDefangFileHostingText==null){
		$MimeDefangFileHostingText="The %s file exceed the company's messaging rules.\nIt has been moved to our Web server.\nYou can download it by clicking on the link bellow.";
	}
	$MimeDefangFileHostingText=str_replace('"', '\"', $MimeDefangFileHostingText);
	$MimeDefangFileHostingText=str_replace("\n", "\\n", $MimeDefangFileHostingText);
	
	$tb=explode("\n",@file_get_contents("/etc/mail/mimedefang-filter"));
	while (list ($index, $line) = each ($tb) ){
		if(preg_match('#my\s+\$dsn#', $line)){
			$tb[$index]="	my \$dsn = 'DBI:mysql:artica_backup:$q->mysql_server';";
			continue;
		}
		
		if(preg_match("#my\s+\\\$db_user_name#", $line)){
			$tb[$index]="	my \$db_user_name = '$q->mysql_admin';";
			continue;
		}
		
		if(preg_match("#my\s+\\\$MimeDefangFileHostingSubjectPrepend#", $line)){
			$tb[$index]="	my \$MimeDefangFileHostingSubjectPrepend = \"$MimeDefangFileHostingSubjectPrepend\";";
			continue;
		}	

		if(preg_match("#my\s+\\\$MimeDefangFileHostingLink#", $line)){
			$tb[$index]="	my \$MimeDefangFileHostingLink = \"$MimeDefangFileHostingLink\";";
			continue;
		}	

		if(preg_match("#my\s+\\\$MimeDefangFileMaxDaysStore#", $line)){
			$tb[$index]="	my \$MimeDefangFileMaxDaysStore = \"$MimeDefangFileMaxDaysStore\";";
			continue;
		}	
		if(preg_match("#my\s+\\\$MimeDefangFileHostingText#", $line)){
			$tb[$index]="	my \$MimeDefangFileHostingText = \"$MimeDefangFileHostingText\";";
			continue;
		}			
				
		
		if(preg_match("#my\s+\\\$DebugMimeFilter#", $line)){
			$tb[$index]="	my \$DebugMimeFilter = {$Param["DEBUG"]};";
			continue;
		}
		
		if(preg_match("#my\s+\\\$db_password#", $line)){
			$tb[$index]="	my \$db_password = '$q->mysql_password';";
			continue;
		}		
		if(preg_match("#my\s+\\\$EnableCompression#", $line)){
			$tb[$index]="	my \$EnableCompression = $EnableAutocompress;";
			continue;
		}	
		if(preg_match("#my\s+\\\$EnableFileHosting#", $line)){
			$tb[$index]="	my \$EnableFileHosting = $EnableFileHosting;";
			continue;
		}	
		
				
		if(preg_match("#my\s+%hashLocalDomains#", $line)){
			$tb[$index]="	my %hashLocalDomains = ($hashlocaldomains);";
			continue;
		}
		
		if(preg_match("#my\s+%hashCompressesRules#", $line)){
			$tb[$index]="	my %hashCompressesRules = ($autocompress_rules);";
			continue;
		}

		if(preg_match("#my\s+%hashFileHostingRules#", $line)){
			$tb[$index]="	my %hashFileHostingRules = ($filehosting_rules);";
			continue;
		}		
		
		if(preg_match("#my\s+%hashDisclaimers#", $line)){
			$tb[$index]="	my %hashDisclaimers = ($disclaimers_rules);";
			continue;
		}		
	}
	echo "Starting mimedefang: saving /etc/mail/mimedefang-filter done\n";
	@file_put_contents("/etc/mail/mimedefang-filter", @implode("\n", $tb));
	
	
}

function disclaimers_rules(){
	$t=array();
	$sql="SELECT * FROM mimedefang_disclaimer";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	@mkdir("/var/spool/MD-disclaimers",0755,true);
	@chown("/var/spool/MD-disclaimers", "postfix");
	@chgrp("/var/spool/MD-disclaimers", "postfix");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern="{$ligne["mailfrom"]}{$ligne["mailto"]}";
		$t[]="'$pattern' => '{$ligne["zmd5"]}'";
		@file_put_contents("/var/spool/MD-disclaimers/{$ligne["zmd5"]}.txt", $ligne["textcontent"]);
		@chown("/var/spool/MD-disclaimers/{$ligne["zmd5"]}.txt", "postfix");
		@chgrp("/var/spool/MD-disclaimers/{$ligne["zmd5"]}.txt", "postfix");
		@file_put_contents("/var/spool/MD-disclaimers/{$ligne["zmd5"]}.html", $ligne["htmlcontent"]);
		
	}
	echo "Starting mimedefang: saving ". count($t)." disclaimers done\n";
	return @implode(",", $t);
	
}
function autocompress_rules(){
	$t=array();
	$sql="SELECT * FROM mimedefang_autocompress WHERE uncompress=0";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern="{$ligne["mailfrom"]}{$ligne["mailto"]}";
		$t[]="'$pattern' => {$ligne["maxsize"]}";
	}
	echo "Starting mimedefang: saving ". count($t)." auto-compress rule(s) done\n";
	if(count($t)>0){
		return @implode(",", $t);
	}
	
}
function filehosting_rules(){
	$t=array();
	$sql="SELECT * FROM mimedefang_filehosting";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern="{$ligne["mailfrom"]}{$ligne["mailto"]}";
		$t[]="'$pattern' => {$ligne["maxsize"]}";
	}
	echo "Starting mimedefang: saving ". count($t)." filehosting rule(s) done\n";
	if(count($t)>0){
		return @implode(",", $t);
	}
	
}
function tmpfsDirCreate(){
	$sock=new sockets();
	$MimeDefangEnabled=$sock->GET_INFO("MimeDefangEnabled");
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}	
	if($MimeDefangEnabled==0){
		echo "Starting mimedefang: is disabled...\n";
		mimedefang_tmpfs_umount();
		return;
	}
	
	
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));	
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}	
	if($Param["MX_TMPFS"]<5){
		echo "Starting mimedefang: tmpfs is disabled...\n";
		mimedefang_tmpfs_umount();
		return;		
	}
	
	mimedefang_tmpfs_mount($Param["MX_TMPFS"]);
	
	
}
function mimedefang_tmpfs_mount($size){
	$sock=new sockets();
	$unix=new unix();
	$TMPDIR="/var/spool/MIMEDefang";
	if(!is_dir($TMPDIR)){@mkdir($TMPDIR,0755,true);}
	@chown($TMPDIR, "postfix");
	@chgrp($TMPDIR, "postfix");	
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	
	if(strlen($idbin)<3){echo "Starting mimedefang: tmpfs `id` no such binary\n";return;}
	if(strlen($mount)<3){echo "Starting mimedefang: tmpfs `mount` no such binary\n";return;}
	exec("$idbin postfix 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}
	$uid=$re[1];
	$gid=$re[2];
	echo "Starting mimedefang: tmpfs uid/gid =$uid:$gid for {$size}M\n";
	mimedefang_tmpfs_umount();
	shell_exec("$rm -rf $TMPDIR/* >/dev/null 2>&1");

	$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$size}M,nr_inodes=10k,mode=0700 tmpfs \"$TMPDIR\"";
	shell_exec($cmd);
	$mounted=mysql_tmpfs_ismounted($uid);
	if(mysql_tmpfs_ismounted()){
		echo "Starting mimedefang: $TMPDIR(tmpfs) for {$size}M success\n";	
		
	}else{
		echo "Starting mimedefang: tmpfs for {$size}M failed, it will return back to disk\n";
	}
}


function mimedefang_tmpfs_umount(){
	$TMPDIR="/var/spool/MIMEDefang";
	if(!mimedefang_tmpfs_ismounted()){return;}
	$unix=new unix();
	$umount=$unix->find_program("umount");
	
	$c=0;
	while (mimedefang_tmpfs_ismounted()) {
		echo "Starting mimedefang: umount $TMPDIR\n";
		shell_exec("$umount -l \"$TMPDIR\"");
		usleep(500);
		$c++;
		}
		if($c>20){
			echo "Starting mimedefang: umount $TMPDIR failed (timeout)\n";
			break;}
			
	if(!mimedefang_tmpfs_ismounted()){
		echo "Starting mimedefang: umounting $TMPDIR done\n"; 
	}
				
}	

function mimedefang_tmpfs_ismounted(){
	$f=file("/proc/mounts");
	while (list ($index, $ligne) = each ($f) ){
		if(!preg_match("#tmpfs\s+\/var\/spool\/MIMEDefang\s+tmpfs#", $ligne,$re)){continue;}
		return true;
	}
	
	return false;
}
	
	
	
function initScriptDebian(){
	$user=new usersMenus();
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	
	if(!is_file($debianbin)){return;}
	$sock=new sockets();
	$MimeDefangEnabled=$sock->GET_INFO("MimeDefangEnabled");
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}	
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));
	
	
	if(!is_numeric($Param["DEBUG"])){$Param["DEBUG"]=0;}
	
	$unix=new unix();
	
	
	$q=new mysql();
	$q->Check_smtp_logs_table();
	
		
	
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	if(!is_numeric($Param["DEBUG"])){$Param["DEBUG"]=0;}
	if(!is_numeric($Param["MX_REQUESTS"])){$Param["MX_REQUESTS"]=200;}
	if(!is_numeric($Param["MX_MINIMUM"])){$Param["MX_MINIMUM"]=2;}
	if(!is_numeric($Param["MX_MAXIMUM"])){$Param["MX_MAXIMUM"]=10;}
	if(!is_numeric($Param["MX_MAX_RSS"])){$Param["MX_MAX_RSS"]=30000;}
	if(!is_numeric($Param["MX_MAX_AS"])){$Param["MX_MAX_AS"]=90000;}
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}	
	
	$MX_DEBUG="no";
	

	$f[]="#!/bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          mimedefang";
	$f[]="# Required-Start:    \$remote_fs \$syslog";
	$f[]="# Required-Stop:     \$remote_fs \$syslog";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="### END INIT INFO";
	$f[]="#";
	$f[]="# FreeBSD/NetBSD start/stop script for MIMEDefang.";
	$f[]="#";
	$f[]="# PROVIDE: mimedefang";
	$f[]="# REQUIRE: LOGIN";
	$f[]="# BEFORE: mail";
	$f[]="# KEYWORD: shutdown";
	$f[]="";
	$f[]="unset HOME";
	$f[]="RETVAL=0";
	$f[]="prog='mimedefang'";
	$f[]="SPOOLDIR='/var/spool/MIMEDefang'";
	$f[]="PID=\"\$SPOOLDIR/\$prog.pid\"";
	$f[]="MIMEDEF_ENABLED=$MimeDefangEnabled";
	$f[]="MXPID=\"\$SPOOLDIR/\$prog-multiplexor.pid\"";
	$f[]="MX_DEBUG=\"$MX_DEBUG\"";
	$f[]="";
	$f[]="# These lines keep SpamAssassin happy.  Not needed if you";
	$f[]="# aren't using SpamAssassin.";
	$f[]="HOME=\"\$SPOOLDIR\"";
	$f[]="export HOME";
	$f[]="# Is the program executable?  We search in /usr/bin and /usr/local/bin.";
	$f[]="";
	$f[]="if [ -x /usr/bin/\$prog ] ; then";
	$f[]="    PROGDIR=/usr/bin";
	$f[]="elif [ -x /usr/bin/\$prog ] ; then";
	$f[]="    PROGDIR=/usr/bin";
	$f[]="elif [ -x /usr/local/bin/\$prog ] ; then";
	$f[]="    PROGDIR=/usr/local/bin";
	$f[]="else";
	$f[]="    exit 0";
	$f[]="fi";
	$f[]="";
	$f[]="# Locale should be set to \"C\" for generating valid date headers";
	$f[]="LC_ALL=C";
	$f[]="export LC_ALL";
	$f[]="";
	$f[]="SOCKET=\$SPOOLDIR/mimedefang.sock";
	$f[]="MX_USER=postfix";
	$f[]="SYSLOG_FACILITY=mail";
	$f[]="";
	$f[]="# If you want to keep spool directories around if the filter fails,";
	$f[]="# set the next one to yes";
	$f[]="# KEEP_FAILED_DIRECTORIES=no";
	$f[]="";
	$f[]="# \"yes\" turns on the multiplexor relay checking function";
	$f[]="# MX_RELAY_CHECK=no";
	$f[]="";
	$f[]="# \"yes\" turns on the multiplexor HELO checking function";
	$f[]="# MX_HELO_CHECK=no";
	$f[]="";
	$f[]="# \"yes\" turns on the multiplexor sender checking function";
	$f[]="# MX_SENDER_CHECK=no";
	$f[]="";
	$f[]="# \"yes\" turns on the multiplexor recipient checking function";
	$f[]="# MX_RECIPIENT_CHECK=no";
	$f[]="";
	$f[]="# Set to yes if you want the multiplexor to log events to syslog";
	$f[]="MX_LOG=yes";
	$f[]="";
	$f[]="# Set to yes if you want to use an embedded Perl interpreter";
	$f[]="# MX_EMBED_PERL=yes";
	$f[]="";
	$f[]="# Set to full path of socket for Sendmail's SOCKETMAP map, if you";
	$f[]="# want to use it with MIMEDefang";
	$f[]="# MX_MAP_SOCKET=\$SPOOLDIR/map.sock";
	$f[]="";
	$f[]="# The multiplexor does not start all slaves at the same time.  Instead,";
	$f[]="# it starts one slave every MX_SLAVE_DELAY seconds when the system is idle.";
	$f[]="# (If the system is busy, the multiplexor starts slaves as incoming mail";
	$f[]="# requires attention.)";
	$f[]="# MX_SLAVE_DELAY=3";
	$f[]="";
	$f[]="# The next setting is an absolute limit on slave activation.  The multiplexor";
	$f[]="# will NEVER activate a slave within MX_MIN_SLAVE_DELAY seconds of another.";
	$f[]="# The default of zero means that the multiplexor will activate slaves as";
	$f[]="# quickly as necessary to keep up with incoming mail.";
	$f[]="MX_MIN_SLAVE_DELAY=0";
	$f[]="";
	$f[]="# Set to yes if you want the multiplexor to log stats in";
	$f[]="# /var/log/mimedefang/stats  The /var/log/mimedefang directory must";
	$f[]="# exist and be writable by the user you're running MIMEDefang as.";
	$f[]="# MX_STATS=no";
	$f[]="";
	$f[]="# Number of slaves reserved for connections from loopback.  Use -1";
	$f[]="# for default behaviour, 0 to allow loopback connections to queue,";
	$f[]="# or >0 to reserve slaves for loopback connections";
	$f[]="LOOPBACK_RESERVED_CONNECTIONS=-1";
	$f[]="";
	$f[]="# If you want new connections to be allowed to queue, set the";
	$f[]="# next variable to yes.  Normally, only existing connections are";
	$f[]="# allowed to queue requests for work.";
	$f[]="ALLOW_NEW_CONNECTIONS_TO_QUEUE=no";
	$f[]="";
	$f[]="# Set to yes if you want the stats file flushed after each entry";
	$f[]="# MX_FLUSH_STATS=no";
	$f[]="";
	$f[]="# Set to yes if you want the multiplexor to log stats to syslog";
	$f[]="# MX_STATS_SYSLOG=no";
	$f[]="";
	$f[]="# The socket used by the multiplexor";
	$f[]="# MX_SOCKET=\$SPOOLDIR/mimedefang-multiplexor.sock";
	$f[]="";
	$f[]="# Maximum # of requests a process handles";
	$f[]="MX_REQUESTS={$Param["MX_REQUESTS"]}";
	$f[]="";
	$f[]="# Minimum number of processes to keep.  The default of 0 is probably";
	$f[]="# too low; we suggest 2 instead.";
	$f[]="MX_MINIMUM={$Param["MX_MINIMUM"]}";
	$f[]="";
	$f[]="# Maximum number of processes to run (mail received while this many";
	$f[]="# processes are running is rejected with a temporary failure, so be";
	$f[]="# wary of how many emails you receive at a time).  This applies only";
	$f[]="# if you DO use the multiplexor.  The default value of 2 is probably";
	$f[]="# too low; we suggest 10 instead";
	$f[]="MX_MAXIMUM={$Param["MX_MAXIMUM"]}";
	$f[]="";
	$f[]="# Uncomment to log slave status; it will be logged every";
	$f[]="# MX_LOG_SLAVE_STATUS_INTERVAL seconds";
	$f[]="# MX_LOG_SLAVE_STATUS_INTERVAL=30";
	$f[]="";
	$f[]="# Uncomment next line to have busy slaves send status updates to the";
	$f[]="# multiplexor.  NOTE: Consumes one extra file descriptor per slave, plus";
	$f[]="# a bit of CPU time.";
	$f[]="# MX_STATUS_UPDATES=yes";
	$f[]="";
	$f[]="# Limit slave processes' resident-set size to this many kilobytes.  Default";
	$f[]="# is unlimited.";
	$f[]="# MX_MAX_RSS={$Param["MX_MAX_RSS"]}";
	$f[]="";
	$f[]="# Limit total size of slave processes' memory space to this many kilobytes.";
	$f[]="# Default is unlimited.";
	$f[]="# MX_MAX_AS={$Param["MX_MAX_AS"]}";
	$f[]="";
	$f[]="# If you want to use the \"notification\" facility, set the appropriate port.";
	$f[]="# See the mimedefang-notify man page for details.";
	$f[]="# MX_NOTIFIER=inet:4567";
	$f[]="";
	$f[]="# Number of seconds a process should be idle before checking for";
	$f[]="# minimum number and killed";
	$f[]="# MX_IDLE=300";
	$f[]="";
	$f[]="# Number of seconds a process is allowed to scan an email before it is";
	$f[]="# considered dead.  The default is 30 seconds; we suggest 600.";
	$f[]="MX_BUSY=600";
	$f[]="";
	$f[]="# Maximum number of concurrent recipok requests on a per-domain basis.";
	$f[]="# 0 means no limit";
	$f[]="MX_RECIPOK_PERDOMAIN_LIMIT=0";
	$f[]="";
	$f[]="# Extra sendmail macros to pass.  Actually, you can add any extra";
	$f[]="# mimedefang options here...";
	$f[]="# MD_EXTRA=\"-X\"";
	$f[]="";
	$f[]="# Multiplexor queue size -- default is 0 (no queueing)";
	$f[]="# MX_QUEUE_SIZE=10";
	$f[]="";
	$f[]="# Multiplexor queue timeout -- default is 30 seconds";
	$f[]="# MX_QUEUE_TIMEOUT=30";
	$f[]="";
	$f[]="# Set to yes if you don't want MIMEDefang to see invalid recipients.";
	$f[]="# Only works with Sendmail 8.14.0 and later.";
	$f[]="# MD_SKIP_BAD_RCPTS=no";
	$f[]="";
	$f[]="# SUBFILTER specifies which filter rules file to use";
	$f[]="SUBFILTER=/etc/mail/mimedefang-filter";
	$f[]="";
	$f[]="# The contents of the \"X-Scanned-By\" header added to each mail.  Do";
	$f[]="# not use the quote mark (') in this setting.  Setting it to \"-\"";
	$f[]="# prevents the header from getting added at all.";
	$f[]="# X_SCANNED_BY=\"MIMEDefang N.NN (www . roaringpenguin . com / mimedefang)\"";
	$f[]="";
	$f[]="# Source configuration";
	$f[]="if [ -f /etc/default/\$prog ] ; then";
	$f[]="    . /etc/default/\$prog";
	$f[]="fi";
	$f[]="";
	$f[]="# BSD specific setup";
	$f[]="if [ -f /etc/rc.subr ]";
	$f[]="then";
	$f[]="    . /etc/rc.subr";
	$f[]="";
	$f[]="    name=\$prog";
	$f[]="    rcvar=`set_rcvar`";
	$f[]="    # default to not enabled, enable in rc.conf";
	$f[]="    eval \$rcvar=\\\${\$rcvar:-NO}";
	$f[]="";
	$f[]="    load_rc_config \$name";
	$f[]="";
	$f[]="    pidfile=\$MXPID";
	$f[]="    procname=\$PROGDIR/\$prog-multiplexor";
	$f[]="    start_cmd=\"start_it\"";
	$f[]="    stop_cmd=\"stop_it\"";
	$f[]="    sig_reload=\"INT\"";
	$f[]="    reread_cmd=\"reread_it\"";
	$f[]="    # provide both \"reload\", the FreeBSD default, with a direct signal to";
	$f[]="    # the multiplexor, and \"reread\", the MIMEDefang default, using md-mx-ctrl";
	$f[]="    extra_commands=\"reload reread\"";
	$f[]="fi";
	$f[]="";
	$f[]="# Make sure required vars are set";
	$f[]="SOCKET=\${SOCKET:=\$SPOOLDIR/\$prog.sock}";
	$f[]="MX_SOCKET=\${MX_SOCKET:=\$SPOOLDIR/\$prog-multiplexor.sock}";
	$f[]="";
	$f[]="start_it() {";
	$f[]="   if [ \$MIMEDEF_ENABLED = 0 ] ; then";
	$f[]="	    echo \"MimeDefang is disabled\"";
	$f[]="		stop_it";
	$f[]="	    return 1";
	$f[]="	fi";	
	$f[]=""; 
	$f[]="    if test -r \$PID ; then";
	$f[]="	if kill -0 `cat \$PID` > /dev/null 2>&1 ; then";
	$f[]="	    echo \"mimedefang (`cat \$PID`) seems to be running.\"";
	$f[]="	    return 1";
	$f[]="	fi";
	$f[]="    fi";
	$f[]="    if test -r \$MXPID ; then";
	$f[]="	if kill -0 `cat \$MXPID` > /dev/null 2>&1 ; then";
	$f[]="	    echo \"mimedefang-multiplexor (`cat \$MXPID`) seems to be running.\"";
	$f[]="	    return 1";
	$f[]="	fi";
	$f[]="    fi";
	$f[]="   $php5 ". __FILE__." --tmpfs-create";
	$f[]="";
	$f[]="    printf \"%-60s\" \"Starting \$prog-multiplexor: \"";
	$f[]="    rm -f \$MX_SOCKET > /dev/null 2>&1";
	$f[]="    if [ \"\$MX_EMBED_PERL\" = \"yes\" ] ; then";
	$f[]="	EMBEDFLAG=-E";
	$f[]="    else";
	$f[]="	EMBEDFLAG=\"\"";
	$f[]="    fi";
	$f[]="    \$PROGDIR/\$prog-multiplexor -p \$MXPID \\";
	$f[]="	\$EMBEDFLAG \\";
	$f[]="	`[ -n \"\$SPOOLDIR\" ] && echo \"-z \$SPOOLDIR\"` \\";
	$f[]="	`[ -n \"\$FILTER\" ] && echo \"-f \$FILTER\"` \\";
	$f[]="	`[ -n \"\$SYSLOG_FACILITY\" ] && echo \"-S \$SYSLOG_FACILITY\"` \\";
	$f[]="	`[ -n \"\$SUBFILTER\" ] && echo \"-F \$SUBFILTER\"` \\";
	$f[]="	`[ -n \"\$MX_MINIMUM\" ] && echo \"-m \$MX_MINIMUM\"` \\";
	$f[]="	`[ -n \"\$MX_MAXIMUM\" ] && echo \"-x \$MX_MAXIMUM\"` \\";
	$f[]="	`[ -n \"\$MX_MAP_SOCKET\" ] && echo \"-N \$MX_MAP_SOCKET\"` \\";
	$f[]="	`[ -n \"\$MX_LOG_SLAVE_STATUS_INTERVAL\" ] && echo \"-L \$MX_LOG_SLAVE_STATUS_INTERVAL\"` \\";
	$f[]="	`[ -n \"\$MX_USER\" ] && echo \"-U \$MX_USER\"` \\";
	$f[]="	`[ -n \"\$MX_IDLE\" ] && echo \"-i \$MX_IDLE\"` \\";
	$f[]="	`[ -n \"\$MX_BUSY\" ] && echo \"-b \$MX_BUSY\"` \\";
	$f[]="	`[ -n \"\$MX_REQUESTS\" ] && echo \"-r \$MX_REQUESTS\"` \\";
	$f[]="	`[ -n \"\$MX_SLAVE_DELAY\" ] && echo \"-w \$MX_SLAVE_DELAY\"` \\";
	$f[]="	`[ -n \"\$MX_MIN_SLAVE_DELAY\" ] && echo \"-W \$MX_MIN_SLAVE_DELAY\"` \\";
	$f[]="	`[ -n \"\$MX_MAX_RSS\" ] && echo \"-R \$MX_MAX_RSS\"` \\";
	$f[]="	`[ -n \"\$MX_MAX_AS\" ] && echo \"-M \$MX_MAX_AS\"` \\";
	$f[]="	`[ \"\$MX_LOG\" = \"yes\" ] && echo \"-l\"` \\";
	$f[]="	`[ \"\$MX_DEBUG\" = \"yes\" ] && echo \"-d\"` \\";	
	$f[]="	`[ \"\$MX_STATS\" = \"yes\" ] && echo \"-t /var/log/mimedefang/stats\"` \\";
	$f[]="	`[ \"\$MX_STATS\" = \"yes\" -a \"\$MX_FLUSH_STATS\" = \"yes\" ] && echo \"-u\"` \\";
	$f[]="	`[ \"\$MX_STATS_SYSLOG\" = \"yes\" ] && echo \"-T\"` \\";
	$f[]="	`[ \"\$MX_STATUS_UPDATES\" = \"yes\" ] && echo \"-Z\"` \\";
	$f[]="	`[ -n \"\$MX_QUEUE_SIZE\" ] && echo \"-q \$MX_QUEUE_SIZE\"` \\";
	$f[]="	`[ -n \"\$MX_QUEUE_TIMEOUT\" ] && echo \"-Q \$MX_QUEUE_TIMEOUT\"` \\";
	$f[]="	`[ -n \"\$MX_NOTIFIER\" ] && echo \"-O \$MX_NOTIFIER\"` \\";
	$f[]="	`[ -n \"\$MX_RECIPOK_PERDOMAIN_LIMIT\" ] && \"echo -y \$MX_RECIPOK_PERDOMAIN_LIMIT\"` \\";
	$f[]="	-s \$MX_SOCKET";
	$f[]="    RETVAL=\$?";
	$f[]="    if [ \$RETVAL = 0 ] ; then";
	$f[]="	echo \"[  OK  ]\"";
	$f[]="    else";
	$f[]="	echo \"[FAILED]\"";
	$f[]="	return 1";
	$f[]="    fi";
	$f[]="";
	$f[]="    # Start mimedefang \$PROGDIR";
	$f[]="    printf \"%-60s\" \"Starting \$PROGDIR/\$prog: \"";
	$f[]="    rm -f \$SOCKET > /dev/null 2>&1";
	$f[]="    \$PROGDIR/\$prog -P \$PID -R \$LOOPBACK_RESERVED_CONNECTIONS \\";
	$f[]="	-m \$MX_SOCKET \\";
	$f[]="	`[ -n \"\$SPOOLDIR\" ] && echo \"-z \$SPOOLDIR\"` \\";
	$f[]="	`[ -n \"\$MX_USER\" ] && echo \"-U \$MX_USER\"` \\";
	$f[]="	`[ -n \"\$SYSLOG_FACILITY\" ] && echo \"-S \$SYSLOG_FACILITY\"` \\";
	$f[]="	`[ \"\$MX_RELAY_CHECK\" = \"yes\" ] && echo \"-r\"` \\";
	$f[]="	`[ \"\$MX_HELO_CHECK\" = \"yes\" ] && echo \"-H\"` \\";
	$f[]="	`[ \"\$MX_DEBUG\" = \"yes\" ] && echo \"-d\"` \\";	
	$f[]="	`[ \"\$MX_SENDER_CHECK\" = \"yes\" ] && echo \"-s\"` \\";
	$f[]="	`[ \"\$MX_RECIPIENT_CHECK\" = \"yes\" ] && echo \"-t\"` \\";
	$f[]="	`[ \"\$KEEP_FAILED_DIRECTORIES\" = \"yes\" ] && echo \"-k\"` \\";
	$f[]="	`[ \"\$MD_EXTRA\" != \"\" ] && echo \$MD_EXTRA` \\";
	$f[]="	`[ \"\$MD_SKIP_BAD_RCPTS\" = \"yes\" ] && echo \"-N\"` \\";
	$f[]=" 	\"`[ -n \"\$X_SCANNED_BY\" ] && \\";
	$f[]="	        ( [ \"\$X_SCANNED_BY\" = \"-\" ] && \\";
	$f[]="		        echo \"-X\" || echo \"-x\$X_SCANNED_BY\" )`\" \\";
	$f[]="	`[ \"\$ALLOW_NEW_CONNECTIONS_TO_QUEUE\" = \"yes\" ] && echo \"-q\"` \\";
	$f[]="	-p \$SOCKET";
	$f[]="    RETVAL=\$?";
	$f[]="    if [ \$RETVAL = 0 ] ; then";
	$f[]="	echo \"[  OK  ]\"";
	$f[]="    else";
	$f[]="	echo \"[FAILED]\"";
	$f[]="	kill `cat \$MXPID`";
	$f[]="	return 1";
	$f[]="    fi";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="stop_it() {";
	$f[]="    # Stop daemon";
	$f[]="    printf \"%-60s\" \"Shutting down \$prog: \"";
	$f[]="    if test -f \"\$PID\" ; then";
	$f[]="	kill `cat \$PID`";
	$f[]="	RETVAL=\$?";
	$f[]="	# killing the parent does not work when the children are still";
	$f[]="	# running";
	$f[]="	killall \$PROGDIR/\$prog";
	$f[]="    else";
	$f[]="	RETVAL=1";
	$f[]="    fi";
	$f[]="    if [ \$RETVAL = 0 ] ; then";
	$f[]="	echo \"[  OK  ]\"";
	$f[]="    else";
	$f[]="	echo \"[FAILED]\"";
	$f[]="    fi";
	$f[]="";
	$f[]="    # Stop daemon";
	$f[]="    printf \"%-60s\" \"Shutting down \$prog-multiplexor: \"";
	$f[]="    if test -f \"\$MXPID\" ; then";
	$f[]="	kill `cat \$MXPID`";
	$f[]="	RETVAL=\$?";
	$f[]="    else";
	$f[]="	RETVAL=1";
	$f[]="    fi";
	$f[]="    if [ \$RETVAL = 0 ] ; then";
	$f[]="	echo \"[  OK  ]\"";
	$f[]="    else";
	$f[]="	echo \"[FAILED]\"";
	$f[]="    fi";
	$f[]="";
	$f[]="    rm -f \$MX_SOCKET > /dev/null 2>&1";
	$f[]="    rm -f \$SOCKET > /dev/null 2>&1";
	$f[]="";
	$f[]="    if [ \"\$1\" = \"wait\" ] ; then";
	$f[]="	printf \"Waiting for daemons to exit.\"";
	$f[]="	WAITPID=\"\"";
	$f[]="	test -f \$PID && WAITPID=`cat \$PID`";
	$f[]="	test -f \$MXPID && WAITPID=\"\$WAITPID `cat \$MXPID`\"";
	$f[]="	n=0";
	$f[]="	while [ -n \"\$WAITPID\" ] ; do";
	$f[]="	    W2=\"\"";
	$f[]="	    for pid in \$WAITPID ; do";
	$f[]="		if kill -0 \$pid > /dev/null 2>&1 ; then";
	$f[]="		    W2=\"\$W2 \$pid\"";
	$f[]="		fi";
	$f[]="	    done";
	$f[]="	    printf \".\"";
	$f[]="	    n=`expr \$n + 1`";
	$f[]="	    test \$n -eq 30 && kill -KILL \$WAITPID > /dev/null 2>&1";
	$f[]="	    test \$n -eq 60 && break";
	$f[]="	    WAITPID=\$W2";
	$f[]="	    sleep 1";
	$f[]="	done";
	$f[]="	echo \"\"";
	$f[]="    fi";
	$f[]="";
	$f[]="    rm -f \$MXPID > /dev/null 2>&1";
	$f[]="    rm -f \$PID > /dev/null 2>&1";
	$f[]="    $php5 ". __FILE__." --tmpfs-destroy";
	$f[]="}";
	$f[]="";
	$f[]="reread_it() {";
	$f[]="	if [ -x \$PROGDIR/md-mx-ctrl ] ; then";
	$f[]="	    \$PROGDIR/md-mx-ctrl -s \$MX_SOCKET reread > /dev/null 2>&1";
	$f[]="	    RETVAL=\$?";
	$f[]="	    if [ \$RETVAL = 0 ] ; then";
	$f[]="		echo \"Told \$prog-multiplexor to force reread of filter rules.\"";
	$f[]="	    else";
	$f[]="		echo \"Could not communicate with \$prog-multiplexor\"";
	$f[]="	    fi";
	$f[]="	else";
	$f[]="	    if [ -r \$MXPID ] ; then";
	$f[]="		kill -INT `cat \$MXPID`";
	$f[]="		RETVAL=\$?";
	$f[]="		if [ \$RETVAL = 0 ] ; then";
	$f[]="		    echo \"Told \$prog-multiplexor to force reread of filter rules.\"";
	$f[]="		else";
	$f[]="		    echo \"Could not signal \$prog-multiplexor\"";
	$f[]="		fi";
	$f[]="	    else";
	$f[]="		RETVAL=1";
	$f[]="		echo \"Could not find process-ID of \$prog-multiplexor\"";
	$f[]="	    fi";
	$f[]="	fi";
	$f[]="}";
	$f[]="";
	$f[]="if type run_rc_command > /dev/null 2>&1";
	$f[]="then";
	$f[]="    # NetBSD/FreeBSD compatible startup script";
	$f[]="    run_rc_command \"\$1\"";
	$f[]="    exit \$RETVAL";
	$f[]="fi";
	$f[]="";
	$f[]="# See how we were called.";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="  start_it";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="  stop_it \$2";
	$f[]="    ;;";
	$f[]="";
	$f[]="  restart|force-reload)";
	$f[]="    stop_it wait";
	$f[]="    start_it";
	$f[]="    RETVAL=\$?";
	$f[]="    ;;";
	$f[]="";
	$f[]="  reread|reload)";
	$f[]="    reread_it";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|force-reload|reread|reload}\"";
	$f[]="    exit 1";
	$f[]="esac";
	$f[]="";
	$f[]="exit \$RETVAL";
	@file_put_contents("/etc/init.d/mimedefang", @implode("\n", $f));
	echo "Starting mimedefang: saving init script for debian done\n";	
	@chmod("/etc/init.d/mimedefang",0755);
	shell_exec("$debianbin -f mimedefang defaults >/dev/null 2>&1");
	
}

function etc_default(){
$f[]="# Mimedefang configuration file";
$f[]="# This file is read by the init script";
$f[]="# See /etc/init.d/mimedefang";
$f[]="";
$f[]="# The socket used by mimedefang to communicate with sendmail";
$f[]="# SOCKET=\$SPOOLDIR/mimedefang.sock";
$f[]="MX_USER=postfix";
$f[]="SYSLOG_FACILITY=mail";
$f[]="";
$f[]="# If you want to keep spool directories around if the filter fails,";
$f[]="# set the next one to yes";
$f[]="# KEEP_FAILED_DIRECTORIES=no";
$f[]="";
$f[]="# \"yes\" turns on the multiplexor relay checking function";
$f[]="# MX_RELAY_CHECK=no";
$f[]="";
$f[]="# \"yes\" turns on the multiplexor HELO checking function";
$f[]="# MX_HELO_CHECK=no";
$f[]="";
$f[]="MX_SENDER_CHECK=yes";
$f[]="MX_RECIPIENT_CHECK=yes";
$f[]="MX_LOG=yes";
$f[]="";
$f[]="# Set to yes if you want to use an embedded Perl interpreter";
$f[]="# MX_EMBED_PERL=yes";
$f[]="";
$f[]="# Set to full path of socket for Sendmail's SOCKETMAP map, if you";
$f[]="# want to use it with MIMEDefang";
$f[]="# MX_MAP_SOCKET=\$SPOOLDIR/map.sock";
$f[]="";
$f[]="# The multiplexor does not start all slaves at the same time.  Instead,";
$f[]="# it starts one slave every MX_SLAVE_DELAY seconds when the system is idle.";
$f[]="# (If the system is busy, the multiplexor starts slaves as incoming mail";
$f[]="# requires attention.)";
$f[]="# MX_SLAVE_DELAY=3";
$f[]="";
$f[]="# The next setting is an absolute limit on slave activation.  The multiplexor";
$f[]="# will NEVER activate a slave within MX_MIN_SLAVE_DELAY seconds of another.";
$f[]="# The default of zero means that the multiplexor will activate slaves as";
$f[]="# quickly as necessary to keep up with incoming mail.";
$f[]="# MX_MIN_SLAVE_DELAY=0";
$f[]="";
$f[]="# Set to yes if you want the multiplexor to log stats in";
$f[]="# /var/log/mimedefang/stats  The /var/log/mimedefang directory must";
$f[]="# exist and be writable by the user you're running MIMEDefang as.";
$f[]="# MX_STATS=no";
$f[]="";
$f[]="# Number of slaves reserved for connections from loopback.  Use -1";
$f[]="# for default behaviour, 0 to allow loopback connections to queue,";
$f[]="# or >0 to reserve slaves for loopback connections";
$f[]="LOOPBACK_RESERVED_CONNECTIONS=-1";
$f[]="";
$f[]="# If you want new connections to be allowed to queue, set the";
$f[]="# next variable to yes.  Normally, only existing connections are";
$f[]="# allowed to queue requests for work.";
$f[]="ALLOW_NEW_CONNECTIONS_TO_QUEUE=no";
$f[]="";
$f[]="# Set to yes if you want the stats file flushed after each entry";
$f[]="# MX_FLUSH_STATS=no";
$f[]="";
$f[]="# Set to yes if you want the multiplexor to log stats to syslog";
$f[]="# MX_STATS_SYSLOG=no";
$f[]="";
$f[]="# The socket used by the multiplexor";
$f[]="# MX_SOCKET=\$SPOOLDIR/mimedefang-multiplexor.sock";
$f[]="";
$f[]="# Maximum # of requests a process handles";
$f[]="# MX_REQUESTS=200";
$f[]="";
$f[]="# Minimum number of processes to keep.  The default of 0 is probably";
$f[]="# too low; we suggest 2 instead.";
$f[]="# MX_MINIMUM=2";
$f[]="";
$f[]="# Maximum number of processes to run (mail received while this many";
$f[]="# processes are running is rejected with a temporary failure, so be";
$f[]="# wary of how many emails you receive at a time).  This applies only";
$f[]="# if you DO use the multiplexor.  The default value of 2 is probably";
$f[]="# too low; we suggest 10 instead";
$f[]="# MX_MAXIMUM=10";
$f[]="";
$f[]="# Uncomment to log slave status; it will be logged every";
$f[]="# MX_LOG_SLAVE_STATUS_INTERVAL seconds";
$f[]="# MX_LOG_SLAVE_STATUS_INTERVAL=30";
$f[]="";
$f[]="# Uncomment next line to have busy slaves send status updates to the";
$f[]="# multiplexor.  NOTE: Consumes one extra file descriptor per slave, plus";
$f[]="# a bit of CPU time.";
$f[]="# MX_STATUS_UPDATES=yes";
$f[]="";
$f[]="# Limit slave processes' resident-set size to this many kilobytes.  Default";
$f[]="# is unlimited.";
$f[]="# MX_MAX_RSS=10000";
$f[]="";
$f[]="# Limit total size of slave processes' memory space to this many kilobytes.";
$f[]="# Default is unlimited.";
$f[]="# MX_MAX_AS=30000";
$f[]="";
$f[]="# If you want to use the \"notification\" facility, set the appropriate port.";
$f[]="# See the mimedefang-notify man page for details.";
$f[]="# MX_NOTIFIER=inet:4567";
$f[]="";
$f[]="# Number of seconds a process should be idle before checking for";
$f[]="# minimum number and killed";
$f[]="# MX_IDLE=300";
$f[]="";
$f[]="# Number of seconds a process is allowed to scan an email before it is";
$f[]="# considered dead.  The default is 30 seconds; we suggest 300.";
$f[]="# MX_BUSY=300";
$f[]="";
$f[]="# Extra sendmail macros to pass.  Actually, you can add any extra";
$f[]="# mimedefang options here...";
$f[]="# MD_EXTRA=\"-a auth_author\"";
$f[]="";
$f[]="# Multiplexor queue size -- default is 0 (no queueing)";
$f[]="# MX_QUEUE_SIZE=10";
$f[]="";
$f[]="# Multiplexor queue timeout -- default is 30 seconds";
$f[]="# MX_QUEUE_TIMEOUT=30";
$f[]="";
$f[]="# Set to yes if you don't want MIMEDefang to see invalid recipients.";
$f[]="# Only works with Sendmail 8.14.0 and later.";
$f[]="# MD_SKIP_BAD_RCPTS=no";
$f[]="";
$f[]="# SUBFILTER specifies which filter rules file to use";
$f[]="SUBFILTER=/etc/mail/mimedefang-filter";
$f[]="";
$f[]="# The contents of the \"X-Scanned-By\" header added to each mail.  Do";
$f[]="# not use the quote mark (') in this setting.  Setting it to \"-\"";
$f[]="# prevents the header from getting added at all.";
$f[]="# X_SCANNED_BY=\"MIMEDefang N.NN (www . roaringpenguin . com / mimedefang)\"\n";	
echo "Starting mimedefang: saving /etc/default/mimedefang done\n";	
@file_put_contents("/etc/default/mimedefang", @implode("\n", $f));


}
