<?php
	$GLOBALS["ICON_FAMILY"]="organizations";
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	
	

	if(isset($_GET["ShowOrganizations"])){ ShowOrganizations();exit;}
	if(isset($_GET["ajaxmenu"])){echo "<div id='orgs'>".ShowOrganizations()."</div>";exit;}
	if(isset($_GET["butadm"])){echo butadm();exit;}
	if(isset($_GET["LoadOrgPopup"])){echo LoadOrgPopup();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["js-pop"])){popup();exit;}
	if(isset($_GET["countdeusers"])){COUNT_DE_USERS();exit;}
	if(isset($_GET["inside-tab"])){popup_inside_tabs();exit;}
	
function js(){
	if(GET_CACHED(__FILE__,__FUNCTION__,"js")){return;}
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{organizations}");
	$page=CurrentPageName();
	$html="f
	var timeout=0;
	
	function LoadOrg(){
		$('#BodyContent').load('$page?js-pop=yes');
	}
	
	function OrgfillpageButton(){
	var content=document.getElementById('orgs').innerHTML;
	if(content.length<90){
		setTimeout('OrgfillpageButton()',900);
		return;
	}
	
	LoadAjax('butadm','$page?butadm=yes');
	
	}
	
	LoadOrg();
	";
	
	SET_CACHED(__FILE__,__FUNCTION__,"js",$html);
	echo $html;
	
}


function popup_inside_tabs(){
	$page=CurrentPageName();
	$html="<div id='BodyContentInsideTabs'></div>
	
	<script>
	function LoadOrg2(){
		$('#BodyContentInsideTabs').load('$page?js-pop=yes');
	}
		
	function OrgfillpageButton(){
	var content=document.getElementById('orgs').innerHTML;
	if(content.length<90){
		setTimeout('OrgfillpageButton()',900);
		return;
	}
	
	LoadAjax('butadm','$page?butadm=yes');
	
	}
	
	LoadOrg2();	
	</script>
	";
	echo $html;
	
}

function popup_activedirectory(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$tr[]=Paragraphe("group-64.png", "{active_directory_users}", "{active_directory_users_browse_text}",
	"javascript:Loadjs('browse-ad-groups.php')"	,null,300	
			
	);

	if($users->SQUID_INSTALLED){
		$tr[]=Paragraphe("database-connect-settings-64.png", "{active_directory_connection}", "{active_directory_connection_parameters}",
		"javascript:Loadjs('squid.adker.php')",null,300
		);
	}
	
	
	$sock->getFrameWork("squid.php?ping-kdc=yes");
	$datas=unserialize(@file_get_contents("ressources/logs/kinit.array"));
	
	if(count($datas)>0){
		$img="error-64.png";
		$textcolor="#8A0D0D";
		$text=$datas["INFO"];
		if(preg_match("#Authenticated to#is", $text)){$img="ok64.png";$textcolor="black";}
		if(trim($text)<>null){$text=": $text";}
		$tr[]=Paragraphe("$img", "{active_directory}", $text, "javascript:Loadjs('squid.adker.php')",null,300 );
	}
	
		
	echo $tpl->_ENGINE_parse_body(CompileTr2($tr));
	
	
}


function popup(){
	
	$users=new usersMenus();
	$userClasse=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$t=time();
	
	
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		popup_activedirectory();
		return;
	}
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	$ZarafaField="{display: '&nbsp;', name : 'Zarafa', width :31, sortable : false, align: 'center'},";
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){$userClasse->WEBSTATS_APPLIANCE=true;}	
	if($userClasse->WEBSTATS_APPLIANCE){$userClasse->SQUID_INSTALLED=true;}
	
	
	if($users->ZARAFA_INSTALLED){
			$ZarafaEnableServer=$sock->GET_INFO("ZarafaEnableServer");
			if(!is_numeric($ZarafaEnableServer)){$ZarafaEnableServer=1;}
				if($ZarafaEnableServer==1){
					if($users->AsMailBoxAdministrator){
						$ZarafaField="{display: '<span style=font-size:20px>Zarafa</span>', name : 'Zarafa', width :98, sortable : false, align: 'center'},";
						$ZarafaUri="&zarafaF=1";
						$help="{name: '$online_help', bclass: 'Help', onpress : Zhelp$t},";
					}
				}
			}
			
	
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}	
	$users=new usersMenus();
	
	if($EnableManageUsersTroughActiveDirectory==1){
		$ldap=new ldapAD();
		$usersnumber=$ldap->COUNT_DE_USERS();
	}else{
		$ldap=new clladp();	
		$usersnumber=$ldap->COUNT_DE_USERS();
		$ldap->ldap_close();
	}
	
	$Totalusers=$tpl->_ENGINE_parse_body("{my_organizations}::<i>{this_server_store}:&nbsp;<strong>$usersnumber</strong>&nbsp;{users}</i>");			
	$organizations_parameters=$tpl->_ENGINE_parse_body("{organizations_parameters}");
	$add_new_organisation=$tpl->_ENGINE_parse_body("{add_new_organisation}");
	$organizations=$tpl->_ENGINE_parse_body("{organizations}");
	$users=$tpl->_ENGINE_parse_body("{users}");
	$groupsF=$tpl->_ENGINE_parse_body("{groupsF}");	
	$domains=$tpl->_ENGINE_parse_body("{domains}");	
	$actions=$tpl->_ENGINE_parse_body("{actions}");	
	$add_new_organisation_text=$tpl->javascript_parse_text("{add_new_organisation_text}");
	$update=$tpl->_ENGINE_parse_body("{update2}");
	if($users->AsArticaAdministrator){
		$parametersBT="{name: '<strong style=font-size:18px>$organizations_parameters</strong>', bclass: 'Reconf', onpress : organizations_parameters},";}
	if(butadm()<>null){
		
		$jsadd="TreeAddNewOrganisation$t";
	}else{
		$jsadd="nothingtodo";
	}
	
	
	$bb="<input type='hidden' name='add_new_organisation_text' id='add_new_organisation_text' value='". $tpl->javascript_parse_text("{add_new_organisation_text}")."'>";
	if(isset($_GET["ajaxmenu"])){$bc="&ajaxmenu=yes";}
	
	$bt_add_new="{name: '<strong style=font-size:18px>$add_new_organisation</strong>', bclass: 'add', onpress : $jsadd},";

	
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		$bt_add_new=null;
		$Totalusers=$tpl->_ENGINE_parse_body("{my_organizations}");
	}else{
		$TEXT_TO_CSV=$tpl->_ENGINE_parse_body("{TEXT_TO_CSV}");
		$CsvToLdap="{name: '<strong style=font-size:18px>$TEXT_TO_CSV</strong>', bclass: 'Copy', onpress : TEXT_TO_CSV},";
	}
	
	if(!$users->AsArticaAdministrator){
		if(!$users->AsPostfixAdministrator){
			$bt_add_new=null;
			$parametersBT=null;
			$CsvToLdap=null;
			$Totalusers=$tpl->_ENGINE_parse_body("{my_organizations}");
		}
	}
	
	
	$buttons="
	buttons : [
	$bt_add_new$parametersBT$CsvToLdap
		],";
	$html="
	$bb
	<input type='hidden' name='MAIN_PAGE_ORGANIZATION_LIST' id='MAIN_PAGE_ORGANIZATION_LIST' value='$t'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
<script>
OUIDMEM='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?ShowOrganizations=yes&t=$t$ZarafaUri$bc',
	dataType: 'json',
	colModel : [
		
		{display: '<span style=font-size:20px>$organizations</span>', name : 'ou', width :237, sortable : false, align: 'left'},
		$ZarafaField
		{display: '<span style=font-size:20px>$users</span>', name : 'users', width :153, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>&nbsp;</span>', name : 'nonex1', width : 50, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>$groupsF</span>', name : 'groups', width : 153, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>&nbsp;</span>', name : 'nonex2', width : 50, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>$domains</span>', name : 'domains', width : 153, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>&nbsp;</span>', name : 'nonex3', width : 50, sortable : false, align: 'center'},		
		{display: '<span style=font-size:20px>&nbsp;</span>', name : 'nonex4', width : 50, sortable : false, align: 'center'},
		{display: '<span style=font-size:20px>&nbsp;</span>', name : 'nonex5', width : 50, sortable : false, align: 'center'},
		
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$organizations', name : 'ou'},
		],
	sortname: 'ou',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$Totalusers</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});

function Zhelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=202','1024','900');
}

function ActiveDirectorySquid$t(){
	Loadjs('squid.adker.php',true);
}

function TEXT_TO_CSV(){
	Loadjs('csvToLdap.php',true);
}

	var x_TreeAddNewOrganisation$t= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
		$('#table-$t').flexReload();
	}
	
	function TreeAddNewOrganisation$t(){
		var texte='$add_new_organisation_text'
		var org=prompt(texte,'');
		if(org){
			var XHR = new XHRConnection();
			XHR.appendData('TreeAddNewOrganisation',org);
			XHR.sendAndLoad('domains.php', 'GET',x_TreeAddNewOrganisation$t);
			}
	}

		function organizations_parameters(){
			Loadjs('domains.organizations.parameters.php');
			
		}
		
		function ActiveDirectoryUpdate$t(){
			Loadjs('domains.activedirectory.update.php?flexigrid=table-$t');
		}
		
		function  nothingtodo(){
			alert('$ERROR_NO_PRIVS');
		}

</script>
";


$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;
}

function ShowOrganizations(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==true){
		if($GLOBALS["VERBOSE"]){echo "ORGANISATIONS_LIST()<br>\n";}
		ORGANISATIONS_LIST();
	
	}else{
		if($usersmenus->AllowAddGroup==true && $usersmenus->AsArticaAdministrator==false){
			if($GLOBALS["VERBOSE"]){echo "ORGANISTATION_FROM_USER()<br>\n";}
			ORGANISTATION_FROM_USER();
		}
	}
	
	
}

function butadm(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	if($usersmenus->EnableManageUsersTroughActiveDirectory){return null;}	
	if($usersmenus->ARTICA_META_ENABLED){if($sock->GET_INFO("AllowArticaMetaAddUsers")<>1){return null;}}
	if($usersmenus->AsArticaAdministrator==true){return 'ok';}
	return null;
}


function ORGANISTATION_FROM_USER(){
	$ldap=new clladp();
	$tpl=new templates();
	
	if($GLOBALS["VERBOSE"]){echo "Hash_Get_ou_from_users({$_SESSION["uid"]}...<br>\n";}
	
	$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
	
	if($GLOBALS["VERBOSE"]){
		print_r($hash);
		
	}
	
	if(!is_array($hash)){return null;}
	$t=$_GET["t"];
	$data = array();
	$data['page'] = 1;
	$data['total'] = 1;
	$data['rows'] = array();
	$ou_nozarafa_explain=$tpl->_ENGINE_parse_body("{ou_nozarafa_explain}");
	
	$ou=$hash[0];
	
	$ou_encoded=base64_encode($ou);
	
	$md=md5(serialize($hash).time());
	$md5S=$md;
	$uri="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$ou');";
	$usersNB=$ldap->CountDeUSerOu($ou);
	$GroupsNB=$ldap->CountDeGroups($ou);
	$DomainsNB=$ldap->CountDeDomainsOU($ou);
	
	
	$select=imgsimple("domain-32.png","{manage_organisations_text}",$uri);
	$adduser=imgsimple("folder-useradd-32.png","$ou<hr><b>{create_user}</b><br><i>{create_user_text}</i>","Loadjs('domains.add.user.php?ou=$ou_encoded&encoded=yes');");
	$addgroup=imgsimple("32-folder-group-add.png","$ou<hr><b>{add_group}</b><br><i>{add_a_new_group_in_this_org}</i>","Loadjs('domains.edit.group.php?popup-add-group=yes&ou=$ou&t=$t');");
	$SearchUser=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{members}</i>","Loadjs('domains.find.user.php?ou=$ou_encoded&encoded=yes');");
	$SearchGroup=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{groups}</i>","Loadjs('domains.find.groups.php?ou=$ou_encoded&encoded=yes&t=$t');");
	$searchDomain=imgsimple("loupe-32.png",
			"$ou<hr><b>{localdomains}</b>:<i>{localdomains_text}</i>",
			"Loadjs('domains.edit.domains.php?js=yes&ou=$ou&master-t=$t');");
	

	$array=array();
	$array[]="<a href=\"javascript:blur();\"
	OnClick=\"$uri\" style='font-size:26px;font-weight:bolder;text-transform:capitalize;
	text-decoration:underline'>$ou</strong></a>$OuZarafaText";
	
	if($_GET["zarafaF"]==1){
		$zarafaEnabled="zarafa-logo-32.png";
		if($NOZARAFA==1){$zarafaEnabled="zarafa-logo-32-grey.png";}
		$array[]="<center>".imgsimple($zarafaEnabled,"<strong style=font-size:26px>$ou:{APP_ZARAFA}</strong>
				<br>{ZARAFA_OU_ICON_TEXT}","Loadjs('domains.edit.zarafa.php?ou=$ou_encoded&t=$t')")."</center>";
	}else{
	$array[]="&nbsp;";
		
	}

	
	
	
	$array[]="<strong style='font-size:26px'>$usersNB</strong>";
	$array[]="<center style='font-size:16px'>$SearchUser</center>";
	
	$array[]="<strong style='font-size:26px'>$GroupsNB</strong>";
	$array[]="<center style='font-size:16px'>$SearchGroup</center>";
	
	$array[]="<strong style='font-size:26px'>$DomainsNB</strong>";
	$array[]="<center style='font-size:16px'>$searchDomain</center>";
	
	$array[]="<center style='font-size:16px'>$adduser</center>";
	$array[]="<center style='font-size:16px'>$addgroup</center>";
	$array[]="<center style='font-size:16px'>&nbsp;</center>";
	$data['rows'][] = array('id' => $md5S,'cell' => $array);

	$total =1;
	$data['page'] = 1;
	$data['total'] = $total;
	echo json_encode($data);
	
}

function ORGANISATIONS_LIST_ACTIVE_DIRECTORY(){
	$t=$_GET["t"];
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$Mypage=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$table="activedirectory_ou";
	$database="artica_backup";
	$page=1;
	$q=new mysql();
	if($_POST["sortname"]=="ou"){$_POST["sortname"]="name";}
	if($_POST["qtype"]=="ou"){$_POST["qtype"]="name";}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
	if(isset($_GET["ajaxmenu"])){$ajax=true;}
	$pic="32-environement.png";
	$style="style='font-size:16px;'";
	$c=0;
	$ldap2=new clladp();
		$ou=$ligne["name"];
		$ou_encoded=base64_encode($ou);
		$md=md5(serialize($ligne).time());
		$md5S=$md;
		if(is_numeric($ligne["dn"])){continue;}
		$DN=urlencode($ligne["dn"]);
		$uri="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$ou&dn=$DN');";
		if($ajax){$uri="javascript:Loadjs('$Mypage?LoadOrgPopup=$ou');";}
		$IsOUUnderActiveDirectory=$ldap2->IsOUUnderActiveDirectory($ou);
		$GroupsNB=$ligne["CountDeGroups"];
		$usersNB=$ligne["CountDeUsers"];
		$array=array();
	
		
		$DomainsNB=0;
		
		$select=imgsimple("domain-32.png","{manage_organisations_text}",$uri);
		$SearchUser=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{members}</i>","Loadjs('domains.find.user.php?ou=$ou_encoded&encoded=yes&dn=$DN');");
		$SearchGroup=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{groups}</i>","Loadjs('domains.find.groups.php?ou=$ou_encoded&encoded=yes&t=$t&dn=$DN');");
		$searchDomain=imgsimple("loupe-32.png",
		"$ou<hr><b>{localdomains}</b>:<i>{localdomains_text}</i>",
		"Loadjs('domains.edit.domains.php?js=yes&ou=$ou&master-t=$t&dn=$DN');");
	

		$delete=imgtootltip("delete-24-grey.png", "", "");
		$adduser=imgsimple("folder-useradd-32-grey.png");
		$addgroup=imgsimple("32-folder-group-add-grey.png");
		
	
	
		$actions="<table style=width:100%;border:0px;><tbody><tr style=background:transparent>
			<td width=1% style=border:0px>$adduser</td><td width=1% style='border:0px'>$addgroup</td></tr></tbody></table>";
			
			$array[]="<a href=\"javascript:blur();\" OnClick=\"$uri\" style='font-size:16px;font-weight:bolder;text-transform:capitalize;text-decoration:underline'>$ou</strong></a>";
	
			if($_GET["zarafaF"]==1){
				$zarafaEnabled="zarafa-logo-32.png";
				$array[]="<center>".imgsimple($zarafaEnabled,null,"Loadjs('domains.edit.zarafa.php?ou=$ou_encoded&t=$t')")."</center>";
			}else{
				$array[]="&nbsp;";
							
			}
	
	
		
	
	
	
	
		$array[]="<strong style='font-size:22px'>$usersNB</strong>";
		$array[]="<strong style='font-size:22px'>$SearchUser</strong>";
		$array[]="<strong style='font-size:22px'>$GroupsNB</strong>";
		$array[]="<strong style='font-size:22px'>$SearchGroup</strong>";
		$array[]="<strong style='font-size:22px'>$DomainsNB</strong>";
		$array[]="<strong style='font-size:22px'>$searchDomain</strong>";
		$array[]="<strong style='font-size:22px'>$actions</strong>";
		$array[]="<strong style='font-size:22px'>$delete</strong>";
		$c++;
		$data['rows'][] = array('id' => $md5S,'cell' => $array);
	
	
	}
	
	
	$total =$c;
	$data['page'] = 1;
	$data['total'] = $total;
	echo json_encode($data);	
	
}


function ORGANISATIONS_LIST(){
	$tpl=new templates();
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		return ORGANISATIONS_LIST_ACTIVE_DIRECTORY();
	}
	
	
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$Mypage=CurrentPageName();	
	$users=new usersMenus();
	$sock=new sockets();
	$ou_nozarafa_explain=$tpl->_ENGINE_parse_body("{ou_nozarafa_explain}");
	$t=$_GET["t"];
	$EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
	if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}	
	$AllowInternetUsersCreateOrg=$sock->GET_INFO("AllowInternetUsersCreateOrg");
	if($EnableManageUsersTroughActiveDirectory==1){
		$ldap = new ldapAD();
		$hash=$ldap->hash_get_ou(true);
		
	}else{
		$ldap=new clladp();
		$hash=$ldap->hash_get_ou(true);
	}
	if(!is_array($hash)){json_error_show("No data...");}
	ksort($hash);
	
	if($EnableManageUsersTroughActiveDirectory==0){
		if(!$ldap->BuildOrganizationBranch()){json_error_show("{GENERIC_LDAP_ERROR}<br>$ldap->ldap_last_error");}
	}

	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}

	if(isset($_GET["ajaxmenu"])){$ajax=true;}
	$pic="32-environement.png";
	$style="style='font-size:16px;'";
	$c=0;
	$ldap2=new clladp();
	
	if(count($hash)==0){
		json_error_show("no data");
	}
	
	while (list ($num, $ligne) = each ($hash) ){
		$ou=$ligne;
		$ou_encoded=base64_encode($ou);
		if(!preg_match("#$search#i", $ligne)){writelogs("'$ligne' NO MATCH $search",__FUNCTION__,__FILE__,__LINE__);continue;}
		$md=md5(serialize($hash).time());
		$md5S=$md;
		$uri="javascript:Loadjs('domains.manage.org.index.php?js=yes&ou=$ligne');";
		if($ajax){$uri="javascript:Loadjs('$Mypage?LoadOrgPopup=$ligne');";}
		$IsOUUnderActiveDirectory=$ldap2->IsOUUnderActiveDirectory($ou);
		$GroupsNB=0;
		

		if($EnableManageUsersTroughActiveDirectory==0){
			$img=$ldap->get_organization_picture($ligne,32);
			writelogs("ldap->CountDeUSerOu($ligne)",__FUNCTION__,__FILE__,__LINE__);
			$usersNB=$ldap->CountDeUSerOu($ligne);
			$usersNB="$usersNB";			
		}else{
			$img=$pic;
			if($IsOUUnderActiveDirectory){
				$ad=new external_ad_search();
				writelogs("ldap->CountDeUSerOu($ligne)",__FUNCTION__,__FILE__,__LINE__);
				$usersNB=$ad->CountDeUSerOu($ligne);
				
			}else{
				writelogs("ldap->CountDeUSerOu($ligne)",__FUNCTION__,__FILE__,__LINE__);
				$usersNB=$ldap->CountDeUSerOu($ligne);
				$usersNB="$usersNB";
			}
		}
		
		$delete=imgtootltip("delete-32-grey.png","<b>{delete_ou} $ligne</b><br><i>{delete_ou_text}</i>");	
		if($users->AsArticaAdministrator){
			$delete=Paragraphe('64-cancel.png',"{delete_ou} $ligne",'{delete_ou_text}',"javascript:Loadjs('domains.delete.org.php?ou=$ligne');",null,210,100,0,true);
			$delete=imgsimple("delete-32.png","<b>{delete_ou} $ligne</b><br><i>{delete_ou_text}</i>","javascript:Loadjs('domains.delete.org.php?ou=$ligne&t=$t&id-table=$md5S');");
		
		}

		
		
		$DomainsNB=$ldap->CountDeDomainsOU($ligne);
		if($GroupsNB==0){
			if($IsOUUnderActiveDirectory){
				$ad=new external_ad_search();
				writelogs("->CountDeGroups($ou)",__FUNCTION__,__FILE__,__LINE__);
				$GroupsNB=$ad->CountDeGroups($ou);
			}else{
				writelogs("->CountDeGroups($ou)",__FUNCTION__,__FILE__,__LINE__);
				$GroupsNB=$ldap->CountDeGroups($ou);
			}
		}
		Paragraphe('folder-useradd-64.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);
		Paragraphe('64-folder-group-add.png','{create_user}','{create_user_text}',"javascript:Loadjs('domains.add.user.php?ou=$ou')",null,210,null,0,true);
		Paragraphe("64-folder-group-add.png","$ou:{add_group}","{add_a_new_group_in_this_org}:<b>$ou</b>","javascript:Loadjs('domains.edit.group.php?popup-add-group=yes&ou=$ou&t=$t')");
		
		
		$select=imgsimple("domain-32.png","{manage_organisations_text}",$uri);
		$adduser=imgsimple("folder-useradd-32.png","$ou<hr><b>{create_user}</b><br><i>{create_user_text}</i>","Loadjs('domains.add.user.php?ou=$ou_encoded&encoded=yes');");
		$addgroup=imgsimple("32-folder-group-add.png","$ou<hr><b>{add_group}</b><br><i>{add_a_new_group_in_this_org}</i>","Loadjs('domains.edit.group.php?popup-add-group=yes&ou=$ou&t=$t');");
		$SearchUser=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{members}</i>","Loadjs('domains.find.user.php?ou=$ou_encoded&encoded=yes');");
		$SearchGroup=imgsimple("loupe-32.png","$ou<hr><b>{search}</b>:<i>{groups}</i>","Loadjs('domains.find.groups.php?ou=$ou_encoded&encoded=yes&t=$t');");
		$searchDomain=imgsimple("loupe-32.png",
		"$ou<hr><b>{localdomains}</b>:<i>{localdomains_text}</i>",
		"Loadjs('domains.edit.domains.php?js=yes&ou=$ou&master-t=$t');");
		$NOZARAFA=0;
		$OuZarafaText=null;
		if($IsOUUnderActiveDirectory){
			$delete=imgtootltip("delete-24-grey.png", "", "");
			$adduser=imgsimple("folder-useradd-32-grey.png");
			$addgroup=imgsimple("32-folder-group-add-grey.png");
		}
		
		if($_GET["zarafaF"]==1){
			$info=$ldap->OUDatas($ou);
			if(!$info["objectClass"]["zarafa-company"]){
				$NOZARAFA=1;
				$OuZarafaText="<br><a href=\"javascript:blur()\" style='color:#B20808;text-decoration:underline;font-style:italic' 
				OnClick=\"javascript:Loadjs('domains.edit.zarafa.php?ou=$ou_encoded&t=$t')\">$ou_nozarafa_explain</a>";
			}
		}
	
				
		$array=array();
		$array[]="<a href=\"javascript:blur();\" 
		OnClick=\"$uri\" style='font-size:26px;font-weight:bolder;text-transform:capitalize;
		text-decoration:underline'>$ligne</strong></a>$OuZarafaText";
		
		if($_GET["zarafaF"]==1){
			$zarafaEnabled="zarafa-logo-32.png";			
			if($NOZARAFA==1){$zarafaEnabled="zarafa-logo-32-grey.png";}	
			$array[]="<center>".imgsimple($zarafaEnabled,"<strong style=font-size:26px>$ou:{APP_ZARAFA}</strong>
					<br>{ZARAFA_OU_ICON_TEXT}","Loadjs('domains.edit.zarafa.php?ou=$ou_encoded&t=$t')")."</center>";
		}else{
			$array[]="&nbsp;";
			
		}			
		

		
		$array[]="<strong style='font-size:26px'>$usersNB</strong>";
		$array[]="<center style='font-size:16px'>$SearchUser</center>";
		
		$array[]="<strong style='font-size:26px'>$GroupsNB</strong>";
		$array[]="<center style='font-size:16px'>$SearchGroup</center>";
		
		$array[]="<strong style='font-size:26px'>$DomainsNB</strong>";
		$array[]="<center style='font-size:16px'>$searchDomain</center>";
		
		$array[]="<center style='font-size:16px'>$adduser</center>";
		$array[]="<center style='font-size:16px'>$addgroup</center>";
		$array[]="<center style='font-size:16px'>$delete</center>";
		$c++;
		$data['rows'][] = array('id' => $md5S,'cell' => $array);			
		
		
	}
	
	
	$total =$c;
	$data['page'] = 1;
	$data['total'] = $total;		
	echo json_encode($data);	
}

function LoadOrgPopup(){
	echo "
	Loadjs('js/artica_organizations.js');
	Loadjs('js/artica_domains.js');
	YahooWin(750,'domains.manage.org.index.php?org_section=0&SwitchOrgTabs={$_COOKIE["SwitchOrgTabs"]}&ou={$_GET["LoadOrgPopup"]}&ajaxmenu=yes','ORG::{$_GET["LoadOrgPopup"]}');	
	";
}


	