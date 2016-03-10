<?php

$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/refresh.index.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/refresh.index.txt";
$GLOBALS["MAIN_TITLE"]="{refresh_index}";
$GLOBALS["FRAMEWORK_COMMAND"]="system.php?refresh-index-ini=yes";

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
	$tpl=new templates();
	$tarballs_file="/usr/share/artica-postfix/ressources/logs/web/tarballs.cache";
	$Content=@file_get_contents($tarballs_file);
	$strlen=strlen($Content);
	$package=null;
	if(preg_match("#<PACKAGES>(.*?)</PACKAGES>#", $Content,$re)){$MAIN=unserialize(base64_decode($re[1])); }
	$t=time();
	$ligne=$MAIN[$_GET["filename"]];
	
	
	
	$PACKAGES["squid32"]="APP_SQUID";
	$PACKAGES["sambac"]="APP_SAMBA";
	$PACKAGES["ntopng"]="APP_NTOPNG";
	
	if(isset($PACKAGES[$ligne["package"]])){
		$package=$tpl->javascript_parse_text("{{$PACKAGES[$ligne["package"]]}}");
	}
	
	header("content-type: application/x-javascript");
	$install=$tpl->javascript_parse_text("{install}");
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text($package);
	$filename=urlencode($_GET["filename"]);
	
	echo "
	function Launch$t(){	
		var package='$package';
		if(package.length>1){
			if(!confirm('$install $package {$_GET["filename"]}')){return;}
		}
		RTMMail('800','$page?popup=yes&filename=$filename','$title:{$_GET["filename"]}');
	}
	YahooWinBrowseHide();
	Launch$t();";
	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;

	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	
	$filename=urlencode($_GET["filename"]);
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/{$_GET["filename"]}-progress.txt";
	$cachefile="/usr/share/artica-postfix/ressources/logs/{$_GET["filename"]}.progress";
	$download_progress=intval(@file_get_contents("/usr/share/artica-postfix/ressources/logs/{$_GET["filename"]}.download.progress"));
	$array=unserialize(@file_get_contents($cachefile));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
	
if($prc==0){
echo "
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&filename=$filename');
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
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file&filename=$filename');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		$('#progress2-$t').progressbar({ value: $download_progress });
		
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('filename', '{$_GET["filename"]}');
		XHR.appendData('t', '$t');
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
		$('#progress2-$t').progressbar({ value: $download_progress });
		LayersTabsAllAfter();
		if(document.getElementById('main_tab_logiciels')){
			$('#'+document.getElementById('main_tab_logiciels').value).flexReload();
		}
		RTMMailHide();
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
	$('#progress2-$t').progressbar({ value: 0 });
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
		$('#progress2-$t').progressbar({ value: $download_progress });
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}&filename=$filename');
	}
	setTimeout(\"Start$time()\",1500);
";




//Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
		
	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$filename=urlencode($_GET["filename"]);
	$sock->getFrameWork("system.php?installv2=yes&filename=$filename");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}: {$_GET["filename"]}...");
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<br>
<div id='progress2-$t' style='height:20px'></div>

<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&filename=$filename');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/{$_POST["filename"]}-progress.txt";
	$t=explode("\n",@file_get_contents($GLOBALS["LOGSFILES"]));
	krsort($t);
	echo @implode("\n", $t);
	
}