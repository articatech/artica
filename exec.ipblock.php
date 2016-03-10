<?php
$GLOBALS["FORCE"]=false;
$_GET["filelogs"]="/var/log/artica-postfix/iptables.debug";
$_GET["filetime"]="/etc/artica-postfix/croned.1/".basename(__FILE__).".time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.baseunix.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');


update();


function update(){
	if(system_is_overloaded()){return;}
	$unix=new unix();
	$sock=new sockets();
	
	$pidfile="/etc/artica-postfix/pids/exec.ipblock.php.update.pid";
	$pidtime="/etc/artica-postfix/pids/exec.ipblock.php.update.time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}}
	include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
	
	if(!is_file($pidtime)){@file_put_contents($pidtime, time()); }
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidtime)>720){
			@unlink($pidtime);
			@file_put_contents($pidtime, time());
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	if($EnableIpBlocks==0){return ;}
	$DIR_TEMP=$unix->TEMP_DIR();
	$curl=new ccurl("http://www.ipdeny.com/ipblocks/data/countries/all-zones.tar.gz");
	if(!$curl->GetFile("$DIR_TEMP/all-zones.tar.gz")){
		system_admin_events(0,"Fatal, Unable to download all-zones.tar.gz from ipdeny.com",__FILE__,__LINE__);
		return;
		
	}
	
	$OldMd5=$sock->GET_INFO("IpBlocksMD5");
	$md5File=md5_file("$DIR_TEMP/all-zones.tar.gz");
	if($md5File==$OldMd5){ipblocks();return;}
	
	$tar=$unix->find_program("tar");
	@mkdir("/home/artica/ipblocks",0755,true);
	shell_exec("$tar xf $DIR_TEMP/all-zones.tar.gz -C  /home/artica/ipblocks/");
	if(ipblocks()){
		$sock->SET_INFO("IpBlocksMD5", "$md5File");
		system_admin_events(0,"Restarting Firewall in order to refresh countries blocking");
	}
	
}

function ipblocks(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nogup=$unix->find_program("nohup");

	$q=new mysql();
	if(!$q->TABLE_EXISTS('ipblocks_db','artica_backup')){$q->BuildTables();}
	$q->QUERY_SQL("TRUNCATE TABLE `ipblocks_db`");
	
	foreach (glob("/home/artica/ipblocks/*.zone") as $filename) {
		
		$basename=basename($filename);
		system_admin_events(2,"Parsing $basename from ipdeny.com",__FILE__,__LINE__);
		if(!preg_match("#(.+?)\.zone#", $basename,$re)){continue;}
		$country=$re[1];
		$datas=explode("\n", @file_get_contents($filename));
		$f=true;

		while (list ($index, $line) = each ($datas) ){
			$line=trim($line);if($line==null){continue;}if($country==null){continue;}
			$sql="INSERT IGNORE INTO ipblocks_db (cdir,country) VALUES('$line','$country')";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				system_admin_events(0,"Fatal, MySQL error $q->mysql_error",__FILE__,__LINE__);
				return false;
			}
		}
	
		@unlink($filename);
	}

	return true;


}


