<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["generate-key"])){generate_key();exit;}
if(isset($_GET["generate-x509"])){generate_x509();exit;}
if(isset($_GET["generate-x509-client"])){generate_x509_client();exit;}

if(isset($_GET["tomysql"])){tomysql();exit;}
if(isset($_GET["copy-privatekey"])){copy_private_key();exit;}
if(isset($_GET["move-privkey"])){move_private_key();exit;}
if(isset($_GET["gen-csr"])){gencsr();exit;}
if(isset($_GET["copy-csr"])){copy_csr();exit;}
if(isset($_GET["generate-csr"])){generate_CSR();exit;}
if(isset($_GET["pvk-convert"])){pvk_convert();exit;}
if(isset($_GET["pfx-convert"])){pfx_convert();exit;}
if(isset($_GET["generate-pfx"])){pfx_generate();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function generate_key(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["generate-key"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --buildkey $servername >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function copy_private_key(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	
	if(!is_file("/etc/openssl/private-key/privkey.key")){
		@mkdir("/etc/openssl/private-key",0755,true);
		shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	@copy("/etc/openssl/private-key/privkey.key","/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key",0777);
}

function move_private_key(){
	@mkdir("/etc/openssl/private-key",0755,true);
	@unlink("/etc/openssl/private-key/privkey.key");
	@copy("/usr/share/artica-postfix/ressources/conf/upload/privkey.key","/etc/openssl/private-key/privkey.key");
	@unlink("/usr/share/artica-postfix/ressources/conf/upload/privkey.key");
}

function copy_csr(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	@copy("/etc/openssl/private-key/server.csr","/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr",0777);
	
}

function gencsr(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	@copy("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY", "/etc/artica-postfix/settings/Daemons/CertificateCenterCSR");
	$ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY"));
	@unlink("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY");
	$CommonName=$ligne["CommonName"];
	if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	@mkdir("/etc/openssl/private-key",0755,true);
	$ligne["password"]=escapeshellcmd($ligne["password"]);
	$C=$ligne["CountryName"];
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
	
	if(!is_file("/etc/openssl/private-key/privkey.key")){
		@mkdir("/etc/openssl/private-key",0755,true);
		shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
	}
	
	$cmd[]="$openssl req -new -sha256 -key /etc/openssl/private-key/privkey.key";
	$cmd[]="-passin pass:{$ligne["password"]}";
	$cmd[]="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" -out /etc/openssl/private-key/server.csr 2>&1";
	$cmdline=@implode(" ", $cmd);
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdline);
}


function generate_x509_client(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=$_GET["generate-x509-client"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --client-server \"$servername\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
	
}
function generate_CSR(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);


	$servername=$_GET["generate-csr"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --BuildCSR \"$servername\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";

}

function generate_x509(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=$_GET["generate-x509"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --x509 $servername --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
}
function tomysql(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["tomysql"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --mysql $servername 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";	
	
}

function pvk_convert(){
	$servername=$_GET["pvk-convert"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --pvk \"$servername\" 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";
	
}
function pfx_convert(){
	$servername=$_GET["pfx-convert"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --pfx-convert \"$servername\" 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";

}


function pfx_generate(){
	$servername=$_GET["generate-pfx"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();


	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pfx $servername >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

