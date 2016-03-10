<?php
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--simple"){execute_mysql($argv[2]);exit;}
if($argv[1]=="--progress"){build();exit;}



execute_mysql(0);


function execute_mysql($OnlyID=0){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$unix=new unix();
	

	
	$httrack=$unix->find_program("httrack");
	if(!is_file($httrack)){apache_admin_mysql(0,"httrack no such binary",null,__FILE__,__LINE__,"webcopy");return;}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
			apache_admin_mysql(1,"Already instance executed",null,__FILE__,__LINE__,"webcopy");
			return;
	}
	
	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=$sock->GET_INFO("ArticaProxySettings");
	if(trim($datas)<>null){
		$ini->loadString($datas);
		if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]="no";}
		
		$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
		$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
		$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
		$ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
		$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
		if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}
	}
	
	
	

	$PPRoxy=null;
	$userPP=null;
	if($ArticaProxyServerEnabled=="yes"){
		if($ArticaProxyServerUsername<>null){
			$userPP="$ArticaProxyServerUsername:$ArticaProxyServerUserPassword@";
		}
		$PPRoxy=" --proxy $userPP@$ArticaProxyServerName:$ArticaProxyServerPort";
	}else{
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){
			$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			$SquidMgrListenPort=intval($sock->GET_INFO("SquidMgrListenPort"));
			$PPRoxy=" --proxy 127.0.0.1:$SquidMgrListenPort";
		}
		
	}
	
	
	$getmypid=getmypid();
	@file_put_contents($pidfile, $getmypid);	
	$php=$unix->LOCATE_PHP5_BIN();
	$APACHE_USERNAME=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	$q=new mysql();
	$nice=EXEC_NICE();
	$sql="SELECT * FROM httrack_sites WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){apache_admin_mysql(0,"Fatal: $q->mysql_error",null,__FILE__,__LINE__,"webcopy");return;}
	$t1=time();
	$count=0;
	
	if($OnlyID>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM httrack_sites WHERE ID=$OnlyID","artica_backup"));
		$log_exp=" only for [{$ligne2["sitename"]}] ";
	}
	
	apache_admin_mysql(2,"Starting executing WebCopy task $log_exp pid:$getmypid",null,__FILE__,__LINE__,"webcopy");
	$dirsizeG=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		if($OnlyID>0){if($ligne["ID"]<>$OnlyID){continue;}}
		$t=time();	
		$count++;
		$workingdir=$ligne["workingdir"];
		$sitename=$ligne["sitename"];
		$minrate=$ligne["minrate"];
		$maxfilesize=$ligne["maxfilesize"];
		$maxsitesize=$ligne["maxsitesize"];
		$size=$ligne["size"];
		$sizeKB=$size/1024;
		$sizeMB=round($sizeKB/1024,2);
		$maxworkingdir=intval($ligne["maxworkingdir"]);
		if($maxworkingdir==0){$maxworkingdir=20;}
		$maxsitesizeMB=$maxsitesize/1000;
		
		if($maxsitesizeMB>$maxworkingdir){
			$maxsitesize=$maxworkingdir*1000;
		}
		
		if($sizeMB>$maxworkingdir){
			apache_admin_mysql(1,"Skip downloading content of $sitename Directory: {$sizeMB}MB reach limit of {$maxworkingdir}MB",null,__FILE__,__LINE__,"webcopy");
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){
			echo "Dir: Current size:$sizeMB\n";
			echo "Dir: Max size:$maxworkingdir\n";
			
		}
		
		$ResteMB=$maxworkingdir-$sizeMB;
		$ResteKB=$ResteMB*1000;
		
		
		if($maxsitesize>$ResteKB){
			$maxsitesize=$ResteKB;
		}
		
		
		echo "Dir: Max Downloads:$maxsitesize KB\n";
		
		$maxfilesize=$maxfilesize*1000;
		$maxsitesize=$maxsitesize*1000;
		$minrate=$minrate*1000;
		$update=null;
		$resultsCMD=array();
		echo "Dir: Max Downloads:$maxsitesize Bytes\n";
		if(!is_dir($workingdir)){@mkdir($workingdir,0755,true);}
		if(is_file("$workingdir/hts-cache")){$update=" --update";}
		

		
		
		apache_admin_mysql(2,"Starting downloading content of $sitename/$minrate/".FormatBytes($maxsitesize/1000),null,__FILE__,__LINE__,"webcopy");
		
		$cmdline="$httrack \"$sitename\" --quiet$update{$PPRoxy} --max-files=$maxfilesize --max-size=$maxsitesize --max-rate=$minrate -O \"$workingdir\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo"$cmdline\n";}
		exec($cmdline,$resultsCMD);
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $resultsCMD);}
		$dirsize=$unix->DIRSIZE_BYTES($workingdir);
		$dirsizeG=$dirsizeG+$dirsize;
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$dirsizeText=round((($dirsize/1024)/1000),2);
		
		if($GLOBALS["VERBOSE"]){
			echo "Dir: Current size:$sizeMB\n";
			echo "Dir: New size....:{$dirsizeText}MB\n";
				
		}
		
		
		apache_admin_mysql(2,"$sitename scrapped took $took size=$dirsizeText MB",@implode("\n", $resultsCMD),__FILE__,__LINE__,"webcopy");
		$q->QUERY_SQL("UPDATE httrack_sites SET size='$dirsize' WHERE ID={$ligne["ID"]}","artica_backup");
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	
	@chmod($workingdir,0755);
	@chmod(dirname($workingdir),0755);
	$chown=$unix->find_program("chown");
	shell_exec("$chown -R $APACHE_USERNAME:$APACHE_SRC_GROUP $workingdir");
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/HTTRackSize",$dirsizeG);
	if($count>0){
		apache_admin_mysql(2,"$count web sites scrapped took $took",null,__FILE__,__LINE__,"webcopy");
	}
	system("$php /usr/share/artica-postfix/exec.syslog-engine.php --apache");
}

function build_progress($text,$pourc){

	$cachefile="/usr/share/artica-postfix/ressources/logs/web/freewebs.HTTrack.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_apache_ON($ID,$dir){

	$file="/etc/apache2/conf.d/HTTrack-$ID";

	$f[]="<IfModule mod_alias.c>";
	$f[]="\tAlias /HTTrack$ID/ \"{$dir}/\"";
	$f[]="\t<Directory \"{$dir}/\">";
	$f[]="\t\tAllowOverride None";
	$f[]="\t\tOrder allow,deny";
	$f[]="\t\tAllow from all";
	$f[]="\t</Directory>";
	$f[]="</IfModule>";
	$f[]="";

	@file_put_contents($file, @implode("\n", $f));
	@chmod($file,0755);
	build_progress("{building} {website} $ID {done}",18);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb", 1);
	
}
function build_apache_OFF(){

	
	$unix=new unix();
	$files=$unix->DirFiles("/etc/apache2/conf.d","HTTrack-[0-9]+");
	while (list ($num, $line) = each ($dirfiles)){
		$webappFile="/etc/apache2/conf.d/$num";
		@unlink($webappFile);
	}

}

function build_IsInSquid(){

	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list($num,$val)=each($f)){
		if(preg_match("#url_rewrite_program.*?\/ufdbgclient\.php#", $val)){return true;}

	}
}

function build_ufdb(){

	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==1){
		build_progress_build("{building} {webfiltering} {enabled} OK",12);

	}else{
		build_progress_build("{building} {webfiltering} {activate} OK",12);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
	}

	if(!build_IsInSquid()){
		build_progress_build("{building} {reconfigure_proxy_service}...",14);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress_build("{building} {reconfigure_proxy_service} {done}",16);
	}

	build_progress_build("{building} {webfiltering} {done}",18);

}

function build(){
	$unix=new unix();
	
	$q=new mysql();
	$sql="SELECT COUNT(*) as tcount FROM httrack_sites WHERE enabled=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress_build("MySQL {failed}!",110);
		return;
	}
	
	$Tcount=intval($ligne["tcount"]);
	$HTTrackInSquid=0;
	if($Tcount>0){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HTTrackInSquid",1);
		$HTTrackInSquid=1;
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HTTrackInSquid",0);
		$HTTrackInSquid=0;
	}
	
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		if($HTTrackInSquid==1){
			build_progress("{enabled}",10);
			if(!build_IsInSquid()){build_ufdb();}
			if(!build_IsInSquid()){
				build_progress_build("{building} {failed} build_IsInSquid!",110);
				return;
			}
		}else{
			build_progress("{disabled}",10);
			
		}
	}
	
	
	
	$MAIN=array();
	build_apache_OFF();
	$sql="SELECT * FROM httrack_sites WHERE enabled=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		
		$path=null;
		$dir=$ligne["workingdir"];
		build_apache_ON($ID,$dir);
		$sitename=$ligne["sitename"];
		build_progress("{building} $sitename",22);
		
		$uri=parse_url($sitename);
		$host=$uri["host"];
		$srhost=$host;
		if(isset($uri["path"])){
			$path=$uri["path"];
			$path=str_replace("/", "\/", $path);
			$path=str_replace(".", "\.", $path);
		}
		$host=str_replace(".", "\.", $host);
		
		$MAIN["$host$path"]="HTTrack$ID/$srhost";
	}
	
	
	@file_put_contents("/etc/squid3/HTTrack.db", serialize($MAIN));
	@chown("/etc/squid3/HTTrack.db","squid");
	@chmod("/etc/artica-postfix/settings/Daemons/HTTrackInSquid", 0755);
	build_progress("{building} {restarting} {webservice}",50);
	system("/etc/init.d/apache2 restart");
	if(is_file($squidbin)){
		system("$squidbin -k reconfigure");
	}
	
	if($HTTrackInSquid==0){
		build_progress("{building} {restarting} {webservice} {done} - DISABLED - $Tcount {websites}",110);
		return;
	}
	
	build_progress("{building} {restarting} {webservice} {done}",100);
	
	
}


