<?php
include_once(dirname(__FILE__).'/class.mysql.inc');


class SquidCacheCenter{
	
	private $table="squid_caches_center";
	private $database="artica_backup";
	private $CORP_LICENSE=false;
	private $SquidBoosterEnable=0;
	private $DisableAnyCache=0;
	private $SquidCacheLevel=4;
	private $SquidSimpleConfig;
	public $CURRENT_PROGRESS=0;
	public $MAX_PROGRESS=0;
	public $ASROOT=false;
	public $CACHES_ARRAY=array();
	private $IsCacheRock=false;

	function SquidCacheCenter(){
		$this->CacheDefault();
		$sock=new sockets();
		$users=new usersMenus();
		$this->CORP_LICENSE=$users->CORP_LICENSE;
		$this->SquidBoosterEnable=intval($sock->GET_INFO("SquidBoosterEnable"));
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$this->SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		$this->SquidSimpleConfig=intval($sock->GET_INFO("SquidSimpleConfig"));
		if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=4;}
		if($this->SquidCacheLevel==0){$this->DisableAnyCache=1;$this->SquidBoosterEnable=0;}
		$this->CACHES_ARRAY=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid_caches_center.db"));
		
	}
	private function root_squid_version_nocache(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
	
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
	
			
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				if($re[1]=="3.3."){$re[1]="3.3.0";}
				if($re[1]=="3.4."){$re[1]="3.4.0";}
				if($re[1]=="3.5."){$re[1]="3.5.0";}
	
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
	
	}
	
	private function CacheDefault(){
		$q=new mysql();
		$squidver=$this->root_squid_version_nocache();
		if(!$q->FIELD_EXISTS("squid_caches_center","remove","artica_backup")){
			$sql="ALTER TABLE `squid_caches_center` ADD `remove` smallint(1) NOT NULL,ADD INDEX (remove)";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$q->FIELD_EXISTS("squid_caches_center","min_size","artica_backup")){
			$sql="ALTER TABLE `squid_caches_center` ADD `min_size` BIGINT UNSIGNED NOT NULL DEFAULT 0";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		if(!$q->FIELD_EXISTS("squid_caches_center","max_size","artica_backup")){
			$sql="ALTER TABLE `squid_caches_center` ADD `max_size` BIGINT UNSIGNED NOT NULL DEFAULT 0";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
		if(!$q->COUNT_ROWS($this->table, $this->database)==0){
			$squid=new squidbee();
			$cachename=basename($squid->CACHE_PATH);
			$q->QUERY_SQL("INSERT IGNORE INTO $this->table (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,remove)
			VALUES('$cachename',1,'$squid->CACHE_PATH','aufs','2000','128','256',1,0,0,0)",$this->database);
		}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM $this->table WHERE cachename='MicroCache'","artica_backup"));
		if(intval($ligne["ID"])==0){
			$q->QUERY_SQL("INSERT IGNORE INTO $this->table (cachename,cpu,cache_dir,cache_type,cache_size,
			cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,remove,min_size,max_size)
			VALUES('MicroCache',1,'/home/squid/microcache','aufs','8','128','256',1,0,0,0,0,2048)",$this->database);
			
		}
		
		if($this->SquidSimpleConfig==1){$q->QUERY_SQL("UPDATE $this->table SET cpu=1",$this->database);}
		
		
		
					
		

	}
	public function WORKERS_NUMBER(){
		if(isset($GLOBALS["SQUID_WORKERS_NUMBER"])){return $GLOBALS["SQUID_WORKERS_NUMBER"];}
		 $GLOBALS["SQUID_WORKERS_NUMBER"]=$this->WORKERS();
		 return  $GLOBALS["SQUID_WORKERS_NUMBER"];
	}
	
	private function WORKERS(){
		$q=new mysql();
		$f[1]=1;
		if(!$this->CORP_LICENSE){return 1;}
		$sock=new sockets();
		$unix=new unix();
		$CPU_NUMBER=$unix->CPU_NUMBER()-1;
		if($CPU_NUMBER<1){$CPU_NUMBER=1;}
		$DisableSquidSMP=intval($sock->GET_INFO("DisableSquidSMP"));
		if($DisableSquidSMP==1){return 1;}
		
		$sql="SELECT cpu FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND cpu>0 ORDER BY cpu";
	
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[$ligne["cpu"]]=$ligne["cpu"];
		}
		
		$c=1;
		while (list ($cpu, $val) = each ($f)){
			if($cpu>$c){$c=$cpu;}
		}
		
		if($c>$CPU_NUMBER){$c=$CPU_NUMBER;}
		return $c;
	}
	

	private function build_progress($text){
			
		if(!isset($GLOBALS["PRC"])){$GLOBALS["PRC"]=$this->CURRENT_PROGRESS;}
		if(!$this->ASROOT){return;}
			
		///--wccp-build
			
		$GLOBALS["PRC"]++;
		echo "Starting......: ".date("H:i:s")." $text {$GLOBALS["PRC"]}%\n";
		if(!function_exists("build_progress")){return;}
		if($this->CURRENT_PROGRESS==0){return;}
		if($GLOBALS["PRC"]>$this->MAX_PROGRESS){$GLOBALS["PRC"]=$this->MAX_PROGRESS;}
		build_progress("{reconfigure} $text",$GLOBALS["PRC"]);
	}
	
	
	public function build(){
		$this->build_progress("Checking Workers");
		$WORKERS=$this->WORKERS();
		$this->build_progress("Checking Default cache");
		$this->CacheDefault();
		$conf[]="#";
		$conf[]="#";
		$conf[]="# # # # # # CACHES MANAGEMENT # # # # # #";
		$conf[]="#";
		$conf[]="# --------- MULTIPLE CPUs --------- ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- Number of CPU.....: $WORKERS ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- SquidBoosterEnable: $this->SquidBoosterEnable ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- DisableAnyCache...: $this->DisableAnyCache ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		$conf[]="# --------- SquidCacheLevel...: $this->SquidCacheLevel ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
		
		
		$this->build_progress("Checking Rock cache");
		
		$conf[]="#";
		$conf[]="# # # # # # CPU Affinity # # # # # #";
		$conf[]=$this->cpu_affinity();
		$conf[]="#";
		$conf[]="workers $WORKERS";
		$rockLines=$this->build_rock();
		$conf[]=$rockLines;
		
		for($i=1;$i<$WORKERS+1;$i++){
			$conf[]="# --------- CPU #{$i} ".__CLASS__."/".__FUNCTION__." [".__LINE__."]";
			$conf[]="if \${process_number} = $i";
			$this->build_progress("Checking #CPU $i");
			$conf[]=$this->build_CPU($i);
			$conf[]="endif";
			
		}
		$conf[]="#";
		$conf[]="#";
		$this->build_progress("Checking SMP done.");
		return @implode("\n", $conf);
	}
	
	private function cpu_affinity(){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("squid_caches_center","CPUAF","artica_backup")){$sql="ALTER TABLE `squid_caches_center` ADD `CPUAF` smallint(2) NOT NULL DEFAULT 0";$q->QUERY_SQL($sql,"artica_backup");}
		$sql="SELECT cpu,CPUAF FROM squid_caches_center GROUP BY cpu,CPUAF ORDER BY cpu";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
		$CPU_NUMBER=$unix->CPU_NUMBER();
		$PROCZ=array();
		$AFF=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			$process=$ligne["cpu"];
			$affinity=intval($ligne["CPUAF"]);
			if($affinity==0){continue;}
			if($affinity>$CPU_NUMBER){$affinity=$CPU_NUMBER;}
			$PROCZ[]=$process;
			$AFF[]=$affinity;
		}
		if(count($PROCZ)==0){return;}
		if(count($AFF)==0){return;}
		return "cpu_affinity_map process_numbers=".@implode(",", $PROCZ)." cores=".@implode(",", $AFF);
	}
	
	
	private function build_rock(){
		if($this->DisableAnyCache==1){return;}
		$q=new mysql();
		$unix=new unix();
		$conf=array();
		$sock=new sockets();
		$EnableRockCache=intval($sock->GET_INFO("EnableRockCache"));
		$conf[]="# Rock cache EnableRockCache = $EnableRockCache [".__LINE__."]";
		if($EnableRockCache==0){
			return @implode("\n", $conf);
		}
		if($this->SquidBoosterEnable==1){
			//@mkdir("/home/squid/cache_rock",0755,true);
			//@chown("/home/squid/cache_rock", "squid");
			//@chgrp("/home/squid/cache_rock", "squid");
			//$conf[]="# --------- Cache Booster one rock for better performance...";
			///$conf[]="cache_dir rock /home/squid/cache_rock 5000 min-size=0 max-size=32768 max-swap-rate=250 swap-timeout=350";
			//$this->IsCacheRock=true;
		}
		
		$sql="SELECT * FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND `cache_type`='rock' ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,$this->database);
		if(mysql_num_rows($results)==0){return @implode("\n", $conf);}
		while ($ligne = mysql_fetch_assoc($results)) {
			$cachename=$ligne["cachename"];
			$cache_dir=$ligne["cache_dir"];
			$cache_type=$ligne["cache_type"];
			$cache_size=$ligne["cache_size"];
			if($this->DisableAnyCache==1){$cache_type="Cachenull";}
			
			if($cache_type=="Cachenull"){
				$conf[]="# --------- $cachename is a null cache";
				continue;
			}
			
			$cache_dir_level1=$ligne["cache_dir_level1"];
			$cache_dir_level2=$ligne["cache_dir_level2"];
			$cache_partition=$unix->DIRPART_OF($cache_dir);
			
			if(!isset($GLOBALS["CACHES_SIZE"][$cache_partition])){
				$GLOBALS["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
			}
			
			
			
			$current_cache_size=$cache_size+50;
			$reste=$GLOBALS["CACHES_SIZE"][$cache_partition]-$current_cache_size;
								
			$this->CreateDir($cache_dir);
			$conf[]="# --------- $cache_dir Size:{$cache_size}M Disk:$cache_partition free:{$reste}M";
			if($reste<1){
				$conf[]="# --------- $cache_dir no space left\n";
				continue;
			}
			$GLOBALS["CACHES_SIZE"][$cache_partition]=$reste;
			$conf[]="cache_dir rock $cache_dir $cache_size min-size=0 max-size=32768 max-swap-rate=250 swap-timeout=350";
			$this->IsCacheRock=true;
		}
		return @implode("\n", $conf);
	}
	
	
	private function build_CPU($cpu){
		
		if($cpu==1){
			if($this->SquidBoosterEnable==1){
				$squidbooster=new squidbooster();
				$this->build_progress("Checking Cache booster");
				$conf[]=$squidbooster->cache_booster();
			}
		}
		
		$q=new mysql();
		$unix=new unix();
		$this->build_progress("Loading MySQL settings for CPU #$cpu");
		$sql="SELECT * FROM $this->table WHERE `enabled`=1 AND `remove`=0 AND `cpu`=$cpu ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,$this->database);
		$Toend=null;
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$max_size_text=null;
			$cachename=$ligne["cachename"];
			$cache_dir=$ligne["cache_dir"];
			$cache_type=$ligne["cache_type"];
			$cache_size=abs(intval($ligne["cache_size"]));
			$cache_dir_level1=intval($ligne["cache_dir_level1"]);
			$cache_dir_level2=intval($ligne["cache_dir_level2"]);
			
			if($cache_dir_level1==0){continue;}
			if($cache_dir_level2==0){continue;}
			if($cache_size==0){continue;}
			
			$min_size=intval($ligne["min_size"]);
			$max_size=$ligne["max_size"];

			if($this->SquidBoosterEnable==1){
				if($min_size==0){$min_size=256;}
			}
			
			
			
			$this->build_progress("Cache $cachename Cache size:{$cache_size}M");
			$conf[]="#";
			$conf[]="# --------- [CPU #{$cpu}]: Cache $cachename ID = $ID type:$cache_type Cache size:{$cache_size}M is Rock=$this->IsCacheRock [".__LINE__."]";
			if($this->DisableAnyCache==1){$cache_type="Cachenull";}
			
			if($cache_type=="tmpfs"){
				$conf[]=$this->build_MemBooster($ID,$cache_size,$min_size,$max_size);
				continue;
			}
			
			if($cache_type=="Cachenull"){continue;}
			if($cache_type=="rock"){continue;}
			
			if(@is_link($cache_dir)){$cache_dir=@readlink($cache_dir);}
			$this->CreateDir($cache_dir);
		
			
			$min_size_text=" min-size=2048";
			if($max_size>0){
				$max_size=$max_size*1024;
				$max_size_text=" max-size=$max_size";
			}
		
			if($min_size>0){
				$min_size=$min_size*1024;
				$min_size_text=" min-size=$min_size";
			}
		
			if($this->IsCacheRock && $min_size<131072){
				$min_size=131072;
				$min_size_text=" min-size=131072";
			}
		
			
			$lineZ="cache_dir\t$cache_type\t$cache_dir\t$cache_size\t$cache_dir_level1\t$cache_dir_level2$min_size_text$max_size_text";
			if($cachename=="cache-default"){$Toend=$lineZ;continue;}
			
			$conf[]=$lineZ;
			
		}
		
		if($Toend<>null){
			$conf[]=$Toend;
		}
		
		$conf[]="# --------- [CPU #{$cpu}]: ".count($conf)." items [".__LINE__."]";
		$this->build_progress("Checking #CPU $cpu - Done -");
		return @implode("\n", $conf);
		
	}
	
	private function build_MemBooster($ID,$cache_size,$minsize=0,$maxsize=0){
		$GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $ID {$cache_size}MB [".__LINE__."]";
		$this->build_progress("Checking Memory Booster $ID {$cache_size}MB");
		$directory="/home/squid/cache/MemBooster$ID";
		
		$cache_size_conf=$cache_size-10;
		if($minsize>0){$minsize=$minsize*1024;}
		if($maxsize>0){$maxsize=$maxsize*1024;}
		if($minsize==0){$minsize=2048;}
		$options[]="min-size=$minsize";
		if($maxsize>0){$options[]="max-size=$maxsize";}
		 
		
		$conf="cache_dir	aufs	$directory $cache_size_conf 16 256 ".@implode(" ", $options);
		
		$this->CreateDir($directory);
		$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
		if($MountedRAM==0){
			$this->BuildSquidCachesMountInRam($cache_size,$ID);
			$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
			if($MountedRAM>0){return $conf;}
			return "# --------- $ID {$cache_size}MB unable to mount $directory\n".@implode("\n", $GLOBALS["BuildSquidCachesMountInRam"]);
			
		}
		
		
		$Diff=intval($cache_size-$MountedRAM);
		if(preg_match("#^-([0-9]+)#", $Diff,$re)){$Diff=intval($re[1]);}
		if($Diff>5){
			$this->BuildSquidCachesMountUmount($ID);
			$this->BuildSquidCachesMountInRam($cache_size,$ID);
			$MountedRAM=$this->BuildSquidCachesMountIsMounted($ID);
			if($MountedRAM>0){return $conf;}
			return "# --------- $ID {$cache_size}MB unable to mount $directory";
		}
		return $conf;
		
	}
	
	private function  BuildSquidCachesMountUmount($Index){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		$rm=$unix->find_program("rm");
		$Dir="/home/squid/cache/MemBooster$Index";
		shell_exec("$umount -l $Dir");
		shell_exec("$rm -rf  -l $Dir");
			
	}	
	
	private function CreateDir($directory){
		@mkdir($directory,0755,true);
		@chown($directory, "squid");
		@chgrp($directory, "squid");
		
	}
	
	private function BuildSquidCachesMountInRam($RAM,$Index){
		$unix=new unix();
		$GLOBALS["BuildSquidCachesMountInRam"]=array();
		$mount=$unix->find_program("mount");
		$Dir="/home/squid/cache/MemBooster$Index";
		$this->CreateDir($Dir);
		$this->build_progress("Checking Memory Booster $Index {$RAM}MB mounting...");
		$GLOBALS["BuildSquidCachesMountInRam"][]="# Checking Memory Booster $Index {$RAM}MB mounting...";
		exec("$mount -t tmpfs -o size={$RAM}M tmpfs $Dir 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			$GLOBALS["BuildSquidCachesMountInRam"][]="# $val";
		}
	
	}	
	
	
	private function BuildSquidCachesMountIsMounted($Index){
		$f=file("/proc/mounts");
		while (list ($num, $val) = each ($f)){
			if(preg_match("#^tmpfs.+?\/home\/squid\/cache\/MemBooster$Index\s+.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
				$size=$re[1];
				$unit=strtolower($re[2]);
				if($unit=="k"){return round($size/1024);}
				if($unit=="m"){return $size;}
	
			}
		}
		return 0;
	}	
	
}