<?php
ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["table-list"])){table_list();exit;}
	if(isset($_GET["recipient"])){recipient_popup();exit;}
	if(isset($_POST["recipient-save"])){recipient_save();exit;}
	if(isset($_POST["recipient-delete"])){recipient_delete();exit;}
	if(isset($_POST["recipient-enable"])){recipient_enable();exit;}
	if(isset($_POST["build-params"])){build_params();exit;}
	if(isset($_GET["import-popup"])){import_popup();exit;}
	if(isset($_POST["recipient-import"])){import_save();exit;}
	if(isset($_GET["recipient-main"])){recipient_popup_main();exit;}
	if(isset($_GET["recipient-list"])){recipient_popup_main_list();exit;}
	if(isset($_GET["recipient-list-table"])){recipient_popup_main_table();exit;}
	
	if(isset($_POST["item-save"])){item_save();exit;}
	if(isset($_POST["item-delete"])){item_delete();exit;}
	if(isset($_POST["item-enable"])){item_enable();exit;}
	if(isset($_GET["items-import-popup"])){item_import_popup();exit;}
	if(isset($_POST["item-import"])){item_import();exit;}
	
main_table();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{diffusion_list}");
	$html="YahooWin3('850','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','{$_GET["hostname"]}&raquo;$title')";
	echo $html;
	
}

function recipient_popup_main_list(){
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();

	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$title=$tpl->javascript_parse_text("{diffusion_lists}");
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : NewDiffListItem$t},
	{name: '<strong style=font-size:18px>$import</strong>', bclass: 'Reconf', onpress :NewDiffListItemImport$t},
	],";		

	
	
$html="

<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>

	
<script>
var memid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?recipient-list-table=yes&mainlist={$_GET["list"]}&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:20px>$items</span>', name : 'recipient', width : 450, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'enable', width :31, sortable : true, align: 'center'},
		{display: '<span style=font-size:20px>$delete</span>', name : 'delete', width : 120, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$items', name : 'recipient'},
		],
	sortname: 'recipient',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:20px>{$_GET["list"]}</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function NewDiffListItemImport$t(){
		YahooWin5('890','$page?items-import-popup=yes&t=$t&hostname=$hostname&ou={$_GET["ou"]}&mainlist={$_GET["list"]}&tt=$tt','$import');
	}
	
	



var X_DiffusionListItemDel$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#row'+memid$t).remove();
		$('#flexRT$tt').flexReload();
	}	
	
var X_NewDiffListItem$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
		$('#flexRT$tt').flexReload();
		
	}
	
var X_DiffusionListItemEnable$t=function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		
	}

	function NewDiffListItem$t(){
		var email=prompt('eMail:');
		if(email){
			var XHR = new XHRConnection();
			XHR.appendData('item-save',email);
			XHR.appendData('item-list','{$_GET["list"]}');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');		
			XHR.sendAndLoad('$page', 'POST',X_NewDiffListItem$t);
			}
		}
	

	
	function DiffusionListItemEnable$t(md,recipient){
		var XHR = new XHRConnection();
		XHR.appendData('item-enable',recipient);
		if(document.getElementById(md+'-enabled').checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}	
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('item-list','{$_GET["list"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_DiffusionListItemEnable$t);		
		}	
	
	function DiffusionListItemDel$t(recipient,md){
		memid$t=md;
		var XHR = new XHRConnection();
		XHR.appendData('item-delete',recipient);
		XHR.appendData('item-list','{$_GET["list"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_DiffusionListItemDel$t);		
		}		
	
</script>
";
	
	echo $html;	
	
	
}

function item_save(){
	$hostname=$_POST["hostname"];
	$sql="INSERT IGNORE INTO postfix_diffusion_list (`recipient`,`mainlist`,`enabled`) VALUES ('{$_POST["item-save"]}','{$_POST["item-list"]}',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function item_delete(){
	$hostname=$_POST["hostname"];
	$sql="DELETE FROM postfix_diffusion_list WHERE `recipient`='{$_POST["item-delete"]}' AND `mainlist`='{$_POST["item-list"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
}

function item_enable(){
	$sql="UPDATE postfix_diffusion_list SET enabled={$_POST["value"]} WHERE `recipient`='{$_POST["item-enable"]}' AND `mainlist`='{$_POST["item-list"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		

	
}

function item_import(){

	$t=explode("\n", $_POST["item-import"]);
	
	$tpl=new templates();
	$q=new mysql();	
	if($_POST["item-import"]==null){return;}
	
	
	$prefix="INSERT IGNORE INTO postfix_diffusion_list (recipient,mainlist,enabled) VALUES ";
	$tr=explode("\n",$_POST["item-import"]);
	while (list ($num, $email) = each ($tr) ){
		$email=trim($email);
		$email=str_replace("\r", "", $email);
		$email=str_replace("\n", "", $email);
		if($email==null){continue;}
		$f[]="('$email','{$_POST["item-list"]}',1)";
	}
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		echo $tpl->javascript_parse_text(count($f)." {new_items}");
	}	
}


function main_table(){
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$destination=$tpl->javascript_parse_text("{destination}");
	$add_routing_relay_recipient_rule=$tpl->javascript_parse_text("{add_routing_relay_recipient_rule}");
	$lists=$tpl->_ENGINE_parse_body("{lists}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"$ou\",\"new domain\")","add_relay_domain",210);
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$new_diffusion_list=$tpl->javascript_parse_text("{new_diffusion_list}");
	$title=$tpl->javascript_parse_text("{diffusion_lists}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_diffusion_list</strong>', bclass: 'add', onpress : NewDiffList$t},
	{name: '<strong style=font-size:18px>$build_parameters</strong>', bclass: 'Reconf', onpress :Build2$t},
	],";		


$html="
<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var memid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$lists</span>', name : 'recipient', width : 639, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$items</span>', name : 'transport', width :156, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>&nbsp;</span>', name : 'enable', width :31, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 156, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$lists', name : 'recipient'},
		],
	sortname: 'recipient',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function NewDiffList$t(){
		var email=prompt('$new_diffusion_list:');
		if(email){
			var XHR = new XHRConnection();
			XHR.appendData('recipient-save',email);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');		
			XHR.sendAndLoad('$page', 'POST',X_Build2$t);
			}
	}
	
	var X_Build2$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
	}		
	
	function Build2$t(){
		var XHR = new XHRConnection();
		XHR.appendData('build-params',1);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_Build2$t);		
	}

	function DiffusionList$t(recipient){
		if(!recipient){recipient='';}
		YahooWin4('890','$page?recipient='+recipient+'&t=$t&hostname=$hostname&ou={$_GET["ou"]}',recipient);
	}

	var X_DiffusionListDel$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#row'+memid$t).remove();
	}	
	
	var X_DiffusionListEnable$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		
	}

	function TransPortReciptImport$t(){
		YahooWin4('550','$page?import-popup=yes&t=$t&hostname=$hostname&ou={$_GET["ou"]}','$import');
	
	}
	

	
	function DiffusionListEnable$t(md,recipient){
		var XHR = new XHRConnection();
		XHR.appendData('recipient-enable',recipient);
		if(document.getElementById(md+'-enabled').checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}	
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_DiffusionListEnable$t);		
		}	
	
	function DiffusionListDel$t(recipient,md){
		memid$t=md;
		var XHR = new XHRConnection();
		XHR.appendData('recipient-delete',recipient);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_DiffusionListDel$t);		
		}		
	
</script>
";
	
	echo $html;
			
	
	
}

function import_save(){
	$tpl=new templates();
	$q=new mysql();	
	if($_POST["recipient-import"]==null){return;}
	if($_POST["relay_address"]==null){echo $tpl->_ENGINE_parse_body('{error_no_server_specified}');exit;}
	$domaintools=new DomainsTools();
	$transport=$domaintools->transport_maps_implode($_POST["relay_address"],$_POST["port"],"smtp",$_POST["MX_lookups"]);	
	
	$prefix="INSERT IGNORE INTO postfix_transport_recipients (recipient,transport,enabled,hostname) VALUES ";
	$tr=explode("\n",$_POST["recipient-import"]);
	while (list ($num, $email) = each ($tr) ){
		$email=trim($email);
		$email=str_replace("\r", "", $email);
		$email=str_replace("\n", "", $email);
		if($email==null){continue;}
		$f[]="('$email','$transport',1,'{$_POST["hostname"]}')";
	}
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		echo $tpl->javascript_parse_text(count($f)." {new_items}");
	}
	
	
}

function build_params(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-hash-aliases=yes&hostname={$_POST["hostname"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{cyrreconstruct_wait}",1);
}

function recipient_delete(){
	
	$sql="DELETE FROM postfix_diffusion_list WHERE `mainlist`='{$_POST["recipient-delete"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$sql="DELETE FROM postfix_diffusion WHERE `recipient`='{$_POST["recipient-delete"]}' AND hostname='{$_POST["hostname"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	_admin_event("{$_POST["recipient-delete"]} diffusion list was deleted from postfix_diffusion table...",__FUNCTION__,__FILE__,__LINE__);
	
}

function recipient_enable(){
	$sql="UPDATE postfix_diffusion SET enabled='{$_POST["value"]}' WHERE `recipient`='{$_POST["recipient-enable"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;	}	
}

function recipient_save(){
	$tpl=new templates();
	$q=new mysql();
	if($_POST["recipient-save"]==null){return;}
	
	
	
	$sql="INSERT IGNORE INTO postfix_diffusion (recipient,enabled,hostname) VALUES 
	('{$_POST["recipient-save"]}',1,'{$_POST["hostname"]}')";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function item_import_popup(){
	$button="{import}";	
	$page=CurrentPageName();
	$ldap=new clladp();
	$tt=$_GET["t"];
	$ttt=$_GET["tt"];
	$t=time();
	if($_GET["ou"]==null){$_GET["ou"]="master";}	
	
	$html="
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=3 class=legend style='font-size:16px'>{recipients}: ({separated_by_acarriage_return})</td>
	</tr>
	<tr>
		<td colspan=3><textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;
		border:5px solid #8E8E8E;overflow:auto;font-size:16px' id='textToParse$t'></textarea></td>
	</tr>		
	
	<tr>
		<td align='right' colspan=3>". button($button,"DiffImportRecipientTable$t()","18px")."</td>
	</tr>		
	</table>
	<script>
	
		var X_DiffImportRecipientTable$t= function (obj) {
				var results=obj.responseText;
				document.getElementById('div-$t').innerHTML='';
				$('#flexRT$tt').flexReload();
				$('#flexRT$ttt').flexReload();
				
				if (results.length>0){alert(results);}
		}		
		function DiffImportRecipientTable$t(){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('item-import',document.getElementById('textToParse$t').value);
				XHR.appendData('item-list','{$_GET["mainlist"]}');
				AnimateDiv('div-$t');
				XHR.sendAndLoad('$page', 'POST',X_DiffImportRecipientTable$t);
				
			}	
</script>	
	
	
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


function import_popup(){
	$button="{import}";	
	$page=CurrentPageName();
	$ldap=new clladp();
	$tt=$_GET["t"];
	$t=time();
	if($_GET["ou"]==null){$_GET["ou"]="master";}	
	if(!is_numeric($port)){$port=25;}
	$html="
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=3 class=legend style='font-size:16px'>{recipients}: ({separated_by_acarriage_return})</td>
	</tr>
	<tr>
		<td colspan=3><textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;
		border:5px solid #8E8E8E;overflow:auto;font-size:16px' id='textToParse$t'>$website_default</textarea></td>
	</tr>		
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{relay_address}:</strong></td>
		<td style='font-size:16px'>" . Field_text("relay_address-$t",$relay_address,"font-size:16px;width:240px") . "</td>	
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{port}:</strong></td>
		<td style='font-size:16px'>" . Field_text("relay_port-$t",$port,"font-size:16px;width:90px") . "</td>
		<td>&nbsp;</td>	
	</tr>	
	<tr>
		<td style='font-size:16px' class=legend>{MX_lookups}</td>
		<td align='left' nowrap style='font-size:16px'>" . Field_checkbox("MX_lookups-$t",1,$MX_lookups)."</td>	
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align='right' colspan=3>". button($button,"PostfixImportRecipientTable$t()","18px")."</td>
	</tr>		
	</table>
	<script>
	
		var X_PostfixImportRecipientTable$t= function (obj) {
				var results=obj.responseText;
				document.getElementById('div-$t').innerHTML='';
				$('#flexRT$tt').flexReload();
				if (results.length>0){alert(results);}
				
				
			}		
		function PostfixImportRecipientTable$t(){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('recipient-import',document.getElementById('textToParse$t').value);
				XHR.appendData('relay_address',document.getElementById('relay_address-$t').value);
				XHR.appendData('port',document.getElementById('relay_port-$t').value);
				if(document.getElementById('MX_lookups-$t').checked){XHR.appendData('MX_lookups',1);}else{XHR.appendData('MX_lookups',0);}
				AnimateDiv('div-$t');
				XHR.sendAndLoad('$page', 'POST',X_PostfixImportRecipientTable$t);
				
			}	
</script>	
	
	
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}


function recipient_popup(){
	
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$_GET["hostname"];
	if($hostname==null){$hostname="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}
	$page=CurrentPageName();
	$title="{new_list}";
	
	
	if($_GET["recipient"]<>null){
		$title=$_GET["recipient"];
		$array["recipient-list"]=$title;
	}
	$height="600px";
	$fontsize="font-size:16px;";
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$height="100%";}
	
	
	
	

	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&list={$_GET["recipient"]}&hostname=$hostname&ou={$_GET["ou"]}&t={$_GET["t"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_diffusion_list");

	
	
	
}





function recipient_popup_main_table(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_diffusion_list", "artica_backup")){$q->BuildTables();}
	$table="postfix_diffusion_list";
	$t=$_GET["t"];
	$database="artica_backup";
	$FORCE_FILTER=" mainlist='{$_GET["mainlist"]}'";
	if(!$q->TABLE_EXISTS("postfix_diffusion_list", "artica_backup")){json_error_show("!Error: postfix_diffusion_list No such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}

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
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $search");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	$forwarded_to=$tpl->_ENGINE_parse_body("{forwarded_to}");
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rules...",1);}
	
	$style="font-size:14px;";
	while ($ligne = mysql_fetch_assoc($results)) {
		
			$forwarded=null;
			$sql="SELECT transport FROM postfix_transport_recipients WHERE `recipient`='{$ligne["recipient"]}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
			$transport=trim($ligne2["transport"]);	
			if($transport<>null){$forwarded="<div style='font-size:11px'>$forwarded_to:$transport</div";}
			
			
			$md=md5(serialize($ligne));
			$cells=array();
			$cells[]="<span style='font-size:22px;font-weight:bold'>{$ligne["recipient"]}</a>$forwarded";
			$cells[]="<center>".Field_checkbox("$md-enabled", 1,$ligne["enabled"],"DiffusionListItemEnable$t('$md','{$ligne["recipient"]}')");
			$cells[]="<center>".imgsimple("delete-32.png",null,"DiffusionListItemDel$t('{$ligne["recipient"]}','$md')");
			
			
			
			$data['rows'][] = array(
				'id' =>$md,
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
	
}


function table_list(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_diffusion", "artica_backup")){$q->BuildTables();}
	$table="postfix_diffusion";
	$t=$_GET["t"];
	$database="artica_backup";
	$FORCE_FILTER=" hostname='{$_GET["hostname"]}'";
	if(!$q->TABLE_EXISTS("postfix_diffusion", "artica_backup")){json_error_show("!Error: postfix_diffusion No such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table is empty");}

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
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $search");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No item...",1);}
	$forwarded_to=$tpl->_ENGINE_parse_body("{forwarded_to}");
	$style="font-size:14px;";
	while ($ligne = mysql_fetch_assoc($results)) {
		
						
			$sql="SELECT COUNT(*) as tcount FROM postfix_diffusion_list WHERE `mainlist`='{$ligne["recipient"]}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
			$total=$ligne2["tcount"];
			
			$forwarded=null;
			$sql="SELECT transport FROM postfix_transport_recipients WHERE `recipient`='{$ligne["recipient"]}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
			$transport=trim($ligne2["transport"]);	
			if($transport<>null){$forwarded="<div style='font-size:11px'>$forwarded_to:$transport</div>";}	
			
			
			$js="DiffusionList$t('{$ligne["recipient"]}')";
			$md=md5(serialize($ligne));
			$cells=array();
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:$js;\" style='font-size:22px;font-weight:bold;text-decoration:underline'>{$ligne["recipient"]}</a>$forwarded";
			$cells[]="<center style='font-size:22px;font-weight:bold;'>$total</center>";
			$cells[]="<center>".Field_checkbox("$md-enabled", 1,$ligne["enabled"],"DiffusionListEnable$t('$md','{$ligne["recipient"]}')")."</center>";
			$cells[]="<center>".imgsimple("delete-32.png",null,"DiffusionListDel$t('{$ligne["recipient"]}','$md')")."</center>";
			
			
			
			$data['rows'][] = array(
				'id' =>$md,
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
}	