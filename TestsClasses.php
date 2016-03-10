<?php

ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
$GLOBALS["VERBOSE"]=true;




$array[]="^microsoft.com";
$array[]="update.microsoft.com";
$array[]="nttdata.com";
$array[]="kds.keane.com";
$array[]="mail703.kds.keane.com";
$array[]="outlookanywhere.keane.com";
$array[]="toto.titi.tata.com";

$squid=new squid_acls();
print_r($squid->clean_dstdomains($array));

