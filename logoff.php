<?php
session_start();


/*	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	*/

if(isset($_GET["shutdown-js"])){shutdown_js();exit;}
if(isset($_POST["defrag"])){defrag();exit;}
if(isset($_GET["restart-js"])){reboot_js();exit;}
if(isset($_GET["defrag-js"])){defrag_js();exit;}



if(isset($_GET["menus"])){
	echo menus();
	exit;
}

if(isset($_GET["perform"])){
	perform();
	exit;
}

function defrag(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once('ressources/class.templates.inc');
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die();}	
	$sock=new sockets();
	$DisableRebootOrShutDown=$sock->GET_INFO('DisableRebootOrShutDown');		
	if($DisableRebootOrShutDown==1){return;}
	$sock->getFrameWork("services.php?system-defrag=yes");	
	echo "See you !! :=)\n";
}

function perform(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once('ressources/class.templates.inc');
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die();}
	
	$sock=new sockets();
	$DisableRebootOrShutDown=$sock->GET_INFO('DisableRebootOrShutDown');		
	if($DisableRebootOrShutDown==1){return;}
	
	if($_GET["perform"]=="reboot"){
		$sock->getFrameWork("cmd.php?system-reboot=yes");
	}
	
	if($_GET["perform"]=="shutdown"){
		$sock->getFrameWork("cmd.php?system-shutdown=yes");
	}	
}


function reboot_js(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once('ressources/class.templates.inc');
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die();}	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$restart_computer_text=$tpl->javascript_parse_text("{restart_computer_text}");
	header("content-type: application/x-javascript");
	$html="
var x_turnoff$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	window.location ='logoff.php';
}
	
	
function turningoff$t(){
	if(!confirm('$restart_computer_text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('perform','reboot');
	XHR.sendAndLoad('$page', 'GET',x_turnoff$t);
}
	
turningoff$t();
	";
	echo $html;	
	
}

function shutdown_js(){
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.users.menus.inc");
	include_once(dirname(__FILE__) . "/ressources/class.templates.inc");	
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsSystemAdministrator){die();}
	$tpl=new templates();
	$warn=$tpl->javascript_parse_text("{warn_shutdown_computer}");
	header("content-type: application/x-javascript");
	$html="
var x_turnoff= function (obj) {
				var results=obj.responseText;
				if(results.length>0){alert(results);}
				window.location ='$page';
				
			}	
	
	
	function turningoff(){
		if(confirm('$warn')){
			var XHR = new XHRConnection();
			XHR.appendData('perform','shutdown');
			XHR.sendAndLoad('$page', 'GET',x_turnoff);
		}
	}
	
	
	turningoff();
	";
	echo $html;
	
}

function defrag_js(){
	include_once('ressources/class.templates.inc');
	$tpl=new templates();
	$restart_computer_and_defrag_warn=$tpl->javascript_parse_text("{restart_computer_and_defrag_warn}");
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsSystemAdministrator){die();}	
	$t=time();
	header("content-type: application/x-javascript");
echo "
var x_RestartDefragComputer$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    window.location ='$page';
}	
	
	
function RestartDefragComputer$t(){
	if(!confirm('$restart_computer_and_defrag_warn')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('defrag','yes');
	XHR.sendAndLoad('$page', 'POST',x_RestartDefragComputer$t);
}
	
RestartDefragComputer$t();";
	
	
	
}


function menus(){
	include_once('ressources/class.templates.inc');
	$tpl=new templates();
	$restart_computer_and_defrag_warn=$tpl->javascript_parse_text("{restart_computer_and_defrag_warn}");
	$page=CurrentPageName();
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$html="
		<input type='hidden' id='isanuser' name ='isanuser' value='1'>
		<center><H2 style='color:#d32d2d'>{logoff}</H2></center>
		";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
		exit;}
	
	$sock=new sockets();
	$AllowShutDownByInterface=$sock->GET_INFO('AllowShutDownByInterface');
	$DisableRebootOrShutDown=$sock->GET_INFO('DisableRebootOrShutDown');		
		
	if($AllowShutDownByInterface==1){
		$AllowShutDownByInterface_tr="
		<td align='center'>
			".imgtootltip('shutdown-computer-64.png','{shutdown}',"Loadjs('logoff.php?shutdown-js=yes')")."
		</td>		
		";
	}
	$reboot=imgtootltip('reboot-computer-64.png','{restart_computer}','RestartComputer()');
	$rebootfsck="<td align='center'>".imgtootltip('reboot-computer-defrag-64.png','{restart_computer_and_defrag}','RestartDefragComputer()')."</td>";
	
	
	if($DisableRebootOrShutDown==1){
		$reboot=imgtootltip('reboot-computer-64-grey.png','{restart_computer}');
		$rebootfsck="<td align='center'>".imgtootltip('reboot-computer-defrag-64-off.png','{restart_computer_and_defrag}','blur()')."</td>";
		
		if($AllowShutDownByInterface_tr<>null){
			$AllowShutDownByInterface_tr=
			"<td align='center'>".imgtootltip('shutdown-computer-64-grey.png','{shutdown}')."</td>";
		}
	}
	
	
	
	
	
	$html="
	<input type='hidden' id='shutdown_computer_text' value='{shutdown_computer_text}'>
	<input type='hidden' id='restart_computer_text' value='{restart_computer_text}'>
	<table style='width:100%'>
	<tr>
		<td align='center'>".imgtootltip('64-disconnect.png','{logoff}',"MyHref('logoff.php')")."</td>		
		<td align='center'>$reboot</td>
		$rebootfsck
		$AllowShutDownByInterface_tr		
	</tr>
	</table>
	<script>
	var x_RestartDefragComputer=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>3){alert(tempvalue);}
      window.location ='$page';
      
     }	
	
	
	function RestartDefragComputer(){
			if(confirm('$restart_computer_and_defrag_warn')){
				var XHR = new XHRConnection();
				XHR.appendData('defrag','yes');
				XHR.sendAndLoad('$page', 'POST',x_RestartDefragComputer);
			}
		}
	</script>
	";
	$tpl=new templates();
	$page=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$page);
	echo $page;
}


if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/pattern.png")){$pattern="ressources/templates/{$_COOKIE["artica-template"]}/i/pattern.png";}
if($pattern==null){$pattern="ressources/templates/default/img/pattern.png";}

if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png")){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png";}

if($logo==null){
	if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/logo.png")){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/logo.png";}
}

if($logo==null){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png";}


unset($_SESSION["privileges_array"]);
unset($_SESSION["FORCED_TEMPLATE"]);
unset($_SESSION["MINIADM"]);
unset($_SESSION["uid"]);
unset($_SESSION["privileges"]);
unset($_SESSION["qaliases"]);
unset($_SERVER['PHP_AUTH_USER']);
unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
unset($_SESSION['smartsieve']['authz']);
unset($_SESSION["passwd"]);
unset($_SESSION["LANG_FILES"]);
unset($_SESSION["TRANSLATE"]);
unset($_SESSION["__CLASS-USER-MENUS"]);
unset($_SESSION["FONT_CSS"]);
unset($_SESSION["translation"]);
unset($_SESSION["CLASS_TRANSLATE_RIGHTS"]);
$_COOKIE["username"]="";
$_COOKIE["password"]="";
$_COOKIE["MINIADM"]="";


while (list ($num, $ligne) = each ($_SESSION) ){
	unset($_SESSION[$num]);
}

session_destroy();
$URL="logon.php";
if(isset($_GET["goto"])){
$URL=$_GET["goto"];	
}

echo "
<html>
<head>
<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$URL\"> 
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />

</head>
<body style='padding:100px;background-image:url($pattern)'>

	<center style='border:3px solid white;padding:5px'><a style='font-size:22px;font-family:arial,tahoma;font-weight:bold;color:white' href='logon.php'>
	Waiting please, redirecting to logon page</a>
	</center>

<center style='padding:15px;background-image:url($logo);background-repeat:no-repeat;background-position:center top;width:100%;height:768px'>

</body>
</html>




";
exit;
	

$html="
<center>
				<form>
				
				<div style='float:right;margin-right:65px;margin-top:60px'>
				<table >
				<tr>
				<td align='right'><strong>{username}:</strong></td>
				<td><input type='text' id='username' value='' style='border:1px solid black;width:130px' OnKeyPress=\"javascript:logon(event);\"></td>
				</tr>
				<tr>
				<td align='right'><strong>{password}:</strong></td>
				<td><input type='password'  id='password' value=''style='border:1px solid black;width:130px' OnKeyPress=\"javascript:logon(event)\"></td>
				</tr>	
				<tr>
				<td colspan=2 align='right' style='padding-right:10px'>
					<input type='button' OnClick=\"javascript:logon();\" value='{logon}&nbsp;&raquo;'>
				</td>
				</tr>
				</table>
				</div>
				
				

				
				
				</form>
				</div>
				</center>";

$tpl=new template_users('{disconnected}',$html,1,0,0,0);
echo $tpl->web_page;


?>