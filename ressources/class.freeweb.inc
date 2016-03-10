<?php
	include_once(dirname(__FILE__).'/class.templates.inc');
	include_once(dirname(__FILE__).'/class.ldap.inc');
	include_once(dirname(__FILE__).'/class.users.menus.inc');
	include_once(dirname(__FILE__).'/class.mysql.inc');
	include_once(dirname(__FILE__).'/class.groups.inc');
	include_once(dirname(__FILE__).'/class.lvm.root.inc');
	include_once(dirname(__FILE__).'/class.drupal.inc');
	include_once(dirname(__FILE__).'/class.ccurl.inc');
	include_once(dirname(__FILE__).'/class.mysql-multi.inc');
	include_once(dirname(__FILE__).'/class.ejabberd.inc');
	include_once(dirname(__FILE__).'/class.SugarCRM.inc');
	include_once(dirname(__FILE__).'/class.apache.certificate.php');
	
	
	class freeweb{
		public $servername;
		public $groupware='';
		public $APACHE_DIR_SITES_ENABLED='';
		public $SSL_enabled=false;
		public $AS_ROOT=false;
		public $WORKING_DIRECTORY='';
		public $SQUID_CACHMGR='';
		public $APACHE_RUN_USER='';
		public $APACHE_RUN_GROUP='';
		public $LimitByIp=0;
		public $Params=array();
		public $mod_rewrite='';
		public $lvm_size=0;
		public $lvm_vg="";
		public $UseLoopDisk=0;
		public $LoopMounts="";
		public $useMysql=0;
		public $mysql_database;
		public $mysql_password;
		public $mysql_username;
		public $ftpuser;
		public $ftppassword;
		public $useSSL=0;
		public $uid;
		public $useFTP=0;
		public $ou;
		public $error;
		public $www_dir;
		public $ServerPort=0;
		public $UseReverseProxy=0;
		public $ProxyPass;
		public $Forwarder=0;
		public $ForwardTo=0;
		public $APACHE_MOD_TOMCAT=false;
		public $COUNTRIES_ISO=array("AALAND ISLANDS"=>"AX","AFGHANISTAN"=>"AF","ALBANIA"=>"AL","ALGERIA"=>"DZ","AMERICAN SAMOA"=>"AS","ANDORRA"=>"AD","ANGOLA"=>"AO","ANGUILLA"=>"AI","ANTARCTICA"=>"AQ","ANTIGUA AND BARBUDA"=>"AG","ARGENTINA"=>"AR","ARMENIA"=>"AM","ARUBA"=>"AW","AUSTRALIA"=>"AU","AUSTRIA"=>"AT","AZERBAIJAN"=>"AZ","BAHAMAS"=>"BS","BAHRAIN"=>"BH","BANGLADESH"=>"BD","BARBADOS"=>"BB","BELARUS"=>"BY","BELGIUM"=>"BE","BELIZE"=>"BZ","BENIN"=>"BJ","BERMUDA"=>"BM","BHUTAN"=>"BT","BOLIVIA"=>"BO","BOSNIA AND HERZEGOWINA"=>"BA","BOTSWANA"=>"BW","BOUVET ISLAND"=>"BV","BRAZIL"=>"BR","BRITISH INDIAN OCEAN TERRITORY"=>"IO","BRUNEI DARUSSALAM"=>"BN","BULGARIA"=>"BG","BURKINA FASO"=>"BF","BURUNDI"=>"BI","CAMBODIA"=>"KH","CAMEROON"=>"CM","CANADA"=>"CA","CAPE VERDE"=>"CV","CAYMAN ISLANDS"=>"KY","CENTRAL AFRICAN REPUBLIC"=>"CF","CHAD"=>"TD","CHILE"=>"CL","CHINA"=>"CN","CHRISTMAS ISLAND"=>"CX","COCOS (KEELING) ISLANDS"=>"CC","COLOMBIA"=>"CO","COMOROS"=>"KM","CONGO, Democratic Republic of (was Zaire)"=>"CD","CONGO, Republic of"=>"CG","COOK ISLANDS"=>"CK","COSTA RICA"=>"CR","COTE D'IVOIRE"=>"CI","CROATIA"=>"HR","CUBA"=>"CU","CYPRUS"=>"CY","CZECH REPUBLIC"=>"CZ","DENMARK"=>"DK","DJIBOUTI"=>"DJ","DOMINICA"=>"DM","DOMINICAN REPUBLIC"=>"DO","ECUADOR"=>"EC","EGYPT"=>"EG","EL SALVADOR"=>"SV","EQUATORIAL GUINEA"=>"GQ","ERITREA"=>"ER","ESTONIA"=>"EE","ETHIOPIA"=>"ET","FALKLAND ISLANDS"=>"FK","FAROE ISLANDS"=>"FO","FIJI"=>"FJ","FINLAND"=>"FI","FRANCE"=>"FR","FRENCH GUIANA"=>"GF","FRENCH POLYNESIA"=>"PF","FRENCH SOUTHERN TERRITORIES"=>"TF","GABON"=>"GA","GAMBIA"=>"GM","GEORGIA"=>"GE","GERMANY"=>"DE","GHANA"=>"GH","GIBRALTAR"=>"GI","GREECE"=>"GR","GREENLAND"=>"GL","GRENADA"=>"GD","GUADELOUPE"=>"GP","GUAM"=>"GU","GUATEMALA"=>"GT","GUINEA"=>"GN","GUINEA-BISSAU"=>"GW","GUYANA"=>"GY","HAITI"=>"HT","HEARD AND MC DONALD ISLANDS"=>"HM","HONDURAS"=>"HN","HONG KONG"=>"HK","HUNGARY"=>"HU","ICELAND"=>"IS","INDIA"=>"IN","INDONESIA"=>"ID","IRAN (ISLAMIC REPUBLIC OF)"=>"IR","IRAQ"=>"IQ","IRELAND"=>"IE","ISRAEL"=>"IL","ITALY"=>"IT","JAMAICA"=>"JM","JAPAN"=>"JP","JORDAN"=>"JO","KAZAKHSTAN"=>"KZ","KENYA"=>"KE","KIRIBATI"=>"KI","KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF"=>"KP","KOREA, REPUBLIC OF"=>"KR","KUWAIT"=>"KW","KYRGYZSTAN"=>"KG","LAO PEOPLE'S DEMOCRATIC REPUBLIC"=>"LA","LATVIA"=>"LV","LEBANON"=>"LB","LESOTHO"=>"LS","LIBERIA"=>"LR","LIBYAN ARAB JAMAHIRIYA"=>"LY","LIECHTENSTEIN"=>"LI","LITHUANIA"=>"LT","LUXEMBOURG"=>"LU","MACAU"=>"MO","MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF"=>"MK","MADAGASCAR"=>"MG","MALAWI"=>"MW","MALAYSIA"=>"MY","MALDIVES"=>"MV","MALI"=>"ML","MALTA"=>"MT","MARSHALL ISLANDS"=>"MH","MARTINIQUE"=>"MQ","MAURITANIA"=>"MR","MAURITIUS"=>"MU","MAYOTTE"=>"YT","MEXICO"=>"MX","MICRONESIA, FEDERATED STATES OF"=>"FM","MOLDOVA, REPUBLIC OF"=>"MD","MONACO"=>"MC","MONGOLIA"=>"MN","MONTSERRAT"=>"MS","MOROCCO"=>"MA","MOZAMBIQUE"=>"MZ","MYANMAR"=>"MM","NAMIBIA"=>"NA","NAURU"=>"NR","NEPAL"=>"NP","NETHERLANDS"=>"NL","NETHERLANDS ANTILLES"=>"AN","NEW CALEDONIA"=>"NC","NEW ZEALAND"=>"NZ","NICARAGUA"=>"NI","NIGER"=>"NE","NIGERIA"=>"NG","NIUE"=>"NU","NORFOLK ISLAND"=>"NF","NORTHERN MARIANA ISLANDS"=>"MP","NORWAY"=>"NO","OMAN"=>"OM","PAKISTAN"=>"PK","PALAU"=>"PW","PALESTINIAN TERRITORY, Occupied"=>"PS","PANAMA"=>"PA","PAPUA NEW GUINEA"=>"PG","PARAGUAY"=>"PY","PERU"=>"PE","PHILIPPINES"=>"PH","PITCAIRN"=>"PN","POLAND"=>"PL","PORTUGAL"=>"PT","PUERTO RICO"=>"PR","QATAR"=>"QA","REUNION"=>"RE","ROMANIA"=>"RO","RUSSIAN FEDERATION"=>"RU","RWANDA"=>"RW","SAINT HELENA"=>"SH","SAINT KITTS AND NEVIS"=>"KN","SAINT LUCIA"=>"LC","SAINT PIERRE AND MIQUELON"=>"PM","SAINT VINCENT AND THE GRENADINES"=>"VC","SAMOA"=>"WS","SAN MARINO"=>"SM","SAO TOME AND PRINCIPE"=>"ST","SAUDI ARABIA"=>"SA","SENEGAL"=>"SN","SERBIA AND MONTENEGRO"=>"CS","SEYCHELLES"=>"SC","SIERRA LEONE"=>"SL","SINGAPORE"=>"SG","SLOVAKIA"=>"SK","SLOVENIA"=>"SI","SOLOMON ISLANDS"=>"SB","SOMALIA"=>"SO","SOUTH AFRICA"=>"ZA","SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS"=>"GS","SPAIN"=>"ES","SRI LANKA"=>"LK","SUDAN"=>"SD","SURINAME"=>"SR","SVALBARD AND JAN MAYEN ISLANDS"=>"SJ","SWAZILAND"=>"SZ","SWEDEN"=>"SE","SWITZERLAND"=>"CH","SYRIAN ARAB REPUBLIC"=>"SY","TAIWAN"=>"TW","TAJIKISTAN"=>"TJ","TANZANIA, UNITED REPUBLIC OF"=>"TZ","THAILAND"=>"TH","TIMOR-LESTE"=>"TL","TOGO"=>"TG","TOKELAU"=>"TK","TONGA"=>"TO","TRINIDAD AND TOBAGO"=>"TT","TUNISIA"=>"TN","TURKEY"=>"TR","TURKMENISTAN"=>"TM","TURKS AND CAICOS ISLANDS"=>"TC","TUVALU"=>"TV","UGANDA"=>"UG","UKRAINE"=>"UA","UNITED ARAB EMIRATES"=>"AE","UNITED KINGDOM"=>"GB","UNITED STATES"=>"US","UNITED STATES MINOR OUTLYING ISLANDS"=>"UM","URUGUAY"=>"UY","UZBEKISTAN"=>"UZ","VANUATU"=>"VU","VATICAN CITY STATE (HOLY SEE)"=>"VA","VENEZUELA"=>"VE","VIET NAM"=>"VN","VIRGIN ISLANDS (BRITISH)"=>"VG","VIRGIN ISLANDS (U.S.)"=>"VI","WALLIS AND FUTUNA ISLANDS"=>"WF","WESTERN SAHARA"=>"EH","YEMEN"=>"YE","ZAMBIA"=>"ZM","ZIMBABWE"=>"ZW");
		public $APACHE_MOD_INFO=false;
		public $APACHE_MOD_STATUS=false;
		public $phpcgi=null;
		public $ForceInstanceZarafaID=0;
		public $mysql_instance_id=0;
		public $ServerIP=null;
		public $gpid=0;
		public $groupware_version=null;
		public $content_plus=null;
		public $WebCopyID=0;
		public $loading_error=false;
		public $loading_error_text=null;
		public $sslcertificate=null;
		public $NginxFrontEnd=0;
		public $PERL_AUTHNTLM=false;
		public $enabled=1;
		public $bandlimit=0;
		public $groupware_admin=null;
		public $groupware_password=null;
		public $groupware_duplicate=null;
		public $MYSQLDUMP=array();
		public $groupware_language=null;
		private $PrefixOutput;
		
		function freeweb($servername=null,$groupware=null){
			$users=new usersMenus();
			if($GLOBALS["posix_getuid"]==0){$this->AS_ROOT=true;}
			$this->APACHE_DIR_SITES_ENABLED=$users->APACHE_DIR_SITES_ENABLED;
			$users=new usersMenus();
			$this->APACHE_MOD_INFO=$users->APACHE_MOD_INFO;
			$this->APACHE_MOD_STATUS=$users->APACHE_MOD_STATUS;
			$this->SQUID_CACHMGR=$users->SQUID_CACHMGR;
			$this->phpcgi=$users->phpcgi;
			$this->APACHE_MOD_TOMCAT=$users->APACHE_MOD_TOMCAT;
			$this->PERL_AUTHNTLM=$users->APP_PYAUTHENNTLM;
			$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"Engine\"";
			
			if($servername<>null){
				$this->WORKING_DIRECTORY="/var/www/$servername";
				$this->servername=$servername;
				$this->PrefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"$this->servername\"";
				$this->load($groupware);
			}
		}
		
		private function ApacheAccount(){
			if(!$this->AS_ROOT){return;}
				$unix=new unix();
				$this->APACHE_RUN_USER=$unix->APACHE_SRC_ACCOUNT();
				$this->APACHE_RUN_GROUP=$unix->APACHE_SRC_GROUP();
			

			if($this->APACHE_RUN_USER==null){
				if($this->AS_ROOT){writelogs("$this->PrefixOutput [CLASS/".__LINE__."] WARNING unable to stat Apache Account !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
		}
		
		public function BackupConfig(){
			$sql="SELECT * from freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$resData=$q->QUERY_SQL($sql,"artica_backup");
			$ligne=@mysql_fetch_array($resData);
			
		       $num_fields = mysql_num_fields($resData);
          	   for($j=0; $j < $num_fields; $j++){
                  	$key=mysql_field_name($resData, $j);
                  	$value=$ligne[$key];
                  	$ARRAYF[$key]=$value;
          	   }
			
          	   return base64_encode(serialize($ARRAYF));
			
		}
		
		public function CreateDatabaseName(){
			$db=$this->servername;
			$db=str_replace(".", "_", $db);
			if(strlen($db)>64){$db=substr($db, 0,64);}
			$this->mysql_database=$db;
			$this->CreateSite(true);
			return $db;
			
		}
		
		public function patchTable(){
			$q=new mysql();
			if(!$q->FIELD_EXISTS("freeweb","groupware_password","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `freeweb` ADD `groupware_password` VARCHAR(256) NULL","artica_backup");
				if(!$q->ok){return false;}
			}
			
			if(!$q->FIELD_EXISTS("freeweb","groupware_admin","artica_backup")){
				$sql="ALTER TABLE `freeweb` ADD `groupware_admin` VARCHAR(255) NULL";
				$q->QUERY_SQL($sql,'artica_backup');
				if(!$q->ok){return false;}
			}
			

			
			if(!$q->FIELD_EXISTS("freeweb","groupware_duplicate","artica_backup")){
				$sql="ALTER TABLE `freeweb` ADD `groupware_duplicate` VARCHAR(255) NULL";
				$q->QUERY_SQL($sql,'artica_backup');
				if(!$q->ok){return false;}
			}
			
			if(!$q->FIELD_EXISTS("freeweb","groupware_language","artica_backup")){
				$sql="ALTER TABLE `freeweb` ADD `groupware_language` VARCHAR(50) NULL";
				$q->QUERY_SQL($sql,'artica_backup');
				if(!$q->ok){return false;}
			}			
			
			return true;
			
		}
		
		
		private function load($groupware=null){
			if($this->AS_ROOT){writelogs("$this->PrefixOutput [CLASS/".__LINE__."] Load DB..",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$groupware_sql=null;
			$q=new mysql();
			$q->TestingConnection();
			if($groupware<>null){
				$groupware_sql=" AND groupware='$groupware'";
				
			}
			
			
			$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$this->servername'$groupware_sql","artica_backup"));
			if(!$q->ok){
				$this->loading_error=true;
				$this->loading_error_text=$q->mysql_error;
				writelogs("Fatal $q->mysql_error SELECT * from freeweb WHERE servername='$this->servername'$groupware_sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return;
			}
			
			$this->MYSQLDUMP=$ligne;
			
			$this->groupware=$ligne["groupware"];
			if($this->AS_ROOT){writelogs("$this->PrefixOutput [CLASS/".__LINE__."] Groupware `$this->groupware`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->servername=$ligne["servername"];
			if(strlen($ligne["Params"])>5){$this->Params=@unserialize(base64_decode($ligne["Params"]));}
			$this->lvm_size=$ligne["lvm_size"];
			$this->lvm_vg=$ligne["lvm_vg"];
			$this->UseLoopDisk=$ligne["UseLoopDisk"];
			$this->LoopMounts=$ligne["LoopMounts"];
			$this->useMysql=$ligne["useMysql"];
			if($this->AS_ROOT){writelogs("$this->PrefixOutput [CLASS/".__LINE__."] groupware:$this->groupware",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if($ligne["useSSL"]==1){$this->SSL_enabled=true;}
			
			if(!isset($ligne["groupware_password"])){$ligne["groupware_password"]=null;}
			
			

			if(isset($this->Params["LimitByIp"]["enabled"])){$this->LimitByIp=$this->Params["LimitByIp"]["enabled"];}
			$this->mod_rewrite=base64_decode($ligne["mod_rewrite"]);
			if($this->UseLoopDisk==1){$this->lvm_vg=null;$this->lvm_size=0;}
			$this->mysql_database=$ligne["mysql_database"];
			$this->mysql_password=$ligne["mysql_password"];
			$this->mysql_username=$ligne["mysql_username"];
			$this->ftpuser=$ligne["ftpuser"];
			$this->ftppassword=$ligne["ftppassword"];
			$this->AsError=$ligne["AsError"];
			$this->useSSL=$ligne["useSSL"];
			$this->uid=$ligne["uid"];
			$this->gpid=$ligne["gpid"];
			$this->useFTP=$ligne["useFTP"];
			$this->ou=$ligne["ou"];
			$this->www_dir=trim($ligne["www_dir"]);
			if($this->www_dir<>null){
				if($this->www_dir<>$this->WORKING_DIRECTORY){
					$this->WORKING_DIRECTORY=$this->www_dir;
				}
			}
			
			$this->ServerPort=$ligne["ServerPort"];
			$this->UseReverseProxy=$ligne["UseReverseProxy"];
			$this->ProxyPass=$ligne["ProxyPass"];
			$this->Forwarder=$ligne["Forwarder"];
			$this->ForwardTo=$ligne["ForwardTo"];
			$this->ForceInstanceZarafaID=$ligne["ForceInstanceZarafaID"];
			$this->mysql_instance_id=$ligne["mysql_instance_id"];
			$this->ServerIP=$ligne["ServerIP"];
			$this->groupware_version=$ligne["groupware_version"];
			$this->content_plus=$ligne["content_plus"];
			if(!isset($ligne["WebCopyID"])){$ligne["WebCopyID"]=0;}
			$this->WebCopyID=$ligne["WebCopyID"];
			$this->bandlimit=intval($ligne["bandlimit"]);
			$this->groupware_admin=trim($ligne["groupware_admin"]);
			$this->groupware_password=trim($ligne["groupware_password"]);
			$this->groupware_duplicate=trim($ligne["groupware_duplicate"]);
			$this->groupware_language=trim($ligne["groupware_language"]);
			

			
			if(isset($ligne["sslcertificate"])){$this->sslcertificate=$ligne["sslcertificate"];}
			if(isset($ligne["enabled"])){$this->enabled=$ligne["enabled"];}
			
			$this->HasDefaults();
			if(!$this->AS_ROOT){return;}
			
			$this->ApacheAccount();
			
			
		}
		
		public function SaveContentPlus($content){
			$content=mysql_escape_string2($content);
			$q=new mysql();
			$q->QUERY_SQL("UPDATE freeweb SET `content_plus`='$content' WHERE servername='$this->servername'","artica_backup");
			if(!$q->ok){echo $q->mysql_error;return;}
			$sock=new sockets();
			$encoded=urlencode($this->servername);
			$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$encoded");
		}
		
		public function SetError($error){
			if(!is_numeric($error)){return;}
			$q=new mysql();
			$q->QUERY_SQL("UPDATE freeweb SET `AsError`='$error' WHERE servername='$this->servername'","artica_backup");
		}
		
		public function update_groupware_version(){
			$version=null;
			if($this->groupware=="SUGAR"){
				$d=new SugarCRM_install($this->servername);
				$version=$d->GetVersion();
			}
			
			if($version<>null){
				$q=new mysql();
				$q->QUERY_SQL("UPDATE freeweb SET groupware_version='$version' WHERE servername='$this->servername'","artica_backup");
			}
			
		}
		

		
		private function HasDefaults(){
			if(!is_numeric($this->WebCopyID)){$this->WebCopyID=0;}
			if(!isset($this->Params["SECURITY"]["FreeWebsDisableBrowsing"])){$this->Params["SECURITY"]["FreeWebsDisableBrowsing"]=0;}
			
			
			
			if(!isset($this->Params["DisableIndexPHPPage"])){$this->Params["DisableIndexPHPPage"]=0;}
			if(!isset($this->Params["MOD_FCGID"]["enabled"])){$this->Params["MOD_FCGID"]["enabled"]=0;}
			if(!isset($this->Params["MOD_FCGID"]["PHP_FCGI_MAX_REQUESTS"])){$this->Params["MOD_FCGID"]["PHP_FCGI_MAX_REQUESTS"]=5000;}
			if(!isset($this->Params["MOD_FCGID"]["PHP_FCGI_CHILDREN"])){$this->Params["MOD_FCGID"]["PHP_FCGI_CHILDREN"]=8;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidBusyTimeout"])){$this->Params["MOD_FCGID"]["FcgidBusyTimeout"]=300;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidIdleTimeout"])){$this->Params["MOD_FCGID"]["FcgidIdleTimeout"]=300;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidMaxRequestInMem"])){$this->Params["MOD_FCGID"]["FcgidMaxRequestInMem"]=65536;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidMaxProcessesPerClass"])){$this->Params["MOD_FCGID"]["FcgidMaxProcessesPerClass"]=100;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidMaxRequestLen"])){$this->Params["MOD_FCGID"]["FcgidMaxRequestLen"]=131072;}
			if(!isset($this->Params["MOD_FCGID"]["FcgidOutputBufferSize"])){$this->Params["MOD_FCGID"]["FcgidOutputBufferSize"]=65536;}
			
			if(!isset($this->Params["SECURITY"]["FreeWebsDisableBrowsing"])){$this->Params["SECURITY"]["FreeWebsDisableBrowsing"]=0;}
			if(!isset($this->Params["SECURITY"]["SymLinksIfOwnerMatch"])){$this->Params["SECURITY"]["SymLinksIfOwnerMatch"]=1;}
			if(!isset($this->Params["SECURITY"]["FollowSymLinks"])){$this->Params["SECURITY"]["FollowSymLinks"]=1;}
			
			
			if(!is_numeric($this->Params["MOD_FCGID"]["enabled"])){$this->Params["MOD_FCGID"]["enabled"]=0;}
			if(!is_numeric($this->Params["MOD_FCGID"]["PHP_FCGI_MAX_REQUESTS"])){$this->Params["MOD_FCGID"]["PHP_FCGI_MAX_REQUESTS"]=5000;}
			if(!is_numeric($this->Params["MOD_FCGID"]["PHP_FCGI_CHILDREN"])){$this->Params["MOD_FCGID"]["PHP_FCGI_CHILDREN"]=8;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidBusyTimeout"])){$this->Params["MOD_FCGID"]["FcgidBusyTimeout"]=300;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidIdleTimeout"])){$this->Params["MOD_FCGID"]["FcgidIdleTimeout"]=300;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidMaxRequestInMem"])){$this->Params["MOD_FCGID"]["FcgidMaxRequestInMem"]=65536;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidMaxProcessesPerClass"])){$this->Params["MOD_FCGID"]["FcgidMaxProcessesPerClass"]=100;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidMaxRequestLen"])){$this->Params["MOD_FCGID"]["FcgidMaxRequestLen"]=131072;}
			if(!is_numeric($this->Params["MOD_FCGID"]["FcgidOutputBufferSize"])){$this->Params["MOD_FCGID"]["FcgidOutputBufferSize"]=65536;}
			if(!is_numeric($this->Params["SECURITY"]["FreeWebsDisableBrowsing"])){$this->Params["SECURITY"]["FreeWebsDisableBrowsing"]=0;}
			if(!is_numeric($this->Params["SECURITY"]["SymLinksIfOwnerMatch"])){$this->Params["SECURITY"]["SymLinksIfOwnerMatch"]=1;}
			if(!is_numeric($this->Params["SECURITY"]["FollowSymLinks"])){$this->Params["SECURITY"]["FollowSymLinks"]=1;}
			
			if(!isset($this->Params["NginxFrontEnd"])){$this->Params["NginxFrontEnd"]=0;}
			if(!is_numeric($this->Params["NginxFrontEnd"])){$this->Params["NginxFrontEnd"]=0;}
			$this->NginxFrontEnd=$this->Params["NginxFrontEnd"];
			
			
			if(!is_numeric($this->Params["DisableIndexPHPPage"])){$this->Params["DisableIndexPHPPage"]=0;}
			if(!isset($this->Params["ZARAFAWEB_PARAMS"])){$this->Params["ZARAFAWEB_PARAMS"]=array();}
			if(!isset($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"])){$this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]=1;}
			if(!is_numeric($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"])){$this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]=1;}			
			
		}
		
		
		public function groupwares_InstallLink(){
			$prefix="http";
			if(!is_numeric($this->ServerPort)){$this->ServerPort=80;}
			if($this->ServerPort==0){$this->ServerPort=80;}
			if($this->useSSL==1){$prefix="https";}
			
			$prefix="$prefix://$this->servername:$this->ServerPort";
			
			switch ($this->groupware) {
			case "WORDPRESS":
				return "$prefix/wp-admin/install.php";
			break;
			
			case "JOOMLA17":
				return "$prefix/administrator";
			break;

			case "DOTCLEAR":
				return "$prefix/admin/install/";
			break;				
			
			}
			
		}
		
		public function AddType(){
			if($this->groupware=="KLMS"){
				$f[]="\t\tAddType text/html .shtml";
			    $f[]="\t\tAddOutputFilter INCLUDES .shtml";
			    $f[]="\t\tDirectoryIndex index.shtml";
			    $f[]="\t\tAddType application/javascript .js";
			    $f[]="\t\tAddDefaultCharset utf-8";
				return @implode("\n", $f);
			}
			
			if($this->groupware=="WPAD"){
				$f[]="\t\tAddType application/x-ns-proxy-autoconfig .dat";
				$f[]="\t\tAddType application/x-ns-proxy-autoconfig .pac";
				$f[]="\t\tRedirect permanent /wpad.dat /proxy.pac";
				
				
			}
			
			if($this->groupware=="WPADDYN"){
				$f[]="\t\tRedirect permanent /wpad.dat /proxy.pac.php";
				$f[]="\t\tRedirect permanent /proxy.pac /proxy.pac.php";
				$f[]="\t\tRedirect permanent /wspad.dat /proxy.pac.php";
				
			}
			
			if($this->groupware=="UPDATEUTILITY"){
				$f[]="\t\tAddType application/octet-stream .klz";
				$f[]="\t\tAddType application/octet-stream .kdz";
				$f[]="\t\tAddType application/octet-stream .kdl";
				$f[]="\t\tAddType application/octet-stream .dif";
				$f[]="\t\tAddType application/octet-stream .xml";
				$f[]="\t\tAddType application/octet-stream .stt";
				$f[]="\t\tAddType application/octet-stream .set";
				$f[]="\t\tAddType application/octet-stream .avc";
				$f[]="\t\tAddType application/octet-stream .xms";
				$f[]="\t\tAddType application/octet-stream .keb";
				$f[]="\t\tAddType application/octet-stream .ppl";
				$f[]="\t\tAddType application/octet-stream .loc";
				$f[]="\t\tAddType application/octet-stream .dtd";
				$f[]="\t\tAddType application/octet-stream .rdf";
				$f[]="\t\tAddType application/octet-stream .prg";
				$f[]="\t\tAddType application/octet-stream .lt";
				$f[]="\t\tAddType application/octet-stream .qml";
				$f[]="\t\tAddType application/octet-stream .xpt";
				$f[]="\t\tAddType application/octet-stream .cat";
				$f[]="\t\tAddType application/octet-stream .manifest";
				$f[]="\t\tAddType application/octet-stream .dat";
			}
			$f[]="\t\tAddType application/x-gtar-compressed .tgz";
			$f[]="\t\tAddType application/x-gzip 	.gz";
			return @implode("\n", $f);
			
		}
		
		private function mod_status_allow_from(){
			if(isset($GLOBALS["MYINTERFACESAllowFROM"])){return;}
			include_once(dirname(__FILE__)."/class.system.network.inc");
			$tcp=new networking();
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$ips=$tcp->ALL_IPS_GET_ARRAY();
			while (list ($ip, $line) = each ($ips) ){$GLOBALS["MYINTERFACESAllowFROM"][]="\t\tAllow from $ip";}
			$hash=unserialize(base64_decode($sock->GET_INFO("FreeWedMoStatusAllowFrom")));
			if(isset($hash)){if(is_array($hash)){while (list ($ip, $line) = each ($hash) ){$GLOBALS["MYINTERFACESAllowFROM"][]="\t\tAllow from $ip";}}}
						
			
		}
		
		
		public function DirectorySecond(){
			
			$this->mod_status_allow_from();
			
			if($this->groupware=="KLMS"){
				$f[]="\t<Directory \"/opt/kaspersky/klmsui/share/htdocs/resources\">";
				$f[]="\t\tOptions +Indexes";
				$f[]="\t\tIndexIgnore *.json ..";
				$f[]="\t\tExpiresActive On";		
				$f[]="\t\tExpiresDefault \"now\"";
				$f[]="\t\tAddType \"application/json; charset=utf-8\" .json";
				$f[]="\t\tAddOutputFilter INCLUDES .json .js";
				$f[]="\t\tOrder allow,deny";
				$f[]="\t\tAllow from all";
				$f[]="\r</Directory>";	


				
				if($this->APACHE_MOD_INFO){
					if($this->APACHE_MOD_STATUS){
						$f[]="\t<Location /opt/kaspersky/klmsui/share/htdocs/server-status>";
						$f[]="\t\tSetHandler server-status";
						$f[]="\t\tOrder deny,allow";
						$f[]="\t\tDeny from all";
						$f[]=@implode("\n", $GLOBALS["MYINTERFACESAllowFROM"]);
						$f[]="\t\tSatisfy Any";
						$f[]="\t</Location>";
					}
				}				
				
				
				return @implode("\n", $f);
			}
			
		}
		
		
		public function groupwares_textintro(){
		switch ($this->groupware) {
			case "WORDPRESS":
				return "{howto_wordpress_text}";
			break;
			
			case "JOOMLA17":
				return "{howto_joomla_text}";
			break;	

			case "DOTCLEAR":
				return "{howto_dotclear_text}";
			break;			
			
			}
			
		}
		
		public function EnableDisableSwitch(){
			$value=0;
			if($this->enabled==0){$value=1;}
			$sql="UPDATE freeweb SET enabled='$value' WHERE servername='$this->servername'";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error;return;}
			$sock=new sockets();
			$sock->getFrameWork("freeweb.php?remove-disabled=yes");	
			
		}
		
		
		
		
		public function CreateSite($noFrame=false){
			include_once(dirname(__FILE__)."/class.user.inc");
			if(trim($this->servername)==null){
				echo "Fatal! no servername\n";return;
			}
			$q=new mysql();
			$this->mysql_password=mysql_escape_string2($this->mysql_password);
			$this->mysql_username=mysql_escape_string2($this->mysql_username);
			$this->ftpuser=mysql_escape_string2($this->ftpuser);
			$this->ftppassword=mysql_escape_string2($this->ftppassword);
			$this->groupware_password=mysql_escape_string2($this->groupware_password);
			$this->groupware_admin=mysql_escape_string2($this->groupware_admin);
			$this->uid=mysql_escape_string2($this->uid);
			

			$this->patchTable();
			
			
			
			
			
			$sqladd="INSERT INTO freeweb (mysql_password,mysql_username,ftpuser,ftppassword,useSSL,
			servername,mysql_database,uid,useMysql,
			useFTP,lvm_vg,lvm_size,UseLoopDisk,LoopMounts,ou,groupware,www_dir,groupware_admin,groupware_password,
			groupware_duplicate,groupware_language)
			VALUES('$this->mysql_password','$this->mysql_username','$this->ftpuser','$this->ftppassword','$this->useSSL',
			'$this->servername','$this->mysql_database','$this->uid','$this->useMysql',
			'$this->useFTP','$this->lvm_vg','$this->lvm_size','$this->UseLoopDisk','$this->LoopMounts','$this->ou',
			'$this->groupware','$this->www_dir',
			'$this->groupware_admin','$this->groupware_password','$this->groupware_duplicate','$this->groupware_language')";
			

		$sqlzedit[]="UPDATE freeweb SET";
		$sqlzedit[]="mysql_password='$this->mysql_password',"; 
		$sqlzedit[]="mysql_username='$this->mysql_username',"; 
		if($this->mysql_database<>null){
			$sqlzedit[]="mysql_database='$this->mysql_database',";
		}
		$sqlzedit[]="ftpuser='$this->ftpuser',";
		$sqlzedit[]="ftppassword='$this->ftppassword',";
		$sqlzedit[]="uid='".$this->uid."',";
		$sqlzedit[]="useMysql='$this->useMysql',";
		$sqlzedit[]="useFTP='$this->useFTP',";
		$sqlzedit[]="lvm_vg='$this->lvm_vg',";
		$sqlzedit[]="lvm_size='$this->lvm_size',";
		$sqlzedit[]="UseLoopDisk='$this->UseLoopDisk',";
		$sqlzedit[]="LoopMounts='$this->LoopMounts',";
		$sqlzedit[]="useSSL='$this->useSSL',";
		$sqlzedit[]="ou='$this->ou',";
		$sqlzedit[]="groupware='$this->groupware',";
		$sqlzedit[]="groupware_admin='$this->groupware_admin',";
		$sqlzedit[]="groupware_password='$this->groupware_password',";
		$sqlzedit[]="groupware_duplicate='$this->groupware_duplicate',";
		$sqlzedit[]="groupware_language='$this->groupware_language',";
		$sqlzedit[]="www_dir='$this->www_dir'";
		
		$sqlzedit[]="WHERE servername='$this->servername'";	
		$sqledit=@implode(" ", $sqlzedit);
		
		if($GLOBALS["VERBOSE"]){echo "\n*******\n$sqledit\n*******\n";}
			
			$sql="SELECT servername FROM freeweb WHERE servername='$this->servername'";
			
			
			if(!$q->FIELD_EXISTS("freeweb","content_plus","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `freeweb` ADD `content_plus` longtext NULL",'artica_backup');
			}
			
			
			$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
			if($ligne["servername"]<>null){$sql=$sqledit;}else{$sql=$sqladd;}
			if(!$q->QUERY_SQL($sql,"artica_backup")){
				$this->error=$q->mysql_error;
				return false;
			}
			
			if(!$noFrame){
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$sock->getFrameWork("cmd.php?freeweb-website=$this->servername&servername=$this->servername");
				$sock->getFrameWork("cmd.php?freeweb-groupware=$this->servername&servername=$this->servername");
			}
			return true;
			
		}
		
		public function AddDefaultSite(){
			$this->servername="_default_";
			$this->www_dir="/var/www/html";
			$this->CreateSite();
		}
		
		
		public function LimitByIp_list(){
			if(!isset($this->Params["LimitByIp"]["IPS"])){return array();}
			if(!is_array($this->Params["LimitByIp"]["IPS"])){return array();}
			reset($this->Params["LimitByIp"]["IPS"]);
			return $this->Params["LimitByIp"]["IPS"];
			
		}
		
		public function LimitByIp_add($ip){
			if($this->servername==null){return;}
			$this->Params["LimitByIp"]["enabled"]=$this->LimitByIp;
			$this->Params["LimitByIp"]["IPS"][]=$ip;
			$this->SaveParams();
		}
		
		public function LimitByIp_del($ip){
			if($this->servername==null){return;}
			writelogs("Deleting ip $ip",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$this->Params["LimitByIp"]["enabled"]=$this->LimitByIp;
			
			while (list ($num, $ligne) = each ($this->Params["LimitByIp"]["IPS"]) ){
				writelogs("found $ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if($ligne==$ip){
					writelogs("unset $ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
					unset($this->Params["LimitByIp"]["IPS"][$num]);
				}
			}
			
			$this->SaveParams();
		}
		
		public function locate_geoip_db(){
			$f[]="/usr/share/GeoIP/GeoIP.dat";
			$f[]="/usr/local/share/GeoIP/GeoIP.dat";
			$f[]="/root/.mldonkey/web_infos/GeoIP.dat";
			while (list ($key, $path) = each ($f) ){
				if(is_file($path)){return $path;}
			}
		}
		
		public function ServerAlias(){
			
			if($this->groupware=="WPAD"){
				$this->Params["ServerAlias"]["wpad"]=true;
			}
			
			if(!isset($this->Params["ServerAlias"])){return;}
			if(count($this->Params["ServerAlias"])==0){return;}
			$f=array();
			while (list ($host,$num) = each ($this->Params["ServerAlias"]) ){
				if(trim($host)==null){continue;}
				$f[]="\tServerAlias $host";
			}	
			if(count($f)>0){return @implode("\n", $f);}
			
		}
		
		
		
		public function mod_pagespeed(){
			if(!isset($this->Params["PageSpeed"])){return null;}
			if(count($this->Params["PageSpeed"])==0){return null;}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_pagespeed Enabled\n";

			$f[]="\t<IfModule pagespeed_module>";
  			$f[]="\t\tModPagespeed On";
  			$f[]="\t\tModPagespeedStatistics Off";
  			
  			
  			if(!isset($this->Params["PageSpeedParams"]["ModPagespeedFileCacheSizeKb"])){$this->Params["PageSpeedParams"]["ModPagespeedFileCacheSizeKb"]=null;}
			if(!isset($this->Params["PageSpeedParams"]["ModPagespeedFileCacheCleanIntervalMs"])){$this->Params["PageSpeedParams"]["ModPagespeedFileCacheCleanIntervalMs"]=null;}
			if(!isset($this->Params["PageSpeedParams"]["ModPagespeedLRUCacheKbPerProcess"])){$this->Params["PageSpeedParams"]["ModPagespeedLRUCacheKbPerProcess"]=null;}
			if(!isset($this->Params["PageSpeedParams"]["ModPagespeedLRUCacheByteLimit"])){$this->Params["PageSpeedParams"]["ModPagespeedLRUCacheByteLimit"]=null;}
  			$CONF=$this->Params["PageSpeedParams"];
			$ModPagespeedFileCacheSizeKb=$CONF["ModPagespeedFileCacheSizeKb"];
			$ModPagespeedFileCacheCleanIntervalMs=$CONF["ModPagespeedFileCacheCleanIntervalMs"];
			$ModPagespeedLRUCacheKbPerProcess=$CONF["ModPagespeedLRUCacheKbPerProcess"];
			$ModPagespeedLRUCacheByteLimit=$CONF["ModPagespeedLRUCacheByteLimit"];
	
			if(!is_numeric($ModPagespeedFileCacheSizeKb)){$ModPagespeedFileCacheSizeKb=102400;}
			if(!is_numeric($ModPagespeedFileCacheCleanIntervalMs)){$ModPagespeedFileCacheCleanIntervalMs=3600000;}
			if(!is_numeric($ModPagespeedLRUCacheKbPerProcess)){$ModPagespeedLRUCacheKbPerProcess=1024;}
			if(!is_numeric($ModPagespeedLRUCacheByteLimit)){$ModPagespeedLRUCacheByteLimit=16384;}			
  			$f[]="\t\tModPagespeedFileCacheSizeKb $ModPagespeedFileCacheSizeKb";
  			$f[]="\t\tModPagespeedFileCacheCleanIntervalMs $ModPagespeedFileCacheCleanIntervalMs";
  			$f[]="\t\tModPagespeedLRUCacheKbPerProcess $ModPagespeedLRUCacheKbPerProcess";
  			$f[]="\t\tModPagespeedLRUCacheByteLimit $ModPagespeedLRUCacheByteLimit";
			
			if(!is_dir("/var/cache/apache2/mod_pagespeed")){@mkdir("/var/cache/apache2/mod_pagespeed",0755,true);}
  			if(!is_dir("/var/cache/apache2/mod_pagespeed/$this->servername")){@mkdir("/var/cache/apache2/mod_pagespeed/$this->servername",0755,true);}
  			if(!is_dir("/var/cache/apache2/mod_pagespeed/$this->servername/files")){@mkdir("/var/cache/apache2/mod_pagespeed/$this->servername/files",0755,true);}
    		$f[]="\t\tModPagespeedFileCachePath            \"/var/cache/apache2/mod_pagespeed/$this->servername\"";
		    $f[]="\t\tModPagespeedGeneratedFilePrefix      \"/var/cache/apache2/mod_pagespeed/$this->servername/files/\"";
			$f[]="\t</IfModule>";
			return @implode("\n", $f);
		}		
		
		public function mod_geoip(){
			if(!isset($this->Params["GEOIP"])){return null;}
			if(count($this->Params["GEOIP"])==0){return null;}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] GeoIP Enabled\n";
			$goip=$this->locate_geoip_db();
			if(!is_file($goip)){
				echo "Starting......: ".date("H:i:s")." WARNING Apache \"$this->servername\" GeoIP unable to locate GeoIP.dat !!!\n";
				return null;
			}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] GeoIP ". count($this->Params["GEOIP"]). " countries to block\n";
			$f[]="\t<IfModule mod_geoip.c>";
  			$f[]="\t\tGeoIPEnable On";
  			$f[]="\t\tGeoIPDBFile $goip MemoryCache";
			
  			while (list ($country, $none) = each ($this->Params["GEOIP"]) ){
  				$f[]="\t\tSetEnvIf GEOIP_COUNTRY_CODE $country BlockCountry";
  			}
  			
  			
			$f[]="\t</IfModule>";
			return @implode("\n", $f);
		}
		
		public function mod_bw(){
			$f=array();
			if($this->bandlimit==0){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_bw() ModeBw Not enabled\n";}
				return;
			}
			if(!isset($this->Params["ModBwParams"])){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_bw() ModBwParams Not filled\n";}
				return;
			}
			$users=new usersMenus();
			if(!$users->APACHE_MOD_BW){return "# Mod Bandwith is not detected L.".__LINE__."\n";}
			
			$Params=$this->Params;
			$BandwidthAll=intval($Params["ModeBw"]["BandwidthAll"]);
			if($BandwidthAll==0){$BandwidthAll=1536000;}
			
			echo "Starting......: ".date("H:i:s")." Apache \"$this->servername\" BandwidthAll = $BandwidthAll\n";
			$ForceBandWidthModule=intval($Params["ModeBw"]["ForceBandWidthModule"]);

			
			$results[]="\t<IfModule mod_bw.c>";
			$results[]="\t\tBandwidthModule On";
			if($ForceBandWidthModule==1){
				$results[]="\t\tForceBandWidthModule On";
			}else{
				$results[]="\t\tForceBandWidthModule Off";
			}
			$results[]="\t\tBandWidthError 510";
			
			$CONF=$Params["ModBwParams"];
			
			if(count($CONF)>0){
				while (list ($index, $RuleArray) = each ($CONF) ){
					$BW_SOURCE=trim(strtolower($RuleArray["BW_SOURCE"]));
					$BW_ENGINE=$RuleArray["BW_ENGINE"];
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_bw() Bandwith source:$BW_SOURCE -> $BW_ENGINE\n";}
					if($BW_SOURCE=="all"){continue;}
					
					$BW_MAX=$RuleArray["BW_MAX"];
					if(!isset($RuleArray["BW_SIZE"])){$RuleArray["BW_SIZE"]=0;}
					$BW_SIZE=$RuleArray["BW_SIZE"];
					if(!is_numeric($BW_MAX)){unset($CONF[$index]);continue;}
					if($BW_ENGINE=="BandWidth"){$f[]="\t\tBandWidth $BW_SOURCE $BW_MAX";}
					if($BW_ENGINE=="MaxConnection"){$f[]="\t\tMaxConnection $BW_SOURCE $BW_MAX";}
					if($BW_ENGINE=="LargeFileLimit"){$f[]="\t\tLargeFileLimit $BW_SOURCE $BW_SIZE $BW_MAX";}
					if($BW_ENGINE=="MinBandWidth"){$f[]="\t\tMinBandWidth $BW_SOURCE $BW_MAX";}
					unset($CONF[$index]);
				}
				
				if(count($CONF)>0){
					while (list ($index, $RuleArray) = each ($CONF) ){
						$BW_SOURCE=trim(strtolower($RuleArray["BW_SOURCE"]));
						$BW_ENGINE=$RuleArray["BW_ENGINE"];
						$BW_MAX=$RuleArray["BW_MAX"];
						if(!isset($RuleArray["BW_SIZE"])){$RuleArray["BW_SIZE"]=0;}
						$BW_SIZE=$RuleArray["BW_SIZE"];
						if(!is_numeric($BW_MAX)){unset($CONF[$index]);continue;}
						if($BW_ENGINE=="BandWidth"){$f[]="\t\tBandWidth $BW_SOURCE $BW_MAX";}
						if($BW_ENGINE=="MaxConnection"){$f[]="\t\tMaxConnection $BW_SOURCE $BW_MAX";}
						if($BW_ENGINE=="LargeFileLimit"){$f[]="\t\tLargeFileLimit $BW_SOURCE $BW_SIZE $BW_MAX";}
						if($BW_ENGINE=="MinBandWidth"){$f[]="\t\tMinBandWidth $BW_SOURCE $BW_MAX";}					
						
					}
				}
			}
			if(count($f)>0){
				$results[]=@implode("\n", $f);
			}
			
			$results[]="\t\tMinBandwidth all -1";
			$results[]="\t\tMaxConnection all 500";
			$results[]="\t\tBandwidth all $BandwidthAll";
			$results[]="\t</IfModule>";
			return @implode("\n", $results);
			
			
		}
		
		
		
		
		public function QUOS(){
			if(!$this->AS_ROOT){return;}
			if(!isset($this->Params["QOS"])){return;}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] QUOS() {$this->Params["QOS"]["EnableQOS"]}\n";
			if(!isset($this->Params["QOS"])){$this->Params["QOS"]["EnableQOS"]=0;}
			$EnableQOS=$this->Params["QOS"]["EnableQOS"];
			if(!is_numeric($EnableQOS)){$EnableQOS=0;}
			if(isset($this->Params["QOS"]["QS_ClientEntries"])){$QS_ClientEntries=$this->Params["QOS"]["QS_ClientEntries"];}
			if(isset($this->Params["QOS"]["QS_SrvMaxConnPerIP"])){$QS_SrvMaxConnPerIP=$this->Params["QOS"]["QS_SrvMaxConnPerIP"];}
			if(isset($this->Params["QOS"]["MaxClients"])){$MaxClients=$this->Params["QOS"]["MaxClients"];}
			if(isset($this->Params["QOS"]["QS_SrvMaxConnClose"])){$QS_SrvMaxConnClose=$this->Params["QOS"]["QS_SrvMaxConnClose"];}
			if(isset($this->Params["QOS"]["QS_SrvMinDataRate"])){$QS_SrvMinDataRate=$this->Params["QOS"]["QS_SrvMinDataRate"];}
			if(isset($this->Params["QOS"]["LimitRequestFields"])){$LimitRequestFields=$this->Params["QOS"]["LimitRequestFields"];}
			if(isset($this->Params["QOS"]["QS_LimitRequestBody"])){$QS_LimitRequestBody=$this->Params["QOS"]["QS_LimitRequestBody"];}
			
			if($EnableQOS==0){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] QUOS() DISABLED\n";
				//$f[]="\t<IfModule mod_qos.c>";
				//$f[]="\t\tQS_InvalidUrlEncoding off";
				//$f[]="\t\tQS_ResponseHeaderFilter off";
				//$f[]="\t\tQS_DenyQueryBody off";
				//$f[]="\t\tQS_PermitUriBody off";
				//$f[]="\t</IfModule>";
				return null;
			}
			
			
			
			if(!is_numeric($QS_ClientEntries)){$QS_ClientEntries=100000;}
			if(!is_numeric($QS_SrvMaxConnPerIP)){$QS_SrvMaxConnPerIP=50;}
			if(!is_numeric($MaxClients)){$MaxClients=256;}
			if(!is_numeric($QS_SrvMaxConnClose)){$QS_SrvMaxConnClose=180;}
			if($QS_SrvMinDataRate==null){$QS_SrvMinDataRate="150 1200";}
			if(!is_numeric($LimitRequestFields)){$LimitRequestFields=30;}
			if(!is_numeric($QS_LimitRequestBody)){$QS_LimitRequestBody=102400;}
			
			
		
			$f[]="\t<IfModule mod_qos.c>";
		  	if($QS_ClientEntries>0){ $f[]="\t\tQS_ClientEntries $QS_ClientEntries";}
		  	if($QS_SrvMaxConnPerIP>0){ $f[]="\t\tQS_SrvMaxConnPerIP $QS_SrvMaxConnPerIP";}
			if($MaxClients>0){ $f[]="\t\tMaxClients $MaxClients";}
			if($QS_SrvMaxConnClose>0){ $f[]="\t\tQS_SrvMaxConnClose $QS_SrvMaxConnClose";}
			if(preg_match("#[0-9]+\s+[0-9]+#",$QS_SrvMinDataRate)){
				$f[]="\t\tQS_SrvMinDataRate $QS_SrvMinDataRate";
			}		 		  		   
			if($LimitRequestFields>0){ $f[]="\t\tLimitRequestFields $LimitRequestFields";}
			if($QS_LimitRequestBody>0){ $f[]="\t\tQS_LimitRequestBody $QS_LimitRequestBody";}			
			$f[]="\t</IfModule>";
			return @implode("\n",$f);
			
		}
		
		
		public function AllowOverride(){
			
			if($this->groupware=="UPDATEUTILITY"){
				return "\t\tAllowOverride None";
			}
			
			if(!isset($this->Params["SECURITY"]["DisableHtAccess"])){$this->Params["SECURITY"]["DisableHtAccess"]=0;}
			$AllowOverride="all";
			if(isset($this->Params["SECURITY"])){
				if($this->Params["SECURITY"]["DisableHtAccess"]==1){return "\t\tAllowOverride None";}
			}
			return "\t\tAllowOverride $AllowOverride";
		
		}
		
		public function Charsets(){
			if(!isset($this->Params["AddDefaultCharset"])){return;}
			$this->Params["AddDefaultCharset"]=trim($this->Params["AddDefaultCharset"]);
			if(trim($this->Params["AddDefaultCharset"])==null){return;}
			$f[]="\tAddDefaultCharset {$this->Params["AddDefaultCharset"]}";
			if(strtolower($this->Params["AddDefaultCharset"])=="off"){return @implode("\n",$f);}
			if(strtolower($this->Params["AddDefaultCharset"])=="on"){return @implode("\n",$f);}
			$f[]="\tAddCharset {$this->Params["AddDefaultCharset"]} .php";
			$f[]="\tAddCharset {$this->Params["AddDefaultCharset"]} .html";
			$f[]="\tAddCharset {$this->Params["AddDefaultCharset"]} .xml";
			$f[]="\tAddCharset {$this->Params["AddDefaultCharset"]} .js";
			@implode("\n",$f);
		}

		public function SaveParams(){
			reset($this->Params);
			$conf=base64_encode(serialize($this->Params));
			$sql="UPDATE freeweb SET `Params`='$conf' WHERE servername='$this->servername'";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				writelogs("$this->servername:: $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				echo $q->mysql_error."\nclass: ".__CLASS__."\nFile: ".basename(__FILE__)."\nLine:".__LINE__;return;
			}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$sock->getFrameWork("cmd.php?freeweb-website=yes&servername=$this->servername");
			
		}
		
		
		public function AuthUsers(){
			if(!isset($this->Params["LDAP"]["members"])){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] AUTH: No members set\n";
				return null;
			}
			$users=$this->Hash_ldap_users();
			echo "$this->PrefixOutput [CLASS/".__LINE__."] AUTH: ".count($users)." member(s)\n";
			
			if(count($users)==0){return null;}
			while (list ($num, $ligne) = each ($users) ){
				if($num==null){continue;}
				$f[]=$ligne;	
			}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] AUTH: ".count($f)." member(s) compiled\n";
			if(count($f)==0){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] AUTH: No user defined\n";
				return null;
			}
			
			if(!isset($GLOBALS["MAANAGER_NAME"])){
				$ldap=new clladp();
				$GLOBALS["MAANAGER_NAME"]=$ldap->ldap_admin;
			}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] AUTH:{$GLOBALS["MAANAGER_NAME"]}\n";
			$f[]="\t\tRequire ldap-user {$GLOBALS["MAANAGER_NAME"]}";
			return @implode("\n", $f);
			
		}
		public function mod_status($servername=null){
			//désactivé le 13 Jav 2014
			
			return null;
			$sock=new sockets();
			$ApacheDisableModStatus=intval($sock->GET_INFO("ApacheDisableModStatus"));
			if($ApacheDisableModStatus==1){return null;}
			if($servername==null){$servername=$this->servername;}
			if($this->groupware=="KLMS"){return null;}
			if(!$this->APACHE_MOD_INFO){echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_info, no such module\n";return;}
			if(!$this->APACHE_MOD_STATUS){echo "$this->PrefixOutput [CLASS/".__LINE__."] mod_status, no such module\n";return;}
			if(isset($GLOBALS["HTTPD_CONF_MOD_STATUS"][$servername])){return $GLOBALS["HTTPD_CONF_MOD_STATUS"][$servername];}
			
			if(!isset($GLOBALS["MYINTERFACESAllowFROM"])){
				include_once(dirname(__FILE__)."/class.system.network.inc");
				$tcp=new networking();
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$ips=$tcp->ALL_IPS_GET_ARRAY();
				while (list ($ip, $line) = each ($ips) ){$GLOBALS["MYINTERFACESAllowFROM"][]="\t\tAllow from $ip";}
				$hash=unserialize(base64_decode($sock->GET_INFO("FreeWedMoStatusAllowFrom")));
				if(isset($hash)){if(is_array($hash)){while (list ($ip, $line) = each ($hash) ){$GLOBALS["MYINTERFACESAllowFROM"][]="\t\tAllow from $ip";}}}
			}
			
			
			
			$dirMD=md5($servername);
			$f[]="\t<Location /$dirMD/$dirMD-status>";
			$f[]="\t\tSetHandler server-status";
			$f[]="\t\tOrder deny,allow";
			$f[]="\t\tDeny from all";
			$f[]=@implode("\n", $GLOBALS["MYINTERFACESAllowFROM"]);
			$f[]="\t\tSatisfy Any";
			$f[]="\t</Location>";
			$mod_status=@implode("\n",$f);
			$GLOBALS["HTTPD_CONF_MOD_STATUS"][$servername]=$mod_status;
			return $mod_status;
			
		}
		
		public function Apache2_AuthenNTLM(){
			if(!$this->PERL_AUTHNTLM){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] Not NTLM compliance\n";
				return null;
			}
			$Params=$this->Params;
			if(!isset($Params["NTLM"]["enabled"])){$Params["NTLM"]["enabled"]=0;}
			if(!is_numeric($Params["NTLM"]["enabled"])){$Params["NTLM"]["enabled"]=0;}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] is NTLM ? = {$Params["NTLM"]["enabled"]}\n";
			if($Params["NTLM"]["enabled"]==0){return null;}
			$RESULTS=$this->ActiveDirectoryInformations();
			
			if(strlen($RESULTS["ADNETIPADDR"])>7){
				$adname=$RESULTS["ADNETIPADDR"];
				$ipaddrZ=explode(".",$adname);
				while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
				$adname=@implode(".", $ipaddrZ);
				
			}
			
			if($adname==null){
				if($RESULTS["WINDOWS_DNS_SUFFIX"]<>null){
					$adname=strtolower("{$RESULTS["WINDOWS_SERVER_NETBIOSNAME"]}.{$RESULTS["WINDOWS_DNS_SUFFIX"]}");
				}else{
					$adname=$RESULTS["WINDOWS_SERVER_NETBIOSNAME"];
				}
			}
			
			if($RESULTS["LDAP_SUFFIX"]<>null){
				$adname="ldap://$adname/{$RESULTS["LDAP_SUFFIX"]}";
			}
			

			$workgroup=$RESULTS["ADNETBIOSDOMAIN"];
			echo "$this->PrefixOutput [CLASS/".__LINE__."] NTLM  = $workgroup@$adname\n";
			$tabs="\t\t";
			$conf[]="{$tabs}PythonAuthenHandler pyntlm";
			$conf[]="{$tabs}AuthType NTLM";
			$conf[]="{$tabs}AuthName $workgroup";
			$conf[]="{$tabs}PythonOption Domain $workgroup";
			$conf[]="{$tabs}PythonOption PDC $adname";
			$conf[]="{$tabs}require valid-user";
			$array=$this->Hash_ldap_users();
			if(count($array)>0){$conf[]=@implode("\n", $array);}
			return @implode("\n", $conf);
		}
		
		public function Hash_ldap_users(){
			$array=$this->Params["LDAP"]["members"];
			if(!is_array($array)){return array();}
			if(count($array)==0){return array();}
			$f=array();
			$c=0;
			$tabs="\t\t";
			while (list ($member, $ligne) = each ($array) ){
				if($member==null){continue;}
					
          		if(preg_match("#^@(.+):([0-9]+)$#",$member,$re)){
          			$groups=new groups($re[2]);
          			$c++;
          			$f[]="{$tabs}Require group $groups->groupName";
          			continue;
          		}
          		
          		if(preg_match("#^group:@(.+?):([0-9]+)#",$member,$re)){
          			$groupName=$re[1];
          			$f[]="{$tabs}Require group $groupName";
          			continue;
          		}
          		
          		
          		if(preg_match("#^group:@(.+?)$#",$member,$re)){
          			$groupName=$re[1];
          			$f[]="{$tabs}Require group $groupName";
          			continue;
          		}
          		
          		if(preg_match("#^user:(.+)#",$member,$re)){
          			$member=$re[1];
          			$f[]="{$tabs}Require user $member";
          			continue; 
          		}
 
			}
			
			return $f;
		}
		
		
		private function ActiveDirectoryInformations(){
			if(isset($GLOBALS["ActiveDirectoryInformations"])){return $GLOBALS["ActiveDirectoryInformations"];}
			$sock=new sockets();
			$RESULTS=array();
			$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
			if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
			$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
			if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
			$RESULTS["LDAP_SUFFIX"]=null;
			if($EnableKerbAuth==1){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] is NTLM ? = Loading KerbAuthInfos\n";
				$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
				$RESULTS["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];
				$RESULTS["WINDOWS_SERVER_NETBIOSNAME"]=$array["WINDOWS_SERVER_NETBIOSNAME"];
				$RESULTS["ADNETBIOSDOMAIN"]=$array["ADNETBIOSDOMAIN"];
				$RESULTS["ADNETIPADDR"]=$array["ADNETIPADDR"];
				
				$ipaddrZ=explode(".",$RESULTS["ADNETIPADDR"]);
				while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
				$RESULTS["ADNETIPADDR"]=@implode(".", $RESULTS["ADNETIPADDR"]);
				
				$RESULTS["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
				$RESULTS["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
				$RESULTS["LDAP_SUFFIX"]=$array["LDAP_SUFFIX"];
			}
			if($EnableSambaActiveDirectory==1){
				$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
				echo "$this->PrefixOutput [CLASS/".__LINE__."] is NTLM ? = Loading SambaAdInfos\n";
				$RESULTS["WINDOWS_SERVER_NETBIOSNAME"]=$array["ADSERVER"];
				$RESULTS["WINDOWS_DNS_SUFFIX"]=$array["ADDOMAIN"];
				$RESULTS["ADNETBIOSDOMAIN"]=$array["WORKGROUP"];
				$RESULTS["WINDOWS_SERVER_ADMIN"]=$array["ADADMIN"];
				$RESULTS["WINDOWS_SERVER_PASS"]=$array["PASSWORD"];
				$RESULTS["ADNETIPADDR"]=$array["ADSERVER_IP"];
				$ipaddrZ=explode(".",$RESULTS["ADNETIPADDR"]);
				while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
				$RESULTS["ADNETIPADDR"]=@implode(".", $ipaddrZ);
				
			}
			
			if($RESULTS["LDAP_SUFFIX"]==null){
				$this->SAMBA_GetNetAdsInfos();
				$RESULTS["LDAP_SUFFIX"]=$GLOBALS["CACHE_NETADS"]["Bind Path"];
			}
			
			if($RESULTS["ADNETIPADDR"]==null){
				$this->SAMBA_GetNetAdsInfos();
				$RESULTS["ADNETIPADDR"]=$GLOBALS["CACHE_NETADS"]["KDC server"];
			}
			
			if(!isset($RESULTS["ADNETBIOSDOMAIN"])){$RESULTS["ADNETBIOSDOMAIN"]=null;}
			if(!isset($RESULTS["WINDOWS_SERVER_NETBIOSNAME"])){$RESULTS["WINDOWS_SERVER_NETBIOSNAME"]=null;}
			if(!isset($RESULTS["WINDOWS_DNS_SUFFIX"])){$RESULTS["WINDOWS_DNS_SUFFIX"]=null;}
			if(!isset($RESULTS["WINDOWS_SERVER_ADMIN"])){$RESULTS["WINDOWS_SERVER_ADMIN"]=null;}
			if(!isset($RESULTS["WINDOWS_SERVER_PASS"])){$RESULTS["WINDOWS_SERVER_PASS"]=null;}
			$GLOBALS["ActiveDirectoryInformations"]=$RESULTS;
			return $RESULTS;
			
		}
		
		private function SAMBA_GetNetAdsInfos(){
			$sock=new sockets();
			if(isset($GLOBALS["CACHE_NETADS"])){return $GLOBALS["CACHE_NETADS"];}
			$GLOBALS["CACHE_NETADS"]=unserialize(base64_decode($sock->getFrameWork("samba.php?GetNetAdsInfos=yes")));
		}
		
		
		public function RewriteEngine(){
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] is forwarder ? ($this->Forwarder) groupware=$this->groupware\n";}
			if($this->Forwarder==1){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] is forwared to $this->ForwardTo\n";
				$conf[]="\tRewriteEngine On";
				$conf[]="\tRewriteRule (.*) $this->ForwardTo";
				return @implode("\n", $conf);
			}
			
			if($this->groupware=="WORDPRESS"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine On";
				$f[]="\t\tRewriteBase /";
				$f[]="\t\tRewriteRule ^index\.php$ - [L]";
				$f[]="\t\tRewriteRule ^wp-admin/includes/ - [F,L]";
				$f[]="\t\tRewriteRule !^wp-includes/ - [S=3]";
				$f[]="\t\tRewriteRule ^wp-includes/[^/]+\.php$ - [F,L]";
				$f[]="\t\tRewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]";
				$f[]="\t\tRewriteRule ^wp-includes/theme-compat/ - [F,L]";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !". md5($this->servername);	
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-f";
				
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-d";
				$f[]="\t\tRewriteRule . /index.php [L]";
				$f[]="\t</IfModule>";
				return @implode("\n", $f);
			}
			
			
			if($this->groupware=="CONCRETE5"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine On";
				$f[]="\t\tRewriteBase /";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME}/index.html !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME}/index.php !-f";
				$f[]="\t\tRewriteRule ^(.*)\$ index.php/\$1 [L]";
				$f[]="\t</IfModule>";
				return @implode("\n", $f);
			}
			
			
		}
		
		public function ApplyPermissions(){
			$WORKING_DIRECTORY=$this->WORKING_DIRECTORY;
			if(is_link($WORKING_DIRECTORY)){$WORKING_DIRECTORY=@readlink($WORKING_DIRECTORY);}
			if(!is_dir($WORKING_DIRECTORY)){return;}
			if($WORKING_DIRECTORY=="/usr/share/artica-postfix"){return;}
			
			$unix=new unix();
			$TEMP_FILE=$unix->FILE_TEMP().".sh";
			$php=$unix->LOCATE_PHP5_BIN();
			$apache_usr=$unix->APACHE_SRC_ACCOUNT();
			$apache_group=$unix->APACHE_SRC_GROUP();
			@mkdir("/etc/artica-postfix/MOUNT_BINDS",0755,true);
			
			$DATAS=unserialize(@file_get_contents("/etc/artica-postfix/MOUNT_BINDS/BINDS.db"));
			
			if(trim($this->uid)<>null){
				include_once(dirname(__FILE__)."/class.user.inc");
				$apache_usr=$this->uid;
				$ct=new user($this->uid);
				$homeDirectory=$ct->homeDirectory;
				if(!is_dir($homeDirectory)){@mkdir($homeDirectory,0755,true);}
				if(is_link($homeDirectory)){$homeDirectory=@readlink($homeDirectory);}
				@chown($WORKING_DIRECTORY, $apache_usr);
				$DATAS[$WORKING_DIRECTORY][$homeDirectory]=$this->uid;
				@file_put_contents("/etc/artica-postfix/MOUNT_BINDS/BINDS.db", serialize($DATAS));
			
			}
			if($this->gpid<>null){$apache_group=$this->gpid;}
			$DIRS_EXPLODED=explode("/",$WORKING_DIRECTORY);
			$PATH=null;
			while (list ($index, $FOLDER) = each ($DIRS_EXPLODED)){
				$FOLDER=trim($FOLDER);
				if($FOLDER==null){continue;}
				if($FOLDER=="/"){continue;}
				$PATH=$PATH."/$FOLDER";
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $PATH -> 0755\n";}
				@chmod($PATH,0755);
				
			
			}
			
			
			
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] owned by $apache_usr/$apache_group\n";}
			@chown($WORKING_DIRECTORY, $apache_usr);
			@chgrp($WORKING_DIRECTORY, $apache_group);
			
			$find=$unix->find_program("find");
			$chmod=$unix->find_program("chmod");
			$chown=$unix->find_program("chown");
			$rm=$unix->find_program("rm");
			$nohup=$unix->find_program("nohup");
			$NICE=$unix->EXEC_NICE();
			$f[]="# !/bin/sh";
			$f[]="$find \"$WORKING_DIRECTORY/\" -type d -exec $chmod 755 {} \;";
			$f[]="$find \"$WORKING_DIRECTORY/\" -type f -exec $chmod 644 {} \;";
			$f[]="$chown -R $apache_usr:$apache_group \"$WORKING_DIRECTORY\"";
			$f[]="$php /usr/share/artica-postfix/exec.mounts.bind.php";
			$f[]="$rm $TEMP_FILE";
			$f[]="";
			@file_put_contents($TEMP_FILE, @implode("\n", $f));
			@chmod($TEMP_FILE, 0755);
			shell_exec("$nohup $TEMP_FILE >/dev/null 2>&1 &");
		}
		
		
		public function mpm_itk_module(){
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mpm_itk_module() checks..\n";}
			$users=new usersMenus();
			if(!$users->MPM_ITK_MODULE){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mpm_itk_module() MPM_ITK_MODULE=FALSE\n";}
				return;}
			if($this->uid==null){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mpm_itk_module() No uid set\n";}
				return;}
			if(!is_numeric($this->gpid)){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mpm_itk_module() gpid:`$this->gpid` is not a numeric value\n";}
				return;}
			if($this->gpid==0){return;}	
			$gp=new groups($this->gpid);
			
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mpm_itk_module() Success for $this->uid $gp->groupName\n";}
			
			$f[]="\t<IfModule mpm_itk_module>";
			$f[]="\t\tAssignUserId $this->uid $gp->groupName";
			$f[]="\t</IfModule>";
			return @implode("\n", $f);
		}
		
		public function LoadModules(){
			$f=array();
			if($this->groupware=="ARKEIA"){
				//$f[]="\tLoadModule php5_module /opt/arkeia/wui/httpd/modules/libphp5.so";
				
			}
			
			if($this->groupware=="KLMS"){
				$f[]="LoadModule klwi_module /opt/kaspersky/klmsui/libexec/mod_klwi4.so";
			}
			
			if(count($f)>0){return @implode("\n",$f);}
		}
		
		
		



		
		
		public function mod_evasive(){
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
			if($FreeWebsEnableModEvasive<>1){return null;}			
			$q=new mysql();
			if(!isset($this->Params["mod_evasive"])){return;}
			$Params=$this->Params["mod_evasive"];	
			if(!is_numeric($Params["DOSHashTableSize"])){$Params["DOSHashTableSize"]=1024;}
			if(!is_numeric($Params["DOSPageCount"])){$Params["DOSPageCount"]=10;}
			if(!is_numeric($Params["DOSSiteCount"])){$Params["DOSSiteCount"]=150;}
			if(!is_numeric($Params["DOSPageInterval"])){$Params["DOSPageInterval"]=1.5;}
			if(!is_numeric($Params["DOSSiteInterval"])){$Params["DOSSiteInterval"]=1.5;}
			if(!is_numeric($Params["DOSBlockingPeriod"])){$Params["DOSBlockingPeriod"]=10.7;}	
			if(!is_numeric($Params["DOSEnable"])){$Params["DOSEnable"]=0;}	
			if($Params["DOSEnable"]==0){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] DDOS evasive disabled\n";
				return; 
			}		

			
			echo "$this->PrefixOutput [CLASS/".__LINE__."] DDOS evasive Enabled\n";
 
					
			
			$f[]="<IfModule mod_evasive20.c>";
			$f[]="   DOSHashTableSize {$Params["DOSHashTableSize"]}";
			$f[]="   DOSPageCount {$Params["DOSPageCount"]}";
			$f[]="   DOSSiteCount {$Params["DOSSiteCount"]}";
			$f[]="   DOSPageInterval {$Params["DOSPageInterval"]}";
			$f[]="   DOSSiteInterval {$Params["DOSSiteInterval"]}";
			$f[]="   DOSBlockingPeriod {$Params["DOSBlockingPeriod"]}";
			$f[]="   DOSLogDir  \"/var/log/apache2/$this->servername/mod_evasive.log\"";
			$f[]="   DOSSystemCommand \"/bin/echo `date '+%F %T'` $this->servername  %s >> /var/log/apache2/dos_evasive_attacks.log\"";
			
			
			@mkdir("/var/log/apache2/$this->servername",0755,true);
			$sql="SELECT * FROM freewebs_whitelist WHERE servername='$this->servername' $qsearch ORDER BY ID DESC";
			$results=$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){echo "$q->mysql_error\n";}			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				$f[]="   DOSWhitelist {$ligne["ipaddr"]}";
			}
			
			$f[]="</IfModule>"; 
			$f[]="";			
			return @implode("\n",$f);
		}
		
		public function ReverseProxy(){
			if($this->UseReverseProxy==0){return;}
			if(substr($this->ProxyPass, strlen($this->ProxyPass)-1,1)<>'/'){$this->ProxyPass=$this->ProxyPass."/";}
			if(!is_numeric($this->Params["Proxy"]["ProxyRequests"])){$this->Params["Proxy"]["ProxyRequests"]=0;}
			if(!is_numeric($this->Params["Proxy"]["ProxyVia"])){$this->Params["Proxy"]["ProxyVia"]=0;}
			if(!is_numeric($this->Params["Proxy"]["KeepAlive"])){$this->Params["Proxy"]["KeepAlive"]=0;}
			
			if(!isset($this->Params["Proxy"]["ProxyTimeout"])){$this->Params["Proxy"]["ProxyTimeout"]=300;}
			if(!is_numeric($this->Params["Proxy"]["ProxyTimeout"])){$this->Params["Proxy"]["ProxyTimeout"]=300;}
			
			
			
			if($this->Params["Proxy"]["ProxyRequests"]==0){$this->Params["Proxy"]["ProxyRequests"]="Off";}else{$this->Params["Proxy"]["ProxyRequests"]="On";}
			if($this->Params["Proxy"]["ProxyVia"]==0){$this->Params["Proxy"]["ProxyVia"]="Off";}else{$this->Params["Proxy"]["ProxyVia"]="On";}
			if($this->Params["Proxy"]["KeepAlive"]==0){$this->Params["Proxy"]["KeepAlive"]="Off";}else{$this->Params["Proxy"]["KeepAlive"]="On";}
			
			$f[]="ProxyPreserveHost On";
			$f[]="ProxyRequests {$this->Params["Proxy"]["ProxyRequests"]}";
			$f[]="ProxyVia {$this->Params["Proxy"]["ProxyVia"]}";
			$f[]="KeepAlive {$this->Params["Proxy"]["KeepAlive"]}";
			$f[]="ProxyTimeout {$this->Params["Proxy"]["ProxyTimeout"]}";
			if(strlen($this->Params["Proxy"]["AllowCONNECT"])>1){$f[]="AllowCONNECT {$this->Params["Proxy"]["AllowCONNECT"]}";}
			$sql="SELECT * FROM freewebs_proxy WHERE servername='$this->servername'";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			if(mysql_num_rows($results)==0){return;}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(!preg_match("#(.+?)\s+(.+)#", $ligne["ProxyPass"],$re)){continue;}
				$request_from=trim($re[1]);$redirect_to=trim($re[2]);
				if(substr($request_from, strlen($request_from)-1)<>'/'){$request_from=$request_from."/";}
				if(substr($redirect_to, strlen($redirect_to)-1)<>'/'){$redirect_to=$redirect_to."/";}
				$f[]="ProxyPass $request_from\t$redirect_to";
				$f[]="ProxyPassReverse $request_from\t$redirect_to";
				
			}
			return @implode("\n", $f);
		}
		
		private function LoadLibraries(){
			if(!isset($GLOBALS["CLASS_SOCKETS"])){
				$GLOBALS["CLASS_SOCKETS"]=new sockets();
			}
			if(!isset($GLOBALS["CLASS_UNIX"])){
				$GLOBALS["CLASS_UNIX"]=new unix();
			}
			if(!isset($GLOBALS["CLASS_USERS"])){
				$GLOBALS["CLASS_USERS"]=new usersMenus();
			}
		}
		
		
		public function mod_security_ifvailable(){
			
			
			$this->LoadLibraries();
			$sock=$GLOBALS["CLASS_SOCKETS"];
			$unix=$GLOBALS["CLASS_UNIX"];
			$users=$GLOBALS["CLASS_USERS"];
			
			
			$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
			$d_path=$unix->APACHE_DIR_SITES_ENABLED();
			$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
			$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
			$FreeWebsEnableModSecurity=intval($sock->GET_INFO("FreeWebsEnableModSecurity"));
			
			if($FreeWebsEnableModSecurity==0){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_security2' disabled\n";
				return false;
			}
			if(!is_file("/usr/lib/libxml2.so.2")){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache /usr/lib/libxml2.so.2 no such library\n";
				return false;
			}
			if(!is_file("$APACHE_MODULES_PATH/mod_security2.so")){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache $APACHE_MODULES_PATH/mod_security2.so no such file\n";
				return false;
			}
			if(!is_file("$APACHE_MODULES_PATH/mod_unique_id.so")){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache $APACHE_MODULES_PATH/mod_unique_id.so no such file\n";
				return false;
			}
			
		}
		
		
		
		public function mod_security(){
			$this->LoadLibraries();
			$sock=$GLOBALS["CLASS_SOCKETS"];
			
			$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
			if($FreeWebsEnableModSecurity<>1){return null;}
			if(!isset($this->Params["mod_security"])){return null;}
			$q=new mysql();
			
			$Params=$this->Params["mod_security"];
			if($Params["SecFilterEngine"]<>1){$Params["SecFilterEngine"]="Off";}else{$Params["SecFilterEngine"]="On";}
			if($Params["SecFilterCheckURLEncoding"]<>1){$Params["SecFilterCheckURLEncoding"]="Off";}else{$Params["SecFilterCheckURLEncoding"]="On";}
			if($Params["SecFilterCheckUnicodeEncoding"]<>1){$Params["SecFilterCheckUnicodeEncoding"]="Off";}else{$Params["SecFilterCheckUnicodeEncoding"]="On";}
			if($Params["SecFilterScanPOST"]<>1){$Params["SecFilterScanPOST"]="Off";}else{$Params["SecFilterScanPOST"]="On";}
			if($Params["SecFilterDefaultAction"]==null){$Params["SecFilterDefaultAction"]="Hello World!";}
			if($Params["SecServerSignature"]==null){$Params["SecServerSignature"]=$sock->GET_INFO("SecServerSignature");}
			
			
			$f[]="<IfModule security2_module>";
			$f[]="   SecRuleEngine {$Params["SecFilterEngine"]}";
			//if($Params["SecServerSignature"]<>null){$f[]="   SecServerSignature \"{$Params["SecServerSignature"]}\"";}
			$f[]="   SecAuditEngine RelevantOnly";
			$f[]="   SecAuditLog /var/log/apache2/$this->servername/modsec_audit_log";
			$f[]="   SecDebugLog /var/log/apache2/$this->servername/modsec_debug_log";
			$f[]="   SecDebugLogLevel 1";
			$f[]="   SecRequestBodyAccess {$Params["SecFilterScanPOST"]}";
			$f[]="   SecDefaultAction \"phase:2,deny,log,status:500\"";
			@mkdir("/var/log/apache2/$this->servername",0755,true);
			$sql="SELECT * FROM freewebs_secfilters WHERE servername='$this->servername' $qsearch ORDER BY ID DESC";
			$results=$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){echo "$q->mysql_error\n";}
	
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
					$ligne["value"]=str_replace("+","\+",$ligne["value"]);
					$ligne["value"]=str_replace(".","\.",$ligne["value"]);
					$ligne["value"]=str_replace("?","\?",$ligne["value"]);
					$ligne["value"]=str_replace(":","\:",$ligne["value"]);
					$ligne["value"]=str_replace("(","\(",$ligne["value"]);
					$ligne["value"]=str_replace(")","\)",$ligne["value"]);
					$ligne["value"]=str_replace("[","\[",$ligne["value"]);
					$ligne["value"]=str_replace("]","\]",$ligne["value"]);
					$ligne["value"]=str_replace("*",".*?",$ligne["value"]);				
				if($ligne["key"]==null){

					$f[]="   SecRule REQUEST_LINE   \"{$ligne["value"]}\" \"deny,log,status:403,msg:'Rule: {$ligne["ID"]}:severity %{HIGHEST_SEVERITY}'\"";
				}else{
					if(preg_match("#(.+?)\s+([deny|pass|log|,]+)#",$ligne["value"],$re)){
						$pattern=$re[1];
						$action=$re[2];
						$f[]="SecRule {$ligne["key"]}   \"$pattern\" \"$action,status:403,msg:'Rule: {$ligne["ID"]}:severity %{HIGHEST_SEVERITY}'\"";
					}
					
					
				}
				   
				
				
			}
			
			$f[]="</IfModule>";
			$f[]="";			
			return @implode("\n",$f);
			
		}
		
		
		
		
		public function CheckWorkingDirectory(){
			if($this->AS_ROOT){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] DEBUG: Working dir ? type=$this->groupware, WebCopy=$this->WebCopyID (L.".__LINE__.")\n";}
			} 
			if($this->IsGroupWareFromArtica()){
				$this->CheckDirectoryRights();
				return;
			}
			
			if($this->groupware=="KLMS"){
				$this->WORKING_DIRECTORY="/opt/kaspersky/klmsui/share/htdocs";
				return;
			}
			
			if($this->groupware=="CHILLI"){
				$this->WORKING_DIRECTORY="/var/www/coova_json";
				$this->InstallChilli();
				return;
			}
			
			if($this->groupware=="WPAD"){
				$this->WORKING_DIRECTORY="/usr/share/proxy.pac";
				return;
			}			
			
			
			if($this->groupware=="ZARAFA"){
				$this->WORKING_DIRECTORY="/var/www/$this->servername";
				$this->InstallZarafa();
				$this->CheckDirectoryRights();
				return;
			}
			
			if($this->groupware=="SARG"){
				$sock=new sockets();
				$SargOutputDir=$sock->GET_INFO("SargOutputDir");
				if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
				$this->WORKING_DIRECTORY=$SargOutputDir;
				$this->CheckDirectoryRights();
				return;
			}			
			
			if($this->groupware=="WEBAPP"){
				$this->WORKING_DIRECTORY="/var/www/$this->servername";
				$this->InstallZarafaWebAPP();
				$this->CheckDirectoryRights();
				return;
			}			
			
			if($this->groupware=="ZARAFA_MOBILE"){
				$this->WORKING_DIRECTORY="/var/www/$this->servername";
				$this->InstallZarafaMobile();
				$this->CheckDirectoryRights();
				return;
			}			
			
			if($this->groupware=="Z-PUSH"){
				$this->WORKING_DIRECTORY="/var/www/$this->servername";
				@mkdir("$this->WORKING_DIRECTORY/state",0777,true);
				@chmod("$this->WORKING_DIRECTORY/state",0777);
				@mkdir("$this->WORKING_DIRECTORY/log",0777,true);
				@chmod("$this->WORKING_DIRECTORY/log",0777);
				$this->InstallZarafaZpush();
				return;
			}
			
			if($this->groupware=="DOTCLEAR"){
				if($this->AS_ROOT){
					$unix=new unix();
					shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.freeweb.php --install-groupware $this->servername >/dev/null 2>&1 &");
					$this->CheckDirectoryRights();
				}
				return;
			}	

				
			if($this->groupware=="POWERADMIN"){
				$this->WORKING_DIRECTORY="/usr/share/poweradmin";
				return;
			}
			if($this->groupware=="ARKEIA"){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] DEBUG: Working dir=/opt/arkeia/wui/htdocs (L.".__LINE__.")\n";} 
				$this->WORKING_DIRECTORY="/opt/arkeia/wui/htdocs";
				$this->CheckDirectoryRights();
				return;
			}
			
			if($this->groupware=="OWNCLOUD"){
				if($this->AS_ROOT){
					$unix=new unix();
					shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.freeweb.php --install-groupware $this->servername >/dev/null 2>&1 &");
					$this->CheckDirectoryRights();
				}
				return;
			}
			
			if($this->groupware=="WORDPRESS"){
				if(trim($this->www_dir)<>null){
					echo "$this->PrefixOutput [CLASS/".__LINE__."] Directory: $this->www_dir\n";
					$this->WORKING_DIRECTORY= $this->www_dir;
					return;
				}
				
			}			
			
			
			if($this->servername=="_default_"){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] DEBUG: is a default website (L.".__LINE__.")\n";} 
				if(trim($this->www_dir)<>null){$this->WORKING_DIRECTORY=trim($this->www_dir);return;}
				$this->WORKING_DIRECTORY="/var/www/html";return;
			}
			
			if($this->CheckWorkingDirectoryLoop()){return;}
			if($this->CheckWorkingDirectoryLVM()){return;}
			
			
			if($this->WebCopyID>0){
				$sql="SELECT sitename,workingdir FROM httrack_sites WHERE ID=$this->WebCopyID";
				$q=new mysql();
				$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				$urlEXt=parse_url($ligne["sitename"]);
				$this->WORKING_DIRECTORY="{$ligne["workingdir"]}/{$urlEXt["host"]}";
				return;
			}

			
			if(trim($this->www_dir)<>null){
				$this->WORKING_DIRECTORY="$this->www_dir";
				if(!is_dir($this->www_dir)){
					if(strpos($this->WORKING_DIRECTORY,$this->servername)==0){
						$this->WORKING_DIRECTORY="$this->WORKING_DIRECTORY/$this->servername";
					}
				}
				$this->CheckDirectoryRights();
				return;
			}
			
			$this->WORKING_DIRECTORY="/var/www/$this->servername";
			$this->CheckDirectoryRights();
			
		}
		
		private function CheckWorkingDirectoryLoop(){
			if($this->UseLoopDisk==0){return false;}
			if(!is_dir($this->LoopMounts)){
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] $this->LoopMounts no such directory\n";}
				return false;
			}
			
			$this->WORKING_DIRECTORY=$this->LoopMounts;
			$this->CheckDirectoryRights();
		}
		
		
		private function CheckWorkingDirectoryLVM(){
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
			if($vgservices["freewebs"]==null){return false;}
			if($this->lvm_size==0){return false;}
			if($this->lvm_vg==null){return false;}
			if($this->AS_ROOT){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] stored on an LVM group $this->lvm_vg partition ({$this->lvm_size}MB)\n";
				$lvm=new lvm_root();
				if(!$lvm->CreateLVS($this->lvm_vg,$this->servername,$this->lvm_size,"$this->lvm_vg-$this->servername")){return false;}
				echo "$this->PrefixOutput [CLASS/".__LINE__."] $this->lvm_vg/$this->lvm_vg-$this->servername success\n";
			}
			$this->WORKING_DIRECTORY="/automounts/$this->lvm_vg-$this->servername";
			if($this->AS_ROOT){
				
				if($GLOBALS["VERBOSE"]){
					exec("/bin/ls -lah $this->WORKING_DIRECTORY 2>&1",$results);
					while (list ($num, $line) = each ($results)){echo "CheckWorkingDirectoryLVM() -> $line\n";}
				}
				$this->CheckDirectoryRights();
			}
			return true;
			
			
		}
		
		public function IsGroupWareFromArtica(){
			if($this->groupware=="ARTICA_USR"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix/user-backup";return true;}
			if($this->groupware=="ARTICA_ADM"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ARTICA_REGISTER_OU"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ARTICA_MINIADM"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ARTICA_ADMPRXY"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="XAPIAN"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ANDROWEB"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="MAILHOSTING"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="SPLASHSQUID"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ARTICA_PRXYLOGS"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="ERRSQUID"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="UFDBGUARD"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			if($this->groupware=="WPADDYN"){$this->WORKING_DIRECTORY="/usr/share/artica-postfix";return true;}
			
			
			
			if($this->groupware=="UPDATEUTILITY"){
				$sock=new sockets();
				$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
				if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
				$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
				if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
				if($UpdateUtilityUseLoop==1){$UpdateUtilityStorePath="/automounts/UpdateUtility";}
				$this->WORKING_DIRECTORY="$UpdateUtilityStorePath/database/Updates";return false;}
			
			
			
			return false;
			
		}
		
		
		private function CheckDirectoryRights(){
			if(!$this->AS_ROOT){return;}
			$unix=new unix();
			$this->WORKING_DIRECTORY=str_replace("//","/",$this->WORKING_DIRECTORY);
			echo "$this->PrefixOutput [CLASS/".__LINE__."] Working directory on $this->WORKING_DIRECTORY\n";
			if(!is_dir($this->WORKING_DIRECTORY)){@mkdir($this->WORKING_DIRECTORY,0666,true);}
			if(!is_dir($this->WORKING_DIRECTORY)){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] working directory failed !!!\n";
				return;
			}
			
			
			if(!is_dir("/var/log/apache2/$this->servername")){
				@mkdir("/var/log/apache2/$this->servername",0755,true);

			
			}
			
			
			
			
			if($this->groupware=="ARKEIA"){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] DEBUG: Check dir=/opt/arkeia/wui/tmp/sessions (L.".__LINE__.")\n";} 
				$this->WORKING_DIRECTORY="/opt/arkeia/wui/htdocs";
				@mkdir("/opt/arkeia/wui/tmp/sessions",0755,true);
				$unix->chown_func($this->APACHE_RUN_USER, $this->APACHE_RUN_GROUP,"/opt/arkeia/wui/tmp/*");
				$unix->chown_func($this->APACHE_RUN_USER, $this->APACHE_RUN_GROUP,"/opt/arkeia/wui/tmp/sessions");
			}
			
			
			
			if(!is_dir("/var/log/apache2")){@mkdir("/var/log/apache2",0666,true);}
			$unix->chown_func($this->APACHE_RUN_USER, $this->APACHE_RUN_GROUP,"/var/log/apache2/*");
			
			
			
			
		}
		
		public function CheckDefaultPage(){
		if(!$this->AS_ROOT){return;}
		if($this->IsGroupWareFromArtica()){return;}
		if($this->Params["DisableIndexPHPPage"]==1){return;}
		if($this->groupware=="Z-PUSH"){return;}
		
		
		$wel[]="<?php";
		$wel[]="echo \"<H1>It Works!</H1>\";";
		$wel[]="while (list(\$index,\$line)=each(\$_SERVER)){";
		$wel[]="echo \"<H2 style='font-size:16px'>\$index:<span style='color:#d32d2d'>\$line</span></H2>\";";
		$wel[]=";}";
		$wel[]="?>";			
		$index_php=@implode("\n",$wel);	
		if($this->groupware=="cachemgr"){$index_php="<?php echo header('location:cgi-bin/cachemgr.cgi');die();?>";}
			
			if(!is_file("$this->WORKING_DIRECTORY/index.php")){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] CheckDefaultPage -> $this->WORKING_DIRECTORY/index.php (L.".__LINE__.")\n";
				@file_put_contents("$this->WORKING_DIRECTORY/index.php",$index_php);
			}			
			
		}
		
		public function ScriptAliases(){
			if($this->groupware=="cachemgr"){	
				@mkdir("$this->WORKING_DIRECTORY/cgi-bin",0755,true);
				shell_exec("/bin/cp -f $this->SQUID_CACHMGR $this->WORKING_DIRECTORY/cgi-bin/cachemgr.cgi");
				$f[]="<Directory \"$this->WORKING_DIRECTORY/cgi-bin\">";
				$f[]="\tOptions -Indexes FollowSymLinks";
				$f[]="\tAllowOverride AuthConfig FileInfo";
				$f[]="\tOrder allow,deny";
				$f[]="\tAllow from all";
				$f[]="</Directory>";
				$f[]="";
				$f[]="ScriptAlias /cgi-bin/cachemgr.cgi $this->WORKING_DIRECTORY/cgi-bin/cachemgr.cgi";
	        	$f[]="<location \"/cgi-bin/cachemgr.cgi\">";
				$f[]="\torder allow,deny";
				$f[]=$this->AllowFrom();
	        	$f[]="</location>"; 
	        	return @implode("\n",$f);
			}

			if($this->groupware=="KLMS"){
				$f[]="\tScriptAlias \"/cgi-bin/\" \"/opt/kaspersky/klmsui/share/htdocs/cgi-bin/\"";
				$f[]="\t<Location /cgi-bin/>";
				$f[]="\t\tOptions +ExecCGI";
				$f[]="\t\tSetHandler klwi-script";
				$f[]="\t</Location>";
				return @implode("\n",$f);
			}
			
			if($this->groupware=="MAILMAN"){
				$f[]="\tScriptAlias /cgi-bin/ \"/usr/lib/cgi-bin/mailman/\"";
	    		$f[]="\tScriptAlias /mailman/ \"/usr/lib/cgi-bin/mailman/\"";
	    		//$f[]="\tScriptAlias /cgi-bin/mailman/ \"/usr/lib/cgi-bin/mailman/\"";
	    		$f[]="\tAlias /pipermail \"/var/lib/mailman/archives/public\"";
	    		$f[]="\tAlias /icons \"/usr/share/images/mailman/\"";
	    		$f[]="\tAlias /images/mailman/ \"/usr/share/images/mailman/\"";
	    		$f[]="\tRedirectMatch permanent ^/$ /mailman/listinfo";
	    		return @implode("\n",$f);	
			}
			
			
		}
		
		public function JkMount(){
			if(!$this->APACHE_MOD_TOMCAT){return null;}
			if(!isset($this->Params["JkMount"])){return null;}
			if($this->Params["JkMount"]==0){return null;}
			$users=new usersMenus();
			if($users->OPENEMM_INSTALLED){
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$OpenEMMEnable=$sock->GET_INFO("OpenEMMEnable");
				if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
				if($OpenEMMEnable==1){return;}
			}	
			
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Adding tomcat connector\n";}
			$f[]="\t<IfModule mod_jk.c>";
			$f[]="\t\tJkMount / default";
			$f[]="\t\tJkMount /* default";
			$f[]="\t\tJkMount /*.jsp ajp13_worker";
			$f[]="\t\tJkMount /*.jsp ajp13_worker";
			$f[]="\t\tJkMount /*.cfm ajp13_worker";
			$f[]="\t\tJkMount /*.cfc ajp13_worker";
			$f[]="\t\tJkMount /*.do ajp13_worker";
			$f[]="\t\tJkMount /*.jsp ajp13_worker";
			$f[]="\t\tJkMount /*.cfchart ajp13_worker";
			$f[]="\t\tJkMount /*.cfres ajp13_worker";
   			$f[]="\t\tJkLogFile /var/log/apache2/mod_jk.log";
			$f[]="\t</IfModule>";
			return @implode("\n", $f);
		}
		
		
		public function AllowFrom(){
			
			if(!isset($this->Params["LimitByIp"]["enabled"])){$this->Params["LimitByIp"]["enabled"]=0;}
			$fc=array();
			if($GLOBALS["VERBOSE"]){echo "DEBUG::AllowFrom:: LimitByIp:{$this->Params["LimitByIp"]["enabled"]}\n";}
			if($this->LimitByIp==1){
				$hash=$this->LimitByIp_list();
				if($GLOBALS["VERBOSE"]){echo "DEBUG::AllowFrom:: ".count($hash)." items\n";}
				while (list ($num, $ligne) = each ($hash) ){
					if($ligne==null){continue;}
					if(is_array($ligne)){continue;}
					if($GLOBALS["VERBOSE"]){echo "DEBUG::AllowFrom:: allow from $ligne\n";}
					$fc[]="\t\tallow from $ligne";
				}
			}
			
			
			if($GLOBALS["VERBOSE"]){echo "DEBUG::AllowFrom::  ".count($fc)." items\n";}
			if(count($fc)==0){return "\t\tallow from all";}else{ return @implode("\n",$fc)."\n";}
			return @implode("\n",$fc)."\n";
		}
		
		public function WebdavHeader(){
				if($this->groupware=="WEBDAVFREE"){$this->Params["WEBDAV"]["ENABLED"]=1;}
				if(!isset($this->Params["WEBDAV"]["ENABLED"])){$this->Params["WEBDAV"]["ENABLED"]=0;}
				
				
				if($this->Params["WEBDAV"]["ENABLED"]==0){return null;}
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache WebDav Engine enabled, DavLock in $this->WORKING_DIRECTORY/DavLock\n";
				$conf[]="\tDavLockDB \"$this->WORKING_DIRECTORY/DavLock\"";
				$conf[]=$this->WebDavBrowserMatches();
				return @implode("\n",$conf);				
			
		}
		
		
		
		
		public function open_basedir(){
				
			
				$f[]=$this->WORKING_DIRECTORY;
				$f[]="/var/log/apache2";
				$f[]="/var/log/apache2/$this->servername";
				$f[]="/home/artica";
				$f[]="/var/log";
				$f[]="/var/run/mysqld";
				$f[]="/tmp";
				
				if($this->AS_ROOT){
					@chmod("/var/log/apache2/$this->servername", 0777);
				}
			
				if($this->groupware=="KLMS"){return null;}
				if($this->groupware=="CHILLI"){return null;}
				
				if($this->groupware=="WPADDYN"){
					$f[]="/usr/share/artica-postfix";
				}
				
			
				if($this->groupware=="GROUPOFFICE"){
					$f[]="/home/$this->servername";
					$f[]="/home/$this->servername/cache";
				}
				if($this->groupware=="ARKEIA"){
					$f[]="/opt/arkeia";
					$f[]="/opt/arkeia/wui/lib";
					$f[]="/opt/arkeia/wui/lib/Zend";
				}
				
				if($this->groupware=="DOTCLEAR"){
					$f[]="/home/$this->servername/cache";
					$f[]="/home/$this->servername/cache/cbtpl";
				}

				if($this->groupware=="APP_FILEZ_WEB"){
					$f[]="/home/fz_uploads/$this->servername";
					
				}
				
				$f[]="/usr/share/php";
				$f[]="/usr/share/php5";
				$f[]="/var/lighttpd/upload";
				$f[]="/var/lib/php5";
				
				
			
			if($this->IsGroupWareFromArtica()){
				$f[]="/usr/share/artica-postfix/ressources";
				$f[]="/usr/share/artica-postfix/framework";
				$f[]="/usr/share/artica-postfix/user-backup";
				$f[]="/usr/share/artica-postfix/user-backup/ressources";
				$f[]="/etc/ssl/certs/mysql-client-download";
				$f[]="/usr/share/artica-postfix/LocalDatabases";
				$f[]="/var/run";
				$f[]="/bin";
				$f[]="/usr/sbin";
				$f[]="/etc/artica-postfix/settings";
			}
			
			if(($this->groupware=="ZARAFA") OR ($this->groupware=="ZARAFA_MOBILE") OR ($this->groupware=="Z-PUSH") OR ($this->groupware=="WEBAPP")){
				$f[]="/usr/share/php/mapi";
				$f[]="/usr/share/php5/mapi";
				$f[]="$this->WORKING_DIRECTORY/plugins";
				$f[]="$this->WORKING_DIRECTORY/tmp";
				if($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]==1){$f[]="$this->WORKING_DIRECTORY/mobile";}
			}
			
		if($this->groupware=="OWNCLOUD"){	
			$homeDirectory="/home/owncloud/$this->servername";
			if($this->uid<>null){
				$ct=new user($this->uid);
				$homeDirectory=$ct->homeDirectory;
				if($homeDirectory==null){$homeDirectory="/home/$this->uid";}
			}
			
			$f[]="$homeDirectory/owncloud_data";
			$f[]="$homeDirectory/apps";
		}
	
			
			
			
			$sql="SELECT alias,directory FROM freewebs_aliases WHERE servername='$this->servername' ORDER BY directory";
			$WorkingDirRegex=string_to_regex($this->WORKING_DIRECTORY);
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(preg_match("#^REGEX:(.+)#",$ligne["alias"])){continue;}
				if(!preg_match("#$WorkingDirRegex#", $ligne["directory"])){
					if(is_file($ligne["directory"])){$ligne["directory"]=dirname($ligne["directory"]);}
					$f[]=$ligne["directory"];
				}
			}
			
			$sql="SELECT directory FROM freewebs_openbasedir WHERE servername='$this->servername' ORDER BY directory";
			$WorkingDirRegex=string_to_regex($this->WORKING_DIRECTORY);
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(is_file($ligne["directory"])){$ligne["directory"]=dirname($ligne["directory"]);}
				$f[]=$ligne["directory"];
			
			}			
			
			
			
			return @implode(":",$f);
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$unix=new unix();
			$GLOBALS["MAX_RECUSIVE_DIRS"]=40;
			$GLOBALS["RECURSVIVE_INC"]=0;
			$dirs=$unix->getDirectories($this->WORKING_DIRECTORY);
			while (list ($num, $ligne) = each ($dirs) ){$f[]=$ligne;}
			
			return @implode(":",$f);
		}
		
		public function Aliases(){
			$f=array();
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] checking aliases..\n";}
			$already=array();

			if($this->groupware=="WPAD"){
				$f[]="\tAlias /wpad.dat /proxy.pac";
				$already["/wpad.dat"]=true;
			}
			
			if(($this->groupware=="ZARAFA") OR ($this->groupware=="WEBAPP") ) {
				if($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]==1){
					$f[]="\tAlias /Microsoft-Server-ActiveSync $this->WORKING_DIRECTORY/mobile/index.php";
					$f[]="\tAlias /AutoDiscover/AutoDiscover.xml \"$this->WORKING_DIRECTORY/Autodiscover/Autodiscover.php\"";
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias /Microsoft-Server-ActiveSync $this->WORKING_DIRECTORY/mobile/index.php\n";}
					$already["/Microsoft-Server-ActiveSync"]=true;
					$already["/AutoDiscover/AutoDiscover.xml"]=true;
				}else{
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] zPushInside is FALSE\n";}
				}
			}
			
			
			if($this->groupware=="Z-PUSH"){
				$f[]="\tAlias /Microsoft-Server-ActiveSync $this->WORKING_DIRECTORY/index.php";
				$f[]="\tAlias /AutoDiscover/AutoDiscover.xml \"$this->WORKING_DIRECTORY/Autodiscover/Autodiscover.php\"";
				$already["/Microsoft-Server-ActiveSync"]=true;
				$already["/AutoDiscover/AutoDiscover.xml"]=true;
			}
			
			$sql="SELECT * FROM freewebs_aliases WHERE servername='$this->servername' ORDER BY alias";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] mysql error failed $q->mysql_error\n";}
			}
			
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] SQL Query return ". mysql_num_rows($results). "rows\n";}
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$Alias=trim($ligne["alias"]);
				if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] check Alias:`$Alias` -> \"{$ligne["directory"]}\"\n";}
				$directory=trim($ligne["directory"]);
				if($Alias==null){continue;}
				$regex=false;
				if(preg_match("#^REGEX:(.+)#", $Alias,$re)){
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias:`$Alias` -> REGEX\n";}
					$Alias=$re[1];$regex=true;}
				
				if(substr($Alias, 0,1)<>'/'){$Alias="/$Alias";}
				if(substr($directory, 0,1)<>'/'){$directory="/$directory";}
				if(isset($already[$Alias])){
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias:`$Alias` -> Already set, skip it\n";}
					continue;
				}
				$noprefix=false;
				if(preg_match("#^\/var\/www\/#", $directory)){$noprefix=true;}
				if(preg_match("#^\/usr\/share\/#", $directory)){$noprefix=true;}
				if(!$noprefix){
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias:`$Alias` -> Must be prefixed...\n";}
					$directory=$this->WORKING_DIRECTORY.$directory;
				}
				if(strtolower($Alias)==strtolower("/Microsoft-Server-ActiveSync")){
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias:`$Alias` -> is an ActiveSync...\n";}
					if(!preg_match("#\/index\.php$#", $directory)){$directory=$directory."/index.php";}}
				
				$already[$Alias]=true;
				if(!$regex){
					$f[]="\tAlias $Alias $directory";
					if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias: result:`Alias $Alias $directory`\n";}
					}else{
						if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias: result:`AliasMatch $Alias $directory`\n";}
						$f[]="\tAliasMatch $Alias $directory";
					}
			}
			if($GLOBALS["VERBOSE"]){echo "$this->PrefixOutput [CLASS/".__LINE__."] Alias: return ". count($f)." items\n";}
			if(count($f)>0){return @implode("\n",$f);}
			
		}
		
		private function UserInSystem($uid){
			if(!isset($GLOBALS["UserInSystem"][$uid])){
				$unix=new unix();
				$id=$unix->find_program("id");
				exec("$id \"$uid\" 2>&1",$results);
				writelogs("$id \"$uid\" 2>&1 ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
				$datas=trim(@implode("", $results));
				if(!preg_match("#uid=([0-9]+).+?#", $datas)){
					$GLOBALS["UserInSystem"][$uid]=false;
					return false;}
				$GLOBALS["UserInSystem"][$uid]=true;
				return true;
			}else{
				return $GLOBALS["UserInSystem"][$uid];
			}
		}
		
		
		private function chown_itk($directory){
			if($directory=="/usr/share/artica-postfix"){return;}
			$users=new usersMenus();
			if(!$users->MPM_ITK_MODULE){return;}
			if($this->uid==null){return;}
			if(!is_numeric($this->gpid)){return;}
			if($this->gpid==0){return;}	
			$gp=new groups($this->gpid);
			echo "$this->PrefixOutput [CLASS/".__LINE__."] chown {$this->uid}:{$gp->groupName} on \"$directory\"\n"; 			
			$unix=new unix();
			$chown=$unix->find_program("chown");
			shell_exec("$chown  {$this->uid}:{$gp->groupName} \"$directory\"");
			shell_exec("$chown -R {$this->uid}:{$gp->groupName} \"$directory\" &");		
			return true;		
		}
		
		
		public function chown($directory){
			if(!is_dir($directory)){echo "$this->PrefixOutput [CLASS/".__LINE__."] chown \"$directory\" no such directory\n";return;}
			if($directory=="/usr/share/artica-postfix"){return;}
			
			if($this->chown_itk($directory)){return;}
			if(!isset($GLOBALS["CHOWNCOMMANDSFREEWBS"])){
				$apacheusername=$this->APACHE_RUN_USER;
				$apachegroup=$this->APACHE_RUN_GROUP;
				
				
				if($this->Params["MOD_FCGID"]["enabled"]==1){
					if($this->uid<>null){
						if($this->UserInSystem($this->uid)){
							$user=new user($this->uid);
							$apacheusername=$user->uidNumber;
							$apachegroup=$user->group_id;
						}
					}
				}
				
				$unix=new unix();
				$chown=$unix->find_program("chown");
				$GLOBALS["CHOWNCOMMANDSFREEWBS"]["CHOWN"]="$chown";
				$GLOBALS["CHOWNCOMMANDSFREEWBS"]["USER"]="$apacheusername";
				$GLOBALS["CHOWNCOMMANDSFREEWBS"]["GROUP"]="$apachegroup";
			}

			if($GLOBALS["CHOWNCOMMANDSFREEWBS"]["USER"]==null){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] chown \"$directory\" no such user\n";
				return;
			}
			
			echo "$this->PrefixOutput [CLASS/".__LINE__."] set permissions for {$GLOBALS["CHOWNCOMMANDSFREEWBS"]["USER"]} on \"$directory\"\n"; 
			shell_exec("{$GLOBALS["CHOWNCOMMANDSFREEWBS"]["CHOWN"]}  {$GLOBALS["CHOWNCOMMANDSFREEWBS"]["USER"]}:{$GLOBALS["CHOWNCOMMANDSFREEWBS"]["GROUP"]} \"$directory\"");
			shell_exec("{$GLOBALS["CHOWNCOMMANDSFREEWBS"]["CHOWN"]} -R {$GLOBALS["CHOWNCOMMANDSFREEWBS"]["USER"]}:{$GLOBALS["CHOWNCOMMANDSFREEWBS"]["GROUP"]} \"$directory\" &");	
			
			
		}
		
		public function mod_fcgid(){
				$apacheusername=$this->APACHE_RUN_USER;
				$apachegroup=$this->APACHE_RUN_GROUP;			
			
				if($this->Params["MOD_FCGID"]["enabled"]==0){return null;}
				$unix=new unix();
				$target_fcgi_path="/var/www/php-fcgi-scripts/$this->servername/php-fcgi-starter";
				$php_ini_path="/var/www/php-fcgi-scripts/$this->servername/php.ini";
				$fa[]="\t<IfModule mod_fcgid.c>";
				if($this->uid<>null){
					if($this->UserInSystem($this->uid)){
						$apacheusername=$this->uid;
						$apachegroup=$this->uid;
						$user=$this->uid;$group=$this->uid;
						$fa[]="\t\tSuexecUserGroup $user $group";
					}
				}
				
				$fa[]="\t\tFcgidBusyTimeout {$this->Params["MOD_FCGID"]["FcgidBusyTimeout"]}";
				$fa[]="\t\tFcgidIdleTimeout {$this->Params["MOD_FCGID"]["FcgidIdleTimeout"]}";
				$fa[]="\t\tFcgidMaxRequestInMem {$this->Params["MOD_FCGID"]["FcgidMaxRequestInMem"]}";
				$fa[]="\t\tFcgidMaxProcessesPerClass {$this->Params["MOD_FCGID"]["FcgidMaxProcessesPerClass"]}";
				$fa[]="\t\tFcgidMaxRequestLen {$this->Params["MOD_FCGID"]["FcgidMaxRequestLen"]}";
				$fa[]="\t\tFcgidOutputBufferSize {$this->Params["MOD_FCGID"]["FcgidOutputBufferSize"]}";
				
				$fa[]="\t\t<IfModule mod_php5.c>";
				$fa[]="\t\t\tphp_admin_flag engine off";
				$fa[]="\t\t</IfModule>";

	
				$fa[]="\t\t\tAddHandler fcgid-script .fcgi .php .phtml .php3 .php4 .php5 .php6";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .php";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .phtml";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .php3";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .php4";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .php5";
				$fa[]="\t\t\tFCGIWrapper $target_fcgi_path .php6";

				$phpfiles[]="php";
				$phpfiles[]="phtml";
				$phpfiles[]="inc";
				$phpfiles[]="php3";
				$phpfiles[]="php4";
				$phpfiles[]="php5";
				$phpfiles[]="php6";
				
				
				while (list ($num, $ext) = each ($phpfiles) ){				
						$fa[]="\t\t<Files *.$ext>";
						$fa[]="\t\t\tSetHandler fcgid-script";
						$fa[]="\t\t</Files>";
				}			
				
				$fa[]="\t</IfModule>";
				
				if(!is_dir("/var/www/php-fcgi-scripts/$this->servername")){
					@mkdir("/var/www/php-fcgi-scripts/$this->servername",0755,true);
				}
				
				
				
				
				
				$phpini=$this->build_php_ini();
				@file_put_contents($php_ini_path, $phpini);
				
				$f[]="#!/bin/sh";
				$f[]="PHPRC=$php_ini_path";
				$f[]="export PHPRC";
				$f[]="export PHP_FCGI_MAX_REQUESTS={$this->Params["MOD_FCGID"]["PHP_FCGI_MAX_REQUESTS"]}";
				$f[]="export PHP_FCGI_CHILDREN={$this->Params["MOD_FCGID"]["PHP_FCGI_CHILDREN"]}";
				$f[]="exec $this->phpcgi\n";
				@file_put_contents($target_fcgi_path, @implode("\n", $f));
				$chmod=$unix->find_program("chmod");
				$chown=$unix->find_program("chown");
				shell_exec("$chown -R $apacheusername /var/www/php-fcgi-scripts/$this->servername >/dev/null");
				shell_exec("$chmod -R 755 /var/www/php-fcgi-scripts >/dev/null");
				return @implode("\n", $fa);
			
			
		}
		
		public function php_values($nginx=false){
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			if($this->WebCopyID>0){return null;}
			if($this->groupware=="KLMS"){return null;}
			if($this->groupware=="CHILLI"){return null;}
			
			$php_open_base_dir=$this->open_basedir();
			$includes[]=".";
			$includes[]="/usr/share/php";
			$includes[]="$this->WORKING_DIRECTORY";
			$includes[]="/usr/share/php5";
			$includes[]="/usr/local/share/php";
			$includes[]="/usr/share/php5/PEAR";
			$includes[]="/usr/share/pear";
			
			$includes[]="/tmp";
			
			if($this->groupware=="DOTCLEAR"){
				$includes[]="$this->WORKING_DIRECTORY/cache";
				$includes[]="$this->WORKING_DIRECTORY/plugins";
			}
			if(($this->groupware=="ZARAFA") OR ($this->groupware=="ZARAFA_MOBILE") OR ($this->groupware=="WEBAPP")){
				if($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]==1){
					$includes[]="$this->WORKING_DIRECTORY/mobile";
					$includes[]="$this->WORKING_DIRECTORY/server/PEAR";
				}
			}
			
			if($this->groupware=="OWNCLOUD"){
				$includes[]="$this->WORKING_DIRECTORY/cache";
			}
			
			
			
			$sql="SELECT alias,directory FROM freewebs_aliases WHERE servername='$this->servername' ORDER BY directory";
			$WorkingDirRegex=string_to_regex($this->WORKING_DIRECTORY);
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(preg_match("#^REGEX:(.+)#",$ligne["alias"])){continue;}
				if(!preg_match("#$WorkingDirRegex#", $ligne["directory"])){
					if(is_file($ligne["directory"])){$ligne["directory"]=dirname($ligne["directory"]);}
					$includes[]=$ligne["directory"];
				}
			}
			
			$sql="SELECT directory FROM freewebs_openbasedir WHERE servername='$this->servername' ORDER BY directory";
			$WorkingDirRegex=string_to_regex($this->WORKING_DIRECTORY);
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,'artica_backup');
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					if(is_file($ligne["directory"])){$ligne["directory"]=dirname($ligne["directory"]);}
					$includes[]=$ligne["directory"];
				
			}			

			
			
			
			$this->Params["PHP_VALUES"]["include_path"]=@implode(":", $includes);
			$this->Params["PHP_VALUES"]["error_log"]="/var/log/apache2/$this->servername/php.log";
			$this->Params["PHP_VALUES"]["log_errors"]="On";
			
	
			

			if($php_open_base_dir<>null){$this->Params["PHP_VALUES"]["open_basedir"]=$php_open_base_dir;}
			
			if($this->mysql_instance_id>0){
				$q=new mysql_multi($this->mysql_instance_id);
				if(is_numeric($q->listen_port)){$this->Params["PHP_VALUES"]["mysql.default_port"]="$q->listen_port";}
				if($q->listen_addr<>null){$this->Params["PHP_VALUES"]["mysql.default_host "]="$q->listen_addr";}
				if($q->SocketPath<>null){$this->Params["PHP_VALUES"]["mysql.default_socket"]=$q->SocketPath;}
				if($q->mysql_admin<>null){$this->Params["PHP_VALUES"]["mysql.default_user"]=$q->mysql_admin;}
				if($q->mysql_password<>null){$this->Params["PHP_VALUES"]["mysql.default_password"]=$q->mysql_password;}							
			}
			

			if(($this->groupware=="ZARAFA") OR ($this->groupware=="ZARAFA_MOBILE") OR ($this->groupware=="WEBAPP")){
				$ZarafaSessionTime=$sock->GET_INFO("ZarafaSessionTime");
				if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
				if(!isset($this->Params["ZARAFAWEB_PARAMS"])){$this->Params["ZARAFAWEB_PARAMS"]=array();}
				if(!isset($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"])){$this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]=0;}
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
				if(!isset($PARAMS["upload_max_filesize"])){$PARAMS["upload_max_filesize"]=50;}
				if(!isset($PARAMS["post_max_size"])){$PARAMS["post_max_size"]=50;}
				$post_max_size=$PARAMS["post_max_size"];
				$upload_max_filesize=$PARAMS["upload_max_filesize"];
				if(!is_numeric($post_max_size)){$post_max_size=50;}
				if(!is_numeric($upload_max_filesize)){$upload_max_filesize=50;}								
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="0";
				$this->Params["PHP_VALUES"]["register_globals"]="0";
				$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]=$ZarafaSessionTime;
				$this->Params["PHP_VALUES"]["post_max_size"]="{$post_max_size}M";
				$this->Params["PHP_VALUES"]["upload_max_filesize"]="{$upload_max_filesize}M";
				$this->Params["PHP_VALUES"]["magic_quotes_runtime"]="0";
				$this->Params["PHP_VALUES"]["short_open_tag"]="1";
				$this->Params["PHP_VALUES"]["safe_mode"]="0";
			}
			
			if($this->groupware=="WEBAPP"){
				$ZarafaSessionTime=$sock->GET_INFO("ZarafaSessionTime");
				if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
				if(!isset($this->Params["ZARAFAWEB_PARAMS"])){$this->Params["ZARAFAWEB_PARAMS"]=array();}
				if(!isset($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"])){$this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]=0;}
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
				if(!isset($PARAMS["upload_max_filesize"])){$PARAMS["upload_max_filesize"]=50;}
				if(!isset($PARAMS["post_max_size"])){$PARAMS["post_max_size"]=50;}
				$post_max_size=$PARAMS["post_max_size"];
				$upload_max_filesize=$PARAMS["upload_max_filesize"];
				if(!is_numeric($post_max_size)){$post_max_size=50;}
				if(!is_numeric($upload_max_filesize)){$upload_max_filesize=50;}								
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="0";
				$this->Params["PHP_VALUES"]["register_globals"]="0";
				$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]=$ZarafaSessionTime;
				$this->Params["PHP_VALUES"]["post_max_size"]="{$post_max_size}M";
				$this->Params["PHP_VALUES"]["upload_max_filesize"]="{$upload_max_filesize}M";
				$this->Params["PHP_VALUES"]["magic_quotes_runtime"]="0";
				$this->Params["PHP_VALUES"]["short_open_tag"]="1";
				$this->Params["PHP_VALUES"]["safe_mode"]="0";
						
			}
			
			if($this->groupware=="APP_FILEZ_WEB"){
				$params=$this->Params["FILEZ"];
				if(!is_numeric($params["post_max_size"])){$params["post_max_size"]=750;}
				if(!is_numeric($params["upload_max_filesize"])){$params["upload_max_filesize"]=750;}
				$this->Params["PHP_VALUES"]["post_max_size"]="{$params["post_max_size"]}M";
				$this->Params["PHP_VALUES"]["upload_max_filesize"]="{$params["upload_max_filesize"]}M";
				$this->Params["PHP_VALUES"]["file_uploads"]="On";
				$this->Params["PHP_VALUES"]["max_input_time"]="1200";
				$this->Params["PHP_VALUES"]["max_execution_time"]="1200";				
			}
			
			
			if($this->groupware=="Z-PUSH"){
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$ZarafaSessionTime=$sock->GET_INFO("ZarafaSessionTime");
				if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}				
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="0";
				$this->Params["PHP_VALUES"]["register_globals"]="0";
				$this->Params["PHP_VALUES"]["magic_quotes_runtime"]="0";
				$this->Params["PHP_VALUES"]["short_open_tag"]="1";
				$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]=$ZarafaSessionTime;	
			}
			
			if($this->groupware=="DRUPAL"){
				$this->Params["PHP_VALUES"]["session.auto_start"]="0";
				$this->Params["PHP_VALUES"]["xdebug.show_exception_trace"]="0";
				$this->Params["PHP_VALUES"]["session.cache_limiter"]="nocache";
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="0";
				$this->Params["PHP_VALUES"]["allow_url_fopen"]="1";

			}
			
			if($this->groupware=="ARKEIA"){
				$this->Params["PHP_VALUES"]["extension"]="arkphp.so";
				$this->Params["PHP_VALUES"]["date.timezone"]="UTC";
				$this->Params["PHP_VALUES"]["session.save_path"]="/var/lib/php5";
				
			}
			if($this->groupware=="SUGAR"){
				$this->Params["PHP_VALUES"]["memory_limit"]="80M";
				$this->Params["PHP_VALUES"]["post_max_size"]="75M";
				$this->Params["PHP_VALUES"]["upload_max_filesize"]="75M";
				$this->Params["PHP_VALUES"]["max_execution_time"]="601";
				$this->Params["PHP_VALUES"]["timeout"]="601";
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="0";
				$this->Params["PHP_VALUES"]["session.auto_start"]="0";
				$this->Params["PHP_VALUES"]["mbstring.http_input"]="pass";
				$this->Params["PHP_VALUES"]["mbstring.http_output"]="pass";
				$this->Params["PHP_VALUES"]["mbstring.encoding_translation"]="0";
			
			}
			if($this->groupware=="XAPIAN"){
				$this->Params["PHP_VALUES"]["extension"]="xapian.so";
			}
			

			
			if($this->groupware=="GROUPOFFICE"){
				$this->Params["PHP_VALUES"]["output_buffering"]="Off";
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="Off";
				$this->Params["PHP_VALUES"]["session.cache_limiter"]="nocache";
				$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="Off";
				$this->Params["PHP_VALUES"]["allow_url_fopen"]="On";				
				$f[]="\tphp_admin_flag engine \"on\"";
        		$f[]="\tphp_admin_value open_basedir \"none\"";
        		$f[]="UseCanonicalName On";
				
			}
			
		if($this->groupware=="JOOMLA17"){
			$this->Params["PHP_VALUES"]["register_globals"]="0";
			$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="1";
			$this->Params["PHP_VALUES"]["upload_max_filesize"]="100M";
			$this->Params["PHP_VALUES"]["post_max_size"]="100M";
		}
		
		if($this->groupware=="DOTCLEAR"){
			$this->Params["PHP_VALUES"]["allow_call_time_pass_reference"] = "Off";
			$this->Params["PHP_VALUES"]["register_globals"] = "Off";
		}
		if($this->groupware=="LMB"){
			$this->Params["PHP_VALUES"]["error_reporting"]="0";
			$this->Params["PHP_VALUES"]["allow_url_fopen"]="On";
			$this->Params["PHP_VALUES"]["allow_url_include"]="On";
			$this->Params["PHP_VALUES"]["allow_url_ftp_get"]="On";
			$this->Params["PHP_VALUES"]["magic_quotes_gpc"]="Off";
		}
			
			
			if($this->groupware=="EYEOS"){
				$f[]="php_value error_reporting   \"E_ALL & ~E_NOTICE\"";
				$f[]="php_value display_errors   Off ";
				$f[]="php_value max_execution_time   30 ";
				$f[]="php_value max_input_time   60 ";
				$f[]="php_value memory_limit   128M ";
				$f[]="php_value post_max_size   200M ";
				$f[]="php_value upload_max_filesize   100M ";
				$f[]="php_value allow_url_fopen   On ";
				$f[]="php_value safe_mode   Off ";
				$f[]="php_value short_open_tag   On ";
				$f[]="php_value magic_quotes_runtime   Off ";
				$f[]="php_value file_uploads   On ";				
				
			}
			
		$sql="SELECT *  FROM freeweb_php WHERE servername='$this->servername'";
		$results=$q->QUERY_SQL($sql,'artica_backup');
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$this->Params["PHP_VALUES"][$ligne["key"]]=$ligne["value"];
		}
			
		if(is_array($this->Params["PHP_VALUES"])){
			$array=$this->Params["PHP_VALUES"];
			if($nginx){
				while (list ($key, $val) = each ($array) ){
					$cotes="\"";
					if(is_numeric($val)){$cotes=null;}
					$f[]="set \$php_value \"$key $val\";";
				}
				$line=@implode("\n", $f);
			//	$line=str_replace('"', "", $line);
				return $line;
			}
			
			
			while (list ($key, $val) = each ($array) ){
				$cotes="\"";
				if(is_numeric($val)){$cotes=null;}
				$f[]="\tphp_value $key {$cotes}$val{$cotes}";
			}
		
		}
			
		
			if($this->Params["MOD_FCGID"]["enabled"]==1){return null;}
			return @implode("\n",$f);
		}
		
		public function DirectoryIndex(){
			if($this->WebCopyID>0){return "index.html";}
			if($this->groupware=="WORDPRESS"){return "index.php";}
			if($this->groupware=="ARTICA_USR"){return "logon.php";}
			if($this->groupware=="ARTICA_ADM"){return "logon.php";}
			if($this->groupware=="XAPIAN"){return "xapian.index.php";}
			if($this->groupware=="ANDROWEB"){return "android.index.php";}
			if($this->groupware=="ARTICA_REGISTER_OU"){return "miniadm.register.php";}
			if($this->groupware=="ARTICA_MINIADM"){return "miniadm.php";}
			if($this->groupware=="WPADDYN"){return "proxy.pac.php";}
			
			if($this->groupware=="ARTICA_ADMPRXY"){return "squid.users.logon.php";}
			if($this->groupware=="MAILHOSTING"){return "mailattach.php";}
			if($this->groupware=="SPLASHSQUID"){return "splashproxy.php";}
			if($this->groupware=="ARTICA_PRXYLOGS"){return "prxy.monitor.php";}
			if($this->groupware=="ERRSQUID"){return "public.squid.error.php";}
			if($this->groupware=="UFDBGUARD"){return "ufdbguardd.php";}
			if($this->groupware=="WEBDAVFREE"){return "index.html";}
			if($this->groupware=="UPDATEUTILITY"){return "index.php";}
			if($this->groupware=="WPAD"){return "proxy.pac";}
			if($this->groupware=="CHILLI"){return "splash.php";}
			
			
			
			if($this->groupware=="KLMS"){if(is_file("/etc/apache2/conf.d/klmsui.conf")){@unlink("/etc/apache2/conf.d/klmsui.conf");}return null;}			
			return "index.php index.html";
			
		}
		
		
		public function SpecificsChowns(){
			
			$squidfiles["exec.logfile_daemon.php"]=true;
			$squidfiles["external_acl_squid_ldap.php"]=true;
			$squidfiles["external_acl_dynamic.php"]=true;
			$squidfiles["external_acl_quota.php"]=true;
			$squidfiles["external_acl_basic_auth.php"]=true;
			$squidfiles["external_acl_restrict_access.php"]=true;
			$squidfiles["external_acl_squid.php"]=true;
			$squidfiles["ufdbgclient.php"]=true;
			$squidfiles["external_ufdbunblock.php"]=true;
			$squidfiles["external_acl_dynamic.php"]=true;
			$squidfiles["external_acl_response.php"]=true;
			$squidfiles["external_acl_usersMacs.php"]=true;
			$squidfiles["picporn.php"]=true;
			$squidfiles["external_acl_restrict_access.php"]=true;
			$squidfiles["external_acl_categories.php"]=true;
			$squidfiles["external_acl_basic_auth.php"]=true;
			$squidfiles["external_acl_dynamic.php"]=true;
			$squidfiles["external_acl_response.php"]=true;
			$squidfiles["external_acl_size_quota.php"]=true;
			$squidfiles["external_acl_squid_ldap.php"]=true;
			$squidfiles["external_acl_squid_quota.php"]=true;
			$squidfiles["external_acl_time_quota.php"]=true;
			$squidfiles["external_acl_categorize.php"]=true;
			$squidfiles["external_acl_quota.php"]=true;
			$squidfiles["external_acl_restrict_access.php"]=true;
			$squidfiles["external_acl_squid_extldap.php"]=true;
			$squidfiles["external_acl_squid.php"]=true;
			$squidfiles["external_acl_hotspot.php"]=true;
			$artica_path="/usr/share/artica-postfix";
			while (list ($filename,$line) = each ($squidfiles)){
				if(!is_file("$artica_path/$filename")){continue;}
				@chgrp("$artica_path/$filename", "squid");
				@chown("$artica_path/$filename", "squid");
				@chmod("$artica_path/$filename",0755);
			}
			
		}
		
		
		public function ErrorDocument(){
			$f=array();
			$t=array();
			if($this->groupware=="XAPIAN"){
				$f["403"]="/xapian.index.php";
				
			}
			if($this->groupware=="ANDROWEB"){
				$f["403"]="/android.404.php";
			}
			
			if($this->groupware=="MAILHOSTING"){$f["403"]="/mailattach.php";}
			if($this->groupware=="MAILHOSTING"){$f["304"]="/mailattach.php";}
			if($this->groupware=="SPLASHSQUID"){$f["403"]="/splashproxy.php";}
			if($this->groupware=="SPLASHSQUID"){$f["404"]="/splashproxy.php";}
			
			if($this->groupware=="OWNCLOUD"){
				$f["403"]="/core/templates/403.php";
				$f["404"]="/core/templates/404.php";
			}
			
			if(count($f)>0){
				while (list ($error, $file) = each ($f) ){
					$t[]="\t\tErrorDocument $error \"$file\"";
				}
				
			}
			
			if(count($t)>0){return @implode("\n", $t);}
			
		}
		
		function DirectoryContent(){
			if($this->groupware=="KLMS"){
				$f[]="\t\tOptions FollowSymLinks +Includes";
				$f[]="\t\tAllowOverride None";
				$f[]="\t\tOrder allow,deny";
				$f[]="\t\tAllow from all";
				
				return @implode("\n",$f);
			}
			
			if( $this->groupware=="MAILHOSTING"){
				$f[]="\t\tOptions Indexes +FollowSymLinks MultiViews";
				$f[]="\t\tAllowOverride all";
				$f[]="\t\tOrder allow,deny";
				$f[]="\t\tallow from all";	
				return @implode("\n",$f);				
			}
			
			
			
		}
		
		public function FilesRestrictions(){
			if(!$this->IsGroupWareFromArtica()){
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] not an artica groupware interface\n";}
				return null;
			}
			if($this->groupware=="ARTICA_ADM"){
				$array["miniadm.register.php"]="miniadm.register.php";
			}
			if($this->groupware=="ARTICA_USR"){return null;}
			
			if($this->groupware=="ARTICA_REGISTER_OU"){
				$array=$this->ParseArticaDirectory();
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] restrictions on ". count($array). " files\n";}
				unset($array["miniadm.register.php"]);
			}
			
			if($this->groupware=="XAPIAN"){
				$array=$this->ParseArticaDirectory();
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] restrictions on ". count($array). " files\n";}
				unset($array["xapian.index.php"]);
			}
			if($this->groupware=="ANDROWEB"){
				$array=$this->ParseArticaDirectory("^android\.");
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] restrictions on ". count($array). " files\n";}
			}
			if($this->groupware=="ARTICA_PRXYLOGS"){
				$array=$this->ParseArticaDirectory();
				unset($array["prxy.monitor.php"]);
				unset($array["squid.perf.logrotate.php"]);
				unset($array["squid.compile.progress.php"]);
				unset($array["squid.squidclient.clientlist.php"]);
				unset($array["squid.squidclient.info.php"]);
				unset($array["squid.restart.php"]);
			}
			
			
			
			
			if(($this->groupware=="ARTICA_MINIADM")){
				$array["miniadm.register.php"]="miniadm.register.php";
				$array["index.php"]="index.php";
				$array["logoff.php"]="logoff.php";
				$array["logon.php"]="logon.php";
				
			}
			if(($this->groupware=="WPADDYN")){
				$array=$this->ParseArticaDirectory();
				unset($array["proxy.pac.php"]);
			}			
			
			
			if( $this->groupware=="ARTICA_ADMPRXY"){
				$array=$this->ParseArticaDirectory("squid\.users\..*?\.php");
				unset($array["ufdbguardd.php"]);
			}
			
			if( $this->groupware=="MAILHOSTING"){
				$array=$this->ParseArticaDirectory();
				unset($array["mailattach.php"]);
			}
			if( $this->groupware=="SPLASHSQUID"){
				$array=$this->ParseArticaDirectory();
				unset($array["splashproxy.php"]);
			}	
			if( $this->groupware=="ERRSQUID"){
				$array=$this->ParseArticaDirectory();
				unset($array["public.squid.error.php"]);
			}
			if( $this->groupware=="UFDBGUARD"){
				$array=$this->ParseArticaDirectory();
				unset($array["ufdbguardd.php"]);
			}
						
			
			

			
			
			
			
			unset($array["fonts.css.php"]);
			
			if(is_array($array)){
				while (list ($num, $file) = each ($array) ){
					$f[]="\t\t<Files \"$file\">";
					$f[]="\t\t\tOrder allow,deny";
					$f[]="\t\t\tDeny from all";
					$f[]="\t\t</Files>";
				}
			}
			return @implode("\n",$f);
						
			
		}
		
		
		private function ParseArticaDirectory($FilterExclude=null){
			$keyMem=md5(__FUNCTION__.$FilterExclude);
			if(isset($GLOBALS[$keyMem])){return $GLOBALS[$keyMem];}
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] parsing /usr/share/artica-postfix directory\n";}
			foreach (glob("/usr/share/artica-postfix/*.php") as $filename) {
				$f=basename($filename);
				if($FilterExclude<>null){
					if(preg_match("#$FilterExclude#", $f)){continue;}
				}
				$GLOBALS["ARTICA_FILES_BAN"][$f]=$f;
			}
			
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] ". count($GLOBALS["ARTICA_FILES_BAN"]). " files\n";}
			unset($GLOBALS["ARTICA_FILES_BAN"]["Inotify.php"]);
			unset($GLOBALS["ARTICA_FILES_BAN"]["admin.top.menus.php"]);
			unset($GLOBALS["ARTICA_FILES_BAN"]["CacheOff.php"]);
			return $GLOBALS["ARTICA_FILES_BAN"];
		}
		
		
		public function IndexIgnores(){
			
			if($this->IsGroupWareFromArtica()){
				$this->Params["IndexIgnores"]["*"]=true;
				
			}
			
			
			if(!isset($this->Params["IndexIgnores"])){return null;}
			while (list ($IndexIgnores, $ligne) = each ($this->Params["IndexIgnores"]) ){
				if(trim($IndexIgnores)==null){return;}
				$f[]="\t\tIndexIgnore $IndexIgnores";
				
			}
			
			return @implode("\n", $f);
			
		}
		
		
		public function mod_rewrite(){
			
			if($this->groupware=="DRUPAL"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine on";
   			 	$f[]="\t\tRewriteBase /";
   			 	$f[]="\t\tRewriteCond %{REQUEST_URI} !=/". md5($this->servername);
			   	$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-f";
			   	$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-d";
			   	$f[]="\t\tRewriteRule ^(.*)$ index.php?q=$1 [L,QSA]";
			  	 if($this->mod_rewrite<>null){$f[]=$this->mod_rewrite;}
			   	$f[]="</IfModule>";
			   	return @implode("\n",$f);
				
			}
			
			if($this->groupware=="JOOMLA17"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine On";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !=/". md5($this->servername);
				$f[]="\t\tRewriteCond %{QUERY_STRING} base64_encode[^(]*\([^)]*\) [OR]";
				$f[]="\t\tRewriteCond %{QUERY_STRING} (<|%3C)([^s]*s)+cript.*(>|%3E) [NC,OR]";
				$f[]="\t\tRewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]";
				$f[]="\t\tRewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2})";
				$f[]="\t\tRewriteRule .* index.php [F]";
				$f[]="\t\tRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !^/index\.php";
				$f[]="\t\tRewriteCond %{REQUEST_URI} /component/|(/[^.]*|\.(php|html?|feed|pdf|vcf|raw))$ [NC]";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-d";
				$f[]="\t\tRewriteRule .* index.php [L]";
				if($this->mod_rewrite<>null){$f[]=$this->mod_rewrite;}				
				$f[]="\t</IfModule>";
			   	return @implode("\n",$f);
			}
			if($this->groupware=="MAILHOSTING"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine On";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !=/". md5($this->servername);
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME}/index.html !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME}/index.php !-f";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !^/mailattach\.php";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME}/mailattach.php !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-f";
				$f[]="\t\tRewriteCond %{REQUEST_FILENAME} !-d";				
				$f[]="\t\tRewriteRule (.*)$ /mailattach.php?fname=$1 [L]";
				if($this->mod_rewrite<>null){$f[]=$this->mod_rewrite;}
				$f[]="\t</IfModule>";	
				return @implode("\n",$f);
			}
			if($this->groupware=="APP_FILEZ_WEB"){
				$f[]="\t<IfModule mod_rewrite.c>";
				$f[]="\t\tRewriteEngine On";
				$f[]="\t\tOptions +FollowSymlinks";
				$f[]="\t\tOptions +Indexes";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !favicon.ico";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !doc/user/(.*)\.";
				$f[]="\t\tRewriteCond %{REQUEST_URI} !resources/(.*)\.";
				$f[]="\t\tRewriteRule ^(.*)$ index.php [QSA,L]";
				
				if($this->mod_rewrite<>null){$f[]=$this->mod_rewrite;}
				$f[]="\t</IfModule>";
				return @implode("\n",$f);
			}		
			
			
			if($this->groupware=="OWNCLOUD"){
					$f[]="\t<IfModule env_module>";
					$f[]="\t\tSetEnv htaccessWorking true";
					$f[]="\t</IfModule>";
					$f[]="\t</IfModule>";
					$f[]="\t<IfModule mod_rewrite.c>";
					$f[]="\t\t<RewriteEngine on";
					$f[]="\t\t<RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]";
					$f[]="\t\t<RewriteRule ^.well-known/host-meta /public.php?service=host-meta [QSA,L]";
					$f[]="\t\t<RewriteRule ^.well-known/carddav /remote.php/carddav/ [R]";
					$f[]="\t\t<RewriteRule ^.well-known/caldav /remote.php/caldav/ [R]	";
					$f[]="\t\t<RewriteRule ^apps/calendar/caldav.php remote.php/caldav/ [QSA,L]";
					$f[]="\t\t<RewriteRule ^apps/contacts/carddav.php remote.php/carddav/ [QSA,L]";
					$f[]="\t\t<RewriteRule ^apps/([^/]*)/(.*\.(css|php))$ index.php?app=$1&getfile=$2 [QSA,L]";
					$f[]="\t\t<RewriteRule ^remote/(.*) remote.php [QSA,L]";
					$f[]="\t</IfModule>";
				return @implode("\n",$f);			
							
			}

			
			if(trim($this->mod_rewrite)==null){return;}
			$f[]="\t<IfModule mod_rewrite.c>";
			$f[]="\t\tRewriteEngine On";
			$f[]="\t\tRewriteCond %{REQUEST_URI} !=/". md5($this->servername);			
			$f[]=$this->mod_rewrite;
			$f[]="\t</IfModule>";
			return @implode("\n",$f);
			
			
		}
		
		public function mod_cache(){
			if(!$this->AS_ROOT){return;}
			if(!isset($this->Params["MOD_CACHE"]["Modenabled"])){$this->Params["MOD_CACHE"]["Modenabled"]=0;}
			if($this->Params["MOD_CACHE"]["Modenabled"]==0){
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Cache is not enabled\n";}
				return null;
			}
			if(!is_numeric($this->Params["MOD_CACHE"]["CacheDefaultExpire"])){$this->Params["MOD_CACHE"]["CacheDefaultExpire"]=3600;}
			if(!is_numeric($this->Params["MOD_CACHE"]["CacheMinExpire"])){$this->Params["MOD_CACHE"]["CacheMinExpire"]=3600;}
			if(!is_numeric($this->Params["MOD_CACHE"]["CacheMinFileSize"])){$this->Params["MOD_CACHE"]["CacheMinFileSize"]=64;}
			if(!is_numeric($this->Params["MOD_CACHE"]["CacheMaxFileSize"])){$this->Params["MOD_CACHE"]["CacheMaxFileSize"]=64000;}			
			

	
			echo "$this->PrefixOutput [CLASS/".__LINE__."] Cache is enabled\n";
			$f[]="\t<IfModule mod_cache.c>";
			$f[]="\t\t<IfModule mod_disk_cache.c>";
			$f[]="\t\t\tCacheEnable disk /";
			$f[]="\t\t\tCacheRoot \"/var/cache/apache/$this->servername\"";
			$f[]="\t\t\tCacheDirLevels 5";
			$f[]="\t\t\tCacheDirLength 4";
			
			if(isset($this->Params["MOD_CACHE"]["CacheDisable"])){
				$array=$this->Params["MOD_CACHE"]["CacheDisable"];
				if(is_array($array)){
					while (list ($num, $ligne) = each ($array) ){
						echo "$this->PrefixOutput [CLASS/".__LINE__."] CacheDisable \"$num\"\n";
						if($num==null){continue;}
						$f[]="\t\t\tCacheDisable $num";
					}
				}else{
					echo "$this->PrefixOutput [CLASS/".__LINE__."] CacheDisable no row\n";
				}
			}else{
				echo "$this->PrefixOutput [CLASS/".__LINE__."] CacheDisable not filled\n"; 
				
			}			
			
			$f[]="\t\t\tCacheMaxFileSize {$this->Params["MOD_CACHE"]["CacheMaxFileSize"]}";
			$f[]="\t\t\tCacheMinFileSize {$this->Params["MOD_CACHE"]["CacheMinFileSize"]}";
			$f[]="\t\t\tCacheIgnoreCacheControl On";
			$f[]="\t\t\tCacheIgnoreNoLastMod On";
			$f[]="\t\t\tCacheIgnoreQueryString Off";
			$f[]="\t\t\tCacheIgnoreHeaders None";
			$f[]="\t\t\tCacheLastModifiedFactor 0.2";
			$f[]="\t\t\tCacheDefaultExpire {$this->Params["MOD_CACHE"]["CacheDefaultExpire"]}";
			//$f[]="\t\t\tCacheMinExpire {$this->Params["MOD_CACHE"]["CacheMinExpire"]}";
			$f[]="\t\t\tCacheStoreNoStore Off";
			$f[]="\t\t\tCacheStorePrivate On";
			//$f[]="\t\t\tCacheStaleOnError On";
			//$f[]="\t\t\tCacheStoreExpired On";
			$f[]="\t\t\tCacheIgnoreQueryString Off";
			$f[]="\t\t\tCacheIgnoreNoLastMod On";
			$f[]="\t\t</IfModule>";
			$f[]="\t</IfModule>";
			
			$f[]="\t<IfModule mod_headers.c>";
			$f[]="\t\t<IfModule mod_expires.c>";
			$f[]="\t\t\tExpiresActive On";
			$f[]="\t\t</IfModule>";
			$f[]="\t\tHeader unset Pragma";
			$f[]="\t\tFileETag None";
			$f[]="\t\tHeader unset ETag";
			$f[]="\t\t<FilesMatch \"\.(ico|pdf|flv|jpg|jpeg|png|gif|swf|mp3|mp4)\$\">";
			$f[]="\t\t\tHeader set Cache-Control \"public, no-transform\"";
			$f[]="\t\t\tHeader set Expires A1209600";
			$f[]="\t\t\tHeader unset Last-Modified";
			$f[]="\t\t</FilesMatch>";
			$f[]="\t</IfModule>";
			$f[]="";
			
			if(!is_dir("/var/cache/apache/$this->servername")){
				@mkdir("/var/cache/apache/$this->servername",0755,true);
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$apacheusername=$sock->GET_INFO('APACHE_SRC_ACCOUNT');
				echo "$this->PrefixOutput [CLASS/".__LINE__."] cache enabled with apache user $apacheusername\n";				
				shell_exec("/bin/chown $apacheusername:$apacheusername /var/cache/apache/$this->servername");
			}
			return @implode("\n", $f);
		}
	
		public function htpassword($username,$clearTextPassword){
			
			$password = crypt($clearTextPassword, base64_encode($clearTextPassword));
			return  "$username:$password";
		}
		
		public function DigestPassword($username,$realm,$password){
			if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
			$password=$GLOBALS["CLASS_UNIX"]->shellEscapeChars($password);
			if(!isset($GLOBALS["ECHO_BIN"])){$GLOBALS["ECHO_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("echo");}
			if(!isset($GLOBALS["MD5SUM_BIN"])){$GLOBALS["MD5SUM_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("md5sum");}
			if(!isset($GLOBALS["CUT_BIN"])){$GLOBALS["CUT_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("cut");}
			$cmd="{$GLOBALS["ECHO_BIN"]} -n \"$username:$realm:\" && {$GLOBALS["ECHO_BIN"]} -n \"$username:$realm:$password\" | {$GLOBALS["MD5SUM_BIN"]} - | {$GLOBALS["CUT_BIN"]} -d' ' -f1 2>&1";
			echo $cmd."\n";
			return exec($cmd);
			
		}
		
		public function WebDavBrowserMatches(){
			$f[]="\tBrowserMatch \"Microsoft Data Access Internet Publishing Provider\" redirect-carefully";
			$f[]="\tBrowserMatch \"MS FrontPage\" redirect-carefully";
			$f[]="\tBrowserMatch \"^WebDrive\" redirect-carefully";
			$f[]="\tBrowserMatch \"^WebDAVFS/1.[0123]\" redirect-carefully";
			$f[]="\tBrowserMatch \"^gnome-vfs/1.0\" redirect-carefully";
			$f[]="\tBrowserMatch \"^XML Spy\" redirect-carefully";
			$f[]="\tBrowserMatch \"^Dreamweaver-WebDAV-SCM1\" redirect-carefully";
			$f[]="\tBrowserMatch \"Microsoft-WebDAV-MiniRedir\" redirect-carefully";
			$f[]="\tBrowserMatch \"^cadaver\" redirect-carefully";
			return @implode("\n", $f);
			
		}
		
		public function WebDavContainersCount(){
			if(isset($GLOBALS["WebDavContainersCount"])){return $GLOBALS["WebDavContainersCount"];}
			$q=new mysql();
			$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(container_id) as tcount FROM users_containers WHERE created=1 AND onerror=0 AND webdav=1","artica_backup"));
			$NUM=$ligne["tcount"];
			if(!is_numeric($NUM)){$NUM=0;}
			$GLOBALS["WebDavContainersCount"]=$NUM;
			return $GLOBALS["WebDavContainersCount"];
		}
		
		private function strip_bad_chars($value){
			$value=str_replace(" ","-",$value);
			$value=str_replace('$','',$value);
			$value=str_replace("&","",$value);
			$value=str_replace("?","",$value);
			$value=str_replace("#","",$value);
			$value=str_replace("[","-",$value);
			$value=str_replace("]","",$value);
			$value=str_replace("{","-",$value);
			$value=str_replace("}","",$value);
			$value=str_replace("*","",$value);
			$value=str_replace('"','',$value);
			$value=str_replace("'","",$value);
			$value=str_replace("(","-",$value);
			$value=str_replace(")","",$value);
			$value=str_replace("<","",$value);
			$value=str_replace(">","",$value);
			$value=str_replace("!","",$value);
			$value=str_replace("+","",$value);
			$value=str_replace(";","-",$value);
			return $value;
			
		}
		
		public function WebDavContainers(){
			if(isset($GLOBALS["WebDavContainers"])){return $GLOBALS["WebDavContainers"];}
			$sock=new sockets();
			$ApacheDisableModDavFS=$sock->GET_INFO("ApacheDisableModDavFS");
			if(!is_numeric($ApacheDisableModDavFS)){$ApacheDisableModDavFS=0;}
			if($this->WebDavContainersCount()==0){$ApacheDisableModDavFS=1;}
			if($ApacheDisableModDavFS==1){$GLOBALS["WebDavContainers"]=null;return;}
			$unix=new unix();
			$ls=$unix->find_program("ls");
			$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0 AND webdav=1";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache ".mysql_num_rows($results)." WebDav items...\n";
			$apacheusername=$unix->APACHE_SRC_ACCOUNT();
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$ID=$ligne["container_id"];
				$webdav_creds=unserialize(base64_decode($ligne["webdav_creds"]));
				
				
				
				$MountedPath="/media/artica_containers/membersdisks/disk$ID";
				shell_exec("$ls $MountedPath/*");
				if(!is_dir("$MountedPath/WebShared")){
					@mkdir("$MountedPath/WebShared",0755,true);
				}
				@chown("$MountedPath/WebShared", $apacheusername);
				@mkdir("/etc/htpass",0755,true);
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache WebDav [disk$ID] `$MountedPath`\n";
				$f[]="Alias /disk$ID $MountedPath/WebShared";
				
				$f[]="<Location /disk$ID>";
				$f[]="\tDAV On";
				$f[]="\tphp_flag engine off";
				$f[]="\tOptions -Includes -ExecCGI";
				
				# Uncomment to prevent .htaccess files from overriding above settings
				# AllowOverride None
				
				# Uncomment to turn off any existing rewrite rules
				# Rewrite engine off
				$f[]=$this->WebDavBrowserMatches();
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache WebDav [disk$ID] Username: `{$webdav_creds["username"]}`\n";
				
				
				if(trim($webdav_creds["username"])<>null){
					$AuthName=$this->strip_bad_chars($ligne["container_name"]);
					$digest=$this->DigestPassword($webdav_creds["username"], $AuthName, $webdav_creds["password"]);
					@file_put_contents("/etc/htpass/disk$ID", $digest);
					@chown("/etc/htpass/disk$ID", $apacheusername);
					
					
					$f[]="\tAuthType digest";
					$f[]="\t# {$ligne["container_name"]} - $AuthName";
					$f[]="\tAuthName \"$AuthName\"";
					echo "Starting......: ".date("H:i:s")." [INIT]: Apache WebDav digest: [disk$ID] `$AuthName/{$webdav_creds["password"]}` ($digest)\n";
					$f[]="\tAuthDigestProvider file";
					$f[]="\tAuthUserFile /etc/htpass/disk$ID";
					$f[]="\tRequire valid-user";
				}
				$f[]="</Location>";
				
				
				
			}
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache WebDav ". count($f)." lines\n";
			$GLOBALS["WebDavContainers"]=@implode("\n", $f);
			return $GLOBALS["WebDavContainers"];
			
		}
		
		
		public function WebDav(){
			if($this->groupware=="WEBDAVFREE"){return null;}
			if(!isset($this->Params["WEBDAV"]["ENABLED"])){$this->Params["WEBDAV"]["ENABLED"]=0;}
			if($this->Params["WEBDAV"]["ENABLED"]==0){
				if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] WebDAV is not enabled\n";}
				return null;
			}
			if($GLOBALS["VERBOSE"]){echo "DEBUG::WebDav:: WebDav enabled\n";}
			$users=new usersMenus();
			if(!$users->APACHE_MODE_WEBDAV){
				if($GLOBALS["VERBOSE"]){echo "DEBUG::WebDav:: WebDav module not installed\n";}
				return null;
			}
			
			$ldap=new clladp();
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] WebDav is enabled\n";}
         	$f[]="DAV On";
          	$f[]="DAVMinTimeout 600";
           	$f[]="AuthType Basic";
           	$f[]="AuthBasicProvider ldap";
           	
           	if($ldap->IsKerbAuth()){
           		$f[]=$this->WebDav_ActiveDirectory();
           		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] WebDav -> Active Directory\n";}
           		return  @implode("\n",$f);
           	}
           	
           	$f[]="AuthName \"$this->servername Users Only\"";
           	$f[]="AuthLDAPURL ldap://$ldap->ldap_host:$ldap->ldap_port/dc=organizations,$ldap->suffix?uid?sub";
           	$f[]="AuthLDAPBindDN cn=$ldap->ldap_admin,$ldap->suffix";
           	$f[]="AuthLDAPBindPassword $ldap->ldap_password";
          	$f[]="AuthLDAPGroupAttribute memberUid";
          	if(!is_array($this->Params["WEBDAV"]["MEMBERS"])){
          		if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Authentication for ". count($this->Params["WEBDAV"]["MEMBERS"])." items\n";}
          		$c=0;
          		while (list ($member, $asnull) = each ($this->Params["WEBDAV"]["MEMBERS"]) ){
          			if($member==null){continue;}
          			if(preg_match("#@(.+):([0-9]+)$#",$member,$re)){
          				$groups=new groups($re[2]);
          				$c++;
          				$f[]="Require group \"$groups->dn\"";
          			}else{
          				$c++;
          				$f[]="Require user \"$member\"";
          			}
          		}
          		
          	}
			if($c==0){$f[]="Require valid-user";}		
			return @implode("\n",$f);
			
		}
		
		public function WebDav_ActiveDirectory(){
			include_once(dirname(__FILE__)."/class.external.ad.inc");
			$ad=new external_ad_search();

			$host=$ad->KerbAuthInfos["ADNETIPADDR"];
			$suffix=$ad->KerbAuthInfos["LDAP_SUFFIX"];
			$password=$ad->KerbAuthInfos["WINDOWS_SERVER_PASS"];
			$usrname=$ad->BuildAdUser();
			$f[]="AuthName \"$this->servername Login\"";
			$f[]="AuthLDAPURL \"ldap://$host:389/$suffix?sAMAccountName?sub?(objectClass=user)\" NONE";
			$f[]="AuthLDAPBindDN \"$usrname\"";
			$f[]="AuthLDAPBindPassword \"$password\"";
			$f[]="require ldap-attribute objectClass=user";
			return @implode("\n",$f);
		}
		
		private function DeleteLVM(){
			if(!$this->AS_ROOT){return;}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
			if($vgservices["freewebs"]==null){return true;}
			if($this->lvm_vg==null){return true;}	
			$lvm=new lvm_root();	
			if(!$lvm->DeleteLVS($this->lvm_vg,$this->servername,"$this->lvm_vg-$this->servername")){return false;}	
			return true;
		}
		
		public function WebDavTable(){
			if($this->groupware<>"WEBDAVFREE"){return null;}
			$q=new mysql();
			$results=$q->QUERY_SQL("SELECT * FROM freeweb_webdav WHERE servername='$this->servername'","artica_backup");
			if(mysql_num_rows($results)==0){return null;}
			
			while ($ligne = mysql_fetch_assoc($results)) {
				$AuthLDAP=false;
				$IsAuth=false;
				$LOCATIONDONE=false;
				$directory=$ligne["directory"];
				@chmod($directory, 0755);
				if(!is_dir($directory)){
					if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] WebDav $directory no such directory\n";}
					continue;
				}
				$params=unserialize(base64_decode($ligne["params"]));
				
				if(($params["connectiontype"]=="ad") OR ($params["connectiontype"]=="ldap")){$AuthLDAP=true;}

				
				$Alias["/{$ligne["alias"]}"]=$directory;
				$AliasN[$ligne["alias"]]=$ligne["alias"];
				$locations[]="\t<Location /{$ligne["alias"]}>";
				if(is_file('/usr/lib/apache2/modules/mod_kav64.so')){$locations[]="\tSetInputFilter KAV";}
				$locations[]="\t\t# connectiontype = \"{$params["connectiontype"]}\"";
				$locations[]="\t\tDAV On";
				if(($params["connectiontype"]=="ad") OR ($params["connectiontype"]=="ldap")){
					$locations[]="\t\tOrder deny,allow";
					$locations[]="\t\tDeny from All";
					$locations[]="\t\tAuthLDAPBindDN \"{$params["LDAP_DN"]}\"";
					$locations[]="\t\tAuthLDAPBindPassword \"{$params["LDAP_PASSWORD"]}\"";
					$locations[]="\t\tAuthType Basic";	
					$locations[]="\t\tAuthBasicProvider ldap";
					$locations[]="\t\tAuthUserFile /dev/null";							
				}
				
				if($AuthLDAP){
					if($params["connectiontype"]=="ad"){
						$LOCATIONDONE=true;
						$locations[]="\t\tAuthLDAPURL \"ldap://{$params["LDAP_SERVER"]}:{$params["LDAP_PORT"]}/{$params["LDAP_SUFFIX"]}?sAMAccountName?sub?(objectClass=*)\"";
						$locations[]="\t\tAuthName \"USE YOUR WINDOWS ACCOUNT\"";
					}
				}
					
				
			
				if($params["connectiontype"]=="ldap"){
					if(!$LOCATIONDONE){
						$AuthLDAPURL="ldap://{$params["LDAP_SERVER"]}:{$params["LDAP_PORT"]}/{$params["LDAP_SUFFIX"]}?{$params["LDAP_FILTER"]}";
						$AuthLDAPURL=str_replace("??", "?", $AuthLDAPURL);
						$locations[]="\t\tAuthLDAPURL \"$AuthLDAPURL\"";
						$locations[]="\t\tAuthName \"USE YOUR LDAP ACCOUNT\"";
					}
					$locations[]="\t\tAuthLDAPGroupAttribute {$params["GROUP_ATTRIBUTE"]}";
					$locations[]="\t\tAuthLDAPGroupAttributeIsDN off";
				}
				
				if(count($params["MEMBERS"])>0){
					while (list ($dn, $itemname) = each ($params["MEMBERS"]) ){$locations[]="\t\tRequire ldap-dn $dn";}
				}
			
				if($params["ADGROUP"]<>null){
					$locations[]="\t\tRequire ldap-group {$params["ADGROUP"]}";
				}else{
					if($IsAuth){$locations[]="\t\trequire valid-user";}
				}
			
				if($IsAuth){$locations[]="\t\tSatisfy any";}
				$locations[]="\t</Location>";
		
			}
			
			while (list ($alias, $dir) = each ($Alias) ){$Aliaz[]="\tAlias $alias \"$dir\"";}
			
			if(count($Aliaz)==0){return null;}
			$this->WebDavIndexPage($AliasN);
			return @implode("\n", $Aliaz)."\n".@implode("\n", $locations)."\n";
			
		}
		
		private function WebDavIndexPage($array){
			ksort($array);
			$BckColor="4685BC";
			$head="<html> <head> 	<meta http-equiv='content-type' content='text/html;charset=utf-8'> 	<title>Web Sharing $this->servername</title> 	<style type=\"text/css\"> 	html,body{height:100%} 	body{ 	width:100%;min-height:100%;margin:0;padding:0;color:#2C2C2C; 	font-family:'Tahoma', 'Verdana'; 	font-size:11px; 	background:#FFF 	} 	form{margin:0} 	table,input,select{font:normal 100% tahoma} 	img{border:0;margin:0} 	table{border-collapse:collapse} 	a{color:#62707D} 	.t,tr.t td{vertical-align:top} 	.m{vertical-align:middle} 	.b,tr.b td{vertical-align:bottom} 	tr.t td td,tr.b td td{vertical-align:auto} 	.l{text-align:left} 	.c{text-align:center} 	.r{text-align:right} 	.nobr{white-space:nowrap} 	.rel{position:relative} 	.abs{position:absolute} 	.fl{float:left} 	.fr{float:right} 	.cl{clear:both} 	.w100{width:100%} 	.h100{height:100%} 	big,.big{font-size:125%} 	small,.small{font-size:95%} 	.micro{color:#DDD;font:normal 9px tahoma} 	h1{font:bold 20px arial; margin:0} 	h4{font:bold 12px arial; margin:0} 	p{text-align:justify;line-height:1.3;margin:0 0 0.5em 0} 	.z{border:1px solid red} 	.h1px{height:1px;font-size:1px;line-height:1px} 	ul{margin:6px 0 6px 20px;padding:0} 	ul li{margin:3px 0} </style> </head> <body>﻿ <table class=\"w100 h100\"> 	<tr> 		<td class=\"c m\"> 			<table style=\"margin:0 auto;border:solid 1px #560000\"> 				<tr> 					<td class=\"l\" style=\"padding:1px\"> 						<div style=\"width:346px;background:#$BckColor\"> 							<div style=\"padding:3px\">";
			//E33630
			
			$f[]="<div style=\"background:#$BckColor;padding:8px;border:solid 1px #FFF;color:#FFF\">";
			$f[]="<h4><SPAN STYLE='FONT-SIZE:11px'>$this->servername</span></h4>";
			$f[]="<h1>Web shared folders list:</h1>";
			$f[]="</div>";
			$f[]="<div class=\"c\" 
					style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Click on one of these links</div>";
			$f[]="<div style=\"background:#F7F7F7;padding:20px 28px 36px\">";
			$f[]="<table>";
			while (list ($alias, $dir) = each ($array) ){
				$f[]="<tr>
				<td width=1%><img src='/arrow.png'></td>
				<td nowrap><a href=\"/$alias\" _target=new 
				style=\"font-size:16px;font-weight:bold;text-decoration:underline\">$alias</a></td></tr>";
				
			}
			$f[]="</table>";
			$f[]="</div>";
			$f[]="<div style=\"background:#F7F7F7;padding:0 2px 2px\">";
			$f[]="<div style=\"background:#E9E9E9;padding:12px 30px 14px\">";
			$f[]="<b><span style=\"font:normal 13px arial;\"><i>Index generated on ". date("Y l F d")."</i><br></div>";
			$f[]="</div>";
			$f[]="</div>";
			$f[]="</div>";
			$f[]="</td>";
			$f[]="</tr>";
			$f[]="</table>";
			$f[]="</td>";
			$f[]="</tr>";
			$f[]="</table>";
			$f[]="";
			$f[]="";
			$f[]="</body>";
			$f[]="</html>";
			@copy("/usr/share/artica-postfix/img/arrow-blue-left-32.png","$this->WORKING_DIRECTORY/arrow.png");
			@file_put_contents($this->WORKING_DIRECTORY."/index.html", $head.@implode("\n", $f));
			
		}
		
		function delete(){
			if(!$this->AS_ROOT){return;}
			if(!$this->DeleteLVM()){
				writelogs("->DeleteLVM() FAILED",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				return;}
			$sql="SELECT * FROM freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
			$database=$ligne["mysql_database"];
			if(strlen($database)>0){
				if($q->DATABASE_EXISTS($database)){
					$q->DELETE_DATABASE($database);
					if($q->DATABASE_EXISTS($database,true)){
						writelogs("Unable to delete DB $database",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
						return;
					}
				}
				
				$sql="DELETE FROM `db` WHERE `Db`='$database'";
				writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$q->QUERY_SQL($sql,'mysql');
			}
			
			
			$sql="DELETE FROM `awstats` WHERE `website`='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){echo $q->mysql_error;}				
			
			if($ligne["mysql_username"]<>null){
				if($q->COUNT_DB_FROM_USER($ligne["mysql_username"])==0){
					$q->DELETE_USER_INMYSQL($ligne["mysql_username"]);
				}
				
			}
			
			
			$sql="DELETE FROM freeweb WHERE servername='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error;return;}
			
			$sql="DELETE FROM awstats_files WHERE `servername`='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
			
			$sql="DELETE FROM freewebs_proxy WHERE `servername`='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");		

			$sql="DELETE FROM freewebs_aliases WHERE `servername`='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
			
			$sql="DELETE FROM freeweb_php WHERE `servername`='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");
			

			$sql="DELETE FROM freeweb_slashsquid WHERE servername='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");	

			$sql="DELETE FROM freeweb_webdav WHERE servername='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");			

			$sql="DELETE FROM freewebs_openbasedir WHERE servername='$this->servername'";
			writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_backup");			
			
			
			
			$ldap=new clladp();
			$dn="dc=$this->servername,dc=pureftpd,dc=organizations,$ldap->suffix";
			if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn,true);}
			
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$sock->getFrameWork("cmd.php?pure-ftpd-users=yes");	
			$sock->getFrameWork("cmd.php?freeweb-restart=yes");
			
			
		}
		public function InstallZarafaWebAPP(){
			if(!$this->AS_ROOT){return;}
			writelogs("Start Working directory = $this->WORKING_DIRECTORY",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if(!is_dir($this->WORKING_DIRECTORY)){@mkdir($this->WORKING_DIRECTORY,0755,true);}
			if(is_dir($this->WORKING_DIRECTORY)){shell_exec("/bin/rm -rf $this->WORKING_DIRECTORY/*");}
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa WebAPP 7\n";
			
			shell_exec("/bin/cp -rf /usr/share/zarafa-webapp/* $this->WORKING_DIRECTORY/");
			@mkdir($this->WORKING_DIRECTORY."/tmp",0755,true);
			@unlink("$this->WORKING_DIRECTORY/config.php");
			writelogs("Start InstallZarafaConfigWebAPP()",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$this->InstallZarafaConfigWebAPP();
			$this->InstallZPushInside();		
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa WebAPP 7 done...\n";			
			
		}
		
		
		
		public function InstallZarafa(){
			if(!$this->AS_ROOT){return;}
			writelogs("Start Working directory = $this->WORKING_DIRECTORY",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if(!is_dir($this->WORKING_DIRECTORY)){@mkdir($this->WORKING_DIRECTORY,0755,true);}
			if(is_dir($this->WORKING_DIRECTORY)){shell_exec("/bin/rm -rf $this->WORKING_DIRECTORY/*");}
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa WebAccess\n";
			shell_exec("/bin/rm -f /usr/share/zarafa-webaccess/zarafa-webaccess >/dev/null 2>&1");
			echo "Starting......: ".date("H:i:s")." Apache $this->servername /usr/share/zarafa-webaccess -> $this->WORKING_DIRECTORY\n";
			shell_exec("/bin/cp -rf /usr/share/zarafa-webaccess/* $this->WORKING_DIRECTORY/");
			@mkdir($this->WORKING_DIRECTORY."/tmp",0755,true);
			echo "Starting......: ".date("H:i:s")." Apache $this->servername remove $this->WORKING_DIRECTORY/config.php\n";
			@unlink("$this->WORKING_DIRECTORY/config.php");
		
			writelogs("Start InstallZarafaConfigPHP()",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$this->InstallZarafaConfigPHP();
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa Webmail done...\n";
			$this->InstallZPushInside();
		}
		
		private function InstallZarafaMobile(){
			if(!$this->AS_ROOT){return;}
			if(!is_dir($this->WORKING_DIRECTORY)){@mkdir($this->WORKING_DIRECTORY,0755,true);}
			if(is_dir($this->WORKING_DIRECTORY)){shell_exec("/bin/rm -rf $this->WORKING_DIRECTORY/*");}
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa\n";
			shell_exec("/bin/rm -f /usr/share/zarafa-webaccess-mobile/zarafa-webaccess-mobile >/dev/null 2>&1");
			@mkdir("$this->WORKING_DIRECTORY/cache",0755,true);
			@mkdir("$this->WORKING_DIRECTORY/templates_c",0755,true);			
			@mkdir($this->WORKING_DIRECTORY."/tmp",0755,true);
			@mkdir($this->WORKING_DIRECTORY."/config",0755,true);
			shell_exec("/bin/cp -rf /usr/share/zarafa-webaccess-mobile/* $this->WORKING_DIRECTORY/");
			echo "Starting......: ".date("H:i:s")." Apache $this->servername installing Zarafa-webaccess-mobile done...\n";	
			$this->InstallZarafaMobileConfigPHP("$this->WORKING_DIRECTORY");	
			
		}
		
		private function InstallZPushInside(){
			if($this->Params["ZARAFAWEB_PARAMS"]["zPushInside"]==0){return;}
			@mkdir("$this->WORKING_DIRECTORY/mobile/state",0777,true);
			@chmod("$this->WORKING_DIRECTORY/mobile/state",0777);
			@mkdir("$this->WORKING_DIRECTORY/mobile/log",0777,true);
			@chmod("$this->WORKING_DIRECTORY/mobile/log",0777);		
			echo "Starting......: ".date("H:i:s")." Apache  installing Z-Push-inside on  $this->WORKING_DIRECTORY/mobile...\n";		
			if(is_file("$this->WORKING_DIRECTORY/mobile/z-push-admin.php")){$this->InstallZarafaZpush("$this->WORKING_DIRECTORY/mobile/config.php");return;}
			if(!is_dir("$this->WORKING_DIRECTORY/mobile")){@mkdir("$this->WORKING_DIRECTORY/mobile",0755,true);}
			shell_exec("/bin/cp -rf /usr/share/z-push/* $this->WORKING_DIRECTORY/mobile/");
			$this->InstallZarafaZpush("$this->WORKING_DIRECTORY/mobile/config.php");
			
			
			
		}
		
		public function ZarafaProxyJabberd(){
			if(!$this->AS_ROOT){return;}
			if($this->groupware<>"WEBAPP"){return null;}
			$users=new usersMenus();
			$sock=new sockets();
			if(!$users->EJABBERD_INSTALLED){echo "Starting......: ".date("H:i:s")." Apache $this->servername ejabberd not installed, aborting reverse Proxy\n";return;}
			$ejabberdInsideZarafa=$sock->GET_INFO("ejabberdInsideZarafa");
			if(!is_numeric($ejabberdInsideZarafa)){$ejabberdInsideZarafa=0;}
			if($ejabberdInsideZarafa==0){return;}
			$f[]="\t<IfModule mod_proxy_http.c>";
			$f[]="\t\tProxyPass /http-bind http://127.0.0.1:5280/http-bind/";
			$f[]="\t\tProxyPassReverse /http-bind http://127.0.0.1:5280/http-bind/";
			//$f[]="\t\t<Proxy http://127.0.0.1:5280/>";
			//$f[]="\t\t\tOrder Allow,Deny";
			//$f[]="\t\t\tAllow from localhost";
			//$f[]="\t\t</Proxy>";
			$f[]="\t</IfModule>";
			echo "$this->PrefixOutput [CLASS/".__LINE__."] ejabberd	-> http://127.0.0.1:5280 Proxy\n";
			return @implode("\n", $f);
		}
		
		
		private function Autodiscover(){
			$unix=new unix();
			$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
			if(!isset($PARAMS["AutoDiscoverUri"])){$PARAMS["AutoDiscoverUri"]=null;}
			$AutoDiscoverUri=$PARAMS["AutoDiscoverUri"];
			if($AutoDiscoverUri==null){
				$prefix="http://";
				if($this->SSL_enabled==1){$prefix="https://";}
				$AutoDiscoverUri="$prefix$this->servername";
			}
			$f[]="<?php";
			$f[]="   //get raw POST data so we can extract the email address";
			$f[]="   \$data = file_get_contents(\"php://input\");";
			$f[]="   preg_match(\"/\<EMailAddress\>(.*?)\<\/EMailAddress\>/\", \$data, \$matches);";
			$f[]="   //set Content-Typeheader(\"Content-Type: application/xml\");?>";
			$f[]="<?php echo '<?xml version=\"1.0\" encoding=\"utf-8\" ?>'; ?>";
			$f[]="<Autodiscover xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns=\"http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006\">";
			$f[]="<Response xmlns=\"http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006\">";
			$f[]="<Culture>en:en</Culture>";
			$f[]="<User>";
			$f[]="<DisplayName><?php echo \$matches[1]; ?></DisplayName>";
			$f[]="<EMailAddress><?php echo \$matches[1]; ?></EMailAddress>";
			$f[]="</User>";
			$f[]="<Action>";
			$f[]="<Settings>";
			$f[]="<Server>";
			$f[]="<Type>MobileSync</Type>";
			$f[]="<Url>$AutoDiscoverUri/Microsoft-Server-ActiveSync</Url>";
			$f[]="<Name>$AutoDiscoverUri/Microsoft-Server-ActiveSync</Name>";
			$f[]="</Server>";
			$f[]="</Settings>";
			$f[]="</Action>";
			$f[]="</Response>";
			$f[]="</Autodiscover>";
			@mkdir("$this->WORKING_DIRECTORY/Autodiscover",0755,true);			
			@file_put_contents("$this->WORKING_DIRECTORY/Autodiscover/Autodiscover.php",@implode("\n",$f));
			$unix->chown_func($this->APACHE_RUN_USER, $this->APACHE_RUN_GROUP,"$this->WORKING_DIRECTORY/Autodiscover/*");
			
		}
		
		
		private function InstallZarafaZpush($indexPath=null){
				if(!$this->AS_ROOT){return;}
				$DirectorySrc="/usr/share/z-push";
				$unix=new unix();
				$cp=$unix->find_program("cp");
				
				
				
				
				
				if($indexPath<>null){$Directory=dirname($indexPath);
				
				
				if(basename($indexPath)<>"config.php"){
						if($this->AS_ROOT){echo "Starting......: ".date("H:i:s")." Apache InstallZarafaZpush():: `$indexPath`: ". basename($indexPath). " is not config.php, aborting...\n";}	
						return ;
					}
				}else{
					$Directory=$this->WORKING_DIRECTORY;
				}
				
				if($this->AS_ROOT){echo "Starting......: ".date("H:i:s")." Apache InstallZarafaZpush():: \"$this->servername\" install to `$Directory`\n";}
				shell_exec("$cp -rf $DirectorySrc/* $Directory/");
				
				
				echo "$this->PrefixOutput [CLASS/".__LINE__."] InstallZarafaZpush():: index path: $indexPath...\n";
				$f[]="<?php";
				$f[]="    define('TIMEZONE', '');";
				$f[]="    define('BASE_PATH', dirname(\$_SERVER['SCRIPT_FILENAME']). '/');";
				$f[]="    define('SCRIPT_TIMEOUT', 0);";
				$f[]="    define('MAX_EMBEDDED_SIZE', 1048576);";
				$f[]="    define('STATE_DIR', '$Directory/state/');";
				$f[]="    define('LOGFILEDIR', '$Directory/log/');";
				$f[]="    define('LOGFILE', LOGFILEDIR . 'z-push.log');";
				$f[]="    define('LOGERRORFILE', LOGFILEDIR . 'z-push-error.log');";
				$f[]="    define('LOGLEVEL', LOGLEVEL_INFO);";
				$f[]="    define('LOGAUTHFAIL', false);";
				$f[]="    define('LOGUSERLEVEL', LOGLEVEL_DEVICEID);";
				$f[]="    \$specialLogUsers = array();";
				$f[]="    define('PROVISIONING', true);";
				$f[]="    define('LOOSE_PROVISIONING', false);";
				$f[]="    define('SYNC_CONFLICT_DEFAULT', SYNC_CONFLICT_OVERWRITE_PIM);";
				$f[]="    define('SYNC_FILTERTIME_MAX', SYNC_FILTERTYPE_ALL);";
				$f[]="    define('PING_INTERVAL', 30);";
				$f[]="    define('SINK_FORCERECHECK', 300);";
				$f[]="    define('BACKEND_PROVIDER', \"BackendZarafa\");";
				$f[]="    define('MAPI_SERVER', 'file:///var/run/zarafa');";
				$f[]="    define('IMAP_SERVER', 'localhost');";
				$f[]="    define('IMAP_PORT', 143);";
				$f[]="    define('IMAP_OPTIONS', '/notls/norsh');";
				$f[]="    define('IMAP_DEFAULTFROM', '');";
				$f[]="    define('IMAP_SENTFOLDER', '');";
				$f[]="    define('IMAP_INLINE_FORWARD', false);";
				$f[]="    define('IMAP_USE_IMAPMAIL', true);";
				$f[]="    define('MAILDIR_BASE', '/tmp');";
				$f[]="    define('MAILDIR_SUBDIR', 'Maildir');";
				$f[]="    define('VCARDDIR_DIR', '/home/%u/.kde/share/apps/kabc/stdvcf');";
				$f[]="    define('SEARCH_PROVIDER', '');";
				$f[]="    define('SEARCH_WAIT', 10);";
				$f[]="    define('SEARCH_MAXRESULTS', 10);";
				$f[]="";
				$f[]="    \$additionalFolders = array(";
				$f[]="        // demo entry for the synchronization of contacts from the public folder.";
				$f[]="        // uncomment (remove '/*' '*/') and fill in the folderid";
				
				if(isset($this->Params["ZPUSH"]["STORES"])){
					$params=$this->Params["ZPUSH"]["STORES"];
					while (list ($ID, $folder) = each ($params) ){
						echo "$this->PrefixOutput [CLASS/".__LINE__."] InstallZarafaZpush():: Public folder:{$folder["U"]}:{$folder["F"]}\n";
						$f[]="        array(";
						$f[]="            'store'     => \"{$folder["U"]}\",";
						$f[]="            'folderid'  => \"$ID\",";
						$f[]="            'name'      => \"{$folder["F"]}\",";
						$f[]="            'type'      => {$folder["T"]},";
						$f[]="        ),";						
					}
				}
				$f[]="/*";
				$f[]="        array(";
				$f[]="            'store'     => \"SYSTEM\",";
				$f[]="            'folderid'  => \"\",";
				$f[]="            'name'      => \"Public Contacts\",";
				$f[]="            'type'      => SYNC_FOLDER_TYPE_USER_CONTACT,";
				$f[]="        ),";
				$f[]="*/";
				$f[]="    );";
				$f[]="";
				$f[]="?>";
				if($indexPath==null){$indexPath="$this->WORKING_DIRECTORY/config.php";}
				echo "Starting......: ".date("H:i:s")." Apache  InstallZarafaZpush():: creating `$indexPath` configuration file\n";
				@file_put_contents($indexPath, @implode("\n", $f));
				$unix=new unix();
				$unix->chown_func($this->APACHE_RUN_USER, $this->APACHE_RUN_GROUP,"$this->WORKING_DIRECTORY/*");
				echo "$this->PrefixOutput [CLASS/".__LINE__."] InstallZarafaZpush():: Check Autodiscover...\n";
				
				$FAKE[]="index.php";
				$FAKE[]="index2.php";
				$FAKE[]="index.html";
				$FAKE[]="update.html";
				
				while (list ($index, $filename) = each ($FAKE) ){
					@unlink("$Directory/state/$filename");
					@touch("$Directory/state/$filename");
					@chmod("$Directory/state/$filename",0644);
					@chown("$Directory/state/$filename", "root");
					@chgrp("$Directory/state/$filename", "root");
				}
				

				$this->Autodiscover();
		}
		
		
		private function InstallZarafaMobileConfigPHP(){
				if(!$this->AS_ROOT){return;}
				$users=new usersMenus();
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
				if(!isset($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$sock->GET_INFO("ZarafaWebNTLM");}
				if(!isset($PARAMS["ZarafaEnablePlugins"])){$PARAMS["ZarafaEnablePlugins"]=$sock->GET_INFO("ZarafaEnablePlugins");}
				if(!isset($PARAMS["ZarafaAspellEnabled"])){$PARAMS["ZarafaAspellEnabled"]=$sock->GET_INFO("ZarafaAspellEnabled");}
				if(!isset($PARAMS["EnableZarafaRemoteServer"])){$PARAMS["EnableZarafaRemoteServer"]=0;}
				if(!isset($PARAMS["EnableZarafaRemoteServerAddr"])){$PARAMS["EnableZarafaRemoteServerAddr"]=null;}
				if(!isset($PARAMS["EnableDebugMode"])){$PARAMS["EnableDebugMode"]=0;}
				if(!isset($PARAMS["EnableZarafaSecondInstance"])){$PARAMS["EnableZarafaSecondInstance"]=0;}
				
				$EnableZarafaSecondInstance=$PARAMS["EnableZarafaSecondInstance"];
				$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
				$ZarafaEnablePlugins=$PARAMS["ZarafaEnablePlugins"];
				$ZarafaAspellEnabled=$PARAMS["ZarafaAspellEnabled"];
				$EnableZarafaRemoteServer=$PARAMS["EnableZarafaRemoteServer"];
				$EnableZarafaRemoteServerAddr=$PARAMS["EnableZarafaRemoteServerAddr"];
				$EnableDebugMode=$PARAMS["EnableDebugMode"];
				
				if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
				if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
				if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
				if(!is_numeric($EnableZarafaRemoteServer)){$EnableZarafaRemoteServer=0;}				
				if(!is_numeric($EnableDebugMode)){$EnableDebugMode=0;}
				if(!is_numeric($EnableZarafaSecondInstance)){$EnableZarafaSecondInstance=0;}
				
				
				if($EnableZarafaRemoteServerAddr==null){$EnableZarafaRemoteServer=0;}
				if($EnableZarafaSecondInstance==1){
					$this->ForceInstanceZarafaID=0;
					$EnableZarafaRemoteServer=0;}
				
				if(!$users->ASPELL_INSTALLED){$ZarafaAspellEnabled=0;}

				
				$DEFAULT_SERVER=$this->InstallZarafaCheckDefaultServer();
	
			
			
			$f[]="<?php";
			$f[]="define(\"CONFIG_CHECK\", true);";
			$f[]="define(\"SERVER\", \"$DEFAULT_SERVER\");";
			$f[]="define('BASE_PATH', dirname(\$_SERVER['SCRIPT_FILENAME']) . \"/\");";
			$f[]="set_include_path(BASE_PATH.\"include/\" . PATH_SEPARATOR . BASE_PATH . PATH_SEPARATOR . \".\" . PATH_SEPARATOR . \"/usr/share/php/\");";
			$f[]="define(\"COOKIE_EXPIRE\", 60*60*24*365);";
			$f[]="define(\"SMARTY_TEMPLATE_DIR\", \"render/html\");";
			$f[]="define(\"SMARTY_CONFIG_DIR\", \"$this->WORKING_DIRECTORY/config\");";
			$f[]="define(\"SMARTY_CACHE_DIR\",	\"$this->WORKING_DIRECTORY/cache\"); // must be writable";
			$f[]="define(\"SMARTY_COMPILE_DIR\", \"$this->WORKING_DIRECTORY/templates_c\"); // must be writable";
			$f[]="\$config[\"renderer\"]=\"html\";";
			$f[]="define('NOSENDERMSG', _(\"No sender\"));";
			$f[]="define('NOSUBJECTMSG', _(\"No subject\"));";
			$f[]="define('CELLSHOUR', 1);";
			$f[]="define('SCREENWIDTH', 240);";
			$f[]="\$GLOBALS[\"emailsonpage\"]=10;";
			$f[]="\$GLOBALS[\"appointmentsonpage\"]=10;";
			$f[]="\$GLOBALS[\"contactsonpage\"]=1000;";
			$f[]="\$GLOBALS[\"tasksonpage\"]=100;";
			$f[]="ini_set('post_max_size', \"31M\");";
			$f[]="ini_set('upload_max_filesize', \"30M\");";
			$f[]="ini_set('memory_limit', -1);  // -1 = no limit";
			$f[]="ini_set('max_execution_time', 300); // 5 minutes";
			$f[]="define('BLOCK_SIZE', 1048576);";
			$f[]="define('MAX_BODY_SIZE', 100 * 1024);	";
			$f[]="\$debug = false;";
			$f[]="";
			$f[]="	if (\$debug){";
			$f[]="		error_reporting(E_ALL);";
			$f[]="		ini_set(\"display_errors\", true);";
			$f[]="		if (file_exists(\"debug.php\")){";
			$f[]="			include (\"debug.php\");";
			$f[]="		}else{";
			$f[]="			// define empty dump function in case we still use it somewhere";
			$f[]="			function dump(){}";
			$f[]="		}	";
			$f[]="	}else{";
			$f[]="		ini_set(\"display_errors\", false);";
			$f[]="	}";
			$f[]="";
			$f[]="?>";	
			@unlink("$this->WORKING_DIRECTORY/config.php");	
			@file_put_contents("$this->WORKING_DIRECTORY/config.php",@implode("\n",$f));	
		}
		
		private function InstallChilli(){
			@mkdir("$this->WORKING_DIRECTORY/$this->servername/img",0755,true);
			@chmod("$this->WORKING_DIRECTORY/$this->servername/img",0755);
			$f["input.png"]=true;    
			$f["btn_bg.png"]=true;  ;  
			$f["input_act.png"]=true;  
			$f["form.png"]=true;  ;  
			$f["logo.png"]=true;   
			$f["pattern.png"]=true;  

			while (list ($key, $value) = each ($f) ){
				@copy("/usr/share/artica-postfix/ressources/templates/Squid/i/$key", "$this->WORKING_DIRECTORY/$this->servername/img/$key");
				@chmod("$this->WORKING_DIRECTORY/$this->servername/img/$key",0755);
			}
			
		}
		
		
		
		public function InstallZarafaConfigWebAPP($Otherdirectory=null){
				if(!$this->AS_ROOT){return;}
				if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
				$unix=$GLOBALS["CLASS_UNIX"];
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$cp=$unix->find_program("cp");
				$chown=$unix->find_program("chown");
				$apacheusername=$unix->APACHE_SRC_ACCOUNT();
				writelogs("[$this->servername]: Start WORKING_DIRECTORY = $this->WORKING_DIRECTORY",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: Start apacheusername = $apacheusername",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$this->InstallZarafaCheckConfig();
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];				
				$users=new usersMenus();	
				$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
				$ZarafaEnablePlugins=$PARAMS["ZarafaEnablePlugins"];
				$ZarafaAspellEnabled=$PARAMS["ZarafaAspellEnabled"];
				$EnableZarafaRemoteServer=$PARAMS["EnableZarafaRemoteServer"];
				$EnableZarafaRemoteServerAddr=$PARAMS["EnableZarafaRemoteServerAddr"];
				$EnableDebugMode=$PARAMS["EnableDebugMode"];
				
				if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
				if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
				if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}	

				$DEFAULT_SERVER=$this->InstallZarafaCheckDefaultServer();
				
				$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
				$ZarafaEnablePlugins=$PARAMS["ZarafaEnablePlugins"];
				$ZarafaAspellEnabled=$PARAMS["ZarafaAspellEnabled"];
				$EnableZarafaRemoteServer=$PARAMS["EnableZarafaRemoteServer"];
				$EnableZarafaRemoteServerAddr=$PARAMS["EnableZarafaRemoteServerAddr"];
				$EnableDebugMode=$PARAMS["EnableDebugMode"];
				
				if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
				if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=1;}
				if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
							
				if(!is_numeric($EnableDebugMode)){$EnableDebugMode=0;}
				if($EnableZarafaRemoteServerAddr==null){$EnableZarafaRemoteServer=0;}
				if(!$users->ASPELL_INSTALLED){$ZarafaAspellEnabled=0;}				
				
				$unix=new unix();
				$f[]="<?php";
				//$f[]="	// Comment next line to disable the config check (or set FALSE to log the config errors)";
				$f[]="	define(\"CONFIG_CHECK\", TRUE);";
				//$f[]="	// Default Zarafa server to connect to.";
				$f[]="	define(\"DEFAULT_SERVER\",\"$DEFAULT_SERVER\");";
				$f[]="";
				//$f[]="	// When using a single-signon system on your webserver, but Zarafa is on another server";
				//$f[]="	// you can use https to access the zarafa server, and authenticate using an SSL certificate.";
				$f[]="	define(\"SSLCERT_FILE\", NULL);";
				$f[]="	define(\"SSLCERT_PASS\", NULL);";
				//$f[]="	// set to 'true' to strip domain from login name found from Single Signon webservers";
				$f[]="	define(\"LOGINNAME_STRIP_DOMAIN\", false);";
				//$f[]="	// Name of the cookie that is used for the session, we can also use an external cookie";
				$f[]="	if (isset(\$_GET[\"external\"]) && preg_match(\"/[a-z][a-z0-9_]+/i\",\$_GET[\"external\"])){";
				$f[]="		define(\"COOKIE_NAME\",\$_GET[\"external\"]);";
				$f[]="	}else{";
				$f[]="		define(\"COOKIE_NAME\",\"ZARAFA_WEBAPP\");";
				$f[]="	}";
				$f[]="	";
				//$f[]="	// Defines the theme that should be used, is a subdirectory under the 'layout' directory.";
				$f[]="	define(\"THEME_COLOR\", \"default\");";
				$f[]="	";
				//$f[]="	// Defines the base url and end with a slash.";
				$f[]="	\$base_url = dirname(\$_SERVER[\"PHP_SELF\"]);";
				$f[]="	if(substr(\$base_url,-1)!=\"/\") \$base_url .=\"/\";";
				$f[]="	define(\"BASE_URL\", \$base_url);";
				$f[]="	";
				//$f[]="	// Defines the base path on the server, terminated by a slash";
				$f[]="	define('BASE_PATH', dirname(\$_SERVER['SCRIPT_FILENAME']) . \"/\");";
				$f[]="";
				//$f[]="	// Defines the temp path (absolute). Here uploaded attachments will be saved.";
				//$f[]="	// The web client doesn't work without this directory.";
				$f[]="	define(\"TMP_PATH\", \"$this->WORKING_DIRECTORY/tmp\");";
				$f[]="";
				$f[]="	// Define the server paths";
				$f[]="	set_include_path(BASE_PATH. PATH_SEPARATOR . ";
				$f[]="	                 BASE_PATH.\"server/PEAR/\" .  PATH_SEPARATOR . ";
				$f[]="	                 \"/usr/share/php/\");";
				$f[]="";
				//$f[]="	// Define the relative URL for dialogs, this string is appended with HTTP GET arguments";
				$f[]="	define(\"DIALOG_URL\", \"index.php?load=dialog&\");";
				//$f[]="	// Define the relative URL for Drag and drop file upload";
				$f[]="	define(\"DND_FILEUPLOAD_URL\", \"index.php?load=upload_attachment&\");";
				//$f[]="	// Define the path to the plugin directory (No slash at the end)";
				$f[]="	define(\"PATH_PLUGIN_DIR\", \"plugins\");";
				//$f[]="	// Enable the plugins";
				$f[]="	define(\"ENABLE_PLUGINS\", true);";
				//$f[]="	// Define list of disabled plugins separated by semicolon";
				$f[]="	define(\"DISABLED_PLUGINS_LIST\", 'browsercompatibility;feedback;pdfbox;webappmanual;webodf;zperformance');";
				//$f[]="	// Disable PHP5>PHP4 compatibility, this could segfault apache with xml_parser_free";
				$f[]="	ini_set('zend.ze1_compatibility_mode', false);";
				//$f[]="	// Set addressbook for GAB not to show any users unless searching for a specific user";
				$f[]="	define(\"DISABLE_FULL_GAB\", false);";
				//$f[]="	// Set the threshold for the addressnook to only show a full contactlist when the number of rows ";
				//$f[]="	// do not exeed this threshold. Otherwise the user can only use the search. Enter any number above";
				//$f[]="	// zero to set the threshold or -1 to always show the list or 0 to always hide the full list.";
				$f[]="	define(\"DISABLE_FULL_CONTACTLIST_THRESHOLD\", -1);";
				//$f[]="	// Set addressbook to use an alphabetbar to only display items that start with the selected letter.";
				$f[]="	define('ENABLE_GAB_ALPHABETBAR', false);";
				//$f[]="	// Set true to show public folders in hierarchy, false will disable public folders in hierarchy.";
				$f[]="	define('ENABLE_PUBLIC_FOLDERS', true);";
//				$f[]="	// Remove delete permanently button from restore items dialog.";
				$f[]="	define('DISABLE_DELETE_IN_RESTORE_ITEMS', false);";
	//			$f[]="	// Booking method (true = direct booking, false = send meeting request)";
				$f[]="	define('ENABLE_DIRECT_BOOKING', true);";
				//Memory usage and timeouts 
		//		$f[]="	// This sets the maximum time in seconds that is allowed to run before it is terminated by the parser.	";
				$f[]="	ini_set('max_execution_time', 300); // 5 minutes";
//				$f[]="	// BLOCK_SIZE (in bytes) is used for attachments by mapi_stream_read/mapi_stream_write";
				$f[]="	define('BLOCK_SIZE', 1048576);";
//				$f[]="	// This value is used by the webclient to prevent a session timeout (in milliseconds)";
				$f[]="	define('CLIENT_TIMEOUT', 5*60*1000);";
	//			$f[]="	// Time that static files may exist in the client's cache (13 weeks)";
				$f[]="	define('EXPIRES_TIME', 60*60*24*7*13);";
		//		$f[]="	// Time that attachments are allowed to survive (in seconds)";
				$f[]="	define('UPLOADED_ATTACHMENT_MAX_LIFETIME', 6*60*60);";
			//	$f[]="	// Define maximum nos of file can be attached at a time.";
				$f[]="	define('FILE_UPLOAD_LIMIT', 50);";
				//$f[]="	// Define maximum nos of file can be in uploading queue at a time.";
				$f[]="	define('FILE_QUEUE_LIMIT', 50);";
				//$f[]="	// When set to true, enable the multi-upload feature of the attachment dialog. This has the following caveats:";
				//$f[]="	// - In FireFox, you can only upload to HTTPS when the certificate is recognized as an official (not self-signed";
				//$f[]="	//   SSL certificate)";
				//$f[]="	// - In Linux, some versions of flash do not support this feature and can crash during upload. Updating to the latest";
				//$f[]="	//   version of flash should fix the issue.";
				//$f[]="	// - In Windows, upload fails if the internet status is 'offline' - open internet explorer to reconnect";
				$f[]="	define(\"ENABLE_MULTI_UPLOAD\", false);";
				$f[]="	define('FCKEDITOR_PATH',dirname(\$_SERVER['SCRIPT_FILENAME']).\"/client/widgets/fckeditor\");";
				$f[]="	define('FCKEDITOR_JS_PATH','client/widgets/fckeditor');";
				if($ZarafaAspellEnabled==1){
					$asspellbin=$unix->find_program("aspell");
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", true);";
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"$asspellbin\");";	
					echo "Starting zarafa..............: Aspell checker is enabled\n";		
					
				}else{
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", false);";	
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"/usr/bin/aspell\");";
					echo "Starting zarafa..............: Aspell checker is disabled\n";
				}					

				//language
				$f[]="	define(\"LANGUAGE_DIR\", \"server/language/\");";
				$f[]="	";
				$f[]="	// Defines the default interface language. This can be overriden by the user.";
				$f[]="	// This language is also used on the login page";
				$f[]="	if (isset(\$_ENV['LANG']) && \$_ENV['LANG']!=\"C\"){";
				$f[]="		define('LANG', \$_ENV[\"LANG\"]); // This means the server environment language determines the web client language.";
				$f[]="	}else{";
				$f[]="		define('LANG', 'en_EN'); // default fallback language";
				$f[]="	}";
				$f[]="";
				//$f[]="	// List of languages that should be enabled in the logon";
				//$f[]="	// screen's language drop down.  Languages should be specified";
				//$f[]="	// using <languagecode>_<regioncode>[.UTF-8], and separated with";
				//$f[]="	// semicolon.  A list of available languages can be found in";
				//$f[]="	// the manual or by looking at the list of directories in";
				//$f[]="	// /usr/share/zarafa-webapp/server/language .";
				$f[]="	define(\"ENABLED_LANGUAGES\", \"de_DE;en_EN;en_US;fr_FR;he_IL;it_IT;nl_NL;ru_RU;zh_CN\");";
				$f[]="";
				//$f[]="	// Defines the default time zone, change e.g. to \"Europe/London\" when needed";
				$f[]="	if(function_exists(\"date_default_timezone_set\")) {";
				$f[]="		if(!ini_get('date.timezone')) {";
				$f[]="			date_default_timezone_set('Europe/London');";
				$f[]="		}";
				$f[]="	}";
				$f[]="";
				$f[]="";
				/* $f[]="	/**************************************\";
				$f[]="	* Debugging                            * ";
				$f[]="	\ ************************************** /";
				$f[]="";
				*/
				$f[]="	ini_set(\"display_errors\", false);";
				$f[]="	error_reporting(0);";
				$f[]="	";
				$f[]="	if (file_exists(\"debug.php\")){";
				$f[]="		include(\"debug.php\");";
				$f[]="	}else{";
				$f[]="		// define empty dump function in case we still use it somewhere";
				$f[]="		function dump(){}";
				$f[]="	}";
				$f[]="?>";				
				
				if($Otherdirectory==null){$Otherdirectory=$this->WORKING_DIRECTORY;}
				
				writelogs("[$this->servername]: config.php = $Otherdirectory/config.php",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if(is_file("$Otherdirectory/config.php")){@unlink("$Otherdirectory/config.php");}
				@file_put_contents("$Otherdirectory/config.php",@implode("\n",$f));
				$cmd="$chown -R $apacheusername $Otherdirectory";
				writelogs("[$this->servername]: config.php = $Otherdirectory/config.php -> `$cmd`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				shell_exec($cmd);
				writelogs("[$this->servername]: Start Apache....",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$this->ZarafaPluginJabberd();
				
		}
		
		private function ZarafaPluginJabberd(){
			if(!$this->AS_ROOT){return;}
			
			if(!is_file("$this->WORKING_DIRECTORY/plugins/xmpp/config.php")){writelogs("[$this->servername]: $this->WORKING_DIRECTORY/plugins/xmpp/config.php no such file",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
			$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
			$ZarafaXMPPDomain=$PARAMS["ZarafaXMPPDomain"];
			if($ZarafaXMPPDomain==null){
				writelogs("[$this->servername]: ZarafaXMPPDomain not configured",__CLASS__.
				"/".__FUNCTION__,__FILE__,__LINE__);
				return ;
			}
			
			$f[]="<?php";
			$f[]="define('PLUGIN_XMPP_ENABLE', true);";
			$f[]="define('PLUGIN_XMPP_USER_DEFAULT_ENABLE',true);";
			$f[]="define('PLUGIN_XMPP_CONNECT_DOMAIN', '$ZarafaXMPPDomain');";
			$f[]="define('PLUGIN_XMPP_CONNECT_RESOURCE', 'zarafa-webaccess');";
			$f[]="define('PLUGIN_XMPP_CONNECT_HTTPBASE', '/http-bind');";
			$f[]="?>";
			@file_put_contents("$this->WORKING_DIRECTORY/plugins/xmpp/config.php", @implode("\n", $f));
		}
		
		
		private function InstallZarafaCheckConfig(){
				if(!$this->AS_ROOT){return;}
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				if(!isset($this->Params["ZARAFAWEB_PARAMS"])){$this->Params["ZARAFAWEB_PARAMS"]=array();}
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
				if(!isset($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$sock->GET_INFO("ZarafaWebNTLM");}
				if(!isset($PARAMS["ZarafaEnablePlugins"])){$PARAMS["ZarafaEnablePlugins"]=$sock->GET_INFO("ZarafaEnablePlugins");}
				if(!isset($PARAMS["ZarafaAspellEnabled"])){$PARAMS["ZarafaAspellEnabled"]=$sock->GET_INFO("ZarafaAspellEnabled");}
				if(!isset($PARAMS["EnableZarafaRemoteServer"])){$PARAMS["EnableZarafaRemoteServer"]=0;}
				if(!isset($PARAMS["EnableZarafaRemoteServerAddr"])){$PARAMS["EnableZarafaRemoteServerAddr"]=null;}
				if(!isset($PARAMS["EnableDebugMode"])){$PARAMS["EnableDebugMode"]=0;}
				if(!isset($PARAMS["zPushInside"])){$PARAMS["zPushInside"]=0;}
				if(!isset($PARAMS["EnableZarafaSecondInstance"])){$PARAMS["EnableZarafaSecondInstance"]=0;}	
				$this->Params["ZARAFAWEB_PARAMS"]=$PARAMS;
			}
		
		private function InstallZarafaCheckDefaultServer(){
			if(!$this->AS_ROOT){return;}
			$EnableZarafaRemoteServer=$this->Params["ZARAFAWEB_PARAMS"]["EnableZarafaRemoteServer"];
			$EnableZarafaSecondInstance=$this->Params["ZARAFAWEB_PARAMS"]["EnableZarafaSecondInstance"];
			$EnableZarafaRemoteServerAddr=$this->Params["ZARAFAWEB_PARAMS"]["EnableZarafaRemoteServerAddr"];
			
			if(!is_numeric($EnableZarafaSecondInstance)){$EnableZarafaSecondInstance=0;}
			if(!is_numeric($EnableZarafaRemoteServer)){$EnableZarafaRemoteServer=0;}
			$DEFAULT_SERVER="file:///var/run/zarafa";
			
			if($EnableZarafaRemoteServer==1){
				if($EnableZarafaRemoteServerAddr<>null){
					$DEFAULT_SERVER="http://$EnableZarafaRemoteServerAddr:236/zarafa";
				}
			}
			
				
			if($this->ForceInstanceZarafaID>0){
					include_once(dirname(__FILE__)."/class.zarafa-multi.inc");
					$ZF=new zarafamulti($this->ForceInstanceZarafaID);
					if($ZF->DEFAULT_SERVER_SOCKET<>null){
						$DEFAULT_SERVER=$ZF->DEFAULT_SERVER_SOCKET;
						$info="// ForceInstanceZarafaID=$this->ForceInstanceZarafaID";
					}
				}
			if($EnableZarafaSecondInstance==1){$DEFAULT_SERVER="file:///var/run/zarafa2";}
			return $DEFAULT_SERVER;
				
		}
		
		
		private function InstallZarafaConfigPHP($Otherdirectory=null){
				if(!$this->AS_ROOT){return;}
				if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
				$unix=$GLOBALS["CLASS_UNIX"];
				if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
				$cp=$unix->find_program("cp");
				$chown=$unix->find_program("chown");
				$apacheusername=$unix->APACHE_SRC_ACCOUNT();
				writelogs("[$this->servername]: Start WORKING_DIRECTORY = $this->WORKING_DIRECTORY",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: Start apacheusername = $apacheusername",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if(!is_dir("$this->WORKING_DIRECTORY/client/layout/img/i")){@mkdir("$this->WORKING_DIRECTORY/client/layout/img/i",0775,true);}
				$users=new usersMenus();
				$this->InstallZarafaCheckConfig();
				$PARAMS=$this->Params["ZARAFAWEB_PARAMS"];
				
				$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
				$ZarafaEnablePlugins=$PARAMS["ZarafaEnablePlugins"];
				$ZarafaAspellEnabled=$PARAMS["ZarafaAspellEnabled"];
				$EnableZarafaRemoteServer=$PARAMS["EnableZarafaRemoteServer"];
				$EnableZarafaRemoteServerAddr=$PARAMS["EnableZarafaRemoteServerAddr"];
				$EnableDebugMode=$PARAMS["EnableDebugMode"];
				
				if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
				if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
				if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
							
				if(!is_numeric($EnableDebugMode)){$EnableDebugMode=0;}
				if($EnableZarafaRemoteServerAddr==null){$EnableZarafaRemoteServer=0;}
				if(!$users->ASPELL_INSTALLED){$ZarafaAspellEnabled=0;}
				$DEFAULT_SERVER=$this->InstallZarafaCheckDefaultServer();

				writelogs("[$this->servername]: ZarafaWebNTLM = $ZarafaWebNTLM",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: ZarafaEnablePlugins = $ZarafaEnablePlugins",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: ZarafaAspellEnabled = $ZarafaAspellEnabled",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: EnableZarafaRemoteServerAddr = $EnableZarafaRemoteServerAddr",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: DEFAULT_SERVER = $DEFAULT_SERVER",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				writelogs("[$this->servername]: EnableDebugMode = $EnableDebugMode",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				

				
				
				/*
				if(!is_file("$this->WORKING_DIRECTORY/client/layout/img/i/logo.png")){shell_exec("$cp -f /usr/share/artica-postfix/ressources/templates/zarafa/i/logo.png $this->WORKING_DIRECTORY/client/layout/img/i/");}
				if(!is_file("$this->WORKING_DIRECTORY/client/login.bak.php")){shell_exec("$cp -f $this->WORKING_DIRECTORY/client/login.php $this->WORKING_DIRECTORY/client/login.back.php");}
				shell_exec("$cp -f /usr/share/artica-postfix/ressources/templates/zarafa/login.php $this->WORKING_DIRECTORY/client/login.php");
				*/
			
				$conf[]="<?php";
				$conf[]="ini_set(\"zend.ze1_compatibility_mode\", false);";
				$conf[]="ini_set(\"max_execution_time\", 300); // 5 minutes";
				$conf[]="ini_set(\"display_errors\", false);";
				$conf[]="define(\"CONFIG_CHECK\", TRUE);";
				$conf[]="define(\"DEFAULT_SERVER\",\"$DEFAULT_SERVER\");";
				$conf[]="define(\"SSLCERT_FILE\", NULL);";
				$conf[]="define(\"SSLCERT_PASS\", NULL);";
				if($ZarafaWebNTLM==1){
					$conf[]="define(\"LOGINNAME_STRIP_DOMAIN\", true);";
				}else{
					$conf[]="define(\"LOGINNAME_STRIP_DOMAIN\", false);";
				}
				$conf[]="if (isset(\$_GET[\"external\"]) && preg_match(\"/[a-z][a-z0-9_]+/i\",\$_GET[\"external\"])){define(\"COOKIE_NAME\",\$_GET[\"external\"]);}else{define(\"COOKIE_NAME\",\"ZARAFA_WEBACCESS\");}";
				$conf[]="define(\"THEME_COLOR\", \"default\");\$base_url = dirname(\$_SERVER[\"PHP_SELF\"]);if(substr(\$base_url,-1)!=\"/\") \$base_url .=\"/\";";
				$conf[]="define(\"BASE_URL\", \$base_url);";
				$conf[]="define(\"BASE_PATH\", dirname(\$_SERVER[\"SCRIPT_FILENAME\"]) . \"/\");";
				$conf[]="define(\"MIME_TYPES\", BASE_PATH . \"server/mimetypes.dat\");";
				$conf[]="define(\"TMP_PATH\", \"$this->WORKING_DIRECTORY/tmp\");";
				$conf[]="set_include_path(BASE_PATH. PATH_SEPARATOR . BASE_PATH.\"server/PEAR/\" .  PATH_SEPARATOR . \"/usr/share/php/\");";
				$conf[]="define(\"DIALOG_URL\", \"index.php?load=dialog&\");";
				$conf[]="define(\"DND_FILEUPLOAD_URL\", \"index.php?load=upload_attachment&\");";
				$conf[]="define(\"PATH_PLUGIN_DIR\", \"plugins\");";
				if(is_link("$this->WORKING_DIRECTORY/plugins")){@unlink("$this->WORKING_DIRECTORY/plugins");}
				@mkdir("$this->WORKING_DIRECTORY/plugins",0755,true);
				if($ZarafaEnablePlugins==1){
					$conf[]="define(\"ENABLE_PLUGINS\", true);";
				}else{
					$conf[]="define(\"ENABLE_PLUGINS\", false);";
				}
				$conf[]="define(\"DISABLED_PLUGINS_LIST\", \"browsercompatibility;feedback;pdfbox;webappmanual;webodf;zperformance\");";
				$conf[]="define(\"DISABLE_FULL_GAB\", false);";
				$conf[]="define(\"DISABLE_FULL_CONTACTLIST_THRESHOLD\", -1);";
				$conf[]="define(\"ENABLE_GAB_ALPHABETBAR\", false);";
				$conf[]="define(\"FREEBUSY_DAYBEFORE_COUNT\", 7);";
				$conf[]="define(\"FREEBUSY_NUMBEROFDAYS_COUNT\", 90);";
				$conf[]="define(\"BLOCK_SIZE\", 1048576);";
				$conf[]="define(\"CLIENT_TIMEOUT\", 5*60*1000);";
				$conf[]="define(\"EXPIRES_TIME\", 60*60*24*7*13);";
				$conf[]="define(\"UPLOADED_ATTACHMENT_MAX_LIFETIME\", 6*60*60);";
				$conf[]="define(\"FCKEDITOR_PATH\",dirname(\$_SERVER[\"SCRIPT_FILENAME\"]).\"/client/widgets/fckeditor\");";
				$conf[]="define(\"FCKEDITOR_JS_PATH\",\"client/widgets/fckeditor\");";
				
			
				if($ZarafaAspellEnabled==1){
					$asspellbin=$unix->find_program("aspell");
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", true);";
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"$asspellbin\");";	
					echo "Starting zarafa..............: Aspell checker is enabled\n";		
					
				}else{
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", false);";	
					$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"/usr/bin/aspell\");";
					echo "Starting zarafa..............: Aspell checker is disabled\n";
				}				
				

				$conf[]="define(\"FCKEDITOR_SPELLCHECKER_LANGUAGE\", FALSE); // set FALSE to use the language chosen by the user, but make sure that these languages are installed with aspell!";
				$conf[]="define(\"LANGUAGE_DIR\", \"server/language/\");";
				$conf[]="if (isset(\$_ENV[\"LANG\"]) && \$_ENV[\"LANG\"]!=\"C\"){";
				$conf[]="	define(\"LANG\", \$_ENV[\"LANG\"]); // This means the server environment language determines the web client language.";
				$conf[]="	}else{";
				$conf[]="define(\"LANG\", \"en_EN\"); // default fallback language";
				$conf[]="	}";
				$conf[]="";
				$conf[]="if (function_exists(\"date_default_timezone_set\")){date_default_timezone_set(\"Europe/London\");}";
				$conf[]="error_reporting(0);";
				$conf[]="if (file_exists(\"debug.php\")){include(\"debug.php\");}else{function dump(){}}";
				$conf[]="?>";
				if($Otherdirectory==null){$Otherdirectory=$this->WORKING_DIRECTORY;}
				
				@unlink("$Otherdirectory/debug.php");
				
				if($EnableDebugMode==1){
					$cmd="$cp /usr/share/artica-postfix/bin/install/zarafa/debug.php $Otherdirectory/debug.php";
					writelogs("[$this->servername]: $cmd",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
					shell_exec($cmd);
				}
				
				writelogs("[$this->servername]: config.php = $Otherdirectory/config.php",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if(is_file("$Otherdirectory/config.php")){@unlink("$Otherdirectory/config.php");}
				@file_put_contents("$Otherdirectory/config.php",@implode("\n",$conf));
				$cmd="$chown -R $apacheusername $Otherdirectory";
				writelogs("[$this->servername]: config.php = $Otherdirectory/config.php -> `$cmd`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				shell_exec($cmd);
				
				writelogs("[$this->servername]: Start Apache....",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$this->ReloadApache();
				
				
		}
		
		private function ReloadApache(){
			if(!$this->AS_ROOT){return;}
			if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
			$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
			if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			if(strlen($apache2ctl)<5){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
			$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_PID_PATH();
			writelogs("[$this->servername]: apache2ctl = $apache2ctl",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
			writelogs("[$this->servername]: LOCATE_APACHE_CONF_PATH = $LOCATE_APACHE_CONF_PATH",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$pid=@file_get_contents($pid_path);
			writelogs("[$this->servername]: pid_path = $pid_path -> $pid",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
				$cmd="$nohup $apache2ctl -f $LOCATE_APACHE_CONF_PATH -k graceful >/dev/null 2>&1 &";
			}else{
				$cmd="$nohup $apache2ctl -f $LOCATE_APACHE_CONF_PATH -k start >/dev/null 2>&1 &";
			}
			writelogs("[$this->servername]: $cmd",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
		}
		
		
		private function TestSUGAR(){
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Testing SugerCRM installation\n";}
			$sugar=new SugarCRM_install($this->servername);
			if(!$sugar->isFirstInstalled()){$sugar->CheckInstall();}
			
		}
		private function TestPIWIGO(){
			include_once(dirname(__FILE__)."/class.piwigo.inc");
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] Testing Piwigo installation\n";}
			$sugar=new piwigo($this->servername);
			$sugar->verifinstall();
			
		}	
		private function TestFileZ(){
			include_once(dirname(__FILE__)."/class.filezweb.inc");
			$cld=new filez_www($this->servername);
			$cld->verifinstall();	
				
		}		
		private function SSLEngine2(){
			$apache_certificate=new apache_certificate($this->sslcertificate);
			return $apache_certificate->build();
			
			
		}

		
		public function SSLEngine(){
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] sslcertificate = $this->sslcertificate\n";}
			$apache_certificate=new apache_certificate($this->sslcertificate);
			return $apache_certificate->build();
		}
		
		
		private function ROUNDCUBE(){
			if(!$this->AS_ROOT){return;}
			echo "$this->PrefixOutput [CLASS/".__LINE__."] Checking RoundCube application\n";
			include_once(dirname(__FILE__)."/class.roundcube.freewebs.inc");
			$rond=new roundcube_freewebs($this->servername);
			$rond->build();
		}
		
		
		
		function phpmyadmin(){
			if($this->WebCopyID>0){return;}
			if($this->AS_ROOT){echo "$this->PrefixOutput [CLASS/".__LINE__."] directory \"$this->WORKING_DIRECTORY\"\n";}
			$phpmyadmin_dir="$this->WORKING_DIRECTORY/phpmyadmin";
			if($this->IsGroupWareFromArtica()){return;}
			if($this->groupware=="ZARAFA"){return;}
			if($this->groupware=="ZARAFA_MOBILE"){return;}
			if($this->groupware=="WEBAPP"){return;}
			if($this->groupware=="SUGAR"){$this->TestSUGAR();return;}
			if($this->groupware=="PIWIGO"){$this->TestPIWIGO();return;}
			if($this->groupware=="APP_FILEZ_WEB"){$this->TestFileZ();return;}
			if($this->groupware=="ROUNDCUBE"){$this->ROUNDCUBE();return;}
			
			if(!is_numeric($this->useMysql)){$this->useMysql=0;}
			
			
			
			if($this->useMysql==0){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] MySQL is not enabled\n";
				if(is_dir("$phpmyadmin_dir")){
					echo "$this->PrefixOutput [CLASS/".__LINE__."] removing $phpmyadmin_dir\n";
					shell_exec("/bin/rm -rf $phpmyadmin_dir");
				}
				return;
				
			}
			if(is_file("/usr/share/phpmyadmin/index.php")){
				echo "$this->PrefixOutput [CLASS/".__LINE__."] installing phpmyadmin /usr/share/phpmyadmin/index.php no such file\n";
				return;
			}
			
			if(!is_dir("/usr/share/phpmyadmin")){
				echo "Starting......: ".date("H:i:s")." Apache checks `/usr/share/phpmyadmin` no such sources\n";
				return;}
			
			
			echo "$this->PrefixOutput [CLASS/".__LINE__."] MySQL is enabled\n";
			echo "Starting......: ".date("H:i:s")." Apache checks $phpmyadmin_dir\n";
			
			
			$q=new mysql();
			@mkdir("$phpmyadmin_dir",0666,true);
			$blowfish_secret=md5($this->servername);
			shell_exec("/bin/cp -rf /usr/share/phpmyadmin/* $phpmyadmin_dir/ >/dev/null 2>&1");
			$f[]="<?php";
			$f[]="\$cfg['blowfish_secret'] = '$blowfish_secret';";
			$f[]="\$i = 0;";
			$f[]="\$i++;";
			$f[]="";
			$f[]="\$cfg['Servers'][\$i]['auth_type'] = 'cookie';";
			$f[]="\$cfg['Servers'][\$i]['host'] = '$q->mysql_server';";
			$f[]="\$cfg['Servers'][\$i]['port'] = '$q->mysql_port';";
			
			$f[]="\$cfg['Servers'][\$i]['connect_type'] = 'tcp';";
			$f[]="\$cfg['Servers'][\$i]['compress'] = false;";
			$f[]="\$cfg['Servers'][\$i]['extension'] = 'mysql';";
			$f[]="\$cfg['Servers'][\$i]['AllowNoPassword'] = false;";
			$f[]="\$cfg['Servers'][\$i]['bs_garbage_threshold'] = 50;";
			$f[]="\$cfg['Servers'][\$i]['bs_repository_threshold'] = '32M';";
			$f[]="\$cfg['Servers'][\$i]['bs_temp_blob_timeout'] = 600;";
			$f[]="\$cfg['Servers'][\$i]['bs_temp_log_threshold'] = '32M';";
			$f[]="\$cfg['Servers'][\$i]['only_db']='$this->mysql_database';";
			$f[]="\$cfg['Servers'][\$i]['hide_db'] = 'information_schema';";			
			$f[]="\$cfg['UploadDir'] = 'upload';";
			$f[]="\$cfg['SaveDir'] = 'upload';";
			$f[]="\$cfg['DefaultLang'] = 'en-utf-8';";
			$f[]="\$cfg['ServerDefault'] = 1;";
			$f[]="\$cfg['AllowUserDropDatabase']=FALSE;";
			$f[]="\$cfg['ShowPhpInfo']=true;";
			$f[]="\$cfg['DisplayDatabasesList']=FALSE;";
			$f[]="\$cfg['ShowServerInfo']=FALSE;";
			$f[]="\$cfg['ShowCreateDb']=FALSE;";
			$f[]="\$cfg['LeftDisplayServers']=FALSE;";
			$f[]="\$cfg['ShowBlob']=TRUE;";
			$f[]="\$cfg['DefaultTabServer']='main.php';";
			
			$f[]="?>\n";		
			if(!$this->AS_ROOT){return;}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			if(!isset($GLOBALS["apacheusername"])){$unix=new unix();$GLOBALS["apacheusername"]=$unix->APACHE_SRC_ACCOUNT();}
			$apacheusername=$GLOBALS["apacheusername"];
			$varWwwPerms=$sock->GET_INFO("varWwwPerms");
			if($varWwwPerms==null){$varWwwPerms="755";}
			
			
			@file_put_contents("$phpmyadmin_dir/config.inc.php",@implode("\n",$f));
			shell_exec("/bin/chown root:root $phpmyadmin_dir/config.inc.php");
			shell_exec("/bin/chmod -R 755 $phpmyadmin_dir/phpmyadmin");
			shell_exec("/bin/chmod 644 $phpmyadmin_dir/phpmyadmin/config.inc.php");
			shell_exec("/bin/chown -R root:root $phpmyadmin_dir");
			@mkdir("$phpmyadmin_dir/upload",0755,true);
			if($apacheusername==null){
				shell_exec("/bin/chmod $varWwwPerms /var/www &");
				shell_exec("/bin/chmod -R 777 $phpmyadmin_dir/upload");
			}else{
				shell_exec("/bin/chmod $varWwwPerms /var/www &");
				$this->chown($phpmyadmin_dir/upload);
				
			}
			unset($f);
		}			
	
	
	private function build_php_ini(){
			if(!$this->AS_ROOT){return;}
			$this->php_defaults();
			$this->php_values();
			
			$f=explode("\n",@file_get_contents($this->locate_php_ini()));
				while(list($index,$v) = each($f)){
		 			if(preg_match("#extension_dir.*?=(.+)#", $v,$re)){
		 				$this->Params["PHP_VALUES"]["extension_dir"]=trim($re[1]);
		 				break;
		 			}
	 			}			
			
			
			$f[]="[PHP]";
			
			$f[]="magic_quotes_gpc = \"{$this->Params["PHP_VALUES"]["magic_quotes_gpc"]}\"";
			$f[]="safe_mode = \"{$this->Params["PHP_VALUES"]["safe_mode"]}\"";
			$f[]="safe_mode_gid = \"{$this->Params["PHP_VALUES"]["safe_mode_gid"]}\"";
			$f[]="engine = \"{$this->Params["PHP_VALUES"]["engine"]}\"";
			$f[]="precision = \"{$this->Params["PHP_VALUES"]["precision"]}\"";
			$f[]="y2k_compliance = \"{$this->Params["PHP_VALUES"]["y2k_compliance"]}\"";
			$f[]="output_buffering = \"{$this->Params["PHP_VALUES"]["output_buffering"]}\"";
			$f[]="serialize_precision = \"{$this->Params["PHP_VALUES"]["serialize_precision"]}\"";
			$f[]="expose_php = \"{$this->Params["PHP_VALUES"]["expose_php"]}\"";
			$f[]="max_execution_time = \"{$this->Params["PHP_VALUES"]["max_execution_time"]}\"";
			$f[]="max_input_time = \"{$this->Params["PHP_VALUES"]["max_input_time"]}\"";
			$f[]="memory_limit = \"{$this->Params["PHP_VALUES"]["memory_limit"]}\"";
			$f[]="error_reporting = \"{$this->Params["PHP_VALUES"]["error_reporting"]}\"";
			$f[]="display_errors = \"{$this->Params["PHP_VALUES"]["display_errors"]}\"";
			$f[]="display_startup_errors = \"{$this->Params["PHP_VALUES"]["display_startup_errors"]}\"";
			$f[]="log_errors = \"{$this->Params["PHP_VALUES"]["log_errors"]}\"";
			$f[]="log_errors_max_len = \"{$this->Params["PHP_VALUES"]["log_errors_max_len"]}\"";
			$f[]="ignore_repeated_errors = \"{$this->Params["PHP_VALUES"]["ignore_repeated_errors"]}\"";
			$f[]="ignore_repeated_source = \"{$this->Params["PHP_VALUES"]["ignore_repeated_source"]}\"";
			$f[]="report_memleaks = \"{$this->Params["PHP_VALUES"]["report_memleaks"]}\"";
			$f[]="track_errors = \"{$this->Params["PHP_VALUES"]["track_errors"]}\"";
			$f[]="error_append_string = \"{$this->Params["PHP_VALUES"]["error_append_string"]}\"";
			$f[]="html_errors = \"{$this->Params["PHP_VALUES"]["html_errors"]}\"";
			$f[]="error_log = \"{$this->Params["PHP_VALUES"]["error_log"]}\"";
			$f[]="variables_order = \"{$this->Params["PHP_VALUES"]["variables_order"]}\"";
			$f[]="register_argc_argv = \"{$this->Params["PHP_VALUES"]["register_argc_argv"]}\"";
			$f[]="auto_globals_jit = \"{$this->Params["PHP_VALUES"]["auto_globals_jit"]}\"";
			$f[]="post_max_size = \"{$this->Params["PHP_VALUES"]["post_max_size"]}\"";
			$f[]="default_mimetype = \"{$this->Params["PHP_VALUES"]["default_mimetype"]}\"";
			$f[]="default_charset = \"{$this->Params["PHP_VALUES"]["default_charset"]}\"";
			$f[]="unicode.semantics = \"{$this->Params["PHP_VALUES"]["unicode.semantics"]}\"";
			$f[]="unicode.runtime_encoding = \"{$this->Params["PHP_VALUES"]["unicode.runtime_encoding"]}\"";
			$f[]="unicode.script_encoding = \"{$this->Params["PHP_VALUES"]["unicode.script_encoding"]}\"";
			$f[]="unicode.output_encoding = \"{$this->Params["PHP_VALUES"]["unicode.output_encoding"]}\"";
			$f[]="unicode.from_error_mode = \"{$this->Params["PHP_VALUES"]["unicode.from_error_mode"]}\"";
			$f[]="unicode.from_error_subst_char = \"{$this->Params["PHP_VALUES"]["unicode.from_error_subst_char"]}\"";
			$f[]="include_path = \"{$this->Params["PHP_VALUES"]["include_path"]}\"";
			$f[]="extension_dir = \"{$this->Params["PHP_VALUES"]["extension_dir"]}\"";
			$f[]="cgi.force_redirect = \"{$this->Params["PHP_VALUES"]["cgi.force_redirect"]}\"";
			$f[]="cgi.fix_pathinfo = \"{$this->Params["PHP_VALUES"]["cgi.fix_pathinfo"]}\"";
			$f[]="file_uploads = \"{$this->Params["PHP_VALUES"]["file_uploads"]}\"";
			$f[]="upload_tmp_dir = \"{$this->Params["PHP_VALUES"]["upload_tmp_dir"]}\"";
			$f[]="upload_max_filesize = \"{$this->Params["PHP_VALUES"]["upload_max_filesize"]}\"";
			$f[]="allow_url_fopen = \"{$this->Params["PHP_VALUES"]["allow_url_fopen"]}\"";
			$f[]="allow_url_include = \"{$this->Params["PHP_VALUES"]["allow_url_include"]}\"";
			$f[]="from = \"{$this->Params["PHP_VALUES"]["from"]}\"";
			$f[]="default_socket_timeout = \"{$this->Params["PHP_VALUES"]["default_socket_timeout"]}\"";
			$f[]="safe_mode = \"{$this->Params["PHP_VALUES"]["safe_mode"]}\"";
			$f[]="[Date]";
			$f[]="date.timezone = \"{$this->Params["PHP_VALUES"]["date.timezone"]}\"";
			$f[]="[filter]";
			$f[]="[iconv]";
			$f[]="iconv.input_encoding = \"{$this->Params["PHP_VALUES"]["iconv.input_encoding"]}\"";
			$f[]="iconv.internal_encoding = \"{$this->Params["PHP_VALUES"]["iconv.internal_encoding"]}\"";
			$f[]="iconv.output_encoding = \"{$this->Params["PHP_VALUES"]["iconv.output_encoding"]}\"";
			$f[]="[Syslog]";
			$f[]="define_syslog_variables = \"{$this->Params["PHP_VALUES"]["define_syslog_variables"]}\"";
			$f[]="[mail function]";
			$f[]="[SQL]";
			$f[]="sql.safe_mode = \"{$this->Params["PHP_VALUES"]["sql.safe_mode"]}\"";
			$f[]="[ODBC]";
			$f[]="odbc.allow_persistent = \"{$this->Params["PHP_VALUES"]["odbc.allow_persistent"]}\"";
			$f[]="odbc.check_persistent = \"{$this->Params["PHP_VALUES"]["odbc.check_persistent"]}\"";
			$f[]="odbc.max_persistent = \"{$this->Params["PHP_VALUES"]["odbc.max_persistent"]}\"";
			$f[]="odbc.max_links = \"{$this->Params["PHP_VALUES"]["odbc.max_links"]}\"";
			$f[]="odbc.defaultlrl = \"{$this->Params["PHP_VALUES"]["odbc.defaultlrl"]}\"";
			$f[]="odbc.defaultbinmode = \"{$this->Params["PHP_VALUES"]["odbc.defaultbinmode"]}\"";
			$f[]="[MySQL]";
			$f[]="mysql.allow_persistent = \"{$this->Params["PHP_VALUES"]["mysql.allow_persistent"]}\"";
			$f[]="mysql.max_persistent = \"{$this->Params["PHP_VALUES"]["mysql.max_persistent"]}\"";
			$f[]="mysql.max_links = \"{$this->Params["PHP_VALUES"]["mysql.max_links"]}\"";
			$q=new mysql();
			if($this->mysql_username==null){$mysql_username=$q->mysql_admin;$mysql_password=$q->mysql_password;}
			if($this->mysql_username<>null){$mysql_username=$this->mysql_username;$mysql_password=$this->mysql_password;}
			
			$f[]="mysql.default_port = \"$q->mysql_port\"";
			$f[]="mysql.default_socket = \"/var/run/mysqld/mysqld.sock\"";
			$f[]="mysql.default_host = \"$q->mysql_server\"";
			$f[]="mysql.default_user = \"$mysql_username\"";
			$f[]="mysql.default_password = \"$mysql_password\"";
			$f[]="mysql.connect_timeout = \"{$this->Params["PHP_VALUES"]["mysql.connect_timeout"]}\"";
			$f[]="mysql.trace_mode = \"{$this->Params["PHP_VALUES"]["mysql.trace_mode"]}\"";
			$f[]="[LDAP]";
			$f[]="ldap.max_links = \"{$this->Params["PHP_VALUES"]["ldap.max_links"]}\"";
			$f[]="ldap.allow_persistent = \"{$this->Params["PHP_VALUES"]["ldap.allow_persistent"]}\"";
			$f[]="ldap.check_persistent = \"{$this->Params["PHP_VALUES"]["ldap.check_persistent"]}\"";
			$f[]="[MySQLi]";
			$f[]="mysqli.max_links = \"{$this->Params["PHP_VALUES"]["mysqli.max_links"]}\"";
			$f[]="mysqli.default_port = \"$q->mysql_port\"";
			$f[]="mysqli.default_socket = \"/var/run/mysqld/mysqld.sock\"";
			$f[]="mysqli.default_host = \"$q->mysql_server\"";
			$f[]="mysqli.default_user = \"$mysql_username\"";
			$f[]="mysqli.default_pw = \"$mysql_password\"";
			$f[]="mysqli.reconnect = \"{$this->Params["PHP_VALUES"]["mysqli.reconnect"]}\"";
			$f[]="[mSQL]";
			$f[]="msql.allow_persistent = \"{$this->Params["PHP_VALUES"]["msql.allow_persistent"]}\"";
			$f[]="msql.max_persistent = \"{$this->Params["PHP_VALUES"]["msql.max_persistent"]}\"";
			$f[]="msql.max_links = \"{$this->Params["PHP_VALUES"]["msql.max_links"]}\"";
			$f[]="[OCI8]";
			$f[]="[PostgresSQL]";
			$f[]="[Sybase]";
			$f[]="[Sybase-CT]";
			$f[]="[bcmath]";
			$f[]="[browscap]";
			$f[]="[Informix]";
			$f[]="[Session]";
			$f[]="session.save_handler = \"{$this->Params["PHP_VALUES"]["session.save_handler"]}\"";
			$f[]="session.save_path = \"{$this->Params["PHP_VALUES"]["session.save_path"]}\"";
			$f[]="session.use_cookies = \"{$this->Params["PHP_VALUES"]["session.use_cookies"]}\"";
			$f[]="session.use_only_cookies = \"{$this->Params["PHP_VALUES"]["session.use_only_cookies"]}\"";
			$f[]="session.name = \"{$this->Params["PHP_VALUES"]["session.name"]}\"";
			$f[]="session.auto_start = \"{$this->Params["PHP_VALUES"]["session.auto_start"]}\"";
			$f[]="session.cookie_lifetime = \"{$this->Params["PHP_VALUES"]["session.cookie_lifetime"]}\"";
			$f[]="session.cookie_path = \"{$this->Params["PHP_VALUES"]["session.cookie_path"]}\"";
			$f[]="session.serialize_handler = \"{$this->Params["PHP_VALUES"]["session.serialize_handler"]}\"";
			$f[]="session.gc_probability = \"{$this->Params["PHP_VALUES"]["session.gc_probability"]}\"";
			$f[]="session.gc_divisor = \"{$this->Params["PHP_VALUES"]["session.gc_divisor"]}\"";
			$f[]="session.gc_maxlifetime = \"{$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]}\"";
			$f[]="session.entropy_length = \"{$this->Params["PHP_VALUES"]["session.entropy_length"]}\"";
			$f[]="session.cache_limiter = \"{$this->Params["PHP_VALUES"]["session.cache_limiter"]}\"";
			$f[]="session.cache_expire = \"{$this->Params["PHP_VALUES"]["session.cache_expire"]}\"";
			$f[]="session.use_trans_sid = \"{$this->Params["PHP_VALUES"]["session.use_trans_sid"]}\"";
			$f[]="session.hash_function = \"{$this->Params["PHP_VALUES"]["session.hash_function"]}\"";
			$f[]="session.bug_compat_warn = \"{$this->Params["PHP_VALUES"]["session.bug_compat_warn"]}\"";
			$f[]="session.hash_bits_per_character = \"{$this->Params["PHP_VALUES"]["session.hash_bits_per_character"]}\"";
			$f[]="[MSSQL]";
			$f[]="mssql.allow_persistent = \"{$this->Params["PHP_VALUES"]["mssql.allow_persistent"]}\"";
			$f[]="mssql.max_persistent = \"{$this->Params["PHP_VALUES"]["mssql.max_persistent"]}\"";
			$f[]="mssql.max_links = \"{$this->Params["PHP_VALUES"]["mssql.max_links"]}\"";
			$f[]="mssql.min_error_severity = \"{$this->Params["PHP_VALUES"]["mssql.min_error_severity"]}\"";
			$f[]="mssql.min_message_severity = \"{$this->Params["PHP_VALUES"]["mssql.min_message_severity"]}\"";
			$f[]="mssql.compatability_mode = \"{$this->Params["PHP_VALUES"]["mssql.compatability_mode"]}\"";
			$f[]="mssql.connect_timeout = \"{$this->Params["PHP_VALUES"]["mssql.connect_timeout"]}\"";
			$f[]="mssql.timeout = \"{$this->Params["PHP_VALUES"]["mssql.timeout"]}\"";
			$f[]="mssql.textlimit = \"{$this->Params["PHP_VALUES"]["mssql.textlimit"]}\"";
			$f[]="mssql.textsize = \"{$this->Params["PHP_VALUES"]["mssql.textsize"]}\"";
			$f[]="mssql.batchsize = \"{$this->Params["PHP_VALUES"]["mssql.batchsize"]}\"";
			$f[]="mssql.datetimeconvert = \"{$this->Params["PHP_VALUES"]["mssql.datetimeconvert"]}\"";
			$f[]="mssql.secure_connection = \"{$this->Params["PHP_VALUES"]["mssql.secure_connection"]}\"";
			$f[]="mssql.max_procs = \"{$this->Params["PHP_VALUES"]["mssql.max_procs"]}\"";
			$f[]="mssql.charset = \"{$this->Params["PHP_VALUES"]["mssql.charset"]}\"";
			$f[]="[Assertion]";
			$f[]="[COM]";
			$f[]="[mbstring]";
			$f[]="[FrontBase]";
			$f[]="[gd]";
			$f[]="[exif]";
			$f[]="[Tidy]";
			$f[]="tidy.clean_output = \"{$this->Params["PHP_VALUES"]["tidy.clean_output"]}\"";
			$f[]="[soap]";
			$f[]="soap.wsdl_cache_ttl = \"{$this->Params["PHP_VALUES"]["soap.wsdl_cache_ttl"]}\"";
			
			if(!isset($GLOBALS["PHP_EXTENTIONS"])){
				$t=explode("\n",@file_get_contents($this->locate_php_ini()));
				while(list($index,$v) = each($t)){
					if(trim($v)==null){continue;}
		 			if(preg_match("#extension.*?=(.+?).so#", $v,$rie)){
		 				$GLOBALS["PHP_EXTENTIONS"][trim($rie[1])]="extension=".trim($rie[1]).".so";
		 			}
	 			}
			}
			
			
			print_r($GLOBALS["PHP_EXTENTIONS"]);
			
	 		$f[]=@implode("\n", $GLOBALS["PHP_EXTENTIONS"]);
	 		return @implode("\n", $f);
			
			
	}

	private function locate_php_ini(){
	  $f[]="/etc/php.ini";
	  $f[]="/etc/php5/cli/php.ini";
	  $f[]="/etc/php5/cgi/php.ini";
	  $f[]="/etc/php5/apache2/php.ini";
	  $f[]="/etc/php/php.ini";
	  $f[]="/etc/php-cgi-fcgi.ini";
	  $f[]="/etc/php5/fastcgi/php.ini"; 
	  while (list ($num, $filename) = each ($f) ){if(is_file($filename)){return $filename;}}
	}
	
	
public function php_defaults(){
		if(!isset($this->Params["PHP_VALUES"]["safe_mode"])){$this->Params["PHP_VALUES"]["safe_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["safe_mode_gid"])){$this->Params["PHP_VALUES"]["safe_mode_gid"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["engine"])){$this->Params["PHP_VALUES"]["engine"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["precision"])){$this->Params["PHP_VALUES"]["precision"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["y2k_compliance"])){$this->Params["PHP_VALUES"]["y2k_compliance"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["output_buffering"])){$this->Params["PHP_VALUES"]["output_buffering"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["serialize_precision"])){$this->Params["PHP_VALUES"]["serialize_precision"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["expose_php"])){$this->Params["PHP_VALUES"]["expose_php"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["max_execution_time"])){$this->Params["PHP_VALUES"]["max_execution_time"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["max_input_time"])){$this->Params["PHP_VALUES"]["max_input_time"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["memory_limit"])){$this->Params["PHP_VALUES"]["memory_limit"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["error_reporting"])){$this->Params["PHP_VALUES"]["error_reporting"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["display_errors"])){$this->Params["PHP_VALUES"]["display_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["display_startup_errors"])){$this->Params["PHP_VALUES"]["display_startup_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["log_errors"])){$this->Params["PHP_VALUES"]["log_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["log_errors_max_len"])){$this->Params["PHP_VALUES"]["log_errors_max_len"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["ignore_repeated_errors"])){$this->Params["PHP_VALUES"]["ignore_repeated_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["ignore_repeated_source"])){$this->Params["PHP_VALUES"]["ignore_repeated_source"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["report_memleaks"])){$this->Params["PHP_VALUES"]["report_memleaks"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["track_errors"])){$this->Params["PHP_VALUES"]["track_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["error_append_string"])){$this->Params["PHP_VALUES"]["error_append_string"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["html_errors"])){$this->Params["PHP_VALUES"]["html_errors"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["error_log"])){$this->Params["PHP_VALUES"]["error_log"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["variables_order"])){$this->Params["PHP_VALUES"]["variables_order"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["register_argc_argv"])){$this->Params["PHP_VALUES"]["register_argc_argv"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["auto_globals_jit"])){$this->Params["PHP_VALUES"]["auto_globals_jit"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["post_max_size"])){$this->Params["PHP_VALUES"]["post_max_size"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["default_mimetype"])){$this->Params["PHP_VALUES"]["default_mimetype"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["default_charset"])){$this->Params["PHP_VALUES"]["default_charset"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.semantics"])){$this->Params["PHP_VALUES"]["unicode.semantics"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.runtime_encoding"])){$this->Params["PHP_VALUES"]["unicode.runtime_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.script_encoding"])){$this->Params["PHP_VALUES"]["unicode.script_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.output_encoding"])){$this->Params["PHP_VALUES"]["unicode.output_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.from_error_mode"])){$this->Params["PHP_VALUES"]["unicode.from_error_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["unicode.from_error_subst_char"])){$this->Params["PHP_VALUES"]["unicode.from_error_subst_char"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["include_path"])){$this->Params["PHP_VALUES"]["include_path"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["extension_dir"])){$this->Params["PHP_VALUES"]["extension_dir"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["cgi.force_redirect"])){$this->Params["PHP_VALUES"]["cgi.force_redirect"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["cgi.fix_pathinfo"])){$this->Params["PHP_VALUES"]["cgi.fix_pathinfo"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["file_uploads"])){$this->Params["PHP_VALUES"]["file_uploads"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["upload_tmp_dir"])){$this->Params["PHP_VALUES"]["upload_tmp_dir"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["upload_max_filesize"])){$this->Params["PHP_VALUES"]["upload_max_filesize"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["allow_url_fopen"])){$this->Params["PHP_VALUES"]["allow_url_fopen"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["allow_url_include"])){$this->Params["PHP_VALUES"]["allow_url_include"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["from"])){$this->Params["PHP_VALUES"]["from"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["default_socket_timeout"])){$this->Params["PHP_VALUES"]["default_socket_timeout"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["safe_mode"])){$this->Params["PHP_VALUES"]["safe_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["date.timezone"])){$this->Params["PHP_VALUES"]["date.timezone"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["iconv.input_encoding"])){$this->Params["PHP_VALUES"]["iconv.input_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["iconv.internal_encoding"])){$this->Params["PHP_VALUES"]["iconv.internal_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["iconv.output_encoding"])){$this->Params["PHP_VALUES"]["iconv.output_encoding"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["define_syslog_variables"])){$this->Params["PHP_VALUES"]["define_syslog_variables"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["sql.safe_mode"])){$this->Params["PHP_VALUES"]["sql.safe_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.allow_persistent"])){$this->Params["PHP_VALUES"]["odbc.allow_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.check_persistent"])){$this->Params["PHP_VALUES"]["odbc.check_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.max_persistent"])){$this->Params["PHP_VALUES"]["odbc.max_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.max_links"])){$this->Params["PHP_VALUES"]["odbc.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.defaultlrl"])){$this->Params["PHP_VALUES"]["odbc.defaultlrl"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["odbc.defaultbinmode"])){$this->Params["PHP_VALUES"]["odbc.defaultbinmode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.allow_persistent"])){$this->Params["PHP_VALUES"]["mysql.allow_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.max_persistent"])){$this->Params["PHP_VALUES"]["mysql.max_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.max_links"])){$this->Params["PHP_VALUES"]["mysql.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.default_port"])){$this->Params["PHP_VALUES"]["mysql.default_port"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.default_socket"])){$this->Params["PHP_VALUES"]["mysql.default_socket"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.default_host"])){$this->Params["PHP_VALUES"]["mysql.default_host"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.default_user"])){$this->Params["PHP_VALUES"]["mysql.default_user"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.default_password"])){$this->Params["PHP_VALUES"]["mysql.default_password"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.connect_timeout"])){$this->Params["PHP_VALUES"]["mysql.connect_timeout"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysql.trace_mode"])){$this->Params["PHP_VALUES"]["mysql.trace_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["ldap.max_links"])){$this->Params["PHP_VALUES"]["ldap.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["ldap.allow_persistent"])){$this->Params["PHP_VALUES"]["ldap.allow_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["ldap.check_persistent"])){$this->Params["PHP_VALUES"]["ldap.check_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.max_links"])){$this->Params["PHP_VALUES"]["mysqli.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.default_port"])){$this->Params["PHP_VALUES"]["mysqli.default_port"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.default_socket"])){$this->Params["PHP_VALUES"]["mysqli.default_socket"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.default_host"])){$this->Params["PHP_VALUES"]["mysqli.default_host"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.default_user"])){$this->Params["PHP_VALUES"]["mysqli.default_user"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.default_pw"])){$this->Params["PHP_VALUES"]["mysqli.default_pw"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mysqli.reconnect"])){$this->Params["PHP_VALUES"]["mysqli.reconnect"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["msql.allow_persistent"])){$this->Params["PHP_VALUES"]["msql.allow_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["msql.max_persistent"])){$this->Params["PHP_VALUES"]["msql.max_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["msql.max_links"])){$this->Params["PHP_VALUES"]["msql.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.save_handler"])){$this->Params["PHP_VALUES"]["session.save_handler"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.save_path"])){$this->Params["PHP_VALUES"]["session.save_path"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.use_cookies"])){$this->Params["PHP_VALUES"]["session.use_cookies"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.use_only_cookies"])){$this->Params["PHP_VALUES"]["session.use_only_cookies"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.name"])){$this->Params["PHP_VALUES"]["session.name"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.auto_start"])){$this->Params["PHP_VALUES"]["session.auto_start"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.cookie_lifetime"])){$this->Params["PHP_VALUES"]["session.cookie_lifetime"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.cookie_path"])){$this->Params["PHP_VALUES"]["session.cookie_path"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.serialize_handler"])){$this->Params["PHP_VALUES"]["session.serialize_handler"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.gc_probability"])){$this->Params["PHP_VALUES"]["session.gc_probability"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.gc_divisor"])){$this->Params["PHP_VALUES"]["session.gc_divisor"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.gc_maxlifetime"])){$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.entropy_length"])){$this->Params["PHP_VALUES"]["session.entropy_length"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.cache_limiter"])){$this->Params["PHP_VALUES"]["session.cache_limiter"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.cache_expire"])){$this->Params["PHP_VALUES"]["session.cache_expire"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.use_trans_sid"])){$this->Params["PHP_VALUES"]["session.use_trans_sid"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.hash_function"])){$this->Params["PHP_VALUES"]["session.hash_function"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.bug_compat_warn"])){$this->Params["PHP_VALUES"]["session.bug_compat_warn"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["session.hash_bits_per_character"])){$this->Params["PHP_VALUES"]["session.hash_bits_per_character"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.allow_persistent"])){$this->Params["PHP_VALUES"]["mssql.allow_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.max_persistent"])){$this->Params["PHP_VALUES"]["mssql.max_persistent"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.max_links"])){$this->Params["PHP_VALUES"]["mssql.max_links"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.min_error_severity"])){$this->Params["PHP_VALUES"]["mssql.min_error_severity"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.min_message_severity"])){$this->Params["PHP_VALUES"]["mssql.min_message_severity"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.compatability_mode"])){$this->Params["PHP_VALUES"]["mssql.compatability_mode"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.connect_timeout"])){$this->Params["PHP_VALUES"]["mssql.connect_timeout"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.timeout"])){$this->Params["PHP_VALUES"]["mssql.timeout"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.textlimit"])){$this->Params["PHP_VALUES"]["mssql.textlimit"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.textsize"])){$this->Params["PHP_VALUES"]["mssql.textsize"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.batchsize"])){$this->Params["PHP_VALUES"]["mssql.batchsize"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.datetimeconvert"])){$this->Params["PHP_VALUES"]["mssql.datetimeconvert"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.secure_connection"])){$this->Params["PHP_VALUES"]["mssql.secure_connection"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.max_procs"])){$this->Params["PHP_VALUES"]["mssql.max_procs"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["mssql.charset"])){$this->Params["PHP_VALUES"]["mssql.charset"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["tidy.clean_output"])){$this->Params["PHP_VALUES"]["tidy.clean_output"]=null;}
		if(!isset($this->Params["PHP_VALUES"]["soap.wsdl_cache_ttl"])){$this->Params["PHP_VALUES"]["soap.wsdl_cache_ttl"]=null;}
		if($this->Params["PHP_VALUES"]["safe_mode_gid"]==null){$this->Params["PHP_VALUES"]["safe_mode_gid"]="Off";}
		if($this->Params["PHP_VALUES"]["engine"]==null){$this->Params["PHP_VALUES"]["engine"]="On";}
		if($this->Params["PHP_VALUES"]["precision"]==null){$this->Params["PHP_VALUES"]["precision"]="12";}
		if($this->Params["PHP_VALUES"]["y2k_compliance"]==null){$this->Params["PHP_VALUES"]["y2k_compliance"]="On";}
		if($this->Params["PHP_VALUES"]["output_buffering"]==null){$this->Params["PHP_VALUES"]["output_buffering"]="On";}
		if($this->Params["PHP_VALUES"]["serialize_precision"]==null){$this->Params["PHP_VALUES"]["serialize_precision"]="100";}
		if($this->Params["PHP_VALUES"]["expose_php"]==null){$this->Params["PHP_VALUES"]["expose_php"]="Off";}
		if($this->Params["PHP_VALUES"]["max_execution_time"]==null){$this->Params["PHP_VALUES"]["max_execution_time"]="3600";}
		if($this->Params["PHP_VALUES"]["max_input_time"]==null){$this->Params["PHP_VALUES"]["max_input_time"]="3600";}
		if($this->Params["PHP_VALUES"]["memory_limit"]==null){$this->Params["PHP_VALUES"]["memory_limit"]="500M";}
		if($this->Params["PHP_VALUES"]["error_reporting"]==null){$this->Params["PHP_VALUES"]["error_reporting"]="E_ALL & ~E_NOTICE";}
		if($this->Params["PHP_VALUES"]["display_errors"]==null){$this->Params["PHP_VALUES"]["display_errors"]="Off";}
		if($this->Params["PHP_VALUES"]["display_startup_errors"]==null){$this->Params["PHP_VALUES"]["display_startup_errors"]="Off";}
		if($this->Params["PHP_VALUES"]["log_errors"]==null){$this->Params["PHP_VALUES"]["log_errors"]="On";}
		if($this->Params["PHP_VALUES"]["log_errors_max_len"]==null){$this->Params["PHP_VALUES"]["log_errors_max_len"]="2048";}
		if($this->Params["PHP_VALUES"]["ignore_repeated_errors"]==null){$this->Params["PHP_VALUES"]["ignore_repeated_errors"]="Off";}
		if($this->Params["PHP_VALUES"]["ignore_repeated_source"]==null){$this->Params["PHP_VALUES"]["ignore_repeated_source"]="Off";}
		if($this->Params["PHP_VALUES"]["report_memleaks"]==null){$this->Params["PHP_VALUES"]["report_memleaks"]="On";}
		if($this->Params["PHP_VALUES"]["track_errors"]==null){$this->Params["PHP_VALUES"]["track_errors"]="Off";}
		if($this->Params["PHP_VALUES"]["error_append_string"]==null){$this->Params["PHP_VALUES"]["error_append_string"]="</code></font><br>";}
		if($this->Params["PHP_VALUES"]["html_errors"]==null){$this->Params["PHP_VALUES"]["html_errors"]="false";}
		if($this->Params["PHP_VALUES"]["error_log"]==null){$this->Params["PHP_VALUES"]["error_log"]="/usr/share/artica-postfix/ressources/logs/php.log";}
		if($this->Params["PHP_VALUES"]["variables_order"]==null){$this->Params["PHP_VALUES"]["variables_order"]="EGPCS";}
		if($this->Params["PHP_VALUES"]["register_argc_argv"]==null){$this->Params["PHP_VALUES"]["register_argc_argv"]="On";}
		if($this->Params["PHP_VALUES"]["auto_globals_jit"]==null){$this->Params["PHP_VALUES"]["auto_globals_jit"]="On";}
		if($this->Params["PHP_VALUES"]["post_max_size"]==null){$this->Params["PHP_VALUES"]["post_max_size"]="128M";}
		if($this->Params["PHP_VALUES"]["default_mimetype"]==null){$this->Params["PHP_VALUES"]["default_mimetype"]="text/html";}
		if($this->Params["PHP_VALUES"]["default_charset"]==null){$this->Params["PHP_VALUES"]["default_charset"]="utf-8";}
		if($this->Params["PHP_VALUES"]["unicode.semantics"]==null){$this->Params["PHP_VALUES"]["unicode.semantics"]="off";}
		if($this->Params["PHP_VALUES"]["unicode.runtime_encoding"]==null){$this->Params["PHP_VALUES"]["unicode.runtime_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["unicode.script_encoding"]==null){$this->Params["PHP_VALUES"]["unicode.script_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["unicode.output_encoding"]==null){$this->Params["PHP_VALUES"]["unicode.output_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["unicode.from_error_mode"]==null){$this->Params["PHP_VALUES"]["unicode.from_error_mode"]="U_INVALID_SUBSTITUTE";}
		if($this->Params["PHP_VALUES"]["unicode.from_error_subst_char"]==null){$this->Params["PHP_VALUES"]["unicode.from_error_subst_char"]="3f";}
		if($this->Params["PHP_VALUES"]["include_path"]==null){$this->Params["PHP_VALUES"]["include_path"]=".:/usr/share/php:/usr/share/obm:/usr/share/php5:/usr/share/obm2:/usr/local/share/php:/usr/share/artica-postfix/ressources/externals:/usr/share/artica-postfix/ressources/externals/Gdata:/usr/share/php5/PEAR:/usr/share/pear";}
		if($this->Params["PHP_VALUES"]["extension_dir"]==null){$this->Params["PHP_VALUES"]["extension_dir"]="/usr/lib/php5/20090626+lfs";}
		if($this->Params["PHP_VALUES"]["cgi.force_redirect"]==null){$this->Params["PHP_VALUES"]["cgi.force_redirect"]="1";}
		if($this->Params["PHP_VALUES"]["cgi.fix_pathinfo"]==null){$this->Params["PHP_VALUES"]["cgi.fix_pathinfo"]="1";}
		if($this->Params["PHP_VALUES"]["file_uploads"]==null){$this->Params["PHP_VALUES"]["file_uploads"]="On";}
		if($this->Params["PHP_VALUES"]["upload_tmp_dir"]==null){$this->Params["PHP_VALUES"]["upload_tmp_dir"]="/var/lighttpd/upload";}
		if($this->Params["PHP_VALUES"]["upload_max_filesize"]==null){$this->Params["PHP_VALUES"]["upload_max_filesize"]="256M";}
		if($this->Params["PHP_VALUES"]["allow_url_fopen"]==null){$this->Params["PHP_VALUES"]["allow_url_fopen"]="On";}
		if($this->Params["PHP_VALUES"]["allow_url_include"]==null){$this->Params["PHP_VALUES"]["allow_url_include"]="Off";}
		if($this->Params["PHP_VALUES"]["from"]==null){$this->Params["PHP_VALUES"]["from"]="anonymous@anonymous.com";}
		if($this->Params["PHP_VALUES"]["default_socket_timeout"]==null){$this->Params["PHP_VALUES"]["default_socket_timeout"]="60";}
		if($this->Params["PHP_VALUES"]["safe_mode"]==null){$this->Params["PHP_VALUES"]["safe_mode"]="Off";}
		if($this->Params["PHP_VALUES"]["date.timezone"]==null){$this->Params["PHP_VALUES"]["date.timezone"]="Europe/Berlin";}
		if($this->Params["PHP_VALUES"]["iconv.input_encoding"]==null){$this->Params["PHP_VALUES"]["iconv.input_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["iconv.internal_encoding"]==null){$this->Params["PHP_VALUES"]["iconv.internal_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["iconv.output_encoding"]==null){$this->Params["PHP_VALUES"]["iconv.output_encoding"]="utf-8";}
		if($this->Params["PHP_VALUES"]["define_syslog_variables"]==null){$this->Params["PHP_VALUES"]["define_syslog_variables"]="Off";}
		if($this->Params["PHP_VALUES"]["sql.safe_mode"]==null){$this->Params["PHP_VALUES"]["sql.safe_mode"]="Off";}
		if($this->Params["PHP_VALUES"]["odbc.allow_persistent"]==null){$this->Params["PHP_VALUES"]["odbc.allow_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["odbc.check_persistent"]==null){$this->Params["PHP_VALUES"]["odbc.check_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["odbc.max_persistent"]==null){$this->Params["PHP_VALUES"]["odbc.max_persistent"]="-1";}
		if($this->Params["PHP_VALUES"]["odbc.max_links"]==null){$this->Params["PHP_VALUES"]["odbc.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["odbc.defaultlrl"]==null){$this->Params["PHP_VALUES"]["odbc.defaultlrl"]="4096";}
		if($this->Params["PHP_VALUES"]["odbc.defaultbinmode"]==null){$this->Params["PHP_VALUES"]["odbc.defaultbinmode"]="1";}
		if($this->Params["PHP_VALUES"]["mysql.allow_persistent"]==null){$this->Params["PHP_VALUES"]["mysql.allow_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["mysql.max_persistent"]==null){$this->Params["PHP_VALUES"]["mysql.max_persistent"]="-1";}
		if($this->Params["PHP_VALUES"]["mysql.max_links"]==null){$this->Params["PHP_VALUES"]["mysql.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["mysql.default_port"]==null){$this->Params["PHP_VALUES"]["mysql.default_port"]="3306";}
		if($this->Params["PHP_VALUES"]["mysql.default_socket"]==null){$this->Params["PHP_VALUES"]["mysql.default_socket"]="/var/run/mysqld/mysqld.sock";}
		if($this->Params["PHP_VALUES"]["mysql.default_host"]==null){$this->Params["PHP_VALUES"]["mysql.default_host"]="127.0.0.1";}
		if($this->Params["PHP_VALUES"]["mysql.default_user"]==null){$this->Params["PHP_VALUES"]["mysql.default_user"]="admin";}
		if($this->Params["PHP_VALUES"]["mysql.default_password"]==null){$this->Params["PHP_VALUES"]["mysql.default_password"]="180872";}
		if($this->Params["PHP_VALUES"]["mysql.connect_timeout"]==null){$this->Params["PHP_VALUES"]["mysql.connect_timeout"]="60";}
		if($this->Params["PHP_VALUES"]["mysql.trace_mode"]==null){$this->Params["PHP_VALUES"]["mysql.trace_mode"]="Off";}
		if($this->Params["PHP_VALUES"]["ldap.max_links"]==null){$this->Params["PHP_VALUES"]["ldap.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["ldap.allow_persistent"]==null){$this->Params["PHP_VALUES"]["ldap.allow_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["ldap.check_persistent"]==null){$this->Params["PHP_VALUES"]["ldap.check_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["mysqli.max_links"]==null){$this->Params["PHP_VALUES"]["mysqli.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["mysqli.default_port"]==null){$this->Params["PHP_VALUES"]["mysqli.default_port"]="3306";}
		if($this->Params["PHP_VALUES"]["mysqli.default_socket"]==null){$this->Params["PHP_VALUES"]["mysqli.default_socket"]="/var/run/mysqld/mysqld.sock";}
		if($this->Params["PHP_VALUES"]["mysqli.default_host"]==null){$this->Params["PHP_VALUES"]["mysqli.default_host"]="127.0.0.1";}
		if($this->Params["PHP_VALUES"]["mysqli.default_user"]==null){$this->Params["PHP_VALUES"]["mysqli.default_user"]="admin";}
		if($this->Params["PHP_VALUES"]["mysqli.default_pw"]==null){$this->Params["PHP_VALUES"]["mysqli.default_pw"]="180872";}
		if($this->Params["PHP_VALUES"]["mysqli.reconnect"]==null){$this->Params["PHP_VALUES"]["mysqli.reconnect"]="Off";}
		if($this->Params["PHP_VALUES"]["msql.allow_persistent"]==null){$this->Params["PHP_VALUES"]["msql.allow_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["msql.max_persistent"]==null){$this->Params["PHP_VALUES"]["msql.max_persistent"]="-1";}
		if($this->Params["PHP_VALUES"]["msql.max_links"]==null){$this->Params["PHP_VALUES"]["msql.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["session.save_handler"]==null){$this->Params["PHP_VALUES"]["session.save_handler"]="files";}
		if($this->Params["PHP_VALUES"]["session.save_path"]==null){$this->Params["PHP_VALUES"]["session.save_path"]="/var/lib/php5";}
		if($this->Params["PHP_VALUES"]["session.use_cookies"]==null){$this->Params["PHP_VALUES"]["session.use_cookies"]="1";}
		if($this->Params["PHP_VALUES"]["session.use_only_cookies"]==null){$this->Params["PHP_VALUES"]["session.use_only_cookies"]="1";}
		if($this->Params["PHP_VALUES"]["session.name"]==null){$this->Params["PHP_VALUES"]["session.name"]="PHPSESSID";}
		if($this->Params["PHP_VALUES"]["session.auto_start"]==null){$this->Params["PHP_VALUES"]["session.auto_start"]="0";}
		if($this->Params["PHP_VALUES"]["session.cookie_lifetime"]==null){$this->Params["PHP_VALUES"]["session.cookie_lifetime"]="0";}
		if($this->Params["PHP_VALUES"]["session.cookie_path"]==null){$this->Params["PHP_VALUES"]["session.cookie_path"]="/";}
		if($this->Params["PHP_VALUES"]["session.serialize_handler"]==null){$this->Params["PHP_VALUES"]["session.serialize_handler"]="php";}
		if($this->Params["PHP_VALUES"]["session.gc_probability"]==null){$this->Params["PHP_VALUES"]["session.gc_probability"]="1";}
		if($this->Params["PHP_VALUES"]["session.gc_divisor"]==null){$this->Params["PHP_VALUES"]["session.gc_divisor"]="100";}
		if($this->Params["PHP_VALUES"]["session.gc_maxlifetime"]==null){$this->Params["PHP_VALUES"]["session.gc_maxlifetime"]="1440";}
		if($this->Params["PHP_VALUES"]["session.entropy_length"]==null){$this->Params["PHP_VALUES"]["session.entropy_length"]="0";}
		if($this->Params["PHP_VALUES"]["session.cache_limiter"]==null){$this->Params["PHP_VALUES"]["session.cache_limiter"]="nocache";}
		if($this->Params["PHP_VALUES"]["session.cache_expire"]==null){$this->Params["PHP_VALUES"]["session.cache_expire"]="420";}
		if($this->Params["PHP_VALUES"]["session.use_trans_sid"]==null){$this->Params["PHP_VALUES"]["session.use_trans_sid"]="0";}
		if($this->Params["PHP_VALUES"]["session.hash_function"]==null){$this->Params["PHP_VALUES"]["session.hash_function"]="0";}
		if($this->Params["PHP_VALUES"]["session.bug_compat_warn"]==null){$this->Params["PHP_VALUES"]["session.bug_compat_warn"]="Off";}
		if($this->Params["PHP_VALUES"]["session.hash_bits_per_character"]==null){$this->Params["PHP_VALUES"]["session.hash_bits_per_character"]="4";}
		if($this->Params["PHP_VALUES"]["mssql.allow_persistent"]==null){$this->Params["PHP_VALUES"]["mssql.allow_persistent"]="On";}
		if($this->Params["PHP_VALUES"]["mssql.max_persistent"]==null){$this->Params["PHP_VALUES"]["mssql.max_persistent"]="-1";}
		if($this->Params["PHP_VALUES"]["mssql.max_links"]==null){$this->Params["PHP_VALUES"]["mssql.max_links"]="-1";}
		if($this->Params["PHP_VALUES"]["mssql.min_error_severity"]==null){$this->Params["PHP_VALUES"]["mssql.min_error_severity"]="10";}
		if($this->Params["PHP_VALUES"]["mssql.min_message_severity"]==null){$this->Params["PHP_VALUES"]["mssql.min_message_severity"]="10";}
		if($this->Params["PHP_VALUES"]["mssql.compatability_mode"]==null){$this->Params["PHP_VALUES"]["mssql.compatability_mode"]="Off";}
		if($this->Params["PHP_VALUES"]["mssql.connect_timeout"]==null){$this->Params["PHP_VALUES"]["mssql.connect_timeout"]="5";}
		if($this->Params["PHP_VALUES"]["mssql.timeout"]==null){$this->Params["PHP_VALUES"]["mssql.timeout"]="60";}
		if($this->Params["PHP_VALUES"]["mssql.textlimit"]==null){$this->Params["PHP_VALUES"]["mssql.textlimit"]="4096";}
		if($this->Params["PHP_VALUES"]["mssql.textsize"]==null){$this->Params["PHP_VALUES"]["mssql.textsize"]="4096";}
		if($this->Params["PHP_VALUES"]["mssql.batchsize"]==null){$this->Params["PHP_VALUES"]["mssql.batchsize"]="0";}
		if($this->Params["PHP_VALUES"]["mssql.datetimeconvert"]==null){$this->Params["PHP_VALUES"]["mssql.datetimeconvert"]="On";}
		if($this->Params["PHP_VALUES"]["mssql.secure_connection"]==null){$this->Params["PHP_VALUES"]["mssql.secure_connection"]="Off";}
		if($this->Params["PHP_VALUES"]["mssql.max_procs"]==null){$this->Params["PHP_VALUES"]["mssql.max_procs"]="-1";}
		if($this->Params["PHP_VALUES"]["mssql.charset"]==null){$this->Params["PHP_VALUES"]["mssql.charset"]="ISO-8859-1";}
		if($this->Params["PHP_VALUES"]["tidy.clean_output"]==null){$this->Params["PHP_VALUES"]["tidy.clean_output"]="Off";}
		if($this->Params["PHP_VALUES"]["soap.wsdl_cache_ttl"]==null){$this->Params["PHP_VALUES"]["soap.wsdl_cache_ttl"]="86400";}
		
		
		
		}	
		
	
	
	}
	
	
	
	
	
?>