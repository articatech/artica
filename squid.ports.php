<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}
if(isset($_GET["allow-80-js"])){allow_port_80_js();exit;}
if(isset($_GET["search"])){page_search();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["delete-port-js"])){delete_port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["nic"])){port_save();exit;}
if(isset($_POST["delete-port"])){port_delete();exit;}
if(isset($_GET["listen-port-popup"])){echo port_popup_main();exit;}
if(isset($_GET["allow-80-popup"])){echo allow_port_80_popup();exit;}
if(isset($_GET["certificate-refresh"])){echo certificate_refresh();exit;}



if(isset($_POST["SquidAllow80Port"])){echo allow_port_80_Save();exit;}

page();


function port_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_port}");
	$wccp=null;

	
	
	if($ID>0){
		
		if(!$q->FIELD_EXISTS("proxy_ports", "WCCP")){
			$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WCCP` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		if(!$q->FIELD_EXISTS("proxy_ports", "NoAuth")){
			$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `NoAuth` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `NoAuth` )");
			if(!$q->ok){echo $q->mysql_error."\n";}
		}
		
		
	
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port,WCCP FROM proxy_ports WHERE ID=$ID"));
		if(intval($ligne["WCCP"])==1){$wccp="WCCP:";}
		$title="$wccp{$ligne["ipaddr"]}:{$ligne["port"]}";
		
	}
	echo "YahooWin2('935','$page?port-popup=yes&ID=$ID','$title')";
}
function allow_port_80_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{allow_80443_port}");
	
	
	echo "YahooWin2('890','$page?allow-80-popup=yes','$title')";	
	
}

function allow_port_80_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	$html="<div style='widh:98%' class=form>".
			
		Paragraphe_switch_img("{allow_80443_port}", "{allow_80443_port_explain}","SquidAllow80Port",$SquidAllow80Port,null,850)."
		<p>&nbsp;</p>
		<div style='text-align:right'>
				<hr>
			". button("{apply}","Save$t()",30)."
		</div>		
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	YahooWin2Hide();
	Loadjs('squid.ports.80.progress.php');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidAllow80Port',document.getElementById('SquidAllow80Port').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function allow_port_80_Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidAllow80Port", $_POST["SquidAllow80Port"]);
	
}



function delete_port_js(){
	$page=CurrentPageName();
	$t=time();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port,nic FROM proxy_ports WHERE ID=$ID"));
		$title="{$ligne["nic"]}:{$ligne["port"]}";
		if($ligne["nic"]==null){$title=$tpl->javascript_parse_text("{listen_port}: {$ligne["port"]}");}
	}
	echo "
var xdel$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	if(document.getElementById('TABLE_SQUID_PORTS')){
		$('#'+document.getElementById('TABLE_SQUID_PORTS').value).flexReload();
	}
		
}			
function del$t(){
	if(!confirm('$delete $title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-port','$ID');
	XHR.sendAndLoad('$page', 'POST',xdel$t);				
}
	del$t();";
			
	
	
}

function port_delete(){
	$ID=intval($_POST["delete-port"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM proxy_ports WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM squid_balancers WHERE portid=$ID","artica_backup");
	CheckPointers();
	

	
}

function CheckPointers(){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND is_nat=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$sock=new sockets();
	if($ligne["tcount"]>0){$sock->SET_INFO("EnableTransparent27", 1);}else{$sock->SET_INFO("EnableTransparent27", 0); }
	
	$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND WCCP=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$sock=new sockets();
	if($ligne["tcount"]>0){$sock->SET_INFO("SquidWCCPEnabled", 1);}else{$sock->SET_INFO("SquidWCCPEnabled", 0); }
	
	$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND FTP=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$sock=new sockets();
	if($ligne["tcount"]>0){$sock->SET_INFO("ServiceFTPEnabled", 1);}else{$sock->SET_INFO("ServiceFTPEnabled", 0); }
	
	$sock=new sockets();
	$sock->getFrameWork("ftp-proxy.php?reconfigure-silent=yes");
	
}

function PatchTable(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("proxy_ports", "WCCP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WCCP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `WCCP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "NoAuth")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `NoAuth` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `NoAuth` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	
	if(!$q->FIELD_EXISTS("proxy_ports", "ICP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `ICP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `ICP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "PortName")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `PortName` VARCHAR(128) NOT NULL DEFAULT 'Newport',ADD INDEX( `PortName` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
		
	if(!$q->FIELD_EXISTS("proxy_ports", "Parent")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `Parent` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `Parent` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterCacheChilds")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterCacheChilds` smallint(1) NOT NULL DEFAULT '1',ADD INDEX( `SquidAsMasterCacheChilds` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterLogExtern")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterLogExtern` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `SquidAsMasterLogExtern` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterFollowxForward")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterFollowxForward` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "WANPROXY_PORT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WANPROXY_PORT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY_PORT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTP_TRANSPARENT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTP_TRANSPARENT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `FTP_TRANSPARENT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	if(!$q->FIELD_EXISTS("proxy_ports", "SOCKS")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SOCKS` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `SOCKS` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	

	
	if(!$q->FIELD_EXISTS("proxy_ports", "MIKROTIK_PORT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `MIKROTIK_PORT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY_PORT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "ICP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `ICP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `ICP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "transparent")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `transparent` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}

	
	if(!$q->FIELD_EXISTS("proxy_ports", "is_nat")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `is_nat` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "outgoing_addr")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `outgoing_addr` varchar(90) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `FTP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "WANPROXY")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WANPROXY` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "WANPROXY_PORT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WANPROXY_PORT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY_PORT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTP_TRANSPARENT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTP_TRANSPARENT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `FTP_TRANSPARENT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "SOCKS")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SOCKS` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `SOCKS` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "MIKROTIK_PORT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `MIKROTIK_PORT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY_PORT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyMaxClients")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyMaxClients` BIGINT(100) NOT NULL DEFAULT '64'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyTimeOuts")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyTimeOuts` INT(100) NOT NULL DEFAULT '360'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyDestinationTransferMode")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyDestinationTransferMode` VARCHAR(32) NOT NULL DEFAULT 'client'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPUserAuthMagic")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPUserAuthMagic` VARCHAR(128) NOT NULL DEFAULT '@user'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPUseMagicChar")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPUseMagicChar` VARCHAR(32) NOT NULL DEFAULT '@'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTPAllowMagicUser")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPAllowMagicUser` SMALLINT(1) NOT NULL DEFAULT '1'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}		
	if(!$q->FIELD_EXISTS("proxy_ports", "WanProxyMemory")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WanProxyMemory` SMALLINT(10) NOT NULL DEFAULT '256'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	if(!$q->FIELD_EXISTS("proxy_ports", "WanProxyCache")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WanProxyCache` SMALLINT(10) NOT NULL DEFAULT '1'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "ProxyProtocol")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `ProxyProtocol` SMALLINT(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}		
	
	
	
}



function port_popup(){
	$ID=intval($_GET["ID"]);
	$page=CurrentPageName();
	$tpl=new templates();
	if($ID==0){echo port_popup_main();return;}
	$sock=new sockets();
	PatchTable();
	$q=new mysql_squid_builder();
	
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT FTP,WANPROXY,MIKROTIK_PORT,transparent,TProxy,Parent,WCCP FROM proxy_ports WHERE ID=$ID"));
	$AS_TRANSPARENT=false;
	$WCCP=intval($ligne["WCCP"]);
	$Parent=intval($ligne["Parent"]);
	$FTP=intval($ligne["FTP"]);
	$WANPROXY=intval($ligne["WANPROXY"]);
	$transparent=intval($ligne["transparent"]);
	$MIKROTIK_PORT=intval($ligne["MIKROTIK_PORT"]);
	$TProxy=intval($ligne["TProxy"]);
	
	if($WANPROXY==1){
		$array["listen-port-popup"]='Wan Compressor Proxy';
		$array["options"]='{options}';
	
	
		while (list ($num, $ligne) = each ($array) ){
	
			if($num=="options"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ports.wanproxy.php?ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
				continue;
					
			}
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
		}
		echo build_artica_tabs($html, "main_proxy_listen_ports");
		return;
	}
	
	if($FTP==1){
		$array["listen-port-popup"]='FTP Proxy';
		$array["options"]='{options}';
		
	
		while (list ($num, $ligne) = each ($array) ){
				
			if($num=="options"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ports.ftp.php?ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
				continue;
					
			}

			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
		}
		echo build_artica_tabs($html, "main_proxy_listen_ports");
		return;
	}	
	
	if($Parent==1){
		$array["listen-port-popup"]='{master_proxy}';
		$array["options"]='{options}';
		$array["childs"]='{childs_proxy}';
		
		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="options"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ports.parent.php?ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
				continue;
					
			}
			
			
			if($num=="childs"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.children.php?popup=yes&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
				continue;
					
			}
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
		}
		echo build_artica_tabs($html, "main_proxy_listen_ports");
		return;
	}
	
	
	
	if($WCCP==0){
		if($transparent==0){
			if($MIKROTIK_PORT==0){
				if(!$sock->isFirehol()){echo port_popup_main();return;}
			}
		}
	}
	
	if($WCCP==0){
		
		if(intval($ligne["transparent"])==1){$AS_TRANSPARENT=true;}
		if(intval($ligne["TProxy"])==1){$AS_TRANSPARENT=true;}
		if(!$AS_TRANSPARENT){echo port_popup_main();return;}
	}
	
	
	if($WCCP==1){
		$array["listen-port-popup"]='{listen_port}';
		$array["wccp-options"]='{WCCP_NAME}';
	}
	
	
	
	if(($transparent==1) OR ($TProxy==1)  ){
		$array["listen-port-popup"]='{listen_port}';
		$array["include"]='{whitelisted_destination_networks}';
		$array["exclude"]="{whitelisted_src_networks}";
		
		// 1 === Destination
		// 0 === Source
	}
	

	

	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="wccp-options"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.wccpl3.php?port-id=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="mikrotik-options"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.mikrotik.php?port-id=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
	
	
		if($num=="include"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ports.ipwbl.php?include=1&port-id=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="exclude"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.ports.ipwbl.php?include=0&port-id=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_proxy_listen_ports");
}




function port_popup_main(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$btname="{add}";
	$t=time();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_port}");
	

	PatchTable();
	
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));
		$title="{$ligne["nic"]}:{$ligne["port"]}";
		if($ligne["nic"]==null){$title="{listen_port}: {$ligne["port"]}";}
		$btname="{apply}";
	}
	
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);

	$array[null]="{all}";
	$array2[null]="{all}";
	while (list ($eth, $none) = each ($interfaces) ){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}	
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	if($ligne["ipaddr"]==null){$ligne["ipaddr"]="0.0.0.0";}
	if($ligne["port"]==0){$ligne["port"]=rand(1024,63000);}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$wanproxy_error="&nbsp;";
	$users=new usersMenus();
	if(!$users->WANPROXY){
		$wanproxy_error="<p class=tex-error>{wanproxy_not_installed}</p>";
	}
	
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=3><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{enabled}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"],"Check$t()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>{service_name2}:</td>
		<td style='font-size:20px'>". field_text("PortName-$t", $ligne["PortName"],"font-size:20px;width:361px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{disable_authentication}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("NoAuth-$t", 1,$ligne["NoAuth"],"CheckTransparentT()")."</td>
		<td>&nbsp;</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:20px'>Transparent Proxy (Tproxy):</td>
		<td style='font-size:20px'>". Field_checkbox_design("TProxy-$t", 1,$ligne["TProxy"],"CheckTransparentT()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>Transparent Proxy (Mikrotik):</td>
		<td style='font-size:20px'>". Field_checkbox_design("MIKROTIK_PORT-$t", 1,$ligne["MIKROTIK_PORT"],"CheckMikrotik()")."</td>
		<td>&nbsp;</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:20px'>{transparent} (HTTP):</td>
		<td style='font-size:20px'>". Field_checkbox_design("transparent-$t", 1,$ligne["transparent"],"CheckTransparent()")."</td>
		<td>&nbsp;</td>
	</tr>
		<input type=hidden id='FTP_TRANSPARENT-$t' value=0>		
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{proxy_protocol}","{proxy_protocol_explain}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("ProxyProtocol-$t", 1,$ligne["ProxyProtocol"],"")."</td>
		<td>&nbsp;</td>
	</tr>				
				
				
				
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{enable_nat_compatibility}","{squid_enable_nat_compatibility_text}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("is_nat-$t", 1,$ligne["is_nat"],"CheckNat()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{WCCP_LAYER3}","{WCCP_LAYER3_EXPLAIN}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("WCCP-$t", 1,$ligne["WCCP"],"CheckWCCP()")."</td>
		<td>&nbsp;</td>
	</tr>	
				
	
	<tr>
		<td class=legend style='font-size:20px'>{parent_proxy}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("Parent-$t", 1,$ligne["Parent"],"CheckParent()")."</td>
		<td>&nbsp;</td>
	</tr>
			
				
				
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{WAN_PARENT}","{WAN_PARENT_EXPLAIN}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("WANPROXY-$t", 1,$ligne["WANPROXY"],"CheckWANProxy()")."</td>
		<td>$wanproxy_error</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:20px'>{icp_port}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("ICP-$t", 1,$ligne["ICP"],"CheckICP()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>FTP:</td>
		<td style='font-size:20px'>". Field_checkbox_design("FTP-$t", 1,$ligne["FTP"],"CheckFTPT()")."</td>
		<td>&nbsp;</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:20px;font-wieght:bold'>{listen_interface}:</td>
		<td style='font-size:20px'>". Field_array_Hash($array, "nic-$t",$ligne["nic"],"style:font-size:20px;font-wieght:bold")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px;font-wieght:bold'>{listen_port}:</td>
		<td style='font-size:20px'>". field_text("port-$t", $ligne["port"],"font-size:20px;width:90px;font-wieght:bold")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px;font-wieght:bold'>{proxy_port}:</td>
		<td style='font-size:20px'>". field_text("WANPROXY_PORT-$t", $ligne["WANPROXY_PORT"],"font-size:20px;width:90px;font-wieght:bold")."</td>
		<td>&nbsp;</td>
	</tr>
				
				
				
	<tr>
		<td class=legend style='font-size:20px'>{forward_interface}:</td>
		<td style='font-size:20px'>". Field_array_Hash($array, "outgoing_addr-$t",$ligne["outgoing_addr"],"style:font-size:20px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>". texttooltip("{UseSSL}","{listen_port_ssl_explain}").":</td>
		<td style='font-size:20px'>". Field_checkbox_design("UseSSL-$t", 1,$ligne["UseSSL"],"CheckUseSSL$t()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{use_certificate_from_certificate_center}:</td>
		<td style='font-size:20px'>
		
			<div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjaxSilent('squid_ports_popup_certificates','$page?certificate-refresh=yes&default={$ligne["sslcertificate"]}&t=$t');")."</div>
			<span id='squid_ports_popup_certificates'>
				<input type='hidden' id='squid_ports_popup_certificates_num' value='$t'>
				". Field_array_Hash($sslcertificates, "sslcertificate-$t",$ligne["sslcertificate"],"style:font-size:20px")."</span>
						
				</td>
		<td>". button("{new_certificate}","Loadjs('certificates.center.wizard.php')")."</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:20px'>{description}:</td>
		<td style='font-size:18px'>". field_text("xnote-$t", $ligne["xnote"],"font-size:20px;width:361px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button($btname,"Save$t()",32)."</td>
	</tr>						
	</table>
	
<script>
	var xSave$t=function (obj) {
		var NextID=0;
		var tempvalue=obj.responseText;
		if (tempvalue.length>5){alert(tempvalue);return;}
		var ID=$ID;
		
		if(ID==0){
			if(!isNaN(tempvalue)){
				ID=tempvalue;
				NextID=ID;
			}
		}
		
		
		if(document.getElementById('UseSSL-$t').checked){
			Loadjs('squid.ports.testssl.progress.php?ID='+ID);
			
		}
		

		
		if(NextID>0){
			YahooWin2Hide();
			Loadjs('$page?port-js=yes&ID='+NextID+'&t={$_GET["tt"]}');
		
		}
		
		if(document.getElementById('TABLE_SQUID_PORTS')){
			$('#'+document.getElementById('TABLE_SQUID_PORTS').value).flexReload();
		}
		
		
		

		
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ID','$ID');
		XHR.appendData('nic',document.getElementById('nic-$t').value);
		XHR.appendData('port',document.getElementById('port-$t').value);
		XHR.appendData('xnote',encodeURIComponent(document.getElementById('xnote-$t').value));
		XHR.appendData('outgoing_addr',document.getElementById('outgoing_addr-$t').value);
		XHR.appendData('sslcertificate',document.getElementById('sslcertificate-$t').value);
		XHR.appendData('PortName',encodeURIComponent(document.getElementById('PortName-$t').value));
		if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		if(document.getElementById('is_nat-$t').checked){XHR.appendData('is_nat',1);}else{XHR.appendData('is_nat',0);}
		if(document.getElementById('transparent-$t').checked){XHR.appendData('transparent',1);}else{XHR.appendData('transparent',0);}		
		if(document.getElementById('TProxy-$t').checked){XHR.appendData('TProxy',1);}else{XHR.appendData('TProxy',0);}
		if(document.getElementById('UseSSL-$t').checked){XHR.appendData('UseSSL',1);}else{XHR.appendData('UseSSL',0);}
		if(document.getElementById('WCCP-$t').checked){XHR.appendData('WCCP',1);}else{XHR.appendData('WCCP',0);}
		if(document.getElementById('Parent-$t').checked){XHR.appendData('Parent',1);}else{XHR.appendData('Parent',0);}
		if(document.getElementById('ICP-$t').checked){XHR.appendData('ICP',1);}else{XHR.appendData('ICP',0);}
		if(document.getElementById('FTP-$t').checked){XHR.appendData('FTP',1);}else{XHR.appendData('FTP',0);}
		if(document.getElementById('WANPROXY-$t').checked){XHR.appendData('WANPROXY',1);}else{XHR.appendData('WANPROXY',0);}
		if(document.getElementById('NoAuth-$t').checked){XHR.appendData('NoAuth',1);}else{XHR.appendData('NoAuth',0);}
		if(document.getElementById('MIKROTIK_PORT-$t').checked){XHR.appendData('MIKROTIK_PORT',1);}else{XHR.appendData('MIKROTIK_PORT',0);}
		if(document.getElementById('FTP_TRANSPARENT-$t').checked){XHR.appendData('FTP_TRANSPARENT',1);}else{XHR.appendData('FTP_TRANSPARENT',0);}
		if(document.getElementById('ProxyProtocol-$t').checked){XHR.appendData('ProxyProtocol',1);}else{XHR.appendData('ProxyProtocol',0);}
		
		
		
		XHR.appendData('WANPROXY_PORT',encodeURIComponent(document.getElementById('WANPROXY_PORT-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
	
	function CheckTransparent(){
		if(document.getElementById('transparent-$t').checked){
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
			DisableAllExcept('transparent-$t');
			
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
		}
	
	}
	
	function DisableAllExcept(id){
	
		if(id!=='MIKROTIK_PORT-$t'){
			document.getElementById('MIKROTIK_PORT-$t').checked=false;
			document.getElementById('MIKROTIK_PORT-$t').disabled=true;
		}
		
		if(id!=='TProxy-$t'){
			document.getElementById('TProxy-$t').checked=false;
			document.getElementById('TProxy-$t').disabled=true;
		}		
	
		if(id!=='is_nat-$t'){
			document.getElementById('is_nat-$t').checked=false;
			document.getElementById('is_nat-$t').disabled=true;
		}
		if(id!=='WCCP-$t'){
			document.getElementById('WCCP-$t').checked=false;
			document.getElementById('WCCP-$t').disabled=true;
		}
		
		if(id!=='Parent-$t'){
			document.getElementById('Parent-$t').checked=false;
			document.getElementById('Parent-$t').disabled=true;
		}
		if(id!=='ICP-$t'){
			document.getElementById('ICP-$t').checked=false;
			document.getElementById('ICP-$t').disabled=true;
		}		
		if(id!=='WANPROXY-$t'){
			document.getElementById('WANPROXY-$t').checked=false;
			document.getElementById('WANPROXY-$t').disabled=true;
		}
		if(id!=='FTP_TRANSPARENT-$t'){
			document.getElementById('FTP_TRANSPARENT-$t').checked=false;
			document.getElementById('FTP_TRANSPARENT-$t').disabled=true;
		}

		
	}
	
	function EnableAll(){
		document.getElementById('MIKROTIK_PORT-$t').disabled=false;
		document.getElementById('TProxy-$t').disabled=false;
		document.getElementById('NoAuth-$t').disabled=false;
		document.getElementById('is_nat-$t').disabled=false;
		document.getElementById('WCCP-$t').disabled=false;
		document.getElementById('Parent-$t').disabled=false;
		document.getElementById('ICP-$t').disabled=false;
		document.getElementById('WANPROXY-$t').disabled=false;
		document.getElementById('FTP_TRANSPARENT-$t').disabled=false;
		document.getElementById('ProxyProtocol-$t').disabled=false;
		
		
	}
	

	
	
	function CheckTransparentFTP(){
		
		if(document.getElementById('FTP_TRANSPARENT-$t').checked){
			DisableAllExcept('FTP_TRANSPARENT-$t');
			document.getElementById('WANPROXY_PORT-$t').disabled=false;
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
			document.getElementById('outgoing_addr-$t').disabled=false;
			document.getElementById('sslcertificate-$t').disabled=true;
			
		}else{
			EnableAll();
			document.getElementById('WANPROXY_PORT-$t').disabled=true;
			document.getElementById('NoAuth-$t').disabled=false;
			document.getElementById('sslcertificate-$t').disabled=false;
		}
	
	
	}
	
	function CheckTransparentT(){
		if(document.getElementById('TProxy-$t').checked){
			DisableAllExcept('TProxy-$t');
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
		}else{
			EnableAll();
			document.getElementById('NoAuth-$t').disabled=false;
		}
	}
	
	function CheckMikrotik(){
		if(document.getElementById('MIKROTIK_PORT-$t').checked){
			DisableAllExcept('MIKROTIK_PORT-$t');
			document.getElementById('outgoing_addr-$t').disabled=false;
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
			EnableAll();
		}
	}
	
	
	function CheckWCCP(){
		if(document.getElementById('WCCP-$t').checked){
			DisableAllExcept('WCCP-$t');
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
			document.getElementById('MIKROTIK_PORT-$t').disabled=false;
		}
	}
	
	function CheckNat(){
		if(document.getElementById('is_nat-$t').checked){
			DisableAllExcept('is_nat-$t');
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
			EnableAll();
		}
	}
	
	function CheckParent(){
		if(document.getElementById('Parent-$t').checked){
			DisableAllExcept('Parent-$t');
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
			document.getElementById('outgoing_addr-$t').disabled=false;
			
			
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
			EnableAll();
		}
	
	}
	
	function CheckICP(){
		if(document.getElementById('ICP-$t').checked){
			DisableAllExcept('ICP-$t');
			document.getElementById('outgoing_addr-$t').disabled=true;
			document.getElementById('sslcertificate-$t').disabled=true;
			document.getElementById('UseSSL-$t').disabled=true;
			
		}else{
			EnableAll();
			document.getElementById('outgoing_addr-$t').disabled=false;
			document.getElementById('sslcertificate-$t').disabled=false;
			document.getElementById('UseSSL-$t').disabled=false;
		}
	}
	
	function CheckFTPT(){
		if(document.getElementById('FTP-$t').checked){
			document.getElementById('MIKROTIK_PORT-$t').checked=false;
			document.getElementById('transparent-$t').checked=false;
			document.getElementById('TProxy-$t').checked=false;
			document.getElementById('WCCP-$t').checked=false;
			document.getElementById('Parent-$t').checked=false;
			document.getElementById('is_nat-$t').checked=false;
			document.getElementById('UseSSL-$t').checked=false;
			document.getElementById('ICP-$t').checked=false;
			document.getElementById('WANPROXY-$t').checked=false;
			document.getElementById('outgoing_addr-$t').disabled=true;
			document.getElementById('WANPROXY_PORT-$t').disabled=true;
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
			document.getElementById('MIKROTIK_PORT-$t').disabled=true;
			
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
		}
	}
	
	function CheckWANProxy(){
		if(document.getElementById('WANPROXY-$t').checked){
			document.getElementById('MIKROTIK_PORT-$t').checked=false;
			document.getElementById('transparent-$t').checked=false;
			document.getElementById('TProxy-$t').checked=false;
			document.getElementById('WCCP-$t').checked=false;
			document.getElementById('Parent-$t').checked=false;
			document.getElementById('is_nat-$t').checked=false;
			document.getElementById('UseSSL-$t').checked=false;
			document.getElementById('ICP-$t').checked=false;
			document.getElementById('FTP-$t').checked=false;
			document.getElementById('outgoing_addr-$t').disabled=true;
			document.getElementById('WANPROXY_PORT-$t').disabled=false;
			document.getElementById('NoAuth-$t').checked=false;
			document.getElementById('NoAuth-$t').disabled=true;
			document.getElementById('MIKROTIK_PORT-$t').disabled=true;
		}else{
			document.getElementById('NoAuth-$t').disabled=false;
			
		}	
	
	}
	
	
	function Check$t(){
		
		document.getElementById('nic-$t').disabled=true;
		document.getElementById('port-$t').disabled=true;
		document.getElementById('xnote-$t').disabled=true;
		document.getElementById('transparent-$t').disabled=true;
		document.getElementById('TProxy-$t').disabled=true;
		document.getElementById('PortName-$t').disabled=true;
		document.getElementById('outgoing_addr-$t').disabled=true;
		document.getElementById('sslcertificate-$t').disabled=true;
		document.getElementById('UseSSL-$t').disabled=true;
		document.getElementById('is_nat-$t').disabled=true;
		document.getElementById('WCCP-$t').disabled=true;
		document.getElementById('Parent-$t').disabled=true;
		document.getElementById('ICP-$t').disabled=true;
		document.getElementById('WANPROXY_PORT-$t').disabled=true;
		document.getElementById('FTP_TRANSPARENT-$t').disabled=true;
		document.getElementById('MIKROTIK_PORT-$t').disabled=true;
		document.getElementById('NoAuth-$t').disabled=true;
		document.getElementById('ProxyProtocol-$t').disabled=true;

		
		if(document.getElementById('enabled-$t').checked){
			document.getElementById('ProxyProtocol-$t').disabled=false;
			document.getElementById('FTP_TRANSPARENT-$t').disabled=false;
			document.getElementById('NoAuth-$t').disabled=false;
			document.getElementById('transparent-$t').disabled=false;
			document.getElementById('nic-$t').disabled=false;
			document.getElementById('port-$t').disabled=false;
			document.getElementById('xnote-$t').disabled=false;
			document.getElementById('PortName-$t').disabled=false;		
			document.getElementById('TProxy-$t').disabled=false;
			document.getElementById('outgoing_addr-$t').disabled=false;		
			document.getElementById('sslcertificate-$t').disabled=false;
			document.getElementById('UseSSL-$t').disabled=false;
			document.getElementById('is_nat-$t').disabled=false;
			document.getElementById('WCCP-$t').disabled=false;
			document.getElementById('Parent-$t').disabled=false;
			document.getElementById('ICP-$t').disabled=false;
			document.getElementById('WANPROXY-$t').disabled=false;
			document.getElementById('MIKROTIK_PORT-$t').disabled=false;
			if(document.getElementById('WANPROXY-$t').checked){document.getElementById('WANPROXY_PORT-$t').disabled=false;}
		}
		
		CheckGlobal$t();
	
	}
	
	function CheckUseSSL$t(){
		document.getElementById('sslcertificate-$t').disabled=false;
		if(document.getElementById('UseSSL-$t').checked){
			document.getElementById('sslcertificate-$t').disabled=false;
		}
	}
function CheckGlobal$t(){
	CheckNat();
	CheckWCCP();
	CheckParent();
	CheckICP();
	CheckFTPT();
	CheckWANProxy();
	CheckUseSSL$t();
	CheckMikrotik();
	CheckTransparentFTP();

}
Check$t();
CheckGlobal$t();
</script>					
				
				
";
	
	return $tpl->_ENGINE_parse_body($html);
	
}

function certificate_refresh(){
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$t=$_GET["t"];
	$default=$_GET["default"];
	echo Field_array_Hash($sslcertificates, "sslcertificate-$t",$default,"style:font-size:20px");
	
}

function port_save(){
	$users=new usersMenus();
	$ID=$_POST["ID"];
	$ipaddr=$_POST["ipaddr"];
	$port=intval($_POST["port"]);
	$xnote=mysql_escape_string2(url_decode_special_tool($_POST["xnote"]));
	$PortName=mysql_escape_string2(url_decode_special_tool($_POST["PortName"]));
	$enabled=$_POST["enabled"];
	$transparent=$_POST["transparent"];
	$TProxy=$_POST["TProxy"];
	$Parent=intval($_POST["Parent"]);
	$outgoing_addr=$_POST["outgoing_addr"];
	$UseSSL=$_POST["UseSSL"];
	$sslcertificate=$_POST["sslcertificate"];
	$WCCP=intval($_POST["WCCP"]);
	$ICP=intval($_POST["ICP"]);
	$FTP=intval($_POST["FTP"]);
	$WANPROXY=intval($_POST["WANPROXY"]);
	$WANPROXY_PORT=intval($_POST["WANPROXY_PORT"]);
	$FTP_TRANSPARENT=intval($_POST["FTP_TRANSPARENT"]);
	$MIKROTIK_PORT=intval($_POST["MIKROTIK_PORT"]);
	$SOCKS=intval($_POST["SOCKS"]);
	$NoAuth=intval($_POST["NoAuth"]);
	$ProxyProtocol=intval($_POST["ProxyProtocol"]);
	$nic=$_POST["nic"];
	$sock=new sockets();
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	$tpl=new templates();
	$SquidMgrListenPort=intval($sock->GET_INFO("SquidMgrListenPort"));
	
	if($port>65535){$port=65535;}
	if($WANPROXY_PORT>65635){$WANPROXY_PORT=65635;}
	if($MIKROTIK_PORT>65635){$MIKROTIK_PORT=65635;}
	
	if($MIKROTIK_PORT==1){
		$FTP=0;
		$transparent=0;
		$TProxy=0;
		$WCCP=0;
		$Parent=0;
		$FTP_TRANSPARENT=0;
		$_POST["is_nat"]=0;
		if($nic==null){
			echo $tpl->javascript_parse_text("{mikrotik_error_interface}");
			return;
		}
	}
	
	if($FTP_TRANSPARENT==1){
		$transparent=0;$TProxy=0;$WCCP=0;$Parent=0;$ICP=0;$FTP=0;
		if(!is_file("/usr/sbin/frox")){
			echo $tpl->javascript_parse_text("{module_in_squid_not_installed}");
			return;
			
		}
		
		if($WANPROXY_PORT==0){
			echo $tpl->javascript_parse_text("{error} {missing_valuefor}: {proxy_port}");
			return;
		}
		
	}
	

	
	if($FTP==1){
		$transparent=0;
		$TProxy=0;
		$WCCP=0;
		$Parent=0;
		$FTP_TRANSPARENT=0;
		$sslcertificate=null;
		$UseSSL=0;
		$nic=null;
		$outgoing_addr=null;
		$SquidAllow80Port=1;
		$_POST["is_nat"]=0;
		$ICP=0;
	}
	
	if($WANPROXY==1){
		if($WANPROXY_PORT<1024){echo $tpl->javascript_parse_text("{proxy_port_must_be_higher_1024}");return;}
		if(!$users->WANPROXY){echo $tpl->javascript_parse_text("{wanproxy_not_installed}");return;}
		$transparent=0;
		$TProxy=0;
		$WCCP=0;
		$Parent=0;
		$sslcertificate=null;
		$UseSSL=0;
		$nic=null;
		$outgoing_addr=null;
		$SquidAllow80Port=1;
		$_POST["is_nat"]=0;
		$ICP=0;
		$FTP=0;
		$FTP_TRANSPARENT=0;
		
	}
	
	if($SquidAllow80Port==0){
		if($port==80){
			echo "$port 80 HTTP port not allowed!\n";
			return;
		}
		
		if($port==21){
			echo "$port 21 FTP port not allowed!\n";
			return;
		}
		
		if($port==443){
			echo "$port 443 SSL port not allowed!\n";
			return;
		}	
	}
	
	if($port==$SquidMgrListenPort){
		echo "$port == Manager port $SquidMgrListenPort (not allowed!)\n";
		return;
		
	}
	

	
	if(intval($_POST["is_nat"])==1){
		$transparent=0;
		$TProxy=0;
		$WCCP=0;
		$WANPROXY_PORT=0;
		$FTP_TRANSPARENT=0;
	}
	
	if($ICP==1){
		$transparent=0;
		$TProxy=0;
		$WCCP=0;
		$Parent=0;
		$sslcertificate=null;
		$UseSSL=0;
		$nic=null;
		$outgoing_addr=null;
		$WANPROXY_PORT=0;
		$FTP_TRANSPARENT=0;
	}
	
	if($UseSSL==1){
		$ProxyProtocol=0;
	}
	
	
	
	if($WCCP==1){
		$WANPROXY_PORT=0;
		if($nic==null){
			echo $tpl->javascript_parse_text("{wccp_error_interface}");
			return;
		}
		
	}
	
	
	$is_nat=intval($_POST["is_nat"]);
	
	$sqladd="INSERT INTO proxy_ports (WANPROXY_PORT,WANPROXY,FTP,ICP,Parent,WCCP,is_nat,nic,ipaddr,port,xnote,enabled,transparent,TProxy,outgoing_addr,PortName,UseSSL,sslcertificate,NoAuth,MIKROTIK_PORT,FTP_TRANSPARENT,SOCKS,ProxyProtocol) 
	VALUES ('$WANPROXY_PORT','$WANPROXY','$FTP','$ICP','$Parent','$WCCP','$is_nat','$nic','$ipaddr','$port','$xnote','$enabled','$transparent','$TProxy','$outgoing_addr','$PortName','$UseSSL','$sslcertificate',$NoAuth,'$MIKROTIK_PORT','$FTP_TRANSPARENT','$SOCKS','$ProxyProtocol')";
	
	
	$q=new mysql_squid_builder();
	
	if($ID==0){
		
		$sql="SELECT ID,PortName FROM proxy_ports WHERE port='$port'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		$ID=intval($ligne["ID"]);
		
		if($ID>0){
			$PortName=$ligne["PortName"];
			echo $tpl->javascript_parse_text("$port: {error_port_already_usedby}:$PortName");
			return;
		}
		
	}
	
	
	$sqledit="UPDATE proxy_ports SET 
	WANPROXY_PORT='$WANPROXY_PORT',
	FTP_TRANSPARENT='$FTP_TRANSPARENT',
	WANPROXY='$WANPROXY',
	FTP='$FTP',
	ICP='$ICP',
	Parent='$Parent',
	TProxy='$TProxy',
	WCCP='$WCCP',
	is_nat='$is_nat',
	transparent='$transparent',
	ipaddr='$ipaddr',
	port='$port',
	nic='$nic',
	xnote='$xnote',
	MIKROTIK_PORT='$MIKROTIK_PORT',
	enabled='$enabled',nic='$nic',`is_nat`='$is_nat',
	outgoing_addr='$outgoing_addr',
	PortName='$PortName',
	SOCKS='$SOCKS',
	UseSSL='$UseSSL',
	sslcertificate='$sslcertificate',
	ProxyProtocol='$ProxyProtocol',
	`NoAuth`='$NoAuth'
	WHERE ID=$ID";
	
	

	
	$sock=new sockets();
	$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
	if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	
	if($port==$InfluxAdminPort){echo "Failed, reserved port Influx Admin Port\n";return;}
	if($port==9900){echo "Failed, reserved port WanProxy Monitor\n";return;}
	if($port==5432){echo "Failed, reserved port PostgreSQL query port\n";return;}
	if($port==8088){echo "Failed, reserved port Unifi HTTP Port\n";return;}
	if($port==8089){echo "Failed, reserved port Influx Meta Port\n";return;}
	if($port==8090){echo "Failed, reserved port Raft Port\n";return;}
	if($port==8099){echo "Failed, reserved port Influx Cluster Port\n";return;}
	if($port==13298){echo "Failed, reserved port Proxy NAT backend port\n";return;}
	if($SquidAllow80Port==0){if($port==21){echo "Failed, reserved port Local FTP service port\n";return;}}
	if($port==25){echo "Failed, reserved port Local SMTP service port\n";return;}
	if($port==31337){echo "Failed, reserved port Local RedSocks\n";return;}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "is_nat")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `is_nat` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "ICP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `ICP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `ICP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}

	
	if(!$q->FIELD_EXISTS("proxy_ports", "TProxy")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `TProxy` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "outgoing_addr")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `outgoing_addr` VARCHAR(90) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	if(!$q->FIELD_EXISTS("proxy_ports", "nic")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `nic` VARCHAR(20) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	
	if(!$q->FIELD_EXISTS("proxy_ports", "UseSSL")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `UseSSL` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}

	if(!$q->FIELD_EXISTS("proxy_ports", "sslcertificate")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `sslcertificate` VARCHAR(128) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	if(!$q->FIELD_EXISTS("proxy_ports", "connectionauth")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `connectionauth` smallint(1) NOT NULL DEFAULT 0");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}	
	if(!$q->FIELD_EXISTS("proxy_ports", "FTP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTP` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `FTP` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "WCCP")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WCCP` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "NoAuth")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `NoAuth` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `NoAuth` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}

	if(!$q->FIELD_EXISTS("proxy_ports", "WANPROXY")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WANPROXY` smallint(1) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "WANPROXY_PORT")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `WANPROXY_PORT` smallint(6) NOT NULL DEFAULT '0',ADD INDEX( `WANPROXY_PORT` )");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "ProxyProtocol")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `ProxyProtocol` SMALLINT(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if($ID>0){$q->QUERY_SQL($sqledit);}else{$q->QUERY_SQL($sqladd);}
	if(!$q->ok){echo $q->mysql_error;}else{echo $q->last_id;}
	CheckPointers();
	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_port}");
	$port=$tpl->javascript_parse_text("{listen_port}");
	$address=$tpl->javascript_parse_text("{listen_address}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$useSSL=$tpl->javascript_parse_text("{decrypt_ssl}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$transparent=$tpl->javascript_parse_text("{transparent}");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("proxy_ports")){$q->CheckTables(null,true);}
	$title="<strong style=font-size:30px>".$tpl->javascript_parse_text("{your_proxy}: {listen_ports}")."</strong>";

	
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$tt},
	{name: '<strong style=font-size:18px>Port 80/443</strong>', bclass: 'Settings', onpress : Port80$tt},
	{name: '<strong style=font-size:18px>MikrotiK</strong>', bclass: 'Settings', onpress : MikrotiK$tt},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt},
	],";
	
	$html="
	<input type='hidden' ID='TABLE_SQUID_PORTS' value='flexRT$tt'>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?search=yes&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'ID', width :70, sortable : true, align: 'center'},
	{display: '<strong style=font-size:18px>$address</strong>', name : 'ipaddr', width :274, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$port</strong>', name : 'port', width :499, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$transparent</strong>', name : 'transparent', width :184, sortable : true, align: 'center'},
	{display: '<strong style=font-size:18px>$useSSL</strong>', name : 'UseSSL', width :184, sortable : true, align: 'center'},
	{display: '<strong style=font-size:18px>$enabled</strong>', name : 'enabled', width : 108, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$port', name : 'port'},
	{display: '$address', name : 'ipaddr'},
	
	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}
	
var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
	Loadjs('squid.reconfigure.php?restart=yes');
}
	
	
function NewRule$tt(){
	Loadjs('$page?port-js=yes&ID=0&t=$tt');
}
function RuleDestinationDelete$tt(zmd5){
	if(!confirm('$delete')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-delete', zmd5);
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function MikrotiK$tt(){
	Loadjs('squid.mikrotik.php');
}


function Port80$tt(){
	Loadjs('$page?allow-80-js=yes');

}
	
	
function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
}
Start$tt();
</script>
";
echo $html;
}
function page_search(){
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql_squid_builder();
$sock=new sockets();
$t=$_GET["t"];
$search='%';
$table="proxy_ports";
$page=1;
$FORCE_FILTER=null;
$total=0;
$OKFW=true;

if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
if(isset($_POST['page'])) {$page = $_POST['page'];}

$searchstring=string_to_flexquery();
$WorkingPort=1;
	if($searchstring<>null){
		$WorkingPort=0;
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
	if(intval($sock->getFrameWork("firehol.php?is-installed=yes"))==0){
		$OKFW=false;
	}else{
		$FireHolConfigured=intval($sock->GET_INFO("FireHolConfigured"));
		if($FireHolConfigured==0){$OKFW=false;}
	}
	if($OKFW){
		$FireHolEnable=intval($sock->GET_INFO("FireHolEnable"));
		if($FireHolEnable==0){$OKFW=false;}
	}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+$WorkingPort;
	$data['rows'] = array();
	
	$ZPORTS=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
	while (list ($mkey, $ligne) = each ($ZPORTS) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#icp_port.*?([0-9]+)#", $ligne,$re)){$CONFIGURED_PORT[$re[1]]=true;continue;}
		if(!preg_match("#http.*?_port\s+.*?:([0-9]+)#", $ligne,$re)){continue;}
		$CONFIGURED_PORT[$re[1]]=true;
	}
	
	if($WorkingPort==1){
		$SquidMgrListenPort=intval($sock->GET_INFO("SquidMgrListenPort"));
		$data['rows'][] = array(
				'id' => "00",
				'cell' => array(
						"<center style='font-size:30px;font-weight:normal;color:#8a8a8a'>
						<img src='img/folder-network-48.png'></center>",
						"<span style='font-size:30px;;color:#8a8a8a'>0.0.0.0</span> ",
						"<span style='font-size:30px;;color:#8a8a8a'>$SquidMgrListenPort</span>&nbsp;<span style='font-size:18px;font-weight:bold;color:#8a8a8a'>".$tpl->javascript_parse_text("{internal_port} ({used_by_artica})")."</span>",
						"<center style='margin-top:3px;color:#8a8a8a'></a></center>",
						"<center style='margin-top:3px;color:#8a8a8a'></a></center>",
						"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:#8a8a8a'><img src='img/check-48.png'></center>",
						"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:#8a8a8a'>&nbsp;</center>",)
		);
		
	}
	
	

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("!!! no data");}
	$main_interface_not_selected=$tpl->javascript_parse_text("{main_interface_not_selected}");
	$wccp_interface_error=$tpl->javascript_parse_text("{wccp_interface_error}");
	$error_firwall_not_configured=$tpl->javascript_parse_text("{error_firwall_not_configuredisquid}");
	$error_need_to_apply_or_restart_squid=$tpl->javascript_parse_text("{error_need_to_apply_or_restart_squid}");
	$error=$tpl->javascript_parse_text("{error}");
	$tpl=new templates();
	$all=$tpl->javascript_parse_text("{all}");
	$TCP_NICS_STATUS_ARRAY=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$noauth_text=$tpl->javascript_parse_text("{disable_authentication}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$ID=$ligne["ID"];
		$zMD5=$ligne["zMD5"];
		$ipaddr=$ligne["ipaddr"];
		$port=$ligne["port"];
		$enabled=$ligne["enabled"];
		$text_ssl=null;
		$eth=$ligne["nic"];
		$UseSSL=intval($ligne["UseSSL"]);
		$icon="folder-network-48.png";
		$check="check-48.png"; //check-48-grey.png
		$xnote=utf8_encode($ligne["xnote"]);
		$script=imgsimple("script-24.png",null,"Loadjs('$MyPage?events-script=yes&zmd5={$ligne["zmd5"]}',true)");
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-port-js=yes&ID=$ID',true)");
		$transparent="&nbsp;";
		$PortName=$tpl->javascript_parse_text($ligne["PortName"]);
		$icon_ssl="&nbsp;";
		$tproxy_note=null;
		$firwall_error=null;
		$WANPROXY_PORT=0;
		$NOT_TEST=false;
		
		$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>({connected_port})</strong>");
		$noauthT=null;
		if($UseSSL==1){
			$icon_ssl=imgsimple("32-cert.png");
			$sslcertificate=$ligne["sslcertificate"];
			$sslcertificateEncode=urlencode($sslcertificate);
			$text_ssl=$tpl->_ENGINE_parse_body("<br><span style='font-size:16px'>{certificate}:</span> <a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('certificates.center.php?certificate-edit-js=yes&CommonName=$sslcertificateEncode');\"
			style='font-size:16px;text-decoration:underline'>$sslcertificate</a>");
		
		}
		
		if($ligne["enabled"]==0){
			$color="#A0A0A0";
			$check="check-48-grey.png";
			if($UseSSL==1){$icon_ssl=imgsimple("32-cert-grey.png");}
			
			}
			
		if($ligne["Parent"]==1){
			$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>({connected_port} - {parent_proxy})</strong>");
			
		}
		if($ligne["FTP"]==1){
			$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>(FTP)</strong>");
				
		}		

		if($ligne["ICP"]==1){
			$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>(UDP {icp_port} - {parent_proxy})</strong>");
			$NOT_TEST=true;
				
		}
		if($ligne["WANPROXY"]==1){
			$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>(TCP {WAN_PARENT} - {parent_proxy})</strong>");
			$NOT_TEST=true;
			$WANPROXY_PORT=$ligne["WANPROXY_PORT"];
			$tproxy_note=$tproxy_note.$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>{forward_to_port}: 127.0.0.1:$WANPROXY_PORT</strong>");
		}
		
		if($ligne["FTP_TRANSPARENT"]==1){
			$icon="folder-network-48-tr.png";
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
			$transparent=imgsimple($check);
			$tproxy_note=$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>( FTP Transparent )</strong>");
			$NOT_TEST=true;
			$WANPROXY_PORT=$ligne["WANPROXY_PORT"];
			$tproxy_note=$tproxy_note.$tpl->_ENGINE_parse_body("<br><strong style='font-size:16px'>{forward_to_port}: 127.0.0.1:$WANPROXY_PORT</strong>");
		}
		
		if($ligne["NoAuth"]==1){
			$noauthT="&nbsp;$noauth_text";
			
		}
		
		
		if($ligne["transparent"]==1){
			$icon="folder-network-48-tr.png";
			$transparent=imgsimple($check);
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
			$tproxy_note="<br><strong style='font-size:16px'>(Transparent)</strong>";
		}
		
		if($ligne["TProxy"]==1){
			$transparent=imgsimple($check);
			$tproxy_note="<br><strong style='font-size:16px'>(Tproxy)</strong>";
			$icon="folder-network-48-tr.png";
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
			
			if(!$OKFW){
				$firwall_error="<br><span style='font-size:18px;color:#d32d2d'>$error_firwall_not_configured</span>&nbsp;";
			}
		}
		
		if($ligne["MIKROTIK_PORT"]==1){
			$icon="folder-network-48-tr.png";
			$transparent=imgsimple($check);
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
			$tproxy_note="<br><strong style='font-size:16px'>(MikrotiK)</strong>";
		}
		
		if($ligne["is_nat"]==1){
			$icon="folder-network-48-tr.png";
			$transparent=imgsimple($check);
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
				
		}
		if($ligne["WCCP"]==1){
			$icon="folder-network-48-tr.png";
			$transparent=imgsimple($check);
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
			$tproxy_note="<br><strong style='font-size:16px'>(WCCP)</strong>";
			$wccp_interface="wccp{$ligne["ID"]}";
			$WCCP_INTERFACE=false;
			exec("ip tunnel show $wccp_interface 2>&1",$wccp_interface_results);
			
			while (list ($tunnel, $tunnel_line) = each ($wccp_interface_results) ){
				$tunnel_line=trim($tunnel_line);
				if($tunnel_line==null){continue;}
				$pattern="$wccp_interface:.*?remote\s+([0-9\.]+)\s+local\s+([0-9\.]+)";
				if(!preg_match("#$wccp_interface:.*?remote\s+([0-9\.]+)\s+local\s+([0-9\.]+)#",$tunnel_line,$re)){
					$WCCPEXPLAIN[]="$tunnel_line";
					continue;
				}
				$tproxy_note=$tproxy_note."&nbsp;<span style='font-weight:bold;color:#46a346;font-size:16px'>". $tpl->_ENGINE_parse_body("$wccp_interface {from} {$re[2]} {to} {$re[1]}")."</span>";
				$WCCP_INTERFACE=true;
			}
			
			if(!$WCCP_INTERFACE){
				$WCCP_INTERFACE_ETH=null;
				if($ligne["nic"]==null){$WCCP_INTERFACE_ETH="<br> $main_interface_not_selected";}
				$firwall_error=$firwall_error."<br>
				
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.wccp.interface.progress.php');\" 
				style='font-size:18px;color:#d32d2d;text-decoration:underline'>
				$wccp_interface: $wccp_interface_error$WCCP_INTERFACE_ETH</a>";
			}
			
			
		}
		
		if($ligne["ProxyProtocol"]==1){
			$tproxy_note=$tproxy_note."&nbsp;<strong style='color:#0612C6;font-size:16px'>".$tpl->javascript_parse_text("{proxy_protocol}")."</strong>";
			
		}
		
		
		if($eth<>null){
			$nic=new system_nic($eth);
			$test_ip=$nic->IPADDR;
			$ipaddr="$eth $nic->IPADDR - $nic->NICNAME";
		}else{
			$test_ip="127.0.0.1";
			$ipaddr=$all;
		}
			
		$xnote=wordwrap($xnote,130,"<br>");
		$EditJs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?port-js=yes&ID=$ID&t={$_GET["tt"]}');\"
		style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
		
		$IS_CONFUGRED=$CONFIGURED_PORT[$port];
		if($ligne["is_nat"]==1){$IS_CONFUGRED=true;}
		if($ligne["FTP"]==1){$IS_CONFUGRED=true;}
		if($ligne["WANPROXY"]==1){$IS_CONFUGRED=true;}
		
		
	
		
		
	if($IS_CONFUGRED){
		if(!$NOT_TEST){
		$array_test=squid_test_port($test_ip,$port);
			if(!$array_test["RES"]){
				$icon="48-red.png";
				$firwall_error=$firwall_error."<br><span style='font-size:18px;color:#d32d2d'>
				$error: {$array_test["ERR"]}<br><i>$error_need_to_apply_or_restart_squid</i></span>&nbsp;";
				
			}
		}

		
		
	}else{
		if($ligne["enabled"]==1){
			$icon="warning48.png";
			$firwall_error=$firwall_error."<br><span style='font-size:18px;color:#d32d2d'>$error_need_to_apply_or_restart_squid</span>&nbsp;";
		}
		
	}
	
	
	
	
	
		
	if($tproxy_note<>null){$tproxy_note="<i>$tproxy_note</i>";}
	if($PortName<>null){$PortName="($PortName)";}

		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<center style='font-size:30px;font-weight:normal;color:$color'>
							<img src='img/$icon'></center>",
						"$EditJs$ipaddr</a> ",
						"$EditJs$port</a>&nbsp;<span style='font-size:18px;font-weight:bold;color:$color'>$PortName</span>$text_ssl{$firwall_error}$tproxy_note<br><span style='font-size:14px;color:$color'>$xnote$noauthT</span>",
						"<center style='margin-top:3px'>$transparent</a></center>",
						"<center style='margin-top:3px'>$icon_ssl</a></center>",
						"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></center>",
						"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
		);
	}
	
	if($searchstring==null){
		
		while (list ($index, $dnarr) = each ($array) ){
			$data['rows'][]=$dnarr;
		}
		
		$data['total']=$data['total']+6;
	}

	echo json_encode($data);
}


function events_search_defaults($return=false){
	//Loadjs('squid.popups.php?script=listen_port');
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port=0;}
	if($squid->isNGnx()){$users->SQUID_REVERSE_APPLIANCE=false;}
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	$transparent=null;
	if($squid->hasProxyTransparent==1){
		$transparent="{transparent}";
	}
	
	$sock=new sockets();
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	if($SquidAsMasterPeerIPAddr==null){$SquidAsMasterPeerIPAddr="0.0.0.0";}
	
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	$delete="delete-48-grey.png";
	$color="black";
	$explainStyle="font-size:13px";
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 5;
	$data['rows'] = array();
	
	$listen_port=$tpl->_ENGINE_parse_body("<strong>{main_port}</strong> $transparent {CnTLMPORT_explain}</strong>");
	$second_port=$tpl->_ENGINE_parse_body("<strong>{second_port}</strong><br>{squid_second_port_explain}");
	$smartphones_port=$tpl->_ENGINE_parse_body("<strong>{smartphones_port}</strong><br>{smartphones_port_explain}");
	$cntlm_port=$tpl->_ENGINE_parse_body("<strong>{cntlm_port}</strong><br>{CnTLMPORT_explain2}");
	$ssl_port=$tpl->_ENGINE_parse_body("<strong>{ssl_port}</strong> $transparent<br>{squid_ssl_port_explain}");
	$parent_port=$tpl->_ENGINE_parse_body("<strong>{parent_port}</strong><br>{parent_port_explain}");
	
	$smartphones_port=wordwrap($smartphones_port,130,"<br>");
	$second_port=wordwrap($second_port,130,"<br>");
	$cntlm_port=wordwrap($cntlm_port,130,"<br>");
	$ssl_port=wordwrap($ssl_port,130,"<br>");
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$SpanJs="<a href=\"javascript:blur();\" style='font-size:30px;font-weight:normal;color:$color;'>";
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	
	$data['rows'][] = array(
			'id' => "001",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a> ",
					"$EditJs$squid->listen_port</a> <div style='font-size:14px'>$listen_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	if($squid->second_listen_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$data['rows'][] = array(
			'id' => "0002",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a> ",
					"$EditJs$squid->second_listen_port</a> <div style='$explainStyle'><span style='color:$color'>$second_port</span></div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	$EnableCNTLM=intval($sock->GET_INFO("EnableCNTLM"));
	if($EnableCNTLM==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}

	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "0003",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'>
						<img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$CNTLMPort</a><div style='$explainStyle'><span style='color:$color'>$cntlm_port</span></div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png

	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	if($squid->ssl_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";	
	
	$data['rows'][] = array(
			'id' => "0004",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$squid->ssl_port</a><div style='$explainStyle'><span style='color:$color'>$ssl_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	
	if($squid->smartphones_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$data['rows'][] = array(
			'id' => "0005",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$squid->smartphones_port</a> <div style='$explainStyle'><span style='color:$color'>$smartphones_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	if($SquidAsMasterPeerPort==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "0005",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$SpanJs<span style='color:$color'>$SquidAsMasterPeerIPAddr</a>",
					"$SpanJs<span style='color:$color'>$SquidAsMasterPeerPort</a> <div style='$explainStyle'><span style='color:$color'>$parent_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	

	if($return){return $data['rows'];}
	echo json_encode($data);
	
}

function squid_test_port($ipaddr,$port){
	
	$fp=@stream_socket_client("tcp://$ipaddr:$port",$errno, $errstr,1, STREAM_CLIENT_CONNECT);
	if(!$fp){
		
		if($errno==110){
			$array["RES"]=true;
			@socket_close($fp);
			@fclose($fp);
			return $array;
			
		}
		
		
		$array["RES"]=false;
		$array["ERR"]="$errno $errstr";
		@socket_close($fp);
		@fclose($fp);
		return $array;
		
	}else{
		$array["RES"]=true;
		@socket_close($fp);
		@fclose($fp);
		return $array;
	}
	
}
	
	
	
	
	

