<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["WITHOUT_RESTART"]=false;
$GLOBALS["CMDLINES"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--no-restart#",implode(" ",$argv))){$GLOBALS["WITHOUT_RESTART"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.tasks.inc');
include_once(dirname(__FILE__).'/ressources/class.process.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--dump"){dump();die();}
if($argv[1]=="--run"){run();exit;}
if($argv[1]=="--syslog"){checksyslog();exit;}


function run(){
	
	
	$ReplaceEntry=null;
	$sock=new sockets();
	$DisableGoogleSSL=intval($sock->GET_INFO("DisableGoogleSSL"));
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	
	echo "Starting......: ".date("H:i:s")." Squid : EnableGoogleSafeSearch = '$EnableGoogleSafeSearch'\n";
	
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	
	echo "Starting......: ".date("H:i:s")." Squid : DisableGoogleSSL = $DisableGoogleSSL\n";
	echo "Starting......: ".date("H:i:s")." Squid : EnableGoogleSafeSearch = $EnableGoogleSafeSearch\n";
	
	if($DisableGoogleSSL==0){
		if($EnableGoogleSafeSearch==0){
			echo "Starting......: ".date("H:i:s")." Squid : change google.com DNS (disabled)\n";
			remove();
			build_progress("{disabled}",100);
			return;
		}
		
	}
	
	if($DisableGoogleSSL==1){
		$ReplaceEntry="nosslsearch.google.com";
	}
	
	if($EnableGoogleSafeSearch==1){
		$ReplaceEntry="forcesafesearch.google.com";
	}
	
	if($ReplaceEntry==null){
		remove();
		build_progress("{disabled}",100);
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." Squid : $ReplaceEntry (enabled)\n";
	build_progress("{enabled}",5);
	addDNSGOOGLE($ReplaceEntry);
	
}

function GetWebsitesList(){
	$f[]="google.com";
	$f[]="google.ad";
	$f[]="google.ae";
	$f[]="google.com.af";
	$f[]="google.com.ag";
	$f[]="google.com.ai";
	$f[]="google.al";
	$f[]="google.am";
	$f[]="google.co.ao";
	$f[]="google.com.ar";
	$f[]="google.as";
	$f[]="google.at";
	$f[]="google.com.au";
	$f[]="google.az";
	$f[]="google.ba";
	$f[]="google.com.bd";
	$f[]="google.be";
	$f[]="google.bf";
	$f[]="google.bg";
	$f[]="google.com.bh";
	$f[]="google.bi";
	$f[]="google.bj";
	$f[]="google.com.bn";
	$f[]="google.com.bo";
	$f[]="google.com.br";
	$f[]="google.bs";
	$f[]="google.bt";
	$f[]="google.co.bw";
	$f[]="google.by";
	$f[]="google.com.bz";
	$f[]="google.ca";
	$f[]="google.cd";
	$f[]="google.cf";
	$f[]="google.cg";
	$f[]="google.ch";
	$f[]="google.ci";
	$f[]="google.co.ck";
	$f[]="google.cl";
	$f[]="google.cm";
	$f[]="google.cn";
	$f[]="google.com.co";
	$f[]="google.co.cr";
	$f[]="google.com.cu";
	$f[]="google.cv";
	$f[]="google.com.cy";
	$f[]="google.cz";
	$f[]="google.de";
	$f[]="google.dj";
	$f[]="google.dk";
	$f[]="google.dm";
	$f[]="google.com.do";
	$f[]="google.dz";
	$f[]="google.com.ec";
	$f[]="google.ee";
	$f[]="google.com.eg";
	$f[]="google.es";
	$f[]="google.com.et";
	$f[]="google.fi";
	$f[]="google.com.fj";
	$f[]="google.fm";
	$f[]="google.fr";
	$f[]="google.ga";
	$f[]="google.ge";
	$f[]="google.gg";
	$f[]="google.com.gh";
	$f[]="google.com.gi";
	$f[]="google.gl";
	$f[]="google.gm";
	$f[]="google.gp";
	$f[]="google.gr";
	$f[]="google.com.gt";
	$f[]="google.gy";
	$f[]="google.com.hk";
	$f[]="google.hn";
	$f[]="google.hr";
	$f[]="google.ht";
	$f[]="google.hu";
	$f[]="google.co.id";
	$f[]="google.ie";
	$f[]="google.co.il";
	$f[]="google.im";
	$f[]="google.co.in";
	$f[]="google.iq";
	$f[]="google.is";
	$f[]="google.it";
	$f[]="google.je";
	$f[]="google.com.jm";
	$f[]="google.jo";
	$f[]="google.co.jp";
	$f[]="google.co.ke";
	$f[]="google.com.kh";
	$f[]="google.ki";
	$f[]="google.kg";
	$f[]="google.co.kr";
	$f[]="google.com.kw";
	$f[]="google.kz";
	$f[]="google.la";
	$f[]="google.com.lb";
	$f[]="google.li";
	$f[]="google.lk";
	$f[]="google.co.ls";
	$f[]="google.lt";
	$f[]="google.lu";
	$f[]="google.lv";
	$f[]="google.com.ly";
	$f[]="google.co.ma";
	$f[]="google.md";
	$f[]="google.me";
	$f[]="google.mg";
	$f[]="google.mk";
	$f[]="google.ml";
	$f[]="google.com.mm";
	$f[]="google.mn";
	$f[]="google.ms";
	$f[]="google.com.mt";
	$f[]="google.mu";
	$f[]="google.mv";
	$f[]="google.mw";
	$f[]="google.com.mx";
	$f[]="google.com.my";
	$f[]="google.co.mz";
	$f[]="google.com.na";
	$f[]="google.com.nf";
	$f[]="google.com.ng";
	$f[]="google.com.ni";
	$f[]="google.ne";
	$f[]="google.nl";
	$f[]="google.no";
	$f[]="google.com.np";
	$f[]="google.nr";
	$f[]="google.nu";
	$f[]="google.co.nz";
	$f[]="google.com.om";
	$f[]="google.com.pa";
	$f[]="google.com.pe";
	$f[]="google.com.pg";
	$f[]="google.com.ph";
	$f[]="google.com.pk";
	$f[]="google.pl";
	$f[]="google.pn";
	$f[]="google.com.pr";
	$f[]="google.ps";
	$f[]="google.pt";
	$f[]="google.com.py";
	$f[]="google.com.qa";
	$f[]="google.ro";
	$f[]="google.ru";
	$f[]="google.rw";
	$f[]="google.com.sa";
	$f[]="google.com.sb";
	$f[]="google.sc";
	$f[]="google.se";
	$f[]="google.com.sg";
	$f[]="google.sh";
	$f[]="google.si";
	$f[]="google.sk";
	$f[]="google.com.sl";
	$f[]="google.sn";
	$f[]="google.so";
	$f[]="google.sm";
	$f[]="google.sr";
	$f[]="google.st";
	$f[]="google.com.sv";
	$f[]="google.td";
	$f[]="google.tg";
	$f[]="google.co.th";
	$f[]="google.com.tj";
	$f[]="google.tk";
	$f[]="google.tl";
	$f[]="google.tm";
	$f[]="google.tn";
	$f[]="google.to";
	$f[]="google.com.tr";
	$f[]="google.tt";
	$f[]="google.com.tw";
	$f[]="google.co.tz";
	$f[]="google.com.ua";
	$f[]="google.co.ug";
	$f[]="google.co.uk";
	$f[]="google.com.uy";
	$f[]="google.co.uz";
	$f[]="google.com.vc";
	$f[]="google.co.ve";
	$f[]="google.vg";
	$f[]="google.co.vi";
	$f[]="google.com.vn";
	$f[]="google.vu";
	$f[]="google.ws";
	$f[]="google.rs";
	$f[]="google.co.za";
	$f[]="google.co.zm";
	$f[]="google.co.zw";
	$f[]="google.cat";
	return $f;
	
}

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.google.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if(!isset($GLOBALS["PROGRESS"])){$GLOBALS["PROGRESS"]=false;}
	if($GLOBALS["PROGRESS"]){sleep(1);}

}




function addDNSGOOGLE($addrName="nosslsearch.google.com"){
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $addrName -> ?\n";}
	$ipaddr=gethostbyname($addrName);
	$ip=new IP();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){
		if($ip->isIPv6($ipaddr)){$OK=true;}
	}
	if(!$OK){
		echo "Starting......: ".date("H:i:s")." Squid : failed, nosslsearch.google.com `$ipaddr` not an IP address...!!!\n";
		build_progress("$addrName {failed}",110);
		return;
	}	
	$q=new mysql();
	
	build_progress("$addrName {checking}",5);
	$entry=null;
	$results=$q->QUERY_SQL("SELECT ipaddr FROM net_hosts WHERE `hostname` = 'www.google.com'","artica_backup");
	if(mysql_num_rows($results)==1){
		while ($ligne = mysql_fetch_assoc($results)) {
			$entry=$ligne["ipaddr"];
		}
	}
	if($entry==null){
		$results=$q->QUERY_SQL("SELECT ipaddr FROM net_hosts WHERE `hostname` = 'google.com'","artica_backup");
		if(mysql_num_rows($results)==1){
			while ($ligne = mysql_fetch_assoc($results)) {
				$entry=$ligne["ipaddr"];
			}
		}		
		
	}
	
	echo "Starting......: ".date("H:i:s")." Squid : Resolved $ipaddr in DB: $entry\n";
	if($entry==$ipaddr){
		echo "Starting......: ".date("H:i:s")." Squid : $addrName no changes...\n";
		if($GLOBALS["OUTPUT"]){
			build_progress("$addrName {no_changes}",50);
			sleep(3);
			build_progress("Patching host file",95);
			shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
			reload_pdns();
			sleep(5);
			build_progress("{success}",100);
			return;
		}
		
		reload_pdns();
		return; 
	}
	
	if($entry<>null){
		echo "Starting......: ".date("H:i:s")." Squid : $addrName [$entry]...\n";
	}
	build_progress("$addrName [$entry]",5);
	
	
	$array=GetWebsitesList();
	
	$max=count($array);
	$c=0;
	while (list ($table, $fff) = each ($array) ){
		$c++;
		$prc=$c/$max;
		$prc=$prc*100;
		if($prc>5){
			if($prc<90){
				build_progress("$fff [$ipaddr]",$prc);
			}
		}
		$md5=md5("$ipaddr$fff");
		$f[]="('$md5','$ipaddr','$fff')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google\.%'" ,"artica_backup");
		$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`) VALUES ".@implode(",\n", $f),"artica_backup");
		if(!$q->ok){
			build_progress("Table net_hosts failed",110);
			return;
		}
		
		build_progress("Patching host file",95);
		echo "Starting......: ".date("H:i:s")." Squid : adding ".count($f)." google servers [$ipaddr] from /etc/hosts\n";
		shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
		build_progress("Reloading proxy service",95);
		shell_exec("$php5 /etc/init.d/squid reload --script=".basename(__FILE__));
		shell_exec("/etc/init.d/dnsmasq restart");
		reload_pdns();
		sleep(5);
		build_progress("{success}",100);
	}		
}

function dump(){
	$ipaddr=gethostbyname("nosslsearch.google.com");
	$ip=new IP();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){
		if($ip->isIPv6($ipaddr)){$OK=true;}
	}
	if(!$OK){echo "Failed nosslsearch.google.com `$ipaddr` not an IP address...!!!\n";return;}
	
	
	
	$array=GetWebsitesList();
	if(count($array)==0){
		echo "Failed!!! -> GetWebsitesList();\n";return;
	}
	
	while (list ($table, $fff) = each ($array) ){
		echo "$fff\t$ipaddr\n";
	}	

}

function reload_pdns(){
	$unix=new unix();
	$pdns_server=$unix->find_program("pdns_server");
	if(!is_file($pdns_server)){return;}
	$kill=$unix->find_program("kill");
	$pid_path="/var/run/pdns/pdns.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Squid : reloading PowerDNS PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}
	
	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));	
	if($unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Squid : reloading PowerDNS Recursor PID $master_pid\n";
		shell_exec("$kill -HUP $master_pid >/dev/null 2>&1");
	}	
	
	
}

function remove(){
	$unix=new unix();
	$newf=array();
	$add=0;
	$f=explode("\n",@file_get_contents("/etc/hosts"));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#google\.#", $line)){$add++;continue;}
		$newf[]=$line;
		
	}
	if($add>0){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google%'" ,"artica_backup");
		@file_put_contents("/etc/hosts", @implode("\n", $newf));
		echo "Starting......: ".date("H:i:s")." Squid : removing $add google servers from /etc/hosts\n";
		shell_exec("/etc/init.d/dnsmasq restart");
		reload_pdns();
	}
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM net_hosts WHERE `hostname` LIKE '%google%'" ,"artica_backup");
	
}

function remove_entry($val){

	
	
}

function checksyslog(){
	$unix=new unix();
	$syslogpath=$unix->LOCATE_SYSLOG_PATH();
	$size=@filesize($syslogpath);
	echo "Size:$size\n";
	if($size==0){
		$unix->RESTART_SYSLOG(true);
	}
}

