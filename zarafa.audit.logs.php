<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["zarafa-filter"])){page();exit;}
	if(isset($_GET["table-list"])){events_list();exit;}
	if(isset($_GET["js-zarafa"])){js_zarafa();exit;}
	
	
js_zarafa();

function js_zarafa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_ZARAFA}:{audit}");
	$html="YahooWinBrowse('942','$page?zarafa-filter=yes','$title')";
	echo $html;
	
}
	
function page(){
	$sock=new sockets();
	$ZarafaEnableSecurityLogging=$sock->GET_INFO("ZarafaEnableSecurityLogging");
	if(!is_numeric($ZarafaEnableSecurityLogging)){$ZarafaEnableSecurityLogging=0;}
	if($ZarafaEnableSecurityLogging==0){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{zarafa_audit_log_not_enabled}");
		echo "<script>alert('$error');YahooWinBrowseHide();</script>";
		return;
		
	}
	
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{APP_ZARAFA}:{audit}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	
	$destination=$tpl->javascript_parse_text("{destination}");
	$events=$tpl->javascript_parse_text("{events}");
	$hostname=$_GET["hostname"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$form="<div style='width:900px' class=form>";
	if(isset($_GET["noform"])){$form="<div style='margin-left:-15px'>";}
	if($_GET["mimedefang-filter"]=="yes"){
		$title=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{events}");
	}
$html="
$form
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
	
<script>
var memid='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width : 58, sortable : true, align: 'left'},
		{display: '$host', name : 'host', width : 71, sortable : true, align: 'left'},
		{display: '$service', name : 'host', width : 58, sortable : true, align: 'left'},
		{display: 'PID', name : 'host', width : 43, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'left'},
		{display: '$events', name : 'events', width :546, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'zDate'},
		],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '$title (/var/log/auth.log)',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 900,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1500]
	
	});   
});

</script>
";
	
	echo $html;
			
	
	
}

function events_list(){
	include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_POST["query"]);
	$array=unserialize(base64_decode($sock->getFrameWork("zarafa.php?audit-log=yes&filter=$query&rp={$_POST["rp"]}&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}")));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($index, $line) = each ($array) ){
		$m5=md5($line);
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
			
			
		}
		$service=str_replace("zarafa-", "", $service);
		$img=statusLogs($line);
		
		if(preg_match("#(user|ownername|username)='(.*?)'#", $line,$re)){
			$line=str_replace($re[2], "<strong>{$re[2]}</strong>", $line);
		}
		
	
	$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
				<span style='font-size:12px'>$date</span>",
				"<span style='font-size:12px'>$host</span>",
				"<span style='font-size:12px'>$service</span>",
				"<span style='font-size:12px'>$pid</span>",
				"<img src='$img'>",
				"<span style='font-size:12px'>$line</span>")
				);	

				
	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);		
	
}


