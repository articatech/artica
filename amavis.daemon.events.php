<?php
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["table-list"])){events_list();exit;}
	if(isset($_GET["zoom"])){zoom();exit;}
	if(isset($_GET["loglevel"])){loglevel();exit;}
	if(isset($_POST["AmavisDebugSpamassassin"])){log_level_save();exit;}
page();		
	
	
function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{APP_AMAVISD_NEW}::{POSTFIX_EVENTS}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$log_level=$tpl->javascript_parse_text("{log_level}");
	$events=$tpl->javascript_parse_text("{events}");
	$hostname=$_GET["hostname"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	
	
	$table_width="715";
	$events_width=360;
	if(isset($_GET["in-front-ajax"])){
		$table_width=860;
		$events_width=507;
	}
	
	$buttons="
	buttons : [
	{name: '<b>$log_level</b>', bclass: 'add', onpress : defineLogLevel},
	
		],";	
	
$html="
<div style='width:99%;margin-left:-10px;margin-top:-7px' class=form >
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
	
<script>
var memid='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width : 58, sortable : true, align: 'left'},
		{display: '$host', name : 'host', width : 71, sortable : true, align: 'left'},
		{display: '$service', name : 'host', width : 58, sortable : true, align: 'left'},
		{display: 'PID', name : 'host', width : 43, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'left'},
		{display: '$events', name : 'events', width :688, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'zDate'},
		],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '$title ($maillog_path)',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 365,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function AmavisZoom(value){
	YahooWin6(400,'$page?zoom='+value,'Zoom');
}

function defineLogLevel(){
	YahooWin6(550,'$page?loglevel=yes','$log_level');
}

</script>
";
	
	echo $html;

}

function zoom(){
	$zoom=base64_decode($_GET["zoom"]);
	echo "<textarea style='width:99%;font-size:14px;height:250px' class=form>$zoom</textarea>";
}

function loglevel(){
	$amavis=new amavis();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$AmavisDebugSpamassassin=$sock->GET_INFO("AmavisDebugSpamassassin");
	if(!is_numeric($AmavisDebugSpamassassin)){$AmavisDebugSpamassassin=0;}	
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();	
for($i=0;$i<6;$i++){
		$hash[$i]="{log_level} 0$i";
		
	}
	
	
	$html="
	
	<div id=$t>
	<table style='width:99%' class=form>
	
	<tr>
		<td class=legend nowrap style='font-size:16px'>{sa_debug}:</td>
		<td>". Field_checkbox("AmavisDebugSpamassassin",1,$AmavisDebugSpamassassin)."</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{log_level}</td>
		<td>" . Field_array_Hash($hash,'log_level',$amavis->main_array["BEHAVIORS"]["log_level"],"style:font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveAmavisEventsParams()",18)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveAmavisEventsParams= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
	   	 YahooWin6Hide();
		}		
	
		function SaveAmavisEventsParams(){
			var XHR = new XHRConnection();
			XHR.appendData('log_level',document.getElementById('log_level').value);
			if(document.getElementById('AmavisDebugSpamassassin').checked){
				XHR.appendData('AmavisDebugSpamassassin',1);
			}else{
				XHR.appendData('AmavisDebugSpamassassin',0);
			}
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveAmavisEventsParams);	

		}	
	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function log_level_save(){
	$amavis=new amavis();
	$sock=new sockets();
	$sock->SET_INFO("AmavisDebugSpamassassin",$_POST["AmavisDebugSpamassassin"]);
	$amavis->main_array["BEHAVIORS"]["log_level"]=$_POST["log_level"];
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body('{log_level} -> '.$_POST["log_level"]."\n" );
	$amavis->Save();
}


function events_list(){
	include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_POST["query"]);
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_POST["rp"]}&prefix=amavis")));
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/query.mail.log"));
	
	
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($index, $line) = each ($array) ){
	$lineOrg=$line;
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
			
			
		}
		
		$img=statusLogs($line);
		$linebb=base64_encode($lineOrg);
		$lineZom="<a href=\"javascript:blur();\" OnClick=\"AmavisZoom('$linebb');\">$line</a>";
	
	$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
				<span style='font-size:12px'>$date</span>",
				"<span style='font-size:12px'>$host</span>",
				"<span style='font-size:12px'>$service</span>",
				"<span style='font-size:12px'>$pid</span>",
				"<img src='$img'>",
				"<span style='font-size:12px'>$lineZom</span>")
				);	

				
	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);		
	
}