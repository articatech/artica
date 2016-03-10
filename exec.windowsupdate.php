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

if($argv[1]=="--build"){build();die();}
if($argv[1]=="--partition"){DirectorySize(true);die();}
if($argv[1]=="--whitelist"){whitelist();die();}
if($argv[1]=="--delete"){delete($argv[2]);die();}


$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
if($WindowsUpdateCaching==0){die();}

xstart();

function xstart(){
	$T1=time();
	$curl=new ccurl();
	$unix=new unix();
	$GLOBALS["MYPID"]=getmypid();

	$pidfile="/etc/artica-postfix/pids/windowupdate.processor.pid";
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
	if(is_dir($TEMPDIR)){
		shell_exec("$rm -rf $TEMPDIR");
		
	}
	@mkdir($TEMPDIR,0755,true);
	$WindowsUpdateMaxPartition=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxPartition"));
	
	
	if($WindowsUpdateMaxPartition==0){$WindowsUpdateMaxPartition=80;}
	
	$CheckPartitionPercentage=CheckPartitionPercentage();
	if($CheckPartitionPercentage>$WindowsUpdateMaxPartition){
		$time=$unix->file_time_min("/etc/squid3/WindowsUpdatePartitionExceed");
		if($time>10){
			@unlink("/etc/squid3/WindowsUpdatePartitionExceed");
			events("Failed: Storage Partition exceed {$WindowsUpdateMaxPartition}%, Stopping retreivals",__LINE__);
			@touch("/etc/squid3/WindowsUpdatePartitionExceed");
			DirectorySize();
		}
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
	
	
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `windowsupdate` (
			`filemd5` VARCHAR( 90 ) NOT NULL ,
			`zDate` DATETIME NOT NULL ,
			`zUri` VARCHAR( 255 ) NOT NULL ,
			`localpath` VARCHAR( 255 ) NOT NULL ,
			`filesize` BIGINT UNSIGNED DEFAULT '0',
			 INDEX ( `filesize` ,`zDate`) ,
			 KEY `localpath`(`localpath`),
			 KEY `zUri`(`zUri`),
			 PRIMARY KEY (`filemd5`)) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("MySQL Failed $q->mysql_error",__LINE__);
		die();
	}
	
	$GLOBALS["WindowsUpdateMaxToPartialQueue"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxToPartialQueue"));
	if($GLOBALS["WindowsUpdateMaxToPartialQueue"]==0){$GLOBALS["WindowsUpdateMaxToPartialQueue"]=350;}
	
	$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
	if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	$filepath="{$GLOBALS["WindowsUpdateCachingDir"]}/Queue.log";
	$WindowsUpdateDownTimeout=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateDownTimeout"));
	if($WindowsUpdateDownTimeout==0){$WindowsUpdateDownTimeout=600;}
	
	
	$WindowsUpdateMaxToPartialQueue=$GLOBALS["WindowsUpdateMaxToPartialQueue"]*1000;
	$WindowsUpdateMaxToPartialQueue=$WindowsUpdateMaxToPartialQueue*1000;
	
	
	$LinesCount=$unix->COUNT_LINES_OF_FILE($filepath);
	
	if(!is_file($filepath)){return;}
	$md5start=md5_file($filepath);
	
	$handle = @fopen($filepath, "r");
	if (!$handle) {events("Fopen failed on $filepath",__LINE__);return false;}
	$NEWBUFFER=array();
	$URLALREADY=array();
	$FinalSize=0;
	$FF=0;
	$c=0;
	while (!feof($handle)){
		$buffer =trim(fgets($handle));
		$c++;
		if($buffer==null){continue;}
		$TR=explode("|||",$buffer);
		
		$prc=$c/$LinesCount;
		$prc=round($prc*100);
		
		
		$LocalFile=$TR[0];
		$URI=$TR[1];
		$ExpectedSize=0;
		
		if(strpos($URI, $GLOBALS["WindowsUpdateCachingDir"])>0){
			events("FOUND! directory in  URI",__LINE__);
			$TTR=explode($GLOBALS["WindowsUpdateCachingDir"],$URI);
			$URI=$TTR[0];
			$LocalFile="{$GLOBALS["WindowsUpdateCachingDir"]}{$TTR[1]}";
			events("FOUND! URI:$URI",__LINE__);
			events("FOUND! NEXT:$LocalFile",__LINE__);
		}
		
		$BASENAMELL=basename($LocalFile);
		if(strlen($BASENAMELL)>20){$BASENAMELL=substr($BASENAMELL, 0,17)."...";}
		build_progressG("$BASENAMELL $c/$LinesCount {files}",$prc);
		if(isset($URLALREADY[$URI])){continue;}
		$URLALREADY[$URI]=true;
		
		
		if(isBlacklisted($URI)){
			events(basename($URI)." blacklisted...");
			continue;
		}
		
		if(is_file($LocalFile)){
			$size=@filesize($LocalFile);
			if($size>5){
				events("SKIP ".basename($LocalFile)." ".xFormatBytes($size/1024),__LINE__);
				update_mysql($LocalFile,$URI);
				continue;
			}else{
				@unlink($LocalFile);
			}
		}
		
		
		$dirname=dirname($LocalFile);
		if(!is_dir($dirname)){@mkdir($dirname,true,0755);}
		$curl=new ccurl($URI);
		$Headers=$curl->getHeaders();
		
		$TIMEDOWN=time();
		$TMPFILE="$TEMPDIR/".basename($LocalFile);
		$GLOBALS["previousProgress"]=0;
		$GLOBALS["DOWNLOADED_FILE"]=basename($LocalFile);
		$GLOBALS["TMPFILE"]=$TMPFILE;
		
		$ExpectedSize=GetTargetedSize($URI);
		if($ExpectedSize==0){
			events("Failed to download $URI ( unable to get expected size)",__LINE__);
			continue;
		}
		
		if($ExpectedSize>$WindowsUpdateMaxToPartialQueue){
			$ExpectedSizeText=xFormatBytes($ExpectedSize/1024,true);
			events(basename($URI)." ($ExpectedSizeText $ExpectedSize/$WindowsUpdateMaxToPartialQueue) Limit $WindowsUpdateMaxToPartialQueue to BigFiles queue",__LINE__);
			AddToPartialQueue($URI,$ExpectedSize,$LocalFile);
			continue;
		}
		
		$curl=new ccurl($URI);
		$curl->WriteProgress=true;
		$curl->Timeout=$WindowsUpdateDownTimeout*60;
		$curl->ProgressFunction="xdownload_progress";
		
		events("Downloading ".basename($URI)." to $TMPFILE (".xFormatBytes($ExpectedSize/1024,true)." max:{$WindowsUpdateDownTimeout} Minutes)",__LINE__);
		
		if(!$curl->GetFile($TMPFILE)){
			events("Failed: TMP: &laquo;$TMPFILE&raquo;",__LINE__);
			events("Failed: URL: &laquo;$URI&raquo;",__LINE__);
			events("Failed: After: ".$unix->distanceOfTimeInWords($TIMEDOWN,time(),true),__LINE__);
			events("Failed: With error: $curl->error http code: $curl->CURLINFO_HTTP_CODE (".count($curl->CURL_ALL_INFOS).") infos",__LINE__);
			
			reset($curl->CURL_ALL_INFOS);
			while (list ($index, $value) = each ($curl->CURL_ALL_INFOS)){
				events("Failed: &laquo;$index&raquo; [$value]",__LINE__);}
			
			if($curl->CURLINFO_HTTP_CODE==404){continue;}
			@unlink($TMPFILE);
			$NEWBUFFER[]="$buffer";
			continue;
		}
		
		if(!is_file($TMPFILE)){
			events("Fatal $TMPFILE: no such file",__LINE__);
			continue;
		}
		
		$size=filesize($TMPFILE);
		$sizeT=xFormatBytes($size/1024);
		
		if($size < 5){
			@unlink($TMPFILE);
			events("Failed: File less than 5 Bytes ($size), aborting",__LINE__);
			continue;
		}
		
		if($ExpectedSize>0){
			if($size<>$ExpectedSize){
				$ExpectedSizeT=xFormatBytes($ExpectedSize/1024);
				events("Failed: corrupted download ".basename($URI)." expected size $ExpectedSizeT/$ExpectedSize current:($sizeT/$size)",__LINE__);
				@unlink($TMPFILE);
				continue;
			}
		}
		
		if(!@copy($TMPFILE, $LocalFile)){
			@unlink($TMPFILE);
			events("Failed: Translating to $LocalFile",__LINE__);
			$NEWBUFFER[]="$buffer";
			continue;
		}
		events("Success: ".basename($TMPFILE)." ($sizeT)",__LINE__);
		
		
		@unlink($TMPFILE);
		$FF++;
		$size=@filesize($LocalFile);
		$FinalSize=$FinalSize+$size;
		update_mysql($LocalFile,$URI);
		
	}
	
	$took=$unix->distanceOfTimeInWords($T1,time(),true);
	
	if($FinalSize>0){
		
		$CURLINFO_SPEED_DOWNLOAD=$curl->CURL_ALL_INFOS["CURLINFO_SPEED_DOWNLOAD"];
		events("Downloaded $FF files for ".xFormatBytes($FinalSize/1024,true)." ($CURLINFO_SPEED_DOWNLOAD) took: $took",__LINE__);
		
	}
	
	$md5finish=md5_file($filepath);
	if(count($NEWBUFFER)>0){
		events("Retry ". count($NEWBUFFER)." requests next time...",__LINE__);
		@file_put_contents($filepath, @implode("\n", $NEWBUFFER));
	}else{
		events("No new file downloaded....",__LINE__);
		events("Removing queue $filepath",__LINE__);
		@unlink($filepath);
		
	}
	DirectorySize();
	events("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; * * * END TOOK: $took * * *",__LINE__);
	
}

function isBlacklisted($URL){


	$NOTNESSCAB[]="disallowedcertstl";
	$NOTNESSCAB[]="pinrulesstl";
	$NOTNESSCAB[]="wsus3setup";
	$NOTNESSCAB[]="authrootstl";
	if(preg_match("#(".@implode("|", $NOTNESSCAB).")\.cab#", $URL)){return true;}
	if(preg_match("#WUClient-SelfUpdate#",$URL)){return true;}
	return false;


}

function AddToPartialQueue($URI,$ExpectedSize,$LocalFile){
	@mkdir("{$GLOBALS["WindowsUpdateCachingDir"]}/Partials",0755,true);
	$logFile="{$GLOBALS["WindowsUpdateCachingDir"]}/Partials/Queue.log";
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$LocalFile|||$URI|||$ExpectedSize\n");
	@fclose($f);
}

function GetTargetedSize($URI){
	$ExpectedSize=0;
	$curl=new ccurl($URI);
	$curl->FollowLocation=true;
	$Headers=$curl->getHeaders();
	
	if(isset($Headers["Content-Length"])){return $Headers["Content-Length"];}
	if($ExpectedSize==0){if(isset($Headers["download_content_length"])){ return $Headers["download_content_length"]; }}
	

	while (list ($index, $value) = each ($Headers)){
		events("Failed $index $value",__LINE__);
	}
	
	return 0;
	
}

function events($text,$line=0){
	$date=@date("H:i:s");
	$logFile="/var/log/squid/windowsupdate.debug";
	$size=@filesize($logFile);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	$line="$date:[Retriever/$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


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

function xdownload_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	$KDOWN=xFormatBytes($downloaded_size/1024);
	$KTOT=xFormatBytes($download_size/1024);
	
	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		if($GLOBALS["VERBOSE"]){echo xFormatBytes($downloaded_size/1024)."/". xFormatBytes($download_size/1024)."\n";}
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
		if($WindowsUpdateCaching==0){
			events("{$GLOBALS["DOWNLOADED_FILE"]}: Feature disabled, aborting..",__LINE__);
			@unlink($GLOBALS["TMPFILE"]);
			build_progress(0,"{failed} {$GLOBALS["DOWNLOADED_FILE"]} $progress $KDOWN/$KTOT");
			die();
		}
		build_progress($progress,"{downloading} $KDOWN/$KTOT");
		events("Downloading {$GLOBALS["DOWNLOADED_FILE"]}: $KDOWN/$KTOT {$progress}%",__LINE__);
		$GLOBALS["previousProgress"]=$progress;
			
	}
}

function build_progressG($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdateG.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);



}

function build_progress($pourc,$text){
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	


}
function build_progress_build($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "build_progress_build:: {$pourc}% $text\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress");
	file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress",0755);



}

function DirectorySize($force=false){

	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}

	
	$WindowsUpdateMaxRetentionTime=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxRetentionTime"));

	build_progress_whitelist("{scanning}",50);
	echo "Scanning: {$GLOBALS["WindowsUpdateCachingDir"]}\n";
	
	
	if($WindowsUpdateMaxRetentionTime>0){
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT `localpath` FROM windowsupdate WHERE zDate<DATE_SUB(NOW(),INTERVAL {$WindowsUpdateMaxRetentionTime} DAY);");
		while ($ligne = mysql_fetch_assoc($results)) {
			$path=$ligne["localpath"];
			delete($path);
		}
	}
	
	
	
	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();

	if(!$force){
		$time=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
		if(!$GLOBALS["VERBOSE"]){if($time<120){return;}}
	}

	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);


	$TOT=$partition["TOT"];
	$AIV=$partition["AIV"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);

	build_progress_whitelist("Storage {$percent}% $size Partition ".xFormatBytes($TOT/1024),70);
	events("INFO: Storage $size Partition $TOT",__LINE__);

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	$ARRAY["AIV"]=$AIV;

	@unlink("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state", serialize($ARRAY));
	build_progress_whitelist("{scanning} {success}",100);

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

function whitelist(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$f=array();
	build_progress_whitelist("{starting}",15);
	
	$sql="SELECT * FROM windowsupdates_white WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress_whitelist("{failed} MySQL Error",110);
		return;
	}
	build_progress_whitelist("{building}",50);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne['ipsrc']=trim($ligne['ipsrc']);
		if($ligne['ipsrc']==null){continue;}
		echo $ligne['ipsrc']."\n";
		$f[]=$ligne['ipsrc'];
		
	}
	
	
	
	@unlink("/etc/squid3/windowsupdate.whitelist.db");
	if(count($f)>0){
		@file_put_contents("/etc/squid3/windowsupdate.whitelist.db", @implode("\n", $f));
		build_progress_whitelist("{reloading}",80);
		@chown("/etc/squid3/windowsupdate.whitelist.db","squid");
		@chmod("/etc/squid3/windowsupdate.whitelist.db",0755);
	}
	
	build_progress_whitelist("{done}",100);
}

function build_progress_whitelist($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate.whitelist.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function build(){
	
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
	if($WindowsUpdateCaching==1){
		build_progress_build("{building} {enabled}",10);
		build_ufdb();
		build_apache_ON();
		
	}else{
		build_progress_build("{building} {disabled}",10);
		build_apache_OFF();
	}
	DirectorySize(true);
	build_progress_build("{building} {success}",100);
}

function build_ufdb(){
	
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==1){
		build_progress_build("{building} {webfiltering} {enabled} OK",12);
		
	}else{
		build_progress_build("{building} {webfiltering} {activate} OK",12);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
	}
	
	if(!build_IsInSquid()){
		build_progress_build("{building} {reconfigure_proxy_service}...",14);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress_build("{building} {reconfigure_proxy_service} {done}",16);
	}
	
	build_progress_build("{building} {webfiltering} {done}",18);
	
}

function build_apache_ON(){
	
	$file="/etc/apache2/conf.d/WindowsUpdate";
	
	$f[]="<IfModule mod_alias.c>";
    $f[]="\tAlias /WindowsUpdateProxyCache/ \"{$GLOBALS["WindowsUpdateCachingDir"]}/\"";
    $f[]="\t<Directory \"{$GLOBALS["WindowsUpdateCachingDir"]}/\">";
    $f[]="\t\tAllowOverride None";
    $f[]="\t\tOrder allow,deny";
    $f[]="\t\tAllow from all";
   	$f[]="\t</Directory>";
	$f[]="</IfModule>";
	$f[]="";
	
	@file_put_contents("/etc/apache2/conf.d/WindowsUpdate", @implode("\n", $f));
	@chmod("/etc/apache2/conf.d/WindowsUpdate",0755);
	build_progress_build("{building} {webfiltering} {done}",18);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb", 1);
	build_progress_build("{building} {restarting} {webservice}",22);
	system("/etc/init.d/apache2 restart");
	build_progress_build("{building} {restarting} {webservice} {done}",24);
}
function build_apache_OFF(){

	if(!is_file("/etc/apache2/conf.d/WindowsUpdate")){return;}
	@unlink("/etc/apache2/conf.d/WindowsUpdate");
	build_progress_build("{building} {restarting} {webservice}",22);
	system("/etc/init.d/apache2 restart");
	build_progress_build("{building} {restarting} {webservice} {done}",24);
}

function build_IsInSquid(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list($num,$val)=each($f)){
		if(preg_match("#url_rewrite_program.*?\/ufdbgclient\.php#", $val)){return true;}
		
	}
}

function delete($path){
	
	if(is_file($path)){
		$size=@filesize($path);
		events("INFO: Remove $path (".xFormatBytes($size/1024).")",__LINE__);
		@unlink($path);
	}
	
	$sql="DELETE FROM windowsupdate WHERE `localpath`='$path'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("FATAL: MySQL error $q->mysql_error",__LINE__);
		return;
	}
	events("INFO: Remove $path fro MySQL Done",__LINE__);
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