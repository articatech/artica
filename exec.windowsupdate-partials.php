<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="HyperCache Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

if($argv[1]=="--partition"){DirectorySize();die();}
if($argv[1]=="--range"){DownloadByRange($argv[2]);}

$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
if($WindowsUpdateCaching==0){die();}


xstart();




function xstart(){
	$T1=time();
	$curl=new ccurl();
	$unix=new unix();
	$GLOBALS["MYPID"]=getmypid();

	$pidfile="/etc/artica-postfix/pids/windowupdate.partial.processor.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die(); }


	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__),true);
	if(count($pids)>0){
		while (list ($i, $line) = each ($pids)){
			events("Already executed PID:$i... aborting ",__LINE__);
		}
		die();
	}

	$TEMPDIR=$unix->TEMP_DIR()."/WindowsUpdates";
	$rm=$unix->find_program("rm");
	@file_put_contents($pidfile, $GLOBALS["MYPID"]);
	if(is_dir($TEMPDIR)){shell_exec("$rm -rf $TEMPDIR");@mkdir($TEMPDIR);}
	
	$GLOBALS["WindowsUpdateMaxToPartialQueue"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxToPartialQueue"));
	$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
	if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	
	$WindowsUpdateDownTimeout=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateDownTimeout"));
	if($WindowsUpdateDownTimeout==0){$WindowsUpdateDownTimeout=600;}
	$WindowsUpdateBandwidthMaxFailed=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateBandwidthMaxFailed"));
	if($WindowsUpdateBandwidthMaxFailed==0){$WindowsUpdateBandwidthMaxFailed=50;}
	$WindowsUpdateMaxPartition=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxPartition"));
	if($WindowsUpdateMaxPartition==0){$WindowsUpdateMaxPartition=80;}
	
	
	$fileSource="{$GLOBALS["WindowsUpdateCachingDir"]}/Partials/Queue.log";
	$LinesCount=$unix->COUNT_LINES_OF_FILE($fileSource);
	
	if(!is_file($fileSource)){return;}
	$md5start=md5_file($fileSource);
	
	
	$CheckPartitionPercentage=CheckPartitionPercentage();
	if($CheckPartitionPercentage>$WindowsUpdateMaxPartition){
		events("Failed: Storage Partition exceed {$WindowsUpdateMaxPartition}% Stopping retreivals",__LINE__);
		@touch("/etc/squid3/WindowsUpdatePartitionExceed");
		DirectorySize();
		return;
	}
	
	$WindowsUpdateInProduction=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateInProduction"));
	if($WindowsUpdateInProduction==0){
		if($unix->IsProductionTime()){
			$time=$unix->file_time_min("/etc/artica-postfix/pids/WindowsUpdateInProduction");
			if($time>15){
				@unlink("/etc/artica-postfix/pids/WindowsUpdateInProduction");
				@touch("/etc/artica-postfix/pids/WindowsUpdateInProduction");
				events("INFO: Aborting, No download during production time",__LINE__);
				DirectorySize();
			}
			return;
		}
	}
	
	if(is_file("/etc/squid3/WindowsUpdatePartitionExceed")){
		@unlink("/etc/squid3/WindowsUpdatePartitionExceed");
	}
	
	$handle = @fopen($fileSource, "r");
	if (!$handle) {events("Fopen failed on $fileSource",__LINE__);return false;}
	$NEWBUFFER=array();
	$URLALREADY=array();
	$NewBuffer=array();
	$FinalSize=0;
	$FF=0;
	$c=0;
	while (!feof($handle)){
		$buffer =trim(fgets($handle));
		$c++;
		if($buffer==null){continue;}
		$TR=explode("|||",$buffer);
		$LocalFile=$TR[0];
		$URI=$TR[1];
		$ExpectedSize=$TR[2];
		if(!isset($TR[3])){$TR[3]=1;}
		
		if(isset($URLALREADY[$URI])){continue;}
		$URLALREADY[$URI]=true;
		$BaseNameOfFile=basename($URI);
		events("INFO: $BaseNameOfFile ". xFormatBytes($ExpectedSize/1024)." Retry:{$TR[3]} [$c/$LinesCount]",__LINE__);
		
		if(DownloadByRange($URI,$ExpectedSize,$LocalFile)){
			update_mysql($LocalFile,$URI);
			continue;
		}
		
		$TR[3]=$TR[3]+1;
		
		if($TR[3]>$WindowsUpdateBandwidthMaxFailed){
			events("Error: Max retry ($WindowsUpdateBandwidthMaxFailed) for filename: $BaseNameOfFile",__LINE__);
			SaveToBlacklists($BaseNameOfFile);
			RemoveTempOf($URI,$LocalFile);
			continue;
			
		}
		$NewBuffer[]=@implode("|||", $TR);
	}
	
	if(count($NewBuffer)==0){@unlink($fileSource);return;}
	events("INFO: add ".count($NewBuffer)." orders to queue",__LINE__);
	@file_put_contents($fileSource, @implode("\n", $NewBuffer));
	DirectorySize();
}
function update_mysql($localpath,$zUri){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$size=@filesize($localpath);
	$filemd5=@md5_file($localpath);
	$date=date("Y-m-d H:i:s");
	$sql_insert="INSERT IGNORE INTO windowsupdate (filemd5,zDate,filesize,localpath,zUri)
	VALUES('$filemd5','$date','$size','$localpath','$zUri')";

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT localpath,filesize FROM windowsupdate WHERE filemd5='$filemd5'"));
	if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}

	if($ligne["localpath"]<>null){
		if(!is_file($ligne["localpath"])){
			$q->QUERY_SQL("DELETE FROM windowsupdate WHERE filemd5='$filemd5'");
			$q->QUERY_SQL($sql_insert);
			if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
			return;
		}

		$md5inMysql=md5_file($ligne["localpath"]);

		if($md5inMysql<>$filemd5){
			if($ligne["localpath"]<>$localpath){
				@unlink($ligne["localpath"]);
				shell_exec("$ln -sf $localpath {$ligne["localpath"]}");
			}
			$q->QUERY_SQL("DELETE FROM windowsupdate WHERE filemd5='$filemd5'");
			$q->QUERY_SQL($sql_insert);
			if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
			return;
		}
			
		if($md5inMysql==$filemd5){
			if($ligne["localpath"]<>$localpath){
				@unlink($localpath);
				shell_exec("$ln -sf {$ligne["localpath"]} $localpath");
				return;
			}

		}

		return;
	}
	$q->QUERY_SQL($sql_insert);
	if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}


}

function RemoveTempOf($URI,$LocalFile){
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$TempDir="{$GLOBALS["WindowsUpdateCachingDir"]}/Partials/".md5($URI);
	if(is_dir($TempDir)){
		events("INFO: Removing $TempDir",__LINE__);
		shell_exec("$rm -rf $TempDir");
	}
	
}

function SaveToBlacklists($filename){
	$logFile="/etc/squid3/WindowsUpdateBlacklists.db";
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$filename\n");
	@fclose($f);
	@chown("/etc/squid3/WindowsUpdateBlacklists.db","squid");
	@chmod("/etc/squid3/WindowsUpdateBlacklists.db",0755);
	@chgrp("/etc/squid3/WindowsUpdateBlacklists.db", "squid");
	
}

function DownloadByRange($URI,$EXPECTED_SIZE=0,$LocalFile=null){
	$HTTP_CODE=0;
	$Time=null;
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}

	$WindowsUpdateBandwidthPartial=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateBandwidthPartial"));
	$WindowsUpdateUseLocalProxy=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy"));
	$WindowsUpdateInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateInterface");
	
	
	
	if($WindowsUpdateBandwidthPartial==0){$WindowsUpdateBandwidthPartial=512;}
	

	if($EXPECTED_SIZE==0){$EXPECTED_SIZE=GetTargetedSize($URI);}
	$EXPECTED_SIZE_TEXT=xFormatBytes($EXPECTED_SIZE/1024);
	$BaseNameOfFile=basename($URI);
	
	$unix=new unix();
	$curl=$unix->find_program("curl");
	$rm=$unix->find_program("rm");
	$mv=$unix->find_program("mv");
	$TempDir="{$GLOBALS["WindowsUpdateCachingDir"]}/Partials/".md5($URI);
	$TempFile="$TempDir/FILE";
	
	
	if(is_file($TempFile)){
		$size=@filesize($TempFile);
		
	}
	
	
	
	
	@mkdir($TempDir,0755,true);
	
	$WorkingPort=$SquidMgrListenPort=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
	
	@unlink("$TempDir/stderr.txt");
	
	$f[]="$curl --location";
	if($WindowsUpdateUseLocalProxy==1){
		events("Downloading $BaseNameOfFile Proxy:127.0.0.1:{$WorkingPort}",__LINE__);
		$f[]="--proxy 127.0.0.1:{$WorkingPort} --url \"$URI\"";
	}
	
	if($WindowsUpdateInterface<>null){
		$INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$ipaddr=$INTERFACES[$WindowsUpdateInterface]["IPADDR"];
		if($ipaddr=="0.0.0.0"){$ipaddr=null;}
		if($ipaddr<>null){
			events("Downloading $BaseNameOfFile Interface:$ipaddr",__LINE__);
			$f[]="--interface $ipaddr";
		}
	}
	
	$f[]="--show-error --write-out \"RRRR:%{http_code} TTT:%{time_total}\"";
	$f[]="--stderr $TempDir/stderr.txt";
	$f[]="--output \"$TempFile\"";
	$f[]="--continue-at -";
	$f[]="--limit-rate {$WindowsUpdateBandwidthPartial}K";
	$f[]="--url \"$URI\"";
	$f[]="2>&1";
	$cmd=@implode(" ", $f);
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	$T1=time();
	events("Downloading $BaseNameOfFile $EXPECTED_SIZE_TEXT limit:{$WindowsUpdateBandwidthPartial}Kb/s",__LINE__);
	exec($cmd,$results);
	$stderr=trim(@file_get_contents("$TempDir/stderr.txt"));
	if($stderr<>null){
		while (list($num,$val)=each($results)){
			if(preg_match("#RRRR:(.+?)\s+TTT:(.+)#", $val,$re)){
				$HTTP_CODE=$re[1];
				$Time=$re[2];
				continue;
			}
			events("INFO: $val",__LINE__);
		}
		if(intval($HTTP_CODE)<>200){
			
			if(preg_match("#Resuming transfer from byte position\s+([0-9]+)#", $stderr,$re)){
				events("Downloading $BaseNameOfFile stopped duration:$Time HTTP Code:$HTTP_CODE and resuming position at : {$re[1]} (".xFormatBytes($re[1]/1024).")",__LINE__);
				return false;
			}
			
			events("Failed: duration:$Time $BaseNameOfFile with error $HTTP_CODE &laquo;$stderr&raquo;",__LINE__);
			return false;
		}
	}
	
	$size=@filesize($TempFile);
	$size_text=xFormatBytes($size);
	
	events("INFO: $BaseNameOfFile ($size_text) HTTP Code:$HTTP_CODE Duration:$Time",__LINE__);
	
	if($size<$EXPECTED_SIZE){
		events("Warning: $BaseNameOfFile $size is not $EXPECTED_SIZE (broken download) retry next time",__LINE__);
		return false;
	}
	events("INFO: $BaseNameOfFile move to $LocalFile",__LINE__);
	@mkdir(dirname($LocalFile));
	
	$cmd = "$mv \"$TempFile\" \"$LocalFile\"";
	exec($cmd, $output, $return_val);
	
	if ($return_val == 0) {
		events("Success: Retranslate $BaseNameOfFile to target directory",__LINE__);
		if(is_dir($TempDir)){shell_exec("$rm -rf $TempDir");}
		return true;
	} else {
		events("Failed: $BaseNameOfFile, unable to move to target directory!",__LINE__);
		return false;
	}
}
function GetTargetedSize($URI){
	$ExpectedSize=0;
	$curl=new ccurl($URI);
	$curl->NoLocalProxy();
	$Headers=$curl->getHeaders();
	if(isset($Headers["Content-Length"])){return $Headers["Content-Length"];}
	if($ExpectedSize==0){if(isset($Headers["download_content_length"])){ return $Headers["download_content_length"]; }}
	return 0;
}

function events($text,$line=0){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$date=@date("H:i:s");
	$logFile="/var/log/squid/windowsupdate.debug";
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	$line="$date:[BigRetriever/$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


}
function xFormatBytes($kbytes,$nohtml=false){

	$spacer=null;


	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2);
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}

function CheckPartitionPercentage(){
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();
	$partition=$unix->DIRPART_INFO($dir);
	return $partition["POURC"];
	
}

function DirectorySize(){

	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}


	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();

	$time=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	if($time<120){return;}

	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);
	
	print_r($partition);

	$TOT=$partition["TOT"];
	$AIV=$partition["AIV"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);


	events("INFO: Storage $size Partition $TOT",__LINE__);

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	$ARRAY["AIV"]=$AIV;

	@unlink("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state", serialize($ARRAY));

}