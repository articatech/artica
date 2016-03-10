<?php

class nginx_tools{
	private $servername=null;
	public $ForceServerName=null;
	public $ServersAliases=array();
	
	function nginx_tools($servername){
		
		$this->servername=$servername;
		
	}
	
	public function server_name(){
		$s=array();
		$KK=array();
		$servername=$this->servername;
		if($this->ForceServerName<>null){$servername=$this->ForceServerName;}
		if(preg_match("#^unix:#", $servername)){return null;}
		$servername=trim(strtolower($servername));
		if(substr($servername, 0,1)=="."){$servername=substr($servername,1,strlen($servername));}
		
		
		
		$IPClass=new IP();
		if(!$IPClass->isIPAddress($servername)){
			$s[]=$servername;
		}
		$sql="SELECT *  FROM `nginx_aliases` WHERE servername='$this->servername'";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql,"artica_events");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$alias=trim(strtolower($ligne["alias"]));
			if($alias==null){continue;}
			$s[]=$alias;
		}
		
		if(count($this->ServersAliases)>0){while (list ($index, $value) = each ($this->ServersAliases) ){$KK[trim(strtolower($value))]=$value;}}
	
		while (list ($index, $value) = each ($s) ){$KK[$value]=$value;}
		$s=array();
		while (list ($index, $value) = each ($KK) ){$s[]=$index;}
		if(count($s)==0){$s[]=$servername;}
		return @implode(" ", $s);
	}
	
	public function IsSubstitutions(){
		if(isset($GLOBALS["IsSubstitutions"])){return $GLOBALS["IsSubstitutions"];}
		$GLOBALS["IsSubstitutions"]=false;
		$ARRAY=$this->CompileParams;
		if(count($ARRAY)<2){
			$unix=new unix();
			$this->CompileParams=$unix->NGINX_COMPILE_PARAMS();
			$ARRAY=$this->CompileParams;
		}
	
	
		if(isset($ARRAY["MODULES"]["ngx_http_substitutions_filter_module-1.0"])){
			$GLOBALS["IsSubstitutions"]=true;
			return $GLOBALS["IsSubstitutions"];
		}
	
	
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
				
			if(preg_match("#http_substitutions_filter#", $a)){
				$GLOBALS["IsSubstitutions"]=true;
				return $GLOBALS["IsSubstitutions"];
			}
	
		}
	
	}	
	
	public function AntiExploits(){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_exploits WHERE servername='$this->servername'";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		$f=array();
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["VERBOSE"]){echo "nginx_exploits: gpid:{$ligne["groupid"]}\n";}
			$groupid=$ligne["groupid"];
			$rules=$this->AntiExploits_items($groupid);
			if($rules<>null){$f[]=$rules;}
		}
			
		if(count($f)>0){return @implode("\n" ,$f);}
	}
	
	private function AntiExploits_items($groupid){
		$q=new mysql_squid_builder();
		$A="$";
		$f=array();
		$sql="SELECT * FROM nginx_exploits_items WHERE groupid=$groupid AND `enabled`=1";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$neg=null;
			if($ligne["reverse"]==1){$neg="!";}
			$ligne["pattern"]=str_replace('"', '\"', $ligne["pattern"]);
			$f[]="\tif ($A{$ligne["token"]} {$neg}~ \"{$ligne["pattern"]}\") {  return 403; }";
			 
		}
	
		if(count($f)>0){return @implode("\n" ,$f);}
	
	}	
	
}
