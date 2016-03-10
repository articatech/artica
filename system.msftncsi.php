<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_POST["EnableMsftncsi"])){save();exit;}

page();

function page(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ip=new networking();
	$msftncsiBindIpAddress=$sock->GET_INFO("msftncsiBindIpAddress");
	$msftncsiSchedule=$sock->GET_INFO("msftncsiSchedule");
	$EnableMsftncsi=intval($sock->GET_INFO("EnableMsftncsi"));
	$t=time();
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$CRON[1]="1 {minute}";
	$CRON[2]="2 {minutes}";
	$CRON[4]="4 {minutes}";
	$CRON[5]="5 {minutes}";
	$CRON[8]="8 {minutes}";
	$CRON[10]="10 {minutes}";
	$CRON[30]="30 {minutes}";
	$CRON[60]="1 {hour}";
	$arrcp[null]="{default}";
	
	
	$p1=Paragraphe_switch_img("{network_awareness}", "{network_awareness_explain}",
			"EnableMsftncsi",$EnableMsftncsi,null,1030);
	
	$WgetBindIpAddress=Field_array_Hash($arrcp,"msftncsiBindIpAddress",$msftncsiBindIpAddress,null,null,0,
			"font-size:26px;padding:3px;");
	
	$html="
	<div style='font-size:40px;margin-bottom:40px'>{network_awareness}</div>
	<div style='width:98%' class=form>		
	$p1
	
	<table style='width:100%'>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:26px'>{url}:</strong></td>
		<td align='left' style='vertical-align:top;font-size:26px;'>http://www.msftncsi.com/ncsi.txt</td>
	</tr>	
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:26px'>{WgetBindIpAddress}:</strong></td>
		<td align='left'>$WgetBindIpAddress</td>
	</tr>
	<tr>
		<td style='font-size:24px' class=legend>{interval}:</td>
		<td style='vertical-align:top;font-size:26px;'>". Field_array_Hash($CRON,"msftncsiSchedule",$msftncsiSchedule,"blur()",null,0,"font-size:26px;")."</td>	
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
	</tr>
</table>
</div>
<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('msftncsiBindIpAddress',document.getElementById('msftncsiBindIpAddress').value);
	XHR.appendData('msftncsiSchedule',document.getElementById('msftncsiSchedule').value);
	XHR.appendData('EnableMsftncsi',document.getElementById('EnableMsftncsi').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num,$ligne);
	}
	$sock->getFrameWork("system.php?msftncsi=yes");
	
}
