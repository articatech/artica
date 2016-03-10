<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include(dirname(__FILE__).'/ressources/class.amavis.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	
start();


function start(){
	
	$sock=new sockets();
	$unix=new unix();
	
	if(!$GLOBALS["VERBOSE"]){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.quarantine.php.start.time";
		if($unix->file_time_min($pidtime)<5){return;}
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
	}
	
	$postgres=new postgres_sql();
	$postgres->SMTP_TABLES();
	$storage_path="/var/spool/MD-Quarantine";
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){system_admin_events("Already process $pid running.. Aborting",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	@file_put_contents($pidpath,getmypid());
	$c=0;
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	if ($handle = opendir($storage_path)) {	
		while (false !== ($file = readdir($handle))) {
			if ($file == "." && $file == "..") {continue;}
			if(substr($file, 0,1)=='.'){continue;}
			if(!preg_match("#^qdir-#", $file)){continue;}
			$path="$storage_path/$file";
			if(!is_file("$path/ENTIRE_MESSAGE")){continue;}
			import_quarantine($path);
				
		}
	}
	
	CleanDatabase();
	
}

function import_quarantine($directory){
	if(!is_file("$directory/ENTIRE_MESSAGE")){
		if($GLOBALS["VERBOSE"]){echo "$directory/ENTIRE_MESSAGE no such file\n";}
		return;
	}
	if($GLOBALS["VERBOSE"]){echo "Scanning directory $directory\n";}
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$msgmd5=md5_file("$directory/ENTIRE_MESSAGE");
	$last_modified = filemtime("$directory/ENTIRE_MESSAGE");
	$filesize=@filesize("$directory/ENTIRE_MESSAGE");
	
	$zdate=date("Y-m-d H:i:s",$last_modified);
	if($GLOBALS["VERBOSE"]){echo "Message MD5....: $msgmd5\n";}
	if($GLOBALS["VERBOSE"]){echo "Message Date...: $last_modified ($zdate)\n";}
	if($GLOBALS["VERBOSE"]){echo "Size...........: $filesize\n";}
	
	$MimeDefangMaxQuartime=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangMaxQuartime"));
	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	if($GLOBALS["VERBOSE"]){echo "Retention time.: {$MimeDefangMaxQuartime}Mn\n";}

	$f=explode("\n",@file_get_contents("$directory/HEADERS"));
	
	while (list ($index, $line) = each ($f)){
		if(preg_match("#Subject:\s+(.*)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Subject........: {$re[1]}\n";}
			$Subject=$re[1];
		}
		if(preg_match("#From:\s+(.*)#i", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "From...........: {$re[1]}\n";}
			$FromHeader=$re[1];
			$FromHeader=str_replace("<", "", $FromHeader);
			$FromHeader=str_replace(">", "", $FromHeader);
			$FromHeader=trim($FromHeader);
			if(preg_match("#(.*?)\s+#", $FromHeader,$re)){$FromHeader=$re[1];}
		}
	}
	
	$mailsTo_array=array();
	$f=explode("\n",@file_get_contents("$directory/RECIPIENTS"));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		$line=str_replace("<", "", $line);
		$line=str_replace(">", "", $line);
		if(strpos($line, "@")==0){continue;}
		if($GLOBALS["VERBOSE"]){echo "Recipient......: {$line}\n";}
		$mailsTo_array[$line]=$line;
	}
	
	$mailfrom=trim(@file_get_contents("$directory/SENDER"));
	if($GLOBALS["VERBOSE"]){echo "Sender.........: {$mailfrom}\n";}
	if($mailfrom==null){$mailfrom=$FromHeader;}
	$mailfrom=str_replace("<", "", $mailfrom);
	$mailfrom=str_replace(">", "", $mailfrom);

	$q=new postgres_sql();
	
	$Subject=str_replace("'", "`", $Subject);
	$mailfromz=explode("@",$mailfrom);
	$domainfrom=$mailfromz[1];
	$final=strtotime("+{$MimeDefangMaxQuartime} minutes",$last_modified);
	$prefix="INSERT INTO quarmsg (zdate,final,msgmd5,size,subject,mailfrom,mailto,domainfrom,domainto ) VALUES ";
	
	
	
	$f=array();
	while (list ($a, $mailto) = each ($mailsTo_array)){
		$mailto=trim(strtolower($mailto));
		if($mailto==null){continue;}
		$mailtoz=explode("@",$mailto);
		$domainto=$mailtoz[1];
		$f[]="('$zdate','$final','$msgmd5','$filesize','$Subject','$mailfrom','$mailto','$domainfrom','$domainto')";
		
	}
	
	if(count($f)==0){
		echo "No... count(f)=0\n";
		shell_exec("$rm -rf \"$directory\"");
		return false;
	}
	
	
	$final_sql=$prefix." ".@implode(",", $f);
	$q->QUERY_SQL($final_sql);
	if(!$q->ok){
		echo $q->mysql_error."\n$final_sql\n";
		echo "No... PostgreSQL error\n";
		return false;
	}
	
	$filecontent_gz=$unix->FILE_TEMP().".gz";
	$unix=new unix();
	if(!$unix->compress("$directory/ENTIRE_MESSAGE", $filecontent_gz)){
		@unlink($filecontent_gz);
		echo "No... Compress error\n";
		return;
	}
	
	@chmod($filecontent_gz,0777);
	
	$q->QUERY_SQL("INSERT INTO quardata (zdate,msgmd5,final,contentid) VALUES ('$zdate','$msgmd5','$final',lo_import('$filecontent_gz') ) ON CONFLICT DO NOTHING");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		echo "No... PostgreSQL error\n";
		return false;
	}
	
	@unlink($filecontent_gz);
	$unix->ToSyslog("from=<$mailfrom> [$Subject] $directory/ENTIRE_MESSAGE success to Quarantine");
	echo "$directory/ENTIRE_MESSAGE (success)\n";
	shell_exec("$rm -rf \"$directory\"");
	return true;
}

function CleanDatabase(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidtime)<120){return;}
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM quarmsg WHERE final < ".time());
	
	$results=$q->QUERY_SQL("SELECT msgmd5,contentid FROM quardata WHERE final < ".time());
	while($ligne=@pg_fetch_assoc($results)){
		$msgmd5=$ligne["msgmd5"];
		$contentid=$ligne["contentid"];
		if($contentid>0){
			$q->QUERY_SQL("select lo_unlink($contentid)");
		}
		$q->QUERY_SQL("DELETE FROM quardata WHERE msgmd5='$msgmd5'");
		
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
}
