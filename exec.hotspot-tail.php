<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pid=getmypid();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
@mkdir("/etc/artica-postfix/pids",0755,true);
$pid=@file_get_contents($pidfile);
$unix=new unix();
$GLOBALS["NOHUP_BIN"]=$unix->find_program("nohup");


events("Running $pid update $pidfile....");
file_put_contents($pidfile,$pid);

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

fclose($pipe);
events("Shutdown daemon...");
die();



function Parseline($buffer){
	
	if(strpos($buffer, "RSA server certificate CommonName")>0){return;}
	if(strpos($buffer, "[debug]")>0){return;}
	if(strpos($buffer, "[info]")>0){return;}
	if(strpos($buffer, "File does not exist")>0){return;}
	if(strpos($buffer, "client denied by server configuration")>0){return;}
//**************************************************************************************************************************	
	if(preg_match("#error.*?raising the MaxClients#", $buffer)){
		$fileStamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__.".time";
		$timefile=file_time_min($fileStamp);
		if($timefile>0){
			@unlink($fileStamp);
			@file_put_contents($fileStamp, time);
			$HotSpotMaxClients=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotMaxClients"));
			if($HotSpotMaxClients==0){$HotSpotMaxClients=20;}
			$HotSpotMaxClientsNew=$HotSpotMaxClients+2;
			@file_put_contents("/etc/artica-postfix/settings/Daemons/HotSpotMaxClients", $HotSpotMaxClientsNew);
			mysql_events(0,"MaxClients reached, upgrade from $HotSpotMaxClients daemons to  $HotSpotMaxClientsNew [action=restart]",$buffer);
			shell_exec("{$GLOBALS["NOHUP_BIN"]} /etc/init.d/artica-hotspot restart >/dev/null 2>&1 &");
		}
		return;
	}
//**************************************************************************************************************************	
if(preg_match("#you may need to increase StartServers#", $buffer)){
	$fileStamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__.".time";
	$timefile=file_time_min($fileStamp);
	if($timefile>0){
		@unlink($fileStamp);
		@file_put_contents($fileStamp, time);
		$HotSpotStartServers=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HotSpotStartServers"));
		if($HotSpotStartServers==0){$HotSpotStartServers=20;}
		$HotSpotStartServersNew=$HotSpotStartServers+2;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HotSpotStartServers", $HotSpotStartServersNew);
		mysql_events(0,"StartServers reached, upgrade from $HotSpotStartServers daemons to  $HotSpotStartServersNew [action=restart]",$buffer);
		}
		return;
}
//**************************************************************************************************************************	
	if(preg_match("#warn.*?Identifier removed: Failed to release SSL session cache lock#", $buffer)){
		$fileStamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__.".time";
		$timefile=file_time_min($fileStamp);
		if($timefile>0){
			@unlink($fileStamp);
			@file_put_contents($fileStamp, time);
			mysql_events(0,"SSL issue, overloaded system, reboot web service",$buffer);
			shell_exec("{$GLOBALS["NOHUP_BIN"]} /etc/init.d/artica-hotspot restart >/dev/null 2>&1 &");
		}
		return;
	}
//**************************************************************************************************************************
	if(preg_match("#error.*?Cannot allocate memory: fork: Unable to fork new process#", $buffer)){
		$fileStamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__.".time";
		$timefile=file_time_min($fileStamp);
		if($timefile>0){
			@unlink($fileStamp);
			@file_put_contents($fileStamp, time);
			mysql_events(0,"Cannot allocate memory, reboot web service",$buffer);
			shell_exec("{$GLOBALS["NOHUP_BIN"]} /etc/init.d/artica-hotspot restart >/dev/null 2>&1 &");
		}
		return;
	}	
	//**************************************************************************************************************************
if(preg_match("#Failed to release SSL session cache lock#", $buffer)){
		$fileStamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__.".time";
		$timefile=file_time_min($fileStamp);
		if($timefile>0){
			@unlink($fileStamp);
			@file_put_contents($fileStamp, time);
			mysql_events(0,"Failed to release SSL session cache lock, reboot web service",$buffer);
			shell_exec("{$GLOBALS["NOHUP_BIN"]} /etc/init.d/artica-hotspot restart >/dev/null 2>&1 &");
		}
		return;
	}	
//**************************************************************************************************************************	

	events("No match $buffer");
	
}



function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	$unix=new unix();
	$unix->events($text,"/var/log/artica-wifidog/watchdog.log",false,$sourcefunction,$sourceline);
}

function mysql_events($severity,$subject,$content){
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			}
		}
			
	
	$zdate=date("Y-m-d H:i:s");
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	$subject=mysql_escape_string2($subject);
	$content=mysql_escape_string2($content);
	$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
}


