<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["rulemd5"])){main_rule();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}

	
	
	if(isset($_POST["mailfrom"])){rule_add();exit;}
	if(isset($_POST["del-zmd5"])){rule_delete();exit;}
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
	$title=$tpl->_ENGINE_parse_body("{rules}:&nbsp;&laquo;{backupemail_behavior}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$retentiontime=$tpl->javascript_parse_text("{retention}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	],	";
	
	
	$html="
	<table class='MIMEDEFANG_BACKUP_TABLE' style='display: none' id='MIMEDEFANG_BACKUP_TABLE' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#MIMEDEFANG_BACKUP_TABLE').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '<span style=font-size:18px>$from</span>', name : 'mailfrom', width :442, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'mailto', width :442, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$retentiontime</span>', name : 'retentiontime', width :334, sortable : true, align: 'left'},
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


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#MIMEDEFANG_BACKUP_TABLE').flexReload();
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


function DeleteRule$t(md5){
	if(confirm('$ask_delete_rule: '+md5)){
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
		
	
	$search='%';
	$table="mimedefang_backup";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	

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
	
	$times[10080]=$tpl->javascript_parse_text("7 {days}");
	$times[14400]=$tpl->javascript_parse_text("10 {days}");
	$times[21600]=$tpl->javascript_parse_text("15 {days}");
	$times[43200]=$tpl->javascript_parse_text("1 {month}");
	$times[129600]=$tpl->javascript_parse_text("3 {months}");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=$ligne["ID"];
	$color="#000000";
	
	$delete=imgsimple("delete-32.png","","DeleteRule$t('$zmd5')");
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:GItem$t('$zmd5','{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;{$ligne["mailto"]}');\"
	style='font-size:26px;color:$color;text-decoration:underline'>";

	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$urljs{$times[$ligne["retentiontime"]]}</a></span>",
			"<center style='font-size:18px;color:$color'>$delete</a></center>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}



function main_rule(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$btname=button("{add}","Addisclaimer$t();","26px");
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
			$('#MIMEDEFANG_BACKUP_TABLE').flexReload();
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

function rule_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mimedefang_backup WHERE ID='{$_POST["del-zmd5"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rule_add(){
	
	$tpl=new templates();
	$_POST["mailfrom"]=trim(strtolower($_POST["mailfrom"]));
	$_POST["mailto"]=trim(strtolower($_POST["mailto"]));
	if($_POST["mailto"]==null){$_POST["mailto"]="*";}
	if($_POST["mailfrom"]==null){echo $tpl->javascript_parse_text("{please_define_sender}");return;}
	
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO mimedefang_backup (mailfrom,mailto,retentiontime) 
	VALUES ('{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["retentiontime"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}



