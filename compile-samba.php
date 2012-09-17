<?php
/*
apt-get install libtalloc2
mkdir /etc/samba
mkdir /var/log/samba/
mkdir /var/run/samba
touch /etc/printcap
*/
//http://www.samba.org/samba/ftp/stable/samba-3.6.6.tar.gz

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

//http://ftp.samba.org/pub/samba/stable/


$dirsrc="samba-0.0.0";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){
	$v=latests();
	if(preg_match("#samba-(.+?)#", $v,$re)){$dirsrc=$re[1];}
	ufdbguard_admin_events("Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die();}}
}

if(is_dir("/root/samba-builder")){shell_exec("$rm -rf /root/samba-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		echo "Downloading $v ...\n";
		shell_exec("$wget http://ftp.samba.org/pub/samba/stable/$v");
		if(!is_file("/root/$v")){echo "Downloading failed...\n";die();}
	}
	
	shell_exec("$tar -xf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/configure")){
		echo "/root/$dirsrc/configure no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/source3/configure")){echo "$ligne/source3/configure no such file\n";}else{
			chdir("$ligne");echo "Change to dir $ligne/source3\n";
			$SOURCE_DIRECTORY=$ligne."/source3";
			$SOURCESOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}

$SOURCE_DIRECTORY2=dirname($SOURCE_DIRECTORY);
echo "Source directory: $SOURCE_DIRECTORY ($SOURCE_DIRECTORY2)\n";
shell_exec("/usr/share/artica-postfix/bin/artica-make APP_CTDB");

chdir($SOURCE_DIRECTORY);
if(is_file("$SOURCE_DIRECTORY/autogen.sh")){
	echo "Executing autogen.sh\n";
	exec("./autogen.sh",$results);
	while (list ($num, $ligne) = each ($results) ){
		echo "autogen.sh::".$ligne."\n";
	}
	
}else{
	echo "$SOURCE_DIRECTORY/autogen.sh no such file\n";
}

$cmds[]='./configure';
$cmds[]=' --with-fhs';
$cmds[]=' --enable-shared';
$cmds[]=' --enable-static';
$cmds[]=' --disable-pie';
$cmds[]=' --prefix=/usr';
$cmds[]=' --sysconfdir=/etc';
$cmds[]=' --libdir=/usr/lib';
$cmds[]=' --with-privatedir=/etc/samba';
$cmds[]=' --with-piddir=/var/run/samba';
$cmds[]=' --localstatedir=/var';
$cmds[]=' --with-rootsbindir=/sbin';
$cmds[]=' --with-pammodulesdir=/lib/security';
$cmds[]=' --with-pam';
$cmds[]=' --with-syslog';
$cmds[]=' --with-utmp';
$cmds[]=' --with-readline';
$cmds[]=' --with-pam_smbpass';
$cmds[]=' --with-libsmbclient';
$cmds[]=' --with-winbind';
if(is_file("/usr/include/ctdb.h")){
	$cmds[]=" --with-cluster-support";
}
$cmds[]=' --with-shared-modules=idmap_rid,idmap_ad';
$cmds[]=' --with-automount';
$cmds[]=' --with-ldap';
$cmds[]=' --with-ads';
$cmds[]=' --with-dnsupdate';
$cmds[]=' --with-smbmount';
$cmds[]=' --with-cifsmount';
$cmds[]=' --with-acl-support';
$cmds[]=' --with-dnsupdate';
$cmds[]=' --with-syslog';
$cmds[]=' --with-quotas';
$cmds[]=' --with-automount'; 





$configure=@implode(" ", $cmds);

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die();}

echo "Executing `$configure`\n";


if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	shell_exec($configure);
	echo "make...\n";
	shell_exec("make");
	echo "make install...\n";
	echo "Make install\n";
	shell_exec("make install");
}

	if(is_file("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so")){
		echo "Copy $SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so\n";
		@copy("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so", "/lib/libnss_wins.so");
		
	}
	if(is_file("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so")){
		echo "Copy $SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so\n";
		@copy("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so", "/lib/libnss_winbind.so");
		
	}	
	
	
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}


	create_package();
	@mkdir("/root/samba-builder/etc/init.d",0755,true);
	if(is_file("$SOURCE_DIRECTORY2/packaging/LSB/samba.sh")){
		shell_exec("/bin/cp $SOURCE_DIRECTORY2/packaging/LSB/samba.sh /root/samba-builder/etc/init.d/samba");
		@chmod("/root/samba-builder/etc/init.d/samba", 0755);
	}else{
		echo "$SOURCE_DIRECTORY2/packaging/LSB/samba.sh no such file";
	}



	$version=SAMBA_VERSION();
	
	echo "Building package Arch:$Architecture Version:$version\n";
	
	@chdir("/root/samba-builder");
	echo "Compressing sambac-$Architecture-$version.tar.gz\n";
	shell_exec("$tar -czf sambac-$Architecture-$version.tar.gz *");
	echo "Compressing sambac-$Architecture-$version.tar.gz Done...\n";
	if(is_file("/root/ftp-password")){
		echo "Uploading sambac-$Architecture-$version.tar.gz Done...\n";
		echo "/root/samba-builder/sambac-$Architecture-$version.tar.gz is now ready to be uploaded\n";
		shell_exec("curl -T /root/samba-builder/sambac-$Architecture-$version.tar.gz ftp://www.artica.fr/download/ --user ".@file_get_contents("/root/ftp-password"));
		if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
		
	}	

function SAMBA_VERSION(){
	
	$unix=new unix();
	$winbind=$unix->find_program("winbindd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
	
	
}





function create_package(){
	@mkdir('/root/samba-builder/usr/sbin',0755,true);
	@mkdir('/root/samba-builder/usr/bin',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/vfs',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/idmap',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/charset',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/auth',0755,true);
	@mkdir('/root/samba-builder/lib/security',0755,true);
	@mkdir('/root/samba-builder/usr/include',0755,true);
	@mkdir('/root/samba-builder/usr/lib',0755,true);
	@mkdir('/root/samba-builder/lib',0755,true);
	@mkdir('/root/samba-builder/usr/include',0755,true);
	@mkdir('/root/samba-builder/etc/ctdb/events.d',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/de/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ar/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/cs/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/da/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/es/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/fi/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/fr/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/hu/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/it/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ja/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ko/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/nb/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/nl/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/pl/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/pt_BR/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ru/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/sv/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/zh_CN/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/zh_TW/LC_MESSAGES',0755,true);	
	@mkdir('/root/samba-builder/usr/bin',0755,true);
	@mkdir('/root/samba-builder/usr/lib',0755,true);
	@mkdir('/root/samba-builder/usr/lib/php5/20090626+lfs',0755,true);
	@mkdir('/root/samba-builder/usr/lib/xapian-omega',0755,true);
	@mkdir('/root/samba-builder/usr/share/omega',0755,true);
	@mkdir('/root/samba-builder/usr/include/xapian',0755,true);	
	$f[]="/usr/sbin/smbd";
	$f[]="/usr/sbin/nmbd";
	$f[]="/usr/sbin/swat";
	$f[]="/usr/sbin/winbindd";
	$f[]="/usr/sbin/msktutil";
	$f[]="/usr/bin/wbinfo";
	$f[]="/usr/bin/smbclient";
	$f[]="/usr/bin/net";
	$f[]="/usr/bin/smbspool";
	$f[]="/usr/bin/testparm";
	$f[]="/usr/bin/smbstatus";
	$f[]="/usr/bin/smbget";
	$f[]="/usr/bin/smbta-util";
	$f[]="/usr/bin/smbcontrol";
	$f[]="/usr/bin/smbtree";
	$f[]="/usr/bin/tdbbackup";
	$f[]="/usr/bin/nmblookup";
	$f[]="/usr/bin/pdbedit";
	$f[]="/usr/bin/tdbdump";
	$f[]="/usr/bin/tdbrestore";
	$f[]="/usr/bin/tdbtool";
	$f[]="/usr/bin/smbpasswd";
	$f[]="/usr/bin/rpcclient";
	$f[]="/usr/bin/smbcacls";
	$f[]="/usr/bin/profiles";
	$f[]="/usr/bin/ntlm_auth";
	$f[]="/usr/bin/sharesec";
	$f[]="/usr/bin/smbcquotas";
	$f[]="/usr/bin/eventlogadm";
	$f[]="/usr/lib/samba/lowcase.dat";
	$f[]="/usr/lib/samba/upcase.dat";
	$f[]="/usr/lib/samba/valid.dat";
	$f[]="/usr/lib/samba/vfs/recycle.so";
	$f[]="/usr/lib/samba/vfs/audit.so";
	$f[]="/usr/lib/samba/vfs/extd_audit.so";
	$f[]="/usr/lib/samba/vfs/full_audit.so";
	$f[]="/usr/lib/samba/vfs/netatalk.so";
	$f[]="/usr/lib/samba/vfs/fake_perms.so";
	$f[]="/usr/lib/samba/vfs/default_quota.so";
	$f[]="/usr/lib/samba/vfs/readonly.so";
	$f[]="/usr/lib/samba/vfs/cap.so";
	$f[]="/usr/lib/samba/vfs/expand_msdfs.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy2.so";
	$f[]="/usr/lib/samba/vfs/xattr_tdb.so";
	$f[]="/usr/lib/samba/vfs/catia.so";
	$f[]="/usr/lib/samba/vfs/streams_xattr.so";
	$f[]="/usr/lib/samba/vfs/streams_depot.so";
	$f[]="/usr/lib/samba/vfs/readahead.so";
	$f[]="/usr/lib/samba/vfs/fileid.so";
	$f[]="/usr/lib/samba/vfs/preopen.so";
	$f[]="/usr/lib/samba/vfs/syncops.so";
	$f[]="/usr/lib/samba/vfs/acl_xattr.so";
	$f[]="/usr/lib/samba/vfs/acl_tdb.so";
	$f[]="/usr/lib/samba/vfs/smb_traffic_analyzer.so";
	$f[]="/usr/lib/samba/vfs/dirsort.so";
	$f[]="/usr/lib/samba/vfs/scannedonly.so";
	$f[]="/usr/lib/samba/vfs/crossrename.so";
	$f[]="/usr/lib/samba/vfs/linux_xfs_sgid.so";
	$f[]="/usr/lib/samba/vfs/time_audit.so";
	$f[]="/usr/lib/samba/idmap/rid.so";
	$f[]="/usr/lib/samba/idmap/autorid.so";
	$f[]="/usr/lib/samba/idmap/ad.so";
	$f[]="/usr/lib/samba/charset/CP850.so";
	$f[]="/usr/lib/samba/charset/CP437.so";
	$f[]="/usr/lib/samba/auth/script.so";
	$f[]="/usr/lib/samba/de.msg";
	$f[]="/usr/lib/samba/en.msg";
	$f[]="/usr/lib/samba/fi.msg";
	$f[]="/usr/lib/samba/fr.msg";
	$f[]="/usr/lib/samba/it.msg";
	$f[]="/usr/lib/samba/ja.msg";
	$f[]="/usr/lib/samba/nl.msg";
	$f[]="/usr/lib/samba/pl.msg";
	$f[]="/usr/lib/samba/ru.msg";
	$f[]="/usr/lib/samba/tr.msg";
	$f[]="/lib/security/pam_smbpass.so";
	$f[]="/lib/security/pam_winbind.so";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/include/talloc.h";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/include/tdb.h";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/usr/lib/libwbclient.a";
	$f[]="/usr/include/wbclient.h";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/include/netapi.h";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/include/libsmbclient.h";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/include/smb_share_modes.h";
	$f[]="/usr/share/locale/de/LC_MESSAGES/net.mo";
	$f[]="/usr/share/locale/ar/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/cs/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pt_BR/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.so.2";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.so.1";
	$f[]="/usr/lib/libcups.so.2";
	$f[]="/usr/lib/libavahi-client.so.3";
	$f[]="/usr/lib/libavahi-client.so.3.2.7";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/lib/libnss_winbind.so";
	$f[]="/lib/libnss_wins.so";
	$f[]="/usr/bin/ctdb";
	$f[]="/usr/bin/smnotify";
	$f[]="/usr/bin/ping_pong";
	$f[]="/usr/bin/ctdb_diagnostics";
	$f[]="/usr/bin/onnode";
	$f[]="/usr/include/ctdb.h";
	$f[]="/usr/include/ctdb_private.h";
	$f[]="/usr/sbin/ctdbd";	
	$f[]="/usr/share/locale/cs/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/eo/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ga/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/gl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/id/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/is/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/lv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pt/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ro/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/th/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/tr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/uk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/vi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/wa/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/popt.mo";
	$f[]="/usr/lib/libpopt.la";
	$f[]="/usr/lib/libpopt.so.0.0.0";
	$f[]="/usr/lib/libpopt.so.0";
	$f[]="/usr/lib/libpopt.so";
	$f[]="/usr/include/popt.h";
	$f[]="/usr/lib/libxapian.la";
	$f[]="/usr/lib/libxapian.so";
	$f[]="/usr/lib/libxapian.a";
	$f[]="/usr/lib/libxapian.so.22.5.0 ";
	$f[]="/usr/lib/libxapian.so.22";
	$f[]="/usr/bin/quartzcheck";
	$f[]="/usr/bin/quartzcheck";
	$f[]="/usr/bin/quartzcompact";
	$f[]="/usr/bin/quartzcompact";
	$f[]="/usr/bin/quartzdump";
	$f[]="/usr/bin/xapian-check";
	$f[]="/usr/bin/xapian-compact";
	$f[]="/usr/bin/xapian-inspect";
	$f[]="/usr/bin/xapian-progsrv";
	$f[]="/usr/bin/xapian-tcpsrv";
	$f[]="/usr/bin/copydatabase";
	$f[]="/usr/bin/delve";
	$f[]="/usr/bin/quest";
	$f[]="/usr/bin/simpleexpand";
	$f[]="/usr/bin/simpleindex";
	$f[]="/usr/bin/simplesearch";
	$f[]="/usr/bin/xapian-config";
	$f[]="/usr/include/xapian.h";
	$f[]="/usr/share/php5/xapian.php";
	$f[]="/usr/lib/php5/20090626+lfs/xapian.so";
	$f[]="/usr/lib/php5/20090626+lfs/xapian.la";
	$f[]="/usr/bin/xapian-check";
	$f[]="/usr/bin/xapian-compact";
	$f[]="/usr/bin/xapian-inspect";
	$f[]="/usr/bin/xapian-replicate";
	$f[]="/usr/bin/xapian-replicate-server";
	$f[]="/usr/bin/xapian-chert-update";
	$f[]="/usr/bin/xapian-progsrv";
	$f[]="/usr/bin/xapian-tcpsrv";
	$f[]="/usr/bin/dbi2omega ";
	$f[]="/usr/bin/htdig2omega ";
	$f[]="/usr/bin/mbox2omega";
	$f[]="/usr/bin/omindex";
	$f[]="/usr/bin/scriptindex";	
	
	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/samba-builder$dir/\n";
		if(!is_dir("/root/samba-builder$dir")){@mkdir("/root/samba-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/samba-builder$dir/");
		
	}
	
	shell_exec("/bin/cp -rfd /usr/lib/samba/* /root/samba-builder/usr/lib/samba/");
	shell_exec("/bin/cp -rfd /etc/ctdb/* /root/samba-builder/etc/ctdb/");
	shell_exec("/bin/cp -rfd /usr/lib/xapian-omega/* /root/samba-builder/usr/lib/xapian-omega/");
	shell_exec("/bin/cp -rfd /usr/share/omega/* /root/samba-builder/usr/share/omega/");	

	echo "Creating package done....\n";
}


	

function parse_install($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	$f=file($filename);
	
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#Installing (.+?)\s+as\s+(.+)#", $ligne,$re)){
			$re[1]=str_replace("///", "/", $re[2]);
			$target[]=$re[1];
			continue;
		}
		
		if(preg_match("#install\s+-c\s+.+?\/(.+?)\s+(.+)#", $ligne,$re)){
			$filename=$re[2]."/".basename($re[1]);
			$filename=str_replace("///", "/", $filename);
			$filename=str_replace("//", "/", $filename);
			$target[]=trim($filename);
		}
		
	}
	
	while (list ($num, $ligne) = each ($target) ){
		$dir=dirname($ligne);
		$mkdirs[trim($dir)]=true;
		$files[trim($ligne)]=true;
		
	}
	while (list ($num, $ligne) = each ($mkdirs) ){
		$tt="/root/samba-builder/$num";
		$tt=str_replace("//", "/", $tt);
		echo "@mkdir('$tt',0755,true);\n";
	}

	while (list ($num, $ligne) = each ($files) ){
			echo "\$f[]=\"$num\";\n";
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


function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	shell_exec("$wget http://ftp.samba.org/pub/samba/stable/ -O /tmp/index.html");
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#<a href=\"samba-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				if(strlen($ri[3])==1){$ri[3]="{$ri[3]}0";}
				$ve="{$ri[1]}.{$ri[2]}.{$ri[3]}";
				
			}
			
			
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			
			
			$file="samba-{$re[1]}.tar.gz";
			$versions[$ve]=$file;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file ({$ri[1]}.{$ri[2]}.{$ri[3]})\n";}
		}
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}`\n";
	return $vv[0];
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









