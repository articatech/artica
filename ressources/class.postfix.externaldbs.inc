<?php

class postfix_extern{
	var $hostname=null;
	var $TypeString=null;
	var $basepath=null;
	var $dbTypes=array(
		"1"=>"{alias_maps} (alias_maps)",
		"2"=>"{virtual_alias_maps} (virtual_alias_maps)",
		"3"=>"{virtual_mailbox_maps} (virtual_mailbox_maps)",
		"4"=>"{relay_recipient_maps} (relay_recipient_maps)",
		"5"=>"(recipient_canonical_maps)"
	);

	var $classTypes=array(
		"alias_maps"=>1,
		"virtual_alias_maps"=>2,
		"virtual_mailbox_maps"=>3,
		"relay_recipient_maps"=>4,
		"recipient_canonical_maps"=>5
	);
	
	
	
	var $dbSources=array(
	1=>"Hash",2=>"LDAP"
	
	
	);
	
	
	function postfix_extern($ID=0){
		if(!isset($ID)){$ID=0;}
		$users=new usersMenus();
		$this->POSTFIX_LDAP_COMPLIANCE=$users->POSTFIX_LDAP_COMPLIANCE;
		if(!$this->POSTFIX_LDAP_COMPLIANCE){unset($this->dbSources[2]);}
		if(!is_numeric($ID)){$ID=0;}
		if($ID==0){return;}
	}
	
	function build_extern($hostname,$TypeString){
		$unix=new unix();
		
		$GLOBALS["postmap"]=$unix->find_program("postmap");
		$q=new mysql();
		$this->hostname=$hostname;
		$this->TypeString=$TypeString;
		$postfixdb=$this->classTypes[$TypeString];
		$t=array();
		
		if($this->hostname=="master"){$this->basepath="/etc/postfix";}else{$this->basepath="/etc/postfix-$this->hostname";}
		if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: postfixdb=$postfixdb :: baspath=$this->basepath\n";}
		$sql="SELECT ID FROM postfix_externaldbs WHERE hostname='$hostname' AND `postfixdb`=$postfixdb";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		echo "Starting......: ".date("H:i:s")." Postfix $hostname checks external databases for $TypeString/$postfixdb ". mysql_num_rows($results)." items\n";
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." Postfix $hostname failed with error $q->mysql_error\n";
		}
		
		if(mysql_num_rows($results)==0){
			if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: $sql return 0 elemnts ???!!\n";}
			return null;
		}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			echo "Starting......: ".date("H:i:s")." Postfix $hostname checks external databases ID:{$ligne["ID"]}\n";
			$t[]=$this->build_externid($ligne["ID"]);
		}
		
		$ff=array();
		while (list ($index, $ligne) = each ($t) ){
				if(trim($ligne)==null){if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: $index -> Null String\n";}continue;}
				if(is_array($ligne)){if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: $index -> is an Array !!!\n";while (list ($a, $b) = each ($ligne) ){echo "build_extern::$hostname/$TypeString:: $a = $b !!!\n";}}continue;}
				if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: $index -> `$ligne`\n";}
				$ligne=trim($ligne);
				$ligne=str_replace("\r", "", $ligne);
				$ligne=str_replace("\n", "", $ligne);
				if($ligne==null){continue;}
				$ff[]=$ligne;
		}
		
		if(count($ff)==0){if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: ff=array -> 0 elements return null...\n";}return null;}
		
		if($GLOBALS["VERBOSE"]){echo "build_extern::$hostname/$TypeString:: ff=array -> ".count($ff)." elements...\n";}
		return @implode(",", $ff);
		
		
	}
	
	

	
	
	private function build_externid($ID){
		$q=new mysql();
		$sql="SELECT dbtype,content FROM postfix_externaldbs WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$content=unserialize(base64_decode($ligne["content"]));
		if($GLOBALS["VERBOSE"]){echo "build_externid:: ID: $ID dbtype:`{$ligne["dbtype"]}`\nDumping content:\n";print_r($content);echo "\n";}
		
		switch (intval($ligne["dbtype"])) {
		case 1:
			return $this->database_hash($content,$ID);
			break;
			
		case 2:
			return $this->database_ldap($content,$ID);
			break;
		
		default:
			;
		break;
	}		
		
	}
	
	private function database_hash($content,$ID){
		$t=array();
		$filename="$this->basepath/{$this->TypeString}_$ID";
		if($GLOBALS["VERBOSE"]){echo "database_hash:: Filename:$filename \n";}
		while (list ($num, $ligne) = each ($content) ){
			$num=trim($num);
			$ligne=trim($ligne);
			if($num==null){continue;}
			if($ligne==null){continue;}
			if(is_array($ligne)){echo "database_hash:: Warning $num = array\n";print_r($ligne);echo "\n";}
			if(is_array($num)){echo "database_hash:: Warning $ligne = array\n";print_r($num);echo "\n";}			
			
			
			$t[]="$num\t$ligne";
		}
		
		echo "Starting......: ".date("H:i:s")." Postfix -$ID- $this->hostname {$this->TypeString} ".count($t)." items\n";
		if(count($t)==0){return;}
		@file_put_contents("$filename", @implode("\n", $t));
		if($GLOBALS["VERBOSE"]){echo "database_hash:: `{$GLOBALS["postmap"]} hash:$filename`\n";}
		shell_exec("{$GLOBALS["postmap"]} hash:$filename");
		return "hash:$filename";
	}
	
	
		private function database_ldap($array,$ID){
			if(!$this->POSTFIX_LDAP_COMPLIANCE){return null;}
			$filename="$this->basepath/{$this->TypeString}_$ID.cf";
			$server_host=trim($array["server_host"]);
			$search_base=trim($array["search_base"]);
			$bind_dn=trim($array["bind_dn"]);
			$bind_password=trim($array["bind_password"]);
			$query_filter=trim($array["query_filter"]);
			$scope=trim($array["scope"]);
			$result_attribute=trim($array["result_attribute"]);
			$leaf_result_attribute=trim($array["leaf_result_attribute"]);
			$special_result_attribute=trim($array["special_result_attribute"]);
			
			
			if($server_host<>null)     		   {$f[]="server_host = $server_host";}
			if($search_base<>null)     		   {$f[]="search_base = $search_base";}
			if($bind_dn<>null)         		   {$f[]="bind_dn = $bind_dn";}
			if($bind_password<>null)  		   {$f[]="bind_pw = $bind_password";}
			if($query_filter<>null)    		   {$f[]="query_filter = $query_filter";}
			if($scope<>null) 		   		   {$f[]="scope = $scope";}
			if($result_attribute<>null)		   {$f[]="result_attribute = $result_attribute";}
			if($special_result_attribute<>null){$f[]="special_result_attribute = $special_result_attribute";}
			if($leaf_result_attribute<>null)   {$f[]="leaf_result_attribute = $leaf_result_attribute";}
			
			$targeted_file=$filename;
			@file_put_contents($targeted_file,@implode("\n",$f));
			return "ldap:$targeted_file";			        
			
		}	
	
	
	
}