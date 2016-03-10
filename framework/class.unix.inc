<?php
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["DEBUG"])){$GLOBALS["DEBUG"]=false;}
if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." class.unix.inc instantiate class.ini-frame.inc...\n";}
include_once(dirname(__FILE__).'/class.ini-frame.inc');
include_once(dirname(__FILE__).'/class.cgroups.inc');
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ini.inc");
include_once("/usr/share/artica-postfix/ressources/class.process.inc");
include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.manager.inc");
include_once("/usr/share/artica-postfix/framework/class.monit.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." class.unix.inc instantiate classes done...\n";}
$GLOBALS["SSLKEY_PATH"]="/etc/ssl/certs/apache";
$GLOBALS["MAX_RECUSIVE_DIRS"]=400;
$GLOBALS["RECURSVIVE_INC"]=0;

if(!isset($GLOBALS["DEBUG_GREP"])){$GLOBALS["DEBUG_GREP"]=false;}
if(!defined("RE_IP_BYTE")){define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');}
if(!defined("RE_IP_ADD")){define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );}
if(!defined("RE_IP_PREFIX")){define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');}
if(!defined("RE_IP_BLOCK")){define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);}
if(!defined("RE_IPV6_WORD")){define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );}
if(!defined("RE_IPV6_GAP")){define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );}
if(!defined("RE_IPV6_V4_PREFIX")){define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );}
if(!defined("RE_IPV6_PREFIX")){define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');}
if(!defined("RE_IPV6_ADD")){define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );}
if(!defined("RE_IPV6_BLOCK")){define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );}
if(!defined("IP_ADDRESS_STRING")){define( 'IP_ADDRESS_STRING','(?:' .RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)'.'|'.RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)' .')' );}


class unix {
	public $mysql_error=null;
	var $proc_status;
	
	function unix(){
		if(!isset($GLOBALS["GET_TIME_ZONE"])){if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." class.unix.inc Get local time..\n";}}
		$getLocalTimezone=$this->getLocalTimezone();
		if(!isset($GLOBALS["GET_TIME_ZONE"])){if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." class.unix.inc $getLocalTimezone..\n";}}
		@date_default_timezone_set($getLocalTimezone);
		
	}
	
	public function PID_CMDLINE($pid){
		$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
		$cmdline=str_replace('"\0"', " ", $cmdline);
		$cmdline=str_replace(' . ', "", $cmdline);
		$cmdline=str_replace(' . ', "", $cmdline);
		$cmdline=str_replace("'", "", $cmdline);
		return trim($cmdline);
	}


	PUBLIC function LOCATE_SYSLOG_INITD(){
			if(is_file("/etc/init.d/rsyslog")){return "/etc/init.d/rsyslog";}
			if(is_file("/etc/init.d/syslog")){return "/etc/init.d/syslog";}
			if(is_file("/etc/init.d/sysklogd")){return "/etc/init.d/sysklogd";}
	}
	
	
	
	public function IsProxy(){
		$ini=new iniFrameWork();
		$sock=new sockets();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		if(trim($datas)==null){return false;}
		$ini->loadString($datas);
		$ArticaProxyServerEnabled=trim(strtolower($ini->_params["PROXY"]["ArticaProxyServerEnabled"]));
		$ArticaProxyServerName=trim($ini->_params["PROXY"]["ArticaProxyServerName"]);
	
		if($ArticaProxyServerEnabled=="yes"){
			if($ArticaProxyServerName<>null){return true;}
		}
		return false;
	}	
	
	public function Process1($force=true){
		if(isset($GLOBALS["CLASS_UNIX_PROCESS1_EXEC"])){return;}
		$GLOBALS["CLASS_UNIX_PROCESS1_EXEC"]=true;
		if(!is_file("/etc/init.d/artica-process1")){return;}
		if(isset($GLOBALS["NO_PROCESS1_CHECK"])){return;}
		if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){$force=true;}
		if(function_exists("system_is_overloaded")){if(system_is_overloaded()){return;}}
		if(!$force){
			$time=$this->file_time_min("/usr/share/artica-postfix/ressources/settings.inc");
			if($time<2){return;}
		}
		
		@shell_exec("/etc/init.d/artica-process1 start");
	}
	
	public function Popuplate_cron_make($cronfile,$schedule,$phpprocess){
		
		$PATH="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$php5=$this->LOCATE_PHP5_BIN();
		$nice=$this->EXEC_NICE();
		$CRON[]="PATH=$PATH";
		$CRON[]="MAILTO=\"\"";
		$CRON[]="$schedule\troot\t$nice $php5 /usr/share/artica-postfix/$phpprocess >/dev/null 2>&1";
		$CRON[]="";
		file_put_contents("/etc/cron.d/$cronfile",@implode("\n", $CRON));
		chmod("/etc/cron.d/$cronfile",0640);
		chown("/etc/cron.d/$cronfile","root");
		if(function_exists("writelogs_framework")){
			writelogs_framework("/etc/cron.d/$cronfile -> Done");
		}
	}
	
	public function MAIN_URI(){
		$ini=new iniFrameWork();
		$uri=null;
		$ini->loadFile('/etc/artica-postfix/artica-update.conf');
		$autoinstall=true;
		if(property_exists($ini,"_params")){
			if(isset($ini->_params["AUTOUPDATE"])){
				if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
				if($ini->_params["AUTOUPDATE"]["autoinstall"]<>"yes"){$autoinstall=false;}
				if(isset($ini->_params["AUTOUPDATE"]["uri"])){$uri=$ini->_params["AUTOUPDATE"]["uri"];}
			}
		}
		if($uri==null){$uri="http://www.articatech.net/auto.update.php";}
		$uri=str_replace("artica.fr", "articatech.net",$uri);
		$arrayURI=parse_url($uri);
		
		
		
		
		
		if($this->IsProxy()){
			
			if($GLOBALS["VERBOSE"]){echo __CLASS__."::".__FUNCTION__."[".__LINE__."] Proxy is enabled, return {$arrayURI["scheme"]}://{$arrayURI["host"]}\n";}
			return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";}
			$hostname=$arrayURI["host"];
			if($this->isIPAddress($hostname)){
				if($GLOBALS["VERBOSE"]){echo __CLASS__."::".__FUNCTION__."[".__LINE__."] $hostname = IP ADDRESS, return {$arrayURI["scheme"]}://{$arrayURI["host"]}\n";}
				return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";
			}
			$ipaddress=gethostbyname($hostname);
			if($GLOBALS["VERBOSE"]){echo __CLASS__."::".__FUNCTION__."[".__LINE__."] $hostname = $ipaddress\n";}
		
			if(!$this->isIPAddress($ipaddress)){
				if($hostname<>"www.artica.fr"){return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";}
				$ipaddress=gethostbyname("www.articatech.net");
				if(!$this->isIPAddress($ipaddress)){return null;}
				$ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";
				$ini->saveFile('/etc/artica-postfix/artica-update.conf');
				return "{$arrayURI["scheme"]}://www.articatech.net";
			}
		
		return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";
	}
	
	
	public function SET_ENVIRONMENT($key,$value){
		$f=explode(@file_get_contents("/etc/environment"));
		$found=false;
		while (list ($num, $val) = each ($f)){
			if(preg_match("#$key#", $val)){
				$f[$num]="$key=\"$val\"";
				$found=true;
				break;
			}
			
		}
		
		if(!$found){
			$f[]="$key=\"$val\"";
		}
		@file_put_contents("/etc/environment", @implode("\n", $f));
	}
	
	
	private function getLocalTimezone(){
		if(isset($GLOBALS["GET_TIME_ZONE"])){return $GLOBALS["GET_TIME_ZONE"];}
		if(is_file("/etc/artica-postfix/FROM_ISO")){
			if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){
				$GLOBALS["GET_TIME_ZONE"]="Europe/Dublin";
				return $GLOBALS["GET_TIME_ZONE"];
			}
		}
		
		
		if(isset($GLOBALS["GET_TIME_ZONE"])){if($GLOBALS["GET_TIME_ZONE"]<>null){return $GLOBALS["GET_TIME_ZONE"];}}
		if(function_exists("debug_mem")){debug_mem();}else{if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." debug_mem no such function...\n";}}
		$page=$this->CurrentPageName();
		if($page=="exec.artica-filter.php"){return;}
		
		
		$timezones=trim(@file_get_contents('/etc/artica-postfix/settings/Daemons/timezones'));
		if($timezones==null){
			$timezones=trim(@file_get_contents("/etc/timezone"));
		}
		
		
		if($timezones<>null){$GLOBALS["GET_TIME_ZONE"]=$timezones;return $GLOBALS["GET_TIME_ZONE"];}
	
		
		$iTime = time();
		$arr = localtime($iTime);
		$arr[5] += 1900;
		$arr[4]++;
		$iTztime = gmmktime($arr[2], $arr[1], $arr[0], $arr[4], $arr[3], $arr[5]);
		$offset = doubleval(($iTztime-$iTime)/(60*60));
		$zonelist =
		array('Kwajalein' => -12.00,'Pacific/Midway' => -11.00,'Pacific/Honolulu' => -10.00,'America/Anchorage' => -9.00,'America/Los_Angeles' => -8.00,'America/Denver' => -7.00,'America/Tegucigalpa' => -6.00,'America/New_York' => -5.00,'America/Caracas' => -4.30,'America/Halifax' => -4.00,'America/St_Johns' => -3.30,'America/Argentina/Buenos_Aires' => -3.00,'America/Sao_Paulo' => -3.00,'Atlantic/South_Georgia' => -2.00,'Atlantic/Azores' => -1.00,'Europe/Dublin' => 0,'Europe/Belgrade' => 1.00,'Europe/Minsk' => 2.00,'Asia/Kuwait' => 3.00,'Asia/Tehran' => 3.30,'Asia/Muscat' => 4.00,'Asia/Yekaterinburg' => 5.00,'Asia/Kolkata' => 5.30,'Asia/Katmandu' => 5.45,'Asia/Dhaka' => 6.00,'Asia/Rangoon' => 6.30,'Asia/Krasnoyarsk' => 7.00,'Asia/Brunei' => 8.00,'Asia/Seoul' => 9.00,'Australia/Darwin' => 9.30,'Australia/Canberra' => 10.00,'Asia/Magadan' => 11.00,'Pacific/Fiji' => 12.00,'Pacific/Tongatapu' => 13.00);
		$index = array_keys($zonelist, $offset);
		if(sizeof($index)!=1){return;}
		$GLOBALS["GET_TIME_ZONE"]=$index[0];
		writelogs_framework("Define timezones -> {$GLOBALS["GET_TIME_ZONE"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents('/etc/artica-postfix/settings/Daemons/timezones', $index[0]);
		$GLOBALS["GET_TIME_ZONE"]= $index[0];
		return $GLOBALS["GET_TIME_ZONE"];
	}	
	
	
	private function CurrentPageName(){
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
		return $phpPage;}
	
	public function StringToGrep($search){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		return $search;
	}
	
	
	PUBLIC function LOCATE_SSHD_PID_PATH(){
		if(is_file("/var/run/sshd.pid")){return "/var/run/sshd.pid";}
		if(is_file("/var/run/sshd.init.pid")){return "/var/run/sshd.init.pid";}
	}
	
	PUBLIC function LOCATE_SSHD_CONFIG_PATH(){
		if(is_file("/etc/ssh/sshd_config")){return "/etc/ssh/sshd_config";}
		
	}	
	
	
	public function MONIT_RELOAD(){
		$monit=new monit_unix();
		$monit->RELOAD();
	}
	
	
public function maillog_to_backupdir($fullpath){
	
	$IsMaillog=false;
	$file=basename($fullpath);
	if(preg_match("#^mail\.(log|warn|info)-#", $file)){$IsMaillog=true;}
	if(preg_match("#^mail\.(log|warn|info)\.([0-9]+)#", $file)){$IsMaillog=true;}
	
	if(!$IsMaillog){return false;}
	
	
	if(!isset($GLOBALS["maillogToMysql"])){
		$GLOBALS["maillogToMysql"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/maillogToMysql"));
	}
	
	if(!is_numeric($GLOBALS["maillogToMysql"])){return false;}
	if($GLOBALS["maillogToMysql"]==0){return false;}
	
	if(!isset($GLOBALS["maillogStoragePath"])){
		$GLOBALS["maillogStoragePath"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/maillogStoragePath"));
	}
	
	if(!isset($GLOBALS["maillogMaxDays"])){
		$GLOBALS["maillogMaxDays"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/maillogMaxDays"));
	}	
	if($GLOBALS["maillogStoragePath"]==null){$GLOBALS["maillogStoragePath"]="/home/postfix/maillog";}
	if(!is_numeric($GLOBALS["maillogMaxDays"])){$GLOBALS["maillogMaxDays"]=7;}
	
	$maillogStoragePath=$GLOBALS["maillogStoragePath"];
	$maillogMaxMin=$GLOBALS["maillogMaxDays"]*24;
	$maillogMaxMin=$maillogMaxMin*60;
	if(!is_dir($maillogStoragePath)){ @mkdir($maillogStoragePath,0755,true); }
	$timef=$this->file_time_min($fullpath);
	if($timef>$maillogMaxMin){@unlink($fullpath);return true;}
	if(@copy($fullpath, "$maillogStoragePath/$file")){
		@unlink($fullpath);
		return true;
	}
	return false;
	
}	
	
	
public function LOCATE_VBoxHeadless(){
	if(is_file("/usr/lib/virtualbox/VBoxHeadless")){return "/usr/lib/virtualbox/VBoxHeadless";}
	if(is_file("/usr/local/lib/virtualbox/VBoxHeadless")){return "/usr/local/lib/virtualbox/VBoxHeadless";}
	}	
	
	public function LOCATE_CYRRECONSTRUCT(){
		if(is_file("/usr/sbin/cyrreconstruct")){return "/usr/sbin/cyrreconstruct";}
		if(is_file("/usr/lib/cyrus/bin/reconstruct")){return "/usr/lib/cyrus/bin/reconstruct";}
		if(is_file("/usr/lib/cyrus-imapd/reconstruct")){return "/usr/lib/cyrus-imapd/reconstruct";}
		$this->events("Unable to stat cyrus reconstruct tool");
	}
	
	public function LOCATE_ROUNDCUBE_WEBFOLDER(){
		if(is_file("/usr/share/roundcube/index.php")){return "/usr/share/roundcube";}
	    if(is_file("/usr/share/roundcubemail/index.php")){return "/usr/share/roundcubemail";}
 	}
	
	public function LOCATE_SLPAD_PATH(){
		if(is_file("/usr/sbin/slapd")){return "/usr/sbin/slapd";}
		if(is_file("/usr/lib/openldap/slapd")){return "/usr/lib/openldap/slapd";}
		return $this->find_program("slpad");
	}
	
	public function LOCATE_SLPAD_CONF(){
		if(is_file('/etc/ldap/slapd.conf')){return '/etc/ldap/slapd.conf';}
		if(is_file('/etc/openldap/slapd.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/etc/openldap/ldap.conf')){return '/etc/openldap/ldap.conf';}
		if(is_file('/opt/artica/etc/openldap/slapd.conf')){return '/opt/artica/etc/openldap/slapd.conf';}
		if(is_file('/usr/local/etc/openldap/slapd.conf')){return '/usr/local/etc/openldap/slapd.conf';} 
	}	
	
	public function LOCATE_SIEVEC(){
		if(is_file("/usr/sbin/sievec")){return "/usr/sbin/sievec";}
		if(is_file("/usr/lib/cyrus/bin/sievec")){return "/usr/lib/cyrus/bin/sievec";}
		if(is_file("/usr/lib/cyrus-imapd/sievec")){return "/usr/lib/cyrus-imapd/sievec";}
		$this->events("Unable to stat cyrus sievec tool");
	}
	
	public function LOCATE_CLAMDCONF(){
		if(is_file("/etc/clamav/clamd.conf")){return "/etc/clamav/clamd.conf";}
		if(is_file("/etc/clamd.conf")){return "/etc/clamd.conf";}
		if(is_file("/usr/local/etc/clamav/clamd.conf")){return "/usr/local/etc/clamav/clamd.conf";}
		if(is_file("/usr/local/etc/clamd.conf")){return "/usr/local/etc/clamd.conf";}
		}
		
	public function APACHE_LOCATE_PHP_FPM(){
		$phpfpm=$this->find_program('php5-fpm');
		if(is_file($phpfpm)){return $phpfpm;}
		$phpfpm=$this->find_program('php-fpm');
		return $phpfpm;
	}
	
	public function apache_LOCATE_MIME_TYPES(){
		if(is_file("/etc/mime.types")){return "/etc/mime.types";}
		if(is_file("/etc/apache2/mime.types")){return "/etc/apache2/mime.types";}
		if(is_file("/etc/httpd/mime.types")){return "/etc/httpd/mime.types";}
	}	
	
	public function CPU_NUMBER(){
		
		if(is_file("/etc/artica-postfix/CPU_NUMBER")){
			$time=$this->file_time_min("/etc/artica-postfix/CPU_NUMBER");
			if($time<60){return @file_get_contents("/etc/artica-postfix/CPU_NUMBER");}
		}
		
		if(isset($GLOBALS["CPU_NUMBER"])){return $GLOBALS["CPU_NUMBER"];}
		$cat=$this->find_program("cat");
		$grep=$this->find_program("grep");
		$cut=$this->find_program("cut");
		$wc=$this->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$GLOBALS["CPU_NUMBER"]=exec($cmd);
		writelogs_framework("$cmd ->{$GLOBALS["CPU_NUMBER"]}",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($GLOBALS["CPU_NUMBER"])){$GLOBALS["CPU_NUMBER"]=1;}
		if($GLOBALS["CPU_NUMBER"]==0){$GLOBALS["CPU_NUMBER"]=1;}
		@unlink("/etc/artica-postfix/CPU_NUMBER");
		@file_put_contents("/etc/artica-postfix/CPU_NUMBER", $GLOBALS["CPU_NUMBER"]);
		return $GLOBALS["CPU_NUMBER"];
	}	
		
	public function LOCATE_AWSTATS_BIN(){
		$f[]="/usr/local/awstats/cgi-bin/awstats.pl";
		$f[]="/usr/local/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/usr/lib/cgi-bin/awstats.pl";
		$f[]="/usr/share/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/var/www/awstats/awstats.pl";
		$f[]="/srv/www/cgi-bin/awstats.pl";
		$f[]="/opt/artica/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/root/awstats-6.3/wwwroot/cgi-bin/awstats.pl";
		$f[]="/home/apache/cgi-bin/awstats.pl";
		$f[]="/srv/www/cgi-bin/awstats/awstats.pl";
		while (list ($num, $path) = each ($f) ){if(is_file($path)){return $path;}}			
	}
	private function LOCATE_AWSTATS_POSSIBLE_DIRS(){
		$f[]="/usr/local/awstats";
		$f[]="/usr/share/awstats";
		$f[]="/root/awstats-6.3";
		$f[]="/srv/www/ispcp/engine/awstats";
		return $f;
	}
	
	
	public function LOCATE_AWSTATS_BUILDSTATICPAGES_BIN(){
		$f[]="/usr/local/awstats/tools/awstats_buildstaticpages.pl";
		$f[]="/usr/share/awstats/tools/awstats_buildstaticpages.pl";
		$f[]="/root/awstats-6.3/tools/awstats_buildstaticpages.pl";
		$f[]="/usr/share/awstats/awstats_buildstaticpages.pl";
		$f[]="/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl";
		$f[]="/srv/www/ispcp/engine/awstats/awstats_buildstaticpages.pl";
		while (list ($num, $path) = each ($f) ){if(is_file($path)){return $path;}}
		
		
	}
	public function LOCATE_AWSTATS_PLUGINS_DIR(){
		$possibledirs=$this->LOCATE_AWSTATS_POSSIBLE_DIRS();
		while (list ($num, $path) = each ($possibledirs) ){
			if(is_dir($path."/plugins")){return $path."/plugins";}
		}
	}
	
	
	
	public function LOCATE_VBOX_ADDITIONS_PID(){	
		if(is_file("/var/run/vboxadd-service")){return "/var/run/vboxadd-service";}
		if(is_file("/var/lock/subsys/vboxadd-service")){return "/var/lock/subsys/vboxadd-service";}
		if(is_file("/var/run/vboxadd-service")){return "/var/run/vboxadd-service";}
		if(is_file("/var/run/vboxadd-service.pid")){return "/var/run/vboxadd-service.pid";}
	}
	
	public function RECONFIGURE_SQUID(){
		$php5=$this->LOCATE_PHP5_BIN();
		$nohup=$this->find_program("nohup");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid >/dev/null 2>&1");
		
	}
	
	public function isDirInFsTab($directory){
		$directoryRegex=$directory;
		$directoryRegex=str_replace("/", "\/", $directoryRegex);
		$directoryRegex=str_replace(".", "\.", $directoryRegex);
		$f=explode("\n", @file_get_contents("/etc/fstab"));
		while (list ($index, $val) = each ($f) ){
			if(preg_match("#^(.+)\s+$directoryRegex#", $val,$re)){
				if($GLOBALS["VERBOSE"]){echo "$directory must be mounted on {$re[1]}\n";}
				return $re[1];
			}
			}
			return null;
	}
	
	
	public function PIDOF_BY_PORT($port){
		$fuser=$this->find_program("fuser");
		$PIDLINE=null;
		$RES=array();
		$cmdline="$fuser $port/tcp 2>&1";
		
		exec("$fuser $port/tcp 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo "$cmdline ". count($results)." lines\n";}
		
		while (list ($index, $val) = each ($results) ){
			if(!preg_match("#tcp:\s+(.+)#", $val,$re)){
				if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Not Found '$val'\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Found {$re[1]}\n";}
			$PIDLINE=$re[1];
			
		
		
		if($PIDLINE<>null){
			$tr=explode(" ",$PIDLINE);
			while (list ($a, $b) = each ($tr) ){
				$b=trim($b);
				if($b==null){continue;}
				if(!is_numeric($b)){continue;}
				if($b<5){continue;}
				$RES[$b]=$b;
			}
		}
	}
		return $RES;
	}
	public function PIDOF_BY_PORT_UDP($port){
		$fuser=$this->find_program("fuser");
		$PIDLINE=null;
		$RES=array();
		exec("$fuser $port/udp 2>&1",$results);
		while (list ($index, $val) = each ($results) ){
			if(preg_match("#udp:\s+(.+)#", $val,$re)){
				if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Found {$re[1]}\n";}
				$PIDLINE=$re[1];
			}
		}
	
		if($PIDLINE<>null){
			$tr=explode(" ",$PIDLINE);
			while (list ($a, $b) = each ($tr) ){
				$b=trim($b);
				if($b==null){continue;}
				if(!is_numeric($b)){continue;}
				if($b<5){continue;}
				$RES[$b]=$b;
			}
		}
		return $RES;
	}	
	
	public function NGINX_COMPILE_PARAMS(){
		if(isset($GLOBALS[__CLASS__.".".__FUNCTION__])){return $GLOBALS[__CLASS__.".".__FUNCTION__];}
		$nginx=$this->find_program("nginx");
		if(!is_file($nginx)){return;}
		exec("$nginx -V 2>&1",$results);
		
		$NOT["CONFIGURE"]=true; // 	1
		$NOT["ARGUMENTS:"]=true; // 	1
		$NOT["PREFIX"]=true; // 	/etc/nginx
		$NOT["SBIN-PATH"]=true; // 	/usr/sbin/nginx
		$NOT["CONF-PATH"]=true; // 	/etc/nginx/nginx.conf
		$NOT["ERROR-LOG-PATH"]=true; // 	/var/log/nginx/error.log
		$NOT["HTTP-LOG-PATH"]=true; // 	/var/log/nginx/access.log
		$NOT["PID-PATH"]=true; // 	/var/run/nginx.pid
		$NOT["LOCK-PATH"]=true; // 	/var/run/nginx.lock
		$NOT["HTTP-CLIENT-BODY-TEMP-PATH"]=true; // 	/var/cache/nginx/client_temp
		$NOT["HTTP-PROXY-TEMP-PATH"]=true; // 	/var/cache/nginx/proxy_temp
		$NOT["HTTP-FASTCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/fastcgi_temp
		$NOT["HTTP-UWSGI-TEMP-PATH"]=true; // 	/var/cache/nginx/uwsgi_temp
		$NOT["HTTP-SCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/scgi_temp
		$NOT["USER"]=true; // 	nginx
		$NOT["GROUP"]=true; //
		$NOT["WITH-HTTP_GUNZIP_MODULE"]=true; //
		
		while (list ($key, $value) = each ($results) ){
			if(preg_match("#nginx version: (.*?)\/([0-9\.]+)#", $value,$re)){
				$ARRAY["DEF"]["VENDOR"]=$re[1];
				$ARRAY["DEF"]["VERSION"]=$re[2];
				continue;}
			if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
			if(preg_match("#configure arguments:\s+(.*)#",$value,$re)){
				$f=explode(" ",$value);
				while (list ($a, $b) = each ($f) ){
		
					if(preg_match("#add-module=(.+)#",$b,$re)){
						$module=basename($re[1]);
						if(preg_match("#headers-more-nginx-module#", $module)){$ARRAY["ngx_headers_more"]=true;}
						$ARRAY["MODULES"][$module]=$module;continue;
					}
		
					$b=str_replace("--", "", $b);
		
					if(preg_match("#(.+?)=(.+)#", $b,$ri)){
						if(isset($NOT[strtoupper($b)])){continue;}
						$ARRAY["ARGS"][strtoupper($ri[1])]=trim($ri[2]);
						continue;
					}
					if(isset($NOT[strtoupper($b)])){continue;}
					$ARRAY["ARGS"][strtoupper($b)]=true;
				}
					
			}
		}
		$GLOBALS[__CLASS__.".".__FUNCTION__]=$ARRAY;
	
		return $ARRAY;
	}
	

	public function InterfaceToIP($eth){
		include_once("/usr/share/artica-postfix/ressources/class.system.nics.inc");
	if($eth==null){return array("IP"=>null,"NETMASK"=>null,"NETWORK"=>null,"IPADDR"=>null);}
	$nics=new system_nic($eth);
	if($nics->IPADDR<>null){
		$PR=explode(".",$nics->IPADDR);
		
		
		return array("IP"=>$nics->IPADDR,"IPADDR"=>$nics->IPADDR,
				"NETMASK"=>$nics->NETMASK,
				"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0"
				);		
	}
	
	$arrayTCP=$this->NETWORK_ALL_INTERFACES();
	$ALLARRAY=$arrayTCP[$eth];
	
	$PR=explode(".",$ALLARRAY["IPADDR"]);
	
	return array("IP"=>$ALLARRAY["IPADDR"],"NETMASK"=>$ALLARRAY["NETMASK"]
			,"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0","IPADDR"=>$ALLARRAY["IPADDR"],
			
			);
}


public function md5_dir($path){
	$find=$this->find_program("find");
	$sort=$this->find_program("sort");
	$xargs=$this->find_program("xargs");
	$md5sum=$this->find_program("md5sum");
	$results=trim(exec("$find \"$path\" -type f | $sort -u | $xargs cat | $md5sum 2>&1"));
	if(preg_match("#(.+?)\s+#", $results,$re)){return $re[1];}
	
}
	
	
	public function CreateUnixUser($username,$groupname=null,$displayname=null){
		if($this->UnixUserExists($username)){return true;}
		include_once(dirname(__FILE__)."/class.settings.inc");
		if(!isset($GLOBALS["CLASS_USERSMENUS"])){$user=new usersMenus();$GLOBALS["CLASS_USERSMENUS"]=$user;}else{$user=$GLOBALS["CLASS_USERSMENUS"];}
		
		if($groupname==null){$groupname=$username;}
		if($displayname==null){$displayname=$username;}
		
		if(!$this->UnixGroupExists($groupname)){
			$groupadd=$this->find_program("groupadd");
			if(is_file("/etc/group.lock")){@unlink("/etc/group.lock");}
			if(is_file("/etc/gshadow.lock")){@unlink("/etc/gshadow.lock");}
			
			$cmd="$groupadd $groupname >/dev/null 2>&1";
			if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
			shell_exec($cmd);	
			if(!$this->UnixGroupExists($groupname)){return false;}
		}
		
		if(is_file("/etc/shadow.lock")){@unlink("/etc/gshadow.lock");}
		if(is_file("/etc/passwd.lock")){@unlink("/etc/passwd.lock");}
		
		$cmd=null;
		$useradd=$this->find_program("useradd");
		if($user->LinuxDistriCode=="CENTOS"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="SUSE"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}		
		if($user->LinuxDistriCode=="FEDORA"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="MANDRAKE"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}						
		if($cmd==null){
			$cmd="useradd -m -g $groupname -G adm -d /home/$username -s /bin/bash -c \"$displayname\" $username >/dev/null 2>&1";
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
		shell_exec($cmd);	
		if(!$this->UnixUserExists($username)){
		if($user->LinuxDistriCode=="CENTOS"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="SUSE"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}		
		if($user->LinuxDistriCode=="FEDORA"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="MANDRAKE"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}						
		if($cmd==null){
			$cmd="useradd -m --d /home/$username -s /bin/bash -c \"$displayname\" $username >/dev/null 2>&1";
		}
			if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
			shell_exec($cmd);
		}
		
		return $this->UnixUserExists($username);
		
		
	}
	
	public function SystemUserExists($username){
		$userinfo = posix_getpwnam($username);
		if(!isset($userinfo["uid"])){return false;}
		if(!is_numeric($userinfo["uid"])){return false;}
		if($userinfo["uid"]<1){return false;}
		return true;
	}
	
	public function SystemUserGetuid($username){
		$userinfo = posix_getpwnam($username);
		if(!isset($userinfo["uid"])){return null;}
		if(!is_numeric($userinfo["uid"])){return null;}
		if($userinfo["uid"]<1){return null;}
		return $userinfo["uid"];
	}

	public function SystemGroupUid($groupname){
		$userinfo = posix_getgrnam($groupname);
		if(!isset($userinfo["gid"])){return null;}
		if(!is_numeric($userinfo["gid"])){return null;}
		if($userinfo["gid"]<1){return null;}
		return $userinfo["gid"];
	}	
	
	
	public function SystemCreateUser($username,$group=null,$xshell=null,$sHome=null){
		
		if(!$this->SystemUserExists($username)){
			$useradd=$this->find_program("useradd");
			if(!is_file($useradd)){return;}
			if($xshell==null){$xshell="/bin/sh";}
			if($group<>null){
				if(!$this->SystemGroupExists($group)){
					if($GLOBALS["VERBOSE"]){echo "Creating Group `$group`...\n";}
					if($this->SystemCreateGroup($group)){return false;}}
			}
			$cmd[]=$useradd;
			if(strlen($xshell)>2){$cmd[]="-s $xshell";}
			if(strlen($sHome)>2){$cmd[]="-d \"$sHome\"";}
			if($group<>null){$cmd[]="-g $group";}
			
			
			$cmd[]=$username;
			$cmd_finale=@implode(" ", $cmd);
			if($GLOBALS["VERBOSE"]){echo $cmd_finale."\n";}
			exec("$cmd_finale 2>&1",$results);
			if($GLOBALS["VERBOSE"]){echo @implode("\n", $results);}
		}
		
		if($group<>null){
			if(!$this->SystemGroupExists($group)){if($this->SystemCreateGroup($group)){return false;}}
			if(!$this->SystemIsUserIngroup($username,$group)){
				$usermod=$this->find_program("usermod");
      			shell_exec("$usermod -a -G $group $username >/dev/null 2>&1 &");
       			shell_exec("$usermod -A $group $username >/dev/null 2>&1 &");
       			shell_exec("$usermod --append $group $username >/dev/null 2>&1 &");
			}
		}
	}
	
	public function SystemCreateGroup($group){
		$groupadd=$this->find_program("groupadd");
		if(!is_file($groupadd)){return false;}
		if($GLOBALS["VERBOSE"]){echo "Create group $groupadd $group\n";}
		shell_exec("$groupadd $group");
		return $this->SystemGroupExists($group);
	}
	
	public function SystemGroupExists($groupname){
		if(strpos($groupname, ":")>0){
			$exp=explode(":",$groupname);
			$groupname=$exp[1];
		}
		$userinfo = posix_getgrnam($groupname);
		if($GLOBALS['VERBOSE']){echo "posix_getgrnam($groupname) -> `{$userinfo["gid"]}`\n";}
		if(!isset($userinfo["gid"])){return false;}
		if(!is_numeric($userinfo["gid"])){return false;}
		return true;		
	}
	
	public function SystemGetMemberID($username){
		$userinfo = posix_getpwnam($username);
		if(isset($userinfo["uid"])){
			if(is_numeric($userinfo["uid"])){return $userinfo["uid"];}
		}
		
	}
	
	public function SystemIsUserIngroup($username,$groupname){
		
		if(!$this->SystemGroupExists($groupname)){
			$this->ToSyslog("SystemIsUserIngroup Group `$groupname` doesn't exists.. [FALSE]");
			return false;
		}
		
		if(strpos($username, ":")>0){
			$exp=explode(":",$username);
			$username=$exp[0];
		}
		
		if(strpos($groupname, ":")>0){
			$exp=explode(":",$groupname);
			$groupname=$exp[1];
		}
		
		
		$username=trim(strtolower($username));
		$groupname=trim(strtolower($groupname));
		
		if($username=="root"){
			if($groupname=="root"){
				return true;
			}
		}
		
		$userinfo = posix_getgrnam($groupname);
		
		$userid=$this->SystemGetMemberID($username);
		
		if($GLOBALS["VERBOSE"]){echo "`$groupname` ".count($userinfo["members"])." members for $username/$userid\n";}
		while (list ($num, $user) = each ($userinfo["members"]) ){
			
			$user=trim(strtolower($user));
			if($GLOBALS["VERBOSE"]){echo "`$groupname` Member: $user ==? $username\n";}
			if($user==$username){return true;}
			
		}
		$this->ToSyslog("SystemIsUserIngroup `$username` is not a member of `$groupname`.. [FALSE]");
		return false;
		
	}
	public function CYRUS_PID_PATH(){
		if(is_file('/var/run/cyrus-master.pid')){return '/var/run/cyrus-master.pid';}
		if(is_file('/var/run/cyrmaster.pid')){return '/var/run/cyrmaster.pid';}
		if(is_file('/var/run/cyrus.pid')){return '/var/run/cyrus.pid';}
		return "/var/run/cyrmaster.pid";
	}
public function CYRUS_DAEMON_BIN_PATH(){
	$cyrmaster=$this->find_program("cyrmaster");
	if(is_file($cyrmaster)){return $cyrmaster;}
	if(is_file('/usr/lib/cyrus-imapd/cyrus-master') ){return '/usr/lib/cyrus-imapd/cyrus-master';}
   if (is_file('/usr/lib/cyrus/bin/master') ){return '/usr/lib/cyrus/bin/master';}
    if (is_file('/usr/sbin/cyrmaster') ){return '/usr/sbin/cyrmaster';}
    if (is_file('/usr/sbin/cyrmaster') ){return '/usr/sbin/cyrmaster';}
    if (is_file('/opt/artica/cyrus/bin/master') ){return '/opt/artica/cyrus/bin/master';}
}	
public function CYRADM_PATH(){

	if (is_file('/usr/bin/cyradm') ){return '/usr/bin/cyradm';}
	if (is_file('/usr/share/bin/cyradm') ){return '/usr/share/bin/cyradm';}
	if (is_file('/opt/artica/bin/cyradm') ){return '/opt/artica/bin/cyradm';}
}
function CYRUS_SYNC_CLIENT_BIN_PATH(){
	$sync_client=$this->find_program("sync_client");
	if(is_file($sync_client)){return $sync_client;}
	if(is_file('/usr/lib/cyrus-imapd/sync_client')){return '/usr/lib/cyrus-imapd/sync_client';}
	if(is_file('/usr/lib/cyrus/bin/sync_client')){return '/usr/lib/cyrus/bin/sync_client';}
	if(is_file('/usr/sbin/sync_client')){return '/usr/sbin/sync_client';}
}
public function LOCATE_CYRUS_QUOTA(){
	$sync_client=$this->find_program("cyrquota");
	if(is_file($sync_client)){return $sync_client;}
	if(is_file('/usr/sbin/cyrquota') ){ return '/usr/sbin/cyrquota';}
	if(is_file('/usr/lib/cyrus/bin/quota') ){ return '/usr/lib/cyrus/bin/quota';}
	if(is_file('/usr/lib/cyrus-imapd/quota') ){ return '/usr/lib/cyrus-imapd/quota';}
}
	
	public function JAVA_HOME_GET(){
		$f=explode("\n", @file_get_contents("/etc/environment"));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
				$re[1]=str_replace('"', "", $re[1]);
				if(is_dir($re[1])){return $re[1];}
			}
		}
		
	}
	
	public function MYSQL_REPAIR_TABLE_FILE_NOT_FOUND($file,$db,$table){
		$Dir=dirname($file);
		if($table==null){return;}
		$FF=array();
		$files=$this->DirFiles("$Dir","$table-[0-9]+\.BAK$");
		while (list ($filename, $f) = each ($files) ){
			if(!preg_match("#$table-([0-9]+)\.BAK$#", $filename,$re)){continue;}
			$FF[$re[1]]=$filename;
		}
		
		krsort($FF);
		while (list ($index, $filename) = each ($FF) ){$tt[]=$filename;}
		$lastfile=$tt[0];
		$L[]="last backuped file=\"$lastfile\"<br>\n";
		
		
		if(!is_file($file)){
			if($lastfile<>null){
				$L[]="replace backuped file=$Dir/$lastfile to $file<br>\n";
				@copy("$Dir/$lastfile", $file);
			}
		}
		if(is_file($file)){
			@chmod($file, 0755);
			@chown($file, "mysql");
			@chgrp($file, "mysql");
			$L[]="$file apply permissions done...<br>\n";
		}else{
			$fileTMD=str_replace(".MYD", ".TMD", $file);
			if(is_file($fileTMD)){@copy($fileTMD,$file);@chown($file, "mysql");@chgrp($file, "mysql");}else{
			$L[]="$file no such file....<br>\n";
			}
		}		
		return @implode("", $L);
		
	}
	
	
	
	
	public function JAVA_HOME_SET($dir){
		$f=explode("\n", @file_get_contents("/etc/environment"));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
				$re[1]=str_replace('"', "", $re[1]);
				if($re[1]==$dir){return;}
				$f[$num]="JAVA_HOME=\"$dir\"";
				@file_put_contents("/etc/environment", @implode("\n", $f));
				return;
			}
		}
		
		$f[]="JAVA_HOME=\"$dir\"";
		@file_put_contents("/etc/environment", @implode("\n", $f));
		
	}	
	
	public function UnixGroupExists($groupname){
		$getent=$this->find_program("getent");
		exec("$getent group $groupname 2>&1",$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#^$groupname#", $val)){
				if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() $val Match\n";}
				return true;}else{if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() $val no match\n";}}
		}
		if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() group:$groupname does not exists in database\n";}	
		return false;
		
	}
	
	public function UnixUserExists($username){
		
		$getent=$this->find_program("getent");
		$cmd="$getent shadow $username 2>&1";
		if($GLOBALS["VERBOSE"]){echo "UnixUserExists() \"$cmd\"\n";}
		exec($cmd,$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#^$username#", $val)){
				if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val Match\n";}
				return true;}
				if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val No match\n";}
		}
		if($GLOBALS["VERBOSE"]){echo "UnixUserExists() user:$username does not exists in database\n";}	
		return false;
	}
	
	function ldap_GET_CONFS(){
	
		$SLAPD_CONF=$this->SLAPD_CONF_PATH();
		$f=explode("\n",@file_get_contents($SLAPD_CONF));
		while (list ($index, $line) = each ($f) ){
			if(preg_match("#suffix.*?\"(.+?)\"#", $line,$re)){
				$ARRAY["SUFFIX"]=$re[1];
				continue;
			}
	
			if(preg_match("#rootdn.*?\"(.+?)\"#", $line,$re)){
				$ARRAY["DN"]=$re[1];
				continue;
			}
			if(preg_match("#rootpw.*?\"(.+?)\"#", $line,$re)){
				$ARRAY["PWD"]=$re[1];
				continue;
			}
	
		}
	
		return $ARRAY;
	
	}
	
	public function file_ext($path){
		$path_info = pathinfo($path);
		return $path_info['extension'];
		
	}
	
	public function GetMyHostId(){
		return $this->GetUniqueID();
		
	}	
	
	
	public function SquidStatsApplianceUri(){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$REMOTE_SSERVER=$RemoteStatisticsApplianceSettings["SERVER"];
		$REMOTE_SPORT=$RemoteStatisticsApplianceSettings["PORT"];
		$REMOTE_SSL=$RemoteStatisticsApplianceSettings["SSL"];
		if($REMOTE_SSL==1){$refix="https";}else{$refix="http";}
		return "$refix://$REMOTE_SSERVER:$REMOTE_SPORT";		
		
	}
	
	
	public function IsSquidTaskCanBeExecuted(){
		if(!class_exists("settings_inc")){include_once(dirname(__FILE__)."/class.settings.inc");}
		$users=new settings_inc();
		$sock=new sockets();
		$update=true;
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
		if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableWebProxyStatsAppliance==1){return true;}	
		$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
		if($CategoriesRepositoryEnable==1){return true;}
		if(!$users->SQUID_INSTALLED){$update=false;}
		return $update;
	}
	
	
	public function squidclient_mgrport(){
			if(!is_file("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort")){
			$php5=$this->LOCATE_PHP5_BIN();
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
			shell_exec("/etc/init.d/squid restart --class-unix=".__LINE__);
		}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort")){
			$http_port=$this->squid_get_alternate_port();
		}else{
			$http_port=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
		}
		if(!is_numeric($http_port)){
			$http_port=$this->squid_get_alternate_port();
		}
		return $http_port;
	}
	
	public function squidclient_builduri(){
		
		$squidbin=$this->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){return;}
		
		$squidclient=$this->find_program("squidclient");
		if($squidclient==null){
			writelogs_framework("Unable to stat squidclient !!",__FUNCTION__,__LINE__);;
			return;
		}
		
		$http_port=$this->squidclient_mgrport();
		$interface="127.0.0.1";
		if(preg_match("#([0-9\.]+):([0-9]+)#",$http_port,$re)){$interface=$re[1];$http_port=$re[2];}
		if($interface=="0.0.0.0"){$interface="127.0.0.1";}
		$cmd="$squidclient -l 127.0.0.1 -h $interface -p $http_port mgr";
		writelogs_framework("$cmd",__FUNCTION__,__LINE__);
		return $cmd;
	}	
	
	public function tune2fs_values($dev){
		$tune2fs=$this->find_program("tune2fs");
		$cmd="$tune2fs -l $dev 2>&1";
		exec($cmd,$results);
		$array=array();
		
		while (list ($num, $line) = each ($results)){
			
			if(preg_match("#Bad magic number#", $line)){
				$array["ERROR"]=true;
				$array["ERROR_TEXT"]=$line;
			}
				
			
			if(!preg_match("#^(.+?):\s+(.+)#i",$line,$re)){continue;}
			$key=trim(strtoupper($re[1]));
			$key=str_replace(" ", "_", $key);
			$value=trim($re[2]);
			if($value=="<not available>"){$value=null;}
			if($value=="<none>"){$value=null;}
			$array[$key]=$value;
		}
		
		$results=array();
		$df=$this->find_program("df");
		exec("df -i $dev 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array["INODES_MAX"]=$re[1];
				$array["INODES_USED"]=$re[2];
				$array["INODES_AVAILABLE"]=$re[3];
				$array["INODES_POURC"]=$re[4];
				break;
			}
		}
		$BLKID=$this->BLKID_INFOS($dev);
		while (list ($num, $ligne) = each ($BLKID) ){
			$array[$num]=$ligne;
		}
		
		return $array;
	}
	


	public function sysctl($key,$value){
		$keyS=str_replace(".", "\.", $key);
		$t=false;
		$f=explode("\n",@file_get_contents("/etc/sysctl.conf"));
		while (list ($num, $val) = each ($f) ){
			if(substr($val, 0,1)=="#"){continue;}
			if(preg_match("#^$keyS.*?=#", $val)){
				$f[$num]="$key = $value";
				$t=true;
				break;
			}
		}
		
		if(!$t){
			$f[]="$key = $value";
		}
		reset($f);
		@file_put_contents("/etc/sysctl.conf", implode("\n", $f));
		
	}
	
	
	
	
	
	public function LOCATE_CLAMDSOCKET(){
		$f=@explode("\n",@file_get_contents($this->LOCATE_CLAMDCONF()));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#LocalSocket\s+(.+)#",$val,$re)){
				return trim($re[1]);
			}
		}
	}
	
	public function POLICYD_WEIGHT_GET($key){
		$f=@explode("\n",@file_get_contents($this->LOCATE_POLICYDWEIGHT_CONF()));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#\$$key.*?=(.+?);#",$val,$re)){
				return trim($re[1]);
			}
		}
	}
	
	public function SQUID_GET_DATADIR(){
		
		$squidbin=$this->find_program("squid3");
		if(!is_file($squidbin)){$squidbin=$this->find_program("squid");}
		exec("$squidbin -v",$res);
		$line=@implode(" ",$res);
		if(preg_match("#--datadir=(.+?)'#",$line,$re)){return trim($re[1]);}
	}
	
	public function SQUID_ENABLED(){
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
			if($GLOBALS["VERBOSE"]){echo "/etc/artica-postfix/STATS_APPLIANCE -> TRUE NOT A SQUID MACHINE\n";}
			return 0;
		}
		if(!$this->SQUID_INSTALLED()){
			if($GLOBALS["VERBOSE"]){echo "SQUID_INSTALLED -> REPORT FALSE NOT A SQUID MACHINE\n";}
			return 0;
		}
		if($GLOBALS["VERBOSE"]){echo "CHECKING -> /etc/artica-postfix/settings/Daemons/SQUIDEnable\n";}
		if(!is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 1);}
		return intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	}
	
	public function SQUID_TAIL_ENABLED(){
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){return 1;}
		$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
		$SquidPerformance=0;
		if(is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));}
		if(!is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 1);}
		$SQUIDEnable=@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable");
		$InfluxUseRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
		$InfluxSyslogRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote"));
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
		
		$EnableInfluxDB=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB"));
		if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}
		
		
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$enabled=1;
		if($SQUIDEnable==0){return 0;}
		if($SquidPerformance>1){return 0;}
		if($InfluxSyslogRemote==1){return 0;}
		return 1;
			
		
		
		
	}
	
	
	public function LOCATE_POLICYDWEIGHT_CONF(){
		    return "/etc/policyd-weight.conf";
		
	}
	
	
	function SQUID_CACHE_FROM_SQUIDCONF(){
		$array=array();
		$SQUID_CONFIG_PATH=$this->SQUID_CONFIG_PATH();
	
		$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
				$array[trim($re[2])]=trim($re[1]);
			}
	
		}
		
		return $array;
	}

	function SQUID_CACHE_FROM_SQUIDCONF_FULL(){
		$array=array();
		$SQUID_CONFIG_PATH=$this->SQUID_CONFIG_PATH();
	
		$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
				$array[trim($re[2])]=$line;
			}
	
		}
	
		return $array;
	}	
	
	
	public function LOCATE_STUNNEL(){
		$bins[]="stunnel5";
		$bins[]="stunnel4";
		$bins[]="stunnel3";
		while (list ($num, $prog) = each ($bins)){
			$a=null;
			$a=$this->find_program($prog);
			if($a<>null){return $a;}
		}
		
	}
	public function LOCATE_STUNNEL_INIT(){
		$bins[]="stunnel";
		$bins[]="stunnel5";
		$bins[]="stunnel4";
		$bins[]="stunnel3";
		while (list ($num, $prog) = each ($bins)){
			$a=null;
			if(is_file("/etc/init.d/$prog")){return "/etc/init.d/$prog";}
			
		}
	
	}	
	
private function icmpChecksum($data){
	if (strlen($data)%2)
	$data .= "\x00";
	 
	$bit = unpack('n*', $data);
	$sum = array_sum($bit);
	 
	while ($sum >> 16)
	$sum = ($sum >> 16) + ($sum & 0xffff);
	 
	return pack('n*', ~$sum);
}	


function PROCESS_UPTIME($pid){
	$PROC_STAT=$this->LOCATE_PROCSTAT();
	if(strlen($PROC_STAT)>3){
 		exec("$PROC_STAT $pid 2>&1",$results);
	    while (list ($num, $ligne) = each ($results) ){
	    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
	    		$tt=$this->time_duration($re[1]);
	    		$tt=str_replace(" Months,","M",$tt);
	    		$tt=str_replace(" days,","d",$tt);
	    		$tt=str_replace(" hours,","h",$tt);
	    		$tt=str_replace(" minutes,","mn",$tt);
	    		$tt=str_replace(" seconds","s",$tt);
	    		$tt=str_replace(" hour,","h",$tt);
	    		$tt=str_replace(" minute,","mn",$tt);
	    		$tt=str_replace(" second","s",$tt);    		
	    		$me="uptime=$tt";
	    		break;
	    	}
	    		
	    	}
	}

	return $me;
	
}

public function PingHost($cible,$nocache=false){
	
	$cachefile="/etc/artica-postfix/pinghosts.cache";
	if(!$nocache){
		$cache=unserialize(@file_get_contents($cachefile));
		$current_time=time();
		if(is_array($cache)){
			if(count($cache)>700){@unlink($cachefile);$cache=array();}	
			if(isset($cache[$cible])){
				$old_time=$cache[$cible]["time"];
				if(!is_numeric($old_time)){$old_time=0;}
				$difference = ($current_time - $old_time); 
				if($difference<30){return $cache[$cible]["result"];}
				
			}
		}
	}
	
	
	
	$timeout=1;
	$type= "\x08";
	$code= "\x00";
	$checksum= "\x00\x00";
	$identifier = "\x00\x00";
	$seqNumber = "\x00\x00";
	$data= "Scarface";
	$package = $type.$code.$checksum.$identifier.$seqNumber.$data;
	//$checksum = $this->icmpChecksum($package); // Calculate the checksum
	
    $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    $socket  = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
	socket_connect($socket, $cible, null);
	$ts = microtime(true);
    
    if(!socket_send($socket, $package, strLen($package), 0)){
    	return false;
    	
    }
    if (socket_read($socket, 255)){$result = true;}else{ $result = false;}
    if(is_resource($socket)){socket_close($socket);}
	if(!$result){
		if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible FALSE\n";}
		$result=$this->PingHostCMD($cible);
	}else{
		if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible TRUE\n";}
	}
	$cache[$cible]["result"]=$result;
	$cache[$cible]["time"]=time();
	if(!$result){writelogs_framework("$cible -> FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}else{writelogs_framework("$cible -> TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if(!$nocache){
		writelogs_framework("Saving : $cachefile",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents($cachefile, serialize($cache));
	}
		return $result;
	}
	
function NETWORK_DEFAULT_IP_ADDR(){	
	$NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES();
	for($i=0;$i<5;$i++){
		if(!isset($NETWORK_ALL_INTERFACES["eth$i"])){continue;}
		if($NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"]=="0.0.0.0"){continue;}
		if(preg_match("#127.0.0#", $NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"])){continue;}
		return $NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"];
		
	}
	
	for($i=0;$i<5;$i++){
		if(!isset($NETWORK_ALL_INTERFACES["br$i"])){continue;}
		if($NETWORK_ALL_INTERFACES["br$i"]["IPADDR"]=="0.0.0.0"){continue;}
		if(preg_match("#127.0.0#", $NETWORK_ALL_INTERFACES["br$i"]["IPADDR"])){continue;}
		return $NETWORK_ALL_INTERFACES["br$i"]["IPADDR"];
	}
}	
	
	
	function NETWORK_REPORT(){
		
		$results[]="Report....:";
		$ifconfig=$this->find_program("ifconfig");
		exec("$ifconfig -a 2>&1",$results);
		$ip=$this->find_program("ip");
		exec("$ip link show 2>&1",$results);
		exec("$ip addr 2>&1",$results);
		$results[]="Routes....:";
		exec("$ip route 2>&1",$results);
		$results[]="DNS.......:\n";
		$results[]=@file_get_contents("/etc/resolv.conf");
		$results[]="TESTS pings......:";
		$ARRAY=$this->NETWORK_ALL_INTERFACES();
		while (list ($interface, $ligne) = each ($ARRAY) ){
			if($ligne["GATEWAY"]<>null){
				$results[]=$this->report_ping($interface,$ligne["GATEWAY"]);
			}
			
		}
		
		return @implode("\r\n", $results);
	}

	
	private function report_ping($from,$to){
		$array[]="Ping from $from -> $to";
		$ping=$this->find_program("ping");
		exec("$ping -I $from -c 2 -i 0 $to 2>&1",$array);
		return @implode("\r\n", $array);
		
	}
	
	
	public function KAV4PROXY_GET_VALUE($key,$value){
		    $ini=new iniFrameWork($this->KAV4PROXY_GET_CONF_PATH());
		    return $ini->get($key,$value);
		
	}	
	
	public function PingHostCMD($cible,$source=null){
		if(isset($GLOBALS["PingHostCMD"][$cible])){return $GLOBALS["PingHostCMD"][$cible];}
		$ping=$this->find_program("ping");
		if($source<>null){
			$source=" -I $source ";
		}
		exec("$ping $source-v -c1 -t3 -W1 -w1 -p ff $cible 2>&1",$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#, ([0-9]+)\% packet loss#", $val,$re)){
				if($re[1]==0){$GLOBALS["PingHostCMD"][$cible]=true;
				if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible TRUE\n";}
				return true;
				}else{
					if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible FALSE\n";}
					$GLOBALS["PingHostCMD"][$cible]=false;
					return false;}
			}
		}
		
	}
	
	


	public function KAV4PROXY_GET_CONF_PATH(){
		if(is_file('/etc/opt/kaspersky/kav4proxy.conf')){return '/etc/opt/kaspersky/kav4proxy.conf';}
		if(is_file('/opt/kaspersky/kav4proxy/etc/opt/kaspersky/kav4proxy.conf')){return '/opt/kaspersky/kav4proxy/etc/opt/kaspersky/kav4proxy.conf';}
	}
	
	public function KAV_MILTER_PATTERN_FILE(){
		if(is_file("/var/db/kav/5.6/kavmilter/bases/master.xml")){return "/var/db/kav/5.6/kavmilter/bases/master.xml";}
		if(is_file("/var/db/kav/5.6/kavmilter/bases/kavset.xml")){return "/var/db/kav/5.6/kavmilter/bases/kavset.xmll";}
		if(is_file("/var/db/kav/5.6/kavmilter/bases/av-i386-0607g.xml")){return "/var/db/kav/5.6/kavmilter/bases/av-i386-0607g.xml";}
	}
	
	public function KAV4PROXY_PATTERN_FILE(){
		return $this->KAV4PROXY_GET_VALUE("path","BasesPath");
	}
	
	
	
	public function LOCATE_SMBD_PID(){
		if(is_file("/var/run/smbd.pid")){return "/var/run/smbd.pid";}
		if(is_file("/var/run/samba/smbd.pid")){return "/var/run/samba/smbd.pid";}
		
	}
	public function LOCATE_NMBD_PID(){
		if(is_file("/var/run/nmbd.pid")){return "/var/run/nmbd.pid";}
		if(is_file("/var/run/samba/nmbd.pid")){return "/var/run/samba/nmbd.pid";}
		
	}	
	public function LOCATE_WINBINDD_PID(){
		if(is_file("/var/run/winbindd.pid")){return "/var/run/winbindd.pid";}
		if(is_file("/var/run/samba/winbindd.pid")){return "/var/run/samba/winbindd.pid";}
		
	}	

	public function LOCATE_CYRUS_IPURGE(){
		if(is_file("/usr/sbin/ipurge")){return "/usr/sbin/ipurge";}
		if(is_file("/usr/lib/cyrus/bin/ipurge")){return "/usr/lib/cyrus/bin/ipurge";}
		if(is_file("/usr/lib/cyrus-imapd/ipurge")){return "/usr/lib/cyrus-imapd/ipurge";}
		$this->events("Unable to stat cyrus ipurge tool");
	}	
	
	public function APACHE_MODULES_PATH(){
		if(isset($GLOBALS["LOCATE_APACHE_MODULES_PATH"])){return $GLOBALS["LOCATE_APACHE_MODULES_PATH"];}
		$GLOBALS["LOCATE_APACHE_MODULES_PATH"]=$this->LOCATE_APACHE_MODULES_PATH();
		return $GLOBALS["LOCATE_APACHE_MODULES_PATH"];
	}
	
	private function LOCATE_APACHE_MODULES_PATH(){
		if(is_file('/usr/lib/apache2/modules/mod_cgi.so') ) { return '/usr/lib/apache2/modules'; }
		if(is_file('/usr/lib/apache2/mod_cgi.so') ) { return '/usr/lib/apache2'; }
		if(is_file('/usr/lib/apache2/mod_perl.so') ) { return '/usr/lib/apache2'; }
		if(is_file('/usr/lib/apache/mod_alias.so') ) { return '/usr/lib/apache'; }
		if(is_file('/usr/lib64/httpd/modules/mod_cgi.so') ) { return '/usr/lib64/httpd/modules'; }
		if(is_file('/usr/lib/httpd/modules/mod_cgi.so') ) { return '/usr/lib/httpd/modules'; }
		if(is_file('/usr/local/apache-groupware/modules/mod_cgi.so') ) { return '/usr/local/apache-groupware/modules'; }
		
		if(isset($GLOBALS["APACHE_MODULES_PATH"])){return $GLOBALS["APACHE_MODULES_PATH"];}
		$DIRS[]="/usr/lib/apache2/modules";
		$DIRS[]="/usr/lib/apache/modules";
		$DIRS[]="/usr/lib64/apache/modules";
		$DIRS[]="/usr/lib64/apache2/modules";
		$DIRS[]="/usr/lib64/httpd/modules";
		$DIRS[]="/usr/lib/httpd/modules";
		
		$f[]="mod_cgi.so";
		$f[]="mod_perl.so";
		$f[]="mod_alias.so";
		
		while (list ($num, $directory) = each ($DIRS)){
			reset($f);
			while (list ($num, $filename) = each ($f)){
				if($GLOBALS["VERBOSE"]){echo "APACHE_MODULES_PATH:: Checking \"$directory/$filename\"\n";}
				if(is_file("$directory/$filename")){
					if($GLOBALS["VERBOSE"]){echo "APACHE_MODULES_PATH:: Found \"$directory\"\n";}
					$GLOBALS["APACHE_MODULES_PATH"]=$directory;
					return $directory;}
			}
		}
	}

	
	public function LOCATE_CYRQUOTA(){
		if(is_file("/usr/sbin/cyrquota")){return "/usr/sbin/cyrquota";}
		if(is_file("/usr/lib/cyrus/bin/quota")){return "/usr/lib/cyrus/bin/quota";}
		if(is_file("/usr/lib/cyrus-imapd/quota")){return "/usr/lib/cyrus-imapd/quota";}
		$this->events("Unable to stat cyrus cyrquota tool");
	}
	
	public function LOCATE_CYRUS_DAEMON(){
		if(is_file("/usr/lib/cyrus-imapd/cyrus-master")){return "/usr/lib/cyrus-imapd/cyrus-master";}
		if(is_file("/usr/lib/cyrus/bin/master")){return "/usr/lib/cyrus/bin/master";}
		if(is_file("/usr/sbin/cyrmaster")){return "/usr/sbin/cyrmaster";}
		if(is_file("/opt/artica/cyrus/bin/master")){return "/opt/artica/cyrus/bin/master";}
		$this->events("Unable to stat cyrus master tool");
	}
	
	public function LOCATE_CYRUS_PIDPATH(){
		if(is_file("/var/run/cyrus-master.pid")){return "/var/run/cyrus-master.pid";}
		if(is_file("/var/run/cyrmaster.pid")){return "/var/run/cyrmaster.pid";}
		if(is_file("/var/run/cyrus.pid")){return "/var/run/cyrus.pid";}
		$this->events("Unable to stat cyrus pid path");
	}

	public function KAV4PROXY_PATTERN(){
		@chmod("/usr/share/artica-postfix/bin/artica-update",0755);
		exec("/usr/share/artica-postfix/bin/artica-update --kav4proxy--pattern",$results);
		return trim(@implode("",$results));
		
	}
	

	
	public function LOCATE_MILTER_GREYLIST_PID(){
		if(is_file("/var/run/milter-greylist/milter-greylist.pid")){return "/var/run/milter-greylist/milter-greylist.pid";}
	}
	
	public function IS_OPENLDAP_RUNNING(){
		$pid_path=$this->LOCATE_SLAPD_PID_PATH();
		$pidnum=$this->get_pid_from_file($pid_path);
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." pid num: $pidnum\n";}
		return $this->process_exists($pidnum);
	}
	
	private function LOCATE_SLAPD_PID_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
		$path=$this->LOCATE_SLAPD_CONF_PATH();
		$f=explode("\n",@file_get_contents($path));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#pidfile\s+(.+)#",$line,$re)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__." pid path: $path\n";}
				$GLOBALS[__FUNCTION__]=trim($re[1]);
				return trim($re[1]);
			}else{
			
			}
		}
		
	}
	
	private function LOCATE_SLAPD_CONF_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
		$f[]="/etc/ldap/slapd.conf";
		$f[]="/etc/openldap/slapd.conf";
		$f[]="/usr/local/etc/openldap/slapd.conf";
		while (list ($num, $path) = each ($f)){
			if(is_file($path)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__." path: $path\n";}
				$GLOBALS[__FUNCTION__]=$path;
				return $path;
			}
		}
		
		
	}
	
	private function VERBOSE_COMMAND_LINE($pid){
		return @file_get_contents("/proc/$pid/cmdline");
	}
	
	private function LOCATE_PROCSTAT(){
		$procstat=$this->find_program("procstat");
		if(is_file($procstat)){return $procstat;}
		if(is_file("/usr/share/artica-postfix/bin/procstat")){
			@chmod("/usr/share/artica-postfix/bin/procstat",0755);
			return "/usr/share/artica-postfix/bin/procstat";}
		$gcc=$this->find_program("gcc");
		if(!is_file($gcc)){return;}
		shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
		if(is_file("/usr/share/artica-postfix/bin/procstat")){
			@chmod("/usr/share/artica-postfix/bin/procstat",0755);
			return "/usr/share/artica-postfix/bin/procstat";}
	}
	
	Public function GetSingleMemoryOf($pid){
		if($pid<1){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
			return null;
		}
		$rss=$this->GetMemoriesOfUniquePID($pid,true);
		//$vm=$this->PROCESS_CACHE_MEMORY($pid,true);
		if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
		$l[]="master_pid=$pid";	
   	 	$l[]="master_memory=$rss";
    	//$l[]="master_cached_memory=$vm";
    	$l[]="processes_number=1";
    	
    	if(!is_file("/usr/share/artica-postfix/bin/procstat")){
    		$gcc=$this->find_program("gcc");
    		if(!is_file($gcc)){return;}
    		shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
    	} 	
    	
    	if(!is_file("/usr/share/artica-postfix/bin/procstat")){
    		$this->events("Unable to compile procstat !!! in " .__CLASS__."/".__FUNCTION__." Line".__LINE__);
    	}
		
    @chmod("/usr/share/artica-postfix/bin/procstat",0755);
  	exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#GLIBC.+?not found#",$ligne)){
    		@unlink("/usr/share/artica-postfix/bin/procstat");
    		return;
    	}
    	
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		$l[]="uptime=$tt";}
    		
    	}
    $l[]="";	
    return implode("\n",$l);return;    	
    	
		
	}
	
public function TCP_TUNE_SQUID_DEFAULT(){
	$echo = $this->find_program("echo");
	$sysctl=$this->find_program("sysctl");
	shell_exec("$sysctl -w vm.vfs_cache_pressure=50 >/dev/null 2>&1");
	
	$DisableTCPEn=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableTCPEn")));
	$DisableTCPWindowScaling=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableTCPWindowScaling")));
	if($DisableTCPEn==1){
		shell_exec("$echo 0 > /proc/sys/net/ipv4/tcp_ecn >/dev/null 2>&1");
	}else{
		shell_exec("$echo 2 > /proc/sys/net/ipv4/tcp_ecn >/dev/null 2>&1");
	}
	
	
	if($DisableTCPWindowScaling==1){
		shell_exec("$echo 0 > /proc/sys/net/ipv4/tcp_window_scaling >/dev/null 2>&1");
	}else{
		shell_exec("$echo 1 > /proc/sys/net/ipv4/tcp_window_scaling >/dev/null 2>&1");
	}
	
	//shell_exec("$echo 0 >/proc/sys/net/ipv4/tcp_window_scaling"); // will quite noticeably degrade TCP performance. 
}
	
function THREAD_COMMAND_SET($zcommands){
	  $md5command=md5(trim($zcommands));
	  $FileDataCommand=@file_get_contents('/etc/artica-postfix/background');
	  $tbl=explode("\n",$FileDataCommand);
	 
	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
			if($ligne==null){continue;}
			 $mdtmp=md5(trim($ligne));
			 if($md5command==$mdtmp){
			 	writelogs_framework("`$zcommands` already scheduled as `$md5command`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			 	return false;
			 }
			}
		reset($tbl);
		}
		
	writelogs_framework("Schedule this command `$zcommands`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$tbl[]=$zcommands;
	@file_put_contents('/etc/artica-postfix/background',implode("\n",$tbl));
}	

public function GetMemoriesOfChild($pid){
	$pidsrc=$pid;
	if($pid<1){
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}return null;}


		$rss=$this->GetMemoriesOfUniquePID($pid,true);
		//$vm=$this->PROCESS_CACHE_MEMORY($pid,true);


		$count=1;
		if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
		$l[]="threads_socket=".$this->PROCESS_SOCKETS_NUM($pid);
		$l[]="application_installed=1";
		$l[]="master_pid=$pid";
		$l[]="master_memory=$rss";
		//$l[]="master_cached_memory=$vm";
		$l[]="processes_number=$count";


		exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
				$tt=$this->time_duration($re[1]);
				$tt=str_replace(" Months,","M",$tt);
				$tt=str_replace(" days,","d",$tt);
				$tt=str_replace(" hours,","h",$tt);
				$tt=str_replace(" minutes,","mn",$tt);
				$tt=str_replace(" seconds","s",$tt);
				$tt=str_replace(" hour,","h",$tt);
				$tt=str_replace(" minute,","mn",$tt);
				$tt=str_replace(" second","s",$tt);
				$l[]="uptime=$tt";}

		}
		$l[]="";
		return implode("\n",$l);return;

}


private function GetMemoriesOfUniquePID($pid){
	if(!is_numeric($pid)){return 0;}
	if($pid<2){return 0;}
	if(!is_file("/usr/share/artica-postfix/bin/meminfo.pl")){return $this->PROCESS_MEMORY($pid,true);}
	@chmod("/usr/share/artica-postfix/bin/meminfo.pl",0755);
	$line=exec("/usr/share/artica-postfix/bin/meminfo.pl $pid 2>&1");
	if(preg_match("#<(.+?)> <([0-9\.]+)\s+([A-Z]+)>#", $line,$re)){
		$UNIT=trim($re[3]);
		$VAL=trim($re[2]);
		if($GLOBALS["VERBOSE"]){echo "GetMemoriesOfUniquePID: {$re[1]} : $VAL $UNIT\n";}
		if($UNIT=="KB"){return $VAL;}
		if($UNIT=="MB"){return $VAL*1024;}
		if($UNIT=="GB"){$VAL= $VAL*1024;return $VAL*1024;}
	}
}	
	

	
	
public function GetMemoriesOf($pid,$unique=false){
	$pidsrc=$pid;
	if($pid<1){
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
		return null;
	}
	$pid=$this->PROCESS_PPID($pid);
	if($GLOBALS["VERBOSE"]){echo "Father of $pidsrc = $pid\n";}
	
	
		
		
	
	
	
	
	$rss=$this->GetMemoriesOfUniquePID($pid,true);
	$vm=$this->PROCESS_CACHE_MEMORY($pid,true);
	if(!$unique){
		$cmd=$this->find_program("pgrep")." -P $pid";
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
		$mpid=getmypid();
		exec($cmd,$results);
		$count=1;
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne<50){continue;}
			if($ligne==$mpid){continue;}
			$count=$count+1;
			$rss0=$this->GetMemoriesOfUniquePID($ligne,true);
			//$vm0=$this->PROCESS_CACHE_MEMORY($ligne,true);
			$rss=$rss+$rss0;
			
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $ligne \"$count\" rss:$rss0 vm:$vm0 Process: \"". $this->VERBOSE_COMMAND_LINE($ligne)."\"\n";}
			
			$cmd=$this->find_program("pgrep")." -P $ligne 2>&1";
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
			$results2=array();
			exec($cmd,$results2);
			while (list ($a, $b) = each ($results2) ){
				$b=trim($b);
				if($b<50){continue;}
				if($b==$mpid){continue;}
				$count=$count+1;
				$rss0=$this->GetMemoriesOfUniquePID($b,true);
				//$vm0=$this->PROCESS_CACHE_MEMORY($b,true);
				$rss=$rss+$rss0;
				
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $b \"$count\" TOT:$rss; rss:$rss0 vm:$vm0 Process: \"". $this->VERBOSE_COMMAND_LINE($b)."\"\n";}
			}
			
			
		}
	}
	if($count==0){$count=1;}
	if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
	$l[]="application_installed=1";
	$l[]="master_pid=$pid";	
	$l[]="master_time=".$this->PROCESS_TIME_INT($pid);
    $l[]="master_memory=$rss";
   // $l[]="master_cached_memory=$vm";
    $l[]="processes_number=$count";
    
    @chmod("/usr/share/artica-postfix/bin/procstat",0755);
    exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		$l[]="uptime=$tt";}
    		
    	}
    $l[]="";	
    return implode("\n",$l);return;
	
}

function PROCESS_TTL_TEXT($pid){
	if(!is_file("/usr/share/artica-postfix/bin/procstat")){return null;}
	@chmod("/usr/share/artica-postfix/bin/procstat",0755);
    exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		return $tt;
    		};
    		
    	}
   }
   
   
   FUNCTION MYSQL_DATA_DIR(){
   		$sock=new sockets();
   		$ChangeMysqlDir=$sock->GET_INFO("ChangeMysqlDir");
		if($ChangeMysqlDir==null){$ChangeMysqlDir="/var/lib/mysql";}
   		return $ChangeMysqlDir;
   }


function PROCESS_TTL($pid){
	if($pid<2){return 0;}
	exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
			$seconds=$re[1];
			return round(($seconds/60));
		}
	}
}

function vhosts_BuildCertificate($hostname){
	
	if($hostname=="__default__"){$hostnameText=$this->hostname_g();}else{$hostnameText=$hostname;}
	
	$dir=$GLOBALS["SSLKEY_PATH"];
	if(is_file("$dir/$hostname.crt")){
		if(is_file("$dir/$hostname.key")){return true;}
	}
	@mkdir($dir,0600,true);
	
	$sock=new sockets();
	$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
	if($CertificateMaxDays==null){$CertificateMaxDays=730;}
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork();
	}else{
		$ini=new Bs_IniHandler();
	}
	
	$ini->loadFile("/etc/artica-postfix/ssl.certificate.conf");
	$conf="/etc/ssl/certs/apache/$hostname.conf";
	$openssl=$this->find_program("openssl");
	echo "Starting......: ".date("H:i:s")." Certificate \"$conf\" ". count($ini->_params) ." Keys\n";
	
	unset($ini->_params["HOSTS_ADDONS"]);
	$ini->_params["default_db"]["default_days"]=$CertificateMaxDays;
	$ini->_params["server_policy"]["commonName"]=$hostnameText;
	$ini->_params["user_policy"]["commonName"]=$hostnameText;
	$ini->_params["default_ca"]["commonName"]=$hostnameText;
	$ini->_params["default_ca"]["commonName_value"]=$hostnameText;
	$ini->_params["policy_match"]["commonName"]=$hostnameText;
	$ini->_params["policy_anything"]["commonName"]=$hostnameText;
	reset($ini->_params);
	$ini->saveFile($conf);
	$cmd="$openssl req -new -x509 -batch -config $conf -nodes -out $dir/$hostname.crt -keyout $dir/$hostname.key -days $CertificateMaxDays 2>&1";
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){
		echo "$cmd\n".@implode("\n",$results)."\n";
	}
	
}

function getLOCALEUSED(){
	$locale=$this->find_program("env");
	writelogs_framework("$locale 2>&1",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	exec("$locale 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#LANG=(.+)#",$ligne,$re)){return trim($re[1]);}
	}
	
	shell_exec("LANG=en_US.utf8");
	return "en_US.utf8";
}


function SSHD_GET_FINGERPRINT($path){
	$path=$this->shellEscapeChars($path);
	$sshkeygen=$this->find_program("ssh-keygen");
	$maincmd="$sshkeygen -lf $path";

	writelogs_framework("$maincmd 2>&1" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	exec($maincmd,$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs_framework("$ligne" ,__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#^[0-9]+\s+(.+?)\s+#",$ligne,$re)){
			writelogs_framework("FOUND {$re[1]}" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return $re[1];
		}
	}
}

function KERNEL_CONFIG(){
	$uname=$this->find_program("uname");
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." uname= $uname\n";}
	exec("$uname -r 2>&1",$results);
	$kernelver=trim(@implode("", $results));
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." kernelver = $kernelver\n";}
	$file="/boot/config-$kernelver";
	if(!is_file($file)){
		writelogs_framework("$file no such file" ,__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $file no such file\n";}}
	$t=explode("\n",@file_get_contents($file));
	
	writelogs_framework("Open $file" ,__FUNCTION__,__FILE__,__LINE__);
	
	while (list ($num, $ligne) = each ($t) ){
		if(!preg_match("#^([A-Z_\-]+)=(.+)#", $ligne,$re)){continue;}
		if(trim($re[2])=="y"){$re[2]=1;}
		if(trim($re[2])=="m"){$re[2]=1;}
		$Array[trim($re[1])]=trim($re[2]);
		
	}
	
	return $Array;
	
}

function KERNEL_VERSION(){
	if(isset($GLOBALS["KERNEL_VERSION"])){return $GLOBALS["KERNEL_VERSION"];}
	$uname=$this->find_program("uname");
	$GLOBALS["KERNEL_VERSION"]=exec("$uname -r 2>&1");
	return trim($GLOBALS["KERNEL_VERSION"]);
}
function KERNEL_VERSION_FULL(){
	if(isset($GLOBALS["KERNEL_VERSION_FULL"])){return $GLOBALS["KERNEL_VERSION_FULL"];}
	$uname=$this->find_program("uname");
	$GLOBALS["KERNEL_VERSION_FULL"]=exec("$uname -a 2>&1");
	return trim($GLOBALS["KERNEL_VERSION_FULL"]);
}

function KERNEL_ARCH(){
	$results=$this->KERNEL_VERSION();
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $results\n";}	
	if(preg_match("#i[0-9]86#", $results)){return 32;}
	if(preg_match("#686-bigmem#", $results)){return 32;}
	if(preg_match("#x86_64#", $results)){return 64;}
	if(preg_match("#-amd64#", $results)){return 64;}
	
	$results=$this->KERNEL_VERSION_FULL();
	if(preg_match("#i[0-9]86#", $results)){return 32;}
	if(preg_match("#686-bigmem#", $results)){return 32;}
	if(preg_match("#x86_64#", $results)){return 64;}
	if(preg_match("#-amd64#", $results)){return 64;}	
	
}

function MYSQL_BIN_PARSE_ERROR($text){
	if(!is_array($text)){
		if(is_file($text)){$text=@file_get_contents($text);}
		$f=explode("\n", $text);
	}else{
		$f=$text;
	}
	
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#already exists#", $ligne)){continue;}
		if($GLOBALS["VERBOSE"]){echo "MYSQL_BIN_PARSE_ERROR:: `$ligne`\n";}
		if(preg_match("#: Got errno [0-9]+ on write#",$ligne)){
			$this->mysql_error=$ligne;
			return true;
		}
		
		if(preg_match("#Got error:\s+([0-9]+):", $ligne)){
			$this->mysql_error=$ligne;
			return true;
		}		
		
		if(preg_match("#^ERROR\s+([0-9]+)#", $ligne)){
			$this->mysql_error=$ligne;
			return true;
		}
		
		
		
	}
	
	return false;
	
}

function RESTART_SYSLOG_FORMAIL($onlysyslog=false){
	$this->RESTART_SYSLOG(true);
	shell_exec("/etc/init.d/postfix-logger restart");
	
}

function RESTART_SYSLOG($onlysyslog=false){
			$nohup=$this->find_program("nohup");
			if(is_file("/etc/init.d/rsyslog")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/rsyslog restart\n";}
				shell_exec("/etc/init.d/rsyslog restart");
			}
				
				
				
			if(is_file("/etc/init.d/syslog")){
				if($GLOBALS["VERBOSE"]){system($this->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init ");}else{
					shell_exec($this->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init >/dev/null 2>&1");
				}
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/syslog restart\n";}
				@chmod("/etc/init.d/syslog", 0755);
				shell_exec("$nohup /etc/init.d/syslog restart >/dev/null 2>&1 &");
			}
			
			
			
			if(is_file("/etc/init.d/sysklogd")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/sysklogd restart\n";}
				shell_exec("$nohup /etc/init.d/sysklogd restart >/dev/null 2>&1 &");
			}
			if(is_file("/etc/init.d/syslog-ng")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/syslog-ng restart\n";}
				shell_exec("$nohup /etc/init.d/syslog-ng restart >/dev/null 2>&1 &");
			}
			if($onlysyslog){return;}

			shell_exec("/etc/init.d/artica-syslog restart");
			shell_exec("/etc/init.d/postfix-logger restart");
			shell_exec("/etc/init.d/artica-postfix restart squid-tail");
			shell_exec("/etc/init.d/artica-postfix restart ocsweb");
			shell_exec("/etc/init.d/artica-postfix restart apachesrc");
			ufdbguard_admin_events("Restarting udbguard-tail after cleaning log files",__FUNCTION__,__FILE__,__LINE__,"system");
			shell_exec("/etc/init.d/ufdb-tail restart");	
	
}

function send_email_events($subject,$text,$context,$date=null,$attached_files=array(),$recipient=null){
	
	$arr_APP=array(
		"APP_ARTICA_BACKGROUND"=>"Artica Background Executor",
		"APP_OPENEMM_SENDMAIL"=>"SendMail for OpenEMM service",
		"APP_OPENEMM"=>"OpenEMM service",
		"APP_TOMCAT"=>"Tomcat Web server",
		"APP_GREENSQL"=>"GreenSQL Firewall",
		"APP_ZARAFA_WEB"=>"Zarafa web server",
		"APP_ZARAFA_SERVER"=>"Zarafa server",
		"APP_ZARAFA_SPOOLER"=>"Zarafa Spooler",
		"APP_ZARAFA_DAGENT"=>"Zarafa Delivery Agent",
		"APP_ZARAFA_GATEWAY"=>"Zarafa Gateway",			
		"APP_ZARAFA_MONITOR"=>"Zarafa Monitor",	
		"APP_ZARAFA_ICAL"=>"iCal, CalDav Gateway",
		"APP_ZARAFA_LICENSED"=>"Zarafa license service",
		"APP_ZARAFA"=>"Zarafa Mail server",
		"APP_SNORT"=>"Intrusion Detection System (Snort)",
		"APP_OCSI"=>"OCS Inventory",
		"APP_ARTICA_AUTH_TAIL"=>"Authentication Watchdog",
		"APP_VNSTAT"=>"VNStat",
		"APP_POSTFWD2"=>"Postfix Postfwd2",
		"APP_VMTOOLS"=>"VMWare tools",
		"APP_IETD"=>"iSCSI service",
		"APP_GREYHOLE"=>"Greyhole Samba relatime Backup",
		"APP_AUTOFS"=>"AutoFS, Automount service",
		"APP_MUNIN"=>"Munin Statistics Client",
		"APP_BACKUPPC"=>"BackupPC Daemon",
		"APP_STUNNEL"=>"sTunnel daemon",
		
		"APP_UFDBGUARD_TAIL"=>"UfdbGuard Watchdog",
		"APP_OPENVPN"=>"OpenVPN Server",
		"OPENVPN_SERVER"=>"OpenVPN server",
		"APP_VBOXADDITIONS"=>"VirtualBox guest additions",
		
		
		"APP_GLUSTER"=>"Gluster Cluster daemon",
		"APP_APACHE_SRC"=>"Apache source Daemon",
		"APP_CLUEBRINGER"=>"ClueBringer (Policyd V2)",
		"APP_DDCLIENT"=>"Dynamic DNS Client",
		"APP_SQUID_CLAMAV_TAIL"=>"squidclamav RealTime log",
		"APP_ARTICA_SQUID_TAIL"=>"Squid RealTime log",
		"APP_CROSSROADS"=>"Crossroads Load balancing service",
		"APP_TFTPD"=>"Trivial File Transfer Protocol service",
		"APP_POLICYD_WEIGHT"=>"Policy Daemon for Postfix",
		"APP_VIRTUALBOX_WEBSERVICE"=>"VirtualBox Web service",
		"APP_ARTICA_POLICY"=>"Artica-Policy service",
		"APP_MILTER_DKIM"=>"Milter-DKIM service",
		"APP_DROPBOX"=>"DropBox service",
		"APP_ARTICA_SQUIDGUARDTAIL"=>"squidGuard watchdog daemon",
		"APP_UFDBGUARD"=>"UfdbGuard Filtering service",
		"APP_OPENDKIM"=>"OpenDKIM Filter service",
		"APP_SQUIDGUARD_HTTP"=>"SquidGuard HTTP service",
		"APP_DNSMASQ"=>"DNS forwarder caching",
		"APP_SMARTMONTOOLS"=>"SMART hard disk monitoring",
		"APP_AUDITD"=>"Auditd Daemon",
		"APP_OPENSSH"=>"OpenSSH server",
		"APP_KAV4FS"=>"Kaspersky For File server",
		"APP_MLDONKEY"=>"MLDonkey server",
		"APP_DHCP"=>"DHCP Server",
		"APP_ARTICA_NOTIFIER"=>"Artica SMTP Notifier",
		"APP_GROUPWARE_APACHE"=>"Apache for groupware applications",
		"APP_ROUNDCUBE"=>"Web service For RoundCube",
		"APP_ZABBIX_SERVER"=>"Zabbix server",
		"APP_MYSQL_ARTICA"=>"Mysql server",
		"APP_ARTICA_MYSQMAIL"=>"Postfix Monitor",
		"APP_ARTICA_DANSGUARDIAN_TAIL"=>"DansGuardian Monitor",
		"APP_ARTICA_WATCHDOG"=>"Watchdog Daemon",
		"APP_ASSP"=>"Anti-Spam SMTP Proxy",
		"APP_BOA"=>"Boa Daemon",
		"APP_CLAMAV"=>"ClamAV Daemon",
		"APP_CUPS"=>"Printer service",
		"APP_CYRUS"=>"Cyrus-imap service",
		"APP_DANSGUARDIAN"=>"DansGuardian",
		"APP_FETCHMAIL_LOGGER"=>"Fetchmail Monitor",
		"APP_FRAMEWORK"=>"Artica FrameWork",
		"APP_FETCHMAIL"=>"Fetchmail Daemon",
		"APP_MAILMAN"=>"Mailman mailinglist Daemon",
		"APP_FRESHCLAM"=>"ClamAV Updater",
		"APP_KAS3_MILTER"=>"Kaspersky Anti-Spam hook",
		"APP_KAS3"=>"Kaspersky Anti-Spam service",
		"APP_KAVMILTER"=>"Kaspersky Anti-virus For Mail",
		"APP_KRETRANSLATOR_HTTPD"=>"Kaspersky Antivirus updater web service",
		"APP_LDAP"=>"OpenLDAP service",
		"APP_LIGHTTPD"=>"Artica Web server",
		"APP_MONIT"=>"Watchdog system",
		"APP_RSYNC"=>"Rsync service",
		"APP_PDNS_RECURSOR"=>"PowerDNS external query ",
		"APP_PDNS"=>"PowerDNS service",
		"APP_POSTFIX"=>"Postfix",
		"APP_SAMBA_NMBD"=>"Samba NetBIOS name service",
		"APP_SAMBA_WINBIND"=>"Samba Name Service Switch",
		"APP_SAMBA_SMBD"=>"Samba Filesharing and printing services",
		"APP_SASLAUTHD"=>"System Authentication",
		"APP_SPAMASSASSIN"=>"SpamAssassin",
		"APP_SQUID"=>"Proxy service (squid)",
		"APP_SYSLOGER"=>"Artica System monitor",
		"APP_KAV4PROXY"=>"Kaspersky Antivirus For Squid",
		"APP_C_ICAP"=>"C-ICAP For Squid",
		"APP_SCANNED_ONLY"=>"Samba Antivirus Scanner",
		"APP_PUREFTPD"=>"FTP Service",
		"APP_MILTERGREYLIST"=>"Greylist service for Postfix",
		"APP_AMAVISD_MILTER"=>"Amavis listener milter",
		"APP_AMAVISD_NEW"=>"Amavis Daemon",
		"APP_CLAMAV_MILTER"=>"ClamAV For Postfix",
		"APP_MAILSPY"=>"MailSpy",
		"APP_GROUPWARE_APACHE"=>"Apache for Groupwares",
		"APP_ARTICA"=>"Artica service",
		
		
	);
	

	while (list ($tok, $service_name) = each ($arr_APP) ){
		$subject=str_replace($tok,$service_name,$subject);
		$text=str_replace($tok,$service_name,$text);
	}
	include_once("/usr/share/artica-postfix/ressources/class.users.menus.inc");	
	if(!isset($GLOBALS["CLASS_USERSMENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERSMENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERSMENUS"];}
	$internal_load=null;
	
	if($date==null){
		$date=date('Y-m-d H:i:s');
		$datesubject=date('H:i:s');
	}else{
		$datesubject=$date;
	}
	
	if(function_exists("sys_getloadavg")){
		$array_load=sys_getloadavg();
		$internal_load=" - system load: {$array_load[0]}";
	}
	
	$filename=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		$debug_backtrace_array=$this->debug_backtrace_array($trace);
		$function="MAIN";
		$line=0;
		if(isset($debug_backtrace_array["file"])){
			$filename=($debug_backtrace_array["file"]);
		}
		if(isset($debug_backtrace_array["function"])){
			$function=" - function {$debug_backtrace_array["function"]}()";
		} 
		if(isset($debug_backtrace_array["line"])){
			$function=$function." line {$debug_backtrace_array["line"]}";
		}
	}
	
	$ini=new iniFrameWork();
	
	
	if(strlen($recipient)>256){$recipient=null;}
	if($GLOBALS["VERBOSE"]){echo "$subject\n$text\n";}
	$textNocrlf=str_replace("\n", "<br>", $text);
	$ini->set('LOG','processname',$filename);
	$ini->set('LOG','date',$date);
	$ini->set('LOG','context',$context);
	$ini->set('LOG','subject',"[$datesubject]: [ARTICA]: ($users->hostname) : $subject$internal_load");
	$ini->set('LOG','recipient',$recipient);
	$ini->set('LOG','text',$textNocrlf.$internal_load.$function);
	$filename="{$GLOBALS["ARTICALOGDIR"]}/events/".md5(serialize($ini->_params));
	if(is_file($filename)){return null;}
	$ini->saveFile($filename);
	$size=@filesize($filename);
	if($size>5242880){@unlink($filename);}
	$datas=@file_get_contents($filename);
	$datas=$datas."\n<text>$text</text>\n";
	$datas=$datas."\n<attachedfiles>". base64_encode(serialize($attached_files))."</attachedfiles>\n";
	
	
	if($GLOBALS["VERBOSE"]){echo strlen($datas)." bytes file $filename text\n";}
	
	@file_put_contents($filename,$datas);
	}
	
	private function debug_backtrace_array($trace){
		
		if(isset($trace[0])){
			if(isset($trace[0]["file"])){
				$filename=basename($trace[0]["file"]);
			}
			if(isset($trace[0]["function"])){
				$function=$trace[0]["function"];
			}
			if(isset($trace[0]["line"])){
				$line=$trace[0]["line"];
			}
		
			return array("function"=>$function,"file"=>$filename,"line"=>$line);
		
		}
		
		if(isset($trace[1])){
			if(isset($trace[1]["file"])){
				$filename=basename($trace[1]["file"]);
			}
			if(isset($trace[1]["function"])){
				$function=$trace[1]["function"];
			}
			if(isset($trace[1]["line"])){
				$line=$trace[1]["line"];
			}
			
			return array("function"=>$function,"file"=>$filename,"line"=>$line);
			
		}
		
	

		return array();
		
	}

private function time_duration($seconds, $use = null, $zeros = false){
    // Define time periods
    $periods = array (
        'years'     => 31556926,
        'Months'    => 2629743,
        'weeks'     => 604800,
        'days'      => 86400,
        'hours'     => 3600,
        'minutes'   => 60,
        'seconds'   => 1
        );

    // Break into periods
    $seconds = (float) $seconds;
    $segments = array();
    foreach ($periods as $period => $value) {
        if ($use && strpos($use, $period[0]) === false) {
            continue;
        }
        $count = floor($seconds / $value);
        if ($count == 0 && !$zeros) {
            continue;
        }
        $segments[strtolower($period)] = $count;
        $seconds = $seconds % $value;
    }

    // Build the string
    $string = array();
    foreach ($segments as $key => $value) {
        $segment_name = substr($key, 0, -1);
        $segment = $value . ' ' . $segment_name;
        if ($value != 1) {
            $segment .= 's';
        }
        $string[] = $segment;
    }

    return implode(', ', $string);
}

public function GetVersionOf($name){
	if(!isset($GLOBALS["version-of-$name"])){$GLOBALS["version-of-$name"]=null;}
	if($GLOBALS["version-of-$name"]<>null){return $GLOBALS["version-of-$name"];}
	exec("/usr/share/artica-postfix/bin/artica-install --export-version $name",$results);
	$version=trim(implode("",$results));
	$version=trim(implode("",$results));
	$GLOBALS["version-of-$name"]=$version;
	return $version;	
}

  function unix_memory_usage() {
        $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            echo $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            return round($mem_usage/1024,2)." kilobytes";
        else
            return round($mem_usage/1048576,2)." megabytes";
           
        
    } 

	
	public function DANSGUARDIAN_CATEGORIES(){
			$path="/etc/dansguardian/lists/blacklists";
			$unix=new unix();
			$dirs=$unix->dirdir($path);
			while (list ($num, $line) = each ($dirs)){
				
				
			}
		}
		
		
public function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		//$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);				
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		$path=str_replace("|","\|",$path);
		$path=str_replace("%","\%",$path);
		return $path;	
}

	function SLAPD_CONF_PATH(){
		if(is_file('/etc/ldap/slapd.conf')){return '/etc/ldap/slapd.conf';}
		if(is_file('/etc/openldap/slapd.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/etc/openldap/ldap.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/opt/artica/etc/openldap/slapd.conf')){return '/opt/artica/etc/openldap/slapd.conf';}
		if(is_file('/usr/local/etc/openldap/slapd.conf')){return '/usr/local/etc/openldap/slapd.conf';}
	   return '/etc/ldap/slapd.conf';
	}
	function SLAPD_PID_PATH(){
	  if(is_file('/var/run/slapd/slapd.pid')){return '/var/run/slapd/slapd.pid';}
	  if(is_file('/var/run/openldap/slapd.pid')){ return '/var/run/openldap/slapd.pid';}
	
	  $conffile=$this->SLAPD_CONF_PATH();
	  if(!is_file($conffile)){return "/var/run/slapd/slapd.pid";}
	  $f=explode("\n",@file_get_contents($conffile));
	  while (list ($num, $line) = each ($f)){
	 	 if(preg_match('#pidfile\s+(.+)#',$line,$re)){return trim($re[1]);}
	  }
	  
		 return('/var/run/slapd/slapd.pid');
	}		
		
	public function APACHE_GROUPWARE_ACCOUNT(){
		$tbl=explode("\n",@file_get_contents("/usr/local/apache-groupware/conf/apache-groupware.conf"));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#User\s+(.+)#",$line,$re)){
				return "{$re[1]}:{$re[1]}";
			}
			
		}
		
	}
	
	public function IS_IPADDR_EXISTS($ipaddr){
		$ipaddr=trim($ipaddr);
		if($ipaddr==null){return false;}
		if(!isset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"])){
			$ifconfig=$this->find_program("ifconfig");
			exec("$ifconfig -a 2>&1",$GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
		}
		$ipaddrregex=str_replace(".", "\.", $ipaddr);
		reset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
		while (list ($num, $line) = each ($GLOBALS["IS_IPADDR_EXISTS_ARRAY"])){
			if(preg_match("#$ipaddrregex#",$line)){return true;}
		}
		return false;
		
	}
	
	

	
	public function APACHE_SRC_ACCOUNT(){
		if(isset($GLOBALS["APACHE_SRC_ACCOUNT"])){return $GLOBALS["APACHE_SRC_ACCOUNT"];}
		$GLOBALS["APACHE_SRC_ACCOUNT"]=$this->_APACHE_SRC_ACCOUNT();
		if($GLOBALS["APACHE_SRC_ACCOUNT"]=='${APACHE_RUN_USER}'){$GLOBALS["APACHE_SRC_ACCOUNT"]="www-data";}
		return $GLOBALS["APACHE_SRC_ACCOUNT"];
	}
	
	private function _APACHE_SRC_ACCOUNT(){
		if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCAccount")){
			$data=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApacheSRCAccount"));
			if(strlen($data)>3){return $data;}
		}
		
		
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		$envars=dirname($httpd_conf)."/envvars";
		if(is_file($envars)){
			$tbl=explode("\n",@file_get_contents($envars));
			while (list ($num, $line) = each ($tbl)){
				if(preg_match("#export APACHE_RUN_USER=(.+)#",$line,$re)){
					return trim($re[1]);
				}
			}
		}
		if(is_file($httpd_conf)){
			$tbl=explode("\n",@file_get_contents($httpd_conf));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^User\s+(.+)#",$line,$re)){
				if(strpos($re[1], '"')==0){return trim("{$re[1]}");}
				}
			}
		}
		
		if(is_file("/etc/apache2/uid.conf")){
			$tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^User\s+(.+)#",$line,$re)){return trim("{$re[1]}");}}	
		}
		
		$tbl=explode("\n",@file_get_contents("/etc/passwd"));
		while (list ($num, $line) = each ($tbl)){if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){return trim("{$re[1]}");}}
		
		
		
	}	
	
	public function APACHE_SRC_GROUP(){
		if(isset($GLOBALS["APACHE_SRC_GROUP"])){
			if(preg_match("#APACHE_RUN_GROUP#", $GLOBALS["APACHE_SRC_GROUP"])){$GLOBALS["APACHE_SRC_GROUP"]="www-data";}
			return $GLOBALS["APACHE_SRC_GROUP"];
		}
		$GLOBALS["APACHE_SRC_GROUP"]=$this->_APACHE_SRC_GROUP();
		if(preg_match("#APACHE_RUN_GROUP#", $GLOBALS["APACHE_SRC_GROUP"])){$GLOBALS["APACHE_SRC_GROUP"]="www-data";}
		return $GLOBALS["APACHE_SRC_GROUP"];
	}
	
	
	private function _APACHE_SRC_GROUP(){
		if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCGroup")){
			$data=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApacheSRCGroup"));
			if(strlen($data)>3){
				if($data=='${APACHE_RUN_GROUP}'){$data="www-data";}
				if($data=='${APACHE_GROUP}'){$data="www-data";}
				$data=str_replace('${APACHE_RUN_GROUP}','www-data',$data);
				$data=str_replace('${APACHE_GROUP}','www-data',$data);
				if(preg_match("#APACHE_RUN_GROUP#", $data)){$data="www-data";}
				return $data;
			}
			return "www-data";
		}		
		
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		if(is_file($httpd_conf)){
			$tbl=explode("\n",@file_get_contents($httpd_conf));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^Group\s+(.+)#",$line,$re)){
				if(strpos($re[1], '"')==0){return trim("{$re[1]}");}
				
			}
			}
		}
		
		if(is_file("/etc/apache2/uid.conf")){
			$tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
			while (list ($num, $line) = each ($tbl)){
				if(preg_match("#^Group\s+(.+)#",$line,$re)){
					$data=trim("{$re[1]}");
					if($data=='${APACHE_RUN_GROUP}'){$data="www-data";}
					return $data;
				}
			}	
		}
		
		$tbl=explode("\n",@file_get_contents("/etc/passwd"));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){
				$data=trim("{$re[1]}");
				if($data=='${APACHE_RUN_GROUP}'){$data="www-data";}
				return $data;
			}
		}
				

		return $this->_APACHE_SRC_ACCOUNT();
		
	}
	
	function GetAvailableListenIP(){
		if(isset($GLOBALS["GetAvailableListenIP"])){return $GLOBALS["GetAvailableListenIP"];}
		$unix=new unix();
		$ifconfig=$unix->find_program("ifconfig");
		exec("$ifconfig -a 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#inet addr.*?([0-9\.]+)#", $line,$re)){
					
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." available network: {$re[1]}\n";}
				$GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
				continue;
			}
			if(preg_match("#inet6 addr:\s+(.*?)\/#", $line,$re)){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." available network: {$re[1]}\n";}
				$GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
				continue;
			}
	
			if($GLOBALS["VERBOSE"]){echo "GetAvailableListenIP() no match `$line`\n";}
		}
		if(count($GLOBALS["GetAvailableListenIP"])==0){echo "Starting......: ".date("H:i:s")." No IP ????\n";}
		return $GLOBALS["GetAvailableListenIP"];
	
	}

	public function SQUID_GET_LISTEN_PORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#http_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
		
	}
	
	public function SQUID_GET_LISTEN_SSL_PORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#https_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#https_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	
	}	
	
	public function APACHE_GET_LISTEN_PORT(){
		$f=explode("\n",@file_get_contents("/etc/apache2/ports.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#Listen\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#Listen\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	}
	
	public function SQUID_GET_LISTEN_SSLPORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#https_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#https_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	
	}

	public function SQUID_ACTIVE_REQUESTS(){
		$manager=new cache_manager();
		return $manager->active_requests();
	
	}
	
	
	public function IsSquidReverse(){
		if(isset($GLOBALS["SQUID_IsSquidReverse"])){return $GLOBALS["SQUID_IsSquidReverse"];}
		if(is_file("/etc/artica-postfix/SQUID_REVERSE_APPLIANCE")){
			$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
		
		
		
		$squid=$this->LOCATE_SQUID_BIN();
		if(!is_file($squid)){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
		$SquidActHasReverse=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidActHasReverse"));
		if(!is_numeric($SquidActHasReverse)){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
		if($SquidActHasReverse==1){$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
	}
	
	public function isNGnx(){
		if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
		$nginx=$this->find_program("nginx");
	
		if(!is_file($nginx)){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
		$sock=new sockets();
		$EnableNginx=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			$GLOBALS["isNGnx"]=true;
			return $GLOBALS["isNGnx"];
		}
			
		$GLOBALS["isNGnx"]=false;
		return $GLOBALS["isNGnx"];
	
	}	
	
	
	private function debug($text){
		
		if(!$GLOBALS["VERBOSE"]){return;}
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			$debug_backtrace=$this->debug_backtrace_array($trace);
			$function="{$debug_backtrace["function"]}()";
			$line=$debug_backtrace["line"];
		}
		
		
		echo __CLASS__."/$function [$line]: $text\n";
	}
	
	
	function dskspace_bytes($dir_name,$nocache=false){
		$EXEC_NICE=$this->EXEC_NICE();
		$du=$this->find_program("du");
		if(is_link($dir_name)){$dir_name=readlink($dir_name);}
		if(!is_dir($dir_name)){return 0;}
		if(!$nocache){
			$mdkey=md5($dir_name);
			@mkdir("/etc/artica-postfix/DIRSIZE_MB_CACHE",0755,true);
			$cache_file="/etc/artica-postfix/DIRSIZE_MB_CACHE/$mdkey.bytes";
			$Time=$this->file_time_min($cache_file);
			if($Time<60){return trim(@file_get_contents("$cache_file"));}
			@unlink($cache_file);
		}
		
		if(!function_exists("system_is_overloaded")){
			include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
		}
		if(function_exists("system_is_overloaded")){
			if(system_is_overloaded()){return 0;}
		}
		
		
		$dir_name_regex=str_replace("/", "\/", $dir_name);
		$dir_name_regex=str_replace(".", "\.", $dir_name_regex);
		$pid=$this->PIDOF_PATTERN("$du.*?$dir_name_regex");
		if($this->process_exists($pid)){return 0;}
		
		if(is_file($du)){
			exec("$EXEC_NICE$du -bs $dir_name 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){
				$ligne=trim($ligne);
				if($ligne==null){continue;}
				if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
					@file_put_contents($cache_file, $re[1]);
					return $re[1];
				}
		
		
			}
			return;
		}		
		
		
       $dir_size =0;
       if (!is_dir($dir_name)) {return;}
       if ($dh = opendir($dir_name)) {
        	while (($file = readdir($dh)) !== false) {
            	if(is_link($dir_name."/".$file)){continue;}
                if($file !="." && $file != ".."){
                	if(is_file($dir_name."/".$file)){
                    	$dir_size += filesize($dir_name."/".$file);
                       }
                             /* check for any new directory inside this directory */
                             if(is_dir($dir_name."/".$file)){
                                $dir_size +=  $this->dskspace_bytes($dir_name."/".$file);
                              }
                           }
                     }
             }
       
	closedir($dh);
	return $dir_size;
	}
	
	
	
	public function is_interface_available($INTERFACE){
		
		
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$INTERFACE)){
	if($this->NETWORK_IS_LISTEN_ADDR_EXISTS($INTERFACE)){return true;}
	return false;}
		
		
		
		if(!isset($GLOBALS["INTERFACES"])){$GLOBALS["INTERFACES"]=$this->NETWORK_ALL_INTERFACES();}
		if(!isset($GLOBALS["INTERFACES"][$INTERFACE]["IPADDR"])){return false;}
		$STATE=$GLOBALS["INTERFACES"][$INTERFACE]["STATE"];
		if($STATE=="DOWN"){return false;}
		return true;
		
		
	}
	
	public function NETWORK_IS_LISTEN_ADDR_EXISTS($ipaddr){
		$NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES(true);
		if(isset($NETWORK_ALL_INTERFACES[$ipaddr])){return true;}
		return false;
	}
	
	public function NETWORK_DEFAULT_LISTEN_ADDR(){
		return $this->NETWORK_DEFAULT_IP_ADDR();
	}
	
	
	private function ifconfig_interface($Interface,$array){
		$ip=$this->find_program("ifconfig");
		exec("$ip $Interface 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(preg_match("#^inet\s+[A-za-z]+:([0-9\.]+)\s+[A-za-z]+:([0-9\.]+)\s+[A-za-z]+:([0-9\.]+)#",$ligne,$re)){
				$array[$Interface]["NETMASK"]=$re[3];
				$array[$Interface]["BROADCAST"]=$re[2];
			}
		}
		return $array;
	}
	
	public function NETWORK_ALL_INTERFACES($OnlyIPS=false){
		if(isset($GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"])){return $GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"];}
		include_once(dirname(__FILE__)."/class.tcpip-parser.inc");
		$ip=$this->find_program("ip");
		
		if(!is_file($ip)){
			$this->debug("IP no such binary");
			return array();}
		exec("$ip addr show 2>&1",$results);
		$this->debug("$ip addr show");
		
		while (list ($num, $ligne) = each ($results) ){
			if(trim($ligne)==null){continue;}
			
			$PROMISC=false;
			
			if(preg_match("#[0-9]+:\s+(.+?):\s+<#", $ligne,$re)){
				$Interface=$re[1];
				if(preg_match("#^(.+?)@#", $Interface,$ri)){$Interface=$ri[1];}
				
				$this->debug("****** Found Interface:$Interface *******");
				$array[$Interface]["INTERFACE"]=$Interface;
				$array[$Interface]["PROMISC"]=false;
				if(preg_match("#PROMISC#", $ligne)){
					$this->debug("****** Found Interface:$Interface -> PROMISC *******");
					$array[$Interface]["PROMISC"]=true;
				}
				
				
				if(preg_match("#POINTOPOINT#", $ligne)){$array[$Interface]["MAC"]="00:00:00:00:00:00";}
				if(preg_match("#state\s+([A-Z]+)#", $ligne,$re)){$array[$Interface]["STATE"]=$re[1];}
				$array=$this->ifconfig_interface($Interface, $array);
				continue;
			}
			
			
			if(preg_match("#inet\s+([0-9\.]+)\s+peer\s+([0-9\.]+)\/([0-9]+)\s+scope global\s+([a-z0-9]+)#", $ligne,$re)){
				if(preg_match("#^(.+?)@#", $re[4],$ri)){$re[4]=$ri[1];}
				$Interface="{$re[4]}";
				$IPTABLE[$re[1]]=true;
				$this->debug("****** Found Interface:$Interface *******");
				$array[$Interface]["INTERFACE"]="{$re[4]}";
				$array[$Interface]["IPADDR"]=$re[1];
				$array[$Interface]["PEER"]=$re[2];
				$array[$Interface]["SCOPE"]=$re[3];
				$tcp=new Unixipv4($re[1], $re[3]);
				$parseCIDR=$tcp->netmask();
				$array[$Interface]["NETMASK"]=$parseCIDR;
				$array=$this->ifconfig_interface($Interface, $array);
				if(isset($array[$re[4]]["MAC"])){$array[$Interface]["MAC"]=$array[$re[4]]["MAC"];}
				continue;
			
			}			
			
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+brd\s+([0-9\.]+)\s+scope global\s+([a-z0-9]+):([0-9]+)#", $ligne,$re)){
				if(preg_match("#^(.+?)@#", $re[4],$ri)){$re[4]=$ri[1];}
				if(!is_numeric($re[5])){continue;}
				$Interface="{$re[4]}:{$re[5]}";
				$IPTABLE[$re[1]]=true;
				$this->debug("****** Found Interface:$Interface *******");
				$array[$Interface]["INTERFACE"]="{$re[4]}:{$re[5]}";
				$array[$Interface]["IPADDR"]=$re[1];
				$array[$Interface]["CDIR"]=$re[1]."/".$re[2];
				$array[$Interface]["SCOPE"]=$re[3];
				$tcp=new Unixipv4($re[1], $re[2]);
				$parseCIDR=$tcp->netmask();
				$array[$Interface]["NETMASK"]=$parseCIDR;
				$array=$this->ifconfig_interface($Interface, $array);
				if(isset($array[$re[4]]["MAC"])){$array[$Interface]["MAC"]=$array[$re[4]]["MAC"];}
				continue;
				
			}
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+brd\s+([0-9\.]+)\s+scope global\s+secondary\s+([a-z0-9]+):([0-9]+)#", $ligne,$re)){
				if(preg_match("#^(.+?)@#", $re[4],$ri)){$re[4]=$ri[1];}
				if(!is_numeric($re[5])){continue;}
				$Interface="{$re[4]}:{$re[5]}";
				$IPTABLE[$re[1]]=true;
				$this->debug("****** Found Interface:$Interface *******");
				$array[$Interface]["INTERFACE"]="{$re[4]}:{$re[5]}";
				$array[$Interface]["IPADDR"]=$re[1];
				$array[$Interface]["CDIR"]=$re[1]."/".$re[2];
				$array[$Interface]["SCOPE"]=$re[3];
				$tcp=new Unixipv4($re[1], $re[2]);
				$parseCIDR=$tcp->netmask();
				$array[$Interface]["NETMASK"]=$parseCIDR;
				$array=$this->ifconfig_interface($Interface, $array);
				if(isset($array[$re[4]]["MAC"])){$array[$Interface]["MAC"]=$array[$re[4]]["MAC"];}
				continue;
				
			}
					
			
	
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+brd\s+([0-9\.]+)\s+#", $ligne,$re)){
				$IPTABLE[$re[1]]=true;
				if(!isset($array[$Interface]["IPADDR"])){ $array[$Interface]["IPADDR"]=$re[1]; }
				if(!isset($array[$Interface]["CDIR"])){ $array[$Interface]["CDIR"]=$re[1]."/".$re[2];}
				if(!isset($array[$Interface]["SCOPE"])){ $array[$Interface]["SCOPE"]=$re[3];}
				if(!isset($array[$Interface]["NETMASK"])){
					$tcp=new Unixipv4($re[1], $re[2]);
					$parseCIDR=$tcp->netmask();
					$array[$Interface]["NETMASK"]=$parseCIDR;
				}
				continue;
			}
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+scope\s+global\s+#", $ligne,$re)){
				$IPTABLE[$re[1]]=true;
				if(!isset($array[$Interface]["IPADDR"])){ $array[$Interface]["IPADDR"]=$re[1];}
				if(!isset($array[$Interface]["CDIR"])){ $array[$Interface]["CDIR"]=$re[1]."/".$re[2];}
				if(!isset($array[$Interface]["SCOPE"])){ $array[$Interface]["SCOPE"]="0.0.0.0/0";}
				if(!isset($array[$Interface]["NETMASK"])){ 
					$tcp=new Unixipv4($re[1], $re[2]);
					$parseCIDR=$tcp->netmask();
					$array[$Interface]["NETMASK"]=$parseCIDR;
				}
				continue;
			}
			
			
			
			if(preg_match("#link\/ether\s+([0-9a-z:]+)\s+#", $ligne,$re)){
				
				if(!isset($array[$Interface]["MAC"])){$array[$Interface]["MAC"]=$re[1];}
				continue;
			}
		
			if(preg_match("#inet6\s+(.+)\/([0-9]+)\s+#", $ligne,$re)){
				$array[$Interface]["v6"]=$re[1];
				$array[$Interface]["v6CDIR"]=$re[1]."/".$re[2];
				continue;
			}
			
			if(preg_match("#state UP#", $ligne,$re)){
				if(!isset($array[$Interface]["STATE"])){$array[$Interface]["STATE"]="yes";}
			}
			if(preg_match("#state DOWN#", $ligne,$re)){
				if(!isset($array[$Interface]["STATE"])){$array[$Interface]["STATE"]="no";}
			}	

			
			$this->debug("No match `$ligne`");
		
		}
		
		if($OnlyIPS){return $IPTABLE;}
		$results=array();
		exec("$ip route list 2>&1",$results);
		
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#default via\s+([0-9\.]+)\s+dev\s+([0-9A-Za-z\-\_]+)#", $ligne,$re)){
				$Interface=$re[2];
				if(preg_match("#^(.+?)@#", $Interface,$ri)){$Interface=$ri[1];}
				if($Interface==null){continue;}
				$array[$Interface]["GATEWAY"]=$re[1];
					
			}
		}
		
		$GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"]=$array;
		return $array;
		
	}
	
	
	function NETWORK_INTERFACE_OK($eth){
		$ip=$this->find_program("ip");
		exec("$ip link show $eth 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#^[0-9]+.*?state\s+([A-Z]+)#", $ligne,$re)){continue;}
			$state=$re[1];
			if($state=="DOWN"){return false;}
		}
		return true;
	}
	
	function NMAP_CMDLINE($net,$tmpfile){
		$nmap=$this->find_program("nmap");
		return "$nmap $net -oG $tmpfile -F -R -O -A --system-dns -p1 --min-hostgroup 30 --min-parallelism 30 --osscan-guess --host-timeout 5000";
	}
	
	
	function NETWORK_DEBIAN_FILCONF($eth){
		$f=explode("\n",@file_get_contents('/etc/network/interfaces'));
		$ttr["address"]="IPADDR";
		$ttr["netmask"]="NETMASK";
		$ttr["gateway"]="GATEWAY";
		$ttr["broadcast"]="BROADCAST";
		$ttr["network"]="NETWORK";
		$ttr["metric"]="METRIC";
		
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#iface\s+(.+?)\s+.*?(static|dhcp)#", $ligne,$re)){
				$interface=$re[1];
				$outputz[$interface]["BOOTPROTO"]=$re[2];
				$outputz[$interface]="METHOD=debian";
				$outputz[$interface]="DEVICE=$interface";
				continue;
			}
		
			if(preg_match("#(.+?)\s+(.+)#", $ligne,$re)){
				$key=strtolower(trim($re[1]));
				if(isset($ttr[$key])){
					$outputz[$interface][$ttr[$key]]=trim($re[2]);
					continue;
				}
					
				$outputz[$interface][$key]=$re[2];
		
			}
		
		}
		
		while (list ($num, $ligne) = each ($outputz[$eth]) ){
			$return[]="$num=$ligne";
		}
		return @implode("\n",$return);
	}
	
	
	function APACHE_ListenDefaultAddress(){
		if(isset($GLOBALS["APACHE_ListenDefaultAddress"])){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		if($this->isNGnx()){$GLOBALS["APACHE_ListenDefaultAddress"]="127.0.0.1";return "127.0.0.1";}
		if($this->IsSquidReverse()){$GLOBALS["APACHE_ListenDefaultAddress"]="127.0.0.1";return "127.0.0.1";}
		
		$GLOBALS["APACHE_ListenDefaultAddress"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/FreeWebListen"));
		if($GLOBALS["APACHE_ListenDefaultAddress"]==null){$GLOBALS["APACHE_ListenDefaultAddress"]="*";}
		if($GLOBALS["APACHE_ListenDefaultAddress"]<>"*"){
			if(is_numeric($GLOBALS["APACHE_ListenDefaultAddress"])){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` is not a valid network address...\n";}
				$GLOBALS["APACHE_ListenDefaultAddress"]="*";
			}
		}
	
		if($GLOBALS["APACHE_ListenDefaultAddress"]=="0.0.0.0"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		if($GLOBALS["APACHE_ListenDefaultAddress"]=="*"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		$GetAvailableListenIP=$this->GetAvailableListenIP();
		if(!isset($GetAvailableListenIP[$GLOBALS["APACHE_ListenDefaultAddress"]])){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` does not exists...\n";}
			$GLOBALS["APACHE_ListenDefaultAddress"]="*";
			return $GLOBALS["APACHE_ListenDefaultAddress"];
		}
			return $GLOBALS["APACHE_ListenDefaultAddress"];
	}

	public function APACHE_BIN_PATH(){
		$bin=$this->find_program("apache2");
		if(is_file($bin)){return $bin;}
		$bin=$this->find_program("apache");
		if(is_file($bin)){return $bin;}
		$bin=$this->find_program("httpd");
		if(is_file($bin)){return $bin;}
	}
	
	
	public function APACHE_PID_PATH(){
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		if(!is_file($httpd_conf)){return null;}
		$f=file($httpd_conf);
		$PidFile=null;
		while (list ($num, $line) = each ($f)){
			if(preg_match("#^PidFile\s+(.+)#", $line,$re)){
				$PidFile=trim($re[1]);
				break;
			}
		}
		
		if($PidFile==null){
			$PidFile=$this->APACHE_DEFAULT_PIDLOG();
		}
		
		
		if($PidFile=='${APACHE_PID_FILE}'){
			if(is_file("/etc/apache2/envvars")){
				$f=explode("\n",@file_get_contents("/etc/apache2/envvars"));
				while (list ($num, $line) = each ($f)){
					if(preg_match("#^export APACHE_PID_FILE=(.+)#",$line,$re)){
						$PidFile=trim($re[1]);
						$PidFile=str_replace('$SUFFIX','',$PidFile);
						break;
					}
				}
			}
		}
		if($PidFile=="run/httpd.pid"){$PidFile="/var/run/httpd.pid";}
		return $PidFile;
	}
function GET_PERFS($value){
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
	}else{
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
	}
	
	if(isset($ini->_params["PERFORMANCES"][$value])){
		return $ini->_params["PERFORMANCES"][$value];
	}
	
}
	
public function EXEC_NICE(){
	include_once(dirname(__FILE__)."/class.nice.inc");
	$nice=new exec_nice();
	return $nice->nice;
}	

public function MYSQL_VERSION(){
	if(isset($GLOBALS["SQUID_DB_VERSION"])){return $GLOBALS["SQUID_DB_VERSION"];}
	$mysqld=$this->find_program("mysqld");

	exec("$mysqld --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["SQUID_DB_VERSION"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}
		
		
	public function LIGHTTPD_PORT($conf=null){
		if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
		$tbl=explode("\n",@file_get_contents($conf));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#^server\.port.*?=.*?([0-9]+)#",$line,$re)){
				return $re[1];
			}
		}
		
	}
	
	public function LIGHTTPD_USER($conf=null){
		if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
		$tbl=explode("\n",@file_get_contents($conf));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#^server\.username.*?=(.+?)$#",$line,$re)){
				$re[1]=str_replace("\"","",$re[1]);
				return trim($re[1]);
			}
		}
		
	}
	
	private function LIGHTTPD_CONF_PATH(){
		if(is_file('/etc/lighttpd/lighttpd.conf')){return '/etc/lighttpd/lighttpd.conf';}
		if(is_file('/usr/local/etc/lighttpd/lighttpd.conf')){return '/usr/local/etc/lighttpd/lighttpd.conf';}
		
	}
	
	public function MEMORY_OF($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		while (list ($index, $line) = each ($datas)){
		 if(preg_match("#^VmSize.+?([0-9]+)#",$line,$re)){
		 	return $re[1];
		 }
		}
		
	}
	
	public function PPID_OF($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		while (list ($index, $line) = each ($datas)){
		 if(preg_match("#^PPid.+?([0-9]+)#i",$line,$re)){
		 	if($re[1]<3){return $pid;}
		 	return $re[1];
		 }
		}
		
		return $pid;
		
	}

	public function GET_WIRELESS_CARD(){
		
		$t=explode("\n",@file_get_contents("/proc/net/wireless"));
		while (list ($index, $line) = each ($t)){
			if(preg_match("#(.+?):\s+([0-9]+)#",$line,$re)){return $re[1];}
		}
		
		$iwconfig=$this->find_program("iwconfig");
		if($iwconfig==null){
			if($GLOBALS["VERBOSE"]){echo "Unable to find iwconfig...\n";}
			return;
		}
		
		exec("$iwconfig 2>&1",$results);
		
		while (list ($index, $line) = each ($results)){
			if(preg_match("#^(.+?)\s+.+?ESSID#",$line,$re)){
				return $re[1];
			}else{
				if($GLOBALS["VERBOSE"]){echo "$line NO MATCH...\n";}
			}
		}
		
	}
	
	
	private function GET_INFO($key){
		if(!is_file("/etc/artica-postfix/settings/Daemons/$key")){return null;}
		$value=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
		return $value;
	
	}
	
	public function EnableClamavDaemon(){
		
		$bin_path=$this->find_program("clamd");
		if(!is_file($bin_path)){return 0;}
		$EnableClamavDaemon=intval($this->GET_INFO("EnableClamavDaemon"));
		$EnableClamavDaemonForced=intval($this->GET_INFO("EnableClamavDaemonForced"));
		if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
		
		$squidbin=$this->LOCATE_SQUID_BIN();
		
		if(is_file($squidbin)){
			$SQUIDEnable=$this->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($SQUIDEnable==1){
				$CicapEnabled=intval($this->GET_INFO("CicapEnabled"));
				if($CicapEnabled==1){
					$EnableClamavInCiCap=$this->GET_INFO("CicapEnabled");
					if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}
					if($EnableClamavInCiCap==1){$EnableClamavDaemon=1;}
				}
			}
		}
		
		$mimedefang=$this->find_program("mimedefang");
		if(is_file($mimedefang)){
			$MimeDefangEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangEnabled"));
			if($MimeDefangEnabled==1){
				$MimeDefangClamav=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangClamav"));
				if($MimeDefangClamav==1){return 1;}
			}
			
		}
		
		
	
		return $EnableClamavDaemon;
	}	
	
	
	
	public function LOCATE_NET_BIN_PATH(){
		$net=$this->find_program("net");
		if(is_file($net)){return $net;}
		$net=$this->find_program("net.samba3");
		if(is_file($net)){return $net;}
	}
	
	public function FULL_HOSTNAME(){
	if(isset($GLOBALS["FULL_HOSTNAME"])){return $GLOBALS["FULL_HOSTNAME"];}
	if(is_file("/etc/artica-postfix/FULL_HOSTNAME")){
		if($this->file_time_min("/etc/artica-postfix/FULL_HOSTNAME")<10){
			$GLOBALS["FULL_HOSTNAME"]=@file_get_contents("/etc/artica-postfix/FULL_HOSTNAME");
			return $GLOBALS["FULL_HOSTNAME"];
		}
	}
	
	if(is_file("/etc/hostname")){
		$GLOBALS["FULL_HOSTNAME"]=trim(@file_get_contents("/etc/hostname"));
		if($GLOBALS["FULL_HOSTNAME"]<>null){return $GLOBALS["FULL_HOSTNAME"];}
	}
	
	$ypdomainname=$this->find_program("ypdomainname");
	$hostname=$this->find_program("hostname");
	$sysctl=$this->find_program("sysctl");
	if($ypdomainname<>null){
		exec("$ypdomainname 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		
	}else{
		exec("$sysctl -n kernel.domainname 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		
	}
	unset($results);
	exec("$hostname -s 2>&1",$results);
	$host=trim(@implode(" ",$results));
	writelogs_framework("$hostname -s -> $host",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	unset($results);
	
	if(preg_match("#not set#",$domain)){$domain=null;}
	if(preg_match("#\(none#",$domain)){$domain=null;}
	if($domain==null){
		
		exec("$hostname -d 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		if(strpos($domain, "not known")){$domain=null;}
		
		writelogs_framework("$hostname -d -> $domain",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($domain<>null){
			if($ypdomainname<>null){shell_exec("$ypdomainname $domain");}
		}
	}
	
	if(strlen($domain)>0){$host="$host.$domain";}
	writelogs_framework("Final = $host ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$host=str_replace('.(none)',"",$host);
	if(preg_match("#(.+?)\.hostname:#", $host,$re)){$host=$re[1];}	
	$GLOBALS["FULL_HOSTNAME"]=$host;
	@unlink("/etc/artica-postfix/FULL_HOSTNAME");
	@file_put_contents("/etc/artica-postfix/FULL_HOSTNAME", $GLOBALS["FULL_HOSTNAME"]);
	return $GLOBALS["FULL_HOSTNAME"];	
	}
	
	
	public FUNCTION GET_LOCAL_SID(){
		if(!isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=null;}
		if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		if(!isset($_SESSION[__CLASS__])){$_SESSION[__CLASS__][__FUNCTION__]=null;}
		
		if($_SESSION[__CLASS__][__FUNCTION__]<>null){return $_SESSION[__CLASS__][__FUNCTION__];}
		if(is_file("/etc/artica-postfix/LOCAL_SID")){
			$filtime=$this->file_time_min("/etc/artica-postfix/LOCAL_SID");
			if($filtime<520){return @file_get_contents("/etc/artica-postfix/LOCAL_SID");}
		}		
		$net=$this->LOCATE_NET_BIN_PATH();
		if(strlen($net)<3){return null;}
		
		exec("$net getlocalsid 2>&1",$results);
		while (list ($index, $line) = each ($results)){
			if(preg_match("#is:\s+(.+)#",$line,$re)){
				$GLOBALS[__FUNCTION__]=trim($re[1]);
				$_SESSION[__CLASS__][__FUNCTION__]=trim($re[1]);
				@file_put_contents("/etc/artica-postfix/LOCAL_SID",$re[1]);
				writelogs_framework("SID:{$re[1]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return trim($re[1]);
			}
			
			if(preg_match("#Can.+?t fetch domain SID for#",$line,$re)){
				$date=time();
				$newsid='S-1-5-21-2596694876-1976058220-'.$date; 
				exec("$net setlocalsid $newsid",$rr);
				while (list ($a, $b) = each ($rr)){writelogs_framework($b,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$GLOBALS[__FUNCTION__]=$newsid;
				$_SESSION[__CLASS__][__FUNCTION__]=trim($newsid);
				writelogs_framework("SID:$newsid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@file_put_contents("/etc/artica-postfix/LOCAL_SID",$newsid);
				return trim($newsid);
			}
		}
	}
	
	
	public function COUNT_LINES_OF_FILE($path){
		$wc=$this->find_program("wc");
		if($wc==null){
			$array=explode("\n",@file_get_contents("$path"));
			return count($array);
			
		}
		
		exec("$wc -l $path 2>&1",$results);
		$line=@implode(" ",$results);
		if(preg_match("#^([0-9]+)\s+#",$line,$re)){
			return $re[1];
		}
		
	}
	
	private function NETWORK_ADD_ROUTE_REDHAT($nic,$array){
		return;
		$count=0;
	if(is_array($array)){
	   	 while (list ($ip, $ip_array) = each ($array) ){
	   	 	$f[]="GATEWAY$count={$ip_array["GATEWAY"]}";
	   	 	$f[]="NETMASK$count={$ip_array["NETMASK"]}";
	   	 	$f[]="ADDRESS$count=$ip";
	   	 	$suse[]="$ip {$ip_array["GATEWAY"]} {$ip_array["NETMASK"]} $nic";
	   	}
	   }		
	  if(is_dir("/etc/sysconfig/network-scripts")){
	  	@file_put_contents("/etc/sysconfig/network-scripts/route-$nic",@implode("\n",$f)."\n");
	  	@chmod("/etc/sysconfig/network-scripts/route-$nic",0777);
	  }
	  if(is_dir("/etc/sysconfig/network/scripts")){
	  	@file_put_contents("/etc/sysconfig/network/scripts/ifroute-$nic",@implode("\n",$suse)."\n");
	  	@chmod("/etc/sysconfig/network/scripts/ifroute-$nic",0777);
	  	shell_exec("ln -s /etc/sysconfig/network/scripts/ifup-route /etc/sysconfig/network/if-up.d/ifup-route >/dev/null");
	  }	  
		
	}
	
	public function NETWORK_ADD_ROUTE($nic,$array){
		return;
		if(!is_file("/etc/network/interfaces")){$this->NETWORK_ADD_ROUTE_REDHAT($nic,$array);return;}
		$start=false;
		$newf=array();
		$f=explode("\n",@file_get_contents("/etc/network/interfaces"));
		while (list ($num, $ligne) = each ($f) ){
			if(trim($ligne)==null){unset($f[$num]);continue;}
			if(preg_match("#^iface\s+$nic#",$ligne)){$start=true;$newf[]=$ligne;unset($f[$num]);continue;}
			if($start){
				if(preg_match("#up route add -net#",$ligne)){unset($f[$num]);continue;}
				if(preg_match("#down route del#",$ligne)){unset($f[$num]);continue;}
				$newf[]=$ligne;
				unset($f[$num]);
				if(preg_match("#^iface#",$ligne)){break;}
			}
			
		}
		
	   if(is_array($array)){
	   	 while (list ($ip, $ip_array) = each ($array) ){
	   		$newf[]="\tup route add -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   		$newf[]="\tdown route del -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   	}
	   }
		
	   @file_put_contents("/etc/artica-postfix/routes-interfaces-$nic",@implode("\n",$newf));
	   $new_interface=@implode("\n",$f)."\n".@implode("\n",$newf)."\n";
	   @file_put_contents("/etc/network/interfaces",$new_interface);
		
	}

	public function NETWORK_SAVE_DEBIAN_NIC($array){
		
	}
	
	public function NETWORK_DEBIAN_PARSE_ARRAY($tmpfile=null){
	$file="/etc/network/interfaces";
		if(is_file($tmpfile)){$file=$tmpfile;}
		if($GLOBALS["VERBOSE"]){echo "Parse $file..\n";}
		$f=explode("\n",@file_get_contents($file));
		$nic=array();
		while (list ($num, $ligne) = each ($f) ){
			if(trim($ligne)==null){unset($f[$num]);continue;}
			if(preg_match("#auto\s+(.+?)\s+(.+)$#",$ligne,$re)){
				$iface=trim($re[1]);
				$nic[$iface]["AUTO"]=trim($re[2]);
				continue;
			}
			
			if(preg_match("#iface\s+(.+?)inet\s+(.+)$#",$ligne,$re)){
				$iface=trim($re[1]);
				$nic[$iface]["BOOTPROTO"]=trim($re[2]);
				continue;
			}
			
			if(preg_match("#address\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["IPADDR"]=trim($re[1]);continue;}
			if(preg_match("#gateway\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["GATEWAY"]=trim($re[1]);continue;}
			if(preg_match("#\s+netmask\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["NETMASK"]=trim($re[1]);continue;}
			if(preg_match("#^netmask\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["NETMASK"]=trim($re[1]);continue;}
			if(preg_match("#broadcast\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["BROADCAST"]=trim($re[1]);continue;}
			if(preg_match("#bridge_ports\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_PORTS"]=trim($re[1]);continue;}
			if(preg_match("#bridge_stp\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_STP"]=trim($re[1]);continue;}
			if(preg_match("#bridge_maxwait\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_MAXWAIT"]=trim($re[1]);continue;}
			if(preg_match("#dns-search\s+(.+)$#",$ligne,$re)){$nic[$iface]["DNS_SEARCH"]=trim($re[1]);continue;}
			if(preg_match("#vlan_raw_device\s+(.+)$#",$ligne,$re)){$nic[$iface]["VLAN_ROW_DEVICE"]=trim($re[1]);continue;}
			if(preg_match("#mtu\s+(.+)$#",$ligne,$re)){$nic[$iface]["MTU"]=trim($re[1]);continue;}
			
			
			
			if(preg_match("#dns-nameservers\s+(.+)$#",$ligne,$re)){
				if(preg_match("#([0-9\.]+)\s+([0-9\.]+)#",$re[1],$ri)){
					$nic[$iface]["DNS1"]=$ri[1];
					$nic[$iface]["DNS2"]=$ri[2];
				}else{
					$nic[$iface]["DNS1"]=trim($re[1]);}
				continue;
				}

 			if(preg_match("#up\s+route\s+add.+?net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+gw\s+([0-9\.]+)#",$ligne,$re)){
 				$nic[$iface]["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"GATEWAY"=>$re[3]);
 				continue;
 			}			
				
				
			}
		return $nic;
		
	}
	
	public function NETWORK_DEBIAN_SAVE($eth,$arrayToSave=array(),$norestart=false){
		$array_eth=$this->NETWORK_DEBIAN_PARSE_ARRAY();
		
		writelogs_framework("Modify $eth configuration with an array of ". count($arrayToSave) . " elements",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if(count($arrayToSave)>0){
			while (list ($a, $b) = each ($arrayToSave) ){
				writelogs_framework("Modify $eth configuration with $a=$b",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		
		if($arrayToSave["UNSET"]){unset($array_eth[$eth]);}
		if(isset($arrayToSave["AUTO"])){$array_eth[$eth]["AUTO"]=$arrayToSave["AUTO"];}
		if(isset($arrayToSave["BRIDGE_PORTS"])){$array_eth[$eth]["BRIDGE_PORTS"]=$arrayToSave["BRIDGE_PORTS"];}
		if(isset($arrayToSave["BRIDGE_STP"])){$array_eth[$eth]["BRIDGE_STP"]=$arrayToSave["BRIDGE_STP"];}
		if(isset($arrayToSave["BRIDGE_MAXWAIT"])){$array_eth[$eth]["BRIDGE_MAXWAIT"]=$arrayToSave["BRIDGE_MAXWAIT"];}
		if(isset($arrayToSave["DNS_SEARCH"])){$array_eth[$eth]["DNS_SEARCH"]=$arrayToSave["DNS_SEARCH"];}
		if(isset($arrayToSave["VLAN_ROW_DEVICE"])){$array_eth[$eth]["VLAN_ROW_DEVICE"]=$arrayToSave["VLAN_ROW_DEVICE"];}
		if(isset($arrayToSave["MTU"])){$array_eth[$eth]["MTU"]=$arrayToSave["MTU"];}
		if(isset($arrayToSave["IPADDR"])){if($arrayToSave["IPADDR"]<>null){$array_eth[$eth]["IPADDR"]=$arrayToSave["IPADDR"];}}
		if(isset($arrayToSave["NETMASK"])){if($arrayToSave["NETMASK"]<>null){$array_eth[$eth]["NETMASK"]=$arrayToSave["NETMASK"];}}
		if(isset($arrayToSave["BOOTPROTO"])){if($arrayToSave["BOOTPROTO"]<>null){$array_eth[$eth]["BOOTPROTO"]=$arrayToSave["BOOTPROTO"];}}
		if(isset($arrayToSave["GATEWAY"])){if($arrayToSave["GATEWAY"]<>null){$array_eth[$eth]["GATEWAY"]=$arrayToSave["GATEWAY"];}}
		if(isset($arrayToSave["BROADCAST"])){if($arrayToSave["BROADCAST"]<>null){$array_eth[$eth]["BROADCAST"]=$arrayToSave["BROADCAST"];}}
		if(isset($arrayToSave["DNS1"])){if($arrayToSave["DNS1"]<>null){$array_eth[$eth]["DNS1"]=$arrayToSave["DNS1"];}}
		if(isset($arrayToSave["DNS2"])){if($arrayToSave["DNS2"]<>null){$array_eth[$eth]["DNS2"]=$arrayToSave["DNS2"];}}
		if(is_array($arrayToSave["ROUTES"])){$array_eth[$eth]["ROUTES"]=$arrayToSave["ROUTES"];}	

		if(isset($array_eth["lo"]["AUTO"])){$AUTOLO=" ".$array_eth["lo"]["AUTO"];}
		$f[]="auto lo$AUTOLO";
		$f[]="iface lo inet loopback";
		
		unset($array_eth["lo"]);
		$array=array();
		
		while (list ($eth, $array) = each ($array_eth) ){
			if(!isset($array["GATEWAY"])){$array["GATEWAY"]=null;}
			
			writelogs_framework("$eth BOOTPROTO= {$array["BOOTPROTO"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			writelogs_framework("$eth address = {$array["IPADDR"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$f[]="";
			$f[]="auto $eth";
			$NETWORK_INTERFACES[]="auto $eth";
			$f[]="iface $eth inet {$array["BOOTPROTO"]}";
			if($array["BOOTPROTO"]=="dhcp"){continue;}
			
	        $f[]="\taddress {$array["IPADDR"]}";
	        if($array["GATEWAY"]<>null){$f[]="\tgateway {$array["GATEWAY"]}";}
	        $f[]="\tnetmask {$array["NETMASK"]}";
	        if($array["BROADCAST"]<>null){$f[]="\tbroadcast {$array["BROADCAST"]}";}
	        if(isset($array["BRIDGE_PORTS"])){$f[]="\tbridge_ports {$array["BRIDGE_PORTS"]}";}
			if(isset($array["BRIDGE_STP"])){$f[]="\tbridge_stp {$array["BRIDGE_STP"]}";}
			if(isset($array["BRIDGE_MAXWAIT"])){$f[]="\tbridge_maxwait {$array["BRIDGE_MAXWAIT"]}";}
			if(isset($array["DNS_SEARCH"])){$f[]="\tdns-search {$array["DNS_SEARCH"]}";}
			if(isset($array["VLAN_ROW_DEVICE"])){$f[]="\tvlan_raw_device {$array["VLAN_ROW_DEVICE"]}";}
			if(isset($array["MTU"])){$f[]="\tmtu {$array["MTU"]}";}
			
			
	        
	        $dns="{$array["DNS1"]} {$array["DNS2"]}";
	        if(trim($dns)<>null){$f[]="\tdns-nameservers $dns";}
	        if(is_array($array["ROUTES"])){
	         	while (list ($ip, $ip_array) = each ($array["ROUTES"]) ){
	   				$f[]="\tup route add -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   				$f[]="\tdown route del -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   			}	
	        }
	        
		}
		$f[]="";
		if(is_file("/etc/network/if-up.d/sendmail")){@unlink("/etc/network/if-up.d/sendmail");}
		if(is_file("/etc/network/if-up.d/mountnfs")){@unlink("/etc/network/if-up.d/mountnfs");}
		
		
		
		if(!$norestart){$this->NETWORK_DEBIAN_RESTART();}			
	}
	
public function NETWORK_DEBIAN_RESTART($file=null,$function=null){
	$nohup=$this->find_program("nohup");
	$file=basename($file);
	$this->ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec("$nohup /etc/init.d/artica-ifup start --script=$file/$function >/dev/null 2>&1 &");
	
}

public function NETWORK_REDHAT_RESTART(){
	$nohup=$this->find_program("nohup");
	$this->ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec("$nohup /etc/init.d/artica-ifup start >/dev/null 2>&1 &");
}

public function KILL_PROCESS($pid,$signal=0){
	$pid=intval($pid);
	if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: $pid signal:$signal\n";}
	if($pid<5){
		if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: ABORTING\n";}
		return;}
	if($signal==null){$signal=15;}
	if($signal==0){$signal=15;}
	
	
	$arg["HUP"]=1;
	$arg["USR1"]=10;
	$arg["USR2"]=12;
	$arg["TERM"]=15;
	$arg["WINCH"]=28;
	$arg["KILL"]=9;
	
	$argnum[1]="HUP";
	$argnum[10]="USR1";
	$argnum[12]="USR2";
	$argnum[15]="TERM";
	$argnum[28]="WINCH";
	$argnum[9]="KILL";

	if(is_numeric($signal)){
		if(isset($argnum[$signal])){
			if(is_file("/proc/$pid/cmdline")){$cmdline=@file_get_contents("/proc/$pid/cmdline");}
			$this->Tologs("/var/log/processes.killed.logs", "Send signal {$argnum[$signal]} process id: $pid `$cmdline`");
			posix_kill($pid,$signal);
		}else{
			if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: unkbown signal: $signal ABORTING\n";}
		}
		return;
		
	}
	
	
	
	if(isset($arg[$signal])){
		if(is_file("/proc/$pid/cmdline")){$cmdline=@file_get_contents("/proc/$pid/cmdline");}
		$this->Tologs("/var/log/processes.killed.logs", "Send signal $signal process id: $pid `$cmdline`");
		posix_kill($pid,$arg[$signal]);
	}

}




function proc_exec($cmd){
	$BUF_SIZE=1024;
	$FD_WRITE=0 ;      # stdin
	$FD_READ=1;
	$FD_ERR= 2;        # stderr	
	$errbuf=null;
	$first_exitcode=null;
	$array=array();
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr)){return array();}

    while (($buffer = fgets($pipes[$FD_READ], $BUF_SIZE)) != NULL || ($errbuf = fgets($pipes[$FD_ERR], $BUF_SIZE)) != NULL) {
        if (!isset($flag)) {
            $pstatus = proc_get_status($ptr);
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        if (strlen($buffer)){
        	$buffer=str_replace("\n", "", $buffer);
        	$buffer=str_replace("\r", "", $buffer);
            $array[]=$buffer;
        }
        
        if (strlen($errbuf)){
            //echo "ERR: " . $errbuf;
        }
    }

    foreach ($pipes as $pipe){
        fclose($pipe);
    }

    
    $pstatus = proc_get_status($ptr);
    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {
        
        if ($pstatus["running"])
            proc_terminate($ptr);
        $ret = proc_close($ptr);
    } else {
        if ((($first_exitcode + 256) % 256) == 255
                && (($pstatus["exitcode"] + 256) % 256) != 255)
            $ret = $pstatus["exitcode"];
        elseif (!strlen($first_exitcode))
            $ret = $pstatus["exitcode"];
        elseif ((($first_exitcode + 256) % 256) != 255)
            $ret = $first_exitcode;
        else
            $ret = 0; 
        proc_close($ptr);
    }

    return $array;
}





private function NETWORK_REDHAT_NETWORKMANAGER_DISABLE(){
	$cacheFile="/etc/artica-postfix/redhat.network.manager.disabled";
	if(is_file($cacheFile)){return;}
	$chkconfig=$this->find_program("chkconfig");
	$service=$this->find_program("service");
	if(!is_file($chkconfig)){return;}
	shell_exec("$chkconfig NetworkManager off");
	shell_exec("$chkconfig network on");
	shell_exec("$service NetworkManager stop");
	shell_exec("$service network start");
	@file_put_contents("$cacheFile",time());
	}
	
	
	public function CACHE_VERSIONS($KEY){
		if(isset($GLOBALS["CACHE_VERSIONS"][$KEY])){return $GLOBALS["CACHE_VERSIONS"][$KEY];} 
		if(!is_file("/usr/share/artica-postfix/ressources/logs/global.versions.conf")){return null;}
		
		if(!isset($GLOBALS["CACHE_VERSIONS"]["global.versions.conf"])){
			$GLOBALS["CACHE_VERSIONS"]["global.versions.conf"]=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/global.versions.conf"));
			}
		
		while (list ($num, $val) = each ($GLOBALS["CACHE_VERSIONS"]["global.versions.conf"])){
			if(preg_match('#\[$KEY\]\s+"(.+?)"#',$val,$re)){$GLOBALS["CACHE_VERSIONS"][$KEY]=$re[1];return $re[1];}
		}
		
	}

	
	public function LOCATE_SQUID_CONF(){
		   if(is_file('/etc/squid3/squid.conf')){ return '/etc/squid3/squid.conf';}
	   	   if(is_file('/opt/artica/etc/squid.conf')){ return '/opt/artica/etc/squid.conf';}
	   	   if(is_file('/etc/squid/squid.conf')){ return '/etc/squid/squid.conf';}
			}
	
	public function LOCATE_DHCPD_PID_PATH(){
		
		   if(is_file('/var/run/dhcpd.pid')){ return '/var/run/dhcpd.pid';}
	   	   if(is_file('/var/run/dhcpd/dhcpd.pid')){ return '/var/run/dhcpd/dhcpd.pid';}
	   	   if(is_file('/var/run/dhcp3-server/dhcpd.pid')){ return '/var/run/dhcp3-server/dhcpd.pid';}
			}	
	
	public function LOCATE_PURE_FTPD_PID_PATH(){
		if(is_file('/var/run/pure-ftpd/pure-ftpd.pid')){return "/var/run/pure-ftpd/pure-ftpd.pid";}
		if(is_file('/var/run/pure-ftpd.pid')){return "/var/run/pure-ftpd.pid";}
		}
	
	
	 public function SQUID_CONFIG_PATH(){
	   if(is_file("/etc/squid3/squid.conf")){return '/etc/squid3/squid.conf';}
	   if(is_file('/opt/artica/etc/squid.conf')){return '/opt/artica/etc/squid.conf';}
	   if(is_file('/etc/squid/squid.conf')){return '/etc/squid/squid.conf';}
	   return "/etc/squid3/squid.conf";
	}	
	
	public function SQUID_ALL_PORTS(){
		$array=array();
		$SQUID_CONFIG_PATH=$this->SQUID_CONFIG_PATH();
		$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
		while (list ($num, $line) = each ($f)){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#^http.*?_port\s+(.+)#",$line,$re)){
				$port=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo " *** PORT: `$port` -> $line\n";}
				if(preg_match("#^(.+?)\s+#", $port,$re)){$port=$re[1];}
						
				if(preg_match("#^(.+?):([0-9]+)#", $port,$re)){
					if($GLOBALS["VERBOSE"]){echo " *** PORT: `{$re[2]}` -> $port\n";}
					$port=$re[2];}
					$array[$port]=true;
				}
		
			}
		
			return $array;
		
		}
		
		
	
	
	
	public function BetterFS(){
		if(!$this->IsExt4()){return "ext3";}
		if(is_file($this->find_program("mkfs.ext4"))){return "ext4";}
		if(is_file($this->find_program("mkfs.ext3"))){return "ext3";}
		return "ext3";
	}
	
	public function IsExt4(){
		$f=explode("\n",@file_get_contents("/proc/filesystems"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#.*?\s+ext4#",$ligne)){return true;}
		}
		return false;
	}

	public function hostname_g(){
		
		if(is_file("/etc/artica-postfix/settings/Daemons/myhostname")){
			$results=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/myhostname"));
			if(strlen($results)>2){return $results;}
		}
		
		
		$uname=trim(posix_uname());
		$domainname=trim($uname["domainname"]);
		if($domainname=="(none)"){$domainname="localhost.localdomain";}
		
 		$hostname=trim($uname["nodename"]);
 		if(strpos($hostname, ".")==0) {
 			if($domainname<>null){$hostname=trim("$hostname.$domainname");}
 		}
 		return trim($hostname); 
 		
 		
 		
		}
	public function hostname_simple(){
 		$host=$this->hostname_g();
 		if(strpos($host, ".")>0){$tt=explode(".",$host);$host=$tt[0];}
 		return $host;
		}		
		
	function BACKUPPC_locate_config_path(){
		if(is_file("/etc/backuppc/config.pl")){return "/etc/backuppc";}
		if(is_file("/etc/BackupPC/config.pl")){return "/etc/BackupPC";}
	
	}
	
	
	
	
	function chmod_alldirs($bit,$dir){
		$dirz=explode("/",$dir);
		$dirStart="/{$dirz[0]}";
		if($dirStart=="/"){$dirStart=null;}
		for($i=1;$i<count($dirz);$i++){
			$dirStart=$dirStart."/".$dirz[$i];
			if(!is_dir($dirStart)){@mkdir($dirStart,$bit,true);continue;}
			@chmod($dirStart, $bit);
		
		}
		
		
	}
	public function ToSyslog($text,$error=false,$forcefile=null){
		
		$file="framework";
		$function=__FUNCTION__;
		$line=__LINE__;
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			$debug_backtrace=$this->debug_backtrace_array($trace);
			$file=basename($debug_backtrace["file"]);
			$function="{$debug_backtrace["function"]}()";
			$ligne=intval($debug_backtrace["line"]);
				
			
		}
		if($forcefile<>null){$file=$forcefile;}
		$text="$function{".$line."} $text";
		if($GLOBALS["VERBOSE"]){echo "$text\n";}
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog($file, LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	private function Tologs($file,$text){
		if($GLOBALS["VERBOSE"]){echo "[$file]: $text\n";}
		@mkdir(dirname($file));
		$logFile=$file;
		
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    if($size>100000){unlink($logFile);}
   		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		
		$time=date("H:i:s");
		@fwrite($f, "$time: $text\n");
		@fclose($f);
	}
	
	private function SystemUserGroups($username){
		$username=strtolower(trim($username));
		$groupsLine=null;
		$ARRAY=array();
		$groupsbin=$this->find_program("groups");
		exec("$groupsbin $username 2>&1",$results);
		while (list ($index, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(preg_match("$username.*?:(.+)#", $ligne,$re)){
				$groupsLine=trim($re[1]);
				break;
			}
		}
		
		if($groupsLine==null){return array();}
		$xgroupsLine=explode(" ",$groupsLine);
		while (list ($index, $ligne) = each ($xgroupsLine) ){
			$ligne=trim(strtolower($ligne));
			if($ligne==null){continue;}
			$ARRAY[$ligne]=$ligne;
		}
		
	}
	
	public function SystemIsUserInGroup_cmdline($username,$groupName){
		$groupName=trim(strtolower($groupName));
		if($groupName==null){return true;}
		$ARRAY=$this->SystemUserGroups($username);
		if(!isset($ARRAY[$groupName])){return false;}
		return true;
		
		
	}
	
	public function SystemAddUserToGroup($username,$groupName){
		
		
		
		if($this->SystemIsUserIngroup($username, $groupName)){return true;}
		if(!$this->SystemUserExists($username)){
			$useradd=$this->find_program("useradd");
		}else{
			$useradd=$this->find_program("usermod")." -a ";
		}
		exec("$useradd -G \"$groupName\" \"$username\" 2>&1",$results);
		if($this->SystemIsUserIngroup($username, $groupName)){return true;}
		
		while (list ($index, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$this->ToSyslog("Warning, Unable to add user `$username` in `$groupName` $useradd say `$ligne`");
		}
		
		
		return false;
		
	}
	
	private function debug_backtrace_implode($array){
		if(isset($array[0])){
			return "called by ". basename($array[0]["file"])." {$array[0]["function"]}() line {$array[0]["line"]}";
		}
		
		if(isset($array[1])){
			return "called by ". basename($array[1]["file"])." {$array[1]["function"]}() line {$array[1]["line"]}";
			
		}
		

		
		
		
	}
	
	function chown_func($user,$group=null,$dir=null){
		$called=null;
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			$called=$this->debug_backtrace_implode($trace);
		}
		
		if($user=="squid"){
			if(!isset($GLOBALS["SQUID_BIN"])){ $GLOBALS["SQUID_BIN"]=$this->LOCATE_SQUID_BIN(); }
			if(!is_file($GLOBALS["SQUID_BIN"])){
				$this->ToSyslog("Warning, want to chown $user:$group with no squid installed $called");
				return;
			}
		}
		
		if($user=="postfix"){
			if(!isset($GLOBALS["POSTCONF_BIN"])){$GLOBALS["POSTCONF_BIN"]=$this->find_program("postconf");}
			if(!is_file($GLOBALS["POSTCONF_BIN"])){
				$this->ToSyslog("Warning, want to chown $user:$group with no Postfix installed $called");
				return;
			}
			
		}
		
		if($user==null){return;}
		$chown=$this->find_program("chown");
		
	
		if(!isset($GLOBALS["DEBUG_CHOWN"])){ 
			$GLOBALS["DEBUG_CHOWN"]=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DebugChownFunc")));
		}

		
		
		if($GLOBALS["DEBUG_CHOWN"]){
			$this->Tologs("/var/log/artica.chown.debug","$user:$group \"$dir\" $called");
		}		
		
		
		
		if(trim($dir)==null){
			$this->ToSyslog("Warning, want to chown $user:$group with no directory $called");
			return;
		}
		if($group=='${APACHE_RUN_GROUP}'){$group="www-data";}
		
		if(trim($group)<>null){
			if(!$this->SystemIsUserIngroup($user,$group) ){
				$this->ToSyslog("Warning, want to chown user: `$user` group:`$group` invalid $called");
				if(!$this->SystemGroupExists($group) ){
					$this->ToSyslog("Warning, want to chown non existent `$group` -> create it $called");
					$this->SystemCreateGroup($group);
				}
				if(!$this->SystemGroupExists($group) ){
					$this->ToSyslog("Warning, want to chown non existent $group failed, aborting");
					return ;
				}
				$this->ToSyslog("Adding user `$user` to group `$group`");
				if(!$this->SystemAddUserToGroup($user,$group)){
					$this->ToSyslog("Warning, Unable to add user `$user` in `$group` failed, aborting");
					return;
				}
				
			}
		}
		

		
		if(isset($GLOBALS["chown_func"][md5("$user$group$dir")])){return;}
		$dir=trim($dir);
		$recursive=false;
		
		
		
		if(substr($dir, strlen($dir)-1,1)=="*"){
			$dir=substr($dir, 0,strlen($dir)-1);
			$recursive=true;
		}
		
		if(!is_dir($dir)){
			if(!is_file($dir)){
				return;
			}
		}
		
		if(strlen($group)>1){
			if(strpos("/", $group)){
				if((is_file($group)) OR is_dir(is_file($group)) ){$dir=$group;$group=$user;}
			}
		}
		
		if(preg_match("#(.*?):(.+)#", $user,$re)){
			$user=$re[1];
			$group=$re[2];
		}
		
		if($group<>null){$group=":$group";}
		
		if($recursive){
			if($GLOBALS["VERBOSE"]){echo "$chown -R $user$group $dir >/dev/null\n";}
			shell_exec("$chown -R $user$group $dir >/dev/null");
			
			
		}else{		
			if($GLOBALS["VERBOSE"]){echo "$chown $user$group $dir >/dev/null\n";}
			shell_exec("$chown $user$group $dir >/dev/null");
		}
		
		$GLOBALS["chown_func"][md5("$user$group$dir")]=true;
		
	}
	
	function chmod_func($bit,$dir){
		if(@is_link($dir)){$dir=@readlink($dir);}
		if(!is_dir($dir)){if(!is_file($dir)){return;}}
		$findbit=false;
		if($bit=="493"){$bit="0755";$findbit=true;}
		if($bit=="511"){$bit="0777";$findbit=true;}
		if($bit=="416"){$bit="0640";$findbit=true;}
		
		if($bit=="0755"){$bit="0755";$findbit=true;}
		if($bit=="0777"){$bit="0777";$findbit=true;}
		if($bit=="0640"){$bit="0640";$findbit=true;}
		
		if($bit=="755"){$bit="0755";$findbit=true;}
		if($bit=="777"){$bit="0777";$findbit=true;}
		if($bit=="640"){$bit="0640";$findbit=true;}		
		
		if(!$findbit){return;}
		
		if(isset($GLOBALS["chmod_func"][md5("$bit$dir")])){return;}
		$dir=trim($dir);
		$recursive=false;
		$chmod=$this->find_program("chmod");
		
		if(!isset($GLOBALS["DEBUG_CHOWN"])){
			$GLOBALS["DEBUG_CHOWN"]=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DebugChownFunc")));
		}
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}
		}
		
		if($GLOBALS["DEBUG_CHOWN"]){
			$this->Tologs("/var/log/artica.chown.debug","bit $bit \"$dir\" $called");
		}		
		
		if(substr($dir, strlen($dir)-1,1)=="*"){
			$dir=substr($dir, 0,strlen($dir)-1);
			$recursive=true;
		}
		
		
			
		if($recursive){
				shell_exec("$chmod -R $bit $dir >/dev/null 2>&1");
		}else{
			shell_exec("$chmod $bit $dir >/dev/null 2>&1");
		}		
					
		$GLOBALS["chmod_func"][md5("$bit$dir")]=true;
		
	}
	
	function PEAR_INSTALL_CHECK($packagename){
		$pear=$this->find_program("pear");
		if(strlen($pear)<5){return false;}
		exec("$pear list 2>&1",$array);
		while (list ($num, $ligne) = each ($array) ){
			if(preg_match("#^$packagename\s+#",$ligne,$re)){
				return true;
			}
		}
		return false;
	}
	

	function BACKUPPC_GET_CONFIG_INFOS($key){
		$f=explode("\n",@file_get_contents($this->BACKUPPC_locate_config_path()."/config.pl"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#Conf\{$key\}.*?'(.+?)';#i",$ligne,$re)){
				$re[1]=trim($re[1]);
				$re[2]=str_replace("'","",$re[1]);
				return $re[1];
			}
		}
		
	}
	
	function BACKUPPC_GET_CGIBIN_PATH(){
		if(is_file("/usr/share/backuppc/cgi-bin/index.cgi")){return "/usr/share/backuppc/cgi-bin/index.cgi";}
		if(is_file("/usr/share/backuppc/cgi-bin/BackupPC_Admin")){return "/usr/share/backuppc/cgi-bin/BackupPC_Admin";}
		if(is_file("/usr/share/BackupPC/sbin/BackupPC_Admin")){return "/usr/share/BackupPC/sbin/BackupPC_Admin";}
		}
		
	function BACKUPPC_GET_IMG_DIR(){
		if(is_dir("/usr/share/backuppc/image")){return "/usr/share/backuppc/image";}
		if(is_dir("/usr/share/BackupPC/html")){return "/usr/share/BackupPC/html";}
		
		}
		
	function ZARAFA_VERSION(){
		$func=__CLASS__.'/'.__FUNCTION__;
		if(isset($GLOBALS["ZARAFA_VERSION"])){
			if(strlen($GLOBALS["ZARAFA_VERSION"])>5){return $GLOBALS["ZARAFA_VERSION"];}
		}
		$zarafa_server=$this->find_program("zarafa-server");
		if(!is_file($zarafa_server)){
			if($GLOBALS["VERBOSE"]){echo "$func:: zarafa-server no such file\n";}
			return;
		}
		exec("$zarafa_server -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Product version:\s+([0-9,\.]+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				$re[1]=str_replace(",",".",$re[1]);
				$GLOBALS["ZARAFA_VERSION"]=$re[1];
				return $re[1];
			}else{
				if($GLOBALS["VERBOSE"]){echo "$func:: $ligne no match\n";}
			}
			
		}
		
	}
	
	function ASSP_VERSION(){
		$func=__CLASS__.'/'.__FUNCTION__;
		if(strlen($GLOBALS["ASSP_VERSION"])>2){return $GLOBALS["ASSP_VERSION"];}
		$results=@explode("\n",@file_get_contents("/usr/share/assp/assp.pl"));
		if($GLOBALS["VERBOSE"]){echo count($results)." lines to parse\n";}
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#our\s+\$version[\s=]+.+?([0-9\.]+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$func:: match $ligne\n";}
				$GLOBALS["ASSP_VERSION"]=$re[1];
				return $re[1];
			}
			
			if(preg_match('#our.*?\$version\s+.*?([0-9\.]+)#',$ligne,$re)){
				$re[1]=trim($re[1]);
				$GLOBALS["ASSP_VERSION"]=$re[1];
				if($GLOBALS["VERBOSE"]){echo "$func:: match $ligne\n";}
				return $re[1];
			}
			
		}
		
		
	}
	
	public function clean_EtcHosts(){ return;	}	
	
	
	public function get_EtcHostsByName($hostname){
		$hostname=str_replace(".", "\.", $hostname);
		$f=explode("\n",@file_get_contents("/etc/hosts"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#^([0-9\.]+).*?$hostname#", $ligne,$re)){
				return $re[1];
			}
		}
	}
	
	
	public function create_EtcHosts($hostname,$ipaddr){
		$netbiosname=null;
		if(strpos($hostname, ".")>0){
			$hostnameT=explode(".",$hostname);
			$netbiosname=$hostnameT[0];
		}
		$UPDATE=false;
		$hostnameRegex=str_replace(".", "\.", $hostname);
		$f=explode("\n",@file_get_contents("/etc/hosts"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#^([0-9\.]+).*?$hostnameRegex#", $ligne,$re)){
				$f[$num]=null;
			}
		}
		
		$f[]="$ipaddr\t$hostname\t$netbiosname";
		reset($f);
		$FINAL=array();
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$FINAL[]=$ligne;
		}
		@file_put_contents("/etc/hosts", @implode("\n", $FINAL)."\n");
		
	}

	public function del_EtcHostsByName($hostname){}	
	public function del_EtcHosts($ip){}
	public function add_EtcHosts($hostname,$ip){}
		
		
		
	public function DISK_LIST(){
		if(!isset($GLOBALS["DEBUG_DISK"])){$GLOBALS["DEBUG_DISK"]=false;}
		$tmp=$this->FILE_TEMP();
		$results=array();
		exec($this->find_program("df") ." -P -h -B G",$returns);
		$pattern="#(.+)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)\%\s+(.+?)$#";
		while (list ($num, $ligne) = each ($returns) ){
			if(!preg_match($pattern,$ligne,$re)){continue;}
			if($GLOBALS["DEBUG_DISK"]){echo trim($re[1])." = {$re[2]}{$re[3]}\n";}
			
			if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
				$uuid=trim($ri[1]);
				$UUIDS=$this->BLKID_UUIDs();
				if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
			}
			
			if($GLOBALS["DEBUG_DISK"]){echo trim($re[1])." = {$re[2]}{$re[3]}\n";}
			$results[trim($re[1])]=array("SIZE"=>$re[2],"UNIT"=>$re[3],"USED"=>$re[4],"DISP"=>$re[6],"POURC"=>trim($re[8]));
			
		}
		
		return $results;
	}
	
	public function DISK_INODES(){
		
		if(is_file("/etc/artica-postfix/settings/Daemons/InodesStatus")){
			$TimeF=$this->file_time_min("/etc/artica-postfix/settings/Daemons/InodesStatus");
			if($TimeF<60){
				return unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/InodesStatus"));
			}
			@unlink("/etc/artica-postfix/settings/Daemons/InodesStatus");
		}
		
		
		$tmp=$this->FILE_TEMP();
		$results=array();
		$dfbin=$this->find_program("df");
		
		if($this->process_exists($this->PIDOF($dfbin))){return array();}
		
		exec("$dfbin -i -h 2>&1",$returns);
		$pattern="#^(.+?)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)%\s+#";
		//Filesystem                                             Inodes IUsed IFree IUse%
		while (list ($num, $ligne) = each ($returns) ){
			if(!preg_match($pattern,$ligne,$re)){
				if(preg_match("#^(.+?)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)%\s+#", $ligne,$re)){
					
					if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
						$uuid=trim($ri[1]);
						$UUIDS=$this->BLKID_UUIDs();
						if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
					}
					
					$results[trim($re[1])]=array("INODES"=>$re[2].$re[3],"USED"=>$re[4],"FREE"=>$re[5].$re[6],"POURC"=>trim($re[7]));
					continue;
				}
				continue;}
				
				if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
					$uuid=trim($ri[1]);
					$UUIDS=$this->BLKID_UUIDs();
					if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
				}				
				
			$results[trim($re[1])]=array("INODES"=>$re[2].$re[3],"USED"=>$re[4].$re[5],"FREE"=>$re[6].$re[7],"POURC"=>trim($re[8]));
				
		}
		@file_put_contents("/etc/artica-postfix/settings/Daemons/InodesStatus", serialize($results));
		return $results;
	}
	
	
	public function CHECK_PERL_MODULE($ModulesToCheck){
		$perlbin=$this->find_program("perl");
		$cmd="$perlbin -M{$ModulesToCheck} -e 'print \"\$$ModulesToCheck::VERSION\\n\"' 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$returns);
		while (list ($num, $ligne) = each ($returns) ){
			if($GLOBALS["VERBOSE"]){echo "\"$ligne\"\n";}
			if(preg_match("#Can't locate#i", $ligne)){return false;}
			if(preg_match("#([0-9\.]+)#", $ligne,$re)){
				if(trim($re[1])=="."){return false;}
				if($GLOBALS["VERBOSE"]){echo "SUCCESS {$re[1]}\n";}
				return true;
			}
			
		}
		
		return false;
		
	}
	
	public FUNCTION SQUID_PARMAZ(){
		if(isset($GLOBALS["SQUID_PARMAZ"])){
			if(count($GLOBALS["SQUID_PARMAZ"])>0){return $GLOBALS["SQUID_PARMAZ"];}
		}
		$squid=$this->LOCATE_SQUID_BIN();
		exec("$squid -v 2>&1",$results);
		if(preg_match("#configure options:(.+)#", @implode(" ", $results),$re)){
			$re[1]=str_replace("'", "", $re[1]);
			$opts=explode(" ",trim($re[1]));
			while (list ($num, $ligne) = each ($opts) ){
				$ligne=trim($ligne);
				if($ligne==null){continue;}
				if(preg_match("#(.+?)=(.*)#", $ligne,$re)){
					$GLOBALS["SQUID_PARMAZ"][$re[1]]=trim($re[2]);
					continue;
				}
				$GLOBALS["SQUID_PARMAZ"][$ligne]=true;
			}
				
		}

		return $GLOBALS["SQUID_PARMAZ"];
		
	}

	public function SQUID_ICAP_ENABLED(){
		$ARRAY=$this->SQUID_PARMAZ();
		if(isset($ARRAY["--enable-icap-client"])){return true;}
		if(isset($ARRAY["--enable-icap-support"])){return true;}
	}
	

	
public function file_size($path){
	if(!is_file($path)){return 0;}
	exec($this->find_program("stat")." $path 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			return $re[1];break;
		}
	}
	return 0;
}

public function file_size_human($path){
	if(!is_file($path)){if(!is_dir($path)){return 0;}}
	exec($this->find_program("du")." -h $path ",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#^([0-9A-Z,]+)\s+#",$line,$re)){
			return $re[1];break;
		}
	}
	return 0;
}

public function file_extension($filename){
	return @pathinfo($filename, PATHINFO_EXTENSION);
}

public function compress($source,$dest){
	
		if(!is_file($source)){
			$GLOBALS["COMPRESSOR_ERROR"]="Source not found";
			return false;}
	
		if(!function_exists("gzopen")){
			$GLOBALS["COMPRESSOR_ERROR"]="gzopen return false";
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->_syslog("Fatal!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){
	    	$GLOBALS["COMPRESSOR_ERROR"]="fp_out return false";
	    	return false ;
	    }
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){
	    	$GLOBALS["COMPRESSOR_ERROR"]="fp_in return false";
	    	return false;
	    }
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}
	function getSystemMemInfo(){
		$data = explode("\n", file_get_contents("/proc/meminfo"));
		$meminfo = array();
		foreach ($data as $line) {
			if(strpos($line, ":")==0){continue;}
			list($key, $val) = explode(":", $line);
			$val=trim($val);
			if(preg_match("#^([0-9\.]+)\s+#", $val,$re)){$val=$re[1];}
			$meminfo[$key] = trim($val);
		}
		return $meminfo;
	}

	function GetMemFreePourc(){
		$array=$this->getSystemMemInfo();
		$MemTotal=$array["MemTotal"];
		$MemFree=$array["MemFree"];
		if(!is_numeric($MemFree)){return;}
		if(!is_numeric($MemTotal)){return;}
		$percent=$MemFree/$MemTotal;
		$percent=$percent*100;
		$percent=round($percent);
		return $percent;
	}

	
public function uncompress($srcName, $dstName) {
	
	if(!is_file($srcName)){
		$GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $srcName -> No such file";
		$this->_syslog("Fatal!! $srcName -> No such file");
		return false;
	}
	
	$srcNameText=basename($srcName);
	$GLOBALS["UNCOMPRESS"]=array();
		if(!function_exists("gzopen")){
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			if($GLOBALS["VERBOSE"]){echo "uncompress:: FATAL!! gzopen no such function ! $called in\n";}
			$GLOBALS["UNCOMPRESS"][]="$srcNameText FATAL!! gzopen no such function";
			$this->_syslog("Fatal!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
		$dir=dirname($dstName);
		if(!is_dir($dir)){
			$GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $dir no such directory";
			if($GLOBALS["VERBOSE"]){echo "uncompress:: $dir no such directory\n";}
			return false;
		}
		
		$dstNameTMP="$dstName.tests";
	    $sfp = gzopen($srcName, "rb");
	    
	    if(!$sfp){
	    	$GLOBALS["UNCOMPRESSLOGS"]="gzopen($srcName) return false";
	    	$GLOBALS["UNCOMPRESS"][]="gzopen($srcName) return false";
	    	if($GLOBALS["VERBOSE"]){
	    	
	    	echo "gzopen($srcName) return false";}
	    	return false;
	    }
	    $fp = fopen($dstNameTMP, "w");
	    
	    
	    
		if(!$sfp){if($GLOBALS["VERBOSE"]){
			$GLOBALS["UNCOMPRESSLOGS"]="fopen($dstNameTMP) Permission denied";
			$GLOBALS["UNCOMPRESS"][]="fopen($dstNameTMP) Permission denied.";
			echo "fopen($dstNameTMP) Permission denied.\n";}
			return false;
		}
	    
	    while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	    gzclose($sfp);
	    fclose($fp);
	    $size=@filesize($dstNameTMP);
	    if($size==0){
	    	$GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $srcName -> $dstNameTMP $size = 0 bytes !!";
	    	$this->_syslog("Fatal!! $srcName -> $dstNameTMP $size = 0 bytes !!");
	    	$GLOBALS["UNCOMPRESS"][]="$dstNameTMP size = 0";
	    	if($GLOBALS["VERBOSE"]){echo "$srcName -> $dstNameTMP $size = 0 bytes !!\n";}
	    	@unlink($dstNameTMP);
	    	return false;
	    }
	    
	    @unlink($dstName);
	    $GLOBALS["UNCOMPRESS"][]="$dstNameTMP - > $dstName";
	    if($GLOBALS["VERBOSE"]){echo "$dstNameTMP - > $dstName\n";}
	    @copy($dstNameTMP, $dstName);
	    @unlink($dstNameTMP);
	    return true;
	    
} 

function squid_version(){
	$squidbin=$this->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			return trim($re[1]);
		}
	}

}



public function SquidPHPFiles(){
	$squidfiles["exec.logfile_daemon.php"]=true;
	$squidfiles["external_acl_squid_ldap.php"]=true;
	$squidfiles["external_acl_dynamic.php"]=true;
	$squidfiles["external_acl_paranoid.php"]=true;
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
	$squidfiles["external_acl_categories.php"]=true;
	$squidfiles["external_acl_categorize.php"]=true;
	$squidfiles["external_acl_restrict_access.php"]=true;
	$squidfiles["external_acl_squid_extldap.php"]=true;
	$squidfiles["external_acl_squid.php"]=true;
	$squidfiles["external_acl_usersMacs.php"]=true;
	$squidfiles["external_acl_hotspot.php"]=true;
	return $squidfiles;
}

function samba_version(){
	
	$winbind=$this->find_program("smbd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
}

public function Winbindd_privileged_SQUID(){
	
	$chmod=$this->find_program("chmod");
	$setfacl=$this->find_program("setfacl");
	$nohup=$this->find_program("nohup");
	
	$possibleDirsACLS[]="/var/lib/samba";
	$possibleDirsACLS[]="/var/run/samba";
	$possibleDirsACLS[]="/var/cache/samba";
	
	
		while (list ($num, $Directory) = each ($possibleDirsACLS) ){
			if(!is_dir($Directory)){continue;}
			shell_exec("$nohup $setfacl -R -m u:squid:rwx $Directory >/dev/null 2>&1 &");
			shell_exec("$nohup $setfacl -R -m g:squid:rwx $Directory >/dev/null 2>&1 &");
			
			if(is_dir("$Directory/winbindd_privileged")){
				shell_exec("$chmod 0750 $Directory/winbindd_privileged >/dev/null");
				
				if(strlen($setfacl)>5){
					shell_exec("$setfacl -m u:squid:rx $Directory/winbindd_privileged >/dev/null 2>&1");
				}
				
				$stat=$this->alt_stat("$Directory/winbindd_privileged/pipe");
				if(isset($stat["owner"])){
					shell_exec("$chmod 1777 $Directory/winbindd_privileged/pipe >/dev/null");
				}
			}

			
			if(!is_dir("$Directory/smb_krb5")){continue;}
			if(strlen($setfacl)>5){
				shell_exec("$nohup $setfacl -R -m u:squid:rwx $Directory/smb_krb5 >/dev/null 2>&1 &");
				shell_exec("$nohup $setfacl -R -m g:squid:rwx $Directory/smb_krb5 >/dev/null 2>&1 &");
			}
			
		}
		
}

public function _syslog($text,$file=null,$error=false){
	if(!function_exists("syslog")){return;}
	if($file<>null){$file=basename($file);}
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}	

public function is_socket($socketpath){
	$stat=$this->alt_stat($socketpath);
	$type=$stat["filetype"]["type"];
	if($type=="socket"){return true;}
	return false;
}

public function IS_FIREHOLE_ACTIVE(){
	if(!is_file("/usr/local/sbin/firehol")){return false;}
	if(!is_file("/etc/artica-postfix/settings/Daemons/FireHolConfigured")){return false;}
	$FireHolConfigured=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/FireHolConfigured"));
	if($FireHolConfigured==0){return false;}
	if(!is_file("/etc/artica-postfix/settings/Daemons/FireHolEnable")){return false;}
	$FireHolEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/FireHolEnable"));
	if($FireHolEnable==0){return false;}
	return true;
	
}
	
public function alt_stat($file=null) {
	 if($file==null){return;}
	 $ss=@stat($file);
	 if(!$ss) return false; //Couldnt stat file
	 
	 $ts=array(
	  0140000=>'ssocket',
	  0120000=>'llink',
	  0100000=>'-file',
	  0060000=>'bblock',
	  0040000=>'ddir',
	  0020000=>'cchar',
	  0010000=>'pfifo'
	 );
	 
	 $p=$ss['mode'];
	 $t=decoct($ss['mode'] & 0170000); // File Encoding Bit
	 
	 $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
	 $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
	 $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
	 $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
	 $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
	 $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
	 $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
 	if(!isset($ss['type'])){$ss['type']=null;}
		 $s=array(
		 'perms'=>array(
		  'umask'=>sprintf("%04o",@umask()),
		  'human'=>$str,
		  'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
		  'octal2'=>sprintf("0%o", 0777 & $p),
		  'decimal'=>sprintf("%04o", $p),
		  'fileperms'=>@fileperms($file),
		  'mode1'=>$p,
		  'mode2'=>$ss['mode']),
		 
		 'owner'=>array(
		  'fileowner'=>$ss['uid'],
		  'filegroup'=>$ss['gid'],
		  'owner'=>
		  (function_exists('posix_getpwuid'))?
		  @posix_getpwuid($ss['uid']):'',
		  'group'=>
		  (function_exists('posix_getgrgid'))?
		  @posix_getgrgid($ss['gid']):''
		  ),
		 
		 'file'=>array(
		  'filename'=>$file,
		  'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
		  'dirname'=>@dirname($file),
		  'basename'=>@basename($file)
		  ),
		
		 'filetype'=>array(
		  'type'=>substr($ts[octdec($t)],1),
		  'type_octal'=>sprintf("%07o", octdec($t)),
		  'is_file'=>@is_file($file),
		  'is_dir'=>@is_dir($file),
		  'is_link'=>@is_link($file),
		  'is_readable'=> @is_readable($file),
		  'is_writable'=> @is_writable($file)
		  ),
		 
		 'device'=>array(
		  'device'=>$ss['dev'], //Device
		  'device_number'=>$ss['rdev'], //Device number, if device.
		  'inode'=>$ss['ino'], //File serial number
		  'link_count'=>$ss['nlink'], //link count
		  'link_to'=>($ss['type']=='link') ? @readlink($file) : ''
		  ),
		 
		 'size'=>array(
		  'size'=>$ss['size'], //Size of file, in bytes.
		  'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
		  'block_size'=> $ss['blksize'] //Optimal block size for I/O.
		  ),
		 
		 'time'=>array(
		  'mtime'=>$ss['mtime'], //Time of last modification
		  'atime'=>$ss['atime'], //Time of last access.
		  'ctime'=>$ss['ctime'], //Time of last status change
		  'accessed'=>@date('Y M D H:i:s',$ss['atime']),
		  'modified'=>@date('Y M D H:i:s',$ss['mtime']),
		  'created'=>@date('Y M D H:i:s',$ss['ctime'])
		  ),
		 );

 return $s;
}	
	
	private function MapperDetect($dev){
	$mapper=@readlink($dev);
	if($mapper==null){return $dev;}
	$mapper=str_replace("../mapper","/dev/mapper",$mapper);
	return $mapper;
	}
	
	private function folder_to_regex($path){
		$path=str_replace("/","\/",$path);
		$path=str_replace(".","\.",$path);
		$path=str_replace("$","\$",$path);
		$path=str_replace("[","\[",$path);		
		$path=str_replace("]","\]",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		return $path;
	}
	
	function LESSFS_ARRAY(){
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		$array=array();
		while (list ($num, $ligne) = each ($datas) ){
		 if(preg_match("#^lessfs\s+(.+?)\s+fuse\.lessfs\s+#",$ligne,$re)){$array[trim($re[1])]=true;}
		}
		return $array;
		
	}
	
	public function DISK_MOUNTED($dev){
		$dev=$this->MapperDetect($dev);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		$dev=$this->folder_to_regex($dev);
		
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#$dev#",$ligne)){
				writelogs_framework("\"$dev\"=$ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return true;
			}else{
				
			}
		}
	return false;}
	
	public function MOUNTED_PATH($dev){
		if(strpos($dev,"by-uuid")==0){
		$dev=$this->MapperDetect($dev);
		}
		$dev=$this->folder_to_regex($dev);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		$pattern="#^$dev\s+(.+?)\s+(btrfs|ext|rw|fusectl|rpc_pipefs|reiserfs|xfs)";
		if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__.":: $pattern\n";}
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$dev\s+(.+?)\s+(btrfs|ext|rw|fusectl|rpc_pipefs|reiserfs|xfs)#",$ligne,$re)){
				return trim($re[1]);	
			}
		}
	}
	
	public function MOUNTED_DIR($path){
		$path=$this->MapperDetect($path);
		$path=$this->folder_to_regex($path);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($datas) ){
			if(!preg_match("#^(.*?)\s+$path\s+(btrfs|ext|rw)#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: $ligne -> ^(.*?)\s+$path\s+(btrfs|ext|rw) NOT match ".__FUNCTION__."\n";}
				continue;
			}
			return trim($re[1]);
		}
	}	
	
	public function LSOF_PIDS($directory){
		$lsof=$this->find_program("lsof");
		exec("$lsof $directory 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^.+?\s+([0-9]+)#",$ligne,$re)){
				$pids[]=$re[1];
			}
		}
		return $pids;
	}
	
	
	public function MOUNTED_TMPFS_MEM($directory){
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^tmpfs\s+(.+?)\s+tmpfs\s+.+?size=([0-9]+)k\s+#",$ligne,$re)){
				$tmpfs[trim($re[1])]=round($re[2]/1024);
				if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
				continue;
			}
			if(preg_match("#^tmpfs\s+(.*?)\s+tmpfs\s+.*?,size=([0-9]+)k,#", $ligne,$re)){
				$tmpfs[trim($re[1])]=round($re[2]/1024);
				if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
				continue;				
			}
		}
		if($tmpfs[trim($directory)]==null){$tmpfs[trim($directory)]=0;}
		return $tmpfs[trim($directory)];
		
	}
	public function PROCESS_TIME_INT($pid){
		$sec=null;
		if($pid<5){return 0;}
		if($pid==null){return 0;}
		exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){continue;}
			$sec=$re[1];
			break;
				
		}
		
		if($sec==null){return 0;}	
		return time()-$sec;	
	}
	
	public function PROCCESS_TIME_MIN($pid,$killOnTTL=0){
		$sec=null;
		if($pid<5){return 0;}
		if($pid==null){return 0;}
		exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){continue;}
			$sec=$re[1];
			break;
			
		}
		
		if($sec==null){return 0;}
		
		
		if($sec>0){
			$sec=$sec/60;
			$return=intval(trim(round($sec)));
			if($killOnTTL>0){
				if($return>$killOnTTL){
					$kill=$this->find_program("kill");
					if($pid>5){ unix_system_kill_force($pid); }
					return 0;
				}
			}
			
			return $return;
		}
		return 0;
	}
	
	public function PROCESS_MEMORY($pid,$noMo=false){
		//$PPID=$this->PROCESS_PPID();
		//if($PPID>1)
		
		if(!is_file("/proc/$pid/status")){return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^VmRSS:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($noMo){return $re[1];}
				return round($re[1]/1024,2);
			}
		}
		
	}
	
	public function isMaxInstances(){
	
		$MaxInstnaces=11;
		$MaxInstancesToDie=16;
		$unix=new unix();
		$php5=$this->LOCATE_PHP5_BIN();
		$p=new processes_php();
		$MemoryInstances=$p->MemoryInstances();
		if(!is_numeric($MemoryInstances)){$MemoryInstances=0;}
		if($MemoryInstances>$MaxInstancesToDie){return true;}
		if($MemoryInstances>$MaxInstnaces){return true;}
		return false;
	
	}
	
	
	public function PROCESS_CACHE_MEMORY($pid,$noMo=false){
		//$PPID=$this->PROCESS_PPID();
		//if($PPID>1)
		
		if(!is_file("/proc/$pid/status")){return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^VmSize:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($noMo){return $re[1];}
				return round($re[1]/1024,2);
			}
		}
		
	}	
	
	private function PROCESS_PPID($pid){
		$pid=trim($pid);
		$pid=str_replace("\r", "", $pid);
		$pid=str_replace("\n", "", $pid);
		if(!is_file("/proc/$pid/status")){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: unable to stat `/proc/$pid/status`\n";}
		return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^PPid:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Found {$re[1]}\n";}
				if(trim($re[1])<50){return $pid;}
				return trim($re[1]);
			}
		}
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: not Found return $pid\n";}
		return $pid;
		
	}
	
	public function PROCESS_SOCKETS_NUM($pid){
		if(!is_dir("/proc/$pid/fd")){return 0;}
		$list = @glob("/proc/$pid/fd/*");
		$c=0;
		while (list ($index, $line) = each ($list)){
			if(!is_link($line)){continue;}
			$line =@readlink($line);
			if(!preg_match("#^socket:#", $line)){continue;}
			$c++;
			
		}
		
	}
	public function network_test_port($ip,$port){
	
		$fp = @stream_socket_client("tcp://$ip:$port",$errno, $errstr,3, STREAM_CLIENT_CONNECT);
		if($fp){@socket_close($fp);return true;}
		return false;
	
	
	
	}
	
	
	public function PROCESS_STATUS($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		if(!is_array($datas)){return array();}
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#State:\s+([A-Z])\s+#",$ligne,$re)){
				$STATUS=$re[1];
			}
			if(preg_match("#PPid:\s+([0-9]+)#",$ligne,$re)){
				$PPID=$re[1];
			}			
		}
		if($STATUS==null){return array();}
		return array($STATUS,$PPID);
	}
	
	
	public function SPAMASSASSIN_LOCAL_CF(){
		if(is_file("/etc/spamassassin/local.cf")){return('/etc/spamassassin/local.cf');}
		if(is_file("/etc/mail/spamassassin/local.cf")){return('/etc/mail/spamassassin/local.cf');}
		if(is_file("/opt/artica/etc/spamassassin/local.cf")){return('/opt/artica/etc/spamassassin/local.cf');}
	    @mkdir('/etc/spamassassin');
		shell_exec('/bin/touch /etc/spamassassin/local.cf');
		return '/etc/spamassassin/local.cf';
		}
		
		
	public function LOCATE_maillogconvert(){
		$f[]="/usr/local/awstats/tools/maillogconvert.pl";
		$f[]="/usr/bin/maillogconvert.pl";
		$f[]="/usr/share/awstats/maillogconvert.pl";
		$f[]="/usr/share/doc/awstats/examples/maillogconvert.pl";
		$f[]="/var/www/cgi-bin/awstats/maillogconvert.pl";
		$f[]="/usr/share/artica-postfix/bin/maillogconvert.pl";
		while (list ($num, $filename) = each ($f) ){if(is_file($filename)){return $filename;}}
	}
		
	public function get_pid_from_file($file){
		if(!is_file($file)){return;}
		$datas=trim(@file_get_contents($file));
		if(strpos($datas, "\n")>0){
			$tr=explode("\n",$datas);
			while (list ($num, $ligne) = each ($tr) ){
				if(preg_match("#([0-9]+)#",trim($ligne),$re)){return $re[1];}
			}
			
		}
		
		if(preg_match("#([0-9]+)#s",trim($datas),$re)){return $re[1];}
	}
	
	
	public function HostToIp($hostname){
		if(trim($hostname)==null){return;}
		$hostname=str_replace('$','',$hostname);
		$ip=gethostbyname($hostname);
		writelogs_framework("$hostname=$ip",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		if($ip<>$hostname){
			if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip)){return $ip;}
		}
		$nmblookup=$this->find_program("nmblookup");
		writelogs_framework("nmblookup=$nmblookup",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);

		if($nmblookup==null){return;}
		if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$hostname)){return;}
		$cmd="$nmblookup $hostname";
		exec($cmd,$results);
	
		while (list ($num, $ligne) = each ($results) ){
			writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#^([0-9\.]+)\s+#",$ligne,$re)){return $re[1];}
		}		
		
	}
	public function IpToHostname($ipaddr){
		$ipaddr=trim($ipaddr);
		if(!$this->isIPAddress($ipaddr)){return;}		
		if(isset($GLOBALS["IPNAMES"][$ipaddr])){return $GLOBALS["IPNAMES"][$ipaddr];}
	
		if(count($GLOBALS["IPNAMES"])>1500){$GLOBALS["IPNAMES"]=array();}
		$hostres=gethostbyaddr($ipaddr);
		if(!$this->isIPAddress($hostres)){
			$GLOBALS["IPNAMES"][$ipaddr]=$hostres;
			return $hostres;
		}
		$GLOBALS["IPNAMES"][$ipaddr]=$ipaddr;
		return $ipaddr;
	}	
	
	
public function IpToMac($ipaddr){
	$ipaddr=trim($ipaddr);
	
	if(!is_file("/usr/bin/arping")){return;}
	$ttl=date('YmdH');
	if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
	if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}

	$cmd="/usr/bin/arping -f -c 1 $ipaddr 2>&1";
	exec($cmd,$results);
	while (list ($num, $line) = each ($results)){
		if(!preg_match("#reply from.*?\[(.+?)\]#", $line,$re)){ continue; }
		$re[1]=trim(strtolower($re[1]));
		
		if($this->IsvalidMAC($re[1])){
			$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
			return;
		}else{
			$GLOBALS["CACHEARP"][$ttl][$ipaddr]="00:00:00:00:00:00";
			return "00:00:00:00:00:00";
		}

	}
}
	
	public static function isIPAddress( $ip ) {
		$ip=trim($ip);
		if($ip==null){return false;}
		if ( !$ip ) return false;
		if ( is_array( $ip ) ) {return false;}
		if(preg_match("#(.+?)\/([0-9]+)$#", $ip,$re)){
			$re[2]=trim($re[2]);
			if(strlen($re[2])>2){return false;}
			$ip=$re[1];
		}
		if(preg_match("#^".RE_IP_ADD."$#", $ip,$re)){return true;}
		if(preg_match("#^".RE_IPV6_ADD."$#", $ip,$re)){return true;}
		if(substr_count($ip, '::')>0){return false;}
		return false;
	}	
private static function IsvalidMAC($MAC){
		$MAC=str_replace(":", "-", $MAC);
		If(strlen($MAC) > 17) return false;
		If($MAC == "") return false;
		If (!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$MAC)) return false;
		$Array=explode("-",$MAC);
		If(strlen($Array[0]) != 2) return false;
		If(strlen($Array[1]) != 2) return false;
		If(strlen($Array[2]) != 2) return false;
		If(strlen($Array[3]) != 2) return false;
		If(strlen($Array[4]) != 2) return false;
		If(strlen($Array[5]) != 2) return false;
		return true;
	}	
	
	
	public function AddFSTab($dev,$mount){
		$hd=new hd($dev);
		$hd->AddFSTab($mount);
	}
	
	public function DelFSTab($dev){
		if($dev==null){return null;}
		 $datas=explode("\n",@file_get_contents("/etc/fstab"));
		 while (list ($num, $ligne) = each ($datas) ){
		 	if($ligne==null){continue;}
		 	if(preg_match("#(.+?)\s+(.+?)\s+#",$ligne)){
		 		if(trim(strtolower($dev))==trim(strtolower($re[1]))){
		 			shell_exec($this->find_program("umount")." -f $dev");
		 			unset($datas[$num]);
		 			@rmdir(trim($re[2]));
		 		}
		 	}
		 }
		 
	if(!file_put_contents("/etc/fstab",implode("\n",$datas))){
			writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}	 
	
	
	}
	
	public function GetFSTabMountPoint($dev){
	if($dev==null){return null;}
	$dev=$this->MapperDetect($dev);
	$array=array();
	$dev=str_replace("/","\/",$dev);
	$datas=explode("\n",@file_get_contents("/etc/fstab"));
	while (list ($num, $ligne) = each ($datas) ){
		 	if($ligne==null){continue;}
		 	if(preg_match("#$dev\s+(.+?)\s+#",$ligne,$re)){
		 			$array[]=trim($re[1]);
		 			continue;
		 		//	writelogs_framework("#$dev\s+(.+?)\s+# success => $ligne",__FUNCTION__,__FILE__,__LINE__);
		 		}else{
		 		//	writelogs_framework("#$dev\s+(.+?)\s+# failed => $ligne",__FUNCTION__,__FILE__,__LINE__);
		 		}
		 	}
	
	return $array;
	
	}
	
	
	function SendEmailConfigured($arrayParams,$subject=null,$content=null){
		include_once('/usr/share/artica-postfix/ressources/class.mail.inc');
		include_once('/usr/share/artica-postfix/ressources/smtp/class.phpmailer.inc');
	
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
					
			}
				
		}
		if($subject==null){$subject="No subject...";}
		$smtp_dest=$arrayParams["smtp_dest"];
		if($smtp_dest==null){echo "No recipient\n";return false;}
		if(trim($arrayParams["smtp_server_name"])==null){echo "No valid SMTP server\n";return false;}
	
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$content=str_replace("\n", "\r\n", $content);
		$content=$content."#---------------------\r\nCurrent load:$internal_load\r\nFrom: $file in line $line function $function()\r\n";
	
	
		$hostname=$this->hostname_g();
		$addresses=array();
		if(strpos($smtp_dest, ";")>0){
			$destZ=explode(";",$smtp_dest);
			while (list ($num, $email) = each ($destZ) ){
				if(preg_match("#^(.+?)@([a-z0-9\.\-\_]+)$#", $email,$re)){$addresses[]="{$re[1]}@{$re[2]}";}
			}
		}
	
	
		if(strpos($smtp_dest, ",")>0){
			$destZ=explode(",",$smtp_dest);
			while (list ($num, $email) = each ($destZ) ){
				if(preg_match("#^(.+?)@([a-z0-9\.\-\_]+)$#", $email,$re)){$addresses[]="{$re[1]}@{$re[2]}";}
			}
	
		}
	
	
		if(count($addresses)==0){$addresses[]=$smtp_dest;}
		if(!is_numeric($arrayParams["smtp_server_port"])){$arrayParams["smtp_server_port"]=25;}
		$smtp_sender=$arrayParams["smtp_sender"];
		if($smtp_sender==null){$smtp_sender="root@$hostname";}
	
		$results=true;
		$subject="[$hostname]: $subject";
		while (list ($num, $DESTINATAIRE) = each ($addresses) ){
			$mail = new PHPMailer(true);
			$mail->IsSMTP();
			$mail->AddAddress($DESTINATAIRE,$DESTINATAIRE);
			$mail->AddReplyTo($smtp_sender,$smtp_sender);
			$mail->From=$smtp_sender;
			$mail->FromName=$smtp_sender;
			
			$mail->Subject=$subject;
			$mail->Body=$content;
			$mail->Hostname=$hostname;
			$mail->Host=$arrayParams["smtp_server_name"];
			$mail->Port=$arrayParams["smtp_server_port"];
	
			if(($arrayParams["smtp_auth_user"]<>null) && ($arrayParams["smtp_auth_passwd"]<>null)){
				$mail->SMTPAuth=true;
				$mail->Username=$arrayParams["smtp_auth_user"];
				$mail->Password=$arrayParams["smtp_auth_passwd"];
				if($arrayParams["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
				if($arrayParams["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
			}
	
			if(!$mail->Send()){$results=false;continue;}
	
		}
		return $results;
	
	}	
	
	public function TOTAL_MEMORY_MB(){
		if(isset($GLOBALS["TOTAL_MEMORY_MB"])){return $GLOBALS["TOTAL_MEMORY_MB"];}
		$array=$this->TOTAL_MEMORY_KB_WORK();
		$Total=$array[0];
		$GLOBALS["TOTAL_MEMORY_MB"]=round($Total/1024);
		return $GLOBALS["TOTAL_MEMORY_MB"];
		
	}
	
	public function TOTAL_MEM_POURCENT_USED(){
		
		
		$array=$this->TOTAL_MEMORY_KB_WORK();
		$Total=$array[0];
		$Used=$array[1];
		$prc=$Used/$Total;
		$prc=round($prc*100);
		return $prc;
	}
	
	
	
	private function TOTAL_MEMORY_KB_WORK(){
		exec("/usr/share/artica-postfix/ressources/mem.pl 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#T=([0-9]+)\s+U=([0-9]+)#",$ligne,$re)){continue;}
			$Total=$re[1];
			$Used=$re[2];
			return array(intval($Total),intval($Used));
		}
		
	}
	

	public function TOTAL_MEMORY_MB_USED(){
		
		$array=$this->TOTAL_MEMORY_KB_WORK();
		$Total=$array[0];
		$Used=$array[1]/1024;

		return round($Used);
		
	}
	public function TOTAL_MEMORY_MB_FREE(){
		$array=$this->TOTAL_MEMORY_KB_WORK();
		$Total=$array[0];
		$Used=$array[1];
		$free=$Total-$Used;
		return round($free/1024);
	
	}
	
	
	public function DIRECTORY_USEPERCENT($dir){
	
		if(is_link($dir)){$dir=readlink($dir);}
		if(!is_dir($dir)){
			$directory_tmp=dirname($dir);
			if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
			if(is_dir($directory_tmp)){$dir=$directory_tmp;}
		}
	
		$df=$this->find_program("df");
		exec("$df \"$dir\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#^\/.*?[0-9]+\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9\.,]+)%#", $ligne,$re)){
				if(!preg_match("#^\/.*?[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9\.,]+)%\s+#", $ligne,$re)){
					if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
					continue;
				}else{
					return round($re[1]);
				}
			}
			
				return round($re[1]);
				
		}
	
	}
	
	function TMPFS_USEPERCENT($dir){
		if(is_link($dir)){$dir=readlink($dir);}
		if(!is_dir($dir)){
			$directory_tmp=dirname($dir);
			if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
			if(is_dir($directory_tmp)){$dir=$directory_tmp;}
		}
		
		$df=$this->find_program("df");
		exec("$df \"$dir\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#^tmpfs\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9,\.])+%\s+#", $ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
				continue;
			}
				
			return round($re[4]);
		
		}
		
	}
	
	function TMPFS_CURRENTSIZE($dir){
		
		$df=$this->find_program("df");
		exec("$df \"$dir\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#^(tmpfs|shm)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9,\.])+%\s+#", $ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
				continue;
			}
	
			return round($re[2]);
	
		}
	
	}	

	
	function DIRECTORY_MountedOnDirAndDismount($Directory){
		
		$c=0;
		$umount=$this->find_program("umount");
		$DirectoryEx=str_replace("/", '\/', $Directory);
		$DirectoryEx=str_replace(".", "\.", $DirectoryEx);
	
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($a, $line) = each ($f)){
			if(preg_match("#.+?\s+$DirectoryEx\/(.+?)\s+#", $line,$re)){
				shell_exec("$umount -l $Directory/{$re[1]}");
				$c++;
			}
		}
	
		return $c;
	
	}
	
	public function DIRECTORY_FREEM($dir){
		
		if(is_link($dir)){$dir=readlink($dir);}
		if(!is_dir($dir)){
			$directory_tmp=dirname($dir);
			if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
			if(is_dir($directory_tmp)){$dir=$directory_tmp;}
		}
		
		$df=$this->find_program("df");
		exec("$df -B MB $dir",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^\/.*?[0-9]+MB\s+[0-9]+MB\s+([0-9]+)MB\s+#", $ligne,$re)){return $re[1];}
			
		}
		
	}
	public function DIRECTORY_FREE_FILES($dir){
		$df=$this->find_program("df");
		exec("df -i $dev 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array["INODES_MAX"]=$re[1];
				$array["INODES_USED"]=$re[2];
				$array["INODES_AVAILABLE"]=$re[3];
				$array["INODES_POURC"]=$re[4];
				break;
			}
		}
	
		return $array["INODES_AVAILABLE"];
	
	}	

	public function DF_SATUS_K($dir){
		$df=$this->find_program("df");
		//1K-blocks      Used Available Use%
		exec("$df -B K $dir 2>&1",$results);
		$pattern="#^\/.*?([0-9]+)K\s+([0-9]+)K\s+([0-9]+)K\s+([0-9\.]+)%#";
		if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $df -B K $dir 2>&1 = ".count($results)." rows\n";}
		while (list ($num, $ligne) = each ($results) ){
			
		if(preg_match("#^Filesystem#", $ligne)){continue;}
			
		if(!preg_match("$pattern", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $ligne -> No match $pattern\n";}
			continue;}
			return array(
						"SIZE"=>$re[1],
						"USED"=>$re[2],
						"AIVA"=>$re[3],
						"POURC"=>$re[4]
				);
		}
	
	}	
	
	
	public function process_number_me($argv=array()){
		$cmdlines=null;
		if(count($argv)==0){return;}
		while (list ($num, $ligne) = each ($argv) ){
			if($num==0){
				$myScript=basename($ligne);
				continue;
			}
			if($ligne=="--verbose"){continue;}
			if($ligne=="--force"){continue;}
			if($ligne=="--simulate"){continue;}
			if($ligne=="--notime"){continue;}
			if(preg_match("#schedule-id#", $ligne)){continue;}
			$t[]=$ligne;
		}
		
		if(count($t)>0){$cmdlines=".*?".@implode($t, ".*?");}
		$miPid=getmypid();
		
		
		if($GLOBALS["VERBOSE"]){
			//$miPidCmd=@file_get_contents("/proc/$miPid/cmdline");
			//echo "[$miPid]: myScript: $myScript$cmdlines\n[$miPid]: $miPidCmd\n";
		}
		$pgrep=$this->find_program("pgrep");
		$c=0;
		$f=array();
		exec("$pgrep -l -f \"$myScript$cmdlines\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#pgrep#", $ligne)){continue;}
			if(!preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){continue;}
			If(preg_match("#(^|\s+|\/)sh\s+-c#", $ligne)){continue;}
			//if($GLOBALS["VERBOSE"]){echo "[$miPid]: {$re[2]}\n";}
			$pid=$re[1];
			if($pid==$miPid){if($GLOBALS["VERBOSE"]){echo "[$miPid]: $pid==$miPid OK\n";}continue;}
			$ppid=$this->PROCESS_PPID($pid);
			/*if($GLOBALS["VERBOSE"]){
				$miPidCmd=@file_get_contents("/proc/$ppid/cmdline");
				echo "[$pid]: PPID: $ppid $miPidCmd\n";}
			if($GLOBALS["VERBOSE"]){echo "[$pid]: $pid ++Z\n";}
			*/
			$f[]=$pid;
			$c++;
		}
		
		return count($f);
		
	}
	
	
	public function process_exists($pid,$filename=null){
		$GLOBALS["PROCES_EXISTS_DEBUG"]=array();
		$pid=trim($pid);
		$pid=trim($pid);
		$pid=str_replace("\r", "", $pid);
		$pid=str_replace("\n", "", $pid);	
		$status=array();
		$filename=trim($filename);
		$cmdline=null;
		$pid=intval($pid);

		if($pid==0){return false;}
		
		
		if($pid<5){
			if($GLOBALS["VERBOSE"]){echo "$pid < 5 (strange ? aborting)...\n";}
			$GLOBALS["PROCES_EXISTS_DEBUG"][]="$pid < 5 (strange ? aborting)...";
			return false;
		}
		
		
		
		if(is_file("/proc/$pid/exe")){
			$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
			$GLOBALS["PROCES_EXISTS_DEBUG"][]="PROC: $pid exec:OK";
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/status exists cmdline=`$cmdline` Line:".__LINE__."\n";}
			if(preg_match("#'-bash'#",$cmdline)){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
				return false;
			}
			
			
			if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}
			
			$status=$this->PROCESS_STATUS($pid);
			if($filename<>null){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: `$filename`, PROCESS_STATUS:$status '{$status[0]}' Line:".__LINE__."\n";}
			}
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) PROCESS_STATUS: '{$status[0]}' Line:".__LINE__."\n";} 
			if($status[0]=="Z"){
				
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -> Z = SOMBIE ::: Line:".__LINE__."\n";}
				return false;}
			if($filename<>null){
				if($cmdline==null){
					if(is_file("/proc/$pid/cmdline")){
						$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
					}
				}
				$filename=str_replace(".", "\.", $filename);
				$filename=str_replace("/", "\/", $filename);
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: $cmdline Line:".__LINE__."\n";}
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# in \"$cmdline\" Line:".__LINE__."\n";}
				if(preg_match("#$filename#",$cmdline)){if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# SUCCESS Line:".__LINE__."\n";}return true;}else{if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# FAILED Line:".__LINE__."\n";}return false;}
			}
			
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->RUNNING Line:".__LINE__."\n";}
			return true;
		}else{
			$GLOBALS["PROCES_EXISTS_DEBUG"][]="missing /proc/$pid/exe";
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/exe no such file Line:".__LINE__."\n";}
		}
		
		if(is_file("/proc/$pid/cmdline")){
			if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}
			$status=$this->PROCESS_STATUS($pid);
			
			if($status[0]=="Z"){
				$GLOBALS["PROCES_EXISTS_DEBUG"][]="Marked as ZOMBIE";
				return false;
			}			
			
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: $filename Line:".__LINE__."\n";}	
			$cmdline=@file_get_contents("/proc/$pid/cmdline");
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: `$cmdline` Line:".__LINE__."\n";}
			
			if(preg_match("#-bash$#",$cmdline)){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
				$GLOBALS["PROCES_EXISTS_DEBUG"][]="Macthes as -bash";
				return false;				
			}
			$filename=str_replace("/", '\/', $filename);
			if(preg_match("#$filename#i",$cmdline)){
					if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->matches $filename -> RUNNING Line:".__LINE__."\n";}
					return true;
			}else{
				$GLOBALS["PROCES_EXISTS_DEBUG"][]="$filename no match";
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> NO matches $filename -> STOPPED Line:".__LINE__."\n";}
				return false;
			}
			return true;
		}else{
			$GLOBALS["PROCES_EXISTS_DEBUG"][]="/proc/$pid/cmdline no such file ".__LINE__;
		}
		
		$GLOBALS["PROCES_EXISTS_DEBUG"][]="Return false: ".__LINE__;
		return false;
	}
	public function file_time_set($filename){
		if(!is_dir("/etc/artica-postfix/pids")){@mkdir("/etc/artica-postfix/pids",0755,true);}
		@unlink("/etc/artica-postfix/pids/$filename");
		@file_put_contents("/etc/artica-postfix/pids/$filename", time());
	}
	
	public function file_time_get($filename){
		return $this->file_time_min("/etc/artica-postfix/pids/$filename");
	}
	
	public function dir_time_min($path){
		$last_modified=0;
	
		if(!is_dir($path)){return 1;}
		$data1 = filemtime($path."/.");
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
	}	
	
	
	public function time_min($firstTime){
		$data1 = $firstTime;
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
		
	}
	
	
	public function file_time_min($path){
		$last_modified=0;
	
		if(is_dir($path)){return 10000;}
		if(!is_file($path)){return 100000;}
			
		$data1 = filemtime($path);
		$data2 = time();
		$difference = ($data2 - $data1); 	 
		$results=intval(round($difference/60));	 
		if($results<0){$results=1;}
		return $results;
	}
		
	public function file_time_sec($path){
		if(!is_dir($path)){
			if(!is_file($path)){return 100000;}
			}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return $difference;
		}
		
		
	public function file_chmoded($path){
		$array=$this->alt_stat2($path);
		if(isset($array["perms"]["octal1"])){
			return $array["perms"]["octal1"];
		}
		}		

		
	public function alt_stat2($file) {
		 
		 clearstatcache();
		 $ss=@stat($file);
		 if(!$ss) return false; //Couldnt stat file
		 
		 $ts=array(
		  0140000=>'ssocket',
		  0120000=>'llink',
		  0100000=>'-file',
		  0060000=>'bblock',
		  0040000=>'ddir',
		  0020000=>'cchar',
		  0010000=>'pfifo'
		 );
		 
		 $p=$ss['mode'];
		 $t=decoct($ss['mode'] & 0170000); // File Encoding Bit
		 
		 $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
		 $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
		 $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
		 $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
		 $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
		 $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
		 $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
		 
		 $s=array(
		 'perms'=>array(
		  'umask'=>sprintf("%04o",@umask()),
		  'human'=>$str,
		  'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
		  'octal2'=>sprintf("0%o", 0777 & $p),
		  'decimal'=>sprintf("%04o", $p),
		  'fileperms'=>@fileperms($file),
		  'mode1'=>$p,
		  'mode2'=>$ss['mode']),
		 
		 'owner'=>array(
		  'fileowner'=>$ss['uid'],
		  'filegroup'=>$ss['gid'],
		  'owner'=>
		  (function_exists('posix_getpwuid'))?
		  @posix_getpwuid($ss['uid']):'',
		  'group'=>
		  (function_exists('posix_getgrgid'))?
		  @posix_getgrgid($ss['gid']):''
		  ),
		 
		 'file'=>array(
		  'filename'=>$file,
		  'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
		  'dirname'=>@dirname($file),
		  'basename'=>@basename($file)
		  ),
		
		 'filetype'=>array(
		  'type'=>substr($ts[octdec($t)],1),
		  'type_octal'=>sprintf("%07o", octdec($t)),
		  'is_file'=>@is_file($file),
		  'is_dir'=>@is_dir($file),
		  'is_link'=>@is_link($file),
		  'is_readable'=> @is_readable($file),
		  'is_writable'=> @is_writable($file)
		  ),
		 
		 'device'=>array(
		  'device'=>$ss['dev'], //Device
		  'device_number'=>$ss['rdev'], //Device number, if device.
		  'inode'=>$ss['ino'], //File serial number
		  'link_count'=>$ss['nlink'], //link count
		  'link_to'=>($s['type']=='link') ? @readlink($file) : ''
		  ),
		 
		 'size'=>array(
		  'size'=>$ss['size'], //Size of file, in bytes.
		  'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
		  'block_size'=> $ss['blksize'] //Optimal block size for I/O.
		  ),
		 
		 'time'=>array(
		  'mtime'=>$ss['mtime'], //Time of last modification
		  'atime'=>$ss['atime'], //Time of last access.
		  'ctime'=>$ss['ctime'], //Time of last status change
		  'accessed'=>@date('Y M D H:i:s',$ss['atime']),
		  'modified'=>@date('Y M D H:i:s',$ss['mtime']),
		  'created'=>@date('Y M D H:i:s',$ss['ctime'])
		  ),
		 );
		 
		 clearstatcache();
		 return $s;
		}		

	PUBLIC function LOCATE_SENDMAIL_PATH(){
		$path=$this->find_program("sendmail");
		if(is_file($path)){return $path;}
		if(is_file("/usr/lib/sendmail")){return "/usr/lib/sendmail";}	
		
	}
	
	public function Ufdbguard_remote_srvc_bool(){
		if(!is_file("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")){return false;}
		$CONTENT=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig"));
		if($CONTENT<>null){ $datas=unserialize($CONTENT);	}
		if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
		if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
		if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
		if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
		if($datas["UseRemoteUfdbguardService"]==1){return true;}	
		return false;
	}	
	
	
	public function LOCATE_APACHE_BIN_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){
			if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		}
		$f=$this->find_program("apache2");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}
		$f=$this->find_program("apache");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}
		
		$f=$this->find_program("httpd2");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}				
		
		$f=$this->find_program("httpd");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}	
		
		if(is_file('/usr/local/obm-apache/bin/obm-apache')){return '/usr/local/obm-apache/bin/obm-apache';}
		if(is_file('/usr/local/apache-groupware/bin/apache-groupware')){return '/usr/local/apache-groupware/bin/apache-groupware';} 		
	}
	
	public function  SQUID_INSTALLED(){
		if(is_file($this->LOCATE_SQUID_BIN())){return true;}
		return false;
	}
	
	public function LOCATE_SQUID_BIN(){
		if(isset($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];}
		$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$this->find_program("squid3");
		if(!is_file($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$this->find_program("squid");}
		return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];
		
	}
	
	
	public function ClamUser(){
		$ClamUser="clamav";
		$squidbin=$this->LOCATE_SQUID_BIN();
		
		if(is_file($squidbin)){
			$SQUIDEnable=$this->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($SQUIDEnable==1){$ClamUser="squid";}
		}
		
		$postconf=$this->find_program("postconf");
		if(is_file($postconf)){
			if(!is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){
				$ClamUser="postfix";
			}
		}
		return $ClamUser;
	}
	
	
	public function SQUID_PID(){
		

			$squidbin=$this->LOCATE_SQUID_BIN();
			$pidfile=$this->LOCATE_SQUID_PID();
			$pid=$this->get_pid_from_file($pidfile);
			if($GLOBALS["VERBOSE"]){echo "PID FILE: $pidfile - > $pid\n";}
		
			if(!$this->process_exists($pid)){
				if($GLOBALS["VERBOSE"]){echo "PID: $pid - > NOT RUNNING !! -> PIDOF $squidbin\n";}
				$pid=$this->PIDOF($squidbin);
				if($GLOBALS["VERBOSE"]){echo "PIDOF: $pid\n";}
			}else{
				return $pid;
			}
		
			if($GLOBALS["VERBOSE"]){
				if(!$this->process_exists($pid)){
					echo " **** ***** ***** ***** $pid] NOT RUNNING !! \n";
				}
			}
			return $pid;
		
		
	}
	
	
	
	
	
public function readlastline($filename,$rows_number=1) { 
		$tail=$this->find_program("tail");
		exec("$tail -n $rows_number \"$filename\" 2>&1",$RESULTS);
		return $RESULTS;
		
		
} 
public function readFirstline($filename,$rows_number=1) {
	$head=$this->find_program("head");
	exec("$head -n $rows_number \"$filename\" 2>&1",$RESULTS);
	return $RESULTS;


}	
	private function APACHE_DEFAULT_PIDLOG(){
		if(isset($GLOBALS["APACHE_DEFAULT_PIDLOG"])){return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
		$bin=$this->LOCATE_APACHE_BIN_PATH();
		if(strlen($bin)<3){
			if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
			return;
		}
		
		exec("$bin -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$GLOBALS["APACHE_DEFAULT_PIDLOG"]=$re[1];return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
		}
		
	}
	
	public function LOCATE_APACHE_CONF_PATH(){
		if(isset($GLOBALS["LOCATE_APACHE_CONF_PATH"])){return $GLOBALS["LOCATE_APACHE_CONF_PATH"];}
		$GLOBALS["LOCATE_APACHE_CONF_PATH"]=$this->_LOCATE_APACHE_CONF_PATH();
		return $GLOBALS["LOCATE_APACHE_CONF_PATH"];
	}
	
	private function _LOCATE_APACHE_CONF_PATH(){
		$bin=$this->LOCATE_APACHE_BIN_PATH();
		if(strlen($bin)<3){
			if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
			return;
		}
		
		
		exec("$bin -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){$r["HTTPD_ROOT"]=$re[1];}
			if(preg_match('#SERVER_CONFIG_FILE="(.+?)"#',$ligne,$re)){$r["SERVER_CONFIG_FILE"]=$re[1];}
		}
		
		chdir("/root");
		if(is_file($r["SERVER_CONFIG_FILE"])){
			$GLOBALS[__FUNCTION__]=$r["SERVER_CONFIG_FILE"];
			return $GLOBALS[__FUNCTION__];
		}
			
		if($GLOBALS["VERBOSE"]){echo "UNIX/LOCATE_APACHE_CONF_PATH -> \"{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}\"\n";}
		
		if(file_exists("{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}")){
			if(isset($r["HTTPD_ROOT"])){$GLOBALS[__FUNCTION__]="{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}";}
			if(isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=str_replace("//","/",$GLOBALS[__FUNCTION__]);}
		}
		if($GLOBALS[__FUNCTION__]=="conf/httpd.conf"){$GLOBALS[__FUNCTION__]="/etc/httpd/conf/httpd.conf";}
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		
		
	}
	
	public function LOCATE_APACHE_PID_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
		$bin_path=$this->LOCATE_APACHE_BIN_PATH();
		exec("$bin_path -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){
				$httpd_root=$re[1];
			}
			
			if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$DEFAULT_PIDLOG=$re[1];}
		}
		
		if(!is_file($DEFAULT_PIDLOG)){
			if(is_file("$httpd_root/$DEFAULT_PIDLOG")){$DEFAULT_PIDLOG="$httpd_root/$DEFAULT_PIDLOG";}
		}
		$GLOBALS[__FUNCTION__]=$DEFAULT_PIDLOG;
		return $GLOBALS[__FUNCTION__];
		
	}
	
	public function LOCATE_APACHE_VERSION(){
		if(isset($GLOBALS[__FUNCTION__])){
			if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		}
		
		$bin_path=$this->LOCATE_APACHE_BIN_PATH();
		$md5=md5_file($bin_path);
		
		if(is_file("/etc/artica-postfix/apache2_version")){
			$array=unserialize(@file_get_contents("/etc/artica-postfix/apache2_version"));
			if($array["MD5"]==$md5){
				return $array["VERSION"];
			}
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "LOCATE_APACHE_VERSION : \"$bin_path\"\n";}
		exec("$bin_path -v 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#^Server version:.+?([0-9\.]+)#',$ligne,$re)){
				$GLOBALS[__FUNCTION__]=$re[1];
				$array["MD5"]=$md5;
				$array["VERSION"]=$GLOBALS[__FUNCTION__];
				@file_put_contents("/etc/artica-postfix/apache2_version", serialize($array));
				return $GLOBALS[__FUNCTION__];
			}
			if($GLOBALS["VERBOSE"]){echo "NO MATCH:: \"^Server version:.+?([0-9\.]+)\" -> \"$ligne\"\n";}
		}
		
	}	
	

	public function IMAPD_GET($key){
		if(!isset($GLOBALS["IMAPD_GET"])){
			$GLOBALS["IMAPD_GET"]=@file_get_contents("/etc/imapd.conf");
		}
		$datas=explode("\n",$GLOBALS["IMAPD_GET"]);
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$key:(.+)#",$ligne,$re)){
				return trim($re[1]);
			}
		}
	}
	
public function ServicesCMDArray(){
		$f[]="ldap";
		$f[]="saslauthd";
		$f[]="imap";
		$f[]="kav6";
		$f[]="squid";
		$f[]="dansgardian";
		$f[]="boa";
		$f[]="ftp";
		$f[]="mysql";
		$f[]="apache";
		$f[]="spamd";
		$f[]="clamd";
		$f[]="freshclam";
		$f[]="mgreylist";
		$f[]="daemon";
		$f[]="clammilter";
		$f[]="postfix";
		$f[]="dnsmasq";
		$f[]="stunnel";
		$f[]="postfix";
		$f[]="mailgraph";
		$f[]="mimedefang";
		$f[]="roundcube";
		$f[]="kav4samba";
		$f[]="bind9";
		$f[]="obm";
		$f[]="yorel";
		$f[]="p3scan";
		$f[]="syslogng";
		$f[]="mailarchive";
		$f[]="bogom";
		$f[]="collectd";
		$f[]="mysql";
		$f[]="fetchmail";
		$f[]="mailspy";
		$f[]="amavis";
		$f[]="retranslator";
		$f[]="spfmilter";
		$f[]="dotclear";
		$f[]="jcheckmail";
		$f[]="mailman";
		$f[]="kas3";
		$f[]="dhcp";
		$f[]="cicap";
		$f[]="openvpn";
		
		$f[]="dansguardian-tail";
		$f[]="apache-groupware";
		$f[]="cups";
		$f[]="dstat";
		$f[]="dstat-top-mem";
		$f[]="dstat-top-cpu";
		$f[]="rsync";
		$f[]="policydw";
		$f[]="autofs";
		$f[]="mysql-cluster";
		$f[]="assp";
		$f[]="pdns";
		$f[]="gluster";
		$f[]="gluster-cli";
		$f[]="zabbix";
		$f[]="smartd";
		return $f;	
}


public function monit_array(){
	$monit=new monit_unix();
	return $monit->all_status();

	
}
	
	
public function IsProtectedDirectory($folder,$nopreg=false){
	if($folder==null){return true;}
	if($folder=="/"){return true;}
	$l["/cdrom"]=true;
	$l["/sys"]=true;
	$l["/tmp"]=true;
	$l["/initrd"]=true;
	$l["/dev"]=true;
	$l["/etc"]=true;
	$l["/boot"]=true;
	$l["/var/lib"]=true;
	$l["/sbin"]=true;
	$l["/lib"]=true;
	$l["/bin"]=true;
	$l["/usr/libexec"]=true;
	$l["/usr/sbin"]=true;
	$l["/usr/bin"]=true;	
	$l["/usr/include"]=true;	
	$l["/usr/local"]=true;	
	$l["/usr/src"]=true;		
	$l["/usr/usr"]=true;
	$l["/usr/X11R6"]=true;
	$l["/usr/lib"]=true;
	$l["/usr/lib64"]=true;
	$l["/usr/share/artica-postfix"]=true;
	$l["/usr/src"]=true;	
	$l["/srv"]=true;
	$l["/var/log"]=true;
	$l["/var/db"]=true;
	$l["/var/lib"]=true;
	$l["/var/lock"]=true;
	$l["/var/mail"]=true;
	$l["/var/milter-greylist"]=true;
	$l["/var/spool"]=true;
	$l["/var/opt"]=true;
	$l["/usr/share"]=true;
	$l["/opt"]=true;
	$l["/var/cache"]=true;
	$l["/var/local"]=true;
	$l["/var/run"]=true;
	$l["/var/tmp"]=true;
	$l["/var/webmin"]=true;
	$l["/lost+found"]=true;
	
	if($nopreg){
		$l["/home"]=true;
		if(isset($l[$folder])){return true;}
		return false;
	}
	
	while (list ($num, $ligne) = each ($l) ){
		$p=str_replace("/","\/",$num);
		$p=str_replace(".","\.",$p);
		if(preg_match("#$p#",$folder)){
			writelogs("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			writelogs_framework("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return true;}
	}
}

	
	public function LOCATE_HEAD(){
		return $this->find_program("head");
		$this->events("Unable to stat head !");
		
	}

	public function LOCATE_DB_RECOVER(){
		$file=$this->find_program("db4.10_recover");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_recover");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.8_recover");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_recover");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_recover");
		if(strlen($file)>3){return $file;}	
		$file=$this->find_program("db4.5_recover");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_recover");
		if(strlen($file)>3){return $file;}

		
	}
	
	public function SLAPD_INITD_PATH(){
	   if(is_file('/etc/init.d/ldap')){return '/etc/init.d/ldap';}
	   if(is_file('/etc/init.d/slapd')){return '/etc/init.d/slapd';}
	   if(is_file('/usr/local/etc/rc.d/slapd')){return '/usr/local/etc/rc.d/slapd';}
	   return "/etc/init.d/slapd";
	}	
	
	public function LOCATE_DB_ARCHIVE(){
		$file=$this->find_program("db4.10_archive");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_archive");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.8_archive");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_archive");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_archive");
		if(strlen($file)>3){return $file;}	
		$file=$this->find_program("db4.5_archive");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_archive");
		if(strlen($file)>3){return $file;}

		
	}	

	public function LOCATE_DB_STAT(){
		$file=$this->find_program("db4.10_stat");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_stat");
		if(strlen($file)>3){return $file;}				
		$file=$this->find_program("db4.8_stat");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_stat");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_stat");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.5_stat");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_stat");
		if(strlen($file)>3){return $file;}

		
	}		

	
	
	public function LOCATE_SYSLOG_PATH(){
		if(!is_file("/etc/artica-postfix/settings/Daemons/syslog_path")){shell_exec("/usr/share/artica-postfix/bin/artica-install --write-syslog");}
		if(is_file("/etc/artica-postfix/settings/Daemons/syslog_path")){
			$datas=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/syslog_path"));
			if(!is_file($datas)){$datas="/var/log/syslog";}
			
		}
		
		return $datas;
		
	}
	
	public function LOCATE_DU(){
		return $this->find_program("du");
		$this->events("Unable to stat du !");
		
	}
	
	
	public function TEMP_DIR(){
		$SysTmpDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/SysTmpDir");
		if($SysTmpDir==null){$SysTmpDir="/home/artica/tmp";}
		if($SysTmpDir=="/tmp"){if(function_exists("sys_get_temp_dir")){$SysTmpDir=sys_get_temp_dir();}}
		if(!is_dir($SysTmpDir)){@mkdir($SysTmpDir,0777,true);}
		return $SysTmpDir;
	}

	public function FILE_TEMP(){
		
		
		if(function_exists("tempnam")){
			return tempnam($this->TEMP_DIR(), 'artica-php');
		}
		
		
		return $this->TEMP_DIR()."/artica-php-".md5(date("Y-m-d H:i:s"));
		
	}
	
	public function LINUX_CODE_NAME(){
		$f=@file_get_contents("/etc/artica-postfix/settings/Daemons/LinuxDistributionCodeName");
		if(trim($f)<>null){return trim($f);}
		$distriname=$this->LINUX_DISTRIBUTION();
		if(preg_match("#Ubuntu#i", $distriname)){return "UBUNTU";}
		if(preg_match("#debian#i", $distriname)){return "DEBIAN";}
		if(preg_match("#suse#i", $distriname)){return "SUSE";}
		if(preg_match("#Fedora#i", $distriname)){return "FEDORA";}
		if(preg_match("#CentOS#i", $distriname)){return "CENTOS";}
		
		
	}
	
public	function BLKID_ARRAY(){
		$unix=new unix();
		$unix->find_program("blkid");
		$df=$unix->find_program("df");
		exec("blkid 2>&1",$results);
		$udevinfo=$this->udevinfo_disks();

		
		while (list ($index, $line) = each ($results) ){
			if(!preg_match("#^(.+?):(.+)#", $line,$re)){continue;}
			$DEV=$re[1];
			$line=$re[2];
			$t=explode(" ",$line);
			while (list ($a, $b) = each ($t) ){
				if(!preg_match("#([A-Z\_]+)=\"(.+?)\"#", $b,$re)){continue;}
				$ARRAY[$DEV][trim($re[1])]=trim($re[2]);
			}
			$ARRAY[$DEV]["PATH"]=$DEV;
			
			if(isset($udevinfo[$DEV])){
				while (list ($a, $b) = each ($udevinfo[$DEV]) ){
					$ARRAY[$DEV][$a]=$b;
				}
			}
	
	
	
		}
		
		
		while (list ($dev, $sif) = each ($ARRAY) ){
			if(isset($sif["UUID"])){
				$UUIDZ[$sif["UUID"]]=$dev;
			}
		}
		
		
		
		exec("$df -h 2>&1",$results2);
		while (list ($index, $line) = each ($results2) ){
			if(preg_match("#(.+?)\s+(.+?)\s+#", $line,$re)){
				$DEV=$re[1];
				if(preg_match("#by-uuid\/(.+)#", $DEV,$ri)){
					$UUID=$ri[1];
					$DEV=$UUIDZ[$UUID];
				}
				
				
				$ARRAY[$DEV]["SIZE"]=$re[2];
			}
			
		}
		
		return $ARRAY;
	
	}
	
	private function udevinfo_disks_query_all($dev){
		if(is_file('/usr/bin/udevinfo')){
			$cmd="/usr/bin/udevinfo -a -p `/usr/bin/udevinfo -q path -n $dev`";
		}
		$ARRAY=array();
		
		if(is_file('/sbin/udevadm')){
			$cmd="/sbin/udevadm info --query=all --path=`/sbin/udevadm info --query=path --name=$dev`";
		}
	
		exec("$cmd 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#E:\s+([A-Z\_]+)=(.+)#", $line,$re)){
				$ARRAY[$re[1]]=trim($re[2]);
			}
		}
		
		return $ARRAY;
		
	}
	

	private function udevinfo_disks(){
	
	
		if(is_file('/usr/bin/udevinfo')){$cmd='/usr/bin/udevinfo -e';}
		if(is_file('/sbin/udevadm')){$cmd='/sbin/udevadm info -e';}
	
		exec("$cmd 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#P:\s+(.+)$#", $line,$re)){$DEV=$re[1];continue;}
			if(preg_match("#E:\s+([A-Z\_]+)=(.+)#", $line,$re)){
				$ARRAY[$DEV][$re[1]]=trim($re[2]);
			}
	
		}
		
		
		
		while (list ($dev, $ar) = each ($ARRAY) ){
			
			if(!isset($ar["DEVNAME"])){
					//if($GLOBALS["VERBOSE"]){echo "$dev -> NO DEVNAME\n";}
					continue;
			}
			
			if(isset($ar["DEVTYPE"])){
			$NewArray=$this->udevinfo_disks_query_all($ar["DEVNAME"]);
				
				while (list ($a, $b) = each ($NewArray) ){
					$ARRAY[$dev][$a]=$b;
				}
				

				while (list ($key, $val) = each ($ARRAY[$dev]) ){
					$NEWARRAY[$ar["DEVNAME"]][$key]=$val;
					
				}
				
			}

	
			}
	

		return $NEWARRAY;
	
	}	
	
	public function LINUX_VERS(){
		$distriname=$this->LINUX_DISTRIBUTION();
		if(preg_match("#([0-9]+)\.([0-9]+)#", $distriname,$re)){
			return array(intval($re[1]),intval($re[2]));
		}
		
		return array(0,0);
		
	}
	
	function LINUX_ARCHITECTURE(){
		$unix=new unix();
		$uname=$this->find_program("uname");
		exec("$uname -m 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#i[0-9]86#", $val)){return 32;}
			if(preg_match("#x86_64#", $val)){return 64;}
		}
	}
	
	
	public function LINUX_DISTRIBUTION(){
		
		$f=@file_get_contents("/etc/artica-postfix/settings/Daemons/LinuxDistributionFullName");
		if(trim($f)<>null){return $f;}
		
		if(is_file("/etc/SuSE-release")){
			$content=@file_get_contents("/etc/SuSE-release");
			if(preg_match("#([0-9]+)\.([0-9]+)#is", $content,$re)){
				return "openSUSE ".trim($re[1]).".".trim($re[2]);;
			}
		}
	
		if(is_file("/etc/lsb-release")){
			if(!is_file("/etc/redhat-release")){
				$content=explode("\n",@file_get_contents("/etc/lsb-release"));
				while (list ($num, $val) = each ($content) ){
					if(preg_match("#DISTRIB_ID=(.+)#", $val,$re)){$distri_provider=trim($re[1]);}
					if(preg_match("#DISTRIB_RELEASE=([0-9\.]+)#", $val,$re)){$distri_ver=trim($re[1]);}
					if(preg_match("#DISTRIB_CODENAME=(.+)#", $val,$re)){$distri_name=trim($re[1]);}
					
				}
				return "$distri_provider $distri_ver $distri_name";
				
				
			}
		}
	  
		if(is_file("/etc/debian_version")){
			$content=explode("\n",@file_get_contents("/etc/debian_version"));
			while (list ($num, $val) = each ($content) ){
				if(preg_match("#([0-9\.]+)#", $val,$re)){return "Debian ".trim($re[1])." Gnu-linux";}
				if(preg_match("#squeeze\/sid#", $val)){return "Debian 6.0 Gnu-linux";}
			}
		}
	
		if(is_file("/etc/redhat-release")){
			$content=explode("\n",@file_get_contents("/etc/redhat-release"));
			while (list ($num, $val) = each ($content) ){
				if(preg_match("#Fedora Core release\s+([0-9]+)#",$val,$re)){return "Fedora Core release ".trim($re[1])."";}
				if(preg_match("#Fedora release\s+([0-9]+)#",$val,$re)){return "Fedora release ".trim($re[1])."";}
				if(preg_match("#Scientific Linux release\s+([0-9\.]+)#",$val,$re)){return "Scientific Linux release ".trim($re[1])."";}
				if(preg_match("#Mandriva Linux release\s+([0-9\.]+)#",$val,$re)){return "Mandriva Linux release ".trim($re[1])."";}
				if(preg_match("#CentOS\s+.*?release\s+([0-9\.]+)#",$val,$re)){return "CentOS release ".trim($re[1])."";}
				
			}
			
		}
	}
//##############################################################################	
	
	
	public function KERNEL_VERSION_BIN(){
		$cmd=$this->find_program("uname")." -r";
		exec($cmd,$datas);
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#",$datas[0],$re)){
			if(strlen($re[2])==1){$re[2]='0'.$re[2];}
			if(strlen($re[3])==1){$re[3]='0'.$re[3];}
			$kernel=$re[1].$re[2].$re[3];
			
		}
		
		return $kernel;
	}
	
	
	public function  miltergreylist_addacl($complete_string){
	   $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
	   if(!is_file($ConfigurationFile)){return null;}
		$datas=@file_get_contents($ConfigurationFile);
		$tbl=explode("\n",$datas);
		while (list ($num, $ligne) = each ($tbl) ){
	   		if(strtolower(trim($ligne))==strtolower(trim($complete_string))){
	   			return ;
	   		}
		}
	  
	  $tbl[]=$complete_string;
	  @file_put_contents($ConfigurationFile,implode("\n",$tbl)."\n");
	  
	}
	
	public function miltergreylist_cleanconf(){
		$ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		$datas=@file_get_contents($ConfigurationFile);
		$tbl=explode("\n",$datas);
		while (list ($num, $ligne) = each ($tbl) ){
			if(trim($ligne)==null){unset($tbl[$num]);}
			if(substr($ligne,0,1)=="#"){unset($tbl[$num]);}
		}
		
		$tbl[]="\n";
		file_put_contents($ConfigurationFile,implode("\n",$tbl));
		
	 	$this->miltergreylist_check();
	}
	
	private function miltergreylist_check(){
		$ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		$MILTER_GREYLIST_BIN_PATH=$this->MILTER_GREYLIST_BIN_PATH();
		$tmpf=$this->FILE_TEMP();
		for($i=0;$i<50;$i++){
			exec("$MILTER_GREYLIST_BIN_PATH -f $ConfigurationFile -c >$tmpf 2>&1");
			$dd=@file_get_contents($tmpf);
			
			@unlink($tmpf);
			if(preg_match("#config error at line\s+([0-9]+)#is",$dd,$ri)){
				if(!$this->miltergreylist_removeline($ri[1])){break;}
				continue;
			}
			
			else{break;}
		}
	}
	
	private function miltergreylist_removeline($line_number){
		 $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		 if(!is_file($ConfigurationFile)){return null;}
		 $datas=@file_get_contents($ConfigurationFile);
		 $tbl=explode("\n",$datas);
		 if(count($tbl)==$line_number){
		 	$tbl[]="";
		 	file_put_contents($ConfigurationFile,implode("\n",$tbl));
		 	return false;
		 }
		 echo "Starting......: ".date("H:i:s")." milter-greylist Config...: error, removing  \"{$tbl[$line_number]}\" line $line_number";
		 unset($tbl[$line_number]);
		 file_put_contents($ConfigurationFile,implode("\n",$tbl));
		 return true;
	}
	
	
	
	public function miltergreylist_buildacl($pattern){
	 	if(preg_match("#(.+?)@(.+)#",$pattern,$re)){
	 		$part=trim($re[1]);
	 		$domain=trim($re[2]);
	 	}
	   	 $part=str_replace('*','',$part);
	 	
	 	 if(strlen($part)==0){ 
	           $suffix='acl whitelist domain';
	           $domain=str_replace("","@",$domain);
	           if(substr($domain, 0,1)=="."){$domain=substr($domain, 1,strlen($domain));}
	           $pattern=trim($domain);
	           if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $domain)){
	           		$suffix='acl whitelist addr';
	           }
	           
	           
	 	 }else{
	 	 	$suffix="acl whitelist from";
	 	 }
	   
		if(strpos($pattern,'*')>0){
	        $pattern=str_replace('.','\.',$pattern);
	        $pattern=str_replace('*','.*',$pattern);
	       	$pattern="/$pattern/";
		}
	     
		return "$suffix $pattern";
	}
	
 
	public function MILTER_GREYLIST_CONF_PATH(){
		if(is_file('/etc/milter-greylist/greylist.conf')){return '/etc/milter-greylist/greylist.conf';}
		if(is_file('/etc/mail/greylist.conf')){return '/etc/mail/greylist.conf';}
		if(is_file('/opt/artica/etc/milter-greylist/greylist.conf')){return '/opt/artica/etc/milter-greylist/greylist.conf';}
		return '/etc/mail/greylist.conf';
	}
	
	public function MILTER_GREYLIST_BIN_PATH(){
		if(is_file('/usr/local/bin/milter-greylist')){return '/usr/local/bin/milter-greylist';}
		if(is_file('/usr/sbin/milter-greylist')){return '/usr/sbin/milter-greylist';}
		if(is_file('/usr/bin/milter-greylist')){return '/usr/bin/milter-greylist';}	
		}
	
	public function DIRPART_INFO($directory){
		$df=$this->find_program("df");
		exec("$df $directory 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)#",$line,$re)){
				$array["DEV"]=$re[1];
				$array["TOT"]=intval($re[2])*1024;
				$array["USED"]=intval($re[3])*1024;
				$array["AIV"]=intval($re[4])*1024;
				$array["POURC"]=$re[5];
				$array["MOUNT"]=$re[6];
				return $array;
			}
		}
	}
	
	public function DIRPART_OF($directory){
		$df=$this->find_program("df");
		exec("$df $directory 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)#",$line,$re)){
				if(strpos($re[1],"by-uuid")>0){return $this->DISK_DEVNAMEOF($re[1]);}
				return $re[1];
				
			}
		}
	}
	
	public function DISK_DEVNAMEOF($string){
		$udevadm=$this->find_program("udevadm");
		exec("$udevadm  info -q all -n \"$string\" 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#DEVNAME=(.+)#", $line,$re)){
				return trim($re[1]);
				
			}
		}
	}
	
	public function DISK_GET_PARENT_PART($dev){
		$dev=$this->DISK_DEVNAMEOF($dev);
		$udevadm=$this->find_program("udevadm");
		if($GLOBALS["VERBOSE"]){echo "udevadm  info -q all -n $dev 2>&1 ".count($results)." rows\n";}
		exec("$udevadm  info -q all -n $dev 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
				
				
			if(preg_match("#DEVNAME=(.+)#", $line,$re)){
				$partname=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$dev = Partition : $partname\n";}
				continue;
			}
				
			if(preg_match("#ID_PART_ENTRY_DISK=(.+)#", $line,$re)){
				$ID_PART_ENTRY_DISK=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$dev = $ID_PART_ENTRY_DISK\n";}
				if($ID_PART_ENTRY_DISK<>null){break;}
				continue;
			}
				
		}
		
		if($ID_PART_ENTRY_DISK==null){return null;}
		return $this->DISK_DEVNAMEOF("/dev/block/$ID_PART_ENTRY_DISK");
	}
	
	
	public function DIRDISK_OF($directory){
		
		$dev=$this->DIRPART_OF($directory);
		if($GLOBALS["VERBOSE"]){echo "$directory = Partition : $dev\n";}
		
		if($dev==null){return null;}
		$udevadm=$this->find_program("udevadm");
		
		
		
		exec("$udevadm  info -q all -n $dev 2>&1",$results);
		
		if($GLOBALS["VERBOSE"]){echo "udevadm  info -q all -n $dev 2>&1 ".count($results)." rows\n";}
		
		while (list ($index, $line) = each ($results) ){
			
			
			if(preg_match("#DEVNAME=(.+)#", $line,$re)){
				$partname=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$dev = Partition : $partname\n";}
				continue;
			}
			
			if(preg_match("#ID_PART_ENTRY_DISK=(.+)#", $line,$re)){
				$ID_PART_ENTRY_DISK=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$dev = $ID_PART_ENTRY_DISK\n";}
				if($ID_PART_ENTRY_DISK<>null){break;}
				continue;
			}
			
		}
		
		if($ID_PART_ENTRY_DISK==null){return null;}
		return $this->DISK_DEVNAMEOF("/dev/block/$ID_PART_ENTRY_DISK");
		
		
	}
	
	public function DIRSIZE_KO_nocache($directory){
		if(is_link($directory)){$directory=readlink($directory);}
		$res=$this->DIRSIZE_BYTES_NOCACHE($directory);
		if($res==0){return 0;}
		return round($res/1024);
	}	
	
	public function DIRSIZE_KO($directory){
		if(is_link($directory)){$directory=readlink($directory);}
		$res=$this->dskspace_bytes($directory);
		if($res==0){return 0;}
		return round($res/1024);
	}
	
	public function DIRSIZE_MB($directory){
		if(is_link($directory)){$directory=readlink($directory);}
		$mdkey=md5($directory);
		@mkdir("/etc/artica-postfix/DIRSIZE_MB_CACHE",0755,true);
		$cache_file="/etc/artica-postfix/DIRSIZE_MB_CACHE/$mdkey";
		$Time=$this->file_time_min($cache_file);
		if($Time<120){return trim(@file_get_contents("$cache_file"));}
		@unlink($cache_file);
		
		$EXEC_NICE=$this->EXEC_NICE();
		$du=$this->find_program("du");
		
		$dir_name_regex=str_replace("/", "\/", $directory);
		$dir_name_regex=str_replace(".", "\.", $dir_name_regex);
		$pid=$this->PIDOF_PATTERN("$du.*?$dir_name_regex");
		if($this->process_exists($pid)){return 0;}
		
		if(!function_exists("system_is_overloaded")){
			include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
		}
		if(function_exists("system_is_overloaded")){
			if(system_is_overloaded()){return 0;}
		}
		
		
		exec("$EXEC_NICE$du -m -s $directory 2>&1",$results);
		
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
				@file_put_contents($cache_file, $re[1]);
				return $re[1];}
		}
	}	
	public function DIRSIZE_BYTES_NOCACHE($directory){
		if(is_link($directory)){$directory=readlink($directory);}
	
		if(!is_dir($directory)){
			$directory_tmp=dirname($directory);
			if(is_link($directory_tmp)){$dir=readlink($directory_tmp);}
			if(is_dir($directory_tmp)){$directory=$directory_tmp;}
		}
		return $this->dskspace_bytes($directory,true);
	}
	
	private function is_overloaded(){
		if(!function_exists("system_is_overloaded")){
			include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
		}
		if(function_exists("system_is_overloaded")){
			return system_is_overloaded();
		}
	}

	public function DIRSIZE_BYTES($directory,$asCache=false){
		if(is_link($directory)){$directory=readlink($directory);}
		$mdkey=md5($directory);
		@mkdir("/etc/artica-postfix/DIRSIZE_BYTES_CACHE",0755,true);
		$cache_file="/etc/artica-postfix/DIRSIZE_BYTES_CACHE/$mdkey.bytes";
		if($this->is_overloaded()){return trim(@file_get_contents($cache_file));}
		
		if($asCache){
			$Time=$this->file_time_min($cache_file);
			if($Time<180){return trim(@file_get_contents($cache_file));}
			@unlink($cache_file);
		}
			
		
		if(!is_dir($directory)){
			$directory_tmp=dirname($directory);
			if(is_link($directory_tmp)){$dir=readlink($directory_tmp);}
			if(is_dir($directory_tmp)){$directory=$directory_tmp;}
		}
		$result=$this->dskspace_bytes($directory);
		@file_put_contents($cache_file, $result);
		return $result;
	}	
	
	public function DIR_COUNT_OF_FILES($directory){
		if(is_link($directory)){$directory=readlink($directory);}
		if(!is_dir($directory)){return 0;}
		$find=$this->find_program("find");
		$nice=$this->EXEC_NICE();
		$wc=$this->find_program("wc");
		exec(trim("$nice $find \"$directory\" -type f  | $wc -l 2>&1"),$results);
		return trim(@implode("",$results));
	}
	
	public function LOCATE_PHP5_BIN(){
	  if(isset($GLOBALS["LOCATE_PHP5_BIN"])){if(strlen($GLOBALS["LOCATE_PHP5_BIN"])>3){return $GLOBALS["LOCATE_PHP5_BIN"];}}
	  
	  if(is_file('/opt/articatech/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/opt/articatech/bin/php';return '/opt/articatech/bin/php';}
	  if(is_file('/usr/bin/php5')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php5';return '/usr/bin/php5';}
	  if(is_file('/usr/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php';return '/usr/bin/php';}
	  if(is_file('/usr/local/apache-groupware/php5/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php5';return '/usr/local/apache-groupware/php5/bin/php';}
	  
	  $php5=$this->find_program("php");
	  if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}
	  $php5=$this->find_program("php5");
	  if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}	 	  
	  $this->events("Unable to stat php5 !");
	  
	}
	public function LOCATE_CUPS_ACCEPT(){
 		if(is_file('/usr/sbin/cupsaccept')){return '/usr/sbin/cupsaccept';}
    	if(is_file('/usr/sbin/accept')){return '/usr/sbin/accept';}
	}
 //############################################################################# 

public function getmodpathfromconf(){
	if(is_dir("/etc/apache2/mods-enabled")){return "/etc/apache2";}
	if(is_dir("/etc/httpd/mods-enabled")){return "/etc/httpd";}
	if(is_dir("/usr/local/etc/httpd/mods-enabled")){return "/usr/local/etc/httpd";}
	if(is_dir("/usr/local/etc/apache2/mods-enabled")){return "/usr/local/etc/apache2";}
	$httpdconf=$this->LOCATE_APACHE_CONF_PATH();
	if($httpdconf=="/etc/httpd/conf/httpd.conf"){return "/etc/httpd";}
	if($httpdconf=="/usr/local/etc/httpd/conf/httpd.conf"){return "/usr/local/etc/httpd";}
	return dirname($httpdconf);
}


public function CGROUPS_limit_service_structure($groupname,$cpu_shares,$cpus=0,$blkio){
	$echobin=$this->find_program("echo");
	$this->CGROUPS_create_service_structure($groupname);
	echo "Starting......: ".date("H:i:s")." cgroups Limiting $groupname to $cpu_shares share CPU:#$cpus I/O $blkio limit\n";
	system("$echobin $cpu_shares > /cgroups/cpu/$groupname/cpu.shares");
	system("$echobin 0 >/cgroups/cpuset/$groupname/cpuset.mems");
	system("$echobin ".intval($cpus)." > /cgroups/cpuset/$groupname/cpuset.cpus");
	system("$echobin $blkio >/cgroups/blkio/$groupname/blkio.weight");

}

private function CGROUPS_create_service_structure($groupname){
	$subgroups[]="cpuset";
	$subgroups[]="blkio";
	$subgroups[]="cpu";
	$unix=new unix();
	$cgcreate=$this->find_program("cgcreate");
	$CREATED=true;

	while (list ($num, $ligne) = each ($subgroups)){
		if(!is_dir("/cgroups/$ligne/$groupname")){$CREATED=false;break;}

	}

	if($CREATED){return;}
	shell_exec("$cgcreate -a root -g cpu,cpuset,blkio:$groupname");
}





public function APACHE_DIR_SITES_ENABLED(){
	if(is_dir("/etc/apache2/sites-enabled")){return '/etc/apache2/sites-enabled';}
	$httpdconf=$this->LOCATE_APACHE_CONF_PATH();
	$DAEMON_PATH=$this->getmodpathfromconf();
	return $DAEMON_PATH."/sites-enabled";
	$mods_enabled=$DAEMON_PATH."/mods-enabled";	
	
}

public function LIGHTTPD_PHP5_CGI_BIN_PATH(){
	if(isset($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];}
	$GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-fcgi");
	if(!is_file($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){$GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-cgi");}
	return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];
	
}

function CREATE_NEW_UUID(){
	if(is_file("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED")){
		@chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0755);
		return;
	}
	
	$chattr=$this->find_program("chattr");
	shell_exec("$chattr -i /etc/artica-postfix/settings/Daemons/SYSTEMID");
	$uuid=trim($this->gen_uuid());
	if(strlen($uuid)>5){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID", $uuid);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED", time());
		@chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0755);
		shell_exec("$chattr +i /etc/artica-postfix/settings/Daemons/SYSTEMID");
		
	}
}

public function GetUniqueID(){
	if(isset($GLOBALS["MYUUID"])){return $GLOBALS["MYUUID"];}
	$GLOBALS["MYUUID"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID"));
	return $GLOBALS["MYUUID"];	
	
}

public function gen_uuid(){
    return sprintf(
        '%08x-%04x-%04x-%02x%02x-%012x',
        mt_rand(),
        mt_rand(0, 65535),
        bindec(substr_replace(
            sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
        ),
        bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
        mt_rand(0, 255),
        mt_rand()
    );
}

public function ifconfig_all_ips(){
	$ri=array();
	$cmd=$this->find_program("ifconfig")." -a 2>&1";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "Found \"$line\"\n";}
		if(preg_match("#inet (adr|addr|Adresse):([0-9\.]+)#",$line,$re)){
			if(trim($re[2])==null){
				if($GLOBALS["VERBOSE"]){echo "ERROR \"$line\" re[2] = NULL\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "SUCCESS \"[{$re[2]}]\"\n";}
			$ri[$re[2]]=$re[2];
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "No match 1 \"$line\"\n";}
		if(preg_match("#inet end\.:\s+([0-9\.]+)#", $line,$re)){
			$ri[$re[1]]=$re[1];
			continue;
		}
		
		
	}
	
	if(count($ri)==0){
		if($GLOBALS["VERBOSE"]){echo "count = 0 -> Next...\n";}
		$ifconfigs=$this->ifconfig_interfaces_list();
		unset($ifconfigs["lo"]);
		while (list ($index, $ipaddr) = each ($ifconfigs) ){
			$ri[$ipaddr]=$ipaddr;
		}
	}
	
	return $ri;
	
	
}



public function ifconfig_interfaces_list(){
	$ri=array();
	$cmd=$this->find_program("ip")." -o addr sho 2>&1";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^[0-9]+:\s+(.*?)\s+inet\s+([0-9\.]+)\/#", $line,$re)){
			if(preg_match("#(.+?)@(.+)#", $re[1],$rz)){$re[1]=$rz[1];}
			$ri[$re[1]]=$re[2];
			continue;
		}
		
	}	
	

	
	return $ri;
}

public function LOCATE_APACHE_CTL(){
	$apachectl=$this->find_program("apachectl");
	if(is_file($apachectl)){return $apachectl;}
	$apachectl=$this->find_program("apache2ctl");
	if(!is_file($apachectl)){$apachectl=$this->find_program("apachectl");}
	if(is_file($apachectl)){return $apachectl;}	
}


public function LOCATE_AMAVISD_BIN_PATH(){
	if(is_file('/usr/local/sbin/amavisd')){return '/usr/local/sbin/amavisd';}
	if(is_file('/usr/sbin/amavisd-new')){return '/usr/sbin/amavisd-new';}
	return $this->find_program('amavisd');
}

public function SAMBA_GetNetAdsInfos(){
	$unix=new unix();
	$net=$this->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	return $array;
}

public function LOCATE_STUNNEL_BIN(){
return $this->LOCATE_STUNNEL();	
	
}


	public function PATH_LDAP_DIRECTORY_DATA(){
		$file=file_get_contents("/etc/ldap/slapd.conf");
		$datas=explode("\n",$file);
		if(!is_array($datas)){
			$this->events("Unable to stat /etc/ldap/slpad.conf !");
			return null;}
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^directory\s+(.+)#",$ligne,$re)){
				return trim($re[1]);
			}
		}
		$this->events("Unable to stat directory in /etc/ldap/slpad.conf ! in ".count($datas)." rows");
	}
	
	public function FILE_IS_LINK($path){
		$path=rtrim($path, '/');
		$stat=$this->find_program("stat");
		exec("$stat $path 2>&1",$datas);
		while (list ($num, $ligne) = each ($datas) ){$ligne=trim($ligne);if($ligne==null){continue;}if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){return true;}}
		return false;
	}
	
	public function FILE_REALPATH($path){
		$orgpath=$path;
		$path=rtrim($path, '/');
		$stat=$this->find_program("stat");
		exec("$stat $path 2>&1",$datas);
			while (list ($num, $ligne) = each ($datas) ){
				$ligne=trim($ligne);
				if($ligne==null){continue;}
				if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){
					$f=explode("->", $ligne);
					$realpth=trim($f[1]);
					if(substr($realpth, 0,1)=="`"){$realpth=substr($realpth, 1,strlen($realpth));}
					if(substr($realpth, strlen($realpth)-1,1)=="'"){$realpth=substr($realpth, 0,strlen($realpth)-1);}
					return trim($realpth);
				}
			}
		return trim($orgpath);
	}
	
	function LDAP_SCHEMA_PATH(){
	  	if(is_dir('/etc/ldap/schema')){return '/etc/ldap/schema';}
	    if(is_dir('/etc/openldap/schema')){return '//etc/openldap/schema';}
	    if(is_dir('/opt/artica/etc/openldap/schema')){return '//opt/artica/etc/openldap/schema';}
	}	
	
	
	public function DHCPD_BIN_PATH(){
		$dhcpd=$this->find_program("dhcpd");
		if(is_file($dhcpd)){return $dhcpd;}
		$dhcpd=$this->find_program("dhcpd3");
		if(is_file($dhcpd)){return $dhcpd;}
	}
	
	public function SessionPathInMemoryInfos(){
		$session_path=ini_get('session.save_path');
		
		$df=$this->find_program("df");
		$cmd="$df 2>&1";
		exec($cmd,$results);
		
		while (list ($num, $line) = each ($results)){
			if(preg_match("#tmpfs\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+$session_path#", $line,$re)){
				$array["MAX"]=$re[1];
				$array["USE"]=$re[2];
				$array["FREE"]=$re[3];
				$array["POURC"]=$re[4];
				break;
			}
		}
		return $array;
		
	}
	

	
	public function ALL_DISKS_STATUS(){
	
	
		$fdisk=$this->find_program("fdisk");
		$cmd="$fdisk -l 2>&1";
		exec($cmd,$results);
	
		while (list ($num, $line) = each ($results)){
			$DISK=null;
			$line=trim($line);if($line==null){continue;}
			if(preg_match("#Disk\s+(.*?):.*?,\s+([0-9]+)\s+bytes#", $line,$re)){
				$DISK=$re[1];
				$ARRAY[$DISK]["SIZE"]=$re[2];
				continue;
				
			}
			
			if(preg_match("#(.+?)\s+.+?\s+[0-9]+\s+[0-9]+\s+[0-9]+.*?\s+[0-9a-z]+#", $line,$re)){
				$MountedOn=$this->tune2fs_values(trim($re[1]));
				$LAST_MOUNTED_ON=trim($re[1]);
				if(!isset($MountedOn["LAST_MOUNTED_ON"])){$MountedOn["LAST_MOUNTED_ON"]=null;}
				if($MountedOn["LAST_MOUNTED_ON"]<>null){
					$LAST_MOUNTED_ON=$MountedOn["LAST_MOUNTED_ON"];
				}
				if($GLOBALS["VERBOSE"]){echo trim($re[1])." -> $LAST_MOUNTED_ON\n";}
				
				if($LAST_MOUNTED_ON<>null){
					$ARRAY[$DISK]["PARTS"][trim($re[1])]=$this->DIRPART_INFO($LAST_MOUNTED_ON);
				}
				continue;
			}else{
				if($GLOBALS["VERBOSE"]){echo "NOT FOUND \"$line\"\n";		}
			}
			
		}
		
		while (list ($disk, $MAIN) = each ($ARRAY)){
			$USED=0;
			if(!isset($MAIN["PARTS"])){continue;}
			if(!is_array($MAIN["PARTS"])){continue;}
			if(count($MAIN["PARTS"])==0){continue;}
			
			while (list ($partition, $SUBARRAY) = each ($MAIN["PARTS"])){
				$USED=$USED+$SUBARRAY["USED"];
			}
			$ARRAY[$disk]["USED"]=$USED;
			
		}
		return $ARRAY;
	
	}	
	
	public function DEBIAN_INSTALL_PACKAGE($package){
		$aptget=$this->find_program("apt-get");
		if(!is_file($aptget)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." apt-get: $package apt-get -> No such binary\n";}
			return;
		}
		$apttime="/etc/artica-postfix/APT_GET_UPDATE_TIME";
		$time=$this->file_time_min($apttime);
		
		if($time>1880){
			$php=$this->LOCATE_PHP5_BIN();
			
			if(function_exists("build_progress_install")){
				build_progress_install("{updating_repository}",20);
			}
			
			
			shell_exec("$php /usr/share/artica-postfix/exec.apt-get.php --sources-list");
			system("$aptget update -y");
			@unlink("/etc/artica-postfix/APT_GET_UPDATE_TIME");
			@file_put_contents("/etc/artica-postfix/APT_GET_UPDATE_TIME", time());
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." apt-get: $package installing package\n";}
		if(function_exists("build_progress_install")){
			build_progress_install("{installing} {please_wait}",25);
		}
		
		if($GLOBALS["OUTPUT"]){
			system("DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -fuy install $package ");
			
		}else{
		
			exec("DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -fuy install $package 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." apt-get: $ligne\n";}
			}
		
		}
		
		if(function_exists("build_progress_install")){
			build_progress_install("{installing} {done}",30);
		}
		
	}
	
	
	public function ps_mem_report(){
	
		
		$pid=$this->PIDOF_PATTERN("ps_mem.py");
		if($this->process_exists($pid)){return;}
		
		$python=$this->find_program("python");
		$nice=$this->EXEC_NICE();
		$ps=$this->find_program("ps");
		$sort=$this->find_program("sort");
		$head=$this->find_program("head");
		$results[]="************* Memory summarize ************************";
		$results[]="";
		exec(trim("$nice $python /usr/share/artica-postfix/bin/ps_mem.py 2>&1"),$results);
		
		$results[]="";
		$results[]="";
		
		
		$results[]="************* TOP 50 processes ************************";
		exec("$ps --no-heading -eo user,pid,pcpu,args|$sort -grbk 3|$head -50 2>&1",$results);
		$results[]="";
		$results[]="";
		
		$results[]="************* TOP 50 Memory processes ************************";
		exec("$ps --no-heading -eo user,pid,pmem,args|$sort -grbk 3|$head -50 2>&1",$results);
		$results[]="";
		$results[]="";
		$results[]="************* Kernel Infos ************************";
		$results[]=@file_get_contents("/proc/meminfo");
		$results[]="";
		$results[]="";
		$results[]="************* ALL Processes ************************";
		exec("$ps auxww 2>&1",$results);
		return @implode("\n", $results);
	}
	
	
	public function REMOVE_INTERFACE_CACHE(){
		$rm=$this->find_program("rm");
		include_once(dirname(__FILE__)."/class.status.hardware.inc");
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*");
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/logs/cache/*");
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/interface-cache/*");
		@unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");
		system("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
		$php=$this->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.interface-size.php --force");
		$stats=new status_hardware();
		shell_exec("/etc/init.d/artica-status restart");
	}
	
	
	public function KILL_PROCESSES_BY_PORT($port){
		
		$fuser=$this->find_program("fuser");
		exec("$fuser $port/tcp 2>&1",$results);
		while (list ($key, $line) = each ($results) ){
			if($GLOBALS["VERBOSE"]){echo "fuser: ->\"$line\"\n";}
			if(preg_match("#tcp:\s+(.+)#", $line,$re)){$FUSERS=explode(" ",$re[1]);}
		}
		
		
		
		if(count($FUSERS)>0){
			while (list ($key, $pid) = each ($FUSERS) ){
				$pid=trim($pid);
				if(!is_numeric($pid)){continue;}
				if($pid==0){continue;}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing $pid PID that listens $port\n";}
				unix_system_kill_force($pid);
			}
		}
	}
	
	public function nginx_version(){
		$nginx=$this->find_program("nginx");
		if(!is_file($nginx)){return "0.0.0";}
		exec("$nginx -V 2>&1",$results);
		while (list ($key, $value) = each ($results) ){
				if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		}
		return "0.0.0";
	}
	
	public function find_program($strProgram,$nocache=false) {
	  $key=md5($strProgram);
	  
	 if(!$nocache){
	  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
	  if(function_exists("apc_store")){$value=trim(apc_fetch("LOCATE_PROGRAM_$key"));if($value<>null){if(basename($value)==$strProgram){$GLOBALS["find_program"][$key]=trim($value);return trim($value);}}}
	  if(!isset($GLOBALS["find_program"]["$key"])){$GLOBALS["find_program"]["$key"]=null;}
	  if(isset($_SESSION["find_program"]["$key"])){if($_SESSION["find_program"]["$key"]<>null){return $_SESSION["find_program"]["$key"];}}
	  if(isset($GLOBALS["find_program"]["$key"])){if($GLOBALS["find_program"]["$key"]<>null){return $GLOBALS["find_program"]["$key"];}	}
	 }
	  $value=trim($this->internal_find_program($strProgram));
	  if(function_exists("apc_store")){apc_store("LOCATE_PROGRAM_$key",$value);}
	  $GLOBALS["find_program"]["$key"]=$value;
      return $value;
		
	}
	
	public 	function CleanOldLibs(){
		$ln=$this->find_program("ln");
		if(is_file("/usr/lib/x86_64-linux-gnu/libtalloc.so.2")){
			if(is_file("/usr/lib/libtalloc.so.2")){@unlink("/usr/lib/libtalloc.so.2");}
		}
		
		if(is_file("/usr/lib/libicapapi.so.3.0.4")){
			@unlink("/usr/lib/libicapapi.so.3.0.1");
			@unlink("/usr/lib/libicapapi.so.3.0.2");
			@unlink("/usr/lib/libicapapi.so.3.0.3");
			if(!is_link("/usr/lib/libicapapi.so.3")){
				@unlink("/usr/lib/libicapapi.so.3");
				shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.4 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
			}
		}
		
		if(is_file("/usr/lib/libicapapi.so.3.0.3")){
			@unlink("/usr/lib/libicapapi.so.3.0.1");
			@unlink("/usr/lib/libicapapi.so.3.0.2");
			if(!is_link("/usr/lib/libicapapi.so.3")){
				@unlink("/usr/lib/libicapapi.so.3");
				shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.3 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
			}
		}		
		if(is_file("/usr/lib/libicapapi.so.3.0.2")){
			@unlink("/usr/lib/libicapapi.so.3.0.1");
			if(!is_link("/usr/lib/libicapapi.so.3")){
				@unlink("/usr/lib/libicapapi.so.3");
				shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.2 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
			}
		}		
		
		if(is_file("/usr/lib/libicapapi.so.3.0.1")){
			if(!is_link("/usr/lib/libicapapi.so.3")){
				shell_exec("$ln -s /usr/lib/libicapapi.so.3.0.1 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
			}
		}
		
	}
	
	public function load_rt_table(){
		$f=explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
		while (list ($index, $line) = each ($f) ){
			if(!preg_match("#([0-9]+)\s+(.+)#", $line,$re)){continue;}
			$array[$re[2]]=$re[1];
	
		}
			
	
	}
	
	public function last_rt_table_number(){
		$c=0;
		$f=explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
		while (list ($index, $line) = each ($f) ){
			if(!preg_match("#([0-9]+)\s+(.+)#", $line,$re)){continue;}
			if($re[1]>0){continue;}
			if($re[1]>252){continue;}
			if($re[1]>$c){$c=$re[1];}
		}
	
		return $c;
	
	}	
	
	public function LOCATE_mysqld_bin(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		$bin=$this->find_program('mysqld');
		if(is_file($bin)){$GLOBALS[__FUNCTION__]=$bin;return $bin;}
		if(is_file("/opt/artica/mysql/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/opt/artica/mysql/libexec/mysqld";return "/opt/artica/mysql/libexec/mysqld";}
		if(is_file("/usr/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/usr/libexec/mysqld";return "/usr/libexec/mysqld";}

	}
//##############################################################################

	
	public function IsProductionTime(){
		$period_from=79200; //22H
		$period_to=21600; //6H
		$time=time();
		$currentStartDay=strtotime(date("Y-m-d 00:00:01"));
		$ThisInt=$time-$currentStartDay;
		if($GLOBALS["VERBOSE"]){echo "Day start at $currentStartDay\n";}
		if($GLOBALS["VERBOSE"]){echo "This time is $ThisInt\n";}
		if($GLOBALS["VERBOSE"]){echo "$period_from = 22H\n";}
		if($GLOBALS["VERBOSE"]){echo "$period_to = 06h\n";}
		if($ThisInt>$period_from){
			if($GLOBALS["VERBOSE"]){echo "$ThisInt>$period_from NO Production time\n";}
			return false;}
		if($ThisInt<$period_to){
			if($GLOBALS["VERBOSE"]){echo "$ThisInt>$period_from NO Production time\n";}
			return false;}
		return true;
		
		
	}
	
	
	public function BLKID_INFOS($dev){
		$array=array();
		$blkid=$this->find_program("blkid");
		$df=$this->find_program("df");
		if(!is_file($blkid)){return array("UUID"=>"blkid No such binary");}
		$cmd="$blkid \"$dev\" 2>&1";
		exec($cmd,$results);
		$imploded=trim(@implode("", $results));
		$f=explode(" ",$imploded );
		while (list ($num, $ligne) = each ($f) ){if(preg_match('#(.+?)="(.+?)"#', $ligne,$re)){$array[strtoupper($re[1])]=$re[2];}}
		$results=array();
		$devRgx=str_replace("/", "\/", $dev);
		$devRgx=str_replace(".", "\.", $devRgx);
		exec("$df -h \"$dev\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#$devRgx\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array["SIZE"]=$re[1];
				$array["USED"]=$re[2];
				$array["AIVA"]=$re[3];
				$array["POURC"]=$re[4];
				$array["MOUNTED"]=$re[5];
			}
		}
		return $array;		
		
	}
	
	public function DIR_STATUS($path){
		
		$df=$this->find_program("df");
	
		
		$results=array();
		$devRgx=str_replace("/", "\/", $path);
		$devRgx=str_replace(".", "\.", $path);
		exec("$df \"$path\" 2>&1",$results);
		
		$pattern="#.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#";
		
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match($pattern, $ligne,$re)){
				writelogs_framework("No match \"$ligne\" $pattern",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
				$array["SIZE"]=$re[1];
				$array["USED"]=$re[2];
				$array["AIVA"]=$re[3];
				$array["POURC"]=$re[4];
				$array["MOUNTED"]=$re[5];
		}
		return $array;
	
	}	
	
	public function BLKID_UUIDs(){
		if(isset($GLOBALS["BLKID_UUIDs"])){return $GLOBALS["BLKID_UUIDs"];}
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/blkid-uuid.db";
		$unix=new unix();
		if(!$GLOBALS["VERBOSE"]){
			if(is_file($cachefile)){
				if($unix->file_time_min($cachefile)<3){$GLOBALS["BLKID_UUIDs"]=unserialize(@file_get_contents($cachefile));return $GLOBALS["BLKID_UUIDs"];}
			}
		}
		$GLOBALS["BLKID_UUIDs"]=array();
		$blkid=$unix->find_program("blkid");
		exec("$blkid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			$label=null;
				
			if(preg_match("#^(.+?):#", $ligne,$re)){$dev=$re[1];}
			if(preg_match("#UUID=\"(.+?)\"#", $ligne,$re)){$uuid=$re[1];}
			if(preg_match("#LABEL=\"(.+?)\"#", $ligne,$re)){$label=$re[1];}
			if($GLOBALS["VERBOSE"]){echo "$ligne uuid=$uuid; label=$label;\n";}
			$GLOBALS["BLKID_UUIDs"][$uuid]["DEV"]=$dev;
			$GLOBALS["BLKID_UUIDs"][$uuid]["LABEL"]=$label;
		}
	
		@file_put_contents($cachefile, serialize($GLOBALS["BLKID_UUIDs"]));
		@chmod($cachefile,0755);
		return $GLOBALS["BLKID_UUIDs"];
	
	}	
	
	public function BLKID_ALL(){
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/blkid.db";
		$unix=new unix();
		if(!$GLOBALS["VERBOSE"]){
			if(is_file($cachefile)){
				if($unix->file_time_min($cachefile)<3){return unserialize(@file_get_contents($cachefile));}
			}
		}
		$blkid=$unix->find_program("blkid");
		exec("$blkid 2>&1",$results);
		$label=null;
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			
			if(preg_match("#^(.+?):#", $ligne,$re)){$dev=$re[1];}
			if(preg_match("#UUID=\"(.+?)\"#", $ligne,$re)){$uuid=$re[1];}
			if(preg_match("#LABEL=\"(.+?)\"#", $ligne,$re)){$label=$re[1];}
			if($GLOBALS["VERBOSE"]){echo "$ligne uuid=$uuid; label=$label;\n";}
			$array[$dev]["UUID"]=$uuid;
			$array[$dev]["LABEL"]=$label;
		}
	
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/blkid.db", serialize($array));
		@chmod($cachefile,0755);
		return $array;
	
	}
	
	public function SYSTEMS_ALL_PARTITIONS(){
		$df=$this->find_program("df");
		exec("$df -h 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array[$re[1]]["SIZE"]=$re[2];
				$array[$re[1]]["USED"]=$re[3];
				$array[$re[1]]["AIVA"]=$re[4];
				$array[$re[1]]["POURC"]=$re[5];
				$array[$re[1]]["MOUNTED"]=$re[6];
				$array[$re[1]]["VALUES"]=$this->tune2fs_values($re[1]);
				
			}
		}
		return $array;
	
	}	
	
	public function SYSTEM_GET_MEMORY_MB(){
		
		$free=$this->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
		return 0;
	}

	public function SYSTEM_GET_SWAP_MB(){
		
		$free=$this->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
		return 0;
	}

	public function SYSTEM_GET_SWAP_PERC(){
		
		$free=$this->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){continue;}
			$total=$re[1];
			$used=$re[2];
			$prc=($used/$total)*100;
			return round($prc);
		}
		return 0;
		
	}
	
	function system_mysql_events($subject,$text,$file,$context,$line=null){
		$file=basename($file);
		if(!is_numeric($line)){$line=0;}
		if($GLOBALS["VERBOSE"]){echo "$subject $text script:$file in line $line\n";}
		if(!class_exists("mysql")){include_once("/usr/share/artica-postfix/ressources/class.mysql.inc");}
		if(!class_exists("mysql")){if($GLOBALS["VERBOSE"]){echo "mysql no such class!\n";}return;}
		if(!isset($GLOBALS["MYHOSTNAME"])){$GLOBALS["MYHOSTNAME"]=$this->hostname_g();}
		$file=basename($file);
		$subject=addslashes($subject);
		$text=addslashes($text);
		$sql="INSERT IGNORE INTO events (zDate,hostname,process,text,context,sended,content)
		VALUES(NOW(),'{$GLOBALS["MYHOSTNAME"]}','$file','$subject','$context','1','$text')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){if(function_exists("writelogs")){writelogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}}
	}	
	
	
	public function UFDBGUARDD_MINOR(){
		if(isset($GLOBALS["UFDBGUARDD_MINOR"])){return $GLOBALS["UFDBGUARDD_MINOR"];}
		$this->UFDBGUARDD_MAJOR();
		return $GLOBALS["UFDBGUARDD_MINOR"];
	}
	
	public function UFDBGUARDD_MAJOR(){
		if(!isset($GLOBALS["UFDBGUARDD_MAJOR"])){$GLOBALS["UFDBGUARDD_MAJOR"]=null;}
		if(is_numeric($GLOBALS["UFDBGUARDD_MAJOR"])){if($GLOBALS["UFDBGUARDD_MAJOR"]>0){return $GLOBALS["UFDBGUARDD_MAJOR"];}}
		$ufdbguarddd=$this->find_program("ufdbguardd"); 
		exec("$ufdbguarddd -v 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#^ufdbguardd:\s+([0-9]+)\.([0-9]+)#",$ligne,$re)){$major=$re[1];$minor=$re[2];break;}}
		$GLOBALS["UFDBGUARDD_MAJOR"]=$major;$GLOBALS["UFDBGUARDD_MINOR"]=$minor;	
		return $GLOBALS["UFDBGUARDD_MAJOR"];	
	}
	
	private function internal_find_program($strProgram){
	if($strProgram=="rm"){
		if(!is_file("/bin/rm")){
			$Arch=$this->LINUX_ARCHITECTURE();
			if($Arch==64){
				@copy("/usr/share/artica-postfix/bin/rm","/bin/rm");
				$chattr=$this->find_program("chattr");
				shell_exec("$chattr -i /bin/rm");
			}
		}
	}	
		
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
	  		 '/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
	  	} else {
	   	return strpos($strProgram, '.exe');
	  }
	}
	
	public function POSTCONF_SET($key,$value){
	$value=str_replace('$','\$',$value);
	if($GLOBALS["MEM_POSTCONF"]<>null){$postconf=$GLOBALS["MEM_POSTCONF"];}else{$postconf=$this->find_program("postconf");$GLOBALS["MEM_POSTCONF"]=$postconf;}
		shell_exec("$postconf -e \"$key = $value\" >/dev/null 2>&1");
	}
	
	
	public function POSTCONF_GET($value){
		if(!isset($GLOBALS["MEM_POSTCONF"])){$GLOBALS["MEM_POSTCONF"]=null;}
		if($GLOBALS["MEM_POSTCONF"]<>null){$postconf=$GLOBALS["MEM_POSTCONF"];}else{$postconf=$this->find_program("postconf");$GLOBALS["MEM_POSTCONF"]=$postconf;}
		if($GLOBALS["DEBUG"]){echo "POSTCONF_GET:: $postconf -h $value\n";}
		exec("$postconf -h $value 2>&1",$results);
		$v=array();
		while (list ($num, $line) = each ($results)){if(preg_match("#warning:#", $line)){continue;}$v[]=$line;}		
		return trim(implode("",$v));
		
	}
	
	public function POSTCONF_MULTI_GET($instance,$value){
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}	
		if($GLOBALS["DEBUG"]){echo "POSTCONF_MULTI_GET:: postmulti=$postmulti\n";}
		$cmd="$postmulti -i postfix-$instance -x postconf -h $value 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		exec($cmd,$results);
		$v=array();
		while (list ($num, $line) = each ($results)){
			if(preg_match("#warning:#", $line)){continue;}
			$v[]=$line;
		}
		$vFinal=@implode("", $v);
		if($GLOBALS["DEBUG"]){writelogs_framework("$cmd -> $vFinal",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		return $vFinal;
	}

	public function POSTCONF_MULTI_SET($instance,$key,$value){
		$value=str_replace('$','\$',$value);
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}
		$cmd="$postmulti -i postfix-$instance -x postconf -e \"$key=$value\" >/dev/null 2>&1";
		if($GLOBALS["DEBUG"]){echo "POSTCONF_MULTI_SET:: $cmd\n";}
		shell_exec("$postmulti -i postfix-$instance -x postconf -e \"$key=$value\" >/dev/null 2>&1");
	}		
	
	public function POSTFIX_MULTI_PID($instance){
		if($GLOBALS["DEBUG"]){echo "POSTFIX_MULTI_PID:: $instance\n";}
		$path=$this->POSTCONF_MULTI_GET($instance,"queue_directory")."/pid/master.pid";
		$data=trim(@file_get_contents($path));
		return trim($data);
	}	
	
	public function POSTFIX_PID(){
		$path=$this->POSTCONF_GET("queue_directory")."/pid/master.pid";
		$GLOBALS["DEBUG_LOGS"][]="Pid path : $path";
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
		$data=trim(@file_get_contents($path));
		$data=trim($data);
		$GLOBALS["DEBUG_LOGS"][]="Pid content : $data";
		if(preg_match("#([0-9]+)#",$data,$re)){$data=$re[1];}
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $data\n";}
		return $data;
	}
	
	public function POSTFIX_MASTER_BIN_PATH(){
		$path=trim($this->POSTCONF_GET("daemon_directory")."/master");
		$GLOBALS["DEBUG_LOGS"][]="Bin path : $path";
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
		return $path;
	}

	
	public function ChangeRootPassword($password){
		
		if(!is_file("/etc/artica-postfix/shadow.bak")){
			if(is_file("/etc/artica-postfix/shadow.bak")){@unlink("/etc/artica-postfix/shadow.bak");}
			@copy("/etc/shadow", "/etc/artica-postfix/shadow.bak");
		}
		
		$t=array();
		$f=explode("\n",@file_get_contents("/etc/shadow"));
		while (list($num,$val)=each($f)){
			if(trim($val)==null){continue;}
			if(preg_match("#^root:(.*?):.*?:#", $val,$re)){
				$val=str_replace($re[1], "", $val);
					
			}
		
			$t[]=$val;
		
		}
		
		@file_put_contents("/etc/shadow", @implode("\n", $t));
		
		
		
		$php=$this->LOCATE_PHP5_BIN();
		$cmd=trim("$php /usr/share/artica-postfix/exec.pam.php --build >/dev/null 2>&1");
		shell_exec($cmd);
		
		$echo=$this->find_program("echo");
		
		$chpasswd=$this->find_program("chpasswd");
		$passwd=$this->shellEscapeChars($password);
		
		$cmd="$echo root:$passwd | $chpasswd 2>&1";
		exec("$cmd",$results);
		writelogs_framework("$cmd " .count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
		
		while (list ($num, $line) = each ($results)){writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);}
		reset($results);
		return base64_encode(@implode("\n",$results));	
		
		
		
	}
	
	
	
	
	
	public function POSTFIX_VERSION(){return $this->POSTCONF_GET("mail_version");}
	
	public function LOCATE_POSTFIX_DAEMON_DIRECTORY(){
		if(isset($GLOBALS[__FUNCTION__])){
			if(strlen($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		}
		$locate1=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_1();
		$locate2=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
		$locate3=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_3();
		if($locate2<>null){if($locate1<>$locate2){$locate1=$locate2;}}
		if($locate3<>null){if($locate1<>$locate3){$locate1=$locate3;}}
		$GLOBALS[__FUNCTION__]=$locate1;
		return $locate1;
	}
	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_1(){
		if(is_file('/usr/lib/postfix/master')){return ('/usr/lib/postfix');}
		if(is_file('/usr/lib64/postfix/master')){return ('/usr/lib64/postfix');}
		if(is_file('/usr/libexec/postfix/master')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/master')){return ('/usr/local/libexec/postfix');}
		
	}
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_2(){
		if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix');}
		
	}	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_3(){
		if(is_file('/usr/lib/postfix/postmulti-script')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postmulti-script')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postmulti-script')){return ('/usr/local/libexec/postfix');}
	}

	public function LOCATE_POSTFIX_POSTSCREEN(){
		$f=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
		if(is_file($f)){return $f;}
		if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix/postscreen');}
		if(is_file('/usr/lib64/postfix/postscreen')){return ('/usr/lib64/postfix/postscreen');}
		if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postscreen');}
		if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix/postscreen');}
	}	
	
	
	
	public function IPTABLES_DELETE_REGEX_ENTRIES($regexPat){
		if(trim($regexPat)==null){return;}
		$iptables_save=$this->find_program("iptables-save");
		$iptables_restore=$this->find_program("iptables-restore");
		$cmd="$iptables_save > /etc/artica-postfix/iptables.conf";
		shell_exec($cmd);
		$data=file_get_contents("/etc/artica-postfix/iptables.conf");
		$datas=explode("\n",$data);
		if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." iptables.conf: ". count($data)." lines L.". __LINE__."\n";}
		$pattern="#$regexPat#";	
		$count=0;
		while (list ($num, $ligne) = each ($datas) ){
			if($ligne==null){continue;}
			if(preg_match($pattern,$ligne)){
				if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." Delete \"$ligne\" L.". __LINE__."\n";}		
				$count++;continue;
			}
			$conf=$conf . $ligne."\n";
		}

		file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
		if($count>0){
			$cmd="$iptables_restore < /etc/artica-postfix/iptables.new.conf";
			if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__."$cmd L.". __LINE__."\n";}
			shell_exec("$cmd");
		}
		
		
	}
	
	
	public function LOCATE_SENDMAIL_PID_PATH(){
		if(is_file("/var/run/sendmail.pid")){return "/var/run/sendmail.pid";}
	}
	
	
	
	public function events($text,$logFile=null,$phplog=false,$sourcefunction=null,$sourceline=null,$sourcefile=null){
		$maxSize=900000;
		$mem=null;
		$internal_load=0;
		$pid=getmypid();
		if($logFile<>null){
			$logbasename=basename($logFile);
			if($logbasename=="squid.watchdog.log"){$maxSize=900000;}
		}
		if($sourcefile<>null){$sourcefile=basename($sourcefile);}
		$date=date("Y-m-d H:i:s");
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				if($sourcefile==null){$sourcefile=basename($trace[1]["file"]);}
				if($sourcefunction==null){$sourcefunction=$trace[1]["function"];}
				if($sourceline==null){$sourceline=$trace[1]["line"];}
			}
			
		}
		
		if(function_exists("memory_get_usage")){$mem=round(((memory_get_usage()/1024)/1000),2);}
		if(function_exists("sys_getloadavg")){$array_load=sys_getloadavg();$internal_load=$array_load[0];	}		
	
		

		$line="$date [$pid] $sourcefile: $text  - function:$sourcefunction in line:$sourceline Mem:{$mem}MB Load:$internal_load\n";
		if($logFile==null){$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslog.log";}
		
		
		$size=@filesize($logFile);
		if($size>$maxSize){
			@unlink($logFile);
			@touch($logFile);
			@chmod($logFile, 0777);
		}
		
		$h = @fopen($logFile, 'a');
	
		
		if($GLOBALS["VERBOSE"]){echo $line;}
		if(!isset($_SESSION["uid"])){
			if($phplog){error_log("[ -- ]::$line");}
		}else{
			if($phplog){error_log("[{$_SESSION["uid"]}]::$line");}
		}
		
		
		@fwrite($h,$line);
		@fclose($h);
	}	

	public function dirdir($path){
		$array=array();
		if(is_link($path)){$path=readlink($path);}
		foreach (glob("$path/*",GLOB_ONLYDIR) as $directory) {
			$directory=utf8_decode($directory);
			$directory=str_replace('//', "/", $directory);
			$array[$directory]=$directory;
		}
		
		return $array;
	}
	
	
	public function COUNT_FILES($directory){
		if(is_link($directory)){$directory=readlink($directory);}
		if(!is_dir($directory)){return 0;}
		if (!$handle = @opendir($directory)) {return 0;}
		$c=0;
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$path = $directory.'/'.$filename;
			if(is_dir($path)){continue;}
			$c++;
		}	
		return $c;
	}
	
public	function getDirectories($directory){
	if(is_link($directory)){$directory=readlink($directory);}
	$GLOBALS["RECURSVIVE_INC"]=$GLOBALS["RECURSVIVE_INC"]+1;
	if($GLOBALS["MAX_RECUSIVE_DIRS"]<50){$GLOBALS["MAX_RECUSIVE_DIRS"]=400;}
	if($GLOBALS["RECURSVIVE_INC"]>$GLOBALS["MAX_RECUSIVE_DIRS"]){
		$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
		error_log("[{$_SESSION["uid"]}]::PHP Fatal: cannot parse directory [instance {$GLOBALS["RECURSVIVE_INC"]}], maximum recursive of {$GLOBALS["MAX_RECUSIVE_DIRS"]} has been reached $called");
		return;
	}
	$array_items = array();
	if ($handle = @opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
						$newarrayDirectories= $this->getDirectories($directory. "/" . $file);
						if(is_array($newarrayDirectories)){$array_items = array_merge($array_items,$newarrayDirectories);}
						$file = $directory . "/" . $file;
						$array_items[] = preg_replace("/\/\//si", "/", $file);
				} else {
					//$file = $directory . "/" . $file;
					//$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
	

} 

function DirRecursiveFiles($dir,$pattern=null){
    $Npattern=str_replace(".", "\.", $pattern);
    $Npattern=str_replace("*", ".*?", $Npattern);	
    $files = Array();
    $file_tmp= glob($dir.'*',GLOB_MARK | GLOB_NOSORT);

    foreach($file_tmp as $item){
        if(substr($item,-1)!=DIRECTORY_SEPARATOR){
        	if($pattern<>null){
        		if(!is_file($item)){continue;}
        		if(!preg_match("#$Npattern$#", $item)){continue;}
        	}
            $files[] = $item;
        }else{
            $files = array_merge($files,$this->DirRecursiveFiles($item,$pattern));
        }
    }

    return $files;
}
	
function DirFiles($path,$regexsearch=null){
	$dir_handle = @opendir($path);
	$array=array();
	if(!$dir_handle){
		return array();
	}
	$count=0;	
	while ($file = readdir($dir_handle)) {
	  if($file=='.'){continue;}
	  if($file=='..'){continue;}
	  if(is_dir("$path/$file")){continue;}
	 	if($regexsearch<>null){if(!preg_match("#$regexsearch#i", $file)){continue;}}
			$array[$file]=$file;
			continue;
		
		
	  }
	
	@closedir($dir_handle);
	return $array;
}

	function GLFS_ismounted($path){
		$pathString=str_replace("/","\/",$path);
		$pathString=str_replace(".","\.",$pathString);
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($index, $line) = each ($f) ){
			if(preg_match("#$pathString\s+fuse\.glusterfs#",$line)){
				return true;
			}
			
		}
		
	}



	public function dir_count_files_recursive($path) {
	    $files = 0;
	    $dir = opendir($path);
	    if (!$dir){return 0;}
	    while (($file = readdir($dir)) !== false) {
	        if ($file[0] == '.'){ continue; }
	        if (is_dir($path.$file)){        
	            $files += $this->dir_count_files_recursive($path.$file.DIRECTORY_SEPARATOR);
	        }
	        else {
	            $files++;
	        }
	    }    
	    closedir($dir);
	    return $files;
		}

public function LOCATE_SQUID_PID(){
	$pid_filename=null;
	if(is_file("/var/run/squid/squid.pid")){return "/var/run/squid/squid.pid";}
	$f=explode("\n",@file_get_contents($this->SQUID_CONFIG_PATH()));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#pid_filename\s+(.+)#",$ligne,$re)){
			$pid_filename=trim($re[1]);
		}
	}
	
	if($pid_filename<>null){
		if(!is_file($pid_filename)){
			if(is_file("/var/run/squid.pid")){return "/var/run/squid.pid";}
		}
	}
	
}


	public function PIDOF($binpath,$noppid=false){
		
		$cmd=$this->find_program('pidof')." -s $binpath";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $cmd\n";}
		$re=$this->proc_exec($cmd);
		if(is_array($re)){
		while (list ($num, $ligne) = each ($re) ){	
			if(preg_match("#[0-9]+#",$ligne)){
				$pid=trim($ligne);
				if($this->PID_IS_CHROOTED($pid)){continue;}
				if($noppid){
					if($GLOBALS["VERBOSE"]){echo "PIDOF -> $pid\n";}
					return $pid;}
				
				return $this->PROCESS_PPID($pid);
			}
		}
		}
		
	}
	
	public function PID_IS_CHROOTED($pid){
		return false;
		if(is_file("/proc/user_beancounters")){return false;}
		if($this->find_program("cgconfigparser")){return false;}
		$pid=trim($pid);
		if(!is_numeric($pid)){return;}
		if(is_file("/etc/artica-postfix/AS_VPS_CLIENT")){return false;}
		if(!is_file("/proc/$pid/cgroup")){return false;}
		if(preg_match("#[0-9]+.+?:/(.+?)$#",@file_get_contents("/proc/$pid/cgroup"),$re)){
			$vps=trim($re[1]);
			if(strlen($vps)>1){return true;}
		}
		return false;
	}
	
	public function PIDOF_PATTERN($pattern){
		$pattern=str_replace("/","\/",$pattern);
		$pattern=str_replace("$","\$",$pattern);
		$pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
		exec($pattern,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#pgrep\s+#",$ligne)){continue;}
			if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $ligne\n";}
			if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){return $re[1];}
		}
		
	}

	
	public function PIDOF_PATTERN_ALL($pattern,$noshAndNoMe=false){
		$mypid=getmypid();
		$LIST=array();
		$pattern=str_replace("/","\/",$pattern);
		$pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
		exec($pattern,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#pgrep\s+#",$ligne)){continue;}		
			if(!preg_match("#^([0-9]+)\s+#",$ligne,$re)){continue;}
			$pid=$re[1];
			if($pid==$mypid){continue;}
			if(preg_match("#^([0-9]+)\s+sh -c#",$ligne,$re)){continue;}
			
			
			
			
			if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN_ALL():: Found $pid \"$ligne\"\n";}
			
			
			$LIST[$pid]=$pid;
				
		}
		return $LIST;
	
	}	
	
	public function PROCESS_GET_USER($pid){
		$filename="/proc/$pid/status";
		if(!is_file($filename)){return null;}
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#Uid:\s+([0-9]+)#", $ligne,$re)){continue;}
			if($re[1]==0){return "root";}
			return $this->USER_ID_FROM_USERNAME($re[1]);
		}
		
	}
	
	public function USER_ID_FROM_USERNAME($id){
		$filename="/etc/shadow";
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#^(.*?):.*?:$id#",$ligne,$re)){continue;}
			return $re[1];
		}
	}
	
	
	public function PROCESS_TIME_TO_LIVE($pid){
		@chmod("/usr/share/artica-postfix/bin/procstat",0755);
		exec("/usr/share/artica-postfix/bin/procstat",$array);
		while (list ($num, $ligne) = each ($array) ){
			if(preg_match("#start_time:.+?\((.+?\.#",$ligne,$re)){
				$mins=($re[1]/60);
				return $mins;
			}
		}
	}
	
	
	public function POSTFIX_MULTI_INSTANCES_LIST(){
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}
		if(strlen($postmulti)<3){return null;}
		exec("$postmulti -l 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^(.+?)\s+([a-z\-])\s+(.+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				if(preg_match("#postfix-(.+)#",$re[1],$ri)){
					$instance[$ri[1]]=$ri[1];
				}
			}
		}
		
		return $instance;
	}
	
	public function ISMemoryHiger1G(){
		if(isset($GLOBALS["ISMemoryHiger1G"])){return $GLOBALS["ISMemoryHiger1G"];}
		$ini=new iniFrameWork();
		$ini->loadFile("/etc/artica-postfix/performances.conf");
		$DisableFollowServiceHigerThan1G=$ini->get("PERFORMANCES","DisableFollowServiceHigerThan1G");
		if(!is_numeric($DisableFollowServiceHigerThan1G)){$DisableFollowServiceHigerThan1G=0;}
		if($DisableFollowServiceHigerThan1G==1){
			$GLOBALS["ISMemoryHiger1G"]=true;
			return true;
		}
		$mem=$this->MEM_TOTAL_INSTALLEE();
		if($mem>1012024){			
			$GLOBALS["ISMemoryHiger1G"]=true;
			return true;
		}
		$GLOBALS["ISMemoryHiger1G"]=false; 
		return false;
	}
	
	
	public function MEM_TOTAL_INSTALLEE(){
		if(isset($GLOBALS["MEM_TOTAL_INSTALLEE"])){return $GLOBALS["MEM_TOTAL_INSTALLEE"];}
		$results=1012029;
		$filename="/proc/meminfo";
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#MemTotal:\s+([0-9]+)#",$ligne,$re)){continue;}
			$results=$re[1];
			break;
		}
		$GLOBALS["MEM_TOTAL_INSTALLEE"]=$results;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/TempMemInstalled",$results);
		return $results;
	}
	
	public function squid_get_alternate_port(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
			if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
			
			if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}			
		}
		
	}
	
	public function squid_locate_pinger(){
		return $this->squid_locate_generic_bin("pinger");
		
	}
	
	
	public function squid_locate_generic_bin($program){
	$possibleDirs[]="/lib/squid3";
	$possibleDirs[]="/lib64/squid3";
	$possibleDirs[]="/lib/squid";
	$possibleDirs[]="/lib64/squid";
	$possibleDirs[]="/usr/lib/squid";
	$possibleDirs[]="/usr/lib64/squid";
	$possibleDirs[]="/usr/local/squid/libexec";
	while (list ($num, $directory) = each ($possibleDirs) ){
		if(is_file("$directory/$program")){return "$directory/$program";}
	}
	
}



function Mysql_TestDump($array,$container){
	while (list ($a, $b) = each ($array)){
		if(preg_match("#Got error:#i", $b)){
			if(function_exists("events_repair")){events_repair("Dump failed $b,...");}
			if($GLOBALS["VERBOSE"]){echo "Dump failed $b,...\n";}
			return false;

		}
	}
	$array_text=@implode(" ", $array);
	if(!is_file($container)){
		$this->ToSyslog("Mysql_TestDump:: Dump failed $container, no such file $array_text");
		if($GLOBALS["VERBOSE"]){echo "Dump failed $container, no such file ...\n";}
		ufdbguard_admin_events("Fatal Error: day: Dump failed $container, no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
			
	}

	$size=@filesize($container);

	if($size<100){
		if($GLOBALS["VERBOSE"]){echo "Dump failed $container size too low ( $size bytes) ...\n";}
		ufdbguard_admin_events("Fatal Error: day: Dump failed $container size too low ( $size bytes) ... ",__FUNCTION__,__FILE__,__LINE__,"backup");
		@unlink($container);
		return;
	}


	return true;
}

private function squid_is31(){
	if(isset($GLOBALS["squid_is31"])){return $GLOBALS["squid_is31"];}
	$root_squid_version=$this->squid_version();
	if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
	$data=null;
	$GLOBALS["squid_is31"]=false;
	$VER=explode(".",$root_squid_version);
	if($VER[0]<4){
		if($VER[1]<2){
			if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
			$GLOBALS["squid_is31"]=true;return true;}
	}
	return false;
	
}

	
	public function squid_internal_port(){
		$array=$this->squid_get_internal_addrport();
		return $array[1];
		
	}
	
	
	public function GET_HTTP_PROXY_STRING(){
		$squidbin=$this->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){
			$SQUIDEnable=$this->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($SQUIDEnable==1){
				$squid_port=$this->squid_internal_port();
				return "http://127.0.0.1:$squid_port";
			}
		
		}
		$ini=new Bs_IniHandler();
		$datas=$this->GET_INFO("ArticaProxySettings");
		if(trim($datas)==null){return null;}
		$ini->loadString($datas);
		$ArticaProxyServerEnabled=strtolower($ini->_params["PROXY"]["ArticaProxyServerEnabled"]);
		if($ArticaProxyServerEnabled==null){return null;}
		if($ArticaProxyServerEnabled=="no"){return null;}
		if($ArticaProxyServerEnabled==0){return null;}
		$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
		if($ArticaProxyServerName==null){return null;}
		$ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
		if($ArticaProxyServerPort==0){return null;}
		return "http://$ArticaProxyServerName:$ArticaProxyServerPort";
		
	}
	
	
	private function squid_get_internal_addrport(){
		$SquidMgrListenPort=trim($this->GET_INFO("SquidMgrListenPort"));
		if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
			$SquidBinIpaddr=$this->GET_INFO("SquidBinIpaddr");
			if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$this->squid_get_alternate_port();
	
			if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
				$SquidBinIpaddr=$re[1];
				if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
				$http_port=$re[2];
			}
	
		}else{
			$SquidBinIpaddr="127.0.0.1";
			$http_port=$SquidMgrListenPort;
		}
	
		return array($SquidBinIpaddr,$http_port);
	}	
	
	

	
	public function squidclient($cmd,$nocache=false){
		if(!$nocache){
			if(isset($GLOBALS["squidclient"][$cmd])){return $GLOBALS["squidclient"][$cmd];}
		}
		$cache_manager=new cache_manager();
		$data=$cache_manager->makeQuery($cmd);
		if($cache_manager->ok){
			$GLOBALS["squidclient"][$cmd]=$data;
			return $data;
		}
		
	}
	
	
	public function isSquidNTLM(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#^auth_param\s+ntlm\s+program#", $ligne)){return true;}
		}
		return false;
		
	}


	
	
	public function squid_get_system_info(){
	
			$results=explode("\n",$this->squidclient("info"));
			
			$path=null;		
		
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^(.*?):$#", trim($ligne),$re)){
				$title=$re[1];
				continue;
			}
		
			if(preg_match("#\s+(.*?):\s+(.+)#", $ligne,$re)){
				$sub=$re[1];
				$values=trim($re[2]);
			}
			if(strpos($ligne, ':')==0){
				if(preg_match("#\s+([0-9]+)\s+(.+)#", $ligne,$re)){
					$sub=trim($re[2]);
					$values=trim($re[1]);
				}
			}
		
			if($title==null){continue;}
			if($sub==null){continue;}
			$ARRAY[$title][$sub]=$values;
		
		
		}

		return $ARRAY;
		
	}
	
	
	public function squid_get_cache_infos(){
	$path=null;
	$array=array();
	$MAINCONTENT=$this->squidclient("storedir");
	
	if($GLOBALS["VERBOSE"]){echo "******** storedir: ".strlen($MAINCONTENT)." Bytes ********\n";}
	$results=explode("\n",$MAINCONTENT);
	if($GLOBALS["VERBOSE"]){echo "******** storedir: ".count($results)." Rows ********\n";}
	if($GLOBALS["VERBOSE"]){
		if(count($results)<10){echo "################## \n\n$MAINCONTENT\n\n##################\n";}
	}
	
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Connection refused#",$line)){
			writelogs_framework($line,__FUNCTION__,__LINE__);;
			return;
		}
		
		if(preg_match("#Shared Memory Cache#", $line)){
			$path="Memory";
			$array[$path]["index"]=0;
			$array[$path]["PARTITION"]="Memory";
			$array[$path]["DIRPART_INFO"]=null;
			$array[$path]["FULL_SIZE"]=null;
		}
		
		if(preg_match("#Store Directory\s+\#([0-9]+).+?:\s+(.+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Store Directory -> {$re[2]}\n";}
			$path=trim($re[2]);
			$array[$path]["index"]=$re[1];
			$array[$path]["PARTITION"]=$this->DIRPART_OF($path);
			$array[$path]["DIRPART_INFO"]=$this->DIRPART_INFO($path);
			$array[$path]["FULL_SIZE"]=$this->DIRSIZE_BYTES($path);
			continue;
		}
		
		if($path==null){continue;}
		
		if(preg_match("#Maximum Size:\s+([0-9]+)#",$line,$re)){
		if($GLOBALS["VERBOSE"]){echo "$path -> Maximum Size:{$re[1]}\n";}
			$array[$path]["MAX"]=$re[1];
			continue;
		}
		
		if(preg_match("#Current Size:\s+([0-9]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$path ->Current Size:{$re[1]} \"$line\"\n";}
			$array[$path]["CURRENT"]=$re[1];
			continue;
		}	

		if(preg_match("#Current Size:\s+([0-9]+).*?KB\s+([0-9\.]+)%#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$path ->Current Size:{$re[1]} \"$line\"\n";}
			$array[$path]["CURRENT"]=$re[1];
			$array[$path]["POURC"]=$re[2];
			continue;
		}	

		if(preg_match("#Percent Used:\s+([0-9\.]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$path ->Percent Used:{$re[1]}\n";}
			$array[$path]["POURC"]=$re[1];
			continue;
		}

		if(preg_match("#ERR_ACCESS_DENIED#",$line)){
			writelogs_framework("ACCESS DENIED",__FUNCTION__,__LINE__);;
			break;
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "NOT DETECTED $line\n";}
		
	}		
		return $array;
		
	}
	
	
	public function MYSQL_SOCKET_PATH(){
		$cnf=$this->MYSQL_MYCNF_PATH();
		$ini=new iniFrameWork($cnf);
		$return=$ini->get("mysqld","socket");
		if(trim($return==null)){return "/var/run/mysqld/mysqld.sock";}
	}
	
	public function MYSQL_MYCNF_PATH(){
		if(is_file('/etc/mysql/my.cnf')){return('/etc/mysql/my.cnf');}
  		if(is_file('/etc/my.cnf')){return '/etc/my.cnf';}

	} 
	
	public function MYSQL_DATADIR(){
		return $this->MYSQL_DATA_DIR();

	} 	
	
	
	
	public function FSTAB_GETDEV($targetPointPath){
		$datas=explode("\n",@file_get_contents("/etc/fstab"));
		while (list ($num, $ligne) = each ($datas) ){
			if(strpos($ligne,$targetPointPath)>0){
				if(preg_match("#(.+?)\s+#",$ligne,$re)){
				return $re[1];
			}}
		}
		
	}
	
	
	public function FSTAB_QUOTA($dev,$enabled=0){
		$rows=explode("\n",@file_get_contents("/etc/fstab"));
		$dev_pattern=str_replace("/","\/",$dev);
		if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
		while (list ($index, $line) = each ($rows) ){
		if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
					$row_index=$index;
					$mount_path=$re[1];
					$type=$re[2];
					$option=$re[3];
					$other=$re[4];
					break;
				}
			}
		if($mount_path==null){return;}
		$options=explode(",",$option);
		while (list ($i, $token) = each ($options) ){$tokens[$token]=true;}
		writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
		unset($tokens["usrquota"]);
		
		if($enabled==0){
			unset($tokens["usrquota"]);
			unset($tokens["usrjquota=aquota.user"]);
			unset($tokens["grpjquota=aquota.group"]);
			unset($tokens["jqfmt=vfsv"]);
			unset($tokens["jqfmt=vfsv0"]);		
			
		}else{
			$tokens["usrjquota=aquota.user"]=true;
			$tokens["grpjquota=aquota.group"]=true;
			$tokens["jqfmt=vfsv0"]=true;
		}
		while (list ($token,$i) = each ($tokens) ){$final[]=$token;}
		$new_options=implode(",",$final);
		writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
		reset($rows);
		$rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
		@file_put_contents("/etc/fstab",@implode("\n",$rows));
		$mount=$this->find_program("mount");
		writelogs_framework("$mount -o remount $dev",__FUNCTION__,__FILE__,__LINE__);
		exec("$mount -o remount $dev");	
		$quotacheck=$this->find_program("quotacheck");
		if(is_file($quotacheck)){shell_exec("$quotacheck -afgum &");}
		$quotaon=$this->find_program("quotaon");
		if(is_file($quotaon)){shell_exec("$quotaon -avug &");}
		
				
	}
	
	
	public function grep_php($filename,$maxnumLines=0,$pattern=null){
		if($pattern==null){return $this->tail_php($filename,$maxnumLines);}
		if(!is_file($filename)){
			if($GLOBALS["DEBUG_GREP"]){echo "$filename no such file\n";}
			return array();
		}
		$lines=array();
		$fp = @fopen($filename, "r");
		if(!$fp){
			if($GLOBALS["DEBUG_GREP"]){echo "$filename BAD FD\n";}
			return array();}
		
		$t=array();
		while(!feof($fp)){
			$line = trim(fgets($fp, 4096));
			$line=str_replace("\r\n", "", $line);
			$line=str_replace("\n", "", $line);
			$line=str_replace("\r", "", $line);
			if(!preg_match("#$pattern#i", $line)){continue;}
			$t[]=$line;
		}
		
		if(count($t)==0){return array();}
		$RESULTS=array();
		if($maxnumLines>0){
			rsort($t);
			
			for($i=0;$i<$maxnumLines;$i++){
				if($GLOBALS["DEBUG_GREP"]){echo "{$t[$i]} OK\n";}
				$RESULTS[]=$t[$i];
			}
			
		}
		
		
		@fclose($fp);
		return $RESULTS;
	}
	
	
	function tail_php($file, $numLines = 100,$pattern=null){
		if($pattern<>null){return $this->grep_php($file,$numLines,$pattern);}
		$fp = fopen($file, "r");
		$chunk = 4096;
		$fs = sprintf("%u", filesize($file));
		$max = (intval($fs) == PHP_INT_MAX) ? PHP_INT_MAX : filesize($file);
		$f=array();
		for ($len = 0; $len < $max; $len += $chunk) {
			$seekSize = ($max - $len > $chunk) ? $chunk : $max - $len;
	
			fseek($fp, ($len + $seekSize) * -1, SEEK_END);
			$data = fread($fp, $seekSize);
			$f[]=$data;
			if (substr_count($data, "\n") >= $numLines + 1) {
				preg_match("!(.*?\n){".($numLines)."}$!", $data, $match);
				fclose($fp);
				return $f;
			}
		}
		fclose($fp);
		return $f;
	}	
	
	
	
	
	
	public function CHANGE_HOSTNAME($hostname){
		if(trim($hostname)==null){return;}
		@unlink("/etc/artica-postfix/FULL_HOSTNAME");
		
		if(strpos($hostname, ".")>0){
			$tbl=explode(".",$hostname);
			$strippedHost=$tbl[0];
			unset($tbl[0]);
			$domainName=@implode(".", $tbl);
		}else{
			$strippedHost=$hostname;
		}
		
		$DisableEtcHosts=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableEtcHosts"));
		if(!is_numeric($DisableEtcHosts)){$DisableEtcHosts=0;}

		
		$domainName=str_replace("(", "", $domainName);
		$domainName=str_replace(")", "", $domainName);
		if($domainName==null){$domainName="localhost.localdomain";}
		
		
		@file_put_contents("/etc/hostname", $hostname);
		@file_put_contents("/etc/HOSTNAME", $hostname);
		@file_put_contents("/etc/mailname", $hostname);
		@file_put_contents("/etc/artica-postfix/FULL_HOSTNAME", $hostname);
		
		$sysctl=$this->find_program("sysctl");
		$hostnamebin=$this->find_program("hostname");
		$nohup=$this->find_program("nohup");
		$php5=$this->LOCATE_PHP5_BIN();
		
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts >/dev/null 2>&1 &");
		
		if(strlen($sysctl)>3){
			shell_exec("$sysctl -w kernel.domainname=$domainName >/dev/null 2>&1");
			shell_exec("$sysctl -w kernel.hostname=$strippedHost >/dev/null 2>&1");
			shell_exec("$sysctl -p >/dev/null 2>&1");
		}
		
		
		
		
		if(strlen($hostnamebin)>3){
			shell_exec("$hostnamebin $hostname  >/dev/null 2>&1");
		}
		
		
		
   		if(is_file("/usr/share/artica-postfix/bin/artica-install")){shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus >/dev/null 2>&1 &");}
	}	
	
	
	public function GET_QUOTA_MOUNTED(){
		$mount=$this->find_program("mount");
		exec("$mount 2>&1",$results);
		while (list ($i, $line) = each ($results) ){
			if(!preg_match("#(.+?)\s+on\s+(.+?)\s+.+?usrjquota#",$line,$re)){continue;}
			$rr[$re[2]]=true;
		}
		
		return $rr;
		
	}
	
function uptime(){

     $e=file('/proc/uptime');
     preg_match('#(.*) (.*)#', $e[0], $e);
     $t=time();
     $Since=$t-intval($e[1]);
     if($GLOBALS["VERBOSE"]){echo "First integer {$e[1]} -> ".intval($e[1])." ($Since)\n";}     
     return $this->distanceOfTimeInWords($Since,time());
     
   }	
   
   function TARGZ_TEST_CONTAINER($container,$nogzip=false,$output=false){
	   	$size=@filesize($container);
	   	$resultsZ=array();
	   	if($size<100){return false;}
		$tar=$this->find_program("tar");
		$z="z";
		if($nogzip){$z=null;}
		$cmdline="$tar {$z}tvf $container";
		if($output){echo "Starting......: ".date("H:i:s")." $cmdline\n";}
	   	exec("$cmdline 2>&1",$resultsZ);
	   	while (list ($a, $b) = each ($resultsZ)){
	   		if($output){echo "Starting......: ".date("H:i:s")." $b\n";}
	   		if(preg_match("#gzip: stdin: not in gzip format#", $b)){
	   			if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $cmdline gzip: stdin: not in gzip format");}
	   			if($GLOBALS["VERBOSE"]){echo "gzip: stdin: not in gzip format\n";}
	   			return false;
	   		}
	   		
	   		if(preg_match("#does not look like a tar#", $b)){
	   			if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: does not look like a tar");}
	   			$GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]=@implode("\n", $resultsZ);
	   			if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
	   			return false;
	   		}
	   	
	   		if(preg_match("#tar: Error#", $b)){
	   			if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $b");}
	   			$GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]=@implode("\n", $resultsZ);
	   			if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
	   			return false;
	   		}
	   	}
	  if($output){echo "Starting......: ".date("H:i:s")." $container correct\n";}
   	return true;
   	
   }
	
	
function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
	    $distanceInSeconds = round(abs($toTime - $fromTime));
	    $distanceInMinutes = round($distanceInSeconds / 60);
	       
	        if ( $distanceInMinutes <= 1 ) {
	            if ( !$showLessThanAMinute ) {
	                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
	            } else {
	                if ( $distanceInSeconds < 5 ) {
	                    return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 10 ) {
	                    return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 20 ) {
	                    return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
	                }
	                if ( $distanceInSeconds < 40 ) {
	                    return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 60 ) {
	                    return '{lessthanaminute}';
	                }
	               
	                return '1 minute';
	            }
	        }
	        if ( $distanceInMinutes < 45 ) {
	            return $distanceInMinutes . ' {minutes}';
	        }
	        if ( $distanceInMinutes < 90 ) {
	            return '{abouttime} 1 {hour}';
	        }
	        if ( $distanceInMinutes < 1440 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
	        }
	        if ( $distanceInMinutes < 2880 ) {
	            return '1 {day}';
	        }
	        if ( $distanceInMinutes < 43200 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
	        }
	        if ( $distanceInMinutes < 86400 ) {
	            return '{abouttime} 1 {month}';
	        }
	        if ( $distanceInMinutes < 525600 ) {
	            return round(floatval($distanceInMinutes) / 43200) . ' {months}';
	        }
	        if ( $distanceInMinutes < 1051199 ) {
	            return '{abouttime} 1 {year}';
	        }
	       
	        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
	}	
	
	
	public function FSTAB_ACL($dev,$aclenabled=0){
	
			$rows=explode("\n",@file_get_contents("/etc/fstab"));
			
			$dev_pattern=str_replace("/","\/",$dev);
			if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
			while (list ($index, $line) = each ($rows) ){
				if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
					$row_index=$index;
					$mount_path=$re[1];
					$type=$re[2];
					$option=$re[3];
					$other=$re[4];
					break;
				}
			}
			
			
			if($mount_path==null){return;}
			$options=explode(",",$option);
			
			while (list ($i, $token) = each ($options) ){$tokens[$token]=true;}
			
			writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
			if($aclenabled==0){
				unset($tokens["acl"]);
				unset($tokens["user_xattr"]);
				$tune2fs_cmd="-acl";
			}else{
				$tokens["acl"]=true;
				$tokens["user_xattr"]=true;
				$tune2fs_cmd="+acl";
			}
			while (list ($token,$i) = each ($tokens) ){$final[]=$token;}	
			
			$new_options=implode(",",$final);
			writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
			reset($rows);
			$rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
			@file_put_contents("/etc/fstab",@implode("\n",$rows));
			$mount=$this->find_program("mount");
			exec("$mount -o remount $dev");	
			$tune2fs=$this->find_program("tune2fs");
			if($tune2fs<>null){
				exec("$tune2fs -o user_xattr,acl $dev 2>&1",$rs);
				writelogs_framework("$tune2fs -o user_xattr,acl $dev",__FUNCTION__,__FILE__,__LINE__);
				writelogs_framework(implode(",",$rs),__FUNCTION__,__FILE__,__LINE__);
				
			}
			
			}
	
	
	public function DISK_GET_TYPE($dev_path){
		$dev_path=str_replace("/","\/",$dev_path);
		$blkid=$this->find_program("blkid");
		$tmp=$this->FILE_TEMP();
		shell_exec("$blkid >$tmp 2>&1");
		$datas=explode("\n",@file_get_contents($tmp));
		@unlink($tmp);
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$dev_path.+?TYPE=\"(.+?)\"#",$ligne,$re)){
				return $re[1];
			}
			
		}
	}

}

function BuildingExecStatus($text,$pourc){
	$ini=new iniFrameWork("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
	$ini->set("status","text","$text");
	$ini->set("status","pourc","$pourc");
	$ini->saveFile("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
	@chmod("/usr/share/artica-postfix/ressources/logs/exec.status.ini",0755);
	error_log(__FUNCTION__.":: status $pourc% $text");
	
}
function BuildingExecRightStatus($text,$pourc){
	$file="/usr/share/artica-postfix/ressources/logs/exec-right.status.ini";
	$ini=new iniFrameWork($file);
	$ini->set("status","text","$text");
	$ini->set("status","pourc","$pourc");
	$ini->saveFile($file);
	@chmod($file,0755);	
}




function cpulimit($forced=0){
	return;
	$unix=new unix();
	if($GLOBALS["cpulimit_path"]==null){
		$GLOBALS["cpulimit_path"]=$unix->find_program("cpulimit");
	}
	if($GLOBALS["cpulimit_path"]==null){return null;}
	if($forced==0){
		$cpu=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/cpulimit"));
		if($cpu==null){$cpu=0;}
	}else{
		$cpu=$forced;
	}
	if($cpu==0){return null;}
	$pid=getmypid();
	$cmd="{$GLOBALS["cpulimit_path"]} -p $pid -l $cpu -z >>/var/log/cpulimit 2>&1 &";
	if($GLOBALS["VERBOSE"]){writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);}
	shell_exec($cmd);
	
}


function GET_INFO_DAEMON($key){
   	if(function_exists("apc_store")){$value=apc_fetch($key);if($value<>null){return trim($value);}}	
	if(isset($GLOBALS["GET_INFO_DAEMON"][$key])){
   		if($GLOBALS["GET_INFO_DAEMON"][$key]<>null){return $GLOBALS["GET_INFO_DAEMON"][$key];}
	}
	$result=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
	$GLOBALS["GET_INFO_DAEMON"][$key]=$result;
	if(function_exists("apc_store")){apc_store($key, $result);}	
	return $result;
}

function cpulimitProcessName($processName){
	$unix=new unix();
	$cpulimit=$unix->find_program("cpulimit");
	if($cpulimit==null){return null;}
	$cpu=GET_INFO_DAEMON('cpulimit');
	if($cpu==null){$cpu=0;}
	if($cpu==0){return null;}
	$cmd="$cpulimit -e $processName -l $cpu &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function cpulimitProcessNameKill($processName){
	$unix=new unix();
	$cpulimit=$unix->find_program("cpulimit");
	if($cpulimit==null){return null;}	
	$cmd=$unix->find_program("pgrep")." -l -f \"$cpulimit -e $processName\"";
	exec($cmd,$results);
	if(!is_array($results)){return null;}
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#([0-9]+)\s+(.+)#",$ligne,$re)){
			if(preg_match("#cpulimit#",$re[2])){
				if($re[1]>2){shell_exec($unix->find_program("kill")," -9 {$re[1]}");}	
			}
		}
	}
	
}


function writelogs_framework($text=null,$function=null,$file_source=null,$line=null){
	
	if($file_source==null){$file_source= __FILE__ ;}
	if(posix_getuid()==0){$prefix="[DAEMON]";}else{$prefix="[CONSOLE]";}
	if(function_exists("getmypid")){$pid=getmypid();}
	$date=date('H:i:s');
	if($line<>null){$line=" line $line";}
	$file_source=str_replace("/usr/share/artica-postfix/","",$file_source);
	$file_source=str_replace("/home/dtouzeau/developpement/artica-postfix/","",$file_source);
	if($GLOBALS["VERBOSE"]){echo "[$pid] $prefix::$function:: $text in $file_source$line\n";}
	$text="$date [$pid] $prefix::$function:: $text in $file_source$line";
	writeFrameOtherlogs("{$GLOBALS["ARTICALOGDIR"]}/framework.debug",$text);
}
function writeFrameOtherlogs($file,$text=null){
			@mkdir(dirname($file));
		    $logFile=$file;
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>100000){unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		
		@fwrite($f, "$text\n");
		@fclose($f);
}


class services_status{
	
	function services_status(){
		
	}
	
	public function artica_backup_status(){
		$unix=new unix();
		$pid=$unix->PIDOF("artica-backup");
		
		
	}
	
}


class kav4fsUnix{
	var	$MAIN=array();
	var $TASKS=array();
	
	
	function kav4fsUnix(){
		exec('/opt/kaspersky/kav4fs/bin/kav4fs-control --get-app-settings 2>&1',$results);
		writelogs_framework(count($results)." rows for --get-app-setting",__CLASS__."/".__FUNCTION__,__FILE__);
		$ini=new iniFrameWork();
		$ini->loadString("[INFOS]".@implode("\n",$results));
		$this->MAIN=$ini->_params["INFOS"];
	}
	
	
	function GetTaskList(){
		exec('/opt/kaspersky/kav4fs/bin/kav4fs-control --get-task-list 2>&1',$results);
		
		if(preg_match_all('#Name:\s+(.+?)\s+Id:\s+(.+?)\s+Class:\s+(.+?)\s+State:\s+([a-zA-Z0-9]+)#is',
		@implode("\n",$results),$re)){
			while (list ($index, $ID) = each ($re[2]) ){
				if(preg_match("#([0-9]+)\s+Runtime ID:\s+([0-9]+)#is",$ID,$ri)){
					$ID=$ri[1];
					$RUNTIMEID=$ri[2];
				}
				$array[$ID]=array(
					"NAME"=>$re[1][$index],"RUNTIMEID"=>$RUNTIMEID,
					"CLASS"=>$re[3][$index],"STATE"=>$re[4][$index]);
				
				$classes[$re[3][$index]][]=$ID;
			}
		
		}
		
		$this->TASKS["LIST"]=$array;
		$this->TASKS["CLASSES"]=$classes;
		
	}
	
	
}

class WakeOnLanClass{
 private $nic;
 var $error=array();
 	public function wake($mac){
 		$this->nic = fsockopen("udp://255.255.255.255", 9,$errno, $errstr);
 		if( !$this->nic ){
 			fclose($this->nic);
 			$this->error[]="fsockopen:\nERROR $errno \nudp 255.255.255.255\n {failed} $errstr";
 			return false;
 		}
 		else{
 			fwrite($this->nic, $this->pacquet($mac));
 			fclose($this->nic);
 			$this->error[]="{success}";
 			return true;
 		}
 }

 private function pacquet($Mac){
 	$packet = "";
 		for($i = 0; $i < 6; $i++){$packet .= chr(0xFF);}
 		for ($j = 0; $j < 16; $j++){
 		for($i = 0; $i < 12; $i=$i + 2){$packet .= chr(hexdec(substr($Mac, $i, 2)));}
 	}
 	return $packet;
 	}
 } 

class debuglogs{
	function debuglogs(){}
	
	public function events($text,$logFile,$phplog=false){
		$unix=new unix();
		$unix->events($text,$logFile,$phplog);
	}
	
}


function ini_set_verbosed(){
			ini_set('html_errors',0);
			ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			ini_set('error_prepend_string','');
			ini_set('error_append_string','');	
}



function system_admin_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}
	
	if($category==null){
		if(isset($GLOBALS["SYS_CAT"])){
			if($GLOBALS["SYS_CAT"]<>null){$category=$GLOBALS["SYS_CAT"];}
		}
	}
			
			
	
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/$md5.log", $serialize);
}

function rotate_admin_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}
	
	if($category==null){
		if(isset($GLOBALS["SYS_CAT"])){
			if($GLOBALS["SYS_CAT"]<>null){$category=$GLOBALS["SYS_CAT"];}
		}
	}
		
		
	
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/rotate_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/rotate_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rotate_admin_events/$md5.log", $serialize);
	
	
	
}


function system_failover_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}

	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events/$md5.log", $serialize);
	
}
function rotate_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}

	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/rotate_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/rotate_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rotate_events/$md5.log", $serialize);
	
}

function hotspot_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject);
		closelog();
	}



	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
}
function rdpproxy_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.watchdog.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];


	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	



}



function blackwhite_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	
	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];


	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	



}
function vsftpd_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}




	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.apache.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	$content="$text\In $file line $line\nServer Load $internal_load, Memory free: $MemFree";

	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}


	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/vsftpd_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/vsftpd_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/vsftpd_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}



}

function time_admin_mysql($severity,$function,$file,$line){
	if($line==0){return true;}
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/cron.1/".basename($file).".$function.$line.time";
	$TimeExec=$unix->file_time_min($TimeFile);
	if($TimeExec<15){return;}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	return true;
}

function apache_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}




	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}
	
	

	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.apache.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	$content="$text\In $file line $line\nServer Load $internal_load, Memory free: $MemFree";

	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	


	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$content;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/apache_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/apache_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/apache_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}



}

function cyrus_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	
	


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.watchdog.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	$content="$text\In $file line $line\nServer Load $internal_load, Memory free: $MemFree";

	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$content;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/cyrus_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/cyrus_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/cyrus_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	
	if($severity==0){
		if(!isset($GLOBALS["CyrusBackupNas"])){
			$sock=new sockets();
			$GLOBALS["CyrusBackupNas"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/CyrusBackupNas")));
		}
		
		if(!is_numeric($GLOBALS["CyrusBackupNas"]["notifs"])){return;}
		if($GLOBALS["CyrusBackupNas"]["notifs"]==1){$unix=new unix(); $unix->SendEmailConfigured($GLOBALS["CyrusBackupNas"],$subject,$content); }
	}
	
}

function events_to_meta($severity,$subject,$text,$file=null,$line=0,$function=null){
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	if($EnableArticaMetaClient==0){return;}
	@mkdir("/home/artica-meta",0755,true);
	if($severity>1){return;}
	if (is_file("/home/artica-meta/META_CLIENT_EVENTS.log")) {
		$size=filesize("/home/artica-meta/META_CLIENT_EVENTS.log");
		if($size>5000000){@unlink("/home/artica-meta/META_CLIENT_EVENTS.log");}
	}
	$unix=new unix();
	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	
	if(!time_admin_mysql($severity,__FUNCTION__,$file,$line)){return;}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	
	$array["uuid"]=$unix->GetUniqueID();
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	if($function<>null){
		$array["function"]=$function;
	}
	$array["severity"]=$severity;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	
	$serialize=serialize($array);
		
	$lineToSave=base64_encode(serialize($array));
	$f = @fopen("/home/artica-meta/META_CLIENT_EVENTS.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
	
	
	if(is_dir("{$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql")){
		$unix=new unix();
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf {$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql >/dev/null 2>&1 &");
	}
}


function meta_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	echo __LINE__."\n";
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}

	$unix=new unix();
	$unix->events($subject,"/var/log/artica-meta.log",false,$function,$line,$file);
	if($EnableArticaMetaClient==1){
		$unix->events($subject,"/var/log/artica-meta-agent.log",false,$function,$line,$file);
		events_to_meta($severity,$subject,$text,$file,$line,$function);
		return;
	}

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];

	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}

	$array["uuid"]=$unix->GetUniqueID();
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	
	
	if(is_dir("/home/artica-meta/events-notify-master")){
		$unix=new unix();
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf /home/artica-meta/events-notify-master >/dev/null 2>&1 &");
	}
	

	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	


}
function mysql_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/artica.watchdog.mysql.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];

	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	



}


function system_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	events_to_meta($severity,$subject,$text,$file,$line,$function);

	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.watchdog.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];


	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}


}
function nginx_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$unix=new unix();
	$unix->events($subject,"/var/log/nginx.watchdog.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];


	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/nginx_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/nginx_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/nginx_admin_mysql/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}


}

function postfix_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	events_to_meta($severity,$subject,$text,$file,$line,$function);

	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}
	if($GLOBALS["VERBOSE"]){echo "$subject $text\n";}
	$unix=new unix();
	$unix->events($subject,"/var/log/postfix.watchdog.log",false,$function,$line,$file);

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];


	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/postfix_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/postfix_admin_mysql",0777,true);}
	@chmod("{$GLOBALS["ARTICALOGDIR"]}/postfix_admin_mysql", 0777);
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/postfix_admin_mysql/$md5.log", $serialize);
	
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}


}

function squid_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$function=null;
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	events_to_meta($severity,$subject,$text,$file,$line,$function);
	
	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();	
	}
	
	$unix=new unix();
	$unix->events($subject,"/var/log/squid/artica.watchdog.log",false,$function,$line,$file);
	
	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	
	
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	$UfdbguardSMTPNotifs=_LoadSquidWatchdogNotif();
	
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql",0777,true);}
	@chmod("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql", 0777);
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql/$md5.log", $serialize);
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_mysql/$md5.log", $serialize);
	
	if(isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){
		if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1){squid_admin_notifs("$subject\n$text",$function,$file,$line,"proxy",$GLOBALS["SCHEDULE_ID"],$severity,true);}
	}
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	
	
}

function squid_admin_enforce($severity,$subject,$text,$file=null,$line=0){
	
	
	
	

	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text."\nLoad: $internal_load, Free Memory:".round($MemFree/1024);
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_enforce")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_enforce",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_enforce/$md5.log", $serialize);
	

}
function webupdate_admin_mysql($severity,$subject,$text,$file=null,$line=0){

	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	

	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text."\nLoad: $internal_load, Free Memory:".round($MemFree/1024);
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/webupdate_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/webupdate_admin_mysql",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/webupdate_admin_mysql/$md5.log", $serialize);


}

function squid_admin_purge($severity,$subject,$text,$file=null,$line=0){

	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	
	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text."\nLoad: $internal_load, Free Memory:".round($MemFree/1024);
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_purge")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_purge",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_purge/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}

}
function stats_admin_events($severity,$subject,$text,$file=null,$line=0){

	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	
	$array_mem=getSystemMemInfo();
	$array_load=sys_getloadavg();
	$MemFree=$array_mem["MemFree"];
	$internal_load=$array_load[0];
	
	
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text."\nLoad: $internal_load, Free Memory:".round($MemFree/1024);
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/stats_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/stats_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/stats_admin_events/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}

}
function artica_update_event($severity,$subject,$text,$file=null,$line=0){
if($GLOBALS["OUTPUT"]){echo $subject."\n";}
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	if(!is_numeric($line)){$line=0;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	if(basename($file)=="exec.squid.blacklists.php"){
		$unix=new unix();$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
	}
	
	
	
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}

	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/artica_update_task")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/artica_update_task",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/artica_update_task/$md5.log", $serialize);
	if(function_exists("meta_smtp_notif")){meta_smtp_notif($severity,$serialize);}
	

}


function meta_smtp_notif($severity,$serialize){
	if(!is_numeric($severity)){return;}
	if($severity==2){return;}
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$MetaClientNotifFatal=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MetaClientNotifFatal"));
	$MetaClientNotifWarning=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MetaClientNotifWarning"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	
	$COPY=false;
	if($EnableArticaMetaClient==1){
		if($severity==1){if($MetaClientNotifWarning==0){return;}}
		if($severity==0){if($MetaClientNotifFatal==0){return;}}
		$COPY=true;
	}
	if($EnableArticaMetaServer==1){
		$COPY=true;
	}
	if(!$COPY){return false;}
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/meta_smtp_notifs")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/meta_smtp_notifs",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/meta_smtp_notifs/$md5.log", $serialize);
	
}



function _LoadSquidWatchdogNotif(){
	if(isset($GLOBALS["UfdbguardSMTPNotifs"])){return $GLOBALS["UfdbguardSMTPNotifs"];}
	if(!is_file("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")){return array();}
	$GLOBALS["UfdbguardSMTPNotifs"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
	if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["smtp_info"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_info"]=0;}
	if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["smtp_warn"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_warn"]=0;}
	if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["smtp_critic"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_critic"]=1;}
	if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["warning_events"])){$GLOBALS["UfdbguardSMTPNotifs"]["warning_events"]=0;}
	
	
	
	if(!is_numeric($GLOBALS["UfdbguardSMTPNotifs"]["smtp_warn"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_warn"]=0;}
	if(!is_numeric($GLOBALS["UfdbguardSMTPNotifs"]["smtp_info"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_info"]=0;}
	if(!is_numeric($GLOBALS["UfdbguardSMTPNotifs"]["smtp_critic"])){$GLOBALS["UfdbguardSMTPNotifs"]["smtp_critic"]=1;}
	$GLOBALS["UfdbguardSMTPNotifs"]["SELECTED_TOT"]=$GLOBALS["UfdbguardSMTPNotifs"]["smtp_warn"]+$GLOBALS["UfdbguardSMTPNotifs"]["smtp_info"]+$GLOBALS["UfdbguardSMTPNotifs"]["smtp_critic"];
	if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"])){$GLOBALS["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($GLOBALS["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"])){$GLOBALS["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($GLOBALS["UfdbguardSMTPNotifs"]["warning_events"])){$GLOBALS["UfdbguardSMTPNotifs"]["warning_events"]=0;}
	return $GLOBALS["UfdbguardSMTPNotifs"];
	
	
}

function squid_admin_notifs($text,$function,$file,$line,$category,$taskid=0,$severity=null,$CheckSeverity=false){
	if(!is_numeric($line)){$line=0;}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	
	if(!time_admin_mysql($severity,$function,$file,$line)){return;}
	
	$UfdbguardSMTPNotifs=_LoadSquidWatchdogNotif();
	$NotificationsEnabled=$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"];
	if($NotificationsEnabled==0){return;}
	
	
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs",0755,true);}
	
	
	
	if($UfdbguardSMTPNotifs["SELECTED_TOT"]==3){
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs/$md5.log", $serialize);
		return;
	}
	
	if(!is_numeric($severity)){
		if(function_exists("syslog")){
			$file=$array["file"];
			$LOG_SEV=LOG_INFO;
			openlog($file, LOG_PID , LOG_SYSLOG);
			syslog($LOG_SEV, "**** WARNING no critical set *** [$function/$line]");
			closelog();
		}
		return;
		
	}
	if($severity==0){
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs/$md5.log", $serialize);
		return;
	}

	if($GLOBALS["UfdbguardSMTPNotifs"]["warning_events"]==0){return;}
	if($severity<>1){return;}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs/$md5.log", $serialize);
	
}




function system_user_events($uid,$text,$function,$file,$line,$category,$taskid=0){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["uid"]=$uid;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_users_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_users_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_users_events/$md5.log", $serialize);	
	
}




function sarg_admin_events($text,$function,$file=null,$line=0,$category=null,$taskid=0){
	$date=date("Y-m-d H:i:s");
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}
	
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events/$md5.log", $serialize);
	
}


function squid_status_sub_percentage($text,$purc){


	$array["TITLE"]=$text;
	$array["POURC"]=$purc;

	@file_put_contents("/usr/share/artica-postfix/ressources/squid.stats.sub.progress.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/squid.stats.sub.progress.inc",0755);


}

function ufdbguard_admin_events($text,$function,$file=null,$line=0,$category=null,$taskid=0){
	
	
	$date=date("Y-m-d H:i:s");
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}	
	
	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text ." [$function/$line]");
		closelog();
	}
	


	
	
	if(basename($file)=="exec.squid.stats.php"){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=@getmypid();}
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		
		if($GLOBALS["AS_ROOT"]){
			$date=@date("H:i:s");
			$logFile="/var/log/artica-squid-stats.log";
			if(is_file($logFile)){
				$size=filesize($logFile);
				if($size>5000000){unlink($logFile);}
			}
			$me="exec.squid.stats.php";
			$f = fopen($logFile, 'a');
			fwrite($f, "$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function::$line: $text\n");
			fclose($f);
		}
	}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";	
	
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events/$md5.log", $serialize);
	
	
	$update["exec.update.squid.tlse.php"]=true;
	$update["exec.update.blacklist.instant.php"]=true;
	$update["exec.squid.blacklists.php"]=true;
	
	if($file=="exec.squidlogs.purge.php"){
		if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid-stats-purge")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid-stats-purge",0755,true);}
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid-stats-purge/$md5.log", $serialize);
	}
	
	if(!isset($update[basename($file)])){return;}
	if($update[basename($file)]){
		if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_update_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_update_events",0755,true);}
		@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_update_events/$md5.log", $serialize);
	
	}	
	
	
}
function mysql_admin_events($text,$function,$file,$line,$category=null){
	$file=basename($file);
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events/$md5.log", $serialize);
	
	
	
}
function update_events($text,$function,$file,$line,$category=null){
	$file=basename($file);
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/update_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/update_admin_events",0755,true);}
	@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/update_admin_events/$md5.log", $serialize);
	
}

function unix_system_kill_force($pid){
	if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}
	if(!is_numeric($pid)){
		_unix_system_kill_debug("[9]: PID $pid is not a numeric! from  $file ($function) in line $line");
		return;
	}
	if($pid<5){
		_unix_system_kill_debug("[9]: PID $pid is under 5!! from  $file ($function) in line $line");
		return;
	}
	if(!is_dir("/proc/$pid")){return;}
	
	$unix=new unix();
	
	
	
	$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
	
	if($cmdline==null){
		$ps=$unix->find_program("ps");
		$grep=$unix->find_program("grep");
		$cmdline=exec("$ps aux|$grep -E \".*?\s+$pid\s+\" 2>&1");
	}
	
	$unix->KILL_PROCESS($pid,9);
	_unix_system_kill_debug("[9]: PID $pid killed \"$cmdline\" from  $file ($function) in line $line");


}

function _unix_system_kill_debug($line){
	
	if (is_file("/var/log/killed-proc.log")) {
		$size=filesize("/var/log/killed-proc.log");
		if($size>1000000){@unlink("/var/log/killed-proc.log");}
	}
	
	$f = @fopen("/var/log/killed-proc.log", 'a');
	$lineToSave=date("Y-m-d H:i:s")." " .$line;
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
}

function unix_system_kill($pid){
	$unix=new unix();
	if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}
	
	
	if(!is_numeric($pid)){
		_unix_system_kill_debug("[?]: PID $pid is not a numeric! from  $file ($function) in line $line");
		return;
	}
	if($pid<5){
		_unix_system_kill_debug("[?]: PID $pid is under 5!! from  $file ($function) in line $line");
		return;
	}
	$kill=$unix->find_program("kill");
	$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
	
	if($cmdline==null){
		$ps=$unix->find_program("ps");
		$grep=$unix->find_program("grep");
		$cmdline=exec("$ps aux|$grep -E \".*?\s+$pid\s+\" 2>&1");
	}
	
	shell_exec("$kill $pid >/dev/null 2>&1");
	_unix_system_kill_debug("[?]: PID $pid smoothly killed \"$cmdline\" from  $file ($function) in line $line");


}
function unix_system_HUP($pid){
	$unix=new unix();
	if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}


	if(!is_numeric($pid)){
		_unix_system_kill_debug("[?]: PID $pid is not a numeric! from  $file ($function) in line $line");
		return;
	}
	if($pid<5){
		_unix_system_kill_debug("[?]: PID $pid is under 5!! from  $file ($function) in line $line");
		return;
	}
	
	
	$kill=$unix->find_program("kill");
	$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
	
	if($cmdline==null){
		$ps=$unix->find_program("ps");
		$grep=$unix->find_program("grep");
		$cmdline=exec("$ps aux|$grep -E \".*?\s+$pid\s+\" 2>&1");
	}
	
	shell_exec("$kill -HUP $pid >/dev/null 2>&1");
	_unix_system_kill_debug("[?]: PID $pid smoothly HUP \"$cmdline\" from  $file ($function) in line $line");


}

function unix_rm($path){
	
	
}


function recursive_remove_directory($directory, $empty=FALSE){
	if($directory==null){return false;}
	if(substr($directory,-1) == '/'){ $directory = substr($directory,0,-1); }
	if(!file_exists($directory) || !is_dir($directory)) { return FALSE; }
	if(!is_readable($directory)){return FALSE;}

	$handle = opendir($directory);
	while (FALSE !== ($item = readdir($handle))){
		if($item == '.'){continue;}
		if($item == '..'){continue;}
		
		$path = $directory.'/'.$item;
		if(is_dir($path)) {
			recursive_remove_directory($path);
			continue;
		}
		unlink($path);
		
	}
		
		
	closedir($handle);

		
	if($empty == FALSE){
		if(!rmdir($directory)){
			return FALSE;
		}
	}
	return TRUE;
	
}




?>