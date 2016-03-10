<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("php5-pgsql");
	
	echo "
	YahooWinBrowseHide();
	RTMMail('800','$page?popup=yes','$title');";
	
}


function popup(){
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body("{please_wait}");
	$t=time();
	
	$sock=new sockets();
	$sock->getFrameWork("postgres.php?php5-pgsql=yes");
	
	$html="
	<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
	<div id='progress-$t' style='height:50px'></div>
	
	<center style='font-size:150px;margin:30px;padding:15px' id='center-$t'></center>
<script>
var CompteArebourgWait=1;
	
function Step1$t(){
	if(!RTMMailOpen()){return;}
	CompteArebourgWait=CompteArebourgWait+1;
	var CompteArebourgText=100-CompteArebourgWait;
	if(CompteArebourgText<1){
		document.location.href='logoff.php';
		return;
	}
	$('#progress-$t').progressbar({ value: CompteArebourgWait });
	document.getElementById('center-$t').innerHTML=CompteArebourgText;
	setTimeout(\"Step1$t()\",800);
}

$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",500);	
	
	</script>
	";
	echo $html;	
	
}