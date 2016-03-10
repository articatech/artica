<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
install($argv[1]);exit;

function ArticaMeta_release($source_package){
	$sock=new sockets();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - DISABLED -\n";
		return;
	}

	echo "Starting......: ".date("H:i:s")." Checking META repository - ENABLED -\n";
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	$basename=basename($source_package);
	if(!preg_match("#artica-[0-9\.]+\.tgz#", $basename)){
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - FAILED ( not an artica package) -\n";
		return;
	}
	if(is_file("$ArticaMetaStorage/releases/$basename")){@unlink("$ArticaMetaStorage/releases/$basename");}
	@copy($source_package, "$ArticaMetaStorage/releases/$basename");
	meta_admin_mysql(2, "Added $basename into official repository", null,__FILE__,__LINE__);
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Starting......: ".date("H:i:s")." Prepare New META package for clients...\n";
	shell_exec("$php ".dirname(__FILE__)."/exec.artica-meta-server.php --force");
	echo "Starting......: ".date("H:i:s")." Prepare New META package for clients done...\n";
}



function install($filename){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/artica.install.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica.install.progress.txt";
	
	$unix=new unix();
	$LINUX_CODE_NAME=$unix->LINUX_CODE_NAME();
	$LINUX_DISTRIBUTION=$unix->LINUX_DISTRIBUTION();
	$LINUX_VERS=$unix->LINUX_VERS();
	$LINUX_ARCHITECTURE=$unix->LINUX_ARCHITECTURE();
	$APACHEUSER=$unix->APACHE_SRC_ACCOUNT();
	$DebianVer="debian{$LINUX_VERS[0]}";
	$TMP_DIR=$unix->TEMP_DIR();
	$ORGV=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$PATCH_VER=null;
	$tarballs_file="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	echo "Package $tarballs_file\n";
	$size=filesize($tarballs_file);
	
	ArticaMeta_release($tarballs_file);
	
	if (preg_match('#([0-9\.]+)_([0-9\.]+)-([0-9]+).tgz$#i',$filename,$r)){
		$CUR_BRANCH=@file_get_contents("/usr/share/artica-postfix/MAIN_RELEASE");
		$CUR_BRANCH=trim($CUR_BRANCH);
		
		echo "Patch....................: {$r[3]}\n";
		echo "From.....................: {$r[1]}\n";
		echo "To.......................: {$r[2]}\n";
		echo "Current Branch..........: $CUR_BRANCH\n";
		if($CUR_BRANCH<>$r[1]){
			echo "$CUR_BRANCH != {$r[1]}\n";
			build_progress("{not_for_current_branch} {requested} {$r[1]}",110);
			return;
		}
		$PATCH_VER=$r[2]." :";
		$ASPATCH=true;
	}
	
	echo "Size....................: ".FormatBytes($size/1024)."\n";
	echo "Current version.........: $ORGV\n";
		
	build_progress("{analyze}...",10);
		
	echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
	echo "Package.................: $filename\n";
	echo "Temp dir................: $TMP_DIR\n";
	echo "Apache User.............: $APACHEUSER\n";
	
	
	
	if(!is_file($tarballs_file)){
		echo "$tarballs_file no such file...\n";
		build_progress("No such file...",110);
		return;
	}
	echo "Uncompressing $tarballs_file...\n";
	build_progress("{extracting} $filename...",20);
	
	
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squid=$unix->LOCATE_SQUID_BIN();
	build_progress("{extracting} $filename...",50);
	
	system("$tar xpf $tarballs_file -C /usr/share/");
	echo "Removing $tarballs_file...\n";
	@unlink($tarballs_file);
	shell_exec("$rm -rf /usr/share/artica-postfix/ressources/conf/upload/*");
	build_progress("{apply_permissions}...",55);
	
	echo "$APACHEUSER -> /usr/share/artica-postfix\n";
	shell_exec("$chown -R $APACHEUSER /usr/share/artica-postfix");
	echo "0755 -> /usr/share/artica-postfix\n";
	shell_exec("$chmod -R 0755 /usr/share/artica-postfix");
	$ORGD=@file_get_contents("/usr/share/artica-postfix/VERSION");
	echo "Old version.............: $ORGV\n";
	if($ASPATCH){$patched=" (patched)";}
	echo "Current version.........: $ORGD$patched\n";
	sleep(2);
	if($ORGV==$ORGD){
		build_progress("{operation_failed} Same version $PATCH_VER$filename...",110);
		return;
	}
	
	build_progress("{restarting} Artica...",60);
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.web-community-filter.php --register");
	build_progress("{restarting} Artica...",65);
	build_progress("{building_init_scripts}...",70);
	system("$php /usr/share/artica-postfix/exec.initslapd.php");
	build_progress("{updating_network}...",75);
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php");
	system("$php /usr/share/artica-postfix/exec.monit.php --build");
	echo "Starting......: ".date("H:i:s")." Purge and clean....\n";
	build_progress("{restarting} Artica...",80);
	if(is_file("/etc/init.d/nginx")){shell_exec("$nohup /etc/init.d/nginx reload >/dev/null 2>&1 &");}
	build_progress("{restarting} Artica...",81);
	shell_exec("$nohup /etc/init.d/auth-tail restart");
	build_progress("{restarting} Artica...",82);
	shell_exec("$nohup /etc/init.d/artica-framework");
	build_progress("{restarting} Artica...",83);
	shell_exec("$nohup /usr/share/artica-postfix/bin/process1 --force --verbose ".time()."");
	build_progress("{restarting} Artica...",84);
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make --empty-cache >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",85);
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",86);
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",87);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",88);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.schedules.php --defaults >/dev/null 2>&1 &");
	build_progress("{restarting} Artica...",90);
	build_progress("{restarting} Artica...",100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";
		
	
	
	
}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
?>