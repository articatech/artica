<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.squid.builder.php');


$users=new usersMenus();
if(!$users->AsProxyMonitor){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){show_list();exit;}

js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{ntlm_processes}");
	echo "YahooWin5('890','$page?popup=yes&cpu={$_GET["cpu"]}','$title')";	
	
}

/*   ID #	     FD	    PID	 # Requests	  # Replies	 Flags	   Time	 Offset	Request
    730	     29	  24794	       4993	       4993	     	  0.003	      0	(none)
    731	     39	  24795	       1170	       1170	     	  0.008	      0	(none)
    732	     45	  24796	        514	        514	     	  0.012	      0	(none)
    733	     47	  24797	        278	        278	     	  0.005	      0	(none)
    734	     49	  24798	        164	        164	     	  0.004	      0	(none)
    746	     89	  27140	         94	         94	     	  0.005	      0	(none)
    747	     92	  27141	         56	         56	     	  0.006	      0	(none)
    748	     94	  27142	         30	         30	     	  0.003	      0	(none)
    749	     82	  25212	         12	         12	     	  0.006	      0	(none)
    750	     86	  25213	          0	          0	     	  0.000	      0	(none)
    751	     88	  25214	          0	          0	     	  0.000	      0	(none)
*/

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$requests=$tpl->javascript_parse_text("{requests}");
	$connections=$tpl->javascript_parse_text("{connections}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$errors=$tpl->javascript_parse_text("{errors}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	$TCP_HIT=$tpl->javascript_parse_text("{cached}");
	$TCP_MISS=$tpl->javascript_parse_text("{not_cached}");
	$TCP_REDIRECT=$tpl->javascript_parse_text("{REDIRECT}");
	$TCP_TUNNEL=$tpl->javascript_parse_text("{ssl}");
	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$title=$tpl->javascript_parse_text("{ntlm_processes}");
	$html="
	<table class='$t' style='display:none' id='$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#$t').flexigrid({
	url: '$page?list=yes&cpu={$_GET["cpu"]}',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>PID</strong>', name : 'uid', width : 211, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>RQS</strong>', name : 'ipaddr', width :139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>REPLIES</strong>', name : 'CUR_CNX', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>FLAG</strong>', name : 'RQS', width : 139, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>TIME</strong>', name : 'TCP_HIT', width : 139, sortable : false, align: 'right'},
	],

	sortname: 'CUR_CNX',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=title-$t style=font-size:30px>$title</span>',
	useRp: true,
	rp: 500,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

function refresh$t(){
$('#CLIENT_LIST_TABLE').flexReload();

}

StartLogsSquidTable$t();
</script>
";
	echo $html;
}


function show_list(){
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$cpu=$_GET["cpu"];
	include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
	$cache_manager=new cache_manager();
	$datas=explode("\n",$cache_manager->makeQuery("ntlmauthenticator"));
	if(!$cache_manager->ok){json_error_show("Err!");}
	
	$CPU_NUMBER=0;
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			continue;
		}
	
		 
		if(!preg_match("#([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+([0-9\.]+)#",$ligne,$re)){continue;}
		$pid=$re[3];
		$rqs=$re[4];
		$rply=$re[5];
		$flags=$re[6];
		$time=$re[7];
	
		$MAIN[$CPU_NUMBER][$pid]["PID"]=$pid;
		$MAIN[$CPU_NUMBER][$pid]["RQS"]=$rqs;
		$MAIN[$CPU_NUMBER][$pid]["RPLY"]=$rply;
		$MAIN[$CPU_NUMBER][$pid]["FLAG"]=$flags;
		$MAIN[$CPU_NUMBER][$pid]["TIME"]=$time;
		 
	}
	
	
	//print_r($MAIN);
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count( $MAIN[$cpu]);
	$data['rows'] = array();
	
	while (list ($pid, $ligne) = each ($MAIN[$cpu]) ){
		$md5=md5(serialize($ligne));
		$PID=numberFormat($ligne["PID"],0,""," ");
		$RQS=numberFormat($ligne["RQS"],0,""," ");
		$RPLY=numberFormat($ligne["RPLY"],0,""," ");
		$FLAG=$ligne["FLAG"];
		$TIME=$ligne["TIME"];
		
	
	
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<span style='font-size:16px'>$PID</a></span>",
						"<span style='font-size:16px'>$RQS</a></span>",
						"<span style='font-size:16px'>$RPLY</span>",
						"<span style='font-size:16px'>$RQS</span>",
						"<span style='font-size:16px'>$FLAG</span>",
						"<span style='font-size:16px'>$TIME</span>",

	
							
				)
		);
	}
	
	
	echo json_encode($data);
	}
