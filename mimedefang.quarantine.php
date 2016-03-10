<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.postgres.inc');

	$user=new usersMenus();
	if(isset($_GET["download"])){download();exit;}
	if(isset($_GET["delete-message-js"])){delete_message_js();exit;}
	if(isset($_POST["delete-message"])){delete_message();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}
	if(isset($_GET["zoom-js"])){zoom_js();exit;}
	if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
	
	
	if(isset($_POST["mailfrom"])){rule_add();exit;}
	if(isset($_POST["del-zmd5"])){autocompress_rule_delete();exit;}
	popup();
	
function delete_message_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete}");
	
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='{$_GET["delete-message-js"]}'"));
	$subject=$ligne["subject"];
	$zdate=$tpl->javascript_parse_text("{date}:")." ".$ligne["zdate"];
	$mailfrom=$tpl->javascript_parse_text("{from}:")." ".$ligne["mailfrom"];
	
	$t=time();
echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#QURANTINE_MESSAGES_TABLE').flexReload();
}
function Add$t(){
	if(!confirm('$text $subject ?\\n$zdate\\n$mailfrom') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-message', '{$_GET["delete-message-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
}	


function delete_message(){
	$tpl=new templates();
	$q=new postgres_sql();
	
	
	
	$tpl=new templates();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT msgmd5 FROM quarmsg WHERE id='{$_POST["delete-message"]}'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$msgmd5=$ligne["msgmd5"];
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT contentid FROM quardata WHERE msgmd5='$msgmd5'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$contentid=$ligne["contentid"];
	
	if($contentid>0){$q->QUERY_SQL("select lo_unlink($contentid)");}
	$q->QUERY_SQL("DELETE FROM quardata WHERE msgmd5='$msgmd5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM quarmsg WHERE msgmd5='$msgmd5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function zoom_js(){
	header("content-type: application/x-javascript");
	$q=new postgres_sql();
	$tpl=new templates();
	$page=CurrentPageName();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='{$_GET["id"]}'"));
	$subject=$ligne["subject"];
	echo "YahooWin2(990,'$page?zoom-popup=yes&id={$_GET["id"]}','$subject')";
}

function  zoom_popup(){
	$q=new postgres_sql();
	$tpl=new templates();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='{$_GET["id"]}'"));
	
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
			Loadjs('mimedefang.quarantine.query.resend.php?id={$_GET["id"]}');
		}
	</script>		
				
				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function download(){
	
	$q=new postgres_sql();
	$zmd5=$_GET["download"];
	$sql="SELECT contentid FROM quardata WHERE msgmd5='$zmd5'";
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
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$to=$tpl->_ENGINE_parse_body("{recipients}");
	$title=$tpl->_ENGINE_parse_body("&laquo;{quarantine}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$zdate=$tpl->javascript_parse_text("{date}");
	$size=$tpl->javascript_parse_text("{size}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	
	
	],	";
	$buttons=null;
	
	$html="
	<table class='QURANTINE_MESSAGES_TABLE' style='display: none' id='QURANTINE_MESSAGES_TABLE' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#QURANTINE_MESSAGES_TABLE').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
	
		{display: '<span style=font-size:18px>$zdate</span>', name : 'zdate', width :158, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$from</span>', name : 'mailfrom', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'mailto', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$subject</span>', name : 'subject', width :615, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'size', width :120, sortable : true, align: 'right'},
		{display: '<span style=font-size:18px>&nbsp;</span>', name : 'action', width :50, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},
		{display: '$subject', name : 'subject'},
	],
	sortname: 'zdate',
	sortorder: 'desc',
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


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#QURANTINE_MESSAGES_TABLE').flexReload();
}

function NewGItem$t(){
	YahooWin('650','$page?rulemd5=&t=$t','$new_entry');
	
}
function GItem$t(zmd5,ttile){
	YahooWin('650','$page?rulemd5='+zmd5+'&t=$t',ttile);
	
}

var x_DeleteAutCompress$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowC'+mem$t).remove();
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
	$q=new postgres_sql();
	$users=new usersMenus();	
	
	$search='%';
	$table="quarmsg";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$users->AsPostfixAdministrator){
		if($users->AsMessagingOrg){
			$ldap=new clladp();
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			
			while (list ($domain,$MAIN) = each ($domains) ){
				$domain=trim(strtolower($domain));
				if($domain==null){continue;}
				$FDOMS[]="domainto='$domain'";
				$FDOMS2[]="domainfrom='$domain'";
			}
			$imploded1=@implode(" OR ", $FDOMS);
			$imploded2=@implode(" OR ", $FDOMS2);
			$table="(select * FROM quarmsg WHERE ($imploded1) OR ($imploded2)) as t";
		}
		
	}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexPostGresquery();
	$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
	$ligne=pg_fetch_array($q->QUERY_SQL($sql));
	$total = $ligne["tcount"];
		
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	$sql="SELECT *  FROM $table WHERE $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(pg_num_rows($results)==0){json_error_show("no rule");}
	while ($ligne = pg_fetch_assoc($results)) {
	$id=$ligne["id"];
	$color="#000000";
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$delete=imgsimple("delete-24.png","","Loadjs('$MyPage?delete-message-js=$id')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?zoom-js=yes&id=$id');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";

	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["zdate"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["subject"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["size"]}</a></span>",
			"<center style='font-size:16px;color:$color'>$delete</a></center>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function disclaimer(){
	
	$zmd5=$_GET["disclaimer"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];

	
	
	$array["diclaimers-rule"]='{rule}';
	
	while (list ($num, $ligne) = each ($array) ){

		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&zmd5=$zmd5&t=$t\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	$width="750px";
	$height="600px";
	$width="100%";$height="100%";
	
	echo "
	<div id=main_config_mimedefang_autozip style='width:{$width};height:{$height};overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mimedefang_autozip').tabs();
			
			
			});
		</script>";		
}

function main_rule(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Addisclaimer$t();","18px");
	$ID=intval($_GET["ID"]);
	
	if($ID>0){
		$btname=null;
		$sql="SELECT * FROM mimedefang_backup WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	
	$times[10080]="7 {days}";
	$times[14400]="10 {days}";
	$times[21600]="15 {days}";
	$times[43200]="1 {month}";
	$times[129600]="3 {months}";
	
	
	$html="
	
	
	 <table style='width:99%' class=form>
	 <tr>
	 	<td class=legend style='font-size:22px'>{sender}:</td>
	 	<td>". Field_text("mailfrom-$t",$ligne["mailfrom"],"font-size:22px;width:310px")."</td>
	 </tr>
	 <tr>
	 	<td class=legend style='font-size:22px'>{recipient}:</td>
	 	<td>". Field_text("mailto-$t",$ligne["mailto"],"font-size:22px;width:310px",null,null,null,false,"AddisclaimerC$t(event)")."</td>
	 </tr>	
	 <tr>
	 	<td class=legend style='font-size:22px'>{retention}:</td>
	 	<td style='font-size:16px'>". Field_array_Hash($times, "retentiontime-$t",$ligne["retentiontime"],"style:font-size:22px")."</td>
	 </tr> 		 
	<tr>
		<td colspan=2 align='right'><hr>$btname</td>
	</tr>
	</table>
	<script>
		var x_Addisclaimer$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t-adddis').innerHTML='';
			$('#QURANTINE_MESSAGES_TABLE').flexReload();
			YahooWinHide();
		}		

		function AddisclaimerC$t(e){
			if(checkEnter(e)){Addisclaimer$t();}
		}
	
		function Addisclaimer$t(){
		var XHR = new XHRConnection();  
		  XHR.appendData('ID','$ID');
		  var uncompress=0;
	      XHR.appendData('mailfrom',document.getElementById('mailfrom-$t').value);
	      XHR.appendData('mailto',document.getElementById('mailto-$t').value);
	      XHR.appendData('retentiontime',document.getElementById('retentiontime-$t').value);
	      XHR.sendAndLoad('$page', 'POST',x_Addisclaimer$t);
		}
		

	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function autocompress_rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mimedefang_backup WHERE ID='{$_POST["ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}





