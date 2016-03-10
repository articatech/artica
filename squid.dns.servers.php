<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$user=new usersMenus();

	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();exit();
	}	
	
	if(isset($_GET["details-tablerows"])){details_tablerows();exit;}
	if(isset($_POST["nameserver"])){dns_add();exit;}
	if(isset($_POST["DnsDelete"])){dns_del();exit;}
	if(isset($_POST["SquidDNSUpDown"])){SquidDNSUpDown();exit;}
	if(isset($_GET["SquidDNSUseLocalDNSService-js"])){SquidDNSUseLocalDNSService_js();exit;}
	if(isset($_POST["SquidDNSUseLocalDNSService"])){SquidDNSUseLocalDNSService();exit;}
	if(isset($_GET["SquidDNSUseSystem-js"])){SquidDNSUseSystem_js();exit;}
	if(isset($_POST["SquidDNSUseSystem"])){SquidDNSUseSystem();exit;}
table();

function dns_add(){
	$_POST["nameserver"]=trim($_POST["nameserver"]);
	$IPClass=new IP();
	if(!$IPClass->isValid($_POST["nameserver"])){
		echo "{$_POST["nameserver"]} invalid\n";
		return;
	}
	$nameserver[$_POST["nameserver"]]=true;
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$_POST["nameserver"]}','1')");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function dns_del(){
	$_POST["DnsDelete"]=trim($_POST["DnsDelete"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM dns_servers WHERE dnsserver LIKE '%{$_POST["DnsDelete"]}%'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function SquidDNSUseLocalDNSService_js(){
	$t=time();
	$page=CurrentPageName();
	echo "
var xEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#SQUID_LOCAL_DNS_SETTINGS').flexReload();
	
	
}				
	
function Enable$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidDNSUseLocalDNSService','yes');
	XHR.sendAndLoad('$page', 'POST',xEnable$t);			
}
Enable$t();";
	
	
}

function SquidDNSUseSystem_js(){
	$t=time();
	$page=CurrentPageName();
	echo "
	var xEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#SQUID_LOCAL_DNS_SETTINGS').flexReload();
	
	
	}
	
	function Enable$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidDNSUseSystem','yes');
	XHR.sendAndLoad('$page', 'POST',xEnable$t);
	}
	Enable$t();";	
	
}

function SquidDNSUseLocalDNSService(){
	$sock=new sockets();
	$SquidDNSUseLocalDNSService=intval($sock->GET_INFO("SquidDNSUseLocalDNSService"));
	if($SquidDNSUseLocalDNSService==1){
		$SquidDNSUseLocalDNSService=0;
	}else{
		$SquidDNSUseLocalDNSService=1;
	}
	
	$sock->SET_INFO("SquidDNSUseLocalDNSService", $SquidDNSUseLocalDNSService);
	
}
function SquidDNSUseSystem(){
	$sock=new sockets();
	$SquidDNSUseSystem=intval($sock->GET_INFO("SquidDNSUseSystem"));
	if($SquidDNSUseSystem==1){
		$SquidDNSUseSystem=0;
	}else{
		$SquidDNSUseSystem=1;
	}
	
	$sock->SET_INFO("SquidDNSUseSystem", $SquidDNSUseSystem);	
	
}


function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$dns_nameservers=$tpl->javascript_parse_text("{dns_nameservers}");
	$new_dns=$tpl->javascript_parse_text("{new_dns_server}");
	$blacklist=$tpl->javascript_parse_text("{blacklist}");
	$domains=$tpl->javascript_parse_text("{domains}");
	$restart_service=$tpl->javascript_parse_text("{apply}");
	$about=$tpl->javascript_parse_text("{about2}");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$title=$tpl->javascript_parse_text("{dns_used_by_the_proxy_service}");
	$about_text=$tpl->javascript_parse_text("{dns_nameservers_text}");
	
	$SQUID_INSTALLED=1;
	$newdns="{name: '<strong style=font-size:18px>$new_dns</strong>', bclass: 'add', onpress : dnsadd},";
	$buttons="
	buttons : [
		$newdns
		
		{name: '<strong style=font-size:18px>$restart_service</strong>', bclass: 'ReConf', onpress : RestartService$t},
		{name: '<strong style=font-size:18px>$about</strong>', bclass: 'Help', onpress : About$t},
	],";

	

$html="<table class='SQUID_LOCAL_DNS_SETTINGS' style='display: none' id='SQUID_LOCAL_DNS_SETTINGS' style='width:99%'></table>
<script>
var xmemnum=0;
$(document).ready(function(){
$('#SQUID_LOCAL_DNS_SETTINGS').flexigrid({
	url: '$page?details-tablerows=yes&t=$t&field={$_GET["field"]}&value={$_GET["value"]}&EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :91, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$dns_nameservers</span>', name : 'server', width :935, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'zOrder', width :91, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'dup', width :91, sortable : false, align: 'center'},		
		{display: '&nbsp;', name : 'none2', width :91, sortable : false, align: 'center'},
	],
	$buttons
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});

function About$t(){
	alert('$about_text');
}

function RestartService$t(){
	Loadjs('squid.compile.progress.php');
}

function BlackList$t(){
	Loadjs('squid.dns.items.black.php');
}
var x_dnsadd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SQUID_LOCAL_DNS_SETTINGS').flexReload();
	if(document.getElementById('squid-services')){
		LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
	}
}
		
var x_dnsdel= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#rowsquid-dns-'+xmemnum).remove();
	$('#SQUID_LOCAL_DNS_SETTINGS').flexReload();
}		
		
function dnsadd(){
	var nameserver=prompt('$dns_nameservers:');
	if(nameserver){
		var XHR = new XHRConnection();
		XHR.appendData('nameserver',nameserver);
		XHR.sendAndLoad('$page', 'POST',x_dnsadd);	
	}
}
		
function DnsDelete$t(num){
	xmemnum=num;
	var XHR = new XHRConnection();
	XHR.appendData('DnsDelete',num);
	XHR.sendAndLoad('$page', 'POST',x_dnsdel);	
}
var x_dnsupd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SQUID_LOCAL_DNS_SETTINGS').flexReload();
	
}

function SquidDNSUpDown(ID,dir){
	var XHR = new XHRConnection();
	XHR.appendData('SquidDNSUpDown',ID);
	XHR.appendData('direction',dir);
	XHR.sendAndLoad('$page', 'POST',x_dnsupd);	
}


</script>";
echo $html;

}

function SquidDNSUpDown(){
	$ID=$_POST["SquidDNSUpDown"];
	$direction=$_POST["direction"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT *  FROM dns_servers WHERE ID='$ID'"));
	$OldZorder=$ligne["zOrder"];
	if($direction==1){
		$zOrder=$ligne["zOrder"]-1;

	}else{
		$zOrder=$ligne["zOrder"]+1;
	}
	
	if($zOrder==-1){$zOrder=0;}
	
	$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$OldZorder WHERE `zOrder`=$zOrder AND ID !='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$zOrder WHERE `ID`='$ID'");
	if(!$q->ok){echo $q->mysql_error;}

	$sql="SELECT *  FROM dns_servers ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("UPDATE `dns_servers` SET `zOrder`=$c WHERE `ID`='$ID'");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}
	
}


function details_tablerows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$squid=new squidbee();
	$sock=new sockets();
	$t=$_GET["t"];
	$search='%';
	$table="dns_servers";

	if(!$q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("CREATE TABLE `squidlogs`.`dns_servers` ( `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				 `dnsserver` VARCHAR( 90 ) NOT NULL , 
				 `zOrder` SMALLINT( 2 ) NOT NULL ,
				 INDEX (`zOrder`), 
				UNIQUE KEY `dnsserver` (`dnsserver`) )" );
	}

	$page=1;
	$FORCE_FILTER=null;

	$total=0;
	
	$enabled=1;
	$SquidDNSUseSystem=intval($sock->GET_INFO("SquidDNSUseSystem"));
	$SquidDNSUseLocalDNSService=intval($sock->GET_INFO("SquidDNSUseLocalDNSService"));
	$LOCAL_ENABLED=1;
	$SquidDNSUseSystem_icon="ok-42.png";
	$SquidDNSUseSystem_color="black";
	$SquidDNSUseLocalDNSService_icon="ok-42.png";
	$SquidDNSUseLocalDNSService_color="black";
	$TableMainColor="black";
	$SquidDNSUseSystem_js="Loadjs('$MyPage?SquidDNSUseSystem-js=yes')";
	$SquidDNSUseLocalDNSService_js="Loadjs('$MyPage?SquidDNSUseLocalDNSService-js=yes')";
	
	if($SquidDNSUseSystem==0){
		$SquidDNSUseSystem_icon="danger42.png";
	}
	
	if($SquidDNSUseLocalDNSService==0){
		$SquidDNSUseLocalDNSService_icon="danger42.png";
		$LOCAL_ENABLED=0;
	}
	if($SquidDNSUseSystem==1){
		$TableMainColor="#898989";
		$LOCAL_ENABLED=0;
		$SquidDNSUseLocalDNSService_icon="ok-42-grey.png";
		$SquidDNSUseLocalDNSService_color="#898989";
		
	}
	if($sock->dnsmasq_enabled()==0){
		$LOCAL_ENABLED=0;
		$SquidDNSUseLocalDNSService_icon="ok-42-grey.png";
		$SquidDNSUseLocalDNSService_color="#898989";
		$SquidDNSUseLocalDNSService_js="blur()";
		
	}
	
	if($LOCAL_ENABLED==1){
		$SquidDNSUseSystem_color="#898989";
		
	}
	
	$SquidDNSUseSystem_icon=imgsimple($SquidDNSUseSystem_icon,null,$SquidDNSUseSystem_js);
	$SquidDNSUseSystem_text=$tpl->_ENGINE_parse_body("{squid_use_local_system_dns}");
	
	$SquidDNSUseLocalDNSService_icon=imgsimple($SquidDNSUseLocalDNSService_icon,null,$SquidDNSUseLocalDNSService_js);
	$SquidDNSUseLocalDNSService_text=$tpl->_ENGINE_parse_body("{squid_use_local_service_dns}");
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	

	

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no data}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+2;
	$data['rows'] = array();
	
	

	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	$data['rows'][] = array(
			'id' => "squid-dns-A",
			'cell' => array(
					"<center style='font-size:36px;color:$SquidDNSUseSystem_color'>$SquidDNSUseSystem_icon</center>",
					"<a href=\"javascript:blur();\" OnClick=\"javascript:$SquidDNSUseSystem_js\" 
						style='font-size:36px;color:$SquidDNSUseSystem_color;text-decoration:underline'>{$SquidDNSUseSystem_text}</span>",
					"<center style='font-size:36px;color:$SquidDNSUseSystem_color'>-</center>",
					"<center style='font-size:36px;color:$SquidDNSUseSystem_color'>-</center>",
					"<center style='font-size:36px;color:$SquidDNSUseSystem_color'>-</center>",
			)
	);
	$data['rows'][] = array(
			'id' => "squid-dns-A",
			'cell' => array(
					"<center style='font-size:36px;color:$SquidDNSUseLocalDNSService_color'>$SquidDNSUseLocalDNSService_icon</center>",
					"<a href=\"javascript:blur();\" OnClick=\"javascript:$SquidDNSUseLocalDNSService_js\" 
					style='font-size:36px;color:$SquidDNSUseLocalDNSService_color;;text-decoration:underline'>{$SquidDNSUseLocalDNSService_text} (127.0.0.1)</span>",
					"<center style='font-size:36px;color:$SquidDNSUseLocalDNSService_color'>-</center>",
					"<center style='font-size:36px;color:$SquidDNSUseLocalDNSService_color'>-</center>",
					"<center style='font-size:36px;color:$SquidDNSUseLocalDNSService_color'>-</center>",
			)
	);	

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$delete=imgtootltip('delete-42.png','{delete}',"DnsDelete$t('{$ligne["dnsserver"]}')");
		$up=imgsimple("arrow-up-42.png","","SquidDNSUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-42.png","","SquidDNSUpDown('{$ligne['ID']}',0)");
		
		$data['rows'][] = array(
				'id' => "squid-dns-{$ligne["ID"]}",
				'cell' => array(
						"<center style='font-size:36px;color:$TableMainColor'>{$ligne["zOrder"]}</center>",
						"<span style='font-size:36px;color:$TableMainColor'>{$ligne["dnsserver"]}</span>",
						"<center style='font-size:12.5px'>$up</center>",
						"<center style='font-size:12.5px'>$down</center>",
						"<center style='font-size:12.5px'>$delete</center>",
				)
		);		

	}

	echo json_encode($data);
}