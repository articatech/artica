<?php
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
	
	
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["vsftpd-status"])){vsftpd_status();exit;}
	if(isset($_GET["vsftpd-config"])){vsftpd_config();exit;}
	if(isset($_GET["vsftpd-settings"])){vsftpd_settings();exit;}
	if(isset($_POST["EnableProFTPD"])){EnableVSFTPDDaemon();exit;}
	if(isset($_POST["VsFTPDPassive"])){vsftpd_settings_save();exit;}
	if(isset($_POST["ProFTPDLeftMenu"])){ProFTPDLeftMenu();exit;}
tabs();


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["status"]="{status}";
	$array["vsftpd-settings"]="{settings}";
	$array["proftpd-members"]="{members}";
	//$array["events"]="{events}";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"sarg.events.php?popup=yes\"><span style='font-size:26px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="proftpd-members"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"proftpd.members.php\"><span style='font-size:26px'>$ligne</span></a></li>\n");
			continue;
		}		
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:26px'>$ligne</span></a></li>\n");
	
	}
	
	$id=time();
	
	echo build_artica_tabs($html, "vsftpd_tabs")."<script>LeftDesign('FTP-white-256-opc20.png');</script>";
}

function status(){
	$error=null;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	
$html="
<div style='font-size:26px;margin-bottom:30px'>{APP_PROFTPD}</div>$error
<table style='width:100%'>
<tr>
	<td valign='top' style='width:30%'><div id='vsftpd-status'></div></td>
	<td valign='top' style='width:30%'><div id='vsftpd-config'></div></td>	
</tr>
</table>	
<script>
	LoadAjax('vsftpd-status','$page?vsftpd-status=yes');
	LoadAjax('vsftpd-config','$page?vsftpd-config=yes');
</script>
";
	
echo $html;
	
	
}

function vsftpd_status(){
	$tpl=new templates();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('proftpd.php?status=yes')));
	
	$html=DAEMON_STATUS_ROUND("APP_PROFTPD",$ini,null,0)."
	<div style='margin-top:15px;text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('vsftpd-status','$page?vsftpd-status=yes');")."</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function vsftpd_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();	
	$VsFTPDPassive=$sock->GET_INFO("VsFTPDPassive");
	if(!is_numeric($VsFTPDPassive)){$VsFTPDPassive=1;}
	$VsFTPDPassiveAddr=$sock->GET_INFO("VsFTPDPassiveAddr");
	$pasv_min_port=intval($sock->GET_INFO("VsFTPDPassiveMinPort"));
	$pasv_max_port=intval($sock->GET_INFO("VsFTPDPassiveMaxPort"));
	$VsFTPDFileOpenMode=$sock->GET_INFO("VsFTPDFileOpenMode");
	$VsFTPDLocalUmask=$sock->GET_INFO("VsFTPDLocalUmask");
	if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
	if($VsFTPDLocalUmask==null){$VsFTPDLocalUmask="077";}
	$ProFTPDRootLogin=intval($sock->GET_INFO("ProFTPDRootLogin"));
	
	$VsFTPDLocalMaxRate=intval($sock->GET_INFO("VsFTPDLocalMaxRate"));
	
	$umask["022"]="{permissive} 755";
	$umask["026"]="{moderate} 751";
	$umask["027"]="{moderate} 750";
	$umask["077"]="{severe}	700";
	
	if($pasv_min_port==0){$pasv_min_port=40000;}
	if($pasv_max_port==0){$pasv_max_port=40200;}
	
	$html="
<div style='width:98%' class=form>
		". Paragraphe_switch_img("{enable_passive_mode}", "{enable_passive_mode_explain}","VsFTPDPassive",$VsFTPDPassive,null,650)."
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{pasv_min_port}:</td>
			<td>". field_text("VsFTPDPassiveMinPort",$pasv_min_port,"explain={pasv_minmax_port_explain};font-size:18px;width:110px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{pasv_max_port}:</td>
			<td>". field_text("VsFTPDPassiveMaxPort",$pasv_max_port,"explain={pasv_minmax_port_explain};font-size:18px;width:110px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{pasv_address}:</td>
			<td>". field_ipv4("VsFTPDPassiveAddr",$VsFTPDPassiveAddr,"explain={pasv_address_explain};font-size:18px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:18px'>{files_permissions}:</td>
			<td>". field_text("VsFTPDFileOpenMode",$VsFTPDFileOpenMode,"explain={VsFTPDFileOpenMode};font-size:18px;width:110px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{directories_permissions}:</td>
			<td>". Field_array_Hash($umask,"VsFTPDLocalUmask",$VsFTPDLocalUmask,"style:font-size:18px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{PermitRootLogin}:</td>
			<td style='font-size:18px'>". Field_checkbox_design("ProFTPDRootLogin",1,"$ProFTPDRootLogin")."</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:18px'>{max_rate}:</td>
			<td style='font-size:18px'>". field_text("VsFTPDLocalMaxRate","$VsFTPDLocalMaxRate","font-size:18px;width:110px")."&nbsp;Ko/s</td>
		</tr>					
					
	</table>
	<div style='text-align:right'><hr>". button("{apply}","Save$t();",26)."</div>
</div>
<script>
var x_Save$t= function (obj) {
	
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(document.getElementById('vsftpd_tabs')){RefreshTab('vsftpd_tabs');}
	Loadjs('proftpd.progress.php');
}	
	
function Save$t(){
	var ProFTPDRootLogin=0;
	var XHR = new XHRConnection();
	XHR.appendData('VsFTPDPassive',document.getElementById('VsFTPDPassive').value);
	XHR.appendData('VsFTPDPassiveAddr',document.getElementById('VsFTPDPassiveAddr').value);
	
	XHR.appendData('VsFTPDPassiveMinPort',document.getElementById('VsFTPDPassiveMinPort').value);
	XHR.appendData('VsFTPDPassiveMaxPort',document.getElementById('VsFTPDPassiveMaxPort').value);
	XHR.appendData('VsFTPDFileOpenMode',document.getElementById('VsFTPDFileOpenMode').value);
	XHR.appendData('VsFTPDLocalUmask',document.getElementById('VsFTPDLocalUmask').value);
	XHR.appendData('VsFTPDLocalMaxRate',document.getElementById('VsFTPDLocalMaxRate').value);
	if(document.getElementById('ProFTPDRootLogin').checked){ProFTPDRootLogin=1;}
	XHR.appendData('ProFTPDRootLogin',ProFTPDRootLogin);
	XHR.sendAndLoad('$page', 'POST',x_Save$t);	
}
</script>			
			
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function vsftpd_settings_save(){
	$sock=new sockets();
	$sock->SET_INFO("VsFTPDPassive", $_POST["VsFTPDPassive"]);
	$sock->SET_INFO("VsFTPDPassiveAddr", $_POST["VsFTPDPassiveAddr"]);
	
	$sock->SET_INFO("VsFTPDPassiveMinPort", $_POST["VsFTPDPassiveMinPort"]);
	$sock->SET_INFO("VsFTPDPassiveMaxPort", $_POST["VsFTPDPassiveMaxPort"]);
	$sock->SET_INFO("VsFTPDFileOpenMode", $_POST["VsFTPDFileOpenMode"]);
	$sock->SET_INFO("VsFTPDLocalUmask", $_POST["VsFTPDLocalUmask"]);
	$sock->SET_INFO("VsFTPDLocalMaxRate", $_POST["VsFTPDLocalMaxRate"]);
	$sock->SET_INFO("ProFTPDRootLogin", $_POST["ProFTPDRootLogin"]);
	
	
	
}

function vsftpd_config(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$EnableProFTPD=intval($sock->GET_INFO("EnableProFTPD"));
	$VSFTPDPort=intval($sock->GET_INFO("VSFTPDPort"));
	$ProFTPDLeftMenu=intval($sock->GET_INFO("ProFTPDLeftMenu"));
	if($VSFTPDPort==0){$VSFTPDPort=21;}
	$t=time();
	$html="<div style='width:98%' class=form>
		". Paragraphe_switch_img("{enable_ftp_service}", "{enable_ftp_service_vsftpd_explain}","EnableProFTPD",
				$EnableProFTPD,null,980)."
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:26px'>{add_to_menu}:</td>
					<td>". Field_checkbox_design("ProFTPDLeftMenu",1,$ProFTPDLeftMenu,"ProFTPDLeftMenuCheck()")."</td>
				</tr>						
			<tr>
				<td class=legend style='font-size:26px'>{listen_port}:</td>
				<td>". Field_text("VSFTPDPort",$VSFTPDPort,"font-size:26px;width:150px")."</td>
			</tr>
			</table>
			<div style='text-align:right'><hr>". button("{apply}","Save$t();",30)."</div>
			</div>
	<script>
var x_Save$t= function (obj) {
	
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(document.getElementById('vsftpd_tabs')){RefreshTab('vsftpd_tabs');}
	Loadjs('proftpd.progress.php');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableProFTPD',document.getElementById('EnableProFTPD').value);
	XHR.appendData('VSFTPDPort',document.getElementById('VSFTPDPort').value);
	XHR.sendAndLoad('$page', 'POST',x_Save$t);	
}

var xProFTPDLeftMenuCheck=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	AjaxTopMenu('template-top-menus','admin.top.menus.php');			
}

function ProFTPDLeftMenuCheck(){
	var XHR = new XHRConnection();
	if(document.getElementById('ProFTPDLeftMenu').checked){
		XHR.appendData('ProFTPDLeftMenu',1);
	}else{
		XHR.appendData('ProFTPDLeftMenu',0);
	}
	XHR.sendAndLoad('$page', 'POST',xProFTPDLeftMenuCheck);
}


</script>			
			";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}
function EnableVSFTPDDaemon(){
	$sock=new sockets();
	$sock->SET_INFO("EnableProFTPD", $_POST["EnableProFTPD"]);
	$sock->SET_INFO("VSFTPDPort", $_POST["VSFTPDPort"]);
	
}
function ProFTPDLeftMenu(){
	$sock=new sockets();
	$sock->SET_INFO("ProFTPDLeftMenu", $_POST["ProFTPDLeftMenu"]);
}

