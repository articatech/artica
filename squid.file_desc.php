<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["max_filedesc"])){max_filedesc();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{file_descriptors}");
	$page=CurrentPageName();
	$html="YahooWin3('890','$page?popup=yes','$title');";
	echo $html;	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();	
	$t=time();
	if(!is_numeric($squid->max_filedesc)){$squid->max_filedesc=8192;}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}

	$file_max=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("fs.file-max"));
	
	
	$html="
	<div id='$t' class=explain style='font-size:18px'>{file_descriptors_squid_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:20px'>{file_descriptors}:</td>
			<td>". Field_text("max_filedesc",$squid->max_filedesc,"font-size:20px;width:110px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:20px'>{NumberOfMaximumFiles}:</td>
			<td style='font-size:16px'>". Field_text("fs_filemax",$file_max,"font-size:20px;width:110px")."&nbsp;{files}</td>
		</tr>	
	
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "SaveCSVGen$t()","28")."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveCSVGen$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('$t').innerHTML='';
		javascript:Loadjs('squid.compile.progress.php');
		YahooWin3Hide();
	}	
	
	function SaveCSVGen$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}	
		var XHR = new XHRConnection();
		XHR.appendData('max_filedesc',document.getElementById('max_filedesc').value);
		XHR.appendData('fs_filemax',document.getElementById('fs_filemax').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCSVGen$t);	
		
	}	

	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function max_filedesc(){
	$squid=new squidbee();
	if($_POST["max_filedesc"]<1024){$_POST["max_filedesc"]=1024;}
	$squid->max_filedesc=$_POST["max_filedesc"];
	$squid->SaveToLdap(true);
	$sock=new sockets();
	
	$key=base64_encode("fs.file-max");
	$sock->getFrameWork("cmd.php?sysctl-setvalue={$_POST["fs_filemax"]}&key=$key");
	
	
	
}