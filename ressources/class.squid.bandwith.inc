<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.cron.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
class squid_bandwith_builder{
	var $rules_array=array();
	var $delay_pools=array();
	var $delay_class=array();
	var $delay_parameters=array();
	var $rules_name=array();
	var $acls=array();
	var $delay_access=array();
	var $rules_explain=array();
	var $delay_is_acls=array();
	var $additionalsAcls=array();
	var $array_squid_quotas=array();
	private $array_squid_qotas_lines=array();
	private $ERRORS=array();
	private $delay_pools_number=0;
	private $ok=false;
	private $SquidDisableAllFilters=0;
	private $SquidUrgency=0;
	private $EnableSquidBandWidthGlobal=0;
	private $EnableSquidQuotasBandwidth=0;
	private $InfluxSyslogRemote=0;
	private $InfluxUseRemote=0;
	private $EnableKerbAuth=0;
	private $ActiveDirectoryEmergency=0;
	private $EnableMacAddressFilter=0;
	var $AS_ROOT=false;
	
	function squid_bandwith_builder($noload=false){
		$this->AS_ROOT=$GLOBALS["AS_ROOT"];
		if(!$noload){
			
			$sock=new sockets();
			$this->SquidDisableAllFilters=intval($sock->GET_INFO("SquidDisableAllFilters"));
			$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
			$this->EnableSquidBandWidthGlobal=intval($sock->GET_INFO("EnableSquidBandWidthGlobal"));
			$this->EnableSquidQuotasBandwidth=intval($sock->GET_INFO("EnableSquidQuotasBandwidth"));
			$this->EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
			$this->ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
			$this->EnableMacAddressFilter=intval($sock->GET_INFO("EnableMacAddressFilter"));
			
			$sock=new sockets();
			$this->InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
			if($this->InfluxUseRemote==1){
				$this->InfluxSyslogRemote=intval($sock->GET_INFO("InfluxSyslogRemote"));
				if($this->InfluxSyslogRemote==1){$this->EnableSquidQuotasBandwidth=0;}
			}
			
			if($this->ActiveDirectoryEmergency==1){$this->EnableKerbAuth=0;}
			
			$this->loadRules();
		}
		
		
	}
	
	private function build_progress_bandwidth($text,$pourc){
		$echotext=$text;
		$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
		echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
		$cachefile="/usr/share/artica-postfix/ressources/logs/squid.bandwww.progress";
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
	}
	
	function loadrules(){
		if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",51);}
		
		
		$sql="SELECT * FROM squid_pools WHERE enable=1";
		$sock=new sockets();
		
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			$this->ERRORS[]="# MySQL Error Line :".__LINE__;
			writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",55);}
		if($GLOBALS["VERBOSE"]){echo "$sql -> ". mysql_num_rows($results)." element(s)\n";}
		
		$this->delay_pools_number=0;
		$this->ERRORS[]="# EnableSquidBandWidthGlobal = $this->EnableSquidBandWidthGlobal Line:".__LINE__;
		if($this->EnableSquidBandWidthGlobal==1){$this->delay_pools_number=1;}
		
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$delay_class=$this->get_delay_class($ligne);
			if($delay_class==0){continue;}
			$this->delay_pools_number=$this->delay_pools_number+1;
			$this->rules_array[$this->delay_pools_number]=$ligne["ID"];
			$this->rules_name[$this->delay_pools_number]=$ligne["rulename"];
			
			$this->delay_class[]="delay_class $this->delay_pools_number $delay_class";
			$this->delay_parameters[]=$this->get_delay_parameters($this->delay_pools_number,$delay_class,$ligne);
		}
		$this->ERRORS[]="# EnableSquidQuotasBandwidth = $this->EnableSquidQuotasBandwidth Line:".__LINE__;
		if($this->EnableSquidQuotasBandwidth==1){
			@mkdir("/etc/squid3/bandwidths",0755,true);
			@chown("/etc/squid3/bandwidths", "squid");
			@chmod("/etc/squid3/bandwidths", 0755);
			$delay_class_mode[0]=4;
			$delay_class_mode[1]=4;
			$delay_class_mode[2]=1;
			$delay_class_mode[3]=4;
			$delay_class_mode[4]=1;
			
			$sql="SELECT * FROM bandquotas WHERE enabled=1";
			$q=new mysql_squid_builder();
			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){
				$this->ERRORS[]="# MySQL Error Line $q->mysql_error Line:".__LINE__;
			}
				
			$this->ERRORS[]="# bandquotas = ".mysql_num_rows($results)." entrie(s) Line:".__LINE__;
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$this->delay_pools_number=$this->delay_pools_number+1;
				$BandwidthLimit=$ligne["BandwidthLimit"];
				
				$this->ERRORS[]="# delay_class Number:$this->delay_pools_number -> $BandwidthLimit Rule:{$ligne["ID"]} Group type:{$ligne["GroupType"]} Line:".__LINE__;
				$this->array_squid_quotas[$ligne["ID"]]=$this->delay_pools_number;
				$this->array_squid_qotas_lines[]="delay_class $this->delay_pools_number {$delay_class_mode[$ligne["GroupType"]]}";
				
				if($delay_class_mode[$ligne["GroupType"]]==1){
					$this->array_squid_qotas_lines[]="delay_parameters $this->delay_pools_number $BandwidthLimit/$BandwidthLimit";
				}
				if($delay_class_mode[$ligne["GroupType"]]==4){
					$this->array_squid_qotas_lines[]="delay_parameters $this->delay_pools_number -1/-1 -1/-1 -1/-1 $BandwidthLimit/$BandwidthLimit";
				}
				
				if($ligne["GroupType"]==2){
					//IP ADDRESS
					$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
					if(!is_file($FileAcls)){@touch($FileAcls);}
					@chown($FileAcls, "squid");
					$this->array_squid_qotas_lines[]="acl SquidQuota{$ligne["ID"]} src \"$FileAcls\"";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number allow SquidQuota{$ligne["ID"]}";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number deny all";
					$this->array_squid_qotas_lines[]="";
					$this->array_squid_qotas_lines[]="";
					continue;
				}
				if($ligne["GroupType"]==0){
					//Active Directory group.
					if($this->EnableKerbAuth==0){continue;}
					$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
					if(!is_file($FileAcls)){@touch($FileAcls);}
					@chown($FileAcls, "squid");
					$this->array_squid_qotas_lines[]="acl SquidQuota{$ligne["ID"]} proxy_auth -i \"$FileAcls\"";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number allow SquidQuota{$ligne["ID"]}";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number deny all";
					$this->array_squid_qotas_lines[]="";
					$this->array_squid_qotas_lines[]="";
					continue;
				}
				if($ligne["GroupType"]==1){
					//Unique Member
					if($this->EnableKerbAuth==0){continue;}
					$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
					if(!is_file($FileAcls)){@touch($FileAcls);}
					@chown($FileAcls, "squid");
					$this->array_squid_qotas_lines[]="acl SquidQuota{$ligne["ID"]} proxy_auth -i \"$FileAcls\"";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number allow SquidQuota{$ligne["ID"]}";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number deny all";
					$this->array_squid_qotas_lines[]="";
					$this->array_squid_qotas_lines[]="";
					continue;
				}
				if($ligne["GroupType"]==3){
					if($this->EnableMacAddressFilter==0){continue;}
					$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
					if(!is_file($FileAcls)){@touch($FileAcls);}
					@chown($FileAcls, "squid");
					$this->array_squid_qotas_lines[]="acl SquidQuota{$ligne["ID"]} arp \"$FileAcls\"";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number allow SquidQuota{$ligne["ID"]}";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number deny all";
					$this->array_squid_qotas_lines[]="";
					$this->array_squid_qotas_lines[]="";
					continue;
				}
				
				if($ligne["GroupType"]==4){
					if($this->EnableMacAddressFilter==0){continue;}
					$FileAcls="/etc/squid3/bandwidths/Group{$ligne["ID"]}.acl";
					if(!is_file($FileAcls)){@touch($FileAcls);}
					@chown($FileAcls, "squid");
					$this->array_squid_qotas_lines[]="acl SquidQuota{$ligne["ID"]} src \"$FileAcls\"";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number allow SquidQuota{$ligne["ID"]}";
					$this->array_squid_qotas_lines[]="delay_access $this->delay_pools_number deny all";
					$this->array_squid_qotas_lines[]="";
					$this->array_squid_qotas_lines[]="";
					continue;
				}				
				
			}
		}
		
		
		if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {reconfigure}",60);}
	}
	
	public function get_delay_class($ligne){
		$delay=0;
		$total_net_max=intval($ligne["total_net_max"]);
		$total_computer_max=intval($ligne["total_computer_max"]);
		$total_user_max=intval($ligne["total_user_max"]);
		
		
		$total_net_band=intval($ligne["total_net_band"]);
		$total_computer_band=intval($ligne["total_computer_band"]);
		$total_user_band=intval($ligne["total_user_band"]);
		$total_member_band=intval($ligne["total_member_band"]);
		
		$total_user_enabled=intval($ligne["total_user_enabled"]);
		$total_net_enabled=intval($ligne["total_net_enabled"]);
		$total_computer_enabled=intval($ligne["total_computer_enabled"]);
		$total_member_enabled=intval($ligne["total_member_enabled"]);
		
		
		if($total_net_enabled==1){
			if($total_net_band>0){
				$delay=1;
			}
		}
		if($total_computer_enabled==1){
			if($total_computer_band>0){
				$delay=2;
			}
		}

		if($total_user_enabled==1){
			if($total_user_band>0){
				$delay=3;
			}
		}
		
		if($total_member_enabled==1){
			if($total_member_band>0){
				$delay=4;
			}
		}		

		return $delay;
		
	}
	
	
	
	public function get_delay_parameters($count,$delay_class,$ligne){
		$total_net_max=intval($ligne["total_net_max"]);
		$total_computer_max=intval($ligne["total_computer_max"]);
		$total_user_max=intval($ligne["total_user_max"]);
		$total_member_max=intval($ligne["total_member_max"]);
		
		
		$total_net_band=intval($ligne["total_net_band"]);
		$total_computer_band=intval($ligne["total_computer_band"]);
		$total_user_band=intval($ligne["total_user_band"]);
		$total_member_band=intval($ligne["total_member_band"]);
		
		if($total_net_band==0){$total_net_band="-1";}
		if($total_net_max==0){$total_net_max=$total_net_band;}else{$total_net_max=$total_net_max/1024;}
		
		if($total_computer_band==0){$total_computer_band="-1";}
		if($total_computer_max==0){$total_computer_max=$total_computer_band;}else{$total_computer_max=$total_computer_max/1024;}	

		
		if($total_user_band==0){$total_user_band="-1";}
		if($total_user_max==0){$total_user_max=$total_user_band;}else{$total_user_max=$total_user_max/1024;}			
		
		if($total_member_band==0){$total_member_band="-1";}		
		if($total_member_max==0){$total_member_max=$total_member_band;}else{$total_member_max=$total_member_max/1024;}	
		
		if($delay_class==1){
			return "delay_parameters $count $total_net_band/$total_net_max";
		}
		if($delay_class==2){
			return "delay_parameters $count $total_net_band/$total_net_max $total_computer_band/$total_computer_max";
		}
		
		if($delay_class==3){
			return "delay_parameters $count $total_net_band/$total_net_max $total_computer_band/$total_computer_max $total_user_band/$total_user_max";
		}
				
		if($delay_class==4){
			return "delay_parameters $count $total_net_band/$total_net_max $total_computer_band/$total_computer_max $total_user_band/$total_user_max $total_member_band/$total_member_max";
		}		
	}
	
	
	private function GlobalRules(){
		$q=new mysql_squid_builder();
		$sql="CREATE TABLE IF NOT EXISTS `limit_bdwww` ( `website` VARCHAR( 128 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		
		if($q->COUNT_ROWS("limit_bdwww")==0){
			$f["ea.com"]=true;
			$f["eamobile.com"]=true;
			$f["microsoft.com"]=true;
			$f["apple.com"]=true;
			$f["playfirst.com"]=true;
			$f["msecnd.net"]=true;
			$f["windowsupdate.com"]=true;
			$f["122.56.115.0/24"]=true; // googlevideos example.
			$f["216.58.208.0/24"]=true; // video.googleuserscontent.com
			$f["googlevideo.com"]=true;
			$f["youtube.com"]=true;
			$f["steamcommunity.com"]=true;
			$f["steampowered.com"]=true;
			$f["facebook.com"]=true;
			$f["steamstatic.com"]=true;
			$f["^video.googleusercontent.com"]=true;
			
			while (list ($www, $pool_id) = each ($f) ){
				$q->QUERY_SQL("INSERT IGNORE INTO `limit_bdwww` (website) VALUES ('$www')");
				if(!$q->ok){
					echo "$q->mysql_error\n";
					$this->ok=false;
					return;
				}
			}
			
		}
		$acls=new squid_acls();
		$sql="SELECT * FROM limit_bdwww";
		$results=$q->QUERY_SQL($sql);
		$TCP=new IP();
		while ($ligne = mysql_fetch_assoc($results)) {
			$www=trim($ligne["website"]);
			if($www==null){continue;}
			if($TCP->isIPAddressOrRange($www)){
				$LimitBDWIP[]="acl LimitBDWIP dst $www";
				continue;
			}
			$clean_dstdomains[]=$www;
		}
		
		
		
		
		
		$clean_dstdomains=$acls->clean_dstdomains($clean_dstdomains);
		if(count($clean_dstdomains)>0){
			while (list ($index, $www) = each ($clean_dstdomains) ){
				$LimitBDWDOM[]="acl LimitBDWDOM dstdomain $www";
			}
		}
			
		
		
		
		if(count($LimitBDWDOM)>0){
			$FIN[]=@implode("\n", $LimitBDWDOM);
		}
		if(count($LimitBDWIP)>0){
			$FIN[]=@implode("\n", $LimitBDWIP);
		}
		
		$FIN[]="delay_class 1 1";
		if(count($LimitBDWDOM)>0){
			$FIN[]="delay_access 1 allow LimitBDWDOM";
		}
		if(count($LimitBDWIP)>0){
			$FIN[]="delay_access 1 allow LimitBDWIP";
		}
		$FIN[]="delay_access 1 deny all";
		
		$sock=new sockets();
		$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
		$BDW_FINAL=$BDWWizard["BDW_FINAL"];
		
		$FIN[]="delay_parameters 1 $BDW_FINAL/$BDW_FINAL";
		$this->ok=true;
		return @implode("\n", $FIN);
	}
	
	
	function compile(){
		$conf=array();
		$conf[]="";
		$conf[]="#--------- Bandwith limitations (".__CLASS__."/".__FUNCTION__."()/".__LINE__.")";
		$conf[]="# EnableSquidQuotasBandwidth = $this->EnableSquidQuotasBandwidth";
		$conf[]=@implode("\n", $this->ERRORS);
		$conf[]="# delay_pools_number = $this->delay_pools_number";
		if($this->delay_pools_number==0){
			@file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
			return true;
		}
		$conf[]="delay_pools $this->delay_pools_number";
		
		if($this->SquidUrgency==1){
			if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {emergency}",110);}
			$conf[]="# SquidUrgency =1, NO RULES!";
			@file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
			return;
		}
		if($this->SquidDisableAllFilters==1){
			if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {emergency}",110);}
			$conf[]="# SquidDisableAllFilters =1, NO RULES!";
			@file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
			return;
		}
		
		
		if($this->EnableSquidBandWidthGlobal==1){
			if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} GlobalRules",60);}
			$conf[]=$this->GlobalRules();
		}
		
		if($this->EnableSquidQuotasBandwidth==1){
			if(count($this->rules_array)==0){
				$conf[]=@implode("\n", $this->array_squid_qotas_lines);
			}
		}
		
		
		
		$logprix=__CLASS__."/".__FUNCTION__;
		if(count($this->rules_array)==0){
				$conf[]="# NO RULES after compilation (".__CLASS__."/".__FUNCTION__."()/".__LINE__.")";
				@file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
				if($GLOBALS["OUTPUT"]){echo "rules_array 0 element (die())\n";}
				if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} {done}",95);}
				return true;
			
		}
	
		
		while (list ($index, $pool_id) = each ($this->rules_array) ){
			if($index=="0"){
				if($GLOBALS["VERBOSE"]){echo "$logprix: Index is 0 -> continue;\n";}
				continue;
			}
			if($index==null){
				if($GLOBALS["VERBOSE"]){echo "$logprix: Index is null -> continue\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "$logprix: Checks Acls poolid=$pool_id index=$index\n";}
			$this->delay_is_acls[$index]=false;
			$this->squid_groups($pool_id,$index);
			$this->src($pool_id,$index);
			$this->filetype($pool_id,$index);
			$this->dstdomain($pool_id,$index);
			$this->work_time($pool_id,$index);
			$this->proxy_objects($pool_id,$index);
			if(!$this->delay_is_acls[$index]){$this->delay_access[]="delay_access $index deny all";}else{$this->delay_access[]="delay_access $index deny all";}
		}	
		
		if(count($this->additionalsAcls)>0){
			while (list ($index, $acl) = each ($this->additionalsAcls) ){$this->acls[]=$acl;}
		}		

		
		$conf[]="";
		$conf[]="#--------- Bandwith limitations";
		$conf[]="#Acls ". count($this->delay_access)." rules";
		if(count($this->delay_access)>1){
			$conf[]=@implode("\n",$this->acls);
			$conf[]="";
			$conf[]="#Rules definitions";
			$conf[]=@implode("\n",$this->delay_class);
			$conf[]=@implode("\n",$this->delay_parameters);
			$conf[]="";
			$conf[]="#Rules Access definitions";		
			$conf[]=@implode("\n",$this->delay_access);
			$conf[]="delay_initial_bucket_level 50";
			$conf[]="";
		}
		
		$conf[]=@implode("\n", $this->array_squid_qotas_lines);
		
		if(function_exists("build_progress_bandwidth")){build_progress_bandwidth("{limit_rate} acls_bandwidth.conf {done}",95);}
		@file_put_contents("/etc/squid3/acls_bandwidth.conf", @implode("\n", $conf));
		return true;

	}
	
	private function src($pool_id,$index){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='SRC_RESTRICT' AND enabled=1";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ID"]<1){return;}
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
		while (list ($num, $nets) = each ($ACL_DATAS) ){
			if(trim($nets)==null){continue;}
			$nets=trim($nets);
			$dd[]=$nets;
			$this->acls[]="acl BAND_SRC_$pool_id src $nets";	
		}
		if(count($dd)==0){return;}
		$this->delay_access[]="delay_access $index allow BAND_SRC_$pool_id";	
		$this->delay_is_acls[$index]=true;
		$this->rules_explain[$pool_id][]="<i style='font-weight:bold'>{limit_computers_scope_to}: ".@implode(", ",$dd)."</i>";
		
		
	}

	private function squid_groups($pool_id,$index){
		if($GLOBALS["VERBOSE"]){echo "squid_groups($pool_id,$index) -> GROUP_RESTRICT\n";}
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='GROUP_RESTRICT' AND enabled=1";
		$q=new mysql();
		$q2=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ID"]<1){return;}
		
		
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
		if(count($ACL_DATAS)==0){return;}
		
		$acls=new squid_acls();
		
		while (list ($num, $gpid) = each ($ACL_DATAS) ){
			$gpid=trim($gpid);
			if(trim($gpid)==null){continue;}
			if(!is_numeric($gpid)){continue;}
			
			$items=$acls->GetItems($gpid);
			$RequestedGroupeType=$acls->RequestedGroupeType;
			$CountDeItems=count($items);
			if($GLOBALS["VERBOSE"]){echo "squid_groups($pool_id,$index) -> Proxy Group $gpid [$RequestedGroupeType] ($CountDeItems items)\n";}
			if($CountDeItems==0){continue;}
			$filename="/etc/squid3/acls/container_bandwith-$gpid.txt";
			@file_put_contents($filename, @implode("\n", $items));
			$this->additionalsAcls[$gpid]="acl BAND_SRC_GP_$gpid $RequestedGroupeType \"$filename\"";
			$CompiledAcls[]="BAND_SRC_GP_$gpid";
				
		}
		if($GLOBALS["VERBOSE"]){echo "squid_groups($pool_id,$index) -> Proxy Group ". count($CompiledAcls). " elements\n";}
		if(count($CompiledAcls)==0){return;}
		$this->delay_access[]="delay_access $index allow ".@implode(" ", $CompiledAcls);	
		$this->delay_is_acls[$index]=true;
		$this->rules_explain[$pool_id][]="<i style='font-weight:bold'>{limit_groups_to}: ".@implode(", ",$CompiledAcls)."</i>";
		
		
	}	
	
	private function dstdomain($pool_id,$index){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='DOMAIN_RESTRICT' AND enabled=1";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ID"]<1){return;}
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
		while (list ($num, $nets) = each ($ACL_DATAS) ){
			if(trim($nets)==null){continue;}
			$nets=trim($nets);
			$dd[]=$nets;
			$this->acls[]="acl BAND_DSTDOM_$pool_id dstdomain $nets";	
		}
		if(count($dd)==0){return;}
		$this->delay_access[]="delay_access $index allow BAND_DSTDOM_$pool_id";	
		$this->delay_is_acls[$index]=true;
		$this->rules_explain[$pool_id][]="{squid_band_www_restriction_text}: <b>".count($dd)." {websites}</b>";
		
		
	}

	
	private function filetype($pool_id,$index){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='FILE_RESTRICT' AND enabled=1";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ID"]<1){return;}
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
		while (list ($num, $nets) = each ($ACL_DATAS) ){
			if(trim($nets)==null){continue;}
			$nets=trim($nets);
			$dd[]=$nets;
			$this->acls[]="acl BAND_FILE_$pool_id url_regex -i \.$nets\$";	
		}
		if(count($dd)==0){return;}
		$this->delay_access[]="delay_access $index allow BAND_FILE_$pool_id";	
		$this->delay_is_acls[$index]=true;
		$this->rules_explain[$pool_id][]="{squid_band_file_restriction_text}: <b>".count($dd)." {files}</b>";
		
		
	}		
	
	private function work_time($pool_id,$index){
		$sql="SELECT * FROM squid_pools_acls WHERE pool_id=$pool_id AND ACL_TYPE='TIME_RESTRICT' AND enabled=1";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ID"]<1){return;}
		$ACL_DATAS=unserialize(base64_decode($ligne["ACL_DATAS"]));
		$cron=new cron_macros();
		unset($dd);
		while (list ($dayIndex, $day) = each ($cron->cron_squid) ){
			if($ACL_DATAS[$dayIndex]==1){$d[]=$dayIndex;$dd[]=$day;}
			
			
		}
		if(count($d)==0){return;}
		$f=@implode("",$d);
		if(strlen($ACL_DATAS["min1"])<10){$ACL_DATAS["min1"]="0{$ACL_DATAS["min1"]}";}
		if(strlen($ACL_DATAS["min2"])<10){$ACL_DATAS["min2"]="0{$ACL_DATAS["min2"]}";}
		$this->acls[]="acl BAND_TIME_$pool_id time $f {$ACL_DATAS["hour1"]}:{$ACL_DATAS["min1"]}-{$ACL_DATAS["hour2"]}:{$ACL_DATAS["min2"]}";
		$this->delay_access[]="delay_access $index allow BAND_TIME_$pool_id";
		$this->delay_is_acls[$index]=true;
		$this->rules_explain[$pool_id][]="{squid_band_time_restriction_text} ".@implode(", ",$dd) . " 
		{from} {$ACL_DATAS["hour1"]}:{$ACL_DATAS["min1"]} {to} {$ACL_DATAS["hour2"]}:{$ACL_DATAS["min2"]}";
		
		
	}
	
	
	private function proxy_objects($pool_id,$index){
		$q=new mysql_squid_builder();
		
		$sql2="SELECT webfilters_sqacls.ID,
		webfilters_sqaclaccess.aclid,
		webfilters_sqacls.aclport,
		webfilters_sqacls.xORDER,
		webfilters_sqacls.PortDirection,
		webfilters_sqaclaccess.httpaccess_data
		FROM webfilters_sqacls, webfilters_sqaclaccess
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		AND webfilters_sqaclaccess.httpaccess='delay_access'
		AND webfilters_sqaclaccess.httpaccess_data='$pool_id'
		ORDER BY webfilters_sqacls.xORDER";		
		
		
		
		
	//$sql="SELECT aclid,httpaccess_value,httpaccess_data 
		//FROM webfilters_sqaclaccess 
		//WHERE httpaccess='delay_access' AND httpaccess_data='$pool_id'";
		
		//$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		
		$results = $q->QUERY_SQL($sql2);
		
	while ($ligne = mysql_fetch_assoc($results)) {
			$aclid=$ligne["aclid"];
			if($this->AS_ROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: [bandwidth] Rule ID $pool_id (delay_access:$index) = ACLS number $aclid\n";}
			$s=new squid_acls_groups();
			$groups=$s->buildacls_bytype_items($aclid);
			if(count($groups)>0){
				$this->delay_access[]="delay_access $index allow ".@implode(" ",$groups);	
				$this->delay_is_acls[$index]=true;
			}
		
		}
	}
	
	
	
}

?>