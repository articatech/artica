<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.squid.templates-simple.inc');
include_once('ressources/class.squid.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}

$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_POST["YoutubeLockerSize"])){YoutubeLockerSize();exit;}

TEMPLATE_SETTINGS();

function TEMPLATE_SETTINGS(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$error=null;
	$t=time();
	$button="<hr>".button("{save}", "Save$t()",40);
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	$EnableYoutubeLocker=intval($sock->GET_INFO("EnableYoutubeLocker"));
	$YoutubeLockerSize=intval($sock->GET_INFO("YoutubeLockerSize"));
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM proxy_ports WHERE UseSSL=1 AND enabled=1"));
	if($ligne["tcount"]==0){
		$error="<p class=text-error style='font-size:18px'>{feature_run_only_ssl}</p>";
		
	}
	
	
	$pp=Paragraphe_switch_img("{youtube_locker}", "{youtube_locker_explain}","EnableYoutubeLocker-$t",$EnableYoutubeLocker,null,1450);
	if($YoutubeLockerSize==0){$YoutubeLockerSize=144;}
	
	$RESOLUTIONS[144]="144p";
	$RESOLUTIONS[240]="240p";
	$RESOLUTIONS[360]="360p";
	$RESOLUTIONS[480]="480p";
	$RESOLUTIONS[720]="720p";
	
	
$html="
<div style='font-size:40px;margin-bottom:30px'>{youtube_locker}</div>		
$error
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=2>$pp</td>
	</tr>
<tr>
	<td class=legend style='font-size:24px' width=1% nowrap>{video_quality}:</td>
	<td width=99%>". Field_array_Hash($RESOLUTIONS,"YoutubeLockerSize-$t",$YoutubeLockerSize,"style:font-size:24px;width:240px")."</td>
</tr>
	<tr>
	<td colspan=2 align='right'>$button</td>
	</tr>
<script>
	var xSave$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	Loadjs('squid.ecap.progress.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('YoutubeLockerSize',document.getElementById('YoutubeLockerSize-$t').value);
	XHR.appendData('EnableYoutubeLocker',document.getElementById('EnableYoutubeLocker-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
}


function YoutubeLockerSize(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num,$ligne);
	}

}