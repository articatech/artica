<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	header("content-type: application/javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["DYN_TTL"])){save();exit;}
if(isset($_POST["SquidReloadInpublic"])){SquidReloadInpublic();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->_ENGINE_parse_body("{options}");
	echo "YahooWin(750,'$page?popup=yes&t={$_GET["t"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$sock=new sockets();
	$ACLS_OPTIONS=unserialize(base64_decode($sock->GET_INFO("AclsOptions")));
	if(!is_numeric($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
	if(!is_numeric($ACLS_OPTIONS["CHILDREN_STARTUP"])){$ACLS_OPTIONS["CHILDREN_STARTUP"]=5;}
	if(!is_numeric($ACLS_OPTIONS["CHILDREN_IDLE"])){$ACLS_OPTIONS["CHILDREN_IDLE"]=5;}
	if(!is_numeric($ACLS_OPTIONS["DYN_LOG_LEVEL"])){$ACLS_OPTIONS["DYN_LOG_LEVEL"]=0;}

	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}	
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	$SquidReloadInpublic=intval($sock->GET_INFO("SquidReloadInpublic"));
	$SquidReloadInpublicAlias=$sock->GET_INFO("SquidReloadInpublicAlias");
	if($SquidReloadInpublicAlias==null){$SquidReloadInpublicAlias="reload-proxy-now";}
	
	for($i=0;$i<6;$i++){$DYN_LOG_LEVEL[$i]=$i;	}
	
	$export=Paragraphe("64-export.png", "{export_rules}", "{export_acl_rules_explain}",
			"javascript:Loadjs('squid.acls.export.php?t=$t')");
	
	$import=Paragraphe("64-import.png", "{import_rules}", "{import_acl_rules_explain}",
			"javascript:Loadjs('squid.acls.import.php?t=$t')");	
	
	$delete=Paragraphe("delete-64.png", "{delete_all_acls}", "{delete_all_acls_explain}",
			"javascript:Loadjs('squid.acls.delete.php?t=$t')");
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td valign='top'>
			<table style='width:100%'>
				<tr>
					<td style='width:32px'><img src='img/32-export.png'></td>
					<td style='font-size:18px'>". texttooltip("{export_rules}","{export_acl_rules_explain}","Loadjs('squid.acls.export.php?t=$t')")."</td>
				</tr>
				<tr>
					<td style='width:32px'><img src='img/32-import.png'></td>
					<td style='font-size:18px'>". texttooltip("{import_rules}","{import_acl_rules_explain}","Loadjs('squid.acls.import.php?t=$t')")."</td>
				</tr>	
				<tr>
					<td style='width:32px'><img src='img/delete-32.png'></td>
					<td style='font-size:18px'>". texttooltip("{delete_all_acls}","{delete_all_acls_explain}","Loadjs('squid.acls.delete.php?t=$t')")."</td>
				</tr>	
				</table>
	</td>
	<td valign='top'>
			<table style='width:100%'>
				<tr>
					<td style='width:32px'><img src='img/32-import.png'></td>
					<td style='font-size:18px'>". texttooltip("{artica_categories_gpid}","{artica_categories_bulk_import}","Loadjs('squid.acls.artica-catz.php?t=$t')")."</td>
				</tr>
			</table>
	</td>
	</tr>
	</table>
	</div>
	<div id='serverkerb-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td colspan=2 style='font-size:22px'>{dynamic_acls_newbee}:<hr style='border-top:1px solid;margin:5px 0'></td>
		</tr>
		<tr>
			<td class=legend style='font-size:20px'>{TTL_CACHE}:</td>
			<td style='font-size:20px'>". Field_text("DYN_TTL",$ACLS_OPTIONS["DYN_TTL"],"font-size:20px;padding:3px;width:110px")."&nbsp;{seconds}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:20px'>{CHILDREN_STARTUP}:</td>
			<td style='font-size:20px'>". Field_text("CHILDREN_STARTUP",$ACLS_OPTIONS["CHILDREN_STARTUP"],"font-size:20px;padding:3px;width:110px")."&nbsp;{processes}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:20px'>{CHILDREN_IDLE}:</td>
			<td style='font-size:20px'>". Field_text("CHILDREN_IDLE",$ACLS_OPTIONS["CHILDREN_IDLE"],"font-size:20px;padding:3px;width:110px")."&nbsp;{processes}</td>
		</tr>									
		<tr>
			<td class=legend style='font-size:20px'>{log_level}:</td>
			<td style='font-size:20px'>". Field_array_Hash($DYN_LOG_LEVEL, "DYN_LOG_LEVEL",$ACLS_OPTIONS["DYN_LOG_LEVEL"],null,null,0,"font-size:20px")."</td>
		</tr>	
		<tr><td colspan=2 align='right'>&nbsp;</td>		
		<tr>
			<td class=legend style='font-size:20px'>{use_dynamic_groups_acls}:</td>
			<td>". Field_checkbox_design("UseDynamicGroupsAcls-$t",1,$UseDynamicGroupsAcls,"UseDynamicGroupsAclsCheck$t()")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td>
		</tr>	
		<tr>
			<td colspan=2 align='right'><hr></td>
		</tr>	
		<tr>
			<td colspan=2 style='font-size:22px'>{reloading_service}:<hr style='border-top:1px solid;margin:5px 0'></td>
		</tr>				
		<tr>
			<td class=legend style='font-size:20px'>{reload_in_public_mode}:</td>
			<td>". Field_checkbox_design("SquidReloadInpublic-$t",1,$SquidReloadInpublic)."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:20px'>{request_on_artica_www}:</td>
			<td>". Field_text("SquidReloadInpublicAlias-$t",$SquidReloadInpublicAlias,"font-size:20px;padding:3px;width:210px")."</td>
		</tr>				
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save2$t()",26)."</td>
		</tr>
	
		
		
		
		
	</table>
	</div>
		<script>
	var x_Save2$t= function (obj) {
		var results=obj.responseText;
		LoadAjaxRound('main-proxy-acls-rules','squid.acls-rules.php');
	}
	
	var x_Save$t= function (obj) {
		var results=obj.responseText;
		LoadAjaxRound('main-proxy-acls-rules','squid.acls-rules.php');
	}
		
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		var UseDynamicGroupsAcls=0;
		var SquidDebugAcls=0;
		alert('".__LINE__."');
		if(document.getElementById('UseDynamicGroupsAcls-$t').checked){UseDynamicGroupsAcls=1;}
		//if(document.getElementById('SquidDebugAcls-$t').checked){SquidDebugAcls=1;}
		XHR.appendData('UseDynamicGroupsAcls',UseDynamicGroupsAcls);
		XHR.appendData('SquidDebugAcls',SquidDebugAcls);
		XHR.appendData('DYN_TTL',document.getElementById('DYN_TTL').value);
		XHR.appendData('CHILDREN_STARTUP',document.getElementById('CHILDREN_STARTUP').value);
		XHR.appendData('CHILDREN_IDLE',document.getElementById('CHILDREN_IDLE').value);
		XHR.appendData('DYN_LOG_LEVEL',document.getElementById('DYN_LOG_LEVEL').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}
	
	
	function Save2$t(){
		SquidReloadInpublic=0;
		var XHR = new XHRConnection();
		if(document.getElementById('SquidReloadInpublic-$t').checked){SquidReloadInpublic=1;}
		XHR.appendData('SquidReloadInpublic',SquidReloadInpublic);
		XHR.appendData('SquidReloadInpublicAlias',document.getElementById('SquidReloadInpublicAlias-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_Save2$t);
	
	}
	
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function SquidReloadInpublic(){
	$sock=new sockets();
	$sock->SET_INFO("SquidReloadInpublic", $_POST["SquidReloadInpublic"]);
	$_POST["SquidReloadInpublicAlias"]=str_replace(" ", "-", $_POST["SquidReloadInpublicAlias"]);
	$_POST["SquidReloadInpublicAlias"]=str_replace("$", "-", $_POST["SquidReloadInpublicAlias"]);
	$_POST["SquidReloadInpublicAlias"]=str_replace("%", "-", $_POST["SquidReloadInpublicAlias"]);
	$sock->SET_INFO("SquidReloadInpublicAlias", $_POST["SquidReloadInpublicAlias"]);
	$sock->getFrameWork("services.php?restart-webconsole-scheduled=yes");
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	
	$sock->SET_INFO("SquidDebugAcls", $_POST["SquidDebugAcls"]);
	
	
	unset($_POST["SquidDebugAcls"]);
	unset($_POST["UseDynamicGroupsAcls"]);
	
	
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "AclsOptions");
}
