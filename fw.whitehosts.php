<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.spamassassin.inc');
include_once('ressources/class.amavis.inc');
include_once('ressources/externals/dns/class.dns.inc');
include_once('ressources/class.resolv.conf.inc');

session_start();
$ldap=new clladp();
if(isset($_GET["loadhelp"])){loadhelp();exit;}

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["newdomain"])){$_POST["white-list-domain"]=$_GET["newdomain"];add_domain_white_save();exit;}
	if(isset($_POST["white-list-host-del"])){hosts_WhiteList_del();exit;}
	if(isset($_POST["white-list-host"])){hosts_WhiteList_add();exit;}
	if(isset($_GET["list-table"])){list_table();exit;}
	if(isset($_GET["add-domain-white-js"])){add_domain_white_js();exit;}
	if(isset($_GET["add-domain-white"])){add_domain_white_popup();exit;}
	if(isset($_POST["white-list-domain"])){add_domain_white_save();exit;}
	
	page();
	
function add_domain_white_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_domain}");
	echo "YahooWin4('650','$page?add-domain-white=yes&t={$_GET["t"]}','$title',true);";
}	
	
function add_domain_white_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<div style='font-size:16px' class=explain>{whitelist_mx_explain}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{domain}:</td>
		<td>". Field_text("domain-$t",null,"font-size:18px;width:95%",null,null,null,false,"SaveCK$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{add}","Save$t()",26)."</td>
	</tr>
	</table>		
	</div>
	
<script>

function SaveCK$t(e){
	if( !checkEnter(e) ){return;}
	Save$t();
}

var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
 	$('#flexRT{$_GET["t"]}').flexReload();
}	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('white-list-domain',document.getElementById('domain-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}	

function add_domain_white_save(){
	$tpl=new templates();
	$domain=$_POST["white-list-domain"];

	getmxrr($domain, $mxhosts,$mxWeight);
	if(count($mxhosts)==0){
		echo $tpl->javascript_parse_text("$domain : {no_mx}");
	}

	
	$q=new mysql();
	while (list ($index, $hostname) = each ($mxhosts) ){
		$ipaddr=gethostbyname($hostname);
		$sql="INSERT IGNORE INTO postfix_whitelist_con (ipaddr,hostname) VALUES('$ipaddr','$hostname')";
		$q->QUERY_SQL($sql,"artica_backup");
		$q->QUERY_SQL("UPDATE iptables SET disable=1 WHERE serverip='$ipaddr'","artica_backup");
		$q->QUERY_SQL("UPDATE iptables SET disable=1 WHERE servername='$hostname'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;continue;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	
}


	
function page(){

		$t=time();
		$page=CurrentPageName();
		$tpl=new templates();
		$users=new usersMenus();
		$sock=new sockets();
		$tt=$_GET["t"];
		$t=time();
		$q=new mysql();
		$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$items=$tpl->_ENGINE_parse_body("{items}");
	
		$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
		$new_item=$tpl->_ENGINE_parse_body("{new_item}");
		$import=$tpl->_ENGINE_parse_body("{import}");
		$title=$tpl->_ENGINE_parse_body("{PostfixAutoBlockDenyAddWhiteList_explain}");
		$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
		$hostname=$tpl->_ENGINE_parse_body("{hostname}");
		$delete=$tpl->_ENGINE_parse_body("{delete}");
		$new_domain=$tpl->_ENGINE_parse_body("{new_domain}");
		$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : AddHostWhite$t},
		{name: '<strong style=font-size:18px>$new_domain</strong>', bclass: 'add', onpress : AddDomainWhite$t},
		],";

	
		$html="
	
	
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
		<script>
		var memid$t='';
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?list-table=yes&t=$t',
		dataType: 'json',
		colModel : [
		
		{display: '<span style=font-size:22px>$ipaddr</span>', name : 'ipaddr', width : 329, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$hostname</span>', name : 'hostname', width :886, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'bounce_error', width : 84, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		
		],
		sortname: 'ipaddr',
		sortorder: 'asc',
		usepager: true,
		title: '<strong style=font-size:30px>$title</strong>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	var x_AddHostWhite$t=function(obj){
    	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
 	  	$('#flexRT$t').flexReload();
      }	
	var x_DelHostWhite$t=function(obj){
    	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
 	  	$('#row'+memid$t).remove();
      }		
	
	function AddHostWhite$t(){
		var server=prompt('$title\\n10.10.10.0/24\\n192.168.1.1\\nhost.domain.tld\\nhost.*.tld');
		if(server){
			var XHR = new XHRConnection();
			XHR.appendData('white-list-host',server);
			XHR.sendAndLoad('$page', 'POST',x_AddHostWhite$t);
			}
		}
		
	function DelHostWhite$t(ip,server,id){
		memid$t=id;
		if(!confirm('$are_you_sure_to_delete '+server+'['+ip+']')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('white-list-host-del',server);
		XHR.appendData('white-list-host-delip',ip);
		XHR.sendAndLoad('$page', 'POST',x_DelHostWhite$t);
	}

	function AddDomainWhite$t(){
		Loadjs('$page?add-domain-white-js=yes&t=$t',true);
	}
	
	</script>";
		echo $html;
	}
	
	
function list_table(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_whitelist_con", "artica_backup")){$q->BuildTables();}
	$table="postfix_whitelist_con";
	$t=$_GET["t"];
	$database="artica_backup";
	$FORCE_FILTER=1;
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("!Error: $table No such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			if(!$q->ok){json_error_show("$q->mysql_error");}
			$total = $ligne["TCOUNT"];
			if($total==0){json_error_show("No rows for $searchstring");}
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		
	
		$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
		$today=date('Y-m-d');
		$style="font-size:14px;";
		
		$unknown=$tpl->_ENGINE_parse_body("{unknown}");
		while ($ligne = mysql_fetch_assoc($results)) {
	
			
			$md=md5(serialize($ligne));
			$cells=array();
			$delete=imgsimple("delete-32.png",null,"DelHostWhite$t('{$ligne["ipaddr"]}','{$ligne["hostname"]}','$md')");
			$cells[]="<span style='font-size:24px;'>{$ligne["ipaddr"]}</span>";
			$cells[]="<span style='font-size:24px;'>{$ligne["hostname"]}</span>";
			$cells[]="<center style='font-size:24px;'>$delete</center>";
				
				
				
			$data['rows'][] = array(
					'id' =>$md,
					'cell' => $cells
			);
	
	
		}
	
		echo json_encode($data);
	}	
function hosts_WhiteList_add(){
		if($_POST["white-list-host"]==null){echo "NULL VALUE";return null;}
	
		$users=new usersMenus();
		$tpl=new templates();
		if(!$users->AsPostfixAdministrator){
			$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
			echo "$error";
			die();
		}
	
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$_POST["white-list-host"])){
			if(strpos($_POST["white-list-host"], "*")==0){
				$ipaddr=gethostbyname($_POST["white-list-host"]);
			}
			$hostname=$_POST["white-list-host"];
		}else{
			$ipaddr=$_POST["white-list-host"];
			$hostname=gethostbyaddr($_POST["white-list-host"]);
		}
	
		$sql="INSERT IGNORE INTO postfix_whitelist_con (ipaddr,hostname) VALUES('$ipaddr','$hostname')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	}
	
function hosts_WhiteList_del(){
		$users=new usersMenus();
		$tpl=new templates();
		if(!$users->AsPostfixAdministrator){
			$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
			echo "$error";
			die();
		}
	
		$found=false;
		$q=new mysql();
		$server=$_POST["white-list-host-del"];
		if($server<>nulll){
			$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='$server'";
			$q->QUERY_SQL($sql,"artica_backup");
			$sql="DELETE FROM postfix_whitelist_con WHERE hostname='$server'";
			$q->QUERY_SQL($sql,"artica_backup");
		}
		if(trim($_POST["white-list-host-delip"])<>null){
			$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='{$_POST["white-list-host-delip"]}'";
			$q->QUERY_SQL($sql,"artica_backup");
			$sql="DELETE FROM postfix_whitelist_con WHERE hostname='{$_POST["white-list-host-delip"]}'";
			$q->QUERY_SQL($sql,"artica_backup");			
		}
		
		
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	
	}	