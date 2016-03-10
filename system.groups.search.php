<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["ICON_FAMILY"]="user";
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.contacts.inc');


if(isset($_GET["q"])){autocomplete_search();exit;}
if(isset($_GET["search"])){search();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ldap=new clladp();
	$t=time();
	$Green="#005447";
	$ForceDefaultGreenColor=$sock->GET_INFO("ForceDefaultGreenColor");
	if($ForceDefaultGreenColor<>null){$Green=$ForceDefaultGreenColor;}
	$skinf=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/top-bar-color.conf";
	if(is_file($skinf)){$Green=@file_get_contents($skinf);}
	$IsKerbAuth=$ldap->IsKerbAuth();
	$default_search="*";
	if(isset($_SESSION["SEARCH_GROUPS_MEMORY"])){$default_search=$_SESSION["SEARCH_GROUPS_MEMORY"];}
	
	$field_search=Field_autocomplete("search-users-$t","shadow:".$tpl->_ENGINE_parse_body("{search_groups}"),"font-size:28px;width:99%","$page","SearchEnter$t(event)","Search$t");
			
	$add_icon="
	<div style='float:right;margin-top:3px;margin-right:5px;'>
	<table>
	<tr><td style='vertical-align:middle;font-size:18px;color:white;'>
	&laquo;&nbsp;<a href=\"javascript:blur();\"
	OnClick=\"javascript:GotoMembersSearch();\"
	style='font-size:18px;text-decoration:underline;color:white'>{search_members}</a>&nbsp;&raquo;</td>";
	
	
	$ldap=new clladp();
	if($IsKerbAuth==0){
		$add_icon=$add_icon."<td style='vertical-align:middle'>" .imgtootltip("add-42-white.png","{new_group}","Loadjs('domains.edit.group.php?popup-add-group=yes&CallBackFunction=Search$t')")."</td>";
	}
	
	
	
	$html="
	<div style='width:100%;padding:5px;'>$field_search</div>
	<div style='height:50px;background-color:$Green;color:white;padding-left:15px;
	font-size:38px;
	  -webkit-border-radius: 5px 5px 0 0;
  -moz-border-radius: 5px 5px 0 0;
  border-radius: 5px 5px 0 0;vertical-align:middle
	'>
		$add_icon</td></table></div>
		{groups2}&nbsp;&nbsp;<span id='title-$t'></span>
	</div>
	<div id='search-$t' style='width:99.8%;margin-top:2px;border:1px solid #CCCCCC;-webkit-border-radius: 5px 5px 0 0;
  -moz-border-radius: 5px 5px 0 0;
  border-radius: 5px 5px 0 0;'></div>
  
  
  
  <script>
  	function SearchEnter$t(e){
  		if(!checkEnter(e)){return;}
  		Search$t();
  	}
  	
  	function Search$t(query){
  		if(!query){
  			query=document.getElementById('search-users-$t').value
  		}
  		
  		LoadAjaxSilent('search-$t','$page?search='+encodeURIComponent(query)+'&t=$t');
  	
  	}
  	
  	Search$t('$default_search');
  	
 </script> 
  
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
	
	
	
	
}


function search(){
	$t=$_GET["t"];
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$_SESSION["SEARCH_GROUPS_MEMORY"]=$_GET["search"];
	$stringtofind=url_decode_special_tool($_GET["search"]);
	if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}
	$stringtofind="$stringtofind*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$ldap=new clladp();
	$tpl=new templates();
	
	$IsKerbAuth=$ldap->IsKerbAuth();
	
	if($IsKerbAuth==0){
		if($EnableOpenLDAP==1){
			$MAIN_HASH=$ldap->GroupsSearch($stringtofind,50);
		}
		
	}else{
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$MAIN_HASH=$ad->GroupsSearch(null,$stringtofind,50);
		
	}	
	
	
	$TABLE[]="<table style='width:100%'>";
	$colortr=null;
	if(is_array($MAIN_HASH)){
	
		while (list ($GroupDN, $displayname) = each ($MAIN_HASH) ){
			if($colortr=="f0f9ec"){$colortr="FFFFFF";}else{$colortr="f0f9ec";}
			if(strpos($GroupDN,"dc=pureftpd,dc=organizations")>0){continue;}
			if($displayname==null){continue;}
			
			if($IsKerbAuth==1){
				$CountofUsers=$ad->CountDeUsersByGroupDN($GroupDN);
			}else{
				$CountofUsers=$ldap->CountDeUsersByGroupDN($GroupDN);
			}

			$TABLE[]="<tr style='height:60px;background-color:$colortr'>";
			
			if($IsKerbAuth==1){
				$jsGRP="Loadjs('domains.edit.group.php?js=yes&group-id=".urlencode($GroupDN)."',true)";
			}else{
				$GidNumberGroupDN=$ldap->GidNumberGroupDN($GroupDN);
				$jsGRP="Loadjs('domains.edit.group.php?js=yes&group-id=$GidNumberGroupDN',true)";
			}
			$picture_link="img/group-48.png";
			$linkuser="<a href\"javascript:blur();\" OnClick=\"javascript:$jsGRP\"
			style='text-decoration:underline'>";
			
			
			$TABLE[]="<td style='width:48px;'>";
			$TABLE[]="<img src='$picture_link' style='border-radius: 50% 50% 50% 50%; box-shadow: 0 0 5px silver;height: 48px;margin: 0 32px;width: 48px;'>";
			$TABLE[]="</td>";
			$TABLE[]="<td style='width:300px;padding-left:5px'>";
			$TABLE[]="<span style='font-size:18px'>$linkuser$displayname</a></span>";
			$TABLE[]="</td>";

			$TABLE[]="<td style='width:300px;padding-left:5px'>";
			$TABLE[]="<span style='font-size:18px'>$CountofUsers {members}</span>";
			$TABLE[]="</td>";
			
			$TABLE[]="<td>";
			$TABLE[]="<span style='font-size:18px;padding-left:5px;width:300px;'>".@implode("", $telephonenumber)."</span>";
			$TABLE[]="</td>";
			
			$TABLE[]="<td>";
			$TABLE[]="<span style='font-size:18px;padding-left:5px'>$GroupsTableau</span>";
			$TABLE[]="</td>";
			
			$TABLE[]="</tr>";
	
		}
	
	}	
	
	
	$TABLE[]="</table>";
	
	
	$TABLE[]="<script>";
	$TABLE[]="document.getElementById('title-$t').innerHTML='$stringtofind';";
	$TABLE[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("", $TABLE));
	
}


function autocomplete_search(){
	$stringtofind=trim($_GET["q"])."*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$ldap=new clladp();
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$IsKerbAuth=$ldap->IsKerbAuth();
	
	if($IsKerbAuth==0){
		if($EnableOpenLDAP==1){
			$MAIN_HASH=$ldap->GroupsSearch($stringtofind,50);
		}
	}else{
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$MAIN_HASH=$ad->GroupsSearch(null,$stringtofind,50);
		
	}
	
	if(is_array($MAIN_HASH)){
	
		while (list ($dn, $displayname) = each ($MAIN_HASH) ){
			if(strpos($dn,"dc=pureftpd,dc=organizations")>0){continue;}
			$f[]="$displayname";
			

		}
		
	}
	
	echo json_encode($f);
	
}



function finduser_list(){
	$keycached="{$_GET["finduser"]}";

	header("Pragma: no-cache");
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	$GLOBALS["OUTPUT_DEBUG"]=false;
	$stringtofind=trim($_GET["finduser"]);
	if($_POST["query"]<>null){$stringtofind=$_POST["query"];}
	if(!isset($_POST["rp"])){$_POST["rp"]=15;}

	$users=new usersMenus();
	$sock=new sockets();
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}

	if(preg_match("#debug:(.+)#",$stringtofind,$re)){
		$GLOBALS["OUTPUT_DEBUG"]=true;
		$stringtofind=trim($re[1]);
	}

	if($GLOBALS["OUTPUT_DEBUG"]){echo "Want to search $stringtofind<br>";}
	$tpl=new templates();
	$usermenu=new usersMenus();
	$ldap=new clladp();
	if(!$ldap->IsKerbAuth()){

		if($usermenu->AsAnAdministratorGeneric==true){
			if($GLOBALS["OUTPUT_DEBUG"]){echo "It is an administrator search in the entire tree<br>";}
			$hash_full=$ldap->UserSearch(null,$stringtofind,$_POST["rp"]);
				
		}else{
			$us=$ldap->UserDatas($_SESSION["uid"]);
			if($GLOBALS["OUTPUT_DEBUG"]){echo "It is an user search in the {$us["ou"]} tree<br>";}
			$hash_full=$ldap->UserSearch($us["ou"],$stringtofind,$_POST["rp"]);
		}

		$hash1=$hash_full[0];
		$hash2=$hash_full[1];
		if($GLOBALS["OUTPUT_DEBUG"]){echo "Search results ".
				count($hash1) ." users and ".
				count($hash2)." contacts<br>";}


	}else{
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$hash_full=$ad->UserSearch(null,$stringtofind,$_POST["rp"]);
		$hash1=$hash_full[0];
		$hash2=$hash_full[1];
		if($GLOBALS["OUTPUT_DEBUG"]){echo "Search results ".
				count($hash1) ." users and ".
				count($hash2)." contacts<br>";}

	}




	$hash=array();
	$count=0;

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	if(is_array($hash1)){
		if($GLOBALS["OUTPUT_DEBUG"]){echo "<strong>Search results ->HASH1</strong><br>\n";}

		while (list ($num, $ligne) = each ($hash1) ){


			if(isset($ligne["samaccountname"][0])){$ligne["uid"][0]=$ligne["samaccountname"][0];}
			if($ligne["uid"][0]==null){
				if(preg_match("#^CN=(.+?),#i", $ligne["dn"],$re)){
					$ligne["uid"][0]=$re[1];
					$hash[$count]["displayname"][0]=$re[1];
				}
					
			}
			if($EnableManageUsersTroughActiveDirectory==0){	if(($ligne["uid"][0]==null) && ($ligne["employeenumber"][0]==null)){continue;}}
			if(strpos($ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
			$hash[$count]["displayname"][0]=trim($ligne["displayname"][0]);
			$hash[$count]["givenname"][0]=$ligne["givenname"][0];
			if($EnableManageUsersTroughActiveDirectory==1){
				$hash[$count]["uid"][0]=$ligne["samaccountname"][0];
			}else{
				$hash[$count]["uid"][0]=$ligne["uid"][0];
			}
			if(substr($hash[$count]["uid"][0],strlen($hash[$count]["uid"][0])-1,1)=='$'){continue;}

			$hash[$count]["employeenumber"][0]=$ligne["employeenumber"][0];
			$hash[$count]["title"][0]=$ligne["title"][0];
			$hash[$count]["uri"][0]=$ligne["uri"][0];
			$hash[$count]["mail"][0]=$ligne["mail"][0];
			$hash[$count]["phone"][0]=$ligne["telephonenumber"][0];
			$hash[$count]["sn"][0]=$ligne["sn"][0];
			$hash[$count]["dn"]=$ligne["dn"];
			$count++;

		}}else{
			if($GLOBALS["OUTPUT_DEBUG"]){echo "<strong>Search results ->HASH1 NOT AN ARRAY</strong><br>\n";}
		}



		if(is_array($hash2)){
			if($GLOBALS["OUTPUT_DEBUG"]){echo "<strong>Search results ->HASH2</strong><br>\n";}
			while (list ($num, $ligne) = each ($hash2) ){
				if(isset($ligne["samaccountname"][0])){$ligne["uid"][0]=$ligne["samaccountname"][0];}
				if(($ligne["uid"][0]==null) && ($ligne["employeenumber"][0]==null)){continue;}

				if(strpos($ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
				$hash[$count]["displayname"][0]=$ligne["displayname"][0];
				$hash[$count]["givenname"][0]=$ligne["givenname"][0];
				$hash[$count]["uid"][0]=$ligne["uid"][0];
				$hash[$count]["employeenumber"][0]=$ligne["employeenumber"][0];
				$hash[$count]["title"][0]=$ligne["title"][0];
				$hash[$count]["uri"][0]=$ligne["uri"][0];
				$hash[$count]["mail"][0]=$ligne["mail"][0];
				$hash[$count]["phone"][0]=$ligne["telephonenumber"][0];
				$hash[$count]["sn"][0]=$ligne["sn"][0];
				$hash[$count]["dn"]=$ligne["dn"];
				$count=$count+1;

			}}else{
				if($GLOBALS["OUTPUT_DEBUG"]){echo "<strong>Search results ->HASH2 NOT AN ARRAY</strong><br>\n";}
			}


			$count=count($hash);
			$data['total'] = $count;
			if($count==0){json_error_show("no data",1);}
			if($GLOBALS["OUTPUT_DEBUG"]){echo "<strong>Search results $count items</strong><br>\n";}


			if(is_array($hash)){

				while (list ($num, $ligne) = each ($hash) ){
					if($GLOBALS["OUTPUT_DEBUG"]){echo "dn:{$ligne["dn"]}<br>";}
					if($GLOBALS["OUTPUT_DEBUG"]){echo "uid:{$ligne["uid"][0]}<br>";}
					if($GLOBALS["OUTPUT_DEBUG"]){echo "employeenumber:{$ligne["employeenumber"][0]}<br>";}
					if(($ligne["uid"][0]==null) && ($ligne["employeenumber"][0]==null)){
						if($GLOBALS["OUTPUT_DEBUG"]){echo "null twice, aborting...<br>";}
						continue;
					}
						
					if($ligne["uid"][0]=="squidinternalauth"){$count=$count-1;continue;}
						
					if($GLOBALS["OUTPUT_DEBUG"]){echo "edit_config_user={$ligne["uid"][0]}<br>";}
						
					$edit_config_user=MEMBER_JS($ligne["uid"][0],1,0,$ligne["dn"]);
						
					if($usermenu->AllowAddUsers==true){$uri=$edit_config_user;}else{$uri=null;}
					if($usermenu->AsOrgAdmin==true){$uri=$edit_config_user;}else{$uri=null;}
					if($usermenu->AsArticaAdministrator==true){$uri=$edit_config_user;}else{$uri=null;}
						
						
						
					$displayname=trim($ligne["displayname"][0]);
					$givenname=$ligne["givenname"][0];
					$mail=$ligne["mail"][0];
						
					if($displayname==null){$displayname=$ligne["uid"][0];}
					if($givenname==null){$givenname='{unknown}';}
					if($mail==null){$mail='{unknown}';}

					if($ligne["employeenumber"][0]<>null){
						$array["employeenumber"]=$ligne["employeenumber"][0];
						$user=new contacts($_SESSION["uid"],$ligne["employeenumber"][0]);
						$array["title"]=$user->displayName;
						$uri="javascript:Loadjs('contact.php?employeeNumber={$ligne["employeenumber"][0]}')";

					}else{
						if($ligne["uid"][0]<>null){
							$array["title"]=$ligne["uid"][0];
							$user=new user($ligne["uid"][0]);
								
								
						}
					}
						
					if(strlen($user->jpegPhoto)>0){$array["img"]=$user->img_identity;}else{$array["img"]="img/contact-unknown-user.png";}
					writelogs("identity:$user->img_identity ",__FUNCTION__,__FILE__);
					$array["uri"]=$uri;
					$array["mail"]=$ligne["mail"][0];;
					$array["phone"]=$ligne["telephonenumber"][0];
					$array["sn"]=$ligne["sn"][0];
					if(!$ldap->EnableManageUsersTroughActiveDirectory){
						if($displayname==null){$displayname="$givenname {$ligne["sn"][0]}";}
					}
					$array["displayname"]=$displayname;
					$array["givenname"]=$givenname;
					$array["JS"]=$edit_config_user;
					$array["title"]=$ligne["title"][0];;;
					$array["ou"]=$user->ou;
					$array["uid"]=$ligne["uid"][0];

					$data['rows'][] =finduser_format($array);


				}
			}

			echo json_encode($data);

}
