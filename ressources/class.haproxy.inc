<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).  '/class.mysql.inc');
include_once(dirname(__FILE__).  '/class.haproxy.acls.inc');
class haproxy{
	var $mode=array(0=>"tcp",1=>"http",2=>"http");
	var $acls_actions=array(0=>"{do_nothing}",1=>"{use_backend}",2=>"{use_server}",
			3=>"{deny}", //tcp-request connection reject 
			);
	var $acl_operator=array(0=>"{and}",1=>"{or}");
	var $acl_GroupType=array();
	private $haproxyversion;
	private $MAJOR=1;
	private $MINOR=5;
	
	function haproxy(){
		$this->acl_GroupType["src"]="{src_addr}";
		$this->acl_GroupType["hdr(host)"]="{dstdomain}";
		$this->acl_GroupType["dst"]="{dst_addr}";
		$this->acl_GroupType["dst_port"]="{destination_port}";
	}
	
	function buildconf(){
		if($this->haproxyversion==null){$this->haproxyversion=$this->haversion();}
		
		$this->MAJOR=1;
		$this->MINOR=5;
		
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $this->haproxyversion,$re)){
			$this->MAJOR=intval($re[1]);
			$this->MINOR=intval($re[2]);
				
		}
		$f[]="# $this->haproxyversion $this->MAJOR,$this->MINOR";
		$f[]="global";
		$f[]="\tlog	127.0.0.1 local0";
		
		$f[]="\tmaxconn	4000";
		$f[]="\tuid	0";
		$f[]="\tgid	0";
		$f[]="\tchroot	/tmp";
		$f[]="\tpidfile /var/run/haproxy.pid";
		$f[]="\tstats socket /var/run/haproxy.stat mode 600 level admin";
		$f[]="\tnbproc\t1";
		$f[]="\tdaemon";
		$f[]="\tdebug";
		$f[]="#\tquiet";	
		$f[]="";
		$f[]="defaults";
		$f[]="\tlog\tglobal";
		$f[]="\t".$this->logformat();
		
		$f[]="\tmode\thttp";
		$f[]="\toption\tdontlognull";
		$f[]="\toption\tlogasap";
		$f[]="\tretries\t3";
		$f[]="\toption\tredispatch";
		$f[]="\tmaxconn\t4000";
		$f[]="\ttimeout connect\t5000";
		$f[]="\ttimeout client\t50000";
		$f[]="\ttimeout server\t50000";		
		$f[]="";
		$sql="SELECT * FROM haproxy WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building configuration failed $q->mysql_error\n";return;}}
		if(mysql_num_rows($results)==0){
			if($GLOBALS["AS_ROOT"]){
				echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No backend defined\n";
				return;
			}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$options=array();
			$transparent_text=null;
			$send_proxy_v2=0;
			$transparent=$ligne["transparent"];
			$tunnel_mode=$ligne["tunnel_mode"];
			$dispatch_mode=$ligne["dispatch_mode"];
			$loadbalancetype=intval($ligne["loadbalancetype"]);
			
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			$MainConfig["maxretries"]=$MainConfig["retries"];
			$MainConfig["transparent"]=$transparent;
			$MainConfig["tunnel_mode"]=$tunnel_mode;
			$MainConfig["dispatch_mode"]=$dispatch_mode;
			$MainConfig["loadbalancetype"]=$loadbalancetype;
			if($loadbalancetype==2){$send_proxy_v2=1;}
			if($transparent==1){$ligne["listen_ip"]="127.0.0.1";}

			$f[]="# ----------------------- {$ligne["servicename"]} balance:$loadbalancetype-----------------------";
			$f[]=$this->build_frontend($ligne["servicename"],$ligne["listen_ip"],$ligne["listen_port"]);
			
			
			$build_backends=$this->build_backends($ligne["servicename"],$MainConfig,$send_proxy_v2);
			$default_backend=$this->default_backend($ligne["servicename"]);
			
			if($default_backend==null){
				if($build_backends<>null){
					$default_backend="\tdefault_backend default_{$ligne["servicename"]}";
				}
			}
			
			$f[]=$this->BuildOptionsFromMainConfig($MainConfig);
			$acls=new haproxy_acls($ligne["servicename"]);
			$buildacls=$acls->buildacls();
			if($buildacls<>null){
				$f[]="# --------------------------   A C L S  ---------------------------------";
				$f[]=$buildacls;}
			if($default_backend<>null){$f[]=$default_backend;}
			$f[]="";
			$f[]="";
			
			if($build_backends<>null){
				$f[]=$build_backends;
				$f[]="";
				$f[]="";
			}
			
			
			$f[]="";
			$f[]=$this->build_backends_groups($ligne["servicename"]);
			$f[]="# -----------------------------------------------------------------------";
		}
		
		$f[]=$this->admin_page();
      
		return @implode("\n", $f);
		
	}
	
	private function haversion(){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
		$haproxy=$unix->find_program("haproxy");
		exec("$haproxy -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#HA-Proxy version\s+(.+?)\s+#", $val,$re)){
				return trim($re[1]);
			}
		}
	}
	
	private function build_frontend($servicename,$listen_ip,$listen_port){

		if($this->MAJOR>0){
			if($this->MINOR<6){
				return "listen $servicename $listen_ip:$listen_port";
			}
			
			if($this->MINOR>5){
				$f[]="frontend $servicename";
				$f[]="\tbind\t$listen_ip:$listen_port name $servicename";
				return @implode("\n", $f);
			}
			
		}
		
	}
	
	
	private function admin_page(){
		if($this->MAJOR>0){
			if($this->MINOR<6){
				$f[]="listen admin_page ";
				$f[]="\tmode http";
				$f[]="\tstats uri /stats";
				return @implode("\n", $f);
			}
			
			if($this->MINOR>5){
				$f[]="backend admin_page";
				$f[]="\tbind\t127.0.0.1:64741";
				$f[]="\tmode http";
				$f[]="\tstats uri /stats";
				return @implode("\n", $f);
			}
			
		}

		
	}
	
	
	private function backends_gpid_count($gpid){
		$q=new mysql();
		$table="(SELECT haproxy_backends_link.ID as tid,
		haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
		haproxy_backends.ID=haproxy_backends_link.backendid AND
		haproxy_backends_link.gpid='$gpid' ORDER BY bweight) as t";
		
		$sql="SELECT count(*) as tcount FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		return intval($ligne["tcount"]);
	}
	
	private function default_backend($servicename){
		$q=new mysql();
		$sql="SELECT groupname,ID FROM haproxy_backends_groups WHERE servicename='$servicename' AND enabled=1 AND `default`=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["groupname"]==null){return null;}
		if(!is_numeric($ligne["ID"])){return null;}
		if($this->backends_gpid_count($ligne["ID"])==0){return null;}
		
		return "\tdefault_backend {$ligne["groupname"]}";
	}
	
	private function logformat($mode="http"){
		$f[]="%Ts";
		$f[]="%ci"; //client_ip
		$f[]="%H"; //hostname;
		$f[]="%b"; //backend_name;
		$f[]="%bi"; //backend_source_ip;
		$f[]="%bp"; //backend_source_port;
		$f[]="%s"; //server_name
		$f[]="%si"; //server_IP
		$f[]="%sp"; //server_port
		$f[]="%r"; //http_request
		$f[]="%ST"; // status_code;
		$f[]="%B"; //bytes_read
		$f[]="%U"; //bytes_uploaded
		$f[]="%ts"; //Terminaison state
		
		return "log-format HASTATS:::".@implode(":::", $f);
		
	}
	
	
	
	private function build_backends_groups($servicename){
		
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM haproxy_backends_groups WHERE servicename='$servicename'","artica_backup");
		if(!$q->ok){return "# MySQL error build_backends_groups($servicename)\n#$q->mysql_error";}
		if(mysql_num_rows($results)==0){return "# $servicename no result";}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$groupname=$ligne["groupname"];
			$ID=$ligne["ID"];
			if($this->backends_gpid_count($ID)==0){continue;}
			$f[]="backend $groupname";
			
			
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			$MainConfig["servicename"]=$servicename;
			
			$f[]=$this->BuildOptionsFromMainConfig($MainConfig);
			$f[]=$this->build_groups_backends($ID,$MainConfig);
			
		}
		
		return @implode("\n",$f)."\n";
		
		
	}
	
	
	private function SetDefaults($MainConfig){
		if(!isset($MainConfig["servicename"])){return $MainConfig;}
		if($MainConfig["servicename"]==null){return $MainConfig;}
		$hap=new haproxy_multi($MainConfig["servicename"]);
		if(!isset($MainConfig["loadbalancetype"])){$MainConfig["loadbalancetype"]=$hap->loadbalancetype;}
		if(!isset($MainConfig["tunnel_mode"])){$MainConfig["tunnel_mode"]=$hap->tunnel_mode;}
		if(!isset($MainConfig["dispatch_mode"])){$MainConfig["dispatch_mode"]=$hap->dispatch_mode;}
		if(!isset($MainConfig["UseCookies"])){$MainConfig["UseCookies"]=$hap->MainConfig["UseCookies"];}
			
		if(!isset($MainConfig["contimeout"])){$MainConfig["contimeout"]=$hap->MainConfig["contimeout"];}
		if(!isset($MainConfig["srvtimeout"])){$MainConfig["srvtimeout"]=$hap->MainConfig["srvtimeout"];}
		if(!isset($MainConfig["clitimeout"])){$MainConfig["clitimeout"]=$hap->MainConfig["clitimeout"];}
		if(!isset($MainConfig["maxretries"])){$MainConfig["maxretries"]=$hap->MainConfig["retries"];}
		if(!isset($MainConfig["NTLM_COMPATIBILITY"])){$MainConfig["NTLM_COMPATIBILITY"]=$hap->MainConfig["NTLM_COMPATIBILITY"];}
		
		if(!is_numeric($MainConfig["transparent"])){$MainConfig["transparent"]=0;}
		if(!is_numeric($MainConfig["tunnel_mode"])){$MainConfig["tunnel_mode"]=0;}
		if(!is_numeric($MainConfig["NTLM_COMPATIBILITY"])){$MainConfig["NTLM_COMPATIBILITY"]=0;}
		if(!is_numeric($MainConfig["UseCookies"])){$MainConfig["UseCookies"]=0;}
		if(!is_numeric($MainConfig["http-use-proxy-header"])){$MainConfig["http-use-proxy-header"]=1;}
		if(!is_numeric($MainConfig["forwardfor"])){$MainConfig["forwardfor"]=1;}
		if(!is_numeric($MainConfig["originalto"])){$MainConfig["originalto"]=1;}
		return $MainConfig;
	}
	
	private function BuildOptionsFromMainConfig($MainConfig){
		$options=array();

		$MainConfig=$this->SetDefaults($MainConfig);
		
		
		
		$loadbalancetype=$MainConfig["loadbalancetype"];
		$tunnel_mode=$MainConfig["tunnel_mode"];
		$dispatch_mode=$MainConfig["dispatch_mode"];
		
		if($MainConfig["UseSMTPProto"]==1){$tunnel_mode=0;$MainConfig["NTLM_COMPATIBILITY"]=0;}
		$options[]="#Mode '$loadbalancetype'";
		$options[]="\tmode ". $this->mode[$loadbalancetype];
		
		if($tunnel_mode==1){$dispatch_mode="leastconn";}
		if($MainConfig["asSquidArtica"]==1){$dispatch_mode="roundrobin";}
		
		if($dispatch_mode<>null){
			$options[]="\tbalance $dispatch_mode";
		}
		
		
		
		if($loadbalancetype==0){
			$options[]="\toption\ttcplog";
			$MainConfig["UseCookies"]=0;}
		else{
				$options[]="\toption\thttplog";
			}
		
		if($MainConfig["asSquidArtica"]==1){
			$options[]="\tcookie  SERVERID insert indirect nocache";
			
		}
		
		

			
		if($MainConfig["NTLM_COMPATIBILITY"]==1){
			$MainConfig["UseSMTPProto"]=0;
			$options[]="#\t Active Directory / NTLM enabled";
			$options[]="\tno option httpclose";
			$tunnel_mode=1;
			$transparent=0;
		}
		
		if($tunnel_mode==1){
			$options[]="\toption http-tunnel";
		}
		
		if($MainConfig["UseSMTPProto"]==1){
			if($MainConfig["postfix-send-proxy"]==1){
				if($MainConfig["smtpchk_EHLO"]<>null){
					$options[]="\toption\tsmtpchk\tHELO {$MainConfig["smtpchk_EHLO"]}";
				}else{
					$options[]="\toption\tsmtpchk\tNOOP";
				}
			}
		}
		
		$Cookies_options="nocache";
			
		if($loadbalancetype==1){
			if($tunnel_mode==0){$options[]="\toption\thttp-server-close";}
			if($MainConfig["http-use-proxy-header"]==1){$options[]="\toption\thttp-use-proxy-header";}
			if($MainConfig["forwardfor"]==1){$options[]="\toption\tforwardfor";}
			if($MainConfig["originalto"]==1){$options[]="\toption\toriginalto";}
		}
		
		if($transparent==1){
			$options[]="\toption\ttransparent";
			$options[]="\tsource\t0.0.0.0 usesrc clientip";
			$Cookies_options="insert nocache indirect";
				
		}
		
			
		if($MainConfig["contimeout"]>0){$options[]="\ttimeout connect\t{$MainConfig["contimeout"]}";}
		if($MainConfig["srvtimeout"]>0){$options[]="\ttimeout server\t{$MainConfig["srvtimeout"]}";}
		if($MainConfig["clitimeout"]>0){$options[]="\ttimeout client\t{$MainConfig["clitimeout"]}";}
			
		if($MainConfig["maxretries"]>0){$options[]="\tretries\t{$MainConfig["maxretries"]}";}
		if($MainConfig["UseCookies"]==1){$options[]="\tcookie SERVERID $Cookies_options";}

		return @implode("\n", $options)."\n";
		
	}
	
	
	private function build_groups_backends($gpid,$MainConfig){
		$t=array();
		$sql="SELECT haproxy_backends_link.ID as tid,
		haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
		haproxy_backends.enabled=1 AND
		haproxy_backends.ID=haproxy_backends_link.backendid AND
		haproxy_backends_link.gpid='$gpid' ORDER BY bweight";
		
		$q=new mysql();
		
		if($MainConfig["UseCookies"]==1){$UseCookies=$MainConfig["UseCookies"];}
		
		
		
		
		
		if(!is_numeric($UseCookies)){$UseCookies=0;}
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){return "# $q->mysql_error";}
		$CountOfServers=mysql_num_rows($results);
		if($CountOfServers==0){return "# Group $gpid 0 servers";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ID=$ligne["ID"];
			$t[]=$this->build_backend_unit($ID,$UseCookies,0);
		}
		
		return @implode("\n", $t);
		
	}
	
	private function build_backend_unit($ID,$UseCookies=0,$send_proxy=0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_backends WHERE ID='$ID'","artica_backup"));
		$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
		$log=null;
		$xlogs=array();
		$servicename=$ligne["servicename"];
		$backendname=$ligne["backendname"];
		$LINES=array();
		$check="check";
		
		if(!is_numeric($MainConfig["inter"])){$MainConfig["inter"]=60000;}
		if(!is_numeric($MainConfig["fall"])){$MainConfig["fall"]=3;}
		if(!is_numeric($MainConfig["rise"])){$MainConfig["rise"]=2;}
		if(!is_numeric($MainConfig["maxconn"])){$MainConfig["maxconn"]=10000;}
		if(!is_numeric($MainConfig["asSquidArtica"])){$MainConfig["asSquidArtica"]=0;}
		
		
		
		/*$xlogs[]="# $ID -> ". strlen($ligne["MainConfig"])." bytes\n";
		
		while (list ($a, $b) = each ($MainConfig) ){
			
			$xlogs[]="#$a --> '$b'";
		}*/
		
		if($MainConfig["asSquidArtica"]==1){
			$send_proxy=1;
			$GLOBALS["OPTION_CHECK"]="\toption httpchk GET /squid-internal-periodic/store_digest";
			
		}
		
		
		if(!is_numeric($MainConfig["postfix-send-proxy"])){$MainConfig["postfix-send-proxy"]=0;}
		if( ($MainConfig["inter"]>0) && ($MainConfig["fall"]>0) && ($MainConfig["rise"]>0) ){
			$check_params=" inter {$MainConfig["inter"]} rise {$MainConfig["rise"]} fall {$MainConfig["fall"]}";
		}
		$servMD=md5("{$ligne["listen_ip"]}:{$ligne["listen_port"]}");
		if($UseCookies==1){$UseCookiesText=" cookie $servMD";}
		if(intval($MainConfig["maxconn"])>0){$maxconn=" maxconn {$MainConfig["maxconn"]}";}
		if($MainConfig["postfix-send-proxy"]==1){$smtp_proxy=" send-proxy";}
		if($send_proxy==1){
			$smtp_proxy=" send-proxy";
			$check="$check check-send-proxy";
		}
		if(count($xlogs)>0){$log=@implode("\n", $xlogs)."\n";}
		$LINES[]="$log\tserver {$ligne["backendname"]} {$ligne["listen_ip"]}:{$ligne["listen_port"]}$smtp_proxy$UseCookiesText weight {$ligne["bweight"]}$maxconn $check$check_params".$this->outgoing_address($ligne["localInterface"]);
		return @implode("\n",$LINES);
	}
	
	
	private function build_backends($servicename,$MainConfig,$send_proxy=0){
		
		$UseCookies=$MainConfig["UseCookies"];
		
		$sql="SELECT * FROM haproxy_backends WHERE enabled=1 AND servicename='$servicename' ORDER BY bweight";
		$q=new mysql();
		if(!is_numeric($UseCookies)){$UseCookies=0;}
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(mysql_num_rows($results)==0){return;}
		
		$t[]="backend default_$servicename";
		$t[]=$this->BuildOptionsFromMainConfig($MainConfig);
		
		$c=0;
		$GLOBALS["OPTION_CHECK"]=null;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ID=$ligne["ID"];
			$sql="SELECT ID FROM haproxy_backends_link WHERE backendid='$ID'";
			$ligneCheck=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(intval($ligneCheck["ID"])>0){continue;}
			$t[]=$this->build_backend_unit($ID,$UseCookies,$send_proxy);
			$c++;
		}
		if($GLOBALS["OPTION_CHECK"]<>null){$t[]=$GLOBALS["OPTION_CHECK"];}
		if($c==0){return null;}
		return @implode("\n",$t)."\n";
		
	}
	
	private function outgoing_address($localInterface=null){
		if($localInterface==null){return;}
		
		if(!class_exists("unix")){
			return " source [of $localInterface] usesrc clientip";
		}
		
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
			$unix=new unix();
			$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
		}
		
		$IPADDR=$GLOBALS["NETWORK_ALL_INTERFACES"][$localInterface]["IPADDR"];
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $localInterface = $IPADDR\n";}
		if($IPADDR==null){return;}
		if($IPADDR=="0.0.0.0"){return;}
		if($IPADDR=="127.0.0.1"){return;}
		return " source $IPADDR";
		
		
		
	}
	
	
	public function FlexArray($gpid,$enable=1,$fontsize=14){
		$q=new mysql();
		

		$items=0;
		$t=$_GET["t"];
		$color="black";
		if($enable==0){$color="#8a8a8a";}
		$tpl=new templates();
		$rules=$tpl->_ENGINE_parse_body("{rules}");
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,grouptype FROM haproxy_acls_groups WHERE ID='$gpid'","artica_backup"));
		$GroupName=null;
		$GroupType=$ligne["grouptype"];
		if($GLOBALS["VERBOSE"]){echo "$gpid = $GroupType<br>\n";}
		$GroupTypeName=$tpl->_ENGINE_parse_body($this->acl_GroupType[$GroupType]);
		if($GroupName==null){$GroupName=utf8_encode($ligne["groupname"]);}
		
		
		
		$fontsize2=12;
		if($fontsize<14){$fontsize2=10;}
		if($items==0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='$gpid'","artica_backup"));
			$items=$ligne2["tcount"];
		}
				
		$href[]="<a href=\"javascript:blur();\" ";
		$href[]="OnClick=\"javascript:Loadjs('haproxy.acls.group.setting.php?group-js=yes&ID=$gpid',true);\" ";
		$href[]="style=\"font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color;text-transform:capitalize\">";	
		$hrefT=@implode("", $href);
		
		$hrefAdd[]="<a href=\"javascript:blur();\" ";
		$hrefAdd[]="OnClick=\"javascript:Loadjs('haproxy.acls.items.php?AddItem-js=yes&item-id=-1&gpid=$gpid',true);\" ";
		$hrefAdd[]="style=\"font-size:{$fontsize2}px;color:$color;text-decoration:underline\">";
		
		if($GroupType=="all"){$items="*";}
		
		$hrefAdd[]="($GroupTypeName)";
		$hrefAddT=@implode("", $hrefAdd);
		
		return array("ROW"=>"$hrefT($gpid) $GroupName</a>&nbsp;&nbsp;$hrefAddT</a>",
				"ITEMS"=>$items
				
				);
		
		
	}
	
	
	
	
}


class haproxy_multi{
	var $algo=array();
	var $servicename;
	var $listen_ip=null;
	var $listen_port=null;
	var $loadbalancetype=0;
	var $dispatch_mode=null;
	var $MainConfig=array();
	var $enabled=0;
	var $transparent=0;
	var $transparentsrcport=0;
	
	var $tunnel_mode=0;
	function haproxy_multi($servicename){
		
		
		$this->algo[null]="{round-robin}";
		$this->algo["source"]="{strict-hashed-ip}";
		$this->algo["roundrobin"]="{round-robin}";
		$this->algo["leastconn"]="{leastconn}";
		$this->algo["uri"]="HTTP URL";
		if($servicename<>null){
			$this->servicename=$servicename;
			$this->load();
		}
	}
	
	
	private function load(){
		$sql="SELECT * from haproxy WHERE servicename='$this->servicename'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->listen_ip=$ligne["listen_ip"];
		$this->listen_port=$ligne["listen_port"];
		$this->enabled=$ligne['enabled'];
		$this->loadbalancetype=$ligne["loadbalancetype"];
		$this->dispatch_mode=$ligne["dispatch_mode"];
		$this->transparent=$ligne["transparent"];
		$this->transparentsrcport=$ligne["transparentsrcport"];
		$this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
		$this->tunnel_mode=$ligne["tunnel_mode"];
		if(!is_numeric($this->tunnel_mode)){$this->tunnel_mode=0;}
	}
	
	
	public function save(){
		$this->servicename=trim(strtolower($this->servicename));
		$listenaddrport="$this->listen_ip:$this->listen_port";
		$this->servicename=replace_accents($this->servicename);
		$this->servicename=str_replace(" ", "_", $this->servicename);
		$this->servicename=str_replace("-", "_", $this->servicename);
		$sql="SELECT servicename from haproxy WHERE servicename='$this->servicename'";
		$MainConfig=base64_encode(serialize($this->MainConfig));
		$q=new mysql();
		
		if(!$q->FIELD_EXISTS("haproxy", "tunnel_mode", "artica_backup")){		
			$sql2="ALTER TABLE `haproxy` ADD `tunnel_mode` SMALLINT( 1 ) NOT NULL DEFAULT '0',
					ADD INDEX (`tunnel_mode`)"; 
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}			
		}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["servicename"])==null){
			$sql="INSERT IGNORE INTO haproxy (servicename,listen_ip,listen_port,ipaddrport,loadbalancetype,dispatch_mode,transparent,transparentsrcport,tunnel_mode) 
			VALUES ('$this->servicename','$this->listen_ip','$this->listen_port','$listenaddrport','$this->loadbalancetype','$this->dispatch_mode','$this->transparent','$this->transparentsrcport','$this->tunnel_mode')";
			
		}else{
			$sql="UPDATE haproxy SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				ipaddrport='$listenaddrport',
				enabled='$this->enabled',
				loadbalancetype='$this->loadbalancetype',
				dispatch_mode='$this->dispatch_mode',
				transparent='$this->transparent',
				tunnel_mode=$this->tunnel_mode,
				transparentsrcport='$this->transparentsrcport',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename'
				";
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS("haproxy", "artica_backup")){$q->BuildTables();}
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";
		writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;}
		$this->CheckService();
	}
	
	public function DeleteService(){
		$q=new mysql();
		$sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";return;}
		$sql="DELETE FROM haproxy WHERE servicename='$this->servicename'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}	

		$results=$q->QUERY_SQL("SELECT ID FROM haproxy_acls_rules WHERE servicename='$this->servicename'","artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE ruleid='$ID'","artica_backup");
			$q->QUERY_SQL("DELETE FROM haproxy_acls_rules WHERE ID='$ID'","artica_backup");
			
			
		}
		
		
		
		
		
		
		
		$this->CheckService();
	}
	
	private function CheckService(){
		$sock=new sockets();
		$sock->getFrameWork("services.php?reload-haproxy=yes");		
	}
	
}

class haproxy_backends{
	var $backendname=null;
	var $servicename;
	var $listen_ip=null;
	var $listen_port=null;
	var $bweight=1;
	var $dispatch_mode=null;
	var $enabled=0;
	var $MainConfig=array();
	var $localInterface=null;
	function haproxy_backends($servicename,$backendname){
		
		if($servicename<>null){
			$this->servicename=$servicename;
			if($backendname<>null){
				$this->backendname=$backendname;
				$this->load();
			}
		}
	}
	
	
	private function load(){
		$sql="SELECT * from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->listen_ip=$ligne["listen_ip"];
		$this->listen_port=$ligne["listen_port"];
		$this->enabled=$ligne['enabled'];
		$this->bweight=$ligne["bweight"];
		$this->localInterface=$ligne["localInterface"];
		$this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
		
	}
	
	
	public function save(){
		$this->backendname=trim(strtolower($this->backendname));
		$this->backendname=replace_accents($this->backendname);
		$this->backendname=str_replace(" ", "_", $this->backendname);
		$this->backendname=str_replace("-", "_", $this->backendname);
		$sql="SELECT backendname from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
		$MainConfig=base64_encode(serialize($this->MainConfig));
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["backendname"])==null){
			$sql="INSERT IGNORE INTO haproxy_backends (servicename,backendname,listen_ip,listen_port,bweight,localInterface) 
			VALUES ('$this->servicename','$this->backendname','$this->listen_ip','$this->listen_port','$this->bweight','$this->localInterface')";
			
		}else{
			$sql="UPDATE haproxy_backends SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				bweight='$this->bweight',
				enabled='$this->enabled',
				localInterface='$this->localInterface',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename' AND  backendname='$this->backendname'
				";
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS("haproxy", "artica_backup")){$q->BuildTables();}
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n".__CLASS__."\n$sql\n";return;}
		$this->CheckService();
	}
	
	public function DeleteBackend(){
		$q=new mysql();
		$sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename' AND  backendname='$this->backendname'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}		
		$this->CheckService();
	}
	
	private function CheckService(){
		$sock=new sockets();
		$sock->getFrameWork("services.php?reload-haproxy=yes");
	}
	
}