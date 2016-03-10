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

if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_POST["acl-rule-link-enable"])){items_enable();exit;}
if(isset($_POST["acl-rule-link-delete"])){items_unlink();exit;}
if(isset($_POST["acl-rule-link-negation"])){items_negation();exit;}
if(isset($_POST["acl-rule-link-order"])){items_link_order();exit;}

items_js();


	


function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$t=$_GET["t"];
	$MyTime=time();
	

	
	
	$html="
	<table class='QOS_CONTAINER_DSCP' style='display: none' id='QOS_CONTAINER_DSCP' style='width:99%'></table>
<script>
var DeleteAclKey=0;
function LoadTable$t(){
$('#QOS_CONTAINER_DSCP').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$_GET["aclid"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>DSCP/TOS</span>', name : 'gpid', width : 720, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
		
	],

	searchitems : [
		{display: '$items', name : 'GroupName'},
		],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
}
function LinkAclItem() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid{$_GET["aclid"]}&FilterType=IPTABLES');
	
}	

function LinkAddAclItem(){
	Loadjs('squid.acls.groups.php?AddGroup-js=-1&link-acl={$_GET["aclid"]}&table-acls-t=$t');
}

var xChangeDCSP$MyTime= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#QOS_CONTAINER_DSCP').flexReload();
}	
	
	function ChangeDCSP(num){
		var value=0;
		var XHR = new XHRConnection();
		XHR.appendData('aclid', '{$_GET["aclid"]}');
		XHR.appendData('acl-rule-link-enable', num);
		XHR.sendAndLoad('$page', 'POST',xChangeDCSP$MyTime);
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

function items_unlink(){
	$md5=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM qos_sqacllinks WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function items_enable(){
	$aclid=$_POST["aclid"];
	$num=$_POST["acl-rule-link-enable"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT dscp FROM `qos_containers` WHERE ID='$aclid'","artica_backup"));
	$array=unserialize($ligne["dscp"]);
	if(!isset($array[$num])){$array[$num]=true;}else{unset($array[$num]);}
	$newarray=mysql_escape_string2(serialize($array));
	
	if(!$q->FIELD_EXISTS("qos_containers","dscp","artica_backup")){
		$sql="ALTER TABLE `qos_containers` ADD `dscp` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error;
			return false;
		}
	}
	
	
	$q->QUERY_SQL("UPDATE qos_containers SET dscp='$newarray' WHERE ID='$aclid'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$q2=new mysql_squid_builder();
	$ID=$_GET["aclid"];
	
	$t0=$_GET["t"];
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT dscp FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
	$dscp=unserialize($ligne["dscp"]);
	$page=1;

	
	
	
	
	$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($ayDscp);
	$data['rows'] = array();
	
	
	while (list ($num, $ligne) = each ($ayDscp) ){
		
		$acls=array();
		$results=$q2->QUERY_SQL("SELECT aclid FROM webfilters_sqaclaccess WHERE httpaccess='tcp_outgoing_tos' AND httpaccess_value='1' and httpaccess_data='$num'");
		
		if(!$q2->ok){$acls[]=$q2->mysql_error_html();}
		while ($ligneZ = mysql_fetch_assoc($results)) {
			$aclid=$ligneZ["aclid"];
			$ligneZZ=mysql_fetch_array($q2->QUERY_SQL("SELECT aclname FROM webfilters_sqacls WHERE ID='$aclid'"));
			if(!$q2->ok){$acls[]=$q2->mysql_error_html();continue;}
			$acls[]="<i style='font-size:16px'>Proxy: [$aclid] {$ligneZZ["aclname"]}</i>";
		}
		
		$value=0;
		if(isset($dscp[$num])){$value=1;}
		
	
		$negation=Field_checkbox("enable-$num", 1,$value,"ChangeDCSP('$num')");
	$data['rows'][] = array(
		'id' => "$num",
		'cell' => array("<span style='font-size:22px;font-weight:bold'>$num ($ligne)</span>&nbsp;".@implode("<br>", $acls),
		$negation)
		);
	}
	
	
	echo json_encode($data);	
}

function items_link_order(){
	$mkey=$_POST["acl-rule-link-order"];
	$direction=$_POST["direction"];
	$aclid=$_POST["aclid"];
	$table="qos_sqacllinks";
	//up =1, Down=0
	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM qos_sqacllinks WHERE zmd5='$mkey'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE qos_sqacllinks SET zOrder='$OlOrder' WHERE zOrder='$NewOrder' AND aclid='$aclid'";
//	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE qos_sqacllinks SET zOrder='$NewOrder' WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql);
//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM qos_sqacllinks WHERE aclid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE qos_sqacllinks SET zOrder='$c' WHERE zmd5='$zmd5'");
		$c++;
		
	}
	
	
}

