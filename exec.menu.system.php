<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--SuperAdmin"){SuperAdmin();exit;}


menu();

function SuperAdmin(){
	
	
	$POSTED["change_admin"]=@file_get_contents("/etc/artica-postfix/WIZARUSERNAME");
	$POSTED["change_password"]=@file_get_contents("/etc/artica-postfix/WIZARUSERNAMEPASSWORD");
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/ChangeLDPSSET", base64_encode(serialize($POSTED)));
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	@unlink("/etc/artica-postfix/WIZARUSERNAME");
	@unlink("/etc/artica-postfix/WIZARUSERNAMEPASSWORD");
	system("$php /usr/share/artica-postfix/exec.artica.account.progress.php");
	
	echo "* * * * DONE * * * *\n";
	
	
}


function menu(){
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();
$echo=$unix->find_program("echo");
$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ S Y S T E M -  M E N U ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
$diag[]="PASSWD \"System root password\"";
$diag[]="SuperAdmin \"Web interface SuperAdmin account\"";
$diag[]="Update \"Update tasks\"";
$diag[]="BackupRestore \"Backup and restore (snapshots)\"";
$diag[]="CleanLogs \"Clean the log partition\"";
$diag[]="OPTIMIZE \"System Optimization ( SSD Disks, HyperV, XenServer, VMWare )\"";
$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";
$f[]="function Updatep(){
	php /usr/share/artica-postfix/exec.menu.updates.php --menu
	/tmp/bash_update_menu.sh
}";
$f[]="function BackupRestorep(){";
$f[]="$php /usr/share/artica-postfix/exec.menu.snapshots.php --menu";
$f[]="/tmp/bash_snapshots_menu.sh";
$f[]="}";
$f[]="";
$f[]="function OPTIMIZE(){";
$f[]="\t$DIALOG --title \"Optimize your system\" --yesno \"This operation optimize only your system when using\\n\\n- SSD disks\\n- Microsoft HyperV\\n- VMWare ESXI\\n- XenServer\\n\\n\\nYou need to reboot after this operation\\n\\n\\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\t\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\t\trm /tmp/dns.log";
$f[]="\t\tfi";
$f[]="\t\t$echo 1 >/etc/artica-postfix/settings/Daemons/EnableSystemOptimize";
$f[]="\t\t$php /usr/share/artica-postfix/exec.vmware.php --optimize >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAMEPASSWORD";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="function CleanLogsp(){";
$f[]="\t$DIALOG --title \"Clean the log directory\" --yesno \"Do you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="chmod 0755 /usr/share/artica-postfix/bin/remove-artica-logs.sh";
$f[]="for i in $(seq 0 20 80) ; do sleep 1; echo \$i | $DIALOG --gauge \"Please wait\" 10 70 0; done";

$f[]="echo 90 | $DIALOG --gauge \"Running...\" 10 70 0";
$f[]="/usr/share/artica-postfix/bin/remove-artica-logs.sh >/dev/null 2>&1";
$f[]="sleep 1";
$f[]="echo 100 | $DIALOG --gauge \"Please wait\" 10 70 0";
$f[]="sleep 1";
$f[]="\t$DIALOG --title \"Clean the log directory\" --msgbox \"Your log directory as been cleaned...\"  0 0";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="function PASSWDY(){";
$f[]="\tpasswd root";	
$f[]="}";
$f[]="";
$f[]="function SuperAdmin(){";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$DIALOG --clear --title \"Username\" --inputbox \"Enter the SuperAdmin username\" 10 68 Manager 2> /etc/artica-postfix/WIZARUSERNAME";

$f[]="\tcase $? in";
$f[]="\t\t1)";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME || true";
$f[]="\t\treturn";
$f[]="\tesac";
$f[]="WIZARUSERNAME = `cat /etc/artica-postfix/WIZARUSERNAME`";

$f[]="\t$DIALOG --clear --insecure --passwordbox \"ENTER SuperAdmin Password for authentication\"  10 68 secret 2> /etc/artica-postfix/WIZARUSERNAMEPASSWORD";
$f[]="\tcase $? in";
$f[]="\t\t1)";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME || true";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAMEPASSWORD || true";
$f[]="\t\treturn";
$f[]="\tesac";
$f[]="\t$DIALOG --title \"Change SuperAdmin Account\" --yesno \"Do you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php ".__FILE__." --SuperAdmin >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAMEPASSWORD";
$f[]="\t\treturn;;";

$f[]="\t1)";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAMEPASSWORD";
$f[]="\t\treturn;;";

$f[]="\t255)";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAME";
$f[]="\t\trm /etc/artica-postfix/WIZARUSERNAMEPASSWORD";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="";

$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="OPTIMIZE) OPTIMIZE;;";
$f[]="BackupRestore) BackupRestorep;;";
$f[]="PASSWD) PASSWDY;;";
$f[]="SuperAdmin) SuperAdmin;;";
$f[]="Update) Updatep;;";
$f[]="CleanLogs) CleanLogsp;;";
$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_system_menu.sh\n";}
@file_put_contents("/tmp/bash_system_menu.sh", @implode("\n",$f));
@chmod("/tmp/bash_system_menu.sh",0755);
	
}


