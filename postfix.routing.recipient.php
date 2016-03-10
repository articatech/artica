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
main_table();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{recipient_relay_table}");
	$html="YahooWin3('850','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','{$_GET["hostname"]}&raquo;$title')";
	echo $html;
	
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
	$recipients=$tpl->_ENGINE_parse_body("{recipients}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$about2=$tpl->javascript_parse_text("{about2}");
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"{$_GET["ou"]}\",\"new domain\")","add_relay_domain",210);
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$add_routing_relay_recipient_rule</strong>', bclass: 'add', onpress : TransPortRecipt1$t},
	{name: '<strong style=font-size:18px>$import</strong>', bclass: 'add', onpress : TransPortReciptImport$t},
	{name: '<strong style=font-size:18px>$build_parameters</strong>', bclass: 'Reconf', onpress :Build$t},
	{name: '<strong style=font-size:18px>$about2</strong>', bclass: 'help', onpress : Help$t},
	],";		
$explain=$tpl->javascript_parse_text("{routing_recipient_table_explain}");
	
$html="
<input type='hidden' id='ou' value='{$_GET["ou"]}'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var memid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$recipients</span>', name : 'recipient', width : 485, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$relay</span>', name : 'transport', width :485, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>&nbsp;</span>', name : 'enable', width :140, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 140, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$recipients', name : 'recipient'},
		{display: '$relay', name : 'transport'},
		],
	sortname: 'recipient',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function Help$t(){
	alert('$explain');
}

	function TransPortRecipt1$t(){

		YahooWin4('550','$page?recipient=&t=$t&hostname=$hostname&ou={$_GET["ou"]}','$add_routing_relay_recipient_rule');
	}
	
	var X_Build$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
	}		
	
	function Build$t(){
		var XHR = new XHRConnection();
		XHR.appendData('build-params',1);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_Build$t);		
	}

	function TransPortRecipt$t(recipient){
		if(!recipient){recipient='';}
		YahooWin4('550','$page?recipient='+recipient+'&t=$t&hostname=$hostname&ou={$_GET["ou"]}',recipient);
	}

var X_TransPortReciptDel$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#row'+memid$t).remove();
	}	
	
var X_TransPortReciptEnable$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		
	}

	function TransPortReciptImport$t(){
		YahooWin4('550','$page?import-popup=yes&t=$t&hostname=$hostname&ou={$_GET["ou"]}','$import');
	
	}
	

	
	function TransPortReciptEnable$t(md,recipient){
		var XHR = new XHRConnection();
		XHR.appendData('recipient-enable',recipient);
		if(document.getElementById(md+'-enabled').checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}	
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_TransPortReciptEnable$t);		
		}	
	
	function TransPortReciptDel$t(recipient,md){
		memid$t=md;
		var XHR = new XHRConnection();
		XHR.appendData('recipient-delete',recipient);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'POST',X_TransPortReciptDel$t);		
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
	$sock->getFrameWork("cmd.php?postfix-transport-maps=yes&hostname={$_POST["hostname"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{cyrreconstruct_wait}",1);
}

function recipient_delete(){
	$sql="DELETE FROM postfix_transport_recipients WHERE `recipient`='{$_POST["recipient-delete"]}' AND hostname='{$_POST["hostname"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	_admin_event("{$_POST["recipient-delete"]} was deleted from postfix_transport_recipients table...",__FUNCTION__,__FILE__,__LINE__);
	
}

function recipient_enable(){
	$sql="UPDATE postfix_transport_recipients SET enabled='{$_POST["value"]}' WHERE `recipient`='{$_POST["recipient-enable"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;	}	
}

function recipient_save(){
	$tpl=new templates();
	$q=new mysql();
	if($_POST["recipient-save"]==null){return;}
	if($_POST["relay_address"]==null){echo $tpl->_ENGINE_parse_body('{error_no_server_specified}');exit;}
	$sql="SELECT recipient,hostname FROM postfix_transport_recipients WHERE `recipient`='{$_POST["recipient-save"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	if($_POST["MX_lookups"]==1){$_POST["MX_lookups"]="yes";}else{$_POST["MX_lookups"]="no";}
	
	if(trim($ligne["hostname"])<>null){
		if(trim($ligne["hostname"])<>trim($_POST["hostname"])){
			echo $tpl->javascript_parse_text("{already_used_byinstance}:".$ligne["hostname"]);
			return;
		}
	}
	
	
	$domaintools=new DomainsTools();
	
	$transport=$domaintools->transport_maps_implode($_POST["relay_address"],$_POST["port"],"smtp",$_POST["MX_lookups"]);
	
	$sql="INSERT IGNORE INTO postfix_transport_recipients (recipient,transport,enabled,hostname) VALUES 
	('{$_POST["recipient-save"]}','$transport',1,'{$_POST["hostname"]}')";
	
	if($ligne["recipient"]<>null){
		$sql="UPDATE postfix_transport_recipients SET transport='$transport' WHERE `recipient`='{$_POST["recipient-save"]}'";
		
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
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
$button="{add}";	
$page=CurrentPageName();
$ldap=new clladp();
$tt=$_GET["t"];
$t=time();
if($_GET["ou"]==null){$_GET["ou"]="master";}

$emailtr="	<tr>
	<td align='right' nowrap class=legend style='font-size:16px'>{email}:</strong></td>
	<td style='font-size:16px'>" . Field_text("email-$t",$email,"font-size:16px;width:240px") . "</td>
	<td>". help_icon("{transport_email_explain}")."</td>	
	</tr>	";

if($_GET["recipient"]<>null){
	$q=new mysql();
	$sql="SELECT * FROM postfix_transport_recipients WHERE `recipient`='{$_GET["recipient"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$domaintools=new DomainsTools();
	
	$table=$domaintools->transport_maps_explode($ligne["transport"]);
	$relay_address=$table[1];
	$port=$table[2];
	$MX_lookups=$table[3];
	$button="{apply}";
	$emailtr="
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{email}:</strong></td>
		<td style='font-size:16px'>{$_GET["recipient"]}". Field_hidden("email-$t", $_GET["recipient"])."</td>	
		<td>&nbsp;</td>
	</tr>	
	";
	
}
	
if(!is_numeric($port)){$port=25;}
	$html="
	<table style='width:99%' class=form>
	$emailtr	
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
		<td align='right' colspan=3>". button($button,"PostfixAddRecipientTable$t()","18px")."</td>
	</tr>		
	</table>
	<script>
	
		var X_PostfixAddRecipientTable$t= function (obj) {
				var results=obj.responseText;
				if (results.length>0){alert(results);}
				YahooWin4Hide();
				$('#flexRT$tt').flexReload();
			}		
		function PostfixAddRecipientTable$t(){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('recipient-save',document.getElementById('email-$t').value);
				XHR.appendData('relay_address',document.getElementById('relay_address-$t').value);
				XHR.appendData('port',document.getElementById('relay_port-$t').value);
				if(document.getElementById('MX_lookups-$t').checked){XHR.appendData('MX_lookups',1);}else{XHR.appendData('MX_lookups',0);}
				XHR.sendAndLoad('$page', 'POST',X_PostfixAddRecipientTable$t);
				
			}	
</script>	
	
	
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}


function table_list(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_transport_recipients", "artica_backup")){$q->BuildTables();}
	$table="postfix_transport_recipients";
	$t=$_GET["t"];
	$database="artica_backup";
	$FORCE_FILTER=" hostname='{$_GET["hostname"]}'";
	
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
	if(mysql_num_rows($results)==0){json_error_show("No rules...",1);}
	$tools=new DomainsTools();
	$style="font-size:14px;";
	while ($ligne = mysql_fetch_assoc($results)) {
		
			$transport=$ligne["transport"];
			$arr=$tools->transport_maps_explode($transport);
			$relay="{$arr[1]}:{$arr[2]}";
			
			$js="TransPortRecipt$t('{$ligne["recipient"]}')";
			$md=md5(serialize($ligne));
			$cells=array();
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:$js;\" style='font-size:22px;font-weight:bold'>{$ligne["recipient"]}</a>";
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:$js;\" style='font-size:22px;font-weight:bold'>$relay</a>";
			$cells[]="<center>".Field_checkbox("$md-enabled", 1,$ligne["enabled"],"TransPortReciptEnable$t('$md','{$ligne["recipient"]}')")."</center>";
			$cells[]="<center>".imgsimple("delete-32.png",null,"TransPortReciptDel$t('{$ligne["recipient"]}','$md')")."</center>";
			
			
			
			$data['rows'][] = array(
				'id' =>$md,
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
}	