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
	if(!CheckRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_POST["maillogToMysql"])){maillogToMysqlSave();exit;}
	if(isset($_GET["popup"])){page();exit;}
	if(isset($_GET["table-list"])){events_list();exit;}
	if(isset($_GET["js-zarafa"])){js_zarafa();exit;}
	if(isset($_GET["js-mgreylist"])){js_mgreylist();exit;}
	if(isset($_GET["ZoomEvents"])){ZoomEvents();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["SearchPattern"])){SearchPattern();exit;}
	
page();

function CheckRights(){
	$user=new usersMenus();
	if($user->AsPostfixAdministrator){return true;}
	if($user->AsMailBoxAdministrator){return true;}
	return false;
}





function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{history_search}: {POSTFIX_EVENTS}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$searchQueryExplain=$tpl->javascript_parse_text("{postfix_search_history_explain}");
	$destination=$tpl->javascript_parse_text("{destination}");
	$events=$tpl->javascript_parse_text("{events}");
	$hostname=$_GET["hostname"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$search=$tpl->javascript_parse_text("{search}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$form="<div style='width:900px' class=form>";
	if(isset($_GET["noform"])){$form="<div style='margin-left:-15px'>";}
	
	
	$table_width=900;
	$events_wdht=546;
	if(isset($_GET["miniadm"])){
		$table_width=955;
		$events_wdht=601;
	}
	
$html="
<table class='POSTFIX_LOG_HISTORY' style='display: none' id='POSTFIX_LOG_HISTORY' style='width:100%'></table>
<script>
var memid='';
$(document).ready(function(){
$('#POSTFIX_LOG_HISTORY').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&t=$t&zarafa-filter={$_GET["zarafa-filter"]}&miltergrey-filter={$_GET["miltergrey-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$zDate</span>', name : 'zDate', width : 113, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$service</span>', name : 'host', width : 148, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>PID</span>', name : 'host', width : 50, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$events</span>', name : 'events', width :1119, sortable : true, align: 'left'},
		],
buttons : [
		{name: '<strong style=font-size:18px>$search</strong>', bclass: 'add', onpress : SearchQuery$t},
		{separator: true},
		
		],	
	searchitems : [
		{display: '$events', name : 'zDate'},
		],
	sortname: 'events',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ZoomEvents(content){
	RTMMail(650,'$page?ZoomEvents='+content);
}

var xSearchQuery$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	Loadjs('postfix.events.search.progress.php');
}	

function SearchQuery$t(){
	var a=prompt('$searchQueryExplain','mail@domain.tld');
	if(!a){return;}
	var XHR = new XHRConnection();
	XHR.appendData('SearchPattern',encodeURIComponent(a));
	XHR.sendAndLoad('$page', 'POST',xSearchQuery$t);	

}

</script>
";
	
	echo $html;
			
	
	
}

function events_list(){
	include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=urlencode("/usr/share/artica-postfix/ressources/logs/web/mail-history.log");
	$query=base64_encode($_POST["query"]);
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_POST["rp"]}&miltergrey-filter={$_GET["miltergrey-filter"]}&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}")));
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/query.mail.log"));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($index, $line) = each ($array) ){
		$lineenc=base64_encode($line);
		if(preg_match("#^([a-zA-Z]+)\s+([0-9]+)\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]} {$re[2]} {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=$re[6];
			$line=$re[7];
			
			
		}
		
		if($date==null){
			if(preg_match("#([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?):(.+)#", $line,$re)){
				$date="{$re[1]} {$re[2]} {$re[3]}";
				$host=$re[4];
				$service=$re[5];
				$line=$re[6];
			}
			
		}
		
		if(trim($line)==null){continue;}
		
		$img=statusLogs($line);
		
		$loupejs="ZoomEvents('$lineenc')";
		$color="black";
		
		if(preg_match("#([A-Z0-9]+): message-id=<(.+?)>#",$line,$re)){
			$line="{new_message} ID:{$re[1]} ({$re[2]})";
		}
		
		
		
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\s+from <(.*?)> rcpt <(.*?)>:\s+autowhitelisted#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
		}
		
		if(preg_match("#skipping greylist because address (.*?)\s+is whitelisted,.*?from=<(.*?)>,\s+rcpt=<(.*?)>, addr=#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
		}
		
		if(preg_match("#skipping greylist because sender <(.*?)>\s+is whitelisted,.*?from=<(.*?)>,\s+rcpt=<(.*?)>, addr=#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
		}
		
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\s+from =@(.*?)> rcpt <(.*?)>:\s+autowhitelisted#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
		}	
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\s+from =(.*?)> rcpt <(.*?)>:\s+autowhitelisted#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
		}			
		
		if(preg_match("#NOQUEUE: milter-reject: RCPT from (.*?)\[(.+?)\].*?Greylisting in action.*?from=<(.*?)>\s+to=<(.*?)>#",$line,$re)){
			$line="{delayed}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#777676";
		}
		

		
		if(preg_match("#NOQUEUE: reject: RCPT from unknown\[(.+?)\]:.*?Client host rejected: cannot find your hostname.*?from=<(.*?)> to=<(.*?)>.*?helo=<(.*?)>#",$line,$re)){
			$line="{rejected}: Hostname not found {$re[1]} (HELO:{$re[4]}) {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
			$color="#d32d2d";
		}
		
		
		if(preg_match("#NOQUEUE: reject: RCPT from (.*?)\[(.+?)\]: .*?Client host \[(.+?)\] blocked using (.+?)\s+#",$line,$re)){
			$line="{dnsbl_service}: {$re[3]} blocked using <strong>{$re[4]}</strong> ({$re[1]})";
			$color="#d32d2d";
		}
		
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\[(.+?)\]\s+from =@(.*?)> to <(.*?)>\s+blacklisted#",$line,$re)){
			$line="{blacklisted}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#d32d2d";
		}
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\[(.+?)\]\s+from\s+<(.*?)> to <(.*?)>\s+blacklisted#",$line,$re)){
			$line="{blacklisted}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#d32d2d";
		}		
		
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+\[(.+?)\]\[(.+?)\]\s+from <(.*?)> to <(.*?)>\s+delayed#",$line,$re)){
			$line="{delayed}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#777676";
		}
		if(preg_match("#milter-greylist:\s+\(.*:\s+addr\s+(.+?)\[(.+?)\]\s+from <(.*?)> to <(.*?)>\s+delayed#",$line,$re)){
			$line="{delayed}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#777676";
		}
		
		if(preg_match("#\(.*\):\s+addr\s+(.+?)\s+from <(.*?)> rcpt <(.*?)>:\s+autowhitelisted#",$line,$re)){
			$line="{whitelisted}: {$re[1]} {sender}:{$re[2]}  {to}: <strong>{$re[3]}</strong>";
			
		}
		
		if(preg_match("#NOQUEUE: milter-reject: RCPT from (.*?)\[(.+?)\].*?from=<(.*?)>\s+to=<(.*?)>#",$line,$re)){
			$line="{rejected}: {$re[2]} ({$re[2]}) {sender}:{$re[3]}  {to}: <strong>{$re[4]}</strong>";
			$color="#d32d2d";
		}
		
		if(preg_match("#ESMTP::10024 \/.*?:\s+<(.+?)>\s+->\s+<(.+?)>\s+SIZE=([0-9]+)#",$line,$re)){
			$line="Amavis {sender}: {$re[1]} {to}: <strong>{$re[2]}</strong> ".FormatBytes($re[3]/1024);
		}
		
		if(preg_match("#FWD from.*?<(.+?)>\s+->\s+<(.+?)>#",$line,$re)){
			$line="Amavis {forward_to_postfix} {sender}: {$re[2]} {to}: <strong>{$re[3]}</strong>";
		}
		
		if(preg_match("#\([0-9A-Z\-]+\)\s+Checking:.*?\[(.+?)\]\s+<(.+?)>\s+->\s+<(.+?)>#",$line,$re)){
			$line="Amavis {checking} Client:{$re[1]} {sender}: {$re[2]} {to}: <strong>{$re[3]}</strong>";
		}
		
		if(preg_match("#Passed CLEAN.*?<(.+?)>\s+->\s+<(.+?)>, Queue-ID: ([0-9A-Z]+)#",$line,$re)){
			$line="ID:{$re[3]} Amavis {pass} {sender}: {$re[1]} {to}: <strong>{$re[2]}</strong>";
		}
		
		if(preg_match("#([0-9A-Z]+):\s+to=<(.+?)>,\s+orig_to=<(.+?)>,\s+relay=(.+?)\[(.+?)\].*?status=sent#", $line,$re)){
			$line="ID:{$re[1]} {transfered} to <strong>{$re[2]}</strong> ({$re[3]}) SMTP:{$re[5]} ({$re[4]})";
			
		}
		
		if(preg_match("#([0-9A-Z\-]+): redirect: header Subject:.*?from=<(.*?)> to=<(.+?)>.*?:\s+(.+)#", $line,$re)){
			$line="ID:{$re[1]} {smtp_rule} {transfered} to <strong>{$re[4]}</strong> ({$re[3]}) {sender}:{$re[2]}";
			$color="#d32d2d";
			
		}
		
		
		
		if(preg_match("#([A-Z0-9]+):\s+from=<(.+?)>,\s+size=([0-9]+), nrcpt=([0-9]+).*?queue active#", $line,$re)){
			$line="ID:{$re[1]} {put_in_active_queue} {sender}: $re[2] ".FormatBytes($re[3]/1024)." {$re[4]} {recipients}";
			
		}
		
		
		
		
		if(preg_match("#([A-Z0-9]+):\s+to=<(.+?)>,\srelay=(.+?)\[(.+?)\]:([0-9]+).*?, status=sent#", $line,$re)){
			$line="ID:{$re[1]} {sended} {to}: <strong>{$re[2]}</strong> SMTP:{$re[4]}:{$re[5]} ({$re[3]})";
			
			
		}
		
		if(preg_match("#([0-9A-Z\-]+): to=<(.*?)>,.*?status=deferred\s+\((.+?)\)#",$line,$re)){
			$line="ID:{$re[1]} {to}: <strong>{$re[2]}</strong> ERROR {$re[3]}";
			$color="#d32d2d";
		}
		
		$line=str_replace("removed","{removed}",$line);
		$line=str_replace("SMTP:127.0.0.1:10024", "Amavis", $line);
		
		$line=$tpl->_ENGINE_parse_body($line);
	
	$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
				<span style='font-size:16px;color:$color;'>$date</span>",
				"<span style='font-size:16px;color:$color'>$service</span>",
				"<span style='font-size:16px;color:$color'>$pid</span>",
				"<span style='font-size:14px;color:$color'>$line</span>")
				);	

				
	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);		
	
}

function ZoomEvents(){
	
	$ev=base64_decode($_GET["ZoomEvents"]);
	echo "<div style='font-size:14px;width:95%' class=form>$ev</div>";
	
}
function SearchPattern(){
	$sock=new sockets();
	$sock->SET_INFO("PostfixHistorySearch", url_decode_special_tool($_POST["SearchPattern"]));
	
}

