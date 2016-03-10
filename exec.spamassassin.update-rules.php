<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);


if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($GLOBALS["VERBOSE"]){echo "Executing Rule1()\n";}
Master();

function Master(){
	$sock=new sockets();
	$EnableArticaTechSpamAssassin=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaTechSpamAssassin"));
	
	$TargetFile1="/etc/spamassassin/ArticaTechRules1.cf";
	$TargetFile2="/etc/spamassassin/ArticaTechRules2.cf";
	$TargetFile3="/etc/spamassassin/ArticaTechRules3.cf";
	
	
	if($EnableArticaTechSpamAssassin==0){
		if(is_file($TargetFile1)){@unlink($TargetFile1);}
		if(is_file($TargetFile2)){@unlink($TargetFile2);}
		if(is_file($TargetFile3)){@unlink($TargetFile3);}
		if($GLOBALS["VERBOSE"]){echo "EnableArticaTechSpamAssassin = 0\n";}
		die();
	}
	
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
	
	if($GLOBALS["VERBOSE"]){echo($data)."\n";}
	if($GLOBALS["VERBOSE"]){print_r($MAIN);}
	
	@unlink("$temppath/milter-greylist-database.txt");	
	$RELOAD=false;
	if(Rule1($MAIN,$mirror)){$RELOAD=true;}
	if(Rule2($MAIN,$mirror)){$RELOAD=true;}
	if(Rule3($MAIN,$mirror)){$RELOAD=true;}
	
	if($RELOAD){
		system("/etc/init.d/mimedefang reload");
	}
}



function Rule1($MAIN,$mirror){

	$unix=new unix();
	$sock=new sockets();
	
	$temppath=$unix->TEMP_DIR();
	$TargetFile="/etc/spamassassin/ArticaTechRules1.cf";
	$TIME=$MAIN["SPAMASS_1"]["TIME"];
	$MD5=$MAIN["SPAMASS_1"]["MD5"];
	$SourceGZ="spamassassin-rules1.gz";
	$TempSource="$temppath/".basename($TargetFile);
	$KeyTime="SpamassassinPattern1Time";


	$MyTime=$sock->GET_INFO($KeyTime);
	if(!is_file($TargetFile)){$MyTime=0;}
	if($TIME==$MyTime){if($GLOBALS["VERBOSE"]){echo "$KeyTime: $TIME==$MyTime No new update\n";}return;}
		
	$curl=new ccurl("$mirror/$SourceGZ");
	$curl->NoHTTP_POST=true;
	
	if(!$curl->GetFile("$temppath/$SourceGZ")){
		postfix_admin_mysql(0, "Unable to get $SourceGZ", $curl->error,__FILE__,__LINE__);
		return;;
	
	}
	$md5f=md5_file("$temppath/$SourceGZ");
	if($md5f<>$MD5){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to get $SourceGZ (corrupted)", $curl->error,__FILE__,__LINE__);
		return;;
		
	}	

	if(!$unix->uncompress("$temppath/$SourceGZ", $TempSource)){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to extract $SourceGZ (corrupted)", null,__FILE__,__LINE__);
		return;;		
	}

	@unlink("$temppath/$SourceGZ");
	@unlink($TargetFile);
	@copy($TempSource,$TargetFile);
	@unlink($TempSource);
	postfix_admin_mysql(0, "Success updating $TargetFile database version $TIME", null,__FILE__,__LINE__);
	
	$sock->SET_INFO($KeyTime, $TIME);
	return true;

}
function Rule2($MAIN,$mirror){

	$unix=new unix();
	$sock=new sockets();

	$temppath=$unix->TEMP_DIR();
	$TargetFile="/etc/spamassassin/ArticaTechRules2.cf";
	$TIME=$MAIN["SPAMASS_2"]["TIME"];
	$MD5=$MAIN["SPAMASS_2"]["MD5"];
	$SourceGZ="spamassassin-rules3.gz";
	$TempSource="$temppath/".basename($TargetFile);
	$KeyTime="SpamassassinPattern2Time";


	$MyTime=$sock->GET_INFO($KeyTime);
	if(!is_file($TargetFile)){$MyTime=0;}
	if($TIME==$MyTime){if($GLOBALS["VERBOSE"]){echo "$KeyTime: $TIME==$MyTime No new update\n";}return;}

	$curl=new ccurl("$mirror/$SourceGZ");
	$curl->NoHTTP_POST=true;

	if(!$curl->GetFile("$temppath/$SourceGZ")){
		postfix_admin_mysql(0, "Unable to get $SourceGZ", $curl->error,__FILE__,__LINE__);
		return;;

	}
	$md5f=md5_file("$temppath/$SourceGZ");
	if($md5f<>$MD5){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to get $SourceGZ (corrupted)", $curl->error,__FILE__,__LINE__);
		return;;

	}

	if(!$unix->uncompress("$temppath/$SourceGZ", $TempSource)){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to extract $SourceGZ (corrupted)", null,__FILE__,__LINE__);
		return;;
	}

	@unlink("$temppath/$SourceGZ");
	@unlink($TargetFile);
	@copy($TempSource,$TargetFile);
	@unlink($TempSource);
	postfix_admin_mysql(0, "Success updating $TargetFile database version $TIME", null,__FILE__,__LINE__);

	$sock->SET_INFO($KeyTime, $TIME);
	return true;

}
function Rule3($MAIN,$mirror){

	$unix=new unix();
	$sock=new sockets();

	$temppath=$unix->TEMP_DIR();
	$TargetFile="/etc/spamassassin/ArticaTechRules3.cf";
	$TIME=$MAIN["SPAMASS_3"]["TIME"];
	$MD5=$MAIN["SPAMASS_3"]["MD5"];
	$SourceGZ="spamassassin-rules4.gz";
	$TempSource="$temppath/".basename($TargetFile);
	$KeyTime="SpamassassinPattern3Time";


	$MyTime=$sock->GET_INFO($KeyTime);
	if(!is_file($TargetFile)){$MyTime=0;}
	if($TIME==$MyTime){if($GLOBALS["VERBOSE"]){echo "$KeyTime: $TIME==$MyTime No new update\n";}return;}

	$curl=new ccurl("$mirror/$SourceGZ");
	$curl->NoHTTP_POST=true;

	if(!$curl->GetFile("$temppath/$SourceGZ")){
		postfix_admin_mysql(0, "Unable to get $SourceGZ", $curl->error,__FILE__,__LINE__);
		return;;

	}
	$md5f=md5_file("$temppath/$SourceGZ");
	if($md5f<>$MD5){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to get $SourceGZ (corrupted)", $curl->error,__FILE__,__LINE__);
		return;;

	}

	if(!$unix->uncompress("$temppath/$SourceGZ", $TempSource)){
		@unlink("$temppath/$SourceGZ");
		postfix_admin_mysql(0, "Unable to extract $SourceGZ (corrupted)", null,__FILE__,__LINE__);
		return;;
	}

	@unlink("$temppath/$SourceGZ");
	@unlink($TargetFile);
	@copy($TempSource,$TargetFile);
	@unlink($TempSource);
	postfix_admin_mysql(0, "Success updating $TargetFile database version $TIME", null,__FILE__,__LINE__);

	$sock->SET_INFO($KeyTime, $TIME);
	return true;

}
?>