<?php


class haproxy_acls{
	private $servicename;
	private $acls=array();
	private $acls_logs=array();
	function haproxy_acls($servicename){
		
		$this->servicename=$servicename;
		
	}
	
	
	public function buildacls(){
		
		$q=new mysql();
		if($q->COUNT_ROWS("haproxy_acls_rules","artica_backup")==0){return null;}
		
		$f=array();
		$results=$q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE servicename='$this->servicename' AND rule_action>0 ORDER BY zorder","artica_backup");
		if(mysql_num_rows($results)==0){return null;}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$rule_action=$ligne["rule_action"];
			$ID=$ligne["ID"];
			$rule_action_data=$ligne["rule_action_data"];
			
			if($rule_action==2){
				$backend_name=$this->backend_name($rule_action_data);
				if($backend_name==null){
					$this->acls_logs[]="# ERROR: Action: $rule_action service: $this->servicename backend_server $rule_action_data did not exists";
					continue;
					
				}
				$access="use-server $backend_name if";
				
			}
			
			
			if($rule_action==1){
				if(!$this->backend_exists(intval($rule_action_data))){
					$this->acls_logs[]="# ERROR: Action: $rule_action service: $this->servicename backend group $rule_action_data did not exists";
					continue;
				}
				$access="use_backend ".$this->backend_groupname($rule_action_data)." if";
			}
			
			
			if($rule_action==3){$access="http-request deny if ";}
			
			
			$acls=$this->buildGroups($ID);
			if($acls==null){
				$this->acls_logs[]="# ERROR: service: $this->servicename buildGroups($ID) report no group";
				continue;}
			$f[]="\t$access $acls";			
		}
		
		if(count($f)==0){return @implode("\n", $this->acls_logs);}
		return @implode("\n",$this->acls)."\n\n".@implode("\n",$f)."\n";
	}
	
	private function backend_name($ID){
		$q=new mysql();
		$sql="SELECT backendname FROM haproxy_backends WHERE servicename='$this->servicename' AND ID='$ID' AND enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		return $ligne["backendname"];
		
	}
	
	private function backend_groupname($ID){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_backends_groups WHERE servicename='$this->servicename' AND ID='$ID'","artica_backup"));
		return $ligne["groupname"];
	}
	
	private function backend_exists($ID){
		$q=new mysql();
		$sql="SELECT ID,groupname FROM haproxy_backends_groups WHERE servicename='$this->servicename' AND ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(intval($ligne["ID"])==0){return false;}
		if($this->backends_gpid_count($ID)==0){return false;}
		return true;
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
	
	function buildGroups($ruleid){
		$q=new mysql();
		$f=array();
		$table="SELECT haproxy_acls_link.groupid,
		haproxy_acls_link.ID as tid,
		haproxy_acls_link.revert,
		haproxy_acls_link.operator,
		haproxy_acls_link.zorder as torder,
		haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups
		WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND
		haproxy_acls_link.ruleid=$ruleid AND
		haproxy_acls_groups.enabled=1
		ORDER BY haproxy_acls_link.zorder";
		
		$results = $q->QUERY_SQL($table,"artica_backup");
		$this->acls_logs[]="# INFO: Rule:$ruleid $this->servicename report ".mysql_num_rows($results)." groups";
		
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$revert=$ligne["revert"];
			$revert_text=null;
			$operator_text=null;
			if($revert==1){$revert_text="!";}
			$operator=$ligne["operator"];
			$grouptype=$ligne["grouptype"];
			if($grouptype=="hdr(host)"){$grouptype=="hdr_sub(host)";}
			
			$items=$this->builditem($ligne["ID"]);
			if($items==0){
				$this->acls_logs[]="# ERROR: Group:{$ligne["ID"]} Rule:$ruleid $this->servicename report no items";
				continue;}
			if($operator==1){$operator_text="|| ";}
			if($c==0){$operator=null;}
			$this->acls[]="\tacl Group{$ligne["ID"]} $grouptype -f /etc/haproxy/acls/Group{$ligne["ID"]}.acl";
			
			$f[]="{$operator_text}{$revert_text}Group{$ligne["ID"]}";
			$c++;
		}
		if(count($f)==0){return null;}
		return @implode(" ", $f);
	}
	
	private function builditem($gpid){
		$q=new mysql();
		$f=array();
		if(!is_dir("/etc/haproxy/acls")){@mkdir("/etc/haproxy/acls",0755,true);}
		@unlink("/etc/haproxy/acls/Group{$gpid}.acl");
		$sql="SELECT * FROM haproxy_acls_items WHERE groupid='$gpid'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$this->acls_logs[]="# ERROR: Group:{$gpid} $this->servicename MySQL error $q->mysql_error";}
		while ($ligne = mysql_fetch_assoc($results)) {
			$pattern=$ligne["pattern"];
			$f[]=$pattern;
		}
		
		if(count($f)==0){return 0;}
		@file_put_contents("/etc/haproxy/acls/Group{$gpid}.acl", @implode("\n", $f)."\n");
		return count($f);
	}
	
}

