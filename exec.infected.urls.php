<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.categorize.generic.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["PREFIX"]="INSERT IGNORE INTO categoryuris_malware (`zmd5`,`zDate`,`pattern`,`enabled`) VALUES ";


if($argv[1]=="--cleanmx"){clean_mx_de($argv[2]);die();}




xstart();


function xstart(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	
	$TimeExec=$unix->file_time_min($pidtime);
	if($TimeExec<360){return;}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$q=new mysql_squid_builder();
	$q->CreateCategoryUrisTable("malware");
	
	$COUNT1=$q->COUNT_ROWS("categoryuris_malware");
	vxvault();
	malwareurls_joxeankoret();
	clean_mx_de();
	$COUNT2=$q->COUNT_ROWS("categoryuris_malware");
	
	$URLS_ADDED=$COUNT2-$COUNT1;
	if($URLS_ADDED>0){
		system("$php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category malware");
		squid_admin_mysql(2, "$URLS_ADDED malware URLs added", null,__FILE__,__LINE__);
	}
	
}

function malwareurls_joxeankoret(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$curl=new ccurl("http://malwareurls.joxeankoret.com/normal.txt");
	$targetpath=$unix->FILE_TEMP();
	
	
	if(!$curl->GetFile($targetpath)){
		if($GLOBALS["VERBOSE"]){echo "DOWNLOAD FAILED $targetpath\n";}
		@unlink($targetpath);
		return false;
	}
	
	$lastmd5=@file_get_contents("/etc/artica-postfix/settings/Daemons/malwareurls_joxeankoret.md5");
	$Currentmd5=md5_file($targetpath);
	if(!$GLOBALS["FORCE"]){if($lastmd5==$Currentmd5){return;}}
	
	
	if($GLOBALS["VERBOSE"]){echo "Open $targetpath\n";}
	$fp = @fopen($targetpath, "r");
	if(!$fp){
		if($GLOBALS["DEBUG_GREP"]){echo "$targetpath BAD FD\n";}
		@unlink($targetpath);
		return array();
	}
	
	$c=0;
	$t=array();
	while(!feof($fp)){
		$line = trim(fgets($fp));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=str_replace("\\", "/", $line);
		if(!preg_match("#^http#", $line)){if($GLOBALS["VERBOSE"]){echo "SKIP $line\n";}continue;}
		
		if(preg_match("#http:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}
		if(preg_match("#https:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}
		
		$md5=md5($line);
		$SQLZ[]="('$md5',NOW(),'$line',1)";
		if($GLOBALS["VERBOSE"]){echo "ADD $line\n";}
		if(count($SQLZ)>500){
			$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
			
			if(!$q->ok){
				echo $q->mysql_error;
				@fclose($fp);
				@unlink($targetpath);
				return;
			}
		}
	
	}
	
	@fclose($fp);
	@unlink($targetpath);
	
	if(count($SQLZ)>0){
		$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	

	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/malwareurls_joxeankoret.md5", $Currentmd5);
	
}


function clean_mx_de($targetpath=null){
	$unix=new unix();
	$uri="http://support.clean-mx.de/clean-mx/xmlphishing.php?";
	$curl=new ccurl($uri);
	if(preg_match("#^--#", $targetpath)){$targetpath=null;}
	if($targetpath==null){
		$targetpath=$unix->FILE_TEMP();
	}
	
	echo "Target path: $targetpath\n";
	
	if(!is_file($targetpath)){
		$curl=new ccurl("$uri");
		$curl->Timeout=3600;
		if(!$curl->GetFile($targetpath)){
			squid_admin_mysql(0, "Clean MX: Unable to download XML file $curl->error", null,__FILE__,__LINE__);
			@unlink($targetpath);
		}
	}
	
	$fp = @fopen($targetpath, "r");
	if(!$fp){
		if($GLOBALS["DEBUG_GREP"]){echo "$targetpath BAD FD\n";}
		@unlink($targetpath);
		return array();
	}
	
	$l=0;
	$q=new mysql_squid_builder();
	while (!feof($fp)) {
		$l++;
		$ligne = trim(fgets($fp)); 
		if($ligne==null){continue;}
	
		if(!preg_match("#<url><\!\[CDATA\[http:\/\/(.+?)\]\]><\/url>#",$ligne,$re)){
			continue;
		}
		$line=$re[1];
		if(preg_match("#http:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}
		if(preg_match("#https:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}
		
		$line=mysql_escape_string2($line);
		$md5=md5($line);
		$SQLZ[]="('$md5',NOW(),'$line',1)";
		if(count($SQLZ)>500){
			sleep(1);
			$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
			if(!$q->ok){
				echo $q->mysql_error;
				@fclose($fp);
				@unlink($targetpath);
				return;
			}
		}
		
		
	}
	
	@fclose($fp);
	@unlink($targetpath);
	
	if(count($SQLZ)>0){
		$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
	}

	
		
}




function vxvault(){
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$curl=new ccurl("http://vxvault.net/URL_List.php");
	$targetpath=$unix->FILE_TEMP();
	
	
	if(!$curl->GetFile($targetpath)){
		if($GLOBALS["VERBOSE"]){echo "DOWNLOAD FAILED $targetpath\n";}
		@unlink($targetpath);
		return false;
	}
	
	$lastmd5=@file_get_contents("/etc/artica-postfix/settings/Daemons/vxvault.md5");
	$Currentmd5=md5_file($targetpath);
	if(!$GLOBALS["FORCE"]){if($lastmd5==$Currentmd5){return;}}
	
	$fp = @fopen($targetpath, "r");
	if(!$fp){
		if($GLOBALS["DEBUG_GREP"]){echo "$targetpath BAD FD\n";}
		@unlink($targetpath);
		return array();
	}
	
	$c=0;
	$t=array();
	while(!feof($fp)){
		$line = trim(fgets($fp));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=str_replace("\\", "/", $line);
		if(!preg_match("#^http#", $line)){
			if($GLOBALS["VERBOSE"]){echo "SKIP $line\n";}
			continue;}
		
		if(preg_match("#http:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}
		if(preg_match("#https:\/\/(.+?)#", $line,$re)){
			$line=$re[1];
		}	
		$md5=md5($line);
		
		$SQLZ[]="('$md5',NOW(),'$line',1)";
		if(count($SQLZ)>500){
			$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
			if(!$q->ok){
				echo $q->mysql_error;
				@fclose($fp);
				@unlink($targetpath);
				return;
			}
		}
		
	}
	
	@fclose($fp);
	@unlink($targetpath);
	
	if(count($SQLZ)>0){
		$q->QUERY_SQL($GLOBALS["PREFIX"].@implode(",", $SQLZ));
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/vxvault.md5", $Currentmd5);
		
}



