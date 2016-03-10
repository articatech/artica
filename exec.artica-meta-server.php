<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["NOTIFY"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["MAIN_PATH"]="/usr/share/artica-postfix/ressources/conf/meta";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");



if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--notify#",implode(" ",$argv))){$GLOBALS["NOTIFY"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}

if($argv[1]=="--metastats"){meta_stats();exit;}
if($argv[1]=="--repair-tables"){repair_tables();exit;}
if($argv[1]=="--build-orders"){build_orders();exit;}
if($argv[1]=="--build-proxy"){build_proxy_configs();exit;}
if($argv[1]=="--extract"){extract_tgz($argv[2]);exit;}
if($argv[1]=="--syslog"){rotate_client($argv[2]);exit;}
if($argv[1]=="--psaux"){psaux_client($argv[2]);exit;}
if($argv[1]=="--philesight"){philesight_client($argv[2]);exit;}
if($argv[1]=="--metaevents"){metaevents_client($argv[2]);exit;}
if($argv[1]=="--metaevents2"){metaevents_client2($argv[2]);exit;}
if($argv[1]=="--purge-upload"){artica_meta_client_purge_upload_queue($argv[2]);exit;}

if($argv[1]=="--checkufdb"){checkufdb();exit;}
if($argv[1]=="--scan-categories"){scan_categories(true);exit;}
if($argv[1]=="--scan-clean"){scan_categories_clean(true);exit;}



if($argv[1]=="--syslaerts"){sysalerts_client($argv[2]);exit;}
if($argv[1]=="--smtp"){sysalerts_smtp_client($argv[2]);exit;}
if($argv[1]=="--snapshot"){snapshot_client($argv[2]);exit;}
if($argv[1]=="--articadaemons"){articadaemons_client($argv[2]);exit;}
if($argv[1]=="--squid-quota-size"){articasquid_quota_size_client($argv[2]);exit;}
if($argv[1]=="--squid-perfs"){articasquid_perf_client($argv[2]);exit;}
if($argv[1]=="--uuid-events"){metaevents_client3($argv[2]);exit;}
if($argv[1]=="--uuid-scheduler"){system_scheduler($argv[2]);exit;}
if($argv[1]=="--uuid-clone-source"){CloneSource($argv[2]);exit;}
if($argv[1]=="--scan-softs"){scan_softs($argv[2]);exit;}
if($argv[1]=="--scan-repos"){scan_repos();exit;}
if($argv[1]=="--scan-temp"){scan_temp_queue();exit;}
if($argv[1]=="--scan-events"){uuid_META_CLIENT_EVENTS($argv[2]);exit;}
if($argv[1]=="--export-tables"){export_tables(true);exit;}



if($argv[1]=="--status-ini"){global_status_ini($argv[2]);exit;}
if($argv[1]=="--scan-repo"){scan_software_repo($argv[2]);exit;}
if($argv[1]=="--delete-repo"){delete_software_repo($argv[2]);exit;}
if($argv[1]=="--delete-articapkg"){delete_artica_repo($argv[2],$argv[3]);exit;}
if($argv[1]=="--add-node"){add_node();exit;}
if($argv[1]=="--tests-notifs"){send_notifications("Test From Meta Server","This is a test");exit;}
if($argv[1]=="--ping-host"){ping_host($argv[2]);exit;}
if($argv[1]=="--ping-group"){ping_group($argv[2]);exit;}

if(strlen($argv[1])>2){meta_events("Unable to understand command {$argv[1]}");}


execute();

function execute(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo "cachetime:$cachetime\n";}
	
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid)){die();}
	
	$TimeEx=$unix->file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){if($TimeEx<20){return;}}
	
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	
	
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	@file_put_contents($pidfile,getmypid());
	meta_stats();
	scan_repos();
	scan_softs();
	scan_software_repo();
	scan_categories();
	scan_temp_queue();
	extract_all_tgz();
	export_tables();
	
	checkufdb();
	clean_tables();
	
	
	if(is_file("$ArticaMetaStorage/webfiltering/ufdbartica.txt")){
		@unlink("/etc/artica-postfix/settings/Daemons/MetaUfdbArticaVer");
		@copy("$ArticaMetaStorage/webfiltering/ufdbartica.txt","/etc/artica-postfix/settings/Daemons/MetaUfdbArticaVer");
		@chmod("/etc/artica-postfix/settings/Daemons/MetaUfdbArticaVer",0755);
	}
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-squid-parser.php >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
	
}

function checkufdb(){
	if($GLOBALS["VERBOSE"]){echo "checkufdb()\n";}
	$sock=new sockets();
	$unix=new unix();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	
	
	
	
	
	
	$timeFile="/etc/artica-postfix/pids/exec.artica-meta-server.php.checkufdb.time";
	$unix=new unix();
	$time=$unix->file_time_min($timeFile);
	if(!$GLOBALS["VERBOSE"]){
			if($time<1440){
				meta_events("exec.artica-meta-server.php.checkufdb.time = {$time}Mn/1440");
				return;
			}
	}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if($GLOBALS["VERBOSE"]){echo "$php /usr/share/artica-postfix/exec.squid.blacklists.php --ufdbmaster\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.squid.blacklists.php --ufdbmaster >/dev/null 2>&1");
	
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	if(!is_file("$ArticaMetaStorage/webfiltering/ufdbartica.txt")){
	}else{
		if($GLOBALS["VERBOSE"]){echo "$ArticaMetaStorage/webfiltering/ufdbartica.txt OK\n";}
	}
	
	if(!is_file("$ArticaMetaStorage/webfiltering/ufdbartica.tgz")){return;}
	$size=@filesize("$ArticaMetaStorage/webfiltering/ufdbartica.tgz");
	
	$listfiles=$unix->DirFiles("$ArticaMetaStorage/webfiltering/ufdbartica","ufdbartica\.tgz\.[0-9]+");
	while (list ($num, $ligne) = each ($listfiles) ){
		$full_path="$ArticaMetaStorage/webfiltering/ufdbartica/$num";
		$size2=$size2+@filesize($full_path);
	}
	
	$size3=$size-$size2;
	echo "$size - $size2 = $size3\n";
	if($size3<100000){return;}
	reset($listfiles);
	while (list ($num, $ligne) = each ($listfiles) ){
		$full_path="$ArticaMetaStorage/webfiltering/ufdbartica/$num";
		if($GLOBALS["VERBOSE"]){echo "Remove $full_path\n";}
		@unlink($full_path);
	}
	$split=$unix->find_program("split");
	@mkdir("$ArticaMetaStorage/webfiltering/ufdbartica",0755,true);
	chdir("$ArticaMetaStorage/webfiltering/ufdbartica");
	system("cd $ArticaMetaStorage/webfiltering/ufdbartica");
	@copy("$ArticaMetaStorage/webfiltering/ufdbartica.tgz", "$ArticaMetaStorage/webfiltering/ufdbartica/ufdbartica.tgz");
	echo "Split...ufdbartica.tgz\n";
	shell_exec("$split -a 3 -b 1m -d ufdbartica.tgz ufdbartica.tgz.");
	@unlink("$ArticaMetaStorage/webfiltering/ufdbartica/ufdbartica.tgz");
	
	$files=$unix->DirFiles("$ArticaMetaStorage/webfiltering/ufdbartica","ufdbartica\.tgz\.[0-9]+");
	while (list ($num, $ligne) = each ($files) ){
		$Splited_md5=md5_file("$ArticaMetaStorage/webfiltering/ufdbartica/$num");
		$ARRAY["$num"]=$Splited_md5;
	}
	@file_put_contents("$ArticaMetaStorage/webfiltering/ufdbartica/ufdbartica.txt", serialize($ARRAY));
	
	
	
}


function meta_stats(){
	$MAIN_DIR="/usr/share/artica-postfix/ressources/conf/meta";
	$unix=new unix();
	$files=$unix->DirFiles("/usr/share/artica-postfix/ressources/conf/meta","^SIZES_[0-9]+\.db");
	if($GLOBALS["VERBOSE"]){echo count($files)." to scan...\n";}
	
	while (list ($num, $ligne) = each ($files) ){
		$full_path="$MAIN_DIR/$num";
		@unlink($full_path);
	}
	
	clean_meta_stats();
	
}

function clean_meta_stats(){
	$q=new mysql_meta();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'articameta' AND table_name LIKE 'metastats_sized_%'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
			
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#metastats_sized_[0-9]+#", $ligne["c"])){
			$q->QUERY_SQL("DROP TABLE `{$ligne["c"]}`");
		}
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'articameta' AND table_name LIKE '%_WEEK_RTT%'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$q->QUERY_SQL("DROP TABLE `{$ligne["c"]}`");
		
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'articameta' AND table_name LIKE 'metastats_size_%'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("DROP TABLE `{$ligne["c"]}`");
	
	}		
	
	
	
	return true;
}



function order_uuid($uuid,$force=false){
	
	$filepath="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.orders";
	$FileTime="/etc/artica-postfix/metaorder.$uuid.time";
	$unix=new unix();

	if(!$force){
		if(is_file($filepath)){if($unix->file_time_min($filepath)<5){return;}}
		if($unix->file_time_min($FileTime)<5){return;}
	}
	@unlink($FileTime);
	@file_put_contents($FileTime, time());
	
	
	$q=new mysql_meta();
	if($q->COUNT_ROWS("metaorders")==0){
		meta_events("metaorders table contains now row");
		return;}
	
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta/hosts",0755,true);
	$sql="SELECT * FROM metaorders WHERE uuid='$uuid' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$orderid=$ligne["orderid"];
		$ordersubject=$ligne["ordersubject"];
		$ordercontent=$ligne["ordercontent"];
		$FULL_SERVERS[$uuid]=true;
		meta_events("metaorders $orderid $ordersubject");
		$ARRAY[$orderid]=array("SUBJECT"=>$ordersubject,"CONTENT"=>$ordercontent);
	}
	meta_events("metaorders saving $filepath");
	@file_put_contents($filepath, base64_encode(serialize($ARRAY)));
	
}

function system_scheduler($uuid){
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta/hosts",0755,true);
	$unix=new unix();
	$filename="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid-schedules.gz";
	$filenameMD5="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid-schedules.md5";
	$sql="SELECT * FROM system_schedules WHERE uuid='$uuid' AND enabled=1 ORDER BY ID DESC";
	$q=new mysql_meta();
	if($q->COUNT_ROWS("system_schedules")==0){
		if(is_file($filename)){@unlink($filename);@unlink($filenameMD5);}	
		if($GLOBALS["NOTIFY"]){ping_host($uuid);}
		return;
	}
	
	
	$results=$q->QUERY_SQL($sql);

	
	$array=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$TimeText=$ligne["TimeText"];
		$TimeDescription=$ligne["TimeDescription"];
		$TaskType=$ligne["TaskType"];
		$md5=md5(serialize($ligne));
		$array[$md5]["TimeText"]=$TimeText;
		$array[$md5]["TimeDescription"]=$TimeDescription;
		$array[$md5]["TaskType"]=$TaskType;
	}
	
	if(count($array)==0){$array["REMOVE"]=true;}
	
	$tmpfile=$unix->FILE_TEMP();
	@file_put_contents($tmpfile, serialize($array));
	
	if(!$unix->compress($tmpfile, $filename)){@unlink($filename);@unlink($filenameMD5);return;}
	@unlink($tmpfile);
	@chmod($filename,0755);
	$md5Sum=md5_file($filename);
	@file_put_contents($filenameMD5, $md5Sum);
	if($GLOBALS["NOTIFY"]){ping_host($uuid);}
	
	
}


function CloneSource($uuid){
	$filepath="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.clone";
	$q=new mysql_meta();
	$source_uuid=$q->CloneSource($uuid);
	if($source_uuid==null){if(is_file($filepath)){@unlink($filepath);}return;}
	
	$sql="SELECT zmd5 FROM snapshots WHERE uuid='$source_uuid' ORDER BY zDate DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		meta_admin_mysql(0, "MySQL error while building Clone source", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	if(mysql_num_rows($results)==0){
		@unlink($filepath);
		return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		@file_put_contents($filepath,$ligne["zmd5"]);
		
	}
	
	@chmod($filepath,0755);
	if($GLOBALS["NOTIFY"]){ping_host($uuid);}
}

function export_tables_progress($purc,$text){
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	echo $text."\n";
	$filename="/usr/share/artica-postfix/ressources/logs/web/artica-meta.proxy.acls.progress";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0755);
}


function export_tables($notify=false){
	
	$array["articameta"][]="metagroups";
	$array["articameta"][]="metagroups_link";
	$array["squidlogs"][]="webfilters_sqgroups";
	$array["squidlogs"][]="webfilters_sqitems";
	$array["squidlogs"][]="meta_webfilters_acls";
	$array["squidlogs"][]="meta_webfilters_sqacllinks";
	
	$q=new mysql();
	$pattern_cmdline=$q->MYSQL_CMDLINES;
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	
	$ArticaMetaStorage=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaMetaStorage"));
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$dumpfile="$ArticaMetaStorage/dump.sql";
	if(is_file($dumpfile)){@unlink($dumpfile);}
	@touch($dumpfile);
	while (list ($database, $tables) = each ($array) ){
		export_tables_progress(20,"{exporting} $database ".@implode(" ", $tables));
		$cmdline="$mysqldump $pattern_cmdline $database ".@implode(" ", $tables)." >>$dumpfile";
		echo $cmdline."\n";
		system($cmdline);
		
	}
	
	
	export_tables_progress(35,"{compressing} $dumpfile");
	if(!$unix->compress($dumpfile, "$dumpfile.gz")){
		echo "Unable to compress $dumpfile to $dumpfile.gz\n";
		export_tables_progress(110,"{failed}");
		@unlink($dumpfile);
		return;
	}
	
	$md5=md5_file("$dumpfile.gz");
	echo "New MD5 $md5\n";
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ArticaMetaDumpSQLMD5", $md5);
	
	export_tables_progress(55,"{notify}");
	if($notify){
		ping_group_acls();
	}
	export_tables_progress(100,"{done}");
}


function build_orders(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){die();}
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	@file_put_contents($pidfile,getmypid());
	$ARRAY=array();
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta/hosts",0755,true);
	$q=new mysql_meta();
	
	$unix=new unix();
	$files=$unix->DirFiles("/usr/share/artica-postfix/ressources/conf/meta/hosts");
	while (list ($filename, $main) = each ($files) ){
		if($GLOBALS["VERBOSE"]){echo "Removing /usr/share/artica-postfix/ressources/conf/meta/hosts/$filename\n";}
		@unlink("/usr/share/artica-postfix/ressources/conf/meta/hosts/$filename");
	}
	
	$sql="SELECT * FROM metaorders ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		meta_admin_mysql(0, "MySQL error while building orders", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	if(mysql_num_rows($results)==0){
		meta_events("No order saved..Aborting ping computers");
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$orderid=$ligne["orderid"];
		$ordersubject=$ligne["ordersubject"];
		$ordercontent=$ligne["ordercontent"];
		$FULL_SERVERS[$uuid]=true;
		$ARRAY[$uuid][$orderid]=array("SUBJECT"=>$ordersubject,"CONTENT"=>$ordercontent);
		
	}
	
	while (list ($uuid, $main) = each ($ARRAY) ){
		$filepath="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.orders";
		@file_put_contents($filepath, base64_encode(serialize($main)));
		
		
	}
	
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	if($ArticaMetaUseSendClient==0){return;}
	
	$sql="SELECT * FROM metahosts WHERE blacklisted=0";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$public_ip=$ligne['public_ip'];
		$uuid=$ligne["uuid"];
		if(!isset($FULL_SERVERS[$uuid])){continue;}
		$uri="https://$public_ip:9000/artica.meta.listener.php?wakeup=yes";
		$curl=new ccurl($uri,true);
		$curl->NoHTTP_POST=true;
		$curl->Timeout=5;
		if(!$curl->get()){
			meta_admin_mysql(1, "$uuid: Unable to ping $public_ip:9000", $q->mysql_error,__FILE__,__LINE__);
			continue;
		}
		
		if(!preg_match("#<ARTICA_META>SUCCESS</ARTICA_META>#is", $curl->data)){
			meta_admin_mysql(1, "$uuid: ping $public_ip:9000 failed",$curl->errors,__FILE__,__LINE__);
		}else{
			meta_admin_mysql(2, "$uuid: Ping $public_ip:9000 success",$curl->errors,__FILE__,__LINE__);
		}
		
	}
	
}

function ping_group_acls(){
	$sock=new sockets();
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	if($ArticaMetaUseSendClient==0){
		echo "Notify clients -> disbaled\n";
		return;
	}
	
	$q=new mysql_squid_builder();
	$sql="SELECT metagroup FROM meta_webfilters_acls GROUP BY metagroup";
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
			$metagroup=intval($ligne["metagroup"]);
			if($metagroup==0){continue;}
			echo "Notify clients -> Group number $metagroup\n";
			ping_group($metagroup);
	}
	$sql="SELECT metauuid FROM meta_webfilters_acls GROUP BY metauuid";
	$results=$q->QUERY_SQL($sql,"metaclient");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$metauuid=$ligne["metauuid"];
		echo "Notify clients -> UUID number $metauuid\n";
		ping_host($metauuid);
	}	
	
}

function ping_group($gpid){
	if(!is_numeric($gpid)){return;}
	if($gpid==0){return;}
	$q=new mysql_meta();
	$sql="SELECT uuid FROM metagroups_link WHERE gpid=$gpid";
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){
		meta_admin_mysql(1, "MySQL error",$q->mysql_error,__FILE__,__LINE__);
		echo $q->mysql_error."\n$sql\n";return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		ping_host($uuid);
	}
}

function ping_host($uuid){
	$sock=new sockets();
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	if($ArticaMetaUseSendClient==0){return;}
	if(isset($GLOBALS["UUID_PINGED"][$uuid])){return;}
	$GLOBALS["UUID_PINGED"][$uuid]=true;
	$artica_meta=new mysql_meta();
	echo "Notify ping_host to $uuid\n";
	meta_events("ping_host to $uuid");
	$public_ip=$artica_meta->uuid_to_public_ip($uuid);
	if($public_ip==null){
		meta_admin_mysql(1, "$uuid: ping Failed public_ip is null",$curl->errors,__FILE__,__LINE__);
		return;
	}
	order_uuid($uuid,true);
	$uri="https://$public_ip:9000/artica.meta.listener.php?wakeup=yes";
	$curl=new ccurl($uri,true);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=5;
	if(!$curl->get()){
		meta_admin_mysql(1, "$uuid: Unable to ping $public_ip:9000", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	if(!preg_match("#<ARTICA_META>SUCCESS</ARTICA_META>#is", $curl->data)){
		meta_admin_mysql(1, "$uuid: ping $public_ip:9000 failed",$curl->errors,__FILE__,__LINE__);
	}else{
		meta_admin_mysql(2, "$uuid: Ping $public_ip:9000 success",$curl->errors,__FILE__,__LINE__);
	}
	
	meta_events("ping_host done");
	
}



function delete_artica_repo($filename,$repo){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);

	if($repo=="RELEASES"){$src="$ArticaMetaStorage/releases/$filename";}
	if($repo=="NIGHTLY"){$src="$ArticaMetaStorage/nightlys/$filename";}
	
	$splited_Dirname=dirname($src);
	$splited_FileName=basename($src);
	$splited_FileDir=str_replace(".tgz", "", $splited_FileName);
	$splited_TarGetDirectory="$splited_Dirname/$splited_FileDir";
	
	if(is_dir($splited_TarGetDirectory)){
		$rm=$unix->find_program("rm");
		shell_exec("rm -rf $splited_TarGetDirectory");
	}
	
	if(!is_file($src)){
		meta_events("$src no such file");
		if($GLOBALS["VERBOSE"]){echo "$src no such file\n";}
		return;
	}
	@unlink($src);
	scan_repos();
}

function meta_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
		
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
		
		
	
	}
	if($GLOBALS["OUTPUT"]){echo $text."\n";}
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);
	
}

function scan_repos_split($source_path){
	
	if(!is_file($source_path)){
		meta_events("$source_path no such file...");
		return array();
	}
	
	$unix=new unix();
	$split=$unix->find_program("split");
	$Dirname=dirname($source_path);
	$FileName=basename($source_path);
	$FileDir=str_replace(".tgz", "", $FileName);
	$FileDir=str_replace(".tar.gz", "", $FileDir);
	meta_events("Dirname=$Dirname FileDir=$FileDir FileName=$FileName");
	
	
	$TarGetDirectory="$Dirname/$FileDir";
	meta_events("Dirname=$Dirname/FileDir=$FileDir");
	
	if(!is_dir($TarGetDirectory)){
		meta_events("Creating directory $TarGetDirectory");
		if(!@mkdir($TarGetDirectory,0755,true)){
			meta_events("Creating directory $TarGetDirectory FAILED");
			return;
		}
	}
	
	if(is_file("$TarGetDirectory/metaindex.txt")){
		
		return unserialize(@file_get_contents("$TarGetDirectory/metaindex.txt"));
	}
	$ARRAY=array();
	chdir("$TarGetDirectory");
	system("cd $TarGetDirectory");
	scan_repos_progress("Explode $source_path",40);
	meta_events("$split -a 3 -b 1m -d $source_path $FileName.");
	shell_exec("$split -a 3 -b 1m -d $source_path $FileName.");
	chdir("/root");
	system("cd /root");
	
	meta_events("Scanning directory $TarGetDirectory");
	$files=$unix->DirFiles("$TarGetDirectory");
	while (list ($num, $ligne) = each ($files) ){
		$Splited_path="$TarGetDirectory/$num";
		$Splited_md5=md5_file($Splited_path);
		$ARRAY["$FileDir/$num"]=$Splited_md5;
	}
	
	if(count($ARRAY)>0){
		@file_put_contents("$TarGetDirectory/metaindex.txt", serialize($ARRAY));
		return $ARRAY;
	}
	
}



function delete_software_repo($filename=null){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);	
	if($filename<>null){
		$filepath="$ArticaMetaStorage/softwares/$filename";
		if(is_file($filepath)){
			@unlink($filepath);
			scan_software_repo();
		}
	
	}
}

function scan_software_repo($filename=null){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);	
	
	if($filename<>null){
		$filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";
		if(is_file($filepath)){
			@unlink("$ArticaMetaStorage/softwares/$filename");
			@copy($filepath, "$ArticaMetaStorage/softwares/$filename");
			@unlink($filepath);
		}
		
	}
	
	$files=$unix->DirFiles("$ArticaMetaStorage/softwares");
	while (list ($num, $ligne) = each ($files) ){
		if($GLOBALS["VERBOSE"]){echo "Found $ArticaMetaStorage/softwares/$num\n";}
		$ARRAY[$num]["SIZE"]=@filesize("$ArticaMetaStorage/softwares/$num");
		$ARRAY[$num]["SPLITED"]=scan_repos_split("$ArticaMetaStorage/softwares/$num");
	}
	
	@file_put_contents("{$GLOBALS["MAIN_PATH"]}/softwares.db", base64_encode(serialize($ARRAY)));
	@chown("{$GLOBALS["MAIN_PATH"]}/softwares.db",$APACHE_ACCOUNT);
	
	
}

function scan_softs(){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$files=$unix->DirFiles("$ArticaMetaStorage/softwares");
	while (list ($num, $ligne) = each ($files) ){
		if($GLOBALS["VERBOSE"]){echo "Found $ArticaMetaStorage/softwares/$num\n";}
		$ARRAY[$num]["SIZE"]=@filesize("$ArticaMetaStorage/softwares/$num");
		$ARRAY[$num]["SPLITED"]=scan_repos_split("$ArticaMetaStorage/softwares/$num");
	}
	
	@file_put_contents("{$GLOBALS["MAIN_PATH"]}/softwares.db", base64_encode(serialize($ARRAY)));
	@chown("{$GLOBALS["MAIN_PATH"]}/softwares.db",$APACHE_ACCOUNT);
	
}

function scan_repos_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress", serialize($array));
	$unix=new unix();

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	if($GLOBALS["OUTPUT"]){echo "{$pourc}) $text\n";}
	$unix->events("{$pourc}) $text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress",0755);

}


function scan_repos(){
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMaxPackages=intval($sock->GET_INFO("ArticaMaxPackages"));
	if($ArticaMaxPackages==0){$ArticaMaxPackages=5;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);
	
	meta_events("Scanning $ArticaMetaStorage/releases");
	scan_repos_progress("Scanning $ArticaMetaStorage/releases",25);
	
	$unix=new unix();
	$files=$unix->DirFiles("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($files) ){
		$NUM_INT=$num;
		scan_repos_progress("Scanning $NUM_INT",25);
		if($unix->file_time_min("$ArticaMetaStorage/releases/$NUM_INT")<2880){continue;}
		$NUM_INT=str_replace("artica-","", $NUM_INT);
		$NUM_INT=str_replace(".tgz","", $NUM_INT);
		$NUM_INT=str_replace(".","", $NUM_INT);
		$XCOUNT[$NUM_INT]=$num;
	}
	

	if(count($XCOUNT)>$ArticaMaxPackages){
		scan_repos_progress("$XCOUNT > $ArticaMaxPackages",30);
		krsort($XCOUNT);
		$x=0;
		while (list ($num, $filename) = each ($XCOUNT) ){
			scan_repos_progress("$num > $filename",30);
			$x++;
			if($x>$ArticaMaxPackages){break;}
			$directory=str_replace(".tgz", "", $filename);
			scan_repos_progress("Removing $ArticaMetaStorage/releases/$filename",30);
			@unlink("$ArticaMetaStorage/releases/$filename");
			if(is_dir("$ArticaMetaStorage/releases/$directory")){
				shell_exec("$rm -rf $ArticaMetaStorage/releases/$directory");
			}
	
		}
	}
	
	$files=$unix->DirFiles("$ArticaMetaStorage/nightlys");
	while (list ($num, $ligne) = each ($files) ){
		$NUM_INT=$num;
		scan_repos_progress("Scanning $NUM_INT",35);
		if($unix->file_time_min("$ArticaMetaStorage/nightlys/$NUM_INT")<2880){continue;}
		$NUM_INT=str_replace("artica-","", $NUM_INT);
		$NUM_INT=str_replace(".tgz","", $NUM_INT);
		$NUM_INT=str_replace(".","", $NUM_INT);
		$ZCOUNT[$NUM_INT]=$num;
	}
	
	if(count($ZCOUNT)>$ArticaMaxPackages){
		krsort($ZCOUNT);
		$x=0;
		while (list ($num, $filename) = each ($ZCOUNT) ){
			$x++;
			if($x>$ArticaMaxPackages){break;}
			$directory=str_replace(".tgz", "", $filename);
			scan_repos_progress("Removing $ArticaMetaStorage/nightlys/$filename",36);
			@unlink("$ArticaMetaStorage/nightlys/$filename");
			if(is_dir("$ArticaMetaStorage/nightlys/$directory")){
				shell_exec("$rm -rf $ArticaMetaStorage/nightlys/$directory");
			}
	
		}
	}
	
	
	
	
	
	$files=$unix->DirFiles("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($files) ){
		if(!preg_match("#^artica-[\.0-9]+#", $num)){continue;}
		meta_events("Found package $ArticaMetaStorage/releases/$num");
		$ARRAY["RELEASES"][$num]=scan_repos_split("$ArticaMetaStorage/releases/$num");
	}
	
	$dirs=$unix->dirdir("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($dirs) ){
		$dirname=basename($num);
		if(!preg_match("#^artica-[\.0-9]+#", $dirname)){continue;}
		meta_events("Found $num");
		if(!is_file("$num/metaindex.txt")){
			meta_events("Remove $num metaindex not found");
			shell_exec("$rm -rf $num");
			continue;
		}
		
		$arr=unserialize(@file_get_contents("$num/metaindex.txt"));
		if(count($arr)<2){
			meta_events("Remove $num metaindex not enough files");
			shell_exec("$rm -rf $num");
			continue;
		}
	
	}	
	

	
	$files=$unix->DirFiles("$ArticaMetaStorage/nightlys");
	while (list ($num, $ligne) = each ($files) ){
		if(!preg_match("#^artica-[\.0-9]+#", $num)){continue;}
		meta_events("Found package $ArticaMetaStorage/nightlys/$num");
		$ARRAY["NIGHTLY"][$num]=scan_repos_split("$ArticaMetaStorage/nightlys/$num");
	}	
	
	$dirs=$unix->dirdir("$ArticaMetaStorage/nightlys");
	while (list ($num, $ligne) = each ($dirs) ){
		$dirname=basename($num);
		if(!preg_match("#^artica-[\.0-9]+#", $dirname)){continue;}
		scan_repos_progress("Found $num",45);
		if(!is_file("$num/metaindex.txt")){
			meta_events("Remove $num metaindex not found");
			shell_exec("$rm -rf $num");
			continue;
		}
		$arr=unserialize(@file_get_contents("$num/metaindex.txt"));
		if(count($arr)<2){
			meta_events("Remove $num metaindex not enough files");
			shell_exec("$rm -rf $num");
			continue;
		}
		
	}
	$dirs=$unix->dirdir("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($dirs) ){
		$dirname=basename($num);
		if(!preg_match("#^artica-[\.0-9]+#", $dirname)){continue;}
		scan_repos_progress("Found $num",50);
		if(!is_file("$num/metaindex.txt")){
			meta_events("Remove $num metaindex not found");
			shell_exec("$rm -rf $num");
			continue;
		}
		$arr=unserialize(@file_get_contents("$num/metaindex.txt"));
		if(count($arr)<2){
			meta_events("Remove $num metaindex not enough files");
			shell_exec("$rm -rf $num");
			continue;
		}
	
	}	
	scan_repos_progress("Creating index file",60);
	meta_events("Saving {$GLOBALS["MAIN_PATH"]}/updates.db");
	@file_put_contents("{$GLOBALS["MAIN_PATH"]}/updates.db", base64_encode(serialize($ARRAY)));
	@chown("{$GLOBALS["MAIN_PATH"]}/updates.db",$APACHE_ACCOUNT);
	@chmod("{$GLOBALS["MAIN_PATH"]}/updates.db",0755);
	
	if($GLOBALS["OUTPUT"]){scan_repos_progress("{done}",95);sleep(5);}
	scan_repos_progress("{done}",100);
	
}

function build_proxy_configs(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){die();}
	
	
	
	$sock=new sockets();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/proxy",0755,true);
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$q=new mysql_meta();
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta",0755,true);
	$results=$q->QUERY_SQL("SELECT * FROM squid_whitelists ORDER BY `pattern`");
	while ($ligne = mysql_fetch_assoc($results)) {$f[]="('{$ligne["zMD5"]}','{$ligne["pattern"]}')";}
	$prefix="INSERT IGNORE INTO `squid_whitelists` (`zMD5`,`pattern`) VALUES ".@implode(",", $f);
	@file_put_contents("$ArticaMetaStorage/proxy/squid_whitelists.db", base64_encode($prefix));
	@chown("$ArticaMetaStorage/proxy/squid_whitelists.db",$APACHE_ACCOUNT);
	
	$results=$q->QUERY_SQL("SELECT uuid FROM metahosts WHERE PROXY=1");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($GLOBALS["VERBOSE"]){echo "CREATE ORDER FOR {$ligne["uuid"]}\n";}
		$q->CreateOrder($ligne["uuid"], "PROXY_PARAMS");
	}
	
}

function extract_all_tgz(){
	
	$sql="SELECT * FROM metahosts WHERE blacklisted=0";
	$q=new mysql_meta();
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		extract_tgz($uuid);
		global_status_ini($uuid);
		rotate_client($uuid);
		psaux_client($uuid);
		philesight_client($uuid);
		metaevents_client($uuid);
		metaevents_client2($uuid);
		metaevents_client3($uuid);
		sysalerts_smtp_client($uuid);
		articadaemons_client($uuid);
		//bandwidth_squid_calc($uuid);
		articasquid_quota_size_client($uuid);
		articasquid_perf_client($uuid);
		artica_meta_client_squid_relatime_ev($uuid);
		$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT/snapshot.tar.gz";
		if(is_file($workingfile)){snapshot_client($uuid);}
		system_scheduler($uuid);
		order_uuid($uuid);
		MaxSnapShots($uuid);
		CloneSource($uuid);
		artica_meta_client_purge_upload_queue($uuid);
	}
}

function MaxSnapShots($uuid){
	$sock=new sockets();
	$ArticaMaxSnapshots=intval($sock->GET_INFO("ArticaMaxSnapshots"));
	if($ArticaMaxSnapshots==0){$ArticaMaxSnapshots=10;}
	$q=new mysql_meta();
	if(!$q->COUNT_ROWS("snapshots")==0){return;}
	$sql="SELECT COUNT(zmd5) as tcount FROM snapshots WHERE uuid='$uuid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["tcount"])<$ArticaMaxSnapshots){return;}
	$Current=$ligne["tcount"];
	$Todelete=$Current-$ArticaMaxSnapshots;
	$sql="DELETE FROM snapshots WHERE uuid='$uuid' ORDER BY zDate LIMIT 0,$Todelete";
	$q->QUERY_SQL($sql);
	if(!$q->ok){meta_events("FATAL $q->mysql_errror");}
	
	
}



function rotate_client($uuid){
	$unix=new unix();
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/syslog";

	$syslog=new mysql_storelogs();
	$files=$unix->DirFiles($workingdir);
	while (list ($basepath, $none) = each ($files) ){
		$syslog->events("META: Rotate $basepath",__FUNCTION__,__LINE__);
		$syslog->ROTATE_ACCESS_TOMYSQL("$workingdir/$basepath");
	}
	
	
}


function sysalerts_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SYS_ALERTS";
	if(!is_dir($workingdir)){
		meta_events("$uuid: $workingdir no such directory");
		sysalerts_client_scan($uuid);
		return;
	}

	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SYS_ALERTS";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
	
	}
	sysalerts_client_scan($uuid);
	
}

function sysalerts_smtp_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SMTP_NOTIF";
	if(!is_dir($workingdir)){
		meta_events("$uuid: $workingdir no such directory");
		sysalerts_smtp_client_scan($uuid);
		return;
	}
	
	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SMTP_NOTIF";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
	
	}
	sysalerts_smtp_client_scan($uuid);	
	
}



function metaevents_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/META_EVENTS";
	if(!is_dir($workingdir)){return;}
	
	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/META_EVENTS";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
		
	}
	metaevents_client_scan($uuid);
	
	
}

function metaevents_client3($uuid){
	
	$workingDir="/home/artica-meta/$uuid/EVENT_NOTIFY_MASTER";
	if(!is_dir($workingDir)){return;}
	$unix=new unix();
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	$metaSql=new mysql_meta();
	$hostname=$metaSql->uuid_to_host($uuid);
	
}
function artica_meta_client_purge_upload_queue($uuid){
	
	
	$possibleFiles[]="TABLE_NICS.gz";
	$possibleFiles[]="PROXY_CATEGORIES.gz";
	$possibleFiles[]="ARTICA_DAEMONS.gz";
	$possibleFiles[]="META_CLIENT_EVENTS.gz";
	$possibleFiles[]="SQUID_PERFS.gz";
	$possibleFiles[]="INTERFACE_CACHE.gz";
	
	
	$TARGET_DIR="/home/artica/squid/META_UPLOADED_QUEUE";
	@mkdir($TARGET_DIR,0755,true);
	$BaseWorkDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	if(is_dir("/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_PERFS")){
		$unix=new unix();
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_PERFS");
	}
	
	while (list ($num, $BASENAME) = each ($possibleFiles)){
		$SOURCE_FILE="$BaseWorkDir/$BASENAME";
		if(!is_file($SOURCE_FILE)){
			meta_events("$uuid: $SOURCE_FILE  ->  NONE");
			continue;
		}
	
		$TARGET_FILE="$TARGET_DIR/$uuid-$BASENAME";
		if(is_file($TARGET_FILE)){@unlink($TARGET_FILE);}
		meta_events("$uuid: " .basename($SOURCE_FILE)."  ->  $TARGET_FILE");
		@copy($SOURCE_FILE, $TARGET_FILE);
		@unlink($SOURCE_FILE);
	}
	
	uuid_TABLE_NICS($uuid);
	articadaemons_client($uuid);;
	articasquid_perf_client($uuid);
	uuid_META_CLIENT_EVENTS($uuid);
	uuid_INTERFACE_CACHE($uuid);
	
}

function uuid_META_CLIENT_EVENTS($uuid){
	$SOURCE_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-META_CLIENT_EVENTS.gz";
	$DEST_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-META_CLIENT_EVENTS.log";
	if(!is_file($SOURCE_FILE)){return;}
	$unix=new unix();	
	
	if(!$unix->uncompress($SOURCE_FILE, $DEST_FILE)){
		meta_events("Unable to uncompress $SOURCE_FILE");
		@unlink($SOURCE_FILE);
		@unlink($DEST_FILE);
		return;
	}
	@unlink($SOURCE_FILE);
	$f=explode("\n",@file_get_contents($DEST_FILE));
	@unlink($DEST_FILE);
	$q=new mysql_meta();
	$q->create_table_meta_uuid_admin_mysql();
	$hostname=$q->uuid_to_host($uuid);
	
	foreach($f as $line) {
		$array=unserialize(base64_decode($line));
		if(!is_array($array)){continue;}
		
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
		$function=null;
		
		
		$zdate=$array["zdate"];
		if(isset($array["function"])){$function=$array["function"];}
		$file=$array["file"];
		$line=$array["line"];
		$severity=$array["severity"];
		$zmd5=md5(serialize($array));
	
		
		$q->QUERY_SQL("INSERT IGNORE INTO `meta_admin_hosts`
				(`zmd5`,`uuid`,`hostname`,`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
				('$zmd5','$uuid','$hostname','$zdate','$content','$subject','$function','$file','$line','$severity')");
		
		if(!$q->ok){continue;}
	}
	
	$q->QUERY_SQL("DELETE FROM `meta_admin_hosts` WHERE zDate<DATE_SUB(NOW(),INTERVAL 15 DAY)");
	
	
}

function uuid_INTERFACE_CACHE($uuid){
	$SOURCE_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-INTERFACE_CACHE.gz";
	$DEST_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-INTERFACE_CACHE.array";
	if(!is_file($SOURCE_FILE)){return;}
	$unix=new unix();
	if(!$unix->uncompress($SOURCE_FILE, $DEST_FILE)){
		meta_events("Unable to uncompress $SOURCE_FILE");
		@unlink($SOURCE_FILE);
		@unlink($DEST_FILE);
		return;
	}
	@unlink($SOURCE_FILE);
	meta_events("Running $SOURCE_FILE");
	
	$MAIN=unserialize(@file_get_contents($DEST_FILE));
	if(!is_array($MAIN)){
		@unlink($DEST_FILE);
		return;
	}
	
	
	@unlink($DEST_FILE);
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("INTERFACE_CACHE")){$q->CheckTables();}
	
	$q->QUERY_SQL("DELETE FROM `INTERFACE_CACHE` WHERE uuid='$uuid'");
	
	while (list ($filekey,$filecontent) = each ($MAIN)){
			$filecontent=mysql_escape_string2($filecontent);
			$q->QUERY_SQL("INSERT IGNORE INTO `INTERFACE_CACHE` (`uuid`,`filekey`,`filecontent`) VALUES ('$uuid','$filekey','$filecontent')");
	}
}

function uuid_TABLE_NICS($uuid){
	$SOURCE_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-TABLE_NICS.gz";
	$DEST_FILE="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-TABLE_NICS.log";
	if(!is_file($SOURCE_FILE)){return;}
	$unix=new unix();
	
	if(!$unix->uncompress($SOURCE_FILE, $DEST_FILE)){
		meta_events("Unable to uncompress $SOURCE_FILE");
		@unlink($SOURCE_FILE);
		@unlink($DEST_FILE);
		return;
	}
	@unlink($SOURCE_FILE);
	
	$q=new mysql_meta();
	$q->CheckTables();
	
	meta_events("Running $SOURCE_FILE");
	
	$all_lines = file($DEST_FILE, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
	@unlink($DEST_FILE);
	foreach($all_lines as $query) {
		if(trim($query)==null){continue;}
		if(substr($query, 0, 2) == "--") {continue; }
		if(strpos(" $query", "CREATE TABLE IF NOT")>0){continue;}
		if(strpos(" $query", "varchar(")>0){continue;}
		if(strpos(" $query", ") NOT NULL")>0){continue;}
		if(strpos(" $query", "longtext NOT NULL")>0){continue;}
		if(strpos(" $query", "int(")>0){continue;}
		if(strpos(" $query", "` text")>0){continue;}
		if(strpos(" $query", "PRIMARY KEY  (")>0){continue;}
		if(strpos(" $query", "KEY `")>0){continue;}
		if(strpos(" $query", ") ENGINE=InnoDB")>0){continue;}
		if(strpos(" $query", "ALTER IGNORE TABLE")>0){continue;}
		if(strpos(" $query", "SET FOREIGN")>0){continue;}
		$queryZ[]=$query;
		
	}
	
	$q->QUERY_SQL("DELETE FROM nics WHERE uuid='$uuid'");
	$q->QUERY_SQL(@implode(" ", $queryZ));
	if(!$q->ok){
		meta_events("$q->mysql_error\n$query\n");
		return;
	}
	
	
}


function artica_meta_client_squid_relatime_ev($uuid){
	$uuid=$_GET["uuid"];
	$BaseWorkDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SQUID_RELATIME_EVENTS";
	if(!is_dir($BaseWorkDir)){return;}
	$unix=new unix();
	


	
	if (!$handle = opendir($BaseWorkDir)) {return;}

	$TARGET_DIR="/home/artica/squid/META_EVENTS_QUEUE";
	@mkdir($TARGET_DIR,0755,true);


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		@copy($targetFile, "$TARGET_DIR/$filename");
		if(!is_file("$TARGET_DIR/$filename")){
			meta_events("Unable to copy $targetFile to $TARGET_DIR/$filename");
			return;
		}
		@unlink($targetFile);
	}
}


function metaevents_client2($uuid){
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/CLIENT_META_EVENTS";
	if(!is_dir($workingDir)){return;}
	$unix=new unix();
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	$q=new mysql_meta();
	$hostname=$q->uuid_to_host($uuid);
	
	$q->create_table_meta_uuid_admin_mysql($uuid);
	$prefix="INSERT IGNORE INTO `meta_admin_$uuid` (`zmd5`,`uuid`,`zDate`,`content`,`hostname`,`subject`,`function`,`filename`,`line` ,	`severity` ,`sended`) VALUES ";
	
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		$targetfile="$workingfile.array";
		if(!$unix->uncompress($workingfile, $targetfile)){
			@unlink($workingfile);
			@unlink($targetfile);
			continue;
		}
		
		$array=unserialize(base64_decode(@file_get_contents($targetfile)));
		@unlink($workingfile);
		@unlink($targetfile);
		if(!is_array($array)){continue;}
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
		$hostname=
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
		$md5=md5(serialize($array));
		$f[]="('$md5','$uuid','$zdate','$content','$hostname','$subject','$function','$file','$line','$severity',0)";
	}
		
		
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){meta_admin_mysql(0, "MySQL error", "$q->mysql_error",__FILE__,__LINE__);}
		}	
	
}


function scan_temp_queue(){
	
	$workingDir="/home/artica/squid/META_UPLOADED_QUEUE";
	if(!is_dir($workingDir)){return;}
	
	$unix=new unix();
	if (!$handle = opendir($workingDir)){
		meta_events("$workingDir failed to parse");
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		if(!preg_match("#^(.+?)-([A-Z_]+)\.gz#", $file,$re)){
			meta_events("$file not correcly named...");
			continue;
		}
		
		$FILEBASE=$re[2];
		$uuid=$re[1];
		
		meta_events("Checking $uuid ($FILEBASE)");
		
		switch ($FILEBASE){
			case "ARTICA_DAEMONS":articadaemons_client($uuid);break;
			case "TABLE_NICS":uuid_TABLE_NICS($uuid);break;
			case "META_CLIENT_EVENTS":uuid_META_CLIENT_EVENTS($uuid);break;
			case "SQUID_PERFS":articasquid_perf_client($uuid);break;
			case "add-tab":main_add_tab();exit;break;
			default:break;
		}
		
	}
	
	
}


function sysalerts_smtp_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	$meta=new mysql_meta();
	$hostname=$meta->uuid_to_host($uuid);
	$tag=$meta->uuid_to_tag($uuid);
	
	if($tag<>null){$hostname="$hostname/$tag";}
	
	$severityT[0]="Alert";
	$severityT[1]="Warning";
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SMTP_NOTIF";
	$files=$unix->DirFiles($destdir);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		
		$filetime=$unix->file_time_min($sourcefile);
		if($filetime>240){
			@unlink($sourcefile);
			continue;
		}
		
		$array=unserialize(@file_get_contents($sourcefile));
		if(!is_array($array)){
			meta_events("$sourcefile no such array");
			@unlink($sourcefile);
			continue;
		}
		
		
		
		
		$zdate=$array["zdate"];
		$subject=$array["subject"];
		$text=$array["text"];
		$severity=$array["severity"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		
		$md5=md5("$uuid$subject");
		
		if(isset($GLOBALS["NOTIFS"][$md5])){@unlink($sourcefile);continue;}
		$GLOBALS["NOTIFS"][$md5]=true;
		
		$subject="[$hostname]:{$severityT[$severity]} $subject";
		$content="Operation created by $hostname on $zdate by $file ($function in line:$line)\nuuid:$uuid\n$text\n";
		
		if(!send_notifications($subject,$content)){
			continue;
		}
		@unlink($sourcefile);
		
	}
	
}


function sysalerts_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SYS_ALERTS";
	$files=$unix->DirFiles($destdir);
	
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		$destfile="$destdir/$filename.sql";
		meta_events("$uuid: Uncompress $sourcefile...");
		$unix->uncompress($sourcefile, $destfile);
		if(!is_file($destfile)){continue;}
		$q->QUERY_SQL(@file_get_contents($destfile),"artica_events");
		if(!$q->ok){
			meta_events("$uuid:$q->mysql_error");
			@unlink($destfile);
			continue;
		}
		@unlink($sourcefile);
		@unlink($destfile);
	}	
	
	
}


function metaevents_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/META_EVENTS";
	$files=$unix->DirFiles($destdir);
	
	if(!$q->FIELD_EXISTS("meta_admin_mysql", "zmd5", "artica_events")){
		meta_events("Patching meta_admin_mysql");
		$meta=new mysql_meta();
		$q->QUERY_SQL("DROP TABLE `meta_admin_mysql`","artica_events");
		$meta->create_table_meta_admin_mysql();
	}
	
	
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		$destfile="$destdir/$filename.sql";
		meta_events("$uuid: Uncompress $sourcefile...");
		$unix->uncompress($sourcefile, $destfile);
		if(!is_file($destfile)){continue;}
		$q->QUERY_SQL(@file_get_contents($destfile),"artica_events");
		if(!$q->ok){
			meta_events("$uuid:$q->mysql_error");
			@unlink($destfile);
			continue;
		}
		@unlink($sourcefile);
		@unlink($destfile);
	}
	
}

function articadaemons_client($uuid){
	$unix=new unix();
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/ARTICA_DAEMONS";
	if(is_dir($workingDir)){
		meta_events("ARTICA_DAEMONS: $uuid: remove $workingDir");
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf $workingDir");
	}

	$sql="CREATE TABLE IF NOT EXISTS `localconfig` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`filekey` VARCHAR(90) NOT NULL,
			`fileData` TEXT NOT NULL,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `filekey` ( `filekey` ),
			 KEY `uuid` ( `uuid` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_events("$uuid: $workingDir $q->mysql_error");
		return;
	}
	$TARGET_DIR="/home/artica/squid/META_UPLOADED_QUEUE";
	$workingfile="$TARGET_DIR/$uuid-ARTICA_DAEMONS.gz";	
	if(!is_file($workingfile)){
		meta_events("ARTICA_DAEMONS: $uuid: $workingfile -> NONE");
		return;
	}
	if(!$unix->uncompress("$workingfile", "$workingfile.array")){
		meta_events("ARTICA_DAEMONS: $uuid: Uncompress failed");
		@unlink($workingfile);
		continue;
	}
	@unlink($workingfile);
		
	$MAIN_ARRAY=unserialize(@file_get_contents("$workingfile.array"));
	if(!is_array($MAIN_ARRAY)){
		meta_events("ARTICA_DAEMONS: $uuid: $workingfile.array is not an array");
		@unlink("$workingfile.array");
		return;
	}
	@unlink("$workingfile.array");
		
	$prefix="INSERT IGNORE INTO `localconfig` (`zmd5`,`uuid`,`filekey`,`fileData`) VALUES ";
	$f=array();
	$q->QUERY_SQL("DELETE FROM `localconfig` WHERE `uuid`='$uuid'");
	$c=0;
	while (list ($key, $data) = each ($MAIN_ARRAY)){
		$md5=md5("$uuid$key");
		$c++;
		$data=mysql_escape_string2($data);
		$f[]="('$md5','$uuid','$key','$data')";
		if(count($f)>10){
			if(!$q->QUERY_SQL($prefix.@implode(",", $f))){meta_events("ARTICA_DAEMONS: $uuid: $q->mysql_error");return;}
			$f=array();
		}
			
	}
	meta_events("ARTICA_DAEMONS: $uuid: Injecting $c items");
	if(count($f)==0){return;}
	if(!$q->QUERY_SQL($prefix.@implode(",", $f))){meta_events("ARTICA_DAEMONS: $uuid: $q->mysql_error");return;}
	$f=array();

}

function articasquid_quota_size_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_QUOTASIZE/SQUID_QUOTASIZE.gz";
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_QUOTASIZE";
	if(!is_dir($workingDir)){
		meta_events("$workingDir no such directroy");
		return;
	}	
	
	$TEMP_DIR=$unix->TEMP_DIR();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	$TARGET_DIR="/home/artica-meta/$uuid/SQUID_QUOTASIZE";
	@mkdir($TARGET_DIR,0755,true);
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		
		
		
		
		meta_events("Analyze $workingfile (".(@filesize($workingfile)/1024) .")");
		$UnCompressed_path="$TARGET_DIR/SQUID_QUOTASIZE.db";
	
		if(!$unix->uncompress($workingfile,$UnCompressed_path )){
			meta_admin_mysql(1, "Unable to uncompress $workingfile", null,__FILE__,__LINE__);
			return;
		}
		@unlink($workingfile);
		
	}
	
	//bandwidth_squid_calc($uuid);
	
}

function articasquid_perf_client($uuid){
	$unix=new unix();
	
	$workingfile="/home/artica/squid/META_UPLOADED_QUEUE/$uuid-SQUID_PERFS.gz";
	if(!is_file($workingfile)){return;}
	
	$TEMP_DIR=$unix->TEMP_DIR();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("squid_perfs_gb")){
		$sql="CREATE TABLE IF NOT EXISTS `squid_perfs_gb` (
				`uuid` varchar(90) NOT NULL,
				`client_http_hits` FLOAT,
				`client_http_requests` FLOAT,
				`client_http_kbytes_out` FLOAT,
				`TOTALS_NOT_CACHED` INT UNSIGNED,
				`TOTALS_CACHED` INT UNSIGNED,
				`TOTALS_CACHED_AVG` FLOAT,
				PRIMARY KEY (`uuid`) ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){meta_events($q->mysql_error);}
	}
	
	
	meta_events("Analyze $workingfile (".(@filesize($workingfile)/1024) .")");
	$UnCompressed_path="$TEMP_DIR/$uuid-SQUID_PERFS";
	
		if(!$unix->uncompress($workingfile,$UnCompressed_path )){
			meta_admin_mysql(1, "Unable to uncompress $workingfile", null,__FILE__,__LINE__);
			@unlink("$UnCompressed_path");
			@unlink($workingfile);
			return;
		}
		
		
		@unlink($workingfile);
		$array=unserialize(@file_get_contents($UnCompressed_path));
		@unlink($UnCompressed_path);
		
		if(!is_array($array)){
			meta_events("NOT AN ARRAY");
			continue;
		}
	
	
		$client_http_hits=$array["client_http_hits"];
		$client_http_requests=$array["client_http_requests"];
		$client_http_kbytes_out=$array["client_http_kbytes_out"];
		$TOTALS_NOT_CACHED=$array["TOTALS_NOT_CACHED"];
		$TOTALS_CACHED=$array["TOTALS_CACHED"];
		$TOTALS_CACHED_AVG=$array["TOTALS_CACHED_AVG"];
		meta_events("client_http_hits: $client_http_hits, client_http_requests $client_http_requests, client_http_kbytes_out $client_http_kbytes_out");
	
		$q->QUERY_SQL("DELETE FROM `squid_perfs_gb` WHERE `uuid`='$uuid'");
	
		$q->QUERY_SQL("INSERT IGNORE INTO `squid_perfs_gb`
				(uuid,client_http_hits,client_http_requests,client_http_kbytes_out,TOTALS_NOT_CACHED,TOTALS_CACHED,TOTALS_CACHED_AVG)
				VALUES('$uuid','$client_http_hits','$client_http_requests','$client_http_kbytes_out','$TOTALS_NOT_CACHED','$TOTALS_CACHED','$TOTALS_CACHED_AVG')");
		
		if(!$q->ok){meta_events($q->mysql_error);}
	}

function bandwidth_squid_calc($uuid){
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("{$uuid}_WEEK_RTTH")){return;}
	$sql="SELECT SUM(`size`) as tcount FROM `{$uuid}_WEEK_RTTH`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size=$ligne["tcount"];
	$q->QUERY_SQL("UPDATE metahosts SET BANDWIDTH='$size' WHERE `uuid`='$uuid'");
	
}


function snapshot_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT/snapshot.tar.gz";
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT";
	if(!is_dir($workingDir)){meta_events("$uuid: $workingDir no such directory");return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `snapshots` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			`zDate` DATETIME NOT NULL,
			`content` longblob NOT NULL,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `zDate` ( `zDate` ),
			 KEY `uuid` ( `uuid` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_events("$uuid: $workingfile $q->mysql_error");
		return;
	}
	
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		$date=date("Y-m-d H:i:s",filemtime($workingfile));
		$filemd5=md5_file($workingfile);
		$size=@filesize($workingfile);
		meta_events("$uuid: Injecting $file ($size bytes)");
		$content=mysql_escape_string2(@file_get_contents($workingfile));
		$prefix="INSERT INTO `snapshots` (zmd5,uuid,zDate,content,`size`) VALUES ('$filemd5','$uuid','$date','$content','$size')";
		if(!$q->QUERY_SQL($prefix)){
			meta_events("$uuid: $workingfile $q->mysql_error");
			continue;
		}
	
		
		@unlink($workingfile);
	
	}
	
	$sql="SELECT uuid FROM `metahosts` WHERE `cloneFrom`='$uuid'";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		CloneSource($ligne["uuid"]);
	}
	
}


function philesight_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/philesight.tgz";
	if(!is_file($workingfile)){
		meta_events("$uuid: $workingfile no such file");
		return;}
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	
	$sql="CREATE TABLE IF NOT EXISTS `philesight` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`directory` VARCHAR( 255 ) NOT NULL,
			`partition` VARCHAR( 128 ) NOT NULL,
			`image` longblob NOT NULL,
			`hd` VARCHAR( 60 ) NOT NULL,
			`lastscan` INT(10) NOT NULL DEFAULT 0,
			`USED` FLOAT NOT NULL DEFAULT 0,
			`FREEMB` INT UNSIGNED NOT NULL DEFAULT 0,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `lastscan` ( `lastscan` ),
			 KEY `partition` ( `partition` ),
			 KEY `uuid` ( `uuid` ),
			 KEY `directory` ( `directory` ),
			 KEY `hd` ( `hd` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$temppath=$unix->TEMP_DIR()."/$uuid-philesight";
	@mkdir($temppath,0755,true);
	shell_exec("$tar xf $workingfile -C $temppath/");
	$prefix="INSERT INTO `philesight` (zmd5,uuid,directory,partition,image,lastscan,USED,FREEMB,hd) VALUES ";
	
	if(!is_file("$temppath/dump.db")){
		meta_admin_mysql(1, "$uuid: philesight $temppath/dump.db no such file", $workingfile,__FILE__,__LINE__);
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		return;
		
	}
	$data=unserialize(@file_get_contents("$temppath/dump.db"));
	
	if(!is_array($data)){
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		meta_admin_mysql(1, "$uuid: philesight not an array", $workingfile,__FILE__,__LINE__);
		return;
	}
	
	if(count($data)==0){
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		meta_admin_mysql(1, "$uuid: philesight empty array", $workingfile,__FILE__,__LINE__);
		return;
	}
	
	
	$TR=array();
	while (list ($directory, $ARRAY) = each ($data) ){
		$md5=md5($uuid.$directory);
		$md5_image=$ARRAY["MD5"];
		$directory=mysql_escape_string2($directory);
		$partition=$ARRAY["PARTITION"];
		$image=@file_get_contents("$temppath/$md5_image.png");
		$lastscan=$ARRAY["lastscan"];
		$image=mysql_escape_string2($image);
		$USED=$ARRAY["USED"];
		$hd=$ARRAY["HD"];
		$FREEMB=$ARRAY["FREEMB"];
		$TR[]="('$md5','$uuid','$directory','$partition','$image','$lastscan','$USED','$FREEMB','$hd')";
		
		
	}
	
	if(count($TR)>0){
		if($GLOBALS["VERBOSE"]){echo "Adding ".count($TR)." rows\n";}
		$q->QUERY_SQL("DELETE FROM `philesight` WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $TR));
		meta_events("$uuid: INSERTING ".count($TR)." elements...");
	}
	
	@unlink($workingfile);
	shell_exec("$rm -rf $temppath");
	
	
}

function psaux_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/PSAUX.gz";
	if(!is_file($workingfile)){return;}
	$tmpfile=$unix->FILE_TEMP();
	$unix->uncompress($workingfile, $tmpfile);
	$data=explode("\n",@file_get_contents($tmpfile));
	$q=new mysql_meta();
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `psaux` (
					uuid VARCHAR( 90 ) NOT NULL,
					user VARCHAR( 40 ) NOT NULL,
					pid INT( 10 ) NOT NULL,
					CPU FLOAT NOT NULL,
					MEM FLOAT NOT NULL,
					VSZ INT(100) NOT NULL,
					RSS INT(100) NOT NULL,
					pTIME VARCHAR( 40 ) NOT NULL,
					pcmd VARCHAR( 255 ) NOT NULL,
					KEY `user` (`user`),
					KEY `CPU` (`CPU`),
					KEY `uuid` (`uuid`),
					KEY `MEM` (`MEM`),
					KEY `VSZ` (`VSZ`),
					KEY `RSS` (`RSS`)
					) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	
	$prefix="INSERT INTO `psaux` (uuid,user,pid,CPU,MEM,VSZ,RSS,pTIME,pcmd) VALUES ";
	$TR=array();
	while (list ($index, $line) = each ($data) ){
		if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+(.+?)\s+([0-9\:]+)\s+(.*)#", $line,$re)){continue;}
		$re[11]=mysql_escape_string2($re[11]);
		$TR[]="('$uuid','{$re[1]}','{$re[2]}','{$re[3]}','{$re[4]}','{$re[5]}','{$re[6]}','{$re[10]}','{$re[11]}')";
		
		
	}
	
	if(count($TR)>0){
		if($GLOBALS["VERBOSE"]){echo "Adding ".count($TR)." rows\n";}
		$q->QUERY_SQL("DELETE FROM `psaux` WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $TR));
	}
	
	@unlink($workingfile);
	
}

function clean_tables(){
	
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$TImExec=$unix->file_time_min($pidTime);
	if($TImExec<1440){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$array["squid_admin_mysql"]=true;
	$sock=new sockets();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	$q=new mysql_meta();
	if($GLOBALS["VERBOSE"]){echo "Checking $c tables\n";}
	$c=0;
	$max=count($array);
	while (list ($table, $lib) = each ($array) ){
		$c++;
	
	
		if(!$q->TABLE_EXISTS($table, "articameta")){
			if($GLOBALS["VERBOSE"]){echo "$table: No such table\n";}
			continue;
		}
		$FileData="/var/lib/mysql/articameta/$table.MYD";
		$NumRows=$q->COUNT_ROWS($table, "articameta");
		$size=@filesize($FileData);
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		if($GLOBALS["VERBOSE"]){echo "$table:[$c/$max] $NumRows rows, {$size}MB Max rows:{$settings["max_events"]}\n";}
	
		if($size>500){
			if($GLOBALS["VERBOSE"]){echo "$table is more than 500MB > purge it\n";}
			$q->QUERY_SQL("TRUNCATE TABLE `$table` ","articameta");
			continue;
		}
	
		if($NumRows>$settings["max_events"]){
			$toDelete=$NumRows-$settings["max_events"];
			if($GLOBALS["VERBOSE"]){echo "$table DELETING $toDelete rows\n";}
			$q->QUERY_SQL("DELETE FROM `$table` ORDER BY zDate LIMIT $toDelete","articameta");
			continue;
		}
		$q->QUERY_SQL("DELETE FROM `$table` WHERE zDate<DATE_SUB(NOW(),INTERVAL 60 DAY)","articameta");
	}
	
}


function extract_tgz($uuid){
	$unix=new unix();
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	if(is_file("$workingdir/status.tgz")){
		
		$tar=$unix->find_program("tar");
		shell_exec("$tar -xf $workingdir/status.tgz -C $workingdir/");
		@unlink("$workingdir/status.tgz");
	}
	$unix->chown_func($unix->APACHE_SRC_ACCOUNT(),$unix->APACHE_SRC_GROUP(),"$workingdir/*");
	
	if(is_file("$workingdir/squid_admin_mysql.db")){
		$q=new mysql_meta();
		if(!$q->TABLE_EXISTS("squid_admin_mysql")){ $q->CheckTables(); }
		$data=trim(@file_get_contents("$workingdir/squid_admin_mysql.db"));
		if($data<>null){
			$q->QUERY_SQL(@file_get_contents("$workingdir/squid_admin_mysql.db"));
			if(!$q->ok){
				meta_admin_mysql(0, "Failed to import $workingdir/squid_admin_mysql.db", $q->mysql_error,__FILE__,__LINE__);
			}else{
				@unlink("$workingdir/squid_admin_mysql.db");
			}
		}else{
			@unlink("$workingdir/squid_admin_mysql.db");
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$workingdir/squid_admin_mysql.db ( no such file )\n";}
	}
	
	
	if(is_file("$workingdir/network_hosts.db")){
		__network_hosts($uuid,"$workingdir/network_hosts.db");
	}else{
		if($GLOBALS["VERBOSE"]){echo "$workingdir/network_hosts.db ( no such file )\n";}
	}
	
	global_status_ini($uuid);
	
}

function __network_hosts($uuid,$filepath){
	
	$ARRAY=unserialize(@file_get_contents($filepath));
	$f=array();
	$prefix="INSERT IGNORE INTO networks_hosts (MAC,IPADDR,IPINT,uuid,hostname,username,OSNAME) VALUES ";
	if(count($ARRAY)==0){
		@unlink($filepath);
		return;
	}
	while (list ($MAC, $subarray) = each ($ARRAY) ){
		
		while (list ($a, $b) = each ($subarray) ){
			$subarray[$a]=mysql_escape_string2($b);
		}

		
		$f[]="('$MAC','{$subarray["IPADDR"]}','{$subarray["IPINT"]}','{$uuid}','{$subarray["hostname"]}',
		'{$subarray["username"]}','{$subarray["OSNAME"]}')";
	}
	
	if(count($f)==0){@unlink($filepath);return;}
	
	$q=new mysql_meta();
	$q->CheckTables();
	if(!$q->FIELD_EXISTS("networks_hosts", "OSNAME")){
		$q->QUERY_SQL("ALTER TABLE `networks_hosts` ADD `OSNAME` varchar(255)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
	$q->QUERY_SQL($prefix.@implode(",", $f));
	if($q->ok){@unlink($filepath);return;}
		
	
	
}

function global_status_ini($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	if(!is_file("$workingdir/global.status.ini")){
		if($GLOBALS["VERBOSE"]){echo "$workingdir/global.status.ini ( no such file )\n";}
		return;}
	$ini=new Bs_IniHandler("$workingdir/global.status.ini");
	
	$f=array();
	$prefix="INSERT IGNORE INTO global_status (
	`uuid`,`MAIN`,`service_name`,`service_cmd`,`service_disabled`,`watchdog_features`,`binpath`,
	`explain`,`running`,`installed`,`master_pid`,`master_memory`,`master_cached_memory`,`processes_number`,`uptime`,`master_version` ) VALUES ";
	
	while (list ($MAIN, $subarray) = each ($ini->_params) ){
		
		if(!isset($subarray["installed"])){
			if($subarray["master_memory"]<>null){
				$subarray["installed"]=1;
			}else{
				$subarray["installed"]=0;
			}
		}
		
		$f[]="('$uuid','$MAIN','{$subarray["service_name"]}',
		'{$subarray["service_cmd"]}',
		'{$subarray["service_disabled"]}',
		'{$subarray["watchdog_features"]}',
		'{$subarray["binpath"]}',
		'{$subarray["explain"]}',
		'{$subarray["running"]}',
		'{$subarray["installed"]}',
		'{$subarray["master_pid"]}',
		'{$subarray["master_memory"]}',
		'{$subarray["master_cached_memory"]}',
		'{$subarray["processes_number"]}',
		'{$subarray["uptime"]}',
		'{$subarray["master_version"]}'
		
		)";
		
	}
	
	
	if(count($f)>0){
		$q=new mysql_meta();
		$q->CheckTables();
		$q->QUERY_SQL("DELETE FROM global_status WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		@unlink("$workingdir/global.status.ini");
	}
}

function add_node_progress($text,$prc){
	$file="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	sleep(1);
	

}
function repair_tables_progress($text,$prc){
	echo "{$prc}% $text\n";
	$file="/usr/share/artica-postfix/ressources/logs/web/artica-meta.RepairTables.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);


}
function add_node(){
	$sock=new sockets();
	
	$ArticaMetaAddNewServ=unserialize($sock->GET_INFO("ArticaMetaAddNewServ"));
	$ArticaMetaHost=$ArticaMetaAddNewServ["ArticaMetaHost"];
	$ArticaMetaPort=$ArticaMetaAddNewServ["ArticaMetaPort"];
	$ArticaMetaUsername=$ArticaMetaAddNewServ["ArticaMetaUsername"];
	$ArticaMetaPassword=$ArticaMetaAddNewServ["ArticaMetaPassword"];
	$change_uuid=$ArticaMetaAddNewServ["change_uuid"];
	
	echo "ArticaMetaHost.........: $ArticaMetaHost\n";
	echo "ArticaMetaPort.........: $ArticaMetaPort\n";
	echo "ArticaMetaUsername.....: $ArticaMetaUsername\n";
	
	echo "Testing authentication...\n";
	add_node_progress("Authenticate to $ArticaMetaHost:$ArticaMetaPort",10);
	
	$array["username"]=$ArticaMetaUsername;
	$array["password"]=$ArticaMetaPassword;
	$ident=urlencode(base64_encode(serialize($array)));
	
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?test-local-ident=$ident");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}
	
	
	if(!preg_match("#<ARTICA_META>(.+?)</ARTICA_META>#is", $curl->data,$re)){
		echo "Expected <ARTICA_META>Someting...</ARTICA_META>";
		add_node_progress("Communication: {failed}",110);
		die();
	}
	
	if($re[1]<>"SUCCESS"){
		add_node_progress("Authenticate: {failed}",110);
		die();
	}
	
	echo "Testing authentication - success -...\n";
	
	if($change_uuid==1){
		echo "Ask to remote server to change UUID...\n";
		add_node_progress("{change_uuid}",20);
		
		$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&chuuid=yes");
		$curl->NoHTTP_POST=true;
		$curl->NoLocalProxy();
		$curl->Timeout=120;
		if(!$curl->get()){
			echo @implode("\n", $curl->errors);
			add_node_progress("$curl->error",110);
			die();
		}
		
		
		if(!preg_match("#<ARTICA_META>(.+?):(.+?)</ARTICA_META>#is", $curl->data,$re)){
			add_node_progress("{change_uuid}: Communication: {failed}",110);
			die();
		}
		add_node_progress("{change_uuid}:{$re[2]} {success}",25);
		echo "Ask to remote server to change UUID - success -...\n";
		
	}
	
	echo "Ask UUID to remote server...\n";
	add_node_progress("{uuid}:",30);
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&GetYourUUID=yes");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}	
	
	if(!preg_match("#<ARTICA_META>(.+?):(.+?)</ARTICA_META>#is", $curl->data,$re)){
		add_node_progress("{uuid}: Communication: {failed}",110);
		die();
	}
	$RESULT=$re[1];
	$uuid=$re[2];
	echo "UUID results:\n---------------------------------------------\n$uuid\n$RESULT\n---------------------------------------------\n";
	if($RESULT=="SUCCESS"){
		add_node_progress("{uuid}: $uuid {success}",35);
	}else{
		add_node_progress("{uuid}: {failed}",110);
		return;
	}
	
	echo "Ask to remote server to register to Meta Server server ...\n";
	add_node_progress("{order}: -> {register}",50);
	
	$ArticaMetaServerUsername=$sock->GET_INFO("ArticaMetaServerUsername");
	$ArticaMetaServerPassword=$sock->GET_INFO("ArticaMetaServerPassword");
	$ArticaMetaAddNewServ["ArticaMetaUsername"]=$ArticaMetaServerUsername;
	$ArticaMetaAddNewServ["ArticaMetaPassword"]=$ArticaMetaServerPassword;
	
	if($GLOBALS["VERBOSE"]){$verbosed="&verbose=yes";}
	$ArticaMetaAddNewServ_enc=urlencode(base64_encode(serialize($ArticaMetaAddNewServ)));
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&registerby=$ArticaMetaAddNewServ_enc$verbosed");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}
	
	if($GLOBALS["VERBOSE"]){echo "***********************************\n$curl->data\n***********************************\n";}
	
	if(!preg_match("#<ARTICA_META>(.+?):(.*)</ARTICA_META>#is", $curl->data,$re)){
		echo  $curl->data."\n";
		add_node_progress("{register}: Communication: {failed}",110);
		die();
	}
	
	$RESULT=$re[1];
	$DATA=$re[2];
	echo $DATA;
	
	echo "Register results:\n---------------------------------------------\n$DATA\n$RESULT\n---------------------------------------------\n";
	
	if($RESULT=="SUCCESS"){
		add_node_progress("{register}: {success}",99);
	}else{
		add_node_progress("{register}: {failed}",110);
		return;
	}
	
	$artica_meta=new mysql_meta();
	add_node_progress("{waiting}: $ArticaMetaHost {to_return_back}",79);
	for($i=0;$i<20;$i++){
		if($artica_meta->isExists($uuid)){break;}
		echo "Waiting $ArticaMetaHost $uuid to register to Meta Server...$i/20 second\n";
		$prc=79+$i;
		add_node_progress("{waiting}: $ArticaMetaHost {to_return_back}",$prc);
		sleep(1);
		
		
	}
	
	
	if($artica_meta->isExists($uuid)){
		$hostname=$artica_meta->uuid_to_host($uuid);
		add_node_progress("{register}: {success} `$hostname`",100);
		return;
	}
	
	add_node_progress("{register}: {failed}",110);
}

function repair_tables(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Alreay executed\n";
		repair_tables_progress("{done}",110);
		die();}
	
	
	$files=$unix->DirFiles("/var/lib/mysql/articameta","\.MYI");
	
	$myisamchk=$unix->find_program("myisamchk");
	
	$count=count($files);
	$c=0;
	while (list($filename,$notused)=each($files)){
		$filepath="/var/lib/mysql/articameta/$filename";
		$c++;
		$prc=round($c/$count,2);
		$prc=$prc*100;
		if($prc>90){$prc=90;}
		$MYD=str_replace("MYI", "MYD", $filename);
		$FRM=str_replace("MYI", "frm", $filename);
		$size=@filesize($filepath)+@filesize("/var/lib/mysql/articameta/$MYD")+@filesize("/var/lib/mysql/articameta/$FRM");
		
		$size=FormatBytes($size/1024,true);
		echo "Repair $filename $c/$count/$prc\n";
		
		repair_tables_progress("{repair} $filename ($size)",$prc);
		sleep(1);
		system("$myisamchk --safe-recover --backup $filepath");
		
	}
	
	$files=$unix->DirFiles("/var/lib/mysql/articameta","\.BAK");
	while (list($filename,$notused)=each($files)){
		$filepath="/var/lib/mysql/articameta/$filename";
		echo "Remove $filename\n";
		$size=@filesize($filepath);
		$size=FormatBytes($size/1024,true);
		repair_tables_progress("{remove} $filename ($size)",95);
		@unlink($filename);
		usleep(500);
	}
	
	repair_tables_progress("{done} $filename",100);
	
}

function send_notifications($subject,$content){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
	$unix=new unix();
	$sock=new sockets();
	
	$ArticaMetaSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("ArticaMetaSMTPNotifs")));
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");
	$users=new usersMenus();
	$smtp_dest=$ArticaMetaSMTPNotifs["smtp_dest"];
	$smtp_sender=$ArticaMetaSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){
		$users=new usersMenus();
		$smtp_sender="artica-meta@$users->hostname";
	}

	if(!isset($ArticaMetaSMTPNotifs["ssl_enabled"])){$ArticaMetaSMTPNotifs["ssl_enabled"]=0;}
	
	$text=str_replace("\n", "\r\n", $content);
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->FromName=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$text;
	$mail->Host=$ArticaMetaSMTPNotifs["smtp_server_name"];
	$mail->Port=$ArticaMetaSMTPNotifs["smtp_server_port"];

	if(($ArticaMetaSMTPNotifs["smtp_auth_user"]<>null) && ($ArticaMetaSMTPNotifs["smtp_auth_passwd"]<>null)){
		$mail->SMTPAuth=true;
		$mail->Username=$ArticaMetaSMTPNotifs["smtp_auth_user"];
		$mail->Password=$ArticaMetaSMTPNotifs["smtp_auth_passwd"];
		if($ArticaMetaSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($ArticaMetaSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}


	}


	
	if($mail->Send()){
		if($GLOBALS["VERBOSE"]){
			echo " ************ SUCCESS *************\n";
		}
		$unix->events("SMTP SEND From <$smtp_sender> to <$smtp_dest> $subject",
				"/var/log/meta.smtp.log",false,__FUNCTION__,__LINE__,__FILE__);
		return true;
	}else{
		$unix->events("SMTP FAILED From <$smtp_sender> to <$smtp_dest> $subject",
				"/var/log/meta.smtp.log",false,__FUNCTION__,__LINE__,__FILE__);
		if($GLOBALS["VERBOSE"]){echo " ************ !!! FAILED !!! *************\n";}
	}
	return false;

}

function scan_categories($aspid=false){
	$unix=new unix();
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){die();}
		@file_put_contents($pidfile, getmypid());
	}
		
	
	
	
	$sock=new sockets();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	
	
	$q=new mysql_meta();
	
	$sql="SELECT webfiltering_categories_items.pattern, metagroups_link.uuid, 
	webfiltering_categories_link.category FROM metagroups_link,webfiltering_categories_link,
	webfiltering_categories_items WHERE webfiltering_categories_link.gpid=metagroups_link.gpid 
	AND webfiltering_categories_items.category=webfiltering_categories_link.category";
	
	
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){scan_categories_clean();return;}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$category=$ligne["category"];
		$ARRAY[$uuid][$category]["SITES"][]=$ligne["pattern"];
	}
	
	$sql="SELECT webfiltering_categories_urls.pattern, metagroups_link.uuid,
	webfiltering_categories_link.category FROM metagroups_link,webfiltering_categories_link,
	webfiltering_categories_urls WHERE webfiltering_categories_link.gpid=metagroups_link.gpid
	AND webfiltering_categories_urls.category=webfiltering_categories_link.category";
	
	
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$category=$ligne["category"];
		$ARRAY[$uuid][$category]["URLS"][]=$ligne["pattern"];
	}
	$MAIN_UUID=array();
	while (list($uuid,$FINAL)=each($ARRAY)){
		@mkdir("$ArticaMetaStorage/$uuid");
		$SourceFile="$ArticaMetaStorage/$uuid/PERSONAL_CATEGORIES";
		$destfile="$ArticaMetaStorage/$uuid/PERSONAL_CATEGORIES.gz";
		$MAIN_UUID[$uuid]=true;
		
		if($GLOBALS["VERBOSE"]){echo "Saving: $destfile\n";}
		@file_put_contents($SourceFile, serialize($FINAL));
		if(!$unix->compress($SourceFile, $destfile)){
			@unlink($SourceFile);
			@unlink($destfile);
			continue;
		}
		ping_host($uuid);
		@unlink($SourceFile);
	}
	
	if($aspid){@unlink($pidfile);}
	
	scan_categories_clean($MAIN_UUID);
	
}
function scan_categories_clean($MAIN_UUID=array()){
	$sock=new sockets();
	$unix=new unix();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$dirs=$unix->dirdir($ArticaMetaStorage);
	$q=new mysql_meta();
	$rm=$unix->find_program("rm");
	while (list($dirname,$FINAL)=each($dirs)){
		$uuid=basename($dirname);
		if(!preg_match("#^[a-z0-9]+-[a-z0-9]+-[a-z0-9]+-[a-z0-9]+-[a-z0-9]+$#", $uuid)){continue;}		
		echo "$uuid checking if must stay on disk\n";
		
		if(!$q->isExists($uuid)){
			echo "$uuid: isExists -> FALSE: Remove $dirname\n";
			shell_exec("$rm -rf $dirname");
			continue;
		}
		if(count($MAIN_UUID)>0){
			if(!isset($MAIN_UUID[$uuid])){
				if(is_file("$dirname/PERSONAL_CATEGORIES.gz")){
					echo "Remove $dirname/PERSONAL_CATEGORIES.gz\n";
					@unlink("$dirname/PERSONAL_CATEGORIES.gz");
				}
			}
		}
		
		if(is_dir("$dirname/EVENT_NOTIFY_MASTER")){
			echo "Remove $dirname/EVENT_NOTIFY_MASTER ( directory )\n";
			shell_exec("$rm -rf $dirname/EVENT_NOTIFY_MASTER");
		}
		
		if(is_dir("$dirname/SMTP_NOTIF")){
			echo "Remove $dirname/SMTP_NOTIF ( directory )\n";
			shell_exec("$rm -rf $dirname/SMTP_NOTIF");
		}
		
	}
}



