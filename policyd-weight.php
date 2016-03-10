<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.policyd-weight.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsPostfixAdministrator==false){die();}
	if(isset($_GET["popup-index"])){popup_index();exit;}
	if(isset($_GET["popup-daemon"])){popup_daemon();exit;}
	if(isset($_GET["popup-notifs"])){popup_notifs();exit;}
	if(isset($_GET["popup-dnsbl"])){popup_dnsbl();exit;}
	if(isset($_GET["MAX_PROC"])){SaveSettings();exit;}
	if(isset($_GET["MAXDNSBLHITS"])){SaveSettings();exit;}
	if(isset($_GET["REJECTMSG"])){SaveSettingsFormatted();exit;}
	if(isset($_GET["list-dnsbl"])){echo dnsbl_list();exit;}
	if(isset($_GET["DNSBL"])){SaveDNSBL();exit;}
	if(isset($_GET["RHSBL"])){SaveRHSBL();exit;}
	if(isset($_GET["DEL_DNSBL"])){DelDNSBL();exit;}
	if(isset($_GET["DEL_RHSBL"])){DelRHSBL();exit;}
	if(isset($_GET["EnablePolicydWeight"])){EnablePolicydWeight();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	
	
	
	js();


function js(){
$page=CurrentPageName();
$prefix=str_replace('.','_',$page);
$prefix=str_replace('-','_',$prefix);
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{APP_POLICYD_WEIGHT}');

$html="
function {$prefix}Loadpage(){
	YahooWin2('850','$page?popup-index=yes','$title');
	//setTimeout('{$prefix}DisplayDivs()',900);
	}
	
function PolicydDaemonSettings(){
	YahooWin3('650','$page?popup-daemon=yes','$title');
}

function PolicydDaemonNotifs(){
	YahooWin3('650','$page?popup-notifs=yes','$title');
}

function PolicydDaemonDNSBL(){
	YahooWin3('650','$page?popup-dnsbl=yes','$title');
}



var x_ffmpolicy1= function (obj) {
	var results=obj.responseText;
	alert(results);
	PolicydDaemonSettings();
	}





	






	





function {$prefix}DisplayDivs(){
		LoadAjax('main_config_postfix','$page?main={$_GET["main"]}&hostname=$hostname')
		{$prefix}demarre();
		{$prefix}ChargeLogs();
		{$prefix}StatusBar();
	}	
	
 {$prefix}Loadpage();
";
	
	echo $html;
}	

function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["popup-index"]="{status}";
	$array["popup-daemon"]="{daemon_settings}";
	$array["popup-notifs"]="{notifications}";
	$array["popup-dnsbl"]="{DNSBL_settings}";
	
	
	
	
	$fontsize=24;
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_policydaemon",1490)."<script>LeftDesign('management-console-256.png');</script>";
	
	
	
	
}



function popup_index(){
	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePolicydWeight=intval($sock->GET_INFO('EnablePolicydWeight'));
	$EnablePolicydWeight_field=Paragraphe_switch_img('{EnablePolicydWeight}',
			'{APP_POLICYD_WEIGHT_TEXT}<br>{APP_POLICYD_WEIGHT_EXPLAIN}','EnablePolicydWeight',
			$EnablePolicydWeight,"{enable_disable}",1450);
	
	

	
	
	$html="<div style='font-size:30px;margin-bottom:50px'>{APP_POLICYD_WEIGHT}</div>
	<div style='width:98%' class=form>
	$EnablePolicydWeight_field
	<div style='margin-top:20px;text-align:right'>". button("{apply}","EnablePolicydWeight()",40)."</div>
	<script>
var X_EnablePolicydWeight=function (obj) {
		Loadjs('postfix.clients_restrictions.progress.php');
	}	
function EnablePolicydWeight(){
	var XHR = new XHRConnection();
	XHR.appendData('EnablePolicydWeight',document.getElementById('EnablePolicydWeight').value);
	XHR.sendAndLoad('$page', 'GET',X_EnablePolicydWeight);
	}
</script>
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function popup_daemon(){
	
$policy=new policydweight();
$page=CurrentPageName();
$html="
<div id='ffmpolicy1Div' style='width:98%' class=form>
<form name='ffmpolicy1'>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:22px'>{MAX_PROC}:</td>
	<td>" . Field_text('MAX_PROC',$policy->main_array["MAX_PROC"],'width:110px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{MIN_PROC}:</td>
	<td>" . Field_text('MIN_PROC',$policy->main_array["MIN_PROC"],'width:110px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SOMAXCONN}:</td>
	<td>" . Field_text('SOMAXCONN',$policy->main_array["SOMAXCONN"],'width:110px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{MAXIDLECACHE}:</td>
	<td>" . Field_text('MAXIDLECACHE',$policy->main_array["MAXIDLECACHE"],'width:110px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{MAINTENANCE_LEVEL}:</td>
	<td>" . Field_text('MAINTENANCE_LEVEL',$policy->main_array["MAINTENANCE_LEVEL"],'width:110px;font-size:22px')."</td>
</tr>
<tr>
	<td colspan=2 align='right'>
		<hr>". button("{apply}","ParseForm('ffmpolicy1','$page',false,false,false,'ffmpolicy1Div',null,x_ffmpolicy1)",42)."		
	</td>
</tr>
</table>
</div>
<script>
var x_ffmpolicy1= function (obj) {
	var results=obj.responseText;
	alert(results);
	RefreshTab('main_policydaemon');
	}
</script>
";

	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}

function popup_notifs(){
$policy=new policydweight();
$page=CurrentPageName();
$html="
<div id='ffmpolicy2Div' style='width:98%' class=form>
<form name='ffmpolicy2'>
<table style='width:100%'>

<tr>
	<td class=legend style='font-size:22px'>{REJECTMSG}:</td>
	<td>" . Field_text('REJECTMSG',$policy->main_array["REJECTMSG"],'width:1060px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{DNSERRMSG}:</td>
	<td>" . Field_text('DNSERRMSG',$policy->main_array["DNSERRMSG"],'width:1060px;font-size:22px')."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{MAXDNSERRMSG}:</td>
	<td>" . Field_text('MAXDNSERRMSG',$policy->main_array["MAXDNSERRMSG"],'width:1060px;font-size:22px')."</td>
</tr>
	<td class=legend style='font-size:22px'>{MAXDNSBLMSG}:</td>
	<td>" . Field_text('MAXDNSBLMSG',$policy->main_array["MAXDNSBLMSG"],'width:1060px;font-size:22px')."</td>
</tr>



<tr>
	<td colspan=2 align='right'>
		<hr>
		".button("{apply}","ParseForm('ffmpolicy2','$page',false,false,false,'ffmpolicy2Div',null,x_ffmpolicy2);",42)."
		
	</td>
</tr>
</table>
</div>
<script>
	
var x_ffmpolicy2= function (obj) {
	var results=obj.responseText;
	alert(results);
	RefreshTab('main_policydaemon');
	}
</script>
";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}


function SaveSettings(){
	$policy=new policydweight();
	while (list ($num, $ligne) = each ($_GET) ){
		$policy->main_array[$num]=$ligne;
	}
	
	$policy->SaveConf();
	
}

function SaveSettingsFormatted(){
	$policy=new policydweight();
	while (list ($num, $ligne) = each ($_GET) ){
		$ligne=str_replace('"','',$ligne);
		$ligne=str_replace("'",'',$ligne);
		$ligne=addslashes($ligne);
		$policy->main_array[$num]=$ligne;
	}
	
	$policy->SaveConf();	
}

function popup_dnsbl(){
	$policy=new policydweight();
	$page=CurrentPageName();
$html="
<div id='ffmpolicy2Div' style='width:98%' class=form>
	<form name='ffmpolicy2'>
		<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:22px'>{MAXDNSBLHITS}:</td>
				<td>". Field_text("MAXDNSBLHITS",$policy->main_array["MAXDNSBLHITS"],"width:110px;font-size:22px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{MAXDNSBLSCORE}:</td>
				<td>". Field_text("MAXDNSBLSCORE",$policy->main_array["MAXDNSBLSCORE"],"width:110px;font-size:22px")."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'>
					<hr>
					". button("{apply}","ParseForm('ffmpolicy2','$page',false,false,false,'ffmpolicy2Div',null,x_ffmpolicy3);",42)."
					
				</td>
			</tr>
			</table>
	</form>
</div>
							
<div id='dnsbllistpolicd' style='width:98%;height:850px;overflow:auto' classs=form>".dnsbl_list()."</div>
<script>
var x_ffmpolicy3= function (obj) {
	var results=obj.responseText;
	alert(results);
	RefreshTab('main_policydaemon');
	}
</script>
";	

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);		
}

function dnsbl_list(){
	$policy=new policydweight();
	$page=CurrentPageName();
	
	$data=file_get_contents("ressources/dnsrbl.db");
	$tr=explode("\n",$data);
	while (list ($num, $val) = each ($tr) ){
		if(preg_match("#RBL:(.+)#",$val,$re)){
			$RBL[$re[1]]=$re[1];
		}
	if(preg_match("#RHSBL:(.+)#",$val,$re)){
			$RHSBL[$re[1]]=$re[1];
		}		
	}
	
	$list=Field_array_Hash($RBL,"iii_DNSBL",null,"style:font-size:22px");
	$listRHS=Field_array_Hash($RHSBL,"iiy_RHSBL",null,"style:font-size:22px");
	

	$html="
	
	<table style='width:99%' class=form>
	<tr>
	
	<th colspan=2 style='font-size:18px'>DNSBL</th>
	<th style='font-size:18px'>{BAD_SCORE}</th>
	<th style='font-size:18px'>{GOOD_SCORE}</th>
	<th style='font-size:18px'>{log}</th>
	<th style='font-size:18px'>&nbsp;</th>
	</tr>
	";
	while (list ($num, $val) = each ($policy->dnsbl_array) ){
		$md5=md5($num);
		$html=$html ."
		<tr>
			<td width=1%><img src='img/arrow-blue-left-32.png'></td>
			<td><strong style='font-size:22px'>$num</strong></td>
			<td><input type='hidden' id='{$md5}_DNSBL' value='$num'>". Field_text("{$md5}_HIT",$val["HIT"],'width:110px;font-size:22px',null,null,null,false,"PolicyDSaveDnsbl('$md5',event)")."</td>
			<td>". Field_text("{$md5}_MISS",$val["MISS"],'width:110px;font-size:22px',null,null,null,false,"PolicyDSaveDnsbl('$md5',event)")."</td>
			<td><strong style='font-size:18px'>{$val["LOG"]}</td>
			<td width=1%>". imgtootltip('delete-32.png',"{delete}","PolicyDDelDnsbl('$md5')")."</td>
		</tr>
		";
		}
	
	$html=$html . "
	<tr>
		<td width=1%>&nbsp;</td>
		<td colspan=5><hr>$list
		<input type='hidden' id='iii_HIT' value='4.35'>
		<input type='hidden' id='iii_MISS' value='0'>
		". button("{add}","PolicyDSaveDnsbl('iii',13);",22)."</td>
	</tr>		
		
	</table>
	<br><table style='width:99%' class=form>
	<tr>
	
	<th colspan=2 style='font-size:18px'>RHSBL</th>
	<th style='font-size:18px'>{BAD_SCORE}</th>
	<th style='font-size:18px'>{GOOD_SCORE}</th>
	<th style='font-size:18px'>{log}</th>
	<th style='font-size:18px'>&nbsp;</th>
	</tr>	
	";
	
while (list ($num, $val) = each ($policy->rhsbl_array) ){
		$md5=md5($num);
		$html=$html ."
		<tr>
			<td width=1%><img src='img/arrow-blue-left-32.png'></td>
			<td><strong style='font-size:22px'>$num</strong></td>
			<td><input type='hidden' id='{$md5}_RHSBL' value='$num'>". Field_text("{$md5}_HIT",$val["HIT"],'width:110px;font-size:22px',null,null,null,false,"PolicyDSaveRHSBL('$md5',event)")."</td>
			<td>". Field_text("{$md5}_MISS",$val["MISS"],'width:110px;font-size:22px',null,null,null,false,"PolicyDSaveRHSBL('$md5',event)")."</td>
			<td><strong style='font-size:22px'>{$val["LOG"]}</td>
			<td width=1%>". imgtootltip('delete-32.png',"{delete}","PolicyDDelRHSBL('$md5')")."</td>
		</tr>
		";
		}
	
	$html=$html . "
	<tr>
		<td width=1%>&nbsp;</td>
		<td colspan=5 algin='right'><hr>$listRHS
		<input type='hidden' id='iiy_HIT' value='4.35'>
		<input type='hidden' id='iiy_MISS' value='0'>
		 ". button("{add}","PolicyDSaveRHSBL('iiy',13);",22)."</td>
					 
	</tr>		
		
	</table>
<script>

var X_PolicyDSaveDnsbl= function (obj) {
	var results=obj.responseText;
	LoadAjax('dnsbllistpolicd','$page?list-dnsbl=yes');
	}	

function PolicyDSaveDnsbl(md,e){
	var r=false;
	if(e==13){r=true;}
	if(!r){
		if(checkEnter(e)){r=true;}
	}

	if(r){
		 var DNSBL=document.getElementById(md+'_DNSBL').value;
		 var HIT=document.getElementById(md+'_HIT').value;
		 var MISS=document.getElementById(md+'_MISS').value;
		 var XHR = new XHRConnection();
		 XHR.appendData('DNSBL',DNSBL);
		 XHR.appendData('HIT',HIT);
		 XHR.appendData('MISS',MISS);
		 XHR.sendAndLoad('$page', 'GET',X_PolicyDSaveDnsbl);
	}		

}

function PolicyDSaveRHSBL(md,e){
	var r=false;
	if(e==13){r=true;}
	if(!r){
		if(checkEnter(e)){r=true;}
	}

	if(r){
		 var DNSBL=document.getElementById(md+'_RHSBL').value;
		 var HIT=document.getElementById(md+'_HIT').value;
		 var MISS=document.getElementById(md+'_MISS').value;
		 var XHR = new XHRConnection();
		 XHR.appendData('RHSBL',DNSBL);
		 XHR.appendData('HIT',HIT);
		 XHR.appendData('MISS',MISS);
		 XHR.sendAndLoad('$page', 'GET',X_PolicyDSaveDnsbl);
	}		

}		 		
		 		
function PolicyDDelDnsbl(md){
		 var DNSBL=document.getElementById(md+'_DNSBL').value;
		 var XHR = new XHRConnection();
		 XHR.appendData('DEL_DNSBL',DNSBL);
		 
		 XHR.sendAndLoad('$page', 'GET',X_PolicyDSaveDnsbl);
}
function PolicyDDelRHSBL(md){
		 var DNSBL=document.getElementById(md+'_RHSBL').value;
		 var XHR = new XHRConnection();
		 XHR.appendData('DEL_RHSBL',DNSBL);
		 
		 XHR.sendAndLoad('$page', 'GET',X_PolicyDSaveDnsbl);
}
</script>		 		
		 		";	
	
$tpl=new templates();
return  $tpl->_ENGINE_parse_body($html);
}

function SaveDNSBL(){
	$policy=new policydweight();
	$kg=$_GET["DNSBL"];
	$kg=str_replace(".","_",$kg);
	$kg=strtoupper($kg);
	$policy->dnsbl_array[$_GET["DNSBL"]]=array("HIT"=>$_GET["HIT"],"MISS"=>$_GET["MISS"],"LOG"=>$kg);
	$policy->SaveConf();
	
}

function SaveRHSBL(){
	$policy=new policydweight();
	$kg=$_GET["RHSBL"];
	$kg=str_replace(".","_",$kg);
	$kg=strtoupper($kg);
	$policy->rhsbl_array[$_GET["RHSBL"]]=array("HIT"=>$_GET["HIT"],"MISS"=>$_GET["MISS"],"LOG"=>$kg);
	$policy->SaveConf();	
}

function DelDNSBL(){
	$policy=new policydweight();
	unset($policy->dnsbl_array[$_GET["DEL_DNSBL"]]);
	$policy->SaveConf();
}
function DelRHSBL(){
	$policy=new policydweight();
	unset($policy->rhsbl_array[$_GET["DEL_RHSBL"]]);
	$policy->SaveConf();	
}

function EnablePolicydWeight(){
	$sock=new sockets();
	$sock->SET_INFO('EnablePolicydWeight',$_GET["EnablePolicydWeight"]);
	
	
}




?>