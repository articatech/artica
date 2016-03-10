<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.postgres.inc');

	$user=new usersMenus();
	if(isset($_GET["download"])){download();exit;}
	if(isset($_GET["delete-all-js"])){delete_all_js();exit;}
	if(isset($_POST["delete-all"])){delete_all();exit;}
	
	
	if(isset($_GET["delete-message-js"])){delete_message_js();exit;}
	if(isset($_POST["delete-message"])){delete_message();exit;}
	
	
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["zmd5"])){item();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}
	if(isset($_GET["zoom-js"])){zoom_js();exit;}
	if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
	
	if(isset($_POST["delete-email"])){delete_by_recipient();exit;}

	if(isset($_POST["mailfrom"])){rule_add();exit;}
	if(isset($_POST["del-zmd5"])){autocompress_rule_delete();exit;}
	popup();
	
function delete_all_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete_all}");
	

	$t=time();
echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#AUTOWHITE_TABLE').flexReload();
}
function Add$t(){
	if(!confirm('$text?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-all', 'yes');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
}	

function delete_message_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete}");
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM autowhite WHERE zmd5='{$_GET["delete-message-js"]}'","artica_backup"));
	
	$mailfrom=$tpl->javascript_parse_text("{from}:")." ".$ligne["mailfrom"];
	$mailto=$tpl->javascript_parse_text("{to}:")." ".$ligne["mailto"];
	
	$t=time();
	echo "
	var xAdd$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#AUTOWHITE_TABLE').flexReload();
	}
	function Add$t(){
		if(!confirm('$text $mailfrom $mailto ?') ){return;}
		var XHR = new XHRConnection();
		XHR.appendData('delete-message', '{$_GET["delete-message-js"]}');
		XHR.sendAndLoad('$page', 'POST',xAdd$t);
	}
	Add$t();";	
	
}

function delete_by_recipient(){
	$receipitent=$_POST["delete-email"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM autowhite WHERE mailto='$receipitent'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	return;
	
	
}


function delete_all(){
	$users=new usersMenus();
	$q=new mysql();
	if($users->AsPostfixAdministrator){
		$q->QUERY_SQL("TRUNCATE TABLE autowhite","artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	if($users->AsMessagingOrg){
		$ldap=new clladp();
		$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			
		while (list ($domain,$MAIN) = each ($domains) ){
			$domain=trim(strtolower($domain));
			if($domain==null){continue;}
			$FDOMS[]="domainto LIKE '%$domain'";
			$FDOMS2[]="mailfrom LIKE '%$domain'";
		}
		$imploded1=@implode(" OR ", $FDOMS);
		$imploded2=@implode(" OR ", $FDOMS2);
		$sql="delete FROM autowhite WHERE ($imploded1) OR ($imploded2)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;echo "\n$sql\n";}
		return;
	}
	
	
}

function delete_message(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM autowhite WHERE zmd5='{$_POST["delete-message"]}'","artica_backup");
	
}

function zoom_js(){
	header("content-type: application/x-javascript");
	$q=new postgres_sql();
	$tpl=new templates();
	$page=CurrentPageName();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='{$_GET["id"]}'"));
	$subject=$ligne["subject"];
	echo "YahooWin2(990,'$page?zoom-popup=yes&id={$_GET["id"]}','$subject')";
}

function  zoom_popup(){
	$q=new postgres_sql();
	$tpl=new templates();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='{$_GET["id"]}'"));
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{size}:</td>
		<td style='font-size:18px;font-weight:bold'>". FormatBytes($ligne["size"]/1024)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{from}:</td>
		<td style='font-size:18px;font-weight:bold'>". texttooltip(substr($ligne["mailfrom"],0,64),$ligne["mailfrom"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{to}:</td>
		<td style='font-size:18px;font-weight:bold'>". texttooltip(substr($ligne["mailto"],0,64),$ligne["mailto"])."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:18px'>{subject}:</td>
		<td style='font-size:18px;font-weight:bold'>{$ligne["subject"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{retention}:</td>
		<td style='font-size:18px;font-weight:bold'>".date("Y {l} {F} d",$ligne["final"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{download2}:</td>
		<td style='font-size:18px;font-weight:bold'><a href=\"$page?download={$ligne["msgmd5"]}\"
		style='font-size:18px;font-weight:bold;text-decoration:underline'>{$ligne["msgmd5"]}.gz</a></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>". button("{resend}","Resend$t()",26)."</td>
	</tr>
	
	
	</table>
	
	</div>
	<script>
		function Resend$t(){
			if(!confirm('$resend ?')){return;}
			Loadjs('mimedefang.backup.query.resend.php?id={$_GET["id"]}');
		}
	</script>		
				
				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function download(){
	
	$q=new postgres_sql();
	$zmd5=$_GET["download"];
	$sql="SELECT contentid FROM backupdata WHERE msgmd5='$zmd5'";
	if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "<hr>MySQL Error:".$q->mysql_error."<br>".die();}
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		die();
	}
	
	$contentid=$ligne["contentid"];
	if($GLOBALS["VERBOSE"]){echo "<hr>contentid:&laquo;$contentid&raquo;<br>";}
	@mkdir("/usr/share/artica-postfix/ressources/conf/upload",0777,true);
	@chmod("/usr/share/artica-postfix/ressources/conf/upload",0777);
	
	if(is_file("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz")){
			@unlink("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
	}
	
	$sql="select lo_export($contentid, '/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz')";
	if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "<hr>MySQL Error:".$q->mysql_error."<br>";}
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		die();
	}
	if($GLOBALS["VERBOSE"]){echo "<hr>OK /usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz<br>\n";}
	if(!$GLOBALS["VERBOSE"]){
		header('Content-type: '."application/x-gzip");
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename=\"$zmd5.gz\"");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	}
	
	if($GLOBALS["VERBOSE"]){
		if(!is_file("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz")){
			echo "<hr>/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz no such file<br>\n";
		}else{
			echo "<hr>/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz Exists<br>\n";
		}
		
	}
	if($GLOBALS["VERBOSE"]){echo "<hr>filesize()...<br>\n";}
	$fsize = @filesize("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
	if($GLOBALS["VERBOSE"]){echo "<hr>fsize:$fsize<br>\n";}
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
	@unlink("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=880;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$to=$tpl->_ENGINE_parse_body("{recipients}");
	$title=$tpl->_ENGINE_parse_body("&laquo;{smtp_AutoWhiteList}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$zdate=$tpl->javascript_parse_text("{date}");
	$size=$tpl->javascript_parse_text("{size}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$delete_by_recipient=$tpl->javascript_parse_text("{delete_by_recipient}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	{name: '<strong style=font-size:18px>$delete_all</strong>', bclass: 'Delz', onpress : DeleteAll$t},
	{name: '<strong style=font-size:18px>$delete_by_recipient</strong>', bclass: 'Delz', onpress : DeleteRecipt$t},
	
	],	";
	
	
	$html="
	<table class='AUTOWHITE_TABLE' style='display: none' id='AUTOWHITE_TABLE' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#AUTOWHITE_TABLE').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '<span style=font-size:18px>$from</span>', name : 'mailfrom', width :572, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'mailto', width :572, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>&nbsp;</span>', name : 'action', width :100, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},
		
	],
	sortname: 'mailfrom',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');
}

function DeleteAll$t(){
	Loadjs('$page?delete-all-js=yes');
}


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#AUTOWHITE_TABLE').flexReload();
}

function NewGItem$t(){
	YahooWin('650','$page?zmd5=&t=$t','$new_entry');
	
}
function GItem$t(zmd5,ttile){
	YahooWin('650','$page?rulemd5='+zmd5+'&t=$t',ttile);
	
}

var x_DeleteAutCompress$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowC'+mem$t).remove();
}

function DeleteRecipt$t(){
	var email=prompt('email ?');
	if(!email){return;}
	var XHR = new XHRConnection();
    XHR.appendData('delete-email',email);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);
}

function GroupAmavisExtEnable(id){
	var value=0;
	if(document.getElementById('gp'+id).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-gp',id);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}


function DeleteAutCompress$t(md5){
	if(confirm('$ask_delete_rule')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-zmd5',md5);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteAutCompress$t);		
	
	}

}

</script>";
	
	echo $html;
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$users=new usersMenus();	
	
	$search='%';
	$table="autowhite";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$users->AsPostfixAdministrator){
		if($users->AsMessagingOrg){
			$ldap=new clladp();
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			
			while (list ($domain,$MAIN) = each ($domains) ){
				$domain=trim(strtolower($domain));
				if($domain==null){continue;}
				$FDOMS[]="domainto LIKE '%$domain'";
				$FDOMS2[]="mailfrom LIKE '%$domain'";
			}
			$imploded1=@implode(" OR ", $FDOMS);
			$imploded2=@implode(" OR ", $FDOMS2);
			$table="(select * FROM autowhite WHERE ($imploded1) OR ($imploded2)) as t";
		}
		
	}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["tcount"];
		
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart,$rp ";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=$ligne["zmd5"];
	$color="#000000";
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$delete=imgsimple("delete-24.png","","Loadjs('$MyPage?delete-message-js=$zmd5')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?zoom-js=yes&id=$id');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";

	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
			"<center style='font-size:16px;color:$color'>$delete</a></center>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}



function item(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Save$t();","22px");
	$zmd5=$_GET["zmd5"];
	
	if($zmd5<>null){
		$btname=button("{apply}","Save$t();","22px");
		$sql="SELECT * FROM autowhite WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	

	
	$html="
	
	 <div style='font-size:18px;margin:20px' class=explain>{mimedefang_email_explain}</div>
	 <table style='width:99%' class=form>
	 <tr>
	 	<td class=legend style='font-size:22px'>{sender}:</td>
	 	<td>". Field_text("mailfrom-$t",$ligne["mailfrom"],"font-size:22px;width:310px")."</td>
	 </tr>
	 <tr>
	 	<td class=legend style='font-size:22px'>{recipient}:</td>
	 	<td>". Field_text("mailto-$t",$ligne["mailto"],"font-size:22px;width:310px",null,null,null,false,"SaveC$t(event)")."</td>
	 </tr>	

	<tr>
		<td colspan=2 align='right'><hr>$btname</td>
	</tr>
	</table>
	<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#AUTOWHITE_TABLE').flexReload();
	YahooWinHide();
}		

function SaveC$t(e){
	if(checkEnter(e)){Save$t();}
}
	
function Save$t(){
	var XHR = new XHRConnection();  
	XHR.appendData('zmd5','$zmd5');
	XHR.appendData('mailfrom',document.getElementById('mailfrom-$t').value);
	XHR.appendData('mailto',document.getElementById('mailto-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
		

	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function autocompress_rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mimedefang_backup WHERE ID='{$_POST["ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rule_add(){

	$tpl=new templates();
	$_POST["mailfrom"]=trim(strtolower($_POST["mailfrom"]));
	$_POST["mailto"]=trim(strtolower($_POST["mailto"]));
	if($_POST["mailto"]==null){$_POST["mailto"]="*";}
	if($_POST["mailfrom"]==null){echo $tpl->javascript_parse_text("{please_define_sender}");return;}
	$zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO autowhite (zmd5,mailfrom,mailto)
			VALUES ('$zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}



