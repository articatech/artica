<?php


class unix_cgroups{
	
	function unix_cgroups(){
		if(!isset($GLOBALS["CLASS_UNIX"])){
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		
	}
	
	private function is_cgroup_structure_mounted($structure){
		$structure=str_replace("/", "\/", $structure);
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#\/cgroups\/$structure\s+#",$ligne)){
				if($GLOBALS["VERBOSE"]){echo "is_cgroup_structure_mounted:: $ligne \$MATCH$ \/cgroups\/$structure\s+\n";}
				return true;}
				if($GLOBALS["VERBOSE"]){echo "is_cgroup_structure_mounted:: $ligne NO MATCH \/cgroups\/$structure\s+\n";}
		}
		return false;
	}
	
	
	
	
	public function create_service_structure($groupname){
		$subgroups[]="cpuset";
		$subgroups[]="blkio";
		$subgroups[]="cpu";
		$cgcreate=$GLOBALS["CLASS_UNIX"]->find_program("cgcreate");
		$CREATED=true;
		
		while (list ($num, $ligne) = each ($subgroups)){
			if(!is_dir("/cgroups/$ligne/$groupname")){$CREATED=false;break;}
			
		}
		
		if($CREATED){return;}
		shell_exec("$cgcreate -a root -g cpu,cpuset,blkio:$groupname");
	}
	
	
	public function load_family(){
		
		if(isset($GLOBALS["CGROUPS_FAMILY"])){
			if(count($GLOBALS["CGROUPS_FAMILY"])>1){return $GLOBALS["CGROUPS_FAMILY"];}
		}
		
		
	$f=explode("\n", @file_get_contents("/proc/cgroups"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^([a-z\_]+)\s+#", $ligne,$re)){
			if($re[1]=="net_cls"){continue;}
			if($re[1]=="freezer"){continue;}
			if($re[1]=="devices"){continue;}
			$GLOBALS["CGROUPS_FAMILY"][$re[1]]=true;
		}
	}
	
	return $GLOBALS["CGROUPS_FAMILY"];
}
	
	
	
	public function mount_structure(){
		$CGROUPS_FAMILY=$this->load_family();
		$mount=$GLOBALS["CLASS_UNIX"]->find_program("mount");
		reset($CGROUPS_FAMILY);
		while (list ($structure, $ligne) = each ($GLOBALS["CGROUPS_FAMILY"])){
			if($this->is_cgroup_structure_mounted($structure)){continue;}
			@mkdir("/cgroups/$structure",0775,true);
			$results=array();
			shell_exec("$mount -t cgroup -o\"$structure\" none \"/cgroups/$structure\" 2>&1");
				
			
		}
	
	
	}	
	
}