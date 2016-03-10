<?php
session_start();
if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.apache.inc');
include_once('ressources/class.freeweb.inc');
include_once('ressources/class.artica.graphs.inc');
$user=new usersMenus();
if($user->AsWebMaster==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}
if(isset($_GET["table-list"])){events_list();exit;}

page();

function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{POSTFIX_EVENTS}");
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
	$servername=$_GET["servername"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$form="<div style='width:900px' class=form>";
	if(isset($_GET["noform"])){$form="<div style='margin-left:-15px'>";}

	$table_width=865;
	$events_wdht=634;
	if(isset($_GET["miniadm"])){
		$table_width=955;
		$events_wdht=601;
	}

	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	<script>
	var memid='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&servername=$servername&t=$t&type={$_GET["type"]}',
	dataType: 'json',
	colModel : [
	{display: '$zDate', name : 'zDate', width : 74, sortable : true, align: 'left'},
	{display: '$service', name : 'host', width : 58, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'left'},
	{display: '$events', name : 'events', width :$events_wdht, sortable : true, align: 'left'},
	],
	$buttons
	searchitems : [
	{display: '$events', name : 'zDate'},
	],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '$servername::{$_GET["type"]}',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

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
	
	$query=base64_encode(string_to_regex($_POST["query"]));
	$array=explode("\n",(base64_decode($sock->getFrameWork("freeweb.php?query-logs=yes&servername={$_GET["servername"]}&filter=$query&rp={$_POST["rp"]}&type={$_GET["type"]}"))));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}

	while (list ($index, $line) = each ($array) ){
		$date=null;
		if(preg_match("#\[(.+?)\]\s+\[(.*?)\]\s+(.+)#", $line,$re)){
			$date="{$re[1]}";
			$errortype=$re[2];
			$line=$re[3];
			if(preg_match("#\[client\s+(.+?)\]#", $line,$re)){
				$line=str_replace($re[0], "", $line);
				$line="[".$re[1]."] ".$line;		
			}
				
		}
		
		
		
		if(preg_match("#(.+?) - (.*?) \[(.+?)\]\s+(.+)#",$line,$re)){
			$date="{$re[3]}";
			$time=strtotime($date);
			if(date('Y-m-d',$time)==date("Y-m-d")){
				$dateText=date("H:i:s",$time);
			}else{
				$dateText=date("H:i:s - l d",$time);
			}
			$errortype=$re[2];
			$line="[".$re[1]."] ".$re[4];			
		}
		

		
		$line=str_replace("HTTP/1.1", "", $line);
		$line=str_replace("HTTP/1.0", "", $line);

		$img=statusLogs($line);
		$m5=md5($line);
		
		if(trim($dateText)==null){
			if(preg_match("#(.+?)\s+([0-9:]+)\s+\[(.+?)\]\s+([0-9]+)\#[0-9]+:(.+)#", $line,$re)){
				$re[1]=str_replace(date("Y/m/d"), "", $re[1]);
				$dateText=$re[1]." ".$re[2];
				$errortype=$re[3];
				$line=$re[5];
			}
		}
		

		$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
						<span style='font-size:12px'>$dateText</span>",
						"<span style='font-size:12px'>$errortype</span>",
						"<img src='$img'>",
						"<span style='font-size:12px'>$line</span>")
		);


	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);

}