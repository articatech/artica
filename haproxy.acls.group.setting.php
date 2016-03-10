<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.haproxy.inc');

	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["gpid"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}


function new_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{object}:{new_object}");
	$html="YahooWinBrowse('600','$page?popup=yes&linkacl={$_GET["linkacl"]}','$title')";
	echo $html;
}
function group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_acls_groups WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$title=$tpl->_ENGINE_parse_body("{object}:{$ligne["groupname"]}");
	$html="YahooWinBrowse('600','$page?tabs=yes&linkacl={$_GET["linkacl"]}&gpid={$_GET["ID"]}','$title')";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_acls_groups WHERE ID='{$_GET["gpid"]}'","artica_backup"));
	$title=$tpl->javascript_parse_text($ligne["groupname"]);
	if($title==null){$title=$tpl->javascript_parse_text("{group} $ID");}
	$array["popup"]=$title;
	$array["items"]="{items}";
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="popup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?popup=yes&gpid={$_GET["gpid"]}\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="items"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.acls.items.php?gpid={$_GET["gpid"]}\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		
	}
	echo build_artica_tabs($html, "haproxy_groups_{$_GET["gpid"]}");	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$haproxy=new haproxy();
	$gpid=intval($_GET["gpid"]);
	$ligne["enabled"]=1;
	$linkAcl=intval($_GET["linkacl"]);
	$btname="{add}";
	$t=time();
	$title="{new_object}";
	
	if($gpid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_groups WHERE ID='$gpid'","artica_backup"));
		$title=$tpl->_ENGINE_parse_body("{object}: {$ligne["groupname"]}");
		$btname="{apply}";
	}
	
	$html="<div style='font-size:30px;margin-bottom:30px'>$title</div>
	<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td style='font-size:22px' class=legend>{groupname}:</td>
			<td>". Field_text("groupname-$t",$ligne["groupname"],"font-size:22px;width:300px")."</td>
		<tr>
		<tr>
			<td style='font-size:22px' class=legend>{enabled}:</td>
			<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
		<tr>					
		<tr>
			<td style='font-size:22px' class=legend>{type}:</td>
			<td>". field_array_Hash($haproxy->acl_GroupType,"grouptype-$t",$ligne["rule_action_data"],"blur()",null,0,"font-size:22px",false)."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",26)."</td>
		</tr>
		</table>
	</div>
<script>
var xSave$t=function (obj) {
	var gpid='$gpid';
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	
	if(document.getElementById('HAPROXY_OBJECTS_LIST_ACLS')){
		$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	}
	if(document.getElementById('HAPROXY_OBJECTS_LIST_ACLS')){
		$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	}
	if(document.getElementById('HAPROXY_BROWSE_ACL_GROUPS_TOT')){
		$('#HAPROXY_BROWSE_ACL_GROUPS_TOT').flexReload();	
	}
	if(gpid==0){ YahooWinBrowseHide();}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	var pp=encodeURIComponent(document.getElementById('groupname-$t').value);
	XHR.appendData('gpid','$gpid');
	XHR.appendData('linkacl',$linkAcl);
    XHR.appendData('groupname',pp);
    XHR.appendData('grouptype',document.getElementById('grouptype-$t').value);
    XHR.appendData('enabled',enabled);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}	

function check$t(){
	var gpid='$gpid';
	if(gpid>0){
		document.getElementById('grouptype-$t').disabled=true;
	}
	var enabled='{$ligne["enabled"]}';

}
check$t();
</script>					
";
	
echo $tpl->_ENGINE_parse_body($html);
	
}


function save(){
	$gpid=$_POST["gpid"];
	$linkacl=$_POST["linkacl"];
	$groupname=mysql_escape_string2(url_decode_special_tool($_POST["groupname"]));
	$groupname=replace_accents($groupname);
	$groupname=strtolower($groupname);
	$grouptype=$_POST["grouptype"];
	$enabled=$_POST["enabled"];
	
	if($gpid==0){
		$sql="INSERT IGNORE INTO haproxy_acls_groups (groupname,grouptype,enabled) VALUES ('$groupname','$grouptype','$enabled')";
	}else{
		$sql="UPDATE haproxy_acls_groups SET groupname='$groupname',enabled='$enabled' WHERE ID=$gpid";
	}
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("haproxy_acls_groups", "artica_backup")){$q->BuildTables();}
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
	$gpid=$q->last_id;
	
	if($linkacl>0){
		$sql="INSERT IGNORE INTO haproxy_acls_link (ruleid,groupid,operator) VALUES ('$linkacl','$gpid','0')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
}