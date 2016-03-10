<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');


include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--regex"){update_milter_regex();die();}
if($argv[1]=="--postfix"){compile_postfix();die();}


run();

function run(){
	update_milter_greylist();
	update_milter_regex();
	
	
}

function compile_postfix(){
	$unix=new unix();
	$main=new maincf_multi("master","master");
	$check_client_access=$main->check_client_access();
	$postfix=$unix->find_program("postfix");
	shell_exec("$postfix stop");
	shell_exec("$postfix start");
	
}


function update_milter_greylist(){

$unix=new unix();
$mirror="http://mirror.articatech.net/webfilters-databases";
if($GLOBALS["VERBOSE"]){echo "Downloading $mirror/milter-greylist-database.txt\n";}
$curl=new ccurl("$mirror/milter-greylist-database.txt");
$curl->NoHTTP_POST=true;

$temppath=$unix->TEMP_DIR();

if(!$curl->GetFile("$temppath/milter-greylist-database.txt")){
		postfix_admin_mysql(0, "Unable to get Milter-greylist index file", $curl->error);
		return;;

}

if(!is_file("$temppath/milter-greylist-database.txt")){
	postfix_admin_mysql(0, "Unable to get Milter-greylist index file (no such file)", $curl->error);
		return;;	
}

$data=@file_get_contents("$temppath/milter-greylist-database.txt");
$MAIN=unserialize($data);



@unlink("$temppath/milter-greylist-database.txt");
$TIME=$MAIN["PATTERN"]["TIME"];
$MD5=$MAIN["PATTERN"]["MD5"];
$sock=new sockets();
$MyTime=$sock->GET_INFO("MilterGreyListPatternTime");
if(!is_file("/etc/mail/milter-greylist-database.conf")){$MyTime=0;}
if($TIME==$MyTime){
	if($GLOBALS["VERBOSE"]){echo "$TIME==$MyTime No new update\n";}
	return;;}
	
$curl=new ccurl("$mirror/milter-greylist-database.gz");
$curl->NoHTTP_POST=true;

if(!$curl->GetFile("$temppath/milter-greylist-database.gz")){
	postfix_admin_mysql(0, "Unable to get milter-greylist-database.gz", $curl->error,__FILE__,__LINE__);
	return;;

}
$md5f=md5_file("$temppath/milter-greylist-database.gz");
if($md5f<>$MD5){
	@unlink("$temppath/milter-greylist-database.gz");
	postfix_admin_mysql(0, "Unable to get milter-greylist-database.gz (corrupted)", $curl->error,__FILE__,__LINE__);
	return;;
	
}	

if(!$unix->uncompress("$temppath/milter-greylist-database.gz", "$temppath/milter-greylist-database.conf")){
	@unlink("$temppath/milter-greylist-database.gz");
	postfix_admin_mysql(0, "Unable to extract milter-greylist-database.gz (corrupted)", null,__FILE__,__LINE__);
	return;;		
}

@unlink("$temppath/milter-greylist-database.gz");
@unlink("/etc/mail/milter-greylist-database.conf");
@copy("$temppath/milter-greylist-database.conf","/etc/mail/milter-greylist-database.conf");
@unlink("$temppath/milter-greylist-database.conf");
postfix_admin_mysql(0, "Success updating new Milter-greylist database version $TIME", null,__FILE__,__LINE__);
$sock->SET_INFO("MilterGreyListPatternTime", $TIME);
$sock->SET_INFO("MilterGreyListPatternCount", $unix->COUNT_LINES_OF_FILE("/etc/mail/milter-greylist-database.conf"));

	$main=new maincf_multi("master","master");
	$check_client_access=$main->check_client_access();
	$postfix=$unix->find_program("postfix");
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --body-checks >/dev/null 2>&1 &");
	shell_exec("$postfix stop");
	shell_exec("$postfix start");

postfix_admin_mysql(1, "Restarting Milter-greylist service", null,__FILE__,__LINE__);
shell_exec("/etc/init.d/milter-greylist restart");

}

function update_milter_regex(){
	$unix=new unix();
	$mirror="http://mirror.articatech.net/webfilters-databases";
	if($GLOBALS["VERBOSE"]){echo "Downloading $mirror/milter-regex-database.gz\n";}
	$curl=new ccurl("$mirror/milter-regex-database.gz");
	$curl->NoHTTP_POST=true;
	
	$temppath=$unix->TEMP_DIR();
	
	if(!$curl->GetFile("$temppath/milter-regex-database.gz")){
		postfix_admin_mysql(0, "Unable to get Milter-Regex database file", $curl->error);
		return;
	
	}
	
	if(!is_file("$temppath/milter-regex-database.gz")){
		postfix_admin_mysql(0, "Unable to get Milter-Regex database file (no such file)", $curl->error);
		return;
	}

	if(!$unix->uncompress("$temppath/milter-regex-database.gz", "$temppath/milter-regex-database.sql")){
		@unlink("$temppath/milter-regex-database.gz");
		postfix_admin_mysql(0, "Unable to extract milter-regex-database.gz (corrupted)", null,__FILE__,__LINE__);
		return;
	}
	
	@unlink("$temppath/milter-regex-database.gz");
	
	$MAIN=unserialize(@file_get_contents("$temppath/milter-regex-database.sql"));
	if(!is_array($MAIN)){
		@unlink("$temppath/milter-regex-database.sql");
		postfix_admin_mysql(0, "Unable to understand milter-regex-database (Array corrupted)", null,__FILE__,__LINE__);
		return;
	}
	
	$Time=intval($MAIN["PATTERN"]["TIME"]);
	if($Time==0){
		@unlink("$temppath/milter-regex-database.sql");
		postfix_admin_mysql(0, "Unable to understand milter-regex-database (Time corrupted)", null,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$MyTime=$sock->GET_INFO("MilterRegexPatternTime");
	if($MyTime==$Time){return;}
	$q=new mysql();
	$RULES=$q->COUNT_ROWS("milterregex_acls", "artica_backup");
	@unlink("$temppath/milter-regex-database.sql");
	
	while (list ($num, $ligne) = each ($MAIN["DATAS"]) ){
	
		while (list ($a, $b) = each ($ligne) ){
			$ligne[$a]=mysql_escape_string2($b);
		}
	
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$method=$ligne["method"];
		$zmd5=$ligne["zmd5"];
		$instance=$ligne["instance"];
		$method=$ligne["method"];
		$type=$ligne["type"];
		$enabled=$ligne["enabled"];
		$reverse=$ligne["reverse"];
		$extended=$ligne["extended"];
		$zDate=$ligne["zDate"];
	
		$sql="INSERT INTO `milterregex_acls`
		(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES
		('$zmd5','$zDate','$instance','$method','$type','$pattern','$description',$enabled,$reverse,$extended);";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
	}	
	
	$sock->SET_INFO("MilterRegexPatternTime", $MAIN["PATTERN"]["TIME"]);
	$RULES2=$q->COUNT_ROWS("milterregex_acls", "artica_backup");
	$SUM=$RULES2-$RULES;
	if($SUM>0){
		postfix_admin_mysql(1, "Restarting Milter-regex service", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/milter-regex restart");
		postfix_admin_mysql(2, "$SUM rules updated for Milter-regex ACls", null,__FILE__,__LINE__);
	}
	
	$chown=$unix->find_program("chown");
	shell_exec("$chown postfix:postfix /var/run/milter-greylist/milter-greylist.sock >/dev/null 2>&1");
	
}





?>