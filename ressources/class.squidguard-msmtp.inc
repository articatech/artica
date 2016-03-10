<?php

class squidguard_msmtp{
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
	public $MaxError=0;
	
	function squidguard_msmtp($smtp_sender,$body){
		if(is_file("/usr/bin/msmtp")){$this->binary="/usr/bin/msmtp";}
		$sock=new sockets();
		$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
		
		$this->smtp_sender=$smtp_sender;
		$this->recipient=$SquidGuardWebSMTP["smtp_recipient"];
		$this->tmpfile="/usr/share/artica-postfix/ressources/logs/web/msmtp.".time().".tmp";
		$this->tmplogfile="/usr/share/artica-postfix/ressources/logs/web/.".time().".log";
		$this->conffile="/usr/share/artica-postfix/ressources/logs/web/.".time().".conf";
		$this->tls_enabled=$SquidGuardWebSMTP["tls_enabled"];
		$this->host=$SquidGuardWebSMTP["smtp_server_name"];
		$this->port=$SquidGuardWebSMTP["smtp_server_port"];
		$this->smtp_auth_user=$SquidGuardWebSMTP["smtp_auth_user"];
		$this->smtp_auth_passwd=$SquidGuardWebSMTP["smtp_auth_passwd"];
		if(isset($SquidGuardWebSMTP["MaxError"])){
			$this->MaxError=intval($SquidGuardWebSMTP["MaxError"]);
		}
		if($this->MaxError==0){$this->MaxError=1;}
		$this->body=$body;
	}
	
	
	
	private function buildconf(){
		$conf[]="account default";
		$conf[]="host $this->host";
		$conf[]="from $this->smtp_sender";
		$conf[]="port $this->port";
		
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("host: $this->host",__FUNCTION__,__LINE__);}
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("from: $this->smtp_sender",__FUNCTION__,__LINE__);}
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("Port: $this->port",__FUNCTION__,__LINE__);}
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("User: $this->smtp_auth_user",__FUNCTION__,__LINE__);}
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("TLS?: $this->tls_enabled",__FUNCTION__,__LINE__);}
		
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
		$msmtp[]="--read-envelope-from";
		$msmtp[]="--logfile=$this->tmplogfile";
		$msmtp[]="-- $this->recipient";
		$msmtp[]="< $this->tmpfile 2>&1";
		
		$cmd=@implode(" ", $msmtp);
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("$cmd",__FUNCTION__,__LINE__);}
		
		exec("$cmd 2>&1",$results);
		$this->logs=@implode("\n", $results)."\n".@file_get_contents($this->tmplogfile);
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("Results 2:$this->logs",__FUNCTION__,__LINE__);}
		
		@unlink($this->conffile);
		@unlink($this->tmplogfile);
		@unlink($this->tmpfile);
		
		if(strpos($this->logs, "exitcode=EX_OK")>0){return true;}
		if(function_exists("ufdb_smtp_logs")){ufdb_smtp_logs("exitcode=EX_OK Not found ",__FUNCTION__,__LINE__);}
		return false;
		
	}
	
	
	
	
	
	
	
}