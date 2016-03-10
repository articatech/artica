<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["SERVICE_NAME"]="IDS service";
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	
if($argv[1]=="--purge"){purge();exit;}
if($argv[1]=="--delete"){DeleteRules();exit;}
if($argv[1]=="--run"){xrun();exit;}
if($argv[1]=="--build"){xstart();exit;}	

function DeleteRules(){
	$d=0;

	$iptables_save=find_program("iptables-save");
	exec("$iptables_save > /etc/artica-postfix/iptables-suricata.conf");

	$data=file_get_contents("/etc/artica-postfix/iptables-suricata.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaSuricata#";
	$conf=null;
	$iptables_restore=find_program("iptables-restore");
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){
			echo "Remove $ligne\n";
			$d++;continue;}

			$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables-suricata.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables-suricata.new.conf");


}


function xrun(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	if($unix->process_exists($pid,basename(__FILE__))){echo "PID: $pid Already exists....\n";die();}
	@file_put_contents($pidfile, getmypid());
	DeleteRules();
	xstart(true);
	shell_exec("/bin/suricata-fw.sh");
}

function purge(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.suricata-fw.php.purge.pid";
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	if($unix->process_exists($pid,basename(__FILE__))){echo "PID: $pid Already exists....\n";die();}
	@file_put_contents($pidfile, getmypid());
	
	$pidExec=$unix->file_time_min($pidTime);
	if($pidExec<15){return;}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$sock=new sockets();
	$SuricataFirewallPurges=intval($sock->GET_INFO("SuricataFirewallPurges"));
	if($SuricataFirewallPurges==0){$SuricataFirewallPurges=24;}
	
	$q=new postgres_sql();
	$sql="SELECT COUNT(*) as tcount FROM suricata_firewall";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	$CountOfRules=intval($ligne["tcount"]);
	if($CountOfRules==0){
		echo "No rules...\n";
		return;}
	
	$time = strtotime("-$SuricataFirewallPurges hour");	
	$date=date("Y-m-d H:i:s",$time);
	echo "Remove rules before $date\n";		
		
	$sql="DELETE FROM suricata_firewall WHERE zdate < '$date' ";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		system_admin_mysql(0, "Purging MySQL error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	
	$sql="SELECT COUNT(*) as tcount FROM suricata_firewall";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	$CountOfRules2=intval($ligne["tcount"]);
	
	$removed=$CountOfRules-$CountOfRules2;
	if($removed==0){return;}
	
	system_admin_mysql(1, "Purging $removed IDS rules ( added before $date ) from firewall", null,__FILE__,__LINE__);
	xstart(true);
	shell_exec("/bin/suricata-fw.sh");
}


	
function xstart($aspid=false){
	
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/exec.suricata-fw.php.xstart.time";
	
	if(system_is_overloaded()){die();}
	
	$unix=new unix();
	if(!$aspid){
		$pid=@file_get_contents($pidfile);
		if($pid<100){$pid=null;}
		if($unix->process_exists($pid,basename(__FILE__))){echo "PID: $pid Already exists....\n";die();}
		@file_put_contents($pidfile, getmypid());
		
		$pidExec=$unix->file_time_min($pidtime);
		if($pidExec<15){return;}
		
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		
	}
	
	
	
	$q=new postgres_sql();
	$php=$unix->LOCATE_PHP5_BIN();
	$sql="SELECT * FROM suricata_firewall";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	
	$f[]="#!/bin/sh";
	$f[]="$php ".__FILE__." --delete";
	$f[]="";
	
	if($EnableSuricata==0){
		@file_put_contents("/bin/suricata-fw.sh", @implode("\n", $f)."\n");
		@chmod("/bin/suricata-fw.sh",0755);
		return;
		
	}
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(pg_num_rows($results)==0){
		@file_put_contents("/bin/suricata-fw.sh", @implode("\n", $f)."\n");
		@chmod("/bin/suricata-fw.sh",0755);
		return;
	}
	$suffixTables="-m comment --comment \"ArticaSuricata\"";
	$iptables=$unix->find_program("iptables");
	while ($ligne = pg_fetch_assoc($results)) {
		$proto=strtolower($ligne["proto"]);
		$dest_port_text=null;
		$dest_port=$ligne["dst_port"];
		$src_ip=$ligne["src_ip"];
		if($dest_port>0){$dest_port_text=" --dport $dest_port";}
		$cmdline="$iptables -I INPUT -p $proto -m $proto -s $src_ip{$dest_port_text}  -j DROP $suffixTables || true";
		$f[]="$cmdline";
		
		
	}
	
	
	@file_put_contents("/bin/suricata-fw.sh", @implode("\n", $f)."\n");
	@chmod("/bin/suricata-fw.sh",0755);
	
}