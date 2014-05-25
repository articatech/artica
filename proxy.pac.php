<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--verbose"){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["TITLENAME"]="Dynamic Proxy PAC";
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


proxy_pac();

//SERVER_PROTOCOL //SERVER_SOFTWARE //REMOTE_ADDR !
// HTTP_USER_AGENT = Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:20.0) Gecko/20100101 Firefox/20.0
// HTTP_ACCEPT_LANGUAGE = fr,fr-fr;q
// HTTP_VIA = 1.1 squid32-64.localhost.localdomain
function proxy_pac(){
	
	$ClassiP=new IP();
	$sock=new sockets();
	$GLOBALS["PROXY_PAC_DEBUG"]=$sock->GET_INFO("ProxyPacDynamicDebug");
	if(!is_numeric($GLOBALS["PROXY_PAC_DEBUG"])){$GLOBALS["PROXY_PAC_DEBUG"]=0;}
	if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	$GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
	$HTTP_USER_AGENT=trim($_SERVER["HTTP_USER_AGENT"]);
	if(strpos($IPADDR, ",")>0){$FR=explode(",",$IPADDR);$IPADDR=trim($FR[0]);}
	
	$q=new mysql_squid_builder();
	
	if(!$ClassiP->isIPAddress($IPADDR)){
		$GLOBALS["HOSTNAME"]=$IPADDR;
		$IPADDR=gethostbyname($IPADDR);
		
	}else{
		$GLOBALS["HOSTNAME"]=gethostbyaddr($IPADDR);
	}
	$GLOBALS["IPADDR"]=$IPADDR;
	//srcdomain
	
	
	pack_debug("Connection FROM: $IPADDR [ $HTTP_USER_AGENT ] ",__FUNCTION__,__LINE__);
	
	
	$sql="SELECT * FROM wpad_rules ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){die();}
	header("content-type: application/x-ns-proxy-autoconfig");
	$date=date("Y-m-d H:i:s");
	$md5=md5("$date$IPADDR$HTTP_USER_AGENT");
	$HTTP_USER_AGENT=mysql_escape_string2($HTTP_USER_AGENT);

	while ($ligne = mysql_fetch_assoc($results)) {
		$rulename=$ligne["rulename"];
		$ID=$ligne["ID"];
		pack_debug("Parsing rule: \"$rulename\" ID:$ID",__FUNCTION__,__LINE__);
		if(!client_matches($ID)){
			pack_debug("client_matches() resturn false,No source match rule $rulename ID $ID, check other rule",__FUNCTION__,__LINE__);
			continue;
		}
		
		pack_debug("$rulename matches source {$GLOBALS["IPADDR"]} building script..",__FUNCTION__,__LINE__);
		$f=array();
		$f[]="function FindProxyForURL(url, host) {";
		$f[]="\turl = url.toLowerCase();";
		$f[]="\thost = host.toLowerCase();";
		$f[]="\tvar hostIP = dnsResolve(host);";
		$f[]="\tvar myip=myIpAddress();";
		$f[]="\tvar PROTO='';";
		$f[]="\tif (url.substring(0, 5) == 'http:' ){ PROTO='HTTP'; }";
		$f[]="\tif (url.substring(0, 6) == 'https:' ){ PROTO='HTTPS'; }";
		$f[]="\tif (url.substring(0, 5) == 'ftp:' ){ PROTO='FTP'; }";
		$f[]="\tif ( isInNet(hostIP, \"127.0.0.1\", \"255.255.255.255\") ) { return 'DIRECT';}";
		$f[]="\tif( host  == \"localhost\") { return 'DIRECT';}";
		pack_debug("$rulename/$ID building build_whitelist($ID)",__FUNCTION__,__LINE__);
		$f[]=build_whitelist($ID);
		pack_debug("$rulename/$ID building build_subrules($ID)",__FUNCTION__,__LINE__);
		$f[]=build_subrules($ID);
		pack_debug("$rulename/$ID building build_proxies($ID)",__FUNCTION__,__LINE__);
		$f[]=build_proxies($ID);
		$f[]="}\r\n";
		
		$script=@implode("\r\n", $f);
		pack_debug("SUCCESS $rulename sends script ". strlen($script)." bytes to client",__FUNCTION__,__LINE__);
		echo $script;
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

function build_subrules($ID){
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM wpad_destination_rules WHERE aclid=$ID ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$destinations=array();
		$values=array();
		$value=trim($ligne["value"]);
		if($value==null){continue;}
		$xtype=$ligne["xtype"];
		
		if(strpos($value, "\n")==0){$values[]=$value;}
		$explode=explode("\n", $value);
		while (list ($num, $ligne) = each ($explode) ){$ligne=trim($ligne);if($ligne==null){continue;}$values[]=$ligne;}
		
		$value=trim($ligne["destinations"]);
		if($value<>null){
			if(strpos($value, "\n")==0){$destinations[]=$value;}
			$explode=explode("\n", $value);
			while (list ($num, $ligne) = each ($explode) ){$ligne=trim($ligne);if($ligne==null){continue;}$destinations[]=$ligne;}
		}
	
		if(count($destinations)==0){$destinations_final="DIRECT";}
		if(count($destinations)>0){$destinations_final=@implode("; ", $destinations);}
		
		if($xtype=="shExpMatchRegex"){
			while (list ($num, $pattern) = each ($values) ){
				$f[]="\tvar regexpr = /$pattern/;";
				$f[]="\tif( regexpr.test( url ) ){ return \"$destinations_final\"; }";
				$f[]="\tif( regexpr.test( host ) ){ return \"$destinations_final\"; }";
			}
			continue;
		}
			
		if($xtype=="shExpMatch"){
			while (list ($num, $pattern) = each ($values) ){
				$f[]="\tif( shExpMatch( url,\"$pattern\" ) ){ return \"$destinations_final\"; }";
			}
			continue;
		}
		
		if($xtype=="isInNetMyIP"){
			while (list ($num, $pattern) = each ($values) ){
				$xt=explode("-",$pattern);
				$xt[0]=trim($xt[0]);
				$xt[1]=trim($xt[1]);
				$f[]="\tif( isInNet( myip, \"{$xt[1]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
			}
			continue;
		}	

		if($xtype=="isInNet"){
			while (list ($num, $pattern) = each ($values) ){
				$xt=explode("-",$pattern);
				$xt[0]=trim($xt[0]);
				$xt[1]=trim($xt[1]);
				$f[]="\tif( isInNet( hostIP, \"{$xt[1]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
			}
			continue;
		}		

	
	}
	
	return @implode("\r\n", $f);
	
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
	
	
	if($dntlhstname==1){ $f[]="if (isPlainHostName(host) ) { return 'DIRECT'; }"; }
	if($isResolvable==1){ $f[]="if( isResolvable(host) ) { return 'DIRECT'; }"; }
	
	
	
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
		pack_debug("FATAL !! $ID $q->mysql_error",__FILE__,__LINE__);
		return null;
	}
	
	$CountObjects=mysql_num_rows($results);
	if($CountObjects==0){
		pack_debug("Rule:[$ID] No whitelist groups set",__FUNCTION__,__LINE__);
		return null;
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
		
		if($ligne["GroupType"]=="dstdomain"){ $f[]=build_whitelist_dstdomain($gpid,$negation); continue;}
		if($ligne["GroupType"]=="src"){ $f[]=build_whitelist_src($gpid,$negation); continue;}
		if($ligne["GroupType"]=="dst"){ $f[]=build_whitelist_dst($gpid,$negation); continue;}
		if($ligne["GroupType"]=="srcdomain"){ $f[]=build_whitelist_srcdomain($gpid,$negation); continue;}
		if($ligne["GroupType"]=="time"){ $f[]=build_whitelist_time($gpid,$negation); continue;}
		writelogs("Not supported Group {$ligne["GroupType"]} - $GroupName",__FUNCTION__,__FILE__,__LINE__);
	}
	
	return @implode("\n", $f);
}

function build_whitelist_dstdomain($gpid,$negation){
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
		if(substr($ligne["pattern"], 0,1)<>'.'){$ligne["pattern"]=".{$ligne["pattern"]}";}
		$f[]="\tif( {$exclam}dnsDomainIs(host, \"{$ligne["pattern"]}\") ){  return 'DIRECT'; }";
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
			$f[]="\tif( {$exclam}isInNet(hostIP, \"$ipaddr\", \"$netmask\") ){ return 'DIRECT';}";
			continue;
		}		
		
			
		
		
		if ($ip->isIPAddress($pattern)){
			$f[]="\tif ( isInNet(hostIP, \"$pattern\") ) { return 'DIRECT';}";
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
			if(!preg_match("#^[0-9\.]+$#", $netmask)){
				pack_debug("ERROR CAN'T PARSE $pattern to netmask",__FILE__,__LINE__);
				continue;
			}
			$f[]="\tif( {$exclam}isInNet(hostIP, \"$ipaddr\", \"$netmask\") ){ return 'DIRECT';}";
			continue;
		}		


		if ($ip->isIPAddress($pattern)){
			$f[]="\tif( hostIP {$exclam}== \"$pattern\") { return 'DIRECT';}";
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
			$f[]="\tif( hostIP {$exclam}== \"$pattern\") { return 'DIRECT';}";
			continue;
		}
		
		if(substr($pattern, 0,1)=='.'){
			$f[]="\tif( dnsDomainIs(host ,\"$pattern\") { return 'DIRECT';}";
			continue;
			
		}
		
		$f[]="\tif( host {$exclam}== \"$pattern\") { return 'DIRECT';}";

		
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
				$f[]="\t\t\treturn 'DIRECT';";
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

function build_proxies($ID){
	$sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID ORDER BY zorder";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return "\n\treturn 'DIRECT';";}
	if(mysql_num_rows($results)==0){return "\n\treturn 'DIRECT';";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
			$g[]="PROXY {$ligne["proxyserver"]}:{$ligne["proxyport"]}";
		}
	
	if(count($g)==0){return "\n\treturn 'DIRECT';";}
	return "\n\treturn \"".@implode(" ", $g)."\";";
}
function pack_debug($text,$function,$line){
	if($GLOBALS["PROXY_PAC_DEBUG"]==0){return;}
	$logFile="/var/log/apache2/proxy.pack.debug";
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