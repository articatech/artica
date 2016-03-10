<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.firehol.inc');

	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["interfaces"])){search();exit;}
	if(isset($_GET["service-js"])){service_js();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_GET["service-popup"])){service_popup();exit;}
	if(isset($_POST["linkservice"])){service_save();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_POST["service"])){create_service();exit;}
	
js();	
	

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{browse}:{services}");
	echo "YahooWin6('580','$page?popup=yes&xtable={$_GET["xtable"]}&nic={$_GET["nic"]}&SelectField={$_GET["SelectField"]}','$title')";
}


function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	
	$t=time();
	$interfaces=$tpl->_ENGINE_parse_body("{interfaces}");
	$server_port=$tpl->_ENGINE_parse_body("{ports}");
	$service=$tpl->_ENGINE_parse_body("{services}");
	$client_port=$tpl->_ENGINE_parse_body("{client_ports}");
	$saved_date=$tpl->_ENGINE_parse_body("{zDate}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$services=$tpl->_ENGINE_parse_body("{services}");
	$new_service=$tpl->_ENGINE_parse_body("{new_service}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$IPSET_INSTALLED=0;
	if($users->IPSET_INSTALLED){$IPSET_INSTALLED=1;}

	$TB_HEIGHT=500;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	//service,server_port,client_port,helper,enabled

	$t=time();

	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px>$new_service</strong>', bclass: 'Add', onpress : NewRule$t},
	
	],	";
	
	
	
	$html="
	<table class='FIREHOLE_SERVICES_TABLES' style='display: none' id='FIREHOLE_SERVICES_TABLES' style='width:99%'></table>
	<script>
	var IptableRow='';
	$(document).ready(function(){
	$('#FIREHOLE_SERVICES_TABLES').flexigrid({
	url: '$page?interfaces=yes&t=$t&xtable={$_GET["xtable"]}&nic={$_GET["nic"]}&SelectField={$_GET["SelectField"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$service</span>', name : 'service', width :136, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$server_port</span>', name : 'server_port', width :136, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$client_port</span>', name : 'client_port', width :136, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width :70, sortable : false, align: 'center'},
	

	],
	$buttons

	searchitems : [
	{display: '$service', name : 'service'},
	{display: '$server_port', name : 'server_port'},
	{display: '$client_port', name : 'client_port'},
	],

	sortname: 'service',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:20px>$service</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true

});
});

function NewRule$t(){
	Loadjs('$page?service-js=');
}

function Apply$t(){
	Loadjs('firehol.progress.php');
}

var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}').flexReload();
}
function FireWallAddNewNicService(service){
	var XHR = new XHRConnection();
	XHR.appendData('linkservice', service);
	XHR.appendData('table', '{$_GET["xtable"]}');
	XHR.appendData('nic', '{$_GET["nic"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}

function FireWallSelectFieldService(service){
	document.getElementById('{$_GET["SelectField"]}').value=service;
	YahooWin6Hide();
}

</script>";

	echo $html;
}

function delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM `firehol_services_def` WHERE `service`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_services` WHERE `service`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_client_services` WHERE `service`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_services_routers` WHERE `service`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
	$q->QUERY_SQL("DELETE FROM `firehol_routers_exclude` WHERE `service`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
	
	
	
}

function service_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$service=$_GET["service-js"];
	if($service==null){$title=$tpl->javascript_parse_text("{new_service}");}else{$title=$service;}
	echo "YahooWin('900','$page?service-popup=$service','$title')";
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]} ?");
	$t=time();
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREHOLE_SERVICES_TABLES').flexReload();
}
function Add$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
	}

function service_save(){
	
	
	$service=$_POST["linkservice"];
	$table=$_POST["table"];
	$md5=md5("{$_POST["nic"]}$service");
	
	$ADD="INSERT IGNORE INTO `$table` 
			(service,interface,allow_type,enabled,zmd5) 
			VALUES ('$service','{$_POST["nic"]}','1','1','$md5')";
	
	$q=new mysql();
	$q->QUERY_SQL($ADD,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$ADD\n";}
	
}

function service_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql();
	$service=trim($_GET["service-popup"]);
	$title="{new_service}";
	$bt="{add}";
	$textarea_style="style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:200px;border:5px solid #8E8E8E;
		overflow:auto;font-size:22px !important;width:99%;height:200px'";
	if($service<>null){
		$title=$service;
		$bt="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM firehol_services_def WHERE service='$service'","artica_backup"));
	}
	$enabled=$ligne["enabled"];
	
	if($ligne["client_port"]==null){$ligne["client_port"]="default";}

	if(!is_numeric($enabled)){$enabled=1;}
	if(trim($ligne["client_port"])==null){$ligne["client_port"]="default";}
	$ligne["server_port"]=str_replace(" ", "\n", $ligne["server_port"]);
	$ligne["client_port"]=str_replace(" ", "\n", $ligne["client_port"]);
	
	$html="
<div style='width:98%' class=form>
	<div style='font-size:36px;margin-bottom:25px;margin-top:10px;margin-left:5px'>{service2}: $title</div>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{service2}:</td>
			<td style='font-size:22px'>". Field_text("service-$t", $ligne["service"],"font-size:28px;width:560px")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:22px'>{enabled}:</td>
			<td style='font-size:22px'>". Field_checkbox_design("enabled-$t", 1,$enabled,"EnableCK$t()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;vertical-align:top'>{ports}:</td>
			<td style='font-size:22px'><textarea $textarea_style id='server_port-$t'>{$ligne["server_port"]}</textarea></td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;vertical-align:top'>{local_ports}:</td>
			<td style='font-size:22px'><textarea $textarea_style id='client_port-$t'>{$ligne["client_port"]}</textarea></td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button($bt,"Save$t()",32)."</td>
		</tr>
	</table>
</div>
<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		var ID='{$_GET["service-popup"]}';
		$('#FIREHOLE_SERVICES_TABLES').flexReload();
		$('#FIREHOLE_SERVICES_BRTABLES').flexReload();
		if (ID.length==0){ YahooWinHide();}
			
	}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('service',  encodeURIComponent(document.getElementById('service-$t').value));
	XHR.appendData('server_port',  encodeURIComponent(document.getElementById('server_port-$t').value));
	XHR.appendData('client_port',  encodeURIComponent(document.getElementById('client_port-$t').value));
	if(document.getElementById('enabled-$t').checked){ XHR.appendData('enabled',1); }else{ XHR.appendData('enabled',0); }
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function EnableCK$t(){
	var ID='{$_GET["service-popup"]}';
	 

	if(document.getElementById('enabled-$t').checked){
		document.getElementById('server_port-$t').disabled=false;
		document.getElementById('client_port-$t').disabled=false;
	}else{
		document.getElementById('server_port-$t').disabled=true;
		document.getElementById('client_port-$t').disabled=true;
	}
	document.getElementById('service-$t').disabled=true;
	if (ID.length==0){  document.getElementById('service-$t').disabled=false; }
	
}
	
EnableCK$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="nics";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}

	
	$searchstring=string_to_flexquery();
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$table="firehol_services_def";
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=16;
	$firehole=new firehol();
	
	// 	//service,server_port,client_port,helper,enabled
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$service=$ligne["service"];
		$server_port=$ligne["server_port"];
		$client_port=$ligne["client_port"];
		$enabled=$ligne["enabled"];
		$js="Loadjs('$MyPage?service-js=$service')";
		if($enabled==0){$color="#909090";}
		$delete=imgsimple("arrow-right-24.png",null,"FireWallAddNewNicService('$service')");
		if($_GET["SelectField"]<>null){
			$delete=imgsimple("arrow-right-24.png",null,"FireWallSelectFieldService('$service')");
			
		}

		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='color:$color;font-size:{$fontsize}px;text-decoration:underline'>";

		$data['rows'][] = array(
				'id' => $ligne["Interface"],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>$link$service</a></span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$link$server_port</a></span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$client_port</span>",
						"<center style='font-size:{$fontsize}px;color:$color'>$delete</center>",
						
						
							

				)
		);
	}


	echo json_encode($data);

}

function create_service(){


	while (list ($num, $dr) = each ($_POST)){$_POST[$num]=url_decode_special_tool($dr);}
	$_POST["service"]=str_replace("_", "", $_POST["service"]);
	$_POST["service"]=str_replace(" ", "", $_POST["service"]);
	$_POST["service"]=str_replace("-", "", $_POST["service"]);
	$_POST["service"]=replace_accents($_POST["service"]);

	$_POST["client_port"]=trim($_POST["client_port"]);
	$_POST["client_port"]=str_replace("\n", " ", $_POST["client_port"]);
	$_POST["client_port"]=str_replace("  ", " ", $_POST["client_port"]);

	$_POST["server_port"]=trim($_POST["server_port"]);
	$_POST["server_port"]=str_replace("\n", " ", $_POST["server_port"]);
	$_POST["server_port"]=str_replace("  ", " ", $_POST["server_port"]);

	reset($_POST);
	while (list ($num, $dr) = each ($_POST)){$_POST[$num]=mysql_escape_string2(($dr));}

	$ADD="INSERT IGNORE INTO `firehol_services_def`
	(service,server_port,client_port,helper,enabled) VALUES ('{$_POST["service"]}','{$_POST["server_port"]}','{$_POST["client_port"]}','','{$_POST["enabled"]}')";

	$EDIT="UPDATE firehol_services_def SET `client_port`='{$_POST["client_port"]}',
	server_port='{$_POST["server_port"]}',
	`enabled`='{$_POST["enabled"]}'
			WHERE `service`='{$_POST["service"]}'";


	$q=new mysql();
	// 1 = Allow, 0 = Deny
	$sql="SELECT `service`FROM `firehol_services_def` WHERE `service`='{$_POST["service"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$sql=$ADD;
			if($ligne["service"]<>null){
			$sql=$EDIT;
			}

			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n$sql\n";}

}