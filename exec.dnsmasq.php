<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS server DNSMasq";
$GLOBALS["CNAMES"]=array();
$GLOBALS["PROGRESS"]=false;
$GLOBALS["COMMANDLINE"]=@implode($argv, " ");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");

if($GLOBALS['VERBOSE']){echo "Parsing....{$argv[1]}\n";}
if($argv[1]=="--testresolv"){testsRESOLV();exit;}



if($argv[1]=="--varrun"){
	if($GLOBALS['VERBOSE']){echo "Running....{$argv[1]}\n";}
	varrun();
	exit;
}

if($argv[1]=="--restart-progress"){$GLOBALS["PROGRESS"]=true;restart();die();}
if($argv[1]=="--reload"){restart();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--build-hosts"){restart();die();}
if($argv[1]=="--install-service"){install_service($argv[2]);die();}
if($argv[1]=="--remove-service"){remove_service($argv[2]);die();}

build();



function reversed_name($ipaddr){
	$tr=explode(".", $ipaddr);
	krsort($tr);
	return @implode(".", $tr);
	
}

function cachednshosts_records($g){
	$unix=new unix();
	if(!is_array($g)){$g=array();}
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	$sock=new sockets();
	$IpClass=new IP();
	$EnableDNSMASQOCSDB=$sock->GET_INFO("EnableDNSMASQOCSDB");
	$EnableDHCPServer=intval($sock->GET_INFO("EnableDHCPServer"));
	$EnableDNSMASQLDAPDB=$sock->GET_INFO("EnableDNSMASQLDAPDB");
	if(!is_numeric($EnableDNSMASQOCSDB)){$EnableDNSMASQOCSDB=1;}
	if(!is_numeric($EnableDNSMASQLDAPDB)){$EnableDNSMASQLDAPDB=0;}
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	
	$C=0;
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Use OCS database $EnableDNSMASQOCSDB\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Use LDAP database $EnableDNSMASQLDAPDB\n";}
	$q=new mysql_squid_builder();
	$qSquid=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$GLOBALS["MYSQL_ERROR_DNSMASQ"]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL error, aborting\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}
		return false;
	}
	$CNAMES=array();
	$MAIN_MEM=array();
	
	@unlink("/etc/dnsmasq.hosts.cmdline");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=trim($ligne["ipaddr"]);
		$hostname=hostname_valid($ligne["hostname"]);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} host: $hostname/$ipaddr\n";}
		
		
		if($hostname==null){continue;}
		if(strpos($hostname, ".")>0){$build_hosts_array[$q->GetFamilySites($hostname)]=true;}
		if(strpos($hostname, ".")>0){$hostname_EXPLODED=explode(".",$hostname); $MAIN_MEM[$ipaddr][]=$hostname_EXPLODED[0]; }
		push_ptr($hostname,$ipaddr);
		
		

		$results2=$q->QUERY_SQL("SELECT hostname FROM dnsmasq_cname WHERE recordid={$ligne["ID"]}");
		$aliases=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if(trim(hostname_valid($ligne2["hostname"]))==null){continue;}
			$aliases[]=$ligne2["hostname"];
			$C++;
			$GLOBALS["CNAMES"][$ligne2["hostname"]]="--cname={$ligne2["hostname"]},$ipaddr";
			if($GLOBALS["VERBOSE"]){echo "$hostname -> {$ligne2["hostname"]}\n";}
		}
		
	}
	
	if($EnableDHCPServer==1){
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM dhcpd_fixed","artica_backup");
		if(!$q->ok){return;}
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["hostname"]=hostname_valid($ligne["hostname"]);
			if($ligne["hostname"]==null){continue;}
			
			
			$arecord=$ligne["ipaddr"];
			$hostname=$ligne["hostname"];
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.#", $hostname)){continue;}
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] dhcpd_fixed::$hostname/$arecord\n";}
			if(strpos($hostname, ".")>0){$build_hosts_array[$qSquid->GetFamilySites($hostname)]=true;}
			
			push_ptr($hostname,$arecord);
			if($GLOBALS["VERBOSE"]){echo "dhcpd_fixed:: $hostname -> $ipaddr\n";}		
		}
	}
	
	if($EnableArticaHotSpot==1){
		$HospotHTTPServerName=trim($sock->GET_INFO("HospotHTTPServerName"));
		if($HospotHTTPServerName<>null){
			
			$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
			$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
			$IPADDR=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, adding $HospotHTTPServerName/$IPADDR\n";}
			if(strpos($HospotHTTPServerName, ".")>0){$build_hosts_array[$qSquid->GetFamilySites($hostname)]=true;}
			push_ptr($HospotHTTPServerName,$IPADDR);
			$C++;
		}
		
	}
	
	
	
	if($EnableDNSMASQOCSDB==1){
	
		$sql="SELECT networks.IPADDRESS,hardware.name FROM networks,hardware WHERE 	networks.HARDWARE_ID=hardware.ID
			AND networks.IPADDRESS!='0.0.0.0' AND networks.IPADDRESS REGEXP '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"ocsweb");
		if($GLOBALS["VERBOSE"]){if(!$q->ok){echo $q->mysql_error."\n";}}
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: MYSQL -> ".mysql_num_rows($results)." entries\n";}
	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$arecord=$ligne["IPADDRESS"];
			$hostname=hostname_valid($ligne["name"]);
			if($hostname==null){continue;}
			
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.#", $hostname)){continue;}
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: $hostname/$arecord OCS MYSQL\n";}
			if(strpos($hostname, ".")>0){$build_hosts_array[$qSquid->GetFamilySites($hostname)]=true;}
			$C++;
			push_ptr($hostname,$arecord);
			if($GLOBALS["VERBOSE"]){echo "OCS:: $hostname -> $ipaddr\n";}
		}
	
	
	}	
	
	if($EnableDNSMASQLDAPDB==1){
		$ldap=new clladp();
		$filter_search="(&(objectClass=ArticaComputerInfos)(|(cn=*)(ComputerIP=*)(uid=*))(gecos=computer))";
		$ldap=new clladp();
		$attrs=array("uid","ComputerIP");
		$dn="$ldap->suffix";
		$hash=$ldap->Ldap_search($dn,$filter_search,$attrs);
			for($i=0;$i<$hash["count"];$i++){
				$arecord=$hash[$i][strtolower("ComputerIP")][0];
				$hostname=trim(strtolower($hash[$i]["uid"][0]));
				$hostname=hostname_valid($hostname);
				if($hostname==null){continue;}
				if($arecord=="127.0.0.1"){continue;}
				if($arecord=="0.0.0.0"){continue;}
				if($arecord==null){continue;}
				if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$hostname])){continue;}

				
				if(!$IpClass->isValid($arecord)){continue;}
				if(strpos($hostname, ".")>0){$build_hosts_array[$qSquid->GetFamilySites($hostname)]=true;}
				$reversed=reversed_name($arecord);
				$C++;
				push_ptr($hostname,$arecord);
				if($GLOBALS["VERBOSE"]){echo "LDAP:: $hostname -> $arecord\n";}
				
				}
		}
	
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ".count($GLOBALS["PTR_RECORDS"])." Records...\n";}
		
		while (list ($arecord, $hostnames) = each ($GLOBALS["PTR_RECORDS"]) ){
			
			if($arecord=="0.0.0.0"){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Invalid `$arecord/$hostnames`\n";}
				continue;
			}
			
			$netbiosname=hostname_valid($hostnames[0]);
			$FullyHostname=hostname_valid($hostnames[1]);
			if($netbiosname==null){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Invalid hostname_valid():`$arecord/{$hostnames[0]}`\n";}
				continue;
			}
			$hostname_text=@implode("/", $hostnames);
			
			$hostname=$hostnames[0];
			$hostname=str_replace(" ", "", $hostname);
			
			$reversed=reversed_name($arecord);
			$g[]="--address=/$hostname_text/$arecord";
			$g[]="--ptr-record=$reversed.in-addr.arpa.,$hostname";
		}
		
		while (list ($arecord, $cmdline) = each ($GLOBALS["CNAMES"]) ){
			$g[]=$cmdline;
		}
		

	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $C host(s)\n";}
	
	@file_put_contents("/etc/dnsmasq.hash.domains",serialize($build_hosts_array));
	$g=GetGoogleWebsitesList($g);
	
	if(count($g)>0){@file_put_contents("/etc/dnsmasq.hosts.cmdline",@implode(" ", $g));}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, return ". count($g)." host item(s)\n";}
	return $g;
}

function hostname_valid($hostname){
	$IpClass=new IP();
	if($IpClass->isValid($hostname)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},$hostname is an IP address!\n";}
		return null;}
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},$hostname is an preg_match IP address!\n";}
		return null;}
	$hostname=trim($hostname);
	$hostname=strtolower($hostname);
	$hostname=str_replace(' ', '', $hostname);
	$hostname=str_replace("$", "", $hostname);
	if(!preg_match("#^[a-z0-9\_\-\.]+$#", $hostname)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},$hostname is not preg_match hostname\n";}
		return null;}
	return $hostname;
}

function push_ptr($hostname,$ipaddr){
	
	if(strpos($hostname, ".")>0){
		$TR=explode(".",$hostname);
		$singlename=$TR[0];
		if(!isset($GLOBALS["DONE"][$singlename])){
			$GLOBALS["DONE"][$singlename]=true;
			$GLOBALS["PTR_RECORDS"][$ipaddr][]=$singlename;
		}
		if(!isset($GLOBALS["DONE"][$hostname])){
			$GLOBALS["DONE"][$hostname]=true;
			$GLOBALS["PTR_RECORDS"][$ipaddr][]=$hostname;
		}
		return;
	}
	
	if(!isset($GLOBALS["DONE"][$hostname])){
		$GLOBALS["DONE"][$hostname]=true;
		$GLOBALS["PTR_RECORDS"][$ipaddr][]=$hostname;
	}
	
	
	
}




function GetGoogleWebsitesList($g){
	$sock=new sockets();
	
	$DisableGoogleSSL=$sock->GET_INFO("DisableGoogleSSL");
	if(!is_numeric($DisableGoogleSSL)){$DisableGoogleSSL=0;}
	if($DisableGoogleSSL==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Goolge SSL is allowed\n";}
		return $g;
	}
	
	$q=new mysql_squid_builder();
	$arrayDN=$q->GetFamilySitestt(null,true);
	while (list ($table, $fff) = each ($arrayDN) ){
		if(preg_match("#\.(gov|gouv|gor|org|net|web|ac)\.#", "google.$table")){continue;}
		$array[]="www.google.$table";
		$array[]="google.$table";
	}
	
	$ipaddr=gethostbyname("nosslsearch.google.com");
	$ip=new IP();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$OK=true;
	if(!$ip->isIPv4($ipaddr)){$OK=false;}
	if(!$OK){if($ip->isIPv6($ipaddr)){$OK=true;}}
	if(!$OK){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Unable to resolve nosslsearch.google.com\n";}
		return $g;
	}
	
	while (list ($a, $googlesite) = each ($array) ){
		$g[]="--address=/$googlesite/$ipaddr";
		$g[]="--cname=$googlesite,nosslsearch.google.com";
	}
	
	return $g;

}



function GetSpecificDnsServers(){
	$sock=new sockets();
	$f=array();
	$ARTICA_ARRAY=unserialize(base64_decode($sock->GET_INFO("DNSMasqArticaParams")));
	if(count($ARTICA_ARRAY["RRDNS"])>0){
		while (list ($domain, $dns_server) = each ($ARTICA_ARRAY["RRDNS"]) ){
			if(intval($ARTICA_ARRAY["LOCALNET"][$domain])==0){continue;}
			$MYIPS["/$domain/$dns_server"]=true;
		}
	}	
		
	while (list ($final, $none) = each ($MYIPS) ){
		if(trim($final)==null){continue;}
		$f[]="--server={$final}";
	}

	if(count($f)==0){return;}
	return @implode("\n",$f);
}


function GetDNSSservers(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$ipClass=new IP();
	$sock=new sockets();
	$EnableDHCPServer=intval($sock->GET_INFO("EnableDHCPServer"));
	$f=array();
	
	$NET=$unix->NETWORK_ALL_INTERFACES(true);
	
	$UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
	if($UtDNSEnable==1){
		$UtDNSArticaUser=json_decode(base64_decode($sock->GET_INFO("UtDNSArticaUser")));
		if($UtDNSArticaUser->success){
			$MYIPS[$UtDNSArticaUser->prim]=true;
			$MYIPS[$UtDNSArticaUser->sec]=true;
		}
	}
	
	
	
	
	if($q->TABLE_EXISTS("dns_servers")){
		
		if(!$q->ok){
			$GLOBALS["MYSQL_ERROR_DNSMASQ"]=true;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL Error\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}
			return;
		}
		
		
		
		if($q->COUNT_ROWS("dns_servers")>0){
			$sql="SELECT * FROM dns_servers ORDER by zOrder";
			$results=$q->QUERY_SQL($sql);
			
			if(!$q->ok){
				$GLOBALS["MYSQL_ERROR_DNSMASQ"]=true;
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL Error\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}
				return;
			}
			
			while ($ligne = mysql_fetch_assoc($results)) {
				if(!$ipClass->isValid($ligne["dnsserver"])){continue;}
				if(isset($NET[$ligne["dnsserver"]])){continue;}
				$MYIPS[$ligne["dnsserver"]]=true;
				
			}
		}
	}
	
	if($EnableDHCPServer==1){
	$dhcp=new dhcpd(0,1);
	
	if($ipClass->isIPAddress($dhcp->DNS_1)){
			if(!isset($MYIPS[$dhcp->DNS_1])){
				if(!isset($NET[$dhcp->DNS_1])){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS 1:$dhcp->DNS_1\n";}
					$MYIPS[$dhcp->DNS_1]=true;
				}
				
			}
		}
		
		if($ipClass->isIPAddress($dhcp->DNS_2)){
			if(!isset($MYIPS[$dhcp->DNS_2])){
				if(!isset($NET[$dhcp->DNS_2])){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNS 2:$dhcp->DNS_2\n";}
					$MYIPS[$dhcp->DNS_2]=true;
				}
			}
		}	
	
	}
	

	if(count($f)==0){
		$resolv=new resolv_conf();
		if($ipClass->isValid($resolv->MainArray["DNS1"])){
			if(!isset($NET[$resolv->MainArray["DNS1"]])){
				if(!isset($MYIPS[$resolv->MainArray["DNS1"]])){$MYIPS[$resolv->MainArray["DNS1"]]=true;}
			}
		}
		if($ipClass->isValid($resolv->MainArray["DNS2"])){
			if(!isset($NET[$resolv->MainArray["DNS2"]])){
				if(!isset($MYIPS[$resolv->MainArray["DNS2"]])){$MYIPS[$resolv->MainArray["DNS2"]]=true;}
			}
		}
		if($ipClass->isValid($resolv->MainArray["DNS3"])){
			if(!isset($NET[$resolv->MainArray["DNS3"]])){
				if(!isset($MYIPS[$resolv->MainArray["DNS3"]])){$MYIPS[$resolv->MainArray["DNS3"]]=true; }
			}
		}				
		
	}
	
	
	$ARTICA_ARRAY=unserialize(base64_decode($sock->GET_INFO("DNSMasqArticaParams")));
	if(count($ARTICA_ARRAY["RRDNS"])>0){
		while (list ($domain, $dns_server) = each ($ARTICA_ARRAY["RRDNS"]) ){
			unset($MYIPS[$dns_server]);
		}
	}
	
	while (list ($final, $none) = each ($MYIPS) ){
		if(trim($final)==null){continue;}
		$f[]="--server={$final}";
	}
	
	
	if(count($f)>0){
		return "--no-resolv ".@implode(" ", $f);
	}
	return null;
	
}

function build_progress($text,$pourc){

	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/dnsmasq.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function start($aspid=false,$restart=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("dnsmasq");
	$ipClass=new IP();
	
	
	if(!is_file($Masterbin)){
		$unix->DEBIAN_INSTALL_PACKAGE("dnmasq");
		
	}

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, dnsmasq not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	if($restart){
		stop(true);
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return false;
	}
	$sock=new sockets();
	$EnableLocalDNSMASQ=$sock->GET_INFO('EnableLocalDNSMASQ');
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	$EnableLocalDNSMASQ=$sock->GET_INFO("EnableLocalDNSMASQ");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableDNSMASQ=$EnableDNSMASQ\n";}
	
	
	if($EnableLocalDNSMASQ==1){$EnableDNSMASQ=1;}
	$EnableDNSMASQ=$sock->dnsmasq_enabled();

	if($EnableDNSMASQ==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableLocalDNSMASQ ($EnableLocalDNSMASQ)\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableDNSMASQ ($EnableDNSMASQ)\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableDNSMASQ)\n";}
		return false;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Build.....\n";}
	build(true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Starting service\n";}
	
	if($restart){
		stop(true);
	}
	shell_exec("/etc/init.d/dnsmasq start");
	return true;
	
}



function fuser_port(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$PIDS=$unix->PIDOF_BY_PORT_UDP("53");
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens 53...\n";}

		return;}
		while (list ($pid, $b) = each ($PIDS) ){
			if($unix->process_exists($pid)){
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens 53 UDP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
				unix_system_kill_force($pid);
			}
		}
}

function ldap_domains(){
	$ldap=new clladp();
	$sock=new sockets();
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	$domains=$ldap->hash_get_all_domains();
	while (list ($num, $ligne) = each ($domains) ){
		$build_hosts_array[trim(strtolower($num))]=true;
	
	}	
	@file_put_contents("/etc/dnsmasq.hash.domains", serialize($build_hosts_array));
	if(isset($GLOBALS["BLACKLIST_DOMAINS"])){return $GLOBALS["BLACKLIST_DOMAINS"];}
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_blacklist";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}
		$GLOBALS["BLACKLIST_DOMAINS"]=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains-blacklist"));
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		$domain=$ligne["hostname"];
		$domain=trim(strtolower($domain));
		if($domain==null){continue;}
		$t[$domain]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} blacklisted domain: $domain\n";}
		$GLOBALS["BLACKLIST_DOMAINS"][$domain]=true;
	}
	
	$ARTICA_ARRAY=unserialize(base64_decode($sock->GET_INFO("DNSMasqArticaParams")));
	if(isset($ARTICA_ARRAY["LOCALNET"])){
		if(count($ARTICA_ARRAY["LOCALNET"])>0){
			while (list ($domain, $enabled) = each ($ARTICA_ARRAY["LOCALNET"]) ){
				$domain=trim(strtolower($domain));
				if($domain==null){continue;}
				if($enabled==1){continue;}
				$t[$domain]=true;
				$GLOBALS["BLACKLIST_DOMAINS"][$domain]=true;
				
			}
		}
	}
	@file_put_contents("/etc/dnsmasq.hash.domains-blacklist", serialize($t));
}

function isDomainValid($domain){
	$ipClass=new IP();
	if(!isset($GLOBALS["BLACKLIST_DOMAINS"])){
		ldap_domains();
	}
	
	$domain=trim(strtolower($domain));
	if($domain=="artica.fr"){return null;}
	$domain=str_replace("$", "", $domain);
	
	if(isset($GLOBALS["BLACKLIST_DOMAINS"][$domain])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} skip $domain\n";}
		return null;
	}
	
	if($ipClass->isIPAddress($domain)){return null;}
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+$#", $domain)){return null;}
	return $domain;
}


function getdomains(){
	$array=array();
	$sock=new sockets();
	$build_hosts_array=unserialize(@file_get_contents("/etc/dnsmasq.hash.domains"));
	
	$EnableDHCPServer=$sock->GET_INFO("EnableDHCPServer");
	if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
	if($EnableDHCPServer==1){
		$dhcp=new dhcpd();
		if($dhcp->ddns_domainname<>null){
			$array[$dhcp->ddns_domainname]=true;
		}
	}
	
	$array["localdomain"]=true;
	$array["local"]=true;
	$array["localhost"]=true;
	
	
	$ARTICA_ARRAY=unserialize(base64_decode($sock->GET_INFO("DNSMasqArticaParams")));
	while (list ($domain, $enabled) = each ($ARTICA_ARRAY["LOCALNET"]) ){
		if($enabled==0){continue;}
		$domain=isDomainValid($domain);
		if($domain==null){continue;}
		$array[$domain]=true;
	}
	
	reset($ARTICA_ARRAY);
	if(count($ARTICA_ARRAY["RRDNS"])>0){
		while (list ($domain, $dns_server) = each ($ARTICA_ARRAY["RRDNS"]) ){
			if($ARTICA_ARRAY["LOCALNET"][$domain]==0){continue;}
			unset($array[$domain]);
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} local domains from hosts:".count($build_hosts_array)."\n";}
	if(count($build_hosts_array)>0){
		while (list ($domain, $ligne) = each ($build_hosts_array) ){
			$domain=isDomainValid($domain);
			if($domain==null){continue;}
			if(preg_match("#\.local$#", $domain)){continue;}
			if(preg_match("#\.localdomain$#", $domain)){continue;}
			if(preg_match("#\.localhost$#", $domain)){continue;}
			$array[$domain]=true;
		}
	}
	
	$unix=new unix();
	$resolv=new resolv_conf();
	$myhostname=$unix->hostname_g();
	
	
	$tt=explode(".",$myhostname);
	unset($tt[0]);
	$domain=@implode(".", $tt);	

	if($domain<>null){$array[trim(strtolower($domain))]=true;}
	
	$cf=new dnsmasq();
	$LOCAL_DOMAIN=$cf->main_array["domain"];
	if($LOCAL_DOMAIN<>null){$array[$LOCAL_DOMAIN]=true;}
	
	if($resolv->MainArray["DOMAINS3"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS3"]))]=true;}
	if($resolv->MainArray["DOMAINS2"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS2"]))]=true;}
	if($resolv->MainArray["DOMAINS1"]<>null){$array[trim(strtolower($resolv->MainArray["DOMAINS1"]))]=true;}
	
	
		
	
	$G=array();
	while (list ($num, $ligne) = each ($array) ){
		if(isset($ff[$num])){continue;}
		if(is_numeric($num)){continue;}
		$num=isDomainValid($num);
		if($num==null){continue;}
		if($num==$LOCAL_DOMAIN){continue;}
		if(isset($ARTICA_ARRAY["RRDNS"][$num])){continue;}
		$ff[$num]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} answer to local domain: `$num`\n";}
		$G[]="--local=/$num/";
	}
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".count($G)." local domains\n";}
	
	if(count($G)>0){return @implode(" ", $G);}
}

function PID_NUM(){

	$unix=new unix();
	
	$pid=$unix->get_pid_from_file("/var/run/dnsmasq.pid");
	if($unix->process_exists($pid)){return $pid;}
	
	$Masterbin=$unix->find_program("dnsmasq");
	return $unix->PIDOF($Masterbin);

}

function restart() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	
	if($EnableDNSMASQ==1){
		build_progress("{checking_service}",20);
		InstallResolvConf();
		
	}else{
		build_progress("{checking_service}",20);
		RemoveResolvConf();
		stop(true);
		build_progress("{stopping_service} {success}",100);
		return;
	}
	
	
	build_progress("{starting_service}",50);
	if(!start(true,true)){
		build_progress("{starting_service} {failed} - report false",110);
		return;
	}
	build_progress("{starting_service} {restart}",100);

}
function build($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";

	if(!$aspid){
		if(!$GLOBALS["FORCE"]){
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){ writelogs("Already executed pid $pid, aborting...","MAIN",__FILE__,__LINE__); die(); }
			$time=$unix->file_time_min($pidtime);
			if($time<2){
				if($time>0){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Current {$time}Mn Requested 2mn, schedule this task\n";}
					writelogs("Current {$time}Mn Requested 2mn, schedule this task","MAIN",__FILE__,__LINE__);
					$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
				}
				die();
			}
		}
		
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		@file_put_contents($pidfile, getmypid());
		
		}



	$Masterbin=$unix->find_program("dnsmasq");
	$users=new settings_inc();
	if(!$users->dnsmasq_installed){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} is not installed, aborting\n";
		writelogs("DNSMasq is not installed, aborting","MAIN",__FILE__,__LINE__);
		return;
	}

	$sock=new sockets();
	$EnableDNSMASQ=$sock->dnsmasq_enabled();
	$EnableRemoteStatisticsAppliance=intval($sock->GET_INFO("EnableRemoteStatisticsAppliance"));
	$DNSMasqUseStatsAppliance=intval($sock->GET_INFO("DNSMasqUseStatsAppliance"));
	$EnableWebProxyStatsAppliance=intval($sock->GET_INFO("EnableWebProxyStatsAppliance"));
	$UnlockWebStats=intval($sock->GET_INFO("UnlockWebStats"));
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
	if($EnableDNSMASQ==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DnsMasq is disabled\n";}
		return;
	}


	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} patching local hosts..\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts");
	
	@file_put_contents("/etc/dnsmasq.conf.empty","");
	$GLOBALS["MYSQL_ERROR_DNSMASQ"]=false;
	$DNsServers=GetDNSSservers();
	
	if($GLOBALS["MYSQL_ERROR_DNSMASQ"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL error!\n";}
		build_progress("{error}",110);
		return;
	}
	
	$GetSpecificDnsServers=GetSpecificDnsServers();
	$getdomains=getdomains();
	
	$LocalDNSMASQItems=$sock->GET_INFO('LocalDNSMASQItems');
	if(!is_numeric($LocalDNSMASQItems)){$LocalDNSMASQItems=250000;}
	build_progress("{building_service}",20);
	$cf=new dnsmasq();
	$G=array();
	
	$G[]="--local-ttl=3600";
	$G[]="--conf-file=/etc/dnsmasq.conf.empty";
	$G[]="--pid-file=/var/run/dnsmasq.pid";
	$G[]="--strict-order";
	$G[]="--domain-needed";
	$G[]="--expand-hosts";
	$G[]="--bogus-priv";
	$G[]="--dns-forward-max={$cf->main_array["dns-forward-max"]}";
	if($GetSpecificDnsServers<>null){ $G[]=$GetSpecificDnsServers; }
	if($DNsServers<>null){ $G[]=$DNsServers; }
	if($getdomains<>null){ $G[]=$getdomains; }
	$G[]="--cache-size=$LocalDNSMASQItems";
	$G[]="--filterwin2k";
	$G[]="--log-facility=DAEMON";
	if($cf->main_array["log-queries"]=="yes"){$G[]="--log-queries"; }
	build_progress("{building_service}",30);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR ".count($G)." line(s) LINE:".__LINE__."\n";}
	$GLOBALS["MYSQL_ERROR_DNSMASQ"]=false;
	$G=cachednshosts_records($G);
	if($GLOBALS["MYSQL_ERROR_DNSMASQ"]){
		build_progress("{error}",110);
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR ".count($G)." line(s) LINE:".__LINE__."\n";}
	
	@mkdir("/var/run/dnsmasq",0755,true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CONFIGURATOR /etc/dnsmasq.cmdlines.array done\n";}
	
	
	
	@file_put_contents("/etc/dnsmasq.cmdlines.array", serialize($G));
	if(!install_service_main($G)){
		build_progress("{building_service} {failed}",110);
		return;
	}
	if($EnableWebProxyStatsAppliance==1){notify_remote_proxys_dnsmasq();}
	build_progress("{building_service}",30);
}

function UseStatsAppliance(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$sock=new sockets();
	$unix=new unix();
	$tempdir=$unix->TEMP_DIR();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	$unix=new unix();
	$hostname=$unix->hostname_g();	
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/dnsmasq.conf";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("$tempdir/dnsmasq.conf")){ufdbguard_admin_events("Failed to download dnsmasq.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"dns-compile");return;}		
	
	$mv=$unix->find_program("mv");
	$cp=unix-find_program("cp");
	$chmod=$unix->find_program("chmod");
	
	shell_exec("$mv $tempdir/dnsmasq.conf /etc/dnsmasq.conf");	
	shell_exec("cp /etc/dnsmasq.conf /etc/artica-postfix/settings/Daemons/DnsMasqConfigurationFile");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	
	if(is_file($dnsmasqbin)){
		$pid=$unix->PIDOF($dnsmasqbin);
		if(is_numeric($pid)){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading PID:`$pid`\n";
			$kill=$unix->find_program("kill");
			unix_system_HUP($pid);
		}
	}	
}

function testsRESOLV(){
	$resolv=new resolv_conf();
	echo $resolvConfBuild=$resolv->build()."\n";
	
}

function reload_dnsmasq(){
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	if($EnableDNSMASQ==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not enabled)\n";
		return ;
	}
	$unix=new unix();
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$chilli=$unix->find_program("chilli");
	
	
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if(is_file($chilli)){
		if($EnableChilli==1){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} HotSpot is enabled, cannot use this instance\n";
			return;
		}	
	}
	
	$dnsmasqbin=$unix->find_program("dnsmasq");
	if(is_file(!$dnsmasqbin)){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not such dsnmasq binary)\n";return;}
	$pid=$unix->PIDOF($dnsmasqbin);
	if(!is_numeric($pid)){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unable to reload DnsMASQ (not running)\n";return;}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading PID:`$pid`\n";
	$kill=$unix->find_program("kill");
	unix_system_HUP($pid);
}

function varrun(){
	if(!is_file("/var/run/dnsmasq/resolv.conf")){
		echo "Starting......: ".date("H:i:s")." /var/run/dnsmasq/resolv.conf no such file\n";
		ResolvConfChecks();
		return;
	}
	$f=explode("\n",@file_get_contents("/var/run/dnsmasq/resolv.conf"));
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	if(!$configured){
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: ".date("H:i:s")." /var/run/dnsmasq/resolv.conf not configured, write it...\n";
		@file_put_contents("/var/run/dnsmasq/resolv.conf", $resolvConfBuild);
		shell_exec("/etc/init.d/dnsmasq reload");
	}
	echo "Starting......: ".date("H:i:s")." ResolvConfChecks()\n";
	ResolvConfChecks();
}

function InstallResolvConf(){
	
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	while (list ($index, $line) = each ($f) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^nameserver.+#",$line, $re)){continue;}
		$X[]=$line;
	}
	
	$X[]="nameserver\t127.0.0.1";
	
	@file_put_contents("/etc/resolv.conf", @implode("\n", $X));
	
}

function RemoveResolvConf(){
	$resolv=new resolv_conf();
	$resolvConfBuild=$resolv->build();
	echo "Starting......: ".date("H:i:s")." /etc/resolv.conf install new one...\n";
	@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
	
}


function ResolvConfChecks(){
	$unix=new unix();
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}	
	$f=file("/etc/resolv.conf");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	
	if($configured){return;}
		
	if(file_exists($dnsmasqbin)){
		if($EnableDNSMASQ==0){
			$resolv=new resolv_conf();
			$resolvConfBuild=$resolv->build();
			echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
		}
		if($EnableDNSMASQ==1){
			reset($f);
			$f[]="nameserver 127.0.0.1";
			echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);			
			reload_dnsmasq();
		}
	}else{
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: ".date("H:i:s")." /etc/resolv.conf not configured, write it...\n";
		@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
	}
	
	
}


function notify_remote_proxys_dnsmasq(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM squidservers";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		writelogs("remote server $server:$port",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="DNSMASQ";
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error for DNSMASQ");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration for DNSMASQ");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data for DNSMASQ");
	}
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function remove_service($eth){
	$INITD_PATH="/etc/init.d/dnsmasq-$eth";
	$INIT=basename($INITD_PATH);
	shell_exec("$INITD_PATH stop");
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f $INIT remove >/dev/null 2>&1");
		@unlink($INITD_PATH);
	}
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del $INIT >/dev/null 2>&1");
		@unlink($INITD_PATH);

	}	
	
}
function install_service_main($G){
	
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsmasq";
	$php5script=basename(__FILE__);
	$daemonbinLog="DNSMasq for main";
	$daemon_path=$unix->find_program("dnsmasq");
	
	$EnableDNSMASQ=$sock->dnsmasq_enabled();
	$cmdline=@implode(" ", $G);
	
	
	if(count($G)<2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed NO Tokens, Build config...\n";}
		return;
	
	}
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:       dnsmasq";
	$f[]="# Required-Start: \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop:  \$network \$remote_fs \$syslog";
	$f[]="# Default-Start:  2 3 4 5";
	$f[]="# Default-Stop:   0 1 6";
	$f[]="# Description:    DHCP and DNS server";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="set +e   # Don't exit on error status";
	$f[]="";
	$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="DAEMON=$daemon_path";
	$f[]="NAME=dnsmasq";
	$f[]="DESC=\"DNS forwarder and DHCP server\"";
	$f[]="";
	$f[]="# Most configuration options in /etc/default/dnsmasq are deprecated";
	$f[]="# but still honoured.";
	$f[]="ENABLED=$EnableDNSMASQ";
	$f[]="if [ -r /etc/default/\$NAME ]; then";
	$f[]="	. /etc/default/\$NAME";
	$f[]="fi";
	$f[]="";
	$f[]="# Get the system locale, so that messages are in the correct language, and the ";
	$f[]="# charset for IDN is correct";
	$f[]="if [ -r /etc/default/locale ]; then";
	$f[]="        . /etc/default/locale";
	$f[]="        export LANG";
	$f[]="fi";
	$f[]="";
	$f[]="test -x \$DAEMON || exit 0";
	$f[]="";
	$f[]="# Provide skeleton LSB log functions for backports which don't have LSB functions.";
	$f[]="if [ -f /lib/lsb/init-functions ]; then";
	$f[]="         . /lib/lsb/init-functions";
	$f[]="else";
	$f[]="         log_warning_msg () {";
	$f[]="            echo \"\${@}.\"";
	$f[]="         }";
	$f[]="";
	$f[]="         log_success_msg () {";
	$f[]="            echo \"\${@}.\"";
	$f[]="         }";
	$f[]="";
	$f[]="         log_daemon_msg () {";
	$f[]="            echo -n \"\${1}: \$2\"";
	$f[]="         }";
	$f[]="";
	$f[]="	 log_end_msg () {";
	$f[]="            if [ \$1 -eq 0 ]; then";
	$f[]="              echo \".\"";
	$f[]="            elif [ \$1 -eq 255 ]; then";
	$f[]="              /bin/echo -e \" (warning).\"";
	$f[]="            else";
	$f[]="              /bin/echo -e \" failed!\"";
	$f[]="            fi";
	$f[]="         }";
	$f[]="fi";
	$f[]="";
	$f[]="# RESOLV_CONF:";
	$f[]="# If the resolvconf package is installed then use the resolv conf file";
	$f[]="# that it provides as the default.  Otherwise use /etc/resolv.conf as";
	$f[]="# the default.";
	$f[]="#";
	$f[]="# If IGNORE_RESOLVCONF is set in /etc/default/dnsmasq or an explicit";
	$f[]="# filename is set there then this inhibits the use of the resolvconf-provided";
	$f[]="# information.";
	$f[]="#";
	$f[]="# Note that if the resolvconf package is installed it is not possible to ";
	$f[]="# override it just by configuration in /etc/dnsmasq.conf, it is necessary";
	$f[]="# to set IGNORE_RESOLVCONF=yes in /etc/default/dnsmasq.";
	$f[]="";
	$f[]="if [ ! \"\$RESOLV_CONF\" ] &&";
	$f[]="   [ \"\$IGNORE_RESOLVCONF\" != \"yes\" ] &&";
	$f[]="   [ -x /sbin/resolvconf ]";
	$f[]="then";
	$f[]="	RESOLV_CONF=/var/run/dnsmasq/resolv.conf";
	$f[]="fi";
	$f[]="";
	$f[]="for INTERFACE in \$DNSMASQ_INTERFACE; do";
	$f[]="	DNSMASQ_INTERFACES=\"\$DNSMASQ_INTERFACES -i \$INTERFACE\"";
	$f[]="done";
	$f[]="";
	$f[]="for INTERFACE in \$DNSMASQ_EXCEPT; do";
	$f[]="	DNSMASQ_INTERFACES=\"\$DNSMASQ_INTERFACES -I \$INTERFACE\"";
	$f[]="done";
	$f[]="";
	$f[]="if [ ! \"\$DNSMASQ_USER\" ]; then";
	$f[]="   DNSMASQ_USER=\"root\"";
	$f[]="fi";
	$f[]="";
	$f[]="start()";
	$f[]="{";
	$f[]="        # Return";
	$f[]="	#   0 if daemon has been started";
	$f[]="	#   1 if daemon was already running";
	$f[]="	#   2 if daemon could not be started";
	$f[]="";
	$f[]="        # /var/run may be volatile, so we need to ensure that";
	$f[]="        # /var/run/dnsmasq exists here as well as in postinst";
	$f[]="        if [ ! -d /var/run/dnsmasq ]; then";
	$f[]="           mkdir /var/run/dnsmasq || return 2";
	$f[]="        fi";
	$f[]="";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON --test > /dev/null || return 1";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON -- $cmdline || return 2 ";
	$f[]="}";
	$f[]="";
	$f[]="start_resolvconf()";
	$f[]="{";
	$f[]="# If interface \"lo\" is explicitly disabled in /etc/default/dnsmasq";
	$f[]="# Then dnsmasq won't be providing local DNS, so don't add it to";
	$f[]="# the resolvconf server set.";
	$f[]="	for interface in \$DNSMASQ_EXCEPT";
	$f[]="	do";
	$f[]="		[ \$interface = lo ] && return";
	$f[]="	done";
	$f[]="";
	$f[]="        if [ -x /sbin/resolvconf ] ; then";
	$f[]="		echo \"nameserver 127.0.0.1\" | /sbin/resolvconf -a lo.\$NAME";
	$f[]="	fi";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="stop()";
	$f[]="{";
	$f[]="	# Return";
	$f[]="	#   0 if daemon has been stopped";
	$f[]="	#   1 if daemon was already stopped";
	$f[]="	#   2 if daemon could not be stopped";
	$f[]="	#   other if a failure occurred";
	$f[]="	start-stop-daemon --stop --quiet --retry=TERM/30/KILL/5 --pidfile /var/run/dnsmasq/\$NAME.pid --name \$NAME";
	$f[]="	RETVAL=\"\$?\"";
	$f[]="	[ \"\$RETVAL\" = 2 ] && return 2";
	$f[]="	return \"\$RETVAL\"";
	$f[]="}";
	$f[]="";
	$f[]="stop_resolvconf()";
	$f[]="{";
	$f[]="	if [ -x /sbin/resolvconf ] ; then";
	$f[]="		/sbin/resolvconf -d lo.\$NAME";
	$f[]="	fi";
	$f[]="	return 0";
	$f[]="}";
	$f[]="";
	$f[]="status()";
	$f[]="{";
	$f[]="	# Return";
	$f[]="	#   0 if daemon is running";
	$f[]="	#   1 if daemon is dead and pid file exists";
	$f[]="	#   3 if daemon is not running";
	$f[]="	#   4 if daemon status is unknown";
	$f[]="	start-stop-daemon --start --quiet --pidfile /var/run/dnsmasq/\$NAME.pid --exec \$DAEMON --test > /dev/null";
	$f[]="	case \"\$?\" in";
	$f[]="		0) [ -e \"/var/run/dnsmasq/\$NAME.pid\" ] && return 1 ; return 3 ;;";
	$f[]="		1) return 0 ;;";
	$f[]="		*) return 4 ;;";
	$f[]="	esac";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	test \"\$ENABLED\" != \"0\" || exit 0";
	$f[]="	log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";
	$f[]="	start";
	$f[]="	case \"\$?\" in";
	$f[]="		0)";
	$f[]="			log_end_msg 0";
	$f[]="			start_resolvconf";
	$f[]="			exit 0";
	$f[]="			;;";
	$f[]="		1)";
	$f[]="			log_success_msg \"(already running)\"";
	$f[]="			exit 0";
	$f[]="			;;";
	$f[]="		*)";
	$f[]="			log_end_msg 1";
	$f[]="			exit 1";
	$f[]="			;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="  $php ".__FILE__." --stop || true";
	$f[]="	;;";
	$f[]="  restart|force-reload)";
	$f[]="	test \"\$ENABLED\" != \"0\" || exit 1";
	$f[]="  $php ".__FILE__." --stop || true";
	$f[]="  $php ".__FILE__." --start || true";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	log_daemon_msg \"Checking \$DESC\" \"\$NAME\"";
	$f[]="	status";
	$f[]="	case \"\$?\" in";
	$f[]="		0) log_success_msg \"(running)\" ; exit 0 ;;";
	$f[]="		1) log_success_msg \"(dead, pid file exists)\" ; exit 1 ;;";
	$f[]="		3) log_success_msg \"(not running)\" ; exit 3 ;;";
	$f[]="		*) log_success_msg \"(unknown)\" ; exit 4 ;;";
	$f[]="	esac";
	$f[]="	;;";
	$f[]="  dump-stats)";
	$f[]="        kill -s USR1 `cat /var/run/dnsmasq/\$NAME.pid`";
	$f[]="	;;";
	$f[]="  systemd-start-resolvconf)";
	$f[]="	start_resolvconf";
	$f[]="	;;";
	$f[]="  systemd-stop-resolvconf)";
	$f[]="	stop_resolvconf";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	echo \"Usage: /etc/init.d/\$NAME {start|stop|restart|force-reload|dump-stats|status}\" >&2";
	$f[]="	exit 3";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	$f[]="";
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	return true;
	
}





function install_service($eth){
	

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsmasq-$eth";
	$php5script=basename(__FILE__);
	$daemonbinLog="DNSMasq for $eth";
	$daemon_path=$unix->find_program("dnsmasq");
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsmasq-$eth";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="DAEMON=$daemon_path";
	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart-eth $eth --script \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
	
}


