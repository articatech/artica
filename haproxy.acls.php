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
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["acl-list"])){rules_search();exit;}
if(isset($_POST["rule-new"])){rule_new();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_POST["rule-move"])){rule_move();exit;}

function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ruleid"];
	$servicename=$_GET["servicename"];
	
	if(!is_numeric($ID)){$ID=0;}

if($ID==0){
	$xt=time();
	$rulename=$tpl->javascript_parse_text("{rulename}");
$html="
var xSave$xt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HAPROXY_ACLS_TABLE').flexReload();
}

function Save$xt(){
	var XHR = new XHRConnection();
	var pp=prompt('$rulename ?');
	if(!pp){return;}
	XHR.appendData('rule-new',  encodeURIComponent(pp));
	XHR.appendData('servicename',  '$servicename');
	XHR.sendAndLoad('$page', 'POST',xSave$xt);
}

Save$xt()";
echo $html;
return;
}
$q=new mysql();
$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE ID='$ID'","artica_backup"));
$title=$tpl->javascript_parse_text($ligne["rulename"]);
echo "YahooWin2('800','$page?rule-tabs=yes&ID=$ID&servicename=$servicename','$title')";
}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$xt=time();
$rulename=$tpl->javascript_parse_text("{delete} {rule} $ID ?");
$html="
var xSave$xt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HAPROXY_ACLS_TABLE').flexReload();
}
	
function Save$xt(){
	var XHR = new XHRConnection();
	var pp=confirm('$rulename');
	if(!pp){return;}
	XHR.appendData('rule-delete',  $ID);
	XHR.sendAndLoad('$page', 'POST',xSave$xt);
}
	
Save$xt()";	
	echo $html;
}

function rule_new(){
	$q=new mysql();
	$rulename=url_decode_special_tool($_POST["rule-new"]);
	$rulename=mysql_escape_string2($rulename);
	$servicename=$_POST["servicename"];
	$q->QUERY_SQL("INSERT INTO haproxy_acls_rules (rulename,servicename) VALUES ('$rulename','$servicename')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function rule_delete(){
	$ID=$_POST["rule-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE ruleid='$ID'","artica_backup");
	$q->QUERY_SQL("DELETE FROM haproxy_acls_rules WHERE ID='$ID'","artica_backup");
	
}

function rule_move(){
	$ID=$_POST["rule-move"];
	$direction=$_POST["direction"];
	$servicename=$_POST["servicename"];
	$table="webfilters_sqacllinks";
	//up =1, Down=0
	$q=new mysql();
	$sql="SELECT zorder FROM haproxy_acls_rules WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));

	$OlOrder=$ligne["zorder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	
	$sql="UPDATE haproxy_acls_rules SET zorder='$OlOrder' WHERE zorder='$NewOrder' AND ID='$ID'";
	//	echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE haproxy_acls_rules SET zorder='$NewOrder' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM haproxy_acls_rules WHERE servicename='$servicename' ORDER BY zorder","artica_backup");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE haproxy_acls_rules SET zorder='$c' WHERE ID='$zmd5'","artica_backup");
		$c++;

	}


}

function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM haproxy_acls_rules WHERE ID='$ID'","artica_backup"));
	$title=$tpl->javascript_parse_text($ligne["rulename"]);
	if($title==null){$title=$tpl->javascript_parse_text("{rule} $ID");}
	$array["rule-popup"]=$title;
	$array["rule-groups"]="{objects}";

	while (list ($num, $ligne) = each ($array) ){

		if($num=="rule-popup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.acls.settings.php?ID=$ID=yes&ID=$ID\" style='font-size:22px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="rule-groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.acls.groups.php?ID=$ID=yes&t=$t&ID=$ID\" style='font-size:22px'><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "haproxy_acls_rules_$ID");
}

table();


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$q->BuildTables();
	
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$RuleName=$tpl->_ENGINE_parse_body("{rulename}");
	$action=$tpl->_ENGINE_parse_body("{action}");
	$down=$tpl->_ENGINE_parse_body("{down}");
	$up=$tpl->_ENGINE_parse_body("{up}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$delete_backend=$tpl->javascript_parse_text("{delete_backend}");
	$title=$tpl->javascript_parse_text("{acls}");
	$servicename=$_GET["servicename"];
	$Apply=$tpl->javascript_parse_text("{apply}");
	$tt=$_GET["tt"];
	$t=time();
	
	$html="
	<table class='table-$t' style='display: none' id='HAPROXY_ACLS_TABLE' style='width:99%'></table>
	<script>
	var tmp$t='';
	$(document).ready(function(){
	$('#HAPROXY_ACLS_TABLE').flexigrid({
	url: '$page?acl-list=yes&t=$t&servicename=$servicename',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$RuleName</span>', name : 'rulename', width : 588, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$up</span>', name : 'up', width : 52, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$down</span>', name : 'down', width : 97, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 119, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : HaAclsRuleAdd},
	{name: '<strong style=font-size:18px>$Apply</strong>', bclass: 'apply', onpress : HaApply},
	
	],
	searchitems : [
	{display: '$RuleName', name : 'rulename'},
	
	],
	sortname: 'zorder',
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
	Loadjs('$page?rule-js=yes&ID=0&servicename={$_GET['servicename']}');
	
	}
	
function HaApply(){
	Loadjs('haproxy.progress.php');
}	
	
var xRuleHaproxyAclsGroupUpDown= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#HAPROXY_ACLS_TABLE').flexReload();	
	$('#HAPROXY_BACKENDS_TABLE').flexReload();
	$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();	
	
}
function RuleHaproxyAclsGroupUpDown(id,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rule-move', id);
	XHR.appendData('direction', direction);
	XHR.appendData('servicename', '$servicename');
	XHR.sendAndLoad('$page', 'POST',xRuleHaproxyAclsGroupUpDown);
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
		$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
		$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
		$t=$_GET["t"];
		$FORCE_FILTER=null;
		$search='%';
		$table="haproxy_acls_rules";
	
		
		$table="(SELECT * FROM haproxy_acls_rules WHERE servicename='$servicename' ORDER BY zorder ) as t";
		$page=1;
	
		if($q->COUNT_ROWS("haproxy_acls_rules","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
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
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$val=0;
			$color="black";
			$time=null;
			$mkey=md5(serialize($ligne));
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?rule-delete-js=yes&ID={$ligne["ID"]}')");
			$up=imgsimple("arrow-up-32.png","","RuleHaproxyAclsGroupUpDown('{$ligne["ID"]}',0)");
			$down=imgsimple("arrow-down-32.png","","RuleHaproxyAclsGroupUpDown('{$ligne["ID"]}',1)");
			$rulename=trim($ligne["rulename"]);
	
			$EXPLAIN=EXPLAIN_THIS_RULE($ligne["ID"]);
			if($rulename==null){$rulename=$tpl->_ENGINE_parse_body("{rule} {$ligne["ID"]}");}
	
			if($ligne["rule_action"]==0){$color="#8a8a8a";}
	
			$js="Loadjs('$MyPage?rule-js=yes&ruleid={$ligne["ID"]}',true);";
	
			
	
			$JSRULE="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
			style='font-size:22px;font-weight:bold;text-decoration:underline;color:$color'>";
	
			
			if($c==0){$up=null;}
			$data['rows'][] = array(
					'id' => "$mkey",
					'cell' => array(
							"$JSRULE$rulename</a><br><span style='font-size:16px;color:$color'>$EXPLAIN</span>",
							"<center style=\"margin-top:5px\">$up</center>",
							"<center style=\"margin-top:5px\">$down</center>",
							"<center style=\"margin-top:4px\">$delete</center>")
			);
			$c++;
		}
	
		echo json_encode($data);
	
	}	
	
function EXPLAIN_THIS_RULE($ruleid){
	$q=new mysql();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'","artica_backup"));
	if(!$q->ok){return $q->mysql_error;}
	$haproxy=new haproxy();
	$rule_action=$ligne["rule_action"];
	$rule_action_data=$ligne["rule_action_data"];
	if($rule_action==0){return $tpl->_ENGINE_parse_body("{do_nothing}");}
	
	if($rule_action==1){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_backends_groups WHERE ID='$rule_action_data'","artica_backup"));
		$to=$ligne["groupname"];
		$rule_action_text=$tpl->_ENGINE_parse_body($haproxy->acls_actions[$rule_action] ."{to} $to");
	}
	
	if($rule_action==2){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT backendname FROM haproxy_backends WHERE ID='$rule_action_data'","artica_backup"));
		$to=$ligne["backendname"];
		$rule_action_text=$tpl->_ENGINE_parse_body($haproxy->acls_actions[$rule_action])." {to} $to";
	}
	
	if($rule_action==3){
		$rule_action_text=$tpl->_ENGINE_parse_body("{deny_access}");
	}
	
	$table="SELECT haproxy_acls_link.groupid,
	haproxy_acls_link.ID as tid,
	haproxy_acls_link.revert,
	haproxy_acls_link.operator,
	haproxy_acls_link.zorder as torder,
	haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups
	WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND 
	haproxy_acls_link.ruleid=$ruleid AND
	haproxy_acls_groups.enabled=1
	ORDER BY haproxy_acls_link.zorder";
	
	$results = $q->QUERY_SQL($table,"artica_backup");
	
	$acl=new haproxy();
	
	
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$revert=$ligne["revert"];
		$revert_text=null;
		if($revert==1){$revert_text="{not} ";}
		$operator=$ligne["operator"];
		$operator=$acl->acl_operator[$operator];
		$operator=$tpl->_ENGINE_parse_body($operator)." ";
		if($c==0){$operator=null;}
		$arrayF=$acl->FlexArray($ligne['ID']);
		$items=$arrayF["ITEMS"];
		if($items==0){continue;}
		$f[]="$operator$revert_text{$arrayF["ROW"]} <span style='font-size:14px'>($items {items})</span>";
		$c++;
	}
	
	if(count($f)==0){return $tpl->_ENGINE_parse_body("{do_nothing}");}
	return $tpl->_ENGINE_parse_body("<span style='font-size:14px'> {for_objects} ".@implode("<br>", $f)."<br>{then} $rule_action_text</span>");
}