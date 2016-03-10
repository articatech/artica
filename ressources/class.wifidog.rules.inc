<?php

include_once(dirname(__FILE__)."/class.wifidog.tools.inc");
include_once(dirname(__FILE__)."/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");

class wifidog_rules{
	private $gateway_addr;
	private $gw_port;
	private $gw_id;
	private $ARP;
	private $url;
	private $client_ip;
	public $ruleid=0;
	private $cachedir="/home/artica/hotspot/caches";
	
	function wifidog_rules(){
		if(!isset($GLOBALS["CONNECTED_IP"])){$GLOBALS["CONNECTED_IP"]=null;}
		$this->gateway_addr=$_REQUEST["gw_address"];
		$this->gw_port=$_REQUEST["gw_port"];
		$this->gw_id=$_REQUEST["gw_id"];
		$this->ARP=$_REQUEST["mac"];
		$this->url=$_REQUEST["url"];
		if(isset($_REQUEST["ruleid"])){$ruleid=$_REQUEST["ruleid"];}
		if(!isset($_REQUEST["token"])){$_REQUEST["token"]=generateToken($this->ARP);}
		if(!isset($_REQUEST["ip"])){$_REQUEST["ip"]=$this->findip();}
		$this->client_ip=$_REQUEST["ip"];
		if($GLOBALS["CONNECTED_IP"]==null){$GLOBALS["CONNECTED_IP"]=$_REQUEST["ip"];}
		if($this->ruleid==0){$this->ruleid=$this->found_rule();}
	}
	
	private function findip(){
		if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
		if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
		if(isset($_GET["forceip"])){$IPADDR=$_GET["forceip"];}
		if($GLOBALS["CONNECTED_IP"]==null){$GLOBALS["CONNECTED_IP"]=$IPADDR;}
		return $IPADDR;
		
		
	}
	
	private function events($text,$function,$line=0){
		if(!$GLOBALS["HOTSPOT_DEBUG"]){return;}
		if(!function_exists("wifidog_logs")){return;}
		wifidog_logs($text,$function,$line);
		
		
	}
	
	
	private function found_rule(){
		$ip=new IP();
		$cachefile="$this->cachedir/webauth_rules_nets";
		if(!is_file($cachefile)){
			$q=new mysql_squid_builder();
			$sql="SELECT ruleid,pattern FROM webauth_rules_nets";
			$results=$q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$pattern=$ligne["pattern"];
				$ruleid=$ligne["ruleid"];
				$MAIN[]=array("pattern"=>$pattern,"ruleid"=>$ruleid);
			}
			
			@file_put_contents($cachefile, serialize($MAIN));
		}else{
			$MAIN=unserialize(@file_get_contents($cachefile));
			
		}
			
		while (list ($num, $ligne) = each ($MAIN) ){
			$pattern=$ligne["pattern"];
			$this->events("webauth_rules_nets: $pattern \"$this->client_ip\"", __FUNCTION__,__LINE__);
			if(!$ip->isInRange($this->client_ip, $pattern)){
				$this->events("webauth_rules_nets: \"$this->client_ip\"  not matches $pattern rule {$ligne["ruleid"]}", __FUNCTION__,__LINE__);
				continue;
			}
			
			$this->events("webauth_rules_nets: \"$this->client_ip\"  OK FOR $pattern rule {$ligne["ruleid"]}", __FUNCTION__,__LINE__);
			return $ligne["ruleid"];
			
		}
		
		return 0;
		
	}

	
	
}