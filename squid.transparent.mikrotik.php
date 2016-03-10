<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["MikrotikTransparent"])){save();exit;}
	if(isset($_GET["mikrotik-ipface"])){mikrotik_ipface();exit;}
	
page();


function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$MikrotikHTTPSquidPort=intval($sock->GET_INFO('MikrotikHTTPSquidPort'));
	$MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
	$MikrotikLocalInterface=$sock->GET_INFO('MikrotikLocalInterface');
	$MikrotikNetMask=$sock->GET_INFO('MikrotikNetMask');
	$MikrotikIPAddr=$sock->GET_INFO('MikrotikIPAddr');
	$MikrotikLAN=$sock->GET_INFO('MikrotikLAN');
	$MikrotikSSLTransparent=intval($sock->GET_INFO("MikrotikSSLTransparent"));
	$MikrotikHTTPSSquidPort=intval($sock->GET_INFO("MikrotikHTTPSSquidPort"));
	
	$ip=new networking();
	$ipsH=$ip->Local_interfaces();
	
	$t=time();
	
	if($MikrotikHTTPSquidPort==0){$MikrotikHTTPSquidPort=rand(9090,63000);}
	if($MikrotikHTTPSSquidPort==0){$MikrotikHTTPSSquidPort=rand(9190,63000);}
	if($MikrotikVirtualIP==null){$MikrotikVirtualIP="172.16.24.2";}
	if($MikrotikNetMask==null){$MikrotikNetMask="255.255.255.252";}
	if($MikrotikIPAddr==null){$MikrotikIPAddr="172.16.24.1";}
	if($MikrotikLAN==null){$MikrotikLAN="192.168.1.0/24";}
	if($MikrotikLocalInterface==null){$MikrotikLocalInterface="eth0";}
	
	$ARRAYBT["{apply}"]="Apply$t";
	$ARRAYBT["{save}"]="Save$t";
	
	
	$p=Paragraphe_switch_img("{mikrotik_compliance}", "{mikrotik_compliance_explain}",
			"MikrotikTransparent",$MikrotikTransparent,null,760);
	
	$p2=Paragraphe_switch_img("{mikrotik_ssl_compliance}", "{mikrotik_ssl_compliance_explain}",
			"MikrotikSSLTransparent",$MikrotikSSLTransparent,null,760);	
	
	$html="
	<div style='text-align:right'><img src='img/mikrotik-150.png'></div>
	<div style='width:95%;padding:30px' class=form>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:220px'><div id='mikrotik-ipface'></div></td>
		<td style='vertical-align:top;width:99%;padding-left:30px'>
	
			$p$p2
		</td>
	</table>
	<table style='width:100%'>
	".
	Field_text_table("MikrotikHTTPSquidPort", "{proxy_listen_port}",$MikrotikHTTPSquidPort,22,null).
	Field_text_table("MikrotikHTTPSSquidPort", "{proxy_listen_port} (SSL)",$MikrotikHTTPSSquidPort,22,null).
	Field_list_table("MikrotikLocalInterface", "{local_interface}", $MikrotikLocalInterface,22,$ipsH).
	Field_ipv4_table("MikrotikVirtualIP", "{local_ip_address}",$MikrotikVirtualIP,22,"{MikrotikVirtualIP}",null).
	Field_text_table("MikrotikNetMask", "{netmask}",$MikrotikNetMask,22).
	Field_ipv4_table("MikrotikIPAddr", "{mikrotik_address}",$MikrotikIPAddr,22,"{mikrotik_address_explain}").
	Field_area_table("MikrotikLAN", "{your_network}",$MikrotikLAN,22,null).
	Field_buttons_table_autonome($ARRAYBT,32)."
	</table>
	<div id='mikrotik-config'></div>
</div>


<script>

	var xSave$t= function (obj) {
		var res=obj.responseText;
		
		document.getElementById('mikrotik-config').innerHTML=res;

	}	
	
	
	
	


	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('MikrotikSSLTransparent',document.getElementById('MikrotikSSLTransparent').value);
		XHR.appendData('MikrotikLocalInterface',document.getElementById('MikrotikLocalInterface').value);
		XHR.appendData('MikrotikTransparent',document.getElementById('MikrotikTransparent').value);
		XHR.appendData('MikrotikHTTPSquidPort',document.getElementById('MikrotikHTTPSquidPort').value);
		XHR.appendData('MikrotikHTTPSSquidPort',document.getElementById('MikrotikHTTPSSquidPort').value);
		XHR.appendData('MikrotikVirtualIP',document.getElementById('MikrotikVirtualIP').value);
		XHR.appendData('MikrotikNetMask',document.getElementById('MikrotikNetMask').value);
		XHR.appendData('MikrotikIPAddr',document.getElementById('MikrotikIPAddr').value);
		XHR.appendData('MikrotikLAN',document.getElementById('MikrotikLAN').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
	
function Apply$t(){	
	Save$t();
	Loadjs('squid.compile.progress.php?restart=yes&ask=yes');
}
	
Save$t();
LoadAjax('mikrotik-ipface','$page?mikrotik-ipface=yes');
</script>					
	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mikrotik_ipface(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$mikrotik_ipface=unserialize(base64_decode($sock->getFrameWork("squid.php?mikrotik-ipface=yes")));
	if(!is_array($mikrotik_ipface)){
		$l[]="[interface]";
		$l[]="service_name=interface";
		$l[]="master_version=1.0";
		$l[]="service_cmd=/etc/init.d/tproxy";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=system";
		$l[]="installed=1";
		$l[]="running=0";
		
	}else{
		$l[]="[interface]";
		$l[]="service_name=interface";
		$l[]="master_version=1.0";
		$l[]="service_cmd=/etc/init.d/tproxy";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=system";
		$l[]="installed=1";
		$l[]="running=1";
		$l[]="application_installed=1";
		$l[]="master_pid={$mikrotik_ipface["INTERFACE"]}/{$mikrotik_ipface["ETH"]}";
		$l[]="master_time=0";
		$l[]="master_memory=0";
		$l[]="processes_number=1";
	}
	
	$l[]="";
	
	
	
	$MikrotikIPAddr=$sock->GET_INFO('MikrotikIPAddr');
	$data=trim($sock->getFrameWork("ping-host=yes&ipfrom={$mikrotik_ipface["INTERFACE"]}&ipto=$MikrotikIPAddr"));
	if($data=="FALSE"){
		$l[]="[mikrotik]";
		$l[]="service_name=mikrotik_ping";
		$l[]="master_version=1.0";
		$l[]="service_cmd=/etc/init.d/tproxy";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=system";
		$l[]="installed=1";
		$l[]="running=0";
		
	}else{
		$l[]="[mikrotik]";
		$l[]="service_name=mikrotik_ping";
		$l[]="master_version=1.0";
		$l[]="service_cmd=/etc/init.d/tproxy";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=system";
		$l[]="installed=1";
		$l[]="running=1";
		$l[]="application_installed=1";
		$l[]="master_pid=$MikrotikIPAddr";
		$l[]="master_time=0";
		$l[]="master_memory=0";
		$l[]="processes_number=1";
		
	}
	$l[]="";
	
	$datas=@implode("\n", $l);
	
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("interface",$ini,null,1);
	$status2=DAEMON_STATUS_ROUND("mikrotik",$ini,null,1);
	$refresh="<div style='text-align:right'>".imgsimple("refresh-32.png",null,"LoadAjax('mikrotik-ipface','$page?mikrotik-ipface=yes');")."</div>";
	echo $tpl->_ENGINE_parse_body("$status$status2$refresh");
}

function save(){
	$sock=new sockets();
	
	$sock->SaveConfigFile($_POST["MikrotikLAN"],"MikrotikLAN");
	unset($_POST["MikrotikLAN"]);
	while (list ($num, $val) = each ($_POST) ){
		$sock->SET_INFO($num,$val);
		
	}
	
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$MikrotikHTTPSquidPort=intval($sock->GET_INFO('MikrotikHTTPSquidPort'));
	$MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
	$MikrotikNetMask=$sock->GET_INFO('MikrotikNetMask');
	$MikrotikIPAddr=$sock->GET_INFO('MikrotikIPAddr');
	$MikrotikLAN=$sock->GET_INFO('MikrotikLAN');
	$MikrotikSSLTransparent=intval($sock->GET_INFO("MikrotikSSLTransparent"));
	$MikrotikHTTPSSquidPort=intval($sock->GET_INFO("MikrotikHTTPSSquidPort"));
	$t=time();
	
	if($MikrotikHTTPSquidPort==0){$MikrotikHTTPSquidPort=rand(9090,63000);}
	if($MikrotikVirtualIP==null){$MikrotikVirtualIP="172.16.24.2";}
	if($MikrotikNetMask==null){$MikrotikNetMask="255.255.255.252";}
	if($MikrotikIPAddr==null){$MikrotikIPAddr="172.16.24.1";}
	if($MikrotikLAN==null){$MikrotikLAN="192.168.1.0/24";}
	$NOT=array();
	$f[]="<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important' id='procedure3-text'>";
	$f[]="MikroTik client config:"; 
	$MikrotikLANs=explode("\n",$MikrotikLAN);
	while (list ($num, $val) = each ($MikrotikLANs) ){
		$val=trim($val);
		if($val==null){continue;}
		
		if(substr($val, 0,1)=="!"){
			$NOT[]=substr($val, 1,strlen($val));
			continue;
		}
		
		
		
		$f[]="/ip firewall address-list add list=\"to_proxy_list\" address=$val";
	}
	
	if(count($NOT)>0){
		while (list ($num, $val) = each ($NOT) ){
			$f[]="/ip firewall address-list add list=\"to_direct\" address=$val";
		}
		
	}
	
	$f[]="/ip route";

	
	$f[]="add check-gateway=ping distance=1 gateway=$MikrotikVirtualIP routing-mark=to-artica-proxy";
	
	$f[]="/ip firewall mangle";
	if(count($NOT)>0){
		$f[]="add action=return chain=prerouting comment=\"mark routing to direct\" dst-port=80 src-address-list=to_direct";
		
		if($MikrotikSSLTransparent==1){
			$f[]="add action=return chain=prerouting comment=\"mark routing to direct\" dst-port=443 src-address-list=to_direct";
		}
		
	}
	$f[]="add action=mark-routing chain=prerouting comment=\"mark routing to Artica proxy HTTP\" dst-port=80 new-routing-mark=to-artica-proxy passthrough=no protocol=tcp src-address-list=to_proxy_list";
	
	if($MikrotikSSLTransparent==1){
		$f[]="add action=mark-routing chain=prerouting comment=\"mark routing to Artica proxy SSL\" dst-port=443 new-routing-mark=to-artica-proxy passthrough=no protocol=tcp src-address-list=to_proxy_list";
	}
	
	$f[]="</textarea>";
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-iptables=yes");
	
	echo @implode("\n",$f);
	
	
	
	
	
}

