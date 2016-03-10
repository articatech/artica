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
	include_once(dirname(__FILE__).  "/ressources/smtp/smtp.php");
	include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
	if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	
start($argv[1]);

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	sleep(1);

}


function start($id){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.resend.progress.$id";
	$id=intval($id);
	$sock=new sockets();
	$unix=new unix();
	
	
	if($id==0){
		echo "ID: $id not supported\n";
		build_progress(110,"{failed}");
		die();
	}
	
	$postgres=new postgres_sql();
	$tempfile=$unix->FILE_TEMP();
	$Dirtemp=$unix->TEMP_DIR();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	
	if(!$q->ok){
		echo "PostgreSQL Error:".$q->mysql_error."\n";
		build_progress(110,"PostgreSQL {failed}");
		die();
	}
	$instance=$unix->hostname_g();
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$msgmd5=$ligne["msgmd5"];
	if($mailfrom==null){$mailfrom="root@$instance";}
	
	echo "From: $mailfrom\n";
	echo "To..: $mailto\n";
	echo "ID..: $msgmd5\n";
	
	
	build_progress(20,"$mailfrom {to} $mailto ($msgmd5)");
	
	$sql="SELECT contentid FROM backupdata WHERE msgmd5='$msgmd5'";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	
	if(!$q->ok){
		echo "PostgreSQL Error:".$q->mysql_error."\n";
		build_progress(110,"PostgreSQL {failed}");
		die();
	}
	
	$contentid=$ligne["contentid"];
	
	build_progress(30,"msg id: $contentid");
	@mkdir($Dirtemp,0777,true);
	@chmod($Dirtemp,0777);
	
	$sql="select lo_export($contentid, '$Dirtemp/$msgmd5.gz')";
	if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "PostgreSQL Error:".$q->mysql_error."\n";
		build_progress(110,"PostgreSQL {failed}");
		die();
	}
	
	build_progress(40,"{uncompress}");
	
	if(!$unix->uncompress("$Dirtemp/$msgmd5.gz", "$Dirtemp/$msgmd5.msg")){
		@unlink("$Dirtemp/$msgmd5.gz");
		@unlink("$Dirtemp/$msgmd5.msg");
		build_progress(110,"{uncompress} {failed}");
		die();
	}

	
	
	$smtp=new smtp();
	$TargetHostname=inet_interfaces();
	if(preg_match("#all#is", $TargetHostname)){$TargetHostname="127.0.0.1";}
	
	
	$params["helo"]=$instance;
	$params["debug"]=true;
	$params["host"]=$TargetHostname;
	$params["bindto"]="127.0.0.1";
	build_progress(50,"{connecting}");
	
	if(!$smtp->connect($params)){
		build_progress(110,"{connect} {failed}");
		@unlink("$Dirtemp/$msgmd5.msg");
		echo "$smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text\n";
		return;
	}	
	
	$finalbody=@file_get_contents("$Dirtemp/$msgmd5.msg");

	build_progress(90,"{sending}");
	if(!$smtp->send(array("from"=>$mailfrom,"recipients"=>$mailto,"body"=>$finalbody,"headers"=>null))){
		build_progress(110,"{sending} {failed}");
		@unlink("$Dirtemp/$msgmd5.msg");
		echo "$smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text\n";
		$smtp->quit();
		return;
	}
	
	@unlink("$Dirtemp/$msgmd5.msg");
	$smtp->quit();
	build_progress(100,"{success}");
	
}

function inet_interfaces(){
	$f=file("/etc/postfix/main.cf");
	while (list ($key, $line) = each ($f) ){
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(preg_match("#^inet_interfaces.*?=(.*)#", $line,$re)){
			$re[1]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
			$inet_interfaces=trim($re[1]);
			$inet_interfaces=str_replace("\r\n", "", $inet_interfaces);
			$inet_interfaces=str_replace("\r", "", $inet_interfaces);
			$inet_interfaces=str_replace("\n", "", $inet_interfaces);
				
				
			if(strpos($inet_interfaces, ",")>0){
				$tr=explode(",",$inet_interfaces);
				if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}
				if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
				return $tr[0];
			}
				
			if(strpos($inet_interfaces, " ")>0){
				$tr=explode(" ",$inet_interfaces);
				if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}

				if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
				return $tr[0];
			}
			if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
			return $re[1];
				
		}
	}

}