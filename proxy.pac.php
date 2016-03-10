<?php
$GLOBALS["PROXY_PAC_DEBUG"]=0;

if(isset($_GET["verbose"])){ini_set('display_errors', 1);	
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["PROXY_PAC_DEBUG"]=1;
}
if(isset($argv[1])){
	if($argv[1]=="--verbose"){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["TITLENAME"]="Dynamic Proxy PAC";



proxy_pac();

function LoadIncludes(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.groups.inc");
	include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");	
	include_once(dirname(__FILE__)."/ressources/class.familysites.inc");	
	
}


function GET_REMOTE_ADDR(){
	if(isset($_SERVER["REMOTE_ADDR"])){
		$IPADDR=$_SERVER["REMOTE_ADDR"];
		if($GLOBALS["VERBOSE"]){echo "REMOTE_ADDR = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){
		$IPADDR=$_SERVER["HTTP_X_REAL_IP"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_REAL_IP = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_FORWARDED_FOR = $IPADDR<br>\n";}
	}
	$GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
	if($GLOBALS["VERBOSE"]){echo "HTTP_USER_AGENT = {$GLOBALS["HTTP_USER_AGENT"]}<br>\n";}
	
	if($GLOBALS["VERBOSE"]){
		while (list ($num, $Linz) = each ($_SERVER) ){
			if(is_array($Linz)){
				while (list ($a, $b) = each ($Linz) ){
					echo "<li style='font-size:10px'>\$_SERVER[\"$num\"][\"$a\"]=\"$b\"</li>\n";
				}
				continue;
			}
			echo "<li style='font-size:10px'>\$_SERVER[\"$num\"]=\"$Linz\"</li>\n";
		}
		
	}
	
	
	$GLOBALS["IPADDR"]=$IPADDR;
	return $IPADDR;
}

function proxy_pac(){
	$SessionCache=0;
	if(!$GLOBALS["VERBOSE"]){
		header("content-type: application/x-ns-proxy-autoconfig");
	}
	header ("Date: " . gmdate('D, d M Y H:i:s \G\M\T', time ()));
	header ("Last-Modified: " . gmdate('D, d M Y H:i:s \G\M\T', time ()));
	header ("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time () + 60 * 30));
	header('Content-Transfer-Encoding: binary');
	if(!$GLOBALS["VERBOSE"]){header("Content-Disposition: attachment; filename=\"proxy.pac\"");}
	
	if($GLOBALS["VERBOSE"]){unset($_SESSION["PROXY_PAC_CACHE"]);}
	$SessionCache=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyPacCacheTime"));
	$ProxyPacLockScript=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyPacLockScript"));
	if($SessionCache==0){$SessionCache=10;}
	
	
	if($ProxyPacLockScript==1){
		$ProxyPacLockScriptContent=@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyPacLockScriptContent");
		header("Content-Length: ".filesize($ProxyPacLockScriptContent) );
		echo $ProxyPacLockScriptContent."\n";
		return;
	}
	
	if(intval($SessionCache==0)){$SessionCache=10;}
	if(!is_numeric($GLOBALS["PROXY_PAC_DEBUG"])){$GLOBALS["PROXY_PAC_DEBUG"]=0;}
	
	$IPADDR=GET_REMOTE_ADDR();
	$HTTP_USER_AGENT=trim($GLOBALS["HTTP_USER_AGENT"]);
	if(strpos($IPADDR, ",")>0){$FR=explode(",",$IPADDR);$IPADDR=trim($FR[0]);}

	
	
	$KEYMd5=md5($HTTP_USER_AGENT.$IPADDR);
	$CACHE_FILE=dirname(__FILE__)."/ressources/logs/proxy.pacs/$KEYMd5";
	
	if(!$GLOBALS["VERBOSE"]){
		if(is_file($CACHE_FILE)){
			$time=pac_file_time_min($CACHE_FILE);
			if($time<$SessionCache){
				header("Content-Length: ".filesize($CACHE_FILE) );
				@readfile($CACHE_FILE);
				return;
			}
			@unlink($CACHE_FILE);
		}
	}
	
	
	
	if(!$GLOBALS["VERBOSE"]){
		$GLOBALS["PROXY_PAC_DEBUG"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyPacDynamicDebug"));
	}
	
	
	if(!class_exists("sockets")){ LoadIncludes();}
	
	$ClassiP=new IP();
	
	
	if(!$ClassiP->isIPAddress($IPADDR)){
		$GLOBALS["HOSTNAME"]=$IPADDR;
		$IPADDR=gethostbyname($IPADDR);
		
	}else{
		$GLOBALS["HOSTNAME"]=gethostbyaddr($IPADDR);
	}
	$GLOBALS["IPADDR"]=$IPADDR;
	//srcdomain
	
	
	pack_debug("Connection FROM: $IPADDR [ $HTTP_USER_AGENT ] ",__FUNCTION__,__LINE__);
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM wpad_rules ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){die();}
	
	$date=date("Y-m-d H:i:s");
	$md5=md5("$date$IPADDR$HTTP_USER_AGENT");
	$HTTP_USER_AGENT=mysql_escape_string2($HTTP_USER_AGENT);
	$DenyDnsResolve=intval($sock->GET_INFO("DenyDnsResolve"));

	while ($ligne = mysql_fetch_assoc($results)) {
		$rulename=$ligne["rulename"];
		$FinishbyDirect=$ligne["FinishbyDirect"];
		$ID=$ligne["ID"];
		pack_debug("Parsing rule: \"$rulename\" ID:$ID FinishbyDirect:$FinishbyDirect",__FUNCTION__,__LINE__);
		if(!client_matches($ID)){
			pack_debug("client_matches() resturn false,No source match rule $rulename ID $ID, check other rule",__FUNCTION__,__LINE__);
			continue;
		}
		
		pack_debug("$rulename matches source {$GLOBALS["IPADDR"]} building script..",__FUNCTION__,__LINE__);
		$f=array();
		$f[]="function FindProxyForURL(url, host) {";
		$f[]="\turl = url.toLowerCase();";
		$f[]="\thost = host.toLowerCase();";
		if($DenyDnsResolve==0){
			$f[]="\tvar hostIP = dnsResolve(host);";
		}else{
			$f[]="\tvar hostIP = host;";
		}
		$f[]="\tvar myip=myIpAddress();";
		$f[]="\tvar DestPort=GetPort(url);";
		$f[]="\tvar PROTO='';";
		
		$f[]="\tif (url.substring(0, 5) == 'http:' ){ PROTO='HTTP'; }";
		$f[]="\tif (url.substring(0, 6) == 'https:' ){ PROTO='HTTPS'; }";
		$f[]="\tif (url.substring(0, 4) == 'ftp:' ){ PROTO='FTP'; }";
		
		
		pack_debug("$rulename/$ID building build_whitelist($ID)",__FUNCTION__,__LINE__);
		$f[]=build_whitelist($ID);
		pack_debug("$rulename/$ID building build_subrules($ID)",__FUNCTION__,__LINE__);
		$f[]=build_subrules($ID);
		pack_debug("$rulename/$ID building build_proxies($ID)",__FUNCTION__,__LINE__);
		$f[]=build_proxies($ID,$FinishbyDirect);
		$f[]="}\r\n";
		$f[]="function GetPort(TestURL){";
		$f[]="\tTestURLRegex = /^[^:]*\:\/\/([^\/]*).*/;";
		$f[]="\tTestURLMatch = TestURL.replace(TestURLRegex, \"$1\");";
		$f[]="\tTestURLLower = TestURLMatch.toLowerCase();";
		$f[]="\tTestURLLowerRegex = /^([^\.]*)[^\:]*(.*)/;";
		$f[]="\tNewPort=TestURLLower.replace(TestURLLowerRegex, \"$2\");";
		$f[]="\tif (NewPort == \"\"){";
		$f[]="\t\tNewPort=\":80\";";
		$f[]="\t}";
		$f[]="\treturn NewPort;";
		$f[]="}";
		$f[]="\r\n\r\n";
		
		$script=@implode("\r\n", $f);
		
		pack_debug("SUCCESS $rulename sends script ". strlen($script)." bytes to client",__FUNCTION__,__LINE__);
		if($GLOBALS["VERBOSE"]){
			echo "<textarea style='width:100%;height:450px'>$script</textarea>";
			return ;
		}
		
		header("Content-Length: ".strlen($script) );
		echo $script;
		packsyslog("Connection FROM: $IPADDR [ $HTTP_USER_AGENT ] sends script ". strlen($script),__FUNCTION__,__LINE__);

		@mkdir(dirname($CACHE_FILE),0755,true);
		file_put_contents($CACHE_FILE, $script);
		if(!is_file($CACHE_FILE)){
			packsyslog("FAILED $CACHE_FILE, permission denied");
			pack_error("FAILED $CACHE_FILE, permission denied",__FUNCTION__,__LINE__);
		}
		$script=mysql_escape_string2(base64_encode($script));
		$q->QUERY_SQL("INSERT IGNORE INTO `wpad_events` (`zmd5`,`zDate`,`ruleid`,`ipaddr`,`browser`,`script`,`hostname`) VALUES('$md5','$date','$ID','$IPADDR','$HTTP_USER_AGENT','$script','{$GLOBALS["HOSTNAME"]}')");
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		$q->QUERY_SQL("DELETE FROM `wpad_events` WHERE zDate<DATE_SUB(NOW(),INTERVAL 7 DAY)");
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}

		
		
		return;
		
	}
	
	$q->QUERY_SQL("INSERT IGNORE INTO `wpad_events` (`zmd5`,`zDate`,`ruleid`,`ipaddr`,`browser`,`hostname`) VALUES('$md5','$date','0','$IPADDR','$HTTP_USER_AGENT','{$GLOBALS["HOSTNAME"]}')");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
}

function pac_file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
		
	$data1 = filemtime($path);
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

function build_subrules($ID){
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM wpad_destination_rules WHERE aclid=$ID ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	$f[]="";
	$f[]="// build_subrules($ID)";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$destinations=array();
		$values=array();
		$value=trim($ligne["value"]);
		if($value==null){continue;}
		$xtype=$ligne["xtype"];
		
		$CarriageReturn=strpos($value, "\n");
		$f[]="// Carriage return = $CarriageReturn [".__LINE__."]";
		
		if($CarriageReturn==0){
			$f[]="// Carriage source = $value [".__LINE__."]";
			$values[]=$value;
		}else{
			$explode=explode("\n", $value);
			while (list ($num, $Linz) = each ($explode) ){
				$Linz=trim($Linz);
				if($Linz==null){continue;}
				$values[]=$Linz;
			}
			$f[]="// Carriage sources = ".count($values)." items [".__LINE__."]";
		}
		
		$value=trim($ligne["destinations"]);
		if($value<>null){
			$CarriageReturn=strpos($value, "\n");
			if($CarriageReturn==0){
				$value=trim($value);
				if(!preg_match("#^PROXY#", $value)){$value="PROXY $value";}
				$destinations[]=$value;
				$f[]="// Carriage return Proxy = $value [".__LINE__."]";
			}else{
			
				$explode=explode("\n", $value);
				while (list ($num, $destline) = each ($explode) ){
					$destline=trim($destline);
					if($destline==null){continue;}
					if(!preg_match("#^PROXY#", $destline)){$destline="PROXY $destline";}
					$destinations[]=$destline;
				}
			}
			
			
			
		}
	
		if(count($destinations)==0){$destinations_final="DIRECT";}
		if(count($destinations)>0){$destinations_final=@implode("; ", $destinations);}
		
		if($xtype=="shExpMatchRegex"){
			while (list ($num, $pattern) = each ($values) ){
				$f[]="\tif( url.match( /$pattern/i ) ){ return \"$destinations_final\"; }";
				$f[]="\tif( host.match( /$pattern/i ) ){ return \"$destinations_final\"; }";
			}
			continue;
		}
			
		if($xtype=="shExpMatch"){
			while (list ($num, $pattern) = each ($values) ){
				$f[]="\tif( shExpMatch( url,\"$pattern\" ) ){ return \"$destinations_final\"; }";
				$f[]="\tif( shExpMatch( host,\"$pattern\" ) ){ return \"$destinations_final\"; }";
			}
			continue;
		}
		
		if($xtype=="isInNetMyIP"){
			while (list ($num, $pattern) = each ($values) ){
				$xt=explode("-",$pattern);
				$xt[0]=trim($xt[0]);
				$xt[1]=trim($xt[1]);
				if($xt[1]==null){$xt[1]="255.255.255.0";}
				$f[]="\tif( isInNet( myip, \"{$xt[0]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
			}
			continue;
		}	

		if($xtype=="isInNet"){
			while (list ($num, $pattern) = each ($values) ){
				$xt=explode("-",$pattern);
				$xt[0]=trim($xt[0]);
				$xt[1]=trim($xt[1]);
				if($xt[1]==null){$xt[1]="255.255.255.0";}
				$f[]="\tif( isInNet( hostIP, \"{$xt[0]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
			}
			continue;
		}		

	
	}
	
	return @implode("\r\n\r\n", $f);
	
}


function client_matches($ID){
	$q=new mysql_squid_builder();
	$sql="SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";
	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("$ID $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$not=false;
		$matches=false;
		$GroupName=$ligne["GroupName"];
		$negation=$ligne["negation"];
		if($negation==1){$not=true;}
		pack_debug("Checks $GroupName Group Type:\"{$ligne["GroupType"]}\" negation=\"$negation\"",__FUNCTION__,__LINE__);
		
		if($ligne["GroupType"]=="dstdomain"){
			$matches=true;
			continue;
		}
		
		
		if($ligne["GroupType"]=="all"){
				if($not==false){
					pack_debug("Checks $GroupName * ALL * will matche in all cases..: Yes",__FUNCTION__,__LINE__);
					$matches=true;
				}
			continue;
		}
		
		
		if($ligne["GroupType"]=="browser"){
			if(matches_browser($gpid,$negation)){
				pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : Yes",__FUNCTION__,__LINE__);
				$matches=true;
				continue;
			}
			$matches=false;
			pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : No",__FUNCTION__,__LINE__);
		}
		if($ligne["GroupType"]=="srcdomain"){
			if(matches_srcdomain($gpid,$negation)){
				pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : Yes",__FUNCTION__,__LINE__);
				$matches=true;
				continue;
			}
			$matches=false;
			pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : No",__FUNCTION__,__LINE__);			
			
		}
		if($ligne["GroupType"]=="src"){
			if(matches_src($gpid,$negation)){
				pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : Yes",__FUNCTION__,__LINE__);
				$matches=true;
				continue;
			}
			$matches=false;
			pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : No",__FUNCTION__,__LINE__);
				
		}	

		if($ligne["GroupType"]=="time"){
			if(matches_time($gpid,$negation)){
				pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : Yes",__FUNCTION__,__LINE__);
				$matches=true;
				continue;
			}
			$matches=false;
			pack_debug("negation=$negation: $GroupName {$ligne["GroupType"]} : No",__FUNCTION__,__FILE__,__LINE__);
		
		}		
	
	
	}	
	
	if(!$matches){
		pack_debug("Final : Nothing matches: No",__FUNCTION__,__LINE__);
	}else{
		pack_debug("Final : Rules matches: Yes",__FUNCTION__,__LINE__);
	}
	return $matches;
}


function matches_browser($gpid,$negation){
	$q=new mysql_squid_builder();
	$HTTP_USER_AGENT=$_SERVER["HTTP_USER_AGENT"];
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($negation==1){
			if(!preg_match("#{$ligne["pattern"]}#i", $HTTP_USER_AGENT)){
				pack_debug("{$ligne["pattern"]} \"$HTTP_USER_AGENT\" Won -> No match",__FUNCTION__,__LINE__);
				return true;
			}
			
		}else{
			if(preg_match("#{$ligne["pattern"]}#i", $HTTP_USER_AGENT)){
				pack_debug("{$ligne["pattern"]} \"$HTTP_USER_AGENT\" Won -> Match",__FUNCTION__,__LINE__);
				return true;
			}
		}
	}
	
	pack_debug("\"$HTTP_USER_AGENT\" no rule match, abort -> FALSE",__FUNCTION__,__LINE__);
}

function matches_srcdomain($gpid,$negation){
	$q=new mysql_squid_builder();
	$TO_MATCH=$GLOBALS["HOSTNAME"];
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["pattern"]=str_replace(".", "\.", $ligne["pattern"]);
		if($negation==1){
			if(!preg_match("#{$ligne["pattern"]}#i", $TO_MATCH)){return true;}
		}else{
			if(preg_match("#{$ligne["pattern"]}#i", $TO_MATCH)){return true;}
		}
	}	
	
}
function matches_src($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	
	pack_debug("Checks \"$gpid\" Negation \"{$negation}\"",__FUNCTION__,__LINE__);
	
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){
		pack_debug("No item associated to this group $gpid",__FUNCTION__,__LINE__);
		return false;
	}
	if($negation==1){$exclam="!";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){return;}
		
		pack_debug("Checks \"$pattern\" against \"{$GLOBALS["IPADDR"]}\"",__FUNCTION__,__LINE__);
		
		if(preg_match("#^[0-9\.]+\/[0-9]+$#", $pattern,$re)){
			if($negation==1){
					if(!$ip->isInRange($GLOBALS["IPADDR"], $pattern)){
						pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" not in range \"{$pattern}\" WON",__FUNCTION__,__LINE__);
						return true;
					}
			}
			if($ip->isInRange($GLOBALS["IPADDR"], $pattern)){
				pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" is in range \"{$pattern}\" WON",__FUNCTION__,__LINE__);
				return true;
			}
			pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" range \"{$pattern}\" NO MATCH",__FUNCTION__,__LINE__);
			continue;
		}
		
		if(preg_match("#^[0-9\.]+-[0-9\.]+$#", $pattern,$re)){
			if($negation==1){if(!$ip->isInRange($GLOBALS["IPADDR"], $pattern)){
					pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" not in range \"{$pattern}\" WON",__FUNCTION__,__LINE__);
					return true;
				}
			}
			if($ip->isInRange($GLOBALS["IPADDR"], $pattern)){
					pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" is in range \"{$pattern}\" WON",__FUNCTION__,__LINE__);
					return true;
			}
			pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" range \"{$pattern}\" NO MATCH",__FUNCTION__,__LINE__);
			continue;
		}		

		if ($ip->isIPAddress($pattern)){
			if($negation==1){
				if($GLOBALS["IPADDR"]<>$pattern){
					pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" IP NOT \"{$pattern}\" WON",__FUNCTION__,__LINE__);
					return true;
				}
			}
			if($GLOBALS["IPADDR"]==$pattern){
				pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" IP IS \"{$pattern}\" WON",__FUNCTION__,__LINE__);
				return true;
			}
			pack_debug("Checks \"{$GLOBALS["IPADDR"]}\" == \"{$pattern}\" NO MATCH",__FUNCTION__,__LINE__);
			continue;
		}




		pack_debug("Not supported pattern $pattern",__FUNCTION__,__LINE__);
	}
	
	pack_debug("Group $gpid, nothing match",__FUNCTION__,__LINE__);
	return false;
}

function matches_time($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	if($negation==1){$exclam="!";}	
	$result=false;
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){return;}
		
		$pattern=base64_decode($ligne["other"]);
		$TimeSpace=unserialize(base64_decode($ligne["other"]));
		if(!is_array($TimeSpace)){
			writelogs("Not supported pattern !is_array",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		$fromtime=$TimeSpace["H1"];
		$tottime=$TimeSpace["H2"];
		
		if($fromtime=="00:00" && $tottime=="00:00"){
			writelogs("From: $fromtime to $tottime not supported...",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		
		$timerange1=strtotime(date("Y-m-d $fromtime:00"));
		$timerange2=strtotime(date("Y-m-d $tottime:00"));
		$timerange0=time();
		$days=array("0"=>"1","1"=>"2","2"=>"3","3"=>"4","4"=>"5","5"=>"6","6"=>"7");		
		while (list ($key, $ligne) = each ($TimeSpace) ){if(preg_match("#^day_([0-9]+)#", $key,$re)){$dayT=$re[1];if($ligne<>1){continue;}$dd[$days[$dayT]]=true;}}
		
		$CurrentDay=date('D');
		if($negation==1){
			if(!isset($dd[$CurrentDay])){
				if($timerange0 <= $timerange1){$result=true;}
				
			}
			continue;
		}
		if(isset($dd[$CurrentDay])){
			if($timerange0>=$timerange1){
				if($timerange0<=$timerange2){
					$result=true;
				}
			}
		}
	}
	return $result;
	
}

function build_whitelist($ID){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM wpad_rules WHERE ID='$ID'"));
	$dntlhstname=$ligne["dntlhstname"];
	$isResolvable=$ligne["isResolvable"];
	$f=array();
	
	if($dntlhstname==1){ $f[]="\tif ( isPlainHostName(host) ) { return \"DIRECT\"; }"; }
	if($isResolvable==1){ $f[]="\tif( isResolvable(host) ) { return \"DIRECT\"; }"; }
	
	
	
	$sql="SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
	wpad_white_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_white_link,webfilters_sqgroups
	WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
	AND wpad_white_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_white_link.zorder";
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		pack_debug("Fatal !! $ID $q->mysql_error",__FILE__,__LINE__);
		@implode("\n", $f);
	}
	
	$CountObjects=mysql_num_rows($results);
	if($CountObjects==0){
		pack_debug("Rule:[$ID] No whitelist groups set",__FUNCTION__,__LINE__);
		return @implode("\n", $f);
	}
	
	pack_debug("Rule:[$ID] $CountObjects Object(s)",__FUNCTION__,__LINE__);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$not=false;
		$matches=false;
		$GroupName=$ligne["GroupName"];
		$negation=$ligne["negation"];
		if($negation==1){$not=true;}
		
		pack_debug("Rule:[$ID] Whitelisted group {$GroupName}[$gpid] Type:{$ligne["GroupType"]} Negation:$negation",__FUNCTION__,__LINE__);
		
		if($ligne["GroupType"]=="port"){ $f[]=build_whitelist_port($gpid,$negation); continue;}
		if($ligne["GroupType"]=="dstdomain"){ $f[]=build_whitelist_dstdomain($gpid,$negation); continue;}
		if($ligne["GroupType"]=="src"){ $f[]=build_whitelist_src($gpid,$negation); continue;}
		if($ligne["GroupType"]=="dst"){ $f[]=build_whitelist_dst($gpid,$negation); continue;}
		if($ligne["GroupType"]=="srcdomain"){ $f[]=build_whitelist_srcdomain($gpid,$negation); continue;}
		if($ligne["GroupType"]=="time"){ $f[]=build_whitelist_time($gpid,$negation); continue;}
		writelogs("Not supported Group {$ligne["GroupType"]} - $GroupName",__FUNCTION__,__FILE__,__LINE__);
	}
	
	return @implode("\n", $f);
}

function build_whitelist_port($gpid,$negation){
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	if($negation==1){$exclam="!";}
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		if(!is_numeric($pattern)){return;}
		$f[]="\tif( DestPort{$exclam}==\":{$ligne["pattern"]}\"){  return \"DIRECT\"; }";
	}
	return @implode("\n", $f);
}

function build_whitelist_dstdomain($gpid,$negation){
	$q=new mysql_squid_builder();
	$fam=new familysite();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}	
	if($negation==1){$exclam="!";}
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim(strtolower($ligne["pattern"]));
		$Family=$fam->GetFamilySites($pattern);
		pack_debug("Group::[$gpid] Item: \"$pattern\" -> $Family",__FUNCTION__,__LINE__);
		
		
		if(strpos(" $pattern", "*")>0){
			if(preg_match("#^\^(.+)#", $ligne["pattern"],$re)){$pattern=$re[1];}
			$f[]="\tif( shExpMatch(host ,\"$pattern\") ){ return \"DIRECT\";}";
			continue;
		}
		
		if(preg_match("#^\^(.+)#", $ligne["pattern"],$re)){
			$f[]="\tif( {$exclam}dnsDomainIs(host, \"{$re[1]}\") ){  return \"DIRECT\"; }";
			continue;
		}
		if($Family==$ligne["pattern"]){
			if(!preg_match("#^\.#", $ligne["pattern"])){
				$f[]="\tif( {$exclam}dnsDomainIs(host, \".{$ligne["pattern"]}\") ){  return \"DIRECT\"; }";
				continue;
			}
		}
		
		$f[]="\tif( {$exclam}dnsDomainIs(host, \"{$ligne["pattern"]}\") ){  return \"DIRECT\"; }";
	}
	return @implode("\n", $f);
}

function build_whitelist_dst($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){
		pack_debug("Group:[$gpid] FATAL !! $q->mysql_error",__FUNCTION__,__LINE__);
		return false;
	}
	
	$CountObjects=mysql_num_rows($results);
	if($CountObjects==0){
		pack_debug("Group::[$gpid] No object defined",__FUNCTION__,__LINE__);
		return false;
	}	
	if($negation==1){$exclam="!";}
	$f=array();
	pack_debug("Group::[$gpid] $CountObjects object(s) defined",__FUNCTION__,__LINE__);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		pack_debug("Group::[$gpid] Item: \"$pattern\"",__FUNCTION__,__LINE__);
		
		if($pattern==null){return;}
		
		if(preg_match("#^([0-9\.]+)-([0-9\.]+)$#", $pattern,$re)){
			$pattern=GetRange($pattern);
			pack_debug("Group::[$gpid] Item: \"{$ligne["pattern"]}\" -> $pattern",__FUNCTION__,__LINE__);
		}
		
		if(preg_match("#^([0-9\.]+)\/[0-9]+$#", $pattern,$re)){
			$ipaddr=$re[1];
			$netmask=cdirToNetmask($pattern);
			if(!preg_match("#^[0-9\.]+$#", $netmask)){
				pack_debug("ERROR CAN'T PARSE $pattern to netmask",__FILE__,__LINE__);
				continue;
			}
			if($netmask==null){$netmask="255.255.255.0";}
			$f[]="\tif( {$exclam}isInNet(hostIP, \"$ipaddr\", \"$netmask\") ){ return \"DIRECT\";}";
			continue;
		}		
		
			
		
		
		if ($ip->isIPAddress($pattern)){
			$f[]="\tif ( isInNet(hostIP, \"$pattern\",\"255.255.255.0\") ) { return \"DIRECT\";}";
			continue;
		}
		

		
		
		writelogs("Not supported pattern $pattern",__FUNCTION__,__FILE__,__LINE__);
	}
	return @implode("\n", $f);
}

function build_whitelist_src($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	if($negation==1){$exclam="!";}
	$f=array();

	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){return;}
		
		if(preg_match("#^([0-9\.]+)-([0-9\.]+)$#", $pattern,$re)){
			$pattern=GetRange($pattern);
			pack_debug("Group::[$gpid] Item: \"{$ligne["pattern"]}\" -> $pattern",__FUNCTION__,__LINE__);
		}
		
		
		if(preg_match("#^([0-9\.]+)\/[0-9]+$#", $pattern,$re)){
			$ipaddr=$re[1];
			$netmask=cdirToNetmask($pattern);
			
			if($GLOBALS["VERBOSE"]){$f[]="// --- $pattern -> $ipaddr netmask:$netmask [".__LINE__."]";}
			if(!preg_match("#^[0-9\.]+$#", $netmask)){
				pack_debug("ERROR CAN'T PARSE $pattern to netmask",__FILE__,__LINE__);
				continue;
			}
			if($netmask==null){$netmask="255.255.255.0";}
			$f[]="\tif( {$exclam}isInNet(myip, \"$ipaddr\", \"$netmask\") ){ return \"DIRECT\";}";
			continue;
		}		


		if ($ip->isIPAddress($pattern)){
			if($GLOBALS["VERBOSE"]){$f[]="// --- $pattern -> isIPAddress(TRUE) [".__LINE__."]";}
			$f[]="\tif( {$exclam}isInNet(myip, \"$pattern\", \"255.255.255.255\") ){ return \"DIRECT\";}";
			continue;
		}




		pack_debug("Not supported pattern $pattern",__FILE__,__LINE__);
	}
	return @implode("\n", $f);
}

function build_whitelist_srcdomain($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	if($negation==1){$exclam="!";}
	$f=array();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){return;}
	
		if ($ip->isIPAddress($pattern)){
			$f[]="\tif( hostIP {$exclam}== \"$pattern\") { return \"DIRECT\";}";
			continue;
		}
		
		if(substr($pattern, 0,1)=='.'){
			if(strpos($pattern, "*")>0){
				$f[]="\tif( shExpMatch(host ,\"$pattern\") ) { return \"DIRECT\";}";
				continue;
			}
			
			$f[]="\tif( dnsDomainIs(host ,\"$pattern\") ){ return \"DIRECT\";}";
			continue;
			
		}
		
		$f[]="\tif( host {$exclam}== \"$pattern\") { return \"DIRECT\";}";

		
	}
	return @implode("\n", $f);	
	
}

function build_whitelist_time($gpid,$negation){
	$ip=new IP();
	$q=new mysql_squid_builder();
	$sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	$exclam=null;
	if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
	if(mysql_num_rows($results)==0){return false;}
	if($negation==1){$exclam="!";}
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){return;}
	
		$pattern=base64_decode($ligne["other"]);
		$TimeSpace=unserialize(base64_decode($ligne["other"]));
		if(!is_array($TimeSpace)){
			writelogs("Not supported pattern !is_array",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		$fromtime=$TimeSpace["H1"];
		$tottime=$TimeSpace["H2"];
	
		if($fromtime=="00:00" && $tottime=="00:00"){
			writelogs("From: $fromtime to $tottime not supported...",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
	
	
		$timerange1=strtotime(date("Y-m-d $fromtime:00"));
		$timerange2=strtotime(date("Y-m-d $tottime:00"));
		$timerange0=time();
		
		
		
		$days=array("0"=>"MON","1"=>"TUE","2"=>"WED","3"=>"THU","4"=>"FRI","5"=>"SAT","6"=>"SUN");
		while (list ($key, $ligne) = each ($TimeSpace) ){
			if(preg_match("#^day_([0-9]+)#", $key,$re)){
				$dayT=$re[1];
				if($ligne<>1){continue;}
				$f[]="\tif( {$exclam}weekdayRange(\"{$days[$dayT]}\") ){";
				$f[]="\t\t{$exclam}timeRange(".date("H",$timerange1).",". date("i",$timerange1).", 0,".date("H",$timerange2).",".date("i",$timerange2).", 0) ){";
				$f[]="\t\t\treturn \"DIRECT\";";
				$f[]="\t\t}";
				$f[]="\t}";
				
			}
		}

	}
	return @implode("\n", $f);
	
}

function GetRange($net){
	
	if(preg_match("#(.+?)-(.+)#", $net,$re)){
		$ip=new IP();
		return $ip->ip2cidr($re[1],$re[2]);
		
		
	}
	
}

function cdirToNetmask($net){
	$results2=array();
	
	if(preg_match("#(.+?)\/(.+)#", $net,$re)){
		$ip=new ipv4($re[1],$re[2]);
		$netmask=$ip->netmask();
		$ipaddr=$ip->address();

		if(preg_match("#[0-9\.]+#", $netmask)){
			return $netmask;
		}
		
		pack_debug("$net -> $ipaddr - $netmask ",__FILE__,__LINE__);
	}
	
	
	
	
	exec("/usr/share/artica-postfix/bin/ipcalc $net 2>&1");
	pack_debug("/usr/share/artica-postfix/bin/ipcalc $net 2>&1",__FILE__,__LINE__);
	while (list ($index, $line) = each ($results2) ){
		if(preg_match("#Netmask:\s+([0-9\.]+)#", $line,$re)){return $re[1];break;}
	}

}

function build_proxies($ID,$FinishbyDirect=0){
	$sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID ORDER BY zorder";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return "\n\treturn \"DIRECT\";";}
	if(mysql_num_rows($results)==0){return "\n\treturn \"DIRECT\";";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
			$g[]="PROXY {$ligne["proxyserver"]}:{$ligne["proxyport"]};";
		}
	
	
		
		
	if(count($g)==0){return "\n\treturn \"DIRECT\";";}
	if($FinishbyDirect==1){$g[]="DIRECT";}
	
	return "\n\treturn \"".@implode(" ", $g)."\";";
}

function packsyslog($text,$function=null,$line=0){
	$servername=$_SERVER["SERVER_NAME"];
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}
	
	
	
	$LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
	ToSyslog($LineToSyslog);
	
	
}

function pack_debug($text,$function,$line){

	if($GLOBALS["VERBOSE"]){echo "<code style='font-size:14px'>$function:[$line] $text<br>\n";}
	if($GLOBALS["PROXY_PAC_DEBUG"]==0){return;}
	
	$servername=$_SERVER["SERVER_NAME"];
	$LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
	ToSyslog($LineToSyslog);
	
	$logFile="/var/log/apache2/proxy.pack.debug";
	
	$from=$_SERVER["REMOTE_ADDR"];
	$lineToSave=date('H:i:s')." [$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
	
	if (is_file($logFile)) { $size=@filesize($logFile); if($size>900000){@unlink($logFile);} }
	
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
}
function pack_error($text,$function,$line){
	
	$logFile="/var/log/apache2/proxy.pack.error";
	$servername=$_SERVER["SERVER_NAME"];
	$from=$_SERVER["REMOTE_ADDR"];
	$lineToSave=date('H:i:s')." [$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
	$LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
	if (is_file($logFile)) { $size=@filesize($logFile); if($size>900000){@unlink($logFile);} }

	$f = @fopen($logFile, 'a');
	if(!$f){ ToSyslog($LineToSyslog); return; }
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
}

function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("proxy.pac", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}
///* Don't proxy local hostnames */ if (isPlainHostName(host)) { return 'DIRECT'; }
//  if (dnsDomainLevels(host) > 0) { // if the number of dots in host > 0
//  if (isResolvable(host))
?>