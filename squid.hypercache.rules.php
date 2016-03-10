<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["ruleid-popup"])){ruleid_popup();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_POST["ID"])){items_save();exit;}
if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_POST["acl-rule-link-negation"])){items_negation();exit;}
if(isset($_POST["acl-rule-link-order"])){items_link_order();exit;}

items_js();


function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{delete} {rule} {$_GET["delete-js"]} ?");
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#STOREID_RULES_TABLE').flexReload();

}

function xFunct$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["delete-js"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
echo $html;
}

function rule_js(){
	$page=CurrentPageName();
	$ruleid=intval($_GET["ruleid-js"]);
	$tpl=new templates();
	if($ruleid==0){$title=$tpl->javascript_parse_text("{new_rule}");}else{
		$title=$tpl->javascript_parse_text("{rule}:$ruleid");
	}
	//$q=new mysql();
	//$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM amavisd_ext_rules WHERE ID='$ruleid'","artica_backup"));
	$html="YahooWin('1000','$page?ruleid-popup=$ruleid','$title')";
	echo $html;

}	

function ruleid_popup(){
	$ID=intval($_GET["ruleid-popup"]);
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$title="{new_rule}";
	$buttonname="{add}";
	$ligne["zOrder"]=1;
	$ligne["enabled"]=1;
	if($ID>0){
		$title="{rule}:$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM StoreID WHERE ID='$ID'"));
		$buttonname="{apply}";
	}
	
	$button="<hr>".button($buttonname, "Save$t()",32);
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		//$button=null;
	}
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:40px;margin-bottom:30px'>$title</div>		
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{enabled}:</td>
		<td>".Field_checkbox_design("enabled-$t", 1,$ligne["enabled"])."</td>
	<tr>	
	<tr>
		<td class=legend style='font-size:18px'>{order}:</td>
		<td>".Field_text("zOrder-$t", $ligne["zOrder"],"font-size:18px;width:110px")."</td>
	<tr>				
	<tr>
		<td class=legend style='font-size:18px'>{url_returned_proxy}:</td>
		<td>".Field_text("dedup-$t",$ligne["dedup"],"font-size:18px;font-family:Courier New;width:550px;font-weight:bold")."</td>
	<tr>
		<td colspan=2 style='font-size:18px'>{pattern}:</td>
	</tr>	
	<tr>
		<td colspan=2 >
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:220px;border:5px solid #8E8E8E;overflow:auto;font-size:18px  !important'
		id='pattern-$t'>{$ligne["pattern"]}</textarea>
	</td>
</tr>
	<tr>
		<td colspan=2 align='right'>$button</td>
	</tr>
	<script>
		var xSave$t=function(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			$('#STOREID_RULES_TABLE').flexReload();
			var ID=$ID;
			if(ID==0){YahooWinHide();}
		}

		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ID','$ID');
			XHR.appendData('dedup',encodeURIComponent(document.getElementById('dedup-$t').value));
			XHR.appendData('pattern',encodeURIComponent(document.getElementById('pattern-$t').value));
			XHR.appendData('zOrder',encodeURIComponent(document.getElementById('zOrder-$t').value));
			if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
     		XHR.sendAndLoad('$page', 'POST',xSave$t);

	}
</script>	
";
	
echo $tpl->_ENGINE_parse_body($html);
	
}


function items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$URL=$tpl->_ENGINE_parse_body("{url}");
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$new_item=$tpl->_ENGINE_parse_body("{new_rule}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	$t=time();
	$MyTime=time();
	$title=$tpl->javascript_parse_text("{rules}");
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("StoreID")){$q->CheckTables();}
	
	include_once(dirname(__FILE__)."/ressources/class.storeid.defaults.inc");
	$q->QUERY_SQL(FillStoreIDDefaults());
	if(!$q->ok){echo $q->mysql_error_html();die();}
	
	
	
	
	
	
	$html="
	<table class='STOREID_RULES_TABLE' style='display: none' id='STOREID_RULES_TABLE' style='width:99%'></table>
<script>
var DeleteAclKey=0;
function LoadTable$t(){
$('#STOREID_RULES_TABLE').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$_GET["aclid"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>&nbsp;</span>', name : 'zOrder', width :50, sortable : true, align: 'center'},	
		{display: '<span style=font-size:18px>$pattern</span>', name : 'pattern', width : 585, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$URL</span>', name : 'dedup', width : 585, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'up', width :43, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width :43, sortable : false, align: 'center'},		
		{display: '&nbsp;', name : 'del', width : 43, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : LinkAclItem$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : Apply$t},

		],	
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: '$URL', name : 'dedup'},
		{display: 'ID', name : 'ID'},
		],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
}
function LinkAclItem$t() {
	Loadjs('$page?ruleid-js=0');
	
}	
var xHyperCacheRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#STOREID_RULES_TABLE').flexReload();
}	
	
function HyperCacheRuleGroupUpDown(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-order', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',xHyperCacheRuleGroupUpDown$t);

}

function Apply$t(){
	Loadjs('squid.hypercache.rules.progress.php');
}

LoadTable$t();
</script>
	
	";
	
	echo $html;
	
}

function items_negation(){
	$md5=$_POST["acl-rule-link-negation"];
	$sql="UPDATE qos_sqacllinks SET negation={$_POST["value"]} WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function items_delete(){
	$ID=$_POST["delete"];
	$sql="DELETE FROM StoreID WHERE ID='$ID'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function items_save(){
	$ID=$_POST["ID"];
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=trim(url_decode_special_tool($ligne));
		
	}
	
	if($_POST["dedup"]==null){echo "URL is null!\n";return;}
	if($_POST["pattern"]==null){echo "Pattern is null!\n";return;}
	
	$zmd5=md5($_POST["pattern"]);
	$_POST["pattern"]=mysql_escape_string2($_POST["pattern"]);
	$_POST["dedup"]=mysql_escape_string2($_POST["dedup"]);
	$sql="INSERT IGNORE INTO StoreID (zmd5,pattern,dedup,zOrder,enabled) VALUES 
	('$zmd5','{$_POST["pattern"]}','{$_POST["dedup"]}','{$_POST["zOrder"]}','{$_POST["enabled"]}')";
	
	if($ID>0){
		$sql="UPDATE StoreID SET 
			`pattern`='{$_POST["pattern"]}',
			`dedup`='{$_POST["dedup"]}',
			`zOrder`='{$_POST["zOrder"]}',
			`enabled`='{$_POST["enabled"]}' WHERE ID=$ID";
		
		
	}
	

	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}


function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["aclid"];
	$acl=new squid_acls();
	$t0=$_GET["t"];
	

	

	
	$search='%';
	$table="StoreID";
	
	$page=1;

	if($q->COUNT_ROWS("StoreID")==0){json_error_show("No datas",1);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$acl=new squid_acls_groups();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["zmd5"];
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}')");
		
		$up=imgsimple("arrow-up-32.png","","HyperCacheRuleGroupUpDown('{$ligne["ID"]}',0)");
		$down=imgsimple("arrow-down-32.png","","HyperCacheRuleGroupUpDown('{$ligne["ID"]}',1)");
		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		
	$data['rows'][] = array(
		'id' => "$mkey",
		'cell' => array(
				"<span style=font-size:18px>{$ligne["zOrder"]}</span>",
				"<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?ruleid-js={$ligne["ID"]}');\" 
				style='font-size:18px;text-decoration:underline'>{$ligne["pattern"]}</span>",
				"<span style=font-size:18px>{$ligne["dedup"]}</span>",
				"<center>$up</center>","<center>$down</center>","<center>$delete</center>")
		);
	}
	
	
	echo json_encode($data);	
}

function items_link_order(){
	$ID=$_POST["acl-rule-link-order"];
	$direction=$_POST["direction"];
	$table="StoreID";
	//up =1, Down=0
	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM StoreID WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE StoreID SET zOrder='$OlOrder' WHERE zOrder='$NewOrder'";
	//echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE StoreID SET zOrder='$NewOrder' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	//echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT ID FROM StoreID ORDER BY zOrder");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE StoreID SET zOrder='$c' WHERE ID='$ID'";
		//echo "$sql\n";
		$q->QUERY_SQL($sql,"artica_backup");
		$c++;
		
	}
	
	
}

