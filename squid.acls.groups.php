<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.external.ad.inc');
	include_once('ressources/class.ldap-extern.inc');
	
	$GLOBALS["GroupType"]["src"]="{src_addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_POST["DeleteAllGroup"])){DeleteAllGroup();exit;}
if(isset($_POST["authenticate_ip_ttl"])){authenticate_ip_ttl_save();exit;}
if(isset($_POST["RAD_SERVER"])){items_radius_save();exit;}
if(isset($_POST["AD_LDAP_PORT"])){items_radius_save();exit;}
if(isset($_POST["OPENLDAP_PASSWORD_ATTRIBUTE"])){items_radius_save();exit;}
if(isset($_POST["TimeSave"])){item_date_save();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}
if(isset($_GET["AddGroup-js"])){AddGroup_js();exit;}
if(isset($_GET["EditGroup-popup"])){EditGroup_popup();exit;}
if(isset($_GET["EditGroup-content"])){EditGroup_content();exit;}
if(isset($_GET["EditGroup-events"])){EditGroup_events();exit;}
if(isset($_GET["EditGroup-events-search"])){EditGroup_events_search();exit;}
if(isset($_POST["GroupName"])){EditGroup_save();exit;}
if(isset($_POST["DeleteTimeRule"])){EditTimeRule_delete();exit;}
if(isset($_POST["EnableGroup"])){EditGroup_enable();exit;}
if(isset($_POST["DeleteGroup"])){EditGroup_delete();exit;}
if(isset($_GET["acl-dynamic-virtual-form"])){item_acldyn_member();exit;}
if(isset($_GET["dynamic-acls-params"])){item_acldyn_params();exit;}
if(isset($_GET["authenticate_ip_ttl_form"])){authenticate_ip_ttl_form();exit;}




if(isset($_GET["reset-connection-js"])){reset_connection_js();exit;}
if(isset($_POST["reset-connection"])){reset_connection();exit;}

if(isset($_GET["remove-template-js"])){remove_template_js();exit;}
if(isset($_POST["remove-template"])){remove_template();exit;}
if(isset($_GET["items"])){items_js();exit;}
if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_GET["AddItem-tab"])){item_tab();exit;}
if(isset($_GET["AddItem-js"])){item_popup_js();exit;}
if(isset($_GET["AddItem-popup"])){item_form();exit;}
if(isset($_GET["AddItem-import"])){item_form_import();exit;}
if(isset($_POST["item-import"])){item_import();exit;}
if(isset($_POST["item-pattern"])){item_save();exit;}
if(isset($_POST["EnableItem"])){item_enable();exit;}
if(isset($_POST["DeleteItem"])){item_delete();exit;}
if(isset($_GET["items-date"])){item_date();exit;}
if(isset($_GET["GroupType-button"])){GroupType_button();exit;}
if(isset($_GET["dynamic-acls-infos"])){Dynamic_acls_infos();exit;}
if(isset($_POST["group-params"])){EditGroup_params_save();exit;}
page(true);

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	$title=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$html="YahooWin('750','$page?table-width=730&table-heigth=450&GroupName-width=360&GroupType-width=169&table-org={$_GET["toexplainorg"]}&ACLType={$_GET["ACLType"]}$wpad','$title')";
	echo $html;
	
}

function reset_connection_js(){
	$ID=$_GET["reset-connection-js"];
	$page=CurrentPageName();
	
	
	$t=time();
	$html="
	var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_content_rule_editsquidgroup');
	if(document.getElementById('ACL_ID_MAIN_TABLE')){ $('#'+document.getElementById('ACL_ID_MAIN_TABLE').value).flexReload(); }
	
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('reset-connection','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	Save$t();";
	echo $html;	
	
}

function remove_template_js(){
	$ID=$_GET["remove-template-js"];
	$page=CurrentPageName();
	
	
	$t=time();
	$html="
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_content_rule_editsquidgroup');
	if(document.getElementById('ACL_ID_MAIN_TABLE')){ $('#'+document.getElementById('ACL_ID_MAIN_TABLE').value).flexReload(); }
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('remove-template','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();";
echo $html;
}
function remove_template(){
	$ID=$_POST["remove-template"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilters_sqgroups SET acltpl='' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}	
}

function reset_connection(){
	$ID=$_POST["reset-connection"];
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){$q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");}
	$sql="SELECT acltpl,tplreset FROM webfilters_sqgroups WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["tplreset"])==0){$tplreset=1;}else{$tplreset=0;}
	$q->QUERY_SQL("UPDATE webfilters_sqgroups SET tplreset='$tplreset' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}


function item_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["item-id"];
	if($ID>0){
		$title="{item}:$ID";
	}
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin5(1027,'$page?AddItem-tab=yes&item-id=$ID&ID={$_GET["ID"]}&table-t={$_GET["table-t"]}&table-org={$_GET["table-org"]}&ACLType={$_GET["ACLType"]}$wpad','$title')";
	echo $html;
}

function AddGroup_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";$ASWPAD=true;}
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if($ID>0){
		$GroupTypeName=null;
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$ID'"));
		$GroupType=$ligne["GroupType"];
		if(preg_match("#ExtLDAP:(.+?):#", $ligne["GroupName"],$re)){$ligne["GroupName"]=$re[1];}
		
		
		if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM quota_objects WHERE ID='{$re[1]}'"));
			$GroupTypeName="&nbsp;&raquo;&nbsp;".$tpl->_ENGINE_parse_body("{time_quota} {$ligne2["objectname"]}");
		}
		
		if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'"));
			$GroupTypeName="&nbsp;&raquo;&nbsp;".$tpl->_ENGINE_parse_body("{session_tracking} - {$re[1]} - {$ligne2["objectname"]}");
		}
		
		
		
		$ligne["GroupName"]=utf8_encode($ligne["GroupName"]);
		$title="{group}:$ID&nbsp;&raquo;&nbsp;{$ligne["GroupName"]}&nbsp;&raquo;&nbsp;{$q->acl_GroupType[$ligne["GroupType"]]}$GroupTypeName";
	}else{
		
		$title="{new_item}";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	
	$link_acl="&link-acl={$_GET["link-acl"]}";
	
	if(isset($_GET["fw-acl"])){
		$link_acl="&fw-acl={$_GET["fw-acl"]}&fw-dir={$_GET["fw-dir"]}";
		
	}
	
	if(isset($_GET["TABLEID"])){$_GET["table-org"]=$_GET["TABLEID"];}
	$html="YahooWinT(595,'$page?EditGroup-popup=yes&ID=$ID$link_acl&table-acls-t={$_GET["table-acls-t"]}&table-org={$_GET["table-org"]}&FilterType={$_GET["FilterType"]}&TransParentMode={$_GET["TransParentMode"]}&ACLType={$_GET["ACLType"]}&callback={$_GET["callback"]}$wpad','$title')";
	echo $html;	
	
}

function EditGroup_content(){
	$ID=$_GET["ID"];
	$sock=new sockets();
	$sock->getFrameWork("squid.php?aclgroup-content=$ID");
	$content=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/container_{$ID}.txt");
	echo "<textarea style='margin-top:5px;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important' id='textToParseCats-$t'>$content</textarea>";
	
}

function EditGroup_popup(){
	$ID=$_GET["ID"];
	$ASWPAD=false;
	$AsTransParent=false;
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";$ASWPAD=true;}
	if($_GET["TransParentMode"]){$wpad="&wpad=yes&TransParentMode=1";$ASWPAD=true;$AsTransParent=true;}
	$acltpl_delete=null;
	if(!is_numeric($ID)){$ID=0;}
	if($ID>0){if(!isset($_GET["tab"])){EditGroup_tabs();return;}}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqgroups WHERE ID='$ID'"));
	
	
	if(intval($ligne["tplreset"])==1){
		$tplreset=" ({enabled})";
	}else{
		$tplreset=" ({disabled})";
	}
	
	$buttonname="{apply}";
	$acltpl_md5=trim($ligne["acltpl"]);
	$acltpl="{default}";
	$sock=new sockets();
	$jstpl="blur();";
	$browse="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.templates.php?choose-acl=$ID');\" 
	style='font-weight:normal;text-decoration:underline;font-size:16px'>";
	if($ID<1){$buttonname="{add}";$browse=null;$acltpl=null;}	
	if($acltpl_md5<>null){
			if($acltpl_md5=="ARTICA_SLASH_SCREEN"){
				$jstpl="javascript:Loadjs('squid.webauth.php');";
				$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" 
				style='font-size:16px;text-decoration:underline'>HotSpot</a>";
				
				
			}else{
			$md5=$acltpl_md5;
			$sql="SELECT template_name,template_link FROM squidtpls WHERE `zmd5`='{$acltpl_md5}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$templatename=$ligne2["template_name"];
			
			$acltpl=addslashes($ligne2["template_name"]);
			$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($acltpl)."');";
			$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:16px;text-decoration:underline'>$templatename</a>";
			if($ligne2["template_link"]==1){
				$acltpl="<span style='font-size:16px;'>$templatename</span>";
			}
			
			$acltpl_delete="<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?remove-template-js=$ID');\" style='font-size:16px;text-decoration:underline'>{remove_template}</span></a></td>
			</tr>";
			
			
		}
	}	
	
	
	$t=time();
	$tt=time();
	$GroupType=$q->acl_GroupType;
	$sql="SELECT ID,objectname  FROM `quota_objects` ORDER BY objectname";
	$results = $q->QUERY_SQL($sql);
	while ($ligne3 = mysql_fetch_assoc($results)) {
		$GroupType["time_quota:{$ligne3["ID"]}"]="{time_quota}:{$ligne3["objectname"]}";
	}
	$sql="SELECT ID,objectname  FROM `sessions_objects` ORDER BY objectname";
	$results = $q->QUERY_SQL($sql);
	while ($ligne3 = mysql_fetch_assoc($results)) {
		$GroupType["time_session:LOGIN:{$ligne3["ID"]}"]="{session_tracking}:LOGIN:{$ligne3["objectname"]}";
		$GroupType["time_session:LOGOUT:{$ligne3["ID"]}"]="{session_tracking}:LOGOUT:{$ligne3["objectname"]}";
		$GroupType["time_session:ACTIVE:{$ligne3["ID"]}"]="{session_tracking}:ACTIVE:{$ligne3["objectname"]}";
	}	
	
	
	if($ASWPAD){$GroupType=$q->acl_GroupType_WPAD;}
	if($AsTransParent){$GroupType=$q->acl_GroupType_iptables;}
	
	$GroupeTypeField=Field_array_Hash($GroupType,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
	
	
	if($GLOBALS["VERBOSE"]){
		echo "FilterType={$_GET["FilterType"]}<br>\n";
	}
	
	if($_GET["FilterType"]<>null){
		switch ($_GET["FilterType"]) {
			case "src":
				$GroupeTypeField="<input type='hidden' name='GroupType-$tt' id='GroupType-$tt' value='src'>
				{$GroupType["src"]}";
				$ScriptAdd="TypeAddButton$tt()";
				break;
			
			case "MAC":
				$GroupeTypeField="<input type='hidden' name='GroupType-$tt' id='GroupType-$tt' value='arp'>
				{$GroupType["arp"]}";
				$ScriptAdd="TypeAddButton$tt()";
				break;
				
			case "FW-IN":
				$GroupType=array();
				$GroupeTypeField=Field_array_Hash($q->acl_GroupType_Firewall_in,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				$ScriptAdd="TypeAddButton$tt()";
				break;
				
			case "FW-OUT":
				$GroupType=array();
				$GroupeTypeField=Field_array_Hash($q->acl_GroupType_Firewall_out,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				$ScriptAdd="TypeAddButton$tt()";
				break;		

			case "FW-PORT":
				$GroupType=array();
				$GroupeTypeField=Field_array_Hash($q->acl_GroupType_Firewall_port,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				$ScriptAdd="TypeAddButton$tt()";
				break;	

				
			case "IPTABLES":
				$GroupType=array();
				$GroupeTypeField=Field_array_Hash($q->acl_GroupType_iptables,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				break;
				
				
			case "uid":
				$GroupType=array();
				$GroupType["ext_user"]=$q->acl_GroupType["ext_user"];
				$GroupType["proxy_auth_ads"]=$q->acl_GroupType["proxy_auth_ads"];
				$GroupType["proxy_auth"]=$q->acl_GroupType["proxy_auth"];
				$GroupeTypeField=Field_array_Hash($GroupType,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				break;
				
			case "ADMBR":
				$GroupType=array();
				$GroupType["proxy_auth_ads"]=$q->acl_GroupType["proxy_auth_ads"];
				$GroupType["proxy_auth"]=$q->acl_GroupType["proxy_auth"];	
				$GroupeTypeField=Field_array_Hash($GroupType,"GroupType-$tt",$ligne["GroupType"],"TypeAddButton$tt()",null,0,"font-size:16px");
				$ScriptAdd="TypeAddButton$tt()";
				break;

			case "EXT_USER":
				$GroupeTypeField="<input type='hidden' name='GroupType-$tt' id='GroupType-$tt' value='ext_user'>
				{$GroupType["ext_user"]}";
				$ScriptAdd="TypeAddButton$tt()";
				break;
				
			case "dstdomain":
				$GroupeTypeField="<input type='hidden' name='GroupType-$tt' id='GroupType-$tt' value='dstdomain'>
				{$GroupType["dstdomain"]}";
				$ScriptAdd="TypeAddButton$tt()";
				break;
			case "srcdomain":
				$GroupeTypeField="<input type='hidden' name='GroupType-$tt' id='GroupType-$tt' value='srcdomain'>
				{$GroupType["srcdomain"]}";
				$ScriptAdd="TypeAddButton$tt()";
				break;	

			case "max_user_ip":
				$ScriptAdd2="LoadAjax('$tt-authenticate_ip_ttl_form','$page?authenticate_ip_ttl_form=yes')";
				break;
				
				
				
									
			default:$GroupeTypeField=null;break;
		}
	}
	
	
	if($ligne["GroupType"]=="max_user_ip"){
		$ScriptAdd2="LoadAjax('$tt-authenticate_ip_ttl_form','$page?authenticate_ip_ttl_form=yes')";
	}
	
	
$template_options="<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><strong style='font-size:16px'><span id='acltplTxt'>$acltpl</span></a></td>
			</tr>
			<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><span style='font-size:16px'>$browse<span id='acltplTxt style='font-size:16px !important'>{change_template}</span></a></td>
			</tr>
			$acltpl_delete";
if(intval($ligne["tplreset"])==1){$template_options=null;}
	
$template_section="
<tr>
		<td class=legend style='font-size:14px' valign='top'>{template}:</td>
		<td>
			<table style='width:99%'>
			
			<tr>
				<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
				<td valign='top'><strong style='font-size:16px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?reset-connection-js=$ID');\" style='font-size:16px;text-decoration:underline'>{reset_connection} $tplreset</a></td>
			</tr>			
			$template_options
		
		</table>
		</td>
	</tr>";
	
if($ligne["GroupType"]=="hotspot_auth"){
	$template_section="
		<tr>
			<td colspan=2 align='right'><a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.webauth.php?YahooWin=6');\"
			style='font-size:16px;text-decoration:underline'>{hotspot_parameters}</a></td>
		</tr>		
	";
	
}	

if($ligne["GroupType"]=="dynamic_acls"){
	$ScriptAdd2="LoadAjax('$tt-infos2','$page?dynamic-acls-params=yes&gpid=$ID&table-acls-t={$_GET["table-acls-t"]}&table-org={$_GET["table-org"]}');";
	
}

if($_GET["FilterType"]=="FW-OUT"){$template_section=null;}
if($_GET["FilterType"]=="FW-IN"){$template_section=null;}


	
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	
	<table style='width:99%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px' nowrap width=99%>{groupname}:</td>
		<td>". Field_text("GroupName-$tt",utf8_encode($ligne["GroupName"]),"font-size:16px;width:340px",null,null,null,false,"SaveAclGroupModeCheck(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{group_type}:</td>
		<td style='font-size:16px;font-weight:bold'>$GroupeTypeField</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><span id='group-add-f-$t'></span></td>
	</tr>	
	$template_section	
	
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "SaveAclGroupMode()",20)."</td>
	</tr>
	</table>
	</div>
	<div id='$tt-infos'></div>
	<div id='$tt-authenticate_ip_ttl_form'></div>
	<div id='$tt-infos2'></div>
	
	
	<script>
	var x_SaveAclGroupMode= function (obj) {
		var res=obj.responseText;
		var ID=$ID;
		var callback='{$_GET["callback"]}';
		
		if(res.length>0){
			if(is_integer(res)){
				if(callback.length>3){
					{$_GET["callback"]}(res);
					if(ID==0){YahooWinTHide();}
					return;
				}
			}
		}
		
		if(res.length>3){alert(res);return;}
		if(ID==0){YahooWinTHide();}
		if(ID==-1){YahooWinTHide();}
		if(document.getElementById('formulaire-choix-groupe-proxy')){RefreshFormulaireChoixGroupeProxy();}
		var tableaclt='{$_GET["table-acls-t"]}';
		var tableorg='{$_GET["table-org"]}';
		if(tableaclt.length>3){ $('#table-items-'+tableaclt).flexReload();}
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}
		ifFnExistsCallIt('RefreshSquidGroupTable');
		RefreshAllAclsTables();
		
	}
	
	function SaveAclGroupModeCheck(e){
		if(checkEnter(e)){SaveAclGroupMode();}
	}
	
	function TypeAddButton$tt(){
		var mGroupName='GroupName-$tt';
		var GroupType=document.getElementById('GroupType-$tt').value;
		document.getElementById('GroupName-$tt').disabled=false;
		
		if(GroupType=='proxy_auth_ads'){
			document.getElementById('GroupName-$tt').disabled=true;
		}
		if(GroupType=='proxy_auth_tagad'){
			document.getElementById('GroupName-$tt').disabled=true;
		}
		
		
		if(GroupType=='proxy_auth_statad'){
			document.getElementById('GroupName-$tt').disabled=true;
		}
		
		if(GroupType=='proxy_auth_ldap'){
			document.getElementById('GroupName-$tt').disabled=true;
		}		
		
		LoadAjaxTiny('group-add-f-$t','$page?GroupType-button=yes&GroupName='+mGroupName+'&t=$t&GroupType='+GroupType+'&tt=$tt');
	}
	
	
	
	function SaveAclGroupMode(){
		      var XHR = new XHRConnection();
		      if(!document.getElementById('GroupName-$tt')){
		      	alert('Group name: GroupName-$tt; no such id');
		      	return;
		      }
		      if(!document.getElementById('GroupType-$tt')){
		      	alert('Group name: GroupType-$tt; no such id');
		      	return;
		      }		      
		      
		      XHR.appendData('callback', '{$_GET["callback"]}');
		      XHR.appendData('GroupName', document.getElementById('GroupName-$tt').value);
		      XHR.appendData('GroupType', document.getElementById('GroupType-$tt').value);
		      XHR.appendData('ACLType', '{$_GET["ACLType"]}');
			  XHR.appendData('ID', '$ID');
		      XHR.appendData('link-acl', '{$_GET["link-acl"]}');
		      XHR.appendData('fw-acl', '{$_GET["fw-acl"]}');
		      XHR.appendData('fw-dir', '{$_GET["fw-dir"]}');
		      XHR.appendData('TransParentMode', '{$_GET["TransParentMode"]}');
		      XHR.sendAndLoad('$page', 'POST',x_SaveAclGroupMode);  		
		}	
		
	function CheckGrouform$t(){
		var id=$ID;
		var GroupType=document.getElementById('GroupType-$tt').value;
		if(GroupType=='proxy_auth_ads'){document.getElementById('GroupName-$tt').disabled=true;}
		if(GroupType=='proxy_auth_tagad'){document.getElementById('GroupName-$tt').disabled=true;}
		if(GroupType=='proxy_auth_statad'){document.getElementById('GroupName-$tt').disabled=true;}
		if(GroupType=='proxy_auth_ldap'){document.getElementById('GroupName-$tt').disabled=true;}
		if(id>0){document.getElementById('GroupType-$tt').disabled=true;return;}
	}
	
$ScriptAdd2;	
CheckGrouform$t();
$ScriptAdd;

LoadAjax('$tt-infos','$page?dynamic-acls-infos=yes&ID=$ID');

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function GroupType_button(){
	$GroupType=$_GET["GroupType"];
	$GroupName=$_GET["GroupName"];
	$tpl=new templates();
	if($GroupType=="proxy_auth_ads"){
		$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
		
		if($EnableArticaMetaServer==1){
			echo "<div style='margin-top:10px'>";
			$js="Loadjs('artica-meta-ad-groups.php?field-user=$GroupName&field-type=3')";
			echo $tpl->_ENGINE_parse_body(button("{add_group}..",$js,"14"));
			echo "</div>";
			return;
		}
		
		
		echo "<div style='margin-top:10px'>";
			$js="Loadjs('browse-ad-groups.php?field-user=$GroupName&field-type=3')";
			echo $tpl->_ENGINE_parse_body(button("{browse_active_directory}..",$js,"14"));
		echo "</div>";
	}
	
	
	if($GroupType=="proxy_auth_tagad"){
		$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	
		if($EnableArticaMetaServer==1){
			echo "<div style='margin-top:10px'>";
			$js="Loadjs('artica-meta-ad-groups.php?field-user=$GroupName&field-type=3')";
			echo $tpl->_ENGINE_parse_body(button("{add_group}..",$js,"14"));
			echo "</div>";
			return;
		}
	
	
		echo "<div style='margin-top:10px'>";
		$js="Loadjs('browse-ad-groups.php?field-user=$GroupName&field-type=3')";
		echo $tpl->_ENGINE_parse_body(button("{browse_active_directory}..",$js,"14"));
		echo "</div>";
	}	
	
	
	
	
	
	
	if($GroupType=="proxy_auth_statad"){
		
		if($EnableArticaMetaServer==1){
			echo "<div style='margin-top:10px'>";
			$js="Loadjs('artica-meta-ad-groups.php?field-user=$GroupName&field-type=3')";
			echo $tpl->_ENGINE_parse_body(button("{add_group}..",$js,"14"));
			echo "</div>";
			return;
		}
		
		
		echo "<div style='margin-top:10px'>";
			$js="Loadjs('browse-ad-groups.php?field-user=$GroupName&field-type=3')";
			echo $tpl->_ENGINE_parse_body(button("{browse_active_directory}..",$js,"14"));
		echo "</div>";
	}

	if($GroupType=="proxy_auth_ldap"){
		echo "<div style='margin-top:10px'>";
			$js="Loadjs('browse-extldap-groups.php?field-user=$GroupName&field-type=4')";
			echo $tpl->_ENGINE_parse_body(button("{browse_remote_ldap_server}..",$js,"14"));
		echo "</div>";
	}

	
	
}


function DeleteAllGroup(){
	
	$sql="SELECT ID FROM webfilters_sqgroups";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$_POST["ID"]=$ligne["ID"];
		EditGroup_delete();
		
	}
	
}


function EditGroup_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynamic WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynlogs WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q->QUERY_SQL("DELETE FROM transparent_networks_groups WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM firewallfilter_sqacllinks WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	

	
}
function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
}

function EditGroup_params_save(){
	$gpid=$_POST["gpid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$tpl=new templates();
	$params=unserialize(base64_decode($ligne["params"]));

	while (list($num,$val)=each($_POST)){
		$params[$num]=$val;
		
	}
	
	$newval=base64_encode(serialize($params));
	$newval=mysql_escape_string2($newval);
	$sql="UPDATE webfilters_sqgroups SET params='$newval' WHERE ID='$gpid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__)."\n\n$sql\n";return;}
}

function EditGroup_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	if(strlen($_POST["GroupName"])<3){
		echo "`{$_POST["GroupName"]} Wrong group name\n";
		return;
	}
	$sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`) 
	VALUES ('{$_POST["GroupName"]}','{$_POST["GroupType"]}','1','','');";
	
	$sql="UPDATE webfilters_sqgroups SET GroupName='{$_POST["GroupName"]}' WHERE ID='$ID'";

	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__)."\n\n$sql\n";return;}
	
	if($ID<1){
		$gpid=$q->last_id;
		
		if($_POST["callback"]<>null){echo $gpid;return;}
		
		if(intval($_POST["link-acl"])>0){
			$aclid=$_POST["link-acl"];
			$md5=md5($aclid.$gpid);
			$sql="INSERT IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
			$q=new mysql_squid_builder();
			
			if($_POST["ACLType"]=="session-time"){
				$q=new mysql();
				$sql="INSERT IGNORE INTO ext_time_quota_acl_link (zmd5,ruleid,groupid,enabled) VALUES('$md5','$aclid','$gpid',1)";
			}
			
			if($_POST["TransParentMode"]==1){
				$sql="INSERT IGNORE INTO transparent_networks_groups (zmd5,ruleid,gpid) VALUES('$md5','$aclid','$gpid')";
				
			}
			
			
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__);}
		}
		
		if(intval($_POST["fw-acl"])>0){
			$aclid=$_POST["fw-acl"];
			$dir=$_POST["fw-dir"];
			$md5=md5($aclid.$gpid);
			
			$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`firewallfilter_sqacllinks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT UNSIGNED ,
			`negation` smallint(1) NOT NULL ,
			`direction` smallint(1) NOT NULL ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`),
			KEY `zOrder`(`zOrder`)
			)  ENGINE = MYISAM;");
			if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__);}
			
			$sql="INSERT IGNORE INTO firewallfilter_sqacllinks (zmd5,aclid,gpid,zOrder,direction) VALUES('$md5','$aclid','$gpid',1,$dir)";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__);}
		}
		
		
	}
	
	

}
function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$MULTIPLE_SQL=array();
	$ECHO=false;
	if(isset($_POST["ECHO"])){$ECHO=true;}
	$_POST["item-pattern"]=url_decode_special_tool($_POST["item-pattern"]);
	
	$q=new mysql_squid_builder();
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$GroupType=$ligne["GroupType"];
	$GroupName=$ligne["GroupName"];
	if($GroupType=="url_regex_extensions"){
		if(preg_match("#\.(.+?)$#", $_POST["item-pattern"],$re)){$_POST["item-pattern"]=$re[1];}
	}
	
	if($GroupType=="dstdomain"){
		if(preg_match("#\/\/#", $_POST["item-pattern"])){
			$URLAR=parse_url($_POST["item-pattern"]);
			if(isset($URLAR["host"])){$_POST["item-pattern"]=$URLAR["host"];}
		}
		if(preg_match("#^www.(.*)#", $_POST["item-pattern"],$re)){$_POST["item-pattern"]=$re[1];}
		if(preg_match("#(.*?)\/#", $_POST["item-pattern"],$re)){$_POST["item-pattern"]=$re[1];}	
	}
	
	if($GroupType=="dst"){
		$ipClass=new IP();
		if(!$ipClass->isIPAddressOrRange($_POST["item-pattern"])){
			
			echo "Not a valid IP {$_POST["item-pattern"]}\n";
			return;
		}
	}
	if($GroupType=="src"){
		$ipClass=new IP();
		if(!$ipClass->isIPAddressOrRange($_POST["item-pattern"])){
				
			echo "Not a valid IP {$_POST["item-pattern"]}\n";
			return;
		}
	}	
	
	if($GroupType=="method"){
		
		$_POST["item-pattern"]=trim(strtoupper( $_POST["item-pattern"]));
		if(strpos($_POST["item-pattern"], " ")>0){
			$INTR=explode(" ",$_POST["item-pattern"]);
		}else{
			$ff="no exploded\n";
			$INTR[]=$_POST["item-pattern"];
		}
		
		
		$ERROR_PROTO=false;
		while (list ($index, $proto) = each ($INTR) ){
			$proto=trim($proto);
			if($proto==null){continue;}
			if(!isset($q->AVAILABLE_METHOD[$proto])){;
				echo "Unknown Method:[$index]/".count($INTR)." `$proto` $ff\n";
				$ERROR_PROTO=true;
				continue;
			}
			
			$MULTIPLE_SQL[]="('$proto','$gpid','1','')";
			
		}
		if($ERROR_PROTO){
			echo "Alowed methods are:\n";
			while (list ($TaskType, $none) = each ($q->AVAILABLE_METHOD) ){echo "\t$TaskType\n";}
		}
	
	
	}
	
	
	
	
	if($GroupType=="arp"){
		$_POST["item-pattern"]=trim(strtoupper( $_POST["item-pattern"]));
		$_POST["item-pattern"]=str_replace("-", ":", $_POST["item-pattern"]);
	}
	
	
	if(count($MULTIPLE_SQL)>0){
		$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ".@implode(",", $MULTIPLE_SQL);
		$q->QUERY_SQL($sqladd);
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	//echo __LINE__.") {$_POST["item-pattern"]}\n";
	 
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqitems WHERE gpid='$gpid' AND pattern='{$_POST["item-pattern"]}'"));
	if(trim($ligne["ID"])>0){return;}
	
	$_POST["item-pattern"]=str_replace("\\", "\\\\", $_POST["item-pattern"]);
	
	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) 
	VALUES ('{$_POST["item-pattern"]}','$gpid','1','');";
	
	$sql="UPDATE webfilters_sqitems SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";	
	if($ID<1){$sql=$sqladd;
		if($ECHO){echo "{$_POST["item-pattern"]} -> $GroupName Type $GroupType OK\n";}
	}
	
//	echo __LINE__.") $sql\n";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
function item_import(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$GroupType=$ligne["GroupType"];
	
	
	$t=array();
	$sqladd="INSERT IGNORE INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ";
	$Patterns=array();
	$f=explode("\n",$_POST["item-import"]);
	$ipClass=new IP();
	while (list ($num, $pattern) = each (	$f)){
		if(trim($pattern)==null){continue;}
		
		if($GroupType=="url_regex_extensions"){
			if(preg_match("#\.(.+?)$#", $pattern,$re)){$pattern=$re[1];}
		}
		
		if($GroupType=="dstdomain"){
			if(preg_match("#\/\/#", $pattern)){
				$URLAR=parse_url($pattern);
				if(isset($URLAR["host"])){$pattern=$URLAR["host"];}
			}
			if(preg_match("#^www.(.*)#",$pattern,$re)){$pattern=$re[1];}
			if(preg_match("#(.*?)\/#", $pattern,$re)){$pattern=$re[1];}
		}

		if($GroupType=="arp"){
			$pattern=trim(strtoupper( $pattern));
			$pattern=str_replace("-", ":", $pattern);
		}
		
		
		if($GroupType=="dst"){if(!$ipClass->isIPAddressOrRange($pattern)){continue;}}
		if($GroupType=="src"){if(!$ipClass->isIPAddressOrRange($pattern)){continue;}}
			
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqitems WHERE gpid='$gpid' AND pattern='$pattern'"));
		if(trim($ligne["ID"])>0){continue;}
		$Patterns[$pattern]=true;
		
		
		
		
	}
	
	if(count($Patterns)>0){
		while (list ($a, $b) = each (	$Patterns)){
			$t[]="('$a','$gpid','1','')";
		}
	}
	
	
	
	if(count($t)>0){
		
		$sql=$sqladd.@implode(",", $t);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n***\n$sql\n****\n";return;}
		
	}
}

function item_date(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tableorg=$_GET["table-org"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT other FROM webfilters_sqitems WHERE gpid='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$t=time();
	$pattern=base64_decode($ligne["other"]);
	$TimeSpace=unserialize(base64_decode($ligne["other"]));
	
	
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	$title="<strong style='font-size:16px'>{days}:</strong>";
	
	while (list ($num, $val) = each ($days) ){
		
		$jsjs[]="if(document.getElementById('$t-day_{$num}').checked){ XHR.appendData('day_{$num}',1);}else{ XHR.appendData('day_{$num}',0);}";
		
		$tt[]="
		<table style='width:1%'>
		<tr>
		<td width=1%>". Field_checkbox("$t-day_{$num}",1,$TimeSpace["day_{$num}"])."</td>
		<td width=99% class=legend style='font-size:16px' align='left'>{{$val}}</td>
		</tr>
		</table>
		";
		
	}	
	
	$dayF=CompileTr3($tt,false,$title);
	
	
	$html="
	<div id='animate-$t'></div>
	$dayF
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' width=50%>
		<center>
		<strong style='font-size:16px'>{from_time}</strong>
		".field_iphone_time("FT-$t",$TimeSpace["H1"])."</center></td>
		<td valign='top' width=50%><center>
		<strong style='font-size:16px'>{to_time}</strong>
		".field_iphone_time("FT2-$t",$TimeSpace["H2"])."</center></td>
	</tr>
	</table>
	<hr>
	<div style='width:100%;text-align:right'>". button("{apply}","SaveFF$t()","18px")."</div>
	
	<script>
	var x_SaveFF$t= function (obj) {
		var tableorg='$tableorg';
		var res=obj.responseText;
		document.getElementById('animate-$t').innerHTML='';
		var ID=$ID;
		if(res.length>3){alert(res);return;}
		RefreshTab('main_content_rule_editsquidgroup');
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}
		ExecuteByClassName('SearchFunction');
	}
	
	function SaveFF$t(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeSave', 'yes');
		      XHR.appendData('ID', '$ID');
		      ". @implode("\n", $jsjs)."
		      XHR.appendData('H1', document.getElementById('FT-$t').value);
		      XHR.appendData('H2', document.getElementById('FT2-$t').value);
			  AnimateDiv('animate-$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveFF$t);  		
		}
		
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function item_date_save(){
	$ID=$_POST["ID"];
	$sql="SELECT other,pattern FROM webfilters_sqitems WHERE gpid='$ID'";
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$H1=$_POST["H1"];
	$H2=$_POST["H2"];
	
	$H1T=strtotime(date("Y-m-d $H1:00"));
	$H2T=strtotime(date("Y-m-d $H2:00"));
	
	if($H2T<$H1T){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{ERROR_SQUID_TIME_ACL}");
		return;
	}
	
	
	$pattern=base64_encode(serialize($_POST));
	if(strlen(trim($ligne["pattern"]))<3){
		$sql="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ('$pattern','$ID','1','$pattern');";
	}else{
		$sql="UPDATE webfilters_sqitems SET pattern='$pattern',other='$pattern' WHERE gpid='$ID'";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}
	
	
	
function EditGroup_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];

	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID"));
	
	$array["items"]='{items}';
	$array["EditGroup-popup"]='{settings}';
	$array["EditGroup-content"]='{content}';
	
	
	if($ligne["GroupType"]=="proxy_auth_ads"){unset($array["items"]);unset($array["EditGroup-content"]); }
	if($ligne["GroupType"]=="proxy_auth_tagad"){unset($array["items"]);unset($array["EditGroup-content"]); }
	if($ligne["GroupType"]=="proxy_auth_statad"){unset($array["items"]); unset($array["EditGroup-content"]);}
	if($ligne["GroupType"]=="proxy_auth_ldap"){unset($array["items"]); unset($array["EditGroup-content"]);}
	if($ligne["GroupType"]=="all"){unset($array["items"]);unset($array["EditGroup-content"]);}
	if($ligne["GroupType"]=="NudityScan"){
		unset($array["items"]);
		unset($array["EditGroup-content"]);
		$array["NudityParams"]="{global_parameters}";
	
	}
	if($ligne["GroupType"]=="hotspot_auth"){unset($array["items"]);unset($array["EditGroup-content"]);}
	
	$AS_CATEGORIES=false;
	if($ligne["GroupType"]=="categories"){$AS_CATEGORIES=true;}
	if($ligne["GroupType"]=="tag_categories"){$AS_CATEGORIES=true;}
	
	
	if($ligne["GroupType"]=="time"){unset($array["items"]);$array["items-date"]='{items}';unset($array["EditGroup-content"]);}
	
	if(!$AS_CATEGORIES){
		if(isset($q->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])){unset($array["items"]);unset($array["EditGroup-content"]);}
	}
	
	if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $ligne["GroupType"],$re)){
		unset($array["items"]);
		unset($array["EditGroup-content"]);
		
	}
	
	if($ligne["GroupType"]=="dynamic_acls"){
		$array["EditGroup-privileges"]='{privileges}';
	}
	
	
	
	

	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="AdUsers"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"squid.acls.groups.ActiveDirectoryUsers.php?ID=$ID\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="EditGroup-privileges"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"squid.acls.groups.privileges.php?gpid=$ID\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="NudityParams"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"squid.nudityscan.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		

		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes&ID=$ID&tab=yes&table-org={$_GET["table-org"]}\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo build_artica_tabs($html, "main_content_rule_editsquidgroup");	
}

function items_ad_auth(){
	$ID=$_GET["ID"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID=$ID"));
	$array=unserialize(base64_decode($ligne["params"]));
	if(!is_numeric($array["AD_LDAP_PORT"])){$array["AD_LDAP_PORT"]=389;}
	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{activedirectory_server}:</td>
	<td>". Field_text("AD_SERVER-$tt",$array["AD_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("AD_LDAP_PORT-$tt",$array["AD_LDAP_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$tt()","18px")."</td>
	</tr>
	</table>
<script>
	var x_Save$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
		document.getElementById('$tt').innerHTML='';
		RefreshTab('main_content_rule_editsquidgroup');
		ExecuteByClassName('SearchFunction');
	}
	
	function Save$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('ID', '$ID');
		XHR.appendData('AD_SERVER', document.getElementById('AD_SERVER-$tt').value);
		XHR.appendData('AD_LDAP_PORT', document.getElementById('AD_LDAP_PORT-$tt').value);
		AnimateDiv('$tt');
		XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
		
}

function items_openldap_auth(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID=$ID"));
	$array=unserialize(base64_decode($ligne["params"]));
	$tt=time();


	if($array["OPENLDAP_FILTER"]==null){$array["OPENLDAP_FILTER"]="(uid=%uid)";}
	if($array["OPENLDAP_PASSWORD_ATTRIBUTE"]==null){$array["OPENLDAP_PASSWORD_ATTRIBUTE"]="userPassword";}
	if(!is_numeric($array["OPENLDAP_PORT"])){$array["OPENLDAP_PORT"]=389;}
	
	$tt=time();
	$html="
	<div id='$tt'></div>
	<div class=explain style='font-size:14px'>{ldap_cleartext_warn}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:12px'>{hostname}:</td>
		<td>". Field_text("OPENLDAP_SERVER-$tt",$array["OPENLDAP_SERVER"],"font-size:12px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:12px'>{ldap_port}:</td>
		<td>". Field_text("OPENLDAP_PORT-$tt",$array["OPENLDAP_PORT"],"font-size:12px;padding:3px;width:30px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:12px'>{ldap_suffix}:</td>
		<td>". Field_text("OPENLDAP_SUFFIX-$tt",$array["OPENLDAP_SUFFIX"],"font-size:12px;padding:3px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:12px'>{bind_dn}:</td>
		<td>". Field_text("OPENLDAP_DN-$tt",$array["OPENLDAP_DN"],"font-size:12px;padding:3px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:12px'>{password}:</td>
		<td>". Field_password("OPENLDAP_PASSWORD-$tt",$array["OPENLDAP_PASSWORD"],"font-size:12px;padding:3px;width:190px")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:12px'>{password_attribute}:</td>
		<td>". Field_text("OPENLDAP_PASSWORD_ATTRIBUTE-$tt",$array["OPENLDAP_PASSWORD_ATTRIBUTE"],"font-size:12px;padding:3px;width:190px")."</td>
	</tr>	
				
	<tr>
		<td class=legend style='font-size:12px'>{ldap_filter}:</td>
		<td>". Field_text("OPENLDAP_FILTER-$tt",$array["OPENLDAP_FILTER"],"font-size:12px;padding:3px;width:220px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'>
				<hr>". button("{apply}","Save$tt()","18px")."</td>
	</tr>
	</table>
						
						
	<script>
		var x_Save$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
		document.getElementById('$tt').innerHTML='';
		RefreshTab('main_content_rule_editsquidgroup');
		ExecuteByClassName('SearchFunction');
	}	
	
	function Save$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('ID', '$ID');
		XHR.appendData('OPENLDAP_SERVER', document.getElementById('OPENLDAP_SERVER-$tt').value);
		XHR.appendData('OPENLDAP_PORT', document.getElementById('OPENLDAP_PORT-$tt').value);
		XHR.appendData('OPENLDAP_SUFFIX', document.getElementById('OPENLDAP_SUFFIX-$tt').value);
		XHR.appendData('OPENLDAP_DN', document.getElementById('OPENLDAP_DN-$tt').value);
		XHR.appendData('OPENLDAP_PASSWORD', encodeURIComponent(document.getElementById('OPENLDAP_PASSWORD-$tt').value));
		XHR.appendData('OPENLDAP_PASSWORD_ATTRIBUTE', encodeURIComponent(document.getElementById('OPENLDAP_PASSWORD_ATTRIBUTE-$tt').value));
		XHR.appendData('OPENLDAP_FILTER', encodeURIComponent(document.getElementById('OPENLDAP_FILTER-$tt').value));
		AnimateDiv('$tt');
		XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
	
		
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	

function items_radius(){
	$ID=$_GET["ID"];
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID=$ID"));
	$array=unserialize(base64_decode($ligne["params"]));
	if(!is_numeric($array["RAD_PORT"])){$array["RAD_PORT"]=1812;}
	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{radius_server}:</td>
	<td>". Field_text("RAD_SERVER-$tt",$array["RAD_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("RAD_PORT-$tt",$array["RAD_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("RAD_PASSWORD-$tt",$array["RAD_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$tt()","18px")."</td>
	</tr>
	</table>
	<script>
		var x_Save$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
			document.getElementById('$tt').innerHTML='';
			RefreshTab('main_content_rule_editsquidgroup');
			ExecuteByClassName('SearchFunction');
		}
	
	function Save$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('ID', '$ID');
		XHR.appendData('RAD_SERVER', document.getElementById('RAD_SERVER-$tt').value);
		XHR.appendData('RAD_PORT', document.getElementById('RAD_PORT-$tt').value);
		XHR.appendData('RAD_PASSWORD', encodeURIComponent(document.getElementById('RAD_PASSWORD-$tt').value));
		AnimateDiv('$tt');
		XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function items_radius_save(){
	if(isset($_POST["RAD_PASSWORD"])){$_POST["RAD_PASSWORD"]=url_decode_special_tool($_POST["RAD_PASSWORD"]);}
	if(isset($_POST["OPENLDAP_PASSWORD_ATTRIBUTE"])){$_POST["OPENLDAP_PASSWORD_ATTRIBUTE"]=url_decode_special_tool($_POST["OPENLDAP_PASSWORD_ATTRIBUTE"]);}
	if(isset($_POST["OPENLDAP_FILTER"])){$_POST["OPENLDAP_FILTER"]=url_decode_special_tool($_POST["OPENLDAP_FILTER"]);}
	if(isset($_POST["OPENLDAP_PASSWORD"])){$_POST["OPENLDAP_PASSWORD"]=url_decode_special_tool($_POST["OPENLDAP_PASSWORD"]);}
	
	
	
	
	$params=base64_encode(serialize($_POST));
	$sql="UPDATE webfilters_sqgroups SET `params`='$params' WHERE ID='{$_POST["ID"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#Unknown column#", $q->mysql_error)){
			$q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `params` LONGTEXT NOT NULL");
			if(!$q->ok){echo $q->mysql_error;return;}
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return;}
		}else{
			echo $q->mysql_error;
		}
	}
}

function EditGroup_events(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$t=time();
	if(!$q->TABLE_EXISTS("webfilter_aclsdynlogs")){$q->CheckTables();}
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%;margin:-1px'></table>
<script>
var DeleteGroupItemTemp=0;
function flexigridStart$t(){
	$('#table-$t').flexigrid({
	url: '$page?EditGroup-events-search=yes&ID=$ID&table-org={$_GET["table-org"]}',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 106, sortable : true, align: 'left'},
	{display: '$member', name : 'who', width : 67, sortable : true, align: 'left'},
	{display: '$events', name : 'events', width : 276, sortable : true, align: 'left'},

	
	],
	
	searchitems : [
	{display: '$member', name : 'who'},
	{display: '$events', name : 'events'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	rpOptions: [10, 15,20, 30, 50,100,200,300,500],
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
}
	
	
setTimeout('flexigridStart$t()',800);
</script>
";
	echo $html;	
	
}

function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$t=time();		
	
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID"));	
	if($ligne["GroupType"]=="radius_auth"){items_radius();return;}
	if($ligne["GroupType"]=="ad_auth"){items_ad_auth();return;}	
	if($ligne["GroupType"]=="ldap_auth"){items_openldap_auth();return;}	
	
	$Additem_js="Loadjs('$page?AddItem-js=yes&item-id=-1&ID=$ID&table-t=$t&table-org={$_GET["table-org"]}$wpad');";
	
	
	if($ligne["GroupType"]=="categories"){
		$Additem_js="Loadjs('squid.acls.categories.php?gpid=$ID&tablet=table-$t')";
		$new_item=$tpl->javascript_parse_text("{choose_categories}");
	}
	
	if($ligne["GroupType"]=="tag_categories"){
		$Additem_js="Loadjs('squid.acls.categories.php?gpid=$ID&tablet=table-$t')";
		$new_item=$tpl->javascript_parse_text("{choose_categories}");
	}
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteGroupItemTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID&table-org={$_GET["table-org"]}$wpad',
	dataType: 'json',
	colModel : [
		{display: '$items', name : 'pattern', width : 386, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : AddItem},
		],	
	searchitems : [
		{display: '$items', name : 'pattern'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 250,
	singleSelect: true
	
	});   
});
function AddItem() {
	$Additem_js
	
}	

function RefreshSquidGroupItemsTable(){
	$('#table-$t').flexReload();
}


	var x_DeleteGroupItem= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowitem'+DeleteGroupItemTemp).remove();
		RefreshAllAclsTables();
		RefreshSquidGroupTable();
		ExecuteByClassName('SearchFunction');
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		RefreshAllAclsTables();
		ExecuteByClassName('SearchFunction');
	}	
	
	function DeleteGroupItem(ID){
		DeleteGroupItemTemp=ID;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteItem', 'yes');
		XHR.appendData('ID', ID);
		XHR.sendAndLoad('$page', 'POST',x_DeleteGroupItem);  		
	}

	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
		RefreshAllAclsTables();
		ExecuteByClassName('SearchFunction');
	}
	
	function EnableDisableItem(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableItem', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('itemid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		

</script>
	
	";
	
	echo $html;
	
}

function page($big=false){
	$ID=$_GET["ID"];
	$Adbig=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$title=$tpl->javascript_parse_text("{proxy_objects}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$remove_all_groups=$tpl->javascript_parse_text("{remove_all_objects}");
	$t=time();		
	$table_width=835;
	$table_height=350;
	$GroupName_width=372;
	$GroupType_width=278;
	$rules_width=36;
	$enabled_width=22;
	$none3_width=36;
	$items_width=37;
	$rules_width=70;
	if(isset($_GET["table-width"])){$table_width=$_GET["table-width"];}
	if(isset($_GET["table-heigth"])){$table_height=$_GET["table-heigth"];}
	if(isset($_GET["GroupName-width"])){$GroupName_width=$_GET["GroupName-width"];}
	if(isset($_GET["GroupType-width"])){$GroupType_width=$_GET["GroupType-width"];}
	if($big){
		
		$Adbig="&as-big=yes";
		$enabled_width=74;
		$none3_width=74;
		$items_width=125;
		$GroupType_width=427;
		$rules_width=74;
		$table_height=540;
		$rules_width=292;
	}
	
	$html="
	<input type='hidden' name='ACL_ID_GROUP_TABLE' id='ACL_ID_GROUP_TABLE' value='table-$t'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?groups-list=yes$Adbig',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$description</span>', name : 'GroupName', width : $GroupName_width, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$type</span>', name : 'GroupType', width : $GroupType_width, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$items</span>', name : 'items', width : $items_width, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>$rules</span>', name : 'rules', width : $rules_width, sortable : false, align: 'left'},
		
		{display: '', name : 'enabled', width : $enabled_width, sortable : true, align: 'center'},
		{display: '', name : 'none3', width : $none3_width, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:22px>$new_group</strong>', bclass: 'add', onpress : AddGroup},
	{name: '<strong style=font-size:22px>$remove_all_groups</strong>', bclass: 'Delz', onpress : RemoveAllAclsGroups},
		],	
	searchitems : [
		{display: '$description', name : 'GroupName'},
		{display: 'Group id', name : 'ID'},
		],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $table_height,
	singleSelect: true
	
	});   
});
function AddGroup() {
	Loadjs('$page?AddGroup-js=yes&ID=-1&table-org={$_GET["table-org"]}');
	
}	

function RefreshSquidGroupTable(){
	$('#table-$t').flexReload();
	var tableorg='{$_GET["table-org"]}';
	if(tableorg.length>3){ $('#'+tableorg).flexReload();}		
}

function RemoveAllAclsGroups(){
	if(!confirm('$remove_all_groups ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAllGroup', 'yes');
	XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
}





	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
		RefreshAllAclsTables();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}
		
		ExecuteByClassName('SearchFunction');		
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		RefreshAllAclsTables();
		
		
	}	
	
	function DeleteSquidAclGroup(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_group_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteGroup', 'yes');
			XHR.appendData('ID', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
		}  		
	}

	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowgroup'+DeleteSquidAclGroupTemp).remove();
		RefreshAllAclsTables();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}	
		
		
	}
	
	function EnableDisableGroup(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableGroup', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('groupid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function item_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	
	$array["AddItem-popup"]='{item}';
	$array["AddItem-import"]='{import}';
	if($_GET["item-id"]>0){
		unset($array["AddItem-import"]);
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID=$ID"));
	if($ligne["GroupType"]=="method"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="dynamic_acls"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="categories"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="myportname"){unset($array["AddItem-import"]);}

	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&item-id={$_GET["item-id"]}&ID={$_GET["ID"]}&table-t={$_GET["table-t"]}&table-org={$_GET["table-org"]}$wpad\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	
	}

	
	echo build_artica_tabs($html, "squid_aclm_item_add");

}

function item_form_import(){
	$ID=$_GET["ID"];
	$item_id=$_GET["item_id"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	$GroupTypeText=$q->acl_GroupType[$GroupType];
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE ID='$item_id'"));
	$buttonname="{add}";
	if($ID<1){$buttonname="{add}";}
	
	
	
	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse="<input type='button' value='{browse}...' 
		OnClick=\"javascript:Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr');\" style='font-size:16px'>";
	}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="dst"){$explain="{acl_squid_dst_explain}";}
	if($GroupType=="url_regex"){$explain="{acl_squid_url_regex_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="urlpath_regex"){$explain="{acl_squid_url_regex_explain}";}
	if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
	
	
	if($GroupType=="proxy_auth"){
		
		if($EnableKerbAuth==1){
			$browse="<input type='button' value='{browse_active_directory}...' 
			OnClick=\"javascript:Loadjs('BrowseActiveDirectory.php?field-user=$t-pattern&OnlyGroups=1&OnlyAD=1&OnlyGUID=1');\" style='font-size:16px'>";
		}
		$explain="{acl_proxy_auth_explain}";
	}
	
	if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
		$GroupType="{time_quota}";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM quota_objects WHERE ID='{$re[1]}'"));
		$GroupTypeText=$ligne2["objectname"];
		$explain="{ext_time_quota_acl_explain}";
	}
	if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
		$GroupType="{session_tracking} - {$re[1]} -";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'"));
		$GroupTypeText=$ligne2["objectname"];
		$explain="{ext_session_acl_explain}";
	}	
	
	
	
	
	$html="
	<div style='font-size:22px'>$GroupTypeText</div>
	<div class=explain style='font-size:16px'>$explain</div>
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:22px;text-align:left' nowrap width=99%>{pattern}:</td>
	</tr>
	<tr>
		<td><textarea style='margin-top:5px;
			font-family:Courier New;font-weight:bold;width:98%;height:150px;
			border:5px solid #8E8E8E;overflow:auto;font-size:18px !important' 
		id='textToParseCats-$t'></textarea>
	</td>
	</tr>
	<tr>
	<td style='text-align:right;height:30px'><hr>". button($buttonname, "SaveItemsMode$t()",26)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveItemsMode$t= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		document.getElementById('textToParseCats-$t').value='';
		RefreshSquidGroupTable();
		if(!document.getElementById('table-{$_GET["table-t"]}')){
			if(document.getElementById('main_content_rule_editsquidgroup')){
				RefreshTab('main_content_rule_editsquidgroup');
			}
		}else{
			$('#table-{$_GET["table-t"]}').flexReload();
		}
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}	
		RefreshAllAclsTables();
			
		
	}
	
	function SaveItemsModeCheck(e){
		if(!checkEnter(e)){return;}
		SaveItemsMode$t();
	}
	
	function SaveItemsMode$t(){
		      var XHR = new XHRConnection();
		      XHR.appendData('item-import', document.getElementById('textToParseCats-$t').value);
		      XHR.appendData('item-id', '$item_id');
		      XHR.appendData('ID', '$ID');		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}


function item_form(){
	$ID=$_GET["ID"];
	$item_id=$_GET["item_id"];
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	$GroupTypeText=$q->acl_GroupType[$GroupType];
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}		
	$label_form="{pattern}";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE ID='$item_id'"));
	$buttonname="{add}";$jsadd=null;
	if($ID<1){$buttonname="{add}";}
	$LOCK=0;
	$BLOCK_FIELD=0;
	
	
	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse=button("{browse}...","Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr')");
	}
	if($GroupType=="dst"){$explain="{acl_dst_text}";}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
	if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
	if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
	if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
	if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
	if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
	if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
	if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
	if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
	if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}

	
	
	$FIELD_SIZE=450;
	
	if($GroupType=="proxy_auth_multiad"){
		$js="Loadjs('browse-ad-groups.php?field-user=$t-pattern&field-type=3')";
		$explain="{multiple_active_directory_groups_text}";
		$browse=button("{browse_active_directory}",$js,16);
		$LOCK=1;
		$FIELD_SIZE=210;
	
	}
	
	
	$MAIN_BUTTON=button($buttonname, "SaveItemsMode$t()",26);
	
	if($GroupType=="tag_categories"){
		$explain="{categories_tag_acls_explain}";
		$browse=button("{choose_categories}..","Loadjs('squid.acls.categories.php?gpid=$ID')",16);
		$MAIN_BUTTON=null;
		$LOCK=1;
	}
	if($GroupType=="categories"){
		$explain="{categories_acls_explain}";
		$browse=button("{choose_categories}..","Loadjs('squid.acls.categories.php?gpid=$ID')",16);
		$MAIN_BUTTON=null;
		$LOCK=1;
	}
	
	if($GroupType=="browser"){
		$explain="{acl_squid_browser_explain}";
		$browse=button("{list}..","Loadjs('squid.browsers.php?ShowOnly=1')",16);
	}
	
	if($GroupType=="arp"){
		$browse=button("{list}..","Loadjs('squid.computer-browse.php?callback=FillFieldMAC$t')",16);
	}
	
	if($GroupType=="method"){$explain="{acl_squid_method_explain}";}
	
	if($GroupType=="proxy_auth"){
		if($EnableKerbAuth==1){
			$LOCK=1;
			$browse=button("{browse_active_directory}...",
					"Loadjs('BrowseActiveDirectory.php?field-user=$t-pattern&OnlyGroups=0&OnlyUsers=1&OnlyAD=1&OnlyGUID=1');",16);
		}
		$explain="{acl_proxy_auth_explain}";
		
	}
	
	if($GroupType=="dynamic_acls"){
		$explain="{acl_squid_ext_dynamic_acls_explain}";
		$label_form="{group}";
		$jsadd="LoadAjax('$t-to-add','$page?acl-dynamic-virtual-form=yes&ID=$ID&item-id=$item_id&animate=$t$wpad')";
		if($EnableKerbAuth==1){
			$LOCK=1;
			$browse=button("{browse}..",
					"Loadjs('BrowseActiveDirectory.php?field-user=$t-pattern&OnlyGroups=1&OnlyAD=1&OnlyGUID=1');",16);
		}
	}
	
	if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
		$GroupType="{time_quota}";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM quota_objects WHERE ID='{$re[1]}'"));
		$GroupTypeText=$ligne2["objectname"];
		$explain="{ext_time_quota_acl_explain}";
	}	
	if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
		$GroupType="{session_tracking} - {$re[1]} -";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'"));
		$GroupTypeText=$ligne2["objectname"];
		$explain="{ext_session_acl_explain}";
	}	
	
	$MAIN_FIELD=Field_text("$t-pattern",utf8_encode($ligne["pattern"]),"font-size:30px;width:{$FIELD_SIZE}px",null,null,null,false,"SaveItemsModeCheck(event)");
	
	if($GroupType=="myportname"){
		$sql="SELECT * FROM proxy_ports WHERE enabled=1";
		$results_myportname=$q->QUERY_SQL($sql);
		while ($ligne_myportname = mysql_fetch_assoc($results_myportname)) {
			$eth=$ligne_myportname["nic"];
			$ssl=null;
			$PortName=$ligne_myportname["PortName"];
			$port=$ligne_myportname["port"];
			
			if($ligne_myportname["FTP"]==1){continue;}
			if($ligne_myportname["ICP"]==1){continue;}
			if($ligne_myportname["WANPROXY"]==1){continue;}
			if($ligne_myportname["UseSSL"]==1){$ssl=" SSL";}
			$PortName=$PortName."{$ssl} ($port)";
			if($eth<>null){
				$nic=new system_nic($eth);
				$test_ip=$nic->IPADDR;
				$PortName="$PortName{$ssl} ($nic->IPADDR:$port)";
			}
			
			$myportnameZ["MyPortNameID{$ligne_myportname["ID"]}"]=$PortName;
		}
		
		
		$MAIN_FIELD=Field_array_Hash($myportnameZ, "$t-pattern",$ligne["pattern"],null,null,
				0,"font-size:18px;");
		
	}
	
	
	

	$html="
	<div style='font-size:22px;margin-bottom:15px'>$GroupType:$GroupTypeText</div>
	<div class=explain style='font-size:18px'>$explain</div>
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:30px' nowrap width=99% nowrap>$label_form:</td>
		<td>$MAIN_FIELD</td>
		<td width=1%>$browse</td>
	</tr>
	
	<tr>
	<td colspan=3 style='text-align:right;height:30px'><hr>$MAIN_BUTTON</td>
	</tr>
	<tr><td colspan=3 style='text-align:right;height:30px'>&nbsp;</td></tr>
	</table>
	</div>
	<div id='$t-to-add'></div>
	<script>
	var x_SaveItemsMode$t= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		document.getElementById('$t-pattern').value='';
		$('#table-{$_GET["table-t"]}').flexReload();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}	
		ifFnExistsCallIt('RefreshSquidGroupTable');	
		RefreshAllAclsTables();
		
	}
	
	function FillFieldMAC$t(realuid,mac,ip){
		document.getElementById('$t-pattern').value=mac;
		YahooWin6Hide();
	}
	
	function SaveItemsModeCheck(e){
		if(checkEnter(e)){SaveItemsMode$t();}
	}
	
	function SaveItemsMode$t(){
		var XHR = new XHRConnection();
		XHR.appendData('item-pattern', encodeURIComponent(document.getElementById('$t-pattern').value));
		XHR.appendData('item-id', '$item_id');
		XHR.appendData('ID', '$ID');		      
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);  		
	}
	
	
		
	$jsadd
	
	function CheckLock$t(){
		var LOCK=$LOCK;
		if(LOCK==1){
			document.getElementById('$t-pattern').disabled=true;
		}
	}
	CheckLock$t();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_acldyn_member(){
	$t=time();
	$animate=$_GET["animate"];
	$ID=$_GET["ID"];
	$item_id=$_GET["item-id"];
	$page=CurrentPageName();
	$tpl=new templates();
	$MyT=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{virtual_user}:</td>
		<td>". Field_text("$t-user",null,"font-size:14px;width:200px",null,null,null,false,"SaveItemsModeCheck$t(event)")."</td>	
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{password}:</td>
		<td>". Field_password("$t-password",null,"font-size:14px;width:150px",null,null,null,false,"SaveItemsModeCheck$t(event)")."</td>	
	</tr>	
	<tr>
	<tr>
	<td colspan=2 align='right'>". button("{add}", "SaveItemsMode$MyT()",16)."</td>
	</tr>	
	</table>
	</div>	
<script>

	function SaveItemsModeCheck$t(e){
		if(checkEnter(e)){SaveItemsMode();}
	}
	
	function SaveItemsMode$MyT(){
		var XHR = new XHRConnection();
		var password=document.getElementById('$t-password').value;
		var uid=document.getElementById('$t-user').value;
		if(password.length==0){return;}
		if(uid.length==0){return;}
		password=MD5(password);
		XHR.appendData('item-pattern', uid+':'+password);
		XHR.appendData('item-id', '$item_id');
		XHR.appendData('ID', '$ID');		      
		AnimateDiv('$animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$animate);  		
	}			
</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function item_acldyn_params(){
	$ID=$_GET["gpid"];
	if(!is_numeric($ID)){return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$ID'"));	
	$tpl=new templates();
	$params=unserialize(base64_decode($ligne["params"]));
	$t=time();
	$durations[0]="{unlimited}";
	$durations[5]="05 {minutes}";
	$durations[10]="10 {minutes}";
	$durations[15]="15 {minutes}";
	$durations[30]="30 {minutes}";
	$durations[60]="1 {hour}";
	$durations[120]="2 {hours}";
	$durations[240]="4 {hours}";
	$durations[480]="8 {hours}";
	$durations[720]="12 {hours}";
	$durations[960]="16 {hours}";
	$durations[1440]="1 {day}";
	$durations[2880]="2 {days}";
	$durations[5760]="4 {days}";
	$durations[10080]="1 {week}";
	$durations[20160]="2 {weeks}";
	$durations[43200]="1 {month}";
	
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
		<table style='width:99%'>
			<tr>
				<td class=legend style='font-size:14px'>{time_duration}:</td>
				<td>". Field_array_Hash($durations,"duration-$t",$params["duration"],null,null,0,"font-size:14px")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{allow_members}:</td>
				<td>". Field_checkbox("allow_duration-$t",1,$params["allow_duration"])."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><hr>".button("{apply}", "Save$t()",16)."</td>
			</tr>
		</table>
	</div>
	<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(res.length>3){alert(res);return;}
		
		$('#table-{$_GET["table-t"]}').flexReload();
		var tableorg='{$_GET["table-org"]}';
		if(tableorg.length>3){ $('#'+tableorg).flexReload();}	
		ifFnExistsCallIt('RefreshSquidGroupTable');
		RefreshAllAclsTables();	
		ExecuteByClassName('SearchFunction');	
		
	}
	
	function Save$t(){
		var allow_duration=0;
		var XHR = new XHRConnection();
		XHR.appendData('group-params', 'yes');
		XHR.appendData('duration', document.getElementById('duration-$t').value);
		if(document.getElementById('allow_duration-$t').checked){allow_duration=1;}
		XHR.appendData('allow_duration',allow_duration);
		XHR.appendData('gpid', '$ID');		      
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xSave$t);  		
	}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EditGroup_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqgroups SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	
}
function item_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqitems SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}

function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	$FORCE_FILTER=null;
	$search='%';
	$table="webfilters_sqgroups";
	
	$page=1;

	if($q->COUNT_ROWS($table)==0){
			json_error_show("No data");
	}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);} 
	if(mysql_num_rows($results)==0){json_error_show("no data");}	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$GroupType=$q->acl_GroupType;
	$GroupType["src"]="{addr}";
	$GroupType["arp"]="{ComputerMacAddress}";
	$GroupType["dstdomain"]="{dstdomain}";
	$GroupType["proxy_auth"]="{members}";
	$GroupType["port"]="{remote_ports}";
	$GroupType["maxconn"]="{max_connections}";
	$font_size="15px";
	$delet_img="delete-24.png";
	if(isset($_GET["as-big"])){
		$font_size="24px;";
		$delet_img="delete-42.png";
	}
	$rules_text=$tpl->javascript_parse_text("{rules}");	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$ligne2['tcount']=0;
		$disable=Field_checkbox("groupid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableGroup('{$ligne['ID']}')");
		$ligne['GroupName']=utf8_encode($ligne['GroupName']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($GroupType[$ligne["GroupType"]]);
		$delete=imgsimple($delet_img,null,"DeleteSquidAclGroup('{$ligne['ID']}')");
		
		if($ligne["GroupType"]=="proxy_auth_ads"){
			$p=new external_ad_search();
			$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);
		}
		if($ligne["GroupType"]=="proxy_auth_statad"){
			$p=new external_ad_search();
			$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);
		}
		if($ligne["GroupType"]=="proxy_auth_tagad"){
			$p=new external_ad_search();
			$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);
		}		
		
		
		if($ligne["GroupType"]=="proxy_auth_ldap"){
			$p=new ldap_extern();
			preg_match("#^ExtLDAP:(.+?):(.+)#", $ligne['GroupName'],$re);
			$ligne['GroupName']=$re[1];
			$DN=base64_decode($re[2]);
			$ligne2['tcount']=$p->CountDeUsersByGroupDN($DN);
		}		
		
		
		
		
		if($ligne2['tcount']==0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}'"));
		}
		if($ligne["GroupType"]=="all"){
			$ligne2['tcount']="*";
		}
		
		$CountOfRules=0;
		$results3=$q->QUERY_SQL("SELECT webfilters_sqacls.aclname,webfilters_sqacls.ID as aclid FROM  webfilters_sqacllinks,webfilters_sqacls WHERE webfilters_sqacllinks.gpid='{$ligne['ID']}' AND webfilters_sqacllinks.aclid=webfilters_sqacls.ID");
		$CountOfRules=mysql_num_rows($results3);
		
		
		$results4=$q->QUERY_SQL("SELECT aclid FROM firewallfilter_sqacllinks WHERE gpid='{$ligne['ID']}'");
		$CountOfRules2=mysql_num_rows($results4);	

		$CountOfRules=$CountOfRules+$CountOfRules2;
		
		$aclnames=array();
		if($CountOfRules>0){
		$aclnames[]="<strong style='font-size:16px'>$CountOfRules $rules_text:</strong><ul>";
		}
		
		while ($ligne2 = mysql_fetch_assoc($results3)) {
			$aclname=utf8_encode($ligne2["aclname"]);
			$aclnames[]="<li><a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('squid.acls-rules.php?Addacl-js=yes&ID={$ligne2["aclid"]}')\"
					style='text-decoration:underline;font-size:14px'>Proxy:$aclname</a></li>";
			
		}
		
		$qNorm=new mysql();
		while ($ligne2 = mysql_fetch_assoc($results4)) {
			$ligne4=mysql_fetch_array($qNorm->QUERY_SQL("SELECT eth,rulename FROM iptables_main WHERE ID='{$ligne2["aclid"]}'","artica_backup"));
			$eth=$ligne4["eth"];
			$aclname=utf8_encode($ligne4["rulename"]);
			$jsfwrule="Loadjs('firehol.nic.rules.php?ruleid={$ligne2["aclid"]}&eth=$eth')";
			$aclnames[]="<li><a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfwrule\"
			style='text-decoration:underline;font-size:14px'>Fw:$aclname</a></li>";
				
		}
		
		
		if($CountOfRules>0){
			$aclnames[]="</ul>";
			$CountOfRules="<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('squid.acls.groups.rules.php?ID={$ligne['ID']}');\" style='font-size:{$font_size};text-decoration:underline'>$CountOfRules</a>";
			
		}
		
		
		
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?AddGroup-js=yes&ID={$ligne['ID']}');\" 
		style='font-size:{$font_size};text-decoration:underline'>{$ligne['GroupName']}</span>",
		"<span style='font-size:{$font_size};'>$GroupTypeText</span>",
		
		"<center style='font-size:{$font_size};'>{$ligne2['tcount']}</center>",
		"<span style='font-size:{$font_size};'>".@implode("",$aclnames)."</center>",
		
		
	
		"<center>$disable</center>",
		"<center>$delete</center>")
		);
	}
	
	
	echo json_encode($data);	
}

function EditGroup_events_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	
	$search='%';
	$table="webfilter_aclsdynlogs";
	$page=1;
	if(!$q->TABLE_EXISTS("webfilter_aclsdynlogs")){json_error_show("No such table...",2);}
	if($q->COUNT_ROWS($table)==0){json_error_show("No data...",2);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE gpid=$ID $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",2);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",2);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
				$ligne['events']=$tpl->_ENGINE_parse_body($ligne['events']);
				$data['rows'][] = array(
				'id' => "item{$ligne['ID']}",
				'cell' => array(
				"<span style='font-size:12px;'>{$ligne['zDate']}</span>",
				"<span style='font-size:12px;'>{$ligne['who']}</span>",
				"<span style='font-size:12px;'>{$ligne['events']}</span>",
				)
				);
	}
	
	
				echo json_encode($data);
	}

function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$FORCE_FILTER=null;
	$search='%';
	$table="webfilters_sqitems";
	$page=1;

	if($q->COUNT_ROWS($table)==0){
		json_error_show("No data");
	}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE gpid=$ID $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("itemid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableItem('{$ligne['ID']}')");
		$macname=$q->MAC_TO_NAME($ligne['pattern']);
		$ligne['pattern']=utf8_encode($ligne['pattern']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['pattern']}","DeleteGroupItem('{$ligne['ID']}')");
		$additional_text=null;
		
		if($macname){
			$additional_text="<div style='font-size:10px'>$macname</div>";
		}
		
		
		if(preg_match("#MyPortNameID([0-9]+)#", $ligne["pattern"],$re)){
			$ligne_myportname=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID={$re[1]}"));
			$ssl=null;
			$PortName=$ligne_myportname["PortName"];
			$port=$ligne_myportname["port"];
			$eth=$ligne_myportname["nic"];
			if($ligne_myportname["FTP"]==1){continue;}
			if($ligne_myportname["ICP"]==1){continue;}
			if($ligne_myportname["WANPROXY"]==1){continue;}
			if($ligne_myportname["UseSSL"]==1){$ssl=" SSL";}
			$PortName=$PortName."{$ssl} ($port)";
			if($eth<>null){
				$nic=new system_nic($eth);
				$test_ip=$nic->IPADDR;
				$PortName="$PortName{$ssl} ($nic->IPADDR:$port)";
			}
			$ligne['pattern']=$tpl->javascript_parse_text($PortName);
		}
		
		
		if(preg_match("#AD:(.*?):(.+)#", $ligne["pattern"],$re)){
			$dnEnc=$re[2];
			$LDAPID=$re[1];
			$ad=new ActiveDirectory($LDAPID);
			$tty=$ad->ObjectProperty(base64_decode($dnEnc));
			$entries=$ad->search_users_from_group(base64_decode($dnEnc),0);
			$ligne['pattern']="Active Directory:&nbsp;".$tty["cn"]." - ".count($entries)." items";
		}		
			
		
		
		
	$data['rows'][] = array(
		'id' => "item{$ligne['ID']}",
		'cell' => array("<span style='font-size:13px;font-weight:bold'>{$ligne['pattern']}</span>$additional_text",
		"<div style='padding-top:5px'>$disable</div>",
		$delete)
		);
	}
	
	
	echo json_encode($data);	
}
function Dynamic_acls_infos(){
	$ID=$_GET["ID"];if($ID>0){return;}
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return ;}
	if($UseDynamicGroupsAcls==1){return ;}
	$alcs_parameters=$tpl->_ENGINE_parse_body("{acls_parameters}");

	
	$html="<div class=explain style='font-size:14px'>{sugesst_dynamic_acl_groups}</div>
	<div style='text-align:right'><a href=\"javascript:blur();\" OnClick=\"javascript:YahooWin(650,'squid.acls.options.php?popup=yes&t=$t','$alcs_parameters');\"
	style='font-size:16px;font-weight:bold;text-decoration:underline'>&laquo;$alcs_parameters&raquo;</a></div>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function authenticate_ip_ttl_form(){
	$t=time();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$authenticate_ip_ttl=intval($sock->GET_INFO("authenticate_ip_ttl"));
	if($authenticate_ip_ttl==0){$authenticate_ip_ttl=60;}
	$html="<div class=explain style='font-size:14px'>{authenticate_ip_ttl}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	". Field_text_table("authenticate_ip_ttl-$t","{ttl} ({seconds})",$authenticate_ip_ttl,"16",null,110).
	Field_button_table_autonome("{apply}","Save$t",18)."</table>
	</div>
	<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
		
		
	}
	
	function Save$t(){
		var allow_duration=0;
		var XHR = new XHRConnection();
		XHR.appendData('authenticate_ip_ttl', document.getElementById('authenticate_ip_ttl-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);  		
	}
</script>			
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function authenticate_ip_ttl_save(){
	$sock=new sockets();
	$sock->SET_INFO("authenticate_ip_ttl", $_POST["authenticate_ip_ttl"]);
	
}
