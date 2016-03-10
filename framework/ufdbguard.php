<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["ufdbcat-restart-progress"])){ufdbcat_restart_progress();exit;}
if(isset($_GET["compile-category"])){compile_category();exit;}
if(isset($_GET["getversion"])){getversion();exit;}
if(isset($_GET["db-size"])){db_size();exit;}
if(isset($_GET["recompile"])){recompile();exit;}
if(isset($_GET["recompile-all"])){recompile_all();exit;}
if(isset($_GET["db-status"])){db_status();exit;}
if(isset($_GET["recompile-dbs"])){recompile_all();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["ad-dump"])){ad_dump();exit;}
if(isset($_GET["used-db"])){used_databases();exit;}
if(isset($_GET["saveconf"])){ufdbguard_save_content();exit;}
if(isset($_GET["debug-groups"])){debug_groups();exit;}
if(isset($_GET["databases-percent"])){databases_percent();exit;}
if(isset($_GET["articawebfilter-database-version"])){artica_webfilter_database_version();exit;}
if(isset($_GET["apply-restart-progress"])){apply_restart_progress();exit;}
if(isset($_GET["remove-sessions-caches"])){remove_sessions_caches();exit;}
if(isset($_GET["ufdbtail-restart"])){ufdbtail_restart();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function remove_sessions_caches(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $rm -rf /home/squid/error_page_sessions/* >/dev/null 2>&1 &");
	shell_exec("$nohup $rm -rf /home/squid/error_page_cache/* >/dev/null 2>&1 &");
	
}

function ufdbtail_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup /etc/init.d/ufdb-tail restart 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
}


function getversion(){
	$unix=new unix();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	exec("$ufdbguardd -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){echo "<articadatascgi>{$re[1]}</articadatascgi>";return;}
	}	
}

function db_size(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-status");
}

function recompile(){
	@mkdir("/etc/artica-postfix/ufdbguard.recompile-queue",644,true);
	$db=$_GET["recompile"];
	@file_put_contents("/etc/artica-postfix/ufdbguard.recompile-queue/".md5($db)."db",$db);
	
}
function compile_category(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category {$_GET["category"]} --ouptut >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function ufdbcat_restart_progress(){

	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.restart.log";
	

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);

	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ufdbcat.php --restart --ouptut >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}


function apply_restart_progress(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.restart.log";	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --apply-restart --ouptut >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}


function recompile_all(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-recompile-dbs >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function db_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squidguard.php --databases-status >/dev/null 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function service_cmds(){
	$action=$_GET["service-cmds"];
	$unix=new unix();
	if($action=="reconfigure"){
		$php5=$unix->LOCATE_PHP5_BIN();
		exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build --verbose 2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
		
	}
	
	$results[]="/etc/init.d/ufdb $action 2>&1";
	exec("/etc/init.d/ufdb $action 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function ad_dump(){
	$ruleid=$_GET["ad-dump"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --dump-adrules $ruleid >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function used_databases(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#^domainlist\s+\"(.+?)\"#", $ligne,$re)){continue;}
		$path=$re[1];
		$DB="PERS";
		$size=0;
		if(strpos($path, "ftpunivtlse1fr")>0){$DB="UNIV";}
		if(strpos($path, "ufdbartica")>0){$DB="ART";}
		if(is_file("$path.ufdb")){
			$size=@filesize("$path.ufdb");
		}
		$ARRAY[]=array("DB"=>$DB,"SIZE"=>$size,"DIR"=>"$path.ufdb");
		
	}
	echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";
	
	
}



function ufdbguard_save_content(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$ufdbguardd=$unix->find_program("ufdbguardd");
	$datas=base64_decode($_GET["saveconf"]);
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
			echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
			return;
		}
	@file_put_contents("/etc/squid3/ufdbGuard-temp.conf", $datas);
	@chown("/etc/squid3/ufdbGuard-temp.conf", "squid");
	
	$cmd="$ufdbguardd -c /etc/squid3/ufdbGuard-temp.conf -C verify 2>&1";
	
	exec($cmd,$results);
	$ERR=array();
	writelogs_framework($cmd ." ->".count($results),__FUNCTION__,__FILE__,__LINE__);
	$error=false;
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);
		if(!preg_match("#(ERROR:|FATAL ERROR)#", $ligne)){continue;}
		writelogs_framework("ERROR ***** > $ligne",__FUNCTION__,__FILE__,__LINE__);
		$ERR[]=$ligne;
		$error=true;
	}
	
	
	if($error){echo "<articadatascgi>". base64_encode(@implode("\n",$ERR))."</articadatascgi>";return;}
	writelogs_framework("/etc/squid3/ufdbGuard-temp.conf -> /etc/squid3/ufdbGuard.conf",__FUNCTION__,__FILE__,__LINE__);
	@copy("/etc/squid3/ufdbGuard-temp.conf", "/etc/squid3/ufdbGuard.conf");
	@chown("/etc/squid3/ufdbGuard.conf", "squid");
	shell_exec("$nohup /etc/init.d/ufdb reload >/dev/null 2>&1 &");
}
function debug_groups(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#execuserlist\s+\"(.+?)\"#", $ligne,$re)){continue;}
		$path=$re[1];
		$cmds[$path]=true;
	}	
	
	while (list ($num, $ligne) = each ($cmds) ){
		
		exec("$num --verbose 2>&1",$results);
		
	}
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
	
}

function artica_webfilter_database_version(){
	$file="/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD";
	$STATUS=unserialize(@file_get_contents($file));
	$myVersion=intval($STATUS["LAST_DOWNLOAD"]["TIME"]);
	echo "<articadatascgi>$myVersion</articadatascgi>";
	
}

function databases_percent(){
	$unix=new unix();
	
	if(is_file("/etc/artica-postfix/UFDB_DB_STATS")){
		if($unix->file_time_min("/etc/artica-postfix/UFDB_DB_STATS")<3){
			echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/artica-postfix/UFDB_DB_STATS"))."</articadatascgi>";
			return;
		}
	}
	
	
	$MAX=47;
	$files=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	
	$c=0;
	while (list ($dir, $line) = each ($files)){
		if(is_link($dir)){continue;}
		$database_path="$dir/domains.ufdb";
		if(!is_file($database_path)){continue;}
		$cat=basename($dir);
		$size=@filesize("$dir/domains.ufdb");
		if($size<290){continue;}
		$time=filemtime("$dir/domains.ufdb");
		$UFDB[$time]=true;
		$c++;
		
	}	
	
	krsort($UFDB);
	while (list ($time, $line) = each ($UFDB)){
		$xtime=$time;
		break;
	}
	$ARRAY["TLSE"]["LAST_TIME"]=$xtime;
	$ARRAY["TLSE"]["MAX"]=$MAX;
	$ARRAY["TLSE"]["COUNT"]=$c;
	
	$files=$unix->dirdir("/var/lib/ufdbartica");
	
	$MAX=144;
	$c=0;
	$UFDB=array();
	
	while (list ($dir, $line) = each ($files)){
		if(is_link($dir)){continue;}
		$database_path="$dir/domains.ufdb";
		if(!is_file($database_path)){continue;}
		$cat=basename($dir);
		$size=@filesize("$dir/domains.ufdb");
		if($size<290){continue;}
		$time=filemtime("$dir/domains.ufdb");
		$UFDB[$time]=true;
		$c++;
	
	}	
	
	
	krsort($UFDB);
	while (list ($time, $line) = each ($UFDB)){
		$xtime=$time;
		break;
	}
	
	$ARRAY["ARTICA"]["LAST_TIME"]=$xtime;
	$ARRAY["ARTICA"]["MAX"]=$MAX;
	$ARRAY["ARTICA"]["COUNT"]=$c;	

	
	$MAX=150;
	$c=0;
	$UFDB=array();
	$files=$unix->DirFiles("/home/artica/categories_databases");
	
	$c=0;
	while (list ($filename, $line) = each ($files)){
		$filepath="/home/artica/categories_databases/$filename";
		if($filename=="CATZ_ARRAY"){continue;}
		if(is_link("$filepath")){continue;}
	
		$cat=basename($filepath);
		$size=@filesize($filepath);
		if($size<290){continue;}
		$UFDB[$time]=true;
		$c++;
		
	}
	krsort($UFDB);
	while (list ($time, $line) = each ($UFDB)){
		$xtime=$time;
		break;
	}
	$ARRAY["CATZ"]["LAST_TIME"]=$xtime;
	$ARRAY["CATZ"]["MAX"]=$MAX;
	$ARRAY["CATZ"]["COUNT"]=$c;	
	
	@file_put_contents("/etc/artica-postfix/UFDB_DB_STATS", serialize($ARRAY));
	
	echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";
	
}
