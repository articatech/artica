<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');

if($argv[1]=="--check-http"){check_all_websites_http();exit;}
if($argv[1]=="--avail-status"){check_all_websites_status_available();exit;}


startx();

function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/nginx-wizard.progress";
	echo "[{$pourc}%] $filename $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);


}

function startx(){
	$IP=new IP();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$array=unserialize($sock->GET_INFO("NginxWizard"));
	$serverfrom=$array["www_cnx"];
	$serverto=$array["www_dest"];
	$peer_id=$array["peer-id"];
	$MSEXCH=$array["MSEXCH"];
	if(!is_numeric($peer_id)){$peer_id=0;}
	$q=new mysql_squid_builder();
	if($serverfrom==null){
		build_progress("{failed} {from} not specified",110);
		return;
	}
	
events("Source: $serverfrom",__LINE__);
events("Destin: $serverto",__LINE__);

	if($serverto==null){
		if($peer_id==0){
			build_progress("{failed} {to} not specified",110);
			return;
		}
	}	
	
	$ssl=0;
	$parse_url=parse_url($serverfrom);
	$scheme=$parse_url["scheme"];
	$host=$parse_url["host"];
	events("$serverfrom: $scheme",__LINE__);
	events("$serverfrom: $host",__LINE__);
	
	if($scheme=="http"){$local_port=80;}
	if($scheme=="https"){$local_port=443;$ssl=1;}
	if(isset($parse_url["port"])){$local_port=$parse_url["port"];}
	if(preg_match("#(.+?):([0-9]+)#", $host,$re)){$local_port=$re[2];$host=$re[1];}
	echo "Receive connections from port $local_port SSL=$ssl [".__LINE__."]\n";
	events("$serverfrom:Port $local_port SSL=$ssl",__LINE__);
	build_progress("$host Port:$local_port",10);
	
	$Rssl=0;
	$path=null;
	if($peer_id>0){$ID=$peer_id;}
	
	echo "Receive Peer ID:$peer_id [".__LINE__."]\n";
	$IP=new IP();
	if($peer_id==0){
		$parse_url=parse_url($serverto);
		if(isset($parse_url["path"])){$path=$parse_url["path"]; }
		
		while (list ($num, $c) = each ($parse_url) ){events("$serverto: parse_url[$num]='$c'",__LINE__);}
		
		events("$serverto: Path='$path'",__LINE__);
		
		$forcedomain=null;
		$scheme=$parse_url["scheme"];
		events("$serverto: scheme='$scheme'",__LINE__);
		
		$remote_host=$parse_url["host"];
		if($scheme=="http"){$remote_port=80;}
		if($scheme=="https"){$remote_port=443;$Rssl=1;}
		if(isset($parse_url["port"])){$remote_port=$parse_url["port"];}
		if(preg_match("#(.+?):([0-9]+)#", $host,$re)){$remote_port=$re[2];$remote_host=$re[1];}
		events("$serverto: Port='$remote_port'",__LINE__);
		events("$serverto: Hostname='$remote_host'",__LINE__);
		
		if(!$IP->isIPAddress($remote_host)){
			$forcedomain=$remote_host;
			$ipaddr=gethostbyname($remote_host);
			if(!$IP->isIPAddress($ipaddr)){
				build_progress("Failed, unable to resolve $forcedomain",110);
				return;
			}
		}else{
			$ipaddr=$remote_host;
		}
		
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_sources", "remote_path")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `remote_path` CHAR(255) NULL");}
		
		
		echo "transfert connections to port $remote_port $ipaddr ($path) SSL=$Rssl\n";	
		build_progress("{to} $remote_host Port:$remote_port",20);
	
		
		
		$ID=0;
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$ipaddr' AND port='$remote_port'"));
		if($ligne["ID"]==0){
			$sql="INSERT IGNORE INTO `reverse_sources` (`servername`,`ipaddr`,`port`,`ssl`,`enabled`,`forceddomain`,`remote_path`)
			VALUES ('$remote_host port $remote_port','$ipaddr','$remote_port',$Rssl,1,'$forcedomain','$path')";
			events("$sql",__LINE__);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				echo $q->mysql_error."\n$sql\n";
				build_progress("{failed}: MySQL",110);
				return;
			}
			$ID=$q->last_id;
		}else{
			$ID=$ligne["ID"];
			$sql="UPDATE `reverse_sources` SET `remote_path`='$path',`ssl`='$ssl' WHERE ID='$ID'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				echo $q->mysql_error."\n$sql\n";
				build_progress("{failed}: MySQL",110);
				return;
			}
		}
	
	}
	
	echo "Receive Peer ID:$ID [".__LINE__."]\n";
	
	if($ID==0){
		build_progress("{failed}: MySQL bad ID for source..",110);
		return;
	}

	
	echo "Checking $host:$local_port [".__LINE__."]\n";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$host' and port='$local_port'"));
	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("{failed}: MySQL",110);
		return;
	}
	
	
	if($ligne["servername"]==null){
		echo "Insterting $host:$local_port [".__LINE__."]\n";
		$sql="INSERT IGNORE INTO reverse_www (`servername`,`port`,`ssl`,`cache_peer_id`,`enabled`,`default_server`,`owa`) 
				VALUES ('$host','$local_port','$ssl','$ID','1','0','$MSEXCH');";
		
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			echo $q->mysql_error;
			build_progress("{failed}: MySQL",110);
			return;
		}
	}else{
		echo "Updating $host:$local_port [".__LINE__."]\n";
		$sql="UPDATE `reverse_www` SET `cache_peer_id`='$ID',`ssl`='$ssl',`owa`='$MSEXCH' WHERE servername='$host' and port='$local_port'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n$sql\n";
			build_progress("{failed}: MySQL",110);
			return;
		}
	}
	echo "Insterting $host:$local_port [".__LINE__."] Done \n";
	
	build_progress("$host:$local_port: {verify_global_configuration}",50);
	check_all_websites_http();
	build_progress("$host:$local_port: {building_website_configuration}",60);
	system("$php /usr/share/artica-postfix/exec.nginx.single.php \"$host\" --no-reload --no-buildmain");
	build_progress("$host:$local_port: {stopping_reverse_proxy} ",90);
	system("/etc/init.d/nginx stop --force");
	build_progress("$host:$local_port: {starting_reverse_proxy} ",95);
	system("/etc/init.d/nginx start --force");
	build_progress("{success}",100);
}




function preg_match_site($file){
	
	if(preg_match("#[0-9]+-freewebs-ssl-(.+?)\.([0-9]+)\.conf$#", trim($file),$re)){
		//if($GLOBALS["VERBOSE"]){echo "[0-9]+-freewebs-ssl-(.+?)\.([0-9]+)\.conf MATCH $file\n";}
		return array($re[1],$re[2],$re[2]);
	}
	
	
	if(preg_match("#[0-9]+-freewebs-(.+?)\.([0-9]+)\.conf$#", trim($file),$re)){
		//if($GLOBALS["VERBOSE"]){echo "#[0-9]+-freewebs-(.+?)\.([0-9]+)\.conf$# no MATCH `$file`\n";}
		return array($re[1],$re[2],$re[2]);
	}

	return false;
}
function events($text,$line=0){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/nginx.wizard.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] ($line) $text\n";
	@fwrite($h,$line);
	@fclose($h);
}



function check_all_websites_status_available_scan($servername){
	if(trim($servername)==null){return;}
	$servername_org=$servername;
	$servername=trim(strtolower($servername));
	$servername=str_replace(".", "\.", $servername);
	$REMOVED=false;

	$dirs[]="/etc/nginx/sites-enabled-backuped";
	$dirs[]="/etc/nginx/local-sites";
	$dirs[]="/etc/nginx/sites-enabled";

	while (list ($index, $directory) = each ($dirs)){
		
		
		foreach (glob("$directory/*") as $filename) {
				
			if(preg_match("#$servername#", basename($filename))){
				return 1;
			}

		}
	}
	return 0;	
}


function check_all_websites_status_available(){
	
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/exec.nginx.wizard.php.check_all_websites_status_available.pid";
	$pidTime="/etc/artica-postfix/pids/exec.nginx.wizard.php.check_all_websites_status_available.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$timeExec=$unix->file_time_min($pidTime);
		if($timeExec<15){return;}
		
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_www", "zavail")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `zavail` smallint(1) NOT NULL DEFAULT 0 , ADD INDEX ( `zavail`)");if(!$q->ok){echo $q->mysql_error_html();}}
	
	$results=$q->QUERY_SQL("SELECT servername,zavail FROM reverse_www WHERE enabled=1 ORDER BY zOrder");
	if($GLOBALS["VERBOSE"]){echo  "Scan ".mysql_num_rows($results)."\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=trim($ligne["servername"]);
		if($sitename==null){continue;}
		$zavail=$ligne["zavail"];
		$zavail2=check_all_websites_status_available_scan($sitename);
		if($GLOBALS["VERBOSE"]){echo "$sitename = $zavail2\n";}
		if($zavail<>$zavail2){
			$q->QUERY_SQL("UPDATE reverse_www SET `zavail`=$zavail2 WHERE servername='$sitename'");
		}
	}
	
	
}


function check_all_websites_http(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	
	$files=$unix->DirFiles("/etc/nginx/sites-enabled");
	while (list ($file, $line) = each ($files)){
		$main=preg_match_site($file);
		if(!$main){echo "Skip Site `$file`\n";continue;}
		$sitename=$main[0];
		if($GLOBALS["VERBOSE"]){echo "Found: $sitename\n";}
		$MAIN_ARRAY[$sitename]=true;
	}
	
	$results=$q->QUERY_SQL("SELECT servername FROM reverse_www WHERE enabled=0 ORDER BY zOrder");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=trim($ligne["servername"]);
		if($sitename==null){continue;}
		if(isset($MAIN_ARRAY[$sitename])){
			nginx_admin_mysql(1, "Ask to remove $sitename from configuration (site disabled)", null,__FILE__,__LINE__);
			system("$php /usr/share/artica-postfix/exec.nginx.single.php --remove \"$sitename\" --no-buildmain --no-reload");
		}
	}
	
	
	
	$results=$q->QUERY_SQL("SELECT servername,zOrder FROM reverse_www ORDER BY zOrder");
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		$q->QUERY_SQL("UPDATE reverse_www SET `zOrder`=$c WHERE `servername`='{$ligne["servername"]}'");
		
	}
	
	
	$files=$unix->DirFiles("/etc/nginx/sites-enabled");
	
	
	while (list ($file, $line) = each ($files)){
		$fullpath="/etc/nginx/sites-enabled/$file";
		$main=preg_match_site($file);
		if($file=="KILL"){@unlink($fullpath);continue;}
		if(!$main){
			echo "Skip Site `$file`\n";
			continue;
		}
		$servername=null;
		$sitename=$main[0];
		$sitename_port=intval($main[1]);
		$sitename_ssl=intval($main[2]);
		echo "Found $file Site `$sitename` port $sitename_port SSL=$sitename_ssl\n";
		
		
		
		if($sitename_ssl>0){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$sitename' AND `port`='$sitename_ssl' AND `enabled`=1"));
			if(!$q->ok){nginx_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);echo $q->mysql_error."\n"; build_progress("{failed} MySQL error",110); die(); }
			$servername=$ligne["servername"];
				
			if($servername==null){
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$sitename' AND `ssl`=1 AND `enabled`=1"));
				if(!$q->ok){nginx_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);echo $q->mysql_error."\n"; build_progress("{failed} MySQL error",110); die(); }
				$servername=$ligne["servername"];
			}
			
		}else{
			if($sitename_port>0){
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$sitename' AND `port`='$sitename_port' AND `enabled`=1"));
				if(!$q->ok){nginx_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);echo $q->mysql_error."\n"; build_progress("{failed} MySQL error",110); die(); }
				$servername=$ligne["servername"];
			}
			
			if($servername==null){
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$sitename' AND `enabled`=1"));
				if(!$q->ok){nginx_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);echo $q->mysql_error."\n"; build_progress("{failed} MySQL error",110); die(); }
				$servername=$ligne["servername"];
			}
			
		}
		
		if($servername==null){
			nginx_admin_mysql(1, "Removing Site `$sitename`", "Removed:$fullpath\nFound $file Site `$sitename` port $sitename_port SSL=$sitename_ssl",__FILE__,__LINE__);
			echo "Removing Site `$sitename` $fullpath\n";
			@unlink($fullpath);
		}
	}
	
	$dirs=$unix->dirdir("/var/log/apache2");
	$rm=$unix->find_program("rm");
	$q2=new mysql();
	while (list ($dirpath, $line) = each ($dirs)){
		$sitename=basename($dirpath);
		if($sitename=="unix-varrunnginx-authenticator.sock"){continue;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$sitename'"));
		if(!$q->ok){nginx_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);echo $q->mysql_error."\n"; continue;}
		if($ligne["servername"]<>null){continue;}
		
		$ligne=mysql_fetch_array($q2->QUERY_SQL("SELECT servername FROM freeweb WHERE servername='$sitename'","artica_backup"));
		if(!$q2->ok){nginx_admin_mysql(0, "MySQL Error", $q2->mysql_error,__FILE__,__LINE__);echo $q2->mysql_error."\n"; continue;}
		if($ligne["servername"]<>null){continue;}
		
		nginx_admin_mysql(1, "Removing logs Directory $dirpath for $sitename",__FILE__,__LINE__);
		echo "Directory: `$sitename` is not managed, remove it\n";
		system("$rm -rf $dirpath");
		
	}
	
	
	
	
}

