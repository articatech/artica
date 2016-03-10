<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");
class firehol_rules{
	private $eth;
	private $route=0;
	public $logs;
	private $iptablesNDPI=0;
	
	public function firehol_rules($eth,$route=0){
		$this->eth=$eth;
		$this->route=$route;
		$this->iptablesNDPI=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/iptablesNDPI"));
	}
	
	
	public function buildrules(){
		
		
		$sql="SELECT * FROM iptables_main WHERE eth='$this->eth' AND enabled=1 ORDER BY zOrder";
		$prefix="server";
		if($this->route==1){$prefix="route";}
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$this->logs[]="# $this->eth $q->mysql_error";}
		$rules=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$TimeEnabled=$ligne["enablet"];
			$restriction=null;
			$jlog=$ligne["jlog"];
			
			if($ligne["application"]<>null){
				if($this->iptablesNDPI==0){continue;}
				$restriction=" custom-in '-m ndpi\t--{$ligne["application"]}'";
			}
			
			if($ligne["accepttype"]=="ACCEPT"){$ACTION="accept";}
			if($ligne["accepttype"]=="DROP"){$ACTION="deny";}
			
			if($jlog==1){$ACTION=$ACTION." loglimit \"RULE-$ID\"";}
			
			
			if($TimeEnabled==1){$restriction=$this->parse_time($ligne["time_restriction"],$restriction);}
			if($ligne["service"]==null){$ligne["service"]="any";}
			$this->logs[]="# $this->eth rule ID: #$ID";
			$rule_prefix="\t$prefix {$ligne["service"]} $ACTION";

			if($this->route==1){
				$rule_prefix="\t$prefix {$ligne["service"]} $ACTION";
				$ACTION=null;
			}
			if($GLOBALS["VERBOSE"]){$rules[]="#Rule id $ID application:{$ligne["application"]} ($this->iptablesNDPI); prefix=$rule_prefix ACTION=$ACTION, restriction=$restriction, LOG=$jlog";}
			$rules[]=$this->build_objects($ID,$rule_prefix,$ACTION,$restriction);
		}
		
		if(count($rules)>0){
			return @implode("\n",$rules);
		}
		
	}
	
	
	private function parse_time($content,$restriction){
		if($content==null){return $restriction;}
		$TTIME=unserialize($content);
		if(!is_array($TTIME)){return $restriction;}
		$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");
		$DDS=array();
		
		while (list ($num, $maks) = each ($array_days)){
			if($TTIME["D{$num}"]==1){$DDS[]=$num;}
		}
		
		if(count($DDS)>0){
			$f[]="--weekdays ".@implode(",", $DDS);
		}
		
		if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
			$f[]="--timestart {$TTIME["ftime"]} --timestop {$TTIME["ttime"]}";
		}
		
		if(count($f)==0){return $restriction;}
		
		if(count($f)>0){
			if($restriction<>null){
				preg_match("#custom\s+'(.*?)'#", $restriction,$re);
				$restriction=trim($re[1])." ";
			}
			return " custom-in '{$restriction}-m time ".@implode(" ", $f)."'";
		}
		
		
	}
	
	
	private function build_objects($ID,$rule_prefix,$ACTION,$time_restriction){
		
		$inbound_objects=$this->objects($ID,0);
		$outbound_objects=$this->objects($ID,1);
		$rules=array();
		$this->logs[]="# $this->eth Inbound Objects: ".count($inbound_objects);
		$this->logs[]="# $this->eth Outbound Objects: ".count($outbound_objects);
		
		if(count($inbound_objects)>0){
			while (list ($index, $objects_inbound) = each ($inbound_objects) ){
				
				if(count($outbound_objects)>0){
					while (list ($index2, $objects_outbound) = each ($outbound_objects) ){
						$rules[]="$rule_prefix $objects_inbound $objects_outbound $time_restriction";
					}
					return @implode("\n", $rules);
				}
				
				$rules[]="$rule_prefix $objects_inbound $time_restriction";
				
			}
			
			return @implode("\n", $rules);
		}
		
		if(count($outbound_objects)>0){
			while (list ($index2, $objects_outbound) = each ($outbound_objects) ){
				$rules[]="$rule_prefix $objects_outbound $time_restriction";
			}
			
			return @implode("\n", $rules);
		}
		
		$rules[]="$rule_prefix $time_restriction";
		return @implode("\n", $rules);
		
		
	}
	
	
	private function objects($ID,$direction){
		$q=new mysql_squid_builder();
		$sql="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
		firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.ID as gpid,
		webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
		WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
		AND firewallfilter_sqacllinks.aclid=$ID
		AND firewallfilter_sqacllinks.direction='$direction'
		AND webfilters_sqgroups.enabled='1'
		ORDER BY firewallfilter_sqacllinks.zOrder";
		$array=array();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$gpid=$ligne["gpid"];
			$GroupType=$ligne["GroupType"];
			$items=$this->build_items($gpid,$GroupType,$ligne["negation"]);
			if($items<>null){
				$array[$GroupType][]=$items;
			}
		}
		
		if(count($array)==0){return;}
		$f=array();
		$FINAL_GROUPS=array();
		while (list ($GroupType, $array2) = each ($array) ){
			if($GroupType=="src"){$prefix="src";}
			if($GroupType=="dst"){$prefix="dst";}
			if($GroupType=="arp"){$prefix="mac";}
			
			$tt=array();
			while (list ($index, $values) = each ($array2) ){
				$tt[]=$values;
				
			}
			$FINAL_GROUPS[]="$prefix \"".@implode(" ", $tt)."\"";
			
			
			
		}
		
		if(count($FINAL_GROUPS)>0){return $FINAL_GROUPS;}
		
	}
	
	
	private function build_items($gpid,$GroupType,$negation=0){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
		$results = $q->QUERY_SQL($sql);
		$IPClass=new IP();
		$prefix=null;
		if($negation==1){$prefix="!";}
		$MAIN=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["pattern"]=trim($ligne["pattern"]);
			if($ligne["pattern"]==null){continue;}
			
			if($GroupType=="src"){
				if(!$IPClass->isIPAddressOrRange($ligne["pattern"])){continue;}
				$MAIN["$prefix{$ligne["pattern"]}"]="$prefix{$ligne["pattern"]}";
				continue;
			}
			if($GroupType=="arp"){
				if(!$IPClass->IsvalidMAC($ligne["pattern"])){continue;}
				$MAIN["$prefix{$ligne["pattern"]}"]="$prefix{$ligne["pattern"]}";
				continue;
			}
		}
		
		if(count($MAIN)==0){return null;}
		$f=array();
		while (list ($index, $ligne) = each ($MAIN) ){
			$f[]=$index;
		}
		return @implode(" ",$f);
	}
	
	
	
	
}