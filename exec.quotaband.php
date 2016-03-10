<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Bandwidth quota service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');



$EnableSquidQuotasBandwidth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidQuotasBandwidth"));
$InfluxUseRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
if($InfluxUseRemote==1){
	$InfluxSyslogRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote"));
	if($InfluxSyslogRemote==1){$EnableSquidQuotasBandwidth=0;}
	
}
if($EnableSquidQuotasBandwidth==0){
	echo "Feature is not enabled, aborting...\n";
	build_progress("{disabled}",110);
	die();
}




ScanRules();


function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.quotasband.status.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);


}


function ScanRules(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	if(!$GLOBALS["FORCE"]){
		$pid=$unix->get_pid_from_file($pidfile);
	
		if($unix->process_exists($pid,basename(__FILE__))){
			build_progress("Process $pid already exists",110);
			echo "Process $pid already exists\n";
			return;
		}
	}
	
	if(system_is_overloaded(basename(__FILE__))){
		echo "Overloaded system, aborting\n";
		build_progress("Overloaded system",110);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
	$q=new mysql_squid_builder();
	
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	$baseHour="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/$HOUR";
	$baseDay="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY";
	$baseWeek="/home/squid/rttsize/$YEAR/$MONTH/$WEEK";
	$baseMonth="/home/squid/rttsize/$YEAR/$MONTH";
	
	
	$during[60]=$baseHour;
	$during[1440]=$baseDay;
	$during[10080]=$baseWeek;
	$GLOBALS["MUST_RELOAD_SQUID"]=false;
	$GLOBALS["STATUS"]=array();

	
	$sql="SELECT ID,PatternGroup FROM bandquotas_status WHERE `freeze`=1";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$key="{$ligne["ID"]}-{$ligne["PatternGroup"]}";
		$GLOBALS["STATUS"][$key]["FREEZE"]=1;
	}
	
	
	$sql="SELECT * FROM bandquotas WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL error",110);
		return;
	}
	
	if(mysql_num_rows($results)==0){
		build_progress("{no_rule}",110);
		set_status();
		return;
	}
	
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$QuotaSizeBytes=$ligne["QuotaSizeBytes"];
		$GroupType=$ligne["GroupType"];
		$PatternGroup=$ligne["PatternGroup"];
		$TimeFrame=$ligne["TimeFrame"];
		$basePath=$during[$TimeFrame];
		$RuleName=$ligne["RuleName"];
		$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
		build_progress("{Scanning} Rule:{$ligne["ID"]}",50);
		events("[INFO]: Analyze $RuleName",__LINE__);
		
		if($GroupType==0){
			if($GLOBALS["VERBOSE"]){echo "Scanning $PatternGroup [Active Directory] ($RuleName)\n";}
			ScanActiveDirectoryGroups($ligne["ID"],$RuleName,$PatternGroup,$basePath,$QuotaSizeBytes,$FileAcls);
			continue;
		}
		
		if($GroupType==2){
			ScanIpAddr_single($ligne["ID"],$RuleName,$PatternGroup,$basePath,$QuotaSizeBytes,$FileAcls);
			continue;
			
		}
		if($GroupType==1){
			if($GLOBALS["VERBOSE"]){echo "Scanning $PatternGroup [Simple Member] ($RuleName)\n";}
			Scan_member_single($ligne["ID"],$RuleName,$PatternGroup,$basePath,$QuotaSizeBytes,$FileAcls);
			continue;
		}
		if($GroupType==3){
			if($GLOBALS["VERBOSE"]){echo "Scanning $PatternGroup [Simple MAC] ($RuleName)\n";}
			ScanMacAddr_single($ligne["ID"],$RuleName,$PatternGroup,$basePath,$QuotaSizeBytes,$FileAcls);
			continue;
		}
		if($GroupType==4){
			if($GLOBALS["VERBOSE"]){echo "Scanning $PatternGroup [Full network] ($RuleName)\n";}
			ScanNetwork($ligne["ID"],$RuleName,$PatternGroup,$basePath,$QuotaSizeBytes,$FileAcls);
			continue;
		}		
		
	}
	build_progress("{Scanning} {status}",90);
	set_status();
	
	if($GLOBALS["MUST_RELOAD_SQUID"]){
		build_progress("{reloading_proxy_service}",95);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
		
	}
	
	
	build_progress("{done}",100);

}

function Scan_member_single($RuleID,$RuleName,$MEMBER,$basePath,$QuotaSizeBytes,$FileAcls){
	
	$FileAclsMD5_start=md5_file($FileAcls);
	
	
	if(!Scan_member($RuleID,$RuleName,$MEMBER,$basePath,$QuotaSizeBytes,$FileAcls)){
		@unlink($FileAcls);
		@touch($FileAcls);
	}else{
		@file_put_contents($FileAcls, $MEMBER);
	}
	
	$FileAclsMD5_end=md5_file($FileAcls);
	
	if($FileAclsMD5_end<>$FileAclsMD5_start){
		squid_admin_mysql(1, "$RuleName: Quota changed", @implode("\n", $GLOBALS["NOTIF_TEXT"]),__FILE__,__LINE__);
		$GLOBALS["MUST_RELOAD_SQUID"]=true;
	}
	
		
	
}

function ScanMacAddr_single($RuleID,$RuleName,$MACADDR,$basePath,$QuotaSizeBytes,$FileAcls){

	$FileAclsMD5_start=md5_file($FileAcls);


	if(!Scan_MacAddr($RuleID,$RuleName,$MACADDR,$basePath,$QuotaSizeBytes,$FileAcls)){
		@unlink($FileAcls);
		@touch($FileAcls);
	}else{
		@file_put_contents($FileAcls, $MACADDR);
	}

	$FileAclsMD5_end=md5_file($FileAcls);

	if($FileAclsMD5_end<>$FileAclsMD5_start){
		squid_admin_mysql(1, "$RuleName: Quota changed", @implode("\n", $GLOBALS["NOTIF_TEXT"]),__FILE__,__LINE__);
		$GLOBALS["MUST_RELOAD_SQUID"]=true;
	}



}


function ScanIpAddr_single($RuleID,$RuleName,$IPADDR,$basePath,$QuotaSizeBytes,$FileAcls){
	
	$FileAclsMD5_start=md5_file($FileAcls);

	
	if(!Scan_IpAddr($RuleID,$RuleName,$IPADDR,$basePath,$QuotaSizeBytes,$FileAcls)){
		@unlink($FileAcls);
		@touch($FileAcls);
	}else{
		@file_put_contents($FileAcls, $IPADDR);
	}
		
	$FileAclsMD5_end=md5_file($FileAcls);
	
	if($FileAclsMD5_end<>$FileAclsMD5_start){
		squid_admin_mysql(1, "$RuleName: Quota changed", @implode("\n", $GLOBALS["NOTIF_TEXT"]),__FILE__,__LINE__);
		$GLOBALS["MUST_RELOAD_SQUID"]=true;
	}
	
	
	
}

function ScanNetwork($RuleID,$RuleName,$network,$basePath,$QuotaSizeBytes,$FileAcls){
	events("[INFO]: Scanning $network",__LINE__);
	$acls_content=array();
	$NOTIF_TEXT=array();
	$basePath=$basePath."/IPADDR";
	$unix=new unix();
	$dirs=$unix->dirdir($basePath);
	$FileAclsMD5_start=md5_file($FileAcls);
	$IP=new IP();
	$f=array();
	while (list ($fullpath, $none) = each ($dirs) ){
		$addr=basename($fullpath);
		if(!$IP->isInRange($addr, $network)){continue;}
		if(!Scan_IpAddr($RuleID,$RuleName,$addr,$basePath,$QuotaSizeBytes,$FileAcls)){continue;}
		$f[]=$addr;
	}
	
	@file_put_contents($FileAcls, @implode("\n", $f));
	$FileAclsMD5_end=md5_file($FileAcls);
	
	if($FileAclsMD5_end<>$FileAclsMD5_start){
		squid_admin_mysql(1, "$RuleName: Quota changed", @implode("\n", $GLOBALS["NOTIF_TEXT"]),__FILE__,__LINE__);
		$GLOBALS["MUST_RELOAD_SQUID"]=true;
	}
	
	
	
}


function Scan_MacAddr($RuleID,$RuleName,$MACADDR,$basePath,$QuotaSizeBytes,$FileAcls){
	if(($GLOBALS["VERBOSE"])){echo "Path: $basePath\n";}
	events("[INFO]: Scanning $MACADDR",__LINE__);
	$acls_content=array();
	$NOTIF_TEXT=array();
	$basePath=$basePath."/MAC";
	$FileName="$basePath/$MACADDR/TOT";
	
	$key="$RuleID-$MACADDR";
	if(!is_file($FileName)){return false;}
	
	if(!isset($GLOBALS["STATUS"][$key]["FREEZE"])){$GLOBALS["STATUS"][$key]["FREEZE"]=0;}
	$Bytes=@file_get_contents($FileName);
	
	if($GLOBALS["VERBOSE"]){echo "$MACADDR $Bytes / $QuotaSizeBytes\n";}
	$Reste=intval($QuotaSizeBytes)-intval($Bytes);
	if($GLOBALS["VERBOSE"]){echo "$MACADDR Reste: $QuotaSizeBytes - $Bytes = $Reste / $QuotaSizeBytes\n";}
	
	$BytesKB=$Bytes/1024;
	$BytesMB=round($BytesKB/1024,2);
	
	$QuotaSizeKB=$QuotaSizeBytes/1024;
	$QuotaSizeMB=round($QuotaSizeKB/1024,2);
	
	
	if($Bytes<$QuotaSizeBytes){
		$perc=($Bytes/$QuotaSizeBytes)*100;
		$perc=round($perc);
	}else{
		$perc=100;
	}
	
	$GLOBALS["STATUS"][$key]["FLAGGED"]=0;
	$GLOBALS["STATUS"][$key]["MEMBER"]=$MACADDR;
	$GLOBALS["STATUS"][$key]["RULE"]=$RuleID;
	$GLOBALS["STATUS"][$key]["CURRENT"]=$Bytes;
	$GLOBALS["STATUS"][$key]["PERC"]=$perc;
	$GLOBALS["STATUS"][$key]["zDate"]=date("Y-m-d H:i:s");
	if($GLOBALS["STATUS"][$key]["FREEZE"]==1){
		events("[INFO]: Member: $MACADDR - FREEZE - ($RuleName)",__LINE__);
		return;
	}
	if($perc<99){return;}
	$GLOBALS["NOTIF_TEXT"][]="$MACADDR Exceed {$QuotaSizeMB}MB current {$BytesMB}MB";
	$GLOBALS["STATUS"][$key]["FLAGGED"]=1;
	events("[INFO]: [$RuleName] Member: $MACADDR ".FormatBytes($Bytes/1024,true)."/".FormatBytes($QuotaSizeBytes/1024,true)."Container:".FormatBytes($Reste/1024,true)." ({$perc}%)",__LINE__);
	
	return true;
	
}


function Scan_IpAddr($RuleID,$RuleName,$MEMBER,$basePath,$QuotaSizeBytes,$FileAcls){
	if(($GLOBALS["VERBOSE"])){echo "Path: $basePath\n";}
	events("[INFO]: Scanning $MEMBER",__LINE__);
	$acls_content=array();
	$NOTIF_TEXT=array();
	$basePath=$basePath."/IPADDR";
	$FileName="$basePath/$MEMBER/TOT";
	$key="$RuleID-$MEMBER";
	if(!is_file($FileName)){return false;}
	
	if(!isset($GLOBALS["STATUS"][$key]["FREEZE"])){$GLOBALS["STATUS"][$key]["FREEZE"]=0;}
	$Bytes=@file_get_contents($FileName);
	
	if($GLOBALS["VERBOSE"]){echo "$MEMBER $Bytes / $QuotaSizeBytes\n";}
	$Reste=intval($QuotaSizeBytes)-intval($Bytes);
	if($GLOBALS["VERBOSE"]){echo "$MEMBER Reste: $QuotaSizeBytes - $Bytes = $Reste / $QuotaSizeBytes\n";}
	
	$BytesKB=$Bytes/1024;
	$BytesMB=round($BytesKB/1024,2);
	
	$QuotaSizeKB=$QuotaSizeBytes/1024;
	$QuotaSizeMB=round($QuotaSizeKB/1024,2);
	
	
	if($Bytes<$QuotaSizeBytes){
		$perc=($Bytes/$QuotaSizeBytes)*100;
		$perc=round($perc);
	}else{
		$perc=100;
	}
	
	
	
	
	$GLOBALS["STATUS"][$key]["FLAGGED"]=0;
	$GLOBALS["STATUS"][$key]["MEMBER"]=$MEMBER;
	$GLOBALS["STATUS"][$key]["RULE"]=$RuleID;
	$GLOBALS["STATUS"][$key]["CURRENT"]=$Bytes;
	$GLOBALS["STATUS"][$key]["PERC"]=$perc;
	$GLOBALS["STATUS"][$key]["zDate"]=date("Y-m-d H:i:s");
	if($GLOBALS["STATUS"][$key]["FREEZE"]==1){
		events("[INFO]: Member: $MEMBER - FREEZE - ($RuleName)",__LINE__);
		return;
	}
	if($perc<99){return;}
	$GLOBALS["NOTIF_TEXT"][]="$MEMBER Exceed {$QuotaSizeMB}MB current {$BytesMB}MB";
	$GLOBALS["STATUS"][$key]["FLAGGED"]=1;
	events("[INFO]: [$RuleName] Member: $MEMBER ".FormatBytes($Bytes/1024,true)."/".FormatBytes($QuotaSizeBytes/1024,true)."Container:".FormatBytes($Reste/1024,true)." ({$perc}%)",__LINE__);
	
	return true;
	
}


function Scan_member($RuleID,$RuleName,$MEMBER,$basePath,$QuotaSizeBytes,$FileAcls){
	
	events("[INFO]: Scanning $MEMBER ($RuleName)",__LINE__);
	$FileName="$basePath/$MEMBER/TOT";
	$key="$RuleID-$MEMBER";
	
	if(!is_file($FileName)){
		events("[INFO]: Member: $MEMBER no status... ($RuleName)",__LINE__);
		if(isset($GLOBALS["STATUS"][$key])){unset($GLOBALS["STATUS"][$key]);}
		return false;
	}
	
	
	$Bytes=@file_get_contents($FileName);
	
	if($GLOBALS["VERBOSE"]){echo "$MEMBER $Bytes / $QuotaSizeBytes\n";}
	$Reste=intval($QuotaSizeBytes)-intval($Bytes);
	if($GLOBALS["VERBOSE"]){echo "$MEMBER Reste: $QuotaSizeBytes - $Bytes = $Reste / $QuotaSizeBytes\n";}
	
	$BytesKB=$Bytes/1024;
	$BytesMB=round($BytesKB/1024,2);
	
	$QuotaSizeKB=$QuotaSizeBytes/1024;
	$QuotaSizeMB=round($QuotaSizeKB/1024,2);
	
	
	if($Bytes<$QuotaSizeBytes){
		$perc=($Bytes/$QuotaSizeBytes)*100;
		$perc=round($perc);
	}else{
		$perc=100;
	}
	
	
	if(!isset($GLOBALS["STATUS"][$key]["FREEZE"])){$GLOBALS["STATUS"][$key]["FREEZE"]=0;}
	$GLOBALS["STATUS"][$key]["FLAGGED"]=0;
	$GLOBALS["STATUS"][$key]["MEMBER"]=$MEMBER;
	$GLOBALS["STATUS"][$key]["RULE"]=$RuleID;
	$GLOBALS["STATUS"][$key]["CURRENT"]=$Bytes;
	$GLOBALS["STATUS"][$key]["PERC"]=$perc;
	$GLOBALS["STATUS"][$key]["zDate"]=date("Y-m-d H:i:s");
	
	if($GLOBALS["STATUS"][$key]["FREEZE"]==1){
		events("[INFO]: Member: $MEMBER - FREEZE - ($RuleName)",__LINE__);
		return false;}
	if($perc<99){return false;}
	$GLOBALS["NOTIF_TEXT"][]="$MEMBER Exceed {$QuotaSizeMB}MB current {$BytesMB}MB";
	$GLOBALS["STATUS"][$key]["FLAGGED"]=1;
	events("[INFO]: [$RuleName] Member: $MEMBER ".FormatBytes($Bytes/1024,true)."/".FormatBytes($QuotaSizeBytes/1024,true)."Container:".FormatBytes($Reste/1024,true)." ({$perc}%)",__LINE__);
	return true;
	
}




function ScanActiveDirectoryGroups($RuleID,$RuleName,$DNGroup,$basePath,$QuotaSizeBytes,$FileAcls){
	include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
	$f=new ActiveDirectory();
	if(($GLOBALS["VERBOSE"])){echo "Path: $basePath\n";}
	events("[INFO]: Scanning Active Directory group $DNGroup ($RuleName)",__LINE__);
	$USERS=$f->dump_users_from_group($DNGroup);
	$acls_content=array();
	$NOTIF_TEXT=array();
	$basePath=$basePath."/UID";
	$GLOBALS["NOTIF_TEXT"]=array();
	$FileAclsMD5_start=md5_file($FileAcls);
	
	while (list ($MEMBER, $ligne) = each ($USERS) ){
		
		if(Scan_member($RuleID,$RuleName,$MEMBER,$basePath,$QuotaSizeBytes,$FileAcls)){
			$acls_content[]=$MEMBER;
			
		}
	}
	
	@file_put_contents($FileAcls, @implode("\n", $acls_content));
	$FileAclsMD5_end=md5_file($FileAcls);
	
	if($FileAclsMD5_end<>$FileAclsMD5_start){
		squid_admin_mysql(1, "$RuleName: Quota changed", @implode("\n", $GLOBALS["NOTIF_TEXT"]),__FILE__,__LINE__);
		$GLOBALS["MUST_RELOAD_SQUID"]=true;
	}
	
	
}


function set_status(){
	
	
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("DROP TABLE bandquotas_status");
	
	$sql="CREATE TABLE IF NOT EXISTS `bandquotas_status` (
	`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	 PatternGroup VARCHAR(90) NOT NULL,
	zDate datetime NOT NULL,
	ruleid BIGINT(10) NOT NULL,
	size BIGINT UNSIGNED NOT NULL,
	percent smallint(2) NOT NULL DEFAULT 0,
	freeze smallint(2) NOT NULL DEFAULT 0,
	zflag smallint(1) NOT NULL DEFAULT 0,
	KEY `ruleid` (`ruleid`),
	KEY `size` (`size`),
	KEY `percent` (`percent`),
	KEY `zDate` (`zDate`),
	KEY `zflag` (`zflag`),
	KEY `freeze` (`freeze`)
	)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	
	$prefix="INSERT IGNORE INTO bandquotas_status (zDate,zflag,PatternGroup,ruleid,size,percent,freeze) VALUES ";
	$f=array();
	
	
	
	while (list ($key, $ligne) = each ($GLOBALS["STATUS"]) ){
		$MEMBER=$ligne["MEMBER"];
		$flag=$ligne["FLAGGED"];
		$ruleid=$ligne["RULE"];
		$Currentsize=$ligne["CURRENT"];
		$perc=$ligne["PERC"];
		$freeze=intval($ligne["FREEZE"]);
		$zDate=$ligne["zDate"];
		$zflag=$ligne["FLAGGED"];
		$f[]="('$zDate','$zflag','$MEMBER','$ruleid','$Currentsize','$perc','$freeze')";
	}
	

	if(count($f)>0){
		events("[INFO]: Analyze injecting ".count($f)." row(s)",__LINE__);
		$q->QUERY_SQL("$prefix ". @implode(",", $f));
		if(!$q->ok){events("[ERROR]: MySQL error ! $q->mysql_error",__LINE__);}
	}
	
	
}


function events($text,$line=0){
	$date=@date("H:i:s");
	$logFile="/var/log/squid/quotaband.debug";
	$size=@filesize($logFile);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	
	
	$line="$date:[$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);
}
function ImplodeAcls($array,$filename){
	if(count($array)==0){
		@unlink($filename);
		@touch($filename);
		return;
	}

	while (list ($MEMBER,$none) = each ($array) ){
		$f[]=$MEMBER;

	}
	@file_put_contents($filename, @implode("\n", $f));
}