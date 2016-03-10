<?php
if(posix_getuid()==0){die();}
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.haproxy.inc');




$user=new usersMenus();
if($user->AsDansGuardianAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}
if(isset($_GET["ActionItem"])){ActionItem();exit;}
if(isset($_POST["ruleid"])){Save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=time();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE ID='$ID'","artica_backup"));
	$servicename=$ligne["servicename"];
	$haproxy=new haproxy();
	$rule_action_data=$ligne["rule_action_data"];
	
	$html="<div style='width:100%;font-size:30px;margin-bottom:30px'>{$ligne["rulename"]}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend>{rulename}:</td>
		<td>". Field_text("rulename-$t",$ligne["rulename"],"font-size:22px;width:95%")."</td>
	</tr>	
	<tr>
		<td style='font-size:22px' class=legend>{order}:</td>
		<td>". Field_text("zorder-$t",$ligne["zorder"],"font-size:22px;width:110px")."</td>
	</tr>				
				
	<tr>
		<td style='font-size:22px' class=legend>{method}:</td>
		<td>". Field_array_Hash($haproxy->acls_actions,"rule_action-$t",$ligne["rule_action"],"ActionItem$t()",null,0,"font-size:22px",false)."</td>
	</tr>			
	<tr>
		<td style='font-size:22px' class=legend>{value}:</td>
		<td><span id='ActionItem-$t'></span></td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{apply}", "Save$t()",30)."</td>
	</table>	
	</div>
<script>
	function ActionItem$t(){
		var action=document.getElementById('rule_action-$t').value;
		LoadAjaxSilent('ActionItem-$t','$page?ActionItem=yes&selected='+action+'&t=$t&servicename=$servicename&rule_action_data=$rule_action_data');
	
	}
	
var xSave$t=function (obj) {
	var servicename='$servicename';
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#HAPROXY_ACLS_TABLE').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('rulename-$t').value);
	XHR.appendData('ruleid','$ID');
	XHR.appendData('rulename',pp);
    XHR.appendData('rule_action',document.getElementById('rule_action-$t').value);
    XHR.appendData('rule_action_data',document.getElementById('rule_action_data-$t').value);
    XHR.appendData('zorder',document.getElementById('zorder-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
ActionItem$t();
</script>	
	";
	
	
echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));
	$sql="UPDATE haproxy_acls_rules SET 
		rulename='{$_POST["rulename"]}',
		rule_action='{$_POST["rule_action"]}',
		rule_action_data='{$_POST["rule_action_data"]}',
		zorder='{$_POST["zorder"]}'
		WHERE ID={$_POST["ruleid"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function ActionItem(){
	$servicename=$_GET["servicename"];
	$selected=$_GET["selected"];
	$t=$_GET["t"];
	$q=new mysql();
	$ARRAY=array();
	
	if($selected==1){
		$sql="SELECT ID,groupname FROM haproxy_backends_groups WHERE servicename='$servicename'";
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysql_fetch_assoc($results)) {
			$ARRAY[$ligne["ID"]]=$ligne["groupname"];
		}
		echo field_array_Hash($ARRAY,"rule_action_data-$t",$_GET["rule_action_data"],"blur()",null,0,"font-size:22px",false);
		return;
		
	}
	
	if($selected==2){
		$sql="SELECT ID,backendname FROM haproxy_backends WHERE servicename='$servicename'";
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysql_fetch_assoc($results)) {
			$ARRAY[$ligne["ID"]]=$ligne["backendname"];
		}
		echo field_array_Hash($ARRAY,"rule_action_data-$t",$_GET["rule_action_data"],"blur()",null,0,"font-size:22px",false);
		return;
	
	}	
	
	if($selected==3){
		$ARRAY[null]="{deny}";
		echo field_array_Hash($ARRAY,"rule_action_data-$t",$_GET["rule_action_data"],"blur()",null,0,"font-size:22px",false);
	}
}

