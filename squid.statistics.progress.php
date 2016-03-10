<?php

$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics-{$_REQUEST["md5"]}.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics-{$_REQUEST["md5"]}.log";

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

	include_once('ressources/class.templates.inc');
						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$title=$tpl->javascript_parse_text("{generating_statistics}");
	$_GET["NextFunction"]=urlencode($_GET["NextFunction"]);
	echo "
	function Start$t(){	
		RTMMail('800','$page?popup=yes&md5={$_GET["zmd5"]}&NextFunction={$_GET["NextFunction"]}&tt={$_GET["t"]}','$title');
	}
	
	
	Start$t();";
	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	$NextFunction=$_REQUEST["NextFunction"];
	$NextFunction_encode=urlencode($NextFunction);
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($GLOBALS["CACHEFILE"]));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
	$NextFunction_final=base64_decode($NextFunction);
	
if($prc==0){
echo "
function Start$time(){	
	if(!RTMMailOpen()){return;}
	Loadjs('$page?build-js=yes&t=$t&md5={$_REQUEST["md5"]}&NextFunction=$NextFunction_encode&tt={$_REQUEST["tt"]}');
}
setTimeout(\"Start$time()\",1000);";
return;
}

$md5file=md5_file($GLOBALS["LOGSFILES"]);
if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file&md5={$_REQUEST["md5"]}&NextFunction=$NextFunction_encode&tt={$_REQUEST["tt"]}');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('md5', '{$_REQUEST["md5"]}');
		XHR.appendData('t', '$t');
		XHR.appendData('tt', '{$_REQUEST["tt"]}');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc==100){
	echo "
	function Start$time(){
	if(!RTMMailOpen()){return;}
	document.getElementById('title-$t').innerHTML='$title';
	$('#progress-$t').progressbar({ value: $prc });
	if(document.getElementById('graph-{$_REQUEST["tt"]}')){
		document.getElementById('graph-{$_REQUEST["tt"]}').innerHTML='<center><img src=img/loader-big.gif></center>';
	}
	
	RTMMailHide();
	$NextFunction_final
	if(document.getElementById('SQUID_STATISTICS_MEMBERS_MD5')){
		var md5=document.getElementById('SQUID_STATISTICS_MEMBERS_MD5').value;
		LoadAjaxRound('stats-members','squid.statistics.members.php?zmd5='+md5);
	}
	if(document.getElementById('POSTFIX_STATISTICS_MEMBERS_MD5')){
		var md5=document.getElementById('POSTFIX_STATISTICS_MEMBERS_MD5').value;
		LoadAjaxRound('messaging-stats-members','postfix.statistics.members.php?zmd5='+md5);
	}
	
	
	
}
setTimeout(\"Start$time()\",1000);
";
	return;
}

if($prc>100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		document.getElementById('title-$t').style.border='1px solid #C60000';
		document.getElementById('title-$t').style.color='#C60000';
		$('#progress-$t').progressbar({ value: $prc });
		}
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		Loadjs('$page?build-js=yes&t=$t&md5={$_REQUEST["md5"]}&NextFunction=$NextFunction_encode&tt={$_REQUEST["tt"]}');
	}
	setTimeout(\"Start$time()\",1500);
";




//Loadjs('$page?build-js=yes&t=$t&md5={$_GET["md5"]}');
		
	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-110-report={$_REQUEST["md5"]}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	$NextFunction=$_REQUEST["NextFunction"];
	$NextFunction_encode=urlencode($NextFunction);
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&md5={$_REQUEST["md5"]}&NextFunction=$NextFunction_encode&tt={$_REQUEST["tt"]}');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	$t=explode("\n",@file_get_contents($GLOBALS["LOGSFILES"]));
	krsort($t);
	echo @implode("\n", $t);
	
}