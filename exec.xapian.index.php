<?php
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__) .'/ressources/class.autofs.inc');
$GLOBALS["INDEXED"]=0;
$GLOBALS["SKIPPED"]=0;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$cmdlines=@implode(" ", $argv);
writelogs("Executed `$cmdlines`","MAIN",__FILE__,__LINE__);
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if($argv[1]=="--mysql-dirs"){Scan_mysql_dirs();die();}
if($argv[1]=="--shared"){shared();die();}
if($argv[1]=="--homes"){homes();die();}

if(systemMaxOverloaded()){
	writelogs("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	die();
}





ScanQueue();
die();

function ScanQueue(){
	
$unix=new unix();
$GLOBALS["omindex"]=$unix->find_program("omindex");
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid)){
	writelogs("Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__);
	die();
}

@file_put_contents($pidfile, getmypid());	
	
	
	$users=new usersMenus();
	$GLOBALS["SAMBA_INSTALLED"]=$users->SAMBA_INSTALLED;
	$path="{$GLOBALS["ARTICALOGDIR"]}/xapian";
	$SartOn=time();
	$files=$unix->DirFiles($path);
	if(count($files)==0){return;}
	cpulimitProcessName("omindex");
	while (list ($num, $file) = each ($files) ){
		$toScan="$path/$file";
		if(ScanFile($toScan)){
			@unlink($toScan);
		}
	}
$SartOff=time();
$time=distanceOfTimeInWords($SartOn,$SartOff);
$countdir=count($GLOBALS["DIRS"]);
cpulimitProcessNameKill("omindex");

$echo="InstantSearch {items}: {skipped}: {$GLOBALS["SKIPPED"]} {files}<br>{indexed}: {$GLOBALS["INDEXED"]} {files}<br>{duration}:$time";
if($GLOBALS["INDEXED"]>0){
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/xapian.results",$echo);
	@chmod("/usr/share/artica-postfix/ressources/logs/xapian.results",0777);
}
echo($echo."\n");	
	

}

function ScanFile($toScan){
	if(!$GLOBALS["SAMBA_INSTALLED"]){return true;}
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	$file=@file_get_contents($toScan);
	$ext=Get_extension($file);
	$nice=EXEC_NICE();	
	$database="$localdatabase/samba.db";
	if(!is_file($GLOBALS["omindex"])){return true;}
	$directory=dirname($file);
	if($GLOBALS["DIRS"]["$directory"]){return true;}
	$basename=basename($file);
	
	$cmd="$nice{$GLOBALS["omindex"]} -l 1 --follow -D $database -U \"$directory\" \"$directory\"";
	$GLOBALS["DIRS"]["$directory"]=true;
	exec($cmd,$results);
	ParseLogs($results);
	return true;
	}

//xls2csv,antiword

function ParseLogs($array){
	$indexed=0;
	$skipped=0;
	if(!is_array($array)){return null;}
	while (list ($num, $ligne) = each ($array) ){
		if(trim($ligne)==null){continue;}
		if(preg_match('#^Indexing.+?\.\.\.\s+updated\.$#',trim($ligne))){
			$GLOBALS["INDEXED"]=$GLOBALS["INDEXED"]+1;
			$indexed++;
			continue;
		}
		
	if(preg_match('#.+skipping$#',$ligne)){
			$GLOBALS["SKIPPED"]=$GLOBALS["SKIPPED"]+1;
			$skipped++;
			continue;
		}	

	if(preg_match('#^Indexing.+#',trim($ligne))){
			$GLOBALS["INDEXED"]=$GLOBALS["INDEXED"]+1;
			$indexed++;
			continue;
		}

	if(preg_match('#Skipping empty file#',trim($ligne))){
			$GLOBALS["SKIPPED"]=$GLOBALS["SKIPPED"]+1;
			$skipped++;
			continue;
		}

	if(preg_match('#but indexing metadata anyway#',trim($ligne))){
			$GLOBALS["INDEXED"]=$GLOBALS["INDEXED"]+1;
			$indexed++;
			continue;
		}
		
	

	if(preg_match('#Entering directory#',$ligne)){
		continue;
	}
	
	if(preg_match('#: Skipping -\s+#',$ligne)){
		$GLOBALS["SKIPPED"]=$GLOBALS["SKIPPED"]+1;
		$skipped++;
		continue;	
	}
	
	if(preg_match('#File is encrypted#',$ligne)){continue;}
	if(preg_match('#Error: Missing or invalid#',$ligne)){continue;}
		
	echo "Unable to understand: \"$ligne\"\n";	
		
	}
	if($GLOBALS["VERBOSE"]){echo "ParseLogs(array)-> $indexed - $skipped\n";}
	return array($indexed,$skipped);
	
}

function shared(){
	$FOLDERS=array();
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){system_admin_events("Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");die();}
	@file_put_contents($pidfile, getmypid());			
	$EnableSambaXapian=$sock->GET_INFO("EnableSambaXapian");
	if(!is_numeric($EnableSambaXapian)){$EnableSambaXapian=0;}
	if($EnableSambaXapian==0){system_admin_events("Parsing shared folder is disabled in this configuration, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}	
	$SambaXapianAuth=unserialize(base64_decode($sock->GET_INFO("SambaXapianAuth")));	
	if(!is_file($GLOBALS["omindex"])){system_admin_events("omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$smbclient=$unix->find_program("smbclient");
	$umount=$unix->find_program("umount");
	
	
	if(!is_file($smbclient)){system_admin_events("smbclient, no such binary, aborting...",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$username=$SambaXapianAuth["username"];
	$password=$SambaXapianAuth["password"];
	$domain=$SambaXapianAuth["domain"];
	$comp=$SambaXapianAuth["ip"];
	if(!isset($SambaXapianAuth["lang"])){$SambaXapianAuth["lang"]=="none";}
	$lang=$SambaXapianAuth["lang"];
	if($lang==null){$lang="none";}
	
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	$database="$localdatabase/samba.default.db";
	$nice=EXEC_NICE();	
	
	
	if($comp==null){system_admin_events("smbclient, no computer set, aborting...",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	if($username<>null){
		$creds="-U $username";
		if($password<>null){
			$creds=$creds."%$password";
		}
	}
	$t1=time();
	$cmd="$smbclient -N $creds -L //$comp -g 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	
	if(is_array($results)){
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Disk\|(.+?)\|#",$ligne,$re)){
				$folder=$re[1];
				if($folder=="$username"){continue;}
				$FOLDERS[$folder]=true;
			}
		}
	}	
	if(count($FOLDERS)==0){system_admin_events("No shared folder can be browsed with $username@$comp",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$tmpM="/artica-mount-xapian";
	$count=0;
	@mkdir($tmp,0755,true);
	while (list ($directory, $none) = each ($FOLDERS) ){
		
		$mount=new mount();
		if(!$mount->smb_mount($tmpM, $comp, $username, $password, $directory)){system_admin_events("Folder:$directory, permission denied\n".@implode("\n", $GLOBALS["MOUNT_EVENTS"]),__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
		$BaseUrl="file://///$comp/$directory";
		
		
		$cmd="$nice{$GLOBALS["omindex"]} -l 0 -s $lang -E 512 -m 60M --follow -D \"$database\" -U \"$BaseUrl\" \"$tmpM\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		
		$results_scan=array();
		exec($cmd,$results_scan);
		shell_exec("$umount -l $tmpM");
		
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$count++;
		system_admin_events("scanned smb://$comp/$directory took $took indexed:{$dirRes[0]} skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
			
		
	}
	@rmdir($tmpM);
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	system_admin_events("scanned $count directorie(s) took $took",__FUNCTION__,__FILE__,__LINE__,"xapian");	
	
}

function Scan_mysql_dirs(){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){system_admin_events("Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");die();}
	@file_put_contents($pidfile, getmypid());		
	$q=new mysql();
	$q->check_storage_table(true);
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	
	$nice=EXEC_NICE();
	$sql="SELECT * FROM xapian_folders";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$t1=time();
	if(!is_file($GLOBALS["omindex"])){system_admin_events("omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	$autofs=new autofs();
	$autofs->automounts_Browse();
	$count=0;
	while ($ligne = mysql_fetch_assoc($results)) {	
		$directory=$ligne["directory"];
		$database="$localdatabase/samba.".md5($directory).".db";
		$depth=$ligne["depth"];
		$maxsize=$ligne["maxsize"];
		$samplsize=$ligne["sample-size"];
		$lang=$ligne["lang"];
		$WebCopyID=$ligne["WebCopyID"];
		$autmountdn=$ligne["autmountdn"];
		if($lang==null){$lang="english";}
		$indexed=$ligne["indexed"];
		if(!is_numeric($samplsize)){$samplsize=512;}
		if(!is_numeric($maxsize)){$maxsize=60;}
		if(!is_numeric($depth)){$depth=0;}	
		$BaseUrl=$directory;
		
		if($WebCopyID>0){
			$directory=WebCopyIDDirectory($WebCopyID);
			$BaseUrl=WebCopyIDAddresses($WebCopyID)."/";
			
		}
		
		if($autmountdn<>null){
			if(!isset($autofs->hash_by_dn[$autmountdn])){
				system_admin_events("Fatal.. $autmountdn no such connection",__FUNCTION__,__FILE__,__LINE__,"xapian");
				continue;
			}
			$autmountdn_array=$autofs->hash_by_dn[$autmountdn];
			$directory="/automounts/{$autmountdn_array["FOLDER"]}";
			$autmountdn_infos=$autmountdn_array["INFOS"];
			if(!isset($autmountdn_infos["BROWSER_URI"])){
				system_admin_events("Fatal.. $autmountdn external protocol error",__FUNCTION__,__FILE__,__LINE__,"xapian");
				continue;
			}
			$BaseUrl=$autmountdn_infos["BROWSER_URI"];
		}
		
		if(!is_dir($database)){@mkdir($database,0755,true);}
		if(!is_dir($directory)){system_admin_events("$directory, no such directory",__FUNCTION__,__FILE__,__LINE__,"xapian");continue;}
		$t=time();
		$cmd="$nice{$GLOBALS["omindex"]} -l $depth -s $lang -E $samplsize -m {$maxsize}M --follow -D \"$database\" -U \"$BaseUrl\" \"$directory\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$GLOBALS["DIRS"]["$directory"]=true;
		$results_scan=array();
		exec($cmd,$results_scan);
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$DatabaseSize=$unix->DIRSIZE_BYTES($database);
		$count++;
		$indexed=$indexed+$dirRes[0];
		system_admin_events("scanned $directory took $took indexed:$indexed skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
		$q->QUERY_SQL("UPDATE xapian_folders SET ScannedTime=NOW(),indexed=$indexed,DatabasePath='$database',DatabaseSize='$DatabaseSize'
	
		WHERE ID={$ligne["ID"]}","artica_backup");
		if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"xapian");}
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	system_admin_events("scanned $count directorie(s) took $took",__FUNCTION__,__FILE__,__LINE__,"xapian");
	
}

function WebCopyIDAddresses($ID){
	$q=new mysql();
	$sql="SELECT useSSL,servername FROM freeweb WHERE WebCopyID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]<>null){
		$method="http";
		if($ligne["useSSL"]==1){$method="https";}
		return "$method://{$ligne["servername"]}";
	}
	
	$sql="SELECT sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	return $ligne["sitename"];
	
}
function WebCopyIDDirectory($ID){
	$q=new mysql();
	$sql="SELECT workingdir,sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$parsed_url=parse_url($ligne["sitename"]);
	$ligne["sitename"]="{$parsed_url["host"]}";	
	return $ligne["workingdir"]."/{$ligne["sitename"]}";
	
}


function TransFormToHtml($file){
	if(!is_file($file)){return false;}
	$original_file=trim(@file_get_contents("$file"));
	
 $attachmentdir=dirname($file);
 $fullmessagesdir=dirname($file);
 $attachmenturl='images.listener.php?mailattach=';   
   $cmd='/usr/bin/mhonarc ';
   $cmd=$cmd."-attachmentdir $attachmentdir ";
   $cmd=$cmd."-attachmenturl $attachmenturl ";
   $cmd=$cmd.'-nodoc ';
   $cmd=$cmd.'-nofolrefs ';
   $cmd=$cmd.'-nomsgpgs ';
   $cmd=$cmd.'-nospammode ';
   $cmd=$cmd.'-nosubjectthreads ';
   $cmd=$cmd.'-idxfname storage ';
   $cmd=$cmd.'-nosubjecttxt "no subject" ';
   $cmd=$cmd.'-single ';
   $cmd=$cmd.$original_file . ' ';
   $cmd=$cmd. ">$attachmentdir/message.html 2>&1";
   system($cmd);
   $size=filesize("$attachmentdir/message.html");
	write_syslog("Creating html  $attachmentdir/message.html ($size bytes)",__FILE__);
	
}

function homes(){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$FOLDERS=array();
	$RFOLDERS=array();
	$unix=new unix();
	$GLOBALS["omindex"]=$unix->find_program("omindex");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){system_admin_events("Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"xapian");die();}
	@file_put_contents($pidfile, getmypid());		
	$nice=EXEC_NICE();
	$t1=time();
	if(!is_file($GLOBALS["omindex"])){system_admin_events("omindex no such binary, aborting",__FUNCTION__,__FILE__,__LINE__,"xapian");return;}
	
	$ldap=new clladp();
	$attr=array("homeDirectory","uid","dn");
	$pattern="(&(objectclass=sambaSamAccount)(uid=*))";
	$sock=new sockets();
	$sock=new sockets();
	$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,".$ldap->suffix,$pattern,$attr);
	$hash=ldap_get_entries($ldap->ldap_connection,$sr);
	$sock=new sockets();
	for($i=0;$i<$hash["count"];$i++){
		$uid=$hash[$i]["uid"][0];
		$homeDirectory=$hash[$i][strtolower("homeDirectory")][0];
		if($uid==null){writelogs("uid is null, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($uid=="nobody"){writelogs("uid is nobody, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($uid=="root"){writelogs("uid is root, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if(substr($uid,strlen($uid)-1,1)=='$'){writelogs("$uid:This is a computer, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($homeDirectory==null){$homeDirectory="/home/$uid";}	
		if(!is_dir($homeDirectory)){continue;}	
		$FOLDERS[$uid]=$homeDirectory;
		$RFOLDERS[$homeDirectory]=true;
	}
	
	$SambaXapianAuth=unserialize(base64_decode($sock->GET_INFO("SambaXapianAuth")));	
	$username=$SambaXapianAuth["username"];
	$password=$SambaXapianAuth["password"];
	$domain=$SambaXapianAuth["domain"];
	$comp=$SambaXapianAuth["ip"];
	if(!isset($SambaXapianAuth["lang"])){$SambaXapianAuth["lang"]=="none";}
	$lang=$SambaXapianAuth["lang"];
	if($lang==null){$lang="none";}	
	$t1=time();
	$dirs=$unix->dirdir("/home");
	$samba=new samba();
	$localdatabase="/usr/share/artica-postfix/LocalDatabases";
	
	while (list ($dir, $ligne) = each ($dirs) ){
		if($dir=="/home/export"){continue;}
		if($dir=="/home/netlogon"){continue;}
		if($dir=="/home/artica"){continue;}
		if($dir=="/home/logs-backup"){continue;}
		if(isset($RFOLDERS[$dir])){continue;}
		if(isset($samba->main_shared_folders[$dir])){continue;}
		$FOLDERS[basename($dir)]=$dir;
	}
	$count=0;
	while (list ($uid, $directory) = each ($FOLDERS) ){	
		
		$BaseUrl=$directory;
		$database="$localdatabase/xapian-$uid";
		@mkdir($database,0755,true);
		if(!is_dir($directory)){system_admin_events("$directory, no such directory",__FUNCTION__,__FILE__,__LINE__,"xapian");continue;}
		$t=time();
		$cmd="$nice{$GLOBALS["omindex"]} -l 0 -s $lang -E 512 -m 60M --follow -D \"$database\" -U \"$BaseUrl\" \"$directory\" -v 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		$results_scan=array();
		exec($cmd,$results_scan);
		$dirRes=ParseLogs($results_scan);		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$count++;
		system_admin_events("scanned $directory took $took indexed:{$dirRes[0]} skipped:{$dirRes[1]}",__FUNCTION__,__FILE__,__LINE__,"xapian");
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	system_admin_events("scanned $count directorie(s) took $took",__FUNCTION__,__FILE__,__LINE__,"xapian");	
}



?>