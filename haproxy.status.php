<?php
	if(posix_getuid()==0){die();}
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.haproxy.inc');
	
	
	
	
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["EnableHaProxy"])){Save();exit;}
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnableHaProxy=intval($sock->GET_INFO("EnableHaProxy"));
	
	if($EnableHaProxy==1){
		$q=new mysql();
		$sql="SELECT count(*) as tcount FROM haproxy WHERE enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(intval($ligne["tcount"])==0){
			$ERR="<p class=text-error style='font-size:22px'>{HAPROXY_NOBACKENDS_DEFINED}</p>";
			
		}
	}
	
	
	$DenyHaproxyConf=intval($sock->GET_INFO("DenyHaproxyConf"));
	$t=time();
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:30%'><div id='haproxy-status'></div></td>
		<td style='vertical-align:top;width:70%;padding-left:15px'>
			<div style='font-size:40px;margin-bottom:40px'>{load_balancing}</div>
			$ERR
			<div style='width:98%' class=form>
			". Paragraphe_switch_img("{EnableHaProxy}", "{EnableHaProxy_text}","EnableHaProxy-$t",$EnableHaProxy,null,1010)."<br>
			". Paragraphe_switch_img("{DenyHaproxyConf}", "{DenyHaproxyConf_text}","DenyHaproxyConf-$t",$DenyHaproxyConf,null,1010)."<br>
			
			<div style='width:100%;text-align:right'><hr>". button("{apply}","Save$t()",44)."</div>
			</div>
			</td>
	</tr>
	</table>

	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		UnlockPage();
		Loadjs('haproxy.progress.php');
		
	}
	function Save$t(){
		LockPage();
		var XHR = new XHRConnection();
		XHR.appendData('EnableHaProxy',document.getElementById('EnableHaProxy-$t').value);
		XHR.appendData('DenyHaproxyConf',document.getElementById('DenyHaproxyConf-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		}		
		LoadAjax('haproxy-status','haproxy.php?haproxy-status-popup-content=yes&bigsize=yes');	
	</script>
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableHaProxy", $_POST["EnableHaProxy"]);
	$sock->SET_INFO("DenyHaproxyConf", $_POST["DenyHaproxyConf"]);
	$sock->getFrameWork("haproxy.php?service-cmds=restart");
}
