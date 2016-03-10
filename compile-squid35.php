<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/ressources/class.compile_squid.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["SUBDIR"]="3.5";
/*
 * Haarp ./configure --prefix=/usr CXX=g++-4.4
 * 
 * 


unset($argv[0]);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
shell_exec("$php5 ".dirname(__FILE__)."/compile-squid34.php ".@implode(" ",$argv));
die();
 */
/* CICAP
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"
 * MODULES
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav
 * 
 Contrack tools
 
git clone git://git.netfilter.org/libnfnetlink
cd /root/libnfnetlink
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root
git clone git://git.netfilter.org/libmnl
cd /root/libmnl/ 
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
git clone git://git.netfilter.org/libnetfilter_conntrack
cd /root/libnetfilter_conntrack
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cttimeout/files/libnetfilter_cttimeout-1.0.0.tar.bz2
tar -xf libnetfilter_cttimeout-1.0.0.tar.bz2
cd /root/libnetfilter_cttimeout-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cthelper/files/libnetfilter_cthelper-1.0.0.tar.bz2
tar -xf libnetfilter_cthelper-1.0.0.tar.bz2
cd /root/libnetfilter_cthelper-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_queue/files/libnetfilter_queue-1.0.2.tar.bz2
tar -xf libnetfilter_queue-1.0.2.tar.bz2
cd /root/libnetfilter_queue-1.0.2
./configure --prefix=/usr
make && make install
cd /root/

cd /root/
 wget http://www.netfilter.org/projects/conntrack-tools/files/conntrack-tools-1.4.2.tar.bz2
 tar -xf conntrack-tools-1.4.2.tar.bz2
 
  
wget http://ftp.de.debian.org/debian/pool/main/s/squid/squid_2.7.STABLE9.orig.tar.gz
tar -xf squid_2.7.STABLE9.orig.tar.gz 
cd squid-2.7.STABLE9/
./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/squid27 --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --enable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=27

./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/streamcache --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --enable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=cache --program-prefix=stream
 
--- Mikrotik
./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/mikrotik-cache --sysconfdir=/etc/mikrotik-cache --localstatedir=/var/spool/mikrotik-cache --datadir=/usr/share/mikrotik-cache --with-pthreads --enab
le-async-io --disable-wccp --disable-wccp2 --disable-carp  --enable-referer-log  --enable-useragent-log --enable-linux-tproxy --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --enable-cache-digests --e
nable-referer-log --enable-useragent-log --enable-auth=basic --disable-unlinkd --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linu
x-gnu --program-suffix=-cache --program-prefix=mikrotik 
 
 
 
 ***************** RDP Proxy **************************
 *
 *git clone https://github.com/wallix/redemption
apt-get install libboost1.49-dev libboost-dev libboost-test-dev libboost-program-options-dev libssl-dev locales libkrb5-dev libgssglue-dev
bjam
bjam install 
 
 
svn co https://iscsitarget.svn.sourceforge.net/svnroot/iscsitarget iscsitarget 
 
 
C-ICAP
./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"

CI-ICAP MODULES
./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav
 
 
ntopng

libonig2
libhiredis0.10

https://packages.debian.org/wheezy/libhiredis0.10

svn co https://svn.ntop.org/svn/ntop/trunk/ntopng/
cd ntopng
./autogen.sh
./configure
make geoip
make
make install


git clone https://github.com/ntop/nDPI.git

git clone https://github.com/ntop/ntopng.git

cd ntopng
./autogen.sh

./configure

make

make install
 
 
 */



$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--configure-squid"){CONFIGURE_SQUID();exit;}
if($argv[1]=="--patch-squid"){PATCH_SQUID($argv[2]);exit;}
if($argv[1]=="--cross-packages"){crossroads_package();exit;}
if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--error-txt"){error_txt();exit;}
if($argv[1]=="--proftpd-package"){proftpd();exit;}
if($argv[1]=="--c-icap"){package_c_icap();exit;}
if($argv[1]=="--ufdb"){package_ufdbguard();exit;}
if($argv[1]=="--ecapclam"){ecap_clamav();exit;}
if($argv[1]=="--package"){create_package_squid();exit;}
if($argv[1]=="--c-icap-remove"){die();exit;}
if($argv[1]=="--msmtp"){package_msmtp();exit;}

if($argv[1]=="--nginx"){package_nginx();exit;}
if($argv[1]=="--nginx-compile"){nginx_compile();exit;}


if($argv[1]=="--ntopng"){ntopng();exit;}
if($argv[1]=="--dnsmasqver"){dnsmasq_lastver();exit;}
if($argv[1]=="--dnsmasq"){dnsmasq_compile();exit;}
if($argv[1]=="--rdpproxy"){package_redemption();exit;}
if($argv[1]=="--iscsi"){iscsi_target();exit;}
if($argv[1]=="--firehol"){firehol();exit;}








$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="squid-{$GLOBALS["SUBDIR"]}.0.0";
$Architecture=Architecture();

//$GLOBALS["FORCE_VERSION"]="squid-3.3.13.tar.gz";

if(!$GLOBALS["NO_COMPILE"]){
	
	if(!isset($GLOBALS["FORCE_VERSION"])){$v=latests();}else{$v=$GLOBALS["FORCE_VERSION"];}
	if(preg_match("#squid-(.+?)-#", $v,$re)){$dirsrc=$re[1];}
	system_admin_events("Downloading lastest {$GLOBALS["SUBDIR"]} file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die();}}
}

if(is_dir("/root/squid-builder")){shell_exec("$rm -rf /root/squid-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	
	if(!is_file("/usr/include/libecap/common/libecap.h")){
		shell_exec("$wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz -O /root/libecap-0.2.0.tar.gz");
		shell_exec("$tar -xf /root/libecap-0.2.0.tar.gz -C /root/");
		chdir("/root/libecap-0.2.0");
		shell_exec("./configure --prefix=/usr");
		shell_exec("make");
		shell_exec("make install");
		if(!is_file("/usr/include/libecap/common/libecap.h")){
			echo "libecap.h failed\n";
			die();
		}
	}
	
	
	if(is_dir("/root/$dirsrc")){
		echo "Removing /root/$dirsrc\n";
		shell_exec("/bin/rm -rf /root/$dirsrc");
		sleep(1);
	}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		system_admin_events("Detected new version $v", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading $v ...\n";
		shell_exec("$wget http://www.squid-cache.org/Versions/v3/{$GLOBALS["SUBDIR"]}/$v");
		if(!is_file("/root/$v")){
			system_admin_events("Downloading failed", __FUNCTION__, __FILE__, __LINE__, "software");
			echo "Downloading failed...\n";die();}
	}
	
	shell_exec("$tar -xf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/configure")){
		echo "/root/$dirsrc/configure no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/configure")){echo "$ligne/configure no such file\n";}else{
			chdir("$ligne");
			echo "[OK]: Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}


PATCH_SQUID($SOURCE_DIRECTORY);


$Debian=DebianVersion();

$Architecture=Architecture();


$configure=CONFIGURE_SQUID();

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo "Debian:\"$Debian\"\n\n$configure\n";die();}
if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	system($configure);
	echo "make...\n";
	if($GLOBALS["VERBOSE"]){system("make");}
	if(!$GLOBALS["VERBOSE"]){system("make");}
	system_admin_events("Installing the new squid-cache $v version", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "make install...\n";
	
	$unix=new unix();
	$squid3=$unix->find_program("squid3");
	if(is_file($squid3)){@unlink($squid3);}
	echo "Removing squid last install\n";
	remove_squid();
	echo "Make install\n";
	if($GLOBALS["VERBOSE"]){system("make install");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make install");}	
	
}
if(!is_file("/usr/sbin/squid")){
	system_admin_events("Installing the new squid-cache $v failed", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "Failed\n";}
	

if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/rm -rf /usr/share/squid3/errors/templates/*");}
shell_exec("/bin/chown -R squid:squid /usr/share/squid3");
create_package_squid($t);	
	
function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];	
}

function dnsmasq_compile(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	

$dnsmasq_lastver=dnsmasq_lastver();
if($dnsmasq_lastver==null){echo "No version...\n";return;}	
if(!is_file("/usr/include/dbus-1.0/dbus/dbus.h")){shell_exec("apt-get install -y libdbus-1-dev");}
if(!is_file("/usr/include/libnetfilter_conntrack/libnetfilter_conntrack.h")){shell_exec("apt-get install -y libnetfilter-conntrack-dev");}

echo "downloading $dnsmasq_lastver\n";
if(!is_file("/root/$dnsmasq_lastver")){
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/$dnsmasq_lastver -O /root/$dnsmasq_lastver");
}
$f[]="make install-i18n";
$f[]="PREFIX=/usr"; 
$f[]="CFLAGS=\"-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security -D_FORTIFY_SOURCE=2 -Wall -W\"";
$f[]="LDFLAGS=\"-Wl,-z,relro\""; 
$f[]="COPTS=\" -DHAVE_DBUS -DHAVE_CONNTRACK\" CC=gcc";

$dir=str_replace(".tar.gz", "", $dnsmasq_lastver);
if(is_dir("/root/$dir")){shell_exec("$rm -rf /root/$dir");}
shell_exec("$tar -xf /root/$dnsmasq_lastver -C /root/");

if(!is_dir("/root/$dir")){
	echo "/root/$dir no such dir\n";
	return;
}


@chdir("/root/$dir");
$cmd=@implode(" ", $f);
echo "$cmd\n";
shell_exec($cmd);
@chmod("/usr/sbin/dnsmasq",0755);

	
}

function dnsmasq_lastver(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/ -O /root/dnsmasq.html");
	$f=explode("\n",@file_get_contents("/root/dnsmasq.html"));
	while (list ($num, $ligne) = each ($f) ){
		if(!preg_match("#href=.*?dnsmasq-([0-9\.]+).tar.gz#",$ligne,$re));
		$binver=str_replace(".", "", trim($re[1]));
		$R[$binver]="dnsmasq-{$re[1]}.tar.gz";
			
		
		
		
	}
	krsort($R);
	while (list ($num, $ligne) = each ($R) ){
		return $ligne;
	}
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

function squid_version(){
	exec("/usr/sbin/squid -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			return trim($re[1]);
		}
	}
	
}

function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	echo "Downloading latests index file Versions/v3/{$GLOBALS["SUBDIR"]}/\n";
	@unlink("/tmp/index.html");
	shell_exec("$wget http://www.squid-cache.org/Versions/v3/{$GLOBALS["SUBDIR"]}/ -O /tmp/index.html");
	
	$CURMAIN=0;
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#<a href=\"squid-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			
			if(preg_match("#^([0-9\.]+)-#", $ve,$ri)){
				$MAIN_VERSION=str_replace(".", "", $ri[1]);
			}else{
				$MAIN_VERSION=str_replace(".", "", $ve);
			}
			
			if($MAIN_VERSION>=$CURMAIN){
				$CURMAIN=$MAIN_VERSION;
			}else{
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo  "$ve = MAIN=$MAIN_VERSION\n";}
			$STT=explode(".", $ve);
			$CountDeSTT=count($STT);
			if($CountDeSTT<4){$ve="{$ve}.00";}
			$veOrg=$ve;
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			if($GLOBALS["VERBOSE"]){echo "Add version $veOrg -> `$ve`\n";}
			$file="squid-{$re[1]}.tar.gz";
			$versions[$ve]=$file;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file $CountDeSTT points\n";}
		}else{
			
		}
		
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}`\n";
	return $vv[0];
}


function crossroads_package(){
$Architecture=Architecture();	
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
$unix=new unix();
$tar=$unix->find_program("tar");
$f[]="/usr/sbin/xrctl";
$f[]="/usr/share/man/man1/xr.1";
$f[]="/usr/share/man/man1/xrctl.1";
$f[]="/usr/share/man/man5/xrctl.xml.5";
$f[]="/usr/sbin/xr";
@mkdir("/root/crossroads",0755,true);
while (list ($num, $file) = each ($f)){
	$dir=dirname($file);
	@mkdir("/root/crossroads$dir",0755,true);
	@copy($file, "/root/crossroads$file");

}
	chdir("/root/crossroads");
	shell_exec("$tar -czf crossroads-$Architecture.tar.gz *");

	
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

function serialize_tests(){
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]="this is the text";
	$array["function"]="this is the function";
	$array["file"]="this is the process";
	$array["line"]="this is the line";
	$array["category"]="this is the category";
	$serialize=serialize($array);
	echo $serialize;
	
}










function c_cicap_remove(){
	die();

}




function ecap_clamav(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");	
	chdir("/root");
	shell_exec("$rm -rf /root/libecap-0.2.0 >/dev/null 2>&1");
	@unlink("/root/libecap-0.2.0.tar.gz");
	echo "Download libecap-0.2.0.tar.gz\n";
	shell_exec("wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz");
	echo "extracting libecap-0.2.0.tar.gz\n";
	shell_exec("$tar -xf libecap-0.2.0.tar.gz");
	if(!is_dir("/root/libecap-0.2.0")){echo "Failed\n";return;}
	chdir("/root/libecap-0.2.0");
	echo "Configuring....\n";
	shell_exec("./configure --prefix=/usr --includedir=\"\${prefix}/include\" --mandir=\"\${prefix}/share/man\" --infodir=\"\${prefix}/share/info\" --sysconfdir=/etc --localstatedir=/var --libexecdir=\"\${prefix}/lib\"");
	if(!is_file("/root/libecap-0.2.0/Makefile")){echo "Failed\n";return;}
	echo "Make....\n";
	shell_exec("make");
	shell_exec("make install");
	mkdir("/root/ecapav/usr/include/libecap/common",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/adapter",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/host",0755,true);
	mkdir("/root/ecapav/usr/lib",0755,true);
	mkdir("/root/ecapav/usr/libexec/squid",0755,true);
	
	shell_exec("$cp -a /usr/include/libecap/common/* /root/ecapav/usr/include/libecap/common/");
	shell_exec("$cp -a /usr/include/libecap/adapter/* /root/ecapav/usr/include/libecap/adapter/");
	shell_exec("$cp -a /usr/include/libecap/host/* /root/ecapav/usr/include/libecap/host/");
	shell_exec("$cp -a /usr/lib/libecap.so.2.0.0 /root/ecapav/usr/lib/libecap.so.2.0.0");
	shell_exec("$cp -a /usr/lib/libecap.so.2 /root/ecapav/usr/lib/libecap.so.2");
	shell_exec("$cp -a /usr/lib/libecap.so /root/ecapav/usr/lib/libecap.so");
	shell_exec("$cp -a /usr/lib/libecap.la /root/ecapav/usr/lib/libecap.la");
	
	
	chdir("/root");
	echo "Download squid-ecap-av-1.0.3.tar.bz2\n";
	@unlink("/root/squid-ecap-av-1.0.3.tar.bz2");
	shell_exec("wget http://www.articatech.net/download/squid-ecap-av-1.0.3.tar.bz2");
	echo "extracting squid-ecap-av-1.0.3.tar.bz2\n";
	shell_exec("$tar -xf squid-ecap-av-1.0.3.tar.bz2");
	if(!is_dir("/root/squid-ecap-av-1.0.3")){echo "Failed\n";return;}
	chdir("/root/squid-ecap-av-1.0.3");
	echo "cmake\n";
	shell_exec("cmake -DCMAKE_INSTALL_PREFIX=/usr");
	echo "Make....\n";
	shell_exec("make");
	echo "Make install\n";
	shell_exec("make install");
	shell_exec("$cp -a /usr/libexec/squid/ecap_adapter_av.so /root/ecapav/usr/libexec/squid/ecap_adapter_av.so");
	
}
function package_msmtp_version(){
	exec("/root/msmtp-compiled/usr/bin/msmtp --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#msmtp version.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}

function package_msmtp(){
	$base="/root/msmtp-compiled";
	shell_exec("/bin/rm -rf $base");
	
	/* git clone git://git.code.sf.net/p/msmtp/code msmtp
	 *./configure  --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/gsasl"  --disable-dependency-tracking
	 */
	

	$f[]="/usr/share/info/msmtp.info";
	$f[]="/usr/bin/msmtp";
	$f[]="/usr/share/gettext/po";

	while (list ($num, $filename) = each ($f)){

		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			shell_exec("/bin/cp -rf $filename/ $base/$filename/");
			continue;
		}

		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		shell_exec("/bin/cp -f $filename $base/$dirname/");

	}

	$Architecture=Architecture();
	$version=package_msmtp_version();
	chdir($base);
	shell_exec("tar -czf msmtp-$Architecture-$version.tar.gz *");
	shell_exec("/bin/cp msmtp-$Architecture-$version.tar.gz /root/");
	echo "/root/msmtp-$Architecture-$version.tar.gz done\n";

}



function package_freerdp(){
/* 
 * 
 * 
 * 
 *
 * 
 * 
apt-get install build-essential git-core cmake libssl-dev libx11-dev libxext-dev libxinerama-dev \
  libxcursor-dev libxdamage-dev libxv-dev libxkbfile-dev libasound2-dev libcups2-dev libxml2 libxml2-dev \
  libxrandr-dev libgstreamer0.10-dev libgstreamer-plugins-base0.10-dev libxi-dev libavutil-dev libavcodec-dev ibcunit1-dev libdirectfb-dev xmlto doxygen libxtst-dev
uri: https://github.com/FreeRDP/FreeRDP/zipball/master
apt-get install libpcsclite-dev libasound2-dev libxtst-dev
cmake -DCMAKE_BUILD_TYPE=Debug -DWITH_DEBUG_CERTIFICATE=ON -DWITH_PCSC:BOOL=OFF -DWITH_DEBUG_CHANNELS=ON WITH_DEBUG_CLIPRDR=ON -DWITH_DEBUG_DVC=ON -DWITH_DEBUG_GDI=ON -DWITH_DEBUG_KBD=ON -DWITH_DEBUG_LICENSE=ON -DWITH_DEBUG_NEGO=ON -DWITH_DEBUG_NLA=ON -DWITH_DEBUG_NTLM=ON -DWITH_DEBUG_ORDERS=ON -DWITH_DEBUG_RAIL=ON -DWITH_DEBUG_RDP=ON -DWITH_DEBUG_REDIR=ON -DWITH_DEBUG_RFX=ON -DWITH_DEBUG_SCARD=ON -DWITH_DEBUG_SVC=ON -DWITH_DEBUG_TRANSPORT=ON -DWITH_DEBUG_TSG=ON -DWITH_DEBUG_WND=ON -DWITH_DEBUG_X11=ON -DWITH_DEBUG_X11_CLIPRDR=ON -DWITH_DEBUG_X11_LOCAL_MOVESIZE=ON -DWITH_DEBUG_XV=ON -DWITH_FFMPEG=OFF -DWITH_JPEG=ON -DWITH_MANPAGES=ON -DWITH_PCSC=ON -DWITH_PROFILER=ON -DWITH_PULSEAUDIO=ON -DWITH_SERVER=ON -DWITH_SSE2=ON -DWITH_SSE2_TARGET=ON -DWITH_THIRD_PARTY=ON -DWITH_ALSA=ON -DWITH_XTEST=ON .
make
make install
*/
	
	$f["/usr/local/include/winpr/crt.h"]=true;
	$f["/usr/local/include/winpr/stream.h"]=true;
	$f["/usr/local/include/winpr/winhttp.h"]=true;
	$f["/usr/local/include/winpr/print.h"]=true;
	$f["/usr/local/include/winpr/nt.h"]=true;
	$f["/usr/local/include/winpr/interlocked.h"]=true;
	$f["/usr/local/include/winpr/spec.h"]=true;
	$f["/usr/local/include/winpr/sam.h"]=true;
	$f["/usr/local/include/winpr/ndr.h"]=true;
	$f["/usr/local/include/winpr/timezone.h"]=true;
	$f["/usr/local/include/winpr/winsock.h"]=true;
	$f["/usr/local/include/winpr/cmdline.h"]=true;
	$f["/usr/local/include/winpr/pipe.h"]=true;
	$f["/usr/local/include/winpr/security.h"]=true;
	$f["/usr/local/include/winpr/io.h"]=true;
	$f["/usr/local/include/winpr/wtsapi.h"]=true;
	$f["/usr/local/include/winpr/handle.h"]=true;
	$f["/usr/local/include/winpr/input.h"]=true;
	$f["/usr/local/include/winpr/rpc.h"]=true;
	$f["/usr/local/include/winpr/collections.h"]=true;
	$f["/usr/local/include/winpr/platform.h"]=true;
	$f["/usr/local/include/winpr/pool.h"]=true;
	$f["/usr/local/include/winpr/error.h"]=true;
	$f["/usr/local/include/winpr/windows.h"]=true;
	$f["/usr/local/include/winpr/memory.h"]=true;
	$f["/usr/local/include/winpr/file.h"]=true;
	$f["/usr/local/include/winpr/heap.h"]=true;
	$f["/usr/local/include/winpr/asn1.h"]=true;
	$f["/usr/local/include/winpr/schannel.h"]=true;
	$f["/usr/local/include/winpr/config.h"]=true;
	$f["/usr/local/include/winpr/winpr.h"]=true;
	$f["/usr/local/include/winpr/wlog.h"]=true;
	$f["/usr/local/include/winpr/sspi.h"]=true;
	$f["/usr/local/include/winpr/sysinfo.h"]=true;
	$f["/usr/local/include/winpr/string.h"]=true;
	$f["/usr/local/include/winpr/wtypes.h"]=true;
	$f["/usr/local/include/winpr/midl.h"]=true;
	$f["/usr/local/include/winpr/tchar.h"]=true;
	$f["/usr/local/include/winpr/thread.h"]=true;
	$f["/usr/local/include/winpr/path.h"]=true;
	$f["/usr/local/include/winpr/bcrypt.h"]=true;
	$f["/usr/local/include/winpr/endian.h"]=true;
	$f["/usr/local/include/winpr/crypto.h"]=true;
	$f["/usr/local/include/winpr/ntlm.h"]=true;
	$f["/usr/local/include/winpr/registry.h"]=true;
	$f["/usr/local/include/winpr/dsparse.h"]=true;
	$f["/usr/local/include/winpr/synch.h"]=true;
	$f["/usr/local/include/winpr/credentials.h"]=true;
	$f["/usr/local/include/winpr/sspicli.h"]=true;
	$f["/usr/local/include/winpr/environment.h"]=true;
	$f["/usr/local/include/winpr/library.h"]=true;
	$f["/usr/local/include/winpr/credui.h"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-synch.so"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-input.so"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-handle.so"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-environment.so"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-thread.so"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-error.so"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pool.so"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crt.so"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-file.so"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-io.so"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-path.so"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-heap.so"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-library.so"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-com.so"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credui.so"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-registry.so"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-utils.so"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-nt.so"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-makecert-tool.a"]=true;
	$f["/usr/local/include/freerdp/types.h"]=true;
	$f["/usr/local/include/freerdp/api.h"]=true;
	$f["/usr/local/include/freerdp/peer.h"]=true;
	$f["/usr/local/include/freerdp/dvc.h"]=true;
	$f["/usr/local/include/freerdp/svc.h"]=true;
	$f["/usr/local/include/freerdp/rail.h"]=true;
	$f["/usr/local/include/freerdp/secondary.h"]=true;
	$f["/usr/local/include/freerdp/window.h"]=true;
	$f["/usr/local/include/freerdp/primitives.h"]=true;
	$f["/usr/local/include/freerdp/primary.h"]=true;
	$f["/usr/local/include/freerdp/pointer.h"]=true;
	$f["/usr/local/include/freerdp/input.h"]=true;
	$f["/usr/local/include/freerdp/extension.h"]=true;
	$f["/usr/local/include/freerdp/error.h"]=true;
	$f["/usr/local/include/freerdp/message.h"]=true;
	$f["/usr/local/include/freerdp/scancode.h"]=true;
	$f["/usr/local/include/freerdp/settings.h"]=true;
	$f["/usr/local/include/freerdp/altsec.h"]=true;
	$f["/usr/local/include/freerdp/constants.h"]=true;
	$f["/usr/local/include/freerdp/client.h"]=true;
	$f["/usr/local/include/freerdp/addin.h"]=true;
	$f["/usr/local/include/freerdp/event.h"]=true;
	$f["/usr/local/include/freerdp/listener.h"]=true;
	$f["/usr/local/include/freerdp/graphics.h"]=true;
	$f["/usr/local/include/freerdp/freerdp.h"]=true;
	$f["/usr/local/include/freerdp/update.h"]=true;
	$f["/usr/local/include/freerdp/cache"]=true;
	$f["/usr/local/include/freerdp/cache/offscreen.h"]=true;
	$f["/usr/local/include/freerdp/cache/nine_grid.h"]=true;
	$f["/usr/local/include/freerdp/cache/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/cache/pointer.h"]=true;
	$f["/usr/local/include/freerdp/cache/brush.h"]=true;
	$f["/usr/local/include/freerdp/cache/cache.h"]=true;
	$f["/usr/local/include/freerdp/cache/glyph.h"]=true;
	$f["/usr/local/include/freerdp/cache/palette.h"]=true;
	$f["/usr/local/include/freerdp/codec"]=true;
	$f["/usr/local/include/freerdp/codec/rfx.h"]=true;
	$f["/usr/local/include/freerdp/codec/nsc.h"]=true;
	$f["/usr/local/include/freerdp/codec/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_enc.h"]=true;
	$f["/usr/local/include/freerdp/codec/dsp.h"]=true;
	$f["/usr/local/include/freerdp/codec/jpeg.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_dec.h"]=true;
	$f["/usr/local/include/freerdp/codec/color.h"]=true;
	$f["/usr/local/include/freerdp/codec/audio.h"]=true;
	$f["/usr/local/include/freerdp/crypto"]=true;
	$f["/usr/local/include/freerdp/crypto/per.h"]=true;
	$f["/usr/local/include/freerdp/crypto/der.h"]=true;
	$f["/usr/local/include/freerdp/crypto/certificate.h"]=true;
	$f["/usr/local/include/freerdp/crypto/ber.h"]=true;
	$f["/usr/local/include/freerdp/crypto/er.h"]=true;
	$f["/usr/local/include/freerdp/crypto/crypto.h"]=true;
	$f["/usr/local/include/freerdp/crypto/tls.h"]=true;
	$f["/usr/local/include/freerdp/gdi"]=true;
	$f["/usr/local/include/freerdp/gdi/region.h"]=true;
	$f["/usr/local/include/freerdp/gdi/16bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/line.h"]=true;
	$f["/usr/local/include/freerdp/gdi/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/gdi/drawing.h"]=true;
	$f["/usr/local/include/freerdp/gdi/32bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/shape.h"]=true;
	$f["/usr/local/include/freerdp/gdi/pen.h"]=true;
	$f["/usr/local/include/freerdp/gdi/dc.h"]=true;
	$f["/usr/local/include/freerdp/gdi/8bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/brush.h"]=true;
	$f["/usr/local/include/freerdp/gdi/gdi.h"]=true;
	$f["/usr/local/include/freerdp/gdi/clipping.h"]=true;
	$f["/usr/local/include/freerdp/gdi/palette.h"]=true;
	$f["/usr/local/include/freerdp/locale"]=true;
	$f["/usr/local/include/freerdp/locale/timezone.h"]=true;
	$f["/usr/local/include/freerdp/locale/keyboard.h"]=true;
	$f["/usr/local/include/freerdp/locale/locale.h"]=true;
	$f["/usr/local/include/freerdp/rail"]=true;
	$f["/usr/local/include/freerdp/rail/rail.h"]=true;
	$f["/usr/local/include/freerdp/rail/window.h"]=true;
	$f["/usr/local/include/freerdp/rail/icon.h"]=true;
	$f["/usr/local/include/freerdp/rail/window_list.h"]=true;
	$f["/usr/local/include/freerdp/utils"]=true;
	$f["/usr/local/include/freerdp/utils/tcp.h"]=true;
	$f["/usr/local/include/freerdp/utils/uds.h"]=true;
	$f["/usr/local/include/freerdp/utils/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/utils/signal.h"]=true;
	$f["/usr/local/include/freerdp/utils/pcap.h"]=true;
	$f["/usr/local/include/freerdp/utils/rail.h"]=true;
	$f["/usr/local/include/freerdp/utils/time.h"]=true;
	$f["/usr/local/include/freerdp/utils/svc_plugin.h"]=true;
	$f["/usr/local/include/freerdp/utils/list.h"]=true;
	$f["/usr/local/include/freerdp/utils/passphrase.h"]=true;
	$f["/usr/local/include/freerdp/utils/stopwatch.h"]=true;
	$f["/usr/local/include/freerdp/utils/msusb.h"]=true;
	$f["/usr/local/include/freerdp/utils/debug.h"]=true;
	$f["/usr/local/include/freerdp/utils/profiler.h"]=true;
	$f["/usr/local/include/freerdp/utils/event.h"]=true;
	$f["/usr/local/include/freerdp/client"]=true;
	$f["/usr/local/include/freerdp/client/tsmf.h"]=true;
	$f["/usr/local/include/freerdp/client/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/client/cmdline.h"]=true;
	$f["/usr/local/include/freerdp/client/channels.h"]=true;
	$f["/usr/local/include/freerdp/client/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/client/rdpei.h"]=true;
	$f["/usr/local/include/freerdp/client/file.h"]=true;
	$f["/usr/local/include/freerdp/client/disp.h"]=true;
	$f["/usr/local/include/freerdp/server"]=true;
	$f["/usr/local/include/freerdp/server/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/server/audin.h"]=true;
	$f["/usr/local/include/freerdp/server/channels.h"]=true;
	$f["/usr/local/include/freerdp/server/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels"]=true;
	$f["/usr/local/include/freerdp/channels/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/channels/channels.h"]=true;
	$f["/usr/local/include/freerdp/channels/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels/wtsvc.h"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-core.so"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-client.so"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-server.so"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp-server"]=true;
	$f["/etc/ld.so.conf.d/freerdp.conf"]=true;	
	
}

function nginx_compile(){
	$f[]="./configure";
	$f[]="--with-luajit ";
	$f[]="--sbin-path=/usr/sbin/nginx ";
	$f[]="--prefix=/usr/share/nginx ";
	$f[]="--conf-path=/etc/nginx/nginx.conf ";
	$f[]="--error-log-path=/var/log/nginx/error.log ";
	$f[]="--http-client-body-temp-path=/var/lib/nginx/body ";
	$f[]="--http-fastcgi-temp-path=/var/lib/nginx/fastcgi ";
	$f[]="--http-log-path=/var/log/nginx/access.log ";
	$f[]="--http-proxy-temp-path=/var/lib/nginx/proxy ";
	$f[]="--http-scgi-temp-path=/var/lib/nginx/scgi ";
	$f[]="--http-uwsgi-temp-path=/var/lib/nginx/uwsgi ";
	$f[]="--lock-path=/var/lock/nginx.lock ";
	$f[]="--pid-path=/var/run/nginx.pid";
	$f[]="--with-pcre-jit";
	$f[]="--with-debug";
	$f[]="--with-http_addition_module ";
	$f[]="--with-http_dav_module ";
	$f[]="--with-http_geoip_module ";
	$f[]="--with-http_gzip_static_module ";
	$f[]="--with-http_image_filter_module ";
	$f[]="--with-http_realip_module ";
	$f[]="--with-http_stub_status_module ";
	$f[]="--with-http_ssl_module ";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-ipv6";
	$f[]="--with-mail";
	$f[]="--with-mail_ssl_module";
	$f[]="--with-http_realip_module";
	$f[]="--with-http_addition_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-http_image_filter_module";
	$f[]="--with-http_geoip_module";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_dav_module";
	$f[]="--with-http_flv_module";
	$f[]="--with-http_gzip_static_module";
	$f[]="--with-http_random_index_module";
	$f[]="--with-http_secure_link_module";
	$f[]="--with-http_degradation_module";
	$f[]="--with-http_stub_status_module";
	echo @implode(" ", $f);
	
}

function package_nginx(){
	/*
	 * 
	 * apt-get install libxslt1-dev
	 * http://openresty.org/#Download
cd /root
wget http://openresty.org/download/ngx_openresty-1.7.2.1.tar.gz
tar -xf ngx_openresty-1.7.2.1.tar.gz
cd ngx_openresty-1.7.2.1.tar.gz
git config --global http.proxy http://192.168.1.245:3140

cd ngx_openresty-1.2.8.6/bundle
git clone git://github.com/yaoweibin/ngx_http_substitutions_filter_module.git
mv ngx_http_substitutions_filter_module ngx_http_substitutions_filter_module-1.0

git clone https://github.com/kvspb/nginx-auth-ldap.git  
mv nginx-auth-ldap ngx_http_auth_ldap_module-1.0

cd ngx_openresty-1.2.8.6/bundle
git clone  https://github.com/yaoweibin/nginx_tcp_proxy_module.git 
cd ...
patch -p1 < bundle/nginx_tcp_proxy_module/tcp.patch

wget http://mdounin.ru/hg/ngx_http_auth_request_module/archive/a29d74804ff1.tar.gz
tar -xf a29d74804ff1.tar.gz
mv ngx_http_auth_request_module-a29d74804ff1 ngx_http_auth_request_module-1.0



git clone https://github.com/pagespeed/ngx_pagespeed.git
mv nginx-auth-ldap ngx_http_auth_ldap_module-1.0


// MArche pas
wget https://github.com/downloads/SpiderLabs/ModSecurity/modsecurity-apache_2.7.1.tar.gz
tar -xf modsecurity-apache_2.7.1.tar.gz
cd modsecurity-apache_2.7.1
./configure --enable-standalone-module --prefix=/usr
make 
make install
cp -rfv /modsecurity-apache_2.7.1/nginx/modsecurity /root/ngx_openresty-1.4.3.9/bundle/
mv modsecurity ngx_http_modsecurity-1.0
//
 * 
 * 
 // NAXIS:
  * 
  * git clone git://github.com/nbs-system/naxsi.git
  * mv naxsi ngx_http_naxsi-1.0

 *****
dans configure
[http_substitutions_filter_module=>'ngx_http_substitutions_filter_module'],
[http_auth_ldap_module=>'ngx_http_auth_ldap_module'],
[http_naxsi_module=>'ngx_http_naxsi_module'],


// marche pas pour l'instant [http_modsecurity=>'ngx_http_modsecurity'],


// ne semble nécéssaire à partir de la v 1.3.9
[http_auth_request_module=>'ngx_http_auth_request_module'],

*****
* Inutile de rajouter l'option du module dans la ligne de commande.


	 */
	$base="/root/nginx-compiled";
	shell_exec("/bin/rm -rf $base");
	shell_exec("/bin/rm -rf /etc/nginx/sites-enabled/*");
	$f[]="/usr/share/nginx";
	$f[]="/usr/sbin/nginx";
	$f[]="/etc/nginx";
	$f[]="/var/lib/nginx";
	$f[]="/usr/lib/libxslt.a";
	$f[]="/usr/lib/libxslt.la";
	$f[]="/usr/lib/libxslt.so";
	$f[]="/usr/lib/libxslt.so.1";
	$f[]="/usr/lib/libxslt.so.1.1.26";
	$f[]="/usr/lib32/libxslt.so.1";
	$f[]="/usr/lib32/libxslt.so.1.1.26";
	$f[]="/usr/lib/mod_security2.so";
	$f[]="/usr/lib/mod_security2.la";
	$f[]="/usr/lib/mod_security2.a";
	$f[]="/usr/lib/standalone.so";
	$f[]="/usr/lib/standalone.la";
	$f[]="/usr/lib/standalone.a";

	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=package_nginx_version();	
	
	while (list ($num, $filename) = each ($f)){
	
		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rf $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rf $filename/* $base$filename/");
			continue;
		}
	
		
		if(is_file($filename)){
			$dirname=dirname($filename);
			if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
			echo "/bin/cp -f $filename $base$dirname/\n";
			shell_exec("/bin/cp -f $filename $base/$dirname/");
		}
	
	}

	chdir($base);
	if(is_file("$base/nginx-$Architecture-$version.tar.gz")){
		@unlink("$base/nginx-$Architecture-$version.tar.gz");
	}
	
	$filename="nginx-debian{$Debian}-$Architecture-$version.tar.gz";
	shell_exec("/bin/rm -rf $base/etc/nginx/sites-enabled/*");
	shell_exec("tar -czf $filename *");
	shell_exec("/bin/cp $filename /root/");
	echo "/root/$filename done\n";	
	
}
function package_nginx_version(){
	
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);
	
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}	
}


function ntopng_version(){


	$masterbin="/usr/local/bin/ntopng";
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS["ntopng_version"]=trim($re[1]);
			return $GLOBALS["ntopng_version"];
		}
	}
}

function ntopng(){
	$base="/root/ntopng-compiled";
	
	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=ntopng_version();
	
	
	$f[]="/usr/local/share/ntopng";
	$f[]="/usr/local/bin/ntopng";
	$f[]="/usr/local/man/man8/ntopng.8";
	$f[]="/usr/local/lib/libndpi.a";
	$f[]="/usr/local/lib/libndpi.la";
	$f[]="/usr/local/lib/libndpi.so";  
	$f[]="/usr/local/lib/libndpi.so.1";  
	$f[]="/usr/local/lib/libndpi.so.1.0.0";
	
	while (list ($num, $filename) = each ($f)){
	
		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rfd $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rfd $filename/* $base$filename/");
			continue;
		}
	
	
	if(is_file($filename)){
		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		echo "/bin/cp -fd $filename $base$dirname/\n";
		shell_exec("/bin/cp -fd $filename $base/$dirname/");
	}
	
	}
	
	chdir($base);
	if(is_file("$base/ntopng-$Architecture-$version.tar.gz")){
		@unlink("$base/ntopng-$Architecture-$version.tar.gz");
	}
	
	
	$filename="ntopng-debian{$Debian}-$Architecture-$version.tar.gz";
	if(is_file("$base/$filename")){@unlink("$base/$filename");}
	system("tar -czvf $filename *");
	shell_exec("/bin/cp $filename /root/");
	echo "/root/$filename done\n";	
	


}

function package_firehol_version(){
	
	exec("/usr/local/sbin/firehol 2>&1",$f);
	while (list ($num, $filename) = each ($f)){
		if(preg_match("#FireHOL\s+([0-9\.]+)#", $filename,$re)){
			return $re[1];
		}
	}
}

function firehol(){
	
	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=package_firehol_version();
	if($version==null){
		echo "*** Unable to stat version ****\n";
		return;
	}
	$f[]="/usr/local/etc/firehol";
	$f[]="/usr/local/share/doc/firehol";
	$f[]="/usr/local/sbin/firehol";
	$f[]="/usr/local/sbin/fireqos";
	$f[]="/etc/firehol";
	$base="/root/firehol";
	shell_exec("rm -rf $base");
	
	
	
	@mkdir("$base/usr/local/etc",0755,true);
	@mkdir("$base/usr/local/share/doc/firehol",0755,true);
	@mkdir("$base/usr/local/sbin",0755,true);
	
	
	shell_exec("/bin/cp -rfd /usr/local/etc/firehol $base/usr/local/etc/");
	shell_exec("/bin/cp -rfd /usr/local/share/doc/firehol $base/usr/local/share/doc/");
	shell_exec("/bin/cp -rf /usr/local/sbin/firehol $base/usr/local/sbin/firehol");
	shell_exec("/bin/cp -rf /usr/local/sbin/fireqos $base/usr/local/sbin/fireqos");
	
	chdir($base);
	$filename="firehol-debian{$Debian}-$Architecture-$version.tar.gz";
	@unlink("$base/$filename");
	shell_exec("tar -czf $filename *");
	@unlink("/root/$filename");
	shell_exec("/bin/cp $filename /root/");
	
	echo "/root/$filename done\n";
}

function iscsi_target(){
	
	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=package_nginx_version();
	$f[]="/usr/sbin/ietd";
	$f[]="/usr/sbin/ietadm";
	$f[]="/lib/modules/3.2.0-4-amd64/extra/iscsi/iscsi_trgt.ko";
	$f[]="/lib/modules/3.2.0-4-amd64/updates/dkms/iscsi_trgt.ko.distrib";
	$base="/root/ietadm";
	
	while (list ($num, $filename) = each ($f)){
	
		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rfd $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rfd $filename/* $base$filename/");
			continue;
		}
	
	
		if(is_file($filename)){
		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		echo "/bin/cp -fd $filename $base$dirname/\n";
		shell_exec("/bin/cp -fd $filename $base/$dirname/");
		}
	
		}
	
		chdir($base);
		if(is_file("$base/ietd-$Architecture.tar.gz")){
		@unlink("$base/ietd-$Architecture.tar.gz");
		}
	
		$filename="ietd-debian{$Debian}-$Architecture.tar.gz";
		shell_exec("tar -czf $filename *");
		shell_exec("/bin/cp $filename /root/");
		echo "/root/$filename done\n";	
}


function PATCH_SQUID($SOURCE_DIRECTORY){
	
	
	$replace='if (!Config.onoff.original_dst) {
		debugs(85, 3, HERE << "validate IP " << clientConn->local << " possible from Host:");
		http->request->flags.hostVerified = true;
		http->doCallouts();
		return;
	}
	else if (ia != NULL && ia->count > 0) {';
	
	
	if(!is_file("$SOURCE_DIRECTORY/src/client_side_request.cc")){
		echo "$SOURCE_DIRECTORY/src/client_side_request.cc No such file die()\n";
		die();
		
	}
	if(!is_file("$SOURCE_DIRECTORY/src/cf.data.pre")){
		echo "$SOURCE_DIRECTORY/src/cf.data.pre No such file die()\n";
		die();
	
	}
	if(!is_file("$SOURCE_DIRECTORY/src/SquidConfig.h")){
		echo "$SOURCE_DIRECTORY/src/SquidConfig.h No such file die()\n";
		die();
	
	}	
	
	
	
	$FOUND=false;
	$PATCHED=false;
	$linetofind="if (ia != NULL && ia->count > 0) {";
	$f=explode("\n",@file_get_contents("$SOURCE_DIRECTORY/src/client_side_request.cc"));
	while (list ($num, $ligne) = each ($f) ){
		
		if(strpos($ligne, "Config.onoff.original_dst")>0){
			echo "client_side_request.cc Already patched SKIP\n";
			$PATCHED=true;
			break;
		}
		
		if(strpos($ligne, $linetofind)>0){
			echo "Found $ligne Line $num\n";
			$FOUND=true;
			$f[$num]=$replace;
		}
		
		
	}
	
	if(!$PATCHED){
		if(!$FOUND){echo "FATAL, unable to patch client_side_request.cc\n";die();}
		@file_put_contents("$SOURCE_DIRECTORY/src/client_side_request.cc", @implode("\n", $f));
		echo "PATCHED  client_side_request.cc\n";
	}
	
	$replace="NAME: original_dst\nTYPE: onoff\nDEFAULT: off\nLOC: Config.onoff.original_dst\nDOC_START\nDOC_END\n\nNAME: client_dst_passthru\n";
	$f=explode("\n",@file_get_contents("$SOURCE_DIRECTORY/src/cf.data.pre"));
	
	$FOUND=false;
	$linetofind="NAME: client_dst_passthru";
	while (list ($num, $ligne) = each ($f) ){
		if(strpos($ligne, "Config.onoff.original_dst")>0){
			echo "Already patched!! please restart with a new freshed source package\n";
			die();
		}
		if(strpos(" $ligne", $linetofind)>0){
			echo "Found $ligne Line $num\n";
			$FOUND=true;
			$f[$num]=$replace;
		}
		
	}
	if(!$FOUND){echo "FATAL, unable to patch cf.data.pre\n";die();}
	@file_put_contents("$SOURCE_DIRECTORY/src/cf.data.pre", @implode("\n", $f));
	echo "PATCHED cf.data.pre\n";
	
	
	$replace="int original_dst;\n\tint client_dst_passthru;";
	$linetofind="int client_dst_passthru;";

	$f=explode("\n",@file_get_contents("$SOURCE_DIRECTORY/src/SquidConfig.h"));
	
	$FOUND=false;

	while (list ($num, $ligne) = each ($f) ){
		if(strpos(" $ligne", "int original_dst")>0){
			echo "Already patched!! please restart with a new freshed source package\n";
			die();
		}
		if(strpos(" $ligne", $linetofind)>0){
			echo "Found $ligne Line $num\n";
			$FOUND=true;
			$f[$num]=$replace;
		}
	
		}
		if(!$FOUND){echo "FATAL, unable to patch SquidConfig.h\n";die();}
		@file_put_contents("$SOURCE_DIRECTORY/src/SquidConfig.h", @implode("\n", $f));
		echo "PATCHED  SquidConfig.h\n";
	
}
function CONFIGURE_SQUID(){
	$Debian=DebianVersion();
	
	$Architecture=Architecture();
	
	/* NETTLE !!
	 * http://www.linuxfromscratch.org/blfs/view/svn/postlfs/nettle.html
	 */
	
	
	$CFLAGS[]="#!/bin/sh";
	$CFLAGS[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$CFLAGS[]="CFLAGS=\"-g -O2 -fPIE -fstack-protector -DNUMTHREADS=256 --param=ssp-buffer-size=4 -Wformat -Werror=format-security -Wall\"";
	$CFLAGS[]="CXXFLAGS=\"-g -O2 -fPIE -DNUMTHREADS=256 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security\"";
	$CFLAGS[]="CPPFLAGS=\"-D_FORTIFY_SOURCE=2\" LDFLAGS=\"-fPIE -pie -Wl,-z,relro -Wl,-z,now\"";
	$CFLAGS[]="echo \$CFLAGS";
	$CFLAGS[]="echo \$CXXFLAGS";
	$CFLAGS[]="echo \$CPPFLAGS";
	$CFLAGS[]="";
	@file_put_contents("/tmp/flags.sh", @implode("\n", $CFLAGS));
	$CFLAGS=array();
	@chmod("/tmp/flags.sh",0755);
	system("/tmp/flags.sh");
	
	
	$cmds[]="--prefix=/usr";
	if($Architecture==64){
		$cmds[]="--build=x86_64-linux-gnu";
	}
	
	$cmds[]="--includedir=\${prefix}/include";
	$cmds[]="--mandir=\${prefix}/share/man";
	$cmds[]="--infodir=\${prefix}/share/info";
	$cmds[]="--localstatedir=/var";
	$cmds[]="--libexecdir=\${prefix}/lib/squid3";
	$cmds[]="--disable-maintainer-mode";
	$cmds[]="--disable-dependency-tracking";
	$cmds[]="--srcdir=.";
	$cmds[]="--datadir=/usr/share/squid3";
	$cmds[]="--sysconfdir=/etc/squid3";
	$cmds[]="--enable-gnuregex";
	$cmds[]="--enable-removal-policy=heap";
	$cmds[]="--enable-follow-x-forwarded-for";
	$cmds[]="--enable-cache-digests";
	$cmds[]="--enable-http-violations";
	$cmds[]="--enable-removal-policies=lru,heap";
	$cmds[]="--enable-arp-acl";
	$cmds[]="--enable-truncate";
	$cmds[]="--with-large-files";
	$cmds[]="--with-pthreads";
	$cmds[]="--enable-esi";
	$cmds[]="--enable-storeio=aufs,diskd,ufs,rock";
	$cmds[]="--enable-x-accelerator-vary";
	$cmds[]="--with-dl";
	$cmds[]="--enable-linux-netfilter";
	if($Debian>6){
		$cmds[]="--with-netfilter-conntrack";
	}
	$cmds[]="--enable-wccpv2";
	$cmds[]="--enable-eui";
	$cmds[]="--enable-auth";
	$cmds[]="--enable-auth-basic";
	$cmds[]="--enable-snmp";
	$cmds[]="--enable-icmp";
	$cmds[]="--enable-auth-digest";
	$cmds[]="--enable-log-daemon-helpers";
	$cmds[]="--enable-url-rewrite-helpers";
	$cmds[]="--enable-auth-ntlm";
	$cmds[]="--with-default-user=squid";
	$cmds[]="--enable-icap-client";
	$cmds[]="--enable-cache-digests";
	$cmds[]="--enable-poll";
	$cmds[]="--enable-epoll";
	$cmds[]="--enable-async-io=128";
	$cmds[]="--enable-zph-qos";
	$cmds[]="--enable-delay-pools";
	$cmds[]="--enable-http-violations";
	$cmds[]="--enable-url-maps";
	$cmds[]="--enable-ecap";
	$cmds[]="--enable-ssl";
	$cmds[]="--with-openssl";
	$cmds[]="--enable-ssl-crtd";
	$cmds[]="--enable-xmalloc-statistics";
	$cmds[]="--enable-ident-lookups";
	$cmds[]="--with-filedescriptors=65536";
	$cmds[]="--with-aufs-threads=128";
	$cmds[]="--disable-arch-native";
	
	
	//$cmds[]="--disable-ipv6";
	
	
	//CPPFLAGS="-I../libltdl"
	
	
	
	$configure="./configure ". @implode(" ", $cmds);
	echo "apt-get install libssl-dev conntrack libnetfilter-conntrack-dev libcap-dev\n";
	echo "***************************************************************\n\n\n$configure\n\n***************************************************************\n";
	return $configure;
}




