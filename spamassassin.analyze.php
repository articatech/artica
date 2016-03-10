<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.spamassassin.inc');
	include_once('ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
	$user=new usersMenus();
		if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["messages"])){messages();exit;}
	if(isset($_GET["add"])){messages_add();exit;}
	if(isset($_POST["upload-message"])){message_upload();exit;}
	if(isset($_GET["messages-list"])){messages_table();exit;}
	if(isset($_GET["messages-search"])){messages_search();exit;}
	
	if(isset($_GET["show-results"])){message_results();exit;}
	if(isset($_GET["analyze-message"])){message_analyze();exit;}
	if(isset($_GET["delete-message"])){message_delete();exit;}
	
	
tabs();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{APP_SPAMASSASSIN}::{message_analyze}");		
	echo "YahooWin3('700','$page?tabs=yes','$title');";	
	
}

function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["add"]='{message_analyze}';
	$array["messages-list"]='{messages_list}';
	
	
	

	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:26px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_spamass_analyzemess",1490);

	
}




function messages_add(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div style='font-size:26px;color:#d32d2d' id='post-message-results'></div>
	<div class=explain style='font-size:18px'>{spamass_analyze_post_explain}</div>
	<hr>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{from}:</td>
		<td>". Field_text("amavid-sender",null,"font-size:22px;padding:3px;width:97%")."</td>
	</tr>
		<td valign='top' class=legend style='font-size:22px'>{recipients}:</td>
		<td><textarea id='amavid-recipients' style='width:100%;height:60px;overflow:auto;font-size:22px !important'></textarea></td>
	</tr>
	<td colspan=2>
		<div style='margin-bottom:10px;margin-top:20px;font-size:22px'>{source_message_explain}:</div>
		<textarea id='spamass_message' placeholder='{source_message_explain}' style='width:100%;height:250px;overflow:auto;font-size:7px'></textarea>
	</td>
	</tr>
	</table>
	<hr>
	<center>". button("{submit}","spamass_message_upload()",40)."</center>
	
	<script>
var X_spamass_message_upload= function (obj) {
		var results=obj.responseText;
		document.getElementById('post-message-results').innerHTML=results;
		
	}		
function spamass_message_upload(){
		var XHR = new XHRConnection();
		XHR.appendData('upload-message','yes');
		XHR.appendData('message',document.getElementById('spamass_message').value);
		XHR.appendData('sender',document.getElementById('amavid-sender').value);
		XHR.appendData('recipients',document.getElementById('amavid-recipients').value);
		document.getElementById('post-message-results').innerHTML='analyze....';
		XHR.sendAndLoad('$page', 'POST',X_spamass_message_upload);
		}
		
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function message_upload(){
	
			$mime=new mime_parser_class();
			$mime->decode_bodies = 0;
			$mime->ignore_syntax_errors = 1;	
			$parameters['Data']=$_POST["message"];
			$parameters['SkipBody']=1;
			$decoded=array();
			$mime->Decode($parameters, $decoded);
			$subject=addslashes($decoded[0]["Headers"]["subject:"]);	
	
	
	$_POST["message"]=mysql_escape_string2($_POST["message"]);
	$q=new mysql();
	$date=date('Y-m-d H:i:s');
	$sql="
	INSERT INTO `amavisd_tests` (`sender`,`recipients`,`message`,`saved_date`,`subject`) 
	VALUES ('{$_POST["sender"]}','{$_POST["recipients"]}','{$_POST["message"]}','$date','$subject')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$size=FormatBytes((strlen($_POST["message"])/1024));
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success} {message_size}:$size<br>$subject");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?spamass-test=yes");
	
	
}

function messages_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$sender=$tpl->_ENGINE_parse_body("{sender}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$recipients=$tpl->_ENGINE_parse_body("{recipients}");
	$t=time();

	
	$buttons="	buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : GlobalBlackListAdd$t},
	],";
	$buttons=null;
	
	$popup_title=$tpl->_ENGINE_parse_body("{APP_SPAMASSASSIN}::{message_analyze}");
	$html="<table class='SPAMASSASSIN_ANALYZE_TABLE' style='display: none' id='SPAMASSASSIN_ANALYZE_TABLE' style='width:99%'></table>
	<script>
	var mem_$t='';
	var selected_id=0;
	$(document).ready(function(){
	$('#SPAMASSASSIN_ANALYZE_TABLE').flexigrid({
	url: '$page?messages-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$date</span>', name : 'saved_date', width : 157, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$sender</span>', name : 'sender', width : 252, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$recipients</span>', name : 'recipients', width : 252, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$subject</span>', name : 'subject', width : 285, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$status</span>', name : 'finish', width : 180, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width : 110, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none2', width : 110, sortable : false, align: 'center'},

	],
$buttons
	searchitems : [
	{display: '$date', name : 'saved_date'},
	{display: '$sender', name : 'value'},
	{display: '$recipients', name : 'recipients'},
	{display: '$subject', name : 'subject'},
	],
	sortname: 'saved_date',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$popup_title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});

function GlobalBlackListAdd$t(){
YahooWin4('890','$page?popup-global-black-add=yes&domain={$_GET["domain"]}&ou={$_GET["ou"]}','{$_GET["domain"]}::$popup_title');
}

function GlobalBlackRefresh(){
$('#table-$t').flexReload();
}

var x_GlobalBlackDelete$t= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+mem_$t).remove();
}

var x_GlobalBlackDisable= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}

}
var X_spamass_message_upload= function (obj) {
	var results=obj.responseText;
	$('#SPAMASSASSIN_ANALYZE_TABLE').flexReload();
}		
	
function spamass_ana_msg(ID){
	var XHR = new XHRConnection();
	XHR.appendData('analyze-message',ID);
	XHR.sendAndLoad('$page', 'GET',X_spamass_message_upload);
}	
		
function DeleteSpamTest(ID){
	var XHR = new XHRConnection();
	XHR.appendData('delete-message',ID);
	XHR.sendAndLoad('$page', 'GET',X_spamass_message_upload);	
}

function GlobalBlackDelete(key){
	var XHR = new XHRConnection();
	mem_$t=key;
	XHR.appendData('GlobalBlackDelete',key);
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDelete$t);
}

function GlobalBlackDisable(ID){
	var XHR = new XHRConnection();
	XHR.appendData('ID',ID);
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	if(document.getElementById('enabled_'+ID).checked){XHR.appendData('GlobalBlackDisable',1);}else{XHR.appendData('GlobalBlackDisable',0);}
	XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDisable);
}
	function SpamassShowMsgStatus(ID){
		YahooWin2(1024,'$page?show-results='+ID,'ID::'+ID);
	}
</script>
";

echo $html;

}


function messages_search(){
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$search='%';
	$table="amavisd_tests";
	$page=1;
	$total=0;
	$MyPage=CurrentPageName();
	
	if(!$q->TestingConnection()){json_error_show("Connection to MySQL server failed");}
	if($q->COUNT_ROWS("amavisd_tests","artica_backup")==0){json_error_show("no data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	$total = $ligne["TCOUNT"];

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();


	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	if(mysql_num_rows($results)==0){json_error_show("no row $sql",1);}
	$score=0;
	
	

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		
			if($ligne["subject"]==null){$ligne["subject"]="{subject}:{unknown}";}
			$recp=explode(",",$ligne["recipients"]);
			$rcpt_text=@implode($recp,"<br>");
			if($ligne["finish"]==0){$status="{scheduled}";}
			if($ligne["finish"]==1){
				$status="{analyzed}";
				$ahrf="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:SpamassShowMsgStatus({$ligne["ID"]})\"
				style='font-size:16px;font-weight:normal;text-decoration:underline'>";
			
			}
			$delete=imgtootltip("delete-32.png","{delete}","DeleteSpamTest({$ligne["ID"]})");
			$analyze=imgtootltip("refresh-32.png","{analyze}","spamass_ana_msg({$ligne["ID"]})");
			$status=$tpl->_ENGINE_parse_body($status);
			
			
			$data['rows'][] = array(
					'id' => $ligne['prefid'],
					'cell' => array(
							"<span style='font-size:16px'>$ahrf{$ligne["saved_date"]}</a></span>",
							"<span style='font-size:16px'>$ahrf{$ligne["sender"]}</a></span>",
							"<span style='font-size:16px'>$ahrf$rcpt_text</a></span>",
							"<span style='font-size:16px'>{$ligne["subject"]}</a></span>",
							"<span style='font-size:16px'>$ahrf$status</a></span>",
							"<center>$analyze</center>",
							"<center>$delete</center>"

							)
			);
			
	}
	echo json_encode($data);
	
}
function message_results(){
	$ID=$_GET["show-results"];
	if(!is_numeric($ID)){return null;}
	$sql="SELECT subject,amavisd_results FROM amavisd_tests WHERE ID=$ID";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$spamassassin_results=base64_decode($ligne["amavisd_results"]);
	$spamassassin_results=utf8_decode($spamassassin_results);
	$bytes=strlen($spamassassin_results);
	$tbl=explode("\n",$spamassassin_results);
			if(is_array($tbl)){
				while (list ($index, $line) = each ($tbl) ){
					$fontsize=16;
					$fontcolor="005447";
					$fontweight="normal";
					if(preg_match("#Content analysis details#i", $line)){
						$fontweight="bold";
						$fontsize="22";
						$fontcolor="d32d2d";
					}
					
					if(preg_match("#([0-9\.])+\s+([A-Z0-9_]+)\s+#", $line)){
						$fontweight="bold";
						$fontsize="22";
						$fontcolor="2975b8";
						}
							
						
					
					$line=htmlentities($line);
					$line=str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$line);
					$line=str_replace(" ","&nbsp;",$line);
					$content=$content."<div style='margin-top:5px;color:$fontcolor;font-weight:$fontweight'><code style='font-size:{$fontsize}px'>$line</code></div>\n";

					
				}
			
				
			
		}
		$bytes=FormatBytes($bytes/1024);
	$html="
	<div style='font-size:22px;margin-bottom:20px'>{$ligne["subject"]} $bytes</div>
	<hr>
	$content
	";
	
	echo $html;
	
}

function message_analyze(){
	$sql="UPDATE amavisd_tests SET finish=0 WHERE ID={$_GET["analyze-message"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?spamass-test={$_GET["analyze-message"]}");	
	
}

function message_delete(){
	$sql="DELETE FROM amavisd_tests WHERE ID={$_GET["delete-message"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");	
}



