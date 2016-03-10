<?php
if(!is_file('/etc/postfix/main.cf')){die();}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


$unix=new unix();
$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
@mkdir(dirname($pidFile),0755);
$pid=$unix->get_pid_from_file($pidFile);
if($unix->process_exists($pid,basename(__FILE__))){
	writelogs("PID $pid already exists, aborting...",__FUNCTION__,__FILE__,__LINE__);
	die();
}

@file_put_contents($pidFile, getmypid());


WhiteListed();

function WhiteListed(){
	$ldap=new clladp();
	
	$unix=new unix();
	$SPAMASSASSIN_LOCAL_CF=$unix->SPAMASSASSIN_LOCAL_CF();
	$spammassDirectory=dirname($SPAMASSASSIN_LOCAL_CF);
	$assp=array();
	$spamassassin=array();
	$miltergrey=array();


$blacks=$ldap->BlackListFromDomain();
if(is_array($blacks)){
while (list ($to, $array) = each ($blacks)){
		$spamassassin[]="#rcpt :$to";
		while (list ($index, $from) = each ($array)){
			if($from=="*@*"){continue;}
			if(preg_match("#(.+?)@(.+)#",$from,$re)){
				$first_part=$re[1];
				$domain=$re[2];
			}else{
				$first_part="*";
				$domain=$from;
			}
			$domain=str_replace("@", "", $domain);
			$spamassassin[]="blacklist_from\t$first_part@$domain";
			
		}
		
	}
}


echo "Starting......: ".date("H:i:s")." writing whitelist/blacklists for ASSP\n";
@mkdir("/usr/share/assp/files");
@mkdir("/usr/local/etc");
@file_put_contents("/usr/share/assp/files/whiteorg.txt",implode("\n",$assp));
echo "Starting......: ".date("H:i:s")." writing whitelist/blacklists for Amavis\n";
$final=implode("\n",$sender_scores_sitewide);
$final=$final."\n";
@file_put_contents("/usr/local/etc/sender_scores_sitewide",$final);
@chmod("/usr/local/etc/sender_scores_sitewide",0644);
@chown("/usr/local/etc/sender_scores_sitewide","postfix");
if(is_file('/usr/local/sbin/amavisd')){
	if(is_file('/usr/local/etc/amavisd.conf')){
		if(is_file('/var/spool/postfix/var/run/amavisd-new/amavisd-new.pid')){
			sys_THREAD_COMMAND_SET('/usr/local/sbin/amavisd -c /usr/local/etc/amavisd.conf -P /var/spool/postfix/var/run/amavisd-new/amavisd-new.pid reload');
		}
	}
}



echo "Starting......: ".date("H:i:s")." writing whitelist/blacklists for spamassassin\n";
@file_put_contents("$spammassDirectory/wbl.cf",implode("\n",$spamassassin));
}




?>