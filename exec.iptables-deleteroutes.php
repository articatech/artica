<?php
ini_set('display_errors', 1);	
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$iptables_save=find_program("iptables-save");
$iptables_restore=find_program("iptables-restore");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");

$datas=explode("\n",$data);
$pattern="#.+?ArticaIpRoute2#";



$d=0;
$conf=null;
while (list ($num, $ligne) = each ($datas) ){
	if($ligne==null){continue;}
	if(preg_match($pattern,$ligne)){$d++;continue;}
	$conf=$conf . $ligne."\n";
}
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");


function find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
			'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
	} else {
		return strpos($strProgram, '.exe');
	}
}