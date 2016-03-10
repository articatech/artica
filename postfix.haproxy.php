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
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["debug_peer_list"])){debug_peer_list_add();exit;}
	if(isset($_POST["debug_peer_del"])){debug_peer_list_del();exit;}
	if(isset($_POST["EnablePostfixHaProxy"])){save();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["hostname"]}::{load_balancing_compatibility}");
	$html="YahooWin5('890','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	
	
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $users->POSTFIX_VERSION,$re)){
		$major=intval($re[1]);
		$minor=intval($re[2]);
		$binver="{$major}{$minor}";
		if($binver<210){echo error_not_compatible();die();}
		
	}

	$main=new maincf_multi($hostname);
	$EnablePostfixHaProxy=$main->GET("EnablePostfixHaProxy");
	if(!is_numeric($EnablePostfixHaProxy)){$EnablePostfixHaProxy=0;}
	
	$p=Paragraphe_switch_img("{enable_smtp_haproxy}", "{enable_smtp_haproxy_explain}",
			 "EnablePostfixHaProxy-$t", $EnablePostfixHaProxy,null,880);
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
		<td align='right'>". button("{apply}","SaveHapProxyCompliant$t()","16px")."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var X_SaveHapProxyCompliant$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		YahooWin5Hide();
		}		
	
	function SaveHapProxyCompliant$t(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('EnablePostfixHaProxy',document.getElementById('EnablePostfixHaProxy-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SaveHapProxyCompliant$t);
	}
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	$main=new maincf_multi($_POST["hostname"]);
	$main->SET_VALUE("EnablePostfixHaProxy", $_POST["EnablePostfixHaProxy"]);
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?happroxy=yes&hostname={$_POST["hostname"]}");
}


function error_not_compatible(){
	$users=new usersMenus();
	
	$tpl=new templates();
	$html="<div style='margin:30px'>
		<table style='width:99%' class=form>
		<tr>
			<td width=1%><img src='img/error-128.png'></td>
			<td><div style='font-size:16px;font-weight:bold'>Current Postfix: $users->POSTFIX_VERSION<hr>{this_feature_require_210v_minimal}</div>
		</tr>
		</table>
		
		
		";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

