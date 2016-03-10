<?php
$GLOBALS["NOCHECK"]=false;
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.externals.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.childs.inc');
if($argv[1]=="--freewebs"){freewebs();exit;}
if($argv[1]=="--childs"){ChildsProxy();exit;}
if($argv[1]=="--nochek"){$GLOBALS["NOCHECK"]=true;}


xstart();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.access.center.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function checkIntegrated(){
	if($GLOBALS["NOCHECK"]){return true;}
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f) ){if(preg_match("#GlobalAccessManager_auth\.conf#", $line)){return checkIntegrated2();}}
	return false;
}
function checkIntegrated2(){
	if($GLOBALS["NOCHECK"]){return true;}
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f) ){if(preg_match("#acls_center\.conf#", $line)){return checkIntegrated3();}}
	return false;
}
function checkIntegrated3(){
	if($GLOBALS["NOCHECK"]){return true;}
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f) ){if(preg_match("#squid3\/external_acls\.conf#", $line)){return true;}}
	
	echo "**** **** external_acls.conf *** *** Missing\n";
	return false;
}

function freewebs(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){return;}
	$q=new squid_freewebs();
	exec("$squidbin -k reconfigure 2>&1",$results);
	squid_admin_mysql(1, "Reconfigure proxy service ( FreeWebs acls builder)", @implode("\n", $results),__FILE__,__LINE__);
}




function xstart(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",15);
	
	$extern=new external_acls_squid();
	$extern->Build();
	
	
	
	
	if(!checkIntegrated()){
		
		build_progress("{starting} {GLOBAL_ACCESS_CENTER}",30);
		$squid_access_manager=new squid_access_manager();
		$squid_access_manager->build_all();
		build_progress("{starting} {GLOBAL_ACCESS_CENTER}",40);
		$squid=new squidbee();
		
		
		$icap=new icap();
		$icap->build_services();
		
		build_progress("{starting} {reconfigure_proxy_service}",50);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		
		if(!checkIntegrated()){
			build_progress("Missing CONF files:{failed}",110);
			return;
		}
		build_progress("{done} {GLOBAL_ACCESS_CENTER}",100);
		return;
	}
	
	
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",20);
	$external_acls_squid=new external_acls_squid();
	$external_acls_squid->Build();
	
	
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",30);

	
	
	$GLOBALS["aclGen"]=new squid_acls();
	$GLOBALS["aclGen"]->Build_Acls(true);
	$ACLS_TO_ADD=@implode("\n",$GLOBALS["aclGen"]->acls_array);
	@file_put_contents("/etc/squid3/acls_center.conf", $ACLS_TO_ADD);
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",50);
	
	$squid_access_manager=new squid_access_manager();
	$squid_access_manager->build_all();
	

	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",55);
	$squid_childs=new squid_childs();
	$squid_childs->build();
	
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",60);
	$squid=new squidbee();
	$q=new squid_freewebs();
	
	$icap=new icap();
	$icap->build_services();
	
	
	
	
	build_progress("{starting} {GLOBAL_ACCESS_CENTER}",60);
	
	if($GLOBALS["NOCHECK"]){return true;}
	
	$squid_checks=new squid_checks();
	
	if(!$squid_checks->squid_parse()){
		build_progress("{failed}",110);
		return;
	}
	
	build_progress("{done} {reloading_proxy_service}",100);
	$squidbin=$unix->find_program("squid");
	system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
}

function ChildsProxy(){
	$squid_childs=new squid_childs();
	$squid_childs->build();
	
}

function Test_config(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}

	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return false;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			
			if(preg_match("#GlobalAccessManager_url_rewrite\.conf#", $ligne)){
				echo "************ GlobalAccessManager_url_rewrite *********\n";
				echo @file_get_contents("/etc/squid3/GlobalAccessManager_url_rewrite.conf")."\n";
				echo "*******************************************\n";
			}
			
			
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "[line:$lineNumber]: {$tt[$i]}\n";
				}
			}

			return false;
		}
	
	}

	return true;
	
}


