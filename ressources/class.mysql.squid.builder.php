<?php
if(isset($_SESSION["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("date_default_timezone_set")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!isset($GLOBALS["FULL_DEBUG"])){$GLOBALS["FULL_DEBUG"]=false;}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.simple.image.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.highcharts.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.squid.stats.tools.inc");
include_once(dirname(__FILE__)."/class.analyze-page.inc");
include_once(dirname(__FILE__)."/class.stats-appliance.inc");
include_once(dirname(__FILE__)."/class.mysql-dump.inc");
ini_set("mysql.connect_timeout",60);

class mysql_squid_builder{

	public $ok=false;
	public $mysql_error;
	public $UseMysql=true;
	public $database="squidlogs";
	public $mysql_server="127.0.0.1";
	public $mysql_admin;
	public $mysql_password;
	public $mysql_port;
	public $MysqlFailed=false;
	public $mysql_connection;
	public $EnableRemoteStatisticsAppliance=0;
	public $EnableRemoteSyslogStatsAppliance=0;
	private $squidEnableRemoteStatistics=0;
	private $sql;
	public $DisableArticaProxyStatistics=0;
	public $EnableSquidRemoteMySQL=0;
	public $ProxyUseArticaDB=0;
	public $UseStandardMysql=false;
	public $EnableSargGenerator=0;
	public $tasks_array=array();
	public $tasks_explain_array=array();
	public $tasks_remote_appliance=array();
	public $CACHE_AGES=array();
	public $CACHES_RULES_TYPES=array();
	public $tasks_processes=array();
	public $tasks_disabled=array();
	public $last_id;
	public $acl_GroupType=array();
	public $acl_GroupType_WPAD=array();
	public $acl_GroupType_iptables=array();
	public $acl_GroupType_Firewall_in=array();
	public $acl_GroupType_Firewall_out=array();
	public $acl_GroupType_Firewall_port=array();
	public $acl_NTLM=array();
	public $acl_ARRAY_NO_ITEM=array();
	public $SquidActHasReverse=0;
	public $AVAILABLE_METHOD=array();
	public $acl_GroupTypeDynamic=array();
	public $PROXY_PAC_TYPES=array();
	public $PROXY_PAC_TYPES_EXPLAIN=array();
	public $SocketName="/var/run/mysqld/mysqld.sock";
	public $SocketPath="";
	public $DisableLocalStatisticsTasks=0;
	private $NOCHDB=array("mysql"=>true);
	private $EnableKerbAuth=0;
	private $UseDynamicGroupsAcls=1;
	public $MYSQL_CMDLINES=null;
	public $MYSQL_DATA_DIR="/var/lib/mysql";
	public $mysql_affected_rows;
	public $report_types=array();
	public $MySQLConnectionType=0;
	private $BD_CONNECT_ERROR;
	private $PDO_DSN;
	
	function mysql_squid_builder($local=false){
		$LDAP_EXTERNAL_AUTH=0;
		if(function_exists("getLocalTimezone")){
			@date_default_timezone_set(getLocalTimezone());
		}
		
		if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
		if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
		
		
		$ini=new Bs_IniHandler();
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			$sock=new sockets();
			$ini->loadString($sock->GET_INFO('ArticaSquidParameters'));
			if(isset($ini->_params)){$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}
		
		if(isset($ini->_params)){
			$LDAP_EXTERNAL_AUTH=intval($ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"]);
		}
		
		

		if(!isset($GLOBALS["DEBUG_SQL"])){$GLOBALS["DEBUG_SQL"]=false;}
		$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
		
		if($EnableArticaMetaServer==1){
			$LDAP_EXTERNAL_AUTH=1;
		}
		
		
		$this->report_types[1]="{by_categories}";
		$this->report_types[2]="{by_websites}";
		
		$this->acl_NTLM["src"]="{src_addr}";
		$this->acl_NTLM["arp"]="{ComputerMacAddress}";
		$this->acl_NTLM["dstdomain"]="{dstdomain}";
		$this->acl_NTLM["dst"]="{dst_addr}";
		$this->acl_NTLM["port"]="{destination_port}";
		
		$this->acl_GroupType["all"]="{all}";
		$this->acl_GroupType["src"]="{src_addr}";
		$this->acl_GroupType["srcdomain"]="{srcdomain}";
		$this->acl_GroupType["arp"]="{ComputerMacAddress}";
		$this->acl_GroupType["dstdomain"]="{dstdomain}";
		$this->acl_GroupType["dstdom_regex"]="{dstdomain_regex}";
		$this->acl_GroupType["url_regex_extensions"]="{url_regex_extensions}";
		$this->acl_GroupType["dst"]="{dst_addr}";
		$this->acl_GroupType["maxconn"]="{acl_maxconn}";
		$this->acl_GroupType["max_user_ip"]="{acl_max_user_ip}";
		$this->acl_GroupType["proxy_auth"]="{members}";
		$this->acl_GroupType["proxy_auth_ads"]="{dynamic_activedirectory_group}";
		$this->acl_GroupType["proxy_auth_statad"]="{static_activedirectory_group}";
		$this->acl_GroupType["proxy_auth_tagad"]="{acl_tag_adgroup}";
		$this->acl_GroupType["proxy_auth_multiad"]="{multiple_active_directory_groups}";
		$this->acl_GroupType["tag_categories"]="{acl_tag_categories}";
		
		
		if($LDAP_EXTERNAL_AUTH==1){
			$this->acl_GroupType["proxy_auth_ldap"]="{dynamic_ldap_group}";
		}
		
	
		
		$this->acl_GroupType["Smartphones"]="{smartphones}";
		$this->acl_GroupType["browser"]="{browser}";
		$this->acl_GroupType["NudityScan"]="{nudityScan}";
		$this->acl_GroupType["ssl_sni"]="{acl_ssl_sni}";
		$this->acl_GroupType["time"]="{DateTime}";
		$this->acl_GroupType["ext_user"]="{ext_user}";
		$this->acl_GroupType["method"]="{connection_method}";
		$this->acl_GroupType["FTP"]="FTP {protocol}";
		$this->acl_GroupType["dynamic_acls"]="{dynamic_acls}";
		$this->acl_GroupType["req_mime_type"]="{req_mime_type}";
		$this->acl_GroupType["rep_mime_type"]="{rep_mime_type}";
		$this->acl_GroupType["rep_header_filename"]="{rep_header_filename}";
		$this->acl_GroupType["url_regex"]="{url_regex_acl2}";
		$this->acl_GroupType["urlpath_regex"]="{url_regex_acl3}";
		$this->acl_GroupType["referer_regex"]="{referer_regex}";
		$this->acl_GroupType["radius_auth"]="{radius_auth}";
		$this->acl_GroupType["ad_auth"]="{basic_ad_auth}";
		$this->acl_GroupType["ldap_auth"]="{basic_ldap_auth}";
		$this->acl_GroupType["hotspot_auth"]="{hotspot_auth}";
		$this->acl_GroupType["port"]="{destination_port}";
		$this->acl_GroupType["myportname"]="{local_proxy_port}";
		$this->acl_GroupType["clt_conn_tag"]="{statistics_virtual_group}";
		$this->acl_GroupType["categories"]="{artica_categories}";
		$this->acl_GroupType["teamviewer"]="{macro}: TeamViewer";
		$this->acl_GroupType["whatsapp"]="{macro}: whatsapp";
		$this->acl_GroupType["skype"]="{macro}: Skype";
		$this->acl_GroupType["youtube"]="{macro}: Youtube";
		$this->acl_GroupType["office365"]="{macro}: Office 365";
		$this->acl_GroupType["quota_time"]="{quota_time}";
		$this->acl_GroupType["quota_size"]="{quota_size}";
		$this->acl_GroupType["google"]="{macro}: Google {websites}";
		$this->acl_GroupType["google_ssl"]="{macro}: Google SSL {websites}";
		$this->acl_GroupType["AntiTrack"]="{macro}: {ads_and_trackers}";
		$this->acl_GroupType["dropbox"]="{macro}: DropBox networks";
		
		
		$this->acl_ARRAY_NO_ITEM["clt_conn_tag"]=true;
		$this->acl_ARRAY_NO_ITEM["FTP"]=true;
		$this->acl_ARRAY_NO_ITEM["proxy_auth_ads"]=true;
		$this->acl_ARRAY_NO_ITEM["proxy_auth_tagad"]=true;
		
		$this->acl_ARRAY_NO_ITEM["proxy_auth_statad"]=true;
		$this->acl_ARRAY_NO_ITEM["NudityScan"]=true;
		$this->acl_ARRAY_NO_ITEM["all"]=true;
		$this->acl_ARRAY_NO_ITEM["dynamic_acls"]=true;
		$this->acl_ARRAY_NO_ITEM["categories"]=true;
		$this->acl_ARRAY_NO_ITEM["radius_auth"]=true;
		$this->acl_ARRAY_NO_ITEM["ad_auth"]=true;
		$this->acl_ARRAY_NO_ITEM["ldap_auth"]=true;
		$this->acl_ARRAY_NO_ITEM["hotspot_auth"]=true;
		$this->acl_ARRAY_NO_ITEM["AntiTrack"]=true;
		$this->acl_ARRAY_NO_ITEM["teamviewer"]=true;
		$this->acl_ARRAY_NO_ITEM["whatsapp"]=true;
		$this->acl_ARRAY_NO_ITEM["office365"]=true;
		$this->acl_ARRAY_NO_ITEM["skype"]=true;
		$this->acl_ARRAY_NO_ITEM["youtube"]=true;
		$this->acl_ARRAY_NO_ITEM["google"]=true;
		$this->acl_ARRAY_NO_ITEM["dropbox"]=true;
		$this->acl_ARRAY_NO_ITEM["google_ssl"]=true;
		$this->acl_ARRAY_NO_ITEM["proxy_auth_ldap"]=true;
		$this->acl_ARRAY_NO_ITEM["Smartphones"]=true;
		
		$this->acl_GroupType_WPAD["all"]="{all}";
		$this->acl_GroupType_WPAD["src"]="{ipsrc}";
		$this->acl_GroupType_WPAD["srcdomain"]="{srcdomain}";
		$this->acl_GroupType_WPAD["dstdomain"]="{dstdomain}";
		$this->acl_GroupType_WPAD["dst"]="{dst}";
		$this->acl_GroupType_WPAD["browser"]="{browser}";
		$this->acl_GroupType_WPAD["time"]="{DateTime}";
		$this->acl_GroupType_WPAD["port"]="{destination_port}";
		
		$this->acl_GroupType_iptables["src"]="{addr}";
		$this->acl_GroupType_iptables["dst"]="{dst}";
		$this->acl_GroupType_iptables["arp"]="{ComputerMacAddress}";
		$this->acl_GroupType_iptables["port"]="{destination_port}";
		$this->acl_GroupType_iptables["teamviewer"]="{macro}: TeamViewer {networks}";
		$this->acl_GroupType_iptables["whatsapp"]="{macro}: whatsapp {networks}";
		$this->acl_GroupType_iptables["office365"]="{macro}: Office365 {networks}";
		$this->acl_GroupType_iptables["skype"]="{macro}: Skype {networks}";
		$this->acl_GroupType_iptables["youtube"]="{macro}: YouTube {networks}";
		$this->acl_GroupType_iptables["google"]="{macro}: Google {networks}";
		$this->acl_GroupType_iptables["google_ssl"]="{macro}: Google SSL {networks}";
		$this->acl_GroupType_iptables["dropbox"]="{macro}: DropBox {networks}";
		
		
		$this->acl_GroupType_Firewall_in["src"]="{addr}";
		$this->acl_GroupType_Firewall_in["arp"]="{ComputerMacAddress}";
		
		$this->acl_GroupType_Firewall_out["dst"]="{dst}";
		$this->acl_GroupType_Firewall_out["teamviewer"]="TeamViewer - {macro}";
		$this->acl_GroupType_Firewall_out["whatsapp"]="whatsapp - {macro}";
		$this->acl_GroupType_Firewall_out["office365"]="office 365 - {macro}";
		$this->acl_GroupType_Firewall_out["skype"]="Skype - {macro}";
		$this->acl_GroupType_Firewall_out["youtube"]="YouTube - {macro}";
		$this->acl_GroupType_Firewall_out["google"]="Google - {macro}";
		
		
		
		$this->acl_GroupType_Firewall_port["port"]="{destination_port}";
		
		$this->acl_GroupTypeDynamic[0]="{mac}";
		$this->acl_GroupTypeDynamic[1]="{ipaddr}";
		$this->acl_GroupTypeDynamic[3]="{hostname}";
		$this->acl_GroupTypeDynamic[2]="{member}";
		$this->acl_GroupTypeDynamic[4]="{webserver}";
		
		
		$this->AVAILABLE_METHOD["GET"]=true;
		$this->AVAILABLE_METHOD["PUT"]=true;
		$this->AVAILABLE_METHOD["POST"]=true;
		$this->AVAILABLE_METHOD["HEAD"]=true;
		$this->AVAILABLE_METHOD["CONNECT"]=true;
		$this->AVAILABLE_METHOD["TRACE"]=true;
		$this->AVAILABLE_METHOD["OPTIONS"]=true;
		$this->AVAILABLE_METHOD["DELETE"]=true;
		$this->AVAILABLE_METHOD["PROPFIND"]=true;
		$this->AVAILABLE_METHOD["PROPPATCH"]=true;
		$this->AVAILABLE_METHOD["MKCOL"]=true;
		$this->AVAILABLE_METHOD["COPY"]=true;
		$this->AVAILABLE_METHOD["MOVE"]=true;
		$this->AVAILABLE_METHOD["LOCK"]=true;
		$this->AVAILABLE_METHOD["UNLOCK"]=true;
		$this->AVAILABLE_METHOD["BMOVE"]=true;
		$this->AVAILABLE_METHOD["BDELETE"]=true;
		$this->AVAILABLE_METHOD["BPROFIND"]=true;
		$this->AVAILABLE_METHOD["PUT"]=true;
		$this->AVAILABLE_METHOD["REPORT"]=true;
		$this->AVAILABLE_METHOD["OPTIONS"]=true;
		$this->AVAILABLE_METHOD["PROPFIND"]=true;
		$this->AVAILABLE_METHOD["TUNNEL"]=true;
		$this->AVAILABLE_METHOD["PROPFIND"]=true;
		$this->AVAILABLE_METHOD["PROPPATCH"]=true;
		$this->AVAILABLE_METHOD["MKCOL"]=true;
		$this->AVAILABLE_METHOD["COPY"]=true;
		$this->AVAILABLE_METHOD["MOVE"]=true;
		$this->AVAILABLE_METHOD["LOCK"]=true;
		$this->AVAILABLE_METHOD["UNLOCK"]=true;
		$this->AVAILABLE_METHOD["MKDIR"]=true;
		$this->AVAILABLE_METHOD["INDEX"]=true;
		$this->AVAILABLE_METHOD["RMDIR"]=true;
		$this->AVAILABLE_METHOD["LINK"]=true;
		$this->AVAILABLE_METHOD["UNLINK"]=true;
		$this->AVAILABLE_METHOD["PATCH"]=true;
		$this->AVAILABLE_METHOD["BCOPY"]=true;
		$this->AVAILABLE_METHOD["BDELETE"]=true;
		$this->AVAILABLE_METHOD["BMOVE"]=true;
		$this->AVAILABLE_METHOD["BPROPPATCH"]=true;
		$this->AVAILABLE_METHOD["MKCO"]=true;
		$this->AVAILABLE_METHOD["POLL"]=true;
		$this->AVAILABLE_METHOD["SEARCH"]=true;
		$this->AVAILABLE_METHOD["SUBSCRIBE"]=true;

		$this->PROXY_PAC_TYPES[null]="{select}";
		$this->PROXY_PAC_TYPES["shExpMatch"]="{shExpMatch2}";
		$this->PROXY_PAC_TYPES["shExpMatchRegex"]="{shExpMatchRegex}";
		$this->PROXY_PAC_TYPES["isInNetMyIP"]="{isInNetMyIP}";
		$this->PROXY_PAC_TYPES["isInNet"]="{isInNet2}";
		
		$this->PROXY_PAC_TYPES_EXPLAIN["shExpMatch"]="{shExpMatch2_explain}";
		$this->PROXY_PAC_TYPES_EXPLAIN["shExpMatchRegex"]="{shExpMatchRegex_explain}";
		$this->PROXY_PAC_TYPES_EXPLAIN["isInNetMyIP"]="{isInNetMyIP_explain}";
		$this->PROXY_PAC_TYPES_EXPLAIN["isInNet"]="{isInNet2_explain}";		
		
		$this->CACHE_AGES[0]="{none}";
		$this->CACHE_AGES[30]="30 {minutes}";
		$this->CACHE_AGES[60]="1 {hour}";
		$this->CACHE_AGES[120]="2 {hours}";
		$this->CACHE_AGES[360]="6 {hours}";
		$this->CACHE_AGES[720]="12 {hours}";
		$this->CACHE_AGES[1440]="1 {day}";
		$this->CACHE_AGES[2880]="2 {days}";
		$this->CACHE_AGES[4320]="3 {days}";
		$this->CACHE_AGES[10080]="1 {week}";
		$this->CACHE_AGES[20160]="2 {weeks}";
		$this->CACHE_AGES[43200]="1 {month}";
		$this->CACHE_AGES[525600]="1 {year}";
		
		$this->CACHES_RULES_TYPES[1]="{domains}";
		$this->CACHES_RULES_TYPES[2]="{extensions}";
		$this->CACHES_RULES_TYPES[3]="{shExpMatchRegex}";
		
		
		if($local==true){$this->squidEnableRemoteStatistics=0;$this->EnableSquidRemoteMySQL=0;}
		
		$this->PrepareMySQLClass();
		
		if(!$this->DATABASE_EXISTS("squidlogs")){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: Patching tables -> squidlogs...\n";}
			$this->CREATE_DATABASE("squidlogs");
			$this->CheckTables();
		}
		

		$this->fill_task_array();
		$this->fill_tasks_disabled();
		if(!$this->TestingConnection()){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: TestingConnection -> FAILED Stamp MySQL to FAILED\n";}
			$this->MysqlFailed=true;
		}
		
	}
	
	public function time_to_date($xtime,$time=false){
		if(!class_exists("templates")){return;}
		$tpl=new templates();
		$dateT=date("{l} {F} d",$xtime);
		if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
		return $tpl->_ENGINE_parse_body($dateT);
	
	}
	
	public function mysql_error_html(){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}		
		return "<p class=text-error>$this->mysql_error<br>$this->sql<br><i>$called</i></p>";
		
	}
	
	public function mysql_error_jsdiv($id){
		$html=$this->mysql_error_html();
		$html=str_replace("'", "\'", $html);
		$html=str_replace("\n", "<br>", $html);
		header("content-type: application/x-javascript");
		echo "document.getElementById('$id').innerHTML='$html';";
		
	}
	
	
	
	
	public function dump_table_to_file($tablename,$filename,$field_to_add=null,$field_type=null,$field_data=null){
		$dump=new class_mysql_dump($this->database,$this->mysql_connection,$filename);
		if($field_to_add<>null){
			$dump->add_fields_values($field_to_add,$field_data,$field_type);
		}
		$dump->set_OnlyTableName($tablename);
		$dump->doDump();
	}
	
	
	
	public function GRANT_PRIVS($hostname,$username,$password){
		$this->BD_CONNECT();
		$ok=@mysql_select_db("mysql",$this->mysql_connection);
		if (!$ok){
				if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_errornum=$errnum;
				$this->mysql_error="Error Number ($errnum) ($des)";
				$this->ok=false;
				return false;
			}
			
			
		$sql="SELECT User FROM user WHERE Host='$hostname' AND User='$username'";
		$ligne=@mysql_fetch_array(@mysql_query($sql,$this->mysql_connection));
		if(trim($ligne["User"])==null){
			if(!$this->EXECUTE_SQL("CREATE USER '$username'@'$hostname' IDENTIFIED BY '$password';")){
				return false;
			}
			
			
			if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){
				return false;
			}
			$this->EXECUTE_SQL("FLUSH PRIVILEGES;");
			return true;
		}
		
		if(!$this->EXECUTE_SQL("GRANT ALL PRIVILEGES ON * . * TO '$username'@'$hostname' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0")){return false;}
		$this->EXECUTE_SQL("FLUSH PRIVILEGES;");		
		return true;
	}
	
	private function PrepareMySQLClassMemory(){
		if(!isset($GLOBALS["PrepareMySQLClassMemory"])){
			if(!class_exists("sockets")){include_once(dirname(__FILE__)."/class.sockets.inc");}
			if(!class_exists("usersMenus")){include_once(dirname(__FILE__)."/class.users.menus.inc");}
			$sock=new sockets();
			
			$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]=$sock->GET_INFO("ChangeMysqlDir");
			if($GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]==null){$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"]="/var/lib/mysql";}
			$GLOBALS["PrepareMySQLClassMemory"]["squidEnableRemoteStatistics"]=$sock->GET_INFO("squidEnableRemoteStatistics");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteStatisticsAppliance"]=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"]=$sock->GET_INFO("EnableSquidRemoteMySQL");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteSyslogStatsAppliance"]=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"]=$sock->GET_INFO("squidRemostatisticsServer");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"]=$sock->GET_INFO("squidRemostatisticsPort");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"]=$sock->GET_INFO("squidRemostatisticsUser");
			$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"]=$sock->GET_INFO("squidRemostatisticsPassword");
			$GLOBALS["PrepareMySQLClassMemory"]["SquidActHasReverse"]=$sock->GET_INFO("SquidActHasReverse");
			$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"]=$sock->GET_INFO("ProxyUseArticaDB");
			$GLOBALS["PrepareMySQLClassMemory"]["DisableArticaProxyStatistics"]=$sock->GET_INFO("DisableArticaProxyStatistics");
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::ProxyUseArticaDB = {$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"]}\n";}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::EnableSquidRemoteMySQL = {$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"]}\n";}
			$GLOBALS["PrepareMySQLClassMemory"]["DisableLocalStatisticsTasks"]=$sock->GET_INFO("DisableLocalStatisticsTasks");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableSargGenerator"]=$sock->GET_INFO("EnableSargGenerator");
			$GLOBALS["PrepareMySQLClassMemory"]["EnableKerbAuth"]=$sock->GET_INFO("EnableKerbAuth");
			$GLOBALS["PrepareMySQLClassMemory"]["UseDynamicGroupsAcls"]=1;
			
			
		}
		
		$this->squidEnableRemoteStatistics=$GLOBALS["PrepareMySQLClassMemory"]["squidEnableRemoteStatistics"];
		$this->EnableRemoteStatisticsAppliance=$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteStatisticsAppliance"];
		$this->EnableSquidRemoteMySQL=$GLOBALS["PrepareMySQLClassMemory"]["EnableSquidRemoteMySQL"];
		$this->EnableRemoteSyslogStatsAppliance=$GLOBALS["PrepareMySQLClassMemory"]["EnableRemoteSyslogStatsAppliance"];
		$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
		$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
		$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
		$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
		$this->SquidActHasReverse=$GLOBALS["PrepareMySQLClassMemory"]["SquidActHasReverse"];
		$this->ProxyUseArticaDB=$GLOBALS["PrepareMySQLClassMemory"]["ProxyUseArticaDB"];
		$this->DisableLocalStatisticsTasks=$GLOBALS["PrepareMySQLClassMemory"]["DisableLocalStatisticsTasks"];
		$this->DisableArticaProxyStatistics=$GLOBALS["PrepareMySQLClassMemory"]["DisableArticaProxyStatistics"];
		$this->EnableSargGenerator=$GLOBALS["PrepareMySQLClassMemory"]["EnableSargGenerator"];
		$this->EnableKerbAuth=$GLOBALS["PrepareMySQLClassMemory"]["EnableKerbAuth"];
		$this->UseDynamicGroupsAcls=$GLOBALS["PrepareMySQLClassMemory"]["UseDynamicGroupsAcls"];
		
		
		if(!is_numeric($this->EnableSquidRemoteMySQL)){$this->EnableSquidRemoteMySQL=0;}
		if(!is_numeric($this->squidEnableRemoteStatistics)){$this->squidEnableRemoteStatistics=0;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->EnableRemoteSyslogStatsAppliance)){$this->EnableRemoteSyslogStatsAppliance=0;}
		if(!is_numeric($this->ProxyUseArticaDB)){$this->ProxyUseArticaDB=0;}
		if(!is_numeric($this->DisableLocalStatisticsTasks)){$this->DisableLocalStatisticsTasks=0;}
		if($this->EnableRemoteStatisticsAppliance==1){$this->squidEnableRemoteStatistics=0;}
		if(!is_numeric($this->DisableArticaProxyStatistics)){$this->DisableArticaProxyStatistics=0;}
		if(!is_numeric($this->EnableSargGenerator)){$this->EnableSargGenerator=0;}
		if(!is_numeric($this->SquidActHasReverse)){$this->SquidActHasReverse=0;}
		
		
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=1;}
		
		if($GLOBALS["DEBUG"]){
			echo "squidEnableRemoteStatistics=$this->squidEnableRemoteStatistics\n";
			echo "EnableRemoteStatisticsAppliance=$this->EnableRemoteStatisticsAppliance\n";
			echo "EnableSquidRemoteMySQL=$this->EnableSquidRemoteMySQL\n";
		}
		
	}
	
	
	
	private function PrepareMySQLClass(){
		
		$this->PrepareMySQLClassMemory();
		$this->MYSQL_DATA_DIR=$GLOBALS["PrepareMySQLClassMemory"]["MYSQL_DATA_DIR"];
		if($this->MYSQL_DATA_DIR==null){$this->MYSQL_DATA_DIR="/var/lib/mysql";}
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		if(isset($_SESSION["MYSQL_PARAMETERS"])){
		unset($_SESSION["MYSQL_PARAMETERS"]);
		}

		
		
		
		if($this->EnableSquidRemoteMySQL==1){
			
			$pass=null;
			$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
			$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
			$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
			$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
			$def["mysql_admin"]=$squidRemostatisticsUser;
			$def["mysql_password"]=$squidRemostatisticsPassword;
			$def["mysql_port"]=$squidRemostatisticsPort;
			$def["mysql_server"]=$squidRemostatisticsServer;
			$def["MySQLConnectionType"]=2;
			$def["SocketPath"]=null;
			$def["TryTCP"]=true;
			$this->mysql_admin=$squidRemostatisticsUser;
			$this->mysql_password=$squidRemostatisticsPassword;
			$this->mysql_port=$squidRemostatisticsPort;
			$this->mysql_server=$squidRemostatisticsServer;
			$this->MySQLConnectionType=2;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			$this->UseStandardMysql=false;
			
			
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__.":: squidRemostatisticsUser =$squidRemostatisticsUser\n";}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__.":: squidRemostatisticsPassword =$squidRemostatisticsPassword\n";}
			
			if(strlen($squidRemostatisticsPassword)>1){
				$mysql_password=$this->shellEscapeChars($squidRemostatisticsPassword);
				$pass=" -p$mysql_password";
			}
			$this->MYSQL_CMDLINES="--protocol=tcp --host=$squidRemostatisticsServer --port=$squidRemostatisticsPort -u $squidRemostatisticsUser$pass";
			
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$def["mysql_admin"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$def["mysql_password"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$def["SocketPath"];
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=$def["TryTCP"];
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;			
			
			return;			
		}
		
		
		if($this->squidEnableRemoteStatistics==1){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>squidEnableRemoteStatistics = 1</strong><br>\n";}
			$squidRemostatisticsServer=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsServer"];
			$squidRemostatisticsPort=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPort"];
			$squidRemostatisticsUser=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsUser"];
			$squidRemostatisticsPassword=$GLOBALS["PrepareMySQLClassMemory"]["squidRemostatisticsPassword"];
			
			$def["mysql_admin"]=$squidRemostatisticsUser;
			$def["mysql_password"]=$squidRemostatisticsPassword;
			$def["mysql_port"]=$squidRemostatisticsPort;
			$def["mysql_server"]=$squidRemostatisticsServer;
			$def["SocketPath"]=null;
			$def["TryTCP"]=true;
			$this->MySQLConnectionType=2;
			$this->mysql_admin=$squidRemostatisticsUser;
			$this->mysql_password=$squidRemostatisticsPassword;
			$this->mysql_port=$squidRemostatisticsPort;
			$this->mysql_server=$squidRemostatisticsServer;
			$this->mysql_admin=$this->mysql_admin;
			$this->mysql_password=$this->mysql_password;
			$this->mysql_port=$this->mysql_port;
			$this->mysql_server=$this->mysql_server;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			
			if(strlen($squidRemostatisticsPassword)>1){
				$mysql_password=$this->shellEscapeChars($squidRemostatisticsPassword);
				$pass=" -p$mysql_password";
			}
			$this->MYSQL_CMDLINES="--protocol=tcp --host=$squidRemostatisticsServer --port=$squidRemostatisticsPort -u $squidRemostatisticsUser$pass";
			
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$squidRemostatisticsUser;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$squidRemostatisticsPassword;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$squidRemostatisticsPort;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$squidRemostatisticsServer;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=null;
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=true;
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
			$this->UseStandardMysql=false;
			return;
		}
		
		if($this->ProxyUseArticaDB==1){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>ProxyUseArticaDB = 1</strong><br>\n";}
			$this->MYSQL_DATA_DIR="/opt/squidsql/data";
			$def["SocketPath"]="/var/run/mysqld/squid-db.sock";
			$this->SocketPath="/var/run/mysqld/squid-db.sock";
			$def["mysql_admin"]="root";
			$def["mysql_password"]=null;	
			$def["TryTCP"]=false;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_server="127.0.0.1";	
			$this->MYSQL_CMDLINES="--protocol=socket --socket=/var/run/mysqld/squid-db.sock -u root";
			$this->MySQLConnectionType=1;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$def["mysql_admin"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$def["mysql_password"];
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
			$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
			$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$def["SocketPath"];
			$GLOBALS["ARTICA_SQUID_DB"]["TryTCP"]=$def["TryTCP"];
			$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
			$this->PDO_DSN="mysql:unix_socket=$this->SocketName;dbname=$this->database";
			$this->UseStandardMysql=false;
			return;
		}
		
		
		if($GLOBALS["DEBUG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:blue'>Use Standard MysSQL instance</strong><br>\n";}
		$this->mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		if($this->mysql_password=="!nil"){$this->mysql_password=null;}
		$this->mysql_password=stripslashes($this->mysql_password);
		$this->mysql_admin=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$this->mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		$this->mysql_port=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
		if($this->mysql_port==null){$this->mysql_port=3306;}
		if($this->mysql_server==null){$this->mysql_server="localhost";}
		$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
		$this->mysql_admin=trim($this->mysql_admin);	
		$this->mysql_password=str_replace("\r", "", $this->mysql_password);
		$this->mysql_password=trim($this->mysql_password);	
		
		$pass=null;
		if(strlen($this->mysql_password)>1){
			$mysql_password=$this->shellEscapeChars($this->mysql_password);
			$pass=" -p$mysql_password";
		}
		
		if($this->mysql_server=="localhost.localdomain"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		if(preg_match("#localhost#i", $this->mysql_server)){$this->mysql_server="127.0.0.1";}
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		
		if($this->mysql_server=="127.0.0.1"){
			$this->MySQLConnectionType=1;
			$this->PDO_DSN="unix_socket=/var/run/mysqld/mysqld.sock;dbname=$this->database";
			$this->MYSQL_CMDLINES="--protocol=socket --socket=/var/run/mysqld/mysqld.sock -u {$this->mysql_admin}$pass";
		}else{
			$this->MySQLConnectionType=2;
			$this->PDO_DSN="mysql:host=$this->mysql_server;port=$this->mysql_port;dbname=$this->database";
			$this->MYSQL_CMDLINES="--protocol=tcp --host={$this->mysql_server} --port={$this->mysql_port} -u {$this->mysql_admin}$pass";
		}
		
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_admin"]=$this->mysql_admin;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_password"]=$this->mysql_password;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_port"]=$this->mysql_port;
		$GLOBALS["ARTICA_SQUID_DB"]["mysql_server"]=$this->mysql_server;
		$GLOBALS["ARTICA_SQUID_DB"]["SocketPath"]=$this->SocketPath;
		$GLOBALS["ARTICA_SQUID_DB"]["MYSQL_CMDLINES"]=$this->MYSQL_CMDLINES;
		$this->UseStandardMysql=true;
	}
	
	public function MEMORY_TABLES_DUMP(){
		if($this->EnableSquidRemoteMySQL==1){return;}
		if($this->squidEnableRemoteStatistics==1){return;}
		if($this->mysql_server<>"127.0.0.1"){return;}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$workdir="/home/artica/MySQLStartStop";
		$array=$this->MEMORY_TABLES_LIST();
		$CountDeArray=count($array);
		if($CountDeArray==0){echo "Stopping MySQL...............: no memory table to dump\n";return;}
		$unix=new unix();
		
		echo "Stopping MySQL...............: $CountDeArray memory tables to dump\n";
		$mysqldump=$unix->find_program("mysqldump");
		$MYSQL_CMDLINES=$this->MYSQL_CMDLINES;
		@mkdir($workdir,0755,true);
		$workfile="$workdir/restore.sql";
		
		$F[]="$mysqldump";
		$F[]="--skip-add-drop-table";
		$F[]="--delayed-insert";
		$F[]="--insert-ignore";
		$F[]=$MYSQL_CMDLINES;
		
		$cmdline=@implode(" ", $F)." $this->database ". @implode(" ", $array)." >$workfile";
		if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
		shell_exec($cmdline);
		
	}
	
	public function MEMORY_TABLES_RESTORE(){
		$workdir="/home/artica/MySQLStartStop";
		$workfile="$workdir/restore.sql";
		if(!is_file($workfile)){return;}
		$unix=new unix();
		$mysql=$unix->find_program("mysql");
		echo "Starting......: ".date("H:i:s")." MySQL restoring memory tables\n";
		
		$MYSQL_CMDLINES=$this->MYSQL_CMDLINES;
		$F[]="$mysql";
		$F[]="--batch";
		$F[]="--force";
		$F[]=$MYSQL_CMDLINES;
		$F[]=$this->database;
		$F[]=" < $workfile 2>&1";
		$cmdline=@implode(" ", $F);
		echo "Starting......: ".date("H:i:s")." MySQL `$cmdline`\n";
		if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
		exec($cmdline,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#already exists#",$ligne)){continue;}
			echo "Starting......: ".date("H:i:s")." MySQL restoring memory tables $ligne\n";
		}
		
		
		@unlink($workfile);
	}
	
	public function MEMORY_TABLES_LIST(){
		$sql="SHOW TABLE STATUS";
		if(!$this->BD_CONNECT()){return false;}
		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		
		if (!$ok){
			if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_errornum=$errnum;
			$this->mysql_error="Error Number ($errnum) ($des)";
			$this->ok=false;
			return false;
		}
		
		$results=@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			if($GLOBALS["VERBOSE"]){echo "mysql_query -> ERROR\n";}
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;
			return false;
		}
		
		
		$ARRAY=array();
		if($GLOBALS["VERBOSE"]){echo "mysql_query -> ". mysql_num_rows($results)." items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$Name=$ligne["Name"];
			$Engine=$ligne["Engine"];
			if($Engine<>"MEMORY"){continue;}
			$ARRAY[]=$Name;
		
		}
		return $ARRAY;
		
		
	}
	
	private function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		return $path;
	}	
	
	
	private function fill_tasks_disabled(){
			$users=new usersMenus();
			$DisableArticaProxyStatistics=$this->DisableArticaProxyStatistics;
			if($this->EnableRemoteSyslogStatsAppliance==1){$this->DisableLocalStatisticsTasks=1;}
			if($this->DisableLocalStatisticsTasks==1){$DisableArticaProxyStatistics=1;}
			$sock=new sockets();
			$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));
			$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
			$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
			$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
			$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
			
			$this->tasks_disabled[7]=true;
			$this->tasks_disabled[8]=true;
			$this->tasks_disabled[14]=true;
			$this->tasks_disabled[37]=true;
			$this->tasks_disabled[38]=true;
			$this->tasks_disabled[41]=true;
			$this->tasks_disabled[42]=true;
			$this->tasks_disabled[47]=true;
			$this->tasks_disabled[53]=true;
			$this->tasks_disabled[21]=true;
			
			$CATEGORY_UNSET=false;
			if($SquidPerformance>0){$CATEGORY_UNSET=true;}
			if($EnableLocalUfdbCatService==1){$CATEGORY_UNSET=false;}
			if($RemoteUfdbCat==1){$CATEGORY_UNSET=false;}

					if($CATEGORY_UNSET){
						unset($this->acl_GroupType["categories"]);
					}
			
			if($SquidPerformance>1){$this->DisableArticaProxyStatistics=1;}
			
			if($SquidPerformance>2){
				$this->tasks_disabled[41]=true;
				$this->tasks_disabled[37]=true;
				$this->tasks_disabled[46]=true;
			}
			
			if($SquidPerformance>1){
				$this->tasks_disabled[17]=true;
			}
			
			$MEMORY=$users->MEM_TOTAL_INSTALLEE;
			
			if($MEMORY<624288){
				$users->PROXYTINY_APPLIANCE=true;
				$this->DisableArticaProxyStatistics=1;
				$DisableArticaProxyStatistics=1;
				$this->tasks_disabled[31]=true;
				$this->tasks_disabled[38]=true;
				$this->tasks_disabled[52]=true;
				$this->tasks_disabled[51]=true;
				$this->tasks_disabled[53]=true;
			}
			
			if($DisableCategoriesDatabasesUpdates==1){
				$this->tasks_disabled[1]=true;
				$this->tasks_disabled[2]=true;
			}
			
			
			if($SQUIDEnable==0){
				$users->PROXYTINY_APPLIANCE=true;
				$this->DisableArticaProxyStatistics=1;
				$DisableArticaProxyStatistics=1;
				$this->EnableSargGenerator=0;
				$this->tasks_disabled[31]=true;
				$this->tasks_disabled[38]=true;
				$this->tasks_disabled[52]=true;
				$this->tasks_disabled[51]=true;
				$this->tasks_disabled[53]=true;
			}
			
			
			
			if($users->PROXYTINY_APPLIANCE){
				$this->tasks_disabled[1]=true;
				$this->tasks_disabled[8]=true;
				$this->tasks_disabled[31]=true;
				$this->tasks_disabled[42]=true;
				$this->tasks_disabled[44]=true;
				$this->tasks_disabled[47]=true;
				$this->tasks_disabled[49]=true;
				$this->tasks_disabled[50]=true;
				$this->tasks_disabled[53]=true;
				$this->DisableArticaProxyStatistics=1;
			}
			
			if($this->SquidActHasReverse==1){
				$this->tasks_disabled[1]=true;
				$this->tasks_disabled[2]=true;
				$this->tasks_disabled[3]=true;
				$this->tasks_disabled[8]=true;
				$this->tasks_disabled[18]=true;
				$this->tasks_disabled[42]=true;
				$this->tasks_disabled[29]=true;
				$this->tasks_disabled[23]=true;
				$this->tasks_disabled[49]=true;
				$this->tasks_disabled[50]=true;
				
			}
			
			if($this->DisableArticaProxyStatistics==1){
				$this->tasks_disabled[38]=true;
				$this->tasks_disabled[37]=true;
				$this->tasks_disabled[49]=true;
				$this->tasks_disabled[50]=true;
				$this->tasks_disabled[53]=true;
			}
			
			
			if($DisableArticaProxyStatistics==1){
				$this->tasks_disabled[15]=true;
				$this->tasks_disabled[16]=true;
				$this->tasks_disabled[9]=true;
				$this->tasks_disabled[10]=true;
				$this->tasks_disabled[11]=true;
				$this->tasks_disabled[6]=true;
				$this->tasks_disabled[7]=true;
				$this->tasks_disabled[2]=true;
				$this->tasks_disabled[23]=true;
				$this->tasks_disabled[25]=true;
				$this->tasks_disabled[14]=true;
				$this->tasks_disabled[3]=true;
				$this->tasks_disabled[28]=true;
				$this->tasks_disabled[29]=true;
				$this->tasks_disabled[34]=true;
				$this->tasks_disabled[36]=true;
				$this->tasks_disabled[40]=true;
				$this->tasks_disabled[43]=true;
				$this->tasks_disabled[44]=true;
				$this->tasks_disabled[47]=true;
				$this->tasks_disabled[49]=true;
				$this->tasks_disabled[50]=true;
				$this->tasks_disabled[53]=true;
			}
			
			if(!$users->CORP_LICENSE){
				$this->tasks_disabled[20]=true;
				$this->tasks_disabled[30]=true;
				$this->tasks_disabled[44]=true;
			}
			
			if($this->EnableSargGenerator==0){
				$this->tasks_disabled[26]=true;
				$this->tasks_disabled[27]=true;
				
			}
			
			if($this->EnableRemoteStatisticsAppliance==1){
				$tasks_remote_appliance=$this->tasks_remote_appliance;
				while (list ($TaskType, $none) = each ($tasks_remote_appliance) ){
					$this->tasks_disabled[$TaskType]=true;
				}
			}
			
			$this->tasks_disabled[6]=true;

			$this->tasks_disabled[2]=true;
			$this->tasks_disabled[9]=true;
			$this->tasks_disabled[10]=true;
			$this->tasks_disabled[11]=true;
			$this->tasks_disabled[15]=true;
			$this->tasks_disabled[16]=true;
			$this->tasks_disabled[23]=true;
			$this->tasks_disabled[25]=true;
			$this->tasks_disabled[28]=true;
			$this->tasks_disabled[34]=true;
			$this->tasks_disabled[36]=true;
			$this->tasks_disabled[40]=true;
			$this->tasks_disabled[44]=true;
			$this->tasks_disabled[49]=true;
			$this->tasks_disabled[50]=true;
			
			
			if($AsCategoriesAppliance==1){
				$this->tasks_disabled[1]=false;
			}
			
			
			
	}
	
	private function fill_task_array(){
			
			$this->tasks_array[0]="{select}";
			$this->tasks_array[1]="{databases_ufdbupdate}";
			
			$this->tasks_array[3]="{databases_compilation}";
			$this->tasks_array[4]="{restart_proxy_service}";
			$this->tasks_array[5]="{restart_kav4Proxy}";
			$this->tasks_array[6]="{verify_urls_databases}";
			$this->tasks_array[7]="{build_hours_tables}";
			$this->tasks_array[8]="{update_tlse}";
			$this->tasks_array[9]="{rebuild_visited_sites}";
			$this->tasks_array[10]="{recategorize_schedule}";
			$this->tasks_array[11]="{build_month_tables}";
			$this->tasks_array[12]="{update_kaspersky_databases}";
			$this->tasks_array[13]="{launch_UpdateUtility}";
			$this->tasks_array[14]="{optimize_database}";
			$this->tasks_array[15]="{hourly_cache_performances}";
			$this->tasks_array[16]="{build_daily_visited_websites}";
			$this->tasks_array[17]="{cache_items}";
			$this->tasks_array[18]="{backup_categories}";
			$this->tasks_array[19]="{build_crypted_tables_catz}";
			$this->tasks_array[20]="{compile_ufdb_repos}";
			$this->tasks_array[21]="{importadmembers}";
			$this->tasks_array[22]="{synchronize_webfilter_rules}";
			$this->tasks_array[23]="{repair_categories}";
			$this->tasks_array[24]="{clean_cloud_datacenters}";
			$this->tasks_array[25]="{build_blocked_week_statistics}";
			$this->tasks_array[26]="{sarg_build_daily_stats}";
			$this->tasks_array[27]="{sarg_build_hourly_stats}";
			$this->tasks_array[28]="{thumbnail_parse}";
			$this->tasks_array[29]="{malware_uri}";
			$this->tasks_array[30]="{update_precompiled_ufdb}";
			$this->tasks_array[31]="{parse_squid_logs_queue}";
			$this->tasks_array[32]="{parse_squid_framework}";
			$this->tasks_array[33]="{squid_logrotate_perform}";
			$this->tasks_array[34]="{squid_week_stats}";
			$this->tasks_array[35]="{squid_backup_stats}";
			$this->tasks_array[36]="{members_stats}";
			$this->tasks_array[37]="{squid_tail_injector}";
			$this->tasks_array[38]="{web_injector}";
			$this->tasks_array[39]="{reconfigure_proxy_task}";
			$this->tasks_array[40]="{hourly_bandwidth_users}";
			$this->tasks_array[41]="{squid_rrd}";
			$this->tasks_array[42]="{compile_tlse_database}";
			$this->tasks_array[43]="{squid_check_lost_tables}";
			$this->tasks_array[44]="{build_reports}";
			$this->tasks_array[45]="{rebuild_caches}";
			$this->tasks_array[46]="{fill_squid_client_table}";
			$this->tasks_array[47]="{squid_logs_purge}";
			$this->tasks_array[48]="{squid_logs_restore}";
			$this->tasks_array[49]="{statistics_per_users}";
			$this->tasks_array[50]="{categorize_tables}";
			$this->tasks_array[51]="{restart_ufdb}";
			$this->tasks_array[52]="{proxy_status}";
			$this->tasks_array[53]="{build_proxy_statistics}";
			$this->tasks_array[54]="{perfom_proxy_log_rotation}";
			$this->tasks_array[56]="{sarg_daily}";
			$this->tasks_array[57]="{reload_webfiltering_service}";
			$this->tasks_array[58]="{proxy_performances}";
			$this->tasks_array[59]="{static_activedirectory_group_task}";
			$this->tasks_array[60]="{local_realtime_statistics}";
			
			
			$this->tasks_explain_array[1]="{databases_ufdbupdate_explain}";
			$this->tasks_explain_array[2]="{instant_update_explain}";
			$this->tasks_explain_array[3]="{databases_compilation_explain}";
			$this->tasks_explain_array[4]="{restart_proxy_service_explain}";
			$this->tasks_explain_array[5]="{restart_kav4Proxy_explain}";
			$this->tasks_explain_array[6]="{verify_urls_databases_explain}";
			$this->tasks_explain_array[7]="{build_hours_tables_explain}";
			$this->tasks_explain_array[8]="{update_tlse_explain}";
			$this->tasks_explain_array[9]="{rebuild_visited_sites_explain}";
			$this->tasks_explain_array[10]="{www_recategorize_explain}";
			$this->tasks_explain_array[11]="{build_month_tables_explain}";
			$this->tasks_explain_array[12]="{update_kaspersky_databases_explain}";
			$this->tasks_explain_array[13]="{launch_UpdateUtility_explain}";
			$this->tasks_explain_array[14]="{squid_optimize_database_explain}";
			$this->tasks_explain_array[15]="{hourly_cache_performances_explain}";
			$this->tasks_explain_array[16]="{build_daily_visited_websites_explain}";
			$this->tasks_explain_array[17]="{cache_items_tasks_explain}";
			$this->tasks_explain_array[18]="{backup_categories_explain}";
			$this->tasks_explain_array[19]="{build_crypted_tables_catz_explain}";
			$this->tasks_explain_array[20]="{compile_ufdb_repos_explain}";
			$this->tasks_explain_array[21]="{importadmembers_explain}";
			$this->tasks_explain_array[22]="{synchronize_webfilter_rules_text}";
			$this->tasks_explain_array[23]="{repair_categories_explain}";
			$this->tasks_explain_array[24]="{clean_cloud_datacenters_explain}";
			$this->tasks_explain_array[25]="{build_blocked_week_statistics_explain}";
			$this->tasks_explain_array[26]="{sarg_build_daily_stats_explain}";
			$this->tasks_explain_array[27]="{sarg_build_daily_stats_explain}";
			$this->tasks_explain_array[28]="{thumbnail_parse_explain}";
			$this->tasks_explain_array[29]="{malware_uri_explain}";
			$this->tasks_explain_array[30]="{update_precompiled_ufdb_explain}";
			$this->tasks_explain_array[31]="{parse_squid_logs_queue_explain}";
			$this->tasks_explain_array[32]="{parse_squid_framework_explain}";
			$this->tasks_explain_array[33]="{squid_logrotate_perform_explain}";
			$this->tasks_explain_array[34]="{squid_week_stats_explain}";
			$this->tasks_explain_array[35]="{squid_backup_stats_explain}";
			$this->tasks_explain_array[36]="{members_stats_explain}";
			$this->tasks_explain_array[37]="{squid_tail_injector_explain}";
			$this->tasks_explain_array[38]="{web_injector_explain}";
			$this->tasks_explain_array[39]="{reconfigure_proxy_task_explain}";
			$this->tasks_explain_array[40]="{hourly_bandwidth_users_explain}";
			$this->tasks_explain_array[41]="{squid_rrd_explain}";
			$this->tasks_explain_array[42]="{compile_tlse_database_explain}";
			$this->tasks_explain_array[43]="{squid_check_lost_tables_explain}";
			$this->tasks_explain_array[44]="{build_reports_explain}";
			$this->tasks_explain_array[45]="{rebuild_caches_explain}";
			$this->tasks_explain_array[46]="{fill_squid_client_table_explain}";
			$this->tasks_explain_array[47]="{squid_logs_purge_explain}";
			$this->tasks_explain_array[48]="{squid_logs_restore_explain}";
			$this->tasks_explain_array[49]="{statistics_per_users_explain}";
			$this->tasks_explain_array[50]="{categorize_tables_explain}";
			$this->tasks_explain_array[51]="{restart_ufdb_explain}";
			$this->tasks_explain_array[52]="{proxy_status_explain}";
			$this->tasks_explain_array[53]="{build_proxy_statistics_explain}";
			$this->tasks_explain_array[54]="{perfom_proxy_log_rotation_explain}";
			$this->tasks_explain_array[56]="{sarg_daily_explain}";
			$this->tasks_explain_array[57]="{reload_webfiltering_service}";
			$this->tasks_explain_array[58]="{proxy_performances_task_explain}";
			$this->tasks_explain_array[59]="{static_activedirectory_group_task_explain}";
			$this->tasks_explain_array[60]="{local_realtime_statistics_explain}";
			

			$this->tasks_processes[1]="exec.squid.blacklists.php --update --bycron";
			
			$this->tasks_processes[3]="exec.squidguard.php --ufdbguard-recompile-dbs --bycron";
			$this->tasks_processes[4]="exec.squid.php --restart-squid --byschedule";
			$this->tasks_processes[5]="exec.squid.php --restart-kav4proxy";
			$this->tasks_processes[6]="exec.squid.blacklists.php --inject";
			$this->tasks_processes[7]="exec.squid.stats.php --scan-hours";
			$this->tasks_processes[8]="exec.update.squid.tlse.php";
			
			
			$this->tasks_processes[12]="exec.keepup2date.php --update";
			$this->tasks_processes[13]="exec.keepup2date.php --UpdateUtility";
			$this->tasks_processes[14]="exec.squid.stats.php --optimize";
			$this->tasks_processes[17]="exec.squid.purge.php --scan";
			$this->tasks_processes[18]="exec.squid.cloud.compile.php --backup-catz";
			$this->tasks_processes[19]="exec.squid.cloud.compile.php --v2";
			$this->tasks_processes[20]="exec.squid.cloud.compile.php --ufdb";
			$this->tasks_processes[21]="exec.adusers.php";
			$this->tasks_processes[22]="exec.squidguard.php --build --force";	
			$this->tasks_processes[24]="exec.cleancloudcatz.php --all";		
			$this->tasks_processes[29]="exec.squid.updateuris.malware.php --www";
			$this->tasks_processes[30]="exec.squid.blacklists.php --ufdb --bycron";
			$this->tasks_processes[31]="exec.dansguardian.injector.php";
			$this->tasks_processes[32]="exec.squid.framework.php";
			$this->tasks_processes[33]="exec.squid.php --rotate";
			$this->tasks_processes[35]="exec.squid.dbback.php";
			$this->tasks_processes[37]="exec.squid-tail-injector.php";
			$this->tasks_processes[38]="exec.dansguardian.injector.php";
			$this->tasks_processes[39]="exec.squid.php --build --force";
			$this->tasks_processes[41]="exec.squid-rrd.php";
			$this->tasks_processes[42]="exec.update.squid.tlse.php --compile";
			
			$this->tasks_processes[45]="exec.squid.rebuild.caches.php";
			$this->tasks_processes[46]="exec.squid-tail-injector.php --users-auth";
			$this->tasks_processes[47]="exec.squidlogs.purge.php";
			$this->tasks_processes[48]="exec.squidlogs.restore.php --restore-all";
			$this->tasks_processes[51]="exec.ufdb.php --restart";
			$this->tasks_processes[52]="exec.status.php --all-squid";
			$this->tasks_processes[53]="exec.squid.stats.central.php";
			$this->tasks_processes[54]="exec.squid.php --rotate --byschedule";
			$this->tasks_processes[57]="exec.ufdb.php --reload --from-schedule";
			$this->tasks_processes[58]="exec.squid.squeezer.php";
			$this->tasks_processes[59]="exec.squid.static.ad.groups.php";
			$this->tasks_processes[60]="exec.squid.run.schedules.php";
			
			$this->tasks_remote_appliance["51"]=true;
			$this->tasks_remote_appliance["50"]=true;
			$this->tasks_remote_appliance["49"]=true;
			$this->tasks_remote_appliance["46"]=true;
			$this->tasks_remote_appliance["44"]=true;
			$this->tasks_remote_appliance["43"]=true;
			$this->tasks_remote_appliance["42"]=true;
			$this->tasks_remote_appliance["40"]=true;
			$this->tasks_remote_appliance["36"]=true;
			$this->tasks_remote_appliance["34"]=true;
			$this->tasks_remote_appliance["30"]=true;
			$this->tasks_remote_appliance["29"]=true;
			$this->tasks_remote_appliance["28"]=true;
			$this->tasks_remote_appliance["27"]=true;
			$this->tasks_remote_appliance["26"]=true;
			$this->tasks_remote_appliance["25"]=true;
			$this->tasks_remote_appliance["23"]=true;
			$this->tasks_remote_appliance["22"]=true;
			$this->tasks_remote_appliance["14"]=true;
			$this->tasks_remote_appliance["15"]=true;
			$this->tasks_remote_appliance["16"]=true;
			$this->tasks_remote_appliance["13"]=true;
			$this->tasks_remote_appliance["12"]=true;
			$this->tasks_remote_appliance["9"]=true;
			$this->tasks_remote_appliance["10"]=true;
			$this->tasks_remote_appliance["11"]=true;
			$this->tasks_remote_appliance["8"]=true;
			$this->tasks_remote_appliance["7"]=true;
			$this->tasks_remote_appliance["6"]=true;
			$this->tasks_remote_appliance["3"]=true;
			$this->tasks_remote_appliance["2"]=true;
			$this->tasks_remote_appliance["1"]=true;
			
			
	}
	
	
	public function fill_webfilter_certs(){
		$file=dirname(__FILE__)."/databases/ufdbcerts";
		if(!is_file($file)){return;}
		$text=unserialize(base64_decode(@file_get_contents($file)));
		$prefix="INSERT IGNORE INTO webfilter_certs (`zmd5`,`certname`,`certdata`) VALUES ";
		while (list ($md5, $array) = each ($text)){
			$data=mysql_escape_string2($array["DATA"]);
			$name=$array["NAME"];
			if(strtolower($name)<>"default"){
				$name=mysql_escape_string2(base64_decode($array["NAME"]));
			}
			$f[]="('$md5','$name','$data')";

		}
		
		$this->QUERY_SQL($prefix.@implode(",",$f));
		
	}
	
	function LoadStatusCodes(){
		$array= array(
				null=>"{none}",
				200=>"OK",
				201=>"Created",
				202=>"Accepted",
				203=>"Non-Authoritative Information",
				204=>"No Content",
				205=>"Reset Content",
				206=>"Partial Content",
				207=>"Multi Status",
				300=>"Multiple Choices",
				301=>"Moved Permanently",
				302=>"Moved Temporarily",
				303=>"See Other",
				304=>"Not Modified",
				305=>"Use Proxy",
				307=>"Temporary Redirect",
				400=>"Bad Request",
				401=>"Unauthorized",
				402=>"Payment Required",
				403=>"Forbidden",
				404=>"Not Found",
				405=>"Method Not Allowed",
				406=>"Not Acceptable",
				407=>"Proxy Authentication Required",
				408=>"Request Timeout",
				409=>"Conflict",
				410=>"Gone",
				411=>"Length Required",
				412=>"Precondition Failed",
				413=>"Request Entity Too Large",
				414=>"Request URI Too Large",
				415=>"Unsupported Media Type",
				416=>"Request Range Not Satisfiable",
				417=>"Expectation Failed",
				422=>"Unprocessable Entity",
				424=>"Locked",
				424=>"Failed Dependency",
				433=>"Unprocessable Entity",
				500=>"Internal Server Error",
				501=>"Not Implemented",
				502=>"Bad Gateway",
				503=>"Service Unavailable",
				504=>"Gateway Timeout",
				505=>"HTTP Version Not Supported",
				507=>"Insufficient Storage",
				600=>"Squid: header parsing error",
				601=>"Squid: header size overflow detected while parsing",
				601=>"roundcube: software configuration error",
				603=>"roundcube: invalid authorization");
	
		while (list ($num, $line) = each ($array)){
			if(is_numeric($num)){
				$array[$num]="[$num]: $line";
			}
		}
		reset($array);
		return $array;
	
	}	
	
	
	public function CheckDefaultSchedules(){
		
		$allminutes="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59";
		
		if(!$this->TABLE_EXISTS('webfilters_schedules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_schedules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`TimeText` VARCHAR( 128 ) NOT NULL ,
			`TimeDescription` VARCHAR( 128 ) NOT NULL ,
			`TaskType` SMALLINT( 1 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TaskType` , `TimeDescription`,`enabled`)
			) ENGINE=MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("Fatal!!! $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}	
		
			$update=false;
			$array[1]=array("TimeText"=>"0 0,3,5,7,9,11,13,15,17,19,23 * * *","TimeDescription"=>"Check update each 3H");
			
			$array[6]=array("TimeText"=>"20,40,59 * * * *","TimeDescription"=>"each 20mn");
			
			$array[8]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
			$array[9]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");
			$array[10]=array("TimeText"=>"0 5 * * *","TimeDescription"=>"each day at 05:00");
			$array[11]=array("TimeText"=>"0 1 * * *","TimeDescription"=>"each day at 01:00");
			$array[25]=array("TimeText"=>"30 2 * * *","TimeDescription"=>"each day at 02:30");
			$array[2]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
			$array[3]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");	
			
			$array[15]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Calculate cache performance each hour");
			$array[16]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
			$array[17]=array("TimeText"=>"30 * * * *","TimeDescription"=>"each 1h30");
			$array[21]=array("TimeText"=>"0 2,4,6,8,10,12,14,16,18,20,22 * * *","TimeDescription"=>"Check AD server each 2H");
			
			$array[28]=array("TimeText"=>"10,20,30,40,50 * * * *","TimeDescription"=>"check thumbnails queue each 10mn");
			$array[29]=array("TimeText"=>"30 6 * * *","TimeDescription"=>"Update infected uris Each day at 06h30");
			$array[30]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Update precompiled databases Each day at 04h30");
			$array[31]=array("TimeText"=>"0,5,10,15,20,25,30,35,40,45,50,55 * * * *","TimeDescription"=>"Check queue requests each 5mn");
			$array[32]=array("TimeText"=>"0,10,20,30,40,50 * * * *","TimeDescription"=>"Check framework requests each 10mn");
			
			$array[37]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
			$array[38]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
			$array[40]=array("TimeText"=>"10 * * * *","TimeDescription"=>"Each hour +10mn");
			$array[42]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Compile Toulouse databases tables Each day at 04h30");
			$array[43]=array("TimeText"=>"30 3 * * *","TimeDescription"=>"Lost tables Each day at 03h30");
			$array[46]=array("TimeText"=>"7,22,37,52 * * * *","TimeDescription"=>"each 15mn");
			$array[47]=array("TimeText"=>"30 2 * * *","TimeDescription"=>"Daily Purge Statistics at 2h30");
			$array[51]=array("TimeText"=>"30 5 * * *","TimeDescription"=>"Restart Web Filtering service each day at 05h30");
			$array[52]=array("TimeText"=>"0,5,10,15,20,25,30,35,40,45,50,55 * * * *","TimeDescription"=>"Generate Proxy status each 5mn");
			$array[53]=array("TimeText"=>"0 1 * * *","TimeDescription"=>"Generate Statistics, each day at 01h00");
			$array[56]=array("TimeText"=>"59 23 * * *","TimeDescription"=>"each day at 23:59");
			$array[57]=array("TimeText"=>"30 3 * * *","TimeDescription"=>"each day at 03:30");
			$array[60]=array("TimeText"=>"0,6,11,16,21,26,31,36,41,46,51,56 * * * *","TimeDescription"=>"each 5 minutes");
			
			$this->tasks_disabled[6]=true;
			$this->tasks_disabled[2]=true;
			$this->tasks_disabled[9]=true;
			$this->tasks_disabled[10]=true;
			$this->tasks_disabled[11]=true;
			$this->tasks_disabled[15]=true;
			$this->tasks_disabled[16]=true;
			$this->tasks_disabled[23]=true;
			$this->tasks_disabled[25]=true;
			$this->tasks_disabled[28]=true;
			$this->tasks_disabled[34]=true;
			$this->tasks_disabled[36]=true;
			$this->tasks_disabled[40]=true;
			$this->tasks_disabled[43]=true;
			$this->tasks_disabled[44]=true;
			$this->tasks_disabled[49]=true;
			$this->tasks_disabled[50]=true;	
			$this->tasks_disabled[55]=true;
			

			
			

			while (list ($TaskType, $content) = each ($array) ){
				
				if($GLOBALS["VERBOSE"]){echo "<strong style='color:blue'>$TaskType</strong>\n";}
					
				if(isset($this->tasks_disabled[$TaskType])){
						if($this->tasks_disabled[$TaskType]){
						if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$TaskType tasks_disabled</strong>\n";}
						continue;
					}
				}
				
				
				$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM webfilters_schedules WHERE TaskType=$TaskType"));
				if($ligne["ID"]>0){
					if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>$TaskType Already saved as {$ligne["ID"]}</strong>\n";}
					continue;
				}
				
				$sql="INSERT IGNORE INTO webfilters_schedules (TimeDescription,TimeText,TaskType,enabled) 
					VALUES('{$content["TimeDescription"]}','{$content["TimeText"]}','$TaskType',1)";	
				
				if(function_exists("squid_admin_mysql")){
					squid_admin_mysql(1, "Task type $TaskType is not added into scheduler or removed [add it]", "{$content["TimeDescription"]}",__FILE__,__LINE__);
				}			
				
				$this->QUERY_SQL($sql);
				if(!$this->ok){
					squid_admin_mysql(1, "Task type $TaskType MySQL error", "$sql\n$q->mysql_error\n",__FILE__,__LINE__);
					continue;
				}
				$update=true;
			}
			
			if($update){$sock=new sockets();$sock->getFrameWork("squid.php?build-schedules=yes");}
			
		
	}
	
	FUNCTION DELETE_TABLE($table){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
		if(!$this->ok){return false;}
		$this->QUERY_SQL("FLUSH TABLES",$this->database);
		return true;
	}		
	
	
	public function TestingConnection($called=null){
			return $this->BD_CONNECT();
	}
	
	public function COUNT_ROWS($table,$database=null){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
	}
	
	
	public function TABLE_SIZE($table,$database=null){
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];	
	}
	
	FUNCTION TABLE_STATUS($table,$database=null){
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		if(!function_exists('mysql_connect')){
		$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);return false;}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$database));		
	}
	
	public function TABLE_EXISTS($table,$database=null){
		$keyCache=md5(__CLASS__.__FUNCTION__.$table.__LINE__);
		if(isset($GLOBALS[$keyCache])){return $GLOBALS[$keyCache];}
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		if(function_exists("debug_backtrace")){
			try {
				$trace=@debug_backtrace();
				if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			} catch (Exception $e) {$this->writeLogs("TABLE_EXISTS:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		
		
		if(!$this->DATABASE_EXISTS($database)){
			
			$this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			if(!$this->CREATE_DATABASE($database)){
				$this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql,$database,$called);
		$result=false;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS[$keyCache][$database][$ligne["Tables_in_$database"]]=true;
			if(!$GLOBALS["AS_ROOT"]){$_SESSION[$keyCache][$database][$ligne["Tables_in_$database"]]=true;}
			if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){
				$GLOBALS[$keyCache]=true;
				return true;
			}
		}
		$GLOBALS[$keyCache]=false;
		return $result;
		
	}
	private function DATABASE_EXISTS($database){
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION["MYSQL_DATABASE_EXISTS"][$database]=true;
				return true;
			}
		}
		
		return false;
	}
	
	public function DELETE_CATEGORY($category){
		if($category=="UnkNown"){$category="";}
		$tableURI="categoryuris_$category";
		
		$this->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$category'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n";return;}
		$this->QUERY_SQL("DELETE FROM usersisp_catztables WHERE category='$category'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n";return;}
		$this->QUERY_SQL("DELETE FROM usersisp_blkwcatz WHERE category='$category'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n";return;}
		$this->QUERY_SQL("DELETE FROM usersisp_blkcatz WHERE category='$category'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n";return;}
		$this->QUERY_SQL("DELETE FROM webfilter_catprivs WHERE categorykey='$category'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n";return;}
		$this->QUERY_SQL("DELETE FROM webfilter_blkcnt WHERE `category` = '".mysql_escape_string2($category)."'");
		if(!$this->ok){echo $this->mysql_error."\nline:".__LINE__."\n$sql\n";return;}
		$this->QUERY_SQL("DROP TABLE category_$category");
		if(!$this->ok){echo $this->mysql_error."\nDROP TABLE category_$category\nline:".__LINE__."\n";return;}
		if($this->TABLE_EXISTS("$tableURI")){$this->QUERY_SQL("DROP TABLE $tableURI");}
		
		$category=mysql_escape_string2($category);
		$this->QUERY_SQL("DELETE FROM personal_categories WHERE `category`='$category'");
		
		$this->QUERY_SQL("TRUNCATE TABLE `webfilters_categories_caches`");
		
		
		
		
		return true;
		
	}
	
	
	function PRIVILEGES($user,$password){
		
		$sql="SELECT User FROM user WHERE User='$user'";
	
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$this->database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $this->database \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		
		if($userfound==null){
			$sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
		}
		
		
		$sql="CREATE USER '$user'@'*' IDENTIFIED BY '$password';";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){
			echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
			return false;
		}
		
		$sql="GRANT USAGE ON `$this->database`. *  TO '$user'@'*' IDENTIFIED BY '$password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}


		$sql="GRANT ALL PRIVILEGES ON `$this->database` . * TO '$user'@'*' WITH GRANT OPTION ;";
		$this->EXECUTE_SQL($sql);
		if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}
	}
	
	
	
	
	public function DATABASE_INFOS(){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$this->database);
		$dbsize=0;$count=0;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
			$count=$count+1;}
			return array($count,ParseBytes($dbsize));
		
	}
	
	
	public function TABLE_GET_COLUMNS($table) {
		$result =$this->QUERY_SQL("SHOW COLUMNS FROM `$table`");
		$fieldnames=array();
		if (@mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_assoc($result)) {
				$fieldnames[] = $row['Field'];
			}
		}
	
		return $fieldnames;
	}	
	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		if($database==null){$database=$this->database;}
		$field=trim($field);
		if(isset($GLOBALS["__FIELD_EXISTS"])){
			if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
				if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
					if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]==true){return true;}
				}
			}
		}
		$sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		
		if(trim($ligne["Field"])<>null){
			$GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
			return true;
		}else{
			$this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
			$this->writelogs("$sql",__FUNCTION__,__LINE__);
			return false;
		}
		
	}

	public function BD_CONNECT($noretry=false,$called=null){
			if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
			if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
			
			
			
			if($this->SERVER_CONNECT($noretry,$called)){
				unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
				return true;
			}
			
			@mysql_close();
			usleep(500);
			if($this->SERVER_CONNECT($noretry,$called)){
				unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
				return true;
			}
			@mysql_close();
			usleep(500);
			if($this->SERVER_CONNECT($noretry,$called)){
				unset($GLOBALS["SQUID_BD_STOP_PROCESSSING"]);
				return true;
			}
			$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
			return false;
		
		
	}
	
	
	public function SERVER_CONNECT($noretry=false,$called=null){
			if(!isset($GLOBALS["SERVER_CONNECT_COUNT"])){$GLOBALS["SERVER_CONNECT_COUNT"]=0;}
			$GLOBALS["SERVER_CONNECT_COUNT"]++;
			$ErrorCount="{$GLOBALS["SERVER_CONNECT_COUNT"]} retried connection";
			if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
			if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
			if($this->MySQLConnectionType==1){
				if(!$this->is_socket($this->SocketName)){
					$this->mysql_error="$this->SocketName $ErrorCount no such socket";
					$this->ToSyslog("$this->SocketName $ErrorCount no such socket");
					$GLOBALS["THIS_TestingConnection"]=false;
					return false;
				}
				
				if($this->SocketName=="/var/run/mysqld/mysqld.sock"){
					$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,$this->mysql_password);
				}else{
					$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,null);
				}
				
				
				
				if($bd){
					$this->mysql_connection=$bd;
					$GLOBALS["THIS_TestingConnection"]=true;
					return true;
				}
				
				
				
				
				if($GLOBALS["VERBOSE"]){echo "mysql_connect $this->SocketName -> error<br>\n";}
				$des=@mysql_error(); $errnum=@mysql_errno();
				$this->BD_CONNECT_ERROR=__LINE__.": $ErrorCount MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
				$this->ToSyslog($this->BD_CONNECT_ERROR);
				$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
		}
	
		if($this->MySQLConnectionType==2){
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($bd){$this->mysql_connection=$bd;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->BD_CONNECT_ERROR=__LINE__.": $ErrorCount MySQL Server:$this->mysql_server: MySQLConnectionType = $this->MySQLConnectionType failed (N:$errnum) \"$des\" $called";
			$this->ToSyslog($this->BD_CONNECT_ERROR);
			$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
			$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
			$GLOBALS["THIS_TestingConnection"]=false;
			return false;
		}
		
		if($this->mysql_server=="127.0.0.1"){
			if(!$this->is_socket($this->SocketName)){
				$this->mysql_error="$this->SocketName no such socket $ErrorCount";
				$this->ToSyslog("$this->SocketName no such socket $ErrorCount");
				$GLOBALS["THIS_TestingConnection"]=false;
				$GLOBALS["SQUID_BD_STOP_PROCESSSING"]=true;
				return false;
			}
			
			$bd=@mysql_connect(":$this->SocketName",$this->mysql_admin,$this->mysql_password);
			$FinalLog="$this->SocketName@$this->mysql_admin";
		}else{
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			$FinalLog="$this->mysql_admin@$this->mysql_server:$this->mysql_port";
		}
		
		
			if($bd){$this->mysql_connection=$bd;$GLOBALS["THIS_TestingConnection"]=true;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->BD_CONNECT_ERROR=__LINE__.":$ErrorCount $FinalLog = Err:$errnum $des $called";
			$this->ToSyslog($this->BD_CONNECT_ERROR);
			$this->writelogs($this->BD_CONNECT_ERROR,__FUNCTION__,__LINE__);
			$GLOBALS["THIS_TestingConnection"]=false;
			
			return false;
		
	}
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}	
	
	private function THIS_TestingConnection($noretry=false,$called=null){return $this->BD_CONNECT();}	
	
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
		$des=@mysql_error($this->mysql_connection);
		$this->mysql_error=$des;
	
	}

	public function TABLES_STATUS_CORRUPTED(){
		$ARRAY=array();
		$sql="show TABLE STATUS";
		if(!$this->BD_CONNECT()){return false;}
		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		
		if (!$ok){
			if($GLOBALS["VERBOSE"]){echo "mysql_select_db -> ERROR\n";}
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_errornum=$errnum;
			$this->mysql_error="Error Number ($errnum) ($des)";
			$this->ok=false;
			return false;
		}
		
		$results=@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			if($GLOBALS["VERBOSE"]){echo "mysql_query -> ERROR\n";}
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;
			return false;
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "mysql_query -> ". mysql_num_rows($results)." items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			
			$Name=$ligne["Name"];
			$Comment=$ligne["Comment"];
			if(trim($Comment)==null){continue;}
			$ARRAY[$Name]=$Comment;
	
		}
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		
		return $ARRAY;
	}	
	
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
		
		$results=@mysql_query($sql,$this->mysql_connection);
		if(mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=mysql_errno($this->mysql_connection);
			$des=mysql_error($this->mysql_connection);
			$this->mysql_error="Error Number ($errnum) ($des) <hr>$sql";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;
			return false;
		}
	
		$this->ok=true;
		return $results;
	}	
	
	
	public function DATABASE_LIST(){
		if(!$this->BD_CONNECT()){return false;}
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
	}
	
	private function writelogs($text=null,$function=null,$line=0){
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.squid.debug";
		@mkdir(dirname($file_source));
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){
			writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
		}
		if(!$GLOBALS["VERBOSE"]){return;}
		$logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
		@fclose($f);
	}	
	
	public function QUERY_PDO($sql){
		$this->sql=$sql;
		$pdo_opt = array ( PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
		
		try {
	    $dbh = new PDO($this->PDO_DSN, $this->mysql_admin, $this->mysql_password,$pdo_opt);
		} catch (PDOException $e) {
			$this->ok=false;
			$this->mysql_error="Connection failed $this->PDO_DSN ". $e->getMessage();
			if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
	    	return false;
		}
		
		
		
		
		$stmt = $dbh->prepare($sql);
		
		
		
		if (!$stmt->execute()){
			$this->ok=false;
			$this->mysql_error=implode(":",$stmt->errorInfo());
			if($GLOBALS["VERBOSE"]){echo "PDO Failed: $this->mysql_error\n";}
			return false;
		}
		
		
		return $stmt;
		
	}
	
	
	public function QUERY_SQL($sql,$database=null,$called=null,$unbuffered=false){
		$TableDropped_blacklist["ufdb_smtp"]=true;
		$database=trim($database);
		if($database=="artica_backup"){$database=$this->database;}
		if($database=="artica_events"){$database=$this->database;}
		if($database=="ocsweb"){$database=$this->database;}
		if($database=="postfixlog"){$database=$this->database;}
		if($database=="powerdns"){$database=$this->database;}
		if($database=="zarafa"){$database=$this->database;}
		if($database=="syslogstore"){$database=$this->database;}
		if($database=="metaclient"){$database=$this->database;}
		if($database==null){$database=$this->database;}
		$this->last_id=0;
		$this->sql=$sql;
		$CLASS=__CLASS__;
		$FUNCTION=__FUNCTION__;
		$FILENAME=basename(__FILE__);
		$LOGPRF="$FILENAME::$CLASS/$FUNCTION";
		$this->ok=false;
		
		if(isset($GLOBALS["HOTSPOT_DEBUG"])){
		if($GLOBALS["HOTSPOT_DEBUG"]){
			if(function_exists("wifidog_logs")){
				wifidog_logs("$sql",__FUNCTION__,__LINE__);}
			}
		}
	
		
		
		
		$sql=trim($sql);
		
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		
		
		if(preg_match("#delete.*?webfilter_members#i", $sql)){
			$this->ToSyslog("FATAL!! CHATEAU-THIERRY $sql $called");
			
		}
		
		
		if($GLOBALS["DEBUG_SQL"]){echo "this->BD_CONNECT\n";}
		@mysql_close($this->mysql_connection);
		if(!$this->BD_CONNECT(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->BD_CONNECT_ERROR ." Error, unable to connect to MySQL server";
			$this->ToSyslog($this->mysql_error);
			return false;
		}
	
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(!isset($TableDropped_blacklist[$TableDropped])){
				if(function_exists("system_admin_events")){
					$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
				}
			}
		}
	
	
		if($GLOBALS["DEBUG_SQL"]){echo "mysql_select_db()\n";}
		if($GLOBALS['VERBOSE']){$ok=mysql_select_db($database,$this->mysql_connection);}else{
		$ok=@mysql_select_db($database,$this->mysql_connection);
		}
		
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if(!is_numeric($errnum)){
				if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__."  [FAILED] error $errnum $des -> RESTART !!\n";};
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				$this->BD_CONNECT(false,$called);
				$ok=@mysql_select_db($this->database,$this->mysql_connection);
				if (!$ok){
					if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] -> SECOND TIME !!\n";};
					
					if(function_exists("mysql_admin_mysql")){mysql_admin_mysql(0,"FATAL MySQL error Connection failed to MySQL database",$sql."\n$this->mysql_error",__FILE__,__LINE__);}
					$this->ok=false;
					return false;
				}
			}
		}
		
		
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db/$this->database/".__LINE__." [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			if($GLOBALS["VERBOSE"]){echo "mysql -u $this->mysql_admin -p$this->mysql_password -h $this->mysql_server -P $this->mysql_port -A $this->database\n";}
			$this->mysql_errornum=$errnum;
			$this->mysql_error=$des;
			$time=date('h:i:s');
			$this->writeLogs("$LOGPRF Line:".__LINE__.":mysql_select_db DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->mysql_error="$LOGPRF Line:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
			$this->ok=false;
			$this->ToSyslog($this->mysql_error);
			$this->ToSyslog($sql);
			@mysql_close($this->mysql_connection);
			$this->mysql_connection=false;
			if(function_exists("mysql_admin_mysql")){mysql_admin_mysql(0,"FATAL MySQL error Error Number ($errnum) ($des)",$sql."\n$this->mysql_error",__FILE__,__LINE__);}
			
			
			
			return null;
		}
	
		
		$mysql_unbuffered_query_log=null;
		if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
			$mysql_unbuffered_query_log="mysql_unbuffered_query";
			if($GLOBALS["DEBUG_SQL"]){echo "mysql_unbuffered_query()\n";}
			$results=@mysql_unbuffered_query($sql,$this->mysql_connection);
			
		}else{
			if($unbuffered){
				$mysql_unbuffered_query_log="mysql_unbuffered_query";
				if($GLOBALS["DEBUG_SQL"]){echo "mysql_unbuffered_query()\n";}
				$results=@mysql_unbuffered_query($sql,$this->mysql_connection);
			}else{
				$mysql_unbuffered_query_log="mysql_query";
				if($GLOBALS["DEBUG_SQL"]){echo "mysql_query()\n";}
				$results=@mysql_query($sql,$this->mysql_connection);
				$this->last_id=@mysql_insert_id($this->mysql_connection);
			}
		}
		
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			
			if(preg_match('#Duplicate entry#',$des)){
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->ok=true;
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				return true;
			}
			$this->mysql_errornum=$errnum;
			$this->mysql_error="QUERY_SQL:".__LINE__.": $mysql_unbuffered_query_log:: $called Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
			
			
			if(isset($GLOBALS["HOTSPOT_DEBUG"])){if($GLOBALS["HOTSPOT_DEBUG"]){if(function_exists("wifidog_logs")){wifidog_logs("$this->mysql_error",__FUNCTION__,__LINE__);}}}
				
			
			$this->ToSyslog($this->mysql_error);
			$sql=str_replace("\n", " ", $sql);
			$sql=str_replace("\t", " ", $sql);
			$sql=str_replace("  ", " ", $sql);
			$this->ToSyslog($sql);
			if(preg_match("#Table\s+'(.+?)'.*?is marked as crashed#", $des,$re)){
				if(class_exists("sockets")){
						$sock=new sockets();
						$ARRAY["DB"]=$database;
						$ARRAY["TABLE"]=$re[1];
						$data=urlencode(base64_encode(serialize($ARRAY)));
						$sock->getFrameWork("squid.php?mysql-crash=$data");
				}
			}
			
			
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log/".__LINE__." [FAILED] N.$errnum DESC:$des $called\n";}
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log".__LINE__." [FAILED] $sql\n";}
			if(function_exists("mysql_admin_mysql")){mysql_admin_mysql(0,"FATAL MySQL error Error Number ($errnum) ($des)",$sql."\n$this->mysql_error",__FILE__,__LINE__);}
			@mysql_free_result($this->mysql_connection);
			@mysql_close($this->mysql_connection);
			$this->mysql_connection=false;
			$this->ok=false;
			return null;
	
		}
		if($GLOBALS["DEBUG_SQL"]){echo "SUCCESS\n";}
		$this->ok=true;
		if($this->last_id==0){
			$this->last_id=@mysql_insert_id($this->mysql_connection);
		}
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		$this->mysql_connection=false;
		return $result_return;
	
	
	}
	
	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\r", " ", $text);
		
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$function="{$trace[1]["function"]}()";
				$line="{$trace[1]["line"]}";
			}
		}
		
		$text="{$function}[$line]:$text";
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("mysql-squid", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	public function FIELD_TYPE($table,$field){
		$database=$this->database;
		if(isset($GLOBALS["__FIELD_TYPE"])){
			if(isset($GLOBALS["__FIELD_TYPE"][$database][$table][$field])){
				if($GLOBALS["__FIELD_TYPE"][$database][$table][$field]<>null){return $GLOBALS["__FIELD_TYPE"][$database][$table][$field];}
			}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		$GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
		return strtolower($ligne["Type"]);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}

		if(isset($_SESSION["MYSQL_INDEX_EXISTS"])){if($_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]==true){return true;}}
		$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	public FUNCTION CREATE_DATABASE($database){
		if(isset($GLOBALS["SQUID_BD_STOP_PROCESSSING"])){if($GLOBALS["SQUID_BD_STOP_PROCESSSING"]){return false;}}
		if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
		
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		
		
		if($GLOBALS["VERBOSE"]){echo " -> ->DATABASE_EXISTS($database)<br>\n";}
		if($this->DATABASE_EXISTS($database)){
			writelogs("CREATE_DATABASE $database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}}
  		$results=@mysql_query("CREATE DATABASE `$database`",$this->mysql_connection);
			if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connectiond);
				if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
				$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}

		$this->ok=true;
		return true;
	}
	
	public function CheckTable_dansguardian(){
		$this->CheckTables();
	}
	
	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		
		if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." $sql<br>\n";}
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["FULL_DEBUG"]){echo __CLASS__.'/'.__FUNCTION__." ".__LINE__." SUM: {$ligne["tsum"]}<br>\n";}
		return $ligne["tsum"];
		
	}
	

	

	public function CategoryShellEscape($category){
		$category=trim($category);
		if($category==null){return;}
		$category=str_replace("/", "_", $category);
		$category=str_replace("-", "_", $category);
		$category=str_replace(" ", "_", $category);		
		return $category;
	}
	
	public function StripBadChars_hostname($value){
		$value=trim(strtolower($value));
		$value=$this->replace_accents($value);
		$value=str_replace("$", "", $value);
		$value=str_replace(" ", "", $value);
		$value=str_replace("/", "_", $value);
		$value=str_replace("\\", "_", $value);
		$value=str_replace("#", "", $value);
		$value=str_replace("\"", "", $value);
		$value=str_replace("'", "`", $value);
		$value=str_replace("!", "", $value);
		$value=str_replace(";", "", $value);
		$value=str_replace(",", "", $value);
		$value=str_replace(":", "", $value);
		$value=str_replace("%", "", $value);
		$value=str_replace("*", "", $value);
		$value=str_replace("(", "", $value);
		$value=str_replace("[", "", $value);
		$value=str_replace("{", "", $value);
		$value=str_replace(")", "", $value);
		$value=str_replace("]", "", $value);
		$value=str_replace("}", "", $value);
		$value=str_replace("|", "", $value);
		$value=str_replace("&", "", $value);
		$value=str_replace("+", "", $value);
		$value=str_replace("=", "", $value);
		$value=str_replace("@", "", $value);
		$value=str_replace("£", "", $value);
		$value=str_replace("€", "", $value);
		$value=str_replace("----", "-", $value);
		$value=str_replace("---", "-", $value);
		$value=str_replace("--", "-", $value);
		$value=str_replace("--", "-", $value);
		$value=str_replace("--", "-", $value);
		$value=str_replace("__", "_", $value);
		$value=str_replace("__", "_", $value);
		$value=str_replace("__", "_", $value);
		$value=str_replace("__", "_", $value);
		
		return $value;
		
	}
	
	private function replace_accents($s) {
			$s = htmlentities($s);$s = preg_replace ('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|ring);/', '$1', $s);$s=str_replace("&Ntilde;","N",$s);$s=str_replace("&ntilde;","n",$s);$s=str_replace("&Oacute;","O",$s);$s=str_replace("&oacute;","O",$s);$s=str_replace("&Ograve;","O",$s);$s=str_replace("&ograve;","o",$s);$s=str_replace("&Ocirc;","O",$s);$s=str_replace("&ocirc;","o",$s);$s=str_replace("&Ouml;","O",$s);$s=str_replace("&ouml;","o",$s);$s=str_replace("&Otilde;","O",$s);$s=str_replace("&otilde;","o",$s);$s=str_replace("&Oslash;","O",$s);$s=str_replace("&oslash;","o",$s);$s=str_replace("&szlig;","b",$s);$s=str_replace("&Thorn;","T",$s);$s=str_replace("&thorn;","t",$s);$s=str_replace("&Uacute;","U",$s);$s=str_replace("&uacute;","u",$s);$s=str_replace("&Ugrave;","U",$s);$s=str_replace("&ugrave;","u",$s);$s=str_replace("&Ucirc;","U",$s);$s=str_replace("&ucirc;","u",$s);$s=str_replace("&Uuml;","U",$s);$s=str_replace("&uuml;","u",$s);$s=str_replace("&Yacute;","Y",$s);$s=str_replace("&yacute;","y",$s);$s=str_replace("&yuml;","y",$s);$s=str_replace("&Icirc;","I",$s);$s=str_replace("&icirc;","i",$s);$s = html_entity_decode($s);return $s;
	}	
	

	public function LIST_TABLES_ACCESSDB_DAY(){
		if(isset($GLOBALS["LIST_TABLES_ACCESSDB_DAY"])){return $GLOBALS["LIST_TABLES_ACCESSDB_DAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE '%_daccess'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_daccess#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_ACCESSDB_DAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}	
	
	
	public function LIST_TABLES_HOURS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_HOURS"])){return $GLOBALS["SQUID_LIST_TABLES_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_hour'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_hour#", $ligne["c"])){
				$GLOBALS["SQUID_LIST_TABLES_HOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
	}
	
	public function LIST_TABLES_MONTH(){
		if(isset($GLOBALS["LIST_TABLES_MONTH"])){return $GLOBALS["LIST_TABLES_MONTH"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_month'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_month#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_MONTH"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}

	
	
	
	public function LIST_TABLES_QUOTA_TEMP(){
		if(isset($GLOBALS["LIST_TABLES_QUOTA_TEMP"])){return $GLOBALS["LIST_TABLES_QUOTA_TEMP"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'quotatemp_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#quotatemp_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_QUOTA_TEMP"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}	
	
	
	
	
	
	
	
	


	
	public function LIST_TABLES_NGINX_BLOCKED_RT(){
		if(isset($GLOBALS["LIST_TABLES_BLOCKED"])){return $GLOBALS["LIST_TABLES_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'ngixattck_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#ngixattck_[0-9]+$#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_BLOCKED"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
		
		
	}
	
	public function LIST_TABLES_BLOCKED(){
		if(isset($GLOBALS["LIST_TABLES_BLOCKED"])){return $GLOBALS["LIST_TABLES_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_blocked$#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_BLOCKED"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
		
	}
	


	
	
	

	
	public function LIST_TABLES_YOUTUBE_HOURS(){
		if(isset($GLOBALS["LIST_TABLES_YOUTUBE_HOURS"])){return $GLOBALS["LIST_TABLES_YOUTUBE_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'youtubehours_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#youtubehours_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_YOUTUBE_HOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "Return " . count($array)." tables\n";}
		return $array;		
		
	}

	public function LIST_TABLES_SEARCHWORDS_HOURS(){
		if(isset($GLOBALS["LIST_TABLES_SEARCHWORDS_HOURS"])){return $GLOBALS["LIST_TABLES_SEARCHWORDS_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'searchwords_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#searchwords_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_SEARCHWORDS_HOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "Return " . count($array)." tables\n";}
		return $array;		
		
	}	
	
	public function LIST_TABLES_SEARCHWORDS_DAY(){
		if(isset($GLOBALS["LIST_TABLES_SEARCHWORDS_DAY"])){return $GLOBALS["LIST_TABLES_SEARCHWORDS_DAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#searchwordsD_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_SEARCHWORDS_DAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "Return " . count($array)." tables\n";}
		return $array;
	
	}	
	
		
	public function LIST_TABLES_YOUTUBE_DAYS(){
		if(isset($GLOBALS["LIST_TABLES_YOUTUBE_DAYS"])){return $GLOBALS["LIST_TABLES_YOUTUBE_DAYS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND 
		table_name LIKE 'youtubeday_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#youtubeday_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_YOUTUBE_DAYS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "Return " . count($array)." tables\n";}
		return $array;		
		
	}

	
	public function LIST_TABLES_YOUTUBE_WEEK(){
		if(isset($GLOBALS["LIST_TABLES_YOUTUBE_WEEK"])){return $GLOBALS["LIST_TABLES_YOUTUBE_WEEK"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND
		table_name LIKE 'youtubeweek_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#youtubeweek_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_YOUTUBE_WEEK"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "Return " . count($array)." tables\n";}
		return $array;
	
	}	
	

	
	
	public function move_category($orginal_md5,$category,$nextCategory){
		
		if($nextCategory==null){echo "Error no next category set\n";return;}
		
		if(!isset($GLOBALS["uuid"])){$sock=new sockets();$GLOBALS["uuid"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));}
		$uuid=$GLOBALS["uuid"];
		$table=$this->cat_totablename($category);
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT * FROM $table WHERE zmd5='$orginal_md5'"));
		if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
		$www=$ligne["pattern"];
		if($www==null){
			echo "!!!!!!!!!!! Error, no website in table $table $orginal_md5 !!!!!!!!!!!!!! \n";
			return;
		}
		$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('{$ligne["pattern"]}','$category','$orginal_md5')";
		$this->QUERY_SQL($sql);
		if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
		
		$newmd5=md5($nextCategory.$www);
		$next_table=$this->cat_totablename($nextCategory);
		if($next_table==null){echo "Error no next table `$next_table` ($nextCategory) \n";return;}
		
		
		$this->QUERY_SQL("DELETE FROM $table WHERE zmd5='$orginal_md5'");
		if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
		
		
		
		
		$this->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$nextCategory','$www','$uuid')");
		if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
		$this->QUERY_SQL("INSERT IGNORE INTO $next_table (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$newmd5',NOW(),'$nextCategory','$www','$uuid',1)");
		if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
		if($GLOBALS["VERBOSE"]){echo "[OK]:: Move $www From $table to $next_table \n";}
		
	}
	
	public function move_to_unknown($orginal_md5,$category){
			if(!isset($GLOBALS["uuid"])){$sock=new sockets();$GLOBALS["uuid"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));}
			$table=$this->cat_totablename($category);
			$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT * FROM $table WHERE zmd5='$orginal_md5'"));
			if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
			$www=$ligne["pattern"];
			if($www==null){
				echo "!!!!!!!!!!! Error, no website!!!!!!!!!!!!!! \n";
				return;
			}
			$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('{$ligne["pattern"]}','$category','$orginal_md5')";
			$this->QUERY_SQL($sql);
			if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
			$this->QUERY_SQL("DELETE FROM $table WHERE zmd5='$orginal_md5'");
			$ipaddr=gethostbyname($www);
			$family=$this->GetFamilySites($www);
			$this->QUERY_SQL("INSERT IGNORE INTO webtests (sitename,ipaddr,family) VALUES ('$www','$ipaddr','$family')");
			if(!$this->ok){echo "Fatal: $this->mysql_error Line:".__LINE__."\n";return;}
			if($GLOBALS["VERBOSE"]){echo "[OK]:: Move $www From $table to webtests \n";}
	}
	
	
	
	

	
	
	
	
	public function LIST_TABLES_CACHEHOURS(){
		if(isset($GLOBALS["LIST_TABLES_CACHEHOURS"])){return $GLOBALS["LIST_TABLES_CACHEHOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'cachehour_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#cachehour_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_CACHEHOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}

	public function LIST_TABLES_STATS_UAPP(){
		if(isset($GLOBALS["LIST_TABLES_STATS_UAPP"])){return $GLOBALS["LIST_TABLES_STATS_UAPP"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE '%_statsuapp'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_statsuapp#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_STATS_UAPP"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}	
	
	
	
	// UserSizeD_20130212
	public function LIST_TABLES_USERSIZED(){
		if(isset($GLOBALS["LIST_TABLES_USERSIZED"])){return $GLOBALS["LIST_TABLES_USERSIZED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#UserSizeD_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_USERSIZED"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}	
	
	public function LIST_TABLES_QUOTADAY(){
		if(isset($GLOBALS["LIST_TABLES_QUOTADAY"])){return $GLOBALS["LIST_TABLES_QUOTADAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE 'quotaday_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#quotaday_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_QUOTADAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}
	public function LIST_TABLES_QUOTAMONTH(){
		if(isset($GLOBALS["LIST_TABLES_QUOTAMONTH"])){return $GLOBALS["LIST_TABLES_QUOTAMONTH"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
				AND table_name LIKE 'quotamonth_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#quotamonth_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_QUOTAMONTH"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}
	
	
	
	public function LIST_CAT_FAMDAY(){
		if(isset($GLOBALS["LIST_CAT_FAMDAY"])){return $GLOBALS["LIST_CAT_FAMDAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
				AND table_name LIKE '%_catfam'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_catfam#", $ligne["c"])){
				$GLOBALS["LIST_CAT_FAMDAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	}	

	public FUNCTION TLSE_CONVERTION($officiels=false){
			$f["agressif"]="aggressive";
			$f["audio-video"]="audio-video";
			$f["youtube"]="youtube";
			$f["celebrity"]="celebrity";
			$f["cleaning"]="cleaning";
			$f["dating"]="dating";
			$f["filehosting"]="filehosting";
			$f["gambling"]="gamble";
			$f["hacking"]="hacking";
			$f["liste_bu"]="liste_bu";
			$f["manga"]="manga";
			$f["mobile-phone"]="mobile-phone";
			$f["press"]="news";
			$f["radio"]="webradio";
			$f["translation"]="translators";
			$f["bitcoin"]="paytosurf";
			$f["violence"]="violence";
			$f["drugs"]="drugs";
			$f["redirector"]="proxy";
			$f["sexual_education"]="sexual_education";
			$f["sports"]="recreation/sports";
			$f["tricheur"]="tricheur";
			$f["webmail"]="webmail";
			$f["adult"]="porn";
			$f["arjel"]="arjel";
			$f["bank"]="finance/banking";
			$f["chat"]="chat";
			$f["cooking"]="hobby/cooking";
			$f["drogue"]="drugs";
			$f["financial"]="financial";
			$f["games"]="games";
			$f["jobsearch"]="jobsearch";
			$f["marketingware"]="marketingware";
			$f["phishing"]="phishing";
			$f["remote-control"]="remote-control";
			$f["shopping"]="shopping";
			$f["strict_redirector"]="strict_redirector";
			$f["astrology"]="astrology";
			$f["blog"]="blog";
			$f["child"]="children";
			$f["dangerous_material"]="dangerous_material";
			$f["forums"]="forums";
			$f["lingerie"]="sex/lingerie";
			$f["malware"]="malware";
			$f["mixed_adult"]="mixed_adult";
			$f["publicite"]="publicite";
			$f["reaffected"]="reaffected";
			$f["sect"]="sect";
			$f["social_networks"]="socialnet";
			$f["strong_redirector"]="strong_redirector";
			$f["warez"]="warez";
			$f["verisign"]="sslsites";
			
			if(!$officiels){
				$f["aggressive"]="aggressive";
				$f["children"]="children";
				$f["drugs"]="drugs";
				$f["finance_banking"]="finance/banking";
				$f["gamble"]="gamble";
				$f["hobby_cooking"]="hobby/cooking";
				$f["porn"]="porn";
				$f["proxy"]="proxy";
				$f["recreation_sports"]="recreation/sports";
				$f["sex_lingerie"]="sex/lingerie";
				$f["socialnet"]="socialnet";
				$f["webradio"]="webradio";	
			}	
			
			
			
			return $f;		
		
	}
	
	
	public function LIST_TABLES_ARTICA_SQUIDLOGS(){
		if(isset($GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS2"])){return $GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS2"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_ARTICA_SQUIDLOGS2"]=$array;
		return $array;
	
	}
	
	
	public function LIST_TABLES_DAYS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_DAYS"])){return $GLOBALS["SQUID_LIST_TABLES_DAYS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_day' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["SQUID_LIST_TABLES_DAYS"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_day#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["SQUID_LIST_TABLES_DAYS"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_DAYS count:". count($GLOBALS["SQUID_LIST_TABLES_DAYS"])."\n";}
		return $array;		
	}	
	
	public function LIST_TABLES_FAMILY(){
		if(isset($GLOBALS["LIST_TABLES_FAMILY"])){return $GLOBALS["LIST_TABLES_FAMILY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE '%_family' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["LIST_TABLES_FAMILY"])."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_family#", $ligne["c"])){
				$array[$ligne["c"]]=$ligne["c"];
				$GLOBALS["LIST_TABLES_FAMILY"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_FAMILY count:". count($GLOBALS["LIST_TABLES_FAMILY"])."\n";}
		return $array;
	}	
	
	public function COUNT_ALL_TABLES(){
		
		$sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = 'squidlogs'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		return array($ligne["tcount"],$ligne["x"]);
	}	
	
	public function LIST_TABLES_WEEKS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_WEEKS"])){return $GLOBALS["SQUID_LIST_TABLES_WEEKS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_week' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["SQUID_LIST_TABLES_WEEKS"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_week#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["SQUID_LIST_TABLES_WEEKS"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "SQUID_LIST_TABLES_WEEKS count:". count($GLOBALS["SQUID_LIST_TABLES_WEEKS"])."\n";}
		return $array;			
		
	}
	
	
	
	public function LIST_TABLES_WEEKS_BLOCKED(){
		if(isset($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])){return $GLOBALS["LIST_TABLES_WEEKS_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked_week' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_blocked_week#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["LIST_TABLES_WEEKS_BLOCKED"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_WEEKS_BLOCKED count:". count($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])."\n";}
		return $array;			
		
	}

	public function LIST_TABLES_DAYS_BLOCKED(){
		if(isset($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])){return $GLOBALS["LIST_TABLES_DAYS_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#^[0-9]+_blocked$#", trim($ligne["c"]))){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["LIST_TABLES_DAYS_BLOCKED"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_DAYS_BLOCKED count:". count($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])."\n";}
		return $array;			
		
	}	
	
	public function LIST_TABLES_MEMBERS(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_members' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_members#", $ligne["c"])){
				$f=explode("_",$ligne["c"]);
				if(strlen($f[0])<8){continue;}
				$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;		
	}
	
	public function LIST_TABLES_MEMBERS_MONTH(){
		$LIST_TABLES_MEMBERS=$this->LIST_TABLES_MEMBERS();
		$array=array();
		while (list ($tablesource, $b) = each ($LIST_TABLES_MEMBERS)){
			$time=$this->TIME_FROM_HOUR_TABLE($tablesource);
			$DAY=date("d",$time);
			$YEAR=date("Y",$time);
			$MONTH=date("m",$time);
			$month_table=date("Ym",$time)."_members";
			$array[$month_table]=$month_table;
		
		}
		return $array;
		
	}	
	
	
	public function LIST_TABLES_GCACHE(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE '%_gcache' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_gcache#", $ligne["c"])){$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;
	}	
	
	public function LIST_TABLES_GSIZE(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
				AND table_name LIKE '%_gsize' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_gsize#", $ligne["c"])){$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;
	}	
	
	public function LIST_TABLES_CACHED_RATED(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
				AND table_name LIKE '%_cacherated' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_cacherated#", $ligne["c"])){$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;
	}
	
	

	
	public function LIST_TABLES_WWWUID(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
				AND table_name LIKE 'www_%' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#^www_.*#", $ligne["c"])){$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;
	}

	
	public function HIER_TIME(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return strtotime($ligne["tdate"]." 00:00:00");
	}
	
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];
	}
	
	public function ILYA5HOURS(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 5 HOUR),'%H') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];		
	}
	
	public function LIST_TABLES_CATEGORIES_PERSO(){
		if(isset($GLOBALS["LIST_TABLES_CATEGORIES_PERSO"])){return $GLOBALS["LIST_TABLES_CATEGORIES_PERSO"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
		if(!$this->BD_CONNECT(true)){ writelogs("Fatal Error: Unable to BD_CONNECT()",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array(); }
	
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
		$Count=mysql_num_rows($results);
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["c"]=="category_"){$this->QUERY_SQL("DROP TABLE `category_`");continue;}
			$Count=$this->COUNT_ROWS($ligne["c"]);
			if($Count==0){continue;}
			$array[$ligne["c"]]=$ligne["c"];
		}
	
	
		$GLOBALS["LIST_TABLES_CATEGORIES_PERSO"]=$array;
		return $array;
	
	}	
	
	public function LIST_TABLES_CATEGORIES(){
		if(isset($GLOBALS["LIST_TABLES_CATEGORIES"])){return $GLOBALS["LIST_TABLES_CATEGORIES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
		if(!$this->BD_CONNECT(true)){ writelogs("Fatal Error: Unable to BD_CONNECT()",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array(); }
		
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		$Count=mysql_num_rows($results);
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["c"]=="category_"){$this->QUERY_SQL("DROP TABLE `category_`");continue;}
			$array[$ligne["c"]]=$ligne["c"];
		}
		
		
		$ctz=new mysql_catz(true);
		$TransArray=$ctz->TransArray();
		while (list ($tablename,$categoryname ) = each ($TransArray) ){
			if($this->COUNT_ROWS($tablename)==0){continue;}
			$array[$tablename]=$tablename;
		}
		$GLOBALS["LIST_TABLES_CATEGORIES"]=$array;
		return $array;
		
	}

	public function LIST_TABLES_WEIGHTED(){
		if(isset($GLOBALS["LIST_TABLES_WEIGHTED"])){return $GLOBALS["LIST_TABLES_WEIGHTED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'weigthed_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["VERBOSE"]){echo "{$ligne["c"]}\n";}
			if($ligne["c"]=="weighted_"){$this->QUERY_SQL("DROP TABLE `weighted_`");}
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_WEIGHTED"]=$array;
		return $array;
		
	}	
	
	public function UPDATE_CATEGORIES_TABLES($sitename,$category){
		if(trim($sitename)==null){return;}
		if(trim($category)==null){return;}
		$array=$this->LIST_TABLES_HOURS();
		while (list ($num, $tablename) = each ($array) ){
			$this->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$sitename'");
		}
		
			
	}
	
	public function UPDATE_WEBSITES_TABLES($sitename,$newsitename){
		if(trim($sitename)==null){return;}
		if(trim($newsitename)==null){return;}
		$array=$this->LIST_TABLES_HOURS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET sitename='$newsitename' WHERE sitename='$sitename'");}
		$array=$this->LIST_TABLES_DAYS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET sitename='$newsitename' WHERE sitename='$sitename'");}		
	}
	
	public function ACCOUNTS_ISP(){
		$array=array();
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$sql="SELECT userid,publicip FROM usersisp WHERE enabled=1";
		$results=$this->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["publicip"]==null){continue;}
			$array[$ligne["publicip"]]=$ligne["userid"];
		}
		
		$GLOBALS[__CLASS__.__FUNCTION__]=$array;
		return $array;
	}
	
	public function TableNudityHour($prefix=null){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if($prefix==null){$prefix=date("YmdH");}
		
		$table="znudehour_$prefix";
		
		if(!$this->TABLE_EXISTS($table,$this->database)){
		writelogs("Checking $table in $this->database NOT EXISTS...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sql="CREATE TABLE IF NOT EXISTS `$table` (
		  `sitename` varchar(90) NOT NULL,
		  `uri` varchar(255) NOT NULL,
		  `ipaddr` varchar(50) NOT NULL DEFAULT '',
		  `hostname` varchar(120) NOT NULL DEFAULT '',
		  `zDate` datetime NOT NULL,
		  `zMD5` CHAR(32) NOT NULL,
		  `uid` varchar(128) NOT NULL,
		  `MAC` varchar(20) NOT NULL,
		  `POURC` INT(3) NOT NULL,
		  PRIMARY KEY (`zMD5`),
		  KEY `sitename` (`sitename`),
		  KEY `hostname` (`hostname`),
		  KEY `zDate` (`zDate`),
		  KEY `ipaddr` (`ipaddr`),
		  KEY `uid` (`uid`),
		  KEY `POURC` (`POURC`),
		  KEY `MAC` (`MAC`)
		) ENGINE=MYISAM;";
	  $this->QUERY_SQL($sql,$this->database); 
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";
				return false;
			}
		}
		
		return true;
		
	}	
	
	
	public function categorize($www,$category,$noprocess=false){
		if(trim($www)==null){return;}
		if(trim($category)==null){return;}
		if(!isset($_GET["week"])){$_GET["week"]=null;}
		if(!isset($_GET["day"])){$_GET["day"]=null;}
		if(!isset($_GET["enabled"])){$_GET["enabled"]=1;}
		$sock=new sockets();
		if(!isset($GLOBALS["UUID"])){$GLOBALS["UUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));}
		$uuid=$GLOBALS["UUID"];
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^(.+?)\?#", $www,$re)){$www=$re[1];}
		if(preg_match("#^(.+?)\/#", $www,$re)){$www=$re[1];}
		if(strpos(" $www", "/")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", ";")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", ",")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "$")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "%")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "!")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "&")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "<")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", ">")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "[")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "]")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "(")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", ")")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "+")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		if(strpos(" $www", "?")>0){if($GLOBALS["VERBOSE"]){echo "$www Bad pattern\n";}return;}
		$www=trim(strtolower($www));
		
		if(function_exists("idn_to_ascii")){
			$www = @idn_to_ascii($www, "UTF-8");
		}
		
		
		
		$md5=md5($category.$www);
		$enabled=$_GET["enabled"];
		$this->CreateCategoryTable($category);
		$category_table=$this->category_transform_name($category);
		$sql="SELECT zmd5 FROM category_$category_table WHERE pattern='$www'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql));
		
		
		if($ligne["zmd5"]<>null){
			if($GLOBALS["VERBOSE"]){echo "$www Already exists in category_$category_table ($category)\n";}
			return true;
		}
		
		$sql_add="INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')";
		$sql_add2="INSERT IGNORE INTO category_$category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')";
		$sql_edit="DELETE FROM category_$category_table WHERE zmd5='{$ligne["zmd5"]}'";
		
		
		$ligne["zmd5"]=trim($ligne["zmd5"]);
		
		if($ligne["zmd5"]==null){
			$this->QUERY_SQL($sql_add2);
			
		}
		
		if($GLOBALS["VERBOSE"]){echo "Adding new website $www in category_$category_table ($category)\n";}
		$this->QUERY_SQL("DELETE FROM notcategorized WHERE sitename='$www'");
		if(!$this->ok){echo $this->mysql_error."\n";echo $sql."\n";}
		$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
		if(!$this->ok){echo $this->mysql_error."\n";echo $sql."\n";}
		
		if($noprocess){return;}
		$cats=addslashes($this->GET_CATEGORIES($www,true,true,true));
		
		$this->QUERY_SQL("UPDATE visited_sites SET category='$cats' WHERE sitename='$www'");
		if(!$this->ok){echo $this->mysql_error."\n";echo $sql."\n";}
		
		
		$newmd5=md5("$cats$www");
		$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$www','$cats')");
		if(!$this->ok){echo $this->mysql_error."\n";echo $sql."\n";}
		if($enabled==1){
			$this->QUERY_SQL("DELETE FROM categorize_delete WHERE zmd5='$md5'");
		}else{
			$this->QUERY_SQL("INSERT IGNORE INTO categorize_delete(zmd5,sitename,category) VALUES('$md5','$www','$category')");
		}
		
		if($_GET["week"]<>null){$_GET["day"]=$_GET["week"];}
		
		
		if($_GET["day"]<>null){
		$time=strtotime($_GET["day"]." 00:00:00");
				$tableSrc=date('Ymd')."_hour";
		if(!$this->TABLE_EXISTS($tableSrc)){$this->CreateHourTable($tableSrc);}
				$this->QUERY_SQL("UPDATE $tableSrc SET category='$cats' WHERE sitename='$www'");
				if(!$this->ok){echo $this->mysql_error;}
				$tableWeek=date("YW",$time)."_week";
				$this->QUERY_SQL("UPDATE $tableWeek SET category='$cats' WHERE sitename='$www'");
		}
		
		
		if(!isset($GLOBALS[__CLASS__."/".__FUNCTION__])){
			$sock->getFrameWork("cmd.php?export-community-categories=yes");
			$sock->getFrameWork("squid.php?re-categorize=yes");	
			$GLOBALS[__CLASS__."/".__FUNCTION__]=true;
		}	
		
	}


	
	public function CheckReportTable(){
		$sql="CREATE TABLE IF NOT EXISTS `reports_cache` (
		`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`zmd5` VARCHAR(90),
		`title` VARCHAR(128),
		`ou` VARCHAR(128),
		`uid` VARCHAR(128),
		`report_type` VARCHAR(40),
		`builded` smallint(1) NOT NULL DEFAULT 0,
		`zDate` datetime NOT NULL,
		`params` TEXT,
		`values` LONGBLOB,
		`values_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
		UNIQUE KEY `zmd5`  (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `report_type` (`report_type`),
		KEY `ou` (`ou`),
		KEY `uid` (`uid`),
		KEY `title` (`title`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		if(!$this->FIELD_EXISTS("reports_cache", "ou")){
			$this->QUERY_SQL("ALTER IGNORE TABLE `reports_cache` ADD `ou` VARCHAR( 128 ) NOT NULL ,ADD INDEX( `ou` )");
		}
		if(!$this->FIELD_EXISTS("reports_cache", "uid")){
			$this->QUERY_SQL("ALTER IGNORE TABLE `reports_cache` ADD `uid` VARCHAR( 128 ) NOT NULL ,ADD INDEX( `uid` )");
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `mgr_client_list` (
		`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
		`ipaddr` VARCHAR(90),
		`uid` VARCHAR(90),
		`CUR_CNX` BIGINT UNSIGNED,
		`RQS` BIGINT UNSIGNED,
		`TAG_NONE` BIGINT UNSIGNED,
		`TCP_HIT` BIGINT UNSIGNED,
		`TCP_MISS` BIGINT UNSIGNED,
		`TCP_REDIRECT`BIGINT UNSIGNED,
		`TCP_TUNNEL` BIGINT UNSIGNED,
		KEY `ipaddr` (`ipaddr`),
		KEY `uid` (`uid`),
		KEY `RQS` (`RQS`),
		KEY `TAG_NONE` (`TAG_NONE`),
		KEY `TCP_HIT` (`TCP_HIT`),
		KEY `TCP_REDIRECT` (`TCP_REDIRECT`),
		KEY `TCP_MISS` (`TCP_MISS`),
		KEY `CUR_CNX` (`CUR_CNX`)
		) ENGINE=MYISAM;";		
		$this->QUERY_SQL($sql,$this->database);
	}
	

	
	public function check_hypercacheHour($table=null){
		if($table==null){
			$prefix=date("YmdH");
			$table="hypercache_$prefix";
		}
	
		$sql="CREATE TABLE IF NOT EXISTS `$table` (
		`zmd5` VARCHAR(128) PRIMARY KEY,
		`zDate` datetime NOT NULL,
		`hits` BIGINT UNSIGNED,
		`category` VARCHAR(60),
		`ipaddr` VARCHAR(90),
		KEY `zDate` (`zDate`),
		KEY `category` (`category`),
		KEY `ipaddr` (`ipaddr`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
	
		if(!$this->ok){ return false; }
			return true;
		}	
	
	public function TablePrimaireCacheHour($prefix=null,$nomem=false,$table=null){
		
		if($prefix>0){
			$table="cachehour_$prefix";
		}
		
		if($table==null){
			$prefix=date("YmdH");
			$table="cachehour_$prefix";
		}
		
		$MEM="ENGINE=MYISAM";
		
		if($GLOBALS["VERBOSE"]){echo "CREATE TABLE $table...\n";}
	
		$sql="CREATE TABLE IF NOT EXISTS `$table` (
		
		`zDate` datetime NOT NULL,
		`size` BIGINT UNSIGNED,
		cached smallint(1),
		`familysite` VARCHAR(128) NOT NULL,
		KEY `zDate` (`zDate`),
		KEY `cached` (`cached`),
		KEY `size` (`size`),
		KEY `familysite` (`familysite`)
		) $MEM;";
		$this->QUERY_SQL($sql,$this->database);
	
		if(!$this->ok){ return false; }
	
		}	
	
	public function check_SearchWords_hour($timekey=null,$table=null){
		include_once(dirname(__FILE__)."/class.realtime-buildsql.inc");
		if($table<>null){
			if(preg_match("#searchwords_(.+)#", $table,$re)){$timekey=$re[1];}
		}
		if($timekey==null){$timekey=date('YmdH');}
		REALTIME_searchwords($timekey);
		
		return true;
		
	}	
	public function check_SearchWords_day($timekey=null){
		if($timekey==null){$timekey=date('Ymd');}
		
		$table="searchwordsD_$timekey";
		if(!$this->TABLE_EXISTS("$table",$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`zmd5` VARCHAR(128) PRIMARY KEY,
			`hits` INT UNSIGNED NOT NULL,
			`sitename` varchar(90) NOT NULL,
			`zDate` date NOT NULL,
			`hour` smallint(2) NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`familysite` varchar(128) NOT NULL,
			`words` VARCHAR(255) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `sitename`(`sitename`),
			 KEY `familysite`(`familysite`),
			 KEY `zDate`(`zDate`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `words`(`words`),
			 KEY `hits`(`hits`),
			 KEY `hour`(`hour`),
			 KEY `account`(`account`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){return false;}
		}
		
		return true;
		
	}	
	
	public function check_SearchWords_week($timekey=null){
		if($timekey==null){return false;}
	
		$table="searchwordsW_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`zmd5` VARCHAR(128) PRIMARY KEY,
			`hits` INT UNSIGNED NOT NULL,
			`sitename` varchar(90) NOT NULL,
			`day` date NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`familysite` varchar(128) NOT NULL,
			`words` VARCHAR(255) NOT NULL,
			KEY `ipaddr`(`ipaddr`),
			KEY `sitename`(`sitename`),
			KEY `familysite`(`familysite`),
			KEY `day`(`day`),
			KEY `hostname`(`hostname`),
			KEY `uid`(`uid`),
			KEY `MAC`(`MAC`),
			KEY `words`(`words`),
			KEY `hits`(`hits`),
			KEY `account`(`account`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){return false;}
		
	
			return true;
	
		}	
	
	public function check_youtube_hour($timekey=null){
		if($timekey==null){$timekey=date('YmdH');}
		
		$table="youtubehours_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`zDate` datetime NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`youtubeid` VARCHAR(60) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `zDate`(`zDate`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 ) ENGINE=MEMORY;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){return false;}
			return true;
	}
	
	
	public function check_quota_hour_tmp($timekey=null,$table=null){
		if($table==null){if($timekey==null){$timekey=date('YmdH');}
	
		$table="quotatemp_$timekey";}
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
		`xtime` datetime NOT NULL,
		`keyr` VARCHAR(90) NOT NULL,
		`ipaddr` VARCHAR(40),
		`familysite` VARCHAR(128),
		`servername` VARCHAR(255),
		`uid` VARCHAR(40) NOT NULL,
		`ou` VARCHAR(128) NOT NULL,
		`MAC` VARCHAR(20) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		KEY `ipaddr`(`ipaddr`),
		KEY `familysite`(`familysite`),
		KEY `keyr`(`keyr`),
		KEY `xtime`(`xtime`),
		KEY `ou`(`ou`),
		KEY `servername`(`servername`),
		KEY `uid`(`uid`),
		KEY `MAC`(`MAC`)
		) ENGINE=MEMORY;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		return true;
	}	
	
	
	
	

	
	
	
	
	public function check_nginx_attacks_RT($timekey=null){
		if($timekey==null){$timekey=date('YmdH');}
	
		$table="ngixattck_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
		`zDate` DATETIME NOT NULL,
		`ipaddr` VARCHAR(40),
		`familysite` VARCHAR(128),
		`hostname` VARCHAR(255),
		`country` VARCHAR(40) NOT NULL,
		`servername` VARCHAR(255) NOT NULL,
		`keyr` VARCHAR(90) PRIMARY KEY,
		KEY `zDate`(`zDate`),
		KEY `familysite`(`familysite`),
		KEY `hostname`(`hostname`),
		KEY `country`(`country`),
		KEY `servername`(`servername`)
		) ENGINE=MEMORY;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){
			if(function_exists("Debuglogs")){Debuglogs($this->mysql_error);}
			return false;}
		return true;
	}	
	
	public function check_nginx_attacks_DAY($timekey=null){
		if($timekey==null){$timekey=date('Ymd');}
	
		$table="ngixattckd_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
		`zmd5` VARCHAR(90) PRIMARY KEY,
		`hour` smallint(2) NOT NULL,
		`ipaddr` VARCHAR(40),
		`familysite` VARCHAR(128),
		`hostname` VARCHAR(255),
		`country` VARCHAR(40) NOT NULL,
		`servername` VARCHAR(255) NOT NULL,
		`hits` INT UNSIGNED NOT NULL,
		KEY `hour`(`hour`),
		KEY `hits`(`hits`),
		KEY `familysite`(`familysite`),
		KEY `hostname`(`hostname`),
		KEY `country`(`country`),
		KEY `servername`(`servername`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		return true;
	}	
	
	public function check_quota_day($timekey=null){
		if($timekey==null){$timekey=date('Ymd');}
	
		$table="quotaday_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
		`keyr` VARCHAR(90) PRIMARY KEY,
		`hour` smallint(2) NOT NULL,
		`ipaddr` VARCHAR(40),
		`familysite` VARCHAR(128),
		`servername` VARCHAR(255),
		`uid` VARCHAR(40) NOT NULL,
		`ou` VARCHAR(128) NOT NULL,
		`MAC` VARCHAR(20) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		KEY `ipaddr`(`ipaddr`),
		KEY `familysite`(`familysite`),
		KEY `hour`(`hour`),
		KEY `servername`(`servername`),
		KEY `uid`(`uid`),
		KEY `ou`(`ou`),
		KEY `MAC`(`MAC`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		
		if(!$this->FIELD_EXISTS("$table", "ou")){
			$this->QUERY_SQL("ALTER IGNORE TABLE `$table` ADD `ou`VARCHAR( 128 ) NOT NULL ,ADD INDEX( `ou` )");
		}
		
		return true;
	}	
	
	public function check_quota_month($timekey=null){
		if($timekey==null){$timekey=date('Ym');}
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`quotachecked` ( 
			`tablename` VARCHAR(60) PRIMARY KEY,
			`ztime` DATETIME,
			KEY `ztime`(`ztime`)) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
	
		$table="quotamonth_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`keyr` VARCHAR(90) PRIMARY KEY,
			`day` smallint(2) NOT NULL,
			`ipaddr` VARCHAR(40),
			`familysite` VARCHAR(128),
			`servername` VARCHAR(255),
			`uid` VARCHAR(40) NOT NULL,
			`ou` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			KEY `ipaddr`(`ipaddr`),
			KEY `familysite`(`familysite`),
			KEY `day`(`day`),
			KEY `servername`(`servername`),
			KEY `uid`(`uid`),
			KEY `ou`(`ou`),
			KEY `MAC`(`MAC`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){return false;}
			return true;
		}	
	
	public function check_youtube_day($timekey=null){
		if($timekey==null){$timekey=date('Ymd');}
		
		$table="youtubeday_$timekey";
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`zmd5` VARCHAR(128) PRIMARY KEY,
			`zDate` date NOT NULL,
			`hour` smallint(2) NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`youtubeid` VARCHAR(60) NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `hour`(`hour`),
			 KEY `hits`(`hits`),
			 KEY `zDate`(`zDate`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){return false;}
			
		
		
		return true;
	}
	
	
public function createWeekYoutubeTable($week=null){
	if(preg_match("#youtubeweek_[0-9]+#", $week)){$tablename=$week;}
		else{
			if(!is_numeric($week)){$week=date('YW');}
			$tablename="youtubeweek_{$week}";
			}
			
	if($this->TABLE_EXISTS($tablename,'artica_events')){return true;}	
	
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	  `zmd5` varchar(128) NOT NULL,
	  `day` smallint(2) NOT NULL,
	  `ipaddr` varchar(40) DEFAULT NULL,
	  `hostname` varchar(128) DEFAULT NULL,
	  `uid` varchar(40) NOT NULL,
	  `MAC` varchar(20) NOT NULL,
	  `account` BIGINT UNSIGNED NOT NULL,
	  `youtubeid` varchar(60) NOT NULL,
	  `hits` BIGINT UNSIGNED NOT NULL,
	  PRIMARY KEY (`zmd5`),
	  KEY `ipaddr` (`ipaddr`),
	  KEY `hits` (`hits`),
	  KEY `day` (`day`),
	  KEY `hostname` (`hostname`),
	  KEY `uid` (`uid`),
	  KEY `MAC` (`MAC`),
	  KEY `account` (`account`)
	) ENGINE = MYISAM;";	
	
	$this->QUERY_SQL($sql);
	if(!$this->ok){return false;}
	return true;
	
}

public function CheckTablesBlocked_day($time=0,$tableblock=null){
	if(!is_numeric($time)){$time=time();}
	if($tableblock==null){
		if($time==0){$time=time();}
		$tableblock=date('Ymd',$time)."_blocked";
	}

	
	
			$sql="CREATE TABLE IF NOT EXISTS `$tableblock` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `zmd5` varchar(90) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `client` varchar(90) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `website` varchar(125) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `rulename` varchar(50) NOT NULL,
			  `public_ip` varchar(40) NOT NULL,
			  `uri` varchar(255) NOT NULL,
			  `event` varchar(20) NOT NULL,
			  `why` varchar(90) NOT NULL,
			  `explain` text NOT NULL,
			  `blocktype` varchar(255) NOT NULL,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `zmd5` (`zmd5`),
			  KEY `zDate` (`zDate`),
			  KEY `uid` (`uid`),
			  KEY `client` (`client`),
			  KEY `MAC` (`MAC`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `website` (`website`),
			  KEY `category` (`category`),
			  KEY `rulename` (`rulename`),
			  KEY `public_ip` (`public_ip`),
			  KEY `event` (`event`),
			  KEY `why` (`why`)
			) ENGINE=MYISAM;"; 
		$this->QUERY_SQL($sql); 
		if(!$this->ok){
			writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_error=$this->mysql_error."\n$sql";
			return false;
		}
		
		if(!$this->FIELD_EXISTS("$tableblock", "zmd5")){
			$this->QUERY_SQL("ALTER IGNORE TABLE `$tableblock` ADD `zmd5` 
			VARCHAR( 90 ) NOT NULL ,ADD UNIQUE KEY( `zmd5` )");
		}

		
		
	$this->RepairTableBLock($tableblock);	
	return true;

}

public function create_webfilters_categories_caches($nofill=false){
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_categories_caches` (
			  `categorykey` varchar(50) NOT NULL,
			  `description` varchar(255) NOT NULL,
			  `picture` varchar(50) NOT NULL,
			  `master_category` varchar(50) NOT NULL,
			  `categoryname` varchar(128) NOT NULL,
			  PRIMARY KEY (`categorykey`),
			  KEY `category` (`master_category`),
			  KEY `categoryname` (`categoryname`),
			  KEY `description` (`description`)
			) ENGINE = MEMORY;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["VERBOSE"]){echo "<H2>$q->mysql_error</H2>\r\n";}}
			
			
			
			if($this->ok){return true;}
			return false;

}

public function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$this->BD_CONNECT();
		$this->ok=true;
		$results=mysql_query($sql,$this->mysql_connection);
		if(!$results){$this->ok=false;}
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			
			
			return $array;
		}
		
		public function SHOW_STATUS(){
			$sql="SHOW STATUS;";
			$this->BD_CONNECT();
		$this->ok=true;
		$results=mysql_query($sql,$this->mysql_connection);
		if(!$results){$this->ok=false;}
			$errnum=@mysql_error($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			$this->mysql_error=$des;
		
		
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$Variable_name=$ligne["Variable_name"];
				$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
}

function ifStatisticsMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	
	if(isset($WizardStatsAppliance["AS_DISCONNECTED"])){
		if($WizardStatsAppliance["AS_DISCONNECTED"]==1){
			return true;
		}
	}
	
	
	if($EnableRemoteStatisticsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "EnableRemoteStatisticsAppliance = 1 -> FALSE\n";}
		return false;}
	if($EnableWebProxyStatsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance = 1 -> FALSE\n";}
		return true;}
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){
		if($GLOBALS["VERBOSE"]){echo "CategoriesRepositoryEnable = 1 -> FALSE\n";}
		return true;}
	return true;
}

function ifSquidUpdatesMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->APP_UFDBGUARD_INSTALLED){
		$GLOBALS["ifMustBeExecuted"][]="APP_UFDBGUARD_INSTALLED = FALSE";
		if($GLOBALS["OUTPUT"]){echo "Info..........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} APP_UFDBGUARD_INSTALLED FALSE\n";}
		return false;
	}
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){
		if($GLOBALS["OUTPUT"]){echo "Info..........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableArticaMetaServer TRUE\n";}
		return true;
	}
	if($sock->EnableUfdbGuard()==0){
		$GLOBALS["ifMustBeExecuted"][]="EnableUfdbGuard = FALSE";
		if($GLOBALS["OUTPUT"]){echo "Info..........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableUfdbGuard FALSE\n";}
		return false;
	}
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if($UseRemoteUfdbguardService==1){
		$GLOBALS["ifMustBeExecuted"][]="UseRemoteUfdbguardService = 1";
		if($GLOBALS["OUTPUT"]){echo "Info..........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} UseRemoteUfdbguardService TRUE\n";}
		return false;
	}
	return true;
	
}



private function  Hotspot_debug($text){
	if(!$GLOBALS["SPLASH_DEBUG"]){return;}
	if(function_exists("WLOG")){
		WLOG($text);
		return;
	}
	writelogs($text,"Hotspot_SessionActive",__FILE__,0);
	
}

private function TimeDiffMinutes($oldtime){
	
	$data1 = $oldtime;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

public function Hotspot_SessionActive($array){
	$LOGIN=$array["LOGIN"];
	if(trim($LOGIN)<>null){return $LOGIN;}
	$IPADDR=$array["IPADDR"];
	if($IPADDR<>null){$KEY=$IPADDR;}
	
	
	
	if(isset($GLOBALS[__FUNCTION__][$KEY])){
		
		$TimeDiffMinutes=$this->TimeDiffMinutes($GLOBALS[__FUNCTION__][$KEY]["TIME"]);
		if($TimeDiffMinutes<10){
			$this->Hotspot_debug("[{$IPADDR}]: CACHE {$TimeDiffMinutes}mn/10mn = {$GLOBALS[__FUNCTION__][$KEY]["UID"]}");
			return $GLOBALS[__FUNCTION__][$KEY]["UID"];
		}
	}
	
	
	$MAC=$array["MAC"];
	if($MAC==null){$MAC="00:00:00:00:00:00";}
	$HOST=$array["HOST"];
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");

	$this->Hotspot_debug("[{$IPADDR}]:SessionActive:: Check authentication against LOGIN=$LOGIN; IPADDR=$IPADDR; MAC=$MAC, HOST=$HOST");

	$time=time();




	if($MAC<>"00:00:00:00:00:00"){
		$sql="SELECT uid,logintime,hostname,ipaddr FROM hotspot_sessions WHERE maxtime>$time AND MAC='$MAC'";
		$this->Hotspot_debug("[{$IPADDR}]:SessionActive:: Verify session for MAC=$MAC $sql");
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){$this->Hotspot_debug("[{$IPADDR}]:SessionActive:: FATAL, MySQL error \"$this->mysql_error\" in line:".__LINE__);return;}
		if($ligne["uid"]<>null){
			$GLOBALS[__FUNCTION__][$KEY]["TIME"]=time();
			$GLOBALS[__FUNCTION__][$KEY]["UID"]=$ligne["uid"];
			$this->Hotspot_debug("[{$IPADDR}]:Found {$ligne["uid"]} ipaddr {$ligne["ipaddr"]}/{$ligne["hostname"]}");
			return $ligne["uid"];
		}
	}

	if($IPADDR<>null){
		$sql="SELECT uid,logintime,hostname,ipaddr FROM hotspot_sessions WHERE maxtime>$time AND ipaddr='$IPADDR'";
		$this->Hotspot_debug("[{$IPADDR}]:SessionActive:: Verify session for IPADDR=$IPADDR $sql");
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){$this->Hotspot_debug("SessionActive:: FATAL, MySQL error \"$this->mysql_error\" in line:".__LINE__);return;}
		if($ligne["uid"]<>null){
			$GLOBALS[__FUNCTION__][$KEY]["TIME"]=time();
			$GLOBALS[__FUNCTION__][$KEY]["UID"]=$ligne["uid"];			
			$this->Hotspot_debug("[{$IPADDR}]:Found {$ligne["uid"]} ipaddr {$ligne["ipaddr"]}/{$ligne["hostname"]}");
			return $ligne["uid"];
		}

	}

	$this->Hotspot_debug("[{$IPADDR}]:SessionActive:: No active session found, return none...");

}

private  function file_time_min($path){
	if(!is_dir($path)){
		if(!is_file($path)){return 100000;}
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

public function CheckTablesICAP(){
	
	
		$sql="CREATE TABLE IF NOT EXISTS `c_icap_services` (
					  `ID` INT(10)  NOT NULL AUTO_INCREMENT,
					  `service_name` varchar(128)  NOT NULL,
					
					  `service_key` varchar(64)  NOT NULL,
					  `respmod` varchar(40)  NOT NULL,
					  `routing` smallint(1)  NOT NULL,
					  `bypass`  smallint(1)  NOT NULL,
					  `overload` varchar(20)  NOT NULL,
					  `maxconn` smallint(10)  NOT NULL,
					  `enabled`  smallint(1)  NOT NULL,
					  `status`  smallint(1)  NOT NULL DEFAULT '0',
					  `zOrder`  smallint(1)  NOT NULL,
					  `ipaddr` varchar(128) NOT NULL,
					  `listenport` smallint(10)  NOT NULL,
					  `icap_server` varchar(50)  NOT NULL,
					  PRIMARY KEY (`ID`),
					  KEY `service_name` (`service_name`),
					  UNIQUE KEY `service_key` (`service_key`),
					  KEY `enabled` (`enabled`),
					  KEY `status` (`status`),
					  KEY `ipaddr` (`ipaddr`),
					  KEY `zOrder` (`zOrder`)
					)  ENGINE = MYISAM AUTO_INCREMENT = 20;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog(__LINE__.": Fatal $q->mysql_error");}
	
	
		if(!$this->FIELD_EXISTS("c_icap_services", "status")){$this->QUERY_SQL("ALTER TABLE `c_icap_services`  ADD `status` smallint(1)  NOT NULL DEFAULT '0' ,ADD KEY `status` (`status`)");}
	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=1"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload) 
				VALUES ('1','C-ICAP Antivirus - LOCAL - RESPONSE','service_antivir_rep','respmod_precache',1,1,0,0,'127.0.0.1',1345,'srv_clamav',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=2"));
	if(!is_numeric($ligne["ID"])){	
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('2','C-ICAP Antivirus - LOCAL - REQUEST','service_antivir_req','reqmod_precache',1,2,0,0,'127.0.0.1',1345,'srv_clamav',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}
		
	

	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=3"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload) 
				VALUES ('3','C-ICAP Antivirus - REMOTE - RESPONSE','service_antivir_rrep','respmod_precache',1,20,0,0,'10.20.0.2',1345,'srv_clamav',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}
	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=4"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('4','C-ICAP Antivirus - REMOTE - REQUEST','service_antivir_rreq','reqmod_precache',1,21,0,0,'10.20.0.2',1345,'srv_clamav',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}

	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=5"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('5','Kaspersky Antivirus - LOCAL - REQUEST','service_kaspersky_req','reqmod_precache',1,22,0,0,'127.0.0.1',1344,'av/reqmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=6"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('6','Kaspersky Antivirus - LOCAL - RESPONSE','service_kaspersky_rep','respmod_precache',1,22,0,0,'127.0.0.1',1344,'av/respmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}
	
	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=7"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('7','Kaspersky Antivirus - REMOTE - REQUEST','service_kaspersky_rreq','reqmod_precache',1,23,0,0,'10.20.0.2',1344,'av/reqmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	

	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=8"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('8','Kaspersky Antivirus - REMOTE - RESPONSE','service_kaspersky_rrep','respmod_precache',1,24,0,0,'10.20.0.2',1344,'av/respmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}

	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=9"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('9','Olfeo Web filtering - REMOTE - REQUEST','service_olfeo_rreq','reqmod_precache',1,25,0,0,'10.20.0.2',1344,'reqmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=10"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('10','WebSense Web filtering - REMOTE - REQUEST','service_websense_rreq','reqmod_precache',1,26,0,0,'10.20.0.2',1344,'request',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	
	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=11"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('11','Proventia Web Filter - REMOTE - REQUEST','service_proventia_rreq','reqmod_precache',1,27,0,0,'10.20.0.2',1344,'reqmod',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	
	
	$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM c_icap_services WHERE ID=12"));
	if(!is_numeric($ligne["ID"])){
		$sql="INSERT INTO c_icap_services (ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
				VALUES ('12','C-ICAP Web Filtering - LOCAL - REQUEST','service_url_check','reqmod_precache',1,3,0,0,'127.0.0.1',1345,'url_check_module',100,'bypass')";
		$this->QUERY_SQL($sql,$this->database);
	}	
	
	
	
}

public	function check_hotspot_session(){
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_sessions` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`maxtime` INT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`incoming` INT UNSIGNED ,
			`outgoing` INT UNSIGNED ,
			`nextcheck` BIGINT UNSIGNED,
			`ruleid` BIGINT UNSIGNED,
			`autocreate` smallint(1) NOT NULL DEFAULT 0,
			`username` VARCHAR( 128 ) NOT NULL ,
			`MAC` VARCHAR( 90 ) NOT NULL,
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			INDEX ( `logintime` , `maxtime` , `username` ,`finaltime`),
			KEY `MAC` (`MAC`),
			KEY `incoming` (`incoming`),
			KEY `ruleid` (`ruleid`),
			KEY `nextcheck` (`nextcheck`),
			KEY `autocreate` (`autocreate`),
			KEY `uid` (`uid`),
			KEY `hostname` (`hostname`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";
	$this->QUERY_SQL($sql,$this->database);
	if(!$this->ok){echo "CREATE TABLE hotspot_sessions Failed $this->mysql_error\n";}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "nextcheck")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `nextcheck` BIGINT UNSIGNED ,ADD INDEX ( `nextcheck` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "finaltime")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `finaltime` BIGINT UNSIGNED ,ADD INDEX ( `finaltime` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "ipaddr")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ipaddr` VARCHAR( 128 ) ,ADD INDEX ( `ipaddr` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "incoming")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `incoming` BIGINT UNSIGNED ,ADD INDEX ( `incoming` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "outgoing")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `outgoing` BIGINT UNSIGNED  ,ADD INDEX ( `outgoing` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "autocreate")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `autocreate` smallint(1) NOT NULL DEFAULT 1  ,ADD INDEX ( `autocreate` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "ruleid")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ruleid` BIGINT UNSIGNED,ADD INDEX ( `ruleid` )");}
	
}


public function LINK_RTTH_CURRENT($field,$value,$textInside,$fontsize=14,$color="black"){
	
	
	$currentHour=date("H");
	$current_tablename="RTTH_".date("YmdH");
	
	if($this->TABLE_EXISTS($current_tablename)){
		return "<a href='javascript:blur();' 
		OnClick=\"javascript:Loadjs('squid.member.RTTH.current.php?js=yes&field=$field&value=$value');\"
		style='font-size:$fontsize;text-decoration:underline;color:$color'>$textInside<a>";
	}
	
	return $textInside;
	
}

public function check_hotspot_tables(){


	
	$sql="CREATE TABLE IF NOT EXISTS `hotspot_sslwhitelists` (
				`objectid` INT UNSIGNED NOT NULL  PRIMARY KEY,
				`enabled` smallint(1) NOT NULL DEFAULT 1,
				KEY `enabled` (`enabled`)
			 )  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);	
	

	$sql="CREATE TABLE IF NOT EXISTS `hotspot_networks` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`zorder` INT UNSIGNED NOT NULL  DEFAULT 1, 
				`pattern` VARCHAR( 90 ) NOT NULL,
				`proto` VARCHAR( 20 ) NOT NULL DEFAULT 'tcp',
				`action` VARCHAR( 20 ) NOT NULL DEFAULT 'allow',
				`port` smallint(5) NULL,
				`hotspoted` smallint(1) NOT NULL,
				`direction` smallint(1) NOT NULL,
				`destination` VARCHAR( 90 ) NOT NULL,
				KEY `pattern` (`pattern`),
				KEY `zorder` (`zorder`),
				KEY `hotspoted` (`hotspoted`),
				KEY `proto` (`proto`),
				KEY `port` (`port`),
				KEY `action` (`action`)
			 )  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);
	
	$sql="CREATE TABLE IF NOT EXISTS `webfilters_paranoid` (
				`pattern` VARCHAR( 90 ) NOT NULL,
				`object` VARCHAR( 20 ) NOT NULL DEFAULT 'dstdomain',
				`zDate` datetime NOT NULL,
				PRIMARY KEY (`pattern`),
				KEY `object` (`object`),
				KEY `pattern` (`pattern`)
			 )  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);
	
	
	
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `global_whitelist` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR( 90 ) NOT NULL,
				`description` VARCHAR( 128 ) NOT NULL,
				`zDate` datetime NOT NULL,
				`type` smallint( 1 ) NOT NULL DEFAULT '0',
				`enabled` smallint(1) NULL,
				`ruletype` smallint(1) NULL,
				`deny_cache` smallint(1) NULL DEFAULT 0,
				`deny_auth` smallint(1) NULL DEFAULT 0,
				`deny_ufdb` smallint(1) NULL DEFAULT 0,
				`deny_icap` smallint(1) NULL DEFAULT 0,
				`deny_ext` smallint(1) NULL DEFAULT 0,
				`deny_global` smallint(1) NULL DEFAULT 0,
				`frommeta` smallint(1) NULL,
				 KEY `pattern` (`pattern`),
				 KEY `zDate` (`zDate`),
				 KEY `type` (`type`),
				 KEY `enabled` (`enabled`),
				 KEY `frommeta` (`frommeta`)
			 )  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);	
	
	
	
	
	if(!$this->FIELD_EXISTS("hotspot_networks", "proto")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks` 
		ADD `proto` VARCHAR( 20 ) NOT NULL DEFAULT 'tcp',
		ADD INDEX ( `proto` )");
	}
	if(!$this->FIELD_EXISTS("hotspot_networks", "zorder")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks`
		ADD `zorder` INT UNSIGNED NOT NULL  DEFAULT 1,
		ADD INDEX ( `zorder` )");
	}
	
	if(!$this->FIELD_EXISTS("hotspot_networks", "direction")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks`
		ADD `direction` smallint(1) NOT NULL  DEFAULT 0,
		ADD INDEX ( `direction` )");
	}
	if(!$this->FIELD_EXISTS("hotspot_networks", "destination")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks`
		ADD `destination` VARCHAR( 90 ) NULL,
		ADD INDEX ( `destination` )");
	}		
	
	if(!$this->FIELD_EXISTS("hotspot_networks", "action")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks`
		ADD `action` VARCHAR( 20 ) NOT NULL DEFAULT 'allow',
		ADD INDEX ( `action` )");
	}	
	
	if(!$this->FIELD_EXISTS("hotspot_networks", "port")){
		$this->QUERY_SQL("ALTER TABLE `hotspot_networks`
		ADD `port` smallint(5) NULL,
		ADD INDEX ( `port` )");
	}
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members` (
			`uid` VARCHAR( 128 ) NOT NULL ,
			`password` VARCHAR( 128 ) NOT NULL ,
			`creationtime` INT UNSIGNED NOT NULL,
			`ttl` INT UNSIGNED ,
			`sessiontime` INT UNSIGNED ,
			`MAC` VARCHAR( 90 ) NOT NULL,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 50 ) NOT NULL ,
			`enabled` smallint(1) NOT NULL,
			`sessionkey` VARCHAR( 90 ),
			`autocreate` smallint(1) NOT NULL DEFAULT 0,
			`autocreate_confirmed` smallint(1) NOT NULL DEFAULT 0,
			`autocreate_maxttl` smallint(2) NOT NULL DEFAULT 0,
			`activedirectory` smallint(1) NOT NULL DEFAULT 0,
			`activedirectorycnx` VARCHAR( 90 ) ,
			PRIMARY KEY ( `uid` ) ,
			INDEX ( `ttl` , `sessiontime`,`enabled`),
			KEY `MAC` (`MAC`),
			KEY `activedirectory` (`activedirectory`),
			KEY `autocreate_confirmed` (`autocreate_confirmed`),
			KEY `autocreate_maxttl` (`autocreate_maxttl`),
			KEY `activedirectorycnx` (`activedirectorycnx`),
			KEY `sessionkey` (`sessionkey`),
			KEY `hostname` (`hostname`),
			KEY `creationtime` (`creationtime`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);
	if(!$this->ok){echo "CREATE TABLE hotspot_members Failed $this->mysql_error\n";}
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members_meta` ( `uid` VARCHAR( 128 ) NOT NULL , `creationtime` INT UNSIGNED NOT NULL, PRIMARY KEY ( `uid` ) , INDEX ( `creationtime`) )  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);
	if(!$this->ok){echo "CREATE TABLE hotspot_members hotspot_members_meta $this->mysql_error\n";}
	
	


	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_activedirectory` (
				`zmd5` VARCHAR( 90 ),
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`ruleid` BIGINT(10) NOT NULL ,
				`enabled` smallint(1) NOT NULL ,
				`groups` TEXT NULL ,
				`ttl` INT UNSIGNED,
				 PRIMARY KEY ( `zmd5` ) ,
				 KEY `hostname` (`hostname`),
				 KEY `enabled` (`enabled`),
				 KEY `ruleid` (`ruleid`),
				 KEY `ttl` (`ttl`)
		
				)  ENGINE = MYISAM;";
	$this->QUERY_SQL($sql,$this->database);
	if(!$this->ok){echo "CREATE TABLE hotspot_activedirectory Failed $this->mysql_error\n";}
		
	
	if(!$this->FIELD_EXISTS("hotspot_activedirectory", "ruleid")){$this->QUERY_SQL("ALTER TABLE `hotspot_activedirectory` ADD `ruleid` BIGINT(10) NOT NULL DEFAULT 0 ,ADD INDEX ( `ruleid` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "ruleid")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `ruleid` BIGINT(10) NOT NULL DEFAULT 0 ,ADD INDEX ( `ruleid` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "token")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `token` VARCHAR( 128 ) NOT NULL DEFAULT 0,ADD INDEX ( `token` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "username")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `username` VARCHAR( 128 ) NOT NULL DEFAULT 0,ADD INDEX ( `username` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "activedirectory")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `activedirectory` smallint( 1 ) NOT NULL DEFAULT 0,ADD INDEX ( `activedirectory` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "activedirectorycnx")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `activedirectorycnx` VARCHAR( 90 ) ,ADD INDEX ( `activedirectorycnx` )");}		
	if(!$this->FIELD_EXISTS("hotspot_members", "sessionkey")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `sessionkey` VARCHAR( 90 ) ,ADD INDEX ( `sessionkey` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "creationtime")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `creationtime` VARCHAR( 90 ) ,ADD INDEX ( `creationtime` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "autocreate_confirmed")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `autocreate_confirmed` smallint(1) NOT NULL DEFAULT 0 ,ADD INDEX ( `autocreate_confirmed` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "autocreate_maxttl")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `autocreate_maxttl` smallint(2) NOT NULL DEFAULT 0 ,ADD INDEX ( `autocreate_maxttl` )");}
	if(!$this->FIELD_EXISTS("hotspot_members", "autocreate")){$this->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `autocreate` smallint(1) NOT NULL DEFAULT 0 ,ADD INDEX ( `autocreate` )");}
		
	
	if(!$this->FIELD_EXISTS("hotspot_sessions", "nextcheck")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `nextcheck` BIGINT UNSIGNED ,ADD INDEX ( `nextcheck` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "autocreate")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `autocreate` smallint(1) NOT NULL DEFAULT 0 ,ADD INDEX ( `autocreate` )");}
	if(!$this->FIELD_EXISTS("hotspot_sessions", "ruleid")){$this->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `ruleid` BIGINT(10) NOT NULL DEFAULT 0 ,ADD INDEX ( `ruleid` )");}
	
	
	
}	


public function Check_tables_statsapps(){
	
	$sql="CREATE TABLE IF NOT EXISTS `StatsApplianceReceiver` (
	`uuid` VARCHAR(90) NOT NULL,
	`created` DATETIME NOT NULL,
	`updated` DATETIME NOT NULL,
	`ipaddr` VARCHAR(90) NOT NULL,
	`hostname` VARCHAR(128) NOT NULL,
	 PRIMARY KEY (`uuid`),
	 KEY `ipaddr` (`ipaddr`),
	 KEY `created` (`created`),
	 KEY `updated` (`updated`),
	 KEY `hostname` (`hostname`)
	) ENGINE=MYISAM;";
	
	$this->QUERY_SQL($sql);

	
}


public function check_stats_appliance_hourly_uploaded($time=0){
	if($time==0){$time=date("YmdH");}
	
	$sql="CREATE TABLE IF NOT EXISTS `{$time}_statsuapp` (
	`zDate` DATETIME NOT NULL,
	`filename` VARCHAR(90) NOT NULL,
	`filesize` INT UNSIGNED,
	`uuid` VARCHAR(90) NOT NULL,
	 KEY `uuid` (`uuid`),
	 KEY `zDate` (`zDate`),
	 KEY `filesize` (`filesize`)
	) ENGINE=MYISAM;";
	
	$this->QUERY_SQL($sql);
	if(!$this->ok){return;}
	return true;
	
}
public function check_stats_appliance_monthly_uploaded(){
	$time=date("Ym");

	$sql="CREATE TABLE IF NOT EXISTS `{$time}_Mstatsuapp` (
	`zDate` DATETIME NOT NULL,
	`hits` VARCHAR(90) NOT NULL,
	`size` INT UNSIGNED,
	`uuid` VARCHAR(90) NOT NULL,
	KEY `uuid` (`uuid`),
	KEY `zDate` (`zDate`),
	KEY `hits` (`size`)
	) ENGINE=MYISAM;";

	$this->QUERY_SQL($sql);


}	
public function CheckTables($table=null,$force=false){
		if(isset($GLOBALS[__CLASS__]["FAILED"])){
			writelogs("Global connection is failed, aborting",__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;return false;}
		$md5=md5("CheckTables($table)");
		if(isset($GLOBALS[$md5])){return;}
		$GLOBALS[$md5]=true;
		
	if($this->EnableRemoteStatisticsAppliance==1){return;}
	
	if(!$force){
		if($GLOBALS["AS_ROOT"]){
			if(!$GLOBALS["VERBOSE"]){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
				$unix=new unix();
				$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
				$XT_TIME=$this->file_time_min($timefile);
				if($XT_TIME<30){return true;}
				@unlink($timefile);
				@file_put_contents($timefile,time());
				$this->ToSyslog("Verify MySQL Tables ( as root) for database $this->database last check since {$XT_TIME}Mn");
			}
		}
		
		
		
		if(!$GLOBALS["AS_ROOT"]){
			if(!$GLOBALS["VERBOSE"]){
				$timefile="/usr/share/artica-postfix/ressources/logs/web/".basename(__FILE__).".time";
				$XT_TIME=$this->file_time_min($timefile);
				if($XT_TIME<30){return true;}
				$this->ToSyslog("Verify MySQL Tables ( as {$_SESSION["uid"]} ) for database $this->database last check since {$XT_TIME}Mn");
				@unlink($timefile);
				@file_put_contents($timefile,time());
			}
		}
	}
	
	
	if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
	if(isset($GLOBALS[__CLASS__]["FAILED"])){return;}
	
	$this->CreateWeekTable();
	$this->create_webfilters_categories_caches();
	
	
	if($this->TABLE_EXISTS("category_teans")){
		if(!$this->TABLE_EXISTS("category_teens")){
			$this->QUERY_SQL("RENAME TABLE `category_teans` TO `category_teens`");
		}
	}
	
	

	if($this->TABLE_EXISTS("webfilters_schedules",$this->database)){
		if(!$this->FIELD_EXISTS("webfilters_schedules","Params",$this->database)){
			$this->QUERY_SQL("ALTER TABLE `webfilters_schedules` ADD `Params` TEXT NOT NULL");
		}
	}

		
		
		 

		
		
		if($table<>null){
			if(!$this->FIELD_EXISTS($table,"uid",$this->database)){
				$sql="ALTER TABLE `$table` ADD `uid` VARCHAR( 128 ) NOT NULL,ADD INDEX ( uid )";
				if(!$this->ok){
					writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->mysql_error=$this->mysql_error."\n$sql";
				}			
				$this->QUERY_SQL($sql,$this->database);
			}
		}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `computers_time` (
				`MAC` VARCHAR( 90 ) NOT NULL,
				`ipaddr` VARCHAR( 90 ) NOT NULL,
				`enabled` SMALLINT(1) NOT NULL DEFAULT 0,
				`MONDAY_AM` VARCHAR( 90 ) NOT NULL,
				`MONDAY_PM` VARCHAR( 90 ) NOT NULL,
				
				`TUESDAY_AM` VARCHAR( 90 ) NOT NULL,
				`TUESDAY_PM` VARCHAR( 90 ) NOT NULL,

				`WEDNESDAY_AM` VARCHAR( 90 ) NOT NULL,
				`WEDNESDAY_PM` VARCHAR( 90 ) NOT NULL,				

				`THURSDAY_AM` VARCHAR( 90 ) NOT NULL,
				`THURSDAY_PM` VARCHAR( 90 ) NOT NULL,					

				`FRIDAY_AM` VARCHAR( 90 ) NOT NULL,
				`FRIDAY_PM` VARCHAR( 90 ) NOT NULL,	

				`SATURDAY_AM` VARCHAR( 90 ) NOT NULL,
				`SATURDAY_PM` VARCHAR( 90 ) NOT NULL,					
				
				
				`SUNDAY_AM` VARCHAR( 90 ) NOT NULL,
				`SUNDAY_PM` VARCHAR( 90 ) NOT NULL,				
				
				 UNIQUE KEY `MAC` (`MAC`),
				 KEY `enabled` (`enabled`)
				 
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);
		

		
		$sql="CREATE TABLE IF NOT EXISTS `dnsmasq_records` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`ipaddrton` BIGINT UNSIGNED,
				`ipaddr` VARCHAR( 90 ) NOT NULL,
				`hostname` VARCHAR(128),
				 KEY `ipaddr` (`ipaddr`),
				 KEY `ipaddrton` (`ipaddrton`),
				 KEY `hostname` (`hostname`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);	
		

		$sql="CREATE TABLE IF NOT EXISTS `sarg_aliases` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR( 128 ) NOT NULL,
				`group` VARCHAR( 90 ) NOT NULL,
				`replace` VARCHAR(90),
				 UNIQUE KEY `pattern` (`pattern`),
				 KEY `group` (`group`),
				 KEY `replace` (`replace`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);	

	
		
		$sql="CREATE TABLE IF NOT EXISTS `ufdb_page_rules` (
			`zmd5` varchar(90) NOT NULL,
			`category` varchar(90) NOT NULL,
			`deny` smallint(1) NOT NULL,
			`allow` smallint(1) NOT NULL,
			`adgroup` varchar(255) NOT NULL,
			`noauth` smallint(1) NOT NULL,
			`maxtime` smallint(3) NOT NULL,
			`infinite` smallint(1) NOT NULL,
			`addTocat` varchar(255) NOT NULL,
			`username` varchar(255) NOT NULL,
			`webruleid` INT(10) NOT NULL,
			PRIMARY KEY (`zmd5`),
			KEY `category` (`category`),
			KEY `deny` (`deny`),
			KEY `allow` (`allow`),
			KEY `webruleid` (`webruleid`),
			KEY `infinite` (`infinite`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		if(!$this->FIELD_EXISTS("ufdb_page_rules", "maxtime")){
			$this->QUERY_SQL("ALTER TABLE `ufdb_page_rules` ADD `maxtime` smallint(3) NOT NULL DEFAULT 0, ADD INDEX ( `maxtime` )");
		}
		if(!$this->FIELD_EXISTS("ufdb_page_rules", "ticket")){
			$this->QUERY_SQL("ALTER TABLE `ufdb_page_rules` ADD `ticket` smallint(1) NOT NULL DEFAULT 0, ADD INDEX ( `ticket` )");
		}		
		if(!$this->FIELD_EXISTS("ufdb_page_rules", "webruleid")){
			$this->QUERY_SQL("ALTER TABLE `ufdb_page_rules` ADD `webruleid` INT(10) NOT NULL NOT NULL DEFAULT 0, ADD INDEX ( `webruleid` )");
		}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `UsersAgentsDB` (
				`explain` VARCHAR(255),
				`editor` VARCHAR( 90 ) NOT NULL,
				`pattern` VARCHAR(60) PRIMARY KEY,
				`bypass` smallint(1) NOT NULL DEFAULT 1,
				`deny` smallint(1) NOT NULL DEFAULT 0,
				`enabled` smallint(1) NOT NULL DEFAULT 1,
				 KEY `bypass` (`bypass`),
				 KEY `enabled` (`enabled`),
				 KEY `editor` (`editor`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `StoreID` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`zmd5` VARCHAR( 90 ) NOT NULL,
				`pattern` TEXT,
				`dedup` VARCHAR(255),
				`zOrder` smallint(2) NOT NULL DEFAULT 1,
				`enabled` smallint(1) NOT NULL DEFAULT 1,
				 UNIQUE KEY `zmd5` (`zmd5`),
				 KEY `enabled` (`enabled`),
				 KEY `dedup` (`dedup`),
				 KEY `zOrder` (`zOrder`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);
		if($this->COUNT_ROWS("StoreID")==0){
			include_once(dirname(__FILE__)."/class.storeid.defaults.inc");
			$this->QUERY_SQL(FillStoreIDDefaults());
		}		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `dnsmasq_blacklist` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`hostname` VARCHAR( 256 ) NOT NULL,
				 KEY `hostname` (`hostname`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);		
		
		$sql="CREATE TABLE IF NOT EXISTS `dnsmasq_cname` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`recordid` BIGINT UNSIGNED,
				`hostname` VARCHAR(128),
				 KEY `recordid` (`recordid`),
				 KEY `hostname` (`hostname`)
				 )  ENGINE = MYISAM;
			";
		$this->QUERY_SQL($sql,$this->database);		
		
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_blklnk` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				 zmd5 VARCHAR(90) NOT NULL,
				 webfilter_blkid INT(10) NOT NULL,
				 webfilter_ruleid  INT(10) NOT NULL,
				 blacklist smallint(1) NOT NULL DEFAULT '1',
				 UNIQUE KEY `zmd5` (`zmd5`),
				 KEY `webfilter_blkid` (`webfilter_blkid`),
				 KEY `webfilter_ruleid` (`webfilter_ruleid`),
				 KEY `blacklist` (`blacklist`)
				)  ENGINE = MYISAM;
			";
			$this->QUERY_SQL($sql,$this->database);
			
			
			$sql="CREATE TABLE IF NOT EXISTS `cicap_profiles` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				 `rulename` VARCHAR(90) NOT NULL,
				 `blacklist` smallint(2) NOT NULL,
				 `whitelist` smallint(2) NOT NULL,
				 `enabled` smallint(1) NOT NULL,
				 KEY `rulename` (`rulename`),
				 KEY `blacklist` (`blacklist`),
				 KEY `whitelist` (`whitelist`),
				 KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM AUTO_INCREMENT = 5;
			";
			$this->QUERY_SQL($sql,$this->database);			
			
			
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_blkgp` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				 groupname VARCHAR(255) NOT NULL,
				 enabled smallint(1) NOT NULL DEFAULT '1',
				 KEY `groupname` (`groupname`),
				 KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM;
			";			
			$this->QUERY_SQL($sql,$this->database);
			
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_blkcnt` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`webfilter_blkid` INT( 10 ) NOT NULL,
				 category VARCHAR(255) NOT NULL,
				 KEY `category` (`category`),
				KEY `webfilter_blkid` (`webfilter_blkid`)
				)  ENGINE = MYISAM;
			";			
			$this->QUERY_SQL($sql,$this->database);
			
			
			
			$sql="CREATE TABLE IF NOT EXISTS `squid_balancers` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`ipsrc` VARCHAR( 255 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			INDEX ( `enabled`,`ipsrc` )) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			
		
			
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_quotas` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			enabled smallint(1) NOT NULL,
			groupname VARCHAR(90) NOT NULL,
			quotasize INT(10) NOT NULL,
			quotaPeriod SMALLINT(1) NOT NULL,				
			UseExternalWebPage smallint(1) NOT NULL DEFAULT '0' ,
			ExternalWebPage VARCHAR(255) NOT NULL,
			zOrder SMALLINT(2) NOT NULL,
			AllSystems smallint(1) NOT NULL,
			KEY `groupname` (`groupname`),
			KEY `enabled` (`enabled`),
			KEY `zOrder` (`zOrder`),
			KEY `AllSystems` (`AllSystems`))  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			
			$sql="CREATE TABLE IF NOT EXISTS `acls_ntlm` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			enabled smallint(1) NOT NULL,
			item VARCHAR(256) NOT NULL,
			Type VARCHAR(20) NOT NULL,
			KEY `item` (`Type`),
			KEY `enabled` (`enabled`),
			KEY `Type` (`Type`))  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);			
			
			

		if(!$this->TABLE_EXISTS('webfilter_rules',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_rules` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				  	groupmode smallint(1) NOT NULL,
				  	enabled smallint(1) NOT NULL,
					groupname VARCHAR(90) NOT NULL,
					BypassSecretKey VARCHAR(90) NOT NULL,
					endofrule VARCHAR(50) NOT NULL,
					blockdownloads smallint(1) NOT NULL DEFAULT '0' ,
					naughtynesslimit INT(2) NOT NULL DEFAULT '50' ,
					searchtermlimit INT(2) NOT NULL DEFAULT '30' ,
					bypass smallint(1) NOT NULL DEFAULT '0' ,
					deepurlanalysis  smallint(1) NOT NULL DEFAULT '0' ,
					UseExternalWebPage smallint(1) NOT NULL DEFAULT '0' ,
					ExternalWebPage VARCHAR(255) NOT NULL,
					freeweb VARCHAR(255) NOT NULL,
					sslcertcheck smallint(1) NOT NULL DEFAULT '0' ,
					sslmitm smallint(1) NOT NULL DEFAULT '0',
					GoogleSafeSearch smallint(1) NOT NULL DEFAULT '0',
					TimeSpace TEXT NOT NULL,
					TemplateError TEXT NOT NULL,
					TemplateColor1 VARCHAR(90),
					TemplateColor2 VARCHAR(90),
					RewriteRules TEXT NOT NULL,
					zOrder SMALLINT(2) NOT NULL,
					AllSystems smallint(1) NOT NULL,
					UseSecurity smallint(1) NOT NULL,
				  KEY `groupname` (`groupname`),
				  KEY `enabled` (`enabled`),
				  KEY `UseSecurity` (`UseSecurity`),
				  KEY `zOrder` (`zOrder`),
				  KEY `AllSystems` (`AllSystems`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->FIELD_EXISTS("webfilter_rules", "endofrule")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `endofrule` VARCHAR(50)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "BypassSecretKey")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `BypassSecretKey` VARCHAR(90)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "embeddedurlweight")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `embeddedurlweight` smallint(1)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TimeSpace")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TimeSpace` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "RewriteRules")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `RewriteRules` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TemplateError")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TemplateError` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "GoogleSafeSearch")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `GoogleSafeSearch` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "UseExternalWebPage")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `UseExternalWebPage` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "ExternalWebPage")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `ExternalWebPage` VARCHAR(255) NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "freeweb")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `freeweb` VARCHAR(255) NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "zOrder")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `zOrder` SMALLINT(2) NOT NULL,ADD INDEX ( `zOrder` )");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "AllSystems")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `AllSystems` smallint(1),ADD INDEX ( `AllSystems` )");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "UseSecurity")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `UseSecurity` smallint(1),ADD INDEX ( `UseSecurity` )");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TemplateColor1")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TemplateColor1` VARCHAR(90)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TemplateColor2")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TemplateColor2` VARCHAR(90)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "http_code")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `http_code` smallint(3)");}
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_catprivs` (
			`zmd5` VARCHAR(90) NOT NULL,
			`categorykey` VARCHAR(128) NOT NULL,
			`groupdata` VARCHAR(255) NOT NULL,
			`allowrecompile` smallint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY `zmd5` (`zmd5`)
			) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);

		
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_catprivslogs` (
			`zDate` datetime NOT NULL,
			`events` VARCHAR(128) NOT NULL,
			`uid` VARCHAR(128) NOT NULL,
			 KEY `uid` (`uid`),
			 KEY `zDate` (`zDate`),
			 KEY `events` (`events`)
			) ENGINE = MYISAM;";		
		$this->QUERY_SQL($sql,$this->database);
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_certs` (
			`zmd5` VARCHAR(90) NOT NULL,
			`certname` VARCHAR(255) NOT NULL,
			`certdata` TEXT NOT NULL,
			PRIMARY KEY `zmd5` (`zmd5`),
			 KEY `certname` (`certname`)
			 ) ENGINE = MYISAM;
	    ";
		$this->QUERY_SQL($sql,$this->database);
		if($this->COUNT_ROWS("webfilter_certs")==0){$this->fill_webfilter_certs();}
		

		
		if(!$this->TABLE_EXISTS('webfilter_group',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_group` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					groupname VARCHAR(90) NOT NULL,
					localldap smallint(1) NOT NULL DEFAULT '0' ,
					enabled smallint(1) NOT NULL DEFAULT '1' ,
					gpid INT(10) NOT NULL DEFAULT '0' ,
					description VARCHAR(255) NOT NULL,
					`dn` VARCHAR(255) NOT NULL,
				  KEY `groupname` (`groupname`),
				  KEY `gpid` (`gpid`),
				  KEY `enabled` (`enabled`),
				  KEY `dn` (`dn`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		$sql="CREATE TABLE IF NOT EXISTS `notcategorized_events` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					tablename VARCHAR(128) NOT NULL,
					zDate datetime NOT NULL ,
					subject VARCHAR(255) NOT NULL,
					finished smallint(1) NOT NULL DEFAULT '0' ,
					description TEXT NOT NULL,
				  KEY `tablename` (`tablename`),
				  KEY `zDate` (`zDate`),
				  KEY `finished` (`finished`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		
		$sql="CREATE TABLE IF NOT EXISTS `proxy_ports_wbl` (
		`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`portid` INT NOT NULL,
		`pattern` VARCHAR(128) NOT NULL,
		`include`  smallint(1) NOT NULL DEFAULT '0' ,
		KEY `pattern` (`pattern`),
		KEY `include` (`include`),
		KEY `portid` (`portid`)
		)  ENGINE = MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		

		
		$sql="CREATE TABLE IF NOT EXISTS `ss5_fw` (
		`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`zorder` INT( 5 ) NOT NULL,
		`mode` smallint(1) NOT NULL DEFAULT 0,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		`src_host` VARCHAR(128),
		`src_port` BIGINT UNSIGNED,
		`dst_host` VARCHAR(128),
		`dst_port` BIGINT UNSIGNED,
		`fixup` varchar(20) NULL,
		`group` VARCHAR(128),
		`bandwitdh` BIGINT UNSIGNED,
		`expdate` VARCHAR(40) NULL,
		KEY `zorder` (`zorder`),
		KEY `mode` (`mode`),
		KEY `enabled` (`enabled`),
		KEY `src_host` (`src_host`),
		KEY `dst_host` (`dst_host`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `ssl_rules` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`description` VARCHAR( 128 ) NOT NULL,
				`zDate` datetime NOT NULL,
				`crypt` smallint( 1 ) NOT NULL DEFAULT '0',
				`trust` smallint( 1 ) NOT NULL DEFAULT '0',
				`enabled` smallint(1) NULL,
				`ruletype` smallint(1) NULL,
				`frommeta` smallint(1) NULL,
				`zOrder` smallint(5) NULL,
				 KEY `zDate` (`zDate`),
				 KEY `crypt` (`crypt`),
				 KEY `trust` (`trust`),
				 KEY `enabled` (`enabled`),
			 	 KEY `zOrder` (`zOrder`),
				 KEY `frommeta` (`frommeta`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql);
		
		

		
		$sql="CREATE TABLE IF NOT EXISTS `proxy_ports` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`PortName` VARCHAR(128) NULL,
			`zMD5` VARCHAR(90) NOT NULL,
			`xnote` TEXT NULL ,
			`Params` TEXT NULL ,
			`ipaddr` VARCHAR(128) NOT NULL,
			`port` INT NOT NULL,
			`transparent` smallint(1) NOT NULL DEFAULT '0' ,
			`is_nat` smallint(1) NOT NULL DEFAULT '0' ,
			`enabled` smallint(1) NOT NULL DEFAULT '1' ,
			 KEY `ipaddr` (`ipaddr`),
			 KEY `enabled` (`enabled`),
			 KEY `port` (`port`)
			)  ENGINE = MYISAM AUTO_INCREMENT = 20;";
		$this->QUERY_SQL($sql,$this->database);		
		
		if(!$this->FIELD_EXISTS("proxy_ports", "transparent")){
			$this->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `transparent` smallint(1) NOT NULL DEFAULT '0'");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}
		if(!$this->FIELD_EXISTS("proxy_ports", "PortName")){
			$this->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `PortName` VARCHAR(128) NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}

		if(!$this->FIELD_EXISTS("proxy_ports", "TProxy")){
			$this->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `TProxy` smallint(1) NOT NULL DEFAULT '0'");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}	

		if(!$this->FIELD_EXISTS("proxy_ports", "is_nat")){
			$this->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `is_nat` smallint(1) NOT NULL DEFAULT '0'");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}		

		$sql="CREATE TABLE IF NOT EXISTS `itcharters` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`ChartContent` TEXT NULL,
			`ChartHeaders` TEXT NOT NULL,
			`PdfContent` LONGBLOB NULL,
			`TextIntro` TEXT NOT NULL,
			`TextButton` VARCHAR(128) NOT NULL,
			`PdfFileName` VARCHAR(128) NULL,
			`Params` TEXT NULL ,
			`title` VARCHAR(255) NOT NULL,
			`explain` TEXT NOT NULL,
			`enabled` smallint(1) NOT NULL DEFAULT '1' ,
			`enablepdf` smallint(1) NOT NULL DEFAULT '0' ,
			 KEY `title` (`title`),
			 KEY `enablepdf` (`enablepdf`),
			 KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		
		$sql="CREATE TABLE IF NOT EXISTS `influxIPClients` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			 `ipaddr` VARCHAR(128),
			`hostname` VARCHAR(128),
			`isServ` smallint(1) NOT NULL DEFAULT '1' ,
			UNIQUE KEY `ipaddr` (`ipaddr`),
			KEY `hostname` (`hostname`),  
			KEY `isServ` (`isServ`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `sarg_performed` (
		    `md5file` VARCHAR(128) PRIMARY KEY ,
			`filename` VARCHAR(128) ,
			`zDate` datetime
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		if(!$this->FIELD_EXISTS("itcharters", "PdfContent")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` LONGBLOB NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}

		if(!$this->FIELD_EXISTS("itcharters", "PdfContent")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` LONGBLOB NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}		
		
		if(!$this->FIELD_EXISTS("itcharters", "enablepdf")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `enablepdf` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `enablepdf` )");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}
		
		if(!$this->FIELD_EXISTS("itcharters", "PdfFileName")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileName` VARCHAR(128) NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}

		if(!$this->FIELD_EXISTS("itcharters", "PdfFileSize")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileSize` INT UNSIGNED NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}		
		
		if(!$this->FIELD_EXISTS("itcharters", "TextIntro")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `TextIntro` TEXT NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}	


		if(!$this->FIELD_EXISTS("itcharters", "TextButton")){
			$this->QUERY_SQL("ALTER TABLE `itcharters` ADD `TextButton` VARCHAR(128) NOT NULL");
			if(!$this->ok){echo $this->mysql_error."\n";}
		}		

		$sql="CREATE TABLE IF NOT EXISTS `itchartlog` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`chartid` INT NOT NULL,
			`uid` VARCHAR(128) NOT NULL ,
			`zDate` datetime NOT NULL,
			`ipaddr` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR(128) NOT NULL
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	

		
			
		$sql="CREATE TABLE IF NOT EXISTS `ident_networks` (
				network_item VARCHAR(128) NOT NULL PRIMARY KEY
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `transparent_networks` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR( 90 ) NOT NULL,
				`transparent` smallint(1) NOT NULL,
				`destination`  VARCHAR( 90 ) NOT NULL,
				`destination_port`  smallint( 3 ) NOT NULL,
				`ssl`  smallint(1) NOT NULL,
				`remote_proxy`  VARCHAR( 128 ) NOT NULL,
				`enabled` smallint(1) NOT NULL,
				`isnot` smallint(1) NOT NULL,
				`block` smallint(1) NOT NULL,
				`zOrder` smallint(5) NOT NULL,
				`eth` VARCHAR( 40 ),
				KEY `pattern` (`pattern`),
				KEY `transparent` (`transparent`),
				KEY `destination` (`destination`),
				KEY `remote_proxy` (`remote_proxy`),
				KEY `isnot` (`isnot`),
				KEY `block` (`block`),
				KEY `ssl` (`ssl`),
				KEY `zOrder` (`zOrder`),
				KEY `enabled` (`enabled`),
				KEY `eth` (`eth`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		if(!$this->FIELD_EXISTS("transparent_networks", "isnot")){$this->QUERY_SQL("ALTER TABLE `transparent_networks` ADD `isnot` smallint( 1 ) NOT NULL ,ADD INDEX ( `isnot` )");}
		if(!$this->FIELD_EXISTS("transparent_networks", "block")){$this->QUERY_SQL("ALTER TABLE `transparent_networks` ADD `block` smallint( 1 ) NOT NULL ,ADD INDEX ( `block` )");}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `transparent_networks_groups` (
		`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`inbound` smallint(1) NOT NULL,
		`zmd5` VARCHAR(90) NOT NULL,
		ruleid INT NOT NULL,
		gpid INT NOT NULL,
		`enabled` smallint(1) NOT NULL,
		UNIQUE KEY (`zmd5`),
		KEY `inbound` (`inbound`),
		KEY `ruleid` (`ruleid`),
		KEY `enabled` (`enabled`),
		KEY `gpid` (`gpid`)
		) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		$this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squid_reports` (
				   `ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`zmd5` VARCHAR( 90 ) NOT NULL,
					report_name VARCHAR(128) NOT NULL,
					report_days smallint(3) NOT NULL ,
					report_type smallint(1) NOT NULL,
					report_cat VARCHAR(128) NOT NULL,
					report_sum smallint(1) NOT NULL,
					report_not_categorized smallint(1) NOT NULL,
					report_progress smallint(2) NOT NULL DEFAULT 0,
					report_progress_text VARCHAR(128),
					report_build_time_start INT UNSIGNED,
					report_build_time_end INT UNSIGNED,
					report_time_start INT UNSIGNED,
					report_time_end INT UNSIGNED,
					report_schedenabled smallint(1) NOT NULL,
					report_bin longblob,
					report_csv longblob,
					report_log longblob,
					report_csv_ext VARCHAR(10),
					report_schedule VARCHAR(255) NOT NULL,
					recategorize smallint(1) NOT NULL,
					categorize smallint(1) NOT NULL,
					report_users TEXT,
					report_data TEXT,
					description TEXT NOT NULL,
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `report_name` (`report_name`),
				  KEY `report_days` (`report_days`),
				  KEY `report_schedenabled` (`report_schedenabled`),
				  KEY `report_type` (`report_type`)
				)  ENGINE = MYISAM;");
		if(!$this->ok){echo $this->mysql_error;}
		
		if(!$this->FIELD_EXISTS("squid_reports", "report_csv")){
		$this->QUERY_SQL("ALTER TABLE `squid_reports` ADD `report_csv` longblob");}
		
		if(!$this->FIELD_EXISTS("squid_reports", "report_log")){
			$this->QUERY_SQL("ALTER TABLE `squid_reports` ADD `report_log` longblob");}		
			
		if(!$this->FIELD_EXISTS("squid_reports", "report_csv_ext")){
		$this->QUERY_SQL("ALTER TABLE `squid_reports` ADD `report_csv_ext` VARCHAR(10)");}	
		
		$this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squid_reports_categories` (
				   `zmd5` VARCHAR( 90 ) NOT NULL,
					category VARCHAR(128) NOT NULL,
					report_id  INT( 10 ) NOT NULL,
					UNIQUE KEY `zmd5` (`zmd5`),
					KEY `report_id` (`report_id`),
					KEY `category` (`category`)
				)  ENGINE = MYISAM;");
		if(!$this->ok){echo $this->mysql_error;}
		
		$this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squid_reports_websites` (
				   `zmd5` VARCHAR( 90 ) NOT NULL,
					familysite VARCHAR(128) NOT NULL,
					report_id  INT( 10 ) NOT NULL,
					UNIQUE KEY `zmd5` (`zmd5`),
					KEY `report_id` (`report_id`),
					KEY `familysite` (`familysite`)
				)  ENGINE = MYISAM;");
		if(!$this->ok){echo $this->mysql_error;}		
				
		$this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squid_reports_members` (
				   `zmd5` VARCHAR( 90 ) NOT NULL,
					member_value VARCHAR(128) NOT NULL,
					member_field VARCHAR(40) NOT NULL,
					report_id  INT( 10 ) NOT NULL,
					UNIQUE KEY `zmd5` (`zmd5`),
					KEY `report_id` (`report_id`),
					KEY `member_value` (`member_value`)
				)  ENGINE = MYISAM;");
		if(!$this->ok){echo $this->mysql_error;}		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `hotspot_whitelist` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`hostname` VARCHAR( 256 ) NOT NULL,
				`ipaddr` VARCHAR( 90 ) NOT NULL,
				`port` smallint(2) NOT NULL,
				`ssl` smallint(1) NOT NULL,
				KEY `ipaddr` (`ipaddr`),
				KEY `ssl` (`ssl`),
				KEY `hostname` (`hostname`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webfiltering_meta_items` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`) 
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webfiltering_meta_urls` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`) 
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `hotspot_ident` (
				`ipaddr` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
				 `username` VARCHAR(128) NOT NULL,
				  `MAC` VARCHAR(128) NOT NULL,
				  zDate datetime NOT NULL,
				  KEY `username` (`username`),
				  UNIQUE KEY `MAC` (`MAC`),
				  KEY `zDate` (`zDate`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic` (
				  	`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
					`type` smallint(1) NOT NULL,
					`value` VARCHAR(255) NOT NULL,
					`enabled` smallint(1) NOT NULL DEFAULT '1' ,
					`gpid` INT(10) NOT NULL DEFAULT '0' ,
					`description` VARCHAR(255) NOT NULL,
					`who` VARCHAR(128) NOT NULL,
				  	KEY `type` (`type`),
				  	KEY `value` (`value`),
				  	KEY `enabled` (`enabled`),
					KEY `who` (`who`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynlogs` (
				  	`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
					`zDate` DATETIME NOT NULL,
					`gpid` INT(10) NOT NULL DEFAULT '0' ,
					`events` VARCHAR(255) NOT NULL,
					`who` VARCHAR(128) NOT NULL,
				  	KEY `zDate` (`zDate`),
				  	KEY `gpid` (`gpid`),
					KEY `who` (`who`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
							
			
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic_rights` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`gpid` INT UNSIGNED,
			`type` smallint(1) NOT NULL,
			`pattern` VARCHAR(255) NOT NULL,
			KEY `type` (`type`),
			KEY `pattern` (`pattern`) ) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
		
		if(!$this->FIELD_EXISTS("webfilter_group", "dn")){$this->QUERY_SQL("ALTER TABLE `webfilter_group` ADD `dn` VARCHAR( 255 ) NOT NULL ,ADD INDEX ( `dn` )");} 
		
		if(!$this->FIELD_EXISTS("webfilter_aclsdynamic", "maxtime")){
			$this->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `maxtime` INT UNSIGNED ,
					ADD INDEX ( `maxtime` )");
		}
		if(!$this->FIELD_EXISTS("webfilter_aclsdynamic", "duration")){
			$this->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `duration` INT UNSIGNED ,
					ADD INDEX ( `duration` )");
		}		
		
		
		
		
		if(!$this->TABLE_EXISTS('webfilters_dtimes_rules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_dtimes_rules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`TimeName` VARCHAR( 128 ) NOT NULL ,
			`TimeCode` TEXT NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`ruleid` INT UNSIGNED ,
			INDEX ( `TimeName` , `enabled` , `ruleid` )
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_databases_disk',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_databases_disk` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`filename` VARCHAR( 128 ) NOT NULL ,
			`size` BIGINT UNSIGNED ,
			`filtime` BIGINT UNSIGNED ,
			`category` VARCHAR( 50 ) NOT NULL ,
			INDEX ( `size` , `category` ,`filtime`),
			KEY `filename` (`filename`)
			)  ENGINE = MYISAM;";	
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->FIELD_EXISTS("webfilters_databases_disk", "filtime")){
			$this->QUERY_SQL("ALTER TABLE `webfilters_databases_disk` ADD `filtime`  INT UNSIGNED NOT NULL ,ADD INDEX ( `filtime` )");
		}
		
		if(!$this->FIELD_EXISTS("webfilters_quotas", "notify")){
			$this->QUERY_SQL("ALTER TABLE webfilters_quotas ADD `notify` smallint(1)  NOT NULL DEFAULT 0, ADD INDEX (`notify`)");
		}
		if(!$this->FIELD_EXISTS("webfilters_quotas", "notify_params")){
			$this->QUERY_SQL("ALTER TABLE webfilters_quotas ADD `notify_params` TEXT");
		}

		if(!$this->TABLE_EXISTS('webfilters_quotas',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_quotas` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`xtype` VARCHAR( 40 ) NOT NULL ,
			`value` VARCHAR(150) ,
			`maxquota` BIGINT UNSIGNED ,
			`notify` smallint(1)  NOT NULL DEFAULT 0,
			`notify_params` TEXT,
			`enabled` smallint(1),
			`duration` smallint(1),
			KEY `type` (`xtype`),
			KEY `value` (`value`),
			KEY `duration` (`duration`),
			KEY `maxquota` (`maxquota`)
			)  ENGINE = MYISAM;";	
			$this->QUERY_SQL($sql,$this->database);
		}

		
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_quotas_grp` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`zmd5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED,
			`gpid` INT UNSIGNED,
		    UNIQUE KEY `zmd5` (`zmd5`),
			KEY `ruleid` (`ruleid`),
			KEY `gpid` (`gpid`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
				
		
		
		if(!$this->TABLE_EXISTS('webtests',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webtests` (
			`sitename` VARCHAR( 250 ) NOT NULL PRIMARY KEY ,
			`category` VARCHAR( 128 ) NOT NULL ,
			`family` VARCHAR( 128 ) NOT NULL,
			`Country` VARCHAR( 50 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`ipaddr` VARCHAR(50) NOT NULL ,
			`SiteInfos` TEXT NOT NULL ,
			`checked` SMALLINT( 1 ) NOT NULL ,
			 KEY `sitename` (`sitename`),
			 KEY `category` (`category`),
			 KEY `Country` (`Country`),
			 KEY `checked` (`checked`),
			 KEY `family` (`family`),
			 KEY `ipaddr` (`ipaddr`),
			 KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}else{
			if(!$this->FIELD_EXISTS("webtests", "Country")){
				$this->QUERY_SQL("ALTER TABLE `webtests` ADD `Country`  VARCHAR( 50 ) NOT NULL ,ADD INDEX ( `Country` )");
			}
		}
		


			
		if(!$this->TABLE_EXISTS('webcacheperfs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webcacheperfs` (
			`zTimeInt` INT UNSIGNED NOT NULL PRIMARY KEY,
			`zHour` TINYINT( 4 ) NOT NULL,
			`zDay` TINYINT( 4 ) NOT NULL,
			`zMonth` TINYINT( 4 ) NOT NULL,
			`zYear` INT( 5 ) NOT NULL,
			`notcached` BIGINT UNSIGNED,
			`cached` BIGINT UNSIGNED,
			`pourc` TINYINT( 2 ) NOT NULL ,
			INDEX ( `zHour` , `zDay` , `zMonth`,`zYear`,`notcached`,`cached`,`pourc` )
			 
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}

		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_users` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`username` VARCHAR(128),
			`password` VARCHAR(128),
			 KEY `username`(`username`),
			 KEY `password`(`password`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `deny_websites` ( `items` VARCHAR( 255 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		
		$sql="CREATE TABLE IF NOT EXISTS `denycache_websites` ( `items` VARCHAR( 255 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);		

		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_items` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`userid` BIGINT(11),
			`service` VARCHAR(128) ,
			`rhost` VARCHAR(128),
			`username` VARCHAR(128),
			`domain` VARCHAR(128),
			`password` VARCHAR(128),
			`servicetype` VARCHAR(15),
			`serviceport` smallint(15),
			`alive` INT UNSIGNED NOT NULL,
			`is_rec` smallint(1),
			 KEY `username`(`username`),
			 KEY `password`(`password`),
			 KEY `service`(`service`),
			 KEY `rhost`(`rhost`),
			 KEY `userid`(`userid`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		if(!$this->FIELD_EXISTS("rdpproxy_items", "domain")){
			$this->QUERY_SQL("ALTER TABLE `rdpproxy_items` ADD `domain`  VARCHAR(128)");
		}
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`main_cache_rules` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`rulename` VARCHAR(128),
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 KEY `rulename`(`rulename`),
			 KEY `zorder`(`zorder`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	

		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`main_cache_dyn` (
			`familysite` VARCHAR(128) PRIMARY KEY,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			`level` smallint(2) NOT NULL DEFAULT 5,
			`zDate` DATETIME,
			 KEY `familysite`(`familysite`),
			 KEY `enabled`(`enabled`),
			 KEY `zDate`(`zDate`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	

		if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyImages")){
			$this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyImages`  smallint( 1 ) DEFAULT '0'");
		}
		if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyeDoc")){
			$this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyeDoc`  smallint( 1 ) DEFAULT '0'");
		}
		if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyFiles")){
			$this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyFiles`  smallint( 1 ) DEFAULT '0'");
		}				
		if(!$this->FIELD_EXISTS("main_cache_dyn", "OnlyMultimedia")){
			$this->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyMultimedia`  smallint( 1 ) DEFAULT '0'");
		}		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`ruleid` INT UNSIGNED NOT NULL,
			`rulename` VARCHAR(128),
			`min` smallint(50) NOT NULL,
			`max` smallint(50) NOT NULL,
			`perc` smallint(2) NOT NULL DEFAULT 20,
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`GroupType` smallint(1) NOT NULL DEFAULT 1,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 KEY `rulename`(`rulename`),
			 KEY `zorder`(`zorder`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	

		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules_items` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED NOT NULL,
			`item` VARCHAR(255),
			`zorder` smallint(2) NOT NULL DEFAULT 0,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 UNIQUE KEY `zMD5`(`zMD5`),
			 KEY `item`(`item`),
			 KEY `zorder`(`zorder`),
			 KEY `ruleid`(`ruleid`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`cache_rules_options` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED NOT NULL,
			`option` VARCHAR(40),
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 UNIQUE KEY `zMD5`(`zMD5`),
			 KEY `option`(`option`),
			 KEY `ruleid`(`ruleid`)
			 )  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webauth_rules` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`rulename` varchar(128) NOT NULL,
			`enabled` smallint(1) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `rulename` (`rulename`),
			 KEY `enabled` (`enabled`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		
		$sql="CREATE TABLE IF NOT EXISTS `webauth_rules_nets` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`pattern` VARCHAR(128) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`)	
		)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webauth_settings` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`MasterKey` VARCHAR(128) NOT NULL,
			`MasterValue` TEXT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`),
			 KEY `MasterKey` (`MasterKey`)
		)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql);		
		
		
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`UserAuthDays` (
			`zMD5` VARCHAR(90) PRIMARY KEY ,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`zDate` TIMESTAMP NOT NULL,
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`QuerySize` BIGINT UNSIGNED NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `hostname`(`hostname`),
			 KEY `zDate`(`zDate`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		
		
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`UserAuthDaysGrouped` (
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`QuerySize` BIGINT UNSIGNED NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			
			
			
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_rules` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`rulename` VARCHAR(128),
			`enabled` smallint(1) NOT NULL,
			`zorder` smallint(2) NOT NULL ,
			 `dntlhstname` smallint(1) DEFAULT '0' ,
			`isResolvable` smallint(1) DEFAULT '0' ,
			`FinishbyDirect` smallint(1) DEFAULT '0' ,
			 KEY `rulename`(`rulename`),
			 KEY `FinishbyDirect`(`FinishbyDirect`),
			 KEY `zorder`(`zorder`),
			 KEY `enabled`(`enabled`)
			 )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);	
			
			if(!$this->FIELD_EXISTS("wpad_rules", "zorder")){
				$this->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
			}
			
			if(!$this->FIELD_EXISTS("wpad_rules", "dntlhstname")){
				$this->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `dntlhstname`  smallint( 1 ) DEFAULT '0'");
			}
			if(!$this->FIELD_EXISTS("wpad_rules", "isResolvable")){
				$this->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `isResolvable`  smallint( 1 )  DEFAULT '0'");
			}
			if(!$this->FIELD_EXISTS("wpad_sources_link", "zorder")){
				$this->QUERY_SQL("ALTER TABLE `wpad_sources_link` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
			}
			if(!$this->FIELD_EXISTS("wpad_rules", "FinishbyDirect")){
				$this->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `FinishbyDirect`  smallint( 1 ) DEFAULT '0',ADD INDEX (`FinishbyDirect`)");
			}
			
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_sources_link` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zorder` smallint(2) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`,`zorder`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_white_link` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zorder` smallint(2) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`,`zorder`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);

			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_destination` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`proxyserver` VARCHAR(128) NOT NULL ,
			`proxyport` INT UNSIGNED ,
			`zorder` smallint(2) NOT NULL ,
			INDEX ( `aclid` ,`zorder`, `proxyserver`,`proxyport`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);	
			
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_destination_rules` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`rulename` VARCHAR(255) NOT NULL ,
			`aclid` BIGINT UNSIGNED ,
			`xtype` VARCHAR(40) NOT NULL, 
			`value` TEXT NOT NULL, 
			`destinations` TEXT NOT NULL, 
			`proxyserver` VARCHAR(128) NOT NULL ,
			`proxyport` INT UNSIGNED ,
			`enabled` smallint(1) NOT NULL,
			`zorder` smallint(2) NOT NULL ,
			 KEY `xtype` (`xtype`),
			 KEY `enabled` (`enabled`),
			 KEY `rulename` (`rulename`),
			INDEX ( `aclid` ,`zorder`, `proxyserver`,`proxyport`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);			
			
			if(!$this->FIELD_EXISTS("wpad_destination_rules", "destinations")){
				$this->QUERY_SQL("ALTER TABLE `wpad_destination_rules` ADD `destinations` TEXT  NOT NULL");
			}
			if(!$this->FIELD_EXISTS("wpad_destination_rules", "rulename")){
				$this->QUERY_SQL("ALTER TABLE `wpad_destination_rules` ADD `rulename` VARCHAR(255) NOT NULL, ADD INDEX (`rulename`)");
			}			
			

			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`wpad_events` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`zDate` DATETIME NOT NULL ,
			`ruleid` INT UNSIGNED ,
			`ipaddr` VARCHAR( 90 ) NOT NULL ,
			`hostname` VARCHAR( 255 ) NOT NULL ,
			`browser` VARCHAR( 255 ) NOT NULL ,
			`script` TEXT NOT NULL ,
			 KEY `ruleid` ( `ruleid`),
			 KEY `ipaddr` ( `ipaddr`),
			 KEY `hostname` ( `hostname`),
			 KEY `zDate` ( `zDate`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);	

			if(!$this->FIELD_EXISTS("wpad_events", "hostname")){
				$this->QUERY_SQL("ALTER TABLE `wpad_events` ADD `hostname`  VARCHAR( 255 )  NOT NULL ,ADD INDEX ( `hostname` )");
			}
			
			
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}

			
		if(!$this->TABLE_EXISTS('youtube_objects',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`youtube_objects` (
			`youtubeid` VARCHAR(60) NOT NULL PRIMARY KEY,
			`category` VARCHAR(90),
			`title` VARCHAR(255) NOT NULL,
			`content` TEXT NOT NULL,
			`uploaded` TIMESTAMP NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			`duration` INT UNSIGNED NOT NULL,
			`thumbnail` longblob NOT NULL,
			 KEY `category`(`category`),
			 KEY `uploaded`(`uploaded`),
			 KEY `title`(`title`),
			 KEY `duration`(`duration`),
			 KEY `hits`(`hits`)
			 )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		
		if(!$this->TABLE_EXISTS("youtube_dayz",$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`youtube_dayz` (
			`zDate` date NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`youtubeid` VARCHAR(60) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `zDate`(`zDate`),
			 KEY `hits`(`hits`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			
		}
		

		
		if(!$this->TABLE_EXISTS('RegexCatz',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`RegexCatz` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR( 90 ) NOT NULL,
			`RegexPattern` VARCHAR( 255 ) NOT NULL,
			`category` VARCHAR( 90 ) NOT NULL,
			`enabled` TINYINT( 1 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX (`enabled`,`zDate`),
			KEY `zMD5`(`zMD5`),
			KEY `category`(`category`)
			 
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		if(!$this->FIELD_EXISTS("webtests", "checked")){
			$this->QUERY_SQL("ALTER TABLE `webtests` ADD `checked`  SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `checked` )");
		}
		
		if(!$this->TABLE_EXISTS('websites_caches_params',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`websites_caches_params` (
			`sitename` VARCHAR(255) NOT NULL PRIMARY KEY,
			`MIN_AGE` INT( 10 ) NOT NULL,
			`PERCENT` INT( 10 ) NOT NULL,
			`MAX_AGE` INT( 10 ) NOT NULL,
			`options` TINYINT(1 ) NOT NULL,
			INDEX ( `MIN_AGE` , `PERCENT` , `MAX_AGE`,`options`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}			
		
		
		if(!$this->TABLE_EXISTS('webfilters_sqtimes_rules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqtimes_rules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`TimeName` VARCHAR( 128 ) NOT NULL ,
			`TimeCode` TEXT NOT NULL ,
			`TemplateError` TEXT NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`Allow` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TimeName` , `enabled`,`Allow`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->TABLE_EXISTS('webfilters_schedules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_schedules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`TimeText` VARCHAR( 128 ) NOT NULL ,
			`TimeDescription` VARCHAR( 128 ) NOT NULL ,
			`TaskType` SMALLINT( 1 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TaskType` , `TimeDescription`,`enabled`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}	
		
		
		

		
		if(!$this->TABLE_EXISTS('webfilters_bigcatzlogs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_bigcatzlogs` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`category_table` VARCHAR( 90 ) NOT NULL ,
			`category` VARCHAR( 60 ) NOT NULL ,
			`AddedItems` BIGINT UNSIGNED ,
			INDEX ( `AddedItems` , `category`,`zDate`),
			KEY `category_table`(`category_table`)
			
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		if(!$this->FIELD_EXISTS("webtests", "family")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `family` VARCHAR( 128 ) NOT NULL,ADD INDEX (`family`)");}
		if(!$this->FIELD_EXISTS("webtests", "zDate")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,ADD INDEX (`zDate`)");}
		if(!$this->FIELD_EXISTS("webtests", "ipaddr")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `ipaddr` VARCHAR(50) NOT NULL,ADD INDEX (`ipaddr`)");}
		if(!$this->FIELD_EXISTS("webtests", "SiteInfos")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `SiteInfos`  TEXT NOT NULL");}		
		if(!$this->FIELD_EXISTS("webfilters_sqtimes_rules", "Allow")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqtimes_rules` ADD `Allow`  SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `Allow` )");}
		if(!$this->FIELD_EXISTS("webfilters_sqtimes_rules", "TemplateError")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqtimes_rules` ADD `TemplateError`  TEXT  NOT NULL");}
		
		if(!$this->TABLE_EXISTS('webfilters_sqtimes_assoc',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqtimes_assoc` (
			`zMD5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`TimeRuleID` INT UNSIGNED ,
			`gpid` INT UNSIGNED ,
			INDEX ( `zMD5` , `TimeRuleID`,`gpid`)
			) ENGINE=MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		}		

			
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_sqgroups` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`GroupName` VARCHAR( 128 ) NOT NULL ,
			`GroupType` VARCHAR(50) NOT NULL ,
			`acltpl` VARCHAR(90) ,
			`tplreset` SMALLINT( 1 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`params` LONGTEXT NOT NULL,
			INDEX ( `GroupName` , `enabled`,`GroupType`),
			KEY `acltpl`(`acltpl`)
			)  ENGINE = MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`portals` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`PortalName` VARCHAR( 255 ) NOT NULL ,
			`ListenInterface` VARCHAR(20) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `PortalName` , `enabled`,`ListenInterface`)
			)  ENGINE = MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);		
		
		
		if(!$this->TABLE_EXISTS('webfilters_sqitems',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqitems` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`pattern` VARCHAR( 128 ) NOT NULL ,
			`other` TEXT,
			`gpid` INT UNSIGNED ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `pattern` , `enabled`,`gpid`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->FIELD_EXISTS("webfilters_sqitems", "other")){
			$this->QUERY_SQL("ALTER TABLE `webfilters_sqitems` ADD `other` TEXT");
		}
		
		
	
		
		
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_sqaclsports` (
			`aclport` SMALLINT( 5 ) PRIMARY KEY,
			`portname` VARCHAR( 128 ) NOT NULL,
			`interface`  VARCHAR( 128 ),
			`enabled`  SMALLINT( 1 ) NOT NULL,
			KEY `portname`(`portname`),
			KEY `aclport`(`aclport`),
			KEY `enabled`(`enabled`)
			) ENGINE = MYISAM;";
			
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
			
			if(!$this->FIELD_EXISTS("webfilters_sqaclsports", "interface")){
				$this->QUERY_SQL("ALTER TABLE `webfilters_sqaclsports` ADD `interface` VARCHAR(128),ADD INDEX(`interface`)");
			}
			if(!$this->FIELD_EXISTS("webfilters_sqaclsports", "enabled")){
				$this->QUERY_SQL("ALTER TABLE `webfilters_sqaclsports` ADD `enabled` smallint(1) NOT NULL,ADD INDEX(`enabled`)");
			}		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_sqacls` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`PortDirection` smallint( 2 ) NOT NULL DEFAULT '0',
			`aclname` VARCHAR( 128 ) NOT NULL ,
			`aclport` SMALLINT( 5 ) NOT NULL ,
			`acltpl`  VARCHAR( 90 ),
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`aclgroup` SMALLINT( 1 ) NOT NULL,
			`aclgpid`  INT UNSIGNED,			
			`xORDER` SMALLINT( 2 ),
			INDEX ( `aclname` , `enabled`,`xORDER`),
			KEY `acltpl`(`acltpl`),
			KEY `PortDirection`(`PortDirection`),
			KEY `aclport`(`aclport`)
			)  ENGINE = MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `meta_webfilters_acls` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`aclname` VARCHAR( 128 ) NOT NULL ,
			`acltpl`  VARCHAR( 90 ),
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`metagroup` INT UNSIGNED NOT NULL DEFAULT 0,
			`metauuid` VARCHAR(90) NULL,
			`aclgpid`  INT UNSIGNED,
			`httpaccess` VARCHAR( 60 ) NOT NULL ,
			`httpaccess_value`  SMALLINT( 1 ) NOT NULL,
			`httpaccess_data`  TEXT NOT NULL,				
			`xORDER` SMALLINT( 2 ),
			INDEX ( `aclname` , `enabled`,`xORDER`),
			KEY `acltpl`(`acltpl`),
			KEY `metagroup`(`metagroup`),
			KEY `httpaccess`(`httpaccess`),
			KEY `metauuid`(`metauuid`),
			KEY `httpaccess_value`(`httpaccess_value`)
			)  ENGINE = MYISAM;";		
		$this->QUERY_SQL($sql);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`meta_webfilters_sqacllinks` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zorder` INT( 10 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`),
			KEY `zorder`(`zorder`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`meta_webfilters_groups` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`groupname` VARCHAR( 128 ) NOT NULL ,
			`grouptype` VARCHAR(50) NOT NULL ,
			`acltpl` VARCHAR(90) ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`params` LONGTEXT NOT NULL,
			INDEX ( `groupname` , `enabled`,`grouptype`),
			KEY `acltpl`(`acltpl`)
			)  ENGINE = MYISAM;";
		
		$this->QUERY_SQL($sql);
		

	
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_sqaclaccess` (
		`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
		`aclid` BIGINT UNSIGNED ,
		`httpaccess` VARCHAR( 60 ) NOT NULL ,
		`httpaccess_value`  SMALLINT( 1 ) NOT NULL,
		`httpaccess_data`  TEXT NOT NULL,
		INDEX ( `aclid` ,`httpaccess_value`),
		KEY `httpaccess`(`httpaccess`)
		)  ENGINE = MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`sslrules_sqacllinks` (
		`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
		`aclid` BIGINT UNSIGNED ,
		`negation` smallint(1) NOT NULL ,
		`gpid` INT UNSIGNED ,
		`zOrder` INT( 10 ) NOT NULL ,
		INDEX ( `aclid` , `gpid`,`negation`),
		KEY `zOrder`(`zOrder`)
		)  ENGINE = MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
				
		
		$sql="CREATE TABLE `squidlogs`.`webfilters_sqacllinks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`),
			KEY `zOrder`(`zOrder`)
			)  ENGINE = MYISAM;";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		
			
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`qos_sqacllinks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`),
			KEY `zOrder`(`zOrder`)
			)  ENGINE = MYISAM;";
			
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
						
		
	
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_usersasks` (
		`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
		`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
		`ipaddr` VARCHAR( 60 ) NOT NULL ,
		`sitename` VARCHAR( 255 ) NOT NULL ,
		`uid` VARCHAR( 128 ) NOT NULL ,
		INDEX ( `ipaddr`),
		KEY `sitename`(`sitename`),
		KEY `uid`(`uid`)
		)  ENGINE = MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		
		
		
		
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`firewallfilter_sqacllinks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`direction` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`),
			KEY `zOrder`(`zOrder`)
			)  ENGINE = MYISAM;";
		
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "Fatal !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		
		

		if(!$this->TABLE_EXISTS('squid_storelogs',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `squid_storelogs` (
				  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `filename` varchar(128) NOT NULL,
				  `fileext` varchar(4) NOT NULL,
				  `filesize` BIGINT UNSIGNED  NOT NULL,
				  `filecontent` longblob  NOT NULL,
				  `filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `Compressedsize` BIGINT UNSIGNED NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `filename` (`filename`),
				  KEY `filesize` (`filesize`),
				  KEY `fileext` (`fileext`),
				  KEY `filetime` (`filetime`),
				  KEY `Compressedsize` (`Compressedsize`)
				)  ENGINE = MYISAM;";	
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('webfilters_dbstats',$this->database)){
			
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_dbstats` (
				  `category` varchar(128) NOT NULL PRIMARY KEY,
				  `articasize` INT UNSIGNED NOT NULL,
				  `unitoulouse` INT UNSIGNED NOT NULL,
				  `persosize` BIGINT UNSIGNED  NOT NULL,
				  KEY `articasize` (`articasize`),
				  KEY `unitoulouse` (`unitoulouse`),
				  KEY `persosize` (`persosize`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);			
			
		}
		
		
		
			
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_nodes` (
		`MAC` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
		`uid` VARCHAR( 128 ) NOT NULL ,
		`hostname` VARCHAR( 128 ),
		`group` VARCHAR( 128 ),
		`nmap` smallint(1) NOT NULL ,
		`nmapreport` TEXT,
		 KEY `group` (`group`),
		 INDEX ( `uid`,`nmap`)
		)  ENGINE = MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		
		
		
		if(!$this->TABLE_EXISTS('webfilters_ipaddr',$this->database)){
			$sql="CREATE TABLE `squidlogs`.`webfilters_ipaddr` (
			`ipaddr` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			 `ip` int(10) unsigned NOT NULL default '0',
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ),
			`group` VARCHAR( 128 ),
			 INDEX ( `uid`,`hostname`),
			 KEY `group` (`group`),
			 KEY `ip` (`ip`)
			)  ENGINE = MYISAM;";
		
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('webfilters_backupeddbs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_backupeddbs` (
			`filepath` VARCHAR( 128 ) NOT NULL PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`size` BIGINT UNSIGNED ,
			INDEX ( `zDate`),
			KEY `size`(`size`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		if(!$this->FIELD_EXISTS("webfilters_nodes", "group")){$this->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `group` VARCHAR( 128 ),ADD INDEX ( `group` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_ipaddr", "group")){$this->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `group` VARCHAR( 128 ),ADD INDEX ( `group` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_nodes", "nmap")){$this->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `nmap` SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `nmap` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_nodes", "nmapreport")){$this->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `nmapreport` TEXT NOT NULL");}		
		if(!$this->FIELD_EXISTS("webfilters_usersasks", "uid")){$this->QUERY_SQL("ALTER TABLE `webfilters_usersasks` ADD `uid` VARCHAR( 128 ) NOT NULL ,ADD INDEX ( `uid` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_usersasks", "zDate")){$this->QUERY_SQL("ALTER TABLE `webfilters_usersasks` ADD `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");}
		if(!$this->FIELD_EXISTS("webfilters_sqacls", "acltpl")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `acltpl` VARCHAR( 90 ) ,ADD INDEX ( `acltpl` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_sqgroups", "acltpl")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `acltpl` VARCHAR( 90 ) ,ADD INDEX ( `acltpl` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_sqaclaccess", "httpaccess_data")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqaclaccess` ADD `httpaccess_data` VARCHAR( 255 ) NOT NULL");}

		
		
		if(!$this->TABLE_EXISTS('squidservers',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`squidservers` (
				`ipaddr` VARCHAR( 90 ) NOT NULL ,
				`port` INT( 2 ) NOT NULL DEFAULT 9000 ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`udpated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`created` TIMESTAMP NOT NULL ,
				PRIMARY KEY ( `ipaddr` ) ,
				INDEX ( `hostname` , `udpated` , `created` )
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("$this->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			}
		}

		if(!$this->FIELD_EXISTS("squidservers", "udpated")){
			$this->QUERY_SQL("ALTER TABLE `squidservers` ADD `udpated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,ADD INDEX ( `udpated` )");
		}

		
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ftpunivtlse1fr` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`filename` VARCHAR( 60) NOT NULL,
				`websitesnum` INT( 10 ) NOT NULL ,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				PRIMARY KEY ( `filename` ) ,
				INDEX ( `websitesnum` , `zmd5` , `zDate` )
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`univtlse1fr` (
				`category` VARCHAR( 90 ) NOT NULL ,
				`websitesnum` INT UNSIGNED ,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				PRIMARY KEY ( `category` ) ,
				INDEX ( `websitesnum` , `zDate` )
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);		

		if(!$this->TABLE_EXISTS('webfilter_members',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_members` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					pattern VARCHAR(90) NOT NULL,
					enabled smallint(1) NOT NULL DEFAULT '1' ,
					groupid INT(10) NOT NULL DEFAULT '0' ,
					membertype smallint(1) NOT NULL DEFAULT '0' ,
					  KEY `pattern` (`pattern`),
					  KEY `groupid` (`groupid`),
					  KEY `membertype` (`membertype`),
					  KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		
		if(!$this->TABLE_EXISTS('cacheconfig',$this->database)){	
			$sql="CREATE TABLE `cacheconfig` (
					`uuid` VARCHAR( 90 ) NOT NULL ,
					`hostname` VARCHAR( 128 ) NOT NULL ,
					`workers` INT( 2 ) NOT NULL ,
					`cachesDirectory` VARCHAR( 255 ) NOT NULL,
					`globalCachesize` INT UNSIGNED ,
					`alternateConfig` MEDIUMTEXT NOT NULL ,
					PRIMARY KEY ( `uuid` ) ,
					INDEX ( `hostname` , `workers` , `globalCachesize` )
					)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('cachestatus',$this->database)){	
			$sql="CREATE TABLE `cachestatus` (
					`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`uuid` VARCHAR( 90 ) NOT NULL ,
					`cachedir` VARCHAR( 255 ) NOT NULL ,
					`maxsize` BIGINT UNSIGNED ,
					`currentsize` BIGINT UNSIGNED ,
					`pourc` VARCHAR( 20 ) NOT NULL,
					INDEX ( `maxsize` , `currentsize` , `pourc` ),
					KEY `uuid` (`uuid`),
					KEY `cachedir` (`cachedir`)
					) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
	
		if(!$this->FIELD_EXISTS("webfilter_members", "membertype")){
			$this->QUERY_SQL("ALTER TABLE `webfilter_members` 
			ADD `membertype` smallint(1) NOT NULL ,ADD KEY `membertype` (`membertype`)");}		
		
		if(!$this->TABLE_EXISTS('webfilter_bannedexts',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedexts` (
				  `zmd5` varchar(90) NOT NULL,
				  `ext` varchar(10) NOT NULL,
				  `description` varchar(255) NOT NULL,
				  `enabled` smallint(1) NOT NULL DEFAULT '1',
				  `ruleid` int(2) NOT NULL,
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `ext` (`ext`,`enabled`,`ruleid`),
				  KEY `description` (`description`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('webfilter_bannedextsdoms',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedextsdoms` (
				  `zmd5` varchar(90) NOT NULL,
				  `ext` varchar(10) NOT NULL,
				  `description` varchar(255) NOT NULL,
				  `enabled` smallint(1) NOT NULL DEFAULT '1',
				  `ruleid` int(2) NOT NULL,
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `ext` (`ext`,`enabled`,`ruleid`),
				  KEY `description` (`description`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilter_avwhitedoms',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_avwhitedoms` (
				  `websitename` varchar(128) NOT NULL,
				  PRIMARY KEY (`websitename`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	

		$this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_notcached` (
			`TIME` DATETIME,
			`PROXYNAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			KEY `PROXYNAME` (`PROXYNAME`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
		);
		
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_blckports` (
			`port` BIGINT UNSIGNED ,
			`enabled` smallint(1) NOT NULL,
			PRIMARY KEY ( `port` ) ,
			INDEX ( `enabled`)
			
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){echo "CREATE TABLE hotspot_blckports Failed $this->mysql_error\n";}		

		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_whitemacs` (
			`MAC` VARCHAR(90) NOT NULL ,
			`enabled` smallint(1) NOT NULL,
			`username` VARCHAR(120) NULL,
			PRIMARY KEY ( `MAC` ) ,
			INDEX ( `enabled`)
		
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){echo "CREATE TABLE hotspot_blckports Failed $this->mysql_error\n";}		
		
		if(!$this->FIELD_EXISTS("hotspot_whitemacs", "username")){
			$this->QUERY_SQL("ALTER TABLE `hotspot_whitemacs` ADD `username` VARCHAR(128) NULL ,
					ADD INDEX ( `username` )");
		}
		
		
		
		$this->check_hotspot_session();

		$this->check_hotspot_tables();
		
		
	
		
		
		
		if(!$this->TABLE_EXISTS('webfilter_dnsbl',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_dnsbl` (
				  `dnsbl` varchar(128) NOT NULL,
				  `name` varchar(128) NOT NULL,
				  `uri` varchar(128) NOT NULL ,
				  `enabled` smallint(1) NOT NULL DEFAULT '1',
				  UNIQUE KEY `dnsbl` (`dnsbl`),
				  KEY `name` (`name`,`enabled`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
		$this->checkDNSBLTables();

		
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_updateev` (
			`zDate` TIMESTAMP NOT NULL ,
			`description` MEDIUMTEXT NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`category` VARCHAR( 50 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			INDEX ( `zDate` , `function` , `filename` , `line` , `category`,`TASKID` )
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');		
		
	


		if(!$this->TABLE_EXISTS('webfilters_blkwhlts',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_blkwhlts` (
			  `pattern` varchar(128) NOT NULL,
			  `description` text NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `PatternType` smallint(1) NOT NULL,
			  `blockType` smallint(1) NOT NULL,
			  `zmd5` VARCHAR( 90 ) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `pattern` (`pattern`),
			  KEY `enabled` (`enabled`),
			  KEY `blockType` (`blockType`),
			  FULLTEXT KEY `description` (`description`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	
		


		if(!$this->TABLE_EXISTS('UserAutDB',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `UserAutDB` (
			  `zmd5` varchar(90) NOT NULL,
			  `MAC` varchar(90) NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `hostname` varchar(128) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `MAC` (`MAC`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `uid` (`uid`),
			  KEY `hostname` (`hostname`),
			  KEY `UserAgent` (`UserAgent`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		
		if(!$this->TABLE_EXISTS('webfilter_blks',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_blks` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	modeblk smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `category` (`category`),
				  KEY `modeblk` (`modeblk`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `cicap_profiles_blks` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    mainid INT(3) NOT NULL,
				  	bltype smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `mainid` (`mainid`),
				  KEY `category` (`category`),
				  KEY `bltype` (`bltype`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		


		$sql="CREATE TABLE IF NOT EXISTS `cicap_rules` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    rulename VARCHAR(128) NOT NULL,
				  	GroupType smallint(1) NOT NULL,
				  	ProfileID INT(10) NOT NULL,
					enabled smallint(1) NOT NULL,
				  KEY `rulename` (`rulename`),
				  KEY `GroupType` (`GroupType`),
				  KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		if(!$this->TABLE_EXISTS('webfilter_termsg',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_termsg` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   groupname VARCHAR(128) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   KEY `groupname` (`groupname`),
				   KEY `enabled` (`enabled`)
				  
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilter_terms',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_terms` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   term VARCHAR(255) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   xregex smallint(1) NOT NULL,
				   UNIQUE KEY `term` (`term`),
				   KEY `enabled` (`enabled`),
				   KEY `xregex` (`xregex`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `dashboard_cached` (
			`TIME` DATETIME,
			`PROXYNAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			KEY `PROXYNAME` (`PROXYNAME`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		
	if(!$this->FIELD_EXISTS("webfilter_terms", "xregex")){$this->QUERY_SQL("ALTER TABLE `webfilter_terms` ADD `xregex` smallint( 1 ) NOT NULL ,ADD INDEX ( `xregex` )");}

		if(!$this->TABLE_EXISTS('webfilter_termsassoc',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_termsassoc` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   term_group BIGINT( 100) NOT NULL,
				   termid BIGINT( 100)NOT NULL,
				   KEY `term_group` (`term_group`),
				   KEY `termid` (`termid`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('webfilter_ufdbexpr',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexpr` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   rulename VARCHAR(128) NOT NULL,
				   ruleid  BIGINT( 100) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   KEY `rulename` (`rulename`),
				   KEY `enabled` (`enabled`),
				   KEY `ruleid` (`ruleid`)
				  
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	

			if(!$this->TABLE_EXISTS('webfilter_ufdbexprassoc',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexprassoc` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   groupid  BIGINT( 100) NOT NULL,
				   termsgid  BIGINT( 100) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   KEY `ID` (`ID`),
				   KEY `groupid` (`groupid`),
				   KEY `enabled` (`enabled`),
				   KEY `termsgid` (`termsgid`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		


		if(!$this->TABLE_EXISTS('webfilters_dtimes_blks',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_dtimes_blks` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	modeblk smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `category` (`category`),
				  KEY `modeblk` (`modeblk`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_quotas_blks` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	modeblk smallint(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `category` (`category`),
				  KEY `modeblk` (`modeblk`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		

		if(!$this->TABLE_EXISTS('webfilters_rewriterules',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriterules` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    rulename VARCHAR(128) NOT NULL,
				  	enabled smallint(1) NOT NULL DEFAULT 1,
				  	ItemsCount INT(10) NOT NULL,
				  KEY `rulename` (`rulename`),
				  KEY `enabled` (`enabled`),
				  KEY `ItemsCount` (`ItemsCount`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_updates',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_updates` (
				    tablename VARCHAR(128) NOT NULL PRIMARY KEY,
				  	zDate timestamp NOT NULL,
				  	updated smallint(1) NOT NULL,
				  KEY `zDate` (`zDate`),
				  KEY `updated` (`updated`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('webfilters_rewriteitems',$this->database)){	
					$sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriteitems` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    ruleid INT(2) NOT NULL,
				  	frompattern VARCHAR(128) NOT NULL,
				  	topattern VARCHAR(128) NOT NULL,
				  	enabled smallint(1) NOT NULL DEFAULT 1,
				  KEY `ruleid` (`ruleid`),
				  KEY `frompattern` (`frompattern`),
				  KEY `enabled` (`enabled`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	

			if(!$this->TABLE_EXISTS('webstats_backup',$this->database)){	
					$sql="CREATE TABLE IF NOT EXISTS `webstats_backup` (
				     `tablename` VARCHAR( 90 )  NOT NULL PRIMARY KEY,
				     `filepath` VARCHAR(255) NOT NULL,
				  	 `filesize` INT UNSIGNED NOT NULL,
					 KEY `filesize` (`filesize`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('webfilter_assoc_groups',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_assoc_groups` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	group_id INT(2) NOT NULL,
				  	zMD5 VARCHAR(90) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `group_id` (`group_id`),
				  UNIQUE KEY `zMD5` (`zMD5`)
				)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
		$sql="CREATE TABLE IF NOT EXISTS `webfilter_assoc_quota_groups` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	group_id INT(2) NOT NULL,
				  	zMD5 VARCHAR(90) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `group_id` (`group_id`),
				  UNIQUE KEY `zMD5` (`zMD5`)
				)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		
		
		
		if(!$this->FIELD_EXISTS("webfilter_assoc_groups", "zMD5")){$this->QUERY_SQL("ALTER TABLE `webfilter_assoc_groups` ADD `zMD5` VARCHAR( 90 ) NOT NULL ,ADD UNIQUE KEY `zMD5` (`zMD5`)");}		
		
		if(!$this->TABLE_EXISTS('instant_updates',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `instant_updates` (
				  `ID` BIGINT UNSIGNED PRIMARY KEY ,
				   `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  	CountItems INT UNSIGNED NOT NULL,
				  	DbCount INT UNSIGNED NOT NULL,
				    KEY `zDate` (`zDate`),
				  	KEY `CountItems` (`CountItems`),
				  	KEY `DbCount` (`DbCount`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('uris_malwares',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `uris_malwares` (
				  `uri` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('UserAgents',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `UserAgents` (
				  `pattern` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('uris_phishing',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `uris_phishing` (
				  `uri` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  ) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('tables_day',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `tables_day` (
			  `tablename` varchar(90) NOT NULL,
			  `zDate` date NOT NULL,
			  `size` bigint(255) NOT NULL,
			  `size_cached` bigint(255) NOT NULL,
			  `totalsize` bigint(255) NOT NULL,
			  `requests` bigint(255) NOT NULL,
			  `cache_perfs` int(2) NOT NULL,
			  `cached` smallint(1) NOT NULL DEFAULT '0',
			  `YouTubeHits` INT UNSIGNED NOT NULL,
			  `MembersCount` INT UNSIGNED NOT NULL,
			  `Hour` smallint(1) NOT NULL,
			  `members` smallint(1) NOT NULL DEFAULT '0',
			  `month_members` smallint(1) NOT NULL DEFAULT '0',
			  `month_flow` smallint(1) NOT NULL DEFAULT '0',
			  `blocks` smallint(1) NOT NULL,
			  `totalBlocked` bigint(255) NOT NULL,
			  `weekdone` smallint(1) NOT NULL DEFAULT '0',
			  `weekbdone` smallint(1) NOT NULL DEFAULT '0',
			  `monthdone` smallint(1) NOT NULL DEFAULT '0',
			  `yeardone` smallint(1) NOT NULL DEFAULT '0',
			  `year1` smallint(1) NOT NULL DEFAULT '0',
			  `month_query` smallint(1) NOT NULL DEFAULT '0',
			  `not_categorized` INT(50) NOT NULL DEFAULT '0',
			  `visited_day` smallint(1) NOT NULL DEFAULT '0',
			  `memberscentral` smallint(1) NOT NULL DEFAULT '0',
			  `compressed`  smallint(1) NOT NULL DEFAULT '0',
			  `backuped` smallint(1) NOT NULL DEFAULT '0',
			  `youtube_dayz` smallint(1) NOT NULL DEFAULT '0',
			  `youtube_week` smallint(1) NOT NULL DEFAULT '0',
			  `members_uid` smallint(1) NOT NULL DEFAULT '0',
			  `websites_uid` smallint(1) NOT NULL DEFAULT '0',
			  `blocked_uid` smallint(1) NOT NULL DEFAULT '0',
			  `youtube_uid` smallint(1) NOT NULL DEFAULT '0',
			  `members_mac` smallint(1) NOT NULL DEFAULT '0',
			  `members_macip` smallint(1) NOT NULL DEFAULT '0',
			  `familysites` smallint(1) NOT NULL DEFAULT '0',
			  `usersday` smallint(1) NOT NULL DEFAULT '0',
			  `WeekDay` SMALLINT( 2 ) NOT NULL ,
			  `WeekNum` SMALLINT( 2 ) NOT NULL, 
			  `SearchWordWeek` INT(50) NOT NULL,
			  `SearchWordTEMP` INT(50) NOT NULL,
			  `wwwvisited` smallint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`tablename`),
			  KEY `zDate` (`zDate`,`size`,`size_cached`,`cache_perfs`),
			  KEY `Hour` (`Hour`),
			  KEY `totalsize` (`totalsize`),
			  KEY `requests` (`requests`),
			  KEY `members` (`members`),
			  KEY `memberscentral` (`memberscentral`),
			  KEY `youtube_dayz` (`youtube_dayz`),
			  KEY `youtube_week` (`youtube_week`),
			  KEY `month_members` (`month_members`),
			  KEY `month_flow` (`month_flow`),
			  KEY `blocks` (`blocks`),
			  KEY `totalBlocked` (`totalBlocked`),
			  KEY `weekdone` (`weekdone`),
			  KEY `weekbdone` (`weekbdone`),
			  KEY `monthdone` (`monthdone`),
			  KEY `yeardone` (`yeardone`),
			  KEY `not_categorized` (`not_categorized`),
			  KEY `YouTubeHits` (`YouTubeHits`),
			  KEY `MembersCount` (`MembersCount`),
			  KEY `month_query` (`month_query`),
			  KEY `compressed` (`compressed`),
			  KEY `WeekDay` (`WeekDay`),
			  KEY `WeekNum` (`WeekNum`),
			  KEY `SearchWordWeek` (`SearchWordWeek`),
			  KEY `members_uid` (`members_uid`),
			  KEY `websites_uid` (`websites_uid`),
			  KEY `blocked_uid` (`blocked_uid`),
			  KEY `members_mac` (`members_mac`),
			  KEY `members_macip` (`members_macip`),
			  KEY `usersday` (`usersday`),
			  KEY `visited_day` (`visited_day`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->FIELD_EXISTS("tables_day", "blocks")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `blocks` INT( 1 ) NOT NULL ,ADD INDEX ( `blocks` )");}
		if(!$this->FIELD_EXISTS("tables_day", "totalBlocked")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `totalBlocked` BIGINT UNSIGNED ,ADD INDEX ( `totalBlocked` )");}
		if(!$this->FIELD_EXISTS("tables_day", "weekdone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `weekdone` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `weekdone` )");}
		if(!$this->FIELD_EXISTS("tables_day", "weekbdone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `weekbdone` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `weekbdone` )");}
		if(!$this->FIELD_EXISTS("tables_day", "month_query")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `month_query` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `month_query` ) ");}
		if(!$this->FIELD_EXISTS("tables_day", "not_categorized")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `not_categorized` INT( 50 ) NOT NULL DEFAULT '0',ADD INDEX ( `not_categorized` ) ");}
		if(!$this->FIELD_EXISTS("tables_day", "visited_day")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `visited_day` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `visited_day` )");}
		if(!$this->FIELD_EXISTS("tables_day", "memberscentral")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `memberscentral` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `memberscentral` )");}
		if(!$this->FIELD_EXISTS("tables_day", "backuped")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `backuped` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `backuped` )");}
		if(!$this->FIELD_EXISTS("tables_day", "month_flow")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `month_flow` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `month_flow` )");}
		if(!$this->FIELD_EXISTS("tables_day", "compressed")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `compressed` SMALLINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `compressed` )");}
		if(!$this->FIELD_EXISTS("tables_day", "YouTubeHits")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `YouTubeHits` INT UNSIGNED DEFAULT '0', ADD INDEX ( `YouTubeHits` )");}
		if(!$this->FIELD_EXISTS("tables_day", "MembersCount")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `MembersCount` INT UNSIGNED DEFAULT '0', ADD INDEX ( `MembersCount` )");}
		if(!$this->FIELD_EXISTS("tables_day", "youtube_dayz")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `youtube_dayz` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `youtube_dayz` )");}
		if(!$this->FIELD_EXISTS("tables_day", "youtube_week")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `youtube_week` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `youtube_week` )");}
		if(!$this->FIELD_EXISTS("tables_day", "wwwvisited")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `wwwvisited` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `wwwvisited` )");}
		if(!$this->FIELD_EXISTS("tables_day", "members_uid")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `members_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `members_uid` )");}
		if(!$this->FIELD_EXISTS("tables_day", "WeekDay")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `WeekDay` SMALLINT( 2 ) NOT NULL ,ADD `WeekNum` SMALLINT( 2 ) NOT NULL ,ADD INDEX ( `WeekDay` , `WeekNum` )");}
		if(!$this->FIELD_EXISTS("tables_day", "SearchWordWeek")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `SearchWordWeek` INT(50) NOT NULL ,ADD INDEX ( `SearchWordWeek` )");}
		if(!$this->FIELD_EXISTS("tables_day", "SearchWordTEMP")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `SearchWordTEMP` INT(50) NOT NULL ,ADD INDEX ( `SearchWordTEMP` )");}
		if(!$this->FIELD_EXISTS("tables_day", "websites_uid")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `websites_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `websites_uid` )");}
		if(!$this->FIELD_EXISTS("tables_day", "members_mac")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `members_mac` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `members_mac` )");}
		if(!$this->FIELD_EXISTS("tables_day", "members_macip")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `members_macip` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `members_macip` )");}
		if(!$this->FIELD_EXISTS("tables_day", "blocked_uid")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `blocked_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `blocked_uid` )");}
		if(!$this->FIELD_EXISTS("tables_day", "youtube_uid")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `youtube_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `youtube_uid` )");}
		if(!$this->FIELD_EXISTS("tables_day", "familysites")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `familysites` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `familysites` )");}
		if(!$this->FIELD_EXISTS("tables_day", "cached")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `cached` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `cached` )");}
		if(!$this->FIELD_EXISTS("tables_day", "monthdone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `monthdone` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `monthdone` )");}
		if(!$this->FIELD_EXISTS("tables_day", "yeardone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `yeardone` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `yeardone` )");}
		if(!$this->FIELD_EXISTS("tables_day", "year1")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `year1` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `year1` )");}
		if(!$this->FIELD_EXISTS("tables_day", "familyday")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `familyday` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `familyday` )");}
		if(!$this->FIELD_EXISTS("tables_day", "usersday")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `usersday` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `usersday` )");}
		
		
		
		
		if($this->FIELD_TYPE("tables_day", "totalBlocked",$this->database)=="bigint(100)"){
			$this->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size` `size` BIGINT( 255 ) NOT NULL');
			$this->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalBlocked` `totalBlocked` BIGINT( 255 ) NOT NULL');
			$this->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `requests` `requests` BIGINT( 255 ) NOT NULL');
			$this->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalsize` `totalsize` BIGINT( 255 ) NOT NULL');
			$this->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size_cached` `size_cached` BIGINT( 255 ) NOT NULL');
		}
		
		

		
		$sql="CREATE TABLE IF NOT EXISTS `squidtpls` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `template_name` varchar(128)  NOT NULL,
			  `template_body` LONGTEXT  NOT NULL,
			  `template_header` LONGTEXT  NOT NULL,
			  `template_title` varchar(255)  NOT NULL,
			  `template_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `template_link` smallint(1) NOT NULL,
			  `template_uri` varchar(255)  NOT NULL,
			  `lang` varchar(5)  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `template_name` (`template_name`,`lang`),
			  KEY `template_title` (`template_title`),
			  KEY `template_time` (`template_time`),
			  KEY `template_link` (`template_link`),
			  FULLTEXT KEY `template_body` (`template_body`)
			)  ENGINE = MYISAM;";		
		$this->QUERY_SQL($sql,$this->database);
		
		$sql="CREATE TABLE IF NOT EXISTS `members_uid` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `uid` varchar(128)  NOT NULL,
			  `zDate` date  NOT NULL,
			  `size` BIGINT UNSIGNED  NOT NULL,
			  `hits`  BIGINT UNSIGNED  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `uid` (`uid`),
			  KEY `zDate` (`zDate`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		$sql="CREATE TABLE IF NOT EXISTS `familysites` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `familysites` varchar(255)  NOT NULL,
			  `zDate` date  NOT NULL,
			  `size` BIGINT UNSIGNED  NOT NULL,
			  `hits`  BIGINT UNSIGNED  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `familysites` (`familysites`),
			  KEY `zDate` (`zDate`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `members_macip` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `MAC` varchar(128)  NOT NULL,
			  `ipaddr` varchar(128)  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `MAC` (`MAC`),
			  KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);		
		

		$sql="CREATE TABLE IF NOT EXISTS `members_mac` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `MAC` varchar(128)  NOT NULL,
			  `zDate` date  NOT NULL,
			  `size` BIGINT UNSIGNED  NOT NULL,
			  `hits`  BIGINT UNSIGNED  NOT NULL,
				
			  PRIMARY KEY (`zmd5`),
			  KEY `MAC` (`MAC`),
			  KEY `zDate` (`zDate`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		
		if(!$this->FIELD_EXISTS("squidtpls", "template_time")){		
			$sql="ALTER TABLE `squidtpls` ADD `template_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,ADD INDEX (`template_time`)"; 
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}			
		}			
		
		if(!$this->FIELD_EXISTS("squidtpls", "template_header")){$this->QUERY_SQL("ALTER TABLE `squidtpls` ADD `template_header` LONGTEXT  NOT NULL");}
		if(!$this->FIELD_EXISTS("squidtpls", "template_link")){$this->QUERY_SQL("ALTER TABLE `squidtpls` ADD `template_link` smallint(1)  NOT NULL,ADD INDEX (`template_link`)");}
		if(!$this->FIELD_EXISTS("squidtpls", "template_uri")){$this->QUERY_SQL("ALTER TABLE `squidtpls` ADD `template_uri` VARCHAR(255)  NOT NULL");}
		if(!$this->FIELD_EXISTS("squidtpls", "emptytpl")){$this->QUERY_SQL("ALTER TABLE `squidtpls` ADD `emptytpl` smallint(1)  NOT NULL");}
		
		if(!$this->TABLE_EXISTS('tables_hours',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `tables_hours` (
			  `tablename` varchar(90) NOT NULL,
			  `zDate` date NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `size_cached` INT UNSIGNED NOT NULL,
			  `totalsize` INT UNSIGNED ,
			  `requests` INT UNSIGNED ,
			  `cache_perfs` INT( 2 ) NOT NULL ,
			  `members` smallint(1) NOT NULL,
			  PRIMARY KEY (`tablename`),
			  KEY `zDate` (`zDate`,`size`,`size_cached`,`cache_perfs`),
			  KEY `totalsize` (`totalsize`),
			  KEY `requests` (`requests`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
		}
		
	

		if(!$this->TABLE_EXISTS('TrackMembers',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `TrackMembers` (
			  `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			  `report` VARCHAR(255) NOT NULL,
			  `userfield` VARCHAR(60) NOT NULL,
			  `duration` VARCHAR(128) NOT NULL,
			  `userdata` VARCHAR(128) NOT NULL,
			  `categories` TEXT NOT NULL ,
			  `sitename` TEXT NOT NULL ,
			  `scheduled` smallint(1) NOT NULL,
			  `csv` smallint(1) NOT NULL,
			  `csvContent` longblob NOT NULL,
			  KEY `report` (`report`),
			  KEY `scheduled` (`scheduled`),
			  KEY `csv` (`csv`)
			  
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
		}			
		if(!$this->FIELD_EXISTS("TrackMembers", "duration")){$this->QUERY_SQL("ALTER TABLE `TrackMembers` ADD `duration` VARCHAR( 128 ) NOT NULL");}
		if(!$this->FIELD_EXISTS("TrackMembers", "scheduled")){$this->QUERY_SQL("ALTER TABLE `TrackMembers` ADD `scheduled` smallint(1) NOT NULL,ADD KEY `scheduled` (`scheduled`)");}
		if(!$this->FIELD_EXISTS("TrackMembers", "csv")){$this->QUERY_SQL("ALTER TABLE `TrackMembers` ADD `csv` smallint(1) NOT NULL,ADD KEY `csv` (`csv`)");}
		if(!$this->FIELD_EXISTS("TrackMembers", "csvContent")){$this->QUERY_SQL("ALTER TABLE `TrackMembers` ADD `csvContent` longblob NOT NULL");}
		
	if(!$this->TABLE_EXISTS('updateblks_events',$this->database)){	
		$sql="CREATE TABLE `squidlogs`.`updateblks_events` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL ,
			`PID` INT( 5 ) NOT NULL ,
			`function` VARCHAR( 50 ) NOT NULL ,
			`category` VARCHAR( 50 ) NOT NULL ,
			`text` TEXT NOT NULL ,
			INDEX ( `zDate` , `PID` , `function` ),
			KEY `category` (`category`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->FIELD_EXISTS("updateblks_events", "category")){$this->QUERY_SQL("ALTER TABLE `updateblks_events` ADD `category` VARCHAR( 50 ) NOT NULL ,ADD KEY `category` (`category`)");} 		
		
		$sql="CREATE TABLE IF NOT EXISTS `visited_sites_days` (
			  `zmd5` varchar(90) NOT NULL,
			  `familysite` varchar(255) NOT NULL,
			  `size` BIGINT(255) UNSIGNED NOT NULL,
			  `hits` BIGINT(255) UNSIGNED NOT NULL,
			  `zDate` date NOT NULL ,			  
			  PRIMARY KEY (`zmd5`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `zDate` (`zDate`),
			  KEY `familysite` (`familysite`)
			)  ENGINE = MYISAM;";		
		$this->QUERY_SQL($sql,$this->database);
		
		$sql="CREATE TABLE IF NOT EXISTS `visited_sites_tot` (
			  `familysite` varchar(255) NOT NULL,
			  `size` BIGINT(255) UNSIGNED NOT NULL,
			  `hits` BIGINT(255) UNSIGNED NOT NULL,
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  PRIMARY KEY `familysite` (`familysite`)
			)  ENGINE = MYISAM;";
	
	
		$this->QUERY_SQL($sql,$this->database);		
		
		$sql="CREATE TABLE IF NOT EXISTS `cached_total` (
				  `size` bigint(255) unsigned NOT NULL,
				  `cached` smallint(1) unsigned NOT NULL,
				  `zDate` date NOT NULL,
				  `zmd5` varchar(40) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `size` (`size`),
				  KEY `cached` (`cached`)
				) ENGINE=MyISAM;";
		$this->QUERY_SQL($sql,$this->database);	

		
		$sql="CREATE TABLE IF NOT EXISTS `notcategorized` (
				  `sitename` VARCHAR(255) NOT NULL,
				  `familysite` VARCHAR(255) NOT NULL,
				  `domain` VARCHAR(5) NOT NULL,
				  `country` VARCHAR(60) NOT NULL,
				  `hits` bigint(255) unsigned NOT NULL,
				  `size` bigint(255) unsigned NOT NULL,
				 `seen` TEXT,
				  PRIMARY KEY (`sitename`),
				  KEY `size` (`size`),
				  KEY `hits` (`hits`),
				  KEY `familysite` (`familysite`),
				  KEY `domain` (`domain`),
				  KEY `country` (`country`)
				) ENGINE=MyISAM;";
		$this->QUERY_SQL($sql,$this->database);	
		if(!$this->FIELD_EXISTS("notcategorized", "seen")){$this->QUERY_SQL("ALTER TABLE `notcategorized` ADD `seen` TEXT");}	
		
		
		$sql="CREATE TABLE IF NOT EXISTS `visited_sites` (
			  `sitename` varchar(255) NOT NULL,
			  `Querysize` BIGINT UNSIGNED NOT NULL,
			  `category` varchar(255) NOT NULL,
			  `HitsNumber` INT UNSIGNED NOT NULL,
			  `country` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `whois` TEXT,
			  `probablect1` varchar(60) NOT NULL ,
			  `probablect2` varchar(60) NOT NULL ,
			  `probablect3` varchar(60) NOT NULL ,
			  `NotVisitedSended` smallint(1) NOT NULL,
			  `recatgorized` smallint(1) NOT NULL,
			  `thumbnail` smallint(1) NOT NULL,
			  PRIMARY KEY (`sitename`),
			  KEY `Querysize` (`Querysize`,`HitsNumber`,`country`),
			  KEY `familysite` (`familysite`),
			  KEY `probablect1` (`probablect1`),
			  KEY `probablect2` (`probablect2`),
			  KEY `probablect3` (`probablect3`),
			  KEY `category` (`category`),
			  KEY `recatgorized` (`recatgorized`),
			  KEY `thumbnail` (`thumbnail`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		
		if(!$this->INDEX_EXISTS("visited_sites", "category", $this->database)){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD KEY `category` (`category`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "whois")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `whois` TEXT");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect1")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect1` varchar(60) NOT NULL ,ADD KEY `probablect1` (`probablect1`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect2")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect2` varchar(60) NOT NULL ,ADD KEY `probablect2` (`probablect2`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect3")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect3` varchar(60) NOT NULL ,ADD KEY `probablect3` (`probablect3`)");}		
		if(!$this->FIELD_EXISTS("visited_sites", "NotVisitedSended")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `NotVisitedSended` smallint(1) NOT NULL ,ADD KEY `NotVisitedSended` (`NotVisitedSended`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "thumbnail")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `thumbnail` smallint(1) NOT NULL ,ADD KEY `thumbnail` (`thumbnail`)");}
		
		
		
		if(!$this->TABLE_EXISTS("visited_sites_catz",$this->database)){
			$sql="CREATE TABLE IF NOT EXISTS `visited_sites_catz` (
				 `zmd5` varchar(90) NOT NULL,
				 `category` varchar(60) NOT NULL,
				 `familysite` varchar(128) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `familysite` (`familysite`),
				  KEY `category` (`category`)
				  )  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->TABLE_EXISTS('stats_appliance_events',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `stats_appliance_events` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `hostname` varchar(60) NOT NULL,
			  `events` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  PRIMARY KEY (`ID`),
			  KEY `hostname` (`hostname`),
			  KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('categorize',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `categorize` (
			  `zmd5` varchar(90) NOT NULL,
			  `pattern` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `uuid` varchar(128) NOT NULL,
			  `category` varchar(80) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zDate` (`zDate`,`category`),
			  KEY `pattern` (`pattern`),
			  KEY `uuid` (`uuid`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('framework_orders',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `framework_orders` (
			  `zmd5` varchar(90) NOT NULL,
			  `ORDER` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  PRIMARY KEY (`zmd5`),
			  KEY `ORDER` (`ORDER`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('categorize_changes',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `categorize_changes` (
			  `zmd5` varchar(90) NOT NULL,
			  `sitename` varchar(255) NOT NULL,
			  `category` varchar(255) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `sitename` (`sitename`),
			  KEY `category` (`category`)
			)  ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('categorize_delete',$this->database)){	
				$sql="CREATE TABLE IF NOT EXISTS `categorize_delete` (
				  `sitename` varchar(255) NOT NULL,
				  `category` varchar(128) NOT NULL,
				  `zmd5` varchar(90) NOT NULL,
				  `sended` INT( 1 ) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `category` (`category`),
				  KEY `sitename` (`sitename`),
				  KEY `sended` (`sended`)
				)  ENGINE = MYISAM;";
				
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->FIELD_EXISTS("categorize_delete", "sended")){$this->QUERY_SQL("ALTER TABLE `categorize_delete` ADD `sended` INT( 1 ) NOT NULL ,ADD INDEX ( `sended` )");} 
		
		if(!$this->TABLE_EXISTS('personal_categories',$this->database)){	
				$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`personal_categories` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`category_description` VARCHAR( 255 ) NOT NULL ,
				`master_category` VARCHAR( 50 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				`PublicMode` smallint( 1 ) NOT NULL DEFAULT '0',
				`Meta` smallint( 1 ) NOT NULL DEFAULT '0',
				INDEX ( `category_description` , `sended` ) ,
				KEY `master_category` (`master_category`),
				KEY `PublicMode` (`PublicMode`),
				UNIQUE (`category`))  ENGINE = MYISAM;";		
				$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->FIELD_EXISTS("personal_categories", "PublicMode")){
				$this->QUERY_SQL("ALTER TABLE `personal_categories` 
				ADD `PublicMode` smallint( 1 ) NOT NULL ,
				ADD INDEX ( `PublicMode` )");
		}
		if(!$this->FIELD_EXISTS("personal_categories", "Meta")){
			$this->QUERY_SQL("ALTER TABLE `personal_categories`
				ADD `Meta` smallint( 1 ) NOT NULL ,
				ADD INDEX ( `Meta` )");
		}

	
		
		
	if(!$this->TABLE_EXISTS('work_optimize',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`work_optimize` (
		`table_name` VARCHAR( 50 ) NOT NULL ,
		`job` TINYINT NOT NULL ,
		PRIMARY KEY ( `table_name` ))  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('work_squid_repo',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`work_squid_repo` (
		`version` VARCHAR( 50 ) NOT NULL ,
		`package` longblob NOT NULL ,
		PRIMARY KEY ( `version` )
		)  ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		}		
		
				
		
	if(!$this->FIELD_EXISTS("personal_categories", "master_category")){$this->QUERY_SQL("ALTER TABLE `personal_categories` ADD `master_category` VARCHAR( 50 ) NOT NULL ,ADD KEY `master_category` (`master_category`)");}
		
	if(!$this->TABLE_EXISTS('usersisp',$this->database)){			
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`usersisp` (
				`userid` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`email` VARCHAR( 128 ) NOT NULL ,
				`user_password` VARCHAR( 90 ) NOT NULL ,
				`createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`enabled` SMALLINT( 1 ) NOT NULL ,
				`publicip` VARCHAR( 60 ) NOT NULL ,
				`macaddr` VARCHAR( 60 ) NOT NULL ,
				`language` VARCHAR( 5 ) NOT NULL ,
				`updatedon` TIMESTAMP NOT NULL ,
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`ProxyPacDatas` TEXT NOT NULL,
				`ProxyPacCompiled` TEXT NOT NULL,
				`wwwname` VARCHAR( 255 ) NOT NULL ,
				`ProxyPacRemoveProxyListAtTheEnd` SMALLINT( 1 ) NOT NULL ,
				INDEX ( `createdon` , `enabled` , `updatedon` ) ,
				KEY `wwwname` (`wwwname`),
				UNIQUE KEY (`email`),
				UNIQUE KEY (`publicip`),
				UNIQUE KEY (`macaddr`),
				UNIQUE KEY (`zmd5`)
				) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->FIELD_EXISTS("usersisp", "wwwname")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `wwwname` VARCHAR( 255 ) NOT NULL ,ADD KEY `wwwname` (`wwwname`)");}

		if(!$this->TABLE_EXISTS('usersisp_blkcatz',$this->database)){			
			$sql="CREATE TABLE `squidlogs`.`usersisp_blkcatz` (`category` VARCHAR( 255 ) NOT NULL PRIMARY KEY) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('usersisp_blkwcatz',$this->database)){			
		$sql="CREATE TABLE `squidlogs`.`usersisp_blkwcatz` (
				`category` VARCHAR( 255 ) NOT NULL PRIMARY KEY
				) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('usersisp_catztables',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`usersisp_catztables` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zmd5` VARCHAR( 90 ) NOT NULL,
				`category` VARCHAR( 255 ) NOT NULL,
				`userid` BIGINT UNSIGNED,
				`blck` smallint(1) NOT NULL,
				KEY `category` (`category`),
				UNIQUE (`zmd5`),
				INDEX ( `userid` , `blck`) 
				) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_thumbnails',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_thumbnails` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`withid` SMALLINT( 1 ) NOT NULL ,
				`sended` SMALLINT( 1 ) NOT NULL ,
				`picture` BLOB NOT NULL ,
				`savedate` TIMESTAMP NOT NULL ,
				`filemd5` VARCHAR( 90 ) NOT NULL ,
				`LinkTo` VARCHAR( 90 ) NOT NULL ,
				PRIMARY KEY ( `zmd5` ) ,
				UNIQUE KEY `filemd5` (`filemd5`),
				KEY `LinkTo` (`LinkTo`),
				INDEX ( `withid` , `sended` , `savedate` )
				) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
		}		
			
		
		
		if(!$this->FIELD_EXISTS("webfilters_thumbnails", "filemd5")){$this->QUERY_SQL("ALTER TABLE `webfilters_thumbnails` ADD `filemd5` VARCHAR( 90 ) NOT NULL ,ADD UNIQUE (`filemd5`)");}
		if(!$this->FIELD_EXISTS("webfilters_thumbnails", "LinkTo")){$this->QUERY_SQL("ALTER TABLE `webfilters_thumbnails` ADD `LinkTo` VARCHAR( 90 ) NOT NULL ,ADD KEY `LinkTo` (`LinkTo`)");}
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacDatas")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacDatas` TEXT");}
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacRemoveProxyListAtTheEnd")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacRemoveProxyListAtTheEnd` SMALLINT( 1 ) NOT NULL");}  
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacCompiled")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacCompiled` TEXT NOT NULL");}
		
		$this->CreateCategoryWeightedTable();
	}
	
	function COUNT_CATEGORIES(){
		$c=0;
		$tablescat=$this->LIST_TABLES_CATEGORIES();
		while (list ($table, $none) = each ($tablescat) ){		
			$count=$this->COUNT_ROWS($table);
			$c=$c+$count;
		}
		return $c;
	}
	
	
	FUNCTION MAC_TO_NAME($MAC=null){
		if($MAC=="00:00:00:00:00:00"){return null;}
		if($MAC==null){return null;}
		include_once(dirname(__FILE__)."/class.tcpip.inc");
		$ip=new IP();
		$tt=array();
		if(!$ip->IsvalidMAC($MAC)){return null;}
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT uid FROM `webfilters_nodes` WHERE `MAC`='$MAC'"));
		if($ligne["uid"]<>null){return $ligne["uid"];}
		$results=$this->QUERY_SQL("SELECT hostname FROM `UserAutDB` WHERE `MAC`='$MAC' AND LENGTH(hostname)>0");
		while ($ligne = mysql_fetch_assoc($results)) {$tt[$ligne["hostname"]]=$ligne["hostname"];}
		if(count($tt)>0){return @implode(",", $tt);}
		
	}
	
	public function PostedServerToHost($posteddata){
		$posteddata=trim(strtolower($posteddata));
		if(preg_match("#^http(.*?):#", $posteddata)){
			$arrayURI=parse_url($posteddata);
			if(isset($arrayURI["host"])){$posteddata=$arrayURI["host"];}
		}
	
		if(preg_match("#^http(.*?):\/\/(.+)$#", $posteddata,$re)){
			$posteddata=$re[2];
		}
	
		if(preg_match("#^(.*?):([0-9]+)#", $posteddata,$re)){$posteddata=$re[1];}
		if(preg_match("#^www\.(.*?):([0-9]+)#", $posteddata,$re)){$posteddata=$re[1];}
		return $posteddata;
	
	}	
	
	
	function WebsiteStrip($www){
		
		$www=trim(strtolower($www));
		if(preg_match("#^(http|ftp).*?:\/\/(.+)#i", $www,$re)){$www=$re[2];}
		if($www==null){return;}
		if(strpos($www, "/")>0){
			preg_match("#^(.+?)\/#", $www,$re);
			$www=$re[1];
		}
		
		
		$www=stripslashes($www);
		if($www==null){return;}
		
		$www=str_replace(";", ".", $www);
		if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
		if(preg_match("#<a href.*?http://(.+?)([\/\"'>])#i",$www,$re)){$www=$re[1];}
		$www=str_replace("http://", "", $www);
		if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$www=$re[1];return $www;}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		$www=str_replace("<a href=", "", $www);
		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/"));}
		if(preg_match("#\.php$#", $www,$re)){echo "$www php script...\n";return;}
		if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){echo "$www No extension\n";return;}
		$www=trim(strtolower($www));
		$www=str_replace("\t", "", $www);
		$www=str_replace(chr(194),"",$www);
		$www=str_replace(chr(32),"",$www);
		$www=str_replace(chr(160),"",$www);
		return $www;
	}
	
	
	function ADD_CATEGORYZED_WEBSITE($sitename,$category){
		$category=trim($category);
		$sitename=$this->WebsiteStrip($sitename);
		if(trim($sitename)==null){return;}
		if(trim($category)==null){return;}
		if(strlen($sitename)<4){return;}
		
		$sock=new sockets();
		if(!isset($GLOBALS["MY_UUID"])){
			$GLOBALS["MY_UUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
		}
		$uuid=$GLOBALS["MY_UUID"];
		if(strpos($category, ",")>0){$categories=explode(",",$category);}else{$categories[]=$category;}
		while (list ($index, $cat) = each ($categories) ){
			$cat=trim($cat);
			$md5=md5("$cat$sitename");
			$category_table="category_".$this->category_transform_name($cat);
			if(!$this->TABLE_EXISTS($category_table)){
				writelogs("$category_table no such table",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return false;
			}
			
			$ligneX=mysql_fetch_array($this->QUERY_SQL("SELECT zmd5 FROM $category_table WHERE pattern='$sitename'"));
			if($ligneX["zmd5"]<>null){continue;}
			$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
			if(!$this->ok){echo "categorize $sitename failed $this->mysql_error\n";return false;}			
			
			$this->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
			if(!$this->ok){echo $this->mysql_error."\n";return false;}	
					
		}
		
		if(!isset($GLOBALS["export-community"])){
			$sock=new sockets();
			$sock->getFrameWork("cmd.php?export-community-categories=yes");	
			$GLOBALS["export-community"]=true;
		}
		return true;
	}
	
	
	FUNCTION REMOVE_CATEGORIZED_SITENAME($sitename,$category){
		$table=null;
		if(preg_match("#category_(.+?)#",$category)){$table=$category;}
		if($table==null){$table="category_".$this->category_transform_name($category);}
		writelogs("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'");
		if(!$this->ok){echo $this->mysql_error;return;}
		$md5=md5("$category$sitename");
		$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('$sitename','$category','$md5')";
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL($sql);
		$sock=new sockets();
		$categories=$this->GET_CATEGORIES($sitename,true,true,true);
		$sql="UPDATE visited_sites SET category='$categories' WHERE sitename='$sitename'";
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL($sql);		
		
		
		
		
		$sock->getFrameWork("squid.php?export-deleted-categories=yes");
		
	}
	
	FUNCTION UID_FROM_ALL($pattern){
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if($GLOBALS["TCP_CLASS"]->IsvalidMAC($pattern)){return $this->UID_FROM_MAC($pattern);}
		if($GLOBALS["TCP_CLASS"]->isValid($pattern)){return $this->UID_FROM_IP($pattern);}
		
		
	}
	

	
	FUNCTION UID_FROM_MAC($mac=null){
		if(trim($mac)==null){return ;}
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(isset($GLOBALS[__FUNCTION__][$mac])){return $GLOBALS[__FUNCTION__][$mac];}
		
		
		if($GLOBALS["AS_ROOT"]){
			if(is_file("/etc/squid3/MacToUid.ini")){
				$array=unserialize(@file_get_contents("/etc/squid3/MacToUid.ini"));
				if(isset($array[$mac])){
					$uid=$array[$mac];
					if(trim($uid)==null){$GLOBALS[__FUNCTION__][$mac]=null;return null;}
					$GLOBALS[__FUNCTION__][$mac]=$array[$mac];
					return $array[$mac];
				}
			}
		}
		
		if($GLOBALS["TCP_CLASS"]->IsvalidMAC($mac)){
			$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT uid FROM webfilters_nodes WHERE MAC='$mac'"));
			$uid=trim($ligne["uid"]);
			if(trim($uid)==null){
				$GLOBALS[__FUNCTION__][$mac]=null;
				return;
			}
			$GLOBALS[__FUNCTION__][$mac]=$uid;
			return $uid;
		}
		
		if(!$GLOBALS["TCP_CLASS"]->isValid($mac)){
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM hostsusers WHERE MacAddress='$mac'","artica_backup"));
			$uid=trim($ligne["uid"]);
			if(trim($uid)==null){
				$GLOBALS[__FUNCTION__][$mac]=null;
				return;
			}
			$GLOBALS[__FUNCTION__][$mac]=$uid;
			return $uid;
		}
		
		
		return null;
	}
	
	FUNCTION UID_FROM_IP($ipaddr){
		if(trim($ipaddr)==null){return ;}
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(isset($GLOBALS[__FUNCTION__][$ipaddr])){return $GLOBALS[__FUNCTION__][$ipaddr];}
		if(!$GLOBALS["TCP_CLASS"]->isValid($ipaddr)){return;}
		
		
		
		
		
		if($GLOBALS["AS_ROOT"]){
			if(is_file("/etc/squid3/MacToUid.ini")){
				$array=unserialize(@file_get_contents("/etc/squid3/MacToUid.ini"));
				if(isset($array[$ipaddr])){$GLOBALS[__FUNCTION__][$ipaddr]=$array[$ipaddr];return $array[$ipaddr];}
			}
		}
	
	
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT uid FROM webfilters_ipaddr WHERE ipaddr='$ipaddr'"));
		$uid=trim($ligne["uid"]);
	
		
		$GLOBALS[__FUNCTION__][$ipaddr]=$uid;
		return $uid;
	}	
	
	public function ADD_UID($ipaddr=null,$mac=null,$uid){
		if($ipaddr<>null){
			$uid=$this->UID_FROM_IP($ipaddr);
			if($uid<>null){
				$this->QUERY_SQL("UPDATE webfilters_ipaddr SET uid='$uid' WHERE ipaddr='$ipaddr'");
				if(!$this->ok){return;}	
				}else{
				$this->QUERY_SQL("INSERT INTO webfilters_ipaddr ( ipaddr,uid) VALUES ('$ipaddr','$uid');");
				if(!$this->ok){return;}
			}
		}
		
		if($mac<>null){
			$uid=$this->UID_FROM_MAC($mac);
			if($uid<>null){
				$this->QUERY_SQL("UPDATE webfilters_nodes SET uid='$uid' WHERE MAC='$mac'");
				if(!$this->ok){return;}
			}else{
				$this->QUERY_SQL("INSERT INTO webfilters_nodes (MAC,uid) VALUES ('$mac','$uid');");
				if(!$this->ok){return;}
			}
		}	
		
		$sock=new sockets();
		$sock->getFrameWork("squid.php?user-retranslation=yes&update=yes");
		
	}
	
	public function categorize_temp($sitename,$category=null){
		
	}
	
	function LIST_ALL_TABLES_PERSONAL_CATEGORIES(){
		$sql="SELECT * FROM personal_categories";
		$results=$this->QUERY_SQL($sql);
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$master_category=$ligne["master_category"];
			$categoryKey=$ligne["category"];
			$category_table="category_".$this->category_transform_name($categoryKey);
			if(!$this->TABLE_EXISTS($category_table)){continue;}
			$ARRAY[$category_table]=$categoryKey;
		}
	}
	
	
	function LIST_ALL_CATEGORIES(){
		$array=$this->LIST_TABLES_CATEGORIES();
		$f=array();
		$f[null]="----------------------------";
		$ctz=new mysql_catz();
		$TransArray=$ctz->TransArray();
		while (list ($index, $cat) = each ($array) ){
			if(isset($TransArray[$cat])){$f[$TransArray[$cat]]=$TransArray[$cat];continue;}
			if(preg_match("#category_(.+)#",$cat,$re)){
				$category=trim($re[1]);
				if(is_numeric($category)){continue;}
				if($category==null){continue;}
				$f[$category]=$category;
			}
		}
		
		$sql="SELECT * FROM personal_categories";
		$results=$this->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=trim($ligne["category"]);
			if(is_numeric($category)){continue;}
			if($category==null){continue;}
			$f[$category]=$category;
		}
		
		ksort($f);
		return $f;
	}
	
function GET_FULL_CATEGORIES($sitename){
	
	$cat[]=$this->GET_CATEGORIES_DB($sitename);
	$cat[]=$this->GET_CATEGORIES_HEURISTICS($sitename);
	if(!is_array($cat)){return null;}
	while (list ($index, $categories) = each ($cat) ){
		if(strpos($categories, ",")>0){
			$f=explode(",",$categories); while (list ($a, $b) = each ($f) ){ $category[]=$b; }
			continue;
		}
		$category[]=$categories;
	}
	
	while (list ($index, $categories) = each ($category) ){
		$CLEANED[$categories]=$categories;
	}
	
	while (list ($index, $categories) = each ($CLEANED) ){
		$F_CATZ[]=$categories;
	}
	
	
	
	$final=@implode(",", $F_CATZ);
	$final=str_replace(",,", ",", $final);
	return $final;
	
}
	
// ***********************************************************************************************************************************************	
function GET_CATEGORIES($sitename,$nocache=false,$nok9=false,$noheuristic=false,$noArticaDB=false){
		$pagename=null;if(function_exists("CurrentPageName")){$pagename=CurrentPageName();}
		$t=time();
		if($pagename=='exec.cleancloudcatz.php'){$noArticaDB=true;}
		if(trim($sitename)==null){return;}
		$sitename=strtolower(trim($sitename));
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^\*\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		if(substr($sitename, 0,1)=="."){$sitename=substr($sitename, 1,strlen($sitename));}
		if(trim($sitename)==null){return;}
		if(preg_match("#^www[0-9]+\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		
		
		$IpClass=new IP();
		if($IpClass->isValid($sitename)){ if(isset($GLOBALS["IPCACHE"][$sitename])){ $sitename=gethostbyaddr($sitename); $GLOBALS["IPCACHE"][$sitename]=$sitename; } }
		
		$sitename=trim(strtolower($sitename));
		if(function_exists("idn_to_ascii")){ $sitename = @idn_to_ascii($sitename, "UTF-8"); }


		if(!isset($GLOBALS["BlueCoatKey"])){ $sock=new sockets(); $GLOBALS["BlueCoatKey"]=trim($sock->GET_INFO("BlueCoatKey")); }
		
		$GLOBALS["CATEGORIZELOGS"]=array();
		if(isset($GLOBALS["GET_CATEGORIES_MEMORY"][$sitename])){return $GLOBALS["GET_CATEGORIES_MEMORY"][$sitename];}
		$cat=array();
		$cattmp=array();

		
		if(!isset($GLOBALS["getpartOnly"])){$GLOBALS["getpartOnly"]=$this->GetFamilySitestt(null,true);}
		if(isset($GLOBALS["getpartOnly"][$sitename])){ if(($GLOBALS["getpartOnly"][$sitename])){return null;} }
		
	if(strpos(" $sitename", ".")==0){
		$this->categorize_reaffected($sitename);
		$GLOBALS["CATEGORIZELOGS-COUNT"]++;
		$GLOBALS["GET_CATEGORIES_MEMORY"][$sitename]="reaffected";
		$this->categorize_temp($sitename,"reaffected");
		return "reaffected";
	}
		

	
	if($cat<>null){	 if($cat=="unknown"){return null;} return $cat; }
	
	$familysite=$this->GetFamilySites($sitename);
	
	
	
	
	
	if(!$noArticaDB){
		$t1=time();
		if($GLOBALS["VERBOSE"]){echo " ********* GET_CATEGORIES_DB ( $sitename ) *********\n";}
		if($GLOBALS["OUTPUT"]){echo date("H:i:s")." $sitename -> GET_CATEGORIES_DB($sitename)\n";}
		$cat=$this->GET_CATEGORIES_DB($sitename);
		if($cat<>null){
			if(isset($_REQUEST["WEBTESTS"])){echo "ArticaDB: ";}
			$this->categorize_temp($sitename,$cat); return $cat;}
			if($GLOBALS["VERBOSE"]){echo " ********* GET_CATEGORIES_DB ( $sitename ) ". distanceOfTimeInWords($t1,time(),true)."\n";}
	}
	
	if(!$noheuristic){
		$t1=time();
		if($GLOBALS["VERBOSE"]){echo " ********* GET_CATEGORIES_HEURISTICS ( $sitename ) *********\n";}
		if($GLOBALS["OUTPUT"]){echo date("H:i:s")." $sitename -> GET_CATEGORIES_HEURISTICS($sitename)\n";}
		
		if(!isset($GLOBALS["SquidAppendDomain"])){
			$sock=new sockets();
			$SquidAppendDomain=trim($sock->GET_INFO("SquidAppendDomain"));
			$GLOBALS["SquidAppendDomain"]=trim($sock->GET_INFO("SquidAppendDomain"));
			if($GLOBALS["SquidAppendDomain"]==null){
				$MainArray=unserialize(base64_decode($sock->GET_INFO("resolvConf")));
				$GLOBALS["SquidAppendDomain"]=trim($MainArray["DOMAINS1"]);
				if($GLOBALS["SquidAppendDomain"]==null){$GLOBALS["SquidAppendDomain"]="localhost.local";}
			}
		}
		
		
		if($GLOBALS["SquidAppendDomain"]<>null){
			$domain=str_replace(".", "\.", $GLOBALS["SquidAppendDomain"]);
			if(preg_match("#\.$domain$#", $GLOBALS["SquidAppendDomain"])){return "internal";}
		}
		
		$cat=$this->GET_CATEGORIES_HEURISTICS($sitename);
		if($cat<>null){
			$this->categorize_temp($sitename,$cat); 
			return $cat;
		}
		if($GLOBALS["VERBOSE"]){echo " ********* GET_CATEGORIES_HEURISTICS ( $sitename ) ". distanceOfTimeInWords($t1,time(),true)."\n";}
	}

	
		
	
	
			

			
	if($GLOBALS["OUTPUT"]){echo date("H:i:s")." $sitename -> GET_CATEGORIES_GOOGLE_SAFE($sitename)\n";}
	$t1=time();
	$cat=$this->GET_CATEGORIES_GOOGLE_SAFE($sitename);
	if($cat<>null){if(isset($_REQUEST["WEBTESTS"])){echo "Google: ";}
		$this->categorize($sitename, $cat);
		$this->categorize_temp($sitename,$cat);
		return $cat;
	}
		
	if(!$nok9){
		if($GLOBALS["OUTPUT"]){echo date("H:i:s")." $sitename -> GET_CATEGORIES_K9($sitename)\n";}
		$cat=$this->GET_CATEGORIES_K9($sitename);
		if($cat<>null){ 
			$this->categorize($sitename, $cat);
			$this->categorize_temp($sitename,$cat); 
			return $cat;
		}
	}

	if($IpClass->isValid($sitename)){
		$this->categorize_temp($sitename,null);
		return null;
	}
	
	if($GLOBALS["OUTPUT"]){echo date("H:i:s")." $sitename -> GET_CATEGORIES_K9($sitename)\n";}
	$cat=$this->GET_CATEGORIES_REAFFECTED($sitename);
	$this->categorize_temp($sitename,$cat);
	$this->cloudlogs("Cannot categorize $sitename / $cat");
	
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES($sitename) $took<br>\n";
	}
	
	
	return $cat;
	
}



private function GET_CATEGORIES_GOOGLE_SAFE($sitename){
	if(!class_exists("phpGSB")){return null;}
	$t=time();
	include_once(dirname(__FILE__)."/class.categorize.externals.inc");
	$ext=new external_categorize(null);
	
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES_GOOGLE_SAFE($sitename) $took<br>\n";
	}
	
	return trim($ext->UBoxGoogleSafeBrowsingPhpGsbLookup($sitename));
	

}

private function GET_CATEGORIES_K9($sitename){
	if(strlen($GLOBALS["BlueCoatKey"])==0){return null;}
	if(function_exists("debug_mem")){debug_mem();}
	$this->cloudlogs("K9($sitename)");
	$t=time();
	if(!class_exists("external_categorize")){
		if(!is_file(dirname(__FILE__)."/class.categorize.externals.inc")){return;}
		include_once(dirname(__FILE__)."/class.categorize.externals.inc");
	}
	
	$ext=new external_categorize($sitename);
	
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES_K9($sitename) $took<br>\n";
	}
	
	return trim($ext->K9());
	
	
}

private function GET_CATEGORIES_REAFFECTED($sitename){
	$t=time();
	$ipaddr=gethostbyname($sitename);
	
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES_REAFFECTED($sitename) -> $ipaddr $took<br>\n";
	}
	
	if($ipaddr<>$sitename){return null;}
	$ipaddr=gethostbyname("www.$sitename");
	
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES_REAFFECTED(www.$sitename) -> $ipaddr $took<br>\n";
	}
	
	if($ipaddr<>$sitename){return null;}
	$this->categorize_reaffected($sitename);
	$GLOBALS["CATEGORIZELOGS-COUNT"]++;
	return "reaffected";

}

private function GET_CATEGORIES_HEURISTICS($sitename){
	$this->cloudlogs("HEURISTIC: CategoriesFamily($sitename)");
	$t=time();
	$catz=$this->CategoriesFamily($sitename);
	if(!isset($GLOBALS["CATEGORIZELOGS-COUNT"])){$GLOBALS["CATEGORIZELOGS-COUNT"]=0;}
	if($GLOBALS["VERBOSE"]){
		$took=distanceOfTimeInWords($t,time(),true);
		echo "GET_CATEGORIES_HEURISTICS($sitename) -> $catz $took<br>\n";
	}
	
	
	if($catz==null){return null;}
	$this->categorize($sitename, $catz);
	if(!isset($GLOBALS["HEURISTICS"])){$GLOBALS["HEURISTICS"]=0;}
	$GLOBALS["HEURISTICS"]++;
	$GLOBALS["CATEGORIZELOGS-COUNT"]++;
	return trim($catz);
	
}


private function GET_CATEGORIES_DB($sitename){
	$pagename=CurrentPageName();
	$t=time();
	$qz=new mysql_catz();
	
	$this->cloudlogs("$pagename: mysql_catz -> $sitename");
	$catz=$qz->GET_CATEGORIES($sitename);
	
	
	if($GLOBALS["VERBOSE"]){$took=distanceOfTimeInWords($t,time(),true); echo "qz->GET_CATEGORIES_DB($sitename) = $catz took $took<br>\n";}
	if($catz==null){return;}
	
	if(!isset($GLOBALS["ARTICADB"])){$GLOBALS["ARTICADB"]=0;}
	$GLOBALS["ARTICADB"]++;
	$GLOBALS["CATZWHY"]="INTERNAL-CATZ";
	return trim($catz);
	
}

public function isCrashedRootRepair($table){
	$unix=new unix();
	if(!preg_match("#is marked as crashed and should be repaired#i", $this->mysql_error)){return false;}
	$myisamchk=$unix->find_program("myisamchk");
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	$table_path="$WORKDIR/data/squidlogs/$table.MYI";
	shell_exec("myisamchk -r $table_path");
	return true;

}

// ***********************************************************************************************************************************************
	public function GetFamilySites($sitename){
		if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
		if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/class.squid.familysites.inc");}
		$fam=new squid_familysite();
		$GLOBALS["GetFamilySites"][$sitename]=$fam->GetFamilySites($sitename);
		return $GLOBALS["GetFamilySites"][$sitename];
    }
    
    
    private function ExtractAllUris($content){
    	$matches=array();
    	if(!preg_match_all("/a[\s]+[^>]*?href[\s]?=[\s\"\']+(.*?)[\"\']+.*?>"."([^<]+|.*?)?<\/a>/",$content, $matches)){return array();}
    	$matches = $matches[1];
    	foreach($matches as $var){
    		$array=parse_url($var);
    		if(isset($array["host"])){
    			if(preg_match("#^www\.(.+)#", $array["host"],$re)){$array["host"]=$re[1];}
    			$array[$array["host"]]=$array["host"];
    		}
    
    	}
    
    	return $array;
	 }
	 
	 
	 private function already_Cats($www){
	 	$array[]="addthis.com";
	 	//$array[]="google.";
	 	$array[]="w3.org";
	 	$array[]="icra.org";
	 	$array[]="facebook.";
	 	while (list ($num, $wwws) = each ($array)){
	 		$pattern=str_replace(".", "\.", $wwws);
	 		if(preg_match("#$pattern#", $www)){return true;}
	 
	 	}
	 	return false;
	 }	

	 
	 
	public function categorize_logs($category,$action,$item){
		$events=mysql_escape_string2("$category $action $item");
		$date=date("Y-m-d H:i:s");
		$uid=$_SESSION["uid"];
		if($uid=="-100"){$uid="Manager";}
		$sql="INSERT IGNORE INTO `webfilter_catprivslogs` (zDate,events,uid) VALUES ('$date','$events','$uid')";
		$this->QUERY_SQL($sql);
	}
    
    
    public function free_categorizeSave($PostedDatas=null,$category,$ForceCat=0,$ForceExt=0){
    	include_once(dirname(__FILE__)."/class.html2text.inc");
    	$sock=new sockets();
    	if(!isset($GLOBALS["uuid"])){$sock=new sockets();$GLOBALS["uuid"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));}
    	$uuid=$GLOBALS["uuid"];
    	
    	
    	$f=array();
    	$ExtractAllUris=$this->ExtractAllUris($PostedDatas);
    	if(count($ExtractAllUris)>0){
    		while (list ($num, $ligne) = each (	$ExtractAllUris)){$f[]=$num;}
    		$PostedDatas=null;
    	}
    
    	$h2t =new html2text($PostedDatas);
    	$h2t->get_text();
    
    	while (list ($num, $ligne) = each (	$h2t->_link_array)){
    		if(trim($ligne)==null){continue;}
    		$ligne=strtolower($ligne);
    		
    		
    		
    		$ligne=str_replace("(whois)", "", $ligne);
    		$ligne=str_replace("||", "", $ligne);
    		$ligne=str_replace("^", "", $ligne);
    		$ligne=trim($ligne);
    		if(preg_match("#^([0-9\.]+):[0-9]+#", $ligne,$re)){
    			$websitesToscan[]=$re[1];
    			continue;
    		}
    		if(strpos(" $ligne", "http")==0){$ligne="http://$ligne";}
    		$hostname=parse_url($ligne,PHP_URL_HOST);
			if(preg_match("#^www\.(.+)#", $hostname,$re)){$hostname=$re[1];}
    		if(preg_match("#^\.(.+)#", $hostname,$re)){$hostname=$re[1];}
    		if(preg_match("#^\*\.(.+)#", $hostname,$re)){$hostname=$re[1];}
    		writelogs("$ligne = $hostname",__FUNCTION__,__FILE__,__LINE__);
    		$websitesToscan[]=$ligne;
    	}
    
    
    
    	$PostedDatas=str_replace("<", "\n<", $PostedDatas);
    	$PostedDatas=str_replace(' rel="nofollow"', "", $PostedDatas);
    	$PostedDatas=str_replace("\r", "\n", $PostedDatas);
    	$PostedDatas=str_replace("https:", "http:", $PostedDatas);
    	if($PostedDatas<>null){$f=explode("\n",$PostedDatas );}
    	
    
    	if(!is_numeric($ForceExt)){$ForceExt=0;}
    	if(!is_numeric($ForceCat)){$ForceCat=0;}
    	$ipClass=new IP();
    	while (list ($num, $www) = each ($f) ){
    		$www=trim($www);
    		if($www==null){continue;}
    		if(preg_match("#--------------#", $www)){continue;}
    		if(preg_match("#No extension#", $www)){continue;}
    		if(preg_match("#no website#i", $www)){continue;}
    		if(preg_match("#^analyze\s+[0-9]+\s+#", $www)){continue;}
    		if(preg_match("#(false|true):\s+(.+?)\s+already#i", $www,$re)){$www=$re[2];}
    		writelogs("Scanning $www",__FUNCTION__,__FILE__,__LINE__);
    		if(preg_match("#^(.+?)\"\s+#", $www,$re)){$www=$re[1];}
    		if(preg_match("#^([0-9\.]+):[0-9]+#", $www,$re)){$www=$re[1];}
    		
    		$www=str_replace("(whois)", "", $www);
    		$www=str_replace("\r", "", $www);
    		$www=str_replace("||", "", $www);
    		$www=str_replace("^", "", $www);
    		
    		$www=trim(strtolower($www));
    		
    		if($ipClass->isValid($www)){$www=ip2long($www).".addr";$websitesToscan[]=$www;continue;}
    		
    		if($www==null){continue;}
    		$www=stripslashes($www);
    		if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
    		if(preg_match('#<a rel=.+?href="(.+?)"#', $www,$re)){$www=$re[1];}
    		if(preg_match("#<a href.*?http://(.+?)([\/\"'>])#i",$www,$re)){$www=$re[1];}
    		if(preg_match("#<span>www\.(.+?)\.([a-z]+)</span>#i",$www,$re)){$www=$re[1].".".$re[2];}
    		$www=str_replace("http://", "", $www);
    		if(preg_match("#\/\/.+?@(.+)#",$www,$re)){$websitesToscan[]=$re[1];}
    		if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$websitesToscan[]=$re[1];continue;}
    		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
    		$www=str_replace("<a href=", "", $www);
    		$www=str_replace("<img src=", "", $www);
    		$www=str_replace("title=", "", $www);
    		if(preg_match("#^(.*?)\/#", $www,$re)){$www=$re[1];}
    		if(preg_match("#\.php$#", $www,$re)){echo "$www php script...\n";continue;}
    		$www=str_replace("/", "", $www);
    		$www=trim($www);
    		if($ForceExt==0){
    			if(!preg_match("#\.([a-z0-9]+)$#",$www,$re)){echo "`$www` No extension !!?? \n";continue;}
    			if(strlen($re[1])<2){
    				if(!is_numeric($re[1])){
    					echo "$www bad extension `.{$re[1]}` [$ForceExt]\n";
    					continue;
    				}
    			}
    		}
    
    		$www=str_replace('"', "", $www);
    		writelogs("Success pass $www",__FUNCTION__,__FILE__,__LINE__);
    		$websitesToscan[]=$www;
    	}
    
    	while (list ($num, $www) = each ($websitesToscan) ){$cleaned[$www]=$www;}
    	$websitesToscan=array();
    	while (list ($num, $www) = each ($cleaned) ){$websitesToscan[]=$www;}
    
    
    	while (list ($num, $www) = each ($websitesToscan) ){
    		writelogs("Scanning $www",__FUNCTION__,__FILE__,__LINE__);
    		$www=strtolower($www);
    		$www=replace_accents($www);
    		if($www=="www"){continue;}
    		if($www=="ssl"){continue;}
    		$www=str_replace("http://", "", $www);
    		$www=str_replace("https://", "", $www);
    		$www=str_replace("ftp://", "", $www);
    		$www=str_replace("ftps://", "", $www);
    		if(preg_match("#.+?@(.+)#",$www,$ri)){$www=$ri[1];}
    		if(preg_match("#^www\.(.+?)$#i",$www,$ri)){$www=$ri[1];}
    		if($ForceCat==0){
    			if($this->already_Cats($www)){continue;}
    		}
    			
    		if(strpos($www, '"')>0){$www=substr($www, 0,strpos($www, '"'));}
    		if(strpos($www, "'")>0){$www=substr($www, 0,strpos($www, "'"));}
    		if(strpos($www, ">")>0){$www=substr($www, 0,strpos($www, ">"));}
    		if(strpos($www, "?")>0){$www=substr($www, 0,strpos($www, "?"));}
    		if(strpos($www, "\\")>0){$www=substr($www, 0,strpos($www, "\\"));}
    		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/")-1);}
    		if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
    		if(preg_match("#^\*\.(.+)#", $www,$re)){$www=$re[1];}
    		if(preg_match("#\.html$#i",$www,$re)){continue;}
    		if(preg_match("#\.htm$#i",$www,$re)){continue;}
    		if(preg_match("#\.gif$#i",$www,$re)){continue;}
    		if(preg_match("#\.png$#i",$www,$re)){continue;}
    		if(preg_match("#\.jpeg$#i",$www,$re)){continue;}
    		if(preg_match("#\.jpg$#i",$www,$re)){continue;}
    		if(preg_match("#\.php$#i",$www,$re)){continue;}
    		if(preg_match("#\.js$#i",$www,$re)){continue;}
    		if($ForceExt==0){
    			if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){;
    			echo "$www bad extension `$www` \n";
    			continue;
    			}
    			}
    			if(strpos(" ", trim($www))>0){continue;}
    			$sites[$www]=$www;
    	}
    
    
    	
    	$this->CheckTable_dansguardian();
    
    	if(count($sites)==0){echo "NO websites\n";return;}
    	
    	echo "\n----------------\nanalyze ".count($sites)." websites into $category\n";
    	while (list ($num, $www) = each ($sites) ){
    		$www=trim($www);
    		if($www==null){continue;}
    		if(preg_match("#^www\.(.+?)$#", $www,$re)){$www=$re[1];}
    		writelogs("Analyze $www",__FUNCTION__,__FILE__,__LINE__);
    		$md5=md5($category.$www);
    		if($ForceCat==0){
    			$cats=$this->GET_CATEGORIES($www,true,true,true);
    			if($cats<>null){echo "FALSE: $www already categorized ($cats)\n";continue;}
    		}
    
    		$category_table="category_".$this->category_transform_name($category);
    		$this->CreateCategoryTable($_POST["category"]);
    		
    
    		$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
    		if(!$this->ok){echo "categorize $www failed $this->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";continue;}
    		$this->categorize_logs($category, "{add}", $www);
    		
    		echo "TRUE: $www Added\n";
    		$this->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
    		if(!$this->ok){echo $this->mysql_error."\n";}
    	}
    		
    	$sock=new sockets();
    	$sock->getFrameWork("cmd.php?export-community-categories=yes");
    }

    
    public function categorize_reaffected($www){
    	$www=trim(strtolower($www));
    	if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
    	if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^\*\.(.+)#", $www,$re)){$www=$re[1];}
    	
    	
    	if(!isset($GLOBALS["MYUUID"])){
    		$sock=new sockets();
			$GLOBALS["MYUUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
    	}
    	$newmd5=md5("reaffected$www");
    	$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$www','reaffected')");
		$this->QUERY_SQL("INSERT IGNORE INTO category_reaffected (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'reaffected','$www','{$GLOBALS["MYUUID"]}')");
		$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
    }
    
   
    
    
    public function GetFamilySitestt($domain,$getpartOnly=false){
    		if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/class.squid.familysites.inc"); }
			$fam=new squid_familysite();
			return $fam->GetFamilySitestt($domain,$getpartOnly);
    } 
    
		
		
	
	
	
private function CategoriesFamily($www){
		include_once(dirname(__FILE__)."/class.squid.categorize.generic.inc");
		$f=new generic_categorize();
		$cat=$f->GetCategories($www);
		if($cat<>null){$this->ADD_CATEGORYZED_WEBSITE($www,$cat);
			writelogs("Generic Category $cat for $www done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return $cat;
		}

}  	
	
	
	function category_transform_name($category){
			$q=new mysql_catz(true);return $q->category_transform_name($category);
	}
	
	function CachePerfHour(){
		$currentHour=date("H");
		$currentHour=$currentHour-1;
		$currentDay=date('d');
		$currentMonth=date('m');
		$currentYear=date('Y');
		if(!$this->TABLE_EXISTS("webcacheperfs")){return -1;}
		$sql="SELECT pourc FROM webcacheperfs WHERE zHour=$currentHour AND zDay=$currentDay AND zMonth=$currentMonth AND zYear=$currentYear";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs($this->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		writelogs("$sql=`{$ligne["pourc"]}%`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return $ligne["pourc"];	
		
		
	}
	
	function TransArray(){
		$q=new mysql_catz(true);
		return $q->TransArray();	
		
	}
	
	function cat_totablename($category){
		$category=trim(strtolower($category));
		$trans=$this->TransArray();
		while (list ($table, $categories) = each ($trans) ){
			if($categories==$category){return $table;}
		}
		
		return "category_{$category}";
		
	}
	
	function isTableCompressed($tablename){
		if(isset($GLOBALS["isTableCompressed::$tablename"])){return $GLOBALS["isTableCompressed::$tablename"];}
		$ligne=mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS FROM squidlogs LIKE '$tablename'"));
		if($ligne["Row_format"]=="Compressed"){
			$GLOBALS["isTableCompressed::$tablename"]=true;
			return true;
		}
		$GLOBALS["isTableCompressed::$tablename"]=false;
		return false;
		
	}
	
	function CompressTable($tablename){
		
		$unix=new unix();
		$sock=new sockets();
		$myisamchk=$unix->find_program("myisamchk");
		$myisampack=$unix->find_program("myisampack");
		$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
		if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
		
		$this->QUERY_SQL("OPTIMIZE TABLE $tablename");
		$this->QUERY_SQL("LOCK TABLE $tablename WRITE");
		$this->QUERY_SQL("FLUSH TABLE $tablename");
		if(!is_file("$MYSQL_DATA_DIR/$this->database/$tablename.MYI")){
			if($GLOBALS["VERBOSE"]){echo "Skip $MYSQL_DATA_DIR/$this->database/$tablename.MYI (did not exists)\n";}
			return;
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "$myisamchk -cFU $MYSQL_DATA_DIR/$this->database/$tablename.MYI\n";}
		shell_exec("$myisamchk -cFU $MYSQL_DATA_DIR/$this->database/$tablename.MYI");
		shell_exec("$myisampack -f $MYSQL_DATA_DIR/$this->database/$tablename.MYI");
		shell_exec("$myisamchk -raqS $MYSQL_DATA_DIR/$this->database/$tablename.MYI");
		$this->QUERY_SQL("FLUSH TABLE $tablename");
		$this->QUERY_SQL("UPDATE tables_day SET `compressed`=1 WHERE `tablename`='$tablename'");		
	}
	
	function UncompressTable($tablename){
		if(!$this->isTableCompressed($tablename)){return null;}
		unset($GLOBALS["isTableCompressed::$tablename"]);
		if($GLOBALS["VERBOSE"]){echo "Uncompress table `$tablename`\n";}
		writelogs_squid("Uncompress table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"MySQL");
		$unix=new unix();
		$sock=new sockets();
		$myisamchk=$unix->find_program("myisamchk");
		$myisampack=$unix->find_program("myisampack");
		$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
		if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
		$cmd="$myisamchk --unpack $MYSQL_DATA_DIR/$this->database/$tablename.MYI 2>&1";
		$esults[]=$cmd;
		exec("$cmd",$esults);
		$this->QUERY_SQL("FLUSH TABLE $tablename");
		$this->QUERY_SQL("UPDATE tables_day SET `compressed`=0 WHERE `tablename`='$tablename'");
		return @implode("\n", $esults);
		if($this->isTableCompressed($tablename)){
			writelogs_squid("Uncompress table `$tablename`: FAILED ". @implode(", ", $esults),__FUNCTION__,__FILE__,__LINE__,"stats");
		}
		if($GLOBALS["VERBOSE"]){echo "Uncompress table done\n";}
	}
	
	
	
	function tablename_tocat($tablename){
			if(isset($GLOBALS["tablename_tocat"][$tablename])){return $GLOBALS["tablename_tocat"][$tablename];}
			$trans=$this->TransArray();
			if(!isset($trans[$tablename])){
				if($this->TABLE_EXISTS("$tablename")){
					$ligne2=mysql_fetch_array($this->QUERY_SQL("SELECT category FROM $tablename LIMIT 0,1"));
					if(trim($ligne2["category"])<>null){
						$GLOBALS["tablename_tocat"][$tablename]=trim($ligne2["category"]);
						return trim($ligne2["category"]);
					}
					
					if(preg_match("#category_(.+)#", $tablename,$re)){
						$GLOBALS["tablename_tocat"][$tablename]=trim(strtolower($re[1]));
						return trim(strtolower($re[1]));
					}
				}
					
					
			}else{
				$GLOBALS["tablename_tocat"][$tablename]=$trans[$tablename];
				return $trans[$tablename];
			}
	 }
	
	function filaname_tocat($filename){
		if(strpos($filename, "/domains.ufdb")>0){$filename=str_replace("/domains.ufdb", "",$filename);}
		$q=new mysql_catz(true);
		$trans=$q->TransArray();	
		$filename=basename($filename);
		$filename=str_replace(".ufdb", "", $filename);
		if(preg_match("#^category_(.*)#", $filename,$re)){$filename=$re[1];}
		if(isset($trans["category_{$filename}"])){return $trans["category_{$filename}"];}
		$array["audio-video"]="audio-video";
		$array["gambling"]="gamble";
		$array["cooking"]="hobby/cooking";
		$array["bank"]="finance/banking";
		$array["lingerie"]="sex/lingerie";
		$array["drogue"]="drugs";
		$array["child"]="children";
		$array["adult"]="porn";
		$array["aggressive"]="agressive";
		$array["agressif"]="agressive";
		$array["radio"]="webradio";
		$array["remote-control"]="remote-control";
		$array["social_networks"]="socialnet";
		$array["mobile-phone"]="mobile-phone";
		$array["sports"]="recreation/sports";
		$array["verisign"]="sslsites";
		$array["associations"]="associations";
		$array["translation"]="translators";
		
		$array["arjel"]="arjel";
		if(isset($array["$filename"])){return $array["$filename"];}
		
		return "$filename";
	}
	
	
	
	function category_transform_name_toulouse($category){
			if($category=="finance/banking"){$category="bank";}
			if($category=="drugs"){$category="drogue";}
			if($category=="webradio"){$category="radio";}
			if($category=="recreation/sports"){$category="sports";}
			if($category=="sslsites"){$category="verisign";}
			if($category=="children"){$category="child";}
			if($category=="porn"){$category="adult";}
			if($category=="hobby/cooking"){$category="cooking";}
			if($category=="agressive"){$category="agressif";}
			$category=str_replace('/other',"other",$category);
			if(preg_match("#.+?\/(.+)#", $category,$re)){$category=$re[1];}
			$category=str_replace('/',"",$category);
			$category=str_replace('-',"",$category);
			$category=str_replace('_',"",$category);
			return $category;	
	}

	
	function CreateCategoryUrisTable($category,$fulltablename=null){
		$category=$this->category_transform_name($category);
		$tablename=strtolower("categoryuris_$category");
		if($fulltablename<>null){$tablename=$fulltablename;}
		$sql="CREATE TABLE IF NOT EXISTS `$this->database`.`$tablename` (
			`zmd5` VARCHAR( 90 ) NOT NULL ,
			`zDate` DATETIME NOT NULL ,
			`pattern` VARCHAR( 255 ) NOT NULL ,
			`enabled` smallint( 1 ) NOT NULL DEFAULT '1',
			PRIMARY KEY ( `zmd5` ) ,
			UNIQUE KEY `pattern` (`pattern`),
			KEY `zDate` (`zDate`),		
			KEY `enabled` (`enabled`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){
				writelogs("Failed to create $tablename",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
		return true;
		
	}
	
	
	function WebFilteringAllSystems(){
		$f=array();
		$sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 AND AllSystems=1 ORDER BY groupname";
		$results=$this->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return array();}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]="<li style='font-size:14px;font-weight:bold'>".$ligne["groupname"]."</li>";
			
		}
		return $f;
		
	}
	
	
	function CreateCategoryTable($category,$fulltablename=null){
		$catz=new mysql_catz();
		$catz->CreateCategoryTable($category,$fulltablename);
		if($category=="drogue"){$category="drugs";}
		if($category=="gambling"){$category="gamble";}
		if($category=="hobby/games"){$category="games";}
		if($category=="forum"){$category="forums";}
		if($category=="spywmare"){$category="spyware";}
		if($category=="association"){$category="associations";}			

		if($this->EnableRemoteStatisticsAppliance==1){return;}
		$category=$this->category_transform_name($category);
		$tablename=strtolower("category_$category");
		if($fulltablename<>null){$tablename=$fulltablename;}
		if($tablename=="category_teans"){$tablename="category_teens";}
		$tablename=strtolower($tablename);
		$tablename=str_replace("category_category_","category_",$tablename);
		if($tablename=="category_drogue"){$tablename="category_drugs";}
		if($tablename=="category_gambling"){$tablename="category_gamble";}
		if($tablename=="category_hobby_games"){$tablename="category_games";}
		if($tablename=="category_forum"){$tablename="category_forums";}
		if($tablename=="category_spywmare"){$tablename="category_spyware";}
		if($tablename=="category_association"){$tablename="category_associations";}
		$tablename=strtolower($tablename);
			
		if($GLOBALS["VERBOSE"]){echo "CREATE CATEGORY TABLE `$tablename`\n";}
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `$this->database`.`$tablename` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`familysite` VARCHAR( 128 ) NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				UNIQUE KEY `pattern` (`pattern`),
				KEY `familysite` (`familysite`),
				KEY `zDate` (`zDate`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`)
			) ENGINE=MYISAM;";
		
		
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("Failed to create category_$category",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		

		$this->QUERY_SQL("DROP TABLE webfilters_categories_caches");
		$this->create_webfilters_categories_caches();
		
	}
	
	function CreateCategoryWeightedTable(){
		
		if(!$this->TABLE_EXISTS("phraselists_weigthed",$this->database)){	
		$sql="CREATE TABLE `$this->database`.`phraselists_weigthed` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`language` VARCHAR( 40 ) NOT NULL ,
				`score` INT( 3 ) NOT NULL DEFAULT '50',
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				KEY `zDate` (`zDate`),
	  			KEY `pattern` (`pattern`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`),
	  			KEY `language` (`language`),
	  			KEY `score` (`score`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("Failed to create phraselists_weigthed",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->CategoryWeightedImport();
		}else{
			if($this->COUNT_ROWS("phraselists_weigthed")==0){$this->CategoryWeightedImport();}
		}			
		
	}
	
	
	function CreateCategoryBannedRegexPurllistTable(){
		
		if(!$this->TABLE_EXISTS("regex_urls",$this->database)){	
		$sql="CREATE TABLE `$this->database`.`regex_urls` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` TEXT NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '0',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				KEY `zDate` (`zDate`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`),
	  			KEY `uuid` (`uuid`),
	  			FULLTEXT KEY `pattern` (`pattern`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("Failed to create regex_urls",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return;}
			$this->CategoryExpressionsUrlsImport();
			
		}else{
			if($this->COUNT_ROWS("regex_urls")==0){$this->CategoryExpressionsUrlsImport();}
		}			
		
	}	
	
	function CategoryWeightedImport(){
		$f=unserialize(@file_get_contents(dirname(__FILE__)."/databases/weightedPhrases.db"));
		$prefix="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,score,uuid,language) VALUES ";
		while (list ($linum, $line) = each ($f) ){
			if(trim($line)==null){continue;}
			$t[]=$line;
			if(count($t)>500){$this->QUERY_SQL($prefix.@implode(",", $t));$t=array();}
		}
		if(count($t)>0){$this->QUERY_SQL($prefix.@implode(",", $t));$t=array();}	
		
		
		
	}
	
	function CategoryExpressionsUrlsImport(){
		$f["porn"][]="(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex|phone)+.*(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)s?";
		$f["porn"][]="(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)+.*(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex)+";
		$f["porn"][]="(adultsight|adultsite|adultsonly|adultweb|blowjob|bondage|centerfold|cumshot|cyberlust|cybercore|hardcore|masturbat)";
		$f["porn"][]="(bangbros|pussylip|playmate|pornstar|sexdream|showgirl|softcore|striptease)";
		$f["porn"][]="(incest|obscene|pedophil|pedofil)";
		$f["porn"][]="(sex|fuck|boob|cunt|fetish|tits|anal|hooter|asses|shemale|submission|porn|xxx|busty|knockers|slut|nude|naked|pussy)+.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)";
		$f["porn"][]="(girls|babes|bikini|model)+.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)";
		
		
		$f["models"][]="(male|m[ae]n|boy|girl|beaut|agen[ct]|glam)+.*(model|talent)";
		$f["proxies"][]="(cecid.php|nph-webpr|nph-pro|/dmirror|cgiproxy|phpwebproxy|__proxy_url|proxy.php)";
		$f["proxies"][]="(anonymizer|proxify|megaproxy)";
		$f["gamble"][]="(casino|bet(ting|s)|lott(ery|o)|gam(e[rs]|ing|bl(e|ing))|sweepstake|poker)";
		
		$f["recreation/sports"][]="(bowling|badminton|box(e[dr]|ing)|skat(e[rs]|ing)|hockey|soccer|nascar|wrest|rugby|tennis|sports|cheerlead|rodeo|cricket|badminton|stadium|derby)";
		$f["recreation/sports"][]="((paint|volley|bas(e|ket)|foot|quet)ball|/players[/\.]?|(carn|fest)ival)";
		
		$f["dating"][]="(meet|hook|mailord|latin|(asi|mexic|dominic|russi|kore|colombi|balk)an|brazil|filip|french|chinese|ukrain|thai|tour|foreign|date)+.*(dar?[lt]ing|(sing|coup)le|m[ae]n|girl|boy|guy|mat(e|ing)|l[ou]ve?|partner|meet)";
		$f["dating"][]="(marr(y|i[ae])|roman(ce|tic)|fiance|bachelo|dating|affair|personals)";
		$f["tracker"][]="(adlog.php|cnt.cgi|count.cgi|count.dat|count.jsp|count.pl|count.php|counter.cgi|counter.js|counter.pl|countlink.cgi|fpcount.exe|logitpro.cgi|rcounter.dll|track.pl|w_counter.js)";
		
		$prefix="INSERT IGNORE INTO regex_urls (zmd5,zDate,category,pattern,enabled) VALUES ";
		while (list ($category, $array) = each ($f) ){
			while (list ($index, $pattern) = each ($array) ){
				$md5=md5("$category$pattern");
				$date=date('Y-m-d H:i:s');
				$s[]="('$md5','$date','$category','$pattern',0)";
				
			}
			
		}
		if(count($s)>0){$this->QUERY_SQL($prefix.@implode(",", $s));$s=array();}	
		
		
	}
	
	function CreateMemberReportBlockTable($tablename=null){
		if($tablename==null){return;}
		
			
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `zDate` date NOT NULL ,
			  `hits` INT UNSIGNED NOT NULL ,
			  `ipaddr` varchar(90) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `website` varchar(125) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `rulename` varchar(50) NOT NULL,
			  `event` varchar(20) NOT NULL,
			  `why` varchar(90) NOT NULL,
			  `explain` text NOT NULL,
			  `blocktype` varchar(255) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `zDate` (`zDate`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `MAC` (`MAC`),
			  KEY `account` (`account`),
			  KEY `website` (`website`),
			  KEY `category` (`category`),
			  KEY `rulename` (`rulename`),
			  KEY `uid` (`uid`),
			  KEY `hits` (`hits`),
			  KEY `event` (`event`),
			  KEY `why` (`why`)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql); 
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";return false;
			}	
		return true;
	
	}	
	

	function CreateWeekBlockedTable($week=null){
		if(preg_match("#[0-9]+_blocked_week#", $week)){$tableblock=$week;}
		else{
			if(!is_numeric($week)){
				$week=date('YW');}
				$tableblock="{$week}_blocked_week";
		}
		
		$tableblock=str_replace("_blocked_week_blocked_week", "_blocked_week", $tableblock);
		if(!$this->TABLE_EXISTS($tableblock)){		
			$sql="CREATE TABLE IF NOT EXISTS `$tableblock` (
			  `zMD5` CHAR(32) NOT NULL,
			  `day` smallint(2) NOT NULL ,
			  `hits` INT UNSIGNED NOT NULL ,
			  `client` varchar(90) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `website` varchar(125) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `rulename` varchar(50) NOT NULL,
			  `event` varchar(20) NOT NULL,
			  `why` varchar(90) NOT NULL,
			  `explain` text NOT NULL,
			  `blocktype` varchar(255) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `day` (`day`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `MAC` (`MAC`),
			  KEY `uid` (`uid`),
			  KEY `account` (`account`),
			  KEY `website` (`website`),
			  KEY `category` (`category`),
			  KEY `rulename` (`rulename`),
			  KEY `hits` (`hits`),
			  KEY `event` (`event`),
			  KEY `why` (`why`)
			) ENGINE = MYISAM;"; 
			$this->QUERY_SQL($sql); 
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
			$this->mysql_error=$this->mysql_error."\n$sql";return false;}else{writelogs("Checking $tableblock SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}

		}		
		if(!$this->FIELD_EXISTS("$tableblock", "MAC")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `MAC` VARCHAR( 20 ) NOT NULL ,ADD INDEX ( `MAC` )");}
		return true;
	
	}
	
	function CreateUserSizeRTTTable(){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("UserSizeRTT",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `UserSizeRTT` (
			  `zMD5` CHAR(32) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `zdate` DATETIME NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `hits` INT UNSIGNED NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `uid` (`uid`),
			  KEY `zdate` (`zdate`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `MAC` (`MAC`),
			  KEY `UserAgent` (`UserAgent`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}

		if(!$this->FIELD_EXISTS("UserSizeRTT", "hits")){
			$this->QUERY_SQL("ALTER TABLE `UserSizeRTT` ADD `hits` BIGINT( 100) NOT NULL ,ADD INDEX ( `hits` ) ");
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
	}
	
	

	function CreateUserSizeRTT_day($tablename){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `zdate` DATE NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `hits` INT UNSIGNED NOT NULL,
			  `hour` smallint(4) NOT NULL,
			  KEY `uid` (`uid`),
			  KEY `zdate` (`zdate`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `MAC` (`MAC`),
			  KEY `UserAgent` (`UserAgent`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `hour` (`hour`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}

		return true;
		
	}
	
	function uid_to_tablename($uid){
		  $uid=trim($uid);
		  if(preg_match("#(.+?)\/(.+)#", $uid,$re)){$uid=$re[2];}
		  if(!class_exists("class.html.tools.inc")){include_once(dirname(__FILE__)."/class.html.tools.inc");}
		  $t=new htmltools_inc();
  		  $uid=$t->replace_accents($uid);
  		  $uid=str_replace("$", "", $uid);
  		  $uid=str_replace(" ", "_", $uid);
  		  return $uid;
  		  
	}

	
	
	function CreateHourTable($tablename){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` INT UNSIGNED NOT NULL,
			  `hour` int(2) NOT NULL,
			  `remote_ip` varchar(50) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `country` varchar(50) NOT NULL,
			  `size` BIGINT(200) NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `country` (`country`),
			  KEY `hour` (`hour`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}	

		if(!$this->FIELD_EXISTS("$tablename", "hostname")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		return true;
		
	}
		
	private function checkDNSBLTables(){
		if($this->COUNT_ROWS("webfilter_dnsbl")>0){return;}
		$f=@file_get_contents(dirname(__FILE__)."/databases/db.surbl.txt");
		
		$prefix="INSERT IGNORE INTO webfilter_dnsbl(`dnsbl`,`name`,`uri`,`enabled`) VALUES ";
		if(preg_match_all("#<server>(.+?)</server>#is",$f,$servers)){
			while (list ($num, $line) = each ($servers[0])){
				if(preg_match("#<item>(.+?)</item>#",$line,$re)){$server_uri=$re[1];}
				if(preg_match("#<name>(.+?)</name>#",$line,$re)){$name=$re[1];}
				if(preg_match("#<uri>(.+?)</uri>#",$line,$re)){$info=$re[1];}
				$name=addslashes($name);
				$info=addslashes($info);
				$SQ[]="('$server_uri','$name','$info',0)";
			}
			
		}else{
			writelogs("Unable to preg_match in ".strlen($f)." bytes",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		if(count($SQ)>0){
			$this->QUERY_SQL($prefix.@implode($SQ, ","));
		}else{
			writelogs("Warning unable to found any DNSBL item",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		}	

	function CreateWeekTable($tablename=null){
		if($tablename==null){$tablename=date('YW')."_week";}
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` INT UNSIGNED NOT NULL,
			  `day` int(2) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `size` BIGINT(200) NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `day` (`day`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
				
		

		if(!$this->FIELD_EXISTS("$tablename", "account")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `account` INT UNSIGNED NOT NULL ,ADD INDEX ( `account` )");}
		return true;
		
	}
	
	function CreateMemberReportTable($tablename=null){
		if($tablename==null){return;}
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` INT UNSIGNED NOT NULL,
			  `zDate` date NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `size` BIGINT(200) NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `zDate` (`zDate`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			return true;
		
	}	
	
	function LOG_ADDED_CATZ($category_table,$rownumbers){
		//webfilters_bigcatzlogs
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
		if($rownumbers==0){return;}
		if(!is_numeric($rownumbers)){return ;}
		if($category_table==null){if(function_exists("WriteToSyslog")){WriteToSyslog("Fatal: No category Table set $called", basename(__FILE__));}}
		if($this->TABLE_EXISTS("webfilters_bigcatzlogs")){$this->CheckTables();}
		$categoryname=$this->tablename_tocat($category_table);
		if($categoryname==null){if(function_exists("WriteToSyslog")){WriteToSyslog("Warning: Unable to find category for $categoryname $called", basename(__FILE__));}}
		$sql="INSERT IGNORE INTO webfilters_bigcatzlogs (zDate,category_table,category,AddedItems) 
		VALUES (NOW(),'$category_table','$categoryname','$rownumbers')";
		$this->QUERY_SQL($sql);
		if(!$this->ok){if(function_exists("WriteToSyslogMail")){WriteToSyslogMail(__FUNCTION__."::$q->mysql_error", basename(__FILE__));}return;}
		//if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("$category_table $rownumbers new items", basename(__FILE__));}
		$ID=time();
		$sql="INSERT IGNORE INTO instant_updates (ID,zDate,CountItems) VALUES('$ID',NOW(),'$rownumbers')";
		$this->QUERY_SQL($sql);
		}
	
	
	function CreateVisitedDayTable($tablename=null){
		if($tablename==null){$tablename=date('Ymd')."_visited";}
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `hits` INT UNSIGNED NOT NULL,
			  PRIMARY KEY (`sitename`),
			  KEY `familysite` (`familysite`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
				
		}

		
		return true;
		
	}	
	
	function RepairTableBLock($tableblock){
		if($tableblock==null){return;}
		if(!$this->FIELD_EXISTS("$tableblock", "uri")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `uri` VARCHAR( 255 ) NOT NULL");}
		if(!$this->FIELD_EXISTS("$tableblock", "event")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `event` VARCHAR( 20 ) NOT NULL,ADD INDEX ( `event` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "why")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `why` VARCHAR( 90 ) NOT NULL,ADD INDEX ( `why` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "explain")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `explain` TEXT");}
		if(!$this->FIELD_EXISTS("$tableblock", "blocktype")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `blocktype` VARCHAR( 255 )");}
		if(!$this->FIELD_EXISTS("$tableblock", "hostname")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "account")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `account` INT UNSIGNED NOT NULL ,ADD INDEX ( `account` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "uid")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `uid` VARCHAR(90) NOT NULL ,ADD INDEX ( `uid` )");}
				
		
	}

	

	
	function CreateFamilyDayTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zMD5` CHAR(32) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`zMD5`),
		KEY `size` (`size`),
	    KEY `hits` (`hits`)
		) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;
	}
	function CreateFamilyCondensed(){
		$sql="CREATE TABLE IF NOT EXISTS `FamilyCondensed` (
		`zMD5` CHAR(32) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`zDate` DATE,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`zMD5`),
		KEY `size` (`size`),
		KEY `zDate` (`zDate`),
		KEY `familysite` (`familysite`),
		KEY `hits` (`hits`)
		) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;
	}	
	
	function CreateFamilyMainTable(){
		$sql="CREATE TABLE IF NOT EXISTS `main_websites` (
		`familysite` varchar(128) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`familysite`),
		KEY `size` (`size`),
		KEY `hits` (`hits`)
		) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;		
		
	}
	
	
	function CreateMonthTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			
			  `account` INT UNSIGNED NOT NULL,
			  `day` int(2) NOT NULL,
			  `remote_ip` varchar(50) NOT NULL,
			  `country` varchar(50) NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `hits` BIGINT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `client` (`client`),	
			  KEY `account` (`account`),
			  KEY `country` (`country`),
			  KEY `day` (`day`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;
		
	}	
	function CreateYearTable($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zMD5` CHAR(32) NOT NULL,
		
		`familysite` varchar(128) NOT NULL,
		`client` varchar(50) NOT NULL,
		`hostname` varchar(120) NOT NULL,
		`account` INT UNSIGNED NOT NULL,
		`month` int(2) NOT NULL,
		`remote_ip` varchar(50) NOT NULL,
		`country` varchar(50) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`uid` varchar(90) NOT NULL,
		`category` varchar(50) NOT NULL,
		`MAC` varchar(20) NOT NULL,
		`cached` smallint(1) NOT NULL,
		PRIMARY KEY (`zMD5`),
		
		KEY `client` (`client`),
		KEY `hostname` (`hostname`),
		KEY `account` (`account`),
		KEY `country` (`country`),
		KEY `month` (`month`),
		KEY `category` (`category`),
		KEY `size` (`size`),
		KEY `hits` (`hits`),
		KEY `uid` (`uid`),
		KEY `MAC` (`MAC`),
		KEY `familysite` (`familysite`),
		KEY `cached` (`cached`)
		) ENGINE = MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;
	
	}
		
	function CreateMembersDayTable($tablename=null){
		if($tablename==null){$tablename=date("Ymd")."_members";}
		
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` INT UNSIGNED NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `hour` smallint(2) NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `hits` INT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `hour` (`hour`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}			
		return true;
		
	}	
	
	function CreateUsersDayTable($tablename=null){
		if($tablename==null){$tablename=date("Ymd")."_users";}
	
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			`zMD5` CHAR(32) NOT NULL,
			`client` varchar(50) NOT NULL,
			`hostname` varchar(120) NOT NULL,
			`MAC` varchar(20) NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			`uid` varchar(90) NOT NULL,
			PRIMARY KEY (`zMD5`),
			KEY `client` (`client`),
			KEY `hostname` (`hostname`),
			KEY `size` (`size`),
			KEY `hits` (`hits`),
			KEY `uid` (`uid`),
			KEY `MAC` (`MAC`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			}
			return true;
	
			}	
			
		function CreateUsersTempTable($tablename=null){
				
			
				if(!$this->TABLE_EXISTS("UsersTMP",$this->database)){
					$sql="CREATE TABLE IF NOT EXISTS `UsersTMP` (
					`zMD5` CHAR(32) NOT NULL,
					`zDate` DATE NOT NULL,
					`client` varchar(50) NOT NULL,
					`hostname` varchar(120) NOT NULL,
					`MAC` varchar(20) NOT NULL,
					`size` INT UNSIGNED NOT NULL,
					`hits` INT UNSIGNED NOT NULL,
					`uid` varchar(90) NOT NULL,
					PRIMARY KEY (`zMD5`),
					KEY `client` (`client`),
					KEY `zDate` (`zDate`),
					KEY `hostname` (`hostname`),
					KEY `size` (`size`),
					KEY `hits` (`hits`),
					KEY `uid` (`uid`),
					KEY `MAC` (`MAC`)
					) ENGINE = MEMORY;";
					$this->QUERY_SQL($sql,$this->database);
					if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
					}
					return true;
			
				}	


		function CreateUsersFullTable(){
			if(!$this->TABLE_EXISTS("UsersToTal",$this->database)){
			$sql="CREATE TABLE IF NOT EXISTS `UsersToTal` (
				`zMD5` CHAR(32) NOT NULL,
				`client` varchar(50) NOT NULL,
				`hostname` varchar(120) NOT NULL,
				`MAC` varchar(20) NOT NULL,
				`size` INT UNSIGNED NOT NULL,
				`hits` INT UNSIGNED NOT NULL,
				`uid` varchar(90) NOT NULL,
				PRIMARY KEY (`zMD5`),
				KEY `client` (`client`),
				KEY `hostname` (`hostname`),
				KEY `size` (`size`),
				KEY `hits` (`hits`),
				KEY `uid` (`uid`),
				KEY `MAC` (`MAC`)
				) ENGINE = MYISAM;";
				$this->QUERY_SQL($sql,$this->database);
				if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
				}
				return true;
		}				
			
			
	
	function CreateMembersMonthTable($tablename=null){
		if($tablename==null){$tablename=date("Ym")."_members";}
		
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` CHAR(32) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `day` int(2) NOT NULL,
			  `size` INT UNSIGNED NOT NULL,
			  `hits` INT UNSIGNED NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` INT UNSIGNED NOT NULL,
			  `cached` smallint(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `day` (`day`),
			  KEY `client` (`client`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}			
		return true;
		
	}
	
	function CreateMembersYearTable(){
		$tablename="year_members";
	
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			`zMD5` CHAR(32) NOT NULL,
			`client` varchar(50) NOT NULL,
			`month` int(2) NOT NULL,
			`Year` int(4) NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			`hits` INT UNSIGNED NOT NULL,
			`uid` varchar(90) NOT NULL,
			`MAC` varchar(20) NOT NULL,
			`hostname` varchar(120) NOT NULL,
			`account` INT UNSIGNED NOT NULL,
			`cached` smallint(1) NOT NULL,
			PRIMARY KEY (`zMD5`),
			KEY `month` (`month`),
			KEY `Year` (`Year`),
			KEY `client` (`client`),
			KEY `size` (`size`),
			KEY `hits` (`hits`),
			KEY `uid` (`uid`),
			KEY `MAC` (`MAC`),
			KEY `hostname` (`hostname`),
			KEY `account` (`account`),
			KEY `cached` (`cached`)
			) ENGINE = MYISAM;";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			}
			return true;
	
			}
	


	
	public function GET_THUMBNAIL($sitename,$width){
		$sitename=trim(strtolower($sitename));
		return "
		<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.statistics.php?thumbnail-zoom-js=$sitename');\">
		<img src='/squid.statistics.php?thumbnail=$sitename&width=$width' class=img-polaroid></a>";
	}
	
	
	public function WEEK_TITLE($weekNumber, $year){
		if(strlen($weekNumber)==1){$weekNumber="0$weekNumber";}
		$xtime= strtotime("{$weekNumber}W{$year}");
		$t1=date("{l} d {F}",$xtime);
		$t2 = date("{l} d {F}",strtotime(date("Y-m-d",$xtime)." 00:00:00 + 7 days"));
		return "{week}:&nbsp;&nbsp;{from}&nbsp;$t1&nbsp;{to}&nbsp;$t2 $year";
	}
	
	public function WEEK_TITLE_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			return $this->WEEK_TITLE($Cweek,$Cyear);
	}
	
	public function WEEK_HASHTIME_FROM_TABLENAME($tablename){
			return $this->WEEK_TOTIMEHASH_FROM_TABLENAME($tablename);
			
	}
	
	public function WEEK_NUMBER($time){
		$ddate = date("Y-m-d",$time);
		$duedt = explode("-",$ddate);
		$date = mktime(0, 0, 0, $duedt[2], $duedt[1],$duedt[0]);
		$week = (int)date('W', $date);
		return $week;
		
	}
	
	public function WEEK_TABLE_BLOCKED_CURRENT(){
		$sql="SELECT WEEK( NOW() ) AS tweek, YEAR( NOW() ) AS tyear";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return "{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
		
	}
	
	
	
	public function WEEK_TOTIMEHASH_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			$xtime= strtotime("{$Cweek}W{$Cyear}");
			return $xtime;
			
	}	
	
	
	public function TIME_FROM_WEEK_TABLE($tablename){
		return $this->WEEK_HASHTIME_FROM_TABLENAME($tablename);
		
	}
	
	
	
	public function WEEK_TIME_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			return strtotime("{$Cyear}W{$Cweek}");	
			
	}	
	
	public function MONTH_TITLE_FROM_TABLENAME($tablename){
		$date=$this->TIME_FROM_MONTH_TABLE($tablename);
		return date("{F} Y",$date);
		
	}
	
	public function TIME_FROM_MONTH_TABLE($tablename){
		$Cyear=substr($tablename, 0,4);
		$month=substr($tablename,4,2);
		$month=str_replace("_", "", $month);
		$dayfull="01-$month-$Cyear 00:00:00";
		return strtotime($dayfull);
	}	
	
	public function TIME_FROM_DAY_TABLE($tablename){
		$Cyear=substr($tablename, 0,4);
		$CMonth=substr($tablename,4,2);
		$CDay=substr($tablename,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	

	public function TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename){
		preg_match("#dansguardian_events_([0-9]+)#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	
	public function TIME_FROM_HOUR_TABLE($tablename){
		preg_match("#([0-9]+)_[a-z]+$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}
	public function TIME_FROM_SEARCHHOUR_TABLE($tablename){
		preg_match("#^searchwords_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}	
	
	
	
	public function TIME_FROM_QUOTAHOUR_TABLE($tablename){
		preg_match("#^quotahours_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}
	
	public function TIME_FROM_QUOTATEMP_TABLE($tablename){
		preg_match("#^quotatemp_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}	
	public function TIME_FROM_RTTH_TABLE($tablename){
		preg_match("#^RTTH_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}	
	public function TIME_FROM_QUOTAMONTH_TABLE($tablename){
		preg_match("#^quotamonth_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CMonth=str_replace("_", "", $CMonth);
		return strtotime("$Cyear-$CMonth-01 00:00:00");
	}	

	
	public function TIME_FROM_HOUR_TEMP_TABLE($tablename){
		preg_match("#[a-z]+_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}	

	public function TIME_FROM_YOUTUBE_DAY_TABLE($tablename){
		preg_match("#youtubeday_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}
public function TIME_FROM_YOUTUBE_HOUR_TABLE($tablename){
	preg_match("#youtubehours_([0-9]+)$#", $tablename,$re);
	$intval=$re[1];
	$Cyear=substr($intval, 0,4);
	$CMonth=substr($intval,4,2);
	$CDay=substr($intval,6,2);
	$CDay=str_replace("_", "", $CDay);
	$CHour=substr($intval,8,2);
	return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
}

public function MacToUid($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);

}
public function IpToUid($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);
	
}
public function MacToHost($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]);
}
public function IpToHost($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]);
}	
	public function TIME_FROM_USERSIZED_TABLE($tablename){
		preg_match("#UserSizeD_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	
	public function TIME_FROM_QUOTADAY_TABLE($tablename){
		preg_match("#quotaday_([0-9]+)$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	public function TIME_FROM_CAT_FAMDAY_TABLE($tablename){
		preg_match("#([0-9]+)_catfam$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	public function TIME_FROM_PREFIX_GENERIC($tablename){
		preg_match("#([0-9]+)_[a-z]+$#", $tablename,$re);
		$intval=$re[1];
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	
	public function WEEK_TABLE_TO_MONTH($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			$tt=$this->getDaysInWeek($Cweek,$Cyear);
			foreach ($tt as $dayTime) {$f[intval(date("d", $dayTime))]=date("{l} d {F} Y", $dayTime);}
			return $f;
	}	

	public function DAY_TITLE_FROM_TABLENAME($tablename){
		$time=$this->TIME_FROM_DAY_TABLE($tablename);
		return date("{l} d {F} Y",$time);
	}
	
	public function DAY_TABLENAME_TO_TIME($tablename){
		return $this->TIME_FROM_DAY_TABLE($tablename);
	}	
		
		
	public function getDaysInWeek ($weekNumber, $year) {
	  if(isset($GLOBALS["getDaysInWeek$weekNumber$year"])){return $GLOBALS["getDaysInWeek$weekNumber$year"];}
	  $sql="SELECT zDate,WeekDay FROM tables_day WHERE WeekNum=$weekNumber AND YEAR(zDate)='$year'";
	  $q=new mysql_squid_builder();
	  $results=$q->QUERY_SQL($sql);	
	  	if(preg_match("#Unknown column#i", $q->mysql_error)){
	  		if(!$q->FIELD_EXISTS("tables_day", "WeekDay")){
	  			$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `WeekDay` SMALLINT( 2 ) NOT NULL,ADD INDEX ( `WeekDay`)");
	  			$sock=new sockets();
	  			$sock->GET_INFO("squid.php?weekdaynum=yes");
	  		}
	  		
	  		if(!$q->FIELD_EXISTS("tables_day", "WeekNum")){
	  			$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `WeekNum` SMALLINT( 2 ) NOT NULL,ADD INDEX ( `WeekNum`)");
	  			$sock=new sockets();
	  			$sock->GET_INFO("squid.php?weekdaynum=yes");	  			
	  		}	  		
	  		$results=$q->QUERY_SQL($sql);	
	  	}
		
	  
	  
	  if(!$q->ok){echo $q->mysql_error;return;}
	  while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	  	$time=strtotime($ligne["zDate"]." 00:00:00");
	  	$dayTimes[$ligne["WeekDay"]]=$time;
	  }
	  $GLOBALS["getDaysInWeek$weekNumber$year"]=$dayTimes;
	  return $dayTimes;
	}

	private function cloudlogs($text=null){
		if($GLOBALS["VERBOSE"]){echo "$text<br>\n";}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$logFile="/var/log/cleancloud.log";
		$time=date("Y-m-d H:i:s");
		$PID=getmypid();
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {
			$size=filesize($logFile);
			if($size>1000000){unlink($logFile);}
		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$time [$PID]:class.mysql.squid.builder: $text\n");
		@fclose($f);
	}
	
}
function writelogs_squid($text,$function=null,$file=null,$line=0,$category=null,$nosql=false){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=@getmypid();}
	if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	if($file<>null){$me=basename($file);}else{$me=basename(__FILE__);}
	$date=@date("H:i:s");
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];}
	}
	
	if($function==__FUNCTION__){$function=null;}
	if($function==null){$function=$sourcefunction;}
	if($line==0){$line=$sourceline;}
	if($file==null){$line=$sourcefile;}
	
	if(function_exists("stats_admin_events")){
		stats_admin_events(2,$text,"$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function",$sourcefile,$sourceline);
	}
	
	if($GLOBALS["AS_ROOT"]){
		
		$logFile="/var/log/artica-squid-stats.log";
		if(is_file($logFile)){
			$size=filesize($logFile);
			if($size>5000000){unlink($logFile);}
		}
		
		$f = fopen($logFile, 'a');
		fwrite($f, "$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function::$line: $text\n");
		fclose($f);
	}
	if($nosql){return;}
	if(function_exists("ufdbguard_admin_events")){
		ufdbguard_admin_events($text, $function, $file, $line, $category);
	}
}
	
function TITLE_SQUID_STATSTABLE($sql,$title,$TimeType="week"){
	$queryjs=base64_encode($sql);
	$titlejs=base64_encode($title);
	$title_style="font-size:15px;width:100%;font-weight:bold;text-decoration:underline;margin-bottom:10px";
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$span=md5("$sql");
	if(isset($_GET["day"])){$day=$_GET["day"];}
	if($day==null){if(isset($_GET["week"])){$day=$_GET["week"];}}
	$title0="<div style='$title_style;' $mouse OnClick=\"javascript:Loadjs('squid.statistics.querytable.php?query=$queryjs&title=$titlejs&span=$span&TimeType=$TimeType&day=$day')\">$title</div>
	<span id='$span'></span>
	";
	return $title0;
}

function squidstatsApplianceEvents($host,$text){
	$q=new mysql_squid_builder();
	$text=addslashes($text);
	if($GLOBALS["VERBOSE"]){echo "$host:: $text\n";}
	$sql="INSERT INTO stats_appliance_events(`hostname`,`events`) VALUES ('$host','$text')";
	$q->QUERY_SQL($sql);
	
}

function categorize_tables_events($subject,$text,$tablename,$finish=0){
	$time=date("Y-m-d H:i:s");
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];}
		}
	$line="{by} $sourcefile {function} $sourcefunction {line} $sourceline\n";
	$taskid=$GLOBALS["SCHEDULE_ID"];
	$line="$line task:$taskid";
	
	$q=new mysql_squid_builder();
	$subject=mysql_escape_string2($subject);
	$text=mysql_escape_string2($text)."<div><i>$line</i></div>";
	$sql="INSERT IGNORE INTO notcategorized_events 
	(tablename,zDate,subject,finished,description)
	VALUES('$tablename','$time','$subject','$finish','$text')";
	if(!$q->TABLE_EXISTS("notcategorized_events")){
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `notcategorized_events` (
				   `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					tablename VARCHAR(128) NOT NULL,
					zDate datetime NOT NULL ,
					subject VARCHAR(255) NOT NULL,
					finished smallint(1) NOT NULL DEFAULT '0' ,
					description TEXT NOT NULL,
				  KEY `tablename` (`tablename`),
				  KEY `zDate` (`zDate`),
				  KEY `finished` (`finished`)
				)  ENGINE = MYISAM;");
	}
	
	
	$q->QUERY_SQL($sql);
	
}





class webfilter_rules{
	
	function rules_dans_time_rule($RULEID){
		
		
		$q=new mysql_squid_builder();
		$tpl=new templates();
		$sql="SELECT * FROM webfilters_dtimes_rules WHERE ruleid='$RULEID' and enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}
		$text="<table style='width:100%'><tbody>";
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne['TimeName']=utf8_encode($ligne['TimeName']);
			$TimeSpace=unserialize($ligne["TimeCode"]);
			
			
			
			
			$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
			$f=array();
			while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){
				if($num==array()){continue;}
				if(!isset($days[$num])){continue;}
				if($days[$num]==array()){continue;}
				if($val<>1){continue;}
				$f[]= "{{$days[$num]}}";
			}
	
	
			if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
			if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
			if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
			if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}
	
	
			$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks
					WHERE webfilter_id={$ligne["ID"]} AND modeblk=0"));
			$blacklist=$ligneTOT["tcount"];
	
			$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks
					WHERE webfilter_id={$ligne["ID"]} AND modeblk=1"));
			$whitelist=$ligneTOT["tcount"];
	
	
	
			$text=$text."<tr style='background-color:transparent'>
			<td width=1%><img src='img/clock_24.png'></td>
			<td width=99%><div style='font-size:11px'>
			<strong>{$ligne['TimeName']}</strong>: {from} {$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]} {to} {$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]} (".@implode(", ", $f).")
			<div><i>{blacklist}:<b>$blacklist</b> {whitelist}:<b>$whitelist</b></div>
			</td>
			</tR>";
	
	
	
		}
	
		$text=$text."</tbody></table>";
	return $text;
	}
	
	public function rule_time_list_from_ruleid($ID,$t){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_dtimes_rules WHERE ruleid='$ID' and enabled=1";
		if($GLOBALS["VERBOSE"]){echo "<HR>$sql<br>\n";}
		$results = $q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){
			if($GLOBALS["VERBOSE"]){echo "<HR>Nothing<br>\n";}
			return;}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne['TimeName']=utf8_encode($ligne['TimeName']);
			if($GLOBALS["VERBOSE"]){echo "<HR>{$ligne['TimeName']}:TimeCode<br>\n";}
			
			$f[]=$this->rule_time_list_explain($ligne["TimeCode"],$ID,$t);
		}
		if(count($f)>0){
			return @implode("",$f);
		}
		
	}
	
	
	public function rule_time_list_explain($TimeSpace,$ID,$t){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$TimeSpace=unserialize(base64_decode($TimeSpace));
		if(!is_array($TimeSpace)){if($GLOBALS["VERBOSE"]){echo "<HR>$ID not an array\n";}}
		if($GLOBALS["VERBOSE"]){echo "<HR>\n";print_r($TimeSpace);}
		
		$tpl=new templates();
		if(!is_array($TimeSpace)){return null;}
		if(count($TimeSpace["TIMES"])==0){return null;}
		
		if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
		if($TimeSpace["RuleMatchTime"]=="none"){
			return $tpl->_ENGINE_parse_body("<br><strong style='color:#B81515'>{time}:{no_position_set} !</strong><br>");
		}
		
		$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
		$rule_text=$tpl->javascript_parse_text("{rule}");
	
		while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
	
			$dd=array();
			if(is_array($array["DAYS"])){
				while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]="{{$daysARR[$day]}}";}}
				$daysText=@implode(", ", $dd);
			}
			if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
			if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
			if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
			if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
			$daysText=$daysText.$tpl->javascript_parse_text("<br>{from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]}",1);
			$daysText=str_replace("\\n\\n","<br>",$daysText);
	
	
			$href="<a href=\"javascript:blur()\"
			OnClick=\"javascript:YahooWin5(550,'dansguardian2.edit.php?rule-time-ID=yes&TIMEID=$TIMEID&ID=$ID&t=$t','$rule_text:$TIMEID');\"
			style='font-size:14px;text-decoration:underline'>";
	
	
			$textfinal=$tpl->javascript_parse_text("{each} $daysText");
	
	
			$FINAL[]="<span style='font-size:14px'>$href<i>$textfinal</i></a></span>";
		}
		if(count($FINAL)>0){return @implode("<br>", $FINAL);}
		//rule_time_list_explain($ligne["TimeSpace"]);
	
	}	
	
	function COUNTDEGROUPES($ruleid){
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT(ID) as tcount FROM webfilter_assoc_groups WHERE webfilter_id='$ruleid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
		return $ligne["tcount"];
	}
	
	function COUNTDEGBLKS($ruleid){
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=0" ;
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
		$C=$ligne["tcount"];
		$C=$C+$this->COUNTDEGBLKS_GROUPS($ruleid,0);
		return $C;
	}
	
	function COUNTDEGBLKS_GROUPS($ruleid,$modeblk){
		$q=new mysql_squid_builder();
		$sql="SELECT webfilter_blkid FROM webfilter_blklnk WHERE webfilter_ruleid=$ruleid AND blacklist=$modeblk";
		$results=$q->QUERY_SQL($sql);
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		$Count=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$groupid=$ligne["webfilter_blkid"];
			
			
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilter_blkgp WHERE ID=$groupid"));
			
			
			if($ligne2["enabled"]==0){continue;}
			
			$sql="SELECT COUNT(`category`) AS tcount FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
			
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			if(!is_numeric($ligne2["tcount"])){continue;}
			
			$Count=$Count+$ligne2["tcount"];
			
		}
		return $Count;		
		
	}
	
	
	function COUNTDEGBWLS($ruleid){
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
		$C=$ligne["tcount"];
		$C=$C+$this->COUNTDEGBLKS_GROUPS($ruleid,1);
		return $C;
	}

	function TimeToText($TimeSpace){
		$RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
		if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
		if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
		if($TimeSpace["RuleMatchTime"]=="none"){return;}
		$q=new mysql_squid_builder();
	
		$RULESS["none"]="{none}";
		$RULESS[0]="{default}";
		$sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$RULESS[$ligne["ID"]]=$ligne["groupname"];}
	
	
		$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
		while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$dd=array();
			if(!is_array($array["DAYS"])){return;}
	
			while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]="{{$daysARR[$day]}}";}}
			$daysText=@implode(", ", $dd);
	
			if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
			if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
			if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
			if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
	
			$f[]="<div style='font-weight:normal'>{$RuleBH[$TimeSpace["RuleMatchTime"]]} $daysText {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]} {then}
		 {alternate_rule} {to} {$RULESS[$TimeSpace["RuleAlternate"]]}</div>";
	
		}
	
	
		return @implode("\n", $f);
	
	
	}	
	
}
function CategoriesCheckRightsRead(){
	$users=new usersMenus();
	if($users->AsDansGuardianAdministrator){return true;}
	$q=new mysql_squid_builder();
	$sql="SELECT PublicMode FROM personal_categories WHERE category='{$_REQUEST["category"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));

	if(!$q->ok){
		echo $q->mysql_error_html();
		return;
	}

	$GLOBALS["CategoriesCheckRights"][]="{$_REQUEST["category"]}: Public Mode: {$ligne["PublicMode"]}";
	if($ligne["PublicMode"]==1){return true;}
	$CategoriesCheckPerms=CategoriesCheckPerms();
	if($CategoriesCheckPerms[$_REQUEST["category"]]==1){return true;}
	return false;
}
function CategoriesCheckRightsWrite(){
	$users=new usersMenus();
	if($users->AsDansGuardianAdministrator){return true;}
	$CategoriesCheckPerms=CategoriesCheckPerms();
	if($CategoriesCheckPerms[$_REQUEST["category"]]==1){return true;}
}
function lkdfjozif_uehfe(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->CORP_LICENSE){
	define("kdfjozif", "<p class=text-error>".$tpl->_ENGINE_parse_body("{ERROR_NO_LICENSE}")."</p>");}
	
}
function CategoriesCheckPerms($nocache=false){
	if(!$nocache){if(isset($_SESSION["ProxyCategoriesPermissions"])){return $_SESSION["ProxyCategoriesPermissions"];}}
	$_SESSION["ProxyCategoriesPermissions"]=array();
	


	$ldap=new clladp();

	$ARRAYPERS=CategoriesCheckGroupsArray();
	$q=new mysql_squid_builder();
	$sql="SELECT *  FROM `webfilter_catprivs`";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=$ligne["zmd5"];
		$categorykey=$ligne["categorykey"];
		$groupdata=$ligne["groupdata"];
		preg_match("#^@(.+?):(.+)#", $groupdata,$re);
		$GroupName=$re[1];
		$gpdata=strtolower(trim(base64_decode($re[2])));
		$allowrecompile=$ligne["allowrecompile"];
		if(!isset($ARRAYPERS[$gpdata])){continue;}
		$_SESSION["ProxyCategoriesPermissions"][$categorykey]=$allowrecompile;
	}

	return $_SESSION["ProxyCategoriesPermissions"];

}
function CategoriesCheckGroupsArray(){

	if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
	$ldap=new clladp();
	$ARRAYPERS=array();
	if($ldap->IsKerbAuth()){
		include_once(dirname(__FILE__)."/class.external.ad.inc");
		$ad=new external_ad_search();
		$groups=$ad->GroupsOfMember($_SESSION["uid"]);
		if(!is_array($groups)){$groups=array();}
		while (list ($dn, $name) = each ($groups) ){$ARRAYPERS[strtolower($dn)]=true;}

	}else{
		$users = new user ( $_SESSION["uid"]);
		$groups = $users->Groups_list();
		if(!is_array($groups)){$groups=array();}
		while (list ($gid, $name) = each ($groups) ){$ARRAYPERS[$gid]=true;}
	}

	$GLOBALS[__CLASS__.__FUNCTION__]=$ARRAYPERS;
	return $ARRAYPERS;

}

