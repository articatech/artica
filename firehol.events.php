<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once('ressources/class.system.nics.inc');

$RIGHTS=false;
$users=new usersMenus();
if($users->AsSystemAdministrator OR $users->AsSquidAdministrator OR $users->AsDansGuardianAdministrator){
	$RIGHTS=true;
}
if(!$RIGHTS){echo FATAL_ERROR_SHOW_128("{NO_PRIVS}");die();}
if(isset($_GET["events-list"])){events_list();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();

	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{hour}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");

	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$table_size=855;
	$url_row=505;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["url-row"])){$url_row=$_GET["url-row"];}

	$q=new mysql_squid_builder();

	$table=date("Ymd")."_hour";

	$title=$tpl->_ENGINE_parse_body("{firewall}: {realtime_requests}");
	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:$tableprc'></table>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'filetime', width :75, sortable : true, align: 'left'},
	{display: '$rule', name : 'hour', width :161, sortable : true, align: 'left'},
	{display: 'PROTO', name : 'filetime', width :75, sortable : true, align: 'center'},
	{display: '$interface (IN)', name : 'servername', width : 159, sortable : false, align: 'left'},
	{display: 'Source', name : 'size', width : 341, sortable : true, align: 'left'},
	{display: '$interface (OUT)', name : 'servername', width : 159, sortable : false, align: 'left'},
	{display: 'DEST', name : 'size', width : 341, sortable : true, align: 'left'},
	],
		

	searchitems : [
	{display: 'ALL', name : 'ALL'},

	],
	sortname: 'hour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=\"font-size:22px\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});

}
setTimeout('StartLogsSquidTable$t()',800);

</script>
";
	echo $html;

}

function events_list(){
	$sock=new sockets();
	$catz=new mysql_catz();
//grep -E 'FIREHOL:.*?IN=' /var/log/syslog
if(!isset($_POST["rp"])){$_POST["rp"]=50;}
	$sock->getFrameWork("firehol.php?accesses=yes&rp={$_POST["rp"]}&query=".urlencode($_POST["query"]));
	$filename="/usr/share/artica-postfix/ressources/logs/firehol.log.tmp";
	$dataZ=explode("\n",@file_get_contents($filename));
	$tpl=new templates();
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($data);
	$data['rows'] = array();
	$today=date("Y-m-d");
	$tcp=new IP();

	$cachedT=$tpl->_ENGINE_parse_body("{cached}");
	$unknown=$tpl->javascript_parse_text("{unknown}");
	$c=0;
	$fontsize=16;
	if(count($dataZ)==0){json_error_show("no data");}
	
	krsort($dataZ);
	$IP=new IP();
	while (list ($num, $line) = each ($dataZ)){
		
		
		
		$color="black";
		if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*? FIREHOL:(.+?):(.+)#", $line,$re)){continue;}
		
		$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
		$RULE=$re[4];
		$line=$re[5];
		
		$TR=preg_split("/[\s]+/", $line);
		while (list ($num, $xdata) = each ($TR)){
			if(!preg_match("#([A-Z]+)=(.*)#",$xdata,$ri)){continue;}
			$MAIN[$ri[1]]=$ri[2];
		}
		
		
		$MACZ=explode(":",$MAIN["MAC"]);
		
		$MAC_DEST="{$MACZ[0]}:{$MACZ[1]}:{$MACZ[2]}:{$MACZ[3]}:{$MACZ[4]}:{$MACZ[5]}";
		$MAC_SRC="{$MACZ[6]}:{$MACZ[7]}:{$MACZ[8]}:{$MACZ[9]}:{$MACZ[10]}:{$MACZ[11]}";
		
		
		if(!isset($MAIN["ETH_NAME"][$MAIN["OUT"]])){
			$q=new system_nic($MAIN["OUT"]);
			$MAIN["ETH_NAME"][$MAIN["OUT"]]=$q->NICNAME;
		}

		if(!isset($MAIN["ETH_NAME"][$MAIN["IN"]])){
			$q=new system_nic($MAIN["IN"]);
			$MAIN["ETH_NAME"][$MAIN["IN"]]=$q->NICNAME;
		}
	

		$c++;
		$xtime=date("H:i:s");
		$data['rows'][] = array(
				'id' => md5($line),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>$xtime</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$RULE</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$MAIN["PROTO"]}</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$MAIN["IN"]} {$MAIN["ETH_NAME"][$MAIN["IN"]]}</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$MAIN["SRC"]} <span style='font-size:12px'>($MAC_SRC/{$MAIN["SPT"]})</span></span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$MAIN["OUT"]} {$MAIN["ETH_NAME"][$MAIN["OUT"]]}</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$MAIN["DST"]}:<strong>{$MAIN["DPT"]}</strong> <span style='font-size:12px'>($MAC_DEST)</span></span>",
						"<center style='font-size:{$fontsize}px;color:$color'>$link</center>",
						"<span style='font-size:{$fontsize}px;color:$color'>$size</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$duration}$durationunit</span>",
						"$ip"
				)
		);

	}


	$data['total'] = $c;
	echo json_encode($data);

}