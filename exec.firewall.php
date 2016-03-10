#!/usr/bin/php
<?php
$GLOBALS["PERIOD"]=null;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--period=([0-9]+)([a-z])#", implode(" ",$argv),$re)){$GLOBALS["PERIOD"]=$re[1].$re[2];}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');



if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--remove"){iptables_delete_all();exit;}
if($argv[1]=="--apply"){iptables_apply();exit;}

function iptables_apply(){
	$filename="/usr/share/artica-postfix/ressources/logs/web/exec.firewall.php.html";
	$unix=new unix();
	if($GLOBALS["PERIOD"]<>null){
		if(preg_match("#([0-9]+)([a-z])#", $GLOBALS["PERIOD"],$re)){
			$period=intval($re[1]);
			$unit=strtolower(trim($re[2]));
			if($unit=="m"){$period=$period*60;}
		}
	}
	
	@unlink($filename);
	if(!is_numeric($period)){$period=0;}
	
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /bin/artica-firewall.sh >>/usr/share/artica-postfix/ressources/logs/web/exec.firewall.php.html 2>&1 &");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/exec.firewall.php.html",0755);
	if($period>0){
		@file_put_contents($filename,"Executing FireWall script for a period of {$period} seconds...");
		sleep($period);
		@file_put_contents($filename,"\n\n************* Removing Firewall rules... *************\n");
		iptables_delete_all();
		@file_put_contents($filename,"\n\n************* Removing Firewall rules done *************\n...");
		
	}
	
}

function iptables_delete_all(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaFireWall#";
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$c++;continue;}
		$conf=$conf . $ligne."\n";
	}

	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	if($c>0){echo "$c Firewall rules removed\n";}
}

function build(){
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	$FireHolEnable=intval($sock->GET_INFO("FireHolEnable"));
	if($FireHolEnable==1){
		@unlink("/bin/artica-firewall.sh");
		iptables_delete_all();
		return;
	}
	
	iptables_delete_all();
	$FINAL_LOG_DROP=array();
	
	
	if(!$q->FIELD_EXISTS("nics","isFWAcceptNet","artica_backup")){
		$sql="ALTER TABLE `nics` ADD `isFWAcceptNet` smallint( 1 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	}
	if(!$q->FIELD_EXISTS("nics","isFWAcceptArtica","artica_backup")){
		$sql="ALTER TABLE `nics` ADD `isFWAcceptArtica` smallint( 1 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	}	
	
	
	$sql="SELECT `Interface`,`Bridged`,`BridgedTo`,`isFWAcceptNet`,`isFWAcceptArtica`,`isFWLogBlocked` FROM `nics` WHERE `isFW`=1 AND `Bridged`=0";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$SCRIPT[]="#! /bin/sh";
	$SCRIPT[]="$php /usr/share/artica-postfix/exec.syslog-engine.php --buildconf";
	$SCRIPT[]="if [ ! -f /var/log/iptables.log ]; then";
	$SCRIPT[]="\t$echo \"/var/log/iptables.log doesn't exists...\"";
	$SCRIPT[]="\t".$unix->LOCATE_SYSLOG_INITD()." restart";
	$SCRIPT[]="fi";
	$SCRIPT[]="$echo \"Removing Firewall rules...\"";
	$SCRIPT[]=$php." ".__FILE__." --remove || true";
	$SCRIPT[]="# -------------- Q . O . S --------------";
	$SCRIPT[]=build_qos();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$CountDeInterface=mysql_num_rows($results);
	$SCRIPT[]="$echo \"Firewall enabled on $CountDeInterface Interface(s)\"";
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	
	
	$net=new networkscanner();
	while (list ($num, $maks) = each ($net->networklist)){
		if(trim($maks)==null){continue;}
		$SCRIPT[]="# Accept potential Network $maks";
		$hash[$maks]=$maks;
	}
	$ALL_RULES=0;
	if($CountDeInterface>0){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ALL_RULES++;
			$isFWAcceptNet=intval($ligne["isFWAcceptNet"]);
			$J_LOGPRX="--j LOG --log-level debug --log-prefix \"AID=0/INPUT/REJECT \"";
			$InInterface=" -i {$ligne["Interface"]} ";
			$SCRIPT_FINAL[]="$iptables -A INPUT $InInterface $MARKLOG -j REJECT || true";
			if($ligne["isFWLogBlocked"]==1){
				$FINAL_LOG_DROP["$iptables -A INPUT $InInterface $MARKLOG $J_LOGPRX || true"]=true;
			}
			
			$SCRIPT[]="$iptables -I INPUT $InInterface -s 127.0.0.1 $MARKLOG -j ACCEPT || true";
			$SCRIPT[]="$iptables -I INPUT $InInterface -d 127.0.0.1 $MARKLOG -j ACCEPT || true";
			$SCRIPT[]="# $InInterface Accept local network ? = $isFWAcceptNet";
			if($isFWAcceptNet==1){
				reset($hash);
				while (list ($num, $maks) = each ($hash)){
					$SCRIPT[]="$iptables -I INPUT $InInterface -d $maks $MARKLOG -j ACCEPT || true";
					$SCRIPT[]="$iptables -I INPUT $InInterface -s $maks $MARKLOG -j ACCEPT || true";
				}
			}
			
			$SCRIPT[]=BuilFWdRules($ligne["Interface"],"INPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules($ligne["Interface"],"OUTPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules_FORWARD($ligne["Interface"],$ligne["isFWLogBlocked"]);
			
		}
	}
	
	
	$sql="SELECT * FROM `nics_bridge` WHERE `isFW`=1";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		$SCRIPT[]="#".str_replace("\n", " ", $q->mysql_error);
	}
	$CountDeInterface=mysql_num_rows($results);
	$SCRIPT[]="$echo \"Firewall enabled on $CountDeInterface Bridge(s)\"";
		
	if($CountDeInterface>0){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ALL_RULES++;
			$J_LOGPRX="--j LOG --log-level debug --log-prefix \"AID={$ligne["ID"]}/INPUT/REJECT \"";
			
			
			$SCRIPT[]="$echo \"Apply rules on bridge br{$ligne["ID"]} log block={$ligne["isFWLogBlocked"]}\"";
			$interface="br{$ligne["ID"]}";
			$InInterface=" -i $interface ";
			
			$SCRIPT[]="$iptables -I INPUT $InInterface -s 127.0.0.1 $MARKLOG -j ACCEPT || true";
			
			reset($hash);
			while (list ($num, $maks) = each ($hash)){
				$SCRIPT[]="$iptables -I INPUT $InInterface -d $maks $MARKLOG -j ACCEPT || true";
				$SCRIPT[]="$iptables -I INPUT $InInterface -s $maks $MARKLOG -j ACCEPT || true";
			}
			
			$SCRIPT[]=BuilFWdRules($interface,"INPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules($interface,"OUTPUT",$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules_FORWARD($interface,$ligne["isFWLogBlocked"]);
			$SCRIPT[]=BuilFWdRules_MARK($interface);
			
			if($ligne["isFWLogBlocked"]==1){
				$FINAL_LOG_DROP["$iptables -A INPUT $InInterface $MARKLOG $J_LOGPRX || true"]=true;
			}
			
			$SCRIPT_FINAL[]="$iptables -A INPUT $InInterface $MARKLOG -j REJECT || true";
			
	
	
		}

	}
	
	$SCRIPT_FINAL[]=ProtectArtica();
	
	
	$SCRIPT[]="#Final step, block necessaries connections";
	if(count($FINAL_LOG_DROP)>0){
		while (list ($itemSRC, $b) = each ($FINAL_LOG_DROP) ){
			if(is_array($itemSRC)){continue;}
			$SCRIPT[]=$itemSRC;
		}
		
	}
	
	$SCRIPT[]=@implode("\n", $SCRIPT_FINAL);
	$SCRIPT[]="exit 0\n";
	@file_put_contents("/bin/artica-firewall.sh", @implode("\n", $SCRIPT));
	@chmod("/bin/artica-firewall.sh",0755);
	echo "[".__LINE__."]: /bin/artica-firewall.sh done...\n";
	
}

function ProtectArtica(){
	
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
	$LighttpdArticaListenIP=$sock->GET_INFO("LighttpdArticaListenIP");
	$ArticaHttpsPort=intval($sock->GET_INFO("ArticaHttpsPort"));
	$iptables=$unix->find_program("iptables");
	if(!isset($NETWORK_ALL_INTERFACES[$LighttpdArticaListenIP])){$LighttpdArticaListenIP=null;}
	if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}

	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$SCRIPT_FINAL[]="";
	$SCRIPT_FINAL[]="#Artica Web interface listens on `$LighttpdArticaListenIP` port:{$ArticaHttpsPort}";
	
	if($LighttpdArticaListenIP<>null){$LighttpdArticaListenIP=" -d $LighttpdArticaListenIP";}
	$CountOfRules=$q->COUNT_ROWS("iptables_webint","artica_backup");
	
	if($CountOfRules==0){
		
		$sql="SELECT `Interface`,`isFWAcceptArtica` FROM `nics` WHERE `isFW`=1 AND `isFWAcceptArtica`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$SCRIPT_FINAL[]="#This rule allow connections to the Web interface from {$ligne["Interface"]} in order to allow access to Artica Web interface";
			$SCRIPT_FINAL[]="$iptables -I INPUT -i {$ligne["Interface"]} $LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
			$SCRIPT_FINAL[]="";
		}
		return @implode("\n", $SCRIPT_FINAL);
	}
	
	
	$SCRIPT_FINAL[]="#This rule allow connection to the Web interface for only $CountOfRules items";
	$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j DROP || true";
	$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG --j LOG --log-level debug --log-prefix \"AID=0/INPUT/REJECT\" || true";
	
	$results=$q->QUERY_SQL("SELECT * FROM iptables_webint","artica_backup");
	if(!$q->ok){
		$q->mysql_error=str_replace("\n", "", $q->mysql_error);
		$SCRIPT_FINAL[]="# $q->mysql_error";
		$SCRIPT_FINAL[]="#This rule allow connections to the Web interface in order to allow access to Artica Web interface";
		$SCRIPT_FINAL[]="$iptables -I INPUT$LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
		$SCRIPT_FINAL[]="";
		return @implode("\n", $SCRIPT_FINAL);
	}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$SCRIPT_FINAL[]="$iptables -I INPUT -s {$ligne["pattern"]} $LighttpdArticaListenIP -p tcp --dport $ArticaHttpsPort $MARKLOG -j ACCEPT || true";
	}
	
	$SCRIPT_FINAL[]="";
	return @implode("\n", $SCRIPT_FINAL);
	
	
	
}
function BuilFWdRules_MARK($interface){
	$q=new mysql();
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$iptables=$unix->find_program("iptables");
	$sock=new sockets();
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";

	$sql="SELECT * FROM iptables_main WHERE
	iptables_main.eth='$interface' AND iptables_main.MOD='MARK'
	AND enabled=1
	ORDER BY zOrder";

	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$CountDeRules=mysql_num_rows($results);
	$SCRIPT[]="";
	$SCRIPT[]="$echo \"$interface/MARK -> $CountDeRules rules(s)\"";
	$SCRIPT[]="################## MARK ##################";


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$InInterface=" -o $interface ";
		$mark=0;
		$OutInterface=null;
		$proto=null;
		$LOG=0;
		
		
		if($ligne["jlog"]){$LOG=1;}
		
		$ACTION=$ligne["accepttype"];
		if($ligne["QOS"]>0){
			if($EnableQOS==0){continue;}
			$ligne["MARK"]=0;
			$mark=$ligne["QOS"];
		}
		if($ligne["MARK"]>0){$mark=$ligne["MARK"];}
		if($mark==0){
			$SCRIPT[]="{$ligne["rulename"]}:{$ligne["ID"]} no mark set";
			continue;
		}
		
		$J_LOGPRX="-j LOG --log-level debug --log-prefix \"AID={$ligne["ID"]}/MARK/{$ligne["accepttype"]} MARK=$mark \"";
		$J=" -j MARK --set-mark $mark";
		$APPEND="-I";
		if($ligne["ForwardNIC"]<>null){$OutInterface=" -o {$ligne["ForwardNIC"]} ";$InInterface=null;}
		if($ligne["proto"]<>null){$proto=" -p {$ligne["proto"]} ";}
		$GroupTime=GroupTime($ligne);
		
		
		$forward_prefix="$iptables -t mangle $APPEND POSTROUTING $InInterface$OutInterface$proto$GroupTime ";


		$sourcegroups=GroupInArray($ligne["source_group"]);
		$DestGroups=GroupInArray($ligne["dest_group"]);
		$portsGroups=GroupInArray($ligne["destport_group"]);
		$ForwardTo=GroupForward($ligne["ForwardTo"]);
		if(is_array($portsGroups)){$portsGroups=null;}


		if( (count($sourcegroups)>0 ) AND (count($DestGroups)>0) ){
			echo "[".__LINE__."]: MODE 0:: Source(s) and Desintation(s)\n";
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
					while (list ($itemDST, $b) = each ($DestGroups) ){
					$LOG_RULE="$forward_prefix$itemSRC $itemDST $portsGroups $MARKLOG $J_LOGPRX";
					if($LOG==1){$SCRIPT[]=$LOG_RULE;}	
					$SCRIPT[]="$forward_prefix$itemSRC $itemDST $portsGroups $MARKLOG $J || true";
				}
			}

			continue;
		}


		if( (count($sourcegroups)==0 ) AND (count($DestGroups)>0) ){
				echo "[".__LINE__."]:  MODE 1:: Source = 0 and Destination >0 portsGroups = $portsGroups\n";
				while (list ($itemDST, $b) = each ($DestGroups) ){
					$LOG_RULE="$forward_prefix$itemDST $portsGroups $MARKLOG $J_LOGPRX";
					if($LOG==1){$SCRIPT[]=$LOG_RULE;}
					
					$SCRIPT[]="$forward_prefix $itemDST $portsGroups $MARKLOG $J || true";
					
				}
				continue;
			}


			if( (count($sourcegroups)>0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 2:: Source > 0 and Dests == 0 portsGroups = $portsGroups\n";}
				while (list ($itemSRC, $b) = each ($sourcegroups) ){
					$LOG_RULE="$forward_prefix$itemSRC $portsGroups $MARKLOG $J_LOGPRX";
					if($LOG==1){$SCRIPT[]=$LOG_RULE;}
					
					$SCRIPT[]="$forward_prefix$itemSRC $portsGroups $MARKLOG $J || true";
					
				}
				continue;
			}

			if( (count($sourcegroups)==0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 3:: Source == 0 and Dests == 0\n";}
				$LOG_RULE="$forward_prefix$portsGroups $MARKLOG $J_LOGPRX";
				if($LOG==1){$SCRIPT[]=$LOG_RULE;}
				
				$SCRIPT[]="$forward_prefix$portsGroups $MARKLOG $J || true";
				continue;
			}

			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Unknown ???\n";}

	}
	return @implode("\n", $SCRIPT);

}

function BuilFWdRules_FORWARD($interface){
	$q=new mysql();
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$iptables=$unix->find_program("iptables");
	$IpClass=new IP();
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	$sock=new sockets();
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$sql="SELECT * FROM iptables_main WHERE
	iptables_main.eth='$interface' AND iptables_main.MOD='FORWARD'
	AND enabled=1
	ORDER BY zOrder";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$CountDeRules=mysql_num_rows($results);
	$SCRIPT[]="";
	$SCRIPT[]="$echo \"$interface/FORWARD -> $CountDeRules rules(s)\"";	
	$SCRIPT[]="################## FORWARD ##################";
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$InInterface=" -i $interface ";
		$masquerade=intval($ligne["masquerade"]);
		$ACTION=$ligne["accepttype"];
		if($ACTION=="LOG"){$LOG=1;}
		$J_LOGPRX="-j LOG --log-level debug --log-prefix \"AID={$ligne["ID"]}/FORWARD/$ACTION \"";
		if($ACTION<>"DROP"){
			if($ACTION<>"LOG"){
				$J_LOGPRX="-j LOG --log-level debug --log-prefix \"AID={$ligne["ID"]}/FORWARD/FORWARD \"";
			}
		}
		$LOG=$ligne["jlog"];
		$OutInterface=null;
		$proto=null;
		$SCRIPT[]="# rule {$ligne["rulename"]}";
	
		$APPEND="-A";
		$J=" -j $ACTION";
		if($ligne["OverideNet"]==1){$APPEND="-I";}
		if($ligne["ForwardNIC"]<>null){$OutInterface=" -o {$ligne["ForwardNIC"]} ";}
		if($ligne["proto"]<>null){$proto=" -p {$ligne["proto"]} ";}
		$GroupTime=GroupTime($ligne);
		
		$MARK_GET=null;
		
		if($EnableL7Filter==1){
			if($ligne["L7Mark"]>0){
				$MARK_GET=" -m mark --mark {$ligne["L7Mark"]}";
			}
		}
		
		$prefix="$iptables $APPEND FORWARD $InInterface$OutInterface$proto$GroupTime$MARK_GET ";
		$forward_prefix="$iptables $APPEND PREROUTING -t nat$InInterface$OutInterface$proto$GroupTime$MARK_GET ";
		$masquerade_prefix="$iptables -A POSTROUTING -t nat$InInterface$OutInterface$proto$GroupTime$MARK_GET "; //-j MASQUERADE
		
		$sourcegroups=GroupInArray($ligne["source_group"]);
		$DestGroups=GroupInArray($ligne["dest_group"]);
		$portsGroups=GroupInArray($ligne["destport_group"],true);
		$ForwardTo=GroupForward($ligne["ForwardTo"]);
			
		if( ( count($sourcegroups)>0 ) AND (count($DestGroups)>0) ){
			$SCRIPT[]="# Source(s) and Destination(s) [".__LINE__."]";
			echo "[".__LINE__."]: MODE -1:: Source(s) and Destination(s) and port(s)\n";
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				while (list ($itemDST, $b) = each ($DestGroups) ){
					while (list ($none, $Isport) = each ($portsGroups) ){
						$prtTxt=null;
						if($Isport==0){if(count($portsGroups)>1){continue;}}
						if($Isport>0){$prtTxt=" --dport $Isport";}
						$LOG_RULE="$prefix$itemSRC $itemDST $prtTxt $MARKLOG $J_LOGPRX";
						if($LOG==1){$SCRIPT[]=$LOG_RULE;}
						if($ACTION=="LOG"){continue;}
							
						$SCRIPT[]=$prefix."$itemSRC $itemDST $prtTxt $MARKLOG $J || true";
							
						if($ACTION=="DROP"){continue;}
						if($ForwardTo<>null){
							$SCRIPT[]=$forward_prefix."$itemSRC $itemDST $prtTxt $MARKLOG -j DNAT --to-destination $ForwardTo || true";
							if($masquerade==1){ $SCRIPT[]="$masquerade_prefix $itemSRC $itemDST $prtTxt $MARKLOG -j MASQUERADE || true"; }
						}
					}
				}
			}
			
		continue;}
		
		
		reset($portsGroups);
				
			if( (count($sourcegroups)==0 ) AND (count($DestGroups)>0) ){
				$SCRIPT[]="# No Source but Destination action=$ACTION Forward=$ForwardTo Ports=". count($portsGroups)."[".__LINE__."]";
				echo "[".__LINE__."]:  MODE 1:: Source = 0 and Destination >0 portsGroups = $portsGroups\n";
				
				if(count($portsGroups)==0){
					$xport=$IpClass->ExtractPort($ForwardTo);
					if($xport){
						$SCRIPT[]="# No destination port assume $xport";
						$portsGroups[]=$xport;
					}
				}
				
				
				while (list ($itemDST, $b) = each ($DestGroups) ){
						
						reset($portsGroups);
						while (list ($none, $Isport) = each ($portsGroups) ){
							$prtTxt=null;
							if($Isport==0){
								if(count($portsGroups)>1){
									$SCRIPT[]="# Ports is an array [".__LINE__."]";
									continue;
								}
							}
							if($Isport>0){$prtTxt=" --dport $Isport";}
							$LOG_RULE="$prefix $itemDST $prtTxt $MARKLOG $J_LOGPRX";
							if($LOG==1){$SCRIPT[]=$LOG_RULE;}
							
							if($ACTION=="LOG"){continue;}
							$SCRIPT[]=$prefix."$itemDST $prtTxt $MARKLOG $J || true";
							if($ACTION=="DROP"){continue;}
							if($ForwardTo<>null){
								$SCRIPT[]=$forward_prefix."$itemDST $prtTxt $MARKLOG -j DNAT --to-destination $ForwardTo || true";
								if($masquerade==1){ $SCRIPT[]="$masquerade_prefix $itemSRC $itemDST $prtTxt $MARKLOG -j MASQUERADE || true"; }
							}	
						}
				
				}
				
			continue;
			}
		
		
			if( (count($sourcegroups)>0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 2:: Source > 0 and Dests == 0 portsGroups = $portsGroups\n";}
					while (list ($itemSRC, $b) = each ($sourcegroups) ){
						while (list ($none, $Isport) = each ($portsGroups) ){
							$prtTxt=null;
							if($Isport==0){if(count($portsGroups)>1){continue;}}
							if($Isport>0){$prtTxt=" --dport $Isport";}
							$LOG_RULE="$prefix $itemSRC $prtTxt $MARKLOG $J_LOGPRX";
							if($LOG==1){$SCRIPT[]=$LOG_RULE;}
							if($ACTION=="LOG"){continue;}
							
							$SCRIPT[]="$prefix $itemSRC $prtTxt $MARKLOG $J || true";
							if($ACTION=="DROP"){continue;}
							if($ForwardTo<>null){
								$SCRIPT[]=$forward_prefix."$itemSRC $prtTxt $MARKLOG -j DNAT --to-destination $ForwardTo || true";
								if($masquerade==1){ $SCRIPT[]="$masquerade_prefix $itemSRC $itemDST $prtTxt $MARKLOG -j MASQUERADE || true"; }
							}
						}
				}
			continue;
			}
		
			if( (count($sourcegroups)==0 ) AND (count($DestGroups)==0) ){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 3:: Source == 0 and Dests == 0\n";}
				while (list ($none, $Isport) = each ($portsGroups) ){
					$prtTxt=null;
					if($Isport==0){if(count($portsGroups)>1){continue;}}
					if($Isport>0){$prtTxt=" --dport $Isport";}
					$LOG_RULE="$prefix $prtTxt $MARKLOG $J_LOGPRX";
					if($LOG==1){$SCRIPT[]=$LOG_RULE;}
					if($ACTION=="LOG"){continue;}
					
					$SCRIPT[]="$prefix $prtTxt $MARKLOG $J || true";
					if($ACTION=="DROP"){continue;}
					if($ForwardTo<>null){
						$SCRIPT[]=$forward_prefix." $prtTxt $MARKLOG -j DNAT --to-destination $ForwardTo || true";
						if($masquerade==1){ $SCRIPT[]="$masquerade_prefix $itemSRC $itemDST $prtTxt $MARKLOG -j MASQUERADE || true"; }
					}
				}
				continue;
			}
		
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Unknown ???\n";}
		
		}
		return @implode("\n", $SCRIPT);
	
}

function GroupForward($host){
	if($host==null){return null;}
	$port=null;
	$ip=new IP();
	if(preg_match("#(.*?):([0-9]+)#", $host,$re)){
		$host=$re[1];
		$port=":{$re[2]}";
	}
	
	if(!$ip->isValid($host)){
		$host=gethostbyname($host);
		return "$host$port";
	}
	if($port<>null){return "$host$port";}
	return $host;
}


function BuilFWdRules($interface,$TABLE,$isFWLogBlocked=0){
	$q=new mysql();
	$unix=new unix();
	$sock=new sockets();
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaFireWall\"";
	
	$sql="SELECT * FROM iptables_main WHERE 
			iptables_main.eth='$interface' AND iptables_main.MOD='$TABLE'
			AND enabled=1
			ORDER BY zOrder";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$CountDeRules=mysql_num_rows($results);
	$SCRIPT[]="";
	$SCRIPT[]="$echo \"$interface: $TABLE -> $CountDeRules rules(s)\"";
	$SCRIPT[]="################## $TABLE ##################";
	if($CountDeRules==0){return @implode("\n", $SCRIPT);}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$J_LOGPRX="--j LOG --log-level debug --log-prefix \"AID={$ligne["ID"]}/$TABLE/{$ligne["accepttype"]} \"";
		$rulename=$ligne["rulename"];
		$ACTION=$ligne["accepttype"];
		$rulename=str_replace('"', "`", $rulename);
		$SCRIPT[]="$echo \"$interface: Firewall Rule $rulename\"";
		$OutInterface=null;
		$proto=null;
		$LOG=$ligne["jlog"];
		if($ACTION=="LOG"){$LOG=1;}
		if($isFWLogBlocked==1){$LOG=1;}
		
		$InInterface=" -i $interface ";
		if($TABLE=="OUTPUT"){$InInterface=" -o $interface ";}
		$APPEND="-A";
		$J=" -j $ACTION";
		if($ligne["OverideNet"]==1){$APPEND="-I";}
		if($ligne["proto"]<>null){$proto=" -p {$ligne["proto"]} ";}
		$GroupTime=GroupTime($ligne);
		$MARK_GET=null;
		
		if($ligne["L7Mark"]>0){
			if($EnableL7Filter==0){
				$SCRIPT[]="$echo \"$interface: Firewall Rule $rulename L7Mark/EnableL7Filter disabled \"";
				continue;
			}
			$MARK_GET=" -m mark --mark {$ligne["L7Mark"]}";
		}
		
		
		$prefix="$iptables $APPEND $TABLE $InInterface$proto";
		$sourcegroups=GroupInArray($ligne["source_group"]);
		$DestGroups=GroupInArray($ligne["dest_group"]);
		$portsGroups=GroupInArray($ligne["destport_group"]);
		if(is_array($portsGroups)){$portsGroups=null;}
	
		if( (count($sourcegroups)>0 ) AND (count($DestGroups)>0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: MODE 0:: Sources and Destss\n";}
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				while (list ($itemDST, $b) = each ($DestGroups) ){
					$RULE="$TABLE $InInterface $itemSRC $itemDST $proto$portsGroups$GroupTime$MARK_GET $MARKLOG";
					if($LOG==1){$SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
					if($ACTION=="LOG"){continue;}
					
					$SCRIPT[]="$iptables $APPEND $RULE $J || true";
				}
			}
				
		continue;}
			
			
		if( (count($sourcegroups)==0 ) AND (count($DestGroups)>0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 1:: Source = 0 and Destss >0 portsGroups = $portsGroups\n";}
			while (list ($itemDST, $b) = each ($DestGroups) ){
				$RULE="$TABLE $InInterface $itemDST $proto$portsGroups$GroupTime$MARK_GET $MARKLOG";
				if($LOG==1){$SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
					
				if($ACTION=="LOG"){continue;}
				$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			}
		continue;}
		
				
		if( (count($sourcegroups)>0 ) AND (count($DestGroups)==0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 2:: Source > 0 and Dests == 0 portsGroups = $portsGroups\n";}
			while (list ($itemSRC, $b) = each ($sourcegroups) ){
				$RULE="$TABLE $InInterface $itemSRC $proto$portsGroups$GroupTime$MARK_GET $MARKLOG";
				if($LOG==1){$SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
				
				if($ACTION=="LOG"){continue;}
				$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			}
		continue;}			
		
		if( (count($sourcegroups)==0 ) AND (count($DestGroups)==0) ){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]:  MODE 3:: Source == 0 and Dests == 0\n";}
			$RULE="$TABLE $InInterface $proto$portsGroups$GroupTime$MARK_GET $MARKLOG";
			if($LOG==1){$SCRIPT_LOG[]="$iptables -I $RULE $J_LOGPRX || true"; }
			
			if($ACTION=="LOG"){continue;}
			$SCRIPT[]="$iptables $APPEND $RULE $J || true";
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Unknown ???\n";}

	}
	
	$SCRIPT_TXT=null;
	if(count($SCRIPT_LOG)>0){
		$SCRIPT_TXT="\n#". count($SCRIPT_LOG)." Logging rules:\n".@implode("\n", $SCRIPT_LOG);
	}
	
	return @implode("\n", $SCRIPT).$SCRIPT_TXT;
}

function GroupInLine($ID=0){
	if($ID==0){return array();}
	$q=new mysql_squid_builder();
	$sql="SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$GroupType=$ligne["GroupType"];
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $GroupType Get items.\n";}
	$IpClass=new IP();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1";	
	
	
}


function GroupInArray($ID=0,$IsArray=false){
	
	if($ID==0){return array();}
	$q=new mysql_squid_builder();
	$sql="SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	$GroupType=$ligne["GroupType"];
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $GroupType Get items.\n";}
	
	
	if($GroupType=="teamviewer"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->teamviewer_networks();
		if($GLOBALS["VERBOSE"]){echo "teamviewer_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
			
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}
	
	if($GroupType=="office365"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->office365_networks();
		if($GLOBALS["VERBOSE"]){echo "office365 ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
				
		}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}	
	
	if($GroupType=="skype"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->skype_networks();
		if($GLOBALS["VERBOSE"]){echo "skype_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
				
		}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}	
	
	
	
	if($GroupType=="google"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->google_networks();
		if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
				
		}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}
	if($GroupType=="google_ssl"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->google_ssl();
		if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
	
		}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: teamviewer::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}
	if($GroupType=="dropbox"){
		include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
		$products_ip_ranges=new products_ip_ranges();
		$array=$products_ip_ranges->dropbox_networks();
		if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
		while (list ($a, $b) = each ($array) ){
			if(preg_match("#([0-9]+)-([0-9]+)#", $b)){
				$f["-m iprange --dst-range $b"]=true;
				continue;
			}
			$f["--dst $b"]=true;
	
		}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: dropbox::$ID -> ".count($f)." item(s).\n";}
		return $f;
	}	
	
	$IpClass=new IP();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1";
	
	
	$f=array();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $pattern item.\n";}
		
		if($GroupType=="arp"){
			if(!$IpClass->IsvalidMAC($pattern)){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> $pattern INVALID.\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> ADD -m mac --mac-source $pattern.\n";}
			$f["-m mac --mac-source $pattern"]=true;
			continue;
			}
			
			
		
		
		if($GroupType=="src"){
			if(preg_match("#[0-9\.]+-[0-9\.]+#", $pattern)){
				$f["-m iprange --src-range $pattern"]=true;
				continue;
			}
			
			$f["--source $pattern"]=true;
			continue;
		}
		
		if($GroupType=="dst"){
			if(preg_match("#[0-9\.]+-[0-9\.]+#", $pattern)){
				$f["-m iprange --dst-range $pattern"]=true;
				continue;
			}
				
			$f["--dst $pattern"]=true;
			continue;
		}

		if($GroupType=="port"){
			$f[$pattern]=true;
		}
		
	}
	
	if($GroupType=="port"){
		$T=array();
		if($IsArray){$T[]=0;}
		
		while (list ($a, $b) = each ($f) ){ 
			$T[]=$a; }
		if($IsArray){return $T;}
		if(count($T)==0){return null;}
		if(count($T)==1){return "--destination-port ".@implode("", $T);}
		
		return "--destination-ports ".@implode(",", $T);
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$ID -> ".count($f)." item(s).\n";}
	return $f;
	
	
	
}
function GroupTime($ligne){
	if($ligne["enablet"]==0){return null;}
	$f=array();
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);

	$TTIME=unserialize($ligne["time_restriction"]);

	$DDS=array();

	while (list ($num, $maks) = each ($array_days)){
		if($TTIME["D{$num}"]==1){$DDS[]=$num;}

	}
	
	if(count($DDS)>0){
		$f[]="--weekdays ".@implode(",", $DDS);
	}

	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$f[]="--timestart {$TTIME["ftime"]} --timestop {$TTIME["ttime"]}";
	}

	if(count($f)>0){
		return " -m time ".@implode(" ", $f)." ";
	}


}

function build_qos(){
	$q=new mysql();
	$unix=new unix();
	$sock=new sockets();
	$tc=$unix->find_program("tc");
	if(!is_file($tc)){
		return "# tc No such binary";
	}
	$F=array();
	$nic=new system_nic();
	$F[]="# Remove rules on any interfaces";
	$results=$q->QUERY_SQL("SELECT Interface FROM nics ORDER BY Interface","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$Interface = $ligne["Interface"];
		$Interface=$nic->NicToOther($Interface);
		$INT[$Interface]=$Interface;
	}
	
	while (list ($Interface, $ligne) = each ($INT) ){
		$F[]="$tc qdisc del dev $Interface root";
	}
	
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	if($EnableQOS==0){
		return @implode("\n", $F);
	}
	
	
	$results=$q->QUERY_SQL("SELECT Interface,QOSMAX FROM nics WHERE QOS=1 ORDER BY Interface","artica_backup");
	if(mysql_num_rows($results)==0){return "# No interface defined for QOS";}
	$ID_ROOT=0;
	$classid=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$Interface = $ligne["Interface"];
		
		$results2=$q->QUERY_SQL("SELECT * FROM qos_containers WHERE enabled=1 AND eth='$Interface' ORDER BY prio","artica_backup");
		if(mysql_num_rows($results2)==0){
			$F[]="# $Interface no container defined or enabled";
			continue;
		}
		
		$Interface=$nic->NicToOther($Interface);
		$F[]="# *********** {$ligne["Interface"]} -> $Interface ***********";
		$ID_ROOT++;
		$classid++;
		$F[]="# $Interface Capacity of {$ligne["QOSMAX"]}Mbits Level $ID_ROOT";
		$F[]="$tc qdisc add dev $Interface root handle $ID_ROOT: htb default {$ID_ROOT}00";
		$F[]="$tc class add dev $Interface parent $ID_ROOT:0 classid $ID_ROOT:$classid htb rate {$ligne["QOSMAX"]}Mbit ceil {$ligne["QOSMAX"]}Mbit";
		
		
		while ($ligne = mysql_fetch_assoc($results2)) {
			$id=$ligne["ID"];
			$gar="{$ligne["rate"]}{$ligne["rate_unit"]}";
			$band="{$ligne["ceil"]}{$ligne["ceil_unit"]}";
			if(intval($ligne["rate"])==0){$F[]="# $Interface: Container {$ligne["name"]} Guaranteed Rate invalid"; continue; }
			if(intval($ligne["ceil"])==0){$F[]="# $Interface: Container {$ligne["name"]} Bandwidth invalid"; continue; }
			$F[]="# $Interface: Container {$ligne["name"]} Guaranteed Rate of $gar , Bandwidth of $band Level Prio {$ligne["prio"]}";
			$F[]="$tc class add dev $Interface parent $ID_ROOT:$classid classid $classid:$id htb rate $gar ceil $band";
			$F[]="# $Interface: Container {$ligne["name"]} add policy for mark $id";
			$F[]="$tc filter add dev $Interface parent $ID_ROOT: protocol ip prio {$ligne["prio"]} handle $id fw flowid $classid:$id";
			
		}
		
	
	}
	return @implode("\n", $F);
	
}


