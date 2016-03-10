<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once("ressources/class.templates.inc");
	include_once("ressources/class.ldap.inc");
	
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();exit();
	}

	if(isset($_GET["list-table"])){ events_list();exit;}
	if(isset($_GET["sequence-js"])){sequence_js();exit;}
	page();
	
	
function sequence_js() {
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$datas=file_get_contents('js/artica_settings.js');
	$PHP_VERSION=PHP_VERSION;
	$html="YahooWin(1174,'$page?sequence-popup={$_GET["sequence"]}','{$_GET["sequence"]}');";
	echo $html;
	
}	
	
function page(){
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$q=new mysql();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$failed=$tpl->javascript_parse_text("{failed}");
	$all=$tpl->javascript_parse_text("{all}");
	$import=$tpl->javascript_parse_text("{import}");
	$title=$tpl->javascript_parse_text("{last_transaction_messages}");
	$country=$tpl->javascript_parse_text("{country}");
	$service=$tpl->javascript_parse_text("{service}");
	$process=$tpl->javascript_parse_text("{process}");
	$status=$tpl->javascript_parse_text("{status}");
	$date=$tpl->javascript_parse_text("{zDate}");
	$event=$tpl->javascript_parse_text("{event}");
	$buttons="
		buttons : [
		{name: '$failed', bclass: 'search', onpress : Failed$t},
		{name: '$all', bclass: 'search', onpress :All$t},
		],";
	
	
	$height="500";
	if($_GET["sequence-popup"]<>null){$height=300;}
	$html="
	
	
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
		<script>
		var memid$t='';
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?list-table=yes&sequence={$_GET["sequence-popup"]}&ou={$_GET["ou"]}&t=$t',
		dataType: 'json',
		colModel : [
		
		{display: '$date', name : 'time_connect', width : 116, sortable : true, align: 'left'},
		{display: '$service', name : 'sender_user', width :58, sortable : true, align: 'left'},
		{display: '$process', name : 'delivery_user', width : 56, sortable : false, align: 'left'},
		{display: '$event', name : 'bounce_error', width : 865, sortable : false, align: 'left'},
		],
		$buttons
		searchitems : [
		{display: '$event', name : 'sender_user'},

		],
		sortname: 'time_connect',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: {$height},
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	function Failed$t(){
		$('#flexRT$t').flexOptions({url: '$page?list-table=yes&sequence={$_GET["sequence-popup"]}&ou={$_GET["ou"]}&t=$t&failed=yes'}).flexReload(); 
	}
	
	function All$t(){
		$('#flexRT$t').flexOptions({url: '$page?list-table=yes&sequence={$_GET["sequence-popup"]}&ou={$_GET["ou"]}&t=$t'}).flexReload();
	}
	
	</script>";
		echo $html;
	}	
	
	
	function events_list(){
		include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
		$MyPage=CurrentPageName();
		$sock=new sockets();
		$users=new usersMenus();
		$maillog_path=$users->maillog_path;
		$search=base64_encode(string_to_flexregex());
		
		$sock->getFrameWork("postfix.php?maillog-postfix=yes&filter=$search&maillog=$maillog_path&rp={$_POST["rp"]}&sequence={$_GET["sequence"]}&failed={$_GET["failed"]}");
		$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/postlogs{$_GET["sequence"]}"));
		@unlink("/usr/share/artica-postfix/ressources/logs/web/postlogs{$_GET["sequence"]}");
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
		while (list ($index, $line) = each ($array) ){
			
			if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+postfix\/(.+?)\[([0-9]+)\]:(.+)#i", $line,$re)){continue;}
			$stylew="normal";
			$color="black";
			$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
			$serv=$re[4];
			$service=$re[5];
			$pid=$re[6];
			$ligne=trim($re[7]);
			
			$ligne=htmlentities($ligne);
			$zDate=date("m D H:i:s",$date);
			
			$img=statusLogs($line);
	
			$m5=md5($line);
			
			if(preg_match("#warning#", $ligne)){$color="#EA8E09";}
			if(preg_match("#reject:#", $ligne)){$color="#EA0C09";$stylew="bold";}
			if(preg_match("#listed by domain#", $ligne)){$color="#EA0C09";}
			if(preg_match("#Greylisting in action#", $ligne)){$color="#515151";$stylew="bold";}
			if(preg_match("#status=sent#", $ligne)){$color="#028A29";}
			if(preg_match("#: removed#", $ligne)){$color="#028A29";}
			if(preg_match("#status=deferred#", $ligne)){$color="#EA0C09";$stylew="bold";}
			if(preg_match("#Connection timed out#", $ligne)){$color="#EA0C09";}
			
			
			if(preg_match("#^([0-9A-Z]+):\s+#", $ligne,$re)){
				$instance=$re[1];
				
				$ligne=str_replace($re[1],"<a href=\"javascript:blur();\" 
						OnClick=\"javascript:Loadjs('$MyPage?sequence-js=yes&sequence={$re[1]}')\"
						style='text-decoration:underline;font-size:12px;color:$color;font-weight:$stylew'
						>{$re[1]}</a>
						",  $ligne);
				
			}
			
			$data['rows'][] = array(
					'id' => "dom$m5",
					'cell' => array("
							<span style='font-size:12px;color:$color;font-weight:$stylew'>$zDate</span>",
							"<span style='font-size:12px;color:$color;font-weight:$stylew'>$service</span>",
							"<span style='font-size:12px;color:$color;font-weight:$stylew'>$pid</span>",
							"<span style='font-size:12px;color:$color;font-weight:$stylew'>$ligne</span>")
			);
	
	
		}
		$data['page'] = 1;
		$data['total'] =count($array);
		echo json_encode($data);
	
	}	