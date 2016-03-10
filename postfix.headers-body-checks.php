<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	
	if(isset($_GET["headers"])){headers();exit;}
	if(isset($_GET["headers-search"])){headers_search();exit;}
	if(isset($_GET["import-search"])){parse_examples();exit;}
	
	if(isset($_GET["bodies"])){bodies();exit;}
	if(isset($_GET["import-bodies"])){bodies_import();exit;}
	
	
	
	if(isset($_GET["mimes"])){mimes();exit;}
	if(isset($_GET["import-mime"])){mimes_import();exit;}
	
	
	if(isset($_GET["ID-ACTION"])){regex_rule_action();exit;}
	if(isset($_GET["ID"])){regex_rule();exit;}
	if(isset($_POST["ID"])){regex_rule_save();exit;}
	
	if(isset($_POST["ENABLE_ID"])){regex_rule_enable();exit;}
	if(isset($_POST["NOTIFY_ID"])){regex_rule_notify();exit;}
	
	
	if(isset($_POST["DELETE_ID"])){regex_rule_del();exit;}
	if(isset($_POST["DELETE_ALL"])){regex_rule_delall();exit;}
	if(isset($_POST["NOTIFY_ALL"])){regex_rule_notifyall();exit;}
	if(isset($_GET["help"])){help();exit;}
	
	
js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["hostname"]}::{headers_and_body_rules}");
	$html="YahooWin5('900','$page?tabs=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}	

function help(){
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:30px'>{postfix_regex_man5}</div>");
	
}

function tabs(){
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["rule-reject"]='{blockips}';
	$array["headers"]='{header_checks}';
	$array["bodies"]='{body_checks}';
	$array["mimes"]='{mime_header_checks}';
	$array["SimpleWords"]='{RegexSimpleWords}';
	
	
	
	$array["help"]="{help}";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="rule-reject"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"domains.postfix.multi.reject.php?hostname=$hostname&ou={$_GET["ou"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
		}	
	
		if($num=="SimpleWords"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"domains.postfix.multi.regex.php?SimpleWords=yes&hostname=$hostname&ou={$_GET["ou"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="bodies"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?headers=yes&headers-query=0&hostname=$hostname&ou={$_GET["ou"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="mimes"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?headers=yes&headers-query=2&hostname=$hostname&ou={$_GET["ou"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
		}		
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&hostname=$hostname&ou={$_GET["ou"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_multi_config_headersbody$t",1450);
	
}


function headers(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if(!isset($_GET["headers-query"])){$_GET["headers-query"]=1;}
	$hostname=$_GET["hostname"];
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$type=$tpl->_ENGINE_parse_body("{sourcetype}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$flags=$tpl->_ENGINE_parse_body("{flags}");
	$newrule=$tpl->_ENGINE_parse_body("{new_rule}");
	$squidGroup=$tpl->_ENGINE_parse_body("{SquidGroup}");
	$title=$tpl->_ENGINE_parse_body($title);
	$enable=$tpl->javascript_parse_text("{enable}");
	$users=new usersMenus();
	$SQUID_ARP_ACL_ENABLED=1;
	if(!$users->SQUID_ARP_ACL_ENABLED){$SQUID_ARP_ACL_ENABLED=0;}
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$action=$tpl->javascript_parse_text("{action}");
	$add_squid_uderagent_explain=$tpl->javascript_parse_text("{add_squid_uderagent_explain}");
	$import_headers_regex=$tpl->_ENGINE_parse_body("{import_headers_regex}");
	$delete_alltext=$tpl->javascript_parse_text("{delete_all} ?");
	$delete_allB=$tpl->javascript_parse_text("{delete_all}");
	$deletetext=$tpl->javascript_parse_text("{delete} ?");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$explain=$tpl->_ENGINE_parse_body("{headers_checks_text}");
	$notify=$tpl->_ENGINE_parse_body("{notify}");
	$notify_all=$tpl->_ENGINE_parse_body("{notify_all}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	if($_GET["headers-query"]==1){
		$buttons="
		buttons : [
		{name: '$newrule', bclass: 'add', onpress : AddRegexRule},
		{name: '$import_headers_regex', bclass: 'add', onpress : import_headers_regex},
		{name: '$notify_all', bclass: 'eMail', onpress : NotifyAll},
		{name: '$apply', bclass: 'apply', onpress : Apply$t},
		{name: '$delete_allB', bclass: 'Delz', onpress : PostfixRegexDelAll},
		
		],";
		$explain=$tpl->javascript_parse_text("{headers_checks_text}");

	}
	if($_GET["headers-query"]==0){
		$buttons="
		buttons : [
		{name: '$newrule', bclass: 'add', onpress : AddRegexRule},
		{name: '$import_headers_regex', bclass: 'add', onpress : import_bodies_regex},
		{name: '$notify_all', bclass: 'eMail', onpress : NotifyAll},
		{name: '$apply', bclass: 'apply', onpress : Apply$t},
		{name: '$delete_allB', bclass: 'Delz', onpress : PostfixRegexDelAll},
		
		],";
		$explain=$tpl->javascript_parse_text("{body_checks_text}");

	}		
	if($_GET["headers-query"]==2){
		$buttons="
		buttons : [
		{name: '$newrule', bclass: 'add', onpress : AddRegexRule},
		{name: '$import_headers_regex', bclass: 'add', onpress : import_mime_regex},
		{name: '$notify_all', bclass: 'eMail', onpress : NotifyAll},
		{name: '$apply', bclass: 'apply', onpress : Apply$t},
		{name: '$delete_allB', bclass: 'Delz', onpress : PostfixRegexDelAll},
		
		],";
		$explain=$tpl->javascript_parse_text("{mime_header_checks_text}");
		

	}		
	

$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?headers-search=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&headers-query={$_GET["headers-query"]}',
	dataType: 'json',
	colModel : [
		{display: '$pattern', name : 'pattern', width :353, sortable : true, align: 'left'},
		{display: '$flags', name : 'flags', width :219, sortable : true, align: 'left'},
		{display: '$action', name : 'action', width : 143, sortable : true, align: 'center'},
		{display: '$notify', name : 'xNOTIFY', width : 90, sortable : true, align: 'center'},
		{display: '$enable', name : 'enabled', width : 90, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: '$flags', name : 'flags'},
		{display: 'ID', name : 'ID'}
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:18px>$explain</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function About$t(){
	alert('$explain');
}

function AddRegexRule(){
	PostfixRegexAdd(-1,'$hostname');
}

function Apply$t(){
	Loadjs('postfix.headers-body-checks.progress.php?hostname=$hostname');
}
	
var x_PostfixRegexDelAll= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT$t').flexReload();
}
	
function PostFixRegexRefreshTableau(){
		$('#flexRT$t').flexReload();
}
	
function NotifyAll(){
	var XHR = new XHRConnection();
	XHR.appendData('NOTIFY_ALL',{$_GET["headers-query"]});
	XHR.appendData('hostname','$hostname');
	XHR.appendData('ou','{$_GET["ou"]}');
	AnimateDiv('postfix-headers-list');
	XHR.sendAndLoad('$page', 'POST',x_PostfixRegexDelAll);		
}


	function PostfixRegexDelAll(){
		if(confirm('$delete_alltext')){
			var XHR = new XHRConnection();
			XHR.appendData('DELETE_ALL',{$_GET["headers-query"]});
			XHR.appendData('hostname','$hostname');
			XHR.appendData('ou','{$_GET["ou"]}');
			AnimateDiv('postfix-headers-list');
			XHR.sendAndLoad('$page', 'POST',x_PostfixRegexDelAll);	
			}
	}
	
	var x_PostfixRegexDel= function (obj) {
		var headers={$_GET["headers-query"]};
		var results=obj.responseText;
		if(results.length>3){
			alert('\"'+results+'\"');
		}else{
			$('#row'+IDTMP).remove();
		}
	}
	
	var x_PostfixRegexSilent= function (obj) {
		var headers={$_GET["headers-query"]};
		var results=obj.responseText;
		if(results.length>3){
			alert('\"'+results+'\"');
		}
	}	
	
	
	function PostfixRegexDel(ID){
	if(confirm('$deletetext')){
		IDTMP=ID;
		var XHR = new XHRConnection();
		XHR.appendData('DELETE_ID',ID);
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_PostfixRegexDel);
		}
	}
	
	function PostfixRegexAdd(ID){
		YahooWin6('850','$page?ID='+ID+'&hostname=$hostname&ou={$_GET["ou"]}&header-query={$_GET["headers-query"]}','$rule_text::'+ID+'::$hostname');
	}	
	
	function import_headers_regex(){
		var XHR = new XHRConnection();
		XHR.appendData('import-search','yes');
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'GET',x_PostfixRegexDelAll);	
	}
	
	function PostfixRegexEnable(md5,id){
		var XHR = new XHRConnection();
		XHR.appendData('ENABLE_ID',id);
		if(document.getElementById(md5).checked){XHR.appendData('VALUE',1);}else{XHR.appendData('VALUE',0);}
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_PostfixRegexSilent);	
	}
	
	function PostfixRegexNotify(md5,id){
		var XHR = new XHRConnection();
		XHR.appendData('NOTIFY_ID',id);
		if(document.getElementById(md5).checked){XHR.appendData('VALUE',1);}else{XHR.appendData('VALUE',0);}
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'POST',x_PostfixRegexSilent);	
	}
	
	function import_bodies_regex(){
		var XHR = new XHRConnection();
		XHR.appendData('import-bodies','yes');
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'GET',x_PostfixRegexDelAll);		
	}
	
	function import_mime_regex(){
		var XHR = new XHRConnection();
		XHR.appendData('import-mime','yes');
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.sendAndLoad('$page', 'GET',x_PostfixRegexDelAll);		
	}	
</script>

";	
	echo $html;
	
}

function headers_search(){
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="postfix_regex_checks";
	$page=1;
	
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$table="(SELECT * FROM `$table` WHERE hostname='{$_GET["hostname"]}' AND headers={$_GET["headers-query"]} ) as t";
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	if(mysql_num_rows($results)==0){json_error_show("No data");}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5($ligne["ID"]);
		$select="PostfixRegexAdd('{$ligne["ID"]}')";
		$delete=imgtootltip("delete-24.png","{delete}","PostfixRegexDel('{$ligne["ID"]}')");
		$enable=Field_checkbox($md5,1,$ligne["enabled"],"PostfixRegexEnable('$md5','{$ligne["ID"]}')");
		$notify=Field_checkbox($md5."_not",1,$ligne["xNOTIFY"],"PostfixRegexNotify('{$md5}_not','{$ligne["ID"]}')");

		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		if($ligne["flags"]==null){$ligne["flags"]="&nbsp;";}
		if($ligne["pcre"]==2){$ligne["pcre"]="regex";}else{$ligne["pcre"]="pcre";;}
		$ligne["flags"]=str_replace("'", "`", $ligne["flags"]);
		$ligne["pattern"]=str_replace("'", "`", $ligne["pattern"]);
		if(strlen($ligne["pattern"])>50){$ligne["pattern"]=substr($ligne["pattern"],0,45)."...";}
		if(strlen($ligne["flags"])>30){$ligne["flags"]=substr($ligne["flags"],0,25)."...";}
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$select\" 
		style='font-size:18px;text-decoration:underline;font-family:monospace;font-weight:bold;color:$color'>";	
		
		$c++;
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("$href{$ligne["pattern"]}</a>"
		,"$href{$ligne["flags"]}</a>"
		,"$href{$ligne["action"]}</a>"
		,$notify
		,$enable
		,$delete )
		);
	}
	if($c==0){json_error_show("No data");}
	
echo json_encode($data);		
}

	


function regex_rule(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();	
	$ID=$_GET["ID"];
	$headers=$_GET["header-query"];
	if(!is_numeric($headers)){$headers=1;}
	$sql="SELECT * FROM postfix_regex_checks WHERE hostname='$hostname' AND ID='$ID' AND headers=$headers";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$buttonname="{apply}";
	if($ID<1){$buttonname="{add}";}
	$time=time();
	
	$format["1"]="Perl Compatible Regular Expression";
	$format["2"]="POSIX regular expression";
	
	$action["REJECT"]="{reject}";
	$action["DISCARD"]="{discard}";
	$action["DUNNO"]="{dunno}";
	$action["FILTER"]="{filter}";
	$action["HOLD"]="{hold}";
	$action["IGNORE"]="{ignore}";
	
	$action["INFO"]="{info}";
	$action["PREPEND"]="{prepend}";
	$action["REDIRECT"]="{redirect}";
	$action["REPLACE"]="{replace}";
	$action["WARN"]="{warn}";
	
	$html="
	<div id='$time'>
	<table style='width:99%' class=form>
	</tbody>
	<tr>
		<td class=legend style='font-size:22px'>{format}:</td>
		<td>". Field_array_Hash($format, "pcre",$ligne["pcre"],null,null,0,"font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{action}:</td>
		<td>". Field_array_Hash($action, "postfixregexaction",$ligne["action"],"RegexExplainAction()",null,0,"font-size:22px;font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;")."</td>
	</tr>		
	<tr>
		<td colspan=2>
		
		<textarea id='pattern' style='font-size:22px !important;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:120px;overflow:auto'>{$ligne["pattern"]}</textarea></td>
	</tr>

	<tr>
		<td colspan=2><div id='postfixregexaction-fill'></div></td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($buttonname,"SaveRegexPostfixPattern()",36)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		function RegexExplainAction(){
			var action=document.getElementById('postfixregexaction').value;
			LoadAjaxTiny('postfixregexaction-fill','$page?ID-ACTION=yes&action='+action+'&ID=$ID&hostname=$hostname&ou={$_GET["ou"]}');
		
		}
		
	var x_SaveRegexPostfixPattern= function (obj) {
			var headers=$headers;
			var results=obj.responseText;
			if(results.length>3){alert('\"'+results+'\"');}
			YahooWin6Hide();
			PostFixRegexRefreshTableau();
		}		
		
		function SaveRegexPostfixPattern(){
			var XHR = new XHRConnection();
			var pp=document.getElementById('pattern').value;
			pp = pp.replace(/\+/g,'%2B');
			var pp=encodeURIComponent(document.getElementById('pattern').value);
			XHR.appendData('pcre',document.getElementById('pcre').value);
			XHR.appendData('ID','$ID');
			XHR.appendData('headers','$headers');
			XHR.appendData('action',document.getElementById('postfixregexaction').value);
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('hostname','$hostname');
			XHR.appendData('pattern',pp);
			if(document.getElementById('flags').value){
				XHR.appendData('flags',document.getElementById('flags').value);	
			}
			AnimateDiv('$time');
			XHR.sendAndLoad('$page', 'POST',x_SaveRegexPostfixPattern);			
		
		}
		
		
		RegexExplainAction();
		
		
		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function regex_rule_action(){
	$action=$_GET["action"];
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();	
	$ID=$_GET["ID"];
	$sql="SELECT * FROM postfix_regex_checks WHERE hostname='$hostname' AND ID='$ID' AND action='{$_GET["action"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	if($action=="DISCARD"){$fieldname="{event}:";}
	if($action=="DUNNO"){$fieldname="{event}:";}
	if($action=="FILTER"){$fieldname="{service}:";}
	if($action=="HOLD"){$fieldname="{event}:";}
	if($action=="IGNORE"){echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:18px'>{POSTFIX_REGEX_".strtoupper($_GET["action"])."}</div><input type='hidden' id='flags' value=''>");return;}
	if($action=="PREPEND"){$fieldname="{PREPEND}:";}
	if($fieldname==null){$fieldname="{{$action}}:";}
	
	$html="<div class=explain style='font-size:18px'>{POSTFIX_REGEX_".strtoupper($_GET["action"])."}</div>
	<table style='width:100%'>
	<tr>
		<tr>
		<td class=legend style='font-size:22px'>$fieldname:</td>
		<td>". Field_text("flags", $ligne["flags"],"font-size:22px !important;font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;")."</td>
	</tr>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function regex_rule_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();		
	$_POST["pattern"]=url_decode_special_tool($_POST["pattern"]);
	while (list ($num, $ligne) = each ($_POST) ){$_POST[$num]=addslashes($ligne);}
	
	
	if($_POST["action"]=="FILTER"){
		if(preg_match("#transport:(.+)#", $_POST["flags"],$re)){$_POST["flags"]="smtp:".$re[1];}
	}
	
	if($_POST["action"]=="REPLACE"){
		if(trim($_POST["flags"])==null){
			echo $tpl->javascript_parse_text("{POSTHDRCHK_ERROR_REPLACE}");
			return;
		}
	}
	
	
	$sql="UPDATE postfix_regex_checks SET 
	pcre='{$_POST["pcre"]}',
	action='{$_POST["action"]}',
	pattern='{$_POST["pattern"]}',
	flags='{$_POST["flags"]}' 
	WHERE ID={$_POST["ID"]}
	";
	
	if($_POST["ID"]<1){
		$sql="INSERT IGNORE INTO postfix_regex_checks (pcre,action,pattern,flags,hostname,headers) VALUES 
		('{$_POST["pcre"]}','{$_POST["action"]}','{$_POST["pattern"]}','{$_POST["flags"]}','{$_POST["hostname"]}','{$_POST["headers"]}')";
	}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
		
		
	
}

function mimes_import(){
	$prefix="INSERT IGNORE INTO postfix_regex_checks (pcre,action,pattern,flags,hostname,headers) VALUES ";
	
	$f[]=array("REJECT","^((Content-(Disposition: attachment;|Type:).*|\ +)| *)(file)?name\ *=\ *\"?.*\.(lnk|asd|hlp|ocx|reg|bat|c[ho]m|cmd|exe|dll|vxd|pif|scr|hta|jse?|sh[mbs]|vb[esx]|ws[fh]|wmf)\"?\ *$","attachment type not allowed");
	$f[]=array("REJECT","^Content-(?:Disposition:\s+attachment;|Type:).*\b(?:file)?name\s*=.*\.(?:ad[ep]|asd|ba[st]|chm|cmd|com(?=$|\")|cpl|crt|dll|eml|exe|hlp|hta|in[fs]|isp|jse?|lnk|md[betw]|ms[cipt]|nws|ocx|ops|pcd|p[ir]f|reg|sc[frt]|sh[bsm]|swf|url|vb[esx]?|vxd|ws[cfh]|\{[[:xdigit:]]{8}(?:-[[:xdigit:]]{4}){3}-[[:xdigit:]]{12}\})\b",
	"Windows executables not allowed");
	$f[]=array("REJECT","^(.*)name=\"(DHL_document).(zip|cmd)\\\"$","");
	$f[]=array("REJECT","/^(.*)name=\"(DHL_notification).(zip|cmd)\\\"$","");
	while (list ($num, $lin) = each ($f) ){
		while (list ($a, $b) = each ($lin) ){$lin[$a]=addslashes($b);}
		
		$t[]="('2','{$lin[0]}','{$lin[1]}','{$lin[2]}','{$_GET["hostname"]}','2')";
		
	}
	$q=new mysql();
	$sql=$prefix.@implode(",", $t);
	$q->QUERY_SQL($prefix.@implode(",", $t),"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}
	
	
	
}


function bodies_import(){
	
	$prefix="INSERT IGNORE INTO postfix_regex_checks (pcre,action,pattern,flags,hostname,headers) VALUES ";
	$f[]=array("DUNNO","^[A-Za-z0-9+\/=]{4,76}$","");
	$f[]=array("DUNNO","^ {6,11}\d{1,6}[ km]","");
	$f[]=array("DUNNO","^ {4}blocked using ","");
	$f[]=array("REJECT","^begin\s+\d+\s+.+?\.(386|ad[ept]|app|as[dpx]|ba[st]|bin|btm|cab|cb[lt]|cgi|chm|cil|cla(ss)?|cmd|com|cp[el]|crt|cs[chs]|cvp|dll|dot|drv|em(ai)?l|ex[_e]|fon|fxp|hlp|ht[ar]|in[fips]|isp|jar|jse?|keyreg|ksh|lib|lnk|md[abetw]|mht(m|ml)?|mp3|ms[ciopt]|nte|nws|obj|ocx|ops|ov.|pcd|pgm|pif|p[lm]|pot|pps|prg|reg|sc[rt]|sh[bs]?|slb|smm|sw[ft]|sys|url|vb[esx]?|vir|vmx|vxd|wm[dsz]|ws[cfh]|xl.|xms|\{[\da-f]{8}(?:-[\da-f]{4}){3}-[\da-f]{12}\})\b","\".$1\" filetype not allowed");
	$f[]=array("REJECT","<\s*(object\s+data)\s*=","Email with \"$1\" tags not allowed");
	$f[]=array("REJECT","<\s*(script\s+language\s*=\"vbs\")","Email with \"$1\" tags not allowed");
	$f[]=array("REJECT","<\s*(script\s+language\s*=\"VBScript\.Encode\")","Email with \"$1\" tags not allowed");

	while (list ($num, $lin) = each ($f) ){
		while (list ($a, $b) = each ($lin) ){$lin[$a]=addslashes($b);}
		
		$t[]="('2','{$lin[0]}','{$lin[1]}','{$lin[2]}','{$_GET["hostname"]}','0')";
		
	}
	$q=new mysql();
	$sql=$prefix.@implode(",", $t);
	$q->QUERY_SQL($prefix.@implode(",", $t),"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}
	
	
	
}


function parse_examples(){
	
	$q=new mysql();
	

	$prefix="INSERT IGNORE INTO postfix_regex_checks (pcre,pattern,action,flags,hostname,headers) VALUES ";
	
	$datas=explode("\n",file_get_contents(dirname(__FILE__) . "/ressources/databases/examples.header_checks.db"));
	if(!is_array($datas)){return null;}
	while (list ($num, $ligne) = each ($datas) ){
		$lin=ParseRegexLine($ligne);
		if(is_array($lin)){
			$lin[1]=addslashes($lin[1]);
			$lin[2]=addslashes($lin[2]);
			$lin[0]=addslashes($lin[0]);
			$t[]="('2','{$lin[0]}','{$lin[1]}','{$lin[2]}','{$_GET["hostname"]}','1')";
		}
	}
	
	$sql=$prefix.@implode(",", $t);
	$q->QUERY_SQL($prefix.@implode(",", $t),"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}
		
}


function ParseRegexLine($line){
	if(substr($line,0,1)=='#'){return null;}
	if(preg_match('#/(.+?)/(.*)#',$line,$re)){
		$regex=$re[1];
		$val=$re[2];
		if(preg_match('#(REDIRECT|DISCARD|HOLD|PREPEND|REPLACE|REJECT|WARN|DROP|IGNORE)(.*)#i',$val,$li)){
			$action=$li[1];
			$infos=$li[2];
			return array(trim($regex),trim($action),trim($infos));
		}else{
			writelogs("unable to parse '$val' in '$line'",__CLASS__.'/'.__FUNCTION__,__FILE__);
		}	
	}
}
	
function regex_rule_notifyall(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE  postfix_regex_checks SET xNOTIFY=1 
	WHERE hostname='{$_POST["hostname"]}' AND headers={$_POST["NOTIFY_ALL"]}","artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-mime-header-checks=yes&hostname={$_POST["hostname"]}");	
	
}

function regex_rule_delall(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM postfix_regex_checks WHERE hostname='{$_POST["hostname"]}' AND headers={$_POST["DELETE_ALL"]}","artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	
	
	
}
function regex_rule_enable(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE postfix_regex_checks SET enabled={$_POST["VALUE"]} WHERE hostname='{$_POST["hostname"]}' AND ID={$_POST["ENABLE_ID"]}","artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	$sock=new sockets();
	
}

function regex_rule_notify(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE postfix_regex_checks SET xNOTIFY={$_POST["VALUE"]} WHERE hostname='{$_POST["hostname"]}' AND ID={$_POST["NOTIFY_ID"]}","artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	$sock=new sockets();
	
}

function regex_rule_del(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM postfix_regex_checks WHERE ID={$_POST["DELETE_ID"]}","artica_backup");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	$sock=new sockets();
	
}