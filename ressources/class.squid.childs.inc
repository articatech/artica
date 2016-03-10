<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_childs{
	private $LogsWarninStop=0;
	private $SquidUrgency=0;
	private $FireWallScriptsIps=array();
	private $FireWallMARK="-m comment --comment \"ArticaSquidChilds\"";
	
	function squid_childs(){
		$sock=new sockets();
		$this->LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
		$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	}
	
	
	
	
	public function deny_logs(){
		$q=new mysql_squid_builder();
		$tr=array();
		
		if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterLogChilds")){
			$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterLogChilds` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		
		$sql="SELECT ID  FROM proxy_ports WHERE Parent=1 AND SquidAsMasterLogChilds=0";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return null;}
		while ($ligne = mysql_fetch_assoc($results)) {
			if(!isset($ligne["SquidAsMasterFollowxForward"])){$ligne["SquidAsMasterFollowxForward"]=0;}
			$SquidAsMasterFollowxForward=intval($ligne["SquidAsMasterFollowxForward"]);
			if($SquidAsMasterFollowxForward==0){
				$tr[]="deny_log ChildsProxy{$ligne["ID"]}\n";
				
			}
			
		}
			
		if(count($tr)>0){return @implode("\n", $tr);}
	}
	
	
	public function final_access(){
		$ipClass=new IP();
		$tr=array();
		$sql="SELECT ID,port FROM proxy_ports WHERE enabled=1 AND Parent=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return;}
	
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$tr[]="http_access allow ParentPort{$ligne["ID"]}";
			$c++;
		}
		
		$sql="SELECT ID FROM proxy_ports WHERE enabled=1 AND WANPROXY=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return @implode("\n", $tr);}
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$tr[]="http_access allow ParentPort{$ligne["ID"]}";
			$c++;
		}
		
		if($c==0){return @implode("\n", $tr);}
		
		return @implode("\n", $tr);
		
	}
		
	
	
	
	function build(){
		@unlink("/etc/squid3/ChildsProxy.conf");
		@touch("/etc/squid3/ChildsProxy.conf");
		
		$ipClass=new IP();
		$tr=array();
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterFollowxForward")){
			$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterFollowxForward` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		
		$sql="SELECT ID,port,SquidAsMasterFollowxForward FROM proxy_ports WHERE enabled=1 AND Parent=1";
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			return;}
		
		$c=0;
		
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			if(!isset($ligne["SquidAsMasterFollowxForward"])){$ligne["SquidAsMasterFollowxForward"]=0;}
			$SquidAsMasterFollowxForward=intval($ligne["SquidAsMasterFollowxForward"]);
			echo "Starting......: ".date("H:i:s")." [CHIL]: Port {$ligne["port"]}\n";
			$this->FireWallScriptsIps[]="/sbin/iptables -I INPUT -p tcp -m tcp --dport {$ligne["port"]} $this->FireWallMARK -j DROP || true";
			$this->FireWallScriptsIps[]="/sbin/iptables -I INPUT -m limit --limit 1/sec $this->FireWallMARK -j LOG --log-prefix \"FIREHOL:PROXY-Parentdeny:\" || true";
			$this->FireWallScriptsIps[]="/sbin/iptables -I INPUT -p tcp -s 127.0.0.1 -d 127.0.0.1 -j ACCEPT || true";
			$tr[]=$this->build_clients($ligne["ID"],$ligne["port"]);
			$tr[]="acl ParentPort{$ligne["ID"]} localport {$ligne["port"]}";
			if($SquidAsMasterFollowxForward==1){
			$tr[]="follow_x_forwarded_for allow ChildsProxy{$ligne["ID"]}";
			
			$c++;
			}
			
			$this->BuildSHScript();
			
			
		}
		
		$sql="SELECT ID,WANPROXY_PORT FROM proxy_ports WHERE enabled=1 AND WANPROXY=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			return;
		}
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
				$c++;
				$tr[]="acl ParentPort{$ligne["ID"]} localport {$ligne["WANPROXY_PORT"]}";
				$tr[]="follow_x_forwarded_for allow ParentPort{$ligne["ID"]}";
		}
		
		
		if(count($tr)==0){
			echo "Starting......: ".date("H:i:s")." [CHIL]: TR === 0, return...\n";
			
			return;}
		
		
		
		$tr[]=$this->build_all_clients();
		$tr[]="icp_access allow AllChilds";
		
		@file_put_contents("/etc/squid3/ChildsProxy.conf", @implode("\n", $tr));
		
	}
	
	private function BuildSHScript(){
		$unix=new unix();
		$echo=$unix->find_program("echo");
		$php=$unix->LOCATE_PHP5_BIN();
		$sh[]="#!/bin/sh -e";
		$sh[]="$echo \"Remove all rules...\"";
		$sh[]="$php /usr/share/artica-postfix/exec.squid.transparent.delete.php --parent ||true";
		$sh[]="$echo \"Create parent rules...\"";
		$sh[]=@implode("\n", $this->FireWallScriptsIps);
		$sh[]="";
		@file_put_contents("/bin/iptables-parents.sh", @implode("\n", $sh));
		@chmod("/bin/iptables-parents.sh",0755);
	}
	
	
	private function build_all_clients(){
		$tr=array();
		$ipClass=new IP();
		$sql="SELECT portid,ipsrc FROM squid_balancers WHERE enabled=1";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "acl AllChilds src all";}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			if(trim($ligne["ipsrc"])==null){continue;}
			if(!$ipClass->isValid($ligne["ipsrc"])){continue;}
			$tr[]="acl AllChilds src {$ligne["ipsrc"]}\n";
		}		
		
		if(count($tr)==0){
			$tr[]="acl AllChilds src all\n";
		}
		
		return @implode("\n", $tr);
	}
	
	
	private function build_clients($portid,$localport){
		$MAIN=array();
		$ipClass=new IP();
		$tr=array();
		$sql="SELECT portid,ipsrc FROM squid_balancers WHERE enabled=1 and portid=$portid";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "acl ChildsProxy{$portid} localport $localport";}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			if(trim($ligne["ipsrc"])==null){continue;}
			if(!$ipClass->isIPAddressOrRange($ligne["ipsrc"])){continue;}
			$tr[]="acl ChildsProxy{$portid} src {$ligne["ipsrc"]}\n";
			$MAIN["ChildsProxy{$portid}"]=true;
			
			$this->FireWallScriptsIps[]="/sbin/iptables -I INPUT -p tcp -m tcp -s {$ligne["ipsrc"]} --dport $localport $this->FireWallMARK -j ACCEPT || true";
			
		}
		
		if(count($tr)==0){
			$tr[]="acl ChildsProxy{$portid} localport $localport";
			
		}
		
		if(count($MAIN)>0){
			while (list ($Group, $none) = each ($MAIN) ){
				$tr[]="http_access allow $Group";
			}
			
		}
		
		return @implode("\n",$tr);
	
	}	
	
	

	
	
	public function access_log_childs(){
		if($this->LogsWarninStop==1){return;}
		if($this->SquidUrgency==1){return;}
		
		
		$sql="SELECT ID,port FROM proxy_ports WHERE enabled=1 AND Parent=1 AND SquidAsMasterLogChilds=1";
		$q=new mysql_squid_builder();
		
		if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterLogChilds")){
			$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterLogChilds` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return null;}
		if(mysql_num_rows($results)==0){return null;}
		while ($ligne = mysql_fetch_assoc($results)) {
			$conf[]="acl ParentPorts localport {$ligne["port"]}";
			
		}
		
		

		$conf[]="logformat childs %ts.%03tu %6tr %>a %Ss/%03>Hs %<st %rm %ru %[un %Sh/%<a %mt %{X-Forwarded-For}>h";
		$access_log="access_log stdio:/var/log/squid/childs-access.log on-error=drop logformat=childs buffer-size=64KB ParentPorts";
		$conf[]=$access_log;
		return @implode("\n", $conf);
	}	
	
	
	
	
}