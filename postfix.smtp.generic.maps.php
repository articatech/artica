<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.maincf.multi.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["item-js"])){item_js();exit;}
	if(isset($_GET["item-delete-js"])){item_js_delete();exit;}
	if(isset($_GET["item-popup"])){item_popup();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["table-list"])){main_search();exit;}
	if(isset($_POST["source_pattern"])){smtp_generic_map_add();exit;}
	if(isset($_POST["delete"])){smtp_generic_map_del();exit;}
		
	main_table();

	
if(isset($_GET["item_js"])){item_js();exit;}	
	
function item_js_delete(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=$_GET["item-delete-js"];
	$ou=base64_decode($_GET["ou"]);
	$title=$tpl->javascript_parse_text("{delete}")."::".base64_decode($_GET["ou"]);
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM smtp_generic_maps WHERE ID='$ID'","artica_backup"));
		$generic_from=$ligne["generic_from"];
		$generic_to=$ligne["generic_to"];
		$title=$title." $generic_from >> $generic_to";
	}
	
echo "
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#SMTP_GENERIC_MAPS').flexReload();
}	

		
function Save$t(){
	var XHR = new XHRConnection();
	
	if(!confirm('$title')){return;}
	
	XHR.appendData('delete','$ID');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();";
}


function item_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$ou=base64_decode($_GET["ou"]);
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_rule}")."::".base64_decode($_GET["ou"]);
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM smtp_generic_maps WHERE ID='$ID'","artica_backup"));
		$generic_from=$ligne["generic_from"];
		$generic_to=$ligne["generic_to"];
		$title=$title." $generic_from >> $generic_to";
	}
	echo "YahooWin4('850','$page?item-popup=yes&ID=$ID&ou={$_GET["ou"]}','$title');";	
	
	
}

function item_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$bt="{add}";
	$ID=$_GET["ID"];
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM smtp_generic_maps WHERE ID='$ID'","artica_backup"));
		$bt="{save}";
	}
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$html="<div class=explain style='font-size:18px'>{smtp_generic_maps_text}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{source_pattern}","position:down:{smtp_generic_maps_explain}").":</td>
		<td>". Field_text("source_pattern",$ligne["generic_from"],"font-size:22px;padding:3px",null,null,null,false,
				"smtp_generic_map_add_check$t(event)")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>". texttooltip("{destination_pattern}","position:down:{smtp_generic_maps_explain}").":</td>
		<td>". Field_text("destination_pattern",$ligne["generic_to"],"font-size:22px;padding:3px",null,null,null,false,
				"smtp_generic_map_add_check$t(event)")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{outgoing_mails_only}:</td>
		<td>". Field_checkbox_design("smtp_generic_maps-$t", 1,$ligne["smtp_generic_maps"],"smtp_generic_map_check$t()")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' nowrap >{sender_address}:</td>
		<td>". Field_checkbox_design("sender_canonical_maps-$t", 1,$ligne["sender_canonical_maps"],"sender_canonical_maps_check$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{recipient_address}:</td>
		<td>". Field_checkbox_design("recipient_canonical_maps-$t", 1,$ligne["recipient_canonical_maps"],"sender_canonical_maps_check$t()")."</td>
	</tr>							
				
				
				
	<tr>
		<td colspan=2 align='right' style='padding-top:30px'><hr>".button($bt,"smtp_generic_map_add$t()",36)."</td>
</tr>
</table>
</div>
<script>
var x_smtp_generic_map_add$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    YahooWin4Hide();
    $('#SMTP_GENERIC_MAPS').flexReload();
}	

function smtp_generic_map_add_check$t(e){
	if(checkEnter(e)){smtp_generic_map_add$t();}
}
		
function smtp_generic_map_add$t(){
	var XHR = new XHRConnection();
	var src=document.getElementById('source_pattern').value;
	var dest=document.getElementById('source_pattern').value;
	if(src.length==0){alert('please specify source pattern');return;}
	if(dest.length==0){alert('please specify destination pattern');return;}
	XHR.appendData('ID','{$_GET["ID"]}');
	
	if(document.getElementById('smtp_generic_maps-$t').checked){
		XHR.appendData('smtp_generic_maps',1);
	}else{
		XHR.appendData('smtp_generic_maps',0);
		
	}
	if(document.getElementById('smtp_generic_maps-$t').checked){
		XHR.appendData('smtp_generic_maps',1);
	}else{
		XHR.appendData('smtp_generic_maps',0);
		
	}
	if(document.getElementById('sender_canonical_maps-$t').checked){
		XHR.appendData('sender_canonical_maps',1);
	}else{
		XHR.appendData('sender_canonical_maps',0);
		
	}		
	if(document.getElementById('recipient_canonical_maps-$t').checked){
		XHR.appendData('recipient_canonical_maps',1);
	}else{
		XHR.appendData('recipient_canonical_maps',0);
		
	}	

	
	XHR.appendData('source_pattern',document.getElementById('source_pattern').value);
	XHR.appendData('destination_pattern',document.getElementById('destination_pattern').value);
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.sendAndLoad('$page', 'POST',x_smtp_generic_map_add$t);
}


function smtp_generic_map_check$t(){
	if(document.getElementById('smtp_generic_maps-$t').checked){
		document.getElementById('sender_canonical_maps-$t').checked=false;
		document.getElementById('recipient_canonical_maps-$t').checked=false;
		document.getElementById('sender_canonical_maps-$t').disabled=true;
		document.getElementById('recipient_canonical_maps-$t').disabled=true;
	}else{
		document.getElementById('sender_canonical_maps-$t').disabled=false;
		document.getElementById('recipient_canonical_maps-$t').disabled=false;
	}
}

function sender_canonical_maps_check$t(){
	var a=0;
	if(document.getElementById('sender_canonical_maps-$t').checked){a=1;}
	if(document.getElementById('recipient_canonical_maps-$t').checked){a=1;}
	if(a==1){
		document.getElementById('smtp_generic_maps-$t').checked=false;
		document.getElementById('smtp_generic_maps-$t').disabled=true;
	}else{
		document.getElementById('smtp_generic_maps-$t').disabled=false;
	}
}

sender_canonical_maps_check$t();
smtp_generic_map_check$t();			
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
function main_table(){
		$t=time();
		$page=CurrentPageName();
		$tpl=new templates();
		$users=new usersMenus();
		$sock=new sockets();
		if(!isset($_GET["ou"])){$_GET["ou"]=base64_encode("POSTFIX_MAIN");}
	
		$q=new mysql();
		
	
		$t=time();
		$source_pattern=$tpl->_ENGINE_parse_body("{source_pattern}");
		$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
		$destination_pattern=$tpl->javascript_parse_text("{destination_pattern}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$title=$tpl->_ENGINE_parse_body("{smtp_generic_maps}");
		
		
		$destination=$tpl->javascript_parse_text("{destination}");
		$hostname=$_GET["hostname"];
		$apply=$tpl->javascript_parse_text("{apply}");
		$about2=$tpl->javascript_parse_text("{about2}");
		$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
		$recipient=$tpl->javascript_parse_text("{recipient}");
		$sender=$tpl->javascript_parse_text("{sender}");
		$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : new_rule$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : apply$t},
		{name: '<strong style=font-size:18px>$about2</strong>', bclass: 'help', onpress : Help$t},
		],";
	
		$explain=$tpl->javascript_parse_text("{smtp_generic_maps_text}");
		$html="

		<table class='SMTP_GENERIC_MAPS' style='display: none' id='SMTP_GENERIC_MAPS' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#SMTP_GENERIC_MAPS').flexigrid({
		url: '$page?table-list=yes&ou={$_GET["ou"]}&t=$t',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>$source_pattern</span>', name : 'generic_from', width : 428, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$destination_pattern</span>', name : 'generic_to', width :428, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>SMTP</span>', name : 'smtp_generic_maps', width :100, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$recipient</span>', name : 'recipient_canonical_maps', width :100, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$sender</span>', name : 'sender_canonical_maps', width :100, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 131, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$source_pattern', name : 'generic_from'},
		{display: '$destination_pattern', name : 'generic_to'},
		],
		sortname: 'generic_from',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: '550',
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	function  Help$t(){
	alert('$explain');
	}
	
	var RefreshTable$t= function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	$('#SMTP_GENERIC_MAPS').flexReload();
	}
	
	function MoveSubRuleLinks$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('item-move', mkey);
	XHR.appendData('direction', direction);
	XHR.appendData('hostname', '$hostname');
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
	}
	
	function new_rule$t(){
	Loadjs('$page?item-js=yes&ID=0&ou={$_GET["ou"]}');
	}
	
	function apply$t(){
	Loadjs('postfix.smtp.generic.maps.progress.php');
	
	}
	
	</script>
	";
	
		echo $html;
	
	
	}	

	
function smtp_generic_map_add(){
	$ou=base64_decode($_POST["ou"]);
	$ID=intval($_POST["ID"]);
	$md5=md5($_POST["source_pattern"]."$ou{$_POST["smtp_generic_maps"]}{$_POST["recipient_canonical_maps"]}{$_POST["sender_canonical_maps"]}");
	$q=new mysql();
	
	
	 
	
	if(!$q->FIELD_EXISTS("smtp_generic_maps","smtp_generic_maps","artica_backup")){
		$sql="ALTER TABLE `smtp_generic_maps` ADD `smtp_generic_maps` smallint(1)  NOT NULL DEFAULT '1',
				ADD INDEX ( `smtp_generic_maps` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	if(!$q->FIELD_EXISTS("smtp_generic_maps","recipient_canonical_maps","artica_backup")){
		$sql="ALTER TABLE `smtp_generic_maps` ADD `recipient_canonical_maps` smallint(1)  NOT NULL DEFAULT '0',
				ADD INDEX ( `recipient_canonical_maps` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}	
	if(!$q->FIELD_EXISTS("smtp_generic_maps","sender_canonical_maps","artica_backup")){
		$sql="ALTER TABLE `smtp_generic_maps` ADD `sender_canonical_maps` smallint(1)  NOT NULL DEFAULT '0',
				ADD INDEX ( `sender_canonical_maps` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}	
	
	if($ID==0){
		$sql="INSERT INTO smtp_generic_maps (generic_from,generic_to,ou,zmd5,smtp_generic_maps,recipient_canonical_maps,sender_canonical_maps)
		VALUES('{$_POST["source_pattern"]}','{$_POST["destination_pattern"]}','$ou','$md5',
		'{$_POST["smtp_generic_maps"]}','{$_POST["recipient_canonical_maps"]}','{$_POST["sender_canonical_maps"]}'
		
		);";
	}else{
		$sql="UPDATE smtp_generic_maps SET generic_from='{$_POST["source_pattern"]}',
		generic_to='{$_POST["destination_pattern"]}',
		zmd5='$md5',
		sender_canonical_maps='{$_POST["sender_canonical_maps"]}',
		recipient_canonical_maps='{$_POST["recipient_canonical_maps"]}',
		smtp_generic_maps='{$_POST["smtp_generic_maps"]}'
		WHERE ID=$ID";
		
	}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){echo $q->mysql_error;return ;}
	//$sock=new sockets();
	//$sock->getFrameWork("cmd.php?postfix-hash-smtp-generic=yes");
	
}

function smtp_generic_map_del(){
	$ou=base64_decode($_POST["ou"]);
	$sql="DELETE FROM smtp_generic_maps WHERE ID='{$_POST["delete"]}' AND ou='$ou'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return ;}	
	//$sock=new sockets();
	//$sock->getFrameWork("cmd.php?postfix-hash-smtp-generic=yes");
	
	
}

function main_search(){
	$MyPage=CurrentPageName();
	$main=new maincf_multi();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$t=$_GET["t"];
	$ou=base64_decode($_GET["ou"]);

	$searchstring=string_to_flexquery();
	$page=1;
	$table="(SELECT * FROM smtp_generic_maps WHERE ou='$ou' ORDER BY generic_from) as t";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";


	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";

		$ColorTime="black";
		

		$icon_grey="ok32-grey.png";
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_f=$icon_grey;
		//if($ligne["enabled"]==0){$ColorTime="#8a8a8a";}
		$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline;color:$ColorTime'";
		$style=" style='font-size:{$fontsize}px;color:$ColorTime'";


		$urijs="Loadjs('$MyPage?item-js=yes&ID={$ligne["ID"]}&ou={$_GET["ou"]}');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?item-delete-js={$ligne["ID"]}&ou={$_GET["ou"]}')");


		$sender_canonical_maps =$icon_grey;
		$recipient_canonical_maps =$icon_grey;
		$smtp_generic_maps=$icon_grey;
		if($ligne["sender_canonical_maps"]==1){$sender_canonical_maps=$icon;}
		if($ligne["smtp_generic_maps"]==1){$smtp_generic_maps=$icon;}
		if($ligne["recipient_canonical_maps"]==1){$recipient_canonical_maps=$icon;}

		$cell=array();
		$cell[]="<span $style>$link{$ligne["generic_from"]}</a></span>";
		$cell[]="<span $style>$link{$ligne["generic_to"]}</a></span>";
		$cell[]="<center $style><img src='img/$smtp_generic_maps'></a></center>";
		$cell[]="<center $style><img src='img/$recipient_canonical_maps'></a></center>";
		$cell[]="<center $style><img src='img/$sender_canonical_maps'></a></center>";
		$cell[]="<center $style>$delete</a></center>";

		$data['rows'][] = array(
				'id' => $ligne['uuid'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}
	


?>