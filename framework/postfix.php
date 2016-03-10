<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["smtpd-recipient-restrictions"])){smtpd_recipient_restrictions();exit;}
if(isset($_GET["clamav-milter"])){clamav_milter();exit;}
if(isset($_GET["apply-networks-master"])){apply_networks();exit;}
if(isset($_GET["milters-progress"])){milters_progress();exit;}
if(isset($_GET["myhostname"])){myhostname();exit;}
if(isset($_GET["maillog-postfix"])){maillog_postfix();exit;}
if(isset($_GET["postcat-q"])){postfix_postcat_q();exit;}
if(isset($_GET["postfix-mime-header-checks"])){postfix_mime_header_checks();exit;}
if(isset($_GET["postfix-hash-smtp-generic"])){postfix_hash_smtp_generic_maps();exit;}
if(isset($_GET["apply_sender_routing_rule"])){apply_sender_routing_rule();exit;}
if(isset($_GET["smtpd-client-restrictions"])){smtpd_client_restrictions();exit;}
if(isset($_GET["mastercf"])){master_cf();exit;}
if(isset($_GET["RunSaUpd"])){RunSaUpd();exit;}
if(isset($_GET["postfix-instances-list"])){postfix_instances_list();exit;}
if(isset($_GET["postfix-reconfigure-transport"])){postfix_reconfigure_transport();exit;}
if(isset($_GET["tests-smtp-watchdog"])){test_smtp_watchdog();exit;}

if(isset($_GET["instance-delete"])){postfix_instance_delete();exit;}
if(isset($_GET["postsuper-remove-all"])){postfix_remove_all_queues();exit;}
if(isset($_GET["postfix-debug-peer-list"])){postfix_debug_peer_list();exit;}
if(isset($_GET["EnableStopPostfix"])){EnableStopPostfix();exit;}
if(isset($_GET["smtp-adv-start"])){ExecuteAdvancedRouting();exit;}
if(isset($_GET["multibubble"])){multibubble();exit;}
if(isset($_GET["reconfigure-all-instances"])){multiple_reconfigure_all();exit;}
if(isset($_GET["reconfigure-single-instance"])){multiple_reconfigure_single();exit;}
if(isset($_GET["isp-adv-remount"])){isp_adv_remount();exit;}
if(isset($_GET["query-maillog"])){query_maillog();exit();}
if(isset($_GET["mgreylist-srv"])){milter_greylist_service_debug();exit();}
if(isset($_GET["mgreylist-config"])){milter_greylist_config();exit();}
if(isset($_GET["transactions-order"])){transaction_search_postfixid();exit;}
if(isset($_GET["reconfigure-mailman"])){reconfigure_mailman();exit;}
if(isset($_GET["mailbox-transport"])){mailbox_transport();exit;}
if(isset($_GET["mailbox-transport-maps"])){mailbox_transport_maps();exit;}
if(isset($_GET["milters"])){build_milters();exit;}
if(isset($_GET["restart-mailarchiver"])){restart_mailarchiver();exit;}
if(isset($_GET["mailarchiver-status"])){mailarchiver_status();exit;}
if(isset($_GET["iredmail-status"])){iredmail_status();exit;}
if(isset($_GET["varspool"])){checks_varspool();exit;}
if(isset($_GET["changeSpool"])){changeSpool();exit;}
if(isset($_GET["stats-var-spool"])){stats_var_spool();exit;}
if(isset($_GET["CertificateConfigFile"])){CertificateConfigFile();exit;}


if(isset($_GET["islocked"])){islocked();exit;}
if(isset($_GET["RemovePostfixInterface"])){islocked_enable();exit;}
if(isset($_GET["EnablePostfixInterface"])){islocked_disable();exit;}
if(isset($_GET["happroxy"])){happroxy();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);

function postfix_postcat_q(){
	$msg_id=$_GET["postcat-q"];
	writelogs("/usr/sbin/postcat -q $msg_id >/usr/share/artica-postfix/ressources/logs/web/postcat-$msg_id.txt",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/usr/sbin/postcat -q $msg_id >/usr/share/artica-postfix/ressources/logs/web/postcat-$msg_id.txt 2>&1");
	
	@chmod("/usr/share/artica-postfix/ressources/logs/web/postcat-$msg_id.txt",0777);

}
function master_cf(){
	$servername=$_GET["instance"];
	if($servername=="master"){$path="/etc/postfix/master.cf";}else{$path="/etc/postfix-$servername/master.cf";}
	echo "<articadatascgi>". base64_encode(@file_get_contents($path))."</articadatascgi>";	
	
}

function postfix_reconfigure_transport(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
		
	if($hostname=="master"){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --transport --reload >/dev/null 2>&1 &";
	}else{
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	}
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function ExecuteAdvancedRouting(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.smtp-senderadv.php >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function smtpd_recipient_restrictions(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --smtpd-recipient-restrictions >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function isp_adv_remount(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.smtp-senderadv.php --remount >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function restart_mailarchiver(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart mailarchiver >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}


function postfix_mime_header_checks(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/HEADER_CHECK";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/HEADER_CHECK.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	if($_GET["hostname"]<>null){
		if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --mime-header-checks >{$GLOBALS["LOG_FILE"]} 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		}
		return;
	}
	$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --mime-header-checks >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function multibubble(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.multi.bubble.php >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function multiple_reconfigure_all(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.multi.bubble.php --reconfigure-all >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function multiple_reconfigure_single(){
	$hostname=$_GET["hostname"];
	if(trim($hostname)==null){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function build_milters(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters >/dev/null 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	$MilterGreyListEnabled=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled")));
	if($MilterGreyListEnabled==1){
		if(!is_file("/etc/init.d/milter-greylist")){
			shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --milter-greylist");
		}
		$cmd=trim("$nohup /etc/init.d/milter-greylist restart >/dev/null 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
		
}
function mailarchiver_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --mailarchiver --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function iredmail_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --iredmail --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
}

function happroxy(){
	$hostname=$_GET["hostname"];
	if(trim($hostname)==null){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if($hostname=="master"){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --loadbalance >/dev/null 2>&1 &";	
		shell_exec($cmd);
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
		return;		
	}
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}




function RunSaUpd(){
	$statusFileContent="ressources/logs/sa-update-status.txt";
	@file_put_contents($statusFileContent, "{scheduled}\n");
	shell_exec("/bin/chmod 777 $statusFileContent");
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function postfix_debug_peer_list(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	

	if($_GET["hostname"]<>null){
		if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --debug-peer-list >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		}
		return;
	}
	$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --debug-peer-list >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function postfix_instance_delete(){
	$unix=new unix();
	$postmulti=$unix->find_program("postmulti");
	$instance="postfix-{$_GET["instance-delete"]}";
	$cmd="$postmulti -i $instance -p stop";
	$results=array();
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$cmd="$postmulti -i $instance -e disable";
	$results=array();
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	$rm=$unix->find_program("rm");
	$directory="/var/spool/$instance";
	if(is_dir($directory)){
		$cmd="$rm -rf $directory";
		$results=array();
		exec($cmd,$results);
		writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);			
	}
	
}

function postfix_instances_list(){
	$unix=new unix();
	$search=trim(strtolower($_GET["search"]));
	if(strlen($search)>0){
		$grep=$unix->find_program("grep");
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		$searcchmd="|$grep -i -E '$search.*?\s+'";
	}
	
	$postmulti=$unix->find_program("postmulti");
	$cmd="$postmulti -l$searcchmd 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function postfix_remove_all_queues(){
	$hostname=trim($_GET["hostname"]);
	if($hostname==null){$hostname="master";}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$postsuper=$unix->find_program("postsuper");
	$conf="/etc/postfix";
	if($hostname<>"master"){$conf=" -c /etc/postfix-$hostname";}
	$cmd=trim("$postsuper$conf -d ALL >/dev/null 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.watchdog.postfix.queue.php >/dev/null 2>&1 &");
	
}

function maillog_postfix(){
	$unix=new unix();
	$maillog=$_GET["maillog"];
	if($maillog==null){echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";return;}
	$maillogSecond=$maillog;
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["filter"]));
	
	$sequence=trim($_GET["sequence"]);
	if($sequence<>null){$search="$sequence:.*?$search";}
	if($_GET["failed"]=="yes"){
		$search=$search.".*?status=defer";
	}
	
	$filename="/usr/share/artica-postfix/ressources/logs/web/postlogs$sequence";
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}

	$maincommand="$grep -i -E '\s+postfix\/(smtp|cleanup|qmgr|lmtp|postscreen|dnsblog|pickup|maildrop)' $maillog|$tail -n $max >$filename";
	if($search<>null){
		$maincommand="$grep -i -E '\s+postfix\/(smtp|cleanup|qmgr|lmtp|postscreen|dnsblog|pickup|maildrop).*?$search' $maillog|$tail -n $max >$filename 2>&1";
	}
	writelogs_framework("$maincommand",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($maincommand);
	@chmod($filename, 0777);
}

function query_maillog(){
	$unix=new unix();
	$maillog=$_GET["maillog"];
	if($maillog==null){echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";return;}
	$maillogSecond=$maillog;
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["filter"]));
	
	$emails=unserialize(base64_decode($_GET["emails"]));
	$zz=array();
	if(is_array($emails)){
		if(count($emails)>0){
			while (list ($num, $line) = each ($emails)){
				if(trim($line)==null){continue;}
				$zz[]=$line;
			}
		}
		
		if(count($zz)>0){
			$searchEmails="(".@implode("|", $zz).")";
			writelogs_framework("searchEmails = \"$searchEmails\"",__FUNCTION__,__FILE__,__LINE__);
			$searchEmails=str_replace(".", "\.", $searchEmails);
			$searchEmails=".*?$searchEmails";
		}
	}
	
	if(isset($_GET["zarafa-filter"])){
		if($_GET["zarafa-filter"]=="yes"){
			$_GET["prefix"]="\s+zarafa\-(spooler|server|gateway|dagent|license)$searchEmails";
		}
	}
	
	if(isset($_GET["miltergrey-filter"])){
		if($_GET["miltergrey-filter"]=="yes"){
			$_GET["prefix"]="\s+milter-greylist$searchEmails";
		}
	}
	
	if(isset($_GET["mimedefang-filter"])){
		if($_GET["mimedefang-filter"]=="yes"){
			$_GET["prefix"]="\s+mimedefang(\-multiplexor|\[)$searchEmails";
		}
	}	
	
	if(isset($_GET["prefix"])){
		$prefix="$grep -i -E '{$_GET["prefix"]}(\[|:)$searchEmails' $maillog|";
		$maillogSecond=null;
	}
	

	$filename="/usr/share/artica-postfix/ressources/logs/web/query.mail.log";
	
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
			$search=$unix->StringToGrep($search);
			if($searchEmails<>null){
				$cmd="$prefix$grep -i -E '$searchEmails' $maillog|$grep -E '$search'|$tail -n $max >$filename 2>&1";
			}else{
				$cmd="$prefix$grep -i -E '$search' $maillogSecond|$tail -n $max >$filename 2>&1";
			}
		
	}else{
		if($prefix<>null){
			$cmd="$prefix$tail -n $max >$filename 2>&1";
		}else{
			if($searchEmails<>null){
				$cmd="$grep -i -E '$searchEmails' $maillog|$tail -n $max >$filename 2>&1";
			}else{
				$cmd="$tail -n $max $maillog >$filename 2>&1";
			}
			
		}
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	
	
}

function milter_greylist_service_debug(){
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			$cmdp=" --hostname={$_GET["hostname"]} --ou=\"{$_GET["ou"]}\"";
		}
	}
	
	$what=$_GET["what"];
	if($what=="stop"){$what=" --stop";}
	if($what=="start"){$what=" --start";}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php$what$cmdp --who=WebInterface >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function milter_greylist_config(){
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			writelogs_framework("[{$_GET["hostname"]}] -> \"/etc/milter-greylist/{$_GET["hostname"]}/greylist.conf\"",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(serialize(file("/etc/milter-greylist/{$_GET["hostname"]}/greylist.conf")))."</articadatascgi>";
			return;
		}
	}
	
	writelogs_framework("[{$_GET["hostname"]}] -> \"/etc/milter-greylist/greylist.conf\"",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize(file("/etc/milter-greylist/greylist.conf")))."</articadatascgi>";
	
}


function EnableStopPostfix(){
	
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.stop.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.stop.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.stop.php >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
}

function transaction_search_postfixid(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();		
	$msgid=$_GET["transactions-order"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$id=$_GET["id"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.finder.php --transaction-find $msgid $id >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function reconfigure_mailman(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --mailman >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function mailbox_transport(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}

	if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --imap-sockets >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			return;
		}
}
function mailbox_transport_maps(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}

	if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.hashtables.php --mailbox-transport-maps >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			return;
		}	
}
function checks_varspool(){
	if(!is_link("/var/spool")){
		echo "<articadatascgi>". base64_encode("/var/spool")."</articadatascgi>";
		return;
	}
	
	echo  "<articadatascgi>". base64_encode(readlink("/var/spool"))."</articadatascgi>";
	
}

function stats_var_spool(){
	$unix=new unix();
	$df=$unix->find_program("df");
	if(!is_link("/var/spool")){
		$dir="/var/spool";
	}else{
		$dir=readlink("/var/spool");
	}
	
	exec("$df -h $dir 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(!preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%#", $line,$re)){continue;}
		$array["DEV"]=$re[1];
		$array["SIZE"]=$re[2];
		$array["OC"]=$re[3];
		$array["DISP"]=$re[4];
		$array["POURC"]=$re[5];
		
	}
	
	exec("$df -i $dir 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(!preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%#", $line,$re)){continue;}
		$array["INODES"]=$re[2];
		$array["IUSED"]=$re[3];
		$array["IDISP"]=$re[4];
		$array["IPOURC"]=$re[5];
	
	}	
	echo  "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function changeSpool(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$dir=$_GET["dir"];
	$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.change-spool.php $dir >/dev/null 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
}
function islocked(){
	if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){
		echo  "<articadatascgi>". base64_encode("TRUE")."</articadatascgi>";
		return;
	}
	echo  "<articadatascgi>". base64_encode("FALSE")."</articadatascgi>";
}
function islocked_enable(){
	@file_put_contents("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX", time());
	if(!is_file("/etc/init.d/artica-process1")){return;}
	shell_exec("/etc/init.d/artica-process1 start");
}
function islocked_disable(){
	@unlink("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX");
	if(!is_file("/etc/init.d/artica-process1")){return;}
	shell_exec("/etc/init.d/artica-process1 start");
}
function CertificateConfigFile(){
	if(is_file('/etc/artica-postfix/ssl.certificate.conf')){
		echo  "<articadatascgi>".@file_get_contents("/etc/artica-postfix/ssl.certificate.conf")."</articadatascgi>";
		return;
	}
	if(is_file('/usr/share/artica-postfix/ressources/databases/DEFAULT-CERTIFICATE-DB.txt')){
		echo  "<articadatascgi>".@file_get_contents("/usr/share/artica-postfix/ressources/databases/DEFAULT-CERTIFICATE-DB.txt")."</articadatascgi>";
		return;
	}

}
function smtpd_client_restrictions(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --smtpd-client-restrictions >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
}
function clamav_milter(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/APP_CLAMAV_MILTER.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/APP_CLAMAV_MILTER.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --clamav-milter >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.clamav-milter.php --restart >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function milters_progress(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_milters";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_milters.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);


	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function test_smtp_watchdog(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$dir=$_GET["dir"];
	$cmd="$php /usr/share/artica-postfix/exec.postqueue.watchdog.php --tests 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo  "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}
function postfix_hash_smtp_generic_maps(){
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --smtp-generic-maps >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function apply_networks(){
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.othervalues.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.othervalues.progress.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --networks >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


function apply_sender_routing_rule(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_sender_routing";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/build_progress_sender_routing.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	
	if($hostname=="master"){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --sender-dependent-relayhost >{$GLOBALS["LOG_FILE"]} 2>&1 &";
		shell_exec($cmd);
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		
	}
}
function myhostname(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --myhostname >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


die();