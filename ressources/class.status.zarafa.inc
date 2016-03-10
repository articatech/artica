<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}




function zarafa_increment_func($array){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){ if(function_exists("LoadIncludes")){LoadIncludes();}else{return $array;} }
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$EnableStopPostfix=1;$EnablePostfixMultiInstance=0;}
	if($DisableMessaging==1){return $array;}
	if($EnableStopPostfix==1){return $array;}
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){return $array;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	if($enabled==0){return $array;}
	
	$array[]="zarafa_server";
	$array[]="zarafa_dagent";
	$array[]="zarafa_db";
	$array[]="zarafa_monitor";
	$array[]="zarafa_search";
	$array[]="zarafa_spooler";
	$array[]="zarafa_ical";
	$array[]="zarafa_indexer";
	$array[]="zarafa_mapi";
	$array[]="zarafa_web";
	return $array;
}


function zarafa_mapi(){
	if(!_zarafa_checkExtension("mapi", "5.0-4688", "Mapi error, please contact Artica support team.")){
		if($GLOBALS["VERBOSE"]){echo "Warning Zarafa mapi php extension error {$GLOBALS["ZARAFA_ERROR"]}\n";}
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Zarafa mapi php extension error",$GLOBALS["ZARAFA_ERROR"],"mailbox");

	}
}

function _zarafa_error_version($name, $needed, $found, $help){return sprintf("Version error: %s %s found, but %s needed.\n",$name, $needed, $found);}
function _zarafa_error_notfound($name, $help){return sprintf("Not Found: %s not found", $name);}

function zarafa_web_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/zarafa-web/httpd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$apachebin=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$apachebin.*?/etc/zarafa/httpd.conf");

}

function zarafa_web(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaApacheEnable");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-web/httpd.pid";
	$master_pid=zarafa_web_pid();

	$l[]="[APP_ZARAFA_WEB]";
	$l[]="service_name=APP_ZARAFA_WEB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-web";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/zarafa-web start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================


function zarafa_licensed(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=1;

	$pid_path="/var/run/zarafa-licensed.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	if($enabled==1){
		$enabledGLobal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
		if(is_numeric($enabledGLobal)){if($enabledGLobal==0){$enabled=0;}}
	}

	$l[]="[APP_ZARAFA_LICENSED]";
	$l[]="service_name=APP_ZARAFA_LICENSED";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-licensed";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-licensed start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

//========================================================================================================================================================
function zarafa_indexer(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INDEXER_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-indexer");
	if(!is_file($binpath)){return;}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaIndexer");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/zarafa-indexer.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	if($enabled==1){
		$enabledGlobal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
		if(is_numeric($enabledGlobal)){if($enabledGlobal==0){$enabled=0;}}
	}

	$l[]="[APP_ZARAFA_INDEXER]";
	$l[]="service_name=APP_ZARAFA_INDEXER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-indexer";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-indexer start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================


function zarafa_ical(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaiCalEnable");
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/zarafa-ical.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_ICAL]";
	$l[]="service_name=APP_ZARAFA_ICAL";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-ical";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-ical start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================


function zarafa_server(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	if(is_file("/etc/artica-postfix/ZARFA_FIRST_INSTALL")){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zarafa.build.stores.php --remove-database >/dev/null 2>&1 &");
		@unlink("/etc/artica-postfix/ZARFA_FIRST_INSTALL");
	}

	$enabled=1;
	$pid_path="/var/run/zarafa-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$master_version=$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="[APP_ZARAFA_SERVER]";
	$l[]="service_name=APP_ZARAFA_SERVER";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/zarafa-server";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";

	if(is_dir("/usr/share/zarafa-webaccess")){
		if(!is_file("/usr/share/zarafa-webaccess/VERSION")){
			@file_put_contents("/usr/share/zarafa-webaccess/VERSION", $master_version);
		}
	}

	if(!is_file("/etc/artica-postfix/LOCAL_GEN_EXECUTED")){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.locale.gen.php >/dev/null 2>&1 &");
		events("running $cmd",__FUNCTION__,__LINE__);
		shell_exec2($cmd);
	}



	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("/etc/init.d/zarafa-server start");
		}
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}



	$meme=GetMemoriesOf($master_pid);
	$l[]=$meme;
	$l[]="";
	$l[]="[APP_ZARAFA]";
	$l[]="service_name=APP_ZARAFA";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="family=mailbox";
	$l[]="service_cmd=/etc/init.d/zarafa-server";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/zarafa-server start");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup")." ";
		if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();}
		$cmd=trim($nohup."{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa.build.stores.php --export-hash >/dev/null 2>&1 &");
		events("running $cmd",__FUNCTION__,__LINE__);
		shell_exec2($cmd);
	}

	zarafa_mapi();
	$l[]="running=1";
	$l[]=$meme;
	$l[]="";

	$filetime="/etc/artica-postfix/pids/exec.zarafa.build.stores.php.exoprhs.time";
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time_file>60){
		@unlink($filetime);
		@file_put_contents($filetime, time());
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa.build.stores.php --exoprhs >/dev/null 2>&1 &");
	}

	$filetime="/etc/artica-postfix/pids/exec.zarafa-backup.php.Clean_dirs.time";
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time_file>1440){

		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa-backup.php --clean-dirs >/dev/null 2>&1 &");
	}

	return implode("\n",$l);;
}
//========================================================================================================================================================
function zarafa_dagent(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/zarafa-dagent.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_DAGENT]";
	$l[]="service_name=APP_ZARAFA_DAGENT";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-dagent";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-dagent start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_monitor(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-monitor.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_MONITOR]";
	$l[]="service_name=APP_ZARAFA_MONITOR";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-monitor";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-monitor start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_search(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_SEARCH_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaSearch");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-search.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_SEARCH]";
	$l[]="service_name=APP_ZARAFA_SEARCH";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-search";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";



	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-search start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================


function zarafa_gateway(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}


	$ZarafaPop3Enable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaPop3Enable");
	$ZarafaPop3sEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaPop3sEnable");
	$ZarafaIMAPEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaIMAPEnable");
	$ZarafaIMAPsEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaIMAPsEnable");

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}

	if(!is_numeric($ZarafaPop3Enable)){$ZarafaPop3Enable=1;}
	if(!is_numeric($ZarafaPop3sEnable)){$ZarafaPop3sEnable=0;}
	if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
	if(!is_numeric($ZarafaIMAPsEnable)){$ZarafaIMAPsEnable=0;}
	$ZarafaPop3Enable=intval($ZarafaPop3Enable);
	$ZarafaPop3sEnable=intval($ZarafaPop3sEnable);
	$ZarafaIMAPEnable=intval($ZarafaIMAPEnable);
	$ZarafaIMAPsEnable=intval($ZarafaIMAPsEnable);

	$total=$ZarafaIMAPsEnable+$ZarafaPop3Enable+$ZarafaPop3sEnable+$ZarafaIMAPEnable;
	if($total==0){$enabled=0;}
	$pid_path="/var/run/zarafa-gateway.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_ZARAFA_GATEWAY]";
	$l[]="service_name=APP_ZARAFA_GATEWAY";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-gateway";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/zarafa-gateway start");
		$master_pid=trim(@file_get_contents($pid_path));
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-gateway start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function zarafa_spooler(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-spooler.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ZARAFA_SPOOLER]";
	$l[]="service_name=APP_ZARAFA_SPOOLER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-spooler";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/zarafa-spooler start >/dev/null 2>&1 &");
		}

		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function ZARAFA_DB_VERSION(){
	if(isset($GLOBALS["ZARAFA_DB_VERSION"])){return $GLOBALS["ZARAFA_DB_VERSION"];}
	exec("/opt/zarafa-db/bin/mysqld --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["ZARAFA_DB_VERSION"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}

//========================================================================================================================================================
function zarafa_db(){

	$ZarafaMySQLServiceType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaMySQLServiceType");
	if(!is_numeric($ZarafaMySQLServiceType)){$ZarafaMySQLServiceType=1;}
	$ZarafaDedicateMySQLServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	if($ZarafaDedicateMySQLServer==0){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDBEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-db.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ZARAFA_DB]";
	$l[]="service_name=APP_ZARAFA_DB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/zarafa-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?--pid-file=/var/run/zarafa-db.pid");
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.zarafa-db.php --start >/dev/null 2>&1");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zarafa-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}
//========================================================================================================================================================
