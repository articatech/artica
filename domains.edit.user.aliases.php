<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/charts.php');
include_once ('ressources/class.mimedefang.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["USER_ALIASES_FORM_ADD"])){USER_ALIASES_FORM_ADD();exit;}
table();


function table(){
	$privilege=true;
	if (GetRights_aliases () == 0) {$privilege = false;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=801;
	$aliases_field=681;
	$user=new user($_GET["userid"]);
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{add_new_alias}");
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$title=$tpl->_ENGINE_parse_body("{aliases}:&nbsp;&laquo;$user->uid&raquo;");
	
	
	if($_GET["expanded"]=="usermin"){
		$TB_WIDTH=930;
		$TB_HEIGHT=500;
		$aliases_field=800;
	}
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_entry', bclass: 'Add', onpress : NewItem$t},
	],	";
	
	if(!$privilege){$buttons=null;}
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&userid={$_GET["userid"]}',
	dataType: 'json',
	colModel : [	
		{display: '<span style=font-size:22px>$aliases</span>', name : 'ID', width :$aliases_field, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>TEST</span>', name : 'action', width :80, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'action', width :80, sortable : true, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$aliases', name : 'task'},

	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_ItemDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

function ItemDelete$t(email,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAliases','{$_GET["userid"]}');
	XHR.appendData('aliase',email);
	XHR.sendAndLoad('domains.edit.user.php', 'GET',x_ItemDelete$t);		
	}


function help$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=270','1024','900');
}
	

function NewItem$t(){
	title='$new_entry';
	YahooWin5(750,'$page?USER_ALIASES_FORM_ADD={$_GET["userid"]}&t=$t','$title');
}
	
</script>";
	
	echo $html;		
}	

function items(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$tSource=$_GET["t"];
	$privilege=true;
	if (GetRights_aliases () == 0) {$privilege = false;}	
	$search='';
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){$_POST["query"]=string_to_regex($_POST["query"]);}
		
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = 0;
	$data['rows'] = array();

	$user=new user($_GET["userid"]);
	$aliases = $user->aliases;
	$c=0;
	while ( list ( $num, $ligne ) = each ( $aliases ) ) {
		if($_POST["query"]<>null){if(!preg_match("#{$_POST["query"]}#i", $ligne)){continue;}}
		$c++;
		$logs=null;
		$id=md5($ligne);
		//Loadjs('$page?delete-aliases=yes&mail=$ligne&uid=$userid')
		$delete = imgsimple( 'delete-32.png', '{delete aliase}', "ItemDelete$tSource('$ligne','$id')" );
		
		$test=imgsimple ( 'test-message-32.png', null, "Loadjs('postfix.sendtest.mail.php?rcpt=$ligne')" );
		if (! $privilege) {$delete = null;}
		
	$data['rows'][] = array(
		'id' => "$id",
		'cell' => array(	
			"<code style='font-size:22px;font-weight:bold'>$ligne</code>",
			"<center>$test</center>",
			"<center>$delete</center>",
			)
		);
		$data['total'] = $c;
	}
	if($c==0){json_error_show("no alias");}
	
echo json_encode($data);		
	
}

function USER_ALIASES_FORM_ADD() {
	$t=$_GET["t"];
	$userid = $_GET["USER_ALIASES_FORM_ADD"];
	$ldap = new clladp ( );
	$user = new user ( $userid );
	$domains = $ldap->hash_get_domains_ou ( $user->ou );
	$default_domain=null;
	$email=$user->mail;
	if(preg_match("#.*?@(.+)#", $email,$re)){$default_domain=$re[1];}
	
	$user_domains = Field_array_Hash ( $domains, 'user_domain',$default_domain,null,null,0,'font-size:18px;padding:3px' );
	
	$form_catech_all = "";

	$form_add = "
<div id='$t-div'></div>
<div style='width:98%' class=form>
	<table style='width:100%;'>
    	<tr>
    		<td nowrap colspan=2>
    			<strong style='font-size:18px;'>{add_new_alias}:&laquo;{in_the_same_organization}&raquo;</strong></td>
    	</tr>
    	<tr>
	    	<td valign='top'>
		    	<table>
		    		<tr style='height:70px'>
		    			<td style='vertical-align:middle;padding-top:10px'>" . Field_text ( 'aliases', null, 'width:250px;font-size:18px;padding:3px;text-align:right',null,null,null,false,"AddNewAliasesCheckEnter$t(event)" ) . "</td>
		    			<td style='vertical-align:middle' width=1%><strong style='font-size:18px;'>@</strong></td>
		    			<td style='vertical-align:middle' width=99% align='left'>$user_domains</td>
		    		</tr>
		    	</table>
	    	</td>
    	</tr>
   				<tr>
   						<td nowrap colspan=2>&nbsp;</td>
   				</tr>
   				<tr>
    				<td nowrap colspan=2><strong style='font-size:18px;'>
    					{add_new_alias}:&laquo;{out_of_organization}&raquo;</strong></td>
    			</tr>
    			<tr>
    				<td valign='top'>
	    					<table>
	    						<tr>
	    							<td>" . Field_text ( 'fullaliase', null, 'width:350px;font-size:22px;padding:3px',null,null,null,false,"AddNewAliasesCheckEnter$t(event)"  ) . "</td>
	    						</tr>
	    					</table>
    				</td>
    			</tr>    				
    				<tr>
    					<td colspan=2 align='right'><hr>
    					" . button ( "{add}", "AddNewAliases$t('$userid');" ,"30px") . "
    						
    						
    					</td>
    			</tr>
   				  			
    			</table>";
	
	$html = "
<div class=explain style='font-size:18px'>{aliases_text}:&nbsp;&laquo;<b>{$user->mail}&raquo;</b></div>
$form_add



<script>
	function AddNewAliasesCheckEnter$t(e){
		if(!checkEnter(e)){return;}
		AddNewAliases$t();
	}
	
	var x_AddNewAliasesUser$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		YahooWin5Hide();
		$('#flexRT$t').flexReload();
	}		
	
		
	function  AddNewAliases$t(){
		var uid='{$_GET["USER_ALIASES_FORM_ADD"]}';
		m_userid=uid;
		var aliase=document.getElementById('aliases').value;
		var aliase_domain=document.getElementById('user_domain').value;
		var fullaliase=document.getElementById('fullaliase').value;
		aliase=aliase+'@'+aliase_domain;
		if(fullaliase.length>0){aliase=fullaliase;}
		var XHR = new XHRConnection();
		XHR.appendData('AddAliases',uid);
		XHR.appendData('aliase',aliase);
		XHR.sendAndLoad('domains.edit.user.php', 'GET',x_AddNewAliasesUser$t);
		}	
	
	
</script>
";
	$tpl = new templates ( );
	echo $tpl->_ENGINE_parse_body ( $html );
}
	