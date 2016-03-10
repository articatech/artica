<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');	


subdomains();









function subdomains(){
	$subdomains="https://ransomwaretracker.abuse.ch/downloads/RW_DOMBL.txt";
	
	$MAIN=unserialize(@file_get_contents("/root/ransomwaretracker.db"));
	
	$curl=new ccurl($subdomains);
	if($curl->GetFile("/root/RW_DOMBL.txt")){
		$f=explode("\n",@file_get_contents("/root/RW_DOMBL.txt"));
	
		while (list ($index, $line) = each ($f) ){
			$line=trim($line);
			if(substr($line, 0,1)=="#"){continue;}
			$MAIN["DOMAINS"][$line]=true;
		}
		
	}else{
		echo "$subdomains failed\n";
		
	}
	
	$ips="https://ransomwaretracker.abuse.ch/downloads/RW_IPBL.txt";
	$curl=new ccurl($ips);
	if($curl->GetFile("/root/RW_IPBL.txt")){
		$f=explode("\n",@file_get_contents("/root/RW_IPBL.txt"));
	
		while (list ($index, $line) = each ($f) ){
			$line=trim($line);
			if(substr($line, 0,1)=="#"){continue;}
			$MAIN["IPS"][$line]=true;
		}
	
	}else{
		echo "$ips failed\n";
	
	}	
	
	
	$uris="https://ransomwaretracker.abuse.ch/downloads/RW_URLBL.txt";
	$curl=new ccurl($uris);
	if($curl->GetFile("/root/RW_URLBL.txt")){
		$f=explode("\n",@file_get_contents("/root/RW_URLBL.txt"));
	
		while (list ($index, $line) = each ($f) ){
			$line=trim($line);
			if(substr($line, 0,1)=="#"){continue;}
			$MAIN["URIS"][$line]=true;
		}
	
	}else{
		echo "$uris failed\n";
	
	}	
	
	$MAIN2["TIME"]=time();
	$MAIN2["MD5"]=md5(serialize($MAIN));
	@file_put_contents("/root/ransomwaretracker.db", serialize($MAIN));
	@file_put_contents("/root/ransomwaretracker.txt", serialize($MAIN2));
	$unix=new unix();
	$unix->compress("/root/ransomwaretracker.db", "/root/ransomwaretracker.gz");
	PushToRepo("/root/ransomwaretracker.txt");
	
	PushToRepo("/root/ransomwaretracker.gz");
	
	
}
function PushToRepo($filepath){
	$curl="/usr/bin/curl";
	$unix=new unix();
	$ftpass5=trim(@file_get_contents("/root/ftp-password5"));
	$uri="ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS";
	$size=round(filesize($filepath)/1024);
	$ftpass5=$unix->shellEscapeChars($ftpass5);
	echo "Push $filepath ( $size KB ) to $uri\n";
	system("$curl -T $filepath $uri/ --user $ftpass5");
}



