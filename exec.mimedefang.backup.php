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
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.backup.php.start.time";
		if($unix->file_time_min($pidtime)<5){return;}
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
	}
	
	$postgres=new postgres_sql();
	$postgres->SMTP_TABLES();
	$storage_path="/var/spool/MIMEDefang/BACKUP";
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){system_admin_events("Already process $pid running.. Aborting",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	@file_put_contents($pidpath,getmypid());
	$c=0;
	if ($handle = opendir($storage_path)) {	
		while (false !== ($file = readdir($handle))) {
			if ($file == "." && $file == "..") {continue;}
			if(substr($file, 0,1)=='.'){if($GLOBALS["VERBOSE"]){echo "skipped: `$file`\n";}continue;}
			if(!preg_match("#.email$#", $file)){continue;}
			$path="$storage_path/$file";
			if(import_backup_file($path)){$c++;}
				
		}
	}
	
	CleanDatabase();
	
}

function import_backup_file($filepath){
	if($GLOBALS["VERBOSE"]){echo "Import $filepath\n";}
	
	$dirname=dirname($filepath);
	$filename=basename($filepath);
	$filecontent=$dirname."/".str_replace(".email", ".msg", $filename);
	if(!is_file($filecontent)){
		echo "$filecontent no such file\n";
		@unlink($filepath);
		return true;
	}
	$last_modified = filemtime($filepath);
	
	//$FinalLog="$Subject|||$Sender|||$recipt|||$body_hash|||$body_length||$rententiontime";
	
	$F=explode("|||",@file_get_contents($filepath));
	print_r($F);
	if(count($F)<5){
		echo "Truncated file index : $filepath !\n";
		return false;
		
	}
	
	$q=new postgres_sql();
	$zdate=date("Y-m-d H:i:s",$last_modified);
	$subject=str_replace("'", "`", $F[0]);
	$mailfrom=$F[1];
	$mailfrom=str_replace("<", "", $mailfrom);
	$mailfrom=str_replace(">", "", $mailfrom);
	
	$mailfromz=explode("@",$mailfrom);
	$domainfrom=$mailfromz[1];
	$mailto_line=$F[2];
	$hash=$F[3];
	$retentiontime=$F[5];
	$filesize=@filesize($filecontent);
	$msgmd5=md5_file($filecontent);
	$final=strtotime("+{$retentiontime} minutes",$last_modified);

	$prefix="INSERT INTO backupmsg (zdate,final,msgmd5,size,subject,mailfrom,mailto,domainfrom,domainto ) VALUES ";
	
	$mailsTo_array=explode(";",$mailto_line);
	
	$f=array();
	while (list ($a, $mailto) = each ($mailsTo_array)){
		$mailto=trim(strtolower($mailto));
		$mailto=str_replace("<", "", $mailto);
		$mailto=str_replace(">", "", $mailto);
		if($mailto==null){continue;}
		$mailtoz=explode("@",$mailto);
		$domainto=$mailtoz[1];
		$f[]="('$zdate','$final','$msgmd5','$filesize','$subject','$mailfrom','$mailto','$domainfrom','$domainto')";
		
	}
	
	if(count($f)==0){
		echo "No... count(f)=0\n";
		@unlink($filepath);
		@unlink($filecontent);
		return false;
		
	}
	
	
	$final_sql=$prefix." ".@implode(",", $f);
	$q->QUERY_SQL($final_sql);
	if(!$q->ok){
		echo $q->mysql_error."\n$final_sql\n";
		echo "No... PostgreSQL error\n";
		return false;
	}
	
	$filecontent_gz="$filecontent.gz";
	$unix=new unix();
	if(!$unix->compress($filecontent, $filecontent_gz)){
		@unlink($filecontent_gz);
		echo "No... Compress error\n";
		return;
	}
	
	@chmod($filecontent_gz,0777);
	
	$q->QUERY_SQL("INSERT INTO backupdata (zdate,msgmd5,final,contentid) VALUES ('$zdate','$msgmd5','$final',lo_import('$filecontent_gz') ) ON CONFLICT DO NOTHING");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		echo "No... PostgreSQL error\n";
		return false;
	}
	
	
	$unix->ToSyslog("from=<$mailfrom> [$subject] $filepath success to backup");
	echo "$filepath (success)\n$filecontent (success)\n";
	
	@unlink($filepath);
	@unlink($filecontent);	
	@unlink($filecontent_gz);
	return true;
}

function CleanDatabase(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidtime)<120){return;}
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM backupmsg WHERE final < ".time());
	
	$results=$q->QUERY_SQL("SELECT msgmd5,contentid FROM backupdata WHERE final < ".time());
	while($ligne=@pg_fetch_assoc($results)){
		$msgmd5=$ligne["msgmd5"];
		$contentid=$ligne["contentid"];
		if($contentid>0){
			$q->QUERY_SQL("select lo_unlink($contentid)");
		}
		$q->QUERY_SQL("DELETE FROM backupdata WHERE msgmd5='$msgmd5'");
		
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
}
