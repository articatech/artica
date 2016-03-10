<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(is_file("/etc/artica-postfix/AS_KIMSUFFI")){echo "AS_KIMSUFFI!\n";die();}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



install();


function install(){
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/phpmyadmin.progress";
	$GLOBALS["DOWNLOAD_PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/phpmyadmin.download.progress";
	$uri="http://articatech.net/download/phpMyAdmin-4.2.12.tar.gz";
	//$uri="http://artica.fr/download/phpMyAdmin-4.2.12.tar.gz";
	echo "Starting $uri\n";
	$unix=new unix();
	
	echo "Downloading phpMyAdmin-4.2.12.tar.gz...\n";
	build_progress("{downloading} phpMyAdmin-4.2.12.tar.gz...",20);
	$TMP_FILE=$unix->TEMP_DIR()."/phpMyAdmin-4.2.12.tar.gz";
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	if(!$curl->GetFile($TMP_FILE)){
		@unlink($TMP_FILE);
		build_progress("{downloading} phpMyAdmin-4.2.12.tar.gz {failed}...",110);
		return;
	}
		
	
	
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$ln=$unix->find_program("ln");
	build_progress("{extracting} phpMyAdmin-4.2.12.tar.gz...",50);
	
	
	
	
	$ERROR=false;
	echo "$tar xvf $TMP_FILE -C /usr/share/ 2>&1\n";
	exec("$tar xvf $TMP_FILE -C /usr/share/ 2>&1",$EXT);
	while (list ($index, $ligne) = each ($EXT) ){
		echo "$ligne\n";
		if(preg_match("#(Cannot|recoverable|Error|exiting)\s+#", $ligne)){
			echo "Error Found $ligne\n";
			$ERROR=true; 
		}
		
	}
	
	
	
	if($ERROR){
		build_progress("{extraction_failed}...",110);
		sleep(4);
		if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
		return;
		
	}
	if(is_file($TMP_FILE)){@unlink($TMP_FILE);}
	
	if(!is_dir("/usr/share/phpMyAdmin-4.2.12-all-languages")){
		build_progress("Destination failed...",110);
		return;
	}
	
	build_progress("Creating symbolic link",50);
	if(!is_link("/usr/share/phpmyadmin")){@unlink("/usr/share/phpmyadmin");}
	if(is_dir("/usr/share/phpmyadmin")){shell_exec("$rm -rf /usr/share/phpmyadmin");}
	shell_exec("$ln -sf /usr/share/phpMyAdmin-4.2.12-all-languages /usr/share/phpmyadmin");

	build_progress("{restart_services}: Artica Status...",50);
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{restart_services}: Artica Web interface...",80);
	system("$php /usr/share/artica-postfix/exec.phpmyadmin.php --build");
	system("$php /usr/share/artica-postfix/exec.lighttpd.php --apache-build");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	system("$apache2ctl -f /etc/artica-postfix/httpd.conf -k restart");

	
	

	
	build_progress("{success}...",100);
	
	
	
}

function build_progress($text,$pourc){
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	$info=2;
	if($pourc>100){$info=0;}
	

}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		$downloaded_size=FormatBytes($downloaded_size/1024,true);
		$download_size=FormatBytes($download_size/1024,true);
		
		
		echo "phpMyAdmin-4.2.12.tar.gz: {$progress}% $downloaded_size/$download_size\n";
		@file_put_contents($GLOBALS["DOWNLOAD_PROGRESS_FILE"], $progress);
		@chmod($GLOBALS["DOWNLOAD_PROGRESS_FILE"], 0777);
		$GLOBALS["previousProgress"]=$progress;
	}
}