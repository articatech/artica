<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["CACHE_FILE"]="/usr/share/artica-postfix/ressources/logs/zarafatrash.build.progress";
$GLOBALS["LOGS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/zarafatrash_reconfigure.txt";

if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;
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
	$restart=null;
	if($_GET["restart"]=="yes"){$restart="&restart=yes"; }
	$title=$tpl->javascript_parse_text("{REMOVE_DATABASE}");
	$t=time();
	$confirm_remove_zarafa_db=$tpl->javascript_parse_text("{confirm_remove_zarafa_db}");
	
	echo "function start$t(){
			if(!confirm('$confirm_remove_zarafa_db')){return;}
			RTMMail('800','$page?popup=yes$restart','$title');
	
	}
	start$t();";

	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	$cachefile=$GLOBALS["CACHE_FILE"];
	$logsFile=$GLOBALS["LOGS_FILE"];
	$md5file=md5_file($GLOBALS["LOGS_FILE"]);
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($cachefile));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	$restart=null;
	if($_GET["restart"]=="yes"){$restart="&restart=yes"; }
	
	if($GLOBALS["VERBOSE"]){echo "Logs file: {$GLOBALS["LOGS_FILE"]}\n";}
	if($GLOBALS["VERBOSE"]){echo "Percent file: $cachefile\n";}
	if($GLOBALS["VERBOSE"]){echo "Percent : $prc\n";}
	if($GLOBALS["VERBOSE"]){echo "MD5 file: $md5file\n";}
	if($GLOBALS["VERBOSE"]){echo "Last MD5 file: {$_GET["md5file"]}\n";}
	file_get_contents($GLOBALS["LOGS_FILE"]);
	
if($prc==0){
echo "
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}$restart');
}
setTimeout(\"Start$time()\",1000);";
return;
}


if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file$restart');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc>=100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		LayersTabsAllAfter();
		RTMMailHide();
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
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}$restart');
	}
	setTimeout(\"Start$time()\",1500);
";
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$restart=null;
	if($_GET["restart"]=="yes"){$restart="&restart=yes"; }
	$sock->getFrameWork("zarafa.php?db-trash=yes$restart");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	
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
	Loadjs('$page?build-js=yes&t=$t&md5file=0$restart');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	
	$logsFile=$GLOBALS["LOGS_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
	
}