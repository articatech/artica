<?php
include_once(dirname(__FILE__)."/class.maillog.tools.inc");
class postfix_maillog_buffer{
	var $buffer=null;
	
	
	function postfix_maillog_buffer($buffer){
		$this->buffer=$buffer;
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
		if(!isset($GLOBALS["POSTCONF_BIN"])){$GLOBALS["POSTCONF_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program('postconf');}
		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		if(!isset($GLOBALS["MYSQL_POSTFIX"])){$GLOBALS["MYSQL_POSTFIX"]=new mysql_postfix_builder();}
		if(!isset($GLOBALS["EnablePostfixMultiInstance"])){$GLOBALS["EnablePostfixMultiInstance"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");}
		if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
		
		if(!is_numeric($GLOBALS["EnablePostfixMultiInstance"])){$GLOBALS["EnablePostfixMultiInstance"]=0;}
	}
	
	public function parse(){
		$this->CheckDirs();
		$buffer=$this->buffer;
		if(preg_match("#refreshing the Postfix mail system#i", $buffer)){return true;}
		if(preg_match("#reload -- version .*?, configuration#i", $buffer)){return true;}
		
		
		if(preg_match("#postfix\/(lmtp|smtp).*?:(.+?):\s+to=<(.+?)>.*?relay.*?status=deferred.*?unable to verify user#",$buffer,$re)){
			$messageid=$re[1];
			$recipient=$re[2];
			$cause="Mailbox Unavailable";
			$this->routed_info($messageid,null,$recipient,null,null,$cause);
			return true;
		}
		
		if(preg_match("#postfix\/(lmtp|smtp).*?:(.+?):\s+to=<(.+?)>.*?relay.*?status=bounced.*?User does not exist#",$buffer,$re)){
			$messageid=$re[1];
			$recipient=$re[2];
			$cause="Mailbox Unavailable";
			$this->routed_info($messageid,null,$recipient,null,null,$cause);
			return true;
		}
		if(preg_match("#postfix\/(lmtp|smtp).*?:(.+?):\s+to=<(.+?)>.*?relay.*?status=sent#",$buffer,$re)){
			$messageid=$re[1];
			$recipient=$re[2];
			$cause="Sended";
			$this->routed_info($messageid,null,$recipient,null,null,$cause);
			return true;
		}
		
		
		
		
		if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.+?)\]: 503 5.5.0.*?Client host rejected: Improper use of SMTP command pipelining; from=<(.*?)> to=<(.*?)>#",$buffer,$re)){
			$this->connection_failed("Non-SMTP",$re[2],$re[3],null,$re[1]);
			return true;
		}
		
		if(preg_match("#(.*?):\s+to=<(.*?)>,\s+relay=(.+?)\[(.*?)\]:.*?,\s+delay=([0-9\.]+),\s+delays=#",$buffer,$re)){	
			$messageid=$re[1];
			$recipient=$re[2];
			$hostname=$re[3];
			$ipaddr=$re[4];
			$delay=$re[5]*1000;
			$this->routed($messageid,$recipient,$hostname,$ipaddr,$delay);
			return true;
		}

		
		
		if(preg_match("#cleanup\[.*?:\s+([0-9A-Z]+):\s+redirect: header Subject:.*?from (.+?)\[(.+?)\]; from=<(.*?)> to=<(.*?)>.*?:\s+(.+)#",$buffer,$re)){
			$messageid=$re[1];
			$hostname=$re[3];
			$ipaddr=$re[2];
			$recipient=$re[2];
			$sender=$re[4];
			$recipient=$re[5];
			$cause=trim($re[6]);
			$this->routed_info($messageid,$sender,$recipient,$hostname,$ipaddr,$cause);
			return true;
		}
	
		
		
		
		if(preg_match("#:\s+(.+?):\s+from=<(.*?)>,\s+size=([0-9]+)#",$buffer,$re)){	
			$messageid=$re[1];
			$mailfrom=$re[2];
			$size=$re[3];
			$this->routed_from($messageid,$mailfrom,$size);
			return true;
		}	
		
		if(preg_match("#:\s+(.+?):\s+client=(.+?)\[(.+?)\]#",$buffer,$re)){	
			$messageid=$re[1];
			$hostname=$re[2];
			$ipaddr=$re[3];
			$this->routed_cnx($messageid,$hostname,$ipaddr);
			return true;
		}	
// ----------------------------------------------------------------- Create Postfix user -----------------------------------------		
		if(preg_match("#parameter mail_owner: unknown user name value: postfix#i",$buffer)){
			$GLOBALS["CLASS_UNIX"]->CreateUnixUser("postfix","postfix");
			return true;
		}
		
// ----------------------------------------------------------------- Restart Amavis -----------------------------------------		
		if(preg_match("#postfix\/error\[.*?delivery temporarily suspended.*?lost connection with\s+127\.0\.0\.1#i",$buffer)){
			if($GLOBALS["AMAVIS_INSTALLED"]){
				if($GLOBALS["EnableAmavisDaemon"]==1){
					$timefile="/etc/artica-postfix/croned.1/".md5("deliverytemporarilysuspendeddeliverytemporarilysuspended:lostconnectionwith127.0.0.1");
					$TimeMin=$this->file_time_min($timefile);
					if($TimeMin>8){
					$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/amavis restart >/dev/null 2>&1 &";
					$this->email_events("[AMAVISD]:delivery temporarily suspended: lost connection with 127.0.0.1",$buffer."\nArtica will restart amavis",'postfix');
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,time());
					}
				}
			}
		}
		
// -----------------------------------------------------------------------------------------------------------------------------------------		
		if(preg_match("#klms-milter:.+?started,\s+pid.*?([0-9]+).*?scanner.*?=(.*)#",$buffer,$re )){
			$this->email_events("Kaspersky Mail security Milter started pid {$re[1]} on {$re[2]}",$buffer,'klms');
			return true;
		}
// -----------------------------------------------------------------------------------------------------------------------------------------		

	if(preg_match("#connect to Milter service unix:.*?maildump\/maildump\.socket: No such file or directory#",$buffer,$re )){
			$timefile="/etc/artica-postfix/croned.1/NoSuchFile-".md5("maildump.socket");
			$TimeMin=$this->file_time_min($timefile);
			$this->events("maildump.socket such file -> START IT",__LINE__);
			if($TimeMin>5){
				shell_exec("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.initslapd.php --mailarchive-perl");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d//etc/init.d/mailarchive-perl restart >/dev/null 2>&1 &";
				shell_exec($cmd);
				@unlink($timefile);
				@file_put_contents($timefile,time());
				return;
			}
			return;			
		}
// -----------------------------------------------------------------------------------------------------------------------------------------		
		
		if(preg_match("#postfix\[.*?fatal:\s+chdir\((.+?)\):\s+No such file or directory#",$buffer,$re )){
			$dir=$re[1];
			$timefile="/etc/artica-postfix/croned.1/chdir-".md5($dir);
			$TimeMin=$this->file_time_min($timefile);
			$this->events("$dir such dir",__LINE__);
			if($TimeMin>5){
				if(preg_match("#\/var\/spool\/postfix-(.+)", $dir,$re)){
					$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure {$re[1]} >/dev/null 2>&1 &";
					$this->events("$cmd",__LINE__);
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,time());					
					return;
				}
				@mkdir($dir);
				@chgrp($dir, "postfix");
				@chown($dir, "postfix");
				@unlink($timefile);
				@file_put_contents($timefile,time());				
				return;
			}
		}
		
		
		if(preg_match("#postfix\/local.+?status=deferred\s+\(.*?alias database unavailable#",$buffer,$re )){
			$this->events("Master instance Alias database unavailable",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/postfix-alias-database-unavailable";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.hashtables.php --aliases >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
				@unlink($timefile);
				@file_put_contents($timefile,"#");
				$this->email_events("alias database unavailable","Postfix claim $buffer,\naliases database will be ordered to be generated",'postfix');
			}
			return true;
		}		
		
		if(preg_match("#postfix\[.+?: fatal: bad string length 0 < 1: inet_interfaces#", $buffer,$re)){
			$timefile="/etc/artica-postfix/croned.1/master-fatal-".md5("fatal: bad string length 0 < 1: inet_interfaces");
			$TimeMin=$this->file_time_min($timefile);
			$this->events("Master instance bad string length 0 < 1: inet_interfaces",__LINE__);
			if($GLOBALS["EnablePostfixMultiInstance"]==1){
				$cmd="{$GLOBALS["POSTCONF_BIN"]} -c /etc/postfix -e 'inet_interfaces=127.0.0.1'";
				$this->events("EnablePostfixMultiInstance:: $cmd",__LINE__);
				shell_exec($cmd);
				@unlink($timefile);
				@file_put_contents($timefile,time());
			}
		}
		
		
		if(preg_match("#smtpd\[.+?warning.*?SASL authentication failure: cannot connect to saslauthd server: Permission denied#i",$buffer,$re)){
			$this->events("SASL authentication failure !!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/restart-saslauthd";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>3){
				@chmod("/var/run/saslauthd",0755);
				$this->email_events("SASL authentication failure (Permission denied) ","Postfix claim: $buffer\nsaslauthd will be restarted","postfix");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/saslauthd restart >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,time());				
			}	
			return true;
		}
		
		if(preg_match("#\/lmtp\[.+?127\.0\.0\.1\[127\.0\.0\.1\]:2003.+?deferred \(conversation.+?timed out while receiving the initial server greeting#", $buffer)){
			$this->events("LMTP timedout (Zarafa ?)",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/127.0.0.1.2003.timedout";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>3){
				if($GLOBALS["ZARAFA_INSTALLED"]){
					$this->email_events("zarafa-dagent timed out...","Postfix claim: $buffer\nZarafa-dagent will be restarted","postfix");
					$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/zarafa-dagent restart restart >/dev/null 2>&1 &";
					$this->events($cmd);
					shell_exec($cmd);
			 		@unlink($timefile);
					@file_put_contents($timefile,time());				
				}			
			}
		}
		
		
		if(preg_match("#relay=none,\s+delay=.+?status=deferred.+?suspended:\s+connect to.+?\.0\.0\.1\]:10024:\s+Connection refused#",$buffer,$re )){
			$this->events("restart amavis !!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/restart-amavis";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>5){
				$this->email_events("Amavis is out of service, restart amavis","Postfix claim: $buffer\nAmavis is restarted","postfix");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/amavis restart >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}			
		}
		
		if(preg_match("#\/master\[.+?fatal:\s+bind\s+0\.0\.0\.0\s+port\s+25:\s+Address already in use#",$buffer,$re )){
			if(is_file("/usr/sbin/exim4")){
				if(is_file("/usr/bin/apt-get")){
				shell_exec("{$GLOBALS["NOHUP_PATH"]} /bin/kill -9 `/bin/pidof /usr/sbin/exim4` >/dev/null 2>&1 &");
				$cmd="{$GLOBALS["NOHUP_PATH"]} /usr/bin/apt-get --purge --yes --force-yes --remove exim* >/dev/null 2>&1 &";
				$this->events("bind 0.0.0.0 port 25: Address already in use: $cmd ",__LINE__); 
				}
			}
		}
		
		if(preg_match("#postfix\/smtpd\[.+?:\s+error:\s+open database\s+(.+?)\.db:\s+No such file or directory#",$buffer,$re )){
			$file=$re[1];
			@file_put_contents($file, "\n");
			$cmd="{$GLOBALS["POSTMAP_PATH"]} hash:$file >/dev/null 2>&1";
			$this->events($cmd,__LINE__);
			shell_exec($cmd);
			return true;
		}
		
		
		if(preg_match("#postfix\/smtpd.*?warning: connect to Milter service unix:.*?\/MIMEDefang\/mimedefang\.sock#",$buffer,$re )){
			$sock=new sockets();
			$MimeDefangEnabled=trim($sock->GET_INFO("MimeDefangEnabled",true));
			if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}
			if($MimeDefangEnabled==0){
				$this->events("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --milters >/dev/null 2>&1 &");
				shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --milters >/dev/null 2>&1 &");
			}
			return true;
		}
		
		if(preg_match("#postfix\/postfix-script.?warning: not set-gid or not owner\+group\+world executable:\s+(.*)#",$buffer,$re )){
			$unix=new unix();
			$chmod=$unix->find_program("chmod");
			shell_exec("$chmod g+s {$re[1]} >/dev/null 2>&1");
			$this->events("$chmod g+s {$re[1]} >/dev/null 2>&1");
			return true;
		}
		
		
		
		if(preg_match("#\/postfix-script\[.+?warning: not owned by root:\s+(.+)#",$buffer,$re )){
			$this->events("chown root {$re[1]}",__LINE__);
			@chown(trim($re[1]),"root");
			return true;
		}
		
		
		if(preg_match("#postfix-(.*?)\/smtpd\[.+?warning: connect to Milter service\s+unix:(.*?):.*?Permission denied#i",$buffer,$re )){
			$unix=new unix();
			$file=$re[2];
			
			if(preg_match("#milter-greylist#", $file)){
				if(!is_file($file)){
					@chown("/var/spool/postfix/var/run/milter-greylist", "postfix");
					@chgrp("/var/spool/postfix/var/run/milter-greylist", "postfix");
					@chmod("/var/spool/postfix/var/run/milter-greylist",0755);					
					$this->events("Milter-greylist:[{$re[1]}] $file, no such file -> start it",__LINE__);
					shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.milter-greylist.php --start --hostname={$re[1]} >/dev/null 2>&1");
					$this->email_events("instance: {$re[1]} $file no such file","Postfix claim $buffer,\nArtica will start the milter-greylist instance",'postfix');
				}
			}
			$this->events("{$re[1]} permission denied on $file",__LINE__);
			$unix->chown_func("postfix","postfix", $file);
			$unix->chown_func("postfix","postfix", dirname($file));
			$unix->chmod_func(0777, $file);
			$this->email_events("instance: {$re[1]} permission $file issue","Postfix claim $buffer,\nArtica will chown this file and chmod it...",'postfix');
			return true;	
		}
		
		if(preg_match("#\/postfix-script\[.+?warning: not owned by group\s+(.+?):\s+(.+)#",$buffer,$re )){
			$this->events("chown group {$re[1]} ->{$re[2]}",__LINE__);
			@chgrp(trim($re[2]),trim($re[1]));
			return true;			
		}
		
		if(preg_match("#\/postfix-script\[.+?warning:\s+(.+?)\s+and\s+(.+?)\s+differ#",$buffer,$re )){
			$re[2]=trim($re[2]);
			$this->events("Symbolic link from  {$re[2]} -> {$re[1]} \"/bin/ln -s \"{$re[2]}\" \"{$re[1]}\"",__LINE__);
			@unlink(trim($re[1]));
			shell_exec("/bin/ln -s \"{$re[2]}\" \"{$re[1]}\"");
			return true;			
		}
		
		if(preg_match("#postfix-(.+?)\/smtpd\[.+?: warning: connect to Milter service unix:(.+?)\/greylist.sock:\s+Connection refused#",$buffer,$re)){
			$this->events("Instance {$re[1]} path={$re[2]}/greylist.sock -> Connection refused",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/restart-mgreylist-{$re[1]}";
			$TimeMin=$this->file_time_min($timefile);
			if($TimeMin>5){
				@chown("/var/spool/postfix/var/run/milter-greylist", "postfix");
				@chgrp("/var/spool/postfix/var/run/milter-greylist", "postfix");
				@chmod("/var/spool/postfix/var/run/milter-greylist",0755);				
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.milter-greylist.php --start --hostname={$re[1]} >/dev/null 2>&1 &";
				$this->events($cmd);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");	
				return true;				
			}
			
			$this->events("Instance {$re[1]}:: {$TimeMin}Mn need to wait 5mn, keep the error...");
			return true;
		}
		
		

		
		
		if(preg_match('#postfix(.*?)\/smtpd\[.+?:\s+fatal: non-null host address bits in "(.+?)", perhaps you should use "(.+?)" instead#',$buffer,$re)){
			$instance=$re[1];
			if($instance==null){$instance="master";}
			if(substr($instance, 0,1)=="-"){$instance=substr($instance, 1,strlen($instance));}
			$badentry=$re[2];
			$goodentry=$re[3];
			$md5=md5("$instance$badentry$goodentry");
			$timefile="/etc/artica-postfix/croned.1/non-null-host-address.$md5";
			if($this->file_time_min($timefile)>5){
				$this->email_events("[POSTFIX MISCONFIGURATION]: Wrong netwwork rule $badentry from postfix instance `$instance`",
				"Please add  $goodentry instead \"$rule\" in $badentry in the network configuration\nArtica will try to fix this issue\n$buffer",'postfix');
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --badnettr \"$instance\" \"$badentry\" \"$goodentry\" >/dev/null 2>&1";
				$this->events("[POSTFIX MISCONFIGURATION]: -> $cmd",__LINE__);
				shell_exec($cmd);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");					
				return true;
			}
			return true;
			
		}
		
		if(preg_match("#trivial-rewrite\[.+? fatal: open database\s+(.+?)\.db: No such file or directory#",$buffer,$re)){
			@file_put_contents($re[1], "\n");
			$cmd="{$GLOBALS["POSTMAP_PATH"]} hash:{$re[1]}";
			$this->events($cmd,__LINE__);
			shell_exec($cmd);
			return true;
		}
		
		
		if(strpos($buffer, "ARTICA_XNOTIFY")>5){
			if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+(.+?):\s+reject:\s+.+?\s+(.+?)\s+from.+?\[(.+?)\];\s+from=<(.+?)>\s+to=<(.+?)>.+?:\s+([0-9\.]+)\s+(.+?)ARTICA_XNOTIFY#",$buffer,$re)){
				$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$message_id=$re[3];
				$line=$re[4];$server=$re[5];$from=$re[6];$to=$re[7];$code=$re[8];$text=$re[9];
				$GLOBALS["maillog_tools"]->event_messageid_rejected($message_id,"headers rule",$server,$to);
				$text="$buffer\n\n---------\nMessage from $from and to $to was refused from the service $service and instance $mails\nmatching $line with error code $code ($text)";
				$this->events("ARTICA_XNOTIFY -> email event from=<$from> to=<$to> `$line`",__LINE__);
				$this->email_events("[SMTP headers rules]: refused from $from",$text,'postfix');
				return true;			
			}
		}
		
		if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+warning: regexp map\s+(.+?), line\s+[0-9]+:\s+Invalid preceding regular expression#",$buffer,$re)){
			$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$file=$re[3];$line=$re[4];
			$timefile="/etc/artica-postfix/croned.1/regexp.map.".md5("$file$line");
			if($this->file_time_min($timefile)>5){
				$f=explode("\n",@file_get_contents($file));$rule=$f[$line];
				$this->email_events("[SMTP headers rules]: Wrong rule line $line","Please check the rule \"$rule\" in $file",'postfix');
				$this->events("error $rule  in file $file",__LINE__);
				@unlink($timefile);@file_put_contents($timefile,"#");				
			}
			return true;			
		}
		
		if(preg_match("#postfix(.*?)\/(.+?)\[.+?:\s+(.+?):\s+reject:\s+.+?\s+(.+?)\s+from.+?\[(.+?)\];\s+from=<(.+?)>\s+to=<(.+?)>.+?:\s+([0-9\.]+)\s+(.+?)#",$buffer,$re)){$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$service=$re[2];$message_id=$re[3];$line=$re[4];$server=$re[5];$from=$re[6];$to=$re[7];$code=$re[8];$text=$re[9];$GLOBALS["maillog_tools"]->event_messageid_rejected($message_id,"headers rule",$server,$to);return true;}
		if(preg_match("#postfix(.*?)\/master\[([0-9]+)\]: terminating on signal#", $buffer,$re)){$mails=$re[1];if(substr($mails, 0,1)=="-"){$mails=substr($mails, 1,strlen($mails));}$this->email_events("Postfix PID {$re[2]} $mails was stopped","$buffer",'postfix');return true;}
		if(preg_match("#dkim-filter\[([0-9]+)\].+?Sendmail DKIM Filter v([0-9\.]+) starting#", $buffer,$re)){$this->email_events("DKIM Filter version {$re[2]} was successfully started PID {$re[1]}","$buffer",'postfix');return true;}
		if(preg_match("#smtpd\[.+?warning: connect to Milter service unix:/var/run/dkim-milter/dkim-milter.sock: Connection refused#",$buffer,$re)){shell_exec("{$GLOBALS["CHOWN"]} postfix:postfix /var/run/dkim-milter/dkim-milter.sock >/dev/null 2>&1");return true;}
		
		
		if(preg_match("#bounce\[.+?fatal: open /etc/postfix/bounce.template.cf: No such file or directory#i",$buffer,$re)){
			$this->events("tlsmgr: /etc/postfix/bounce.template.cf  No such file or directory!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/bounce.template.cf.error";
			if($this->file_time_min($timefile)>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --notifs-templates-force >/dev/null 2>&1 &";
				$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}
			return true;			
		}
		
		
		if(preg_match("#postfix\/tlsmgr\[.+?warning: request to update table btree:.+?smtpd_scache in non-postfix directory#i",$buffer,$re)){
			$this->events("tlsmgr: non-postfix directory!",__LINE__);
			$timefile="/etc/artica-postfix/croned.1/tlsmgr.non-postfix-dir";
			if($this->file_time_min($timefile)>5){
				$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sasl >/dev/null 2>&1 &";
				$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");				
			}
			return true;
		}
		
		
		if(preg_match("#([0-9\:]+)\s+.+?\s+postfix-(.+?)\/smtpd\[.+?fatal: open database\s+(.+?)\.db: No such file#i",$buffer,$re)){
			$fileDB=trim($re[1]).".db";
			$fileSource=trim($re[1]);
			$md5=md5($fileSource);
			if(!is_file($fileSource)){$this->events("Create null file $fileSource",__LINE__);@file_put_contents($fileSource, "\n");}
			$timefile="/etc/artica-postfix/croned.1/$md5.db.no.such.file";
			if($this->file_time_min($timefile)>5){
		 		$postmap=$GLOBALS["CLASS_UNIX"]->find_program('postmap');
		 		$cmd="{$GLOBALS["NOHUP_PATH"]} $postmap hash:$fileSource >/dev/null 2>&1 &";
		 		$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");
		 	}
		 	return true;
		}
		
		
		
		 if(preg_match("#postfix.+?\]: warning: database .+? is older than source file (.+)#",$buffer,$re)){
		 	$filesource=trim($re[1]);
		 	$md5=md5($filesource);
		 	if(!is_file($filesource)){$this->events("$filesource no such file (is older than source file)",__LINE__);return true;}
		 	$timefile="/etc/artica-postfix/croned.1/$md5.older.than.source";
		 	if($this->file_time_min($timefile)>5){
		 		$postmap=$GLOBALS["CLASS_UNIX"]->find_program('postmap');
		 		$cmd="{$GLOBALS["NOHUP_PATH"]} $postmap hash:$filesource >/dev/null 2>&1 &";
		 		$this->events("$cmd",__LINE__);
		 		@unlink($timefile);
				@file_put_contents($timefile,"#");
		 	}
		 	return true;
		 }
		
		if(preg_match("#[0-9]+\s+[0-9\:]+.+?\s+(.+?)\/postsuper\[.+?fatal: scan_dir_push: open directory.+?: Permission denied#",$buffer,$re)){
			$instance=$re[1];
			$timefile="/etc/artica-postfix/croned.1/postsuper.$instance.permission.denied";
			$this->events("Permission denied $instance",__LINE__);
			if($this->file_time_min($timefile)>5){
				$postfixbin=$GLOBALS["CLASS_UNIX"]->find_program('postfix');
					$cmd="{$GLOBALS["NOHUP_PATH"]} $postfixbin -c /etc/$instance set-permissions  >/dev/null 2>&1 &";
					$this->events("Permission denied $cmd",__LINE__);
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,"#");
				}
				return true;
			}
		
		if(preg_match("#postfix-(.+?)\/master\[.+? fatal: open lock file pid\/master.pid: unable to set exclusive lock: Resource temporarily unavailable#i",$buffer,$re)){
			$instance=$re[1];
			$timefile="/etc/artica-postfix/croned.1/master.$instance.master.pid.lock";
			$this->events("$instance unable to set exclusive lock on master.pid",__LINE__);
			if($this->file_time_min($timefile)>5){
				$postmulti=$GLOBALS["CLASS_UNIX"]->find_program('postmulti');
					$cmd="{$GLOBALS["NOHUP_PATH"]} $postmulti -i $instance -p start >/dev/null 2>&1 &";
					$this->events(" exclusive lock on master.pid $cmd",__LINE__);
					shell_exec($cmd);
					@unlink($timefile);
					@file_put_contents($timefile,"#");
				}
				return true;
			}	
		

		if(preg_match("#fatal: master_spawn: exec\s+(.+?)\/postscreen: No such file or directory#",$buffer,$re)){
			$postscreen=$GLOBALS["CLASS_UNIX"]->LOCATE_POSTFIX_POSTSCREEN();
			$timefile="/etc/artica-postfix/croned.1/postscreen.no.such.file";
			$this->events("Missing Postscreen binary..",__LINE__);
			if($this->file_time_min($timefile)>10){
				if(is_file($postscreen)){
					if(is_file($GLOBALS["LN_BIN"])){$required=$re[1]."/postscreen";$this->events("Do symbolic link from $postscreen to $required",__LINE__);shell_exec("{$GLOBALS["LN_BIN"]} -s $postscreen $required");}
				}else{
					$this->email_events("Could not find PostScreen binary",
				"Postfix claim \n$buffer\nArtica is unable to find postscreen binary","postfix");
				@file_put_contents($timefile,"#");
				}
			}
		return true;
		}	
		
		// postfix/tlsmgr[4476]: fatal: open database /var/lib/postfix/smtpd_tls_cache.db: Invalid argument
		if(preg_match("#tlsmgr\[.+?fatal: open database\s+(.+?):\s+Invalid argument#", $buffer)){
			$timefile="/etc/artica-postfix/croned.1/".md5($re[1]).".Invalid.argument";
			$this->events("Database {$re[1]} Failed",__LINE__);
			if($this->file_time_min($timefile)>5){
				$this->events("Database {$re[1]} Failed -> Remove it",__LINE__);
				@unlink($re[1]);
				@file_put_contents($timefile, time());
				shell_exec(trim("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &"));
			}
			return true;
		}

		if(preg_match("#warning: connect to Milter service unix:(.+?)\/spamass.sock: No such file or directory#",$buffer,$re)){
				$this->events("{$re[1]}/spamass.sock Error ",__LINE__);	
				$this->MilterSpamAssassinError($buffer,"$re[1]/spamass.sock");
				return true;
		}
		
		if(preg_match("#SpamAssassin: Unable to bind to port\s+(.+?)\/spamass\.sock: Permission denied#",$buffer,$re)){
				$this->events("{$re[1]}/spamass.sock Error ",__LINE__);	
				$this->MilterSpamAssassinError($buffer,"$re[1]/spamass.sock");
				return true;
		}
		
		
		if(preg_match("#postfix\/showq\[.+?fatal: open active .+?: Permission denied#",$buffer,$re)){
				$timefile="/etc/artica-postfix/croned.1/showq.Permission.denied";
				$this->events("showq: Permission error",__LINE__);	
				if($this->file_time_min($timefile)>5){
					$cmd=trim("{$GLOBALS["NOHUP_PATH"]} /bin/chown -R postfix:postfix /var/spool/mail &&  {$GLOBALS["postfix_bin_path"]} stop && {$GLOBALS["postfix_bin_path"]} start >/dev/null 2>&1 &");
					$this->events($cmd,__LINE__);
					@unlink($re[1]);
					shell_exec($cmd);
					@file_put_contents($timefile, time());	 
				}
			return true;
		}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	}
	


	private function ToSyslog($text){
		$LOG_SEV=LOG_INFO;
		if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	
	private function CheckDirs(){
		@mkdir("/home/artica/postfix/postfix/logger",0755,true);
		
	}
	


	private function routed($messageid,$recipient,$hostname,$ipaddr,$delay){
		
		$ARRAY["MESSAGE_ID"]=$messageid;
		$ARRAY["RECIPIENT"]=$recipient;
		$ARRAY["HOSTNAME"]=$hostname;
		$ARRAY["IPADDR"]=$ipaddr;
		$ARRAY["DELAY"]=$delay;
		$ARRAY["SENT"]=1;
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write($messageid,$ARRAY);
		return;

		
	}
	
	private function routed_info($messageid,$sender,$recipient,$hostname,$ipaddr,$cause){
		$ARRAY["MESSAGE_ID"]=$messageid;
		$ARRAY["RECIPIENT"]=$recipient;
		$ARRAY["HOSTNAME"]=$hostname;
		$ARRAY["IPADDR"]=$ipaddr;
		$ARRAY["FROM"]=$sender;
		$cause=str_replace("'", "`", $cause);
		$ARRAY["REJECTED"]=$cause;
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write($messageid,$ARRAY);
	}
	
	
	private function connection_failed($reject,$from=null,$to=null,$hostname=null,$ipaddr=null){
		if($hostname==null){$hostname=$ipaddr;}
		$GLOBALS["maillog_tools"]->event_message_reject_hostname($reject,$from=null,$to=null,$hostname,$ipaddr);
	}
	
	private function routed_from($messageid,$mailfrom,$size){
		
		$ARRAY["MESSAGE_ID"]=$messageid;
		$ARRAY["FROM"]=$mailfrom;
		$ARRAY["SIZE"]=$size;
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write($messageid,$ARRAY);
		return;
	

	}
	
	private function routed_cnx($messageid,$hostname,$ipaddr){
		
		$ARRAY["MESSAGE_ID"]=$messageid;
		$ARRAY["HOSTNAME"]=$hostname;
		$ARRAY["IPADDR"]=$ipaddr;
		$GLOBALS["maillog_tools"]->berkleydb_relatime_write($messageid,$ARRAY);
		return;

	
	}
	
	
	private function events($text,$line){
		$filename=basename(__FILE__);
		$logFile="/var/log/artica-postfix/postfix-logger.debug";
		
		$size=@filesize($logFile);
		if($size>9000000){@unlink($logFile);}
		
		
		if(!isset($GLOBALS["CLASS_UNIX"])){return;}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text (line $line)",$logFile);		
	}	
	
	
	private function MilterSpamAssassinError($buffer,$socket){
		$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
		if($this->file_time_min($file)<15){
			$this->events("Must wait 15mn",__LINE__);
		}	
	
		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(class_exists("usersMenus")){
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$AMAVIS_INSTALLED=$GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED;
		}
		$SpamAssMilterEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled");
		if(!is_numeric($SpamAssMilterEnabled)){$SpamAssMilterEnabled=0;}
		$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
		
		if($AMAVIS_INSTALLED){
			$EnableAmavisDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
			if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
		}

		
		
		$this->email_events("spamassin-milter socket error: $socket","Postfix claim \"$buffer\", Artica will reload Postfix and compile new Postfix settings",'postfix');
		if($SpamAssMilterEnabled==1){
			$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.policyd-weight.php --spamass-milter >/dev/null 2>&1 &";
			$this->events($cmd,__LINE__);
			shell_exec($cmd);			
		}
		$cmd="{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.initslapd.php --spamass-milter >/dev/null 2>&1 &";
		$this->events($cmd,__LINE__);
		shell_exec($cmd);		
		
		@unlink($file);
		@file_put_contents($file,"#");	
	}

	
	
	
	
   private function email_events($subject,$text,$context){
	 if(!isset($GLOBALS["CLASS_UNIX"])){return;}
	 $GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}

	private function file_time_min($path){
		if(!is_file($path)){if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}return 100000;}
		$last_modified = filemtime($path);
	 	$data1 = $last_modified;
	 	$data2 = time();
		$difference = ($data2 - $data1); 	 
		return round($difference/60);	 
	}	
	
	
}