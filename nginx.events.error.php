<?php
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.mysql.squid.builder.php');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}

popup();
function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$TB_WIDTH=872;
	$TB2_WIDTH=610;
	$TB_HEIGHT=600;
	$TB_EV=574;
	if(is_numeric($_GET["TB_WIDTH"])){$TB_WIDTH=$_GET["TB_WIDTH"];}
	if(is_numeric($_GET["TB_HEIGHT"])){$TB_HEIGHT=$_GET["TB_HEIGHT"];}
	if(is_numeric($_GET["TB_EV"])){$TB_EV=$_GET["TB_EV"];}
	$t=time();
	$SE_SERV="{display: '$service', name : 'service'},";
	$TB_SERV="{display: '$service', name : 'zDate', width :97, sortable : false, align: 'left'},";
	if($_GET["force-prefix"]<>null){$MasterTitle=$_GET["force-prefix"];$TB_SERV=null;$SE_SERV=null;}
	
	$MasterTitle=$tpl->javascript_parse_text("{access_events}:{errors}");
	$hostname=$tpl->javascript_parse_text("{type}");
	$client=$tpl->javascript_parse_text("{client}");
	$event=$tpl->javascript_parse_text("{events}");
	$status=$tpl->javascript_parse_text("{status}");
	


	
	
	
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	
	$buttons=null;
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?search=yes&prepend={$_GET["prepend"]}&force-prefix={$_GET["force-prefix"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$date</span>', name : 'a1', width :166, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$hostname</span>', name : 'a3', width :100, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$event</span>', name : 'events', width : 1133, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		$SE_SERV
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:30px>$MasterTitle</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height:550 ,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	
</script>";
	
	echo $html;
	return ;

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$squid=new mysql_squid_builder();
	$total=0;
	$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	$removeService=false;
	$output="/usr/share/artica-postfix/ressources/logs/web/nginx.query.errors";
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$StatusCodes=$squid->LoadStatusCodes();

	$search=base64_encode($_POST["query"]);
	$sock->getFrameWork("nginx.php?access-errors=$search&rp={$_POST["rp"]}");	
	
	$array=explode("\n", @file_get_contents($output));
	
	$total = count($array);
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	$today=$tpl->_ENGINE_parse_body("{today}");
	$c=0;
	
	
	if($GLOBALS["VERBOSE"]){echo "<H1>Array of ".count($array)." Lines</H1>\n";}
	
	
	$style="<span style='font-size:16px'>";
	$styleoff="</span>";
	
	while (list ($key, $line) = each ($array) ){
			$lines=array();
			$line=trim($line);
			if($line==null){continue;}

			
			if(!preg_match("#(.+?)\s+\[(.+?)\]\s+(.+)#",$line,$re)){continue;}
	
			$time=strtotime($re[1]);
			$service=$re[2];
			$line=$re[3];
			
			if(date("Y-m-d",$time)==date("Y-m-d")){
				$zdate=date("H:i:s");
			}else{
				$zdate=date("Y-m-d H:i:s");
			}

			
			$lines[]="$style$zdate$styleoff";
			$lines[]="$style$service$styleoff";
			$lines[]="$style$line$styleoff";

		
		$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
		

	}
	
	if($c==0){json_error_show("no data");}
	
	$data['total'] = $c;
	
echo json_encode($data);		

}









?>