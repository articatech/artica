<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');



	if(isset($_GET["disclaimer"])){disclaimer();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}
	if(isset($_GET["disclaimers-text"])){disclaimer_text();exit;}
	if(isset($_GET["disclaimers-html"])){disclaimer_html();exit;}
	
	if(isset($_POST["textcontent"])){disclaimer_text_save();exit;}
	if(isset($_POST["htmlcontent"])){disclaimer_htmlcontent_save();exit;}
	
	if(isset($_POST["mailfrom"])){disclaimer_rule_add();exit;}
	if(isset($_POST["del-zmd5"])){disclaimer_rule_delete();exit;}
	popup();

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
	$title=$tpl->_ENGINE_parse_body("{rules}:&nbsp;&laquo;{disclaimers}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	
	
	],	";
	
	
	$html="
	<table class='DISCLAIMERS_RULES_TABLE' style='display: none' id='DISCLAIMERS_RULES_TABLE' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#DISCLAIMERS_RULES_TABLE').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '<span style=font-size:18px>$from</span>', name : 'mailfrom', width :600, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'mailto', width :600, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :80, sortable : false, align: 'center'},

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


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#DISCLAIMERS_RULES_TABLE').flexReload();
}

function NewGItem$t(){
	YahooWin('900','$page?disclaimer=&t=$t','$new_entry');
	
}
function GItem$t(zmd5,ttile){
	YahooWin('900','$page?disclaimer='+zmd5+'&t=$t',ttile);
	
}

var x_DeleteDisclaimer$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}

function GroupAmavisExtEnable(id){
	var value=0;
	if(document.getElementById('gp'+id).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-gp',id);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}


function DeleteDisclaimer$t(md5){
	if(confirm('$ask_delete_rule')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-zmd5',md5);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteDisclaimer$t);		
	
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
		
	
	$search='%';
	$table="mimedefang_disclaimer";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$users->AsPostfixAdministrator){
		if($users->AsMessagingOrg){
			$ldap=new clladp();
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
				
			while (list ($domain,$MAIN) = each ($domains) ){
				$domain=trim(strtolower($domain));
				if($domain==null){continue;}
				$FDOMS[]="mailto LIKE '%$domain'";
				$FDOMS2[]="mailfrom LIKE '%$domain'";
			}
			$imploded1=@implode(" OR ", $FDOMS);
			$imploded2=@implode(" OR ", $FDOMS2);
			$table="(select * FROM mimedefang_disclaimer WHERE ($imploded1) OR ($imploded2)) as t";
		}
	
	}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=$ligne["zmd5"];

	$color="black";
	$delete=imgsimple("delete-24.png","","DeleteDisclaimer$t('$zmd5')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:GItem$t('$zmd5','{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;{$ligne["mailto"]}');\"
	style='font-size:22px;color:$color;text-decoration:underline'>";
	
	
	$data['rows'][] = array(
			
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:22px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:22px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
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
	if(strlen($zmd5)>10){
		$array["disclaimers-text"]='{text_mode}';
		$array["disclaimers-html"]='{html_mode}';
		
	}
	
	while (list ($num, $ligne) = each ($array) ){

		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&zmd5=$zmd5&t=$t\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	
	$width="750px";
	$height="600px";
	$width="100%";$height="100%";
	
	echo build_artica_tabs($html, "main_config_mimedefang_discl");

}

function disclaimer_rule(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Addisclaimer$t();","32px");
	$zmd5=$_GET["zmd5"];
	
	if($zmd5<>null){
		$btname=null;
		$sql="SELECT * FROM mimedefang_disclaimer WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	
	$html="
	<div style='font-size:18px;margin:20px' class=explain>{mimedefang_email_explain}</div>
	 <table style='width:98%' class=form>
	 <tr>
	 	<td class=legend style='font-size:22px'>{sender}:</td>
	 	<td>". Field_text("mailfrom-$t",$ligne["mailfrom"],"font-size:22px;width:90%")."</td>
	 </tr>
	 <tr>
	 	<td class=legend style='font-size:22px'>{recipient}:</td>
	 	<td>". Field_text("mailto-$t",$ligne["mailto"],"font-size:22px;width:90%",null,null,null,false,"AddisclaimerC$t(event)")."</td>
	 </tr>	
	<tr>
		<td colspan=2 align='right'><hr>$btname</td>
	</tr>
	</table>
	<script>
var x_Addisclaimer$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#DISCLAIMERS_RULES_TABLE').flexReload();
	var zmd5='$zmd5';
	if(zmd5.length==0){
		YahooWinHide();
	}
}		

function AddisclaimerC$t(e){
	if(checkEnter(e)){Addisclaimer$t();}
}
	
function Addisclaimer$t(){
	var XHR = new XHRConnection();  
	XHR.appendData('zmd5','$zmd5');
	XHR.appendData('mailfrom',document.getElementById('mailfrom-$t').value);
	XHR.appendData('mailto',document.getElementById('mailto-$t').value);
	XHR.sendAndLoad('$page', 'POST',x_Addisclaimer$t);
}
		
function AddisclaimerCheck$t(){
	var zmd5='$zmd5';
	if(zmd5.length>5){
		document.getElementById('mailfrom-$t').disabled=true;
		document.getElementById('mailto-$t').disabled=true;
	}
}
	
AddisclaimerCheck$t();
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function disclaimer_rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mimedefang_disclaimer WHERE zmd5='{$_POST["del-zmd5"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function disclaimer_rule_add(){
	
	$tpl=new templates();
	$_POST["mailfrom"]=trim(strtolower($_POST["mailfrom"]));
	$_POST["mailto"]=trim(strtolower($_POST["mailto"]));
	if($_POST["mailto"]==null){$_POST["mailto"]="*";}
	if($_POST["mailfrom"]==null){echo $tpl->javascript_parse_text("{please_define_sender}");return;}
	$zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO mimedefang_disclaimer (zmd5,mailfrom,mailto) 
	VALUES ('$zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}

function disclaimer_text(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Addisclaimer$t();","18px");
	$zmd5=$_GET["zmd5"];
	
	if($zmd5<>null){
		$btname=null;
		$sql="SELECT textcontent FROM mimedefang_disclaimer WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	if($ligne["textcontent"]==null){
		$ligne["textcontent"]="Any views or opinions presented in this email are solely those of the author and do not necessarily represent those of the company.\nEmployees of [Company] are expressly required not to make defamatory statements and not to infringe or authorize any infringement of copyright or any other legal right by email communications.\nAny such communication is contrary to company policy and outside the scope of the employment of the individual concerned.\nThe company will not accept any liability in respect of such communication, and the employee responsible will be personally liable for any damages or other liability arising.";
	}

	$html="
		<div style='font-size:22px'>{put_text_content_here}</div>
	<div id='$t-adddis'></div>
	<table style='width:99%;' class=form>
	<tr>
		<td>
		<textarea 
			style='margin-top:5px;font-family:Courier New;font-weight:bold;
			width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;
			font-size:18px !important' id='textcontent$t'>{$ligne["textcontent"]}</textarea>
		</td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}", "SaveDiclaimerText$t()","30")."</td>
	</tr>
	</table>
	<script>
		var x_SaveDiclaimerText$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t-adddis').innerHTML='';
			RefreshTab('main_config_mimedefang_discl');
		}		
	
		function SaveDiclaimerText$t(){
		var XHR = new XHRConnection();  
		  XHR.appendData('zmd5','$zmd5');
	      XHR.appendData('textcontent',document.getElementById('textcontent$t').value);
		  AnimateDiv('$t-adddis');
		  XHR.sendAndLoad('$page', 'POST',x_SaveDiclaimerText$t);
		}	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}
function disclaimer_html(){
	$t=$_GET["t"];
	$tt=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$zmd5=$_GET["zmd5"];
	
	if($zmd5<>null){
		$btname=null;
		$sql="SELECT htmlcontent FROM mimedefang_disclaimer WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	if($ligne["textcontent"]==null){
		$ligne["htmlcontent"]="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 TRANSITIONAL//EN\">\n<HTML>\n<HEAD>\n<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; CHARSET=UTF-8\">\n<META NAME=\"GENERATOR\" CONTENT=\"GtkHTML/3.28.3\">\n</HEAD>\n<BODY>\n<div style=\"font-size:12px;font-weight:normal;font-style:italic\">\n<hr>\n Any views or opinions presented in this email are solely those of the author \n and do not necessarily represent those of the company.<br>\n Employees of <strong>[Company]</strong> are expressly required not to make defamatory \n statements and not to infringe or authorize any infringement of copyright or any other legal \n right by email communications.<br>\n Any such communication is contrary to company policy and outside the scope of the employment of \n the individual concerned.<br>\n The company will not accept any liability in respect of such communication, and the employee \n responsible will be personally liable for any damages or other liability arising.\n<hr>\n</div>\n  </BODY>\n  </HTML>";
	}

	$html="
	<div style='font-size:22px'>{put_html_code_here}</div>

	<table style='width:99%;' class=form>
	<tr>
		<td><textarea 
			style='margin-top:5px;font-family:Courier New;font-weight:bold;
			width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;
			font-size:18px !important' id='textcontent$tt'>{$ligne["htmlcontent"]}</textarea></td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}", "SaveDiclaimerText$tt()","30")."</td>
	</tr>
	</table>
	<script>
		var x_SaveDiclaimerText$tt= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('main_config_mimedefang_discl');
		}		
	
		function SaveDiclaimerText$tt(){
		var XHR = new XHRConnection();  
		  XHR.appendData('zmd5','$zmd5');
	      XHR.appendData('htmlcontent',document.getElementById('textcontent$tt').value);
		  XHR.sendAndLoad('$page', 'POST',x_SaveDiclaimerText$tt);
		}	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
}
function disclaimer_text_save(){
	
	$sql="UPDATE mimedefang_disclaimer SET textcontent='{$_POST["textcontent"]}' WHERE zmd5='{$_POST["zmd5"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function disclaimer_htmlcontent_save(){
	
	$sql="UPDATE mimedefang_disclaimer SET htmlcontent='{$_POST["htmlcontent"]}' WHERE zmd5='{$_POST["zmd5"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}