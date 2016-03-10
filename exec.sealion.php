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
if($argv[1]=="--dump"){dump();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}


xinstall();
function build_progress($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/sealion.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}

function uninstall(){
	$unix=new unix();
	$sock=new sockets();
	
	build_progress("{uninstall}",15);
	
	$pid=sealion_pid();
	if($unix->process_exists($pid)){
		for($i=0;$i<6;$i++){
			build_progress("{stop_service} $i/5",30);
			$unix->KILL_PROCESS($pid,9);
			$pid=sealion_pid();
			if($unix->process_exists($pid)){break;}
		}
	}
	
	
	build_progress("{uninstall}",50);
	chdir("/usr/local/sealion-agent");
	system("cd /usr/local/sealion-agent");
	system("./uninstall.sh");
	
	chdir("/root");
	system("cd /root");
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir("/usr/local/sealion-agent")){
		shell_exec("rm -rf /usr/local/sealion-agent");
	}
	build_progress("{uninstall} {done}",100);
	
}
function sealion_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/usr/local/sealion-agent/var/run/sealion.pid");
	if($unix->process_exists($pid)){return $pid;}
	return 0;

}
function xinstall(){
	
	$unix=new unix();
	$sock=new sockets();
	
	$sealionmd5=$sock->GET_INFO("SealLionMD5");
	
	if($sealionmd5==null){
		build_progress("{failed} SealLionMD5 == NULL",110);
		return;
		
	}
	
	build_progress("{downloading}",20);
	
	$TMPDIR=$unix->TEMP_DIR();
	$curl=new ccurl("http://articatech.net/download/sealion-agent-3.5.1-noarch.tar.gz");
	if(!$curl->GetFile("$TMPDIR/sealion-agent-3.5.1-noarch.tar.gz")){
		build_progress("{downloading} {failed}",110);
		return;
		
	}
	
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	@mkdir("$TMPDIR/SEALION",0755,true);
	build_progress("{installing}...",50);
	echo "Extracting to $TMPDIR/SEALION\n";
	shell_exec("$tar -xf $TMPDIR/sealion-agent-3.5.1-noarch.tar.gz -C $TMPDIR/SEALION/");
	@unlink("$TMPDIR/sealion-agent-3.5.1-noarch.tar.gz");
	
	if(!is_file("$TMPDIR/SEALION/sealion-agent/install.sh")){
		echo "$TMPDIR/SEALION/sealion-agent/install.sh no such file\n";
		build_progress("{installing}...{failed}",110);
		shell_exec("$rm -rf $TMPDIR/SEALION");
		return;
	}
	
	@chdir("$TMPDIR/SEALION/sealion-agent/");
	system("cd $TMPDIR/SEALION/sealion-agent/");
	
	build_progress("{installing}...",70);
	system("./install.sh -o $sealionmd5");
	
	if(!is_file("/usr/local/sealion-agent/etc/init.d/sealion")){
		@chdir("/root");
		system("cd /root");
		echo "/etc/init.d/sealion no such file\n";
		build_progress("{installing}...{failed}",110);
		shell_exec("$rm -rf $TMPDIR/SEALION");
		return;
	}
	
	system("$ln -sf /usr/local/sealion-agent/etc/init.d/sealion /etc/init.d/sealion");
	
	
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f sealion defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add sealion >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 sealion on >/dev/null 2>&1");
	}
	
	system("/etc/init.d/artica-status restart --force");
	
	build_progress("{installing}...{success}",100);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SealionAgentInstalled", 1);
	
}

function dump(){
	
	$data=@file_get_contents("/usr/local/sealion-agent/etc/agent.json");
	$array=json_decode($data);
	print_r($array);
	
	echo $array->orgToken."\n";
	echo $array->agentVersion."\n";
	
	
	
}