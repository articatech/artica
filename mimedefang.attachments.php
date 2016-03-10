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
	

function zoom_js(){
	header("content-type: application/x-javascript");
	$q=new postgres_sql();
	$tpl=new templates();
	$page=CurrentPageName();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='{$_GET["id"]}'"));
	$subject=$ligne["subject"];
	echo "YahooWin2(990,'$page?zoom-popup=yes&id={$_GET["id"]}','$subject')";
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
	$title=$tpl->_ENGINE_parse_body("&laquo;{attachments}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$content_type=$tpl->_ENGINE_parse_body("{content_type}");
	$zdate=$tpl->javascript_parse_text("{date}");
	$size=$tpl->javascript_parse_text("{size}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry</strong>', bclass: 'Add', onpress : NewGItem$t},
	
	
	],	";
	$buttons=null;
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
	
		{display: '<span style=font-size:18px>$zdate</span>', name : 'zdate', width :158, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$from</span>', name : 'mailfrom', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$to</span>', name : 'mailto', width :224, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$filename</span>', name : 'fname', width :403, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$content_type</span>', name : 'contenttype', width :246, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'size', width :120, sortable : true, align: 'right'},
		

	],
	$buttons

	searchitems : [
		{display: '$from', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},
		{display: '$filename', name : 'fname'},
		{display: '$content_type', name : 'contenttype'},
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
    $('#flexRT$t').flexReload();
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
	$table="attachstats";
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
			$table="(select * FROM attachstats WHERE ($imploded1) OR ($imploded2)) as t";
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
	$urljs=null;
	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["zdate"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailfrom"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["mailto"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["fname"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["contenttype"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["size"]}</a></span>",
			
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
			$('#flexRT$t').flexReload();
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





