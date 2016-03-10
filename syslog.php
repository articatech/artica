<?php
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["sjs"])){js_popup();exit;}
js();



function js_popup(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$syslogpath=urlencode($_GET["syslog-path"]);
	echo "YahooWin6(1169,'$page?popup=yes&syslog-path=$syslogpath','Syslog')";
	
}

function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="
	
	
function SyslogLoadpage(){
	$('#BodyContent').load('$page?popup=yes');
	}
	
	
	SyslogLoadpage();";
	
	echo $html;
	
	
}


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
	if($_GET["prepend"]<>null){$MasterTitle=$MasterTitle."&raquo;{$_GET["prepend"]}";}
	$syslogpath=urlencode($_GET["syslog-path"]);
	if($MasterTitle==null){$MasterTitle=$tpl->javascript_parse_text("{syslog_events}");}
	
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
	url: '$page?search=yes&prepend={$_GET["prepend"]}&force-prefix={$_GET["force-prefix"]}&syslog-path=$syslogpath',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :93, sortable : true, align: 'left'},
		$TB_SERV
		{display: 'PID', name : 'zDate', width :40, sortable : false, align: 'left'},
		{display: '$events', name : 'events', width : 1140, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		$SE_SERV
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:16px>$MasterTitle</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height:$TB_HEIGHT ,
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
	
	
	
	
	$table="events";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	
	
$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	$removeService=false;
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}

	if($_GET["force-prefix"]<>null){
		if($_POST["qtype"]=="service"){$_POST["query"]=null;}
		$_GET["prefix"]=$_GET["force-prefix"];
		$removeService=true;
	}
	
	$syslogpath=urlencode($_GET["syslog-path"]);

	if($_GET["query"]<>null){

		$search=base64_encode($_POST["query"]);
		$sock->getFrameWork("cmd.php?syslog-query=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}&syslog-path=$syslogpath");	
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);
		
	}else{
		$sock->getFrameWork("cmd.php?syslog-query=&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}&syslog-path=$syslogpath");
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);
	}
	
	
	
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
	
	
	while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
			$date=null;
			$host=null;
			$service=null;
			$pid=null;
			$color="black";
			if(preg_match("#(ERROR|WARN|FATAL|UNABLE|Failed|not found|denied|OVERLOAD)#i", $line)){$color="#D61010";}
				
			$style="<span style='color:$color'>";
			$styleoff="</span>";
			
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?)\[([0-9]+)\]:\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=$re[6];
			$line=$re[7];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
			if($_GET["prefix"]=="haproxy"){$line=Parse_haproxy($line);}
			
			$lines=array();
			$lines[]="$style$date$styleoff";
			if(!$removeService){$lines[]="$style$service$styleoff";}
			$lines[]="$style$pid$styleoff";
			$lines[]="$style$line$styleoff";
			
			$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
			continue;	
		}
		
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?):\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=null;
			$line=$re[6];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
			if($_GET["prefix"]=="haproxy"){$line=Parse_haproxy($line);}
			$lines=array();
			$lines[]="$style$date$styleoff";
			if(!$removeService){$lines[]="$style$service$styleoff";}
			$lines[]="$style$pid$styleoff";
			$lines[]="$style$line$styleoff";			
			
			$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
			continue;				
		}
		
			if($_GET["prefix"]=="haproxy"){$line=Parse_haproxy($line);}
			$lines=array();
			$lines[]="$style$date$styleoff";
			if(!$removeService){$lines[]="$style$service$styleoff";}
			$lines[]="$style$pid$styleoff";
			$lines[]="$style$line$styleoff";			
		
		$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
		

	}
	
	if($c==0){json_error_show("no data");}
	
	$data['total'] = $c;
	
echo json_encode($data);		

}

function Parse_haproxy($line){
	
	
	//0=HASTATS 1=1451328946 2=192.168.1.8 3=articaproxy 4=proxies_service 5=- 6=- 7= 8=- 9=- 10=CONNECT clients4.google.com:443 HTTP/1.1 11=403 12=+188 13=228 14=PR
	if(strpos(" $line", "HASTATS:::")>0){
		$lineZ=explode(":::",$line);
		$servicename=$lineZ[4];
		$backend_name=$lineZ[7];
		$uri=$lineZ[10];
		$HTTP_CODE=$lineZ[11];
		$PR=$lineZ[14];
		$backend_name_js="<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('haproxy.php?backend-js=yes&backendname=$backend_name&servicename=$servicename&t=');\" style=\"text-decoration:underline;color:#CF0707;font-weight:bolder\">";
		return "($PR) $backend_name_js$backend_name</a> [$HTTP_CODE] From $servicename/$backend_name to $uri";
	}
	
	if(preg_match("#Server\s+(.+?)\/(.+?)\s+is\s+#", $line,$re)){
		$line=str_replace($re[2], "$js{$re[2]}</a>", $line);
		return $line;
		
	}
	
	
	if(preg_match("#\]\s+.+?\s+(.+?)\/(.+?)\s+[0-9]+\/[0-9]+\/[0-9]+#", $line,$re)){
		$js="<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('haproxy.php?backend-js=yes&backendname={$re[2]}&servicename={$re[1]}&t=');\" style=\"text-decoration:underline;color:#CF0707;font-weight:bolder\">";
		$line=str_replace($re[2], "$js{$re[2]}</a>", $line);
		return $line;
	}
	return $line;
	
}




?>