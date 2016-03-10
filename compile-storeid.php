<?php

ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__)."/ressources/class.haproxy.logs.inc");




$t=explode("\n",@file_get_contents("/home/dtouzeau/Documents/developpement/artica-postfix/bin/install/squid/storeid_file_rewrite"));
$c=0;
while (list ($index, $line) = each ($t) ){
	$line=trim($line);
	if($line==null){continue;}
	$c++;
	
	$ZZ=explode("\t",$line);
	
	$pattern=$ZZ[0];
	$dedup=$ZZ[1];
	$md5=md5($pattern);
	$zOrder=$c;
	$pattern=mysql_escape_string2($pattern);
	
	$f[]="('$md5','$pattern','$dedup','$zOrder','1')";
	
	
	
	
}

$data="INSERT IGNORE INTO StoreID (zmd5,pattern,dedup,zOrder,enabled) VALUES ".@implode(",", $f);

echo "\n\n".base64_encode($data)."\n\n";
