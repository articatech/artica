<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["NO_BUILD_MAIN"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--no-buildmain#",implode(" ",$argv),$re)){$GLOBALS["NO_BUILD_MAIN"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');

if($argv[1]=="--remove"){remove_website($argv[2]);exit;}

compile_site($argv[1]);

function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-single.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}


}


function remove_website($servername){
	if(trim($servername)==null){return;}	
	$servername_org=$servername;
	$servername=trim(strtolower($servername));
	$servername=str_replace(".", "\.", $servername);
	$REMOVED=false;
	
	$dirs[]="/etc/nginx/sites-enabled-backuped";
	$dirs[]="/etc/nginx/local-sites";
	$dirs[]="/etc/nginx/sites-enabled";
	
	while (list ($index, $directory) = each ($dirs)){
		build_progress("$servername_org: {analyze} $directory ",80);
		$c=0;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Remove $servername_org from $directory\n";}
		foreach (glob("$directory/*") as $filename) {
			
			if(preg_match("#$servername#", basename($filename))){
				nginx_admin_mysql(1, "$filename was deleted", "Asked to remove the \"$servername\" pattern",__FILE__,__LINE__);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Remove old file $filename\n";}
				@unlink($filename);
				$REMOVED=true;
				$c++;
			}else{
				if($GLOBALS["VERBOSE"]){echo basename($filename) . " NO MATCH $servername\n";}
			}
				
		}
	}
	
if($REMOVED){	
	if(!$GLOBALS["NO_RELOAD"]){
		if(!$GLOBALS["NO_BUILD_MAIN"]){
			nginx_admin_mysql(1, "Restarting service after deleting $c unused site(s)",__FILE__,__LINE__);
			build_progress("$servername_org: {stopping_reverse_proxy} ",90);
			system("/etc/init.d/nginx stop --force");
			build_progress("$servername_org: {starting_reverse_proxy} ",95);
			system("/etc/init.d/nginx start --force");
		}else{
			build_progress("{reloading_reverse_proxy} skipped",90);
			build_progress("$servername_org: {done}",100);
		}
	}else{
		build_progress("{reloading_reverse_proxy} skipped",90);
		
	}	
}
	
	
}



function compile_site($servername){
	$servername=trim(strtolower($servername));
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$servername.".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx $cmdline\n";}
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	$php=$unix->LOCATE_PHP5_BIN();
	
	
	$sql="SELECT servername from freeweb WHERE servername='$servername'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]<>null){
		build_progress("{reconfigure} $servername",10);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:  $servername is a freeweb\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:  *** NOTICE *** $servername is a freeweb\n";}
		configure_single_freeweb($servername);
		shell_exec("$php /usr/share/artica-postfix/exec.nginx.wizard.php --avail-status --force >/dev/null 2>&1 &");
		if(!$GLOBALS["NO_RELOAD"]){
			if(!$GLOBALS["NO_BUILD_MAIN"]){
				build_progress("{$ligne["servername"]}: {reloading_reverse_proxy} ",80);
				system("/etc/init.d/nginx reload --force");
				build_progress("{$ligne["servername"]}: {reloading_reverse_proxy}  {done}",100);
			}
		}
		return;
	
	}
	
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1 AND servername='$servername'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if($ligne["servername"]==null){
		remove_website($servername);
		echo "servername row is null ??...\n";
		build_progress("{reconfigure} $servername {disabled}",110);
		return;
	}
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."] $servername $q->mysql_error\n";}return;}
	
		build_progress("{reconfigure} $servername",10);
		if(!BuildReverse($ligne,true)){return;}
		if(!$GLOBALS["NO_BUILD_MAIN"]){
			build_progress("{building_main_settings}",85);
			system("$php /usr/share/artica-postfix/exec.nginx.php --main");
		}else{
			build_progress("{building_main_settings} skipped",85);
		}
		
		
	shell_exec("$php /usr/share/artica-postfix/exec.nginx.wizard.php --avail-status --force >/dev/null 2>&1 &");

	if(!$GLOBALS["NO_RELOAD"]){		
		if(!$GLOBALS["NO_BUILD_MAIN"]){
			build_progress("{$ligne["servername"]}: {stopping_reverse_proxy} ",90);
			system("/etc/init.d/nginx stop --force");
			build_progress("{$ligne["servername"]}: {starting_reverse_proxy} ",95);
			system("/etc/init.d/nginx start --force");
			build_progress("{$ligne["servername"]}: {reloading_reverse_proxy}  {done}",100);
		}else{
			build_progress("{reloading_reverse_proxy} skipped",90);
			build_progress("{$ligne["servername"]}: {done}",100);
		}
	}else{
		build_progress("{reloading_reverse_proxy} skipped",90);
		build_progress("{$ligne["servername"]}: {done}",100);
	}
	
	
	
	
}

function configure_single_freeweb($servername){
	$q=new mysql();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$servername'","artica_backup"));
	$free=new freeweb($servername);
	$NginxFrontEnd=$free->NginxFrontEnd;
	$groupware=$free->groupware;

	build_progress("$servername: $groupware",20);

	$NOPROXY["SARG"]=true;
	$NOPROXY["ARTICA_MINIADM"]=true;
	$NOPROXY["WORDPRESS"]=true;
	$NOPROXY[null]=true;

	$q2=new mysql_squid_builder();
	$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT cacheid FROM reverse_www WHERE servername='{$ligne["servername"]}'"));


	$host=new nginx($servername);
	if($host->owa==1){
		$nginx_exchange=new nginx_exchange($servername);
		build_progress("$servername: {building}",50);
		$nginx_exchange->buildConfig();
		build_progress("$servername: {building} {done}",90);
		return;
	}
	


	if(isset($NOPROXY[$groupware])){
		build_progress("$servername: compile as FRONT-END",30);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername compile as FRONT-END\n";}
		$free->CheckWorkingDirectory();
		$host->set_proxy_disabled();
		$host->set_DocumentRoot($free->WORKING_DIRECTORY);
		if($groupware=="SARG"){$host->SargDir();}
		if($groupware=="WORDPRESS"){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,$php /usr/share/artica-postfix/exec.wordpress.php \"$servername\"\n";}
			system("$php /usr/share/artica-postfix/exec.wordpress.php \"$servername\"");
			$host->WORDPRESS=true;
			$host->set_index_file("index.php");
				
		}
	}else{
		build_progress("$servername: {building}",30);
		$host->set_freeweb();
		$host->set_storeid($ligne2["cacheid"]);

	}
	if($free->groupware=="Z-PUSH"){$host->NoErrorPages=true;}
	if($free->groupware=="WORDPRESS"){$host->WORDPRESS=true;}
	$host->set_servers_aliases($free->Params["ServerAlias"]);

	if($groupware=="ZARAFA"){
		if($free->NginxFrontEnd==1){
			$host->groupware_zarafa_Frontend();
			return;
		}
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername building configuration...\n";}
	build_progress("$servername: {building}",50);
	$host->build_proxy();
	build_progress("$servername: {building} {done}",90);

}

function LoadConfigs(){
	if(isset($GLOBALS["LoadConfigs"])){return;}
	$GLOBALS["REMOVE_LOCAL_ADDR"]=false;
	$unix=new unix();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM reverse_www WHERE default_server=0"));
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:  *** FATAL ** $q->mysql_error\n";}return;}
	if($ligne["tcount"]>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx *** NOTICE *** Defaults websites as been defined, no IP addresses are allowed\n";}
		$EnableArticaFrontEndToNGninx=0;$GLOBALS["REMOVE_LOCAL_ADDR"]=true;
	}

	if($GLOBALS["REMOVE_LOCAL_ADDR"]){$GLOBALS["IPADDRS"]=$unix->NETWORK_ALL_INTERFACES(true);unset($GLOBALS["IPADDRS"]["127.0.0.1"]);}
	$GLOBALS["LoadConfigs"]=true;
}

function BuildReverse($ligne,$backupBefore=false){
	$T1=time();
	$q=new mysql_squid_builder();
	$unix=new unix();
	$ligne["servername"]=trim($ligne["servername"]);
	$GLOBALS["IPADDRS"]=$unix->NETWORK_ALL_INTERFACES(true);
	$IPADDRS=$GLOBALS["IPADDRS"];
	$DenyConf=$ligne["DenyConf"];
	$ligne["servername"]=trim($ligne["servername"]);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."]  ************* {$ligne["servername"]}:{$ligne["port"]} / $DenyConf ************* \n";}

	if($ligne["port"]==82){
		echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."] 82 port is an apache port, SKIP\n";
		build_progress("Bad port {$ligne["servername"]}:82",110);
		return;
	}

	if($GLOBALS["REMOVE_LOCAL_ADDR"]){
		if(isset($IPADDRS[$ligne["servername"]])){
			build_progress("{$IPADDRS[$ligne["servername"]]} *** SKIPPED ***",110);
			echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."]  {$ligne["servername"]} *** SKIPPED ***\n";
			return;
		}
	}


	if($DenyConf==1){
		build_progress("Denied config *** SKIPPED ***",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."]  Local web site `{$ligne["servername"]}`, DenyConf = 1,skipped\n";}
		return;
	}

	if(isset($ALREADYSET[$ligne["servername"]])){
		build_progress("Already setup",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: [".__LINE__."]  `{$ligne["servername"]}` Already defined, abort\n";}
		return;
	}
	
	
	$ListenPort=$ligne["port"];
	$SSL=$ligne["ssl"];
	$certificate=$ligne["certificate"];
	echo "Starting......: ".date("H:i:s")." [INIT]:  ListenPort..............:$ListenPort\n";
	echo "Starting......: ".date("H:i:s")." [INIT]:  SSL.....................:$SSL\n";
	echo "Starting......: ".date("H:i:s")." [INIT]:  Certificate.............:$certificate\n";
	echo "Starting......: ".date("H:i:s")." [INIT]:  OWA.....................:{$ligne["owa"]}\n";
	
	
	if($ligne["owa"]==1){
		$GLOBALS["OUTPUT"]=true;
		$nginx_exchange=new nginx_exchange($ligne["servername"]);
		build_progress("{$ligne["servername"]}: {building} Microsoft Exchange Configuration",50);
		$nginx_exchange->buildConfig();
		build_progress("{$ligne["servername"]}: {building} Microsoft Exchange Configuration {done}",70);
		$Took=distanceOfTimeInWords($T1,time(),true);
		nginx_admin_mysql(2, "Success build Microsoft Exchange Configuration configuration for {$ligne["servername"]} took: $Took", "Took: $Took",__FILE__,__LINE__);
		build_progress("{$ligne["servername"]}: Microsoft Exchange Configuration {done}",80);
		return true;
		
	}
	
	
	build_progress("{$ligne["servername"]}:$ListenPort [SSL:$SSL]",20);
	echo "Starting......: ".date("H:i:s")." [INIT]: Protect remote web site `{$ligne["servername"]}:$ListenPort [SSL:$SSL]`\n";
	
	
	if($ligne["servername"]==null){
		echo "Starting......: ".date("H:i:s")." [INIT]:  skip it...\n";
		return;
	}
	
	
	$cache_peer_id=$ligne["cache_peer_id"];
	if($cache_peer_id>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `reverse_sources` WHERE `ID`='$cache_peer_id'"));
	}

	$host=new nginx($ligne["servername"]);

	if($ListenPort==80 && $SSL==1){
		build_progress("{$ligne["servername"]}: Building HTTP",40);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:  HTTP/HTTPS Enabled [".__LINE__."]...\n";}
		$host->set_RedirectQueries($ligne["RedirectQueries"]);
		$host->set_forceddomain($ligne2["forceddomain"]);
		$host->set_ssl(0);
		$host->set_mixed_ssl(1);
		$host->set_proxy_port($ligne2["port"]);
		$host->set_listen_port(80);
		$host->set_poolid($ligne["poolid"]);
		$host->set_owa($ligne["owa"]);
		$host->set_storeid($ligne["cacheid"]);
		$host->set_cache_peer_id($cache_peer_id);
		$host->BackupBefore=$backupBefore;
		build_progress("{$ligne["servername"]}: HTTP/HTTPS Enabled",50);
		$GLOBALS["NGINX_FATAL_ERRORS"]=array();
		if(!$host->build_proxy()){
			if($GLOBALS["NGINX_FATAL_ERROR"]<>null){
				nginx_admin_mysql(0, "Fatal error on {$ligne["servername"]} <{$GLOBALS["NGINX_FATAL_ERROR"]}>", "{$GLOBALS["NGINX_FATAL_ERROR"]}\n".@implode("\n", $GLOBALS["NGINX_FATAL_ERRORS"]));
				echo "***                                             ***\n";
				echo "*** Fatal error {$GLOBALS["NGINX_FATAL_ERROR"]} ***\n";
				echo "***                                             ***\n";
				build_progress("{$ligne["servername"]}: {failed} {$GLOBALS["NGINX_FATAL_ERROR"]}",110);
				return;
			}
			build_progress("{$ligne["servername"]}: {failed}",110);
			return;
		}
		
		if(!$GLOBALS["NO_RELOAD"]){
			build_progress("{$ligne["servername"]}: {done}",80);
			return true;
		}
	}

	if($ligne["ssl"]==1){
		echo "Starting......: ".date("H:i:s")." [INIT]:  SSL Enabled...\n";
		$ligne2["ssl"]=1;
	}

	if($ligne["port"]==443){ $ligne2["ssl"]=1; }
	build_progress("{$ligne["servername"]}",50);
	$host->BackupBefore=$backupBefore;
	$host->set_owa($ligne["owa"]);
	$host->set_RedirectQueries($ligne["RedirectQueries"]);
	$host->set_ssl_certificate($certificate);
	$host->set_ssl_certificate($ligne2["ssl_commname"]);
	$host->set_forceddomain($ligne2["forceddomain"]);
	$host->set_ssl($ligne2["ssl"]);
	$host->set_proxy_port($ligne2["port"]);
	$host->set_listen_port($ligne["port"]);
	$host->set_poolid($ligne["poolid"]);
	$host->set_owa($ligne["owa"]);
	$host->set_storeid($ligne["cacheid"]);
	$host->set_cache_peer_id($cache_peer_id);
	$host->build_proxy();
	
	if($GLOBALS["NGINX_FATAL_ERROR"]<>null){
		nginx_admin_mysql(0, "Fatal error on {$ligne["servername"]} <{$GLOBALS["NGINX_FATAL_ERROR"]}>", "{$GLOBALS["NGINX_FATAL_ERROR"]}\n".@implode("\n", $GLOBALS["NGINX_FATAL_ERRORS"]),__FILE__,__LINE__);
		echo "*** Fatal error {$GLOBALS["NGINX_FATAL_ERROR"]} ***\n";
		build_progress("{$ligne["servername"]}: {failed}",110);
		return;
	}
	
	$Took=distanceOfTimeInWords($T1,time(),true);
	nginx_admin_mysql(2, "Success build configuration for {$ligne["servername"]} took: $Took", "Took: $Took",__FILE__,__LINE__);
	build_progress("{$ligne["servername"]}: {done}",80);
	return true;
	
	

}