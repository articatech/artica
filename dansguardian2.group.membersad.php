<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.ActiveDirectory.inc');

	
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["groups-list"])){group_list();exit;}
	
popup();


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$dn=$_GET["dn"];
	$t=time();
	$Members=$tpl->_ENGINE_parse_body("{members}");
	
	if(preg_match("#AD:(.*?):(.+)#", $_GET["dn"],$re)){
		$dnEnc=$re[2];
		$DN_TITLE=base64_decode($dnEnc);
		$LDAPID=$re[1];
		$link="BrowseActiveDirectory.php?UsersGroup-list=yes&dn=$dnEnc&ADID=$LDAPID";
	}	
	
	$html="
		<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$link',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'img', width : 31, sortable : false, align: 'center'},
		{display: '<strong style=font-size:18px>$Members</strong>', name : 'uid', width : 773, sortable : false, align: 'left'},
	],

	searchitems : [
		{display: '$Members', name : 'uid'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '$DN_TITLE',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});   
});
</script>	
	
	";
	
echo $html;
}

function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	

	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		//$disable=Field_checkbox("groupid_{$ligne['gpid']}", 1,$ligne["enabled"],"EnableDisableGroup('{$ligne['ID']}')");
		$ligne['uid']=utf8_encode($ligne['uid']);
		$jsSelect="blur()";
		
		
	$data['rows'][] = array(
		'id' => "group{$ligne['gpid']}",
		'cell' => array(
		"<img src='img/user-24.png'>",
		"<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$jsSelect\" 
		style='font-size:16px;text-decoration:underline'>{$ligne['uid']}</span>",
		)
		);
	}
	
	
	echo json_encode($data);	
}

