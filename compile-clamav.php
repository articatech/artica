<?php

/*./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/clamav --disable-maintainer-mode --disable-dependency-tracking --with-dbdir=/var/lib/clamav --sysconfdir=/etc/clamav --enable-milter --enable-dns-fix --with-gnu-ld
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



$GLOBALS["ROOT-DIR"]="/root/securities-plugins";

create_package();


function CLAMAV_VERSION(){
	$unix=new unix();
	$proftpd=$unix->find_program("clamav-config");
	exec("$proftpd --version 2>&1",$results);
	if(preg_match("#([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=CLAMAV_VERSION();
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/usr/bin",0755,true);
	@mkdir("$WORKDIR/usr/lib/python2.7/dist-packages/axl",0755,true);
	@mkdir("$WORKDIR/usr/lib/valvulad/modules",0755,true);
	@mkdir("$WORKDIR/usr/etc/valvula/mods-available",0755,true);
	@mkdir("$WORKDIR/usr/local/lib/perl/5.14.2/auto/Digest/SHA1",0755,true);
	@mkdir("$WORKDIR/usr/local/share/perl/5.14.2/MIME",0755,true);
	@mkdir("$WORKDIR/usr/local/share/perl/5.14.2/Net/DNS",0755,true);
	@mkdir("$WORKDIR/usr/local/share/perl/5.14.2/Math",0755,true);
	@mkdir("$WORKDIR/usr/local/share/perl/5.14.2/auto/Math/Round",0755,true);
	@mkdir("$WORKDIR/usr/local/share/perl/5.14.2/Archive/Zip",0755,true);
	@mkdir("$WORKDIR/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP",0755,true);
	@mkdir("$WORKDIR/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase",0755,true);
	@mkdir("$WORKDIR/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/Util",0755,true);
	@mkdir("$WORKDIR/usr/local/bin",0755,true);
	@mkdir("$WORKDIR/usr/local/sbin",0755,true);
	@mkdir("$WORKDIR/etc/mail",0755,true);
	$f[]="/usr/lib/libclamav.la";
	$f[]="/usr/lib/libclamav.so.6";	   
	$f[]="/usr/lib/libclamav.so.7";
	$f[]="/usr/lib/libclamunrar_iface.la";
	$f[]="/usr/lib/libclamunrar_iface.so.7";
	$f[]="/usr/lib/libclamunrar.la";
	$f[]="/usr/lib/libclamunrar.so.7";
	$f[]="/usr/lib/libclamav.so";
	$f[]="/usr/lib/libclamav.so.6.1.24";
	$f[]="/usr/lib/libclamav.so.7.1.1";
	$f[]="/usr/lib/libclamunrar_iface.so";
	$f[]="/usr/lib/libclamunrar_iface.so.7.1.1";
	$f[]="/usr/lib/libclamunrar.so";
	$f[]="/usr/lib/libclamunrar.so.7.1.1";
	$f[]="/usr/include/clamav.h";
	$f[]="/usr/bin/clamscan";
	$f[]="/usr/sbin/clamd";
	$f[]="/usr/bin/clamdscan";
	$f[]="/usr/bin/freshclam";
	$f[]="/usr/bin/sigtool";
	$f[]="/usr/bin/clamconf";
	$f[]="/usr/sbin/clamav-milter";
	$f[]="/usr/bin/clambc";
	$f[]="/usr/bin/clamsubmit";
	$f[]="/usr/bin/clamav-config";
	$f[]="/usr/lib/libaxl.a";
	$f[]="/usr/lib/libaxl-babel.la";
	$f[]="/usr/lib/libaxl-babel.so.0";
	$f[]="/usr/lib/libaxl.la";
	$f[]="/usr/lib/libaxl-ns.la";
	$f[]="/usr/lib/libaxl-ns.so.0";
	$f[]="/usr/lib/libaxl.so";
	$f[]="/usr/lib/libaxl.so.0.0.0";
	$f[]="/usr/lib/libaxl-babel.a";
	$f[]="/usr/lib/libaxl-babel.so";
	$f[]="/usr/lib/libaxl-babel.so.0.0.0";
	$f[]="/usr/lib/libaxl-ns.a";
	$f[]="/usr/lib/libaxl-ns.so";
	$f[]="/usr/lib/libaxl-ns.so.0.0.0";
	$f[]="/usr/lib/libaxl.so.0";
	$f[]="/usr/lib/libvalvula.a";
	$f[]="/usr/lib/libvalvulad.la";
	$f[]="/usr/lib/libvalvulad.so.0";
	$f[]="/usr/lib/libvalvula.la";
	$f[]="/usr/lib/libvalvula.so.0";
	$f[]="/usr/lib/libvalvulad.a";
	$f[]="/usr/lib/libvalvulad.so";
	$f[]="/usr/lib/libvalvulad.so.0.0.0";
	$f[]="/usr/lib/libvalvula.so";
	$f[]="/usr/lib/libvalvula.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-bwl.a";
	$f[]="/usr/lib/valvulad/mod-bwl.so";
	$f[]="/usr/lib/valvulad/mod-bwl.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-mquota.la";
	$f[]="/usr/lib/valvulad/mod-mquota.so.0";
	$f[]="/usr/lib/valvulad/mod-mw.a";
	$f[]="/usr/lib/valvulad/mod-mw.so";
	$f[]="/usr/lib/valvulad/mod-mw.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-slm.la";
	$f[]="/usr/lib/valvulad/mod-slm.so.0";
	$f[]="/usr/lib/valvulad/mod-test.a";
	$f[]="/usr/lib/valvulad/mod-test.so";
	$f[]="/usr/lib/valvulad/mod-test.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-ticket.la";
	$f[]="/usr/lib/valvulad/mod-ticket.so.0";
	$f[]="/usr/lib/valvulad/mod-bwl.la";
	$f[]="/usr/lib/valvulad/mod-bwl.so.0";
	$f[]="/usr/lib/valvulad/mod-mquota.a";
	$f[]="/usr/lib/valvulad/mod-mquota.so";
	$f[]="/usr/lib/valvulad/mod-mquota.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-mw.la";
	$f[]="/usr/lib/valvulad/mod-mw.so.0";
	$f[]="/usr/lib/valvulad/mod-slm.a";
	$f[]="/usr/lib/valvulad/mod-slm.so";
	$f[]="/usr/lib/valvulad/mod-slm.so.0.0.0";
	$f[]="/usr/lib/valvulad/mod-test.la";
	$f[]="/usr/lib/valvulad/mod-test.so.0";
	$f[]="/usr/lib/valvulad/mod-ticket.a";
	$f[]="/usr/lib/valvulad/mod-ticket.so";
	$f[]="/usr/lib/valvulad/mod-ticket.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-bwl.a";
	$f[]="/usr/lib/valvulad/modules/mod-mquota.la";
	$f[]="/usr/lib/valvulad/modules/mod-mw.so";
	$f[]="/usr/lib/valvulad/modules/mod-slm.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-test.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-bwl.la";
	$f[]="/usr/lib/valvulad/modules/mod-mquota.so";
	$f[]="/usr/lib/valvulad/modules/mod-mw.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-slm.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-ticket.a";
	$f[]="/usr/lib/valvulad/modules/mod-bwl.so";
	$f[]="/usr/lib/valvulad/modules/mod-mquota.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-mw.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-test.a";
	$f[]="/usr/lib/valvulad/modules/mod-ticket.la";
	$f[]="/usr/lib/valvulad/modules/mod-bwl.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-mquota.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-slm.a";
	$f[]="/usr/lib/valvulad/modules/mod-test.la";
	$f[]="/usr/lib/valvulad/modules/mod-ticket.so";
	$f[]="/usr/lib/valvulad/modules/mod-bwl.so.0.0.0";
	$f[]="/usr/lib/valvulad/modules/mod-mw.a";
	$f[]="/usr/lib/valvulad/modules/mod-slm.la";
	$f[]="/usr/lib/valvulad/modules/mod-test.so";
	$f[]="/usr/lib/valvulad/modules/mod-ticket.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-mquota.a";
	$f[]="/usr/lib/valvulad/modules/mod-mw.la";
	$f[]="/usr/lib/valvulad/modules/mod-slm.so";
	$f[]="/usr/lib/valvulad/modules/mod-test.so.0";
	$f[]="/usr/lib/valvulad/modules/mod-ticket.so.0.0.0";
	$f[]="/usr/lib/python2.7/dist-packages/axl/__init__.py";
	$f[]="/usr/lib/python2.7/dist-packages/axl/libpy_axl.a";
	$f[]="/usr/lib/python2.7/dist-packages/axl/libpy_axl.la";
	$f[]="/usr/lib/python2.7/dist-packages/axl/libpy_axl.so";
	$f[]="/usr/lib/python2.7/dist-packages/axl/libpy_axl.so.0";
	$f[]="/usr/lib/python2.7/dist-packages/axl/libpy_axl.so.0.0.0";
	$f[]="/usr/local/lib/perl/5.14.2/auto/Digest/SHA1/SHA1.so";
	$f[]="/usr/local/lib/perl/5.14.2/auto/Digest/SHA1/SHA1.bs";
	$f[]="/usr/local/lib/perl/5.14.2/Digest/SHA1.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Head.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Words.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Body.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Tools.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Parser.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/WordDecoder.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Entity.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Field/ContDisp.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Field/ContType.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Field/ConTraEnc.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Field/ParamVal.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Parser/Results.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Parser/Reader.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Parser/Filer.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/UU.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/BinHex.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/Gzip64.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/Binary.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/Base64.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/QuotedPrint.pm";
	$f[]="/usr/local/share/perl/5.14.2/MIME/Decoder/NBit.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/Netmask.pod";
	$f[]="/usr/local/share/perl/5.14.2/Net/Netmask.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/DomainName.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/ZoneFile.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Text.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Nameserver.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Update.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Parameters.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Domain.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/FAQ.pod";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Mailbox.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Question.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Header.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Packet.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/RP.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/PX.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CNAME.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/ISDN.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NULL.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/KX.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/URI.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/DLV.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/L32.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/SRV.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/DHCID.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/HIP.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/MX.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CERT.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/RT.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/EUI48.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/X25.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/MR.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/HINFO.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CDNSKEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/MINFO.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/PTR.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/GPOS.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/TXT.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CDS.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/SOA.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NSEC3.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/SPF.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NAPTR.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/A.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NS.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/MG.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/SIG.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/DS.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/LP.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/TLSA.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/AAAA.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/RRSIG.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/DNAME.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/KEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/LOC.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/TKEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/DNSKEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/TSIG.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/AFSDB.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/APL.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/MB.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NSEC.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CAA.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NSEC3PARAM.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/L64.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/OPT.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/SSHFP.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/IPSECKEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/EUI64.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/OPENPGPKEY.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/NID.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/RR/CSYNC.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/cygwin.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/android.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/Recurse.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/os2.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/MSWin32.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/Base.pm";
	$f[]="/usr/local/share/perl/5.14.2/Net/DNS/Resolver/UNIX.pm";
	$f[]="/usr/local/share/perl/5.14.2/Math/Round.pm";
	$f[]="/usr/local/share/perl/5.14.2/auto/Math/Round/autosplit.ix";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/MockFileHandle.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/MemberRead.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/Tree.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/Member.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/DirectoryMember.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/FileMember.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/NewFileMember.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/StringMember.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/Archive.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/ZipFileMember.pm";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/FAQ.pod";
	$f[]="/usr/local/share/perl/5.14.2/Archive/Zip/BufferedFileHandle.pm";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/Util/Util.bs";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/Util/Util.so";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP.pm";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP/Util.pm";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP/Lite.pm";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP/UtilPP.pm";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP/Util_IS.pm";
	$f[]="/usr/local/lib/perl/5.14.2/NetAddr/IP/InetBase.pm";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/hostenum.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/_splitref.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/re6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/short.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/canon.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/mod_version.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/_compact_v6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/re.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/coalesce.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/wildcard.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/_compV6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/do_prefix.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/autosplit.ix";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/prefix.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/compactref.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/_splitplan.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/nprefix.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/Util/autosplit.ix";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/_inet_ntop.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/_inet_pton.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/inet_any2n.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/ipv6_ntoa.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/inet_n2dx.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/ipv6_aton.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/inet_ntoa.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/_packzeros.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/autosplit.ix";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/InetBase/inet_n2ad.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/bin2bcd.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_bcd2bin.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/bcdn2bin.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/simple_pack.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_deadlen.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_sa128.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/comp128.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/bin2bcdn.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/ipanyto6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/add128.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_bin2bcdn.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/ipv4to6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/hasbits.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/notcontiguous.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/mask4to6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/slowadd128.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/shiftleft.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/autosplit.ix";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_128x2.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/ipv6to4.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/maskanyto6.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/addconst.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/bcdn2txt.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_bcdcheck.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/_128x10.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/sub128.al";
	$f[]="/usr/local/lib/perl/5.14.2/auto/NetAddr/IP/UtilPP/bcd2bin.al";
	$f[]="/usr/share/rspamd/lua";
	$f[]="/usr/share/rspamd/lua/hfilter.lua";
	$f[]="/usr/share/rspamd/lua/forged_recipients.lua";
	$f[]="/usr/share/rspamd/lua/multimap.lua";
	$f[]="/usr/share/rspamd/lua/ip_score.lua";
	$f[]="/usr/share/rspamd/lua/once_received.lua";
	$f[]="/usr/share/rspamd/lua/settings.lua";
	$f[]="/usr/share/rspamd/lua/maillist.lua";
	$f[]="/usr/share/rspamd/lua/mime_types.lua";
	$f[]="/usr/share/rspamd/lua/emails.lua";
	$f[]="/usr/share/rspamd/lua/fun.lua";
	$f[]="/usr/share/rspamd/lua/ratelimit.lua";
	$f[]="/usr/share/rspamd/lua/dmarc.lua";
	$f[]="/usr/share/rspamd/lua/spamassassin.lua";
	$f[]="/usr/share/rspamd/lua/trie.lua";
	$f[]="/usr/share/rspamd/lua/rbl.lua";
	$f[]="/usr/share/rspamd/lua/fann_scores.lua";
	$f[]="/usr/share/rspamd/lua/whitelist.lua";
	$f[]="/usr/share/rspamd/lua/phishing.lua";
	$f[]="/usr/share/rspamd/effective_tld_names.dat";
	$f[]="/usr/share/rspamd/rules/rspamd.classifiers.lua";
	$f[]="/usr/share/rspamd/rules/rspamd.lua";
	$f[]="/usr/share/rspamd/rules/http_headers.lua";
	$f[]="/usr/share/rspamd/rules/misc.lua";
	$f[]="/usr/share/rspamd/rules/regexp";
	$f[]="/usr/share/rspamd/rules/regexp/drugs.lua";
	$f[]="/usr/share/rspamd/rules/regexp/headers.lua";
	$f[]="/usr/share/rspamd/rules/regexp/fraud.lua";
	$f[]="/usr/share/rspamd/rules/regexp/lotto.lua";
	$f[]="/usr/share/rspamd/rules/html.lua";
	$f[]="/usr/share/rspamd/www/favicon.ico";
	$f[]="/usr/share/rspamd/www/index.html";
	$f[]="/usr/share/rspamd/www/react-index.html";
	$f[]="/usr/share/rspamd/www/img";
	$f[]="/usr/share/rspamd/www/img/spinner.gif";
	$f[]="/usr/share/rspamd/www/img/desc.png";
	$f[]="/usr/share/rspamd/www/img/spinner.png";
	$f[]="/usr/share/rspamd/www/img/asc.png";
	$f[]="/usr/share/rspamd/www/css";
	$f[]="/usr/share/rspamd/www/css/glyphicons-halflings-regular.woff";
	$f[]="/usr/share/rspamd/www/css/datatables.min.css";
	$f[]="/usr/share/rspamd/www/css/glyphicons-halflings-regular.woff2";
	$f[]="/usr/share/rspamd/www/css/rspamd.css";
	$f[]="/usr/share/rspamd/www/README.md";
	$f[]="/usr/share/rspamd/www/js";
	$f[]="/usr/share/rspamd/www/js/d3pie.min.js";
	$f[]="/usr/share/rspamd/www/js/datatables.min.js";
	$f[]="/usr/share/rspamd/www/js/rspamd.js";
	$f[]="/usr/share/rspamd/www/plugins.txt";
	$f[]="/usr/share/lintian/overrides/rspamd";
	$f[]="/usr/bin/rspamc";
	$f[]="/usr/bin/rspamadm";
	$f[]="/usr/bin/rspamd";
	$f[]="/usr/lib/rspamd";
	$f[]="/usr/lib/rspamd/librspamd-actrie.so";
	$f[]="/usr/sbin/rmilter";
	$f[]="/var/log/rspamd";
	$f[]="/var/lib";
	$f[]="/var/lib/rspamd";
	$f[]="/etc/rmilter.conf";
	$f[]="/etc/init.d/rmilter.org";
	$f[]="/etc/init.d/rspamd.org";
	$f[]="/etc/init/rmilter.conf";
	$f[]="/etc/logrotate.d/rspamd";
	$f[]="/etc/rmilter.conf.common";
	$f[]="/etc/rspamd/spf_dkim_whitelist.inc";
	$f[]="/etc/rspamd/logging.inc";
	$f[]="/etc/rspamd/worker-normal.inc";
	$f[]="/etc/rspamd/rspamd.systemd.conf";
	$f[]="/etc/rspamd/metrics.conf";
	$f[]="/etc/rspamd/common.conf";
	$f[]="/etc/rspamd/worker-controller.inc";
	$f[]="/etc/rspamd/composites.conf";
	$f[]="/etc/rspamd/surbl-whitelist.inc";
	$f[]="/etc/rspamd/dmarc_whitelist.inc";
	$f[]="/etc/rspamd/mime_types.inc";
	$f[]="/etc/rspamd/rspamd.sysvinit.conf";
	$f[]="/etc/rspamd/modules.d";
	$f[]="/etc/rspamd/modules.d/mime_types.conf";
	$f[]="/etc/rspamd/modules.d/emails.conf";
	$f[]="/etc/rspamd/modules.d/rbl.conf";
	$f[]="/etc/rspamd/modules.d/regexp.conf";
	$f[]="/etc/rspamd/modules.d/phishing.conf";
	$f[]="/etc/rspamd/modules.d/hfilter.conf";
	$f[]="/etc/rspamd/modules.d/once_received.conf";
	$f[]="/etc/rspamd/modules.d/chartable.conf";
	$f[]="/etc/rspamd/modules.d/spf.conf";
	$f[]="/etc/rspamd/modules.d/dkim.conf";
	$f[]="/etc/rspamd/modules.d/dmarc.conf";
	$f[]="/etc/rspamd/modules.d/ip_score.conf";
	$f[]="/etc/rspamd/modules.d/multimap.conf";
	$f[]="/etc/rspamd/modules.d/surbl.conf";
	$f[]="/etc/rspamd/modules.d/maillist.conf";
	$f[]="/etc/rspamd/modules.d/whitelist.conf";
	$f[]="/etc/rspamd/modules.d/fuzzy_check.conf";
	$f[]="/etc/rspamd/modules.d/ratelimit.conf";
	$f[]="/etc/rspamd/modules.d/forged_recipients.conf";
	$f[]="/etc/rspamd/statistic.conf";
	$f[]="/etc/rspamd/2tld.inc";
	$f[]="/etc/rspamd/modules.conf";
	$f[]="/etc/rspamd/options.inc";
	$f[]="/etc/rspamd/rspamd.conf";

	
	
	$f[]="/etc/mail/mimedefang-ip-key";
	$f[]="/usr/local/bin/mimedefang-multiplexor";
	$f[]="/usr/local/bin/md-mx-ctrl";
	$f[]="/usr/local/bin/mimedefang";
	$f[]="/usr/local/bin/watch-mimedefang";
	$f[]="/usr/local/bin/watch-multiple-mimedefangs.tcl";
	$f[]="/usr/local/bin/mimedefang.pl";
	$f[]="/usr/local/bin/mimedefang-util";
	

	$f[]="/usr/etc/valvula/mods-available/mod-bwl.xml";
	$f[]="/usr/etc/valvula/mods-available/mod-mquota.xml";
	$f[]="/usr/etc/valvula/mods-available/mod-mw.xml";
	$f[]="/usr/etc/valvula/mods-available/mod-slm.xml";
	$f[]="/usr/etc/valvula/mods-available/mod-test.xml";
	$f[]="/usr/etc/valvula/mods-available/mod-ticket.xml";
	$f[]="/usr/etc/valvula/valvula.example.conf";
	$f[]="/usr/bin/valvulad";
	

	while (list ($num, $ligne) = each ($f) ){
		if(is_dir($ligne)){
			echo "$WORKDIR$ligne Creating directory\n";continue;
			@mkdir("$WORKDIR$ligne",0755,true);
			continue;
		}
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
	$targtefile="mailsecurity-$Architecture-$version.tar.gz";
	echo "Compressing $targtefile\n";
	if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
	shell_exec("tar -czf /root/$targtefile *");
	echo "Compressing /root/$targtefile Done...\n";	
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










