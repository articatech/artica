#!/usr/bin/php -q
<?php
if(isset($argv[1])){if($argv[1]=="--bycron"){die();}}
register_shutdown_function('shutdown');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["COUNT"]=0;
$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(isset($argv[1])){
	if($argv[1]=="--classes"){load_classifications();print_r($GLOBALS["CLASSIFICATIONS"]);die();}
	
}


$pid=getmypid();
$pidfile="/etc/artica-postfix/exec.suricata-tail.php.pid";
@mkdir("/etc/artica-postfix/pids",0755,true);
$pid=@file_get_contents($pidfile);
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

$GLOBALS["VERSION"]="1.0";
$logthis=array();
if($GLOBALS["VERBOSE"]){$logthis[]="Verbosed";}
if($GLOBALS["ACT_AS_REVERSE"]){$logthis[]=" Act as reverse...";}
$GLOBALS["MYPID"]=getmypid();
events("Starting PID: {$GLOBALS["MYPID"]} version: {$GLOBALS["VERSION"]}, ".@implode(", ", $logthis));
if($GLOBALS["DisableLogFileDaemonCategories"]==1){events("Starting: WILL NOT USE Categories detection feature..."); }
if($GLOBALS["DisableLogFileDaemonCategories"]==0){events("Starting: USING Categories detection feature..."); }
if($GLOBALS["EnableArticaMetaClient"]==1){events("Starting: USING Meta Web management console..."); }
if($GLOBALS["EnableArticaMetaClient"]==1){events("Starting: Dump events each {$GLOBALS["LogFileDaemonMaxEvents"]} rows..."); }

$GLOBALS["COUNT_RQS_TIME"]=0;
$GLOBALS["COUNT_RQS"]=0;
$GLOBALS["PURGED"]=0;


$unix=new unix();
$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();
$GLOBALS["iptables"]=$unix->find_program("iptables");
$GLOBALS["nohup"]=$unix->find_program("nohup");
$DCOUNT=0;
$GLOBALS["REQS"]=array();
@file_put_contents($pidfile, getmypid());
load_classifications();
XLOAD_FIREWALL();
system_admin_mysql(2, "Starting Suricata Daemon PID {$GLOBALS["MYPID"]}", null,__FILE__,__LINE__);

//$pipe = fopen("php://stdin", "r");
$buffer=null;
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {
		if($GLOBALS["VERBOSE"]){events(" - > `$buffer`");}
		Parseline($buffer);
	} catch (Exception $e) {
		events("Fatal error on buffer $buffer");
	}

	$buffer=null;
}

function Parseline($buffer){
	
	
	if(!isset($GLOBALS["TIMEEXEC"])){$GLOBALS["TIMEEXEC"]=time();}
	
	
	
	$main=json_decode($buffer);
	$timestamp=strtotime($main->timestamp);
	$zdate=date("Y-m-d H:i:s",$timestamp);
	$zdate_min=date("Y-m-d H:i:00",$timestamp);
	$event_type=$main->event_type;
	$src_ip=$main->src_ip;
	$src_port=$main->src_port;
	$dest_port=$main->dest_port;
	$dest_ip=$main->dest_ip;
	$proto=$main->proto;
	$signature_id=$main->alert->signature_id;
	$signature_rev=$main->alert->rev;
	$signature_string=$main->alert->signature;
	$category=$main->alert->category;
	$severity=$main->alert->severity;
	$uduniq=md5($category);
	
	$class_id=getClassification($uduniq,$category);
	
	if($GLOBALS["VERBOSE"]){events("BUFFER: $uduniq/$category = $class_id");}
	
	$md5=md5("$zdate_min$src_ip$proto$dest_ip$dest_port$signature_id");
	
	if(isset($GLOBALS["FIREWALL"][$signature_id])){
		XDENY($signature_id,$src_ip,$dest_port,$proto);
	}
	
	
	
	if(!isset($RULES[$md5])){
		$RULES[$md5]["DATE"]=$zdate_min;
		$RULES[$md5]["SRC"]=$src_ip;
		$RULES[$md5]["DEST"]=$dest_ip;
		$RULES[$md5]["PROTO"]=$proto;
		$RULES[$md5]["DEST_PORT"]=$dest_port;
		$RULES[$md5]["SIG"]=$signature_id;
		$RULES[$md5]["severity"]=$severity;
		$RULES[$md5]["COUNT"]=1;
		
	}else{
		$RULES[$md5]["COUNT"]=$RULES[$md5]["COUNT"]+1;
		
	}
	
	if(!isset($SIG[$signature_id])){
		if($GLOBALS["VERBOSE"]){events("BUFFER: $signature_id = $signature_string");}
		$SIG[$signature_id]=$signature_string;
	}
	
	$cacheTailTime=tool_time_sec($GLOBALS["TIMEEXEC"]);
	if($GLOBALS["VERBOSE"]){events("TIME: {$GLOBALS["TIMEEXEC"]} = {$cacheTailTime}s / 10");}
	
	if($cacheTailTime>10){
		XDUMP($RULES);
		XDUMP_RULES($SIG);
		$GLOBALS["TIMEEXEC"]=time();
		$RULES=array();
		$SIG=array();
	}
	
	
	
	events("$zdate $event_type $proto {$src_ip}:$src_port -> $dest_ip:$dest_port $signature_id/$class_id");
	

	if($GLOBALS["COUNT_RQS"]==0){$GLOBALS["COUNT_RQS"]=1;}
	$ctrqs=intval($GLOBALS["COUNT_RQS"]);
	$ctrqs++;
	$GLOBALS["COUNT_RQS"]=$ctrqs;
	if($GLOBALS["COUNT_RQS_TIME"]==0){$GLOBALS["COUNT_RQS_TIME"]=time();}

	if($GLOBALS["VERBOSE"]){events( __LINE__." {$GLOBALS["COUNT_RQS"]} connexions");}

	$buffer=null;
		
		

}

function getClassification($uniqid,$text){
	if(isset($GLOBALS["CLASSIFICATIONS"][$uduniq])){return $GLOBALS["CLASSIFICATIONS"][$uniqid];}
	$text=strtolower($text);
	if(isset($GLOBALS["CLASSIFICATIONS"][$text])){return $GLOBALS["CLASSIFICATIONS"][$text];}
	events( __LINE__." No classification for $uniqid ($text)");
}

events("Stopping PID:".getmypid()." After $DCOUNT event(s)");
events("Stopping PID:".getmypid()." Stopped()");
die();



function XDUMP($MAIN){
	$hostname=$GLOBALS["MYHOSTNAME"];
	$f=array();
	while (list ($md5, $ARRAY) = each ($MAIN) ){
		$zdate_min=$ARRAY["DATE"];
		$src_ip=$ARRAY["SRC"];
		$dest_ip=$ARRAY["DEST"];
		$proto=$ARRAY["PROTO"];
		$dest_port=$ARRAY["DEST_PORT"];
		$signature_id=$ARRAY["SIG"];
		$severity=$ARRAY["severity"];
		$COUNT=$ARRAY["COUNT"];
		if($GLOBALS["VERBOSE"]){events("DUMP alert: $zdate_min $src_ip -> $dest_ip:$dest_port sig:$signature_id");}
		$f[]="('$zdate_min','$src_ip','$dest_ip','$proto','$dest_port','$signature_id','$severity','$COUNT','$hostname')";
	}
	
	if(count($f)>0){
		$prefix="INSERT INTO suricata_events (zDate,src_ip,dst_ip,proto,dst_port,signature,severity,xcount,proxyname) VALUES ";
		$results=xPGQUERY_SQL($prefix.@implode(",", $f));
	}
	
	
}

function xPGQUERY_SQL($sql){
	
	$postgres=new postgres_sql();
	$postgres->QUERY_SQL($sql);
	
	if(!$postgres->ok){
		events($postgres->mysql_error);
		if(!is_dir("/home/artica/suricata-tail/errors")){@mkdir("/home/artica/suricata-tail/errors",0755,true);}
		@file_put_contents("/home/artica/suricata-tail/errors/".md5($sql),$sql);
	}
	
}

function XDENY($signature_id,$src_ip,$dest_port,$proto){
	$proxyname=$GLOBALS["MYHOSTNAME"];
	$suffixTables="-m comment --comment \"ArticaSuricata\"";
	$prefix="INSERT INTO suricata_firewall (zdate,uduniq,signature,src_ip,dst_port,proto,proxyname) VALUES ";
	$uduniq=md5("$signature_id,$src_ip,$dest_port,$proto");
	$zdate=date("Y-m-d H:i:s");
	$content="('$zdate','$uduniq','$signature_id','$src_ip','$dest_port','$proto','$proxyname')";
	xPGQUERY_SQL("$prefix $content ON CONFLICT DO NOTHING");
	
	$proto=strtolower($proto);
	$cmdline="{$GLOBALS["nohup"]} {$GLOBALS["iptables"]} -I INPUT -p $proto -m $proto -s $src_ip --dport $dest_port -j DROP $suffixTables >>/var/log/suricata/tail.debug 2>&1 &";
	events($cmdline);
	shell_exec($cmdline);
	
}

function XDUMP_RULES($MAIN){	
	
	$proxyname=$GLOBALS["MYHOSTNAME"];
	$prefix="INSERT INTO suricata_sig (signature,description,enabled) VALUES ";
	
	while (list ($signature, $explain) = each ($MAIN) ){
		$explain=pg_escape_string2($explain);
		if($GLOBALS["VERBOSE"]){events("Dump signature: $signature = $explain");}
		if(strlen($explain)>128){$explain=substr($explain, 0,128);}
		$f[]="('$signature',E'$explain',1)";

	}
	if(count($f)>0){
		xPGQUERY_SQL($prefix.@implode(",", $f). " ON CONFLICT DO NOTHING");
	}
	
}

function XLOAD_FIREWALL(){
	$GLOBALS["FIREWALL"]=array();
	$q=new postgres_sql();
	$q->suricata_tables();
	$results=$q->QUERY_SQL("SELECT signature FROM suricata_sig WHERE enabled=1 and firewall=1");
	if(!$q->ok){events("$q->mysl_error");}
	while ($ligne = pg_fetch_assoc($results)) {
		$GLOBALS["FIREWALL"][$ligne["signature"]]=true;
		
	}
	
	events("Loading ".count($GLOBALS["FIREWALL"])." Auto Firewall signatures");
	
}




function xQUERY_SQL($sql,$database){
	
	if(!isset($GLOBALS["mysql_password"])){
		if(is_file("/etc/artica-postfix/settings/Mysql/database_password")){
			$GLOBALS["mysql_password"]=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password");
		}else{
			$GLOBALS["mysql_password"]=null;
		}
	}
	if(!isset($GLOBALS["mysql_admin"])){
		if(is_file("/etc/artica-postfix/settings/Mysql/database_admin")){
			$GLOBALS["mysql_admin"]=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin");
		}else{
			$GLOBALS["mysql_admin"]="root";
		}
	}
	if(!isset($GLOBALS["mysql_server"])){
		if(is_file("/etc/artica-postfix/settings/Mysql/mysql_server")){
			$GLOBALS["mysql_server"]=@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server");
		}else{
			$GLOBALS["mysql_server"]="127.0.0.1";
		}
	}
	if(!isset($GLOBALS["mysql_port"])){
		if(is_file("/etc/artica-postfix/settings/Mysql/port")){
			$GLOBALS["mysql_port"]=@file_get_contents("/etc/artica-postfix/settings/Mysql/port");
		}else{
			$GLOBALS["mysql_port"]=3306;
		}
	}	
	if($GLOBALS["mysql_server"]=="localhost"){$GLOBALS["mysql_server"]="127.0.0.1";}
	
	if($GLOBALS["mysql_server"]=="127.0.0.1"){
		$cnt_string=":/var/run/mysqld/mysqld.sock";
	}else{
		$cnt_string="{$GLOBALS["mysql_server"]}:{$GLOBALS["mysql_port"]}";
	}
	
	$bd=@mysql_connect($cnt_string,$GLOBALS["mysql_admin"],$GLOBALS["mysql_password"]);
	
	if(!$bd){
		$des=@mysql_error(); $errnum=@mysql_errno();
		events("MySQL Error $errnum $des");
		return false;
	}
	
	$ok=@mysql_select_db($database,$bd);
	if(!$ok){
		$des=@mysql_error($bd); $errnum=@mysql_errno($bd);
		events("MySQL Error $errnum $des");
		@mysql_close($bd);
		return false;
	}
	
	$results=@mysql_query($sql,$bd);
	if(!$results){
		$des=@mysql_error($bd); $errnum=@mysql_errno($bd);
		events("MySQL Error $errnum $des");
		events("$sql");
		@mysql_close($bd);
		return false;
	}
	
	@mysql_close($bd);
	return $results;
	
}


function load_classifications(){
	
	$postgres=new postgres_sql();
	$postgres->suricata_tables();
	$results=$postgres->QUERY_SQL("SELECT * FROM suricata_classifications");
	
	while($ligne=@pg_fetch_assoc($results)){
		$ID=$ligne["id"];
		$uduniq=$ligne["uduniq"];
		$description=$ligne["description"];
		$description=strtolower($description);
		events("load_classifications $uduniq {$ligne["description"]} = $ID");
		
		$GLOBALS["CLASSIFICATIONS"][$uduniq]=$ID;
		$GLOBALS["CLASSIFICATIONS"][$description]=$ID;
	}
	events("Starting ".count($GLOBALS["CLASSIFICATIONS"])." classifications");
	
	if(count($GLOBALS["CLASSIFICATIONS"])==0){
		parse_classifications();
		
		$results=$postgres->QUERY_SQL("SELECT * FROM suricata_classifications");
		
		while($ligne=@pg_fetch_assoc($results)){
			$ID=$ligne["id"];
			$uduniq=$ligne["uduniq"];
			$description=$ligne["description"];
			$description=strtolower($description);
			events("load_classifications $uduniq {$ligne["description"]} = $ID");
			$GLOBALS["CLASSIFICATIONS"][$uduniq]=$ID;
			$GLOBALS["CLASSIFICATIONS"][$description]=$ID;
		}
		events("Starting (2) ".count($GLOBALS["CLASSIFICATIONS"])." classifications");
		
	}

}

function writeCompresslogs($filename,$line){
	$GLOBALS["BYTES_WRITE"]=intval($GLOBALS["BYTES_WRITE"])+strlen($line);
	$f = @fopen($filename, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);	
}

function parse_classifications(){
	
	
	$q=new mysql();
	$f=explode("\n",@file_get_contents("/etc/suricata/rules/classification.config"));
	$postgres=new postgres_sql();

	
	
	while (list ($num, $val) = each ($f)){
		$val=trim($val);
		if(trim($val)==null){continue;}
		if(substr($val, 0,1)=="#"){continue;}
		if(!preg_match("#^config classification:\s+(.+?),(.+?),([0-9]+)#", $val,$re)){continue;}
		$uduniq=md5($re[2]);
		$shortname=mysql_real_escape_string($re[1]);
		$description=mysql_real_escape_string($re[2]);
		$priority=$re[3];
		events("parse_classifications $uduniq $description = $priority");
		$t[]="('$uduniq','$shortname','$description','$priority')";
	}
	
	if(count($t)>0){
		$sql="INSERT INTO suricata_classifications (uduniq,shortname,description,priority) VALUES ".@implode(",", $t)." ON CONFLICT DO NOTHING";
		$postgres->QUERY_SQL($sql);
		if(!$postgres->ok){events($postgres->mysql_error);}
	}
}






function tool_time_sec($last_time){
	if($last_time==0){return 0;}
	$data1 = $last_time;
	$data2 = time();
	
	$difference = ($data2 - $data1);
	if($GLOBALS["VERBOSE"]){events("Current time: $data2 - $data1 = $difference");}
	return $difference;
}

function CachedSizeMem($cached,$SIZE){
	
	
	$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
	writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/MAIN_SIZE",$line);
	
	if($cached==0){
		$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
		writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/NO_CACHED",$line);
		return;
	}
	
	$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
	writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/CACHED",$line);
	

}





function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}







function events($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/suricata/tail.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[REALTIME_LOGS] $pid `$text`\n");
	@fclose($f);
}



function xmysql_escape_string2($line){
	
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}


function rtt_microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}



function rtt_microtime_ms($start){
	return  round(rtt_microtime_float() - $start,3);
}



function shutdown() {
	$error = error_get_last();
	$type=trim($error["type"]);
	$message= trim($error["message"]);
	if($message==null){return;}
	$file = $error["file"];
	$line = $error["line"];
	if(function_exists("openlog")){openlog("artica-status", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog(true, "$file: Fatal, stopped with error $type $message line $line");}
	if(function_exists("closelog")){closelog();}

}

function AccountDecode($path){
	if(strpos($path, "%")==0){return $path;}
	$path=str_replace("%C3%C2§","ç",$path);
	$path=str_replace("%5C","\\",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%0A","\n",$path);
	$path=str_replace("%C2£","£",$path);
	$path=str_replace("%C2§","§",$path);
	$path=str_replace("%C3§","ç",$path);
	$path=str_replace("%E2%82%AC","€",$path);
	$path=str_replace("%C3%89","É",$path);
	$path=str_replace("%C3%A9","é",$path);
	$path=str_replace("%C3%A0","à",$path);
	$path=str_replace("%C3%AA","ê",$path);
	$path=str_replace("%C3%B9","ù",$path);
	$path=str_replace("%C3%A8","è",$path);
	$path=str_replace("%C3%A2","â",$path);
	$path=str_replace("%C3%B4","ô",$path);
	$path=str_replace("%C3%AE","î",$path);
	$path=str_replace("%E9","é",$path);
	$path=str_replace("%E0","à",$path);
	$path=str_replace("%F9","ù",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%E8","è",$path);
	$path=str_replace("%E7","ç",$path);
	$path=str_replace("%26","&",$path);
	$path=str_replace("%FC","ü",$path);
	$path=str_replace("%2F","/",$path);
	$path=str_replace("%F6","ö",$path);
	$path=str_replace("%EB","ë",$path);
	$path=str_replace("%EF","ï",$path);
	$path=str_replace("%EE","î",$path);
	$path=str_replace("%EA","ê",$path);
	$path=str_replace("%E2","â",$path);
	$path=str_replace("%FB","û",$path);
	$path=str_replace("%u20AC","€",$path);
	$path=str_replace("%u2014","–",$path);
	$path=str_replace("%u2013","—",$path);
	$path=str_replace("%24","$",$path);
	$path=str_replace("%21","!",$path);
	$path=str_replace("%23","#",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%7E",'~',$path);
	$path=str_replace("%22",'"',$path);
	$path=str_replace("%25",'%',$path);
	$path=str_replace("%27","'",$path);
	$path=str_replace("%F8","ø",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%3A",":",$path);
	$path=str_replace("%A1","¡",$path);
	$path=str_replace("%A7","§",$path);
	$path=str_replace("%B2","²",$path);
	$path=str_replace("%3B",";",$path);
	$path=str_replace("%3C","<",$path);
	$path=str_replace("%3E",">",$path);
	$path=str_replace("%B5","µ",$path);
	$path=str_replace("%B0","°",$path);
	$path=str_replace("%7C","|",$path);
	$path=str_replace("%5E","^",$path);
	$path=str_replace("%60","`",$path);
	$path=str_replace("%25","%",$path);
	$path=str_replace("%A3","£",$path);
	$path=str_replace("%3D","=",$path);
	$path=str_replace("%3F","?",$path);
	$path=str_replace("%3F","€",$path);
	$path=str_replace("%28","(",$path);
	$path=str_replace("%29",")",$path);
	$path=str_replace("%5B","[",$path);
	$path=str_replace("%5D","]",$path);
	$path=str_replace("%7B","{",$path);
	$path=str_replace("%7D","}",$path);
	$path=str_replace("%2B","+",$path);
	$path=str_replace("%40","@",$path);
	$path=str_replace("%09","\t",$path);
	$path=str_replace("%u0430","а",$path);
	$path=str_replace("%u0431","б",$path);
	$path=str_replace("%u0432","в",$path);
	$path=str_replace("%u0433","г",$path);
	$path=str_replace("%u0434","д",$path);
	$path=str_replace("%u0435","е",$path);
	$path=str_replace("%u0451","ё",$path);
	$path=str_replace("%u0436","ж",$path);
	$path=str_replace("%u0437","з",$path);
	$path=str_replace("%u0438","и",$path);
	$path=str_replace("%u0439","й",$path);
	$path=str_replace("%u043A","к",$path);
	$path=str_replace("%u043B","л",$path);
	$path=str_replace("%u043C","м",$path);
	$path=str_replace("%u043D","н",$path);
	$path=str_replace("%u043E","о",$path);
	$path=str_replace("%u043F","п",$path);
	$path=str_replace("%u0440","р",$path);
	$path=str_replace("%u0441","с",$path);
	$path=str_replace("%u0442","т",$path);
	$path=str_replace("%u0443","у",$path);
	$path=str_replace("%u0444","ф",$path);
	$path=str_replace("%u0445","х",$path);
	$path=str_replace("%u0446","ц",$path);
	$path=str_replace("%u0447","ч",$path);
	$path=str_replace("%u0448","ш",$path);
	$path=str_replace("%u0449","щ",$path);
	$path=str_replace("%u044A","ъ",$path);
	$path=str_replace("%u044B","ы",$path);
	$path=str_replace("%u044C","ь",$path);
	$path=str_replace("%u044D","э",$path);
	$path=str_replace("%u044E","ю",$path);
	$path=str_replace("%u044F","я",$path);
	return $path;
}



?>