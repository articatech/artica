<?php
	if(posix_getuid()==0){die();}
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.haproxy.inc');
	
	
	
	
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	//status
	if(isset($_GET["haproxy-status"])){haproxy_status_bar();exit;}
	if(isset($_GET["popup-status"])){haproxy_status_popup();exit;}
	if(isset($_GET["haproxy-status-popup-content"])){haproxy_status_popup_content();exit;}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){service_cmds_popup();exit;}
	if(isset($_GET["service-cmds-logs"])){service_cmds_logs();exit;}
	if(isset($_GET["balancer-method-options"])){balancer_method_options();exit;}
	
	//backend-status
	if(isset($_GET["backend-status"])){haproxy_backend_status();exit;}
	if(isset($_GET["backend-status-list"])){haproxy_backend_status_list();exit;}
	
	
	if(isset($_GET["millisec"])){milliseconds_text();exit;}
	if(isset($_GET["events"])){events();exit;}
	
	//conf
	if(isset($_GET["popup-view-script"])){popup_script();exit;}
	if(isset($_POST["HAPROXY_CONTENT"])){popup_script_save();exit;}
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["balancers"])){balancers();exit;}
	if(isset($_GET["balancers-list"])){balancers_list();exit;}
	if(isset($_GET["balancer-js"])){balancer_js();exit;}
	if(isset($_GET["balancer-tabs"])){balancer_tabs();exit;}
	if(isset($_GET["balancer-settings"])){balancer_settings();exit;}
	if(isset($_POST["balancer-save"])){balancer_save();exit;}
	if(isset($_POST["balancer-delete"])){balancer_delete();exit;}
	if(isset($_POST["balancer-enable"])){balancer_enable();exit;}
	
	if(isset($_GET["balancer-backends"])){backends();exit;}
	if(isset($_GET["balancer-backends-list"])){backends_list();exit;}
	
	if(isset($_GET["backend-js"])){backends_js();exit;}
	if(isset($_GET["backend-tabs"])){backends_tabs();exit;}
	if(isset($_GET["backend-settings"])){backends_settings();exit;}
	if(isset($_POST["backends-save"])){backends_save();exit;}
	if(isset($_POST["backends-delete"])){backends_delete();exit;}
	if(isset($_POST["backends-enable"])){backends_enable();exit;}
	
	//cmds
	if(isset($_POST["balancer-cmd-stop"])){backends_action_stop();exit;}
	if(isset($_POST["balancer-cmd-start"])){backends_action_start();exit;}

	
js();
function backends_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	if($_GET["backendname"]==null){$title="{$_GET["servicename"]}&raquo;$new_backend";}else{$title="{$_GET["servicename"]}&raquo;{$_GET["backendname"]}";}
	echo "YahooWin4(850,'$page?backend-tabs=yes&servicename={$_GET["servicename"]}&backendname={$_GET["backendname"]}&t={$_GET["t"]}&tt={$_GET["tt"]}','$title')";
	
}

function backends_action_stop(){
	$sock=new sockets();
	$sock->getFrameWork("haproxy.php?stop-socket={$_POST["balancer-cmd-stop"]}");
	
}
function backends_action_start(){
	$sock=new sockets();
	$sock->getFrameWork("haproxy.php?start-socket={$_POST["balancer-cmd-start"]}");	
}

function balancer_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$new_service=$tpl->_ENGINE_parse_body("{new_service}");
	if($_GET["servicename"]==null){$title=$new_service;}else{$title=$_GET["servicename"];}
	echo "YahooWin3(990,'$page?balancer-tabs=yes&servicename={$_GET["servicename"]}&t={$_GET["t"]}','$title')";
	
}

function js(){
	$page=CurrentPageName();
	echo "
		AnimateDiv('BodyContent');
		LoadAjax('BodyContent','$page?tabs=yes');
		QuickLinkShow('quicklinks-load_balancing');
		";
	
}

function haproxy_status_popup(){
	$page=CurrentPageName();	
	$t=time();
	$html="<div id='$t'></div>
	<script>
		function RefreshHaProxyStatus(){
			LoadAjax('$t','$page?haproxy-status-popup-content=yes');
		}
		
		RefreshHaProxyStatus();
	</script>
	
	";
	echo $html;
	
	
}

function haproxy_backend_status(){
	
	// see http://code.google.com/p/haproxy-docs/wiki/StatisticsMonitoring
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$q->BuildTables();
	$servicename=$tpl->_ENGINE_parse_body("{servicename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_service=$tpl->_ENGINE_parse_body("{new_service}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$service=$tpl->_ENGINE_parse_body("{service}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$requests=$tpl->_ENGINE_parse_body("{requests}");
	$t=time();		
	$view_script=$tpl->_ENGINE_parse_body("{view_script}");
	$title=$tpl->_ENGINE_parse_body("{backends_status}");
	
	$buttons="buttons : [
	{name: '$new_service', bclass: 'add', onpress : HaProxyAdd},
	{name: '$view_script', bclass: 'Script', onpress : HaBackConf},
		],	";
	
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?backend-status-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 65, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$servicename</span>', name : 'servicename', width : 284, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$backends</span>', name : 'none2', width : 404, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>IN</span>', name : 'enabled', width : 124, sortable : true, align: 'right'},
		{display: '<span style=font-size:22px>OUT</span>', name : 'delete', width : 124, sortable : false, align: 'right'},
		{display: '<span style=font-size:22px>$requests</span>', name : 'delete', width : 124, sortable : false, align: 'right'},
		{display: '<span style=font-size:22px>CMD</span>', name : 'delete', width : 124, sortable : false, align: 'center'},
		
	],

	
	
	searchitems : [
		{display: '$servicename', name : 'servicename'},
		{display: '$interface', name : 'listen_ip'},
		],
	sortname: 'servicename',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});


	var x_HaProxyDownserv= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}	
	
	function HaProxyUpserv(based){
		var XHR = new XHRConnection();
		XHR.appendData('balancer-cmd-start', based);
		XHR.sendAndLoad('$page', 'POST',x_HaProxyDownserv);	
	}
	
	
	function HaProxyDownserv(based){
		var XHR = new XHRConnection();
		XHR.appendData('balancer-cmd-stop', based);
		XHR.sendAndLoad('$page', 'POST',x_HaProxyDownserv);
		  		
	}

</script>
	
	";
	
	echo $html;	
	
	
	
}

function haproxy_backend_status_list(){
	
	$sock=new sockets();
	$table=unserialize(base64_decode($sock->getFrameWork("haproxy.php?global-stats=yes")));
	if(count($table)<2){json_error_show("No data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($table);
	$data['rows'] = array();
	
	
$status["UNK"]="unknown";
$status["INI"]="initializing";
$status["SOCKERR"]="socket error";
$status["L4OK"]="check passed on layer 4, no upper layers testing enabled";
$status["L4TMOUT"]="layer 1-4 timeout";
$status["L4CON"]="layer 1-4 connection problem";
$status["L6OK"]="check passed on layer 6";
$status["L6TOUT"]="layer 6 (SSL) timeout";
$status["L6RSP"]="layer 6 invalid response - protocol error";
$status["L7OK"]="check passed on layer 7";
$status["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
$status["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
$status["L7RSP"]="layer 7 invalid response - protocol error";
$status["L7STS"]="layer 7 response error, for example HTTP 5xx"; 
	
$ERR["SOCKERR"]=true;
$ERR["L4TMOUT"]=true;
$ERR["L4CON"]=true;
$ERR["L6TOUT"]=true;
$ERR["L6RSP"]=true;
$ERR["L7TOUT"]=true;
$ERR["L7RSP"]=true;
$ERR["L7STS"]=true;

$typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");
	
	while (list ($num, $ligne) = each ($table) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#\##", $ligne)){continue;}
		$f=explode(",", $ligne);
		$pxname=$f[0];
		$svname=$f[1];
		$qcur=$f[2];
		$qmax=$f[3];
		$scur=$f[4];
		$smax=$f[5];
		$slim=$f[6];
		$stot=$f[7];
		$bin=FormatBytes($f[8]/1024);
		$bout=FormatBytes($f[9]/1024);
		$dreq=$f[10];
		$dresp=$f[11];
		$ereq=$f[12];
		$econ=$f[13];
		$eresp=$f[14];
		$wretr=$f[15];
		$wredis=$f[16];
		$status=$f[17];
		$weight=$f[18];
		$act=$f[19];
		$bck=$f[20];
		$chkfail=$f[21];
		$chkdown=$f[22];
		$lastchg=$f[23];
		$downtime=$f[24];
		$qlimit=$f[25];
		$pid=$f[26];
		$iid=$f[27];
		$sid=$f[28];
		$throttle=$f[29];
		$lbtot=$f[30];
		$tracked=$f[31];
		$type=$typof[$f[32]];
		$rate=$f[33];
		$rate_lim=$f[34];
		$rate_max=$f[35];
		$check_status=$f[36];
		$check_code=$f[37];
		$check_duration=$f[38];
		$hrsp_1xx=$f[39];
		$hrsp_2xx=$f[40];
		$hrsp_3xx=$f[41];
		$hrsp_4xx=$f[42];
		$hrsp_5xx=$f[43];
		$hrsp_other=$f[44];
		$hanafail=$f[45];
		$req_rate=$f[46];
		$req_rate_max=$f[47];
		$req_tot=$f[48];
		$cli_abrt=$f[49];
		$srv_abrt=$f[50];
		if(!is_numeric($req_tot)){$req_tot=0;}
		$img="ok42.png";
		$color="black";
		$check_status_text=$status[$check_status];
		if(isset($ERR[$check_status])){$img="error-42.png";$color="#D20C0C";}
		$md5=md5($ligne);
		$button=null;
		$arraySRV=base64_encode(serialize(array($pxname,$svname)));
		if($type=="server"){
			
			$downser="HaProxyDownserv('$arraySRV');";
			$button=imgsimple("32-stop.png",null,$downser);
			
		}
		
		if($status=="MAINT"){
			$downser="HaProxyUpserv('$arraySRV');";
			$img="warning42.png";
			$button=imgsimple("32-run.png",null,$downser);
		}
		
		if(preg_match("#DOWN#", $status)){
			$downser="HaProxyUpserv('$arraySRV');";
			$button=null;
			$img="error-42.png";
			$color="#D20C0C";
			$button=imgsimple("32-run.png",null,$downser);
		}
		
			
		if($type=="backend"){continue;}	
		if($pxname=="admin_page"){continue;}
		
		$data['rows'][] = array(
		'id' => "$md5",
		'cell' => array("<center><img src='img/$img'></center>",
		"<span style='font-size:22px;color:$color'>$pxname</span>",
		"<span style='font-size:22px;color:$color'>$svname ($type - $status)</span>",
		"<span style='font-size:22px;color:$color'>$bin</span>",
		"<span style='font-size:22px;color:$color'>$bout</span>",
		"<span style='font-size:22px;color:$color'>$req_tot</span>",
		"<center style='font-size:22px;color:$color'>$button</center>",
		
		$disable,$delete)
		);
		
		
	}
	
	echo json_encode($data);
}



function haproxy_status_popup_content(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$tr=array();
	$fontsize=null;
	$page=CurrentPageName();	
	$datas=base64_decode($sock->getFrameWork("haproxy.php?main-status=yes"));
	$ini->loadString($datas);	
	$status=DAEMON_STATUS_ROUND("APP_HAPROXY",$ini,null,0);
	if(isset($_GET["bigsize"])){
		
		$fontsize="style='font-size:14px'";
		$refresh="<div style='text-align:right'>
				".imgtootltip("refresh-32.png",
						"{refresh}",
						"LoadAjax('haproxy-status','haproxy.php?haproxy-status-popup-content=yes&bigsize=yes');")."</div>";
	
	}
	
	$tbl=unserialize(base64_decode($sock->getFrameWork("haproxy.php?global-status=yes")));
	if(count($tbl)>2){
		while (list ($num, $ligne) = each ($tbl) ){
			if(!preg_match("#^(.*?):(.*)#", $ligne,$re)){continue;}
			$tr[]="
			<tr>
				<td class=legend nowrap $fontsize>".trim($re[1])."</td>
				<td nowrap><strong $fontsize>".trim($re[2])."</strong></td>
			</tr>
			";
			
		}
	}
	
	if(count($tr)>1){
		$TRTAB="	<div style='width:98%' class=form>
			<table style='width:100%' >". @implode("\n", $tr)."
			</table>
	</div>";
	}
	
	$table="
	<center style=''>
	$status
	$refresh
	$TRTAB
	</center>
	";	
	echo $tpl->_ENGINE_parse_body($table);
}

	
function balancers(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$q->BuildTables();
	$servicename=$tpl->_ENGINE_parse_body("{servicename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_service=$tpl->_ENGINE_parse_body("{new_service}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$service=$tpl->_ENGINE_parse_body("{service}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$Apply=$tpl->javascript_parse_text("{apply}");
	$t=time();		
	$view_script=$tpl->_ENGINE_parse_body("{view_script}");
	$html="
	<table class='MAIN_HAPROXY_BALANCERS_TABLE' style='display: none' id='MAIN_HAPROXY_BALANCERS_TABLE' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#MAIN_HAPROXY_BALANCERS_TABLE').flexigrid({
	url: '$page?balancers-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$servicename</span>', name : 'servicename', width : 595, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$interface</span>', name : 'listen_ip', width : 296, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$backends</span>', name : 'none2', width : 210, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width : 113, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 122, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:18px>$new_service</strong>', bclass: 'add', onpress : HaProxyAdd},
	{name: '<strong style=font-size:18px>$view_script</strong>', bclass: 'Script', onpress : HaBackConf},
	{name: '<strong style=font-size:18px>$Apply</strong>', bclass: 'apply', onpress : HaApply},
	{name: '<strong style=font-size:18px>$status:</strong><strong id=haproxy-status-$t></strong>', bclass: 'Net', onpress : ZoomHapProxStatus},
		],	
	searchitems : [
		{display: '$servicename', name : 'servicename'},
		{display: '$interface', name : 'listen_ip'},
		],
	sortname: 'servicename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});
function HaProxyAdd() {
	Loadjs('$page?balancer-js=yes&servicename=&t=$t');
	
}	
function HaBackConf(){
		YahooWin2('900','$page?popup-view-script=yes','$view_script');
}

function ZoomHapProxStatus(){
	YahooWin2('300','$page?popup-status=yes','$status');
}

function HaApply(){
	Loadjs('haproxy.progress.php');
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	var x_EnableDisableHaService= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
	}
	
	var x_EnableDisableHaServiceSilent= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
	}	

	var x_BalancerDeleteService= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowBF'+tmp$t).remove();
	}	
	
	
	function BalancerDeleteService(servicename,md){
		tmp$t=md;
		if(confirm('$delete_service :'+servicename+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('balancer-delete', servicename);
			XHR.sendAndLoad('$page', 'POST',x_BalancerDeleteService);
		}  		
	}


	
	function EnableDisableHaService(servicename){
		var XHR = new XHRConnection();
		XHR.appendData('balancer-enable', servicename);
		if(document.getElementById('HaProxDisable_'+servicename).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableHaServiceSilent);  		
	}

	function HaProxyStatusRoll$t(){
		if(document.getElementById('haproxy-status-$t')){
			LoadAjaxSilent('haproxy-status-$t','$page?haproxy-status=yes');
			setTimeout('HaProxyStatusRoll$t()',15000);
		}
	
	}
setTimeout('HaProxyStatusRoll$t()',5000);
</script>
	
	";
	
	echo $html;
	
}//http-use-proxy-header

function balancers_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="haproxy";
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rules....");}
	
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("HaProxDisable_{$ligne['servicename']}", 1,$ligne["enabled"],"EnableDisableHaService('{$ligne['servicename']}')");
		$md5=md5($ligne['servicename']);
		$delete=imgsimple("delete-32.png",null,"BalancerDeleteService('{$ligne['servicename']}','$md5')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$listen_ip=$ligne["listen_ip"];
		$listen_port=$ligne["listen_port"];
		$interface="$listen_ip:$listen_port";
		
		$sql="SELECT COUNT(*) as Tcount from haproxy_backends WHERE servicename='{$ligne['servicename']}'";
		$q2=new mysql();
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){$Tcount=$q->mysql_error;}else{$Tcount=$ligne2["Tcount"];}
		
	$data['rows'][] = array(
		'id' => "BF$md5",
		'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?balancer-js=yes&servicename={$ligne['servicename']}&t={$_GET["t"]}');\" 
		style='font-size:24px;text-decoration:underline;color:$color'>{$ligne['servicename']}</span>",
		"<span style='font-size:24px;color:$color'>$interface</span>",
		"<span style='font-size:24px;color:$color'>$Tcount</span>",
		"<center>$disable</center>",
		"<center>$delete</center>")
		);
	}
	
	
	echo json_encode($data);	
}

function backends_settings(){
	$tt=$_GET["t"];
	$t=time();
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$servicename=$_GET["servicename"];
	$backendname=$_GET["backendname"];
	$smtp_disable=0;
	$hapServ=new haproxy_multi($servicename);
	if($hapServ->loadbalancetype==2){$smtp_disable=1;}
	$UseSMTPProto=$hapServ->MainConfig["UseSMTPProto"];
	if(!is_numeric($UseSMTPProto)){$UseSMTPProto=0;}
	
	
	$hap=new haproxy_backends($servicename,$backendname);
	$remove_this_backend=$tpl->javascript_parse_text("{remove_this_backend}");
	
	
	if($hap->enabled==1){
		$enableT="	<tr>
		<td width=1%><img src='img/arrow-right-24.png'>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:SaveHaProxyBackendDisable$t(0);\" 
		style=\"font-size:14px;text-decoration:underline\">{disable_this_backend}</td>
	</tr>";
	}else{
		$enableT="	<tr>
		<td width=1%><img src='img/arrow-right-24.png'>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:SaveHaProxyBackendDisable$t(1);\" 
		style=\"font-size:14px;text-decoration:underline\">{activate_this_backend}</td>
	</tr>";		
	}
	
	$toolbox="
	<table style='width:99%' class=form>
	$enableT
	<tr>
		<td width=1%><img src='img/arrow-right-24.png'>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:SaveHaProxyBackendDelete$t();\" 
		style=\"font-size:14px;text-decoration:underline\">$remove_this_backend</td>
	</tr>
	</table>";
	
	
	$buttonname="{apply}";
	if($backendname==null){$buttonname="{add}";$toolbox=null;}
	if(!is_numeric($hap->MainConfig["inter"])){$hap->MainConfig["inter"]=60000;}
	if(!is_numeric($hap->MainConfig["fall"])){$hap->MainConfig["fall"]=3;}
	if(!is_numeric($hap->MainConfig["rise"])){$hap->MainConfig["rise"]=2;}
	if(!is_numeric($hap->MainConfig["maxconn"])){$hap->MainConfig["maxconn"]=10000;}
	if(!is_numeric($hap->MainConfig["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=0;}
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	$Interfaces[null]="{default}";
	unset($Interfaces["lo"]);
	
	
	$html="
	<div id='$t-defaults'></div>
	<table style='width:99%;margin-bottom:15px' class=form>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{backendname}:</td>
				<td>". Field_text("backendname-$t",$backendname,"font-size:20px;padding:3px;width:540px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>". texttooltip("{outgoing_address}","{haproxy_local_interface_help}").":</td>
				<td>". Field_array_Hash($Interfaces,"localInterface-$t",$hap->localInterface,"style:font-size:20px;padding:3px;")."</td>
				
			</tr>
						
						
			<tr>
				<td class=legend style='font-size:20px' nowrap>{listen_ip}:</td>
				<td width=99%>". field_ipv4("listen_ip-$t",$hap->listen_ip,"font-size:20px;")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{listen_port}:</td>
				<td>". Field_text("listen_port-$t",$hap->listen_port,"font-size:20px;padding:3px;width:70px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>Artica Proxy:</td>
				<td width=99%>". Field_checkbox_design("asSquidArtica-$t",1,$hap->MainConfig["asSquidArtica"])."</td>
				
			</tr>										
			";
	
	if($smtp_disable==0){
			$html=$html."<tr>
				<td class=legend style='font-size:20px' nowrap>{postfix_send_proxy}:</td>
				<td width=99%>". Field_checkbox_design("postfix-send-proxy-$t",1,$hap->MainConfig["postfix-send-proxy"],"UseSMTPSendProxy$t()")."</td>
				
			</tr>
			";
	}			
		$html=$html."<tr>
				<td class=legend style='font-size:20px' nowrap>{weight}:</td>
				<td>". Field_text("bweight-$t",$hap->bweight,"font-size:20px;padding:3px;width:70px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{max_connections}:</td>
				<td>". Field_text("maxconn-$t",$hap->MainConfig["maxconn"],"font-size:20px;padding:3px;width:100px")."</td>
				
			</tr>			
			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{check_interval}:</td>
				<td style='font-size:20px;'>".
				 Field_text("inter-$t",$hap->MainConfig["inter"],"font-size:20px;padding:3px;width:100px",null,"intercalc$t()",null,false,"intercalc$t()",false).
				 
				 "&nbsp;{milliseconds}&nbsp;<span id='inter-span-$t'></span></td>
				
			</tr>			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{failed_number}:</td>
				<td style='font-size:20px;'>
				". Field_text("fall-$t",$hap->MainConfig["fall"],"font-size:20px;padding:3px;width:100px").
				"&nbsp;{attempts}&nbsp;<span id='fall-span-$t'></span></td>
				
			</tr>			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{success_number}:</td>
				<td style='font-size:20px;'>". Field_text("rise-$t",$hap->MainConfig["rise"],"font-size:20px;padding:3px;width:70px")."&nbsp;{attempts}</td>
				
			</tr>				
			<tr>			
				<td colspan=2 align='right'>". button("$buttonname","SaveHaProxyBackend()",30)."</td>
			</tr>						
	</table>
$toolbox
	
<script>
	var x_SaveHaProxyBackend$t=function (obj) {
		    var servicename='$backendname';
			var results=obj.responseText;
			document.getElementById('$t-defaults').innerHTML='';
			if(results.length>2){alert(results);return;}
			$('#HAPROXY_BACKENDS_TABLE').flexReload();
			$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
			if(servicename.length==0){YahooWin4Hide();return;}
			RefreshTab('main_config_backendservice');
		}

	var x_SaveHaProxyBackendDelete$t=function (obj) {
		    var servicename='$backendname';
			var results=obj.responseText;
			document.getElementById('$t-defaults').innerHTML='';
			if(results.length>2){alert(results);return;}
			$('#HAPROXY_BACKENDS_TABLE').flexReload();
			$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
			YahooWin4Hide();
			
		}		
		
		function SaveHaProxyBackend(){
			var XHR = new XHRConnection();
			XHR.appendData('backends-save','yes');
    		XHR.appendData('servicename','$servicename');
    		XHR.appendData('backendname',document.getElementById('backendname-$t').value);
    		XHR.appendData('listen_ip',document.getElementById('listen_ip-$t').value);
    		XHR.appendData('listen_port',document.getElementById('listen_port-$t').value);
    		XHR.appendData('bweight',document.getElementById('bweight-$t').value);
    		XHR.appendData('maxconn',document.getElementById('maxconn-$t').value);
    		XHR.appendData('localInterface',document.getElementById('localInterface-$t').value);
    		
    		
    		
    		
    		XHR.appendData('inter',document.getElementById('inter-$t').value);
    		XHR.appendData('fall',document.getElementById('fall-$t').value);
    		XHR.appendData('rise',document.getElementById('rise-$t').value);
    		if(document.getElementById('postfix-send-proxy-$t')){
    			if(document.getElementById('postfix-send-proxy-$t').checked){XHR.appendData('postfix-send-proxy',1);}else{XHR.appendData('postfix-send-proxy',0);}
    		}
    		if(document.getElementById('asSquidArtica-$t').checked){XHR.appendData('asSquidArtica',1);}else{XHR.appendData('asSquidArtica',0);}
    		
    		
			
    		XHR.sendAndLoad('$page', 'POST',x_SaveHaProxyBackend$t);
			
		}	
		
		function SaveHaProxyBackendDelete$t(){
			if(confirm('$remove_this_backend ?')){
				AnimateDiv('$t-defaults');
				XHR.appendData('servicename','$servicename');
    			XHR.appendData('backends-delete','$backendname');
				XHR.sendAndLoad('$page', 'POST',x_SaveHaProxyBackendDelete$t);
			}
		}
		function SaveHaProxyBackendDisable$t(enable){
			var XHR = new XHRConnection();
			XHR.appendData('backends-enable', '$backendname');
			XHR.appendData('servicename', '$servicename');
			XHR.appendData('enable', enable);
			XHR.sendAndLoad('$page', 'POST',x_SaveHaProxyBackend$t);
		}  	

		
		function intercalc$t(){
			LoadAjaxTiny('inter-span-$t','$page?millisec='+document.getElementById('inter-$t').value);
		
		}
		
		
		function CheckService$t(){
			 var backendname='$backendname';
			 var UseSMTPProto=$UseSMTPProto;
			 var smtp_disable=$smtp_disable;
			 if(backendname.length>2){document.getElementById('backendname-$t').disabled=true;}
			 if(document.getElementById('postfix-send-proxy-$t')){
			 	if(UseSMTPProto==1){document.getElementById('postfix-send-proxy-$t').disabled=false;}
			 }
			 
		}
		
CheckService$t();		
	intercalc$t();	
		
</script>";	
echo $tpl->_ENGINE_parse_body($html);
}

function backends_save(){
	

	
	
	$hap=new haproxy_backends($_POST["servicename"], $_POST["backendname"]);
	$hap->listen_ip=$_POST["listen_ip"];
	$hap->listen_port=$_POST["listen_port"];
	$hap->bweight=$_POST["bweight"];
	$hap->localInterface=$_POST["localInterface"];
	$hap->MainConfig["inter"]=$_POST["inter"];
	$hap->MainConfig["fall"]=$_POST["fall"];
	$hap->MainConfig["rise"]=$_POST["rise"];
	$hap->MainConfig["maxconn"]=$_POST["maxconn"];
	$hap->MainConfig["asSquidArtica"]=$_POST["asSquidArtica"];
	
	
	if(isset($_POST["postfix-send-proxy"])){$hap->MainConfig["postfix-send-proxy"]=$_POST["postfix-send-proxy"];}

	$hap->save();
}
function backends_delete(){
	$hap=new haproxy_backends($_POST["servicename"], $_POST["backends-delete"]);
	$hap->DeleteBackend();
}
function backends_enable(){
	$hap=new haproxy_backends($_POST["servicename"], $_POST["backends-enable"]);
	$hap->enabled=$_POST["enable"];
	$hap->save();
}

function balancer_settings(){
	$tt=$_GET["t"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$servicename=$_GET["servicename"];
	$havp_transparent_not_same_port=$tpl->javascript_parse_text("{havp_transparent_not_same_port}");
	$hap=new haproxy_multi($servicename);
	$tcp=new networking();
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	$ips["*"]="{all}";
	$buttonname="{apply}";
	if($servicename==null){$buttonname="{add}";}
	$mode=array(0=>"TCP",1=>"HTTP Web",2=>"HTTP Proxy");
	if(!isset($hap->MainConfig["smtpchk_EHLO"])){$hap->MainConfig["smtpchk_EHLO"]=$users->hostname;}
	if(!is_numeric($hap->MainConfig["contimeout"])){$hap->MainConfig["contimeout"]=4000;}
	if(!is_numeric($hap->MainConfig["srvtimeout"])){$hap->MainConfig["srvtimeout"]=50000;}
	if(!is_numeric($hap->MainConfig["clitimeout"])){$hap->MainConfig["clitimeout"]=15000;}
	if(!is_numeric($hap->MainConfig["retries"])){$hap->MainConfig["retries"]=3;}
	if(!is_numeric($hap->MainConfig["UseCookies"])){$hap->MainConfig["UseCookies"]=0;}
	if(!is_numeric($hap->MainConfig["NTLM_COMPATIBILITY"])){$hap->MainConfig["NTLM_COMPATIBILITY"]=0;}
	
	
	
	
	$t=time();
	$html="
	<div id='$t-defaults'></div>
	<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{servicename}:</td>
				<td>". Field_text("servicename-$t",$servicename,"font-size:20px;padding:3px;width:500px")."</td>
				
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{listen_ip}:</td>
				<td width=99%>". Field_array_Hash($ips,"listen_ip-$t",$hap->listen_ip,"style:font-size:20px;padding:3px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{listen_port}:</td>
				<td>". Field_text("listen_port-$t",$hap->listen_port,"font-size:20px;padding:3px;width:70px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{method}:</td>
				<td width=99%>". Field_array_Hash($mode,"mode-$t",$hap->loadbalancetype,"MethodChk$t()",null,0,"font-size:20px;padding:3px")."&nbsp;<span id='mode-options-$t'></span></td>
				
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{tunnel_mode}:</td>
				<td width=99%>". Field_checkbox_design("tunnel_mode-$t",1,$hap->tunnel_mode,"tunnel_modeChk$t()")."&nbsp;<span id='mode-tunnel_mode-$t'></span></td>
				
			</tr>				
			<tr>
				<td class=legend style='font-size:20px' nowrap>{dispatch_method}:</td>
				<td width=99%>". Field_array_Hash($hap->algo,"dispatch_mode-$t",$hap->dispatch_mode,"style:font-size:20px;padding:3px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{UseCookies}:</td>
				<td width=99%>". Field_checkbox_design("UseCookies-$t",1,$hap->MainConfig["UseCookies"])."</td>
				
			</tr>			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{transparent_mode}:</td>
				<td width=99%>". Field_checkbox_design("transparent-$t",1,$hap->transparent,"transparentCheck$t()")."</td>
				<td>&nbsp;</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{transparent_srcport}:</td>
				<td width=99%>". Field_text("transparentsrcport-$t",$hap->transparentsrcport,"font-size:20px;padding:3px;width:70px")."</td>
				
			</tr>			
			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{UseSMTPProto}:</td>
				<td width=99%>". Field_checkbox_design("UseSMTPProto-$t",1,$hap->MainConfig["UseSMTPProto"],"UseSMTPProtoChk$t()")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>". texttooltip("{NTLM_COMPATIBLE}","{HAP_NTLM_COMPATIBLE}").":</td>
				<td width=99%>". Field_checkbox_design("NTLM_COMPATIBILITY-$t",1,$hap->MainConfig["NTLM_COMPATIBILITY"],"NTLM_COMPATIBILITYCHK$t()")."</td>
			</tr>
						

			
			
			<tr>
				<td class=legend style='font-size:20px' nowrap>{smtpchk_EHLO}:</td>
				<td width=99%>". Field_text("smtpchk_EHLO-$t",$hap->MainConfig["smtpchk_EHLO"],"font-size:20px;padding:3px;width:270px")."</td>
				
			</tr>
			<tr>
				<td class=legend style='font-size:20px' nowrap>{contimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("contimeout-$t",$hap->MainConfig["contimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 
				 "&nbsp;{milliseconds}&nbsp;<span id='contimeout-span-$t'></span></td>
				 
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{srvtimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("srvtimeout-$t",$hap->MainConfig["srvtimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 "&nbsp;{milliseconds}&nbsp;<span id='srvtimeout-span-$t'></span></td>
				 
			</tr>	
			<tr>
				<td class=legend style='font-size:20px' nowrap>{clitimeout}:</td>
				<td style='font-size:20px;'>".
				 Field_text("clitimeout-$t",$hap->MainConfig["clitimeout"],"font-size:20px;padding:3px;width:100px",null,
				 "contimeoutcalc$t()",null,false,"contimeout$t()",false).
				 "&nbsp;{milliseconds}&nbsp;<span id='clitimeout-span-$t'></span></td>
				 
			</tr>				
			<tr>
				<td class=legend style='font-size:20px' nowrap>{maxretries}:</td>
				<td style='font-size:20px;'>".
				 Field_text("retries-$t",$hap->MainConfig["retries"],"font-size:20px;padding:3px;width:60px",null,
				 "blur()",null,false,"blur()",false).
				 "&nbsp;{times}</td>
				 
			</tr>			
			
			
			</tr>			

			
			
			<tr>			
				<td colspan=2 align='right'>". button("$buttonname","SaveHaProxyService()",30)."</td>
			</tr>						
	</table>
	
<script>
	var x_SaveHaProxyService$t=function (obj) {
		    var servicename='$servicename';
			var results=obj.responseText;
			document.getElementById('$t-defaults').innerHTML='';
			if(results.length>2){alert(results);return;}
			$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
			if(servicename.length==0){YahooWin3Hide();return;}
			RefreshTab('main_config_haservice');
		}	
		
		
		function tunnel_modeChk$t(){
			if( document.getElementById('tunnel_mode-$t').checked){
				document.getElementById('dispatch_mode-$t').disabled=true;
				document.getElementById('UseCookies-$t').disabled=true;
			}else{
				document.getElementById('dispatch_mode-$t').disabled=false;
				document.getElementById('UseCookies-$t').disabled=false;			
			}
		
		}		
		
		function SaveHaProxyService(){
			if( document.getElementById('transparent-$t').checked ){
				var port1=document.getElementById('listen_port-$t').value;
				var port2=document.getElementById('transparentsrcport-$t').value;
				if(port1==port2){alert('$havp_transparent_not_same_port');return;	}
			}
			var XHR = new XHRConnection();
			XHR.appendData('balancer-save','yes');
    		XHR.appendData('servicename',document.getElementById('servicename-$t').value);
    		XHR.appendData('listen_ip',document.getElementById('listen_ip-$t').value);
    		XHR.appendData('listen_port',document.getElementById('listen_port-$t').value);
    		XHR.appendData('mode',document.getElementById('mode-$t').value);
    		XHR.appendData('dispatch_mode',document.getElementById('dispatch_mode-$t').value);
    		XHR.appendData('smtpchk_EHLO',document.getElementById('smtpchk_EHLO-$t').value);
    		XHR.appendData('contimeout',document.getElementById('contimeout-$t').value);
    		XHR.appendData('clitimeout',document.getElementById('clitimeout-$t').value);
    		XHR.appendData('srvtimeout',document.getElementById('srvtimeout-$t').value);
    		XHR.appendData('retries',document.getElementById('retries-$t').value);
    		
    		
    		
    		XHR.appendData('transparentsrcport',document.getElementById('transparentsrcport-$t').value);
    		if( document.getElementById('UseSMTPProto-$t').checked){XHR.appendData('UseSMTPProto',1);}else{XHR.appendData('UseSMTPProto',0);}
    		if( document.getElementById('transparent-$t').checked){XHR.appendData('transparent',1);}else{XHR.appendData('transparent',0);}
    		if( document.getElementById('UseCookies-$t').checked){XHR.appendData('UseCookies',1);}else{XHR.appendData('UseCookies',0);}
    		if( document.getElementById('tunnel_mode-$t').checked){XHR.appendData('tunnel_mode',1);}else{XHR.appendData('tunnel_mode',0);}
    		if( document.getElementById('NTLM_COMPATIBILITY-$t').checked){XHR.appendData('NTLM_COMPATIBILITY',1);}else{XHR.appendData('NTLM_COMPATIBILITY',0);}
    		XHR.sendAndLoad('$page', 'POST',x_SaveHaProxyService$t);
			
		}
		
		function contimeout$t(){
			LoadAjaxTiny('contimeout-span-$t','$page?millisec='+document.getElementById('contimeout-$t').value);
			LoadAjaxTiny('srvtimeout-span-$t','$page?millisec='+document.getElementById('srvtimeout-$t').value);
			LoadAjaxTiny('clitimeout-span-$t','$page?millisec='+document.getElementById('clitimeout-$t').value);
			
			
		
		}		

		function MethodChk$t(){
		 var servicename='$servicename';
		 var method=document.getElementById('mode-$t').value;
		 document.getElementById('UseSMTPProto-$t').disabled=true;
		 document.getElementById('smtpchk_EHLO-$t').disabled=true;
		 document.getElementById('UseCookies-$t').disabled=false;
		 if(method==0){
		 	document.getElementById('UseSMTPProto-$t').disabled=false;
		 	document.getElementById('smtpchk_EHLO-$t').disabled=false;
		 	document.getElementById('UseCookies-$t').disabled=true;	
		 	document.getElementById('NTLM_COMPATIBILITY-$t').disabled=true;	 
		 
		 }
		 LoadAjaxTiny('mode-options-$t','$page?balancer-method-options='+method+'&servicename=$servicename');
		 
		 
		
		}
		
		function transparentCheck$t(){
			 if( document.getElementById('transparent-$t').disabled){return;}
			 document.getElementById('transparentsrcport-$t').disabled=true;
			 if( document.getElementById('transparent-$t').checked){
			 	document.getElementById('transparentsrcport-$t').disabled=false;
			 }
		}
		
		function UseSMTPProtoChk$t(){
			var method=document.getElementById('mode-$t').value;
			if(method==1){return;}
			document.getElementById('smtpchk_EHLO-$t').disabled=true;
			if( document.getElementById('UseSMTPProto-$t').checked){
				document.getElementById('smtpchk_EHLO-$t').disabled=false;	
			}
		}
		
		function CheckService$t(){
			 var servicename='$servicename';
			 if(servicename.length>2){document.getElementById('servicename-$t').disabled=true;}
		}
		
		function NTLM_COMPATIBILITYCHK$t(){
			if(document.getElementById('NTLM_COMPATIBILITY-$t').disabled){return;}
		
		
		 	document.getElementById('transparentsrcport-$t').disabled=true;
		 	document.getElementById('transparent-$t').disabled=true;
		 	document.getElementById('smtpchk_EHLO-$t').disabled=true;
		 	document.getElementById('tunnel_mode-$t').disabled=true;
		 	
		 	
		 	
			if(!document.getElementById('NTLM_COMPATIBILITY-$t').checked){
				document.getElementById('transparentsrcport-$t').disabled=false;
				document.getElementById('transparent-$t').disabled=false;
				document.getElementById('smtpchk_EHLO-$t').disabled=false;
				document.getElementById('tunnel_mode-$t').disabled=false;
			}
		}
		
NTLM_COMPATIBILITYCHK$t();		
CheckService$t();		
MethodChk$t();	
UseSMTPProtoChk$t();
transparentCheck$t();	
contimeout$t()
tunnel_modeChk$t();
		
</script>";	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function balancer_save(){
	
	$SQUIDEnable=@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable");
	if($SQUIDEnable==1){
		$q=new mysql_squid_builder();
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT PortName,ID FROM proxy_ports WHERE port='{$_POST["listen_port"]}'"));
		$ID=$ligne["ID"];
		$ID=intval($ID);
	
		if($ID>0){
			$PortName=$ligne["PortName"];
			echo "Unable to listen {$_POST["listen_port"]}, it used by the HTTP Proxy service $PortName ID $ID\n";
			return;
		}
		}
	
	
	$hap=new haproxy_multi($_POST["servicename"]);
	$hap->listen_ip=$_POST["listen_ip"];
	$hap->listen_port=$_POST["listen_port"];
	$hap->loadbalancetype=$_POST["mode"];
	$hap->dispatch_mode=$_POST["dispatch_mode"];
	$hap->MainConfig["smtpchk_EHLO"]=$_POST["smtpchk_EHLO"];
	$hap->MainConfig["UseSMTPProto"]=$_POST["UseSMTPProto"];
	$hap->MainConfig["contimeout"]=$_POST["contimeout"];
	$hap->MainConfig["srvtimeout"]=$_POST["srvtimeout"];
	$hap->MainConfig["clitimeout"]=$_POST["clitimeout"];
	$hap->MainConfig["retries"]=$_POST["retries"];
	$hap->MainConfig["UseCookies"]=$_POST["UseCookies"];
	$hap->MainConfig["NTLM_COMPATIBILITY"]=$_POST["NTLM_COMPATIBILITY"];
	
	
	$hap->tunnel_mode=$_POST["tunnel_mode"];
	$hap->transparent=$_POST["transparent"];
	$hap->transparentsrcport=$_POST["transparentsrcport"];
	
	$hap->save();
	
}

function balancer_method_options(){
	$servicename=$_GET["servicename"];
	$method=$_GET["balancer-method-options"];
	if($method==0){return;}
	$tpl=new templates();
	if($servicename==null){
		echo $tpl->_ENGINE_parse_body("<span style='color:#757575;text-decoration:underline;font-size:14px'>{options_will_be_available_after_creating_service}</span>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\" OnClick=\"Loadjs('haproxy.services.options.php?servicename=$servicename')\" 
	style='color:black;text-decoration:underline;font-weight:bold;font-size:14px'>{options}</span>");
	
	
}


function balancer_delete(){
	$hap=new haproxy_multi($_POST["balancer-delete"]);
	$hap->DeleteService();
}
function balancer_enable(){
	$hap=new haproxy_multi($_POST["balancer-enable"]);
	$hap->enabled=$_POST["enable"];	
	$hap->save();
	
}


function balancer_tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$servicename=$_GET["servicename"];
	$array["balancer-settings"]='{parameters}';
	$array["balancer-backends"]='{backends}';
	$array["balancer-groups"]='{groups2}';
	$array["balancer-acls"]='{acls}';
	$tpl=new templates();
	
	if($servicename==null){
		unset($array["balancer-backends"]);
	}
	
	$fontsize="style='font-size:22px'";$width="100%";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		if($num=="balancer-groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.groups.php?servicename=$servicename&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}
		
		
		
			if($num=="balancer-acls"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.acls.php?servicename=$servicename&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
				continue;
			}
		
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&servicename=$servicename&t={$_GET["t"]}\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_haservice");
	
	
}
function backends_tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$servicename=$_GET["servicename"];
	$backendname=$_GET["backendname"];
	$array["backend-settings"]='{parameters}';
	
	
	
	
	$tpl=new templates();
	
	if($servicename==null){
		unset($array["balancer-backends"]);
	}
	
	$fontsize="style='font-size:20px'";$width="100%";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&servicename={$_GET["servicename"]}&backendname={$_GET["backendname"]}&t={$_GET["t"]}&tt={$_GET["tt"]}\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_backendservice");
		
	
	
}




function tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["balancers"]='{balancers}';
	$array["backend-status"]='{backends_status}';
	$array["proxy-groups"]='{proxy_objects}';
	
	
	$array["events"]='{events}';
	$tpl=new templates();
	
	$fontsize="style='font-size:26px'";$width="100%";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
			if($num=="status"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.status.php?$num=yes\"><span $fontsize>$ligne</span></a></li>\n");
				continue;
			}
			if($num=="proxy-groups"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.acls.grouplist.php\"><span $fontsize>$ligne</span></a></li>\n");
				continue;
			}		
		
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_haproxy",1494)."<script>LeftDesign('load-balance-white-256-opac20.png');</script>";
	
	
	
}
function backends(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$q->BuildTables();
	$servicename=$tpl->_ENGINE_parse_body("{servicename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$weight=$tpl->_ENGINE_parse_body("{weight}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$delete_backend=$tpl->javascript_parse_text("{delete_backend}");

	$servicename=$_GET["servicename"];
	$tt=$_GET["tt"];
	$t=time();		

	$html="
	<table class='HAPROXY_BACKENDS_TABLE' style='display: none' id='HAPROXY_BACKENDS_TABLE' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#HAPROXY_BACKENDS_TABLE').flexigrid({
	url: '$page?balancer-backends-list=yes&t=$t&servicename=$servicename',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$backends</span>', name : 'backendname', width : 339, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$interface</span>', name : 'listen_ip', width : 233, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$weight</span>', name : 'bweight', width : 52, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width : 97, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 119, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:18px>$new_backend</strong>', bclass: 'add', onpress : HaBackendAdd},

		],	
	searchitems : [
		{display: '$backends', name : 'backendname'},
		{display: '$interface', name : 'listen_ip'},
		],
	sortname: 'bweight',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
});
function HaBackendAdd() {
	Loadjs('$page?backend-js=yes&backendname=&t=$t&servicename={$_GET['servicename']}&tt=$tt');
	
}	




	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	

	
	var x_EnableDisableBackendSilent= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#HAPROXY_BACKENDS_TABLE').flexReload();
		$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
	}	

	var x_BackendDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowTF'+tmp$t).remove();
		$('#HAPROXY_BACKENDS_TABLE').flexReload();
		$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
		
	}	
	
	
	function BackendDelete(backendname,servicename,md){
		tmp$t=md;
		if(confirm('$delete_backend :$servicename/'+backendname+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('backends-delete', backendname);
			XHR.appendData('servicename', '$servicename');
			XHR.sendAndLoad('$page', 'POST',x_BackendDelete);
		}  		
	}


	
	function EnableDisableBackend(backendname){
		var XHR = new XHRConnection();
		XHR.appendData('backends-enable', backendname);
		XHR.appendData('servicename', '$servicename');
		if(document.getElementById('HaProxBckDisable_'+backendname).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableBackendSilent);  		
	}		
	
	

	
</script>";
echo $html;
}
function backends_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="haproxy_backends";
	$FORCE_FILTER=" AND servicename='{$_GET["servicename"]}'";
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rules....");}
	
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("HaProxBckDisable_{$ligne['backendname']}", 1,$ligne["enabled"],"EnableDisableBackend('{$ligne['backendname']}')");
		$md5=md5($ligne['servicename'].$ligne['backendname']);
		$delete=imgsimple("delete-32.png",null,"BackendDelete('{$ligne['backendname']}','{$_GET['servicename']}','$md5')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$listen_ip=$ligne["listen_ip"];
		$listen_port=$ligne["listen_port"];
		$interface="$listen_ip:$listen_port";
		

		
		
	$data['rows'][] = array(
		'id' => "TF$md5",
		'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?backend-js=yes&backendname={$ligne['backendname']}&servicename={$_GET["servicename"]}&t={$_GET["t"]}');\" 
		style='font-size:22px;text-decoration:underline;color:$color'>{$ligne['backendname']}</span>",
		"<span style='font-size:22px;color:$color'>$interface</span>",
		"<span style='font-size:22px;color:$color'>{$ligne['bweight']}</span>",
	
	
		$disable,$delete)
		);
	}
	
	
	echo json_encode($data);	
}
function popup_script_save(){
	$data=url_decode_special_tool($_POST["HAPROXY_CONTENT"]);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy2.cfg", $data);
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/haproxy2.cfg")){echo "Permission denied\n";return;}
	$sock=new sockets();
	$page=CurrentPageName();
	echo base64_decode($sock->getFrameWork("haproxy.php?apply-conf=yes"));
}

function popup_script(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock->getFrameWork("haproxy.php?copy-conf=yes");
	$t=time();
	$conf=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy.cfg");
	
	$html="<textarea 
		style='width:99%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
		font-weight:bold;padding:3px;font-family:Courier New;'
		id='HAPROXY_CONTENT-$t'>$conf</textarea>
		<center style='margin-top:15px'>". $tpl->_ENGINE_parse_body(button("{apply}","Save$t()",26))."</center>
	
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		UnlockPage();
	
	}
	function Save$t(){
		LockPage();
		var XHR = new XHRConnection();
		XHR.appendData('HAPROXY_CONTENT',encodeURIComponent(document.getElementById('HAPROXY_CONTENT-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		}		
		
	</script>	
	
	
	";
	echo $html;
	
	
}

function events(){
	$t=time();
	$html="<div id='$t' style='width:100%'></div>
	
	<script>
		LoadAjax('$t','syslog.php?popup=yes&force-prefix=haproxy&TB_WIDTH=997&TB_HEIGHT=455&TB_EV=803');
	</script>
	";
	echo $html;
	
	
}

function haproxy_status_bar(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork("haproxy.php?main-status=yes"));
	$ini->loadString($datas);
	if($ini->_params["APP_HAPROXY"]["running"]==1){
		echo $tpl->_ENGINE_parse_body("<strong style='color:#098C27'>{running}</strong>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<strong style='color:#A5350A'>{stopped}</strong>");
	
}

function service_cmds_js(){
	$page=CurrentPageName();
	$cmd=$_GET["service-cmds"];
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','Service::$cmd');";
	echo $html;	
}

function service_cmds_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock->getFrameWork("haproxy.php?service-cmds={$_GET["service-cmds-peform"]}");
	$t=time();
	

	$html="<div style='width:100%;height:350px;min-height:350px;overflow:auto' id='service-$t'>
	
		<center style='margin:50px'><img src='img/loadingAnimation.gif'>
		<div style='font-size:28px'>{$_GET["service-cmds-peform"]}</div>
		</center></div>
	<script>
		function service_cmds_popup_refresh$t(){
			LoadAjax('service-$t','$page?service-cmds-logs=yes&t=$t');
			RefreshHaProxyStatus();
		
		}
	
		setTimeout('service_cmds_popup_refresh$t()',10000);
	</script>
	";
	
	echo $html;
}

function service_cmds_logs(){
	$tpl=new templates();
	$t=$_GET["t"];
	$datas=service_logs_to_table("ressources/logs/web/haproxy.cmds");
$html="<div>
$datas
</div>
<center style='margin:5px'><img src='img/loadingAnimation.gif'></center>
<script>
	if(YahooWin4Open()){
		setTimeout('service_cmds_popup_refresh$t()',10000);
		
	}
</script>

";		
	
echo $tpl->_ENGINE_parse_body($html);
}



function milliseconds_text(){
	$ms=intval($_GET["millisec"]);
	if($ms<1000){return;}
	$s=$ms/1000;
	$ex[]="$s {seconds}";
	if($s>59){
		$m=secondMinute($s);
		$ex[]="$m";
	}
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<span style='font-size:12px'>({$_GET["millisec"]}: ".@implode(", ", $ex)).")</span>";
	
	
}


function secondMinute($seconds){

    /// get minutes
    $minResult = floor($seconds/60);
    
    /// if minutes is between 0-9, add a "0" --> 00-09
    if($minResult < 10){$minResult = 0 . $minResult;}
    
    /// get sec
    $secResult = ($seconds/60 - $minResult)*60;
    
    /// if secondes is between 0-9, add a "0" --> 00-09
    if($secResult < 10){$secResult = 0 . $secResult;}
    
    /// return result
    return $minResult." {minutes} ".$secResult." {seconds}";

}
