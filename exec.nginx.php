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



	$GLOBALS["ARGVS"]=implode(" ",$argv);
	
	if($argv[1]=="--dump-modules"){$GLOBALS["OUTPUT"]=true;dump_nginx_params();exit;}
	if($argv[1]=="--reconfigure-all-reboot"){$GLOBALS["OUTPUT"]=true;reconfigure_all();exit;}
	if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;configure_single_website($argv[2]);die();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--restart-build"){$GLOBALS["OUTPUT"]=true;restart_build();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
	if($argv[1]=="--force-restart"){$GLOBALS["OUTPUT"]=true;force_restart();die();}
	if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}
	if($argv[1]=="--main"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build(true);die();}
	if($argv[1]=="--artica-web"){articaweb();exit;}
	if($argv[1]=="--install-nginx"){install_nginx();exit;}
	if($argv[1]=="--status"){status();exit;}
	if($argv[1]=="--rotate"){rotate();exit;}
	if($argv[1]=="--awstats"){awstats();exit;}
	if($argv[1]=="--caches-status"){caches_status();exit;}
	if($argv[1]=="--framework"){$GLOBALS["OUTPUT"]=true;framework();exit;}
	if($argv[1]=="--tests-sources"){test_sources();exit;}
	if($argv[1]=="--reconfigure-all"){$GLOBALS["OUTPUT"]=true;build_localhosts();exit;}
	if($argv[1]=="--authenticator"){$GLOBALS["OUTPUT"]=true;authenticator(true);exit;}
	if($argv[1]=="--purge-cache"){$GLOBALS["OUTPUT"]=true;purge_cache($argv[2]);exit;}
	if($argv[1]=="--purge-all-caches"){$GLOBALS["OUTPUT"]=true;purge_all_caches();exit;}
	if($argv[1]=="--import-file"){$GLOBALS["OUTPUT"]=true;import_file();exit;}
	if($argv[1]=="--import-bulk"){$GLOBALS["OUTPUT"]=true;import_bulk();exit;}
	if($argv[1]=="--mem"){$GLOBALS["OUTPUT"]=true;parse_memory();exit;}
	if($argv[1]=="--mymem"){$GLOBALS["OUTPUT"]=true;max_memory();exit;}
	if($argv[1]=="--mail"){$GLOBALS["OUTPUT"]=true;mail_protocols();exit;}
	
	
	
	
	
	if($argv[1]=="--build-default"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RELOAD"]=true;build_default();exit;}
	
	echo "Unable to understand this command\n";
	echo "Should be:\n";
	echo "--framework...........: Build framework\n";
	echo "--caches-status.......: Build caches status\n";
	echo "--build-default.......: Build default website\n";
	
function mail_protocols(){
	$sock=new sockets();
	if(is_file("/etc/nginx/conf.d/mail.conf")){@unlink("/etc/nginx/conf.d/mail.conf");}
	$EnableNginxMail=intval($sock->GET_INFO("EnableNginxMail"));
	if($EnableNginxMail==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: EnableNginxMail: $EnableNginxMail, aborting\n";
		return;}
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$f[]="mail {";
	
	$f[]="\tserver_name       $hostname;";
	$f[]="\tauth_http         unix:/usr/share/artica-postfix/ressources/web/framework.sock:/auth.unix.php;";
	$f[]="";
	$f[]="\timap_capabilities IMAP4rev1 UIDPLUS IDLE LITERAL+ QUOTA;";
	$f[]="\timap_auth			login plain cram-md5;";
	$f[]="";
	$f[]="\tpop3_auth         plain apop cram-md5;";
	$f[]="\tpop3_capabilities LAST TOP USER PIPELINING UIDL;";
	$f[]="";
	$f[]="\tsmtp_auth         login plain cram-md5;";
	$f[]="\tsmtp_capabilities \"SIZE 10485760\" ENHANCEDSTATUSCODES PIPELINING 8BITMIME DSN;";
	$f[]="\txclient           off;";
	$f[]="\tproxy on;";
	$f[]="";

	
	
	$f[]="\tssl_prefer_server_ciphers on;";
	$f[]="\tssl_protocols TLSv1 SSLv3;";
	$f[]="\tssl_ciphers HIGH:!ADH:!MD5:@STRENGTH;";
	$f[]="\tssl_session_cache shared:MAIL:10m;";
	
	$nginx_certificate=new nginx_certificate();
	$f[]=$nginx_certificate->GetConf();
	
	$f[]="\tserver {";
	$f[]="\t\tlisten 25;";
	$f[]="\t\tprotocol smtp;";
	$f[]="\t\ttimeout 120000;";
	$f[]="\t\tsmtp_auth  none;";
	$f[]="\t}";	
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten     143;";
	$f[]="\t\tprotocol   imap;";
	$f[]="\t\tproxy      on;";
	$f[]="\t\tproxy_pass_error_message  on;";
	$f[]="\t}";
	$f[]="";
	
	$f[]="\tserver {";
	$f[]="\t\tlisten 465;";
	$f[]="\t\tprotocol smtp;";
	$f[]="\t\tssl on;";
	$f[]="\t}";
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten 587;";
	$f[]="\t\tprotocol smtp;";
	$f[]="\t\tstarttls on;";
	$f[]="\t}";
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten 110;";
	$f[]="\t\tprotocol pop3;";
	$f[]="\t\tstarttls on;";
	$f[]="\t}";
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten 995;";
	$f[]="\t\tprotocol pop3;";
	$f[]="\t\tssl on;";
	$f[]="\t\t}";
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten 143;";
	$f[]="\t\tprotocol imap;";
	$f[]="\t\tstarttls on;";
	$f[]="\t\t}";
	$f[]="";
	$f[]="\tserver {";
	$f[]="\t\tlisten 993;";
	$f[]="\t\tprotocol imap;";
	$f[]="\t\tssl on;";
	$f[]="\t}";
$f[]="}";
	

	
	
	
	return @implode("\n", $f);
	
}	

function nginx_version(){
	if(isset($GLOBALS["nginx_version"])){return $GLOBALS["nginx_version"];}
	$unix=new unix();
	$bin=$unix->find_program("nginx");
	if(!is_file($bin)){return 0;}

	exec("$bin -v 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#\/([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["nginx_version"]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "nginx_version(), $line, not found \n";}
	}

}

function dump_nginx_params(){
	
		$unix=new unix();
		$ARRAY=$unix->NGINX_COMPILE_PARAMS();
		$nginx=new nginx();

	while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
		echo "Module: \"$a\"\n";
		
	}
	
	echo "Substitutions: ".$nginx->IsSubstitutions()."\n";
	
}
	

function build($OnlySingle=false){
	if(isset($GLOBALS[__FILE__.__FUNCTION__])){return;}
	$GLOBALS[__FILE__.__FUNCTION__]=true;
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	shell_exec("/etc/init.d/mysql start");
	
	build_progress("{building_main_settings}",10);
	
	if($unix->SQUID_GET_LISTEN_PORT()==80){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Squid listen 80, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restarting Squid-cache..\n";}
		shell_exec("/etc/init.d/squid restart --script=".basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: done...\n";}
	}
	
	if($unix->SQUID_GET_LISTEN_SSL_PORT()==443){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Squid listen 443, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restarting Squid-cache..\n";}
		shell_exec("/etc/init.d/squid restart --script=".basename(__FILE__));	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: done...\n";}
	}
	
	$reconfigured=false;
	if($unix->APACHE_GET_LISTEN_PORT()==80){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache listen 80, ports conflicts, change it\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --build --force");
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --stop --force");
		shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --start --force");
		$reconfigured=true;
	}
	
	if(!$reconfigured){
		if($unix->APACHE_GET_LISTEN_PORT()==443){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache listen 443, ports conflicts, change it\n";}
			shell_exec("$php5 /usr/share/artica-postfix/exec.freeweb.php --build --force");
		}	
	}
	
	
	
	$APACHE_USER=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$NginxProxyStorePath="/home/nginx";
	@mkdir("/etc/nginx/sites-enabled",0755,true);
	@mkdir("/etc/nginx/local-sites",0755,true);
	@mkdir("/etc/nginx/local-sslsites",0755,true);
	@mkdir($NginxProxyStorePath,0755,true);
	@mkdir($NginxProxyStorePath."/tmp",0755,true);
	@mkdir($NginxProxyStorePath."/disk",0755,true);
	@mkdir("/var/lib/nginx/fastcgi",0755,true);
	@mkdir("/home/nginx/tmp",0755,true);
	
	$Tempdir=$unix->TEMP_DIR()."/nginx";
	@mkdir($Tempdir,0755,true);
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath);
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, "/etc/nginx/sites-enabled");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath."/tmp");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $NginxProxyStorePath."/disk");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, "/var/lib/nginx/fastcgi");
	$unix->chown_func($APACHE_USER,$APACHE_SRC_GROUP, $Tempdir);
	nginx_ulimit();
	$workers=$unix->CPU_NUMBER();

	
	build_progress("Building configuration",15);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running $APACHE_USER:$APACHE_SRC_GROUP..\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Running $workers worker(s)..\n";}
	

	
	if(is_file("/etc/nginx/sites-enabled/default")){@unlink("/etc/nginx/sites-enabled/default");}
	if(is_link("/etc/nginx/sites-enabled/default")){@unlink("/etc/nginx/sites-enabled/default");}
	if(is_link("/etc/nginx/conf.d/example_ssl.conf")){@unlink("/etc/nginx/conf.d/example_ssl.conf");}
	
	
	
	
	$limit=4096*$workers;
	if($limit>65535){$limit=65535;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Running limit of $limit open files\n";}
	
	$L=explode("\n",@file_get_contents("/etc/security/limits.conf"));
	$FOUNDL=false;
	$T=array();
	while (list ($index, $line) = each ($L)){
		$line=trim($line);
		if(trim($line)==null){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(preg_match("#^$APACHE_USER#", $line)){continue;}
		$T[]=$line;
	}
	
	if(!$FOUNDL){
		$T[]="$APACHE_USER       soft    nofile   $limit";
		$T[]="$APACHE_USER       hard    nofile   $limit";
	}
	
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $T)."\n");
	$L=array();
	$T=array();
	
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	$server_names_hash_bucket_size=128;
	$worker_connections=8192;
	
	
	if($MEMORY<624288){
		$server_names_hash_bucket_size=64;
		$worker_connections=1024;
		$workers=4;
	}
	
	$mail_protocols=mail_protocols();
	
	
	//
	
	$f[]="# Builded on ".date("Y-m-d H:i:s");
	$f[]="user   $APACHE_USER;";
	$f[]="worker_processes  $workers;";
	$nginx_version=nginx_version();
	
	preg_match("#^([0-9])+\.([0-9]+)\.#", $nginx_version,$re);
	$re[1]=intval($re[1]);
	$re[2]=intval($re[2]);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Major {$re[1]} Minor:{$re[2]}\n";}
	$syslog=false;
	
	$f[]="worker_rlimit_nofile 16384;";
	$f[]="timer_resolution 1ms;";
	$f[]="";
	if($re[1]>0){
		if($re[2]>6){
			$f[]="error_log syslog:server=127.0.0.1,facility=daemon info;";
			$syslog=true;
		}
	}
	
	
	$syslog=false;
	if(!$syslog){
		$f[]="error_log  /var/log/nginx/error.log warn;";
	}
	$f[]="pid        /var/run/nginx.pid;";
	$f[]="";
	$f[]="";
	$f[]="events {";
	$f[]="    worker_connections  $worker_connections;";
	$f[]="    multi_accept  on;";
	$f[]="    use epoll;";
	$f[]="	  accept_mutex_delay 1ms;";
	$f[]="}";
	
	$upstream=new nginx_upstream();
	$upstreams_servers=$upstream->build();
	
	$NginxBehindLB=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/NginxBehindLB"));
	$NginxLBIpaddr=@file_get_contents("/etc/artica-postfix/settings/Daemons/NginxLBIpaddr");
	if($NginxLBIpaddr==null){$NginxBehindLB=0;}
	$logf="-";

	$f[]="";
	$f[]="";
	$f[]="http {";
	$f[]="\tinclude /etc/nginx/mime.types;";
	
	
	if($NginxBehindLB==1){
		$f[]="\tset_real_ip_from   $NginxLBIpaddr;";
		$f[]="\treal_ip_header      X-Forwarded-For;";
		$logf="\$http_x_forwarded_for";
	}
	
	$f[]="\tlog_format  awc_log";
	$f[]="\t\t'ngx[\$server_name] \$remote_addr $logf \$remote_user [\$time_local] \$request '";
	$f[]="\t\t'\"\$status\" \$body_bytes_sent \"\$http_referer\" '";
	$f[]="\t\t'\"\$http_user_agent\" \"\$http_x_forwarded_for\" [\$upstream_cache_status]';";
	$f[]="";	
	
	
	$f[]="\tlimit_conn_zone \$binary_remote_addr zone=LimitCnx:10m;";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT LimitReqs,servername FROM reverse_www WHERE LimitReqs > 0");
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$ZoneName=str_replace(".", "", $servername);
		$ZoneName=str_replace("-", "", $servername);
		$ZoneName=str_replace("_", "", $servername);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, limit $servername/$servername {$ligne["LimitReqs"]}r/s\n";}
		$f[]="\tlimit_req_zone  \$binary_remote_addr  zone=$ZoneName:10m   rate={$ligne["LimitReqs"]}r/s;";
	}
	
	$nginxClass=new nginx();
	if($nginxClass->IsSubstitutions()){
		//$f[]="\tsubs_filter_types text/html text/css text/xml;";
	}
	

	
	
	@mkdir($Tempdir,0775,true);
	@mkdir("/home/nginx/tmp",0755,true);
	$f[]="\tlimit_conn_log_level info;";
	$f[]="\tclient_body_temp_path $Tempdir 1 2;";
	$f[]="\tclient_header_timeout 5s;";
	$f[]="\tclient_body_timeout 5s;";
	$f[]="\tsend_timeout 10m;";
	$f[]="\tconnection_pool_size 128k;";
	$f[]="\tclient_header_buffer_size 16k;";
	$f[]="\tlarge_client_header_buffers 1024 128k;";
	$f[]="\trequest_pool_size 128k;";
	$f[]="\tkeepalive_requests 1000;";
	$f[]="\tkeepalive_timeout 10;";
	$f[]="\tclient_max_body_size 10g;";
	$f[]="\tclient_body_buffer_size 1m;";
	$f[]="\tclient_body_in_single_buffer on;";
	$f[]="\topen_file_cache max=10000 inactive=300s;";
	$f[]="\treset_timedout_connection on;";
	$f[]="\ttypes_hash_max_size 8192;";
	$f[]="\tserver_names_hash_bucket_size 128;";
	$f[]="\tserver_names_hash_max_size 512;";
	$f[]="\tvariables_hash_max_size 512;";
	$f[]="\tvariables_hash_bucket_size 128;";
	

	
	$f[]="\tfastcgi_buffers 8 16k;";
	$f[]="\tfastcgi_buffer_size 32k;";
	$f[]="\tfastcgi_connect_timeout 300;";
	$f[]="\tfastcgi_send_timeout 300;";
	$f[]="\tfastcgi_read_timeout 300;";
	
	$f[]="map \$scheme \$server_https {";
	$f[]="default off;";
	$f[]="https on;";
	$f[]="}	";	

	$f[]="\tgzip on;";
	$f[]="\tgzip_disable msie6;";
	$f[]="\tgzip_static on;";
	$f[]="\tgzip_min_length 1100;";
	$f[]="\tgzip_buffers 16 8k;";
	$f[]="\tgzip_comp_level 9;";
	$f[]="\tgzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript;";
	$f[]="\tgzip_vary on;";
	$f[]="\tgzip_proxied any;";
	
	$f[]="\toutput_buffers 1000 128k;";
	$f[]="\tpostpone_output 1460;";
	$f[]="\tsendfile on;";
	$f[]="\tsendfile_max_chunk 256k;";
	$f[]="\ttcp_nopush on;";
	$f[]="\ttcp_nodelay on;";
	$f[]="\tserver_tokens off;";
	
	$dns=new resolv_conf();
	$sock=new sockets();
	
	if($sock->dnsmasq_enabled()){
		$resolver[]="127.0.0.1";
	}
	
	if($dns->MainArray["DNS1"]<>null){$resolver[]=$dns->MainArray["DNS1"];}
	if($dns->MainArray["DNS2"]<>null){$resolver[]=$dns->MainArray["DNS2"];}
	if($dns->MainArray["DNS3"]<>null){$resolver[]=$dns->MainArray["DNS3"];}

	
	$f[]="\tresolver ". @implode(" ", $resolver).";";
	$f[]="\tignore_invalid_headers on;";
	$f[]="\tindex index.html;";
	$f[]="\tadd_header X-CDN \"Served by myself\";";
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_caches  ORDER BY directory";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=$ligne["directory"];
		@mkdir($directory,0755,true);
		$unix->chown_func("www-data","www-data", $directory);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, cache `$directory`\n";}
		$f[]="\tproxy_cache_path $directory levels={$ligne["levels"]} keys_zone={$ligne["keys_zone"]}:{$ligne["keys_zone_size"]}m max_size={$ligne["max_size"]}G  inactive={$ligne["inactive"]} loader_files={$ligne["loader_files"]} loader_sleep={$ligne["loader_sleep"]} loader_threshold={$ligne["loader_threshold"]};";
		
		
	}
		
	
	
	$f[]="\tproxy_temp_path $NginxProxyStorePath/tmp/ 1 2;";
	$f[]="\tproxy_cache_valid 404 10m;";
	$f[]="\tproxy_cache_valid 400 501 502 503 504 1m;";
	$f[]="\tproxy_cache_valid any 4320m;";
	$f[]="\tproxy_cache_use_stale updating invalid_header error timeout http_404 http_500 http_502 http_503 http_504;";
	$f[]="\tproxy_next_upstream error timeout invalid_header http_404 http_500 http_502 http_503 http_504;";
	$f[]="\tproxy_redirect off;";
	$f[]="\tproxy_set_header Host \$http_host;";
	$f[]="\tproxy_set_header Server Apache;";
	$f[]="\tproxy_set_header Connection Close;";
	$f[]="\tproxy_pass_header Set-Cookie;";
	$f[]="\tproxy_pass_header User-Agent;";
	$f[]="\tproxy_set_header X-Accel-Buffering on;";
	$f[]="\tproxy_hide_header X-CDN;";
	$f[]="\tproxy_hide_header X-Server;";
	$f[]="\tproxy_intercept_errors off;";
	$f[]="\tproxy_ignore_client_abort on;";
	$f[]="\tproxy_connect_timeout 60s;";
	$f[]="\tproxy_send_timeout 60s;";
	$f[]="\tproxy_read_timeout 150s;";
	$f[]="\tproxy_buffer_size 64k;";
	$f[]="\tproxy_buffers 16384 128k;";
	$f[]="\tproxy_busy_buffers_size 256k;";
	$f[]="\tproxy_temp_file_write_size 128k;";
	$f[]="\tproxy_headers_hash_bucket_size 128;";
	$f[]="\tproxy_cache_min_uses 0;";
	$f[]="";
	$f[]="$upstreams_servers";
	
	
	$f[]="\tinclude /etc/nginx/sites-enabled/*.conf;";
	$f[]="\tinclude /etc/nginx/local-sites/*.conf;";
	$f[]="\tinclude /etc/nginx/conf.d/*.conf;";
	
	$f[]="\t}";
	$f[]=$mail_protocols;
	$f[]="";	
	@copy("/etc/nginx/nginx.conf","/etc/nginx/nginx.bak");
	@file_put_contents("/etc/nginx/nginx.conf", @implode("\n", $f));
	
	
	
	
	if(!$OnlySingle){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
		build_progress("Building default configuration",10);
		build_default(true);
		build_localhosts();
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Only single defined\n";}
	}
	
	if($GLOBALS["RECONFIGURE"]){
		$pid=PID_NUM();
		if(is_numeric($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reload pid $pid\n";}
			$kill=$unix->find_program("kill");
			unix_system_HUP($pid);
		}else{
			start(true);
		}
	}
	
	build_progress("Building configuration done",10);
	
}

function configure_single_freeweb($servername){
	$q=new mysql();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$servername'","artica_backup"));
	$free=new freeweb($servername);
	$NginxFrontEnd=$free->NginxFrontEnd;	
	$groupware=$free->groupware;
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername [$groupware]\n";}
	
	$NOPROXY["SARG"]=true;
	$NOPROXY["ARTICA_MINIADM"]=true;
	$NOPROXY["WORDPRESS"]=true;
	$NOPROXY[null]=true;
	
	$q2=new mysql_squid_builder();
	$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT cacheid FROM reverse_www WHERE servername='{$ligne["servername"]}'"));
	
	
	$host=new nginx($servername);

	
	if(isset($NOPROXY[$groupware])){
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
		$host->set_freeweb();
		$host->set_storeid($ligne2["cacheid"]);
		
	}
	if($free->groupware=="Z-PUSH"){$host->NoErrorPages=true;}
	if($free->groupware=="WORDPRESS"){$host->WORDPRESS=true;}
	$host->set_servers_aliases($free->Params["ServerAlias"]);
	
	if($groupware=="ZARAFA"){
		if($free->NginxFrontEnd==1){
			$host->groupware_zarafa_Frontend();
			configure_single_website_rebuild();
			configure_single_website_reload();
			return;
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername building configuration...\n";}
	$host->build_proxy();
	configure_single_website_rebuild();
	configure_single_website_reload();
	
}


function configure_single_website_rebuild(){
	LoadConfigs();
	build(true);
}


function configure_single_website($servername,$noreload=false,$nopid=false){
	$unix=new unix();
	$sock=new sockets();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}	
	$q=new mysql();
	$sql="SELECT servername from freeweb WHERE servername='$servername'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]<>null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername is a freeweb\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** NOTICE *** $servername is a freeweb\n";}
		configure_single_freeweb($servername);
		if(!$noreload){configure_single_website_reload(); }
		return;
	
	}
	
	
	$q=new mysql_squid_builder();

	$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1 AND servername='$servername'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] $servername $q->mysql_error\n";}return;}
	configure_single_website_rebuild();
	BuildReverse($ligne,true);
	if(!$noreload){configure_single_website_reload(); }
	
	
}
function configure_single_website_reload(){
	$unix=new unix();
	$pid=PID_NUM();
	if(is_numeric($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reload pid $pid\n";}
		$nginx=$unix->find_program("nginx");
		shell_exec("$nginx -c /etc/nginx/nginx.conf -s reload >/dev/null 2>&1");
		if($unix->process_exists($pid)){
			nginx_admin_mysql(1, "Nginx Web service reload done [action=start]", null,__FILE__,__LINE__);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Success service reloaded pid:$pid...\n";}
		}
	
	
	}else{
		start(true);
	}

}


function LoadConfigs(){
	if(isset($GLOBALS["LoadConfigs"])){return;}
	$GLOBALS["REMOVE_LOCAL_ADDR"]=false;
	$unix=new unix();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM reverse_www WHERE default_server=0"));
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL ** $q->mysql_error\n";}return;}
	if($ligne["tcount"]>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx *** NOTICE *** Defaults websites as been defined, no IP addresses are allowed\n";}
		$EnableArticaFrontEndToNGninx=0;$GLOBALS["REMOVE_LOCAL_ADDR"]=true;
	}
	
	if($GLOBALS["REMOVE_LOCAL_ADDR"]){$GLOBALS["IPADDRS"]=$unix->NETWORK_ALL_INTERFACES(true);unset($GLOBALS["IPADDRS"]["127.0.0.1"]);}
	$GLOBALS["LoadConfigs"]=true;	
}


function authenticator($alone=false){
	
	if($alone){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}	
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticate port /var/run/nginx-authenticator.sock\n";}
	$host=new nginx("unix:/var/run/nginx-authenticator.sock");
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix");
	$host->set_index_file("authenticator.php");
	$host->build_proxy();
	if($alone){
		stop(true);
		start(true);
	}
	
}

function build_localhosts(){
	if($GLOBALS["VERBOSE"]){echo "\n############################################################\n\n".__FUNCTION__.".".__LINE__.":Start...\n";}
	$squidR=new squidbee();
	$rev=new squid_reverse();
	$sock=new sockets();
	$unix=new unix();
	LoadConfigs();
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	
	$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
	if($NginxAuthPort==null){
		$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";
		$sock->SET_INFO("NginxAuthPort",$NginxAuthPort);	
	}
	
	
	

	
	if($EnableArticaFrontEndToNGninx==1){
		
		shell_exec("/etc/init.d/artica-webconsole stop >/dev/null 2>&1 &");
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
		if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort=9000;}
		$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');
		$host=new nginx($ArticaHttpsPort);
		$host->set_ssl();
		$host->set_listen_ip($LighttpdArticaListenIP);
		$host->set_proxy_disabled();
		$host->set_DocumentRoot("/usr/share/artica-postfix");
		$host->set_index_file("admin.index.php");
		$host->build_proxy();
	}
	
	$q=new mysql();
	
	foreach (glob("/etc/nginx/sites-enabled-backuped/*") as $filename) {@unlink($filename);}
	@mkdir("/etc/nginx/sites-enabled-backuped",0755,true);
	
	$q=new mysql_squid_builder();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, scanning /etc/nginx/sites-enabled\n";}
	
	build_progress("Backup old config",10);
	
	foreach (glob("/etc/nginx/local-sites/*") as $filename) {
		@unlink($filename);
	}
	
	
	foreach (glob("/etc/nginx/sites-enabled/*") as $filename) {
		
		$file=basename($filename);
		if(is_numeric($file)){@unlink($filename);continue;}
		$filedetect=$file;
		if(!preg_match("#freewebs-#", $file,$re)){continue;}
		if(preg_match("#_default_#", $file)){@unlink($filename);continue;}
			
		
		$filedetect=str_replace("freewebs-ssl-", "", $filedetect);
		$filedetect=str_replace("freewebs-", "", $filedetect);
		$filedetect=str_replace("freewebs-unix-", "", $filedetect);
		
		if(preg_match("#([0-9])-(.+?)\.([0-9]+)\.conf$#",$filedetect,$re)){$filedetect="{$re[2]}.{$re[3]}.conf"; }
		
		if(preg_match("#(.+?)\.[0-9]+\.conf$#",$filedetect,$re)){
			$sitename=$re[1];
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, [$sitename] backup/remove $filename\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup \"$file\"\n";}
			@copy($filename, "/etc/nginx/sites-enabled-backuped/$file");
			@unlink($filename);
			continue;
		}
			
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup \"$file\"\n";}
		@copy($filename, "/etc/nginx/sites-enabled-backuped/$file");
		@unlink($filename);
	}
	

	$NOPROXY["SARG"]=true;
	$NOPROXY["ARTICA_MINIADM"]=true;
	build_progress("Building Artica in Nginx (if set)",10);
	BuildArticaInNginx();
	build_progress("Building authenticator",10);
	authenticator(false);

}

function BuildArticaInNginx(){
	$sock=new sockets();
	$unix=new unix();
	$BuildFrameWorkInNginx=false;
	$EnableArticaFrontEndToNGninx=intval($sock->GET_INFO("EnableArticaFrontEndToNGninx"));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, EnableArticaFrontEndToNGninx = $EnableArticaFrontEndToNGninx\n";} 
	if($EnableArticaFrontEndToNGninx==0){return;}
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	
	if(!is_dir($SargOutputDir)){@mkdir($SargOutputDir,0755,true);}
	if(!is_file("$SargOutputDir/logo.gif")){@copy("/usr/share/artica-postfix/css/images/logo.gif", "$SargOutputDir/logo.gif");}
	if(!is_file("$SargOutputDir/pattern.png")){@copy("/usr/share/artica-postfix/css/images/pattern.png", "$SargOutputDir/pattern.png");}
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
	
	
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=1;}
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if(!is_file($phpfpm)){$EnablePHPFPM=0;}
	if($EnablePHPFPM==1){
		ToSyslog("Restarting PHP5-FPM");
		shell_exec("/etc/init.d/php5-fpm reload >/dev/null 2>&1");
	}
	
	$host=new nginx("0.0.0.0:9000");
	$host->set_ssl();
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix");
	$host->set_index_file("admin.index.php");
	$host->build_proxy();
	
	
	$lighttpdbin=$unix->find_program("lighttpd");
	if(!is_file($lighttpdbin)){$BuildFrameWorkInNginx=true;}
	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$BuildFrameWorkInNginx=true;}
	
	
	
	if($EnableSargGenerator==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, SARG is enabled...\n";}
		$host->SargDir();
		$host->build_proxy();
	}
		
	if($BuildFrameWorkInNginx){
		if(is_file("/etc/php5/fpm/pool.d/framework.conf")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, building framework...\n";}
			$host=new nginx(47980);
			$host->set_proxy_disabled();
			$host->set_DocumentRoot("/usr/share/artica-postfix/framework");
			$host->set_framework();
			$host->set_listen_ip("127.0.0.1");
			$host->set_servers_aliases(array("127.0.0.1"));
			$host->build_proxy();
		}
	}
	
	
	
}


function BuildReverse($ligne,$backupBefore=false){
	$q=new mysql_squid_builder();
	$ligne["servername"]=trim($ligne["servername"]);
	$IPADDRS=$GLOBALS["IPADDRS"];
	
	$ligne["servername"]=trim($ligne["servername"]);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  ************* {$ligne["servername"]}:{$ligne["port"]} / $DenyConf ************* \n";}
		
	if($ligne["port"]==82){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."] 82 port is an apache port, SKIP\n";}
		return;
	}
	
	if($GLOBALS["REMOVE_LOCAL_ADDR"]){
		if(isset($IPADDRS[$ligne["servername"]])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  {$ligne["servername"]} *** SKIPPED ***\n";}
			continue;
		}
	}
		
		

		
	if(isset($ALREADYSET[$ligne["servername"]])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[".__LINE__."]  `{$ligne["servername"]}` Already defined, abort\n";}
		continue;
	}
	$ListenPort=$ligne["port"];
	$SSL=$ligne["ssl"];
	
	if($ligne["owa"]==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, MS Exchange Web site...`\n";}
		
		
	}
	
		
	$certificate=$ligne["certificate"];
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, protect remote web site `{$ligne["servername"]}:$ListenPort [SSL:$SSL]`\n";}
	if($ligne["servername"]==null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, skip it...\n";}
		continue;
	}
	$cache_peer_id=$ligne["cache_peer_id"];
	if($cache_peer_id>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `reverse_sources` WHERE `ID`='$cache_peer_id'"));
	}
		
	$host=new nginx($ligne["servername"]);
		
	if($ListenPort==80 && $SSL==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, HTTP/HTTPS Enabled...\n";}
		$host->set_RedirectQueries($ligne["RedirectQueries"]);
		$host->set_forceddomain($ligne2["forceddomain"]);
		$host->set_ssl(0);
		$host->set_proxy_port($ligne2["port"]);
		$host->set_listen_port(80);
		$host->set_poolid($ligne["poolid"]);
		$host->set_owa($ligne["owa"]);
		$host->set_storeid($ligne["cacheid"]);
		$host->set_cache_peer_id($cache_peer_id);
		$host->BackupBefore=$backupBefore;
		$host->build_proxy();
	
		$host=new nginx($ligne["servername"]);
		$host->set_ssl_certificate($certificate);
		$host->set_ssl_certificate($ligne2["ssl_commname"]);
		$host->set_forceddomain($ligne2["forceddomain"]);
		$host->BackupBefore=$backupBefore;
		$host->set_ssl(1);
		$host->set_proxy_port($ligne2["port"]);
		$host->set_listen_port(443);
		$host->set_poolid($ligne["poolid"]);
		$host->set_owa($ligne["owa"]);
		$host->set_storeid($ligne["cacheid"]);
		$host->set_cache_peer_id($cache_peer_id);
		$host->build_proxy();
		
	}
		
	if($ligne["ssl"]==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, SSL Enabled...\n";}
		$ligne2["ssl"]=1;
	}
		
	if($ligne["port"]==443){
		$ligne2["ssl"]=1;
	}
	$host->BackupBefore=$backupBefore;
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
	
}


function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function rotate(){
	$unix=new unix();
	
	
	
	$pidTime="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidTime)<55){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());	
	
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	$NginxWorkLogsDir=$sock->GET_INFO("NginxWorkLogsDir");
	if($NginxWorkLogsDir==null){$NginxWorkLogsDir="/home/nginx/logsWork";}
	
	@mkdir("$NginxWorkLogsDir",0755,true);
	$directories=$unix->dirdir("/var/log/apache2");
	while (list ($directory, $line) = each ($directories)){
		$sitename=basename($directory);
		$date=date("Y-m-d-H");
		$nginx_source_logs="$directory/nginx.access.log";
		$nginx_dest_logs="$NginxWorkLogsDir/$sitename-$date.log";
		if(is_file("$nginx_dest_logs")){
			echo "$nginx_dest_logs no such file\n";
			continue;}
		if(!is_file($nginx_source_logs)){continue;}
		if(!@copy($nginx_source_logs, $nginx_dest_logs)){
			echo "Failed to copy $nginx_dest_logs\n";
			continue;
		}
		
		@unlink($nginx_source_logs);
		
	}
	
	$pid=PID_NUM();
	shell_exec("$kill -USR1 $pid");
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	
	
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	
	if($EnableNginxStats==0){
		shell_exec("$nohup $php5 ".__FILE__." --awstats >/dev/null 2>&1 &");
		return;
	}else{
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.nginx-stats.php --parse >/dev/null 2>&1 &");	
	}
	
	
	
}


function build_default($aspid=false){
	
	$sock=new sockets();
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Hostname $hostname\n";}
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	@unlink("/etc/nginx/conf.d/default.conf");
	
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	

	
	if($EnableFreeWeb==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Hostname $hostname FreeWeb is disabled\n";}
		return;
	}
	
	
	$f[]="server {";
	$f[]="\tlisten       80;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tproxy_cache_key \$host\$request_uri\$cookie_user;";
	$f[]="\tproxy_set_header Host \$host;";
	$f[]="\tproxy_set_header	X-Forwarded-For	\$proxy_add_x_forwarded_for;";
	$f[]="\tproxy_set_header	X-Real-IP	\$remote_addr;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\taccess_log   off;";
	$f[]="\tallow 127.0.0.1;";
	$f[]="\tdeny all;";
	$f[]="}";

	
	$squidR=new squidbee();
	$nginx=new nginx();
	
	$f[]="\tlocation / {";
	$f[]="\t\tproxy_pass http://127.0.0.1:82;";
	$f[]="\t}";
	$f[]="}\n";
	
	$f[]="server {";
	$f[]="\tlisten       443;";
	$f[]="\tkeepalive_timeout   70;";
	
	$f[]="\tssl on;";
	$f[]="\t".$squidR->SaveCertificate($unix->hostname_g(),false,true);
	$f[]="\tssl_session_timeout  5m;";
	$f[]="\tssl_protocols  SSLv3 TLSv1;";
	$f[]="\tssl_ciphers HIGH:!aNULL:!MD5;";
	$f[]="\tssl_prefer_server_ciphers   on;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tproxy_cache_key \$host\$request_uri\$cookie_user;";
	$f[]="\tproxy_set_header Host \$host;";
	$f[]="\tproxy_set_header	X-Forwarded-For	\$proxy_add_x_forwarded_for;";
	$f[]="\tproxy_set_header	X-Real-IP	\$remote_addr;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\taccess_log   off;";
	$f[]="\tallow 127.0.0.1;";
	$f[]="\tdeny all;";
	$f[]="}";
	
	
	
	$nginx=new nginx();
	$f[]=$nginx->webdav_containers();
	$f[]="\tlocation / {";
	$f[]="\t\tproxy_pass http://127.0.0.1:82;";
	$f[]="\t}";
	$f[]="}\n";	
	
	
	@file_put_contents("/etc/nginx/conf.d/default.conf", @implode("\n", $f));
	if($GLOBALS["RELOAD"]){reload(true);}
}



function build_default_asArtica(){
	$nginx=new nginx();
	$unix=new unix();
	$squidR=new squidbee();
	
	$f[]="server {";
	$f[]="\tlisten       80;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tindex     logon.php;";
	$f[]="\tlocation /nginx_status {";
	$f[]="\tstub_status on;";
	$f[]="\terror_log  /var/log/nginx/default.error.log warn;";
	$f[]="\taccess_log   /var/log/nginx/default.access.log;";
	$f[]="\tallow all;";
	$f[]="\t}";
	$f[]="\tlocation / {";
	$f[]="\t\troot\t/usr/share/artica-postfix;";
	$f[]="\t}";
	$f[]=$nginx->php_fpm("logon.php","/usr/share/artica-postfix",1);
	$f[]="}";
	
	$f[]="server {";
	$f[]="\tlisten       443;";
	$f[]="\tindex     logon.php;";
	$f[]="\tkeepalive_timeout   70;";
	$f[]="\terror_log  /var/log/nginx/default.error.log warn;";
	$f[]="\taccess_log   /var/log/nginx/default.access.log;";
	$f[]="\tssl on;";
	$f[]="\t".$squidR->SaveCertificate($unix->hostname_g(),false,true);
	$f[]="\tssl_session_timeout  5m;";
	$f[]="\tssl_protocols  SSLv3 TLSv1;";
	$f[]="\tssl_ciphers HIGH:!aNULL:!MD5;";
	$f[]="\tssl_prefer_server_ciphers   on;";
	$f[]="\tserver_name  ".$unix->hostname_g().";";
	$f[]="\tlocation / {";
	$f[]="\t\troot\t/usr/share/artica-postfix;";
	$f[]="\t}";
	$f[]=$nginx->php_fpm("logon.php","/usr/share/artica-postfix",1);
	$f[]="}";	
	@file_put_contents("/etc/nginx/conf.d/default.conf", @implode("\n", $f));
	if($GLOBALS["RELOAD"]){reload(true);}
}



function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("nginx"));
}

function GHOSTS_PID(){
	$unix=new unix();
	$f=array();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"nginx:\s+\"",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$f[]=$re[1];
		
	}
	if(count($f)==0){return;}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service Shutdown ". count($f)." processes...\n";}
	$kill=$unix->find_program("kill");
	while (list ($num, $pid) = each ($f)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx kill PID:$pid\n";}
		unix_system_kill_force($pid);
		
	}
	
}

//##############################################################################
function PID_PATH(){
	return '/var/run/nginx.pid';
}
//##############################################################################
function nginx_ulimit(){
	$setup=true;
	
	$unix=new unix();
	$ulimit=$unix->find_program("ulimit");
	if(is_file($ulimit)){shell_exec("$ulimit -n 65535 >/dev/null 2>&1");}

	
	$f=explode("\n",@file_get_contents("/etc/security/limits.conf"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#^www-data\s+-\s+65535#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ulimit true\n";}
			return;
		}
		
	}
	
	$f[]="www-data\t-\tnofile\t65535\n";
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ulimit setup done\n";}
	
}

function reload($aspid=false){
	force_restart();
	
}

function force_restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx reloading\n";}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx reloading PID $pid running since {$time}Mn\n";}
		$unix->KILL_PROCESS($pid,"HUP");
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx not running, start it\n";};
	start(true);
	
	
		
	
}


function reconfigure_all(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already executed",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{cleaning_old_configs}...",5);
	system("$php /usr/share/artica-postfix/exec.nginx.wizard.php --check-http");
	
	
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	build(false);
	
	$sql="SELECT servername FROM reverse_www WHERE enabled=1";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){build_progress("MySQL Error",110); echo $q->mysql_error."\n"; return;}	
	
	$start=10;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$start++;
		if($start>50){$start=50;}
		$servername=$ligne["servername"];
		build_progress("$servername...",$start);
		system("$php /usr/share/artica-postfix/exec.nginx.single.php \"$servername\" --no-reload --output --no-buildmain");
	}
	
	
	$sql="SELECT servername FROM freeweb WHERE enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){build_progress("MySQL Error",110); echo $q->mysql_error."\n"; return;}	
	$start=50;
	$php=$unix->LOCATE_PHP5_BIN();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$start++;
		if($start>80){$start=80;}
		$servername=$ligne["servername"];
		build_progress("$servername...",$start);
		system("$php /usr/share/artica-postfix/exec.nginx.single.php \"$servername\" --no-reload --output --no-buildmain");
	}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	build_progress("{stopping_service}",80);
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.": OK...\n";}
	stop(true);
	build_progress("{starting_service}",90);
	start(true);
	build_progress("{done}",100);
	nginx_admin_mysql(2, "Reconfiguring all Web sites done [action=start]", null,__FILE__,__LINE__);
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	

	nginx_admin_mysql(1, "Restart reverse-proxy service [action=info]", null,__FILE__,__LINE__);
	stop(true);
	start(true);
	
	
}

function restart_build(){
	
	
	build_progress_restart("{reconfiguring}",10);
	build(true);
	build_progress_restart("{stopping_service}",50);
	nginx_admin_mysql(1, "Restart reverse-proxy service by Admin [action=info]", null,__FILE__,__LINE__);
	stop(true);
	build_progress_restart("{starting_service}",90);
	if(!start(true)) {
		build_progress_restart("{starting_service} {failed}",110);
	}
	build_progress_restart("{starting_service} {success}",100);
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, not installed\n";}
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx {$MEMORY}K\n";}
		
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	
	
	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, is Wordpress Appliance\n";}
		$sock->SET_INFO("EnableNginx",1);
		if(!is_dir("/usr/share/wordpress-src")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Installing Wordpress\n";}
			shell_exec("$php /usr/share/artica-postfix/exec.wordpress.download.php");
		}
		
		$EnableNginx=1;
	}
	
	
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service \"EnableNginx\" = $EnableNginx\n";}
	if($SquidAllow80Port==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service disabled (SquidAllow80Port)\n";}
		return false;
	}
	
	if($EnableNginx==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service disabled\n";}
		return false;
	}
	GHOSTS_PID();
	@mkdir("/home/nginx/tmp",0755,true);
	@mkdir("/var/log/nginx",0755,true);
	$nohup=$unix->find_program("nohup");
	$fuser=$unix->find_program("fuser");
	$kill=$unix->find_program("kill");
	$results=array();
	$FUSERS=array();
	
	
	$unix->KILL_PROCESSES_BY_PORT(80);
	$unix->KILL_PROCESSES_BY_PORT(443);
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	
	
	if($unix->is_socket("/var/run/nginx-authenticator.sock")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Remove authenticator socket\n";}
		@unlink("/var/run/nginx-authenticator.sock");
	}
	
	if(is_file("/var/run/nginx-authenticator.sock")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Remove authenticator socket\n";}
		@unlink("/var/run/nginx-authenticator.sock");
	}	
	
	nginx_mime_types();
	

	@unlink("/etc/nginx/conf.d/default.conf");
	
		
	
	$cmd="$nginx -c /etc/nginx/nginx.conf";
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting $i/6...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		nginx_admin_mysql(2, "Nginx Web service success to start [action=info]", null,__FILE__,__LINE__);
		@unlink($GLOBALS["pidStampReload"]);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service Success service started pid:$pid...\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.php-fpm.php --start >/dev/null 2>&1 &");
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.nginx.wizard.php --avail-status --force >/dev/null 2>&1 &");
		return true;
	}
	return false;
	nginx_admin_mysql(0, "Nginx Web service failed to start [action=info]", null,__FILE__,__LINE__);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
}

function nginx_mime_types(){
$f[]="\ntypes {";
$f[]="    text/html                             html htm shtml;";
$f[]="    text/css                              css;";
$f[]="    text/xml                              xml;";

$f[]="    application/atom+xml                  atom;";
$f[]="    application/rss+xml                   rss;";
$f[]="";
$f[]="    text/mathml                           mml;";
$f[]="    text/plain                            txt;";
$f[]="    text/plain                            version;";
$f[]="    text/vnd.sun.j2me.app-descriptor      jad;";
$f[]="    text/vnd.wap.wml                      wml;";
$f[]="    text/x-component                      htc;";
$f[]="";
$f[]="    image/gif                             gif;";
$f[]="    image/jpeg                            jpeg jpg;";
$f[]="    image/png                             png;";
$f[]="    image/tiff                            tif tiff;";
$f[]="    image/vnd.wap.wbmp                    wbmp;";
$f[]="    image/x-icon                          ico;";
$f[]="    image/x-jng                           jng;";
$f[]="    image/x-ms-bmp                        bmp;";
$f[]="    image/svg+xml                         svg svgz;";
$f[]="    image/webp                            webp;";
$f[]="";
$f[]="    application/octet-stream				ovf ova xva hdx;";
$f[]="    application/java-archive              jar war ear;";
$f[]="    application/mac-binhex40              hqx;";
$f[]="    application/msword                    doc;";
$f[]="    application/pdf                       pdf;";
$f[]="    application/x-tar						tar;";
$f[]="    application/x-bzip2					bz2;";
$f[]="    application/x-deb						deb;";
$f[]="    application/x-javascript				js;";
$f[]="    application/x-gzip					gz;";
$f[]="    application/postscript                ps eps ai;";
$f[]="    application/rtf                       rtf;";
$f[]="    application/vnd.ms-excel              xls;";
$f[]="    application/vnd.ms-powerpoint         ppt;";
$f[]="    application/vnd.wap.wmlc              wmlc;";
$f[]="    application/vnd.google-earth.kml+xml  kml;";
$f[]="    application/vnd.google-earth.kmz      kmz;";
$f[]="    application/x-7z-compressed           7z;";
$f[]="    application/x-cocoa                   cco;";
$f[]="    application/x-java-archive-diff       jardiff;";
$f[]="    application/x-java-jnlp-file          jnlp;";
$f[]="    application/x-makeself                run;";
$f[]="    application/x-perl                    pl pm;";
$f[]="    application/x-pilot                   prc pdb;";
$f[]="    application/x-rar-compressed          rar;";
$f[]="    application/x-redhat-package-manager  rpm;";
$f[]="    application/x-sea                     sea;";
$f[]="    application/x-shockwave-flash         swf;";
$f[]="    application/x-stuffit                 sit;";
$f[]="    application/x-tcl                     tcl tk;";
$f[]="    application/x-x509-ca-cert            der pem crt;";
$f[]="    application/x-xpinstall               xpi;";
$f[]="    application/xhtml+xml                 xhtml;";
$f[]="    application/zip                       zip;";
$f[]="    application/x-gtar-compressed 		tgz;";
$f[]="";
$f[]="    application/binary					bin;";
$f[]="    application/octet-stream              exe dll;";
$f[]="    application/octet-stream              dmg;";
$f[]="    application/octet-stream              eot;";
$f[]="    application/octet-stream              iso img;";
$f[]="    application/octet-stream              msi msp msm;";
$f[]="";
$f[]="    audio/midi                            mid midi kar;";
$f[]="    audio/mpeg                            mp3;";
$f[]="    audio/ogg                             ogg;";
$f[]="    audio/x-m4a                           m4a;";
$f[]="    audio/x-realaudio                     ra;";
$f[]="";
$f[]="    video/3gpp                            3gpp 3gp;";
$f[]="    video/mp4                             mp4;";
$f[]="    video/mpeg                            mpeg mpg;";
$f[]="    video/quicktime                       mov;";
$f[]="    video/webm                            webm;";
$f[]="    video/x-flv                           flv;";
$f[]="    video/x-m4v                           m4v;";
$f[]="    video/x-mng                           mng;";
$f[]="    video/x-ms-asf                        asx asf;";
$f[]="    video/x-ms-wmv                        wmv;";
$f[]="    video/x-msvideo                       avi;";
$f[]="}\n";
@file_put_contents("/etc/nginx/mime.types", @implode("\n", $f));
if(is_file("/etc/nginx/mime.types.default")){@unlink("/etc/nginx/mime.types.default");}
}


function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service already stopped...\n";}
		GHOSTS_PID();
		return;
	}
	
	
	
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	$nginx=$unix->find_program("nginx");

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service Shutdown pid $pid...\n";}
	shell_exec("$nginx -c /etc/nginx/nginx.conf -s stop >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service success...\n";}
		GHOSTS_PID();
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Nginx service failed...\n";}
	GHOSTS_PID();
}

function install_nginx($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [install_nginx]: nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){echo "Already installed\n";return;}
	$aptget=$unix->find_program("apt-get");
	if(!is_file($aptget)){echo "apt-get, no such binary...\n";die();}	
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Check debian repository...\n";
	shell_exec("$php /usr/share/artica-postfix/exec.apt-get.php --nginx");
	echo "installing nginx\n";
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install nginx 2>&1";
	system($cmd);
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){echo "Failed\n";return;}
	shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php");
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --build");
	system("/etc/init.d/nginx restart");
}

function articaweb(){
	echo "************ \n\n** Installing nginx ** \n\n************\n";
	install_nginx(true);
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){echo "nginx not installed cannot find binary `nginx`\n";die();}
	$sock=new sockets();
	echo "Transfert Artica front-end to nginx\n";
	$sock->SET_INFO("EnableArticaFrontEndToNGninx", 1);
	echo "Stopping lighttpd\n";
	shell_exec("/etc/init.d/artica-webconsole stop");
	echo "Set starting script\n";
	shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php");
	echo "Restarting nginx...\n";
	system("/etc/init.d/nginx restart");

}

function status(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl";
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidTime)<5){return;}
	}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	

	
	caches_status();
	
	
	
	@unlink($pidTime);
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777,true);
	@file_put_contents($pidTime, time());
	@chmod($pidTime,0777);
	rotate();
	
}


function caches_status(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$sql="SELECT directory,ID FROM nginx_caches";
	
	if(!$q->FIELD_EXISTS("nginx_caches", "CurrentSize")){
		$q->QUERY_SQL("ALTER TABLE `nginx_caches` ADD `CurrentSize` BIGINT UNSIGNED DEFAULT '0', ADD INDEX ( `CurrentSize` )");
	
	}
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){
		echo $q->mysql_error."\n------------------------\n$sql\n------------------------\n";
		build_progress_caches("MySQL error !",110);
		return ;
	}
	
	$Sum=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "$Sum caches..\n";}
	$c=0;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		$directorySize=$unix->DIRSIZE_BYTES($ligne["directory"]);
		$prc=$c/$Sum;
		$prc=$prc*100;
		if($prc>90){$prc=90;}
		if($GLOBALS["OUTPUT"]){echo "{$ligne["directory"]} $directorySize..\n";}
		build_progress_caches("{$ligne["directory"]} $directorySize",$prc);
		$q->QUERY_SQL("UPDATE nginx_caches SET CurrentSize='$directorySize' WHERE ID='{$ligne["ID"]}'");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress_caches("MySQL error !",110);
			return ;
		}	
	}	
	
	build_progress_caches("{done}",100);
	
}

function awstats(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($unix->file_time_min($pidTime)<60){return;}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$sock=new sockets();
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	if($EnableNginxStats==1){return;}
	
	include_once(dirname(__FILE__)."/ressources/class.awstats.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
	
	$awstats_bin=$unix->LOCATE_AWSTATS_BIN();
	$nice=EXEC_NICE();
	$perl=$unix->find_program("perl");
	$awstats_buildstaticpages=$unix->LOCATE_AWSTATS_BUILDSTATICPAGES_BIN();
	if($GLOBALS["VERBOSE"]){
		echo "awstats......: $awstats_bin\n";
		echo "statics Pages: $awstats_buildstaticpages\n";
		echo "Nice.........: $nice\n";
		echo "perl.........: $perl\n";
	}
	
	if(!is_file($awstats_buildstaticpages)){
		echo "buildstaticpages no such binary...\n";
		return;
	}
	
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	$NginxWorkLogsDir=$sock->GET_INFO("NginxWorkLogsDir");
	if($NginxWorkLogsDir==null){$NginxWorkLogsDir="/home/nginx/logsWork";}
	$sys=new mysql_storelogs();
	$files=$unix->DirFiles($NginxWorkLogsDir,"-([0-9\-]+)\.log");
	while (list ($filename, $line) = each ($files) ){
		
		if(!preg_match("#^(.+?)-[0-9]+-[0-9]+-[0-9]+-[0-9]+\.log$#", $filename,$re)){
			if($GLOBALS["VERBOSE"]){echo "$filename, skip\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$filename, domain:{$re[1]}\n";}
		$servername=$re[1];
		$GLOBALS["nice"]=$nice;
		$aw=new awstats($servername);
		$aw->set_LogFile("$NginxWorkLogsDir/$filename");
		$aw->set_LogType("W");
		$aw->set_LogFormat(1);
		$config=$aw->buildconf();
		$SOURCE_FILE_PATH="$NginxWorkLogsDir/$filename";
		
		
		$configlength=strlen($config);
		if($configlength<10){
			if($GLOBALS["VERBOSE"]){echo "configuration file lenght failed $configlength bytes, aborting $servername\n";}
			return;
		}
		
		@file_put_contents("/etc/awstats/awstats.$servername.conf",$config);
		@chmod("/etc/awstats/awstats.$servername.conf",644);
		$Lang=$aw->GET("Lang");
		if($Lang==null){$Lang="auto";}
		@mkdir("/var/tmp/awstats/$servername",666,true);		
		$t1=time();
		$cmd="$nice$perl $awstats_buildstaticpages -config=$servername -update -lang=$Lang -awstatsprog=$awstats_bin -dir=/var/tmp/awstats/$servername -LogFile=\"$SOURCE_FILE_PATH\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		shell_exec($cmd);	
		$filedate=date('Y-m-d H:i:s',filemtime($SOURCE_FILE_PATH));
		if(!awstats_import_sql($servername)){continue;}
		$sys->ROTATE_TOMYSQL($SOURCE_FILE_PATH, $filedate);
		
		
		
	}
}

function awstats_import_sql($servername){
	$q=new mysql();
	$unix=new unix();


	$sql="DELETE FROM awstats_files WHERE `servername`='$servername'";
	$q->QUERY_SQL($sql,"artica_backup");

	foreach (glob("/var/tmp/awstats/$servername/awstats.*") as $filename) {
			
		if(basename($filename)=="awstats.$servername.html"){
			$awstats_filename="index";
		}else{
			if(preg_match("#awstats\.(.+)\.([a-z0-9]+)\.html#",$filename,$re)){$awstats_filename=$re[2];}
		}
		if($GLOBALS["VERBOSE"]){echo "$servername: $awstats_filename\n";}
		if($awstats_filename<>null){
			$content=addslashes(@file_get_contents("$filename"));
			$results[]="Importing $filename";
			@unlink($filename);
			$sql="INSERT INTO awstats_files (`servername`,`awstats_file`,`content`)
			VALUES('$servername','$awstats_filename','$content')";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
				$unix->send_email_events("awstats for $servername failed database error",$q->mysql_error,"system");
				return false;
			}
		}
		$q->ok;
	}
	
	return true;

}

function framework(){
	$unix=new unix();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Framework...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	if(!is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){
	$lighttpdbin=$unix->find_program("lighttpd");
		if(is_file($lighttpdbin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $lighttpdbin OK turn, to lighttpd...\n";}
			return;
		}

	}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.php-fpm.php --build");
	}
	
	if(!is_file("/etc/php5/fpm/pool.d/framework.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Unable to stat framework settings\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, building framework...\n";}
	$host=new nginx(47980);
	
	$host->set_proxy_disabled();
	$host->set_DocumentRoot("/usr/share/artica-postfix/framework");
	$host->set_framework();
	$host->build_proxy();

	$PID=PID_NUM();
	if(!$unix->process_exists($PID)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, not started, start it...\n";}
		start(true);
	}
	
	$kill=$unix->find_program("kill");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, reloading PID $PID\n";}
	shell_exec("$kill -HUP $PID >/dev/null 2>&1");
	
}

function test_sources(){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";} 
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTime<15){return;}
		@file_put_contents($pidfile, getmypid());
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	}
	
	$echo=$unix->find_program("echo");
	$nc=$unix->find_program("nc");
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccess")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccess` smallint(1) NOT NULL DEFAULT '1', ADD INDEX ( `isSuccess`)");
	}
	
	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccesstxt")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccesstxt` TEXT");
	}

	if(!$q->FIELD_EXISTS("reverse_sources", "isSuccessTime")){
		$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccessTime` datetime");
	}	
	
	$sql="SELECT * FROM reverse_sources";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ipaddr=$ligne["ipaddr"];
		$ID=$ligne["ID"];
		$port=$ligne["port"];
		$IsSuccess=1;
		$linesrows=array();
		$cmdline="$echo -e -n \"GET / HTTP/1.1\\r\\n\" | $nc -q 2 -v  $ipaddr $port 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$ipaddr: $cmdline\n";}
		exec($cmdline,$linesrows);
		while (list ($a, $b) = each ($linesrows) ){
			if($GLOBALS["VERBOSE"]){echo "$ipaddr: $b\n";}
			if(preg_match("#failed#", $b)){$IsSuccess=0;}}
		reset($linesrows);
		$linesrowsText=mysql_escape_string2(base64_encode(serialize($linesrows)));
		$date=date("Y-m-d H:i:s");
		$q->QUERY_SQL("UPDATE reverse_sources SET isSuccess=$IsSuccess,isSuccesstxt='$linesrowsText',isSuccessTime='$date' WHERE ID=$ID");
		
	}
}

function purge_all_caches(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT directory FROM nginx_caches");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]=$ligne["directory"];
	}
	$f[]="/home/nginx/tmp";
	$rm=$unix->find_program("rm");
	while (list ($index, $value) = each ($f) ){
		if(!is_dir($value)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value\n";}
		shell_exec("$rm -rf $value/*");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $value OK\n";}
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Reloading service\n";}
	reload(true);
	
}




function purge_cache($ID){
	if(!is_numeric($ID)){return;}
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM nginx_caches WHERE ID='$ID'"));
	$directory=$ligne["directory"];
	if(!is_dir($directory)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [PURGE]: `$directory` no such directory\n";}
	}
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf \"$directory\"");
	@mkdir($directory,true,0755);
	reload(true);
	caches_status();
	
}

function import_file(){
	$q=new mysql_squid_builder();
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.import";
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	
	$f=explode("\n",@file_get_contents($filename));
	
	$IpClass=new IP();
	while (list ($index, $line) = each ($f)){
		if(trim($line)==null){continue;}
		if(strpos($line, ",")==0){continue;}
		$tr=explode(",",$line);
		if(count($tr)<2){continue;}
		$sourceserver=trim($tr[0]);
		$sitename=trim($tr[1]);
		if(!isset($tr[2])){$tr[2]=0;}
		if(!isset($tr[3])){$tr[3]=null;}
		$ssl=$tr[2];
		$forceddomain=$tr[3];
		if(!preg_match("#(.+?):([0-9]+)#", $sourceserver,$re)){
			if($ssl==1){$sourceserver_port=443;}
			if($ssl==0){$sourceserver_port=80;}
		}else{
			$sourceserver=trim($re[1]);
			$sourceserver_port=$re[2];
		}
		
		
		if(!preg_match("#(.+?):([0-9]+)#", $sitename,$re)){
			if($ssl==1){$sitename_port=443;}
			if($ssl==0){$sitename_port=80;}
		}else{
			$sitename=trim($re[1]);
			$sitename_port=$re[2];
		}	
		
		if($forceddomain<>null){$title_source=$forceddomain;}else{$title_source=$sourceserver;}
		echo "Importing $sitename ($sitename_port) -> $sourceserver ($sourceserver_port)\n";
		// On cherche la source:
		
		if($sitename==null){
			 echo "Local sitename is null\n";
			 continue;
		}
		
		if($sourceserver==null){
			echo "Source is null\n";
			continue;
		}	

		if(!$IpClass->isValid($sourceserver)){
			$tcp=gethostbyname($sourceserver);
			if(!$IpClass->isValid($tcp)){
				echo "Source $sourceserver cannot be resolved\n";
				continue;
			}	
		}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
		$IDS=intval($ligne["ID"]);
		
		if($IDS==0){
			$sql="INSERT IGNORE INTO `reverse_sources` (`servername`,`ipaddr`,`port`,`ssl`,`enabled`,`forceddomain`)
			VALUES ('$title_source','$sourceserver','$sourceserver_port','$ssl',1,'$forceddomain')";
			$q->QUERY_SQL($sql);
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
			$IDS=intval($ligne["ID"]);
			
		}
		
		if($IDS==0){
			echo "Failed to add $sourceserver/$sourceserver_port/$forceddomain\n";
			continue;
		}

		
		// On attaque  le site web:
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,cache_peer_id FROM reverse_www WHERE servername='$sitename'"));
		if(trim($ligne["servername"]<>null)){
			echo "$sitename already exists on cache ID : {$ligne["cache_peer_id"]}/$IDS\n";
			if($ligne["cache_peer_id"]<>$IDS){
				$q->QUERY_SQL("UPDATE reverse_www SET `cache_peer_id`=$IDS WHERE  servername='$sitename'");
			}
			continue;
		}
		
		$sql="INSERT IGNORE INTO `reverse_www` (`servername`,`cache_peer_id`,`port`,`ssl`) VALUES
		('$sitename','$IDS','$sitename_port','$ssl')";
	
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;continue;}
		
		
	}
	
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 ".__FILE__." --restart >/dev/null 2>&1 &");
	
	
	
}

function import_bulk(){
	$q=new mysql_squid_builder();
	$nginxSources=new nginx_sources();
	$nginx=new nginx();
	$filename="/usr/share/artica-postfix/ressources/logs/web/nginx.importbulk";
	if(!is_file($filename)){echo "$filename no such file\n";return;}	
	$CONF=unserialize(@file_get_contents($filename));
	
	
	
	if($CONF["RemoveOldImports"]==1){
		// on supprime les anciennes entrées:
		$results=$q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE `Imported`=1");
		while ($ligne = mysql_fetch_assoc($results)) {
			$nginxSources->DeleteSource($ligne["ID"]);
		}
		
		$results=$q->QUERY_SQL("SELECT servername FROM reverse_www WHERE `Imported`=1");
		while ($ligne = mysql_fetch_assoc($results)) {
			$nginx->Delete_website($ligne["servername"],true);
		}		
		
		
	}
	
	$randomArray[1]="a";
	$randomArray[2]="b";
	$randomArray[3]="c";
	$randomArray[4]="d";
	$randomArray[5]="e";
	$randomArray[6]="f";
	$randomArray[7]="g";
	$randomArray[8]="h";
	$randomArray[9]="i";
	$randomArray[10]="j";
	$randomArray[11]="k";
	$randomArray[12]="l";
	$randomArray[13]="m";
	$randomArray[14]="n";
	$randomArray[15]="o";
	$randomArray[16]="p";
	$randomArray[17]="q";
	$randomArray[18]="r";
	$randomArray[19]="s";
	$randomArray[20]="t";
	$randomArray[21]="u";
	$randomArray[22]="v";
	$randomArray[23]="x";
	$randomArray[24]="y";
	$randomArray[25]="z";
	$RandomText=$CONF["RandomText"];
	$digitAdd=0;
	$webauth=null;
	$authentication_id=$CONF["authentication"];
	if(!is_numeric($authentication_id)){$authentication_id=0;}
	
	
	if($authentication_id>0){
		$AUTHENTICATOR["USE_AUTHENTICATOR"]=1;
		$AUTHENTICATOR["AUTHENTICATOR_RULEID"]=$authentication_id;
		$webauth=mysql_escape_string2(base64_encode(serialize($AUTHENTICATOR)));
	}
	
	
	
	if(preg_match("#\%sx([0-9]+)#", $RandomText,$re)){
		$digitAdd=intval($re[1]);
		$RandomText=str_replace("%sx{$re[1]}", "%s", $RandomText);
		
	}
	
	echo "Random: $RandomText\n";
	

	
	// on parse le fichier en première passe pour le cleaner
	
	$f=explode("\n",$CONF["import"]);
	while (list ($index, $line) = each ($f)){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if(preg_match("#^http.*?:\/#", $line)){
			// c'est une URI, on la décompose
			$URZ=parse_url($line);
			if(!isset($URZ["host"])){echo "$line -> Unable to determine HOST, skipping\n";}
			$MAIN[$URZ["host"]]=$URZ["scheme"];
			continue;
			
		}
		$MAIN[$line]="http";
		
		
	}
	
	ksort($MAIN);
	$i=1;
	$Letter=1;
	$IpClass=new IP();
	$SUCCESS=0;
	$FAILED=0;
	while (list ($servername, $proto) = each ($MAIN)){
		$LetterText=$randomArray[$Letter];
		$iText=$i;
		$ssl=0;
		if($digitAdd>0){$iText = sprintf("%1$0{$digitAdd}d", $i); }
		$SourceWeb=$RandomText;
		if($SourceWeb<>null){
			$SourceWeb=str_replace("%a", $LetterText, $SourceWeb);
			$SourceWeb=str_replace("%s", $iText, $SourceWeb);
			
		}else{
			$SourceWeb=$servername;
		}
		$sourceserver="$proto://$servername";
		echo "$proto://$servername\n";
		if($proto=="http"){$sourceserver_port=80;}
		if($proto=="https"){$sourceserver_port=443;$ssl=1;}
		if(preg_match("#(.+?):([0-9]+)#", $servername,$re)){$sourceserver_port=$re[1];}
		
		//existe-t-il ? 
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
		$IDS=intval($ligne["ID"]);
		
		if($IDS==0){
			//non -> Ajout de l'entrée...
			$sql="INSERT IGNORE INTO `reverse_sources` 
			(`servername`,`ipaddr`,`port`,`ssl`,`enabled`,`forceddomain`,`Imported`)
			VALUES ('$servername','$sourceserver','$sourceserver_port','$ssl',1,'$servername',1)";
			$q->QUERY_SQL($sql);
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM reverse_sources WHERE ipaddr='$sourceserver' AND `port`='$sourceserver_port'"));
			$IDS=intval($ligne["ID"]);
				
		}
		
		if($IDS==0){
			echo "Failed to add $sourceserver/$sourceserver_port/$servername\n";
			$FAILED++;
			continue;
		}
		
		
		// On attaque  le site web:
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,cache_peer_id FROM reverse_www WHERE servername='$SourceWeb'"));
		if(trim($ligne["servername"]<>null)){
			echo "$SourceWeb already exists on cache ID : {$ligne["cache_peer_id"]}/$IDS\n";
			if($ligne["cache_peer_id"]<>$IDS){
			$q->QUERY_SQL("UPDATE reverse_www SET `cache_peer_id`=$IDS WHERE  servername='$SourceWeb'");
			}
			$SUCCESS++;
			continue;
		}
		
		$sql="INSERT IGNORE INTO `reverse_www` (`servername`,`cache_peer_id`,`port`,`ssl`,`Imported`,`webauth`) VALUES
		('$SourceWeb','$IDS','$sourceserver_port','$ssl',1,'$webauth')";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;$FAILED++;continue;}	
		$SUCCESS++;	
		
		
		$i++;
		$Letter++;
		if($Letter>25){$Letter=1;}
	}
	
	
	echo "$SUCCESS Imported sites, $FAILED failed\n";
	
}
function build_progress_restart($text,$pourc){
	$filename=basename(__FILE__);

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


	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/nginx.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}

}
function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	
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
	
	
	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/nginx.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}

}
function build_progress_caches($text,$pourc){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,[{$pourc}%] $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/nginx-caches.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function max_memory(){
	$unix=new unix();
	$MyMEM=$unix->MEM_TOTAL_INSTALLEE()/1000;
	$MyMEM=$MyMEM-1600;
	$sock=new sockets();
	$NginxMaxMemToUse=intval($sock->GET_INFO("NginxMaxMemToUse"));
	if($NginxMaxMemToUse==0){$NginxMaxMemToUse=75;}
	$NginxMaxMemToUse=$NginxMaxMemToUse/100;
	$MyMEM=round($MyMEM*$NginxMaxMemToUse);
	return $MyMEM;
	
	
}

function parse_memory(){
	$unix=new unix();
	
	$nginx=$unix->find_program("nginx");
	$sock=new sockets();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, not installed\n";}
		return;
	}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidStampReload=$GLOBALS["pidStampReload"];
	
	if(!$GLOBALS["VERBOSE"]){
	echo "$pidtime\n";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
			return;
	}
	
	$TimExec=$unix->file_time_min($pidtime);
	if($TimExec<5){return;}
	}
	@file_put_contents($pidfile, getmypid());
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	$python=$unix->find_program("python");
	$nice=$unix->EXEC_NICE();
	exec("{$nice}$python /usr/share/artica-postfix/bin/ps_mem.py 2>&1",$results);
	$FOUND=false;
	while (list ($index, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^[0-9\.]+.*?=\s+([0-9\.]+)\s+(.+?)\s+nginx#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Not found \"$line\"\n";}
			continue;}
			$memoryValue=$re[1];
			$unit=trim(strtolower($re[2]));
			echo "Found $memoryValue $unit\n";
			if($unit=="kib"){$memoryValue=$memoryValue/1048.576;}
			if($unit=="mib"){$memoryValue=$memoryValue*1.048576;}
			if($unit=="gib"){$memoryValue=$memoryValue*1048.576;}
			$FOUND=true;
			break;
		
		
	}
	
	if(!$FOUND){
		if($GLOBALS["VERBOSE"]){echo "Not found...\n";}
		return;
	}
	$memoryValue=round($memoryValue,2);
	
	$MaxMemory=max_memory();
	$MaxMemoryReload=$MaxMemory/2;
	$memoryValueInt=intval($memoryValue);
	echo "Nginx = $memoryValue MB  INT($memoryValueInt) Reload on:{$MaxMemoryReload}MB; Restart on:{$MaxMemory}MB\n";
	
	$ACTION_DONE=false;
	
	if($MaxMemory>0){
		if($memoryValueInt>0){
			
			if($memoryValueInt>$MaxMemoryReload){
				$StampTime=$unix->file_time_min($pidStampReload);
				if($StampTime>20){
					squid_admin_mysql(1, "Reverse proxy reach medium memory {$memoryValueInt}MB Reload:{$MaxMemoryReload}MB [action=reload]", "The service will be restarted");
					reload(true);
					@unlink($pidStampReload);
					@file_put_contents($pidStampReload, time());
					$ACTION_DONE=true;
				}
			}
			
			
			if(!$ACTION_DONE){
				if($memoryValueInt>$MaxMemory){
					squid_admin_mysql(0, "Reverse proxy reach max memory allowed {$memoryValueInt}MB MAX:{$MaxMemory}MB [action=restart]", "The service will be restarted");
					stop(true);
					start(true);
					@unlink($pidStampReload);
				}
			}
		}
	}
	
	
	add_memory_value($memoryValue);
	
}

function add_memory_value($memory){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("nginx_mem", "artica_events")){
		
		$sql="CREATE TABLE `nginx_mem`  (
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `xmemory` DEC(10,2) NOT NULL ,
			  UNIQUE KEY `zDate` (`zDate`),
				KEY `memory` (`xmemory`)
				) ENGINE=MYISAM;";
			 
		$q->QUERY_SQL($sql,"artica_events");
		
	}
	
	$q->QUERY_SQL("INSERT IGNORE INTO nginx_mem (`xmemory`) VALUES ('$memory')","artica_events");
	
}


?>