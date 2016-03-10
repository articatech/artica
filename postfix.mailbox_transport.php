<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["mailbox_transport"])){save_transport();exit;}
	
	
js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["hostname"]}::{mailbox_agent}");
	$html="YahooWin5('890','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$main=new maincf_multi($_GET["hostname"]);
	$t=time();
	$html="
	<div id='$t'></div>
	<div class=explain style='font-size:18px'>{postfix_mailbox_transport_art_expl}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:22px'>{agent_address}:</td>
		<td>". Field_text("mailbox_transport-$t",$main->GET("mailbox_transport"),"font-size:22px;width:590px",null,null,null,false,"SaveMailBoxTransportCK$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveMailBoxTransport$t()","30px")."</td>
	</tr>
	</table>
	<script>
		var X_SaveMailBoxTransport$t= function (obj) {
				var results=obj.responseText;
				if (results.length>0){alert(results);}
				document.getElementById('$t').innerHTML='';
			
			}		
			
			
		function SaveMailBoxTransportCK$t(e){
			if(checkEnter(e)){SaveMailBoxTransport$t();}
		}
		
		function SaveMailBoxTransport$t(){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('mailbox_transport',document.getElementById('mailbox_transport-$t').value);
				AnimateDiv('$t');
				XHR.sendAndLoad('$page', 'POST',X_SaveMailBoxTransport$t);
				
			}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function save_transport(){
	$main=new maincf_multi($_POST["hostname"]);
	$main->SET_VALUE("mailbox_transport", trim(strtolower($_POST["mailbox_transport"])));
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?mailbox-transport=yes&hostname={$_POST["hostname"]}");
	
}
