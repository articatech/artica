<?php

class HyperCacheSquid{
	private $root_squid_version_nocache;
	private $SquidUrgency=0;
	private $MGR_LISTEN_PORT=0;
	private $HyperCacheMaxProcesses=0;
	
	function HyperCacheSquid(){
		$this->root_squid_version_nocache=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
		$this->MGR_LISTEN_PORT=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort");
		$this->SquidUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
		$this->HyperCacheMaxProcesses=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheMaxProcesses"));
		if($this->HyperCacheMaxProcesses==0){$this->HyperCacheMaxProcesses=20;}
	}
	
	
	public function build(){
		@file_put_contents("/etc/squid3/StoreID.conf",$this->StoreID());
		@chown("/etc/squid3/StoreID.conf","squid");
		
	}
	
	
	public function StoreIDFree(){
		
		$f[]="# # # # # # STORE ID Proxy v$this->root_squid_version_nocache # # # # # #";
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("StoreID")){$q->CheckTables();}
	
		include_once(dirname(__FILE__)."/class.storeid.defaults.inc");
		$q->QUERY_SQL(FillStoreIDDefaults());
		
		
		if($q->COUNT_ROWS("StoreID")==0){
			@file_put_contents("/etc/squid3/storeid_rewrite", @file_get_contents("/usr/share/artica-postfix/bin/install/squid/storeid_file_rewrite"));
		}else{
			$TR=array();
			$results=$q->QUERY_SQL("SELECT * FROM StoreID WHERE enabled=1 order by zOrder");
			while ($ligne = mysql_fetch_assoc($results)) {
				$pattern=trim($ligne["pattern"]);
				$dedup=trim($ligne["dedup"]);
				if($pattern==null){continue;}
				if($dedup==null){continue;}
				$TR[]="$pattern\t$dedup";
				
			}
			@file_put_contents("/etc/squid3/storeid_rewrite",@implode("\n", $TR)."\n");
			
		}
		
		
		
		
		$SquidDebugPortNum=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidDebugPortNum"));
		$SquidDebugPortInterface=intval(@file_get_contents("/etc/artica-postfix/settings/DaemonsSquidDebugPortInterface"));
		$refresh_pattern_def_min=64800;
		$refresh_pattern_def_max=129600;
		$refresh_pattern_def_perc=80;
	
		$f[]="store_id_bypass on";
		$f[]="store_id_program /lib/squid3/storeid_file_rewrite /etc/squid3/storeid_rewrite";
		$f[]="store_id_children $this->HyperCacheMaxProcesses startup=5 idle=2 concurrency=0";
		$f[]="store_id_access deny internal_icons";
		$f[]="store_id_access deny !GET";
		$f[]="store_id_access allow all";
		
		
		$f[]="refresh_pattern ^http://(appledlxml|appledl)\.SQUIDINTERNAL/.*  10080 80%  79900 refresh-ims override-expire ignore-reload ignore-private ignore-no-store reload-into-ims";
		$f[]="refresh_pattern -i ^http:\/\/.*\.SQUIDINTERNAL\/.* $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max ignore-reload ignore-no-store ignore-private ignore-auth ignore-must-revalidate override-expire override-lastmod";
		
	
		
		@chown("/etc/squid3/storeid_rewrite","squid");
		@chgrp("/etc/squid3/storeid_rewrite", "squid");
		return @implode("\n",$f)."\n";
	
	
	}
	
	
	private function StoreID(){
		
	
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidCacheLevel")){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidCacheLevel", 4);
		}
		$SquidCacheLevel=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidCacheLevel"));
	
	
	
		$f[]="# # # # # # STORE ID Proxy v$this->root_squid_version_nocache # # # # # #";
		if(is_file("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron")){
			$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		}
		if($EnableIntelCeleron==1){
			$f[]="# StoreID disabled with Enable Intel Celeron";
			return @implode("\n",$f)."\n";
		}
	
		if($SquidCacheLevel==0){
			$f[]="# SquidCacheLevel == 0 Aborting";
			return @implode("\n",$f)."\n";
		}
	
	
	
		if($this->SquidUrgency==1){$f[]="# Emergency mode!";
		return @implode("\n",$f)."\n";
			
		}
	
		$StoreIDUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/StoreIDUrgency"));
		$refresh_pattern_def_min=64800;
		$refresh_pattern_def_max=129600;
		$refresh_pattern_def_perc=80;
	
		if($StoreIDUrgency==1){
			$f[]="# !!!! StoreID Emergency mode !!!!";
			return @implode("\n",$f)."\n";
				
		}
	
		$Compatible=false;
		if(preg_match("#^3\.5#", $this->root_squid_version_nocache)){$Compatible=true;}
		if(preg_match("#^4\.#", $this->root_squid_version_nocache)){$Compatible=true;}
		if(!$Compatible){
			$f[]="# Not a 3.5/4.x version";
			return @implode("\n",$f)."\n";
				
		}
	
		if(!is_file("/lib/squid3/hypercache-plugin")){
			$f[]="# Plugin not installed";
			$f[]=$this->StoreIDFree();
			return @implode("\n",$f)."\n";
		}
	
		
		$HyperCacheStoreID=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheStoreID"));
		$HyperCacheStoreIDLicense=@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheStoreIDLicense");
		$HyperCacheLicensedMode=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheLicensedMode"));
		$SquidDebugPortNum=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidDebugPortNum"));
		$SquidDebugPortInterface=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidDebugPortInterface"));
		$license=null;
	
		if($HyperCacheStoreIDLicense<>null){$license=" -c $HyperCacheStoreIDLicense";}
		if($HyperCacheStoreID==0){
			$f[]="# Not Enabled";
			return @implode("\n",$f)."\n";
		}
	
		if($HyperCacheLicensedMode==0){
			$f[]="# Community Edition";
			$f[]=$this->StoreIDFree();
			return @implode("\n",$f)."\n";
		}
	
		if(!is_file("/var/log/squid/hypercache-plugin.log")){
			@touch("/var/log/squid/hypercache-plugin.log");
			@chown("/var/log/squid/hypercache-plugin.log","squid");
			@chgrp("/var/log/squid/hypercache-plugin.log","squid");
		}
	
	
	
		$refresh_pattern_def_min=64800;
		$refresh_pattern_def_max=129600;
		$refresh_pattern_def_perc=80;
	
		
		$f[]="store_id_bypass on";
		$f[]="store_id_program /lib/squid3/hypercache-plugin -g{$license} -p 127.0.0.1:$this->MGR_LISTEN_PORT -l /var/log/squid ";
		$f[]="store_id_children $this->HyperCacheMaxProcesses startup=5 idle=2 concurrency=0";
		$f[]="store_id_access deny !GET";
		$f[]="store_id_access allow all";
		$f[]="refresh_pattern -i ^http:\/\/windowsupdate\.hypercache-plugin\.internal\/.* $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max ignore-reload ignore-no-store ignore-private ignore-auth ignore-must-revalidate override-expire override-lastmod reload-into-ims";
		$f[]="refresh_pattern -i ^http:\/\/.*\.hypercache-plugin\.internal\/.* $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max ignore-reload ignore-no-store ignore-private ignore-auth ignore-must-revalidate override-expire override-lastmod";
		return @implode("\n",$f)."\n";
	}
	
	
	
}