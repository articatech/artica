<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	
	if(isset($_GET["id-js"])){ID_JS();exit;}
	if(isset($_GET["id-popup"])){ID_POPUP();exit;}
	if(isset($_POST["src_host"])){SaveRule();exit;}
	
	
	if(isset($_POST["revert"])){revert();exit;}
	if(isset($_GET["firehol_status"])){firehol_status();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["isFW"])){isFW();exit;}
	
	table();

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$nic=$_GET["nic"];
	$xtable=$_GET["xtable"];
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#SS5_FW_RULES').flexReload();
}
function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
	
	
	
}


function ID_JS(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ID=$_GET["id-js"];
	
	if($ID>0){
		$title=$tpl->javascript_parse_text("{rule}:$ID");
	}else{
		$title=$tpl->javascript_parse_text("{new_rule}");
	}
	echo "YahooWin('990','$page?id-popup=yes&ID=$ID','$title')";
	
	
}

function revert(){
	$q=new mysql();
	
	$sql="SELECT `allow_type` FROM `{$_POST["xtable"]}` WHERE `zmd5`='{$_POST["revert"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	if($ligne["allow_type"]==1){$allow_type=0;}else{$allow_type=1;}
	$q->QUERY_SQL("UPDATE `{$_POST["xtable"]}` SET allow_type=$allow_type WHERE `zmd5`='{$_POST["revert"]}'","artica_backup");
	
	
}
function delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM `ss5_fw` WHERE `ID`='{$_POST["delete"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
}	

function table(){
	
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$nic=new system_nic($_GET["nic"]);
	
	
	
	
	$t=time();
	$service2=$tpl->_ENGINE_parse_body("{service2}");
	$netzone=$tpl->_ENGINE_parse_body("{netzone}");
	$local_services=$tpl->_ENGINE_parse_body("{local_services}");
	$log=$tpl->_ENGINE_parse_body("{LOG}");
	$new_rule=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$services=$tpl->_ENGINE_parse_body("{services}");
	$current_rules=$tpl->_ENGINE_parse_body("{current_rules}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$apply_firewall_rules=$tpl->javascript_parse_text("{apply}");
	$IPSET_INSTALLED=0;
	
	$sql="CREATE TABLE IF NOT EXISTS `ss5_fw` (
		`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`zorder` INT( 5 ) NOT NULL,
		`mode` smallint(1) NOT NULL DEFAULT 0,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		`src_host` VARCHAR(128),
		`src_port` BIGINT UNSIGNED,
		`dst_host` VARCHAR(128),
		`dst_port` BIGINT UNSIGNED,
		`fixup` varchar(20) NULL,
		`group` VARCHAR(128),
		`bandwitdh` BIGINT UNSIGNED,
		`expdate` VARCHAR(40) NULL,
		KEY `zorder` (`zorder`),
		KEY `mode` (`mode`),
		KEY `enabled` (`enabled`),
		KEY `src_host` (`src_host`),
		KEY `dst_host` (`dst_host`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	

	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;

	
	$allowdeny=$tpl->javascript_parse_text("{allow}/{deny}");
	$source=$tpl->javascript_parse_text("{source}");
	$destination=$tpl->javascript_parse_text("{destination}");
	$bandwitdh=$tpl->javascript_parse_text("{bandwitdh}");
	$t=time();
	
	$title=$tpl->javascript_parse_text("{APP_SS5} {rules}");

	$buttons="
	buttons : [
	
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'Add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply_firewall_rules</strong>', bclass: 'Apply', onpress : FW$t},
	
	],	";
	$html="
	<table class='SS5_FW_RULES' style='display: none' id='SS5_FW_RULES' style='width:99%'></table>
	<script>
	var IptableRow='';
	$(document).ready(function(){
	$('#SS5_FW_RULES').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>&nbsp;</span>', name : 'zorder', width :50, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>&nbsp;</span>', name : 'aaa', width :50, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$allowdeny</span>', name : 'mode', width :134, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$source</span>', name : 'src_host', width :190, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$destination</span>', name : 'dst_host', width :190, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'bandwitdh', width :480, sortable : true, align: 'right'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
	{display: '$source', name : 'src_host'},
	{display: '$destination', name : 'dst_host'},
	],

	sortname: 'zorder',
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

function block_countries(){
	var IPSET_INSTALLED=$IPSET_INSTALLED;
	if(IPSET_INSTALLED==0){alert('$ERROR_IPSET_NOT_INSTALLED');return;}
	Loadjs('system.ipblock.php')
}

function current_rules(){
Loadjs('system.iptables.save.php');
}

function FW$t(){
	Loadjs('ss5.progress.php');
}
	

var x_EmptyRules= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
IpTablesInboundRuleResfresh();
}

function EmptyRules(){
if(confirm('$empty_all_firewall_rules ?')){
var XHR = new XHRConnection();
XHR.appendData('EmptyAll','yes');
XHR.sendAndLoad('$page', 'POST',x_EmptyRules);
}
}

function NewRule$t(){
	Loadjs('$page?id-js=0');
}

function IpTablesInboundRuleResfresh(){
$('#table-$t').flexReload();
}

function AllowRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=1' }).flexReload();
}
function BannedRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=0' }).flexReload();
}

var x_IptableDelete= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+IptableRow).remove();

}

function options$t(){
Loadjs('$page?options=yes&table=table-$t',true);
}

function IptableDelete(key){
IptableRow=key;
var XHR = new XHRConnection();
XHR.appendData('DeleteIptableRule',key);
XHR.sendAndLoad('$page', 'POST',x_IptableDelete);
}

var x_FirewallDisableRUle= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}
}

function iptables_edit_rules(num){
YahooWin5('800','$page?edit_rule=yes&t=$t&rulemd5='+num,'$rule');

}


function FirewallDisableRUle(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableFwRule',0);}else{XHR.appendData('EnableFwRule',1);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);
}

function EnableLog(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableLog',1);}else{XHR.appendData('EnableLog',0);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);

}

</script>";

	echo $html;
}

function ID_POPUP(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	$button_name="{add}";
	
	if($ID>0){
		$button_name="{apply}";
		$sql="SELECT * FROM ss5_fw WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	}else{
		$ligne["fixup"]="-";
		$ligne["mode"]=1;
		$ligne["enabled"]=1;
		$ligne["zorder"]=0;
		$ligne["src_host"]="0.0.0.0/0";
		$ligne["dst_host"]="0.0.0.0/0";
		$ligne["src_port"]=0;
		$ligne["dst_port"]=0;
	}
	$groupmode[0]="{deny}";
	$groupmode[1]="{allow}";
	
	
	
	$fixup["-"]="{all}";
	$fixup["http"]="http";
	$fixup["https"]="https";
	$fixup["smtp"]="smtp";
	$fixup["pop3"]="pop3";
	$fixup["imap4"]="imap4";
	
	

	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td style='font-size:22px'>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{order}:</td>
		<td style='font-size:16px'>". Field_text("zorder-$t",$ligne["zorder"],"font-size:22px;width:90px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{mode}:</td>
		<td style='font-size:22px'>". Field_array_Hash($groupmode,"mode-$t",$ligne["groupmode"],
				"style:font-size:22px;")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{source}:</td>
		<td style='font-size:18px'>". Field_text("src_host-$t",$ligne["src_host"],"font-size:22px;width:98%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{source_port}:</td>
		<td style='font-size:22px'>". Field_text("src_port-$t",$ligne["src_port"],"font-size:22px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{destination}:</td>
		<td style='font-size:22px'>". Field_text("dst_host-$t",$ligne["dst_host"],"font-size:22px;width:98%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{destination_port}:</td>
		<td style='font-size:22px'>". Field_text("dst_port-$t",$ligne["dst_port"],"font-size:22px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{destination_protocol}:</td>
		<td style='font-size:16px'>". Field_array_Hash($fixup,"fixup-$t",$ligne["fixup"],
				"style:font-size:22px;")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{bandwitdh}:</td>
		<td style='font-size:18px'>". Field_text("bandwitdh-$t",$ligne["bandwitdh"],"font-size:22px;width:150px")."&nbsp;bytes/s</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{expire}:</td>
		<td style='font-size:22px'>". Field_text("expdate-$t",$ligne["expdate"],"font-size:22px;width:150px")."&nbsp;Date (DD-MM-YYYY)</td>
	</tr>				
				
	<tr>
		<td colspan=3 align='right'><hr>". button($button_name,"Save$t()",30)."</td>
	</tr>
	</tbody>
	</table>
		</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	var ID='$ID';
	if (res.length>3){alert(res);return;}
	if(ID==0){YahooWinHide();}
	$('#SS5_FW_RULES').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('enabled-$t').checked){ XHR.appendData('enabled',1);}else{ XHR.appendData('enabled',0);}
	XHR.appendData('zorder', document.getElementById('zorder-$t').value);
	XHR.appendData('mode', document.getElementById('mode-$t').value);
	XHR.appendData('src_host', document.getElementById('src_host-$t').value);
	XHR.appendData('src_port', document.getElementById('src_port-$t').value);
	
	XHR.appendData('dst_host', document.getElementById('dst_host-$t').value);
	XHR.appendData('dst_port', document.getElementById('dst_port-$t').value);
	
	XHR.appendData('fixup', document.getElementById('fixup-$t').value);
	XHR.appendData('bandwitdh', document.getElementById('bandwitdh-$t').value);
	XHR.appendData('expdate', document.getElementById('expdate-$t').value);
	XHR.appendData('ID','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function SaveRule(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	while (list ($field, $value) = each ($_POST) ){
		
		$addF[]="`$field`";
		$addV[]="'$value'";
		$editF[]="`$field`='$value'";
	}
		
	$insert="INSERT IGNORE INTO `ss5_fw` (".@implode(",", $addF).") VALUES (".@implode(",", $addV).")";
	$edit="UPDATE `ss5_fw` SET ".@implode(",", $editF)." WHERE ID='$ID'";
	$q=new mysql_squid_builder();
	
	

	$sql="CREATE TABLE IF NOT EXISTS `ss5_fw` (
		`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`zorder` INT( 5 ) NOT NULL,
		`mode` smallint(1) NOT NULL DEFAULT 0,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		`src_host` VARCHAR(128),
		`src_port` BIGINT UNSIGNED,
		`dst_host` VARCHAR(128),
		`dst_port` BIGINT UNSIGNED,
		`fixup` varchar(20) NULL,
		`group` VARCHAR(128),
		`bandwitdh` BIGINT UNSIGNED,
		`expdate` VARCHAR(40) NULL,
		KEY `zorder` (`zorder`),
		KEY `mode` (`mode`),
		KEY `enabled` (`enabled`),
		KEY `src_host` (`src_host`),
		KEY `dst_host` (`dst_host`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	
	
	if($ID==0){$sql=$insert;$q->QUERY_SQL($insert);}else{$sql=$edit;$q->QUERY_SQL($edit);}
	
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
		
}



function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$database="squidlogs";
	$search='%';
	$table="ss5_fw";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}

	
	$searchstring=string_to_flexquery();
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];

	if(!isset($_POST['rp'])){$_POST['rp']=50;}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table  WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;
	
	
	$allow_type[1]=$tpl->javascript_parse_text("{allow}");
	$allow_type[0]=$tpl->javascript_parse_text("{deny}");
	
	$allow_typeS[1]="arrow-right-24.png";
	$allow_typeS[0]="arrow-right-24-red.png";
	$all=$tpl->javascript_parse_text("{all}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$service=$ligne["service"];
		$allow_typez=$ligne["allow_type"];
		$netzone=$ligne["netzone"];
		$zmd5=$ligne["zmd5"];
		$icon=$allow_type[$allow_typez];
		$img=$allow_typeS[$ligne["mode"]];
		$color="black";
		
		
		if($ligne["enabled"]==0){$color="#8a8a8a";$img="arrow-right-24-grey.png";}
			
		
		
		$link="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?id-js={$ligne["ID"]}');\"
		style='text-decoration:underline;color:$color'>";
		
		
		$allow=imgsimple($icon,null,"Loadjs('$MyPage?revert-js=yes&zmd5=$zmd5&nic={$_GET["nic"]}&xtable={$_GET["xtable"]}')");
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}')");
		
		/*{display: '<span style=font-size:18px>&nbsp;</span>', name : 'zorder', width :50, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$allowdeny</span>', name : 'mode', width :70, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$source</span>', name : 'src_host', width :70, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$destination</span>', name : 'dst_host', width :70, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'bandwitdh', width :70, sortable : true, align: 'left'},
	*/
		
		if($ligne["mode"]==0){$ligne["bandwitdh"]=0;}
		if($ligne["bandwitdh"]==0){$ligne["bandwitdh"]="-";}
		if($ligne["bandwitdh"]>0){
			$bytes=$ligne["bandwitdh"]." bytes/s";
			$bits=$ligne["bandwitdh"]*8;
			$kbs=$bits/1000;
			$Mbps=round($kbs/1000,2);
			$kbs=round($kbs,2);
			$ligne["bandwitdh"]="$bytes - {$kbs}kb/s - {$Mbps}Mb/s";
		}
		
		if($ligne["src_host"]==null){$ligne["src_host"]="$all";}
		if($ligne["dst_host"]==null){$ligne["dst_host"]="$all";}
		if($ligne["src_host"]=="0.0.0.0"){$ligne["src_host"]="$all";}
		if($ligne["dst_host"]=="0.0.0.0"){$ligne["dst_host"]="$all";}
		if($ligne["src_host"]=="0.0.0.0/0"){$ligne["src_host"]="$all";}
		if($ligne["dst_host"]=="0.0.0.0/0"){$ligne["dst_host"]="$all";}
		
		if($ligne["src_port"]==0){$ligne["src_port"]="$all";}
		if($ligne["dst_port"]==0){$ligne["dst_port"]="$all";}
		
		$data['rows'][] = array(
				'id' => $service,
				'cell' => array(
						"<span style='font-size:22px'>{$ligne["zorder"]}</a></span>",
						"<center style='font-size:18px'><img src='img/$img'></center>",
						"<span style='font-size:22px'>$link{$allow_type[$ligne["mode"]]}</a></span>",
						"<span style='font-size:22px'>$link{$ligne["src_host"]}:{$ligne["src_port"]}</a></span>",
						"<span style='font-size:22px'>$link{$ligne["dst_host"]}:{$ligne["dst_port"]}</a></span>",
						"<span style='font-size:22px'>{$ligne["bandwitdh"]}</span>",
						"<center style='font-size:18px'>$delete</center>",

							

				)
		);
	}


	echo json_encode($data);

}