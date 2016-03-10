<?php
if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.squid.acls.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.squid.acls.inc');

if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["whitelist-single"])){add_white_single();exit;}
if(isset($_GET["lists"])){list_items();exit;}
page();


function page(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$t=time();
	$order=$tpl->javascript_parse_text("{order}");
	$squid_templates_error=$tpl->javascript_parse_text("{squid_templates_error}");
	$websites=$tpl->javascript_parse_text("{websites}");
	$new_website=$tpl->javascript_parse_text("{new_website}");
	$new_group=$tpl->javascript_parse_text("{new_group_of_rules}");
	
	$session_manager="{name: '$session_manager', bclass: 'clock', onpress : SessionManager$t},";
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$create_a_snapshot=$tpl->javascript_parse_text("{create_a_snapshot}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$Table_title=$tpl->javascript_parse_text("{whitelist}");
	$t=time();
	
	$table_width=905;
	$apply_paramsbt="{separator: true},{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : SquidBuildNow$t},";
	$add="{name: '<strong style=font-size:18px>$new_website</strong>', bclass: 'add', onpress : SquidGlobalWhiteListAdd$t},";
	
	// removed {name: '$squid_templates_error', bclass: 'Script', onpress : SquidTemplatesErrors$t},
	
	$fields_size=22;
	$aclname_size=363;
	$items_size=682;
	$icon_size=70;
	
	
	
	$html="
<table class='PRIVOXY_GLOBAL_WHITELIST' style='display: none' id='PRIVOXY_GLOBAL_WHITELIST' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
function flexigridStart$t(){
	$('#PRIVOXY_GLOBAL_WHITELIST').flexigrid({
	url: '$page?lists=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$websites</span>', name : 'items', width : 600, sortable : true, align: 'left'},
	{display: '', name : 'none4', width : 90, sortable : false, align: 'center'},
	],
	buttons : [
	$add$apply_paramsbt
	],
	searchitems : [
	{display: '$websites', name : 'items'},
	],
	sortname: 'items',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>$Table_title</strong>',
	useRp: true,
	rp: 45,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true
	
	});
}
function SquidBuildNow$t(){
	Loadjs('privoxy.progress.squid.php');
}

var xSquidGlobalWhiteListAdd$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#PRIVOXY_GLOBAL_WHITELIST').flexReload();
}
	
function SquidGlobalWhiteListAdd$t(){
	var neworder=prompt('$squid_ask_domain');
	if(!neworder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('whitelist-single', neworder);
	XHR.sendAndLoad('$page', 'POST',xSquidGlobalWhiteListAdd$t);
}

function DeleteGlobalWihitePrivoxylistItem(www){
	var XHR = new XHRConnection();
	XHR.appendData('delete', www);
	XHR.sendAndLoad('$page', 'POST',xSquidGlobalWhiteListAdd$t);
}


setTimeout('flexigridStart$t()',800);
	
</script>
";
	
		echo $html;
}

function add_white_single(){
	$q=new mysql_squid_builder();
	$acl=new squid_acls();
	$IP=new IP();

	$sql="CREATE TABLE IF NOT EXISTS `privoxy_whitelist` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";


	$q->QUERY_SQL($sql,"artica_backup");

	$www=$_POST["whitelist-single"];
	$www=$acl->dstdomain_parse($www);
	if($www==null){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO privoxy_whitelist (items) VALUES ('{$www}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}


}
function delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM privoxy_whitelist WHERE items='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}

}


function list_items(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `privoxy_whitelist` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
	
	$sock=new sockets();
	$t=$_GET["t"];
	$search='%';
	$table="privoxy_whitelist";
	$GROUPE_RULE_ID_NEW_RULE=null;
	$FORCE_FILTER=null;
	$page=1;
	$data = array();
	$data['rows'] = array();
	

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}


	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";



	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show("$q->mysql_error");}

	if(mysql_num_rows($results)==0){json_error_show("no rules");}

	$font_size=18;
	$data['page'] = $page;
	$data['total'] = $total;


	$c=0;

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$val=0;
		$color="black";
		$delete=imgsimple("delete-32.png",null,"DeleteGlobalWihitePrivoxylistItem('{$ligne['items']}')");
		$md=md5($ligne["items"]);

		
		$data['rows'][] = array(
				'id' => "$md",
				'cell' => array("
				<span style='font-size:26px'>{$ligne['items']}</span>",
				"<center>$delete</center>")
				);
	}


	echo json_encode($data);
}