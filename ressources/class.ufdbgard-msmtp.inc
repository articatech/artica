<?php

class ufdb_msmtp{
	private $binary=null;
	public $smtp_sender=null;
	public $recipient=null;
	private $tmpfile=null;
	private $tmplogfile=null;
	private $conffile=null;
	private $tls_enabled=0;
	private $host=null;
	private $port=25;
	private $smtp_auth_user=null;
	private $smtp_auth_passwd=null;
	private $body=null;
	public $logs=null;
	
	function ufdb_msmtp(){
		if(is_file("/usr/bin/msmtp")){$this->binary="/usr/bin/msmtp";}
		$sock=new sockets();
		$UfdbguardSMTPNotifs=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
		
		if(!isset($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}
		if(!is_numeric($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}
		if(!isset($UfdbguardSMTPNotifs["smtp_server_name"])){$UfdbguardSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
		if(!isset($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
		if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_sender"];}
		if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
		if(!isset($UfdbguardSMTPNotifs["smtp_auth_user"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
		if(!isset($UfdbguardSMTPNotifs["smtp_auth_passwd"])){$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
		if(!isset($UfdbguardSMTPNotifs["tls_enabled"])){$UfdbguardSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
		if(!isset($UfdbguardSMTPNotifs["ssl_enabled"])){$UfdbguardSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
		
		if(!is_numeric($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=25;}
		
		$this->smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
		$this->tmpfile="/usr/share/artica-postfix/ressources/logs/web/msmtp.".time().".tmp";
		$this->tmplogfile="/usr/share/artica-postfix/ressources/logs/web/.".time().".log";
		$this->conffile="/usr/share/artica-postfix/ressources/logs/web/.".time().".conf";
		$this->tls_enabled=intval($UfdbguardSMTPNotifs["tls_enabled"]);
		$this->host=$UfdbguardSMTPNotifs["smtp_server_name"];
		$this->port=$UfdbguardSMTPNotifs["smtp_server_port"];
		$this->smtp_auth_user=$UfdbguardSMTPNotifs["smtp_auth_user"];
		$this->smtp_auth_passwd=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
		$this->recipient=$UfdbguardSMTPNotifs["smtp_dest"];
		
	}
	
	
	private function buildconf(){
		$conf[]="account default";
		$conf[]="host $this->host";
		$conf[]="from $this->smtp_sender";
		
		$conf[]="port $this->port";
		if($this->smtp_auth_user<>null){
			if($this->tls_enabled==0){$conf[]="auth plain";}else{$conf[]="auth on";}
			$conf[]="user $this->smtp_auth_user";
			$conf[]="password $this->smtp_auth_passwd";
			$conf[]="logfile $this->tmplogfile";
		}
		if($this->tls_enabled==1){
			$conf[]="tls on";
			$conf[]="tls_certcheck off";
		}
		
		@file_put_contents($this->conffile, @implode("\n", $conf)."\n");
		@chmod($this->conffile,0600);
		
	}
	
	
	public function Send($body){
		$this->body=$body;
		if(!is_file($this->binary)){
			$this->logs="Unable to find msmtp binary";
			return false;
		}
		$this->buildconf();
		@file_put_contents($this->tmpfile, $this->body);
		
		$msmtp[]=$this->binary;
		$msmtp[]="--file=$this->conffile";
		$msmtp[]="--protocol=smtp";
		$msmtp[]="--logfile=$this->tmplogfile";
		$msmtp[]="--read-envelope-from";
		$msmtp[]="-- $this->recipient";
		$msmtp[]="< $this->tmpfile 2>&1";
		
		$cmd=@implode(" ", $msmtp);
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$this->logs=@file_get_contents($this->tmplogfile);
		@unlink($this->conffile);
		@unlink($this->tmplogfile);
		@unlink($this->tmpfile);
		if($GLOBALS["VERBOSE"]){echo $this->logs."\n";}
		if(strpos($this->logs, "exitcode=EX_OK")>0){return true;}
		return false;
		
	}
	
	
	
	
	
	
	
}