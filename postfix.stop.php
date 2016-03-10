<?php
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableStopPostfix"])){EnableStopPostfixSave();exit;}
	
js();
function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->javascript_parse_text("{stop_messaging}");
	$html="YahooWin2(890,'$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableStopPostfix=$sock->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	$t=time();
	$p=Paragraphe_switch_img("{stop_messaging}", "{stop_messaging_explain}",
			"EnableStopPostfix",$EnableStopPostfix,null,860);
	
	
	$html="
	<div id='$t'>
	$p
	<hr>
	</div>
	<div style='text-align:right;width:100%'>". button("{apply}","EnableStopPostfixSave()",30)."</div>
	<script>
	var x_EnableStopPostfixSave= function (obj) {
		var res=obj.responseText;
		YahooWin2Hide();
		Loadjs('postfix.stop.progress.php');
	}
	
	function EnableStopPostfixSave(){
		      var XHR = new XHRConnection();
		      XHR.appendData('EnableStopPostfix', document.getElementById('EnableStopPostfix').value);
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_EnableStopPostfixSave);  		
		}	

	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function EnableStopPostfixSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableStopPostfix", $_POST["EnableStopPostfix"]);
	
	
}
