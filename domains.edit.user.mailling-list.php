<?php
session_start();
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/charts.php');
	include_once('ressources/class.mimedefang.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ini.inc');	
	include_once('ressources/class.ocs.inc');
	
	include_once(dirname(__FILE__). "/ressources/class.cyrus.inc");
	
	if((isset($_GET["uid"])) && (!isset($_GET["userid"]))){$_GET["userid"]=$_GET["uid"];}
	
	
	
	
//permissions	
	$usersprivs=new usersMenus();
	$change_aliases=1;
	$modify_user=1;
	
	if(!$usersprivs->AsAnAdministratorGeneric){
		if(!$usersprivs->AllowEditAliases){
			$change_aliases=0;
			}
		if($_SESSION["uid"]<>$_GET["userid"]){$modify_user=0;}
	}
	

if($change_aliases==0){die();}


		if(isset($_GET["popup"])){echo USER_ALIASES_MAILING_LIST($_GET["uid"]);exit;}
		if(isset($_GET["USER_ALIASES_MAILING_LIST_ADD_JS"])){USER_ALIASES_MAILING_LIST_ADD_JS();exit;}
		if(isset($_GET["USER_ALIASES_MAILING_LIST_DEL_JS"])){USER_ALIASES_MAILING_LIST_DEL_JS();exit;}
		if(isset($_GET["x_AddAliasesMailing"])){echo USER_ALIASES_MAILING_LIST_LIST($_GET["x_AddAliasesMailing"]);exit;}
		if(isset($_GET["MailingListAddressGroupSwitch"])){MailingListAddressGroupSwitch_js();exit;}
		if(isset($_GET["MailingListAddressGroup"])){USER_ALIASES_MAILING_LIST_GROUP_SAVE();exit;}
		if(isset($_GET["aliases-mailing-list"])){echo USER_ALIASES_MAILING_LIST_LIST($_GET["uid"]);exit;}

js();

function MailingListAddressGroupSwitch_js(){
	$page=CurrentPageName();
	$html="
	
var x_MailingListAddressGroupJS= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);
		document.getElementById('MailingListAddressGroup').checked=false;
		return;
	}
	LoadAjax('aliases-mailing-list','$page?x_AddAliasesMailing={$_GET["uid"]}&ou={$_GET["ou"]}&uid={$_GET["uid"]}');	
}	
	
function MailingListAddressGroupJS(){
	var XHR = new XHRConnection();
	XHR.appendData('MailingListAddressGroup','yes');
	XHR.appendData('uid','{$_GET["uid"]}');
	if(document.getElementById('MailingListAddressGroup').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
	XHR.sendAndLoad('$page', 'GET',x_MailingListAddressGroupJS);
}
	
	
	MailingListAddressGroupJS();";
	echo $html;
	
}

function USER_ALIASES_MAILING_LIST_GROUP_SAVE(){
	$user=new user($_GET["uid"]);
	$user->MaillingListGroupEnable($_GET["enabled"]);
	}


function USER_ALIASES_MAILING_LIST_ADD_JS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$AddAliasesMailing_jstext=$tpl->javascript_parse_text("{AddAliasesMailing_jstext}");
	$t=$_GET["flexRT"];
	$md="A_".md5("{$_GET["mail"]}{$_GET["uid"]}".date('Y-m-d-h:i:s'));
$html="
var $md= function (obj) {
	$('#flexRT$t').flexReload();
	
}


	var aliase=prompt('$AddAliasesMailing_jstext');
	if(aliase){
		var XHR = new XHRConnection();
		XHR.appendData('AddAliasesMailing','{$_GET["uid"]}');
		XHR.appendData('aliase',aliase);
		XHR.sendAndLoad('domains.edit.user.php', 'GET',$md);
	}";

echo $html;
}
function USER_ALIASES_MAILING_LIST_DEL_JS(){
	$page=CurrentPageName();
	$md="A_".md5("{$_GET["mail"]}{$_GET["uid"]}".date('Y-m-d-h:i:s'));
$html="
		var $md= function (obj) {
			$('#row{$_GET["id"]}').remove();	
		}

		var XHR = new XHRConnection();
		XHR.appendData('DeleteAliasesMailing','{$_GET["uid"]}');
		XHR.appendData('aliase','{$_GET["mail"]}');
		XHR.sendAndLoad('domains.edit.user.php', 'GET',$md);
	";

echo $html;
}

function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["uid"]}: {mailing_list}");
	$page=CurrentPageName();
	$html="
	function mailing_list_load(){
			YahooWin2(630,'$page?popup=yes&uid={$_GET["uid"]}','$title');
		}
	
	
	mailing_list_load();
	";
	
	echo $html;
	
}


function USER_ALIASES_MAILING_LIST($userid){
   	$page=CurrentPageName();
   	$u=new user($userid);
   	$priv=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$add_new_alias=$tpl->javascript_parse_text("{add_new_recipient}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$telephonenumber=$tpl->_ENGINE_parse_body("{phone}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$website_ssl_wl_help=$tpl->javascript_parse_text("{website_ssl_wl_help}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$send_a_test_mail=$tpl->javascript_parse_text("{send_a_test_mail}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$explain=$tpl->_ENGINE_parse_body("<div style='font-size:16px' class=explain>{aliases_mailing_text}:&nbsp;&laquo;<b>{$u->mail}&raquo;</b></div>");
	
	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");
	if(is_base64_encoded($_GET["ou"])){$ou_encoded=$_GET["ou"];$ou=base64_decode($_GET["ou"]);}else{$ou=$_GET["ou"];$ou_encoded=base64_encode($_GET["ou"]);}
	
		$add_user_disabled=Paragraphe('folder-useradd-64-grey.png','{create_user}','{create_user_text}');
		$add_user=Paragraphe('folder-useradd-64.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);	
		$groups=Paragraphe('folder-group-64.png','{manage_groups}','{manage_groups_text}',"javascript:Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')",null,210,100,0,true);
		$delete_all_users=Paragraphe('member-64-delete.png','{delete_all_users}','{delete_all_users_text}',"javascript:DeleteAllusers()",null,210,100,0,true);	
		
		
	$add_new_alias_js="{name: '<b>$add_new_alias</b>', bclass: 'Add', onpress : NewAliasEmail},";
	if($priv->AllowAddUsers==false){$add_new_alias_js=null;}
		
	$buttons="
	buttons : [
	$add_new_alias_js
	{name: '<b>$send_a_test_mail</b>', bclass: 'eMail', onpress : SendTestMail},
	{name: '<b>$apply</b>', bclass: 'apply', onpress : Apply$t},
	],";	
	
$html="
    	
    	<input type='hidden' id='ou' value='$u->ou'>
$explain
    	". $tpl->_ENGINE_parse_body("<table style='width:99%' class=form>
    	<tr>
    		<td class=legend style='font-size:16px'>{MailingListAddressGroup}:</td>
    		<td align='left'>". Field_checkbox_design("MailingListAddressGroup",1,$u->MailingListAddressGroup,"Loadjs('$page?MailingListAddressGroupSwitch=yes&uid=$userid&ou=$u->ou')","{MailingListAddressGroup_text}")."</td>
    	</tr>
    	</table>")."
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
row_id='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?aliases-mailing-list=yes&ou={$_GET["ou"]}&t=$t&uid=$userid',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$email</span>', name : 'email', width : 480, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'aaa', width : 31, sortable : false, align: 'center'},	
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$member', name : 'search'},
		],
	sortname: 'uid',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function Apply$t(){
	Loadjs('postfix.aliases.progress.php');
}

	var x_sslBumbAddwl=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
     	//$('#flexRT$t').flexReload();
     	}	
      
     function SendTestMail(){
    		Loadjs('postfix.sendtest.mail.php?rcpt=$u->mail');
		}

	function NewAliasEmail(){
		Loadjs('$page?USER_ALIASES_MAILING_LIST_ADD_JS=yes&uid=$userid&ou=$u->ou&flexRT=$t')
	}
	
	
	
</script>

";
	
	return $html;
   	


}
	
   
function USER_ALIASES_MAILING_LIST_LIST($userid){  
    	$u=new user($userid);
    	$page=CurrentPageName();
		$hash=$u->LoadAliasesMailing();	
		$t=$_GET["t"];
		
    		while (list ($num, $ligne) = each ($hash) ){if($ligne==null){continue;} $array[$ligne]=true;}	
			$groups=$u->MailingGroupsLoadAliases(); 
    		while (list ($num, $ligne) = each ($groups) ){
    			if($ligne==null){continue;}  	
    			$array[$ligne]=false;
    		}	
    		    			
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();	

	if(trim($_POST["query"])<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		
	}
	$c=0;
	while (list ($num, $ligne) = each ($array) ){
		if($search<>null){if(!preg_match("#$search#", $num)){continue;}}
		$c++;
		$testmail=imgsimple("test-mail-22.png",null,"Loadjs('postfix.sendtest.mail.php?rcpt=$num')");
		$id=md5($num.$c.time());
		$dele=imgsimple("delete-24.png",null,"Loadjs('$page?USER_ALIASES_MAILING_LIST_DEL_JS=yes&mail=$num&uid=$userid&ou=$u->ou&t=$t&id=$id')");
		
		$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:16px;color:$color'>$num</span>",
		"<span style='font-size:14px;color:$color'>$testmail</span>",
		"<span style='font-size:14px;color:$color'>$dele</span>",
		
		
		)
		);		
		
	}
	
	$data['page'] = 1;
	$data['total'] = $c;
	
	return json_encode($data);	
}

  
	
?>