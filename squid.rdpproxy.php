<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["services-status"])){services_status();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["EnableRDPProxy"])){EnableRDPProxy();exit;}
	if(isset($_GET["members"])){members();exit;}
	if(isset($_GET["members-search"])){members_search();exit;}
	if(isset($_GET["members-js"])){members_js();exit;}
	if(isset($_GET["members-tabs"])){members_tabs();exit;}
	if(isset($_GET["members-popup"])){members_popup();exit;}
	if(isset($_POST["members-delete"])){members_delete();exit;}
	if(isset($_POST["serviceport"])){accounts_save();exit;}
	if(isset($_POST["username"])){members_save();exit;}
	if(isset($_GET["accounts-search"])){accounts_search();exit;}
	if(isset($_GET["members-accounts"])){accounts();exit;}
	if(isset($_GET["account-js"])){accounts_js();exit;}
	if(isset($_GET["accounts-js"])){accounts_js();exit;}
	if(isset($_GET["accounts-popup"])){accounts_popup();exit;}
	if(isset($_POST["accounts-delete"])){accounts_delete();exit;}
	if(isset($_GET["rdp-download"])){rdpp_download();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$APP_RDPPROXY=$tpl->javascript_parse_text("{APP_RDPPROXY}");
	echo "YahooWin('750','$page?tabs=yes','$APP_RDPPROXY',true);";
}
function members_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("{new_member}");
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_users WHERE ID='$ID'"));
		$TITLE=utf8_encode($ligne["username"]);
	}
	
	echo "YahooWin2('750','$page?members-tabs=yes&ID=$ID&t=$t','$TITLE',true);";	
	
}
function accounts_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("{new_connection}");
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_items WHERE ID='$ID'"));
		$TITLE=utf8_encode($ligne["service"]);
	}
	
	echo "YahooWin3('750','$page?accounts-popup=yes&userid={$_GET["userid"]}&ID=$ID&t=$t&tt={$_GET["tt"]}','$TITLE',true);";	
}


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["parameters"]='{parameters}';
	$array["members"]='{connections}';
	$array["events"]="{events}";
	$array["about"]="{about2}";
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.rdpproxy-events.php\" 
					style='font-size:24px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="about"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.rdpproxy-about.php\" 
					style='font-size:24px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" 
				style='font-size:24px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_rdpproxy_tabs")."<script>LeftDesign('remote-desktop-256-white-opac20.png');</script>";

}
function members_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==0){
		$array["members-popup"]='{new_member}';
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_users WHERE ID='$ID'"));
		$array["members-popup"]=utf8_encode($ligne["username"]);
		$array["members-accounts"]='{connections}';
	}
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID&tt={$_GET["tt"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_members_tabs");	
	
}

function services_status(){
	
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?rdpproxy-ini-status=yes')));
	$APP_RDPPROXY=DAEMON_STATUS_ROUND("APP_RDPPROXY",$ini,null,0);
	$APP_RDPPROXY_AUTHHOOK=DAEMON_STATUS_ROUND("APP_RDPPROXY_AUTHHOOK",$ini,null,0);
	
	
	$tr[]=$APP_RDPPROXY;
	$tr[]=$APP_RDPPROXY_AUTHHOOK;
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $tr));
	
}


function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$net=new networking();
	if(!$users->RDPPROXY_INSTALLED){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{NOT_INSTALLED}")."</p>";
	}
	$t=time();

	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	$RDPProxyListen=$sock->GET_INFO("RDPProxyListen");
	$RDPProxyPort=$sock->GET_INFO("RDPProxyPort");
	$RDPDisableGroups=$sock->GET_INFO("RDPDisableGroups");
	if($RDPProxyListen==null){$RDPProxyListen="0.0.0.0";}
	
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if(!is_numeric($RDPProxyPort)){$RDPProxyPort=3389;}
	if(!is_numeric($RDPDisableGroups)){$RDPDisableGroups=1;}
	$ips=$net->ALL_IPS_GET_ARRAY();
	unset($ips["127.0.0.1"]);
	$ips["0.0.0.0"]="{all}";

	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:350px'>
			<div style='width:98%' class=form>
				<div style='font-size:30px;margin-bottom:20px'>{services_status}</div>
				<div id='squidrdp-status'></div>
				<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('squidrdp-status','$page?services-status=yes')")."</div>
			</div>
		
		
		</td>
		<td valign='top' style='padding-left:15px'>
	<div style='font-size:60px;margin-bottom:15px'>{APP_RDPPROXY}</div>	
	<hr>	
	<div id='test-$t'></div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
		<td colspan=3>". Paragraphe_switch_img("{activate_RDP_service}", 
				"{APP_RDPPROXY_EXPLAIN}","EnableRDPProxy","$EnableRDPProxy",null,1050)."
		</tr>
			<tr>
				<td class=legend style='font-size:22px !important;vertical-align:top''>{listen_ip}:</td>
				<td>". Field_array_Hash($ips, "RDPProxyListen",$RDPProxyListen,null,null,0,"font-size:22px")."</td>
				<td width=1%></td>
			</tr>		
			<tr>
				<td class=legend style='font-size:22px !important;vertical-align:top''>{listen_port}:</td>
				<td>". Field_text("RDPProxyPort", $RDPProxyPort,"font-size:22px;width:90px")."</td>
				<td width=1%></td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px !important;vertical-align:top''>{disable_groups}:</td>
				<td>". Field_checkbox_design("RDPDisableGroups", 1,$RDPDisableGroups)."</td>
				<td width=1%></td>
			</tr>						

			<tr>
				<td colspan=3  align='right'><hr>". button("{apply}", "Save$t()","32px")."</td>
			</tr>
</table>
</div>
</td>
</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.rdpproxy.progress.php');
	
}
function Save$t(){
	var XHR = new XHRConnection();
	EnableRDPProxy=0;
	RDPDisableGroups=0;
	
	if(document.getElementById('RDPDisableGroups').checked){RDPDisableGroups=1;}
	XHR.appendData('EnableRDPProxy',EnableRDPProxy);
	XHR.appendData('RDPDisableGroups',RDPDisableGroups);
	XHR.appendData('RDPProxyPort',document.getElementById('RDPProxyPort').value);
	XHR.appendData('EnableRDPProxy',document.getElementById('EnableRDPProxy').value);
	XHR.appendData('RDPProxyListen',document.getElementById('RDPProxyListen').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(){
	LoadAjax('squidrdp-status','$page?services-status=yes');
	document.getElementById('RDPDisableGroups').disabled=true;
}
Check$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}
function EnableRDPProxy(){
	$sock=new sockets();
	$sock->SET_INFO("EnableRDPProxy", $_POST["EnableRDPProxy"]);
	$sock->SET_INFO("RDPProxyPort", $_POST["RDPProxyPort"]);
	$sock->SET_INFO("RDPProxyListen", $_POST["RDPProxyListen"]);
	$sock->getFrameWork("rdpproxy.php?restart=yes");
	$sock->getFrameWork("monit.php?restart=yes");
}
function members(){
	$page=CurrentPageName();
	$sock=new sockets();
	$RDPDisableGroups=$sock->GET_INFO("RDPDisableGroups");
	if(!is_numeric($RDPDisableGroups)){$RDPDisableGroups=1;}
	if($RDPDisableGroups==1){
		$t=time();
		echo "<div id='$t'></div><script>LoadAjax('$t','$page?members-accounts=yes&ID=0&t=$t&tt=$t');</script>";
		return;
	}
	
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$shortname=$tpl->javascript_parse_text("{member}");
	$nastype=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$connection=$tpl->javascript_parse_text("{connection}");
	$add=$tpl->javascript_parse_text("{new_member}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$freeradius_users_explain=$tpl->_ENGINE_parse_body("{freeradius_users_explain}");
	$tablewidht=883;
	
	$title=$tpl->javascript_parse_text("{connections}");
	
	$t=time();

	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : AddMember$t},
	],	";



	echo "
	
	<table class='$t' style='display: none' id='flexRT$t' style='width:99%;text-align:left'></table>
<script>
var MEMM$t='';
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?members-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>$shortname</span>', name : 'username', width : 365, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>CNX</span>', name : 'none3', width : 80, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none3', width : 40, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$shortname', name : 'username'},


	],
	sortname: 'username',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	});
});

function RefreshTable$t(){
	$('#flexRT$t').flexReload();
}

function enable_ip_authentication_save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
}


var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$t();
}

var x_ConnectionDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	RefreshTable$t();
}

function AddMember$t(){
	Loadjs('$page?members-js=yes&t=$t&ID=0');
}

function EnableLocalLDAPServer$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableLocalLDAPServer','yes');
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

function EnableDisable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('EnableDisable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

function MembersDelete$t(ID){
	MEMM$t=ID;
	if(confirm('$delete ?')){
		var XHR = new XHRConnection();
		XHR.appendData('members-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
	}
}
</script>
";
}
function members_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="rdpproxy_users";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=null;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
	if(!$q->TABLE_EXISTS($table)){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_users` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`username` VARCHAR(128),
			`password` VARCHAR(128),
			 KEY `username`(`username`),
			 KEY `password`(`password`)
			 )  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql);
			if(!$q->ok){json_error_show("$q->mysql_error",1);}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	if(mysql_num_rows($results)==0){json_error_show("{no_member_stored_in_this_area}",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$delete=imgsimple("delete-48.png",null,"MembersDelete$t('{$ligne['ID']}')");
		$sql="SELECT COUNT(*) as TCOUNT FROM `rdpproxy_items` WHERE userid={$ligne["ID"]}";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		$totalC = $ligne2["TCOUNT"];
		$ahref="<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('$MyPage?members-js=yes&ID={$ligne['ID']}&t=$t');\"
						style=\"font-size:20px;text-decoration:underline;color:$color\">";
	
	
		$data['rows'][] = array(
				'id' => $ligne['id'],
				'cell' => array("
						<img src='img/computer-windows-64.png'>",
						"$ahref{$ligne['username']}</a>",
						"$ahref$totalC</a>",
						$delete
		)
		);
	}
	
	
	echo json_encode($data);
}


function members_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_users WHERE ID='$ID'"));
		
	}

$html="<div id='anim-$t'></div>
<div style='width:98%' class=form>
	<table style='width:99%'>
		<tr>
			<td class=legend style='font-size:16px'>{username}:</td>
			<td>". Field_text("username-$t",$ligne["username"],"font-size:16px;width:220px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{password}:</td>
			<td>". Field_password("password-$t",$ligne["password"],"font-size:16px;width:220px")."</td>
		</tr>
		<tr>
			<td colspan=2 align=right><hr>".button("$btname","Save$t()",18)."</td>
		</tr>
	</table>
</div>
<script>
var x_Save$t= function (obj) {
	var ID='$ID';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	if(ID==0){YahooWin2Hide();}
	$('#flexRT$t').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('username', encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('ID', '$ID');
	XHR.appendData('password', encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}
function members_save(){
	$ID=$_POST["ID"];
	$username=url_decode_special_tool($_POST["username"]);
	$password=url_decode_special_tool($_POST["password"]);
	$q=new mysql_squid_builder();
	
	if($ID>0){
		$q->QUERY_SQL("UPDATE rdpproxy_users SET `username`='$username',`password`='$password' WHERE ID=$ID");	
		
	}else{
		$q->QUERY_SQL("INSERT IGNORE INTO rdpproxy_users (`username`,`password`) VALUES ('$username','$password')");
	}
	if(!$q->ok){echo $q->mysql_error;}
}
function accounts(){

	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$shortname=$tpl->javascript_parse_text("{member}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$service=$tpl->javascript_parse_text("{connection}");
	$add=$tpl->javascript_parse_text("{new_connection}");
	$servicetype=$tpl->_ENGINE_parse_body("{type}");
	$title=$tpl->javascript_parse_text("{connections}");
	$tablewidht=883;
	$t=$_GET["t"];
	$tt=time();

	$buttons="buttons : [
	{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : AddAccount$tt},
	],	";

	echo "

	<table class='$tt' style='display: none' id='flexRT$tt' style='width:99%;text-align:left'></table>
	<script>
	var MEMM$tt='';
	$(document).ready(function(){
	$('#flexRT$tt').flexigrid({
	url: '$page?accounts-search=yes&t=$t&tt=$tt&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none2', width : 90, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>$service</span>', name : 'service', width : 209, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$shortname</span>', name : 'username', width : 300, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$hostname</span>', name : 'rhost', width : 300, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$servicetype</span>', name : 'servicetype', width : 70, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>download</span>', name : 'download', width : 127, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width : 90, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$service', name : 'service'},
	{display: '$hostname', name : 'rhost'},
	{display: '$servicetype', name : 'servicetype'},
	{display: '$shortname', name : 'username'},
	


	],
	sortname: 'username',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
});
});

function RefreshTable$tt(){
	$('#flexRT$tt').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
}


var x_Refresh$tt=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$tt();
}

function AddAccount$tt(){
	Loadjs('$page?account-js=yes&t=$t&userid={$_GET["ID"]}&ID=0&tt=$tt');
}

function ItemsDelete$tt(ID){
	MEMM$tt=ID;
	if(confirm('$delete ?')){
		var XHR = new XHRConnection();
		XHR.appendData('accounts-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_Refresh$tt);
	}
}
</script>
		";
}

function accounts_popup(){
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$userid=$_GET["userid"];
	$t=time();
	$services["RDP"]="RDP";
	$services["VNC"]="VNC";
	$page=CurrentPageName();
	$tpl=new templates();
	$btname="{add}";
	$q=new mysql_squid_builder();
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_items WHERE ID='$ID'"));
	
	}
	
	if(!is_numeric($ligne["alive"])){$ligne["alive"]=720000;}
	if(!is_numeric($ligne["is_rec"])){$ligne["is_rec"]=0;}
	if(!is_numeric($ligne["serviceport"])){$ligne["serviceport"]=3389;}
	
	$distance=time()+$ligne["alive"];
	$dis=distanceOfTimeInWords(time(),$distance,true);
	
	$html="<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td>". Field_text("service-$t",$ligne["service"],"font-size:22px;width:430px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{type}:</td>
		<td>". Field_array_Hash($services,"servicetype-$t",$ligne["servicetype"],"style:font-size:22px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{ipaddr}:</td>
		<td>". Field_text("rhost-$t",$ligne["rhost"],"font-size:22px;width:430px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{listen_port}:</td>
		<td>". Field_text("serviceport-$t",$ligne["serviceport"],"font-size:22px;width:430px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{username}:</td>
		<td>". Field_text("username-$t",$ligne["username"],"font-size:22px;width:430px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{domain}:</td>
		<td>". Field_text("domain-$t",$ligne["domain"],"font-size:22px;width:430px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("password-$t",$ligne["password"],"font-size:22px;width:430px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{alive}:</td>
		<td style='font-size:16px'>". Field_text("alive-$t",$ligne["alive"],"font-size:22px;width:160px")."&nbsp;{seconds}</td>
	</tr>	
	<tr><td colspan=2 align='right'><span style='font-size:16px'><i>$dis</i></span></td></tr>			
	<tr>
		<td class=legend style='font-size:22px'>{record_session}:</td>
		<td style='font-size:22px'>". Field_checkbox_design("is_rec-$t",1,$ligne["is_rec"])."</td>
	</tr>				
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",30)."</td>
	</tr>
	</table>
	</div>
<script>
var x_Save$t= function (obj) {
	var ID='$ID';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	if(ID==0){YahooWin3Hide();}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var is_rec=0;
	if( document.getElementById('is_rec-$t').checked){is_rec=1;}
	XHR.appendData('username', encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('password', encodeURIComponent(document.getElementById('password-$t').value));
	XHR.appendData('rhost', encodeURIComponent(document.getElementById('rhost-$t').value));
	XHR.appendData('serviceport', encodeURIComponent(document.getElementById('serviceport-$t').value));
	XHR.appendData('username', encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('domain', encodeURIComponent(document.getElementById('domain-$t').value));
	XHR.appendData('service', encodeURIComponent(document.getElementById('service-$t').value));
	XHR.appendData('servicetype', encodeURIComponent(document.getElementById('servicetype-$t').value));
	
	
	XHR.appendData('alive', encodeURIComponent(document.getElementById('alive-$t').value));
	XHR.appendData('ID', '$ID');
	XHR.appendData('userid', '$userid');
	XHR.appendData('is_rec', is_rec);
	
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}
	</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function accounts_save(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=url_decode_special_tool($ligne);
	}
	

	if(!$q->FIELD_EXISTS("rdpproxy_items", "domain")){
		$q->QUERY_SQL("ALTER TABLE `rdpproxy_items` ADD `domain`  VARCHAR(128)");
	}
	
	$ID=$_POST["ID"];
	if($ID>0){
		$sql="UPDATE rdpproxy_items SET 
			`service` ='{$_POST["service"]}',
			`rhost` ='{$_POST["rhost"]}',
			`username` ='{$_POST["username"]}',
			`domain` ='{$_POST["domain"]}',
			`password` ='{$_POST["password"]}',
			`servicetype` ='{$_POST["servicetype"]}',
			`serviceport` ='{$_POST["serviceport"]}',
			`alive` ='{$_POST["alive"]}',
			`is_rec` ='{$_POST["is_rec"]}' WHERE ID={$_POST["ID"]}";
		
	}else{
		if(!$users->CORP_LICENSE){if($q->COUNT_ROWS("rdpproxy_items")>50){echo base64_decode("TGljZW5zZSBlcnJvciBNQVg6NTAK");return;}}
		$sql="INSERT INTO rdpproxy_items (`userid`,	`service`,`rhost`,`username`,`password`,`servicetype`,
			`serviceport`,`alive`,`is_rec`,`domain`)
		VALUES ('{$_POST["userid"]}','{$_POST["service"]}','{$_POST["rhost"]}',
			'{$_POST["username"]}',
			'{$_POST["password"]}',
			'{$_POST["servicetype"]}',
			'{$_POST["serviceport"]}',
			'{$_POST["alive"]}',
			'{$_POST["is_rec"]}','{$_POST["domain"]}')";
		}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}	
	$sock=new sockets();
	$sock->getFrameWork("rdpproxy.php?restart-auth=yes");	

}
function accounts_delete(){
	
	$sql="DELETE FROM rdpproxy_items WHERE ID={$_POST["accounts-delete"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
	$sock=new sockets();
	$sock->getFrameWork("rdpproxy.php?restart-auth=yes");
	
}
function members_delete(){
	$sql="DELETE FROM rdpproxy_items WHERE userid={$_POST["members-delete"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	$sql="DELETE FROM rdpproxy_users WHERE ID={$_POST["members-delete"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}		
	$sock=new sockets();
	$sock->getFrameWork("rdpproxy.php?restart-auth=yes");
	
}

function accounts_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="rdpproxy_items";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER="userid='{$_GET["ID"]}'";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	if(!$q->TABLE_EXISTS($table)){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`rdpproxy_items` (
			`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`userid` BIGINT(11),
			`service` VARCHAR(128) ,
			`rhost` VARCHAR(128),
			`username` VARCHAR(128),
			`domain` VARCHAR(128),
			`password` VARCHAR(128),
			`servicetype` VARCHAR(15),
			`serviceport` smallint(15),
			`alive` INT UNSIGNED NOT NULL,
			`is_rec` smallint(1),
			 KEY `username`(`username`),
			 KEY `password`(`password`),
			 KEY `service`(`service`),
			 KEY `rhost`(`rhost`),
			 KEY `userid`(`userid`)
			 )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
	}


	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `rdpproxy_items` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}



	$data['page'] = $page;
	$data['total'] = $total;

	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$download="&nbsp;";
		$delete=imgsimple("delete-48.png",null,"ItemsDelete$tt('{$ligne['ID']}')");

		$href="<a href=\"javascript:blur();\"
						OnClick=\"javascript:Loadjs('$MyPage?accounts-js=yes&userid={$_GET["ID"]}&ID={$ligne['ID']}&t=$t&tt=$tt');\"
						style=\"font-size:22px;text-decoration:underline;color:$color\">";

		$img="computer-windows-48.png";
		if($ligne['servicetype']=="RDP"){
			if(!fsock_perform($ligne['rhost'],3389)){
				$img="computer-windows-48-red.png";
			}else{
				
				$download="<a href=\"$MyPage?rdp-download={$ligne['ID']}\"><img src='img/download-48.png'></a>";
			}
		}
		
		
		$data['rows'][] = array(
				'id' => "ACC{$ligne['ID']}",
				'cell' => array("
						<center><img src='img/$img'></center>",
						"$href{$ligne['service']}</a>",
						"$href{$ligne['username']}/{$ligne['service']}</a>",
						"$href{$ligne['rhost']}</a>",
						"<center$href{$ligne['servicetype']}</a></center>",
						"<center>$download</center>",
						"<center>$delete</center>"
				)
		);
	}
	echo json_encode($data);
}
function fsock_perform($server,$port){
	$fp=@fsockopen($server, $port, $errno, $errstr, 1);
	if(!$fp){
		if($GLOBALS["DEBUG"]){writelogs("Fatal: fsockopen -> $server:$port",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		@fclose($fp);
		return false;
	}
	@fclose($fp);
	return true;
}

function rdpp_download(){
	
	$ID=$_GET["rdp-download"];
	$q=new mysql_squid_builder();
	
	$sock=new sockets();
	$RDPProxyListen=$sock->GET_INFO("RDPProxyListen");
	if($RDPProxyListen=="0.0.0.0"){
		$net=new networking();
		$ips=$net->ALL_IPS_GET_ARRAY();
		unset($ips["127.0.0.1"]);
		while (list ($num, $ligne) = each ($ips) ){ $RDPProxyListen=$num;break; }
	}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM rdpproxy_items WHERE ID='$ID'"));
	
	
	$f[]="screen mode id:i:1";
	$f[]="use multimon:i:1";
	$f[]="desktopwidth:i:1024";
	$f[]="desktopheight:i:768";
	$f[]="session bpp:i:32";
	$f[]="winposstr:s:0,1,325,121,1365,927";
	$f[]="compression:i:1";
	$f[]="keyboardhook:i:2";
	$f[]="audiocapturemode:i:0";
	$f[]="videoplaybackmode:i:1";
	$f[]="connection type:i:2";
	$f[]="displayconnectionbar:i:1";
	$f[]="disable wallpaper:i:1";
	$f[]="allow font smoothing:i:0";
	$f[]="allow desktop composition:i:0";
	$f[]="disable full window drag:i:1";
	$f[]="disable menu anims:i:1";
	$f[]="disable themes:i:0";
	$f[]="disable cursor setting:i:0";
	$f[]="bitmapcachepersistenable:i:1";
	$f[]="full address:s:{$RDPProxyListen}";
	$f[]="audiomode:i:1";
	$f[]="redirectprinters:i:1";
	$f[]="redirectcomports:i:0";
	$f[]="redirectsmartcards:i:1";
	$f[]="redirectclipboard:i:1";
	$f[]="redirectposdevices:i:0";
	$f[]="redirectdirectx:i:1";
	$f[]="autoreconnection enabled:i:1";
	$f[]="authentication level:i:2";
	$f[]="prompt for credentials:i:0";
	$f[]="negotiate security layer:i:1";
	$f[]="remoteapplicationmode:i:0";
	$f[]="alternate shell:s:";
	$f[]="shell working directory:s:";
	$f[]="gatewayhostname:s:";
	$f[]="gatewayusagemethod:i:4";
	$f[]="gatewaycredentialssource:i:4";
	$f[]="gatewayprofileusagemethod:i:0";
	$f[]="promptcredentialonce:i:0";
	$f[]="use redirection server name:i:0";
	$f[]="drivestoredirect:s:Data (Z:);";
	$f[]="networkautodetect:i:1";
	$f[]="bandwidthautodetect:i:1";
	$f[]="enableworkspacereconnect:i:0";
	$f[]="rdgiskdcproxy:i:0";
	$f[]="kdcproxyname:s:";
	$f[]="gatewaybrokeringtype:i:0";
	$f[]="username:s:{$ligne['username']}/{$ligne['service']}";
	
	$data=@implode("\r\n", $f);
	header('Content-type: application/x-rdp');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"rdpprody-{$ligne['username']}@{$ligne['service']}.rdp\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = strlen($data);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $data;
	
	
	
}



?>