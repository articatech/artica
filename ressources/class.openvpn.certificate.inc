<?php

class openvpn_certificate{
	private $CommonName=null;
	private $ssl_path="/etc/ssl/certs/openvpn";
	private $UsePrivKeyCrt=0;
	private $crt_content=null;
	private $csr_content=null;
	private $srca_content=null;
	private $privkey_content=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
	private $ssl_client_certificate=0;
	private $RootCa=null;
	private $PrefixOutput;
	private $bundle;
	private $AS_ROOT=false;
	private $password=null;
	private $LOGS=array();
	
	public $ca_path;
	public $dh_path;
	public $key_path;
	public $cert_path;
	public $password_path;
	public $config_path;
	
	function openvpn_certificate($CommonName=null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($CommonName<>null){$this->CommonName=$CommonName;}
		$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: OpenVPN \"CERT\"";
		if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
	}
	
	
	public function build(){
		$askpass=null;
		$sock=new sockets();
		$ligne=unserialize($sock->GET_INFO("OpenVPNCertificateSettings"));
		$ldap=new clladp();
		if(trim($ligne["password"])==null){$ligne["password"]=$ldap->ldap_password;}
		
		
		@file_put_contents("/etc/artica-postfix/openvpn/keys/password",$ligne["password"]);
		$askpass=" --askpass /etc/artica-postfix/openvpn/keys/password";
		
	   $ca="/etc/artica-postfix/openvpn/keys/openvpn-ca.crt";
	   $dh='/etc/artica-postfix/openvpn/keys/dh1024.pem';
	   $key="/etc/artica-postfix/openvpn/keys/vpn-server.key";
	   $crt="/etc/artica-postfix/openvpn/keys/vpn-server.crt";

		
		
		return "--ca $ca --dh $dh --key $key --cert $crt$askpass";
	}
	
	
	private function BuildOfficial(){
			$certificate_subdir=str_replace("*", "_ALL_", $this->CommonName);
			$Directory="$this->ssl_path/$certificate_subdir";
			@mkdir($Directory,0755,true);
			$askpass=null;
			
			$CA_CONTENT=$this->RootCa;
			$PRIVATE_KEY=$this->srca_content;
			$CERTIFICATE=$this->crt_content;
			$CHAIN=$this->bundle;
		
			@file_put_contents("$Directory/server.crt", "$CERTIFICATE");
			@file_put_contents("$Directory/server.key", "$PRIVATE_KEY");
			@file_put_contents("$Directory/ca.key", "$CA_CONTENT");
			
			$this->ca_path="$Directory/ca.key";
			$this->dh_path="$Directory/dhparam.pem";
			$this->key_path="$Directory/server.key";
			$this->cert_path="$Directory/server.crt";
			$this->password_path="$Directory/password";
			
			if($this->password<>null){
				@file_put_contents("$Directory/password", "$this->password");
				$askpass=" --askpass $Directory/password";
			}
			
			if(!is_file("$Directory/dhparam.pem")){
				$unix=new unix();
				$openssl=$unix->find_program("openssl");
				system("$openssl dhparam -outform PEM -out $Directory/dhparam.pem 2048");
			}
			if(!is_file("/etc/openssl/private-key/privkey.key")){
				$unix=new unix();
				$openssl=$unix->find_program("openssl");
				@mkdir("/etc/openssl/private-key",0755,true);
				shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
			}
					
			return "--ca $Directory/ca.key --dh $Directory/dhparam.pem".
			" --key $Directory/server.key --cert $Directory/server.crt$askpass";
	}
	
	
	private function load_certificate(){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
		$sql="SELECT `password`,`UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle`
		FROM sslcertificates WHERE CommonName='$this->CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			$this->LOGS[]="#".str_replace("\n", " ", $q->mysql_error);
		}
		$this->LOGS[]="# $this->CommonName UsePrivKeyCrt={$ligne["UsePrivKeyCrt"]}";
		
		$this->UsePrivKeyCrt=intval($ligne["UsePrivKeyCrt"]);
		$this->crt_content=$ligne["crt"];
		$this->csr_content=$ligne["csr"];
		$this->srca_content=$ligne["srca"];
		
		

		$this->password=$ligne["password"];
		$this->privkey_content=$ligne["privkey"];
		$this->SquidCert=$ligne["SquidCert"];
		$this->Squidkey=$ligne["Squidkey"];
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->RootCa=$ligne["srca"];
		$this->bundle=$ligne["bundle"];
		
		if($this->UsePrivKeyCrt==0){
			$this->srca_content=$this->Squidkey;
			$this->crt_content=$this->SquidCert;
		}else{
			$this->srca_content=$ligne["privkey"];
			$this->crt_content=$ligne["crt"];
		}
			

		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Private key: ".strlen($this->srca_content)." bytes\n";}
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Certificate: ".strlen($this->crt_content)." bytes\n";}
			
		
	}
	
	
	private function build_default(){
		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Build default certificate\n";}
		
		if(!is_file("/etc/artica-postfix/openvpn/keys/openvpn-ca.key")){
			system("/usr/share/artica-postfix/bin/artica-install --openvpn-build-certificate");
		}
		$ca="/etc/artica-postfix/openvpn/keys/openvpn-ca.crt";
		$dh='/etc/artica-postfix/openvpn/keys/dh1024.pem';
		$key="/etc/artica-postfix/openvpn/keys/vpn-server.key";
		$crt="/etc/artica-postfix/openvpn/keys/vpn-server.crt";
		$this->ca_path=$ca;
		$this->dh_path=$dh;
		$this->key_path=$key;
		$this->cert_path=$crt;
		$this->password_path="/etc/artica-postfix/openvpn/keys/password";
		$this->config_path="/etc/artica-postfix/openvpn/openssl.cnf";
			
		return "--ca $ca --dh $dh --key $key --cert $crt --askpass /etc/artica-postfix/openvpn/keys/password";

	}
	
	
	public function buildSubj($ForceCommonName=null){
		$sql="SELECT *  FROM sslcertificates WHERE CommonName='$this->CommonName'";
		$q=new mysql();
		$C="US";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$CertificateMaxDays=intval($ligne["CertificateMaxDays"]);
		if($CertificateMaxDays<5){$CertificateMaxDays=730;}
		if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
		if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
		if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
		if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
		if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
		if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
		if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$C=$re[1];}
		$ST=$ligne["stateOrProvinceName"];
		$L=$ligne["localityName"];
		$O=$ligne["OrganizationName"];
		$OU=$ligne["OrganizationalUnit"];
		$CommonName=$this->CommonName;
		if($ForceCommonName<>null){$CommonName=$ForceCommonName;}
		
		if($C<>null){$TT[]="C=$C";}
		if($ST<>null){$TT[]="ST=$ST";}
		if($L<>null){$TT[]="L=$L";}
		if($O<>null){$TT[]="O=$O";}
		if($OU<>null){$TT[]="OU=$OU";}
		$TT[]="CN=$CommonName";
		
		$subj= "-subj \"/". @implode("/", $TT)."\"";
		$subj=str_replace("//","/",$subj);
		return $subj;
		
	}
	
	
	
	
}

