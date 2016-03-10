<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.sockets.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	

$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){echo "alert('NO RIGHTS');";die();}
if(isset($_GET["js-message"])){queue_js();exit;}
if(isset($_GET["tab-message"])){queue_tab();exit;}
if(isset($_GET["routing-info"])){queue_info();exit;}
if(isset($_GET["body-messages"])){queue_message();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["process-message"])){process_message_js();exit;}
if(isset($_GET["delete-message"])){delete_message_js();exit;}

if(isset($_POST["reprocess"])){process_message();exit;}
if(isset($_POST["delete"])){delete_message();exit;}

page();

function page(){

	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$rulename =$tpl->_ENGINE_parse_body("{rulename}");
	$title=$tpl->_ENGINE_parse_body("{today}: {blocked} (requests)");
	$event=$tpl->_ENGINE_parse_body("{why}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$t=time();
	
	$title=$tpl->javascript_parse_text("{postqueue_list_explain}");
	
	$html="<table class='POSTFIX_QUEUE_DETAILS' style='display: none' id='POSTFIX_QUEUE_DETAILS' style='width:100%'></table>
<script>
$(document).ready(function(){
$('#POSTFIX_QUEUE_DETAILS').flexigrid({
	url: '$page?search=yes&hostname={$_GET["hostname"]}',
	dataType: 'json',
	colModel : [
		
		{display: '<span style=font-size:18px>$date</span>', name : 'website', width : 135, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$from</span>', name : 'from', width : 180, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'recipients', width : 181, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$event</span>', name : 'event', width : 769, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>process</span>', name : 'rep', width : 41, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$delete</span>', name : 'del', width : 41, sortable : false, align: 'center'},

		],
	
	searchitems : [
		{display: '$from', name : 'from'},
		{display: '$to', name : 'recipients'},
		{display: '$event', name : 'event'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
</script>
";
echo $html;
	
}

function queue_tab(){
	$array["routing-info"]="{routing_info}";
	$array["body-messages"]="{body_message}";
	
	
	$_GET["font-size"]=18;
	
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$height="100%";}
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	while (list ($num, $ligne) = each ($array) ){
	
	
		$ligne=$tpl->_ENGINE_parse_body("$ligne");
		$html[]= "<li><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&message-id={$_GET["message-id"]}\" style='$fontsize'><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "queue-{$_GET["message-id"]}");	
	
/*	<td>" . Paragraphe("64-banned-phrases.png",'{routing_info}','{routing_info_text}',"javascript:switchDivViewQueue('messageidtable');")."</td>
	</tr>
	<tr>
	<td>" . Paragraphe("64-banned-regex.png",'{body_message}','{body_message_text}',"javascript:switchDivViewQueue('messageidbody');")."</td>
	</tr>
	<tr>
	<td>" . Paragraphe("64-refresh.png",'{reprocess_message}','{reprocess_message_text}',"javascript:PostCatReprocess('$messageid');")."</td>
	</tr>
	<tr>
	<td>" . Paragraphe("delete-64.png",'{delete_message}','{delete_message_text}',"javascript:PostCatDelete('$messageid');")."</td>
	</tr>
*/	
	
}

function queue_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	echo "YahooWin2('900','$page?tab-message=yes&message-id={$_GET["js-message"]}','{$_GET["js-message"]}');";
}

function process_message_js(){
	header("content-type: application/x-javascript");
	$msgid=$_GET["process-message"];
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{reprocess_message} $msgid ?\n{reprocess_message_text}");
	echo "
var xPostCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	$('#POSTFIX_QUEUE_DETAILS').flexReload();
	
	}	
	
	function PostCat$t(){
		if(!confirm('$title')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('reprocess','$msgid');
		XHR.sendAndLoad('$page', 'POST',xPostCat$t);	
	}
	
	PostCat$t();";
	
}

function delete_message_js(){
	header("content-type: application/x-javascript");
	$msgid=$_GET["delete-message"];
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{delete} $msgid ?\n");
	echo "
	var xPostCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	$('#POSTFIX_QUEUE_DETAILS').flexReload();
	
	}
	
function PostCat$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$msgid');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	
	XHR.sendAndLoad('$page', 'POST',xPostCat$t);
}
	
	PostCat$t();";	
	
}

function delete_message(){
	$mailid=$_POST["delete"];
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('cmd.php?postsuper-d-master='.$mailid));
	echo $datas;
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM postqueue WHERE msgid='$mailid'","artica_events");
}

function process_message(){
	$mailid=$_POST["reprocess"];
	if(trim($mailid)==null){echo "No Mail ID\n";return;}
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?postsuper-r-master='.$mailid);
	$logpath="/usr/share/artica-postfix/ressources/logs/web/postcat-{$mailid}.log";
	
	echo @file_get_contents($logpath);
	@unlink($logpath);
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM postqueue WHERE msgid='$mailid'","artica_events");
	
}

function queue_info(){
	include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/rfc822_addresses.php');
	$messageid=$_GET["message-id"];
	$tpl=new templates();
	$sock=new sockets();
	
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/postcat-$messageid.txt")){
		$sock->getFrameWork("postfix.php?postcat-q=$messageid");
		
		}
		
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/postcat-$messageid.txt")){
		echo FATAL_ERROR_SHOW_128("postcat-$messageid.txt no such file");
		return;
		
	}	
	
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/postcat-$messageid.txt");
	
	
	if(preg_match('#\*\*\* ENVELOPE RECORDS.+?\*\*\*(.+?)\s+\*\*\*\s+MESSAGE CONTENTS#is',$datas,$re)){
		$table_content=$re[1];
	}
	if(preg_match('#\*\*\* MESSAGE CONTENTS.+?\*\*\*(.+?)\*\*\*\s+HEADER EXTRACTED #is',$datas,$re)){
		$message_content=$re[1];
	}
	
	$tbl=explode("\n",$table_content);
	while (list ($num, $val) = each ($tbl) ){
		if(trim($val)==null){continue;}
		if(preg_match('#(.+?):(.+)#',$val,$ri)){
			$fields[$ri[1]]=trim($ri[2]);
		}
	
	}
	if(preg_match('#^([0-9]+)#',$fields["message_size"],$ri)){
		$fields["message_size"]=FormatBytes(($fields["message_size"]/1024));
	}
	
	
	
	
	$table="
<table style='width:100%'>";

if(is_array($fields)){
	while (list ($num, $val) = each ($fields) ){
		$table=$table . "
			<tr>
			<td class=legend style='font-size:18px' nowrap>{{$num}}:</td>
			<td><strong  style='font-size:18px'>{$val}</strong></td>
			</tr>
			";
			}
}
$table=$table . "</table>";


	
$html="
		<div style='font-size:26px'>{show_mail} $messageid {routing_info}</div><p>&nbsp;</p><div stytle='width:98%' class=form>$table</div>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function queue_message(){
	
	include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/rfc822_addresses.php');
	$messageid=$_GET["message-id"];
	$tpl=new templates();
	$sock=new sockets();
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/postcat-$messageid.txt")){
		echo FATAL_ERROR_SHOW_128("postcat-$messageid.txt no such file");
		return;
	
	}
	
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/postcat-$messageid.txt");
	
	if(preg_match('#\*\*\* MESSAGE CONTENTS.+?\*\*\*(.+?)\*\*\*\s+HEADER EXTRACTED #is',$datas,$re)){
		$message_content=$re[1];
	}	
	
	
	$message_content=htmlspecialchars($message_content);
	$len=FormatBytes(strlen($message_content)/1024);
	$messagesT=explode("\n",$message_content);
	$message_content=null;
	while (list ($num, $val) = each ($messagesT) ){
		if(trim($val)==null){continue;}
		$message_content=$message_content."<div><code style='font-size:14px'>$val</code></div>";
	}
	$html="
	<div style='font-size:26px'>{show_mail} $messageid {body_message} ($len)</div><p>&nbsp;</p><div stytle='width:98%' class=form>$message_content</div>";
	echo $tpl->_ENGINE_parse_body($html);
}


function search(){
	$MyPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$searchstring=string_to_flexquery();
	$table="postqueue";

	$search='%';
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE instance='{$_GET["hostname"]}' $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$total = $ligne["TCOUNT"];

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM `$table` WHERE instance='{$_GET["hostname"]}' $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error,1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	while ($ligne = mysql_fetch_assoc($results)) {

		$msgid=$ligne["msgid"];
		$to=$ligne["recipients"];
		$date=$ligne["zDate"];
		$from=$ligne["from"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('postfix.queue.monitoring.php?delete-message=$msgid&hostname={$_GET["hostname"]}')");
		$run=imgsimple("32-run.png",null,"Loadjs('postfix.queue.monitoring.php?process-message=$msgid')");
		$linkZoom="<a href=\"javascript:Loadjs('postfix.queue.monitoring.php?js-message=$msgid');\" style='font-size:18px;text-decoration:underline'>";
		$data['rows'][] = array(
				'id' => "$msgid",
				'cell' => array(
						"<span style='font-size:16px'>$date</span>",
						"<span style='font-size:16px'>$linkZoom$from</a></span>",
						"<span style='font-size:16px'>$to</span>",
						"<span style='font-size:16px'>{$ligne["event"]}</span>","<center>$run</center>",
						"<center>$delete</center>")
		);
}
echo json_encode($data);
}