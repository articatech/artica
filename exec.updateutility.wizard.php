#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["TITLENAME"]="Kaspersky Update Utility";


$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

xrun();

function build_progress($text,$pourc){
	$echotext=$text;
	if(is_numeric($text)){$old=$pourc;$pourc=$text;$text=$old;}
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/UpdateUtility.wizard.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function xrun(){
	$unix=new unix();
	$curl=new ccurl();
	$sock=new sockets();
	$tmpfile=$unix->FILE_TEMP();
	$tmpdir=$unix->TEMP_DIR();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	$UpdateUtilityWebServername=$sock->GET_INFO("UpdateUtilityWebServername");
	if($UpdateUtilityWebServername==null){$UpdateUtilityWebServername="update.domain.tld";}
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	$UpdateUtilitySchedule=intval($sock->GET_INFO("UpdateUtilitySchedule"));
	$UpdateUtilityForceProxy=intval($sock->GET_INFO("UpdateUtilityForceProxy"));
	
	$FreewebsStorageDirectory=$UpdateUtilityStorePath;
	
	build_progress("{creating_the_webservice}:$UpdateUtilityWebServername ",15);
	$servername=trim(strtolower($UpdateUtilityWebServername));
	
	$SCHEDULES[0]="0 * * * *";
	$SCHEDULES[1]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$SCHEDULES[2]="0 0,4,8,12,16,20 * * *";
	$SCHEDULES[3]="0 0,6,12,18 * * *";
	
	$schedule=$SCHEDULES[$UpdateUtilitySchedule];
	$cronfile="UpdateUtility";
	
	$PATH="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	
	$CRON[]="PATH=$PATH";
	$CRON[]="MAILTO=\"\"";
	$CRON[]="$schedule\troot\t$nice $php5 /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility >/dev/null 2>&1";
	$CRON[]="";
	file_put_contents("/etc/cron.d/$cronfile",@implode("\n", $CRON));
	chmod("/etc/cron.d/$cronfile",0640);
	chown("/etc/cron.d/$cronfile","root");
	system("/etc/init.d/cron reload");
	
	$uid=null;
	$lvm_vg=null;
	$vg_size=null;
	$ServerIP=null;
	$ServerPort=0;
	
	
	$sock->SET_INFO("EnableFreeWeb",1);
	$sock->SET_INFO("EnableApacheSystem",1);

	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if($service<>null){
		if(is_file($debianbin)){shell_exec("$debianbin -f $service defaults >/dev/null 2>&1");}
		if(is_file($redhatbin)){shell_exec("$redhatbin --add $service >/dev/null 2>&1");}
	}
	
	if(!is_numeric($vg_size)){$vg_size=5000;}
	$useSSL=0;
	
	
	$TDOM=explode(".",$UpdateUtilityWebServername);
	unset($TDOM[0]);
	$domainname=@implode(".", $TDOM);
	$WebCopyID=0;
	build_progress("{creating_the_webservice}:$UpdateUtilityWebServername ",50);
	
	$q=new mysql();
	$sql="DELETE FROM freeweb WHERE groupware='UPDATEUTILITY'";
	$q->QUERY_SQL($sql,"artica_backup");
	
	

	$servername=strip_bad_characters($UpdateUtilityWebServername);
	if(substr($servername, strlen($servername)-1,1)=='.'){$servername=substr($servername, 0,strlen($servername)-1);}
	if(substr($servername,0,1)=='.'){$servername=substr($servername, 1,strlen($servername));}
	$sql="INSERT INTO freeweb (useSSL,servername,domainname,www_dir,groupware)
	 VALUES('0','$servername','{$domainname}','$FreewebsStorageDirectory','UPDATEUTILITY' )";
	
	
	build_progress("{creating_the_webservice}:$UpdateUtilityWebServername ",55);
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("{creating_the_webservice}:{failed} ",110);
		return;
	}
	
	$cgcreate=$unix->find_program("cgcreate");
	if(is_file($cgcreate)){
		
		$cgroupsEnabled=intval($sock->GET_INFO("cgroupsEnabled"));
		if($cgroupsEnabled==0){
			build_progress("Enable cGroups...",70);
			$sock->SET_INFO("cgroupsEnabled", 1);
			system("$php5 /usr/share/artica-postfix/exec.cgroups.php --start");
		}
		
		$UpdateUtilityCpuShares=intval($sock->GET_INFO("UpdateUtilityCpuShares"));
		$UpdateUtilityDiskIO=intval($sock->GET_INFO("UpdateUtilityDiskIO"));
		if($UpdateUtilityCpuShares==0){$UpdateUtilityCpuShares=256;}
		if($UpdateUtilityDiskIO==0){$UpdateUtilityDiskIO=450;}
		$unix->CGROUPS_limit_service_structure("kasupt",$UpdateUtilityCpuShares,0,$UpdateUtilityDiskIO);
		build_progress("LIMIT CPU  $UpdateUtilityCpuShares I/O $UpdateUtilityDiskIO",70);
		
	}
	
	
	if(is_file($squidbin)){
		if($UpdateUtilityForceProxy==1){
			build_ufdb();
			shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
		}else{
			build_apache_OFF();
		}
		
	}
	
	
	$sql="UPDATE freeweb SET `www_dir`='$FreewebsStorageDirectory' WHERE servername='$UpdateUtilityWebServername'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "Function ". __FUNCTION__."\nLine:".__LINE__."\nFile:".__FILE__."\n".$q->mysql_error;
		build_progress("{creating_the_webservice}:{failed} ",110);
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{reconfiguring_service} ",80);
	$cmd=trim("$php /usr/share/artica-postfix/exec.freeweb.php --sitename $UpdateUtilityWebServername --restart --no-httpd-conf");
	system($cmd);

	build_progress("{restarting_service} ",85);
	system("/etc/init.d/apache2 restart");
	build_progress("{done} ",100);
	$sock->SET_INFO("UpdateUtilityWizard", 1);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility >/dev/null 2>&1 &");
	
}
function build_ufdb(){

	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	build_apache_ON();
	if($EnableUfdbGuard==1){
		build_progress("{building} {webfiltering} {enabled} OK",75);

	}else{
		build_progress("{building} {webfiltering} {activate} OK",75);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
	}

	if(!build_IsInSquid()){
		build_progress("{building} {reconfigure_proxy_service}...",76);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbUseArticaClient", 1);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress("{building} {reconfigure_proxy_service} {done}",78);
	}

	build_progress("{building} {webfiltering} {done}",79);

}

function build_apache_ON(){
	$sock=new sockets();
	$file="/etc/apache2/conf.d/UpdateUtility";
	
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}

	$f[]="<IfModule mod_alias.c>";
	$f[]="\tAlias /UpdateUtilityStorePath/ \"$UpdateUtilityStorePath/\"";
	$f[]="\t<Directory \"$UpdateUtilityStorePath/\">";
	$f[]="\t\tAllowOverride None";
	$f[]="\t\tOrder allow,deny";
	$f[]="\t\tAllow from all";
	$f[]="\t</Directory>";
	$f[]="</IfModule>";
	$f[]="";

	@file_put_contents("/etc/apache2/conf.d/UpdateUtility", @implode("\n", $f));
	@chmod("/etc/apache2/conf.d/UpdateUtility",0755);
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb", 1);
	build_progress("{building} {restarting} {webservice}",75);
	system("/etc/init.d/apache2 restart");
	build_progress("{building} {restarting} {webservice} {done}",75);
}
function build_apache_OFF(){

	if(!is_file("/etc/apache2/conf.d/UpdateUtility")){return;}
	@unlink("/etc/apache2/conf.d/UpdateUtility");
	build_progress("{building} {restarting} {webservice}",75);
	system("/etc/init.d/apache2 restart");
	build_progress("{building} {restarting} {webservice} {done}",75);
}

function build_IsInSquid(){

	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list($num,$val)=each($f)){
		if(preg_match("#url_rewrite_program.*?\/ufdbgclient\.php#", $val)){return true;}

	}
}