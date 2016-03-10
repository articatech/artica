<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsProxyMonitor==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	
	js();
	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("Infos.");
	$html="YahooWin4(890,'$page?popup=yes','Infos.')";
	echo $html;
}


function popup(){
	$q=new mysql();
	$sock=new sockets();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT max_size FROM squid_caches_center WHERE `enabled`=1 AND `remove`=0 ORDER BY max_size DESC","artica_backup"));
	$max_size=$ligne["max_size"];
	$ssl_text=null;
	$TP=false;
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(cache_size) as SumOfCache FROM squid_caches_center WHERE `enabled`=1 AND `remove`=0","artica_backup"));
	
	if(!$q->ok){echo $q->mysql_error_html();}
	$SumOfCache=$ligne["SumOfCache"]*1024;
	
	$SumOfCacheExplain=$tpl->_ENGINE_parse_body("{SumOfCacheExplain}");
	$SumOfCacheExplain=str_replace("%s", FormatBytes($SumOfCache), $SumOfCacheExplain)."<br><br>";
	
	
	$SquidDNSUseSystem=intval($sock->GET_INFO("SquidDNSUseSystem"));
	$SquidDNSUseLocalDNSService=intval($sock->GET_INFO("SquidDNSUseLocalDNSService"));
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
			
	if(intval($ligne["ICP"])==1){continue;}
	if(intval($ligne["Parent"])==1){continue;}
	if(intval($ligne["FTP_TRANSPARENT"])==1){continue;}
	if(intval($ligne["SOCKS"])==1){continue;}
	if(intval($ligne["ProxyProtocol"])==1){continue;}
	if(intval($ligne["FTP"])==1){continue;}
	if(intval($ligne["WANPROXY"])==1){continue;}
	
	if(intval($ligne["WCCP"])==1){
		$PORTS_TEXT[]="{your_cisco_is_able_ports}";
		continue;
	}
	
	
	if(intval($ligne["transparent"])==1){
		if(!$TP){
			$TP=true;
			$PORTS_TEXT[]="{your_proxy_transparent_explain}";
		}
	continue;}

	if(intval($ligne["TProxy"])==1){
		if(!$TP){
			$TP=true;
			$PORTS_TEXT[]="{your_proxy_transparent_explain}";
		}
		continue;}	
	
	if(intval($ligne["is_nat"])==1){
		$your_proxy_is_nat=$tpl->_ENGINE_parse_body("{your_proxy_is_nat}");
		$your_proxy_is_nat=str_replace("%port", $ligne["port"], $your_proxy_is_nat);
		$PORTS_TEXT[]=$your_proxy_is_nat;
		continue;
	}
	$your_proxy_normal=$tpl->_ENGINE_parse_body("{your_proxy_normal}");
	$your_proxy_normal=str_replace("%port", $ligne["port"], $your_proxy_normal);
	$PORTS_TEXT[]=$your_proxy_normal;
	if($ligne["UseSSL"]==1){
		$ssl_text="<br>{explain_proxy_ssl_cert}";
	}
	
	
}
	if($SquidDNSUseSystem==1){
		$resolv=new resolv_conf();
		$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
		if($EnableDNSMASQ==1){
			$dns[]="127.0.0.1";
			$dns[]=$resolv->MainArray["DNS1"];
		}
		else{
			$dns[]=$resolv->MainArray["DNS1"];
			$dns[]=$resolv->MainArray["DNS2"];
		}
		
	}else{
		if($SquidDNSUseLocalDNSService==1){$dns[]="127.0.0.1";}
		$q=new mysql_squid_builder();

		$results=$q->QUERY_SQL("SELECT * FROM dns_servers ORDER BY zOrder");
		while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["dnsserver"]==null){continue;}
			$dns[]=$ligne["dnsserver"];
		}
	}
	
	$proxy_store_max_files=$tpl->_ENGINE_parse_body("{proxy_store_max_files}");
	$proxy_store_max_files=str_replace("%F", FormatBytes($max_size), $proxy_store_max_files)."<br><br>";
	if($SquidCacheLevel==0){$proxy_store_max_files=null;$SumOfCacheExplain=null;}
	
	$html="
<div style='width:98%' class=form>
<div style='font-size:30px'>{how_to_use_this_proxy}:</div>
<div style='font-size:18px;margin:20px'>".@implode("<br>{or} ", $PORTS_TEXT)."$ssl_text</div>
<div style='font-size:30px;margin-top:30px'>{performance}:</div>
<div style='font-size:18px;margin:20px'>{proxy_using_the_dns} ".@implode(" {or} ", $dns)."</div>
<div style='font-size:30px;margin-top:30px'>{caching}:</div>
<div style='font-size:18px;margin:20px'>$proxy_store_max_files
$SumOfCacheExplain
{SquidCacheLevel{$SquidCacheLevel}}</div>		
</div>	
";
	
echo $tpl->_ENGINE_parse_body($html);
	
}
