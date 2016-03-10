<?php
$GLOBALS["AS_ROOT"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
	$GLOBALS["AS_ROOT"]=true;
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).  '/framework/class.unix.inc');
	include_once(dirname(__FILE__).  '/framework/frame.class.inc');
	include_once(dirname(__FILE__).  '/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).  '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__).  "/ressources/class.tcpip.inc");
		

	
	xrun();
	
	
	
function xrun(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$SpamAssassinUrlScore=intval($sock->GET_INFO("SpamAssassinUrlScore"));
	$SpamAssassinScrapScore=intval($sock->GET_INFO("SpamAssassinScrapScore"));
	$SpamAssassinSubjectsScore=intval($sock->GET_INFO("SpamAssassinSubjectsScore"));
	if($SpamAssassinUrlScore==0){$SpamAssassinUrlScore=9;}
	if($SpamAssassinScrapScore==0){$SpamAssassinScrapScore=6;}
	if($SpamAssassinSubjectsScore==0){$SpamAssassinSubjectsScore=3;}
	$TargetFilename="/etc/spamassassin/ArticaUrlsRules.cf";
	$TargetFilename2="/etc/spamassassin/ArticaEscrapRules.cf";
	$TargetFilename3="/etc/spamassassin/ArticaSubjectsRules.cf";
	
	$q=new mysql();
	$sql="SELECT * FROM spamasssin_baddomains";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		build_progress(110, "MySQL Error");
		return;
	}
	$f=array();
	$f2=array();
	build_progress(50, "Building rules...");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_BAD_URLS_$ID";
		$RuleName2="ARTICATECH_BAD_URLS_$ID";
		build_progress(50, "Building rule $pattern...");
		
		$f[]="uri $RuleName /$pattern/";
		$f[]="describe $RuleName Urls found in spam messages by Messaging Team.";
		$f[]="score $RuleName $SpamAssassinUrlScore";
		
		$f2[]="uri $RuleName2 /$pattern/";
		$f2[]="describe $RuleName2 Urls found in spam messages - by Artica Team.";
		$f2[]="score $RuleName2 9";
		
	}
	

	
	
	$f2[]="uri ARTICA_MICRO_LINK /\/\/goo\.gl\//";
	$f2[]="describe ARTICA_MICRO_LINK goo.gl found in message - by Artica Team.";
	$f2[]="score ARTICA_MICRO_LINK 2";
	
	$f2[]="uri UNSUBSCRIBE_NEWS_LETTER  /\/unsubscribe\.php\?/";
	$f2[]="score UNSUBSCRIBE_NEWS_LETTER 1";
	$f2[]="describe UNSUBSCRIBE_NEWS_LETTER Probably a news letter - by Artica Team.";
	
	$f2[]="uri ARTICA_DYNALINK_INTEGER_1 /\/(link|unsubscribe)\.php\?M=[0-9]+/";
	$f2[]="describe ARTICA_DYNALINK_INTEGER_1 Probably a tracker that point to an integer value - by Artica Team.";
	$f2[]="score ARTICA_DYNALINK_INTEGER_1 2";
	
	
	$f2[]="uri ARTICA_URI_EXE /\.(?:exe|scr|dll|pif|vbs|wsh|cmd|bat)(?:\W{0,20}$|\?)/i";
	$f2[]="describe ARTICA_URI_EXE link contains executables files - by Artica Team.";
	$f2[]="score ARTICA_URI_EXE 3";
	
	
	
	
	@unlink($TargetFilename);
	@file_put_contents($TargetFilename, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules1.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules1.cf", @implode("\n", $f2)."\n");
	
	
	$sql="SELECT * FROM spamasssin_escrap";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		build_progress(110, "MySQL Error");
		return;
	}
	$f=array();
	$f2=array();
	$f2[]="# # # e-Scrap From Artica Team, builded on ". date("Y-m-d H:i:s")."\n";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_SCRAP_$ID";
		$RuleName2="ARTICATECH_SCRAP_$ID";
		build_progress(60, "Building rules $pattern...");

		
		$f[]="header $RuleName From =~ /$pattern/i";
		$f[]="score $RuleName  $SpamAssassinScrapScore";
		$f[]="describe $RuleName From e-scrap messages - non sollicted mails";

	
		$f2[]="header $RuleName2 From =~ /$pattern/i";
		$f2[]="score $RuleName2  6";
		$f2[]="describe $RuleName2 From e-scrap messages - non sollicted mails by Artica Team";
	
	}
	
	$f2[]="header BOUNCE_NEWS_ADDR From =~ /\@bounce\.news\./i";
	$f2[]="score BOUNCE_NEWS_ADDR  3";
	$f2[]="describe BOUNCE_NEWS_ADDR From e-scrap messages - @bounce.news. in mail addr by Artica Team";
	
	$f2[]="header INVITATION_INADDR From =~ /\@invitation\..*?\.(com|fr|net)/i";
	$f2[]="score INVITATION_INADDR  3";
	$f2[]="describe INVITATION_INADDR From e-scrap messages - @invitation. something in mail addr by Artica Team";
	
	$f2[]="header WEBMASTER_INADDR From =~ /(bounce|noreply|webmaster|www-data)\@/i";
	$f2[]="score WEBMASTER_INADDR  2";
	$f2[]="describe WEBMASTER_INADDR From e-scrap messages - bounce,noreply,WebMaster,www-data is a generic mail address by Artica Team";
	
	
	$f2[]="header LEGETIMATE_BANK From =~ /\@bnpparibas\.com/i";
	$f2[]="score LEGETIMATE_BANK  -5";
	$f2[]="describe LEGETIMATE_BANK From legetimate bank - by Artica Team";
	
	$f2[]="header LEGETIMATE_GOOGLE From =~ /noreply\@youtube\.com/i";
	$f2[]="score LEGETIMATE_GOOGLE  -5";
	$f2[]="describe LEGETIMATE_GOOGLE From Google mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_TWITTER From =~ /\@twitter.com/i";
	$f2[]="score LEGETIMATE_TWITTER  -5";
	$f2[]="describe LEGETIMATE_TWITTER From twitter mailing lists - by Artica Team";

	$f2[]="header LEGETIMATE_GOOGLE From =~ /googlealerts-noreply\@google\.com/i";
	$f2[]="score LEGETIMATE_GOOGLE  -5";
	$f2[]="describe LEGETIMATE_GOOGLE From Google mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_PAYPAL From =~ /[a-z]+\@paypal\.(fr|com|de|it|es|pt|pl)/i";
	$f2[]="score LEGETIMATE_PAYPAL  -5";
	$f2[]="describe LEGETIMATE_PAYPAL From PayPal mailing lists - by Artica Team";
	
	$f2[]="header LEGETIMATE_VIADEO From =~ /\@[a-z]+\.viadeo\.com/i";
	$f2[]="score LEGETIMATE_VIADEO  -5";
	$f2[]="describe LEGETIMATE_VIADEO From viadeo mailing lists - by Artica Team";
	
	
	
	
	
	$f2[]="header VENTE_FLASH Subject =~ /vente flash/i";
	$f2[]="score VENTE_FLASH  3";
	$f2[]="describe VENTE_FLASH Subject - Seems a flash sales - by Artica Team";
	
	$f2[]="header X_ACCORHOTELS_PRESENT		   exists:X-Accorhotels-ReservationDate";
	$f2[]="describe X_ACCORHOTELS_PRESENT      Message has X-Accorhotels-ReservationDate";
	$f2[]="score X_ACCORHOTELS_PRESENT         -8";	
	
	$f2[]="header X_IRONPORT_PRESENT		exists:X-IronPort-AV";
	$f2[]="describe X_IRONPORT_PRESENT      Message has X-IronPort-AV";
	$f2[]="score X_IRONPORT_PRESENT         -5";
	
	
	$f2[]="header X_LINKEDIN_PRESENT		exists:X-LinkedIn-Id";
	$f2[]="describe X_LINKEDIN_PRESENT      Message has X-LinkedIn-Id";
	$f2[]="score X_LINKEDIN_PRESENT         -9";
		
	$f2[]="header X_BEVERLYMAIL_PRESENT        exists:X-BeverlyMail-Recipient";
	$f2[]="describe X_BEVERLYMAIL_PRESENT      Message has X-BeverlyMail-Recipient";
	$f2[]="score X_BEVERLYMAIL_PRESENT         3";
	
	$f2[]="header X_MAILINCAMPAIGN_PRESENT        exists:X-Mailin-Campaign";
	$f2[]="describe X_MAILINCAMPAIGN_PRESENT      Message has X-Mailin-Campaign";
	$f2[]="score X_MAILINCAMPAIGN_PRESENT         3";
	
	
	
	$f2[]="header X_MAILERSID_PRESENT        exists:X-Mailer-SID";
	$f2[]="describe X_MAILERSID_PRESENT      Message has X-Mailer-SID";
	$f2[]="score X_MAILERSID_PRESENT         3";
	
	@unlink($TargetFilename2);
	@file_put_contents($TargetFilename2, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules3.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules3.cf", @implode("\n", $f2)."\n");
	
	
	$sql="SELECT * FROM spamasssin_subjects";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		build_progress(110, "MySQL Error");
		return;
	}
	$f=array();
	$f2=array();
	$f2[]="# # # Subjects From Artica Team, builded on ". date("Y-m-d H:i:s")."\n";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$pattern=pattern_replace($ligne["pattern"]);
		if($pattern==null){continue;}
		$RuleName="ARTICA_SUBJECT_$ID";
		$RuleName2="ARTICATECH_SUBJECT_$ID";
		build_progress(60, "Building rules $pattern...");
	
	
		$f[]="header $RuleName Subject =~ /$pattern/i";
		$f[]="score $RuleName  $SpamAssassinSubjectsScore";
		$f[]="describe $RuleName Subject - non sollicted mails";
	
	
		$f2[]="header $RuleName2 Subject =~ /$pattern/i";
		$f2[]="score $RuleName2  3";
		$f2[]="describe $RuleName2 Subject - non sollicted mails by Artica Team";
	
	}

	@unlink($TargetFilename3);
	@file_put_contents($TargetFilename3, @implode("\n", $f)."\n");
	
	@unlink("/etc/artica-postfix/spamassassin-rules4.cf");
	@file_put_contents("/etc/artica-postfix/spamassassin-rules4.cf", @implode("\n", $f2)."\n");	
	
	build_progress(70, "Building rules {done}...");
	Reload();
	build_progress(95, "{exporting_rules}...");
	shell_exec("$php /usr/share/artica-postfix/exec.milter-greylist.cloud.php >/dev/null 2>&1");
	build_progress(100, "{done}...");
}

function pattern_replace($pattern){
	$pattern=trim($pattern);
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	$pattern=str_replace("@", "\@", $pattern);
	$pattern=str_replace("#END", "$", $pattern);
	$pattern=str_replace("#ALPHANUM", "[a-z0-9]+", $pattern);
	return $pattern;
	
}

function Reload(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(60, "{reloading_service} 1/4...");
	system("/etc/init.d/spamassassin reload");
	build_progress(70, "{reloading_service} 2/4...");
	system("/etc/init.d/spamass-milter restart");
	build_progress(71, "{reloading_service} 3/4...");
	@copy("/usr/share/artica-postfix/bin/install/mimedefang/mimedefang-filter.pl", "/etc/mail/mimedefang-filter");
	system("/etc/init.d/mimedefang reload");
	build_progress(72, "{reloading_service} 4/4...");
	system("/etc/init.d/postfix-logger restart");	
	build_progress(73, "{reloading_service} {done}...");
}

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress",0755);
}

function CheckSecuritiesFolders(){
	if(is_dir("/etc/mail/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/mail/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/mail/spamassassin");
		shell_exec("/bin/chmod 755 /etc/mail/spamassassin");		
	}
	if(is_dir("/etc/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/spamassassin");
		shell_exec("/bin/chmod 755 /etc/spamassassin");
	}
	
	if(is_dir("/var/lib/spamassassin")){
		shell_exec("/bin/chmod -R 755 /var/lib/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /var/lib/spamassassin");
	}	
	
}







?>