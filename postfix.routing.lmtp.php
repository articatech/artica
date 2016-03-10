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
	if(isset($_GET["ID"])){recipient_popup();exit;}
	if(isset($_POST["uid-save"])){uid_save();exit;}
	if(isset($_POST["ID-delete"])){uid_delete();exit;}
	if(isset($_POST["recipient-enable"])){recipient_enable();exit;}
	if(isset($_POST["build-params"])){build_params();exit;}
	if(isset($_GET["import-popup"])){import_popup();exit;}
	if(isset($_POST["recipient-import"])){import_save();exit;}
	if(isset($_GET["change-field"])){change_field();exit;}
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
	$service=$tpl->javascript_parse_text("{service}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($_GET["ou"]);
	$destination=$tpl->javascript_parse_text("{destination}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$mailboxes=$tpl->_ENGINE_parse_body("{mailboxes}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"{$_GET["ou"]}\",\"new domain\")","add_relay_domain",210);
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$about2=$tpl->javascript_parse_text("{about2}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : TransPortRecipt1$t},
	{name: '<strong style=font-size:18px>$build_parameters</strong>', bclass: 'Reconf', onpress :Build$t},
	{name: '<strong style=font-size:18px>$about2</strong>', bclass: 'help', onpress : Help$t},
	],";		
$explain=$tpl->javascript_parse_text("{routing_lmtp_table_explain}");


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
		{display: '<span style=font-size:22px>$mailboxes</span>', name : 'uid', width : 435, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$service</span>', name : 'transport', width :435, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 135, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$mailboxes', name : 'uid'},
		{display: '$service', name : 'transport'},
		],
	sortname: 'uid',
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

function  Help$t(){
alert('$explain');
}

	function TransPortRecipt1$t(ID){
		if(!is_integer(ID)){ID=0;}
		var title='$new_rule';
		if(ID>0){title='$hostname:: R.'+ID;}
		YahooWin4('550','$page?ID='+ID+'&t=$t&hostname=$hostname&ou={$_GET["ou"]}',title);
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
	
	function TransPortReciptDel$t(ID){
		memid$t=ID;
		var XHR = new XHRConnection();
		XHR.appendData('ID-delete',ID);
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
	$sock->getFrameWork("postfix.php?mailbox-transport-maps=yes&hostname={$_GET["hostname"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{cyrreconstruct_wait}",1);
}

function uid_delete(){
	$ID=$_POST["ID-delete"];
	$sql="DELETE FROM postfix_transport_mailbox WHERE `ID`='$ID' AND hostname='{$_POST["hostname"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?mailbox-transport-maps=yes&hostname={$_POST["hostname"]}");	
	
	
}

function recipient_enable(){
	$sql="UPDATE postfix_transport_recipients SET enabled='{$_POST["value"]}' WHERE `recipient`='{$_POST["recipient-enable"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;	}	
}

function uid_save(){
	$tpl=new templates();
	$q=new mysql();
	if($_POST["uid-save"]==null){return;}
	if($_POST["lmtp_address"]==null){echo $tpl->_ENGINE_parse_body('{error_no_server_specified}');exit;}
	$ID=$_POST["ID"];
		
	if($ID==0){
	$sql="INSERT IGNORE INTO postfix_transport_mailbox (uid,xType,lmtp_address,hostname) VALUES 
	('{$_POST["uid-save"]}','{$_POST["xType"]}','{$_POST["lmtp_address"]}','{$_POST["hostname"]}')";
	}else{	
	$sql="UPDATE postfix_transport_mailbox 
	SET 
		uid='{$_POST["uid"]}',
		lmtp_address='{$_POST["lmtp_address"]}',
		xType='{$_POST["xType"]}'
		WHERE `ID`='{$_POST["ID"]}',
	";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?mailbox-transport-maps=yes&hostname={$_POST["hostname"]}");
	
}

function import_popup(){
	$button="{import}";	
	$page=CurrentPageName();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
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
				XHR.appendData('ID','{$_GET["ID"]}');
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
if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
$ldap=new clladp();
$tt=$_GET["t"];
$hostname=$_GET["hostname"];
$t=time();
if($_GET["ou"]==null){$_GET["ou"]="master";}

$chooses[0]="{mailbox}";
$chooses[1]="{organization}";





if($_GET["ID"]>0){
	$q=new mysql();
	$sql="SELECT * FROM postfix_transport_mailbox WHERE `ID`='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
	$button="{apply}";
	$xType=$ligne["xType"];
}
$choose=Field_array_Hash($chooses, "xType-$t",$xType,"ChangeField$t()",null,0,"font-size:16px");

	$html="
	<table style='width:99%' class=form>
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{type}:</strong></td>
		<td style='font-size:16px'>$choose</td>	
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{mailbox}:</strong></td>
		<td style='font-size:16px'><span id='field$t'></span></td>	
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td align='right' nowrap class=legend style='font-size:16px'>{lmtp_address}:</strong></td>
		<td style='font-size:16px'>" . Field_text("lmtp_address-$t",$ligne["lmtp_address"],"font-size:16px;width:240px") . "</td>	
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align='right' colspan=3><hr>". button($button,"PostfixAddRecipientTable$t()","18px")."</td>
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
				XHR.appendData('ID','{$_GET["ID"]}');
				XHR.appendData('uid-save',document.getElementById('uid-$t').value);
				XHR.appendData('xType',document.getElementById('xType-$t').value);
				XHR.appendData('lmtp_address',document.getElementById('lmtp_address-$t').value);
				XHR.sendAndLoad('$page', 'POST',X_PostfixAddRecipientTable$t);
				
			}	
			
		function  ChangeField$t(){
			var ztype=document.getElementById('xType-$t').value
			LoadAjaxTiny('field$t','$page?change-field=yes&key={$_GET["ID"]}&t=$t&hostname=$hostname&type='+ztype);
		
		}
		
		 ChangeField$t();
</script>	
	
	
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}
function change_field(){
	$type=$_GET["type"];
	$t=$_GET["t"];
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT uid FROM postfix_transport_mailbox WHERE `ID`='{$_POST["key"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
	
	if($type==0){
		echo Field_text("uid-$t",$ligne["uid"],"font-size:16px;width:180px");
		return;
	}
	
	$ldap=new clladp();
	$users=new usersMenus();
	if($users->AsSystemAdministrator){
		$ous=$ldap->hash_get_ou(true);
		$ous[null]="{select}";
		echo $tpl->_ENGINE_parse_body(Field_array_Hash($ous, "uid-$t",$ligne["uid"],"blur()",null,0,"font-size:16px"));
		return;
	}
	
	echo Field_hidden("uid-$t",$_SESSION["ou"])."<span style='font-size:16px'>{$_SESSION["ou"]}</span>";
	
}


function table_list(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();	
	$t=$_GET["t"];
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_transport_recipients", "artica_backup")){$q->BuildTables();}
	$table="postfix_transport_mailbox";
	
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
	
	$chooses[0]="{mailbox}";
	$chooses[1]="{organization}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
			$relay=$ligne["lmtp_address"];
			$js="TransPortRecipt1$t({$ligne["ID"]})";
			
			
			$choose=$tpl->_ENGINE_parse_body($chooses[$ligne["xType"]]);
			
			$md=md5(serialize($ligne));
			$cells=array();
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:$js;\" style='font-size:22px;font-weight:bold;text-decoration:underline'>$choose:{$ligne["uid"]}</a>";
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:$js;\" style='font-size:22px;font-weight:bold;text-decoration:underline'>LMTP:$relay</a>";
			$cells[]="<center>".imgsimple("delete-32.png",null,"TransPortReciptDel$t('{$ligne["ID"]}')")."</center>";
			
			
			
			$data['rows'][] = array(
				'id' =>$ligne["ID"],
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
}	