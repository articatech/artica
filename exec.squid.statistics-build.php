<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");


xstart($argv[1]);


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics-{$GLOBALS["zMD5"]}.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function xstart($md5){
	$GLOBALS["zMD5"]=$md5;
	echo "***********************************\n";
	echo "Report ID: $md5\n";
	echo "***********************************\n";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_type FROM reports_cache WHERE `zmd5`='$md5'"));
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL {failed}",110);
		return;
	}
	
	$report_type=$ligne["report_type"];
	echo "Report type: $report_type\n";
	switch ($report_type) {
		case "FLOW":
			system("$php /usr/share/artica-postfix/exec.squid.statistics.FLOW.build.php $md5");
			exit;
		break;
		case "MEMBERS":
			echo "Running /usr/share/artica-postfix/exec.squid.statistics.MEMBERS.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.squid.statistics.MEMBERS.build.php $md5");
			exit;
		break;
		
		case "MEMBER_UNIQ":
			echo "Running /usr/share/artica-postfix/exec.squid.statistics.MEMBERUNIQUE.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.squid.statistics.MEMBERUNIQUE.build.php $md5");
			exit;
		break;
		
		case "WEBSITES":
			echo "Running exec.squid.statistics.WEBSITES.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.squid.statistics.WEBSITES.build.php $md5");
			exit;
		break;
		
		case "IDS":
			echo "Running exec.squid.statistics.IDS.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.system.statistics.IDS.build.php $md5");
			exit;
			break;		
		
		case "CATEGORIES":
			system("$php /usr/share/artica-postfix/exec.squid.statistics.CATEGORIES.build.php $md5");
			exit;
			break;
			
			
		case "CATEGORY_UNIQ":
			system("$php /usr/share/artica-postfix/exec.squid.statistics.CATEGORIESUNIQUE.build.php $md5");
			exit;
			break;			
			
			
		case "WEBFILTERING":
			echo "Running exec.squid.statistics.WEBFILTERING.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.squid.statistics.WEBFILTERING.build.php $md5");
			exit;
			break;
			
		case "SMTP_MEMBERS":
			echo "Running exec.postfix.statistics.MEMBERS.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.postfix.statistics.MEMBERS.build.php $md5");
			exit;
			break;
			
		case "SMTP_REFUSED":
			echo "Running exec.postfix.statistics.SMTP_REFUSED.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.postfix.statistics.SMTP_REFUSED.build.php $md5");
			exit;
			break;	

		case "SMTP_FLOW_MD":
			echo "Running exec.postfix.statistics.SMTP_FLOW.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.postfix.statistics.SMTP_FLOW.build.php $md5");
			exit;
			break;	

		case "SMTP_ATTACHS":
			echo "Running exec.postfix.statistics.SMTP_ATTACHS.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.postfix.statistics.SMTP_ATTACHS.build.php $md5");
			exit;
			break;			
			
			

		case "SMTP_UNIQ":
			echo "Running /usr/share/artica-postfix/exec.postfix.statistics.MEMBERUNIQUE.build.php $md5\n";
			system("$php /usr/share/artica-postfix/exec.postfix.statistics.MEMBERUNIQUE.build.php $md5");
			exit;
			break;			
			
		
		
		
		
		default:
			build_progress("Unable to understand report $report_type  {failed}",110);
		break;
	}
	
}

