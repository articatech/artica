<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
echo " *********************** 1\n";
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

echo " *********************** 2\n";

if($argv[1]=='--compile'){CompileOpenVPN();die();}
if($argv[1]=='--package'){PackageOpenVPN();die();}



echo "????\n";

function CompileOpenVPN(){
	$unix=new unix();
	$git=$unix->find_program("git");
	if(!is_file("$git")){echo "git no such binary\n";return;}
	system("cd /root");
	if(is_dir("/root/openvpn")){system("rm -rf /root/openvpn");}
	system("$git clone https://github.com/OpenVPN/openvpn.git");
	system("cd openvpn");
	system("autoreconf -i -v -f");
	system("./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/openvpn  --host=x86_64-linux-gnu --build=x86_64-linux-gnu --prefix=/usr --mandir=\${prefix}/share/man --enable-iproute2 --with-plugindir=\${prefix}/lib/openvpn --includedir=\${prefix}/include/openvpn --enable-x509-alt-username --with-special-build=\"Artica Edition\"");
	system("make");
	system("make install");
	PackageOpenVPN();
}
function PackageOpenVPN(){
	echo "Build OpenVPN package\n";
	$unix=new unix();	
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$Architecture=xArchitecture();
	chdir("/root");
	if(is_dir("/root/openvpn-builder")){system("$rm -rf /root/openvpn-builder");}
	@mkdir("/root/openvpn-builder",0755,true);
	@mkdir("/root/openvpn-builder/usr/lib/openvpn",0755,true);
	@mkdir("/root/openvpn-builder/usr/sbin",0755,true);
	
	system("cp -rfd /usr/lib/openvpn/* /root/openvpn-builder/usr/lib/openvpn/");
	system("cp -rfd /usr/sbin/openvpn /root/openvpn-builder/usr/sbin/openvpn");
	
	system("cd /root/openvpn-builder/");
	@chdir("/root/openvpn-builder/");
	system("pwd");
	$version=xopenvpn_version();
	$arcg=xArchitecture();
	$filename="/root/openvpn-$arcg-$version.tar.gz";
	if(is_file($filename)){@unlink($filename);}
	echo "Compressing $filename\n";
	system("$tar czf $filename *");

}


function xopenvpn_version(){
	$unix=new unix();
	if(isset($GLOBALS["openvpn_version"])){return $GLOBALS["openvpn_version"];}
	$bin_path=$unix->find_program("openvpn");
	exec("$bin_path --version 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#OpenVPN\s+([0-9]+)\.([0-9]+)([a-z0-9\_\-\.]+)\s+#",$line,$re)){
			$GLOBALS["openvpn_version"]=$re[1].".{$re[2]}{$re[3]}";
			return $GLOBALS["openvpn_version"];
		}
	}
	
}


function xArchitecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}
?>