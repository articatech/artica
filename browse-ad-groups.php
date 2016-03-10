<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	include_once("ressources/class.harddrive.inc");
	include_once("ressources/class.external.ad.inc");
	include_once("ressources/class.ActiveDirectory.inc");
	
	if(isset($_GET["RightPan"])){RightPan();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["browser-infos"])){brower_infos();exit;}
	if(isset($_POST["create-folder"])){folder_create();exit;}
	if(isset($_POST["delete-folder"])){folder_delete();exit;}
	if(isset($_GET["browse-dn"])){browse_groups_for_ou();exit;}
	if(isset($_GET["RightPan"])){RightPan();exit;}
	if(isset($_GET["groups-items"])){items_groups();exit;}
	if(isset($_GET["users-items"])){items_users();exit;}
	if(isset($_GET["popup-table"])){popup_table();exit;}
	if(isset($_GET["search-groups"])){popup_search();exit;}
	js();

function js(){
	header("content-type: application/x-javascript");
	$t=time();
	$title=$_GET["ou"];
	$_GET["ou"]=urlencode($_GET["ou"]);
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	$page=CurrentPageName();
	
	$ad=new external_ad_search();
	
	$ad->suffix;
	if($title==null){$title=$ad->suffix;}
	//echo "YahooWinBrowse(966,'$page?popup=yes&ADID={$_GET["ADID"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}','Browse::$title');";
	echo "YahooWinBrowse(650,'$page?popup-table=yes&ADID={$_GET["ADID"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}','Browse::$title');";
}

function popup(){
	$tpl=new templates();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$ad=new external_ad_search();
	$ous=$ad->SearchOuSimple(null);
	$root=$ad->KerbAuthInfos["ADNETBIOSDOMAIN"];
	$users=new usersMenus();
	
	$adAD=new ActiveDirectory();
	if($adAD->ldapFailed){
		$adAD->ldap_last_error=nl2br($adAD->ldap_last_error);
		echo FATAL_ERROR_SHOW_128_DESIGN("{error_ad_ldap}","{error}:LDAP&nbsp;&raquo;&nbsp;Active Directory ($adAD->ldap_host:$adAD->ldap_port)</strong><hr>$adAD->ldap_last_error","GotoActiveDirectoryLDAPParams()");
	}
	
	
	
	
	if(!$users->CORP_LICENSE){
		$Days=86400*30;
		$DayToLeft=30;
		if(is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){
			include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
			$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
			$seconds_diff = $EndTime - time();
			$DayToLeft=floor($seconds_diff/3600/24);
		}
		
		$MAIN_ERROR=$tpl->_ENGINE_parse_body("<p class=text-error style='font-size:18px'>
		{warn_no_license_activedirectory_30days}</p>");
		$MAIN_ERROR=str_replace("%s", $DayToLeft, $MAIN_ERROR);
	}
	

	$style=" style='font-size:13px' OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$f[]="$MAIN_ERROR<table style='width:100%'>";
	$f[]="<tr>";
	$f[]="<td width=30% style='vertical-align:top'>";
	$f[]="<div style='width:400px;' class=form>";
	$f[]="<ul id='root-$t' class='jqueryFileTree'>";
	$f[]="<li class=ou style='font-size:14px'>$root";
	$f[]="<ul id='mytree-$t' class='jqueryFileTree'>";
	

	
	while (list ($dn, $ounameArray) = each ($ous) ){
		$CLASS="ou";
		$ouname=$ounameArray["NAME"];
		if(isset($ounameArray["objectClass"]["container"])){$CLASS="container";}
		$id=md5("$ouname$dn");
		$js=texttooltip("$ouname",$ouname ,"TreeOuExpand$t('$id','$dn');");
		$f[]="<li class=$CLASS collapsed id='$id' $style>$js</li>";
		
	}
	
	$f[]="</ul>";
	$f[]="</li>";
	$f[]="</ul>";
	$f[]="</div>";
	$f[]="</td>";
	$f[]="<td width=510px style='vertical-align:top;padding-left:10px;border-left:3px #CCCCCC'>";
	$f[]="<div id='content-$t'></div>";
	$f[]="</td>";
	$f[]="</tr>";
	$f[]="</table>";
	
$f[]="<script>
var mem_id$t='';
var mem_path$t='';


function RightPan(){
	dn=encodeURIComponent(mem_path$t);
	LoadAjax('content-$t','$page?RightPan=yes&ADID={$_GET["ADID"]}&dn='+dn+'&field-type={$_GET["field-type"]}&field-user={$_GET["field-user"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}',true);
}

function DirectPan(dn){
	mem_path$t=dn;
	RightPan();
}



var xTreeOuExpand$t= function (obj) {
	var results=obj.responseText;
	$('#'+mem_id$t).removeClass('collapsed');
	if($('#'+mem_id$t).hasClass('ou')){\$('#'+mem_id$t).addClass('ouExpanded');}
	if($('#'+mem_id$t).hasClass('container')){\$('#'+mem_id$t).addClass('containerExpanded');}
	$('#'+mem_id$t).append(results);
	RightPan(mem_path$t);
}

	function TreeOuExpand$t(id,DN){
		mem_id$t=id;
		mem_path$t=DN;
		var expanded=false;
		if($('#'+mem_id$t).hasClass('ouExpanded')){expanded=true;}
		if($('#'+mem_id$t).hasClass('containerExpanded')){expanded=true;}
		if(!expanded){if($('#'+mem_id$t).hasClass('ouExpanded')){expanded=true;}}
			
		if(!expanded){
			var XHR = new XHRConnection();
			XHR.appendData('browse-dn',encodeURIComponent(DN));
			XHR.appendData('function','{$_GET["function"]}');
			XHR.appendData('field-user','{$_GET["field-user"]}');
			XHR.appendData('t','{$_GET["t"]}');
			XHR.appendData('CallBack2','{$_GET["CallBack2"]}');
			XHR.sendAndLoad('$page', 'GET',xTreeOuExpand$t);
		}else{
			$('#'+mem_id$t).children('ul').empty();
			if($('#'+mem_id$t).hasClass('ouExpanded')){\$('#'+mem_id$t).removeClass('ouExpanded');}
			if($('#'+mem_id$t).hasClass('containerExpanded')){\$('#'+mem_id$t).removeClass('containerExpanded');}
			$('#'+mem_id$t).addClass('collapsed');
	
		}
	}
	
		
</script>";
	
echo @implode("\n", $f);

}

function browse_groups_for_ou(){
	$DN=url_decode_special_tool($_GET["browse-dn"]);
	$t=$_GET["t"];
	$function=$_GET["function"];
	$field_user=$_GET["field-user"];
	$tpl=new templates();
	$ad=new external_ad_search();
	$ous=$ad->SearchOuSimple($DN);
	$id_root=md5($DN."1");
	$style=" style='font-size:13px' OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	
	
	if(count($ous)>0){
		$f[]="<ul id='ou-$id_root' class='jqueryFileTree'>";
		while (list ($dn, $ounameArray) = each ($ous) ){
			$CLASS="ou";
			$ouname=$ounameArray["NAME"];
			if(isset($ounameArray["objectClass"]["container"])){$CLASS="container";}
			$id=md5("$ouname$dn");
			$js=texttooltip("$ouname",$ouname ,"TreeOuExpand$t('$id','$dn');");
			$f[]="<li class=$CLASS collapsed id='$id' $style>$js</li>";
		
		}
		
		$f[]="</ul>";
	}
	
	
	$groups=$ad->searchGroupSimple($DN);
	
	if(count($groups)>0){
		$f[]="<ul id='group-$id_root' class='jqueryFileTree'>";
		ksort($groups);
		$group=$tpl->_ENGINE_parse_body("{group2}");
		while (list ($dnsearch, $groupname) = each ($groups) ){
			if($GLOBALS["VERBOSE"]){echo "$num -> $groupname<br>\n";}
			$id=md5($dnsearch);
			$CLASS="group";
			$f[]="<li class=$CLASS collapsed id='$id' >
			<a href=\"#\" 
			OnClick=\"javascript:DirectPan('$dnsearch');\"
			$style>$groupname</a>
			</li>";
		}
	
		$f[]="</ul>";	
	}
	
	echo @implode("\n", $f);
	
}


function RightPan(){
	$DN=url_decode_special_tool($_GET["dn"]);
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	for($z=0;$z<$hash[0]["objectclass"]["count"];$z++){
		$FINAL["objectClass"][$hash[0]["objectclass"][$z]]=true;
	
	}
	
	
	
	if( ( isset($FINAL["objectClass"]["container"]) ) OR ( isset($FINAL["objectClass"]["organizationalUnit"]) ) ){
		Interface_SearchGroups($DN);
		return;
	}
	Interface_SearchUsers($DN);
}

function Interface_SearchGroups($DN){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$field_user=$_GET["field-user"];
	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$items=$tpl->javascript_parse_text("{items}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$select_this_group=$tpl->javascript_parse_text("{select_this_group}");
	if(!isset($_GET["CallBack2"])){$_GET["CallBack2"]=null;}
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	$tt=md5(time());
	
	$miniexplain=$tpl->_ENGINE_parse_body("{browse_ad_groups_explain}");
	
	$description=$hash[0]["description"][0];
	if(isset($hash[0]["name"][0])){$name=$hash[0]["name"][0];}
	if(isset($hash[0]["ou"][0])){$name=$hash[0]["ou"][0];}
	$title="$name<br><span style=font-size:10px;font-style:italic>$description</span>";
	
	for($z=0;$z<$hash[0]["objectclass"]["count"];$z++){
		$FINAL["objectClass"][$hash[0]["objectclass"][$z]]=true;
		
	}
	
	$fieldtype=$_GET["field-type"];
	if(!is_numeric($fieldtype)){$fieldtype=0;}
	$OPENBT=false;
	$DN_enc=urlencode($DN);	
	$DN_base64=base64_encode($DN);
	if(isset($FINAL["objectClass"]["group"])){
	if($_GET["field-user"]<>null){
		$OPENBT=true;
		$select="{name: '$select_this_group', bclass: 'Down', onpress : Select$tt},";
	}
	}
	
	if($_GET["CallBack2"]<>null){
		$CallBack2="{$_GET["CallBack2"]}(base64,Name);";
	}
	
	
$buttons="
	buttons : [
		{name: '$parameters', bclass: 'Group', onpress : Parameters$tt},
		$select
		],";
if(!$OPENBT){$buttons=null;}
$html="
<div style='font-size:14px' class=explain>$miniexplain</div>
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?groups-items=yes&tt=$tt&DN=$DN_enc&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&CallBack2={$_GET["CallBack2"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '$groupname', name : 'groupname', width :295, sortable : false, align: 'left'},
	{display: '$items', name : 'items', width :32, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
		{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '500',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,700,1000]
	});
}

function EditField$tt(base64,Name){
	var fieldtype='$fieldtype';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('$field_user')){
		if(fieldtype==2){
			document.getElementById('$field_user').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}
		if(fieldtype==3){
			document.getElementById('$field_user').value=Name;
			alert('$item_add `'+Name+'`');
			$CallBack2
			return;
		}		
		document.getElementById('$field_user').value=base64;
		alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
		
	}
	$CallBack2
}

function Select$tt(){
	EditField$tt('$DN_base64','$name');
}

Start$tt();
</script>
";
echo $html;
	
}

function items_users(){
	$search=string_to_flexquery();
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($_GET["DN"]);
	
	if($GLOBALS["VERBOSE"]){
		print_r($hash);
	}
	
	if($hash[0]["member"]["count"]==0){
		json_error_show("$search no data");
	}
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=50;}
	if($hash[0]["member"]["count"]>$_POST["rp"]){$hash[0]["member"]["count"]=$_POST["rp"];}
	$data = array();
	$data['page'] = 1;
	$data['total'] = $hash[0]["member"]["count"];
	$data['rows'] = array();
	$fontsize=14;
	$c=0;
	for($i=0;$i<$hash[0]["member"]["count"];$i++){
		$dn=$hash[0]["member"][$i];
		
		$hash2=$ad->DNinfos($dn);
		if(!isset($hash2[0])){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:#d32d2d'>DNinfos($dn) Failed</strong><br>\n";}
			continue;
		}
		$sn=$hash2[0]["sn"][0];
		$displayname=$hash2[0]["displayname"][0];
		$samaccountname=$hash2[0]["displayname"][0];
		$description=$hash2[0]["description"][0];
		if(isset($hash2[0]["userprincipalname"][0])){
			$email=$hash2[0]["userprincipalname"][0];
		}
		if($displayname==null){$displayname=$samaccountname;}
		if($displayname==null){$displayname=$hash2[0]["cn"][0];;}
		
		if($search<>null){
			if(!preg_match("#$search#", "$sn $displayname $samaccountname $email")){continue;}
		}
		$c++;
		
		$color="black";
		
		$description="<br><span style='font-size:12px;font-style:italic;font-weight:normal'>$description - $samaccountname $email</span>";
		$data['rows'][] = array(
				'id' => md5($dn),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$displayname$description</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
		
						,)
		);
		
	}
	
	$data['total'] = $c;
	echo json_encode($data);
}

function items_groups(){
	
	
	$tt=$_GET["tt"];
	$search=$_POST["query"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$field_user=$_GET["field-user"];
	
	$ad=new external_ad_search();
	$array=$ad->SearchGroups($search,$_GET["DN"],$_POST["rp"]);
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $array["count"];
	$data['rows'] = array();
	$fontsize=13;
	$c=0;
	for($i=0;$i<$array["count"];$i++){
		$c++;
			$color="black";
			$DN=$array[$i]["dn"];
			$samaccountname=$array[$i]["samaccountname"][0];
			$description=$array[$i]["description"][0];
			$itemsNum=$array[$i]["member"]["count"];
			if($samaccountname==null){$samaccountname=$array[$i]["cn"][0];}
			if(!is_numeric($itemsNum)){$itemsNum=0;}
			$select="&nbsp;";
			
			$DN_enc=urlencode($DN);
			$FicheGroup="Loadjs('domains.edit.group.php?ou=ABC&js=yes&group-id=$DN_enc',true)";
			$editjs="<a href=\"javascript:blur();\" OnClick=\"$FicheGroup\" style='text-decoration:underline;font-size:{$fontsize}px;font-weight:bold;'>";
			
			if($field_user<>null){
				$base64=base64_encode($DN);
				$select=imgsimple("arrow-right-24.png",null,"EditField$tt('$base64','$samaccountname')");
			}
			
			if($description<>null){$description="<br><span style='font-size:10px;font-style:italic;font-weight:normal'>$description</span>";}
			$data['rows'][] = array(
			'id' => md5($DN),
			'cell' => array(
				"<img src='img/wingroup.png'>",
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$samaccountname</a>$description</span>",
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$itemsNum</a></span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$select</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
	
			,)
		);
	}
	
	$array=$ad->SearchUsers($search,$_GET["DN"],$_POST["rp"]);
	
	for($i=0;$i<$array["count"];$i++){
		if($c>$_POST["rp"]){break;}
		$c++;
		$color="black";
		$DN=$array[$i]["dn"];
		$samaccountname=$array[$i]["samaccountname"][0];
		$description=$array[$i]["description"][0];
		$itemsNum="-";
		if($samaccountname==null){$samaccountname=$array[$i]["cn"][0];}
		$select="&nbsp;";
		$jsUser=MEMBER_JS($samaccountname,0,0,$DN);
		$editjs="<a href=\"javascript:blur();\" $jsUser style='text-decoration:underline;font-size:{$fontsize}px;font-weight:bold;'>";
		if($description<>null){$description="<br><span style='font-size:10px;font-style:italic;font-weight:normal'>$description</span>";}
		$data['rows'][] = array(
				'id' => md5($DN),
				'cell' => array(
						"<img src='img/user-18.png'>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$samaccountname$description</span>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$itemsNum</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$itemsNum</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
	
						,)
		);
	}

	if($c==0){
		json_error_show("$search no data");
	}
	
	$data['total'] = $c;
	echo json_encode($data);	
	
}



function Interface_SearchUsers($DN){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();

	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groupname=$tpl->javascript_parse_text("{members}");
	$privilegesandparameters=$tpl->javascript_parse_text("{privilegesandparameters}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$items=$tpl->javascript_parse_text("{items}");
	$select_this_group=$tpl->javascript_parse_text("{select_this_group}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$ad=new external_ad_search();
	$hash=$ad->DNinfos($DN);
	$DN_enc=urlencode($DN);
	$tt=md5($DN);
	$DN_ENC=urlencode($DN);
	if(!isset($_GET["CallBack2"])){$_GET["CallBack2"]=null;}
	$description=$hash[0]["description"][0];
	$name=$hash[0]["samaccountname"][0];
	$title="$name<br><span style=font-size:10px;font-style:italic>$description</span>";
	$field_user=$_GET["field-user"];
	$fieldtype=$_GET["field-type"];
	$FicheGroup="Loadjs('domains.edit.group.php?ou=ABC&js=yes&group-id=$DN_enc&field-type={$_GET["field-type"]}',true)";
	

	if($_GET["CallBack2"]<>null){
		$CallBack2="{$_GET["CallBack2"]}(base64,Name);";
	}
	
	$OPENBT=false;
	$DN_enc=urlencode($DN);
	$DN_base64=base64_encode($DN);
	if($_GET["field-user"]<>null){
			$OPENBT=true;
			$select="{name: '$select_this_group', bclass: 'Down', onpress : Select$tt},";
		}
	


	$buttons="
	buttons : [
	{name: '$privilegesandparameters', bclass: 'Group', onpress : Parameters$tt},$select
	],";
	
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?users-items=yes&tt=$tt&DN=$DN_ENC&field-user={$_GET["field-user"]}',
	dataType: 'json',
	colModel : [
	{display: '$groupname', name : 'groupname', width :382, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '500',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

function Parameters$tt(){
	$FicheGroup
}

function EditField$tt(base64,Name){
	var fieldtype='$fieldtype';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('$field_user')){
		if(fieldtype==2){
			document.getElementById('$field_user').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}
		
		if(fieldtype==3){
			document.getElementById('$field_user').value=Name;
			alert('$item_add `'+Name+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}		
		
		document.getElementById('$field_user').value=base64;
		alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
		$CallBack2
		
	}
}

function Select$tt(){
	EditField$tt('$DN_base64','$name');
}


Start$tt();
</script>
		";
		echo $html;

}


function popup_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);WinORGHide();return;";}
	$GroupName=$tpl->_ENGINE_parse_body("{groupname}");
	$Members=$tpl->_ENGINE_parse_body("{members}");
	$item_add=$tpl->javascript_parse_text("{item_added}");
	$Select=$tpl->javascript_parse_text("{select}");
	$title=$tpl->javascript_parse_text("{browse_active_directory_groups}");
	$t=time();
	$CallBack2=null;
	
	if($_GET["CallBack2"]<>null){
		$CallBack2="{$_GET["CallBack2"]}(base64,Name);";
	}
	
	$html="
	<table class='table$t' style='display: none' id='table$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#table$t').flexigrid({
	url: '$page?search-groups=yes&t=$t&ADID={$_GET["ADID"]}&field-user={$_GET["field-user"]}&field-type={$_GET["field-type"]}&function={$_GET["function"]}&t=$t&CallBack2={$_GET["CallBack2"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'select', width : 42, sortable : false, align: 'center'},
	{display: '$GroupName', name : 'groupname', width : 372, sortable : true, align: 'left'},
	{display: '$Members', name : 'none', width : 67, sortable : false, align: 'center'},
	{display: '$Select', name : 'none', width : 79, sortable : false, align: 'center'},
	],
	
	searchitems : [
	{display: '$GroupName', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	function BrowseFindUserGroupClick(e){
	if(checkEnter(e)){BrowseFindUserGroup();}
	}
	
	var x_BrowseFindUserGroup=function (obj) {
	tempvalue=obj.responseText;
	document.getElementById('finduserandgroupsidBrwse').innerHTML=tempvalue;
	}
	
	
	function BrowseFindUserGroup(){
		LoadAjax('finduserandgroupsidBrwse','$page?query='+escape(document.getElementById('BrowseUserQuery').value)+'&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD');
	
	}
	
	
	function BrowseSelect$t(id,prependText,guid){
		$callback
		var prepend={$_GET["prepend"]};
		var prepend_gid={$_GET["prepend-guid"]};
		var OnlyGUID=$OnlyGUID;
		if(document.getElementById('{$_GET["field-user"]}')){
			var selected=id;
			if(OnlyGUID==1){
				document.getElementById('{$_GET["field-user"]}').value=guid;
				WinORGHide();
			return;
		}
	
		if(prepend==1){selected=prependText+id;}
		if(prepend_gid==1){
			if(guid>1){
				selected=prependText+id+':'+guid;
			}
		}
		document.getElementById('{$_GET["field-user"]}').value=selected;
		WinORGHide();
		}
	}
	
function EditField$t(base64,Name){
	var fieldtype='{$_GET["field-type"]}';
	var ADID='{$_GET["ADID"]}';
	if(document.getElementById('{$_GET["field-user"]}')){
		if(fieldtype==2){
			document.getElementById('{$_GET["field-user"]}').value='AD:'+ADID+':'+base64;
			alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}
		
		if(fieldtype==3){
			document.getElementById('{$_GET["field-user"]}').value=Name;
			alert('$item_add `'+Name+' - '+'`'+Name+'`');
			$CallBack2
			return;
		}		
		
		document.getElementById('{$_GET["field-user"]}').value=base64;
		alert('$item_add mode:'+fieldtype+' - '+'`'+Name+'`');
		$CallBack2
		
	}
}
</script>
";
echo $html;	
}

function popup_search(){
	
	$icon="win7groups-32.png";
	$ad=new external_ad_search();
	if($_POST["query"]==null){$_POST["query"]="*";}
	
	if(strpos(" {$_POST["query"]}", "*")==0){$_POST["query"]="*{$_POST["query"]}*";}
	$_POST["query"]=str_replace("**", "*", $_POST["query"]);
	$_POST["query"]=str_replace("**", "*", $_POST["query"]);
	
	$Array=$ad->flexRTGroups($_POST["query"],$_POST["rp"]);
	if($ad->error<>null){json_error_show($ad->error,1);}
	
	if(count($Array)==0){json_error_show("No item",1);}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($Array);
	$data['rows'] = array();
	
	while (list ($dn, $itemname) = each ($Array) ){
		$GroupxSourceName=$itemname;
		$GroupxName=$itemname;
		$GroupxName=replace_accents($GroupxName);
		$GroupxName=str_replace("'", "`", $itemname);
		
		$addtitile=null;
		$select=null;
		$dn_enc=base64_encode($dn);
		$DN_base64=base64_encode($dn);
		$itemnameenc=base64_encode($itemname);
		$CountDeUsers=$ad->CountDeUsersByGroupDN($dn);
		$link=null;
		$js="EditField{$_GET["t"]}('$DN_base64','$GroupxSourceName');";
		
	
		$image=imgsimple($icon,null,$js);
		$select=imgsimple("arrow-right-32.png",null,$js);
		
		if($CountDeUsers>0){
			$link="<a href=\"javascript:Loadjs('browse-ad-users-dn.php?DN=$DN_base64')\"
			style='text-decoration:underline'>";
		}
	
		$md5=md5($dn);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<center>$image</center>",
						"<span style='font-size:20px;'>$link$GroupxName</a></span>",
						"<center style='font-size:20px;'>$CountDeUsers</center>",
						"<center>$select</center>" )
		);
	}
	
	
	echo json_encode($data);	
	
}
