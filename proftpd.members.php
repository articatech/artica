<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

if(isset($_GET["username-form-id"])){member_id();exit;}	
if(isset($_GET["member-id-js"])){member_id_js();exit;}
if(isset($_GET["member-delete-js"])){member_delete_js();exit;}	
if(isset($_GET["query"])){members_list();exit;}	
if(isset($_POST["id"])){Save();exit;}
if(isset($_POST["member-delete"])){member_delete();exit;}	
page();


function member_id_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$id=$_GET["member-id-js"];
	$title=$tpl->javascript_parse_text("{new_profile}");
	$t=$_GET["t"];

	if($id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array(
				$q->QUERY_SQL("SELECT userid FROM ftpuser WHERE id='$id'","artica_backup"));
		$title=utf8_decode($tpl->javascript_parse_text($ligne["userid"]));
	}

	echo "YahooWin2('990','$page?username-form-id=$id','$title')";

}

function member_delete_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$id=$_GET["member-delete-js"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$q=new mysql();
	$t=time();
	$ligne=mysql_fetch_array(
	$q->QUERY_SQL("SELECT userid FROM ftpuser WHERE id='$id'","artica_backup"));
	
	echo "
var xProftpdDeleteVirtUser$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#PROFTPD_USER').flexReload();
}	
	
function ProftpdDeleteVirtUser$t(){
	if(confirm('$delete {$ligne["userid"]} ?')){
		var XHR = new XHRConnection();
		XHR.appendData('member-delete','$id');
		XHR.sendAndLoad('$page', 'POST',xProftpdDeleteVirtUser$t);
	}
}
	
ProftpdDeleteVirtUser$t();";
	
}

function member_id(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$btname="{add}";
	$q=new mysql();
	$id=intval($_GET["username-form-id"]);
	$sock=new sockets();
	$sock->getFrameWork("proftpd.php?systemusers=yes");
	if($id>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM ftpuser WHERE id='$id'","artica_backup"));
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT gpid FROM radusergroup WHERE username='{$ligne["username"]}'","artica_backup"));
		$gpid=$ligne2["gpid"];
		if(!is_numeric($gpid)){$gpid=0;}
	}

	$REALUSERS=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/SystemUsers"));
	
	
	$zuid="{$ligne["uid"]}:{$ligne["gid"]}";
	
	$REALUSERSF=Field_array_Hash($REALUSERS, "uid-$t",$zuid,"blur()",null,0,"font-size:22px");

	$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{username}:</td>
		<td>". Field_text("userid-$t",$ligne["userid"],"font-size:22px;width:320px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("passwd-$t",$ligne["passwd"],"font-size:22px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{system_user}:</td>
		<td>$REALUSERSF</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{directory}:</td>
		<td>". Field_text("homedir-$t",$ligne["homedir"],"font-size:22px;width:420px")."&nbsp;".button_browse("homedir-$t")."</td>
	</tr>	
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",32)."</td>
	</tr>
</table>
</div>
				

<script>
	var x_Save$t= function (obj) {
	var connection_id='$id';
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(connection_id==0){YahooWin2Hide();}
	$('#PROFTPD_USER').flexReload();
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('userid', encodeURIComponent(document.getElementById('userid-$t').value));
	XHR.appendData('uid', document.getElementById('uid-$t').value);
	XHR.appendData('id', '$id');
	XHR.appendData('passwd', encodeURIComponent(document.getElementById('passwd-$t').value));
	XHR.appendData('homedir', encodeURIComponent(document.getElementById('homedir-$t').value));
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}	
 </script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
	


function page(){
	
		$page=CurrentPageName();
		$tpl=new templates();
		$q=new mysql();
		$sock=new sockets();
		$shortname=$tpl->javascript_parse_text("{member}");
		$nastype=$tpl->javascript_parse_text("{type}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$connection=$tpl->javascript_parse_text("{connection}");
		$add=$tpl->javascript_parse_text("{new_member}");
		$groups=$tpl->javascript_parse_text("{groups2}");
		$members=$tpl->javascript_parse_text("{members}");
		$system_user=$tpl->javascript_parse_text("{system_user}");
		$title=$tpl->javascript_parse_text("FTP {virtual_users}");
		$directory=$tpl->javascript_parse_text("{directory}");
		$freeradius_users_explain=$tpl->javascript_parse_text("{freeradius_users_explain}");
		$about2=$tpl->javascript_parse_text("{about2}");
		$tablewidht=883;
		$t=time();
		$sock=new sockets();
		$sock->getFrameWork("proftpd.php?systemusers=yes");
	
		$buttons="buttons : [
		{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : AddConnection$t},
		],	";
	
	
	
		echo "
		<table class='PROFTPD_USER' style='display: none' id='PROFTPD_USER' style='width:99%;text-align:left'></table>
		<script>
		var MEMM$t='';
		$(document).ready(function(){
		$('#PROFTPD_USER').flexigrid({
		url: '$page?query=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>$shortname</span>', name : 'userid', width : 456, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$system_user</span>', name : 'uid', width : 195, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$directory</span>', name : 'homedir', width : 563, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 62, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$shortname', name : 'userid'},
		{display: '$directory', name : 'homedir'},
	
	
		],
		sortname: 'userid',
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
	$('#PROFTPD_USER').flexReload();
}
	
function enable_ip_authentication_save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
}
	
function Groups$t(){
	Loadjs('freeradius.groups.php');
}
	
function About$t(){
	alert('$freeradius_users_explain');
}
	
var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$t()
}
	

	
function AddConnection$t(){
	Loadjs('$page?member-id-js=');
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
	
	

</script>
";
	}

	
function members_list(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$sock=new sockets();
		$q=new mysql();
		$database="artica_backup";
		$t=$_GET["t"];
		$search='%';
		$table="ftpuser";
		$page=1;
		$data = array();
		$data['rows'] = array();
		$FORCE_FILTER=null;
		$REALUSERS=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/SystemUsers"));
	
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
		$searchstring=string_to_flexquery();
	
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
				
		}
		$rp=50;
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
		$data['page'] = $page;
		$data['total'] = $total;
	
		if(mysql_num_rows($results)==0){
			json_error_show("{no_member_stored_in_this_area}",0);
		}
	
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$val=0;
			$color="black";
	
			$zuid="{$ligne["uid"]}:{$ligne["gid"]}";
			$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?member-delete-js={$ligne['id']}')");
	
			
			$link="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$MyPage?member-id-js={$ligne['id']}&t=$t');\"
			style=\"font-size:22px;text-decoration:underline;color:$color\">";
			
			
			$data['rows'][] = array(
					'id' => $ligne['id'],
					'cell' => array(
						"$link{$ligne['userid']}</a>",
						"$link{$REALUSERS[$zuid]}",
						"$link{$ligne["homedir"]}",
						"<center>$delete</center>",
			)
			);
		}
	
	
		echo json_encode($data);
	
	}	

function Save(){
	
	while (list ($num, $line) = each ($_POST)){
		$_POST[$num]=mysql_escape_string2(url_decode_special_tool($line));
		
	}
	
	$tt=explode(":",$_POST["uid"]);
	$uid=$tt[0];
	$gid=$tt[1];
	$id=$_POST["id"];
	$userid=$_POST["userid"];
	$passwd=$_POST["passwd"];
	$homedir=$_POST["homedir"];
	if($id==0){
		$sql="INSERT INTO `ftpuser` ( `userid`, `passwd`, `uid`, `gid`, `homedir`, `shell`, `count`, `accessed` , `modified`, `LoginAllowed` )
		VALUES ('$userid', '$passwd', '$uid', '$gid', '$homedir', '/bin/false', '', '', '', 'true' );";
	}else{
		$sql="UPDATE `ftpuser` SET 
			`userid`='$userid',
			`passwd`='$passwd',
			`uid`=$uid,
			`gid`=$gid,
			`homedir`='$homedir' WHERE id=$id";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("proftpd.php?chowndirs=yes");
}	

function member_delete(){
	$id=$_POST["member-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM `ftpuser` WHERE id=$id","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
	
