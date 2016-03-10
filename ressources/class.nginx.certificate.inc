<?php



class nginx_certificate{
	private $ssl_path="/etc/nginx/certificates";
	private $certificate=null;
	private $UsePrivKeyCrt=0;
	private $UseGodaddy=0;
	private $crt_content=null;
	private $csr_content=null;
	private $srca_content=null;
	private $privkey_content=null;
	private $bundle=null;
	private $SquidCert=null;
	private $Squidkey=null;
	private $clientkey=null;
	private $clientcert=null;
	private $ssl_client_certificate=0;
	private $RootCa=null;
	
	function nginx_certificate($certificate=null,$ssl_client_certificate=0){
		$this->ssl_client_certificate=intval($ssl_client_certificate);
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if($certificate<>null){$this->certificate=$certificate;}
	}
	
	
	private function certificate_default(){
		
		$ldap=new clladp();
		$sock=new sockets();
		$unix=new unix();
		@mkdir($this->ssl_path."/default",0755,true);
		
	
		@unlink("$this->ssl_path/default/privkey.cp.pem");
		@unlink("$this->ssl_path/default/cacert.pem");
		@unlink("$this->ssl_path/default/privkey.pem");
	
		$this->certificate_conf();

		$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
		if($CertificateMaxDays==null){$CertificateMaxDays='730';}
		$password=$unix->shellEscapeChars($ldap->ldap_password);
			
		$openssl=$unix->find_program("openssl");
		$config="/etc/nginx/certificates/openssl.conf";
		
		$cmd="$openssl genrsa -des3 -passout pass:$password -out $this->ssl_path/default/privkey.pem 2048 1024";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		system($cmd);
		
		$cmd="$openssl req -new -x509 -nodes -passin pass:$password -key $this->ssl_path/default/privkey.pem -batch -config $config -out $this->ssl_path/default/cacert.pem -days $CertificateMaxDays";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		system($cmd);
		system("/bin/cp $this->ssl_path/default/privkey.pem $this->ssl_path/default/privkey.cp.pem");
		system("$openssl rsa -passin pass:$password -in $this->ssl_path/default/privkey.cp.pem -out $this->ssl_path/default/privkey.pem");
	}
	
	private function CreateClientCertificate(){
		
		//privkey.pem = 
		//cacert.pem = ca.crt
		
		//$path_to_clientkey=
		//$config="/etc/nginx/certificates/openssl.conf";
		
		//openssl genrsa -des3 -passout pass:secret -out $path_to_clientkey/client.key 2048 1024
		//openssl req -new -x509 -nodes -passin pass:secret -key client.key -batch -config /etc/nginx/certificates/openssl.conf -out client.csr
		
		//openssl x509 -req -days 365 -in client.csr -CA cacert.pem -CAkey private_key.key -set_serial 01 -out client.crt
		
		$unix=new unix();
		$openssl=$unix->find_program("openssl");
		$password=$unix->shellEscapeChars($ldap->ldap_password);
		$cmd="$openssl genrsa -des3 -passout pass:$password -out $this->ssl_path/default/privkey.pem 2048 1024";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		system($cmd);
	
	}
	

	
	private function load_certificate(){
		$q=new mysql();
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`csr`,`srca`,`clientkey`,`clientcert`,`privkey`,`SquidCert`,`Squidkey`,
		`bundle`,`UseGodaddy`
		FROM sslcertificates WHERE CommonName='$this->certificate'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
		$this->crt_content=$ligne["crt"];
		$this->csr_content=$ligne["csr"];
		$this->srca_content=$ligne["srca"];
		$this->privkey_content=$ligne["privkey"];
		$this->SquidCert=$ligne["SquidCert"];
		$this->Squidkey=$ligne["Squidkey"];
		$this->clientkey=$ligne["clientkey"];
		$this->clientcert=$ligne["clientkey"];
		$this->bundle=$ligne["bundle"];
		$this->RootCa=$ligne["srca"];
		$this->UseGodaddy=intval($ligne["UseGodaddy"]);
		

		//ssl_client_certificate
		
	}
	
	
	private function is_default(){
		if(!is_file("$this->ssl_path/default/cacert.pem")){return false;}
		if(!is_file("$this->ssl_path/default/privkey.pem")){return false;}
		return true;
	}
	
	public function GetConf(){
		if($this->certificate==null){
			if(!$this->is_default()){$this->certificate_default();}
			$f[]="\tssl_certificate $this->ssl_path/default/cacert.pem;";
			$f[]="\tssl_certificate_key $this->ssl_path/default/privkey.pem;";
			return @implode("\n", $f);
		}
		
		$this->load_certificate();
		$certificate_subdir=str_replace("*", "_ALL_", $this->certificate);
		
		$Directory="$this->ssl_path/$certificate_subdir";
		@mkdir($Directory,0755,true);
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Private Key: $this->UsePrivKeyCrt\n";}
		@file_put_contents("$Directory/certificate.csr", $this->csr_content);
		@file_put_contents("$Directory/CAkey.pem", $this->srca_content);
		//https://gist.github.com/mtigas/952344
		// ssl_certificate   =SquidCert
		//ssl_certificate_key = Squidkey
		//ssl_client_certificate = srca
		
		
		$ssl_client_certificate=array();
		if($this->ssl_client_certificate==1){
			$ssl_client_certificate[]="# Use ssl_client_certificate TRUE [".__CLASS__."/".__LINE__."]";
			if($this->RootCa<>null){
				@file_put_contents("$Directory/ca.crt", $this->RootCa);
				$ssl_client_certificate[]="\tssl_client_certificate $Directory/ca.crt;";
				$ssl_client_certificate[]="\tssl_verify_client\ton;";
				//$ssl_client_certificate[]="\tssl_verify_depth 3;";
			}else{
				$ssl_client_certificate[]="# Root certificate no saved [".__CLASS__."/".__LINE__."]";
			}
			
		}else{
			$ssl_client_certificate[]="# Use ssl_client_certificate FALSE [".__CLASS__."/".__LINE__."]";
		}
		
		
		$f[]="# Use Godaddy: $this->UseGodaddy";
		
		if($this->UseGodaddy==1){
			$Directory="$Directory/Godaddy";
			@mkdir($Directory,0755,true);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Godaddy Certificate\n";}
			$chain=$this->crt_content."\n".$this->bundle;
			$unix=new unix();
			$openssl=$unix->find_program("openssl");
			if(!is_file("/etc/openssl/private-key/privkey.key")){
				@mkdir("/etc/openssl/private-key",0755,true);
				shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
			}
			
			@copy("/etc/openssl/private-key/privkey.key","$Directory/private_key.key");
			@file_put_contents("$Directory/certificate.pem", $chain);
			$f[]="# Use Godaddy key [".__CLASS__."/".__LINE__."]";
			$f[]="\tssl_certificate $Directory/certificate.pem;";
			$f[]="\tssl_certificate_key $Directory/private_key.key;";
			return @implode("\n", $f);
		}
		
		
		if($this->UsePrivKeyCrt==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use crt_content\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use privkey_content\n";}
			if(strlen($this->bundle)>20){
				$this->crt_content="$this->crt_content\n$this->bundle";
			}
			
			if(strlen($this->privkey_content)<20){
				$f[]="# Private key field is empty, using the server one.[".__CLASS__."/".__LINE__."]";
				$this->privkey_content=@file_get_contents("/etc/openssl/private-key/privkey.key");
			}
			
			@file_put_contents("$Directory/certificate.pem", $this->crt_content);
			
			@file_put_contents("$Directory/private_key.key", $this->privkey_content);
			$f[]="# Use Private key [".__CLASS__."/".__LINE__."]";
			$f[]="\tssl_certificate $Directory/certificate.pem;";
			$f[]="\tssl_certificate_key $Directory/private_key.key;";
			return @implode("\n", $f);
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use SquidCert\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$this->certificate]: Use Squidkey\n";}
		@file_put_contents("$Directory/certificate.pem",$this->SquidCert);
		@file_put_contents("$Directory/private_key.key", $this->Squidkey);
		@file_put_contents("$Directory/certificate.csr", $this->csr_content);
		$f[]="# Use certificate center key [".__CLASS__."/".__LINE__."]";
		$f[]="\tssl_certificate $Directory/certificate.pem;";
		$f[]="\tssl_certificate_key $Directory/private_key.key;";
		$f[]="# ssl_client_certificate = ". count($ssl_client_certificate)." lines [".__LINE__."]";
		$f[]=@implode("\n", $ssl_client_certificate);
		return @implode("\n", $f);
		
			
	}
	

	

	
	private function certificate_conf(){
		include_once(dirname(__FILE__).'/class.ssl.certificate.inc');
		$ssl=new ssl_certificate();
		$array=$ssl->array_ssl;
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$cc=$array["artica"]["country"]."_".$array["default_ca"]["countryName_value"];
	
		$country_code="US";
		$contryname="Delaware";
		$locality="Wilmington";
		$organizationalUnitName="Artica Web Proxy Unit";
		$organizationName="Artica";
		$emailAddress="root@$users->hostname";
		$commonName=$users->hostname;
	
	
	
		if(preg_match("#(.+?)_(.+?)$#",$cc,$re)){
			$contryname=$re[1];
			$country_code=$re[2];
		}
		if($array["server_policy"]["localityName"]<>null){$locality=$array["server_policy"]["localityName"];}
		if($array["server_policy"]["organizationalUnitName"]<>null){$organizationalUnitName=$array["server_policy"]["organizationalUnitName"];}
		if($array["server_policy"]["emailAddress"]<>null){$emailAddress=$array["server_policy"]["emailAddress"];}
		if($array["server_policy"]["organizationName"]<>null){$organizationName=$array["server_policy"]["organizationName"];}
		if($array["server_policy"]["commonName"]<>null){$commonName=$array["server_policy"]["commonName"];}
	
		@mkdir("/etc/nginx/certificates/new",0666,true);
	
		$conf[]="[ca]";
		$conf[]="default_ca=default_db";
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[default_db]";
		$conf[]="dir=.";
		$conf[]="certs=.";
		$conf[]="new_certs_dir=/etc/nginx/certificates/new";
		$conf[]="database= /etc/nginx/certificates/ca.index";
		$conf[]="serial = /etc/nginx/certificates/ca.serial";
		$conf[]="RANDFILE=.rnd";
		$conf[]="certificate=/etc/nginx/certificates/key.pem";
		$conf[]="private_key=/etc/nginx/certificates/ca.key";
		$conf[]="default_days= 730";
		$conf[]="default_crl_days=30";
		$conf[]="default_md=md5";
		$conf[]="preserve=no";
		$conf[]="name_opt=ca_default";
		$conf[]="cert_opt=ca_default";
		$conf[]="unique_subject=no";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[server_policy]";
		$conf[]="countryName=supplied";
		$conf[]="stateOrProvinceName=supplied";
		$conf[]="localityName=supplied";
		$conf[]="organizationName=supplied";
		$conf[]="organizationalUnitName=supplied";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[server_cert]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=serverAuth,clientAuth,msSGC,nsSGC";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="";
		$conf[]="[user_policy]";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[user_cert]";
		$conf[]="subjectAltName=email:copy";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=clientAuth,emailProtection";
		$conf[]="";
		$conf[]="[req]";
		$conf[]="default_bits=1024";
		$conf[]="default_keyfile=ca.key";
		$conf[]="distinguished_name=default_ca";
		$conf[]="x509_extensions=extensions";
		$conf[]="string_mask=nombstr";
		$conf[]="req_extensions=req_extensions";
		$conf[]="input_password=secret";
		$conf[]="output_password=secret";
		$conf[]="";
		$conf[]="[default_ca]";
		$conf[]="countryName=Country Code";
		$conf[]="countryName_value=$country_code";
		$conf[]="countryName_min=2";
		$conf[]="countryName_max=2";
		$conf[]="stateOrProvinceName=State Name";
		$conf[]="stateOrProvinceName_value=$contryname";
		$conf[]="localityName=Locality Name";
		$conf[]="localityName_value=$locality";
		$conf[]="organizationName=Organization Name";
		$conf[]="organizationName_value=$organizationName";
		$conf[]="organizationalUnitName=Organizational Unit Name";
		$conf[]="organizationalUnitName_value=$organizationalUnitName";
		$conf[]="commonName=Common Name";
		$conf[]="commonName_value=$commonName";
		$conf[]="commonName_max=".strlen($commonName);
		$conf[]="emailAddress=Email Address";
		$conf[]="emailAddress_value=$emailAddress";
		$conf[]="emailAddress_max=".strlen($emailAddress);
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[extensions]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="basicConstraints=critical,CA:false";
		$conf[]="";
		$conf[]="[req_extensions]";
		$conf[]="nsCertType=objsign,email,server";
		$conf[]="";
		$conf[]="[CA_default]";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[policy_match]";
		$conf[]="countryName=match";
		$conf[]="stateOrProvinceName=match";
		$conf[]="organizationName=match";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=match";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[policy_anything]";
		$conf[]="countryName=optional";
		$conf[]="stateOrProvinceName=optional";
		$conf[]="localityName=optional";
		$conf[]="organizationName=optional";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=optional";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[v3_ca]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always,issuer:always";
		$conf[]="basicConstraints=critical,CA:false";
		@mkdir("/etc/nginx/certificates",0666,true);
		file_put_contents("/etc/nginx/certificates/openssl.conf",@implode("\n",$conf));
	}	
	
	
}


