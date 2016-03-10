<?php
class samba_kerb{
	
	public function samba_kerb(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_geteuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		if(!class_exists("settings_inc")){include_once("/usr/share/artica-postfix/framework/class.settings.inc");}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
	}
	
	

	public function buildPart(){
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Reconfigure Samba for proxy compliance", basename(__FILE__));}
		if($GLOBALS["VERBOSE"]){"SAMBA_SPECIFIC_PROXY() start... in line ".__LINE__."\n";}
		$IsAppliance=false;
		if($GLOBALS["VERBOSE"]){"users=new usersMenus(); in line ".__LINE__."\n";}
		$user=new settings_inc();
		$unix=new unix();
		$sock=new sockets();
		if($user->SQUID_APPLIANCE){$IsAppliance=true;}
		if($user->KASPERSKY_WEB_APPLIANCE){$IsAppliance=true;}
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($user->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
		if($EnableWebProxyStatsAppliance==1){$IsAppliance=true;}
		$SambaBindInterface=$sock->GET_INFO("SambaBindInterface");
		$WinBindMaxClients=intval($sock->GET_INFO("WinBindMaxClients"));
		if($WinBindMaxClients==0){$WinBindMaxClients=500;}
		
		
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
		if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
		
		
		$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
		$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
		$adminpassword=$array["WINDOWS_SERVER_PASS"];
		$adminpassword=$unix->shellEscapeChars($adminpassword);
		
		$adminname=$array["WINDOWS_SERVER_ADMIN"];
		$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
		$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
		$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
		$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
		$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
		if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
		if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
		if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
		if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
		
		
		$workgroup=$array["ADNETBIOSDOMAIN"];
		$realm=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
		$ipaddr=trim($array["ADNETIPADDR"]);
		echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: Kdc server ads : $ad_server workgroup `$workgroup` ipaddr:$ipaddr\n";
		$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
		$password_server=$hostname;
		//if($ipaddr<>null){$password_server=$ipaddr;}
		if(strpos($password_server, ".")>0){$aa=explode(".", $password_server);$password_server=$aa[0];}
		$SAMBA_VERSION=$this->SAMBA_VERSION();
		$ipaddr=trim($array["ADNETIPADDR"]);
		if($ipaddr<>null){
			$ipaddrZ=explode(".",$ipaddr);
			while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
			$ipaddr=@implode(".", $ipaddrZ);
			$password_server=$ipaddr;
		}
		
		$AS36=false;
		if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){$AS36=true;}
		
		if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#", $SAMBA_VERSION,$re)){
			$MAJOR=intval($re[1]);
			$MINOR=intval($re[2]);
			$REV=intval($re[3]);
			echo "Starting......: ".date("H:i:s")."  Samba, V$MAJOR $MINOR $REV\n";
		
		}
		
		$unix=new unix();
		$myhostname=$unix->hostname_g();
		if(strpos($myhostname, ".")>0){
			$tt=explode(".",$myhostname);
			$myhostname=$tt[0];
		}
		$myhostname=strtoupper($myhostname);
		
		$f[]="\tnetbios name = $myhostname";
		$f[]="\tworkgroup = $workgroup";
		$f[]="\tkerberos method = dedicated keytab";
		$f[]="\tdedicated keytab file = /etc/krb5.keytab";
		$f[]="\trealm = $realm";
		$f[]="\tsecurity = ads";

		if($KerbAuthDisableGroupListing==0){
			$f[]="\twinbind enum groups = Yes";
			$f[]="\twinbind enum users = Yes";
		}else{
			$f[]="\twinbind enum groups = No";
			$f[]="\twinbind enum users = No";
		}
		$f[]="\tidmap config * : range = 10000 - 20000";
		$f[]="\tidmap config * : backend = tdb";
		
		$arrayBCK["autorid"]="autorid";
		$arrayBCK["rid"]="rid";
		$arrayBCK["tdb"]="tdb";
		
		switch ($array["SAMBA_BACKEND"]) {
		case 'autorid':
		$f[]="\tidmap config $workgroup: backend = autorid";
		$f[]="\tidmap config $workgroup: range = 100000-1499999";
		break;
		case 'rid':
		$f[]="\tidmap config $workgroup :backend	= rid";
		$f[]="\tidmap config $workgroup :range	= 50000001-5999999";
		$f[]="\tidmap config $workgroup :base_rid	= 0";
		break;
		case 'tdb':
		$f[]="\tidmap config $workgroup : backend = tdb";
		$f[]="\tidmap config $workgroup : range = 20000 - 20000000";
		break;
		case 'ad':
		$f[]="\tidmap config $workgroup : backend  = ad";
		$f[]="\tidmap config $workgroup : range = 20000-20000000	";
		break;
		default:
		$f[]="\tidmap config $workgroup : range = 20000 - 20000000";
		$f[]="\tidmap config $workgroup : backend = tdb";
		break;
		
		
		}
		
			
		if($KerbAuthMapUntrustedDomain==1){
			$f[]="\tmap untrusted to domain = Yes";
		}else{
			$f[]="\tmap untrusted to domain = No";
		}
		
		$f[]="\tclient ntlmv2 auth = Yes";
		$f[]="\tclient lanman auth = No";
		
		if($KerbAuthDisableNormalizeName==1){
		$f[]="\twinbind normalize names = No";
		}else{
		$f[]="\twinbind normalize names = Yes"; # bug 9226
		}
		$f[]="\twinbind separator = /";
		$f[]="\twinbind use default domain = yes";
		$f[]="\twinbind nested groups = Yes";
		$f[]="\twinbind nss info = rfc2307";
		$f[]="\twinbind reconnect delay = 30";
		$f[]="\twinbind offline logon = true";
		$f[]="\twinbind cache time = 1800";
		$f[]="\twinbind refresh tickets = true";
		$f[]="\twinbind max clients = $WinBindMaxClients";
		
		if($KerbAuthTrusted==1){
			$f[]="\tallow trusted domains = Yes";
						}else{
			$f[]="\tallow trusted domains = No";
		}
		$f[]="\tserver signing = auto";
		$f[]="\tclient signing = auto";
		$f[]="\tlm announce = No";
		$f[]="\tntlm auth = No";
		$f[]="\tlanman auth = No";
		$f[]="\tpreferred master = No";
		$f[]="\tlocal master = No";
		$f[]="\twins support = No";
		$f[]="\tencrypt passwords = yes";
		$f[]="\tpassword server = $password_server";
		$f[]="\tprinting = bsd";
		$f[]="\tload printers = no";
		$f[]="\tsocket options = TCP_NODELAY SO_RCVBUF=8192 SO_SNDBUF=8192";
		
		if($SambaBindInterface<>null){
			$f[]="\tinterfaces = $SambaBindInterface lo";
			$f[]="\tbind interfaces only = yes";
		}
		
		$f[]="";		
		return @implode("\n",$f);
		
	}
	private function SAMBA_VERSION(){
		$unix=new unix();
		$winbind=$unix->find_program("winbindd");
		exec("$winbind -V 2>&1",$results);
		if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
	}
		
	
}