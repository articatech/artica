<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.roundcube.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');

	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["RoundCubeHTTPSPort"])){Save();exit;}
	
main();
	
function main(){
	
	$page=CurrentPageName();
	$user=new usersMenus();
	$round=new roundcube();
	$artica=new artica_general();
	$sock=new sockets();
	$debug_levela=array(1=>"log",2=>"report",4=>"show",8=>"trace");
	
	$tpl=new templates();
	$RoundCubeHTTPSPort=intval($sock->GET_INFO("RoundCubeHTTPSPort"));
	$RoundCubeHTTPPort=intval($sock->GET_INFO("RoundCubeHTTPPort"));
	if($RoundCubeHTTPSPort==0){$RoundCubeHTTPSPort=449;}
	if($RoundCubeHTTPPort==0){$RoundCubeHTTPPort=8888;}
	$RoundCubeHTTPEngineEnabled=intval($sock->GET_INFO("RoundCubeHTTPEngineEnabled"));
	$RoundCubeUseSSL=intval($sock->GET_INFO("RoundCubeUseSSL"));
	$RoundCubeEnableLDAP=intval($sock->GET_INFO("RoundCubeEnableLDAP"));
	$RoundCubeUserLink=$sock->GET_INFO("RoundCubeUserLink");
	if($RoundCubeUserLink==null){
		$RoundCubeUserLink="http://{$_SERVER["SERVER_NAME"]}:$RoundCubeHTTPPort";
		$sock->SET_INFO("RoundCubeUserLink", "http://{$_SERVER["SERVER_NAME"]}:$RoundCubeHTTPPort");
	}
	
	$RoundCubeDebugLevel=intval($sock->GET_INFO("RoundCubeDebugLevel"));
	
	$debug_level=Field_array_Hash($debug_levela,'RoundCubeDebugLevel',$RoundCubeDebugLevel,"style:font-size:22px");
	$RoundCubeEnableCaching=intval($sock->GET_INFO("RoundCubeEnableCaching"));
	$RoundCubeAutoCreateuser=intval($sock->GET_INFO("RoundCubeAutoCreateuser"));
	$RoundCubeDefaultHost=$sock->GET_INFO("RoundCubeDefaultHost");
	if($RoundCubeDefaultHost==null){$RoundCubeDefaultHost="127.0.0.1";}
	$RoundCubeSievePort=$sock->GET_INFO("RoundCubeSievePort");
	if($RoundCubeSievePort==null){$RoundCubeSievePort=$round->SieveListenIp.":4190";}
	$RoundCubeUploadMaxFilesize=intval($sock->GET_INFO("RoundCubeUploadMaxFilesize"));
	if($RoundCubeUploadMaxFilesize==0){$RoundCubeUploadMaxFilesize=128;}
	
	$RoundeCubeLocalString=$sock->GET_INFO("RoundeCubeLocalString");
	if($RoundeCubeLocalString==null){$RoundeCubeLocalString="us";}
	$RoundCubeProductName=$sock->GET_INFO("RoundCubeProductName");
	if($RoundCubeProductName==null){$RoundCubeProductName="RoundCube Webmail for Artica";}
	
	$RoundCubeSkipDeleted=intval($sock->GET_INFO("RoundCubeSkipDeleted"));
	$RoundCubeFlagForDeletion=intval($sock->GET_INFO("RoundCubeFlagForDeletion"));
	
	$t=time();
	$html="
	<form name='FFM1'>
	<div id='wait'></div>
	<div style='width:99%' class=form>
	<table style='width:99%'>
	<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{RoundCubePath}:</strong></td>
	<td valign='top' nowrap align='left'><strong style='font-size:22px'>$user->roundcube_folder</td>
	</tr>
	<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{roundcube_web_folder}:</strong></td>
	<td valign='top' nowrap align='left'><strong style='font-size:22px'>$user->roundcube_web_folder</td>
	</tr>
	<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{listen_port} SSL:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_text('RoundCubeHTTPSPort',$RoundCubeHTTPSPort,'width:110px;font-size:22px')."</td>
	</tr>
	<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{listen_port} HTPP:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_text('RoundCubeHTTPPort',$RoundCubeHTTPPort,'width:110px;font-size:22px')."</td>
	</tr>			
	<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{UseSSL}:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_checkbox_design('RoundCubeUseSSL',1,$RoundCubeUseSSL)."</td>
	</tr>
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{user_link}:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_text('RoundCubeUserLink',$RoundCubeUserLink,'width:595px;font-size:22px')."</td>
</tr>
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{roundcube_ldap_directory}:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_checkbox_design('RoundCubeEnableLDAP',1,$RoundCubeEnableLDAP)."</td>
</tr>	
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{debug_level}:</strong></td>
	<td valign='top' nowrap align='left'><strong>$debug_level</td>
</tr>
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{enable_caching}:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_checkbox_design('RoundCubeEnableCaching',1,$RoundCubeEnableCaching)."</td>
</tr>
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{upload_max_filesize}:</strong></td>
	<td valign='top' nowrap align='left' style='font-size:22px'>" . Field_text('RoundCubeUploadMaxFilesize',$RoundCubeUploadMaxFilesize,'width:90px;font-size:22px')."M</td>
</tr>

		
<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>{auto_create_user}:</strong></td>
	<td valign='top' nowrap align='left'>" . Field_checkbox_design('RoundCubeAutoCreateuser',1,$RoundCubeAutoCreateuser)."</td>
</tr>
<tr>
	<td align='right' class=legend style='font-size:22px'>{default_host}:</strong></td>
	<td>" . Field_text('RoundCubeDefaultHost',$RoundCubeDefaultHost,'width:230px;font-size:22px')."</td>
</tr>

<tr>
	<td valign='top' nowrap align='right' class=legend style='font-size:22px'>Sieve:</strong></td>
	<td valign='top' nowrap align='left' style='font-size:22px'>" . Field_text('RoundCubeSievePort',$RoundCubeSievePort,'width:190px;font-size:22px')."</td>
</tr>

<tr>
	<td align='right' class=legend style='font-size:22px'>{locale_string}:</strong></td>
	<td>" . Field_text('RoundeCubeLocalString',trim($RoundeCubeLocalString),'width:60px;font-size:22px')."</td>
</tr>		
		
<tr>
	<td align='right' class=legend style='font-size:22px'>{product_name}:</strong></td>
	<td>" . Field_text('RoundCubeProductName',trim($RoundCubeProductName),'width:550px;font-size:22px')."</td>
</tr>	
<tr>
	<td align='right' class=legend style='font-size:22px'>{skip_deleted}:</strong></td>
	<td>" . Field_checkbox_design('RoundCubeSkipDeleted',1,$RoundCubeSkipDeleted )."</td>
</tr>
<tr>
	<td align='right' class=legend style='font-size:22px'>{flag_for_deletion}:</strong></td>
	<td style='padding-left:-3px'>
	<table style='width:100%;margin-left:-4px;padding:0px'>
	<tr>
	<td width=1%  valign='top' style='padding-left:-3px'>
	" . Field_checkbox_design('RoundCubeFlagForDeletion',1,$RoundCubeFlagForDeletion)."</td>
	<td valign='center' >".help_icon('{flag_for_deletion_text}',true)."</td>
	</tr>
	</table>
	</td>
</tr>			
			
			
	<tr>
		<td colspan=2 align='right'>
			".button('{apply}',"SaveRoundCubeForm$t()",40)."
	</tr>
			</table>
	</form>
</div>
							<script>
								
var X_SaveRoundCubeForm$t= function (obj) {
}
								
function SaveRoundCubeForm$t(){
	var XHR = new XHRConnection();
	
	if(document.getElementById('RoundCubeUseSSL').checked){XHR.appendData('RoundCubeUseSSL','1');}else{XHR.appendData('RoundCubeUseSSL','0');}
	if(document.getElementById('RoundCubeEnableLDAP').checked){XHR.appendData('RoundCubeEnableLDAP','1');}else{XHR.appendData('RoundCubeEnableLDAP','0');}
	if(document.getElementById('RoundCubeEnableCaching').checked){XHR.appendData('RoundCubeEnableCaching','1');}else{XHR.appendData('RoundCubeEnableCaching','0');}
	if(document.getElementById('RoundCubeSkipDeleted').checked){XHR.appendData('RoundCubeSkipDeleted','1');}else{XHR.appendData('RoundCubeSkipDeleted','0');}
	if(document.getElementById('RoundCubeFlagForDeletion').checked){XHR.appendData('RoundCubeFlagForDeletion','1');}else{XHR.appendData('RoundCubeFlagForDeletion','0');}
	
	
	
	XHR.appendData('RoundCubeProductName',document.getElementById('RoundCubeProductName').value);
	XHR.appendData('RoundCubeUploadMaxFilesize',document.getElementById('RoundCubeUploadMaxFilesize').value);
	XHR.appendData('RoundCubeSievePort',document.getElementById('RoundCubeSievePort').value);
	XHR.appendData('RoundeCubeLocalString',document.getElementById('RoundeCubeLocalString').value);
	XHR.appendData('RoundCubeDebugLevel',document.getElementById('RoundCubeDebugLevel').value);
	XHR.appendData('RoundCubeUserLink',document.getElementById('RoundCubeUserLink').value);
	XHR.appendData('RoundCubeHTTPSPort',document.getElementById('RoundCubeHTTPSPort').value);
	XHR.appendData('RoundCubeHTTPPort',document.getElementById('RoundCubeHTTPPort').value);
	XHR.sendAndLoad('$page', 'POST',X_SaveRoundCubeForm$t);
	}
</script>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}	


function Save(){
	$sock=new sockets();
	while (list ($num, $line) = each ($_POST)){
		$sock->SET_INFO($num, $line);
		
	}
	
	$sock->getFrameWork("roundcube.php?restart=yes");
}



