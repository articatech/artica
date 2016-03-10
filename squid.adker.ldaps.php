<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_GET["server-js"])){server_js();exit;}
	if(isset($_GET["server-popup"])){server_popup();exit;}
	if(isset($_POST["WORKGROUP"])){server_save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["rulemd5"])){main_rule();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_GET["diclaimers-rule"])){disclaimer_rule();exit;}
	
	
	
	if(isset($_POST["mailfrom"])){rule_add();exit;}
	if(isset($_POST["del-zmd5"])){rule_delete();exit;}
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{alternate_servers}");
	echo "YahooWin2(990,'$page?popup=yes','$title')";
	
}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete} {$_GET["workgroup"]} ?");
	$t=time();
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#ACTIVE_DIR_ALTERNATE_SERVERS').flexReload();
}
function Add$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["workgroup"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
}
function server_js(){
	$workgroup=$_GET["workgroup"];
	$tpl=new templates();
	$page=CurrentPageName();
	if($workgroup==null){$workgroup="{new_server}";}
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{workgroup}: $workgroup");
	echo "YahooWin3(990,'$page?server-popup=yes&workgroup={$_GET["workgroup"]}','$title')";
	
}

function delete(){
	$sock=new sockets();
	$workgroup=$_POST["delete"];
	$ARRAY=unserialize($sock->GET_INFO("SquidAddkerAlernates"));	
	unset($ARRAY[$workgroup]);
	$sock->SaveConfigFile(serialize($ARRAY), "SquidAddkerAlernates");
}

function server_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$workgroup=$_GET["workgroup"];
	$ARRAY=unserialize($sock->GET_INFO("SquidAddkerAlernates"));
	$t=time();
	$MAINArray=$ARRAY[$workgroup];
	
	if(intval($MAINArray["LDAP_PORT"])==0){$MAINArray["LDAP_PORT"]=389;}

	$btname="{edit}";
	$title="{workgroup}: $workgroup";
	
	if($workgroup==null){
		$title="{workgroup}: {new_server}";
		$btname="{add}";
	}
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>$title:: {ldap_parameters2}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{workgroup}:</td>
		<td>". Field_text("WORKGROUP-$t",$workgroup,"font-size:22px;padding:3px;width:70%")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td>". Field_text("LDAP_SERVER-$t",$MAINArray["LDAP_SERVER"],"font-size:22px;padding:3px;width:70%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$t",$MAINArray["LDAP_PORT"],"font-size:22px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$t",$MAINArray["LDAP_SUFFIX"],"font-size:22px;padding:3px;width:580px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN-$t",$MAINArray["LDAP_DN"],"font-size:22px;padding:3px;width:580px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$t",$MAINArray["LDAP_PASSWORD"],"font-size:22px;padding:3px;width:510px")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("$btname","SaveLDAPADker$t()",46)."</td>
	</tr>
	</table>
	</div>
	
	<script>
var x_SaveLDAPADker$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#ACTIVE_DIR_ALTERNATE_SERVERS').flexReload();
	var workgroup='{$_GET["workgroup"]}';
	if(workgroup.length()==0){YahooWin3Hide();}
}
	
function SaveLDAPADker$t(){
	if(!document.getElementById('LDAP_PASSWORD-$t')){alert('LDAP_PASSWORD !!');}
	if(!document.getElementById('WORKGROUP-$t')){alert('WORKGROUP !!');}
	if(!document.getElementById('LDAP_SERVER-$t')){alert('LDAP_SERVER !!');}
	if(!document.getElementById('LDAP_PORT-$t')){alert('LDAP_PORT !!');}
	if(!document.getElementById('LDAP_SUFFIX-$t')){alert('LDAP_SUFFIX !!');}
	
		
	var pp=encodeURIComponent(document.getElementById('LDAP_PASSWORD-$t').value);
	var XHR = new XHRConnection();
	XHR.appendData('WORKGROUP',document.getElementById('WORKGROUP-$t').value);
	XHR.appendData('LDAP_SERVER',document.getElementById('LDAP_SERVER-$t').value);
	XHR.appendData('LDAP_PORT',document.getElementById('LDAP_PORT-$t').value);
	XHR.appendData('LDAP_SUFFIX',document.getElementById('LDAP_SUFFIX-$t').value);
	XHR.appendData('LDAP_DN',document.getElementById('LDAP_DN-$t').value);
	XHR.appendData('LDAP_PASSWORD',pp);
	XHR.sendAndLoad('$page', 'POST',x_SaveLDAPADker$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function server_save(){
	
	$sock=new sockets();
	$_POST["WORKGROUP"]=trim(strtolower($_POST["WORKGROUP"]));
	$_POST["LDAP_PASSWORD"]=trim(url_decode_special_tool($_POST["LDAP_PASSWORD"]));
	
	
	$MAINArray=unserialize($sock->GET_INFO("SquidAddkerAlernates"));
	
	
	while (list ($index, $none) = each ($_POST) ){
		$MAINArray[$_POST["WORKGROUP"]][$index]=$none;
	}
	
	$sock->SaveConfigFile(serialize($MAINArray), "SquidAddkerAlernates");
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=880;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_server}");
	$servers=$tpl->_ENGINE_parse_body("{servers}");
	$to=$tpl->_ENGINE_parse_body("{recipients}");
	$title=$tpl->_ENGINE_parse_body("{alternate_servers}");
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
	<table class='ACTIVE_DIR_ALTERNATE_SERVERS' style='display: none' id='ACTIVE_DIR_ALTERNATE_SERVERS' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#ACTIVE_DIR_ALTERNATE_SERVERS').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '<span style=font-size:18px>$servers</span>', name : 'mailfrom', width :813, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>&nbsp;</span>', name : 'action', width :100, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$servers', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},

	],
	sortname: 'mailfrom',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 80,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [80]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');
}


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#ACTIVE_DIR_ALTERNATE_SERVERS').flexReload();
}

function NewGItem$t(){
	Loadjs('$page?server-js=yes&workgroup=');
	
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
	$sock=new sockets();
	
	$MAINArray=unserialize($sock->GET_INFO("SquidAddkerAlernates"));
		
	
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	

	
	$c=0;
	while (list ($workgroup, $array) = each ($MAINArray) ){	
	$zmd5=md5($workgroup);
	$color="#000000";
	
	$delete=imgsimple("delete-32.png","","Loadjs('$MyPage?delete-js=yes&workgroup=$workgroup')");
	
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('$MyPage?server-js=yes&workgroup=$workgroup');\"
	style='font-size:26px;color:$color;text-decoration:underline'>";
	$server=$array["LDAP_SERVER"];
	
	$data['rows'][] = array(
		'id' => "C$zmd5",
		'cell' => array(
			"<span style='font-size:18px;color:$color'>$urljs$workgroup/{$server}</a></span>",
			"<center style='font-size:18px;color:$color'>$delete</a></center>",
			)
		);
		$c++;
	}
	
	if($c==0){json_error_show("no data");}
	$data['total'] = $c;	
	
echo json_encode($data);	
	
}







