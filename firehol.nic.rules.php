<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.ndpi.services.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$error')";
	die();
}

if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["ruleid"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tab();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["rule-new"])){rule_new_save();exit;}
if(isset($_POST["rule-enable"])){rule_enable();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_POST["rule-order"])){rule_order();exit;}

iptables_table();
function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ruleid"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	
	if($ID==0){
		$xt=time();
		$rulename=$tpl->javascript_parse_text("{rulename}");
		$html="
				
		var xSave$xt= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);return;}
			$('#FIREWALL_NIC_RULES').flexReload();
		}
		
		function Save$xt(){
			var XHR = new XHRConnection();
			var pp=prompt('$rulename ?');
			if(!pp){return;}
			XHR.appendData('rule-new',  encodeURIComponent(pp));
			XHR.appendData('eth',  '$eth');		
			XHR.sendAndLoad('$page', 'POST',xSave$xt);	
		}
				
		Save$xt()";
		
		echo $html;
		return;
	}
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
	$title="$eth::".$tpl->javascript_parse_text($ligne["rulename"]);
	echo "YahooWin2('1200','$page?rule-tabs=yes&ID=$ID&t=$t&eth=$eth','$title')";
}
function rule_tab(){
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	$eth=$_GET["eth"];
	$ID=$_GET["ID"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];


	$array["rule-popup"]="{rule}";
	$array["inbound-objects"]="{inbound_object}";
	$array["outbound-objects"]="{outbound_object}";
	$array["rule-time"]="{time_restriction}";
	
	$fontsize="font-size:18px";
	while (list ($index, $ligne) = each ($array) ){
		if($index=="rule-popup"){
			$html[]="<li><a href=\"firehol.nic.rules.popup.php?$index=yes&eth=$eth&ID=$ID&t=$t\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
		
		if($index=="inbound-objects"){
			$html[]="<li><a href=\"firehol.nic.rules.objects.php?$index=yes&eth=$eth&ID=$ID&t=$t&direction=0\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
		
		if($index=="outbound-objects"){
			$html[]="<li><a href=\"firehol.nic.rules.objects.php?$index=yes&eth=$eth&ID=$ID&t=$t&direction=1\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
		if($index=="rule-time"){
			//time_restriction field
			$html[]="<li><a href=\"firehol.nic.rules.time.php?$index=yes&eth=$eth&ID=$ID&t=$t&direction=1\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
	}


	echo build_artica_tabs($html,'main_firewall_rule_'.$ID);

}

function rule_enable(){
	$ID=$_POST["rule-enable"];
	$q=new mysql();
	$sql="SELECT `enabled` FROM iptables_main WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}

	if($ligne["enabled"]==0){
		$sql="UPDATE iptables_main SET enabled='1' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	}
	if($ligne["enabled"]==1){
		$sql="UPDATE iptables_main SET enabled='0' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	}
}

function rule_delete(){
	$ID=$_POST["rule-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}

	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM firewallfilter_sqacllinks WHERE aclid='$ID'");
}

function iptables_table(){

	

	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$title=$tpl->javascript_parse_text("{rules}: {all_interfaces}");
	if($eth<>null){
		$ethC=new system_nic($eth);
		$title=$tpl->javascript_parse_text("{rules}: $eth $ethC->NICNAME");
	}
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$interface=$tpl->javascript_parse_text("{interface}");

	$t=time();
	$html="
	<table class='FIREWALL_NIC_RULES' style='display: none' id='FIREWALL_NIC_RULES' style='width:99%'></table>
	<script>

	function LoadTable$t(){
	$('#FIREWALL_NIC_RULES').flexigrid({
	url: '$page?rules=yes&eth=$eth&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'zOrder', width :20, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$rulename</span>', name : 'rulename', width : 762, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$interface</span>', name : 'eth', width : 198, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width : 83, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$type</span>', name : 'accepttype', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 70, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$delete</span>', name : 'del', width : 70, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '<strong style=font-size:18px>$new</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},

	],
	searchitems : [
	{display: '$rulename', name : 'rulename'},
	],
	sortname: 'zOrder',
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
}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#FIREWALL_NIC_RULES').flexReload();
}

function RuleGroupUpDown$t(ID,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', '$eth');
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function DeleteRule$t(ID){
	if(!confirm('$delete '+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rule-delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
	Loadjs('firehol.progress.php');
}

function ChangEnabled$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function NewRule$t() {
	Loadjs('$page?ruleid=0&eth=$eth&t=$t&table=$iptable',true);
}
LoadTable$t();
</script>
";
	echo $html;

}

function rule_new_save(){
	
	$rulename=mysql_escape_string2(url_decode_special_tool($_POST["rule-new"]));
	$eth=$_POST["eth"];
	$sql="INSERT IGNORE INTO iptables_main (`rulename`,`eth`,`accepttype`,`enabled`) 
	VALUES ('$rulename','$eth','ACCEPT','1')";
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("iptables_main","service","artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `iptables_main` ADD `service` varchar(50) NULL ,ADD INDEX ( service );","artica_backup");
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function rules(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$eth=trim($_GET["eth"]);
	$table_type=$_GET["table"];
	$sock=new sockets();
	$EnableL7Filter=intval($sock->GET_INFO("EnableL7Filter"));
	$EnableQOS=intval($sock->GET_INFO("EnableQOS"));
	$t=$_GET["t"];
	$FORCE_FILTER=null;
	$search='%';
	$table="iptables_main";
	
	if($eth<>null){
		$table="(SELECT iptables_main.* FROM iptables_main WHERE iptables_main.eth='$eth' AND iptables_main.MOD='$table_type'
		ORDER BY zOrder ) as t";
	}
	$page=1;

	if($q->COUNT_ROWS("iptables_main","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
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
		$delete=imgsimple("delete-32.png",null,"DeleteRule$t('{$ligne["ID"]}')");
		$enabled=Field_checkbox("enabled-{$ligne["ID"]}", 1,$ligne["enabled"],"ChangEnabled{$_GET["t"]}('{$ligne["ID"]}')");
		$up=imgsimple("arrow-up-32.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',0)");
		$down=imgsimple("arrow-down-32.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',1)");
		$L7Mark=$ligne["L7Mark"];
		$FORWARD_TEXT=null;
		if($EnableL7Filter==0){$L7Mark=0;}
		$rulename=trim(utf8_encode($ligne["rulename"]));
		$eth_text=$ligne["eth"];
		
		
		if(!preg_match("#(.+?)2(.+?)#", $eth_text)){
			$eth_text="<a href=\"javascript:blud();\" OnClick=\"javascript:GoToNicFirewallConfiguration('$eth_text');\"
			style='text-decoration:underline;font-weight:bold'>$eth_text</a>";
			
		}
	
		
		$EXPLAIN=EXPLAIN_THIS_RULE($ligne);

		if($ligne["jlog"]==1){
			$explain=$explain."<br><span style='font-size:12px'>$log_all_events</span>";
		}


		if($rulename==null){$rulename=$tpl->_ENGINE_parse_body("{rule} {$ligne["ID"]}");}

		if($ligne["enabled"]==0){$color="#8a8a8a";}

		$js="Loadjs('$MyPage?ruleid={$ligne["ID"]}&eth=$eth&t={$_GET["t"]}&table=$table_type',true);";

		$ACTION="cloud-goto-32.png";
		if($ligne["enabled"]==0){$ACTION="cloud-goto-32-grey.png";}
		
		if($ligne["accepttype"]=="DROP"){
			$ACTION="cloud-deny-32.png";
			if($ligne["enabled"]==0){$ACTION="cloud-deny-32-grey.png";}
		}

		if($ligne["accepttype"]=="RETURN"){
			$ACTION="arrow-right-32.png";
		}
		if($ligne["accepttype"]=="LOG"){
			$ACTION="log-32.png";
		}


		$JSRULE="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
		style='font-size:22px;font-weight:bold;text-decoration:underline;color:$color'>";

		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<span style='font-size:18px;font-weight:bold;color:$color'>{$ligne["zOrder"]}</span>",
						"$JSRULE$rulename</a>&nbsp;$EXPLAIN$FORWARD_TEXT$time",
						"<center style='font-size:18px;font-weight:bold;color:$color'>$eth_text</center>",
						"<div style=\"margin-top:5px\">$enabled</div>",
						"<center style='font-size:14px;font-weight:bold;color:$color'><img src='img/$ACTION'></span>",
						"<center style=\"margin-top:5px\">$up</center>",
						"<center style=\"margin-top:5px\">$down</center>",
						"<center style=\"margin-top:4px\">$delete</center>")
		);
	}

	echo json_encode($data);

}

function EXPLAIN_THIS_RULE($ligne){
	
	$red_color="#d32d2d";
	$color="black";
	$log=null;
	if($ligne["enabled"]==0){$red_color="#8a8a8a";$color="#8a8a8a";}
	
	$service=$ligne["service"];
	if($service<>null){$service="<br>{service2} &laquo;$service&raquo;";}
	
	if($ligne["jlog"]==1){
		$log=" {and} {log_all_events}";
	}
	
	$application=$ligne["application"];
	if($application<>null){
		$ndpi=new ndpi_services();
		$service="<br>{application} &laquo;".$ndpi->dpiArray[$ligne["application"]]."&raquo;";
	}
	
	if($ligne["accepttype"]=="ACCEPT"){
		$action="{then} {accept}$log";
		
	}
	
	if($ligne["accepttype"]=="DROP"){
		$action="{then} <strong style='color:$red_color'>{deny_access}</strong>$log";
		
	}
	

	
	$inboud=EXPLAIN_LIST_OBJECTS($ligne["ID"],0,$color);
	if($inboud<>null){
		$inbound_text="{for_inbound_objects} $inboud {and} ";
	}else{
		$inbound_text="{for_all_nodes} {and} ";
	}
	
	$outbound=EXPLAIN_LIST_OBJECTS($ligne["ID"],1,$color);
	if($outbound<>null){
		$outbound_text="{to} $outbound {and} ";
	}else{
		$outbound_text="{to_everything} {and} ";
	}
	
	$ExplainThisTime=ExplainThisTime($ligne);
	if($ExplainThisTime<>null){$ExplainThisTime="<br>$ExplainThisTime";}
	
	$intro="<br><span style='font-size:16px;color:$color'>$inbound_text $outbound_text$service $action $ExplainThisTime";
	
	$f[]=$intro;
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(@implode("<br>", $f)."</span>");
	
	
}

function EXPLAIN_LIST_OBJECTS($ID,$dir,$color){
	
	$q=new mysql_squid_builder();
	$table="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
	firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID as gpid,
	webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
	WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
	AND firewallfilter_sqacllinks.aclid=$ID
	AND firewallfilter_sqacllinks.direction='$dir'
	AND webfilters_sqgroups.enabled='1'
	ORDER BY firewallfilter_sqacllinks.zOrder";
	
	
	$results=$q->QUERY_SQL($table);
	if(!$q->ok){return $q->mysql_error;}
	$GPS=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupType=$ligne["GroupType"];
		$ID=$ligne["gpid"];
		$js_items="javascript:Loadjs('squid.acls.groups.php?AddItem-js=yes&item-id=-1&ID=$ID&table-org=FIREWALL_NIC_RULES',true);";
		$js_group="javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$ID&table-org=FIREWALL_NIC_RULES',true);";
		
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$ID'"));
		$items=$ligne2["tcount"];
		$GPS[]="<strong><a href=\"javascript:blur();\" OnClick=\"$js_group\" 
		style='text-decoration:underline;color:$color'>$GroupName</a> (<a 
		href=\"javascript:blur();\" OnClick=\"$js_items\" $items {elements}</a>)</strong>";
		
	}
	if(count($GPS)==0){return null;}
	return @implode("<br> {or} ", $GPS);"<br>";
}

function ExplainThisTime($ligne){
	if($ligne["enablet"]==0){return "{all_times}";}
	$f=array();
	$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");

	$TTIME=unserialize($ligne["time_restriction"]);

	$DDS=array();

	while (list ($num, $maks) = each ($array_days)){
		if($TTIME["D{$num}"]==1){$DDS[]=$num;}
		$DAYS[]="{{$array_days[$num]}}";

	}

	if(count($DDS)>0){
		$f[]=@implode(", ", $DAYS);
	}

	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$f[]="{from_time} {$TTIME["ftime"]} {to_time} {$TTIME["ttime"]}";
	}

	if(count($f)>0){
		return @implode("<br>", $f);
	}


}
function rule_order(){
	$ID=$_POST["rule-order"];
	$direction=$_POST["direction"];
	$eth=$_POST["eth"];
	$table=$_POST["table"];


	//up =1, Down=0
	$q=new mysql();
	$sql="SELECT `zOrder`,`MOD`,`eth` FROM iptables_main WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	$table=$ligne["MOD"];
	$eth=$ligne["eth"];

	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE iptables_main SET zOrder='$OlOrder' WHERE `zOrder`='$NewOrder' AND `MOD`='$table' AND `eth`='$eth'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
	$sql="UPDATE iptables_main SET zOrder='$NewOrder' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE `MOD`='$table' AND `eth`='$eth' ORDER BY zOrder","artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("UPDATE iptables_main SET zOrder='$c' WHERE ID='$ID'","artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
		$c++;

	}



}