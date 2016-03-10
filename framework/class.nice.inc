<?php

class exec_nice{
	public $nice;
function exec_nice(){	
	
	if(isset($GLOBALS["exec_nice-exec_nice"])){
		if($GLOBALS["exec_nice-exec_nice"]<>null){
			$this->nice=$GLOBALS["exec_nice-exec_nice"];
			return $GLOBALS["exec_nice-exec_nice"];
		}
	}
	
	$tmp=null;
	$ArticaInCgroups=0;
	$cgroupsEnabled=0;
	$useIonice=1;
	$useNice=1;
	$ArticaCgroup=0;
	if(is_file("/etc/artica-postfix/settings/Daemons/useIonice")){$useIonice=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/useIonice"));}
	if(is_file("/etc/artica-postfix/settings/Daemons/useNice")){$useNice=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/useNice"));}
	if(is_file("/etc/artica-postfix/settings/Daemons/ArticaInCgroups")){$ArticaInCgroups=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaInCgroups"));}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/ArticaCgroup")){$ArticaCgroup=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaCgroup"));}
	if(is_file("/etc/artica-postfix/settings/Daemons/cgroupsEnabled")){$cgroupsEnabled=trim(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/cgroupsEnabled")));}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron")){
		$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: EnableIntelCeleron=$EnableIntelCeleron\n";}
		if($EnableIntelCeleron==1){$cgroupsEnabled=1;}
	}
	
	if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: cgroupsEnabled=$cgroupsEnabled\n";}
	
	if($cgroupsEnabled==1){
		
		if(!is_file("/usr/bin/cgexec")){
			if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: /usr/bin/cgexec no such bin cgroupsEnabled = 0\n";}
			$cgroupsEnabled=0;
		}
		
		if(!is_dir("/cgroups/cpu/php")){
			shell_exec("/etc/init.d/cgconfig start >/dev/null 2>&1");
		}
		
		if(!is_dir("/cgroups/cpu/php")){
			if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: /cgroups/cpu/php no such directory\n";}
			$cgroupsEnabled=0;
		}
		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: cgroupsEnabled=$cgroupsEnabled\n";}
	if($cgroupsEnabled==1){
		$GLOBALS["exec_nice-exec_nice"]="/usr/bin/cgexec -g cpu,cpuset,blkio:php ";
		$this->nice= $GLOBALS["exec_nice-exec_nice"];
		return "/usr/bin/cgexec -g cpu,cpuset,blkio:php ";
	}
	
	
	
	if(!is_numeric($ArticaInCgroups)){$ArticaInCgroups=0;}
	if(!is_numeric($useIonice)){$useIonice=1;}
	if(!is_numeric($useNice)){$useNice=1;}
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}
	
	
	
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
	}else{
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
	}
	
	
	if(isset($ini->_params)){
		if(isset($ini->_params["PERFORMANCES"]['ProcessNice'])){
			$tmp=intval($ini->_params["PERFORMANCES"]['ProcessNice']);
		}
	}
	if($tmp==0){$tmp=19;}
	
	if($useIonice==1){
		if(is_file("/usr/bin/ionice")){
			$ionice="/usr/bin/ionice -c2 -n7 ";
		}
	}
	
	if($useNice==1){
		if(is_file("/usr/bin/nice")){$nice="/usr/bin/nice --adjustment=$tmp ";}
	}
	if(!isset($ionice)){$ionice=null;}
	$GLOBALS["exec_nice-exec_nice"]="$ionice$nice";
	$this->nice= "$ionice$nice";
	
}
}


