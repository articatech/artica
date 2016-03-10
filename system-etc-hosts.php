<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["add-form"])){popup_add();exit;}
	if(isset($_POST["ip_addr"])){host_add();exit;}
	if(isset($_GET["refresh"])){echo getlist();exit;}
	if(isset($_POST["host-delete"])){popup_delete();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["add-etc-hosts-p"])){Paragraphe_add();exit;}
	if(isset($_GET["DisableEtcHosts"])){DisableEtcHosts_save();exit;}
	if(isset($_GET["hosts-search"])){hosts_search();exit;}
	if(isset($_POST["rebuild"])){rebuild();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{etc_hosts}");
	echo "YahooWin3(800,'$page?popup=yes','$title',true);";
	
}

function rebuild(){
	
	$sock=new sockets();
	$data=$sock->getFrameWork("network.php?etc-hosts=yes");
	$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");
	$data=unserialize(base64_decode($data));
	echo @implode("\n", $data);
}



function DisableEtcHosts_save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableEtcHosts",$_GET["DisableEtcHosts"]);
	
}
function popup(){
	$page=CurrentPageName();
	$t=time();
	
	echo "<div id='$t'></div>
	
	<script>
		LoadAjax('$t','$page?table=yes',true);
	</script>
	
	";
	
	
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ip_address=$tpl->javascript_parse_text("{ip_address}");
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$alias=$tpl->_ENGINE_parse_body("{alias}");
	$delete=$tpl->javascript_parse_text("{delete} {hosts} ?");
	$add_new_entry=$tpl->javascript_parse_text("{add_new_entry}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$title=$tpl->javascript_parse_text("{etc_hosts}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$add_new_entry</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Rebuild$t},
	],";
	
	
	
	//`zmd5`,`ipaddr`,`hostname`,`alias`
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	<script>
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?hosts-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:20px>$ip_address</span>', name : 'ipaddr', width :250, sortable : true, align: 'left'},
	{display: '<span style=font-size:20px>$servername</span>', name : 'hostname', width : 550, sortable : false, align: 'left'},
	{display: '<span style=font-size:20px>$alias</span>', name : 'alias', width :250, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$ip_address', name : 'ipaddr'},
	{display: '$servername', name : 'hostname'},
	{display: '$alias', name : 'alias'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
var xNewRule$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
}
	
function Rebuild$t(){
	if(!confirm('$apply ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rebuild', 'yes');
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);
	}
	
function NewRule$t(){
	YahooWin4(475,'$page?add-form=yes&t=$t','$add_new_entry',true);
	
}
	
function RuleDelete$t(ID){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('host-delete', ID);
		XHR.sendAndLoad('$page', 'POST',xNewRule$t);
	}
}
</script>
	
			";
			echo $html;
}

function hosts_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$t=$_GET["t"];
	$search='%';
	$table="net_hosts";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("net_hosts: no data");
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no rule");}


	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		// (`zmd5`,`ipaddr`,`hostname`,`alias`)
		$ID=$ligne["ID"];
		$md5=md5($ligne["zmd5"]);
		
		$delete=imgtootltip("delete-48.png","{delete} Rule:{$ligne["rulename"]}","RuleDelete$t('{$ligne["zmd5"]}')");


		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:22px;font-weight:bold;color:$color'>{$ligne["ipaddr"]}</span>",
						"<span style='font-size:22px;font-weight:bold;color:$color'>{$ligne["hostname"]}</span>",
						"<span style='font-size:22px;font-weight:bold;color:$color'>{$ligne["alias"]}</span>"
						,"<center>$delete</center>" )
		);
}


echo json_encode($data);

}


function old_popup(){
	$page=CurrentPageName();
	$LIST=getlist();
	$sock=new sockets();
	
	$html="<div class=explain>{etc_hosts_explain}</div>
	
	<div style='text-align:right'>
	<table>
	<tr>
		<td class=legend>{DisableEtcHosts}:</td>
		<td>". Field_checkbox("DisableEtcHosts",1,$sock->GET_INFO("DisableEtcHosts"),"DisableEtcHostsSave()")."</td>
	</tr>
	</table>
	</div>
	
	<table style='width:100%'>
	<tr>
		<td valign='top'><div style='width:100%;height:330px;overflow:auto' id='idhosts'>$LIST</div></td>
		<td valign='top'><div id='add-etc-hosts-p'></div></td>
	</tr>
	</table>
	<script>
	function ParEtcHosts(){
		LoadAjax('add-etc-hosts-p','$page?add-etc-hosts-p=yes');
	}
	
	function idhostsList(){
		LoadAjax('idhosts','$page?refresh=yes');
	
	}
	
	var x_DisableEtcHostsSave=function (obj) {
			tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			ParEtcHosts();
			idhostsList();
	    }
	
	
		function DisableEtcHostsSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('DisableEtcHosts').checked){
			XHR.appendData('DisableEtcHosts','1');}else{
			XHR.appendData('DisableEtcHosts','0');}
			document.getElementById('add-etc-hosts-p').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';			
			XHR.sendAndLoad('$page', 'GET',x_DisableEtcHostsSave);		
		}
	
	ParEtcHosts();
	</script>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");	
	
	
}
function Paragraphe_add(){
	$add=Paragraphe("host-file-64-add.png","{add_new_entry}","{add_new_entry_text}","javascript:etc_hosts_add_form()","{add_new_entry_text}");
	$sock=new sockets();
	if($sock->GET_INFO("DisableEtcHosts")==1){
		$add=Paragraphe("host-file-64-add-grey.png","{add_new_entry}","{add_new_entry_text}","");
	}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($add);
	}
	
	



function host_add(){

	
	$md5=md5("{$_POST["ip_addr"]}{$_POST["servername"]}");
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES
	('$md5','{$_POST["ip_addr"]}','{$_POST["servername"]}','{$_POST["alias"]}')","artica_backup");
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function popup_add(){
	$page=CurrentPageName();
	
	$t=$_GET["t"];
	$html="
	
	<div id='hostsdiv' class=form style='width:95%'>
	<table style='width:100%'>
	
			<tr>
				<td class=legend style='font-size:16px'>{ip_address}:</td>
				<td>". field_ipv4('ip_addr',null,"font-size:16px")."</td>
			</tR>
			<tr>
				<td class=legend style='font-size:16px'>{hostname}:</td>
				<td>". Field_text('servername',null,"width:220px;font-size:16px",null,"CheckHostAlias()")."</td>
			</tR>
				
			<tr>
				<td class=legend style='font-size:14px'>{alias}:</td>
				<td>". Field_text('alias',null,"width:220px;font-size:16px")."</td>
			</tR>
		</table>
		<div style='width:100%;text-align:right'><hr>". button("{add}","Add$t()",18)."</div>
	</div>
		
		<script>
		
			function CheckHostAlias(){
				var servername=document.getElementById('servername').value;
				if(servername.length==0){return;}
				
				document.getElementById('alias').disabled=false;
				
				var alias=document.getElementById('alias').value;
				if(alias.length>0){return;}
				
				
				tr=servername.split('.');
				
				if(tr.length>0){
					document.getElementById('alias').value=tr[0];
				}
			
			}
				
			
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	YahooWin4Hide();
}		
	
function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ip_addr',document.getElementById('ip_addr').value);
	XHR.appendData('servername',document.getElementById('servername').value);
	XHR.appendData('alias',document.getElementById('alias').value);
	XHR.sendAndLoad('$page', 'POST',xAdd$t);		
}				
</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");		
	
}

function popup_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM net_hosts WHERE `zmd5`='{$_POST["host-delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

}
	

?>