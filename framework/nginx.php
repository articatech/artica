<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["remove-site"])){remove_website();exit;}
if(isset($_GET["access-errors"])){events_errors();exit;}
if(isset($_GET["checking-service"])){checking_service();exit;}
if(isset($_GET["enable-service"])){enable_service();exit;}
if(isset($_GET["execute-wizard"])){execute_wizard();exit;}
if(isset($_GET["import"])){import();exit;}
if(isset($_GET["status-infos"])){status_info();exit;}
if(isset($_GET["delete-cache"])){delete_cache();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["www-events"])){www_events();exit;}
if(isset($_GET["mysqldb-restart"])){mysqldb_restart();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["conf-view"])){conf_view();exit;}
if(isset($_GET["replic-conf"])){conf_save();exit;}
if(isset($_GET["uncompress-nginx"])){uncompress_nginx();exit;}
if(isset($_GET["reconfigure-single"])){reconfigure_single();exit;}
if(isset($_GET["purge-cache"])){purge_cache();exit;}
if(isset($_GET["import-bulk"])){import_bulk();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["access-query"])){events_all();exit;}
if(isset($_GET["compile-single"])){compile_single();exit;}
if(isset($_GET["compile-destination"])){compile_destination();exit;}
if(isset($_GET["refresh-caches"])){refresh_caches();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["clean-websites"])){clean_websites();exit;}
if(isset($_GET["backup"])){backup();exit;}
if(isset($_GET["restore"])){restore();exit;}
if(isset($_GET["build-main"])){build_main();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function status_info(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$ARRAY=$unix->NGINX_COMPILE_PARAMS();
	
	
	exec("$php5 /usr/share/artica-postfix/exec.status.php --nginx --nowachdog 2>&1",$results);
	$ARRAY["STATUS"]=@implode("\n", $results);
	
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";

}

function remove_website(){
	
	$website=$_GET["remove-site"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php --remove \"$website\" --output=yes >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function build_main(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --main >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/nginx.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/nginx.restart.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --restart-build >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function execute_wizard(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-wizard.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/rnginx-wizard.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.wizard.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function compile_single(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-single.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-single.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php \"{$_GET["servername"]}\" --output=yes >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function checking_service(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.enable.php --verif >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function enable_service(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.enable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


//

function events_errors(){
	$pattern=trim(base64_decode($_GET["access-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	$syslogpath="/var/log/nginx/error.log";
	$size=@filesize($syslogpath);
	if($size==0){@unlink($syslogpath);}
	$output="/usr/share/artica-postfix/ressources/logs/web/nginx.query.errors";
	$unix=new unix();
	
	if(!is_file($syslogpath)){
		if(is_file("/var/log/apache2/access-common.log")){
			$syslogpath="/var/log/apache2/access-common.log";
		}
	}
	
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		$grep="$grepbin -i -E '$pattern' $syslogpath";
	}
		
	if($grep<>null){
		$cmd="$grep|$tail -n $maxrows >$output 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath >$output 2>&1";
	}
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($output, 0755);
	
	
}

function events_all(){
	$pattern=trim(base64_decode($_GET["access-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	$syslogpath="/var/log/apache2/common-access.log";
	$size=@filesize($syslogpath);
	if($size==0){@unlink($syslogpath);}
	$output="/usr/share/artica-postfix/ressources/logs/web/nginx.query";
	$unix=new unix();
	
	if(!is_file($syslogpath)){
		if(is_file("/var/log/apache2/access-common.log")){
			$syslogpath="/var/log/apache2/access-common.log";
		}
	}
	
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}


	if(strlen($pattern)>1){
		$grep="$grepbin -i -E '$pattern' $syslogpath";
	}
			
	if($grep<>null){
		$cmd="$grep|$tail -n $maxrows >$output 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath >$output 2>&1";
	}

	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($output, 0755);
}

function conf_save(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	$servername=$_GET["replic-conf"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.single.php \"$servername\" --replic-conf >/dev/null 2>&1 &");
	
	writelogs_framework("$nginx -c /etc/nginx/nginx.conf -t 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$nginx -c /etc/nginx/nginx.conf -t 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#test is successful#", $line)){$OK=true;}
	}
	
	if(!$OK){
		writelogs_framework("FAILED",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
		return;
	}
	
	writelogs_framework("SUCCESS",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode("SUCCESS\n******************\n".@implode("\n", $results))."</articadatascgi>";

	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --force-restart >/dev/null 2>&1 &");
	
}

function conf_view(){
	$sitename=$_GET["conf-view"];
	writelogs_framework("conf_view $sitename",__FUNCTION__,__FILE__,__LINE__);
	foreach (glob("/etc/nginx/sites-enabled/freewebs-$sitename*") as $filename) {
		writelogs_framework("Copy $filename",__FUNCTION__,__FILE__,__LINE__);
		@copy($filename, "/usr/share/artica-postfix/ressources/logs/".basename($filename));
		$array["FILENAME"]=basename($filename);
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
	
}

function sync_freewebs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --sync-squid");
	
}

function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	if(isset($_GET["enabled"])){
		if($_GET["enabled"]==0){
			$cmd="$nohup /etc/init.d/apache2 restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &";
			shell_exec($cmd);		
			$cmd="$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &";
			shell_exec($cmd);
			$cmd="$nohup /etc/init.d/monit restart >/dev/null 2>&1 &";
			shell_exec($cmd);
		}
	}
	
}

function delete_cache(){
	
	$directory=base64_decode($_GET["delete-cache"]);
	if(trim($directory)==null){return;}
	if(!is_dir($directory)){return;}
	$unix=new unix();
	if($unix->IsProtectedDirectory($directory,true)){return;}
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $rm -rf \"$directory\" >/dev/null 2>&1 &");
}
function www_events(){
	$servername=$_GET["servername"];
	$port=$_GET["port"];	
	$type=$_GET["type"];
	$filename="/var/log/apache2/$servername/nginx.access.log";
	if($type==2){
		$filename="/var/log/apache2/$servername/nginx.error.log";
	}
	$search=$_GET["search"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
function mysqldb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx-db.php --init");
	shell_exec("$nohup /etc/init.d/nginx-db restart >/dev/null 2>&1");
}
function uncompress_nginx(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress-nginx"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	
	$cmd="$tar -xf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=nginx_version();
	shell_exec("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
}

function reconfigure_single(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/nginx-$servername.log";
	@unlink($cachefile);
	@file_put_contents($cachefile, "Starting......: ".date("H:i:s")." [INIT]: Nginx, **** RECONFIGURING $servername ****\n");
	@chmod($cachefile, 0777);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --reconfigure \"$servername\" >>$cachefile 2>&1 &");
}

function clean_websites(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.wizard.php --check-http >/dev/null 2>&1 &");
	
	
}


function nginx_version(){
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);

	while (list ($key, $value) = each ($results) ){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}
}

function reconfigure_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nginx.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx.reconfigure.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --reconfigure-all-reboot >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	
}

function compile_destination(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-destination.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-destination.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.destinations.php {$_GET["cacheid"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
}

function backup(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.dump.php --dump --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function restore(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-dump.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.dump.php --restore \"{$_GET["filename"]}\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



function refresh_caches(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-caches.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nginx-caches.log";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --caches-status --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$servername=$_GET["servername"];
	$targetfile="/usr/share/artica-postfix/ressources/logs/access.log.$servername.tmp";
	$sourceLog="/var/log/apache2/$servername/nginx.access.log";
	
	$rp=$_GET["rp"];
	writelogs_framework("access_real -> $rp" ,__FUNCTION__,__FILE__,__LINE__);


	$query=$_GET["query"];
	$grep=$unix->find_program("grep");


	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep -E \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}


function purge_cache(){
	$unix=new unix();
	$ID=$_GET["purge-cache"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --purge-cache $ID >/dev/null 2>&1 &");
}

function import(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-file >/usr/share/artica-postfix/ressources/logs/web/nginx.import.results 2>&1");	
}

function import_bulk(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.nginx.php --import-bulk >/usr/share/artica-postfix/ressources/logs/web/nginx.import-bulk.results 2>&1");
}

