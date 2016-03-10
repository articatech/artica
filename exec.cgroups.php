<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["RELOAD_STATUS"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.status.hardware.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reload-status#",implode(" ",$argv))){$GLOBALS["RELOAD_STATUS"]=true;}


if($argv[1]=='--build'){build();die();}
if($argv[1]=='--start'){start();die();}
if($argv[1]=='--restart'){restart();die();}
if($argv[1]=='--stop'){stop();die();}
if($argv[1]=='--reload'){reload();die();}

if($argv[1]=='--services-check'){services_check();exit();}
if($argv[1]=="--cgred-start"){cgred_start();exit;}
if($argv[1]=="--cgred-stop"){cgred_stop();exit;}
if($argv[1]=="--ismounted"){ismounted();exit;}
if($argv[1]=="--stats"){buildstats();exit;}
if($argv[1]=="--tasks"){TaskSave();exit;}
if($argv[1]=="--install-progress"){install();exit;}




function restart(){stop();start();}

function is_cgroup_mounted($path){
	$path=str_replace("/", "\/", $path);
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#\/cgroups\/$path\s+#",$ligne)){return true;}
		if($GLOBALS["VERBOSE"]){echo "is_cgroup_mounted:: $ligne NO MATCH \/cgroups\/$path\s+\n";}
	}
	return false;
}

function ismounted(){
	load_family();
	reset($GLOBALS["CGROUPS_FAMILY"]);
		while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if(!is_cgroup_structure_mounted($structure)){
				echo "Starting......: ".date("H:i:s")." cgroups: structure:$structure is not mounted\n";
			}else{
				echo "Starting......: ".date("H:i:s")." cgroups: structure:$structure is mounted\n";
			}
		}	
}

function is_old_debian_mounted(){
	$array=array();
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^cgroup\s+(.+?)\s+cgroup#",$ligne,$re)){
			$array[]=$re[1];
		}
		
	}
	
	return $array;
	
}

function is_cgroup_structure_mounted($structure){
	$structure=str_replace("/", "\/", $structure);
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#\/cgroups\/$structure\s+#",$ligne)){
			if($GLOBALS["VERBOSE"]){echo "is_cgroup_structure_mounted:: $ligne \$MATCH$ \/cgroups\/$structure\s+\n";}
			return true;}
		if($GLOBALS["VERBOSE"]){echo "is_cgroup_structure_mounted:: $ligne NO MATCH \/cgroups\/$structure\s+\n";}
	}
	return false;
}

function load_family(){
	$f=explode("\n", @file_get_contents("/proc/cgroups"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^([a-z\_]+)\s+#", $ligne,$re)){
			if($re[1]=="net_cls"){continue;}
			if($re[1]=="freezer"){continue;}
			if($re[1]=="devices"){continue;}
			$GLOBALS["CGROUPS_FAMILY"][$re[1]]=true;
		}
	}
}

function testStructure($group,$structure,$MyPid){
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	echo "Starting......: ".date("H:i:s")." cgroups: testing structure $structure on group $group for my PID:$MyPid\n";
	if(!is_dir("/cgroups/$structure/$group")){
		echo "Starting......: ".date("H:i:s")." cgroups: testing structure /cgroups/$structure/$group no such directory...\n";
		return false; 
	}
	
	if(!is_file("/cgroups/$structure/$group/tasks")){
		echo "Starting......: ".date("H:i:s")." cgroups: testing structure /cgroups/$structure/$group/tasks no such file...\n";
		return false; 		
	}
	
	
	$echobin=$GLOBALS["CLASS_UNIX"]->find_program("echo");
	if(!is_file($echobin)){
		echo "Starting......: ".date("H:i:s")." cgroups: testing structure 'echo' no such binary...\n";
		return false; 
	}

	exec("$echobin $MyPid >/cgroups/$structure/$group/tasks 2>&1",$results);
	$line=trim(@implode("", $results));
	if(strlen($line)>5){
		echo "Starting......: ".date("H:i:s")." cgroups: testing structure failed \"$line\"...\n";
		return false;
	}
	return true;
	
}


function build(){
		if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
		$catBin=$GLOBALS["CLASS_UNIX"]->find_program("cat");
		$MyPid=getmypid();
		services_check();
		load_family();
		$unix=new unix();
		$cgcreate=$unix->find_program("cgcreate");
		$echobin=$unix->find_program("echo");
		$f[]="\nmount {";
		
		Mount_structure(); 
		reset($GLOBALS["CGROUPS_FAMILY"]);
		while (list ($num, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if(is_cgroup_structure_mounted($num)){
			echo "Starting......: ".date("H:i:s")." cgroups: supported structure:$num\n";
			$f[]="\t$num = /cgroups/$num;";
			}
		}
		$f[]="}\n";
		reset($GLOBALS["CGROUPS_FAMILY"]);
		$DirMounts[]="cpu";
		$DirMounts[]="memory";
		$DirMounts[]="cpuacct";

		if(!is_dir("/cgroups")){@mkdir("/cgroups",0755,true);}

		
		
		echo "Starting......: ".date("H:i:s")." cgroups: Writing /etc/cgconfig.conf\n";
		@file_put_contents("/etc/cgconfig.conf", @implode("\n", $f));
		echo "Starting......: ".date("H:i:s")." cgroups: Writing /etc/cgrules.conf\n";
		@file_put_contents("/etc/cgrules.conf", @implode("\n", $GLOBALS["CGRULES_CONF"]));
		if(file_exists("/etc/sysconfig/cgconfig")){@file_put_contents("/etc/sysconfig/cgconfig", @implode("\n", $f));}


	if(is_file("/etc/sysconfig/cgred.conf")){
		$u[]="CONFIG_FILE=\"/etc/cgrules.conf\"";
		$u[]="LOG_FILE=\"/var/log/cgrulesengd.log\"";
		$u[]="NODAEMON=\"\"";
		$u[]="SOCKET_USER=\"\"";
		$u[]="SOCKET_GROUP=\"cgred\"";
		$u[]="LOG=\"\"";
		@file_put_contents("/etc/sysconfig/cgred.conf", @implode("\n", $f));
		
	}
	if(is_file("/etc/default/cgred.conf")){
		$u[]="CONFIG_FILE=\"/etc/cgrules.conf\"";
		$u[]="LOG_FILE=\"/var/log/cgrulesengd.log\"";
		$u[]="NODAEMON=\"\"";
		$u[]="SOCKET_USER=\"\"";
		$u[]="SOCKET_GROUP=\"cgred\"";
		$u[]="LOG=\"\"";
		@file_put_contents("/etc/default/cgred.conf", @implode("\n", $f));
		
	}


	$p[]="CREATE_DEFAULT=yes";
	@file_put_contents("/etc/default/cgconfig", @implode("\n", $p));
	
	$sock=new sockets();
	$cgroupsPHPCpuShares=intval($sock->GET_INFO("cgroupsPHPCpuShares"));
	$cgroupsPHPDiskIO=intval($sock->GET_INFO("cgroupsPHPDiskIO"));
	if($cgroupsPHPCpuShares==0){$cgroupsPHPCpuShares=256;}
	if($cgroupsPHPDiskIO==0){$cgroupsPHPDiskIO=450;}
	
	$cgroupsMySQLCpuShares=intval($sock->GET_INFO("cgroupsMySQLCpuShares"));
	$cgroupMySQLDiskIO=intval($sock->GET_INFO("cgroupsMySQLDiskIO"));
	if($cgroupsMySQLCpuShares==0){$cgroupsMySQLCpuShares=620;}
	if($cgroupMySQLDiskIO==0){$cgroupMySQLDiskIO=800;}
	
	
	limit_service_structure("php",$cgroupsPHPCpuShares,0,$cgroupsPHPDiskIO);
	limit_service_structure("mysql",$cgroupsMySQLCpuShares,0,$cgroupMySQLDiskIO);

	if($GLOBALS["RELOAD_STATUS"]){
		shell_exec("/etc/init.d/artica-status restart --force");
	}
	
	
}

function limit_service_structure($groupname,$cpu_shares,$cpus,$blkio){
	$unix=new unix();
	$echobin=$unix->find_program("echo");
	create_service_structure($groupname);
	echo "Starting......: ".date("H:i:s")." cgroups Limiting $groupname to $cpu_shares share CPU:#$cpus I/O $blkio limit\n";
	system("$echobin $cpu_shares > /cgroups/cpu/$groupname/cpu.shares");
	system("$echobin $cpus > /cgroups/cpuset/$groupname/cpuset.cpus");
	system("$echobin 0 >/cgroups/cpuset/$groupname/cpuset.mems");
	system("$echobin $blkio >/cgroups/blkio/$groupname/blkio.weight");
	
}

function create_service_structure($groupname){
	$subgroups[]="cpuset";
	$subgroups[]="blkio";
	$subgroups[]="cpu";
	$unix=new unix();
	$cgcreate=$unix->find_program("cgcreate");
	$CREATED=true;
	
	while (list ($num, $ligne) = each ($subgroups)){
		if(!is_dir("/cgroups/$ligne/$groupname")){$CREATED=false;break;}
		
	}
	
	if($CREATED){return;}
	shell_exec("$cgcreate -a root -g cpu,cpuset,blkio:$groupname");
}

function stop(){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." cgroups: Already pid $pid is running, aborting\n";return;}
	@file_put_contents($pidfile, getmypid());
	
	
	$GLOBALS["CGROUPS_FAMILY"]=array();
	load_family();
	echo "Starting......: ".date("H:i:s")." cgroups: stopping daemons\n";
	echo "Starting......: ".date("H:i:s")." cgroups: stopping cgred\n";
	if(is_file("/etc/init.d/cgred")){shell_exec("/etc/init.d/cgred stop");}
	
		
		$umount=$unix->find_program("umount");
		$mount=$unix->find_program("mount");
		$rm=$unix->find_program("rm");
		$echo=$unix->find_program("echo");

	
		while (list ($num, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if(is_cgroup_structure_mounted($num)){
				echo "Starting......: ".date("H:i:s")." cgroups: unmount structure:$num\n";
				$results=array();
				exec("$umount -l /cgroups/$num  2>&1",$results);
				if(count($results)>1){while (list ($a, $b) = each ($results)){ echo "Starting......: ".date("H:i:s")." cgroups: $b\n";}}
			}else{
				echo "Starting......: ".date("H:i:s")." cgroups: structure:$num already dismounted\n";
			}
		}
		
		$arrayDEB=is_old_debian_mounted();
		while (list ($num, $mounted) = each ($arrayDEB)){
			if(trim($mounted)==null){continue;}
			echo "Starting......: ".date("H:i:s")." cgroups: unmount $mounted\n";
			$results=array();
			exec("$umount -l $mounted  2>&1",$results);
			if(count($results)>1){while (list ($a, $b) = each ($results)){ echo "Starting......: ".date("H:i:s")." cgroups: $b\n";}}
		}
		
		reset($GLOBALS["CGROUPS_FAMILY"]);
		while (list ($num, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if(is_cgroup_structure_mounted($num)){
				echo "Starting......: ".date("H:i:s")." cgroups: unmount structure:$num failed\n";
			}
		}		
	$results=array();	
	exec("$rm -rf /cgroups/* 2>&1",$results);	
	if(count($results)>1){while (list ($a, $b) = each ($results)){ echo "Starting......: ".date("H:i:s")." cgroups: $b\n";}}
	sleep(2);
	cgred_stop();
	
}

function reload(){
	build();
	cgred_stop();
	cgred_start();
}

function Mount_structure(){
	
	$unix=new unix();
	$mount=$unix->find_program("mount");
	reset($GLOBALS["CGROUPS_FAMILY"]);
	while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
		if(!is_cgroup_structure_mounted($structure)){
			echo "Starting......: ".date("H:i:s")." cgroups: mounting structure:$structure\n";
			@mkdir("/cgroups/$structure",0775,true);
			$results=array();
			exec("$mount -t cgroup -o\"$structure\" none \"/cgroups/$structure\" 2>&1",$results);
			if(count($results)>1){while (list ($a, $b) = each ($results)){ echo "Starting......: ".date("H:i:s")." cgroups: $b\n";}}
		}else{
			echo "Starting......: ".date("H:i:s")." cgroups: structure:$structure already mounted\n";
		}
	}
	
	
}



function start(){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." cgroups: Already pid $pid is running, aborting\n";return;}
	@file_put_contents($pidfile, getmypid());
	$GLOBALS["CGROUPS_FAMILY"]=array();
	
	$sock=new sockets();
	$cgroupsEnabled=$sock->GET_INFO("cgroupsEnabled");
	$cgroupsServiceEnabled=$sock->GET_INFO("cgroupsServiceEnabled");
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}
	if($sock->EnableIntelCeleron==1){$cgroupsEnabled=1;}
	if($cgroupsEnabled==0){echo "Starting......: ".date("H:i:s")." cgroups: cgroups is disabled\n";stop();cgred_stop(true);return;}
	
	load_family();
	echo "Starting......: ".date("H:i:s")." cgroups: starting daemons\n";
	
		$unix=new unix();
		$umount=$unix->find_program("umount");
		$mount=$unix->find_program("mount");
		$rm=$unix->find_program("rm");
		$echo=$unix->find_program("echo");	
	
		
		Mount_structure();		
	
	build();
	
	reset($GLOBALS["CGROUPS_FAMILY"]);
	if(is_array($GLOBALS["ArrayRULES"])){
		while (list ($groupname, $array) = each ($GLOBALS["ArrayRULES"])){
			echo "Starting......: ".date("H:i:s")." cgroups: mounting group:$groupname\n";
			while (list ($structure, $array2) = each ($array)){
				if(!isset($GLOBALS["CGROUPS_FAMILY"][$structure])){continue;}
				echo "Starting......: ".date("H:i:s")." cgroups: create :/cgroups/$structure/$groupname\n";
				@mkdir("/cgroups/$structure/$groupname",0775,true);
				while (list ($key, $value) = each ($array2)){
					echo "Starting......: ".date("H:i:s")." cgroups:$groupname:$structure  $key = $value\n";
					@file_put_contents("/cgroups/$structure/$groupname/$key", $value);
				}
			}
				
		}	
	}else{
	 echo "Starting......: ".date("H:i:s")." cgroups: No rules...\n";	
	}
	$c=0;
	if(count($GLOBALS["PROCESSES"])>0){
		echo "Starting......: ".date("H:i:s")." cgroups checking processes\n";
		while (list ($process, $groupname) = each ($GLOBALS["PROCESSES"])){
			$pid=intval($unix->PIDOF($process));
			if($pid>0){
				reset($GLOBALS["CGROUPS_FAMILY"]);
				while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
					$directory="/cgroups/$structure/$groupname";
					if(is_dir($directory)){
						shell_exec("$echo $pid >$directory/tasks");
						$c++;
					}else{
						if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups $directory no such directory\n";}
					}
				}
			}
			
		}
	}	
	
	
	echo "Starting......: ".date("H:i:s")." cgroups: starting daemons and $c attached processes\n";
	cgred_start();
}




function writerules($gpid,$gpname){
	$q=new mysql();
	$sql="SELECT * FROM cgroups_processes WHERE groupid=$gpid ORDER BY process_name";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";die();}
	echo "Starting......: ".date("H:i:s")." cgroups: Group \"$gpname\" [$gpid] ". mysql_num_rows($results). " Processe(s)\n";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["process_name"]=trim($ligne["process_name"]);
		$ligne["user"]=trim($ligne["user"]);
		echo "Starting......: ".date("H:i:s")." cgroups: attach {$ligne["user"]}:{$ligne["process_name"]}	to $gpname\n";
		$GLOBALS["CGRULES_CONF"][]="{$ligne["user"]}:\"{$ligne["process_name"]}\"\t*\t$gpname/";
		$GLOBALS["PROCESSES"][$ligne["process_name"]]=$gpname;
		
	}
	
}



function services_check(){
	if(is_file("/etc/init.d/cgconfig")){
		echo "Starting......: ".date("H:i:s")." cgroups: checks cgconfig service...\n";
		if(!function_exists("is_link")){echo "Starting......: ".date("H:i:s")." cgroups: is_link no such function\n";}
		if(!is_link("/etc/init.d/cgconfig")){
			echo "Starting......: ".date("H:i:s")." cgroups: installing specific Artica init.d/cgconfig script\n";
			shell_exec("/bin/mv /etc/init.d/cgconfig /etc/init.d/cgconfig.bak");
			
		}
	}else{
		echo "Starting......: ".date("H:i:s")." cgroups: /etc/init.d/cgconfig no such file\n";
	}
	
	if(is_file("/etc/init.d/cgred")){
		if(!is_link("/etc/init.d/cgred")){
			shell_exec("/etc/init.d/cgred stop");
			echo "Starting......: ".date("H:i:s")." cgroups: installing specific Artica init.d/cgred script\n";
			shell_exec("/bin/mv /etc/init.d/cgred /etc/init.d/cgred.bak");
			
		}
	}else{
		echo "Starting......: ".date("H:i:s")." cgroups: /etc/init.d/cgred no such file\n";
	}
	
}



function cgred_start(){
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." cgroups: cgred_start function Already running pid $pid is running, aborting\n";return;}
	@file_put_contents($pidfile, getmypid());	
	
	$cgrulesengd=$GLOBALS["CLASS_UNIX"]->find_program("cgrulesengd");
	$sock=new sockets();
	$cgroupsEnabled=$sock->GET_INFO("cgroupsEnabled");
	$cgroupsServiceEnabled=$sock->GET_INFO("cgroupsServiceEnabled");
	
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}
	
	if($cgroupsEnabled==0){
		echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon cgroups is disabled\n";return;
		if(is_file($cgrulesengd)){
			$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
			if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){cgred_stop(true);return;}
		}
	}	
	
	
	if(!is_file($cgrulesengd)){
		echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon no such binary\n";
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon\n";
	load_family();
	$catBin=$GLOBALS["CLASS_UNIX"]->find_program("cat");
	reset($GLOBALS["CGROUPS_FAMILY"]);
		while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if(!is_cgroup_structure_mounted($structure)){
				if($structure<>"memory"){
					echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon structure:$structure is not mounted, aborting\n";
					return;
				}
			}
		}

	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon already exists pid $pid\n";
		return;
	}
	
		$q=new mysql();
		$sql="SELECT *  FROM cgroups_groups ORDER BY cpu_shares,groupname";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n";}	
	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$group=$ligne["groupname"];
			reset($GLOBALS["CGROUPS_FAMILY"]);
			echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon checking group $group\n";
			while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
				if(!is_dir("/cgroups/$structure/$group")){
					echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine Daemon create structure $structure\n";
					@mkdir("/cgroups/$structure/$group",0755,true);
				}
			}
		}

		
	if($cgroupsServiceEnabled==0){echo "Starting......: ".date("H:i:s")." cgroups: cgroups is disabled\n";stop();cgred_stop(true);return;}
	if(is_file("/var/log/cgrulesend.log")){@unlink("/var/log/cgrulesend.log");}
	if(is_file("/var/log/cgrulesengd.log")){@unlink("/var/log/cgrulesengd.log");}
	$cmdline="$cgrulesengd  -f /etc/cgrules.conf --logfile=/var/log/cgrulesengd.log";
	shell_exec($cmdline);
	for($i=0;$i<6;$i++){
		$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
			break;
		}
	sleep(1);
	}
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
	if($unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine started pid $pid\n";
		TaskSave();
	}else{
		echo "Starting......: ".date("H:i:s")." cgroups: CGroup Rules Engine failed to start with cmdline: $cmdline\n";
	}
	
	
}
function cgred_stop($nomypidcheck=false){
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	if(!$nomypidcheck){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			}
			echo "Starting......: ".date("H:i:s")." cgroups: cgred_stop() function Already running pid $pid, aborting $called\n";return;}
		@file_put_contents($pidfile, getmypid());	
	}
	
	$cgrulesengd=$unix->find_program("cgrulesengd");
	$kill=$unix->find_program("kill");
	if(!is_file($cgrulesengd)){
		echo "Stopping cgroups.............: CGroup Rules Engine Daemon no such binary\n";
		return;
	}
	
	$pid=$unix->PIDOF($cgrulesengd);
	if(!$unix->process_exists($pid)){
		echo "Stopping cgroups.............: CGroup Rules Engine Daemon already stopped\n";
		return;
	}

	unix_system_kill($pid);
	for($i=0;$i<6;$i++){
		$pid=$unix->PIDOF($cgrulesengd);
		if(!$unix->process_exists($pid)){
			break;
		}
	sleep(1);
	}	
	
	$pid=$unix->PIDOF($cgrulesengd);
	if(!$unix->process_exists($pid)){
		echo "Stopping cgroups.............: CGroup Rules Engine successfully stopped\n";
	}else{
		echo "Stopping cgroups.............: CGroup Rules Engine failed to stop\n";
	}
		
}

function TaskPidof($cmdline){
		if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		if(!is_file($pgrep)){return array();}
		exec("$pgrep -l -f \"$cmdline\" 2>&1",$results);
		while (list ($index, $ligne) = each ($results)){
			if($GLOBALS["VERBOSE"]){echo "TaskPidof::".__LINE__.":: $ligne\n";}
			if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
			if(preg_match("#^[0-9]+\s+.+?pgrep#", $ligne)){continue;}
			$pidf=$re[1];
			if($GLOBALS["VERBOSE"]){echo "TaskPidof::".__LINE__.":: $cmdline -> $pidf\n";}
			$pidR[$GLOBALS["CLASS_UNIX"]->PPID_OF($pidf)]=true;
		}
		if(!isset($pidR)){return array();}
		if(!is_array($pidR)){return array();}
		return $pidR;
	}

function TaskSave(){
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	load_family();
	$q=new mysql();	
	$echo=$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("echo");
	$sql="SELECT cgroups_processes.process_name,cgroups_groups.groupname  FROM cgroups_processes,cgroups_groups WHERE cgroups_processes.groupid=cgroups_groups.ID ORDER BY process_name";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";die();}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["process_name"]=trim($ligne["process_name"]);
		$pids=TaskPidof($ligne["process_name"]);
		$groupname=$ligne["groupname"];
		if(count($pids)==0){continue;}
		
		reset($GLOBALS["CGROUPS_FAMILY"]);
		while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			
			if(!is_dir("/cgroups/$structure")){continue;}
			if(!is_dir("/cgroups/$structure/$groupname")){@mkdir("/cgroups/$structure/$groupname");}
			
			
			if(is_file("/cgroups/$structure/$groupname/tasks")){
				reset($pids);
				while (list ($pid, $none) = each ($pids)){
					if(!is_numeric($pid)){continue;}
					if($GLOBALS["VERBOSE"]){echo "/cgroups/$structure/$groupname/tasks -> $pid\n";}
					shell_exec("$echo $pid >/cgroups/$structure/$groupname/tasks >/dev/null 2>&1");
				}
			}
			
		}
	
	}	
	
}



function buildstats(){
	// default structure.
	
	$sock=new sockets();
	$cgroupsEnabled=$sock->GET_INFO("cgroupsEnabled");
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}
	if($cgroupsEnabled==0){
		if(is_dir("/cgroups")){@rmdir("/cgroups");}
		if(is_dir("/cgroup")){@rmdir("/cgroup");}
		return;
	}
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$int=$unix->file_time_min($timefile);
	if($int<15){return;}
	@unlink($int);
	@file_put_contents($timefile, time());
	TaskSave();

	$q=new mysql();
	$array[]=array("structure"=>"cpuacct" ,"key"=>"cpuacct.usage");
	$array[]=array("structure"=>"memory" ,"key"=>"memory.memsw.max_usage_in_bytes");
	$array[]=array("structure"=>"memory" ,"key"=>"memory.usage_in_bytes");

	
	
	$prefix="INSERT INTO cgroups_stats (zmd5,zDate,structure,groupname,`key`,`value`) VALUES ";
	$date=date("Y-m-d H:i:s");
	
	
	while (list ($index, $keyARRAY) = each ($array)){
		$structure=$keyARRAY["structure"];
		$key=$keyARRAY["key"];
		if(is_file("/cgroups/$structure/$key")){
		$datas=trim(@file_get_contents("/cgroups/$structure/$key"));
		$zmd5=md5("$date{$datas}$key$structure");
		$ql[]="('$zmd5','$date','$structure','system','$key','$datas')";
		}
	}
	
	$q=new mysql();
	$sql="SELECT groupname  FROM cgroups_groups";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
		
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
			reset($array);
			$groupname=$ligne["groupname"];
			while (list ($index, $keyARRAY) = each ($array)){
				$structure=$keyARRAY["structure"];
				$key=$keyARRAY["key"];				
				if(is_file("/cgroups/$structure/$key")){
					$datas=trim(@file_get_contents("/cgroups/$structure/$groupname/$key"));
					$zmd5=md5("$date{$datas}$key$structure");
					$ql[]="('$zmd5','$date','$structure','$groupname','$key','$datas')";
				}			
			}
		
		}
		
		
	if(count($ql)>0){
		$sql=$prefix." " .@implode(",", $ql);
		$q->QUERY_SQL($sql,"artica_events");
	}
	
}
function build_progress_install($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/cgroups.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}	

function install(){
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$cgrulesengd=$unix->find_program("cgrulesengd");
	echo "cgrulesengd = $cgrulesengd\n";
	if(is_file($cgrulesengd)){
		build_progress_install("{success}",100);
		return;
	}
	$GLOBALS["OUTPUT"]=true;
	build_progress_install("{installing} {please_wait}",15);
	$unix=new unix();
	$cgrulesengd=null;

	
	$unix->DEBIAN_INSTALL_PACKAGE("cgroup-bin",true);
	
	if(is_file("/usr/sbin/cgrulesengd")){
		$cgrulesengd="/usr/sbin/cgrulesengd";
	}
	
	if($cgrulesengd==null){
		$cgrulesengd=$unix->find_program("cgrulesengd",true);
	}
	if(is_file($cgrulesengd)){
		build_progress_install("{learning_artica}",80);
		system("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
		build_progress_install("{removing_caches}",90);
		$unix->REMOVE_INTERFACE_CACHE();
		
		start();
		build_progress_install("{success}",100);
		
		return;
	}
	
	build_progress_install("{failed_to_install}",110);
}

