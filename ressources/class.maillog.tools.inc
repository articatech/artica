<?php
class maillog_tools{
	var $buffer=null;
	
	
	function maillog_tools(){
		if(!isset($GLOBALS["ActAsSMTPGatewayStatistics"])){$sock=new sockets();$GLOBALS["ActAsSMTPGatewayStatistics"]=$sock->GET_INFO("ActAsSMTPGatewayStatistics");if(!is_numeric($GLOBALS["ActAsSMTPGatewayStatistics"])){$GLOBALS["ActAsSMTPGatewayStatistics"]=0;}}
	}
	
	public function Postfix_Addconnection_error($hostname,$ip,$error_text){
		if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
		$time=time();
		if($hostname==null){if($ip<>null){$hostname=gethostbyaddr($ip);}}
		if(preg_match("#Relay access denied#i", $error_text)){$this->SMTP_HACKS($hostname, $ip);}
		
		
		
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){
			$ip=gethostbyname($ip);
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){if($hostname<>null){$ip=gethostbyname($hostname);}}
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){return null;}
		}
		
		
		$f = @fopen("/home/artica/postfix/realtime-events/cnx_errors.log", 'a');
		$text="$time:::$hostname:::$ip::$error_text";
		@fwrite($f, "$text\n");
		@fclose($f);
		
	}	
	
	
	private function SMTP_HACKS($hostname,$ip){
		if(!isset($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"])){return;}
		if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]==0){return;}
		if(!function_exists("smtp_hack_perform")){return;}
		
		$GLOBALS["SMTP_HACK"][$hostname]["RBL"]=$GLOBALS["SMTP_HACK"][$ip]["RBL"]+2;
		if(!function_exists("events")){events("Postfix Hack: {$hostname} RBL !! {$ip}={$GLOBALS["SMTP_HACK"][$ip]["RBL"]} attempts");}
			
			
		if($GLOBALS["SMTP_HACK"][$ip]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($ip,$GLOBALS["SMTP_HACK"][$ip],"RBL");
			unset($GLOBALS["SMTP_HACK"][$ip]);
		}
	}

		
		
	
	
	
	
public function amavis_spam($postfix_id,$smtp_sender,$from,$to,$size,$action){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$file="/var/log/artica-postfix/RTM/$postfix_id.msg";
	$ini=new Bs_IniHandler($file);
	$ini->set("TIME","message-id","$postfix_id");
	$ini->set("TIME","time_end",date("Y-m-d H:i:s"));
	$ini->set("TIME","bounce_error",$action);
	$ini->set("TIME","size",$size);
	if($to<>null){$ini->set("TIME","mailto",$to);}
	if($from<>null){$ini->set("TIME","mailfrom","$from");}
	if($smtp_sender<>null){$ini->set("TIME","server_from","$smtp_sender");}
	if($action<>"Sended"){
		$ini->set("TIME","delivery_success","no");
		$this->Postfix_Addconnection_error(null,$smtp_sender,"SPAM");
	}else{
		$ini->set("TIME","delivery_success","yes");
		
	}
	$ini->saveFile($file);
}

private function ToSyslog($text){
	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

private function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(is_file($db_path)){return true;}
	$this->ToSyslog("berekley_db_create:: Failed Creating $db_path database");
}

private function berkleydb_write($dbpath,$key,$MAIN_ARRAY){
	$MAIN_ARRAY["TIME"]=time();
	if(!$this->berekley_db_create($dbpath)){return;}
	$db_con = @dba_open($dbpath, "w","db4");
	if(!$db_con){
		$this->ToSyslog("berkleydb_write:: $dbpath failed connect");
		@dba_close($db_con);
		continue;

	}

	$seralized=serialize($MAIN_ARRAY);
	@dba_replace($key,$seralized,$db_con);
	@dba_close($db_con);


}



public function berkleydb_relatime_write($key,$MAIN_ARRAY){
	return;
	$time=0;
	$to=null;
	$from=null;
	$server=null;
	$ipaddr=null;
	$status=null;
	$size=0;
	$MESSAGE_ID=null;
	if(!isset($MAIN_ARRAY["SIZE"])){$MAIN_ARRAY["SIZE"]=0;}
	if(isset($MAIN_ARRAY["RECIPIENT"])){$to=$MAIN_ARRAY["RECIPIENT"];}
	if(isset($MAIN_ARRAY["SENDER"])){$from=$MAIN_ARRAY["SENDER"];}
	if(isset($MAIN_ARRAY["HOSTNAME"])){$server=$MAIN_ARRAY["HOSTNAME"];}
	if(isset($MAIN_ARRAY["IPADDR"])){$ipaddr=$MAIN_ARRAY["IPADDR"];}
	$reject=$MAIN_ARRAY["REJECTED"];
	$size=intval($MAIN_ARRAY["SIZE"]);
	$TIME_END=intval($MAIN_ARRAY["TIME_END"]);
	if(isset($MAIN_ARRAY["STATUS"])){$status=$MAIN_ARRAY["STATUS"];}
	if(isset($MAIN_ARRAY["MESSAGE_ID"])){$MESSAGE_ID=$MAIN_ARRAY["MESSAGE_ID"];}
	if(isset($MAIN_ARRAY["DATE_FROM"])){$time=$MAIN_ARRAY["DATE_FROM"];}
	
	if($time==0){$time=time();}
	if($MESSAGE_ID==null){if($key<>null){$MESSAGE_ID=$key;}}
	$f = @fopen("/home/artica/postfix/realtime-events/realtime.log", 'a');
	$text="$time:::$MESSAGE_ID:::$size:::$to:::$from:::$server:::$ipaddr:::$reject:::$status:::$TIME_END";
	@fwrite($f, "$text\n");
	@fclose($f);
	

}
private function berkleydb_realtime_reject_write($key,$MAIN_ARRAY){
	
	if(!class_exists("class.postgres.inc")){include_once(dirname(__FILE__)."/class.postgres.inc");}
	$time=time();
	if(!isset($MAIN_ARRAY["IPADDR"])){$MAIN_ARRAY["IPADDR"]=null;}
	$mailto=$MAIN_ARRAY["RECIPIENT"];
	$mailfrom=$MAIN_ARRAY["SENDER"];
	$hostname=$MAIN_ARRAY["HOSTNAME"];
	$ipaddr=$MAIN_ARRAY["IPADDR"];
	$reason=$MAIN_ARRAY["REJECTED"];
	
	if($ipaddr==null){$ipaddr=gethostbyname($hostname);}
	
	$postgres=new postgres_sql();
	$date=date("Y-m-d H:i:s");
	$VALUES="('$date','$hostname','$mailfrom','$mailto','$ipaddr','$reason')";
	$postgres->QUERY_SQL("INSERT INTO smtprefused (zdate,hostname,mailfrom,mailto,ipaddr,reason) VALUES $VALUES");
	return true;
	
}


public function event_message_from($postfix_id,$from,$size){
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	$ARRAY["SENDER"]=$from;
	$ARRAY["SIZE"]=$size;
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}

public function event_newmail($postfix_id,$messageid=null){
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	$ARRAY["DATE_FROM"]=time();
	if($messageid<>null){$ARRAY["EMAIL_ID"]=$messageid;}
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}

function event_message_milter_reject($postfix_id,$reject,$from,$to=null,$buffer=null,$sender=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$ARRAY["IPADDR"]=null;
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	$smtp_sender=null;
	$hostname=null;
	
	
	if($sender==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){$smtp_sender=$re[1];}
		if(preg_match("#assp\[.+?\]:\s+.+?\s+(.+?)\s+<#",$buffer,$re)){$smtp_sender=$re[1];}
		
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$smtp_sender)){
			$hostname=gethostbyaddr($smtp_sender);
			$this->Postfix_Addconnection_error($hostname,$smtp_sender,$reject);
		}
	}
	
	if($smtp_sender<>null){
		$ARRAY["IPADDR"]=$smtp_sender;
	}
	
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($hostname<>null){$ARRAY["HOSTNAME"]=$hostname;}
	$ARRAY["REJECTED"]=$reject;
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);

}

function Postfix_Addconnection($hostname=null,$ip=null){
	$time=time();
	$fam=new familysite();
	if($hostname==null){$hostname=$fam->GetComputerName($ip);}
	$curdate=date("YmdH");
	$zDate=date("Y-m-d H:i:s");
	$domain=$fam->GetFamilySites($hostname);
	$zmd5=md5("$time$hostname$ip");
	
	$f = @fopen("/home/artica/postfix/realtime-events/cnx.log", 'a');
	$text="$time:::$hostname:::$domain:::$ip";
	@fwrite($f, "$text\n");
	@fclose($f);
	return;
}


public function event_message_reject_hostname($reject,$from=null,$to=null,$server,$ipaddr=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$to=str_replace("|","",$to);
	$to=trim($to);
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$server)){$ipaddr=$server;}
		
	$this->Postfix_Addconnection_error($server,$ipaddr,$reject);
	
	
	
	$ARRAY["MESSAGE_ID"]=time();
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	$ARRAY["HOSTNAME"]=$server;
	if($ipaddr<>null){$ARRAY["IPADDR"]=$ipaddr;}
	
	$ARRAY["REJECTED"]=$reject;
	$this->berkleydb_realtime_reject_write(time(),$ARRAY);

}

public function event_message_rejected($id,$reject,$from=null,$to=null,$buffer,$sender=null,$ipaddr=null){
	$this->event_message_reject_hostname($reject, $from,$to, $sender,$ipaddr);
	
}

function event_DISCARD($postfix_id,$from,$to,$buffer=null,$ipaddr=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	if($ipaddr==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){
			$ARRAY["HOSTNAME"]=$re[1];
			$ARRAY["IPADDR"]=$re[1];
		}
			
	}else{
		$ARRAY["IPADDR"]=$re[1];
	}

	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ipaddr)){
		$hostname=gethostbyaddr($ipaddr);
		$ARRAY["HOSTNAME"]=$hostname;
		$this->Postfix_Addconnection_error($hostname,$ipaddr,"Discard");
	}

	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	$ARRAY["REJECTED"]="Discard";
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}

public function event_messageid_rejected($msg_id_postfix,$error,$server=null,$to=null,$from=null,$ipaddr=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$ARRAY["MESSAGE_ID"]=$msg_id_postfix;
	if($to<>null){
		$ARRAY["RECIPIENT"]=$to;
	}
	$error=str_replace("'", "`", $error);
	$ARRAY["HOSTNAME"]=$server;
	$ARRAY["REJECTED"]=$error;
	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($ipaddr<>null){$ARRAY["IPADDR"]=$ipaddr;}
	$this->berkleydb_relatime_write($msg_id_postfix,$ARRAY);
	
}
public function event_messageid_removed($msg_id_postfix){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}

	$ARRAY["REJECTED"]="removed";
	$ARRAY["TIME_END"]=time();
	$this->berkleydb_relatime_write($msg_id_postfix,$ARRAY);

}

function event_Content_scanner_malfunction($postfix_id,$from=null,$to=null){
	
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	$ARRAY["REJECTED"]="Content scanner malfunction";
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}



public function AmavisConfigErrorInPostfix($buffer){
	if(!isset($GLOBALS["ActAsSMTPGatewayStatistics"])){$sock=new sockets();$GLOBALS["ActAsSMTPGatewayStatistics"]=$sock->GET_INFO("ActAsSMTPGatewayStatistics");if(!is_numeric($GLOBALS["ActAsSMTPGatewayStatistics"])){$GLOBALS["ActAsSMTPGatewayStatistics"]=0;}}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	$timeFile=file_time_min($file);
	if($timeFile<15){
		events("*** $buffer ****");
		events("amavisd-new socket no operations, blocked by timefile $timeFile Mn!!!");
		return null;}
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){	
			events("amavisd-new socket error time:$timeFile Mn!!!");
			email_events("amavisd-new socket error","Postfix claim \"$buffer\", Artica will reload Postfix and compile new Postfix settings",'postfix');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} ".dirname(__FILE__)."/exec.postfix.maincf.php --reconfigure");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/amavis restart');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --postfix-reload');
		}
	@unlink($file);
	@file_put_contents($file,"#");	
	if(!is_file($file)){
		events("error writing time file:$file");
	}
}
private function shell_exec_maillog($cmd){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){
		events("`$cmd` will not be executed ActAsSMTPGatewayStatistics is enabled" );
		return;
	}
	shell_exec($cmd);
	events("EXEC:`$cmd`" );
}


function event_finish($postfix_id,$to,$status,$bounce_error,$from=null,$buffer=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$smtp_sender=null;
	$delivery_success='yes';
	if($status=='bounced'){$delivery_success='no';}
	if($status=='deferred'){$delivery_success='no';}
	if($status=='reject'){$delivery_success='no';}
	if($status=='expired'){$delivery_success='no';}

	if(preg_match("#Queued mail for delivery#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#550 No Such User Here#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#No such user#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#550 5\.1\.0 error: R4\.1#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#Sender address rejected: need fully-qualified address#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="need fully-qualified address";
	}

	if(preg_match("#550.*?The email account that you tried to reach does not exist#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#no mailbox here#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#refused to talk to me.+?RBL rejection#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="RBL";
	}

	if(preg_match("#550.+?Service unavailable.+?blocked using.+?RBL#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="RBL";
	}

	if(preg_match("#554 : Recipient address rejected: Access denied#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Access denied";
	}


	if(preg_match("#451 4.2.0 Mailbox has an invalid format#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox corrupt";
	}

	if(preg_match("#delivered via#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}
	
	if(preg_match("#message size [0-9]+ exceeds size limit [0-9]+#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Size Limit";
	}


	if(preg_match("#Content scanner malfunction#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Content scanner malfunction";
	}

	if(preg_match("#4\.5\.0 Failure#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Error";
	}


	if(preg_match("#250 2\.0\.0 Ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#Host or domain name not found#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Host or domain name not found";
	}


	if(preg_match("#4\.5\.0 Error in processing#",$bounce_error)){$status="Error";$delivery_success="no"; $bounce_error="Error"; };
	if(preg_match("#Sender address rejected.+?Domain not found#",$bounce_error)){$status="Error";$delivery_success="no"; $bounce_error="Domain not found";};

	if(preg_match("#delivered to command: procmail -a#",$bounce_error)){$status="Deliver";$delivery_success="yes";$bounce_error="Sent to procmail";};

	if(preg_match("#550 must be authenticated#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Authentication error";
	};

	if(preg_match("#250 Message.+?accepted by#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	};

	if(preg_match("#250 Message.*?received#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}


	if(preg_match("#Connection timed out#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="timed out";
	};

	if(preg_match("#connect\s+to.+?Connection refused#",$bounce_error)){
		if(preg_match("#connect to 127\.0\.0\.1\[127\.0\.0\.1\]:2003:#", $bounce_error)){
			$file="/etc/artica-postfix/croned.1/postfix.lmtp.127.0.0.1:2003.refused";
			if(file_time_min($file)>5){
				if($GLOBALS["ZARAFA_INSTALLED"]){
					email_events("Postfix: Zarafa LMTP Error","Postfix\n$buffer\nArtica will trying to start Zarafa","postfix");
					$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix start zarafa >/dev/null 2>&1 &";
					shell_exec_maillog(trim($cmd));
					@unlink($file);
					file_put_contents($file,"#");
					return;
				}
			}
		}
		$status="Error";
		$delivery_success="no";
		$bounce_error="Connection refused";
	}

	if(preg_match("#127\.0\.0\.1.*?said:\s+450\s+[0-9\.]+\s+(.+?)\s+Mailbox Temporarily Unavailable#", $bounce_error,$re)){
		$file="/etc/artica-postfix/croned.1/postfix.lmtp.127.0.0.1:{$re[1]}.Temporarily.Unavailable";
		if(file_time_min($file)>5){
			email_events("Postfix:MailBox issue on {$re[1]} Temporarily Unavailable","Postfix\n$buffer\nChecks is {$re[1]} is a realy account..","postfix");
			//$cmd="{$GLOBALS["NOHUP_PATH"]} /etc/init.d/artica-postfix start zarafa >/dev/null 2>&1 &";
			//shell_exec_maillog(trim($cmd));
			@unlink($file);
			file_put_contents($file,"#");
			return;
		}
		$status="Error";
		$delivery_success="no";
		$bounce_error="Mailbox Unavailable";

	}

	if(preg_match("#temporary failure.+?artica-msmtp:\s+recipient address\s+(.+?)\s+not accepted by the server artica-msmtp#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="artica-filter error";
	}

	if(preg_match("#host.+?said: 550 No such user#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="No such user";
	}

	if(preg_match("#250 2\.1\.5 Ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if($bounce_error=="250 OK: data received"){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if($bounce_error=="250 Ok: queued as"){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}
	if(preg_match("#250\s+ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#504.+?Recipient address rejected#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="recipient address rejected";
	}

	if(preg_match("#Address rejected#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Address rejected";
	}

	if(preg_match("#conversation with .+?timed out#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="timed out";
	}

	if(preg_match("#connect to\s+(.+?)\[.+?cyrus.+?lmtp\]: Connection refused#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="mailbox service error";
		cyrus_generic_error($bounce_error,"Cyrus socket error");
	}

	if(preg_match("#host.+?\[(.+?)\]\s+said:.+?<(.+?)>: Recipient address rejected: User unknown in local recipient table#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="User unknown";
		$to=$re[2];
	}
		
	if(preg_match("#host.+?said:\s+554.+?<(.+?)>:\s+Recipient address rejected.+?not existing recipient#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="not existing recipient";
		$to=$re[2];
	}
		
		
	if(preg_match("#said:.+?Authentication required#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Authentication required";
	}

	if(preg_match("#temporary failure.+?[0-9]+\s+[0-9\.]+\s+Bad sender address syntax.+?could not send mail#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Bad sender address syntax";
	}

	if(preg_match("#connect.+?Permission denied#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="service permissions error";
	}

	if(preg_match("#Command died with status 255:.+?exec\.artica-filter\.php#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="artica-filter error";
	}
	
	if(preg_match("#Host or domain name not found#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="non-existent-smtp";
	}


	if(preg_match("#host\s+(.+?)\[(.+?)\]\s+said:\s+[0-9]+.+?Recipient address rejected: Access denied#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Access denied";
		$smtp_sender=$re[2];
	}

	if(preg_match("#250.+?Ok#i",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#Message accepted#i",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#host.+?said:.+?Domain of sender address.+?does not exist#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Your domain does not exist";
	}

	if(preg_match("#connect to .+?No such file or dire#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="socket error";
	}

	if(preg_match("#lost connection with.+?\[(.+?)\] while receiving#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="lost connection";$smtp_sender=$re[1];}
	if(preg_match("#host.+?\[(.+?)\] said:.+?Recipient address rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Recipient address rejected";$smtp_sender=$re[1];}

	if(preg_match("#loops back to myself#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
			
		$file="/etc/artica-postfix/croned.1/postfix.loops.back.to.myself";
		if(file_time_min($file)>10){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time);
		}
			
		$bounce_error="loops back to myself";
	}

	if(preg_match("#Sender denied#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Sender denied";}
	if(preg_match("#User unknown#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="User unknown";}
	if(preg_match("#Bounce attack signature verification failed#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Bounce attack";}
	if(preg_match("#mailbox unavailable#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="No mailbox";}
	if(preg_match("#Message rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Message rejected";}
	if(preg_match("#250 2\.0\.0 from MTA#",$bounce_error,$re)){$status="Deliver";$delivery_success="yes";$bounce_error="Sended";}
	if(preg_match("#421-ts03#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Your are blacklisted";}
	if(preg_match("#User does not exist#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="No mailbox";}
	if(preg_match("#Recipient address rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="authentication required"; }
	if(preg_match("#unsolicited mail originating from your IP address#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Your are blacklisted"; }


	if($bounce_error=="250 OK"){$bounce_error="Sended";$delivery_success="yes";$status="Deliver";}
	if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){$bounce_error="lost connection";$delivery_success="no";$smtp_sender=$re[1];}
	if(preg_match("#status=bounced \(message size.+?exceeds size limit.+?of server.+?\[(.+?)\]#",$bounce_error,$re)){$bounce_error="size exceed limit";$delivery_success="no";$smtp_sender=$re[1];}
	if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){$bounce_error="lost connection";$delivery_success="no";$smtp_sender=$re[1];}

	if($delivery_success=="no"){
		if($bounce_error=="User unknown in relay recipient table"){$bounce_error="User unknown";}
			
		events("event_finish() line ".__LINE__. " bounce_error=$bounce_error");
		if(preg_match("#connect to.+?\[(.+?)lmtp\].+?No such file or directory#",$bounce_error,$ra)){
			events("Cyrus error found -> CyrusSocketErrot");
			cyrus_socket_error($bounce_error,$ra[1].'/lmtp');
		}
		if(preg_match("#550\s+User\s+unknown\s+<(.+?)>.+?in reply to RCPT TO command#",$bounce_error,$ra)){mailbox_unknown($bounce_error,$ra[1]);}
	}

	
	if($delivery_success=="yes"){
			$ARRAY["SENT"]=1;
	}
	
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	
	if($smtp_sender==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){
			$ARRAY["HOSTNAME"]=$re[1];
		}
	}else{
		$ARRAY["HOSTNAME"]=$smtp_sender;
	}

	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	$bounce_error=str_replace("'", "`", $bounce_error);
	$ARRAY["REJECTED"]=$bounce_error;
	$ARRAY["TIME_END"]=time();
	$ARRAY["STATUS"]=$status;
	$this->berkleydb_relatime_write($postfix_id, $ARRAY);


	

	
	 

}
	
	
}