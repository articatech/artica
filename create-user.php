<?php
	session_start();
	
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
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
	

	if(isset($_GET["form"])){formulaire();exit;}
	if(isset($_GET["ch-groupid"])){groups_selected();exit;}
	if(isset($_GET["ch-domain"])){domain_selected();exit;}
	if(isset($_POST["password"])){save();exit;}
	
	js();


$users=new usersMenus();
if(!$users->AllowAddUsers){die("alert('not allowed');");}
	
function js(){
$tpl=new templates();
$page=CurrentPageName();
$ouJS="";
$title=$tpl->_ENGINE_parse_body('{add user explain}');
if(!is_numeric($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
if($_GET["ou"]<>null){$ffou="&ou={$_GET["ou"]}";$ouJS="{$_GET["ou"]}";}
$html="
var x_serid='';

function OpenAddUser(){
	YahooWin5('590','$page?form=yes&t=$t','$title');
}

var x_ChangeFormValues= function (obj) {
	var tempvalue=obj.responseText;
	var internet_domain='';
	var ouJS='$ouJS';
	var ou=document.getElementById('organization-$t').value;
	if(ouJS.length>0){ou=ouJS;}
	if(!document.getElementById('select_groups-$t')){alert('select_groups-$t no such id');}
	document.getElementById('select_groups-$t').innerHTML=tempvalue;
	if(document.getElementById('internet_domain-$t')){internet_domain=document.getElementById('internet_domain-$t').value;}
	if(document.getElementById('DomainsUsersFindPopupDiv')){DomainsUsersFindPopupDivRefresh();}
  	 var XHR = new XHRConnection();
     XHR.appendData('ou',ou);
	 XHR.appendData('ch-domain',internet_domain);
	 XHR.appendData('t','$t');       	
     XHR.sendAndLoad('$page', 'GET',x_ChangeFormValues2);		
}

var x_ChangeFormValues2= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length==0){return;}
	var domain='';
	var email='';
	var login='';
	var ouJS='$ouJS';
	var ou=document.getElementById('organization-$t').value;
	if(ouJS.length>0){ou=ouJS;}
	
	document.getElementById('select_domain-$t').innerHTML=tempvalue;
	if(!document.getElementById('email-$t')){alert('email-$t no such id');}
	if(!document.getElementById('login-$t')){alert('login-$t no such id');}
	
	email=document.getElementById('email-$t').value;
	login=document.getElementById('login-$t').value;
	if(login.length==0){
		if(email.length>0){
			document.getElementById('login-$t').value=email;
		}
	}
		
}


var x_SaveAddUser= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){
		alert(tempvalue);
		document.getElementById('ffform-$t').innerHTML=\"<div style='width:100%;padding:15px'><center><img src='img/identity-add-96.png'></center></div>\";  
		return false;
	}
	YahooWin5Hide();
	if(document.getElementById('flexRT$t')){ $('#flexRT$t').flexReload(); }
	if(document.getElementById('table-$t')){ $('#table-$t').flexReload(); }
	
	if(document.getElementById('main_config_pptpd')){RefreshTab('main_config_pptpd');}
	if(document.getElementById('MAIN_PAGE_ORGANIZATION_LIST')){
		var ppa=document.getElementById('MAIN_PAGE_ORGANIZATION_LIST').value;
		$('#table-'+ppa).flexReload();
	}else{
		if(document.getElementById('admin_perso_tabs')){RefreshTab('admin_perso_tabs');}
	}
	if(document.getElementById('org_main')){RefreshTab('org_main');}
}

function SaveAddUserCheck(e){
	if(checkEnter(e)){SaveAddUser();}
}

function SaveAddUser(){
	  var gpid='';
	  var internet_domain='';
	  var ou=document.getElementById('organization-$t').value;
	  var email=document.getElementById('email-$t').value;
	  var firstname=encodeURIComponent(document.getElementById('firstname-$t').value);
	  var lastname=encodeURIComponent(document.getElementById('lastname-$t').value);  
	  var login=document.getElementById('login-$t').value;
	  var password=encodeURIComponent(document.getElementById('password-$t').value);
	  x_serid=login;
	  if(document.getElementById('groupid-$t')){gpid=document.getElementById('groupid-$t').value;}
	  if(document.getElementById('internet_domain-$t')){internet_domain=document.getElementById('internet_domain-$t').value;}
	  var EnableVirtualDomainsInMailBoxes=document.getElementById('EnableVirtualDomainsInMailBoxes-$t').value;
	  if(EnableVirtualDomainsInMailBoxes==1){x_serid=email+'@'+internet_domain;}

  	 var XHR = new XHRConnection();
     XHR.appendData('ou',ou);
     XHR.appendData('internet_domain',internet_domain);
	 XHR.appendData('email',email);
     XHR.appendData('firstname',firstname);
     XHR.appendData('lastname',lastname);
     XHR.appendData('login',login);
     XHR.appendData('password',password);
     XHR.appendData('gpid',gpid);  
     AnimateDiv('ffform-$t');                                    		      	
     XHR.sendAndLoad('$page', 'POST',x_SaveAddUser);		  
}




	

function ChangeFormValues(){
  var gpid='';
  var ou=document.getElementById('organization-$t').value;
		
  		if(document.getElementById('groupid-$t')){gpid=document.getElementById('groupid-$t').value;}
  		var XHR = new XHRConnection();
        XHR.appendData('ch-groupid',gpid);
        XHR.appendData('ou',ou);
        XHR.sendAndLoad('$page', 'GET',x_ChangeFormValues);	

}



OpenAddUser();";
echo $html;
}

function groups_selected(){
	$t=$_GET["t"];
	$ldap=new clladp();
	if(is_base64_encoded($_GET["ou"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	$hash_groups=$ldap->hash_groups($_GET["ou"],1);
	$groups=Field_array_Hash($hash_groups,"groupid-$t",$_GET["ch-groupid"],null,null,0,"font-size:16px;padding:3px");
	echo $groups;
	
}

function domain_selected(){
	$t=$_GET["t"];
	$ldap=new clladp();
	if(is_base64_encoded($_GET["ou"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	$hash_domains=$ldap->hash_get_domains_ou($_GET["ou"]);
	$domains=Field_array_Hash($hash_domains,"internet_domain-$t",$_GET["ch-domain"],null,null,0,"font-size:16px;padding:3px");
	echo $domains;
	
}

function formulaire(){
	$users=new usersMenus();
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	if($users->AsAnAdministratorGeneric){
		$hash=$ldap->hash_get_ou(false);
	}else{
		if($_GET["ou"]==null){
			$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
			
		}else{
			$hash[0]=$_GET["ou"];
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
		}
		
	}
	
	if(count($hash)==0){
		
		echo $tpl->_ENGINE_parse_body("<center style='font-size:16px;color:#9E0000;margin:35px'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:TreeAddNewOrganisation();\" style='font-size:16px;color:#9E0000;text-decoration:underline'>
		{error_no_ou_created}</a></center>");
		return;
		
		
	}
	
	if(count($hash)==1){
		$org=$hash[0];
		$hash_groups=$ldap->hash_groups($org,1);
		$hash_domains=$ldap->hash_get_domains_ou($org);
		$groups=Field_array_Hash($hash_groups,"groupid-$t",null,null,null,0,"font-size:16px;padding:3px");
		$domains=Field_array_Hash($hash_domains,"domain-$t",null,null,null,0,"font-size:16px;padding:3px");
	}
	
	
	$artica=new artica_general();
	$EnableVirtualDomainsInMailBoxes=$artica->EnableVirtualDomainsInMailBoxes;	
	
	
	while (list ($num, $ligne) = each ($hash) ){
		$ous[$ligne]=$ligne;
	}
	
	$ou=Field_array_Hash($ous,"organization-$t",$_GET["ou"],"ChangeFormValues()",null,0,"font-size:16px;padding:3px");
	$form="
	
	<input type='hidden' id='EnableVirtualDomainsInMailBoxes-$t' value='$EnableVirtualDomainsInMailBoxes'>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{organization}:</td>
			<td>$ou</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{group}:</td>
			<td><span id='select_groups-$t'>$groups</span>
		</tr>
		<tr>
		<tr>
			<td class=legend style='font-size:16px'>{firstname}:</td>
			<td>" . Field_text("firstname-$t",null,'width:120px;font-size:16px;padding:3px',null,'ChangeFormValues()')."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:16px'>{lastname}:</td>
			<td>" . Field_text("lastname-$t",null,'width:120px;font-size:16px;padding:3px',null,"ChangeFormValues()")."</td>
		</tr>		
			
		<tr>
			<td class=legend style='font-size:16px'>{email}:</td>
			<td>" . Field_text("email-$t",null,'width:120px;font-size:16px;padding:3px',null,"ChangeFormValues()")."@<span id='select_domain-$t'>$domains</span></td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{uid}:</td>
			<td>" . Field_text("login-$t",null,'width:120px;font-size:16px;padding:3px')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{password}:</td>
			<td>" .Field_password("password-$t",null,"font-size:16px;padding:3px",null,null,null,false,"SaveAddUserCheck(event)")."</td>
		</tr>	
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","SaveAddUser()",18)."
				
			</td>
		</tr>
		
		</table>
	";
			
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><div id='ffform-$t'><img src='img/identity-add-96.png'></div></td>
		<td valign='top' width=99%><div>$form</div></td>
	</tr>
	</table>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$tpl=new templates();     
     $users=new user($_POST["login"]);
     if($users->password<>null){
     	writelogs("User already exists {$_POST["login"]} ",__FUNCTION__,__FILE__);
     	echo($tpl->_ENGINE_parse_body('{account_already_exists}'));
     	exit;
     }
     $ou=$_POST["ou"];
     $password=url_decode_special_tool($_POST["password"]);
     $_POST["firstname"]=url_decode_special_tool($_POST["firstname"]);
     $_POST["lastname"]=url_decode_special_tool($_POST["lastname"]);
     
     
     if(trim($_POST["internet_domain"])==null){$_POST["internet_domain"]="localhost.localdomain";}
     writelogs("Add new user {$_POST["login"]} {$_POST["ou"]} {$_POST["gpid"]}",__FUNCTION__,__FILE__);
     $users->ou=$_POST["ou"];
     $users->password=url_decode_special_tool($_POST["password"]);
     $users->mail="{$_POST["email"]}@{$_POST["internet_domain"]}";    
     $users->DisplayName="{$_POST["firstname"]} {$_POST["lastname"]}";
     $users->givenName=$_POST["firstname"];
     $users->sn=$_POST["lastname"];
     $users->group_id=$_POST["gpid"];
     
     
     
	if(is_numeric($_POST["gpid"])){
		$gp=new groups($_POST["gpid"]);
		writelogs( "privileges: {$_POST["gpid"]} -> AsComplexPassword = \"{$gp->Privileges_array["AsComplexPassword"]}\"", __FUNCTION__, __FILE__, __LINE__ );
		if($gp->Privileges_array["AsComplexPassword"]=="yes"){
			$ldap=new clladp();		
			$hash=$ldap->OUDatas($ou);	
			$privs=$ldap->_ParsePrivieleges($hash["ArticaGroupPrivileges"],array(),true);
			$policiespwd=unserialize(base64_decode($privs["PasswdPolicy"]));
			if(is_array($policiespwd)){
				$priv=new privileges();
				if(!$priv->PolicyPassword($password,$policiespwd)){
					echo "Need complex password";return;
				}
			}
		}
	}     
     
     
	if(!$users->add_user()){echo $users->error."\n".$users->ldap_error;}
    
}


?>