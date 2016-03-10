<?php
include_once(dirname(__FILE__)."/class.phpmailer.inc");

class system_msmtp{
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
	private $logsArray=array();
	public $logs=null;
	public $ENABLED=0;
	
	function system_msmtp($recipient=null,$body){
		if($GLOBALS["VERBOSE"]){echo "system_msmtp functionF<br>\n";}
		if(is_file("/usr/bin/msmtp")){$this->binary="/usr/bin/msmtp";}
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$datas=trim($sock->GET_INFO("SmtpNotificationConfig"));
		if($datas==null){return;}
		$ini->loadString($datas);
		if(!isset($ini->_params["SMTP"])){return;}
		if(!isset($ini->_params["SMTP"]["enabled"])){return;}
		
		$this->ENABLED=intval($ini->_params["SMTP"]["enabled"]);
		
		
		$this->smtp_sender=$ini->_params["SMTP"]["smtp_sender"];
		if($recipient==null){
			$this->recipient=$ini->_params["SMTP"]["smtp_dest"];
		}else{
			$this->recipient=$recipient;
		}
		$this->tmpfile="/usr/share/artica-postfix/ressources/logs/web/msmtp.".time().".tmp";
		$this->tmplogfile="/usr/share/artica-postfix/ressources/logs/web/".time().".log";
		$this->conffile="/usr/share/artica-postfix/ressources/logs/web/".time().".conf";
		$this->tls_enabled=intval($ini->_params["SMTP"]["tls_enabled"]);
		$this->host=$ini->_params["SMTP"]["smtp_server_name"];
		$this->port=intval($ini->_params["SMTP"]["smtp_server_port"]);
		$this->smtp_auth_user=$ini->_params["SMTP"]["smtp_auth_user"];
		$this->smtp_auth_passwd=$ini->_params["SMTP"]["smtp_auth_passwd"];
		if($this->port==0){$this->port=25;}
		$this->body=$body;
		if($GLOBALS["VERBOSE"]){echo "system_msmtp functionF<br>\n";}
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
	
	
	public function buildTextMail($subject,$content,$recipient){
		
		$body=str_replace("\r", "", $content);
		$body=str_replace("\n", "\r\n","$content");
		
		$body[]="Return-Path: <$this->smtp_sender>";
		$body[]="Reply-To:  $this->smtp_sender";
		$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
		$body[]="From: $this->smtp_sender";
		$body[]="Content-Type: text/plain;charset=utf-8";
		$body[]="To: $recipient";
		$body[]="Subject: $subject";
		
		$body[]="";
		$body[]=$content;
		$body[]="";
		return @implode("\r\n", $body);
		
	}
	

	
	
	public function Send(){
		if($GLOBALS["VERBOSE"]){echo "SEND functionF<br>\n";}
		if(!is_file($this->binary)){
			if($GLOBALS["VERBOSE"]){echo "Unable to find msmtp binary<br>\n";}
			$this->logs="Unable to find msmtp binary";
			return false;
		}
		if($GLOBALS["VERBOSE"]){echo "Build CONF<br>\n";}
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
		
		if($GLOBALS["VERBOSE"]){system("$cmd");}
		
		
		exec($cmd,$this->logsArray);
		$this->logsArray[]=@file_get_contents($this->tmplogfile);
		@unlink($this->conffile);
		@unlink($this->tmplogfile);
		@unlink($this->tmpfile);
		$this->logs=@implode("\n", $this->logsArray);
		if(strpos($this->logs, "exitcode=EX_OK")>0){return true;}
		if($GLOBALS["VERBOSE"]){echo "$this->logs -> FALSE<br>\n";}
		return false;
		
	}
	
	
	
	
	
	
	
}