<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.ccurl.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.resolv.conf.inc');
include_once('ressources/class.tcpip.inc');

$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_POST["fullhostname"])){fullhostname();exit;}
if(isset($_POST["WINDOWS_SERVER_ADMIN"])){WINDOWS_SERVER_ADMIN();exit;}
if(isset($_POST["html"])){SaveMysql();exit;}



js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("ActiveDirectory: {quick_connect}");


	echo "
	function Start$t(){
	RTMMail('890','$page?popup=yes','$title');
}
Start$t();";


}

function popup(){
	$page=CurrentPageName();
	$html="<div id='ACTIVEDIRECTORY_QUICK_CONNECT'></div>
	<script>LoadAjax('ACTIVEDIRECTORY_QUICK_CONNECT','$page?step1=yes');</script>
	";
	echo $html;


}

function step1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$fullhosname="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
	if($fullhosname==null){$fullhosname="adserv.domain.tld";}
	$t=time();
	$html="<div style='font-size:22px;margin-bottom:20px;font-size:20px' class=explain>
			ActiveDirectory: {quick_connect}
	<br>{ad_quick_1}		
			
	</div>

	<div style='width:98%' class=form>
	<table 	style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{ad_full_hostname}:</td>
			<td>". Field_text("fullhostname-$t",$fullhosname,"font-size:26px;",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>".button("{next}","Save$t()",32)."</td>
		</tr>
	</table>
	</div>
	<script>
	var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		LoadAjax('ACTIVEDIRECTORY_QUICK_CONNECT','$page?step2=yes');
	}

function Save$t(){
var XHR = new XHRConnection();
	var rule=document.getElementById('fullhostname-$t').value;
	if(rule.length<1){return;}
	XHR.appendData('fullhostname',encodeURIComponent(document.getElementById('fullhostname-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);


}
function step2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));

	$fullhosname="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
	if($fullhosname==null){$fullhosname="adserv.domain.tld";}
	$t=time();
	$html="<div style='font-size:22px;margin-bottom:20px;font-size:20px' class=explain>
			ActiveDirectory: {quick_connect}
	<br>{ad_quick_2}
		
	</div>

	<div style='width:98%' class=form>
	<table 	style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN-$t",$array["WINDOWS_SERVER_ADMIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS-$t",$array["WINDOWS_SERVER_PASS"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>".button("{next}","Save$t()",32)."</td>
	</tr>
</table>
</div>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('ACTIVEDIRECTORY_QUICK_CONNECT','$page?step3=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	var rule=document.getElementById('WINDOWS_SERVER_ADMIN-$t').value;
	if(rule.length<1){return;}
	
	rule=document.getElementById('WINDOWS_SERVER_PASS-$t').value;
	if(rule.length<1){return;}
	
	
	XHR.appendData('WINDOWS_SERVER_ADMIN',encodeURIComponent(document.getElementById('WINDOWS_SERVER_ADMIN-$t').value));
	XHR.appendData('WINDOWS_SERVER_PASS',encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);


}
function step3(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));

	$fullhosname="{$array["WINDOWS_SERVER_NETBIOSNAME"]}.{$array["WINDOWS_DNS_SUFFIX"]}";
	$WINDOWS_SERVER_ADMIN=$array["WINDOWS_SERVER_ADMIN"];
	$ADNETIPADDR=$array["ADNETIPADDR"];
	$ADNETBIOSDOMAIN=$array["ADNETBIOSDOMAIN"];
	$t=time();
	$html="<div style='font-size:22px;margin-bottom:20px;font-size:20px' class=explain>
			ActiveDirectory: {quick_connect}
	<br>{ad_quick_3}

	</div>

	<div style='width:98%' class=form>
	<table 	style='width:100%'>
			
	<tr>
		<td class=legend style='font-size:22px'>{ad_full_hostname}:</td>
		<td style='font-size:22px'>$fullhosname</td>
		
	</tr>
<tr>
	<td class=legend style='font-size:22px'>{ADNETBIOSDOMAIN}:</td>
	<td style='font-size:22px'>$ADNETBIOSDOMAIN</td>
</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ADNETIPADDR}:</td>
		<td style='font-size:22px'>$ADNETIPADDR</td>
		
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:22px'>{administrator}:</td>
		<td style='font-size:22px'>$WINDOWS_SERVER_ADMIN</td>
		
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>".button("{connect}","Save$t()",32)."</td>
	</tr>
	</table>
</div>
<script>
function Save$t(){
	RTMMailHide();
	Loadjs('squid.ad.progress.php');
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);


}

function WINDOWS_SERVER_ADMIN(){
	$sock=new sockets();
	$WINDOWS_SERVER_ADMIN=url_decode_special_tool($_POST["WINDOWS_SERVER_ADMIN"]);
	$WINDOWS_SERVER_PASS=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$array["WINDOWS_SERVER_ADMIN"]=$WINDOWS_SERVER_ADMIN;
	$array["WINDOWS_SERVER_PASS"]=$WINDOWS_SERVER_PASS;
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	$sock->SET_INFO("EnableKerbAuth", 1);
	
}

function fullhostname(){
	$ipclass=new IP();
	$tpl=new templates();
	$hostname=url_decode_special_tool($_POST["fullhostname"]);
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$ipaddr=gethostbyname($hostname);
	if(!$ipclass->isValid($ipaddr)){
		$error=$tpl->javascript_parse_text("{adfullhostname_error_resolv}");
		$error=str_replace("%s", $hostname, $error);
		echo $error;
		
	}else{
		$array["ADNETIPADDR"]=$ipaddr;
	}
	
	if(strpos($hostname, ".")==0){
		echo $tpl->javascript_parse_text("{ad_quick_1}");
		return;
	}
	
	$tr=explode(".",$hostname);
	$netbios=$tr[0];
	unset($tr[0]);
	$ADNETBIOSDOMAIN=$tr[1];
	
	if(strlen($netbios)<2){
		echo "{$_POST["fullhostname"]} invalid!";
		return;
	}
	
	$array["WINDOWS_SERVER_NETBIOSNAME"]=$netbios;
	$array["ADNETBIOSDOMAIN"]=$ADNETBIOSDOMAIN;
	$array["WINDOWS_DNS_SUFFIX"]=@implode(".", $tr);
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
}
