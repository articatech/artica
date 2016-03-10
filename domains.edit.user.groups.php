<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.computers.inc');

if(isset($_REQUEST["uid"])){$_GET["uid"]=$_REQUEST["uid"];$_GET["userid"]=$_REQUEST["uid"];}
if(isset($_REQUEST["userid"])){$_GET["uid"]=$_REQUEST["userid"];$_GET["userid"]=$_REQUEST["userid"];}


//permissions	
$GetRights_aliases=GetRights_aliases();
if($GetRights_aliases==0){
	$tpl=new templates();
	$error="{ERROR_NO_PRIVS}<br>GetRights_aliases=$GetRights_aliases<br>".@implode("<br>", $GLOBALS["ERRORS_PRIVS"]);
	echo $tpl->_ENGINE_parse_body("$error");
	die();
}
$modify_user = 1;
if(isset($_GET["group-list"])){groups_list();exit;}
if(isset($_POST["DeleteUserGroup"])){DeleteUserFromGroup();exit();}


page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$userid=$_GET["userid"];
	$u=new user($_GET["userid"]);
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$group2=$tpl->_ENGINE_parse_body("{groups2}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$workgroup=$tpl->_ENGINE_parse_body("{workgroup}");
	$privileges=$tpl->_ENGINE_parse_body("{privileges}");
	$virtual_servers=$tpl->_ENGINE_parse_body("{virtual_servers}");
	$ou=$tpl->_ENGINE_parse_body("{organization}");
	$link_group=$tpl->_ENGINE_parse_body("{link_group}");
	$sure_delete_smb_vrt=$tpl->javascript_parse_text("{sure_delete_smb_vrt}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ou_encoded=base64_encode($u->ou);
	$title=$tpl->_ENGINE_parse_body("{$_GET["userid"]}::{member_of_group}");
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$link_group</strong>', bclass: 'Add', onpress : AddMemberGroup$t},
	],	";
	//$('#flexRT$t').flexReload();
	
	if($u->AsActiveDirectoryMember){$buttons=null;}
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?group-list=yes&t=$t&userid={$_GET["userid"]}&dn={$_GET["dn"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>', name : 'hostname', width :53, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$group2</span>', name : 'workgroup', width :670, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$privileges</span>', name : 'workgroup', width :110, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>&nbsp;</span>', name : 'delete', width :53, sortable : false, align: 'center'},
	],
	$buttons
	
	searchitems : [
	{display: '$group2', name : 'hostname'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	});
	
	var x_DeleteUserGroup$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#flexRT$t').flexReload();
	}		
	
function DeleteUserGroup$t(group_id){
		var XHR = new XHRConnection();
		XHR.appendData('uid','{$_GET["userid"]}');
		XHR.appendData('DeleteUserGroup',group_id);
		XHR.sendAndLoad('$page', 'POST',x_DeleteUserGroup$t);		
		}	

		

	
	function AddMemberGroup$t(){
		Loadjs('domains.group.user.affect.php?ou=$ou_encoded&uid=$userid&t=$t');
	}

</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function groups_list(){
	$userid=$_GET["userid"];
	$t=$_GET["t"];
	if($_GET["dn"]<>null){
		if(strpos($_GET["dn"], ",")==0){$_GET["dn"]=base64_decode($_GET["dn"]);}
	}
	if (substr ( $userid, strlen ( $userid ) - 1, 1 ) == '$') {$users = new computers ( $userid );} else {$users = new user ( $userid,$_GET["dn"] );}
	$ou = $users->ou;
	$groups = $users->Groups_list();
	$priv = new usersMenus ( );
	$sambagroups = array ("515" => true, "548" => true, "544" => true, "551" => true, "512" => true, "514" => true, "513" => true, 550 => true, 552 => true );
	if($users->AsActiveDirectoryMember){$priv->EnableManageUsersTroughActiveDirectory=true;}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($groups);
	$data['rows'] = array();	
	$search=string_to_flexregex();
	if(count($groups)==0){json_error_show("no data");}
	$c=0;
	while ( list ( $num, $ligne ) = each ( $groups ) ) {
		if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
		$delete = imgsimple( '32-group-delete-icon.png', '{DISCONNECT_FROM_GROUP} ' . $ligne, "DeleteUserGroup$t($num)" );
		$privileges = imgsimple ( "members-priv-32.png", '{privileges}', "Loadjs('domains.edit.group.php?GroupPrivilegesjs=$num')" );
		$md5=md5($ligne);
		if($priv->EnableManageUsersTroughActiveDirectory){
			$delete = imgsimple ( '32-group-delete-icon-grey.png', '{DISCONNECT_FROM_GROUP} ' . $ligne);
			$privileges = imgsimple ( "members-priv-32-grey.png", '{privileges}' );
		}
	
		if(!is_numeric($num)){$num=urlencode($num);}
		$groupjs = "Loadjs('domains.edit.group.php?ou=$ou&js=yes&group-id=$num&t=$t')";
		if ($sambagroups [$ligne]) {$privileges = null;$groupjs = null;}
		if ($priv->AllowAddUsers == false) {$delete = "&nbsp;";$groupjs = null;}
		$c++;
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						imgsimple("group-32.png",null,$groupjs),
						"<span style='font-size:16px;'>		
						<a href=\"javascript:blur();\" 
							OnClick=\"javascript:$groupjs\" 
							style='font-size:18px;font-weight:bold;text-decoration:underline'>$ligne</a></span>",
						"<span style='font-size:16px;'>$privileges</span>",
						"<span style='font-size:16px;'>$delete</span>",
				)
		);


	
	}
	$data['total'] = $c;
	echo json_encode($data);
	
	
}






function DeleteUserFromGroup() {
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$usr = new usersMenus ( );
	$tpl = new templates ( );
	if ($usr->AllowAddGroup == false) {
		echo $tpl->javascript_parse_text( '{no_privileges}' );
		exit ();
	}
	$ldap = new clladp ( );

	$userid = $_POST["uid"];
	$groupid = $_POST["DeleteUserGroup"];
	if (! $ldap->UserDeleteToGroup ( $userid, $groupid )) {
		echo $ldap->ldap_last_error;
	}
}
?>