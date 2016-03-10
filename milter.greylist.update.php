<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.milter.greylist.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.maincf.multi.inc');


if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}

$user=new usersMenus();
if(!$user->AsPostfixAdministrator){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
if(isset($_POST["EnableMilterGreylistExternalDB"])){EnableMilterGreylistExternalDB();exit;}

page();


function page(){

	$page=CurrentPageName();
	$tpl=new templates();

	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$EnableMilterGreylistExternalDB=intval($sock->GET_INFO("EnableMilterGreylistExternalDB"));
	$MilterGreylistExternalDBSchedule=intval($sock->GET_INFO("MilterGreylistExternalDBSchedule"));
	if($MilterGreylistExternalDBSchedule==0){$MilterGreylistExternalDBSchedule=4;}
	$MilterGreyListPatternTime=intval($sock->GET_INFO("MilterGreyListPatternTime"));
	$MilterGreyListPatternCount=intval($sock->GET_INFO("MilterGreyListPatternCount"));
	$EnableArticaTechSpamAssassin=intval($sock->GET_INFO("EnableArticaTechSpamAssassin"));
	$schedules[1]="1 {hour}";
	$schedules[2]="2 {hours}";
	$schedules[4]="4 {hours}";
	$schedules[8]="8 {hours}";
	$schedules[24]="1 {day}";
	
	
	$p=Paragraphe_switch_img("{EnableMilterGreylistExternalDB}", "{EnableMilterGreylistExternalDB_explain}","EnableMilterGreylistExternalDB-$t",$EnableMilterGreylistExternalDB,null,960);
	
	$p1=Paragraphe_switch_img("{EnableArticaTechSpamAssassin}", "{EnableArticaTechSpamAssassin_explain}","EnableArticaTechSpamAssassin-$t",$EnableArticaTechSpamAssassin,null,960);
	
	
	
	$field=Field_array_Hash($schedules, "MilterGreylistExternalDBSchedule-$t",$MilterGreylistExternalDBSchedule,"blur()",null,0,"font-size:26px");
	
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{rules_update}, {current}: v$MilterGreyListPatternTime {$MilterGreyListPatternCount} {rules}</div>		
	
	<div style='width:98%' class=form>
	$p
	$p1
	
	<table style='width:100%'>
	<tbody>
	<tr>
	<td class=legend style='font-size:26px'>{schedule}:</td>
	<td style='font-size:16px'>$field</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",45)."</td>
			</tr>
			</tbody>
			</table>
			</div>
			<script>
	
			var xSave$t= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
	}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaTechSpamAssassin', document.getElementById('EnableArticaTechSpamAssassin-$t').value);
	XHR.appendData('MilterGreylistExternalDBSchedule', document.getElementById('MilterGreylistExternalDBSchedule-$t').value);
	XHR.appendData('EnableMilterGreylistExternalDB', document.getElementById('EnableMilterGreylistExternalDB-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableMilterGreylistExternalDB(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}

	$sock->getFrameWork("system.php?EnableMilterGreylistExternalDB=yes");
	$sock->getFrameWork("cmd.php?postfix-body-checks=yes");

}