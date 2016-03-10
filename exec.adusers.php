<?php
die();
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
	include_once(dirname(__FILE__).'/ressources/class.computers.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	if($argv[1]=="--computers"){impcomputers();die();}
	
	die();
	if($argv[1]=="--export"){export($argv[2]);die();}
	if($argv[1]=="--import"){import($argv[2]);die();}

	
	
	ImportTasks();
	
function impcomputers(){
	$sock=new sockets();
	$ldap=new clladp();
	$users=new usersMenus();
	$EnableKerbAuth=1;
	if(!$ldap->IsKerbAuth()){$EnableKerbAuth=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($GLOBALS["VERBOSE"]){echo "EnableKerbAuth=$EnableKerbAuth\n";}
	if($EnableKerbAuth==0){return;}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/exec.adusers.php.impcomputers.time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){return;}
	
	$ztime=$unix->file_time_min($timefile);
	if($ztime<240){die();}
	@unlink($timefile);
	@file_put_contents($timefile, time());

	
	
	array('a','b','c','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$iptcp=new IP();
	
	
	
	while (list ($num, $letter) = each ($array) ){
		$ad=new external_ad_search();
		$CMPS=$ad->find_computers("$letter*");
		
		$count=$CMPS["count"];
		if($GLOBALS["VERBOSE"]){echo "$letter* == $count\n";}
		
		for($i=0;$i<$count;$i++){
			$arraCMP=$CMPS[$i];
			$hostname=null;
			$domain=null;
			$operatingsystem=null;
			$uid=$arraCMP["samaccountname"][0];
			$computer=new computers($uid);
			if($computer->IsExists){continue;}
			if(isset($arraCMP["dnshostname"][0])){$hostname=$arraCMP["dnshostname"][0];}
			if(isset($arraCMP["operatingsystem"][0])){$operatingsystem=$arraCMP["operatingsystem"][0];}
			
			if($hostname==null){if(isset($arraCMP["name"])){$hostname=$arraCMP["name"][0];}}
			if($hostname==null){if(isset($arraCMP["cn"])){$hostname=$arraCMP["cn"][0];}}
			if($hostname==null){continue;}
			if(strpos($hostname, ".")>0){$DD=explode(".",$hostname);unset($DD[0]);$domain=@implode(".", $DD);}
			$ipaddr=gethostbyname($hostname);
			if(!$iptcp->isIPAddress($ipaddr)){
				if($GLOBALS["VERBOSE"]){echo "$hostname - > $ipaddr -> abort\n";}
				continue;}
			$computer->ComputerIP=$ipaddr;
			$computer->uid="$uid";
			if($domain<>null){$computer->DnsZoneName=$domain;}
			$computer->ComputerRealName=$arraCMP["name"];
			$computer->ComputerOS=$operatingsystem;	
			if($GLOBALS["VERBOSE"]){echo "Add $hostname/$ipaddr $operatingsystem\n";}
			$computer->Add();
		
			
			
		}
		
	}
	
	
}

function ImportTasks(){
	$sock=new sockets();
	$ldap=new clladp();
	$users=new usersMenus();
	$EnableKerbAuth=1;
	if(!$ldap->IsKerbAuth()){$EnableKerbAuth=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($GLOBALS["VERBOSE"]){echo "EnableKerbAuth=$EnableKerbAuth\n";}
	if($EnableKerbAuth==0){return;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){
		ufdbguard_admin_events("Warning: Task Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
		

	if(!CheckTables()){
		ufdbguard_admin_events("Failed, Mysql is not ready", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		return;
	}


	$q=new mysql();
	$q->check_storage_table(true);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$wbinfo=$unix->find_program("wbinfo");
	$GLOBALS["xxxCOUNT"]=0;
	exec("$wbinfo -g 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Error looking#", $line)){
			ufdbguard_admin_events("Failed to lookup users, aborting task", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return;
		}

		if(trim($line)==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "Checking group $line\n";}
		CheckGroup($line);
	}

	if(($users->SQUID_INSTALLED) OR ($users->APP_UFDBGUARD_INSTALLED)){
		ufdbguard_admin_events("Importing {$GLOBALS["xxxCOUNT"]} users done", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		if($GLOBALS["xxxCOUNT"]>0){
				
			$php5=$unix->LOCATE_PHP5_BIN();
			shell_exec("$nohup $php5 ". dirname(__FILE__)."/exec.squidguard.php --build schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
		}

		$AdSchBuildProxy=$sock->GET_INFO("AdSchBuildProxy");
		$AdSchBuildUfdb=$sock->GET_INFO("AdSchBuildUfdb");
		$AdSchRestartSquid=$sock->GET_INFO("AdSchRestartSquid");
		if(!is_numeric($AdSchBuildProxy)){$AdSchBuildProxy=0;}
		if(!is_numeric($AdSchBuildUfdb)){$AdSchBuildUfdb=0;}
		if(!is_numeric($AdSchRestartSquid)){$AdSchRestartSquid=0;}

		if($AdSchBuildProxy==1){
			shell_exec("$php5 ". dirname(__FILE__)."/exec.squid.build --build --force schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1");
		}
		if($AdSchBuildUfdb==1){
			shell_exec("$php5 ". dirname(__FILE__)."/exec.squidguard.php --build --force schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1");
		}

		if($AdSchRestartSquid==1){
			shell_exec("$php5 ". dirname(__FILE__)."/exec.squidguard.php  --reload-squid schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1");
		}
	}

	shell_exec("$nohup $php5 ". __FILE__." --computers schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");


}



function CheckTables(){
	$q=new mysql();

	if(!$q->TABLE_EXISTS('adgroups','artica_backup')){
		$sql="CREATE TABLE `artica_backup`.`adgroups` (
			`gpid` BIGINT UNSIGNED NOT NULL,
			`groupname` VARCHAR( 128 ) NOT NULL ,
			 PRIMARY KEY (`gpid`),
			 KEY `groupname` (`groupname`)
			 )";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
	}
	if(!$q->TABLE_EXISTS('adusers','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `adusers` (
				  `gpid` BIGINT UNSIGNED NOT NULL,
				  `uid` varchar(128) NOT NULL,
				  KEY `gpid` (`gpid`),
				  KEY `uid` (`uid`)
				)";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
	}

	if(!$q->TABLE_EXISTS('adgroups','artica_backup')){return false;}
	if(!$q->TABLE_EXISTS('adusers','artica_backup')){return false;}
	$q->check_storage_table(true);
	return true;

}

function CheckGroup($groupname){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$net=$unix->find_program("net");
	$groupanecmd=$unix->shellEscapeChars($groupname);

	$cmd="$net cache flush";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	$cmd="$wbinfo --group-info=$groupanecmd 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	$line=trim(@implode(" ", $results));
	if(preg_match("#Could not get info for group#",$line,$re)){
		ufdbguard_admin_events("Failed to lookup users, $groupname: $line", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		return;

	}
	if(!preg_match("#^.+?:x:([0-9]+):(.*)#", $line,$re)){
		ufdbguard_admin_events("Failed to lookup users, $groupname: $line", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		return;
	}

	$gpid=$re[1];
	$userslist=$re[2];
	$sql="DELETE FROM adgroups WHERE gpid=$gpid";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		ufdbguard_admin_events("Failed to manage, $groupname: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		return;
	}
	$groupname=utf8_encode($groupname);
	$groupname=addslashes($groupname);
	$q->QUERY_SQL("INSERT IGNORE INTO adgroups (gpid,groupname) VALUES ('$gpid','$groupname')","artica_backup");
	if(!$q->ok){
		ufdbguard_admin_events("Failed to manage, $groupname: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
		return;
	}

	$q->QUERY_SQL("DELETE FROM adusers WHERE gpid=$gpid","artica_backup");
	$c=0;
	$f=array();
	$usersTR=explode(",",$userslist);
	while (list ($num, $line) = each ($usersTR)){
		if(trim($line)==null){continue;}
		$line=utf8_encode($line);
		$line=addslashes($line);
		$f[]="('$gpid','$line')";
		$c++;
	}

	if(count($f)>0){
		$sql="INSERT IGNORE INTO adusers(`gpid`,`uid`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			ufdbguard_admin_events("Failed to import users on $groupname: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return;
		}
	}
	$GLOBALS["xxxCOUNT"]=$GLOBALS["xxxCOUNT"]+$c;

}

function export($filename){
	$sql="SELECT * FROM adgroups";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$f["GROUPS"][$ligne["gpid"]]=$ligne["groupname"];

	}

	$sql="SELECT * FROM adusers";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$f["USERS"][]=array("gpid"=>$ligne["gpid"],"uid"=>$ligne["uid"]);

	}

	@file_put_contents($filename, serialize($f));
	echo "Exporting $filename done\n";

}

function import($filename){

	if(!CheckTables()){echo "Failed\n";}

	$f=unserialize(@file_get_contents($filename));
	if(!is_array($f)){
		echo "Not an array\n";
		return;
	}

	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE adgroups","artica_backup");
	$usrs=$f["USERS"];

	while (list ($gpid, $groupname) = each ($f["GROUPS"])){
		$groupname=utf8_encode($groupname);
		$groupname=addslashes($groupname);
		$t[]="('$gpid','$groupname')";

	}


	$sql="INSERT IGNORE INTO adgroups (gpid,groupname) VALUES ".@implode(",", $t);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";return;}

	$t=array();
	$q->QUERY_SQL("TRUNCATE TABLE adusers","artica_backup");
	echo count($usrs)." users\n";
	while (list ($index, $array) = each ($usrs)){
		//echo "{$array["gpid"]}:{$array["uid"]}\n";
		$array["uid"]=utf8_encode($array["uid"]);
		$array["uid"]=addslashes($array["uid"]);
		$t[]="('{$array["gpid"]}','{$array["uid"]}')";

	}

	$sql="INSERT IGNORE INTO adusers(`gpid`,`uid`) VALUES ".@implode(",", $t);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "$q->mysql_error\n";
		return;
	}
	echo "Importing $filename done\n";
}
?>