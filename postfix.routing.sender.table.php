<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once(dirname(__FILE__) . "/ressources/class.system.network.inc");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsPostfixAdministrator){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
}

if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_tab();exit;}
if(isset($_GET["item-form"])){item_popup();exit;}
if(isset($_POST["item-move"])){item_move();exit;}
if(isset($_POST["enabledauth"])){item_auth_save();exit;}
if(isset($_POST["zmd5"])){item_save();exit;}
if(isset($_GET["item-delete-js"])){item_delete_js();exit;}
if(isset($_GET["relay-sender-table-list"])){main_search();exit;}
if(isset($_POST["item-delete"])){item_delete();exit;}
if(isset($_GET["apply_sender_routing_rule-js"])){apply_sender_routing_rule_js();exit;}
if(isset($_POST["apply_sender_routing_rule"])){apply_sender_routing_rule();exit;}
if(isset($_GET["item-auth"])){item_auth();exit;}


main_table();

// table sender_dependent_relay_host SENDER_DEPENDENT_RELAY_HOST

function item_delete_js(){
	header("content-type: application/x-javascript");
	$zmd5=$_GET["item-delete-js"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT domain FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
	$title=$tpl->javascript_parse_text($ligne["domain"]);
	$text=$tpl->javascript_parse_text("{item}: $title - {delete} ?");

	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SENDER_DEPENDENT_RELAY_HOST').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('item-delete','$zmd5');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function item_tab(){
	$zmd5=$_GET["zmd5"];
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$add_sender_routing_rule=$tpl->_ENGINE_parse_body("{add_sender_routing_rule}");
	if($zmd5==null){
		$array["item-form"]="$hostname:$add_sender_routing_rule";
		
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT domain FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
		$title=$tpl->javascript_parse_text($ligne["domain"]);
		$array["item-form"]=$title;
		$array["item-auth"]="{authentication}";
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes&hostname=$hostname&zmd5=$zmd5\" style='font-size:18px'><span>$ligne</span></a></li>\n";
			
	}	
	
	echo build_artica_tabs($tab, "sender_routing_popup_tab");
	
}

function apply_sender_routing_rule_js(){
	header("content-type: application/x-javascript");
	$zmd5=$_GET["item-delete-js"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SENDER_DEPENDENT_RELAY_HOST').flexReload();
}
	
function xFunct$t(){
	var XHR = new XHRConnection();
	XHR.appendData('apply_sender_routing_rule','yes');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
	
xFunct$t();
";
	echo $html;
	
}
function apply_sender_routing_rule(){
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?apply_sender_routing_rule=yes&hostname={$_POST["hostname"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{done}");
}


function item_js(){
	header("content-type: application/x-javascript");
	$zmd5=$_GET["zmd5"];
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{add_sender_routing_rule}");
	if($zmd5<>null){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT domain FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
		$title=$tpl->javascript_parse_text($ligne["domain"]);
		
	}
	echo "YahooWin4('850','$page?item-popup=yes&zmd5=$zmd5&hostname=$hostname','$hostname::$title');";

}

function item_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM sender_dependent_relay_host WHERE `zmd5`='{$_POST["item-delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function item_popup(){
	$button="{add}";
	$page=CurrentPageName();
	$q=new mysql();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$hostname=$_GET["hostname"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
	if($zmd5<>null){$button="{save}";}
	if(intval($ligne["relay_port"])==0){$ligne["relay_port"]=25;}
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips[null]="{default}";
	
	$nets=Field_array_Hash($ips,"smtp_bind_address-$t",$ligne["smtp_bind_address"],"style:font-size:22px;padding:3px");
	
$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend>{rule_enabled}</td>
		<td>" . Field_checkbox_design('enabled',1,$ligne["enabled"])."</td>
	</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:22px'>{order}:</strong></td>
			<td style='font-size:22px'>" . Field_text("zOrder-$t",$ligne["zOrders"],"font-size:22px;width:93px") . "</td>
		</tr>							
	<tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{domain}:</strong></td>
		<td style='font-size:22px'>" . Field_text("domain-$t",$ligne["domain"],"font-size:22px;width:400px") . "</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend>{direct_mode}</td>
		<td>" . Field_checkbox_design('directmode',1,$ligne["directmode"],"DirectMode$t()")."</td>
		
	</tr>				
	<tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{relay_address}:</strong></td>
		<td style='font-size:22px'>" . Field_text("relay_address-$t",$ligne["relay"],"font-size:22px;width:300px") . "</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{listen_port}:</strong></td>
		<td style='font-size:22px'>" . Field_text("relay_port-$t",$ligne["relay_port"],"font-size:22px;width:90px") . "</td>
	</tr>
				
	<tr>
		<td style='font-size:22px' class=legend>{MX_lookups}</td>
		<td>" . Field_checkbox_design('MX_lookups',1,$ligne["lookups"])."</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend>{override_transport}</td>
		<td>" . Field_checkbox_design("override_transport-$t",1,$ligne["override_transport"],"DirectMode2$t()")."</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend>{override_relay}</td>
		<td>" . Field_checkbox_design("override_relay-$t",1,$ligne["override_relay"])."</td>
	</tr>
				
		
	<tr>
		<td style='font-size:22px' class=legend>{smtp_bind_address}</td>
		<td style='font-size:22px'>$nets</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend>{smtp_helo_name}</td>
		<td style='font-size:22px'>" . Field_text("smtp_helo_name-$t",$ligne["smtp_helo_name"],"font-size:22px;width:300px") . "</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend>{syslog_name}</td>
		<td style='font-size:22px'>" . Field_text("syslog_name-$t",$ligne["syslog_name"],"font-size:22px;width:300px") . "</td>
	</tr>	
	
				
			
	<tr>
		<td align='right' colspan=2>". button($button,"PostfixAddNewSenderTable$t()",30)."</td>
	</tr>
</table>
</div>
<div class=explain style='font-size:14px'>{sender_dependent_relayhost_maps_text}</div>
<script>
var X_PostfixAddNewSenderTable$t= function (obj) {
	var results=obj.responseText;
	var zmd5='$zmd5';
	if (results.length>0){alert(results);return;}
	if(zmd5.length==0){YahooWin4Hide();}
	$('#SENDER_DEPENDENT_RELAY_HOST').flexReload();
}
function PostfixAddNewSenderTable$t(){
	var XHR = new XHRConnection();
	XHR.appendData('zmd5','$zmd5');
	XHR.appendData('hostname','$hostname');
	XHR.appendData('domain',document.getElementById('domain-$t').value);
	XHR.appendData('relay_address',document.getElementById('relay_address-$t').value);
	XHR.appendData('relay_port',document.getElementById('relay_port-$t').value);
	XHR.appendData('smtp_bind_address',document.getElementById('smtp_bind_address-$t').value);
	XHR.appendData('smtp_helo_name',document.getElementById('smtp_helo_name-$t').value);
	XHR.appendData('syslog_name',document.getElementById('syslog_name-$t').value);
	XHR.appendData('zOrder',document.getElementById('zOrder-$t').value);
	
	
	
	if(document.getElementById('override_transport-$t').checked){
		XHR.appendData('override_transport',1);
	}else{
		XHR.appendData('override_transport',0);
	}

	if(document.getElementById('override_relay-$t').checked){
		XHR.appendData('override_relay',1);
	}else{
		XHR.appendData('override_relay',0);
	}		
	
	if(document.getElementById('MX_lookups').checked){
		XHR.appendData('MX_lookups',1);
	}else{
		XHR.appendData('MX_lookups',0);
	}
	
	if(document.getElementById('enabled').checked){
		XHR.appendData('enabled',1);
	}else{
		XHR.appendData('enabled',0);
	}	
	if(document.getElementById('directmode').checked){
		XHR.appendData('directmode',1);
	}else{
		XHR.appendData('directmode',0);
	}		
	
	XHR.sendAndLoad('$page', 'POST',X_PostfixAddNewSenderTable$t);
}

function DirectMode$t(){
	if(document.getElementById('directmode').checked){
		document.getElementById('relay_address-$t').disabled=true;
		document.getElementById('relay_port-$t').disabled=true;
	}else{
		document.getElementById('relay_address-$t').disabled=false;
		document.getElementById('relay_port-$t').disabled=false;
	}
}

function DirectMode2$t(){
	document.getElementById('smtp_bind_address-$t').disabled=true;
	document.getElementById('smtp_helo_name-$t').disabled=true;
	document.getElementById('syslog_name-$t').disabled=true;


	if(document.getElementById('override_transport-$t').checked){
		document.getElementById('smtp_bind_address-$t').disabled=false;
		document.getElementById('smtp_helo_name-$t').disabled=false;
		document.getElementById('syslog_name-$t').disabled=false;
	}
}



DirectMode$t();DirectMode2$t();
</script>
";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function item_save(){
	
	/*`hostname` varchar(255) NOT NULL,
	 `domain` varchar(255) NOT NULL,
	`enabled` smallint(1) NOT NULL DEFAULT '1',
	`relay` varchar(255) NOT NULL,
	`relay_port` smallin(2) NOT NULL,
	`lookups` smallint(1) NOT NULL,
	`zOrders` smallint(2) NOT NULL,
	`directmode` smallint(1) NOT NULL,
	`zmd5` varchar(90) NOT NULL,
	*/
	
	
	
	$q=new mysql();
	$q->BuildTables();
	
	if(!$q->FIELD_EXISTS("sender_dependent_relay_host","syslog_name","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `syslog_name` varchar(128)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	if($_POST["zmd5"]==null){
		$zmd5=md5("{$_POST["hostname"]}{$_POST["domain"]}");
		$sql="INSERT IGNORE INTO `sender_dependent_relay_host` 
		(hostname,domain,enabled,relay,relay_port,lookups,zOrders,directmode,zmd5,override_transport,
		smtp_helo_name,smtp_bind_address,override_relay)
		VALUES('{$_POST["hostname"]}','{$_POST["domain"]}',1,'{$_POST["relay_address"]}',
		'{$_POST["relay_port"]}','{$_POST["MX_lookups"]}','0','{$_POST["directmode"]}','$zmd5','{$_POST["override_transport"]}',
		'{$_POST["smtp_helo_name"]}','{$_POST["smtp_bind_address"]}','{$_POST["override_relay"]}')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n$sql";}
		return;
	}
	
	$sql="UPDATE `sender_dependent_relay_host` SET 
		`relay`='{$_POST["relay_address"]}',
		`relay_port`='{$_POST["relay_port"]}',
		`directmode`='{$_POST["directmode"]}',
		`lookups`='{$_POST["MX_lookups"]}',
		`override_transport`='{$_POST["override_transport"]}',
		`override_relay`='{$_POST["override_relay"]}',
		`smtp_helo_name`='{$_POST["smtp_helo_name"]}',
		`smtp_bind_address`='{$_POST["smtp_bind_address"]}',
		`zOrders`='{$_POST["zOrder"]}',
		`syslog_name`='{$_POST["syslog_name"]}',
		`enabled`='{$_POST["enabled"]}' WHERE `zmd5`='{$_POST["zmd5"]}'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function main_table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("sender_dependent_relay_host", "artica_backup")){
		$q->BuildTables();
	}
	
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{sender_domain_email}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($_GET["ou"]);
	$destination=$tpl->javascript_parse_text("{destination}");
	$hostname=$_GET["hostname"];
	$apply=$tpl->javascript_parse_text("{apply}");
	$about2=$tpl->javascript_parse_text("{about2}");
	$add_sender_routing_rule=$tpl->_ENGINE_parse_body("{add_sender_routing_rule}");
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
			"javascript:AddRemoteDomain_form(\"{$_GET["ou"]}\",\"new domain\")","add_relay_domain",210);

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$add_sender_routing_rule</strong>', bclass: 'add', onpress : add_sender_routing_rule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : apply_sender_routing_rule$t},
	{name: '<strong style=font-size:18px>$about2</strong>', bclass: 'help', onpress : Help$t},
	],";

	$explain=$tpl->javascript_parse_text("{postfix_transport_senders_explain}");
	$html="
	<input type='hidden' id='ou' value='{$_GET["ou"]}'>
	<table class='SENDER_DEPENDENT_RELAY_HOST' style='display: none' id='SENDER_DEPENDENT_RELAY_HOST' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SENDER_DEPENDENT_RELAY_HOST').flexigrid({
	url: '$page?relay-sender-table-list=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$domain</span>', name : 'domain', width : 450, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$relay</span>', name : 'relay', width :450, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>AUTH</span>', name : 'zOrders', width : 84, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>UP</span>', name : 'zOrders', width : 84, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>DOWN</span>', name : 'zOrders', width : 84, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 136, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$domain', name : 'domain'},
	{display: '$relay', name : 'relay'},
	],
	sortname: 'zOrders',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '550',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function  Help$t(){
alert('$explain');
}

var RefreshTable$t= function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);return;}
	$('#SENDER_DEPENDENT_RELAY_HOST').flexReload();
}

function MoveSubRuleLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('item-move', mkey);
	XHR.appendData('direction', direction);
	XHR.appendData('hostname', '$hostname');
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);	
}

function add_sender_routing_rule$t(){
	Loadjs('$page?item-js=yes&zmd5=&hostname=$hostname');
}

function sender_routing_ruleED$t(domainName){
	YahooWin3(552,'postfix.routing.table.php?SenderTable=yes&domainName='+domainName+'&t=$t','$sender_dependent_relayhost_maps_title::'+domainName);
}


function apply_sender_routing_rule$t(){
	Loadjs('postfix.sender.routing.progress.php?hostname=$hostname');

}

</script>
";

echo $html;


}

function item_move(){
	$zmd5=$_POST["item-move"];
	$hostname=$_POST["hostname"];
	$direction=$_POST["direction"];

	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	
	$LastOrder=$ligne["zOrders"];


	$ruleid=$ligne["zmd5"];

	if($direction=="up"){
		$NewOrder=$ligne["zOrders"]-1;
	}else{
		$NewOrder=$ligne["zOrders"]+1;
	}

	
	//echo "$zmd5: $LastOrder->$NewOrder ";
	$q->QUERY_SQL("UPDATE sender_dependent_relay_host SET zOrders='$NewOrder' WHERE zmd5='$zmd5'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

	$q->QUERY_SQL("UPDATE sender_dependent_relay_host SET zOrders='$LastOrder' 
			WHERE zOrders='$NewOrder' AND zmd5!='$zmd5' 
			AND hostname='$hostname'","artica_backup");
	
	if(!$q->ok){echo $q->mysql_error;}

	$sql="SELECT *  FROM sender_dependent_relay_host WHERE `hostname`='$hostname' ORDER BY `zOrders`";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["zmd5"];

		$q->QUERY_SQL("UPDATE sender_dependent_relay_host SET zOrders='$c' WHERE zmd5='$ID'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;

	}

}

function main_search(){
	$MyPage=CurrentPageName();
	$main=new maincf_multi();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$t=$_GET["t"];
	$table="sender_dependent_relay_host";

	$searchstring=string_to_flexquery();
	$page=1;
	$table="(SELECT * FROM sender_dependent_relay_host WHERE `hostname`='{$_GET["hostname"]}' ORDER by zOrders) as t";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";
	
	
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	$dns_destination=$tpl->javascript_parse_text("{direct_mode}");
	$all_others_domains=$tpl->javascript_parse_text("{all_others_domains}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";

		$ColorTime="black";
		$hostname=$ligne["hostname"];
		$domain=$ligne["domain"];
		$zmd5=$ligne["zmd5"];
		$relay=$ligne["relay"];
		$relay_port=$ligne["relay_port"];
		$lookups=$ligne["lookups"];
		$relay_text=$main->RelayToPattern($relay, $relay_port, $lookups);
		
		$icon_grey="ok32-grey.png";
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_f=$icon_grey;
		if($ligne["enabled"]==0){$ColorTime="#8a8a8a";}
		$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline;color:$ColorTime'";
		$style=" style='font-size:{$fontsize}px;color:$ColorTime'";
		

		$urijs="Loadjs('$MyPage?item-js=yes&zmd5=$zmd5&hostname=$hostname');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";

		$orders=imgtootltip("48-settings.png",null,"Loadjs('artica-meta.menus.php?gpid={$ligne["ID"]}');");
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?item-delete-js=$zmd5')");
		
		$up=imgsimple("arrow-up-32.png",null,"MoveSubRuleLinks$t('$zmd5','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveSubRuleLinks$t('$zmd5','down')");
	

		if($ligne["directmode"]==1){$relay_text="$dns_destination";}
		if($ligne["enabledauth"]==1){$icon_f=$icon;}
		if($domain=="*"){$domain=$all_others_domains;}

		$cell=array();
		$cell[]="<span $style>$link$domain</a></span>";
		$cell[]="<span $style>$link$relay_text</a></span>";
		$cell[]="<center $style><img src='img/$icon_f'></a></center>";
		$cell[]="<center $style>$up</a></center>";
		$cell[]="<center $style>$down</a></center>";
		$cell[]="<center $style>$delete</a></center>";

		$data['rows'][] = array(
				'id' => $ligne['uuid'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}

function item_auth(){
	$page=CurrentPageName();
	$q=new mysql();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$hostname=$_GET["hostname"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sender_dependent_relay_host WHERE zmd5='$zmd5'","artica_backup"));
	$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	</tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{authenticate}:</strong></td>
		<td style='font-size:12px'>" . Field_checkbox_design("enabledauth-$t",1,$ligne["enabledauth"],"DirectMode$t()") . "</td>
		</tr>					
	<tR>
		<td align='right' nowrap class=legend style='font-size:22px'>{username}:</strong></td>
		<td style='font-size:12px'>" . Field_text("relay_username-$t",$ligne["username"],"font-size:22px;padding:3px") . "</td>
		</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{password}:</strong></td>
		<td style='font-size:12px'>" . Field_password("relay_password-$t",$ligne["password"],"font-size:22px;padding:3px;") . "</td>
	</tr>
	<tr>
		<td align='right' colspan=2>". button("{apply}","PostfixAddNewSenderTable$t()",26)."</td>
	</tr>
	</table>
	</div>
<script>
var X_PostfixAddNewSenderTable$t= function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);return;}
	$('#SENDER_DEPENDENT_RELAY_HOST').flexReload();
}



function PostfixAddNewSenderTable$t(){
	var XHR = new XHRConnection();
	XHR.appendData('zmd5','$zmd5');
	XHR.appendData('hostname','$hostname');
	XHR.appendData('password',encodeURIComponent(document.getElementById('relay_password-$t').value));
	XHR.appendData('username',document.getElementById('relay_username-$t').value);
	
	if(document.getElementById('enabledauth-$t').checked){
	XHR.appendData('enabledauth',1);
	}else{
	XHR.appendData('enabledauth',0);
	}
	
	XHR.sendAndLoad('$page', 'POST',X_PostfixAddNewSenderTable$t);
	}
	
function DirectMode$t(){
	if(document.getElementById('enabledauth-$t').checked){
		document.getElementById('relay_username-$t').disabled=false;
		document.getElementById('relay_password-$t').disabled=false;
	}else{
		document.getElementById('relay_username-$t').disabled=true;
		document.getElementById('relay_password-$t').disabled=true;
	}
	}
	DirectMode$t();
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_auth_save(){
	
	
	
	
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$q=new mysql();
	
	
	if(!$q->FIELD_EXISTS("sender_dependent_relay_host","enabledauth","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `enabledauth` smallint(1) NULL,
		ADD INDEX ( `enabledauth` )";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("sender_dependent_relay_host","password","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `password` varchar(128) NULL";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	if(!$q->FIELD_EXISTS("sender_dependent_relay_host","username","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `username` varchar(128) NULL";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	
	$sql="UPDATE `sender_dependent_relay_host` SET
	`enabledauth`='{$_POST["enabledauth"]}',
	`password`='{$_POST["password"]}',
	`username`='{$_POST["username"]}'
	WHERE `zmd5`='{$_POST["zmd5"]}'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}


/*`hostname` varchar(255) NOT NULL,
`domain` varchar(255) NOT NULL,
`enabled` smallint(1) NOT NULL DEFAULT '1',
`relay` varchar(255) NOT NULL,
`relay_port` smallin(2) NOT NULL,
`lookups` smallint(1) NOT NULL,
`zOrders` smallint(2) NOT NULL,
`directmode` smallint(1) NOT NULL,
`zmd5` varchar(90) NOT NULL,
*/