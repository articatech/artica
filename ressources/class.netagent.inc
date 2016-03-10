<?php
define('PROCESS1_BIN',   "/usr/share/artica-postfix/bin/process1");
include_once(dirname(__FILE__)."/class.ccurl.inc");

class netagent{
	var $master_uri=null;
	var $nodeid=0;
	
	function netagent(){
		if(!isset($GLOBALS["CLASS_SOCKET"])){$GLOBALS["CLASS_SOCKET"]=new sockets();}
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKET"]->GET_INFO("RemoteStatisticsApplianceSettings")));
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$proto="http://";
		if($RemoteStatisticsApplianceSettings["SSL"]==1){$proto="https://";}
		$this->master_uri="$proto{$RemoteStatisticsApplianceSettings["SERVER"]}:{$RemoteStatisticsApplianceSettings["PORT"]}";	
		$this->nodeid=$GLOBALS["CLASS_SOCKET"]->GET_INFO("NODE_ID");
		
	}
	
	function ping($norestart=false){
		if($this->nodeid<1){
			if(!$norestart){$this->_register();}
		}
		$hostid=$this->hostid();
		if($GLOBALS["OUTPUT"]){echo "Send status to the remote console with node id:$this->nodeid AND hostid=$hostid...\n";}
		$this->sendstatus();
		
		
	}
	
	
	
	private function _register(){
		if($GLOBALS["OUTPUT"]){echo "Register to $this->master_uri\n";}
		$this->events("[$this->nodeid]: master_uri `$this->master_uri`",__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		if(is_numeric($this->nodeid) && $this->nodeid>0){
			$curl->parms["nodeid"]=$this->nodeid;
		}
		$hostid=$this->hostid();
		if($GLOBALS["OUTPUT"]){echo "Register to $this->master_uri with hostid `$hostid`\n";}
		$curl->parms["REGISTER"]="yes";
		$curl->parms["nets"]=$this->GetNets();
		$curl->parms["hostname"]=exec("hostname -f");
		$curl->parms["port"]=0;
		$curl->parms["hostid"]=$this->hostid();
		$curl->parms["ISARTICA"]=1;
		
		if($GLOBALS["VERBOSE"]){
			$curl->parms["VERBOSE"]=1;
		}
		
		if(!$curl->get()){
			if($GLOBALS["OUTPUT"]){echo "Unable to register with Fatal error: $curl->error\n";}
			$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}
		if(preg_match("#<ERROR>(.+?)</ERROR>#is", $curl->data,$re)){
			echo "Fatal ERROR ".$re[1]."\n";
			return;
		}
		if(preg_match("#<SUCCESS>([0-9]+)</SUCCESS>#is", $curl->data,$re)){
			if($GLOBALS["OUTPUT"]){echo "SUCCESS NODE ID ".$re[1]."\n";}
			$this->events("SUCCESS NODE ID ".$re[1],__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_SOCKET"]->SET_INFO("NODE_ID",$re[1]);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/NODE_ID", $re[1]);
			$this->ping(true);
		}
		
	}
	
	private function hostname(){$unix=new unix();return exec($unix->find_program("hostname"));}
	
	public function Architecture(){
		$uname=$GLOBALS["CLASS_UNIX"]->find_program("uname");
		exec("$uname -m 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#i[0-9]86#", $val)){return 32;}
			if(preg_match("#x86_64#", $val)){return 64;}
		}
	}
	
	
	public function compress($source,$dest){
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}	

	
	
	private function perfs($CPU_NUMBER){
		if(!class_exists("os_system")){include_once(dirname(__FILE__)."/class.os.system.tools.inc");}
		$mem=new os_system();
		$cpunum=intval($CPU_NUMBER);
		$array_load=sys_getloadavg();
		$org_load=$array_load[2];
		$load=intval($org_load);
		$max_vert_fonce=$cpunum;
		$max_vert_tfonce=$cpunum+1;
		$max_orange=$cpunum*0.75;
		$max_over=$cpunum*2;
		$purc1=$load/$cpunum;
		$pourc=round($purc1*100,2);
		if($pourc>100){$pourc=100;}
		$color="#5DD13D";
		if($load>=$max_orange){$color="#F59C44";}
		if($load>$max_vert_fonce){$color="#C5792D";}
		if($load>$max_vert_tfonce){$color="#83501F";}	
		if($load>=$max_over){$color="#640000";}
		$array["LOAD_POURC"]=$pourc;
		$array["LOAD_COLOR"]=$color;
		$array["MEMORY"]=$mem->memory();
		$array["REALMEM"]=$mem->realMemory();
		$this->events("[$this->nodeid]: Load:$pourc color:$color: memory:{$array["MEMORY"]} REALMEM:{$array["REALMEM"]}",__FUNCTION__,__FILE__,__LINE__);
		return base64_encode(serialize($array));
	}	
	
	
	private function sendstatus(){
		if($GLOBALS["OUTPUT"]){echo "Compiling informations...\n";}
		include(dirname(__FILE__)."/settings.inc");
		$sock=new sockets();
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
		if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
		$hostname=$this->hostname();
		$rm=$GLOBALS["CLASS_UNIX"]->find_program("rm");
		$values=base64_encode(serialize($_GLOBAL));
		$hostid=$this->hostid();
		
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curlparms["SETTINGS_PARAMS"]=$values;
		$curlparms["nodeid"]=$this->nodeid;
		$curlparms["VERSION"]=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
		$curlparms["perfs"]=$this->perfs($_GLOBAL["CPU_NUMBER"]);
		$curlparms["TOTAL_MEMORY_MB"]=$unix->TOTAL_MEMORY_MB();
		if($GLOBALS["VERBOSE"]){echo "48% Memory: {$curlparms["TOTAL_MEMORY_MB"]}\n";}
		$curlparms["softwares"]=$this->SoftwaresStats();
		$curlparms["nets"]=$this->GetNets();
		$curlparms["hostname"]=$hostname;
		$curlparms["prodstatus"]=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini"));
		if($GLOBALS["VERBOSE"]){echo "50% squid_caches_info\n";}
		$curlparms["squid_caches_info"]=$this->squid_caches_infos();
		$curlparms["squid_system_info"]=$this->squid_system_info();
		$curlparms["disks_list"]=base64_encode(serialize($this->GetDiskList()));
		$curlparms["hostid"]=$hostid;
		if($GLOBALS["VERBOSE"]){echo "55% cache_logs\n";}
		$curlparms["CACHE_LOGS"]=$this->cache_logs();
		if($GLOBALS["VERBOSE"]){echo "56% UFDBCLIENT_LOGS\n";}
		$curlparms["UFDBCLIENT_LOGS"]=$this->ufdbclientlog();
		if($GLOBALS["VERBOSE"]){echo "57% SQUIDVER\n";}
		$curlparms["SQUIDVER"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
		$curlparms["ARCH"]=$this->Architecture();
		$curlparms["ISARTICA"]=1;
		$curlparms["ArticaHttpsPort"]=$ArticaHttpsPort;
		$curlparms["usessl"]=$ArticaHttpUseSSL;
		if($GLOBALS["VERBOSE"]){echo "57% YOREL\n";}
		$curlparms["YOREL"]=$this->BuilYorelGraphs();
		
		$SMP_STATUS=array();
		if($GLOBALS["OUTPUT"]){echo "[$hostid]::$php /usr/share/artica-postfix/exec.squid.smp.php --status 2>&1\n";}
		exec("$php /usr/share/artica-postfix/exec.squid.smp.php --status 2>&1",$SMP_STATUS);
		$SMP_STATUS_CONTENT=base64_encode(serialize($SMP_STATUS));
		if(strlen($SMP_STATUS_CONTENT)>10){$curlparms["SQUID_SMP_STATUS"]=$SMP_STATUS_CONTENT;}
		$SMP_STATUS_CONTENT=null;unset($SMP_STATUS);
		
		$curlparms["BOOSTER_SMP_STATUS"]=$sock->getFrameWork("squid.php?smp-booster-status=yes");
		
		if($GLOBALS["VERBOSE"]){
			if($GLOBALS["OUTPUT"]){echo "[$hostid]::Asking to the statistic appliance to be in verbose mode\n";}
			$curlparms["VERBOSE"]=1;
		}
		
		
		$curlparms["ISARTICA"]=1;
		$curlparms["PARMS"]=$this->parse_daemon_settings();
		
		if(is_file("/etc/squid3/squid.conf")){
			$curlparms["ETC_SQUID_CONF"]=base64_encode(@file_get_contents("/etc/squid3/squid.conf"));
		}
		
		
		@file_put_contents(dirname(__FILE__)."/com.txt", base64_encode(serialize($curlparms)));
		if($GLOBALS["OUTPUT"]){echo "Compressing informations...\n";}
		$this->compress(dirname(__FILE__)."/com.txt",dirname(__FILE__)."/com.txt.gz");
		$this->events("[$this->nodeid]: ". count($_GLOBAL)." items Softs:".round(strlen($curlparms["softwares"])/1024) ."Ko",__FUNCTION__,__FILE__,__LINE__);
		$curl->Timeout=120;
		
		if($GLOBALS["OUTPUT"]){
			echo "Posting informations to nodes.listener.php...\n";
			$unix=new unix();
			$fsize=$unix->file_size_human(dirname(__FILE__)."/com.txt.gz");
			echo "Posting com.txt.gz $fsize...\n";
		}
			
		if($GLOBALS["OUTPUT"]){
			
			$curl->DebugProgress=true;
		}
		
		
		if(!$curl->postFile("SETTINGS_INC",dirname(__FILE__)."/com.txt.gz",array("HOSTNAME"=>$hostname,"nodeid"=>$this->nodeid,"hostid"=>$hostid))){
			if($GLOBALS["OUTPUT"]){echo "Posting informations Failed $curl->error...\n";}
			$this->events("[$this->nodeid]: Failed $curl->error",__FUNCTION__,__FILE__,__LINE__);
			
		}
		
		if($GLOBALS["VERBOSE"]){
			echo "Received datas:\n". htmlspecialchars($curl->data)."\n";
		}
		
		@unlink(dirname(__FILE__)."/com.txt");
		@unlink(dirname(__FILE__)."/com.gz");
		shell_exec("$rm -rf /tmp/process1-*");
		if($GLOBALS["OUTPUT"]){echo "Checking orders...". strlen($curl->data)." bytes\n";}
		
		if(preg_match("#<NODEID>([0-9]+)</NODEID>#is", $curl->data,$re)){
			if($GLOBALS["OUTPUT"]){echo "Update nodeid:{$re[1]} \n";}
			$GLOBALS["CLASS_SOCKET"]->SET_INFO("NODE_ID",$re[1]);
		}
		
		
		$ORDERS_RECEIVED=false;
		//$curl->data=html_entity_decode($curl->data);
		
		if(strlen($curl->data)>0){
			$Encrypted=$this->decode_orders($curl->data);
			if($Encrypted<>null){
				$this->OrdersParse($Encrypted);
				$ORDERS_RECEIVED=true;
			}else{
				if($GLOBALS["OUTPUT"]){echo "Encrypted = NULL\n";}
			}
			
			
		}
		
		if(!$ORDERS_RECEIVED){
			if(preg_match("#<ORDERS>(.*?)</ORDERS>#is", $curl->data,$re)){
				$this->OrdersParse($re[1]);
				$ORDERS_RECEIVED=true;
			}
		}
		
		
		
		
		if($GLOBALS["OUTPUT"]){if(!$ORDERS_RECEIVED){echo "No orders receive ".strlen($curl->data)." bytes\n";}}
		if($GLOBALS["OUTPUT"]){echo "Done...\n";}
		
	}
	
	private function decode_orders($bigdata){
		$unix=new unix();
		$tmpfile=$unix->FILE_TEMP();
		
		@file_put_contents($tmpfile, $bigdata);
		$bigdata=null;
		
		$f=explode("\n",@file_get_contents($tmpfile));
		if($GLOBALS["OUTPUT"]){echo count($f)." lines\n";}
		
		while (list ($num, $line) = each ($f) ){
			if(strpos(" $line", "<ORDERS>")>0){
				if($GLOBALS["OUTPUT"]){echo "FOUND $num\n";}
				$start=true;
				$line=str_replace("<ORDERS>", "", $line);
				$t[]=$line;
				if(strpos(" $line", "</ORDERS>")>0){
					if($GLOBALS["OUTPUT"]){echo "FOUND $num END\n";}
					$line=str_replace("</ORDERS>", "", $line);
					return $line;
				}
				continue;
			}
		
			if(strpos(" $line", "</ORDERS>")>0){
				if($GLOBALS["OUTPUT"]){echo "FOUND $num END\n";}
				$line=str_replace("</ORDERS>", "", $line);
				$t[]=$line;
				$return=@implode("", $t);
				unset($t);
				return $return;
			}
		}
		
		
	}
	
	private function squid_caches_infos(){
		return base64_encode(serialize($GLOBALS["CLASS_UNIX"]->squid_get_cache_infos()));
	}
	
	private function squid_system_info(){
		return base64_encode(serialize($GLOBALS["CLASS_UNIX"]->squid_get_system_info()));
		
	}
	
	private function orders_cache($orderid){
		$array=unserialize(@file_get_contents("/etc/artica-postfix/NETAGENTORDERS.cache"));
		if(isset($array[$orderid])){
			$this->events("Order ID:$orderid already performed",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
		
	}
	
	private function orders_set_cache($orderid){
		$array=unserialize(@file_get_contents("/etc/artica-postfix/NETAGENTORDERS.cache"));
		$array[$orderid]=true;
		$this->events("Order ID:$orderid stamped to executed",__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/NETAGENTORDERS.cache", serialize($array));		
	}
	
	private function events($text,$function=null,$file=null,$line=null){
		$pid=getmypid();
		$text=date('H:i:s')." [$pid] $text in $file function: $function line:$line";
		$logFile="/var/log/artica-netagent.log";
		@mkdir(dirname($logFile));
		$logFile=$file;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {
			$size=filesize($logFile);
			if($size>1000000){unlink($logFile);}
		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$text\n");
		@fclose($f);
	}
	
	private function OrdersParse($EncodedData){
		$EncodedData=trim($EncodedData);
		$data=unserialize(base64_decode($EncodedData));
		while (list ($num, $array) = each ($data) ){
				$orderid=$array["orderid"];
				$orderdata=unserialize(base64_decode($array["orderdata"]));
				$OrderOK=false;
				
				
			while (list ($OrderKey, $OrderValue) = each ($orderdata) ){
				if($GLOBALS["OUTPUT"]){echo "Execute order $OrderKey = $OrderValue ?..\n";}
				if(!$this->orders_cache($orderid)){
					$this->events("Accepted order $OrderKey from statistics appliance",__FUNCTION__,__FILE__,__LINE__);
					if($this->OrderExecute($OrderKey,$OrderValue)){
						if($GLOBALS["OUTPUT"]){echo "Executed order $orderid done...\n";}
						$this->events("Ask to remove ID:$orderid/$OrderKey",__FUNCTION__,__FILE__,__LINE__);
						$this->OrderOk($orderid,$OrderKey);
						$this->orders_set_cache($orderid);
					}
				}else{
					$this->events("Ask to remove ID:$orderid/$OrderKey",__FUNCTION__,__FILE__,__LINE__);
					$this->OrderOk($orderid,$OrderKey);					
				}
			}
		}
	}
	
	public function OrderExecute($ORDER,$ORDER_VALUE){
		if(isset($GLOBALS["OrderExecuted$ORDER"])){return true;}
		$GLOBALS["OrderExecuted$ORDER"]=true;
		
		$unix=new unix();$php5=$unix->LOCATE_PHP5_BIN();
		if($ORDER=="SQUID"){
			$this->squid_get_confs();
			return true;
		}
		if($ORDER=="RESTART_SQUID"){
			shell_exec("/etc/init.d/artica-postfix restart squid-cache &");
			return true;
		}
		if($ORDER=="RECONFIGURE_SQUID"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
			return true;
		}
		
		if($ORDER=="VERIFY_CACHE"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --remoteapp-conf");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --caches --force");
			return true;
		}	

		if($ORDER=="RECONSTRUCT_CACHE"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --remoteapp-conf");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --caches-reconstruct --force");
			return true;
		}	
		
		if($ORDER=="REINDEX_CACHE"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --remoteapp-conf");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --reindex --force");
			return true;
		}

		if($ORDER=="REBUILD_CACHE"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --remoteapp-conf");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --force");
			return true;
		}		
		if($ORDER=="WINBIND_RECONFIGURE"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.kerbauth.php --build");
			return true;
		}
		
		if($ORDER=="AD_DISCONNECT"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.kerbauth.php --disconnect 2>&1");
			return true;
		}
		if($ORDER=="AD_CONNECT"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.kerbauth.php --build --force");
			return true;
		}
		if($ORDER=="BUILDCONF"){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build 2>&1 &");
			return true;
		}
		
		if($ORDER=="PING"){
			writelogs("Receive PING...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return true;
		}		
		
		if($ORDER=="ACLSDYN"){
			$this->webfilter_aclsdynamic();
			return true;
		}
		
	}
	
	private function OrderOk($orderid,$orderkey=null){

		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curl->parms["nodeid"]=$this->nodeid;
		$curl->parms["hostid"]=$this->hostid();
		$curl->parms["orderid"]=$orderid;
		$curl->parms["orderkey"]=$orderkey;
		$this->events("[$this->nodeid]: $this->master_uri/nodes.listener.php -> $orderid -> remove",__FUNCTION__,__FILE__,__LINE__);
		if(!$curl->get()){
			if($GLOBALS["OUTPUT"]){echo "orderid=$orderid: Fatal $curl->error\n";}
			$this->events("[$this->nodeid]: orderid=$orderid: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$this->events("[$this->nodeid]: $curl->data",__FUNCTION__,__FILE__,__LINE__);
	}
	

	private function GetDiskList(){
		$dbPath=dirname(__FILE__)."/usb.scan.inc";
		$unix=new unix();
		$timeFile=$unix->file_time_min($dbPath);
		if($timeFile>30){@unlink($dbPath);}
		if(!is_file($dbPath)){shell_exec(PROCESS1_BIN." --disk-scan");}
		if(!is_file($dbPath)){return;}
		include($dbPath);
		return array("DISKS"=>$_GLOBAL["disks_list"],"USB"=>$_GLOBAL["usb_list"]);
	}

	private function BuilYorelGraphs(){
		$unix=new unix();
		$tar=$unix->find_program("tar");
		$t=$unix->FILE_TEMP();
		chdir("/opt/artica/share/www/system/rrd");
		if($GLOBALS["VERBOSE"]){echo "cd /opt/artica/share/www/system/rrd & $tar -czf $t.tar.gz *\n";}
		if($GLOBALS["VERBOSE"]){system("cd /opt/artica/share/www/system/rrd & $tar -czvf $t.tar.gz *");}
		else{
			shell_exec("cd /opt/artica/share/www/system/rrd & $tar -czf $t.tar.gz *");
		}
		$content=@file_get_contents("$t.tar.gz");
		@unlink("$t.tar.gz");
		chdir("/root");
		return base64_encode($content);
		
	}
	
	private function cache_logs(){
		$tail=$GLOBALS["CLASS_UNIX"]->find_program("tail");
		exec("$tail -n 1500 /var/log/squid/cache.log 2>&1",$results);
		return base64_encode(serialize($results));
	}
	private function ufdbclientlog(){
		$tail=$GLOBALS["CLASS_UNIX"]->find_program("tail");
		$grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
		if($GLOBALS["VERBOSE"]){echo "ufdbclientlog:: $tail -n 1500 /var/log/squid/ufdbgclient.log 2>&1\n";}
		exec("$tail -n 1000 /var/log/squid/ufdbgclient.log|grep -E \"\[[0-9]+\]\" 2>&1",$results);
		return base64_encode(serialize($results));
	}	
	

	public function squid_get_confs(){
		$MASTERURI=$this->master_uri."/ressources/logs/web/squid/$this->nodeid";
		$unix=new unix();
		$curl=new ccurl($this->master_uri."/nodes.listener.php");
		$curl->parms["nodeid"]=$this->nodeid;
		$curl->parms["SQUIDCONF"]="TRUE";
		
		if(!$curl->get()){$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}
		$this->events("[$this->nodeid]: save datas to /etc/squid3/squid.conf.gz",__FUNCTION__,__FILE__,__LINE__);
		
		$curl=new ccurl("$MASTERURI/squid.conf.gz");
		if(!$curl->GetFile("/etc/squid3/squid.conf.gz")){$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		@mkdir("/etc/squid3",0755,true);
		$this->uncompress("/etc/squid3/squid.conf.gz","/etc/squid3/squid.conf");
		
		$curl=new ccurl("$MASTERURI/squid.db.gz");
		if(!$curl->GetFile("/etc/squid3/squid.db.gz")){$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/squid.db.gz","/etc/squid3/squid.db");
		
		$curl=new ccurl("$MASTERURI/squid-block.acl.gz");
		if(!$curl->GetFile("/etc/squid3/squid-block.acl.gz")){$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/squid-block.acl.gz","/etc/squid3/squid-block.acl");

		$curl=new ccurl("$MASTERURI/DaemonSettings.conf.gz");
		if(!$curl->GetFile("/etc/squid3/DaemonSettings.conf.gz")){$this->events("[$this->nodeid]: Fatal $curl->error",__FUNCTION__,__FILE__,__LINE__);return;}		
		$this->uncompress("/etc/squid3/DaemonSettings.conf.gz","/etc/squid3/DaemonSettings.conf");		
		
		$array=unserialize(base64_decode(@file_get_contents("/etc/squid3/DaemonSettings.conf")));
		if(is_array($array)){
			while (list ($key, $value) = each ($array) ){
				$value=base64_decode($value);
				$GLOBALS["CLASS_SOCKET"]->SET_INFO($key,$value);
			}
		}else{
			$this->events("[$this->nodeid]: /etc/squid3/DaemonSettings.conf not an array",__FUNCTION__,__FILE__,__LINE__);
		}
		
		
		$array=unserialize(base64_decode(@file_get_contents("/etc/squid3/squid.db")));
		while (list ($key, $value) = each ($array) ){
			$value=base64_decode($value);
			$this->events("[$this->nodeid]: Set $key = $value",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_SOCKET"]->SET_INFO($key,$value);
		}		
		
		$chown=$unix->find_program("chown");
		shell_exec("$chown squid:squid /etc/squid3 >/dev/null 2>&1");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		squid_admin_notifs("Network Agent: Asking to restart squid\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");	
		squid_admin_mysql(2,"Network Agent: Restarting squid-cache","$executed");
		$cmd="/etc/init.d/artica-postfix restart squid-cache";
		$this->events("[$this->nodeid]: reloading squid",__FUNCTION__,__FILE__,__LINE__);
		$this->events("[$this->nodeid]: $cmd",__FUNCTION__,__FILE__,__LINE__);	
		$squidbin=$unix->find_program("squid");
		if(strlen($squidbin)<3){$squidbin=$unix->find_program("squid3");}
		
		@mkdir("/var/log/squid",0755,true);
		@chown("/var/log/squid", "squid");
		@chgrp("/var/log/squid", "squid");
		@chown("/var/log/squid/cache.log", "squid");
		@chgrp("/var/log/squid/cache.log", "squid");
		@chmod("/var/log/squid/cache.log", 0755);		
		
		
		if(is_file($squidbin)){shell_exec("/etc/init.d/squid reload");}
		
		
	}	
	
	public function uncompress($srcName, $dstName) {
	    $sfp = gzopen($srcName, "rb");
	    $fp = fopen($dstName, "w");
	    while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	    gzclose($sfp);
	    fclose($fp);
	}

	private function parse_daemon_settings(){
		$this->events("[$this->nodeid]: Scanning /etc/artica-postfix/settings/Daemons",__FUNCTION__,__FILE__,__LINE__);
		
		
		foreach (glob("/etc/artica-postfix/settings/Daemons/*") as $filename) {
			$key=basename($filename);
			$datas=base64_encode(@file_get_contents($filename));
			$array[$key]=$datas;
			
		}
		$this->events("[$this->nodeid]: Scanning /etc/artica-postfix/settings/Daemons done...".count($array)." elements...",__FUNCTION__,__FILE__,__LINE__);
		return base64_encode(serialize($array));
		
	}
	
	private function webfilter_aclsdynamic(){
		$unix=new unix();
		$curl=new ccurl($this->master_uri."/nodes.listener.php?squid-table=webfilter_aclsdynamic");
		$curl->GetFile("/tmp/webfilter_aclsdynamic.gz");
		if(!$unix->uncompress("/tmp/webfilter_aclsdynamic.gz", "/tmp/webfilter_aclsdynamic.sql")){
			$this->events("[$this->nodeid]: Scanning failed uncompress /tmp/webfilter_aclsdynamic.gz",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		@unlink("/tmp/webfilter_aclsdynamic.gz");
		$this->inject_table("squidlogs","/tmp/webfilter_aclsdynamic.sql");
	}
	
	
	private function inject_table($localdatabase,$sqlfilename){
		$unix=new unix();
		$mysqlbin=$unix->find_program("mysql");
		$q=new mysql();
		$password=null;
		if($q->mysql_server=="127.0.0.1"){$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";}else{$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqlbin --batch --force --user=$q->mysql_admin$password $servcmd";
		$cmd="$cmdline --database=$localdatabase <$sqlfilename 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$results);
		@unlink($sqlfilename);	
		if($q->IS_ERROR_DUMP_CMDLINE($results)){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $localdatabase MySQL tables failed..\n";}
			ufdbguard_admin_events("replication failed restoring $localdatabase with $sqlfilename with error $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}			
		
	}
	function IS_ERROR_DUMP_CMDLINE($results=array()){
		while (list ($index, $line) = each ($results)){
			if(preg_match("#error:\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if(preg_match("#ERROR\s+([0-9]+)#i", $line)){$this->mysql_error=$line;return true;}
			if($GLOBALS["VERBOSE"]){echo "\"$line\" Unknown....line\n";}
		}
	
		return false;
	
	}	

	public function GetNets(){
		exec(PROCESS1_BIN." --nickernel 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			$results1=array();
			exec(PROCESS1_BIN." --nicstatus $ligne 2>&1",$results1);
			$tbl=explode(";",$results1[0]);
			$this->events("Sending status $ligne IP: {$tbl[0]}",__FUNCTION__,__FILE__,__LINE__);
			$array[$ligne]=array(
				"IPADDR"=>$tbl[0],
				"MACADDR"=>$tbl[1],
				"NETMASK"=>$tbl[2],
				"BROADCAST"=>$tbl[3],
				"GATEWAY"=>$tbl[4],
			);

		}
		return base64_encode(serialize($array));
		
	}	
	
	private function SoftwaresStats(){
		$cachefile=dirname(__FILE__)."/softs.cache";
		$TimeSettings=$GLOBALS["CLASS_UNIX"]->file_time_min($cachefile);
		
		$f=explode("\n", @file_get_contents(dirname(__FILE__)."/versions.conf"));
		if($TimeSettings<300){
			$f[]=@file_get_contents($cachefile);
			return base64_encode(@implode("\n", $f));
			
		}
		$dpkg=$GLOBALS["CLASS_UNIX"]->find_program('dpkg');
		
		if(is_file($dpkg)){
				exec("$dpkg -l 2>&1",$results);
				while (list ($num, $ligne) = each ($results) ){if(preg_match("#^ii\s+(.+?)\s+\s+(.+?)\s+\s+(.*)#", $ligne,$re)){$s[]="[{$re[1]}: ({$re[3]})] \"{$re[2]}\"";}}
				@file_put_contents($cachefile, @implode("\n", $s));
		}
			
		
		$f[]=@file_get_contents($cachefile);
		return base64_encode(@implode("\n", $f));
	}
	
	
	public function hostid(){
		$hostid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HOSTID"));
		if($hostid<>null){return $hostid;}
			
		$unix=new unix();
		$hostidbin=$unix->find_program("hostid");
		if(is_file($hostidbin)){
			exec("$hostidbin 2>&1",$results);
			$hostid=md5(trim(@implode("", $results).time()));
			if($hostid<>"00000000"){
				@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
				return $hostid;
			}
		}
			
		$hostid=md5(time());
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
		return $hostid;
	}	
	
}