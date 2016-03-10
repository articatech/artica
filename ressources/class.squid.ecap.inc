<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.cron.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
class squid_ecap_builder{
	var $rules_array=array();
	var $delay_pools=array();
	var $delay_class=array();
	var $delay_parameters=array();
	var $rules_name=array();
	var $acls=array();
	var $delay_access=array();
	var $rules_explain=array();
	var $delay_is_acls=array();
	var $additionalsAcls=array();
	var $acls_doms=array();
	var $acls_ips=array();
	private $ok=false;
	private $SquidDisableAllFilters=0;
	private $SquidUrgency=0;
	private $EnableSquidBandWidthGlobal=0;
	private $eCAPClamavMaxSize=0;
	private $EnableeCapClamav=0;
	private $EnableeCapGzip=0;
	private $eCAPClamavEmergency=0;
	private $EnableYoutubeLocker=0;
	private $YoutubeLockerSize=240;
	var $AS_ROOT=false;
	
	function squid_ecap_builder($noload=false){
		$this->AS_ROOT=$GLOBALS["AS_ROOT"];
		if(!$noload){
			
			$sock=new sockets();
			$this->SquidDisableAllFilters=intval($sock->GET_INFO("SquidDisableAllFilters"));
			$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
			$this->EnableeCapClamav=intval($sock->GET_INFO("EnableeCapClamav"));
			$this->EnableeCapGzip=intval($sock->GET_INFO("EnableeCapGzip"));
			$this->eCAPClamavMaxSize=intval($sock->GET_INFO("eCAPClamavMaxSize"));
			$this->eCAPClamavEmergency=intval($sock->GET_INFO("eCAPClamavEmergency"));
			$this->EnableYoutubeLocker=intval($sock->GET_INFO("EnableYoutubeLocker"));
			$this->YoutubeLockerSize=intval($sock->GET_INFO("YoutubeLockerSize"));
		}
		
		
	}
	
	private function build_progress($text,$pourc){
		$echotext=$text;
		$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
		echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.ecap.progress";
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
	}
	
	public function compile(){
		$ACLS_TRUE=false;
		if($this->SquidUrgency==1){
			@file_put_contents("/etc/squid3/acls_ecap.conf", "\n");
			return null;
		}
		if($this->SquidDisableAllFilters==1){
			@file_put_contents("/etc/squid3/acls_ecap.conf", "\n");
			return null;
		}
		if($this->EnableeCapClamav==1){$ACLS_TRUE=true;}
		if($this->EnableYoutubeLocker==1){$ACLS_TRUE=true;}
		if($this->EnableeCapGzip==1){$ACLS_TRUE=true;}
		
		if($ACLS_TRUE){
			$f[]="ecap_enable on";
			$this->build_progress("{ecap_plugins}: {building_acls}",55);
			$this->excludes_acls();
			$this->domain_deny();
		}
		$f[]="#Excludes ACLS ".count($this->acls)." rules";
		$f[]=@implode("\n", $this->acls);
		
		if(count($this->acls_ips)>0){
			$f[]=@implode("\n", $this->acls_ips);
		}
		
		if(count($this->acls_doms)>0){
			$f[]=@implode("\n", $this->acls_doms);
		}
		$f[]=$this->ecap_youtube_locker();
		$f[]=$this->ecap_clamav();
		$f[]=$this->ecap_gzip();
		
		$unix=new unix();
		$ln=$unix->find_program("ln");
		$chown=$unix->find_program("chown");
		shell_exec("$ln -sf /var/lib/clamav /usr/share/clamav >/dev/null 2>&1");
		shell_exec("$chown -R squid:squid /var/lib/clamav >/dev/null 2>&1");
		
		@file_put_contents("/etc/squid3/acls_ecap.conf", @implode("\n", $f));
		@chown("/etc/squid3/acls_ecap.conf","squid");
		return true;
		
	}
	
	function ecap_gzip(){
		$f[]="#eCAP GZIP..";
		if(!is_file("/usr/lib/ecap_adapter_gzip.so")){
			$f[]="# /usr/lib/ecap_adapter_gzip.so no such library";
			return @implode($f, "\n");
		}
		
		if($this->SquidUrgency==1){$f[]="# SquidUrgency is enabled";return @implode($f, "\n");}
		if($this->SquidDisableAllFilters==1){$f[]="# SquidDisableAllFilters is enabled";return @implode($f, "\n");}
		if($this->EnableeCapGzip==0){$f[]="# EnableeCapGzip is disabled";return @implode($f, "\n");}
		
		$f[]="acl HTTP_STATUS_OK http_status 200";
		$f[]="acl ar_xecap req_header X-Ecap -i Artica";
		$f[]="loadable_modules /usr/lib/ecap_adapter_gzip.so";
		$f[]="ecap_service gzip_service respmod_precache ecap://www.vigos.com/ecap_gzip bypass=off";
		$f[]="adaptation_access gzip_service deny ar_xecap";
		$f[]="adaptation_access gzip_service allow HTTP_STATUS_OK";
		return @implode("\n", $f);
		
	}
	
	
	private function ecap_youtube_locker(){
		
		if($this->EnableYoutubeLocker==0){
			$f[]="# Youtube locker disabled";
			return @implode($f, "\n");
		}
		if($this->YoutubeLockerSize==0){$this->YoutubeLockerSize=144;}
		
		@unlink("/usr/lib/ecap_adapter_reslocker.so");
		@copy("/usr/share/artica-postfix/bin/install/squid/ecap_adapter_reslocker.so","/usr/lib/ecap_adapter_reslocker.so");
		@chmod("/usr/lib/ecap_adapter_reslocker.so",0755);
		$f[]="#eCAP Youtube Locker..";
		$f[]="acl html5player url_regex -i ^http.*\.ytimg.com\/yts\/jsbin\/html5player.*\/html5player\-new.js";
		$f[]="request_header_access Accept-Encoding deny html5player";
		$f[]="loadable_modules /usr/lib/ecap_adapter_reslocker.so";
		$f[]="ecap_service ecapModifier respmod_precache uri=ecap://www.unveiltech.com/ecap_modifying ytmax={$this->YoutubeLockerSize}p bypass=on";
		$f[]="adaptation_access ecapModifier allow html5player";
		$f[]="adaptation_access ecapModifier deny all";
		return @implode("\n", $f);
	}
	
	
	private function ecap_clamav(){
		$f[]="#eCAP Clamav..";
		if($this->eCAPClamavEmergency==1){$f[]="# eCAPClamavEmergency is enabled";return @implode($f, "\n");}
		if($this->SquidUrgency==1){$f[]="# SquidUrgency is enabled";return @implode($f, "\n");}
		if($this->SquidDisableAllFilters==1){$f[]="# SquidDisableAllFilters is enabled";return @implode($f, "\n");}
		if($this->EnableeCapClamav==0){$f[]="# EnableeCapClamav is disabled";return @implode($f, "\n");}
		
		if(!is_file("/usr/lib/ecap_clamav_adapter.so")){
			$f[]="# /usr/lib/ecap_clamav_adapter.so no such library";
			return @implode($f, "\n");
		}
		
		if($this->eCAPClamavMaxSize>0){
			$kb=$this->eCAPClamavMaxSize*1024;
			$mb=$kb*1024;
			$huge_size=" huge_size=$mb";
		}
		
		@mkdir("/home/squid/clamav_tmp",0755,true);
		@chown("/home/squid/clamav_tmp","squid");
		@chgrp("/home/squid/clamav_tmp", "squid");
		$staging_dir=" staging_dir=/home/squid/clamav_tmp";
		
		$f[]="loadable_modules /usr/lib/ecap_clamav_adapter.so";
		$f[]="ecap_service clamav_service_req reqmod_precache uri=ecap://e-cap.org/ecap/services/clamav?mode=REQMOD{$huge_size}$staging_dir bypass=off";
		$f[]="ecap_service clamav_service_resp respmod_precache uri=ecap://e-cap.org/ecap/services/clamav?mode=RESPMOD{$huge_size}$staging_dir bypass=on";
		$f[]="adaptation_access clamav_service_req deny bypass_scan_types_req all";
		$f[]="adaptation_access clamav_service_resp deny bypass_scan_types_rep all";
		
		if(count($this->acls_ips)>0){
			$f[]="adaptation_access clamav_service_resp deny bypass_scan_ips all";
			
		}
		if(count($this->acls_doms)>0){
			$f[]="adaptation_access clamav_service_resp deny bypass_scan_dom all";
				
		}	
		$f[]="adaptation_access clamav_service_resp allow all";
		$f[]="";
		return @implode("\n", $f);
	}
	
	
	private function excludes_acls(){
		if($this->SquidUrgency==1){
			$this->acls[]="# excludes_acls, Emergency enabled..";
			return;
		}
		$this->req_mime_type();
		
		
	}
	
	private function default_req_mime_type(){
		$f[]="^text/";
		$f[]="^application/x-javascript";
		$f[]="^application/x-shockwave-flash";
		$f[]="^image/";
		$f[]="^video";
		$f[]="^audio";
		$f[]="^.*application\/x-mms-framed.*$";
		
		while (list ($num, $val) = each ($f)){
			$this->acls[]="acl bypass_scan_types_req req_mime_type -i $val";
			$this->acls[]="acl bypass_scan_types_rep rep_mime_type -i $val";
			
		}
	}
	
	private function domain_deny(){
		$IP=new IP();
		$q=new mysql_squid_builder();
		$acls=new squid_acls();
		$sql="SELECT * FROM webfilter_avwhitedoms";
		$results=$q->QUERY_SQL($sql);
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$websitename=trim(strtolower($ligne["websitename"]));
			if($websitename==null){continue;}
			if(!$IP->isIPAddressOrRange($websitename)){
				$DOMAINS[]=$websitename;
				continue;
			}
			$this->acls_ips[]="acl bypass_scan_ips dst $websitename";
		}
		
		$DOMAINS2=$acls->clean_dstdomains($DOMAINS);
		while (list ($index, $domain) = each ($DOMAINS2) ){
			$this->acls_doms[]="acl bypass_scan_dom dstdomain $domain";
			
		}
		
		
		
	}
	
	private function req_mime_type(){
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("webfilters_blkwhlts")){return $this->default_req_mime_type();}
		if($q->COUNT_ROWS("webfilters_blkwhlts")==0){return $this->default_req_mime_type();}
		$already=array();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=6 AND enabled=1";
		$results=$q->QUERY_SQL($sql);
			
		$this->acl[]="# Do not Scan AV for these Mime types: ". mysql_num_rows($results)." items in ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
		$this->acl[]="acl bypass_scan_types_req req_mime_type -i ^image/png";
		$this->acl[]="acl bypass_scan_types_rep req_mime_type -i ^image/png";
		
			
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["pattern"]);
			if($www=="image/png"){continue;}
			$www_def=$www;
			if($www==null){continue;}
			$this->acls[]="acl bypass_scan_types_req req_mime_type -i ^$www";
			$this->acls[]="acl bypass_scan_types_rep rep_mime_type -i ^$www";
		}
	
	}	
	
	
	
	
}

?>