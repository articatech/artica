<?php
session_start();

if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.wifidog.settings.inc");

$users=new usersMenus();
if(!$users->AsHotSpotManager){die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["sessions"])){sessions();exit;}
if(isset($_GET["sessions-items"])){sessions_items();exit;}
if(isset($_POST["delete-session"])){sessions_remove();exit;}
if(isset($_POST["DeleteAllSessions"])){sessions_all_remove();exit;}

if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-items"])){members_items();exit;}
if(isset($_POST["EnableMember"])){members_enable();exit;}
if(isset($_POST["DeleteMember"])){members_delete();exit;}
if(isset($_GET["uid"])){member_popup();exit;}
if(isset($_POST["uid"])){members_save();exit;}
if(isset($_GET["ttl"])){members_ttl();exit;}
if(isset($_POST["uid-ttl"])){members_ttl_save();exit;}
if(isset($_GET["mysql-settings"])){mysql_settings();exit;}
if(isset($_GET["ttl-js"])){ttl_js();exit;}

if(isset($_GET["delete-session-js"])){delete_session_js();exit;}
if(isset($_POST["HotSpotAutoRegisterWebMail"])){mysql_settings_save();exit;}
if(isset($_GET["export-members"])){export_members();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function ttl_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$uid=urlencode($_GET["ttl-js"]);
	echo "YahooWin3('750','$page?ttl=$uid&t={$_GET["t"]}','{$_GET["ttl-js"]}');";
	
}

function delete_session_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$md5=$_GET["delete-session-js"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE md5='$md5'"));
	$t=$_GET["t"];
	$tt=time();
	$pattern=$tpl->javascript_parse_text("{delete} {session}: {$ligne["uid"]} - {$ligne["MAC"]}/{$ligne["ipaddr"]} ?");
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
	
	
function Save$tt(){
	var XHR = new XHRConnection();
	if(!confirm('$pattern')){return;}
	XHR.appendData('delete-session',  '$md5');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
	
	Save$tt();
	";
	echo $html;	
	
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{hostpot_members}</H1>
		<p>{hostpot_members_text}</p>
	</div>	
	<div id='backuped-tabs-$t'></div>
	
	<script>
		LoadAjax('backuped-tabs-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(isset($_GET["title"])){
		$title=$tpl->_ENGINE_parse_body("<H3>{hostpot_members}</H3><p>{hostpot_members_text}</p>");
	}
	$boot=new boostrap_form();
	$array["{sessions}"]="$page?sessions=yes";
	$array["{members}"]="$page?members=yes";
	echo $title.$boot->build_tab($array);
	
}

function members(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$members=$tpl->javascript_parse_text("{members}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ttl=$tpl->javascript_parse_text("{ttl}");
	$finaltime=$tpl->javascript_parse_text("{re_authenticate_each}");
	$endtime=$tpl->javascript_parse_text("{endtime}");
	$title=$tpl->javascript_parse_text("{members}");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`=''");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$new_account=$tpl->javascript_parse_text("{new_account}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$created=$tpl->javascript_parse_text("{created}");
	$smtp=$tpl->_ENGINE_parse_body("{self_register}");
	$import=$tpl->javascript_parse_text("{import}");
	$export=$tpl->javascript_parse_text("{export}");
	$disable_account_in=$tpl->javascript_parse_text("{disable_account_in}");
	$button_parameters="{name: '<strong style=font-size:18px>$parameters</strong>', bclass: 'Settings', onpress : Settings$t},";
	$button_parameters=null;
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_account</strong>', bclass: 'Add', onpress : NewAccount$t},
	{name: '<strong style=font-size:18px;>$import</strong>', bclass: 'Settings', onpress : Import$t},
	{name: '<strong style=font-size:18px;>$export</strong>', bclass: 'xls', onpress : Export$t},
	$button_parameters
	],";	
	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$created</span>', name : 'creationtime', width :306, sortable : true, align: 'left'},	
		{display: '<span style=font-size:22px>$members</span>', name : 'uid', width :405, sortable : true, align: 'left'},	
		{display: '<span style=font-size:22px>$ttl</span>', name : 'ttl', width :190, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$disable_account_in</span>', name : 'sessiontime', width :190, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$enabled</span>', name : 'enabled', width :60, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none', width :80, sortable : true, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$members', name : 'uid'},
		{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '<span id=\"title-$t\" style=\"font-size:30px\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	var x_DeleteSession$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
		ExecuteByClassName('SearchFunction');
		
	}

function DeleteSession$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function Export$t(){
	YahooWin4('520','$page?export-members=yes&t=$t','$export');
}

function Settings$t(){
	YahooWin4('990','$page?mysql-settings=yes&t=$t','$parameters');
}

function AutoLogin$t(){
	Loadjs('squid.webauth.smtp.php');
}

function NewAccount$t(){
	YahooWin4('750','$page?uid=&t=$t','$new_account');
}

var x_MemberEnable$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}

function Import$t(){
	Loadjs('squid.webauth.members.import.php?t=$t');

}

function MemberEnable$t(uid){
	var enabled=0;
	if(document.getElementById('enable_'+uid).checked){enabled=1;}
	var XHR = new XHRConnection();
	XHR.appendData('EnableMember',uid);
	XHR.appendData('value',enabled);		
	XHR.sendAndLoad('$page', 'POST',x_MemberEnable$t);	
	
}

var x_DeleteMember$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row'+mem$t).remove();
	ExecuteByClassName('SearchFunction');
}

function DeleteMember$t(uid,md){
	if(!confirm('$delete '+uid+' ?')){return;}
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteMember',uid);
	XHR.sendAndLoad('$page', 'POST',x_DeleteMember$t);		
}

</script>";
	
	echo $html;	
}

function members_ttl(){
	$tt=$_GET["t"];
	$uid=$_GET["ttl"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";		
	$Timez[80640]="2 {months}";	
	$Timez[161280]="4 {months}";
	$Timez[241920]="6 {months}";
	
	$bttext="{extend_account}";
	$q=new mysql_squid_builder();
	$sql="SELECT creationtime,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$html="
	<div id='$t-animate' style='width:98%' class=form>
	<table style='width:99%'>
		<tr>
		<td class=legend style='font-size:24px'>{disable_account_in}:</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"ttl-$t",$ligne["ttl"],null,null,0,"font-size:24px")."</td>	</tr>			
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("$bttext","SaveTTLHotspot()","42px")."</td>
	</tr>
	</table>
</div>
	<script>
	var x_SaveTTLHotspot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ExecuteByClassName('SearchFunction');
		YahooWin4Hide();
		$('#flexRT$tt').flexReload();

	}


function SaveTTLHotspot(){
		var XHR = new XHRConnection();
		if(document.getElementById('ttl-$t')){XHR.appendData('ttl',document.getElementById('ttl-$t').value);}
		XHR.appendData('uid-ttl','$uid');			
		XHR.sendAndLoad('$page', 'POST',x_SaveTTLHotspot$t);
	}
	
	
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function export_members(){
	$q=new mysql_squid_builder();
	$filename=dirname(__FILE__)."/ressources/conf/upload/hotspot_members.csv";
	@chmod($filename=dirname(__FILE__)."/ressources/conf/upload", 0777);
	
	if(is_file($filename)){@unlink($filename);}
	$sql="SELECT * FROM hotspot_members 
	INTO OUTFILE '$filename' FIELDS ENCLOSED BY '\\\"' TERMINATED BY ';' ESCAPED BY '\\\"' LINES TERMINATED BY '\r\n';";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	$filesize=@filesize($filename);
	echo "<div style='margin:10px' class=form>
	<center style='font-size:18px;margin:20px'>hotspot_members.csv (".FormatBytes($filesize/1024).")</center>
	<center><a href='/ressources/conf/upload/hotspot_members.csv'><img src='img/csv-256.png'></a></center>
	</div>
	
	";
	echo "</center>";
	
}

function member_popup(){
	$tt=$_GET["t"];
	$uid=trim($_GET["uid"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	$bttext="{add}";
	$close=0;
	if($uid<>null){
		$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$bttext="{apply}";
		$close=1;
		
	}
	$error_passwords_mismatch=$tpl->javascript_parse_text("{error_passwords_mismatch}");
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";		
	
	
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	if(!is_numeric($HotSpotConfig["USERAD"])){$HotSpotConfig["USERAD"]=0;}
	
	$ttl="	<tr>
		<td class=legend style='font-size:16px'>{ttl}:</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"ttl-$t",$ligne["ttl"],null,null,0,"font-size:14px")."</td>	</tr>			
	</tr>";
	if($close==0){$ttl=null;}
	
	if(!is_numeric($ligne["sessiontime"])){$ligne["sessiontime"]=$HotSpotConfig["CACHE_AUTH"];}
	
	$html="
	<div id='$t-animate'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{account}</td>
		<td>". Field_text("uid-$t",$uid,"font-size:22px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password}</td>
		<td>". Field_password("nolock:password-$t",null,"font-size:22px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password} ({confirm})</td>
		<td>". Field_password("nolock:password1-$t",null,"font-size:22px;width:300px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ttl}:</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"ttl-$t",$ligne["ttl"],"style:font-size:22px;")."</td>
	</tr>						
	<tr>
		<td colspan=2 align='right'><hr>". button("$bttext","SaveAccountHotspot()","32")."</td>
	</tr>
	</table>
	</div>
	<script>
var x_SaveAccountHotspot$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('$t-animate').innerHTML='';
	if(results.length>3){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	var close=$close;
	if(close==1){YahooWin4Hide();}
	$('#flexRT$tt').flexReload();
}


function SaveAccountHotspot(){
	var XHR = new XHRConnection();
	var password=document.getElementById('password-$t').value;
	if(password.length>0){
		var password1=document.getElementById('password1-$t').value;
		if(password1!=password){alert('$error_passwords_mismatch');return;}
		password=encodeURIComponent(password);
		XHR.appendData('password',password);
	}
	XHR.appendData('uid',document.getElementById('uid-$t').value);
	if(document.getElementById('ttl-$t')){XHR.appendData('ttl',document.getElementById('ttl-$t').value);}
	XHR.appendData('ttl',document.getElementById('ttl-$t').value);				
	XHR.sendAndLoad('$page', 'POST',x_SaveAccountHotspot$t);
	}
	
function Check$t(){
	var close=$close;
	if(close==0){return;}
	document.getElementById('uid-$t').disabled=true;
}
Check$t();
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}
function members_save(){
	$uid=$_POST["uid"];
	$q=new mysql_squid_builder();
	$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$creationtime=time();
	if($ligne["uid"]==null){
		$_POST["password"]=url_decode_special_tool($_POST["password"]);
		$_POST["password"]=md5($_POST["password"]);
		$sql="INSERT IGNORE INTO hotspot_members (uid,ttl,sessiontime,password,enabled,creationtime) VALUES
		('$uid','{$_POST["ttl"]}','{$_POST["sessiontime"]}','{$_POST["password"]}',1,'$creationtime')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
		return;
	}
	
	if($_POST["password"]<>null){
		$_POST["password"]=url_decode_special_tool($_POST["password"]);
		$password=",password=MD5('{$_POST["password"]}')";
	}
	$sql="UPDATE hotspot_members 
		SET uid='$uid',
		ttl={$_POST["ttl"]}
		$password WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}	
}

function members_ttl_save(){
	
	$uid=$_POST["uid-ttl"];
	$q=new mysql_squid_builder();
	$creationtime=time();
	$sql="UPDATE hotspot_members SET ttl={$_POST["ttl"]},creationtime=$creationtime WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}		
}

function members_enable(){
	
	$sql="UPDATE hotspot_members SET enabled={$_POST["value"]} WHERE uid='{$_POST["EnableMember"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function members_delete(){
	$uid=$_POST["DeleteMember"];
	$q=new mysql_squid_builder();
	
	
	
	$sql="DELETE FROM hotspot_members WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	$sql="DELETE FROM hotspot_sessions WHERE username='$uid'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
}

function sessions(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$members=$tpl->_ENGINE_parse_body("{members}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$logintime=$tpl->_ENGINE_parse_body("{logintime}");
	$finaltime=$tpl->_ENGINE_parse_body("{end_session}");
	$created=$tpl->_ENGINE_parse_body("{created}");
	$title=$tpl->_ENGINE_parse_body("{sessions} ".date("{l} d {F}"));
	$incoming=$tpl->_ENGINE_parse_body("incoming");
	$outgoing=$tpl->_ENGINE_parse_body("outgoing");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`=''");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$remove_all_sessions=$tpl->javascript_parse_text("{remove_all_sessions}");
	$remove_all_sessions_explain=$tpl->javascript_parse_text("{remove_all_sessions_explain}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$remove_all_sessions</strong>', bclass: 'Delz', onpress : DeleteAll$t},
	],";
	
	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?sessions-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '<span style=font-size:18px>$members</span>', name : 'uid', width :477, sortable : true, align: 'left'},	
		{display: '<span style=font-size:18px>$MAC</span>', name : 'MAC', width :140, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$logintime</span>', name : 'logintime', width :233, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$finaltime</span>', name : 'finaltime', width :233, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$incoming</span>', name : 'incoming', width :96, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$outgoing</span>', name : 'outgoing', width :96, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none', width :80, sortable : true, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$members', name : 'uid'},
		{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'logintime',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\" style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	var x_DeleteSession$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
		
	}

function DeleteMember$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function DeleteAll$t(){
	if(!confirm('$remove_all_sessions_explain')){return;}	
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAllSessions','yes');	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function DeleteSession$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function Chooseday$t(){
	YahooWin2('260','$page?choose-day=yes&t=$t','$choose_day');

}

</script>";
	
	echo $html;	
}
function sessions_remove(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE md5='{$_POST["delete-session"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function sessions_all_remove(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE hotspot_sessions");
	if(!$q->ok){echo $q->mysql_error;}
}


function sessions_items(){

	
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="hotspot_sessions";
	$uid=$_SESSION["uid"];
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",1,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
		$md5=$ligne["md5"];
		
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	
	if(mysql_num_rows($results)==0){json_error_show("No session",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$finaltime=$ligne["finaltime"];
		$logintime=intval($ligne["logintime"]);
		$nextcheck=$ligne["nextcheck"];
		$ArticaSplashHotSpotEndTime_text=null;
		$ArticaSplashHotSpotRemoveAccount_text=null;
		
		$Start=$tpl->_ENGINE_parse_body(date("{l} d H:i",$logintime));
		$delete=imgsimple("delete-42.png",null,"Loadjs('$myPage?delete-session-js={$ligne["md5"]}&t=$t')");
		$End=$tpl->_ENGINE_parse_body(date("Y {l} d H:i",$nextcheck));
		$incoming=FormatBytes($ligne["incoming"]);
		$outgoing=FormatBytes($ligne["outgoing"]);
		$ipaddr=$ligne["ipaddr"];
		if($ligne["nextcheck"]<time()){
			$color="#CD0D0D";
			
		}
		
		$ruleid=$ligne["ruleid"];
		if($ruleid>0){
			$sql="SELECT rulename FROM webauth_rules WHERE ID='{$ruleid}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$rulename=$tpl->_ENGINE_parse_body("{rule}:$ruleid &laquo;".utf8_encode($ligne2["rulename"])."&raquo;");
			$sock=new wifidog_settings($ruleid);
			$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
			if($ArticaSplashHotSpotCacheAuth==0){
					
				$color="black";
			}
			if($ArticaSplashHotSpotCacheAuth>0){
				$nextcheck = strtotime("+30 minutes", time());
				$End=$tpl->_ENGINE_parse_body(date("Y {l} d H:i",$nextcheck));
			}
			
			$ArticaSplashHotSpotEndTime=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
			$ArticaSplashHotSpotRemoveAccount=intval($sock->GET_INFO("ArticaSplashHotSpotRemoveAccount"));
			if($ArticaSplashHotSpotEndTime==0){$ArticaSplashHotSpotEndTime_text=$tpl->_ENGINE_parse_body("{never}");}
			if($ArticaSplashHotSpotEndTime>0){
				$endlife = strtotime("+{$ArticaSplashHotSpotEndTime} minutes", time());
				$ArticaSplashHotSpotEndTime_text=$tpl->_ENGINE_parse_body(date("{l} d H:i",$endlife));
				
			}
			if($ArticaSplashHotSpotRemoveAccount==0){$ArticaSplashHotSpotRemoveAccount_text=$tpl->_ENGINE_parse_body("{never}");}
			if($ArticaSplashHotSpotRemoveAccount>0){
				$endlife = strtotime("+{$ArticaSplashHotSpotRemoveAccount} minutes", time());
				$ArticaSplashHotSpotRemoveAccount_text=$tpl->_ENGINE_parse_body(date("{l} d H:i",$endlife));
				
			}
			
			
		}else{
			$rulename=$tpl->_ENGINE_parse_body("{rule}: &laquo;{default}&raquo;");
		}
		
		
		if($ArticaSplashHotSpotCacheAuth==0){$End=$tpl->_ENGINE_parse_body("{never}");}
		
		$hostname=$ligne["hostname"];
		$nextcheck=$ligne["nextcheck"];
		
		
		$nextcheckS=$nextcheck-time();
		$nextcheckM=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),$nextcheck));
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $ligne["md5"],
				'cell' => array(
					"<span style='font-size:18px;color:$color'>{$ligne["uid"]}<br>$rulename<br><i style='font-size:16px'>".
					$tpl->javascript_parse_text("{disable_account}").":$ArticaSplashHotSpotEndTime_text<br>".
					$tpl->javascript_parse_text("{remove_account}").":$ArticaSplashHotSpotRemoveAccount_text</i><br><i style='font-size:16px'>".
					$tpl->javascript_parse_text("{nextcheck}").": {$nextcheckS}s ({$nextcheckM})</i>".
					"<i style='font-size:18px'>$hostname</i></a></span>",
					"<span style='font-size:18px;color:$color'>{$ligne["MAC"]}</a><br><i style='font-size:18px'>$ipaddr</i></span>",
					"<span style='font-size:18px;color:$color'>$Start</a></span>",
					
					"<span style='font-size:18px;color:$color'>$End</a></span>",
					"<span style='font-size:18px;color:$color'>$incoming</a></span>",
					"<span style='font-size:18px;color:$color'>$outgoing</a></span>",
					"<center style='font-size:18px;color:$color'>$delete</a></center>",
					
					)
				);
			}
	
	
echo json_encode($data);	
}

function members_items(){
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="hotspot_members";
	$uid=$_SESSION["uid"];
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	$sock=new sockets();
	

	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",0,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

		
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$unlimited=$tpl->_ENGINE_parse_body("{unlimited}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$waiting_confirmation=$tpl->_ENGINE_parse_body("{waiting_confirmation}");
	$confirmed=$tpl->_ENGINE_parse_body("{confirmed}");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$activedirectory_text=null;
		$md5=md5(serialize($ligne));
		$uid= $ligne["uid"];
		$uid_url=urlencode($uid);
		$ttl=intval($ligne["ttl"]);
		$EnOfLife = strtotime("+{$ttl} minutes", $ligne["creationtime"]);
		$delete=imgsimple("delete-42.png",null,"DeleteMember$t('{$ligne["uid"]}','$md5')");
		$MAC_text=null;
		$creationtime=$q->time_to_date($ligne["creationtime"],true);
		$End=$q->time_to_date($EnOfLife,true);
		$hostname=$ligne["hostname"];
		$MAC=$ligne["MAC"];
		$ipaddr=$ligne["ipaddr"];
		$ttl=intval($ligne["ttl"]);
		
		
		$color="black";
		if($ligne["enabled"]==0){$color="#A4A1A1";}
		
		$urlend="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$myPage?ttl-js=$uid_url&t=$t');\"
		style='font-size:22px;text-decoration:underline;color:$color'>";		
		
		
		if($ttl==0){$ttl=$unlimited;$End=$unlimited;}else{
			$ttl="{$ttl} $minutes";
		}
		
		
		$enabled=Field_checkbox("enable_$uid", 1,$ligne["enabled"],"MemberEnable$t('$uid')");

		$uid_url=urlencode($ligne["uid"]);
		$urljs="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin4('750','$myPage?uid=$uid_url&t=$t','{$ligne["uid"]}');\"
		style='font-size:22px;text-decoration:underline;color:$color'>";	

		$urlttl="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin4('500','$myPage?ttl=$uid_url&t=$t','$ttl:{$ligne["uid"]}');\"
		style='font-size:22px;text-decoration:underline;color:$color'>";

		if($ligne["activedirectory"]==1){
			$activedirectory_text="<br><span style='font-style:italic;font-size:18px'>Active Directory: ".ActiveDirectoryCnx($ligne["activedirectorycnx"])."</span>";
		}
		
		if($ligne["autocreate"]==1){
			if($ligne["autocreate_confirmed"]==1){
				$activedirectory_text="<br><span style='font-style:italic;font-size:18px'>$confirmed</span>";
				
			}else{
				$activedirectory_text="<br><span style='font-style:italic;font-size:18px'>{$waiting_confirmation}</span>";
			}
		}
		
		if($MAC<>null){$MAC_text= " <span style='font-size:12px'>($MAC)</span>";}
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
					"<span style='font-size:22px;color:$color'>{$creationtime}</a></span>",
					"<span style='font-size:22px;color:$color'>$urljs{$ligne["uid"]}</a>$MAC_text</span>$activedirectory_text",
					"<span style='font-size:22px;color:$color'>$ttl</a></span>",
					"<span style='font-size:22px;color:$color'>$urlend$End</a></span>",
					"<span style='font-size:22px;color:$color'>$enabled</a></span>",
					"<center style='font-size:22px;color:$color'>$delete</a></center>",
					
					)
				);
			}
	
	
echo json_encode($data);	
}

function ActiveDirectoryCnx($md5){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT hostname FROM hotspot_activedirectory WHERE zmd5='$md5'"));
	return $ligne["hostname"];
}

function mysql_settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$HotSpotAutoRegisterWebMail=intval($sock->GET_INFO("HotSpotAutoRegisterWebMail"));
	$HotSpotAutoRegisterSMTPSrv=$sock->GET_INFO("HotSpotAutoRegisterSMTPSrv");
	$HotSpotAutoRegisterSMTPSrvPort=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPSrvPort"));
	$HotSpotAutoRegisterSMTPSender=$sock->GET_INFO("HotSpotAutoRegisterSMTPSender");
	$HotSpotAutoRegisterSMTPUser=$sock->GET_INFO("HotSpotAutoRegisterSMTPUser");
	$HotSpotAutoRegisterSMTPPass=$sock->GET_INFO("HotSpotAutoRegisterSMTPPass");
	$HotSpotAutoRegisterSMTPTls=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPTls"));
	$HotSpotAutoRegisterSMTPSSL=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPSSL"));
	$HotSpotAutoRegisterSubject=$sock->GET_INFO("HotSpotAutoRegisterSubject");
	$HotSpotAutoRegisterContent=$sock->GET_INFO("HotSpotAutoRegisterContent");
	$HotSpotAutoRegisterConfirmTxt=$sock->GET_INFO("HotSpotAutoRegisterConfirmTxt");
	$HotSpotAutoRegisterMaxTime=intval($sock->GET_INFO("HotSpotAutoRegisterMaxTime"));
	
	if($HotSpotAutoRegisterSMTPSrvPort==0){$HotSpotAutoRegisterSMTPSrvPort=25;}
	if($HotSpotAutoRegisterMaxTime==0){$HotSpotAutoRegisterMaxTime=3;}
	
	if($HotSpotAutoRegisterSubject==null){
		$HotSpotAutoRegisterSubject="Access to Internet";
	}
	
	if($HotSpotAutoRegisterContent==null){
		$HotSpotAutoRegisterContent="In order to complete your registration and activate your account %email with %pass password\r\nClick on the link bellow:\r\n";
	}

	if($HotSpotAutoRegisterConfirmTxt==null){
		$HotSpotAutoRegisterConfirmTxt="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration";
	}
	
	$HotSpotAutoRegisterMaxTimeR[3]="3mn";
	$HotSpotAutoRegisterMaxTimeR[5]="5mn";
	$HotSpotAutoRegisterMaxTimeR[10]="10mn";
	$HotSpotAutoRegisterMaxTimeR[15]="15mn";
	
	$html="<div style=width:95%' class=form>
	<table style='width:99%'>
		<tr>
			<td colspan=2>". Paragraphe_switch_img("{HotSpotAutoRegisterWebMail}", 
					"{HotSpotAutoRegisterWebMail_explain}","HotSpotAutoRegisterWebMail",$HotSpotAutoRegisterWebMail,null,600)."
			</td>
		</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{max_allowed_time}:</strong></td>
		<td>" . Field_array_Hash($HotSpotAutoRegisterMaxTimeR,'HotSpotAutoRegisterMaxTime',$HotSpotAutoRegisterMaxTime,'style:font-size:18px;')."</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:32px'>&nbsp;</td>
	</tr>										
	<tr>
		<td colspan=2 style='font-size:32px'>{smtp_parameters}<hr></td>
	</tr>							
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text('HotSpotAutoRegisterSMTPSrv',$HotSpotAutoRegisterSMTPSrv,'font-size:18px;padding:3px;width:453px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text('HotSpotAutoRegisterSMTPSrvPort',$HotSpotAutoRegisterSMTPSrvPort,'font-size:18px;padding:3px;width:90px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_sender}:</strong></td>
		<td>" . Field_text('HotSpotAutoRegisterSMTPSender',$HotSpotAutoRegisterSMTPSender,'font-size:18px;padding:3px;width:453px')."</td>
	</tr>

	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text('HotSpotAutoRegisterSMTPUser',$HotSpotAutoRegisterSMTPUser,'font-size:18px;padding:3px;width:453px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("HotSpotAutoRegisterSMTPPass",trim($HotSpotAutoRegisterSMTPPass),'font-size:18px;padding:3px;width:200px')."</td>
	</tr>
	<tr>

	<tr>
		<td colspan=2 style='font-size:32px'>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=2 style='font-size:32px'>{message_parameters}<hr></td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:18px'>{subject}:</strong></td>
		<td>" . Field_text('HotSpotAutoRegisterSubject',$HotSpotAutoRegisterSubject,
				'font-size:18px;padding:3px;width:629px')."</td>
	</tr>
	
	
	
	<tr>
		<td colspan=2 style='font-size:18px'>{message_content}:</td>
	</tr>	
	<tr>
		<td colspan=2 >
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:220px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px'
		id='HotSpotAutoRegisterContent'>$HotSpotAutoRegisterContent</textarea>
	</td>
	</tr>
	
	<tr>
		<td colspan=2 style='font-size:18px'>{confirm_message}:</td>
	</tr>	
	<tr>
		<td colspan=2 >
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:180px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px'
		id='HotSpotAutoRegisterConfirmTxt'>$HotSpotAutoRegisterConfirmTxt</textarea>
	</td>
	</tr>	
	
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",28)."</td>
	</tr>							
							
	</table>
	</div>
<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	
}

function Save$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('HotSpotAutoRegisterSMTPPass').value);
	XHR.appendData('HotSpotAutoRegisterWebMail',document.getElementById('HotSpotAutoRegisterWebMail').value);
	XHR.appendData('HotSpotAutoRegisterMaxTime',document.getElementById('HotSpotAutoRegisterMaxTime').value);
	XHR.appendData('HotSpotAutoRegisterSMTPSrv',document.getElementById('HotSpotAutoRegisterSMTPSrv').value);
	XHR.appendData('HotSpotAutoRegisterSMTPSrvPort',document.getElementById('HotSpotAutoRegisterSMTPSrvPort').value);
	XHR.appendData('HotSpotAutoRegisterSMTPSender',document.getElementById('HotSpotAutoRegisterSMTPSender').value);
	XHR.appendData('HotSpotAutoRegisterSMTPPass',pp);
	XHR.appendData('HotSpotAutoRegisterSubject',encodeURIComponent(document.getElementById('HotSpotAutoRegisterSubject').value));
	XHR.appendData('HotSpotAutoRegisterContent',encodeURIComponent(document.getElementById('HotSpotAutoRegisterContent').value));
	XHR.appendData('HotSpotAutoRegisterConfirmTxt',encodeURIComponent(document.getElementById('HotSpotAutoRegisterConfirmTxt').value));
	XHR.appendData('HotSpotAutoRegisterSMTPUser',document.getElementById('HotSpotAutoRegisterSMTPUser').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";					
echo $tpl->_ENGINE_parse_body($html);
}
function mysql_settings_save(){
	$sock=new sockets();
	$_POST["HotSpotAutoRegisterSMTPPass"]=url_decode_special_tool($_POST["HotSpotAutoRegisterSMTPPass"]);
	$_POST["HotSpotAutoRegisterSMTPPass"]=url_decode_special_tool($_POST["HotSpotAutoRegisterSMTPPass"]);
	$_POST["HotSpotAutoRegisterContent"]=url_decode_special_tool($_POST["HotSpotAutoRegisterContent"]);
	$_POST["HotSpotAutoRegisterSubject"]=url_decode_special_tool($_POST["HotSpotAutoRegisterSubject"]);
	$_POST["HotSpotAutoRegisterConfirmTxt"]=url_decode_special_tool($_POST["HotSpotAutoRegisterConfirmTxt"]);
	
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num, $ligne);
	}
	
}




