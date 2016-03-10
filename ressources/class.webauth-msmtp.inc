<?php

class webauth_msmtp{
	private $binary=null;
	private $smtp_sender=null;
	private $recipient=null;
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
	
	function webauth_msmtp($smtp_sender,$body,$email=null,$ruleid=0){
		if(is_file("/usr/bin/msmtp")){$this->binary="/usr/bin/msmtp";}
		$sock=new wifidog_settings($ruleid);
		
		
		$this->smtp_sender=$smtp_sender;
		$this->tmpfile="/usr/share/artica-postfix/ressources/logs/web/msmtp.".time().".tmp";
		$this->tmplogfile="/usr/share/artica-postfix/ressources/logs/web/.".time().".log";
		$this->conffile="/usr/share/artica-postfix/ressources/logs/web/.".time().".conf";
		$this->tls_enabled=intval($sock->GET_INFO("tls_enabled"));
		$this->host=$sock->GET_INFO("smtp_server_name");
		$this->port=$sock->GET_INFO("smtp_server_port");
		$this->smtp_auth_user=$sock->GET_INFO("smtp_auth_user");
		$this->smtp_auth_passwd=$sock->GET_INFO("smtp_auth_passwd");
		if($email<>null){$this->recipient=$email;}
		$this->body=$body;
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
	
	
	public function Send(){
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
		shell_exec($cmd);
		$this->logs=@file_get_contents($this->tmplogfile);
		@unlink($this->conffile);
		@unlink($this->tmplogfile);
		@unlink($this->tmpfile);
		
		if(strpos($this->logs, "exitcode=EX_OK")>0){return true;}
		return false;
		
	}
	
	
	
	
	
	
	
}