<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--save-report"){save_report();exit;}
if($argv[1]=="--menu"){menu();exit;}

function menu(){
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();
$php5=$unix->LOCATE_PHP5_BIN();
$sock=new sockets();
$ArticaMetaUsername=$sock->GET_INFO("ArticaMetaUsername");
$ArticaMetaPassword=$sock->GET_INFO("ArticaMetaPassword");
$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));


$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ S T A T I S T I C S  M E N U ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
$DPREF="/etc/artica-postfix/settings/Daemons";


$diag[]="PROXY \"Build a proxy report\"";
$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";

$dir="/etc/artica-postfix/wizard/statistics";

$f[]="";
$f[]="function xPROXY(){";

$f[]="mkdir -p $dir || true";
$f1[]="$DIALOG --clear --ok-label \"Next\" --exit-label \"Cancel\" --title \"Proxy Statistics:Create report\"";
$f1[]="--radiolist";
$f1[]="\"Report type ?\" 15 60 4";
$f1[]="\"WEBSITES\" \"Statistics by Websites\" ON";
$f1[]="\"FLOW\" \"Statistics by Flow\" OFF";
$f1[]="\"CATEGORIES\" \"Statistics by category\" OFF";
$f1[]="\"WEBFILTERING\" \"Web filtering statistics\" OFF";
$f1[]="2> $dir/report_type";
$f[]=@implode(" ", $f1);
$f1=array();
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f $dir/report_type";
$f[]="\t\treturn";
$f[]="\tfi";


$f[]="REPORT=`cat $dir/report_type`";

$f1[]="$DIALOG --clear --ok-label \"Next\" --exit-label \"Cancel\" --title \"Proxy Statistics (\$REPORT)\"";
$f1[]="--radiolist";
$f1[]="\"Type of user ?\" 15 60 4";
$f1[]="\"MAC\" \"By Mac Address\" OFF";
$f1[]="\"USERID\" \"By user name\" OFF";
$f1[]="\"IPADDR\" \"By IP address\" ON";
$f1[]="2> $dir/USER";
$f[]=@implode(" ", $f1);


$f1=array();
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f $dir/USER";
$f[]="\t\treturn";
$f[]="\tfi";
$f[]="";

$date=date("Y-m-d 00:00:00");
$f[]="\t$DIALOG --clear --ok-label \"Next\" --title \"Proxy Statistics (\$REPORT)\" --inputbox \"Enter the first date\" 10 68 \"$date\" 2> $dir/date1";
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f $dir/date1";
$f[]="\t\treturn";
$f[]="\tfi";

$date=date("Y-m-d 23:59:59");
$f[]="";
$f[]="\t$DIALOG --clear --ok-label \"Next\" --title \"Proxy Statistics (\$REPORT)\" --inputbox \"Enter the last date\" 10 68 \"$date\" 2> $dir/date2";
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f $dir/date2";
$f[]="\t\treturn";
$f[]="\tfi";

$search="*";
$f[]="";
$f[]="\t$DIALOG --clear --ok-label \"Build report\" --title \"Proxy Statistics (\$REPORT)\" --inputbox \"Enter the member you want to find\" 10 68 \"$search\" 2> $dir/searchuser";
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f $dir/searchuser";
$f[]="\t\treturn";
$f[]="\tfi";
$f[]="";

$f[]="\t$php ".__FILE__." --save-report >/tmp/report.log &";
$f[]="\t$DIALOG --tailbox /tmp/report.log  25 150";


$f[]="}";
$f[]="";



$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="PROXY) xPROXY;;";
$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_statistics_menu.sh\n";}
@file_put_contents("/tmp/bash_statistics_menu.sh", @implode("\n",$f));
@chmod("/tmp/bash_statistics_menu.sh",0755);
	
}

function save_report(){
	$dir="/etc/artica-postfix/wizard/statistics";
	$unix=new unix();
	$params["FROM"]=strtotime(@file_get_contents("$dir/date1"));
	$params["TO"]=strtotime(@file_get_contents("$dir/date2"));
	$params["USER"]=trim(@file_get_contents("$dir/USER"));
	$params["searchsites"]=trim(@file_get_contents("$dir/searchsites"));
	$params["searchuser"]=trim(@file_get_contents("$dir/searchuser"));
	$report_type=trim(@file_get_contents("$dir/report_type"));
	$content=serialize($params);
	$md5=md5($content.$report_type);
	$title="Report {$report_type} {$params["USER"]} From ".@file_get_contents("$dir/date1")." To ".@file_get_contents("$dir/date2");
	echo "$title\n";
	echo "Report Type........: $report_type\n";
	echo "From Date..........: {$params["FROM"]}\n";
	echo "To Date............: {$params["TO"]}\n";
	echo "Search.............: {$params["searchuser"]}\n";
	echo "MD5................: $md5\n";
	
	$title=mysql_escape_string2($title);
	$content=mysql_escape_string2($content);
	
	echo "Please wait, saving report parameters......\n";
	$sql="INSERT IGNORE INTO reports_cache (`title`,`params`,`zmd5`,`report_type`,`zDate`) VALUES 
	('$title','$content','$md5','$report_type',NOW())";
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Please wait, building report......\n";
	echo "\n\n";
	system("$php /usr/share/artica-postfix/exec.squid.statistics-build.php $md5");
	echo " * * * DONE * * *\n\n";
	@unlink("/tmp/report.log");
}

