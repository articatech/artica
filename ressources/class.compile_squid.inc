<?php
function create_package_squid($t){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$Architecture=Architecture();
	$version=squid_version();
	$debian=DebianVersion();
		shell_exec("wget http://www.articatech.net/download/anthony-icons.tar.gz -O /tmp/anthony-icons.tar.gz");
	@mkdir("/usr/share/squid3/icons",0755,true);
	shell_exec("tar -xf /tmp/anthony-icons.tar.gz -C /usr/share/squid3/icons/");
	shell_exec("/bin/chown -R squid:squid /usr/share/squid3/icons/");
	echo "Removing /root/squid-builder\n";
	
	if(is_file("/root/hypercache/64bits/hypercache-plugin")){
		@unlink("/lib/squid3/hypercache-plugin");
		@copy("/root/hypercache/64bits/hypercache-plugin", "/lib/squid3/hypercache-plugin");
		@chmod("/lib/squid3/hypercache-plugin", 0755);
	}
	
	@unlink("/etc/squid3/squid.conf");
	@unlink("/etc/squid3/acls_center.conf");
	@unlink("/etc/squid3/ChildsProxy.conf");
	@unlink("/etc/squid3/external_acls.conf");
	@unlink("/etc/squid3/GlobalAccessManager_auth.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny.conf");
	@unlink("/etc/squid3/icap.conf");
	@unlink("/etc/squid3/mime.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");
	@unlink("/etc/squid3/wccp.conf");
	@unlink("/etc/squid3/cachemgr.conf");
	@unlink("/etc/squid3/dangerous_extensions.conf");
	@unlink("/etc/squid3/freewebs.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny_cache.conf");
	@unlink("/etc/squid3/GlobalAccessManager_url_rewrite.conf");
	@unlink("/etc/squid3/listen_ports.conf");
	@unlink("/etc/squid3/refresh_patterns.conf");
	@unlink("/etc/squid3/ssl.conf");
	@unlink("/etc/squid3/url_rewrite_access.conf");
	
	sleep(1);
	shell_exec("/bin/rm -rf /root/squid-builder");
	@unlink("/etc/squid3/squid.conf");
	mkdir("/root/squid-builder/usr/share/squid3",0755,true);
	mkdir("/root/squid-builder/usr/lib",0755,true);
	mkdir("/root/squid-builder/usr/lib32",0755,true);
	mkdir("/root/squid-builder/etc/squid3",0755,true);
	mkdir("/root/squid-builder/etc/miniupnpd/netfilter",0755,true);
	mkdir("/root/squid-builder/lib/squid3",0755,true);
	mkdir("/root/squid-builder/usr/sbin",0755,true);
	mkdir("/root/squid-builder/usr/bin",0755,true);
	mkdir("/root/squid-builder/usr/share/squid-langpack",0755,true);
	mkdir("/root/squid-builder/usr/share/sarg/images",0755,true);
	mkdir("/root/squid-builder/usr/etc",0755,true);
	mkdir("/root/squid-builder/opt/kaspersky/KasperskyUpdateUtility",0755,true);
	mkdir("/root/squid-builder/usr/share/nmap",0755,true);
	mkdir("/root/squid-builder/usr/share/squid27",0755,true);
	mkdir("/root/squid-builder/usr/share/man/man8",0755,true);
	mkdir("/root/squid-builder/usr/share/perl5",0755,true);
	mkdir("/root/squid-builder/usr/lib/squid27",0755,true);
	mkdir("/root/squid-builder/etc/squid27",0755,true);
	mkdir("/root/squid-builder/etc/streamcache",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/common",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/adapter",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/host",0755,true);
	mkdir("/root/squid-builder/usr/include/jasper",0755,true);
	mkdir("/root/squid-builder/usr/share/man/man1",0755,true);
	mkdir("/root/squid-builder/usr/lib/c_icap",0755,true);
	mkdir("/root/squid-builder/opt/ufdbcat",0755,true);
	mkdir("/root/squid-builder/usr/local/sbin",0755,true);
	mkdir("/root/squid-builder/var/lib/ss5/ss5",0755,true);
	@mkdir("/root/squid-builder/usr/lib/openvpn",0755,true);
	@mkdir("/root/squid-builder/usr/sbin",0755,true);
	@mkdir("/root/squid-builder/usr/local/sbin",0755,true);
	@mkdir("/root/squid-builder/usr/local/etc",0755,true);

	shell_exec("$cp -rfd /usr/share/squid3/* /root/squid-builder/usr/share/squid3/");
	shell_exec("$cp -rfd /etc/squid3/* /root/squid-builder/etc/squid3/");
	shell_exec("$cp -rfd /lib/squid3/* /root/squid-builder/lib/squid3/");
	shell_exec("$cp -rfd /usr/share/squid-langpack/* /root/squid-builder/usr/share/squid-langpack/");
	shell_exec("$cp -rfd /usr/sbin/squid /root/squid-builder/usr/sbin/squid");
	shell_exec("$cp -rfd /usr/bin/purge /root/squid-builder/usr/bin/purge");
	shell_exec("$cp -rfd /usr/bin/squidclient /root/squid-builder/usr/bin/squidclient");
	shell_exec("$cp -rfd /usr/sbin/vnstatd /root/squid-builder/usr/sbin/vnstatd");
	shell_exec("$cp -rfd /usr/bin/pactester /root/squid-builder/usr/bin/pactester");
	shell_exec("$cp -rfd /usr/lib/libpacparser.so.1 /root/squid-builder/usr/lib/libpacparser.so.1");
	shell_exec("$cp -rfd /usr/share/sarg/images/* /root/squid-builder/usr/share/sarg/images/");
	shell_exec("$cp -rfd /opt/kaspersky/﻿KasperskyUpdateUtility/* /root/squid-builder/opt/kaspersky/﻿KasperskyUpdateUtility/");
	
	
	shell_exec("$cp -fd /usr/local/sbin/haproxy /root/squid-builder/usr/local/sbin/haproxy");
	shell_exec("$cp -fd /usr/local/sbin/haproxy-systemd-wrapper /root/squid-builder/usr/local/sbin/haproxy-systemd-wrapper");
	shell_exec("$cp -fd /usr/bin/nping /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ncat /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/nmapfe /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/zenmap /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/nmap /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap.dtd /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-mac-prefixes /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-os-db /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-payloads /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-protocols /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-rpc /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-service-probes /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-services /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap.xsl /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nse_main.lua /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/redsocks /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/openvpn /root/squid-builder/usr/bin/openvpn");
	shell_exec("$cp -fd /usr/local/sbin/privoxy /root/squid-builder/usr/local/sbin/privoxy");
	
	//shell_exec("$cp -fd /usr/local/sbin/haproxy /root/squid-builder/usr/local/sbin/haproxy");
	//shell_exec("$cp -fd /usr/local/sbin/haproxy-systemd-wrapper /root/squid-builder/usr/local/sbin/haproxy-systemd-wrapper");
	shell_exec("$cp -rfd /usr/share/nmap/* /root/squid-builder/usr/share/nmap/");
	shell_exec("$cp -rfd /usr/lib/openvpn/* /root/squid-builder/usr/lib/openvpn/");
	shell_exec("$cp -rfd /usr/local/etc/privoxy /root/squid-builder/usr/local/etc/");
	
	shell_exec("$cp -rfd /usr/include/libecap/common/* /root/squid-builder/usr/include/libecap/common/");
	shell_exec("$cp -rfd /usr/include/libecap/adapter/* /root/squid-builder/usr/include/libecap/adapter/");
	shell_exec("$cp -rfd /usr/include/libecap/host/* /root/squid-builder/usr/include/libecap/host/");
	shell_exec("$cp -rfd /usr/include/jasper/* /root/squid-builder/usr/include/jasper/");

	
	
	//shell_exec("$cp -fd /usr/lib/libecap.so.2.0.0 /root/squid-builder/usr/lib/libecap.so.2.0.0");
	//shell_exec("$cp -fd /usr/lib/libecap.so.2 /root/squid-builder/usr/lib/libecap.so.2");

	shell_exec("$cp -fd /usr/lib/libecap.so.3.0.0 /root/squid-builder/usr/lib/libecap.so.3.0.0");
	shell_exec("$cp -fd /usr/lib/libecap.so.3 /root/squid-builder/usr/lib/libecap.so.3");
	shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/libecap.so");
	shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/libecap.la");
	
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.so /root/squid-builder/usr/lib/ecap_clamav_adapter.so");
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.la /root/squid-builder/usr/lib/ecap_clamav_adapter.la");
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.a /root/squid-builder/usr/lib/ecap_clamav_adapter.a");
	
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.so /root/squid-builder/usr/lib/ecap_adapter_gzip.so");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.la /root/squid-builder/usr/lib/ecap_adapter_gzip.la");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.a /root/squid-builder/usr/lib/ecap_adapter_gzip.a");

	shell_exec("$cp -fd /usr/lib/libnettle.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libnettle.so /root/squid-builder/usr/lib/"); 
	shell_exec("$cp -fd /usr/lib/libnettle.so.6 /root/squid-builder/usr/lib/");  
	shell_exec("$cp -fd /usr/lib/libnettle.so.6.1 /root/squid-builder/usr/lib/");
	
	
	shell_exec("$cp -fd /usr/lib/libgif.so.6.0.1 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so.6 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /root/wanproxy/programs/wanproxy/wanproxy /root/squid-builder/usr/bin/wanproxy");
	shell_exec("$cp -fd /usr/include/gif_lib.h /root/squid-builder/usr/include/");
	shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libjasper.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libjasper.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/bin/jasper /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/imgcmp /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/imginfo /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/tmrdemo /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ziproxylogtool  /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ziproxy  /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxy.1 /root/squid-builder/usr/share/man/man1/");
	shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxylogtool.1 /root/squid-builder/usr/share/man/man1/");
	shell_exec("$cp -fd /etc/squid3/README_ZIPPROXY /root/squid-builder/etc/squid3/");
	shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libecap.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.a /root/squid-builder/usr/lib/");
	
	shell_exec("$cp -fd /usr/bin/upnpc /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/lib/libminiupnpc.so.10 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libminiupnpc.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/sbin/miniupnpd /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/iptables_init.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/iptables_removeall.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/ip6tables_init.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/ip6tables_removeall.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/miniupnpd.conf /root/squid-builder/etc/miniupnpd/");
	shell_exec("$cp -fd /usr/share/man/man8/miniupnpd.8 /root/squid-builder/usr/share/man/man8/");
	shell_exec("$cp -fd /usr/share/perl5/SquidAnalyzer.pm /root/squid-builder/usr/share/perl5/SquidAnalyzer.pm");
	
	
	
	shell_exec("$cp -rfd /usr/lib/c_icap/* /root/squid-builder/usr/lib/c_icap/");
	shell_exec("$cp -rfd /usr/share/squid27/* /root/squid-builder/usr/share/squid27/");
	shell_exec("$cp -rfd /usr/lib/squid27/* /root/squid-builder/usr/lib/squid27/");
	shell_exec("$cp -rfd /etc/squid27/* /root/squid-builder/etc/squid27/");
	shell_exec("$cp -rfd /etc/streamcache/* /root/squid-builder/etc/streamcache/*");
	shell_exec("$cp -rfd /opt/ufdbcat/* /root/squid-builder/opt/ufdbcat/");
	shell_exec("$cp -rfd /var/lib/ss5/ss5/* /root/squid-builder/var/lib/ss5/ss5/");
	
	shell_exec("$cp -fd /usr/local/lib/libhttpd.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/lib/libhttpd.so.0.0.0 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/lib/libhttpd.so.0 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/bin/wifidog /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/local/bin/wdctl /root/squid-builder/usr/bin/");

	$f[]="/usr/lib/libssl.so.0.9.8";
	$f[]="/usr/lib/libcrypto.so.0.9.8";
	$f[]="/usr/lib/libboost_program_options.so.1.42.0";
	$f[]="/usr/bin/bwm-ng";
	$f[]="/usr/share/info/msmtp.info";
	$f[]="/etc/ld.so.conf.d/KasperskyUpdateUtility.conf";
	$f[]="/usr/bin/msmtp";
	$f[]="/usr/sbin/dnsmasq";
	$f[]="/usr/share/gettext/po";
	$f[]="/usr/etc/css.tpl";
	$f[]="/usr/etc/exclude_codes";
	$f[]="/usr/bin/sarg";
	$f[]="/usr/sbin/cntlm";
	$f[]="/usr/lib/libmysqlclient.so.18";
	$f[]="/usr/lib/libmysqlclient.so.18.0.0";
	$f[]="/usr/lib/libicapapi.la";
	//$f[]="/usr/lib/libicapapi.so.3.0.1";
	//$f[]="/usr/lib/libicapapi.so.3.0.2";
	//$f[]="/usr/lib/libicapapi.so.3.0.3";
	//$f[]="/usr/lib/libicapapi.so.3.0.4";
	$f[]="/usr/lib/libicapapi.so.3.0.5";
	$f[]="/usr/lib/libicapapi.so.3";
	$f[]="/usr/bin/ufdbguardd";
	$f[]="/usr/bin/ufdbgclient";
	$f[]="/usr/bin/ufdb-pstack";
	$f[]="/usr/bin/ufdbConvertDB";
	$f[]="/usr/bin/ufdbGenTable";
	$f[]="/usr/bin/ufdbAnalyse";
	$f[]="/usr/bin/ufdbhttpd";
	$f[]="/usr/bin/ufdbUpdate";
	$f[]="/etc/init.d/ufdb";
	$f[]="/usr/lib32/libglib-2.0.so.0";
	$f[]="/usr/lib32/liblzma.so.5";
	$f[]="/usr/lib32/libpcre.so.3";
	$f[]="/usr/lib32/libQtCLucene.so.4";
	$f[]="/usr/lib32/libQtCLucene.so.4.8.2";
	$f[]="/usr/lib32/libQtCore.so.4.8";
	$f[]="/usr/lib32/libQtXml.so.4";
	$f[]="/usr/lib32/libQtXml.so.4.8.2";
	$f[]="/usr/lib32/libxml2.so.2.8.0";
	$f[]="/usr/lib32/libglib-2.0.so.0.3200.4";
	$f[]="/usr/lib32/liblzma.so.5.0.0";
	$f[]="/usr/lib32/libpcre.so.3.13.1";
	$f[]="/usr/lib32/libQtCLucene.so.4.8";
	$f[]="/usr/lib32/libQtCore.so.4";
	$f[]="/usr/lib32/libQtCore.so.4.8.2";
	$f[]="/usr/lib32/libQtXml.so.4.8";
	$f[]="/usr/lib32/libxml2.so.2";
	$f[]="/usr/sbin/RunCache27";
	$f[]="/usr/sbin/squidclient27";
	$f[]="/usr/sbin/cossdump27";
	$f[]="/usr/sbin/squid27";
	$f[]="/usr/sbin/streamRunCachecache";
	$f[]="/usr/sbin/streamsquidcache";
	$f[]="/usr/local/sbin/vsftpd";
	$f[]="/usr/local/man/man8/vsftpd.8";
	$f[]="/usr/man/man8/vsftpd.8";
	$f[]="/usr/share/man/man5/vsftpd.conf.5";
	$f[]="/usr/share/man/man8/vsftpd.8";
	$f[]="/usr/lib/libsocks.so.0.1.1";
	$f[]="/usr/lib/libsocks.so";
	$f[]="/usr/lib/libsocks.la";
	$f[]="/usr/lib/libsocks.a";
	$f[]="/usr/sbin/sockd";
	$f[]="/usr/share/man/man8/sockd.8";
	$f[]="/usr/share/man/man1/socksify.1";
	$f[]="/usr/share/man/man5/sockd.conf.5";
	$f[]="/usr/share/man/man5/socks.conf.5";
	$f[]="/usr/bin/socksify";
	$f[]="/usr/include/socks.h";
	
	$f[]="/usr/sbin/ss5";
	$f[]="/usr/sbin/ss5srv";
	$f[]="/usr/local/bin/krake";
	$f[]="/usr/bin/squid-analyzer";

	//  mkdir -p /var/spool/squid27/cache && mkdir /var/spool/squid27/logs && chown -R nobody:nobody /var/spool/squid27 && squid27 -z
	// pour démarer squid27 : /usr/sbin/squid27


	/*
	 $f[]="/usr/lib/libnfnetlink.la";
	$f[]="/usr/lib/libnfnetlink.so.0.2.0";
	$f[]="/usr/lib/libnfnetlink.so.0";
	$f[]="/usr/lib/libnfnetlink.so";
	$f[]="/usr/lib/libnfnetlink.a";
	$f[]="/usr/lib/libmnl.so.0.1.0";
	$f[]="/usr/lib/libmnl.la";
	$f[]="/usr/lib/libmnl.so.0";
	$f[]="/usr/lib/libnetfilter_conntrack.so.3.5.0";
	$f[]="/usr/lib/libnetfilter_conntrack.so.3";
	$f[]="/usr/lib/libnetfilter_conntrack.so";
	$f[]="/usr/lib/libnetfilter_conntrack.la";
	$f[]="/usr/lib/libnetfilter_cttimeout.so.1.0.0";
	$f[]="/usr/lib/libnetfilter_cttimeout.so";
	$f[]="/usr/lib/libnetfilter_cttimeout.so.1";
	$f[]="/usr/lib/libnetfilter_cttimeout.la";
	$f[]="/usr/lib/libnetfilter_cthelper.so.0.0.0";
	$f[]="/usr/lib/libnetfilter_cthelper.so.0";
	$f[]="/usr/lib/libnetfilter_cthelper.so";
	$f[]="/usr/lib/libnetfilter_cthelper.la";
	$f[]="/usr/lib/libnetfilter_queue.so.1.3.0";
	$f[]="/usr/lib/libnetfilter_queue.so";
	$f[]="/usr/lib/libnetfilter_queue.so.1";
	$f[]="/usr/lib/libnetfilter_queue.la";
	$f[]="/usr/sbin/conntrack";
	$f[]="/usr/sbin/conntrackd";
	$f[]="/usr/sbin/nfct";
	$f[]="/usr/lib/conntrack-tools/ct_helper_ftp.la";
	$f[]="/usr/lib/conntrack-tools/ct_helper_ftp.so";
	$f[]="/usr/lib/conntrack-tools/ct_helper_rpc.la";
	$f[]="/usr/lib/conntrack-tools/ct_helper_rpc.so";
	$f[]="/usr/lib/conntrack-tools/ct_helper_tns.la";
	$f[]="/usr/lib/conntrack-tools/ct_helper_tns.so";
	*/

	//ldconfig -n /usr/lib/conntrack-tools
	
	@unlink("/etc/squid3/squid.conf");
	@unlink("/etc/squid3/acls_center.conf");
	@unlink("/etc/squid3/ChildsProxy.conf");
	@unlink("/etc/squid3/external_acls.conf");
	@unlink("/etc/squid3/GlobalAccessManager_auth.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny.conf");
	@unlink("/etc/squid3/icap.conf");
	@unlink("/etc/squid3/mime.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");
	@unlink("/etc/squid3/wccp.conf");
	@unlink("/etc/squid3/cachemgr.conf");
	@unlink("/etc/squid3/dangerous_extensions.conf");
	@unlink("/etc/squid3/freewebs.conf");	 
	@unlink("/etc/squid3/GlobalAccessManager_deny_cache.conf");
	@unlink("/etc/squid3/GlobalAccessManager_url_rewrite.conf");
	@unlink("/etc/squid3/listen_ports.conf");
	@unlink("/etc/squid3/refresh_patterns.conf");
	@unlink("/etc/squid3/ssl.conf");
	@unlink("/etc/squid3/url_rewrite_access.conf");

	while (list ($num, $ligne) = each ($f) ){
		if(is_dir($ligne)){
			shell_exec("/bin/cp -rfd $ligne/* /root/squid-builder$ligne/");
			continue;
		}

		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/squid-builder$dir/\n";
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");

}
package_redemption();


$CICAP=c_icap_array();
while (list ($num, $filename) = each ($CICAP) ){
if(is_dir($filename)){
	@mkdir("/root/squid-builder$filename",0755,true);
	shell_exec("/bin/cp -rfd $filename/* /root/squid-builder$filename/");
	continue;
}
$dir=dirname($filename);
echo "Installing $filename in /root/squid-builder$dir/\n";
if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
shell_exec("/bin/cp -fd $filename /root/squid-builder$dir/");
}

proftpd("/root/squid-builder",true);

echo "Debian Version: $debian\n";
echo "Compile SARG....\n";
compile_sarg();

if($debian>6){
$debianv="-debian$debian";
}
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version Debian v:$debian \n";
chdir("/root/squid-builder");

$TARGET_TGZ="/root/squid32-$Architecture$debianv-$version.tar.gz";

$version=squid_version();
echo "Compressing $TARGET_TGZ....\n";
shell_exec("$tar -czf $TARGET_TGZ *");

if(!is_file($TARGET_TGZ)){
	echo "$TARGET_TGZ no such file ???\n";
	return;
}

echo "********************************************\n";
echo "$TARGET_TGZ\n";
echo "********************************************\n\n";


system_admin_events("$TARGET_TGZ  ready...",__FUNCTION__,__FILE__,__LINE__);
if(is_file("/root/ftp-password")){
	echo "$TARGET_TGZ is now ready to be uploaded\n";
	shell_exec("curl -T $TARGET_TGZ ftp://articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
	system_admin_events("Uploading $TARGET_TGZ done.",__FUNCTION__,__FILE__,__LINE__);
	if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}

}

	shell_exec("/etc/init.d/artica-postfix restart squid-cache");
$took=$unix->distanceOfTimeInWords($t,time(),true);
system_admin_events("Installing the new squid-cache $version success took:$took", __FUNCTION__, __FILE__, __LINE__, "software");
}

function proftpd($base=null,$nocompress=false){
	// apt-get install libpq-dev
	// ./configure --prefix=/usr --with-includes=/usr/include/postgresql --mandir=/usr/share/man --sysconfdir=/etc/proftpd --localstatedir=/var/run --libexecdir=/usr/lib/proftpd --enable-sendfile --enable-facl --enable-dso --enable-autoshadow --enable-ctrls --with-modules=mod_readme --enable-ipv6  --build x86_64-linux-gnu --with-shared=mod_quotatab:mod_sql:mod_sql_mysql:mod_sql_postgres:mod_quotatab_sql:mod_ldap:mod_quotatab_ldap:mod_ratio:mod_tls:mod_rewrite:mod_radius:mod_wrap:mod_quotatab_file:mod_facl:mod_ctrls_admin:mod_ifsession
	$f[]="/usr/sbin/proftpd";

	$f[]="/usr/bin/ftpasswd";
	$f[]="/usr/bin/ftpmail";
	$f[]="/usr/bin/ftpquota";
	$f[]="/usr/bin/ftpcount";
	$f[]="/usr/bin/ftpdctl";
	$f[]="/usr/sbin/ftpscrub";
	$f[]="/usr/sbin/ftpshut";
	$f[]="/usr/bin/ftptop";
	$f[]="/usr/bin/ftpwho";
	$f[]="/usr/bin/prxs";


	if($base==null){
		$base="/root/proftpd-build";
	}
	
	

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
			echo "/bin/cp -rfd $filename $base$dirname/\n";
			shell_exec("/bin/cp -rfd $filename $base/$dirname/");
		}

	}
	@mkdir("$base/usr/lib/proftpd",0755,true);
	shell_exec("/bin/cp -rfd /usr/lib/proftpd/* $base/usr/lib/proftpd/");

	if(!$nocompress){
		$Debian=DebianVersion();
		$Architecture=Architecture();
		$version=proftpd_version();
		chdir($base);
		if(is_file("$base/proftpd-debian{$Debian}-$Architecture-$version.tar.gz")){
			@unlink("$base/proftpd-debian{$Debian}-$Architecture-$version.tar.gz");
		}
	
		$filename="proftpd-debian{$Debian}-$Architecture-$version.tar.gz";
		shell_exec("tar -czf $filename *");
		shell_exec("/bin/cp $filename /root/");
		echo "/root/$filename done\n";
	}


}

function proftpd_version(){
$unix=new unix();
$proftpd=$unix->find_program("proftpd");
	exec("$proftpd -v 2>&1",$results);

	while (list ($num, $val) = each ($results)){
	if(preg_match("#ProFTPD Version\s+([0-9\.]+)#", $val,$re)){
	$GLOBALS["proftpd_version"]=trim($re[1]);
	return $GLOBALS["proftpd_version"];
		}
	}

}


function c_icap_array(){

	// ./configure --with-c-icap=/usr/lib/c_icap/

	$f[]="/usr/bin/c-icap";
	$f[]="/usr/bin/c-icap-client";
	$f[]="/usr/bin/c-icap-config";
	$f[]="/usr/bin/c-icap-libicapapi-config";
	$f[]="/usr/bin/c-icap-mkbdb";
	$f[]="/usr/bin/c-icap-mods-sguardDB";
	$f[]="/usr/bin/c-icap-stretch";
	$f[]="/usr/lib/c_icap/bdb_tables.a";
	$f[]="/usr/lib/c_icap/bdb_tables.la";
	$f[]="/usr/lib/c_icap/bdb_tables.so";
	$f[]="/usr/lib/c_icap/dnsbl_tables.a";
	$f[]="/usr/lib/c_icap/dnsbl_tables.la";
	$f[]="/usr/lib/c_icap/dnsbl_tables.so";
	$f[]="/usr/lib/c_icap/ldap_module.a";
	$f[]="/usr/lib/c_icap/ldap_module.la";
	$f[]="/usr/lib/c_icap/ldap_module.so";
	$f[]="/usr/lib/c_icap/srv_echo.a";
	$f[]="/usr/lib/c_icap/srv_echo.la";
	$f[]="/usr/lib/c_icap/srv_echo.so";
	$f[]="/usr/lib/c_icap/sys_logger.a";
	$f[]="/usr/lib/c_icap/sys_logger.la";
	$f[]="/usr/lib/c_icap/sys_logger.so";
	$f[]="/usr/lib/c_icap/srv_content_filtering.la";
	$f[]="/usr/lib/c_icap/srv_content_filtering.so";
	$f[]="/usr/lib/libicapapi.la";
	$f[]="/usr/lib/libicapapi.so";
	//$f[]="/usr/lib/libicapapi.so.3.0.1";
	//$f[]="/usr/lib/libicapapi.so.3.0.2";
	//$f[]="/usr/lib/libicapapi.so.3.0.3";
	//$f[]="/usr/lib/libicapapi.so.3.0.4";
	//$f[]="/usr/lib/libicapapi.so.3";
	
	//$f[]="/usr/lib/libicapapi.so.4.0.1";
	$f[]="/usr/lib/libicapapi.so.4.0.2";
	$f[]="/usr/lib/libicapapi.so.4";
	
	$f[]="/usr/share/c_icap/templates/srv_content_filtering/en/BLOCK";
	$f[]="/usr/share/c_icap/templates/srv_url_check/en/DENY";
	$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_HEAD";
	$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_PROGRESS";
	$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_TAIL";
	$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_VIRUS_FOUND";
	$f[]="/usr/share/c_icap/templates/virus_scan/en/VIRUS_FOUND";
	$f[]="/usr/share/man/man8/c-icap.8";
	$f[]="/usr/share/man/man8/c-icap-client.8";
	$f[]="/usr/share/man/man8/c-icap-config.8";
	$f[]="/usr/share/man/man8/c-icap-libicapapi-config.8";
	$f[]="/usr/share/man/man8/c-icap-mkbdb.8";
	$f[]="/usr/share/man/man8/c-icap-stretch.8";
	$f[]="/etc/c-icap.conf";
	$f[]="/etc/srv_url_check.conf";
	$f[]="/etc/virus_scan.conf";
	$f[]="/etc/c-icap.magic.default";
	$f[]="/etc/c-icap.magic";
	$f[]="/usr/lib/c_icap/bdb_tables.a";
	$f[]="/usr/lib/c_icap/dnsbl_tables.a";
	$f[]="/usr/lib/c_icap/ldap_module.a";
	$f[]="/usr/lib/c_icap/srv_clamav.a";
	$f[]="/usr/lib/c_icap/srv_echo.a";
	$f[]="/usr/lib/c_icap/srv_url_check.a";
	$f[]="/usr/lib/c_icap/sys_logger.a";
	$f[]="/usr/lib/c_icap/bdb_tables.la";
	$f[]="/usr/lib/c_icap/dnsbl_tables.la";
	$f[]="/usr/lib/c_icap/ldap_module.la";
	$f[]="/usr/lib/c_icap/srv_clamav.la";
	$f[]="/usr/lib/c_icap/srv_echo.la";
	$f[]="/usr/lib/c_icap/srv_url_check.la";
	$f[]="/usr/lib/c_icap/sys_logger.la";
	$f[]="/usr/lib/c_icap/bdb_tables.so";
	$f[]="/usr/lib/c_icap/dnsbl_tables.so";
	$f[]="/usr/lib/c_icap/ldap_module.so";
	$f[]="/usr/lib/c_icap/srv_clamav.so";
	$f[]="/usr/lib/c_icap/srv_echo.so";
	$f[]="/usr/lib/c_icap/srv_url_check.so";
	$f[]="/usr/lib/c_icap/sys_logger.so";
	$f[]="/usr/lib/c_icap/virus_scan.a";
	$f[]="/usr/lib/c_icap/virus_scan.la";
	$f[]="/etc/srv_url_check.conf.default";
	$f[]="/etc/srv_url_check.conf";
	$f[]="/etc/srv_clamav.conf.default";
	$f[]="/etc/srv_clamav.conf";
	$f[]="/usr/local/bin/fhs_judge";
	$f[]="/usr/local/bin/fhs_learn";
	$f[]="/usr/local/bin/fhs_makepreload";
	$f[]="/usr/local/bin/fnb_judge";
	$f[]="/usr/local/bin/fnb_learn";
	$f[]="/usr/local/bin/fnb_makepreload";
	$f[]="/usr/lib/c_icap/srv_classify.la";
	$f[]="/usr/lib/c_icap/srv_classify.so";
	$f[]="/usr/lib/c_icap/bdb_tables.a";
	$f[]="/usr/lib/c_icap/dnsbl_tables.a";
	$f[]="/usr/lib/c_icap/ldap_module.a";
	$f[]="/usr/lib/c_icap/libbz2.so.1.0.4";
	$f[]="/usr/lib/c_icap/srv_echo.so";
	$f[]="/usr/lib/c_icap/srv_ex206.so";
	$f[]="/usr/lib/c_icap/srv_url_check.so";
	$f[]="/usr/lib/c_icap/sys_logger.so";
	$f[]="/usr/lib/c_icap/virus_scan.so";
	$f[]="/usr/lib/c_icap/bdb_tables.la";
	$f[]="/usr/lib/c_icap/dnsbl_tables.la";
	$f[]="/usr/lib/c_icap/ldap_module.la";
	$f[]="/usr/lib/c_icap/srv_echo.a";
	$f[]="/usr/lib/c_icap/srv_ex206.a";
	$f[]="/usr/lib/c_icap/srv_url_check.a";
	$f[]="/usr/lib/c_icap/sys_logger.a";
	$f[]="/usr/lib/c_icap/virus_scan.a";
	$f[]="/usr/lib/c_icap/bdb_tables.so";
	$f[]="/usr/lib/c_icap/dnsbl_tables.so";
	$f[]="/usr/lib/c_icap/ldap_module.so";
	$f[]="/usr/lib/c_icap/srv_echo.la";
	$f[]="/usr/lib/c_icap/srv_ex206.la";
	$f[]="/usr/lib/c_icap/srv_url_check.la";
	$f[]="/usr/lib/c_icap/sys_logger.la";
	$f[]="/usr/lib/c_icap/virus_scan.la";
	$f[]="/usr/lib/c_icap/clamav_mod.a";
	$f[]="/usr/lib/c_icap/clamav_mod.la";
	$f[]="/usr/lib/c_icap/clamav_mod.so";
	$f[]="/usr/lib/c_icap/clamd_mod.a";
	$f[]="/usr/lib/c_icap/clamd_mod.la";
	$f[]="/usr/lib/c_icap/clamd_mod.so";
	$f[]="/usr/sbin/openvpn";
	$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-auth-pam.so";
	$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-auth-pam.la";
	$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-down-root.so";
	$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-down-root.la";
	$f[]="/usr/bin/memcached";

	return $f;

}

function package_ufdbguard(){

	shell_exec("/bin/rm -rf /root/ufdbGuard-compiled");

	$f[]="/usr/bin/ufdbguardd";
	$f[]="/usr/bin/ufdbgclient";
	$f[]="/usr/bin/ufdb-pstack";
	$f[]="/usr/bin/ufdbConvertDB";
	$f[]="/usr/bin/ufdbGenTable";
	$f[]="/usr/bin/ufdbAnalyse";
	$f[]="/usr/bin/ufdbhttpd";
	$f[]="/usr/bin/ufdbUpdate";
	$f[]="/etc/init.d/ufdb";
	$base="/root/ufdbGuard-compiled";
	while (list ($num, $filename) = each ($f)){
		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		shell_exec("/bin/cp -f $filename $base/$dirname/");

	}

	$Architecture=Architecture();
	$version=ufdbguardVersion();
	chdir("/root/ufdbGuard-compiled");
	shell_exec("tar -czf ufdbGuard-$Architecture-$version.tar.gz *");
	shell_exec("/bin/cp ufdbGuard-$Architecture-$version.tar.gz /root/");
	echo "/root/ufdbGuard-$Architecture-$version.tar.gz done";


}

function ufdbguardVersion(){
	exec("/root/ufdbGuard-compiled/usr/bin/ufdbguardd -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}

function remove_squid(){
	$bins[]="/usr/sbin/squid3";
	$bins[]="/usr/sbin/squid";
	$bins[]="/usr/share/man/man8/squid3.8.gz";
	$bins[]="/usr/sbin/squid";
	$bins[]="/usr/bin/purge";
	$bins[]="/usr/bin/squidclient";

	while (list ($num, $filename) = each ($bins)){
		if(is_file($filename)){
			echo "Remove $filename\n";
			@unlink($filename);
	}

}

$dirs[]="/etc/squid3";
$dirs[]="/lib/squid3";
$dirs[]="/usr/lib/squid3";
$dirs[]="/lib64/squid3";
$dirs[]="/usr/lib64/squid3";
$dirs[]="/usr/share/squid3";

while (list ($num, $filename) = each ($dirs)){
	if(is_dir($filename)){
		echo "Remove $filename\n";
		shell_exec("/bin/rm -rf $filename");
	}

}


}

function compile_sarg(){

	@mkdir("/root/squid-builder/usr/bin",0755,true);
	@mkdir("/root/squid-builder/usr/share/locale",0755,true);


	$f[]="/usr/bin/sarg";
	$f[]="/usr/share/locale/bg/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/ca/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/cs/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/el/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/id/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/lv/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/pt/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/ro/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/sk/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/sr/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/tr/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/sarg.mo";
	$f[]="/usr/share/locale/uk/LC_MESSAGES/sarg.mo";
	$f[]="/usr/etc/sarg.conf";
	$f[]="/usr/etc/user_limit_block";
	$f[]="/usr/etc/exclude_codes";

	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/squid-builder$dir/\n";
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");

	}

	$f=array();
	$f[]="/usr/share/sarg/fonts";
	$f[]="/usr/share/sarg/images";

	while (list ($num, $dir) = each ($f) ){
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		echo "Installing $dir/* in /root/squid-builder$dir/\n";
		shell_exec("/bin/cp -rfdv $dir/* /root/squid-builder$dir/");
}



}

function package_redemption(){

	/* apt-get instal boost-build libboost-dev  libboost-test-dev libboost-program-options-dev libssl-dev
	 wget https://github.com/wallix/redemption/archive/master.zip
	mkdir redemption && cp master.zip redemption/
	cd redemption && unzip master.zip
	cd redemption-master
	bjam
	bjam install
	*/


	
	
	$f["/usr/local/share/rdpproxy"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_16.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_24.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b-xxx.bmp"]=true;
	$f["/usr/local/share/rdpproxy/wablogoblue.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_18.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_192.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_14.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.png"]=true;
	$f["/usr/local/share/rdpproxy/ad160-24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_13.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.jpg"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.bmp"]=true;
	$f["/usr/local/share/rdpproxy/sans-10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_12.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.png"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.bmp"]=true;
	$f["/usr/local/bin/rdpproxy"]=true;
	$f["/usr/local/bin/redrec"]=true;
	$f["/etc/rdpproxy"]=true;
	$f["/etc/rdpproxy/rsakeys.ini"]=true;
	$f["/etc/rdpproxy/rdpproxy.ini"]=true;
	$f["/etc/rdpproxy/dh1024.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-cert.pem"]=true;
	$f["/etc/rdpproxy/dh2048.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-key.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy.crt"]=true;
	$f["/etc/rdpproxy/rdpproxy.key"]=true;
	$f["/etc/rdpproxy/rdpproxy.p12"]=true;
	$f["/usr/local/share/rdpproxy/cursor0.cur"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-wallix.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_11.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_20.fv1"]=true;
	$f["/usr/local/share/rdpproxy/cursor1.cur"]=true;
	$f["/usr/local/share/rdpproxy/FreeSans.ttf"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_32.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_48.fv1"]=true;


	$base="/root/squid-builder";
	@mkdir("$base",0755,true);

	while (list ( $filename,$none) = each ($f)){
		if(is_file($filename)){
			$dirname=dirname($filename);
			$newbase="$base/$dirname";
			@mkdir("$newbase",0755,true);
			echo "Copy $filename to $newbase\n";
			shell_exec("/bin/cp $filename $newbase/");
		}

	}
	@mkdir("$base/etc/rdpproxy/tools",0755,true);
	if(is_dir("/root/redemption/redemption-master/tools")){
		shell_exec("/bin/cp -rfv /root/redemption/redemption-master/tools $base/etc/rdpproxy/tools/");
	}
	
	if(is_dir("/root/redemption/tools")){
		shell_exec("/bin/cp -rfv /root/redemption/tools $base/etc/rdpproxy/tools/");
	}
//rdproxy-debian7-64-0.9.353.tar.gz

}

function package_c_icap(){
	$f=c_icap_array();

	$base="/root/c-icap-export";
	shell_exec("/bin/rm -rf $base");
	@mkdir($base);
	while (list ($num, $filename) = each ($f)){
		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		if(is_file($filename)){
			echo "Copy $filename into $base$dirname\n";
			shell_exec("/bin/cp -f $filename $base$dirname/");
	}

	}
	$C_ICAP_VERSION=C_ICAP_VERSION();
	$Architecture=Architecture();
	echo "C-icap version $C_ICAP_VERSION ($Architecture)\n";
	mkdir("/root/c-icap/usr/share/c_icap",0755,true);
mkdir("/root/c-icap/usr/include/c_icap",0755,true);
shell_exec("/bin/cp -rf /usr/share/c_icap/* /root/c-icap/usr/share/c_icap/");
shell_exec("/bin/cp -rf /usr/include/c_icap/* /root/c-icap/usr/include/c_icap/");
//error while loading shared libraries: libbz2.so.1.0
shell_exec("/bin/cp /lib/libbz2.so.1.0.4 /usr/lib/c_icap/");

chdir($base);
@unlink("/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz");
shell_exec("/bin/tar -czf /root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz *");
echo "/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz\n";
}





function C_ICAP_VERSION(){

	$results=exec("/usr/bin/c-icap-config --version");
	preg_match("#([0-9\.]+)#", $results,$re);
	return $re[1];
}