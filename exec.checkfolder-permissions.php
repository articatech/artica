<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');


$unix=new unix();
$time=$unix->file_time_min("/etc/artica-postfix/pids/exec.checkfolder-permissions.php.MAIN.time");

if($time<240){
	if($GLOBALS["VERBOSE"]){echo "NEED 240Mn, current {$time}mn die()\n";}
	die();
}


$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>0){
	if($GLOBALS["VERBOSE"]){echo "Too many instances ". count($pids)." dying\n";}
	die();
}



$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){die();}


if(system_is_overloaded(basename(__FILE__))){
	die("System is overloaded\n");
}

@unlink("/etc/artica-postfix/pids/exec.checkfolder-permissions.php.MAIN.time");
@file_put_contents("/etc/artica-postfix/pids/exec.checkfolder-permissions.php.MAIN.time",time());

$sock=new sockets();
if(is_file("/usr/share/artica-postfix/class.users.menus.inc")){@unlink("/usr/share/artica-postfix/class.users.menus.inc");}
$f=file("/etc/lighttpd/lighttpd.conf");
while (list ($num, $line) = each ($f) ){
	if(preg_match("#server\.username.*?\"(.+?)\"#", $line,$re)){
		$username=$re[1];
		continue;
	}
	
	if(preg_match("#server\.groupname.*?\"(.+?)\"#", $line,$re)){
		$groupname=$re[1];
		continue;
	}	
	
	if($groupname<>null){
		if($username<>null){break;}
	}
	
}

$f=array();
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
if($GLOBALS["VERBOSE"]){echo "lighttpd user: $username:$groupname\n";}
if(is_dir("/etc/resolvconf")){
	shell_exec("$php5 ".basename(__FILE__)."/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1 &");
	if(!is_dir("/etc/resolvconf/run/interface")){@mkdir("/etc/resolvconf/run/interface",0755,true);}

}
if(!is_dir("/var/log/btmp")){@mkdir("/var/log/btmp",0755,true);}
@chmod("/etc/artica-postfix/settings/Daemons",0755);
$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
writeprogress(5,"/etc/artica-postfix/settings/Daemons");

$sock=new sockets();
  $f[]='ressources';
  $f[]='ressources/sessions';
  $f[]='ressources/web';
  $f[]='ressources/web/logs';
  $f[]='ressources/logs/web/queue/sessions';
  $f[]='framework'; 
  $f[]='ressources/userdb';
  $f[]='ressources/conf';
  $f[]='ressources/conf/kasDatas';
  $f[]='ressources/logs';
  $f[]='ressources/profiles';
  $f[]='ressources/profiles/icons';
  $f[]='ressources/sessions/SessionData';
  $f[]='computers/ressources/sessions/SessionData';
  $f[]='ressources/logs/web';
  $f[]='computers/ressources/logs';
  $f[]='ressources/conf/upload';
  $f[]='computers/ressources/profiles';
  $f[]='ressources/conf/upload';
  
  
  $artica_path=dirname(__FILE__);
  if($username==null){
  	$LighttpdUserAndGroup=$sock->GET_INFO('LighttpdUserAndGroup');
  	$LighttpdUserAndGroup=str_replace('lighttpd:lighttpd:lighttpd','lighttpd:lighttpd',$LighttpdUserAndGroup);
  	$LighttpdUserAndGroup=str_replace('www-data:www-data:www-data','www-data:www-data',$LighttpdUserAndGroup);   
	if(!preg_match("#(.+?):(.+)#", $LighttpdUserAndGroup,$re)){$username="www-data";$groupname="www-data";}
  }
  @mkdir("/etc/artica-postfix/croned.1",0755,true);
  @mkdir("/etc/artica-postfix/pids",0755,true);
 
  $unix->chmod_func(0755, "$artica_path/*");
  $unix->chmod_func(0755, "$artica_path/bin/*");
  $unix->chmod_func(0777,"/usr/share/artica-postfix/exec.logfile_daemon.php");
  $unix->chmod_func(0777, "$artica_path/ressources");
  $unix->chmod_func(0777, "$artica_path/ressources/conf/upload/*");
  
  if($username==null){$username="www-data";}
  if($groupname==null){$groupname="www-data";}
  
  $unix->chown_func($username,$groupname, "$artica_path/*");
  
  
  	@chown("$artica_path", $username);
  	@chgrp("$artica_path", $groupname);  
  	@chmod("$artica_path",0755);
  while (list ($num, $dir) = each ($f) ){
  	
  	if(system_is_overloaded(basename(__FILE__))){die();}
  	
  	writeprogress(5,"$dir");
  	if(!is_dir("$artica_path/$dir")){@mkdir("$artica_path/$dir",0755,true);}
  	@chown("$artica_path/$dir", $username);
  	@chgrp("$artica_path/$dir", $groupname);
  	@chmod("$artica_path/$dir",0755);
  	$unix->chown_func($username, $groupname,"$artica_path/$dir/*");
  	
  }
  

  @mkdir("/usr/share/artica-postfix/ressources/logs/web/create-users",0755,true);
  
  @mkdir("/opt/artica/var/rrd/yorel",0755,true);
  @chown("/opt/artica/var/rrd/yorel", $username);
  @chgrp("/opt/artica/var/rrd/yorel", $groupname);  
  
$f=array();

$f[]='/usr/share/zarafa-webaccess/*';
$f[]='/var/lib/zarafa-webaccess/tmp/*';
while (list ($num, $dir) = each ($f) ){
	$unix->chown_func($username, $groupname,$dir);
	$unix->chmod_func(0755,$dir);
}  

$filesBin=$unix->DirFiles("/usr/share/artica-postfix/bin");
while (list ($filename,$line) = each ($filesBin)){
	@chmod("/usr/share/artica-postfix/bin/$filename",0755);
	@chown("/usr/share/artica-postfix/bin/$filename","root");
}
  

$f=array();
$f[]='conf';
$f[]='install';
$f[]='conf/kasDatas';
$f[]='logs';
$f[]='profiles';
$f[]='sessions';
$f[]='sessions/SessionData';
$f[]='userdb';
$f[]='databases';



  while (list ($num, $dir) = each ($f) ){
  	writeprogress(5,"$dir");
  	if(system_is_overloaded(basename(__FILE__))){die();}
  	$dirname="$artica_path/computers/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755);
  	
  	$dirname="$artica_path/user-backup/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755); 	

  	$dirname="$artica_path/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755);   	
  	
  }
  
  
$f=array();
$f[]='/ressources/conf';
$f[]='/ressources/logs';
$f[]='/var/run/lighttpd';
$f[]='/ressources/databases';
$f[]='/ressources/install';
$f[]='/ressources/profiles';
$f[]='/ressources/sessions';
$f[]='/computers/ressources/sessions';
$f[]='/computers/ressources/logs';
$f[]="/ressources/isoqlog";




  while (list ($num, $dir) = each ($f) ){
  	writeprogress(5,"$dir");
  	$unix->chown_func($username, $groupname,"$artica_path$dir/*");
  }

$ToCreateDefault[]="/opt/artica/amavis-hooks";
$ToCreateDefault[]="/opt/artica/philesight";
$ToCreateDefault[]="/opt/artica/share/www/jpegPhoto";
  while (list ($num, $dir) = each ($ToCreateDefault) ){
  	writeprogress(5,"$dir");
  	if(!is_dir($dir)){@mkdir($dir,0755,true);}
  	
  }
$unix->chown_func("postfix", "postfix","/opt/artica/amavis-hooks");
$unix->chown_func("root", "root","/etc/cron.d/*");
$unix->chown_func($username, $groupname,"$artica_path/ressources/userdb/*");
$unix->chown_func($username, $groupname,"$artica_path");
$unix->chown_func($username, $groupname,"/var/lib/php5/*");
$unix->chown_func($username, $groupname,"/opt/artica/share/www/jpegPhoto/*");

if(system_is_overloaded(basename(__FILE__))){die();}
$unix->chmod_func(0640,"etc/cron.d");
$unix->chmod_func(0755,"$artica_path/ressources/databases");
$unix->chmod_func(0777,"$artica_path/ressources/userdb");
$unix->chmod_func(0777,"$artica_path/ressources/logs");
writeprogress(5,"/var/lib/php5");
$unix->chmod_func(0777,"/var/lib/php5/*");
$unix->chmod_func(0755,"/usr/local/share/artica");


$chown=$unix->find_program("chown");
writeprogress(5,"/home/ArticaStatsDB");
shell_exec("$chown -R ArticaStats:ArticaStats /home/ArticaStatsDB");

if(is_file('/var/run/memcached.sock')){@chmod("/var/run/memcached.sock",0777);}
if(is_dir("/etc/ssh")){$unix->chmod_func(0640,"etc/ssh");}
if(is_dir("/usr/share/pommo")){
	$unix->chown_func($username, $groupname,"/usr/share/pommo/*");
	$unix->chmod_func(0755,"/usr/share/pommo/*");
}

  if(is_dir("/usr/share/zarafa-webaccess")){@mkdir("/var/lib/zarafa-webaccess/tmp",0755,true);}   
  $unix->chmod_func(0777,"/usr/share/artica-postfix/user-backup/ressources/conf");
  if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/queue/sessions")){
  @mkdir("/usr/share/artica-postfix/ressources/logs/web/queue/sessions",0755,true);}
  writeprogress(5,"artica-postfix/bin");
  $unix->chmod_func(0755,"/usr/share/artica-postfix/bin/*");
  $unix->chmod_func(0755,"/usr/share/artica-postfix/ressources/mem.pl");
  $unix->chmod_func(0644,"/usr/share/artica-postfix/bin/install/amavis/check-external-users.conf");
  $unix->chown_func("root","root", "/usr/share/artica-postfix/bin/*");
  $unix->chown_func("root","root", "/usr/share/artica-postfix/bin/install/amavis");
  $unix->chown_func($username,$groupname, "/var/lib/php/session/*");
  $unix->chown_func($username,$groupname, "/var/lib/php5/*");
  $unix->chown_func($username,$groupname, "/var/lighttpd/upload");
  $unix->chown_func("mysql","mysql", "/var/run/mysqld/*");
  writeprogress(5,"/var/lib/mysql");
  $chown=$unix->find_program("chown");
  $nice=$unix->EXEC_NICE();
  $nohup=$unix->find_program("nohup");
  $rm=$unix->find_program("rm");

  if(system_is_overloaded(basename(__FILE__))){die();}
  $tmpf=$unix->FILE_TEMP();
  $sh[]="#!/bin/sh";
  $sh[]="$nice $chown -R mysql:mysql /var/lib/mysql >/dev/null 2>&1";
  $sh[]="$rm -f $tmpf.sh";
  $sh[]="\n";
  @file_put_contents("$tmpf.sh", @implode("\n", $sh));
  @chmod("$tmpf.sh",0755);
  system("$nohup $tmpf.sh >/dev/null 2>&1 &");
  $sh=array();

   
$postconf=$unix->find_program("postconf");
$ln=$unix->find_program("ln");
$squidbin=$unix->LOCATE_SQUID_BIN();

if(is_file($postconf)){
	if(!is_dir("/var/lib/postfix")){@mkdir("/var/lib/postfix",0755,true);}
	if(!is_dir("/var/spool/postfix/var")){@mkdir("/var/spool/postfix/var",0755,true);}
	if(!is_dir("/opt/artica/mimedefang-hooks")){@mkdir("/opt/artica/mimedefang-hooks",0755,true);}
	if(!is_dir("/var/mail/artica-wbl")){@mkdir("/var/mail/artica-wbl",0755,true);}
	$unix->chown_func("postfix", "postfix","/var/lib/postfix/*");
	$unix->chown_func("postfix", "postfix","/opt/artica/mimedefang-hooks/*");
	$unix->chown_func("mail", "root","/var/mail/artica-wbl/*");
	shell_exec("$ln -s --force /var/run /var/spool/postfix/var/run");
	if(is_dir('/var/run/saslauthd')){if(is_file('/var/run/saslauthd/mux')){$unix->chown_func('postfix','mail','/var/run/saslauthd');}}
	$CopyToDomainSpool=$sock->GET_INFO('CopyToDomainSpool');
	if(strlen($CopyToDomainSpool)==0){$CopyToDomainSpool='/var/spool/artica/copy-to-domain';}
	@mkdir($CopyToDomainSpool,0755,true);
	$unix->chown_func("postfix", "postfix","$CopyToDomainSpool/*");
	if(is_file('/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.sock')){$unix->chown_func("postfix", "postfix","/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.sock");}
	if(is_file('/usr/local/etc/altermime-disclaimer.txt')){$unix->chown_func("postfix", "postfix","/usr/local/etc/altermime-disclaimer.txt");}
	@mkdir("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chmod("/home/artica/postfix/milter-greylist",0755,true);
	@chown("/home/artica/postfix/milter-greylist", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist", "postfix");
	
	@chmod("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chown("/home/artica/postfix/milter-greylist/logger", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist/logger", "postfix");

}

$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
if($MySQLTMPDIR<>null){
	if($MySQLTMPDIR<>"/tmp"){
		if(strlen($MySQLTMPDIR)>3){
			if(!is_dir("MySQLTMPDIR")){@mkdir($MySQLTMPDIR,0777);}
			$unix->chmod_func(0777, $MySQLTMPDIR);
			$unix->chown_func("mysql","mysql", $MySQLTMPDIR);
		}
	}
}

	CheckArticaFolderSecurity($username,$groupname);


$files=$unix->DirFiles("/usr/share/artica-postfix/bin");
while (list ($filename,$line) = each ($files)){
	writeprogress(5,"$filename");
	@chmod("/usr/share/artica-postfix/bin/$filename",0755);
	@chown("/usr/share/artica-postfix/bin/$filename","root");
}





writeprogress(6,"{done}");

function writeprogress($perc,$text){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wizard.progress";
	$array["POURC"]=$perc;
	$array["TEXT"]="{set_permissions} $text";
	echo "$text\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",true,0755);
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	$unix=new unix();

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}


	$unix->events("$perc} $text","/var/log/artica-wizard.log",$sourcefunction,$sourceline,$sourcefile);

}



function CheckArticaFolderSecurity($username,$groupname){
	$unix=new unix();
	$artica_path="/usr/share/artica-postfix";
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
	
	if(is_file("/usr/local/ArticaStats/bin/postgres")){
		$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
		if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
	}
	
	$fSquidDirs[]="/var/log/squid/squid_admin_mysql";
	$fSquidDirs[]="/usr/share/squid3";
	$fSquidDirs[]=$GLOBALS["LogFileDeamonLogDir"];
	
	
	while (list ($num, $directory) = each ($fSquidDirs)){
		if(!is_dir($directory)){@mkdir($directory,0755,true);}
		@chown($directory, "squid");
		@chgrp($directory, "squid");
	}
	

	$squidfiles=$unix->SquidPHPFiles();
	
	$files=$unix->DirFiles($artica_path);
	while (list ($filename,$line) = each ($files)){
	
		if(is_numeric($filename)){
			@unlink("$artica_path/$filename");
			continue;
		}
	
		if(isset($squidfiles[$filename])){
			@chmod("$artica_path/$filename",0755);
			@chgrp("$artica_path/$filename", "squid");
			@chown("$artica_path/$filename", "squid");
			continue;
		}
	
		$unix->chown_func($username, $groupname,"$artica_path/$filename");
		$unix->chmod_func(0755, "$artica_path/$filename");
	
	}
	
	$unix->chmod_func(0755,"/var/log/squid/access.log");
	$unix->chmod_func(0777,"/var/log/squid/QUOTADB.db");
	
}



?>