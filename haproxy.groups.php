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
if($user->AsDansGuardianAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["acl-list"])){rules_search();exit;}
if(isset($_POST["group-new"])){group_new();exit;}
if(isset($_POST["group-delete"])){group_delete();exit;}
if(isset($_GET["delete-group-js"])){group_delete_js();exit;}



function group_delete_js(){
	$ID=$_GET["ID"];
	$xt=time();
	$q=new mysql();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
	$groupname=$ligne["groupname"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$page=CurrentPageName();
	$html="
	var xSave$xt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HAPROXY_GROUPS_TABLE').flexReload();
	}
	
	function Save$xt(){
	var XHR = new XHRConnection();
	if(!confirm('$delete $groupname ?')){return;}
	XHR.appendData('group-delete',  '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$xt);
	}
	
	Save$xt()";
	echo $html;
	
}

function group_delete(){
	$q=new mysql();
	$ID=$_POST["group-delete"];
	$q->QUERY_SQL("DELETE FROM haproxy_backends_link WHERE gpid=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM haproxy_backends_groups WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
}

function group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$servicename=$_GET["servicename"];
	
	if(!is_numeric($ID)){$ID=0;}

if($ID==0){
	$xt=time();
	$rulename=$tpl->javascript_parse_text("{group2}");
	$page=CurrentPageName();
$html="
var xSave$xt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HAPROXY_GROUPS_TABLE').flexReload();
}

function Save$xt(){
	var XHR = new XHRConnection();
	var pp=prompt('$rulename ?');
	if(!pp){return;}
	XHR.appendData('group-new',  encodeURIComponent(pp));
	XHR.appendData('servicename',  '$servicename');
	XHR.sendAndLoad('$page', 'POST',xSave$xt);
}

Save$xt()";
echo $html;
return;
}
$q=new mysql();
$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
$servicename=$ligne["servicename"];
$title=$tpl->javascript_parse_text("/$servicename/{$ligne["groupname"]}/{backends}");
echo "YahooWin4('890','$page?group-tabs=yes&ID=$ID&servicename=$servicename','$title')";
}

function group_new(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("haproxy_backends_groups", "artica_backup")){$q->BuildTables();}
	$rulename=url_decode_special_tool($_POST["group-new"]);
	
	$servicename=trim($_POST["servicename"]);
	$rulename=str_replace(" ", "", $rulename);
	$rulename=str_replace("-", "_", $rulename);
	$rulename=str_replace("'", "", $rulename);
	$rulename=replace_accents($rulename);
	$rulename=mysql_escape_string2($rulename);
	$default=0;
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM haproxy_backends_groups WHERE servicename='$servicename' AND `default`=1","artica_backup"));
	if(intval($ligne["tcount"])==0){$default=1;}
	
	
	$q->QUERY_SQL("INSERT INTO haproxy_backends_groups (groupname,servicename,`default`) VALUES ('$rulename','$servicename','$default')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
function group_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
	$title=$tpl->javascript_parse_text($ligne["groupname"]);
	$array["group"]=$title;
	$array["group-backends"]="{backends}";

	while (list ($num, $ligne) = each ($array) ){

		if($num=="group"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.groups.settings.php?ID=$ID=yes&ID=$ID\" style='font-size:26px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="group-backends"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.groups.list.php?ID=$ID=yes&t=$t&ID=$ID\" style='font-size:26px'><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:26px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "haproxy_group_$ID");
}

table();


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$groupname=$tpl->_ENGINE_parse_body("{groupname}");
	$action=$tpl->_ENGINE_parse_body("{action}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$delete_backend=$tpl->javascript_parse_text("{delete_backend}");
	$title=$tpl->javascript_parse_text("{resources_groups}");
	$servicename=$_GET["servicename"];
	$tt=$_GET["tt"];
	$t=time();
	
	$html="
	<table class='table-$t' style='display: none' id='HAPROXY_GROUPS_TABLE' style='width:99%'></table>
	<script>
	var tmp$t='';
	$(document).ready(function(){
	$('#HAPROXY_GROUPS_TABLE').flexigrid({
	url: '$page?acl-list=yes&t=$t&servicename=$servicename',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$groupname</span>', name : 'groupname', width : 339, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$backends</span>', name : 'up', width : 346, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 119, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '<strong style=font-size:18px>$new_group</strong>', bclass: 'add', onpress : HaAclsRuleAdd},
	
	],
	searchitems : [
	{display: '$groupname', name : 'groupname'},
	
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
});
function HaAclsRuleAdd() {
	Loadjs('$page?group-js=yes&ID=0&servicename={$_GET['servicename']}');
}
</script>";
	echo $html;
	}
	
	
	function rules_search(){
		//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
		$tpl=new templates();
		$MyPage=CurrentPageName();
		
		$q=new mysql();
		$servicename=trim($_GET["servicename"]);
		$table_type=$_GET["table"];
		$sock=new sockets();
		
		$t=$_GET["t"];
		$FORCE_FILTER=null;
		$search='%';
		$table="haproxy_backends_groups";
	
		
		$table="(SELECT haproxy_backends_groups.* FROM haproxy_backends_groups WHERE haproxy_backends_groups.servicename='$servicename') as t";
		$page=1;
	
		if($q->COUNT_ROWS("haproxy_backends_groups","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}
		$rules=$tpl->_ENGINE_parse_body("{rules}");
		$log_all_events=$tpl->_ENGINE_parse_body("{log_all_events}");
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$val=0;
			$color="black";
			$time=null;
			$mkey=md5(serialize($ligne));
			$default_text=null;
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-group-js=yes&ID={$ligne["ID"]}')");
			$up=imgsimple("arrow-up-32.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',0)");
			$down=imgsimple("arrow-down-32.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',1)");
			$groupname=trim(utf8_encode($ligne["groupname"]));
	
			$BACKENDS_LIST=BACKENDS_LIST($ligne["ID"]);
			if($groupname==null){$groupname=$tpl->_ENGINE_parse_body("{group2} {$ligne["ID"]}");}
	
			if($ligne["enabled"]==0){$color="#8a8a8a";}
			if($ligne["default"]==1){$default_text=" (".$tpl->_ENGINE_parse_body("{default}").")";}
	
			$js="Loadjs('$MyPage?group-js=yes&ID={$ligne["ID"]}',true);";
	
			
	
			$JSRULE="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
			style='font-size:22px;font-weight:bold;text-decoration:underline;color:$color'>";
	
			if($ligne["zOrder"]==1){$up=null;}
			if($ligne["zOrder"]==0){$up=null;}
			$data['rows'][] = array(
					'id' => "$mkey",
					'cell' => array(
							"$JSRULE$groupname</a>&nbsp;$default_text",
							"<span style=\"font-size:18px\">$BACKENDS_LIST</center>",
							"<center style=\"margin-top:4px\">$delete</center>")
			);
		}
	
		echo json_encode($data);
	
	}	
	
function BACKENDS_LIST($ID){
	$q=new mysql();
	$table="(SELECT haproxy_backends_link.ID as tid,
	haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
	haproxy_backends.ID=haproxy_backends_link.backendid AND
	haproxy_backends.enabled=1 AND
	haproxy_backends_link.gpid='$ID' ORDER BY bweight) as t";
	
	$results = $q->QUERY_SQL("SELECT * FROM $table ","artica_backup");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$listen_ip=$ligne["listen_ip"];
		$listen_port=$ligne["listen_port"];
		$interface="$listen_ip:$listen_port";
		$TR[]="{$ligne['backendname']} ( $listen_ip:$listen_port )";
	}
	
	return @implode("<br>", $TR);
	
}