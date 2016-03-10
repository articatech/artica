<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="--register"){register();exit;}
if($argv[1]=="--unlink"){exunlink();exit;}
if($argv[1]=="--dump"){dump();exit;}


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.failover.php.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.failover.php.progress",0755);

}

function dump(){
	$sock=new sockets();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	while (list ($num, $val) = each ($MAIN) ){
		echo "$num............: $val\n";
		
	}
	
}

function register(){
	$sock=new sockets();
	$unix=new unix();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	
	if(preg_match("#^([0-9\.]+)$#", $MAIN["SLAVE"])){
		$ip=new IP();
		if(!$ip->isValid($MAIN["SLAVE"])){
			build_progress("{connecting_to} {$MAIN["SLAVE"]} {invalid}",110);
			echo "{$MAIN["SLAVE"]} is an invalid IP address\n";
			return;
		}
	}
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp=$SEND_SETTING";
	
	echo "Communicate with {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} with interface $WgetBindIpAddress\n";
	echo "Send $proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?...\n";
	
	build_progress("{connecting_to} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}",15);
	
	$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=10;
	if(!$curl->get()){
		echo "$curl->error\n";
		debug_curl($curl->CURL_ALL_INFOS);
		build_progress("{connecting_to} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed} $WgetBindIpAddress",17);
		$curl=new ccurl($uri,true,"none",true);
		$curl->NoHTTP_POST=true;
		$curl->Timeout=10;
		if(!$curl->get()){
			echo "$curl->error\n";
			debug_curl($curl->CURL_ALL_INFOS);
			build_progress("{connecting_to} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed} NONE",110);
			return;
		}else{
			$sock->SET_INFO("WgetBindIpAddress", null);
			$WgetBindIpAddress=null;
		}
		
	}
	
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo "Please verify that both servers must have the same Artica version\n";
		build_progress("{connecting_to} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {protocol_error}",110);
		return;
	}	
	
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo "{$array["ERROR_SHOW"]}\n";
		build_progress("{connecting_to} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
		return;
		
	}
	
	build_progress("{linking} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}",30);
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["first_ipaddr"];
	echo "Communicate to the slave $eth/{$MAIN["BALANCE_IP"]}\n";
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp2=$SEND_SETTING";	
	
	$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		echo "$curl->error\n";
		debug_curl($curl->CURL_ALL_INFOS);
		build_progress("{linking} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
		return;
	}

	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo "Please verify that both servers must have the same Artica version\n";
		build_progress("{linking} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {protocol_error}",110);
		return;
	
	}
		
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo "{$array["ERROR_SHOW"]}\n";
		build_progress("{linking} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
		return;
	
	}	
	build_progress("{saving_local_parameters}",50);
	$nic=new system_nic($eth);
	$nic->IPADDR=$MAIN["second_ipaddr"];
	$nic->ucarp_enabled=1;
	$nic->ucarp_vip=$MAIN["BALANCE_IP"];
	$nic->ucarp_vid=$MAIN["ucarp_vid"];
	$nic->ucarp_master=1;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		echo "Unable to save local settings\n";
		build_progress("{saving_local_parameters} {failed}",110);
		return;
	}
	
	
	build_progress("{reboot_networks}",70);
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["second_ipaddr"];
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp3=$SEND_SETTING";
	
	
	$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		echo "$curl->error\n";
		debug_curl($curl->CURL_ALL_INFOS);
		build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo "Please verify that both servers must have the same Artica version\n";
		build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {protocol_error}",110);
		return;
	}
	
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo "{$array["ERROR_SHOW"]}\n";
		build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
		return;
	}	
	
	build_progress("{reboot_networks}",75);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build --force");
	build_progress("{reboot_networks}",80);
	squid_admin_mysql(0, "Rebooting Network", null,__FILE__,__LINE__);
	system("/etc/init.d/artica-ifup start --script=exec.failover.php/".__FUNCTION__);
	build_progress("{starting_service}",90);
	system("/etc/init.d/artica-failover start");
	sleep(3);
	build_progress("{done}",100);
	
}

function exunlink(){
	
	$unix=new unix();
	$sock=new sockets();
	$net=new networking();
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["first_ipaddr"];
	$SEND_SETTING=base64_encode(serialize($MAIN));
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp2-remove=$SEND_SETTING&continue=true";
	
	if(!$GLOBALS["FORCE"]){
		build_progress("Notify {$MAIN["SLAVE"]}",20);
		
		$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			echo "$curl->error\n";
			debug_curl($curl->CURL_ALL_INFOS);
			build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
			return;
		}
		
		if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
			echo "Please verify that both servers must have the same Artica version\n";
			build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {protocol_error}",110);
			return;
		}
		
		$array=unserialize(base64_decode($re[1]));
		if($array["ERROR"]){
			echo "{$array["ERROR_SHOW"]}\n";
			build_progress("{reboot_networks} {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} {failed}",110);
			return;
		}
	}
	build_progress("{please_wait_reconfigure_network}",80);
	if(!$GLOBALS["FORCE"]){sleep(3);}
	$nic=new system_nic($eth);
	$nic->ucarp_enabled=0;
	$nic->ucarp_vip=null;
	$nic->ucarp_vid=0;
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(isset($MAIN["first_ipaddr"])){
		if($MAIN["first_ipaddr"]<>null){
			$nic->IPADDR=$MAIN["first_ipaddr"];
		}
	}
	if(!$nic->SaveNic()){
		echo "Unable to save local settings\n";
		build_progress("{saving_local_parameters} {failed}",110);
		return;
	}	
	//please_wait_reconfigure_network
	$sock->SET_INFO("HASettings", base64_encode(serialize(array())));
	
	build_progress("{reboot_networks}",75);
	if(!$GLOBALS["FORCE"]){sleep(3);}
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build --force");
	build_progress("{reboot_networks}",80);
	squid_admin_mysql(0, "Rebooting Network", null,__FILE__,__LINE__);
	system("/etc/init.d/artica-ifup start --script=exec.failover.php/".__FUNCTION__);
	build_progress("{starting_service}",90);
	system("/etc/init.d/artica-failover stop");
	sleep(3);
	build_progress("{done}",100);
	
}

function debug_curl($array){
	

	while (list ($num, $val) = each ($array) ){
		if(is_array($val)){
			while (list ($a, $b) = each ($val) ){echo "$a......:$b\n";}
			$val=null;
			$val=@implode("\n", $tt);
		}
		
		
		if(strtolower($num)=="url"){continue;}
		echo "$num......:$val\n";
	}
}