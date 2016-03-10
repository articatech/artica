<?php

/*./configure --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=/home/ufdbcat --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbcat --with-ufdb-logdir=/var/log/ufdbcat --without-unix-sockets --with-ufdb-bindir=/opt/ufdbcat/bin
 * 
 */

include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--latest"){echo "Latest:". latests()."\n";exit;}
if($argv[1]=="--create-package"){create_package();exit;}
if($argv[1]=="--parse-install"){parse_install($argv[2]);exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");



$GLOBALS["ROOT-DIR"]="/root/ufdbcat-builder";


if(is_dir($GLOBALS["ROOT-DIR"])){shell_exec("$rm -rf {$GLOBALS["ROOT-DIR"]}");}
create_package();


function UFDBCAT_VERSION(){
	exec("/opt/ufdbcat/bin/ufdbcatdd -v 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){return $re[1];}
		}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	
	@mkdir("$WORKDIR/opt/ufdbcat/bin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/etc/artica-postfix",0755,true);
	@mkdir("$WORKDIR/etc/artica-postfix/settings/Daemons",0755,true);
	
	@file_put_contents("$WORKDIR/etc/artica-postfix/UFDBCAT_INSTALLED",time());
	@file_put_contents("$WORKDIR/etc/artica-postfix/settings/Daemons/UfdbcatOnlyTCP",1);
	
	if(is_file("/opt/ufdbcat/bin/ufdbcatdd")){@unlink("/opt/ufdbcat/bin/ufdbcatdd");}
	@copy("/opt/ufdbcat/bin/ufdbguardd", "/opt/ufdbcat/bin/ufdbcatdd");
	@chmod("/opt/ufdbcat/bin/ufdbcatdd",0755);

	$f[]="/opt/ufdbcat/bin/ufdbcatdd";
	$f[]="/opt/ufdbcat/bin/ufdbGenTable";
	
	
	$version=UFDBCAT_VERSION();

	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
		
	}
	
	
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$tgzname="ufdbcat-$Architecture-$version.tar.gz";
	echo "Compressing $tgzname\n";
	if(is_file("/root/$tgzname")){@unlink("/root/$tgzname");}
	shell_exec("tar -czf /root/$tgzname *");
	echo "Compressing /root/$tgzname Done...\n";	
}


	

function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}








function factorize($path){
	$f=explode("\n",@file_get_contents($path));
	while (list ($num, $val) = each ($f)){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}










