<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["step0"])){step0();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["step3"])){step3();exit;}
	if(isset($_POST["www_cnx"])){save_mem();exit;}
	if(isset($_POST["www_dest"])){save_mem();exit;}
	if(isset($_POST["agree"])){agree();exit;}

js();

function suffix(){
	
	return "&peer-id={$_REQUEST["peer-id"]}";
}


function js(){
	$suffix=suffix();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_server}");
	
	if(intval($_REQUEST["peer-id"])>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port,servername FROM reverse_sources WHERE ID='{$_GET["peer-id"]}'"));
		$servername=$ligne["servername"];
		$ipaddr=$ligne["ipaddr"];
		$port=$ligne["port"];
		$title="$servername:: $ipaddr:$port";
	}
	
	$html="YahooWin(800,'$page?step0=yes$suffix','$title')";
	echo $html;	
}
function step0(){
	$suffix=suffix();
	$page=CurrentPageName();
	echo "<div id='NGINX_NEW_WIZ'></div>
	<script>
		LoadAjax('NGINX_NEW_WIZ','$page?step1=yes$suffix');
	</script>		
	";
	
}

function step1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$suffix=suffix();
	if($_SESSION["NGINX"]["www_cnx"]==null){$_SESSION["NGINX"]["www_cnx"]="http://";}
	$t=time();
	$html="<div style='width:98%' class=form>
	<div class=explain style='font-size:18px'>{nginx_welcome_step1}</div>
	<center style='margin-top:20px'>". Field_text("www_cnx-$t",$_SESSION["NGINX"]["www_cnx"],"font-size:22px;font-weight:bold;padding:20px;width:95%",null,null,null,false,"SaveCheck$t(event)")."
	
	<div style='margin-top:15px'>". Paragraphe_switch_img("Microsoft Windows Exchange", 
	"{nginx_msexch_dedicated_explain}","MSEXCH-$t",$_SESSION["NGINX"]["MSEXCH"],null,775)."</div>
			
			
	<div style='margin-top:20px'>". button("{next}","Save$t()",30)."</div>		
	</center>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		LoadAjax('NGINX_NEW_WIZ','$page?step2=yes$suffix');
	}	

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('www_cnx',document.getElementById('www_cnx-$t').value);
		XHR.appendData('MSEXCH',document.getElementById('MSEXCH-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);		
	
	}
	
	function SaveCheck$t(e){
		if(!checkEnter(e)){return;}
		Save$t();
	}
	
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function step2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$DefaultDest=$_SESSION["NGINX"]["www_dest"];
	$SourceDest=$_SESSION["NGINX"]["www_cnx"];
	if($DefaultDest==null){if($_SESSION["NGINX"]["MSEXCH"]==1){$DefaultDest="https://";}}
	if($DefaultDest==null){$DefaultDest	="http://";}
	
	$nginx_welcome_step2=$tpl->_ENGINE_parse_body("{nginx_welcome_step2}");
	
	if($_SESSION["NGINX"]["MSEXCH"]==1){
		$SourceDest=$SourceDest." (Microsoft Exchange Server)";
		
	}
	
	$nginx_welcome_step2=str_replace("%s", $SourceDest, $nginx_welcome_step2);
	
	if($_GET["peer-id"]>0){
		$usffix=suffix();
		$_SESSION["NGINX"]["PEER_ID"]=$_GET["peer-id"];
		echo "<script>LoadAjax('NGINX_NEW_WIZ','$page?step3=yes$usffix');</script>";
	}
	
	
	$html="
	<div style='width:98%' class=form>
	<div style='font-size:24px'>{reverse_proxy}:$SourceDest</div>
	<div class=explain style='font-size:18px'>$nginx_welcome_step2</div>
	<center style='margin-top:20px'>". Field_text("www_dest-$t",$DefaultDest,"font-size:22px;font-weight:bold;padding:20px;width:95%",null,null,null,false,"SaveCheck$t(event)")."
	<div style='margin-top:20px'>". button("{next}","Save$t()",30)."</div>
	</center>
	<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	LoadAjax('NGINX_NEW_WIZ','$page?step3=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('www_dest',document.getElementById('www_dest-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
	function SaveCheck$t(e){
		if(!checkEnter(e)){return;}
		Save$t();
	}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);


}
function step3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$DefaultDest=$_SESSION["NGINX"]["www_dest"];
	$SourceDest=$_SESSION["NGINX"]["www_cnx"];
	
	if($_SESSION["NGINX"]["MSEXCH"]==1){
		$DefaultDest=$DefaultDest." (Microsoft Exchange Server)";
	
	}

	$nginx_welcome_step3=$tpl->_ENGINE_parse_body("{nginx_welcome_step3}");
	$nginx_welcome_step3=str_replace("%s", $SourceDest, $nginx_welcome_step3);
	$nginx_welcome_step3=str_replace("%d",$DefaultDest, $nginx_welcome_step3);
	
	if($_GET["peer-id"]>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port FROM reverse_sources WHERE ID='{$_GET["peer-id"]}'"));
		$name="{$ligne["ipaddr"]}:{$ligne["port"]} ({$ligne["servername"]})";
		$nginx_welcome_step3=$tpl->_ENGINE_parse_body("<strong style='font-size:22px'>{destination}:$name</strong><hr>").str_replace("%d", $name, $nginx_welcome_step3);
		$xhr3="XHR.appendData('peer-id','{$_GET["peer-id"]}');";
	}
	
	$html="
	<div style='width:98%' class=form>
	<div class=explain style='font-size:18px'>$nginx_welcome_step3</div>
	<center>
	<div style='margin-top:20px'>". button("{build_settings}","Save$t()",30)."</div>
	</center>
	<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	Loadjs('nginx.new.progress.php');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('agree','yes');
	$xhr3
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}

</script>
";

echo $tpl->_ENGINE_parse_body($html);

}

function agree(){
	while (list ($num, $ligne) = each ($_SESSION["NGINX"]) ){
		$array[$num]=$ligne;
	}
	while (list ($num, $ligne) = each ($_POST) ){
		$array[$num]=$ligne;
	}	
	
	
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($array), "NginxWizard");
	
	
}

function save_mem(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_SESSION["NGINX"][$num]=$ligne;
	}
	
	
}
